<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BarangayChecklistAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['ai_action_plan_checklist_activities', 'ai_action_plan_checklist_items', 'ai_action_plan_checklists', 'barangay_halls', 'barangay_officers', 'markers', 'full_addresses', 'users', 'roles', 'barangays', 'cities'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('cities', function (Blueprint $table) { $table->id('city_id'); $table->string('city_name'); $table->timestamps(); });
        Schema::create('barangays', function (Blueprint $table) { $table->id('barangay_id'); $table->string('barangay_name'); $table->unsignedBigInteger('city_id')->nullable(); $table->timestamps(); });
        Schema::create('full_addresses', function (Blueprint $table) { $table->id('address_id'); $table->string('street')->nullable(); $table->unsignedBigInteger('barangay_id')->nullable(); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->string('first_name'); $table->string('middle_name')->nullable(); $table->string('last_name'); $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('address_id')->nullable(); $table->string('email')->unique(); $table->string('password'); $table->boolean('is_verified')->default(true); $table->rememberToken(); $table->timestamps(); });
        Schema::create('markers', function (Blueprint $table) { $table->id('marker_id'); $table->timestamps(); });
        Schema::create('barangay_officers', function (Blueprint $table) { $table->id('barangay_officer_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('user_id'); $table->string('position'); $table->string('status')->default('active'); $table->timestamps(); });
        Schema::create('barangay_halls', function (Blueprint $table) { $table->id('barangay_hall_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('marker_id')->nullable(); $table->unsignedBigInteger('captain_user_id')->nullable(); $table->string('hall_name'); $table->timestamps(); });
        Schema::create('ai_action_plan_checklists', function (Blueprint $table) { $table->id('ai_action_plan_checklist_id'); $table->unsignedBigInteger('barangay_id'); $table->string('status'); $table->boolean('is_superseded')->default(false); $table->timestamps(); });
        Schema::create('ai_action_plan_checklist_items', function (Blueprint $table) { $table->id('ai_action_plan_checklist_item_id'); $table->unsignedBigInteger('ai_action_plan_checklist_id'); $table->unsignedInteger('item_order'); $table->text('item_text'); $table->string('status')->default('pending'); $table->unsignedBigInteger('reviewed_by')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->text('review_notes')->nullable(); $table->timestamps(); });

        DB::table('roles')->insert(['role_id' => 1, 'role' => 'barangay officer']);
        DB::table('cities')->insert(['city_id' => 1, 'city_name' => 'Test City']);
        DB::table('barangays')->insert([
            ['barangay_id' => 10, 'barangay_name' => 'Alpha', 'city_id' => 1],
            ['barangay_id' => 20, 'barangay_name' => 'Beta', 'city_id' => 1],
        ]);
        DB::table('users')->insert(['user_id' => 1, 'first_name' => 'Barangay', 'last_name' => 'Officer', 'role_id' => 1, 'email' => 'officer@example.test', 'password' => bcrypt('Password1!')]);
        DB::table('barangay_officers')->insert(['barangay_officer_id' => 1, 'barangay_id' => 10, 'user_id' => 1, 'position' => 'officer']);
        DB::table('ai_action_plan_checklists')->insert([
            ['ai_action_plan_checklist_id' => 100, 'barangay_id' => 10, 'status' => 'confirmed'],
            ['ai_action_plan_checklist_id' => 200, 'barangay_id' => 20, 'status' => 'confirmed'],
        ]);
        DB::table('ai_action_plan_checklist_items')->insert([
            ['ai_action_plan_checklist_item_id' => 101, 'ai_action_plan_checklist_id' => 100, 'item_order' => 1, 'item_text' => 'Own step'],
            ['ai_action_plan_checklist_item_id' => 201, 'ai_action_plan_checklist_id' => 200, 'item_order' => 1, 'item_text' => 'Other step'],
        ]);
    }

    public function test_officer_can_update_an_item_assigned_to_their_barangay(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patch('/barangay/checklists/100/items/101', ['status' => 'followed', 'review_notes' => 'Completed on site.'])
            ->assertRedirect(route('barangay.checklists'));

        $this->assertDatabaseHas('ai_action_plan_checklist_items', ['ai_action_plan_checklist_item_id' => 101, 'status' => 'followed', 'reviewed_by' => 1]);
        $this->assertDatabaseHas('ai_action_plan_checklists', ['ai_action_plan_checklist_id' => 100, 'status' => 'submitted']);
    }

    public function test_officer_cannot_update_another_barangays_checklist(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->patch('/barangay/checklists/200/items/201', ['status' => 'followed'])
            ->assertNotFound();

        $this->assertDatabaseHas('ai_action_plan_checklist_items', ['ai_action_plan_checklist_item_id' => 201, 'status' => 'pending']);
    }
}
