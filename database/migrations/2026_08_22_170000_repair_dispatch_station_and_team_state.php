<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teams') || ! Schema::hasTable('station_officers')) {
            return;
        }

        $memberColumns = [
            'commander_driver',
            'pumper',
            'team_leader',
            'firefighter_officer_1',
            'firefighter_officer_2',
            'firefighter_officer_3',
            'rescue_radio',
        ];

        DB::table('teams')
            ->whereNull('station_id')
            ->orderBy('team_id')
            ->get(array_merge(['team_id'], $memberColumns))
            ->each(function ($team) use ($memberColumns) {
                $officerIds = collect($memberColumns)
                    ->map(fn (string $column) => $team->{$column})
                    ->filter()
                    ->unique()
                    ->values();

                if ($officerIds->isEmpty()) {
                    return;
                }

                $stationIds = DB::table('station_officers')
                    ->whereIn('station_officer_id', $officerIds)
                    ->pluck('station_id')
                    ->filter()
                    ->unique()
                    ->values();

                if ($stationIds->count() === 1) {
                    DB::table('teams')->where('team_id', $team->team_id)->update([
                        'station_id' => $stationIds->first(),
                        'updated_at' => now(),
                    ]);
                }
            });

        $releasedTeamIds = DB::table('teams')
            ->where('status', 'responding')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('incidents')
                    ->whereColumn('incidents.team_id', 'teams.team_id')
                    ->whereIn('incidents.status', ['Reported', 'Responding', 'On Scene']);
            })
            ->when(Schema::hasTable('incident_response_units'), function ($query) {
                $query->whereNotExists(function ($unitQuery) {
                    $unitQuery->selectRaw('1')
                        ->from('incident_response_units')
                        ->whereColumn('incident_response_units.team_id', 'teams.team_id')
                        ->whereIn('incident_response_units.status', ['reserved', 'en_route', 'on_scene']);
                });
            })
            ->pluck('team_id');

        if ($releasedTeamIds->isNotEmpty()) {
            DB::table('teams')->whereIn('team_id', $releasedTeamIds)->update([
                'status' => 'available',
                'updated_at' => now(),
            ]);

            $releasedOfficerIds = DB::table('teams')
                ->whereIn('team_id', $releasedTeamIds)
                ->get($memberColumns)
                ->flatMap(fn ($team) => collect($memberColumns)->map(fn (string $column) => $team->{$column}))
                ->filter()
                ->unique()
                ->values();

            DB::table('station_officers')
                ->whereIn('station_officer_id', $releasedOfficerIds)
                ->whereIn('status', ['dispatched', 'on-scene'])
                ->update(['status' => 'available', 'updated_at' => now()]);
        }

        if (Schema::hasTable('incidents') && Schema::hasTable('station_barangay_coverage')) {
            DB::table('incidents')
                ->whereNull('station_id')
                ->whereNotNull('barangay_id')
                ->orderBy('incident_id')
                ->get(['incident_id', 'barangay_id'])
                ->each(function ($incident) {
                    $stationId = DB::table('station_barangay_coverage')
                        ->where('barangay_id', $incident->barangay_id)
                        ->where('is_active', true)
                        ->orderByRaw("CASE coverage_type WHEN 'primary' THEN 1 WHEN 'support' THEN 2 ELSE 3 END")
                        ->orderBy('station_barangay_coverage_id')
                        ->value('station_id');

                    if ($stationId) {
                        DB::table('incidents')->where('incident_id', $incident->incident_id)->update([
                            'station_id' => $stationId,
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // This migration repairs ownership and availability; reverting those corrections is unsafe.
    }
};
