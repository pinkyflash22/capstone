<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// public function up(): void
// {
//     // 🔥 SPOT INVESTIGATION
//     Schema::table('spot_investigation_report', function (Blueprint $table) {

//         // drop old foreign key first
//         $table->dropForeign('spot_investigation_report_station_officer_id_foreign');
//         $table->dropColumn('investigator');
//         $table->dropColumn('casualty');
//         // add new column
       
//         $table->foreignId('station_officer_id')
//               ->after('spot_investigation_report_id')
//               ->constrained('station_officers', 'station_officer_id')
//               ->onDelete('cascade');
//          $table->integer('dead')->after('station_officer_id');
//         $table->integer('injured')->after('dead');

//     });


//     // 🔥 PROGRESS INVESTIGATION
//     Schema::table('progress_investigation_report', function (Blueprint $table) {

//         $table->foreignId('station_officer_id')
//               ->after('spot_investigation_id')
//               ->constrained('station_officers', 'station_officer_id')
//               ->onDelete('cascade');
//     });


//     // 🔥 FINAL INVESTIGATION
//     Schema::table('final_investigation_report', function (Blueprint $table) {

//         $table->foreignId('station_officer_id')
//               ->after('progress_investigation_id')
//               ->constrained('station_officers', 'station_officer_id')
//               ->onDelete('cascade');
//     });
// }
public function up(): void
{
    Schema::table('spot_investigation_report', function (Blueprint $table) {

        // ✅ Drop investigator column ONLY if exists
        if (Schema::hasColumn('spot_investigation_report', 'investigator')) {

            try {
                $table->dropForeign(['investigator']);
            } catch (\Exception $e) {}

            $table->dropColumn('investigator');
        }

        // ✅ Drop casualty ONLY if exists
        if (Schema::hasColumn('spot_investigation_report', 'casualty')) {
            $table->dropColumn('casualty');
        }

        // ✅ Add station_officer_id ONLY if not exists
        if (!Schema::hasColumn('spot_investigation_report', 'station_officer_id')) {
            $table->foreignId('station_officer_id')
                  ->after('spot_investigation_report_id')
                  ->constrained('station_officers', 'station_officer_id')
                  ->onDelete('cascade');
        }

        // ✅ Add new fields safely
        if (!Schema::hasColumn('spot_investigation_report', 'dead')) {
            $table->integer('dead')->default(0);
        }

        if (!Schema::hasColumn('spot_investigation_report', 'injured')) {
            $table->integer('injured')->default(0);
        }
    });


    // PROGRESS
    Schema::table('progress_investigation_report', function (Blueprint $table) {

        if (!Schema::hasColumn('progress_investigation_report', 'station_officer_id')) {
            $table->foreignId('station_officer_id')
                  ->constrained('station_officers', 'station_officer_id')
                  ->onDelete('cascade');
        }
    });


    // FINAL
    Schema::table('final_investigation_report', function (Blueprint $table) {

        if (!Schema::hasColumn('final_investigation_report', 'station_officer_id')) {
            $table->foreignId('station_officer_id')
                  ->constrained('station_officers', 'station_officer_id')
                  ->onDelete('cascade');
        }
    });
}
    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    // SPOT
    Schema::table('spot_investigation_report', function (Blueprint $table) {

        $table->dropForeign(['station_officer_id']);
        $table->dropColumn('station_officer_id');
        $table->dropColumn('casualty');

        // restore old column
        $table->foreignId('station_officer_id')
              ->constrained('station_officers', 'station_officer_id')
              ->onDelete('cascade');
    });

    // PROGRESS
    Schema::table('progress_investigation_report', function (Blueprint $table) {

        $table->dropForeign(['station_officer_id']);
        $table->dropColumn('station_officer_id');
    });

    // FINAL
    Schema::table('final_investigation_report', function (Blueprint $table) {

        $table->dropForeign(['station_officer_id']);
        $table->dropColumn('station_officer_id');
    });
}
};
