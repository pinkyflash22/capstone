<?php

namespace App\Services;

use App\Models\DutyException;
use App\Models\DutyShift;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DutyRosterService
{
    public const DEPLOYABLE_ROLES = [
        'team_leader', 'commander_driver', 'pumper',
        'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio',
    ];

    public const ALL_ROLES = [...self::DEPLOYABLE_ROLES, 'station_duty'];

    public function sync(?int $stationId = null, ?Carbon $at = null): array
    {
        $at ??= now();
        $teams = Team::query()
            ->where('roster_managed', true)
            ->when($stationId, function ($query) use ($stationId) {
                $officerIds = StationOfficer::query()
                    ->where('station_id', $stationId)
                    ->pluck('station_officer_id')
                    ->all();
                $query->where(function ($stationQuery) use ($stationId, $officerIds) {
                    $stationQuery->where('station_id', $stationId);
                    foreach (self::DEPLOYABLE_ROLES as $role) {
                        $stationQuery->orWhereIn($role, $officerIds);
                    }
                });
            })
            ->get();
        $summary = ['checked' => $teams->count(), 'activated' => 0, 'off_duty' => 0, 'deferred' => 0];

        foreach ($teams as $team) {
            $result = $this->syncTeam($team, $at);
            $summary[$result]++;
        }

        $activeExceptions = DutyException::query()
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->get(['station_officer_id', 'type']);
        foreach ($activeExceptions->groupBy('type') as $type => $exceptions) {
            StationOfficer::query()
                ->whereIn('station_officer_id', $exceptions->pluck('station_officer_id'))
                ->whereNotIn('status', ['dispatched', 'on-scene'])
                ->update(['status' => $type === 'leave' ? 'on-leave' : 'off-duty', 'updated_at' => $at]);
        }

        $activeLeaveIds = $activeExceptions->where('type', 'leave')->pluck('station_officer_id');
        $expiredLeaveIds = DutyException::query()
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->where('type', 'leave')
            ->where('ends_at', '<=', $at)
            ->pluck('station_officer_id')
            ->diff($activeLeaveIds)
            ->unique();
        if ($expiredLeaveIds->isNotEmpty()) {
            StationOfficer::query()
                ->whereIn('station_officer_id', $expiredLeaveIds)
                ->where('status', 'on-leave')
                ->update(['status' => 'off-duty', 'updated_at' => $at]);
        }

        return $summary;
    }

    public function syncTeam(Team $team, ?Carbon $at = null): string
    {
        $at ??= now();
        $shift = DutyShift::query()
            ->with(['assignments.officer.user.role'])
            ->where('team_id', $team->team_id)
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->latest('starts_at')
            ->first();

        if ($team->status === 'responding') {
            $team->forceFill([
                'pending_duty_shift_id' => $shift?->duty_shift_id,
                'roster_synced_at' => $at,
            ])->save();
            return 'deferred';
        }

        if (! $shift) {
            $this->applyRoster($team, null, [], $at);
            return 'off_duty';
        }

        $officerIds = $shift->assignments->pluck('station_officer_id')->all();
        $unavailableIds = DutyException::query()
            ->whereIn('station_officer_id', $officerIds)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->pluck('station_officer_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $assignments = $shift->assignments
            ->reject(fn ($assignment) => in_array((int) $assignment->station_officer_id, $unavailableIds, true));
        $hasCommandCoverage = $assignments->contains(fn ($assignment) => $assignment->team_role === 'station_duty');

        if (! $hasCommandCoverage || $assignments->whereIn('team_role', self::DEPLOYABLE_ROLES)->count() < 2) {
            $this->applyRoster($team, $shift, [], $at);
            return 'off_duty';
        }

        $roster = $assignments
            ->whereIn('team_role', self::DEPLOYABLE_ROLES)
            ->mapWithKeys(fn ($assignment) => [$assignment->team_role => (int) $assignment->station_officer_id])
            ->all();
        $this->applyRoster($team, $shift, $roster, $at);

        StationOfficer::query()
            ->whereIn('station_officer_id', $assignments->pluck('station_officer_id')->all())
            ->whereNotIn('status', ['dispatched', 'on-scene'])
            ->update(['status' => 'available', 'updated_at' => $at]);

        return 'activated';
    }

    private function applyRoster(Team $team, ?DutyShift $shift, array $roster, Carbon $at): void
    {
        DB::transaction(function () use ($team, $shift, $roster, $at) {
            $locked = Team::query()->whereKey($team->team_id)->lockForUpdate()->firstOrFail();
            $previousIds = collect(self::DEPLOYABLE_ROLES)->map(fn ($role) => $locked->{$role})->filter()->unique();
            $payload = collect(self::DEPLOYABLE_ROLES)->mapWithKeys(fn ($role) => [$role => $roster[$role] ?? null])->all();
            $payload += [
                'status' => empty($roster) ? 'off' : 'available',
                'active_duty_shift_id' => $shift?->duty_shift_id,
                'pending_duty_shift_id' => null,
                'roster_synced_at' => $at,
            ];
            $locked->forceFill($payload)->save();

            $newIds = collect($roster)->filter()->unique();
            $releasedIds = $previousIds->diff($newIds);
            // Handoffs between runs must not mark personnel off-duty after
            // another team's current shift has already activated them.
            $otherActiveIds = \App\Models\DutyShiftAssignment::query()
                ->whereIn('station_officer_id', $releasedIds->all())
                ->whereHas('shift', fn ($query) => $query->where('team_id', '!=', $locked->team_id)
                    ->where('status', 'scheduled')->where('starts_at', '<=', $at)->where('ends_at', '>', $at))
                ->pluck('station_officer_id');
            $releasedIds = $releasedIds->diff($otherActiveIds);
            if ($releasedIds->isNotEmpty()) {
                StationOfficer::query()
                    ->whereIn('station_officer_id', $releasedIds->all())
                    ->whereNotIn('status', ['dispatched', 'on-scene', 'on-leave'])
                    ->update(['status' => 'off-duty', 'updated_at' => $at]);
            }
        });
    }
}
