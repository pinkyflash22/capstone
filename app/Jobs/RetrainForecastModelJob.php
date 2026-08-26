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
        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'running', 15, 'Preparing the latest forecast dataset.');

        $report = $forecastModelService->retrain(exportDataset: true);
        if (! ($report['success'] ?? false)) {
            ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, $report['message'] ?? 'Forecast model training failed.', [
                'details' => $report['failure_summary'] ?? $report['exception_message'] ?? null,
                'code' => $report['status'] ?? 'training_failed',
                'diagnostics' => $report['diagnostics'] ?? [],
            ]);

            return;
        }

        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'running', 90, 'Saving the trained model to durable storage.');
        $persistence = $artifactStore->persist();
        if (! ($persistence['success'] ?? false)) {
            ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, $persistence['message'], [
                'details' => $persistence['failure_summary'] ?? null,
                'code' => $persistence['status'] ?? 'persistence_failed',
            ]);

            return;
        }

        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'completed', 100, 'Forecast model retrained and backed up successfully.', [
            'model_metadata' => $forecastModelService->readMetadataSummary(),
            'persistence' => $persistence,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        ForecastTrainingStatus::put($this->trainingId, $this->requestedBy, 'failed', 100, 'Forecast model retraining stopped unexpectedly.', [
            'details' => $exception?->getMessage(),
            'code' => 'job_failed',
        ]);
    }
}
