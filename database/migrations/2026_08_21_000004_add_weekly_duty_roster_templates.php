<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_roster_templates', function (Blueprint $table) {
            $table->id('duty_roster_template_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams', 'team_id')->cascadeOnDelete();
            $table->string('label', 100);
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_time');
            $table->time('ends_time');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['station_id', 'status', 'effective_from', 'effective_until'], 'duty_template_station_window_idx');
        });

        Schema::create('duty_roster_template_assignments', function (Blueprint $table) {
            $table->id('duty_roster_template_assignment_id');
            $table->foreignId('duty_roster_template_id')->constrained('duty_roster_templates', 'duty_roster_template_id')->cascadeOnDelete();
            $table->foreignId('station_officer_id')->constrained('station_officers', 'station_officer_id')->cascadeOnDelete();
            $table->string('team_role', 40);
            $table->timestamps();
            $table->unique(['duty_roster_template_id', 'station_officer_id'], 'duty_template_officer_unique');
        });

        Schema::table('duty_shifts', function (Blueprint $table) {
            $table->foreignId('duty_roster_template_id')->nullable()->after('team_id')
                ->constrained('duty_roster_templates', 'duty_roster_template_id')->nullOnDelete();
            $table->date('occurrence_date')->nullable()->after('duty_roster_template_id');
            $table->boolean('is_override')->default(false)->after('occurrence_date');
            $table->unique(['duty_roster_template_id', 'occurrence_date'], 'duty_shift_template_occurrence_unique');
        });
    }

    public function down(): void
    {
        Schema::table('duty_shifts', function (Blueprint $table) {
            $table->dropUnique('duty_shift_template_occurrence_unique');
            $table->dropConstrainedForeignId('duty_roster_template_id');
            $table->dropColumn(['occurrence_date', 'is_override']);
        });
        Schema::dropIfExists('duty_roster_template_assignments');
        Schema::dropIfExists('duty_roster_templates');
    }
};
