<?php

namespace Tests\Unit;

use App\Support\PythonRuntime;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PythonRuntimeResolutionTest extends TestCase
{
    #[Test]
    public function it_resolves_an_executable_python_runtime_cross_platform(): void
    {
        $binary = PythonRuntime::resolve('definitely-not-a-python-command');

        $this->assertNotSame('definitely-not-a-python-command', $binary);
        $this->assertNotSame('', trim($binary));
    }

    #[Test]
    public function all_forecast_processes_use_the_shared_runtime_resolver(): void
    {
        $forecast = file_get_contents(app_path('Services/ForecastModelService.php'));
        $prediction = file_get_contents(app_path('Services/BarangayPredictionService.php'));
        $commands = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString('PythonRuntime::resolve(', $forecast);
        $this->assertStringContainsString('PythonRuntime::failureContext(', $forecast);
        $this->assertStringContainsString('PythonRuntime::resolve(', $prediction);
        $this->assertStringContainsString('PythonRuntime::failureContext(', $prediction);
        $this->assertStringContainsString('PythonRuntime::resolve(', $commands);
    }

    #[Test]
    public function render_startup_reports_ai_runtime_health_without_requiring_shell_access(): void
    {
        $startup = file_get_contents(base_path('docker/render-start.sh'));

        $this->assertStringContainsString('[ai-runtime] configured=', $startup);
        $this->assertStringContainsString('[ai-runtime] dependencies=ready', $startup);
        $this->assertStringContainsString('apache2-foreground', $startup);
    }
}
