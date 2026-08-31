<?php

namespace App\Http\Controllers\Firefighter;

use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklist;
use App\Models\AiActionPlanChecklistActivity;
use App\Models\ChecklistStepVisit;
use App\Models\MitigationAction;
use App\Models\MitigationActionActivity;
use App\Models\MitigationActionAttachment;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Support\BarangayNotificationService;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BarangayCooperationController extends Controller
{
    public function index(Request $request): View
    {
        [$stationOfficer, $coverage] = $this->stationCoverage($request);
        $barangayIds = $coverage->pluck('barangay_id')->map(fn ($id) => (int) $id);
        $cityIds = $coverage->pluck('barangay.city_id')->filter()->map(fn ($id) => (int) $id)->unique();

        $checklists = AiActionPlanChecklist::query()
            ->with([
                'barangay:barangay_id,barangay_name,city_id',
                'barangay.city:city_id,city_name',
                'assignee:user_id,first_name,middle_name,last_name',
                'confirmer:user_id,first_name,middle_name,last_name',
                'items:ai_action_plan_checklist_item_id,ai_action_plan_checklist_id,item_order,item_text,status,reviewed_by,reviewed_at,review_notes',
                'items.reviewer:user_id,first_name,middle_name,last_name',
                'visits.firefighter:user_id,first_name,middle_name,last_name',
                'visits.signatory:user_id,first_name,middle_name,last_name',
                'visits.item:ai_action_plan_checklist_item_id,item_order,item_text',
                'visits.photos',
                'activities.user:user_id,first_name,middle_name,last_name',
            ])
            ->whereIn('barangay_id', $barangayIds)
            ->whereIn('status', ['confirmed', 'in_progress', 'submitted'])
            ->orderByRaw("CASE priority_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('target_date')
            ->get();

        $mitigations = MitigationAction::query()
            ->with([
                'barangay:barangay_id,barangay_name',
                'creator:user_id,first_name,middle_name,last_name',
                'responsibleUser:user_id,first_name,middle_name,last_name',
                'responsibleTeam:team_id,team_name',
                'aiActionPlanChecklist:ai_action_plan_checklist_id,station_id,barangay_id,plan_title',
                'checklist:barangay_prediction_checklist_id,title',
                'attachments:mitigation_action_attachment_id,mitigation_action_id,file_path,file_name',
                'activities.actor:user_id,first_name,middle_name,last_name',
            ])
            ->where(function ($query) use ($barangayIds, $cityIds, $stationOfficer) {
                $query->whereIn('barangay_id', $barangayIds)
                    ->orWhere(fn ($scopeQuery) => $scopeQuery
                        ->where('scope_type', 'stationwide')
                        ->where('owner_station_id', $stationOfficer->station_id))
                    ->orWhere(fn ($scopeQuery) => $scopeQuery
                        ->where('scope_type', 'citywide')
                        ->whereIn('scope_city_id', $cityIds))
                    ->orWhereHas('aiActionPlanChecklist', fn ($planQuery) => $planQuery
                        ->whereNull('barangay_id')
                        ->where('station_id', $stationOfficer->station_id));
            })
            ->whereIn('status', ['planned', 'approved', 'in_progress', 'blocked', 'completed'])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'planned' THEN 2 WHEN 'blocked' THEN 3 WHEN 'completed' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE priority_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('target_date')
            ->get();

        $summary = [
            'barangays' => $coverage->count(),
            'checklists' => $checklists->count(),
            'open_steps' => $checklists->sum(fn ($checklist) => $checklist->items->where('status', 'pending')->count()),
            'open_mitigations' => $mitigations->whereIn('status', ['planned', 'in_progress', 'blocked'])->count(),
        ];

        return view('firefighters.barangay-cooperation', [
            'stationOfficer' => $stationOfficer->loadMissing('station'),
            'coverage' => $coverage,
            'checklists' => $checklists,
            'mitigations' => $mitigations,
            'summary' => $summary,
        ]);
    }

    public function updateChecklistItem(Request $request, int $checklistId, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'followed', 'not_followed'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
            'visit_photos' => [
                'nullable',
                'array',
                'max:5',
                function (string $attribute, mixed $files, \Closure $fail): void {
                    $totalBytes = collect(is_array($files) ? $files : [])
                        ->sum(fn ($file) => is_object($file) && method_exists($file, 'getSize') ? (int) $file->getSize() : 0);
                    if ($totalBytes > 15 * 1024 * 1024) {
                        $fail('Visit proof photos must not exceed 15 MB combined.');
                    }
                },
            ],
            'visit_photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        [$stationOfficer, $coverage] = $this->stationCoverage($request);
        $barangayIds = $coverage->pluck('barangay_id')->map(fn ($id) => (int) $id);

        $checklist = AiActionPlanChecklist::query()
            ->whereIn('barangay_id', $barangayIds)
            ->whereIn('status', ['confirmed', 'in_progress', 'submitted'])
            ->findOrFail($checklistId);
        $item = $checklist->items()
            ->where('ai_action_plan_checklist_item_id', $itemId)
            ->firstOrFail();
        $oldStatus = (string) $item->status;
        $newStatus = (string) $validated['status'];

        $visit = DB::transaction(function () use ($checklist, $item, $oldStatus, $newStatus, $validated, $request, $stationOfficer): ChecklistStepVisit {
            $item->update([
                'status' => $newStatus,
                'reviewed_by' => $newStatus === 'pending' ? null : Auth::id(),
                'reviewed_at' => $newStatus === 'pending' ? null : now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            $visit = ChecklistStepVisit::create([
                'ai_action_plan_checklist_id' => $checklist->getKey(),
                'ai_action_plan_checklist_item_id' => $item->getKey(),
                'station_id' => $stationOfficer->station_id,
                'barangay_id' => $checklist->barangay_id,
                'firefighter_user_id' => Auth::id(),
                'verified_status' => $newStatus,
                'visit_notes' => $validated['review_notes'] ?? null,
                'visited_at' => now(),
                'signoff_status' => 'pending',
            ]);

            foreach (array_values(array_filter($request->file('visit_photos', []))) as $index => $photo) {
                $storedPath = MediaPath::storeForDisk(
                    $photo->store("checklist-visits/{$visit->getKey()}", 'supabase'),
                    'supabase'
                );
                $visit->photos()->create([
                    'file_path' => $storedPath,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                    'file_size' => (int) $photo->getSize(),
                    'sort_order' => $index + 1,
                ]);
            }

            $items = $checklist->items()->get();
            $reviewedCount = $items->whereIn('status', ['followed', 'not_followed'])->count();
            $followedCount = $items->where('status', 'followed')->count();
            $totalCount = $items->count();
            $checklist->update([
                'status' => $reviewedCount === 0
                    ? 'confirmed'
                    : ($totalCount > 0 && $followedCount === $totalCount ? 'submitted' : 'in_progress'),
            ]);

            if (Schema::hasTable('ai_action_plan_checklist_activities')) {
                AiActionPlanChecklistActivity::create([
                    'ai_action_plan_checklist_id' => $checklist->getKey(),
                    'ai_action_plan_checklist_item_id' => $item->getKey(),
                    'user_id' => Auth::id(),
                    'event_type' => 'firefighter_visit_step_updated',
                    'old_value' => $oldStatus,
                    'new_value' => $newStatus,
                    'notes' => $validated['review_notes'] ?? null,
                    'metadata' => [
                        'station_id' => (int) $stationOfficer->station_id,
                        'barangay_id' => (int) $checklist->barangay_id,
                        'visit_id' => $visit->getKey(),
                        'item_order' => $item->item_order,
                        'item_text' => $item->item_text,
                    ],
                    'created_at' => now(),
                ]);
            }

            UserActivityLogger::log('updated covered barangay checklist step during BFP cooperation', Auth::id(), $request);

            return $visit;
        });

        $visit->load(['photos', 'firefighter:user_id,first_name,middle_name,last_name']);
        BarangayNotificationService::notifyChecklistStepVerified($checklist, $item, $visit, Auth::user());

        $progress = $checklist->fresh()->checklist_progress;

        return response()->json([
            'success' => true,
            'message' => 'Barangay checklist step updated.',
            'data' => [
                'checklist_id' => $checklist->getKey(),
                'item_id' => $item->getKey(),
                'status' => $newStatus,
                'checklist_status' => $checklist->fresh()->status,
                'progress' => $progress,
                'reviewed_by' => $newStatus === 'pending' ? null : Auth::user()?->full_name,
                'reviewed_at' => $newStatus === 'pending' ? null : now()->toIso8601String(),
                'visit' => [
                    'id' => $visit->getKey(),
                    'status' => $visit->verified_status,
                    'notes' => $visit->visit_notes,
                    'visited_at' => $visit->visited_at?->toIso8601String(),
                    'visited_at_label' => $visit->visited_at?->format('M d, Y h:i A'),
                    'firefighter' => $visit->firefighter?->full_name,
                    'signoff_status' => $visit->signoff_status,
                    'photos' => $visit->photos->map(fn ($photo) => [
                        'id' => $photo->getKey(),
                        'name' => $photo->file_name,
                        'url' => $photo->url,
                    ])->values(),
                ],
            ],
        ]);
    }

    public function updateMitigation(Request $request, int $mitigationActionId): JsonResponse
    {
        $hasVerificationFields = Schema::hasColumn('mitigation_actions', 'blocked_reason')
            && Schema::hasColumn('mitigation_actions', 'follow_up_date');
        $hasProofTable = Schema::hasTable('mitigation_action_attachments');
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'blocked', 'completed'])],
            'implementation_notes' => ['required', 'string', 'max:3000'],
            'blocked_reason' => array_values(array_filter([
                $hasVerificationFields ? Rule::requiredIf($request->input('status') === 'blocked') : null,
                'nullable', 'string', 'max:2000',
            ])),
            'completion_notes' => ['nullable', 'string', 'max:3000', Rule::requiredIf($request->input('status') === 'completed')],
            'outcome_result' => ['nullable', 'string', 'max:3000', Rule::requiredIf($request->input('status') === 'completed')],
            'effectiveness' => [Rule::requiredIf($request->input('status') === 'completed'), 'nullable', Rule::in(['pending_evaluation', 'effective', 'partially_effective', 'ineffective']), Rule::notIn($request->input('status') === 'completed' ? ['pending_evaluation'] : [])],
            'follow_up_required' => ['nullable', 'boolean'],
            'follow_up_date' => array_values(array_filter([
                $hasVerificationFields ? Rule::requiredIf($request->boolean('follow_up_required')) : null,
                'nullable', 'date',
            ])),
            'follow_up_notes' => ['nullable', 'string', 'max:3000', Rule::requiredIf($request->boolean('follow_up_required'))],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ]);
        [$stationOfficer, $coverage] = $this->stationCoverage($request);
        $barangayIds = $coverage->pluck('barangay_id')->map(fn ($id) => (int) $id);
        $cityIds = $coverage->pluck('barangay.city_id')->filter()->map(fn ($id) => (int) $id)->unique();

        $action = MitigationAction::query()
            ->with(['barangay:barangay_id,barangay_name', 'aiActionPlanChecklist:ai_action_plan_checklist_id,station_id,barangay_id,plan_title'])
            ->where(function ($query) use ($barangayIds, $cityIds, $stationOfficer) {
                $query->whereIn('barangay_id', $barangayIds)
                    ->orWhere(fn ($scopeQuery) => $scopeQuery
                        ->where('scope_type', 'stationwide')
                        ->where('owner_station_id', $stationOfficer->station_id))
                    ->orWhere(fn ($scopeQuery) => $scopeQuery
                        ->where('scope_type', 'citywide')
                        ->whereIn('scope_city_id', $cityIds))
                    ->orWhereHas('aiActionPlanChecklist', fn ($planQuery) => $planQuery
                        ->whereNull('barangay_id')
                        ->where('station_id', $stationOfficer->station_id));
            })
            ->whereIn('status', ['planned', 'approved', 'in_progress', 'blocked', 'completed'])
            ->findOrFail($mitigationActionId);
        if ($hasProofTable && $validated['status'] === 'completed' && ! $action->attachments()->exists() && ! $request->hasFile('attachments')) {
            return response()->json([
                'success' => false,
                'message' => 'Attach at least one proof file before asking the chief to close this plan.',
                'errors' => ['attachments' => ['Proof is required for completion review.']],
            ], 422);
        }
        $oldStatus = $action->status;

        DB::transaction(function () use ($action, $validated, $request, $oldStatus, $hasVerificationFields, $hasProofTable): void {
            $completionRequested = $validated['status'] === 'completed';
            $status = $completionRequested ? 'in_progress' : $validated['status'];
            $updates = [
                'status' => $status,
                'implementation_notes' => $validated['implementation_notes'],
                'completion_notes' => $validated['completion_notes'] ?? null,
                'outcome_result' => $validated['outcome_result'] ?? null,
                'effectiveness' => $validated['effectiveness'] ?? 'pending_evaluation',
                'follow_up_required' => (bool) ($validated['follow_up_required'] ?? false),
                'follow_up_notes' => $validated['follow_up_notes'] ?? null,
                'completed_at' => null,
                'completion_approval_status' => $completionRequested ? 'pending' : null,
                'completion_requested_by' => $completionRequested ? Auth::id() : null,
                'completion_requested_at' => $completionRequested ? now() : null,
                'completion_decided_by' => null,
                'completion_decided_at' => null,
                'completion_decision_notes' => null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'is_manually_edited' => true,
            ];
            if ($hasVerificationFields) {
                $updates['blocked_reason'] = $validated['blocked_reason'] ?? null;
                $updates['follow_up_date'] = $validated['follow_up_date'] ?? null;
            }
            $action->update($updates);

            MitigationActionActivity::create([
                'mitigation_action_id' => $action->getKey(),
                'actor_user_id' => Auth::id(),
                'event_type' => $completionRequested ? 'completion_requested' : 'progress_updated',
                'old_status' => $oldStatus,
                'new_status' => $status,
                'notes' => $validated['implementation_notes'],
                'metadata' => ['effectiveness' => $validated['effectiveness'] ?? 'pending_evaluation'],
            ]);

            foreach ($hasProofTable ? $request->file('attachments', []) : [] as $file) {
                $storedPath = MediaPath::storeForDisk($file->store('mitigation-actions', 'supabase'), 'supabase');
                MitigationActionAttachment::create([
                    'mitigation_action_id' => $action->getKey(),
                    'file_path' => $storedPath,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'attachment_type' => str_starts_with((string) $file->getMimeType(), 'image/') ? 'photo' : 'proof',
                ]);
            }

            UserActivityLogger::log('updated covered barangay mitigation progress', Auth::id(), $request);
        });

        $action = $action->fresh(['barangay']);
        BarangayNotificationService::notifyMitigationProgressUpdated($action, Auth::user(), $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'Mitigation progress updated.',
            'data' => [
                'id' => $action->getKey(),
                'status' => $action->status,
                'display_status' => $action->completion_approval_status === 'pending' ? 'pending_approval' : $action->status,
                'completion_approval_status' => $action->completion_approval_status,
                'implementation_notes' => $action->implementation_notes,
                'completion_notes' => $action->completion_notes,
                'outcome_result' => $action->outcome_result,
                'effectiveness' => $action->effectiveness,
                'follow_up_required' => (bool) $action->follow_up_required,
                'follow_up_notes' => $action->follow_up_notes,
                'reviewed_by' => Auth::user()?->full_name,
                'reviewed_at' => $action->reviewed_at?->toIso8601String(),
            ],
        ]);
    }

    private function stationCoverage(Request $request): array
    {
        $stationOfficer = StationOfficer::query()
            ->with('station:station_id,station_name,station_code')
            ->where('user_id', $request->user()?->user_id)
            ->first();

        abort_unless($stationOfficer?->station_id, 403, 'Your firefighter account is not assigned to a BFP station.');

        $coverage = StationBarangayCoverage::query()
            ->with(['barangay:barangay_id,barangay_name,city_id', 'barangay.city:city_id,city_name'])
            ->where('station_id', $stationOfficer->station_id)
            ->where('is_active', true)
            ->orderByRaw("CASE coverage_type WHEN 'primary' THEN 1 WHEN 'support' THEN 2 ELSE 3 END")
            ->orderBy('barangay_id')
            ->get();

        return [$stationOfficer, $coverage];
    }
}
