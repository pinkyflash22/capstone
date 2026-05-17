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
         Schema::create('provinces', function (Blueprint $table) {
            $table->id('province_id');
            $table -> string('province_name')->unique();
            $table->timestamps();
        });

         Schema::create('cities', function (Blueprint $table) {
            $table->id('city_id');
            $table -> string('city_name');
            $table -> foreignId('province_id')->constrained('provinces','province_id')->onDelete('cascade');
            $table->string('postal_code')->nullable();

            $table->timestamps();
        });

         Schema::create('barangays', function (Blueprint $table) {
            $table->id('barangay_id');
            $table -> string('barangay_name');
            $table -> foreignId('city_id')->constrained('cities','city_id')->onDelete('cascade');

            $table->timestamps();
        });

      

          Schema::create('full_addresses', function (Blueprint $table) {
            $table->id('address_id');
            $table->string('street');
            $table -> foreignId('barangay_id')->constrained('barangays','barangay_id')->onDelete('cascade');
            $table->timestamps();
        });

         Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table -> string('role');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('account_code')->unique(); //CIT-0001 sample
            $table -> text('first_name');
            $table -> text('middle_name');
            $table -> text('last_name');
       
            $table -> foreignId('role_id')->constrained('roles', 'role_id') ->onDelete('cascade');
            $table -> foreignId('address_id')->nullable()->constrained('full_addresses', 'address_id') ->nullOnDelete();
            
            $table -> integer('contact_number');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('image')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('barangays');

        Schema::dropIfExists('full_addresses');

        Schema::dropIfExists('roles');

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
