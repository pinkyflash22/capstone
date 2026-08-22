<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incident_report') || ! Schema::hasColumn('incidents', 'dispatch_priority')) {
            return;
        }

        DB::table('incident_report')
            ->select(['incident_id', 'severity'])
            ->orderBy('incident_id')
            ->chunk(200, function ($reports) {
                foreach ($reports as $report) {
                    $priority = match (strtolower(trim((string) $report->severity))) {
                        'code red', 'critical' => 'critical',
                        'high' => 'high',
                        'low' => 'low',
                        default => 'medium',
                    };

                    DB::table('incidents')
                        ->where('incident_id', $report->incident_id)
                        ->update(['dispatch_priority' => $priority]);
                }
            });
    }

    public function down(): void
    {
        // Priority remains valid operational data when this backfill is rolled back.
    }
};
