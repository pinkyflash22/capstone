<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('historical_fire_incidents')) {
            return;
        }

        Schema::table('historical_fire_incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('historical_fire_incidents', 'temperature_2m')) {
                $table->decimal('temperature_2m', 6, 2)->nullable()->after('temperature');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'relative_humidity_2m')) {
                $table->decimal('relative_humidity_2m', 6, 2)->nullable()->after('humidity');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'precipitation')) {
                $table->decimal('precipitation', 8, 2)->nullable()->after('relative_humidity_2m');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'rain')) {
                $table->decimal('rain', 8, 2)->nullable()->after('precipitation');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'weather_code')) {
                $table->integer('weather_code')->nullable()->after('wind_direction');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'wind_speed_10m')) {
                $table->decimal('wind_speed_10m', 8, 2)->nullable()->after('wind_speed');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'wind_gusts')) {
                $table->decimal('wind_gusts', 8, 2)->nullable()->after('wind_speed_10m');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'wind_gusts_10m')) {
                $table->decimal('wind_gusts_10m', 8, 2)->nullable()->after('wind_gusts');
            }

            if (! Schema::hasColumn('historical_fire_incidents', 'wind_direction_10m')) {
                $table->decimal('wind_direction_10m', 8, 2)->nullable()->after('wind_direction');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('historical_fire_incidents')) {
            return;
        }

        Schema::table('historical_fire_incidents', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'temperature_2m',
                'relative_humidity_2m',
                'precipitation',
                'rain',
                'weather_code',
                'wind_speed_10m',
                'wind_gusts',
                'wind_gusts_10m',
                'wind_direction_10m',
            ] as $column) {
                if (Schema::hasColumn('historical_fire_incidents', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
