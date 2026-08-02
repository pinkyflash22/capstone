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
     
       Schema::create('hydrants', function (Blueprint $table) {
            $table->id('hydrant_id');
            $table->string('hydrant_code')->unique(); //serail numbers etc
            $table ->foreignId('station_id')->constrained('stations','station_id')->onDelete('cascade');
            $table ->foreignId('marker_id')->constrained('markers','marker_id')->onDelete('cascade');
            $table->enum('type', ['dry barrel', 'wet barrel', 'pillar', 'underground', 'compression'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    
        Schema::dropIfExists('hydrants');
        
    }
};
