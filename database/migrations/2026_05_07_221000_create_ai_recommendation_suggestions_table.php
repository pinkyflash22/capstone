<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendation_suggestions', function (Blueprint $table) {
            $table->id('ai_recommendation_suggestion_id');
            $table->foreignId('analysis_id')->nullable();
            $table->foreign('analysis_id', 'ai_rec_sugg_analysis_fk')
                ->references('analysis_id')
                ->on('analysis')
                ->nullOnDelete();

            $table->foreignId('ai_action_plan_checklist_id')->nullable();
            $table->foreign('ai_action_plan_checklist_id', 'ai_rec_sugg_plan_fk')
                ->references('ai_action_plan_checklist_id')
                ->on('ai_action_plan_checklists')
                ->nullOnDelete();

            $table->foreignId('station_id')->nullable();
            $table->foreign('station_id', 'ai_rec_sugg_station_fk')
                ->references('station_id')
                ->on('stations')
                ->nullOnDelete();

            $table->foreignId('barangay_id')->nullable();
            $table->foreign('barangay_id', 'ai_rec_sugg_barangay_fk')
                ->references('barangay_id')
                ->on('barangays')
                ->nullOnDelete();

            $table->string('recommendation_title');
            $table->text('recommendation_description')->nullable();
            $table->enum('priority_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('request_context')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('steps')->nullable();
            $table->json('resources_needed')->nullable();
            $table->text('estimated_timeline')->nullable();
            $table->longText('ai_response')->nullable();
            $table->boolean('is_ai_generated')->default(true);
            $table->timestamps();

            $table->index(['analysis_id', 'created_at'], 'ai_recommendation_suggestions_analysis_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendation_suggestions');
    }
};
