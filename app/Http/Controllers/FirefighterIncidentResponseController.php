<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentResponseUnit;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Services\DutyRosterService;
use App\Services\IncidentResourceDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FirefighterIncidentResponseController extends Controller
{
    public function respond(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $unit = DB::transaction(function () use ($incident, $resources) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            $officer = $this->currentOfficer();
            $team = $this->respondingTeamForOfficer($locked, $officer);
            abort_unless($team, 403, 'You are not assigned to this incident response.');
            abort_if(in_array(strtolower((string) $locked->status), ['controlled', 'closed'], true), 409, 'This incident is no longer accepting responders.');

            $existing = IncidentResponseUnit::query()->where('incident_id', $locked->incident_id)->where('team_id', $team->team_id)
                ->whereIn('status', ['reserved', 'en_route', 'on_scene'])->first();
            if ($existing) return $existing;

            if ($resources->incidentRequiresFiretruck($locked)) {
                $reserved = $resources->reservePrimary($locked, $team, (int) $officer->station_id, null, Auth::id());
                abort_unless($reserved, 409, 'Your station has no available firetruck. This team cannot respond until an apparatus is available.');
                $existing = $reserved;
            } else {
                $resources->dispatchTeam($team);
            }

            $locked->forceFill(['status' => 'Responding'])->save();
            return $existing;
        });

        return response()->json([
            'success' => true,
            'message' => $unit ? 'Response confirmed and a firetruck has been reserved.' : 'Response confirmed.',
            'unit' => $unit ? $resources->serializeUnit($unit) : null,
        ]);
    }

    public function requestBackup(Incident $incident, IncidentResourceDispatchService $resources): JsonResponse
    {
        $unit = $resources->requestBackup($incident, (int) Auth::id());
        $source = $unit->dispatch_source === 'same_station' ? 'your station' : ($unit->station?->station_name ?: 'the nearest available station');
        return response()->json([
            'success' => true,
            'message' => "Backup run {$unit->run_number} dispatched from {$source}.",
            'unit' => $resources->serializeUnit($unit),
        ], 201);
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
