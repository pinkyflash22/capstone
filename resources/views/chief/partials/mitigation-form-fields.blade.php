@php
    $draft = $mitigationDraft ?? [];
    $fieldPrefix = $fieldPrefix ?? 'mitigation';
    $formMode = $formMode ?? 'create';
    $fieldId = static fn (string $name): string => $fieldPrefix . ucfirst($name);
@endphp

@if(!empty($draft['workflow_note']))
    <div class="mitigation-workflow-note">
        <span class="mitigation-workflow-note__icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
        <div><strong>Generated workflow</strong><p>{{ $draft['workflow_note'] }}</p></div>
    </div>
@endif

@if($formMode === 'create')
    <section class="mitigation-form-block" data-checklist-draft-builder>
        <header class="mitigation-form-block__head">
            <span class="mitigation-form-block__icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
            <div><h4>Generate Mitigation from Checklist</h4><p>Reuse an existing recommended checklist without making another AI request.</p></div>
            <span class="mitigation-form-block__step">AI</span>
        </header>
        <div class="mitigation-form-block__grid">
            <div class="mitigation-field mitigation-field--full">
                <label for="{{ $fieldId('draftChecklistSource') }}"><i class="fa-solid fa-list-check"></i><span>Existing checklist</span></label>
                <select id="{{ $fieldId('draftChecklistSource') }}" data-checklist-draft-source>
                    <option value="">Select a generated checklist</option>
                    @foreach($aiActionPlanOptions as $item)
                        <option value="{{ $item->ai_action_plan_checklist_id }}" @selected((string) ($draft['ai_action_plan_checklist_id'] ?? '') === (string) $item->ai_action_plan_checklist_id)>{{ collect($managedBarangays)->firstWhere('barangay_id', $item->barangay_id)?->barangay_name ?? 'Barangay' }} - {{ $item->plan_title }}</option>
                    @endforeach
                </select>
                <small>Selecting a checklist loads its already stored recommended actions.</small>
            </div>
            <div class="mitigation-field mitigation-field--full" data-checklist-draft-items hidden></div>
            <div class="mitigation-field mitigation-field--full">
                <button type="button" class="hero-btn" data-generate-checklist-draft disabled><i class="fa-solid fa-wand-magic-sparkles"></i><span>Generate AI-Assisted Mitigation Draft</span></button>
                <small data-checklist-draft-status>Choose a checklist to begin. Nothing will be saved until you submit this form.</small>
            </div>
        </div>
    </section>
@endif

