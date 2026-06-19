<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitigation_actions', function (Blueprint $table) {
            $table->id('mitigation_action_id');
            $table->foreignId('barangay_id')->constrained('barangays', 'barangay_id')->cascadeOnDelete();
            $table->foreignId('risk_area_id')->nullable()->constrained('risk_areas', 'risk_area_id')->nullOnDelete();
            $table->foreignId('barangay_prediction_checklist_id')->nullable()->constrained('barangay_prediction_checklists', 'barangay_prediction_checklist_id')->nullOnDelete();
            $table->unsignedBigInteger('ai_action_plan_checklist_id')->nullable();
            $table->string('title');
            $table->string('category', 80)->default('other');
            $table->string('priority_level', 30)->default('medium');
            $table->string('status', 30)->default('planned');
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('description');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('ai_action_plan_checklist_id', 'mitigation_actions_ai_checklist_fk')
                ->references('ai_action_plan_checklist_id')
                ->on('ai_action_plan_checklists')
                ->nullOnDelete();
            $table->foreign('created_by', 'mitigation_actions_created_by_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reviewed_by', 'mitigation_actions_reviewed_by_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['barangay_id', 'status'], 'mitigation_actions_barangay_status_idx');
            $table->index(['barangay_id', 'priority_level'], 'mitigation_actions_barangay_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitigation_actions');
    }
};
