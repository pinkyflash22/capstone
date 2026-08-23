<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitizenMobileNavigationTest extends TestCase
{
    #[Test]
    public function mobile_dock_keeps_only_risk_reporting_in_the_long_press_menu(): void
    {
        $view = file_get_contents(resource_path('views/citizen/dashboard.blade.php'));

        $this->assertStringContainsString('data-citizen-sos-open', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Map', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Call', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Updates', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Menu', $view);

        $burstStart = strpos($view, 'data-citizen-nav-burst');
        $burstEnd = strpos($view, '</div>', $burstStart);
        $burst = substr($view, $burstStart, $burstEnd - $burstStart);

        $this->assertNotFalse($burstStart);
        $this->assertStringContainsString('data-citizen-risk-hazard-open', $burst);
        $this->assertSame(1, substr_count($burst, 'citizen-mobile-dock__burst-item'));
        $this->assertStringNotContainsString('data-citizen-alerts-open', $burst);
        $this->assertStringNotContainsString('data-citizen-insights-open', $burst);
    }

    #[Test]
    public function moved_navigation_is_available_in_the_mobile_menu_with_trailing_chevrons(): void
    {
        $view = file_get_contents(resource_path('views/citizen/dashboard.blade.php'));
        $menuStart = strpos($view, 'data-citizen-settings-panel');
        $menuEnd = strpos($view, 'data-citizen-map-layers-panel', $menuStart);
        $menu = substr($view, $menuStart, $menuEnd - $menuStart);

        $this->assertStringContainsString('data-citizen-switch-section="fsic"', $menu);
        $this->assertStringContainsString('data-citizen-switch-section="alerts"', $menu);
        $this->assertStringContainsString('data-citizen-switch-section="contacts"', $menu);
        $this->assertStringContainsString('data-citizen-insights-open', $menu);
        $this->assertStringContainsString('data-citizen-alerts-open', $menu);
        $this->assertStringContainsString('data-citizen-switch-section="profile"', $menu);
        $this->assertStringContainsString('data-citizen-open-logs', $menu);
        $this->assertGreaterThanOrEqual(7, substr_count($menu, 'citizen-mobile-panel__action-trailing'));
    }

    #[Test]
    public function citizen_calling_uses_agora_then_station_telephone_then_911(): void
    {
        $config = file_get_contents(resource_path('views/partials/voice-call-config.blade.php'));
        $voiceCalls = file_get_contents(resource_path('js/voice-calls.js'));
        $view = file_get_contents(resource_path('views/citizen/dashboard.blade.php'));

        $this->assertStringContainsString('fallbackStations:', $config);
        $this->assertStringContainsString('openEmergencyTelephoneFallback', $voiceCalls);
        $this->assertStringContainsString('window.location.assign(`tel:${stationPhone}`)', $voiceCalls);
        $this->assertStringContainsString("window.location.assign('tel:911')", $voiceCalls);
        $this->assertStringContainsString('Agora first, station phone if unavailable', $view);
    }
}
