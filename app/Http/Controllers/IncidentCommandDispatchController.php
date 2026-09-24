<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentDispatchAudit;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Services\IncidentDispatchStateService;
use App\Services\IncidentResourceDispatchService;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class IncidentCommandDispatchController extends Controller
{
    public function options(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $this->authorizeCommand($incident);
        $incident->loadMissing(['marker', 'station', 'team']);
        $originStationId = (int) ($incident->station_id ?? 0);
        $localTeams = $originStationId > 0
            ? $resources->dispatchableTeamsForIncident($incident, $originStationId)
            : collect();

        $externalStations = $resources->stationsByIncidentDistance($incident)
            ->reject(fn ($station) => (int) $station->station_id === $originStationId)
            ->map(function ($station) use ($incident, $resources) {
                $teams = $resources->dispatchableTeamsForIncident($incident, (int) $station->station_id);
                if ($teams->isEmpty()) return null;
                return [
                    'station_id' => (int) $station->station_id,
                    'station_name' => $station->station_name,
                    'distance_meters' => round((float) $station->distance_meters),
                    'available_team_count' => $teams->count(),
                    'available_firetruck_count' => $resources->stationAvailableFiretruckCount((int) $station->station_id),
                ];
            })->filter()->take(5)->values();

        $dispatch = app(IncidentDispatchStateService::class)->describe($incident);
        return response()->json([
            'success' => true,
            'data' => [
                'incident_id' => (int) $incident->incident_id,
                'status' => $incident->status,
                'station_id' => $originStationId ?: null,
                'station_name' => $incident->station?->station_name,
                'assigned_team_id' => $incident->team_id ? (int) $incident->team_id : null,
                'dispatch_state' => $dispatch['dispatch_state'],
                'dispatch_state_label' => $dispatch['dispatch_state_label'],
                'needs_external_response' => strtolower((string) $incident->status) === 'reported' && $localTeams->isEmpty(),
                'local_teams' => $localTeams->map(fn (Team $team) => $this->serializeTeam($team))->values(),
                'external_stations' => $externalStations,
            ],
        ]);
    }

    public function reassign(Request $request, Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $this->authorizeCommand($incident);
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,team_id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($incident, $validated, $resources) {
            $locked = Incident::query()->with(['marker', 'station'])->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            if (strtolower(trim((string) $locked->status)) !== 'reported') {
                throw ValidationException::withMessages(['incident' => 'Only a reported incident can be manually reassigned. Use cross-station backup for an active response.']);
            }

            $team = Team::query()->whereKey((int) $validated['team_id'])->lockForUpdate()->firstOrFail();
            if ((int) $team->station_id !== (int) $locked->station_id) {
                throw ValidationException::withMessages(['team_id' => 'Manual reassignment is limited to the incident station. Use Call Nearest Station for an external response.']);
            }
            if (! $resources->teamCanAcceptIncident($team, $locked)) {
                throw ValidationException::withMessages(['team_id' => 'That team no longer has an available crew and firetruck for this incident.']);
            }

            $previousTeamId = $locked->team_id ? (int) $locked->team_id : null;
            $locked->team_id = $team->team_id;
            if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) $locked->dispatch_assigned_at = now();
            $locked->save();

            return ['incident' => $locked->fresh(['team', 'station']), 'team' => $team, 'previous_team_id' => $previousTeamId];
        });

        $this->audit($result['incident'], 'manual_reassignment', $request, [
            'previous_team_id' => $result['previous_team_id'],
            'assigned_team_id' => $result['team']->team_id,
            'source_station_id' => $result['incident']->station_id,
            'target_station_id' => $result['team']->station_id,
            'reason' => trim($validated['reason']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident reassigned to Team #'.$result['team']->team_id.'. The incident remains Reported until that team confirms Respond Now.',
            'data' => ['incident_id' => (int) $incident->incident_id, 'team' => $this->serializeTeam($result['team'])],
        ]);
    }

    public function externalResponse(Request $request, Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $this->authorizeCommand($incident);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $result = $resources->requestExternalResponse($incident, (int) Auth::id());
        $unit = $result['unit'];

        $this->audit($result['incident'], $result['mode'], $request, [
            'previous_team_id' => $result['previous_team_id'],
            'assigned_team_id' => $result['team']->team_id,
            'source_station_id' => $result['incident']->station_id,
            'target_station_id' => $result['station']->station_id,
            'response_unit_id' => $unit?->incident_response_unit_id,
            'reason' => trim($validated['reason']),
            'metadata' => ['distance_meters' => round((float) $result['station']->distance_meters)],
        ]);

        $message = $result['mode'] === 'external_assignment'
            ? "Nearest capable station selected: {$result['station']->station_name}, Team #{$result['team']->team_id}. Awaiting their Respond Now confirmation."
            : "Backup dispatched from {$result['station']->station_name} as run {$unit->run_number}.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'mode' => $result['mode'],
                'station_id' => (int) $result['station']->station_id,
                'station_name' => $result['station']->station_name,
                'team' => $this->serializeTeam($result['team']),
                'unit' => $unit ? $resources->serializeUnit($unit) : null,
            ],
        ], 201);
    }

    private function authorizeCommand(Incident $incident): void
    {
        $user = Auth::user();
        abort_unless($user, 401);
        $role = strtolower(trim((string) $user->role?->role));
        if (in_array($role, ['provincial admin', 'admin'], true)) return;
        abort_unless(in_array($role, ['chief', 'stationadmin'], true), 403);

        $stationId = StationOfficer::query()->where('user_id', $user->user_id)->value('station_id');
        abort_unless($stationId && (int) $incident->station_id === (int) $stationId, 403, 'You can command only incidents assigned to your station.');
    }

    private function serializeTeam(Team $team): array
    {
        return [
            'team_id' => (int) $team->team_id,
            'label' => $team->team_name ?? ('Team #'.$team->team_id),
            'station_id' => (int) $team->station_id,
            'status' => $team->status,
        ];
    }

    private function audit(Incident $incident, string $action, Request $request, array $data): void
    {
        if (Schema::hasTable('incident_dispatch_audits')) {
            IncidentDispatchAudit::create([
                'incident_id' => $incident->incident_id,
                'actor_user_id' => Auth::id(),
                'action' => $action,
                ...$data,
            ]);
        }
        UserActivityLogger::log('command dispatch '.$action, (int) Auth::id(), $request);
    }
}
