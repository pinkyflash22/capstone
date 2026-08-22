<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RouteVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['routes', 'incidents', 'teams', 'station_officers', 'barangay_officers', 'stations', 'users', 'roles'] as $table) Schema::dropIfExists($table);
        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->unsignedBigInteger('role_id'); $table->string('first_name'); $table->string('last_name'); $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->string('status')->default('available'); $table->timestamps(); });
        Schema::create('barangay_officers', function (Blueprint $table) { $table->id('barangay_officer_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('user_id'); $table->timestamps(); });
        Schema::create('teams', function (Blueprint $table) { $table->id('team_id'); $table->unsignedBigInteger('station_id')->nullable(); foreach (['commander_driver','pumper','team_leader','firefighter_officer_1','firefighter_officer_2','firefighter_officer_3','rescue_radio'] as $column) $table->unsignedBigInteger($column)->nullable(); $table->timestamps(); });
        Schema::create('incidents', function (Blueprint $table) { $table->id('incident_id'); $table->unsignedBigInteger('station_id')->nullable(); $table->unsignedBigInteger('team_id')->nullable(); $table->unsignedBigInteger('reported_by'); $table->unsignedBigInteger('barangay_id')->nullable(); $table->string('status'); $table->timestamps(); });
        Schema::create('routes', function (Blueprint $table) { $table->id('route_id'); $table->unsignedBigInteger('incident_id'); $table->json('coordinates'); $table->decimal('total_distance', 10, 2); $table->decimal('estimated_time', 10, 2); $table->string('status'); $table->string('route_source')->default('real'); $table->string('destination_title')->nullable(); $table->decimal('destination_lat', 10, 8)->nullable(); $table->decimal('destination_lng', 11, 8)->nullable(); $table->decimal('origin_lat', 10, 8)->nullable(); $table->decimal('origin_lng', 11, 8)->nullable(); $table->integer('current_position_index')->default(0); $table->decimal('current_lat', 10, 8)->nullable(); $table->decimal('current_lng', 11, 8)->nullable(); $table->decimal('progress_percentage', 5, 2)->default(0); $table->timestamp('last_updated_at')->nullable(); $table->timestamps(); });

        DB::table('roles')->insert([['role_id' => 1, 'role' => 'citizen'], ['role_id' => 2, 'role' => 'firefighter'], ['role_id' => 3, 'role' => 'chief'], ['role_id' => 4, 'role' => 'barangay officer']]);
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'first_name' => 'First', 'last_name' => 'Reporter'],
            ['user_id' => 2, 'role_id' => 1, 'first_name' => 'Second', 'last_name' => 'Reporter'],
            ['user_id' => 3, 'role_id' => 2, 'first_name' => 'Station', 'last_name' => 'Firefighter'],
            ['user_id' => 4, 'role_id' => 3, 'first_name' => 'City', 'last_name' => 'Chief'],
            ['user_id' => 5, 'role_id' => 4, 'first_name' => 'Local', 'last_name' => 'Officer'],
        ]);
        DB::table('stations')->insert([['station_id' => 10, 'station_name' => 'Station 10'], ['station_id' => 20, 'station_name' => 'Station 20']]);
        DB::table('station_officers')->insert(['station_officer_id' => 3, 'station_id' => 10, 'user_id' => 3]);
        DB::table('barangay_officers')->insert(['barangay_officer_id' => 5, 'barangay_id' => 101, 'user_id' => 5]);
        DB::table('incidents')->insert([
            ['incident_id' => 100, 'station_id' => 10, 'reported_by' => 1, 'barangay_id' => 101, 'status' => 'Responding'],
            ['incident_id' => 200, 'station_id' => 20, 'reported_by' => 2, 'barangay_id' => 202, 'status' => 'Responding'],
        ]);
        foreach ([100, 200] as $id) DB::table('routes')->insert(['route_id' => $id, 'incident_id' => $id, 'coordinates' => json_encode([[120.0, 14.0], [120.1, 14.1]]), 'total_distance' => 1000, 'estimated_time' => 120, 'status' => 'moving', 'route_source' => $id === 100 ? 'demo' : 'real']);
        Role::flushDefinitionCache();
    }

    public function test_reporter_sees_only_the_route_for_their_report(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->getJson('/api/route/active')->assertOk();
        $this->assertSame([100], collect($response->json())->pluck('incident_id')->all());
    }

    public function test_firefighter_sees_their_station_routes_and_chief_sees_all_routes(): void
    {
        $stationResponse = $this->actingAs(User::findOrFail(3))->getJson('/api/route/active')->assertOk();
        $this->assertSame([100], collect($stationResponse->json())->pluck('incident_id')->all());

        $chiefResponse = $this->actingAs(User::findOrFail(4))->getJson('/api/route/active')->assertOk();
        $this->assertEqualsCanonicalizing([100, 200], collect($chiefResponse->json())->pluck('incident_id')->all());
    }

    public function test_barangay_officer_sees_every_route_in_their_barangay_only(): void
    {
        $response = $this->actingAs(User::findOrFail(5))->getJson('/api/route/active')->assertOk();
        $this->assertSame([100], collect($response->json())->pluck('incident_id')->all());
    }

    public function test_firefighter_clear_only_stops_routes_visible_to_their_station(): void
    {
        $this->actingAs(User::findOrFail(3))->postJson('/api/route/clear-active')->assertOk()->assertJson(['count' => 1]);

        $this->assertSame('inactive', DB::table('routes')->where('route_id', 100)->value('status'));
        $this->assertSame('moving', DB::table('routes')->where('route_id', 200)->value('status'));
    }
}
