<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'entry_type')) {
                $table->enum('entry_type', ['hazard', 'risk'])
                    ->default('risk')
                    ->after('station_id');
            }

            if (! Schema::hasColumn('risk_areas', 'condition_label')) {
                $table->string('condition_label', 150)
                    ->nullable()
                    ->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            foreach (['condition_label', 'entry_type'] as $column) {
                if (Schema::hasColumn('risk_areas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
