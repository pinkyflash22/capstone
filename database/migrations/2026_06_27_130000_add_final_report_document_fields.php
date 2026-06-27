<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_investigation_report', function (Blueprint $table) {
            $table->dateTime('report_completed_at')->nullable()->after('station_officer_id');
            $table->text('office_address')->nullable()->after('exact_address');
            $table->dateTime('alarm_at')->nullable()->after('office_address');
            $table->string('establishment_burned')->nullable()->after('alarm_at');
            $table->text('fire_victims')->nullable()->after('establishment_burned');
            $table->text('origin_of_fire')->nullable()->after('fire_victims');
            $table->text('cause_of_fire')->nullable()->after('origin_of_fire');
            $table->json('substantiating_documents')->nullable()->after('cause_of_fire');
            $table->longText('facts_of_case')->nullable()->after('substantiating_documents');
            $table->longText('discussion')->nullable()->after('facts_of_case');
            $table->longText('findings')->nullable()->after('discussion');
            $table->longText('recommendation')->nullable()->after('findings');
            $table->foreignId('noted_by_station_officer_id')->nullable()->after('recommendation');
            $table->foreignId('approved_by_station_officer_id')->nullable()->after('noted_by_station_officer_id');
            $table->foreign('noted_by_station_officer_id', 'fir_noted_by_fk')
                ->references('station_officer_id')
                ->on('station_officers')
                ->nullOnDelete();
            $table->foreign('approved_by_station_officer_id', 'fir_approved_by_fk')
                ->references('station_officer_id')
                ->on('station_officers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('final_investigation_report', function (Blueprint $table) {
            $table->dropForeign('fir_noted_by_fk');
            $table->dropForeign('fir_approved_by_fk');
            $table->dropColumn([
                'report_completed_at',
                'office_address',
                'alarm_at',
                'establishment_burned',
                'fire_victims',
                'origin_of_fire',
                'cause_of_fire',
                'substantiating_documents',
                'facts_of_case',
                'discussion',
                'findings',
                'recommendation',
                'noted_by_station_officer_id',
                'approved_by_station_officer_id',
            ]);
        });
    }
};
