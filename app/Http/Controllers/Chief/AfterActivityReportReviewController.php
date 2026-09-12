<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\AfterActivityReport;
use App\Notifications\AfterActivityReportReviewedNotification;
use App\Models\StationOfficer;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AfterActivityReportReviewController extends Controller
{
    public function index(Request $request)
    {
        $role = strtolower((string) $request->user()?->role?->role);
        $isProvincialAdmin = $role === 'provincial admin';
        $stationId = $isProvincialAdmin ? null : $this->reviewerOfficer($request)->station_id;
        $status = in_array($request->string('status')->toString(), ['submitted', 'approved', 'changes_requested'], true)
            ? $request->string('status')->toString()
            : '';

        $reports = AfterActivityReport::query()
            ->with(['station', 'preparedBy.user', 'reviewedBy', 'proofImages', 'reviewHistory.reviewer'])
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->when($status, fn ($query) => $query->where('review_status', $status))
            ->latest('activity_date')
            ->latest('after_activity_report_id')
            ->paginate(15)
            ->withQueryString();

        return view('chief.after-activity-reports', [
            'reports' => $reports,
            'status' => $status,
            'isProvincialAdmin' => $isProvincialAdmin,
            'reviewRouteName' => $isProvincialAdmin ? 'admin.after-activity-reports.review' : 'station-admin.after-activity-reports.review',
            'pdfRouteName' => $isProvincialAdmin ? 'admin.after-activity-reports.pdf' : 'station-admin.after-activity-reports.pdf',
        ]);
    }

    public function review(Request $request, AfterActivityReport $afterActivityReport)
    {
        $this->authorizeReport($request, $afterActivityReport);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'request_changes'])],
            'review_comments' => ['nullable', 'string', 'max:5000'],
            'signature_name' => ['exclude_unless:decision,approve', 'required', 'string', 'max:255'],
            'signature_consent' => ['exclude_unless:decision,approve', 'required', 'accepted'],
        ]);
        $user = $request->user();
        $expectedName = trim((string) $user?->full_name);
        if ($validated['decision'] === 'approve' && mb_strtolower(trim((string) $validated['signature_name'])) !== mb_strtolower($expectedName)) {
            throw ValidationException::withMessages(['signature_name' => 'Type your account name exactly as shown to approve and sign.']);
        }

        DB::transaction(function () use ($afterActivityReport, $validated, $user, $expectedName, $request) {
            $report = AfterActivityReport::query()->lockForUpdate()->findOrFail($afterActivityReport->getKey());
            $approved = $validated['decision'] === 'approve';
            $officer = StationOfficer::query()->where('user_id', $user->user_id)->where('station_id', $report->station_id)->first();
            $signedAt = $approved ? now() : null;

            $report->update([
                'review_status' => $approved ? 'approved' : 'changes_requested',
                'review_comments' => $validated['review_comments'] ?? null,
                'reviewed_by_user_id' => $user->user_id,
                'reviewed_at' => now(),
                'approved_by_station_officer_id' => $approved && $officer ? $officer->station_officer_id : $report->approved_by_station_officer_id,
                'approval_signer_name' => $approved ? $expectedName : null,
                'approved_signed_at' => $signedAt,
                'approval_signature_hash' => null,
            ]);
            if ($approved) {
                $report->forceFill(['approval_signature_hash' => $report->makeApprovalSignatureHash()])->saveQuietly();
            }
            $report->reviewHistory()->create([
                'reviewer_user_id' => $user->user_id,
                'decision' => $report->review_status,
                'comments' => $validated['review_comments'] ?? null,
                'signer_name' => $approved ? $expectedName : null,
                'signed_at' => $approved ? $signedAt : null,
                'signature_hash' => $approved ? $report->approval_signature_hash : null,
            ]);
            $preparer = $report->preparedBy()->with('user')->first()?->user;
            if ($preparer) {
                $notification = AfterActivityReportReviewedNotification::fromReport(
                    $report,
                    $report->review_status,
                    $validated['review_comments'] ?? null,
                    $expectedName,
                );
                DB::afterCommit(fn () => $preparer->notify($notification));
            }
            UserActivityLogger::log($approved ? 'approved after activity report' : 'requested changes to after activity report', $user->user_id, $request);
        });

        $message = $validated['decision'] === 'approve'
            ? 'AAR approved and digitally signed.'
            : 'The AAR was returned for changes.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function authorizeReport(Request $request, AfterActivityReport $report): void
    {
        if (strtolower((string) $request->user()?->role?->role) === 'provincial admin') {
            return;
        }
        abort_unless((int) $this->reviewerOfficer($request)->station_id === (int) $report->station_id, 404);
    }

    private function reviewerOfficer(Request $request): StationOfficer
    {
        return StationOfficer::query()->where('user_id', $request->user()?->user_id)->firstOrFail();
    }

}
