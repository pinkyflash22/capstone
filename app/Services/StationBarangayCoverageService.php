<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StationBarangayCoverageService
{
    public function syncAll(?int $stationId = null): array
    {
        $stations = DB::table('stations')
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->orderBy('station_id')
            ->get(['station_id', 'station_name', 'address_id']);

        $summary = ['stations' => 0, 'barangays' => 0, 'unresolved' => []];
        foreach ($stations as $station) {
            try {
                $count = $this->syncStation((int) $station->station_id);
                $summary['stations']++;
                $summary['barangays'] += $count;
            } catch (RuntimeException $exception) {
                $summary['unresolved'][] = [
                    'station_id' => (int) $station->station_id,
                    'station_name' => $station->station_name,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $summary;
    }

    public function syncStation(int $stationId): int
    {
        $station = DB::table('stations')->where('station_id', $stationId)->first();
        if (! $station) {
            throw new RuntimeException("Station {$stationId} was not found.");
        }

        [$cityId, $source] = $this->resolveStationCity($station);
        if (! $cityId) {
            throw new RuntimeException('No city could be resolved from the station address, evidence, or station name.');
        }

        $barangayIds = DB::table('barangays')
            ->where('city_id', $cityId)
            ->orderBy('barangay_name')
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($barangayIds->isEmpty()) {
            throw new RuntimeException("The resolved city {$cityId} has no barangay records.");
        }

        DB::transaction(function () use ($stationId, $barangayIds, $source) {
            DB::table('station_barangay_coverage')
                ->where('station_id', $stationId)
                ->where('source', 'like', 'auto:%')
                ->whereNotIn('barangay_id', $barangayIds->all())
                ->update(['is_active' => false, 'updated_at' => now()]);

            $rows = $barangayIds->map(fn ($barangayId) => [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'coverage_type' => 'primary',
                'source' => $source,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('station_barangay_coverage')->upsert(
                $rows,
                ['station_id', 'barangay_id'],
                ['coverage_type', 'source', 'is_active', 'updated_at']
            );
        });

        return $barangayIds->count();
    }

    protected function resolveStationCity(object $station): array
    {
        $addressCity = DB::table('full_addresses')
            ->join('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
            ->where('full_addresses.address_id', $station->address_id)
            ->value('barangays.city_id');
        if ($addressCity) {
            return [(int) $addressCity, 'auto:station_address_city'];
        }

        $riskCity = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->where('risk_areas.station_id', $station->station_id)
            ->orderByDesc('risk_areas.updated_at')
            ->value('barangays.city_id');
        if ($riskCity) {
            return [(int) $riskCity, 'auto:station_evidence_city'];
        }

        $stationName = $this->normalize((string) $station->station_name);
        $city = DB::table('cities')->get(['city_id', 'city_name'])
            ->filter(fn ($candidate) => str_contains($stationName, $this->normalize((string) $candidate->city_name)))
            ->sortByDesc(fn ($candidate) => strlen($this->normalize((string) $candidate->city_name)))
            ->first();

        return $city
            ? [(int) $city->city_id, 'auto:station_name_city']
            : [null, null];
    }

    protected function normalize(string $value): string
    {
        return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', ' ', $value)));
    }
}
