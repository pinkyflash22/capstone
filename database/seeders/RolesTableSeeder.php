<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            'admin',
            'provincial admin',
            'chief',
            'stationadmin',
            'citizen',
            'anonymous',
            'firefighter',
            'barangay',
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role' => $role],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
