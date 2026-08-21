<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RiskHeatmapSemanticsTest extends TestCase
{
    #[Test]
    public function approved_risk_evidence_and_forecasts_use_original_position_heatmaps(): void
    {
        $mapScript = file_get_contents(resource_path('js/map.js'));
        $forecastService = file_get_contents(app_path('Services/PredictedRiskAreaService.php'));

        $this->assertStringContainsString("riskReviewStatus === 'approved' || riskStatus === 'verified'", $mapScript);
        $this->assertStringContainsString('const heatPoints = fireRiskHeatPoints(area);', $mapScript);
        $this->assertStringContainsString("fillColor: 'transparent'", $mapScript);
        $this->assertStringContainsString("\$area['geometry_type'] = 'heatmap';", $forecastService);
        $this->assertStringContainsString("\$area['geometry_precision'] = 'supporting_evidence_positions';", $forecastService);
        $this->assertStringNotContainsString("\$area['geometry_type'] = 'geojson';", $forecastService);
    }
}
