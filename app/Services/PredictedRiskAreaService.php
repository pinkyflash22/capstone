<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PredictedRiskAreaService
{
    public function buildForecastAreas(?int $stationId): Collection
    {
        $analyses = DB::table('analysis')
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->orderByDesc('created_at')
            ->orderByDesc('analysis_id')
            ->limit(100)
            ->get([
                'analysis_id',
                'station_id',
                'risk_level',
                'confidence_data',
                'analysis_data',
                'revision_number',
                'created_at',
            ]);

        $locations = $this->forecastBarangayLocations($stationId);
        $areas = collect();
        $seen = [];

        foreach ($analyses as $analysis) {
            $analysisData = $this->decodeJsonArray($analysis->analysis_data);
            $rawData = is_array($analysisData['raw_data'] ?? null) ? $analysisData['raw_data'] : $analysisData;
            $scope = strtolower((string) ($rawData['prediction_scope'] ?? 'current_month'));
            $overlayType = $scope === 'next_month' ? 'next_month_forecast' : 'current_analysis';
            $predictions = collect($rawData['barangay_predictions'] ?? [])
                ->filter(fn ($item) => is_array($item) && (int) ($item['barangay_id'] ?? 0) > 0);

            foreach ($predictions as $prediction) {
                $barangayId = (int) $prediction['barangay_id'];
                $dedupeKey = $overlayType . ':' . $barangayId;
                if (isset($seen[$dedupeKey])) {
                    continue;
                }

                $location = $locations->get($barangayId);
                if (! $location) {
                    continue;
                }

                $riskLevel = $this->normalizeForecastRiskLevel(
                    $prediction['risk_level'] ?? $analysis->risk_level ?? 'low'
                );
                $probability = $prediction['probability_percent']
                    ?? (isset($prediction['probability']) ? ((float) $prediction['probability'] * 100) : null);
                $drivers = collect($prediction['drivers'] ?? [])
                    ->map(fn ($driver) => is_array($driver) ? ($driver['summary'] ?? $driver['feature'] ?? null) : $driver)
                    ->filter()
                    ->map(fn ($driver) => (string) $driver)
                    ->values()
                    ->all();
                $targetLabel = data_get($rawData, 'prediction_context.target_month_label')
                    ?? data_get($rawData, 'prediction_context.target_period_label');

                $area = [
                    'id' => "{$overlayType}-{$analysis->analysis_id}-{$barangayId}",
                    'overlay_type' => $overlayType,
                    'source_type' => 'saved_ai_forecast',
                    'name' => ($prediction['barangay_name'] ?? $location->barangay_name) . ' fire-risk forecast',
                    'barangay_id' => $barangayId,
                    'barangay_names' => [$prediction['barangay_name'] ?? $location->barangay_name],
                    'city_name' => $prediction['city_name'] ?? $location->city_name,
                    'risk_level' => $riskLevel,
                    'risk_score' => $probability !== null ? round((float) $probability, 2) : null,
                    'probability_percent' => $probability !== null ? round((float) $probability, 2) : null,
                    'confidence_percent' => is_numeric($analysis->confidence_data) ? (int) $analysis->confidence_data : null,
                    'predicted_fire_incidents' => (int) ($prediction['expected_month_total_fire_incidents']
                        ?? $prediction['predicted_fire_incidents']
                        ?? $prediction['predicted_incident_count']
                        ?? 0),
                    'target_label' => $targetLabel,
                    'analysis_id' => (int) $analysis->analysis_id,
                    'revision_number' => (int) ($analysis->revision_number ?? 1),
                    'drivers' => $drivers,
                    'metrics' => [],
                ];

                if (! empty($location->geojson)) {
                    $area['geometry_type'] = 'geojson';
                    $area['geometry_precision'] = 'barangay_boundary';
                    $area['geojson'] = $location->geojson;
                } elseif (is_numeric($location->lat) && is_numeric($location->lng)) {
                    // A barangay hall is only a reference point. Do not imply that a
                    // fixed geographic radius around the hall is the predicted zone.
                    $area['geometry_type'] = 'point';
                    $area['geometry_precision'] = 'barangay_hall_reference';
                    $area['center'] = ['lat' => (float) $location->lat, 'lng' => (float) $location->lng];
                } else {
                    continue;
                }

                $seen[$dedupeKey] = true;
                $areas->push($area);
            }
        }

        return $areas->values();
    }

    public function buildPredictedAreas(?int $stationId, int $proximityMeters = 100): Collection
    {
        $evidence = $this->riskEvidencePoints($stationId)
            ->concat($this->historicalIncidentEvidencePoints($stationId))
            ->sortBy([
                ['lat', 'asc'],
                ['lng', 'asc'],
            ])
            ->values();

        if ($evidence->isEmpty()) {
            return collect();
        }

        return $this->clusterEvidencePoints($evidence, $proximityMeters)
            ->map(fn (array $cluster, int $index) => $this->buildAreaFromCluster($cluster, $index, $proximityMeters))
            ->filter()
            ->values();
    }

    protected function riskEvidencePoints(?int $stationId): Collection
    {
        return DB::table('risk_areas')
            ->join('markers', 'risk_areas.marker_id', '=', 'markers.marker_id')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->whereNotNull('markers.lat')
            ->whereNotNull('markers.lng')
            ->where(function ($query) {
                $query->where('risk_areas.review_status', 'approved')
                    ->orWhere('risk_areas.status', 'verified');
            })
            ->where(function ($query) {
                $query->whereNull('risk_areas.review_type')
                    ->orWhere('risk_areas.review_type', '!=', 'delete');
            })
            ->when($stationId, fn ($query) => $query->where('risk_areas.station_id', $stationId))
            ->select([
                'risk_areas.risk_area_id',
                'risk_areas.marker_id',
                'risk_areas.entry_type',
                'risk_areas.risk_type',
                'risk_areas.hazard_category',
                'risk_areas.hazard_type',
                'risk_areas.risk_score',
                'risk_areas.assessment_level',
                'risk_areas.street_or_sitio',
                'risk_areas.condition_label',
                'risk_areas.estimated_affected_households',
                'risk_areas.blocked_hydrant',
                'risk_areas.combustible_materials_present',
                'risk_areas.informal_settlement',
                'risk_areas.cooking_fire_prone_activity',
                'risk_areas.poor_waste_disposal',
                'risk_areas.previous_fire_prone_location',
                'risk_areas.road_accessibility',
                'risk_areas.population_density_level',
                'risk_areas.building_density_level',
                'risk_areas.created_at',
                'barangays.barangay_id',
                'barangays.barangay_name',
                'cities.city_name',
                'markers.title as marker_title',
                'markers.lat',
                'markers.lng',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'source_type' => 'risk_area',
                    'source_id' => (int) $row->risk_area_id,
                    'marker_id' => $row->marker_id ? (int) $row->marker_id : null,
                    'marker_title' => $row->marker_title,
                    'entry_type' => $row->entry_type ?: 'risk',
                    'risk_type' => $row->risk_type,
                    'hazard_category' => $row->hazard_category,
                    'hazard_type' => $row->hazard_type,
                    'barangay_id' => (int) $row->barangay_id,
                    'barangay_name' => $row->barangay_name,
                    'city_name' => $row->city_name,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'risk_score' => $row->risk_score !== null ? (float) $row->risk_score : null,
                    'assessment_level' => strtolower((string) ($row->assessment_level ?? '')),
                    'street_or_sitio' => $row->street_or_sitio,
                    'condition_label' => $row->condition_label,
                    'population_density_level' => $row->population_density_level,
                    'building_density_level' => $row->building_density_level,
                    'estimated_affected_households' => (int) ($row->estimated_affected_households ?? 0),
                    'created_at' => $row->created_at,
                    'pressure_flags' => [
                        'blocked_hydrant' => ! empty($row->blocked_hydrant),
                        'combustible_materials_present' => ! empty($row->combustible_materials_present),
                        'informal_settlement' => ! empty($row->informal_settlement),
                        'cooking_fire_prone_activity' => ! empty($row->cooking_fire_prone_activity),
                        'poor_waste_disposal' => ! empty($row->poor_waste_disposal),
                        'previous_fire_prone_location' => ! empty($row->previous_fire_prone_location),
                        'poor_access' => in_array($row->road_accessibility, ['difficult', 'very_difficult'], true),
                    ],
                ];
            });
    }

    protected function historicalIncidentEvidencePoints(?int $stationId): Collection
    {
        return DB::table('historical_fire_incidents')
            ->join('incidents', 'historical_fire_incidents.incident_id', '=', 'incidents.incident_id')
            ->join('markers', 'incidents.marker_id', '=', 'markers.marker_id')
            ->leftJoin('teams', 'incidents.team_id', '=', 'teams.team_id')
            ->leftJoin('barangays', 'historical_fire_incidents.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->whereNotNull('markers.lat')
            ->whereNotNull('markers.lng')
            ->where(function ($query) {
                $query->whereNull('historical_fire_incidents.is_verified')
                    ->orWhere('historical_fire_incidents.is_verified', true);
            })
            ->when($stationId, function ($query) use ($stationId) {
                $query->where(function ($stationQuery) use ($stationId) {
                    $stationQuery->where('incidents.station_id', $stationId)
                        ->orWhere('teams.station_id', $stationId);
                });
            })
            ->select([
                'historical_fire_incidents.historical_fire_incident_id',
                'historical_fire_incidents.barangay_id',
                'historical_fire_incidents.incident_date',
                'historical_fire_incidents.incident_count',
                'historical_fire_incidents.injured_count',
                'historical_fire_incidents.death_count',
                'historical_fire_incidents.estimated_damage',
                'barangays.barangay_name',
                'cities.city_name',
                'markers.lat',
                'markers.lng',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'source_type' => 'historical_incident',
                    'source_id' => (int) $row->historical_fire_incident_id,
                    'barangay_id' => $row->barangay_id ? (int) $row->barangay_id : null,
                    'barangay_name' => $row->barangay_name,
                    'city_name' => $row->city_name,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'incident_date' => $row->incident_date,
                    'incident_count' => max(1, (int) ($row->incident_count ?? 1)),
                    'injured_count' => (int) ($row->injured_count ?? 0),
                    'death_count' => (int) ($row->death_count ?? 0),
                    'estimated_damage' => $row->estimated_damage !== null ? (float) $row->estimated_damage : 0.0,
                ];
            });
    }

    protected function forecastBarangayLocations(?int $stationId): Collection
    {
        $rows = DB::table('barangays')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->leftJoin('barangay_halls', 'barangays.barangay_id', '=', 'barangay_halls.barangay_id')
            ->leftJoin('markers', 'barangay_halls.marker_id', '=', 'markers.marker_id')
            ->leftJoin('boundary_location', function ($join) use ($stationId) {
                $join->on('barangays.barangay_id', '=', 'boundary_location.barangay_id')
                    ->where('boundary_location.boundary_type', '=', 'barangay');
                if ($stationId) {
                    $join->where(function ($query) use ($stationId) {
                        $query->where('boundary_location.station_id', $stationId)
                            ->orWhereNull('boundary_location.station_id');
                    });
                }
            })
            ->select([
                'barangays.barangay_id',
                'barangays.barangay_name',
                'cities.city_name',
                'markers.lat',
                'markers.lng',
                'boundary_location.geojson',
                'boundary_location.station_id as boundary_station_id',
                'boundary_location.updated_at as boundary_updated_at',
            ])
            ->get()
            ->map(function ($row) {
                $row->geojson = $this->decodeJsonArray($row->geojson);
                return $row;
            })
            ->sortByDesc(fn ($row) => [
                ! empty($row->geojson) ? 1 : 0,
                $row->boundary_station_id !== null ? 1 : 0,
                (string) ($row->boundary_updated_at ?? ''),
            ]);

        return $rows->unique('barangay_id')->keyBy(fn ($row) => (int) $row->barangay_id);
    }

    protected function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizeForecastRiskLevel(mixed $level): string
    {
        $normalized = strtolower(trim((string) $level));
        if (in_array($normalized, ['moderate', 'medium'], true)) {
            return 'medium';
        }

        return in_array($normalized, ['high', 'low'], true) ? $normalized : 'low';
    }

    protected function clusterEvidencePoints(Collection $points, int $proximityMeters): Collection
    {
        $clusters = [];

        foreach ($points as $point) {
            $matchedIndex = null;

            foreach ($clusters as $index => $cluster) {
                $isNearCluster = collect($cluster['points'])->contains(function (array $existingPoint) use ($point, $proximityMeters) {
                    return $this->distanceMeters(
                        (float) $point['lat'],
                        (float) $point['lng'],
                        (float) $existingPoint['lat'],
                        (float) $existingPoint['lng']
                    ) <= $proximityMeters;
                });

                if ($isNearCluster) {
                    $matchedIndex = $index;
                    break;
                }
            }

            if ($matchedIndex === null) {
                $clusters[] = [
                    'points' => [$point],
                ];

                continue;
            }

            $clusters[$matchedIndex]['points'][] = $point;
        }

        return collect($clusters)->map(function (array $cluster) {
            $points = collect($cluster['points'])->values();
            $centerLat = $points->avg('lat');
            $centerLng = $points->avg('lng');
            $radius = $points->reduce(function (float $carry, array $point) use ($centerLat, $centerLng) {
                $distance = $this->distanceMeters(
                    (float) $centerLat,
                    (float) $centerLng,
                    (float) $point['lat'],
                    (float) $point['lng']
                );

                return max($carry, $distance);
            }, 0.0);

            $cluster['points'] = $points->all();
            $cluster['center_lat'] = round((float) $centerLat, 7);
            $cluster['center_lng'] = round((float) $centerLng, 7);
            $cluster['radius_meters'] = (int) round(min(220, max(90, $radius + 35)));

            return $cluster;
        });
    }

    protected function buildAreaFromCluster(array $cluster, int $index, int $proximityMeters): ?array
    {
        $points = collect($cluster['points']);
        $riskPoints = $points->where('source_type', 'risk_area')->values();
        $incidentPoints = $points->where('source_type', 'historical_incident')->values();

        if ($riskPoints->isEmpty() || $points->count() < 2) {
            return null;
        }

        $highAssessments = $riskPoints->filter(fn (array $point) => $point['assessment_level'] === 'high')->count();
        $moderateAssessments = $riskPoints->filter(fn (array $point) => in_array($point['assessment_level'], ['moderate', 'medium'], true))->count();
        $hazards = $riskPoints->filter(fn (array $point) => ($point['entry_type'] ?? 'risk') === 'hazard')->count();
        $risks = $riskPoints->count() - $hazards;
        $maxRiskScore = (float) ($riskPoints->max('risk_score') ?? 0);
        $avgRiskScore = round((float) ($riskPoints->avg(fn (array $point) => $point['risk_score'] ?? 0) ?? 0), 2);
        $pressure = $riskPoints->sum(fn (array $point) => count(array_filter($point['pressure_flags'] ?? [])));
        $affectedHouseholds = (int) $riskPoints->sum('estimated_affected_households');
        $historicalIncidentCount = $incidentPoints->count();
        $incidentLoad = (int) $incidentPoints->sum('incident_count');
        $concentrationFactor = max(0, $points->count() - 1);
        $recentIncidentCount = $incidentPoints->filter(function (array $point) {
            if (empty($point['incident_date'])) {
                return false;
            }

            try {
                return now()->diffInDays($point['incident_date']) <= 365;
            } catch (\Throwable) {
                return false;
            }
        })->count();

        $weightedScore = max($maxRiskScore, $avgRiskScore);
        $weightedScore += min(18, $pressure * 4);
        $weightedScore += min(16, $incidentLoad * 3);
        $weightedScore += min(12, $concentrationFactor * 3);
        $weightedScore += min(8, $recentIncidentCount * 4);

        if ($highAssessments > 0) {
            $weightedScore = max($weightedScore, 72);
        } elseif ($moderateAssessments > 0) {
            $weightedScore = max($weightedScore, 52);
        }

        $weightedScore = round(min(100, $weightedScore), 2);

        if (! $this->hasMeaningfulPredictionSignal(
            $weightedScore,
            $points->count(),
            $pressure,
            $historicalIncidentCount,
            $highAssessments,
            $moderateAssessments
        )) {
            return null;
        }

        $riskLevel = $this->classifyClusterRiskLevel(
            $weightedScore,
            $pressure,
            $historicalIncidentCount,
            $highAssessments,
            $moderateAssessments,
            $points->count()
        );

        if (! $riskLevel) {
            return null;
        }

        $barangayNames = $points->pluck('barangay_name')
            ->filter()
            ->countBy()
            ->sortDesc();
        $cityNames = $points->pluck('city_name')
            ->filter()
            ->unique()
            ->values();
        $primaryBarangay = (string) ($barangayNames->keys()->first() ?? 'Nearby area');
        $primaryRiskPoint = $riskPoints
            ->sortByDesc(fn (array $point) => [
                (float) ($point['risk_score'] ?? 0),
                $point['assessment_level'] === 'high' ? 2 : ($point['assessment_level'] === 'moderate' || $point['assessment_level'] === 'medium' ? 1 : 0),
                strtotime((string) ($point['created_at'] ?? '1970-01-01')),
            ])
            ->first();
        $pressureFactors = collect($riskPoints)
            ->flatMap(function (array $point) {
                return collect($point['pressure_flags'] ?? [])
                    ->filter()
                    ->keys()
                    ->values();
            })
            ->countBy()
            ->sortDesc()
            ->keys()
            ->values()
            ->all();

        return [
            'id' => 'predicted-zone-' . ($index + 1),
            'name' => 'Predicted fire-risk zone near ' . $primaryBarangay,
            'city_name' => $cityNames->isNotEmpty() ? $cityNames->implode(', ') : 'Clustered nearby evidence',
            'barangay_id' => $primaryRiskPoint['barangay_id'] ?? null,
            'barangay_names' => $barangayNames->keys()->values()->all(),
            'primary_risk_area_id' => $primaryRiskPoint['source_id'] ?? null,
            'primary_marker_id' => $primaryRiskPoint['marker_id'] ?? null,
            'primary_marker_title' => $primaryRiskPoint['marker_title'] ?? null,
            'primary_entry_type' => $primaryRiskPoint['entry_type'] ?? null,
            'primary_condition_label' => $primaryRiskPoint['condition_label'] ?? null,
            'supporting_risk_area_ids' => $riskPoints->pluck('source_id')->map(fn ($id) => (int) $id)->values()->all(),
            'supporting_risk_count' => $riskPoints->count(),
            'risk_level' => $riskLevel,
            'risk_score' => $weightedScore,
            'risk_label' => ucfirst($riskLevel) . ' predicted fire-risk zone',
            'drivers' => $this->clusterDrivers(
                $highAssessments,
                $moderateAssessments,
                $hazards,
                $risks,
                $historicalIncidentCount,
                $incidentLoad,
                $pressure,
                $points->count(),
                $proximityMeters
            ),
            'contributing_factors' => $pressureFactors,
            'metrics' => [
                'verified_records' => $riskPoints->count(),
                'hazards' => $hazards,
                'risks' => $risks,
                'historical_incidents' => $historicalIncidentCount,
                'incident_load' => $incidentLoad,
                'high_assessments' => $highAssessments,
                'moderate_assessments' => $moderateAssessments,
                'max_risk_score' => $maxRiskScore > 0 ? round($maxRiskScore, 2) : null,
                'avg_risk_score' => $avgRiskScore > 0 ? $avgRiskScore : null,
                'affected_households' => $affectedHouseholds,
                'support_points' => $points->count(),
                'cluster_radius_meters' => (int) $cluster['radius_meters'],
                'pressure_score' => $pressure,
            ],
            'geometry_type' => 'circle',
            'center' => [
                'lat' => (float) $cluster['center_lat'],
                'lng' => (float) $cluster['center_lng'],
            ],
            'radius_meters' => (int) $cluster['radius_meters'],
            'evidence_points' => $points->values()->all(),
        ];
    }

    protected function hasMeaningfulPredictionSignal(
        float $weightedScore,
        int $supportPoints,
        int $pressure,
        int $historicalIncidentCount,
        int $highAssessments,
        int $moderateAssessments
    ): bool {
        if ($supportPoints < 2) {
            return false;
        }

        return $weightedScore >= 50
            || ($highAssessments > 0 && ($pressure >= 2 || $historicalIncidentCount > 0))
            || ($moderateAssessments > 0 && $historicalIncidentCount > 0)
            || ($pressure >= 3 && $historicalIncidentCount > 0);
    }

    protected function classifyClusterRiskLevel(
        float $weightedScore,
        int $pressure,
        int $historicalIncidentCount,
        int $highAssessments,
        int $moderateAssessments,
        int $supportPoints
    ): ?string {
        if (
            $weightedScore >= 72
            || ($highAssessments > 0 && ($historicalIncidentCount > 0 || $pressure >= 3))
            || ($pressure >= 5 && $supportPoints >= 3)
        ) {
            return 'high';
        }

        if (
            $weightedScore >= 50
            || ($moderateAssessments > 0 && $supportPoints >= 2)
            || ($historicalIncidentCount > 0 && $pressure >= 2)
        ) {
            return 'medium';
        }

        return null;
    }

    protected function clusterDrivers(
        int $highAssessments,
        int $moderateAssessments,
        int $hazards,
        int $risks,
        int $historicalIncidentCount,
        int $incidentLoad,
        int $pressure,
        int $supportPoints,
        int $proximityMeters
    ): array {
        return collect([
            $highAssessments > 0 ? "{$highAssessments} high field assessment(s) inside the cluster" : null,
            $moderateAssessments > 0 ? "{$moderateAssessments} moderate field assessment(s) inside the cluster" : null,
            $hazards > 0 ? "{$hazards} approved hazard marker(s) supporting the zone" : null,
            $risks > 0 ? "{$risks} approved risk marker(s) supporting the zone" : null,
            $historicalIncidentCount > 0 ? "{$historicalIncidentCount} nearby historical incident point(s) linked to the zone" : null,
            $incidentLoad > 0 ? "{$incidentLoad} historical fire occurrence signal(s) in nearby records" : null,
            $pressure > 0 ? "{$pressure} field pressure factor(s) contributing to the prediction" : null,
            $supportPoints > 1 ? "{$supportPoints} nearby supporting evidence point(s) clustered within {$proximityMeters}m" : null,
        ])->filter()->values()->all();
    }

    protected function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $angle = 2 * asin(sqrt(
            sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2
        ));

        return $angle * $earthRadius;
    }
}
