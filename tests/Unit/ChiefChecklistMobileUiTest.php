<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChiefChecklistMobileUiTest extends TestCase
{
    #[Test]
    public function checklist_register_becomes_touch_friendly_cards_on_mobile_and_installed_pwa(): void
    {
        $view = file_get_contents(resource_path('views/chief/course-checklists.blade.php'));

        $this->assertStringContainsString('data-checklist-mobile-style', $view);
        $this->assertStringContainsString('(display-mode: standalone) and (max-width: 1024px)', $view);
        $this->assertStringContainsString('.checklist-topbar { display: none !important; }', $view);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $view);
        $this->assertStringContainsString('border-radius: 18px', $view);
        $this->assertStringContainsString('.checklist-table td[data-label="Actions"] { position: static; grid-column: 1 / -1; }', $view);
        $this->assertStringContainsString('.checklist-modal { z-index: 12000;', $view);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $view);
    }

    #[Test]
    public function checklist_buttons_use_generated_routes_and_recover_from_expired_csrf_sessions(): void
    {
        $view = file_get_contents(resource_path('views/chief/course-checklists.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        foreach (['history', 'items', 'confirm', 'bulk'] as $endpoint) {
            $this->assertStringContainsString("station-admin.action-plan-checklists.{$endpoint}", $view);
            $this->assertStringContainsString("action-plan-checklists.{$endpoint}", $routes);
        }

        $this->assertStringContainsString('async function checklistFetch', $view);
        $this->assertStringContainsString('response.status === 419', $view);
        $this->assertStringContainsString('await refreshChecklistSession()', $view);
        $this->assertStringContainsString("credentials: 'same-origin'", $view);
        $this->assertStringContainsString('The checklist server could not be reached.', $view);
        $this->assertStringNotContainsString("fetch('/station-admin/api/action-plan-checklists", $view);
    }

    #[Test]
    public function checklist_desktop_layout_respects_sidebar_and_long_plan_titles(): void
    {
        $view = file_get_contents(resource_path('views/chief/course-checklists.blade.php'));
        $workspace = file_get_contents(resource_path('views/chief/partials/course-checklist-workspace.blade.php'));

        $this->assertStringContainsString('@media(min-width:1025px)', $view);
        $this->assertStringContainsString('margin-left:var(--sidebar-w,280px)', $view);
        $this->assertStringContainsString('.checklist-modal{left:var(--sidebar-w,280px)}', $view);
        $this->assertStringContainsString('.plan-cell>span:last-child{display:block;min-width:0;flex:1}', $view);
        $this->assertStringContainsString('-webkit-line-clamp:2', $view);
        $this->assertStringContainsString('table-layout:fixed;min-width:760px', $view);

        $this->assertStringContainsString('Plan and area', $workspace);
        $this->assertStringContainsString('Priority and status', $workspace);
        $this->assertStringContainsString('class="checklist-col-area"', $workspace);
        $this->assertStringContainsString('class="checklist-col-status"', $workspace);
        $this->assertStringContainsString('class="table-state"', $workspace);
    }

    #[Test]
    public function authenticated_browser_audit_is_fail_fast_and_has_an_isolated_fixture(): void
    {
        $audit = file_get_contents(base_path('tests/Browser/run-announcement-visual-smoke.ps1'));
        $workflow = file_get_contents(base_path('.github/workflows/chief-checklist-browser.yml'));
        $seeder = file_get_contents(database_path('seeders/BrowserChecklistSmokeSeeder.php'));

        $this->assertStringContainsString('[switch] $Assert', $audit);
        $this->assertStringContainsString('if ($Assert)', $audit);
        $this->assertStringContainsString('Authenticated checklist PATCH did not reach Laravel.', $audit);
        $this->assertStringContainsString('-Assert', $workflow);
        $this->assertStringContainsString('BrowserChecklistSmokeSeeder', $workflow);
        $this->assertStringContainsString('browser-checklist-responsive-smoke-v1', $seeder);
    }
}
