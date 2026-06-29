@php
    $sidebarId = $sidebarId ?? 'ffSidebar';
    $backdropId = $backdropId ?? 'ffSidebarBackdrop';
    $currentActive = $active ?? '';
    $sidebarSections = [
        [
            'title' => 'Operations',
            'items' => [
                ['type' => 'link', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'route' => 'firefighter.dashboard', 'active' => ['dashboard']],
                ['type' => 'link', 'label' => 'Incidents', 'icon' => 'fas fa-fire-extinguisher', 'route' => 'firefighter.incidents', 'active' => ['incidents']],
                ['type' => 'link', 'label' => 'My Station', 'icon' => 'fas fa-building', 'route' => 'firefighter.my-station', 'active' => ['station']],
                ['type' => 'link', 'label' => 'Requests', 'icon' => 'fas fa-clipboard-list', 'route' => 'firefighter.equipments', 'active' => ['equipment', 'fsic']],
            ],
        ],
        [
            'title' => 'Communications',
            'items' => [
                ['type' => 'link', 'label' => 'Announcements', 'icon' => 'fas fa-bullhorn', 'route' => 'firefighter.announcements', 'active' => ['announcements']],
                ['type' => 'link', 'label' => 'My Schedule', 'icon' => 'fas fa-calendar', 'route' => 'firefighter.schedule', 'active' => ['schedule']],
                ['type' => 'button', 'label' => 'My Logs', 'icon' => 'fas fa-clock-rotate-left', 'onclick' => 'openMyLogsModal()'],
            ],
        ],
    ];
    $firefighterInitials = strtoupper(substr(auth()->user()->first_name ?? 'F', 0, 1) . substr(auth()->user()->last_name ?? 'R', 0, 1));
    $mobileDockIsOpsActive = in_array($currentActive, ['dashboard', 'incidents', 'station', 'equipment', 'fsic'], true);
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:FILL@1&display=swap');

    .ff-mobile-icon {
        font-family: "Material Symbols Rounded" !important;
        font-weight: normal;
        font-style: normal;
        font-size: 22px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
        font-variation-settings:
            "FILL" 1,
            "wght" 500,
            "GRAD" 0,
            "opsz" 24;
    }

    .ff-mobile-panel {
        position: fixed;
        inset: 0;
        z-index: 9996;
        display: none;
    }

    .ff-mobile-panel.is-open {
        display: block;
    }

    .ff-mobile-panel__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(8px);
    }

    .ff-mobile-panel__sheet {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, #fffaf4 0%, #fbf7ef 100%);
        padding: calc(18px + env(safe-area-inset-top, 0px)) 18px calc(22px + env(safe-area-inset-bottom, 0px));
        overflow-y: auto;
    }

    .ff-mobile-panel__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .ff-mobile-panel__eyebrow {
        margin: 0 0 4px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #d18a16;
    }

    .ff-mobile-panel__title {
        margin: 0;
        font-size: 1.55rem;
        line-height: 1.1;
        font-weight: 800;
        color: #1e293b;
    }

    .ff-mobile-panel__close {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .ff-mobile-panel__list {
        display: grid;
        gap: 12px;
    }

    .ff-mobile-panel__action {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        text-decoration: none;
        border: 1px solid rgba(198, 61, 38, 0.12);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
        color: #334155;
    }

    .ff-mobile-panel__action--button {
        appearance: none;
        text-align: left;
    }

    .ff-mobile-panel__action-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        color: #fff;
        box-shadow: 0 12px 20px rgba(198, 61, 38, 0.18);
    }

    .ff-mobile-panel__copy {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }

    .ff-mobile-panel__copy strong {
        font-size: 0.94rem;
        line-height: 1.2;
        color: #0f172a;
    }

    .ff-mobile-panel__copy small {
        font-size: 0.75rem;
        line-height: 1.35;
        color: #64748b;
    }

    .ff-mobile-panel__action--danger {
        border-color: rgba(239, 68, 68, 0.16);
    }

    .ff-mobile-panel__action--danger .ff-mobile-panel__action-icon {
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
    }

    .ff-mobile-bottom-spacer {
        display: none;
    }

    @media (max-width: 768px) {
        :root {
            --ff-mobile-dock-h: calc(92px + env(safe-area-inset-bottom, 0px));
        }

        .ff-mobile-bottom-spacer {
            display: block;
            height: var(--ff-mobile-dock-h);
            width: 100%;
            flex-shrink: 0;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app {
            position: fixed;
            inset: auto 0 0;
            display: block !important;
            padding: 0 14px calc(12px + env(safe-area-inset-bottom, 0px));
            min-height: var(--ff-mobile-dock-h);
            background: linear-gradient(180deg, rgba(251, 247, 239, 0) 0%, rgba(251, 247, 239, 0.58) 46%, rgba(251, 247, 239, 0.94) 100%);
            backdrop-filter: blur(8px);
            z-index: 9997;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock {
            width: 100%;
            margin: 0 auto;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: flex-end;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__rail {
            position: relative;
            width: min(100%, 344px);
            margin: 0 auto;
            gap: 6px;
            min-height: 66px;
            padding: 12px 16px 11px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(20, 83, 90, 0.98) 0%, rgba(16, 75, 84, 0.96) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.24);
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            align-items: end;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__rail::before {
            content: '';
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            pointer-events: none;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item {
            position: relative;
            min-width: 44px;
            min-height: 44px;
            padding: 0;
            border: none;
            background: transparent;
            color: rgba(214, 227, 230, 0.5);
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            justify-self: center;
            text-decoration: none;
            transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            border-radius: 999px;
            z-index: 1;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item.active {
            color: #ffffff;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item.active::before {
            content: '';
            position: absolute;
            inset: 5px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.16);
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.14);
            z-index: -1;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item .ff-mobile-icon {
            width: 20px;
            height: 20px;
            font-size: 20px;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center {
            width: 66px;
            height: 66px;
            min-width: 66px;
            min-height: 66px;
            margin: -30px auto 0;
            padding: 0;
            border: 4px solid #f5f7ec;
            border-radius: 999px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            appearance: none;
            -webkit-appearance: none;
            flex-shrink: 0;
            box-shadow: 0 18px 34px rgba(34, 197, 94, 0.34), 0 0 0 8px rgba(255, 255, 255, 0.22);
            position: relative;
            z-index: 3;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center::before {
            position: absolute;
            inset: 8px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.34);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center::after {
            content: '';
            position: absolute;
            inset: 4px 9px auto;
            height: 16px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.28), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center.active {
            box-shadow: 0 18px 34px rgba(34, 197, 94, 0.34), 0 0 0 8px rgba(255, 255, 255, 0.22);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center .ff-mobile-icon {
            font-size: 24px;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__burst {
            position: absolute;
            left: 50%;
            bottom: 78px;
            width: 0;
            height: 0;
            pointer-events: none;
            z-index: 6;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__burst-item {
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 52px;
            height: 52px;
            margin-left: -26px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.85);
            background: rgba(255, 255, 255, 0.96);
            color: #14532d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.14);
            opacity: 0;
            transform: translate(0, 8px) scale(0.84);
            transition: transform 0.22s ease, opacity 0.22s ease, background 0.2s ease, color 0.2s ease;
            pointer-events: none;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__burst-item.active {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #ffffff;
            border-color: transparent;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst {
            pointer-events: auto;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item:nth-child(1) {
            transform: translate(-78px, -44px) scale(1);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item:nth-child(2) {
            transform: translate(0, -108px) scale(1);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item:nth-child(3) {
            transform: translate(78px, -44px) scale(1);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item:nth-child(4) {
            transform: translate(0, -158px) scale(1);
        }
    }
</style>

<aside class="sidebar ff-sidebar-shell hidden md:flex" id="{{ $sidebarId }}">
    <div class="sidebar-header-gradient px-6 py-8">
        <div class="ff-shell-brand flex items-center gap-3">
            <div class="ff-shell-brand-icon w-20 h-14 bg-white rounded-2xl flex items-center justify-center border border-white/60 shadow-sm px-2 py-1">
                <img src="{{ \App\Support\MediaPath::url(config('app.brand_logo_path')) }}" alt="PyroSense logo" class="ff-shell-brand-logo w-full h-full object-contain">
            </div>
            <div class="ff-shell-brand-copy">
                <h2 class="ff-shell-brand-title text-white font-bold text-xl leading-tight">PyroSense</h2>
                <p class="ff-shell-brand-subtitle text-white/70 text-xs uppercase tracking-widest">Responder Hub</p>
            </div>
        </div>
    </div>

    <nav class="mt-6 flex-1 px-4">
        @foreach ($sidebarSections as $section)
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest {{ $loop->first ? 'mb-4' : 'mt-8 mb-4' }}">{{ $section['title'] }}</p>
            @foreach ($section['items'] as $item)
                @php
                    $isActive = !empty($item['active']) && in_array($currentActive, $item['active'], true);
                @endphp

                @if (($item['type'] ?? 'link') === 'button')
                    <button type="button" class="nav-item w-full text-left {{ $isActive ? 'active' : '' }}" onclick="{{ $item['onclick'] }}">
                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </button>
                @else
                    <a href="{{ route($item['route']) }}" class="nav-item {{ $isActive ? 'active' : '' }}">
                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    <div class="p-4 border-t border-slate-100">
        <div class="bg-red-50/50 rounded-2xl p-4 border border-red-100/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold border border-red-200">
                    {{ $firefighterInitials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name ?? auth()->user()->name ?? 'Firefighter' }}</p>
                    <p class="text-[10px] text-slate-500 font-medium">On Duty • Station 7</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<nav class="mobile-bottom-nav mobile-bottom-nav--firefighter-app block fixed bottom-0 left-0 right-0 z-[9999] md:hidden" aria-label="Mobile primary navigation">
    <div class="ff-mobile-dock" data-ff-mobile-dock>
        <div class="ff-mobile-dock__burst" aria-hidden="true">
            <a href="{{ route('firefighter.incidents') }}" class="ff-mobile-dock__burst-item {{ $currentActive === 'incidents' ? 'active' : '' }}" aria-label="Incidents">
                <span class="ff-mobile-icon" aria-hidden="true">local_fire_department</span>
            </a>
            <a href="{{ route('firefighter.my-station') }}" class="ff-mobile-dock__burst-item {{ $currentActive === 'station' ? 'active' : '' }}" aria-label="My Station">
                <span class="ff-mobile-icon" aria-hidden="true">apartment</span>
            </a>
            <a href="{{ route('firefighter.equipments') }}" class="ff-mobile-dock__burst-item {{ in_array($currentActive, ['equipment', 'fsic'], true) ? 'active' : '' }}" aria-label="Requests">
                <span class="ff-mobile-icon" aria-hidden="true">assignment</span>
            </a>
            @if ($currentActive === 'dashboard')
                <button type="button" class="ff-mobile-dock__burst-item" data-ff-open-insights aria-label="Charts and Cards">
                    <span class="ff-mobile-icon" aria-hidden="true">stacked_bar_chart</span>
                </button>
            @endif
        </div>
        <div class="ff-mobile-dock__rail">
            <a href="{{ route('firefighter.schedule') }}" class="ff-mobile-dock__item {{ $currentActive === 'schedule' ? 'active' : '' }}" aria-label="Schedule">
                <span class="ff-mobile-icon" aria-hidden="true">calendar_month</span>
            </a>
            <button type="button" class="ff-mobile-dock__item" data-ff-open-notifications aria-label="Notifications">
                <span class="ff-mobile-icon" aria-hidden="true">notifications</span>
            </button>
            <button type="button" class="ff-mobile-dock__center {{ $mobileDockIsOpsActive ? 'active' : '' }}" data-ff-dashboard-fab aria-label="Dashboard">
                <span class="ff-mobile-icon" aria-hidden="true">dashboard</span>
            </button>
            <a href="{{ route('firefighter.announcements') }}" class="ff-mobile-dock__item {{ $currentActive === 'announcements' ? 'active' : '' }}" aria-label="Announcements">
                <span class="ff-mobile-icon" aria-hidden="true">campaign</span>
            </a>
            <button type="button" class="ff-mobile-dock__item {{ $currentActive === 'profile' ? 'active' : '' }}" data-ff-open-settings aria-label="Settings">
                <span class="ff-mobile-icon" aria-hidden="true">settings</span>
            </button>
        </div>
    </div>
</nav>

<div class="ff-mobile-bottom-spacer md:hidden" aria-hidden="true"></div>

<div class="ff-mobile-panel md:hidden" data-ff-notifications-panel aria-hidden="true">
    <div class="ff-mobile-panel__backdrop" data-ff-close-panel></div>
    <div class="ff-mobile-panel__sheet">
        <div class="ff-mobile-panel__header">
            <div>
                <p class="ff-mobile-panel__eyebrow">Responder Hub</p>
                <h2 class="ff-mobile-panel__title">Notifications</h2>
            </div>
            <button type="button" class="ff-mobile-panel__close" data-ff-close-panel aria-label="Close notifications">
                <span class="ff-mobile-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="ff-mobile-panel__list">
            <a href="{{ route('firefighter.incidents') }}" class="ff-mobile-panel__action">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">local_fire_department</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Active Incidents</strong>
                    <small>Check incident updates and field assignments.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.announcements') }}" class="ff-mobile-panel__action">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">campaign</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Announcements</strong>
                    <small>Read station advisories and recent internal posts.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.schedule') }}" class="ff-mobile-panel__action">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">calendar_month</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Duty Schedule</strong>
                    <small>Review upcoming shifts, drills, and station activities.</small>
                </span>
            </a>
        </div>
    </div>
</div>

<div class="ff-mobile-panel md:hidden" data-ff-settings-panel aria-hidden="true">
    <div class="ff-mobile-panel__backdrop" data-ff-close-panel></div>
    <div class="ff-mobile-panel__sheet">
        <div class="ff-mobile-panel__header">
            <div>
                <p class="ff-mobile-panel__eyebrow">Responder Hub</p>
                <h2 class="ff-mobile-panel__title">Settings</h2>
            </div>
            <button type="button" class="ff-mobile-panel__close" data-ff-close-panel aria-label="Close settings">
                <span class="ff-mobile-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="ff-mobile-panel__list">
            <a href="{{ route('firefighter.my-profile') }}" class="ff-mobile-panel__action">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">person</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>My Profile</strong>
                    <small>Update personal details and firefighter account info.</small>
                </span>
            </a>
            <button type="button" class="ff-mobile-panel__action ff-mobile-panel__action--button" data-ff-open-logs>
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">history</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>My Logs</strong>
                    <small>View recent activity and submitted firefighter actions.</small>
                </span>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ff-mobile-panel__action ff-mobile-panel__action--button ff-mobile-panel__action--danger">
                    <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">logout</span></span>
                    <span class="ff-mobile-panel__copy">
                        <strong>Logout</strong>
                        <small>Sign out of the firefighter dashboard safely.</small>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

@include('partials.user-activity-logger')
@include('firefighters.partials.my-logs')
@include('firefighters.partials.logout-confirmation')

<div id="{{ $backdropId }}" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dock = document.querySelector('[data-ff-mobile-dock]');
    const dashboardFab = document.querySelector('[data-ff-dashboard-fab]');
    const notificationsPanel = document.querySelector('[data-ff-notifications-panel]');
    const settingsPanel = document.querySelector('[data-ff-settings-panel]');
    const panelCloseButtons = document.querySelectorAll('[data-ff-close-panel]');
    const notificationsOpenButton = document.querySelector('[data-ff-open-notifications]');
    const settingsOpenButton = document.querySelector('[data-ff-open-settings]');
    const openLogsButton = document.querySelector('[data-ff-open-logs]');
    const openInsightsButton = document.querySelector('[data-ff-open-insights]');
    let holdTimer = null;
    let holdTriggered = false;
    let touchTapPending = false;

    function openPanel(panel) {
        if (!panel) return;
        [notificationsPanel, settingsPanel].forEach(function (target) {
            if (target) {
                target.classList.toggle('is-open', target === panel);
                target.setAttribute('aria-hidden', target === panel ? 'false' : 'true');
            }
        });
    }

    function closePanels() {
        [notificationsPanel, settingsPanel].forEach(function (panel) {
            if (panel) {
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function openBurstMenu() {
        if (!dock) return;
        holdTriggered = true;
        dock.classList.add('is-burst-open');
    }

    function closeBurstMenu() {
        if (!dock) return;
        dock.classList.remove('is-burst-open');
    }

    function clearHoldTimer() {
        if (holdTimer) {
            window.clearTimeout(holdTimer);
            holdTimer = null;
        }
    }

    function goToFirefighterDashboard() {
        window.location.href = @json(route('firefighter.dashboard'));
    }

    if (dashboardFab) {
        const beginHold = function (event) {
            if (window.innerWidth > 768) return;
            holdTriggered = false;
            touchTapPending = event.type === 'touchstart';
            clearHoldTimer();
            holdTimer = window.setTimeout(function () {
                openBurstMenu();
            }, 360);
            if (event.type === 'touchstart') {
                event.preventDefault();
            }
        };

        const endHold = function () {
            clearHoldTimer();
        };

        dashboardFab.addEventListener('mousedown', beginHold);
        dashboardFab.addEventListener('touchstart', beginHold, { passive: false });
        dashboardFab.addEventListener('mouseup', endHold);
        dashboardFab.addEventListener('mouseleave', endHold);
        dashboardFab.addEventListener('touchend', function () {
            endHold();
            if (!holdTriggered && touchTapPending) {
                touchTapPending = false;
                goToFirefighterDashboard();
                return;
            }
            holdTriggered = false;
            touchTapPending = false;
        });
        dashboardFab.addEventListener('touchcancel', endHold);
        dashboardFab.addEventListener('click', function (event) {
            if (holdTriggered) {
                event.preventDefault();
                holdTriggered = false;
                return;
            }

            if (touchTapPending) {
                event.preventDefault();
                touchTapPending = false;
                return;
            }

            goToFirefighterDashboard();
        });
    }

    notificationsOpenButton?.addEventListener('click', function () {
        closeBurstMenu();
        openPanel(notificationsPanel);
    });

    settingsOpenButton?.addEventListener('click', function () {
        closeBurstMenu();
        openPanel(settingsPanel);
    });

    openLogsButton?.addEventListener('click', function () {
        closePanels();
        if (typeof openMyLogsModal === 'function') {
            openMyLogsModal();
        }
    });

    openInsightsButton?.addEventListener('click', function () {
        closeBurstMenu();
        if (typeof window.openFirefighterInsightsPanel === 'function') {
            window.openFirefighterInsightsPanel();
        }
    });

    panelCloseButtons.forEach(function (button) {
        button.addEventListener('click', closePanels);
    });

    document.addEventListener('click', function (event) {
        if (dock && !dock.contains(event.target)) {
            closeBurstMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanels();
            closeBurstMenu();
        }
    });
});
</script>

@include('firefighters.partials.icon-fallback')
