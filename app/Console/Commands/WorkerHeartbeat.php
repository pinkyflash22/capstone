<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WorkerHeartbeat extends Command
{
    protected $signature = 'ops:worker-heartbeat {name}';

    protected $description = 'Publish a shared queue-worker heartbeat';

    public function handle(): int
    {
        $name = preg_replace('/[^a-zA-Z0-9_.-]/', '-', (string) $this->argument('name'));
        Cache::lock('ops:worker-heartbeats:lock', 10)->block(3, function () use ($name): void {
            $heartbeats = (array) Cache::get('ops:worker-heartbeats', []);
            $heartbeats[$name] = now()->timestamp;
            Cache::put('ops:worker-heartbeats', $heartbeats, now()->addDay());
        });

        return self::SUCCESS;
    }
}
