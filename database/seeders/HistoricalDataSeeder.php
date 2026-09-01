<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalDataSeeder extends Seeder
{
    public function run(): void
    {
        $stationId = DB::table('stations')
            ->where('station_code', 'ST-001')
            ->value('station_id');

        if (! $stationId) {
            $stationId = DB::table('stations')
                ->where('station_name', 'like', '%Urbiztondo%')
                ->value('station_id');
        }

        if (! $stationId) {
            $this->command?->warn('HistoricalDataSeeder skipped: Urbiztondo station not found.');
            return;
        }

        $urbiztondoCityId = DB::table('cities')
            ->where('city_name', 'Urbiztondo')
            ->value('city_id');

        if (! $urbiztondoCityId) {
            $this->command?->warn('HistoricalDataSeeder skipped: Urbiztondo city not found.');
            return;
        }

        $barangayIds = DB::table('barangays')
            ->where('city_id', $urbiztondoCityId)
            ->pluck('barangay_id', 'barangay_name');

        $now = now();
        $monthlyRows = $this->buildMonthlyRows((int) $stationId, $now);
        $incidentRows = $this->buildIncidentRows($barangayIds, $now);

        DB::transaction(function () use ($stationId, $monthlyRows, $incidentRows) {
            foreach ($monthlyRows as $row) {
                DB::table('historical_data')->updateOrInsert(
                    [
                        'station_id' => $stationId,
                        'month' => $row['month'],
                        'year' => $row['year'],
                    ],
                    collect($row)->except(['station_id', 'month', 'year'])->all()
                );
            }

            foreach ($incidentRows as $row) {
                if (! $row['barangay_id']) {
                    throw new \RuntimeException("Historical fire incident barangay could not be resolved for {$row['incident_date']}.");
                }

                $identity = [
                    'barangay_id' => $row['barangay_id'],
                    'incident_date' => $row['incident_date'],
                    'source_name' => $row['source_name'],
                ];

                $existing = DB::table('historical_fire_incidents')->where($identity)->exists();

                if (! $existing) {
                    DB::table('historical_fire_incidents')->insert($row);
                    continue;
                }

                // Weather enrichment is populated separately from the archive
                // preview. Never replace those saved values when restoring the
                // source incident facts from this seeder.
                $facts = collect($row)->except([
                    'barangay_id',
                    'incident_date',
                    'source_name',
                    'created_at',
                    'temperature',
                    'humidity',
                    'wind_speed',
                    'wind_direction',
                    'weather_condition',
                ])->all();

                DB::table('historical_fire_incidents')->where($identity)->update($facts);
            }
        });
    }

    private function buildMonthlyRows(int $stationId, Carbon $now): array
    {
        $temperatureByMonth = [
            'January' => 28.0,
            'February' => 29.0,
            'March' => 31.0,
            'April' => 32.0,
            'May' => 33.0,
            'June' => 31.0,
            'July' => 30.0,
            'August' => 30.0,
            'September' => 30.0,
            'October' => 29.0,
            'November' => 29.0,
            'December' => 28.0,
        ];

        $months = array_keys($temperatureByMonth);
        $rows = [];

        foreach (range(2023, 2026) as $year) {
            foreach ($months as $month) {
                $season = in_array($month, ['December', 'January', 'February', 'March', 'April', 'May'], true)
                    ? 'dry'
                    : 'wet';

                $rows["{$year}-{$month}"] = [
                    'station_id' => $stationId,
                    'month' => $month,
                    'year' => $year,
                    'incident_count' => 0,
                    'risk_level' => 'low',
                    'average_temperature' => $temperatureByMonth[$month],
                    'season' => $season,
                    'near_water' => false,
                    'density' => 'medium',
                    'materials' => 'mixed',
                    'peak_hours' => 'none recorded',
                    'accesibility' => 'moderate access',
                    'type_incident' => 'no recorded incident',
                    'occupancy' => 'mixed-use',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $overrides = [
            '2023-January' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 30.0,
                'near_water' => true,
                'density' => 'high',
                'materials' => 'light materials',
                'peak_hours' => '17:38',
                'accesibility' => 'easy access',
                'type_incident' => 'open flame from rubbish fire',
                'occupancy' => 'residential',
            ],
            '2023-March' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 35.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'substantial house',
                'peak_hours' => '12:30',
                'accesibility' => 'difficult access',
                'type_incident' => 'open flame from rubbish fire',
                'occupancy' => 'residential',
            ],
            '2023-July' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 32.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'semi concrete residential house',
                'peak_hours' => '15:30',
                'accesibility' => 'difficult access',
                'type_incident' => 'electrical ignition due to arcing',
                'occupancy' => 'residential',
            ],
            '2023-August' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 28.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'two storey residential house',
                'peak_hours' => '07:40',
                'accesibility' => 'difficult access',
                'type_incident' => 'open flame from cooking (lpg/gas stove)',
                'occupancy' => 'residential',
            ],
            '2024-January' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 28.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'shanty house',
                'peak_hours' => '19:24',
                'accesibility' => 'difficult access',
                'type_incident' => 'open flame from kerosene lamp',
                'occupancy' => 'residential',
            ],
            '2025-January' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 28.0,
                'near_water' => true,
                'density' => 'medium',
                'materials' => 'motorcycle',
                'peak_hours' => 'none recorded',
                'accesibility' => 'easy access',
                'type_incident' => 'vehicular fire',
                'occupancy' => 'mixed-use',
            ],
            '2025-February' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 29.0,
                'near_water' => true,
                'density' => 'medium',
                'materials' => 'school storage',
                'peak_hours' => 'none recorded',
                'accesibility' => 'easy access',
                'type_incident' => 'storage fire',
                'occupancy' => 'institutional',
            ],
            '2025-May' => [
                'incident_count' => 1,
                'risk_level' => 'high',
                'average_temperature' => 33.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'farm storage',
                'peak_hours' => '11:41',
                'accesibility' => 'moderate access',
                'type_incident' => 'electrical ignition overloading',
                'occupancy' => 'industrial',
            ],
            '2025-December' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 28.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'residential',
                'peak_hours' => 'none recorded',
                'accesibility' => 'difficult access',
                'type_incident' => 'residential fire',
                'occupancy' => 'residential',
            ],
            '2026-January' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 28.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'single and two family residential',
                'peak_hours' => '05:38',
                'accesibility' => 'difficult access',
                'type_incident' => 'thrown cigarette butt',
                'occupancy' => 'residential',
            ],
            '2026-February' => [
                'incident_count' => 1,
                'risk_level' => 'low',
                'average_temperature' => 29.0,
                'near_water' => false,
                'density' => 'low',
                'materials' => 'storage',
                'peak_hours' => '09:05',
                'accesibility' => 'moderate access',
                'type_incident' => 'unattended smoldering coconut leaves',
                'occupancy' => 'commercial',
            ],
            '2026-March' => [
                'incident_count' => 1,
                'risk_level' => 'medium',
                'average_temperature' => 31.0,
                'near_water' => false,
                'density' => 'medium',
                'materials' => 'single and two family residential',
                'peak_hours' => '14:28',
                'accesibility' => 'moderate access',
                'type_incident' => 'electrical ignition due to pinch wire',
                'occupancy' => 'residential',
            ],
        ];

        foreach ($overrides as $key => $override) {
            if (!isset($rows[$key])) {
                continue;
            }

            $rows[$key] = array_merge($rows[$key], $override, [
                'updated_at' => $now,
            ]);
        }

        return array_values($rows);
    }

    private function buildIncidentRows($barangayIds, Carbon $now): array
    {
        $sourceName = 'Urbiztondo historical fire dataset';
        $sourceReference = 'Seeder import from user-provided 2023-2026 records';

        $resolveBarangayId = function (string $name) use ($barangayIds): ?int {
            $map = [
                'Bayaoas' => 'Bayaoas',
                'Dalanguiring' => 'Dalanguiring',
                'Angatel' => 'Angatel',
                'Poblacion' => 'Poblacion',
                'Pasibi West' => 'Pasibi West',
                'Real' => 'Real',
                'Rial' => 'Real',
                'Gueteb' => 'Gueteb',
                'Pasibi East' => 'Pasibi East',
            ];

            $resolved = $map[$name] ?? $name;

            return $barangayIds[$resolved] ?? null;
        };

        return [
            [
                'barangay_id' => $resolveBarangayId('Poblacion'),
                'incident_id' => null,
                'incident_date' => '2023-01-01',
                'incident_time' => '17:38:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Open flame from rubbish fire',
                'incident_location' => 'Barangay Poblacion, near station and near hydrant',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => null,
                'temperature' => 30.0,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'High density lightweight material or shanty house. Month-level record with no exact day provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Bayaoas'),
                'incident_id' => null,
                'incident_date' => '2023-03-01',
                'incident_time' => '12:30:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Open flame from rubbish fire',
                'incident_location' => 'Sitio Bangul, Barangay Bayaoas',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => null,
                'temperature' => 35.0,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Low density substantial house, no near hydrant, far from station. Month-level record with no exact day provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Pasibi East'),
                'incident_id' => null,
                'incident_date' => '2023-07-01',
                'incident_time' => '15:30:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Electrical ignition caused by arcing',
                'incident_location' => 'Barangay Pasibi East',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => null,
                'temperature' => 32.0,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Low density two storey or semi concrete residential house, no near hydrants, far from fire station. Month-level record with no exact day provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Dalanguiring'),
                'incident_id' => null,
                'incident_date' => '2023-08-01',
                'incident_time' => '07:40:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Open flame from cooking (LPG / gas stove)',
                'incident_location' => 'Sitio Valdez, Barangay Dalanguiring',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => null,
                'temperature' => 28.0,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Low density two storey residential house, no near hydrants, far from station. Month-level record with no exact day provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Gueteb'),
                'incident_id' => null,
                'incident_date' => '2024-01-01',
                'incident_time' => '19:24:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Open flame from kerosene lamp',
                'incident_location' => 'Barangay Gueteb',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => null,
                'temperature' => 28.0,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Low density shanty house, no near hydrant, far from station. Month-level record with no exact day provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Poblacion'),
                'incident_id' => null,
                'incident_date' => '2025-01-01',
                'incident_time' => null,
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'undetermined',
                'fire_cause' => null,
                'incident_location' => 'Barangay Poblacion, front of UIS',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 10000.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Motorcycle fire. Month-level record with no exact day and time provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Poblacion'),
                'incident_id' => null,
                'incident_date' => '2025-02-01',
                'incident_time' => null,
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'undetermined',
                'fire_cause' => null,
                'incident_location' => 'Barangay Poblacion, Urbiztondo Integrated School storage',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 20000.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Month-level record with no exact day and time provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Pasibi West'),
                'incident_id' => null,
                'incident_date' => '2025-05-03',
                'incident_time' => '11:41:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Electrical ignition overloading',
                'incident_location' => 'Barangay Pasibi West, A66CT Farm storage',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 1400000.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Large storage fire.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Rial'),
                'incident_id' => null,
                'incident_date' => '2025-12-01',
                'incident_time' => null,
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'undetermined',
                'fire_cause' => null,
                'incident_location' => 'Barangay Real',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 7000.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Residential fire, no near fire hydrant, away from station. Month-level record with no exact day and time provided; first day of month used as placeholder.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Bayaoas'),
                'incident_id' => null,
                'incident_date' => '2026-01-01',
                'incident_time' => '05:38:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Thrown cigarette butt',
                'incident_location' => 'Barangay Bayaoas',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 10500.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Residential single and two family. No near hydrant and far away from station.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Dalanguiring'),
                'incident_id' => null,
                'incident_date' => '2026-02-26',
                'incident_time' => '09:05:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Accidental unattended smoldering coconut leaves',
                'incident_location' => 'Sitio Valdez, Barangay Dalanguiring',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 1500.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Involved storage.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barangay_id' => $resolveBarangayId('Angatel'),
                'incident_id' => null,
                'incident_date' => '2026-03-25',
                'incident_time' => '14:28:00',
                'source_name' => $sourceName,
                'source_type' => 'bfp_record',
                'source_reference' => $sourceReference,
                'fire_classification' => 'accidental',
                'fire_cause' => 'Electrical ignition due to pinch wire',
                'incident_location' => 'Barangay Angatel',
                'incident_count' => 1,
                'injured_count' => 0,
                'death_count' => 0,
                'estimated_damage' => 50000.00,
                'temperature' => null,
                'humidity' => null,
                'wind_speed' => null,
                'wind_direction' => null,
                'weather_condition' => null,
                'is_verified' => true,
                'notes' => 'Residential single and two family.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }
}
