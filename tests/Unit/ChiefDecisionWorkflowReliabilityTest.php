<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\AnalysisRunCompletedNotification;
use Tests\TestCase;

class ChiefDecisionWorkflowReliabilityTest extends TestCase
{
    public function test_analysis_uses_evidence_quality_and_background_runs(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));
        $routes = file_get_contents(base_path('routes/api.php'));

        $this->assertStringContainsString("'method' => 'evidence_coverage_v2'", $controller);
        $this->assertStringContainsString("'score_adjustment_applied' => false", $controller);
        $this->assertStringContainsString('RunPredictiveAnalysisJob::dispatch', $controller);
        $this->assertStringContainsString('predictionRunStatus', $routes);
        $this->assertStringContainsString('command-decision-strip', $view);
        $this->assertStringContainsString('Evidence Quality', $view);
        $this->assertStringContainsString('Fallback estimate — review before acting.', $view);
    }

    public function test_completed_runs_notify_users_and_accuracy_uses_only_matured_forecasts(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $job = file_get_contents(app_path('Jobs/RunPredictiveAnalysisJob.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));

        $this->assertStringContainsString('AnalysisRunCompletedNotification', $job);
        $this->assertStringContainsString('$this->notifyRequester(true', $job);
        $this->assertStringContainsString("data_get(\$notification->data, 'category') === 'analysis_run'", $controller);
        $this->assertStringContainsString('greaterThanOrEqualTo(Carbon::today()->startOfMonth())', $controller);
        $this->assertStringContainsString("'operational_accuracy'", $controller);
        $this->assertStringContainsString('Real-world forecast accuracy', $view);
        $this->assertStringContainsString('Within 1 incident', $view);
        $this->assertStringContainsString('accuracyYearFilter', $view);
        $this->assertStringContainsString('accuracyQuarterFilter', $view);
        $this->assertStringContainsString("'outcomes' =>", $controller);
    }

    public function test_command_notifications_are_available_across_chief_and_admin_pages(): void
    {
        $chiefSidebar = file_get_contents(resource_path('views/chief/partials/sidebar.blade.php'));
        $adminSidebar = file_get_contents(resource_path('views/admin/partials/sidebar.blade.php'));
        $center = file_get_contents(resource_path('views/partials/command-notification-center.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("partials.command-notification-center', ['notificationRoutePrefix' => 'station-admin'", $chiefSidebar);
        $this->assertStringContainsString("partials.command-notification-center', ['notificationRoutePrefix' => 'admin'", $adminSidebar);
        $this->assertStringContainsString('data-command-notification-read-all', $center);
        $this->assertStringContainsString('window.setInterval(refresh, 30000)', $center);
        $this->assertSame(6, substr_count($routes, 'CommandNotificationController'));
    }

    public function test_critical_forecast_failures_use_email_without_emailing_successes(): void
    {
        config()->set('services.ai_prediction.failure_email_enabled', true);
        $user = new User(['email' => 'chief@example.com']);
        $failure = new AnalysisRunCompletedNotification('run-1', false, 'Forecast stopped.', '/station-admin/analytics');
        $success = new AnalysisRunCompletedNotification('run-2', true, 'Forecast ready.', '/station-admin/analytics');

        $this->assertSame(['database', 'mail'], $failure->via($user));
        $this->assertSame(['database'], $success->via($user));
        $this->assertSame('PyroSense forecast needs attention', $failure->toMail($user)->subject);
    }

    public function test_accuracy_exports_and_ci_security_audit_are_available(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $view = file_get_contents(resource_path('views/chief/analytics.blade.php'));
        $pdf = file_get_contents(resource_path('views/chief/exports/forecast-accuracy.blade.php'));
        $workflow = file_get_contents(base_path('.github/workflows/composer-security.yml'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('exportAccuracyCsv', $controller);
        $this->assertStringContainsString('exportAccuracyPdf', $controller);
        $this->assertStringContainsString('analytics/accuracy.csv', $routes);
        $this->assertStringContainsString("exportAccuracyReport('csv')", $view);
        $this->assertStringContainsString('Only forecasts whose complete target month has ended', $pdf);
        $this->assertStringContainsString('composer audit --locked --no-dev', $workflow);
        $this->assertStringContainsString("@include('admin.partials.sidebar'", $view);
    }

    public function test_checklist_handoffs_are_paginated_and_guarded(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $workspace = file_get_contents(resource_path('views/chief/partials/course-checklist-workspace.blade.php'));

        $this->assertStringContainsString('assertChecklistReadyForStatus', $controller);
        $this->assertStringContainsString('Add a short reason when a step is not followed.', $controller);
        $this->assertStringContainsString('$checklists = $query->paginate($perPage)', $controller);
        $this->assertStringContainsString('My tasks', $workspace);
        $this->assertStringContainsString('Needs review', $workspace);
        $this->assertStringContainsString('No owner', $workspace);
    }

    public function test_mitigation_results_require_measurement_proof_and_verification(): void
    {
        $chiefController = file_get_contents(app_path('Http/Controllers/Chief/StationAdminController.php'));
        $firefighterController = file_get_contents(app_path('Http/Controllers/Firefighter/BarangayCooperationController.php'));
        $fields = file_get_contents(resource_path('views/chief/partials/mitigation-form-fields.blade.php'));

        foreach (['baseline_condition', 'expected_result', 'blocked_reason', 'verification_date', 'follow_up_date'] as $field) {
            $this->assertStringContainsString("name=\"{$field}\"", $fields);
        }

        $this->assertStringContainsString('validateMitigationWorkflowRequirements', $chiefController);
        $this->assertStringContainsString('Completion cannot be approved without proof.', $chiefController);
        $this->assertStringContainsString('Proof is required for completion review.', $firefighterController);
        $this->assertStringContainsString("->selectRaw('COUNT(*) AS total')", $chiefController);
    }
}
