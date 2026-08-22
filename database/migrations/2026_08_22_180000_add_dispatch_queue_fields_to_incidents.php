<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'dispatch_priority')) {
                $table->string('dispatch_priority', 20)->default('medium')->index();
            }
            if (! Schema::hasColumn('incidents', 'duplicate_of_incident_id')) {
                $table->unsignedBigInteger('duplicate_of_incident_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'duplicate_of_incident_id')) {
                $table->dropColumn('duplicate_of_incident_id');
            }
            if (Schema::hasColumn('incidents', 'dispatch_priority')) {
                $table->dropColumn('dispatch_priority');
            }
        });
    }
};
