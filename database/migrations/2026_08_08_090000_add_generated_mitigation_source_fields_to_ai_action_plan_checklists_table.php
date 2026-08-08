<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasRiskAreaId = Schema::hasColumn('ai_action_plan_checklists', 'risk_area_id');
        $hasSourceSignature = Schema::hasColumn('ai_action_plan_checklists', 'source_signature');

        Schema::table('ai_action_plan_checklists', function (Blueprint $table) use ($hasRiskAreaId, $hasSourceSignature) {
            if (! $hasRiskAreaId) {
                $table->unsignedBigInteger('risk_area_id')->nullable()->after('barangay_id');
                $table->foreign('risk_area_id', 'ai_action_plan_risk_area_fk')
                    ->references('risk_area_id')
                    ->on('risk_areas')
                    ->nullOnDelete();
            }

            if (! $hasSourceSignature) {
                $table->string('source_signature', 190)->nullable()->after('status');
                $table->index(['source_signature'], 'ai_action_plan_source_signature_idx');
            }
        });
    }

    public function down(): void
    {
        $hasRiskAreaId = Schema::hasColumn('ai_action_plan_checklists', 'risk_area_id');
        $hasSourceSignature = Schema::hasColumn('ai_action_plan_checklists', 'source_signature');

        Schema::table('ai_action_plan_checklists', function (Blueprint $table) use ($hasRiskAreaId, $hasSourceSignature) {
            if ($hasSourceSignature) {
                $table->dropIndex('ai_action_plan_source_signature_idx');
                $table->dropColumn('source_signature');
            }

            if ($hasRiskAreaId) {
                $table->dropForeign('ai_action_plan_risk_area_fk');
                $table->dropColumn('risk_area_id');
            }
        });
    }
};
