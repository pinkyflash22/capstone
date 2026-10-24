<?php

namespace Tests\Unit;

use Tests\TestCase;

class IncidentCommandDispatchSemanticsTest extends TestCase
{
    public function test_backup_authority_and_controls_belong_to_chief_and_admin_command(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $apiRoutes = file_get_contents(base_path('routes/api.php'));
        $map = file_get_contents(resource_path('js/map.js'));
        $firefighter = file_get_contents(resource_path('js/firefighter/dashboard-shell.js'));

        $this->assertStringContainsString("role:stationadmin,chief,provincial admin", $routes);
        $this->assertStringContainsString("/incidents/{incident}/external-response", $routes);
        $this->assertStringNotContainsString("/incidents/{incident}/backup", $routes);
        $this->assertStringContainsString('openIncidentCommandPanel', $map);
        $this->assertStringContainsString('Measured from incident', $map);
        $this->assertStringContainsString('No capable local response is available', $map);
        $this->assertStringNotContainsString('request-incident-backup', $firefighter);
        $this->assertStringNotContainsString('/firefighter/incidents/${incidentId}/backup', $firefighter);
        $this->assertStringNotContainsString('/incidents/{incident}/backup', $apiRoutes);
        $this->assertStringNotContainsString('requestIncidentBackup', file_get_contents(resource_path('views/firefighters/dashboard.blade.php')));
        $this->assertStringContainsString("middleware(['web', 'auth', 'role:provincial admin'])->prefix('/management')", $apiRoutes);
    }

    public function test_registered_dispatch_routes_require_command_authority(): void
    {
        $routes = app('router')->getRoutes();
        $commandRoute = $routes->getByName('command.incidents.external-response');

        $this->assertNotNull($commandRoute);
        $this->assertContains('auth', $commandRoute->gatherMiddleware());
        $this->assertContains('role:stationadmin,chief,provincial admin', $commandRoute->gatherMiddleware());
        $uris = array_map(fn ($route) => $route->uri(), $routes->getRoutes());
        $this->assertNotContains('firefighter/incidents/{incident}/backup', $uris);
        $this->assertNotContains('api/incidents/{incident}/backup', $uris);
    }

    public function test_command_dispatch_validates_resources_and_records_an_audit_trail(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/IncidentCommandDispatchController.php'));
        $resources = file_get_contents(app_path('Services/IncidentResourceDispatchService.php'));

        $this->assertStringContainsString('teamCanAcceptIncident', $controller);
        $this->assertStringContainsString('incident_dispatch_audits', $controller);
        $this->assertStringContainsString('stationsByIncidentDistance', $resources);
        $this->assertStringContainsString('$stationId === $originStationId', $resources);
        $this->assertStringContainsString("dispatch_source", file_get_contents(database_path('migrations/2026_08_21_000005_create_incident_response_units_table.php')));
    }
}
