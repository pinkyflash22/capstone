<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            if (! Schema::hasColumn('spot_investigation_report', 'weather_context')) {
                $table->json('weather_context')->nullable()->after('estimated_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            if (Schema::hasColumn('spot_investigation_report', 'weather_context')) {
                $table->dropColumn('weather_context');
            }
        });
    }
};
