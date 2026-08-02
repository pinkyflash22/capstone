<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_investigation_report', function (Blueprint $table) {
            if (! Schema::hasColumn('progress_investigation_report', 'report_completed_at')) {
                $table->timestamp('report_completed_at')->nullable()->after('station_officer_id');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'memorandum_for')) {
                $table->string('memorandum_for')->nullable()->after('report_completed_at');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'mission_order_number')) {
                $table->string('mission_order_number')->nullable()->after('memorandum_for');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'mission_order_date')) {
                $table->date('mission_order_date')->nullable()->after('mission_order_number');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'facts_of_case')) {
                $table->text('facts_of_case')->nullable()->after('mission_order_date');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'disposition')) {
                $table->text('disposition')->nullable()->after('facts_of_case');
            }

            if (! Schema::hasColumn('progress_investigation_report', 'noted_by_station_officer_id')) {
                $table->foreignId('noted_by_station_officer_id')
                    ->nullable()
                    ->after('disposition');
                $table->foreign(
                    'noted_by_station_officer_id',
                    'pir_noted_by_fk'
                )->references('station_officer_id')->on('station_officers')->nullOnDelete();
            }

            if (! Schema::hasColumn('progress_investigation_report', 'approved_by_station_officer_id')) {
                $table->foreignId('approved_by_station_officer_id')
                    ->nullable()
                    ->after('noted_by_station_officer_id');
                $table->foreign(
                    'approved_by_station_officer_id',
                    'pir_approved_by_fk'
                )->references('station_officer_id')->on('station_officers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_investigation_report', function (Blueprint $table) {
            if (Schema::hasColumn('progress_investigation_report', 'approved_by_station_officer_id')) {
                $table->dropForeign('pir_approved_by_fk');
                $table->dropColumn('approved_by_station_officer_id');
            }

            if (Schema::hasColumn('progress_investigation_report', 'noted_by_station_officer_id')) {
                $table->dropForeign('pir_noted_by_fk');
                $table->dropColumn('noted_by_station_officer_id');
            }

            foreach ([
                'disposition',
                'facts_of_case',
                'mission_order_date',
                'mission_order_number',
                'memorandum_for',
                'report_completed_at',
            ] as $column) {
                if (Schema::hasColumn('progress_investigation_report', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
