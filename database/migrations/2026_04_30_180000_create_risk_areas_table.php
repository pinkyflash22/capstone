<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_areas', function (Blueprint $table) {
            $table->id('risk_area_id');

            $table->foreignId('marker_id');
            $table->foreign('marker_id', 'risk_areas_marker_fk')
                ->references('marker_id')
                ->on('markers')
                ->cascadeOnDelete();

            $table->foreignId('barangay_hall_id');
            $table->foreign('barangay_hall_id', 'risk_areas_hall_fk')
                ->references('barangay_hall_id')
                ->on('barangay_halls')
                ->cascadeOnDelete();

            $table->foreignId('station_id')->nullable();
            $table->foreign('station_id', 'risk_areas_station_fk')
                ->references('station_id')
                ->on('stations')
                ->nullOnDelete();

            $table->enum('risk_factor', [
                'people_density',
                'housing_material',
                'housing_spacing',
                'electrical_risk',
                'wiring',
            ]);

            $table->enum('mode', [
                'low',
                'medium',
                'high',
                'concrete',
                'mixed',
                'light_materials',
                'dikit_dikit',
                'nearly_dikit_dikit',
                'moderate',
                'far',
                'none',
                'octopus',
                'straight',
                'overflow',
            ]);

            $table->enum('hazard_type', [
                'overcrowding',
                'electrical',
                'flammable',
                'none',
            ])->default('none');

            $table->decimal('risk_score', 5, 2)->nullable();
            $table->decimal('estimated_radius', 8, 2)->nullable();
            $table->boolean('near_hydrant')->default(false);
            $table->boolean('near_water_source')->default(false);
            $table->enum('status', ['pending', 'verified', 'resolved'])->default('pending');

            $table->foreignId('patrolled_by')->nullable();
            $table->foreign('patrolled_by', 'risk_areas_patrolled_by_fk')
                ->references('barangay_officer_id')
                ->on('barangay_officers')
                ->nullOnDelete();

            $table->foreignId('reported_by')->nullable();
            $table->foreign('reported_by', 'risk_areas_reported_by_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['barangay_hall_id', 'status'], 'risk_areas_hall_status_idx');
            $table->index(['risk_factor', 'mode'], 'risk_areas_factor_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_areas');
    }
};
