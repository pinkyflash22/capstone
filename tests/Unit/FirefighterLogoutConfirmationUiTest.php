<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirefighterLogoutConfirmationUiTest extends TestCase
{
    #[Test]
    public function firefighter_logout_forms_use_a_persistent_confirmation_dialog(): void
    {
        $sidebar = file_get_contents(resource_path('views/firefighters/partials/shell-sidebar.blade.php'));
        $topbar = file_get_contents(resource_path('views/firefighters/partials/shell-topbar.blade.php'));
        $dashboard = file_get_contents(resource_path('views/firefighters/dashboard.blade.php'));
        $confirmation = file_get_contents(resource_path('views/firefighters/partials/logout-confirmation.blade.php'));

        $this->assertStringContainsString("@include('firefighters.partials.logout-confirmation')", $sidebar);
        $this->assertStringContainsString('data-firefighter-logout-form', $sidebar);
        $this->assertStringContainsString('data-firefighter-logout-form', $topbar);
        $this->assertStringContainsString('data-firefighter-logout-form', $dashboard);
        $this->assertStringContainsString("document.addEventListener('submit'", $confirmation);
        $this->assertStringContainsString("form?.matches('[data-firefighter-logout-form]')", $confirmation);
        $this->assertStringContainsString('event.preventDefault();', $confirmation);
        $this->assertStringContainsString('Are you sure you want to log out?', $confirmation);
        $this->assertStringContainsString('Yes, log out', $confirmation);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form);', $confirmation);
    }

    #[Test]
    public function alternate_firefighter_shells_also_require_logout_confirmation(): void
    {
        $v2Layout = file_get_contents(resource_path('views/firefighters/v2-layout.blade.php'));
        $v2Topbar = file_get_contents(resource_path('views/firefighters/partials/v2-topbar.blade.php'));
        $v3Shell = file_get_contents(resource_path('views/firefighters/v3-shell.blade.php'));

        $this->assertStringContainsString("@include('firefighters.partials.logout-confirmation')", $v2Layout);
        $this->assertStringContainsString('data-firefighter-logout-form', $v2Topbar);
        $this->assertStringContainsString("@include('firefighters.partials.logout-confirmation')", $v3Shell);
        $this->assertStringContainsString('data-firefighter-logout-form', $v3Shell);
    }
}
