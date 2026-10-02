<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markers', function (Blueprint $table) {
            if (! Schema::hasColumn('markers', 'spammed_by')) {
                $table->foreignId('spammed_by')
                    ->nullable()
                    ->after('image')
                    ->constrained('users', 'user_id')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('markers', 'spammed_at')) {
                $table->timestamp('spammed_at')->nullable()->after('spammed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('markers', function (Blueprint $table) {
            if (Schema::hasColumn('markers', 'spammed_by')) {
                $table->dropConstrainedForeignId('spammed_by');
            }

            if (Schema::hasColumn('markers', 'spammed_at')) {
                $table->dropColumn('spammed_at');
            }
        });
    }
};
