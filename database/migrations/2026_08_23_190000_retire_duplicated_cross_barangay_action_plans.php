<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_action_plan_checklists')
            || ! Schema::hasColumn('ai_action_plan_checklists', 'is_superseded')
            || ! Schema::hasColumn('ai_action_plan_checklists', 'requires_revision_review')) {
            return;
        }

        $duplicateGroups = DB::table('ai_action_plan_checklists')
            ->whereNotNull('analysis_id')
            ->whereNotNull('barangay_id')
            ->where('is_superseded', false)
            ->selectRaw('analysis_id, plan_title, COUNT(*) AS row_count, COUNT(DISTINCT barangay_id) AS barangay_count')
            ->groupBy('analysis_id', 'plan_title')
            ->havingRaw('COUNT(*) > 1')
            ->havingRaw('COUNT(DISTINCT barangay_id) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $checklists = DB::table('ai_action_plan_checklists')
                ->where('analysis_id', $group->analysis_id)
                ->where('plan_title', $group->plan_title)
                ->where('is_superseded', false)
                ->get();

            foreach ($checklists as $checklist) {
                $checklistId = (int) $checklist->ai_action_plan_checklist_id;
                $protected = strtolower((string) ($checklist->status ?? 'pending')) !== 'pending'
                    || ! empty($checklist->assigned_to)
                    || ! empty($checklist->confirmed_by)
                    || ! empty($checklist->confirmed_at);

                if (! $protected && Schema::hasTable('ai_action_plan_checklist_items')) {
                    $protected = DB::table('ai_action_plan_checklist_items')
                        ->where('ai_action_plan_checklist_id', $checklistId)
                        ->where('status', '!=', 'pending')
                        ->exists();
                }

                if (! $protected && Schema::hasTable('mitigation_actions')) {
                    $mitigationQuery = DB::table('mitigation_actions')
                        ->where('ai_action_plan_checklist_id', $checklistId)
                        ->where(function ($query) {
                            $query->where('status', '!=', 'draft');

                            if (Schema::hasColumn('mitigation_actions', 'is_manually_edited')) {
                                $query->orWhere('is_manually_edited', true);
                            }
                        });
                    $protected = $mitigationQuery->exists();
                }

                if ($protected) {
                    DB::table('ai_action_plan_checklists')
                        ->where('ai_action_plan_checklist_id', $checklistId)
                        ->update(['requires_revision_review' => true, 'updated_at' => now()]);

                    if (Schema::hasTable('mitigation_actions')
                        && Schema::hasColumn('mitigation_actions', 'requires_revision_review')) {
                        DB::table('mitigation_actions')
                            ->where('ai_action_plan_checklist_id', $checklistId)
                            ->update(['requires_revision_review' => true, 'updated_at' => now()]);
                    }

                    continue;
                }

                DB::table('ai_action_plan_checklists')
                    ->where('ai_action_plan_checklist_id', $checklistId)
                    ->update([
                        'is_superseded' => true,
                        'requires_revision_review' => false,
                        'superseded_at' => now(),
                        'updated_at' => now(),
                    ]);

                if (Schema::hasTable('mitigation_actions')
                    && Schema::hasColumn('mitigation_actions', 'is_superseded')) {
                    DB::table('mitigation_actions')
                        ->where('ai_action_plan_checklist_id', $checklistId)
                        ->where('status', 'draft')
                        ->update([
                            'is_superseded' => true,
                            'superseded_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally retained: restoring known-invalid generated plans could
        // put cross-barangay guidance back into active operational workflows.
    }
};
