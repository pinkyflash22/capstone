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
        Schema::table('barangay_halls', function (Blueprint $table) {
            if (! Schema::hasColumn('barangay_halls', 'marker_id')) {
                $table->foreignId('marker_id')
                    ->nullable()
                    ->after('barangay_id')
                    ->constrained('markers', 'marker_id')
                    ->nullOnDelete();
            }
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangay_halls', function (Blueprint $table) {
            if (Schema::hasColumn('barangay_halls', 'marker_id')) {
                $table->dropConstrainedForeignId('marker_id');
            }
        });

       
    }
};
