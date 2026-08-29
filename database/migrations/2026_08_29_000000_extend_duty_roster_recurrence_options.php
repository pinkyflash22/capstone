<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duty_roster_templates', function (Blueprint $table) {
            $table->string('recurrence_type', 20)->default('weekly')->after('weekday');
            $table->json('weekdays')->nullable()->after('recurrence_type');
        });
    }

    public function down(): void
    {
        Schema::table('duty_roster_templates', function (Blueprint $table) {
            $table->dropColumn(['recurrence_type', 'weekdays']);
        });
    }
};
