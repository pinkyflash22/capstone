<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incidents')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'barangay_id')) {
                $table->foreignId('barangay_id')
                    ->nullable()
                    ->after('reported_by')
                    ->constrained('barangays', 'barangay_id')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('incidents', 'barangay_resolution_status')) {
                $table->string('barangay_resolution_status', 30)
                    ->default('pending')
                    ->after('barangay_id');
            }

            if (! Schema::hasColumn('incidents', 'barangay_resolution_source')) {
                $table->string('barangay_resolution_source', 50)
                    ->nullable()
                    ->after('barangay_resolution_status');
            }

            if (! Schema::hasColumn('incidents', 'barangay_resolved_at')) {
                $table->timestamp('barangay_resolved_at')
                    ->nullable()
                    ->after('barangay_resolution_source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incidents')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'barangay_id')) {
                $table->dropConstrainedForeignId('barangay_id');
            }

            foreach (['barangay_resolved_at', 'barangay_resolution_source', 'barangay_resolution_status'] as $column) {
                if (Schema::hasColumn('incidents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
