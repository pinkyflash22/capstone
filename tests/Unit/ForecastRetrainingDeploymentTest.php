<?php

namespace Tests\Unit;

use Tests\TestCase;

class ForecastRetrainingDeploymentTest extends TestCase
{
    public function test_render_web_process_can_write_training_artifacts(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('/var/www/html/capstone-ai', $dockerfile);
        $this->assertStringContainsString('chown -R www-data:www-data', $dockerfile);
    }

    public function test_training_has_a_deployment_safe_timeout_and_preflight(): void
    {
        $service = file_get_contents(app_path('Services/ForecastModelService.php'));
        $commands = file_get_contents(base_path('routes/console.php'));
        $config = file_get_contents(config_path('services.php'));
        $render = file_get_contents(base_path('render.yaml'));

        $this->assertStringContainsString('AI_TRAINING_TIMEOUT_SECONDS', $config);
        $this->assertStringContainsString('AI_TRAINING_TIMEOUT_SECONDS', $render);
        $this->assertStringContainsString("'training_directory_not_writable'", $service);
        $this->assertStringContainsString("'python_dependencies_missing'", $service);
        $this->assertStringContainsString("'import joblib, pandas, sklearn'", $service);
        $this->assertStringContainsString('training_directories_writable', $service);
        $this->assertStringContainsString('->timeout($trainingTimeout)', $commands);
        $this->assertStringContainsString("Cache::lock('forecast-model-training', \$trainingTimeout + 60)", $service);
        $this->assertStringContainsString("'training_already_running'", $service);
    }

    public function test_retraining_error_returns_actionable_details_to_the_forecast_page(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $job = file_get_contents(app_path('Jobs/RetrainForecastModelJob.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString('ForecastTrainingStatus::get', $controller);
        $this->assertStringContainsString("\$report['failure_summary']", $job);
        $this->assertStringContainsString('trainingResult.details', $view);
        $this->assertStringContainsString('trainingResult.code', $view);
        $this->assertStringContainsString("rtrim(\$request->getPathInfo(), '/')", $controller);
        $this->assertStringNotContainsString("rtrim(\$request->url(), '/')", $controller);
    }
}
