<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        Schema::table('voice_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('voice_calls', 'created_incident_id')) {
                $table->unsignedBigInteger('created_incident_id')->nullable()->after('incident_id');
                $table->foreign('created_incident_id')->references('incident_id')->on('incidents')->nullOnDelete();
            }

            if (! Schema::hasColumn('voice_calls', 'caller_latitude')) {
                $table->decimal('caller_latitude', 10, 7)->nullable()->after('created_incident_id');
            }

            if (! Schema::hasColumn('voice_calls', 'caller_longitude')) {
                $table->decimal('caller_longitude', 10, 7)->nullable()->after('caller_latitude');
            }

            if (! Schema::hasColumn('voice_calls', 'caller_accuracy')) {
                $table->unsignedInteger('caller_accuracy')->nullable()->after('caller_longitude');
            }

            if (! Schema::hasColumn('voice_calls', 'caller_location_label')) {
                $table->string('caller_location_label')->nullable()->after('caller_accuracy');
            }

            if (! Schema::hasColumn('voice_calls', 'flagged_at')) {
                $table->timestamp('flagged_at')->nullable()->after('ended_at');
            }

            if (! Schema::hasColumn('voice_calls', 'flagged_by')) {
                $table->unsignedBigInteger('flagged_by')->nullable()->after('flagged_at');
                $table->foreign('flagged_by')->references('user_id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('voice_calls', 'flag_reason')) {
                $table->string('flag_reason', 160)->nullable()->after('flagged_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        Schema::table('voice_calls', function (Blueprint $table) {
            if (Schema::hasColumn('voice_calls', 'created_incident_id')) {
                $table->dropForeign(['created_incident_id']);
                $table->dropColumn('created_incident_id');
            }

            foreach (['caller_latitude', 'caller_longitude', 'caller_accuracy', 'caller_location_label'] as $column) {
                if (Schema::hasColumn('voice_calls', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('voice_calls', 'flagged_by')) {
                $table->dropForeign(['flagged_by']);
                $table->dropColumn('flagged_by');
            }

            foreach (['flagged_at', 'flag_reason'] as $column) {
                if (Schema::hasColumn('voice_calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
