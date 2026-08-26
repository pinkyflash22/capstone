<?php

namespace Tests\Unit;

use App\Http\Controllers\Chief\AnalyticController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class ForecastScopeUiSeparationTest extends TestCase
{
    #[Test]
    public function current_and_next_month_results_have_distinct_saved_scope_signatures(): void
    {
        $reflection = new ReflectionClass(AnalyticController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('scopeSignatureFromRawData');

        $current = $method->invoke($controller, [
            'prediction_scope' => 'current_month',
            'analysis_kind' => 'actual_analysis',
            'barangay_id' => 8,
            'prediction_context' => ['target_month_label' => 'August 2026'],
        ]);
        $next = $method->invoke($controller, [
            'prediction_scope' => 'next_month',
            'analysis_kind' => 'forecast',
            'barangay_id' => 8,
            'prediction_context' => ['target_month_label' => 'September 2026'],
        ]);

        $this->assertNotSame($current, $next);
        $this->assertSame('current_month', $current['prediction_scope']);
        $this->assertSame('next_month', $next['prediction_scope']);
        $this->assertSame('August 2026', $current['target_month_label']);
        $this->assertSame('September 2026', $next['target_month_label']);
    }

    #[Test]
    public function forecast_page_exposes_separate_period_views_and_a_non_blocking_loader(): void
    {
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString('data-forecast-view-scope="current_month"', $view);
        $this->assertStringContainsString('data-forecast-view-scope="next_month"', $view);
        $this->assertStringContainsString('activeForecastViewScope', $view);
        $this->assertStringContainsString('pointer-events: none;', $view);
        $this->assertStringContainsString('analytics-loading-card__bar', $view);
        $this->assertStringContainsString('bottom: calc(var(--mobile-bottom-nav-h', $view);
    }
}
