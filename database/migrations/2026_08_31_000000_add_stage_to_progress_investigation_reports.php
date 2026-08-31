<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_investigation_report', function (Blueprint $table) {
            $table->dropUnique('progress_investigation_report_spot_investigation_id_unique');
        });

        Schema::table('progress_investigation_report', function (Blueprint $table) {
            $table->string('report_stage', 16)->default('single')->after('spot_investigation_id');
            $table->foreignId('parent_progress_investigation_id')
                ->nullable()
                ->after('report_stage')
                ->constrained('progress_investigation_report', 'progress_investigation_report_id')
                ->restrictOnDelete();
            $table->unique(
                ['spot_investigation_id', 'report_stage'],
                'progress_report_spot_stage_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('progress_investigation_report', function (Blueprint $table) {
            $table->dropForeign(['parent_progress_investigation_id']);
            $table->dropUnique('progress_report_spot_stage_unique');
            $table->dropColumn(['report_stage', 'parent_progress_investigation_id']);
            $table->unique('spot_investigation_id');
        });
    }
};
