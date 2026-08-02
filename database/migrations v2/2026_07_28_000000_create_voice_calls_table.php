<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_calls', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name')->unique();
            $table->foreignId('caller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('station_id')->nullable()->constrained('stations', 'station_id')->nullOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('incidents', 'incident_id')->nullOnDelete();
            $table->string('status', 24)->default('ringing');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'station_id']);
            $table->index(['caller_id', 'status']);
            $table->index(['assigned_admin_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_calls');
    }
};
