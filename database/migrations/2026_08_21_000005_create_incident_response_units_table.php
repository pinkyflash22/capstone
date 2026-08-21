<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_response_units', function (Blueprint $table) {
            $table->id('incident_response_unit_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams', 'team_id')->nullOnDelete();
            $table->foreignId('equipment_station_id')->constrained('equipments_to_stations', 'equipment_station_id')->restrictOnDelete();
            $table->unsignedSmallInteger('run_number');
            $table->string('dispatch_source', 30);
            $table->string('status', 20)->default('en_route');
            $table->foreignId('requested_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->decimal('distance_meters', 12, 2)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['incident_id', 'run_number'], 'incident_response_run_unique');
            $table->index(['incident_id', 'status']);
            $table->index(['station_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_response_units');
    }
};
