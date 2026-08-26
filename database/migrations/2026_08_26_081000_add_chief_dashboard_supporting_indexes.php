<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsic_approvals', function (Blueprint $table): void {
            $table->index(['approved_by', 'date_issued'], 'fsic_approvals_officer_issued_idx');
        });

        Schema::table('barangay_officers', function (Blueprint $table): void {
            $table->index(['status', 'position'], 'barangay_officers_status_position_idx');
        });
    }

    public function down(): void
    {
        Schema::table('barangay_officers', fn (Blueprint $table) => $table->dropIndex('barangay_officers_status_position_idx'));
        Schema::table('fsic_approvals', fn (Blueprint $table) => $table->dropIndex('fsic_approvals_officer_issued_idx'));
    }
};
