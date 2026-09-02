<div class="checklist-detail-grid">
    <div class="wide"><span>Checklist</span><strong>{{ $item->title }}</strong></div>
    <div class="wide"><span>Guidance</span><strong>{{ $item->detail }}</strong></div>
    <div><span>Priority</span><strong>{{ strtoupper($item->priority) }}</strong></div>
    <div><span>Status</span><strong>{{ strtoupper(str_replace('_', ' ', $item->status)) }}</strong></div>
    <div><span>Assigned to</span><strong>{{ $item->assignee }}</strong></div>
    <div><span>Reviewer</span><strong>{{ $item->reviewer }}</strong></div>
    <div><span>Target</span><strong>{{ $item->target?->format('M d, Y') ?? 'No target date' }}</strong></div>
    <div><span>Progress</span><strong>{{ $item->progress }}%</strong></div>

    @if($item->steps->isNotEmpty())
        <div class="wide">
            <span>Steps assigned to your barangay</span>
            <ol class="checklist-detail-steps">
                @foreach($item->steps as $step)
                    <li>
                        <form method="POST" action="{{ route('barangay.checklists.items.update', [$item->checklist_id, $step->getKey()]) }}" class="checklist-step-form">
                            @csrf
                            @method('PATCH')
                            <strong class="checklist-step-text">{{ $step->item_text }}</strong>
                            <div class="checklist-step-controls">
                                <select name="status" aria-label="Status for {{ $step->item_text }}">
                                    <option value="pending" @selected($step->status === 'pending')>Pending</option>
                                    <option value="followed" @selected($step->status === 'followed')>Followed</option>
                                    <option value="not_followed" @selected($step->status === 'not_followed')>Not followed</option>
                                </select>
                                <input type="text" name="review_notes" value="{{ $step->review_notes }}" maxlength="1000" placeholder="Optional note" aria-label="Note for {{ $step->item_text }}">
                                <button type="submit">Save</button>
                            </div>
                        </form>

                        @if($step->visits->isNotEmpty())
                            <div class="barangay-visit-list">
                                @foreach($step->visits as $visit)
                                    <article class="barangay-visit-card">
                                        <div class="barangay-visit-card__head">
                                            <strong><i class="bi bi-person-check-fill"></i> {{ $visit->firefighter?->full_name ?? 'BFP firefighter' }}</strong>
                                            <time>{{ $visit->visited_at?->format('M d, Y h:i A') }}</time>
                                        </div>
                                        <p>Marked this step as <b>{{ str_replace('_', ' ', $visit->verified_status) }}</b>{{ $visit->visit_notes ? ' · '.$visit->visit_notes : '' }}</p>
                                        @if($visit->photos->isNotEmpty())
                                            <div class="barangay-visit-photos">
                                                @foreach($visit->photos as $photo)
                                                    <a href="{{ $photo->url }}" target="_blank" rel="noopener"><img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"></a>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($visit->signoff_status === 'pending')
                                            <form method="POST" action="{{ route('barangay.checklists.visits.sign-off', $visit->getKey()) }}" class="barangay-signoff-form">
                                                @csrf
                                                @method('PATCH')
                                                <select name="signoff_status" aria-label="Visit sign-off decision"><option value="accepted">Accept verification</option><option value="needs_follow_up">Needs follow-up</option></select>
                                                <input type="text" name="signoff_notes" maxlength="1000" placeholder="Sign-off note; required for follow-up">
                                                <button type="submit"><i class="bi bi-pen-fill"></i> Sign off</button>
                                            </form>
                                        @else
                                            <div class="barangay-signoff-state {{ $visit->signoff_status }}"><i class="bi bi-patch-check-fill"></i> {{ str_replace('_', ' ', $visit->signoff_status) }} by {{ $visit->signatory?->full_name ?? 'barangay officer' }}{{ $visit->signoff_notes ? ' · '.$visit->signoff_notes : '' }}</div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    @if(!empty($item->legacy_checklist_id))
        <div class="wide">
            <span>Update checklist oversight</span>
            <form method="POST" action="{{ route('barangay.checklists.prediction.update', $item->legacy_checklist_id) }}" class="barangay-record-update-form">
                @csrf
                @method('PATCH')
                <label>Status
                    <select name="status" required>
                        @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'complied' => 'Complied', 'not_complied' => 'Not complied', 'deferred' => 'Deferred'] as $value => $label)
                            <option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Target date<input type="date" name="target_date" value="{{ $item->target?->format('Y-m-d') }}"></label>
                <label class="full">Planning notes<textarea name="planning_notes" maxlength="3000" rows="3">{{ $item->planning_notes }}</textarea></label>
                <label class="full">Compliance remarks<textarea name="compliance_remarks" maxlength="3000" rows="3">{{ $item->compliance_remarks }}</textarea></label>
                <button type="submit"><i class="bi bi-save-fill"></i> Save checklist</button>
            </form>
        </div>
    @endif

    <div class="wide"><span>Compliance note</span><strong>{{ $item->note }}</strong></div>
</div>
