<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $stationId = $this->resolveSeedStationId();

        if (! $stationId) {
            return;
        }

        $teamLeaderId = $this->resolveStationOfficerId('FIR-0001', $stationId);
        $firefighterOfficer1Id = $this->resolveStationOfficerId('FIR-0002', $stationId);
        $firefighterOfficer2Id = $this->resolveStationOfficerId('FIR-0003', $stationId);

        if (! $teamLeaderId || ! $firefighterOfficer1Id || ! $firefighterOfficer2Id) {
            return;
        }

        DB::table('teams')->updateOrInsert(
            [
                'team_leader' => $teamLeaderId,
                'firefighter_officer_1' => $firefighterOfficer1Id,
                'firefighter_officer_2' => $firefighterOfficer2Id,
            ],
            [
                'commander_driver' => null,
                'pumper' => null,
                'firefighter_officer_3' => null,
                'rescue_radio' => null,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
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

    private function resolveStationOfficerId(string $accountCode, int $stationId): ?int
    {
        $userId = DB::table('users')
            ->where('account_code', $accountCode)
            ->value('user_id');

        if (! $userId) {
            return null;
        }

        $stationOfficerId = DB::table('station_officers')
            ->where('station_id', $stationId)
            ->where('user_id', $userId)
            ->value('station_officer_id');

        return $stationOfficerId ? (int) $stationOfficerId : null;
    }
}
