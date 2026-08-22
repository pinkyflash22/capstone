<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentJurisdictionService
{
    public function __construct(private readonly BoundaryMatcherService $boundaryMatcherService)
    {
    }

    public function resolve(float $lat, float $lng): array
    {
        if (! Schema::hasTable('boundary_location')) {
            return ['barangay_id' => null, 'station_id' => null];
        }

        $boundary = $this->boundaryMatcherService->locateBarangayBoundary($lat, $lng);
        $barangayId = $boundary?->barangay_id ? (int) $boundary->barangay_id : null;
        $stationId = null;

        if ($barangayId && Schema::hasTable('station_barangay_coverage')) {
            $stationId = DB::table('station_barangay_coverage')
                ->where('barangay_id', $barangayId)
                ->where('is_active', true)
                ->orderByRaw("CASE coverage_type WHEN 'primary' THEN 1 WHEN 'support' THEN 2 ELSE 3 END")
                ->orderBy('station_barangay_coverage_id')
                ->value('station_id');
        }

        $stationId ??= $boundary?->station_id;

        return [
            'barangay_id' => $barangayId,
            'station_id' => $stationId ? (int) $stationId : null,
        ];
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
                $updates['barangay_resolution_source'] = 'saved_boundary';
                $updates['barangay_resolved_at'] = now();
            }
        }

        if ($jurisdiction['station_id'] && ! $incident->station_id) {
            $updates['station_id'] = $jurisdiction['station_id'];
        }

        if ($updates !== []) {
            $incident->forceFill($updates)->save();
        }

        return $incident;
    }
}
