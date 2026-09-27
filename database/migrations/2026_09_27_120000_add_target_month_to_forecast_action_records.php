<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_action_plan_checklists') && ! Schema::hasColumn('ai_action_plan_checklists', 'target_month')) {
            $hasSuperseded = Schema::hasColumn('ai_action_plan_checklists', 'is_superseded');

            Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
                $table->date('target_month')->nullable()->after('target_date');
            });

            if ($hasSuperseded) {
                Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
                    $table->index(['station_id', 'barangay_id', 'target_month', 'is_superseded'], 'ai_action_plan_month_scope_idx');
                });
            }
        }

        if (Schema::hasTable('mitigation_actions') && ! Schema::hasColumn('mitigation_actions', 'target_month')) {
            $hasSuperseded = Schema::hasColumn('mitigation_actions', 'is_superseded');

            Schema::table('mitigation_actions', function (Blueprint $table) {
                $table->date('target_month')->nullable()->after('target_date');
            });

            if ($hasSuperseded) {
                Schema::table('mitigation_actions', function (Blueprint $table) {
                    $table->index(['barangay_id', 'target_month', 'is_superseded'], 'mitigation_actions_month_scope_idx');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mitigation_actions') && Schema::hasColumn('mitigation_actions', 'target_month')) {
            Schema::table('mitigation_actions', function (Blueprint $table) {
                if (Schema::hasColumn('mitigation_actions', 'is_superseded')) {
                    $table->dropIndex('mitigation_actions_month_scope_idx');
                }
                $table->dropColumn('target_month');
            });
        }

        if (Schema::hasTable('ai_action_plan_checklists') && Schema::hasColumn('ai_action_plan_checklists', 'target_month')) {
            Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
                if (Schema::hasColumn('ai_action_plan_checklists', 'is_superseded')) {
                    $table->dropIndex('ai_action_plan_month_scope_idx');
                }
                $table->dropColumn('target_month');
            });
        }
    }
};
