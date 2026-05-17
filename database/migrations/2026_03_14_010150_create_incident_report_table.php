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
        Schema::create('spot_investigation_report', function (Blueprint $table) {
            $table->id('spot_investigation_report_id');
            $table -> foreignId('investigator') ->constrained('users', 'user_id')->onDelete('cascade');
            $table->enum('casualty',['none','death','injured']); //injured or death or none

            // $table -> foreignId('owner_user_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->string('owner') -> nullable();
            $table->timestamp('fire_started')->nullable();
            $table->timestamp('fire_out')->nullable(); //update from controlled fiore
            $table->float('estimated_damage');
            $table->enum('occupancy',['residential','commercial','industrial']);
            $table->float('estimated_area');
            $table -> foreignId('incident_id') ->constrained('incidents', 'incident_id')->onDelete('cascade');
            $table->unique('incident_id');

            // $table -> foreignId('weather_id')->nullable()->constrained('weather', 'weather_id')->onDelete('set null');
            $table->timestamps();
        });

         Schema::create('fire_origin', function (Blueprint $table) {
            $table->id('fire_origin_id');
            $table->string('origin'); //injured or death or none
            $table->timestamps();
        });

        Schema::create('progress_investigation_report', function (Blueprint $table) {
            $table->id('progress_investigation_report_id');
            $table -> foreignId('fire_origin_id')->nullable() ->constrained('fire_origin', 'fire_origin_id')->onDelete('set null');
            $table -> foreignId('spot_investigation_id') ->constrained('spot_investigation_report', 'spot_investigation_report_id')->onDelete('cascade');
            $table->unique('spot_investigation_id');
            $table->string('responsible_arson')->nullable(); //injured or death or none
            $table->string('fire_cause'); //cooking via accident
            // $table -> foreignId('investigator') ->constrained('users', 'user_id')->onDelete('cascade');        
            $table->timestamps();
        });

         Schema::create('final_investigation_report', function (Blueprint $table) {
            $table->id('final_investigation_report_id');
            // $table->string('fire_origin'); //injured or death or none
            $table -> foreignId('progress_investigation_id') ->constrained('progress_investigation_report', 'progress_investigation_report_id')->onDelete('cascade');
            $table->unique('progress_investigation_id');
            $table->float('total_damage');
            // $table -> foreignId('address_id')->nullable() ->constrained('full_addresses', 'address_id')->onDelete('set null'); //complete
            $table->text('exact_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_investigation_report');
        Schema::dropIfExists('fire_origin');
        Schema::dropIfExists('progress_investigation_report');
        Schema::dropIfExists('final_investigation_report');

    }
};
