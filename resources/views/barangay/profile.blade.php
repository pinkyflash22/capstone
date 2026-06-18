@extends('barangay.layouts.app')

@php
    $profileBarangay = $barangay ?? ($user?->address?->barangay);
    $profileCity = $city ?? $profileBarangay?->city;
    $profileHall = $hall;
    $captainName = $connectedUsers->first(function ($connectedUser) {
        return strtolower((string) ($connectedUser->role->role ?? '')) === 'barangay captain';
    })?->full_name ?? auth()->user()->full_name;
    $coverTitle = $profileBarangay?->barangay_name ? 'Barangay ' . $profileBarangay->barangay_name : 'Barangay Profile';
@endphp

@section('active_nav', 'profile')
@section('page_title', 'Barangay Profile')
@section('page_subtitle', 'Institution background and assigned barangay users')
@section('hide_hero', '1')

@section('top_bar_actions')
    <a href="{{ route('barangay.reports') }}" class="portal-report-fire-btn" data-barangay-quick-report data-report-url="{{ route('barangay.reports.quick') }}">
        <i class="fa-solid fa-fire-flame-curved"></i>
        <span>Report Fire</span>
    </a>
@endsection

@section('content')
    <style>
        .barangay-profile-cover {
            overflow: hidden;
            border-radius: 36px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.1);
        }

        .barangay-profile-cover__body {
            padding: 20px;
        }

        .barangay-profile-cover__banner {
            min-height: 180px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.75), transparent 32%),
                radial-gradient(circle at 75% 20%, rgba(255,255,255,0.55), transparent 26%),
                linear-gradient(135deg, rgba(191, 219, 254, 0.95), rgba(226, 232, 240, 0.95) 48%, rgba(254, 249, 195, 0.9) 100%);
            position: relative;
            overflow: hidden;
        }

        .barangay-profile-cover__banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 30% 35%, rgba(255,255,255,0.7), transparent 18%),
                radial-gradient(circle at 55% 25%, rgba(255,255,255,0.8), transparent 16%),
                radial-gradient(circle at 75% 38%, rgba(255,255,255,0.65), transparent 20%);
            opacity: 0.9;
        }

        .barangay-profile-cover__content {
            padding: 24px 6px 10px;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(300px, 0.9fr);
            gap: 28px;
            align-items: start;
        }

        .barangay-profile-cover__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(251, 191, 36, 0.22);
            color: #c2410c;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-profile-cover__title {
            margin: 16px 0 0;
            font-size: clamp(2.2rem, 4vw, 3.8rem);
            font-weight: 950;
            letter-spacing: -0.04em;
            line-height: 0.98;
            color: #0f172a;
        }

        .barangay-profile-cover__copy {
            margin: 18px 0 0;
            max-width: 60ch;
            color: #64748b;
            font-size: 0.98rem;
            line-height: 1.75;
        }

        .barangay-profile-cover__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .barangay-profile-cover__chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fffaf0;
            border: 1px solid rgba(251, 191, 36, 0.18);
            color: #9a3412;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .barangay-profile-pane {
            padding-top: 6px;
        }

        .barangay-profile-pane__grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }

        .barangay-profile-pane__item {
            padding: 14px 0;
            border-bottom: 1px solid rgba(226, 232, 240, 0.82);
        }

        .barangay-profile-pane__item:last-child {
            border-bottom: 0;
        }

        .barangay-profile-pane__label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-profile-pane__value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .barangay-profile-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .barangay-profile-stat {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            padding: 18px;
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        }

        .barangay-profile-stat__label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-profile-stat__value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 950;
        }

        .barangay-user-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 18px;
        }

        .barangay-user-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            padding: 18px;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: linear-gradient(180deg, #ffffff 0%, #fffdfa 100%);
        }

        .barangay-user-row__name {
            color: #0f172a;
            font-size: 1.02rem;
            font-weight: 850;
            line-height: 1.4;
        }

        .barangay-user-row__meta {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.6;
        }

        .barangay-user-row__badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .barangay-user-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid rgba(251, 191, 36, 0.22);
            background: #fff7ed;
            color: #c2410c;
        }

        @media (max-width: 1100px) {
            .barangay-profile-cover__content,
            .barangay-profile-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .barangay-profile-cover__body {
                padding: 14px;
            }

            .barangay-profile-cover__content {
                padding: 18px 0 6px;
            }

            .barangay-profile-pane__grid {
                grid-template-columns: 1fr;
            }

            .barangay-user-row {
                grid-template-columns: 1fr;
            }

            .barangay-user-row__badges {
                justify-content: flex-start;
            }
        }
    </style>

    <section class="barangay-profile-cover" data-barangay-profile-root data-refresh-url="{{ route('barangay.profile') }}">
        <div class="barangay-profile-cover__body">
            <div class="barangay-profile-cover__banner"></div>
            <div class="barangay-profile-cover__content">
                <div>
                    <span class="barangay-profile-cover__eyebrow">
                        <i class="fa-solid fa-building-columns"></i>
                        Barangay Institution Profile
                    </span>
                    <h2 id="barangayProfileTitle" class="barangay-profile-cover__title">{{ $coverTitle }}</h2>
                    <p class="barangay-profile-cover__copy">
                        Institutional background for the barangay desk, including local hall information, leadership reference, and the active barangay-linked users connected to this service area.
                    </p>
                    <div class="barangay-profile-cover__chips">
                        <span id="barangayProfileCityChip" class="barangay-profile-cover__chip"><i class="fa-solid fa-location-dot"></i> {{ $profileCity?->city_name ?? 'City not linked' }}</span>
                        <span id="barangayProfileCaptainChip" class="barangay-profile-cover__chip"><i class="fa-solid fa-user-shield"></i> {{ $captainName }}</span>
                        <span id="barangayProfileUsersChip" class="barangay-profile-cover__chip"><i class="fa-solid fa-users"></i> {{ $connectedUsers->count() }} connected users</span>
                    </div>
                </div>

                <aside class="barangay-profile-pane">
                    <div class="barangay-profile-pane__grid">
                        <div class="barangay-profile-pane__item">
                            <div class="barangay-profile-pane__label">Barangay Hall</div>
                            <div id="barangayProfileHallName" class="barangay-profile-pane__value">{{ $profileHall?->hall_name ?? 'Hall profile pending' }}</div>
                        </div>
                        <div class="barangay-profile-pane__item">
                            <div class="barangay-profile-pane__label">Municipality / City</div>
                            <div id="barangayProfileCityValue" class="barangay-profile-pane__value">{{ $profileCity?->city_name ?? 'No city data' }}</div>
                        </div>
                        <div class="barangay-profile-pane__item">
                            <div class="barangay-profile-pane__label">Contact Number</div>
                            <div id="barangayProfileHallContact" class="barangay-profile-pane__value">{{ $profileHall?->contact_number ?: 'No hall contact yet' }}</div>
                        </div>
                        <div class="barangay-profile-pane__item">
                            <div class="barangay-profile-pane__label">Official Email</div>
                            <div id="barangayProfileHallEmail" class="barangay-profile-pane__value">{{ $profileHall?->email ?: 'No hall email yet' }}</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="barangay-profile-stats">
        <article class="barangay-profile-stat">
            <div class="barangay-profile-stat__label">Connected Users</div>
            <div id="barangayProfileStatUsers" class="barangay-profile-stat__value">{{ $connectedUsers->count() }}</div>
        </article>
        <article class="barangay-profile-stat">
            <div class="barangay-profile-stat__label">Active Risk Records</div>
            <div id="barangayProfileStatRiskAreas" class="barangay-profile-stat__value">{{ $riskAreas->count() }}</div>
        </article>
        <article class="barangay-profile-stat">
            <div class="barangay-profile-stat__label">Fire Records</div>
            <div id="barangayProfileStatFireRecords" class="barangay-profile-stat__value">{{ $fireRecords->count() }}</div>
        </article>
        <article class="barangay-profile-stat">
            <div class="barangay-profile-stat__label">Checklist Items</div>
            <div id="barangayProfileStatChecklists" class="barangay-profile-stat__value">{{ $predictionChecklists->count() }}</div>
        </article>
    </section>

    <section class="panel-card mt-6">
        <div class="panel-eyebrow">Connected Users</div>
        <h3 class="panel-title">Barangay users assigned or linked to this barangay</h3>
        <p class="panel-copy">This roster shows barangay-level accounts connected to this barangay through assignment or barangay-linked address information.</p>

        @if($connectedUsers->isEmpty())
            <div class="empty-state mt-6">No barangay-connected users are available yet for this barangay.</div>
        @else
            <div id="barangayConnectedUsersList" class="barangay-user-list">
                @foreach ($connectedUsers as $connectedUser)
                    @php
                        $fullName = $connectedUser->full_name ?: trim(($connectedUser->first_name ?? '') . ' ' . ($connectedUser->last_name ?? ''));
                        $position = $connectedUser->barangayOfficer?->position;
                        $userBarangay = $connectedUser->barangayOfficer?->barangay?->barangay_name ?? $connectedUser->address?->barangay?->barangay_name;
                    @endphp
                    <div class="barangay-user-row">
                        <div>
                            <div class="barangay-user-row__name">{{ $fullName ?: 'Unnamed Barangay User' }}</div>
                            <div class="barangay-user-row__meta">
                                {{ $connectedUser->email ?: 'No email' }}<br>
                                {{ $connectedUser->contact_number ?: 'No contact number' }}<br>
                                {{ $userBarangay ?: 'Barangay not linked' }}
                            </div>
                        </div>
                        <div class="barangay-user-row__badges">
                            <span class="barangay-user-badge"><i class="fa-solid fa-id-badge"></i> {{ $connectedUser->role->role ?? 'barangay' }}</span>
                            @if($position)
                                <span class="barangay-user-badge"><i class="fa-solid fa-user-tie"></i> {{ $position }}</span>
                            @endif
                            @if($connectedUser->barangayOfficer)
                                <span class="barangay-user-badge"><i class="fa-solid fa-link"></i> assigned</span>
                            @else
                                <span class="barangay-user-badge"><i class="fa-solid fa-location-dot"></i> linked</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const profileRoot = document.querySelector('[data-barangay-profile-root]');
        if (!profileRoot) {
            return;
        }

        const refreshUrl = profileRoot.dataset.refreshUrl;
        const usersList = document.getElementById('barangayConnectedUsersList');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderUserRow(user) {
            const positionBadge = user.position
                ? `<span class="barangay-user-badge"><i class="fa-solid fa-user-tie"></i> ${escapeHtml(user.position)}</span>`
                : '';
            const connectionBadge = user.connection_type === 'assigned'
                ? '<span class="barangay-user-badge"><i class="fa-solid fa-link"></i> assigned</span>'
                : '<span class="barangay-user-badge"><i class="fa-solid fa-location-dot"></i> linked</span>';

            return `
                <div class="barangay-user-row">
                    <div>
                        <div class="barangay-user-row__name">${escapeHtml(user.full_name || 'Unnamed Barangay User')}</div>
                        <div class="barangay-user-row__meta">
                            ${escapeHtml(user.email || 'No email')}<br>
                            ${escapeHtml(user.contact_number || 'No contact number')}<br>
                            ${escapeHtml(user.barangay_name || 'Barangay not linked')}
                        </div>
                    </div>
                    <div class="barangay-user-row__badges">
                        <span class="barangay-user-badge"><i class="fa-solid fa-id-badge"></i> ${escapeHtml(user.role || 'barangay')}</span>
                        ${positionBadge}
                        ${connectionBadge}
                    </div>
                </div>
            `;
        }

        function setText(id, value, fallback = '') {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = value || fallback;
            }
        }

        function setChip(id, iconClass, value) {
            const node = document.getElementById(id);
            if (node) {
                node.innerHTML = `<i class="${iconClass}"></i> ${escapeHtml(value)}`;
            }
        }

        async function refreshBarangayProfile() {
            try {
                const response = await fetch(refreshUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh barangay profile.');
                }

                const payload = await response.json();
                const data = payload.data || {};
                const barangay = data.barangay || {};
                const stats = data.stats || {};
                const connectedUsers = data.connected_users || [];

                setText('barangayProfileTitle', barangay.name ? `Barangay ${barangay.name}` : 'Barangay Profile');
                setChip('barangayProfileCityChip', 'fa-solid fa-location-dot', barangay.city || 'City not linked');
                setChip('barangayProfileCaptainChip', 'fa-solid fa-user-shield', data.captain_name || 'Barangay officer');
                setChip('barangayProfileUsersChip', 'fa-solid fa-users', `${connectedUsers.length} connected users`);
                setText('barangayProfileHallName', barangay.hall_name || 'Hall profile pending');
                setText('barangayProfileCityValue', barangay.city || 'No city data');
                setText('barangayProfileHallContact', barangay.hall_contact_number || 'No hall contact yet');
                setText('barangayProfileHallEmail', barangay.hall_email || 'No hall email yet');
                setText('barangayProfileStatUsers', String(stats.connected_users ?? 0));
                setText('barangayProfileStatRiskAreas', String(stats.active_risk_records ?? 0));
                setText('barangayProfileStatFireRecords', String(stats.fire_records ?? 0));
                setText('barangayProfileStatChecklists', String(stats.checklist_items ?? 0));

                if (usersList) {
                    usersList.innerHTML = connectedUsers.length
                        ? connectedUsers.map(renderUserRow).join('')
                        : '<div class="empty-state mt-6">No barangay-connected users are available yet for this barangay.</div>';
                }
            } catch (error) {
                console.error(error);
            }
        }

        refreshBarangayProfile();
        window.setInterval(refreshBarangayProfile, 30000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                refreshBarangayProfile();
            }
        });
    });
</script>
@endpush