<section class="mitigation-form-block" aria-labelledby="{{ $fieldId('planningHeading') }}">
    <header class="mitigation-form-block__head">
        <span class="mitigation-form-block__icon"><i class="fa-solid fa-clipboard-check"></i></span>
        <div><h4 id="{{ $fieldId('planningHeading') }}">Plan and coverage</h4><p>Define the action, where it applies, and when it should be completed.</p></div>
        <span class="mitigation-form-block__step">01</span>
    </header>
    <div class="mitigation-form-block__grid">
        <div class="mitigation-field">
            <label for="{{ $fieldId('scope') }}"><i class="fa-solid fa-layer-group"></i><span>Coverage scope</span></label>
            <select id="{{ $fieldId('scope') }}" name="scope_type" required>
                <option value="barangay" @selected(old('scope_type', $draft['scope_type'] ?? 'barangay') === 'barangay')>Selected barangay</option>
                <option value="stationwide" @selected(old('scope_type', $draft['scope_type'] ?? '') === 'stationwide')>All barangays covered by this station</option>
                <option value="citywide" @selected(old('scope_type', $draft['scope_type'] ?? '') === 'citywide')>All stations in the selected barangay's city</option>
            </select>
            <small>Choose how widely this plan will be applied.</small>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('barangay') }}"><i class="fa-solid fa-map-location-dot"></i><span>Reference barangay</span></label>
            <select id="{{ $fieldId('barangay') }}" name="barangay_id" required>
                <option value="">Select barangay</option>
                @foreach($managedBarangays as $barangayOption)
                    <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $draft['barangay_id'] ?? '') === (string) $barangayOption->barangay_id)>{{ $barangayOption->barangay_name }}{{ !empty($barangayOption->city_name) ? ', ' . $barangayOption->city_name : '' }}</option>
                @endforeach
            </select>
            <small>Used to match linked risks and checklists.</small>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('title') }}"><i class="fa-solid fa-heading"></i><span>Mitigation title</span></label>
            <input id="{{ $fieldId('title') }}" name="title" type="text" value="{{ old('title', $draft['title'] ?? '') }}" placeholder="Use a short, action-focused title" required>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('category') }}"><i class="fa-solid fa-shapes"></i><span>Category</span></label>
            <select id="{{ $fieldId('category') }}" name="category" required>@foreach($mitigationCategoryOptions as $value => $label)<option value="{{ $value }}" @selected(old('category', $draft['category'] ?? 'other') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('priority') }}"><i class="fa-solid fa-arrow-up-wide-short"></i><span>Priority</span></label>
            <select id="{{ $fieldId('priority') }}" name="priority_level" required>@foreach($mitigationPriorityOptions as $value => $label)<option value="{{ $value }}" @selected(old('priority_level', $draft['priority_level'] ?? 'medium') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('status') }}"><i class="fa-solid fa-bars-progress"></i><span>Current stage</span></label>
            <select id="{{ $fieldId('status') }}" name="status" required>@foreach(($mitigationStatusLabels ?? $mitigationStatusOptions) as $value => $label)<option value="{{ $value }}" @selected(old('status', $draft['status'] ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('targetDate') }}"><i class="fa-regular fa-calendar-check"></i><span>Target date</span></label>
            <input id="{{ $fieldId('targetDate') }}" name="target_date" type="date" value="{{ old('target_date', $draft['target_date'] ?? '') }}">
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('description') }}"><i class="fa-solid fa-bullseye"></i><span>Objective and mitigation plan</span></label>
            <textarea id="{{ $fieldId('description') }}" name="description" rows="5" placeholder="Describe the intended risk reduction and the action to be carried out" required>{{ old('description', $draft['description'] ?? '') }}</textarea>
            @if($formMode === 'create')
                <div data-recommended-actions-preview hidden></div>
            @endif
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('baselineCondition') }}"><i class="fa-solid fa-camera-retro"></i><span>Condition before work</span><em>Optional for drafts</em></label>
            <textarea id="{{ $fieldId('baselineCondition') }}" name="baseline_condition" rows="3" placeholder="Record the current condition so the result can be compared later">{{ old('baseline_condition', $draft['baseline_condition'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('expectedResult') }}"><i class="fa-solid fa-bullseye"></i><span>Expected result</span><em>Optional for drafts</em></label>
            <textarea id="{{ $fieldId('expectedResult') }}" name="expected_result" rows="3" placeholder="State the result that will show this plan worked">{{ old('expected_result', $draft['expected_result'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('remarks') }}"><i class="fa-regular fa-note-sticky"></i><span>Planning remarks</span><em>Optional</em></label>
            <textarea id="{{ $fieldId('remarks') }}" name="remarks" rows="3" placeholder="Add constraints, coordination notes, or planning context">{{ old('remarks', $draft['remarks'] ?? '') }}</textarea>
        </div>
    </div>
</section>

<section class="mitigation-form-block" aria-labelledby="{{ $fieldId('ownershipHeading') }}">
    <header class="mitigation-form-block__head">
        <span class="mitigation-form-block__icon is-blue"><i class="fa-solid fa-people-group"></i></span>
        <div><h4 id="{{ $fieldId('ownershipHeading') }}">Ownership and traceability</h4><p>Assign responsibility and connect the plan to the evidence that created it.</p></div>
        <span class="mitigation-form-block__step">02</span>
    </header>
    <div class="mitigation-form-block__grid">
        <div class="mitigation-field">
            <label for="{{ $fieldId('team') }}"><i class="fa-solid fa-helmet-safety"></i><span>Responsible team</span><em>Optional</em></label>
            <select id="{{ $fieldId('team') }}" name="responsible_team_id"><option value="">Not assigned</option>@foreach(($stationTeams ?? collect()) as $team)<option value="{{ $team->team_id }}" @selected((string) old('responsible_team_id', $draft['responsible_team_id'] ?? '') === (string) $team->team_id)>{{ $team->team_name ?? ('Team #' . $team->team_id) }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('person') }}"><i class="fa-solid fa-user-shield"></i><span>Responsible person</span><em>Optional</em></label>
            <select id="{{ $fieldId('person') }}" name="responsible_user_id"><option value="">Not assigned</option>@foreach(($stationUsers ?? collect()) as $stationUser)<option value="{{ $stationUser->user_id }}" @selected((string) old('responsible_user_id', $draft['responsible_user_id'] ?? '') === (string) $stationUser->user_id)>{{ $stationUser->full_name }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('source') }}"><i class="fa-solid fa-code-branch"></i><span>Plan source</span></label>
            <select id="{{ $fieldId('source') }}" name="source_type">@foreach(($mitigationSourceOptions ?? []) as $value => $label)<option value="{{ $value }}" @selected(old('source_type', $draft['source_type'] ?? 'manual') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('riskArea') }}"><i class="fa-solid fa-triangle-exclamation"></i><span>Linked risk or hazard</span><em>Optional</em></label>
            <select id="{{ $fieldId('riskArea') }}" name="risk_area_id"><option value="">None</option>@foreach($riskAreas as $riskArea)<option value="{{ $riskArea->risk_area_id }}" data-barangay-id="{{ $riskArea->barangayHall?->barangay_id }}" @selected((string) old('risk_area_id', $draft['risk_area_id'] ?? '') === (string) $riskArea->risk_area_id)>{{ $riskArea->barangayHall?->barangay?->barangay_name ?? 'Barangay' }} - {{ $riskArea->marker?->title ?? 'Risk area' }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('barangayChecklist') }}"><i class="fa-solid fa-list-check"></i><span>Barangay checklist</span><em>Optional</em></label>
            <select id="{{ $fieldId('barangayChecklist') }}" name="barangay_prediction_checklist_id"><option value="">None</option>@foreach($predictionChecklists as $item)<option value="{{ $item->barangay_prediction_checklist_id }}" data-barangay-id="{{ $item->barangay_id }}" @selected((string) old('barangay_prediction_checklist_id', $draft['barangay_prediction_checklist_id'] ?? '') === (string) $item->barangay_prediction_checklist_id)>{{ $item->barangay?->barangay_name ?? 'Barangay' }} - {{ $item->title }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('aiChecklist') }}"><i class="fa-solid fa-wand-magic-sparkles"></i><span>AI action plan</span><em>Optional</em></label>
            <select id="{{ $fieldId('aiChecklist') }}" name="ai_action_plan_checklist_id"><option value="">None</option>@foreach($aiActionPlanOptions as $item)<option value="{{ $item->ai_action_plan_checklist_id }}" data-barangay-id="{{ $item->barangay_id }}" @selected((string) old('ai_action_plan_checklist_id', $draft['ai_action_plan_checklist_id'] ?? '') === (string) $item->ai_action_plan_checklist_id)>{{ collect($managedBarangays)->firstWhere('barangay_id', $item->barangay_id)?->barangay_name ?? 'Barangay' }} - {{ $item->plan_title }}</option>@endforeach</select>
        </div>
    </div>
