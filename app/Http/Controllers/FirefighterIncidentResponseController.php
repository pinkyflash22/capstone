<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentDispatchAudit;
use App\Models\IncidentResponseUnit;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Observers\IncidentObserver;
use App\Services\DutyRosterService;
use App\Services\IncidentAutoAssignmentService;
use App\Services\IncidentResourceDispatchService;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FirefighterIncidentResponseController extends Controller
{
    public function respond(
        Incident $incident,
        IncidentResourceDispatchService $resources,
        IncidentAutoAssignmentService $assignments
    ): JsonResponse
    {
        $response = DB::transaction(function () use ($incident, $resources) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            $team = $this->respondingTeamForOfficer($locked, $officer);
            abort_unless($team, 403, 'You are not assigned to this incident response.');
            abort_if(in_array(strtolower((string) $locked->status), ['controlled', 'closed'], true), 409, 'This incident is no longer accepting responders.');

            $existing = IncidentResponseUnit::query()->where('incident_id', $locked->incident_id)->where('team_id', $team->team_id)
                ->whereIn('status', ['reserved', 'en_route', 'on_scene'])->first();
            if ($existing) return ['unit' => $existing, 'team_id' => (int) $team->team_id];

            if ($resources->incidentRequiresFiretruck($locked)) {
                $reserved = $resources->reservePrimary($locked, $team, (int) $officer->station_id, null, Auth::id());
                abort_unless($reserved, 409, 'Your station has no available firetruck. This team cannot respond until an apparatus is available.');
                $existing = $reserved;
            } else {
                $resources->dispatchTeam($team);
            }

            $statusUpdate = ['status' => 'Responding'];
            if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
                $statusUpdate['dispatch_assigned_at'] = null;
            }
            $locked->forceFill($statusUpdate)->save();
            return ['unit' => $existing, 'team_id' => (int) $team->team_id];
        });

        $queue = $assignments->reassignReportedIncidentsFromOccupiedTeam(
            $response['team_id'],
            (int) $incident->incident_id
        );
        $unit = $response['unit'];
        $message = $unit ? 'Response confirmed and a firetruck has been reserved.' : 'Response confirmed.';
        if (count($queue['reassigned']) > 0) {
            $message .= ' Other waiting incident assignments were moved to available response teams.';
        }
        if (count($queue['queued']) > 0) {
            $message .= ' Incidents without another available team remain queued for station escalation.';
        }
        UserActivityLogger::log("responded to incident #{$incident->incident_id}", Auth::id(), request());

        return response()->json([
            'success' => true,
            'message' => $message,
            'unit' => $unit ? $resources->serializeUnit($unit) : null,
            'queue' => $queue,
        ]);
    }

    public function requestBackup(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $unit = $resources->requestBackup($incident, (int) Auth::id());
        $source = $unit->dispatch_source === 'same_station' ? 'your station' : ($unit->station?->station_name ?: 'the nearest available station');
        UserActivityLogger::log("requested backup run {$unit->run_number} for incident #{$incident->incident_id}", Auth::id(), request());

        return response()->json([
            'success' => true,
            'message' => "Backup run {$unit->run_number} dispatched from {$source}.",
            'unit' => $resources->serializeUnit($unit),
        ], 201);
    }

    public function arrive(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $alreadyOnScene = DB::transaction(function () use ($incident, $resources) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            abort_unless($this->respondingTeamForOfficer($locked, $officer), 403, 'You are not assigned to this incident response.');
            $status = strtolower(trim((string) $locked->status));

            if ($status === 'on scene') {
                $resources->markIncidentUnitsOnScene($locked);

                return true;
            }

            abort_unless($status === 'responding', 409, 'Only a responding incident can be marked on scene.');

            $locked->forceFill(['status' => 'On Scene'])->save();
            $resources->markIncidentUnitsOnScene($locked);

            return false;
        });

        if (! $alreadyOnScene) {
            UserActivityLogger::log("marked incident #{$incident->incident_id} on scene", Auth::id(), request());
        }

        return response()->json([
            'success' => true,
            'message' => $alreadyOnScene
                ? 'Arrival was already confirmed. Incident is On Scene.'
                : 'Arrival confirmed. Incident updated to On Scene.',
        ]);
    }

    public function complete(
        Request $request,
        Incident $incident,
        IncidentResourceDispatchService $resources,
        IncidentAutoAssignmentService $assignments
    ): JsonResponse
    {
        $validated = $request->validate([
            'completion_readiness' => ['nullable', 'string', 'in:ready,needs_refill'],
        ]);
        $completionReadiness = $validated['completion_readiness'] ?? 'ready';
        $releaseAsAvailable = $completionReadiness === 'ready';

        $completed = DB::transaction(function () use ($incident) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            abort_unless($this->respondingTeamForOfficer($locked, $officer), 403, 'You are not assigned to this incident response.');
            abort_unless(strtolower(trim((string) $locked->status)) === 'on scene', 409, 'Confirm arrival before completing this incident.');

            IncidentObserver::skipAutomaticClosureReleaseFor((int) $locked->incident_id);
            $locked->forceFill(['status' => 'Controlled'])->save();
            if ($locked->marker) {
                $locked->marker->forceFill(['status' => 'completed'])->save();
            }

            return $locked;
        });

        $resources->releaseIncidentUnits($completed, $releaseAsAvailable);
        $queue = $releaseAsAvailable
            ? $assignments->assignRecentUnassignedIncidents()
            : ['checked' => 0, 'assigned' => [], 'expired' => []];
        UserActivityLogger::log("marked incident #{$incident->incident_id} controlled", Auth::id(), request());

        $message = $releaseAsAvailable
            ? 'Incident completed. Team, personnel, and apparatus are available again.'
            : 'Incident completed. Team was held out of reassignment for refill/restock.';

        if (! $releaseAsAvailable) {
            $this->recordTeamReadinessAudit(
                $completed,
                'team_refill_required',
                'Team requested refill/restock before accepting another dispatch.'
            );
            UserActivityLogger::log("held response team for refill after incident #{$incident->incident_id}", Auth::id(), request());
        } elseif (count($queue['assigned'] ?? []) > 0) {
            $message .= ' '.$this->assignmentQueueSummary($queue);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'completion_readiness' => $completionReadiness,
            'team_available' => $releaseAsAvailable,
            'queue' => $queue,
        ]);
    }

    public function markTeamReady(IncidentAutoAssignmentService $assignments): JsonResponse
    {
        $officer = $this->currentOfficer();
        $team = $this->currentOfficerTeam($officer);
        abort_unless($team, 403, 'You are not assigned to a firefighter response team.');

        $latestIncident = null;

        DB::transaction(function () use ($team, $officer, &$latestIncident) {
            $lockedTeam = Team::query()->whereKey($team->team_id)->lockForUpdate()->firstOrFail();
            $memberIds = $this->teamMemberIds($lockedTeam);

            $lockedTeam->forceFill(['status' => 'available'])->save();
            StationOfficer::query()
                ->whereIn('station_officer_id', $memberIds)
                ->whereIn('status', ['unavailable', 'off-duty', 'busy'])
                ->update(['status' => 'available', 'updated_at' => now()]);

            $latestIncident = $this->latestIncidentForTeam((int) $lockedTeam->team_id);
            if ($latestIncident) {
                $this->recordTeamReadinessAudit(
                    $latestIncident,
                    'team_refill_completed',
                    'Team marked ready after refill/restock.',
                    (int) $lockedTeam->team_id
                );
            }

            foreach (
                StationOfficer::query()
                    ->whereIn('station_officer_id', $memberIds->push((int) $officer->station_officer_id)->unique())
                    ->pluck('user_id')
                    ->filter() as $userId
            ) {
                Cache::forget("firefighter:assigned:{$userId}");
                Cache::forget("firefighter:dashboard:widgets:{$userId}");
            }
        });

        $queue = $assignments->assignRecentUnassignedIncidents();
        UserActivityLogger::log('marked firefighter team ready after refill', Auth::id(), request());

        $message = 'Team marked ready for dispatch.';
        if (count($queue['assigned'] ?? []) > 0) {
            $message .= ' '.$this->assignmentQueueSummary($queue);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'team_status' => 'available',
            'queue' => $queue,
        ]);
    }

    public function falseAlarm(
        Incident $incident,
        IncidentResourceDispatchService $resources,
        IncidentAutoAssignmentService $assignments
    ): JsonResponse
    {
        $closed = DB::transaction(function () use ($incident) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            abort_unless($this->respondingTeamForOfficer($locked, $officer), 403, 'You are not assigned to this incident response.');
            abort_unless(
                in_array(strtolower(trim((string) $locked->status)), ['reported', 'responding'], true),
                409,
                'Only an active report can be marked as a false alarm.'
            );

            IncidentObserver::skipAutomaticClosureReleaseFor((int) $locked->incident_id);
            $locked->forceFill(['status' => 'Closed'])->save();
            if ($locked->marker) {
                $locked->marker->forceFill(['status' => 'inactive'])->save();
            }

            return $locked;
        });

        $resources->releaseIncidentUnits($closed);
        $queue = $assignments->assignRecentUnassignedIncidents();
        UserActivityLogger::log("closed incident #{$incident->incident_id} as false alarm", Auth::id(), request());

        $message = 'Incident marked as a false alarm and closed.';
        if (count($queue['assigned'] ?? []) > 0) {
            $message .= ' '.$this->assignmentQueueSummary($queue);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'queue' => $queue,
        ]);
    }

    public function close(Incident $incident): JsonResponse
    {
        DB::transaction(function () use ($incident) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            abort_unless($this->respondingTeamForOfficer($locked, $officer), 403, 'You are not assigned to this incident.');
            abort_unless(strtolower(trim((string) $locked->status)) === 'controlled', 409, 'Complete the incident before closing the case.');
            $locked->forceFill(['status' => 'Closed'])->save();
            if ($locked->marker) {
                $locked->marker->forceFill(['status' => 'inactive'])->save();
            }
        });
        UserActivityLogger::log("closed incident #{$incident->incident_id}", Auth::id(), request());

        return response()->json(['success' => true, 'message' => 'Incident case closed.']);
    }

    public function resources(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $officer = $this->currentOfficer();
        abort_unless($this->respondingTeamForOfficer($incident, $officer), 403);
        return response()->json([
            'success' => true,
            'units' => $incident->responseUnits()->with(['station.marker', 'equipmentAssignment.equipment', 'apparatus'])->orderBy('run_number')->get()->map(fn ($unit) => $resources->serializeUnit($unit))->values(),
        ]);
    }

    public function recallBackup(Request $request, Incident $incident, IncidentResponseUnit $unit, IncidentResourceDispatchService $resources): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $recalled = $resources->recallBackup(
            $incident,
            $unit,
            (int) Auth::id(),
            trim((string) ($validated['reason'] ?? 'Backup is no longer required.')) ?: 'Backup is no longer required.'
        );

        return response()->json([
            'success' => true,
            'message' => "Backup run {$recalled->run_number} was recalled before arrival.",
            'unit' => $resources->serializeUnit($recalled),
        ]);
    }

    private function currentOfficer(): StationOfficer
    {
        return StationOfficer::query()->where('user_id', Auth::id())->firstOrFail();
    }

    private function currentOfficerTeam(StationOfficer $officer): ?Team
    {
        return Team::query()
            ->where(function ($query) use ($officer) {
                foreach (DutyRosterService::DEPLOYABLE_ROLES as $role) {
                    $query->orWhere($role, $officer->station_officer_id);
                }
            })
            ->orderBy('team_id')
            ->first();
    }

    private function latestIncidentForTeam(int $teamId): ?Incident
    {
        $incidentId = IncidentResponseUnit::query()
            ->where('team_id', $teamId)
            ->latest('updated_at')
            ->value('incident_id');

        return $incidentId
            ? Incident::query()->find((int) $incidentId)
            : Incident::query()->where('team_id', $teamId)->latest('updated_at')->first();
    }

    private function recordTeamReadinessAudit(
        Incident $incident,
        string $action,
        string $reason,
        ?int $teamId = null
    ): void {
        if (! Schema::hasTable('incident_dispatch_audits')) {
            return;
        }

        $resolvedTeamId = $teamId ?: ($incident->team_id ? (int) $incident->team_id : null);

        IncidentDispatchAudit::create([
            'incident_id' => $incident->incident_id,
            'actor_user_id' => Auth::id(),
            'action' => $action,
            'previous_team_id' => $resolvedTeamId,
            'assigned_team_id' => $resolvedTeamId,
            'source_station_id' => $incident->station_id,
            'target_station_id' => $incident->station_id,
            'reason' => $reason,
            'metadata' => [
                'source' => 'firefighter_response',
                'team_readiness' => $action === 'team_refill_completed' ? 'ready' : 'needs_refill',
            ],
        ]);
    }

    private function teamMemberIds(Team $team)
    {
        return collect(DutyRosterService::DEPLOYABLE_ROLES)
            ->map(fn (string $role) => $team->{$role})
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function assignmentQueueSummary(array $queue): string
    {
        $assigned = collect($queue['assigned'] ?? [])->filter();
        $count = $assigned->count();

        if ($count === 0) {
            return 'No waiting incidents were reassigned.';
        }

        $first = $assigned->first();
        $firstIncident = data_get($first, 'incident_id');
        $firstTeam = data_get($first, 'team_name') ?: (data_get($first, 'team_id') ? 'Team #'.data_get($first, 'team_id') : 'an available team');
        $prefix = $count === 1
            ? '1 recent waiting incident under 3 hours old was reassigned'
            : "{$count} recent waiting incidents under 3 hours old were reassigned";

        if ($firstIncident) {
            $prefix .= " starting with incident #{$firstIncident} to {$firstTeam}.";
        } else {
            $prefix .= ' to available teams.';
        }

        return $prefix;
    }

    private function respondingTeamForOfficer(Incident $incident, StationOfficer $officer): ?Team
    {
        $teamIds = collect([$incident->team_id])->concat(
            IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)->whereIn('status', ['reserved', 'en_route', 'on_scene'])->pluck('team_id')
        )->filter()->unique();
        return Team::query()->whereIn('team_id', $teamIds)->where(function ($query) use ($officer) {
            foreach (DutyRosterService::DEPLOYABLE_ROLES as $role) $query->orWhere($role, $officer->station_officer_id);
        })->first();
    }
}
