<?php

namespace Tests\Feature;

use App\Models\ProgressInvestigationReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgressInvestigationSequenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['investigation_report_images', 'final_investigation_report', 'progress_investigation_report', 'fire_origin', 'spot_investigation_report', 'station_officers', 'users', 'roles'] as $table) {
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
        Schema::create('station_officers', function (Blueprint $table) {
            $table->id('station_officer_id');
            $table->unsignedBigInteger('station_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('spot_investigation_report', function (Blueprint $table) {
            $table->id('spot_investigation_report_id');
            $table->unsignedBigInteger('incident_id')->nullable();
            $table->unsignedBigInteger('station_officer_id')->nullable();
            $table->timestamps();
        });
        Schema::create('fire_origin', function (Blueprint $table) {
            $table->id('fire_origin_id');
            $table->string('origin');
        });
        Schema::create('progress_investigation_report', function (Blueprint $table) {
            $table->id('progress_investigation_report_id');
            $table->unsignedBigInteger('spot_investigation_id');
            $table->string('report_stage', 16)->default('single');
            $table->unsignedBigInteger('parent_progress_investigation_id')->nullable();
            $table->unsignedBigInteger('fire_origin_id')->nullable();
            $table->string('responsible_arson')->nullable();
            $table->text('fire_cause');
            $table->unsignedBigInteger('station_officer_id');
            $table->timestamp('report_completed_at')->nullable();
            $table->string('memorandum_for')->nullable();
            $table->string('mission_order_number')->nullable();
            $table->date('mission_order_date')->nullable();
            $table->text('facts_of_case')->nullable();
            $table->text('disposition')->nullable();
            $table->unsignedBigInteger('noted_by_station_officer_id')->nullable();
            $table->unsignedBigInteger('approved_by_station_officer_id')->nullable();
            $table->timestamps();
            $table->unique(['spot_investigation_id', 'report_stage']);
        });
        Schema::create('final_investigation_report', function (Blueprint $table) {
            $table->id('final_investigation_report_id');
            $table->unsignedBigInteger('progress_investigation_id')->unique();
            $table->timestamps();
        });
        Schema::create('investigation_report_images', function (Blueprint $table) {
            $table->id('investigation_report_image_id');
            $table->string('report_type');
            $table->unsignedBigInteger('report_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('roles')->insert(['role_id' => 2, 'role' => 'firefighter']);
        DB::table('users')->insert([
            'user_id' => 10,
            'role_id' => 2,
            'first_name' => 'Test',
            'last_name' => 'Investigator',
        ]);
        DB::table('station_officers')->insert(['station_officer_id' => 10, 'station_id' => 1, 'user_id' => 10]);
        DB::table('spot_investigation_report')->insert([
            ['spot_investigation_report_id' => 101, 'station_officer_id' => 10],
            ['spot_investigation_report_id' => 102, 'station_officer_id' => 10],
        ]);
        Role::flushDefinitionCache();
        $this->actingAs(User::findOrFail(10));
    }

    public function test_first_then_second_progress_is_allowed_but_regular_path_cannot_be_mixed_in(): void
    {
        $first = $this->postJson('/api/progress-investigation', $this->progressPayload(101, 'first'))
            ->assertOk()
            ->assertJsonPath('data.report_stage', 'first')
            ->json('data');

        $this->postJson('/api/progress-investigation', $this->progressPayload(
            101,
            'second',
            (int) $first['progress_investigation_report_id']
        ))->assertOk()->assertJsonPath('data.report_stage', 'second');

        $this->postJson('/api/progress-investigation', $this->progressPayload(101, 'single'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('spot_investigation_id');
    }

    public function test_second_progress_requires_a_matching_first_progress_report(): void
    {
        $regular = $this->postJson('/api/progress-investigation', $this->progressPayload(102, 'single'))
            ->assertOk()
            ->json('data');

        $this->postJson('/api/progress-investigation', $this->progressPayload(
            101,
            'second',
            (int) $regular['progress_investigation_report_id']
        ))->assertUnprocessable()->assertJsonValidationErrors('parent_progress_investigation_id');
    }

    public function test_only_regular_and_second_progress_reports_are_available_for_final(): void
    {
        $first = ProgressInvestigationReport::create($this->modelPayload(101, 'first'));
        ProgressInvestigationReport::create($this->modelPayload(101, 'second', $first->getKey()));
        ProgressInvestigationReport::create($this->modelPayload(102, 'single'));

        $response = $this->getJson('/api/progress-investigations-available')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['second', 'single'],
            collect($response->json())->pluck('report_stage')->all()
        );
    }

    public function test_final_report_rejects_the_first_progress_report(): void
    {
        $first = ProgressInvestigationReport::create($this->modelPayload(101, 'first'));

        $this->postJson('/api/final-investigation', [
            'progress_investigation_id' => $first->getKey(),
            'station_officer_id' => 10,
            'total_damage' => 1000,
            'exact_address' => 'Test address',
        ])->assertUnprocessable()->assertJsonValidationErrors('progress_investigation_id');
    }

    private function progressPayload(int $spotId, string $stage, ?int $parentId = null): array
    {
        return [
            'spot_investigation_id' => $spotId,
            'report_stage' => $stage,
            'parent_progress_investigation_id' => $parentId,
            'fire_cause' => 'Under investigation',
            'station_officer_id' => 10,
        ];
    }

    private function modelPayload(int $spotId, string $stage, ?int $parentId = null): array
    {
        return $this->progressPayload($spotId, $stage, $parentId) + [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
