<?php

namespace Tests\Unit;

use App\Http\Controllers\DutyRosterController;
use App\Http\Controllers\staff\FirefighterController;
use App\Models\DutyShift;
use App\Models\DutyRosterTemplate;
use App\Models\Incident;
use App\Models\Team;
use App\Models\User;
use App\Services\DutyRosterRecurrenceService;
use App\Services\DutyRosterService;
use App\Services\IncidentResourceDispatchService;
use App\Services\IncidentAutoAssignmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Tests\TestCase;

class DutyRosterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['apparatus_equipment', 'incident_response_units', 'apparatus', 'incidents', 'markers', 'equipments_to_stations', 'equipments', 'event_activities', 'duty_exceptions', 'duty_shift_assignments', 'duty_shifts', 'duty_roster_template_assignments', 'duty_roster_templates', 'teams', 'station_officers', 'stations', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->unsignedBigInteger('role_id'); $table->string('first_name'); $table->string('last_name'); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->unsignedBigInteger('marker_id')->nullable(); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->string('rank')->nullable(); $table->string('status')->default('off-duty'); $table->timestamps(); });
        Schema::create('teams', function (Blueprint $table) {
            $table->id('team_id'); $table->unsignedBigInteger('station_id');
            foreach (DutyRosterService::DEPLOYABLE_ROLES as $role) $table->unsignedBigInteger($role)->nullable();
            $table->string('status')->default('off'); $table->boolean('roster_managed')->default(true);
            $table->unsignedBigInteger('active_duty_shift_id')->nullable(); $table->unsignedBigInteger('pending_duty_shift_id')->nullable();
            $table->timestamp('roster_synced_at')->nullable(); $table->timestamps();
        });
        Schema::create('duty_roster_templates', function (Blueprint $table) { $table->id('duty_roster_template_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('team_id'); $table->string('label'); $table->unsignedTinyInteger('weekday'); $table->time('starts_time'); $table->time('ends_time'); $table->date('effective_from'); $table->date('effective_until')->nullable(); $table->string('status')->default('active'); $table->unsignedBigInteger('created_by')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); });
        Schema::create('duty_roster_template_assignments', function (Blueprint $table) { $table->id('duty_roster_template_assignment_id'); $table->unsignedBigInteger('duty_roster_template_id'); $table->unsignedBigInteger('station_officer_id'); $table->string('team_role'); $table->timestamps(); });
        Schema::create('duty_shifts', function (Blueprint $table) { $table->id('duty_shift_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('team_id'); $table->unsignedBigInteger('duty_roster_template_id')->nullable(); $table->date('occurrence_date')->nullable(); $table->boolean('is_override')->default(false); $table->string('label'); $table->timestamp('starts_at'); $table->timestamp('ends_at'); $table->string('status')->default('scheduled'); $table->unsignedBigInteger('created_by')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['duty_roster_template_id', 'occurrence_date']); });
        Schema::create('duty_shift_assignments', function (Blueprint $table) { $table->id('duty_shift_assignment_id'); $table->unsignedBigInteger('duty_shift_id'); $table->unsignedBigInteger('station_officer_id'); $table->string('team_role'); $table->timestamps(); });
        Schema::create('duty_exceptions', function (Blueprint $table) { $table->id('duty_exception_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('station_officer_id'); $table->string('type'); $table->timestamp('starts_at'); $table->timestamp('ends_at'); $table->string('reason')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); });
        Schema::create('event_activities', function (Blueprint $table) { $table->id('event_id'); $table->string('event'); $table->date('event_date'); $table->string('start_time')->nullable(); $table->string('end_time')->nullable(); $table->string('schedule')->nullable(); $table->string('activity_type')->nullable(); $table->string('status')->nullable(); $table->string('barangay')->nullable(); $table->text('notes')->nullable(); $table->unsignedBigInteger('station_id')->nullable(); $table->unsignedBigInteger('user_id')->nullable(); $table->timestamps(); });
        Schema::create('equipments', function (Blueprint $table) { $table->id('equipment_id'); $table->string('equipment_name'); $table->string('category')->nullable(); $table->string('type')->nullable(); $table->timestamps(); });
        Schema::create('equipments_to_stations', function (Blueprint $table) { $table->id('equipment_station_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('equipment_id'); $table->integer('available_quantity'); $table->integer('damaged_quantity')->default(0); $table->integer('maintenance_quantity')->default(0); $table->timestamps(); });
        Schema::create('apparatus', function (Blueprint $table) { $table->id('apparatus_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('equipment_id')->nullable(); $table->string('unit_number'); $table->string('plate_number')->nullable(); $table->string('apparatus_type')->default('fire_engine'); $table->unsignedInteger('water_capacity_liters')->nullable(); $table->string('status')->default('available'); $table->string('maintenance_status')->default('operational'); $table->date('last_maintenance_at')->nullable(); $table->date('next_maintenance_due_at')->nullable(); $table->string('gps_status')->default('offline'); $table->decimal('last_lat', 10, 7)->nullable(); $table->decimal('last_lng', 10, 7)->nullable(); $table->decimal('gps_accuracy_meters', 8, 2)->nullable(); $table->decimal('gps_speed_kph', 8, 2)->nullable(); $table->decimal('gps_heading', 6, 2)->nullable(); $table->timestamp('gps_last_seen_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->softDeletes(); });
        Schema::create('apparatus_equipment', function (Blueprint $table) { $table->id('apparatus_equipment_id'); $table->unsignedBigInteger('apparatus_id'); $table->unsignedBigInteger('equipment_station_id'); $table->unsignedInteger('quantity')->default(1); $table->string('notes')->nullable(); $table->timestamps(); $table->unique(['apparatus_id', 'equipment_station_id']); });
        Schema::create('markers', function (Blueprint $table) { $table->id('marker_id'); $table->string('title')->nullable(); $table->string('category')->nullable(); $table->decimal('lat', 10, 7)->nullable(); $table->decimal('lng', 10, 7)->nullable(); $table->timestamps(); });
        Schema::create('incidents', function (Blueprint $table) { $table->id('incident_id'); $table->unsignedBigInteger('marker_id')->nullable(); $table->string('status'); $table->timestamp('occurred_at')->nullable(); $table->string('occurred_at_source')->nullable(); $table->timestamp('occurred_at_verified_at')->nullable(); $table->unsignedBigInteger('occurred_at_verified_by')->nullable(); $table->timestamp('incident_completed_at')->nullable(); $table->unsignedBigInteger('station_id')->nullable(); $table->unsignedBigInteger('team_id')->nullable(); $table->timestamps(); });
        Schema::create('incident_response_units', function (Blueprint $table) { $table->id('incident_response_unit_id'); $table->unsignedBigInteger('incident_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('team_id')->nullable(); $table->unsignedBigInteger('equipment_station_id'); $table->unsignedBigInteger('apparatus_id')->nullable(); $table->unsignedSmallInteger('run_number'); $table->string('dispatch_source'); $table->string('status'); $table->unsignedBigInteger('requested_by')->nullable(); $table->decimal('distance_meters', 12, 2)->nullable(); $table->timestamp('estimated_arrival_at')->nullable(); $table->timestamp('dispatched_at')->nullable(); $table->timestamp('arrived_at')->nullable(); $table->timestamp('released_at')->nullable(); $table->timestamp('recalled_at')->nullable(); $table->unsignedBigInteger('recalled_by')->nullable(); $table->string('recall_reason')->nullable(); $table->timestamps(); $table->unique(['incident_id', 'run_number']); });

        DB::table('roles')->insert([['role_id' => 1, 'role' => 'firefighter'], ['role_id' => 2, 'role' => 'chief']]);
        DB::table('stations')->insert(['station_id' => 10, 'station_name' => 'Central Fire Station']);
        for ($id = 1; $id <= 5; $id++) {
            DB::table('users')->insert(['user_id' => $id, 'role_id' => $id === 5 ? 2 : 1, 'first_name' => "Officer{$id}", 'last_name' => 'Test']);
            DB::table('station_officers')->insert(['station_officer_id' => $id, 'station_id' => 10, 'user_id' => $id]);
        }
        DB::table('teams')->insert(['team_id' => 1, 'station_id' => 10, 'status' => 'off', 'roster_managed' => true]);
    }

    public function test_an_overnight_handoff_replaces_the_active_firefighters(): void
    {
        $morning = $this->shift('Morning', '2026-08-21 08:00:00', '2026-08-21 19:00:00', [[1, 'team_leader'], [2, 'commander_driver'], [5, 'station_duty']]);
        $night = $this->shift('Night', '2026-08-21 19:00:00', '2026-08-22 08:00:00', [[3, 'team_leader'], [4, 'commander_driver'], [5, 'station_duty']]);
        $service = app(DutyRosterService::class);

        $service->sync(10, Carbon::parse('2026-08-21 18:59:00'));
        $team = Team::find(1);
        $this->assertSame($morning->duty_shift_id, $team->active_duty_shift_id);
        $this->assertSame(1, $team->team_leader);
        $this->assertSame(2, $team->commander_driver);

        $service->sync(10, Carbon::parse('2026-08-21 19:01:00'));
        $team->refresh();
        $this->assertSame($night->duty_shift_id, $team->active_duty_shift_id);
        $this->assertSame(3, $team->team_leader);
        $this->assertSame(4, $team->commander_driver);
        $this->assertSame('off-duty', DB::table('station_officers')->where('station_officer_id', 1)->value('status'));
    }

    public function test_handoff_is_deferred_while_the_team_is_responding(): void
    {
        $morning = $this->shift('Morning', '2026-08-21 08:00:00', '2026-08-21 19:00:00', [[1, 'team_leader'], [2, 'commander_driver'], [5, 'station_duty']]);
        $night = $this->shift('Night', '2026-08-21 19:00:00', '2026-08-22 08:00:00', [[3, 'team_leader'], [4, 'commander_driver'], [5, 'station_duty']]);
        $service = app(DutyRosterService::class);
        $service->sync(10, Carbon::parse('2026-08-21 18:59:00'));
        Team::whereKey(1)->update(['status' => 'responding']);

        $service->sync(10, Carbon::parse('2026-08-21 19:01:00'));
        $team = Team::find(1);
        $this->assertSame($morning->duty_shift_id, $team->active_duty_shift_id);
        $this->assertSame($night->duty_shift_id, $team->pending_duty_shift_id);
        $this->assertSame(1, $team->team_leader);
    }

    public function test_expired_leave_is_reconciled_to_off_duty(): void
    {
        DB::table('station_officers')->where('station_officer_id', 1)->update(['status' => 'on-leave']);
        DB::table('duty_exceptions')->insert([
            'station_id' => 10, 'station_officer_id' => 1, 'type' => 'leave',
            'starts_at' => '2026-08-20 08:00:00', 'ends_at' => '2026-08-21 08:00:00',
        ]);

        app(DutyRosterService::class)->sync(10, Carbon::parse('2026-08-21 09:00:00'));

        $this->assertSame('off-duty', DB::table('station_officers')->where('station_officer_id', 1)->value('status'));
    }

    public function test_one_person_cannot_hold_multiple_positions_in_the_same_shift(): void
    {
        $controller = app(DutyRosterController::class);
        $method = new \ReflectionMethod($controller, 'validateAssignments');

        try {
            $method->invoke($controller, [
                ['station_officer_id' => 1, 'team_role' => 'team_leader'],
                ['station_officer_id' => 1, 'team_role' => 'commander_driver'],
                ['station_officer_id' => 5, 'team_role' => 'station_duty'],
            ], 10, Carbon::parse('2026-08-22 08:00:00'), Carbon::parse('2026-08-22 20:00:00'));

            $this->fail('Duplicate personnel assignments should be rejected.');
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $validation = $exception instanceof \Illuminate\Validation\ValidationException
                ? $exception
                : $exception->getPrevious();

            $this->assertInstanceOf(\Illuminate\Validation\ValidationException::class, $validation);
            $this->assertSame(
                'Each person can only hold one duty position per shift.',
                $validation->errors()['assignments'][0]
            );
        }
    }

    public function test_weekly_template_generates_overnight_occurrences_without_overwriting_an_override(): void
    {
        $template = DutyRosterTemplate::create([
            'station_id' => 10, 'team_id' => 1, 'label' => 'Monday Night', 'weekday' => 1,
            'starts_time' => '19:00:00', 'ends_time' => '08:00:00',
            'effective_from' => '2026-08-24', 'effective_until' => '2026-09-14', 'status' => 'active',
        ]);
        $template->assignments()->createMany([
            ['station_officer_id' => 1, 'team_role' => 'team_leader'],
            ['station_officer_id' => 2, 'team_role' => 'commander_driver'],
            ['station_officer_id' => 5, 'team_role' => 'station_duty'],
        ]);
        $recurrence = app(DutyRosterRecurrenceService::class);

        $this->assertSame(4, $recurrence->generate(10, Carbon::parse('2026-08-24'), Carbon::parse('2026-09-14')->endOfDay()));
        $first = DutyShift::whereDate('occurrence_date', '2026-08-24')->firstOrFail();
        $this->assertSame('2026-08-25 08:00:00', $first->ends_at->format('Y-m-d H:i:s'));
        $first->update(['label' => 'Special staffing', 'is_override' => true]);

        $this->assertSame(0, $recurrence->generate(10, Carbon::parse('2026-08-24'), Carbon::parse('2026-09-14')->endOfDay()));
        $this->assertSame('Special staffing', $first->fresh()->label);
    }

    public function test_editing_this_and_future_splits_the_series_without_changing_the_past(): void
    {
        Carbon::setTestNow('2026-08-21 10:00:00');
        $template = DutyRosterTemplate::create([
            'station_id' => 10, 'team_id' => 1, 'label' => 'Original Crew', 'weekday' => 1,
            'starts_time' => '08:00:00', 'ends_time' => '19:00:00',
            'effective_from' => '2026-08-24', 'effective_until' => '2026-09-14', 'status' => 'active', 'created_by' => 5,
        ]);
        $template->assignments()->createMany([
            ['station_officer_id' => 1, 'team_role' => 'team_leader'],
            ['station_officer_id' => 2, 'team_role' => 'commander_driver'],
            ['station_officer_id' => 5, 'team_role' => 'station_duty'],
        ]);
        $recurrence = app(DutyRosterRecurrenceService::class);
        $recurrence->generate(10, Carbon::parse('2026-08-24'), Carbon::parse('2026-09-14')->endOfDay());
        $target = DutyShift::whereDate('occurrence_date', '2026-08-31')->firstOrFail();
        $this->actingAs(User::findOrFail(5));

        app(DutyRosterController::class)->updateShift(Request::create('/station-admin/duty-roster/shifts/'.$target->duty_shift_id, 'PUT', [
            'team_id' => 1, 'label' => 'Replacement Crew',
            'starts_at' => '2026-08-31 08:00:00', 'ends_at' => '2026-08-31 19:00:00',
            'recurrence' => 'weekly', 'recurrence_until' => '2026-09-14', 'edit_scope' => 'future',
            'assignments' => [
                ['station_officer_id' => 3, 'team_role' => 'team_leader'],
                ['station_officer_id' => 4, 'team_role' => 'commander_driver'],
                ['station_officer_id' => 5, 'team_role' => 'station_duty'],
            ],
        ]), $target, app(DutyRosterService::class), $recurrence);

        $past = DutyShift::whereDate('occurrence_date', '2026-08-24')->with('assignments')->firstOrFail();
        $changed = DutyShift::whereDate('occurrence_date', '2026-08-31')->where('status', 'scheduled')->with('assignments')->firstOrFail();
        $this->assertSame('Original Crew', $past->label);
        $this->assertSame([1, 2, 5], $past->assignments->pluck('station_officer_id')->sort()->values()->all());
        $this->assertSame('Replacement Crew', $changed->label);
        $this->assertSame([3, 4, 5], $changed->assignments->pluck('station_officer_id')->sort()->values()->all());
        $this->assertSame('2026-08-30', $template->fresh()->effective_until->toDateString());
        Carbon::setTestNow();
    }

    public function test_firefighter_schedule_only_exposes_that_firefighters_own_duty_shifts(): void
    {
        Carbon::setTestNow('2026-08-21 09:00:00');
        $mine = $this->shift('My Day Shift', '2026-08-21 08:00:00', '2026-08-21 19:00:00', [[1, 'team_leader'], [2, 'commander_driver'], [5, 'station_duty']]);
        $this->shift('Other Crew', '2026-08-22 08:00:00', '2026-08-22 19:00:00', [[3, 'team_leader'], [4, 'commander_driver'], [5, 'station_duty']]);
        DB::table('duty_exceptions')->insert([
            'station_id' => 10, 'station_officer_id' => 1, 'type' => 'rest_day',
            'starts_at' => '2026-08-21 00:00:00', 'ends_at' => '2026-08-22 00:00:00', 'reason' => 'Approved rest day',
        ]);
        $this->actingAs(User::findOrFail(1));

        $payload = app(FirefighterController::class)->scheduleData()->getData(true)['data'];
        $duties = collect($payload['events'])->where('source_type', 'duty')->values();

        $this->assertCount(1, $duties);
        $this->assertSame('duty-'.$mine->duty_shift_id, $duties[0]['event_id']);
        $this->assertSame('Team Leader', $duties[0]['duty_role_label']);
        $this->assertSame('Rest Day', $duties[0]['status_label']);
        $this->assertFalse($duties[0]['can_edit']);
        Carbon::setTestNow();
    }

    public function test_backup_uses_same_station_resources_before_the_nearest_capable_station(): void
    {
        Carbon::setTestNow('2026-08-21 12:00:00');
        DB::table('markers')->insert([
            ['marker_id' => 1, 'title' => 'Residential Fire', 'category' => 'fire', 'lat' => 14.6000, 'lng' => 120.9800],
            ['marker_id' => 2, 'title' => 'Station Two', 'category' => 'station', 'lat' => 14.6100, 'lng' => 120.9900],
        ]);
        DB::table('stations')->where('station_id', 10)->update(['marker_id' => 1]);
        DB::table('stations')->insert(['station_id' => 20, 'station_name' => 'Nearest Backup Station', 'marker_id' => 2]);
        DB::table('equipments')->insert(['equipment_id' => 1, 'equipment_name' => 'Fire Truck', 'category' => 'vehicles']);
        DB::table('equipments_to_stations')->insert([
            ['equipment_station_id' => 1, 'station_id' => 10, 'equipment_id' => 1, 'available_quantity' => 1],
            ['equipment_station_id' => 2, 'station_id' => 20, 'equipment_id' => 1, 'available_quantity' => 1],
        ]);
        foreach ([6, 7, 8, 9] as $id) {
            DB::table('users')->insert(['user_id' => $id, 'role_id' => 1, 'first_name' => "Backup{$id}", 'last_name' => 'Firefighter']);
            DB::table('station_officers')->insert(['station_officer_id' => $id, 'station_id' => $id < 8 ? 10 : 20, 'user_id' => $id, 'status' => 'available']);
        }
        DB::table('teams')->where('team_id', 1)->update(['status' => 'responding', 'roster_managed' => false, 'team_leader' => 1, 'commander_driver' => 2]);
        DB::table('teams')->insert([
            ['team_id' => 2, 'station_id' => 10, 'status' => 'available', 'roster_managed' => false, 'team_leader' => 6, 'commander_driver' => 7],
            ['team_id' => 3, 'station_id' => 20, 'status' => 'available', 'roster_managed' => false, 'team_leader' => 8, 'commander_driver' => 9],
        ]);
        DB::table('incidents')->insert(['incident_id' => 1, 'marker_id' => 1, 'status' => 'Responding', 'station_id' => 10, 'team_id' => 1]);
        $service = app(IncidentResourceDispatchService::class);
        $incident = Incident::findOrFail(1);

        $secondRun = $service->requestBackup($incident, 1);
        $this->assertSame('same_station', $secondRun->dispatch_source);
        $this->assertSame(2, $secondRun->team_id);
        $this->assertSame(0, DB::table('equipments_to_stations')->where('equipment_station_id', 1)->value('available_quantity'));

        $thirdRun = $service->requestBackup($incident, 1);
        $this->assertSame('other_station', $thirdRun->dispatch_source);
        $this->assertSame(20, $thirdRun->station_id);
        $this->assertGreaterThan(0, $thirdRun->distance_meters);

        $this->assertSame(2, $service->releaseIncidentUnits($incident));
        $this->assertSame(1, DB::table('equipments_to_stations')->where('equipment_station_id', 1)->value('available_quantity'));
        $this->assertSame(1, DB::table('equipments_to_stations')->where('equipment_station_id', 2)->value('available_quantity'));
        Carbon::setTestNow();
    }

    public function test_initial_fire_dispatch_skips_a_station_without_an_available_firetruck(): void
    {
        DB::table('markers')->insert([
            ['marker_id' => 1, 'title' => 'Warehouse Fire', 'category' => 'fire', 'lat' => 14.6000, 'lng' => 120.9800],
            ['marker_id' => 2, 'title' => 'Backup Station', 'category' => 'station', 'lat' => 14.6100, 'lng' => 120.9900],
        ]);
        DB::table('stations')->where('station_id', 10)->update(['marker_id' => 1]);
        DB::table('stations')->insert(['station_id' => 20, 'station_name' => 'Equipped Station', 'marker_id' => 2]);
        DB::table('station_officers')->whereIn('station_officer_id', [1, 2])->update(['status' => 'available']);
        DB::table('teams')->where('team_id', 1)->update(['status' => 'available', 'roster_managed' => false, 'team_leader' => 1, 'commander_driver' => 2]);
        DB::table('equipments')->insert(['equipment_id' => 1, 'equipment_name' => 'Fire Truck', 'category' => 'vehicles']);
        DB::table('equipments_to_stations')->insert(['equipment_station_id' => 1, 'station_id' => 10, 'equipment_id' => 1, 'available_quantity' => 0]);
        foreach ([6, 7] as $id) {
            DB::table('users')->insert(['user_id' => $id, 'role_id' => 1, 'first_name' => "Ready{$id}", 'last_name' => 'Firefighter']);
            DB::table('station_officers')->insert(['station_officer_id' => $id, 'station_id' => 20, 'user_id' => $id, 'status' => 'available']);
        }
        DB::table('teams')->insert(['team_id' => 2, 'station_id' => 20, 'status' => 'available', 'roster_managed' => false, 'team_leader' => 6, 'commander_driver' => 7]);
        DB::table('equipments_to_stations')->insert(['equipment_station_id' => 2, 'station_id' => 20, 'equipment_id' => 1, 'available_quantity' => 1]);
        DB::table('incidents')->insert(['incident_id' => 1, 'marker_id' => 1, 'status' => 'Reported', 'station_id' => 10]);

        $assigned = app(IncidentAutoAssignmentService::class)->assignSingleIncident(Incident::findOrFail(1));

        $this->assertSame(20, $assigned['station_id']);
        $this->assertSame(2, $assigned['team_id']);
        $this->assertSame('available', DB::table('teams')->where('team_id', 1)->value('status'));
        $this->assertSame(0, DB::table('equipments_to_stations')->where('equipment_station_id', 2)->value('available_quantity'));
    }

    public function test_only_the_assigned_driver_can_publish_live_apparatus_telemetry(): void
    {
        DB::table('markers')->insert(['marker_id' => 50, 'title' => 'Warehouse Fire', 'category' => 'fire', 'lat' => 14.6000, 'lng' => 120.9800]);
        DB::table('stations')->where('station_id', 10)->update(['marker_id' => 50]);
        DB::table('equipments')->insert(['equipment_id' => 50, 'equipment_name' => 'Fire Engine', 'category' => 'vehicles']);
        DB::table('equipments_to_stations')->insert(['equipment_station_id' => 50, 'station_id' => 10, 'equipment_id' => 50, 'available_quantity' => 1]);
        DB::table('apparatus')->insert(['apparatus_id' => 50, 'station_id' => 10, 'equipment_id' => 50, 'unit_number' => 'ENGINE 50', 'status' => 'dispatched', 'maintenance_status' => 'operational', 'gps_status' => 'offline']);
        DB::table('teams')->where('team_id', 1)->update(['status' => 'responding', 'roster_managed' => false, 'team_leader' => 1, 'commander_driver' => 2]);
        DB::table('incidents')->insert(['incident_id' => 50, 'marker_id' => 50, 'status' => 'Responding', 'station_id' => 10, 'team_id' => 1]);
        DB::table('incident_response_units')->insert(['incident_response_unit_id' => 50, 'incident_id' => 50, 'station_id' => 10, 'team_id' => 1, 'equipment_station_id' => 50, 'apparatus_id' => 50, 'run_number' => 1, 'dispatch_source' => 'primary', 'status' => 'en_route']);
        $payload = ['latitude' => 14.6010, 'longitude' => 120.9810, 'accuracy' => 8.5, 'speed_kph' => 42, 'heading' => 90];

        $this->actingAs(User::findOrFail(1))->postJson('/firefighter/response-units/50/apparatus-location', $payload)->assertForbidden();
        $this->actingAs(User::findOrFail(2))->postJson('/firefighter/response-units/50/apparatus-location', $payload)
            ->assertOk()->assertJsonPath('unit.unit_number', 'ENGINE 50')->assertJsonPath('unit.gps_status', 'online');

        $this->assertEqualsWithDelta(14.6010, (float) DB::table('apparatus')->where('apparatus_id', 50)->value('last_lat'), 0.000001);
        $this->assertSame(42.0, (float) DB::table('apparatus')->where('apparatus_id', 50)->value('gps_speed_kph'));
        $this->assertNotNull(DB::table('incident_response_units')->where('incident_response_unit_id', 50)->value('estimated_arrival_at'));
    }

    public function test_dispatch_reserves_an_exact_operational_apparatus_and_recall_releases_it(): void
    {
        Carbon::setTestNow('2026-08-21 12:00:00');
        DB::table('markers')->insert(['marker_id' => 1, 'title' => 'Structure Fire', 'category' => 'fire', 'lat' => 14.6000, 'lng' => 120.9800]);
        DB::table('stations')->where('station_id', 10)->update(['marker_id' => 1]);
        DB::table('equipments')->insert(['equipment_id' => 1, 'equipment_name' => 'Fire Engine', 'category' => 'vehicles']);
        DB::table('equipments_to_stations')->insert(['equipment_station_id' => 1, 'station_id' => 10, 'equipment_id' => 1, 'available_quantity' => 5]);
        DB::table('apparatus')->insert([
            ['apparatus_id' => 1, 'station_id' => 10, 'equipment_id' => 1, 'unit_number' => 'ENGINE 01', 'plate_number' => 'BFP-1001', 'apparatus_type' => 'pumper', 'water_capacity_liters' => 4000, 'status' => 'available', 'maintenance_status' => 'operational', 'gps_status' => 'online', 'last_lat' => 14.5900, 'last_lng' => 120.9700],
            ['apparatus_id' => 2, 'station_id' => 10, 'equipment_id' => 1, 'unit_number' => 'ENGINE 02', 'plate_number' => 'BFP-1002', 'apparatus_type' => 'pumper', 'water_capacity_liters' => 4000, 'status' => 'available', 'maintenance_status' => 'in_maintenance', 'gps_status' => 'offline', 'last_lat' => null, 'last_lng' => null],
        ]);
        foreach ([6, 7] as $id) {
            DB::table('users')->insert(['user_id' => $id, 'role_id' => 1, 'first_name' => "Backup{$id}", 'last_name' => 'Firefighter']);
            DB::table('station_officers')->insert(['station_officer_id' => $id, 'station_id' => 10, 'user_id' => $id, 'status' => 'available']);
        }
        DB::table('station_officers')->whereIn('station_officer_id', [1, 2])->update(['status' => 'dispatched']);
        DB::table('teams')->where('team_id', 1)->update(['status' => 'responding', 'roster_managed' => false, 'team_leader' => 1, 'commander_driver' => 2]);
        DB::table('teams')->insert(['team_id' => 2, 'station_id' => 10, 'status' => 'available', 'roster_managed' => false, 'team_leader' => 6, 'commander_driver' => 7]);
        DB::table('incidents')->insert(['incident_id' => 1, 'marker_id' => 1, 'status' => 'Responding', 'station_id' => 10, 'team_id' => 1]);
        $service = app(IncidentResourceDispatchService::class);
        $incident = Incident::findOrFail(1);

        $unit = $service->requestBackup($incident, 1);

        $this->assertSame(1, $unit->apparatus_id);
        $this->assertNotNull($unit->estimated_arrival_at);
        $this->assertSame('dispatched', DB::table('apparatus')->where('apparatus_id', 1)->value('status'));
        $this->assertSame(5, DB::table('equipments_to_stations')->where('equipment_station_id', 1)->value('available_quantity'));
        $serialized = $service->serializeUnit($unit);
        $this->assertSame('ENGINE 01', $serialized['unit_number']);
        $this->assertSame('BFP-1001', $serialized['plate_number']);
        $this->assertSame(4000, $serialized['water_capacity_liters']);
        $this->assertSame('online', $serialized['gps_status']);
        $this->assertCount(2, $serialized['team_members']);
        $this->assertSame(['Backup6 Firefighter', 'Backup7 Firefighter'], collect($serialized['team_members'])->pluck('name')->all());

        $recalled = $service->recallBackup($incident, $unit, 1, 'Fire controlled before arrival.');
        $this->assertSame('recalled', $recalled->status);
        $this->assertSame('available', DB::table('apparatus')->where('apparatus_id', 1)->value('status'));
        $this->assertSame('available', DB::table('teams')->where('team_id', 2)->value('status'));
        Carbon::setTestNow();
    }

    private function shift(string $label, string $start, string $end, array $assignments): DutyShift
    {
        $shift = DutyShift::create(['station_id' => 10, 'team_id' => 1, 'label' => $label, 'starts_at' => $start, 'ends_at' => $end, 'status' => 'scheduled']);
        $shift->assignments()->createMany(array_map(fn ($assignment) => ['station_officer_id' => $assignment[0], 'team_role' => $assignment[1]], $assignments));
        return $shift;
    }
}
