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

         Schema::create('station_officers', function (Blueprint $table) {
            $table->id('station_officer_id');
            $table->foreignId('station_id')->constrained('stations','station_id');
            $table->foreignId('user_id')->constrained('users','user_id');
            // $table->string('rank');
            $table->enum('rank',['Fire Officer 1','Fire Officer 2','Fire Officer 3',
            'Senior Officer 1','Senior Officer 2','Senior Officer 3','Senior Fire Officer 4',
            'Fire Inspector','Fire Senior Inspector','Fire Chief Inspector','Fire Superintendent',
            ]);

            $table->timestamps();
        });

    

        Schema::create('teams', function (Blueprint $table) {
            $table->id('team_id');

            $table->foreignId('commander_driver')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_commander_driver_fk');

            $table->foreignId('pumper')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_pumper_fk');

            $table->foreignId('team_leader')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_team_leader_fk');

            $table->foreignId('firefighter_officer_1')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_firefighter_officer_1_fk');

            $table->foreignId('firefighter_officer_2')
                ->nullable()
               ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_firefighter_officer_2_fk');

            $table->foreignId('firefighter_officer_3')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                // ->name('teams_firefighter_officer_3_fk');

            $table->foreignId('rescue_radio')
                ->nullable()
                ->constrained('station_officers', 'station_officer_id')
                ->onDelete('set null');
                

            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->enum('status', ['Reported', 'Responding', 'On Scene', 'Controlled', 'Closed'])
                ->default('Reported');
            $table->foreignId('marker_id')->constrained('markers', 'marker_id')->onDelete('cascade');
            
            $table->foreignId('team_id')->nullable()->constrained('teams', 'team_id')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users', 'user_id')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('teams');

        Schema::dropIfExists('station_officers');
      
    }
};
