<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('incidents', 'incident_completed_at')) {
                $table->timestamp('incident_completed_at')->nullable()->after('occurred_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            foreach (['incident_completed_at', 'occurred_at'] as $column) {
                if (Schema::hasColumn('incidents', $column)) $table->dropColumn($column);
            }
        });
    }
};
