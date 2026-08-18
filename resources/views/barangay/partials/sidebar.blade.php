@php
    $active = $active ?? '';
    $barangayName = $barangay?->barangay_name ?? 'Barangay Area';
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'barangay.dashboard', 'active' => 'dashboard', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>'],
        ['label' => 'Map', 'route' => 'barangay.risk-map', 'active' => 'risk-map', 'icon' => '<svg viewBox="0 0 24 24"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path d="M9 4v14"/><path d="M15 6v14"/></svg>'],
        ['label' => 'Fire Reports', 'route' => 'barangay.reports', 'active' => 'reports', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M9 11h6M9 15h4"/></svg>'],
        ['label' => 'Hazards & Risks', 'route' => 'barangay.hazards', 'active' => 'hazards', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3l9 16H3z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>'],
        ['label' => 'Checklist Center', 'route' => 'barangay.checklists', 'active' => 'checklists', 'icon' => '<svg viewBox="0 0 24 24"><path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/></svg>'],
        ['label' => 'Awareness Posts', 'route' => 'barangay.awareness', 'active' => 'awareness', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 11v2"/><path d="M6 10h3l7-4v12l-7-4H6z"/><path d="M9 14l1.5 4"/></svg>'],
        ['label' => 'Barangay Profile', 'route' => 'barangay.profile', 'active' => 'profile', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>'],
    ];
    $mobileDockItems = [
        ['label' => 'Map', 'route' => 'barangay.risk-map', 'active' => 'risk-map', 'mobile_icon' => 'map'],
        ['label' => 'Fire Reports', 'route' => 'barangay.reports', 'active' => 'reports', 'mobile_icon' => 'description'],
        ['label' => 'Hazards & Risks', 'route' => 'barangay.hazards', 'active' => 'hazards', 'mobile_icon' => 'warning'],
    ];
    $mobileCenterItem = ['label' => 'Dashboard', 'route' => 'barangay.dashboard', 'active' => 'dashboard', 'mobile_icon' => 'dashboard'];
    $mobileCenterActions = [
        ['label' => 'Checklist Center', 'route' => 'barangay.checklists', 'active' => 'checklists', 'mobile_icon' => 'checklist'],
        ['label' => 'Awareness Posts', 'route' => 'barangay.awareness', 'active' => 'awareness', 'mobile_icon' => 'campaign'],
        ['label' => 'Barangay Profile', 'route' => 'barangay.profile', 'active' => 'profile', 'mobile_icon' => 'person'],
    ];
@endphp

<aside class="sidebar chief-shell-sidebar hidden md:flex" id="sidebar">
    <div class="sidebar-header-gradient chief-shell-sidebar-header px-6 py-8">
        <div class="chief-shell-brand flex items-center gap-3">
            <div class="chief-shell-brand-icon w-20 h-14 bg-white rounded-2xl flex items-center justify-center border border-white/60 shadow-sm px-2 py-1">
                @include('partials.app-logo', ['class' => 'chief-shell-brand-logo w-full h-full object-contain'])
            </div>
            <div class="chief-shell-brand-copy">
                <h2 class="chief-shell-brand-title text-white font-bold text-xl leading-tight">PyroSense</h2>
                <p class="chief-shell-brand-subtitle text-white/70 text-xs uppercase tracking-widest">Barangay Desk</p>
            </div>
        </div>
    </div>

    <nav class="chief-shell-sidebar-nav mt-6 flex-1 px-4">
        <p class="chief-shell-section-title px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Barangay Center</p>

        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" class="nav-item chief-shell-nav-item {{ $active === $item['active'] ? 'active' : '' }}">
                <span class="inline-fa-icon" aria-hidden="true">{!! $item['icon'] !!}</span>
                <span class="chief-shell-nav-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="chief-shell-sidebar-footer p-4 border-t border-slate-100">
        <div class="chief-shell-profile-card bg-amber-50/50 rounded-2xl p-4 border border-amber-100/50">
            <div class="flex items-center gap-3">
                <div class="chief-shell-profile-avatar w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold border border-amber-200">
                    {{ substr(auth()->user()->first_name ?? 'B', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="chief-shell-profile-name text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name }}</p>
                    <p class="chief-shell-profile-role text-[10px] text-slate-500 font-medium truncate">{{ $barangayName }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<nav class="barangay-mobile-dock md:hidden" aria-label="Barangay mobile navigation">
    <div class="barangay-mobile-dock__shell">
        <div class="barangay-mobile-dock__track">
            <a href="{{ route($mobileDockItems[0]['route']) }}" class="barangay-mobile-dock__item {{ $active === $mobileDockItems[0]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[0]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[0]['mobile_icon'] }}</span>
            </a>

            <a href="{{ route($mobileDockItems[1]['route']) }}" class="barangay-mobile-dock__item {{ $active === $mobileDockItems[1]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[1]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[1]['mobile_icon'] }}</span>
            </a>

            <div class="barangay-mobile-dock__center-wrap {{ $active === $mobileCenterItem['active'] ? 'is-active' : '' }}" data-barangay-nav-center>
                <div class="barangay-mobile-dock__burst" data-barangay-nav-burst>
                    <button type="button" class="barangay-mobile-dock__burst-item" data-pyro-voice-call aria-label="Call the assigned BFP station">
                        <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__burst-icon" aria-hidden="true">phone_in_talk</span>
                        <span class="barangay-mobile-dock__burst-label">Call BFP</span>
                    </button>
                    @foreach ($mobileCenterActions as $action)
                        <a href="{{ route($action['route']) }}" class="barangay-mobile-dock__burst-item {{ $active === $action['active'] ? 'active' : '' }}" aria-label="{{ $action['label'] }}">
                            <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__burst-icon" aria-hidden="true">{{ $action['mobile_icon'] }}</span>
                            <span class="barangay-mobile-dock__burst-label">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <a
                    href="{{ route($mobileCenterItem['route']) }}"
                    class="barangay-mobile-dock__item barangay-mobile-dock__item--center {{ $active === $mobileCenterItem['active'] ? 'active' : '' }}"
                    aria-label="{{ $mobileCenterItem['label'] }}"
                    data-barangay-nav-trigger
                >
                    <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__icon" aria-hidden="true">{{ $mobileCenterItem['mobile_icon'] }}</span>
                </a>
            </div>

            <a href="{{ route($mobileDockItems[2]['route']) }}" class="barangay-mobile-dock__item {{ $active === $mobileDockItems[2]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[2]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[2]['mobile_icon'] }}</span>
            </a>

            <button type="button" class="barangay-mobile-dock__item {{ $active === 'profile' ? 'active' : '' }}" aria-label="Settings" data-barangay-settings-open>
                <span class="material-symbols-rounded chief-mobile-material-icon barangay-mobile-dock__icon" aria-hidden="true">settings</span>
            </button>
        </div>
    </div>
</nav>
<div class="barangay-mobile-bottom-spacer md:hidden" aria-hidden="true"></div>
<div class="chief-mobile-panel md:hidden" data-barangay-settings-panel aria-hidden="true">
    <div class="chief-mobile-panel__backdrop" data-barangay-panel-close></div>
    <div class="chief-mobile-panel__sheet">
        <div class="chief-mobile-panel__header">
            <div>
                <p class="chief-mobile-panel__eyebrow">Barangay Desk</p>
                <h2 class="chief-mobile-panel__title">Settings</h2>
            </div>
            <button type="button" class="chief-mobile-panel__close" data-barangay-panel-close aria-label="Close settings">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="chief-mobile-panel__menu">
            <button type="button" class="chief-mobile-panel__action" data-barangay-open-logs>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">history</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Logs</strong>
                    <small>View the full activity modal</small>
                </span>
            </button>
            <a href="{{ route('barangay.profile') }}" class="chief-mobile-panel__action">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">person</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Profile</strong>
                    <small>Manage your barangay account</small>
                </span>
            </a>
            <a href="{{ route('barangay.dashboard') }}" class="chief-mobile-panel__action">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">dashboard</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Dashboard</strong>
                    <small>Return to the barangay overview</small>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="chief-mobile-panel__action chief-mobile-panel__action--danger">
                    <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">logout</span>
                    <span class="chief-mobile-panel__action-copy">
                        <strong>Logout</strong>
                        <small>Sign out of Barangay Desk</small>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
@include('partials.user-activity-logger')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dock = document.querySelector('[data-barangay-nav-center]');
        const trigger = document.querySelector('[data-barangay-nav-trigger]');
        const burst = document.querySelector('[data-barangay-nav-burst]');
        const settingsPanel = document.querySelector('[data-barangay-settings-panel]');
        const settingsOpen = document.querySelector('[data-barangay-settings-open]');
        const logsOpen = document.querySelector('[data-barangay-open-logs]');

        if (!dock || !trigger) {
            return;
        }

        let holdTimer = null;
        let holdActivated = false;
        const holdDelay = 360;
        let lastTouchMove = null;

        const clearHoldTimer = () => {
            if (holdTimer) {
                window.clearTimeout(holdTimer);
                holdTimer = null;
            }
        };

        const openBurst = () => {
            dock.classList.add('is-open');
            holdActivated = true;
        };

        const closeBurst = () => {
            dock.classList.remove('is-open');
            holdActivated = false;
        };

        const openPanel = (panel) => {
            if (!panel) {
                return;
            }

            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
            closeBurst();
        };

        const closePanel = (panel) => {
            if (!panel) {
                return;
            }

            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-open');
        };

        const startHold = () => {
            clearHoldTimer();
            holdTimer = window.setTimeout(openBurst, holdDelay);
        };

        const endHold = () => {
            clearHoldTimer();
        };

        const handleTouchStart = (event) => {
            lastTouchMove = null;
            startHold();
        };

        const handleTouchMove = (event) => {
            lastTouchMove = event.touches?.[0] ? {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
            } : null;
            endHold();
        };

        trigger.addEventListener('touchstart', handleTouchStart, { passive: true });
        trigger.addEventListener('mousedown', startHold);
        trigger.addEventListener('touchend', endHold);
        trigger.addEventListener('touchcancel', endHold);
        trigger.addEventListener('touchmove', handleTouchMove, { passive: true });
        trigger.addEventListener('mouseleave', endHold);
        trigger.addEventListener('mouseup', endHold);
        trigger.addEventListener('contextmenu', (event) => event.preventDefault());

        trigger.addEventListener('click', (event) => {
            if (holdActivated) {
                event.preventDefault();
                closeBurst();
            }
        });

        document.addEventListener('click', (event) => {
            if (!dock.contains(event.target)) {
                closeBurst();
            }
        });

        document.addEventListener('touchstart', (event) => {
            if (!dock.contains(event.target) && !settingsPanel?.contains(event.target)) {
                closeBurst();
            }
        }, { passive: true });

        settingsOpen?.addEventListener('click', () => {
            openPanel(settingsPanel);
        });

        settingsPanel?.querySelectorAll('[data-barangay-panel-close]').forEach((button) => {
            button.addEventListener('click', () => closePanel(settingsPanel));
        });

        logsOpen?.addEventListener('click', () => {
            closePanel(settingsPanel);
            if (typeof window.openChiefLogsModal === 'function') {
                window.openChiefLogsModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeBurst();
                closePanel(settingsPanel);
            }
        });

        dock.querySelectorAll('.barangay-mobile-dock__burst-item').forEach((item) => {
            item.addEventListener('click', () => {
                closeBurst();
            });
        });

        const closeTransientUi = () => {
            closeBurst();
            closePanel(settingsPanel);
        };

        window.addEventListener('popstate', closeTransientUi);
        window.addEventListener('orientationchange', closeTransientUi);
        window.addEventListener('pagehide', closeTransientUi);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                closeTransientUi();
            }
        });

        burst?.addEventListener('touchmove', (event) => {
            event.preventDefault();
        }, { passive: false });
    });
</script>
