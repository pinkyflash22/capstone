<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\Station;
use App\Models\StationOfficer;

use App\Support\UserActivityLogger;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'status' => 'required|in:available,responding,off',
        ]);

       $team->status = $validated['status'];
        $team->save();
        UserActivityLogger::log('updated team status', auth()->user()?->user_id, $request);

      return response()->json(['success'=>true, 'data'=>$team]);
    }



    public function store(Request $request)
{
    $validated = $request->validate([
        'commander_driver' => 'nullable|exists:station_officers,station_officer_id',
        'pumper' => 'nullable|exists:station_officers,station_officer_id',
        'team_leader' => 'required|exists:station_officers,station_officer_id',
        'firefighter_officer_1' => 'nullable|exists:station_officers,station_officer_id',
        'firefighter_officer_2' => 'nullable|exists:station_officers,station_officer_id',
        'firefighter_officer_3' => 'nullable|exists:station_officers,station_officer_id',
        'rescue_radio' => 'nullable|exists:station_officers,station_officer_id',
        // Remove status from validation and set default in controller
    ]);

    // Auto-set status to 'available'
    $validated['status'] = 'available';

    $team = Team::create($validated);
    UserActivityLogger::log('created team', auth()->user()?->user_id, $request);

    return response()->json(['success' => true, 'data' => $team]);
}

    public function show(Team $team)
    {
        $team->load('station', 'commander', 'pumper', 'teamLeader', 
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
            'commander_driver' => 'nullable|exists:station_officers,station_officer_id',
            'pumper' => 'nullable|exists:station_officers,station_officer_id',
            'team_leader' => 'nullable|exists:station_officers,station_officer_id',
            'firefighter_officer_1' => 'nullable|exists:station_officers,station_officer_id',
            'firefighter_officer_2' => 'nullable|exists:station_officers,station_officer_id',
            'firefighter_officer_3' => 'nullable|exists:station_officers,station_officer_id',
            'rescue_radio' => 'nullable|exists:station_officers,station_officer_id',
            'station_id' => 'required|exists:stations,station_id',
            'status' => 'required|in:available,responding,off',
        ]);

        $team->update($validated);
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
}
