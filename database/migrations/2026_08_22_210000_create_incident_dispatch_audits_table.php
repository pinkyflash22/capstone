<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incident_dispatch_audits')) return;

        Schema::create('incident_dispatch_audits', function (Blueprint $table) {
            $table->id('incident_dispatch_audit_id');
            $table->unsignedBigInteger('incident_id')->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('action', 50)->index();
            $table->unsignedBigInteger('previous_team_id')->nullable();
            $table->unsignedBigInteger('assigned_team_id')->nullable();
            $table->unsignedBigInteger('source_station_id')->nullable();
            $table->unsignedBigInteger('target_station_id')->nullable();
            $table->unsignedBigInteger('response_unit_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_dispatch_audits');
    }
};
