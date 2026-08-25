<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChiefFloatingActionUiTest extends TestCase
{
    #[Test]
    public function chief_mobile_call_and_route_actions_are_compact_and_modal_safe(): void
    {
        $css = file_get_contents(resource_path('css/chief/dashboard.css'));

        $this->assertStringContainsString('body.chief-dashboard-page .pyro-voice-launcher', $css);
        $this->assertStringContainsString('body.chief-legacy-layout .pyro-routing-launcher', $css);
        $this->assertStringContainsString('width: 44px !important;', $css);
        $this->assertStringContainsString('height: 44px !important;', $css);
        $this->assertStringContainsString('z-index: 245 !important;', $css);
        $this->assertStringContainsString('body.chief-dashboard-page.menu-open .pyro-voice-launcher', $css);
        $this->assertStringContainsString('body.chief-legacy-layout.modal-open .pyro-routing-launcher', $css);
        $this->assertStringContainsString('body.chief-dashboard-page.pyro-routing-panel-open .pyro-voice-launcher', $css);
        $this->assertStringContainsString(':has(.chief-mobile-panel.is-open, .modal.show, .modal.active', $css);
        $this->assertStringContainsString('pointer-events: none !important;', $css);
    }
}
