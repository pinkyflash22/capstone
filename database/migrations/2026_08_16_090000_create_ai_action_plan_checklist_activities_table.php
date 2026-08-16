<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_plan_checklist_activities', function (Blueprint $table) {
            $table->bigIncrements('ai_action_plan_checklist_activity_id');
            $table->foreignId('ai_action_plan_checklist_id');
            $table->foreign('ai_action_plan_checklist_id', 'aapca_checklist_fk')
                ->references('ai_action_plan_checklist_id')->on('ai_action_plan_checklists')->cascadeOnDelete();
            $table->foreignId('ai_action_plan_checklist_item_id')->nullable();
            $table->foreign('ai_action_plan_checklist_item_id', 'aapca_item_fk')
                ->references('ai_action_plan_checklist_item_id')->on('ai_action_plan_checklist_items')->nullOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id', 'aapca_user_fk')->references('user_id')->on('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('old_value', 80)->nullable();
            $table->string('new_value', 80)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ai_action_plan_checklist_id', 'created_at'], 'aapca_checklist_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_plan_checklist_activities');
    }
};
