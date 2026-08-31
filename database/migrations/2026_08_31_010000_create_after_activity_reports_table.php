<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_activity_reports', function (Blueprint $table) {
            $table->id('after_activity_report_id');
            $table->foreignId('event_activity_id')->nullable()->constrained('event_activities', 'event_id')->nullOnDelete();
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('station_officer_id')->nullable()->constrained('station_officers', 'station_officer_id')->nullOnDelete();
            $table->foreignId('approved_by_station_officer_id')->nullable()->constrained('station_officers', 'station_officer_id')->nullOnDelete();
            $table->string('activity_title');
            $table->string('activity_type', 100)->nullable();
            $table->date('activity_date');
            $table->string('activity_location')->nullable();
            $table->string('memorandum_for')->nullable();
            $table->timestamp('report_completed_at')->nullable();
            $table->text('references')->nullable();
            $table->text('background')->nullable();
            $table->text('significance')->nullable();
            $table->text('activity_details');
            $table->text('issues_challenges')->nullable();
            $table->text('discussion_assessment')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();

            $table->unique('event_activity_id');
            $table->index(['station_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_activity_reports');
    }
};
