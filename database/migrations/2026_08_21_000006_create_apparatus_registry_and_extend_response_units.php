<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparatus', function (Blueprint $table) {
            $table->id('apparatus_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipments', 'equipment_id')->nullOnDelete();
            $table->string('unit_number', 50);
            $table->string('plate_number', 30)->nullable()->unique();
            $table->string('apparatus_type', 50)->default('fire_engine');
            $table->unsignedInteger('water_capacity_liters')->nullable();
            $table->string('status', 30)->default('available');
            $table->string('maintenance_status', 30)->default('operational');
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_due_at')->nullable();
            $table->string('gps_status', 20)->default('offline');
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('gps_last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['station_id', 'unit_number']);
            $table->index(['station_id', 'status', 'maintenance_status']);
        });

        Schema::table('incident_response_units', function (Blueprint $table) {
            $table->foreignId('apparatus_id')->nullable()->after('equipment_station_id')->constrained('apparatus', 'apparatus_id')->nullOnDelete();
            $table->timestamp('estimated_arrival_at')->nullable()->after('distance_meters');
            $table->timestamp('recalled_at')->nullable()->after('released_at');
            $table->foreignId('recalled_by')->nullable()->after('recalled_at')->constrained('users', 'user_id')->nullOnDelete();
            $table->string('recall_reason', 255)->nullable()->after('recalled_by');
        });
    }

    public function down(): void
    {
        Schema::table('incident_response_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recalled_by');
            $table->dropColumn(['recall_reason', 'recalled_at', 'estimated_arrival_at']);
            $table->dropConstrainedForeignId('apparatus_id');
        });
        Schema::dropIfExists('apparatus');
    }
};
