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
}
