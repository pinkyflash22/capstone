<?php

namespace App\Http\Controllers;

use App\Models\FinalInvestigationReport;
use App\Models\FullAddress;
use App\Models\ProgressInvestigationReport;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;

class FinalInvestigationReportController extends Controller
{
    public function index()
    {
        $reports = FinalInvestigationReport::with('investigator.user', 'progressInvestigation.spotInvestigation.incident')
            ->paginate(15);
        return view('admin.investigations.final-reports.index', compact('reports'));
    }

    public function create()
    {
        $progressReports = ProgressInvestigationReport::doesntHave('finalInvestigation')->get();
        $addresses = FullAddress::with('barangay.city.province')->get();
        
        return view('admin.investigations.final-reports.create', compact('progressReports', 'addresses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'progress_investigation_id' => 'required|exists:progress_investigation_report,progress_investigation_report_id|unique:final_investigation_report,progress_investigation_id',
            'station_officer_id'=>'required|exists:station_officers,station_officer_id',
            'total_damage' => 'required|numeric|min:0',
            'exact_address' => 'required|string|max: 255'
        ]);

        $data = FinalInvestigationReport::create($validated);
        UserActivityLogger::log('created final report', auth()->user()?->user_id, $request);

        return response()->json(['success'=>true, 'data'=>$data]);
    }

    public function getAvailableProgressInvestigations()
    {
        $progressInvestigations = ProgressInvestigationReport::with('spotInvestigation')
            ->whereDoesntHave('finalInvestigation')
            ->get();
        
        return response()->json($progressInvestigations);
    }

    public function storeFinal(Request $request)
{
    $validated = $request->validate([
        'progress_investigation_id' => 'required|exists:progress_investigation_report,progress_investigation_report_id|unique:final_investigation_report,progress_investigation_id',
        'station_officer_id' => 'required|exists:station_officers,station_officer_id',
        'total_damage' => 'required|numeric|min:0',
        'exact_address' => 'required|string|max:500',
    ]);

    $final = FinalInvestigationReport::create($validated);
    return response()->json(['success' => true, 'data' => $final]);
}

    public function show(FinalInvestigationReport $finalInvestigationReport)
    {
        $finalInvestigationReport->load('investigator.user', 'progressInvestigation.spotInvestigation.incident');
        return view('admin.investigations.final-reports.show', compact('finalInvestigationReport'));
    }

    public function edit(FinalInvestigationReport $finalInvestigationReport)
    {
        $progressReports = ProgressInvestigationReport::all();
        $addresses = FullAddress::with('barangay.city.province')->get();
        
        return view('admin.investigations.final-reports.edit', compact('finalInvestigationReport', 'progressReports', 'addresses'));
    }

    public function update(Request $request, string $id)
    {
        $final = FinalInvestigationReport::find($id);
          if (!$final) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        $validated = $request->validate([
            'exact_address' => 'required|string|max:255',
            'total_damage' => 'required|numeric|min:0',
        ]);

        $final->update($validated);
        UserActivityLogger::log('updated final report', auth()->user()?->user_id, $request);
        return response()->json(['success'=>true]);

        
    }

    public function destroy(FinalInvestigationReport $finalInvestigationReport)
    {
        $finalInvestigationReport->delete();
        UserActivityLogger::log('deleted final report', auth()->user()?->user_id, request());

        return redirect()->route('final-investigation-reports.index')
            ->with('success', 'Final investigation report deleted successfully.');
    }
}
