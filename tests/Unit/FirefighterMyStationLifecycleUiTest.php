<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirefighterMyStationLifecycleUiTest extends TestCase
{
    #[Test]
    public function partial_navigation_loads_station_config_and_initializes_the_station_module(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $navigation = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));

        $rootPosition = strpos($view, 'data-ff-page-root="station"');
        $configPosition = strpos($view, 'window.firefighterMyStationConfig');
        $modulePosition = strpos($view, "@vite('resources/js/firefighter/station-shell.js')");
        $rootClosePosition = strrpos($view, '</div>');

        $this->assertNotFalse($rootPosition);
        $this->assertGreaterThan($rootPosition, $configPosition);
        $this->assertGreaterThan($configPosition, $modulePosition);
        $this->assertGreaterThan($modulePosition, $rootClosePosition);
        $this->assertStringContainsString("routeKey === 'station'", $navigation);
        $this->assertStringContainsString('window.initFirefighterMyStationPage?.();', $navigation);
    }

    #[Test]
    public function crew_members_render_as_profile_cards_with_a_compact_detail_modal(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/staff/FirefighterController.php'));
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $personnel = file_get_contents(resource_path('views/firefighters/mystation-personnel.blade.php'));
        $reportPerson = file_get_contents(resource_path('views/firefighters/partials/person-trigger.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));
        $redesignStyles = file_get_contents(resource_path('css/firefighters/my-station.css'));

        $this->assertStringContainsString('first_name,last_name,email,contact_number,image,role_id', $controller);
        $this->assertStringContainsString("'profile_image_url' => \$member->user->profile_image_url", $controller);
        $this->assertStringContainsString('data-crew-member-card', $personnel);
        $this->assertStringContainsString('data-member-image="{{ $profileImage }}"', $personnel);
        $this->assertStringContainsString('id="crewMemberDetailModal"', $view);
        $this->assertStringContainsString('class="report-person-card"', $reportPerson);
        $this->assertStringContainsString('profile_thumbnail_url', $reportPerson);
        $this->assertStringContainsString('function openCrewMemberModal', $script);
        $this->assertStringContainsString('function closeCrewMemberModal', $script);
        $this->assertStringContainsString('function createCurrentOfficerTrigger', $script);
        $this->assertStringContainsString('.crew-member-card:hover', $styles);
        $this->assertStringContainsString('.crew-member-modal__dialog', $styles);
        $this->assertStringContainsString('.officer-card.station-person-card', $redesignStyles);
        $this->assertStringContainsString('Identity and contact safeguards live with the primary station breakpoint.', $styles);
        $this->assertStringNotContainsString('My Station mobile identity polish', $styles);
        $this->assertStringContainsString('[data-ff-page-root="station"] > .main-content > .station-shell-page', $styles);
        $this->assertStringNotContainsString('.main-content:has(> [data-ff-page-root="station"])', $styles);
    }

    #[Test]
    public function station_content_is_ordered_by_operational_priority_and_has_mobile_specific_polish(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $styles = file_get_contents(resource_path('css/firefighters/my-station.css'));
        $reports = file_get_contents(resource_path('views/firefighters/mystation-reports-table.blade.php'));

        $personnelPosition = strpos($view, 'id="stationPersonnelSection"');
        $equipmentPosition = strpos($view, 'id="stationEquipmentSection"');
        $reportsPosition = strpos($view, 'id="stationReportsSection"');
        $galleryPosition = strpos($view, 'id="stationOverviewSection"');

        $this->assertNotFalse($personnelPosition);
        $this->assertGreaterThan($personnelPosition, $equipmentPosition);
        $this->assertGreaterThan($equipmentPosition, $reportsPosition);
        $this->assertGreaterThan($reportsPosition, $galleryPosition);
        $this->assertStringContainsString('Operational readiness', $view);
        $this->assertStringContainsString('Highest priority', $view);
        $this->assertStringContainsString("@media (max-width: 768px)", $styles);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr));', $styles);
        $this->assertSame(4, substr_count($reports, "firefighters.partials.person-trigger"));
    }

    #[Test]
    public function readiness_auto_refreshes_and_large_rosters_are_searchable(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $personnel = file_get_contents(resource_path('views/firefighters/mystation-personnel.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/my-station.css'));

        $this->assertStringContainsString('readinessRefreshIntervalMs: 30000', $view);
        $this->assertStringContainsString('$stationOfficers->count() >= 8', $personnel);
        $this->assertStringContainsString('data-station-roster-search', $personnel);
        $this->assertStringContainsString('data-roster-search-text', $personnel);
        $this->assertStringContainsString('window.setInterval(refreshReadiness, READINESS_REFRESH_INTERVAL_MS)', $script);
        $this->assertStringContainsString("document.addEventListener('visibilitychange', handleReadinessVisibilityChange)", $script);
        $this->assertStringContainsString('applyRosterSearch();', $script);
        $this->assertStringContainsString('.station-roster-search', $styles);
    }

    #[Test]
    public function firefighters_can_check_station_scoped_vehicle_and_equipment_maintenance(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/staff/FirefighterController.php'));
        $equipment = file_get_contents(resource_path('views/firefighters/mystation-equipment.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/my-station.css'));

        $this->assertStringContainsString('protected function loadStationApparatus(int $stationId)', $controller);
        $this->assertStringContainsString("->where('station_id', \$stationId)", $controller);
        $this->assertStringContainsString("'apparatusUnits' => \$apparatusUnits", $controller);
        $this->assertStringContainsString('Maintenance check', $equipment);
        $this->assertStringContainsString('Last maintenance', $equipment);
        $this->assertStringContainsString('Next service due', $equipment);
        $this->assertStringContainsString('Other equipment readiness', $equipment);
        $this->assertStringContainsString('data-maintenance-filter="attention"', $equipment);
        $this->assertStringContainsString('function applyMaintenanceFilter', $script);
        $this->assertStringContainsString('applyMaintenanceFilter();', $script);
        $this->assertStringContainsString('.maintenance-unit-card--overdue', $styles);
        $this->assertStringContainsString('@media (max-width: 600px)', $styles);
    }

    #[Test]
    public function spot_investigation_reports_can_adjust_the_incident_marker_location(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/SpotInvestigationReportController.php'));
        $stationController = file_get_contents(app_path('Http/Controllers/staff/FirefighterController.php'));
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));

        $this->assertStringContainsString("'incident_location_lat' => 'nullable|required_with:incident_location_lng|numeric|between:-90,90'", $controller);
        $this->assertStringContainsString('private function syncIncidentMarkerLocation', $controller);
        $this->assertStringContainsString("'marker_lat' => \$incident->marker?->lat", $stationController);
        $this->assertStringContainsString('data-spot-location-picker', $view);
        $this->assertStringContainsString('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', $view);
        $this->assertStringContainsString('function bindSpotLocationPicker', $script);
        $this->assertStringContainsString('data-marker-lat', $script);
        $this->assertStringContainsString('.spot-location-picker__map', $styles);
    }

    #[Test]
    public function incident_registry_links_to_the_next_investigation_report_step(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/staff/FirefighterController.php'));
        $incidentsView = file_get_contents(resource_path('views/firefighters/incidents.blade.php'));
        $stationScript = file_get_contents(resource_path('js/firefighter/station-shell.js'));

        $this->assertStringContainsString("'investigation_action' => \$nextInvestigationAction", $controller);
        $this->assertStringContainsString("'openReport' => 'progress'", $controller);
        $this->assertStringContainsString("'openReport' => 'final'", $controller);
        $this->assertStringContainsString('ProgressInvestigationReport::STAGE_SECOND', $controller);
        $this->assertStringContainsString('incident.investigation_action', $incidentsView);
        $this->assertStringContainsString('Start Final Report', $controller);
        $this->assertStringContainsString("params.get('spot_id')", $stationScript);
        $this->assertStringContainsString("params.get('progress_id')", $stationScript);
        $this->assertStringContainsString("params.get('report_stage')", $stationScript);
    }

    #[Test]
    public function investigation_report_modal_has_one_authoritative_mobile_height_layer(): void
    {
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));

        $this->assertStringContainsString('@media (max-width: 768px)', $styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('height: min(86dvh, calc(100dvh - max(6px, env(safe-area-inset-top)))) !important;', $styles);
        $this->assertStringNotContainsString('height: min(94dvh, 100%) !important;', $styles);
        $this->assertStringNotContainsString('height: min(92dvh, 100%) !important;', $styles);
        $this->assertStringNotContainsString('padding: 24px !important;', $styles);
        $this->assertStringContainsString('min-width: 0 !important;', $styles);
        $this->assertStringContainsString('border-width: 1px 0 0 !important;', $styles);
    }
}
