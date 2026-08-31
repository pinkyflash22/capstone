<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\AfterActivityReport;
use App\Models\EventActivity;
use App\Models\StationOfficer;
use App\Services\InvestigationReportImageService;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AfterActivityReportController extends Controller
{
    public function store(Request $request, InvestigationReportImageService $imageService)
    {
        [$officer, $validated] = $this->validatedData($request);
        $validated['station_id'] = $officer->station_id;
        $validated['station_officer_id'] = $officer->station_officer_id;
        $validated['report_completed_at'] = $validated['report_completed_at'] ?? now();
        $validated['prepared_signed_at'] = now();
        $validated['prepared_signature_hash'] = $this->signatureHash($request, $validated, 'prepared');

        $report = AfterActivityReport::create($validated);
        $imageService->store($request, $report, 'aar');
        UserActivityLogger::log('created after activity report', $request->user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'data' => InvestigationReportImageService::responsePayload(
                $report->load(['eventActivity', 'investigator.user', 'approvedBy.user'])
            ),
        ]);
    }

    public function update(Request $request, AfterActivityReport $afterActivityReport, InvestigationReportImageService $imageService)
    {
        $officer = $this->currentOfficer($request);
        abort_unless((int) $afterActivityReport->station_id === (int) $officer->station_id, 404);
        abort_if($afterActivityReport->isApproved(), 409, 'Approved AARs are locked.');
        [, $validated] = $this->validatedData($request, $afterActivityReport);

        $validated['review_status'] = 'submitted';
        $validated['review_comments'] = null;
        $validated['reviewed_by_user_id'] = null;
        $validated['reviewed_at'] = null;
        $validated['approval_signer_name'] = null;
        $validated['approved_signed_at'] = null;
        $validated['approval_signature_hash'] = null;
        $validated['prepared_signed_at'] = now();
        $validated['prepared_signature_hash'] = $this->signatureHash($request, $validated, 'prepared');
        $afterActivityReport->update($validated);
        $imageService->store($request, $afterActivityReport, 'aar');
        UserActivityLogger::log('updated after activity report', $request->user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'data' => InvestigationReportImageService::responsePayload(
                $afterActivityReport->fresh()->load(['eventActivity', 'investigator.user', 'approvedBy.user'])
            ),
        ]);
    }

    public function destroy(Request $request, AfterActivityReport $afterActivityReport)
    {
        $officer = $this->currentOfficer($request);
        abort_unless((int) $afterActivityReport->station_id === (int) $officer->station_id, 404);
        abort_if($afterActivityReport->isApproved(), 409, 'Approved AARs cannot be deleted.');

        InvestigationReportImageService::deleteForReport($afterActivityReport);
        $afterActivityReport->delete();
        UserActivityLogger::log('deleted after activity report', $request->user()?->user_id, $request);

        return response()->json(['success' => true]);
    }

    private function validatedData(Request $request, ?AfterActivityReport $report = null): array
    {
        $officer = $this->currentOfficer($request);
        $validated = $request->validate([
            'event_activity_id' => [
                'nullable',
                'integer',
                'exists:event_activities,event_id',
                Rule::unique('after_activity_reports', 'event_activity_id')->ignore($report?->getKey(), 'after_activity_report_id'),
            ],
            'activity_title' => ['required', 'string', 'max:255'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'activity_location' => ['nullable', 'string', 'max:255'],
            'participants_text' => ['nullable', 'string', 'max:10000'],
            'deployed_resources_text' => ['nullable', 'string', 'max:10000'],
            'memorandum_for' => ['nullable', 'string', 'max:255'],
            'report_completed_at' => ['nullable', 'date'],
            'references' => ['nullable', 'string'],
            'background' => ['nullable', 'string'],
            'significance' => ['nullable', 'string'],
            'activity_details' => ['required', 'string'],
            'issues_challenges' => ['nullable', 'string'],
            'discussion_assessment' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'approved_by_station_officer_id' => ['nullable', 'integer', 'exists:station_officers,station_officer_id'],
            'signature_name' => ['required', 'string', 'max:255'],
            'signature_consent' => ['accepted'],
            ...InvestigationReportImageService::validationRules(),
        ]);
        unset($validated['proof_images']);
        $signatureName = trim((string) ($validated['signature_name'] ?? ''));
        unset($validated['signature_name'], $validated['signature_consent']);
        $expectedName = trim((string) $request->user()?->full_name);
        if ($expectedName === '' || mb_strtolower($signatureName) !== mb_strtolower($expectedName)) {
            throw ValidationException::withMessages([
                'signature_name' => 'Type your account name exactly as shown to electronically sign this report.',
            ]);
        }
        $validated['participants'] = $this->lineList($validated['participants_text'] ?? null);
        $validated['deployed_resources'] = $this->lineList($validated['deployed_resources_text'] ?? null);
        unset($validated['participants_text'], $validated['deployed_resources_text']);

        if (! empty($validated['event_activity_id'])) {
            $event = EventActivity::query()->findOrFail($validated['event_activity_id']);
            if ((int) $event->station_id !== (int) $officer->station_id) {
                throw ValidationException::withMessages([
                    'event_activity_id' => 'Select an activity from your assigned station.',
                ]);
            }
            if ($event->user_id !== null) {
                throw ValidationException::withMessages([
                    'event_activity_id' => 'Select an official station activity, not a personal schedule item.',
                ]);
            }
            if ($event->event_date?->isFuture()) {
                throw ValidationException::withMessages([
                    'event_activity_id' => 'An AAR can only be created after the scheduled activity date.',
                ]);
            }

            if (! $report) {
                $validated['activity_title'] = $event->event;
                $validated['activity_type'] = $event->activity_type;
                $validated['activity_date'] = $event->event_date?->toDateString();
                $validated['activity_location'] = $event->barangay ?: ($validated['activity_location'] ?? null);
            }
        }

        if (! empty($validated['approved_by_station_officer_id'])) {
            $belongsToStation = StationOfficer::query()
                ->whereKey($validated['approved_by_station_officer_id'])
                ->where('station_id', $officer->station_id)
                ->exists();
            if (! $belongsToStation) {
                throw ValidationException::withMessages([
                    'approved_by_station_officer_id' => 'Select an approving officer from your station.',
                ]);
            }
        }

        return [$officer, $validated];
    }

    private function currentOfficer(Request $request): StationOfficer
    {
        return StationOfficer::query()
            ->where('user_id', $request->user()?->user_id)
            ->firstOrFail();
    }

    private function lineList(?string $value): array
    {
        return collect(preg_split('/\R/u', (string) $value) ?: [])
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }

    private function signatureHash(Request $request, array $payload, string $purpose): string
    {
        $signedPayload = collect($payload)->except(['prepared_signed_at', 'prepared_signature_hash'])->sortKeys()->all();

        return hash_hmac('sha256', json_encode([
            'purpose' => $purpose,
            'user_id' => $request->user()?->user_id,
            'payload' => $signedPayload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string) config('app.key'));
    }
}
