<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKeyExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['analysis', 'analysis_station_id_foreign', 'FOREIGN KEY']
        ));

        Schema::table('analysis', function (Blueprint $table) use ($foreignKeyExists) {
            if ($foreignKeyExists) {
                $table->dropForeign(['station_id']);
            }

            if (Schema::hasColumn('analysis', 'station_id')) {
                $table->dropColumn('station_id');
            }
        });

        Schema::table('analysis', function (Blueprint $table) {
            if (!Schema::hasColumn('analysis', 'station_id')) {
                $table->foreignId('station_id')->nullable();
            }
        });

        Schema::table('analysis', function (Blueprint $table) {
            $table->foreign('station_id')
                ->references('station_id')
                ->on('stations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        $foreignKeyExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['analysis', 'analysis_station_id_foreign', 'FOREIGN KEY']
        ));

        Schema::table('analysis', function (Blueprint $table) use ($foreignKeyExists) {
            if ($foreignKeyExists) {
                $table->dropForeign(['station_id']);
            }

            if (Schema::hasColumn('analysis', 'station_id')) {
                $table->dropColumn('station_id');
            }
        });
    }
};
