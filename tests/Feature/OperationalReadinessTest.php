<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_readiness_probe_checks_database_and_shared_cache(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJsonStructure(['status', 'checked_at'])
            ->assertJsonPath('status', 'ready');
    }

    public function test_backup_checksum_verification_accepts_an_intact_artifact(): void
    {
        Storage::fake('backups');
        config([
            'operations.backup.disk' => 'backups',
            'operations.backup.prefix' => 'backups/database',
        ]);

        $artifact = 'backups/database/pyrosense_20260831_120000.dump';
        $contents = 'safe recovery fixture';
        Storage::disk('backups')->put($artifact, $contents);
        Storage::disk('backups')->put($artifact.'.json', json_encode([
            'artifact' => $artifact,
            'created_at' => now()->toIso8601String(),
            'bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'format' => 'pg_dump-custom',
        ], JSON_THROW_ON_ERROR));

        $this->artisan('ops:backup-database', ['--verify' => true])
            ->expectsOutputToContain('Latest backup checksum verified')
            ->assertSuccessful();
    }

    public function test_backup_checksum_verification_rejects_corruption(): void
    {
        Storage::fake('backups');
        config([
            'operations.backup.disk' => 'backups',
            'operations.backup.prefix' => 'backups/database',
        ]);

        $artifact = 'backups/database/pyrosense_20260831_120000.dump';
        Storage::disk('backups')->put($artifact, 'corrupted');
        Storage::disk('backups')->put($artifact.'.json', json_encode([
            'artifact' => $artifact,
            'created_at' => now()->toIso8601String(),
            'sha256' => hash('sha256', 'original'),
        ], JSON_THROW_ON_ERROR));

        $this->artisan('ops:backup-database', ['--verify' => true])->assertFailed();
    }
}
