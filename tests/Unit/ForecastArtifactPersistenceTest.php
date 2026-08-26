<?php

namespace Tests\Unit;

use App\Jobs\RetrainForecastModelJob;
use App\Services\ForecastArtifactStore;
use App\Services\ForecastModelService;
use App\Support\ForecastTrainingStatus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ForecastArtifactPersistenceTest extends TestCase
{
    public function test_trained_artifacts_are_checksum_verified_and_restored_from_durable_storage(): void
    {
        Storage::fake('forecast-artifacts');
        $directory = storage_path('framework/testing/forecast-artifacts-'.uniqid());
        File::ensureDirectoryExists($directory);

        config([
            'services.ai_prediction.artifact_persistence_enabled' => true,
            'services.ai_prediction.artifact_disk' => 'forecast-artifacts',
            'services.ai_prediction.artifact_prefix' => 'models/forecast',
            'services.ai_prediction.model_path' => $directory.'/model.pkl',
            'services.ai_prediction.metadata_path' => $directory.'/metadata.json',
            'services.ai_prediction.dataset_path' => $directory.'/dataset.json',
            'services.ai_prediction.evaluation_cache_path' => $directory.'/evaluation.json',
        ]);

        File::put($directory.'/model.pkl', 'trained-model');
        File::put($directory.'/metadata.json', '{"trained_at":"2026-08-26T10:00:00+08:00"}');
        File::put($directory.'/dataset.json', '[{"record":1}]');

        $store = new ForecastArtifactStore(new ForecastModelService());
        $persisted = $store->persist();
        $this->assertTrue($persisted['success']);
        Storage::disk('forecast-artifacts')->assertExists('models/forecast/manifest.json');

        File::put($directory.'/model.pkl', 'ephemeral-old-model');
        $restored = $store->restore();

        $this->assertTrue($restored['success']);
        $this->assertSame('trained-model', File::get($directory.'/model.pkl'));

        File::deleteDirectory($directory);
    }

    public function test_background_job_publishes_progress_and_completion_metadata(): void
    {
        config([
            'cache.default' => 'array',
            'services.ai_prediction.training_status_cache_store' => 'array',
        ]);
        $trainingId = 'e260d699-07ef-4e89-b66c-b335939be213';
        $service = Mockery::mock(ForecastModelService::class);
        $service->shouldReceive('retrain')->once()->with(true)->andReturn([
            'success' => true,
            'status' => 'trained',
        ]);
        $service->shouldReceive('readMetadataSummary')->once()->andReturn(['trained_at' => '2026-08-26T10:00:00+08:00']);

        $store = Mockery::mock(ForecastArtifactStore::class);
        $store->shouldReceive('persist')->once()->andReturn([
            'success' => true,
            'status' => 'persisted',
        ]);

        (new RetrainForecastModelJob($trainingId, 42))->handle($service, $store);
        $status = ForecastTrainingStatus::get($trainingId);

        $this->assertSame('completed', $status['status']);
        $this->assertSame(100, $status['progress']);
        $this->assertSame(42, $status['user_id']);
        $this->assertSame('2026-08-26T10:00:00+08:00', $status['model_metadata']['trained_at']);
    }

    public function test_render_starts_the_forecast_worker_and_restores_artifacts(): void
    {
        $startup = file_get_contents(base_path('docker/render-start.sh'));
        $render = file_get_contents(base_path('render.yaml'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString('ai:restore-forecast-artifacts', $startup);
        $this->assertStringContainsString('queue:work', $startup);
        $this->assertStringContainsString('forecast-training', $render);
        $this->assertStringContainsString('AI_ARTIFACT_DISK', $render);
        $this->assertStringContainsString('trainingResult.progress', $view);
        $this->assertStringContainsString('trainingResult.status', $view);
    }
}
