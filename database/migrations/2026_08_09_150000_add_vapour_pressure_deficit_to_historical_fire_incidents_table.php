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
            if (! Schema::hasColumn('historical_fire_incidents', 'vapour_pressure_deficit')) {
                $table->decimal('vapour_pressure_deficit', 8, 3)->nullable()->after('weather_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('historical_fire_incidents') || ! Schema::hasColumn('historical_fire_incidents', 'vapour_pressure_deficit')) {
            return;
        }

        Schema::table('historical_fire_incidents', function (Blueprint $table) {
            $table->dropColumn('vapour_pressure_deficit');
        });
    }
};
