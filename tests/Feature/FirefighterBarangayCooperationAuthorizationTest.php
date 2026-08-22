<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FirefighterBarangayCooperationAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['notifications', 'checklist_step_visit_photos', 'checklist_step_visits', 'ai_action_plan_checklist_items', 'ai_action_plan_checklists', 'barangay_halls', 'barangay_officers', 'station_barangay_coverage', 'station_officers', 'stations', 'full_addresses', 'users', 'roles', 'barangays', 'cities'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('cities', function (Blueprint $table) { $table->id('city_id'); $table->string('city_name'); $table->timestamps(); });
        Schema::create('barangays', function (Blueprint $table) { $table->id('barangay_id'); $table->string('barangay_name'); $table->unsignedBigInteger('city_id')->nullable(); $table->timestamps(); });
        Schema::create('full_addresses', function (Blueprint $table) { $table->id('address_id'); $table->unsignedBigInteger('barangay_id')->nullable(); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->string('first_name'); $table->string('middle_name')->nullable(); $table->string('last_name'); $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('address_id')->nullable(); $table->string('email')->unique(); $table->string('password'); $table->boolean('is_verified')->default(true); $table->rememberToken(); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->string('station_code')->nullable(); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->string('rank')->nullable(); $table->string('status')->default('available'); $table->timestamps(); });
        Schema::create('station_barangay_coverage', function (Blueprint $table) { $table->id('station_barangay_coverage_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('barangay_id'); $table->string('coverage_type')->default('primary'); $table->string('source')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('barangay_officers', function (Blueprint $table) { $table->id('barangay_officer_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('user_id'); $table->string('position'); $table->string('status')->default('active'); $table->timestamps(); });
        Schema::create('barangay_halls', function (Blueprint $table) { $table->id('barangay_hall_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('captain_user_id')->nullable(); $table->string('hall_name')->nullable(); $table->timestamps(); });
        Schema::create('ai_action_plan_checklists', function (Blueprint $table) { $table->id('ai_action_plan_checklist_id'); $table->unsignedBigInteger('barangay_id'); $table->string('status'); $table->boolean('is_superseded')->default(false); $table->timestamps(); });
        Schema::create('ai_action_plan_checklist_items', function (Blueprint $table) { $table->id('ai_action_plan_checklist_item_id'); $table->unsignedBigInteger('ai_action_plan_checklist_id'); $table->unsignedInteger('item_order'); $table->text('item_text'); $table->string('status')->default('pending'); $table->unsignedBigInteger('reviewed_by')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->text('review_notes')->nullable(); $table->timestamps(); });
        Schema::create('checklist_step_visits', function (Blueprint $table) { $table->id('checklist_step_visit_id'); $table->unsignedBigInteger('ai_action_plan_checklist_id'); $table->unsignedBigInteger('ai_action_plan_checklist_item_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('firefighter_user_id'); $table->string('verified_status'); $table->text('visit_notes')->nullable(); $table->timestamp('visited_at'); $table->string('signoff_status')->default('pending'); $table->unsignedBigInteger('barangay_signed_off_by')->nullable(); $table->timestamp('signed_off_at')->nullable(); $table->text('signoff_notes')->nullable(); $table->timestamps(); });
        Schema::create('checklist_step_visit_photos', function (Blueprint $table) { $table->id('checklist_step_visit_photo_id'); $table->unsignedBigInteger('checklist_step_visit_id'); $table->string('file_path'); $table->string('file_name'); $table->string('mime_type'); $table->unsignedBigInteger('file_size')->default(0); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); });
        Schema::create('notifications', function (Blueprint $table) { $table->uuid('id')->primary(); $table->string('type'); $table->string('notifiable_type'); $table->unsignedBigInteger('notifiable_id'); $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps(); });

        DB::table('roles')->insert([['role_id' => 1, 'role' => 'firefighter'], ['role_id' => 2, 'role' => 'barangay officer']]);
        DB::table('cities')->insert(['city_id' => 1, 'city_name' => 'Test City']);
        DB::table('barangays')->insert([
            ['barangay_id' => 10, 'barangay_name' => 'Covered Barangay', 'city_id' => 1],
            ['barangay_id' => 20, 'barangay_name' => 'Outside Barangay', 'city_id' => 1],
        ]);
        DB::table('users')->insert([
            ['user_id' => 1, 'first_name' => 'Field', 'last_name' => 'Responder', 'role_id' => 1, 'email' => 'responder@example.test', 'password' => bcrypt('Password1!')],
            ['user_id' => 2, 'first_name' => 'Barangay', 'last_name' => 'Officer', 'role_id' => 2, 'email' => 'barangay@example.test', 'password' => bcrypt('Password1!')],
        ]);
        DB::table('stations')->insert(['station_id' => 5, 'station_name' => 'Central Fire Station', 'station_code' => 'CFS']);
        DB::table('station_officers')->insert(['station_officer_id' => 7, 'station_id' => 5, 'user_id' => 1, 'rank' => 'Fire Officer 1']);
        DB::table('station_barangay_coverage')->insert(['station_id' => 5, 'barangay_id' => 10, 'coverage_type' => 'primary', 'is_active' => true]);
        DB::table('barangay_officers')->insert(['barangay_officer_id' => 8, 'barangay_id' => 10, 'user_id' => 2, 'position' => 'officer']);
        DB::table('ai_action_plan_checklists')->insert([
            ['ai_action_plan_checklist_id' => 100, 'barangay_id' => 10, 'status' => 'confirmed'],
            ['ai_action_plan_checklist_id' => 200, 'barangay_id' => 20, 'status' => 'confirmed'],
        ]);
        DB::table('ai_action_plan_checklist_items')->insert([
            ['ai_action_plan_checklist_item_id' => 101, 'ai_action_plan_checklist_id' => 100, 'item_order' => 1, 'item_text' => 'Verify covered hydrant access'],
            ['ai_action_plan_checklist_item_id' => 201, 'ai_action_plan_checklist_id' => 200, 'item_order' => 1, 'item_text' => 'Outside station step'],
        ]);

        Role::flushDefinitionCache();
    }

    public function test_firefighter_can_update_a_checklist_for_an_actively_covered_barangay(): void
    {
        Storage::fake('supabase');

        $this->actingAs(User::findOrFail(1))
            ->withHeader('Accept', 'application/json')
            ->patch('/firefighter/barangay-cooperation/checklists/100/items/101', [
                'status' => 'followed',
                'review_notes' => 'Verified with barangay officer during the site visit.',
                'visit_photos' => [UploadedFile::fake()->image('hydrant-proof.jpg')],
            ])
            ->assertOk()
            ->assertJsonPath('data.progress.percent', 100)
            ->assertJsonPath('data.checklist_status', 'submitted');

        $this->assertDatabaseHas('ai_action_plan_checklist_items', [
            'ai_action_plan_checklist_item_id' => 101,
            'status' => 'followed',
            'reviewed_by' => 1,
        ]);
        $this->assertDatabaseHas('checklist_step_visits', [
            'ai_action_plan_checklist_item_id' => 101,
            'station_id' => 5,
            'barangay_id' => 10,
            'firefighter_user_id' => 1,
            'signoff_status' => 'pending',
        ]);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('checklist_step_visit_photos', ['file_name' => 'hydrant-proof.jpg']);

        $visitId = (int) DB::table('checklist_step_visits')->value('checklist_step_visit_id');
        $this->actingAs(User::findOrFail(2))
            ->patchJson("/barangay/checklists/visits/{$visitId}/sign-off", [
                'signoff_status' => 'accepted',
                'signoff_notes' => 'Confirmed during barangay walkthrough.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('checklist_step_visits', [
            'checklist_step_visit_id' => $visitId,
            'signoff_status' => 'accepted',
            'barangay_signed_off_by' => 2,
        ]);
    }

    public function test_firefighter_cannot_update_a_checklist_outside_active_station_coverage(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/firefighter/barangay-cooperation/checklists/200/items/201', ['status' => 'followed'])
            ->assertNotFound();

        $this->assertDatabaseHas('ai_action_plan_checklist_items', [
            'ai_action_plan_checklist_item_id' => 201,
            'status' => 'pending',
        ]);
    }

    public function test_barangay_officer_cannot_sign_off_a_visit_for_another_barangay(): void
    {
        DB::table('checklist_step_visits')->insert([
            'checklist_step_visit_id' => 77,
            'ai_action_plan_checklist_id' => 200,
            'ai_action_plan_checklist_item_id' => 201,
            'station_id' => 5,
            'barangay_id' => 20,
            'firefighter_user_id' => 1,
            'verified_status' => 'followed',
            'visited_at' => now(),
            'signoff_status' => 'pending',
        ]);

        $this->actingAs(User::findOrFail(2))
            ->patchJson('/barangay/checklists/visits/77/sign-off', ['signoff_status' => 'accepted'])
            ->assertNotFound();

        $this->assertDatabaseHas('checklist_step_visits', [
            'checklist_step_visit_id' => 77,
            'signoff_status' => 'pending',
            'barangay_signed_off_by' => null,
        ]);
    }
}
