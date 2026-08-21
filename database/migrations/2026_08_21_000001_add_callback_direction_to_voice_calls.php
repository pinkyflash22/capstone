<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        Schema::table('voice_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('voice_calls', 'direction')) {
                $table->string('direction', 16)->default('inbound')->after('channel_name')->index();
            }
            if (! Schema::hasColumn('voice_calls', 'initiated_by')) {
                $table->foreignId('initiated_by')->nullable()->after('caller_id')
                    ->constrained('users', 'user_id')->nullOnDelete();
            }
            if (! Schema::hasColumn('voice_calls', 'callback_of')) {
                $table->foreignId('callback_of')->nullable()->after('initiated_by')
                    ->constrained('voice_calls')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        Schema::table('voice_calls', function (Blueprint $table) {
            if (Schema::hasColumn('voice_calls', 'callback_of')) {
                $table->dropConstrainedForeignId('callback_of');
            }
            if (Schema::hasColumn('voice_calls', 'initiated_by')) {
                $table->dropConstrainedForeignId('initiated_by');
            }
            if (Schema::hasColumn('voice_calls', 'direction')) {
                $table->dropColumn('direction');
            }
        });
    }
};
