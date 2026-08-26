<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MapMarkerSemanticsTest extends TestCase
{
    public function test_live_apparatus_gps_rejects_stale_inaccurate_and_implausible_fixes(): void
    {
        $dashboardScript = file_get_contents(__DIR__.'/../../resources/js/firefighter/dashboard-shell.js');
        $mapScript = file_get_contents(__DIR__.'/../../resources/js/map.js');

        $this->assertStringContainsString('MAX_LIVE_GPS_ACCURACY_METERS = 150', $dashboardScript);
        $this->assertStringContainsString('MAX_LIVE_GPS_AGE_MS = 30000', $dashboardScript);
        $this->assertStringContainsString('Ignored an implausible apparatus GPS jump.', $dashboardScript);
        $this->assertStringNotContainsString('updateUserLocation(window.currentUserId, 15.8222, 120.3297)', $dashboardScript);
        $this->assertStringContainsString('function usableStoredGpsLocation', $mapScript);
        $this->assertStringContainsString('{ enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }', $mapScript);
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
}
