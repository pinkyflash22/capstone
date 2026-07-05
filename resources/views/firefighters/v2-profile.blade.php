@extends('firefighters.v2-layout', [
    'active' => 'profile',
    'pageTitle' => 'Firefighter Profile V2',
    'pageSubtitle' => 'Responder identity, station assignment, and incident contribution summary'
])

@section('title', 'PyroSense · Firefighter Profile V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-id-badge"></i> Responder Identity</span>
        <h2>{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'My Profile' }}</h2>
        <p>This profile view keeps your responder information, station assignment, and incident participation in one place while your current editable firefighter profile remains untouched.</p>
    </section>

    <section class="ff2-grid-2">
        <article class="ff2-card">
            <div class="ff2-card__body">
                <h3 class="ff2-card__title">Profile Snapshot</h3>
                <p class="ff2-card__subtitle">Loaded from the same firefighter user and station assignment records.</p>
                <div style="display:grid;gap:14px;margin-top:18px;">
                    <div class="ff2-list-item"><div><h3>Email</h3><p>{{ $user->email ?? 'No email on file' }}</p></div></div>
                    <div class="ff2-list-item"><div><h3>Contact</h3><p>{{ $user->contact_number ?? 'No contact number' }}</p></div></div>
                    <div class="ff2-list-item"><div><h3>Role</h3><p>{{ $user->role->role ?? 'Firefighter' }}</p></div></div>
                    <div class="ff2-list-item"><div><h3>Assigned Station</h3><p>{{ $stationOfficer->station->station_name ?? 'No linked station' }}</p></div></div>
                    <div class="ff2-list-item"><div><h3>Active Team</h3><p>{{ $userTeam->team_name ?? 'No team linked yet' }}</p></div></div>
                </div>
                <div style="margin-top:18px;">
                    <a href="{{ route('firefighter.my-profile') }}" class="ff2-button ff2-button--primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open current profile editor</a>
                </div>
            </div>
        </article>

        <article class="ff2-card">
            <div class="ff2-card__body">
                <h3 class="ff2-card__title">Responder Contribution</h3>
                <p class="ff2-card__subtitle">Pulled from the same firefighter incident registry used in the current module.</p>
                <div class="ff2-stats" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-top:18px;">
                    <article class="ff2-stat"><div class="ff2-stat__label"><span>Total</span><i class="fa-solid fa-list"></i></div><div class="ff2-stat__value">{{ $profileStats['total_incidents'] }}</div><div class="ff2-stat__meta">Total registry incidents</div></article>
                    <article class="ff2-stat"><div class="ff2-stat__label"><span>Active</span><i class="fa-solid fa-bolt"></i></div><div class="ff2-stat__value">{{ $profileStats['active_incidents'] }}</div><div class="ff2-stat__meta">Still active</div></article>
                    <article class="ff2-stat"><div class="ff2-stat__label"><span>Controlled</span><i class="fa-solid fa-shield"></i></div><div class="ff2-stat__value">{{ $profileStats['controlled_incidents'] }}</div><div class="ff2-stat__meta">Controlled incidents</div></article>
                    <article class="ff2-stat"><div class="ff2-stat__label"><span>Avg Response</span><i class="fa-solid fa-stopwatch"></i></div><div class="ff2-stat__value">{{ $profileStats['avg_response_minutes'] }}m</div><div class="ff2-stat__meta">Average response minutes</div></article>
                </div>
            </div>
        </article>
    </section>
@endsection
