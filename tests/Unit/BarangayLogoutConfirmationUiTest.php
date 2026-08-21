<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarangayLogoutConfirmationUiTest extends TestCase
{
    #[Test]
    public function barangay_desktop_and_mobile_logout_forms_require_confirmation(): void
    {
        $layout = file_get_contents(resource_path('views/barangay/layouts/app.blade.php'));
        $sidebar = file_get_contents(resource_path('views/barangay/partials/sidebar.blade.php'));
        $barangayConfirmation = file_get_contents(resource_path('views/barangay/partials/logout-confirmation.blade.php'));
        $confirmation = file_get_contents(resource_path('views/chief/partials/logout-confirmation.blade.php'));

        $this->assertStringContainsString("@include('barangay.partials.logout-confirmation')", $layout);
        $this->assertStringContainsString("@include('chief.partials.logout-confirmation')", $barangayConfirmation);
        $this->assertStringContainsString("action=\"{{ route('logout') }}\"", $layout);
        $this->assertStringContainsString("action=\"{{ route('logout') }}\"", $sidebar);
        $this->assertStringContainsString("document.querySelectorAll('form[action=\"{{ route('logout') }}\"]')", $confirmation);
        $this->assertStringContainsString('Are you sure you want to log out?', $confirmation);
        $this->assertStringContainsString('Yes, log out', $confirmation);
        $this->assertStringContainsString("event.preventDefault();", $confirmation);
        $this->assertStringContainsString("pendingLogoutForm.submit();", $confirmation);
    }
}