</section>

<details class="mitigation-form-block mitigation-form-block--details" @if($formMode === 'edit') open @endif>
    <summary class="mitigation-form-block__head">
        <span class="mitigation-form-block__icon is-green"><i class="fa-solid fa-chart-line"></i></span>
        <div><h4>Delivery and evaluation</h4><p>Progress, evidence, results, and follow-up. These fields can be completed later.</p></div>
        <span class="mitigation-form-block__step">03</span><i class="fa-solid fa-chevron-down mitigation-form-block__chevron"></i>
    </summary>
    <div class="mitigation-form-block__grid">
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('implementation') }}"><i class="fa-solid fa-person-digging"></i><span>Implementation updates</span><em>Optional</em></label>
            <textarea id="{{ $fieldId('implementation') }}" name="implementation_notes" rows="4" placeholder="Record work completed, coordination, and current blockers">{{ old('implementation_notes', $draft['implementation_notes'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('blockedReason') }}"><i class="fa-solid fa-road-barrier"></i><span>Blocked reason</span><em>Required when blocked</em></label>
            <textarea id="{{ $fieldId('blockedReason') }}" name="blocked_reason" rows="3" placeholder="What is preventing the work from continuing?">{{ old('blocked_reason', $draft['blocked_reason'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('completion') }}"><i class="fa-solid fa-circle-check"></i><span>Completion notes</span><em>Optional</em></label>
            <textarea id="{{ $fieldId('completion') }}" name="completion_notes" rows="3" placeholder="Summarize what was completed and verified">{{ old('completion_notes', $draft['completion_notes'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('outcome') }}"><i class="fa-solid fa-chart-simple"></i><span>Outcome or result</span><em>Optional</em></label>
            <textarea id="{{ $fieldId('outcome') }}" name="outcome_result" rows="3" placeholder="Describe the observed result or risk reduction">{{ old('outcome_result', $draft['outcome_result'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('effectiveness') }}"><i class="fa-solid fa-gauge-high"></i><span>Effectiveness</span></label>
            <select id="{{ $fieldId('effectiveness') }}" name="effectiveness">@foreach(($mitigationEffectivenessOptions ?? []) as $value => $label)<option value="{{ $value }}" @selected(old('effectiveness', $draft['effectiveness'] ?? 'pending_evaluation') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('verificationDate') }}"><i class="fa-solid fa-user-check"></i><span>Verification date</span><em>Optional</em></label>
            <input id="{{ $fieldId('verificationDate') }}" name="verification_date" type="date" value="{{ old('verification_date', $draft['verification_date'] ?? '') }}">
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('followUpRequired') }}"><i class="fa-solid fa-rotate"></i><span>Follow-up required</span></label>
            <select id="{{ $fieldId('followUpRequired') }}" name="follow_up_required"><option value="0" @selected((string) old('follow_up_required', $draft['follow_up_required'] ?? '0') === '0')>No</option><option value="1" @selected((string) old('follow_up_required', $draft['follow_up_required'] ?? '0') === '1')>Yes</option></select>
        </div>
        <div class="mitigation-field">
            <label for="{{ $fieldId('followUpDate') }}"><i class="fa-regular fa-calendar"></i><span>Follow-up date</span><em>Required when needed</em></label>
            <input id="{{ $fieldId('followUpDate') }}" name="follow_up_date" type="date" value="{{ old('follow_up_date', $draft['follow_up_date'] ?? '') }}">
        </div>
        <div class="mitigation-field mitigation-field--full">
            <label for="{{ $fieldId('followUpNotes') }}"><i class="fa-regular fa-comments"></i><span>Follow-up notes</span><em>Optional</em></label>
            <textarea id="{{ $fieldId('followUpNotes') }}" name="follow_up_notes" rows="3" placeholder="Add the next review, inspection, or corrective action">{{ old('follow_up_notes', $draft['follow_up_notes'] ?? '') }}</textarea>
        </div>
        <div class="mitigation-field mitigation-field--full mitigation-upload-field">
            <label for="{{ $fieldId('attachments') }}"><i class="fa-solid fa-paperclip"></i><span>Proof and attachments</span><em>Optional</em></label>
            <input id="{{ $fieldId('attachments') }}" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
            <small>Attach photos or supporting PDF/Word files.</small>
        </div>
    </div>
</details>
