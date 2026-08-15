<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incident_report')) {
            return;
        }

        Schema::create('incident_report', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id')->unique();
            $table->unsignedBigInteger('marker_id')->nullable();
            $table->unsignedBigInteger('station_id')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('victims')->default(0);
            $table->string('severity')->default('medium');
            $table->timestamp('dateEnded')->nullable();
            $table->timestamps();

            $table->foreign('incident_id')
                ->references('incident_id')
                ->on('incidents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_report');
    }
};
