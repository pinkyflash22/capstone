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

        foreach (['notifications', 'mitigation_action_activities', 'mitigation_actions', 'checklist_step_visit_photos', 'checklist_step_visits', 'ai_action_plan_checklist_items', 'ai_action_plan_checklists', 'barangay_halls', 'barangay_officers', 'station_barangay_coverage', 'station_officers', 'stations', 'full_addresses', 'users', 'roles', 'barangays', 'cities'] as $table) {
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
        Schema::create('ai_action_plan_checklists', function (Blueprint $table) { $table->id('ai_action_plan_checklist_id'); $table->unsignedBigInteger('station_id')->nullable(); $table->unsignedBigInteger('barangay_id')->nullable(); $table->string('plan_title')->nullable(); $table->string('status'); $table->boolean('is_superseded')->default(false); $table->timestamps(); });
        Schema::create('ai_action_plan_checklist_items', function (Blueprint $table) { $table->id('ai_action_plan_checklist_item_id'); $table->unsignedBigInteger('ai_action_plan_checklist_id'); $table->unsignedInteger('item_order'); $table->text('item_text'); $table->string('status')->default('pending'); $table->unsignedBigInteger('reviewed_by')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->text('review_notes')->nullable(); $table->timestamps(); });
        Schema::create('mitigation_actions', function (Blueprint $table) { $table->id('mitigation_action_id'); $table->unsignedBigInteger('barangay_id')->nullable(); $table->string('scope_type')->default('barangay'); $table->unsignedBigInteger('scope_city_id')->nullable(); $table->unsignedBigInteger('owner_station_id')->nullable(); $table->unsignedBigInteger('ai_action_plan_checklist_id')->nullable(); $table->string('title'); $table->string('priority_level')->default('medium'); $table->string('status')->default('planned'); $table->text('implementation_notes')->nullable(); $table->text('completion_notes')->nullable(); $table->text('outcome_result')->nullable(); $table->string('effectiveness')->nullable(); $table->boolean('follow_up_required')->default(false); $table->text('follow_up_notes')->nullable(); $table->timestamp('completed_at')->nullable(); $table->string('completion_approval_status')->nullable(); $table->unsignedBigInteger('completion_requested_by')->nullable(); $table->timestamp('completion_requested_at')->nullable(); $table->unsignedBigInteger('completion_decided_by')->nullable(); $table->timestamp('completion_decided_at')->nullable(); $table->text('completion_decision_notes')->nullable(); $table->unsignedBigInteger('reviewed_by')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->boolean('is_manually_edited')->default(false); $table->boolean('is_superseded')->default(false); $table->timestamps(); });
        Schema::create('mitigation_action_activities', function (Blueprint $table) { $table->id('mitigation_action_activity_id'); $table->unsignedBigInteger('mitigation_action_id'); $table->unsignedBigInteger('actor_user_id')->nullable(); $table->string('event_type'); $table->string('old_status')->nullable(); $table->string('new_status')->nullable(); $table->text('notes')->nullable(); $table->json('metadata')->nullable(); $table->timestamps(); });
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
            ['ai_action_plan_checklist_id' => 100, 'station_id' => 5, 'barangay_id' => 10, 'plan_title' => 'Covered Plan', 'status' => 'confirmed'],
            ['ai_action_plan_checklist_id' => 200, 'station_id' => 9, 'barangay_id' => 20, 'plan_title' => 'Outside Plan', 'status' => 'confirmed'],
            ['ai_action_plan_checklist_id' => 300, 'station_id' => 5, 'barangay_id' => null, 'plan_title' => 'Stationwide Plan', 'status' => 'confirmed'],
        ]);
        DB::table('ai_action_plan_checklist_items')->insert([
            ['ai_action_plan_checklist_item_id' => 101, 'ai_action_plan_checklist_id' => 100, 'item_order' => 1, 'item_text' => 'Verify covered hydrant access'],
            ['ai_action_plan_checklist_item_id' => 201, 'ai_action_plan_checklist_id' => 200, 'item_order' => 1, 'item_text' => 'Outside station step'],
        ]);
        DB::table('mitigation_actions')->insert([
            ['mitigation_action_id' => 301, 'barangay_id' => 10, 'scope_type' => 'barangay', 'scope_city_id' => 1, 'owner_station_id' => 5, 'ai_action_plan_checklist_id' => 100, 'title' => 'Covered hydrant readiness', 'status' => 'planned'],
            ['mitigation_action_id' => 302, 'barangay_id' => 20, 'scope_type' => 'barangay', 'scope_city_id' => 1, 'owner_station_id' => 9, 'ai_action_plan_checklist_id' => 200, 'title' => 'Outside action', 'status' => 'planned'],
            ['mitigation_action_id' => 303, 'barangay_id' => 20, 'scope_type' => 'stationwide', 'scope_city_id' => 1, 'owner_station_id' => 5, 'ai_action_plan_checklist_id' => 300, 'title' => 'Stationwide readiness action', 'status' => 'approved'],
            ['mitigation_action_id' => 304, 'barangay_id' => 20, 'scope_type' => 'citywide', 'scope_city_id' => 1, 'owner_station_id' => 9, 'ai_action_plan_checklist_id' => 200, 'title' => 'Shared city readiness action', 'status' => 'planned'],
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

    public function test_firefighter_can_update_operational_progress_for_a_covered_mitigation(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/firefighter/barangay-cooperation/mitigations/301', [
                'status' => 'completed',
                'implementation_notes' => 'Hydrant access was cleared and verified.',
                'completion_notes' => 'Barangay and BFP completed the walkthrough.',
                'outcome_result' => 'Access is now unobstructed.',
                'effectiveness' => 'effective',
                'follow_up_required' => false,
                'title' => 'Attempted command-plan change',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.display_status', 'pending_approval');

        $this->assertDatabaseHas('mitigation_actions', [
            'mitigation_action_id' => 301,
            'title' => 'Covered hydrant readiness',
            'status' => 'in_progress',
            'completion_approval_status' => 'pending',
            'reviewed_by' => 1,
            'is_manually_edited' => true,
        ]);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('mitigation_action_activities', ['mitigation_action_id' => 301, 'event_type' => 'completion_requested']);
    }

    public function test_firefighter_cannot_update_a_mitigation_outside_station_scope(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/firefighter/barangay-cooperation/mitigations/302', [
                'status' => 'in_progress',
                'implementation_notes' => 'Should not be accepted.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('mitigation_actions', ['mitigation_action_id' => 302, 'status' => 'planned']);
    }

    public function test_firefighter_can_update_an_explicit_stationwide_plan(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/firefighter/barangay-cooperation/mitigations/303', [
                'status' => 'blocked',
                'implementation_notes' => 'Waiting for city engineering clearance.',
                'follow_up_required' => true,
                'follow_up_notes' => 'Coordinate a joint inspection next week.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked');

        $this->assertDatabaseHas('mitigation_actions', [
            'mitigation_action_id' => 303,
            'status' => 'blocked',
            'reviewed_by' => 1,
        ]);
    }

    public function test_firefighter_can_update_a_citywide_plan_shared_with_their_station_city(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patchJson('/firefighter/barangay-cooperation/mitigations/304', [
                'status' => 'in_progress',
                'implementation_notes' => 'Station completed its part of the shared city inspection.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('mitigation_actions', ['mitigation_action_id' => 304, 'status' => 'in_progress', 'reviewed_by' => 1]);
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
