<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            if (! Schema::hasColumn('spot_investigation_report', 'report_completed_at')) {
                $table->timestamp('report_completed_at')->nullable()->after('incident_id');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'memorandum_for')) {
                $table->string('memorandum_for')->nullable()->after('report_completed_at');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'place_of_occurrence')) {
                $table->string('place_of_occurrence')->nullable()->after('memorandum_for');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'involved_structure')) {
                $table->string('involved_structure')->nullable()->after('place_of_occurrence');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'establishment_name')) {
                $table->string('establishment_name')->nullable()->after('involved_structure');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'occupant')) {
                $table->string('occupant')->nullable()->after('establishment_name');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'highest_alarm_level')) {
                $table->string('highest_alarm_level', 50)->nullable()->after('occupant');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'affected_establishments_count')) {
                $table->unsignedInteger('affected_establishments_count')->nullable()->after('highest_alarm_level');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'fatality_details')) {
                $table->text('fatality_details')->nullable()->after('affected_establishments_count');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'details_of_investigation')) {
                $table->longText('details_of_investigation')->nullable()->after('fatality_details');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'disposition')) {
                $table->longText('disposition')->nullable()->after('details_of_investigation');
            }

            if (! Schema::hasColumn('spot_investigation_report', 'noted_by_station_officer_id')) {
                $table->foreignId('noted_by_station_officer_id')
                    ->nullable()
                    ->after('disposition')
                    ->constrained('station_officers', 'station_officer_id')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('spot_investigation_report', 'approved_by_station_officer_id')) {
                $table->foreignId('approved_by_station_officer_id')
                    ->nullable()
                    ->after('noted_by_station_officer_id')
                    ->constrained('station_officers', 'station_officer_id')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            foreach ([
                'approved_by_station_officer_id',
                'noted_by_station_officer_id',
            ] as $foreignColumn) {
                if (Schema::hasColumn('spot_investigation_report', $foreignColumn)) {
                    $table->dropForeign([$foreignColumn]);
                }
            }

            foreach ([
                'approved_by_station_officer_id',
                'noted_by_station_officer_id',
                'disposition',
                'details_of_investigation',
                'fatality_details',
                'affected_establishments_count',
                'highest_alarm_level',
                'occupant',
                'establishment_name',
                'involved_structure',
                'place_of_occurrence',
                'memorandum_for',
                'report_completed_at',
            ] as $column) {
                if (Schema::hasColumn('spot_investigation_report', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
