<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('route_source', 20)->default('real')->after('status');
            $table->decimal('origin_lat', 10, 8)->nullable()->after('destination_lng');
            $table->decimal('origin_lng', 11, 8)->nullable()->after('origin_lat');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['route_source', 'origin_lat', 'origin_lng']);
        });
    }
};
