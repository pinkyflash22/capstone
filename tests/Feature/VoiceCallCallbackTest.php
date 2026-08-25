<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoiceCallCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.agora.app_id' => str_repeat('a', 32),
            'services.agora.app_certificate' => str_repeat('b', 32),
            'services.voice_calls.max_concurrent_responder_calls' => 2,
        ]);

        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        Schema::create('stations', function (Blueprint $table) {
            $table->id('station_id');
            $table->string('station_name');
            $table->timestamps();
        });
        Schema::create('station_officers', function (Blueprint $table) {
            $table->id('station_officer_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('voice_calls', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name')->unique();
            $table->string('direction')->default('inbound');
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->unsignedBigInteger('callback_of')->nullable();
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->unsignedBigInteger('station_id')->nullable();
            $table->unsignedBigInteger('incident_id')->nullable();
            $table->unsignedBigInteger('created_incident_id')->nullable();
            $table->decimal('caller_latitude', 10, 7)->nullable();
            $table->decimal('caller_longitude', 10, 7)->nullable();
            $table->integer('caller_accuracy')->nullable();
            $table->string('caller_location_label')->nullable();
            $table->string('status')->default('ringing');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->unsignedBigInteger('held_by')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->unsignedBigInteger('flagged_by')->nullable();
            $table->string('flag_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('voice_responder_presence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('station_id');
            $table->unsignedSmallInteger('active_call_count')->default(0);
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['role_id' => 1, 'role' => 'station admin'],
            ['role_id' => 2, 'role' => 'citizen'],
            ['role_id' => 3, 'role' => 'provincial admin'],
        ]);
        Role::flushDefinitionCache();
        DB::table('users')->insert([
            ['user_id' => 10, 'role_id' => 1, 'first_name' => 'Station', 'last_name' => 'Admin'],
            ['user_id' => 11, 'role_id' => 1, 'first_name' => 'Second', 'last_name' => 'Admin'],
            ['user_id' => 20, 'role_id' => 2, 'first_name' => 'Citizen', 'last_name' => 'Caller'],
            ['user_id' => 30, 'role_id' => 3, 'first_name' => 'Provincial', 'last_name' => 'Admin'],
        ]);
        DB::table('stations')->insert([
            ['station_id' => 5, 'station_name' => 'Station Five'],
            ['station_id' => 6, 'station_name' => 'Station Six'],
        ]);
        DB::table('station_officers')->insert(['station_id' => 5, 'user_id' => 10]);
        DB::table('station_officers')->insert(['station_id' => 5, 'user_id' => 11]);
    }

    public function test_station_can_call_back_a_registered_citizen_who_contacted_that_station(): void
    {
        $sourceId = $this->voiceCall(20, 5, 'ended', 'inbound');

        $response = $this->actingAs(User::findOrFail(10))->postJson('/voice-calls/callbacks', [
            'citizen_user_id' => 20,
            'callback_of' => $sourceId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('call.direction', 'outbound')
            ->assertJsonPath('call.caller.id', 20)
            ->assertJsonPath('call.assigned_admin_id', 10);
    }

    public function test_station_cannot_call_a_citizen_whose_history_belongs_to_another_station(): void
    {
        $this->voiceCall(20, 6, 'missed', 'inbound');

        $this->actingAs(User::findOrFail(10))->postJson('/voice-calls/callbacks', [
            'citizen_user_id' => 20,
        ])->assertForbidden();
    }

    public function test_responder_can_answer_multiple_calls_idempotently_up_to_the_limit(): void
    {
        $first = $this->voiceCall(20, 5, 'ringing', 'inbound');
        $second = $this->voiceCall(20, 5, 'ringing', 'inbound');
        $third = $this->voiceCall(20, 5, 'ringing', 'inbound');
        $admin = User::findOrFail(10);

        $this->actingAs($admin)->postJson("/voice-calls/{$first}/accept")->assertOk();
        $this->actingAs($admin)->postJson("/voice-calls/{$first}/accept")->assertOk();
        $this->actingAs($admin)->postJson("/voice-calls/{$second}/accept")->assertOk();
        $this->actingAs($admin)->postJson("/voice-calls/{$third}/accept")->assertStatus(409);

        $this->assertSame(2, DB::table('voice_calls')->where('status', 'answered')->count());
    }

    public function test_responder_can_hold_and_resume_only_their_answered_call(): void
    {
        $callId = $this->voiceCall(20, 5, 'ringing', 'inbound');
        $admin = User::findOrFail(10);
        $this->actingAs($admin)->postJson("/voice-calls/{$callId}/accept")->assertOk();

        $this->actingAs($admin)->postJson("/voice-calls/{$callId}/hold")
            ->assertOk()->assertJsonPath('call.is_on_hold', true);
        $this->actingAs($admin)->postJson("/voice-calls/{$callId}/resume")
            ->assertOk()->assertJsonPath('call.is_on_hold', false);
    }

    public function test_waiting_call_is_assigned_to_the_online_responder_with_less_work(): void
    {
        $activeId = $this->voiceCall(20, 5, 'answered', 'inbound');
        DB::table('voice_calls')->where('id', $activeId)->update(['assigned_admin_id' => 10]);
        DB::table('voice_responder_presence')->insert([
            'user_id' => 11,
            'station_id' => 5,
            'active_call_count' => 0,
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $waitingId = $this->voiceCall(20, 5, 'ringing', 'inbound');
        DB::table('voice_calls')->where('id', $waitingId)->update(['created_at' => now()->subSeconds(3)]);

        $this->actingAs(User::findOrFail(10))->getJson('/voice-calls/incoming')->assertOk();

        $this->assertSame(11, (int) DB::table('voice_calls')->where('id', $waitingId)->value('assigned_admin_id'));
    }

    public function test_provincial_admin_can_monitor_calls_across_stations(): void
    {
        $first = $this->voiceCall(20, 5, 'ringing', 'inbound');
        $second = $this->voiceCall(20, 6, 'ringing', 'inbound');

        $response = $this->actingAs(User::findOrFail(30))->getJson('/voice-calls/incoming');

        $response->assertOk();
        $ids = collect($response->json('incoming_calls'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($first));
        $this->assertTrue($ids->contains($second));
    }

    public function test_provincial_admin_callback_uses_the_original_calls_station(): void
    {
        $sourceId = $this->voiceCall(20, 6, 'missed', 'inbound');

        $response = $this->actingAs(User::findOrFail(30))->postJson('/voice-calls/callbacks', [
            'citizen_user_id' => 20,
            'callback_of' => $sourceId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('call.station.id', 6)
            ->assertJsonPath('call.assigned_admin_id', 30);
    }

    private function voiceCall(int $callerId, int $stationId, string $status, string $direction): int
    {
        return (int) DB::table('voice_calls')->insertGetId([
            'channel_name' => uniqid('test_', true),
            'direction' => $direction,
            'caller_id' => $callerId,
            'initiated_by' => $callerId,
            'station_id' => $stationId,
            'status' => $status,
            'queued_at' => now(),
            'answered_at' => $status === 'answered' ? now() : null,
            'ended_at' => in_array($status, ['ended', 'missed', 'rejected'], true) ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
