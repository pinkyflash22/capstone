@php
    $legacyMitigationCountByChecklist = $mitigationActions
        ->whereNotNull('barangay_prediction_checklist_id')
        ->groupBy('barangay_prediction_checklist_id')
        ->map(fn ($group) => $group->count());

    $aiMitigationCountByChecklist = $mitigationActions
        ->whereNotNull('ai_action_plan_checklist_id')
        ->groupBy('ai_action_plan_checklist_id')
        ->map(fn ($group) => $group->count());

    $legacyChecklistRecords = $predictionChecklists->map(function ($item) use ($legacyMitigationCountByChecklist) {
        return (object) [
            'source' => 'legacy',
            'source_label' => 'Legacy Prediction Checklist',
            'workflow_label' => 'Recommendation',
            'title' => $item->title,
            'detail' => $item->planning_notes ?: $item->ai_output ?: 'No checklist guidance available.',
            'priority_level' => $item->priority_level,
            'status_key' => $item->status,
            'status_family' => in_array($item->status, ['complied'], true) ? 'completed' : (in_array($item->status, ['in_progress'], true) ? 'in_progress' : 'pending'),
            'target_date' => $item->target_date,
            'assignment' => $item->assignee?->full_name ?? 'Unassigned',
            'reviewer' => $item->reviewer?->full_name ?? 'Not yet reviewed',
            'completion_note' => $item->compliance_remarks ?: 'No compliance remarks yet.',
            'linked_mitigation_count' => (int) ($legacyMitigationCountByChecklist[$item->barangay_prediction_checklist_id] ?? 0),
            'progress_percent' => $item->status === 'complied' ? 100 : ($item->status === 'in_progress' ? 55 : 0),
        ];
    });

    $aiChecklistRecords = $aiActionPlanChecklists->map(function ($item) use ($aiMitigationCountByChecklist) {
        $progress = $item->checklist_progress;
        $checklistSteps = $item->items
            ->pluck('item_text')
            ->filter()
            ->values();
        $stepPreview = $checklistSteps->take(4)->map(fn ($step) => '• ' . $step)->implode(' ');
        $detailText = $item->plan_description ?: $item->source_description ?: 'No AI action-plan description available.';
        if ($stepPreview !== '') {
            $detailText .= ' Steps to follow: ' . $stepPreview;
        }

        return (object) [
            'source' => 'ai_action_plan',
            'source_label' => 'Chief-Approved AI Action Plan',
            'workflow_label' => 'Approved Barangay Checklist',
            'title' => $item->plan_title,
            'detail' => $detailText,
            'priority_level' => $item->priority_level,
            'status_key' => $item->status,
            'status_family' => ($progress['percent'] ?? 0) >= 100 ? 'completed' : (($progress['percent'] ?? 0) > 0 ? 'in_progress' : 'pending'),
            'target_date' => $item->target_date,
            'assignment' => $item->assignee?->full_name ?? 'Station command',
            'reviewer' => $item->confirmer?->full_name ?? 'Chief / Station command',
            'completion_note' => $item->confirmation_notes ?: ($progress['followed'] ?? 0) . ' of ' . ($progress['total'] ?? 0) . ' approved checklist steps already marked followed.',
            'linked_mitigation_count' => (int) ($aiMitigationCountByChecklist[$item->ai_action_plan_checklist_id] ?? 0),
            'progress_percent' => (int) ($progress['percent'] ?? 0),
        ];
    });

    $unifiedChecklistRecords = $legacyChecklistRecords
        ->concat($aiChecklistRecords)
        ->sortBy([
            fn ($item) => match ($item->priority_level) {
                'critical' => 1,
                'high' => 2,
                'medium' => 3,
                default => 4,
            },
            fn ($item) => $item->target_date?->timestamp ?? PHP_INT_MAX,
            fn ($item) => $item->title,
        ])
        ->values();

    $totalChecklistItems = (int) $unifiedChecklistRecords->count();
    $pendingChecklistItems = $unifiedChecklistRecords->where('status_family', 'pending');
    $inProgressChecklistItems = $unifiedChecklistRecords->where('status_family', 'in_progress');
    $completedChecklistItems = $unifiedChecklistRecords->where('status_family', 'completed');
    $highPriorityChecklistItems = $unifiedChecklistRecords->whereIn('priority_level', ['critical', 'high']);
    $openMitigationActions = $mitigationActions->whereIn('status', ['planned', 'in_progress', 'blocked']);
    $completedMitigationActions = $mitigationActions->where('status', 'completed');
    $highPriorityMitigations = $mitigationActions->whereIn('priority_level', ['critical', 'high']);
