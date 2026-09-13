<?php

namespace App\Services;

use App\Models\DutyException;
use App\Models\DutyShift;
use App\Models\IncidentResponseUnit;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DutyRosterService
{
    public const DEPLOYABLE_ROLES = [
        'team_leader', 'commander_driver', 'pumper',
        'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio',
    ];

    public const ALL_ROLES = [...self::DEPLOYABLE_ROLES, 'station_duty'];

    public function sync(?int $stationId = null, ?Carbon $at = null): array
    {
        return DB::transaction(function () use ($stationId, $at) {
            // Serialize scheduling and balancing together; dispatch must never see
            // a half-completed handoff or the same firefighter on both runs.
            Team::query()->when($stationId, fn ($query) => $query->where('station_id', $stationId))
                ->orderBy('team_id')->lockForUpdate()->get();
            return $this->syncRoster($stationId, $at);
        });
    }

    private function syncRoster(?int $stationId = null, ?Carbon $at = null): array
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
            $result = $this->syncScheduledTeam($team, $at);
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

        $stationIds = Team::query()->whereNotNull('station_id')
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->distinct()->orderBy('station_id')->pluck('station_id');
        foreach ($stationIds as $id) $this->balanceStation((int) $id, $at);

        return $summary;
    }

    public function syncTeam(Team $team, ?Carbon $at = null): string
    {
        $this->sync((int) $team->station_id, $at);
        $team->refresh();
        return $this->hasActiveResponse($team) ? 'deferred' : ($team->status === 'available' ? 'activated' : 'off_duty');
    }

    private function syncScheduledTeam(Team $team, ?Carbon $at = null): string
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

        if ($this->hasActiveResponse($team)) {
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

        $roster = $assignments
            ->whereIn('team_role', self::DEPLOYABLE_ROLES)
            ->mapWithKeys(fn ($assignment) => [$assignment->team_role => (int) $assignment->station_officer_id])
            ->all();
        $startingShift = (int) $team->active_duty_shift_id !== (int) $shift->duty_shift_id;
        $this->applyRoster($team, $shift, $roster, $at);

        StationOfficer::query()
            ->whereIn('station_officer_id', $assignments->pluck('station_officer_id')->all())
            ->whereIn('status', $startingShift ? ['available', 'off-duty'] : ['available'])
            ->update(['status' => 'available', 'updated_at' => $at]);

        return $hasCommandCoverage && count($roster) >= 2 ? 'activated' : 'off_duty';
    }

    private function hasActiveResponse(Team $team): bool
    {
        return $team->status === 'responding'
            || $team->incidents()->whereIn('status', ['Reported', 'Responding', 'On Scene'])->exists()
            || IncidentResponseUnit::query()->where('team_id', $team->team_id)
                ->whereIn('status', ['reserved', 'en_route', 'on_scene'])->exists();
    }

    private function balanceStation(int $stationId, Carbon $at): void
    {
        DB::transaction(function () use ($stationId, $at) {
            $teams = Team::query()->where('station_id', $stationId)->orderBy('team_id')->lockForUpdate()->get();
            $busy = $teams->filter(fn ($team) => $this->hasActiveResponse($team))
                ->merge($teams->skip(2))->unique('team_id');
            $reserved = $busy->flatMap(fn ($team) => collect(self::DEPLOYABLE_ROLES)->map(fn ($role) => $team->{$role}))->filter();
            $exceptions = DutyException::query()->where('station_id', $stationId)
                ->where('starts_at', '<=', $at)->where('ends_at', '>', $at)->pluck('station_officer_id');
            $shifts = DutyShift::query()->with('assignments')->where('station_id', $stationId)
                ->where('status', 'scheduled')->where('starts_at', '<=', $at)->where('ends_at', '>', $at)->get();
            $assignments = $shifts->flatMap->assignments;
            $commandIds = $assignments->where('team_role', 'station_duty')->pluck('station_officer_id');
            $recovering = DutyShift::query()->with('assignments')->where('station_id', $stationId)
                ->where('status', 'scheduled')->where('ends_at', '<=', $at)->where('ends_at', '>', $at->copy()->subDay())->get()
                ->filter(fn ($shift) => DutyAvailabilityService::recoveryEnd($shift)->gt($at))
                ->flatMap->assignments->where('team_role', '!=', 'station_duty')->pluck('station_officer_id');
            $pool = StationOfficer::query()->where('station_id', $stationId)->where('status', 'available')
                ->whereHas('user.role', fn ($query) => $query->where('role', 'like', '%firefighter%'))
                ->whereNotIn('station_officer_id', $reserved->merge($exceptions)->merge($commandIds)->merge($recovering)->unique())
                ->orderBy('station_officer_id')->lockForUpdate()->pluck('station_officer_id')->all();
            $hasCommand = StationOfficer::query()->whereIn('station_officer_id', $commandIds)
                ->whereNotIn('station_officer_id', $exceptions)->where('status', 'available')->exists();
            $free = $teams->take(2)->reject(fn ($team) => $busy->contains('team_id', $team->team_id))->values();
            $count = count($pool);
            $firstSize = $free->count() === 2 && $count >= 5 ? min(7, (int) ceil($count * 3 / 5)) : min(7, $count);
            $knownRoles = [];
            foreach ($teams as $existingTeam) {
                foreach (self::DEPLOYABLE_ROLES as $role) {
                    if ($existingTeam->{$role}) $knownRoles[$existingTeam->{$role}] = $role;
                }
            }
            foreach ($teams as $team) {
                if ($busy->contains('team_id', $team->team_id)) continue;
                $position = $free->search(fn ($run) => $run->team_id === $team->team_id);
                $size = $position === 0 ? $firstSize : ($position === 1 && $count >= 5 ? min(7, count($pool)) : 0);
                $current = collect(self::DEPLOYABLE_ROLES)->map(fn ($role) => $team->{$role})->filter()->all();
                $ordered = array_values(array_unique([...array_intersect($current, $pool), ...$pool]));
                $selected = array_slice($ordered, 0, $size);
                $roster = array_fill_keys(self::DEPLOYABLE_ROLES, null);
                foreach (self::DEPLOYABLE_ROLES as $role) {
                    if ($team->{$role} && in_array($team->{$role}, $selected)) {
                        $roster[$role] = (int) $team->{$role};
                        $selected = array_values(array_diff($selected, [$team->{$role}]));
                    }
                }
                foreach ($selected as $id) {
                    $preferred = $assignments->firstWhere('station_officer_id', $id)?->team_role ?? $knownRoles[$id] ?? null;
                    $role = $preferred && array_key_exists($preferred, $roster) && ! $roster[$preferred]
                        ? $preferred : collect(['firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio'])->first(fn ($role) => ! $roster[$role]);
                    if ($role) $roster[$role] = (int) $id;
                }
                // Never invent specialist qualifications to fill a driver/pumper/leader slot.
                $pool = array_values(array_diff($pool, array_filter($roster)));
                $available = count(array_filter($roster)) >= 2 && (! $team->roster_managed || $hasCommand);
                $team->forceFill($roster + ['status' => $available ? 'available' : 'off', 'roster_synced_at' => $at])->save();
            }
            foreach (['v3', 'v4'] as $version) {
                foreach (['teams', 'officers'] as $section) {
                    Cache::forget("resource-management:station:{$stationId}:{$version}:{$section}");
                    Cache::forget("resource-management:provincial:{$version}:{$section}");
                }
            }
        });
    }

    private function applyRoster(Team $team, ?DutyShift $shift, array $roster, Carbon $at): void
    {
        DB::transaction(function () use ($team, $shift, $roster, $at) {
            $locked = Team::query()->whereKey($team->team_id)->lockForUpdate()->firstOrFail();
            // End duty for the previous scheduled crew, not available substitutes
            // that the station balancer temporarily placed on this run.
            $previousIds = \App\Models\DutyShiftAssignment::query()
                ->where('duty_shift_id', $locked->active_duty_shift_id)->pluck('station_officer_id');
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
