<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UrbiztondoBarangayHallSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $urbiztondoCityId = DB::table('cities')
            ->where('city_name', 'Urbiztondo')
            ->value('city_id');

        if (! $urbiztondoCityId) {
            return;
        }

        $barangayCoordinates = [
            'Angatel' => ['lat' => 15.8117067, 'lng' => 120.3413576],
            'Balangay' => ['lat' => 15.8534484, 'lng' => 120.3634506],
            'Baug' => ['lat' => 15.8177662, 'lng' => 120.3694322],
            'Bayaoas' => ['lat' => 15.8329772, 'lng' => 120.3536367],
            'Bituag' => ['lat' => 15.8405726, 'lng' => 120.3829104],
            'Camambugan' => ['lat' => 15.8189494, 'lng' => 120.3545675],
            'Dalanguiring' => ['lat' => 15.8383857, 'lng' => 120.3312886],
            'Duplac' => ['lat' => 15.8435619, 'lng' => 120.3679571],
            'Galarin' => ['lat' => 15.7953991, 'lng' => 120.3589099],
            'Gueteb' => ['lat' => 15.8455888, 'lng' => 120.3537922],
            'Malaca' => ['lat' => 15.8386760, 'lng' => 120.3728836],
            'Malayo' => ['lat' => 15.8275736, 'lng' => 120.3425646],
            'Malibong' => ['lat' => 15.8372813, 'lng' => 120.3160402],
            'Pasibi East' => ['lat' => 15.8573403, 'lng' => 120.3797199],
            'Pasibi West' => ['lat' => 15.8511054, 'lng' => 120.3677424],
            'Pisuac' => ['lat' => 15.8040586, 'lng' => 120.3559971],
            'Poblacion' => ['lat' => 15.8230293, 'lng' => 120.3280002],
            'Real' => ['lat' => 15.8066858, 'lng' => 120.3472745],
            'Salavante' => ['lat' => 15.7962050, 'lng' => 120.3495713],
            'Sawat' => ['lat' => 15.7968115, 'lng' => 120.3381135],
        ];

        foreach ($barangayCoordinates as $barangayName => $coordinates) {
            $barangay = DB::table('barangays')
                ->where('city_id', $urbiztondoCityId)
                ->where('barangay_name', $barangayName)
                ->first();

            if (! $barangay) {
                continue;
            }

            $markerTitle = $barangayName . ' Barangay Hall';

            DB::table('markers')->updateOrInsert(
                ['title' => $markerTitle],
                [
                    'description' => 'Barangay hall marker for ' . $barangayName . ', Urbiztondo, Pangasinan.',
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng'],
                    'status' => 'active',
                    'category' => 'barangay',
                    'image' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $markerId = DB::table('markers')
                ->where('title', $markerTitle)
                ->value('marker_id');

            DB::table('barangay_halls')->updateOrInsert(
                ['barangay_id' => $barangay->barangay_id],
                [
                    'marker_id' => $markerId,
                    'hall_name' => $markerTitle,
                    'address_id' => null,
                    'contact_number' => null,
                    'email' => null,
                    'captain_user_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
