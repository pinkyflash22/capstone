<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChiefWeatherContextUiTest extends TestCase
{
    #[Test]
    public function chief_weather_workspace_is_station_scoped_filtered_and_paginated(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/WeatherContextController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $sidebar = file_get_contents(resource_path('views/chief/partials/sidebar.blade.php'));

        $this->assertStringContainsString("->where('station_barangay_coverage.station_id', \$stationId)", $controller);
        $this->assertStringContainsString("->whereIn('daily_weather_contexts.barangay_id'", $controller);
        $this->assertStringContainsString('->paginate($perPage)', $controller);
        $this->assertStringContainsString("'quality' => ['nullable', 'in:all,complete,incomplete']", $controller);
        $this->assertStringContainsString("'days' => ['required', 'integer', 'min:1', 'max:31']", $controller);
        $this->assertStringContainsString("Route::get('/weather-context'", $routes);
        $this->assertStringContainsString("'station-admin.weather-context.index'", $sidebar);
        $this->assertStringContainsString("'mobile_icon' => 'cloud'", $sidebar);
    }

    #[Test]
    public function weather_page_uses_real_records_and_never_exposes_a_provider_key(): void
    {
        $view = file_get_contents(resource_path('views/chief/weather.blade.php'));

        $this->assertStringContainsString('Daily weather intelligence', $view);
        $this->assertStringContainsString('Source transparency', $view);
        $this->assertStringContainsString('PAGASA station comparison', $view);
        $this->assertStringContainsString('Not connected', $view);
        $this->assertStringContainsString('Prediction readiness', $view);
        $this->assertStringContainsString('$records->onEachSide(1)->links()', $view);
        $this->assertStringContainsString("@include('chief.partials.sidebar',['active'=>'weather-context'])", $view);
        $this->assertStringNotContainsString('API_KEY', $view);
        $this->assertStringNotContainsString('api.weatherapi.com', $view);
        $this->assertStringNotContainsString('value="London"', $view);
    }
}
