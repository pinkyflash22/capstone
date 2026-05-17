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
        if (! Schema::hasTable('risk_areas')) {
            return;
        }

        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'station_id')) {
                $table->foreignId('station_id')
                    ->nullable()
                    ->after('barangay_hall_id')
                    ->constrained('stations', 'station_id')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('risk_areas')) {
            return;
        }

        Schema::table('risk_areas', function (Blueprint $table) {
            if (Schema::hasColumn('risk_areas', 'station_id')) {
                $table->dropConstrainedForeignId('station_id');
            }
        });
    }
};
