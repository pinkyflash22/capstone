<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_shifts', function (Blueprint $table) {
            $table->id('duty_shift_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams', 'team_id')->cascadeOnDelete();
            $table->string('label', 100);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('status', 20)->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['station_id', 'starts_at', 'ends_at']);
        });

        Schema::create('duty_shift_assignments', function (Blueprint $table) {
            $table->id('duty_shift_assignment_id');
            $table->foreignId('duty_shift_id')->constrained('duty_shifts', 'duty_shift_id')->cascadeOnDelete();
            $table->foreignId('station_officer_id')->constrained('station_officers', 'station_officer_id')->cascadeOnDelete();
            $table->string('team_role', 40);
            $table->timestamps();
            $table->unique(['duty_shift_id', 'station_officer_id'], 'duty_shift_officer_unique');
            $table->index(['duty_shift_id', 'team_role']);
        });

        Schema::create('duty_exceptions', function (Blueprint $table) {
            $table->id('duty_exception_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('station_officer_id')->constrained('station_officers', 'station_officer_id')->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
            $table->index(['station_officer_id', 'starts_at', 'ends_at'], 'duty_exception_officer_window_idx');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('roster_managed')->default(false)->after('status');
            $table->foreignId('active_duty_shift_id')->nullable()->after('roster_managed')
                ->constrained('duty_shifts', 'duty_shift_id')->nullOnDelete();
            $table->foreignId('pending_duty_shift_id')->nullable()->after('active_duty_shift_id')
                ->constrained('duty_shifts', 'duty_shift_id')->nullOnDelete();
            $table->timestamp('roster_synced_at')->nullable()->after('pending_duty_shift_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_duty_shift_id');
            $table->dropConstrainedForeignId('active_duty_shift_id');
            $table->dropColumn(['roster_managed', 'roster_synced_at']);
        });
        Schema::dropIfExists('duty_exceptions');
        Schema::dropIfExists('duty_shift_assignments');
        Schema::dropIfExists('duty_shifts');
    }
};
