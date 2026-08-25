<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffAnnouncementUiConsistencyTest extends TestCase
{
    #[Test]
    public function chief_and_firefighter_announcements_use_the_shared_responsive_workspace(): void
    {
        $chief = file_get_contents(resource_path('views/chief/announcements.blade.php'));
        $firefighter = file_get_contents(resource_path('views/firefighters/announcements.blade.php'));

        foreach ([$chief, $firefighter] as $view) {
            $this->assertStringNotContainsString("resources/css/staff/announcements.css", $view);
            $this->assertStringNotContainsString("resources/css/admin/announcements.css", $view);
            $this->assertStringNotContainsString('<style', $view);
            $this->assertStringContainsString('class="', $view);
            $this->assertStringContainsString('staff-announcements', $view);
            $this->assertStringContainsString('staff-announcements__workspace', $view);
            $this->assertStringContainsString('staff-announcements__heading', $view);
            $this->assertStringContainsString('staff-announcements__refresh', $view);
            $this->assertStringContainsString('composer-header-main', $view);
        }
    }

    #[Test]
    public function shared_staff_announcement_theme_has_mobile_safe_cards_and_sheet_modals(): void
    {
        $css = file_get_contents(resource_path('css/staff/announcements.css'));
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('border-radius: 20px 20px 0 0', $css);
        $this->assertStringContainsString('.staff-announcements .post-card', $css);
        $this->assertStringContainsString('.staff-announcements .media-drawer', $css);
        $this->assertStringContainsString('.staff-announcements .announcement-confirm', $css);
        $this->assertStringContainsString('z-index: 12000', $css);
        $this->assertStringContainsString('display: flex !important', $css);
        $this->assertStringNotContainsString("'resources/css/staff/announcements.css'", $vite);

        foreach (['chief', 'firefighter'] as $role) {
            $script = file_get_contents(resource_path("js/{$role}/announcements.js"));
            $this->assertStringContainsString("import '../../css/staff/announcements.css';", $script);
        }
    }

    #[Test]
    public function both_staff_feeds_render_profile_images_with_initials_fallbacks(): void
    {
        foreach (['chief', 'firefighter'] as $role) {
            $script = file_get_contents(resource_path("js/{$role}/announcements.js"));

            $this->assertStringContainsString('renderUserAvatar(user, className)', $script);
            $this->assertStringContainsString("user?.profile_image_url || ''", $script);
            $this->assertStringContainsString("this.renderUserAvatar(post.user, 'post-author-avatar')", $script);
            $this->assertStringContainsString("this.renderUserAvatar(comment.user, 'comment-avatar')", $script);
        }
    }
}
