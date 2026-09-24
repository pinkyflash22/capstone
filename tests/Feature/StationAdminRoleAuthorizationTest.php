<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StationAdminRoleAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Cache::flush();
        Role::flushDefinitionCache();
    }

    public function test_stationadmin_role_matches_canonical_stationadmin_middleware(): void
    {
        $user = $this->createUserWithRole('stationadmin');

        Auth::login($user);

        $response = app(RoleMiddleware::class)->handle(
            Request::create('/station-admin/dashboard', 'GET'),
            fn () => response('ok'),
            'stationadmin',
            'chief'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_station_admin_routes_use_the_canonical_role_name(): void
    {
        $middleware = app('router')->getRoutes()->getByName('station-admin.dashboard')->gatherMiddleware();

        $this->assertContains('role:stationadmin,chief', $middleware);
    }

    private function createUserWithRole(string $role): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'role_id');

        $userId = DB::table('users')->insertGetId([
            'role_id' => $roleId,
            'first_name' => 'Station',
            'last_name' => 'Admin',
            'email' => null,
            'password' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        Role::flushDefinitionCache();

        return User::findOrFail($userId);
    }
}
