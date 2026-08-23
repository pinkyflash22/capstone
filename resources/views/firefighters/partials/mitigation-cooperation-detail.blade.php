@php
    $isStationWide = $action->scope_type === 'stationwide' || ($action->aiActionPlanChecklist
        && ! $action->aiActionPlanChecklist->barangay_id
        && (int) $action->aiActionPlanChecklist->station_id === (int) $stationOfficer->station_id);
    $isCityWide = $action->scope_type === 'citywide';
    $displayStatus = $action->completion_approval_status === 'pending' ? 'pending approval' : str_replace('_', ' ', $action->status);
@endphp

<div class="coop-detail-grid">
    <div class="coop-detail wide"><span>Mitigation</span><strong>{{ $action->title }}</strong></div>
    <div class="coop-detail wide"><span>Implementation guidance</span><strong>{{ $action->description ?: 'No description recorded.' }}</strong></div>
    <div class="coop-detail"><span>Coverage</span><strong>{{ $isCityWide ? 'Citywide shared plan' : ($isStationWide ? 'Stationwide plan' : ($action->barangay?->barangay_name ?? 'Not assigned')) }}</strong></div>
    <div class="coop-detail"><span>Status</span><strong data-mitigation-detail-status>{{ strtoupper($displayStatus) }}</strong></div>
    <div class="coop-detail"><span>Priority</span><strong>{{ strtoupper($action->priority_level) }}</strong></div>
    <div class="coop-detail"><span>Target</span><strong>{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</strong></div>
    <div class="coop-detail wide"><span>Responsible partner</span><strong>{{ $action->responsibleTeam?->team_name ?? $action->responsibleUser?->full_name ?? 'Station-barangay cooperation' }}</strong></div>
    <div class="coop-detail wide"><span>Linked plan</span><strong>{{ $action->aiActionPlanChecklist?->plan_title ?? $action->checklist?->title ?? 'Direct BFP mitigation' }}</strong></div>
    <div class="coop-detail"><span>Attachments</span><strong>{{ $action->attachments->count() }} file(s)</strong></div>
    <div class="coop-detail"><span>Issued by</span><strong>{{ $action->creator?->full_name ?? 'Station command' }}</strong></div>
</div>

<form class="coop-mitigation-form" data-mitigation-update data-mitigation-id="{{ $action->getKey() }}" action="{{ route('firefighter.barangay-cooperation.mitigations.update', $action->getKey()) }}">
    <div class="coop-mitigation-form__head"><div><h3>Field implementation update</h3><p>Update progress without changing the command-issued plan.</p></div><span><i class="fa-solid fa-shield-halved"></i> BFP verification</span></div>
    <div class="coop-mitigation-fields">
        <label><span>Implementation status</span><select name="status" required><option value="in_progress" @selected($action->status === 'in_progress' || in_array($action->status, ['planned','approved'], true))>In progress</option><option value="blocked" @selected($action->status === 'blocked')>Blocked</option><option value="completed" @selected($action->status === 'completed')>Completed</option></select></label>
        <label><span>Effectiveness</span><select name="effectiveness"><option value="pending_evaluation" @selected(($action->effectiveness ?: 'pending_evaluation') === 'pending_evaluation')>Pending evaluation</option><option value="effective" @selected($action->effectiveness === 'effective')>Effective</option><option value="partially_effective" @selected($action->effectiveness === 'partially_effective')>Partially effective</option><option value="ineffective" @selected($action->effectiveness === 'ineffective')>Ineffective</option></select></label>
        <label class="wide"><span>Implementation notes</span><textarea name="implementation_notes" maxlength="3000" required placeholder="Describe what was checked or implemented in the field.">{{ $action->implementation_notes }}</textarea></label>
        <label class="wide"><span>Completion notes</span><textarea name="completion_notes" maxlength="3000" placeholder="Required when marking the mitigation completed.">{{ $action->completion_notes }}</textarea></label>
        <label class="wide"><span>Outcome or result</span><textarea name="outcome_result" maxlength="3000" placeholder="Record the observed result of the mitigation.">{{ $action->outcome_result }}</textarea></label>
        <label><span>Follow-up required</span><select name="follow_up_required"><option value="0" @selected(! $action->follow_up_required)>No</option><option value="1" @selected($action->follow_up_required)>Yes</option></select></label>
        <label class="wide"><span>Follow-up notes</span><textarea name="follow_up_notes" maxlength="3000" placeholder="Required when follow-up is needed.">{{ $action->follow_up_notes }}</textarea></label>
    </div>
    <div class="coop-mitigation-form__footer"><small>Core plan details remain controlled by station command.</small><button type="submit"><i class="fa-solid fa-floppy-disk"></i> Save progress</button></div>
</form>

<h3 class="coop-timeline-title">Mitigation activity history</h3>
<div class="coop-timeline" data-mitigation-timeline>
    @forelse($action->activities as $activity)
        <article class="coop-timeline__item">
            <div class="coop-timeline__head"><strong>{{ $activity->actor?->full_name ?? 'Authorized user' }}</strong><time>{{ $activity->created_at?->format('M d, Y h:i A') }}</time></div>
            <p>{{ ucwords(str_replace('_', ' ', $activity->event_type)) }}@if($activity->old_status || $activity->new_status) · {{ str_replace('_', ' ', $activity->old_status ?: 'new') }} → {{ str_replace('_', ' ', $activity->new_status ?: 'unchanged') }}@endif{{ $activity->notes ? ' · '.$activity->notes : '' }}</p>
        </article>
    @empty
        <article class="coop-timeline__item"><p>No mitigation updates have been recorded yet.</p></article>
    @endforelse
</div>
