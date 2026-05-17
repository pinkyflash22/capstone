<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangay_prediction_checklists', function (Blueprint $table) {
            $table->id('barangay_prediction_checklist_id');
            $table->foreignId('barangay_id');
            $table->foreign('barangay_id', 'bpcl_barangay_fk')
                ->references('barangay_id')
                ->on('barangays')
                ->cascadeOnDelete();

            $table->foreignId('analysis_id')->nullable();
            $table->foreign('analysis_id', 'bpcl_analysis_fk')
                ->references('analysis_id')
                ->on('analysis')
                ->nullOnDelete();

            $table->foreignId('historical_fire_incident_id')->nullable();
            $table->foreign('historical_fire_incident_id', 'bpcl_hist_fire_fk')
                ->references('historical_fire_incident_id')
                ->on('historical_fire_incidents')
                ->nullOnDelete();

            $table->string('title');
            $table->enum('checklist_type', ['recommendation', 'suggestion', 'action_plan'])
                ->default('recommendation');
            $table->text('ai_output');
            $table->text('planning_notes')->nullable();

            $table->enum('priority_level', ['low', 'medium', 'high', 'critical'])
                ->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'complied', 'not_complied', 'deferred'])
                ->default('pending');

            $table->date('target_date')->nullable();
            $table->timestamp('complied_at')->nullable();

            $table->foreignId('assigned_to')->nullable();
            $table->foreign('assigned_to', 'bpcl_assigned_to_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by')->nullable();
            $table->foreign('reviewed_by', 'bpcl_reviewed_by_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->text('compliance_remarks')->nullable();
            $table->boolean('is_ai_generated')->default(true);
            $table->timestamps();

            $table->index(['barangay_id', 'status'], 'barangay_prediction_checklists_barangay_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangay_prediction_checklists');
    }
};
