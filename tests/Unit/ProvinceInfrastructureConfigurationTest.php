<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProvinceInfrastructureConfigurationTest extends TestCase
{
    public function test_render_uses_shared_state_readiness_and_dedicated_workers(): void
    {
        $blueprint = file_get_contents(dirname(__DIR__, 2).'/render.yaml');

        $this->assertStringContainsString('healthCheckPath: /health/ready', $blueprint);
        $this->assertStringContainsString('type: keyvalue', $blueprint);
        $this->assertStringContainsString('SESSION_DRIVER', $blueprint);
        $this->assertStringContainsString('value: redis', $blueprint);
        $this->assertStringContainsString('pyrosense-general-worker', $blueprint);
        $this->assertStringContainsString('pyrosense-forecast-worker', $blueprint);
        $this->assertStringContainsString('pyrosense-database-backup', $blueprint);
        $this->assertStringContainsString('pyrosense-operations-monitor', $blueprint);
    }

    public function test_worker_recycles_and_publishes_heartbeats(): void
    {
        $worker = file_get_contents(dirname(__DIR__, 2).'/docker/render-worker.sh');

        $this->assertStringContainsString('ops:worker-heartbeat', $worker);
        $this->assertStringContainsString('--max-jobs=', $worker);
        $this->assertStringContainsString('--max-time=', $worker);
        $this->assertStringContainsString('--backoff=', $worker);
    }
}
