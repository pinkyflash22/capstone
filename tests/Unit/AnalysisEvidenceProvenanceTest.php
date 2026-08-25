<?php

namespace Tests\Unit;

use App\Http\Controllers\Chief\AnalyticController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class AnalysisEvidenceProvenanceTest extends TestCase
{
    #[Test]
    public function it_keeps_fire_weather_risk_and_mitigation_evidence_separate(): void
    {
        $reflection = new ReflectionClass(AnalyticController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('buildAnalysisEvidenceProvenance');

        $evidence = $method->invoke(
            $controller,
            'Test Fire Station',
            30,
            '3',
            'current_month',
            ['target_period_label' => 'August 2026', 'current_month_fire_incidents' => 2],
            [[
                'barangay_id' => 5,
                'barangay_name' => 'Barangay A',
                'probability_percent' => 64.5,
                'risk_level' => 'moderate',
                'prediction_source' => 'trained_model',
                'drivers' => [['summary' => 'Verified hazards increased pressure.']],
                'input' => [
                    'incident_count' => 2,
                    'positive_historical_record_count' => 2,
                    'historical_weather_observation_count' => 15,
                    'historical_source_row_count' => 17,
                    'current_month_fire_incidents' => 2,
                    'verified_risk_count' => 1,
                    'verified_hazard_count' => 3,
                    'high_assessment_count' => 2,
                    'risk_area_pressure_signal' => 8.4,
                    'near_hydrant' => 1,
                ],
            ]],
            ['avg_temp_c' => 32, 'avg_humidity' => 58, 'avg_wind_kph' => 12, 'dominant_condition' => 'Sunny'],
            ['item_followed' => 4, 'item_total' => 5, 'completed_mitigations' => 2, 'total_mitigation_actions' => 3],
            ['Dry conditions'],
            2,
            2,
            ['base_score' => 60, 'score' => 54, 'level' => 'High', 'mitigation_reduction_points' => 6],
            ['output' => ['probability_percent' => 64.5, 'predicted_incident_count' => 3]],
        );

        $this->assertSame(2, $evidence['counts']['historical_fire_baseline']);
        $this->assertSame(15, $evidence['counts']['weather_observation_rows']);
        $this->assertSame(1, $evidence['counts']['verified_risks']);
        $this->assertSame(3, $evidence['counts']['verified_hazards']);
        $this->assertSame(32, $evidence['weather']['temperature_c']);
        $this->assertSame(6.0, $evidence['mitigation']['risk_reduction_points']);
        $this->assertSame('trained_model', $evidence['barangays'][0]['prediction_source']);
    }

    #[Test]
    public function analytics_ui_exposes_the_saved_calculation_evidence_responsively(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString("'evidence_provenance' => \$evidenceProvenance", $controller);
        $this->assertStringContainsString("\$weatherContext['avg_humidity']", $controller);
        $this->assertStringContainsString("\$weatherContext['avg_wind_kph']", $controller);
        $this->assertStringContainsString("\$weatherContext['dominant_condition']", $controller);
        $this->assertStringContainsString('id="analysisEvidenceBtn"', $view);
        $this->assertStringContainsString('id="analysisEvidenceModal"', $view);
        $this->assertStringContainsString('function openAnalysisEvidenceModal()', $view);
        $this->assertStringContainsString('Weather-only observation rows', $view);
        $this->assertStringContainsString('#analysisEvidenceModal.modal.show', $view);
    }
}
