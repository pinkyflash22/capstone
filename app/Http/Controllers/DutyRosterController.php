<?php

namespace App\Http\Controllers;

use App\Models\DutyException;
use App\Models\DutyRosterTemplate;
use App\Models\DutyShift;
use App\Models\DutyShiftAssignment;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Services\DutyRosterService;
use App\Services\DutyRosterRecurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DutyRosterController extends Controller
{
    public function index(Request $request, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $stationId = $this->managedStationId();
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
            'teams' => Team::query()->where('station_id', $stationId)->orderBy('team_id')->get(),
            'officers' => $officers->map(fn ($officer) => $this->serializeOfficer($officer))->values(),
            'shifts' => $shifts->map(fn ($shift) => $this->serializeShift($shift))->values(),
            'exceptions' => $exceptions->map(fn ($exception) => $this->serializeException($exception))->values(),
        ]);
    }

    public function storeShift(Request $request, DutyRosterService $service, DutyRosterRecurrenceService $recurrence): JsonResponse
    {
        $stationId = $this->managedStationId();
        $data = $this->validateShift($request);
        $team = Team::query()->where('station_id', $stationId)->findOrFail($data['team_id']);
        [$startsAt, $endsAt] = $this->window($data['starts_at'], $data['ends_at']);
        if (($data['recurrence'] ?? 'none') === 'weekly') {
            $until = Carbon::parse($data['recurrence_until'])->endOfDay();
            if ($startsAt->diffInHours($endsAt) > 24) throw ValidationException::withMessages(['ends_at' => 'A recurring weekly shift cannot exceed 24 hours.']);
            if ($startsAt->diffInDays($until) > 366) throw ValidationException::withMessages(['recurrence_until' => 'Weekly schedules can be created for up to one year.']);
            $prototype = $this->templatePrototype($data, $team, $stationId, $startsAt, $endsAt, $until);
            foreach ($recurrence->occurrences($prototype, $startsAt, $until) as [, $occurrenceStart, $occurrenceEnd]) {
                $this->validateTeamWindow((int) $team->team_id, $occurrenceStart, $occurrenceEnd);
                $this->validateAssignments($data['assignments'], $stationId, $occurrenceStart, $occurrenceEnd);
            }

            $shift = DB::transaction(function () use ($data, $team, $prototype, $startsAt, $until, $recurrence) {
                $template = DutyRosterTemplate::create($prototype->getAttributes());
                $template->assignments()->createMany($data['assignments']);
                $team->forceFill(['roster_managed' => true])->save();
                $recurrence->generate((int) $template->station_id, $startsAt, $until);
                return $template->shifts()->whereDate('occurrence_date', $startsAt->toDateString())->firstOrFail();
            });
            $service->syncTeam($team->fresh());
            return response()->json(['success' => true, 'message' => 'Weekly duty schedule created.', 'shift' => $this->serializeShift($shift->load(['assignments.officer.user.role', 'team', 'template']))], 201);
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
        $stationId = $this->managedStationId();
        abort_unless((int) $dutyShift->station_id === $stationId, 403);
        abort_if($dutyShift->ends_at?->lte(now()), 409, 'Completed shifts are retained as duty history and cannot be changed.');
        abort_if($dutyShift->team?->status === 'responding' && (int) $dutyShift->team?->active_duty_shift_id === (int) $dutyShift->duty_shift_id, 409, 'A responding team cannot be changed until it returns.');
        $previousTeam = $dutyShift->team;
        $data = $this->validateShift($request);
        $team = Team::query()->where('station_id', $stationId)->findOrFail($data['team_id']);
        [$startsAt, $endsAt] = $this->window($data['starts_at'], $data['ends_at']);

        if (($data['edit_scope'] ?? 'this') === 'future' && $dutyShift->duty_roster_template_id) {
            $until = Carbon::parse($data['recurrence_until'])->endOfDay();
            if ($startsAt->diffInHours($endsAt) > 24) throw ValidationException::withMessages(['ends_at' => 'A recurring weekly shift cannot exceed 24 hours.']);
            if ($startsAt->diffInDays($until) > 366) throw ValidationException::withMessages(['recurrence_until' => 'Weekly schedules can run for up to one year from the changed shift.']);
            $oldTemplate = $dutyShift->template()->firstOrFail();
            $cutoverDate = $dutyShift->occurrence_date?->copy() ?? $dutyShift->starts_at->copy()->startOfDay();
            $newPrototype = $this->templatePrototype($data, $team, $stationId, $startsAt, $endsAt, $until);

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
                return $newTemplate->shifts()->whereDate('occurrence_date', $startsAt->toDateString())->firstOrFail();
            });
            if ($previousTeam && (int) $previousTeam->team_id !== (int) $team->team_id) $service->syncTeam($previousTeam->fresh());
            $service->syncTeam($team->fresh());
            return response()->json(['success' => true, 'message' => 'This and future weekly shifts were updated.', 'shift' => $this->serializeShift($newShift->load(['assignments.officer.user.role', 'team', 'template']))]);
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

        return response()->json(['success' => true, 'message' => $dutyShift->duty_roster_template_id ? 'Only this shift was updated.' : 'Duty shift updated.', 'shift' => $this->serializeShift($dutyShift->fresh(['assignments.officer.user.role', 'team', 'template']))]);
    }

    public function destroyShift(DutyShift $dutyShift, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId();
        abort_unless((int) $dutyShift->station_id === $stationId, 403);
        $team = $dutyShift->team;
        abort_if($dutyShift->ends_at?->lte(now()), 409, 'Completed shifts are retained as duty history and cannot be removed.');
        abort_if($team?->status === 'responding' && (int) $team->active_duty_shift_id === (int) $dutyShift->duty_shift_id, 409, 'An active responding shift cannot be deleted.');
        if ($dutyShift->duty_roster_template_id) {
            $dutyShift->forceFill(['status' => 'cancelled', 'is_override' => true])->save();
        } else {
            $dutyShift->delete();
        }
        if ($team) $service->syncTeam($team->fresh());
        return response()->json(['success' => true, 'message' => 'Duty shift removed.']);
    }

    public function storeException(Request $request, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId();
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

    public function destroyException(DutyException $dutyException, DutyRosterService $service): JsonResponse
    {
        $stationId = $this->managedStationId();
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
            'recurrence' => ['nullable', Rule::in(['none', 'weekly'])],
            'recurrence_until' => ['nullable', 'required_if:recurrence,weekly', 'date'],
            'edit_scope' => ['nullable', Rule::in(['this', 'future'])],
            'assignments' => ['required', 'array', 'min:3'],
            'assignments.*.station_officer_id' => ['required', 'integer', 'distinct', 'exists:station_officers,station_officer_id'],
            'assignments.*.team_role' => ['required', 'string', Rule::in(DutyRosterService::ALL_ROLES)],
        ]);
    }

    private function validateAssignments(array $assignments, int $stationId, Carbon $startsAt, Carbon $endsAt, ?int $exceptShiftId = null): void
    {
        $officerIds = collect($assignments)->pluck('station_officer_id')->map(fn ($id) => (int) $id);
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
            $overlap = DutyShiftAssignment::query()->where('station_officer_id', $officerId)->where('team_role', '!=', 'station_duty')
                ->whereHas('shift', fn ($query) => $query->where('status', 'scheduled')->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->when($exceptShiftId, fn ($q) => $q->where('duty_shift_id', '!=', $exceptShiftId)))->exists();
            if ($overlap) throw ValidationException::withMessages(['assignments' => "Officer {$officerId} already has an overlapping deployment shift."]);
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

    private function managedStationId(): int
    {
        $role = strtolower((string) Auth::user()?->role?->role);
        abort_unless(in_array($role, ['chief', 'station admin', 'chief station admin'], true), 403);
        $stationId = StationOfficer::query()->where('user_id', Auth::id())->value('station_id');
        abort_unless($stationId, 403, 'Your account is not assigned to a station.');
        return (int) $stationId;
    }

    private function templatePrototype(array $data, Team $team, int $stationId, Carbon $startsAt, Carbon $endsAt, Carbon $until): DutyRosterTemplate
    {
        if ($until->lt($startsAt)) throw ValidationException::withMessages(['recurrence_until' => 'The repeat-until date must be on or after the first shift.']);
        return new DutyRosterTemplate([
            'station_id' => $stationId,
            'team_id' => $team->team_id,
            'label' => $data['label'],
            'weekday' => $startsAt->dayOfWeekIso,
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
    private function serializeShift(DutyShift $shift): array { return ['id' => $shift->duty_shift_id, 'team_id' => $shift->team_id, 'label' => $shift->label, 'starts_at' => $shift->starts_at?->toIso8601String(), 'ends_at' => $shift->ends_at?->toIso8601String(), 'status' => $shift->status, 'notes' => $shift->notes, 'is_recurring' => (bool) $shift->duty_roster_template_id, 'is_override' => (bool) $shift->is_override, 'occurrence_date' => $shift->occurrence_date?->toDateString(), 'recurrence_until' => $shift->template?->effective_until?->toDateString(), 'assignments' => $shift->assignments->map(fn ($a) => ['id' => $a->duty_shift_assignment_id, 'station_officer_id' => $a->station_officer_id, 'team_role' => $a->team_role, 'officer' => $this->serializeOfficer($a->officer)])->values()]; }
    private function serializeException(DutyException $exception): array { return ['id' => $exception->duty_exception_id, 'station_officer_id' => $exception->station_officer_id, 'type' => $exception->type, 'starts_at' => $exception->starts_at?->toIso8601String(), 'ends_at' => $exception->ends_at?->toIso8601String(), 'reason' => $exception->reason, 'officer' => $this->serializeOfficer($exception->officer)]; }
}
