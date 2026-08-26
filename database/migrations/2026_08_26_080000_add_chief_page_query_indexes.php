<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->index(['deleted_at', 'is_pinned', 'created_at'], 'posts_feed_order_idx');
        });

        Schema::table('station_officers', function (Blueprint $table): void {
            $table->index(['user_id', 'station_id'], 'station_officers_user_station_idx');
            $table->index(['station_id', 'status'], 'station_officers_station_status_idx');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->index(['station_id', 'status', 'created_at'], 'incidents_station_status_created_idx');
            $table->index(['team_id', 'status', 'created_at'], 'incidents_team_status_created_idx');
        });

        Schema::table('analysis', function (Blueprint $table): void {
            $table->index(['station_id', 'created_at'], 'analysis_station_created_idx');
        });

        Schema::table('ai_action_plan_checklists', function (Blueprint $table): void {
            $table->index(
                ['station_id', 'is_superseded', 'status', 'created_at'],
                'ai_action_plan_station_current_status_idx'
            );
            $table->index(
                ['station_id', 'is_superseded', 'barangay_id'],
                'ai_action_plan_station_current_barangay_idx'
            );
        });

        Schema::table('ai_action_plan_checklist_items', function (Blueprint $table): void {
            $table->index(
                ['ai_action_plan_checklist_id', 'item_order'],
                'ai_action_plan_items_order_idx'
            );
        });

        Schema::table('mitigation_actions', function (Blueprint $table): void {
            $table->index(
                ['ai_action_plan_checklist_id', 'is_superseded', 'mitigation_action_id'],
                'mitigation_checklist_current_latest_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mitigation_actions', fn (Blueprint $table) => $table->dropIndex('mitigation_checklist_current_latest_idx'));
        Schema::table('ai_action_plan_checklist_items', fn (Blueprint $table) => $table->dropIndex('ai_action_plan_items_order_idx'));
        Schema::table('ai_action_plan_checklists', function (Blueprint $table): void {
            $table->dropIndex('ai_action_plan_station_current_status_idx');
            $table->dropIndex('ai_action_plan_station_current_barangay_idx');
        });
        Schema::table('analysis', fn (Blueprint $table) => $table->dropIndex('analysis_station_created_idx'));
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex('incidents_station_status_created_idx');
            $table->dropIndex('incidents_team_status_created_idx');
        });
        Schema::table('station_officers', function (Blueprint $table): void {
            $table->dropIndex('station_officers_user_station_idx');
            $table->dropIndex('station_officers_station_status_idx');
        });
        Schema::table('posts', fn (Blueprint $table) => $table->dropIndex('posts_feed_order_idx'));
    }
};
