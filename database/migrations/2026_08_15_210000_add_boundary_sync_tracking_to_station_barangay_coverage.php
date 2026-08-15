<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_barangay_coverage', function (Blueprint $table) {
            $table->string('boundary_sync_status', 30)->nullable()->after('is_active');
            $table->timestamp('boundary_sync_attempted_at')->nullable()->after('boundary_sync_status');
            $table->text('boundary_sync_error')->nullable()->after('boundary_sync_attempted_at');
            $table->index(['boundary_sync_status', 'boundary_sync_attempted_at'], 'station_coverage_boundary_sync_idx');
        });
    }

    public function down(): void
    {
        Schema::table('station_barangay_coverage', function (Blueprint $table) {
            $table->dropIndex('station_coverage_boundary_sync_idx');
            $table->dropColumn(['boundary_sync_status', 'boundary_sync_attempted_at', 'boundary_sync_error']);
        });
    }
};
