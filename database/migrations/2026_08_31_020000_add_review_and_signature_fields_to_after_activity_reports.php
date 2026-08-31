<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('after_activity_reports', function (Blueprint $table) {
            $table->json('participants')->nullable()->after('activity_location');
            $table->json('deployed_resources')->nullable()->after('participants');
            $table->string('review_status', 32)->default('submitted')->after('recommendations');
            $table->text('review_comments')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('review_comments')->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->timestamp('prepared_signed_at')->nullable()->after('reviewed_at');
            $table->string('prepared_signature_hash', 64)->nullable()->after('prepared_signed_at');
            $table->string('approval_signer_name')->nullable()->after('prepared_signature_hash');
            $table->timestamp('approved_signed_at')->nullable()->after('approval_signer_name');
            $table->string('approval_signature_hash', 64)->nullable()->after('approved_signed_at');
            $table->index(['station_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::table('after_activity_reports', function (Blueprint $table) {
            $table->dropIndex(['station_id', 'review_status']);
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn([
                'participants', 'deployed_resources', 'review_status', 'review_comments',
                'reviewed_by_user_id', 'reviewed_at', 'prepared_signed_at',
                'prepared_signature_hash', 'approval_signer_name', 'approved_signed_at',
                'approval_signature_hash',
            ]);
        });
    }
};
