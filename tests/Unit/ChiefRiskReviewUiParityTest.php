<?php

namespace Tests\Unit;

use Tests\TestCase;

class ChiefRiskReviewUiParityTest extends TestCase
{
    public function test_chief_and_firefighter_modes_render_the_same_risk_review_view(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/RiskAreaReviewController.php'));

        $this->assertStringContainsString(
            "in_array(\$mode, ['chief', 'firefighter'], true)",
            $controller
        );
        $this->assertStringContainsString("? 'firefighters.risk-reviews'", $controller);
        $this->assertStringContainsString(": 'Risk & Hazard Verification'", $controller);
    }

    public function test_shared_risk_review_view_keeps_role_specific_navigation_and_routes(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/risk-reviews.blade.php'));

        $this->assertStringContainsString("@include('chief.partials.sidebar', ['active' => 'risk-reviews'])", $view);
        $this->assertStringContainsString("@include('firefighters.partials.shell-sidebar', ['active' => 'risk-reviews'])", $view);
        $this->assertStringContainsString("route(\$indexRouteName)", $view);
        $this->assertStringContainsString("route(\$reviewRouteName", $view);
        $this->assertStringContainsString("route('station-admin.profile')", $view);
    }
}
