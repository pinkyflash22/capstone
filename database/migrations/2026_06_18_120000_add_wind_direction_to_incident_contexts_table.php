<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_contexts', function (Blueprint $table) {
            if (! Schema::hasColumn('incident_contexts', 'wind_direction')) {
                $table->string('wind_direction', 20)->nullable()->after('wind_speed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incident_contexts', function (Blueprint $table) {
            if (Schema::hasColumn('incident_contexts', 'wind_direction')) {
                $table->dropColumn('wind_direction');
            }
        });
    }
};
