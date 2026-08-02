<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_fire_incidents', function (Blueprint $table) {
            $table->id('historical_fire_incident_id');
            $table->foreignId('barangay_id')
                ->nullable()
                ->constrained('barangays', 'barangay_id')
                ->nullOnDelete();
            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents', 'incident_id')
                ->nullOnDelete();

            $table->date('incident_date');
            $table->time('incident_time')->nullable();

            $table->string('source_name');
            $table->enum('source_type', ['research', 'barangay_hall', 'bfp_record', 'lgu_record', 'system_incident', 'other'])
                ->default('research');
            $table->string('source_reference')->nullable();

            $table->enum('fire_classification', ['accidental', 'intentional', 'undetermined'])
                ->default('undetermined');
            $table->string('fire_cause')->nullable();
            $table->string('incident_location')->nullable();

            $table->unsignedInteger('incident_count')->default(1);
            $table->unsignedInteger('injured_count')->default(0);
            $table->unsignedInteger('death_count')->default(0);
            $table->decimal('estimated_damage', 14, 2)->nullable();

            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('wind_speed')->nullable();
            $table->string('wind_direction')->nullable();
            $table->string('weather_condition')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['barangay_id', 'incident_date'], 'historical_fire_incidents_barangay_date_idx');
            $table->index(['source_type', 'incident_date'], 'historical_fire_incidents_source_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_fire_incidents');
    }
};
