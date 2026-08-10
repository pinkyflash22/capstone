<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_activity_requests', function (Blueprint $table) {
            $table->bigIncrements('community_activity_request_id');
            $table->unsignedBigInteger('requester_user_id');
            $table->string('requester_role', 60)->nullable();
            $table->string('submitted_from', 40)->nullable();
            $table->unsignedBigInteger('station_id')->nullable();
            $table->unsignedBigInteger('barangay_id')->nullable();
            $table->string('request_type', 80);
            $table->string('title');
            $table->text('description');
            $table->date('preferred_date')->nullable();
            $table->time('preferred_start_time')->nullable();
            $table->time('preferred_end_time')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_number', 60)->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('converted_event_id')->nullable();
            $table->timestamps();

            $table->index(['station_id', 'status']);
            $table->index(['requester_user_id', 'status']);
            $table->index(['barangay_id', 'status']);
            $table->index('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_activity_requests');
    }
};
