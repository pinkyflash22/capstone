<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_resource_allocations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourceAllocationsTable extends Migration
{
    public function up()
    {
       

        Schema::create('equipments', function (Blueprint $table) {
            $table->id('equipment_id');
            $table->string('equipment_name')->unique();
            $table->enum('category', [
                'Personal Protective Equipment',
                'firefighting equipment',
                'rescue equipment',
                'communication equipment',
                'vehicles',
            ])->nullable();
            $table->enum('type', ['disposable', 'repairable'])->nullable();
            $table->timestamps();
        });

          Schema::create('equipments_to_stations', function (Blueprint $table) {
            $table->id('equipment_station_id');
            $table->foreignId('station_id')->constrained('stations','station_id')->onDelete('cascade');
            // $table->string('serial_number')->nullable()->unique();    
            $table->foreignId('equipment_id')->constrained('equipments','equipment_id')->onDelete('cascade');
            $table->integer('available_quantity');
            $table->integer('damaged_quantity');
            $table->integer('maintenance_quantity');
            $table->timestamps();
        });

         
    }

    public function down()
    {
        
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('equipments_to_stations');




    }
}
