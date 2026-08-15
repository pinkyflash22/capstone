<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'station_id')) {
                $table->foreignId('station_id')->nullable()->after('team_id')->constrained('stations', 'station_id')->nullOnDelete();
            }
        });

        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'station_id')) {
                $table->foreignId('station_id')->nullable()->after('marker_id')->constrained('stations', 'station_id')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'station_id')) $table->dropConstrainedForeignId('station_id');
        });
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'station_id')) $table->dropConstrainedForeignId('station_id');
        });
    }
};
