<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_weather_contexts', function (Blueprint $table) {
            $table->id('daily_weather_context_id');
            $table->foreignId('station_id')->nullable()->constrained('stations', 'station_id')->nullOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays', 'barangay_id')->cascadeOnDelete();
            $table->date('observation_date');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('temperature_avg_c', 6, 2)->nullable();
            $table->decimal('temperature_min_c', 6, 2)->nullable();
            $table->decimal('temperature_max_c', 6, 2)->nullable();
            $table->decimal('humidity_avg_percent', 6, 2)->nullable();
            $table->decimal('wind_avg_kph', 7, 2)->nullable();
            $table->decimal('wind_max_kph', 7, 2)->nullable();
            $table->decimal('wind_gust_max_kph', 7, 2)->nullable();
            $table->decimal('precipitation_total_mm', 8, 2)->nullable();
            $table->decimal('rain_total_mm', 8, 2)->nullable();
            $table->unsignedSmallInteger('dominant_weather_code')->nullable();
            $table->string('dominant_condition', 100)->nullable();
            $table->unsignedSmallInteger('hourly_observation_count')->default(0);
            $table->string('source', 60)->default('open-meteo-forecast-archive');
            $table->json('raw_payload')->nullable();
            $table->timestampTz('fetched_at');
            $table->timestamps();

            $table->unique(
                ['barangay_id', 'observation_date', 'source'],
                'daily_weather_barangay_date_source_unique'
            );
            $table->index(['station_id', 'observation_date'], 'daily_weather_station_date_idx');
            $table->index(['barangay_id', 'observation_date'], 'daily_weather_barangay_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_weather_contexts');
    }
};
