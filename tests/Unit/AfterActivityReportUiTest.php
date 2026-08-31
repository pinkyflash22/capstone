<?php

namespace Tests\Unit;

use Tests\TestCase;

class AfterActivityReportUiTest extends TestCase
{
    public function test_my_station_exposes_a_paginated_aar_workflow_for_scheduled_and_unscheduled_work(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $table = file_get_contents(resource_path('views/firefighters/mystation-reports-table.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $controller = file_get_contents(app_path('Http/Controllers/staff/AfterActivityReportController.php'));

        $this->assertStringContainsString('data-report-tab="aar"', $view);
        $this->assertStringContainsString('Unscheduled operation or activity', $view);
        $this->assertStringContainsString('name="event_activity_id"', $view);
        $this->assertStringContainsString('name="proof_images[]"', $view);
        $this->assertStringContainsString("\$tab === 'aar'", $table);
        $this->assertStringContainsString('$reports->hasPages()', $table);
        $this->assertStringContainsString('appendAarReportRow', $script);
        $this->assertStringContainsString("\$validated['station_id'] = \$officer->station_id", $controller);
        $this->assertStringContainsString("'before_or_equal:today'", $controller);
    }
}
