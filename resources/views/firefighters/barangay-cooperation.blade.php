<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · Barangay Cooperation</title>

    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @include('partials.pwa-head')

    <style data-ff-page-style="cooperation">
        body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%); }
        [data-ff-page-root="cooperation"] .main-content { min-height: 100vh; padding: 20px 24px 36px; }
        .coop-shell { display: grid; gap: 16px; width: min(1420px, 100%); margin: 0 auto; }
        .coop-hero { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; overflow: hidden; padding: 26px 28px; border: 1px solid rgba(198,61,38,.12); border-radius: 26px; background: linear-gradient(135deg, #fff4ec 0%, #fffaf5 58%, #fff 100%); box-shadow: 0 18px 40px rgba(15,23,42,.06); }
        .coop-hero::after { content: ''; position: absolute; width: 240px; height: 240px; right: -84px; top: -116px; border-radius: 50%; background: radial-gradient(circle, rgba(209,138,22,.2), rgba(209,138,22,0)); pointer-events: none; }
        .coop-kicker { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 8px; color: #b45309; font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .coop-hero h1 { margin: 0; color: #172033; font-size: clamp(1.45rem, 2.6vw, 2.25rem); line-height: 1.08; font-weight: 900; letter-spacing: -.035em; }
        .coop-hero p { max-width: 720px; margin: 9px 0 0; color: #64748b; font-size: .9rem; line-height: 1.55; }
        .coop-station-pill { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 10px; flex: 0 0 auto; max-width: 320px; padding: 11px 14px; border: 1px solid rgba(198,61,38,.12); border-radius: 16px; background: rgba(255,255,255,.88); color: #334155; box-shadow: 0 12px 26px rgba(15,23,42,.06); }
        .coop-station-pill i { color: #c63d26; }
        .coop-station-pill span { min-width: 0; font-size: .78rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .coop-summary { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
        .coop-stat { display: flex; align-items: center; gap: 13px; min-width: 0; padding: 15px 16px; border: 1px solid rgba(226,232,240,.92); border-radius: 19px; background: rgba(255,255,255,.94); box-shadow: 0 12px 28px rgba(15,23,42,.045); }
        .coop-stat__icon { display: grid; width: 40px; height: 40px; flex: 0 0 40px; place-items: center; border-radius: 13px; color: #c2410c; background: #fff7ed; }
        .coop-stat small, .coop-stat strong { display: block; }
        .coop-stat small { color: #94a3b8; font-size: .62rem; font-weight: 900; letter-spacing: .075em; text-transform: uppercase; }
        .coop-stat strong { margin-top: 2px; color: #172033; font-size: 1.32rem; line-height: 1; font-weight: 900; }

        .coop-toolbar { display: grid; grid-template-columns: minmax(240px,1fr) minmax(180px,.36fr); gap: 10px; padding: 12px; border: 1px solid #e8edf3; border-radius: 20px; background: rgba(255,255,255,.94); }
        .coop-search { position: relative; }
        .coop-search i { position: absolute; top: 50%; left: 13px; color: #94a3b8; transform: translateY(-50%); }
        .coop-search input, .coop-toolbar select { width: 100%; height: 42px; border: 1px solid #dbe3ec; border-radius: 12px; background: #f8fafc; color: #334155; font-size: .8rem; outline: none; }
        .coop-search input { padding: 0 13px 0 38px; }
        .coop-toolbar select { padding: 0 34px 0 12px; }
        .coop-search input:focus, .coop-toolbar select:focus { border-color: rgba(198,61,38,.42); box-shadow: 0 0 0 3px rgba(198,61,38,.08); }

        .coop-coverage { display: flex; gap: 8px; overflow-x: auto; padding: 1px 1px 5px; scrollbar-width: thin; }
        .coop-coverage button { display: inline-flex; align-items: center; gap: 8px; min-height: 38px; flex: 0 0 auto; padding: 0 13px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; color: #64748b; font-size: .72rem; font-weight: 800; cursor: pointer; }
        .coop-coverage button.active { border-color: transparent; background: linear-gradient(135deg, #c63d26, #d18a16); color: #fff; box-shadow: 0 8px 18px rgba(198,61,38,.18); }
        .coop-coverage button span { opacity: .78; font-size: .6rem; text-transform: uppercase; }

        .coop-workspace { display: grid; grid-template-columns: minmax(0,1.45fr) minmax(330px,.72fr); gap: 16px; align-items: start; }
        .coop-panel { min-width: 0; border: 1px solid rgba(226,232,240,.95); border-radius: 23px; background: rgba(255,255,255,.96); box-shadow: 0 16px 34px rgba(15,23,42,.05); }
        .coop-panel__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 19px 13px; border-bottom: 1px solid #f1f5f9; }
        .coop-panel__head h2 { margin: 0; color: #172033; font-size: 1rem; font-weight: 900; }
        .coop-panel__head p { margin: 4px 0 0; color: #94a3b8; font-size: .72rem; line-height: 1.45; }
        .coop-count { display: inline-flex; min-width: 30px; height: 28px; align-items: center; justify-content: center; padding: 0 9px; border-radius: 9px; background: #fff7ed; color: #b45309; font-size: .7rem; font-weight: 900; }
        .coop-list { display: grid; gap: 10px; padding: 13px; }
        .coop-card { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 14px; padding: 15px; border: 1px solid #e8edf3; border-radius: 17px; background: #fff; transition: border-color .16s ease, transform .16s ease, box-shadow .16s ease; }
        .coop-card:hover { border-color: rgba(198,61,38,.24); transform: translateY(-1px); box-shadow: 0 12px 26px rgba(15,23,42,.055); }
        .coop-card[hidden] { display: none; }
        .coop-card__top { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 7px; }
        .coop-chip { display: inline-flex; align-items: center; min-height: 23px; padding: 0 8px; border-radius: 999px; background: #f1f5f9; color: #64748b; font-size: .58rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .coop-chip.priority-critical, .coop-chip.priority-high { background: #fef2f2; color: #b91c1c; }
        .coop-chip.priority-medium { background: #fff7ed; color: #b45309; }
        .coop-chip.status-submitted, .coop-chip.status-completed { background: #ecfdf5; color: #047857; }
        .coop-chip.status-in_progress { background: #eff6ff; color: #1d4ed8; }
        .coop-card h3 { margin: 0; color: #1e293b; font-size: .88rem; line-height: 1.35; font-weight: 850; }
        .coop-card__meta { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 8px; color: #64748b; font-size: .68rem; }
        .coop-card__meta span { display: inline-flex; align-items: center; gap: 5px; }
        .coop-progress { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 8px; align-items: center; margin-top: 11px; }
        .coop-progress__track { height: 7px; overflow: hidden; border-radius: 999px; background: #edf2f7; }
        .coop-progress__track span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #c63d26, #d18a16); }
        .coop-progress strong { color: #475569; font-size: .65rem; }
        .coop-open { align-self: center; display: grid; width: 36px; height: 36px; place-items: center; border: 1px solid rgba(198,61,38,.18); border-radius: 11px; background: #fff7ed; color: #b45309; cursor: pointer; }
        .coop-empty { padding: 34px 20px; text-align: center; color: #94a3b8; }
        .coop-empty i { display: block; margin-bottom: 10px; color: #cbd5e1; font-size: 1.6rem; }
        .coop-empty strong { display: block; color: #64748b; font-size: .82rem; }
        .coop-empty span { display: block; margin-top: 4px; font-size: .7rem; }

        .coop-modal[hidden] { display: none; }
        .coop-modal { position: fixed; inset: 0; z-index: var(--ff-layer-modal,7000); display: grid; place-items: center; padding: 16px; background: rgba(15,23,42,.54); backdrop-filter: blur(7px); }
        .coop-modal__card { display: flex; flex-direction: column; width: min(700px,100%); max-height: min(86dvh,760px); overflow: hidden; border: 1px solid rgba(255,255,255,.7); border-radius: 22px; background: #fff; box-shadow: 0 30px 80px rgba(15,23,42,.26); }
        .coop-modal__head { display: flex; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid #edf2f7; }
        .coop-modal__eyebrow { margin: 0 0 3px; color: #b45309; font-size: .6rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .coop-modal__head h2 { margin: 0; color: #172033; font-size: 1rem; font-weight: 900; }
        .coop-modal__close { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #64748b; }
        .coop-modal__body { min-height: 0; overflow-y: auto; padding: 16px 18px 20px; }
        .coop-detail-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 9px; }
        .coop-detail { padding: 10px 11px; border: 1px solid #edf2f7; border-radius: 12px; background: #f8fafc; }
        .coop-detail.wide { grid-column: 1/-1; }
        .coop-detail span, .coop-detail strong { display: block; }
        .coop-detail span { margin-bottom: 4px; color: #94a3b8; font-size: .58rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
        .coop-detail strong { color: #334155; font-size: .75rem; line-height: 1.5; overflow-wrap: anywhere; }
        .coop-steps-title { margin: 18px 0 9px; color: #172033; font-size: .82rem; font-weight: 900; }
        .coop-step { display: grid; gap: 9px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 13px; background: #fff; }
        .coop-step + .coop-step { margin-top: 9px; }
        .coop-step__title { display: flex; align-items: flex-start; gap: 9px; color: #334155; font-size: .75rem; line-height: 1.45; font-weight: 800; }
        .coop-step__number { display: grid; width: 23px; height: 23px; flex: 0 0 23px; place-items: center; border-radius: 8px; background: #fff7ed; color: #b45309; font-size: .62rem; }
        .coop-step__controls { display: grid; grid-template-columns: minmax(120px,.65fr) minmax(180px,1.35fr) auto; gap: 7px; }
        .coop-step select, .coop-step input { min-width: 0; height: 37px; padding: 0 10px; border: 1px solid #dbe3ec; border-radius: 9px; background: #f8fafc; color: #334155; font-size: .7rem; }
        .coop-step button { min-width: 76px; height: 37px; padding: 0 12px; border: 0; border-radius: 9px; background: linear-gradient(135deg,#c63d26,#d18a16); color: #fff; font-size: .68rem; font-weight: 900; }
        .coop-step button:disabled { opacity: .6; cursor: wait; }
        .coop-step__review { color: #94a3b8; font-size: .62rem; line-height: 1.4; }
        .coop-proof-picker { display: grid; gap: 6px; padding: 9px; border: 1px dashed #cbd5e1; border-radius: 10px; background: #f8fafc; }
        .coop-proof-picker span { color: #475569; font-size: .66rem; font-weight: 850; }
        .coop-proof-picker input { width: 100%; height: auto; padding: 6px; background: #fff; font-size: .65rem; }
        .coop-proof-picker small { color: #94a3b8; font-size: .58rem; line-height: 1.4; }
        .coop-proof-preview { display: grid; grid-template-columns: repeat(auto-fill,minmax(72px,1fr)); gap: 6px; }
        .coop-proof-preview:empty { display: none; }
        .coop-proof-preview figure { min-width: 0; margin: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; }
        .coop-proof-preview img { display: block; width: 100%; height: 58px; object-fit: cover; }
        .coop-proof-preview figcaption { overflow: hidden; padding: 4px 5px; color: #64748b; font-size: .52rem; text-overflow: ellipsis; white-space: nowrap; }
        .coop-timeline-title { margin: 20px 0 9px; color: #172033; font-size: .82rem; font-weight: 900; }
        .coop-timeline { position: relative; display: grid; gap: 10px; margin-left: 7px; padding-left: 18px; border-left: 2px solid #f1f5f9; }
        .coop-timeline__item { position: relative; padding: 11px 12px; border: 1px solid #edf2f7; border-radius: 12px; background: #f8fafc; }
        .coop-timeline__item::before { content: ''; position: absolute; left: -24px; top: 15px; width: 10px; height: 10px; border: 2px solid #fff; border-radius: 50%; background: #d18a16; box-shadow: 0 0 0 2px #fed7aa; }
        .coop-timeline__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .coop-timeline__head strong { color: #334155; font-size: .7rem; }
        .coop-timeline__head time { color: #94a3b8; font-size: .56rem; white-space: nowrap; }
        .coop-timeline__item p { margin: 5px 0 0; color: #64748b; font-size: .64rem; line-height: 1.45; }
        .coop-signoff { display: inline-flex; align-items: center; gap: 5px; margin-top: 7px; padding: 4px 7px; border-radius: 999px; background: #fff7ed; color: #b45309; font-size: .55rem; font-weight: 900; text-transform: uppercase; }
        .coop-signoff.accepted { background: #ecfdf5; color: #047857; }
        .coop-signoff.needs_follow_up { background: #fef2f2; color: #b91c1c; }
        .coop-visit-photos { display: grid; grid-template-columns: repeat(auto-fill,minmax(76px,1fr)); gap: 6px; margin-top: 8px; }
        .coop-visit-photos a { overflow: hidden; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
        .coop-visit-photos img { display: block; width: 100%; height: 62px; object-fit: cover; }
        .coop-toast { position: fixed; z-index: var(--ff-layer-notice,6000); right: 18px; bottom: 22px; max-width: min(360px,calc(100vw - 32px)); padding: 11px 14px; border-radius: 12px; background: #14532d; color: #fff; font-size: .75rem; font-weight: 800; box-shadow: 0 18px 40px rgba(15,23,42,.2); }
        .coop-toast.error { background: #991b1b; }

        @media (max-width: 1080px) { .coop-workspace { grid-template-columns: 1fr; } .coop-summary { grid-template-columns: repeat(2,minmax(0,1fr)); } }
        @media (max-width: 767px) {
            [data-ff-page-root="cooperation"] .main-content { padding: 14px 12px calc(106px + env(safe-area-inset-bottom,0px)); }
            .coop-shell { gap: 12px; }
            .coop-hero { display: grid; padding: 20px 17px; border-radius: 21px; }
            .coop-hero p { font-size: .8rem; }
            .coop-station-pill { width: 100%; max-width: none; }
            .coop-summary { gap: 8px; }
            .coop-stat { padding: 11px; border-radius: 15px; }
            .coop-stat__icon { width: 34px; height: 34px; flex-basis: 34px; border-radius: 10px; }
            .coop-stat small { font-size: .52rem; }
            .coop-stat strong { font-size: 1.08rem; }
            .coop-toolbar { grid-template-columns: 1fr; padding: 9px; border-radius: 16px; }
            .coop-panel { border-radius: 19px; }
            .coop-panel__head { padding: 15px 14px 11px; }
            .coop-list { padding: 10px; }
            .coop-card { padding: 13px; border-radius: 14px; }
            .coop-card__meta { display: grid; gap: 5px; }
            .coop-modal { align-items: end; padding: 0; }
            .coop-modal__card { width: 100%; max-height: calc(92dvh - env(safe-area-inset-top,0px)); border-radius: 22px 22px 0 0; }
            .coop-modal__body { padding: 13px 14px calc(18px + env(safe-area-inset-bottom,0px)); }
            .coop-detail-grid { grid-template-columns: 1fr; }
            .coop-detail.wide { grid-column: auto; }
            .coop-step__controls { grid-template-columns: 1fr; }
            .coop-step button { width: 100%; }
            .coop-toast { right: 12px; bottom: calc(98px + env(safe-area-inset-bottom,0px)); }
        }
    </style>
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'cooperation'])

    <div data-ff-page-root="cooperation">
        <main class="main-content">
            @include('firefighters.partials.shell-topbar', ['title' => 'Barangay Cooperation', 'subtitle' => 'Station plans, site visits, and checklist verification'])

            <div class="coop-shell" id="barangayCooperationWorkspace">
                <section class="coop-hero">
                    <div>
                        <span class="coop-kicker"><i class="fa-solid fa-people-group"></i> BFP–Barangay Partnership</span>
                        <h1>Preparedness plans for your station coverage</h1>
                        <p>Review BFP-issued mitigation plans for every connected barangay. During coordination visits, verify each checklist step and record concise field notes for the barangay and station command.</p>
                    </div>
                    <div class="coop-station-pill"><i class="fa-solid fa-building-shield"></i><span>{{ $stationOfficer->station?->station_name ?? 'Assigned BFP station' }}</span></div>
                </section>

                <section class="coop-summary" aria-label="Cooperation summary">
                    @foreach([
                        ['Connected Barangays', $summary['barangays'], 'fa-map-location-dot', 'barangays'],
                        ['Active Checklists', $summary['checklists'], 'fa-list-check', 'checklists'],
                        ['Pending Steps', $summary['open_steps'], 'fa-hourglass-half', 'openSteps'],
                        ['Open Mitigations', $summary['open_mitigations'], 'fa-shield-halved', 'mitigations'],
                    ] as [$label, $value, $icon, $key])
                        <article class="coop-stat"><span class="coop-stat__icon"><i class="fa-solid {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong data-summary-value="{{ $key }}">{{ $value }}</strong></div></article>
                    @endforeach
                </section>

                <section class="coop-toolbar" aria-label="Plan filters">
                    <label class="coop-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="coopSearch" placeholder="Search barangay, checklist, or mitigation" autocomplete="off"></label>
                    <select id="coopStatusFilter" aria-label="Filter by status"><option value="all">All statuses</option><option value="pending">Pending</option><option value="in_progress">In progress</option><option value="submitted">Completed checklist</option><option value="planned">Planned mitigation</option><option value="blocked">Blocked</option><option value="completed">Completed mitigation</option></select>
                </section>

                <nav class="coop-coverage" aria-label="Connected barangays">
                    <button type="button" class="active" data-barangay-filter="all"><i class="fa-solid fa-layer-group"></i> All connected <span>{{ $coverage->count() }}</span></button>
                    @foreach($coverage as $link)
                        <button type="button" data-barangay-filter="{{ $link->barangay_id }}"><i class="fa-solid fa-location-dot"></i> {{ $link->barangay?->barangay_name ?? 'Barangay' }} <span>{{ $link->coverage_type }}</span></button>
                    @endforeach
                </nav>

                <div class="coop-workspace">
                    <section class="coop-panel">
                        <header class="coop-panel__head"><div><h2>Barangay action checklists</h2><p>Open a plan to verify steps during a barangay visit.</p></div><span class="coop-count" data-visible-count="checklist">{{ $checklists->count() }}</span></header>
                        <div class="coop-list" data-coop-list="checklist">
                            @forelse($checklists as $checklist)
                                @php $progress = $checklist->checklist_progress; @endphp
                                <article class="coop-card" data-coop-record data-kind="checklist" data-barangay="{{ $checklist->barangay_id }}" data-status="{{ $checklist->status }}" data-search="{{ strtolower(($checklist->barangay?->barangay_name ?? '').' '.$checklist->plan_title.' '.$checklist->plan_description) }}" data-checklist-card="{{ $checklist->getKey() }}">
                                    <div><div class="coop-card__top"><span class="coop-chip">{{ $checklist->barangay?->barangay_name ?? 'Barangay' }}</span><span class="coop-chip priority-{{ $checklist->priority_level }}">{{ $checklist->priority_level }}</span><span class="coop-chip status-{{ $checklist->status }}" data-checklist-status>{{ str_replace('_',' ',$checklist->status) }}</span></div><h3>{{ $checklist->plan_title }}</h3><div class="coop-card__meta"><span><i class="fa-regular fa-calendar"></i>{{ $checklist->target_date?->format('M d, Y') ?? 'No target date' }}</span><span><i class="fa-solid fa-list-check"></i><b data-progress-count>{{ $progress['followed'] }}/{{ $progress['total'] }}</b> followed</span></div><div class="coop-progress"><div class="coop-progress__track"><span data-progress-bar style="width:{{ $progress['percent'] }}%"></span></div><strong data-progress-percent>{{ $progress['percent'] }}%</strong></div></div>
                                    <button type="button" class="coop-open" data-open-coop-detail="checklist-{{ $checklist->getKey() }}" aria-label="Open {{ $checklist->plan_title }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
                                </article>

                                <template id="checklist-{{ $checklist->getKey() }}">
                                    <div class="coop-detail-grid"><div class="coop-detail wide"><span>Plan</span><strong>{{ $checklist->plan_title }}</strong></div><div class="coop-detail wide"><span>Guidance</span><strong>{{ $checklist->plan_description ?: $checklist->source_description ?: 'No additional guidance recorded.' }}</strong></div><div class="coop-detail"><span>Barangay</span><strong>{{ $checklist->barangay?->barangay_name ?? 'Not assigned' }}</strong></div><div class="coop-detail"><span>Priority</span><strong>{{ strtoupper($checklist->priority_level) }}</strong></div><div class="coop-detail"><span>Target date</span><strong>{{ $checklist->target_date?->format('M d, Y') ?? 'No target date' }}</strong></div><div class="coop-detail"><span>Assigned to</span><strong>{{ $checklist->assignee?->full_name ?? 'Station and barangay partners' }}</strong></div><div class="coop-detail wide"><span>Success indicator</span><strong>{{ $checklist->success_indicator ?: 'Complete and verify every assigned step.' }}</strong></div></div>
                                    <h3 class="coop-steps-title">Visit checklist · {{ $progress['followed'] }} of {{ $progress['total'] }} followed</h3>
                                    <div data-modal-steps>
                                        @foreach($checklist->items as $step)
                                            <form class="coop-step" data-checklist-step data-item-id="{{ $step->getKey() }}" data-checklist-id="{{ $checklist->getKey() }}" data-saved-status="{{ $step->status }}" action="{{ route('firefighter.barangay-cooperation.checklists.items.update', [$checklist->getKey(), $step->getKey()]) }}">
                                                <div class="coop-step__title"><span class="coop-step__number">{{ $step->item_order }}</span><span>{{ $step->item_text }}</span></div>
                                                <div class="coop-step__controls"><select name="status" aria-label="Checklist status"><option value="pending" @selected($step->status === 'pending')>Pending</option><option value="followed" @selected($step->status === 'followed')>Followed</option><option value="not_followed" @selected($step->status === 'not_followed')>Not followed</option></select><input name="review_notes" value="{{ $step->review_notes }}" maxlength="1000" placeholder="Visit note (optional)" aria-label="Visit note"><button type="submit">Save step</button></div>
                                                <label class="coop-proof-picker"><span><i class="fa-regular fa-images"></i> Visit proof photos</span><input type="file" name="visit_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-visit-proof-input><small>Optional · up to 5 JPG, PNG, or WebP photos · 15 MB combined</small><div class="coop-proof-preview" data-visit-proof-preview></div></label>
                                                <div class="coop-step__review" data-step-review>@if($step->reviewed_at)Last verified {{ $step->reviewed_at->format('M d, Y h:i A') }} by {{ $step->reviewer?->full_name ?? 'authorized user' }}@elseNot yet verified during a visit.@endif</div>
                                            </form>
                                        @endforeach
                                    </div>
                                    @php
                                        $timeline = $checklist->activities
                                            ->reject(fn ($activity) => $activity->event_type === 'firefighter_visit_step_updated')
                                            ->map(fn ($activity) => (object) ['kind' => 'activity', 'date' => $activity->created_at, 'record' => $activity])
                                            ->concat($checklist->visits->map(fn ($visit) => (object) ['kind' => 'visit', 'date' => $visit->visited_at, 'record' => $visit]))
                                            ->sortByDesc('date')
                                            ->take(30);
                                    @endphp
                                    <h3 class="coop-timeline-title">Cooperation timeline</h3>
                                    <div class="coop-timeline" data-cooperation-timeline>
                                        @forelse($timeline as $entry)
                                            @if($entry->kind === 'visit')
                                                @php $visit = $entry->record; @endphp
                                                <article class="coop-timeline__item"><div class="coop-timeline__head"><strong>{{ $visit->firefighter?->full_name ?? 'BFP firefighter' }} · Step {{ $visit->item?->item_order ?? 'update' }}</strong><time>{{ $visit->visited_at?->format('M d, Y h:i A') }}</time></div><p>Marked as {{ str_replace('_',' ',$visit->verified_status) }}{{ $visit->visit_notes ? ' · '.$visit->visit_notes : '' }}</p><span class="coop-signoff {{ $visit->signoff_status }}"><i class="fa-solid fa-signature"></i>{{ str_replace('_',' ',$visit->signoff_status) }}</span>@if($visit->signatory)<p>Signed by {{ $visit->signatory->full_name }}{{ $visit->signoff_notes ? ' · '.$visit->signoff_notes : '' }}</p>@endif @if($visit->photos->isNotEmpty())<div class="coop-visit-photos">@foreach($visit->photos as $photo)<a href="{{ $photo->url }}" target="_blank" rel="noopener"><img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"></a>@endforeach</div>@endif</article>
                                            @else
                                                @php $activity = $entry->record; @endphp
                                                <article class="coop-timeline__item"><div class="coop-timeline__head"><strong>{{ $activity->user?->full_name ?? 'Authorized user' }}</strong><time>{{ $activity->created_at?->format('M d, Y h:i A') }}</time></div><p>{{ ucwords(str_replace('_',' ',$activity->event_type)) }}{{ $activity->notes ? ' · '.$activity->notes : '' }}</p></article>
                                            @endif
                                        @empty
                                            <article class="coop-timeline__item"><p>No cooperation activity has been recorded yet.</p></article>
                                        @endforelse
                                    </div>
                                </template>
                            @empty
                                <div class="coop-empty"><i class="fa-solid fa-list-check"></i><strong>No published checklist yet</strong><span>Confirmed plans for connected barangays will appear here.</span></div>
                            @endforelse
                        </div>
                    </section>

                    <aside class="coop-panel">
                        <header class="coop-panel__head"><div><h2>BFP mitigation plans</h2><p>Station-issued actions for connected barangays.</p></div><span class="coop-count" data-visible-count="mitigation">{{ $mitigations->count() }}</span></header>
                        <div class="coop-list" data-coop-list="mitigation">
                            @forelse($mitigations as $action)
                                <article class="coop-card" data-coop-record data-kind="mitigation" data-barangay="{{ $action->barangay_id }}" data-status="{{ $action->status }}" data-search="{{ strtolower(($action->barangay?->barangay_name ?? '').' '.$action->title.' '.$action->description) }}"><div><div class="coop-card__top"><span class="coop-chip">{{ $action->barangay?->barangay_name ?? 'Barangay' }}</span><span class="coop-chip priority-{{ $action->priority_level }}">{{ $action->priority_level }}</span><span class="coop-chip status-{{ $action->status }}">{{ str_replace('_',' ',$action->status) }}</span></div><h3>{{ $action->title }}</h3><div class="coop-card__meta"><span><i class="fa-regular fa-calendar"></i>{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</span><span><i class="fa-solid fa-user-group"></i>{{ $action->responsibleTeam?->team_name ?? $action->responsibleUser?->full_name ?? 'Station–barangay partners' }}</span></div></div><button type="button" class="coop-open" data-open-coop-detail="mitigation-{{ $action->getKey() }}" aria-label="Open {{ $action->title }}"><i class="fa-solid fa-eye"></i></button></article>
                                <template id="mitigation-{{ $action->getKey() }}"><div class="coop-detail-grid"><div class="coop-detail wide"><span>Mitigation</span><strong>{{ $action->title }}</strong></div><div class="coop-detail wide"><span>Implementation guidance</span><strong>{{ $action->description ?: 'No description recorded.' }}</strong></div><div class="coop-detail"><span>Barangay</span><strong>{{ $action->barangay?->barangay_name ?? 'Not assigned' }}</strong></div><div class="coop-detail"><span>Status</span><strong>{{ strtoupper(str_replace('_',' ',$action->status)) }}</strong></div><div class="coop-detail"><span>Priority</span><strong>{{ strtoupper($action->priority_level) }}</strong></div><div class="coop-detail"><span>Target</span><strong>{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</strong></div><div class="coop-detail wide"><span>Responsible partner</span><strong>{{ $action->responsibleTeam?->team_name ?? $action->responsibleUser?->full_name ?? 'Station–barangay cooperation' }}</strong></div><div class="coop-detail wide"><span>Linked plan</span><strong>{{ $action->aiActionPlanChecklist?->plan_title ?? $action->checklist?->title ?? 'Direct BFP mitigation' }}</strong></div><div class="coop-detail wide"><span>Implementation notes</span><strong>{{ $action->implementation_notes ?: $action->remarks ?: 'No implementation note recorded.' }}</strong></div><div class="coop-detail"><span>Attachments</span><strong>{{ $action->attachments->count() }} file(s)</strong></div><div class="coop-detail"><span>Issued by</span><strong>{{ $action->creator?->full_name ?? 'Station command' }}</strong></div></div></template>
                            @empty
                                <div class="coop-empty"><i class="fa-solid fa-shield-halved"></i><strong>No mitigation plan found</strong><span>Plans issued for station coverage will appear here.</span></div>
                            @endforelse
                        </div>
                    </aside>
                </div>
            </div>
        </main>

        <div class="coop-modal" id="coopDetailModal" role="dialog" aria-modal="true" aria-labelledby="coopDetailTitle" hidden><div class="coop-modal__card"><header class="coop-modal__head"><div><p class="coop-modal__eyebrow" id="coopDetailType">Cooperation record</p><h2 id="coopDetailTitle">Plan details</h2></div><button type="button" class="coop-modal__close" data-close-coop-modal aria-label="Close details"><i class="fa-solid fa-xmark"></i></button></header><div class="coop-modal__body" id="coopDetailBody"></div></div></div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const workspace = document.getElementById('barangayCooperationWorkspace');
                const modal = document.getElementById('coopDetailModal');
                const modalBody = document.getElementById('coopDetailBody');
                if (!workspace || !modal || !modalBody || workspace.dataset.bound === 'true') return;
                workspace.dataset.bound = 'true';
                const search = document.getElementById('coopSearch');
                const status = document.getElementById('coopStatusFilter');
                let selectedBarangay = 'all';
                let lastFocused = null;

                const toast = (message, type = 'success') => {
                    document.querySelector('.coop-toast')?.remove();
                    const node = document.createElement('div');
                    node.className = `coop-toast ${type === 'error' ? 'error' : ''}`;
                    node.textContent = message;
                    document.body.appendChild(node);
                    window.setTimeout(() => node.remove(), 3200);
                };

                const clearProofPreviews = (scope = modalBody) => {
                    scope.querySelectorAll('[data-visit-proof-input]').forEach((input) => {
                        (input._previewUrls || []).forEach((url) => URL.revokeObjectURL(url));
                        input._previewUrls = [];
                        const preview = input.closest('.coop-proof-picker')?.querySelector('[data-visit-proof-preview]');
                        if (preview) preview.innerHTML = '';
                    });
                };

                const prependVisitTimeline = (visit) => {
                    const timeline = modalBody.querySelector('[data-cooperation-timeline]');
                    if (!timeline || !visit) return;
                    if (timeline.children.length === 1 && timeline.firstElementChild?.textContent?.includes('No cooperation activity')) timeline.innerHTML = '';
                    const item = document.createElement('article');
                    item.className = 'coop-timeline__item';
                    const head = document.createElement('div');
                    head.className = 'coop-timeline__head';
                    const actor = document.createElement('strong');
                    actor.textContent = visit.firefighter || 'BFP firefighter';
                    const time = document.createElement('time');
                    time.textContent = visit.visited_at_label || 'Just now';
                    head.append(actor, time);
                    const copy = document.createElement('p');
                    copy.textContent = `Marked as ${String(visit.status || '').replaceAll('_', ' ')}${visit.notes ? ` · ${visit.notes}` : ''}`;
                    const signoff = document.createElement('span');
                    signoff.className = `coop-signoff ${visit.signoff_status || 'pending'}`;
                    signoff.innerHTML = '<i class="fa-solid fa-signature"></i>';
                    signoff.append(document.createTextNode(String(visit.signoff_status || 'pending').replaceAll('_', ' ')));
                    item.append(head, copy, signoff);
                    if (Array.isArray(visit.photos) && visit.photos.length) {
                        const photos = document.createElement('div');
                        photos.className = 'coop-visit-photos';
                        visit.photos.forEach((photo) => {
                            const link = document.createElement('a');
                            link.href = photo.url;
                            link.target = '_blank';
                            link.rel = 'noopener';
                            const image = document.createElement('img');
                            image.src = photo.url;
                            image.alt = photo.name || 'Visit proof';
                            link.appendChild(image);
                            photos.appendChild(link);
                        });
                        item.appendChild(photos);
                    }
                    timeline.prepend(item);
                };

                const filterRecords = () => {
                    const query = String(search?.value || '').trim().toLowerCase();
                    const selectedStatus = status?.value || 'all';
                    ['checklist', 'mitigation'].forEach((kind) => {
                        let visible = 0;
                        workspace.querySelectorAll(`[data-coop-record][data-kind="${kind}"]`).forEach((record) => {
                            const matchesBarangay = selectedBarangay === 'all' || record.dataset.barangay === selectedBarangay;
                            const matchesStatus = selectedStatus === 'all' || record.dataset.status === selectedStatus;
                            const matchesQuery = !query || String(record.dataset.search || '').includes(query);
                            record.hidden = !(matchesBarangay && matchesStatus && matchesQuery);
                            if (!record.hidden) visible += 1;
                        });
                        const count = workspace.querySelector(`[data-visible-count="${kind}"]`);
                        if (count) count.textContent = String(visible);
                    });
                };

                workspace.querySelectorAll('[data-barangay-filter]').forEach((button) => button.addEventListener('click', () => {
                    workspace.querySelectorAll('[data-barangay-filter]').forEach((item) => item.classList.toggle('active', item === button));
                    selectedBarangay = button.dataset.barangayFilter || 'all';
                    filterRecords();
                }));
                search?.addEventListener('input', filterRecords);
                status?.addEventListener('change', filterRecords);

                const closeModal = () => {
                    clearProofPreviews();
                    modal.hidden = true;
                    modalBody.innerHTML = '';
                    document.body.style.overflow = '';
                    lastFocused?.focus?.();
                };
                modal.querySelector('[data-close-coop-modal]')?.addEventListener('click', closeModal);
                modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
                document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

                workspace.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-open-coop-detail]');
                    if (!button) return;
                    const template = document.getElementById(button.dataset.openCoopDetail);
                    if (!template) return;
                    lastFocused = button;
                    modalBody.replaceChildren(template.content.cloneNode(true));
                    document.getElementById('coopDetailType').textContent = button.dataset.openCoopDetail.startsWith('checklist-') ? 'Barangay visit checklist' : 'BFP mitigation plan';
                    document.getElementById('coopDetailTitle').textContent = button.closest('.coop-card')?.querySelector('h3')?.textContent?.trim() || 'Plan details';
                    modal.hidden = false;
                    document.body.style.overflow = 'hidden';
                    modal.querySelector('[data-close-coop-modal]')?.focus();
                });

                modalBody.addEventListener('change', (event) => {
                    const input = event.target.closest('[data-visit-proof-input]');
                    if (!input) return;
                    const files = Array.from(input.files || []);
                    const totalBytes = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
                    if (files.length > 5 || totalBytes > 15 * 1024 * 1024) {
                        input.value = '';
                        clearProofPreviews(input.closest('.coop-step'));
                        toast(files.length > 5 ? 'Select up to five visit photos.' : 'Visit photos must not exceed 15 MB combined.', 'error');
                        return;
                    }
                    clearProofPreviews(input.closest('.coop-step'));
                    input._previewUrls = [];
                    const preview = input.closest('.coop-proof-picker')?.querySelector('[data-visit-proof-preview]');
                    files.forEach((file) => {
                        const url = URL.createObjectURL(file);
                        input._previewUrls.push(url);
                        const figure = document.createElement('figure');
                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = `${file.name} preview`;
                        const caption = document.createElement('figcaption');
                        caption.textContent = file.name;
                        figure.append(image, caption);
                        preview?.appendChild(figure);
                    });
                });

                modalBody.addEventListener('submit', async (event) => {
                    const form = event.target.closest('[data-checklist-step]');
                    if (!form) return;
                    event.preventDefault();
                    const button = form.querySelector('button[type="submit"]');
                    const oldStatus = form.dataset.savedStatus || 'pending';
                    const newStatus = form.querySelector('select[name="status"]')?.value || 'pending';
                    button.disabled = true;
                    button.textContent = 'Saving…';
                    try {
                        const formData = new FormData(form);
                        formData.set('_method', 'PATCH');
                        const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: formData });
                        const result = await response.json();
                        if (!response.ok || result.success === false) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Unable to update the checklist step.');
                        form.dataset.savedStatus = newStatus;
                        const review = form.querySelector('[data-step-review]');
                        if (review) review.textContent = newStatus === 'pending' ? 'Not yet verified during a visit.' : `Verified just now by ${result.data.reviewed_by || 'firefighter'}.`;

                        const template = document.getElementById(`checklist-${form.dataset.checklistId}`);
                        const sourceForm = template?.content.querySelector(`[data-item-id="${form.dataset.itemId}"]`);
                        if (sourceForm) {
                            const sourceSelect = sourceForm.querySelector('[name="status"]');
                            sourceSelect.value = newStatus;
                            sourceSelect.querySelectorAll('option').forEach((option) => option.toggleAttribute('selected', option.value === newStatus));
                            const sourceNote = sourceForm.querySelector('[name="review_notes"]');
                            sourceNote.value = form.querySelector('[name="review_notes"]')?.value || '';
                            sourceNote.setAttribute('value', sourceNote.value);
                            sourceForm.dataset.savedStatus = newStatus;
                            const sourceReview = sourceForm.querySelector('[data-step-review]');
                            if (sourceReview) sourceReview.textContent = review?.textContent || '';
                        }

                        const progressTitle = `Visit checklist · ${result.data.progress.followed} of ${result.data.progress.total} followed`;
                        const modalProgressTitle = modalBody.querySelector('.coop-steps-title');
                        const templateProgressTitle = template?.content.querySelector('.coop-steps-title');
                        if (modalProgressTitle) modalProgressTitle.textContent = progressTitle;
                        if (templateProgressTitle) templateProgressTitle.textContent = progressTitle;
                        prependVisitTimeline(result.data.visit);
                        const currentTimelineItem = modalBody.querySelector('[data-cooperation-timeline] .coop-timeline__item');
                        const sourceTimeline = template?.content.querySelector('[data-cooperation-timeline]');
                        if (currentTimelineItem && sourceTimeline) {
                            if (sourceTimeline.children.length === 1 && sourceTimeline.firstElementChild?.textContent?.includes('No cooperation activity')) sourceTimeline.innerHTML = '';
                            sourceTimeline.prepend(currentTimelineItem.cloneNode(true));
                        }
                        const proofInput = form.querySelector('[data-visit-proof-input]');
                        if (proofInput) proofInput.value = '';
                        clearProofPreviews(form);

                        const card = workspace.querySelector(`[data-checklist-card="${form.dataset.checklistId}"]`);
                        if (card) {
                            card.dataset.status = result.data.checklist_status;
                            const statusChip = card.querySelector('[data-checklist-status]');
                            if (statusChip) { statusChip.className = `coop-chip status-${result.data.checklist_status}`; statusChip.textContent = result.data.checklist_status.replaceAll('_', ' '); }
                            card.querySelector('[data-progress-bar]').style.width = `${result.data.progress.percent}%`;
                            card.querySelector('[data-progress-percent]').textContent = `${result.data.progress.percent}%`;
                            card.querySelector('[data-progress-count]').textContent = `${result.data.progress.followed}/${result.data.progress.total}`;
                        }
                        if (oldStatus === 'pending' && newStatus !== 'pending') {
                            const summary = workspace.querySelector('[data-summary-value="openSteps"]');
                            if (summary) summary.textContent = String(Math.max(0, Number(summary.textContent || 0) - 1));
                        } else if (oldStatus !== 'pending' && newStatus === 'pending') {
                            const summary = workspace.querySelector('[data-summary-value="openSteps"]');
                            if (summary) summary.textContent = String(Number(summary.textContent || 0) + 1);
                        }
                        filterRecords();
                        toast(result.message || 'Checklist step updated.');
                    } catch (error) {
                        toast(error.message || 'Unable to update the checklist step.', 'error');
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Save step';
                    }
                });
            });
        </script>
    </div>
    @include('partials.pwa-body')
</body>
</html>
