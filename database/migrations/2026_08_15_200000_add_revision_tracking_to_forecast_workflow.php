<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis', function (Blueprint $table) {
            $table->unsignedInteger('revision_number')->default(1);
        });

        Schema::create('analysis_revision_snapshots', function (Blueprint $table) {
            $table->id('analysis_revision_snapshot_id');
            $table->foreignId('analysis_id')->constrained('analysis', 'analysis_id')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('snapshot_data');
            $table->unsignedBigInteger('replaced_by')->nullable();
            $table->timestamp('replaced_at');
            $table->timestamps();

            $table->unique(['analysis_id', 'revision_number'], 'analysis_revision_snapshot_unique');
            $table->foreign('replaced_by', 'analysis_revision_replaced_by_fk')
                ->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
            $table->unsignedInteger('analysis_revision')->default(1);
            $table->boolean('is_superseded')->default(false);
            $table->boolean('requires_revision_review')->default(false);
            $table->timestamp('superseded_at')->nullable();
            $table->index(['analysis_id', 'analysis_revision'], 'ai_action_plan_revision_idx');
        });

        Schema::table('mitigation_actions', function (Blueprint $table) {
            $table->unsignedInteger('analysis_revision')->nullable();
            $table->boolean('is_superseded')->default(false);
            $table->boolean('requires_revision_review')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->timestamp('superseded_at')->nullable();
            $table->index(['analysis_revision', 'is_superseded'], 'mitigation_revision_state_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table) {
            $table->dropIndex('mitigation_revision_state_idx');
            $table->dropColumn(['analysis_revision', 'is_superseded', 'requires_revision_review', 'is_manually_edited', 'superseded_at']);
        });

        Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
            $table->dropIndex('ai_action_plan_revision_idx');
            $table->dropColumn(['analysis_revision', 'is_superseded', 'requires_revision_review', 'superseded_at']);
        });

        Schema::dropIfExists('analysis_revision_snapshots');

        Schema::table('analysis', function (Blueprint $table) {
            $table->dropColumn('revision_number');
        });
    }
};
