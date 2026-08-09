<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Arr;

class ForecastModelService
{
    private string $modelPath;
    private string $metadataPath;
    private string $datasetPath;
    private string $datasetOption;
    private string $trainingScriptPath;
    private string $evaluationScriptPath;
    private string $evaluationCachePath;
    private string $pythonBinary;
    private array $lastTrainingReport = [];

    public function __construct()
    {
        $this->datasetOption = (string) config('services.ai_prediction.dataset_path', 'capstone-ai/station_barangay_monthly_db.json');
        $this->modelPath = $this->resolvePath((string) config('services.ai_prediction.model_path', 'capstone-ai/fire_model.pkl'));
        $this->metadataPath = $this->resolvePath((string) config('services.ai_prediction.metadata_path', 'capstone-ai/fire_model_metadata.json'));
        $this->datasetPath = $this->resolvePath($this->datasetOption);
        $this->trainingScriptPath = $this->resolvePath((string) config('services.ai_prediction.training_script', 'capstone-ai/train_model.py'));
        $this->evaluationScriptPath = $this->resolvePath((string) config('services.ai_prediction.evaluation_script', 'capstone-ai/evaluate_model.py'));
        $this->evaluationCachePath = $this->resolvePath((string) config('services.ai_prediction.evaluation_cache_path', 'capstone-ai/fire_model_evaluation.json'));
        $this->pythonBinary = (string) config('services.ai_prediction.python_binary', 'python');
    }

    public function ensureReady(): bool
    {
        if (! $this->needsRetraining()) {
            $this->lastTrainingReport = [
                'success' => true,
                'status' => 'ready',
                'message' => 'Forecast model files are ready.',
                'diagnostics' => $this->readinessDiagnostics(),
            ];

            return true;
        }

        return $this->retrain(exportDataset: true)['success'] === true;
    }

    public function retrain(bool $exportDataset = true): array
    {
        $runTraining = function () use ($exportDataset) {
            if (! File::exists($this->trainingScriptPath)) {
                return $this->recordTrainingReport(false, 'training_script_missing', 'Forecast training script is missing.');
            }

            if (! File::exists($this->datasetPath) && ! $exportDataset) {
                return $this->recordTrainingReport(false, 'dataset_missing', 'Forecast dataset is missing.');
            }

            $parameters = ['--dataset' => $this->datasetOption];
            if ($exportDataset) {
                $parameters['--export'] = true;
            }

            try {
                $exitCode = Artisan::call('ai:train-forecast-model', $parameters);
                $output = trim(Artisan::output());

                if ($exitCode !== 0) {
                    Log::warning('Forecast model training command failed.', [
                        'exit_code' => $exitCode,
                        'output' => $output,
                        'diagnostics' => $this->readinessDiagnostics(includePaths: true),
                    ]);

                    return $this->recordTrainingReport(false, 'training_failed', 'Forecast model training failed. Check server logs for command output.', [
                        'exit_code' => $exitCode,
                    ]);
                }

                $ready = File::exists($this->modelPath) && File::exists($this->metadataPath);

                return $this->recordTrainingReport(
                    $ready,
                    $ready ? 'trained' : 'model_output_missing',
                    $ready
                        ? 'Forecast model retrained successfully.'
                        : 'Training finished, but model output files were not created.',
                    ['exit_code' => $exitCode]
                );
            } catch (\Throwable $e) {
                Log::warning('Unable to prepare forecast model automatically.', [
                    'message' => $e->getMessage(),
                    'diagnostics' => $this->readinessDiagnostics(includePaths: true),
                ]);

                return $this->recordTrainingReport(
                    false,
                    'training_exception',
                    'Forecast model could not be prepared on this server.',
                    ['exception_message' => $e->getMessage()]
                );
            }
        };

        try {
            return Cache::lock('forecast-model-training', 180)->block(5, $runTraining);
        } catch (\Throwable $e) {
            Log::warning('Forecast model lock unavailable, running training without lock.', [
                'message' => $e->getMessage(),
            ]);

            return $runTraining();
        }
    }

