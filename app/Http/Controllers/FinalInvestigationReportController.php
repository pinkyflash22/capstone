<?php

namespace App\Http\Controllers;

use App\Models\FinalInvestigationReport;
use App\Models\FullAddress;
use App\Models\ProgressInvestigationReport;
use App\Services\FinalNarrativeDraftService;
use App\Services\InvestigationReportImageService;
use App\Support\HistoricalFireIncidentSynchronizer;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinalInvestigationReportController extends Controller
{
    private const DEFAULT_SUBSTANTIATING_DOCUMENTS = [
        'Spot Investigation Report',
        'Progress Investigation Report',
    ];

    public function index()
    {
        $reports = FinalInvestigationReport::with('investigator.user', 'progressInvestigation.spotInvestigation.incident')
            ->paginate(15);
        return view('admin.investigations.final-reports.index', compact('reports'));
    }

    public function create()
    {
        $progressReports = ProgressInvestigationReport::eligibleForFinal()
            ->doesntHave('finalInvestigation')
            ->when(Schema::hasColumn('progress_investigation_report', 'review_status'), fn ($query) => $query->where('review_status', 'approved'))
            ->get();
        $addresses = FullAddress::with('barangay.city.province')->get();
        
        return view('admin.investigations.final-reports.create', compact('progressReports', 'addresses'));
    }

    public function store(Request $request, InvestigationReportImageService $imageService)
    {
        if ($cached = $imageService->cachedSubmissionResponse($request, 'final:create')) {
            return response()->json($cached);
        }

        $validated = $request->validate([
            'progress_investigation_id' => 'required|exists:progress_investigation_report,progress_investigation_report_id|unique:final_investigation_report,progress_investigation_id',
            'station_officer_id'=>'required|exists:station_officers,station_officer_id',
            'total_damage' => 'required|numeric|min:0',
            'exact_address' => 'required|string|max: 255',
            'report_completed_at' => 'nullable|date',
            'memorandum_for' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'alarm_at' => 'nullable|date',
            'establishment_burned' => 'nullable|string|max:255',
            'fire_victims' => 'nullable|string',
            'origin_of_fire' => 'nullable|string',
            'cause_of_fire' => 'nullable|string',
            'substantiating_documents' => 'nullable|array',
            'substantiating_documents.*' => 'string|max:255',
            'facts_of_case' => 'nullable|string',
            'discussion' => 'nullable|string',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);

        $progressReport = $this->resolveLinkedProgressReport((int) $validated['progress_investigation_id']);
        $validated = $this->mergeDerivedDefaults($validated, $progressReport);
        $data = FinalInvestigationReport::create($validated);
        $imageService->store($request, $data, 'final');
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $data->progressInvestigation?->spotInvestigation?->incident_id);
        UserActivityLogger::log('created final report', auth()->user()?->user_id, $request);

        $payload = [
            'success'=>true,
            'data'=>InvestigationReportImageService::responsePayload($data->load(['progressInvestigation.fireOrigin', 'notedBy.user', 'approvedBy.user'])),
        ];

        return response()->json($imageService->rememberSubmissionResponse($request, 'final:create', $payload));
    }

    public function getAvailableProgressInvestigations()
    {
        $progressInvestigations = ProgressInvestigationReport::with('spotInvestigation')
            ->eligibleForFinal()
            ->whereDoesntHave('finalInvestigation')
            ->when(Schema::hasColumn('progress_investigation_report', 'review_status'), fn ($query) => $query->where('review_status', 'approved'))
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

    $this->resolveLinkedProgressReport((int) $validated['progress_investigation_id']);
    $final = FinalInvestigationReport::create($validated);
    HistoricalFireIncidentSynchronizer::syncForIncidentId((int) $final->progressInvestigation?->spotInvestigation?->incident_id);
    return response()->json(['success' => true, 'data' => $final]);
}

    public function show(FinalInvestigationReport $finalInvestigationReport)
    {
        $finalInvestigationReport->load('investigator.user', 'progressInvestigation.spotInvestigation.incident');
        return view('admin.investigations.final-reports.show', compact('finalInvestigationReport'));
    }

    public function edit(FinalInvestigationReport $finalInvestigationReport)
    {
        $progressReports = ProgressInvestigationReport::eligibleForFinal()
            ->when(Schema::hasColumn('progress_investigation_report', 'review_status'), fn ($query) => $query->where('review_status', 'approved'))
            ->get();
        $addresses = FullAddress::with('barangay.city.province')->get();
        
        return view('admin.investigations.final-reports.edit', compact('finalInvestigationReport', 'progressReports', 'addresses'));
    }

    public function update(Request $request, string $id, InvestigationReportImageService $imageService)
    {
        $final = FinalInvestigationReport::find($id);
        if (!$final) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        if (! $this->canReviseReport($final)) {
            return response()->json([
                'success' => false,
                'message' => 'This report is already submitted for review and cannot be edited unless it is rejected for revision.',
            ], 403);
        }
        if ($cached = $imageService->cachedSubmissionResponse($request, "final:update:{$id}")) {
            return response()->json($cached);
        }
        $validated = $request->validate([
            'report_completed_at' => 'nullable|date',
            'memorandum_for' => 'nullable|string|max:255',
            'exact_address' => 'required|string|max:255',
            'office_address' => 'nullable|string',
            'alarm_at' => 'nullable|date',
            'establishment_burned' => 'nullable|string|max:255',
            'fire_victims' => 'nullable|string',
            'origin_of_fire' => 'nullable|string',
            'cause_of_fire' => 'nullable|string',
            'substantiating_documents' => 'nullable|array',
            'substantiating_documents.*' => 'string|max:255',
            'facts_of_case' => 'nullable|string',
            'discussion' => 'nullable|string',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'total_damage' => 'required|numeric|min:0',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);
        $validated = $this->markResubmittedAfterRevision($validated);
        $validated = $this->mergeDerivedDefaults($validated, $final->progressInvestigation);
        $final->update($validated);
        $imageService->store($request, $final, 'final');
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $final->progressInvestigation?->spotInvestigation?->incident_id);
        UserActivityLogger::log('updated final report', auth()->user()?->user_id, $request);
        $payload = [
            'success'=>true,
            'data' => InvestigationReportImageService::responsePayload($final->fresh()->load(['progressInvestigation.fireOrigin', 'notedBy.user', 'approvedBy.user'])),
        ];

        return response()->json($imageService->rememberSubmissionResponse($request, "final:update:{$id}", $payload));

        
    }

    public function draftNarrative(Request $request, FinalNarrativeDraftService $draftService)
    {
        $validated = $request->validate([
            'progress_investigation_id' => 'nullable|exists:progress_investigation_report,progress_investigation_report_id',
            'exact_address' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'alarm_at' => 'nullable|date',
            'establishment_burned' => 'nullable|string|max:255',
            'fire_victims' => 'nullable|string',
            'origin_of_fire' => 'nullable|string',
            'cause_of_fire' => 'nullable|string',
            'total_damage' => 'nullable',
            'substantiating_documents' => 'nullable|array',
            'substantiating_documents.*' => 'string|max:255',
            'facts_of_case' => 'nullable|string',
            'discussion' => 'nullable|string',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        if (! empty($validated['progress_investigation_id'])) {
            $progress = $this->resolveLinkedProgressReport((int) $validated['progress_investigation_id']);
            $spot = $progress->spotInvestigation;
            $incident = $spot?->incident;

            $validated = $this->mergeDerivedDefaults($validated, $progress);
            $validated['progress_fire_origin'] = $progress->fireOrigin?->origin;
            $validated['progress_fire_cause'] = $progress->fire_cause;
            $validated['progress_facts_of_case'] = $progress->facts_of_case;
            $validated['progress_disposition'] = $progress->disposition;
            $validated['responsible_arson'] = $progress->responsible_arson;
            $validated['incident_location'] = $incident?->marker?->title;
            $validated['place_of_occurrence'] = $spot?->place_of_occurrence;
            $validated['spot_description'] = $spot?->description;
            $validated['spot_details_of_investigation'] = $spot?->details_of_investigation;
            $validated['spot_disposition'] = $spot?->disposition;
            $validated['fire_started'] = $spot?->fire_started
                ? Carbon::parse($spot->fire_started)->format('F j, Y g:i A')
                : null;
            $validated['fire_out'] = $spot?->fire_out
                ? Carbon::parse($spot->fire_out)->format('F j, Y g:i A')
                : null;
            $validated['owner'] = $spot?->owner;
            $validated['occupant'] = $spot?->occupant;
            $validated['spot_establishment_name'] = $spot?->establishment_name;
            $validated['occupancy'] = $spot?->occupancy;
            $validated['estimated_damage'] = $spot?->estimated_damage;
            $validated['fatality_details'] = $spot?->fatality_details;
            $validated['dead'] = $spot?->dead;
            $validated['injured'] = $spot?->injured;
        }

        $hasUsefulInput = collect($validated)
            ->flatten()
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isNotEmpty();

        if (! $hasUsefulInput) {
            return response()->json([
                'success' => false,
                'message' => 'Add some final investigation findings first before generating the narrative sections.',
            ], 422);
        }

        $sections = $draftService->draftFinalNarrativeSections($validated);

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    public function destroy(FinalInvestigationReport $finalInvestigationReport)
    {
        if (! $this->canReviseReport($finalInvestigationReport)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This report is already submitted for review and cannot be deleted unless it is rejected for revision.',
                ], 403);
            }

            return back()->with('error', 'This report is already submitted for review and cannot be deleted unless it is rejected for revision.');
        }

        $incidentId = (int) $finalInvestigationReport->progressInvestigation?->spotInvestigation?->incident_id;
        InvestigationReportImageService::deleteForReport($finalInvestigationReport);
        $finalInvestigationReport->delete();
        HistoricalFireIncidentSynchronizer::syncForIncidentId($incidentId);
        UserActivityLogger::log('deleted final report', auth()->user()?->user_id, request());

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('final-investigation-reports.index')
            ->with('success', 'Final investigation report deleted successfully.');
    }

    private function resolveLinkedProgressReport(int $progressInvestigationId): ProgressInvestigationReport
    {
        $progressReport = ProgressInvestigationReport::findOrFail($progressInvestigationId);

        if (! $progressReport->isEligibleForFinal()) {
            throw ValidationException::withMessages([
                'progress_investigation_id' => 'Complete the 2nd Progress Report before creating the Final Report.',
            ]);
        }

        if (Schema::hasColumn('progress_investigation_report', 'review_status') && ($progressReport->review_status ?? 'submitted') !== 'approved') {
            throw ValidationException::withMessages([
                'progress_investigation_id' => 'The Progress Investigation Report must be approved before creating the Final Report.',
            ]);
        }

        return $progressReport->load([
            'spotInvestigation.incident.marker',
            'spotInvestigation.incident.barangay.city.province',
            'spotInvestigation.investigator.station.address.barangay.city.province',
            'fireOrigin',
        ]);
    }

    private function mergeDerivedDefaults(array $validated, ProgressInvestigationReport $progressReport): array
    {
        $spot = $progressReport->spotInvestigation;
        $incident = $spot?->incident;
        $station = $progressReport->investigator?->station;
        $stationBarangay = $station?->address?->barangay;
        $stationCity = $stationBarangay?->city;
        $stationProvince = $stationCity?->province;

        $validated['substantiating_documents'] = array_values(array_filter(
            $validated['substantiating_documents'] ?? self::DEFAULT_SUBSTANTIATING_DOCUMENTS
        ));

        $validated['report_completed_at'] = $validated['report_completed_at'] ?? now();
        $validated['office_address'] = $validated['office_address'] ?: collect([
            $station?->station_name,
            $station?->address?->street,
            $stationBarangay?->barangay_name,
            $stationCity?->city_name,
            $stationProvince?->province_name,
        ])->filter()->join(', ');
        $validated['alarm_at'] = $validated['alarm_at'] ?: ($spot?->fire_started ?? $incident?->created_at);
        $validated['establishment_burned'] = $validated['establishment_burned'] ?: collect([
            $spot?->establishment_name,
            $spot?->occupancy ? ucfirst((string) $spot->occupancy) . ' occupancy' : null,
        ])->filter()->join(' - ');
        $validated['fire_victims'] = $validated['fire_victims'] ?: ($spot?->fatality_details ?: $this->buildVictimSummary($spot?->dead, $spot?->injured));
        $validated['origin_of_fire'] = $validated['origin_of_fire'] ?: $progressReport->fireOrigin?->origin;
        $validated['cause_of_fire'] = $validated['cause_of_fire'] ?: $progressReport->fire_cause;

        return $validated;
    }

    private function buildVictimSummary($dead, $injured): string
    {
        $dead = (int) ($dead ?? 0);
        $injured = (int) ($injured ?? 0);

        if ($dead === 0 && $injured === 0) {
            return '';
        }

        return "Dead: {$dead}; Injured: {$injured}";
    }

    private function canReviseReport(FinalInvestigationReport $report): bool
    {
        if (! Schema::hasColumn('final_investigation_report', 'review_status')) {
            return true;
        }

        if (strtolower((string) (auth()->user()?->role?->role ?? '')) !== 'firefighter') {
            return true;
        }

        return ($report->review_status ?? 'submitted') === 'rejected';
    }

    private function markResubmittedAfterRevision(array $validated): array
    {
        if (Schema::hasColumn('final_investigation_report', 'review_status')) {
            $validated['review_status'] = 'submitted';
            $validated['review_comments'] = null;
            $validated['reviewed_by_user_id'] = null;
            $validated['reviewed_at'] = null;
        }

        return $validated;
    }
}
