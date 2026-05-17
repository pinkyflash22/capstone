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
        Schema::create('routes', function (Blueprint $table) {
            // Primary key
            $table->id('route_id');
            
            // Route data
            $table->json('coordinates'); // Stores the route points as JSON
            $table->decimal('total_distance', 10, 2); // Total distance in meters
            $table->decimal('estimated_time', 10, 2); // Estimated time in seconds
            
            // Status tracking
            $table->enum('status', ['active', 'moving', 'inactive'])->default('active');
            
            // Destination information
            $table->string('destination_title')->nullable();
            $table->decimal('destination_lat', 10, 8)->nullable();
            $table->decimal('destination_lng', 11, 8)->nullable();
            
            // Position tracking for resume capability
            $table->integer('current_position_index')->default(0); // Current point index in the route
            $table->decimal('current_lat', 10, 8)->nullable(); // Current latitude of the truck
            $table->decimal('current_lng', 11, 8)->nullable(); // Current longitude of the truck
            $table->decimal('progress_percentage', 5, 2)->default(0); // Progress percentage (0-100)
            
            // User tracking
            $table->string('started_by')->nullable(); // Who started this route
            $table->timestamp('started_at')->nullable(); // When the movement started
            $table->timestamp('last_updated_at')->nullable(); // Last position update
            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents', 'incident_id')
                ->cascadeOnDelete();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('status');
            $table->index('created_at');
            $table->index('started_by');
            $table->index('incident_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
