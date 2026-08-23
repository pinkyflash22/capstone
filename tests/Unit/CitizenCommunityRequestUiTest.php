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
}
