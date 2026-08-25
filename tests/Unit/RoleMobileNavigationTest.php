<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleMobileNavigationTest extends TestCase
{
    #[Test]
    public function firefighter_dock_is_labeled_and_secondary_navigation_is_in_the_menu(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));

        foreach (['Schedule', 'Alerts', 'Updates', 'Menu'] as $label) {
            $this->assertStringContainsString("ff-mobile-dock__label\">{$label}", $view);
        }

        $this->assertStringContainsString('aria-label="Open Responder Hub menu"', $view);
        $this->assertStringContainsString('<h2 class="ff-mobile-panel__title">Menu</h2>', $view);
        $this->assertStringContainsString('Responder navigation', $view);
        $this->assertStringContainsString('$mobileMenuItems as $item', $view);
        $this->assertStringContainsString('ff-mobile-panel__action-trailing', $view);
        $this->assertStringNotContainsString('ff-mobile-dock__burst', $view);
        $this->assertStringNotContainsString('openBurstMenu', $view);
        $this->assertStringNotContainsString('holdTriggered', $view);

        foreach (['firefighter.incidents', 'firefighter.risk-reviews.index', 'firefighter.barangay-cooperation.index', 'firefighter.my-station', 'firefighter.equipments'] as $route) {
            $this->assertStringContainsString("'route' => '{$route}'", $view);
        }
    }

    #[Test]
    public function chief_dock_is_labeled_and_command_pages_are_in_the_menu(): void
    {
        $view = file_get_contents(resource_path('views/chief/partials/sidebar.blade.php'));

        foreach (['Schedule', 'Alerts', 'Forecast', 'Menu'] as $label) {
            $this->assertStringContainsString("chief-mobile-dock__label\">{$label}", $view);
        }

        $this->assertStringContainsString('aria-label="Open Chief Command menu"', $view);
        $this->assertStringContainsString('<h2 class="chief-mobile-panel__title">Menu</h2>', $view);
        $this->assertStringContainsString('Command navigation', $view);
        $this->assertStringContainsString('$mobileCenterActions as $action', $view);
        $this->assertGreaterThanOrEqual(3, substr_count($view, 'chief-mobile-panel__action-trailing'));
        $this->assertStringContainsString("'dashboard' => 'fa-solid fa-table-cells-large'", $view);
        $this->assertStringNotContainsString('material-symbols-rounded chief-mobile-material-icon', $view);
    }

    #[Test]
    public function barangay_dock_is_labeled_and_barangay_pages_are_in_the_menu(): void
    {
        $view = file_get_contents(resource_path('views/barangay/partials/sidebar.blade.php'));

        foreach (['Map', 'Reports', 'Risks', 'Menu'] as $label) {
            $this->assertStringContainsString("barangay-mobile-dock__label\">{$label}", $view);
        }

        $this->assertStringContainsString('aria-label="Open Barangay Desk menu"', $view);
        $this->assertStringContainsString('<h2 class="chief-mobile-panel__title">Menu</h2>', $view);
        $this->assertStringContainsString('Barangay navigation', $view);
        $this->assertStringContainsString('array_slice($mobileCenterActions, 0, 2)', $view);
        $this->assertStringContainsString('chief-mobile-panel__action-trailing', $view);
        $this->assertStringContainsString('data-pyro-voice-call data-barangay-panel-close', $view);
        $this->assertStringNotContainsString('barangay-mobile-dock__burst', $view);
        $this->assertStringNotContainsString('data-barangay-nav-trigger', $view);
        $this->assertStringNotContainsString('holdDelay', $view);
    }
}
