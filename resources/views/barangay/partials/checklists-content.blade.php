@php
    $legacyMitigationCounts = $mitigationActions->whereNotNull('barangay_prediction_checklist_id')->groupBy('barangay_prediction_checklist_id')->map->count();
    $aiMitigationCounts = $mitigationActions->whereNotNull('ai_action_plan_checklist_id')->groupBy('ai_action_plan_checklist_id')->map->count();

    $legacyRecords = $predictionChecklists->map(fn ($item) => (object) [
        'key' => 'prediction-checklist-' . $item->barangay_prediction_checklist_id,
        'source' => $item->is_ai_generated ? 'AI Prediction Checklist' : 'Prediction Checklist',
        'title' => $item->title,
        'detail' => $item->planning_notes ?: $item->ai_output ?: 'No guidance recorded.',
        'planning_notes' => $item->planning_notes,
        'priority' => $item->priority_level ?: 'medium',
        'status' => $item->status ?: 'pending',
        'family' => $item->status === 'complied' ? 'completed' : ($item->status === 'in_progress' ? 'in_progress' : 'pending'),
        'target' => $item->target_date,
        'assignee' => $item->assignee?->full_name ?? 'Unassigned',
        'reviewer' => $item->reviewer?->full_name ?? 'Not reviewed',
        'note' => $item->compliance_remarks ?: 'No compliance note.',
        'compliance_remarks' => $item->compliance_remarks,
        'mitigations' => (int) ($legacyMitigationCounts[$item->barangay_prediction_checklist_id] ?? 0),
        'progress' => $item->status === 'complied' ? 100 : ($item->status === 'in_progress' ? 50 : 0),
        'steps' => collect(),
        'checklist_id' => null,
        'legacy_checklist_id' => $item->barangay_prediction_checklist_id,
    ]);

    $aiRecords = $aiActionPlanChecklists->map(function ($item) use ($aiMitigationCounts) {
        $progress = $item->checklist_progress;
        return (object) [
            'key' => 'ai-checklist-' . $item->ai_action_plan_checklist_id,
            'source' => 'Approved AI Checklist',
            'title' => $item->plan_title,
            'detail' => $item->plan_description ?: $item->source_description ?: 'No description recorded.',
            'planning_notes' => null,
            'priority' => $item->priority_level ?: 'medium',
            'status' => $item->status ?: 'confirmed',
            'family' => ($progress['percent'] ?? 0) >= 100 ? 'completed' : (($progress['percent'] ?? 0) > 0 ? 'in_progress' : 'pending'),
            'target' => $item->target_date,
            'assignee' => $item->assignee?->full_name ?? 'Station command',
            'reviewer' => $item->confirmer?->full_name ?? 'Station command',
            'note' => $item->confirmation_notes ?: 'No confirmation note.',
            'compliance_remarks' => null,
            'mitigations' => (int) ($aiMitigationCounts[$item->ai_action_plan_checklist_id] ?? 0),
            'progress' => (int) ($progress['percent'] ?? 0),
            'steps' => $item->items->values(),
            'checklist_id' => $item->ai_action_plan_checklist_id,
            'legacy_checklist_id' => null,
        ];
    });

    $checklistRecords = $legacyRecords->concat($aiRecords)->sortBy([
        fn ($item) => match ($item->priority) { 'critical' => 1, 'high' => 2, 'medium' => 3, default => 4 },
        fn ($item) => $item->target?->timestamp ?? PHP_INT_MAX,
    ])->values();

    $linkedAnalyses = $predictionChecklists->pluck('analysis')->concat($aiActionPlanChecklists->pluck('analysis'))->filter()->unique('analysis_id')->sortByDesc('created_at')->values();
    $barangayPredictions = $linkedAnalyses->map(function ($analysis) use ($barangay) {
        $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
        $predictions = is_array($analysis->predictions) ? $analysis->predictions : [];
        $raw = is_array($analysisData['raw_data'] ?? null) ? $analysisData['raw_data'] : [];
        $barangayId = (int) ($barangay?->barangay_id ?? 0);
        $local = collect($raw['barangay_predictions'] ?? [])->first(fn ($entry) => is_array($entry) && (int) ($entry['barangay_id'] ?? 0) === $barangayId);
        if (! is_array($local)) {
            $selected = $raw['selected_barangay_prediction'] ?? null;
            $local = is_array($selected) && (int) ($selected['barangay_id'] ?? $barangayId) === $barangayId ? $selected : [];
        }
        $isBarangayScoped = ! empty($local) || (int) ($raw['barangay_id'] ?? 0) === $barangayId;
        $predicted = data_get($local, 'predicted_fire_incidents') ?? data_get($local, 'predicted_incident_count');
        $probability = data_get($local, 'probability') ?? data_get($local, 'risk_probability');
        if ($isBarangayScoped) {
            $predicted ??= data_get($predictions, 'predicted_fire_incidents') ?? data_get($predictions, 'predicted_incident_count');
            $probability ??= $analysis->probability ?? $analysis->confidence_data;
        }
        $probabilityValue = is_numeric($probability) ? (float) $probability : null;
        if ($probabilityValue !== null && $probabilityValue > 0 && $probabilityValue <= 1) {
            $probabilityValue *= 100;
        }
        return (object) [
            'key' => 'prediction-' . $analysis->analysis_id,
            'kind' => ($raw['analysis_kind'] ?? '') === 'forecast' || ($raw['prediction_scope'] ?? '') === 'next_month' ? 'Next-month forecast' : 'Current analysis',
            'period' => data_get($raw, 'prediction_context.target_month_label') ?: ($analysis->period ?: 'Saved run'),
            'risk' => data_get($local, 'risk_level') ?: ($isBarangayScoped ? ($analysis->risk_level ?: 'Not classified') : 'Not classified'),
            'probability' => $probabilityValue !== null ? rtrim(rtrim(number_format($probabilityValue, 1), '0'), '.') . '%' : 'Not recorded',
            'predicted' => is_numeric($predicted) ? (int) $predicted : null,
            'actual' => (int) ($analysis->incident_count ?? 0),
            'revision' => (int) ($analysis->revision_number ?? 1),
            'saved' => $analysis->created_at,
        ];
    });
    $openChecklists = $checklistRecords->whereIn('family', ['pending', 'in_progress'])->count();
    $openMitigations = $mitigationActions->whereIn('status', ['planned', 'in_progress', 'blocked'])->count();
