<?php

namespace App\Services;

use App\Support\PythonRuntime;
use App\Support\OperationalFailureMonitor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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

    private ?bool $readinessResult = null;

    public function __construct()
    {
        $this->datasetOption = (string) config('services.ai_prediction.dataset_path', 'capstone-ai/station_barangay_monthly_db.json');
        $this->modelPath = $this->resolvePath((string) config('services.ai_prediction.model_path', 'capstone-ai/fire_model.pkl'));
        $this->metadataPath = $this->resolvePath((string) config('services.ai_prediction.metadata_path', 'capstone-ai/fire_model_metadata.json'));
        $this->datasetPath = $this->resolvePath($this->datasetOption);
        $this->trainingScriptPath = $this->resolvePath((string) config('services.ai_prediction.training_script', 'capstone-ai/train_model.py'));
        $this->evaluationScriptPath = $this->resolvePath((string) config('services.ai_prediction.evaluation_script', 'capstone-ai/evaluate_model.py'));
        $this->evaluationCachePath = $this->resolvePath((string) config('services.ai_prediction.evaluation_cache_path', 'capstone-ai/fire_model_evaluation.json'));
        $this->pythonBinary = PythonRuntime::resolve(
            (string) config('services.ai_prediction.python_binary', 'python')
        );
    }

    public function ensureReady(): bool
    {
        if ($this->readinessResult !== null) {
            return $this->readinessResult;
        }

        if (! $this->needsRetraining()) {
            $this->lastTrainingReport = [
                'success' => true,
                'status' => 'ready',
                'message' => 'Forecast model files are ready.',
                'diagnostics' => $this->readinessDiagnostics(),
            ];

            return $this->readinessResult = true;
        }

        $hadUsableModel = File::exists($this->modelPath) && File::exists($this->metadataPath);
        $trainingReport = $this->retrain(exportDataset: true);

        if (($trainingReport['success'] ?? false) === true) {
            return $this->readinessResult = true;
        }

        // A refresh failure must not take forecasting offline when a previously
        // trained model is still readable. The request can safely use that model
        // while the failed retraining attempt remains visible in diagnostics.
        if ($hadUsableModel) {
            Log::warning('Forecast model refresh failed; continuing with the last valid model.', [
                'training_report' => $trainingReport,
            ]);

            $this->lastTrainingReport = array_merge($trainingReport, [
                'success' => true,
                'status' => 'stale_model_fallback',
                'message' => 'Model refresh failed, so the last valid trained model is being used.',
                'refresh_failure' => $trainingReport,
            ]);

            return $this->readinessResult = true;
        }

        return $this->readinessResult = false;
    }

    private function pythonEnvironment(): array
    {
        $pythonPath = trim((string) config('services.ai_prediction.python_path', ''));
        $environment = $pythonPath !== '' ? ['PYTHONPATH' => $pythonPath] : [];

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'APPDATA', 'LOCALAPPDATA'] as $name) {
                $value = getenv($name);
                if (is_string($value) && $value !== '') {
                    $environment[$name] = $value;
                }
            }
        }

        return $environment;
    }

    public function retrain(bool $exportDataset = true, ?callable $progress = null): array
    {
        $notify = static function (int $percentage, string $stage, string $message, array $extra = []) use ($progress): void {
            if ($progress !== null) {
                $progress($percentage, $stage, $message, $extra);
            }
        };

        $runTraining = function () use ($exportDataset, $notify) {
            $notify(12, 'validating', 'Checking the training script and writable model workspace.');
            if (! File::exists($this->trainingScriptPath)) {
                return $this->recordTrainingReport(false, 'training_script_missing', 'Forecast training script is missing.');
            }

            $unwritableDirectories = collect([
                dirname($this->datasetPath),
                dirname($this->modelPath),
                dirname($this->metadataPath),
            ])->unique()->filter(function (string $directory): bool {
                File::ensureDirectoryExists($directory);

                return ! is_writable($directory);
            })->values()->all();

            if ($unwritableDirectories !== []) {
                return $this->recordTrainingReport(
                    false,
                    'training_directory_not_writable',
                    'The server cannot write the forecast dataset or model files.',
                    ['failure_summary' => 'Grant the web process write access to: '.implode(', ', $unwritableDirectories)]
                );
            }

            try {
                $notify(18, 'validating', 'Validating the configured Python runtime and model dependencies.');
                $runtimeProbe = Process::path(base_path())
                    ->env($this->pythonEnvironment())
                    ->timeout(15)
                    ->run([$this->pythonBinary, '-c', 'import joblib, pandas, sklearn']);

                if ($runtimeProbe->failed()) {
                    $failure = PythonRuntime::failureContext($runtimeProbe, $this->pythonBinary);

                    return $this->recordTrainingReport(
                        false,
                        'python_dependencies_missing',
                        'The Python training dependencies are unavailable on this server.',
                        $failure
                    );
                }
            } catch (\Throwable $e) {
                return $this->recordTrainingReport(
                    false,
                    'python_runtime_unavailable',
                    'The configured Python runtime cannot be started.',
                    ['failure_summary' => $e->getMessage()]
                );
            }

            if (! File::exists($this->datasetPath) && ! $exportDataset) {
                return $this->recordTrainingReport(false, 'dataset_missing', 'Forecast dataset is missing.');
            }

            if ($exportDataset) {
                $notify(24, 'exporting', 'Exporting all currently available station, incident, verified-risk, weather, checklist, and mitigation records.');
                $exportExitCode = Artisan::call('ml:export-station-barangay-dataset', [
                    '--path' => $this->datasetOption,
                ]);
                $exportOutput = trim(Artisan::output());

                if ($exportExitCode !== 0) {
                    return $this->recordTrainingReport(false, 'dataset_export_failed', 'Live forecast dataset export failed.', [
                        'exit_code' => $exportExitCode,
                        'failure_summary' => $exportOutput,
                    ]);
                }
            }

            $datasetQuality = $this->readDatasetQualitySummary();
            $notify(30, 'dataset_ready', 'The latest real database export is ready for model training.', [
                'dataset_quality' => $datasetQuality,
            ]);

            try {
                $progressBuffer = '';
                $trainProcess = Process::path(base_path())
                    ->env($this->pythonEnvironment())
                    ->timeout(max(60, (int) config('services.ai_prediction.training_timeout_seconds', 240)))
                    ->run([$this->pythonBinary, $this->trainingScriptPath, $this->datasetPath], function (string $type, string $output) use (&$progressBuffer, $notify): void {
                        $progressBuffer .= $output;
                        while (($newline = strpos($progressBuffer, "\n")) !== false) {
                            $line = trim(substr($progressBuffer, 0, $newline));
                            $progressBuffer = substr($progressBuffer, $newline + 1);
                            if (! str_starts_with($line, 'PYROSENSE_PROGRESS ')) {
                                continue;
                            }

                            $payload = json_decode(substr($line, strlen('PYROSENSE_PROGRESS ')), true);
                            if (! is_array($payload)) {
                                continue;
                            }

                            $notify(
                                (int) ($payload['progress'] ?? 30),
                                (string) ($payload['stage'] ?? 'training'),
                                (string) ($payload['message'] ?? 'Training the forecast model.'),
                                ['python_progress' => true]
                            );
                        }
                    });
                $exitCode = $trainProcess->exitCode();
                $output = trim($trainProcess->output()."\n".$trainProcess->errorOutput());

                if ($trainProcess->failed()) {
                    Log::warning('Forecast model training command failed.', [
                        'exit_code' => $exitCode,
                        'output' => $output,
                        'diagnostics' => $this->readinessDiagnostics(includePaths: true),
                    ]);

                    $failureSummary = $output !== ''
                        ? mb_substr($output, -4000)
                        : 'The training command exited without diagnostic output.';

                    return $this->recordTrainingReport(false, 'training_failed', 'Forecast model training failed.', [
                        'exit_code' => $exitCode,
                        'command_output' => $failureSummary,
                        'failure_summary' => $failureSummary,
                    ]);
                }

                $ready = File::exists($this->modelPath) && File::exists($this->metadataPath);

                return $this->recordTrainingReport(
                    $ready,
                    $ready ? 'trained' : 'model_output_missing',
                    $ready
                        ? 'Forecast model retrained successfully.'
                        : 'Training finished, but model output files were not created.',
                    ['exit_code' => $exitCode, 'dataset_quality' => $datasetQuality]
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

        $trainingTimeout = max(60, (int) config('services.ai_prediction.training_timeout_seconds', 240));

        try {
            $lock = Cache::lock('forecast-model-training', $trainingTimeout + 60);
            $lockAcquired = $lock->get();
        } catch (\Throwable $e) {
            Log::warning('Forecast model lock unavailable, running training without lock.', [
                'message' => $e->getMessage(),
            ]);

            return $runTraining();
        }

        if (! $lockAcquired) {
            return $this->recordTrainingReport(
                false,
                'training_already_running',
                'Another forecast model retraining job is already running.'
            );
        }

        try {
            return $runTraining();
        } finally {
            $lock->release();
        }
    }

    private function readDatasetQualitySummary(): array
    {
        if (! File::exists($this->datasetPath)) {
            return ['record_count' => 0, 'source' => null, 'generated_at' => null];
        }

        $decoded = json_decode((string) File::get($this->datasetPath), true);
        if (! is_array($decoded)) {
            return ['record_count' => 0, 'source' => null, 'generated_at' => null];
        }

        $records = is_array($decoded['records'] ?? null) ? $decoded['records'] : $decoded;
        $years = collect($records)->pluck('year')->filter(fn ($year) => is_numeric($year))->map(fn ($year) => (int) $year);
        $barangays = collect($records)->pluck('barangay_id')->filter()->unique()->count();
        $positiveIncidentRows = collect($records)->filter(fn ($row) => (float) ($row['incident_count'] ?? 0) > 0)->count();
        $damageRows = collect($records)->filter(fn ($row) => is_numeric($row['estimated_damage'] ?? null) && (float) $row['estimated_damage'] > 0)->count();

        return [
            'source' => $decoded['source'] ?? 'database',
            'record_count' => count($records),
            'barangay_count' => $barangays,
            'year_from' => $years->isNotEmpty() ? $years->min() : null,
            'year_to' => $years->isNotEmpty() ? $years->max() : null,
            'positive_incident_rows' => $positiveIncidentRows,
            'positive_damage_rows' => $damageRows,
            'generated_at' => $decoded['generated_at'] ?? null,
        ];
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
            Log::warning('Unable to read forecast model metadata: '.$e->getMessage());

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
                'validation' => Arr::get($metrics, 'validation', []),
                'classification_report' => Arr::get($metrics, 'classification_report', []),
            ],
            'regression_metrics' => Arr::get($metrics, 'regression_metrics', []),
            'learning_performance' => $this->readLearningPerformanceSummary($decoded),
            'notes' => Arr::get($decoded, 'notes', []),
            'model_file' => File::exists($this->modelPath),
            'metadata_file' => File::exists($this->metadataPath),
        ];
    }

    public function artifactPaths(): array
    {
        return [
            'model' => $this->modelPath,
            'metadata' => $this->metadataPath,
            'dataset' => $this->datasetPath,
            'evaluation' => $this->evaluationCachePath,
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
            'python_binary' => $this->pythonBinary,
            'training_directories_writable' => collect([
                dirname($this->datasetPath),
                dirname($this->modelPath),
                dirname($this->metadataPath),
            ])->unique()->every(fn (string $directory) => is_dir($directory) && is_writable($directory)),
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
                ->env($this->pythonEnvironment())
                ->timeout(60)
                ->run([
                    $this->pythonBinary,
                    $this->relativeEvaluationScriptPath(),
                    '--dataset',
                    $this->datasetOption,
                ]);

            if ($process->failed()) {
                OperationalFailureMonitor::record('model_evaluation', $process->errorOutput() ?: 'Evaluation process failed.');
                Log::warning('Forecast model evaluation artifact generation failed.', array_merge(
                    PythonRuntime::failureContext($process, $this->pythonBinary),
                    [
                        'evaluation_script' => $this->evaluationScriptPath,
                        'dataset' => $this->datasetPath,
                        'evaluation_cache' => $this->evaluationCachePath,
                    ]
                ));

                return $fallback;
            }

            $generated = json_decode(trim($process->output()), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($generated)
                || ($generated['trained_at'] ?? null) !== Arr::get($metadata, 'trained_at')) {
                Log::warning('Forecast model evaluation returned an invalid or stale payload.');

                return $fallback;
            }

            try {
                Cache::put($this->evaluationRuntimeCacheKey($metadata), $generated, now()->addHours(6));
            } catch (\Throwable $runtimeCacheException) {
                OperationalFailureMonitor::record('model_evaluation_cache', $runtimeCacheException->getMessage());
                Log::info('Unable to retain runtime model-evaluation cache.', [
                    'message' => $runtimeCacheException->getMessage(),
                ]);
            }

            try {
                File::ensureDirectoryExists(dirname($this->evaluationCachePath));
                if (is_writable(dirname($this->evaluationCachePath))) {
                    File::put(
                        $this->evaluationCachePath,
                        json_encode($generated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    );
                } else {
                    OperationalFailureMonitor::record(
                        'model_evaluation_cache',
                        'Filesystem evaluation-cache directory is not writable.'
                    );
                    Log::info('Skipping filesystem model-evaluation cache; directory is not writable.', [
                        'evaluation_cache' => $this->evaluationCachePath,
                    ]);
                }
            } catch (\Throwable $cacheWriteException) {
                OperationalFailureMonitor::record('model_evaluation_cache', $cacheWriteException->getMessage());
                Log::info('Skipping filesystem model-evaluation cache; runtime cache remains available.', [
                    'message' => $cacheWriteException->getMessage(),
                ]);
            }

            return array_replace_recursive($fallback, $generated);
        } catch (\Throwable $e) {
            OperationalFailureMonitor::record('model_evaluation', $e->getMessage());
            Log::warning('Forecast model evaluation artifact generation threw an exception.', [
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }

        return $fallback;
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
        try {
            $runtimeCached = Cache::get($this->evaluationRuntimeCacheKey($metadata));
            if (is_array($runtimeCached)
                && ($runtimeCached['trained_at'] ?? null) === Arr::get($metadata, 'trained_at')) {
                return $runtimeCached;
            }
        } catch (\Throwable $e) {
            OperationalFailureMonitor::record('model_evaluation_cache', $e->getMessage());
            Log::info('Runtime model-evaluation cache is unavailable.', [
                'message' => $e->getMessage(),
            ]);
        }

        if (! File::exists($this->evaluationCachePath)) {
            return null;
        }

        try {
            $decoded = json_decode(File::get($this->evaluationCachePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            OperationalFailureMonitor::record('model_evaluation_cache', $e->getMessage());
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

    private function evaluationRuntimeCacheKey(array $metadata): string
    {
        return 'forecast-model:evaluation:'.hash('sha256', (string) Arr::get($metadata, 'trained_at', 'unknown'));
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
