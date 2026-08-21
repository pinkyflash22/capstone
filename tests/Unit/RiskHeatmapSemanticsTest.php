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
        $citizenMapScript = file_get_contents(resource_path('js/user.js'));
        $forecastService = file_get_contents(app_path('Services/PredictedRiskAreaService.php'));
        $overlayController = file_get_contents(app_path('Http/Controllers/FireRiskAreaOverlayController.php'));

        $this->assertStringContainsString("case 'risk':", $mapScript);
        $this->assertStringContainsString("case 'hazard':", $mapScript);
        $this->assertStringContainsString('riskHazardFeatureGroup.addLayer(marker)', $mapScript);
        $this->assertStringNotContainsString('Suppress the duplicate legacy pin', $mapScript);
        $this->assertStringContainsString('The server-side marker feed already applies review and role visibility.', $citizenMapScript);
        $this->assertStringContainsString('const heatPoints = fireRiskHeatPoints(area);', $mapScript);
        $this->assertStringContainsString("fillColor: 'transparent'", $mapScript);
        $this->assertStringContainsString("\$area['geometry_type'] = 'heatmap';", $forecastService);
        $this->assertStringContainsString("\$area['geometry_precision'] = 'supporting_evidence_positions';", $forecastService);
        $this->assertStringContainsString("\$area['geometry_type'] = 'heatmap';", $overlayController);
        $this->assertStringContainsString("\$area['geometry_precision'] = 'original_evidence_points';", $overlayController);
        $this->assertStringContainsString("->join('markers', 'incidents.marker_id', '=', 'markers.marker_id')", $forecastService);
        $this->assertStringContainsString("'source_type' => 'historical_incident'", $forecastService);
        $this->assertStringNotContainsString("\$area['geometry_type'] = 'geojson';", $forecastService);
    }
}
