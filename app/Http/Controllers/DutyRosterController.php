<?php

namespace App\Http\Controllers;

use App\Models\DutyException;
use App\Models\DutyRosterTemplate;
use App\Models\DutyShift;
use App\Models\DutyShiftAssignment;
use App\Models\StationOfficer;
use App\Models\Station;
use App\Models\Team;
use App\Services\DutyRosterService;
use App\Services\DutyAvailabilityService;
use App\Services\DutyRosterRecurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DutyRosterController extends Controller
{
    public function index(Request $request, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        $from = Carbon::parse($request->input('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->addDays(14)->toDateString()))->endOfDay();
        abort_if($to->diffInDays($from) > 62, 422, 'Roster windows are limited to 62 days.');
        $recurrence->generate($stationId, $from, $to);
        $service->sync($stationId);

        $shifts = DutyShift::query()
            ->with(['assignments.officer.user.role', 'team', 'template'])
            ->where('station_id', $stationId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->orderBy('starts_at')
            ->get();
        $exceptions = DutyException::query()
            ->with('officer.user.role')
            ->where('station_id', $stationId)
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->orderBy('starts_at')
            ->get();
        $officers = StationOfficer::query()
            ->with('user.role')
            ->where('station_id', $stationId)
            ->orderBy('station_officer_id')
            ->get();

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
            'station_id' => $stationId,
            'teams' => $this->teamsForStation($stationId)
                ->orderBy('team_id')
                ->get()
                ->map(fn (Team $team) => $this->serializeRosterTeam($team, $stationId))
                ->values(),
            'officers' => $officers->map(fn ($officer) => $this->serializeOfficer($officer))->values(),
            'shifts' => $shifts->map(fn ($shift) => $this->serializeShift($shift))->values(),
            'exceptions' => $exceptions->map(fn ($exception) => $this->serializeException($exception))->values(),
            'availability_shifts' => DutyShift::query()->with('assignments')
                ->where('station_id', $stationId)->where('status', 'scheduled')
                ->where('starts_at', '<', $to->copy()->addDay())
                ->where('ends_at', '>', $from->copy()->subDay())->get()
                ->map(fn ($shift) => [
                    'id' => $shift->duty_shift_id,
                    'starts_at' => $shift->starts_at->toIso8601String(),
                    'ends_at' => $shift->ends_at->toIso8601String(),
                    'recovery_ends_at' => DutyAvailabilityService::recoveryEnd($shift)->toIso8601String(),
                    'officer_ids' => $shift->assignments->where('team_role', '!=', 'station_duty')->pluck('station_officer_id')->values(),
                ])->values(),
        ]);
    }

    public function storeShift(Request $request, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        $data = $this->validateShift($request);
        $team = $this->teamsForStation($stationId)->findOrFail($data['team_id']);
        [$startsAt, $endsAt] = $this->window($data['starts_at'], $data['ends_at']);
        if (in_array($data['schedule_template'] ?? 'single', ['two_team_12', 'two_team_24', 'three_team_24'], true)) {
            return $this->storeTeamRotation($data, $stationId, $startsAt, $endsAt, $service, $recurrence);
        }
        if (($data['recurrence'] ?? 'none') !== 'none') {
            $until = Carbon::parse($data['recurrence_until'])->endOfDay();
            if ($startsAt->diffInHours($endsAt) > 24) throw ValidationException::withMessages(['ends_at' => 'A recurring shift cannot exceed 24 hours.']);
            if ($startsAt->diffInDays($until) > 366) throw ValidationException::withMessages(['recurrence_until' => 'Recurring schedules can be created for up to one year.']);
            $prototype = $this->templatePrototype($data, $team, $stationId, $startsAt, $endsAt, $until);
            $occurrences = $recurrence->occurrences($prototype, $startsAt, $until);
            $this->validateRecoveryBetweenOccurrences($occurrences);
            if ($occurrences === []) throw ValidationException::withMessages(['weekdays' => 'Select a weekday that occurs before the repeat-until date.']);
            foreach ($occurrences as [, $occurrenceStart, $occurrenceEnd]) {
                $this->validateTeamWindow((int) $team->team_id, $occurrenceStart, $occurrenceEnd);
                $this->validateAssignments($data['assignments'], $stationId, $occurrenceStart, $occurrenceEnd);
            }

            $shift = DB::transaction(function () use ($data, $team, $prototype, $startsAt, $until, $recurrence) {
                $template = DutyRosterTemplate::create($prototype->getAttributes());
                $template->assignments()->createMany($data['assignments']);
                $team->forceFill(['roster_managed' => true])->save();
                $recurrence->generate((int) $template->station_id, $startsAt, $until);
                return $template->shifts()->orderBy('starts_at')->firstOrFail();
            });
            $service->syncTeam($team->fresh());
            return response()->json(['success' => true, 'message' => $this->recurrenceLabel($data['recurrence']).' duty schedule created.', 'shift' => $this->serializeShift($shift->load(['assignments.officer.user.role', 'team', 'template']))], 201);
        }

        $this->validateTeamWindow((int) $team->team_id, $startsAt, $endsAt);
        $this->validateAssignments($data['assignments'], $stationId, $startsAt, $endsAt);

        $shift = DB::transaction(function () use ($data, $team, $stationId, $startsAt, $endsAt) {
            $shift = DutyShift::create([
                'station_id' => $stationId,
                'team_id' => $team->team_id,
                'label' => $data['label'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'scheduled',
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);
            $shift->assignments()->createMany($data['assignments']);
            $team->forceFill(['roster_managed' => true])->save();
            return $shift;
        });
        $service->syncTeam($team->fresh());

        return response()->json(['success' => true, 'message' => 'Duty shift scheduled.', 'shift' => $this->serializeShift($shift->load(['assignments.officer.user.role', 'team', 'template']))], 201);
    }

    public function updateShift(Request $request, DutyShift $dutyShift, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        abort_unless((int) $dutyShift->station_id === $stationId, 403);
        abort_if($dutyShift->ends_at?->lte(now()), 409, 'Completed shifts are retained as duty history and cannot be changed.');
        abort_if($dutyShift->team?->status === 'responding' && (int) $dutyShift->team?->active_duty_shift_id === (int) $dutyShift->duty_shift_id, 409, 'A responding team cannot be changed until it returns.');
        $previousTeam = $dutyShift->team;
        $data = $this->validateShift($request);
        $team = $this->teamsForStation($stationId)->findOrFail($data['team_id']);
        [$startsAt, $endsAt] = $this->window($data['starts_at'], $data['ends_at']);

        if (($data['edit_scope'] ?? 'this') === 'future' && $dutyShift->duty_roster_template_id) {
            $until = Carbon::parse($data['recurrence_until'])->endOfDay();
            if ($startsAt->diffInHours($endsAt) > 24) throw ValidationException::withMessages(['ends_at' => 'A recurring shift cannot exceed 24 hours.']);
            if ($startsAt->diffInDays($until) > 366) throw ValidationException::withMessages(['recurrence_until' => 'Recurring schedules can run for up to one year from the changed shift.']);
            $oldTemplate = $dutyShift->template()->firstOrFail();
            $cutoverDate = $dutyShift->occurrence_date?->copy() ?? $dutyShift->starts_at->copy()->startOfDay();
            $newPrototype = $this->templatePrototype($data, $team, $stationId, $startsAt, $endsAt, $until);
            $this->validateRecoveryBetweenOccurrences($recurrence->occurrences($newPrototype, $startsAt, $until));
            if ($recurrence->occurrences($newPrototype, $startsAt, $until) === []) throw ValidationException::withMessages(['weekdays' => 'Select a weekday that occurs before the repeat-until date.']);

            $newShift = DB::transaction(function () use ($oldTemplate, $cutoverDate, $dutyShift, $data, $team, $stationId, $startsAt, $until, $recurrence, $newPrototype) {
                $oldTemplate->update(['effective_until' => $cutoverDate->copy()->subDay()->toDateString()]);
                $oldTemplate->shifts()->whereDate('occurrence_date', '>=', $cutoverDate->toDateString())->delete();
                foreach ($recurrence->occurrences($newPrototype, $startsAt, $until) as [, $occurrenceStart, $occurrenceEnd]) {
                    $this->validateTeamWindow((int) $team->team_id, $occurrenceStart, $occurrenceEnd);
                    $this->validateAssignments($data['assignments'], $stationId, $occurrenceStart, $occurrenceEnd);
                }
                $newTemplate = DutyRosterTemplate::create($newPrototype->getAttributes());
                $newTemplate->assignments()->createMany($data['assignments']);
                $team->forceFill(['roster_managed' => true])->save();
                $recurrence->generate($stationId, $startsAt, $until);
                return $newTemplate->shifts()->orderBy('starts_at')->firstOrFail();
            });
            if ($previousTeam && (int) $previousTeam->team_id !== (int) $team->team_id) $service->syncTeam($previousTeam->fresh());
            $service->syncTeam($team->fresh());
            return response()->json(['success' => true, 'message' => 'This and next shifts changed.', 'shift' => $this->serializeShift($newShift->load(['assignments.officer.user.role', 'team', 'template']))]);
        }

        $this->validateTeamWindow((int) $team->team_id, $startsAt, $endsAt, $dutyShift->duty_shift_id);
        $this->validateAssignments($data['assignments'], $stationId, $startsAt, $endsAt, $dutyShift->duty_shift_id);

        DB::transaction(function () use ($dutyShift, $data, $team, $startsAt, $endsAt) {
            $dutyShift->update([
                'team_id' => $team->team_id, 'label' => $data['label'], 'starts_at' => $startsAt,
                'ends_at' => $endsAt, 'notes' => $data['notes'] ?? null,
                'is_override' => (bool) $dutyShift->duty_roster_template_id,
            ]);
            $dutyShift->assignments()->delete();
            $dutyShift->assignments()->createMany($data['assignments']);
            $team->forceFill(['roster_managed' => true])->save();
        });
        if ($previousTeam && (int) $previousTeam->team_id !== (int) $team->team_id) {
            $service->syncTeam($previousTeam->fresh());
        }
        $service->syncTeam($team->fresh());

        return response()->json(['success' => true, 'message' => $dutyShift->duty_roster_template_id ? 'Only this day changed.' : 'Shift changed.', 'shift' => $this->serializeShift($dutyShift->fresh(['assignments.officer.user.role', 'team', 'template']))]);
    }

    public function destroyShift(Request $request, DutyShift $dutyShift, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        $data = $request->validate([
            'delete_scope' => ['nullable', Rule::in(['this', 'future'])],
        ]);
        abort_unless((int) $dutyShift->station_id === $stationId, 403);
        $team = $dutyShift->team;
        abort_if($dutyShift->ends_at?->lte(now()), 409, 'Completed shifts are retained as duty history and cannot be removed.');
        abort_if($team?->status === 'responding' && (int) $team->active_duty_shift_id === (int) $dutyShift->duty_shift_id, 409, 'An active responding shift cannot be deleted.');
        if (($data['delete_scope'] ?? 'this') === 'future' && $dutyShift->duty_roster_template_id) {
            $template = $dutyShift->template()->firstOrFail();
            $cutoverDate = $dutyShift->occurrence_date?->copy() ?? $dutyShift->starts_at->copy()->startOfDay();
            $teamIdsToSync = $template->rotation_group
                ? DutyRosterTemplate::query()->where('station_id', $template->station_id)->where('rotation_group', $template->rotation_group)->pluck('team_id')
                : collect([$template->team_id]);

            DB::transaction(function () use ($template, $cutoverDate, $dutyShift): void {
                $templates = $template->rotation_group
                    ? DutyRosterTemplate::query()->where('station_id', $template->station_id)->where('rotation_group', $template->rotation_group)->get()
                    : collect([$template]);
                foreach ($templates as $linkedTemplate) {
                    $linkedTemplate->update([
                        'effective_until' => $cutoverDate->copy()->subDay()->toDateString(),
                        'status' => $cutoverDate->lte($linkedTemplate->effective_from) ? 'inactive' : $linkedTemplate->status,
                    ]);
                    $linkedTemplate->shifts()
                        ->where('starts_at', '>=', $dutyShift->starts_at)
                        ->delete();
                }
            });

            Team::query()->whereIn('team_id', $teamIdsToSync)->get()->each(fn (Team $linkedTeam) => $service->syncTeam($linkedTeam));

            return response()->json(['success' => true, 'message' => 'This and next shifts removed.']);
        }

        if ($dutyShift->duty_roster_template_id) {
            $dutyShift->forceFill(['status' => 'cancelled', 'is_override' => true])->save();
        } else {
            $dutyShift->delete();
        }
        if ($team) $service->syncTeam($team->fresh());
        return response()->json(['success' => true, 'message' => 'Shift removed.']);
    }

    public function storeException(Request $request, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        $data = $request->validate([
            'station_officer_id' => ['required', 'integer', 'exists:station_officers,station_officer_id'],
            'type' => ['required', Rule::in(['rest_day', 'leave'])],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        [$startsAt, $endsAt] = $this->window($data['starts_at'], $data['ends_at']);
        $officer = StationOfficer::query()->where('station_id', $stationId)->findOrFail($data['station_officer_id']);
        $this->assertExceptionKeepsCommandCoverage($officer, $startsAt, $endsAt);

        $exception = DutyException::create([
            ...$data, 'station_id' => $stationId, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'created_by' => Auth::id(),
        ]);
        $service->sync($stationId);
        return response()->json(['success' => true, 'message' => 'Rest/leave period saved.', 'exception' => $this->serializeException($exception->load('officer.user.role'))], 201);
    }

    public function destroyException(Request $request, DutyException $dutyException, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        abort_unless((int) $dutyException->station_id === $stationId, 403);
        $dutyException->delete();
        $service->sync($stationId);
        return response()->json(['success' => true, 'message' => 'Rest/leave period removed.']);
    }

    private function validateShift(Request $request): array
    {
        return $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,team_id'], 'label' => ['required', 'string', 'max:100'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],
            'schedule_template' => ['nullable', Rule::in(['single', 'two_team_12', 'two_team_24', 'three_team_24'])],
            'rotation_team_ids' => ['nullable', 'array'],
            'rotation_team_ids.*' => ['integer', 'distinct', 'exists:teams,team_id'],
            'rotation_assignments' => ['nullable', 'array'],
            'rotation_assignments.*' => ['array'],
            'rotation_assignments.*.*.station_officer_id' => ['required', 'integer', 'exists:station_officers,station_officer_id'],
            'rotation_assignments.*.*.team_role' => ['required', Rule::in(DutyRosterService::DEPLOYABLE_ROLES)],
            'recurrence' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'weekdays', 'rotation'])],
            'recurrence_until' => ['nullable', 'required_if:recurrence,daily,weekly,weekdays,rotation', 'required_if:schedule_template,two_team_12,two_team_24,three_team_24', 'date'],
            'rotation_interval_days' => ['nullable', 'required_if:recurrence,rotation', 'integer', 'between:2,31'],
            'weekdays' => ['nullable', 'required_if:recurrence,weekdays', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'edit_scope' => ['nullable', Rule::in(['this', 'future'])],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.station_officer_id' => ['required', 'integer', 'distinct', 'exists:station_officers,station_officer_id'],
            'assignments.*.team_role' => ['required', 'string', Rule::in(DutyRosterService::ALL_ROLES)],
        ]);
    }

    private function storeTeamRotation(array $data, int $stationId, Carbon $startsAt, Carbon $endsAt, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $plan = $data['schedule_template'];
        $neededTeams = $plan === 'three_team_24' ? 3 : 2;
        $hoursPerTurn = $plan === 'two_team_12' ? 12 : 24;
        $teamIds = collect($data['rotation_team_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($teamIds->count() !== $neededTeams) {
            throw ValidationException::withMessages(['rotation_team_ids' => "Choose {$neededTeams} teams."]);
        }
        if (abs((int) $startsAt->diffInMinutes($endsAt)) !== $hoursPerTurn * 60) {
            throw ValidationException::withMessages(['ends_at' => "This plan needs a {$hoursPerTurn}-hour shift."]);
        }

        $until = Carbon::parse($data['recurrence_until'])->endOfDay();
        if ($startsAt->diffInDays($until) > 366) {
            throw ValidationException::withMessages(['recurrence_until' => 'The end date can be up to one year away.']);
        }

        $teams = $this->teamsForStation($stationId)->whereIn('team_id', $teamIds)->get()->keyBy('team_id');
        if ($teams->count() !== $neededTeams) {
            throw ValidationException::withMessages(['rotation_team_ids' => 'Choose teams from this station.']);
        }

        $commandAssignments = collect($data['assignments'])
            ->where('team_role', 'station_duty')
            ->map(fn ($item) => ['station_officer_id' => (int) $item['station_officer_id'], 'team_role' => 'station_duty'])
            ->values()
            ->all();
        if ($commandAssignments === []) {
            throw ValidationException::withMessages(['assignments' => 'Choose a chief or station admin.']);
        }

        $plans = [];
        $rotationGroup = (string) Str::uuid();
        foreach ($teamIds as $turn => $teamId) {
            $team = $teams->get($teamId);
            $turnStart = $plan === 'two_team_12'
                ? $startsAt->copy()->addHours($turn * 12)
                : $startsAt->copy()->addDays($turn);
            if ($turnStart->gt($until)) {
                throw ValidationException::withMessages(['recurrence_until' => 'Choose a later end date.']);
            }
            $turnEnd = $turnStart->copy()->addHours($hoursPerTurn);
            $teamAssignments = collect(DutyRosterService::DEPLOYABLE_ROLES)
                ->map(fn ($role) => $team->{$role} ? ['station_officer_id' => (int) $team->{$role}, 'team_role' => $role] : null)
                ->filter()
                ->unique('station_officer_id')
                ->values()
                ->all();
            if (array_key_exists($teamId, $data['rotation_assignments'] ?? [])) {
                $teamAssignments = $data['rotation_assignments'][$teamId];
            }
            if (count($teamAssignments) < 2) {
                throw ValidationException::withMessages(['rotation_team_ids' => "Team {$teamId} needs at least two saved members."]);
            }

            $assignments = [...$teamAssignments, ...$commandAssignments];
            $prototype = new DutyRosterTemplate([
                'station_id' => $stationId,
                'team_id' => $teamId,
                'label' => trim($data['label'])." - Team {$teamId}",
                'weekday' => $turnStart->dayOfWeekIso,
                'recurrence_type' => $plan === 'two_team_12' ? 'daily' : 'rotation',
                'weekdays' => null,
                'rotation_interval_days' => $plan === 'two_team_12' ? null : $neededTeams,
                'rotation_group' => $rotationGroup,
                'starts_time' => $turnStart->format('H:i:s'),
                'ends_time' => $turnEnd->format('H:i:s'),
                'effective_from' => $turnStart->toDateString(),
                'effective_until' => $until->toDateString(),
                'status' => 'active',
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $occurrences = $recurrence->occurrences($prototype, $turnStart, $until);
            $this->validateRecoveryBetweenOccurrences($occurrences);
            // Proposed runs are not yet saved, so database overlap checks alone
            // cannot catch a firefighter appearing in both rotating crews.
            foreach ($plans as [, , $previousAssignments]) {
                $previousIds = collect($previousAssignments)->where('team_role', '!=', 'station_duty')->pluck('station_officer_id');
                if ($previousIds->intersect(collect($teamAssignments)->pluck('station_officer_id'))->isNotEmpty()) {
                    throw ValidationException::withMessages(['assignments' => 'Rotating runs must use different firefighters so each crew can rest after its shift.']);
                }
            }
            foreach ($occurrences as [, $occurrenceStart, $occurrenceEnd]) {
                $this->validateTeamWindow((int) $teamId, $occurrenceStart, $occurrenceEnd);
                $this->validateAssignments($assignments, $stationId, $occurrenceStart, $occurrenceEnd);
            }
            $plans[] = [$team, $prototype, $assignments];
        }

        $firstShift = DB::transaction(function () use ($plans, $stationId, $startsAt, $until, $recurrence) {
            $templateIds = [];
            foreach ($plans as [$team, $prototype, $assignments]) {
                $template = DutyRosterTemplate::create($prototype->getAttributes());
                $templateIds[] = $template->duty_roster_template_id;
                $template->assignments()->createMany($assignments);
                $team->forceFill(['roster_managed' => true])->save();
            }
            $recurrence->generate($stationId, $startsAt, $until);

            return DutyShift::query()
                ->whereIn('duty_roster_template_id', $templateIds)
                ->orderBy('starts_at')
                ->firstOrFail();
        });

        foreach ($plans as [$team]) $service->syncTeam($team->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Team turns saved.',
            'shift' => $this->serializeShift($firstShift->load(['assignments.officer.user.role', 'team', 'template'])),
        ], 201);
    }

    private function validateAssignments(array $assignments, int $stationId, Carbon $startsAt, Carbon $endsAt, ?int $exceptShiftId = null): void
    {
        $officerIds = collect($assignments)->pluck('station_officer_id')->map(fn ($id) => (int) $id);
        if ($officerIds->unique()->count() !== $officerIds->count()) {
            throw ValidationException::withMessages([
                'assignments' => 'Each person can only hold one duty position per shift.',
            ]);
        }

        $deployableRoles = collect($assignments)->pluck('team_role')->filter(fn ($role) => in_array($role, DutyRosterService::DEPLOYABLE_ROLES, true));
        if ($deployableRoles->unique()->count() !== $deployableRoles->count()) throw ValidationException::withMessages(['assignments' => 'Each deployable team role can only be assigned once per shift.']);
        $officers = StationOfficer::query()->with('user.role')->where('station_id', $stationId)->whereIn('station_officer_id', $officerIds)->get()->keyBy('station_officer_id');
        if ($officers->count() !== $officerIds->count()) throw ValidationException::withMessages(['assignments' => 'Every assignment must belong to your station.']);
        if (collect($assignments)->whereIn('team_role', DutyRosterService::DEPLOYABLE_ROLES)->count() < 2) throw ValidationException::withMessages(['assignments' => 'Assign at least two deployable firefighters.']);
        $stationDuty = collect($assignments)->where('team_role', 'station_duty');
        if ($stationDuty->isEmpty()) throw ValidationException::withMessages(['assignments' => 'Assign a station admin or chief to remain at the station.']);
        foreach ($stationDuty as $commandAssignment) {
            $commandRole = strtolower((string) $officers->get((int) $commandAssignment['station_officer_id'])?->user?->role?->role);
            if (! in_array($commandRole, ['chief', 'station admin', 'chief station admin'], true)) throw ValidationException::withMessages(['assignments' => 'Station duty must be assigned only to a chief or station admin.']);
        }

        foreach ($assignments as $assignment) {
            $officerId = (int) $assignment['station_officer_id'];
            $accountRole = strtolower((string) $officers->get($officerId)?->user?->role?->role);
            if ($assignment['team_role'] !== 'station_duty' && $accountRole !== 'firefighter') {
                throw ValidationException::withMessages(['assignments' => 'Deployable positions can only be assigned to firefighter accounts.']);
            }
            $hasException = DutyException::query()->where('station_officer_id', $officerId)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists();
            if ($hasException) throw ValidationException::withMessages(['assignments' => "Officer {$officerId} has rest day or leave during this shift."]);
            if ($assignment['team_role'] === 'station_duty') continue;
            $recoveryConflict = app(DutyAvailabilityService::class)->conflict($officerId, $startsAt, $endsAt, $exceptShiftId);
            if ($recoveryConflict) {
                $name = trim(($officers->get($officerId)?->user?->first_name ?? '').' '.($officers->get($officerId)?->user?->last_name ?? ''));
                throw ValidationException::withMessages(['assignments' => "{$name} has duty or required recovery time conflicting with this shift. Allow rest equal to the shift length, or select another available firefighter."]);
            }
            $overlap = DutyShiftAssignment::query()->where('station_officer_id', $officerId)->where('team_role', '!=', 'station_duty')
                ->whereHas('shift', fn ($query) => $query->where('status', 'scheduled')->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->when($exceptShiftId, fn ($q) => $q->where('duty_shift_id', '!=', $exceptShiftId)))->exists();
            if ($overlap) throw ValidationException::withMessages(['assignments' => "Officer {$officerId} already has an overlapping deployment shift."]);
        }
    }

    private function validateRecoveryBetweenOccurrences(array $occurrences): void
    {
        $availableAt = null;
        foreach ($occurrences as [, $start, $end]) {
            if ($availableAt && $start->lt($availableAt)) {
                throw ValidationException::withMessages(['recurrence' => 'This repeat pattern does not allow recovery between shifts. For 24-hour duty, repeat every 2 days (24 hours on / 24 hours rest).']);
            }
            $availableAt = $end->copy()->addMinutes(DutyAvailabilityService::recoveryMinutes($start, $end));
        }
    }

    private function validateTeamWindow(int $teamId, Carbon $startsAt, Carbon $endsAt, ?int $exceptShiftId = null): void
    {
        $overlap = DutyShift::query()
            ->where('team_id', $teamId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($exceptShiftId, fn ($query) => $query->where('duty_shift_id', '!=', $exceptShiftId))
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_at' => 'This team already has a shift during part of that time window.']);
        }
    }

    private function assertExceptionKeepsCommandCoverage(StationOfficer $officer, Carbon $startsAt, Carbon $endsAt): void
    {
        $overlappingShifts = DutyShift::query()->where('station_id', $officer->station_id)->where('status', 'scheduled')
            ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
            ->whereHas('assignments', fn ($query) => $query->where('station_officer_id', $officer->station_officer_id))
            ->with('assignments')->get();
        foreach ($overlappingShifts as $shift) {
            $assignment = $shift->assignments->firstWhere('station_officer_id', $officer->station_officer_id);
            if ($assignment?->team_role === 'station_duty') continue;
            $remaining = $shift->assignments->whereIn('team_role', DutyRosterService::DEPLOYABLE_ROLES)
                ->where('station_officer_id', '!=', $officer->station_officer_id)
                ->reject(fn ($candidate) => DutyException::query()->where('station_officer_id', $candidate->station_officer_id)
                    ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists());
            if ($remaining->count() < 2) {
                throw ValidationException::withMessages(['station_officer_id' => "{$shift->label} needs at least two deployable firefighters after this rest/leave period."]);
            }
        }

        $commandShifts = DutyShift::query()->where('station_id', $officer->station_id)->where('status', 'scheduled')->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
            ->whereHas('assignments', fn ($query) => $query->where('station_officer_id', $officer->station_officer_id)->where('team_role', 'station_duty'))->with('assignments')->get();
        foreach ($commandShifts as $shift) {
            $alternates = $shift->assignments->where('team_role', 'station_duty')->where('station_officer_id', '!=', $officer->station_officer_id)
                ->reject(fn ($assignment) => DutyException::query()->where('station_officer_id', $assignment->station_officer_id)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists());
            if ($alternates->isEmpty()) throw ValidationException::withMessages(['station_officer_id' => "{$shift->label} would have no chief or station admin remaining at the station."]);
        }
    }

    private function window(string $start, string $end): array
    {
        $startsAt = Carbon::parse($start); $endsAt = Carbon::parse($end);
        if ($endsAt->lte($startsAt)) throw ValidationException::withMessages(['ends_at' => 'Shift end must be after shift start. Overnight shifts must use the following date.']);
        if ($startsAt->diffInHours($endsAt) > 24 * 7) throw ValidationException::withMessages(['ends_at' => 'A single roster period cannot exceed seven days.']);
        return [$startsAt, $endsAt];
    }

    private function managedStationId(Request $request): int
    {
        $role = strtolower((string) Auth::user()?->role?->role);
        if ($role === 'provincial admin') {
            $stationId = (int) $request->input('station_id');
            abort_unless($stationId && Station::query()->whereKey($stationId)->exists(), 422, 'Select a valid station before managing its duty roster.');

            return $stationId;
        }

        abort_unless(in_array($role, ['chief', 'station admin', 'chief station admin'], true), 403);
        $stationId = StationOfficer::query()->where('user_id', Auth::id())->value('station_id');
        abort_unless($stationId, 403, 'Your account is not assigned to a station.');
        return (int) $stationId;
    }

    private function teamsForStation(int $stationId)
    {
        $officerIds = StationOfficer::query()
            ->where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->all();

        return Team::query()->where(function ($query) use ($stationId, $officerIds) {
            $query->where('station_id', $stationId);
            if ($officerIds === []) {
                return;
            }
            foreach (DutyRosterService::DEPLOYABLE_ROLES as $role) {
                $query->orWhereIn($role, $officerIds);
            }
        });
    }

    private function serializeRosterTeam(Team $team, int $stationId): array
    {
        $memberCount = collect(DutyRosterService::DEPLOYABLE_ROLES)
            ->map(fn (string $role) => $team->{$role})
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->count();
        $missingMembers = max(0, 2 - $memberCount);
        $status = strtolower(trim((string) $team->status));
        $reason = $missingMembers > 0
            ? 'Needs '.$missingMembers.' more saved '.($missingMembers === 1 ? 'firefighter' : 'firefighters').' for a rotating run.'
            : match ($status) {
                'responding' => 'Currently responding. Choose a shift that starts after the active response.',
                'off' => 'Currently off duty. The team activates when its scheduled shift begins.',
                default => 'Ready for shift planning.',
            };

        return array_merge($team->toArray(), [
            'station_link_source' => (int) $team->station_id === $stationId ? 'direct' : 'team_members',
            'saved_member_count' => $memberCount,
            'schedule_ready' => $missingMembers === 0,
            'schedule_reason' => $reason,
        ]);
    }

    private function templatePrototype(array $data, Team $team, int $stationId, Carbon $startsAt, Carbon $endsAt, Carbon $until): DutyRosterTemplate
    {
        if ($until->lt($startsAt)) throw ValidationException::withMessages(['recurrence_until' => 'The repeat-until date must be on or after the first shift.']);
        return new DutyRosterTemplate([
            'station_id' => $stationId,
            'team_id' => $team->team_id,
            'label' => $data['label'],
            'weekday' => $startsAt->dayOfWeekIso,
            'recurrence_type' => $data['recurrence'],
            'weekdays' => $data['recurrence'] === 'weekdays'
                ? collect($data['weekdays'] ?? [])->map(fn ($weekday) => (int) $weekday)->unique()->sort()->values()->all()
                : null,
            'rotation_interval_days' => $data['recurrence'] === 'rotation' ? (int) $data['rotation_interval_days'] : null,
            'starts_time' => $startsAt->format('H:i:s'),
            'ends_time' => $endsAt->format('H:i:s'),
            'effective_from' => $startsAt->toDateString(),
            'effective_until' => $until->toDateString(),
            'status' => 'active',
            'created_by' => Auth::id(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    private function serializeOfficer($officer): array { return ['id' => $officer->station_officer_id, 'name' => trim(($officer->user?->first_name ?? '').' '.($officer->user?->last_name ?? '')), 'role' => $officer->user?->role?->role, 'rank' => $officer->rank, 'status' => $officer->status]; }
    private function recurrenceLabel(string $recurrence): string
    {
        return match ($recurrence) {
            'daily' => 'Daily',
            'weekdays' => 'Selected-weekday',
            default => 'Weekly',
        };
    }

    private function serializeShift(DutyShift $shift): array { return ['id' => $shift->duty_shift_id, 'team_id' => $shift->team_id, 'label' => $shift->label, 'starts_at' => $shift->starts_at?->toIso8601String(), 'ends_at' => $shift->ends_at?->toIso8601String(), 'status' => $shift->status, 'notes' => $shift->notes, 'is_recurring' => (bool) $shift->duty_roster_template_id, 'recurrence_type' => $shift->template?->recurrence_type ?: ($shift->duty_roster_template_id ? 'weekly' : 'none'), 'weekdays' => $shift->template?->weekdays ?? [], 'rotation_interval_days' => $shift->template?->rotation_interval_days, 'is_override' => (bool) $shift->is_override, 'occurrence_date' => $shift->occurrence_date?->toDateString(), 'recurrence_until' => $shift->template?->effective_until?->toDateString(), 'assignments' => $shift->assignments->map(fn ($a) => ['id' => $a->duty_shift_assignment_id, 'station_officer_id' => $a->station_officer_id, 'team_role' => $a->team_role, 'officer' => $this->serializeOfficer($a->officer)])->values()]; }
    private function serializeException(DutyException $exception): array { return ['id' => $exception->duty_exception_id, 'station_officer_id' => $exception->station_officer_id, 'type' => $exception->type, 'starts_at' => $exception->starts_at?->toIso8601String(), 'ends_at' => $exception->ends_at?->toIso8601String(), 'reason' => $exception->reason, 'officer' => $this->serializeOfficer($exception->officer)]; }
}
