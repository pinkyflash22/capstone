<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_logs')) {
            return;
        }

        if (Schema::hasColumn('user_logs', 'actions') && ! Schema::hasColumn('user_logs', 'action')) {
            Schema::table('user_logs', function (Blueprint $table) {
                $table->renameColumn('actions', 'action');
            });
        }

        Schema::table('user_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('user_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('action');
            }

            if (! Schema::hasColumn('user_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_logs')) {
            return;
        }

        Schema::table('user_logs', function (Blueprint $table) {
            if (Schema::hasColumn('user_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }

            if (Schema::hasColumn('user_logs', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
        });

        if (Schema::hasColumn('user_logs', 'action') && ! Schema::hasColumn('user_logs', 'actions')) {
            Schema::table('user_logs', function (Blueprint $table) {
                $table->renameColumn('action', 'actions');
            });
        }
    }
};
