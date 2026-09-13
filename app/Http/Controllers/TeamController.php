<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\Station;
use App\Models\StationOfficer;

use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(string $id, string $teamId) //padal natin hnd team id ung teamleader id
    {
        //hanapin sa station officers!


        $officer = StationOfficer::where('user_id', $id)->first(); //hanapin sa station officers!
        abort_unless($officer, 404);
        $stationExact = (int) $officer->station_id; //kunin ung station id ng officer
        $this->assertStationAccess($stationExact);

        $teamOfficers = Team::findOrFail($teamId);
        $this->assertStationAccess((int) ($teamOfficers->station_id ?: $teamOfficers->teamLeader?->station_id));

        $assignedOfficer = StationOfficer::where('station_id', $stationExact)->get(); 
        // Get all teams with their relations
        $teams = Team::with('station','commander', 'teamLeader')->where('station_id', $stationExact)->get();

        $allofficers = StationOfficer::with('user')->where('station_id', $stationExact)->get(); //allofficers na same station para mafetch
        // Get available teams only
        $availableTeams = Team::with('station', 'commander', 'teamLeader')
            ->where('station_id', $stationExact)
            ->where('status', 'available')
            ->get();
           $allTeam = Team::with('commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio')->where('station_id', $stationExact)->get();
            $stationOfficers = StationOfficer::with('user','station')->where('station_id', $stationExact)->get();
        // Return in a consistent format
        return response()->json([
            'success' => true,
            'data' => $availableTeams,  // All available teams
            'all_teams' => $teams,      // All teams (optional)
            'count' => $availableTeams->count(),
            'allTeam'=> $allTeam,
            'stationOfficers' => $stationOfficers,
            'allOfficers' => $allofficers,
            'toedit' => $teamOfficers,
        ]);

        
    }
    public function fetchTeam(){
        $stationId = $this->managedStationId();
        abort_unless($stationId || $this->isProvincialAdmin(), 403);
        $allTeam = Team::with('teamLeader')
            ->when(! $this->isProvincialAdmin(), fn ($query) => $query->where('station_id', $stationId))
            ->get();
    return response()->json([
        'success' => true,
        'data' => $allTeam
    ]);
    }
    public function create()
    {
        $stations = Station::all();
        $firefighters = User::whereHas('role', function($query) {
            $query->where('role', 'like', '%firefighter%');
        })->get();
        
        return view('admin.incidents.teams.create', compact('stations', 'firefighters'));
    }

      public function updateStatus(Request $request,string $id){
          $team = Team::findOrFail($id);
          $this->assertStationAccess((int) ($team->station_id ?: $team->teamLeader?->station_id));
          $validated = $request->validate([
            'status' => 'required|in:available,responding,off',
        ]);

       $team->status = $validated['status'];
        $team->roster_managed = false;
        $team->active_duty_shift_id = null;
        $team->pending_duty_shift_id = null;
        $team->save();
        $this->clearResourceCache((int) ($team->station_id ?: $team->teamLeader?->station_id));
        UserActivityLogger::log('updated team status', auth()->user()?->user_id, $request);

      return response()->json(['success'=>true, 'data'=>$team]);
    }



    public function store(Request $request)
{
    if (! $this->isProvincialAdmin()) {
        $managedStationId = $this->managedStationId();
        abort_unless($managedStationId, 403, 'Your account is not assigned to a station.');
        $request->merge(['station_id' => $managedStationId]);
    }

    $validated = $request->validate([
        'station_id' => 'required|exists:stations,station_id',
        'commander_driver' => 'required|exists:station_officers,station_officer_id',
        'pumper' => 'required|exists:station_officers,station_officer_id',
        'team_leader' => 'required|exists:station_officers,station_officer_id',
        'firefighter_officer_1' => 'nullable|exists:station_officers,station_officer_id',
        'firefighter_officer_2' => 'nullable|exists:station_officers,station_officer_id',
        'firefighter_officer_3' => 'nullable|exists:station_officers,station_officer_id',
        'rescue_radio' => 'nullable|exists:station_officers,station_officer_id',
    ]);

    $stationId = (int) $validated['station_id'];
    $this->assertStationAccess($stationId);
    $this->validateTeamRoster($validated, $stationId);

    // Auto-set status to 'available'
    $validated['status'] = 'available';

    $team = Team::create($validated);
    $this->clearResourceCache($stationId);
    UserActivityLogger::log('created team', auth()->user()?->user_id, $request);

    return response()->json(['success' => true, 'data' => $team]);
}

    public function show(Team $team)
    {
        $this->assertStationAccess((int) ($team->station_id ?: $team->teamLeader?->station_id));
        $team->load('station', 'commander', 'pumpers', 'teamLeader',
            'officer1', 'officer2', 'officer3', 'rescueRadio', 'incidents');
        return view('admin.incidents.teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $stationId = (int) ($team->station_id ?: $team->teamLeader?->station_id);
        $this->assertStationAccess($stationId);
        $stations = Station::query()->when(! $this->isProvincialAdmin(), fn ($query) => $query->where('station_id', $stationId))->get();
        $firefighters = User::whereHas('role', function($query) {
            $query->where('role', 'like', '%firefighter%');
        })->when(! $this->isProvincialAdmin(), fn ($query) => $query->whereHas('stations', fn ($stations) => $stations->where('stations.station_id', $stationId)))->get();
        
        return view('admin.incidents.teams.edit', compact('team', 'stations', 'firefighters'));
    }

    public function update(Request $request, string $id)
    {
        $team = Team::findOrFail($id);
        $originalStationId = (int) ($team->station_id ?: $team->teamLeader?->station_id);
        $this->assertStationAccess($originalStationId);

        if (! $this->isProvincialAdmin()) {
            $managedStationId = $this->managedStationId();
            abort_unless($managedStationId, 403, 'Your account is not assigned to a station.');
            $request->merge(['station_id' => $managedStationId]);
        }
        
        $validated = $request->validate([
            'commander_driver' => 'sometimes|required|exists:station_officers,station_officer_id',
            'pumper' => 'sometimes|required|exists:station_officers,station_officer_id',
            'team_leader' => 'sometimes|required|exists:station_officers,station_officer_id',
            'firefighter_officer_1' => 'nullable|exists:station_officers,station_officer_id',
            'firefighter_officer_2' => 'nullable|exists:station_officers,station_officer_id',
            'firefighter_officer_3' => 'nullable|exists:station_officers,station_officer_id',
            'rescue_radio' => 'nullable|exists:station_officers,station_officer_id',
            'station_id' => 'sometimes|required|exists:stations,station_id',
            'status' => 'required|in:available,responding,off',
        ]);

        $stationId = (int) ($validated['station_id'] ?? $team->station_id ?? $team->teamLeader?->station_id);
        $this->assertStationAccess($stationId);
        $validated['station_id'] = $stationId;
        $this->validateTeamRoster($validated, $stationId, $team);
        $validated['roster_managed'] = false;
        $validated['active_duty_shift_id'] = null;
        $validated['pending_duty_shift_id'] = null;
        $team->forceFill($validated)->save();
        $this->clearResourceCache($stationId);
        UserActivityLogger::log('updated team', auth()->user()?->user_id, $request);

        return response()->json(['success' => true, 'data' => $team]);
    }

    public function destroy(Team $team)
    {
        $this->assertStationAccess((int) ($team->station_id ?: $team->teamLeader?->station_id));
        if ($team->incidents()->count() > 0) {
            return back()->with('error', 'Cannot delete team with assigned incidents.');
        }

        $team->delete();
        UserActivityLogger::log('deleted team', auth()->user()?->user_id, request());

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }

    private function validateTeamRoster(array $validated, int $stationId, ?Team $team = null): void
    {
        $fields = [
            'team_leader', 'commander_driver', 'pumper',
            'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio',
        ];
        $resolved = [];

        foreach ($fields as $field) {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $team?->{$field};
            if ($value !== null && $value !== '') {
                $resolved[$field] = (int) $value;
            }
        }

        if (count($resolved) !== count(array_unique(array_values($resolved)))) {
            throw ValidationException::withMessages([
                'team' => 'Each station officer can hold only one role in the same team.',
            ]);
        }

        $validOfficerCount = StationOfficer::query()
            ->where('station_id', $stationId)
            ->whereIn('station_officer_id', array_values($resolved))
            ->count();

        if ($validOfficerCount !== count($resolved)) {
            throw ValidationException::withMessages([
                'team' => 'Every selected member must be a station officer assigned to the selected station.',
            ]);
        }

        foreach ($resolved as $field => $officerId) {
            $assignedElsewhere = Team::query()
                ->when($team, fn ($query) => $query->where($team->getKeyName(), '!=', $team->getKey()))
                ->where(function ($query) use ($officerId, $fields) {
                    foreach ($fields as $memberColumn) {
                        $query->orWhere($memberColumn, $officerId);
                    }
                })
                ->exists();

            if ($assignedElsewhere) {
                throw ValidationException::withMessages([
                    $field => 'The selected officer is already assigned to another team.',
                ]);
            }
        }
    }

    private function assertStationAccess(int $stationId): void
    {
        if ($this->isProvincialAdmin()) {
            return;
        }

        $managedStationId = $this->managedStationId();
        abort_unless($managedStationId && (int) $managedStationId === $stationId, 403, 'You can only manage teams assigned to your station.');
    }

    private function isProvincialAdmin(): bool
    {
        return strtolower(trim((string) Auth::user()?->role?->role)) === 'provincial admin';
    }

    private function managedStationId(): ?int
    {
        $stationId = StationOfficer::query()->where('user_id', Auth::user()?->user_id)->value('station_id');

        return $stationId ? (int) $stationId : null;
    }

    private function clearResourceCache(int $stationId): void
    {
        foreach (['v3', 'v4'] as $version) {
            foreach (['equipment', 'officers', 'stations', 'teams', 'users', 'boundaries'] as $section) {
                Cache::forget("resource-management:station:{$stationId}:{$version}:{$section}");
                Cache::forget("resource-management:provincial:{$version}:{$section}");
            }
        }
    }
}
