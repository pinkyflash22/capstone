<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PyroSense · Activity Schedule</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    @php
        $scheduleName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'Chief';
        $scheduleInitials = strtoupper(substr(auth()->user()->first_name ?? 'C', 0, 1) . substr(auth()->user()->last_name ?? 'C', 0, 1));
    @endphp

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 32px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 0 0 20px;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown { position: relative; }

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

        .user-dropdown-toggle:hover { background: #fef3c7; }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
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
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            width: 100%;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .schedule-shell {
            max-width: 1480px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .schedule-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.9fr);
            gap: 20px;
            padding: 28px 30px;
            border-radius: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 44%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .schedule-hero h1 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1;
            color: #0f172a;
            font-weight: 900;
        }

        .schedule-hero p {
            margin: 12px 0 0;
            color: #64748b;
            line-height: 1.65;
            font-size: 0.98rem;
            max-width: 62ch;
        }

        .schedule-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .schedule-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.76);
            color: #9a3412;
            border: 1px solid rgba(245, 158, 11, 0.14);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .schedule-summary-grid {
            display: grid;
            gap: 12px;
        }

        .schedule-summary-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(245, 158, 11, 0.12);
            border-radius: 22px;
            padding: 18px 18px 16px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .schedule-summary-label {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .schedule-summary-value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 1.6rem;
            font-weight: 900;
        }

        .schedule-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.95fr);
            gap: 22px;
        }

        .calendar-card,
        .agenda-card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 30px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .calendar-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 22px 24px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
        }

        .calendar-toolbar-title h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 900;
        }

        .calendar-toolbar-title p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 500;
        }

        .calendar-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .calendar-btn {
            height: 42px;
            min-width: 42px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            color: #334155;
            font-weight: 800;
            font-size: 0.84rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .calendar-btn:hover {
            border-color: rgba(198, 61, 38, 0.22);
            color: #9a3412;
            background: #fff7ed;
        }

        .calendar-btn.primary {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.16);
        }

        .calendar-grid-wrap {
            padding: 20px 22px 24px;
        }

        .calendar-weekdays,
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }

        .calendar-weekday {
            padding: 10px 4px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
        }

        .calendar-grid {
            margin-top: 8px;
        }

        .calendar-day {
            min-height: 128px;
            padding: 12px 12px 10px;
            border-radius: 22px;
            border: 1px solid rgba(226, 232, 240, 0.88);
            background: linear-gradient(180deg, #ffffff 0%, #fffdf9 100%);
            display: flex;
            flex-direction: column;
            gap: 10px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
            border-color: rgba(198, 61, 38, 0.16);
        }

        .calendar-day.muted {
            opacity: 0.5;
            background: #f8fafc;
        }

        .calendar-day.today {
            border-color: rgba(198, 61, 38, 0.34);
            box-shadow: inset 0 0 0 1px rgba(198, 61, 38, 0.12);
        }

        .calendar-day.selected {
            border-color: rgba(198, 61, 38, 0.4);
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            box-shadow: 0 18px 30px rgba(198, 61, 38, 0.09);
        }

        .calendar-day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .calendar-day-number {
            font-size: 1rem;
            font-weight: 900;
            color: #0f172a;
        }

        .calendar-day-tag {
            min-width: 26px;
            height: 26px;
            border-radius: 999px;
            background: #fff1f2;
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 900;
        }

        .calendar-event-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 0;
        }

        .calendar-day-add {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 32px;
            border-radius: 12px;
            border: 1px dashed rgba(203, 213, 225, 1);
            background: rgba(248, 250, 252, 0.9);
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            transition: all 0.2s ease;
        }

        .calendar-day-add:hover {
            color: #9a3412;
            border-color: rgba(198, 61, 38, 0.24);
            background: #fff7ed;
        }

        .calendar-event-pill {
            border-radius: 14px;
            padding: 8px 10px;
            font-size: 0.74rem;
            line-height: 1.35;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .calendar-event-pill.operations { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .calendar-event-pill.drill { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .calendar-event-pill.community { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
        .calendar-event-pill.inspection { background: #ecfeff; color: #0f766e; border-color: #a5f3fc; }
        .calendar-event-pill.command { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .calendar-event-pill.logistics { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }

        .agenda-card {
            display: flex;
            flex-direction: column;
        }

        .agenda-header {
            padding: 24px 24px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
        }

        .agenda-header h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.2rem;
            font-weight: 900;
        }

        .agenda-header p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.86rem;
        }

        .agenda-body {
            padding: 20px 24px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .selected-day-card {
            border-radius: 24px;
            padding: 18px 18px 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
        }

        .selected-day-card .eyebrow {
            color: #c2410c;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
        }

        .selected-day-card h4 {
            margin: 8px 0 0;
            color: #0f172a;
            font-size: 1.45rem;
            font-weight: 900;
        }

        .selected-day-card p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .agenda-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .agenda-event {
            border-radius: 22px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }

        .agenda-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .agenda-time {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .agenda-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .agenda-type.operations { background: #eff6ff; color: #1d4ed8; }
        .agenda-type.drill { background: #fff7ed; color: #c2410c; }
        .agenda-type.community { background: #f5f3ff; color: #6d28d9; }
        .agenda-type.inspection { background: #ecfeff; color: #0f766e; }
        .agenda-type.command { background: #fef3c7; color: #92400e; }
        .agenda-type.logistics { background: #f1f5f9; color: #334155; }

        .agenda-event h5 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
        }

        .agenda-event p {
            margin: 0;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .agenda-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .agenda-action-btn {
            border: 0;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.72rem;
            font-weight: 800;
            cursor: pointer;
        }

        .agenda-action-btn.edit {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .agenda-action-btn.delete {
            background: #fff1f2;
            color: #be123c;
        }

        .agenda-empty {
            border: 1px dashed rgba(226, 232, 240, 1);
            border-radius: 22px;
            padding: 28px 18px;
            text-align: center;
            color: #94a3b8;
            background: #f8fafc;
        }

        .upcoming-card {
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            padding-top: 18px;
        }

        .upcoming-card h4 {
            margin: 0 0 12px;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .upcoming-item {
            display: grid;
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 12px 0;
            border-top: 1px solid rgba(241, 245, 249, 1);
        }

        .upcoming-item:first-of-type { border-top: 0; padding-top: 0; }

        .upcoming-date {
            border-radius: 16px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #c2410c;
            text-align: center;
            padding: 8px 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .upcoming-date strong {
            display: block;
            color: #0f172a;
            font-size: 1rem;
            margin-top: 4px;
            letter-spacing: 0;
        }

        .upcoming-copy strong {
            display: block;
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 800;
        }

        .upcoming-copy p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.55;
        }

        .schedule-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.5);
            z-index: 3600;
            overflow-y: auto;
        }

        .schedule-modal.show {
            display: flex;
        }

        .schedule-modal-card {
            width: min(640px, 100%);
            max-height: min(88vh, 920px);
            background: #ffffff;
            border-radius: 30px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .schedule-modal-head {
            padding: 22px 24px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 18px;
        }

        .schedule-modal-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .schedule-modal-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.86rem;
        }

        .schedule-modal-close {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            color: #64748b;
            cursor: pointer;
        }

        .schedule-modal-body {
            padding: 22px 24px 24px;
            overflow-y: auto;
            flex: 1 1 auto;
        }

        .schedule-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .schedule-form-field {
            display: grid;
            gap: 8px;
        }

        .schedule-form-field.full {
            grid-column: 1 / -1;
        }

        .schedule-form-field label {
            color: #475569;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .schedule-form-field input,
        .schedule-form-field select,
        .schedule-form-field textarea {
            width: 100%;
            border: 1px solid #dbe1ea;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.92rem;
            color: #1e293b;
            background: #ffffff;
            outline: none;
        }

        .schedule-form-field input:focus,
        .schedule-form-field select:focus,
        .schedule-form-field textarea:focus {
            border-color: rgba(198, 61, 38, 0.36);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }

        .schedule-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            position: sticky;
            bottom: 0;
            padding-top: 14px;
            background: linear-gradient(180deg, rgba(255,255,255,0.72) 0%, #ffffff 28%);
        }

        .schedule-submit-btn,
        .schedule-cancel-btn {
            height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
        }

        .schedule-submit-btn {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }

        .schedule-cancel-btn {
            color: #9a3412;
            background: #fff7ed;
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .schedule-delete-btn {
            display: none;
            align-items: center;
            justify-content: center;
            height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            border: 0;
            color: #be123c;
            background: #fff1f2;
            margin-right: auto;
        }

        .schedule-delete-btn.show {
            display: inline-flex;
        }

        #scheduleToast {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            color: #fff;
            padding: 0.85rem 1.1rem;
            border-radius: 0.9rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all 0.25s ease;
            z-index: 6000;
            font-size: 0.95rem;
            font-weight: 600;
        }

        #scheduleToast.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1280px) {
            .schedule-layout,
            .schedule-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .schedule-modal {
                padding: 12px;
                align-items: flex-start;
            }

            .schedule-modal-card {
                width: 100%;
                max-height: calc(100vh - 24px);
                border-radius: 24px;
                margin: 0 auto;
            }

            .schedule-modal-head,
            .schedule-modal-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .calendar-weekdays,
            .calendar-grid {
                gap: 8px;
            }

            .calendar-day {
                min-height: 110px;
                padding: 10px;
            }

            .calendar-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .schedule-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
    @include('chief.partials.sidebar', ['active' => 'schedule'])

    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Event Activities Schedule</h1>
                    <p class="text-xs text-slate-500 font-medium">Monthly operations calendar with clickable activity blocks</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ $scheduleInitials }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-sm font-bold text-slate-800">{{ $scheduleName }}</p>
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

        <div class="schedule-shell">
            <section class="schedule-hero">
                <div>
                    <h1>Coordinate drills, inspections, and command activities in one view.</h1>
                    <p>Use the monthly board to track station events at a glance. Every calendar box is clickable, so commanders can inspect day-level activity without leaving the schedule room.</p>
                    <div class="schedule-badges">
                        <span class="schedule-badge"><i class="fa-solid fa-calendar-check"></i> Live monthly command view</span>
                        <span class="schedule-badge"><i class="fa-solid fa-hand-pointer"></i> Click any day cell for details</span>
                        <span class="schedule-badge"><i class="fa-solid fa-layer-group"></i> Drill, inspection, community, logistics</span>
                    </div>
                </div>

                <div class="schedule-summary-grid">
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Current Window</div>
                        <div class="schedule-summary-value" id="scheduleMonthSummary">{{ now()->format('F Y') }}</div>
                    </div>
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Scheduled Activities</div>
                        <div class="schedule-summary-value" id="scheduleEventCount">0</div>
                    </div>
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Next Highlight</div>
                        <div class="schedule-summary-value" id="scheduleNextHighlight" style="font-size:1.1rem;">Loading...</div>
                    </div>
                </div>
            </section>

            <section class="schedule-layout">
                <div class="calendar-card">
                    <div class="calendar-toolbar">
                        <div class="calendar-toolbar-title">
                            <h2 id="calendarMonthLabel">{{ now()->format('F Y') }}</h2>
                            <p>Tap a day cell to inspect its assigned station activity load.</p>
                        </div>

                        <div class="calendar-toolbar-actions">
                            <button type="button" class="calendar-btn" id="prevMonthBtn"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="calendar-btn primary" id="todayMonthBtn">Today</button>
                            <button type="button" class="calendar-btn" id="nextMonthBtn"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <div class="calendar-grid-wrap">
                        <div class="calendar-weekdays">
                            <div class="calendar-weekday">Sun</div>
                            <div class="calendar-weekday">Mon</div>
                            <div class="calendar-weekday">Tue</div>
                            <div class="calendar-weekday">Wed</div>
                            <div class="calendar-weekday">Thu</div>
                            <div class="calendar-weekday">Fri</div>
                            <div class="calendar-weekday">Sat</div>
                        </div>
                        <div id="chiefScheduleCalendarGrid" class="calendar-grid"></div>
                    </div>
                </div>

                <aside class="agenda-card">
                    <div class="agenda-header">
                        <h3>Day Agenda</h3>
                        <p>Selected day details and the upcoming command queue.</p>
                    </div>

                    <div class="agenda-body">
                        <div class="selected-day-card">
                            <div class="eyebrow">Selected Day</div>
                            <h4 id="selectedDayLabel">{{ now()->format('F d, Y') }}</h4>
                            <p id="selectedDaySummary">Choose a date to inspect activities and assignments.</p>
                            <div class="mt-4">
                                <button type="button" class="calendar-btn primary" id="selectedDayAddBtn">
                                    <i class="fa-solid fa-plus"></i> Add Schedule
                                </button>
                            </div>
                        </div>

                        <div id="selectedDayAgenda" class="agenda-list"></div>

                        <div class="upcoming-card">
                            <h4>Upcoming Activity Queue</h4>
                            <div id="upcomingScheduleList"></div>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <div id="scheduleEventModal" class="schedule-modal" aria-hidden="true">
        <div class="schedule-modal-card" role="dialog" aria-modal="true" aria-labelledby="scheduleModalTitle">
            <div class="schedule-modal-head">
                <div>
                    <h3 id="scheduleModalTitle">Add Activity Schedule</h3>
                    <p id="scheduleModalSubtitle">Create a schedule block for the selected calendar day.</p>
                </div>
                <button type="button" class="schedule-modal-close" id="scheduleModalClose" aria-label="Close schedule modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="schedule-modal-body">
                <form id="scheduleEventForm">
                    <input id="scheduleStationId" name="station_id" type="hidden" value="{{ $stationId }}">
                    <input id="scheduleEventId" name="event_id" type="hidden" value="">
                    <div class="schedule-form-grid">
                        <div class="schedule-form-field full">
                            <label for="scheduleEventTitle">Activity Name</label>
                            <input id="scheduleEventTitle" name="event" type="text" placeholder="Example: High-Rise Rescue Drill" required>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleStationLabel">Station</label>
                            <input id="scheduleStationLabel" type="text" value="{{ $stationName ? $stationName . ' (ID: ' . $stationId . ')' : 'Unassigned station' }}" readonly>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleEventDate">Date</label>
                            <input id="scheduleEventDate" name="event_date" type="date" required>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleEventType">Activity Type</label>
                            <select id="scheduleEventType" name="activity_type">
                                <option value="operations">Operations</option>
                                <option value="drill">Drill</option>
                                <option value="community">Community</option>
                                <option value="inspection">Inspection</option>
                                <option value="command">Command</option>
                                <option value="logistics">Logistics</option>
                            </select>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleStartTime">Start Time</label>
                            <select id="scheduleStartTime" name="start_time" required></select>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleEndTime">End Time</label>
                            <select id="scheduleEndTime" name="end_time" required></select>
                            <small id="scheduleTimeHelp" style="color:#64748b;font-size:0.78rem;">Occupied station hours are automatically disabled.</small>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleStatus">Status</label>
                            <select id="scheduleStatus" name="status">
                                <option value="planned">Planned</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="schedule-form-field">
                            <label for="scheduleBarangay">Barangay / Location</label>
                            <select id="scheduleBarangay" name="barangay">
                                <option value="">Select Urbiztondo barangay</option>
                                @foreach(($urbiztondoBarangays ?? collect()) as $barangayOption)
                                    <option value="{{ $barangayOption->barangay_name }}">{{ $barangayOption->barangay_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="schedule-form-field full">
                            <label for="scheduleNotes">Notes</label>
                            <textarea id="scheduleNotes" name="notes" rows="4" placeholder="Optional notes for commanders and station officers"></textarea>
                        </div>
                    </div>
                    <div class="schedule-modal-actions">
                        <button type="button" class="schedule-delete-btn" id="scheduleDeleteBtn">Delete Schedule</button>
                        <button type="button" class="schedule-cancel-btn" id="scheduleCancelBtn">Cancel</button>
                        <button type="submit" class="schedule-submit-btn" id="scheduleSubmitBtn">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="scheduleToast"></div>

    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    sidebar?.classList.add('open');
                    backdrop?.classList.remove('hidden');
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    sidebar?.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });

                userDropdownMenu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const scheduleStoreUrl = @json(route('station-admin.schedule.events.store'));
            const scheduleUpdateUrlTemplate = @json(route('station-admin.schedule.events.update', ['eventActivity' => '__EVENT__']));
            const scheduleDeleteUrlTemplate = @json(route('station-admin.schedule.events.destroy', ['eventActivity' => '__EVENT__']));
            const seedEvents = @json($scheduleEvents ?? []);
            const monthLabel = document.getElementById('calendarMonthLabel');
            const monthSummary = document.getElementById('scheduleMonthSummary');
            const eventCount = document.getElementById('scheduleEventCount');
            const nextHighlight = document.getElementById('scheduleNextHighlight');
            const grid = document.getElementById('chiefScheduleCalendarGrid');
            const selectedDayLabel = document.getElementById('selectedDayLabel');
            const selectedDaySummary = document.getElementById('selectedDaySummary');
            const selectedDayAgenda = document.getElementById('selectedDayAgenda');
            const upcomingScheduleList = document.getElementById('upcomingScheduleList');
            const selectedDayAddBtn = document.getElementById('selectedDayAddBtn');
            const scheduleModal = document.getElementById('scheduleEventModal');
            const scheduleModalClose = document.getElementById('scheduleModalClose');
            const scheduleCancelBtn = document.getElementById('scheduleCancelBtn');
            const scheduleEventForm = document.getElementById('scheduleEventForm');
            const scheduleEventId = document.getElementById('scheduleEventId');
            const scheduleEventTitle = document.getElementById('scheduleEventTitle');
            const scheduleEventType = document.getElementById('scheduleEventType');
            const scheduleStationId = document.getElementById('scheduleStationId');
            const scheduleEventDate = document.getElementById('scheduleEventDate');
            const scheduleStartTime = document.getElementById('scheduleStartTime');
            const scheduleEndTime = document.getElementById('scheduleEndTime');
            const scheduleTimeHelp = document.getElementById('scheduleTimeHelp');
            const scheduleModalSubtitle = document.getElementById('scheduleModalSubtitle');
            const scheduleModalTitle = document.getElementById('scheduleModalTitle');
            const scheduleSubmitBtn = document.getElementById('scheduleSubmitBtn');
            const scheduleDeleteBtn = document.getElementById('scheduleDeleteBtn');
            const scheduleStatus = document.getElementById('scheduleStatus');
            const scheduleBarangay = document.getElementById('scheduleBarangay');
            const scheduleNotes = document.getElementById('scheduleNotes');
            const scheduleToast = document.getElementById('scheduleToast');
            const stationId = @json($stationId);

            const state = {
                viewDate: new Date(),
                selectedDateKey: formatDateKey(new Date()),
                editingEventId: null
            };

            const hourlySlots = Array.from({ length: 24 }, function (_, hour) {
                return {
                    hour,
                    value: `${String(hour).padStart(2, '0')}:00`,
                    label: new Date(2000, 0, 1, hour).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                    }),
                };
            });

            function showScheduleToast(message, isError = false) {
                if (!scheduleToast) {
                    return;
                }

                scheduleToast.textContent = message;
                scheduleToast.style.background = isError
                    ? 'linear-gradient(135deg, #dc2626 0%, #f97316 100%)'
                    : 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)';
                scheduleToast.classList.add('show');

                window.clearTimeout(showScheduleToast._timer);
                showScheduleToast._timer = window.setTimeout(() => {
                    scheduleToast.classList.remove('show');
                }, 3200);
            }

            function formatDateKey(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function formatLongDate(date) {
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            function formatMonthYear(date) {
                return date.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }

            function getTodayKey() {
                const formatter = new Intl.DateTimeFormat('en-CA', {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                });

                return formatter.format(new Date());
            }

            function getManilaNowParts() {
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                });

                const parts = formatter.formatToParts(new Date());
                const values = Object.fromEntries(parts.map(function (part) {
                    return [part.type, part.value];
                }));

                return {
                    dateKey: `${values.year}-${values.month}-${values.day}`,
                    hour: Number.parseInt(values.hour, 10),
                    minute: Number.parseInt(values.minute, 10),
                };
            }

            function getMinimumStartHour(dateKey) {
                const manilaNow = getManilaNowParts();

                if (dateKey !== manilaNow.dateKey) {
                    return 0;
                }

                return manilaNow.minute > 0 ? manilaNow.hour + 1 : manilaNow.hour;
            }

            function getEventsForDate(dateKey, ignoreEventId = null) {
                return seedEvents
                    .filter(event => ignoreEventId === null || Number(event.event_id) !== Number(ignoreEventId))
                    .filter(event => event.event_date === dateKey)
                    .sort((a, b) => `${a.start_time}`.localeCompare(`${b.start_time}`));
            }

            function toHourNumber(timeValue) {
                if (!timeValue || typeof timeValue !== 'string') {
                    return null;
                }

                const [hour] = timeValue.split(':');
                const parsedHour = Number.parseInt(hour, 10);

                return Number.isNaN(parsedHour) ? null : parsedHour;
            }

            function getOccupiedHours(dateKey, ignoreEventId = null) {
                const occupiedHours = new Set();

                getEventsForDate(dateKey, ignoreEventId).forEach(function (event) {
                    const startHour = toHourNumber(event.start_time);
                    const endHour = toHourNumber(event.end_time);

                    if (startHour === null || endHour === null) {
                        return;
                    }

                    for (let hour = startHour; hour <= endHour; hour += 1) {
                        occupiedHours.add(hour);
                    }
                });

                return occupiedHours;
            }

            function getAvailableStartHours(dateKey) {
                const occupiedHours = getOccupiedHours(dateKey, state.editingEventId);
                const minimumStartHour = getMinimumStartHour(dateKey);

                return hourlySlots.filter(function (slot) {
                    return slot.hour >= minimumStartHour && !occupiedHours.has(slot.hour);
                });
            }

            function getAvailableEndHours(dateKey, startTimeValue) {
                const startHour = toHourNumber(startTimeValue);

                if (startHour === null) {
                    return [];
                }

                const occupiedHours = getOccupiedHours(dateKey, state.editingEventId);
                const availableEnds = [];

                for (let hour = startHour + 1; hour <= 23; hour += 1) {
                    let hasConflict = false;

                    for (let blockedHour = startHour; blockedHour <= hour; blockedHour += 1) {
                        if (occupiedHours.has(blockedHour)) {
                            hasConflict = true;
                            break;
                        }
                    }

                    if (hasConflict) {
                        break;
                    }

                    availableEnds.push({
                        value: `${String(hour).padStart(2, '0')}:00`,
                        label: new Date(2000, 0, 1, hour).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                        }),
                    });
                }

                return availableEnds;
            }

            function populateSelectOptions(selectElement, options, placeholder) {
                if (!selectElement) {
                    return;
                }

                selectElement.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                placeholderOption.disabled = true;
                placeholderOption.selected = true;
                selectElement.appendChild(placeholderOption);

                options.forEach(function (option) {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.label;
                    selectElement.appendChild(optionElement);
                });
            }

            function refreshTimeSelectors(dateKey) {
                const availableStarts = getAvailableStartHours(dateKey);
                const previousStartValue = scheduleStartTime.value;
                const previousEndValue = scheduleEndTime.value;

                populateSelectOptions(scheduleStartTime, availableStarts, availableStarts.length ? 'Select start time' : 'No available start times');
                populateSelectOptions(scheduleEndTime, [], 'Select end time');
                scheduleEndTime.disabled = true;

                if (!availableStarts.length) {
                    scheduleTimeHelp.textContent = dateKey === getTodayKey()
                        ? 'This day has no remaining free hourly slots based on Asia/Manila time.'
                        : 'This day has no free hourly slots for this station.';
                    return;
                }

                scheduleTimeHelp.textContent = dateKey === getTodayKey()
                    ? 'Past and occupied Asia/Manila time slots are automatically disabled.'
                    : 'Occupied station hours are automatically disabled.';

                if (availableStarts.some(option => option.value === previousStartValue)) {
                    scheduleStartTime.value = previousStartValue;
                    refreshEndTimeOptions(dateKey, previousEndValue);
                }
            }

            function refreshEndTimeOptions(dateKey, preferredEndValue = '') {
                const startValue = scheduleStartTime.value;
                const availableEnds = getAvailableEndHours(dateKey, startValue);

                populateSelectOptions(scheduleEndTime, availableEnds, availableEnds.length ? 'Select end time' : 'No available end times');
                scheduleEndTime.disabled = availableEnds.length === 0;

                if (!availableEnds.length) {
                    scheduleTimeHelp.textContent = 'Choose another start time. This range would overlap an existing activity.';
                    return;
                }

                scheduleTimeHelp.textContent = 'Only free hourly ranges for this station can be selected.';

                if (preferredEndValue && availableEnds.some(option => option.value === preferredEndValue)) {
                    scheduleEndTime.value = preferredEndValue;
                }
            }

            function getUpcomingEvents() {
                return [...seedEvents]
                    .sort((a, b) => `${a.event_date} ${a.start_time}`.localeCompare(`${b.event_date} ${b.start_time}`))
                    .slice(0, 5);
            }

            function buildAgendaCard(event) {
                return `
                    <article class="agenda-event">
                        <div class="agenda-meta">
                            <div class="agenda-time">${event.schedule || `${event.start_time} - ${event.end_time}`}</div>
                            <span class="agenda-type ${event.activity_type || 'operations'}">
                                <i class="fa-solid fa-circle"></i>
                                ${event.activity_type || 'operations'}
                            </span>
                        </div>
                        <h5>${event.event}</h5>
                        <p><i class="fa-regular fa-location-dot"></i> ${event.barangay || 'No location set'}</p>
                        ${event.notes ? `<p>${event.notes}</p>` : ''}
                        <div class="agenda-actions">
                            <button type="button" class="agenda-action-btn edit" data-event-edit="${event.event_id}"><i class="fa-solid fa-pen"></i> Edit</button>
                            <button type="button" class="agenda-action-btn delete" data-event-delete="${event.event_id}"><i class="fa-solid fa-trash"></i> Delete</button>
                        </div>
                    </article>
                `;
            }

            function renderSelectedDay() {
                const selectedDate = new Date(`${state.selectedDateKey}T00:00:00`);
                const events = getEventsForDate(state.selectedDateKey);

                selectedDayLabel.textContent = formatLongDate(selectedDate);
                selectedDaySummary.textContent = events.length
                    ? `${events.length} scheduled activit${events.length === 1 ? 'y' : 'ies'} on this day.`
                    : 'No scheduled activities for this day yet.';

                if (!events.length) {
                    selectedDayAgenda.innerHTML = `
                        <div class="agenda-empty">
                            <i class="fa-regular fa-calendar-days text-xl mb-3"></i>
                            <div class="text-sm font-semibold text-slate-600">Open day</div>
                            <div class="text-xs mt-1">This calendar box is clickable and currently has no assigned activity.</div>
                        </div>
                    `;
                    return;
                }

                selectedDayAgenda.innerHTML = events.map(buildAgendaCard).join('');
            }

            function renderUpcoming() {
                const upcoming = getUpcomingEvents();

                upcomingScheduleList.innerHTML = upcoming.map(event => {
                    const date = new Date(`${event.event_date}T00:00:00`);
                    return `
                        <div class="upcoming-item">
                            <div class="upcoming-date">
                                ${date.toLocaleDateString('en-US', { month: 'short' })}
                                <strong>${date.getDate()}</strong>
                            </div>
                            <div class="upcoming-copy">
                                <strong>${event.event}</strong>
                                <p>${event.schedule || `${event.start_time} - ${event.end_time}`} · ${event.barangay || 'No location set'}</p>
                            </div>
                        </div>
                    `;
                }).join('');

                eventCount.textContent = String(seedEvents.length);

                const next = upcoming[0];
                nextHighlight.textContent = next
                    ? `${new Date(`${next.event_date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} · ${next.event}`
                    : 'No events';
            }

            function renderCalendar() {
                const year = state.viewDate.getFullYear();
                const month = state.viewDate.getMonth();
                const firstOfMonth = new Date(year, month, 1);
                const lastOfMonth = new Date(year, month + 1, 0);
                const firstCell = new Date(firstOfMonth);
                firstCell.setDate(firstCell.getDate() - firstOfMonth.getDay());
                const todayKey = formatDateKey(new Date());

                monthLabel.textContent = formatMonthYear(firstOfMonth);
                monthSummary.textContent = formatMonthYear(firstOfMonth);
                grid.innerHTML = '';

                for (let i = 0; i < 42; i += 1) {
                    const cellDate = new Date(firstCell);
                    cellDate.setDate(firstCell.getDate() + i);
                    const dateKey = formatDateKey(cellDate);
                    const events = getEventsForDate(dateKey);
                    const isCurrentMonth = cellDate.getMonth() === month;
                    const isToday = dateKey === todayKey;
                    const isSelected = dateKey === state.selectedDateKey;

                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = `calendar-day${isCurrentMonth ? '' : ' muted'}${isToday ? ' today' : ''}${isSelected ? ' selected' : ''}`;
                    cell.dataset.date = dateKey;
                    cell.innerHTML = `
                        <div class="calendar-day-head">
                            <span class="calendar-day-number">${cellDate.getDate()}</span>
                            ${events.length ? `<span class="calendar-day-tag">${events.length}</span>` : ''}
                        </div>
                        <div class="calendar-event-stack">
                            ${events.slice(0, 2).map(event => `
                                <div class="calendar-event-pill ${event.activity_type || 'operations'}">
                                    <div>${event.schedule || `${event.start_time} - ${event.end_time}`}</div>
                                    <div>${event.event}</div>
                                </div>
                            `).join('')}
                            <button type="button" class="calendar-day-add" data-add-date="${dateKey}">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                    `;

                    cell.addEventListener('click', function () {
                        state.selectedDateKey = dateKey;
                        renderCalendar();
                        renderSelectedDay();
                    });

                    cell.querySelector('[data-add-date]')?.addEventListener('click', function (event) {
                        event.stopPropagation();
                        openScheduleModal(dateKey);
                    });

                    grid.appendChild(cell);
                }
            }

            function getEventById(eventId) {
                return seedEvents.find(function (event) {
                    return Number(event.event_id) === Number(eventId);
                }) || null;
            }

            function replaceEventInSeed(updatedEvent) {
                const targetIndex = seedEvents.findIndex(function (event) {
                    return Number(event.event_id) === Number(updatedEvent.event_id);
                });

                if (targetIndex >= 0) {
                    seedEvents[targetIndex] = updatedEvent;
                } else {
                    seedEvents.push(updatedEvent);
                }
            }

            function removeEventFromSeed(eventId) {
                const targetIndex = seedEvents.findIndex(function (event) {
                    return Number(event.event_id) === Number(eventId);
                });

                if (targetIndex >= 0) {
                    seedEvents.splice(targetIndex, 1);
                }
            }

            function openScheduleModal(dateKey, eventData = null) {
                if (dateKey < getTodayKey()) {
                    showScheduleToast('You can only add activities for today or a future date.', true);
                    return;
                }

                state.editingEventId = eventData ? Number(eventData.event_id) : null;
                scheduleEventId.value = eventData?.event_id || '';
                scheduleEventTitle.value = eventData?.event || '';
                scheduleEventType.value = eventData?.activity_type || 'operations';
                scheduleStatus.value = eventData?.status || 'planned';
                scheduleBarangay.value = eventData?.barangay || '';
                scheduleNotes.value = eventData?.notes || '';
                scheduleEventDate.value = dateKey;
                scheduleEventDate.min = getTodayKey();
                scheduleStationId.value = stationId ?? '';
                scheduleModalTitle.textContent = eventData ? 'Edit Activity Schedule' : 'Add Activity Schedule';
                scheduleModalSubtitle.textContent = eventData
                    ? `Update the schedule block for ${formatLongDate(new Date(`${dateKey}T00:00:00`))}.`
                    : `Create a schedule block for ${formatLongDate(new Date(`${dateKey}T00:00:00`))}.`;
                scheduleSubmitBtn.textContent = eventData ? 'Update Schedule' : 'Save Schedule';
                scheduleDeleteBtn.classList.toggle('show', Boolean(eventData));
                refreshTimeSelectors(dateKey);
                if (eventData?.start_time) {
                    scheduleStartTime.value = eventData.start_time;
                    refreshEndTimeOptions(dateKey, eventData.end_time || '');
                }
                scheduleModal.classList.add('show');
                scheduleModal.setAttribute('aria-hidden', 'false');
            }

            function closeScheduleModal() {
                scheduleModal.classList.remove('show');
                scheduleModal.setAttribute('aria-hidden', 'true');
                scheduleEventForm.reset();
                scheduleEventId.value = '';
                scheduleEventTitle.value = '';
                scheduleEventType.value = 'operations';
                scheduleStatus.value = 'planned';
                scheduleBarangay.value = '';
                scheduleNotes.value = '';
                scheduleModalTitle.textContent = 'Add Activity Schedule';
                scheduleSubmitBtn.textContent = 'Save Schedule';
                scheduleDeleteBtn.classList.remove('show');
                state.editingEventId = null;
                scheduleStationId.value = stationId ?? '';
                scheduleEventDate.value = state.selectedDateKey;
                scheduleEventDate.min = getTodayKey();
                refreshTimeSelectors(state.selectedDateKey);
            }

            document.getElementById('prevMonthBtn')?.addEventListener('click', function () {
                state.viewDate = new Date(state.viewDate.getFullYear(), state.viewDate.getMonth() - 1, 1);
                renderCalendar();
            });

            document.getElementById('nextMonthBtn')?.addEventListener('click', function () {
                state.viewDate = new Date(state.viewDate.getFullYear(), state.viewDate.getMonth() + 1, 1);
                renderCalendar();
            });

            document.getElementById('todayMonthBtn')?.addEventListener('click', function () {
                const today = new Date();
                state.viewDate = new Date(today.getFullYear(), today.getMonth(), 1);
                state.selectedDateKey = formatDateKey(today);
                renderCalendar();
                renderSelectedDay();
            });

            selectedDayAddBtn?.addEventListener('click', function () {
                openScheduleModal(state.selectedDateKey);
            });

            selectedDayAgenda?.addEventListener('click', function (event) {
                const editButton = event.target.closest('[data-event-edit]');
                const deleteButton = event.target.closest('[data-event-delete]');

                if (editButton) {
                    const eventData = getEventById(editButton.dataset.eventEdit);
                    if (eventData) {
                        openScheduleModal(eventData.event_date, eventData);
                    }
                }

                if (deleteButton) {
                    deleteScheduleEvent(deleteButton.dataset.eventDelete);
                }
            });

            scheduleModalClose?.addEventListener('click', closeScheduleModal);
            scheduleCancelBtn?.addEventListener('click', closeScheduleModal);
            scheduleEventDate?.addEventListener('change', function () {
                refreshTimeSelectors(scheduleEventDate.value || state.selectedDateKey);
            });
            scheduleStartTime?.addEventListener('change', function () {
                refreshEndTimeOptions(scheduleEventDate.value || state.selectedDateKey);
            });
            scheduleDeleteBtn?.addEventListener('click', function () {
                if (scheduleEventId.value) {
                    deleteScheduleEvent(scheduleEventId.value, true);
                }
            });
            scheduleModal?.addEventListener('click', function (event) {
                if (event.target === scheduleModal) {
                    closeScheduleModal();
                }
            });

            async function deleteScheduleEvent(eventId, closeModalAfter = false) {
                const eventData = getEventById(eventId);

                if (!eventData) {
                    showScheduleToast('Schedule event not found.', true);
                    return;
                }

                if (!window.confirm(`Delete "${eventData.event}" from the schedule?`)) {
                    return;
                }

                try {
                    const response = await fetch(scheduleDeleteUrlTemplate.replace('__EVENT__', String(eventId)), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to delete schedule event.');
                    }

                    removeEventFromSeed(eventId);
                    renderUpcoming();
                    renderCalendar();
                    renderSelectedDay();
                    if (closeModalAfter) {
                        closeScheduleModal();
                    }
                    showScheduleToast('Schedule deleted successfully.');
                } catch (error) {
                    showScheduleToast(error.message || 'Unable to delete schedule.', true);
                }
            }

            scheduleEventForm?.addEventListener('submit', async function (event) {
                event.preventDefault();

                const formData = new FormData(scheduleEventForm);
                const payload = Object.fromEntries(formData.entries());
                const isEditing = Boolean(scheduleEventId.value);

                scheduleSubmitBtn.disabled = true;
                scheduleSubmitBtn.textContent = 'Saving...';

                try {
                    const response = await fetch(
                        isEditing
                            ? scheduleUpdateUrlTemplate.replace('__EVENT__', String(scheduleEventId.value))
                            : scheduleStoreUrl,
                        {
                        method: isEditing ? 'PUT' : 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to save schedule event.');
                    }

                    replaceEventInSeed(data.data);
                    state.selectedDateKey = data.data.event_date;
                    state.viewDate = new Date(`${data.data.event_date}T00:00:00`);
                    renderUpcoming();
                    renderCalendar();
                    renderSelectedDay();
                    closeScheduleModal();
                    showScheduleToast(isEditing ? 'Schedule updated successfully.' : 'Schedule saved successfully.');
                } catch (error) {
                    showScheduleToast(error.message || 'Unable to save schedule.', true);
                } finally {
                    scheduleSubmitBtn.disabled = false;
                    scheduleSubmitBtn.textContent = scheduleEventId.value ? 'Update Schedule' : 'Save Schedule';
                }
            });

            renderUpcoming();
            renderCalendar();
            renderSelectedDay();
            scheduleEventDate.value = state.selectedDateKey;
            scheduleEventDate.min = getTodayKey();
            scheduleStationId.value = stationId ?? '';
            refreshTimeSelectors(state.selectedDateKey);
        });
    </script>
</body>
</html>
