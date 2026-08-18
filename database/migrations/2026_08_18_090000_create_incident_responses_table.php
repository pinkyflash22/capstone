<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_responses', function (Blueprint $table) {
            $table->id('incident_response_id');
            $table->foreignId('incident_id')
                ->unique()
                ->constrained('incidents', 'incident_id')
                ->cascadeOnDelete();
            $table->json('responders')->default('[]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_responses');
    }
};
