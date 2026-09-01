<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OperationalHealth
{
    public function readiness(): array
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function (): void {
                $key = 'health:readiness:'.gethostname();
                $value = bin2hex(random_bytes(8));
                Cache::put($key, $value, 30);
                if (! hash_equals($value, (string) Cache::get($key))) {
                    throw new \RuntimeException('Shared cache read/write verification failed.');
                }
                Cache::forget($key);
            }),
        ];

        return [
            'status' => collect($checks)->every(fn ($check) => $check['ok']) ? 'ready' : 'unavailable',
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function status(): array
    {
        $readiness = $this->readiness();
        $checks = $readiness['checks'];
        $checks['queue'] = $this->queueStatus();
        $checks['workers'] = $this->workerStatus();
        $checks['backups'] = $this->backupStatus();

        $hasDegradation = collect($checks)->contains(fn ($check) => ! $check['ok']);

        return [
            'status' => $readiness['status'] === 'unavailable'
                ? 'unavailable'
                : ($hasDegradation ? 'degraded' : 'healthy'),
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function queueStatus(): array
    {
        return $this->check(function (): array {
            if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
                return ['connection' => config('queue.default'), 'depth' => null];
            }

            $depth = DB::table(config('queue.connections.database.table', 'jobs'))->count();
            $failed = Schema::hasTable(config('queue.failed.table', 'failed_jobs'))
                ? DB::table(config('queue.failed.table', 'failed_jobs'))->count()
                : 0;
            if ($depth > config('operations.health.max_queue_depth')) {
                throw new \RuntimeException('Queue backlog is above the configured threshold.');
            }
            if ($failed > config('operations.health.max_failed_jobs')) {
                throw new \RuntimeException('Failed jobs require operator attention.');
            }

            return ['connection' => 'database', 'depth' => $depth, 'failed' => $failed];
        });
    }

    private function workerStatus(): array
    {
        return $this->check(function (): array {
            $heartbeats = (array) Cache::get('ops:worker-heartbeats', []);
            $staleAfter = config('operations.health.worker_stale_seconds');
            $fresh = collect($heartbeats)->filter(
                fn ($timestamp) => is_numeric($timestamp) && now()->timestamp - (int) $timestamp <= $staleAfter
            );

            if ($fresh->isEmpty()) {
                throw new \RuntimeException('No fresh queue-worker heartbeat was found.');
            }

            return ['active' => $fresh->count(), 'registered' => count($heartbeats)];
        });
    }

    private function backupStatus(): array
    {
        return $this->check(function (): array {
            $disk = Storage::disk(config('operations.backup.disk'));
            $prefix = config('operations.backup.prefix');
            $manifests = collect($disk->files($prefix))
                ->filter(fn ($path) => str_ends_with($path, '.json'))
                ->sortDesc();
            $latest = $manifests->first();

            if (! $latest) {
                throw new \RuntimeException('No verified database backup manifest was found.');
            }

            $manifest = json_decode((string) $disk->get($latest), true, flags: JSON_THROW_ON_ERROR);
            $createdAt = now()->parse($manifest['created_at'] ?? null);
            if ($createdAt->diffInHours(now()) > config('operations.backup.max_age_hours')) {
                throw new \RuntimeException('The latest verified database backup is stale.');
            }

            return ['latest_manifest' => $latest, 'created_at' => $createdAt->toIso8601String()];
        });
    }

    private function check(callable $callback): array
    {
        $started = microtime(true);

        try {
            $details = $callback();

            return [
                'ok' => true,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'details' => is_array($details) ? $details : null,
            ];
        } catch (Throwable $error) {
            report($error);

            return [
                'ok' => false,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'message' => app()->isProduction() ? 'Check failed.' : $error->getMessage(),
            ];
        }
    }
}
