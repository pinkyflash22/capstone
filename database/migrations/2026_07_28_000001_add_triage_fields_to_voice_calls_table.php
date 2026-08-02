<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS created_incident_id BIGINT NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS caller_latitude NUMERIC(10,7) NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS caller_longitude NUMERIC(10,7) NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS caller_accuracy INTEGER NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS caller_location_label VARCHAR(255) NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS flagged_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS flagged_by BIGINT NULL');
        DB::statement('ALTER TABLE voice_calls ADD COLUMN IF NOT EXISTS flag_reason VARCHAR(160) NULL');

        $createdIncidentForeignExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['voice_calls', 'voice_calls_created_incident_id_foreign', 'FOREIGN KEY']
        ));

        if (! $createdIncidentForeignExists) {
            DB::statement('ALTER TABLE voice_calls ADD CONSTRAINT voice_calls_created_incident_id_foreign FOREIGN KEY (created_incident_id) REFERENCES incidents(incident_id) ON DELETE SET NULL');
        }

        $flaggedByForeignExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['voice_calls', 'voice_calls_flagged_by_foreign', 'FOREIGN KEY']
        ));

        if (! $flaggedByForeignExists) {
            DB::statement('ALTER TABLE voice_calls ADD CONSTRAINT voice_calls_flagged_by_foreign FOREIGN KEY (flagged_by) REFERENCES users(user_id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('voice_calls')) {
            return;
        }

        $createdIncidentForeignExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['voice_calls', 'voice_calls_created_incident_id_foreign', 'FOREIGN KEY']
        ));

        if ($createdIncidentForeignExists) {
            DB::statement('ALTER TABLE voice_calls DROP CONSTRAINT voice_calls_created_incident_id_foreign');
        }

        $flaggedByForeignExists = ! empty(DB::select(
            '
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            ['voice_calls', 'voice_calls_flagged_by_foreign', 'FOREIGN KEY']
        ));

        if ($flaggedByForeignExists) {
            DB::statement('ALTER TABLE voice_calls DROP CONSTRAINT voice_calls_flagged_by_foreign');
        }

        foreach ([
            'created_incident_id',
            'caller_latitude',
            'caller_longitude',
            'caller_accuracy',
            'caller_location_label',
            'flagged_at',
            'flagged_by',
            'flag_reason',
        ] as $column) {
            DB::statement("ALTER TABLE voice_calls DROP COLUMN IF EXISTS {$column}");
        }
    }
};
