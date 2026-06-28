<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('final_investigation_report') && ! Schema::hasColumn('final_investigation_report', 'memorandum_for')) {
            Schema::table('final_investigation_report', function (Blueprint $table) {
                $table->string('memorandum_for')->nullable()->after('report_completed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('final_investigation_report') && Schema::hasColumn('final_investigation_report', 'memorandum_for')) {
            Schema::table('final_investigation_report', function (Blueprint $table) {
                $table->dropColumn('memorandum_for');
            });
        }
    }
};
