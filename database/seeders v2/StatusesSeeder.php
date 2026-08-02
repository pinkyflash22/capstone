<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatusesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        DB::table('statuses')->insert([
            ['status_name' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['status_name' => 'Inactive', 'created_at' => $now, 'updated_at' => $now],
            ['status_name' => 'Pending', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}