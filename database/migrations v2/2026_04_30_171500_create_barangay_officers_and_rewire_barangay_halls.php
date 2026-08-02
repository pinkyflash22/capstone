<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barangay_officers')) {
            return;
        }

        Schema::create('barangay_officers', function (Blueprint $table) {
            $table->id('barangay_officer_id');
            $table->foreignId('barangay_id')
                ->constrained('barangays', 'barangay_id')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->string('position')->default('officer');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['barangay_id', 'user_id'], 'barangay_officers_barangay_user_unique');
        });

        $existingCaptainRows = DB::table('barangay_halls')
            ->select('barangay_hall_id', 'barangay_id', 'captain_user_id')
            ->whereNotNull('captain_user_id')
            ->get();

        $hallCaptainMap = [];

        foreach ($existingCaptainRows as $row) {
            $existingOfficerId = DB::table('barangay_officers')
                ->where('barangay_id', $row->barangay_id)
                ->where('user_id', $row->captain_user_id)
                ->value('barangay_officer_id');

            if (! $existingOfficerId) {
                $existingOfficerId = DB::table('barangay_officers')->insertGetId([
                    'barangay_id' => $row->barangay_id,
                    'user_id' => $row->captain_user_id,
                    'position' => 'captain',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'barangay_officer_id');
            }

            $hallCaptainMap[$row->barangay_hall_id] = $existingOfficerId;
        }

        Schema::table('barangay_halls', function (Blueprint $table) {
            $table->dropForeign(['captain_user_id']);
        });

        foreach ($hallCaptainMap as $barangayHallId => $barangayOfficerId) {
            DB::table('barangay_halls')
                ->where('barangay_hall_id', $barangayHallId)
                ->update(['captain_user_id' => $barangayOfficerId]);
        }

        Schema::table('barangay_halls', function (Blueprint $table) {
            $table->foreign('captain_user_id', 'barangay_halls_captain_officer_fk')
                ->references('barangay_officer_id')
                ->on('barangay_officers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('barangay_officers')) {
            return;
        }

        $officerUserMap = DB::table('barangay_officers')
            ->select('barangay_officer_id', 'user_id')
            ->get()
            ->keyBy('barangay_officer_id');

        Schema::table('barangay_halls', function (Blueprint $table) {
            $table->dropForeign('barangay_halls_captain_officer_fk');
        });

        $hallRows = DB::table('barangay_halls')
            ->select('barangay_hall_id', 'captain_user_id')
            ->whereNotNull('captain_user_id')
            ->get();

        foreach ($hallRows as $row) {
            $userId = optional($officerUserMap->get($row->captain_user_id))->user_id;

            DB::table('barangay_halls')
                ->where('barangay_hall_id', $row->barangay_hall_id)
                ->update(['captain_user_id' => $userId]);
        }

        Schema::table('barangay_halls', function (Blueprint $table) {
            $table->foreign('captain_user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::dropIfExists('barangay_officers');
    }
};
