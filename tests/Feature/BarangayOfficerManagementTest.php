<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BarangayOfficerManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['barangay_halls', 'barangay_officers', 'station_barangay_coverage', 'station_officers', 'full_addresses', 'barangays', 'stations', 'users', 'roles'] as $table) Schema::dropIfExists($table);
        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('barangays', function (Blueprint $table) { $table->id('barangay_id'); $table->string('barangay_name'); $table->unsignedBigInteger('city_id')->nullable(); $table->timestamps(); });
        Schema::create('full_addresses', function (Blueprint $table) { $table->id('address_id'); $table->string('street'); $table->unsignedBigInteger('barangay_id'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->string('account_code')->nullable()->unique(); $table->string('first_name'); $table->string('middle_name')->nullable(); $table->string('last_name'); $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('address_id')->nullable(); $table->string('contact_number')->nullable()->unique(); $table->string('email')->unique(); $table->boolean('is_verified')->default(true); $table->string('password'); $table->string('remember_token')->nullable(); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->timestamps(); });
        Schema::create('station_barangay_coverage', function (Blueprint $table) { $table->id('station_barangay_coverage_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('barangay_id'); $table->string('coverage_type')->default('primary'); $table->string('source')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('barangay_officers', function (Blueprint $table) { $table->id('barangay_officer_id'); $table->unsignedBigInteger('barangay_id'); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('previous_role_id')->nullable(); $table->string('position'); $table->string('status')->default('active'); $table->unsignedBigInteger('appointed_by')->nullable(); $table->timestamps(); $table->unique(['barangay_id', 'user_id']); });
        Schema::create('barangay_halls', function (Blueprint $table) { $table->id('barangay_hall_id'); $table->unsignedBigInteger('barangay_id'); $table->string('hall_name'); $table->unsignedBigInteger('captain_user_id')->nullable(); $table->timestamps(); });

        DB::table('roles')->insert([['role_id' => 1, 'role' => 'chief'], ['role_id' => 2, 'role' => 'citizen'], ['role_id' => 3, 'role' => 'firefighter'], ['role_id' => 4, 'role' => 'barangay']]);
        DB::table('barangays')->insert([['barangay_id' => 10, 'barangay_name' => 'Covered'], ['barangay_id' => 20, 'barangay_name' => 'Outside']]);
        DB::table('stations')->insert(['station_id' => 5, 'station_name' => 'Central']);
        DB::table('users')->insert([
            ['user_id' => 1, 'account_code' => 'CHF-1', 'first_name' => 'Chief', 'last_name' => 'User', 'role_id' => 1, 'email' => 'chief@example.test', 'password' => bcrypt('Password1!')],
            ['user_id' => 2, 'account_code' => 'CIT-2', 'first_name' => 'Eligible', 'last_name' => 'Citizen', 'role_id' => 2, 'email' => 'citizen@example.test', 'password' => bcrypt('Password1!')],
            ['user_id' => 3, 'account_code' => 'BFP-3', 'first_name' => 'Protected', 'last_name' => 'Firefighter', 'role_id' => 3, 'email' => 'firefighter@example.test', 'password' => bcrypt('Password1!')],
        ]);
        DB::table('station_officers')->insert(['station_id' => 5, 'user_id' => 1]);
        DB::table('station_barangay_coverage')->insert(['station_id' => 5, 'barangay_id' => 10, 'coverage_type' => 'primary', 'is_active' => true]);
    }

    public function test_chief_can_promote_eligible_user_and_removal_restores_previous_role(): void
    {
        $this->actingAs(User::findOrFail(1));
        $response = $this->postJson('/station-admin/barangay-officers', ['mode' => 'existing', 'user_id' => 2, 'barangay_id' => 10, 'position' => 'kagawad', 'status' => 'active']);
        $response->assertCreated()->assertJsonPath('officer.position', 'Kagawad');
        $officerId = $response->json('officer.barangay_officer_id');
        $this->assertSame(4, (int) User::findOrFail(2)->role_id);
        $this->assertSame(2, (int) DB::table('barangay_officers')->where('barangay_officer_id', $officerId)->value('previous_role_id'));

        $this->deleteJson("/station-admin/barangay-officers/{$officerId}")->assertOk();
        $this->assertSame(2, (int) User::findOrFail(2)->role_id);
    }

    public function test_chief_cannot_assign_protected_user_or_outside_coverage(): void
    {
        $this->actingAs(User::findOrFail(1));
        $base = ['mode' => 'existing', 'position' => 'officer', 'status' => 'active'];
        $this->postJson('/station-admin/barangay-officers', $base + ['user_id' => 3, 'barangay_id' => 10])->assertUnprocessable();
        $this->postJson('/station-admin/barangay-officers', $base + ['user_id' => 2, 'barangay_id' => 20])->assertForbidden();
    }

    public function test_public_registration_cannot_self_assign_a_privileged_role(): void
    {
        $response = $this->postJson('/register', [
            'first_name' => 'Public', 'last_name' => 'Applicant', 'contact_number' => '09123456789',
            'email' => 'public@example.test', 'role_id' => 3, 'password' => 'Secure123@',
            'password_confirmation' => 'Secure123@', 'terms' => '1', 'emergency_agreement' => '1',
        ]);

        $response->assertOk();
        $this->assertSame(2, (int) User::where('email', 'public@example.test')->value('role_id'));
        $this->assertDatabaseMissing('station_officers', ['user_id' => User::where('email', 'public@example.test')->value('user_id')]);
    }
}
