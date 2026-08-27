@php
    $personUser = $personOfficer?->user;
    $personName = trim(($personUser?->first_name ?? '') . ' ' . ($personUser?->last_name ?? '')) ?: 'Unknown investigator';
    $personParts = preg_split('/\s+/', trim($personName)) ?: [];
    $personInitials = collect($personParts)->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->join('') ?: 'FA';
    $personRank = $personOfficer?->rank ?: 'Rank not recorded';
    $personRole = ucfirst($personUser?->role?->role ?? 'Fire arson investigator');
    $personStatus = ucwords(str_replace('-', ' ', strtolower($personOfficer?->status ?: 'available')));
    $personImage = $personUser?->profile_thumbnail_url ?: $personUser?->profile_image_url;
@endphp

<button
    type="button"
    class="report-person-card"
    data-crew-member-card
    data-member-group="{{ $contextLabel ?? 'Report investigator' }}"
    data-member-name="{{ $personName }}"
    data-member-position="{{ $personRank }}"
    data-member-role="{{ $personRole }}"
    data-member-rank="{{ $personRank }}"
    data-member-status="{{ $personStatus }}"
    data-member-email="{{ $personUser?->email }}"
    data-member-contact="{{ $personUser?->contact_number }}"
    data-member-image="{{ $personImage }}"
    data-member-initials="{{ $personInitials }}"
    aria-haspopup="dialog"
    aria-controls="crewMemberDetailModal"
>
    <span class="report-person-photo" aria-hidden="true">
        @if($personImage)<img src="{{ $personImage }}" alt="" loading="lazy">@else<span>{{ $personInitials }}</span>@endif
    </span>
    <span class="report-person-copy"><strong>{{ $personName }}</strong><small>{{ $personRank }}</small></span>
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
</button>
