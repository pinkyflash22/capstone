<?php

namespace Tests\Unit;

use App\Services\DailyWeatherContextService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DailyWeatherContextServiceTest extends TestCase
{
    #[Test]
    public function it_builds_one_completed_daily_summary_from_hourly_weather(): void
    {
        $service = new DailyWeatherContextService;
        $summary = $service->summarizeHourlyPayload([
            'hourly' => [
                'temperature_2m' => [28, 32, 30],
                'relative_humidity_2m' => [80, 60, 70],
                'precipitation' => [0, 1.5, 0.5],
                'rain' => [0, 1, 0.5],
                'weather_code' => [1, 61, 61],
                'wind_speed_10m' => [5, 10, 15],
                'wind_gusts_10m' => [8, 18, 24],
            ],
        ]);

        $this->assertSame(30.0, $summary['temperature_avg_c']);
        $this->assertSame(28.0, $summary['temperature_min_c']);
        $this->assertSame(32.0, $summary['temperature_max_c']);
        $this->assertSame(70.0, $summary['humidity_avg_percent']);
        $this->assertSame(10.0, $summary['wind_avg_kph']);
        $this->assertSame(15.0, $summary['wind_max_kph']);
        $this->assertSame(24.0, $summary['wind_gust_max_kph']);
        $this->assertSame(2.0, $summary['precipitation_total_mm']);
        $this->assertSame(61, $summary['dominant_weather_code']);
        $this->assertSame('Rain', $summary['dominant_condition']);
        $this->assertSame(3, $summary['hourly_observation_count']);
    }

    #[Test]
    public function it_aggregates_barangay_rows_into_calendar_day_month_context(): void
    {
        $service = new DailyWeatherContextService;
        $summary = $service->summarizeStoredRows(collect([
            (object) ['observation_date' => '2026-08-01', 'temperature_avg_c' => 30, 'temperature_min_c' => 26, 'temperature_max_c' => 34, 'humidity_avg_percent' => 70, 'wind_avg_kph' => 8, 'wind_max_kph' => 18, 'precipitation_total_mm' => 0, 'dominant_condition' => 'Clear sky'],
            (object) ['observation_date' => '2026-08-01', 'temperature_avg_c' => 32, 'temperature_min_c' => 27, 'temperature_max_c' => 35, 'humidity_avg_percent' => 66, 'wind_avg_kph' => 10, 'wind_max_kph' => 22, 'precipitation_total_mm' => 2, 'dominant_condition' => 'Rain'],
            (object) ['observation_date' => '2026-08-02', 'temperature_avg_c' => 29, 'temperature_min_c' => 25, 'temperature_max_c' => 31, 'humidity_avg_percent' => 78, 'wind_avg_kph' => 7, 'wind_max_kph' => 15, 'precipitation_total_mm' => 0, 'dominant_condition' => 'Clear sky'],
        ]));

        $this->assertSame(3, $summary['daily_observation_count']);
        $this->assertSame(2, $summary['observed_calendar_days']);
        $this->assertSame(1, $summary['rainy_days']);
        $this->assertSame(1, $summary['hot_days']);
        $this->assertSame(2.0, $summary['total_precipitation_mm']);
    }

    #[Test]
    public function daily_collection_is_additive_and_does_not_modify_incident_weather_storage(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_25_000000_create_daily_weather_contexts_table.php'));
        $commands = file_get_contents(base_path('routes/console.php'));
        $incidentJob = file_get_contents(app_path('Jobs/EnrichIncidentContextJob.php'));
        $renderStart = file_get_contents(base_path('docker/render-start.sh'));

        $this->assertStringContainsString("Schema::create('daily_weather_contexts'", $migration);
        $this->assertStringNotContainsString("Schema::table('incidents'", $migration);
        $this->assertStringContainsString("Schedule::command('weather:collect-daily')", $commands);
        $this->assertStringContainsString("->dailyAt('00:30')", $commands);
        $this->assertStringContainsString("Schema::hasTable('daily_weather_contexts')", $commands);
        $this->assertStringContainsString("\$monthlyDailyWeather->avg('temperature_avg_c')", $commands);
        $this->assertStringContainsString('HistoricalFireRecordSemantics::incidentCount($monthlyIncidents)', $commands);
        $this->assertStringContainsString('IncidentContext::updateOrCreate(', $incidentJob);
        $this->assertStringContainsString("'temperature' => \$weather['temperature']", $incidentJob);
        $this->assertStringContainsString("'captured_at' => now()", $incidentJob);
        $this->assertStringContainsString('RUN_DAILY_WEATHER_WORKER', $renderStart);
        $this->assertStringContainsString('php artisan weather:collect-daily', $renderStart);
        $this->assertStringNotContainsString('php artisan schedule:work', $renderStart);
    }
}
