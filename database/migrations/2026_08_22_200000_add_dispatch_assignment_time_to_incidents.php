<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
                $table->timestamp('dispatch_assigned_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
                $table->dropColumn('dispatch_assigned_at');
            }
        });
    }
};
