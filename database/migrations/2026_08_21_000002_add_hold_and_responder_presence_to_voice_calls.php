<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('voice_calls', 'held_at')) {
                $table->timestamp('held_at')->nullable()->after('answered_at');
            }
            if (! Schema::hasColumn('voice_calls', 'held_by')) {
                $table->foreignId('held_by')->nullable()->after('held_at')
                    ->constrained('users', 'user_id')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('voice_responder_presence')) {
            Schema::create('voice_responder_presence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
                $table->unsignedSmallInteger('active_call_count')->default(0);
                $table->timestamp('last_seen_at')->index();
                $table->timestamps();
                $table->index(['station_id', 'last_seen_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_responder_presence');
        Schema::table('voice_calls', function (Blueprint $table) {
            if (Schema::hasColumn('voice_calls', 'held_by')) {
                $table->dropConstrainedForeignId('held_by');
            }
            if (Schema::hasColumn('voice_calls', 'held_at')) {
                $table->dropColumn('held_at');
            }
        });
    }
};
