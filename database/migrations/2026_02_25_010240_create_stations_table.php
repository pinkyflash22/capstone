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
       
        Schema::create('stations', function (Blueprint $table) {
            $table->id('station_id');

            $table->string('station_name');          // e.g. "Station 1 Quiapo"
            $table->string('station_code')->unique(); // optional code (ST-001)
            // $table->string('station_chief')->nullable()->constrained('users','user_id')->nullOnDelete();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable(); //station email address
            $table -> foreignId('marker_id')->constrained('markers','marker_id')-> onDelete('cascade');
            $table -> foreignId('address_id')->nullable()->constrained('full_addresses', 'address_id') ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
