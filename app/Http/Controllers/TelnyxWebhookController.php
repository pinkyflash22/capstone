<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelnyxWebhookController extends Controller
{
    private const HANDLED_EVENTS = [
        'call.initiated',
        'call.answered',
        'call.hangup',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Telnyx webhook signature.',
            ], 403);
        }

        $payload = $request->json()->all();
        $event = data_get($payload, 'data', []);
        $eventType = (string) data_get($event, 'event_type', '');
        $callPayload = data_get($event, 'payload', []);
        $normalizedEvent = $this->normalizeEvent($eventType, $event, $callPayload);

        if (in_array($eventType, self::HANDLED_EVENTS, true)) {
            Log::info('Telnyx voice webhook received.', $normalizedEvent);
        } else {
            Log::debug('Unhandled Telnyx webhook received.', $normalizedEvent);
        }

        return response()->json([
            'success' => true,
            'received' => true,
        ]);
    }

    private function normalizeEvent(string $eventType, array $event, array $callPayload): array
    {
        return [
            'provider' => 'telnyx',
            'event_type' => $eventType,
            'event_id' => data_get($event, 'id'),
            'occurred_at' => data_get($event, 'occurred_at'),
            'direction' => data_get($callPayload, 'direction'),
            'call_control_id' => data_get($callPayload, 'call_control_id'),
            'call_session_id' => data_get($callPayload, 'call_session_id'),
            'call_leg_id' => data_get($callPayload, 'call_leg_id'),
            'connection_id' => data_get($callPayload, 'connection_id'),
            'caller_number' => data_get($callPayload, 'from'),
            'destination_number' => data_get($callPayload, 'to'),
            'state' => data_get($callPayload, 'state'),
            'hangup_cause' => data_get($callPayload, 'hangup_cause'),
            'hangup_source' => data_get($callPayload, 'hangup_source'),
            'raw_payload' => $callPayload,
        ];
    }

    private function verifySignature(Request $request): bool
    {
        if (! config('services.telnyx.verify_webhook_signature', true)) {
            return true;
        }

        $publicKey = trim((string) config('services.telnyx.public_key'));
        $signature = (string) $request->header('telnyx-signature-ed25519', '');
        $timestamp = (string) $request->header('telnyx-timestamp', '');

        if ($publicKey === '' || $signature === '' || $timestamp === '') {
            Log::warning('Telnyx webhook signature verification failed due to missing configuration or headers.');

            return false;
        }

        if (! ctype_digit($timestamp)) {
            Log::warning('Telnyx webhook timestamp header is invalid.', ['timestamp' => $timestamp]);

            return false;
        }

        $tolerance = (int) config('services.telnyx.webhook_tolerance_seconds', 300);
        if (abs(now()->timestamp - (int) $timestamp) > max(0, $tolerance)) {
            Log::warning('Telnyx webhook timestamp is outside the allowed tolerance.', ['timestamp' => $timestamp]);

            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        $decodedPublicKey = base64_decode($publicKey, true);

        if ($decodedSignature === false || $decodedPublicKey === false) {
            Log::warning('Telnyx webhook signature or public key could not be base64 decoded.');

            return false;
        }

        $message = $timestamp . '|' . $request->getContent();

        try {
            return sodium_crypto_sign_verify_detached($decodedSignature, $message, $decodedPublicKey);
        } catch (\SodiumException $exception) {
            report($exception);

            return false;
        }
    }
}
