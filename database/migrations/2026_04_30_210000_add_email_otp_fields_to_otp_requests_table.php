<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('otp_code', 6)->nullable()->after('request_id');
            $table->index(['email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->dropIndex(['email', 'created_at']);
            $table->dropColumn(['email', 'otp_code']);
        });
    }
};
