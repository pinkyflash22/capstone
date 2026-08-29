<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duty_roster_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('rotation_interval_days')->nullable()->after('weekdays');
            $table->uuid('rotation_group')->nullable()->index()->after('rotation_interval_days');
        });
    }

    public function down(): void
    {
        Schema::table('duty_roster_templates', function (Blueprint $table) {
            $table->dropIndex(['rotation_group']);
            $table->dropColumn(['rotation_interval_days', 'rotation_group']);
        });
    }
};
