<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PangasinanSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // 1. Province
        $provinceId = DB::table('provinces')->insertGetId([
            'province_name' => 'Pangasinan',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Cities (sample)
        $cities =[
    ['city_name' => 'Alaminos'],
    ['city_name' => 'Agno'],
    ['city_name' => 'Aguilar'],
    ['city_name' => 'Alcala'],
    ['city_name' => 'Anda'],
    ['city_name' => 'Asingan'],
    ['city_name' => 'Balungao'],
    ['city_name' => 'Bani'],
    ['city_name' => 'Basista'],
    ['city_name' => 'Bautista'],
    ['city_name' => 'Bayambang'],
    ['city_name' => 'Binalonan'],
    ['city_name' => 'Binmaley'],
    ['city_name' => 'Bolinao'],
    ['city_name' => 'Bugallon'],
    ['city_name' => 'Burgos'],
    ['city_name' => 'Calasiao'],
    ['city_name' => 'Dasol'],
    ['city_name' => 'Dagupan'],
    ['city_name' => 'Infanta'],
    ['city_name' => 'Labrador'],
    ['city_name' => 'Laoac'],
    ['city_name' => 'Lingayen'],
    ['city_name' => 'Mabini'],
    ['city_name' => 'Malasiqui'],
    ['city_name' => 'Manaoag'],
    ['city_name' => 'Mangaldan'],
    ['city_name' => 'Mangatarem'],
    ['city_name' => 'Mapandan'],
    ['city_name' => 'Natividad'],
    ['city_name' => 'Pozorrubio'],
    ['city_name' => 'Rosales'],
    ['city_name' => 'San Carlos'],
    ['city_name' => 'San Fabian'],
    ['city_name' => 'San Jacinto'],
    ['city_name' => 'San Manuel'],
    ['city_name' => 'San Nicolas'],
    ['city_name' => 'San Quintin'],
    ['city_name' => 'Santa Barbara'],
    ['city_name' => 'Santa Maria'],
    ['city_name' => 'Santo Tomas'],
    ['city_name' => 'Sison'],
    ['city_name' => 'Sual'],
    ['city_name' => 'Tayug'],
    ['city_name' => 'Umingan'],
    ['city_name' => 'Urbiztondo'],
    ['city_name' => 'Urdaneta'],
    ['city_name' => 'Villasis'],
];


        foreach ($cities as $city) {
            $cityId = DB::table('cities')->insertGetId([
                'city_name' => $city['city_name'],
                'province_id' => $provinceId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $barangays = $city['city_name'] === 'Urbiztondo'
                ? [
                    'Angatel',
                    'Balangay Batangcaoa',
                    'Baug',
                    'Bayaoas',
                    'Bituag',
                    'Camambugan',
                    'Dalanguiring',
                    'Duplac',
                    'Galarin',
                    'Gueteb',
                    'Malaca',
                    'Malayo',
                    'Malibong',
                    'Pasibi East',
                    'Pasibi West',
                    'Pisuac',
                    'Poblacion',
                    'Real',
                    'Salavante',
                    'Sawat',
                ]
                : [
                    'Poblacion',
                    'Lucap',
                    'San Jose',
                ];

            DB::table('barangays')->insert(
                array_map(fn ($barangayName) => [
                    'barangay_name' => $barangayName,
                    'city_id' => $cityId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $barangays)
            );

           
        }
    }
}
