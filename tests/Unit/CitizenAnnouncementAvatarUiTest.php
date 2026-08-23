<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitizenAnnouncementAvatarUiTest extends TestCase
{
    #[Test]
    public function citizen_posts_use_the_staff_profile_picture_with_an_initials_fallback(): void
    {
        $view = file_get_contents(resource_path('views/citizen/sections/announcements.blade.php'));

        $this->assertStringContainsString('user.profile_thumbnail_url || user.profile_image_url', $view);
        $this->assertStringContainsString('function renderPosterAvatar', $view);
        $this->assertStringContainsString('onerror="this.remove()"', $view);
        $this->assertStringContainsString('${renderPosterAvatar(post.user)}', $view);
        $this->assertStringContainsString("renderPosterAvatar(post.user, 'citizen-media-drawer-avatar')", $view);
        $this->assertStringContainsString('${renderCommenterAvatar(comment.user)}', $view);
        $this->assertStringContainsString("renderCommenterAvatar(comment.user, 'citizen-media-drawer-comment-avatar')", $view);
        $this->assertStringContainsString('object-fit: cover', $view);
    }
}
