<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitizenMobileNavigationTest extends TestCase
{
    #[Test]
    public function mobile_dock_uses_clear_icons_and_moves_notifications_into_the_long_press_menu(): void
    {
        $view = file_get_contents(resource_path('views/citizen/dashboard.blade.php'));

        $this->assertStringContainsString('data-citizen-sos-open', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Map', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Call', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Updates', $view);
        $this->assertStringContainsString('citizen-mobile-dock__label">Settings', $view);
        $this->assertStringContainsString('citizen-mobile-dock__burst-label">Notifications', $view);

        $dockStart = strpos($view, '<nav class="citizen-mobile-dock');
        $dockEnd = strpos($view, '</nav>', $dockStart);
        $dock = substr($view, $dockStart, $dockEnd - $dockStart);
        $notificationPosition = strpos($dock, 'data-citizen-alerts-open');
        $burstPosition = strpos($dock, 'data-citizen-nav-burst');

        $this->assertNotFalse($notificationPosition);
        $this->assertNotFalse($burstPosition);
        $this->assertGreaterThan($burstPosition, $notificationPosition);
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
