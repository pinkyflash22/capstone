@include('partials.unified-notifications')
@include('partials.voice-call-config')
@vite('resources/js/dynamic-actions.js')

@php
    $active = $active ?? 'dashboard';
    $navGroups = [
        [
            'title' => 'Command Center',
            'items' => [
                ['label' => 'Provincial Overview', 'route' => 'admin.dashboard', 'active' => 'dashboard', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>'],
                ['label' => 'FSIC Records', 'route' => 'admin.fsic', 'active' => 'fsic', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14l-2-1-2 1-2-1-2 1-2-1-2 1V3z"/><path d="M14 3v4h4M9 11h6M9 15h6"/></svg>'],
                ['label' => 'Announcements', 'route' => 'admin.announcements', 'active' => 'announcements', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 11v2"/><path d="M6 10h3l7-4v12l-7-4H6z"/><path d="M9 14l1.5 4"/></svg>'],
                ['label' => 'Provincial Management', 'route' => 'admin.management', 'active' => 'management', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 20V5l7-2v17"/><path d="M12 20V9h7v11"/><path d="M8 8h1M8 11h1M8 14h1M15 12h1M15 15h1"/></svg>'],
                ['label' => 'Activity Schedule', 'route' => 'admin.schedule', 'active' => 'schedule', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3v4"/><path d="M17 3v4"/><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 17h3"/></svg>'],
            ],
        ],
        [
            'title' => 'Strategic Assets',
            'items' => [
                ['label' => 'Resources', 'route' => 'admin.resource-management.index', 'active' => 'resources', 'icon' => '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" fill="none"/><rect x="13" y="4" width="7" height="7" fill="none"/><rect x="8.5" y="13" width="7" height="7" fill="none"/></svg>'],
                ['label' => 'Landing Media', 'route' => 'landing-media.index', 'active' => 'landing-media', 'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15l3-3 2 2 3-4 3 5M8 9h.01"/></svg>'],
                ['label' => 'Barangays', 'route' => 'admin.barangays.index', 'active' => 'barangays', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 21s7-4.6 7-11a7 7 0 1 0-14 0c0 6.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>'],
                ['label' => 'Risk Reviews', 'route' => 'admin.risk-reviews.index', 'active' => 'risk-reviews', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.6-2.9 7.8-7 9-4.1-1.2-7-4.4-7-9V7z"/><path d="M9.5 12l1.8 1.8L15 10.2"/></svg>'],
                ['label' => 'Mitigation Control', 'route' => 'admin.mitigations', 'active' => 'mitigations', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.6-2.9 7.8-7 9-4.1-1.2-7-4.4-7-9V7z"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>'],
                ['label' => 'Analytics', 'route' => 'admin.analytics', 'active' => 'analytics', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M5 15l4-4 4 2 6-6"/></svg>'],
                ['label' => 'Course Checklists', 'route' => 'admin.course-checklists', 'active' => 'course-checklists', 'icon' => '<svg viewBox="0 0 24 24"><path d="M9 11l2 2 4-4"/><path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>'],
                ['label' => 'Executive Reports', 'route' => 'admin.reports', 'active' => 'reports', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M9 11h6M9 15h4"/></svg>'],
            ],
        ],
    ];
    $mobileDockItems = [
        ['label' => 'Announcements', 'route' => 'admin.announcements', 'active' => 'announcements', 'mobile_icon' => 'campaign'],
        ['label' => 'Notifications', 'active' => 'notifications', 'mobile_icon' => 'notifications'],
        ['label' => 'Analytics', 'route' => 'admin.analytics', 'active' => 'analytics', 'mobile_icon' => 'monitoring'],
        ['label' => 'Settings', 'active' => 'settings', 'mobile_icon' => 'settings'],
    ];
    $mobileCenterItem = ['label' => 'Provincial Overview', 'route' => 'admin.dashboard', 'active' => 'dashboard', 'mobile_icon' => 'dashboard'];
    $mobileCenterActions = [
        ['label' => 'Resources', 'route' => 'admin.resource-management.index', 'active' => 'resources', 'mobile_icon' => 'inventory_2'],
        ['label' => 'Risk Reviews', 'route' => 'admin.risk-reviews.index', 'active' => 'risk-reviews', 'mobile_icon' => 'fact_check'],
        ['label' => 'FSIC Records', 'route' => 'admin.fsic', 'active' => 'fsic', 'mobile_icon' => 'folder_open'],
        ['label' => 'Reports', 'route' => 'admin.reports', 'active' => 'reports', 'mobile_icon' => 'description'],
        ['label' => 'Management', 'route' => 'admin.management', 'active' => 'management', 'mobile_icon' => 'apartment'],
        ['label' => 'Barangays', 'route' => 'admin.barangays.index', 'active' => 'barangays', 'mobile_icon' => 'location_city'],
        ['label' => 'Mitigations', 'route' => 'admin.mitigations', 'active' => 'mitigations', 'mobile_icon' => 'task_alt'],
        ['label' => 'Schedule', 'route' => 'admin.schedule', 'active' => 'schedule', 'mobile_icon' => 'event'],
        ['label' => 'Announcements', 'route' => 'admin.announcements', 'active' => 'announcements', 'mobile_icon' => 'campaign'],
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'dashboard', 'mobile_icon' => 'dashboard'],
        ['label' => 'Course Checklists', 'route' => 'admin.course-checklists', 'active' => 'course-checklists', 'mobile_icon' => 'checklist'],
        ['label' => 'Landing Media', 'route' => 'landing-media.index', 'active' => 'landing-media', 'mobile_icon' => 'photo_library'],
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
                <p class="chief-shell-brand-subtitle text-white/70 text-xs uppercase tracking-widest">Provincial Command</p>
            </div>
        </div>
    </div>

    <nav class="chief-shell-sidebar-nav mt-6 flex-1 px-4">
        @foreach ($navGroups as $group)
            <p class="chief-shell-section-title px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest {{ $loop->first ? 'mb-4' : 'mt-8 mb-4' }}">{{ $group['title'] }}</p>
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route']) }}" class="nav-item chief-shell-nav-item {{ $active === $item['active'] ? 'active' : '' }}">
                    <span class="inline-fa-icon" aria-hidden="true">{!! $item['icon'] !!}</span>
                    <span class="chief-shell-nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="chief-shell-sidebar-footer p-4 border-t border-slate-100">
        <div class="chief-shell-profile-card bg-amber-50/50 rounded-2xl p-4 border border-amber-100/50">
            <div class="flex items-center gap-3">
                <div class="chief-shell-profile-avatar w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold border border-amber-200">
                    {{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="chief-shell-profile-name text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name }}</p>
                    <p class="chief-shell-profile-role text-[10px] text-slate-500 font-medium">Provincial Administrator</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<nav class="mobile-bottom-nav chief-mobile-dock block fixed bottom-0 left-0 right-0 z-[9999] md:hidden" aria-label="Mobile primary navigation">
    <div class="chief-mobile-dock__shell">
        <div class="chief-mobile-dock__track">
            <a href="{{ route($mobileDockItems[0]['route']) }}" class="chief-mobile-dock__item {{ $active === $mobileDockItems[0]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[0]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[0]['mobile_icon'] }}</span>
            </a>
            <button type="button" class="chief-mobile-dock__item" aria-label="{{ $mobileDockItems[1]['label'] }}" data-admin-notifications-open>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[1]['mobile_icon'] }}</span>
                <span class="command-notification-mobile-badge" data-command-notification-mobile-badge hidden>0</span>
            </button>
            <div class="chief-mobile-dock__center-wrap {{ $active === $mobileCenterItem['active'] ? 'is-active' : '' }}" data-admin-nav-center>
                <div class="chief-mobile-dock__burst" data-admin-nav-burst>
                    @foreach ($mobileCenterActions as $action)
                        <a href="{{ route($action['route']) }}" class="chief-mobile-dock__burst-item {{ $active === $action['active'] ? 'active' : '' }}" aria-label="{{ $action['label'] }}">
                            <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__burst-icon" aria-hidden="true">{{ $action['mobile_icon'] }}</span>
                            <span class="chief-mobile-dock__burst-label">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                <a href="{{ route($mobileCenterItem['route']) }}" class="chief-mobile-dock__item chief-mobile-dock__item--center {{ $active === $mobileCenterItem['active'] ? 'active' : '' }}" aria-label="{{ $mobileCenterItem['label'] }}" data-admin-nav-trigger>
                    <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileCenterItem['mobile_icon'] }}</span>
                </a>
            </div>
            <a href="{{ route($mobileDockItems[2]['route']) }}" class="chief-mobile-dock__item {{ $active === $mobileDockItems[2]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[2]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[2]['mobile_icon'] }}</span>
            </a>
            <button type="button" class="chief-mobile-dock__item" aria-label="{{ $mobileDockItems[3]['label'] }}" data-admin-settings-open>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[3]['mobile_icon'] }}</span>
            </button>
        </div>
    </div>
</nav>

<div class="chief-mobile-panel md:hidden" data-admin-settings-panel aria-hidden="true">
    <div class="chief-mobile-panel__backdrop" data-admin-panel-close></div>
    <div class="chief-mobile-panel__sheet">
        <div class="chief-mobile-panel__header">
            <div>
                <p class="chief-mobile-panel__eyebrow">Provincial Command</p>
                <h2 class="chief-mobile-panel__title">Settings</h2>
            </div>
            <button type="button" class="chief-mobile-panel__close" data-admin-panel-close aria-label="Close settings">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="chief-mobile-panel__menu">
            <a href="{{ route('admin.dashboard') }}" class="chief-mobile-panel__action">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">space_dashboard</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Dashboard</strong>
                    <small>Return to the provincial command center</small>
                </span>
            </a>
            <a href="{{ route('admin.analytics') }}" class="chief-mobile-panel__action">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">monitoring</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Analytics</strong>
                    <small>Open provincial predictive analytics</small>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="chief-mobile-panel__action chief-mobile-panel__action--danger">
                    <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">logout</span>
                    <span class="chief-mobile-panel__action-copy">
                        <strong>Logout</strong>
                        <small>Sign out of Provincial Command</small>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="chief-mobile-panel md:hidden" data-admin-notifications-panel aria-hidden="true">
    <div class="chief-mobile-panel__backdrop" data-admin-notifications-close></div>
    <div class="chief-mobile-panel__sheet chief-mobile-panel__sheet--notifications">
        <div class="chief-mobile-panel__header">
            <div>
                <p class="chief-mobile-panel__eyebrow">Provincial Command</p>
                <h2 class="chief-mobile-panel__title">Notifications</h2>
            </div>
            <button type="button" class="chief-mobile-panel__close" data-admin-notifications-close aria-label="Close notifications">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="chief-mobile-panel__body" data-command-notification-mobile>
            <div class="chief-mobile-panel__empty">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">notifications</span>
                <p>No mobile notifications yet.</p>
            </div>
        </div>
    </div>
</div>
@include('partials.command-notification-center', ['notificationRoutePrefix' => 'admin'])

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dock = document.querySelector('[data-admin-nav-center]');
        const trigger = document.querySelector('[data-admin-nav-trigger]');
        const settingsPanel = document.querySelector('[data-admin-settings-panel]');
        const notificationsPanel = document.querySelector('[data-admin-notifications-panel]');
        const settingsOpen = document.querySelector('[data-admin-settings-open]');
        const notificationsOpen = document.querySelector('[data-admin-notifications-open]');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const openSidebar = document.getElementById('openSidebar');

        if (openSidebar && sidebar && sidebarBackdrop) {
            openSidebar.addEventListener('click', () => {
                sidebar.classList.add('open', 'active');
                sidebarBackdrop.classList.remove('hidden');
                document.body.classList.add('menu-open');
            });
            sidebarBackdrop.addEventListener('click', () => {
                sidebar.classList.remove('open', 'active');
                sidebarBackdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
            });
        }

        if (!dock || !trigger) {
            return;
        }

        let holdTimer = null;
        let holdActivated = false;
        const holdDelay = 360;

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
            if (!panel) return;
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
        };

        const closePanel = (panel) => {
            if (!panel) return;
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.chief-mobile-panel.is-open')) {
                document.body.classList.remove('menu-open');
            }
        };

        trigger.addEventListener('pointerdown', () => {
            holdActivated = false;
            clearHoldTimer();
            holdTimer = window.setTimeout(openBurst, holdDelay);
        });

        trigger.addEventListener('pointerup', () => clearHoldTimer());
        trigger.addEventListener('pointerleave', () => clearHoldTimer());
        trigger.addEventListener('click', () => {
            if (holdActivated) {
                closeBurst();
            }
        });

        document.addEventListener('click', (event) => {
            if (!dock.contains(event.target)) {
                closeBurst();
            }
        });

        settingsOpen?.addEventListener('click', () => openPanel(settingsPanel));
        notificationsOpen?.addEventListener('click', () => {
            window.refreshCommandNotifications?.();
            openPanel(notificationsPanel);
        });

        document.querySelectorAll('[data-admin-panel-close]').forEach((button) => {
            button.addEventListener('click', () => closePanel(settingsPanel));
        });

        document.querySelectorAll('[data-admin-notifications-close]').forEach((button) => {
            button.addEventListener('click', () => closePanel(notificationsPanel));
        });
    });
</script>
