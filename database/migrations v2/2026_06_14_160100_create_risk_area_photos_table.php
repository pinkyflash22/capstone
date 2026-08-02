<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_area_photos', function (Blueprint $table) {
            $table->id('risk_area_photo_id');
            $table->foreignId('risk_area_id')
                ->constrained('risk_areas', 'risk_area_id')
                ->cascadeOnDelete();
            $table->string('photo_path');
            $table->string('photo_name')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_area_photos');
    }
};
