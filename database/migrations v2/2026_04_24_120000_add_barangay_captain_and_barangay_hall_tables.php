<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('barangay_halls', function (Blueprint $table) {
            $table->id('barangay_hall_id');
            $table->foreignId('barangay_id')
                ->unique()
                ->constrained('barangays', 'barangay_id')
                ->cascadeOnDelete();
            $table->foreignId('marker_id')
                ->nullable()
                ->constrained('markers', 'marker_id')
                ->nullOnDelete();
            $table->string('hall_name');
            $table->foreignId('address_id')
                ->nullable()
                ->constrained('full_addresses', 'address_id')
                ->nullOnDelete();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('captain_user_id')
                ->nullable()
                ->constrained('barangay_officers', 'barangay_officer_id')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('barangay_fire_records', function (Blueprint $table) {
            $table->id('barangay_fire_record_id');
            $table->foreignId('barangay_hall_id')
                ->constrained('barangay_halls', 'barangay_hall_id')
                ->cascadeOnDelete();
            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents', 'incident_id')
                ->nullOnDelete();
            $table->foreignId('reported_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->date('incident_date');
            $table->unsignedInteger('incident_count')->default(1);
            $table->enum('fire_classification', ['accidental', 'intentional', 'undetermined']);
            $table->string('fire_cause')->nullable();
            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('wind_speed')->nullable();
            $table->string('wind_direction')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['barangay_hall_id', 'incident_date'], 'barangay_fire_records_hall_date_idx');
        });

        if (! DB::table('roles')->where('role', 'barangay captain')->exists()) {
            DB::table('roles')->insert([
                'role' => 'barangay captain',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('role', 'barangay captain')->delete();

        Schema::dropIfExists('barangay_fire_records');
        Schema::dropIfExists('barangay_halls');
        Schema::dropIfExists('barangay_officers');
    }
};
