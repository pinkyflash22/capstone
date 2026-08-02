<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if foreign key exists before trying to drop it
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'analysis' 
            AND CONSTRAINT_NAME = 'analysis_station_id_foreign'
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        Schema::table('analysis', function (Blueprint $table) use ($foreignKeyExists) {
            // Only drop foreign key if it exists
            if (!empty($foreignKeyExists)) {
                $table->dropForeign(['station_id']);
            }
            
            // Drop column if it exists
            if (Schema::hasColumn('analysis', 'station_id')) {
                $table->dropColumn('station_id');
            }
        });
        
        // Now add the column with proper foreign key
        Schema::table('analysis', function (Blueprint $table) {
            if (!Schema::hasColumn('analysis', 'station_id')) {
                $table->foreignId('station_id')
                    ->nullable();
            }
        });
        
        // Add the foreign key constraint separately
        Schema::table('analysis', function (Blueprint $table) {
            $table->foreign('station_id')
                ->references('station_id')
                ->on('stations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('analysis', function (Blueprint $table) {
            // Drop foreign key if exists
            try {
                $table->dropForeign(['station_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
            
            // Drop column if exists
            if (Schema::hasColumn('analysis', 'station_id')) {
                $table->dropColumn('station_id');
            }
        });
    }
};
