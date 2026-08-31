<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AnalysisRunStatus
{
    public static function put(string $runId, int $userId, string $status, int $progress, string $message, array $extra = []): array
    {
        $payload = array_merge([
            'run_id' => $runId,
            'user_id' => $userId,
            'status' => $status,
            'progress' => max(0, min(100, $progress)),
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], $extra);

        self::store()->put(self::key($runId), $payload, now()->addDay());

        return $payload;
    }

    public static function get(string $runId): ?array
    {
        $value = self::store()->get(self::key($runId));

        return is_array($value) ? $value : null;
    }

    public static function rememberActive(string $signature, string $runId): void
    {
        self::store()->put('analysis-run-active:'.$signature, $runId, now()->addMinutes(10));
    }

    public static function active(string $signature): ?array
    {
        $runId = self::store()->get('analysis-run-active:'.$signature);
        $status = is_string($runId) ? self::get($runId) : null;

        return $status && in_array($status['status'] ?? null, ['queued', 'running'], true) ? $status : null;
    }

    private static function key(string $runId): string
    {
        return 'analysis-run:'.$runId;
    }

    private static function store()
    {
        return Cache::store((string) config('services.ai_prediction.training_status_cache_store', 'database'));
    }
}
