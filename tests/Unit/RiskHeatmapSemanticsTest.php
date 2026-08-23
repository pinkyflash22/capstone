<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RiskHeatmapSemanticsTest extends TestCase
{
    #[Test]
    public function reported_risks_stay_markers_and_only_saved_analyses_produce_heatmaps(): void
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
        $this->assertStringContainsString('buildForecastAreas($stationId)', $overlayController);
        $this->assertStringNotContainsString('buildPredictedAreas($stationId', $overlayController);
        $this->assertStringContainsString("'source' => 'saved_current_analysis_and_forecast_results'", $overlayController);
        $this->assertStringContainsString("area.source_type === 'saved_ai_forecast'", $mapScript);
        $this->assertStringContainsString('analysisWeight * localMultiplier', $mapScript);
        $this->assertStringContainsString('point.pressure_flags', $mapScript);
        $this->assertStringContainsString('point.incident_count', $mapScript);
        $this->assertStringContainsString("\$area['heat_basis'] = 'saved_analysis_result'", $forecastService);
        $this->assertStringContainsString("\$area['location_basis'] = 'historical_fires_and_approved_field_evidence'", $forecastService);
        $this->assertStringNotContainsString('"Verified Risk Evidence": fireRiskAreaLayerGroup', $mapScript);
        $this->assertStringContainsString('fireRiskAreaLayerGroup = L.featureGroup();', $mapScript);
        $this->assertStringContainsString('const heatmapBuckets = new Map();', $mapScript);
        $this->assertStringContainsString('effectiveBlendMeters / Math.max(0.1, metersPerPixel)', $mapScript);
        $this->assertStringContainsString('latLngToContainerPoint', $mapScript);
        $this->assertStringContainsString('data-risk-heat-blend', $mapScript);
        $this->assertStringContainsString('RISK_HEAT_BLEND_STORAGE_KEY', $mapScript);
        $this->assertStringContainsString('Local hotspot intensity', $mapScript);
        $this->assertStringContainsString('fireRiskLocalFactorLabels', $mapScript);
        $this->assertStringContainsString('Low Analysis Heat', $mapScript);
        $this->assertStringContainsString('Medium Analysis Heat', $mapScript);
        $this->assertStringContainsString('High Analysis Heat', $mapScript);
        $this->assertStringContainsString("->join('markers', 'incidents.marker_id', '=', 'markers.marker_id')", $forecastService);
        $this->assertStringContainsString("'source_type' => 'historical_incident'", $forecastService);
        $this->assertStringNotContainsString("\$area['geometry_type'] = 'geojson';", $forecastService);
    }
}
