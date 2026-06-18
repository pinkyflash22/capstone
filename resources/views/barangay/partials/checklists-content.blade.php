@php
    $totalChecklistItems = (int) $predictionChecklists->count();
    $pendingChecklistItems = $predictionChecklists->where('status', 'pending');
    $inProgressChecklistItems = $predictionChecklists->where('status', 'in_progress');
    $completedChecklistItems = $predictionChecklists->where('status', 'complied');
    $highPriorityChecklistItems = $predictionChecklists->whereIn('priority_level', ['critical', 'high']);
@endphp

<div class="checklist-center-stack" data-checklist-center-content>
    <section class="checklist-summary-grid">
        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">Total Checklist Items</div>
                <span class="checklist-summary-icon"><i class="bi bi-list-check"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $totalChecklistItems }}</div>
            <div class="checklist-summary-copy">All prediction-linked barangay checklist items currently available in the system.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">Pending</div>
                <span class="checklist-summary-icon"><i class="bi bi-hourglass-top"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $pendingChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Checklist items that have not yet started and should be reviewed by the barangay team.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">In Progress</div>
                <span class="checklist-summary-icon"><i class="bi bi-arrow-repeat"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $inProgressChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Operational actions or inspections currently being worked on in the field.</div>
        </article>

        <article class="checklist-summary-card">
            <div class="checklist-summary-head">
                <div class="checklist-summary-label">High Priority</div>
                <span class="checklist-summary-icon"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <div class="checklist-summary-value">{{ $highPriorityChecklistItems->count() }}</div>
            <div class="checklist-summary-copy">Critical and high-priority items that should remain visible during barangay coordination.</div>
        </article>
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading">
            <div>
                <h3>Main Checklist Register</h3>
                <p>This is the primary page for all prediction checklist data tied to the barangay. Use it as the single central reference for inspection, awareness, and action tracking.</p>
            </div>
            <span class="tag medium"><i class="bi bi-diagram-3"></i> Central view</span>
        </div>

        @if($predictionChecklists->isEmpty())
            <div class="empty-state">No prediction checklist data is available for this barangay yet.</div>
        @else
            <table class="checklist-table">
                <thead>
                    <tr>
                        <th>Checklist Item</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Target Date</th>
                        <th>Assignment</th>
                        <th>Compliance Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predictionChecklists as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->title }}</strong>
                                <div class="checklist-inline-note">{{ $item->planning_notes ?: $item->ai_output ?: 'No checklist guidance available.' }}</div>
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $item->checklist_type ?? 'recommendation')) }}</td>
                            <td><span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span></td>
                            <td><span class="tag {{ $item->status }}">{{ str_replace('_', ' ', strtoupper($item->status)) }}</span></td>
                            <td>{{ $item->target_date?->format('M d, Y') ?? 'No target date' }}</td>
                            <td>
                                <strong>{{ $item->assignee?->full_name ?? 'Unassigned' }}</strong>
                                <div class="checklist-inline-note">Reviewer: {{ $item->reviewer?->full_name ?? 'Not yet reviewed' }}</div>
                            </td>
                            <td>{{ $item->compliance_remarks ?: 'No compliance remarks yet.' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="checklist-panel-card">
        <div class="checklist-panel-heading">
            <div>
                <h3>Operational Checklist Lanes</h3>
                <p>Quick grouped view so the barangay can see which items still need action, which are underway, and which are already completed.</p>
            </div>
        </div>

        <div class="checklist-columns">
            <div class="checklist-lane">
                <h4>Pending</h4>
                <p>Items waiting for assignment, validation, or first action.</p>
                <div class="checklist-lane-list">
                    @forelse($pendingChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->planning_notes ?: $item->ai_output ?: 'No detail provided.' }}</span>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No pending checklist items.</div>
                    @endforelse
                </div>
            </div>

            <div class="checklist-lane">
                <h4>In Progress</h4>
                <p>Items currently being worked on by the barangay team.</p>
                <div class="checklist-lane-list">
                    @forelse($inProgressChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->planning_notes ?: $item->ai_output ?: 'No detail provided.' }}</span>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No checklist items are currently in progress.</div>
                    @endforelse
                </div>
            </div>

            <div class="checklist-lane">
                <h4>Completed / Complied</h4>
                <p>Items already completed and marked complied by the barangay.</p>
                <div class="checklist-lane-list">
                    @forelse($completedChecklistItems->take(6) as $item)
                        <div class="checklist-lane-item">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->compliance_remarks ?: 'Completed with no added compliance note.' }}</span>
                            <div class="tag-row">
                                <span class="tag complied">COMPLIED</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state !mt-0">No checklist items have been marked complied yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
