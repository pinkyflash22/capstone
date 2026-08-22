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

    public function test_login_refreshes_its_csrf_token_before_submitting(): void
    {
        $response = $this->getJson('/auth/session-refresh')
            ->assertOk()
            ->assertJsonStructure(['csrf_token']);

        $this->assertNotEmpty($response->json('csrf_token'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $view = file_get_contents(resource_path('views/auth/login.blade.php'));
        $this->assertStringContainsString("route('auth.session-refresh')", $view);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(this)', $view);
    }

    public function test_expired_login_sessions_are_not_rendered_as_bad_requests(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('if ($status === 419)', $bootstrap);
        $this->assertStringContainsString("->route('auth.login')", $bootstrap);
    }
}
