<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_logs')) {
            return;
        }

        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('actions', 50);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('actions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};
