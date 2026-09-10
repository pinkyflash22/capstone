<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_media', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'video']);
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('media_path', 2048);
            $table->string('poster_path', 2048)->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_media');
    }
};
