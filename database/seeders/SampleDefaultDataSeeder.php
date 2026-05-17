<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $urbiztondoCenterLat = 15.8222000;
        $urbiztondoCenterLng = 120.3297000;

        $markers = [
            [
                'title' => 'Station 1 Marker',
                'description' => 'Sample fire station marker in Urbiztondo, Pangasinan',
                'lat' => $urbiztondoCenterLat,
                'lng' => $urbiztondoCenterLng,
                'status' => 'active',
                'category' => 'station',
                'image' => null,
            ],
            [
                'title' => 'Hydrant Alpha Marker',
                'description' => 'Sample hydrant marker near the Urbiztondo station',
                'lat' => 15.8231000,
                'lng' => 120.3305000,
                'status' => 'active',
                'category' => 'hydrant',
                'image' => null,
            ],
            [
                'title' => 'Sample Fire Incident Marker',
                'description' => 'Sample incident marker around Urbiztondo, Pangasinan',
                'lat' => 15.8209000,
                'lng' => 120.3312000,
                'status' => 'active',
                'category' => 'fire',
                'image' => null,
            ],
        ];

        foreach ($markers as $marker) {
            DB::table('markers')->updateOrInsert(
                ['title' => $marker['title']],
                $marker + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $stationMarkerId = DB::table('markers')->where('title', 'Station 1 Marker')->value('marker_id');
        $hydrantMarkerId = DB::table('markers')->where('title', 'Hydrant Alpha Marker')->value('marker_id');

        if ($stationMarkerId) {
            DB::table('stations')->updateOrInsert(
                ['station_code' => 'ST-001'],
                [
                'station_name' => 'Urbiztondo Fire Station',
                'station_code' => 'ST-001',
                'contact_number' => '09171234567',
                'email' => 'urbiztondo.station@example.com',
                'marker_id' => $stationMarkerId,
                'address_id' => null,
                ]
            );
        }

        $stationId = DB::table('stations')->where('station_code', 'ST-001')->value('station_id');

        if ($stationId && $hydrantMarkerId) {
            DB::table('hydrants')->updateOrInsert(
                ['hydrant_code' => 'HYD-001'],
                [
                'hydrant_code' => 'HYD-001',
                'station_id' => $stationId,
                'marker_id' => $hydrantMarkerId,
                'type' => 'dry barrel',
                'created_at' => $now,
                'updated_at' => $now,
                ]
            );
        }

        if (!DB::table('equipments')->where('equipment_name', 'Bunker Suit')->exists()) {
            DB::table('equipments')->insert([
                [
                    'equipment_name' => 'Bunker Suit',
                    'category' => 'Personal Protective Equipment',
                    'type' => 'repairable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_name' => 'Fire Hose',
                    'category' => 'firefighting equipment',
                    'type' => 'repairable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_name' => 'Portable Radio',
                    'category' => 'communication equipment',
                    'type' => 'repairable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_name' => 'Medical Gloves',
                    'category' => 'rescue equipment',
                    'type' => 'disposable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_name' => 'Fire Truck',
                    'category' => 'vehicles',
                    'type' => 'repairable',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (!DB::table('analysis')->where('sector', 'Central District')->exists()) {
            DB::table('analysis')->insert([
                'sector' => 'Central District',
                'station_id' => $stationId,
                'period' => 'Q1 2026',
                'risk_level' => 'medium',
                'confidence_data' => '0.82',
                'analysis_data' => json_encode([
                    'summary' => 'Sample analysis record',
                    'trend' => 'stable',
                ]),
                'recommendations' => json_encode([
                    'Inspect hydrants monthly',
                    'Increase station readiness checks',
                ]),
                'incident_count' => 3,
                'probability' => '0.41',
                'predictions' => json_encode([
                    'next_month_risk' => 'medium',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
