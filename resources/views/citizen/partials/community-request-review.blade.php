@php
    $requestKey = $activityRequest->getKey();
    $editingFailed = $errors->communityRequestEdit->any() && (string) old('edit_request_id') === (string) $requestKey;
    $editValue = fn ($field, $default = null) => $editingFailed ? old($field, $default) : $default;
    $communityRequestMinDate = \Carbon\Carbon::now('Asia/Manila')->addDay()->toDateString();
@endphp
<details class="community-request-review" @if($editingFailed) open @endif>
    <summary>View full request @if($activityRequest->canCitizenEdit()) &amp; edit @endif</summary>
    <div class="community-request-review__body">
        <p style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $activityRequest->description }}</p>
        <dl class="community-request-review__facts">
            <div><dt>Preferred date</dt><dd>{{ $activityRequest->preferred_date?->format('M d, Y') ?? 'Flexible' }}</dd></div>
            <div><dt>Preferred time</dt><dd>{{ $activityRequest->preferred_start_time ? substr($activityRequest->preferred_start_time, 0, 5) : 'Flexible' }}{{ $activityRequest->preferred_end_time ? ' – ' . substr($activityRequest->preferred_end_time, 0, 5) : '' }}</dd></div>
            <div><dt>Contact person</dt><dd>{{ $activityRequest->contact_name ?: 'Not provided' }}</dd></div>
            <div><dt>Contact number</dt><dd>{{ $activityRequest->contact_number ?: 'Not provided' }}</dd></div>
        </dl>
        @if($activityRequest->canCitizenEdit())
            <form method="POST" action="{{ route('citizen.community-activity-requests.update', $requestKey) }}" class="community-request-edit">
                @csrf
                @method('PATCH')
                <input type="hidden" name="edit_request_id" value="{{ $requestKey }}">
                <h3>Edit your request</h3>
                <p class="citizen-request-copy">Changes are allowed only before BFP review. Connect to the internet before saving.</p>
                @if($editingFailed)
                    <div role="alert" class="citizen-request-modal__error">
                        @foreach($errors->communityRequestEdit->all() as $message)<p>{{ $message }}</p>@endforeach
                    </div>
                @endif
                <label>Activity type<select name="request_type" required>
                    @foreach(['orientation' => 'Orientation', 'seminar' => 'Seminar', 'drill' => 'Drill', 'inspection' => 'Inspection', 'community_meeting' => 'Community meeting', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected($editValue('request_type', $activityRequest->request_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
                <label>Title<input name="title" required maxlength="255" value="{{ $editValue('title', $activityRequest->title) }}"></label>
                <label>Reason for request<textarea name="description" required maxlength="2000" rows="4">{{ $editValue('description', $activityRequest->description) }}</textarea></label>
                <label>Preferred date<input type="date" name="preferred_date" min="{{ $communityRequestMinDate }}" value="{{ $editValue('preferred_date', $activityRequest->preferred_date?->format('Y-m-d')) }}"></label>
                <div class="community-request-review__facts">
                    <label>Start time<input type="time" name="preferred_start_time" value="{{ $editValue('preferred_start_time', substr($activityRequest->preferred_start_time ?? '', 0, 5)) }}"></label>
                    <label>End time<input type="time" name="preferred_end_time" value="{{ $editValue('preferred_end_time', substr($activityRequest->preferred_end_time ?? '', 0, 5)) }}"></label>
                </div>
                <label>Contact person<input name="contact_name" maxlength="255" value="{{ $editValue('contact_name', $activityRequest->contact_name) }}"></label>
                <label>Contact number<input type="tel" name="contact_number" maxlength="60" value="{{ $editValue('contact_number', $activityRequest->contact_number) }}"></label>
                <button type="submit" class="citizen-request-submit">Save changes</button>
            </form>
        @else
            <p class="citizen-request-note">This request is read-only because it has been reviewed or scheduled. Contact your station if you need to make a change.</p>
        @endif
    </div>
</details>
@once
<style>
    .community-request-review { margin-top:12px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; }
    .community-request-review summary { min-height:44px; padding:12px; color:#b91c1c; font-size:12px; font-weight:800; cursor:pointer; }
    .community-request-review__body { padding:0 12px 14px; }
    .community-request-review__facts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin:12px 0; }
    .community-request-review__facts dt { font-size:10px; color:#64748b; }
    .community-request-review__facts dd { margin:4px 0 0; font-size:12px; color:#334155; overflow-wrap:anywhere; }
    .community-request-edit { display:grid; gap:12px; padding-top:14px; margin-top:14px; border-top:1px solid #e2e8f0; }
    .community-request-edit h3 { margin:0; font-size:15px; }
    .community-request-edit label { display:grid; gap:6px; min-width:0; font-size:12px; color:#475569; font-weight:700; }
    .community-request-edit :is(input,select,textarea) { width:100%; min-width:0; max-width:100%; box-sizing:border-box; min-height:44px; padding:10px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font:inherit; color:#172033; }
    .community-request-edit textarea { resize:vertical; }
    .community-request-review :is(summary,input,select,textarea,button):focus-visible { outline:3px solid #f59e0b; outline-offset:2px; }
    @media(max-width:640px) { .community-request-edit :is(input,select,textarea) { font-size:16px; } }
</style>
@endonce
