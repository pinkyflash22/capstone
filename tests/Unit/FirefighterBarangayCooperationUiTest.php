<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirefighterBarangayCooperationUiTest extends TestCase
{
    #[Test]
    public function cooperation_workspace_uses_station_coverage_and_responsive_firefighter_navigation(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Firefighter/BarangayCooperationController.php'));
        $view = file_get_contents(resource_path('views/firefighters/barangay-cooperation.blade.php'));
        $sidebar = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));
        $updateRoute = Route::getRoutes()->getByName('firefighter.barangay-cooperation.checklists.items.update');

        $this->assertStringContainsString("->where('is_active', true)", $controller);
        $this->assertStringContainsString("->whereIn('barangay_id', \$barangayIds)", $controller);
        $this->assertStringContainsString('firefighter_visit_step_updated', $controller);
        $this->assertStringContainsString('data-barangay-filter', $view);
        $this->assertStringContainsString('data-checklist-step', $view);
        $this->assertStringContainsString('data-visit-proof-input', $view);
        $this->assertStringContainsString('data-cooperation-timeline', $view);
        $this->assertStringContainsString('BarangayNotificationService::notifyChecklistStepVerified', $controller);
        $this->assertStringContainsString('@media (max-width: 767px)', $view);
        $this->assertStringContainsString('Barangay Cooperation', $sidebar);
        $this->assertStringContainsString("'active' => ['cooperation']", $sidebar);
        $this->assertNotNull($updateRoute);
        $this->assertContains('auth', $updateRoute->gatherMiddleware());
        $this->assertContains('role:firefighter', $updateRoute->gatherMiddleware());

        $barangayDetail = file_get_contents(resource_path('views/barangay/partials/checklist-detail.blade.php'));
        $signoffRoute = Route::getRoutes()->getByName('barangay.checklists.visits.sign-off');
        $this->assertStringContainsString('barangay-signoff-form', $barangayDetail);
        $this->assertStringContainsString('checklists.visits.sign-off', $barangayDetail);
        $this->assertNotNull($signoffRoute);
        $this->assertContains('role:barangay,barangay captain,barangay officer,barangay_officer', $signoffRoute->gatherMiddleware());
    }
}
