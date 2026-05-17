<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->string('dti')->nullable();
            $table->string('mayors_permit')->nullable();
            $table->string('tin_id')->nullable();
            $table->foreignId('fsic_request_id')->constrained('fsic_requests')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('images', function (Blueprint $table) {
            $table->id('image_id');
            $table->string('path');
            $table->foreignId('spot_investigation')->nullable()
                ->constrained('spot_investigation_report', 'spot_investigation_report_id')
                ->nullOnDelete();
            $table->foreignId('progress_investigation')->nullable()
                ->constrained('progress_investigation_report', 'progress_investigation_report_id')
                ->nullOnDelete();
            $table->foreignId('final_investigation')->nullable()
                ->constrained('final_investigation_report', 'final_investigation_report_id')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('historical_data', function (Blueprint $table) {
            $table->id('historical_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->string('month')->nullable();
            $table->year('year')->nullable();
            $table->integer('incident_count')->default(0);
            $table->string('risk_level')->nullable();
            $table->decimal('average_temperature', 8, 2)->nullable();
            $table->enum('season', ['dry', 'wet'])->nullable();
            $table->boolean('near_water')->nullable();
            $table->enum('density', ['low', 'medium', 'high'])->nullable();
            $table->string('materials')->nullable();
            $table->string('peak_hours')->nullable();
            $table->string('accesibility')->nullable();
            $table->string('type_incident')->nullable();
            $table->enum('occupancy', ['residential', 'commercial', 'industrial', 'institutional', 'mixed-use'])->nullable();
            $table->timestamps();
        });

        Schema::create('recommendation_checklist', function (Blueprint $table) {
            $table->id('recommendation_id');
            $table->text('recommendation');
            $table->foreignId('analysis_id')->constrained('analysis', 'analysis_id')->cascadeOnDelete();
            $table->boolean('is_complied')->default(false);
            $table->string('barangay')->nullable();
            $table->timestamps();
        });

        Schema::create('suggestion_checklist', function (Blueprint $table) {
            $table->id('suggestion_id');
            $table->text('suggestions');
            $table->foreignId('analysis_id')->constrained('analysis', 'analysis_id')->cascadeOnDelete();
            $table->boolean('is_complied')->default(false);
            $table->string('barangay')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_checklist');
        Schema::dropIfExists('recommendation_checklist');
        Schema::dropIfExists('historical_data');
        Schema::dropIfExists('images');
        Schema::dropIfExists('documents');
    }
};
