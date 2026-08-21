<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_report_images', function (Blueprint $table) {
            $table->id('investigation_report_image_id');
            $table->string('report_type', 20);
            $table->unsignedBigInteger('report_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['report_type', 'report_id'], 'investigation_report_images_report_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_report_images');
    }
};
