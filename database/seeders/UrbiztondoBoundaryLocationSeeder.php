<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

class UrbiztondoBoundaryLocationSeeder extends Seeder
{
    /**
     * @throws JsonException
     */
    public function run(): void
    {
        $cityId = DB::table('cities')
            ->whereRaw('LOWER(city_name) = ?', ['urbiztondo'])
            ->value('city_id');

        if (! $cityId) {
            throw new RuntimeException('Urbiztondo city must be seeded before its barangay boundaries.');
        }

        $stationId = DB::table('stations')
            ->where('station_code', 'ST-001')
            ->value('station_id')
            ?? DB::table('stations')
                ->whereRaw('LOWER(station_name) LIKE ?', ['%urbiztondo%'])
                ->value('station_id');

        if (! $stationId) {
            throw new RuntimeException('The Urbiztondo fire station must be seeded before its barangay boundaries.');
        }

        $fixturePath = __DIR__.'/data/urbiztondo-boundaries.json';
        $fixture = file_get_contents($fixturePath);

        if ($fixture === false) {
            throw new RuntimeException("Unable to read boundary fixture: {$fixturePath}");
        }

        $boundaries = json_decode($fixture, true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        DB::transaction(function () use ($boundaries, $cityId, $stationId, $now): void {
            $legacyBarangay = DB::table('barangays')
                ->where('city_id', $cityId)
                ->where('barangay_name', 'Balangay Batangcaoa')
                ->first();

            $balangayExists = DB::table('barangays')
                ->where('city_id', $cityId)
                ->where('barangay_name', 'Balangay')
                ->exists();

            if ($legacyBarangay && ! $balangayExists) {
                DB::table('barangays')
                    ->where('barangay_id', $legacyBarangay->barangay_id)
                    ->update([
                        'barangay_name' => 'Balangay',
                        'updated_at' => $now,
                    ]);
            }

            foreach ($boundaries as $definition) {
                $barangay = DB::table('barangays')
                    ->where('city_id', $cityId)
                    ->where('barangay_name', $definition['name'])
                    ->first();

                if (! $barangay) {
                    $barangayId = DB::table('barangays')->insertGetId([
                        'barangay_name' => $definition['name'],
                        'city_id' => $cityId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], 'barangay_id');
                } else {
                    $barangayId = $barangay->barangay_id;
                }

                $boundaryValues = [
                    'name' => $definition['name'],
                    'geojson' => json_encode($definition['geojson'], JSON_THROW_ON_ERROR),
                    'station_id' => $stationId,
                    'barangay_id' => $barangayId,
                    'boundary_type' => 'barangay',
                    'source' => 'manual',
                    'external_place_id' => null,
                    'properties' => null,
                    'updated_at' => $now,
                ];

                $boundaryId = DB::table('boundary_location')
                    ->where('barangay_id', $barangayId)
                    ->where('boundary_type', 'barangay')
                    ->value('id');

                if ($boundaryId) {
                    DB::table('boundary_location')
                        ->where('id', $boundaryId)
                        ->update($boundaryValues);
                } else {
                    DB::table('boundary_location')->insert($boundaryValues + [
                        'created_at' => $now,
                    ]);
                }

                $coverageQuery = DB::table('station_barangay_coverage')
                    ->where('station_id', $stationId)
                    ->where('barangay_id', $barangayId);
                $coverageValues = [
                    'coverage_type' => 'primary',
                    'source' => 'urbiztondo_boundary_seeder',
                    'is_active' => true,
                    'updated_at' => $now,
                ];

                if ($coverageQuery->exists()) {
                    $coverageQuery->update($coverageValues);
                } else {
                    DB::table('station_barangay_coverage')->insert($coverageValues + [
                        'station_id' => $stationId,
                        'barangay_id' => $barangayId,
                        'created_at' => $now,
                    ]);
                }
            }
        });
    }
}
