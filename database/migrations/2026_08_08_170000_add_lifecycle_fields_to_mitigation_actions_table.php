<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('mitigation_actions', 'responsible_team_id')) {
                $table->unsignedBigInteger('responsible_team_id')->nullable()->after('ai_action_plan_checklist_id');
                $table->foreign('responsible_team_id', 'mitigation_actions_responsible_team_fk')
                    ->references('team_id')
                    ->on('teams')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mitigation_actions', 'responsible_user_id')) {
                $table->unsignedBigInteger('responsible_user_id')->nullable()->after('responsible_team_id');
                $table->foreign('responsible_user_id', 'mitigation_actions_responsible_user_fk')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mitigation_actions', 'source_type')) {
                $table->string('source_type', 40)->nullable()->after('status');
                $table->index(['source_type', 'status'], 'mitigation_actions_source_status_idx');
            }

            if (! Schema::hasColumn('mitigation_actions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('reviewed_by');
                $table->foreign('approved_by', 'mitigation_actions_approved_by_fk')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mitigation_actions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('mitigation_actions', 'implementation_notes')) {
                $table->text('implementation_notes')->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('mitigation_actions', 'completion_notes')) {
                $table->text('completion_notes')->nullable()->after('implementation_notes');
            }

            if (! Schema::hasColumn('mitigation_actions', 'outcome_result')) {
                $table->text('outcome_result')->nullable()->after('completion_notes');
            }

            if (! Schema::hasColumn('mitigation_actions', 'effectiveness')) {
                $table->string('effectiveness', 40)->nullable()->after('outcome_result');
            }

            if (! Schema::hasColumn('mitigation_actions', 'follow_up_required')) {
                $table->boolean('follow_up_required')->default(false)->after('effectiveness');
            }

            if (! Schema::hasColumn('mitigation_actions', 'follow_up_notes')) {
                $table->text('follow_up_notes')->nullable()->after('follow_up_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table) {
            if (Schema::hasColumn('mitigation_actions', 'follow_up_notes')) {
                $table->dropColumn('follow_up_notes');
            }

            if (Schema::hasColumn('mitigation_actions', 'follow_up_required')) {
                $table->dropColumn('follow_up_required');
            }

            if (Schema::hasColumn('mitigation_actions', 'effectiveness')) {
                $table->dropColumn('effectiveness');
            }

            if (Schema::hasColumn('mitigation_actions', 'outcome_result')) {
                $table->dropColumn('outcome_result');
            }

            if (Schema::hasColumn('mitigation_actions', 'completion_notes')) {
                $table->dropColumn('completion_notes');
            }

            if (Schema::hasColumn('mitigation_actions', 'implementation_notes')) {
                $table->dropColumn('implementation_notes');
            }

            if (Schema::hasColumn('mitigation_actions', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('mitigation_actions', 'approved_by')) {
                $table->dropForeign('mitigation_actions_approved_by_fk');
                $table->dropColumn('approved_by');
            }

            if (Schema::hasColumn('mitigation_actions', 'source_type')) {
                $table->dropIndex('mitigation_actions_source_status_idx');
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('mitigation_actions', 'responsible_user_id')) {
                $table->dropForeign('mitigation_actions_responsible_user_fk');
                $table->dropColumn('responsible_user_id');
            }

            if (Schema::hasColumn('mitigation_actions', 'responsible_team_id')) {
                $table->dropForeign('mitigation_actions_responsible_team_fk');
                $table->dropColumn('responsible_team_id');
            }
        });
    }
};
