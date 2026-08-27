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
        $this->assertStringContainsString('class="lector-top-bar"', $view);
        $this->assertStringContainsString('id="userDropdownMenu"', $view);
        $this->assertStringContainsString('chief-theme-shell', $view);
        $this->assertStringContainsString('enctype="multipart/form-data"', $view);
        $this->assertStringContainsString('name="profile_image"', $view);
        $this->assertStringContainsString('accept="image/jpeg,image/png,image/webp"', $view);
        $this->assertStringContainsString('chiefProfilePhotoClear', $view);
        $this->assertStringContainsString("'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']", $controller);
        $this->assertStringContainsString('ProfileImageService $profileImages', $controller);
        $this->assertStringContainsString("hasFile('profile_image')", $controller);
        $this->assertStringContainsString("'thumbnail_url' => \$user->profile_thumbnail_url", $controller);
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
        $this->assertStringContainsString('class="lector-top-bar"', $view);
        $this->assertStringContainsString('id="userDropdownMenu"', $view);
        $this->assertStringContainsString('chief-theme-shell', $view);
    }
}
