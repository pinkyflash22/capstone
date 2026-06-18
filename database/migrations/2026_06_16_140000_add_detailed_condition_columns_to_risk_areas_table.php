<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'hazard_category')) {
                $table->string('hazard_category', 120)->nullable()->after('entry_type');
            }

            if (! Schema::hasColumn('risk_areas', 'condition_code')) {
                $table->string('condition_code', 120)->nullable()->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            foreach (['condition_code', 'hazard_category'] as $column) {
                if (Schema::hasColumn('risk_areas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
