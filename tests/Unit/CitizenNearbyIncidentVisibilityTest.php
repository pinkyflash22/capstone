<?php

namespace Tests\Unit;

use App\Http\Controllers\MarkerController;
use App\Http\Controllers\PublicController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class CitizenNearbyIncidentVisibilityTest extends TestCase
{
    #[Test]
    public function citizen_dashboard_and_marker_feed_share_the_same_nearby_radius(): void
    {
        $dashboardRadius = (new ReflectionClass(PublicController::class))
            ->getReflectionConstant('CITIZEN_NEARBY_INCIDENT_RADIUS_METERS')
            ->getValue();
        $markerRadius = (new ReflectionClass(MarkerController::class))
            ->getReflectionConstant('CITIZEN_NEARBY_INCIDENT_RADIUS_METERS')
            ->getValue();

        $this->assertSame(3000, $dashboardRadius);
        $this->assertSame($dashboardRadius, $markerRadius);
    }

    #[Test]
    public function citizen_incident_payload_is_limited_to_own_and_nearby_active_reports(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PublicController.php'));
        $markerController = file_get_contents(app_path('Http/Controllers/MarkerController.php'));

        $this->assertStringContainsString('$visibleIncidents = $userIncidents', $controller);
        $this->assertStringContainsString("'incidents' => \$visibleIncidents->map", $controller);
        $this->assertStringContainsString('self::CITIZEN_NEARBY_INCIDENT_RADIUS_METERS', $controller);
        $this->assertStringContainsString('if ($latitude === null || $longitude === null)', $markerController);
        $this->assertStringContainsString("['reported', 'responding', 'en-route', 'en route', 'on scene', 'on-scene']", $markerController);
    }

    #[Test]
    public function citizen_ui_fails_closed_without_real_location_and_refilters_cached_alerts(): void
    {
        $userMap = file_get_contents(resource_path('js/user.js'));
        $dashboard = file_get_contents(resource_path('views/citizen/dashboard.blade.php'));

        $this->assertMatchesRegularExpression(
            '/const userLocation = getCitizenReferenceLocation\(\);\s*if \(!userLocation\) \{\s*return false;\s*\}/',
            $userMap
        );
        $this->assertStringContainsString("markerFeedParams.set('lat'", $userMap);
        $this->assertStringContainsString('function filterCitizenNearbyIncidents', $dashboard);
        $this->assertStringContainsString('const nearbyIncidents = filterCitizenNearbyIncidents(data.nearby_incidents);', $dashboard);
        $this->assertStringContainsString('nearby alerts remain disabled', $dashboard);
        $this->assertStringNotContainsString('initial GPS error, using fallback location', $dashboard);
    }
}
