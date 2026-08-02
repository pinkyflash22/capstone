<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('affected_establishments_count');
        });
    }

    public function down(): void
    {
        Schema::table('spot_investigation_report', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
