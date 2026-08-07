<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Mitigation Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <style>
        html,
        body,
        body.chief-dashboard-page {
            min-height: 100%;
            height: auto;
            overflow-x: hidden;
            overflow-y: auto;
        }

        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Inter, system-ui, sans-serif; }
        .mitigation-shell { min-height: 100vh; background: linear-gradient(180deg, #fff7ed 0%, #f8fafc 22%); }
        .mitigation-main { min-width: 0; width: 100%; overflow: visible; }
        .mitigation-wrap { width: 100%; max-width: 1440px; margin: 0 auto; }
        .dashboard-container { padding: 28px; overflow: visible; }
        .top-bar { background: rgba(255,255,255,.82); backdrop-filter: blur(12px); border: 1px solid rgba(226,232,240,.9); min-height: 72px; padding: 0 24px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:16px; z-index:40; margin-bottom:24px; border-radius:24px; box-shadow:0 16px 36px rgba(15,23,42,.06); }
        .mitigation-banner { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding:32px; margin-bottom:24px; border-radius:24px; border-left:4px solid #f59e0b; background: linear-gradient(135deg, rgba(255,247,237,.98), rgba(255,251,235,.95)); box-shadow: 0 18px 36px rgba(15,23,42,.05); }
        .mitigation-banner__title { margin:0; color:#78350f; font-size:1.9rem; font-weight:900; letter-spacing:-0.04em; }
        .mitigation-banner__copy { margin:8px 0 0; color:#b45309; font-size:.96rem; line-height:1.65; max-width:72ch; }
        .mitigation-banner__meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; min-width:320px; }
        .mitigation-banner__meta-card { padding:14px 16px; border-radius:18px; background:rgba(255,255,255,.7); border:1px solid rgba(251,191,36,.25); }
        .mitigation-banner__meta-label { color:#92400e; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .mitigation-banner__meta-value { margin-top:6px; color:#1f2937; font-size:.92rem; font-weight:800; }
        .station-badge { color:#6b7280; font-size:13px; background:#f8fafc; padding:8px 12px; border-radius:999px; border:1px solid #e2e8f0; }
        .mitigation-stack { display:flex; flex-direction:column; gap:22px; }
        .summary-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:16px; }
        .card, .panel { background:rgba(255,255,255,.97); border:1px solid rgba(226,232,240,.92); border-radius:28px; box-shadow:0 18px 36px rgba(15,23,42,.05); }
        .card { padding:22px; }
        .panel { padding:24px; }
        .card-label { color:#64748b; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .card-value { margin-top:14px; color:#0f172a; font-size:clamp(1.9rem,2.4vw,2.5rem); font-weight:900; line-height:1; }
        .card-copy { margin-top:10px; color:#64748b; font-size:.86rem; line-height:1.55; }
        .panel-head { display:flex; align-items:end; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .panel-head h3 { margin:0; color:#0f172a; font-size:1.18rem; font-weight:900; }
        .panel-head p { margin:6px 0 0; color:#64748b; font-size:.9rem; line-height:1.55; max-width:72ch; }
        .hero-btn, .row-btn, .submit-btn, .cancel-btn { border:none; border-radius:999px; font-weight:800; display:inline-flex; align-items:center; gap:8px; }
        .hero-btn, .submit-btn { min-height:44px; padding:0 18px; background:linear-gradient(135deg, #f97316, #ef4444); color:#fff; box-shadow:0 16px 30px rgba(239,68,68,.16); }
        .table-wrap { overflow:auto; border:1px solid rgba(226,232,240,.92); border-radius:24px; background:linear-gradient(180deg, rgba(248,250,252,.75), rgba(255,255,255,.96)); max-height:72vh; }
        table { width:100%; min-width:1180px; border-collapse:separate; border-spacing:0; table-layout:fixed; }
        th, td { padding:16px 14px; text-align:left; vertical-align:top; border-bottom:1px solid rgba(241,245,249,1); }
        th { position:sticky; top:0; z-index:2; background:rgba(248,250,252,.96); backdrop-filter:blur(8px); color:#64748b; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        td { color:#334155; font-size:.88rem; background:rgba(255,255,255,.72); }
        tbody tr:hover td { background:rgba(255,247,237,.68); }
        tbody tr:last-child td { border-bottom:none; }
        th:nth-child(1), td:nth-child(1) { width: 22%; }
        th:nth-child(2), td:nth-child(2) { width: 11%; }
        th:nth-child(3), td:nth-child(3) { width: 11%; }
        th:nth-child(4), td:nth-child(4) { width: 9%; }
        th:nth-child(5), td:nth-child(5) { width: 10%; }
        th:nth-child(6), td:nth-child(6) { width: 10%; }
        th:nth-child(7), td:nth-child(7) { width: 17%; }
        th:nth-child(8), td:nth-child(8) { width: 14%; }
        th:nth-child(9), td:nth-child(9) { width: 12%; }
        .title { color:#0f172a; font-weight:800; font-size:.95rem; line-height:1.4; }
        .note { margin-top:6px; color:#64748b; font-size:.82rem; line-height:1.5; }
        .note--clamp {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .chip-row { display:flex; flex-wrap:wrap; gap:8px; }
        .chip { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 10px; border-radius:999px; background:#f8fafc; border:1px solid rgba(226,232,240,.96); color:#475569; font-size:.74rem; font-weight:700; }
        .tag { display:inline-flex; align-items:center; min-height:30px; padding:0 12px; border-radius:999px; font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
        .tag.critical { background:#fef2f2; color:#b91c1c; } .tag.high { background:#fff7ed; color:#c2410c; } .tag.medium { background:#eff6ff; color:#1d4ed8; } .tag.low { background:#ecfdf5; color:#047857; }
        .tag.planned { background:#fff7ed; color:#c2410c; } .tag.in_progress { background:#eff6ff; color:#1d4ed8; } .tag.blocked { background:#fef2f2; color:#b91c1c; } .tag.completed { background:#ecfdf5; color:#047857; } .tag.cancelled { background:#f1f5f9; color:#475569; }
        .row-actions { display:flex; align-items:center; justify-content:flex-start; gap:8px; min-width:0; }
        .row-btn { width:40px; justify-content:center; min-height:40px; padding:0; font-size:.86rem; white-space:nowrap; border:1px solid transparent; }
        .row-btn.edit { background:#eff6ff; color:#1d4ed8; }
        .row-btn.delete { background:#fef2f2; color:#b91c1c; }
        .row-btn i { pointer-events:none; }
        .attachments { display:flex; flex-direction:column; gap:8px; }
        .attachments a { display:block; color:#c2410c; font-weight:700; text-decoration:none; overflow-wrap:anywhere; }
        .source-cell,
        .proof-cell { min-width: 0; }
        .empty { padding:26px 24px; border-radius:22px; border:1px dashed rgba(203,213,225,.96); color:#64748b; text-align:center; background:#f8fafc; }
        .form-grid { display:grid; grid-template-columns:1fr; gap:12px; }
        .mitigation-field { display:flex; flex-direction:column; gap:8px; }
        .mitigation-field--full { grid-column:1 / -1; }
        .mitigation-field label { color:#334155; font-size:.78rem; font-weight:800; }
        .mitigation-field input, .mitigation-field select, .mitigation-field textarea { width:100%; min-height:44px; border:1px solid rgba(203,213,225,.96); border-radius:14px; padding:10px 12px; font-size:.84rem; color:#0f172a; background:#fff; }
        .mitigation-field textarea { min-height:96px; resize:vertical; }
        .mitigation-dialog { position:fixed; inset:0; z-index:90; display:none; align-items:flex-start; justify-content:center; padding:28px 20px; background:rgba(15,23,42,.52); backdrop-filter: blur(8px); overflow-y:auto; }
        .mitigation-dialog.is-open { display:flex; }
        .mitigation-dialog__card { width:min(560px, calc(100vw - 40px)); max-width:560px; max-height:min(78vh, 720px); overflow-y:auto; padding:18px; border-radius:24px; border:1px solid rgba(226,232,240,.94); background:rgba(255,255,255,.98); box-shadow:0 28px 60px rgba(15,23,42,.18); flex:0 0 auto; margin:0 auto; }
        .mitigation-dialog__head { display:flex; align-items:start; justify-content:space-between; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid rgba(226,232,240,.92); }
        .mitigation-dialog__head h3 { margin:0; color:#0f172a; font-size:1rem; font-weight:900; }
        .mitigation-dialog__head p { margin:4px 0 0; color:#64748b; font-size:.82rem; line-height:1.5; max-width:42ch; }
        .mitigation-dialog__close { width:42px; height:42px; border:1px solid rgba(226,232,240,.94); border-radius:14px; background:#fff; color:#334155; }
        .form-actions { display:flex; justify-content:end; gap:12px; margin-top:20px; }
        .cancel-btn { min-height:44px; padding:0 18px; border:1px solid rgba(203,213,225,.96); background:#fff; color:#475569; }
        .mitigation-dialog form { margin: 0; }
        .mitigation-dialog .mitigation-field textarea { min-height: 88px; }
        .mitigation-dialog .mitigation-field input[type="file"] { min-height: 0; padding: 10px 0; border: 0; background: transparent; }
        #createModal .mitigation-dialog__card,
        #editModal .mitigation-dialog__card {
            width: min(560px, calc(100vw - 40px)) !important;
            max-width: 560px !important;
        }
        #deleteModal .mitigation-dialog__card {
            width: min(420px, calc(100vw - 40px)) !important;
            max-width: 420px !important;
        }
        .user-dropdown { position:relative; }
        .user-dropdown-toggle { display:flex; align-items:center; gap:12px; padding:8px 16px; border-radius:999px; background:#fffbeb; border:1px solid #fef3c7; cursor:pointer; }
        .user-dropdown-menu { position:absolute; top:calc(100% + 12px); right:0; width:260px; background:white; border-radius:18px; box-shadow:0 24px 56px rgba(15,23,42,.14); border:1px solid rgba(226,232,240,.95); opacity:0; visibility:hidden; transform:translateY(10px); transition:all .2s ease; overflow:hidden; }
        .user-dropdown-menu.show { opacity:1; visibility:visible; transform:translateY(0); }
        .user-dropdown-item { display:flex; align-items:center; gap:12px; padding:12px 18px; color:#1e293b; text-decoration:none; font-size:14px; }
        .user-dropdown-item:hover { background:#fff0e8; color:#c63d26; }

        .mobile-page-head {
            display: none;
        }
        
        @media (min-width: 768px) {
            .mitigation-main {
                margin-left: 348px;
                width: calc(100% - 348px);
            }
        }

        @media (max-width: 1400px) {
            .summary-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
        }

        @media (max-width: 992px) {
            .mitigation-banner {
                flex-direction: column;
            }

            .mitigation-banner__meta {
                width: 100%;
                min-width: 0;
            }

            .panel-head,
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .panel-head .hero-btn,
            .top-bar .user-dropdown {
                width: 100%;
            }

            .panel-head .hero-btn {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            body,
            body.chief-dashboard-page,
            .mitigation-shell,
            .mitigation-main,
            .dashboard-container,
            .mitigation-wrap {
                overflow: visible !important;
            }
            .mitigation-main { margin-left: 0; width: 100%; }
            .dashboard-container { width: min(100%, 760px); padding: 10px 10px calc(env(safe-area-inset-bottom, 0px) + 92px); }
            .mitigation-banner,
            .card,
            .panel {
                border-radius: 20px;
                box-shadow: 0 10px 24px rgba(15,23,42,.05);
                margin-inline: 0;
            }
            .top-bar {
                display: none;
            }
            .mobile-page-head {
                display: block;
                margin-bottom: 6px;
                padding: 1px 2px 0;
            }
            .mobile-page-head__eyebrow {
                color: #c2410c;
                font-size: .56rem;
                font-weight: 900;
                letter-spacing: .07em;
                text-transform: uppercase;
            }
            .mobile-page-head h1 {
                margin: 2px 0 0;
                color: #0f172a;
                font-size: .94rem;
                font-weight: 900;
                line-height: 1.2;
            }
            .mitigation-banner {
                padding: 11px;
                gap: 8px;
                margin-bottom: 8px;
                border-left-width: 3px;
            }
            .chief-theme-hero__eyebrow {
                padding: 5px 9px;
                font-size: .54rem;
                letter-spacing: .07em;
            }
            .mitigation-banner__title { font-size: .94rem; line-height: 1.16; }
            .mitigation-banner__copy { font-size: .68rem; line-height: 1.32; margin-top: 4px; }
            .mitigation-banner__meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
                min-width: 0;
            }
            .mitigation-banner__meta-card {
                padding: 7px 8px;
                border-radius: 11px;
            }
            .mitigation-banner__meta-label {
                font-size: .5rem;
            }
            .mitigation-banner__meta-value {
                margin-top: 2px;
                font-size: .64rem;
                line-height: 1.28;
            }
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 7px;
            }
            .mitigation-stack { gap: 8px; }
            .panel, .card { padding: 10px; border-radius: 15px; }
            .card-label { font-size: .58rem; }
            .card-value { margin-top: 6px; font-size: 1rem; }
            .card-copy { margin-top: 3px; font-size: .62rem; line-height: 1.28; }
            .panel-head {
                gap: 7px;
                margin-bottom: 8px;
            }
            .panel-head h3 { font-size: .82rem; }
            .panel-head p { font-size: .66rem; line-height: 1.28; }
            .form-grid { grid-template-columns:1fr; }
            .panel-head,
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .hero-btn,
            .submit-btn,
            .cancel-btn {
                width: 100%;
                justify-content: center;
                min-height: 38px;
                padding: 0 12px;
                font-size: .72rem;
            }
            .panel-head .hero-btn {
                min-height: 34px;
            }
            .chip {
                min-height: 20px;
                padding: 0 6px;
                font-size: .58rem;
                gap: 4px;
            }
            .tag {
                min-height: 20px;
                padding: 0 7px;
                font-size: .56rem;
            }
            .title {
                font-size: .74rem;
            }
            .note {
                margin-top: 3px;
                font-size: .63rem;
                line-height: 1.28;
            }
            .table-wrap {
                overflow: visible;
                max-height:none;
                border: 0;
                background: transparent;
                border-radius: 0;
                padding: 0;
            }
            table {
                min-width: 0;
                width: 100%;
                table-layout: auto;
                border-spacing: 0;
            }
            thead {
                display: none;
            }
            tbody {
                display: grid;
                gap: 7px;
            }
            tbody tr {
                display: block;
                border: 1px solid rgba(226,232,240,.92);
                border-radius: 14px;
                background: rgba(255,255,255,.98);
                box-shadow: 0 8px 18px rgba(15,23,42,.035);
                overflow: hidden;
            }
            tbody tr:hover td {
                background: transparent;
            }
            td {
                display: grid;
                grid-template-columns: 58px minmax(0, 1fr);
                gap: 6px;
                width: 100% !important;
                padding: 7px 9px;
                font-size: .66rem;
                background: transparent;
                border-bottom: 1px solid rgba(241,245,249,1);
            }
            td::before {
                content: attr(data-label);
                color: #94a3b8;
                font-size: .52rem;
                font-weight: 800;
                letter-spacing: .06em;
                text-transform: uppercase;
                line-height: 1.35;
                padding-top: 2px;
            }
            td:last-child {
                border-bottom: 0;
            }
            td[data-label="Actions"] {
                display: block;
                padding-top: 6px;
            }
            td[data-label="Actions"]::before {
                display: none;
            }
            .row-actions {
                justify-content: flex-end;
                gap: 5px;
            }
            .row-btn {
                width: 28px;
                min-height: 28px;
                font-size: .62rem;
            }
            .attachments {
                gap: 4px;
            }
            .attachments a {
                font-size: .62rem;
            }
            .mitigation-dialog {
                align-items: flex-end;
                padding: 10px;
            }
            .mitigation-dialog__card {
                width: 100%;
                max-width: 100%;
                max-height: min(78dvh, 620px);
                border-radius: 22px 22px 16px 16px;
                padding: 11px;
            }
            #createModal .mitigation-dialog__card,
            #editModal .mitigation-dialog__card,
            #deleteModal .mitigation-dialog__card {
                width: 100% !important;
                max-width: 100% !important;
            }
            .mitigation-dialog__head { margin-bottom: 10px; padding-bottom: 8px; }
            .mitigation-dialog__card::before {
                content: "";
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 4px auto 10px;
            }
            .mitigation-field {
                gap: 5px;
            }
            .mitigation-field label {
                font-size: .66rem;
            }
            .mitigation-field input,
            .mitigation-field select,
            .mitigation-field textarea {
                min-height: 36px;
                padding: 8px 9px;
                border-radius: 12px;
                font-size: .74rem;
            }
            .mitigation-dialog .mitigation-field textarea {
                min-height: 68px;
            }
            .empty {
                padding: 14px 12px;
                border-radius: 16px;
                font-size: .7rem;
            }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @include('chief.partials.sidebar', ['active' => 'mitigations'])
    @php
        $openMitigations = $mitigationActions->whereIn('status', ['planned', 'in_progress', 'blocked']);
        $completedMitigations = $mitigationActions->where('status', 'completed');
        $criticalMitigations = $mitigationActions->whereIn('priority_level', ['critical', 'high']);
    @endphp
    <div class="mitigation-shell">
        <main class="mitigation-main">
            <div class="dashboard-container chief-dashboard-container">
            <div class="mitigation-wrap chief-theme-shell">
                <div class="top-bar">
                    <div class="flex items-center gap-4">
                        <div>
                            <h1 style="margin:0; color:#1e293b; font-size:1.2rem; font-weight:900;">Mitigation Control</h1>
                            <p style="margin:4px 0 0; color:#64748b; font-size:.78rem; font-weight:700;">Command action tracking. {{ now()->format('l, F j') }}</p>
                        </div>
                    </div>
                    <div class="user-dropdown">
                        <button type="button" class="user-dropdown-toggle" id="mitigationUserMenuToggle">
                            <span class="w-10 h-10 rounded-full bg-orange-500 text-white inline-flex items-center justify-center font-bold">
                                {{ substr(auth()->user()->first_name ?? 'S', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                            </span>
                            <div style="text-align:left;">
                                <div style="font-weight:800; color:#334155;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                                <div style="font-size:.76rem; color:#64748b;">{{ auth()->user()->role->role ?? 'Station Admin' }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="font-size:.75rem; color:#64748b;"></i>
                        </button>
                        <div class="user-dropdown-menu" id="mitigationUserMenu">
                            <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item"><i class="fa-solid fa-user"></i> Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item" style="width:100%; border:none; background:none; text-align:left;"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                </div>

                <section class="mobile-page-head" aria-hidden="true">
                    <div class="mobile-page-head__eyebrow">Mitigation</div>
                    <h1>Mitigation Control</h1>
                </section>

                <section class="mitigation-banner chief-theme-hero">
                    <div>
                        <span class="chief-theme-hero__eyebrow">
                            <i class="fa-solid fa-shield-halved"></i>
                            Mitigation Control
                        </span>
                        <h2 class="mitigation-banner__title chief-theme-hero__title">Track station mitigation work.</h2>
                        <p class="mitigation-banner__copy chief-theme-hero__copy">Create, update, and close actions for covered barangays.</p>
                    </div>
                    <div class="mitigation-banner__meta chief-theme-summary-grid">
                        <div class="mitigation-banner__meta-card">
                            <div class="mitigation-banner__meta-label">Coverage</div>
                            <div class="mitigation-banner__meta-value">{{ $managedBarangays->count() }} barangays</div>
                        </div>
                        <div class="mitigation-banner__meta-card">
                            <div class="mitigation-banner__meta-label">Status</div>
                            <div class="mitigation-banner__meta-value">Active</div>
                        </div>
                        <div class="mitigation-banner__meta-card">
                            <div class="mitigation-banner__meta-label">Open</div>
                            <div class="mitigation-banner__meta-value">{{ $openMitigations->count() }} actions</div>
                        </div>
                        <div class="mitigation-banner__meta-card">
                            <div class="mitigation-banner__meta-label">Priority</div>
                            <div class="mitigation-banner__meta-value">{{ $criticalMitigations->count() }} high / critical</div>
                        </div>
                    </div>
                </section>

                <div class="mitigation-stack">
                    @if(!empty($mitigationDraft))
                        <section class="panel" style="border:1px solid rgba(249,115,22,.18); background:linear-gradient(135deg, rgba(255,247,237,.96), rgba(255,255,255,.98));">
                            <div class="panel-head">
                                <div>
                                    <h3>Draft Ready</h3>
                                    <p>Prefilled from <strong>{{ $mitigationDraft['source_title'] ?? ($mitigationDraft['title'] ?? 'the selected checklist') }}</strong>.</p>
                                </div>
                                <button type="button" class="hero-btn" data-open-create-modal><i class="fa-solid fa-wand-magic-sparkles"></i><span>Open Draft</span></button>
                            </div>
                        </section>
                    @endif

                    <section class="summary-grid">
                        <article class="card"><div class="card-label">Total</div><div class="card-value">{{ $mitigationActions->count() }}</div><div class="card-copy">All actions.</div></article>
                        <article class="card"><div class="card-label">Open</div><div class="card-value">{{ $openMitigations->count() }}</div><div class="card-copy">Needs follow-up.</div></article>
                        <article class="card"><div class="card-label">Done</div><div class="card-value">{{ $completedMitigations->count() }}</div><div class="card-copy">Completed.</div></article>
                        <article class="card"><div class="card-label">Priority</div><div class="card-value">{{ $criticalMitigations->count() }}</div><div class="card-copy">High / critical.</div></article>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h3>Action Register</h3>
                                <p>Station-managed mitigation actions.</p>
                            </div>
                            <button type="button" class="hero-btn" data-open-create-modal><i class="fa-solid fa-plus"></i><span>Add Action</span></button>
                        </div>

                        @if($mitigationActions->isEmpty())
                            <div class="empty">No actions yet.</div>
                        @else
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Barangay</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Target</th>
                                            <th>Source</th>
                                            <th>Proof</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mitigationActions as $action)
                                            @php
                                                $payload = [
                                                    'id' => $action->mitigation_action_id,
                                                    'barangay_id' => $action->barangay_id,
                                                    'title' => $action->title,
                                                    'category' => $action->category,
                                                    'priority_level' => $action->priority_level,
                                                    'status' => $action->status,
                                                    'target_date' => optional($action->target_date)->format('Y-m-d'),
                                                    'description' => $action->description,
                                                    'remarks' => $action->remarks,
                                                    'risk_area_id' => $action->risk_area_id,
                                                    'barangay_prediction_checklist_id' => $action->barangay_prediction_checklist_id,
                                                    'ai_action_plan_checklist_id' => $action->ai_action_plan_checklist_id,
                                                ];
                                            @endphp
                                            <tr>
                                                <td data-label="Action">
                                                    <div class="title">{{ $action->title }}</div>
                                                    <div class="note note--clamp">{{ $action->description }}</div>
                                                    <div class="note">{{ $action->creator?->full_name ?? 'Station command' }}</div>
                                                </td>
                                                <td data-label="Barangay">{{ $action->barangay?->barangay_name ?? 'Unknown barangay' }}</td>
                                                <td data-label="Category">{{ $mitigationCategoryOptions[$action->category] ?? ucfirst(str_replace('_', ' ', $action->category)) }}</td>
                                                <td data-label="Priority"><span class="tag {{ $action->priority_level }}">{{ str_replace('_', ' ', strtoupper($action->priority_level)) }}</span></td>
                                                <td data-label="Status"><span class="tag {{ $action->status }}">{{ str_replace('_', ' ', strtoupper($action->status)) }}</span></td>
                                                <td data-label="Target">{{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</td>
                                                <td data-label="Source" class="source-cell">
                                                    <div class="chip-row">
                                                        @if($action->riskArea)
                                                            <span class="chip"><i class="fa-solid fa-location-dot"></i> {{ $action->riskArea->marker?->title ?? 'Risk' }}</span>
                                                        @endif
                                                        @if($action->checklist)
                                                            <span class="chip"><i class="fa-solid fa-list-check"></i> {{ $action->checklist->title }}</span>
                                                        @endif
                                                        @if($action->aiActionPlanChecklist)
                                                            <span class="chip"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ $action->aiActionPlanChecklist->plan_title }}</span>
                                                        @endif
                                                        @if(! $action->riskArea && ! $action->checklist && ! $action->aiActionPlanChecklist)
                                                            <span class="chip"><i class="fa-solid fa-shield-halved"></i> Direct</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td data-label="Proof" class="proof-cell">
                                                    @if($action->attachments->isEmpty())
                                                        <span class="note">No files</span>
                                                    @else
                                                        <div class="attachments">
                                                            @foreach($action->attachments as $attachment)
                                                                <a href="{{ \App\Support\MediaPath::url($attachment->file_path) }}" target="_blank" rel="noopener">{{ $attachment->file_name ?: 'Attachment' }}</a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td data-label="Actions">
                                                    <div class="row-actions">
                                                        <button type="button" class="row-btn edit" title="Edit action" aria-label="Edit action" data-open-edit-modal data-payload='@json($payload)'><i class="fa-solid fa-pen"></i></button>
                                                        <button type="button" class="row-btn delete" title="Delete action" aria-label="Delete action" data-open-delete-modal data-id="{{ $action->mitigation_action_id }}" data-title="{{ $action->title }}"><i class="fa-solid fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
            </div>
        </main>
    </div>

    <div class="mitigation-dialog" id="createModal" aria-hidden="true">
        <div class="mitigation-dialog__card">
            <div class="mitigation-dialog__head">
                <div><h3>Add Action</h3><p>Create a station action.</p></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('station-admin.mitigations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">@include('chief.partials.mitigation-form-fields')</div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="submit-btn"><i class="fa-solid fa-floppy-disk"></i><span>Save</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mitigation-dialog" id="editModal" aria-hidden="true">
        <div class="mitigation-dialog__card">
            <div class="mitigation-dialog__head">
                <div><h3>Edit Action</h3><p>Update this action.</p></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="editForm" action="{{ route('station-admin.mitigations.update', ['mitigationActionId' => 0]) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-grid">@include('chief.partials.mitigation-form-fields')</div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="submit-btn"><i class="fa-solid fa-floppy-disk"></i><span>Update</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mitigation-dialog" id="deleteModal" aria-hidden="true">
        <div class="mitigation-dialog__card">
            <div class="mitigation-dialog__head">
                <div><h3>Delete Action</h3><p id="deleteMessage">This action and its proof files will be removed.</p></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="deleteForm" action="{{ route('station-admin.mitigations.destroy', ['mitigationActionId' => 0]) }}">
                @csrf
                @method('DELETE')
                <div class="form-actions" style="margin-top:0;">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="hero-btn" style="background:#dc2626; box-shadow:none;"><i class="fa-solid fa-trash"></i><span>Delete</span></button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.user-activity-logger')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const editForm = document.getElementById('editForm');
            const deleteForm = document.getElementById('deleteForm');
            const deleteMessage = document.getElementById('deleteMessage');
            const updateBaseUrl = @json(route('station-admin.mitigations.update', ['mitigationActionId' => '__ID__']));
            const deleteBaseUrl = @json(route('station-admin.mitigations.destroy', ['mitigationActionId' => '__ID__']));
            const menuToggle = document.getElementById('mitigationUserMenuToggle');
            const menu = document.getElementById('mitigationUserMenu');

            const captureOptions = (select) => Array.from(select?.options || []).map((option) => ({
                value: option.value,
                text: option.text,
                barangayId: option.dataset.barangayId || '',
            }));

            const rebuildSelectOptions = (select, originalOptions, barangayId, selectedValue = '') => {
                if (!select) return;

                select.innerHTML = '';

                originalOptions
                    .filter((option) => option.value === '' || option.barangayId === '' || !barangayId || option.barangayId === barangayId)
                    .forEach((option) => {
                        const element = document.createElement('option');
                        element.value = option.value;
                        element.textContent = option.text;
                        if (option.barangayId) {
                            element.dataset.barangayId = option.barangayId;
                        }
                        if (String(option.value) === String(selectedValue)) {
                            element.selected = true;
                        }
                        select.appendChild(element);
                    });
            };

            const wireBarangayScopedSelectors = (form) => {
                if (!form) return;

                const barangaySelect = form.querySelector('[name="barangay_id"]');
                const riskSelect = form.querySelector('[name="risk_area_id"]');
                const checklistSelect = form.querySelector('[name="barangay_prediction_checklist_id"]');
                const aiSelect = form.querySelector('[name="ai_action_plan_checklist_id"]');

                const riskOptions = captureOptions(riskSelect);
                const checklistOptions = captureOptions(checklistSelect);
                const aiOptions = captureOptions(aiSelect);

                const sync = () => {
                    const barangayId = barangaySelect?.value || '';
                    const currentRisk = riskSelect?.value || '';
                    const currentChecklist = checklistSelect?.value || '';
                    const currentAi = aiSelect?.value || '';

                    rebuildSelectOptions(riskSelect, riskOptions, barangayId, currentRisk);
                    rebuildSelectOptions(checklistSelect, checklistOptions, barangayId, currentChecklist);
                    rebuildSelectOptions(aiSelect, aiOptions, barangayId, currentAi);
                };

                barangaySelect?.addEventListener('change', sync);
                sync();
            };

            const closeModal = (modal) => { if (!modal) return; modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); };
            const openModal = (modal) => {
                if (!modal) return;
                const modalCard = modal.querySelector('.mitigation-dialog__card');
                if (modalCard) {
                    modalCard.scrollTop = 0;
                }
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            };

            wireBarangayScopedSelectors(createModal?.querySelector('form'));
            wireBarangayScopedSelectors(editForm);

            document.querySelectorAll('[data-open-create-modal]').forEach((button) => button.addEventListener('click', () => openModal(createModal)));
            document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => { closeModal(createModal); closeModal(editModal); closeModal(deleteModal); }));
            [createModal, editModal, deleteModal].forEach((modal) => modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));

            document.querySelectorAll('[data-open-edit-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const payload = JSON.parse(button.getAttribute('data-payload') || '{}');
                    if (!editForm || !payload.id) return;
                    editForm.action = updateBaseUrl.replace('__ID__', String(payload.id));
                    editForm.querySelector('[name="barangay_id"]').value = payload.barangay_id || '';
                    editForm.querySelector('[name="title"]').value = payload.title || '';
                    editForm.querySelector('[name="category"]').value = payload.category || 'other';
                    editForm.querySelector('[name="priority_level"]').value = payload.priority_level || 'medium';
                    editForm.querySelector('[name="status"]').value = payload.status || 'planned';
                    editForm.querySelector('[name="target_date"]').value = payload.target_date || '';
                    editForm.querySelector('[name="description"]').value = payload.description || '';
                    editForm.querySelector('[name="remarks"]').value = payload.remarks || '';
                    editForm.querySelector('[name="risk_area_id"]').value = payload.risk_area_id || '';
                    editForm.querySelector('[name="barangay_prediction_checklist_id"]').value = payload.barangay_prediction_checklist_id || '';
                    editForm.querySelector('[name="ai_action_plan_checklist_id"]').value = payload.ai_action_plan_checklist_id || '';
                    editForm.querySelector('[name="barangay_id"]').dispatchEvent(new Event('change'));
                    editForm.querySelector('[name="risk_area_id"]').value = payload.risk_area_id || '';
                    editForm.querySelector('[name="barangay_prediction_checklist_id"]').value = payload.barangay_prediction_checklist_id || '';
                    editForm.querySelector('[name="ai_action_plan_checklist_id"]').value = payload.ai_action_plan_checklist_id || '';
                    openModal(editModal);
                });
            });

            document.querySelectorAll('[data-open-delete-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-id');
                    const title = button.getAttribute('data-title') || 'this mitigation action';
                    if (!deleteForm || !id) return;
                    deleteForm.action = deleteBaseUrl.replace('__ID__', String(id));
                    deleteMessage.textContent = `Delete "${title}" and all uploaded proof files? This cannot be undone.`;
                    openModal(deleteModal);
                });
            });

            if (menuToggle && menu) {
                menuToggle.addEventListener('click', () => menu.classList.toggle('show'));
                document.addEventListener('click', (event) => {
                    if (!menuToggle.contains(event.target) && !menu.contains(event.target)) {
                        menu.classList.remove('show');
                    }
                });
            }

            @if($errors->any())
                openModal(createModal);
            @endif

            @if(!empty($autoOpenCreateModal))
                openModal(createModal);
            @endif
        });
    </script>
</body>
</html>
