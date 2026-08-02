<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Marker;
use App\Models\Station;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Models\User;
use App\Models\VoiceCall;
use App\Services\AgoraTokenService;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoiceCallController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isCitizen($user), 403);

        $data = $request->validate([
            'station_id' => ['nullable', 'integer', 'exists:stations,station_id'],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,incident_id'],
            'caller_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'caller_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'caller_accuracy' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'caller_location_label' => ['nullable', 'string', 'max:255'],
        ]);

        $this->releaseSessionLock($request);

        $this->expireStaleCalls($request);

        $call = VoiceCall::create([
            'channel_name' => 'pyro_' . $user->user_id . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(10)),
            'caller_id' => $user->user_id,
            'station_id' => $this->resolveStationIdForCaller($user, $request),
            'incident_id' => $data['incident_id'] ?? null,
            'caller_latitude' => $data['caller_latitude'] ?? null,
            'caller_longitude' => $data['caller_longitude'] ?? null,
            'caller_accuracy' => $data['caller_accuracy'] ?? null,
            'caller_location_label' => $data['caller_location_label'] ?? null,
            'status' => VoiceCall::STATUS_RINGING,
            'queued_at' => now(),
        ])->load($this->callRelations());

        $this->logCallForParticipants($call, $request, 'started voice call');

        return response()->json([
            'success' => true,
            'call' => $this->serializeCall($call, $user),
            'poll_interval_ms' => (int) config('services.voice_calls.poll_interval_ms', 2000),
        ], 201);
    }

    public function incoming(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $this->expireStaleCalls($request);

        return response()->json($this->responderPayload($user));
    }

    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        return response()->stream(function () use ($user, $request) {
            $lastFingerprint = null;

            for ($iteration = 0; $iteration < 30; $iteration++) {
                if (connection_aborted()) {
                    break;
                }

                $this->expireStaleCalls($request);
                $payload = $this->responderPayload($user);
                $fingerprint = md5(json_encode($payload));

                if ($fingerprint !== $lastFingerprint || $iteration === 0) {
                    echo "event: calls\n";
                    echo 'data: ' . json_encode($payload) . "\n\n";
                    $lastFingerprint = $fingerprint;
                } else {
                    echo "event: keepalive\n";
                    echo 'data: {"ok":true}' . "\n\n";
                }

                @ob_flush();
                flush();
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function show(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $this->releaseSessionLock($request);
        $this->expireStaleCalls($request);
        $voiceCall->refresh()->load($this->callRelations());
        $this->authorizeCallVisibility($voiceCall, $request->user());

        return response()->json([
            'success' => true,
            'call' => $this->serializeCall($voiceCall, $request->user()),
            'poll_interval_ms' => (int) config('services.voice_calls.poll_interval_ms', 2000),
        ]);
    }

    public function accept(Request $request, VoiceCall $voiceCall, AgoraTokenService $agora): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $acceptedCall = DB::transaction(function () use ($voiceCall, $user) {
            $lockedAdminCall = VoiceCall::query()
                ->where('assigned_admin_id', $user->user_id)
                ->where('status', VoiceCall::STATUS_ANSWERED)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($lockedAdminCall) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'End your active call before accepting another.',
                    'call' => $this->serializeCall($lockedAdminCall->load($this->callRelations()), $user),
                ], 409));
            }

            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeResponderForCall($call, $user);

            if (! in_array($call->status, [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED], true) || $call->assigned_admin_id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'This call is no longer available.',
                    'call' => $this->serializeCall($call->load($this->callRelations()), $user),
                ], 409));
            }

            $call->forceFill([
                'assigned_admin_id' => $user->user_id,
                'station_id' => $call->station_id ?: $this->adminStationId($user),
                'status' => VoiceCall::STATUS_ANSWERED,
                'answered_at' => now(),
            ])->save();

            VoiceCall::query()
                ->whereKeyNot($call->id)
                ->where('station_id', $call->station_id)
                ->where('status', VoiceCall::STATUS_RINGING)
                ->update([
                    'status' => VoiceCall::STATUS_QUEUED,
                    'updated_at' => now(),
                ]);

            return $call->load($this->callRelations());
        });

        $this->logCallForParticipants($acceptedCall, $request, 'station answered voice call', 'accepted citizen voice call');

        return response()->json([
            'success' => true,
            'call' => $this->serializeCall($acceptedCall, $user),
            'agora' => $agora->rtcToken($acceptedCall->channel_name, (int) $user->user_id),
        ]);
    }

    public function reject(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $call = DB::transaction(function () use ($voiceCall, $user) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeResponderForCall($call, $user);

            if (in_array($call->status, [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED], true)) {
                $call->forceFill([
                    'assigned_admin_id' => $call->assigned_admin_id ?: $user->user_id,
                    'status' => VoiceCall::STATUS_REJECTED,
                    'ended_at' => now(),
                ])->save();
            }

            return $call->load($this->callRelations());
        });

        $this->logCallForParticipants($call, $request, 'station rejected voice call', 'rejected citizen voice call');

        return response()->json(['success' => true, 'call' => $this->serializeCall($call, $user)]);
    }

    public function end(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $user = $request->user();
        $this->releaseSessionLock($request);

        $call = DB::transaction(function () use ($voiceCall, $user) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeCallVisibility($call, $user);

            if (! $call->isTerminal()) {
                $call->forceFill([
                    'status' => VoiceCall::STATUS_ENDED,
                    'ended_at' => now(),
                ])->save();
            }

            return $call->load($this->callRelations());
        });

        $endedByCaller = (int) $call->caller_id === (int) $user->user_id;
        $this->logCallForParticipants(
            $call,
            $request,
            $endedByCaller ? 'ended voice call' : 'station ended voice call',
            $endedByCaller ? 'caller ended voice call' : 'ended citizen voice call'
        );

        return response()->json(['success' => true, 'call' => $this->serializeCall($call, $user)]);
    }

    public function token(Request $request, VoiceCall $voiceCall, AgoraTokenService $agora): JsonResponse
    {
        $user = $request->user();
        $this->releaseSessionLock($request);
        $voiceCall->load(['caller', 'assignedAdmin']);

        abort_unless($voiceCall->status === VoiceCall::STATUS_ANSWERED, 403);
        abort_unless((int) $voiceCall->caller_id === (int) $user->user_id || (int) $voiceCall->assigned_admin_id === (int) $user->user_id, 403);

        return response()->json([
            'success' => true,
            'agora' => $agora->rtcToken($voiceCall->channel_name, (int) $user->user_id),
        ]);
    }

    public function createIncident(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $call = DB::transaction(function () use ($voiceCall, $user, $data) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeResponderForCall($call, $user);

            if ($call->created_incident_id) {
                return $call->load($this->callRelations());
            }

            if ($call->flagged_at) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'This call was already flagged as a prank.',
                ], 409));
            }

            if ($call->caller_latitude === null || $call->caller_longitude === null) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Caller location is unavailable.',
                ], 422));
            }

            $call->loadMissing(['caller']);
            $callerName = $this->callerName($call);
            $title = trim($data['title'] ?? '') ?: 'Fire emergency from voice call';

            $marker = Marker::create([
                'title' => $title,
                'description' => 'Incident created from emergency voice call by ' . $callerName . '.',
                'lat' => $call->caller_latitude,
                'lng' => $call->caller_longitude,
                'status' => 'active',
                'category' => 'fire',
            ]);

            $incident = Incident::create([
                'marker_id' => $marker->marker_id,
                'status' => 'Reported',
                'reported_by' => $call->caller_id,
            ]);

            $call->forceFill([
                'created_incident_id' => $incident->incident_id,
                'incident_id' => $call->incident_id ?: $incident->incident_id,
                'station_id' => $call->station_id ?: $this->adminStationId($user),
            ])->save();

            return $call->load($this->callRelations());
        });

        $this->logCallForParticipants($call, $request, 'station created incident from voice call', 'created incident from voice call');

        return response()->json([
            'success' => true,
            'message' => 'Incident marker created from call.',
            'call' => $this->serializeCall($call, $user),
            'incident' => $call->createdIncident ? [
                'id' => $call->createdIncident->incident_id,
                'marker_id' => $call->createdIncident->marker_id,
                'status' => $call->createdIncident->status,
                'marker' => $call->createdIncident->marker,
            ] : null,
        ]);
    }

    public function flagPrank(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        $call = DB::transaction(function () use ($voiceCall, $user, $data) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeResponderForCall($call, $user);

            $status = $call->status;
            if (in_array($status, [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED], true)) {
                $status = VoiceCall::STATUS_REJECTED;
            } elseif ($status === VoiceCall::STATUS_ANSWERED) {
                $status = VoiceCall::STATUS_ENDED;
            }

            $call->forceFill([
                'assigned_admin_id' => $call->assigned_admin_id ?: $user->user_id,
                'status' => $status,
                'ended_at' => $call->ended_at ?: now(),
                'flagged_at' => $call->flagged_at ?: now(),
                'flagged_by' => $user->user_id,
                'flag_reason' => trim($data['reason'] ?? '') ?: 'Flagged from incoming call panel',
            ])->save();

            return $call->load($this->callRelations());
        });

        $this->logCallForParticipants($call, $request, 'station flagged voice call as prank', 'flagged voice call as prank');

        return response()->json([
            'success' => true,
            'message' => 'Call flagged as prank.',
            'call' => $this->serializeCall($call, $user),
        ]);
    }

    private function serializeCall(VoiceCall $call, ?User $viewer = null): array
    {
        $createdAt = $call->created_at ?: now();
        $caller = $call->caller;
        $hasLocation = $call->caller_latitude !== null && $call->caller_longitude !== null;
        $locationDisplay = $call->caller_location_label ?: (
            $hasLocation
                ? number_format((float) $call->caller_latitude, 6) . ', ' . number_format((float) $call->caller_longitude, 6)
                : 'Location unavailable'
        );

        return [
            'id' => $call->id,
            'channel_name' => $call->channel_name,
            'status' => $call->status,
            'caller_id' => $call->caller_id,
            'assigned_admin_id' => $call->assigned_admin_id,
            'station_id' => $call->station_id,
            'incident_id' => $call->incident_id,
            'created_incident_id' => $call->created_incident_id,
            'caller' => [
                'id' => $caller?->user_id,
                'name' => $this->callerName($call),
                'email' => $caller?->email,
                'contact_number' => $caller?->contact_number,
            ],
            'caller_location' => [
                'has_location' => $hasLocation,
                'lat' => $hasLocation ? (float) $call->caller_latitude : null,
                'lng' => $hasLocation ? (float) $call->caller_longitude : null,
                'accuracy' => $call->caller_accuracy,
                'label' => $call->caller_location_label,
                'display' => $locationDisplay,
            ],
            'station' => [
                'id' => $call->station?->station_id,
                'name' => $call->station?->name,
            ],
            'created_incident' => $call->createdIncident ? [
                'id' => $call->createdIncident->incident_id,
                'marker_id' => $call->createdIncident->marker_id,
                'status' => $call->createdIncident->status,
            ] : null,
            'is_flagged_prank' => filled($call->flagged_at),
            'flag_reason' => $call->flag_reason,
            'created_at' => $call->created_at?->toIso8601String(),
            'queued_at' => $call->queued_at?->toIso8601String(),
            'answered_at' => $call->answered_at?->toIso8601String(),
            'ended_at' => $call->ended_at?->toIso8601String(),
            'ringing_seconds' => max(0, now()->diffInSeconds($createdAt, true)),
            'can_accept' => $viewer && $this->isStationResponder($viewer) && ! $this->activeCallForAdmin($viewer),
        ];
    }

    private function expireStaleCalls(?Request $request = null): void
    {
        $seconds = (int) config('services.voice_calls.missed_after_seconds', 45);
        $answeredSeconds = (int) config('services.voice_calls.stale_answered_after_seconds', 180);
        $cutoff = now()->subSeconds(max(15, $seconds));
        $answeredCutoff = now()->subSeconds(max(60, $answeredSeconds));
        $staleRingingCalls = VoiceCall::query()
            ->with($this->callRelations())
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($staleRingingCalls as $staleCall) {
            $staleCall->forceFill([
                'status' => VoiceCall::STATUS_MISSED,
                'ended_at' => now(),
            ])->save();
            $this->logCallForParticipants($staleCall, $request, 'voice call missed', 'citizen voice call missed');
        }

        $staleAnsweredCalls = VoiceCall::query()
            ->with($this->callRelations())
            ->where('status', VoiceCall::STATUS_ANSWERED)
            ->whereNull('ended_at')
            ->whereNotNull('answered_at')
            ->where('answered_at', '<=', $answeredCutoff)
            ->get();

        foreach ($staleAnsweredCalls as $staleCall) {
            $staleCall->forceFill([
                'status' => VoiceCall::STATUS_ENDED,
                'ended_at' => now(),
            ])->save();
            $this->logCallForParticipants($staleCall, $request, 'voice call auto-ended after timeout', 'voice call auto-ended after timeout');
        }
    }

    private function activeCallForAdmin(User $user): ?VoiceCall
    {
        return VoiceCall::query()
            ->with($this->callRelations())
            ->where('assigned_admin_id', $user->user_id)
            ->where('status', VoiceCall::STATUS_ANSWERED)
            ->whereNull('ended_at')
            ->latest('answered_at')
            ->first();
    }

    private function responderPayload(User $user): array
    {
        $stationId = $this->adminStationId($user);
        $activeCall = $this->activeCallForAdmin($user);

        $incoming = VoiceCall::query()
            ->with($this->callRelations())
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
            ->when($stationId, fn ($query) => $query->where(function ($inner) use ($stationId) {
                $inner->where('station_id', $stationId)->orWhereNull('station_id');
            }))
            ->orderBy('created_at')
            ->limit(12)
            ->get()
            ->each(function (VoiceCall $call) use ($activeCall) {
                if ($activeCall && $call->status === VoiceCall::STATUS_RINGING) {
                    $call->forceFill(['status' => VoiceCall::STATUS_QUEUED]);
                }
            });

        return [
            'success' => true,
            'active_call' => $activeCall ? $this->serializeCall($activeCall->load($this->callRelations()), $user) : null,
            'incoming_calls' => $incoming->map(fn (VoiceCall $call) => $this->serializeCall($call, $user))->values(),
            'poll_interval_ms' => (int) config('services.voice_calls.poll_interval_ms', 2000),
            'server_time' => now()->toIso8601String(),
        ];
    }

    private function authorizeCallVisibility(VoiceCall $call, User $user): void
    {
        if ((int) $call->caller_id === (int) $user->user_id || (int) $call->assigned_admin_id === (int) $user->user_id) {
            return;
        }

        if ($this->isStationResponder($user)) {
            $this->authorizeResponderForCall($call, $user);
            return;
        }

        abort(403);
    }

    private function authorizeResponderForCall(VoiceCall $call, User $user): void
    {
        $stationId = $this->adminStationId($user);

        abort_unless($this->isStationResponder($user), 403);
        abort_unless(! $call->station_id || ! $stationId || (int) $call->station_id === (int) $stationId, 403);
    }

    private function resolveStationIdForCaller(User $user, Request $request): ?int
    {
        if ($request->filled('station_id')) {
            return (int) $request->integer('station_id');
        }

        $barangayId = $user->address?->barangay_id;
        if ($barangayId) {
            $coveredStationId = StationBarangayCoverage::query()
                ->where('barangay_id', $barangayId)
                ->where('is_active', true)
                ->value('station_id');

            if ($coveredStationId) {
                return (int) $coveredStationId;
            }
        }

        return Station::query()->value('station_id');
    }

    private function adminStationId(User $user): ?int
    {
        $stationId = StationOfficer::query()->where('user_id', $user->user_id)->value('station_id');

        return $stationId ? (int) $stationId : null;
    }

    private function isCitizen(User $user): bool
    {
        return Str::lower((string) $user->role?->role) === 'citizen';
    }

    private function isStationResponder(User $user): bool
    {
        return in_array(Str::lower((string) $user->role?->role), ['station admin', 'chief', 'chief station admin'], true);
    }

    private function callRelations(): array
    {
        return ['caller', 'assignedAdmin', 'station', 'incident', 'createdIncident.marker', 'flaggedBy'];
    }

    private function callerName(VoiceCall $call): string
    {
        $caller = $call->caller;

        return trim(($caller?->first_name ?? '') . ' ' . ($caller?->last_name ?? ''))
            ?: ($caller?->email ?? 'Anonymous caller');
    }

    private function logCallForParticipants(
        VoiceCall $call,
        ?Request $request,
        ?string $callerAction = null,
        ?string $adminAction = null
    ): void {
        if ($callerAction && $call->caller_id) {
            UserActivityLogger::log(
                $this->buildVoiceCallLogAction($callerAction, $this->assignedAdminName($call)),
                (int) $call->caller_id,
                $request
            );
        }

        if ($adminAction && $call->assigned_admin_id) {
            UserActivityLogger::log(
                $this->buildVoiceCallLogAction($adminAction, $this->auditCallerName($call)),
                (int) $call->assigned_admin_id,
                $request
            );
        }
    }

    private function buildVoiceCallLogAction(string $action, ?string $subject = null): string
    {
        $base = trim($action);
        $target = trim((string) $subject);

        if ($target === '') {
            return $base;
        }

        return mb_substr($base . ': ' . $target, 0, 50);
    }

    private function auditCallerName(VoiceCall $call): string
    {
        $caller = trim($this->callerName($call));

        return $caller !== '' && strcasecmp($caller, 'Anonymous caller') !== 0
            ? $caller
            : 'Anonymous';
    }

    private function assignedAdminName(VoiceCall $call): ?string
    {
        $admin = $call->assignedAdmin;
        if (! $admin) {
            return null;
        }

        $name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));

        return $name !== '' ? $name : ($admin->email ?: 'Station responder');
    }

    private function releaseSessionLock(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        try {
            $request->session()->save();
        } catch (\Throwable $error) {
            report($error);
        }
    }
}
