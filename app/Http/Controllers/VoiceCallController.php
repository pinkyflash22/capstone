<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Marker;
use App\Models\Station;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Models\User;
use App\Models\VoiceCall;
use App\Models\VoiceResponderPresence;
use App\Services\AgoraTokenService;
use App\Support\IncidentContextDispatcher;
use App\Support\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VoiceCallController extends Controller
{
    private const ANONYMOUS_VOICE_USER_SESSION_KEY = 'anonymous_voice_user_id';
    private const ANONYMOUS_VOICE_USER_COOKIE = 'anonymous_voice_user_id';
    private const ANONYMOUS_VOICE_USER_COOKIE_MINUTES = 60 * 24 * 30;

    private ?bool $voiceCallsStorageIsReady = null;
    private ?bool $responderPresenceStorageIsReady = null;

    public function bootstrapAnonymousSession(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionAnonymousUserId = $request->session()->get(self::ANONYMOUS_VOICE_USER_SESSION_KEY);
        $cookieAnonymousUserId = $request->cookie(self::ANONYMOUS_VOICE_USER_COOKIE);

        if ($user && ! in_array(Str::lower((string) $user->role?->role), ['citizen', 'anonymous'], true)) {
            abort(403);
        }

        if ($user && Str::lower((string) $user->role?->role) === 'anonymous') {
            $request->session()->put(self::ANONYMOUS_VOICE_USER_SESSION_KEY, (int) $user->user_id);
        }

        if (! $user) {
            $candidateUserId = $sessionAnonymousUserId ?: $cookieAnonymousUserId;
            $user = $candidateUserId
                ? User::query()->anonymous()->with('role')->find((int) $candidateUserId)
                : null;

            if (! $user) {
                $user = User::createAnonymousCaller()->load('role');
            }

            $request->session()->put(self::ANONYMOUS_VOICE_USER_SESSION_KEY, (int) $user->user_id);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put(self::ANONYMOUS_VOICE_USER_SESSION_KEY, (int) $user->user_id);
        }

        $response = response()->json([
            'success' => true,
            'user_id' => (int) $user->user_id,
            'role' => Str::lower((string) $user->role?->role),
            'csrf_token' => csrf_token(),
        ]);

        if ($this->isAnonymousUser($user)) {
            Cookie::queue(cookie(
                self::ANONYMOUS_VOICE_USER_COOKIE,
                (string) $user->user_id,
                self::ANONYMOUS_VOICE_USER_COOKIE_MINUTES
            ));
        }

        return $response;
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isCaller($user), 403);

        $data = $request->validate([
            'station_id' => ['nullable', 'integer', 'exists:stations,station_id'],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,incident_id'],
            'caller_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'caller_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'caller_accuracy' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'caller_location_label' => ['nullable', 'string', 'max:255'],
        ]);

        // A wide or unknown browser fix is more harmful than no coordinate: it can
        // direct responders to a confidently displayed but incorrect location.
        $hasReliableLocation = isset($data['caller_latitude'], $data['caller_longitude'], $data['caller_accuracy'])
            && (int) $data['caller_accuracy'] > 0
            && (int) $data['caller_accuracy'] <= 150;

        $this->releaseSessionLock($request);

        $this->expireStaleCalls($request);

        $call = VoiceCall::create([
            'channel_name' => 'pyro_' . $user->user_id . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(10)),
            'direction' => VoiceCall::DIRECTION_INBOUND,
            'caller_id' => $user->user_id,
            'initiated_by' => $user->user_id,
            'station_id' => $this->resolveStationIdForCaller($user, $request),
            'incident_id' => $data['incident_id'] ?? null,
            'caller_latitude' => $hasReliableLocation ? $data['caller_latitude'] : null,
            'caller_longitude' => $hasReliableLocation ? $data['caller_longitude'] : null,
            'caller_accuracy' => $hasReliableLocation ? $data['caller_accuracy'] : null,
            'caller_location_label' => $hasReliableLocation ? ($data['caller_location_label'] ?? null) : null,
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

        try {
            $stationId = $this->adminStationId($user);
            $this->touchResponderPresence($user, $stationId);
            $this->distributeWaitingCalls($user, $stationId);
            $this->expireStaleCalls($request);

            return response()->json($this->responderPayload($user, $stationId));
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'success' => false,
                'active_call' => null,
                'incoming_calls' => [],
                'poll_interval_ms' => (int) config('services.voice_calls.poll_interval_ms', 2000),
                'server_time' => now()->toIso8601String(),
                'message' => 'Voice call inbox is temporarily unavailable.',
            ]);
        }
    }

    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);
        $stationId = $this->adminStationId($user);

        return response()->stream(function () use ($user, $request, $stationId) {
            $lastFingerprint = null;

            for ($iteration = 0; $iteration < 30; $iteration++) {
                if (connection_aborted()) {
                    break;
                }

                try {
                    $this->touchResponderPresence($user, $stationId);
                    $this->distributeWaitingCalls($user, $stationId);
                    $this->expireStaleCalls($request);
                    $payload = $this->responderPayload($user, $stationId);
                } catch (\Throwable $error) {
                    report($error);

                    echo "event: error\n";
                    echo 'data: {"success":false,"active_call":null,"incoming_calls":[],"message":"Voice call stream is temporarily unavailable."}' . "\n\n";
                    @ob_flush();
                    flush();
                    break;
                }

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
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            $this->authorizeResponderForCall($call, $user);

            if ($call->status === VoiceCall::STATUS_ANSWERED && (int) $call->assigned_admin_id === (int) $user->user_id) {
                return $call->load($this->callRelations());
            }

            $activeCount = $this->occupiedResponderCallCount($user);
            if ($activeCount >= $this->maxConcurrentResponderCalls()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Your concurrent call limit has been reached. End one call before answering another.',
                ], 409));
            }

            if (! in_array($call->status, [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED], true)
                || ($call->assigned_admin_id && (int) $call->assigned_admin_id !== (int) $user->user_id)) {
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
                'held_at' => null,
                'held_by' => null,
            ])->save();

            return $call->load($this->callRelations());
        });

        $this->logCallForParticipants($acceptedCall, $request, 'station answered voice call', 'accepted citizen voice call');

        return response()->json([
            'success' => true,
            'call' => $this->serializeCall($acceptedCall, $user),
            'agora' => $agora->rtcToken($acceptedCall->channel_name, (int) $user->user_id),
        ]);
    }

    public function callback(Request $request, AgoraTokenService $agora): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);
        $this->releaseSessionLock($request);

        $data = $request->validate([
            'citizen_user_id' => ['required', 'integer', 'exists:users,user_id'],
            'callback_of' => ['nullable', 'integer', 'exists:voice_calls,id'],
        ]);
        $stationId = $this->adminStationId($user);
        $isProvincialAdmin = Str::lower((string) $user->role?->role) === 'provincial admin';
        abort_unless($stationId || $isProvincialAdmin, 422, 'Your account is not assigned to a station.');

        $citizen = User::query()->with('role')->findOrFail((int) $data['citizen_user_id']);
        abort_unless(Str::lower((string) $citizen->role?->role) === 'citizen', 422, 'Only registered citizens can receive station callbacks.');

        $priorCall = VoiceCall::query()
            ->where('caller_id', $citizen->user_id)
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->where(function ($query) {
                $query->where('direction', VoiceCall::DIRECTION_INBOUND)->orWhereNull('direction');
            })
            ->whereIn('status', VoiceCall::TERMINAL_STATUSES)
            ->when(! empty($data['callback_of']), fn ($query) => $query->whereKey((int) $data['callback_of']))
            ->latest('created_at')
            ->first();
        abort_unless($priorCall, 403, 'This citizen has not previously contacted your station.');

        $stationId = $stationId ?: ($priorCall->station_id ? (int) $priorCall->station_id : null);
        abort_unless($stationId, 422, 'The original call is not connected to a station.');

        $call = DB::transaction(function () use ($user, $citizen, $stationId, $priorCall) {
            $existing = VoiceCall::query()
                ->where('direction', VoiceCall::DIRECTION_OUTBOUND)
                ->where('caller_id', $citizen->user_id)
                ->where('assigned_admin_id', $user->user_id)
                ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_ANSWERED])
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing->load($this->callRelations());
            }

            if ($this->occupiedResponderCallCount($user) >= $this->maxConcurrentResponderCalls()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Your concurrent call limit has been reached.',
                ], 409));
            }

            return VoiceCall::create([
                'channel_name' => 'callback_' . $citizen->user_id . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(10)),
                'direction' => VoiceCall::DIRECTION_OUTBOUND,
                'caller_id' => $citizen->user_id,
                'initiated_by' => $user->user_id,
                'callback_of' => $priorCall->id,
                'assigned_admin_id' => $user->user_id,
                'station_id' => $stationId,
                'status' => VoiceCall::STATUS_RINGING,
                'queued_at' => now(),
            ])->load($this->callRelations());
        });

        $this->logCallForParticipants($call, $request, 'received callback from station', 'called citizen back');

        return response()->json([
            'success' => true,
            'message' => 'Calling citizen ' . $citizen->user_id . '.',
            'call' => $this->serializeCall($call, $user),
            'agora' => $agora->rtcToken($call->channel_name, (int) $user->user_id),
        ], 201);
    }

    public function incomingCallback(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isCaller($user), 403);
        $this->releaseSessionLock($request);
        $this->expireStaleCalls($request);

        $call = VoiceCall::query()
            ->with($this->callRelations())
            ->where('direction', VoiceCall::DIRECTION_OUTBOUND)
            ->where('caller_id', $user->user_id)
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_ANSWERED])
            ->whereNull('ended_at')
            ->latest('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'callback' => $call ? $this->serializeCall($call, $user) : null,
        ]);
    }

    public function acceptCallback(Request $request, VoiceCall $voiceCall, AgoraTokenService $agora): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isCaller($user), 403);
        $this->releaseSessionLock($request);

        $call = DB::transaction(function () use ($voiceCall, $user) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            abort_unless($call->direction === VoiceCall::DIRECTION_OUTBOUND, 403);
            abort_unless((int) $call->caller_id === (int) $user->user_id, 403);
            abort_unless(in_array($call->status, [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_ANSWERED], true), 409, 'This callback is no longer available.');

            if ($call->status !== VoiceCall::STATUS_ANSWERED) {
                $call->forceFill(['status' => VoiceCall::STATUS_ANSWERED, 'answered_at' => now()])->save();
            }

            return $call->load($this->callRelations());
        });

        return response()->json([
            'success' => true,
            'call' => $this->serializeCall($call, $user),
            'agora' => $agora->rtcToken($call->channel_name, (int) $user->user_id),
        ]);
    }

    public function rejectCallback(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isCaller($user), 403);

        $call = DB::transaction(function () use ($voiceCall, $user) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            abort_unless($call->direction === VoiceCall::DIRECTION_OUTBOUND, 403);
            abort_unless((int) $call->caller_id === (int) $user->user_id, 403);
            if ($call->status === VoiceCall::STATUS_RINGING) {
                $call->forceFill(['status' => VoiceCall::STATUS_REJECTED, 'ended_at' => now()])->save();
            }

            return $call->load($this->callRelations());
        });

        return response()->json(['success' => true, 'call' => $this->serializeCall($call, $user)]);
    }

    public function hold(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        return $this->setHoldState($request, $voiceCall, true);
    }

    public function resume(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        return $this->setHoldState($request, $voiceCall, false);
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
                    'held_at' => null,
                    'held_by' => null,
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

        $responderWaitingOnCallback = $voiceCall->direction === VoiceCall::DIRECTION_OUTBOUND
            && $voiceCall->status === VoiceCall::STATUS_RINGING
            && (int) $voiceCall->assigned_admin_id === (int) $user->user_id;
        abort_unless($voiceCall->status === VoiceCall::STATUS_ANSWERED || $responderWaitingOnCallback, 403);
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

        if ($call->createdIncident) {
            IncidentContextDispatcher::dispatchFor($call->createdIncident);
        }

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

    private function serializeCall(VoiceCall $call, ?User $viewer = null, ?bool $responderHasCapacity = null): array
    {
        $createdAt = $call->created_at ?: now();
        $caller = $call->caller;
        $anonymousCaller = $this->isAnonymousUser($caller);
        $hasLocation = $call->caller_latitude !== null && $call->caller_longitude !== null;
        $locationDisplay = $call->caller_location_label ?: (
            $hasLocation
                ? number_format((float) $call->caller_latitude, 6) . ', ' . number_format((float) $call->caller_longitude, 6)
                : 'Location unavailable'
        );

        return [
            'id' => $call->id,
            'channel_name' => $call->channel_name,
            'direction' => $call->direction ?: VoiceCall::DIRECTION_INBOUND,
            'is_callback' => $call->direction === VoiceCall::DIRECTION_OUTBOUND,
            'callback_of' => $call->callback_of,
            'status' => $call->status,
            'caller_id' => $call->caller_id,
            'assigned_admin_id' => $call->assigned_admin_id,
            'station_id' => $call->station_id,
            'incident_id' => $call->incident_id,
            'created_incident_id' => $call->created_incident_id,
            'caller' => [
                'id' => $caller?->user_id,
                'name' => $this->callerName($call),
                'email' => $anonymousCaller ? null : $caller?->email,
                'contact_number' => $anonymousCaller ? null : $caller?->contact_number,
            ],
            'caller_location' => [
                'has_location' => $hasLocation,
                'lat' => $hasLocation ? (float) $call->caller_latitude : null,
                'lng' => $hasLocation ? (float) $call->caller_longitude : null,
                'accuracy' => $call->caller_accuracy,
                'label' => $call->caller_location_label,
                'display' => $locationDisplay,
                'captured_at' => $hasLocation ? $createdAt->toIso8601String() : null,
            ],
            'station' => [
                'id' => $call->station?->station_id,
                'name' => $call->station?->station_name,
                'contact_number' => $call->station?->contact_number,
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
            'held_at' => $call->held_at?->toIso8601String(),
            'is_on_hold' => filled($call->held_at),
            'ringing_seconds' => max(0, now()->diffInSeconds($createdAt, true)),
            'can_accept' => $viewer
                && $this->isStationResponder($viewer)
                && $call->direction !== VoiceCall::DIRECTION_OUTBOUND
                && ($responderHasCapacity ?? ($this->occupiedResponderCallCount($viewer) < $this->maxConcurrentResponderCalls())),
            'can_callback' => $viewer
                && $this->isStationResponder($viewer)
                && ! $anonymousCaller
                && $call->direction !== VoiceCall::DIRECTION_OUTBOUND
                && in_array($call->status, VoiceCall::TERMINAL_STATUSES, true),
        ];
    }

    private function expireStaleCalls(?Request $request = null): void
    {
        if (! $this->voiceCallsStorageReady()) {
            return;
        }

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
            $expired = VoiceCall::query()
                ->whereKey($staleCall->id)
                ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
                ->whereNull('answered_at')
                ->update(['status' => VoiceCall::STATUS_MISSED, 'ended_at' => now(), 'updated_at' => now()]);
            if ($expired) {
                $this->logCallForParticipants($staleCall->refresh(), $request, 'voice call missed', 'citizen voice call missed');
            }
        }

        $staleAnsweredCalls = VoiceCall::query()
            ->with($this->callRelations())
            ->where('status', VoiceCall::STATUS_ANSWERED)
            ->whereNull('ended_at')
            ->whereNotNull('answered_at')
            ->where('answered_at', '<=', $answeredCutoff)
            ->get();

        foreach ($staleAnsweredCalls as $staleCall) {
            $expired = VoiceCall::query()
                ->whereKey($staleCall->id)
                ->where('status', VoiceCall::STATUS_ANSWERED)
                ->whereNull('ended_at')
                ->update(['status' => VoiceCall::STATUS_ENDED, 'ended_at' => now(), 'updated_at' => now()]);
            if ($expired) {
                $this->logCallForParticipants($staleCall->refresh(), $request, 'voice call auto-ended after timeout', 'voice call auto-ended after timeout');
            }
        }
    }

    private function activeCallForAdmin(User $user): ?VoiceCall
    {
        return $this->activeCallsForAdmin($user)->first();
    }

    private function activeCallsForAdmin(User $user)
    {
        if (! $this->voiceCallsStorageReady()) {
            return collect();
        }

        return VoiceCall::query()
            ->where('assigned_admin_id', $user->user_id)
            ->where('status', VoiceCall::STATUS_ANSWERED)
            ->whereNull('ended_at')
            ->latest('answered_at')
            ->get();
    }

    private function responderPayload(User $user, ?int $stationId): array
    {
        if (! $this->voiceCallsStorageReady()) {
            return [
                'success' => false,
                'active_call' => null,
                'incoming_calls' => [],
                'missed_calls' => [],
                'poll_interval_ms' => (int) config('services.voice_calls.poll_interval_ms', 2000),
                'server_time' => now()->toIso8601String(),
                'message' => 'Voice call storage is not ready on this server.',
            ];
        }

        $activeCalls = $this->activeCallsForAdmin($user);

        $incoming = VoiceCall::query()
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
            ->where(function ($query) {
                $query->where('direction', VoiceCall::DIRECTION_INBOUND)->orWhereNull('direction');
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_admin_id')->orWhere('assigned_admin_id', $user->user_id);
            })
            ->when($stationId, fn ($query) => $query->where(function ($inner) use ($stationId) {
                $inner->where('station_id', $stationId)->orWhereNull('station_id');
            }))
            ->orderBy('created_at')
            ->limit(12)
            ->get();

        $outgoingCallbacks = VoiceCall::query()
            ->where('direction', VoiceCall::DIRECTION_OUTBOUND)
            ->where('assigned_admin_id', $user->user_id)
            ->where('status', VoiceCall::STATUS_RINGING)
            ->whereNull('ended_at')
            ->latest('created_at')
            ->get();

        $missedCalls = VoiceCall::query()
            ->where('status', VoiceCall::STATUS_MISSED)
            ->when($stationId, fn ($query) => $query->where(function ($inner) use ($stationId) {
                $inner->where('station_id', $stationId)->orWhereNull('station_id');
            }))
            ->latest('ended_at')
            ->limit(8)
            ->get();

        $recentContacts = VoiceCall::query()
            ->whereIn('status', [VoiceCall::STATUS_ENDED, VoiceCall::STATUS_REJECTED])
            ->where(function ($query) {
                $query->where('direction', VoiceCall::DIRECTION_INBOUND)->orWhereNull('direction');
            })
            ->when($stationId, fn ($query) => $query->where(function ($inner) use ($stationId) {
                $inner->where('station_id', $stationId)->orWhereNull('station_id');
            }))
            ->latest('ended_at')
            ->limit(8)
            ->get();

        (new VoiceCall)->newCollection(
            $activeCalls
                ->concat($outgoingCallbacks)
                ->concat($incoming)
                ->concat($missedCalls)
                ->concat($recentContacts)
                ->unique('id')
                ->values()
                ->all()
        )->loadMissing($this->payloadRelations());

        $responderHasCapacity = $this->occupiedResponderCallCount($user) < $this->maxConcurrentResponderCalls();
        $serialize = fn (VoiceCall $call) => $this->serializeCall($call, $user, $responderHasCapacity);

        return [
            'success' => true,
            'active_call' => $activeCalls->first() ? $serialize($activeCalls->first()) : null,
            'active_calls' => $activeCalls->map($serialize)->values(),
            'outgoing_calls' => $outgoingCallbacks->map($serialize)->values(),
            'incoming_calls' => $incoming->map($serialize)->values(),
            'missed_calls' => $missedCalls->map($serialize)->values(),
            'recent_contacts' => $recentContacts->map($serialize)->values(),
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

        if (Str::lower((string) $user->role?->role) === 'firefighter') {
            $officerStationId = StationOfficer::query()
                ->where('user_id', $user->user_id)
                ->value('station_id');

            if ($officerStationId) {
                return (int) $officerStationId;
            }
        }

        if ($this->isAnonymousUser($user)
            && $request->filled('caller_latitude')
            && $request->filled('caller_longitude')) {
            $nearestStationId = $this->nearestStationIdForCoordinates(
                (float) $request->input('caller_latitude'),
                (float) $request->input('caller_longitude')
            );

            if ($nearestStationId) {
                return $nearestStationId;
            }
        }

        $user->loadMissing(['address', 'barangayOfficer']);
        $barangayId = $user->barangayOfficer?->barangay_id ?? $user->address?->barangay_id;
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

    private function nearestStationIdForCoordinates(float $latitude, float $longitude): ?int
    {
        $nearest = Station::query()
            ->with('marker:marker_id,lat,lng')
            ->whereHas('marker', fn ($query) => $query->whereNotNull('lat')->whereNotNull('lng'))
            ->get(['station_id', 'marker_id'])
            ->map(function (Station $station) use ($latitude, $longitude) {
                $stationLatitude = (float) $station->marker->lat;
                $stationLongitude = (float) $station->marker->lng;
                $latitudeDelta = deg2rad($stationLatitude - $latitude);
                $longitudeDelta = deg2rad($stationLongitude - $longitude);
                $a = sin($latitudeDelta / 2) ** 2
                    + cos(deg2rad($latitude)) * cos(deg2rad($stationLatitude)) * sin($longitudeDelta / 2) ** 2;

                return [
                    'station_id' => (int) $station->station_id,
                    'distance' => 6371000 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a))),
                ];
            })
            ->sortBy('distance')
            ->first();

        return $nearest['station_id'] ?? null;
    }

    private function adminStationId(User $user): ?int
    {
        $stationId = StationOfficer::query()->where('user_id', $user->user_id)->value('station_id');

        return $stationId ? (int) $stationId : null;
    }

    private function isCaller(User $user): bool
    {
        return in_array(Str::lower((string) $user->role?->role), [
            'citizen',
            'anonymous',
            'barangay',
            'barangay captain',
            'firefighter',
        ], true);
    }

    private function isAnonymousUser(?User $user): bool
    {
        return Str::lower((string) $user?->role?->role) === 'anonymous';
    }

    private function isStationResponder(User $user): bool
    {
        return in_array(Str::lower((string) $user->role?->role), [
            'station admin',
            'chief',
            'chief station admin',
            'provincial admin',
        ], true);
    }

    private function callRelations(): array
    {
        return ['caller.role', 'assignedAdmin', 'initiatedBy', 'station', 'incident', 'createdIncident.marker', 'flaggedBy'];
    }

    private function payloadRelations(): array
    {
        return ['caller.role', 'station', 'createdIncident'];
    }

    private function maxConcurrentResponderCalls(): int
    {
        return max(1, min(8, (int) config('services.voice_calls.max_concurrent_responder_calls', 4)));
    }

    private function setHoldState(Request $request, VoiceCall $voiceCall, bool $held): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isStationResponder($user), 403);

        $call = DB::transaction(function () use ($voiceCall, $user, $held) {
            $call = VoiceCall::query()->whereKey($voiceCall->id)->lockForUpdate()->firstOrFail();
            abort_unless($call->status === VoiceCall::STATUS_ANSWERED, 409, 'Only connected calls can be placed on hold.');
            abort_unless((int) $call->assigned_admin_id === (int) $user->user_id, 403);
            $call->forceFill([
                'held_at' => $held ? ($call->held_at ?: now()) : null,
                'held_by' => $held ? $user->user_id : null,
            ])->save();

            return $call->load($this->callRelations());
        });

        return response()->json([
            'success' => true,
            'message' => $held ? 'Call placed on hold.' : 'Call resumed.',
            'call' => $this->serializeCall($call, $user),
        ]);
    }

    private function touchResponderPresence(User $user, ?int $stationId): void
    {
        if (! $stationId || ! $this->responderPresenceStorageReady()) {
            return;
        }

        VoiceResponderPresence::query()->updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'station_id' => $stationId,
                'active_call_count' => $this->responderWorkloadCount($user),
                'last_seen_at' => now(),
            ]
        );
    }

    private function distributeWaitingCalls(User $viewer, ?int $stationId): void
    {
        if (! $stationId || ! $this->responderPresenceStorageReady()) {
            return;
        }

        $online = VoiceResponderPresence::query()
            ->where('station_id', $stationId)
            ->where('last_seen_at', '>=', now()->subSeconds(15))
            ->orderBy('active_call_count')
            ->orderBy('last_seen_at', 'desc')
            ->get();
        if ($online->isEmpty()) {
            return;
        }

        $onlineIds = $online->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        VoiceCall::query()
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
            ->where(function ($query) {
                $query->where('direction', VoiceCall::DIRECTION_INBOUND)->orWhereNull('direction');
            })
            ->where('station_id', $stationId)
            ->whereNotNull('assigned_admin_id')
            ->whereNotIn('assigned_admin_id', $onlineIds)
            ->update(['assigned_admin_id' => null, 'updated_at' => now()]);

        $waiting = VoiceCall::query()
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED])
            ->where(function ($query) {
                $query->where('direction', VoiceCall::DIRECTION_INBOUND)->orWhereNull('direction');
            })
            ->where(function ($query) use ($stationId) {
                $query->where('station_id', $stationId)->orWhereNull('station_id');
            })
            ->whereNull('assigned_admin_id')
            ->where('created_at', '<=', now()->subSeconds(2))
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        foreach ($waiting as $call) {
            $target = $online->sortBy(fn ($presence) => [(int) $presence->active_call_count, $presence->last_seen_at?->timestamp ?? 0])->first();
            if (! $target || (int) $target->active_call_count >= $this->maxConcurrentResponderCalls()) {
                break;
            }
            $claimed = VoiceCall::query()->whereKey($call->id)->whereNull('assigned_admin_id')->update([
                'assigned_admin_id' => $target->user_id,
                'station_id' => $stationId,
                'updated_at' => now(),
            ]);
            if ($claimed) {
                $target->active_call_count++;
            }
        }
    }

    private function occupiedResponderCallCount(User $user): int
    {
        return VoiceCall::query()
            ->where('assigned_admin_id', $user->user_id)
            ->whereNull('ended_at')
            ->where(function ($query) {
                $query->where('status', VoiceCall::STATUS_ANSWERED)
                    ->orWhere(function ($callback) {
                        $callback->where('direction', VoiceCall::DIRECTION_OUTBOUND)
                            ->where('status', VoiceCall::STATUS_RINGING);
                    });
            })
            ->count();
    }

    private function responderWorkloadCount(User $user): int
    {
        return VoiceCall::query()
            ->where('assigned_admin_id', $user->user_id)
            ->whereNull('ended_at')
            ->whereIn('status', [VoiceCall::STATUS_RINGING, VoiceCall::STATUS_QUEUED, VoiceCall::STATUS_ANSWERED])
            ->count();
    }

    private function callerName(VoiceCall $call): string
    {
        $caller = $call->caller;

        if ($this->isAnonymousUser($caller)) {
            return 'Anonymous caller';
        }

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

    private function voiceCallsStorageReady(): bool
    {
        if ($this->voiceCallsStorageIsReady !== null) {
            return $this->voiceCallsStorageIsReady;
        }

        try {
            if (! Schema::hasTable('voice_calls')) {
                return $this->voiceCallsStorageIsReady = false;
            }

            $required = ['status', 'answered_at', 'ended_at', 'flagged_at', 'assigned_admin_id', 'direction', 'initiated_by', 'held_at'];
            $available = Schema::getColumnListing('voice_calls');

            return $this->voiceCallsStorageIsReady = array_diff($required, $available) === [];
        } catch (\Throwable $error) {
            report($error);

            return $this->voiceCallsStorageIsReady = false;
        }
    }

    private function responderPresenceStorageReady(): bool
    {
        return $this->responderPresenceStorageIsReady
            ??= Schema::hasTable('voice_responder_presence');
    }
}
