<?php

namespace Tests\Unit;

use Tests\TestCase;

class FirefighterLayerHierarchyTest extends TestCase
{
    public function test_firefighter_map_is_contained_below_shared_navigation_and_notifications(): void
    {
        $shell = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));
        $dashboard = file_get_contents(resource_path('css/firefighters/dashboard-shell.css'));
        $routing = file_get_contents(resource_path('css/routing-panel.css'));

        $this->assertStringContainsString('--ff-layer-map: 0;', $shell);
        $this->assertStringContainsString('--ff-layer-mobile-dock: 2000;', $shell);
        $this->assertStringContainsString('--ff-layer-sidebar: 3010;', $shell);
        $this->assertStringContainsString('--ff-layer-mobile-panel: 5000;', $shell);
        $this->assertStringContainsString('--ff-layer-notice: 6000;', $shell);

        $this->assertStringContainsString('body.firefighter-portal-shell .firefighter-map-stage', $shell);
        $this->assertStringContainsString('isolation: isolate !important;', $shell);
        $this->assertStringContainsString('z-index: var(--ff-layer-mobile-panel) !important;', $shell);
        $this->assertStringContainsString('z-index: var(--ff-layer-notice) !important;', $shell);

        $this->assertStringContainsString('z-index: var(--ff-layer-map, 0) !important;', $dashboard);
        $this->assertStringContainsString('z-index: var(--ff-layer-mobile-dock, 2000) !important;', $dashboard);
        $this->assertStringContainsString('var(--ff-layer-routing-backdrop, 2050)', $routing);
        $this->assertStringContainsString('var(--ff-layer-routing-panel, 2060)', $routing);
    }
}
