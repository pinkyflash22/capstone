<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChiefProfileExecutiveReportsUiTest extends TestCase
{
    #[Test]
    public function chief_profile_uses_database_activity_with_server_pagination(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/StationAdminController.php'));
        $view = file_get_contents(resource_path('views/chief/profile.blade.php'));

        $this->assertStringContainsString("UserLog::query()", $controller);
        $this->assertStringContainsString("'activity_page'", $controller);
        $this->assertStringContainsString('$activityLogs->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('Recent account activity', $view);
        $this->assertStringContainsString('Station assignment', $view);
        $this->assertStringContainsString('chiefProfileForm', $view);
    }

    #[Test]
    public function executive_reports_are_station_scoped_filtered_and_paginated_from_the_database(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/StationAdminController.php'));
        $view = file_get_contents(resource_path('views/chief/reports.blade.php'));

        $this->assertStringContainsString("->whereIn('station_officer_id', \$stationOfficerIds)", $controller);
        $this->assertStringContainsString("->paginate(\$perPage, ['*'], 'reports_page')", $controller);
        $this->assertStringContainsString('$reportRows->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('Real database records', $view);
        $this->assertStringContainsString('No matching records', $view);
        $this->assertStringNotContainsString('window.investigationData', $view);
        $this->assertStringNotContainsString('Mock investigation data', $view);
        $this->assertStringNotContainsString('localStorage', $view);
    }
}
