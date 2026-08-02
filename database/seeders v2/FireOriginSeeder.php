<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FireOriginSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('fire_origin')->insert([
            ['origin' => 'Kitchen', 'created_at' => $now, 'updated_at' => $now],
            ['origin' => 'Electrical', 'created_at' => $now, 'updated_at' => $now],
            ['origin' => 'Bedroom', 'created_at' => $now, 'updated_at' => $now],
            ['origin' => 'Garage', 'created_at' => $now, 'updated_at' => $now],
            ['origin' => 'Unknown', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}