@endphp

<div class="checklist-center-stack" data-checklist-center-content>
    <section class="checklist-summary-grid">
        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">Unified Register</div>
                <span class="checklist-summary-icon"><i class="bi bi-diagram-3"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $totalChecklistItems }}</div>
            <div class="checklist-summary-copy">Combined legacy checklist items and AI action-plan checklists visible to the barangay.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">Pending</div>
                <span class="checklist-summary-icon"><i class="bi bi-hourglass-top"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $pendingChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Checklist records still waiting for barangay review or station confirmation.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">In Progress</div>
                <span class="checklist-summary-icon"><i class="bi bi-arrow-repeat"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $inProgressChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Operational work already moving from recommendation into concrete field execution.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">High Priority</div>
                <span class="checklist-summary-icon"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $highPriorityChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Critical and high-priority records that should remain visible during barangay coordination.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">Open Mitigation</div>
                <span class="checklist-summary-icon"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $openMitigationActions->count() }}</div>
            <div class="checklist-summary-copy">Station-managed mitigation actions currently planned, active, or blocked for this barangay.</div>
        </article>
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading">
            <div>
                <h3>Main Checklist Register</h3>
                <p>This is now the central checklist register for the barangay. It combines prediction-generated checklist records and the newer AI action-plan checklist flow in one operational view.</p>
            </div>
            <span class="tag medium"><i class="bi bi-diagram-3"></i> Unified view</span>
        </div>

        @if($unifiedChecklistRecords->isEmpty())
            <div class="empty-state">No checklist data is available for this barangay yet.</div>
        @else
            <table class="checklist-table">
                <thead>
                    <tr>
                        <th>Checklist Item</th>
                        <th>Flow Stage</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Target Date</th>
                        <th>Assignment</th>
                        <th>Completion / Links</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unifiedChecklistRecords as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->title }}</strong>
                                <div class="checklist-inline-note">{{ $item->detail }}</div>
                                <div class="checklist-inline-note">{{ $item->source_label }}</div>
                            </td>
                            <td>
                                <span class="tag medium">{{ strtoupper($item->workflow_label) }}</span>
                            </td>
                            <td><span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span></td>
                            <td><span class="tag {{ $item->status_family === 'completed' ? 'completed' : $item->status_key }}">{{ str_replace('_', ' ', strtoupper($item->status_key)) }}</span></td>
                            <td>{{ $item->target_date?->format('M d, Y') ?? 'No target date' }}</td>
                            <td>
                                <strong>{{ $item->assignment }}</strong>
                                <div class="checklist-inline-note">Reviewer: {{ $item->reviewer }}</div>
                            </td>
                            <td>
                                {{ $item->completion_note }}
                                <div class="checklist-inline-note">{{ $item->linked_mitigation_count }} linked mitigation {{ \Illuminate\Support\Str::plural('action', $item->linked_mitigation_count) }}</div>
                                <div class="checklist-inline-note">Progress: {{ $item->progress_percent }}%</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading">
            <div>
                <h3>Station Mitigation Status</h3>
                <p>Read-only view of mitigation work already created by station command for this barangay. Use this to track what has been planned, what is underway, and what still needs coordination locally.</p>
            </div>
            <span class="tag medium"><i class="bi bi-building-check"></i> Station-managed</span>
        </div>

        @if($mitigationActions->isEmpty())
            <div class="empty-state">No mitigation actions have been assigned by station command to this barangay yet.</div>
        @else
            <table class="checklist-table">
                <thead>
                    <tr>
                        <th>Mitigation Action</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Target Date</th>
                        <th>Linked Source</th>
                        <th>Proof</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mitigationActions->take(8) as $action)
                        <tr>
                            <td>
                                <strong>{{ $action->title }}</strong>
                                <div class="checklist-inline-note">{{ $action->description ?: 'No mitigation description provided.' }}</div>
                                <div class="checklist-inline-note">Created by: {{ $action->creator?->full_name ?? 'Station command' }}</div>
                            </td>
                            <td><span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span></td>
                            <td><span class="tag {{ $action->status }}">{{ str_replace('_', ' ', strtoupper($action->status)) }}</span></td>
                            <td>{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</td>
                            <td>
                                @if($action->checklist)
                                    <div class="checklist-inline-note"><strong>Checklist:</strong> {{ $action->checklist->title }}</div>
                                @endif
                                @if($action->aiActionPlanChecklist)
                                    <div class="checklist-inline-note"><strong>AI Action Plan:</strong> {{ $action->aiActionPlanChecklist->plan_title }}</div>
                                @endif
                                @if($action->riskArea)
                                    <div class="checklist-inline-note"><strong>Risk / Hazard:</strong> {{ $action->riskArea->marker?->title ?? 'Linked field record' }}</div>
                                @endif
                                @if(! $action->checklist && ! $action->aiActionPlanChecklist && ! $action->riskArea)
                                    <div class="checklist-inline-note">Direct station mitigation action.</div>
                                @endif
                            </td>
                            <td>
                                @if($action->attachments->isEmpty())
                                    <span class="checklist-inline-note">No uploaded proof yet.</span>
                                @else
                                    @foreach($action->attachments->take(3) as $attachment)
                                        <div class="checklist-inline-note">
                                            <a href="{{ \App\Support\MediaPath::url($attachment->file_path) }}" target="_blank" rel="noopener">{{ $attachment->file_name ?: 'Attachment' }}</a>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="checklist-columns" style="margin-top:18px;">
                <div class="checklist-lane">
                    <h4>Open Station Actions</h4>
                    <p>Mitigation work that still needs follow-through from station command or barangay coordination.</p>
                    <div class="checklist-lane-list">
                        @forelse($openMitigationActions->take(5) as $action)
                            <div class="checklist-lane-item">
                                <strong>{{ $action->title }}</strong>
                                <span>{{ $action->description ?: 'No mitigation description provided.' }}</span>
                                <div class="tag-row">
                                    <span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span>
                                    <span class="tag {{ $action->status }}">{{ str_replace('_', ' ', strtoupper($action->status)) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state !mt-0">No open station mitigation actions.</div>
                        @endforelse
                    </div>
                </div>

                <div class="checklist-lane">
                    <h4>Completed Station Actions</h4>
                    <p>Mitigation items already completed and recorded by station command.</p>
                    <div class="checklist-lane-list">
                        @forelse($completedMitigationActions->take(5) as $action)
                            <div class="checklist-lane-item">
                                <strong>{{ $action->title }}</strong>
                                <span>{{ $action->remarks ?: 'Completed with no additional remarks.' }}</span>
                                <div class="tag-row">
                                    <span class="tag completed">COMPLETED</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state !mt-0">No completed station mitigation actions yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="checklist-lane">
                    <h4>Priority Watch</h4>
                    <p>Critical and high-priority mitigation actions that barangay officers should keep visible.</p>
                    <div class="checklist-lane-list">
                        @forelse($highPriorityMitigations->take(5) as $action)
                            <div class="checklist-lane-item">
                                <strong>{{ $action->title }}</strong>
                                <span>{{ $action->description ?: 'No mitigation description provided.' }}</span>
                                <div class="tag-row">
                                    <span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span>
                                    <span class="tag {{ $action->status }}">{{ str_replace('_', ' ', strtoupper($action->status)) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state !mt-0">No critical or high-priority mitigation actions.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading">
            <div>
                <h3>Operational Checklist Lanes</h3>
                <p>Quick grouped view so the barangay can see which records still need action, which are underway, and which are already completed.</p>
            </div>
        </div>

        <div class="checklist-columns">
            <div class="checklist-lane">
                <h4>Pending</h4>
                <p>Records waiting for assignment, validation, or first action.</p>
                <div class="checklist-lane-list">
                    @forelse($pendingChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->detail }}</span>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                                <span class="tag medium">{{ strtoupper($item->workflow_label) }}</span>
                            </div>
                            <div class="checklist-inline-note">{{ $item->linked_mitigation_count }} linked mitigation {{ \Illuminate\Support\Str::plural('action', $item->linked_mitigation_count) }}</div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No pending checklist records.</div>
                    @endforelse
                </div>
            </div>

            <div class="checklist-lane">
                <h4>In Progress</h4>
                <p>Records currently being worked on by the barangay or station side.</p>
                <div class="checklist-lane-list">
                    @forelse($inProgressChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->detail }}</span>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                                <span class="tag medium">{{ strtoupper($item->workflow_label) }}</span>
                            </div>
                            <div class="checklist-inline-note">Progress: {{ $item->progress_percent }}%</div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No checklist records are currently in progress.</div>
                    @endforelse
                </div>
            </div>

            <div class="checklist-lane">
                <h4>Completed</h4>
                <p>Records already complied with or confirmed through the workflow.</p>
                <div class="checklist-lane-list">
                    @forelse($completedChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->completion_note }}</span>
                            <div class="tag-row">
                                <span class="tag completed">COMPLETED</span>
                                <span class="tag medium">{{ strtoupper($item->workflow_label) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No checklist records have been completed yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
