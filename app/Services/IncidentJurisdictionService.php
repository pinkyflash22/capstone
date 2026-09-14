<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\BarangayHall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentJurisdictionService
{
    public function __construct(private readonly BoundaryMatcherService $boundaryMatcherService)
    {
    }

    public function resolve(
        float $lat,
        float $lng,
        ?float $accuracyMeters = null,
        ?array $allowedBarangayIds = null
    ): array
    {
        if (! Schema::hasTable('boundary_location')) {
            return [
                'barangay_id' => null,
                'station_id' => null,
                'resolution_source' => 'boundary_unavailable',
                'boundary_distance_meters' => null,
            ];
        }

        $boundary = $this->boundaryMatcherService->locateBarangayBoundary($lat, $lng, $allowedBarangayIds);
        $resolutionSource = 'saved_boundary';

        if (! $boundary) {
            // Even an otherwise good phone fix can sit outside a hand-drawn
            // polygon. Only accept a nearby edge, capped so another town cannot
            // become the incident jurisdiction.
            $toleranceMeters = max(150.0, min(500.0, ($accuracyMeters ?? 100.0) * 1.5));
            $boundary = $this->boundaryMatcherService->locateNearbyBarangayBoundary(
                $lat,
                $lng,
                $toleranceMeters,
                $allowedBarangayIds
            );
            $resolutionSource = $boundary ? 'saved_boundary_vicinity' : 'boundary_unmatched';
        }

        $barangayId = $boundary?->barangay_id ? (int) $boundary->barangay_id : null;
        $barangayHallDistanceMeters = null;

        if (! $barangayId) {
            $hallMatch = $this->locateNearbyBarangayHall($lat, $lng, $accuracyMeters, $allowedBarangayIds);
            if ($hallMatch) {
                $barangayId = (int) $hallMatch['barangay_id'];
                $barangayHallDistanceMeters = (float) $hallMatch['distance_meters'];
                $resolutionSource = 'barangay_hall_vicinity';
            }
        }

        $stationId = null;

        if ($barangayId && Schema::hasTable('station_barangay_coverage')) {
            $stationId = DB::table('station_barangay_coverage')
                ->where('barangay_id', $barangayId)
                ->where(function ($query) {
                    $query->whereNull('is_active')->orWhere('is_active', true);
                })
                ->orderByRaw("CASE coverage_type WHEN 'primary' THEN 1 WHEN 'support' THEN 2 ELSE 3 END")
                ->orderBy('station_barangay_coverage_id')
                ->value('station_id');
        }

        $stationId ??= $boundary?->station_id;

        return [
            'barangay_id' => $barangayId,
            'station_id' => $stationId ? (int) $stationId : null,
            'resolution_source' => $resolutionSource,
            'boundary_distance_meters' => isset($boundary?->boundary_distance_meters)
                ? round((float) $boundary->boundary_distance_meters, 2)
                : ($boundary ? 0.0 : ($barangayHallDistanceMeters !== null ? round($barangayHallDistanceMeters, 2) : null)),
        ];
    }

    private function locateNearbyBarangayHall(
        float $lat,
        float $lng,
        ?float $accuracyMeters = null,
        ?array $allowedBarangayIds = null
    ): ?array {
        if (! Schema::hasTable('barangay_halls')) {
            return null;
        }

        $maximumDistanceMeters = max(500.0, min(1500.0, ($accuracyMeters ?? 100.0) * 3.0));
        $halls = BarangayHall::query()
            ->with('marker:marker_id,lat,lng')
            ->whereNotNull('barangay_id')
            ->when($allowedBarangayIds !== null, function ($query) use ($allowedBarangayIds) {
                return empty($allowedBarangayIds)
                    ? $query->whereRaw('1 = 0')
                    : $query->whereIn('barangay_id', $allowedBarangayIds);
            })
            ->get();

        return $halls
            ->map(function (BarangayHall $hall) use ($lat, $lng) {
                if (! $hall->marker || $hall->marker->lat === null || $hall->marker->lng === null) {
                    return null;
                }

                return [
                    'barangay_id' => (int) $hall->barangay_id,
                    'distance_meters' => $this->distanceMeters(
                        $lat,
                        $lng,
                        (float) $hall->marker->lat,
                        (float) $hall->marker->lng
                    ),
                ];
            })
            ->filter(fn (?array $match) => $match !== null
                && $match['barangay_id'] > 0
                && $match['distance_meters'] <= $maximumDistanceMeters)
            ->sortBy('distance_meters')
            ->first();
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
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

    public function apply(Incident $incident): Incident
    {
        $incident->loadMissing('marker');
        $marker = $incident->marker;

        if (! $marker || $marker->lat === null || $marker->lng === null) {
            return $incident;
        }

        $jurisdiction = $this->resolve((float) $marker->lat, (float) $marker->lng);
        $updates = [];

        if ($jurisdiction['barangay_id'] && (int) $incident->barangay_id !== $jurisdiction['barangay_id']) {
            $updates['barangay_id'] = $jurisdiction['barangay_id'];
            if (Schema::hasColumn('incidents', 'barangay_resolution_status')) {
                $updates['barangay_resolution_status'] = 'matched';
                $updates['barangay_resolution_source'] = $jurisdiction['resolution_source'];
                $updates['barangay_resolved_at'] = now();
            }
        }

        if ($jurisdiction['station_id'] && (int) $incident->station_id !== $jurisdiction['station_id']) {
            $updates['station_id'] = $jurisdiction['station_id'];
        }

        if ($updates !== []) {
            $incident->forceFill($updates)->save();
        }

        return $incident;
    }
}
