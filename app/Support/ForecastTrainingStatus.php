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
        self::recordHistory($payload);

        return $payload;
    }

    public static function get(string $jobId): ?array
    {
        $value = self::store()->get(self::key($jobId));

        return is_array($value) ? $value : null;
    }

    public static function history(int $userId, int $limit = 10): array
    {
        $value = self::store()->get(self::historyKey($userId), []);

        return collect(is_array($value) ? $value : [])
            ->sortByDesc('updated_at')
            ->take(max(1, min(25, $limit)))
            ->values()
            ->all();
    }

    private static function key(string $jobId): string
    {
        return 'forecast-training:'.$jobId;
    }

    private static function historyKey(int $userId): string
    {
        return 'forecast-training-history:'.$userId;
    }

    private static function recordHistory(array $payload): void
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $history = collect(self::store()->get(self::historyKey($userId), []))
            ->reject(fn ($entry) => ($entry['job_id'] ?? null) === ($payload['job_id'] ?? null))
            ->prepend($payload)
            ->sortByDesc('updated_at')
            ->take(25)
            ->values()
            ->all();

        self::store()->put(self::historyKey($userId), $history, now()->addDays(30));
    }

    private static function store()
    {
        return Cache::store((string) config('services.ai_prediction.training_status_cache_store', 'database'));
    }
}
