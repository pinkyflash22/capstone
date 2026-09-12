<?php

namespace Tests\Unit;

use App\Support\HistoricalFireRecordSemantics;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HistoricalFireRecordSemanticsTest extends TestCase
{
    #[Test]
    public function weather_only_rows_do_not_count_as_historical_fires(): void
    {
        $rows = collect([
            (object) ['incident_count' => 1, 'source_name' => 'BFP record'],
            (object) ['incident_count' => 2, 'source_name' => 'Barangay record'],
            (object) ['incident_count' => 0, 'source_name' => 'Open-Meteo Archive'],
            (object) ['incident_count' => null, 'source_name' => 'Open-Meteo Archive'],
        ]);

        $this->assertSame(3, HistoricalFireRecordSemantics::incidentCount($rows));
        $this->assertCount(2, HistoricalFireRecordSemantics::incidentRows($rows));
    }

    #[Test]
    public function analysis_and_training_export_share_positive_incident_semantics(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $commands = file_get_contents(base_path('routes/console.php'));
        $heatmap = file_get_contents(app_path('Services/PredictedRiskAreaService.php'));

        $this->assertStringContainsString(
            'HistoricalFireRecordSemantics::incidentCount($sameMonthIncidents)',
            $controller
        );
        $this->assertStringContainsString(
            'HistoricalFireRecordSemantics::incidentCount($monthlyIncidents)',
            $commands
        );
        $this->assertStringNotContainsString(
            'max(1, (int) ($incident->incident_count ?? 1))',
            $commands
        );
        $this->assertStringContainsString('COALESCE(incident_count, 0)', $controller);
        $this->assertStringContainsString("->where('historical_fire_incidents.incident_count', '>', 0)", $heatmap);
        $riskEvidenceStart = strpos($heatmap, 'protected function riskEvidencePoints');
        $historicalEvidenceStart = strpos($heatmap, 'protected function historicalIncidentEvidencePoints');
        $riskEvidenceMethod = substr($heatmap, $riskEvidenceStart, $historicalEvidenceStart - $riskEvidenceStart);
        $historicalEvidenceMethod = substr($heatmap, $historicalEvidenceStart);

        $this->assertStringNotContainsString('historical_fire_incidents.incident_count', $riskEvidenceMethod);
        $this->assertStringContainsString('historical_fire_incidents.incident_count', $historicalEvidenceMethod);
    }

    #[Test]
    public function analytics_charts_separate_weather_placeholders_and_explain_missing_incident_evidence(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString("->where('incident_count', '>', 0)", $controller);
        $this->assertStringContainsString('resolveHistoricalWeatherYears', $controller);
        $this->assertStringContainsString('fetchHistoricalWeatherRows', $controller);
        $this->assertStringContainsString('resolveLatestSavedForecastForScope', $controller);
        $this->assertStringContainsString('No incident evidence available', $view);
        $this->assertStringContainsString('setChartEvidenceState', $view);
        $this->assertGreaterThanOrEqual(2, substr_count($view, 'spanGaps: true'));
    }
}
