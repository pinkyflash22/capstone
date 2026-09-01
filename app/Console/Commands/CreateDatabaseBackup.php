<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class CreateDatabaseBackup extends Command
{
    protected $signature = 'ops:backup-database {--verify : Verify the most recent backup instead of creating one}';

    protected $description = 'Create or verify a private object-storage PostgreSQL backup artifact';

    public function handle(): int
    {
        return $this->option('verify') ? $this->verifyLatest() : $this->createBackup();
    }

    private function createBackup(): int
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('Production backups currently require the pgsql connection.');

            return self::FAILURE;
        }

        $connection = config('database.connections.pgsql');
        $timestamp = now('UTC')->format('Ymd_His');
        $baseName = 'pyrosense_'.$timestamp.'.dump';
        $temporaryPath = storage_path('app/'.$baseName);
        $prefix = config('operations.backup.prefix');
        $artifactPath = $prefix.'/'.$baseName;
        $manifestPath = $artifactPath.'.json';

        $process = new Process([
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--username='.$connection['username'],
            '--file='.$temporaryPath,
            $connection['database'],
        ], base_path(), ['PGPASSWORD' => (string) $connection['password']]);
        $process->setTimeout(1800);

        try {
            $process->mustRun();
            $size = filesize($temporaryPath);
            $checksum = hash_file('sha256', $temporaryPath);
            if (! $size || ! $checksum) {
                throw new \RuntimeException('The generated backup is empty or unreadable.');
            }

            $disk = Storage::disk(config('operations.backup.disk'));
            $stream = fopen($temporaryPath, 'rb');
            try {
                if (! $disk->put($artifactPath, $stream)) {
                    throw new \RuntimeException('Backup upload failed.');
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $manifest = [
                'artifact' => $artifactPath,
                'created_at' => now('UTC')->toIso8601String(),
                'database_driver' => 'pgsql',
                'bytes' => $size,
                'sha256' => $checksum,
                'format' => 'pg_dump-custom',
            ];
            $disk->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->pruneExpired($disk);
            $this->info("Verified backup stored at {$artifactPath} ({$size} bytes). No secrets were written to the manifest.");

            return self::SUCCESS;
        } catch (Throwable $error) {
            report($error);
            $this->error('Database backup failed: '.$error->getMessage());

            return self::FAILURE;
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function verifyLatest(): int
    {
        try {
            $disk = Storage::disk(config('operations.backup.disk'));
            $latest = collect($disk->files(config('operations.backup.prefix')))
                ->filter(fn ($path) => str_ends_with($path, '.dump.json'))
                ->sortDesc()
                ->first();
            if (! $latest) {
                throw new \RuntimeException('No backup manifest exists.');
            }

            $manifest = json_decode((string) $disk->get($latest), true, flags: JSON_THROW_ON_ERROR);
            $stream = $disk->readStream($manifest['artifact']);
            if (! is_resource($stream)) {
                throw new \RuntimeException('Backup artifact cannot be read.');
            }
            try {
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);
                $checksum = hash_final($hash);
            } finally {
                fclose($stream);
            }
            if (! hash_equals((string) $manifest['sha256'], $checksum)) {
                throw new \RuntimeException('Backup checksum does not match its manifest.');
            }

            $this->info('Latest backup checksum verified: '.$manifest['artifact']);

            return self::SUCCESS;
        } catch (Throwable $error) {
            report($error);
            $this->error('Backup verification failed: '.$error->getMessage());

            return self::FAILURE;
        }
    }

    private function pruneExpired($disk): void
    {
        $cutoff = now()->subDays(config('operations.backup.retention_days'))->timestamp;
        foreach ($disk->files(config('operations.backup.prefix')) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
            }
        }
    }
}
