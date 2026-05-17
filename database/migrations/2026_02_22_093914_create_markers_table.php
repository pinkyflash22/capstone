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

  


    Schema::create('markers', function (Blueprint $table) {
        $table->id('marker_id');
        $table->string('title');
        $table->text('description')->nullable();
        $table->decimal('lat', 10, 7);
        $table->decimal('lng', 10, 7);
        $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
        $table->enum('category', ['fire', 'station', 'hydrant', 'school', 'barangay', 'bridge'])->nullable();
       
        $table->longText('image')->nullable();
        $table->timestamps();

    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
  

        Schema::dropIfExists('markers');
    }
};
