@php
    $active = $active ?? '';
    $alertCount = $activeAlerts ?? 0;
    $navGroups = [
        [
            'title' => 'Command Center',
            'items' => [
                ['label' => 'Operations Room', 'route' => 'station-admin.dashboard', 'active' => 'dashboard', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>'],
                ['label' => 'FSIC Records', 'route' => 'station-admin.fsic', 'active' => 'fsic', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14l-2-1-2 1-2-1-2 1-2-1-2 1V3z"/><path d="M14 3v4h4M9 11h6M9 15h6"/></svg>', 'badge' => $alertCount > 0 ? $alertCount : null],
                ['label' => 'Station Management', 'route' => 'station-admin.management', 'active' => 'management', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 20V5l7-2v17"/><path d="M12 20V9h7v11"/><path d="M8 8h1M8 11h1M8 14h1M15 12h1M15 15h1"/></svg>'],
            ],
        ],
        [
            'title' => 'Strategic Assets',
            'items' => [
                ['label' => 'Resources', 'route' => 'station-admin.resource-management.index', 'active' => 'resources', 'icon' => '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" fill="none"/><rect x="13" y="4" width="7" height="7" fill="none"/><rect x="8.5" y="13" width="7" height="7" fill="none"/></svg>'],
                ['label' => 'Barangays', 'route' => 'station-admin.barangays.index', 'active' => 'barangays', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 21s7-4.6 7-11a7 7 0 1 0-14 0c0 6.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>'],
                ['label' => 'Risk Reviews', 'route' => 'station-admin.risk-reviews.index', 'active' => 'risk-reviews', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.6-2.9 7.8-7 9-4.1-1.2-7-4.4-7-9V7z"/><path d="M9.5 12l1.8 1.8L15 10.2"/></svg>'],
                ['label' => 'Mitigation Control', 'route' => 'station-admin.mitigations', 'active' => 'mitigations', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.6-2.9 7.8-7 9-4.1-1.2-7-4.4-7-9V7z"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>'],
                ['label' => 'Fire Risk Forecast', 'route' => 'station-admin.analytics', 'active' => 'analytics', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M5 15l4-4 4 2 6-6"/></svg>'],
                ['label' => 'Course Checklists', 'route' => 'station-admin.course-checklists', 'active' => 'course-checklists', 'icon' => '<svg viewBox="0 0 24 24"><path d="M9 11l2 2 4-4"/><path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>'],
                ['label' => 'Executive Reports', 'route' => 'station-admin.reports', 'active' => 'reports', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M9 11h6M9 15h4"/></svg>'],
                ['label' => 'Activity Schedule', 'route' => 'station-admin.schedule', 'active' => 'schedule', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3v4"/><path d="M17 3v4"/><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 17h3"/></svg>'],
            ],
        ],
        [
            'title' => 'Communication',
            'items' => [
                ['label' => 'Announcements', 'route' => 'station-admin.announcements', 'active' => 'announcements', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 11v2"/><path d="M6 10h3l7-4v12l-7-4H6z"/><path d="M9 14l1.5 4"/></svg>'],
            ],
        ],
    ];
    $mobileDockItems = [
        ['label' => 'Activity Schedule', 'route' => 'station-admin.schedule', 'active' => 'schedule', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3v4"/><path d="M17 3v4"/><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 17h3"/></svg>', 'mobile_icon' => 'event'],
        ['label' => 'Notifications', 'active' => 'notifications', 'mobile_icon' => 'notifications'],
        ['label' => 'Fire Risk Forecast', 'route' => 'station-admin.analytics', 'active' => 'analytics', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M5 15l4-4 4 2 6-6"/></svg>', 'mobile_icon' => 'monitoring'],
        ['label' => 'Settings', 'active' => 'settings', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3.75l1.12 2.26 2.5.37-1.81 1.77.43 2.5L12 9.47l-2.24 1.18.43-2.5-1.81-1.77 2.5-.37L12 3.75z"/><circle cx="12" cy="12" r="3.25"/><path d="M4.8 14.2l1.92.34.67 1.63-1.11 1.61 1.53 1.53 1.61-1.11 1.63.67.34 1.92h2.16l.34-1.92 1.63-.67 1.61 1.11 1.53-1.53-1.11-1.61.67-1.63 1.92-.34v-2.16l-1.92-.34-.67-1.63 1.11-1.61-1.53-1.53-1.61 1.11-1.63-.67-.34-1.92h-2.16l-.34 1.92-1.63.67-1.61-1.11-1.53 1.53 1.11 1.61-.67 1.63-1.92.34z"/></svg>', 'mobile_icon' => 'settings'],
    ];
    $mobileCenterItem = ['label' => 'Operations Room', 'route' => 'station-admin.dashboard', 'active' => 'dashboard', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>', 'mobile_icon' => 'dashboard'];
    $mobileCenterActions = [
        ['label' => 'FSIC Records', 'route' => 'station-admin.fsic', 'active' => 'fsic', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14l-2-1-2 1-2-1-2 1-2-1-2 1V3z"/><path d="M14 3v4h4M9 11h6M9 15h6"/></svg>', 'mobile_icon' => 'folder_open'],
        ['label' => 'Risk Reviews', 'route' => 'station-admin.risk-reviews.index', 'active' => 'risk-reviews', 'mobile_icon' => 'fact_check'],
        ['label' => 'Mitigation Control', 'route' => 'station-admin.mitigations', 'active' => 'mitigations', 'mobile_icon' => 'task_alt'],
        ['label' => 'Executive Reports', 'route' => 'station-admin.reports', 'active' => 'reports', 'icon' => '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M9 11h6M9 15h4"/></svg>', 'mobile_icon' => 'description'],
        ['label' => 'Resources', 'route' => 'station-admin.resource-management.index', 'active' => 'resources', 'icon' => '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" fill="none"/><rect x="13" y="4" width="7" height="7" fill="none"/><rect x="8.5" y="13" width="7" height="7" fill="none"/></svg>', 'mobile_icon' => 'inventory_2'],
        ['label' => 'Barangays', 'route' => 'station-admin.barangays.index', 'active' => 'barangays', 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 21s7-4.6 7-11a7 7 0 1 0-14 0c0 6.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>', 'mobile_icon' => 'location_city'],
        ['label' => 'Station Management', 'route' => 'station-admin.management', 'active' => 'management', 'icon' => '<svg viewBox="0 0 24 24"><path d="M5 20V5l7-2v17"/><path d="M12 20V9h7v11"/><path d="M8 8h1M8 11h1M8 14h1M15 12h1M15 15h1"/></svg>', 'mobile_icon' => 'apartment'],
        ['label' => 'Announcements', 'route' => 'station-admin.announcements', 'active' => 'announcements', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 11v2"/><path d="M6 10h3l7-4v12l-7-4H6z"/><path d="M9 14l1.5 4"/></svg>', 'mobile_icon' => 'campaign'],
        ['label' => 'Course Checklists', 'route' => 'station-admin.course-checklists', 'active' => 'course-checklists', 'mobile_icon' => 'checklist'],
    ];
@endphp

@if ($active !== 'dashboard')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dashboardUrl = @json(route('station-admin.dashboard'));
            const dashboardAssets = [
                @json(Vite::asset('resources/css/chief/dashboard.css')),
                @json(Vite::asset('resources/css/app.css')),
                @json(Vite::asset('resources/js/map.js')),
            ];
            const dashboardApiUrls = [
                @json(route('station-admin.dashboard-stats')),
                @json(url('/api/incident/latest')),
            ];
            let dashboardPrefetched = false;

            const appendPrefetchLink = (href, as = 'fetch') => {
                if (!href || document.head.querySelector(`link[rel="prefetch"][href="${href}"]`)) {
                    return;
                }

                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = href;
                link.as = as;
                document.head.appendChild(link);
            };

            const prefetchDashboard = () => {
                if (dashboardPrefetched) {
                    return;
                }

                dashboardPrefetched = true;
                appendPrefetchLink(dashboardUrl, 'document');
                dashboardAssets.forEach((assetHref) => {
                    const as = assetHref.endsWith('.css') ? 'style' : 'script';
                    appendPrefetchLink(assetHref, as);
                });
                dashboardApiUrls.forEach((apiUrl) => appendPrefetchLink(apiUrl, 'fetch'));
            };

            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(prefetchDashboard, { timeout: 1500 });
            } else {
                window.setTimeout(prefetchDashboard, 900);
            }

            document
                .querySelectorAll('a[href="' + dashboardUrl + '"]')
                .forEach((link) => {
                    ['mouseenter', 'focus', 'touchstart', 'pointerdown'].forEach((eventName) => {
                        link.addEventListener(eventName, prefetchDashboard, { passive: true, once: true });
                    });
                });
        });
    </script>
@endif

<aside class="sidebar chief-shell-sidebar hidden md:flex" id="sidebar">
    <div class="sidebar-header-gradient chief-shell-sidebar-header px-6 py-8">
        <div class="chief-shell-brand flex items-center gap-3">
            <div class="chief-shell-brand-icon w-20 h-14 bg-white rounded-2xl flex items-center justify-center border border-white/60 shadow-sm px-2 py-1">
                @include('partials.app-logo', ['class' => 'chief-shell-brand-logo w-full h-full object-contain'])
            </div>
            <div class="chief-shell-brand-copy">
                <h2 class="chief-shell-brand-title text-white font-bold text-xl leading-tight">PyroSense</h2>
                <p class="chief-shell-brand-subtitle text-white/70 text-xs uppercase tracking-widest">Chief Command</p>
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
                    @if (!empty($item['badge']))
                        <span class="ml-auto bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="chief-shell-sidebar-footer p-4 border-t border-slate-100">
        <div class="chief-shell-profile-card bg-amber-50/50 rounded-2xl p-4 border border-amber-100/50">
            <div class="flex items-center gap-3">
                <div class="chief-shell-profile-avatar w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold border border-amber-200">
                    {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="chief-shell-profile-name text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name }}</p>
                    <p class="chief-shell-profile-role text-[10px] text-slate-500 font-medium">Station Commander</p>
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

            <button type="button" class="chief-mobile-dock__item" aria-label="{{ $mobileDockItems[1]['label'] }}" data-chief-notifications-open>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[1]['mobile_icon'] }}</span>
            </button>

            <div class="chief-mobile-dock__center-wrap {{ $active === $mobileCenterItem['active'] ? 'is-active' : '' }}" data-chief-nav-center>
                <div class="chief-mobile-dock__burst" data-chief-nav-burst>
                    @foreach ($mobileCenterActions as $action)
                        @if (!empty($action['route']))
                            <a href="{{ route($action['route']) }}" class="chief-mobile-dock__burst-item {{ $active === $action['active'] ? 'active' : '' }}" aria-label="{{ $action['label'] }}">
                                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__burst-icon" aria-hidden="true">{{ $action['mobile_icon'] }}</span>
                                <span class="chief-mobile-dock__burst-label">{{ $action['label'] }}</span>
                            </a>
                        @else
                            <button type="button" class="chief-mobile-dock__burst-item" aria-label="{{ $action['label'] }}" data-chief-insights-open>
                                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__burst-icon" aria-hidden="true">{{ $action['mobile_icon'] }}</span>
                                <span class="chief-mobile-dock__burst-label">{{ $action['label'] }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>

                <a
                    href="{{ route($mobileCenterItem['route']) }}"
                    class="chief-mobile-dock__item chief-mobile-dock__item--center {{ $active === $mobileCenterItem['active'] ? 'active' : '' }}"
                    aria-label="{{ $mobileCenterItem['label'] }}"
                    data-chief-nav-trigger
                >
                    <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileCenterItem['mobile_icon'] }}</span>
                </a>
            </div>

            <a href="{{ route($mobileDockItems[2]['route']) }}" class="chief-mobile-dock__item {{ $active === $mobileDockItems[2]['active'] ? 'active' : '' }}" aria-label="{{ $mobileDockItems[2]['label'] }}">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[2]['mobile_icon'] }}</span>
            </a>

            <button type="button" class="chief-mobile-dock__item {{ $active === 'profile' ? 'active' : '' }}" aria-label="{{ $mobileDockItems[3]['label'] }}" data-chief-settings-open>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-dock__icon" aria-hidden="true">{{ $mobileDockItems[3]['mobile_icon'] }}</span>
            </button>
        </div>
    </div>
</nav>
<div class="chief-mobile-panel md:hidden" data-chief-settings-panel aria-hidden="true">
    <div class="chief-mobile-panel__backdrop" data-chief-panel-close></div>
    <div class="chief-mobile-panel__sheet">
        <div class="chief-mobile-panel__header">
            <div>
                <p class="chief-mobile-panel__eyebrow">Chief Command</p>
                <h2 class="chief-mobile-panel__title">Settings</h2>
            </div>
            <button type="button" class="chief-mobile-panel__close" data-chief-panel-close aria-label="Close settings">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="chief-mobile-panel__menu">
            <button type="button" class="chief-mobile-panel__action" data-chief-open-logs>
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">history</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Logs</strong>
                    <small>View your recent activity</small>
                </span>
            </button>
            <a href="{{ route('station-admin.profile') }}" class="chief-mobile-panel__action">
                <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">person</span>
                <span class="chief-mobile-panel__action-copy">
                    <strong>Profile</strong>
                    <small>Manage your commander account</small>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="chief-mobile-panel__action chief-mobile-panel__action--danger">
                    <span class="material-symbols-rounded chief-mobile-material-icon chief-mobile-panel__action-icon" aria-hidden="true">logout</span>
                    <span class="chief-mobile-panel__action-copy">
                        <strong>Logout</strong>
                        <small>Sign out of Chief Command</small>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
<div class="chief-mobile-panel md:hidden" data-chief-notifications-panel aria-hidden="true">
    <div class="chief-mobile-panel__backdrop" data-chief-notifications-close></div>
    <div class="chief-mobile-panel__sheet chief-mobile-panel__sheet--notifications">
        <div class="chief-mobile-panel__header">
            <div>
                <p class="chief-mobile-panel__eyebrow">Chief Command</p>
                <h2 class="chief-mobile-panel__title">Notifications</h2>
            </div>
            <button type="button" class="chief-mobile-panel__close" data-chief-notifications-close aria-label="Close notifications">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="chief-mobile-panel__body" id="chiefMobileNotificationsContent">
            <div class="chief-mobile-panel__empty">
                <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">notifications</span>
                <p>No mobile notifications yet.</p>
            </div>
        </div>
    </div>
</div>
@include('partials.user-activity-logger')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dock = document.querySelector('[data-chief-nav-center]');
        const trigger = document.querySelector('[data-chief-nav-trigger]');
        const settingsPanel = document.querySelector('[data-chief-settings-panel]');
        const notificationsPanel = document.querySelector('[data-chief-notifications-panel]');
        const settingsOpen = document.querySelector('[data-chief-settings-open]');
        const notificationsOpen = document.querySelector('[data-chief-notifications-open]');
        const insightsOpenButtons = document.querySelectorAll('[data-chief-insights-open]');
        const notificationsContent = document.getElementById('chiefMobileNotificationsContent');
        const isMobileViewport = () => window.innerWidth <= 768;

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
            if (!panel) {
                return;
            }

            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
        };

        const closePanel = (panel) => {
            if (!panel) {
                return;
            }

            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');

            if (!document.querySelector('.chief-mobile-panel.is-open')) {
                document.body.classList.remove('menu-open');
            }
        };

        const syncMobileNotifications = () => {
            if (!notificationsContent) {
                return;
            }

            const source = document.getElementById('alertsDropdownList');

            if (!source) {
                notificationsContent.innerHTML = `
                    <div class="chief-mobile-panel__empty">
                        <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">notifications</span>
                        <p>Notifications are not available on this page.</p>
                    </div>
                `;
                return;
            }

            notificationsContent.innerHTML = source.innerHTML;
        };

        const startHold = () => {
            clearHoldTimer();
            holdTimer = window.setTimeout(openBurst, holdDelay);
        };

        const endHold = () => {
            clearHoldTimer();
        };

        trigger.addEventListener('touchstart', startHold, { passive: true });
        trigger.addEventListener('mousedown', startHold);
        trigger.addEventListener('touchend', endHold);
        trigger.addEventListener('touchcancel', endHold);
        trigger.addEventListener('mouseleave', endHold);
        trigger.addEventListener('mouseup', endHold);

        trigger.addEventListener('click', (event) => {
            if (holdActivated) {
                event.preventDefault();
            }
        });

        document.addEventListener('click', (event) => {
            if (!dock.contains(event.target)) {
                closeBurst();
            }
        });

        settingsOpen?.addEventListener('click', () => {
            closeBurst();
            openPanel(settingsPanel);
        });

        notificationsOpen?.addEventListener('click', () => {
            closeBurst();
            syncMobileNotifications();
            openPanel(notificationsPanel);
        });

        insightsOpenButtons.forEach((button) => {
            button.addEventListener('click', () => {
                closeBurst();
                document.dispatchEvent(new CustomEvent('chief:open-insights'));
            });
        });

        settingsPanel?.querySelectorAll('[data-chief-panel-close]').forEach((button) => {
            button.addEventListener('click', () => closePanel(settingsPanel));
        });

        notificationsPanel?.querySelectorAll('[data-chief-notifications-close]').forEach((button) => {
            button.addEventListener('click', () => closePanel(notificationsPanel));
        });

        settingsPanel?.querySelector('[data-chief-open-logs]')?.addEventListener('click', () => {
            closePanel(settingsPanel);
            if (typeof window.openChiefLogsModal === 'function') {
                window.openChiefLogsModal();
            }
        });

        const notificationButton = document.getElementById('notificationBtn');
        const alertsDropdown = document.getElementById('alertsDropdown');

        notificationButton?.addEventListener('click', (event) => {
            if (!isMobileViewport()) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            alertsDropdown?.classList.remove('show');
            alertsDropdown?.classList.add('hidden');
            syncMobileNotifications();
            openPanel(notificationsPanel);
        }, true);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeBurst();
                closePanel(settingsPanel);
                closePanel(notificationsPanel);
            }
        });

        dock.querySelectorAll('.chief-mobile-dock__burst-item').forEach((item) => {
            item.addEventListener('click', () => {
                closeBurst();
            });
        });
    });
</script>
