<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'risk_type')) {
                $table->string('risk_type', 100)->nullable()->after('station_id');
            }

            if (! Schema::hasColumn('risk_areas', 'street_or_sitio')) {
                $table->string('street_or_sitio')->nullable()->after('risk_type');
            }

            if (! Schema::hasColumn('risk_areas', 'population_density_level')) {
                $table->enum('population_density_level', ['low', 'moderate', 'high'])->nullable()->after('street_or_sitio');
            }

            if (! Schema::hasColumn('risk_areas', 'building_density_level')) {
                $table->enum('building_density_level', ['low', 'moderate', 'high'])->nullable()->after('population_density_level');
            }

            if (! Schema::hasColumn('risk_areas', 'road_accessibility')) {
                $table->enum('road_accessibility', ['easy', 'moderate', 'difficult', 'very_difficult'])->nullable()->after('building_density_level');
            }

            if (! Schema::hasColumn('risk_areas', 'distance_to_hydrant')) {
                $table->decimal('distance_to_hydrant', 8, 2)->nullable()->after('road_accessibility');
            }

            if (! Schema::hasColumn('risk_areas', 'distance_to_water_source')) {
                $table->decimal('distance_to_water_source', 8, 2)->nullable()->after('distance_to_hydrant');
            }

            if (! Schema::hasColumn('risk_areas', 'nearby_establishments')) {
                $table->text('nearby_establishments')->nullable()->after('distance_to_water_source');
            }

            if (! Schema::hasColumn('risk_areas', 'estimated_affected_households')) {
                $table->unsignedInteger('estimated_affected_households')->nullable()->after('nearby_establishments');
            }

            if (! Schema::hasColumn('risk_areas', 'assessment_level')) {
                $table->enum('assessment_level', ['low', 'moderate', 'high'])->nullable()->after('estimated_affected_households');
            }

            if (! Schema::hasColumn('risk_areas', 'assessment_description')) {
                $table->text('assessment_description')->nullable()->after('assessment_level');
            }

            if (! Schema::hasColumn('risk_areas', 'remarks')) {
                $table->text('remarks')->nullable()->after('assessment_description');
            }

            if (! Schema::hasColumn('risk_areas', 'blocked_hydrant')) {
                $table->boolean('blocked_hydrant')->default(false)->after('remarks');
            }

            if (! Schema::hasColumn('risk_areas', 'informal_settlement')) {
                $table->boolean('informal_settlement')->default(false)->after('blocked_hydrant');
            }

            if (! Schema::hasColumn('risk_areas', 'cooking_fire_prone_activity')) {
                $table->boolean('cooking_fire_prone_activity')->default(false)->after('informal_settlement');
            }

            if (! Schema::hasColumn('risk_areas', 'combustible_materials_present')) {
                $table->boolean('combustible_materials_present')->default(false)->after('cooking_fire_prone_activity');
            }

            if (! Schema::hasColumn('risk_areas', 'poor_waste_disposal')) {
                $table->boolean('poor_waste_disposal')->default(false)->after('combustible_materials_present');
            }

            if (! Schema::hasColumn('risk_areas', 'previous_fire_prone_location')) {
                $table->boolean('previous_fire_prone_location')->default(false)->after('poor_waste_disposal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            $columns = [
                'risk_type',
                'street_or_sitio',
                'population_density_level',
                'building_density_level',
                'road_accessibility',
                'distance_to_hydrant',
                'distance_to_water_source',
                'nearby_establishments',
                'estimated_affected_households',
                'assessment_level',
                'assessment_description',
                'remarks',
                'blocked_hydrant',
                'informal_settlement',
                'cooking_fire_prone_activity',
                'combustible_materials_present',
                'poor_waste_disposal',
                'previous_fire_prone_location',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('risk_areas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
