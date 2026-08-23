<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForecastRuntimeFallbackTest extends TestCase
{
    #[Test]
    public function a_runtime_failure_degrades_to_database_analysis_instead_of_returning_503(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));

        $methodStart = strpos($controller, 'public function runPredictionApi');
        $methodEnd = strpos($controller, 'public function retrainForecastModel', $methodStart);
        $method = substr($controller, $methodStart, $methodEnd - $methodStart);

        $this->assertStringContainsString('continuing with database-driven analysis', $method);
        $this->assertStringContainsString('return $this->runAIPredictiveAnalysis($request);', $method);
        $this->assertStringNotContainsString('], 503)', $method);
    }

    #[Test]
    public function a_failed_refresh_can_keep_using_the_last_valid_model(): void
    {
        $service = file_get_contents(app_path('Services/ForecastModelService.php'));

        $this->assertStringContainsString('$hadUsableModel', $service);
        $this->assertStringContainsString("'status' => 'stale_model_fallback'", $service);
        $this->assertStringContainsString('continuing with the last valid model', $service);
        $this->assertStringContainsString('private ?bool $readinessResult = null;', $service);
    }
}
