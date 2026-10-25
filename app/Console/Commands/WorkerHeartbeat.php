<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WorkerHeartbeat extends Command
{
    protected $signature = 'ops:worker-heartbeat {name} {connection?} {queues?} {state?}';

    protected $description = 'Publish a shared queue-worker heartbeat';

    public function handle(): int
    {
        $name = preg_replace('/[^a-zA-Z0-9_.-]/', '-', (string) $this->argument('name'));
        $heartbeat = [
            'at' => now()->timestamp,
            'connection' => $this->argument('connection') ?: config('queue.default'),
            'queues' => array_values(array_filter(array_map('trim', explode(',', (string) ($this->argument('queues') ?: 'default'))))),
            'state' => $this->argument('state') ?: 'polling',
        ];
        Cache::lock('ops:worker-heartbeats:lock', 10)->block(3, function () use ($name, $heartbeat): void {
            $heartbeats = (array) Cache::get('ops:worker-heartbeats', []);
            $heartbeats[$name] = $heartbeat;
            Cache::put('ops:worker-heartbeats', $heartbeats, now()->addDay());
        });

        return self::SUCCESS;
    }
}
