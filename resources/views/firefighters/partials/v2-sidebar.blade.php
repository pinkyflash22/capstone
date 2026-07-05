@php
    $current = $active ?? 'dashboard';
    $user = auth()->user();
    $initials = strtoupper(substr($user->first_name ?? 'F', 0, 1) . substr($user->last_name ?? 'R', 0, 1));
@endphp

<aside class="ff2-sidebar">
    <div class="ff2-brand">
        <div class="ff2-brand-mark"><i class="fa-solid fa-fire-flame-curved"></i></div>
        <div class="ff2-brand-copy">
            <strong>PyroSense</strong>
            <span>Responder Hub V2</span>
        </div>
    </div>

    <div class="ff2-nav-group">
        <p class="ff2-nav-label">Operations</p>
        <nav class="ff2-nav">
            <a href="{{ route('firefighter.v2.dashboard') }}" class="{{ $current === 'dashboard' ? 'is-active' : '' }}"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="{{ route('firefighter.v2.incidents') }}" class="{{ $current === 'incidents' ? 'is-active' : '' }}"><i class="fa-solid fa-fire"></i><span>Incidents</span></a>
            <a href="{{ route('firefighter.v2.station') }}" class="{{ $current === 'station' ? 'is-active' : '' }}"><i class="fa-solid fa-building-shield"></i><span>My Station</span></a>
            <a href="{{ route('firefighter.v2.requests') }}" class="{{ $current === 'requests' ? 'is-active' : '' }}"><i class="fa-solid fa-clipboard-check"></i><span>Requests</span></a>
            <a href="{{ route('firefighter.v2.schedule') }}" class="{{ $current === 'schedule' ? 'is-active' : '' }}"><i class="fa-solid fa-calendar-days"></i><span>Schedule</span></a>
            <a href="{{ route('firefighter.v2.announcements') }}" class="{{ $current === 'announcements' ? 'is-active' : '' }}"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
            <a href="{{ route('firefighter.v2.profile') }}" class="{{ $current === 'profile' ? 'is-active' : '' }}"><i class="fa-solid fa-id-badge"></i><span>Profile</span></a>
        </nav>
    </div>

    <div class="ff2-sidebar-footer">
        <strong>{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Firefighter' }}</strong>
        <span>{{ $user->email ?? 'No email available' }}</span>
        <div style="display:flex;align-items:center;gap:12px;margin-top:16px;">
            <div style="width:42px;height:42px;border-radius:16px;display:grid;place-items:center;background:rgba(255,255,255,.16);font-weight:900;">{{ $initials }}</div>
            <div>
                <div style="font-size:.78rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;">Parallel Build</div>
                <div style="font-size:.82rem;color:rgba(229,243,247,.78);">Your current firefighter pages are still untouched.</div>
            </div>
        </div>
    </div>
</aside>