    public function lastTrainingReport(): array
    {
        return $this->lastTrainingReport ?: [
            'success' => false,
            'status' => 'not_checked',
            'message' => 'Forecast model readiness has not been checked yet.',
            'diagnostics' => $this->readinessDiagnostics(),
        ];
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
                'model_file' => File::exists($this->modelPath),
                'metadata_file' => File::exists($this->metadataPath),
            ];
        }

        try {
            $decoded = $this->readMetadataPayload();
        } catch (\Throwable $e) {
            Log::warning('Unable to read forecast model metadata: ' . $e->getMessage());

            return [
                'model_ready' => File::exists($this->modelPath),
                'model_file' => File::exists($this->modelPath),
                'metadata_file' => File::exists($this->metadataPath),
                'metadata_error' => 'Metadata could not be read.',
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
            'feature_columns' => collect(Arr::get($decoded, 'feature_columns', []))
                ->filter(fn ($feature) => is_string($feature) && trim($feature) !== '')
                ->values()
                ->all(),
            'global_feature_importance' => collect(Arr::get($decoded, 'global_feature_importance', []))
                ->take(10)
                ->map(function ($item) {
                    return [
                        'feature' => $item['feature'] ?? null,
                        'importance' => isset($item['importance']) ? round((float) $item['importance'], 4) : null,
                    ];
                })
                ->values()
                ->all(),
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
                'train_rows' => Arr::get($metrics, 'train_rows'),
                'test_rows' => Arr::get($metrics, 'test_rows'),
                'accuracy' => Arr::get($metrics, 'accuracy'),
                'roc_auc' => Arr::get($metrics, 'roc_auc'),
                'precision' => Arr::get($metrics, 'precision'),
                'recall' => Arr::get($metrics, 'recall'),
                'f1_score' => Arr::get($metrics, 'f1_score'),
                'brier_score' => Arr::get($metrics, 'brier_score'),
                'cross_val_roc_auc_mean' => Arr::get($metrics, 'cross_val_roc_auc_mean'),
                'cross_val_roc_auc_scores' => Arr::get($metrics, 'cross_val_roc_auc_scores', []),
                'classification_report' => Arr::get($metrics, 'classification_report', []),
            ],
            'regression_metrics' => Arr::get($metrics, 'regression_metrics', []),
            'learning_performance' => $this->readLearningPerformanceSummary($decoded),
            'notes' => Arr::get($decoded, 'notes', []),
            'model_file' => File::exists($this->modelPath),
            'metadata_file' => File::exists($this->metadataPath),
        ];
    }

    public function readinessDiagnostics(bool $includePaths = false): array
    {
        $diagnostics = [
            'model_file' => File::exists($this->modelPath),
            'metadata_file' => File::exists($this->metadataPath),
            'dataset_file' => File::exists($this->datasetPath),
            'training_script' => File::exists($this->trainingScriptPath),
            'evaluation_script' => File::exists($this->evaluationScriptPath),
            'evaluation_cache_file' => File::exists($this->evaluationCachePath),
            'python_configured' => filled($this->pythonBinary),
            'needs_retraining' => $this->needsRetraining(),
        ];

        if ($includePaths) {
            $diagnostics['paths'] = [
                'model' => $this->modelPath,
                'metadata' => $this->metadataPath,
                'dataset' => $this->datasetPath,
                'training_script' => $this->trainingScriptPath,
                'evaluation_script' => $this->evaluationScriptPath,
                'evaluation_cache' => $this->evaluationCachePath,
                'python_binary' => $this->pythonBinary,
            ];
        }

        return $diagnostics;
    }

    private function recordTrainingReport(bool $success, string $status, string $message, array $extra = []): array
    {
        $this->lastTrainingReport = array_merge([
            'success' => $success,
            'status' => $status,
            'message' => $message,
            'diagnostics' => $this->readinessDiagnostics(),
        ], $extra);

        return $this->lastTrainingReport;
    }

    private function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    private function readMetadataPayload(): array
    {
        return json_decode(File::get($this->metadataPath), true, 512, JSON_THROW_ON_ERROR);
    }

    private function readLearningPerformanceSummary(array $metadata): array
    {
        $fallback = $this->buildLearningPerformanceFallback($metadata);
        $cached = $this->readCachedEvaluationPayload($metadata);

        if ($cached !== null) {
            return array_replace_recursive($fallback, $cached);
        }

        if (! File::exists($this->evaluationScriptPath) || ! File::exists($this->modelPath)) {
            return $fallback;
        }

        try {
            $process = Process::path(base_path())
                ->timeout(60)
                ->run([
                    $this->pythonBinary,
                    $this->relativeEvaluationScriptPath(),
                    '--dataset',
                    $this->datasetOption,
                    '--output',
                    $this->evaluationCachePath,
                ]);

            if ($process->failed()) {
                Log::warning('Forecast model evaluation artifact generation failed.', [
                    'error' => $process->errorOutput(),
                    'output' => $process->output(),
                    'evaluation_script' => $this->evaluationScriptPath,
                ]);

                return $fallback;
            }
        } catch (\Throwable $e) {
            Log::warning('Forecast model evaluation artifact generation threw an exception.', [
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }

        return $this->readCachedEvaluationPayload($metadata) ?? $fallback;
    }

    private function buildLearningPerformanceFallback(array $metadata): array
    {
        $metrics = Arr::get($metadata, 'metrics', []);
        $regressionMetrics = Arr::get($metrics, 'regression_metrics', []);

        return [
            'available' => false,
            'partial' => true,
            'trained_at' => Arr::get($metadata, 'trained_at'),
            'model_type' => 'RandomForestClassifier + RandomForestRegressor',
            'supports_epoch_learning_curve' => false,
            'feature_importance' => collect(Arr::get($metadata, 'global_feature_importance', []))
                ->take(10)
                ->values()
                ->all(),
            'classification_performance' => [
                'train_rows' => Arr::get($metrics, 'train_rows'),
                'test_rows' => Arr::get($metrics, 'test_rows'),
                'accuracy' => Arr::get($metrics, 'accuracy'),
                'roc_auc' => Arr::get($metrics, 'roc_auc'),
                'precision' => Arr::get($metrics, 'precision'),
                'recall' => Arr::get($metrics, 'recall'),
                'f1_score' => Arr::get($metrics, 'f1_score'),
                'brier_score' => Arr::get($metrics, 'brier_score'),
                'classification_report' => Arr::get($metrics, 'classification_report', []),
            ],
            'cross_validation' => [
                'metric' => 'ROC AUC',
                'mean' => Arr::get($metrics, 'cross_val_roc_auc_mean'),
                'scores' => Arr::get($metrics, 'cross_val_roc_auc_scores', []),
            ],
            'regression_performance' => $regressionMetrics,
            'actual_vs_predicted' => null,
            'residual_pattern' => null,
            'warning' => 'Row-level model evaluation points are not cached yet.',
        ];
    }

    private function readCachedEvaluationPayload(array $metadata): ?array
    {
        if (! File::exists($this->evaluationCachePath)) {
            return null;
        }

        try {
            $decoded = json_decode(File::get($this->evaluationCachePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Unable to read cached forecast model evaluation artifact.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        if (($decoded['trained_at'] ?? null) !== Arr::get($metadata, 'trained_at')) {
            return null;
        }

        return $decoded;
    }

    private function relativeEvaluationScriptPath(): string
    {
        return (string) config('services.ai_prediction.evaluation_script', 'capstone-ai/evaluate_model.py');
    }

    private function resolveLatestDataUpdateTimestamp(): ?int
    {
        $timestamps = [];

        foreach (['historical_fire_incidents', 'risk_areas', 'incidents'] as $table) {
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
