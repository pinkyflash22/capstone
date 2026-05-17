<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boundary_location', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('geojson');
            $table -> foreignId('station_id')->constrained('stations','station_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::dropIfExists('boundary_location');
    }
};
