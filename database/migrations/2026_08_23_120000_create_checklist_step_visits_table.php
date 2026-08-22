<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_step_visits', function (Blueprint $table) {
            $table->id('checklist_step_visit_id');
            $table->foreignId('ai_action_plan_checklist_id')->constrained('ai_action_plan_checklists', 'ai_action_plan_checklist_id')->cascadeOnDelete();
            $table->foreignId('ai_action_plan_checklist_item_id')->constrained('ai_action_plan_checklist_items', 'ai_action_plan_checklist_item_id')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays', 'barangay_id')->cascadeOnDelete();
            $table->foreignId('firefighter_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('verified_status', 30);
            $table->text('visit_notes')->nullable();
            $table->timestamp('visited_at');
            $table->string('signoff_status', 30)->default('pending');
            $table->foreignId('barangay_signed_off_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('signed_off_at')->nullable();
            $table->text('signoff_notes')->nullable();
            $table->timestamps();

            $table->index(['barangay_id', 'signoff_status'], 'checklist_visit_barangay_signoff_idx');
            $table->index(['ai_action_plan_checklist_id', 'visited_at'], 'checklist_visit_plan_date_idx');
        });

        Schema::create('checklist_step_visit_photos', function (Blueprint $table) {
            $table->id('checklist_step_visit_photo_id');
            $table->foreignId('checklist_step_visit_id')->constrained('checklist_step_visits', 'checklist_step_visit_id')->cascadeOnDelete();
            $table->string('file_path', 700);
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_step_visit_photos');
        Schema::dropIfExists('checklist_step_visits');
    }
};
