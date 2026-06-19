<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitigation_action_attachments', function (Blueprint $table) {
            $table->id('mitigation_action_attachment_id');
            $table->foreignId('mitigation_action_id')->constrained('mitigation_actions', 'mitigation_action_id')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('attachment_type', 40)->default('proof');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitigation_action_attachments');
    }
};
