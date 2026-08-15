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
        $stationExact = $officer -> station_id; //kunin ung station id ng officer

        $teamOfficers = Team::findOrFail($teamId);

        $assignedOfficer = StationOfficer::where('station_id', $stationExact)->get(); 
        // Get all teams with their relations
        $teams = Team::with('station','commander', 'teamLeader')->get();

        $allofficers = StationOfficer::with('user')->where('station_id', $stationExact)->get(); //allofficers na same station para mafetch
        // Get available teams only
        $availableTeams = Team::with('station', 'commander', 'teamLeader')
            ->where('status', 'available')
            ->get();
           $allTeam = Team::with('commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio')->get();
            $stationOfficers = StationOfficer::with('user','station')->get();
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
            $allTeam = Team::with('teamLeader')->get();
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
        $team->save();
        $this->clearResourceCache((int) ($team->station_id ?: $team->teamLeader?->station_id));
        UserActivityLogger::log('updated team status', auth()->user()?->user_id, $request);

      return response()->json(['success'=>true, 'data'=>$team]);
    }



    public function store(Request $request)
{
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
        $team->load('station', 'commander', 'pumpers', 'teamLeader',
            'officer1', 'officer2', 'officer3', 'rescueRadio', 'incidents');
        return view('admin.incidents.teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $stations = Station::all();
        $firefighters = User::whereHas('role', function($query) {
            $query->where('role', 'like', '%firefighter%');
        })->get();
        
        return view('admin.incidents.teams.edit', compact('team', 'stations', 'firefighters'));
    }

    public function update(Request $request, string $id)
    {
        $team = Team::findOrFail($id);
        
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
        $team->update($validated);
        $this->clearResourceCache($stationId);
        UserActivityLogger::log('updated team', auth()->user()?->user_id, $request);

        return response()->json(['success' => true, 'data' => $team]);
    }

    public function destroy(Team $team)
    {
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
        $role = strtolower(trim((string) Auth::user()?->role?->role));
        if ($role === 'provincial admin') {
            return;
        }

        $managedStationId = StationOfficer::query()->where('user_id', Auth::id())->value('station_id');
        abort_unless($managedStationId && (int) $managedStationId === $stationId, 403, 'You can only manage teams assigned to your station.');
    }

    private function clearResourceCache(int $stationId): void
    {
        foreach (['equipment', 'officers', 'stations', 'teams', 'users', 'boundaries'] as $section) {
            Cache::forget("resource-management:station:{$stationId}:v3:{$section}");
            Cache::forget("resource-management:provincial:v3:{$section}");
        }
    }
}
