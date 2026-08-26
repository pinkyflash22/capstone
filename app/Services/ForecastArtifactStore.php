<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ForecastArtifactStore
{
    public function __construct(private readonly ForecastModelService $forecastModelService)
    {
    }

    public function persist(): array
    {
        if (! $this->enabled()) {
            return ['success' => true, 'status' => 'disabled', 'message' => 'Forecast artifact persistence is disabled.'];
        }

        try {
            $disk = Storage::disk($this->disk());
            $manifest = [
                'version' => 1,
                'persisted_at' => now()->toIso8601String(),
                'artifacts' => [],
            ];

            foreach ($this->forecastModelService->artifactPaths() as $name => $localPath) {
                if (! File::exists($localPath)) {
                    if (in_array($name, ['model', 'metadata'], true)) {
                        throw new RuntimeException("Required forecast artifact is missing: {$name}.");
                    }

                    continue;
                }

                $contents = File::get($localPath);
                $remotePath = $this->remotePath($name);
                if (! $disk->put($remotePath, $contents)) {
                    throw new RuntimeException("Unable to upload forecast artifact: {$name}.");
                }

                $manifest['artifacts'][$name] = [
                    'path' => $remotePath,
                    'sha256' => hash('sha256', $contents),
                    'bytes' => strlen($contents),
                ];
            }

            if (! $disk->put($this->manifestPath(), json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                throw new RuntimeException('Unable to upload the forecast artifact manifest.');
            }

            return [
                'success' => true,
                'status' => 'persisted',
                'message' => 'Forecast artifacts were saved to durable storage.',
                'disk' => $this->disk(),
                'artifact_count' => count($manifest['artifacts']),
            ];
        } catch (\Throwable $exception) {
            Log::error('Forecast artifact persistence failed.', ['message' => $exception->getMessage()]);

            return [
                'success' => false,
                'status' => 'persistence_failed',
                'message' => 'The model was trained, but its durable backup failed.',
                'failure_summary' => $exception->getMessage(),
            ];
        }
    }

    public function restore(): array
    {
        if (! $this->enabled()) {
            return ['success' => true, 'status' => 'disabled', 'message' => 'Forecast artifact restoration is disabled.'];
        }

        try {
            $disk = Storage::disk($this->disk());
            if (! $disk->exists($this->manifestPath())) {
                return ['success' => true, 'status' => 'not_found', 'message' => 'No durable forecast artifact backup exists yet.'];
            }

            $manifest = json_decode($disk->get($this->manifestPath()), true, 512, JSON_THROW_ON_ERROR);
            $availablePaths = $this->forecastModelService->artifactPaths();
            $restored = [];

            foreach (($manifest['artifacts'] ?? []) as $name => $artifact) {
                if (! isset($availablePaths[$name], $artifact['path'], $artifact['sha256'])) {
                    continue;
                }

                $contents = $disk->get($artifact['path']);
                if (! hash_equals((string) $artifact['sha256'], hash('sha256', $contents))) {
                    throw new RuntimeException("Checksum verification failed for forecast artifact: {$name}.");
                }

                $localPath = $availablePaths[$name];
                File::ensureDirectoryExists(dirname($localPath));
                File::replace($localPath, $contents);
                $restored[] = $name;
            }

            if (! in_array('model', $restored, true) || ! in_array('metadata', $restored, true)) {
                throw new RuntimeException('The durable forecast backup is incomplete.');
            }

            return [
                'success' => true,
                'status' => 'restored',
                'message' => 'Forecast artifacts were restored from durable storage.',
                'artifact_count' => count($restored),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Forecast artifact restoration failed.', ['message' => $exception->getMessage()]);

            return [
                'success' => false,
                'status' => 'restore_failed',
                'message' => 'Durable forecast artifacts could not be restored.',
                'failure_summary' => $exception->getMessage(),
            ];
        }
    }

    private function enabled(): bool
    {
        return (bool) config('services.ai_prediction.artifact_persistence_enabled', true);
    }

    private function disk(): string
    {
        return (string) config('services.ai_prediction.artifact_disk', config('filesystems.default'));
    }

    private function prefix(): string
    {
        return trim((string) config('services.ai_prediction.artifact_prefix', 'ai/forecast-model'), '/');
    }

    private function manifestPath(): string
    {
        return $this->prefix().'/manifest.json';
    }

    private function remotePath(string $name): string
    {
        $extension = match ($name) {
            'model' => 'pkl',
            default => 'json',
        };

        return $this->prefix().'/'.$name.'.'.$extension;
    }
}
