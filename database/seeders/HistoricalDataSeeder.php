<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HistoricalDataSeeder extends Seeder
{
    public function run(): void
    {
        $stations = DB::table('stations')
            ->orderBy('station_id')
            ->pluck('station_id')
            ->all();

        if (empty($stations)) {
            $this->command?->warn('HistoricalDataSeeder skipped: no stations found.');
            return;
        }

        $years = [2023, 2024, 2025];

        $months = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];

        $rows = [];

        foreach ($stations as $stationId) {
            foreach ($years as $year) {
                foreach ($months as $index => $month) {

                    // Season logic (PH context)
                    $season = in_array($month, ['December','January','February','March','April','May'])
                        ? 'dry'
                        : 'wet';

                    // Temperature pattern
                    $temperature = $season === 'dry'
                        ? rand(30, 36) + (rand(0,99)/100)
                        : rand(25, 30) + (rand(0,99)/100);

                    // Incident pattern (higher in dry season)
                    $incidentCount = $season === 'dry'
                        ? rand(10, 25)
                        : rand(3, 12);

                    // Density random
                    $densityOptions = ['low','medium','high'];
                    $density = $densityOptions[array_rand($densityOptions)];

                    // Risk level logic
                    if ($incidentCount > 18 || $density === 'high') {
                        $riskLevel = 'high';
                    } elseif ($incidentCount > 8) {
                        $riskLevel = 'medium';
                    } else {
                        $riskLevel = 'low';
                    }

                    // Materials
                    $materialsOptions = [
                        'concrete',
                        'mixed',
                        'light materials'
                    ];
                    $materials = $materialsOptions[array_rand($materialsOptions)];

                    // Peak hours
                    $peakHoursOptions = [
                        'afternoon',
                        'evening',
                        'night',
                        'midday'
                    ];
                    $peakHours = $peakHoursOptions[array_rand($peakHoursOptions)];

                    // Accessibility
                    $accessOptions = [
                        'easy access',
                        'moderate access',
                        'difficult access'
                    ];
                    $accessibility = $accessOptions[array_rand($accessOptions)];

                    // Incident type
                    $incidentTypes = [
                        'electrical fire',
                        'cooking fire',
                        'open flame',
                        'short circuit',
                        'gas leak'
                    ];
                    $typeIncident = $incidentTypes[array_rand($incidentTypes)];

                    // Occupancy
                    $occupancies = [
                        'residential',
                        'commercial',
                        'industrial'
                    ];
                    $occupancy = $occupancies[array_rand($occupancies)];

                    $rows[] = [
                        'station_id' => $stationId,
                        'month' => $month,
                        'year' => $year,
                        'incident_count' => $incidentCount,
                        'risk_level' => $riskLevel,
                        'average_temperature' => $temperature,
                        'season' => $season,
                        'near_water' => rand(0,1),
                        'density' => $density,
                        'materials' => $materials,
                        'peak_hours' => $peakHours,
                        'accesibility' => $accessibility,
                        'type_incident' => $typeIncident,
                        'occupancy' => $occupancy,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }
            }
        }

        DB::table('historical_data')->insert($rows);
    }
}
