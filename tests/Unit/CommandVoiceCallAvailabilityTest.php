<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommandVoiceCallAvailabilityTest extends TestCase
{
    #[Test]
    public function chief_and_admin_shared_shells_load_calling_once_on_every_page(): void
    {
        $chiefSidebar = file_get_contents(resource_path('views/chief/partials/sidebar.blade.php'));
        $adminSidebar = file_get_contents(resource_path('views/admin/partials/sidebar.blade.php'));
        $config = file_get_contents(resource_path('views/partials/voice-call-config.blade.php'));

        $this->assertStringContainsString("@include('partials.voice-call-config')", $chiefSidebar);
        $this->assertStringContainsString("@include('partials.voice-call-config')", $adminSidebar);
        $this->assertStringStartsWith('@once', trim($config));
        $this->assertStringContainsString('@endonce', $config);

        foreach ([
            'chief/analytics.blade.php',
            'chief/management.blade.php',
            'chief/reports.blade.php',
            'admin/analytics.blade.php',
            'admin/management.blade.php',
            'admin/reports.blade.php',
        ] as $viewPath) {
            $view = file_get_contents(resource_path("views/{$viewPath}"));
            $expectedSidebar = str_starts_with($viewPath, 'chief/')
                ? "chief.partials.sidebar"
                : "admin.partials.sidebar";

            $this->assertStringContainsString($expectedSidebar, $view, $viewPath);
        }

        foreach ([
            'admin/alerts.blade.php',
            'admin/barangay-show.blade.php',
            'admin/incidentmap.blade.php',
            'admin/weather.blade.php',
        ] as $legacyViewPath) {
            $view = file_get_contents(resource_path("views/{$legacyViewPath}"));
            $this->assertStringContainsString("@include('partials.voice-call-config')", $view, $legacyViewPath);
        }
    }

    #[Test]
    public function provincial_admin_is_a_frontend_and_backend_call_responder(): void
    {
        $script = file_get_contents(resource_path('js/voice-calls.js'));
        $controller = file_get_contents(app_path('Http/Controllers/VoiceCallController.php'));

        $this->assertStringContainsString("'provincial admin'", $script);
        $this->assertStringContainsString("'provincial admin',", $controller);
        $this->assertStringContainsString('$stationId = $stationId ?: ($priorCall->station_id', $controller);
    }
}
