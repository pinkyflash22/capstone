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
        $personnel = file_get_contents(resource_path('views/firefighters/mystation-personnel.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));

        $this->assertStringContainsString('first_name,last_name,email,contact_number,image,role_id', $controller);
        $this->assertStringContainsString("'profile_image_url' => \$member->user->profile_image_url", $controller);
        $this->assertStringContainsString('data-crew-member-card', $personnel);
        $this->assertStringContainsString('id="crewMemberDetailModal"', $personnel);
        $this->assertStringContainsString('function openCrewMemberModal', $script);
        $this->assertStringContainsString('function closeCrewMemberModal', $script);
        $this->assertStringContainsString('.crew-member-card:hover', $styles);
        $this->assertStringContainsString('.crew-member-modal__dialog', $styles);
        $this->assertStringContainsString('Identity and contact safeguards live with the primary station breakpoint.', $styles);
        $this->assertStringNotContainsString('My Station mobile identity polish', $styles);
        $this->assertStringContainsString('[data-ff-page-root="station"] > .main-content > .station-shell-page', $styles);
        $this->assertStringNotContainsString('.main-content:has(> [data-ff-page-root="station"])', $styles);
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
