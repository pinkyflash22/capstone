<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirefighterCommunicationPlacementUiTest extends TestCase
{
    #[Test]
    public function calling_is_in_the_profile_topbar_and_not_the_sidebar_or_mobile_notifications(): void
    {
        $topbar = file_get_contents(resource_path('views/firefighters/partials/shell-topbar.blade.php'));
        $sidebar = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));

        $this->assertStringContainsString('class="ff-topbar-call"', $topbar);
        $this->assertStringContainsString('data-pyro-voice-call', $topbar);
        $this->assertStringContainsString('Call station', $topbar);
        $this->assertStringNotContainsString("'type' => 'voice'", $sidebar);
        $this->assertStringNotContainsString('data-pyro-voice-call', $sidebar);
    }

    #[Test]
    public function mobile_notifications_include_my_station_with_the_other_pages(): void
    {
        $sidebar = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));
        $panelStart = strpos($sidebar, 'data-ff-notifications-panel');
        $panelEnd = strpos($sidebar, 'data-ff-settings-panel', $panelStart);
        $panel = substr($sidebar, $panelStart, $panelEnd - $panelStart);

        $this->assertStringContainsString("route('firefighter.my-station')", $panel);
        $this->assertStringContainsString('<strong>My Station</strong>', $panel);
        $this->assertStringContainsString('data-ff-nav-key="station"', $panel);
    }

    #[Test]
    public function voice_panel_icons_have_reliable_firefighter_fallbacks(): void
    {
        $script = file_get_contents(resource_path('js/voice-calls.js'));
        $fallback = file_get_contents(resource_path('views/firefighters/partials/icon-fallback.blade.php'));

        foreach (['fa-headset', 'fa-fire-flame-curved', 'fa-microphone', 'fa-microphone-slash', 'fa-volume-high', 'fa-volume-xmark', 'fa-phone-slash', 'fa-play', 'fa-pause'] as $icon) {
            $this->assertStringContainsString("'{$icon}':", $fallback);
        }

        $this->assertStringNotContainsString('aria-hidden="true">⌖</span>', $script);
        $this->assertStringContainsString('replaceVoiceControlIcon', $script);
        $this->assertStringContainsString('fa-fire-extinguisher', $script);
        $this->assertStringContainsString('fa-location-dot', $script);
        $this->assertStringContainsString('fa-flag', $script);
    }
}
