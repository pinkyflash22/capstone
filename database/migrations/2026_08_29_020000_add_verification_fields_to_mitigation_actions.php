<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table): void {
            $table->text('baseline_condition')->nullable()->after('description');
            $table->text('expected_result')->nullable()->after('baseline_condition');
            $table->text('blocked_reason')->nullable()->after('implementation_notes');
            $table->date('verification_date')->nullable()->after('effectiveness');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verification_date');
            $table->date('follow_up_date')->nullable()->after('follow_up_required');

            $table->foreign('verified_by', 'mitigation_verified_by_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['effectiveness', 'verification_date'], 'mitigation_effectiveness_verification_idx');
            $table->index(['follow_up_required', 'follow_up_date'], 'mitigation_follow_up_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mitigation_actions', function (Blueprint $table): void {
            $table->dropForeign('mitigation_verified_by_fk');
            $table->dropIndex('mitigation_effectiveness_verification_idx');
            $table->dropIndex('mitigation_follow_up_due_idx');
            $table->dropColumn([
                'baseline_condition',
                'expected_result',
                'blocked_reason',
                'verification_date',
                'verified_by',
                'follow_up_date',
            ]);
        });
    }
};
