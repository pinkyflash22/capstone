<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrowserChecklistSmokeSeeder extends Seeder
{
    public function run(): void
    {
        $chiefId = DB::table('users')->where('email', 'chief.sample@example.com')->value('user_id');
        $stationId = $chiefId
            ? DB::table('station_officers')->where('user_id', $chiefId)->value('station_id')
            : null;
        $barangayId = $stationId
            ? DB::table('station_barangay_coverage')->where('station_id', $stationId)->where('is_active', true)->value('barangay_id')
            : null;
        $barangayId ??= DB::table('barangays')->value('barangay_id');

        if (! $chiefId || ! $stationId || ! $barangayId) {
            throw new \RuntimeException('The browser checklist fixture requires the sample chief, station, and barangay seed data.');
        }

        $signature = 'browser-checklist-responsive-smoke-v1';
        DB::table('ai_action_plan_checklists')->updateOrInsert(
            ['source_signature' => $signature],
            [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'source_type' => 'action_plan',
                'source_title' => 'Browser verification fixture',
                'source_description' => 'Stable fixture for authenticated responsive checklist verification.',
                'plan_title' => 'Verify barangay fire-readiness controls',
                'plan_description' => 'Review access, evacuation paths, and visible prevention controls with the barangay.',
                'checklist_items' => json_encode(['Verify evacuation access and prevention controls.']),
                'success_indicator' => 'The assigned prevention control is verified and documented.',
                'priority_level' => 'high',
                'status' => 'pending',
                'target_date' => now()->addWeek()->toDateString(),
                'assigned_to' => $chiefId,
                'ai_payload' => json_encode(['fixture' => true]),
                'is_ai_generated' => false,
                'is_superseded' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $checklistId = DB::table('ai_action_plan_checklists')
            ->where('source_signature', $signature)
            ->value('ai_action_plan_checklist_id');

        DB::table('ai_action_plan_checklist_items')->updateOrInsert(
            ['ai_action_plan_checklist_id' => $checklistId, 'item_order' => 1],
            [
                'item_text' => 'Verify evacuation access and prevention controls.',
                'status' => 'pending',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
