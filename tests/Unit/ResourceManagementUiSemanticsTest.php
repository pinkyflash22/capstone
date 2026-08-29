<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResourceManagementUiSemanticsTest extends TestCase
{
    public function test_shared_resource_script_uses_the_page_station_payload_without_debug_noise(): void
    {
        $script = file_get_contents(resource_path('js/admin/resource-management.js'));
        $chiefView = file_get_contents(resource_path('views/chief/resource-management.blade.php'));

        $this->assertStringContainsString('window.resourcesData?.activeStations', $script);
        $this->assertStringContainsString("station.marker?.lat !== undefined", $script);
        $this->assertStringNotContainsString('console.log(window.stationary)', $script);
        $this->assertStringNotContainsString("console.log('Available officers by station:'", $chiefView);
        $this->assertStringNotContainsString("console.log('Station selected:'", $chiefView);
    }

    #[Test]
    public function every_resource_table_is_paginated_and_mobile_dialogs_are_scroll_safe(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/ResourceManagementController.php'));
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $resourceStyles = file_get_contents(resource_path('css/admin/resource-management.css'));
        $rosterStyles = file_get_contents(resource_path('css/duty-roster.css'));

        foreach (['personnel_page', 'teams_page', 'users_page', 'equipment_page', 'boundaries_page', 'barangay_officers_page', 'apparatus_page'] as $pageName) {
            $this->assertStringContainsString("'{$pageName}'", $controller . $view);
        }

        $this->assertStringContainsString('$barangayOfficersPage->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('$apparatusPage->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('resource-management-page .modal .modal-body', $resourceStyles);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $resourceStyles);
        $this->assertStringContainsString('position:fixed;inset:0', $rosterStyles);
        $this->assertStringContainsString('overflow-y:auto', $rosterStyles);
    }

    #[Test]
    public function chief_barangay_scope_comes_from_the_assigned_station_not_the_home_address(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/ResourceManagementController.php'));

        $this->assertStringContainsString('$stationCityId = $currentStation?->address?->barangay?->city?->city_id;', $controller);
        $this->assertStringContainsString('$chiefCityId = $stationCityId;', $controller);
        $this->assertStringContainsString('protected function buildBoundaryBarangayList(?int $stationId)', $controller);
        $this->assertStringContainsString('$cityId = $station?->address?->barangay?->city?->city_id;', $controller);
        $this->assertStringContainsString("?->filter(fn (Barangay \$barangay) => (bool) \$barangay->pivot?->is_active)", $controller);
        $this->assertStringContainsString("&& \$cityId", $controller);
        $this->assertStringContainsString("->where('city_id', \$cityId)", $controller);
        $this->assertStringContainsString('resource-management:station:{$stationExact}:v4', $controller);
        $this->assertStringNotContainsString('$loggedInChief?->address?->barangay?->city', $controller);
        $this->assertStringNotContainsString('buildBoundaryBarangayList($stationExact, $chiefCityId)', $controller);
    }

    #[Test]
    public function resource_page_is_compact_paginated_and_ordered_by_operational_priority(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/ResourceManagementController.php'));
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $styles = file_get_contents(resource_path('css/admin/resource-management.css'));

        $this->assertStringContainsString('int $perPage = 10', $controller);
        $this->assertSame(7, substr_count($view, '->onEachSide(1)->links()'));
        foreach (['Response readiness', 'Inventory control', 'Community coverage', 'Account records'] as $heading) {
            $this->assertStringContainsString($heading, $view);
        }
        foreach (['resource-tier--operations', 'resource-tier--apparatus', 'resource-tier--inventory', 'resource-tier--community', 'resource-tier--records'] as $class) {
            $this->assertStringContainsString($class, $view . $styles);
        }
        $this->assertStringContainsString('max-width: 1180px', $styles);
        $this->assertStringContainsString('text-overflow: ellipsis', $styles);
        $this->assertStringContainsString('applyResourceTextLimits', $view);
    }

    #[Test]
    public function provincial_resource_page_uses_admin_navigation_and_action_urls(): void
    {
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("\$isProvincialResourceAdmin ? 'admin.partials.sidebar' : 'chief.partials.sidebar'", $view);
        $this->assertStringContainsString("\$resourceRoutePrefix = \$isProvincialResourceAdmin ? 'admin' : 'station-admin'", $view);
        $this->assertStringContainsString('const resourceAdminBaseUrl = @json($resourceBaseUrl);', $view);
        $this->assertStringContainsString('`${resourceAdminBaseUrl}/users/${userId}`', $view);
        $this->assertStringContainsString("route(\$resourceRoutePrefix.'.barangay-boundaries.sync'", $view);
        $this->assertStringContainsString("Route::post('/barangay-boundaries/{barangayId}/sync'", $routes);
        $this->assertStringContainsString('@if($isProvincialResourceAdmin)', $view);
        $this->assertStringContainsString('<option value="">Choose station</option>', $view);
    }
}
