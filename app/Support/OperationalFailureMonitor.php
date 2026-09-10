<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OperationalFailureMonitor
{
    private const SERVICES = [
        'active_routes',
        'open_meteo',
        'model_evaluation',
        'model_evaluation_cache',
    ];

    public static function record(string $service, string $message): void
    {
        if (! in_array($service, self::SERVICES, true)) {
            return;
        }

        try {
            $windowMinutes = self::windowMinutes();
            $minute = now()->format('YmdHi');
            $countKey = "ops:failures:{$service}:{$minute}";

            Cache::add($countKey, 0, now()->addMinutes($windowMinutes + 2));
            Cache::increment($countKey);
            Cache::put("ops:failures:{$service}:last", [
                'occurred_at' => now()->toIso8601String(),
                'message' => Str::limit(trim($message), 300),
            ], now()->addDay());
        } catch (\Throwable $exception) {
            Log::debug('Unable to update the operational failure counter.', [
                'service' => $service,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public static function status(): array
    {
        $windowMinutes = self::windowMinutes();
        $threshold = max(1, (int) config('operations.monitoring.failure_threshold', 3));
        $services = [];

        foreach (self::SERVICES as $service) {
            try {
                $count = 0;
                for ($offset = 0; $offset < $windowMinutes; $offset++) {
                    $minute = now()->subMinutes($offset)->format('YmdHi');
                    $count += (int) Cache::get("ops:failures:{$service}:{$minute}", 0);
                }

                $services[$service] = [
                    'ok' => $count < $threshold,
                    'failure_count' => $count,
                    'last_failure' => Cache::get("ops:failures:{$service}:last"),
                ];
            } catch (\Throwable $exception) {
                $services[$service] = [
                    'ok' => false,
                    'failure_count' => null,
                    'message' => 'Failure counters are unavailable.',
                ];
            }
        }

        return [
            'ok' => collect($services)->every(fn (array $service) => $service['ok']),
            'window_minutes' => $windowMinutes,
            'failure_threshold' => $threshold,
            'services' => $services,
        ];
    }

    private static function windowMinutes(): int
    {
        return max(1, min(60, (int) config('operations.monitoring.failure_window_minutes', 15)));
    }
}
