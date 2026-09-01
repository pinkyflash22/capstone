<?php

return [
    'health' => [
        'cache_ttl_seconds' => (int) env('HEALTH_CACHE_TTL_SECONDS', 15),
        'worker_stale_seconds' => (int) env('WORKER_STALE_SECONDS', 600),
        'max_failed_jobs' => (int) env('MONITOR_MAX_FAILED_JOBS', 0),
        'max_queue_depth' => (int) env('MONITOR_MAX_QUEUE_DEPTH', 500),
    ],

    'backup' => [
        'disk' => env('BACKUP_DISK', 'backups'),
        'prefix' => trim((string) env('BACKUP_PREFIX', 'backups/database'), '/'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 35),
        'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 26),
    ],

    'monitoring' => [
        'webhook_url' => env('MONITORING_WEBHOOK_URL'),
    ],

    'rollout' => [
        'mode' => env('ROLLOUT_MODE', 'open'),
        'pilot_station_ids' => array_values(array_filter(array_map(
            static fn (string $id) => filter_var(trim($id), FILTER_VALIDATE_INT),
            explode(',', (string) env('PILOT_STATION_IDS', ''))
        ))),
    ],
];
