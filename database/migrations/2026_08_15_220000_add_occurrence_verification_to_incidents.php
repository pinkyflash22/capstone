<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'occurred_at_source')) {
                $table->string('occurred_at_source', 50)->nullable()->after('occurred_at');
            }
            if (! Schema::hasColumn('incidents', 'occurred_at_verified_at')) {
                $table->timestamp('occurred_at_verified_at')->nullable()->after('occurred_at_source');
            }
            if (! Schema::hasColumn('incidents', 'occurred_at_verified_by')) {
                $table->foreignId('occurred_at_verified_by')
                    ->nullable()
                    ->after('occurred_at_verified_at')
                    ->constrained('users', 'user_id')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('incident_occurrence_revisions')) {
            Schema::create('incident_occurrence_revisions', function (Blueprint $table) {
                $table->id('incident_occurrence_revision_id');
                $table->foreignId('incident_id')
                    ->constrained('incidents', 'incident_id')
                    ->cascadeOnDelete();
                $table->timestamp('previous_occurred_at')->nullable();
                $table->timestamp('occurred_at');
                $table->string('source', 50);
                $table->foreignId('verified_by_user_id')
                    ->nullable()
                    ->constrained('users', 'user_id')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['incident_id', 'created_at'], 'incident_occurrence_revision_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_occurrence_revisions');

        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'occurred_at_verified_by')) {
                $table->dropConstrainedForeignId('occurred_at_verified_by');
            }
            foreach (['occurred_at_verified_at', 'occurred_at_source'] as $column) {
                if (Schema::hasColumn('incidents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
