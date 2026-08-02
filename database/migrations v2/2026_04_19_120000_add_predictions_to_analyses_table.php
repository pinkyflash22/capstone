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
        Schema::table('analysis', function (Blueprint $table) {
            if (!Schema::hasColumn('analysis', 'predictions')) {
                $table->json('predictions')->nullable()->after('recommendations');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analysis', function (Blueprint $table) {
            if (Schema::hasColumn('analysis', 'predictions')) {
                $table->dropColumn('predictions');
            }
        });
    }
};
