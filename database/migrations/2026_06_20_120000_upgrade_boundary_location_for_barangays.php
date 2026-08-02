<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boundary_location', function (Blueprint $table) {
            if (! Schema::hasColumn('boundary_location', 'barangay_id')) {
                $table->foreignId('barangay_id')
                    ->nullable()
                    ->after('station_id')
                    ->constrained('barangays', 'barangay_id')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('boundary_location', 'boundary_type')) {
                $table->string('boundary_type', 50)
                    ->default('custom')
                    ->after('barangay_id');
            }

            if (! Schema::hasColumn('boundary_location', 'source')) {
                $table->string('source', 50)
                    ->default('manual')
                    ->after('boundary_type');
            }

            if (! Schema::hasColumn('boundary_location', 'external_place_id')) {
                $table->string('external_place_id')->nullable()->after('source');
            }

            if (! Schema::hasColumn('boundary_location', 'properties')) {
                $table->json('properties')->nullable()->after('external_place_id');
            }
        });

        if (Schema::hasColumn('boundary_location', 'station_id')) {
            Schema::table('boundary_location', function (Blueprint $table) {
                $table->unsignedBigInteger('station_id')->nullable()->change();
            });
        }

        DB::table('boundary_location')
            ->whereNull('boundary_type')
            ->update(['boundary_type' => 'custom']);

        DB::table('boundary_location')
            ->whereNull('source')
            ->update(['source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('boundary_location', function (Blueprint $table) {
            if (Schema::hasColumn('boundary_location', 'barangay_id')) {
                $table->dropConstrainedForeignId('barangay_id');
            }

            foreach (['properties', 'external_place_id', 'source', 'boundary_type'] as $column) {
                if (Schema::hasColumn('boundary_location', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
