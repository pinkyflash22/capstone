<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutingPanelUiSemanticsTest extends TestCase
{
    #[Test]
    public function routing_uses_a_shared_floating_launcher_and_responsive_panel(): void
    {
        $script = file_get_contents(resource_path('js/routing-panel.js'));
        $styles = file_get_contents(resource_path('css/routing-panel.css'));

        $this->assertStringContainsString('pyroRoutingLauncher', $script);
        $this->assertStringContainsString('pyroRoutingPanel', $script);
        $this->assertStringContainsString('pyroRoutingClose', $script);
        $this->assertStringContainsString('#routeInfoCard, #userRouteInfoCard', $script);
        $this->assertStringContainsString('No emergency route is connected', $script);

        $this->assertStringContainsString('.pyro-routing-launcher', $styles);
        $this->assertStringContainsString('.pyro-routing-panel.is-pyro-routing-open', $styles);
        $this->assertStringContainsString('.pyro-routing-backdrop:not([hidden])', $styles);
        $this->assertStringContainsString('max-height:min(72dvh, 650px)', $styles);
        $this->assertStringContainsString('body.pyro-routing-panel-open { overflow:hidden; }', $styles);
    }
}
