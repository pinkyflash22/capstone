<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_activity_report_reviews', function (Blueprint $table) {
            $table->id('after_activity_report_review_id');
            $table->foreignId('after_activity_report_id')->constrained('after_activity_reports', 'after_activity_report_id')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('decision', 32);
            $table->text('comments')->nullable();
            $table->string('signer_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['after_activity_report_id', 'created_at'], 'aar_reviews_report_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_activity_report_reviews');
    }
};
