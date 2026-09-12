@include('partials.unified-notifications')

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
                ['type' => 'link', 'label' => 'Risk Reviews', 'icon' => 'fas fa-triangle-exclamation', 'route' => 'firefighter.risk-reviews.index', 'active' => ['risk-reviews']],
                ['type' => 'link', 'label' => 'Checklist', 'icon' => 'fas fa-list-check', 'route' => 'firefighter.barangay-cooperation.index', 'active' => ['cooperation']],
                ['type' => 'link', 'label' => 'My Station', 'icon' => 'fas fa-building', 'route' => 'firefighter.my-station', 'active' => ['station']],
                ['type' => 'link', 'label' => 'Requests', 'icon' => 'fas fa-clipboard-list', 'route' => 'firefighter.equipments', 'active' => ['equipment', 'fsic']],
            ],
        ],
        [
            'title' => 'Communications',
            'items' => [
                ['type' => 'link', 'label' => 'Announcements', 'icon' => 'fas fa-bullhorn', 'route' => 'firefighter.announcements', 'active' => ['announcements']],
                ['type' => 'link', 'label' => 'My Schedule', 'icon' => 'fas fa-calendar', 'route' => 'firefighter.schedule', 'active' => ['schedule']],
            ],
        ],
    ];
    $firefighterInitials = strtoupper(substr(auth()->user()->first_name ?? 'F', 0, 1) . substr(auth()->user()->last_name ?? 'R', 0, 1));
    $firefighterImageUrl = \App\Support\MediaPath::url(auth()->user()->image ?? null);
    $mobileDockIsOpsActive = $currentActive === 'dashboard';
    $mobileMenuItems = [
        ['label' => 'Incidents', 'description' => 'Review active incidents and field assignments.', 'route' => 'firefighter.incidents', 'active' => ['incidents'], 'key' => 'incidents', 'icon' => 'local_fire_department'],
        ['label' => 'Risk Reviews', 'description' => 'Verify risk and hazard submissions for your station.', 'route' => 'firefighter.risk-reviews.index', 'active' => ['risk-reviews'], 'key' => 'risk-reviews', 'icon' => 'fact_check'],
        ['label' => 'Checklist', 'description' => 'Review barangay plans and update assigned steps.', 'route' => 'firefighter.barangay-cooperation.index', 'active' => ['cooperation'], 'key' => 'cooperation', 'icon' => 'checklist'],
        ['label' => 'My Station', 'description' => 'Open personnel, apparatus, and investigation reports.', 'route' => 'firefighter.my-station', 'active' => ['station'], 'key' => 'station', 'icon' => 'apartment'],
        ['label' => 'Requests', 'description' => 'Manage equipment and service requests.', 'route' => 'firefighter.equipments', 'active' => ['equipment', 'fsic'], 'key' => 'equipment', 'icon' => 'assignment'],
    ];
    $mobileMenuIsActive = $currentActive === 'profile'
        || collect($mobileMenuItems)->contains(fn (array $item) => in_array($currentActive, $item['active'], true));
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:FILL@1&display=swap');

    /* Shared firefighter layer scale. Keep map content inside the map layer so
       Leaflet panes cannot cover navigation, notifications, or dialogs. */
    :root {
        --ff-layer-map: 0;
        --ff-layer-map-ui: 100;
        --ff-layer-page-chrome: 1000;
        --ff-layer-mobile-dock: 2000;
        --ff-layer-floating-action: 2100;
        --ff-layer-sidebar-backdrop: 3000;
        --ff-layer-sidebar: 3010;
        --ff-layer-routing-backdrop: 4000;
        --ff-layer-routing-panel: 4010;
        --ff-layer-mobile-panel: 5000;
        --ff-layer-notice: 6000;
        --ff-layer-modal: 7000;
        --ff-layer-critical: 8000;
    }

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
        z-index: var(--ff-layer-mobile-panel);
        display: none;
        isolation: isolate;
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
        z-index: 1;
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

    .ff-mobile-panel__section-label {
        margin: 2px 4px -4px;
        color: #9a3412;
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .ff-mobile-panel__divider {
        height: 1px;
        margin: 4px 2px;
        background: rgba(148, 163, 184, 0.22);
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

    .ff-mobile-panel__action.active {
        border-color: rgba(198, 61, 38, 0.3);
        background: rgba(255, 247, 237, 0.98);
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

    .ff-mobile-panel__action-trailing {
        width: 24px;
        height: 24px;
        margin-left: auto;
        color: #94a3b8;
        font-size: 20px;
        flex-shrink: 0;
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

    .ff-route-skeleton {
        position: fixed;
        z-index: var(--ff-layer-critical);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.14s ease, visibility 0.14s ease;
    }

    .ff-route-skeleton.is-active {
        opacity: 1;
        visibility: visible;
    }

    #{{ $backdropId }}.hidden {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .ff-route-skeleton__surface {
        width: 100%;
        height: 100%;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(198, 61, 38, 0.08);
        background:
            radial-gradient(circle at top right, rgba(209, 138, 22, 0.14), transparent 24%),
            linear-gradient(180deg, rgba(255, 250, 244, 0.98) 0%, rgba(251, 247, 239, 0.99) 100%);
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08);
    }

    .ff-route-skeleton__content {
        height: 100%;
        overflow: hidden;
        padding: 22px 24px 28px;
        display: grid;
        gap: 22px;
    }

    .ff-route-skeleton__topbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .ff-route-skeleton__summary {
        display: grid;
        gap: 10px;
        padding: 28px;
        border-radius: 24px;
        background: linear-gradient(135deg, #fff4ec 0%, #fff8f0 44%, #ffffff 100%);
        border: 1px solid rgba(198, 61, 38, 0.12);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
    }

    .ff-route-skeleton__stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 18px;
    }

    .ff-route-skeleton__layout-two {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
        gap: 22px;
    }

    .ff-route-skeleton__toolbar-card,
    .ff-route-skeleton__list-card,
    .ff-route-skeleton__line,
    .ff-route-skeleton__pill,
    .ff-route-skeleton__stat,
    .ff-route-skeleton__list-item {
        position: relative;
        overflow: hidden;
    }

    .ff-route-skeleton__line::after,
    .ff-route-skeleton__pill::after,
    .ff-route-skeleton__stat::after,
    .ff-route-skeleton__toolbar-card::after,
    .ff-route-skeleton__list-card::after,
    .ff-route-skeleton__list-item::after {
        content: '';
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.7) 50%, transparent 100%);
        animation: ffRouteShimmer 1.1s ease-in-out infinite;
    }

    .ff-route-skeleton__line,
    .ff-route-skeleton__pill {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 999px;
    }

    .ff-route-skeleton__line--title {
        width: min(280px, 72%);
        height: 20px;
    }

    .ff-route-skeleton__line--subtitle {
        width: min(220px, 54%);
        height: 14px;
    }

    .ff-route-skeleton__line--meta {
        width: min(180px, 40%);
        height: 14px;
    }

    .ff-route-skeleton__pill {
        width: 124px;
        height: 38px;
    }

    .ff-route-skeleton__pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ff-route-skeleton__topbar-actions {
        display: flex;
        gap: 12px;
    }

    .ff-route-skeleton__stat {
        min-height: 142px;
        border-radius: 22px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
    }

    .ff-route-skeleton__toolbar-card {
        min-height: 86px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
    }

    .ff-route-skeleton__list-card {
        min-height: 430px;
        padding: 22px 24px 24px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        display: grid;
        gap: 14px;
    }

    .ff-route-skeleton__panel {
        min-height: 320px;
        padding: 22px 24px 24px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        display: grid;
        gap: 14px;
    }

    .ff-route-skeleton__feed-card {
        min-height: 120px;
        border-radius: 22px;
        background: linear-gradient(135deg, #ffffff 0%, #fff8f2 100%);
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
    }

    .ff-route-skeleton__calendar {
        min-height: 520px;
        border-radius: 30px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
    }

    .ff-route-skeleton__agenda {
        min-height: 520px;
        border-radius: 30px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
    }

    .ff-route-skeleton__list-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .ff-route-skeleton__list-items {
        display: grid;
        gap: 14px;
    }

    .ff-route-skeleton__list-item {
        min-height: 138px;
        border-radius: 20px;
        background: linear-gradient(135deg, #ffffff 0%, #fff9f4 100%);
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
    }

    @keyframes ffRouteShimmer {
        100% {
            transform: translateX(100%);
        }
    }

    body.firefighter-portal-shell .firefighter-map-stage {
        z-index: var(--ff-layer-map) !important;
        isolation: isolate !important;
    }

    body.firefighter-portal-shell .firefighter-map-stage .map-container,
    body.firefighter-portal-shell .firefighter-map-stage .leaflet-container {
        position: relative;
        z-index: var(--ff-layer-map) !important;
        isolation: isolate;
    }

    body.firefighter-portal-shell .firefighter-map-stage .leaflet-control-container {
        position: relative;
        z-index: var(--ff-layer-map-ui);
    }

    body.firefighter-portal-shell .sidebar.ff-sidebar-shell {
        z-index: var(--ff-layer-sidebar) !important;
        isolation: isolate;
    }

    body.firefighter-portal-shell #{{ $backdropId }} {
        z-index: var(--ff-layer-sidebar-backdrop) !important;
    }

    body.firefighter-portal-shell .mobile-bottom-nav--firefighter-app {
        z-index: var(--ff-layer-mobile-dock) !important;
        isolation: isolate;
    }

    body.firefighter-portal-shell .ff-mobile-panel {
        z-index: var(--ff-layer-mobile-panel) !important;
    }

    body.firefighter-portal-shell .ff-app-notice,
    body.firefighter-portal-shell .notification-container,
    body.firefighter-portal-shell .incident-alert-toast-stack {
        z-index: var(--ff-layer-notice) !important;
    }

    body.firefighter-portal-shell .boundary-save-modal,
    body.firefighter-portal-shell .route-source-modal,
    body.firefighter-portal-shell .responder-map-modal,
    body.firefighter-portal-shell .edit-modal,
    body.firefighter-portal-shell .ff-false-alarm-confirm {
        z-index: var(--ff-layer-modal) !important;
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
            z-index: var(--ff-layer-mobile-dock) !important;
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
            width: min(calc(100vw - 28px), 344px);
            margin: 0 auto;
            min-height: 66px;
            padding: 12px 12px 11px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(20, 83, 90, 0.98) 0%, rgba(16, 75, 84, 0.96) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.24);
            display: grid;
            grid-template-columns: minmax(88px, 1fr) 82px minmax(88px, 1fr);
            align-items: end;
            justify-items: center;
            gap: 0;
            box-sizing: border-box;
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
            width: 44px;
            height: 44px;
            padding: 0;
            border: none;
            background: transparent;
            color: rgba(214, 227, 230, 0.5);
            display: inline-flex !important;
            align-items: center;
            flex-direction: column;
            justify-content: center;
            justify-self: center;
            text-decoration: none;
            transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            border-radius: 999px;
            z-index: 1;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__group {
            position: relative;
            z-index: 2;
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: repeat(2, 44px);
            align-items: center;
            justify-items: center;
            justify-content: space-evenly;
            column-gap: 0;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__group--left {
            grid-column: 1;
            padding-right: 0;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__group--right {
            grid-column: 3;
            padding-left: 0;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-notifications],
        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-settings] {
            position: relative !important;
            inset: auto !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            bottom: auto !important;
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            min-height: 44px !important;
            margin: 0 !important;
            transform: none !important;
            background: transparent !important;
            background-image: none !important;
            color: rgba(214, 227, 230, 0.5) !important;
            border: 0 !important;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-notifications]::before,
        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-notifications]::after {
            content: none !important;
            display: none !important;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-notifications].active,
        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-settings].active {
            color: #ffffff !important;
            background: transparent !important;
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

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__label {
            display: block;
            max-width: 52px;
            margin-top: 2px;
            overflow: hidden;
            color: currentColor;
            font-size: 8px;
            font-weight: 750;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center {
            position: absolute;
            left: 50%;
            top: 12px;
            width: 66px;
            height: 66px;
            min-width: 66px;
            min-height: 66px;
            margin: -30px 0 0;
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
            z-index: 3;
            transform: translateX(-50%);
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

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-notifications] .ff-mobile-icon,
        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__item[data-ff-open-settings] .ff-mobile-icon {
            color: currentColor !important;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__center .ff-mobile-icon {
            font-size: 24px;
        }

        .ff-route-skeleton__surface {
            border-radius: 0;
            box-shadow: none;
        }

        .ff-route-skeleton__content {
            padding: 18px 16px calc(20px + env(safe-area-inset-bottom, 0px));
            gap: 16px;
        }

        .ff-route-skeleton__topbar,
        .ff-route-skeleton__list-header {
            flex-direction: column;
            align-items: stretch;
        }

        .ff-route-skeleton__summary,
        .ff-route-skeleton__toolbar-card,
        .ff-route-skeleton__list-card,
        .ff-route-skeleton__stat,
        .ff-route-skeleton__list-item {
            border-radius: 20px;
        }

        .ff-route-skeleton__stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .ff-route-skeleton__layout-two {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .ff-route-skeleton__list-card {
            min-height: 360px;
        }

        .ff-route-skeleton__panel,
        .ff-route-skeleton__calendar,
        .ff-route-skeleton__agenda,
        .ff-route-skeleton__feed-card {
            border-radius: 20px;
        }

        .ff-route-skeleton__calendar,
        .ff-route-skeleton__agenda {
            min-height: 320px;
        }
    }
</style>

<aside class="sidebar ff-sidebar-shell hidden md:flex" id="{{ $sidebarId }}">
    <div class="sidebar-header-gradient px-6 py-8">
        <div class="ff-shell-brand flex items-center gap-3">
            <div class="ff-shell-brand-icon w-14 h-14 bg-white rounded-full flex items-center justify-center border border-white/60 shadow-sm p-2 overflow-hidden">
                @include('partials.app-logo', ['class' => 'ff-shell-brand-logo w-full h-full object-contain'])
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
                    <a href="{{ route($item['route']) }}" class="nav-item {{ $isActive ? 'active' : '' }}" data-ff-nav-link data-ff-nav-key="{{ $item['active'][0] ?? '' }}">
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
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold border border-red-200 overflow-hidden" data-firefighter-avatar-frame>
                    @if ($firefighterImageUrl)
                        <img src="{{ $firefighterImageUrl }}" alt="{{ auth()->user()->first_name ?? 'Firefighter' }}" class="h-full w-full object-cover" data-firefighter-avatar-image>
                    @endif
                    <span @if ($firefighterImageUrl) style="display:none;" @endif data-firefighter-avatar-fallback>{{ $firefighterInitials }}</span>
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
        <div class="ff-mobile-dock__rail">
            <div class="ff-mobile-dock__group ff-mobile-dock__group--left">
                <a href="{{ route('firefighter.schedule') }}" class="ff-mobile-dock__item {{ $currentActive === 'schedule' ? 'active' : '' }}" aria-label="Schedule" data-ff-nav-link data-ff-nav-key="schedule">
                    <span class="ff-mobile-icon" aria-hidden="true">calendar_month</span>
                    <span class="ff-mobile-dock__label">Schedule</span>
                </a>
                <button type="button" class="ff-mobile-dock__item" data-ff-open-notifications aria-label="Notifications">
                    <span class="ff-mobile-icon" aria-hidden="true">notifications</span>
                    <span class="ff-mobile-dock__label">Alerts</span>
                    <span class="command-notification-mobile-badge" data-command-notification-mobile-badge hidden>0</span>
                </button>
            </div>
            <button type="button" class="ff-mobile-dock__center {{ $mobileDockIsOpsActive ? 'active' : '' }}" data-ff-dashboard-fab aria-label="Dashboard">
                <span class="ff-mobile-icon" aria-hidden="true">dashboard</span>
            </button>
            <div class="ff-mobile-dock__group ff-mobile-dock__group--right">
                <a href="{{ route('firefighter.announcements') }}" class="ff-mobile-dock__item {{ $currentActive === 'announcements' ? 'active' : '' }}" aria-label="Announcements" data-ff-nav-link data-ff-nav-key="announcements">
                    <span class="ff-mobile-icon" aria-hidden="true">campaign</span>
                    <span class="ff-mobile-dock__label">Updates</span>
                </a>
                <button type="button" class="ff-mobile-dock__item {{ $mobileMenuIsActive ? 'active' : '' }}" data-ff-open-settings aria-label="Open Responder Hub menu">
                    <span class="ff-mobile-icon" aria-hidden="true">menu</span>
                    <span class="ff-mobile-dock__label">Menu</span>
                </button>
            </div>
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
            <div data-command-notification-mobile></div>
            @php
                // Production stores notifications.data as TEXT, so filter by the
                // notification class instead of applying JSON operators to data.
                $notificationUser = auth()->user();
                $aarNotifications = $notificationUser
                    ? $notificationUser->notifications()
                        ->where('type', \App\Notifications\AfterActivityReportReviewedNotification::class)
                        ->latest()
                        ->limit(5)
                        ->get()
                    : collect();
            @endphp
            @foreach($aarNotifications as $notification)
                <a href="{{ data_get($notification->data, 'url', route('firefighter.my-station', ['tab' => 'aar'])) }}" class="ff-mobile-panel__action" data-aar-notification-id="{{ $notification->id }}">
                    <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">{{ $notification->read_at ? 'description' : 'notifications_active' }}</span></span>
                    <span class="ff-mobile-panel__copy"><strong>{{ data_get($notification->data, 'title', 'AAR update') }}</strong><small>{{ data_get($notification->data, 'message') }}</small></span>
                </a>
            @endforeach
            <a href="{{ route('firefighter.incidents') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="incidents">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">local_fire_department</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Active Incidents</strong>
                    <small>Check incident updates and field assignments.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.risk-reviews.index') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="risk-reviews">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">fact_check</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Risk Reviews</strong>
                    <small>Verify pending hazard and risk submissions for your station.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.barangay-cooperation.index') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="cooperation">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">diversity_3</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Checklist</strong>
                    <small>Review connected barangay plans and verify checklist steps during visits.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.my-station') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="station">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">apartment</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>My Station</strong>
                    <small>Open station personnel, equipment, and investigation reports.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.announcements') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="announcements">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">campaign</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Announcements</strong>
                    <small>Read station advisories and recent internal posts.</small>
                </span>
            </a>
            <a href="{{ route('firefighter.schedule') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="schedule">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">calendar_month</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Duty Schedule</strong>
                    <small>Review upcoming shifts, drills, and station activities.</small>
                </span>
            </a>
        </div>
    </div>
</div>

@include('partials.command-notification-center', [
    'commandNotificationUrls' => [
        'index' => route('firefighter.notifications.index'),
        'read_all' => route('firefighter.notifications.read-all'),
        'read' => route('firefighter.notifications.read', ['notificationId' => '__ID__']),
    ],
])

<div class="ff-mobile-panel md:hidden" data-ff-settings-panel aria-hidden="true">
    <div class="ff-mobile-panel__backdrop" data-ff-close-panel></div>
    <div class="ff-mobile-panel__sheet">
        <div class="ff-mobile-panel__header">
            <div>
                <p class="ff-mobile-panel__eyebrow">Responder Hub</p>
                <h2 class="ff-mobile-panel__title">Menu</h2>
            </div>
            <button type="button" class="ff-mobile-panel__close" data-ff-close-panel aria-label="Close menu">
                <span class="ff-mobile-icon" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="ff-mobile-panel__list">
            <p class="ff-mobile-panel__section-label">Responder navigation</p>
            @foreach ($mobileMenuItems as $item)
                <a href="{{ route($item['route']) }}" class="ff-mobile-panel__action {{ count(array_intersect($item['active'], [$currentActive])) ? 'active' : '' }}" data-ff-nav-link data-ff-nav-key="{{ $item['key'] }}">
                    <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">{{ $item['icon'] }}</span></span>
                    <span class="ff-mobile-panel__copy">
                        <strong>{{ $item['label'] }}</strong>
                        <small>{{ $item['description'] }}</small>
                    </span>
                    <span class="ff-mobile-icon ff-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </a>
            @endforeach
            <div class="ff-mobile-panel__divider" aria-hidden="true"></div>
            <p class="ff-mobile-panel__section-label">Account</p>
            <a href="{{ route('firefighter.my-profile') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="profile">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">person</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>My Profile</strong>
                    <small>Update personal details and firefighter account info.</small>
                </span>
                <span class="ff-mobile-icon ff-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
            </a>
            <button type="button" class="ff-mobile-panel__action ff-mobile-panel__action--button" data-ff-open-logs>
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">history</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>My Logs</strong>
                    <small>View recent activity and submitted firefighter actions.</small>
                </span>
                <span class="ff-mobile-icon ff-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" data-firefighter-logout-form>
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
@include('partials.voice-call-config')

<div class="ff-route-skeleton" data-ff-route-skeleton="incidents" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--meta"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__summary">
                <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(620px, 92%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(560px, 84%);"></div>
                <div class="ff-route-skeleton__pills">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__stats">
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
            </div>
            <div class="ff-route-skeleton__list-card">
                <div class="ff-route-skeleton__list-header">
                    <div style="display:grid; gap:10px; min-width:0; width:100%;">
                        <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(260px, 46%);"></div>
                        <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(440px, 82%);"></div>
                    </div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
                <div class="ff-route-skeleton__toolbar-card"></div>
                <div class="ff-route-skeleton__list-items">
                    <div class="ff-route-skeleton__list-item"></div>
                    <div class="ff-route-skeleton__list-item"></div>
                    <div class="ff-route-skeleton__list-item"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ff-route-skeleton" data-ff-route-skeleton="announcements" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__panel" style="min-height: 180px;">
                <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(240px, 38%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(520px, 86%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(420px, 72%);"></div>
                <div class="ff-route-skeleton__pills">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__panel" style="min-height: 220px;">
                <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(220px, 32%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(640px, 94%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(610px, 88%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(560px, 82%);"></div>
            </div>
            <div class="ff-route-skeleton__feed-card"></div>
            <div class="ff-route-skeleton__feed-card"></div>
            <div class="ff-route-skeleton__feed-card"></div>
        </div>
    </div>
</div>

<div class="ff-route-skeleton" data-ff-route-skeleton="profile" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__panel" style="min-height: 260px;">
                <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(280px, 42%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(580px, 88%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(520px, 78%);"></div>
                <div class="ff-route-skeleton__stats" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 6px;">
                    <div class="ff-route-skeleton__stat" style="min-height: 86px;"></div>
                    <div class="ff-route-skeleton__stat" style="min-height: 86px;"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__layout-two">
                <div class="ff-route-skeleton__panel" style="min-height: 520px;"></div>
                <div style="display:grid; gap:22px;">
                    <div class="ff-route-skeleton__panel" style="min-height: 248px;"></div>
                    <div class="ff-route-skeleton__panel" style="min-height: 248px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ff-route-skeleton" data-ff-route-skeleton="schedule" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__layout-two">
                <div class="ff-route-skeleton__panel" style="min-height: 220px;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(300px, 48%);"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(620px, 88%);"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(560px, 78%);"></div>
                    <div class="ff-route-skeleton__pills">
                        <div class="ff-route-skeleton__pill"></div>
                        <div class="ff-route-skeleton__pill"></div>
                        <div class="ff-route-skeleton__pill"></div>
                    </div>
                </div>
                <div style="display:grid; gap:12px;">
                    <div class="ff-route-skeleton__panel" style="min-height: 100px;"></div>
                    <div class="ff-route-skeleton__panel" style="min-height: 100px;"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__layout-two" style="grid-template-columns:minmax(0, 1.7fr) minmax(300px, 0.95fr);">
                <div class="ff-route-skeleton__calendar"></div>
                <div class="ff-route-skeleton__agenda"></div>
            </div>
        </div>
    </div>
</div>

<div class="ff-route-skeleton" data-ff-route-skeleton="station" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__panel" style="min-height: 260px;"></div>
            <div class="ff-route-skeleton__layout-two">
                <div class="ff-route-skeleton__panel" style="min-height: 250px;"></div>
                <div class="ff-route-skeleton__panel" style="min-height: 250px;"></div>
            </div>
            <div class="ff-route-skeleton__panel" style="min-height: 380px;"></div>
        </div>
    </div>
</div>

<div class="ff-route-skeleton" data-ff-route-skeleton="risk-reviews" aria-hidden="true">
    <div class="ff-route-skeleton__surface">
        <div class="ff-route-skeleton__content">
            <div class="ff-route-skeleton__topbar">
                <div style="display:grid; gap:10px; min-width:0;">
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle"></div>
                    <div class="ff-route-skeleton__line ff-route-skeleton__line--meta"></div>
                </div>
                <div class="ff-route-skeleton__topbar-actions">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__summary">
                <div class="ff-route-skeleton__line ff-route-skeleton__line--title"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(600px, 90%);"></div>
                <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(520px, 82%);"></div>
                <div class="ff-route-skeleton__pills">
                    <div class="ff-route-skeleton__pill"></div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
            </div>
            <div class="ff-route-skeleton__stats" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
                <div class="ff-route-skeleton__stat"></div>
            </div>
            <div class="ff-route-skeleton__list-card">
                <div class="ff-route-skeleton__list-header">
                    <div style="display:grid; gap:10px; min-width:0; width:100%;">
                        <div class="ff-route-skeleton__line ff-route-skeleton__line--title" style="width:min(280px, 52%);"></div>
                        <div class="ff-route-skeleton__line ff-route-skeleton__line--subtitle" style="width:min(460px, 84%);"></div>
                    </div>
                    <div class="ff-route-skeleton__pill"></div>
                </div>
                <div class="ff-route-skeleton__toolbar-card"></div>
                <div class="ff-route-skeleton__list-items">
                    <div class="ff-route-skeleton__list-item"></div>
                    <div class="ff-route-skeleton__list-item"></div>
                    <div class="ff-route-skeleton__list-item"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="{{ $backdropId }}" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dashboardFab = document.querySelector('[data-ff-dashboard-fab]');
    const notificationsPanel = document.querySelector('[data-ff-notifications-panel]');
    const settingsPanel = document.querySelector('[data-ff-settings-panel]');
    const panelCloseButtons = document.querySelectorAll('[data-ff-close-panel]');
    const notificationsOpenButton = document.querySelector('[data-ff-open-notifications]');
    const settingsOpenButton = document.querySelector('[data-ff-open-settings]');
    const openLogsButton = document.querySelector('[data-ff-open-logs]');
    const openInsightsButton = document.querySelector('[data-ff-open-insights]');
    const mainContent = document.querySelector('.main-content');
    const routeSkeletons = {
        incidents: document.querySelector('[data-ff-route-skeleton="incidents"]'),
        announcements: document.querySelector('[data-ff-route-skeleton="announcements"]'),
        profile: document.querySelector('[data-ff-route-skeleton="profile"]'),
        schedule: document.querySelector('[data-ff-route-skeleton="schedule"]'),
        station: document.querySelector('[data-ff-route-skeleton="station"]'),
        'risk-reviews': document.querySelector('[data-ff-route-skeleton="risk-reviews"]')
    };
    const partialRouteKeys = new Set(['incidents', 'announcements', 'profile', 'schedule', 'station', 'risk-reviews']);
    const prefetchedRoutes = new Set();
    const prefetchedPages = new Map();
    const pendingPrefetchTimers = new Map();
    const loadedExternalScripts = new Set(
        Array.from(document.querySelectorAll('script[src]')).map(function (script) {
            try {
                return new URL(script.src, window.location.href).href;
            } catch (error) {
                return script.src;
            }
        })
    );
    const initialPageRoot = document.querySelector('[data-ff-page-root]');

    if (initialPageRoot && !window.history.state?.firefighterPartial) {
        window.history.replaceState({
            firefighterPartial: true,
            routeKey: initialPageRoot.dataset.ffPageRoot || '',
            href: window.location.href
        }, '', window.location.href);
    }

    function hideAllRouteSkeletons() {
        Object.values(routeSkeletons).forEach(function (panel) {
            if (!panel) return;
            panel.classList.remove('is-active');
            panel.setAttribute('aria-hidden', 'true');
        });
    }

    function resetTransientUi() {
        hideAllRouteSkeletons();
        closePanels();
        document.body.classList.remove('menu-open');

        document.getElementById(@json($sidebarId))?.classList.remove('open', 'active');
        document.getElementById(@json($backdropId))?.classList.add('hidden');
        document.getElementById('ffUserDropdownMenu')?.classList.remove('show');
        document.getElementById('userDropdownMenu')?.classList.remove('show');

        const logsModal = document.getElementById('firefighterMyLogsModal');
        if (logsModal) {
            logsModal.classList.add('hidden');
            logsModal.classList.remove('flex');
        }

        const logoutModal = document.getElementById('firefighterLogoutConfirmModal');
        if (logoutModal) {
            logoutModal.classList.remove('active');
            logoutModal.setAttribute('aria-hidden', 'true');
        }
    }

    function syncPendingNavState(nextKey) {
        if (!nextKey) {
            return;
        }

        document.querySelectorAll('[data-ff-nav-key]').forEach(function (item) {
            item.classList.toggle('active', item.dataset.ffNavKey === nextKey);
        });
    }

    function showRouteSkeleton(routeKey, navKey) {
        syncPendingNavState(navKey || routeKey);
        hideAllRouteSkeletons();
    }

    function isEligibleNavigationLink(link) {
        if (!link || !link.href || link.target === '_blank' || link.hasAttribute('download')) {
            return false;
        }

        if (link.dataset.ffNoLoader === 'true') {
            return false;
        }

        let url;

        try {
            url = new URL(link.href, window.location.href);
        } catch (error) {
            return false;
        }

        if (url.origin !== window.location.origin) {
            return false;
        }

        if (!/^https?:$/.test(url.protocol) || url.hash || link.getAttribute('href') === '#') {
            return false;
        }

        return url.pathname.startsWith('/firefighter');
    }

    function prefetchFirefighterRoute(link) {
        if (!isEligibleNavigationLink(link)) {
            return;
        }

        const href = link.href;
        const routeKey = link.dataset.ffNavKey || '';

        if (prefetchedRoutes.has(href)) {
            return;
        }

        if (partialRouteKeys.has(routeKey)) {
            prefetchedPages.set(href, fetch(href, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to preload route content.');
                }

                return response.text();
            }).catch(function () {
                prefetchedPages.delete(href);
                return null;
            }));
        } else {
            const prefetch = document.createElement('link');
            prefetch.rel = 'prefetch';
            prefetch.href = href;
            prefetch.as = 'document';
            document.head.appendChild(prefetch);
        }

        prefetchedRoutes.add(href);
    }

    function canSmartPrefetch() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const effectiveType = String(connection?.effectiveType || '').toLowerCase();

        if (connection?.saveData) {
            return false;
        }

        return !['slow-2g', '2g'].includes(effectiveType);
    }

    function queueFirefighterRoutePrefetch(link, delayMs = 180) {
        if (!link || !canSmartPrefetch()) {
            return;
        }

        const href = link.href;

        if (pendingPrefetchTimers.has(href)) {
            window.clearTimeout(pendingPrefetchTimers.get(href));
        }

        const timerId = window.setTimeout(function () {
            pendingPrefetchTimers.delete(href);
            prefetchFirefighterRoute(link);
        }, delayMs);

        pendingPrefetchTimers.set(href, timerId);
    }

    function clearQueuedPrefetch(link) {
        if (!link?.href) {
            return;
        }

        const timerId = pendingPrefetchTimers.get(link.href);
        if (timerId) {
            window.clearTimeout(timerId);
            pendingPrefetchTimers.delete(link.href);
        }
    }

    function scheduleDashboardRoutePrefetch() {
        const pageIsVisible = typeof window.isPageVisible === 'function'
            ? window.isPageVisible()
            : document.visibilityState !== 'hidden';

        if (!canSmartPrefetch() || !pageIsVisible) {
            return;
        }

        const currentPageRoot = document.querySelector('[data-ff-page-root]');
        const routeKey = currentPageRoot?.dataset?.ffPageRoot || '';

        if (routeKey !== 'dashboard') {
            return;
        }

        if (window.__ffAllowDashboardPrefetch !== true) {
            return;
        }

        const runPrefetch = function () {
            const stillVisible = typeof window.isPageVisible === 'function'
                ? window.isPageVisible()
                : document.visibilityState !== 'hidden';

            if (!stillVisible) {
                return;
            }

            ['incidents', 'risk-reviews', 'announcements', 'schedule', 'profile'].forEach(function (key, index) {
                const link = document.querySelector(`a[data-ff-nav-link][data-ff-nav-key="${key}"]`);
                if (!link) {
                    return;
                }

                window.setTimeout(function () {
                    prefetchFirefighterRoute(link);
                }, index * 220);
            });
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(runPrefetch, { timeout: 2500 });
            return;
        }

        window.setTimeout(runPrefetch, 1800);
    }

    function syncPageHead(documentFragment, routeKey) {
        const title = documentFragment.querySelector('title');
        if (title) {
            document.title = title.textContent || document.title;
        }

        document.querySelectorAll('style[data-ff-page-style], style[data-ff-dynamic-page-style]').forEach(function (styleTag) {
            styleTag.remove();
        });

        documentFragment.querySelectorAll('style[data-ff-page-style]').forEach(function (styleTag) {
            const clone = styleTag.cloneNode(true);
            clone.setAttribute('data-ff-dynamic-page-style', routeKey);
            document.head.appendChild(clone);
        });

        documentFragment.querySelectorAll('link[rel="stylesheet"][href]').forEach(function (linkTag) {
            const href = linkTag.href;

            if (!href || document.head.querySelector(`link[rel="stylesheet"][href="${href}"]`)) {
                return;
            }

            const clone = document.createElement('link');
            clone.rel = 'stylesheet';
            clone.href = href;
            clone.setAttribute('data-ff-dynamic-page-link', routeKey);
            document.head.appendChild(clone);
        });
    }

    function runQueuedDOMContentLoadedCallbacks(callbacks) {
        callbacks.forEach(function (listener) {
            try {
                listener.call(document, new Event('DOMContentLoaded'));
            } catch (error) {
                console.error('Error running partial page DOMContentLoaded callback:', error);
            }
        });
    }

    function executePartialPageScripts(container, routeKey) {
        const scripts = Array.from(container.querySelectorAll('script'));
        const originalAddEventListener = document.addEventListener.bind(document);
        const readyCallbacks = [];

        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                readyCallbacks.push(listener);
                return;
            }

            return originalAddEventListener(type, listener, options);
        };

        let deferredAnnouncementInit = false;
        let deferredStationInit = false;

        scripts.forEach(function (scriptTag) {
            const replacement = document.createElement('script');

            Array.from(scriptTag.attributes).forEach(function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });

            if (scriptTag.src) {
                const resolvedSrc = scriptTag.src;

                if (loadedExternalScripts.has(resolvedSrc)) {
                    scriptTag.remove();
                    deferredAnnouncementInit = deferredAnnouncementInit || routeKey === 'announcements';
                    deferredStationInit = deferredStationInit || routeKey === 'station';
                    return;
                }

                replacement.src = resolvedSrc;
                loadedExternalScripts.add(resolvedSrc);

                if (routeKey === 'announcements') {
                    replacement.addEventListener('load', function () {
                        window.initializeAnnouncementSystem?.();
                    }, { once: true });
                }

                if (routeKey === 'station') {
                    replacement.addEventListener('load', function () {
                        window.initFirefighterMyStationPage?.();
                    }, { once: true });
                }
            } else {
                replacement.textContent = scriptTag.textContent;
            }

            scriptTag.replaceWith(replacement);
        });

        document.addEventListener = originalAddEventListener;
        runQueuedDOMContentLoadedCallbacks(readyCallbacks);

        if (deferredAnnouncementInit && typeof window.initializeAnnouncementSystem === 'function') {
            window.initializeAnnouncementSystem();
        }

        if (deferredStationInit && typeof window.initFirefighterMyStationPage === 'function') {
            window.initFirefighterMyStationPage();
        }
    }

    async function navigateFirefighterPartial(link, routeKey, options = {}) {
        const href = link.href;
        const activeRouteKey = routeKey || link.dataset.ffNavKey || '';
        const shouldPushHistory = options.pushHistory !== false;

        showRouteSkeleton(activeRouteKey, activeRouteKey);
        closePanels();
        document.body.classList.remove('menu-open');
        document.getElementById(@json($sidebarId))?.classList.remove('open', 'active');
        document.getElementById(@json($backdropId))?.classList.add('hidden');
        document.getElementById('ffUserDropdownMenu')?.classList.remove('show');
        document.getElementById('userDropdownMenu')?.classList.remove('show');

        try {
            const html = prefetchedPages.has(href)
                ? await prefetchedPages.get(href)
                : await fetch(href, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to open that page right now.');
                    }

                    return response.text();
                });

            if (!html) {
                window.location.href = href;
                return;
            }

            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const incomingRoot = parsed.querySelector(`[data-ff-page-root="${activeRouteKey}"]`);
            const currentRoot = document.querySelector('[data-ff-page-root]');

            if (!incomingRoot || !currentRoot) {
                window.location.href = href;
                return;
            }

            if (typeof window.__ffCurrentPageCleanup === 'function') {
                try {
                    window.__ffCurrentPageCleanup();
                } catch (error) {
                    console.error('Firefighter page cleanup failed:', error);
                }
            }

            if (window.announcementSystem?.destroy) {
                window.announcementSystem.destroy();
            }

            window.__ffCurrentPageCleanup = null;
            syncPageHead(parsed, activeRouteKey);

            const replacementRoot = document.importNode(incomingRoot, true);
            currentRoot.replaceWith(replacementRoot);
            executePartialPageScripts(replacementRoot, activeRouteKey);
            scheduleDashboardRoutePrefetch();

            if (shouldPushHistory) {
                window.history.pushState({
                    firefighterPartial: true,
                    routeKey: activeRouteKey,
                    href: href
                }, '', href);
            }

            window.scrollTo({ top: 0, behavior: 'auto' });
        } catch (error) {
            console.error('Firefighter partial navigation failed:', error);
            window.location.href = href;
            return;
        } finally {
            resetTransientUi();
        }
    }

    function openPanel(panel) {
        if (!panel) return;
        [notificationsPanel, settingsPanel].forEach(function (target) {
            if (target) {
                if (target !== panel && target.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                }
                target.classList.toggle('is-open', target === panel);
                target.setAttribute('aria-hidden', target === panel ? 'false' : 'true');
            }
        });
    }

    function closePanels() {
        [notificationsPanel, settingsPanel].forEach(function (panel) {
            if (panel) {
                if (panel.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                }
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function goToFirefighterDashboard() {
        const dashboardHref = @json(route('firefighter.dashboard'));
        const dashboardPath = new URL(dashboardHref, window.location.origin).pathname;

        if (window.location.pathname === dashboardPath) {
            closePanels();

            if (typeof window.closeFirefighterInsightsPanel === 'function') {
                window.closeFirefighterInsightsPanel();
            }

            if (typeof window.refreshFirefighterDashboardMap === 'function') {
                window.refreshFirefighterDashboardMap();
            }

            return;
        }

        window.location.href = dashboardHref;
    }

    resetTransientUi();

    if (dashboardFab) {
        dashboardFab.addEventListener('click', function () {
            goToFirefighterDashboard();
        });
    }

    notificationsOpenButton?.addEventListener('click', function () {
        openPanel(notificationsPanel);
    });

    document.querySelectorAll('[data-aar-notification-id]').forEach(function (link) {
        link.addEventListener('click', function () {
            const endpoint = @json(route('firefighter.notifications.read', ['notificationId' => '__NOTIFICATION_ID__']))
                .replace('__NOTIFICATION_ID__', encodeURIComponent(link.dataset.aarNotificationId || ''));
            fetch(endpoint, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            }).catch(function () {});
        });
    });

    settingsOpenButton?.addEventListener('click', function () {
        openPanel(settingsPanel);
    });

    openLogsButton?.addEventListener('click', function () {
        closePanels();
        if (typeof openMyLogsModal === 'function') {
            openMyLogsModal();
        }
    });

    openInsightsButton?.addEventListener('click', function () {
        if (typeof window.openFirefighterInsightsPanel === 'function') {
            window.openFirefighterInsightsPanel();
        }
    });

    panelCloseButtons.forEach(function (button) {
        button.addEventListener('click', closePanels);
    });

    document.querySelectorAll('a[data-ff-nav-link]').forEach(function (link) {
        link.addEventListener('pointerenter', function () {
            queueFirefighterRoutePrefetch(link);
        }, { passive: true });

        link.addEventListener('pointerleave', function () {
            clearQueuedPrefetch(link);
        }, { passive: true });
    });

    document.addEventListener('click', function (event) {
        if (event.__ffMapLayerControlHandled) {
            return;
        }

        const link = event.target.closest ? event.target.closest('a[href]') : null;

        if (!isEligibleNavigationLink(link)) {
            return;
        }

        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const routeKey = link.dataset.ffNavKey || '';

        if (routeSkeletons[routeKey]) {
            event.preventDefault();
            navigateFirefighterPartial(link, routeKey);
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanels();
        }
    });

    window.addEventListener('popstate', function (event) {
        const state = event.state || {};

        if (!state.firefighterPartial || !state.href) {
            window.location.href = window.location.href;
            return;
        }

        if (!routeSkeletons[state.routeKey || '']) {
            window.location.href = state.href;
            return;
        }

        const link = document.createElement('a');
        link.href = state.href;
        link.dataset.ffNavKey = state.routeKey || '';
        navigateFirefighterPartial(link, state.routeKey || '', { pushHistory: false });
    });

    window.addEventListener('pageshow', resetTransientUi);
    window.addEventListener('load', resetTransientUi);
    scheduleDashboardRoutePrefetch();
});
</script>

@include('firefighters.partials.icon-fallback')
