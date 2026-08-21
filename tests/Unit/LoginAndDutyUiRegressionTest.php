<?php

namespace Tests\Unit;

use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginAndDutyUiRegressionTest extends TestCase
{
    public function test_anonymous_emergency_session_can_open_the_login_page(): void
    {
        $request = Request::create('/login', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $anonymous = (object) ['role' => (object) ['role' => 'anonymous']];
        app('view')->share('errors', new ViewErrorBag());

        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($anonymous);
        Auth::shouldReceive('logout')->once();

        $response = app(LoginController::class)->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Login', $response->getContent());
    }

    public function test_duty_form_disables_people_selected_in_another_position(): void
    {
        $script = file_get_contents(resource_path('js/duty-roster.js'));

        $this->assertStringContainsString('syncAssignmentAvailability', $script);
        $this->assertStringContainsString('option.disabled', $script);
        $this->assertStringContainsString('is already assigned to another duty position', $script);
    }
}
