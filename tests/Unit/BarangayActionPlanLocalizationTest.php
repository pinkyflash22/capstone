<?php

namespace Tests\Unit;

use App\Http\Controllers\Chief\AnalyticController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class BarangayActionPlanLocalizationTest extends TestCase
{
    #[Test]
    public function station_wide_plans_are_localized_from_each_barangays_own_risk_data(): void
    {
        $controller = (new ReflectionClass(AnalyticController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'localizeStationWideActionPlan');

        $rawData = [
            'barangay_predictions' => [
                [
                    'barangay_id' => 11,
                    'barangay_name' => 'Barangay Alpha',
                    'risk_level' => 'high',
                    'probability_percent' => 72,
                    'drivers' => [['summary' => 'Repeated electrical incidents']],
                ],
                [
                    'barangay_id' => 22,
                    'barangay_name' => 'Barangay Beta',
                    'risk_level' => 'low',
                    'probability_percent' => 18,
                    'drivers' => [['summary' => 'Low recent incident pressure']],
                ],
            ],
            'recommendation_context' => [
                'focus_findings' => [
                    [
                        'barangay_id' => 11,
                        'barangay_name' => 'Barangay Alpha',
                        'condition_label' => 'exposed wiring',
                        'street_or_sitio' => 'Purok 1',
                        'assessment_level' => 'high',
                        'summary' => 'Exposed wiring near dense homes',
                    ],
                    [
                        'barangay_id' => 22,
                        'barangay_name' => 'Barangay Beta',
                        'condition_label' => 'blocked access road',
                        'street_or_sitio' => 'Sitio Riverside',
                        'assessment_level' => 'low',
                        'summary' => 'Access road requires monitoring',
                    ],
                ],
            ],
        ];

        $basePlan = ['plan_title' => 'Generic station plan', 'checklist_items' => ['Generic step']];
        $baseRecommendation = ['title' => 'Generic station recommendation', 'priority' => 'high'];

        [$alphaPlan, $alphaRecommendation] = $method->invoke($controller, $basePlan, $baseRecommendation, 11, $rawData);
        [$betaPlan, $betaRecommendation] = $method->invoke($controller, $basePlan, $baseRecommendation, 22, $rawData);

        $this->assertNotSame($alphaPlan['plan_title'], $betaPlan['plan_title']);
        $this->assertStringContainsString('Barangay Alpha', $alphaPlan['plan_title']);
        $this->assertStringContainsString('exposed wiring', $alphaPlan['plan_title']);
        $this->assertStringContainsString('Barangay Beta', $betaPlan['plan_title']);
        $this->assertStringContainsString('blocked access road', $betaPlan['plan_title']);
        $this->assertSame('high', $alphaPlan['priority_level']);
        $this->assertSame('low', $betaPlan['priority_level']);
        $this->assertSame($alphaPlan['plan_title'], $alphaRecommendation['title']);
        $this->assertSame($betaPlan['plan_title'], $betaRecommendation['title']);
        $this->assertStringContainsString('72%', $alphaPlan['plan_description']);
        $this->assertStringContainsString('18%', $betaPlan['plan_description']);
        $this->assertCount(4, $alphaPlan['checklist_items']);
        $this->assertNotSame($alphaPlan['checklist_items'][0], $betaPlan['checklist_items'][0]);
    }

    #[Test]
    public function compact_pages_keep_full_information_behind_view_controls(): void
    {
        $mitigationView = file_get_contents(resource_path('views/chief/mitigations.blade.php'));
        $checklistView = file_get_contents(resource_path('views/chief/partials/course-checklist-workspace.blade.php'));

        $this->assertStringContainsString('margin-left: var(--sidebar-w, 280px)', $mitigationView);
        $this->assertStringNotContainsString('margin-left: 348px', $mitigationView);
        $this->assertStringContainsString('data-open-checklist', $checklistView);
        $this->assertStringContainsString('modal-plan-summary', $checklistView);
        $this->assertStringNotContainsString("optional(\$checklist->created_at)->format('M d, Y')", $checklistView);
    }
}
