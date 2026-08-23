@php
    $draft = $mitigationDraft ?? [];
@endphp

@if(!empty($draft['workflow_note']))
    <div class="mitigation-field mitigation-field--full">
        <div style="padding:12px 14px; border-radius:14px; background:#fff7ed; border:1px solid rgba(249,115,22,.18); color:#9a3412; font-size:.78rem; line-height:1.55;">
            <strong>Workflow:</strong> {{ $draft['workflow_note'] }}
        </div>
    </div>
@endif

<div class="mitigation-form-section"><i class="fa-solid fa-clipboard-list"></i> Plan details <small>What action is needed?</small></div>
<div class="mitigation-field">
    <label for="stationMitigationScope">Coverage Scope</label>
    <select id="stationMitigationScope" name="scope_type" required>
        <option value="barangay" @selected(old('scope_type', $draft['scope_type'] ?? 'barangay') === 'barangay')>Selected barangay</option>
        <option value="stationwide" @selected(old('scope_type', $draft['scope_type'] ?? '') === 'stationwide')>All barangays covered by this station</option>
        <option value="citywide" @selected(old('scope_type', $draft['scope_type'] ?? '') === 'citywide')>All stations in the selected barangay's city</option>
    </select>
</div>
<div class="mitigation-field">
    <label for="stationMitigationBarangay">Barangay</label>
    <select id="stationMitigationBarangay" name="barangay_id" required>
        <option value="">Select barangay</option>
        @foreach($managedBarangays as $barangayOption)
            <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $draft['barangay_id'] ?? '') === (string) $barangayOption->barangay_id)>
                {{ $barangayOption->barangay_name }}{{ !empty($barangayOption->city_name) ? ', ' . $barangayOption->city_name : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationTitle">Mitigation Title</label>
    <input id="stationMitigationTitle" name="title" type="text" value="{{ old('title', $draft['title'] ?? '') }}" required>
</div>

<div class="mitigation-field">
    <label for="stationMitigationCategory">Category</label>
    <select id="stationMitigationCategory" name="category" required>
        @foreach($mitigationCategoryOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('category', $draft['category'] ?? 'other') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationPriority">Priority</label>
    <select id="stationMitigationPriority" name="priority_level" required>
        @foreach($mitigationPriorityOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('priority_level', $draft['priority_level'] ?? 'medium') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationStatus">Status</label>
    <select id="stationMitigationStatus" name="status" required>
        @foreach(($mitigationStatusLabels ?? $mitigationStatusOptions) as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $draft['status'] ?? 'draft') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationTargetDate">Target Date</label>
    <input id="stationMitigationTargetDate" name="target_date" type="date" value="{{ old('target_date', $draft['target_date'] ?? '') }}">
</div>

<div class="mitigation-form-section"><i class="fa-solid fa-users"></i> Ownership and links <small>Who is responsible and what is affected?</small></div>
<div class="mitigation-field">
    <label for="stationMitigationResponsibleTeam">Responsible Team</label>
    <select id="stationMitigationResponsibleTeam" name="responsible_team_id">
        <option value="">Not assigned</option>
        @foreach(($stationTeams ?? collect()) as $team)
            <option value="{{ $team->team_id }}" @selected((string) old('responsible_team_id', $draft['responsible_team_id'] ?? '') === (string) $team->team_id)>
                {{ $team->team_name ?? ('Team #' . $team->team_id) }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationResponsibleUser">Responsible Person</label>
    <select id="stationMitigationResponsibleUser" name="responsible_user_id">
        <option value="">Not assigned</option>
        @foreach(($stationUsers ?? collect()) as $stationUser)
            <option value="{{ $stationUser->user_id }}" @selected((string) old('responsible_user_id', $draft['responsible_user_id'] ?? '') === (string) $stationUser->user_id)>
                {{ $stationUser->full_name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationSource">Source</label>
    <select id="stationMitigationSource" name="source_type">
        @foreach(($mitigationSourceOptions ?? []) as $value => $label)
            <option value="{{ $value }}" @selected(old('source_type', $draft['source_type'] ?? 'manual') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationRiskArea">Linked Risk / Hazard</label>
    <select id="stationMitigationRiskArea" name="risk_area_id">
        <option value="">None</option>
        @foreach($riskAreas as $riskArea)
            <option value="{{ $riskArea->risk_area_id }}" data-barangay-id="{{ $riskArea->barangayHall?->barangay_id }}" @selected((string) old('risk_area_id', $draft['risk_area_id'] ?? '') === (string) $riskArea->risk_area_id)>
                {{ $riskArea->barangayHall?->barangay?->barangay_name ?? 'Barangay' }} - {{ $riskArea->marker?->title ?? 'Risk area' }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationChecklist">Linked Barangay Checklist</label>
    <select id="stationMitigationChecklist" name="barangay_prediction_checklist_id">
        <option value="">None</option>
        @foreach($predictionChecklists as $item)
            <option value="{{ $item->barangay_prediction_checklist_id }}" data-barangay-id="{{ $item->barangay_id }}" @selected((string) old('barangay_prediction_checklist_id', $draft['barangay_prediction_checklist_id'] ?? '') === (string) $item->barangay_prediction_checklist_id)>
                {{ $item->barangay?->barangay_name ?? 'Barangay' }} - {{ $item->title }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationAiChecklist">Linked AI Action Plan</label>
    <select id="stationMitigationAiChecklist" name="ai_action_plan_checklist_id">
        <option value="">None</option>
        @foreach($aiActionPlanOptions as $item)
            <option value="{{ $item->ai_action_plan_checklist_id }}" data-barangay-id="{{ $item->barangay_id }}" @selected((string) old('ai_action_plan_checklist_id', $draft['ai_action_plan_checklist_id'] ?? '') === (string) $item->ai_action_plan_checklist_id)>
                {{ collect($managedBarangays)->firstWhere('barangay_id', $item->barangay_id)?->barangay_name ?? 'Barangay' }} - {{ $item->plan_title }}
            </option>
        @endforeach
    </select>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationDescription">Objective / Mitigation Plan</label>
    <textarea id="stationMitigationDescription" name="description" required>{{ old('description', $draft['description'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationRemarks">Planning Remarks</label>
    <textarea id="stationMitigationRemarks" name="remarks">{{ old('remarks', $draft['remarks'] ?? '') }}</textarea>
</div>

<div class="mitigation-form-section"><i class="fa-solid fa-screwdriver-wrench"></i> Delivery and evaluation <small>Record progress, proof, and results.</small></div>
<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationImplementationNotes">Implementation Updates</label>
    <textarea id="stationMitigationImplementationNotes" name="implementation_notes">{{ old('implementation_notes', $draft['implementation_notes'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationCompletionNotes">Completion Notes</label>
    <textarea id="stationMitigationCompletionNotes" name="completion_notes">{{ old('completion_notes', $draft['completion_notes'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationOutcome">Outcome / Result</label>
    <textarea id="stationMitigationOutcome" name="outcome_result">{{ old('outcome_result', $draft['outcome_result'] ?? '') }}</textarea>
</div>

<div class="mitigation-field">
    <label for="stationMitigationEffectiveness">Effectiveness</label>
    <select id="stationMitigationEffectiveness" name="effectiveness">
        @foreach(($mitigationEffectivenessOptions ?? []) as $value => $label)
            <option value="{{ $value }}" @selected(old('effectiveness', $draft['effectiveness'] ?? 'pending_evaluation') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationFollowUpRequired">Follow-up Required</label>
    <select id="stationMitigationFollowUpRequired" name="follow_up_required">
        <option value="0" @selected((string) old('follow_up_required', $draft['follow_up_required'] ?? '0') === '0')>No</option>
        <option value="1" @selected((string) old('follow_up_required', $draft['follow_up_required'] ?? '0') === '1')>Yes</option>
    </select>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationFollowUpNotes">Follow-up Notes</label>
    <textarea id="stationMitigationFollowUpNotes" name="follow_up_notes">{{ old('follow_up_notes', $draft['follow_up_notes'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationAttachments">Proof / Attachments</label>
    <input id="stationMitigationAttachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
</div>
