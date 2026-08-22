<?php

namespace App\Http\Controllers\Barangay;

use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklistActivity;
use App\Models\ChecklistStepVisit;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ChecklistVisitController extends Controller
{
    public function signOff(Request $request, int $visitId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'signoff_status' => ['required', Rule::in(['accepted', 'needs_follow_up'])],
            'signoff_notes' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('signoff_status') === 'needs_follow_up')],
        ]);
        $user = $request->user()->loadMissing(['barangayOfficer', 'address.barangay', 'captainedBarangayHall']);
        $barangayId = (int) ($user->barangayOfficer?->barangay_id
            ?? $user->captainedBarangayHall?->barangay_id
            ?? $user->address?->barangay_id
            ?? 0);

        abort_if($barangayId <= 0, 403, 'Your account is not linked to a barangay.');

        $visit = ChecklistStepVisit::query()
            ->where('barangay_id', $barangayId)
            ->whereHas('checklist', fn ($query) => $query->where('barangay_id', $barangayId))
            ->findOrFail($visitId);
        $oldStatus = $visit->signoff_status;

        DB::transaction(function () use ($visit, $validated, $oldStatus, $request): void {
            $visit->update([
                'signoff_status' => $validated['signoff_status'],
                'barangay_signed_off_by' => Auth::id(),
                'signed_off_at' => now(),
                'signoff_notes' => $validated['signoff_notes'] ?? null,
            ]);

            if (Schema::hasTable('ai_action_plan_checklist_activities')) {
                AiActionPlanChecklistActivity::create([
                    'ai_action_plan_checklist_id' => $visit->ai_action_plan_checklist_id,
                    'ai_action_plan_checklist_item_id' => $visit->ai_action_plan_checklist_item_id,
                    'user_id' => Auth::id(),
                    'event_type' => 'barangay_visit_signed_off',
                    'old_value' => $oldStatus,
                    'new_value' => $validated['signoff_status'],
                    'notes' => $validated['signoff_notes'] ?? null,
                    'metadata' => ['visit_id' => $visit->getKey(), 'barangay_id' => $visit->barangay_id],
                    'created_at' => now(),
                ]);
            }

            UserActivityLogger::log('signed off BFP checklist visit', Auth::id(), $request);
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'BFP visit sign-off saved.']);
        }

        return redirect()->route('barangay.checklists')->with('success', 'BFP visit sign-off saved.');
    }
}
