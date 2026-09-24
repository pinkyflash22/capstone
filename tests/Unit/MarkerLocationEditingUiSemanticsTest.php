<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkerLocationEditingUiSemanticsTest extends TestCase
{
    #[Test]
    public function chief_marker_location_picker_persists_coordinates_through_a_protected_route(): void
    {
        $script = file_get_contents(resource_path('js/map.js'));
        $controller = file_get_contents(app_path('Http/Controllers/MarkerController.php'));

        $this->assertStringContainsString('editMarkerPickLocation', $script);
        $this->assertStringContainsString('beginMarkerLocationPicker', $script);
        $this->assertStringContainsString('draggable: true', $script);
        $this->assertStringContainsString('data-marker-picker-use', $script);
        $this->assertStringContainsString('body: JSON.stringify({ lat: latitude, lng: longitude })', $script);
        $this->assertStringContainsString('function updateLocation', $controller);

        $route = Route::getRoutes()->match(Request::create('/api/markers/42/location', 'PUT'));
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('role:stationadmin,chief', $middleware);
    }
}
