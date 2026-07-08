<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PredictionFeedbackSignalService
{
    /**
     * @var array<int, array<int, object>>
     */
    protected array $checklistsByBarangay = [];

    /**
     * @var array<int, array<int, object>>
     */
    protected array $itemsByChecklist = [];

    /**
     * @var array<int, array<int, object>>
     */
    protected array $mitigationsByBarangay = [];

    /**
     * @var array<int, bool>
     */
    protected array $warmedBarangays = [];

    /**
     * @var array<string, array<string, int|float>>
     */
    protected array $snapshotCache = [];

    public function warmForBarangays(iterable $barangayIds): void
    {
        $ids = collect($barangayIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && ! isset($this->warmedBarangays[$id]))
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $checklists = DB::table('ai_action_plan_checklists')
            ->whereIn('barangay_id', $ids)
            ->get([
                'ai_action_plan_checklist_id',
                'barangay_id',
                'priority_level',
                'status',
                'created_at',
                'confirmed_at',
            ]);

        foreach ($checklists->groupBy('barangay_id') as $barangayId => $rows) {
            $this->checklistsByBarangay[(int) $barangayId] = $rows->all();
        }

        $checklistIds = $checklists
            ->pluck('ai_action_plan_checklist_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($checklistIds->isNotEmpty()) {
            $items = DB::table('ai_action_plan_checklist_items')
                ->whereIn('ai_action_plan_checklist_id', $checklistIds)
                ->get([
                    'ai_action_plan_checklist_id',
                    'status',
                    'reviewed_at',
                ]);

            foreach ($items->groupBy('ai_action_plan_checklist_id') as $checklistId => $rows) {
                $this->itemsByChecklist[(int) $checklistId] = $rows->all();
            }
        }

        $mitigations = DB::table('mitigation_actions')
            ->whereIn('barangay_id', $ids)
            ->get([
                'mitigation_action_id',
                'barangay_id',
                'ai_action_plan_checklist_id',
                'status',
                'priority_level',
                'created_at',
                'completed_at',
            ]);

        foreach ($mitigations->groupBy('barangay_id') as $barangayId => $rows) {
            $this->mitigationsByBarangay[(int) $barangayId] = $rows->all();
        }

        foreach ($ids as $id) {
            $this->warmedBarangays[(int) $id] = true;
            $this->checklistsByBarangay[(int) $id] = $this->checklistsByBarangay[(int) $id] ?? [];
            $this->mitigationsByBarangay[(int) $id] = $this->mitigationsByBarangay[(int) $id] ?? [];
        }
    }

    public function snapshotForBarangay(int $barangayId, CarbonInterface $asOf): array
    {
        $barangayId = (int) $barangayId;
        $this->warmForBarangays([$barangayId]);

        $cacheKey = $barangayId . '|' . $asOf->copy()->endOfDay()->toDateString();
        if (isset($this->snapshotCache[$cacheKey])) {
            return $this->snapshotCache[$cacheKey];
        }

        $priorityWeights = [
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        ];

        $checklists = collect($this->checklistsByBarangay[$barangayId] ?? [])
            ->filter(function ($row) use ($asOf) {
                if (empty($row->created_at)) {
                    return true;
                }

                return Carbon::parse($row->created_at)->lte($asOf);
            })
            ->values();

        $totalWeight = 0;
        $confirmedWeight = 0;
        $confirmedCount = 0;
        $activeChecklistCount = 0;
        $checklistItemTotal = 0;
        $checklistItemFollowed = 0;
        $checklistItemNotFollowed = 0;

        foreach ($checklists as $checklist) {
            $weight = $priorityWeights[strtolower((string) ($checklist->priority_level ?? 'medium'))] ?? 2;
            $totalWeight += $weight;

            $status = strtolower((string) ($checklist->status ?? 'pending'));
            $confirmedAt = ! empty($checklist->confirmed_at) ? Carbon::parse($checklist->confirmed_at) : null;

            if ($status === 'confirmed' && (! $confirmedAt || $confirmedAt->lte($asOf))) {
                $confirmedWeight += $weight;
                $confirmedCount++;
            }

            if (in_array($status, ['pending', 'in_progress', 'submitted', 'confirmed'], true)) {
                $activeChecklistCount++;
            }

            $items = collect($this->itemsByChecklist[(int) $checklist->ai_action_plan_checklist_id] ?? []);
            $checklistItemTotal += $items->count();

            foreach ($items as $item) {
                $itemStatus = strtolower((string) ($item->status ?? 'pending'));
                $reviewedAt = ! empty($item->reviewed_at) ? Carbon::parse($item->reviewed_at) : null;

                if ($itemStatus === 'followed' && $reviewedAt && $reviewedAt->lte($asOf)) {
                    $checklistItemFollowed++;
                } elseif ($itemStatus === 'not_followed' && $reviewedAt && $reviewedAt->lte($asOf)) {
                    $checklistItemNotFollowed++;
                }
            }
        }

        $mitigations = collect($this->mitigationsByBarangay[$barangayId] ?? [])
            ->filter(function ($row) use ($asOf) {
                if (empty($row->created_at)) {
                    return true;
                }

                return Carbon::parse($row->created_at)->lte($asOf);
            })
            ->values();

        $mitigationTotal = $mitigations->count();
        $mitigationCompletedCount = 0;
        $mitigationBlockedCount = 0;
        $mitigationOpenCount = 0;
        $completionDurations = [];

        foreach ($mitigations as $mitigation) {
            $status = strtolower((string) ($mitigation->status ?? 'planned'));
            $createdAt = ! empty($mitigation->created_at) ? Carbon::parse($mitigation->created_at) : null;
            $completedAt = ! empty($mitigation->completed_at) ? Carbon::parse($mitigation->completed_at) : null;

            if (in_array($status, ['planned', 'in_progress', 'blocked'], true)) {
                $mitigationOpenCount++;
            }

            if ($status === 'blocked') {
                $mitigationBlockedCount++;
            }

            if ($status === 'completed' && $completedAt && $completedAt->lte($asOf)) {
                $mitigationCompletedCount++;

                if ($createdAt) {
                    $completionDurations[] = max(0, $createdAt->diffInDays($completedAt));
                }
            }
        }

        $checklistCompletionRatio = $totalWeight > 0 ? round($confirmedWeight / $totalWeight, 4) : 0.0;
        $checklistItemFollowedRatio = $checklistItemTotal > 0 ? round($checklistItemFollowed / $checklistItemTotal, 4) : 0.0;
        $mitigationCompletionRatio = $mitigationTotal > 0 ? round($mitigationCompletedCount / $mitigationTotal, 4) : 0.0;
        $avgMitigationCompletionDays = count($completionDurations) > 0
            ? round(array_sum($completionDurations) / count($completionDurations), 2)
            : 0.0;

        $mitigationReadinessSignal = round(
            ($checklistCompletionRatio * 8)
            + ($checklistItemFollowedRatio * 6)
            + ($mitigationCompletionRatio * 9)
            - min($mitigationBlockedCount * 0.7, 4),
            4
        );

        return $this->snapshotCache[$cacheKey] = [
            'ai_checklist_total' => $checklists->count(),
            'ai_checklist_confirmed_count' => $confirmedCount,
            'ai_checklist_active_count' => $activeChecklistCount,
            'ai_checklist_completion_ratio' => $checklistCompletionRatio,
            'ai_checklist_item_total' => $checklistItemTotal,
            'ai_checklist_item_followed_ratio' => $checklistItemFollowedRatio,
            'linked_mitigation_total' => $mitigationTotal,
            'linked_mitigation_completed_count' => $mitigationCompletedCount,
            'linked_mitigation_blocked_count' => $mitigationBlockedCount,
            'linked_mitigation_open_count' => $mitigationOpenCount,
            'mitigation_completion_ratio' => $mitigationCompletionRatio,
            'avg_mitigation_completion_days' => $avgMitigationCompletionDays,
            'mitigation_readiness_signal' => $mitigationReadinessSignal,
        ];
    }
}
