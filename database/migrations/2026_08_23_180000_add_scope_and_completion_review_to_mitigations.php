<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table) {
            $table->string('scope_type', 20)->default('barangay')->after('barangay_id');
            $table->unsignedBigInteger('scope_city_id')->nullable()->after('scope_type');
            $table->unsignedBigInteger('owner_station_id')->nullable()->after('scope_city_id');
            $table->string('completion_approval_status', 20)->nullable()->after('completed_at');
            $table->unsignedBigInteger('completion_requested_by')->nullable()->after('completion_approval_status');
            $table->timestamp('completion_requested_at')->nullable()->after('completion_requested_by');
            $table->unsignedBigInteger('completion_decided_by')->nullable()->after('completion_requested_at');
            $table->timestamp('completion_decided_at')->nullable()->after('completion_decided_by');
            $table->text('completion_decision_notes')->nullable()->after('completion_decided_at');
            $table->index(['scope_type', 'scope_city_id'], 'mitigation_scope_city_idx');
            $table->index(['owner_station_id', 'completion_approval_status'], 'mitigation_owner_completion_idx');
            $table->foreign('scope_city_id', 'mitigation_scope_city_fk')->references('city_id')->on('cities')->nullOnDelete();
            $table->foreign('owner_station_id', 'mitigation_owner_station_fk')->references('station_id')->on('stations')->nullOnDelete();
            $table->foreign('completion_requested_by', 'mitigation_completion_requester_fk')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('completion_decided_by', 'mitigation_completion_decider_fk')->references('user_id')->on('users')->nullOnDelete();
        });

        $cityByBarangay = DB::table('barangays')->pluck('city_id', 'barangay_id');
        DB::table('mitigation_actions')
            ->select(['mitigation_action_id', 'barangay_id'])
            ->orderBy('mitigation_action_id')
            ->chunkById(250, function ($actions) use ($cityByBarangay): void {
                foreach ($actions as $action) {
                    $cityId = $cityByBarangay->get($action->barangay_id);
                    if ($cityId) {
                        DB::table('mitigation_actions')
                            ->where('mitigation_action_id', $action->mitigation_action_id)
                            ->update(['scope_city_id' => $cityId]);
                    }
                }
            }, 'mitigation_action_id');

        Schema::create('mitigation_action_activities', function (Blueprint $table) {
            $table->id('mitigation_action_activity_id');
            $table->foreignId('mitigation_action_id')->constrained('mitigation_actions', 'mitigation_action_id')->cascadeOnDelete();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('event_type', 50);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('actor_user_id', 'mitigation_activity_actor_fk')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['mitigation_action_id', 'created_at'], 'mitigation_activity_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitigation_action_activities');
        Schema::table('mitigation_actions', function (Blueprint $table) {
            $table->dropForeign('mitigation_scope_city_fk');
            $table->dropForeign('mitigation_owner_station_fk');
            $table->dropForeign('mitigation_completion_requester_fk');
            $table->dropForeign('mitigation_completion_decider_fk');
            $table->dropIndex('mitigation_scope_city_idx');
            $table->dropIndex('mitigation_owner_completion_idx');
            $table->dropColumn(['scope_type', 'scope_city_id', 'owner_station_id', 'completion_approval_status', 'completion_requested_by', 'completion_requested_at', 'completion_decided_by', 'completion_decided_at', 'completion_decision_notes']);
        });
    }
};
