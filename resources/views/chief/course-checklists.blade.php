<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PyroSense · Chief Course Checklists</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])

    <style>
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Inter, system-ui, sans-serif;
        }

        .chief-checklist-shell {
            min-height: 100vh;
            display: flex;
            background: linear-gradient(180deg, #fff7ed 0%, #f8fafc 22%);
        }

        .chief-checklist-main {
            flex: 1;
            min-width: 0;
            padding: 28px;
            width: 100%;
        }

        .chief-checklist-wrap {
            max-width: 1380px;
            margin: 0 auto;
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            height: 72px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin-bottom: 24px;
            border-radius: 24px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
        }

        .station-badge {
            color: #6b7280;
            font-size: 13px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(226, 232, 240, 0.95);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #1e293b;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s ease;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .chief-checklist-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
            padding: 24px 28px;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(251, 191, 36, 0.15), transparent 28%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 247, 237, 0.9));
            border: 1px solid rgba(251, 191, 36, 0.18);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
        }

        .chief-checklist-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .chief-checklist-title {
            margin: 12px 0 8px;
            font-size: 1.85rem;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .chief-checklist-copy {
            margin: 0;
            color: #475569;
            max-width: 700px;
            line-height: 1.55;
            font-size: 14px;
        }

        .chief-checklist-badge {
            padding: 10px 14px;
            border-radius: 999px;
            background: white;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .chief-checklist-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .chief-summary-card,
        .chief-filter-card,
        .chief-checklist-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 20px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
        }

        .chief-summary-card {
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
        }

        .chief-summary-card::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #f97316, #dc2626);
            opacity: 0.9;
        }

        .chief-summary-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .chief-summary-value {
            margin-top: 10px;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .chief-filter-card {
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .chief-filter-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1.2fr auto;
            gap: 12px;
            align-items: end;
        }

        .chief-field label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .chief-field input,
        .chief-field select,
        .chief-row-notes {
            width: 100%;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            padding: 12px 14px;
            font: inherit;
            color: #0f172a;
            background: #fff;
            box-sizing: border-box;
        }

        .chief-filter-actions {
            display: flex;
            gap: 10px;
        }

        .chief-btn {
            border: none;
            border-radius: 12px;
            padding: 11px 15px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .chief-btn:hover {
            transform: translateY(-1px);
        }

        .chief-btn-primary {
            background: linear-gradient(135deg, #dc2626, #ea580c);
            color: white;
        }

        .chief-btn-soft {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #dbe4f0;
        }

        .chief-checklist-stack {
            display: grid;
            gap: 16px;
        }

        .chief-checklist-card {
            padding: 20px;
        }

        .chief-checklist-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .chief-checklist-title-row {
            margin: 0 0 8px;
            font-size: 1.08rem;
            font-weight: 800;
        }

        .chief-checklist-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .chief-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .chief-chip--barangay {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .chief-chip--priority-critical,
        .chief-chip--status-rejected {
            background: #fef2f2;
            color: #b91c1c;
        }

        .chief-chip--priority-high,
        .chief-chip--status-submitted {
            background: #fff7ed;
            color: #c2410c;
        }

        .chief-chip--priority-medium,
        .chief-chip--status-in_progress {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .chief-chip--priority-low,
        .chief-chip--status-pending {
            background: #f8fafc;
            color: #475569;
        }

        .chief-chip--status-confirmed {
            background: #ecfdf5;
            color: #15803d;
        }

        .chief-checklist-description,
        .chief-checklist-success {
            margin: 0 0 12px;
            color: #475569;
            line-height: 1.6;
        }

        .chief-checklist-grid {
            display: grid;
            grid-template-columns: 1.4fr .95fr;
            gap: 16px;
            margin-top: 16px;
        }

        .chief-checklist-panel {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .chief-panel-title {
            margin: 0 0 12px;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .chief-checklist-items {
            display: grid;
            gap: 10px;
        }

        .chief-checklist-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
        }

        .chief-checklist-item.is-followed {
            background: #ecfdf5;
            border-color: #86efac;
        }

        .chief-checklist-item.is-not-followed {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .chief-checklist-item-copy {
            color: #334155;
            line-height: 1.55;
            font-size: 14px;
        }

        .chief-checklist-item.is-followed .chief-checklist-item-copy {
            color: #166534;
        }

        .chief-checklist-item.is-not-followed .chief-checklist-item-copy {
            color: #991b1b;
        }

        .chief-checklist-item-meta {
            display: grid;
            gap: 8px;
            justify-items: end;
        }

        .chief-checklist-item-state {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            color: #64748b;
        }

        .chief-step-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chief-step-btn {
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .03em;
            cursor: pointer;
        }

        .chief-step-btn--followed {
            background: #dcfce7;
            color: #166534;
        }

        .chief-step-btn--not-followed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .chief-step-btn--reset {
            background: #e2e8f0;
            color: #334155;
        }

        .chief-progress-card {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .chief-progress-bar {
            height: 10px;
            background: #fde7cf;
            border-radius: 999px;
            overflow: hidden;
        }

        .chief-progress-bar > span {
            display: block;
            height: 100%;
            background: linear-gradient(135deg, #f97316, #16a34a);
            border-radius: inherit;
        }

        .chief-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .chief-btn-status {
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12px;
        }

        .chief-btn-progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .chief-btn-submitted {
            background: #ffedd5;
            color: #c2410c;
        }

        .chief-btn-confirm {
            background: #dcfce7;
            color: #15803d;
        }

        .chief-btn-reject {
            background: #fee2e2;
            color: #b91c1c;
        }

        .chief-suggestion-history {
            display: grid;
            gap: 10px;
        }

        .chief-suggestion-card {
            border-radius: 12px;
            padding: 12px 14px;
            background: linear-gradient(180deg, #f8fafc, #ffffff);
            border: 1px solid #e2e8f0;
        }

        .chief-suggestion-date {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .chief-empty {
            padding: 42px 24px;
            text-align: center;
            color: #64748b;
            background: rgba(255,255,255,.88);
            border: 1px dashed #cbd5e1;
            border-radius: 24px;
        }

        .chief-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.52);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 4000;
        }

        .chief-modal.is-open {
            display: flex;
        }

        .chief-modal__card {
            width: min(760px, 100%);
            max-height: 85vh;
            overflow: auto;
            border-radius: 24px;
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.16);
        }

        .chief-modal__header,
        .chief-modal__footer {
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .chief-modal__footer {
            border-bottom: none;
            border-top: 1px solid #e2e8f0;
            justify-content: flex-end;
        }

        .chief-modal__body {
            padding: 22px;
        }

        .chief-close-btn {
            border: none;
            background: #f8fafc;
            color: #475569;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            cursor: pointer;
        }

        @media (max-width: 1180px) {
            .chief-checklist-summary,
            .chief-filter-grid,
            .chief-checklist-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (min-width: 768px) {
            .chief-checklist-main {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }

        @media (max-width: 768px) {
            .lector-top-bar {
                height: auto;
                min-height: 64px;
                padding: 12px 16px;
                margin-bottom: 18px;
            }

            .chief-checklist-main {
                padding: 18px 16px 110px;
                margin-left: 0;
                width: 100%;
            }

            .chief-checklist-topbar,
            .chief-checklist-summary,
            .chief-filter-grid,
            .chief-checklist-grid {
                grid-template-columns: 1fr;
                display: grid;
            }

            .chief-filter-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
    <div class="chief-checklist-shell">
        @include('chief.partials.sidebar', ['active' => 'course-checklists'])

        <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

        <main class="chief-checklist-main">
            <div class="chief-checklist-wrap">
            <nav class="lector-top-bar">
                <div class="flex items-center gap-4">
                    <button id="openSidebar" class="lg:hidden text-slate-600">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Action Plans</h1>
                        <p class="text-xs text-slate-500 font-medium">Barangay compliance review · {{ now()->format('l, F j') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="station-badge hidden sm:inline-flex">Station Command</span>

                    <div class="user-dropdown">
                        <button id="userDropdownBtn" class="user-dropdown-toggle">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                                {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                            </div>
                            <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                        </button>

                        <div id="userDropdownMenu" class="user-dropdown-menu">
                            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                                <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-2">
                                <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item">
                                    <i class="fa-regular fa-user"></i>
                                    <span>Commander Profile</span>
                                </a>
                                <a href="#" class="user-dropdown-item chief-open-logs">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span>My Logs</span>
                                </a>
                                <div class="h-px bg-slate-100 my-2"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                        <i class="fa-solid fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="chief-checklist-topbar">
                <div>
                    <span class="chief-checklist-kicker">
                        <i class="fa-solid fa-list-check"></i>
                        Compliance Review
                    </span>
                    <h1 class="chief-checklist-title">Barangay Action Plans</h1>
                    <p class="chief-checklist-copy">Review submitted actions, verify field compliance, and record the result per step.</p>
                </div>
                <div class="chief-checklist-badge">
                    {{ auth()->user()->first_name ?? 'Chief' }} · Chief
                </div>
            </div>

            <section class="chief-checklist-summary">
                <article class="chief-summary-card">
                    <div class="chief-summary-label">Plans</div>
                    <div class="chief-summary-value">{{ $summary['total'] }}</div>
                </article>
                <article class="chief-summary-card">
                    <div class="chief-summary-label">Open</div>
                    <div class="chief-summary-value">{{ $summary['open'] }}</div>
                </article>
                <article class="chief-summary-card">
                    <div class="chief-summary-label">Submitted</div>
                    <div class="chief-summary-value">{{ $summary['submitted'] }}</div>
                </article>
                <article class="chief-summary-card">
                    <div class="chief-summary-label">Confirmed</div>
                    <div class="chief-summary-value">{{ $summary['confirmed'] }}</div>
                </article>
                <article class="chief-summary-card">
                    <div class="chief-summary-label">Rejected</div>
                    <div class="chief-summary-value">{{ $summary['rejected'] }}</div>
                </article>
            </section>

            <section class="chief-filter-card">
                <form method="GET" action="{{ route('station-admin.course-checklists') }}" class="chief-filter-grid">
                    <div class="chief-field">
                        <label for="barangay_id">Barangay</label>
                        <select name="barangay_id" id="barangay_id">
                            <option value="">All barangays</option>
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay->barangay_id }}" @selected((string) $filters['barangay_id'] === (string) $barangay->barangay_id)>
                                    {{ $barangay->barangay_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="chief-field">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">All statuses</option>
                            @foreach (['pending', 'in_progress', 'submitted', 'confirmed', 'rejected'] as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="chief-field">
                        <label for="priority">Priority</label>
                        <select name="priority" id="priority">
                            <option value="">All priorities</option>
                            @foreach (['critical', 'high', 'medium', 'low'] as $priority)
                                <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="chief-field">
                        <label for="q">Search Plan</label>
                        <input type="text" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Title or source">
                    </div>
                    <div class="chief-filter-actions">
                        <button type="submit" class="chief-btn chief-btn-primary">Apply</button>
                        <a href="{{ route('station-admin.course-checklists') }}" class="chief-btn chief-btn-soft" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Reset</a>
                    </div>
                </form>
            </section>

            @if ($checklists->isEmpty())
                <div class="chief-empty">
                    <h3 style="margin:0 0 10px; font-size:1.1rem; color:#0f172a;">No records found</h3>
                    <p style="margin:0;">Try clearing a filter or run a new analysis first.</p>
                </div>
            @else
                <section class="chief-checklist-stack">
                    @foreach ($checklists as $checklist)
                        @php
                            $priorityClass = 'chief-chip--priority-' . strtolower($checklist->priority_level ?? 'medium');
                            $statusClass = 'chief-chip--status-' . strtolower($checklist->status ?? 'pending');
                        @endphp
                        <article class="chief-checklist-card">
                            <div class="chief-checklist-head">
                                <div>
                                    <h2 class="chief-checklist-title-row">{{ $checklist->plan_title }}</h2>
                                    <div class="chief-checklist-meta">
                                        <span class="chief-chip chief-chip--barangay">
                                            <i class="fa-solid fa-location-dot"></i>
                                            {{ $checklist->barangay->barangay_name ?? 'No barangay linked' }}
                                        </span>
                                        <span class="chief-chip {{ $priorityClass }}">{{ strtoupper($checklist->priority_level) }} PRIORITY</span>
                                        <span class="chief-chip {{ $statusClass }}">{{ strtoupper(str_replace('_', ' ', $checklist->status)) }}</span>
                                        @if ($checklist->target_date)
                                            <span class="chief-chip chief-chip--priority-low">
                                                <i class="fa-regular fa-calendar"></i>
                                                Target {{ $checklist->target_date->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($checklist->plan_description)
                                        <p class="chief-checklist-description">{{ \Illuminate\Support\Str::limit($checklist->plan_description, 180) }}</p>
                                    @endif
                                    @if ($checklist->success_indicator)
                                        <p class="chief-checklist-success"><strong>Completion check:</strong> {{ $checklist->success_indicator }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('station-admin.analytics') }}" class="chief-btn chief-btn-soft" style="text-decoration:none;">
                                    Source
                                </a>
                            </div>

                            <div class="chief-checklist-grid">
                                <div class="chief-checklist-panel">
                                    <h3 class="chief-panel-title">Checklist Steps</h3>
                                    @php
                                        $progress = $checklist->checklist_progress;
                                        $progressPercent = $progress['percent'];
                                    @endphp
                                    <div class="chief-progress-card">
                                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
                                            <div style="font-size:13px; font-weight:800; color:#9a3412;">Step Progress</div>
                                            <div style="font-size:13px; font-weight:800; color:#0f172a;">{{ $progress['followed'] }}/{{ max(1, $progress['total']) }} followed</div>
                                        </div>
                                        <div class="chief-progress-bar">
                                            <span style="width: {{ $progressPercent }}%;"></span>
                                        </div>
                                    </div>
                                    <div class="chief-checklist-items">
                                        @foreach ($checklist->items as $item)
                                            <div class="chief-checklist-item {{ $item->status === 'followed' ? 'is-followed' : ($item->status === 'not_followed' ? 'is-not-followed' : '') }}">
                                                <div class="chief-checklist-item-copy">
                                                    <strong style="display:block; margin-bottom:4px; color:#0f172a;">Step {{ $item->item_order }}</strong>
                                                    {{ $item->item_text }}
                                                </div>
                                                <div class="chief-checklist-item-meta">
                                                    <div class="chief-checklist-item-state">{{ strtoupper(str_replace('_', ' ', $item->status)) }}</div>
                                                    <div class="chief-step-actions">
                                                        <button type="button" class="chief-step-btn chief-step-btn--followed" onclick="updateChecklistItemStatus({{ $checklist->ai_action_plan_checklist_id }}, {{ $item->ai_action_plan_checklist_item_id }}, 'followed')">Followed</button>
                                                        <button type="button" class="chief-step-btn chief-step-btn--not-followed" onclick="updateChecklistItemStatus({{ $checklist->ai_action_plan_checklist_id }}, {{ $item->ai_action_plan_checklist_item_id }}, 'not_followed')">Not Followed</button>
                                                        <button type="button" class="chief-step-btn chief-step-btn--reset" onclick="updateChecklistItemStatus({{ $checklist->ai_action_plan_checklist_id }}, {{ $item->ai_action_plan_checklist_item_id }}, 'pending')">Reset</button>
                                                    </div>
                                                    @if ($item->reviewer)
                                                        <div style="font-size:11px; color:#64748b;">{{ trim(($item->reviewer->first_name ?? '') . ' ' . ($item->reviewer->last_name ?? '')) }}</div>
                                                    @endif
                                                    @if ($item->reviewed_at)
                                                        <div style="font-size:11px; color:#94a3b8;">{{ $item->reviewed_at->format('M d, Y g:i A') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="chief-checklist-panel">
                                    <h3 class="chief-panel-title">Review</h3>
                                    <div style="font-size:13px; color:#64748b; margin-bottom:10px;">
                                        Source: <strong style="color:#0f172a;">{{ $checklist->source_title }}</strong>
                                    </div>
                                    <textarea
                                        id="notes-{{ $checklist->ai_action_plan_checklist_id }}"
                                        class="chief-row-notes"
                                        rows="4"
                                        placeholder="Inspection notes"
                                    >{{ $checklist->confirmation_notes }}</textarea>

                                    <div class="chief-row-actions">
                                        <button type="button" class="chief-btn chief-btn-status chief-btn-progress" onclick="updateChecklistStatus({{ $checklist->ai_action_plan_checklist_id }}, 'in_progress')">In Progress</button>
                                        <button type="button" class="chief-btn chief-btn-status chief-btn-submitted" onclick="updateChecklistStatus({{ $checklist->ai_action_plan_checklist_id }}, 'submitted')">Submitted</button>
                                        <button type="button" class="chief-btn chief-btn-status chief-btn-confirm" onclick="updateChecklistStatus({{ $checklist->ai_action_plan_checklist_id }}, 'confirmed')">Confirm</button>
                                        <button type="button" class="chief-btn chief-btn-status chief-btn-reject" onclick="updateChecklistStatus({{ $checklist->ai_action_plan_checklist_id }}, 'rejected')">Reject</button>
                                        <button type="button" class="chief-btn chief-btn-soft" onclick="openExploreModal({{ $checklist->ai_action_plan_checklist_id }})">Details</button>
                                    </div>

                                    @if ($checklist->confirmer)
                                        <div style="margin-top:12px; color:#64748b; font-size:12px;">
                                            Last reviewed by <strong style="color:#0f172a;">{{ trim(($checklist->confirmer->first_name ?? '') . ' ' . ($checklist->confirmer->last_name ?? '')) }}</strong>
                                            @if ($checklist->confirmed_at)
                                                on {{ $checklist->confirmed_at->format('M d, Y g:i A') }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($checklist->suggestions->isNotEmpty())
                                <div class="chief-checklist-panel" style="margin-top:16px;">
                                    <h3 class="chief-panel-title">Notes History</h3>
                                    <div class="chief-suggestion-history">
                                        @foreach ($checklist->suggestions as $suggestion)
                                            <div class="chief-suggestion-card">
                                                <div class="chief-suggestion-date">{{ optional($suggestion->created_at)->format('M d, Y g:i A') }}</div>
                                                <div style="margin-top:6px; font-weight:700;">{{ $suggestion->recommendation_title }}</div>
                                                @if (!empty($suggestion->estimated_timeline))
                                                    <div style="margin-top:6px; color:#475569; font-size:13px;">Timeline: {{ $suggestion->estimated_timeline }}</div>
                                                @endif
                                                @if (!empty($suggestion->steps))
                                                    <div style="margin-top:8px; color:#334155; font-size:13px;">
                                                        {{ \Illuminate\Support\Str::limit(implode(' ', $suggestion->steps), 220) }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endif
        </main>
    </div>

    <div class="chief-modal" id="exploreModal">
        <div class="chief-modal__card">
            <div class="chief-modal__header">
                <div>
                    <div class="chief-panel-title" style="margin:0;">Action Details</div>
                    <div style="color:#64748b; font-size:13px;">Reference notes for this plan.</div>
                </div>
                <button type="button" class="chief-close-btn" onclick="closeExploreModal()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="chief-modal__body" id="exploreModalBody">
                <div class="chief-empty" style="padding:30px 18px;">
                    Details will appear here.
                </div>
            </div>
            <div class="chief-modal__footer">
                <button type="button" class="chief-btn chief-btn-soft" onclick="closeExploreModal()">Close</button>
            </div>
        </div>
    </div>

    @php
        $checklistMap = $checklists->mapWithKeys(function ($item) {
            return [
                $item->ai_action_plan_checklist_id => [
                    'id' => $item->ai_action_plan_checklist_id,
                    'analysis_id' => $item->analysis_id,
                    'title' => $item->source_title ?: $item->plan_title,
                    'description' => $item->source_description ?: $item->plan_description,
                    'priority' => ucfirst($item->priority_level ?? 'medium'),
                ],
            ];
        });
    @endphp

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const checklistMap = @json($checklistMap);

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openBtn && sidebar && backdrop) {
                openBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                });

                backdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', (event) => {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });
            }

            document.querySelectorAll('.chief-open-logs').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    userDropdownMenu?.classList.remove('show');

                    if (typeof window.openChiefLogsModal === 'function') {
                        window.openChiefLogsModal();
                    }
                });
            });
        });

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function updateChecklistStatus(id, status) {
            const notes = document.getElementById(`notes-${id}`)?.value || '';

            try {
                const response = await fetch(`/station-admin/api/action-plan-checklists/${id}/confirm`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status,
                        confirmation_notes: notes,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                window.location.reload();
            } catch (error) {
                alert(`Failed to update checklist row: ${error.message}`);
            }
        }

        async function updateChecklistItemStatus(id, itemId, status) {
            try {
                const response = await fetch(`/station-admin/api/action-plan-checklists/${id}/items`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        status: status,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                window.location.reload();
            } catch (error) {
                alert(`Failed to update checklist item: ${error.message}`);
                window.location.reload();
            }
        }

        function closeExploreModal() {
            document.getElementById('exploreModal').classList.remove('is-open');
        }

        async function openExploreModal(checklistId) {
            const checklist = checklistMap[String(checklistId)] || checklistMap[checklistId];
            const modal = document.getElementById('exploreModal');
            const body = document.getElementById('exploreModalBody');

            if (!checklist) {
                alert('Checklist context not found.');
                return;
            }

            modal.classList.add('is-open');
            body.innerHTML = `
                <div class="chief-empty" style="padding:30px 18px;">
                    Loading details for <strong>${escapeHtml(checklist.title)}</strong>...
                </div>
            `;

            try {
                const response = await fetch('/station-admin/api/explore-recommendation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        recommendation_title: checklist.title,
                        recommendation_description: checklist.description,
                        priority: checklist.priority,
                        analysis_id: checklist.analysis_id,
                        action_plan_checklist_id: checklist.id,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Unknown error');
                }

                const data = result.data || {};
                let html = `
                    <div style="margin-bottom:16px; padding:14px; border-radius:16px; background:#fff7ed; border:1px solid #fdba74;">
                        <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#9a3412;">Original Checklist Focus</div>
                        <div style="margin-top:6px; font-size:16px; font-weight:800; color:#0f172a;">${escapeHtml(checklist.title)}</div>
                        <div style="margin-top:6px; color:#475569; line-height:1.6;">${escapeHtml(checklist.description || '')}</div>
                    </div>
                `;

                if (Array.isArray(data.steps) && data.steps.length) {
                    html += `<div class="chief-checklist-panel"><h3 class="chief-panel-title">Implementation Steps</h3><ol class="chief-checklist-items">`;
                    data.steps.forEach((step) => {
                        html += `<li>${escapeHtml(step)}</li>`;
                    });
                    html += `</ol></div>`;
                }

                if (Array.isArray(data.resources_needed) && data.resources_needed.length) {
                    html += `<div class="chief-checklist-panel" style="margin-top:14px;"><h3 class="chief-panel-title">Resources Needed</h3><ul class="chief-checklist-items">`;
                    data.resources_needed.forEach((resource) => {
                        html += `<li>${escapeHtml(resource)}</li>`;
                    });
                    html += `</ul></div>`;
                }

                if (data.estimated_timeline) {
                    html += `
                        <div class="chief-checklist-panel" style="margin-top:14px;">
                            <h3 class="chief-panel-title">Estimated Timeline</h3>
                            <div style="color:#334155; line-height:1.6;">${escapeHtml(data.estimated_timeline)}</div>
                        </div>
                    `;
                }

                if (!html.trim()) {
                    html = `<div class="chief-empty" style="padding:30px 18px;">No detailed suggestions were returned.</div>`;
                }

                body.innerHTML = html;
            } catch (error) {
                body.innerHTML = `
                    <div class="chief-empty" style="padding:30px 18px; color:#b91c1c;">
                        Failed to load suggestions.<br>
                        <small>${escapeHtml(error.message)}</small>
                    </div>
                `;
            }
        }
    </script>

    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>
