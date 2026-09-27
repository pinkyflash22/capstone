<?php

namespace App\Http\Controllers;

use App\Models\ProgressInvestigationReport;
use App\Models\SpotInvestigationReport;
use App\Models\FireOrigin;
use App\Models\StationOfficer;
use App\Models\User;
use App\Models\Incident;
use App\Services\ProgressNarrativeDraftService;
use App\Services\InvestigationReportImageService;
use App\Support\HistoricalFireIncidentSynchronizer;
use App\Support\UserActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
        $spotReports = SpotInvestigationReport::doesntHave('progressInvestigations')
            ->when($this->reviewWorkflowEnabled('spot_investigation_report'), fn ($query) => $query->where('review_status', 'approved'))
            ->get();
        $fireOrigins = FireOrigin::all();
        $users = User::all();
        
        return view('admin.investigations.progress-reports.create', compact('spotReports', 'fireOrigins', 'users'));
    }

    public function store(Request $request, InvestigationReportImageService $imageService)
    {
        if ($cached = $imageService->cachedSubmissionResponse($request, 'progress:create')) {
            return response()->json($cached);
        }

        $validated = $request->validate([
            'spot_investigation_id' => 'required|exists:spot_investigation_report,spot_investigation_report_id',
            'report_stage' => 'sometimes|string|in:single,first,second',
            'parent_progress_investigation_id' => 'nullable|exists:progress_investigation_report,progress_investigation_report_id',
            'fire_origin_id' => 'nullable',
            'fire_origin_other' => 'nullable|string|max:255',
            'responsible_arson' => 'nullable|string|max:255',
            'fire_cause' => 'required|string',
            'station_officer_id' => 'required|exists:station_officers,station_officer_id',
            'report_completed_at' => 'nullable|date',
            'memorandum_for' => 'nullable|string|max:255',
            'mission_order_number' => 'nullable|string|max:255',
            'mission_order_date' => 'nullable|date',
            'facts_of_case' => 'nullable|string',
            'disposition' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);

        $validated['fire_origin_id'] = $this->resolveFireOriginId(
            $validated['fire_origin_id'] ?? null,
            $validated['fire_origin_other'] ?? null
        );
        unset($validated['fire_origin_other']);

        $data = DB::transaction(function () use ($validated) {
            $validated = $this->validateAndPrepareSequence($validated);

            return ProgressInvestigationReport::create($validated);
        });
        $imageService->store($request, $data, 'progress');
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $data->spotInvestigation?->incident_id);
        UserActivityLogger::log('created progress rpt', auth()->user()?->user_id, $request);

        $payload = [
            'success'=> true,
            'data'=> InvestigationReportImageService::responsePayload($data->load(['fireOrigin', 'notedBy.user', 'approvedBy.user'])),
        ];

        return response()->json($imageService->rememberSubmissionResponse($request, 'progress:create', $payload));
    }
     public function getAvailableSpotInvestigations()
    {
        $spotInvestigations = SpotInvestigationReport::with('incident')
            ->whereDoesntHave('progressInvestigations')
            ->when($this->reviewWorkflowEnabled('spot_investigation_report'), fn ($query) => $query->where('review_status', 'approved'))
            ->get();
        
        return response()->json($spotInvestigations);
    }
    public function storeProgress(Request $request)
{
    $validated = $request->validate([
        'spot_investigation_id' => 'required|exists:spot_investigation_report,spot_investigation_report_id',
        'report_stage' => 'sometimes|string|in:single,first,second',
        'parent_progress_investigation_id' => 'nullable|exists:progress_investigation_report,progress_investigation_report_id',
        'station_officer_id' => 'required|exists:station_officers,station_officer_id',
        'fire_origin_id' => 'required|string|max:255',
        'responsible_arson' => 'nullable|string|max:255',
        'fire_cause' => 'required|string',
    ]);

    $progress = DB::transaction(function () use ($validated) {
        return ProgressInvestigationReport::create($this->validateAndPrepareSequence($validated));
    });
    HistoricalFireIncidentSynchronizer::syncForIncidentId((int) $progress->spotInvestigation?->incident_id);
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

    public function update(Request $request, string $id, InvestigationReportImageService $imageService)
    {
        $progress = ProgressInvestigationReport::find($id);
        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        if (! $this->canReviseReport($progress)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the personnel who created this report can edit it while it is not approved.',
            ], 403);
        }
        if ($cached = $imageService->cachedSubmissionResponse($request, "progress:update:{$id}")) {
            return response()->json($cached);
        }
        $validated = $request->validate([
            'fire_origin_id' => 'nullable',
            'fire_origin_other' => 'nullable|string|max:255',
            'responsible_arson' => 'nullable|string|max:255',
            'fire_cause' => 'required|string',
            'report_completed_at' => 'nullable|date',
            'memorandum_for' => 'nullable|string|max:255',
            'mission_order_number' => 'nullable|string|max:255',
            'mission_order_date' => 'nullable|date',
            'facts_of_case' => 'nullable|string',
            'disposition' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);
        $validated = $this->markResubmittedAfterRevision($validated);
        $validated['fire_origin_id'] = $this->resolveFireOriginId(
            $validated['fire_origin_id'] ?? null,
            $validated['fire_origin_other'] ?? null
        );
        unset($validated['fire_origin_other']);

        $progress->update($validated);
        $imageService->store($request, $progress, 'progress');
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $progress->spotInvestigation?->incident_id);
        UserActivityLogger::log('updated progress rpt', auth()->user()?->user_id, $request);
        $payload = ['success'=>true, 'data' => InvestigationReportImageService::responsePayload($progress->fresh()->load('fireOrigin'))];

        return response()->json($imageService->rememberSubmissionResponse($request, "progress:update:{$id}", $payload));
    }

    public function draftNarrative(Request $request, ProgressNarrativeDraftService $draftService)
    {
        $validated = $request->validate([
            'spot_investigation_id' => 'nullable|exists:spot_investigation_report,spot_investigation_report_id',
            'fire_origin' => 'nullable|string|max:255',
            'fire_cause' => 'nullable|string',
            'responsible_arson' => 'nullable|string|max:255',
            'report_completed_at' => 'nullable|date',
            'memorandum_for' => 'nullable|string|max:255',
            'mission_order_number' => 'nullable|string|max:255',
            'mission_order_date' => 'nullable|date',
            'facts_of_case' => 'nullable|string',
            'disposition' => 'nullable|string',
        ]);

        if (! empty($validated['spot_investigation_id'])) {
            $spotReport = SpotInvestigationReport::with(['incident.context', 'incident.marker', 'incident.historicalRecords'])
                ->find($validated['spot_investigation_id']);

            if ($spotReport) {
                $incident = $spotReport->incident;
                $historical = $incident?->historicalRecords
                    ?->sortByDesc(fn ($record) => $record->incident_date?->format('Y-m-d') . ' ' . ($record->incident_time ?? ''))
                    ?->first();

                $validated['incident_location'] = $incident?->marker?->title;
                $validated['place_of_occurrence'] = $spotReport->place_of_occurrence;
                $validated['spot_description'] = $spotReport->description;
                $validated['spot_details_of_investigation'] = $spotReport->details_of_investigation;
                $validated['spot_disposition'] = $spotReport->disposition;
                $validated['fire_started'] = $spotReport->fire_started
                    ? Carbon::parse($spotReport->fire_started)->format('F j, Y g:i A')
                    : null;
                $validated['fire_out'] = $spotReport->fire_out
                    ? Carbon::parse($spotReport->fire_out)->format('F j, Y g:i A')
                    : null;
                $validated['owner'] = $spotReport->owner;
                $validated['occupant'] = $spotReport->occupant;
                $validated['establishment_name'] = $spotReport->establishment_name;
                $validated['involved_structure'] = $spotReport->involved_structure;
                $validated['estimated_damage'] = $spotReport->estimated_damage;
                $validated['estimated_area'] = $spotReport->estimated_area;
                $validated['affected_establishments_count'] = $spotReport->affected_establishments_count;
                $validated['dead'] = $spotReport->dead;
                $validated['injured'] = $spotReport->injured;
                $validated['fatality_details'] = $spotReport->fatality_details;

                if (empty($validated['fire_origin']) && $incident instanceof Incident) {
                    $validated['fire_origin'] = $historical?->fire_origin;
                }
            }
        }

        $hasUsefulInput = collect($validated)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isNotEmpty();

        if (! $hasUsefulInput) {
            return response()->json([
                'success' => false,
                'message' => 'Add some progress investigation findings first before generating a narrative.',
            ], 422);
        }

        $narrative = $draftService->draftProgressNarrative($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'narrative' => $narrative,
            ],
        ]);
    }

    public function destroy(ProgressInvestigationReport $progressInvestigationReport)
    {
        if (! $this->canReviseReport($progressInvestigationReport)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the personnel who created this report can delete it while it is not approved.',
                ], 403);
            }

            return back()->with('error', 'Only the personnel who created this report can delete it while it is not approved.');
        }

        if ($progressInvestigationReport->secondProgressInvestigation) {
            $message = 'Delete the 2nd Progress Report before deleting the 1st Progress Report.';

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($progressInvestigationReport->finalInvestigation) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete progress report with existing final investigation.',
                ], 422);
            }

            return back()->with('error', 'Cannot delete progress report with existing final investigation.');
        }

        $incidentId = (int) $progressInvestigationReport->spotInvestigation?->incident_id;
        InvestigationReportImageService::deleteForReport($progressInvestigationReport);
        $progressInvestigationReport->delete();
        HistoricalFireIncidentSynchronizer::syncForIncidentId($incidentId);
        UserActivityLogger::log('deleted progress rpt', auth()->user()?->user_id, request());

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('progress-investigation-reports.index')
            ->with('success', 'Progress investigation report deleted successfully.');
    }

    private function resolveFireOriginId(mixed $fireOriginId, mixed $fireOriginOther): ?int
    {
        $otherLabel = trim((string) $fireOriginOther);
        $selected = trim((string) $fireOriginId);

        if ($selected === 'other') {
            if ($otherLabel === '') {
                throw ValidationException::withMessages([
                    'fire_origin_other' => 'Please specify the fire origin when selecting Other.',
                ]);
            }

            return (int) FireOrigin::firstOrCreate(
                ['origin' => $otherLabel],
                ['origin' => $otherLabel]
            )->fire_origin_id;
        }

        if ($selected === '') {
            return null;
        }

        return FireOrigin::whereKey($selected)->exists() ? (int) $selected : null;
    }

    private function validateAndPrepareSequence(array $validated): array
    {
        $stage = $validated['report_stage'] ?? ProgressInvestigationReport::STAGE_SINGLE;
        $spotId = (int) $validated['spot_investigation_id'];

        $spotReport = SpotInvestigationReport::query()
            ->whereKey($spotId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($this->reviewWorkflowEnabled('spot_investigation_report') && ($spotReport->review_status ?? 'submitted') !== 'approved') {
            throw ValidationException::withMessages([
                'spot_investigation_id' => 'The Spot Investigation Report must be approved before creating a Progress Report.',
            ]);
        }

        if ($stage === ProgressInvestigationReport::STAGE_SECOND) {
            $parentId = (int) ($validated['parent_progress_investigation_id'] ?? 0);
            $firstReport = ProgressInvestigationReport::query()
                ->whereKey($parentId)
                ->lockForUpdate()
                ->first();

            if (! $firstReport || $firstReport->report_stage !== ProgressInvestigationReport::STAGE_FIRST) {
                throw ValidationException::withMessages([
                    'parent_progress_investigation_id' => 'Select a valid 1st Progress Report before creating the 2nd Progress Report.',
                ]);
            }

            if ((int) $firstReport->spot_investigation_id !== $spotId) {
                throw ValidationException::withMessages([
                    'spot_investigation_id' => 'The selected spot report does not match the 1st Progress Report.',
                ]);
            }

            if ($firstReport->secondProgressInvestigation()->exists()) {
                throw ValidationException::withMessages([
                    'parent_progress_investigation_id' => 'A 2nd Progress Report already exists for this 1st Progress Report.',
                ]);
            }

            if ($this->reviewWorkflowEnabled('progress_investigation_report') && ($firstReport->review_status ?? 'submitted') !== 'approved') {
                throw ValidationException::withMessages([
                    'parent_progress_investigation_id' => 'The 1st Progress Report must be approved before creating the 2nd Progress Report.',
                ]);
            }

            $validated['parent_progress_investigation_id'] = $firstReport->getKey();
        } else {
            if (ProgressInvestigationReport::query()->where('spot_investigation_id', $spotId)->exists()) {
                throw ValidationException::withMessages([
                    'spot_investigation_id' => 'This spot report already has a progress report path.',
                ]);
            }

            $validated['parent_progress_investigation_id'] = null;
        }

        $validated['report_stage'] = $stage;

        return $validated;
    }

    private function canReviseReport(ProgressInvestigationReport $report): bool
    {
        if (! $this->reviewWorkflowEnabled('progress_investigation_report')) {
            return $this->isReportOwner((int) $report->station_officer_id);
        }

        return ($report->review_status ?? 'submitted') !== 'approved'
            && $this->isReportOwner((int) $report->station_officer_id);
    }

    private function isReportOwner(int $stationOfficerId): bool
    {
        $userId = auth()->user()?->user_id;
        if (! $userId || ! $stationOfficerId) {
            return false;
        }

        return StationOfficer::query()
            ->whereKey($stationOfficerId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function markResubmittedAfterRevision(array $validated): array
    {
        if ($this->reviewWorkflowEnabled('progress_investigation_report')) {
            $validated['review_status'] = 'submitted';
            $validated['review_comments'] = null;
            $validated['reviewed_by_user_id'] = null;
            $validated['reviewed_at'] = null;
        }

        return $validated;
    }

    private function reviewWorkflowEnabled(string $table): bool
    {
        return Schema::hasColumn($table, 'review_status');
    }
}
