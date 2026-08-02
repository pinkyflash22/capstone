<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create otp_requests table
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->id();

            // 🔗 Relationship to users
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->cascadeOnDelete();

            // 📱 Phone number used
            $table->string('phone');

            // 🔑 Movider request ID
            $table->string('request_id')->unique();

            // 📊 Status tracking
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // ⏱ Expiration - Use dateTime instead of timestamp to avoid MySQL strict mode issues
            $table->dateTime('expires_at'); // Changed from timestamp to dateTime

            // 🔒 Optional security tracking
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // ⚡ Index for faster lookup
            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
