<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'citizen' }}">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? 5 }}">
    <meta name="station-id" content="{{ $stationperson->first()?->station_id ?? '' }}">

    <title>PyroSense · Chief Command Dashboard</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

    @php
        $pyroVoiceStationId = optional($stationperson->first())->station_id;
    @endphp
    @include('partials.voice-call-config')
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/app.css', 'resources/js/map.js'])
    @vite(['resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])

    <style>
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .fire-truck-marker { transition: all 0.1s linear; z-index: 1000; }

        .custom-fa-icon i,
        .leaflet-div-icon i,
        .leaflet-marker-icon i {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            font-style: normal !important;
            line-height: 1 !important;
            display: inline-block;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #map {
            height: 520px;
            min-height: 520px;
            width: 100%;
            background: #f8fafc;
        }

        .dashboard-container {
            padding: 28px;
        }

        #markerModal.modal,
        #iconModal.modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.5);
            z-index: 3600;
        }

        #markerModal .modal-content,
        #iconModal .modal-content {
            width: min(760px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.18);
            padding: 26px;
        }

        #markerModal h3,
        #iconModal h3 {
            margin: 0 0 18px;
            color: #1e293b;
            font-size: 1.15rem;
            font-weight: 800;
        }

        .station-fields {
            display: none;
            margin-top: 18px;
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fff8f1 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
        }

        .station-fields.show {
            display: block;
        }

        .marker-subnote {
            margin-top: 8px;
            color: #64748b;
            font-size: 0.75rem;
            line-height: 1.45;
        }

        .marker-helper-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(198, 61, 38, 0.08);
            color: #9a3412;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .station-fields h4 {
            margin: 0 0 14px;
            color: #9a3412;
            font-size: 0.96rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        .form-row label {
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row input,
        .form-row textarea,
        .form-row select,
        #markerModal input,
        #markerModal textarea,
        #markerModal select,
        #iconModal input,
        #iconModal textarea,
        #iconModal select {
            width: 100%;
            border: 1px solid #dbe1ea;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.92rem;
            color: #1e293b;
            background: #ffffff;
            outline: none;
        }

        .form-row input:focus,
        .form-row textarea:focus,
        .form-row select:focus,
        #markerModal input:focus,
        #markerModal textarea:focus,
        #markerModal select:focus,
        #iconModal input:focus {
            border-color: rgba(198, 61, 38, 0.36);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 22px;
        }

        .btn-save,
        .btn-cancel {
            height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            border: 0;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-save {
            color: #ffffff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }

        .btn-cancel {
            color: #9a3412;
            background: #fff7f3;
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .station-code-preview {
            margin-top: 8px;
            padding: 12px 14px;
            border-radius: 14px;
            color: #ffffff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            font-size: 0.82rem;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .kpi-icon,
        .kpi-icon i {
            color: #ffffff !important;
        }

        .dashboard-welcome-toast {
            position: fixed;
            top: 92px;
            right: 28px;
            z-index: 180;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            max-width: 420px;
            padding: 14px 16px;
            border-radius: 22px;
            background: rgba(255, 251, 235, 0.98);
            border: 1px solid rgba(245, 158, 11, 0.18);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.24s ease, transform 0.24s ease;
        }

        .dashboard-welcome-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .dashboard-welcome-toast__icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #ffffff;
            flex-shrink: 0;
        }

        .dashboard-welcome-toast__copy {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .dashboard-welcome-toast__copy strong {
            color: #9a3412;
            font-size: 0.92rem;
            line-height: 1.2;
            font-weight: 800;
        }

        .dashboard-welcome-toast__copy span {
            color: #b45309;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .chief-dashboard-welcome,
        .dashboard-hidden-panel {
            display: none !important;
        }

        .lector-top-bar {
            border-radius: 28px;
        }

        .user-dropdown-toggle {
            border-radius: 999px;
        }

        .dashboard-container .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .dashboard-container .kpi-grid > * {
            min-width: 0;
        }

        .dashboard-container .kpi-card {
            min-height: 92px;
            min-width: 0;
            padding: 14px 16px !important;
            gap: 12px;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 18px;
        }

        .dashboard-container .kpi-info h3 {
            font-size: 11px;
            letter-spacing: 0.08em;
        }

        .dashboard-container .kpi-info p {
            font-size: 20px;
            line-height: 1.05;
        }

        @media (max-width: 1180px) {
            .dashboard-container .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .dashboard-container .kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        @keyframes chiefLogShimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .chief-log-skeleton {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: rgba(248, 250, 252, 0.92);
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .chief-log-skeleton-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .chief-log-skeleton-avatar,
        .chief-log-skeleton-line {
            background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: chiefLogShimmer 1.4s ease-in-out infinite;
        }

        .chief-log-skeleton-avatar {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            flex-shrink: 0;
        }

        .chief-log-skeleton-content {
            flex: 1;
            min-width: 0;
        }

        .chief-log-skeleton-line {
            height: 0.78rem;
            border-radius: 999px;
            margin-bottom: 0.7rem;
        }

        .chief-log-skeleton-line:last-child {
            margin-bottom: 0;
        }

        .chief-log-skeleton-line.w-90 { width: 90%; }
        .chief-log-skeleton-line.w-75 { width: 75%; }
        .chief-log-skeleton-line.w-60 { width: 60%; }
        .chief-log-skeleton-line.w-45 { width: 45%; }
    </style>
</head>
<body class="chief-dashboard-page">
    @include('chief.partials.sidebar', ['active' => 'dashboard'])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP NAVIGATIONBAR -->
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Commander's Dashboard</h1>
                    <p class="text-xs text-slate-500 font-medium">Strategic Overview. {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Notification Bell -->
                <div class="hidden md:block relative chief-alerts-host">
                    <button id="notificationBtn" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition relative" aria-controls="alertsDropdown" aria-expanded="false" aria-label="Open incident notifications">
                        <i class="fa-regular fa-bell"></i>
                        @if($alerts->count() > 0)
                        <span id="alertBadge" class="absolute top-2 right-2 w-2 h-2 bg-amber-500 rounded-full"></span>
                        @endif
                    </button>
                    <div id="alertsDropdown" class="hidden absolute right-0 mt-3 w-[24rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl z-[5200] overflow-hidden" aria-hidden="true">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50">
                            <div>
                                <p class="text-sm font-bold text-slate-800">Incident Notifications</p>
                                <p class="text-[11px] text-slate-500">Live alerts from field reports</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="markAllAsRead()" class="text-[11px] font-semibold text-amber-700 hover:text-amber-800">
                                    Mark all as read
                                </button>
                                <button type="button" id="alertsDropdownClose" class="chief-alerts-mobile-close" aria-label="Close incident notifications">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div id="alertsDropdownList" class="max-h-[24rem] overflow-y-auto divide-y divide-slate-100">
                            <div class="px-4 py-6 text-sm text-slate-500 text-center">
                                No live incident notifications yet.
                            </div>
                        </div>
                    </div>
                    <div id="chiefNotificationBackdrop" class="chief-notification-backdrop" hidden></div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="hidden md:block user-dropdown">
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
                            <a href="#" class="user-dropdown-item" onclick="openChiefLogsModal(); return false;">
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

        <!-- DASHBOARD CONTENT -->
        <div class="dashboard-container chief-dashboard-container">
            <div id="chiefMobileWelcomeToast" class="dashboard-welcome-toast" role="status" aria-live="polite">
                <div class="dashboard-welcome-toast__icon">
                    <i class="fa-solid fa-signal"></i>
                </div>
                <div class="dashboard-welcome-toast__copy">
                    <strong>Welcome back, {{ auth()->user()->first_name }}!</strong>
                    <span>Chief Command is ready. Recent system insights are loaded below.</span>
                </div>
            </div>

            <div id="chiefMobileInsightsPanel" class="chief-mobile-insights-panel md:hidden" aria-hidden="true">
                <div class="chief-mobile-insights-panel__backdrop" data-chief-insights-close></div>
                <div class="chief-mobile-insights-panel__sheet">
                    <div class="chief-mobile-insights-panel__header">
                        <div>
                            <p class="chief-mobile-insights-panel__eyebrow">Chief Command</p>
                            <h2 class="chief-mobile-insights-panel__title">Cards and Charts</h2>
                        </div>
                        <button type="button" class="chief-mobile-insights-panel__close" data-chief-insights-close aria-label="Close cards and charts">
                            <span class="material-symbols-rounded chief-mobile-material-icon" aria-hidden="true">close</span>
                        </button>
                    </div>
                    <div id="chiefMobileInsightsContent" class="chief-mobile-insights-panel__content"></div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="chief-dashboard-welcome bg-amber-50 border-l-4 border-amber-500 rounded-2xl p-8 mb-8 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-amber-900 mb-2">Welcome back, {{ auth()->user()->first_name }}!</h1>
                    <p class="text-amber-700 font-medium">Station Command Overview. {{ now()->format('l, F j, Y') }}</p>
                </div>
                <div class="hidden md:flex items-center gap-4 bg-white/50 p-2 rounded-xl border border-amber-100">
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Command Status</p>
                        <p class="text-xs font-bold text-green-600">OPERATIONAL</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                        <i class="fa-solid fa-signal"></i>
                    </div>
                </div>
            </div>

            <!-- KPI Grid -->
            <div class="kpi-grid" data-chief-insights-source>
                <div class="lector-card kpi-card">
                    <div class="kpi-icon bg-white-100 text-white-600">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Total Incidents</h3>
                        <p id="chiefIncidentTotal">{{ $dashboardStatsSeed['incident_total'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon bg-white-100 text-white-600">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Active Responses</h3>
                        <p id="chiefActiveResponses">{{ $dashboardStatsSeed['active_responses'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon bg-white-100 text-white-600">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>FSIC Claims</h3>
                        <p id="chiefFsicClaims">{{ $dashboardStatsSeed['fsic_claims'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon bg-white-100 text-white-600">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Personnel Online</h3>
                        <p id="chiefCrewOnline">{{ $dashboardStatsSeed['crew_online'] ?? 0 }}</p>
                    </div>
                </div>
        </div>

        @php
            $analysisTrendSeries = collect($analysisTrendSeries ?? $recentAnalyses ?? [])
                ->sortBy('created_at')
                ->values()
                ->map(function ($analysis) {
                    if (is_array($analysis) && array_key_exists('label', $analysis)) {
                        return [
                            'label' => (string) ($analysis['label'] ?? ''),
                            'incident_count' => (int) ($analysis['incident_count'] ?? 0),
                            'predicted_fire_incidents' => (int) ($analysis['predicted_fire_incidents'] ?? 0),
                            'confidence' => (int) ($analysis['confidence'] ?? 0),
                            'risk_level' => (string) ($analysis['risk_level'] ?? 'Unknown'),
                        ];
                    }

                    return [
                        'label' => optional(\Carbon\Carbon::parse($analysis->created_at))->format('M d'),
                        'incident_count' => (int) ($analysis->incident_count ?? 0),
                        'predicted_fire_incidents' => (int) (data_get($analysis->predictions ?? [], 'predicted_fire_incidents') ?? 0),
                        'confidence' => (int) ($analysis->confidence_score ?? 0),
                        'risk_level' => (string) ($analysis->risk_level ?? 'Unknown'),
                    ];
                })
                ->values();
        @endphp

        <div id="chiefLogsModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-center justify-center z-[5000] p-4">
            <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
                    <div>
                        <h2 id="chiefLogsTitle" class="text-lg font-bold text-slate-800">My Logs</h2>
                        <p id="chiefLogsSubtitle" class="text-sm text-slate-500">Your recorded activity in the system.</p>
                    </div>
                    <button type="button" id="closeChiefLogsModal" class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <p id="chiefLogsSummary" class="text-sm text-slate-500">Loading activity logs...</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="showMyChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 text-sm font-semibold">
                            <i class="fa-solid fa-user"></i>
                            My Activity
                        </button>
                        <button type="button" id="showAllChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 text-sm font-semibold">
                            <i class="fa-solid fa-users"></i>
                            Check All User Activities
                        </button>
                        <button type="button" id="refreshChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-50 text-orange-700 border border-orange-100 hover:bg-orange-100 text-sm font-semibold">
                            <i class="fa-solid fa-rotate"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div id="chiefLogsContainer" class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-4 bg-white">
                    <div class="chief-log-skeleton">
                        <div class="chief-log-skeleton-row">
                            <div class="chief-log-skeleton-avatar"></div>
                            <div class="chief-log-skeleton-content">
                                <div class="chief-log-skeleton-line w-60"></div>
                                <div class="chief-log-skeleton-line w-45"></div>
                                <div class="chief-log-skeleton-line w-90"></div>
                                <div class="chief-log-skeleton-line w-75"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="lector-card mb-8 chief-dashboard-map-card">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-100">
                            <i class="fa-solid fa-map-marked-alt"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Operational Command Map</h2>
                            <p class="text-xs text-slate-500 font-medium">Global fleet and incident tracking</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 bg-amber-100 text-amber-600 text-[10px] font-bold rounded-full uppercase tracking-wider border border-amber-200" onclick="refreshMap()">
                            <i class="fa-solid fa-sync-alt"></i> Sync
                        </button>
                    </div>
                </div>
                <div id="map" class="rounded-2xl border border-slate-200 overflow-hidden"></div>
                <div id="chiefMapLegend" class="chief-map-legend ff-clean-map-legend"></div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid" data-chief-insights-source>
                <!-- Incident History Graph -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <span class="chart-title-icon">
                                <i class="fa-solid fa-chart-line"></i>
                            </span>
                            <h3>Incident History</h3>
                        </div>
                        <div class="chart-controls">
                            <button class="chart-period-btn active" data-period="week">Week</button>
                            <button class="chart-period-btn" data-period="month">Month</button>
                            <button class="chart-period-btn" data-period="year">Year</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="incidentChart"></canvas>
                    </div>
                </div>

                <!-- ML Trend Analysis -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <span class="chart-title-icon">
                                <i class="fa-solid fa-brain"></i>
                            </span>
                            <h3>ML Trend Analysis</h3>
                        </div>
                        <span class="chart-total-pill">{{ $analysisTrendRunCount }} saved model run(s)</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="analysisTrendChart"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Latest Risk</div>
                            <div class="mt-1 text-base font-bold text-slate-800">{{ $analysisTrendSeries->last()['risk_level'] ?? 'No data' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Latest Confidence</div>
                            <div class="mt-1 text-base font-bold text-slate-800">{{ $analysisTrendSeries->last()['confidence'] ?? 0 }}%</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Forecast Runs</div>
                            <div class="mt-1 text-base font-bold text-slate-800">{{ $analysisTrendSeries->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row of Charts -->
            <div class="charts-grid dashboard-hidden-panel" data-chief-insights-source>
                <!-- Incident Status Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <span class="chart-title-icon">
                                <i class="fa-solid fa-check-circle"></i>
                            </span>
                            <h3>Incident Status</h3>
                        </div>
                    </div>
                    
                    @php
                        $reportedCount = $incidents->where('status', 'Reported')->count();
                        $respondingCount = $incidents->where('status', 'Responding')->count();
                        $activeCount = $incidents->where('status', 'On Scene')->count();
                        $resolvedCount = $incidents->whereIn('status', ['Controlled', 'Closed'])->count();
                        
                        $totalActive = $reportedCount + $respondingCount + $activeCount;
                    @endphp
                    
                    <div class="chart-container chart-container-compact">
                        <canvas id="statusChart"></canvas>
                    </div>
                    
                    <div class="chart-stat-grid">
                        <div class="chart-stat-card">
                            <span class="chart-stat-label">Active</span>
                            <p class="chart-stat-value is-active">{{ $totalActive }}</p>
                        </div>
                        <div class="chart-stat-card">
                            <span class="chart-stat-label">Resolved</span>
                            <p class="chart-stat-value is-resolved">{{ $resolvedCount }}</p>
                        </div>
                    </div>
                </div>

                <!-- Hourly Incident Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <span class="chart-title-icon">
                                <i class="fa-solid fa-clock"></i>
                            </span>
                            <h3>Peak Hours</h3>
                        </div>
                    </div>
                    
                    @php
                        $hourlyData = array_fill(0, 24, 0);
                        $i = 0;
                        foreach($incidents as $incident) {
                            $hour = \Carbon\Carbon::parse($incident->created_at)->hour;
                            $hourlyData[$hour] = ($hourlyData[$hour] ?? 0) + 1;
                            // $hourlyData[$i] = ($hour ?? 0);
                            // $i++;
                        }
                    @endphp
                    
                    <div class="chart-container chart-container-compact">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                    
                    <div class="chart-axis-labels">
                        <span>00:00</span>
                        <span>06:00</span>
                        <span>12:00</span>
                        <span>18:00</span>
                        <span>23:00</span>
                    </div>
                </div>
            </div>

            <!-- bottom grid: station status -->
            <div class="bottom-grid dashboard-hidden-panel" data-chief-insights-source>
                <!-- station readiness -->
                <div class="station-card">
                    <div class="station-header">
                        <i class="fa-solid fa-user"></i>
                        <h3>Stations Readiness</h3>
                    </div>
                    <div class="station-grid">
                        @foreach ($stationReadiness as $station)
                        <div class="station-item">
                            <div class="name">{{ $station['station_name'] }}</div>
                            <div class="stats">{{ $station['firefighter_count'] }} / {{ $station['total_personnel_count'] }} <small>personnel</small></div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Personnel Availability -->
                <div class="station-card">
                    <div class="station-header">
                        <i class="fa-solid fa-users"></i>
                        <h3>Personnel Availability</h3>
                    </div>
                    
                    <div class="space-y-3 mt-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Available</span>
                                <span class="font-medium">{{ $stationperson->where('status', 'available')->count() ?? 0 }} (
                                        {{  ($stationperson->where('status', 'available')->count() / ($stationperson->count() > 0 ? $stationperson ->count() : 1) ) * 100 }} %
                                    )</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{  ($stationperson->where('status', 'available')->count() / ($stationperson->count() > 0 ? $stationperson ->count() : 1)  ) * 100 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Responding</span>
                                <span class="font-medium">0 (0%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-orange-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Off Duty</span>
                                <span class="font-medium">0 (0%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gray-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-blue-700">Total Personnel</span>
                            <span class="text-2xl font-bold text-blue-700">{{ $stationperson->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6" data-chief-insights-source>
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-brain"></i>
                                Previous Analyses
                            </h2>
                            <p class="text-amber-50 text-sm mt-1">Last 5 AI forecasts saved for your station, including predictions.</p>
                        </div>
                        <a href="{{ route('station-admin.analytics') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/25 transition">
                            <i class="fa-solid fa-chart-line"></i>
                            Open Analytics
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @forelse($recentAnalyses as $analysis)
                        @php
                            $prediction = $analysis->predictions ?? [];
                            $predictionSummary = $prediction['summary'] ?? null;
                            $predictionOutlook = $prediction['outlook'] ?? 'Unknown';
                            $predictedFireIncidents = $prediction['predicted_fire_incidents'] ?? null;
                            $likelyPeakHours = collect($prediction['likely_peak_hours'] ?? [])->filter()->values();
                            $peakHoursLabel = $prediction['likely_peak_hours_label'] ?? ($likelyPeakHours->isNotEmpty() ? $likelyPeakHours->join(', ') : 'No clear peak yet');
                            $predictionWindow = $prediction['prediction_context']['target_period_label'] ?? ($prediction['forecast_window'] ?? null);
                            $predictionDrivers = collect($prediction['drivers'] ?? [])->filter()->take(2)->values();
                        @endphp
                        <div class="mb-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-orange-50/40 p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                            {{ $analysis->sector ?? 'Station Analysis' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold
                                            @if(strtolower($analysis->risk_level ?? '') === 'high' || strtolower($analysis->risk_level ?? '') === 'critical') bg-red-100 text-red-700
                                            @elseif(strtolower($analysis->risk_level ?? '') === 'medium') bg-amber-100 text-amber-700
                                            @else bg-emerald-100 text-emerald-700 @endif">
                                            {{ $analysis->risk_level ?? 'Unknown Risk' }}
                                        </span>
                                    </div>
                                    <h3 class="mt-3 text-lg font-bold text-slate-800">{{ $predictionWindow ?? ($analysis->period ?? 'Analysis Window') }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Saved {{ \Carbon\Carbon::parse($analysis->created_at)->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 min-w-[220px]">
                                    <div class="rounded-xl bg-white p-3 border border-slate-200">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Incidents</div>
                                        <div class="mt-1 text-2xl font-extrabold text-slate-800">{{ $analysis->incident_count ?? 0 }}</div>
                                    </div>
                                    <div class="rounded-xl bg-white p-3 border border-slate-200">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Confidence</div>
                                        <div class="mt-1 text-2xl font-extrabold text-slate-800">{{ (int) ($analysis->confidence_score ?? 0) }}%</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-amber-200/60 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div>
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Prediction Outlook</div>
                                        <div class="mt-1 text-base font-bold text-slate-800">{{ $predictionOutlook }}</div>
                                    </div>
                                    @if(!is_null($predictedFireIncidents))
                                        <div class="rounded-xl bg-white px-4 py-3 border border-amber-200">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-red-600">Possible Fire Incidents</div>
                                            <div class="mt-1 text-2xl font-extrabold text-red-800">{{ $predictedFireIncidents }}</div>
                                        </div>
                                    @endif
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-600">
                                    {{ $predictionSummary ?: 'No prediction summary available for this analysis.' }}
                                </p>

                                @if($peakHoursLabel || $predictionDrivers->isNotEmpty())
                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                        @if($peakHoursLabel)
                                            <div class="rounded-xl bg-white p-4 border border-slate-200">
                                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Likely Peak Hours</div>
                                                <div class="mt-2 text-sm font-semibold text-slate-700">{{ $peakHoursLabel }}</div>
                                            </div>
                                        @endif
                                        @if($predictionDrivers->isNotEmpty())
                                            <div class="rounded-xl bg-white p-4 border border-slate-200">
                                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Primary Drivers</div>
                                                <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                                    @foreach($predictionDrivers as $driver)
                                                        <li>{{ $driver }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-4 flex justify-end">
                                    <button
                                        type="button"
                                        onclick="openAnalysisDetails({{ (int) $analysis->analysis_id }})"
                                        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition"
                                    >
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                        View Full Analysis
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                            <div class="text-slate-400 text-3xl mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <p class="text-sm font-semibold text-slate-700">No previous analyses found</p>
                            <p class="text-xs text-slate-500 mt-1">Run an AI analysis from the analytics page to start building prediction history.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Equipment Card (replacing Today's Operational Summary) -->
            <div class="chief-dashboard-equipment-card dashboard-hidden-panel hidden md:block bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-tools"></i>
                            Equipment Inventory
                        </h2>
                        <button onclick="refreshEquipment()" class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div id="equipmentList" class="space-y-4 max-h-[500px] overflow-y-auto">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-blue-500 text-3xl"></i>
                            <p class="text-gray-500 mt-2">Loading equipment data...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- footer -->
            {{-- <div class="dashboard-footer">
                <i class="fa-solid fa-shield"></i> PyroSense · secured emergency dashboard · live data
            </div> --}}
        </div>

        <!-- Modals (same as before) -->
        <!-- Modals -->
<div id="analysisDetailsModal" class="chief-analysis-modal fixed inset-0 bg-slate-950/45 backdrop-blur-sm hidden items-center justify-center z-[5000] p-4">
    <div class="chief-analysis-modal__dialog w-full max-w-5xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="chief-analysis-modal__header flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Full Analysis Details</h2>
                <p id="analysisDetailsSubtitle" class="text-sm text-slate-500">Saved forecast and recommendation snapshot.</p>
            </div>
            <button type="button" onclick="closeAnalysisDetails()" class="chief-analysis-modal__close w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-100">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="analysisDetailsContent" class="chief-analysis-modal__content max-h-[75vh] overflow-y-auto px-6 py-5 bg-white">
            <div class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-chart-line text-3xl mb-3"></i>
                <p class="text-sm font-semibold">Select an analysis to view details</p>
            </div>
        </div>
    </div>
</div>

<div id="markerModal" class="modal chief-marker-modal" style="display: none;" aria-hidden="true">
    <div class="modal-content chief-marker-modal__card" role="dialog" aria-modal="true" aria-labelledby="markerModalTitle">
        <div class="chief-marker-modal__header">
            <div class="chief-marker-modal__title-row">
                <span class="chief-marker-modal__icon"><i class="fa-solid fa-location-dot"></i></span>
                <div>
                    <p class="chief-marker-modal__eyebrow">Map Management</p>
                    <h3 id="markerModalTitle">Add Map Marker</h3>
                    <p class="chief-marker-modal__subtitle">Save a verified incident, resource, or location point on the command map.</p>
                </div>
            </div>
            <button type="button" class="chief-marker-modal__close" onclick="closeModal()" aria-label="Close add marker form">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="chief-marker-modal__body">
        
        <!-- Marker Basic Fields -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
            <input type="text" id="markerTitle" class="w-full border border-gray-300 rounded-lg p-2" placeholder="Enter marker title">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="markerDescription" class="w-full border border-gray-300 rounded-lg p-2" rows="3" placeholder="Enter description"></textarea>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select id="mCategory" class="w-full border border-gray-300 rounded-lg p-2">
                <option value="">Select Category</option>
                <option value="fire" data-category-name="Incident" data-category-enum="fire">Incident</option>
                <option value="station" data-category-name="Fire Station" data-category-enum="station">Fire Station</option>
                <option value="hydrant" data-category-name="Hydrant" data-category-enum="hydrant">Hydrant</option>
                <option value="barangay" data-category-name="Barangay Hall" data-category-enum="barangay">Barangay Hall</option>
            </select>
        </div>

        <div id="incidentFields" class="station-fields">
            <h4><i class="fa-solid fa-triangle-exclamation"></i> Incident Information</h4>

            <div class="form-row">
                <label><i class="fa-solid fa-fire-flame-curved"></i> Incident Type</label>
                <input type="text" value="Fire" disabled>
                <div class="marker-subnote">Incident markers follow the `fire` category in the current schema.</div>
            </div>

            <div class="form-row">
                <label><i class="fa-solid fa-user"></i> Reported By *</label>
                <input type="text" id="reportedBySearch" list="citizenOptions" placeholder="Search citizen by name, account code, or contact number">
                <datalist id="citizenOptions">
                    @foreach($citizens ?? [] as $citizen)
                        @php
                            $citizenLabel = trim(($citizen->account_code ?? 'CIT') . ' · ' . trim(($citizen->first_name ?? '') . ' ' . ($citizen->middle_name ?? '') . ' ' . ($citizen->last_name ?? '')) . ' · ' . ($citizen->contact_number ?? ''));
                        @endphp
                        <option value="{{ $citizenLabel }}" data-user-id="{{ $citizen->user_id }}"></option>
                    @endforeach
                </datalist>
                <input type="hidden" id="reportedByUserId">
                <div class="marker-subnote">Type to search existing citizen records.</div>
            </div>

            <div class="form-row">
                <label style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="reportedByWalkIn" style="width:auto;">
                    Reported by walk-in without account
                </label>
            </div>

            <div id="walkInFields" style="display:none;">
                <div class="form-row">
                    <label><i class="fa-solid fa-id-card"></i> First Name *</label>
                    <input type="text" id="walkInFirstName" placeholder="First name">
                </div>
                <div class="form-row">
                    <label><i class="fa-solid fa-id-card"></i> Last Name *</label>
                    <input type="text" id="walkInLastName" placeholder="Last name">
                </div>
                <div class="form-row">
                    <label><i class="fa-solid fa-phone"></i> Contact Number *</label>
                    <input type="text" id="walkInContactNumber" placeholder="09XXXXXXXXX">
                </div>
            </div>

            <div class="form-row">
                <label><i class="fa-solid fa-users"></i> Default Response Team</label>
                <div class="marker-helper-chip" id="defaultIncidentTeamLabel">
                    @if(!empty($defaultTeam))
                        <i class="fa-solid fa-truck"></i>
                        Team #{{ $defaultTeam->team_id }}
                    @else
                        <i class="fa-solid fa-circle-exclamation"></i>
                        No station team available
                    @endif
                </div>
                <input type="hidden" id="defaultIncidentTeamId" value="{{ $defaultTeam->team_id ?? '' }}">
                <div class="marker-subnote">Incident assignment defaults to the first team linked to your station.</div>
            </div>
        </div>
        
        <!-- Station Fields (Fire Station) -->
        <div id="stationFields" class="station-fields">
            <h4><i class="fa-solid fa-building"></i> Station Information</h4>
            
            <div class="form-row">
                <label><i class="fa-solid fa-tag"></i> Station Name *</label>
                <input type="text" id="stationName" placeholder="e.g., Station 1 Quiapo">
            </div>
            
            <div class="form-row">
                <label><i class="fa-solid fa-barcode"></i> Station Code *</label>
                <input type="text" id="stationCode" placeholder="e.g., ST-001" oninput="generateStationCodePreview()">
                <div id="stationCodePreview" class="station-code-preview" style="display: none;">
                    <span>Preview:</span><br>
                    <strong id="previewCode">ST-0001</strong>
                </div>
            </div>
            
            <div class="form-row">
                <label><i class="fa-solid fa-phone"></i> Contact Number *</label>
                <input type="tel" id="stationContact" placeholder="e.g., 02-1234-5678">
            </div>
            
            <div class="form-row">
                <label><i class="fa-solid fa-envelope"></i> Email *</label>
                <input type="email" id="stationEmail" placeholder="station@pyrosense.gov">
            </div>
            
            <!-- Address Section -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-location-dot"></i> Station Address
                </h4>
                
                <!-- Province -->
                <div class="form-row">
                    <label><i class="fa-solid fa-map-marker-alt"></i> Province</label>
                    <select id="stationProvince" class="w-full">
                        <option value="">Select Province</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->province_id }}" data-province-name="{{ $province->province_name }}">{{ $province->province_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- City -->
                <div class="form-row">
                    <label><i class="fa-solid fa-city"></i> City/Municipality</label>
                    <select id="stationCity" class="w-full" disabled>
                        <option value="">Select City/Municipality</option>
                    </select>
                </div>
                
                <!-- Barangay -->
                <div class="form-row">
                    <label><i class="fa-solid fa-home"></i> Barangay</label>
                    <select id="stationBarangay" class="w-full" disabled>
                        <option value="">Select Barangay</option>
                    </select>
                </div>
                
                <!-- Postal Code (Hidden) -->
                <input type="hidden" id="stationPostalId" name="station_postal_id">
                
                <!-- Street -->
                <div class="form-row">
                    <label><i class="fa-solid fa-road"></i> Street Address</label>
                    <input type="text" id="stationStreet" placeholder="House number, street name, subdivision">
                </div>
            </div>
        </div>
        
       <!-- Hydrant Fields -->
<div id="hydrantFields" class="station-fields">
    <h4><i class="fa-solid fa-water"></i> Hydrant Information</h4>
    
    <div class="form-row">
        <label><i class="fa-solid fa-barcode"></i> Hydrant Code (Serial Number) *</label>
        <input type="text" id="hydrantCode" placeholder="e.g., HY-001-2024" oninput="generateHydrantCodePreview()">
        <div id="hydrantCodePreview" class="station-code-preview" style="display: none; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
            <span>Preview:</span><br>
            <strong id="previewHydrantCode">HY-0001</strong>
        </div>
    </div>
    
    <div class="form-row">
        <label><i class="fa-solid fa-building"></i> Assigned Station *</label>
        <select id="hydrantStation" class="w-full">
            {{-- user_id to station_officer to get station_id --}}
            @foreach($stations as $station)
                @foreach ($stationperson as $sp )
                    @if(auth()->user()->user_id == $sp->user_id)
                        @php
                            $stationId = $sp->station_id;
                        
                        @endphp
                    @endif
                @endforeach
                
                @if($stationId == $station->station_id)
                <option value="{{ $stationId }}">{{ $station->station_name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    
    <div class="form-row">
        <label><i class="fa-solid fa-tag"></i> Hydrant Type</label>
        <input type="text" value="Managed from hydrant records" disabled>
    </div>
</div>

<!-- School Fields -->
<div id="schoolFields" class="station-fields">
    <h4><i class="fa-solid fa-school"></i> School Information</h4>
    
    <div class="form-row">
        <label><i class="fa-solid fa-tag"></i> School Name *</label>
        <input type="text" id="schoolName" placeholder="e.g., San Carlos City National High School">
    </div>
</div>

<div id="barangayHallFields" class="station-fields">
    <h4><i class="fa-solid fa-building-columns"></i> Barangay Hall Information</h4>

    <div class="form-row">
        <label><i class="fa-solid fa-tag"></i> Hall Name *</label>
        <input type="text" id="barangayHallName" placeholder="e.g., Barangay Hall Poblacion">
    </div>

    <div class="form-row">
        <label><i class="fa-solid fa-phone"></i> Contact Number</label>
        <input type="tel" id="barangayHallContact" placeholder="e.g., 09XXXXXXXXX">
    </div>

    <div class="form-row">
        <label><i class="fa-solid fa-envelope"></i> Email</label>
        <input type="email" id="barangayHallEmail" placeholder="barangayhall@example.gov.ph">
    </div>

    <div class="form-row">
        <label><i class="fa-solid fa-user-tie"></i> Barangay Captain</label>
        <select id="barangayHallCaptain" class="w-full">
            <option value="">Select Captain / Officer</option>
            @foreach($barangayOfficers ?? [] as $officer)
                @php
                    $officerName = trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->middle_name ?? '') . ' ' . ($officer->user->last_name ?? ''));
                    $officerLabel = trim(($officerName !== '' ? $officerName : 'Officer #' . $officer->barangay_officer_id) . ' · ' . ($officer->position ?? 'Officer'));
                @endphp
                <option value="{{ $officer->barangay_officer_id }}" data-barangay-id="{{ $officer->barangay_id }}">
                    {{ $officerLabel }}
                </option>
            @endforeach
        </select>
        <div class="marker-subnote">Only active barangay officers assigned to the selected barangay are shown.</div>
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-location-dot"></i> Barangay Hall Address
        </h4>

        <div class="form-row">
            <label><i class="fa-solid fa-map-marker-alt"></i> Province</label>
            <select id="barangayHallProvince" class="w-full">
                <option value="">Select Province</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->province_id }}" data-province-name="{{ $province->province_name }}">{{ $province->province_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-row">
            <label><i class="fa-solid fa-city"></i> City/Municipality</label>
            <select id="barangayHallCity" class="w-full" disabled>
                <option value="">Select City/Municipality</option>
            </select>
        </div>

        <div class="form-row">
            <label><i class="fa-solid fa-home"></i> Barangay *</label>
            <select id="barangayHallBarangay" class="w-full" disabled>
                <option value="">Select Barangay</option>
            </select>
        </div>

        <input type="hidden" id="barangayHallPostalId" name="barangay_hall_postal_id">

        <div class="form-row">
            <label><i class="fa-solid fa-road"></i> Street Address</label>
            <input type="text" id="barangayHallStreet" placeholder="House number, street name, purok">
        </div>
    </div>
</div>
        
        </div>

        <div class="modal-actions chief-marker-modal__actions">
            <button class="btn-save" id="saveMarkerBtn">Save Marker</button>
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

        <div id="updateModal" style="display:none;">
            <form id="editForm" action="{{ route('markers.update', 0) }}" method="post">
                @csrf
                @method('PUT')
                <input type="hidden" id="markerid" name="marker_id">
                <input type="text" id="editTitle" name="title">
                <textarea id="editDesc" name="description"></textarea>
                <select id="editCat" name="category">
                    <option value="fire">Incident</option>
                    <option value="station">Fire Station</option>
                    <option value="hydrant">Hydrant</option>
                </select>
                <button type="submit">Save</button>
            </form>
        </div>

        <div id="iconModal" class="modal">
            <div class="modal-content">
                <h3>Add Icon</h3>
                <form action="" enctype="multipart/form-data">
                    <input type="file" id="iconImage" class="border p-2 w-full mb-2" accept="image/*">
                    <textarea id="iconType" class="border p-2 w-full mb-4" placeholder="Icon Type"></textarea>
                    <div class="modal-actions">
                        <button class="btn-save" id="saveIconBtn">Save</button>
                        <button type="button" class="btn-cancel" onclick="closeIconModal()">Cancel</button>
                    </div>
                </form>
            </div>
        @php
            $fireCount = $incidents->filter(fn($incident) => strtolower((string) ($incident->marker->category ?? $incident->incident_type ?? '')) === 'fire')->count();
            $medicalCount = $incidents->filter(fn($incident) => str_contains(strtolower((string) ($incident->incident_type ?? '')), 'medical'))->count();
            $rescueCount = $incidents->filter(fn($incident) => str_contains(strtolower((string) ($incident->incident_type ?? '')), 'rescue'))->count();
            $otherCount = $incidents->count() - ($fireCount + $medicalCount + $rescueCount);
            $reportedCount = $incidents->where('status', 'Reported')->count();
            $respondingCount = $incidents->where('status', 'Responding')->count();
            $activeCount = $incidents->where('status', 'On Scene')->count();
            $resolvedCount = $incidents->whereIn('status', ['Controlled', 'Closed'])->count();
            $hourlyData = array_fill(0, 24, 0);
            foreach ($incidents as $incident) {
                $hour = \Carbon\Carbon::parse($incident->created_at)->hour;
                $hourlyData[$hour] = ($hourlyData[$hour] ?? 0) + 1;
            }
        @endphp

    </main>

<script>
    // Pass PHP data to JavaScript
    const chartData = {
        fireCount: {{ $fireCount }},
        medicalCount: {{ $medicalCount }},
        rescueCount: {{ $rescueCount }},
        otherCount: {{ $otherCount }},
        reportedCount: {{ $reportedCount }},
        respondingCount: {{ $respondingCount }},
        activeCount: {{ $activeCount }},
        resolvedCount: {{ $resolvedCount }},
        hourlyData: {{ json_encode(array_values($hourlyData)) }}
    };

    const incidentTrendData = @json($incidentTrendData);
    const mlTrendData = @json($analysisTrendSeries);
    console.log('Incident Trend Data:', incidentTrendData);
    function bindAddressSelectors(prefix) {
        const provinceSelect = document.getElementById(`${prefix}Province`);
        const citySelect = document.getElementById(`${prefix}City`);
        const barangaySelect = document.getElementById(`${prefix}Barangay`);

        if (!provinceSelect || !citySelect || !barangaySelect) {
            return;
        }

        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;

            if (!provinceId) {
                citySelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                if (prefix === 'barangayHall') {
                    syncBarangayHallCaptainOptions('');
                }
                return;
            }

            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Loading...</option>';

            fetch(`/api/cities/${provinceId}`)
                .then(response => response.json())
                .then(cities => {
                    citySelect.disabled = false;
                    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                    cities.forEach(city => {
                        citySelect.innerHTML += `<option value="${city.city_id}">${city.city_name}</option>`;
                    });

                    barangaySelect.disabled = true;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    if (prefix === 'barangayHall') {
                        syncBarangayHallCaptainOptions('');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                });
        });

        citySelect.addEventListener('change', function() {
            const cityId = this.value;

            if (!cityId) {
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                if (prefix === 'barangayHall') {
                    syncBarangayHallCaptainOptions('');
                }
                return;
            }

            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Loading...</option>';

            fetch(`/api/barangays/${cityId}`)
                .then(response => response.json())
                .then(barangays => {
                    barangaySelect.disabled = false;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    barangays.forEach(barangay => {
                        barangaySelect.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                    });
                    if (prefix === 'barangayHall') {
                        syncBarangayHallCaptainOptions(barangaySelect.value || '');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
                });
        });

        barangaySelect.addEventListener('change', function() {
            if (prefix === 'barangayHall') {
                syncBarangayHallCaptainOptions(this.value || '');
            }
        });
    }

    function syncBarangayHallCaptainOptions(selectedBarangayId = '') {
        const captainSelect = document.getElementById('barangayHallCaptain');
        if (!captainSelect) {
            return;
        }

        const targetBarangayId = String(selectedBarangayId || '');
        const options = Array.from(captainSelect.options);

        let hasVisibleOption = false;
        options.forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionBarangayId = String(option.dataset.barangayId || '');
            const shouldShow = !targetBarangayId || optionBarangayId === targetBarangayId;
            option.hidden = !shouldShow;

            if (!shouldShow && option.selected) {
                captainSelect.value = '';
            }

            if (shouldShow) {
                hasVisibleOption = true;
            }
        });

        if (!hasVisibleOption) {
            captainSelect.value = '';
        }
    }

    bindAddressSelectors('station');
    bindAddressSelectors('barangayHall');
    
    var marker = @json($mapmarker) || [];
    window.mapInstanceConfig = {
        containerId: 'map',
        instanceKey: 'chiefDashboard'
    };
    window.mapData = {
        marker: @json($mapmarker) || []
    };
    window.incidentsData = [];
    var stations = @json($stations) || [];
    window.chiefDashboardStatsSeed = @json($dashboardStatsSeed ?? []);
    window.pyroPublicAssetUrls = {
        station: @json(asset('station.png')),
        hydrant: @json(asset('hydrant.png')),
        barangay: @json(asset('hall.png')),
        evacuation: @json(asset('hall.png')),
        incident: @json(asset('fire.png')),
        risk: @json(asset('hazard.png')),
        hazard: @json(asset('hazard.png')),
        firefighter: @json(asset('team.png')),
        firetruck: @json(asset('truck.png'))
    };

    window.stationsData = @json($stations) || [];
    window.stationDatas = window.stationsData;

    let multimediaData = [];
    let icons = [];

    document.addEventListener('DOMContentLoaded', function() {
        window.setTimeout(() => {
            if (typeof window.refreshMapMarkers === 'function') {
                window.refreshMapMarkers({
                    preferSeeded: true,
                    revalidate: true,
                    force: true
                }).catch((error) => {
                    console.warn('Unable to refresh chief dashboard markers:', error);
                });
            }
        }, 220);

        window.addEventListener('pyro:voice-caller-location', function(event) {
            const latLng = Array.isArray(event?.detail?.latLng) ? event.detail.latLng : null;
            if (!latLng || latLng.length < 2) {
                return;
            }

            focusOnMarker(latLng[0], latLng[1]);
        });

        window.addEventListener('pyro:voice-call-incident-created', function() {
            Promise.allSettled([
                typeof window.refreshChiefIncidentNotifications === 'function'
                    ? window.refreshChiefIncidentNotifications()
                    : Promise.resolve(),
                typeof window.refreshMapMarkers === 'function'
                    ? window.refreshMapMarkers({ force: true })
                    : Promise.resolve()
            ]);
        });

        const mobileWelcomeToast = document.getElementById('chiefMobileWelcomeToast');
        const insightsPanel = document.getElementById('chiefMobileInsightsPanel');
        const insightsContent = document.getElementById('chiefMobileInsightsContent');
        const insightsSources = Array.from(document.querySelectorAll('[data-chief-insights-source]'));
        const insightsPlaceholders = insightsSources.map((source, index) => {
            const placeholder = document.createElement('div');
            placeholder.setAttribute('data-chief-insights-placeholder', String(index));
            source.parentNode.insertBefore(placeholder, source);
            return placeholder;
        });
        const isMobileViewport = () => window.innerWidth <= 768;
        let mobileToastTimer = null;

        function showMobileWelcomeToast() {
            if (!mobileWelcomeToast) {
                return;
            }

            mobileWelcomeToast.classList.add('is-visible');
            window.clearTimeout(mobileToastTimer);
            mobileToastTimer = window.setTimeout(() => {
                mobileWelcomeToast.classList.remove('is-visible');
            }, 3200);
        }

        function openInsightsPanel() {
            if (!insightsPanel || !insightsContent || !isMobileViewport()) {
                return;
            }

            insightsSources.forEach((source) => {
                insightsContent.appendChild(source);
            });

            insightsPanel.classList.add('is-open');
            insightsPanel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
        }

        function closeInsightsPanel() {
            if (!insightsPanel || !insightsContent) {
                return;
            }

            insightsSources.forEach((source, index) => {
                const placeholder = insightsPlaceholders[index];
                if (placeholder?.parentNode) {
                    placeholder.parentNode.insertBefore(source, placeholder.nextSibling);
                }
            });

            insightsPanel.classList.remove('is-open');
            insightsPanel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-open');
        }

        insightsPanel?.querySelectorAll('[data-chief-insights-close]').forEach((button) => {
            button.addEventListener('click', closeInsightsPanel);
        });

        document.addEventListener('chief:open-insights', openInsightsPanel);
        showMobileWelcomeToast();
        window.addEventListener('resize', function() {
            if (!isMobileViewport()) {
                closeInsightsPanel();
            }
        });

        // Alert bell toggle
        const alertBell = document.getElementById('notificationBtn');
        const alertsDropdown = document.getElementById('alertsDropdown');
        const alertsHost = alertsDropdown?.closest('.chief-alerts-host');
        const alertsBackdrop = document.getElementById('chiefNotificationBackdrop');
        const alertsCloseBtn = document.getElementById('alertsDropdownClose');
        let lastAlertsFocus = null;

        function isChiefAlertsPanelOpen() {
            return Boolean(alertsDropdown?.classList.contains('show'));
        }

        function openChiefAlertsPanel() {
            if (!alertsDropdown) return;
            lastAlertsFocus = document.activeElement;
            alertsHost?.classList.add('chief-alerts-host-open');
            alertsDropdown.classList.add('show');
            alertsDropdown.classList.remove('hidden');
            alertsDropdown.setAttribute('aria-hidden', 'false');
            alertBell?.setAttribute('aria-expanded', 'true');
            alertsBackdrop?.removeAttribute('hidden');
            alertsBackdrop?.classList.add('is-visible');
            document.body.classList.add('chief-notifications-open');

            if (window.matchMedia('(max-width: 767px)').matches) {
                window.setTimeout(() => alertsCloseBtn?.focus({ preventScroll: true }), 0);
            }
        }

        function closeChiefAlertsPanel(options = {}) {
            if (!alertsDropdown) return;

            if (alertsDropdown.contains(document.activeElement)) {
                document.activeElement?.blur?.();
            }

            alertsDropdown.classList.remove('show');
            alertsDropdown.classList.add('hidden');
            alertsDropdown.setAttribute('aria-hidden', 'true');
            alertBell?.setAttribute('aria-expanded', 'false');
            alertsBackdrop?.classList.remove('is-visible');
            alertsBackdrop?.setAttribute('hidden', 'hidden');
            alertsHost?.classList.remove('chief-alerts-host-open');
            document.body.classList.remove('chief-notifications-open');

            if (options.restoreFocus !== false) {
                (lastAlertsFocus || alertBell)?.focus?.({ preventScroll: true });
            }
        }

        function toggleChiefAlertsPanel() {
            isChiefAlertsPanelOpen() ? closeChiefAlertsPanel() : openChiefAlertsPanel();
        }

        if (alertBell) {
            alertBell.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleChiefAlertsPanel();
            });
        }

        alertsCloseBtn?.addEventListener('click', () => closeChiefAlertsPanel());
        alertsBackdrop?.addEventListener('click', () => closeChiefAlertsPanel());
        alertsBackdrop?.addEventListener('touchmove', (event) => event.preventDefault(), { passive: false });
        alertsBackdrop?.addEventListener('wheel', (event) => event.preventDefault(), { passive: false });

        document.addEventListener('click', function(e) {
            if (!isChiefAlertsPanelOpen()) return;
            if (alertBell?.contains(e.target) || alertsDropdown?.contains(e.target)) return;
            closeChiefAlertsPanel({ restoreFocus: false });
        });

        document.addEventListener('touchmove', function(e) {
            if (!isChiefAlertsPanelOpen()) return;
            if (alertsDropdown?.contains(e.target)) return;
            e.preventDefault();
        }, { passive: false });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isChiefAlertsPanelOpen()) {
                closeChiefAlertsPanel();
            }
        });

        window.openChiefAlertsPanel = openChiefAlertsPanel;
        window.closeChiefAlertsPanel = closeChiefAlertsPanel;

        // Initialize all charts
        initializeCharts();

        // User dropdown toggle
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const chiefLogsModal = document.getElementById('chiefLogsModal');
        const chiefLogsContainer = document.getElementById('chiefLogsContainer');
        const chiefLogsSummary = document.getElementById('chiefLogsSummary');
        const chiefLogsTitle = document.getElementById('chiefLogsTitle');
        const chiefLogsSubtitle = document.getElementById('chiefLogsSubtitle');
        const refreshChiefLogsBtn = document.getElementById('refreshChiefLogsBtn');
        const closeChiefLogsModalBtn = document.getElementById('closeChiefLogsModal');
        const showMyChiefLogsBtn = document.getElementById('showMyChiefLogsBtn');
        const showAllChiefLogsBtn = document.getElementById('showAllChiefLogsBtn');
        let chiefLogsMode = 'mine';
        const chiefLogsPerPage = 10;
        const chiefLogsPageByMode = { mine: 1, all: 1 };

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.remove('show');
                }
            });

            userDropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        function formatChiefLogDate(value) {
            const date = value ? new Date(value) : null;
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return 'Unknown date';
            }

            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function summarizeChiefUserAgent(userAgent) {
            if (!userAgent) {
                return 'Unavailable';
            }

            const trimmed = String(userAgent).trim();
            return trimmed.length > 140 ? `${trimmed.slice(0, 137)}...` : trimmed;
        }

        function renderChiefLogsLoadingState() {
            if (!chiefLogsContainer) {
                return;
            }

            chiefLogsContainer.innerHTML = `
                <div class="space-y-3">
                    ${Array.from({ length: 4 }).map(() => `
                        <div class="chief-log-skeleton">
                            <div class="chief-log-skeleton-row">
                                <div class="chief-log-skeleton-avatar"></div>
                                <div class="chief-log-skeleton-content">
                                    <div class="chief-log-skeleton-line w-60"></div>
                                    <div class="chief-log-skeleton-line w-45"></div>
                                    <div class="chief-log-skeleton-line w-90"></div>
                                    <div class="chief-log-skeleton-line w-75"></div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function setChiefLogsMode(mode) {
            chiefLogsMode = mode === 'all' ? 'all' : 'mine';

            if (chiefLogsTitle) {
                chiefLogsTitle.textContent = chiefLogsMode === 'all' ? 'All User Activities' : 'My Logs';
            }

            if (chiefLogsSubtitle) {
                chiefLogsSubtitle.textContent = chiefLogsMode === 'all'
                    ? 'Complete activity history from the user_logs table.'
                    : 'Your recorded activity in the system.';
            }

            showMyChiefLogsBtn?.classList.toggle('bg-slate-800', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('text-white', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('border-slate-800', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('bg-slate-100', chiefLogsMode !== 'mine');
            showMyChiefLogsBtn?.classList.toggle('text-slate-700', chiefLogsMode !== 'mine');
            showMyChiefLogsBtn?.classList.toggle('border-slate-200', chiefLogsMode !== 'mine');

            showAllChiefLogsBtn?.classList.toggle('bg-amber-600', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('text-white', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('border-amber-600', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('bg-amber-50', chiefLogsMode !== 'all');
            showAllChiefLogsBtn?.classList.toggle('text-amber-700', chiefLogsMode !== 'all');
            showAllChiefLogsBtn?.classList.toggle('border-amber-100', chiefLogsMode !== 'all');
        }

        function renderChiefLogsPagination(meta = {}) {
            const currentPage = Number(meta?.current_page || 1);
            const lastPage = Number(meta?.last_page || 1);

            if (lastPage <= 1) {
                return '';
            }

            return `
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage <= 1 ? 'disabled' : `onclick="loadChiefLogsPage(${currentPage - 1})"`}>
                        Previous
                    </button>
                    <span class="text-xs font-medium text-slate-500">Page ${currentPage} of ${lastPage}</span>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage >= lastPage ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage >= lastPage ? 'disabled' : `onclick="loadChiefLogsPage(${currentPage + 1})"`}>
                        Next
                    </button>
                </div>
            `;
        }

        function renderChiefLogs(logs = [], meta = {}) {
            if (!chiefLogsContainer || !chiefLogsSummary) {
                return;
            }

            const totalLogs = Number(meta?.total || logs.length || 0);
            const from = Number(meta?.from || 0);
            const to = Number(meta?.to || 0);
            chiefLogsSummary.textContent = totalLogs
                ? `Showing ${from}-${to} of ${totalLogs} log entries`
                : '0 log entries found';

            if (!logs.length) {
                chiefLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <div class="text-slate-400 text-3xl mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <p class="text-sm font-semibold text-slate-700">No logs available yet</p>
                        <p class="text-xs text-slate-500 mt-1">Activity records will appear here once actions are logged.</p>
                    </div>
                `;
                return;
            }

            chiefLogsContainer.innerHTML = `
                <div class="space-y-3">
                    ${logs.map((log) => `
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-bold text-slate-800 leading-6">${log.action || 'Unknown action'}</p>
                                        <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">#${log.user_log_id}</span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        ${chiefLogsMode === 'all' ? `
                                            <div class="flex items-start gap-2 text-sm text-slate-600">
                                                <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-user"></i></span>
                                                <span><span class="font-semibold text-slate-700">User:</span> ${log.user_name || 'Unknown user'}${log.role ? ` (${log.role})` : ''}</span>
                                            </div>
                                            <div class="flex items-start gap-2 text-sm text-slate-600">
                                                <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-envelope"></i></span>
                                                <span><span class="font-semibold text-slate-700">Email:</span> ${log.email || 'Unavailable'}</span>
                                            </div>
                                        ` : ''}
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-calendar"></i></span>
                                            <span><span class="font-semibold text-slate-700">Date & Time:</span> ${formatChiefLogDate(log.created_at)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-desktop"></i></span>
                                            <span class="break-words"><span class="font-semibold text-slate-700">User Agent:</span> ${summarizeChiefUserAgent(log.user_agent)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-globe"></i></span>
                                            <span><span class="font-semibold text-slate-700">IP Address:</span> ${log.ip_address || 'Unavailable'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ${renderChiefLogsPagination(meta)}
            `;
        }

        async function loadChiefLogs(mode = chiefLogsMode, page = 1) {
            if (!chiefLogsContainer || !chiefLogsSummary) {
                return;
            }

            setChiefLogsMode(mode);
            chiefLogsPageByMode[chiefLogsMode] = Math.max(1, Number(page || 1));
            chiefLogsSummary.textContent = chiefLogsMode === 'all' ? 'Loading all user activities...' : 'Loading your activity logs...';
            renderChiefLogsLoadingState();

            try {
                const endpoint = chiefLogsMode === 'all' ? '{{ route('user-logs.all') }}' : '{{ route('user-logs.index') }}';
                const response = await fetch(`${endpoint}?page=${chiefLogsPageByMode[chiefLogsMode]}&limit=${chiefLogsPerPage}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load logs (${response.status})`);
                }

                const payload = await response.json();
                renderChiefLogs(Array.isArray(payload?.data) ? payload.data : [], payload?.meta || {});
            } catch (error) {
                console.error('Error loading chief logs:', error);
                chiefLogsSummary.textContent = chiefLogsMode === 'all'
                    ? 'Unable to load all user activities'
                    : 'Unable to load your activity logs';
                chiefLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-red-100 bg-red-50 px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-red-700">Failed to load logs</p>
                        <p class="text-xs text-red-500 mt-1">Please try again in a moment.</p>
                    </div>
                `;
            }
        }

        function openChiefLogsModal(mode = 'mine') {
            userDropdownMenu?.classList.remove('show');
            if (chiefLogsModal) {
                chiefLogsModal.classList.remove('hidden');
                chiefLogsModal.classList.add('flex');
            }
            loadChiefLogs(mode, 1);
        }

        function closeChiefLogsModal() {
            if (chiefLogsModal) {
                chiefLogsModal.classList.add('hidden');
                chiefLogsModal.classList.remove('flex');
            }
        }

        window.openChiefLogsModal = openChiefLogsModal;
        window.closeChiefLogsModal = closeChiefLogsModal;
        window.loadChiefLogsPage = function(page) {
            loadChiefLogs(chiefLogsMode, page);
        };

        refreshChiefLogsBtn?.addEventListener('click', function() {
            loadChiefLogs(chiefLogsMode, chiefLogsPageByMode[chiefLogsMode] || 1);
        });
        showMyChiefLogsBtn?.addEventListener('click', function() {
            loadChiefLogs('mine', 1);
        });
        showAllChiefLogsBtn?.addEventListener('click', function() {
            loadChiefLogs('all', 1);
        });
        closeChiefLogsModalBtn?.addEventListener('click', closeChiefLogsModal);
        chiefLogsModal?.addEventListener('click', function(e) {
            if (e.target === chiefLogsModal) {
                closeChiefLogsModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && chiefLogsModal && !chiefLogsModal.classList.contains('hidden')) {
                closeChiefLogsModal();
            }
            if (e.key === 'Escape' && insightsPanel?.classList.contains('is-open')) {
                closeInsightsPanel();
            }
        });

        const analysisDetailsModal = document.getElementById('analysisDetailsModal');
        analysisDetailsModal?.addEventListener('click', function (e) {
            if (e.target === analysisDetailsModal) {
                closeAnalysisDetails();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && analysisDetailsModal && !analysisDetailsModal.classList.contains('hidden')) {
                closeAnalysisDetails();
            }
        });
    });

    // Helper functions
    function deployTeam() {
    }

    function initializeCharts() {

        // Incident History Chart
        const periodBtns = document.querySelectorAll('.chart-period-btn');
        const ctx = document.getElementById('incidentChart')?.getContext('2d');
        let currentChart = null;

        if (ctx) {
            function loadChartData(period) {
                const selectedPeriod = incidentTrendData[period] || incidentTrendData.week;

                if (currentChart) {
                    currentChart.destroy();
                }

                currentChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: selectedPeriod.labels,
                        datasets: [{
                            label: 'Number of Incidents',
                            data: selectedPeriod.values,
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#dc2626',
                            pointBorderColor: 'white',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1f2937',
                                titleColor: '#f3f4f6',
                                bodyColor: '#f3f4f6',
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#e5e7eb' },
                                ticks: { precision: 0 }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            periodBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    periodBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    loadChartData(this.dataset.period);
                });
            });

            loadChartData('week');
        }

        const analysisTrendCtx = document.getElementById('analysisTrendChart')?.getContext('2d');
        if (analysisTrendCtx) {
            new Chart(analysisTrendCtx, {
                type: 'line',
                data: {
                    labels: mlTrendData.map((item) => item.label),
                    datasets: [
                        {
                            label: 'Actual Incidents',
                            data: mlTrendData.map((item) => item.incident_count),
                            borderColor: '#c63d26',
                            backgroundColor: 'rgba(198, 61, 38, 0.08)',
                            borderWidth: 3,
                            pointRadius: 4,
                            tension: 0.35,
                            fill: false
                        },
                        {
                            label: 'Predicted Fire Incidents',
                            data: mlTrendData.map((item) => item.predicted_fire_incidents),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            borderWidth: 3,
                            pointRadius: 4,
                            tension: 0.35,
                            borderDash: [8, 6],
                            fill: false
                        },
                        {
                            label: 'Confidence %',
                            data: mlTrendData.map((item) => item.confidence),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            borderWidth: 2,
                            pointRadius: 3,
                            tension: 0.3,
                            yAxisID: 'confidenceAxis',
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { precision: 0 }
                        },
                        confidenceAxis: {
                            beginAtZero: true,
                            position: 'right',
                            min: 0,
                            max: 100,
                            grid: { display: false },
                            ticks: {
                                callback: (value) => `${value}%`
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    // Equipment Management Functions
    async function loadEquipment() {
        const equipmentList = document.getElementById('equipmentList');
        if (!equipmentList) {
            return;
        }

        try {
            const response = await fetch('{{ route('station-admin.management.equipment') }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Failed to fetch equipment');
            
            const payload = await response.json();
            displayEquipment(payload.data || []);
        } catch (error) {
            console.error('Error loading equipment:', error);
            document.getElementById('equipmentList').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                    <p class="mt-2">Failed to load equipment data</p>
                    <button onclick="loadEquipment()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Retry
                    </button>
                </div>
            `;
        }
    }

    function displayEquipment(equipment) {
        const container = document.getElementById('equipmentList');
        
        if (!equipment || equipment.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-3xl"></i>
                    <p class="mt-2">No equipment found</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = equipment.map(item => {
            const categoryName = item.category_name || 'Uncategorized';
            const availableQuantity = item.available_quantity ?? 0;
            const maintenanceQuantity = item.maintenance_quantity ?? 0;
            const damagedQuantity = item.damaged_quantity ?? 0;

            return `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-hard-hat text-blue-600"></i>
                            <h3 class="font-semibold text-gray-800">${escapeHtml(item.equipment_name)}</h3>
                            <span class="text-xs px-2 py-1 bg-gray-100 rounded-full">${escapeHtml(categoryName)}</span>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-3 mt-3">
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-green-50 rounded-lg p-2">
                                    <div class="text-green-600 font-bold text-lg">${availableQuantity}</div>
                                    <div class="text-xs text-gray-600">Available</div>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-2">
                                    <div class="text-yellow-600 font-bold text-lg">${maintenanceQuantity}</div>
                                    <div class="text-xs text-gray-600">Maintenance</div>
                                </div>
                                <div class="bg-red-50 rounded-lg p-2">
                                    <div class="text-red-600 font-bold text-lg">${damagedQuantity}</div>
                                    <div class="text-xs text-gray-600">Damaged</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        }).join('');
    }

    function refreshEquipment() {
        loadEquipment();
    }

    async function openAnalysisDetails(analysisId) {
        const modal = document.getElementById('analysisDetailsModal');
        const content = document.getElementById('analysisDetailsContent');
        const subtitle = document.getElementById('analysisDetailsSubtitle');

        if (!modal || !content) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        subtitle.textContent = `Loading analysis #${analysisId}...`;
        content.innerHTML = `
            <div class="text-center py-12 text-slate-500">
                <i class="fa-solid fa-spinner fa-spin text-3xl mb-3"></i>
                <p class="text-sm font-semibold">Loading saved analysis details...</p>
            </div>
        `;

        try {
            const response = await fetch(`/station-admin/api/analyses/${analysisId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Failed to load analysis (${response.status})`);
            }

            const payload = await response.json();
            const analysis = payload?.data;

            if (!analysis) {
                throw new Error('Analysis data is missing.');
            }

            renderAnalysisDetails(analysis);
        } catch (error) {
            console.error('Error loading analysis details:', error);
            subtitle.textContent = 'Unable to load saved analysis';
            content.innerHTML = `
                <div class="rounded-2xl border border-red-100 bg-red-50 px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-red-700">Failed to load analysis details</p>
                    <p class="text-xs text-red-500 mt-1">${escapeHtml(error.message || 'Please try again.')}</p>
                </div>
            `;
        }
    }

    function renderAnalysisDetails(analysis) {
        const content = document.getElementById('analysisDetailsContent');
        const subtitle = document.getElementById('analysisDetailsSubtitle');

        if (!content || !subtitle) {
            return;
        }

        const prediction = analysis.predictions || {};
        const recommendations = Array.isArray(analysis.recommendations) ? analysis.recommendations : [];
        const analysisData = analysis.analysis_data || {};
        const predictionDrivers = Array.isArray(prediction.drivers) ? prediction.drivers : [];
        const likelyPeakHours = Array.isArray(prediction.likely_peak_hours) ? prediction.likely_peak_hours : [];
        const peakHoursLabel = prediction.likely_peak_hours_label || (likelyPeakHours.length ? likelyPeakHours.join(', ') : 'No clear peak yet');
        const riskFactors = Array.isArray(analysisData.risk_factors) ? analysisData.risk_factors : [];
        const savedAt = analysis.created_at ? new Date(analysis.created_at).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit'
        }) : 'Unknown date';

        subtitle.textContent = `${analysis.sector || 'Station Analysis'} · Saved ${savedAt}`;

        content.innerHTML = `
            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-orange-50/40 p-5">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Forecast Window</div>
                            <div class="mt-1 text-xl font-extrabold text-slate-800">${escapeHtml(prediction.forecast_window || analysis.period || 'Analysis Window')}</div>
                            <div class="mt-2 flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">${escapeHtml(analysis.sector || 'Station Analysis')}</span>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ${
                                    ['high', 'critical'].includes(String(analysis.risk_level || '').toLowerCase()) ? 'bg-red-100 text-red-700' :
                                    String(analysis.risk_level || '').toLowerCase() === 'medium' ? 'bg-amber-100 text-amber-700' :
                                    'bg-emerald-100 text-emerald-700'
                                }">${escapeHtml(analysis.risk_level || 'Unknown Risk')}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 min-w-[220px]">
                            <div class="rounded-xl bg-white p-3 border border-slate-200">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Incidents</div>
                                <div class="mt-1 text-2xl font-extrabold text-slate-800">${escapeHtml(String(analysis.incident_count ?? 0))}</div>
                            </div>
                            <div class="rounded-xl bg-white p-3 border border-slate-200">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Confidence</div>
                                <div class="mt-1 text-2xl font-extrabold text-slate-800">${escapeHtml(String(analysis.confidence_score ?? 0))}%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-amber-200/60 bg-gradient-to-r from-amber-50 to-orange-50 p-5">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Prediction Outlook</div>
                            <div class="mt-1 text-lg font-bold text-slate-800">${escapeHtml(prediction.outlook || 'Unknown')}</div>
                        </div>
                        ${prediction.predicted_fire_incidents !== undefined && prediction.predicted_fire_incidents !== null ? `
                            <div class="rounded-xl bg-white px-4 py-3 border border-amber-200">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-red-600">Possible Fire Incidents</div>
                                <div class="mt-1 text-2xl font-extrabold text-red-800">${escapeHtml(String(prediction.predicted_fire_incidents))}</div>
                            </div>
                        ` : ''}
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">${escapeHtml(prediction.summary || 'No saved prediction summary available.')}</p>
                    ${(peakHoursLabel || predictionDrivers.length) ? `
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            ${peakHoursLabel ? `
                                <div class="rounded-xl bg-white p-4 border border-slate-200">
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Likely Peak Hours</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-700">${escapeHtml(peakHoursLabel)}</div>
                                </div>
                            ` : ''}
                            ${predictionDrivers.length ? `
                                <div class="rounded-xl bg-white p-4 border border-slate-200">
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Primary Drivers</div>
                                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                        ${predictionDrivers.map(driver => `<li>${escapeHtml(driver)}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="text-sm font-bold text-slate-800 mb-3">Recommendations</div>
                        ${recommendations.length ? `
                            <div class="space-y-3">
                                ${recommendations.map((rec, index) => `
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center shrink-0">${index + 1}</div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-800">${escapeHtml(rec.title || 'Recommendation')}</div>
                                                <div class="mt-1 text-sm text-slate-600">${escapeHtml(rec.description || '')}</div>
                                                <div class="mt-2 inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-bold text-slate-600">${escapeHtml(rec.priority || 'Medium')}</div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : `
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No saved recommendations for this analysis.</div>
                        `}
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="text-sm font-bold text-slate-800 mb-3">Risk Factors</div>
                        ${riskFactors.length ? `
                            <ul class="space-y-2 text-sm text-slate-700">
                                ${riskFactors.map(factor => `<li class="rounded-xl bg-slate-50 px-4 py-3 border border-slate-200">${escapeHtml(factor)}</li>`).join('')}
                            </ul>
                        ` : `
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No risk factors saved for this analysis.</div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }

    function closeAnalysisDetails() {
        const modal = document.getElementById('analysisDetailsModal');
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================
    // CATEGORY FIELD TOGGLING - UPDATED WITH HYDRANT AND SCHOOL
    // ============================================
    
    // Get the category select element and all field containers
    const categorySelect = document.getElementById('mCategory');
    const stationFields = document.getElementById('stationFields');
    const hydrantFields = document.getElementById('hydrantFields');
    const schoolFields = document.getElementById('schoolFields');
    const barangayHallFields = document.getElementById('barangayHallFields');
    const incidentFields = document.getElementById('incidentFields');
    const reportedBySearch = document.getElementById('reportedBySearch');
    const reportedByUserId = document.getElementById('reportedByUserId');
    const reportedByWalkIn = document.getElementById('reportedByWalkIn');
    const walkInFields = document.getElementById('walkInFields');

    function syncCitizenSearchSelection() {
        if (!reportedBySearch || !reportedByUserId) {
            return;
        }

        const selectedLabel = reportedBySearch.value.trim();
        const options = Array.from(document.querySelectorAll('#citizenOptions option'));
        const match = options.find(option => option.value === selectedLabel);
        reportedByUserId.value = match ? (match.dataset.userId || '') : '';
    }

    function toggleWalkInFields() {
        const isWalkIn = Boolean(reportedByWalkIn?.checked);

        if (walkInFields) {
            walkInFields.style.display = isWalkIn ? 'block' : 'none';
        }

        if (reportedBySearch) {
            reportedBySearch.disabled = isWalkIn;
            if (isWalkIn) {
                reportedBySearch.value = '';
            }
        }

        if (reportedByUserId && isWalkIn) {
            reportedByUserId.value = '';
        }
    }
    
    // Function to check selected category and show appropriate fields
    function checkCategoryFields() {
        if (categorySelect) {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption?.getAttribute('data-category-name') || '';
            const categoryEnum = selectedOption?.getAttribute('data-category-enum') || categorySelect.value || '';
            
            console.log('Selected category:', categoryName);
            
            // Hide all field groups first
            if (stationFields) stationFields.classList.remove('show');
            if (hydrantFields) hydrantFields.classList.remove('show');
            if (schoolFields) schoolFields.classList.remove('show');
            if (barangayHallFields) barangayHallFields.classList.remove('show');
            if (incidentFields) incidentFields.classList.remove('show');
            
            // Remove required attributes from all
            if (document.getElementById('stationName')) document.getElementById('stationName').required = false;
            if (document.getElementById('stationCode')) document.getElementById('stationCode').required = false;
            if (document.getElementById('stationContact')) document.getElementById('stationContact').required = false;
            if (document.getElementById('stationEmail')) document.getElementById('stationEmail').required = false;
            if (document.getElementById('hydrantCode')) document.getElementById('hydrantCode').required = false;
            if (document.getElementById('hydrantStation')) document.getElementById('hydrantStation').required = false;
            if (document.getElementById('schoolName')) document.getElementById('schoolName').required = false;
            if (document.getElementById('barangayHallName')) document.getElementById('barangayHallName').required = false;
            if (document.getElementById('barangayHallBarangay')) document.getElementById('barangayHallBarangay').required = false;
            if (reportedBySearch) reportedBySearch.required = false;
            if (document.getElementById('walkInFirstName')) document.getElementById('walkInFirstName').required = false;
            if (document.getElementById('walkInLastName')) document.getElementById('walkInLastName').required = false;
            if (document.getElementById('walkInContactNumber')) document.getElementById('walkInContactNumber').required = false;
            
            // Check for Fire Station
            if (categoryEnum === 'station' || categoryName.toLowerCase() === 'fire station') {
                console.log('Showing station fields');
                if (stationFields) stationFields.classList.add('show');
                if (document.getElementById('stationName')) document.getElementById('stationName').required = true;
                if (document.getElementById('stationCode')) document.getElementById('stationCode').required = true;
                if (document.getElementById('stationContact')) document.getElementById('stationContact').required = true;
                if (document.getElementById('stationEmail')) document.getElementById('stationEmail').required = true;
            }
            // Check for Fire Hydrant
            else if (categoryEnum === 'hydrant' || categoryName.toLowerCase() === 'hydrant') {
                console.log('Showing hydrant fields');
                if (hydrantFields) hydrantFields.classList.add('show');
                if (document.getElementById('hydrantCode')) document.getElementById('hydrantCode').required = true;
                if (document.getElementById('hydrantStation')) document.getElementById('hydrantStation').required = true;
            }
            // Check for School
            else if (categoryEnum === 'school' || categoryName.toLowerCase() === 'school') {
                console.log('Showing school fields');
                if (schoolFields) schoolFields.classList.add('show');
                if (document.getElementById('schoolName')) document.getElementById('schoolName').required = true;
            }
            else if (categoryEnum === 'barangay' || categoryName.toLowerCase() === 'barangay hall') {
                console.log('Showing barangay hall fields');
                if (barangayHallFields) barangayHallFields.classList.add('show');
                if (document.getElementById('barangayHallName')) document.getElementById('barangayHallName').required = true;
                if (document.getElementById('barangayHallBarangay')) document.getElementById('barangayHallBarangay').required = true;
            }
            else if (categoryEnum === 'fire') {
                if (incidentFields) incidentFields.classList.add('show');
                if (reportedByWalkIn?.checked) {
                    if (document.getElementById('walkInFirstName')) document.getElementById('walkInFirstName').required = true;
                    if (document.getElementById('walkInLastName')) document.getElementById('walkInLastName').required = true;
                    if (document.getElementById('walkInContactNumber')) document.getElementById('walkInContactNumber').required = true;
                } else if (reportedBySearch) {
                    reportedBySearch.required = true;
                }
            }
        }
    }
    
    // Add event listener to category select
    if (categorySelect) {
        categorySelect.addEventListener('change', checkCategoryFields);
    }
    if (reportedBySearch) {
        reportedBySearch.addEventListener('input', syncCitizenSearchSelection);
        reportedBySearch.addEventListener('change', syncCitizenSearchSelection);
    }
    if (reportedByWalkIn) {
        reportedByWalkIn.addEventListener('change', function() {
            toggleWalkInFields();
            checkCategoryFields();
        });
    }
    toggleWalkInFields();
    
    // Function to generate station code preview
    function generateStationCodePreview() {
        const stationCodeInput = document.getElementById('stationCode');
        const previewDiv = document.getElementById('stationCodePreview');
        const previewCode = document.getElementById('previewCode');
        
        if (stationCodeInput && stationCodeInput.value) {
            previewCode.textContent = stationCodeInput.value.toUpperCase();
            previewDiv.style.display = 'block';
        } else if (previewDiv) {
            previewDiv.style.display = 'none';
        }
    }
    
    // Function to generate hydrant code preview
    function generateHydrantCodePreview() {
        const hydrantCodeInput = document.getElementById('hydrantCode');
        const previewDiv = document.getElementById('hydrantCodePreview');
        const previewCode = document.getElementById('previewHydrantCode');
        
        if (hydrantCodeInput && hydrantCodeInput.value) {
            previewCode.textContent = hydrantCodeInput.value.toUpperCase();
            previewDiv.style.display = 'block';
        } else if (previewDiv) {
            previewDiv.style.display = 'none';
        }
    }

    function focusOnMarker(lat, lng) {
        if (window.map) {
            window.map.setView([lat, lng], 16);
            if (typeof window.closeChiefAlertsPanel === 'function') {
                window.closeChiefAlertsPanel({ restoreFocus: false });
            } else {
                document.getElementById('alertsDropdown')?.classList.remove('show');
                document.getElementById('alertsDropdown')?.classList.add('hidden');
            }
        }
    }

    function markAllAsRead() {
        if (typeof window.markAllIncidentAlertsRead === 'function') {
            window.markAllIncidentAlertsRead();
        }
        if (typeof window.closeChiefAlertsPanel === 'function') {
            window.closeChiefAlertsPanel({ restoreFocus: false });
        } else {
            document.getElementById('alertsDropdown')?.classList.remove('show');
            document.getElementById('alertsDropdown')?.classList.add('hidden');
        }
    }

    function openModal(options = {}) {
        if (options.fromMapClick) {
            window.disableMarkerPlacementSelection?.({ clearCoordinates: false });
        } else {
            window.removeMapPlacementMarker?.({ clearCoordinates: true });
            window.enableMarkerPlacementSelection?.();
        }
        const modal = document.getElementById('markerModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('is-open', 'active');
            modal.setAttribute('aria-hidden', 'false');
        }
        
        // Reset all fields when opening modal
        if (stationFields) stationFields.classList.remove('show');
        if (hydrantFields) hydrantFields.classList.remove('show');
        if (schoolFields) schoolFields.classList.remove('show');
        if (barangayHallFields) barangayHallFields.classList.remove('show');
        if (incidentFields) incidentFields.classList.remove('show');
        
        // Clear all inputs
        const inputs = modal.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.id !== 'mCategory' && input.id !== 'defaultIncidentTeamId') {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            }
        });

        toggleWalkInFields();
        syncBarangayHallCaptainOptions('');
        syncCitizenSearchSelection();
        checkCategoryFields();
        
        // Reset previews
        const stationPreview = document.getElementById('stationCodePreview');
        const hydrantPreview = document.getElementById('hydrantCodePreview');
        if (stationPreview) stationPreview.style.display = 'none';
        if (hydrantPreview) hydrantPreview.style.display = 'none';
    }

    function openIconModal() {
        const modal = document.getElementById('iconModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeIconModal() {
        const modal = document.getElementById('iconModal');
        if (modal) modal.style.display = 'none';
    }

    function closeModal(options = {}) {
        const modal = document.getElementById('markerModal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('is-open', 'active');
            modal.setAttribute('aria-hidden', 'true');
        }
        if (!options.skipPlacementCleanup) {
            window.disableMarkerPlacementSelection?.({
                clearCoordinates: options.keepCoordinates !== true
            });
        }
    }

    const markerModalCleanupTarget = document.getElementById('markerModal');
    if (markerModalCleanupTarget && !markerModalCleanupTarget.dataset.markerCleanupBound) {
        markerModalCleanupTarget.dataset.markerCleanupBound = 'true';

        markerModalCleanupTarget.addEventListener('click', (event) => {
            if (event.target === markerModalCleanupTarget) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            const isMarkerModalOpen =
                markerModalCleanupTarget.classList.contains('is-open') ||
                markerModalCleanupTarget.classList.contains('active') ||
                markerModalCleanupTarget.style.display !== 'none';

            if (isMarkerModalOpen) {
                closeModal();
            }
        });
    }

    function openAddCategory() {
        const modal = document.getElementById('iconModal');
        if (modal) {
            modal.style.display = 'flex';
            return;
        }
        console.warn('openAddCategory called but no category modal exists on chief dashboard.');
    }

    function closeAddCategory() {
        const modal = document.getElementById('iconModal');
        if (modal) {
            modal.style.display = 'none';
            return;
        }
        console.warn('closeAddCategory called but no category modal exists on chief dashboard.');
    }

    async function emergencyCall() {
        const shouldCall = typeof window.showMarkerConfirm === 'function'
            ? await window.showMarkerConfirm({
                title: 'Call emergency?',
                message: 'Open your phone dialer for 911.',
                confirmText: 'Call 911',
                cancelText: 'Cancel'
            })
            : true;

        if (shouldCall) {
            window.location.href = 'tel:911';
        }
    }

    async function refreshChiefDashboardStats() {
        if (chiefStatsRequest) {
            return;
        }

        try {
            chiefStatsRequest = createChiefDashboardRequest(chiefStatsRequest, '/station-admin/api/dashboard-stats', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const response = await chiefStatsRequest.promise;

            if (!response.ok) {
                throw new Error(`Failed to load dashboard stats (${response.status})`);
            }

            const payload = await response.json();
            const stats = payload?.data || {};
            applyChiefDashboardStats(stats);
            writeChiefDashboardStatsCache(stats);
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }
            console.error('Error refreshing chief dashboard stats:', error);
        } finally {
            chiefStatsRequest = null;
        }
    }

    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const openBtn = document.getElementById('openSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (openBtn && sidebar && backdrop) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('open');
                backdrop.classList.remove('hidden');
                document.body.classList.add('menu-open');
            });
        }

        if (backdrop && sidebar) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
            });
        }

        const cachedStats = readChiefDashboardStatsCache();
        if (cachedStats) {
            applyChiefDashboardStats(cachedStats);
        } else if (window.chiefDashboardStatsSeed) {
            applyChiefDashboardStats(window.chiefDashboardStatsSeed);
        }

        queueChiefDashboardBootTask(() => refreshChiefDashboardStats(), 180);
    });
    
    // Make functions globally available
    window.refreshEquipment = refreshEquipment;
    window.focusOnMarker = focusOnMarker;
    window.markAllAsRead = markAllAsRead;
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.openAddCategory = openAddCategory;
    window.closeAddCategory = closeAddCategory;
    window.emergencyCall = emergencyCall;
    window.generateStationCodePreview = generateStationCodePreview;
    window.generateHydrantCodePreview = generateHydrantCodePreview;
    window.refreshChiefDashboardStats = refreshChiefDashboardStats;
    window.CHIEF_STATS_INTERVAL_MS = 30000;
    window.CHIEF_AUTO_ASSIGN_INTERVAL_MS = 60000;
    window.CHIEF_NOTIFICATION_INTERVAL_MS = 15000;
    window.chiefStatsInterval = null;
    window.chiefAutoAssignInterval = null;
    window.chiefNotificationInterval = null;
    window.chiefDashboardIsVisible = function() {
        return document.visibilityState === 'visible';
    };
    window.clearChiefDashboardIntervals = function() {
        const runtime = window.__chiefDashboardPollingRuntime || null;

        if (window.chiefStatsInterval) {
            clearInterval(window.chiefStatsInterval);
            window.chiefStatsInterval = null;
        }
        if (window.chiefAutoAssignInterval) {
            clearInterval(window.chiefAutoAssignInterval);
            window.chiefAutoAssignInterval = null;
        }
        if (window.chiefNotificationInterval) {
            clearInterval(window.chiefNotificationInterval);
            window.chiefNotificationInterval = null;
        }

        if (runtime) {
            runtime.pollingStarted = false;
        }
    };
    window.startChiefDashboardIntervals = function() {
        const runtime = window.__chiefDashboardPollingRuntime || { pollingStarted: false };

        if (!window.chiefDashboardIsVisible() || runtime.pollingStarted) {
            return;
        }

        window.clearChiefDashboardIntervals();
        runtime.pollingStarted = true;

        window.chiefStatsInterval = window.setInterval(() => {
            if (!window.chiefDashboardIsVisible()) return;
            refreshChiefDashboardStats();
        }, window.CHIEF_STATS_INTERVAL_MS);

        window.chiefAutoAssignInterval = window.setInterval(() => {
            if (!window.chiefDashboardIsVisible()) return;
            window.runPendingIncidentAutoAssignment();
        }, window.CHIEF_AUTO_ASSIGN_INTERVAL_MS);

        window.chiefNotificationInterval = window.setInterval(() => {
            if (!window.chiefDashboardIsVisible()) return;
            window.refreshChiefIncidentNotifications();
        }, window.CHIEF_NOTIFICATION_INTERVAL_MS);
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Add this at the end of your dashboard.blade.php, before closing body tag -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

{{-- @vite(['resources/js/map/UnifiedMapService.js']) --}}

<script>
    const CHIEF_DASHBOARD_FETCH_TIMEOUT_MS = 10000;
    const CHIEF_DASHBOARD_STATS_CACHE_KEY = 'pyrosense:chief-dashboard:stats';
    let chiefAutoAssignRequest = null;
    let chiefLatestIncidentRequest = null;
    let chiefStatsRequest = null;

    function createChiefDashboardRequest(currentRequest, url, options = {}) {
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), CHIEF_DASHBOARD_FETCH_TIMEOUT_MS);
        const promise = fetch(url, {
            cache: 'no-store',
            ...options,
            signal: controller.signal
        }).finally(() => {
            window.clearTimeout(timeoutId);
        });

        return { controller, promise };
    }

    function getChiefDashboardPollingRuntime() {
        if (!window.__chiefDashboardPollingRuntime) {
            window.__chiefDashboardPollingRuntime = {
                initialized: false,
                pollingStarted: false,
                listenersBound: false,
                bootTimeoutIds: new Set(),
                idleCallbackIds: new Set(),
                listeners: {}
            };
        }

        return window.__chiefDashboardPollingRuntime;
    }

    function abortChiefDashboardRequest(request) {
        try {
            request?.controller?.abort();
        } catch (error) {
            console.warn('Unable to abort chief dashboard request cleanly:', error);
        }
    }

    function applyChiefDashboardStats(stats = {}) {
        const incidentTotal = document.getElementById('chiefIncidentTotal');
        const activeResponses = document.getElementById('chiefActiveResponses');
        const fsicClaims = document.getElementById('chiefFsicClaims');
        const crewOnline = document.getElementById('chiefCrewOnline');

        if (incidentTotal) incidentTotal.textContent = stats.incident_total ?? '0';
        if (activeResponses) activeResponses.textContent = stats.active_responses ?? '0';
        if (fsicClaims) fsicClaims.textContent = stats.fsic_claims ?? '0';
        if (crewOnline) crewOnline.textContent = stats.crew_online ?? '0';
    }

    function readChiefDashboardStatsCache() {
        try {
            const raw = window.sessionStorage?.getItem(CHIEF_DASHBOARD_STATS_CACHE_KEY);
            if (!raw) {
                return null;
            }

            const parsed = JSON.parse(raw);
            if (!parsed?.stats || typeof parsed.stats !== 'object') {
                return null;
            }

            if ((Date.now() - Number(parsed.saved_at || 0)) > 120000) {
                return null;
            }

            return parsed.stats;
        } catch (error) {
            console.warn('Unable to read chief dashboard stats cache:', error);
            return null;
        }
    }

    function writeChiefDashboardStatsCache(stats = {}) {
        try {
            window.sessionStorage?.setItem(CHIEF_DASHBOARD_STATS_CACHE_KEY, JSON.stringify({
                stats,
                saved_at: Date.now()
            }));
            window.pyroPwa?.updatePageSnapshot?.({
                key: 'chief-dashboard',
                label: 'Chief Command Dashboard'
            });
        } catch (error) {
            console.warn('Unable to write chief dashboard stats cache:', error);
        }
    }

    function cancelChiefDashboardBootTasks() {
        const runtime = getChiefDashboardPollingRuntime();

        runtime.bootTimeoutIds.forEach((timeoutId) => window.clearTimeout(timeoutId));
        runtime.bootTimeoutIds.clear();

        if (typeof window.cancelIdleCallback === 'function') {
            runtime.idleCallbackIds.forEach((idleId) => window.cancelIdleCallback(idleId));
        }
        runtime.idleCallbackIds.clear();
    }

    function queueChiefDashboardBootTask(callback, delayMs = 0) {
        const runtime = getChiefDashboardPollingRuntime();
        const timeoutId = window.setTimeout(() => {
            runtime.bootTimeoutIds.delete(timeoutId);

            if (typeof window.requestIdleCallback === 'function') {
                const idleId = window.requestIdleCallback(() => {
                    runtime.idleCallbackIds.delete(idleId);
                    callback();
                }, { timeout: 1500 });

                runtime.idleCallbackIds.add(idleId);
                return;
            }

            callback();
        }, delayMs);

        runtime.bootTimeoutIds.add(timeoutId);
        return timeoutId;
    }

    window.runPendingIncidentAutoAssignment = async function() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            return;
        }

        if (chiefAutoAssignRequest) {
            return;
        }

        try {
            chiefAutoAssignRequest = createChiefDashboardRequest(chiefAutoAssignRequest, '/api/incidents/auto-assign-recent-unassigned', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const response = await chiefAutoAssignRequest.promise;

            const result = await response.json().catch(() => ({}));
            if (result?.success && Number(result.assigned) > 0) {
                await Promise.allSettled([
                    refreshChiefDashboardStats(),
                    window.refreshChiefIncidentNotifications(),
                    typeof window.refreshEquipment === 'function' ? window.refreshEquipment() : Promise.resolve()
                ]);
            }
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }
            console.error('Chief dashboard auto-assignment failed:', error);
        } finally {
            chiefAutoAssignRequest = null;
        }
    };

    window.refreshChiefIncidentNotifications = async function() {
        if (chiefLatestIncidentRequest) {
            return;
        }

        try {
            chiefLatestIncidentRequest = createChiefDashboardRequest(chiefLatestIncidentRequest, '/api/incident/latest', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            const response = await chiefLatestIncidentRequest.promise;

            if (!response.ok) {
                throw new Error(`Failed to refresh chief incident notifications (${response.status})`);
            }

            const payload = await response.json();
            const incidents = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            window.incidentsData = incidents;

            if (typeof window.syncIncidentAlertFeed === 'function') {
                window.syncIncidentAlertFeed('map-chief-incidents', incidents, {
                    titlePrefix: 'Command Alert'
                });
            }
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }
            console.error('Chief incident notification refresh failed:', error);
        } finally {
            chiefLatestIncidentRequest = null;
        }
    };

    function abortChiefDashboardPollingRequests() {
        abortChiefDashboardRequest(chiefStatsRequest);
        abortChiefDashboardRequest(chiefAutoAssignRequest);
        abortChiefDashboardRequest(chiefLatestIncidentRequest);
    }

    function handleChiefDashboardVisibilityChange() {
        const runtime = getChiefDashboardPollingRuntime();

        if (!window.chiefDashboardIsVisible()) {
            cancelChiefDashboardBootTasks();
            window.clearChiefDashboardIntervals();
            runtime.pollingStarted = false;
            return;
        }

        refreshChiefDashboardStats();
        queueChiefDashboardBootTask(() => window.refreshChiefIncidentNotifications(), 0);
        queueChiefDashboardBootTask(() => window.startChiefDashboardIntervals(), 300);
    }

    function teardownChiefDashboardPolling() {
        const runtime = getChiefDashboardPollingRuntime();

        cancelChiefDashboardBootTasks();
        window.clearChiefDashboardIntervals();
        abortChiefDashboardPollingRequests();
        runtime.pollingStarted = false;

        if (runtime.listenersBound) {
            const { visibilitychange, pagehide, beforeunload } = runtime.listeners;

            if (visibilitychange) {
                document.removeEventListener('visibilitychange', visibilitychange);
            }
            if (pagehide) {
                window.removeEventListener('pagehide', pagehide);
            }
            if (beforeunload) {
                window.removeEventListener('beforeunload', beforeunload);
            }

            runtime.listenersBound = false;
        }

        runtime.listeners = {};
        runtime.initialized = false;
    }

    function initializeChiefDashboardPolling() {
        const runtime = getChiefDashboardPollingRuntime();

        if (runtime.initialized) {
            return runtime;
        }

        runtime.initialized = true;

        if (!runtime.listenersBound) {
            runtime.listeners.visibilitychange = handleChiefDashboardVisibilityChange;
            runtime.listeners.pagehide = teardownChiefDashboardPolling;
            runtime.listeners.beforeunload = teardownChiefDashboardPolling;

            document.addEventListener('visibilitychange', runtime.listeners.visibilitychange);
            window.addEventListener('pagehide', runtime.listeners.pagehide);
            window.addEventListener('beforeunload', runtime.listeners.beforeunload);

            runtime.listenersBound = true;
        }

        return runtime;
    }

    // Store existing map reference for UnifiedMapService
    window.unifiedMap = null;
    
    // Initialize UnifiedMapService AFTER your existing map is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeChiefDashboardPolling();
        queueChiefDashboardBootTask(() => window.refreshChiefIncidentNotifications(), 700);
        queueChiefDashboardBootTask(() => window.runPendingIncidentAutoAssignment(), 1400);
        queueChiefDashboardBootTask(() => window.startChiefDashboardIntervals(), 2200);

        // Sidebar Mobile Toggle
        const openSidebarBtn = document.getElementById('openSidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        
        if (openSidebarBtn && sidebar && sidebarBackdrop) {
            openSidebarBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarBackdrop.classList.remove('hidden');
            });
            
            sidebarBackdrop.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarBackdrop.classList.add('hidden');
            });
        }

        // Wait for your existing map to be initialized
        let mapWaitAttempts = 0;
        const maxMapWaitAttempts = 15;
        const checkMap = setInterval(function() {
            mapWaitAttempts += 1;

            if (window.map && window.map._container) {
                clearInterval(checkMap);
                setTimeout(function() {
                    if (window.map && typeof window.map.invalidateSize === 'function') {
                        window.map.invalidateSize();
                    }
                }, 200);
                console.log('Existing map found, initializing UnifiedMapService...');
                
                if (typeof UnifiedMapService !== 'undefined') {
                    window.unifiedMap = new UnifiedMapService();
                    window.unifiedMap.setupOnExistingMap();
                    console.log('UnifiedMapService integrated with existing map');
                }
                return;
            }

            if (mapWaitAttempts >= maxMapWaitAttempts) {
                clearInterval(checkMap);
                console.warn('Chief dashboard map initialization timed out; continuing without UnifiedMapService.');
            }
        }, 1000);
    });
    
    window.showChiefRouteToast = function(message, state = 'loading') {
        let toast = document.getElementById('chiefRouteToast');

        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'chiefRouteToast';
            toast.className = 'chief-route-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.innerHTML = `
                <span class="chief-route-toast__spinner" aria-hidden="true"></span>
                <span class="chief-route-toast__icon" aria-hidden="true"></span>
                <span class="chief-route-toast__copy"></span>
            `;
            document.body.appendChild(toast);
        }

        toast.classList.remove('chief-route-toast--loading', 'chief-route-toast--success', 'chief-route-toast--error');
        toast.classList.add('is-visible', `chief-route-toast--${state}`);
        toast.querySelector('.chief-route-toast__copy').textContent = message || 'Loading route...';

        window.clearTimeout(window.__chiefRouteToastTimer);
        if (state !== 'loading') {
            window.__chiefRouteToastTimer = window.setTimeout(() => {
                toast.classList.remove('is-visible');
            }, 2600);
        }
    };

    function installChiefRouteFeedbackWrappers() {
        const wrapRouteFunction = function(name) {
            const routeFn = window[name];

            if (typeof routeFn !== 'function' || routeFn.__chiefRouteFeedbackWrapped) {
                return;
            }

            const wrapped = function(...args) {
                const source = args[2]?.source === 'demo' || name === 'calculateDemoRouteToIncident'
                    ? 'demo'
                    : 'real';
                const loadingMessage = source === 'demo' ? 'Loading demo route...' : 'Loading directions...';
                const successMessage = source === 'demo' ? 'Demo route ready.' : 'Directions ready.';

                window.showChiefRouteToast(loadingMessage, 'loading');

                let result;
                try {
                    result = routeFn.apply(this, args);
                } catch (error) {
                    window.showChiefRouteToast('Route failed. Please try again.', 'error');
                    throw error;
                }

                if (result && typeof result.then === 'function') {
                    return result
                        .then((value) => {
                            if (value === false || value?.ok === false) {
                                window.showChiefRouteToast('Route could not be displayed.', 'error');
                                return value;
                            }

                            window.showChiefRouteToast(successMessage, 'success');
                            return value;
                        })
                        .catch((error) => {
                            window.showChiefRouteToast('Route failed. Please try again.', 'error');
                            throw error;
                        });
                }

                window.setTimeout(() => {
                    window.showChiefRouteToast(
                        result === false || result?.ok === false ? 'Route could not be displayed.' : successMessage,
                        result === false || result?.ok === false ? 'error' : 'success'
                    );
                }, 700);

                return result;
            };

            wrapped.__chiefRouteFeedbackWrapped = true;
            wrapped.__chiefRouteOriginal = routeFn;
            window[name] = wrapped;
        };

        wrapRouteFunction('calculateRouteToIncident');
        wrapRouteFunction('calculateDemoRouteToIncident');
        wrapRouteFunction('calculateRouteToStation');
    }

    document.addEventListener('DOMContentLoaded', function() {
        installChiefRouteFeedbackWrappers();

        let routeWrapAttempts = 0;
        const routeWrapTimer = window.setInterval(function() {
            installChiefRouteFeedbackWrappers();
            routeWrapAttempts += 1;

            if (routeWrapAttempts >= 12) {
                window.clearInterval(routeWrapTimer);
            }
        }, 500);
    });

    // Keep map.js route handlers intact. These fallbacks only run if the shared route module is absent.
    if (typeof window.calculateRouteToStation !== 'function') {
        window.calculateRouteToStation = function(stationId, stationName, lat, lng) {
            if (window.unifiedMap) {
                window.unifiedMap.calculateRouteToStation(stationId, stationName, lat, lng);
            }
        };
    }

    if (typeof window.calculateRouteToIncident !== 'function') {
        window.calculateRouteToIncident = function(incidentId) {
            if (window.unifiedMap) {
                window.unifiedMap.calculateRouteToIncident(incidentId);
            }
        };
    }

    if (typeof window.stopSimulation !== 'function') {
        window.stopSimulation = async function() {
            if (typeof window.stopAllSimulations === 'function') {
                await window.stopAllSimulations();
                return;
            }

            if (window.unifiedMap) {
                window.unifiedMap.stopSimulation();
            }
        };
    }

    window.refreshMap = function() {
        if (window.map && typeof window.map.invalidateSize === 'function') {
            window.map.invalidateSize();
        }
    };
</script>
@include('partials.pwa-body')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.pyroPwa?.registerPageSnapshot?.({
        key: 'chief-dashboard',
        label: 'Chief Command Dashboard'
    });
});
</script>
@include('chief.partials.logout-confirmation')
</body>
</html>







