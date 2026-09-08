<?php

namespace App\Services;

use App\Models\AiActionPlanChecklist;
use Illuminate\Support\Str;

class ChecklistMitigationDraftService
{
    private const CATEGORIES = [
        'electrical_safety',
        'water_supply',
        'response_readiness',
        'access_routing',
        'inspection_enforcement',
        'community_prevention',
        'evacuation_life_safety',
        'high_risk_area_monitoring',
        'environmental_preparedness',
        'other',
    ];

    public function transform(AiActionPlanChecklist $checklist, array $selectedItemIds = []): array
    {
        $checklist->loadMissing(['items', 'barangay', 'riskArea.marker', 'suggestions']);

        $selectedIds = collect($selectedItemIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();
        $items = $checklist->items
            ->when($selectedIds->isNotEmpty(), fn ($items) => $items->whereIn('ai_action_plan_checklist_item_id', $selectedIds))
            ->sortBy('item_order')
            ->values();
        $actions = $items->pluck('item_text')
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
        $category = $this->resolveCategory($checklist, $actions);
        $categoryLabel = Str::headline($category);
        $location = $checklist->riskArea?->marker?->title
            ?: $checklist->barangay?->barangay_name
            ?: 'the identified area';
        $sourceContext = trim((string) ($checklist->source_description ?: $checklist->plan_description));

        return [
            'label' => 'AI-Assisted Mitigation Draft',
            'helper_text' => 'Generated from the recommended checklist. Review and edit before saving.',
            'workflow_note' => 'AI-Assisted Mitigation Draft - Generated from the recommended checklist. Review and edit before saving.',
            'source_title' => $checklist->plan_title ?: $checklist->source_title,
            'barangay_id' => $checklist->barangay_id,
            'scope_type' => 'barangay',
            'title' => $this->buildTitle($checklist, $categoryLabel),
            'category' => $category,
            'priority_level' => $this->normalizePriority($checklist->priority_level),
            'status' => 'draft',
            'source_type' => 'ai_action_plan',
            'target_date' => optional($checklist->target_date)->format('Y-m-d'),
            'description' => $this->buildDescription($category, $categoryLabel, $location, count($actions)),
            'baseline_condition' => $sourceContext,
            'expected_result' => trim((string) ($checklist->success_indicator ?: $this->defaultExpectedResult($categoryLabel))),
            'remarks' => $this->buildPlanningRemarks((int) $checklist->ai_action_plan_checklist_id, $actions),
            'risk_area_id' => $checklist->risk_area_id,
            'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
            'recommended_actions' => $actions,
            'available_items' => $checklist->items->sortBy('item_order')->values()->map(fn ($item) => [
                'id' => (int) $item->ai_action_plan_checklist_item_id,
                'text' => $item->item_text,
                'status' => $item->status,
            ])->all(),
        ];
    }

    private function resolveCategory(AiActionPlanChecklist $checklist, array $actions): string
    {
        $payloadCategory = Str::snake((string) data_get($checklist->ai_payload, 'recommended_category', ''));
        if (in_array($payloadCategory, self::CATEGORIES, true)) {
            return $payloadCategory;
        }

        $context = Str::lower(implode(' ', array_filter([
            $checklist->plan_title,
            $checklist->plan_description,
            $checklist->source_title,
            $checklist->source_description,
            ...$actions,
        ])));

        return match (true) {
            str_contains($context, 'electrical'), str_contains($context, 'wiring') => 'electrical_safety',
            str_contains($context, 'hydrant'), str_contains($context, 'water supply') => 'water_supply',
            str_contains($context, 'evacuation'), str_contains($context, 'exit') => 'evacuation_life_safety',
            str_contains($context, 'access'), str_contains($context, 'road') => 'access_routing',
            str_contains($context, 'inspection'), str_contains($context, 'compliance') => 'inspection_enforcement',
            str_contains($context, 'awareness'), str_contains($context, 'resident') => 'community_prevention',
            str_contains($context, 'vegetation'), str_contains($context, 'environment') => 'environmental_preparedness',
            str_contains($context, 'equipment'), str_contains($context, 'response') => 'response_readiness',
            default => 'high_risk_area_monitoring',
        };
    }

    private function buildTitle(AiActionPlanChecklist $checklist, string $categoryLabel): string
    {
        $sourceTitle = trim((string) ($checklist->plan_title ?: $checklist->source_title));
        $cleanTitle = trim((string) preg_replace('/\b(?:AI|action[ -]?plan|checklist|recommended actions?)\b/i', '', $sourceTitle), " -:\t\n\r\0\x0B");

        if ($cleanTitle !== '' && ! Str::contains(Str::lower($cleanTitle), 'mitigation')) {
            return Str::limit($cleanTitle.' Mitigation Plan', 255, '');
        }

        return Str::limit(($cleanTitle ?: $categoryLabel).' Mitigation Plan', 255, '');
    }

    private function buildDescription(string $category, string $categoryLabel, string $location, int $actionCount): string
    {
        $objective = match ($category) {
            'electrical_safety' => 'reduce electrical ignition hazards through coordinated inspection, corrective action, utility coordination, and community safety measures',
            'water_supply' => 'restore dependable firefighting water access through inspection, obstruction removal, repair coordination, and operational verification',
            'access_routing' => 'improve emergency vehicle access through route inspection, obstruction clearing, local coordination, and response validation',
            'inspection_enforcement' => 'reduce documented fire-safety violations through focused inspection, corrective notices, coordination, and compliance verification',
            'community_prevention' => 'reduce preventable fire exposure through targeted corrective work, community coordination, and safety awareness activities',
            'evacuation_life_safety' => 'improve evacuation and life-safety readiness through access checks, corrective measures, communication, and field verification',
            'environmental_preparedness' => 'reduce environmental fire exposure through site inspection, combustible-material control, coordination, and follow-up monitoring',
            'response_readiness' => 'strengthen operational readiness through resource checks, personnel coordination, deployment preparation, and field validation',
            default => 'reduce the identified fire risk through coordinated assessment, corrective action, community engagement, and follow-up verification',
        };
        $measureText = $actionCount === 1 ? 'the selected checklist measure' : "the {$actionCount} selected checklist measures";

        return "Conduct a professional {$categoryLabel} intervention in {$location} to {$objective}. Use {$measureText} as the operational basis, document implementation evidence, and verify the resulting reduction in risk before completion.";
    }

    private function defaultExpectedResult(string $categoryLabel): string
    {
        return "The identified {$categoryLabel} condition is corrected or measurably reduced, documented, and verified by the responsible station or barangay team.";
    }

    private function buildPlanningRemarks(int $checklistId, array $actions): string
    {
        $actionLines = collect($actions)
            ->map(fn ($action) => '- '.rtrim(trim((string) $action), '.').'.')
            ->implode("\n");

        return "Recommended actions selected from checklist #{$checklistId}:\n{$actionLines}\n\n"
            .'AI-assisted transformation only; no additional AI request was made. Review and edit before saving.';
    }

    private function normalizePriority(?string $priority): string
    {
        $normalized = Str::lower(trim((string) $priority));

        return in_array($normalized, ['critical', 'high', 'medium', 'low'], true) ? $normalized : 'medium';
    }
}
