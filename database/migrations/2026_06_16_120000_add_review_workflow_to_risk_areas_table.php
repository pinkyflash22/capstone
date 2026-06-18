<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_areas', 'review_status')) {
                $table->enum('review_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('status');
            }

            if (! Schema::hasColumn('risk_areas', 'review_type')) {
                $table->enum('review_type', ['create', 'update', 'delete'])
                    ->default('create')
                    ->after('review_status');
            }

            if (! Schema::hasColumn('risk_areas', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('review_type');
            }

            if (! Schema::hasColumn('risk_areas', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_notes');
            }

            if (! Schema::hasColumn('risk_areas', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at');
                $table->foreign('reviewed_by', 'risk_areas_reviewed_by_fk')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('risk_areas', function (Blueprint $table) {
            if (Schema::hasColumn('risk_areas', 'reviewed_by')) {
                $table->dropForeign('risk_areas_reviewed_by_fk');
                $table->dropColumn('reviewed_by');
            }

            foreach (['reviewed_at', 'review_notes', 'review_type', 'review_status'] as $column) {
                if (Schema::hasColumn('risk_areas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
