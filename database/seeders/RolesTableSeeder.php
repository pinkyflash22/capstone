<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $legacyStationAdmin = DB::table('roles')->where('role', 'stationadmin')->first();
        $stationAdmin = DB::table('roles')->where('role', 'stationadmin')->first();

        if ($legacyStationAdmin && $stationAdmin) {
            DB::table('users')
                ->where('role_id', $legacyStationAdmin->role_id)
                ->update(['role_id' => $stationAdmin->role_id]);

            DB::table('roles')
                ->where('role_id', $legacyStationAdmin->role_id)
                ->delete();
        } elseif ($legacyStationAdmin) {
            DB::table('roles')
                ->where('role_id', $legacyStationAdmin->role_id)
                ->update(['role' => 'stationadmin', 'updated_at' => now()]);
        }

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
