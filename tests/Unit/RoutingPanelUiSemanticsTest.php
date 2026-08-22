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
        $dashboardStyles = file_get_contents(resource_path('css/firefighters/dashboard.css'));
        $sharedMapScript = file_get_contents(resource_path('js/map.js'));
        $firefighterDashboard = file_get_contents(resource_path('views/firefighters/dashboard.blade.php'));

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
        $this->assertStringContainsString('body.firefighter-dashboard-page .pyro-routing-launcher { right:14px;bottom:calc(164px', $styles);

        $this->assertStringContainsString("import './routing-panel.js';", $sharedMapScript);
        $this->assertStringContainsString("route.destination_title = destination?.title || 'Selected destination';", $sharedMapScript);
        $this->assertStringContainsString("event.target?.closest?.('[data-stop-route-navigation]')", $sharedMapScript);
        $this->assertStringContainsString('remaining: activeTrucks.size', $sharedMapScript);
        $this->assertStringNotContainsString('data-speed="slow"', $sharedMapScript);
        $this->assertStringNotContainsString('.speed-control', $dashboardStyles);
        $this->assertStringContainsString('data-stop-route-navigation', $firefighterDashboard);
        $this->assertStringContainsString('id="routeTrackingMode"', $firefighterDashboard);
        $this->assertStringNotContainsString('data-speed="slow"', $firefighterDashboard);
        $this->assertStringContainsString("@vite(['resources/js/map.js', 'resources/js/firefighter/dashboard-shell.js'])", $firefighterDashboard);
        $this->assertStringContainsString("@include('partials.pwa-body', ['showPwaInstallButton' => false])", $firefighterDashboard);
        $this->assertStringNotContainsString("@include('partials.pwa-body', ['showPwaInstallButton' => true])", $firefighterDashboard);
    }
}
