<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProvinceSecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user_locations', 'station_officers', 'stations', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->unsignedBigInteger('role_id'); $table->string('first_name'); $table->string('last_name'); $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->string('rank')->nullable(); $table->string('status')->nullable(); $table->timestamps(); });
        Schema::create('user_locations', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->decimal('latitude', 10, 7); $table->decimal('longitude', 10, 7); $table->timestamps(); });

        DB::table('roles')->insert([
            ['role_id' => 1, 'role' => 'provincial admin'],
            ['role_id' => 2, 'role' => 'firefighter'],
            ['role_id' => 3, 'role' => 'chief'],
            ['role_id' => 4, 'role' => 'citizen'],
        ]);
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'first_name' => 'Province', 'last_name' => 'Admin'],
            ['user_id' => 10, 'role_id' => 2, 'first_name' => 'Station', 'last_name' => 'One'],
            ['user_id' => 20, 'role_id' => 2, 'first_name' => 'Station', 'last_name' => 'Two'],
            ['user_id' => 30, 'role_id' => 3, 'first_name' => 'Chief', 'last_name' => 'One'],
            ['user_id' => 40, 'role_id' => 4, 'first_name' => 'Public', 'last_name' => 'User'],
        ]);
        DB::table('stations')->insert([
            ['station_id' => 100, 'station_name' => 'Station One'],
            ['station_id' => 200, 'station_name' => 'Station Two'],
        ]);
        DB::table('station_officers')->insert([
            ['station_officer_id' => 10, 'station_id' => 100, 'user_id' => 10],
            ['station_officer_id' => 20, 'station_id' => 200, 'user_id' => 20],
            ['station_officer_id' => 30, 'station_id' => 100, 'user_id' => 30],
        ]);
        DB::table('user_locations')->insert([
            ['user_id' => 10, 'latitude' => 14.1000000, 'longitude' => 121.1000000, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 20, 'latitude' => 14.2000000, 'longitude' => 121.2000000, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Cache::flush();
        Role::flushDefinitionCache();
    }

    public function test_sensitive_location_and_multimedia_routes_reject_anonymous_users(): void
    {
        $this->get('/api/firefighter/locations')->assertRedirect(route('auth.login'));
        $this->get('/multimedia')->assertRedirect(route('auth.login'));
        $this->post('/multimedia/store')->assertRedirect(route('auth.login'));
    }

    public function test_station_users_only_receive_their_own_firefighter_locations(): void
    {
        $this->actingAs(User::findOrFail(10))
            ->getJson('/api/firefighter/locations')
            ->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.user_id', 10)
            ->assertJsonPath('locations.0.station_id', 100);

        $this->actingAs(User::findOrFail(30))
            ->getJson('/api/firefighter/locations')
            ->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.user_id', 10);
    }

    public function test_provincial_admin_can_receive_all_station_locations(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->getJson('/api/firefighter/locations')
            ->assertOk()
            ->assertJsonCount(2, 'locations');
    }

    public function test_non_operational_role_cannot_access_sensitive_feeds_or_uploads(): void
    {
        $citizen = User::findOrFail(40);
        $this->actingAs($citizen)->getJson('/api/firefighter/locations')->assertForbidden();
        $this->actingAs($citizen)->getJson('/multimedia')->assertForbidden();
    }

    public function test_multimedia_type_must_match_the_uploaded_file(): void
    {
        $this->actingAs(User::findOrFail(10))
            ->postJson('/multimedia/store', [
                'type' => 'report',
                'path' => UploadedFile::fake()->image('incident-photo.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('path');
    }

    public function test_diagnostic_routes_are_unavailable_outside_local_environment(): void
    {
        $admin = User::findOrFail(1);
        $this->actingAs($admin)->get('/test-api-key')->assertNotFound();
        $this->actingAs($admin)->get('/debug-incidents')->assertNotFound();
        $this->actingAs($admin)->get('/test-db')->assertNotFound();
    }

    public function test_authentication_and_otp_routes_are_rate_limited(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertContains('throttle:10,1', $routes->getByName('auth.authenticate')->gatherMiddleware());
        $this->assertContains('throttle:5,1', $routes->getByName('register.store')->gatherMiddleware());
        $this->assertContains('throttle:5,1', $routes->getByName('register.send-otp')->gatherMiddleware());
        $this->assertContains('throttle:10,1', $routes->getByName('register.verify-otp')->gatherMiddleware());
        $this->assertContains('throttle:3,5', $routes->getByName('register.resend-otp')->gatherMiddleware());
        $this->assertContains('throttle:3,5', $routes->getByName('password.email')->gatherMiddleware());
    }

    public function test_high_risk_api_mutations_require_authentication_and_roles(): void
    {
        $routes = app('router')->getRoutes();
        $protectedUris = [
            'api/spot-investigation',
            'api/incidents/{incidentId}/false-alarm',
            'api/marker-categories',
            'api/station',
            'api/hydrant',
            'api/connection',
        ];

        foreach ($protectedUris as $uri) {
            $route = collect($routes->getRoutes())->first(fn ($candidate) => $candidate->uri() === $uri);
            $this->assertNotNull($route, "Expected protected route [{$uri}] to exist.");
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, "Route [{$uri}] must require authentication.");
            $this->assertTrue(
                collect($middleware)->contains(fn ($item) => str_starts_with($item, 'role:')),
                "Route [{$uri}] must enforce a role boundary."
            );
        }
    }

    public function test_chief_cannot_update_another_stations_officer(): void
    {
        $this->actingAs(User::findOrFail(30))
            ->patchJson('/station-admin/station-officers/20/status', ['status' => 'busy'])
            ->assertForbidden();

        $this->assertNull(DB::table('station_officers')->where('station_officer_id', 20)->value('status'));

        $this->actingAs(User::findOrFail(30))
            ->patchJson('/station-admin/station-officers/10/status', ['status' => 'busy'])
            ->assertOk();

        $this->assertSame('busy', DB::table('station_officers')->where('station_officer_id', 10)->value('status'));
    }

    public function test_provincial_admin_can_update_an_officer_across_stations(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/admin/station-officers/20/status', ['status' => 'training'])
            ->assertOk();

        $this->assertSame('training', DB::table('station_officers')->where('station_officer_id', 20)->value('status'));
    }

    public function test_pilot_mode_allows_only_selected_station_staff_and_provincial_command(): void
    {
        config([
            'operations.rollout.mode' => 'pilot',
            'operations.rollout.pilot_station_ids' => [100],
        ]);

        $this->actingAs(User::findOrFail(10))->getJson('/api/firefighter/locations')->assertOk();
        $this->actingAs(User::findOrFail(20))->getJson('/api/firefighter/locations')->assertStatus(503);
        $this->actingAs(User::findOrFail(1))->getJson('/api/firefighter/locations')->assertOk();
    }
}
