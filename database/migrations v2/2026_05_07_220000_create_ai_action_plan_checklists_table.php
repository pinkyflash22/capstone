<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_plan_checklists', function (Blueprint $table) {
            $table->id('ai_action_plan_checklist_id');

            $table->foreignId('analysis_id')
                ->nullable()
                ->constrained('analysis', 'analysis_id')
                ->nullOnDelete();

            $table->foreignId('station_id')
                ->nullable()
                ->constrained('stations', 'station_id')
                ->nullOnDelete();

            $table->foreignId('barangay_id')
                ->nullable()
                ->constrained('barangays', 'barangay_id')
                ->nullOnDelete();

            $table->enum('source_type', ['recommendation', 'suggestion', 'action_plan'])
                ->default('recommendation');
            $table->string('source_title');
            $table->text('source_description')->nullable();
            $table->string('plan_title');
            $table->text('plan_description')->nullable();
            $table->json('checklist_items');
            $table->string('success_indicator')->nullable();

            $table->enum('priority_level', ['low', 'medium', 'high', 'critical'])
                ->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'confirmed', 'rejected'])
                ->default('pending');

            $table->date('target_date')->nullable();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();
            $table->text('confirmation_notes')->nullable();
            $table->json('ai_payload')->nullable();
            $table->boolean('is_ai_generated')->default(true);
            $table->timestamps();

            $table->index(['station_id', 'status'], 'ai_action_plan_station_status_idx');
            $table->index(['analysis_id', 'priority_level'], 'ai_action_plan_analysis_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_plan_checklists');
    }
};
