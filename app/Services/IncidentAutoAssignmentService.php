<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentAutoAssignmentService
{
    public function __construct(private readonly IncidentJurisdictionService $jurisdictionService)
    {
    }

    public function assignRecentUnassignedIncidents(int $minutes = 60): array
    {
        $cutoff = now()->subMinutes($minutes);

        $incidents = Incident::with('marker')
            ->whereNull('team_id')
            ->where('status', 'Reported')
            ->where('created_at', '>=', $cutoff)
            ->when(Schema::hasColumn('incidents', 'duplicate_of_incident_id'), fn ($query) => $query->whereNull('duplicate_of_incident_id'))
            ->when(
                Schema::hasColumn('incidents', 'dispatch_priority'),
                fn ($query) => $query->orderByRaw("CASE LOWER(COALESCE(dispatch_priority, 'medium')) WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            )
            ->orderBy('created_at', 'asc')
            ->get();

        $assignedIncidents = [];

        foreach ($incidents as $incident) {
            $assignedIncident = $this->assignSingleIncident($incident);
            if ($assignedIncident !== null) {
                $assignedIncidents[] = $assignedIncident;
            }
        }

        return [
            'checked' => $incidents->count(),
            'assigned' => $assignedIncidents,
        ];
    }

    public function assignSingleIncidentById(int $incidentId): ?array
    {
        $incident = Incident::with('marker')->find($incidentId);

        if (! $incident) {
            return null;
        }

        return $this->assignSingleIncident($incident);
    }

    public function assignSingleIncident(Incident $incident): ?array
    {
        return DB::transaction(function () use ($incident) {
            $lockedIncident = Incident::with('marker')
                ->whereKey($incident->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedIncident) {
                return null;
            }

            $lockedIncident = $this->jurisdictionService->apply($lockedIncident);

            if (Schema::hasColumn('incidents', 'duplicate_of_incident_id') && $lockedIncident->duplicate_of_incident_id) {
                return null;
            }

            if ($lockedIncident->team_id || $lockedIncident->status !== 'Reported') {
                return null;
            }

            $marker = $lockedIncident->marker;
            if (! $marker || $marker->lat === null || $marker->lng === null) {
                return null;
            }

            $nearestStations = $this->getCandidateStationsForIncident(
                $lockedIncident,
                (float) $marker->lat,
                (float) $marker->lng
            );

            foreach ($nearestStations as $station) {
                $stationId = (int) $station->station_id;
                $team = app(IncidentResourceDispatchService::class)->findAvailableTeamForStation($stationId);

                if (! $team) {
                    continue;
                }

                $resources = app(IncidentResourceDispatchService::class);
                $responseUnit = null;
                if ($resources->incidentRequiresFiretruck($lockedIncident)) {
                    $responseUnit = $resources->reservePrimary(
                        $lockedIncident,
                        $team,
                        $stationId,
                        isset($station->distance_meters) ? (float) $station->distance_meters : null,
                    );
                    if (! $responseUnit) continue;
                } else {
                    $resources->dispatchTeam($team);
                }

                $lockedIncident->team_id = $team->team_id;
                $lockedIncident->status = 'Responding';
                $lockedIncident->updated_at = now();
                $lockedIncident->save();

                return [
                    'incident_id' => $lockedIncident->incident_id,
                    'team_id' => $team->team_id,
                    'station_id' => $stationId,
                    'status' => 'en-route',
                    'team_status_updated' => true,
                    'response_unit_id' => $responseUnit?->incident_response_unit_id,
                    'created_at' => optional($lockedIncident->created_at)->toISOString(),
                ];
            }

            return null;
        });
    }

    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function getStationsSortedByDistance(float $lat, float $lng)
    {
        return Station::with('marker')
            ->get()
            ->filter(function ($station) {
                return $station->marker && $station->marker->lat !== null && $station->marker->lng !== null;
            })
            ->map(function ($station) use ($lat, $lng) {
                $station->distance_meters = $this->calculateDistanceMeters(
                    $lat,
                    $lng,
                    (float) $station->marker->lat,
                    (float) $station->marker->lng
                );

                return $station;
            })
            ->sortBy('distance_meters')
            ->values();
    }

    private function getCandidateStationsForIncident(Incident $incident, float $lat, float $lng)
    {
        $stationLimit = strtolower((string) ($incident->marker?->category ?? '')) === 'fire' ? 3 : 1;
        $nearbyStations = $this->getStationsSortedByDistance($lat, $lng);
        $primaryStationId = (int) ($incident->station_id ?? 0);
        $orderedStations = collect();

        if ($primaryStationId > 0) {
            $primaryStation = Station::with('marker')->find($primaryStationId);
            if ($primaryStation) {
                if ($primaryStation->marker && $primaryStation->marker->lat !== null && $primaryStation->marker->lng !== null) {
                    $primaryStation->distance_meters = $this->calculateDistanceMeters(
                        $lat,
                        $lng,
                        (float) $primaryStation->marker->lat,
                        (float) $primaryStation->marker->lng
                    );
                } else {
                    $primaryStation->distance_meters = 0;
                }

                $orderedStations->push($primaryStation);
            }
        }

        return $orderedStations
            ->concat($nearbyStations->reject(fn ($station) => (int) $station->station_id === $primaryStationId))
            ->unique(fn ($station) => (int) $station->station_id)
            ->take($stationLimit)
            ->values();
    }

}
