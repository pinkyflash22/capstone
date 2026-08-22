<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FirefighterIncidentRegistryUiSemanticsTest extends TestCase
{
    public function test_registry_prioritizes_active_incidents_and_keeps_results_paginated(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/firefighters/incidents.blade.php');

        $active = strpos($view, 'data-filter="active"');
        $completed = strpos($view, 'data-filter="completed"');
        $closed = strpos($view, 'data-filter="closed"');
        $all = strpos($view, 'data-filter="all"');

        $this->assertNotFalse($active);
        $this->assertTrue($active < $completed && $completed < $closed && $closed < $all);
        $this->assertStringContainsString("filter: 'active'", $view);
        $this->assertStringContainsString('const INCIDENT_PAGE_SIZE = 6;', $view);
        $this->assertStringContainsString('incidents.slice(startIndex, startIndex + INCIDENT_PAGE_SIZE)', $view);
    }

    public function test_registry_uses_a_compact_detail_summary_without_copy_id_action(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/firefighters/incidents.blade.php');

        $this->assertStringContainsString('incident-brief__facts', $view);
        $this->assertStringContainsString('incident-brief__location', $view);
        $this->assertStringNotContainsString('Copy ID', $view);
        $this->assertStringNotContainsString('copyIncidentId', $view);
    }

    public function test_registry_resolves_a_real_location_before_a_generic_marker_title(): void
    {
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/staff/FirefighterController.php');

        $this->assertStringContainsString("'caller_location_label'", $controller);
        $this->assertStringContainsString('$finalAddress', $controller);
        $this->assertStringContainsString('$placeOfOccurrence', $controller);
        $this->assertStringContainsString('$barangayLocation', $controller);
        $this->assertStringContainsString("str_contains(\$normalizedTitle, 'emergency voice call')", $controller);
    }

    public function test_registry_details_include_historical_responders_timeline_and_report_to_finish_duration(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/firefighters/incidents.blade.php');
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/staff/FirefighterController.php');

        $this->assertStringContainsString('data-view-incident', $view);
        $this->assertStringContainsString('Responding personnel', $view);
        $this->assertStringContainsString('Response timeline', $view);
        $this->assertStringContainsString('Report to finish', $view);
        $this->assertStringNotContainsString("{ label: 'Assigned team'", $view);
        $this->assertStringContainsString('IncidentResponderSnapshotService::class', $controller);
        $this->assertStringContainsString("'responders' => \$responders", $controller);
        $this->assertStringContainsString("'timeline' => \$timeline", $controller);
        $this->assertStringContainsString('diffInMinutes(Carbon::parse($completedAt))', $controller);
    }

    public function test_full_details_are_lazy_loaded_and_cached_per_incident(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/firefighters/incidents.blade.php');
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/staff/FirefighterController.php');
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $incidentModel = file_get_contents(__DIR__.'/../../app/Models/Incident.php');
        $unitModel = file_get_contents(__DIR__.'/../../app/Models/IncidentResponseUnit.php');

        $listLoader = substr(
            $controller,
            strpos($controller, 'protected function loadTeamIncidentsForRegistry'),
            strpos($controller, 'protected function transformAssignedIncident') - strpos($controller, 'protected function loadTeamIncidentsForRegistry')
        );

        $this->assertStringContainsString("name('incidents.registry.detail')", $routes);
        $this->assertStringContainsString('registryDetailEndpointTemplate', $view);
        $this->assertStringContainsString('async function fetchIncidentDetails', $view);
        $this->assertStringContainsString('incidentDetailCache = new Map()', $view);
        $this->assertStringContainsString('REGISTRY_DETAIL_CACHE_SECONDS = 60', $controller);
        $this->assertStringContainsString('Cache::remember(', $controller);
        $this->assertStringNotContainsString('responderSnapshot', $listLoader);
        $this->assertStringNotContainsString("                'responseUnits',", $listLoader);
        $this->assertStringContainsString('firefighter:registry:detail:', $incidentModel);
        $this->assertStringContainsString('firefighter:registry:detail:', $unitModel);
    }
}
