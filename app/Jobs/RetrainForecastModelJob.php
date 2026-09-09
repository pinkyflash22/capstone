<?php

namespace App\Jobs;

use App\Services\ForecastArtifactStore;
use App\Services\ForecastModelService;
use App\Support\ForecastTrainingStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RetrainForecastModelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(public string $trainingId, public int $requestedBy)
    {
        $this->onConnection((string) config('services.ai_prediction.training_queue_connection', 'database'));
        $this->onQueue((string) config('services.ai_prediction.training_queue', 'forecast-training'));
    }

    public function handle(ForecastModelService $forecastModelService, ForecastArtifactStore $artifactStore): void
    {
        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'running', 10, 'Starting the forecast retraining worker.', [
            'stage' => 'validating',
        ]);

        $report = $forecastModelService->retrain(
            exportDataset: true,
            progress: function (int $progress, string $stage, string $message, array $extra = []): void {
                ForecastTrainingStatus::put(
                    $this->trainingId,
                    $this->requestedBy,
                    'running',
                    $progress,
                    $message,
                    array_merge(['stage' => $stage], $extra)
                );
            }
        );
        if (! ($report['success'] ?? false)) {
            ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, $report['message'] ?? 'Forecast model training failed.', [
                'stage' => 'failed',
                'details' => $report['failure_summary'] ?? $report['exception_message'] ?? null,
                'code' => $report['status'] ?? 'training_failed',
                'diagnostics' => $report['diagnostics'] ?? [],
            ]);

            return;
        }

        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'running', 86, 'Validating the newly generated model and metadata.', [
            'stage' => 'validating_output',
        ]);

        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'running', 92, 'Saving the trained model to durable storage.', [
            'stage' => 'persisting',
        ]);
        $persistence = $artifactStore->persist();
        if (! ($persistence['success'] ?? false)) {
            ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, $persistence['message'], [
                'stage' => 'failed',
                'details' => $persistence['failure_summary'] ?? null,
                'code' => $persistence['status'] ?? 'persistence_failed',
            ]);

            return;
        }

        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'completed', 100, 'Forecast model retrained and backed up successfully.', [
            'stage' => 'completed',
            'model_metadata' => $forecastModelService->readMetadataSummary(),
            'dataset_quality' => $report['dataset_quality'] ?? null,
            'persistence' => $persistence,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, 'Forecast model retraining stopped unexpectedly.', [
            'stage' => 'failed',
            'details' => $exception?->getMessage(),
            'code' => 'job_failed',
        ]);
    }
}
