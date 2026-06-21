<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_media', function (Blueprint $table) {
            $table->id('station_media_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->string('path');
            $table->enum('kind', ['profile', 'gallery'])->default('gallery');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by_user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_media');
    }
};
