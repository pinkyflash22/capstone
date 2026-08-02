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
        Schema::create('analysis', function (Blueprint $table) {
            $table->id('analysis_id'); // Primary key
            $table->string('sector', 100)->nullable(); // Which sector was analyzed
            $table->string('period', 50)->nullable(); // Time period analyzed
            $table->string('risk_level', 20)->nullable(); // Low/Medium/High/Critical
            $table->string('confidence_data')->nullable();
            $table->json('analysis_data')->nullable(); // Full AI response and data summary
            $table->json('recommendations')->nullable(); // Extracted recommendations
            $table->integer('incident_count')->default(0); // Number of incidents analyzed
            $table->string('probability')->nullable();
            $table->timestamps(); // created_at and updated_at
            
            // Optional: Add indexes for faster queries
            $table->index('sector');
            $table->index('risk_level');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis');
    }
};
