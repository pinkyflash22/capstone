<?php

namespace App\Http\Controllers\Firefighter;

use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklist;
use App\Models\AiActionPlanChecklistActivity;
use App\Models\ChecklistStepVisit;
use App\Models\MitigationAction;
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
                'responsibleTeam:team_id',
                'aiActionPlanChecklist:ai_action_plan_checklist_id,plan_title',
                'checklist:barangay_prediction_checklist_id,title',
                'attachments:mitigation_action_attachment_id,mitigation_action_id,file_path,file_name',
            ])
            ->whereIn('barangay_id', $barangayIds)
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