@endphp

<div class="checklist-center-stack" data-checklist-center-content>
    <section class="checklist-summary-grid">
        @foreach([
            ['AI Predictions', $barangayPredictions->count(), 'bi-stars'],
            ['Checklists', $checklistRecords->count(), 'bi-check2-square'],
            ['Open Items', $openChecklists, 'bi-hourglass-split'],
            ['Open Mitigations', $openMitigations, 'bi-shield-check'],
        ] as [$label, $value, $icon])
            <article class="checklist-summary-card"><span class="checklist-summary-icon"><i class="bi {{ $icon }}"></i></span><div><span class="checklist-summary-label">{{ $label }}</span><strong class="checklist-summary-value">{{ $value }}</strong></div></article>
        @endforeach
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading"><div><span class="checklist-panel-kicker">AI Forecast</span><h3>Barangay Predictions</h3></div><span class="tag medium">{{ $barangay?->barangay_name ?? 'Current barangay' }}</span></div>
        @if($barangayPredictions->isEmpty()) <div class="empty-state">No linked AI prediction found.</div> @else
            <div class="checklist-table-wrap"><table class="checklist-table" data-compact-list="predictions" data-page-size="6"><thead><tr><th>Run</th><th>Period</th><th>Risk</th><th>Prediction</th><th></th></tr></thead><tbody>
            @foreach($barangayPredictions as $item)
                <tr data-compact-row><td><strong>{{ $item->kind }}</strong><small>Revision {{ $item->revision }}</small></td><td>{{ $item->period }}</td><td><span class="tag {{ strtolower($item->risk) }}">{{ strtoupper($item->risk) }}</span></td><td><strong>{{ $item->predicted ?? '—' }}</strong><small>{{ $item->probability }} probability</small></td><td><button type="button" class="checklist-view-button" data-checklist-detail="{{ $item->key }}"><i class="bi bi-eye"></i></button></td></tr>
                <template id="{{ $item->key }}"><div class="checklist-detail-grid"><div><span>Type</span><strong>{{ $item->kind }}</strong></div><div><span>Period</span><strong>{{ $item->period }}</strong></div><div><span>Risk</span><strong>{{ $item->risk }}</strong></div><div><span>Probability</span><strong>{{ $item->probability }}</strong></div><div><span>Predicted incidents</span><strong>{{ $item->predicted ?? 'Not recorded' }}</strong></div><div><span>Recorded incidents</span><strong>{{ $item->actual }}</strong></div><div><span>Revision</span><strong>{{ $item->revision }}</strong></div><div><span>Saved</span><strong>{{ $item->saved?->format('M d, Y h:i A') ?? 'Not recorded' }}</strong></div></div></template>
            @endforeach
            </tbody></table></div><div class="checklist-compact-pagination" data-pagination-for="predictions"></div>
        @endif
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading"><div><span class="checklist-panel-kicker">Action Steps</span><h3>AI and Prediction Checklists</h3></div></div>
        @if($checklistRecords->isEmpty()) <div class="empty-state">No checklist found.</div> @else
            <div class="checklist-table-wrap"><table class="checklist-table" data-compact-list="checklists" data-page-size="6"><thead><tr><th>Checklist</th><th>Priority</th><th>Status</th><th>Progress</th><th></th></tr></thead><tbody>
            @foreach($checklistRecords as $item)
                <tr data-compact-row><td><strong>{{ $item->title }}</strong><small>{{ $item->source }}</small></td><td><span class="tag {{ $item->priority }}">{{ strtoupper($item->priority) }}</span></td><td><span class="tag {{ $item->family === 'completed' ? 'completed' : $item->status }}">{{ strtoupper(str_replace('_', ' ', $item->status)) }}</span></td><td><strong>{{ $item->progress }}%</strong><small>{{ $item->mitigations }} mitigation linked</small></td><td><button type="button" class="checklist-view-button" data-checklist-detail="{{ $item->key }}"><i class="bi bi-eye"></i></button></td></tr>
                <template id="{{ $item->key }}">@include('barangay.partials.checklist-detail', ['item' => $item])</template>
            @endforeach
            </tbody></table></div><div class="checklist-compact-pagination" data-pagination-for="checklists"></div>
        @endif
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading"><div><span class="checklist-panel-kicker">Risk Control</span><h3>Linked Mitigations</h3></div></div>
        @if($mitigationActions->isEmpty()) <div class="empty-state">No mitigation found.</div> @else
            <div class="checklist-table-wrap"><table class="checklist-table" data-compact-list="mitigations" data-page-size="6"><thead><tr><th>Mitigation</th><th>Priority</th><th>Status</th><th>Target</th><th></th></tr></thead><tbody>
            @foreach($mitigationActions as $action)
                <tr data-compact-row><td><strong>{{ $action->title }}</strong><small>{{ $action->creator?->full_name ?? 'Station command' }}</small></td><td><span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span></td><td><span class="tag {{ $action->status }}">{{ strtoupper(str_replace('_', ' ', $action->status)) }}</span></td><td>{{ $action->target_date?->format('M d, Y') ?? 'No date' }}</td><td><button type="button" class="checklist-view-button" data-checklist-detail="mitigation-{{ $action->mitigation_action_id }}"><i class="bi bi-eye"></i></button></td></tr>
                <template id="mitigation-{{ $action->mitigation_action_id }}">@include('barangay.partials.mitigation-detail', ['action' => $action])</template>
            @endforeach
            </tbody></table></div><div class="checklist-compact-pagination" data-pagination-for="mitigations"></div>
        @endif
    </section>
</div>
