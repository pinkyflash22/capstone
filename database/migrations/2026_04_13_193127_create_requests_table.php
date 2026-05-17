<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🔹 FSIC REQUESTS
        Schema::create('fsic_requests', function (Blueprint $table) {
            $table->id();

            // ✅ assumes users table uses default 'id'
            $table->foreignId('user_id')
                ->constrained('users','user_id')
                ->cascadeOnDelete();

            $table->string('request_code')->unique();
            $table->date('date_requested');

            $table->string('business_name');
            $table->string('owner_name');
            $table->string('business_type')->nullable();
            $table->string('building_address');
            $table->string('barangay');

            $table->string('dti_sec_registration_no')->nullable();
            $table->string('mayors_permit_no')->nullable();
            $table->string('tin_number')->nullable();

            $table->enum('status', [
                'pending',
                'for_inspection',
                'inspected',
                'approved',
                'rejected'
            ])->default('pending');

            $table->timestamps();
        });

        // 🔹 FSIC INSPECTIONS
        Schema::create('fsic_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fsic_request_id')
                ->constrained('fsic_requests')
                ->cascadeOnDelete();

            // ✅ FIXED (custom PK)
            $table->unsignedBigInteger('station_officer_id');
            $table->foreign('station_officer_id')
                ->references('station_officer_id')
                ->on('station_officers')
                ->cascadeOnDelete();

            $table->date('inspection_date')->nullable();
            $table->text('inspection_remarks')->nullable();
            $table->boolean('is_compliant')->nullable();

            $table->timestamps();
        });

        // 🔹 FSIC APPROVALS
        Schema::create('fsic_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fsic_request_id')
                ->constrained('fsic_requests')
                ->cascadeOnDelete();

            // ✅ FIXED (custom PK)
            $table->unsignedBigInteger('approved_by');
            $table->foreign('approved_by')
                ->references('station_officer_id')
                ->on('station_officers')
                ->cascadeOnDelete();

            $table->boolean('is_approved')->nullable();
            $table->text('approval_remarks')->nullable();

            $table->string('fsic_number')->nullable();
            $table->date('date_issued')->nullable();
            $table->date('valid_until')->nullable();

            $table->timestamps();
        });

        // 🔹 INSPECTION CHECKLIST (FLATTENED)
        Schema::create('inspection_checklist', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fsic_inspection_id')
                ->constrained('fsic_inspections')
                ->cascadeOnDelete();

            $table->boolean('fire_protection')->nullable();
            $table->boolean('alarm_systems')->nullable();
            $table->boolean('standard_material_used')->nullable();
            $table->boolean('electrical')->nullable();
            $table->boolean('housekeeping')->nullable();
            $table->boolean('hazards')->nullable();
            $table->boolean('planning')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // ✅ CORRECT DROP ORDER (IMPORTANT)
        Schema::dropIfExists('inspection_checklist');
        Schema::dropIfExists('fsic_approvals');
        Schema::dropIfExists('fsic_inspections');
        Schema::dropIfExists('fsic_requests');
    }
};
