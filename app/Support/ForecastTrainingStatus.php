<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ForecastTrainingStatus
{
    public static function put(string $jobId, int $userId, string $status, int $progress, string $message, array $extra = []): array
    {
        $payload = array_merge([
            'job_id' => $jobId,
            'user_id' => $userId,
            'status' => $status,
            'progress' => max(0, min(100, $progress)),
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], $extra);

        self::store()->put(self::key($jobId), $payload, now()->addDay());

        return $payload;
    }

    public static function get(string $jobId): ?array
    {
        $value = self::store()->get(self::key($jobId));

        return is_array($value) ? $value : null;
    }

    private static function key(string $jobId): string
    {
        return 'forecast-training:'.$jobId;
    }

    private static function store()
    {
        return Cache::store((string) config('services.ai_prediction.training_status_cache_store', 'database'));
    }
}
