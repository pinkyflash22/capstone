<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitizenCommunityRequestUiTest extends TestCase
{
    #[Test]
    public function request_history_sits_beside_posts_and_form_opens_in_a_modal(): void
    {
        $view = file_get_contents(resource_path('views/citizen/sections/announcements.blade.php'));

        $workspace = strpos($view, 'class="citizen-announcements-workspace"');
        $stream = strpos($view, 'class="citizen-announcements-stream"', $workspace);
        $sidebar = strpos($view, 'citizen-request-card--sidebar', $workspace);
        $modal = strpos($view, 'id="citizenCommunityRequestModal"');
        $form = strpos($view, "route('citizen.community-activity-requests.store')", $modal);

        $this->assertNotFalse($workspace);
        $this->assertNotFalse($stream);
        $this->assertNotFalse($sidebar);
        $this->assertGreaterThan($stream, $sidebar);
        $this->assertStringContainsString('data-community-request-open', $view);
        $this->assertNotFalse($modal);
        $this->assertGreaterThan($sidebar, $modal);
        $this->assertNotFalse($form);
        $this->assertStringContainsString("grid-template-columns: minmax(0, 1fr) minmax(280px, 340px)", $view);
        $this->assertStringContainsString('@if(!$errors->communityRequest->any()) hidden @endif', $view);
    }

    #[Test]
    public function citizen_request_validation_uses_an_isolated_error_bag(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/CommunityActivityRequestController.php'));

        $this->assertStringContainsString("validateWithBag('communityRequest', \$rules)", $controller);
    }

    #[Test]
    public function request_form_keeps_a_clear_submit_action_visible_while_fields_scroll(): void
    {
        $view = file_get_contents(resource_path('views/citizen/sections/announcements.blade.php'));

        $this->assertStringContainsString('class="citizen-request-form__body"', $view);
        $this->assertStringContainsString('Save and send request', $view);
        $this->assertStringContainsString('.citizen-request-modal .citizen-request-form {', $view);
        $this->assertStringContainsString('.citizen-request-form__body {', $view);
        $this->assertStringContainsString('overflow-y: auto;', $view);
        $this->assertStringContainsString('flex: 0 0 auto;', $view);
        $this->assertStringContainsString('env(safe-area-inset-bottom, 0px)', $view);
    }

    #[Test]
    public function request_form_marks_required_fields_and_prevents_duplicate_submissions(): void
    {
        $view = file_get_contents(resource_path('views/citizen/sections/announcements.blade.php'));

        $this->assertSame(3, substr_count($view, 'class="citizen-request-required"'));
        $this->assertStringContainsString('data-community-request-submit', $view);
        $this->assertStringContainsString("requestSubmitButton.disabled = true", $view);
        $this->assertStringContainsString("requestForm.setAttribute('aria-busy', 'true')", $view);
        $this->assertStringContainsString("requestSubmitLabel.textContent = 'Sending request...'", $view);
        $this->assertStringContainsString("requestSubmitIcon.className = 'fa-solid fa-spinner fa-spin'", $view);
        $this->assertStringContainsString('if (requestSubmitting)', $view);
    }

    #[Test]
    public function slow_submissions_offer_a_safe_status_refresh_before_retrying(): void
    {
        $view = file_get_contents(resource_path('views/citizen/sections/announcements.blade.php'));

        $this->assertStringContainsString('data-community-request-submit-status', $view);
        $this->assertStringContainsString('data-community-request-refresh', $view);
        $this->assertStringContainsString('Refresh and check requests', $view);
        $this->assertStringContainsString('requestSubmissionTimer = window.setTimeout', $view);
        $this->assertStringContainsString('}, 12000);', $view);
        $this->assertStringContainsString("requestRefreshButton?.addEventListener('click', () => window.location.reload())", $view);
    }
}
