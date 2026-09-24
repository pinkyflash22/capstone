<?php

namespace Tests\Unit;

use App\Models\BoundaryLocation;
use App\Models\Role;
use App\Models\User;
use App\Services\BoundaryManagementScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BoundaryManagementScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('station_officers', function (Blueprint $table) {
            $table->id('station_officer_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('station_barangay_coverage', function (Blueprint $table) {
            $table->id('station_barangay_coverage_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('barangay_id');
            $table->string('coverage_type')->default('primary');
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_manager_can_resolve_the_station_for_active_assigned_coverage(): void
    {
        $user = $this->manager(41, 'stationadmin');
        $this->assignOfficerAndCoverage(41, 7, 101, true);

        $this->assertSame(7, app(BoundaryManagementScope::class)->stationForBarangay($user, 101));
    }

    public function test_manager_cannot_modify_an_unassigned_or_inactive_barangay(): void
    {
        $user = $this->manager(42, 'chief');
        $this->assignOfficerAndCoverage(42, 8, 102, false);

        $this->expectException(AuthorizationException::class);
        app(BoundaryManagementScope::class)->stationForBarangay($user, 102);
    }

    public function test_manager_cannot_modify_a_boundary_owned_by_another_station(): void
    {
        $user = $this->manager(43, 'chief');
        $this->assignOfficerAndCoverage(43, 9, 103, true);
        $boundary = new BoundaryLocation(['barangay_id' => 103, 'station_id' => 10]);

        $this->expectException(AuthorizationException::class);
        app(BoundaryManagementScope::class)->stationForBoundary($user, $boundary);
    }

    public function test_manager_can_claim_a_legacy_unowned_boundary_inside_active_coverage(): void
    {
        $user = $this->manager(44, 'stationadmin');
        $this->assignOfficerAndCoverage(44, 11, 104, true);
        $boundary = new BoundaryLocation(['barangay_id' => 104, 'station_id' => null]);

        $this->assertSame(11, app(BoundaryManagementScope::class)->stationForBoundary($user, $boundary));
    }

    private function manager(int $userId, string $roleName): User
    {
        $user = new User();
        $user->setAttribute('user_id', $userId);
        $user->setRelation('role', new Role(['role' => $roleName]));

        return $user;
    }

    private function assignOfficerAndCoverage(
        int $userId,
        int $stationId,
        int $barangayId,
        bool $active,
    ): void {
        DB::table('station_officers')->insert([
            'station_id' => $stationId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('station_barangay_coverage')->insert([
            'station_id' => $stationId,
            'barangay_id' => $barangayId,
            'coverage_type' => 'primary',
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
