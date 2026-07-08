@php
    $draft = $mitigationDraft ?? [];
@endphp

<div class="mitigation-field">
    <label for="stationMitigationBarangay">Barangay</label>
    <select id="stationMitigationBarangay" name="barangay_id" required>
        <option value="">Select barangay</option>
        @foreach($managedBarangays as $barangayOption)
            <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $draft['barangay_id'] ?? '') === (string) $barangayOption->barangay_id)>
                {{ $barangayOption->barangay_name }}{{ $barangayOption->city ? ', ' . $barangayOption->city->city_name : '' }}
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
        @foreach($mitigationStatusOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $draft['status'] ?? 'planned') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mitigation-field">
    <label for="stationMitigationTargetDate">Target Date</label>
    <input id="stationMitigationTargetDate" name="target_date" type="date" value="{{ old('target_date', $draft['target_date'] ?? '') }}">
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
    <label for="stationMitigationDescription">Description / Mitigation Plan</label>
    <textarea id="stationMitigationDescription" name="description" required>{{ old('description', $draft['description'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationRemarks">Remarks</label>
    <textarea id="stationMitigationRemarks" name="remarks">{{ old('remarks', $draft['remarks'] ?? '') }}</textarea>
</div>

<div class="mitigation-field mitigation-field--full">
    <label for="stationMitigationAttachments">Proof / Attachments</label>
    <input id="stationMitigationAttachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
</div>
