<?php
// database/seeders/BFPDataSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BuildingInspection;
use App\Models\ResourceAllocation;
use App\Models\WeatherData;
use Carbon\Carbon;

class BFPDataSeeder extends Seeder
{
    public function run()
    {
        // Sample Building Inspections
        $buildings = [
            [
                'building_name' => 'Commercial Plaza',
                'building_type' => 'commercial',
                'sector' => 'Sector 4',
                'floors' => 5,
                'occupancy' => 450,
                'inspection_date' => Carbon::now()->subDays(15),
                'compliance_score' => 72,
                'fire_extinguishers_count' => 25,
                'sprinkler_system' => true,
                'fire_alarms' => true,
                'emergency_lights' => true,
                'exits_count' => 4,
                'violations' => json_encode(['Blocked fire exit', 'Expired extinguishers']),
                'status' => 'violations',
                'risk_level' => 'Medium'
            ],
            [
                'building_name' => 'Residential Tower',
                'building_type' => 'residential',
                'sector' => 'Sector 3',
                'floors' => 12,
                'occupancy' => 300,
                'inspection_date' => Carbon::now()->subDays(30),
                'compliance_score' => 85,
                'fire_extinguishers_count' => 30,
                'sprinkler_system' => true,
                'fire_alarms' => true,
                'emergency_lights' => true,
                'exits_count' => 6,
                'violations' => null,
                'status' => 'compliant',
                'risk_level' => 'Low'
            ]
        ];

        foreach ($buildings as $building) {
            BuildingInspection::create($building);
        }

        // Sample Resource Allocations
        $resources = [
            [
                'station_id' => 'ST001',
                'station_name' => 'Central Station',
                'sector' => 'Sector 1',
                'trucks_allocated' => 4,
                'personnel_allocated' => 25,
                'equipment_allocated' => json_encode(['Hoses', 'Ladders', 'Extinguishers']),
                'vehicle_types' => json_encode(['Engine', 'Ladder', 'Rescue']),
                'status' => 'active',
                'availability' => 'available',
                'effectiveness_score' => 92,
                'response_time_avg' => 3.5,
                'incidents_handled' => 45
            ],
            [
                'station_id' => 'ST002',
                'station_name' => 'North Station',
                'sector' => 'Sector 4',
                'trucks_allocated' => 3,
                'personnel_allocated' => 18,
                'equipment_allocated' => json_encode(['Hoses', 'Ladders']),
                'vehicle_types' => json_encode(['Engine', 'Rescue']),
                'status' => 'active',
                'availability' => 'deployed',
                'effectiveness_score' => 78,
                'response_time_avg' => 4.8,
                'incidents_handled' => 32
            ]
        ];

        foreach ($resources as $resource) {
            ResourceAllocation::create($resource);
        }

        // Sample Weather Data
        for ($i = 0; $i < 30; $i++) {
            WeatherData::create([
                'date' => Carbon::now()->subDays($i),
                'sector' => 'Sector 4',
                'temperature' => rand(28, 36),
                'humidity' => rand(45, 75),
                'wind_speed' => rand(5, 25),
                'rainfall' => rand(0, 10),
                'fire_danger_index' => rand(40, 85)
            ]);
        }
    }
}