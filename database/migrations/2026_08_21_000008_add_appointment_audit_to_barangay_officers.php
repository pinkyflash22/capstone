<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangay_officers', function (Blueprint $table) {
            $table->foreignId('previous_role_id')->nullable()->after('user_id')->constrained('roles', 'role_id')->nullOnDelete();
            $table->foreignId('appointed_by')->nullable()->after('status')->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('barangay_officers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointed_by');
            $table->dropConstrainedForeignId('previous_role_id');
        });
    }
};
