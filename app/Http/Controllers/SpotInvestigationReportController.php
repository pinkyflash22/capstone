<?php

namespace App\Http\Controllers;

use App\Models\SpotInvestigationReport;
use App\Models\Incident;
use App\Models\Weather;
use App\Models\StationOfficer;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;

class SpotInvestigationReportController extends Controller
{
    public function index()
    {
        $reports = SpotInvestigationReport::with('incident', 'investigator.user', 'progressInvestigation')
            ->paginate(15);
        return view('admin.investigations.spot-reports.index', compact('reports'));
    }

    public function create()
    {
        $incidents = Incident::doesntHave('spotInvestigationReport')->get();
        $users = StationOfficer::with('user')->get();
        $weatherReports = Weather::all();
        
        return view('admin.investigations.spot-reports.create', compact('incidents', 'users', 'weatherReports'));
    }

//     public function storeSpot(Request $request)
// {
//     $validated = $request->validate([
//         'investigator' => 'required|string',
//         'dead' => 'required|integer|min:0',
//         'injured' => 'required|integer|min:0',
//         'owner' => 'required|string',
//         'fire_started' => 'required|date',
//         'fire_out' => 'required|date',
//         'estimated_damage' => 'required|numeric',
//         'occupancy' => 'required|string',
//         'estimated_area' => 'required|numeric',
//         'incident_id' => 'required|exists:incidents,incident_id',
//     ]);
    
//     // Combine dead and injured into casualty field
//     $validated['casualty'] = $validated['dead'] . ' dead, ' . $validated['injured'] . ' injured';
//     unset($validated['dead'], $validated['injured']);
    
//     $spot = SpotInvestigation::create($validated);
    
//     return response()->json(['success' => true, 'data' => $spot]);
// }

    // Add this method if it doesn't exist
    public function getAvailableSpotInvestigations()
    {
        try {
            $spotInvestigations = SpotInvestigationReport::with(['incident', 'investigator'])
                ->whereDoesntHave('progressInvestigation')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Transform the data to include the fields your JavaScript expects
            $formattedSpots = $spotInvestigations->map(function($spot) {
                return [
                    'spot_investigation_report_id' => $spot->spot_investigation_report_id,
                    'station_officer_id' => $spot->station_officer_id ?? 'N/A',
                    'fire_started' => $spot->fire_started ? $spot->fire_started->format('Y-m-d H:i:s') : 'N/A',
                    'incident_id' => $spot->incident_id,
                ];
            });
            
            return response()->json($formattedSpots);
            
        } catch (\Exception $e) {
            \Log::error('Error in getAvailableSpotInvestigations: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,incident_id|unique:spot_investigation_report,incident_id',
            'station_officer_id' => 'required|exists:station_officers,station_officer_id',
            'dead' => 'nullable|numeric|min:0',
            'injured' => 'nullable|numeric|min:0',
            'owner' => 'nullable|string|max:255',
            'fire_started' => 'nullable|date',
            'fire_out' => 'nullable|date|after_or_equal:fire_started',
            'estimated_damage' => 'required|numeric|min:0',
            'occupancy' => 'required|in:residential,commercial,industrial',
            'estimated_area' => 'required|numeric|min:0',
        ]);

       $response = SpotInvestigationReport::create($validated);
        UserActivityLogger::log('created spot report', auth()->user()?->user_id, $request);
        return response()->json(['success'=>true, 'data'=>$response]);
    }

    public function show(SpotInvestigationReport $spotInvestigationReport)
    {
        $spotInvestigationReport->load('incident', 'investigator.user', 'progressInvestigation');
        return view('admin.investigations.spot-reports.show', compact('spotInvestigationReport'));
    }

    public function edit(SpotInvestigationReport $spotInvestigationReport)
    {
        $incidents = Incident::all();
        $users = StationOfficer::with('user')->get();
        $weatherReports = Weather::all();
        
        return view('admin.investigations.spot-reports.edit', compact('spotInvestigationReport', 'incidents', 'users', 'weatherReports'));
    }

    public function update(Request $request, string $id)
    {
         $spot = SpotInvestigationReport::find($id);
          if (!$spot) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        $validated = $request->validate([
            'dead' => 'required|numeric|min:0',
            'injured' => 'required|numeric|min:0',
            'owner' => 'nullable|string|max:255',
            'fire_started' => 'nullable|date',
            'estimated_damage' => 'required|numeric|min:0',
            'occupancy' => 'required|in:residential,commercial,industrial',
            'estimated_area' => 'required|numeric|min:0',         
        ]);

          $spot->update($validated);
        UserActivityLogger::log('updated spot report', auth()->user()?->user_id, $request);
        return response()->json(['success'=>true]);
    }

    public function destroy(string $id)
    {
         $spot = SpotInvestigationReport::find($id);

        if (!$spot) {
            return response()->json(['success' => false, 'message' => 'not found'], 404);

        }

        $spot->delete();
        UserActivityLogger::log('deleted spot report', auth()->user()?->user_id, request());
        return response()->json(['success'=>true]);
    }
}
