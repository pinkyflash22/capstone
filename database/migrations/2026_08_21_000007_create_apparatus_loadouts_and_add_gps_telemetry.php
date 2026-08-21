<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparatus_equipment', function (Blueprint $table) {
            $table->id('apparatus_equipment_id');
            $table->foreignId('apparatus_id')->constrained('apparatus', 'apparatus_id')->cascadeOnDelete();
            $table->foreignId('equipment_station_id')->constrained('equipments_to_stations', 'equipment_station_id')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['apparatus_id', 'equipment_station_id']);
        });

        Schema::table('apparatus', function (Blueprint $table) {
            $table->decimal('gps_accuracy_meters', 8, 2)->nullable()->after('last_lng');
            $table->decimal('gps_speed_kph', 8, 2)->nullable()->after('gps_accuracy_meters');
            $table->decimal('gps_heading', 6, 2)->nullable()->after('gps_speed_kph');
        });
    }

    public function down(): void
    {
        Schema::table('apparatus', function (Blueprint $table) {
            $table->dropColumn(['gps_accuracy_meters', 'gps_speed_kph', 'gps_heading']);
        });
        Schema::dropIfExists('apparatus_equipment');
    }
};
