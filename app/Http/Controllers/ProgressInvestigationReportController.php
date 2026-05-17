<?php

namespace App\Http\Controllers;

use App\Models\ProgressInvestigationReport;
use App\Models\SpotInvestigationReport;
use App\Models\FireOrigin;
use App\Models\StationOfficer;
use App\Models\User;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;

class ProgressInvestigationReportController extends Controller
{
    public function index()
    {
        $reports = ProgressInvestigationReport::with('spotInvestigation.incident', 'fireOrigin', 'investigator')
            ->paginate(15);
        return view('admin.investigations.progress-reports.index', compact('reports'));
    }

    public function create()
    {
        $spotReports = SpotInvestigationReport::doesntHave('progressInvestigation')->get();
        $fireOrigins = FireOrigin::all();
        $users = User::all();
        
        return view('admin.investigations.progress-reports.create', compact('spotReports', 'fireOrigins', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'spot_investigation_id' => 'required|exists:spot_investigation_report,spot_investigation_report_id|unique:progress_investigation_report,spot_investigation_id',
            'fire_origin_id' => 'nullable|exists:fire_origin,fire_origin_id',
            'responsible_arson' => 'nullable|string|max:255',
            'fire_cause' => 'required|string',
            'station_officer_id' => 'required|exists:station_officers,station_officer_id',
        ]);

        $data = ProgressInvestigationReport::create($validated);
        UserActivityLogger::log('created progress rpt', auth()->user()?->user_id, $request);

        return response()->json(['success'=> true, 'data'=> $data]);
    }
     public function getAvailableSpotInvestigations()
    {
        $spotInvestigations = SpotInvestigationReport::with('incident')
            ->whereDoesntHave('progressInvestigation')
            ->get();
        
        return response()->json($spotInvestigations);
    }
    public function storeProgress(Request $request)
{
    $validated = $request->validate([
        'spot_investigation_id' => 'required|exists:spot_investigation_report,spot_investigation_report_id|unique:progress_investigation_report,spot_investigation_id',
        'station_officer_id' => 'required|exists:station_officers,station_officer_id',
        'fire_origin_id' => 'required|string|max:255',
        'responsible_arson' => 'nullable|string|max:255',
        'fire_cause' => 'required|string',
    ]);

    $progress = ProgressInvestigationReport::create($validated);
    return response()->json(['success' => true, 'data' => $progress]);
}

    public function show(ProgressInvestigationReport $progressInvestigationReport)
    {
        $progressInvestigationReport->load('spotInvestigation.incident', 'fireOrigin', 'investigator', 'finalInvestigation');
        return view('admin.investigations.progress-reports.show', compact('progressInvestigationReport'));
    }

    public function edit(ProgressInvestigationReport $progressInvestigationReport)
    {
        $spotReports = SpotInvestigationReport::all();
        $fireOrigins = FireOrigin::all();
        $users = User::all();
        
        return view('admin.investigations.progress-reports.edit', compact('progressInvestigationReport', 'spotReports', 'fireOrigins', 'users'));
    }

    public function update(Request $request, string $id)
    {
        $progress = ProgressInvestigationReport::find($id);
          if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        $validated = $request->validate([
            
            'fire_origin_id' => 'nullable|exists:fire_origin,fire_origin_id',
            'responsible_arson' => 'nullable|string|max:255',
            'fire_cause' => 'required|string',
        ]);

        $progress->update($validated);
        UserActivityLogger::log('updated progress rpt', auth()->user()?->user_id, $request);
        return response()->json(['success'=>true]);
    }

    public function destroy(ProgressInvestigationReport $progressInvestigationReport)
    {
        if ($progressInvestigationReport->finalInvestigation) {
            return back()->with('error', 'Cannot delete progress report with existing final investigation.');
        }

        $progressInvestigationReport->delete();
        UserActivityLogger::log('deleted progress rpt', auth()->user()?->user_id, request());

        return redirect()->route('progress-investigation-reports.index')
            ->with('success', 'Progress investigation report deleted successfully.');
    }
}
