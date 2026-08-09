<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incident_contexts')) {
            return;
        }

        Schema::table('incident_contexts', function (Blueprint $table) {
            if (! Schema::hasColumn('incident_contexts', 'temperature_2m')) {
                $table->decimal('temperature_2m', 6, 2)->nullable()->after('temperature');
            }

            if (! Schema::hasColumn('incident_contexts', 'relative_humidity_2m')) {
                $table->decimal('relative_humidity_2m', 6, 2)->nullable()->after('humidity');
            }

            if (! Schema::hasColumn('incident_contexts', 'precipitation')) {
                $table->decimal('precipitation', 8, 2)->nullable()->after('relative_humidity_2m');
            }

            if (! Schema::hasColumn('incident_contexts', 'rain')) {
                $table->decimal('rain', 8, 2)->nullable()->after('precipitation');
            }

            if (! Schema::hasColumn('incident_contexts', 'wind_speed_10m')) {
                $table->decimal('wind_speed_10m', 8, 2)->nullable()->after('wind_speed');
            }

            if (! Schema::hasColumn('incident_contexts', 'weather_code')) {
                $table->integer('weather_code')->nullable()->after('wind_direction');
            }

            if (! Schema::hasColumn('incident_contexts', 'wind_gusts')) {
                $table->decimal('wind_gusts', 8, 2)->nullable()->after('wind_speed');
            }

            if (! Schema::hasColumn('incident_contexts', 'wind_gusts_10m')) {
                $table->decimal('wind_gusts_10m', 8, 2)->nullable()->after('wind_gusts');
            }

            if (! Schema::hasColumn('incident_contexts', 'wind_direction_10m')) {
                $table->decimal('wind_direction_10m', 8, 2)->nullable()->after('wind_direction');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incident_contexts')) {
            return;
        }

        Schema::table('incident_contexts', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'temperature_2m',
                'relative_humidity_2m',
                'precipitation',
                'rain',
                'weather_code',
                'wind_gusts',
                'wind_speed_10m',
                'wind_gusts_10m',
                'wind_direction_10m',
            ] as $column) {
                if (Schema::hasColumn('incident_contexts', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
