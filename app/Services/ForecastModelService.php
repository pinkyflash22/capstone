<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class ForecastModelService
{
    private string $modelPath;
    private string $metadataPath;

    public function __construct()
    {
        $this->modelPath = base_path('capstone-ai/fire_model.pkl');
        $this->metadataPath = base_path('capstone-ai/fire_model_metadata.json');
    }

    public function ensureReady(): bool
    {
        if (! $this->needsRetraining()) {
            return true;
        }

        try {
            $exitCode = Artisan::call('ai:train-forecast-model', [
                '--dataset' => 'capstone-ai/station_barangay_monthly_db.json',
                '--export' => true,
            ]);

            if ($exitCode !== 0) {
                Log::warning('Forecast model training command failed.', [
                    'output' => Artisan::output(),
                ]);

                return false;
            }

            return File::exists($this->modelPath) && File::exists($this->metadataPath);
        } catch (\Throwable $e) {
            Log::warning('Unable to prepare forecast model automatically: ' . $e->getMessage());

            return false;
        }
    }

    public function needsRetraining(): bool
    {
        if (! File::exists($this->modelPath) || ! File::exists($this->metadataPath)) {
            return true;
        }

        $metadataMtime = File::lastModified($this->metadataPath);
        $latestDataUpdate = $this->resolveLatestDataUpdateTimestamp();

        if ($latestDataUpdate === null) {
            return false;
        }

        return $latestDataUpdate > $metadataMtime;
    }

    public function readMetadataSummary(): array
    {
        if (! File::exists($this->metadataPath)) {
            return [
                'model_ready' => false,
                'model_path' => $this->modelPath,
                'metadata_path' => $this->metadataPath,
            ];
        }

        try {
            $decoded = json_decode(File::get($this->metadataPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Unable to read forecast model metadata: ' . $e->getMessage());

            return [
                'model_ready' => File::exists($this->modelPath),
                'model_path' => $this->modelPath,
                'metadata_path' => $this->metadataPath,
                'metadata_error' => $e->getMessage(),
            ];
        }

        $metrics = Arr::get($decoded, 'metrics', []);

        return [
            'model_ready' => File::exists($this->modelPath),
            'dataset_kind' => Arr::get($decoded, 'dataset_kind'),
            'dataset_path' => Arr::get($decoded, 'dataset_path'),
            'target_column' => Arr::get($decoded, 'target_column'),
            'target_description' => Arr::get($decoded, 'target_description'),
            'records_used' => Arr::get($decoded, 'records_used'),
            'trained_at' => Arr::get($decoded, 'trained_at'),
            'feature_count' => count(Arr::get($decoded, 'feature_columns', [])),
            'top_global_signals' => collect(Arr::get($decoded, 'global_feature_importance', []))
                ->take(5)
                ->map(function ($item) {
                    return [
                        'feature' => $item['feature'] ?? null,
                        'importance' => isset($item['importance']) ? round((float) $item['importance'], 4) : null,
                    ];
                })
                ->values()
                ->all(),
            'metrics' => [
                'accuracy' => Arr::get($metrics, 'accuracy'),
                'roc_auc' => Arr::get($metrics, 'roc_auc'),
                'precision' => Arr::get($metrics, 'precision'),
                'recall' => Arr::get($metrics, 'recall'),
                'f1_score' => Arr::get($metrics, 'f1_score'),
                'brier_score' => Arr::get($metrics, 'brier_score'),
                'cross_val_roc_auc_mean' => Arr::get($metrics, 'cross_val_roc_auc_mean'),
            ],
            'regression_metrics' => Arr::get($metrics, 'regression_metrics', []),
            'notes' => Arr::get($decoded, 'notes', []),
            'model_path' => $this->modelPath,
            'metadata_path' => $this->metadataPath,
        ];
    }

    private function resolveLatestDataUpdateTimestamp(): ?int
    {
        $timestamps = [];

        foreach (['historical_data', 'historical_fire_incidents', 'risk_areas', 'incidents'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $latest = DB::table($table)->max('updated_at');
            if ($latest) {
                try {
                    $timestamps[] = strtotime((string) $latest);
                } catch (\Throwable $e) {
                    // Ignore malformed timestamps.
                }
            }
        }

        $timestamps = array_filter($timestamps);

        return empty($timestamps) ? null : max($timestamps);
    }
}
