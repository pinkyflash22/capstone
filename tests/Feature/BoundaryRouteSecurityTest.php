<?php

namespace Tests\Feature;

use App\Http\Controllers\BoundaryLocationController;
use App\Models\Role;
use App\Models\User;
use App\Services\BoundaryManagementScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BoundaryRouteSecurityTest extends TestCase
{
    public function test_boundary_mutations_require_an_authenticated_management_role(): void
    {
        foreach (['POST', 'PUT', 'DELETE'] as $method) {
            $uri = $method === 'POST' ? '/api/shapes' : '/api/shapes/123';
            $route = Route::getRoutes()->match(Request::create($uri, $method));
            $middleware = $route->gatherMiddleware();

            $this->assertContains('web', $middleware);
            $this->assertContains('auth', $middleware);
            $this->assertContains('role:station admin,chief', $middleware);
        }
    }

    public function test_boundary_map_feeds_remain_readable_without_management_middleware(): void
    {
        foreach (['/api/shapes', '/api/shapes/latest'] as $uri) {
            $route = Route::getRoutes()->match(Request::create($uri, 'GET'));
            $middleware = $route->gatherMiddleware();

            $this->assertNotContains('auth', $middleware);
            $this->assertNotContains('role:station admin,chief', $middleware);
        }
    }

    public function test_controller_rejects_a_non_management_role_as_defense_in_depth(): void
    {
        $user = new User(['first_name' => 'Test']);
        $user->setRelation('role', new Role(['role' => 'citizen']));
        Auth::shouldReceive('user')->once()->andReturn($user);

        $this->expectException(AuthorizationException::class);
        app(BoundaryLocationController::class)->store(
            Request::create('/api/shapes', 'POST'),
            app(BoundaryManagementScope::class),
        );
    }
}
