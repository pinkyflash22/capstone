<?php

namespace App\Console\Commands;

use App\Support\OperationalHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorOperations extends Command
{
    protected $signature = 'ops:monitor';

    protected $description = 'Check operational dependencies and emit alerts for degraded service';

    public function handle(OperationalHealth $health): int
    {
        $result = $health->status();
        $logContext = ['operational_health' => $result];

        if ($result['status'] === 'healthy') {
            Log::info('Operational health check passed.', $logContext);
            $this->info('Operational health is healthy.');

            return self::SUCCESS;
        }

        $cooldownMinutes = max(5, (int) config('operations.monitoring.alert_cooldown_minutes', 30));
        try {
            $shouldAlert = Cache::add(
                'ops:monitor:degradation-alert',
                $result['checked_at'],
                now()->addMinutes($cooldownMinutes)
            );
        } catch (\Throwable) {
            // If the cache itself is degraded, do not let alert suppression
            // prevent the health notification from being emitted.
            $shouldAlert = true;
        }

        if ($shouldAlert) {
            Log::critical('Operational health check detected degradation.', $logContext);
            $webhook = config('operations.monitoring.webhook_url');
            if ($webhook) {
                Http::timeout(10)->retry(2, 500)->post($webhook, [
                    'event' => 'pyrosense.operational_health',
                    'status' => $result['status'],
                    'checked_at' => $result['checked_at'],
                    'checks' => $result['checks'],
                ])->throw();
            }
        } else {
            Log::warning('Operational degradation persists; duplicate alert suppressed.', [
                'status' => $result['status'],
                'alert_cooldown_minutes' => $cooldownMinutes,
            ]);
        }
        $this->error('Operational health is '.$result['status'].'.');

        return self::FAILURE;
    }
}
