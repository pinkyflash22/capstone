<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationOfficersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $stationId = $this->resolveSeedStationId();

        if (! $stationId) {
            return;
        }

        $officers = [
            [
                'account_code' => 'CHF-0001',
                'rank' => 'Fire Chief Inspector',
                'status' => 'available',
            ],
            [
                'account_code' => 'FIR-0001',
                'rank' => 'Fire Officer 1',
                'status' => 'available',
            ],
            [
                'account_code' => 'FIR-0002',
                'rank' => 'Fire Officer 1',
                'status' => 'available',
            ],
            [
                'account_code' => 'FIR-0003',
                'rank' => 'Fire Officer 1',
                'status' => 'available',
            ],
        ];

        foreach ($officers as $officer) {
            $userId = DB::table('users')->where('account_code', $officer['account_code'])->value('user_id');

            if (! $userId) {
                continue;
            }

            DB::table('station_officers')->updateOrInsert(
                [
                    'station_id' => $stationId,
                    'user_id' => $userId,
                ],
                [
                    'rank' => $officer['rank'],
                    'status' => $officer['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function resolveSeedStationId(): ?int
    {
        $preferredStationId = DB::table('stations')
            ->where('station_code', 'ST-001')
            ->value('station_id');

        if ($preferredStationId) {
            return (int) $preferredStationId;
        }

        $fallbackStationId = DB::table('stations')
            ->orderBy('station_id')
            ->value('station_id');

        return $fallbackStationId ? (int) $fallbackStationId : null;
    }
}
