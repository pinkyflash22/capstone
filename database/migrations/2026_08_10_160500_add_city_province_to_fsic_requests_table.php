<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsic_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('fsic_requests', 'city')) {
                $table->string('city')->nullable()->after('barangay');
            }

            if (! Schema::hasColumn('fsic_requests', 'province')) {
                $table->string('province')->nullable()->after('city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fsic_requests', function (Blueprint $table) {
            if (Schema::hasColumn('fsic_requests', 'province')) {
                $table->dropColumn('province');
            }

            if (Schema::hasColumn('fsic_requests', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
};
