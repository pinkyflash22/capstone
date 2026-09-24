<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MapMarkerSemanticsTest extends TestCase
{
    public function test_chief_marker_creation_uses_a_protected_write_endpoint_and_rolls_back_incomplete_details(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $detailsStart = strpos($mapScript, 'async function saveMarkerDetailsForCategory');
        $detailsEnd = strpos($mapScript, 'async function saveMarkerOptimisticallyFromForm', $detailsStart);
        $categoryDetailsScript = substr($mapScript, $detailsStart, $detailsEnd - $detailsStart);

        $this->assertStringContainsString("function getMarkerCreateEndpoint()", $mapScript);
        $this->assertStringContainsString("return resolveCurrentUserMapId() !== 'guest' ? '/map/markers' : '/api/markers';", $mapScript);
        $this->assertStringContainsString("fetch(getMarkerCreateEndpoint(), {", $mapScript);
        $this->assertStringContainsString('fetch(`/map/markers/${encodeURIComponent(savedMarkerId)}`', $mapScript);
        $this->assertStringContainsString("Route::post('/map/markers', [ManagedMarkerController::class, 'store'])", $routes);
        $this->assertStringContainsString("role:admin,provincial admin,stationadmin,chief", $routes);
        $this->assertStringContainsString("fetch('/map/marker-details/incidents'", $mapScript);
        $this->assertStringContainsString("fetch('/map/marker-details/stations'", $mapScript);
        $this->assertStringContainsString("fetch('/map/marker-details/hydrants'", $mapScript);
        $this->assertStringContainsString("fetch('/map/marker-details/barangay-halls'", $mapScript);
        $this->assertStringNotContainsString("fetch('/api/schools'", $categoryDetailsScript);
    }

    public function test_admin_and_chief_can_create_boundary_matched_risk_hazard_markers_with_optional_photos(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/MarkerController.php');
        $adminView = file_get_contents(__DIR__.'/../../resources/views/admin/dashboard.blade.php');
        $chiefView = file_get_contents(__DIR__.'/../../resources/views/chief/dashboard.blade.php');

        foreach ([$adminView, $chiefView] as $view) {
            $this->assertStringContainsString('data-category-enum="risk">Risk Area', $view);
            $this->assertStringContainsString('data-category-enum="hazard">Hazard Area', $view);
            $this->assertStringContainsString('id="riskHazardPhotos"', $view);
        }

        $this->assertStringContainsString("markerForm.append('photos[]', photo, photo.name)", $mapScript);
        $this->assertStringContainsString('bindRiskHazardPhotoPreviews()', $mapScript);
        $this->assertStringContainsString('$boundaryMatcherService->locateBarangayHall($lat, $lng)', $controller);
        $this->assertStringContainsString("'review_status' => 'approved'", $controller);
        $this->assertStringContainsString('RiskAreaPhoto::create([', $controller);
    }

    public function test_live_apparatus_gps_rejects_stale_inaccurate_and_implausible_fixes(): void
    {
        $dashboardScript = file_get_contents(__DIR__.'/../../resources/js/firefighter/dashboard-shell.js');
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');

        $this->assertStringContainsString('MAX_LIVE_GPS_ACCURACY_METERS = 150', $dashboardScript);
        $this->assertStringContainsString('MAX_LIVE_GPS_AGE_MS = 30000', $dashboardScript);
        $this->assertStringContainsString('Ignored an implausible apparatus GPS jump.', $dashboardScript);
        $this->assertStringNotContainsString('updateUserLocation(window.currentUserId, 15.8222, 120.3297)', $dashboardScript);
        $this->assertStringContainsString('function usableStoredGpsLocation', $mapScript);
        $this->assertStringContainsString('{ enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }', $mapScript);
    }

    public function test_user_location_requests_reuse_only_a_five_second_cache(): void
    {
        $locationScript = file_get_contents(__DIR__.'/../../resources/js/location-accuracy.js');
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $citizenView = file_get_contents(__DIR__.'/../../resources/views/citizen/dashboard.blade.php');
        $mobileHubScript = file_get_contents(__DIR__.'/../../resources/js/mobile-hub.js');
        $barangayLayout = file_get_contents(__DIR__.'/../../resources/views/barangay/layouts/app.blade.php');

        $this->assertStringContainsString('QUICK_LOCATION_CACHE_MAX_AGE_MS = 5000', $locationScript);
        $this->assertStringContainsString('export async function getQuickLocationFix', $locationScript);
        $this->assertStringContainsString('maximumAge: browserCacheMaxAgeMs', $locationScript);
        $this->assertStringContainsString('getQuickLocationFix', $citizenView);
        $this->assertStringContainsString('fireLocationFreshness', $citizenView);
        $this->assertStringContainsString('startCitizenReportLocationRefresh', $citizenView);
        $this->assertStringContainsString('forceFresh: true', $citizenView);
        $this->assertStringContainsString('maxAgeMs: 5000', $citizenView);
        $this->assertStringContainsString('candidate.quickCacheHit !== true', $citizenView);
        $this->assertStringContainsString('getQuickLocationFix', $mobileHubScript);
        $this->assertStringContainsString('getQuickLocationFix', $barangayLayout);
        $this->assertStringContainsString('{ enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }', $mapScript);
    }

    public function test_directions_use_an_arrow_and_actual_responses_use_apparatus_markers(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $dashboardScript = file_get_contents(__DIR__.'/../../resources/js/firefighter/dashboard-shell.js');
        $dashboardView = file_get_contents(__DIR__.'/../../resources/views/firefighters/dashboard.blade.php');
        $dashboardStyles = file_get_contents(__DIR__.'/../../resources/css/firefighters/dashboard-shell.css');

        $this->assertStringContainsString('function createNavigationArrowIcon', $mapScript);
        $this->assertStringContainsString('function createDemoTruckIcon', $mapScript);
        $this->assertStringContainsString("iconUrl: '/truck.png'", $mapScript);
        $this->assertStringContainsString('icon: createRouteMovementIcon(truck.routeSource', $mapScript);
        $this->assertStringNotContainsString('icon: createFireTruckIcon', $mapScript);
        $this->assertStringContainsString('Actual dispatched apparatus', $dashboardScript);
        $this->assertStringContainsString("iconUrl: '/truck.png'", $dashboardScript);
        $this->assertStringContainsString('data-response-units-for', $dashboardScript);
        $this->assertStringContainsString('L.icon({', $dashboardScript);
        $this->assertStringContainsString('unit.team_members', $dashboardScript);
        $this->assertStringContainsString('unit.plate_number', $dashboardScript);
        $this->assertStringContainsString('ONBOARD EQUIPMENT', $dashboardScript);
        $this->assertStringContainsString('publishDriverApparatusLocation', $dashboardScript);
        $this->assertGreaterThan(
            strpos($dashboardView, 'data-ff-insights-section="assigned"'),
            strpos($dashboardView, 'id="activeResponseUnitsPanel"'),
            'Response tracking must be inside Assigned Incidents, not over the map.'
        );
        $this->assertStringNotContainsString('.ff-response-units { position:absolute', $dashboardStyles);
    }

    public function test_starting_directions_does_not_advance_the_incident_status(): void
    {
        $script = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $start = strpos($script, 'async function startSimulationForRoute');
        $end = strpos($script, 'function startSmoothAnimationForTruck', $start);
        $navigationFunction = substr($script, $start, $end - $start);

        $this->assertStringNotContainsString('updateIncidentStatusFromRoute', $navigationFunction);
        $this->assertStringContainsString('Directions never dispatch resources', $navigationFunction);
        $this->assertSame(1, substr_count($script, 'updateIncidentStatusFromRoute('), 'Navigation must not call incident-status mutations.');
    }

    public function test_live_navigation_detects_off_route_movement_without_dispatching_resources(): void
    {
        $script = file_get_contents(__DIR__.'/../../resources/js/map.js');

        $this->assertStringContainsString('NAVIGATION_OFF_ROUTE_THRESHOLD_METERS = 120', $script);
        $this->assertStringContainsString('async function maybeRerouteNavigation', $script);
        $this->assertStringContainsString('Off-route movement detected. Navigation recalculated.', $script);
    }

    public function test_demo_routes_are_shared_and_logout_only_stops_local_playback(): void
    {
        $script = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/RouteController.php');

        $this->assertStringContainsString("if (isDemoRouteSource(routeData.route_source || routeData.routeSource))", $script);
        $this->assertStringContainsString("isDemoRouteSource(routeSource) || movementMode === 'response'", $script);
        $this->assertStringContainsString('Demonstration only. This truck does not dispatch resources or update the incident status.', $script);
        $this->assertStringContainsString("&& !isDemoRouteSource(truck?.routeSource)", $script);
        $this->assertStringContainsString("->where('route_source', '!=', 'demo')", $controller);
        $this->assertStringContainsString('stopAllSimulations(false);', $script);
        $this->assertStringContainsString('async function stopAllSimulations(syncWithServer = true)', $script);
        $this->assertStringContainsString('$route->incident?->station_id', $controller);
    }

    public function test_live_and_demo_controls_are_explicit_and_live_routes_wait_for_gps_movement(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $dashboardScript = file_get_contents(__DIR__.'/../../resources/js/firefighter/dashboard-shell.js');
        $citizenScript = file_get_contents(__DIR__.'/../../resources/js/user.js');

        $this->assertStringContainsString('Get Incident <sup class="responder-marker-popup-badge live">LIVE GPS</sup>', $mapScript);
        $this->assertStringContainsString('Respond Now <sup class="responder-marker-popup-badge">DEMO</sup>', $mapScript);
        $this->assertStringContainsString('Live GPS route ready. The marker will move when the assigned device moves.', $mapScript);
        $this->assertStringContainsString("persistRoute: Boolean(options?.response) || isDemoRouteSource(options?.source)", $mapScript);
        $this->assertStringContainsString("{ source: 'real', response: true }", $dashboardScript);
        $this->assertStringContainsString("iconUrl: '/truck.png'", $citizenScript);
        $this->assertStringContainsString('function userUpdateLiveTruckPosition', $citizenScript);
        $this->assertStringNotContainsString("iconUrl: '/storage/defaults/firetruck.png'", $citizenScript);
    }

    public function test_truck_heading_is_applied_to_demo_and_citizen_markers(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $citizenScript = file_get_contents(__DIR__.'/../../resources/js/user.js');

        $this->assertStringNotContainsString("markerElement.classList.contains('pyro-demo-route-truck-marker')", $mapScript);
        $this->assertStringContainsString('applyTruckHeading(currentTruck.animatedVehicle, currentSegment.angle);', $mapScript);
        $this->assertStringContainsString('applyTruckHeading(currentTruck.animatedVehicle, currentSegment.angle);', $citizenScript);
    }

    public function test_controlled_incidents_remain_visible_as_investigation_markers(): void
    {
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');
        $citizenScript = file_get_contents(__DIR__.'/../../resources/js/user.js');
        $incidentController = file_get_contents(__DIR__.'/../../app/Http/Controllers/IncidentController.php');
        $firefighterController = file_get_contents(__DIR__.'/../../app/Http/Controllers/FirefighterIncidentResponseController.php');
        $markerFeed = file_get_contents(__DIR__.'/../../app/Support/MarkerFeedBuilder.php');

        $this->assertStringContainsString("self::STATUS_CONTROLLED => 'completed'", $incidentController);
        $this->assertStringContainsString("\$locked->marker->forceFill(['status' => 'completed'])->save();", $firefighterController);
        $this->assertStringContainsString("'marker_status' => \$marker->status", $markerFeed);
        $this->assertStringContainsString("'incident_status' => \$incident?->status", $markerFeed);
        $this->assertStringContainsString("presentation.iconClass = 'fa-magnifying-glass';", $mapScript);
        $this->assertStringContainsString("const imagePath = isActiveIncident ? inferMarkerImagePath(marker, { id: 'incident' }) : null;", $mapScript);
        $this->assertStringContainsString("!isCitizenActiveFireIncident(null, marker)", $citizenScript);
        $this->assertStringContainsString("icon: 'fa-magnifying-glass'", $citizenScript);
    }
}
