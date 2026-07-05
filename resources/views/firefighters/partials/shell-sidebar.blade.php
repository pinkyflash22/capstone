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

    .ff-route-skeleton {
        position: fixed;
        z-index: 10040;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.14s ease, visibility 0.14s ease;
    }

    .ff-route-skeleton.is-active {
        opacity: 1;
        visibility: visible;
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
            bottom: 86px;
            transform: translateX(-50%);
            width: min(calc(100vw - 28px), 308px);
            min-height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
            pointer-events: none;
            z-index: 6;
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.22s ease;
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock__burst-item {
            position: relative;
            left: auto;
            bottom: auto;
            width: 48px;
            height: 48px;
            min-width: 48px;
            min-height: 48px;
            margin-left: 0;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.85);
            background: rgba(255, 255, 255, 0.96);
            color: #14532d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.14);
            opacity: 0;
            transform: translateY(8px) scale(0.92);
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
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .mobile-bottom-nav.mobile-bottom-nav--firefighter-app .ff-mobile-dock.is-burst-open .ff-mobile-dock__burst-item {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0) scale(1);
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
            <div class="ff-shell-brand-icon w-20 h-14 bg-white rounded-2xl flex items-center justify-center border border-white/60 shadow-sm px-2 py-1">
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
            <a href="{{ route('firefighter.incidents') }}" class="ff-mobile-dock__burst-item {{ $currentActive === 'incidents' ? 'active' : '' }}" aria-label="Incidents" data-ff-nav-link data-ff-nav-key="incidents">
                <span class="ff-mobile-icon" aria-hidden="true">local_fire_department</span>
            </a>
            <a href="{{ route('firefighter.my-station') }}" class="ff-mobile-dock__burst-item {{ $currentActive === 'station' ? 'active' : '' }}" aria-label="My Station" data-ff-nav-link data-ff-nav-key="station">
                <span class="ff-mobile-icon" aria-hidden="true">apartment</span>
            </a>
            <a href="{{ route('firefighter.equipments') }}" class="ff-mobile-dock__burst-item {{ in_array($currentActive, ['equipment', 'fsic'], true) ? 'active' : '' }}" aria-label="Requests" data-ff-nav-link data-ff-nav-key="equipment">
                <span class="ff-mobile-icon" aria-hidden="true">assignment</span>
            </a>
            @if ($currentActive === 'dashboard')
                <button type="button" class="ff-mobile-dock__burst-item" data-ff-open-insights aria-label="Charts and Cards">
                    <span class="ff-mobile-icon" aria-hidden="true">stacked_bar_chart</span>
                </button>
            @endif
        </div>
        <div class="ff-mobile-dock__rail">
            <a href="{{ route('firefighter.schedule') }}" class="ff-mobile-dock__item {{ $currentActive === 'schedule' ? 'active' : '' }}" aria-label="Schedule" data-ff-nav-link data-ff-nav-key="schedule">
                <span class="ff-mobile-icon" aria-hidden="true">calendar_month</span>
            </a>
            <button type="button" class="ff-mobile-dock__item" data-ff-open-notifications aria-label="Notifications">
                <span class="ff-mobile-icon" aria-hidden="true">notifications</span>
            </button>
            <button type="button" class="ff-mobile-dock__center {{ $mobileDockIsOpsActive ? 'active' : '' }}" data-ff-dashboard-fab aria-label="Dashboard">
                <span class="ff-mobile-icon" aria-hidden="true">dashboard</span>
            </button>
            <a href="{{ route('firefighter.announcements') }}" class="ff-mobile-dock__item {{ $currentActive === 'announcements' ? 'active' : '' }}" aria-label="Announcements" data-ff-nav-link data-ff-nav-key="announcements">
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
            <a href="{{ route('firefighter.incidents') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="incidents">
                <span class="ff-mobile-panel__action-icon"><span class="ff-mobile-icon" aria-hidden="true">local_fire_department</span></span>
                <span class="ff-mobile-panel__copy">
                    <strong>Active Incidents</strong>
                    <small>Check incident updates and field assignments.</small>
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
            <a href="{{ route('firefighter.my-profile') }}" class="ff-mobile-panel__action" data-ff-nav-link data-ff-nav-key="profile">
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
    const mainContent = document.querySelector('.main-content');
    const routeSkeletons = {
        incidents: document.querySelector('[data-ff-route-skeleton="incidents"]'),
        announcements: document.querySelector('[data-ff-route-skeleton="announcements"]'),
        profile: document.querySelector('[data-ff-route-skeleton="profile"]'),
        schedule: document.querySelector('[data-ff-route-skeleton="schedule"]')
    };
    const partialRouteKeys = new Set(['incidents', 'announcements', 'profile', 'schedule']);
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
    let holdTimer = null;
    let holdTriggered = false;
    let touchTapPending = false;
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

    function syncPendingNavState(nextKey) {
        if (!nextKey) {
            return;
        }

        document.querySelectorAll('[data-ff-nav-key]').forEach(function (item) {
            item.classList.toggle('active', item.dataset.ffNavKey === nextKey);
        });
    }

    function showRouteSkeleton(routeKey, navKey) {
        const skeleton = routeSkeletons[routeKey];
        const currentMainContent = document.querySelector('.main-content');

        if (!skeleton || !currentMainContent) {
            return;
        }

        syncPendingNavState(navKey || routeKey);
        hideAllRouteSkeletons();

        const rect = currentMainContent.getBoundingClientRect();
        const top = Math.max(rect.top, 0);
        const left = Math.max(rect.left, 0);
        const width = Math.max(rect.width, 280);
        const height = Math.max(window.innerHeight - top - 12, 420);

        skeleton.style.top = `${top}px`;
        skeleton.style.left = `${left}px`;
        skeleton.style.width = `${width}px`;
        skeleton.style.height = `${height}px`;
        skeleton.classList.add('is-active');
        skeleton.setAttribute('aria-hidden', 'false');
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
        if (!canSmartPrefetch() || !isPageVisible()) {
            return;
        }

        const currentPageRoot = document.querySelector('[data-ff-page-root]');
        const routeKey = currentPageRoot?.dataset?.ffPageRoot || '';

        if (routeKey !== 'dashboard') {
            return;
        }

        const runPrefetch = function () {
            if (!isPageVisible()) {
                return;
            }

            ['incidents', 'announcements', 'schedule', 'profile'].forEach(function (key, index) {
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
                    return;
                }

                replacement.src = resolvedSrc;
                loadedExternalScripts.add(resolvedSrc);

                if (routeKey === 'announcements') {
                    replacement.addEventListener('load', function () {
                        window.initializeAnnouncementSystem?.();
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
    }

    async function navigateFirefighterPartial(link, routeKey, options = {}) {
        const href = link.href;
        const activeRouteKey = routeKey || link.dataset.ffNavKey || '';
        const shouldPushHistory = options.pushHistory !== false;

        showRouteSkeleton(activeRouteKey, activeRouteKey);
        closePanels();
        closeBurstMenu();

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
            hideAllRouteSkeletons();
        }
    }

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

    document.querySelectorAll('a[data-ff-nav-link]').forEach(function (link) {
        link.addEventListener('pointerenter', function () {
            queueFirefighterRoutePrefetch(link);
        }, { passive: true });

        link.addEventListener('pointerleave', function () {
            clearQueuedPrefetch(link);
        }, { passive: true });
    });

    document.addEventListener('click', function (event) {
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

    window.addEventListener('pageshow', hideAllRouteSkeletons);
    window.addEventListener('load', hideAllRouteSkeletons);
    scheduleDashboardRoutePrefetch();
});
</script>

@include('firefighters.partials.icon-fallback')
