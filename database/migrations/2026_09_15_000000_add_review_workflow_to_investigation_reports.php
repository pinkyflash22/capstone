<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'spot_investigation_report',
        'progress_investigation_report',
        'final_investigation_report',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'review_status')) {
                    $table->string('review_status', 32)->default('submitted')->index();
                }

                if (! Schema::hasColumn($tableName, 'review_comments')) {
                    $table->text('review_comments')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'reviewed_by_user_id')) {
                    $table->foreignId('reviewed_by_user_id')
                        ->nullable()
                        ->constrained('users', 'user_id')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'reviewed_by_user_id')) {
                    $table->dropConstrainedForeignId('reviewed_by_user_id');
                }

                $columns = collect(['review_status', 'review_comments', 'reviewed_at'])
                    ->filter(fn ($column) => Schema::hasColumn($tableName, $column))
                    ->all();

                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
