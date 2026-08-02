<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_contexts', function (Blueprint $table) {
            $table->id('incident_context_id');
            $table->foreignId('incident_id')
                ->unique()
                ->constrained('incidents', 'incident_id')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('incident_month')->nullable();
            $table->string('season', 40)->nullable();
            $table->string('day_of_week', 20)->nullable();
            $table->string('time_of_day_bucket', 40)->nullable();
            $table->boolean('is_holiday_or_event_period')->default(false);
            $table->string('event_period_label')->nullable();
            $table->decimal('temperature', 6, 2)->nullable();
            $table->decimal('humidity', 6, 2)->nullable();
            $table->decimal('rainfall', 8, 2)->nullable();
            $table->decimal('wind_speed', 8, 2)->nullable();
            $table->string('weather_condition')->nullable();
            $table->boolean('is_dry_season')->default(false);
            $table->string('context_source', 40)->default('derived');
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['incident_month', 'season'], 'incident_contexts_month_season_idx');
            $table->index(['is_dry_season', 'is_holiday_or_event_period'], 'incident_contexts_dry_holiday_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_contexts');
    }
};
