<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\CommunityActivityRequest;
use App\Models\EventActivity;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Support\UserActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommunityActivityRequestController extends Controller
{
    public function storeCitizen(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $barangay = $user->address?->barangay;
        $this->createRequest($request, $user, $barangay, 'citizen');

        UserActivityLogger::log('submitted community activity request', $user->user_id, $request);

        return redirect()
            ->route('citizen.dashboard', ['section' => 'announcements'])
            ->with('success', 'Your orientation or seminar request was sent to the assigned station for review.');
    }

    public function storeBarangay(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $barangay = $this->resolveBarangayForBarangayUser($user);

        if (! $barangay) {
            return redirect()
                ->route('barangay.awareness')
                ->with('error', 'No barangay is linked to this account yet, so the request could not be submitted.');
        }

        $this->createRequest($request, $user, $barangay, 'barangay');

        UserActivityLogger::log('submitted barangay community activity request', $user->user_id, $request);

        return redirect()
            ->route('barangay.awareness')
            ->with('success', 'Community activity request sent to the assigned station for review.');
    }

    public function approve(Request $request, int $requestId): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();
        $stationId = $stationOfficer?->station_id;

        $activityRequest = CommunityActivityRequest::query()
            ->with(['barangay', 'requester'])
            ->findOrFail($requestId);

        if (! $stationId || (int) ($activityRequest->station_id ?? 0) !== (int) $stationId) {
            return redirect()
                ->route('station-admin.schedule')
                ->with('error', 'You are not allowed to approve this request.');
        }

        if (($activityRequest->status ?? 'pending') !== 'pending') {
            return redirect()
                ->route('station-admin.schedule')
                ->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'activity_type' => ['required', Rule::in(['operations', 'drill', 'community', 'inspection', 'command', 'logistics'])],
            'status' => ['nullable', Rule::in(['planned', 'confirmed', 'completed'])],
            'review_notes' => ['nullable', 'string'],
        ]);

        $hasConflict = EventActivity::query()
            ->where('station_id', $stationId)
            ->whereNull('user_id')
            ->whereDate('event_date', $validated['event_date'])
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($hasConflict) {
            return redirect()
                ->route('station-admin.schedule')
                ->with('error', 'The selected schedule window is already occupied for this station.');
        }

        $scheduleLabel = Carbon::createFromFormat('H:i', $validated['start_time'])->format('g:i A')
            . ' - '
            . Carbon::createFromFormat('H:i', $validated['end_time'])->format('g:i A');

        $event = EventActivity::create([
            'event' => $validated['event'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'schedule' => $scheduleLabel,
            'activity_type' => $validated['activity_type'],
            'status' => $validated['status'] ?? 'planned',
            'barangay' => $activityRequest->barangay?->barangay_name,
            'notes' => trim(
                "Community activity request.\n\n"
                . "Request type: {$activityRequest->request_type}\n"
                . "Requested by: " . ($activityRequest->contact_name ?: ($activityRequest->requester?->full_name ?? 'Unknown')) . "\n"
                . "Description: {$activityRequest->description}\n"
                . ($validated['review_notes'] ? "Review notes: {$validated['review_notes']}" : '')
            ),
            'station_id' => $stationId,
            'user_id' => null,
        ]);

        $activityRequest->update([
            'status' => 'approved',
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => $user->user_id,
            'reviewed_at' => now(),
            'converted_event_id' => $event->event_id,
        ]);

        UserActivityLogger::log('approved community activity request', $user->user_id, $request);

        return redirect()
            ->route('station-admin.schedule')
            ->with('success', 'Community activity request approved and added to the official schedule.');
    }

    public function reject(Request $request, int $requestId): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();
        $stationId = $stationOfficer?->station_id;

        $activityRequest = CommunityActivityRequest::query()->findOrFail($requestId);

        if (! $stationId || (int) ($activityRequest->station_id ?? 0) !== (int) $stationId) {
            return redirect()
                ->route('station-admin.schedule')
                ->with('error', 'You are not allowed to reject this request.');
        }

        if (($activityRequest->status ?? 'pending') !== 'pending') {
            return redirect()
                ->route('station-admin.schedule')
                ->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $activityRequest->update([
            'status' => 'rejected',
            'review_notes' => $validated['review_notes'],
            'reviewed_by' => $user->user_id,
            'reviewed_at' => now(),
        ]);

        UserActivityLogger::log('rejected community activity request', $user->user_id, $request);

        return redirect()
            ->route('station-admin.schedule')
            ->with('success', 'Community activity request rejected.');
    }

    private function createRequest(Request $request, $user, ?Barangay $barangay, string $submittedFrom): void
    {
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['orientation', 'seminar', 'drill', 'inspection', 'community_meeting', 'other'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_start_time' => ['nullable', 'date_format:H:i'],
            'preferred_end_time' => ['nullable', 'date_format:H:i', 'after:preferred_start_time'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:60'],
        ]);

        $stationId = $this->resolveStationIdForBarangay($barangay?->barangay_id);

        CommunityActivityRequest::create([
            'requester_user_id' => $user->user_id,
            'requester_role' => strtolower((string) ($user->role->role ?? $submittedFrom)),
            'submitted_from' => $submittedFrom,
            'station_id' => $stationId,
            'barangay_id' => $barangay?->barangay_id,
            'request_type' => $validated['request_type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'preferred_date' => $validated['preferred_date'] ?? null,
            'preferred_start_time' => $validated['preferred_start_time'] ?? null,
            'preferred_end_time' => $validated['preferred_end_time'] ?? null,
            'contact_name' => $validated['contact_name'] ?? ($user->full_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))),
            'contact_number' => $validated['contact_number'] ?? $user->contact_number,
            'status' => 'pending',
        ]);
    }

    private function resolveBarangayForBarangayUser($user): ?Barangay
    {
        if ($user->barangayOfficer?->barangay) {
            return $user->barangayOfficer->barangay;
        }

        if ($user->captainedBarangayHall?->barangay) {
            return $user->captainedBarangayHall->barangay;
        }

        return $user->address?->barangay;
    }

    private function resolveStationIdForBarangay(?int $barangayId): int
    {
        if ($barangayId) {
            $coveredStationId = DB::table('station_barangay_coverage')
                ->where('barangay_id', $barangayId)
                ->where(function ($query) {
                    $query->whereNull('is_active')->orWhere('is_active', true);
                })
                ->orderByRaw("CASE WHEN LOWER(COALESCE(coverage_type, '')) = 'primary' THEN 0 ELSE 1 END")
                ->orderBy('station_barangay_coverage_id')
                ->value('station_id');

            if ($coveredStationId) {
                return (int) $coveredStationId;
            }

            $cityId = Barangay::query()
                ->where('barangay_id', $barangayId)
                ->value('city_id');

            if ($cityId) {
                $cityStationId = Station::query()
                    ->whereHas('address.barangay', function ($query) use ($cityId) {
                        $query->where('city_id', $cityId);
                    })
                    ->orderBy('station_id')
                    ->value('station_id');

                if ($cityStationId) {
                    return (int) $cityStationId;
                }
            }
        }

        return 1;
    }
}
