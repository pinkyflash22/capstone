<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_activities', function (Blueprint $table) {
            $table->id('event_id');
            $table->string('event');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('schedule')->nullable();
            $table->string('activity_type')->default('operations');
            $table->string('status')->default('planned');
            $table->string('barangay')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['station_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_activities');
    }
};
