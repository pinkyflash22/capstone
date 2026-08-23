<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnifiedNotificationUiTest extends TestCase
{
    #[Test]
    public function every_primary_user_shell_loads_the_shared_notification_presenter(): void
    {
        $partial = file_get_contents(resource_path('views/partials/unified-notifications.blade.php'));
        $pwaBody = file_get_contents(resource_path('views/partials/pwa-body.blade.php'));
        $chiefSidebar = file_get_contents(resource_path('views/chief/partials/sidebar.blade.php'));
        $adminSidebar = file_get_contents(resource_path('views/admin/partials/sidebar.blade.php'));
        $barangayLayout = file_get_contents(resource_path('views/barangay/layouts/app.blade.php'));
        $firefighterSidebar = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));

        $this->assertStringContainsString("@vite('resources/js/unified-notifications.js')", $partial);
        $this->assertStringContainsString("session('success')", $partial);
        $this->assertStringContainsString("\$errors->first()", $partial);
        $this->assertStringContainsString("@include('partials.unified-notifications')", $pwaBody);
        $this->assertStringContainsString("@include('partials.unified-notifications')", $chiefSidebar);
        $this->assertStringContainsString("@include('partials.unified-notifications')", $adminSidebar);
        $this->assertStringContainsString("@include('partials.unified-notifications')", $barangayLayout);
        $this->assertStringContainsString("@include('partials.unified-notifications')", $firefighterSidebar);
    }

    #[Test]
    public function presenter_uses_desktop_toasts_mobile_snackbars_and_adapts_legacy_calls(): void
    {
        $script = file_get_contents(resource_path('js/unified-notifications.js'));
        $styles = file_get_contents(resource_path('css/unified-notifications.css'));
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString('window.PyroNotify = api', $script);
        $this->assertStringContainsString('window.showToast =', $script);
        $this->assertStringContainsString('window.showNotification =', $script);
        $this->assertStringContainsString('window.alert =', $script);
        $this->assertStringContainsString('new MutationObserver', $script);
        $this->assertStringContainsString("window.addEventListener('pyro:notify'", $script);
        $this->assertStringContainsString('.pyro-notice-region', $styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $styles);
        $this->assertStringContainsString('html.pyro-notifications-ready :is(', $styles);
        $this->assertStringNotContainsString('.notification-dropdown', $styles);
        $this->assertStringContainsString("'resources/js/unified-notifications.js'", $vite);
    }

    #[Test]
    public function active_role_presenters_delegate_to_the_shared_notification_api(): void
    {
        $sources = [
            resource_path('js/admin/announcements.js'),
            resource_path('js/chief/announcements.js'),
            resource_path('js/firefighter/announcements.js'),
            resource_path('js/firefighter/dashboard-shell.js'),
            resource_path('js/firefighter/station-shell.js'),
            resource_path('js/landing/main.js'),
            resource_path('js/map.js'),
            resource_path('js/mobile-hub.js'),
            resource_path('views/admin/reports.blade.php'),
            resource_path('views/chief/analytics.blade.php'),
            resource_path('views/citizen/dashboard.blade.php'),
            resource_path('views/firefighters/barangay-cooperation.blade.php'),
            resource_path('views/firefighters/incidents.blade.php'),
        ];

        foreach ($sources as $source) {
            $this->assertStringContainsString(
                'PyroNotify',
                file_get_contents($source),
                basename($source).' must use the unified notification presenter.'
            );
        }

        $script = file_get_contents(resource_path('js/unified-notifications.js'));
        $this->assertStringContainsString('actionLabel', $script);
        $this->assertStringContainsString('activeByKey', $script);
        $this->assertStringContainsString('options.persistent', $script);
    }
}
