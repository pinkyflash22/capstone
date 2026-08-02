<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('risk_areas')) {
            return;
        }

        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'estimated_radius')) {
                $table->decimal('estimated_radius', 8, 2)->nullable()->after('risk_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('risk_areas')) {
            return;
        }

        Schema::table('risk_areas', function (Blueprint $table) {
            if (Schema::hasColumn('risk_areas', 'estimated_radius')) {
                $table->dropColumn('estimated_radius');
            }
        });
    }
};
