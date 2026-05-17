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
        Schema::table('boundary_location', function (Blueprint $table) {
            if (Schema::hasColumn('boundary_location', 'station_id')) {
                // Make station_id nullable
                $table->unsignedBigInteger('station_id')->nullable()->change();
            } else {
                // Add station_id as nullable if it doesn't exist
                $table->unsignedBigInteger('station_id')->nullable()->after('geojson');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boundary_location', function (Blueprint $table) {
             if (Schema::hasColumn('boundary_location', 'station_id')) {
                // Revert to not nullable (but be careful - this might fail if there are null values)
                $table->unsignedBigInteger('station_id')->nullable(false)->change();
            }
        });
    }
};
