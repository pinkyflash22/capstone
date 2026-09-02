<div class="checklist-detail-grid">
    <div class="wide"><span>Mitigation</span><strong>{{ $action->title }}</strong></div>
    <div class="wide"><span>Description</span><strong>{{ $action->description ?: 'No description recorded.' }}</strong></div>
    <div><span>Priority</span><strong>{{ strtoupper($action->priority_level) }}</strong></div>
    <div><span>Status</span><strong>{{ $action->completion_approval_status === 'pending' ? 'PENDING CHIEF APPROVAL' : strtoupper(str_replace('_', ' ', $action->status)) }}</strong></div>
    <div><span>Target</span><strong>{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</strong></div>
    <div><span>Created by</span><strong>{{ $action->creator?->full_name ?? 'Station command' }}</strong></div>
    <div class="wide"><span>Linked source</span><strong>{{ $action->checklist?->title ?? $action->aiActionPlanChecklist?->plan_title ?? $action->riskArea?->marker?->title ?? 'Direct mitigation' }}</strong></div>
    <div class="wide"><span>Files</span><strong>{{ $action->attachments->count() }} attachment(s)</strong></div>

    @if(!in_array($action->status, ['completed', 'cancelled'], true) && $action->completion_approval_status !== 'pending')
        <div class="wide">
            <span>Update mitigation oversight</span>
            <form method="POST" action="{{ route('barangay.mitigations.update', $action->getKey()) }}" enctype="multipart/form-data" class="barangay-record-update-form">
                @csrf
                @method('PATCH')
                <label>Status
                    <select name="status" required>
                        <option value="planned" @selected($action->status === 'planned')>Planned</option>
                        <option value="in_progress" @selected(in_array($action->status, ['approved', 'in_progress'], true))>In progress</option>
                        <option value="blocked" @selected($action->status === 'blocked')>Blocked</option>
                        <option value="completed">Request completion</option>
                    </select>
                </label>
                <label>Effectiveness
                    <select name="effectiveness">
                        @foreach(['pending_evaluation' => 'Pending evaluation', 'effective' => 'Effective', 'partially_effective' => 'Partially effective', 'ineffective' => 'Ineffective'] as $value => $label)
                            <option value="{{ $value }}" @selected(($action->effectiveness ?: 'pending_evaluation') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="full">Implementation notes<textarea name="implementation_notes" maxlength="3000" rows="3" required>{{ $action->implementation_notes }}</textarea></label>
                <label class="full">Blocked reason<textarea name="blocked_reason" maxlength="2000" rows="2">{{ $action->blocked_reason }}</textarea></label>
                <label class="full">Completion notes<textarea name="completion_notes" maxlength="3000" rows="2">{{ $action->completion_notes }}</textarea></label>
                <label class="full">Outcome/result<textarea name="outcome_result" maxlength="3000" rows="2">{{ $action->outcome_result }}</textarea></label>
                <label><input type="checkbox" name="follow_up_required" value="1" @checked($action->follow_up_required)> Follow-up required</label>
                <label>Follow-up date<input type="date" name="follow_up_date" value="{{ $action->follow_up_date?->format('Y-m-d') }}"></label>
                <label class="full">Follow-up notes<textarea name="follow_up_notes" maxlength="3000" rows="2">{{ $action->follow_up_notes }}</textarea></label>
                <label class="full">Proof files<input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx"></label>
                <small class="full">Requesting completion requires outcome details, effectiveness, and at least one proof file.</small>
                <button type="submit"><i class="bi bi-save-fill"></i> Save mitigation</button>
            </form>
        </div>
    @elseif($action->completion_approval_status === 'pending')
        <div class="wide"><span>Oversight status</span><strong>Completion evidence has been submitted and is waiting for the station chief's decision.</strong></div>
    @endif
</div>
