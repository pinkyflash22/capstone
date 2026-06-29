{{-- resources/views/firefighter/dashboard.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'firefighter' }}">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? '' }}">
    <meta name="station-id" content="{{ $thisStation ?? '' }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <title>PyroSense · Firefighter Dashboard</title>
    
    @vite(['resources/css/app.css', 'resources/css/firefighters/dashboard.css'])
    
    <style>
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .legend i {
            margin-right: 8px;
            width: 14px;
            display: inline-block;
        }
        
        .leaflet-div-icon {
            background: transparent;
            border: none;
        }

        .firefighter-mobile-welcome-toast,
        .firefighter-mobile-insights-panel {
            display: none;
        }

        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }

            .lector-top-bar {
                display: none !important;
            }

            .firefighter-mobile-welcome-toast {
                position: fixed;
                top: calc(env(safe-area-inset-top, 0px) + 12px);
                left: 12px;
                right: 12px;
                z-index: 320;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 16px;
                border-radius: 22px;
                background: rgba(255, 251, 235, 0.96);
                border: 1px solid rgba(245, 158, 11, 0.18);
                box-shadow: 0 18px 36px rgba(15, 23, 42, 0.10);
                opacity: 0;
                transform: translateY(-8px);
                pointer-events: none;
                transition: opacity 0.24s ease, transform 0.24s ease;
            }

            .firefighter-mobile-welcome-toast.is-visible {
                opacity: 1;
                transform: translateY(0);
            }

            .firefighter-mobile-welcome-toast__icon {
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

            .firefighter-mobile-welcome-toast__copy {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 0;
            }

            .firefighter-mobile-welcome-toast__copy strong {
                color: #9a3412;
                font-size: 0.92rem;
                line-height: 1.2;
                font-weight: 800;
            }

            .firefighter-mobile-welcome-toast__copy span {
                color: #b45309;
                font-size: 0.76rem;
                line-height: 1.35;
            }

            .firefighter-dashboard-welcome,
            [data-ff-insights-source] {
                display: none !important;
            }

            .firefighter-map-stage {
                width: 100vw;
                max-width: 100vw;
                margin: calc(-1 * env(safe-area-inset-top, 0px)) 0 0 calc(50% - 50vw) !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }

            .firefighter-map-stage__header,
            .firefighter-map-stage__legend {
                display: none !important;
            }

            .firefighter-map-stage .map-container {
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: hidden;
            }

            #firefighterDashboardMap {
                width: 100vw !important;
                height: calc(100svh - (92px + env(safe-area-inset-bottom, 0px)) + env(safe-area-inset-top, 0px)) !important;
                min-height: calc(100svh - (92px + env(safe-area-inset-bottom, 0px)) + env(safe-area-inset-top, 0px)) !important;
            }

            .firefighter-mobile-insights-panel {
                position: fixed;
                inset: 0;
                z-index: 9996;
            }

            .firefighter-mobile-insights-panel[aria-hidden="true"] {
                display: none;
            }

            .firefighter-mobile-insights-panel[aria-hidden="false"] {
                display: block;
            }

            .firefighter-mobile-insights-panel__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                backdrop-filter: blur(8px);
            }

            .firefighter-mobile-insights-panel__sheet {
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, #fffaf4 0%, #fbf7ef 100%);
                padding: calc(18px + env(safe-area-inset-top, 0px)) 18px calc(22px + env(safe-area-inset-bottom, 0px));
                overflow-y: auto;
            }

            .firefighter-mobile-insights-panel__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 18px;
            }

            .firefighter-mobile-insights-panel__eyebrow {
                margin: 0 0 4px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #d18a16;
            }

            .firefighter-mobile-insights-panel__title {
                margin: 0;
                font-size: 1.55rem;
                line-height: 1.1;
                font-weight: 800;
                color: #1e293b;
            }

            .firefighter-mobile-insights-panel__close {
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

            .firefighter-mobile-insights-panel__content [data-ff-insights-source] {
                display: block !important;
                margin: 0 0 18px !important;
            }

            .firefighter-mobile-insights-panel__content .kpi-grid,
            .firefighter-mobile-insights-panel__content .grid-4,
            .firefighter-mobile-insights-panel__content .grid-2 {
                grid-template-columns: 1fr !important;
            }

            .firefighter-mobile-insights-panel__content .text-center.text-xs.text-gray-400.mt-8.border-t.pt-4 {
                margin-top: 0 !important;
            }
        }

    </style>
    @include('partials.pwa-head')
</head>
<body>
    @include('firefighters.partials.shell-sidebar', [
        'active' => 'dashboard',
        'sidebarId' => 'sidebar',
        'backdropId' => 'sidebarBackdrop',
    ])
    {{--
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
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Operations</p>
            <a href="{{ route('firefighter.dashboard') }}" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('firefighter.incidents') }}" class="nav-item">
                <i class="fas fa-fire-extinguisher"></i>
                <span>Incidents</span>
                @if($incidentCoords->count() > 0)
                <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $incidentCoords->count() }}</span>
                @endif
            </a>
            <a href="{{ route('firefighter.my-station') }}" class="nav-item">
                <i class="fas fa-building"></i>
                <span>My Station</span>
            </a>
            <a href="{{ route('firefighter.equipments') }}" class="nav-item">
                <i class="fas fa-toolbox"></i>
                <span>Equipment</span>
            </a>

            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-4">Communications</p>
            <a href="{{ route('firefighter.announcements') }}" class="nav-item">
                <i class="fas fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="{{ route('firefighter.schedule') }}" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>My Schedule</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-100">
            <div class="bg-red-50/50 rounded-2xl p-4 border border-red-100/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold border border-red-200">
                        {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name }}</p>
                        <p class="text-[10px] text-slate-500 font-medium">On Duty • Station 7</p>
                    </div>
                </div>
            </div>
        </div>

    --}}
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div id="firefighterMobileWelcomeToast" class="firefighter-mobile-welcome-toast md:hidden" role="status" aria-live="polite">
            <div class="firefighter-mobile-welcome-toast__icon">
                <i class="fas fa-fire"></i>
            </div>
            <div class="firefighter-mobile-welcome-toast__copy">
                <strong>Welcome back, {{ auth()->user()->first_name }}!</strong>
                <span>Responder dashboard is ready with your map, incidents, and stats.</span>
            </div>
        </div>
        <!-- TOP NAVIGATIONBAR -->
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Responder Command</h1>
                    <p class="text-xs text-slate-500 font-medium">Ready for Duty • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Status Pill -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-green-50 rounded-full border border-green-100">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">On Duty</span>
                </div>

                <!-- User Profile Dropdown -->
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-red-200">
                            {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('firefighter.my-profile') }}" class="user-dropdown-item">
                                <i class="far fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#" class="user-dropdown-item" onclick="openMyLogsModal(); return false;">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>My Logs</span>
                            </a>
                            <div class="h-px bg-slate-100 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Welcome Banner -->
        <div class="bg-red-50 border-l-4 border-red-500 rounded-2xl p-8 mb-8 flex items-start justify-between firefighter-dashboard-welcome" data-ff-insights-source>
            <div>
                <h1 class="text-2xl font-bold text-red-900 mb-2">Welcome back, {{ auth()->user()->first_name }}!</h1>
                <p class="text-red-700 font-medium">Station 7 Operations • {{ now()->format('l, F j, Y') }}</p>
            </div>
            <div class="hidden md:flex items-center gap-4 bg-white/50 p-2 rounded-xl border border-red-100">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">System Status</p>
                    <p class="text-xs font-bold text-green-600">FULLY OPERATIONAL</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>
        <!-- Stats Grid -->
        <div class="kpi-grid" data-ff-insights-source>
            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="kpi-info">
                    <h3>Active Incidents</h3>
                    <p id="activeIncidentsCount">{{ $incidentCoords->count() }}</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="kpi-info">
                    <h3>Assigned to Me</h3>
                    <p id="assignedIncidentsCount">0</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-toolbox"></i>
                </div>
                <div class="kpi-info">
                    <h3>Equipment</h3>
                    <p>12</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-info">
                    <h3>Crew Online</h3>
                    <p>{{ $onlineCrewCount ?? 8 }}</p>
                </div>
            </div>
        </div>
         <!-- Map Section -->
        <div class="lector-card mb-8 firefighter-map-stage">
            <div class="flex items-center justify-between mb-6 firefighter-map-stage__header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 border border-red-100">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Operational Map</h2>
                        <p class="text-xs text-slate-500 font-medium">Real-time incident response tracking</p>
                    </div>
                </div>
                <button id="refreshIncidents" class="px-3 py-1 bg-red-100 text-red-600 text-[10px] font-bold rounded-full uppercase tracking-wider border border-red-200 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i> Sync Data
                </button>
            </div>
            <div class="map-container overflow-hidden rounded-2xl border border-slate-200">
                <div id="firefighterDashboardMap" class="bg-gray-200" style="height: 500px;"></div>
            </div>
            <div id="firefighterMapLegend" class="firefighter-map-legend-panel firefighter-map-stage__legend"></div>
        </div>
        <!-- ASSIGNED INCIDENTS SECTION - DYNAMIC -->
        <div class="assigned-incidents-container" data-ff-insights-source>
            <div class="section-header">
                <h2>
                    <i class="fas fa-truck"></i>
                    My Assigned Incidents
                </h2>
                <button id="refreshAssignedIncidents" class="view-all">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div id="assignedIncidentsList" class="incident-list">
                <div class="empty-incidents">
                    <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                    <p>Loading assigned incidents...</p>
                </div>
            </div>
        </div>
<div id="routeInfoCard" style="display: none;">
        <h4><i class="fas fa-route"></i> Route Information</h4>
        <div class="info-row">
            <strong>Destination:</strong> <span id="destinationTitle">Loading...</span>
        </div>
        <div class="info-row">
            <strong>Distance:</strong> <span id="routeDistance">0 km</span>
        </div>
        <div class="info-row">
            <strong>ETA:</strong> <span id="routeETA">--:--</span>
        </div>
        <div class="info-row">
            <strong>Duration:</strong> <span id="routeDuration">0 min</span>
        </div>
        <div class="route-progress-container mt-3">
            <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
        </div>
        <div class="speed-control mt-2">
            <button class="speed-btn" data-speed="slow">🐢 Slow</button>
            <button class="speed-btn active" data-speed="normal">🚒 Normal</button>
            <button class="speed-btn" data-speed="fast">🐇 Fast</button>
        </div>
        <button class="btn btn-sm btn-outline mt-2 w-full" onclick="stopSimulation()">
            <i class="fas fa-stop"></i> Stop Simulation
        </button>
    </div>
        <!-- Route Information Card (hidden until route is calculated) -->
        {{-- <div id="routeInfoCard" class="route-info-card" style="display: none;">
            <div class="flex justify-between items-center">
                <h3 class="font-semibold">
                    <i class="fas fa-route text-blue-600"></i>
                    Active Route
                </h3>
                <button id="stopTracking" class="text-sm text-red-600 hover:text-red-700">
                    <i class="fas fa-stop"></i> Stop Tracking
                </button>
            </div>
            <div class="route-stats">
                <div class="route-stat">
                    <div class="route-stat-label">Distance</div>
                    <div class="route-stat-value" id="routeDistance">-- km</div>
                </div>
                <div class="route-stat">
                    <div class="route-stat-label">Duration</div>
                    <div class="route-stat-value" id="routeDuration">-- min</div>
                </div>
                <div class="route-stat">
                    <div class="route-stat-label">ETA</div>
                    <div class="route-stat-value" id="routeETA">--:--</div>
                </div>
            </div>
            <div class="travel-mode-buttons">
                <button class="travel-mode-btn active" data-mode="driving">
                    <i class="fas fa-car"></i> Driving
                </button>
                <button class="travel-mode-btn" data-mode="walking">
                    <i class="fas fa-walking"></i> Walking
                </button>
                <button class="travel-mode-btn" data-mode="cycling">
                    <i class="fas fa-bicycle"></i> Cycling
                </button>
            </div>
        </div> --}}

       
        <br>

<!-- Statistics Charts Section - One Row -->
<div class="grid-4" style="margin-top: 20px;" data-ff-insights-source>
    <!-- Incident Status Chart -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Incident Status</span>
            <div class="stat-icon blue">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
        <canvas id="incidentStatusChart" style="height: 200px; width: 100%;"></canvas>
        <div class="stat-trend mt-2">
            <i class="fas fa-chart-bar"></i> Current breakdown
        </div>
    </div>

    <!-- Response Time Trend Chart -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Response Time</span>
            <div class="stat-icon green">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <canvas id="responseTimeChart" style="height: 200px; width: 100%;"></canvas>
        <div class="stat-trend mt-2">
            <i class="fas fa-clock"></i> Last 7 days (minutes)
        </div>
    </div>

    <!-- Monthly Incidents Chart -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Monthly Incidents</span>
            <div class="stat-icon purple">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
        <canvas id="monthlyIncidentsChart" style="height: 200px; width: 100%;"></canvas>
        <div class="stat-trend mt-2">
            <i class="fas fa-bar-chart"></i> {{ now()->format('F Y') }}
        </div>
    </div>

    <!-- Performance Metrics Chart -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Performance</span>
            <div class="stat-icon red">
                <i class="fas fa-tachometer-alt"></i>
            </div>
        </div>
        <canvas id="performanceChart" style="height: 200px; width: 100%;"></canvas>
        <div class="stat-trend mt-2">
            <i class="fas fa-chart-line"></i> Success & completion
        </div>
    </div>
</div>
        {{-- <div class="section-header">
            <h2>
                <i class="fas fa-fire"></i>
                Active Incidents
            </h2>
            @if ($incidentCoords->count() > 2)
            <a href="" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            @endif
        </div> --}}

        {{-- <div class="grid-1">
            <div>Assigned Incidents</div>
            @foreach ($incidentCoords->take(2) as $incident)
            <div class="incident-card {{ strtolower($incident->priority ?? 'medium') }}">
                <div class="incident-header">
                    <span class="incident-type">
                        @if($incident->incidentType)
                            <i class="fas fa-fire"></i> {{ $incident->incidentType->type ?? 'Fire' }}
                        @else
                            <i class="fas fa-fire"></i> Fire
                        @endif
                    </span>
                    <span class="incident-priority priority-{{ strtolower($incident->priority ?? 'medium') }}">
                        {{ strtoupper($incident->priority ?? 'MEDIUM') }}
                    </span>
                </div>
                <h3 class="incident-title">{{ $incident->marker->title ?? 'Untitled Incident' }}</h3>
                <div class="incident-location">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $incident->marker->lat ?? 'N/A' }}, {{ $incident->marker->lng ?? 'N/A' }}
                </div>
                <div class="incident-meta">
                    <span><i class="far fa-clock"></i> {{ $incident->created_at->diffForHumans() }}</span>
                    <span><i class="fas fa-user"></i> {{ $incident->reporter ?? 'Dispatch' }}</span>
                </div>
                <div class="incident-actions">
                    <button class="btn btn-primary accept-incident" data-incident-id="{{ $incident->incident_id }}">
                        <i class="fas fa-check"></i> Accept & Navigate
                    </button>
                    <button class="btn btn-outline view-incident" data-incident-id="{{ $incident->incident_id }}">
                        <i class="far fa-eye"></i> Details
                    </button>
                </div>
            </div>
            @endforeach
        </div> --}}
        
        <!-- Announcements Section -->
        <div class="assigned-incidents-container" data-ff-insights-source>
            <div class="bg-white rounded-xl shadow p-5">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-bullhorn"></i>
                        Announcements
                    </h2>
                    <a href="{{ route('firefighter.announcements') }}" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    @forelse ($posts ?? [] as $post)
                        <div class="announcement-item">
                            <input type="hidden" name="post_id" id='post_id' value="{{ $post->id }}">
                            <div class="announcement-avatar">{{ substr($post->user->first_name ?? 'A', 0, 1) }}{{ substr($post->user->last_name ?? 'D', 0, 1) }}</div>
                            <div class="announcement-content">
                                <div class="announcement-header">
                                    <span class="announcement-author">{{ $post->user->first_name ?? 'Admin' }} {{ $post->user->last_name ?? 'Admin' }}</span>
                                    <span class="announcement-time">{{ $post->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="announcement-text">
                                    {{ $post->content }}
                                </div>
                                <div class="announcement-stats">
                                    <span><i class="far fa-thumbs-up"></i> {{ $post->likes_count ?? 0 }}</span>
                                    <span><i class="far fa-comment"></i> {{ $post->comments_count ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <i class="far fa-comment-dots text-3xl mb-2"></i>
                            <p>No announcements yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Stations and Crew Grid -->
        <div class="grid-2" style="margin-top: 30px;" data-ff-insights-source>
            <!-- Nearby Stations -->
            <div>
                <div class="section-header">
                    <h2>
                        <i class="fas fa-building"></i>
                        Nearby Stations
                    </h2>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div style="grid-template-columns: 1fr;">
                    @forelse ($stations ?? [] as $station)
                        <div class="station-card">
                            <div class="station-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="station-info">
                                <h3>{{ $station->station_name }}</h3>
                                <p><i class="fas fa-map-marker-alt"></i> {{ number_format($station->distance ?? 1.2, 1) }} km away</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <p>No stations found</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Online Crew -->
            <div>
                <div class="section-header">
                    <h2>
                        <i class="fas fa-users"></i>
                        Online Crew
                    </h2>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    @forelse ($thiscrew as $crew)
                        <div class="crew-item">
                            <div class="crew-avatar">
                                {{ substr($crew->user->first_name ?? 'F', 0, 1) }}{{ substr($crew->user->last_name ?? 'F', 0, 1) }}
                                <span class="crew-status {{ $crew->status ?? 'online' }}"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">{{ $crew->user->first_name ?? ' ' }}{{ $crew->user->last_name ?? 'Firefighter' }}</div>
                                <div class="crew-role">{{ $crew->rank ?? 'Firefighter' }} · Station {{$crew->station_id}}</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                    @empty
                        <div class="crew-item">
                            <div class="crew-avatar">
                                JD
                                <span class="crew-status online"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">John Doe</div>
                                <div class="crew-role">Firefighter · Station 7</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                        <div class="crew-item">
                            <div class="crew-avatar">
                                JS
                                <span class="crew-status online"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">Jane Smith</div>
                                <div class="crew-role">Lieutenant · Station 7</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 mt-8 border-t pt-4" data-ff-insights-source>
            <i class="fas fa-user mr-1"></i> PyroSense · Firefighter Portal · Responding since {{ now()->format('H:i') }}
        </div>
    </main>

    <div class="firefighter-mobile-insights-panel md:hidden" id="firefighterMobileInsightsPanel" aria-hidden="true">
        <div class="firefighter-mobile-insights-panel__backdrop" data-ff-insights-close></div>
        <div class="firefighter-mobile-insights-panel__sheet">
            <div class="firefighter-mobile-insights-panel__header">
                <div>
                    <p class="firefighter-mobile-insights-panel__eyebrow">Responder Hub</p>
                    <h2 class="firefighter-mobile-insights-panel__title">Cards and Charts</h2>
                </div>
                <button type="button" class="firefighter-mobile-insights-panel__close" data-ff-insights-close aria-label="Close cards and charts">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="firefighter-mobile-insights-panel__content" id="firefighterMobileInsightsContent"></div>
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Status</h3>
            <div class="status-options">
                <button class="status-btn on-duty" data-status="on-duty">
                    <i class="fas fa-circle" style="color: #22c55e;"></i>
                    On Duty
                </button>
                <button class="status-btn on-break" data-status="on-break">
                    <i class="fas fa-mug-hot" style="color: #f97316;"></i>
                    On Break
                </button>
                <button class="status-btn off-duty" data-status="off-duty">
                    <i class="fas fa-clock" style="color: #ef4444;"></i>
                    Off Duty
                </button>
            </div>
            <button class="btn btn-outline" style="width: 100%; margin-top: 20px;" onclick="document.getElementById('statusModal').classList.remove('active')">
                Cancel
            </button>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast" class="toast"></div>
    @vite(['resources/js/map.js'])

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Pass data to JavaScript
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.mapInstanceConfig = {
        containerId: 'firefighterDashboardMap',
        instanceKey: 'firefighterDashboard'
    };
    window.geoapifyKey = '{{ env("GEOAPIFY_KEY") }}';
    window.userId = {{ auth()->user()->user_id ?? 'null' }};
    window.userTeamId = {{ isset($userTeam) ? $userTeam->team_id : 'null' }};
    window.userStationId = {{ isset($thisStation) ? $thisStation : 'null' }};
    window.allMarkers = @json($allMarkers ?? []);
    window.mapData = { marker: window.allMarkers };
    window.stationsData = @json($stations ?? []);
    window.incidentCoords = @json($incidentCoords ?? []);
    window.currentFirefighterId = {{ auth()->user()->user_id ?? 'null' }};
    window.currentUserId = {{ auth()->user()->user_id ?? 'null' }};
    const FF_LOCATION_PUSH_INTERVAL_MS = 45000;
    const FF_LOCATION_PULL_INTERVAL_MS = 45000;
    const FF_ASSIGNED_INCIDENTS_INTERVAL_MS = 60000;
    const FF_AUTO_ASSIGN_INTERVAL_MS = 60000;
    let ffLocationPushInterval = null;
    let ffLocationPullInterval = null;
    let ffAssignedIncidentsInterval = null;
    let ffAutoAssignInterval = null;
    const FF_FETCH_TIMEOUT_MS = 10000;
    let ffAssignedIncidentsRequest = null;
    let ffAutoAssignRequest = null;

    function firefighterDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function clearFirefighterDashboardIntervals() {
        if (ffLocationPushInterval) {
            clearInterval(ffLocationPushInterval);
            ffLocationPushInterval = null;
        }
        if (ffLocationPullInterval) {
            clearInterval(ffLocationPullInterval);
            ffLocationPullInterval = null;
        }
        if (ffAssignedIncidentsInterval) {
            clearInterval(ffAssignedIncidentsInterval);
            ffAssignedIncidentsInterval = null;
        }
        if (ffAutoAssignInterval) {
            clearInterval(ffAutoAssignInterval);
            ffAutoAssignInterval = null;
        }
    }

    function startFirefighterDashboardIntervals() {
        clearFirefighterDashboardIntervals();

        ffLocationPushInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateUserLocation(window.currentUserId, lat, lng);
                    sendUserLocationToServer(lat, lng);

                    if (window.userMarker) {
                        window.userMarker.setLatLng([lat, lng]);
                    }
                },
                function(error) {
                    console.error("Location update error:", error);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }, FF_LOCATION_PUSH_INTERVAL_MS);

        ffLocationPullInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAllFirefighterLocations();
        }, FF_LOCATION_PULL_INTERVAL_MS);

        ffAssignedIncidentsInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAssignedIncidents();
        }, FF_ASSIGNED_INCIDENTS_INTERVAL_MS);

        ffAutoAssignInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            window.runPendingIncidentAutoAssignment();
        }, FF_AUTO_ASSIGN_INTERVAL_MS);
    }

    function fetchFirefighterDashboardOnce(currentRequest, url, options = {}) {
        if (currentRequest) {
            return currentRequest;
        }

        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), FF_FETCH_TIMEOUT_MS);
        const requestPromise = fetch(url, {
            cache: 'no-store',
            ...options,
            signal: controller.signal
        }).finally(() => {
            window.clearTimeout(timeoutId);
        });

        return requestPromise;
    }

    // Function to fetch assigned incidents
    async function fetchAssignedIncidents() {
        try {
            ffAssignedIncidentsRequest = fetchFirefighterDashboardOnce(
                ffAssignedIncidentsRequest,
                `/firefighter/assigned/${window.currentFirefighterId}`
            );
            const response = await ffAssignedIncidentsRequest;
            if (!response.ok) throw new Error('Failed to fetch assigned incidents');
            
            const result = await response.json();
            console.log('API Response:', result);
            
            const incidents = result.data || [];
            console.log('Incidents data:', incidents);
            window.assignedIncidentsData = incidents;
            
            displayAssignedIncidents(incidents);
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(incidents);
            }
            
            document.getElementById('assignedIncidentsCount').textContent = incidents.length;
            
            return incidents;
        } catch (error) {
            console.error('Error fetching assigned incidents:', error);
            document.getElementById('assignedIncidentsList').innerHTML = `
                <div class="empty-incidents">
                    <i class="fas fa-exclamation-triangle text-4xl mb-2 opacity-50"></i>
                    <p>Failed to load assigned incidents</p>
                    <button onclick="fetchAssignedIncidents()" class="btn btn-outline mt-2">Retry</button>
                </div>
            `;
            return [];
        } finally {
            ffAssignedIncidentsRequest = null;
        }
    }

    window.refreshFirefighterAssignedIncidents = fetchAssignedIncidents;

    // Compatibility handler for the inline Stop Simulation button.
    // map.js exposes stopAllSimulations / stopSimulationForRoute globally.
    async function stopSimulation() {
        try {
            if (typeof window.stopAllSimulations === 'function') {
                window.stopAllSimulations();
            } else if (typeof window.stopSimulationForRoute === 'function' && window.activeRoute?.route_id) {
                await window.stopSimulationForRoute(window.activeRoute.route_id);
            }
        } catch (error) {
            console.error('Error stopping simulation:', error);
        } finally {
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'none';
        }
    }
    
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function formatHumanDateTime(value) {
        if (!value) return 'Unknown';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Unknown';
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatReporterLabel(incident) {
        const reporter = incident?.reporter;
        const reporterRole = String(reporter?.role?.role || '').trim().toLowerCase();
        const firstName = String(reporter?.first_name || '').trim();
        const lastName = String(reporter?.last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim() || String(reporter?.name || '').trim();

        if (reporterRole === 'citizen' || (!fullName && incident?.reported_by)) {
            return 'Concerned Citizen';
        }

        return fullName || 'Concerned Citizen';
    }

    function resolveStorageUrl(path) {
        const normalizedPath = String(path || '').trim();
        if (!normalizedPath) return null;
        if (/^(https?:)?\/\//i.test(normalizedPath) || normalizedPath.startsWith('/storage/')) {
            return normalizedPath;
        }

        return `/storage/${normalizedPath.replace(/^\/+/, '')}`;
    }

    function getIncidentImageUrl(incident) {
        const multimediaPath = incident?.marker?.multimedia?.path;
        return resolveStorageUrl(multimediaPath);
    }

    function getIncidentTypeIconClass(typeName = '') {
        const normalized = String(typeName).trim().toLowerCase();
        if (normalized.includes('medical')) return 'fa-truck-medical';
        if (normalized.includes('rescue')) return 'fa-life-ring';
        if (normalized.includes('hazard')) return 'fa-radiation';
        if (normalized.includes('false')) return 'fa-times-circle';
        if (normalized.includes('accident')) return 'fa-car-crash';
        if (normalized.includes('disaster')) return 'fa-house-damage';
        return 'fa-fire';
    }

    function getIncidentStatusId(incident = {}) {
        if (incident?.incident_status_id !== undefined && incident?.incident_status_id !== null) {
            return Number(incident.incident_status_id);
        }

        const status = String(incident?.incident_status || incident?.status || '').trim().toLowerCase();
        if (status === 'en-route' || status === 'responding') return 2;
        if (status === 'on-scene' || status === 'on scene') return 3;
        if (status === 'completed' || status === 'controlled') return 4;
        if (status === 'closed') return 5;
        return 1;
    }

    function getIncidentTypeLabel(incident = {}) {
        return incident?.incident_type_name
            || incident?.incident_type
            || incident?.incidentType?.type
            || incident?.incident_type?.type
            || (incident?.marker?.category === 'fire' ? 'Fire' : 'Incident');
    }

    function displayAssignedIncidents(incidents) {
    const container = document.getElementById('assignedIncidentsList');
    
    if (!incidents || incidents.length === 0) {
        container.innerHTML = `
            <div class="empty-incidents">
                <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                <p>No assigned incidents</p>
                <p class="text-sm">You haven't been assigned to any incidents yet.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    incidents.forEach(incident => {
        const statusId = getIncidentStatusId(incident);
        let statusClass = 'status-reported';
        let statusText = 'REPORTED';
        let actionButton = '';
        let falseAlarmButton = '';
        
        if (statusId == 1) {
            statusClass = 'status-reported';
            statusText = 'REPORTED';
            actionButton = `
                <button class="btn btn-success respond-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-truck"></i> Respond Now
                </button>
            `;
        } else if (statusId == 2) {
            statusClass = 'status-responding';
            statusText = 'RESPONDING';
            actionButton = `
                <button class="btn btn-outline" disabled style="opacity: 0.7; cursor: default;">
                    <i class="fas fa-truck"></i> En Route
                </button>
            `;
        } else if (statusId == 3) {
            statusClass = 'status-resolved';
            statusText = 'ON SCENE';
            actionButton = `
                <button class="btn btn-warning complete-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}" style="background: #f59e0b; color: white;">
                    <i class="fas fa-check-circle"></i> Complete Incident
                </button>
            `;
        } else if (statusId == 4) {
            statusClass = 'status-resolved';
            statusText = 'CONTROLLED';
            actionButton = `
                <button class="btn btn-info close-case" data-incident-id="${incident.incident_id}" data-status="${statusId}" style="background: #8b5cf6; color: white;">
                    <i class="fas fa-folder"></i> Close Case
                </button>
            `;
        } else if (statusId == 5) {
            statusClass = 'status-closed';
            statusText = 'CLOSED';
            actionButton = `
                <button class="btn btn-secondary" disabled style="background: #6b7280; color: white; opacity: 0.6; cursor: not-allowed;">
                    <i class="fas fa-folder"></i> Case Closed
                </button>
            `;
        }

        if ([1, 2].includes(parseInt(statusId))) {
            falseAlarmButton = `
                <button class="btn btn-danger false-alarm-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-exclamation-triangle"></i> False Alarm
                </button>
            `;
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const reporterLabel = formatReporterLabel(incident);
        const reportedAt = formatHumanDateTime(incident.created_at);
        const assignedAt = formatHumanDateTime(incident.updated_at || incident.created_at);
        const imageUrl = getIncidentImageUrl(incident);
        const incidentTypeIcon = getIncidentTypeIconClass(incidentType);
        const hasCoordinates = lat !== null && lat !== undefined && lng !== null && lng !== undefined;
        const locationLabel = hasCoordinates ? `${lat}, ${lng}` : 'Location not specified';
        const imageMarkup = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(incidentTitle)}" onerror="this.remove(); this.parentElement.classList.add('no-image'); this.parentElement.innerHTML = '<div class=&quot;incident-media-fallback&quot;><i class=&quot;fas ${incidentTypeIcon}&quot;></i><span>No Image</span></div>';">`
            : `<div class="incident-media-fallback"><i class="fas ${incidentTypeIcon}"></i><span>No Image</span></div>`;
        
        html += `
            <div class="incident-card medium">
                <div class="incident-card-layout">
                    <div class="incident-media">
                        ${imageMarkup}
                    </div>
                    <div class="incident-main">
                        <div class="incident-topline">
                            <span class="incident-type">
                                <i class="fas ${incidentTypeIcon}"></i> ${escapeHtml(incidentType)}
                            </span>
                            <span class="incident-priority priority-medium">Assigned</span>
                        </div>
                        <div class="incident-title-wrap">
                            <h3 class="incident-title">${escapeHtml(incidentTitle)}</h3>
                            <div class="incident-subtitle">${escapeHtml(description)}</div>
                        </div>
                        <div class="incident-detail-grid incident-detail-grid-compact">
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-map-marker-alt"></i> Location</span>
                                <span class="incident-detail-value-inline">${escapeHtml(locationLabel)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-user-shield"></i> Reported By</span>
                                <span class="incident-detail-value-inline">${escapeHtml(reporterLabel)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="far fa-calendar-alt"></i> Reported At</span>
                                <span class="incident-detail-value-inline">${escapeHtml(reportedAt)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="far fa-clock"></i> Assigned At</span>
                                <span class="incident-detail-value-inline">${escapeHtml(assignedAt)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-hashtag"></i> Incident ID</span>
                                <span class="incident-detail-value-inline">#${escapeHtml(incident.incident_id)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-crosshairs"></i> Coordinates</span>
                                <span class="incident-detail-value-inline">${hasCoordinates ? `${escapeHtml(lat)}, ${escapeHtml(lng)}` : 'Coordinates unavailable'}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-signal"></i> Status</span>
                                <span class="incident-detail-value-inline">${escapeHtml(statusText)}</span>
                            </span>
                        </div>
                    </div>
                    <div class="incident-actions-column">
                        <div class="incident-status-badge ${statusClass} is-block">
                            ${statusText}
                        </div>
                        <button class="btn btn-outline view-incident-details" data-incident-id="${incident.incident_id}">
                            <i class="far fa-eye"></i> Details
                        </button>
                        ${falseAlarmButton}
                        ${actionButton}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Event Listeners for View Details buttons
    document.querySelectorAll('.view-incident-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const incidentId = btn.dataset.incidentId;
            const incident = incidents.find(i => i.incident_id == incidentId);
            if (incident) {
                showProfessionalIncidentModal(incident);
            }
        });
    });
    
    // Event Listeners for Respond buttons (status 1)
    document.querySelectorAll('.respond-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await respondToIncident(incidentId, currentStatus);
        });
    });

    // Event Listeners for Complete buttons (status 2)
    document.querySelectorAll('.complete-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await completeIncident(incidentId, currentStatus);
        });
    });
    
    // Event Listeners for Close Case buttons (status 3)
    document.querySelectorAll('.close-case').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await closeCase(incidentId, currentStatus);
        });
    });

    document.querySelectorAll('.false-alarm-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await markIncidentFalseAlarm(incidentId, currentStatus);
        });
    });
}
    // Professional Incident Details Modal
    function showProfessionalIncidentModal(incident) {
        window.logUserInteraction?.('viewed incident details');
        let modal = document.getElementById('professionalIncidentModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'professionalIncidentModal';
            modal.className = 'professional-modal';
            modal.innerHTML = `
                <div class="professional-modal-overlay"></div>
                <div class="professional-modal-container">
                    <div class="professional-modal-header">
                        <div class="professional-modal-title">
                            <i class="fas fa-file-alt"></i>
                            Incident Report
                        </div>
                        <button class="professional-modal-close">&times;</button>
                    </div>
                    <div class="professional-modal-body" id="professionalIncidentContent"></div>
                    <div class="professional-modal-footer">
                        <button class="btn btn-outline modal-close-btn">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const closeBtn = modal.querySelector('.professional-modal-close');
            const overlay = modal.querySelector('.professional-modal-overlay');
            const closeModalBtn = modal.querySelector('.modal-close-btn');
            
            const closeModal = () => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            };
            
            closeBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            closeModalBtn.addEventListener('click', closeModal);
        }
        
        const content = document.getElementById('professionalIncidentContent');
        
        const statusId = getIncidentStatusId(incident);
        let statusBadgeClass = 'status-badge-reported';
        let statusText = 'REPORTED';
        let statusIcon = 'far fa-bell';
        
        if (statusId == 2) { 
            statusBadgeClass = 'status-badge-responding'; 
            statusText = 'RESPONDING';
            statusIcon = 'fas fa-spinner fa-pulse';
        } else if (statusId == 3) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'ON SCENE';
            statusIcon = 'fas fa-crosshairs';
        } else if (statusId == 4) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'CONTROLLED';
            statusIcon = 'fas fa-check-circle';
        } else if (statusId == 5) { 
            statusBadgeClass = 'status-badge-closed'; 
            statusText = 'CLOSED';
            statusIcon = 'fas fa-folder';
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const teamId = incident.team?.team_id;
        const reportedBy = incident.reported_by || 'Dispatch';
        const createdAt = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
        
        content.innerHTML = `
            <div class="professional-incident-details">
                <!-- Header Status Bar -->
                <div class="details-status-header ${statusBadgeClass}">
                    <i class="${statusIcon}"></i>
                    <span>${statusText}</span>
                </div>
                
                <!-- Incident ID & Type Row -->
                <div class="details-info-row">
                    <div class="details-info-item">
                        <label>INCIDENT ID</label>
                        <div class="details-info-value">#${incident.incident_id}</div>
                    </div>
                    <div class="details-info-item">
                        <label>INCIDENT TYPE</label>
                        <div class="details-info-value">
                            <i class="fas fa-fire" style="color: #ef4444;"></i> ${escapeHtml(incidentType)}
                        </div>
                    </div>
                </div>
                
                <!-- Title Section -->
                <div class="details-title-section">
                    <label>TITLE / LOCATION</label>
                    <div class="details-title">${escapeHtml(incidentTitle)}</div>
                </div>
                
                <!-- Coordinates Section -->
                ${lat && lng ? `
                <div class="details-coordinates">
                    <div class="coord-box">
                        <label>LATITUDE</label>
                        <div class="coord-value">${lat}</div>
                    </div>
                    <div class="coord-box">
                        <label>LONGITUDE</label>
                        <div class="coord-value">${lng}</div>
                    </div>
                </div>
                ` : '<div class="details-info-item"><label>COORDINATES</label><div class="details-info-value">Not available</div></div>'}
                
                <!-- Description Section -->
                <div class="details-description">
                    <label><i class="fas fa-align-left"></i> INCIDENT DESCRIPTION</label>
                    <div class="description-text">${escapeHtml(description)}</div>
                </div>
                
                <!-- Report Information Grid -->
                <div class="details-grid-2">
                    <div class="details-info-item">
                        <label><i class="far fa-user"></i> REPORTED BY</label>
                        <div class="details-info-value">${escapeHtml(reportedBy)}</div>
                    </div>
                    <div class="details-info-item">
                        <label><i class="far fa-calendar"></i> REPORTED AT</label>
                        <div class="details-info-value">${createdAt}</div>
                    </div>
                </div>
                
                <!-- Team Information -->
                ${teamId ? `
                <div class="details-team-info">
                    <label><i class="fas fa-users"></i> ASSIGNED TEAM</label>
                    <div class="team-badge">Team #${teamId}</div>
                </div>
                ` : ''}
                
                <!-- Last Updated -->
                ${incident.updated_at ? `
                <div class="details-last-updated">
                    <i class="far fa-clock"></i> Last updated: ${new Date(incident.updated_at).toLocaleString()}
                </div>
                ` : ''}
            </div>
        `;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
async function respondToIncident(incidentId, currentStatusId) {
    if (currentStatusId != 1) {
        showToast('Cannot respond to this incident - invalid status', 'error');
        return;
    }
    
    if (!confirm('Confirm responding to this incident? Your status will be updated to RESPONDING.')) return;
    
    try {
        const newStatusId = 2;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'You are now responding to this incident!', 'success');
            await fetchAssignedIncidents();
            if (typeof window.calculateRouteToIncident === 'function') {
                await window.calculateRouteToIncident(incidentId);
            }
        } else {
            showToast(result.message || 'Failed to update incident status', 'error');
        }
    } catch (error) {
        console.error('Error responding to incident:', error);
        showToast('Failed to update incident status', 'error');
    }
}

async function completeIncident(incidentId, currentStatusId) {
    if (currentStatusId != 3) {
        showToast('Cannot mark controlled until the team is on scene', 'error');
        return;
    }
    
    if (!confirm('Complete this incident and mark it as controlled?')) return;
    
    try {
        const newStatusId = 4;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Incident completed and marked as controlled!', 'success');
            fetchAssignedIncidents(); // Refresh the list
        } else {
            showToast(result.message || 'Failed to update incident', 'error');
        }
    } catch (error) {
        console.error('Error completing incident:', error);
        showToast('Failed to update incident', 'error');
    }
}

async function closeCase(incidentId, currentStatusId) {
    if (currentStatusId != 4) {
        showToast('Cannot close this case until the incident is controlled', 'error');
        return;
    }
    
    if (!confirm('Close this case? This action will archive the incident.')) return;
    
    try {
        const newStatusId = 5;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Case closed successfully!', 'success');
            fetchAssignedIncidents(); // Refresh the list
        } else {
            showToast(result.message || 'Failed to close case', 'error');
        }
    } catch (error) {
        console.error('Error closing case:', error);
        showToast('Failed to close case', 'error');
    }
}

async function markIncidentFalseAlarm(incidentId, currentStatusId) {
    if (![1, 2].includes(currentStatusId)) {
        showToast('False alarm can only be set while the incident is still active', 'error');
        return;
    }

    const confirmed = await showFalseAlarmConfirmationToast();
    if (!confirmed) return;

    try {
        const response = await fetch(`/api/incidents/${incidentId}/false-alarm`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message || 'Incident tagged as false alarm', 'success');
            fetchAssignedIncidents();
        } else {
            showToast(result.message || 'Failed to tag false alarm', 'error');
        }
    } catch (error) {
        console.error('Error tagging false alarm:', error);
        showToast('Failed to tag false alarm', 'error');
    }
}

function showFalseAlarmConfirmationToast() {
    return new Promise((resolve) => {
        const existing = document.getElementById('falseAlarmConfirmToast');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.id = 'falseAlarmConfirmToast';
        toast.style.cssText = `
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: min(360px, calc(100vw - 32px));
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(251, 146, 60, 0.28);
            border-left: 4px solid #ea580c;
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            padding: 16px;
            z-index: 5000;
            color: #7c2d12;
        `;

        toast.innerHTML = `
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <div style="width:38px; height:38px; border-radius:999px; background:#fb923c; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; margin-bottom:4px;">Flag as False Alarm?</div>
                    <div style="font-size:13px; line-height:1.45; color:#9a3412;">
                        This will tag the incident as a false alarm and remove it from active response handling.
                    </div>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
                <button type="button" id="falseAlarmCancelBtn" style="padding:8px 12px; border-radius:10px; border:1px solid #fdba74; background:#fff; color:#9a3412; font-size:12px; font-weight:700; cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="falseAlarmConfirmBtn" style="padding:8px 12px; border-radius:10px; border:none; background:#ea580c; color:#fff; font-size:12px; font-weight:700; cursor:pointer;">
                    Confirm
                </button>
            </div>
        `;

        const cleanup = (value) => {
            if (toast.parentNode) {
                toast.remove();
            }
            resolve(value);
        };

        document.body.appendChild(toast);

        toast.querySelector('#falseAlarmCancelBtn')?.addEventListener('click', () => cleanup(false));
        toast.querySelector('#falseAlarmConfirmBtn')?.addEventListener('click', () => cleanup(true));

        setTimeout(() => {
            if (document.body.contains(toast)) {
                cleanup(false);
            }
        }, 10000);
    });
}
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
    
    // Sidebar toggle and location initialization
    document.addEventListener('DOMContentLoaded', function() {
        window.userCoords = {
            currentLatitude: null,
            currentLongitude: null,
            ready: false
        };
        // ============================================================================
// GEOLOCATION INITIALIZATION - REPLACE THIS SECTION
// ============================================================================

// Get current user ID from meta tag or blade
window.currentUserId = {{ auth()->user()->user_id ?? 'null' }};

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Update for current userlive
            updateUserLocation(window.currentUserId, lat, lng);
            
            console.log("Location loaded for user:", window.currentUserId);
            console.log("Location:", lat, lng);
            
            // Display user location on map
            if (window.map) {
                // Remove existing user marker if any
                if (window.userMarker) {
                    window.map.removeLayer(window.userMarker);
                }
                
                // Add user marker
                window.userMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'user-location-marker',
                        html: `<div style="
                            width: 24px;
                            height: 24px;
                            background-color: #3b82f6;
                            border: 3px solid white;
                            border-radius: 50%;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                            animation: pulse 2s infinite;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                        </div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).bindPopup("You are here").addTo(window.map);
                
                // Add accuracy circle
                L.circle([lat, lng], {
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    radius: 50
                }).addTo(window.map);
            }
            
            // Send location to server for other users to see
            sendUserLocationToServer(lat, lng);
            
            document.dispatchEvent(new Event("locationReady"));
        },
        function(error) {
            console.error("Location error:", error);
            // Fallback to default location
            const defaultLat = 15.8222;
            const defaultLng = 120.3297;
            updateUserLocation(window.currentUserId, defaultLat, defaultLng);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
    
    // Initial fetch
    setTimeout(() => {
        fetchAllFirefighterLocations();
    }, 2000);
    
} else {
    console.warn("Geolocation not supported");
    const defaultLat = 15.8222;
    const defaultLng = 120.3297;
    updateUserLocation(window.currentUserId, defaultLat, defaultLng);
}
        // if (navigator.geolocation) {
        //     navigator.geolocation.getCurrentPosition(
        //         function(position) {
        //             window.userCoords.currentLatitude = position.coords.latitude;
        //             window.userCoords.currentLongitude = position.coords.longitude;
        //             window.userCoords.ready = true;
                    
        //             console.log("Location loaded:", window.userCoords.currentLatitude, window.userCoords.currentLongitude);
                    
        //             if (window.map) {
        //                 L.marker([window.userCoords.currentLatitude, window.userCoords.currentLongitude])
        //                     .addTo(window.map)
        //                     .bindPopup("You are here")
        //                     .openPopup();
                        
        //                 L.circle([window.userCoords.currentLatitude, window.userCoords.currentLongitude], {
        //                     color: 'red',
        //                     fillColor: '#f03',
        //                     fillOpacity: 0.3,
        //                     radius: 500
        //                 }).addTo(window.map);
        //             }
                    
        //             document.dispatchEvent(new Event("locationReady"));
        //         },
        //         function(error) {
        //             console.error("Location error:", error);
        //             window.userCoords.currentLatitude = 15.8222;
        //             window.userCoords.currentLongitude = 120.3297;
        //             window.userCoords.ready = true;
        //         },
        //         { enableHighAccuracy: true, timeout: 10000 }
        //     );
        // } else {
        //     window.userCoords.currentLatitude = 15.8222;
        //     window.userCoords.currentLongitude = 120.3297;
        //     window.userCoords.ready = true;
        // }
        
        window.incidentsData = @json($incidents) || [];
        
        const sidebar = document.querySelector('.sidebar');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('open');
                backdrop.classList.remove('hidden');
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        const refreshBtn = document.getElementById('refreshAssignedIncidents');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                fetchAssignedIncidents();
            });
        }
        
        fetchAssignedIncidents();
        window.runPendingIncidentAutoAssignment();
        startFirefighterDashboardIntervals();

        document.addEventListener('visibilitychange', () => {
            if (firefighterDashboardIsVisible()) {
                fetchAssignedIncidents();
                fetchAllFirefighterLocations();
                startFirefighterDashboardIntervals();
                return;
            }

            clearFirefighterDashboardIntervals();
        });
        
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.dataset.status;
                const statusDot = document.querySelector('.status-dot');
                const statusButton = document.querySelector('.bg-blue-600');
                
                if (status === 'on-duty') {
                    statusDot.className = 'status-dot online';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-circle"></i><span class="hidden sm:inline">On Duty</span>';
                } else if (status === 'on-break') {
                    statusDot.className = 'status-dot away';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-mug-hot"></i><span class="hidden sm:inline">On Break</span>';
                } else {
                    statusDot.className = 'status-dot offline';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-clock"></i><span class="hidden sm:inline">Off Duty</span>';
                }
                
                document.getElementById('statusModal').classList.remove('active');
                
                fetch('/api/firefighter/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: status })
                });
            });
        });
    });
    // Chart instances
let incidentStatusChart = null;
let responseTimeChart = null;
let monthlyIncidentsChart = null;
let priorityChart = null;
let performanceChart = null;

// Function to initialize all charts
function initializeCharts() {
    // Incident Status Chart (Pie/Doughnut)
    const statusCtx = document.getElementById('incidentStatusChart')?.getContext('2d');
    if (statusCtx) {
        incidentStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reported', 'Responding', 'Resolved', 'Closed'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#e91e8c', '#7c3aed', '#1da1f2', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} incidents` } }
                }
            }
        });
    }

    // Response Time Trend Chart (Line)
    const responseCtx = document.getElementById('responseTimeChart')?.getContext('2d');
    if (responseCtx) {
        responseTimeChart = new Chart(responseCtx, {
            type: 'line',
            data: {
                labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Avg Response Time (minutes)',
                    data: [0, 2, 7, 5, 1, 0, 4],
                    borderColor: '#e91e8c',
                    backgroundColor: 'rgba(233, 30, 140, 0.16)',
                    tension: 0.45,
                    fill: true,
                    pointBackgroundColor: '#e91e8c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } },
                    tooltip: { callbacks: { label: (context) => `${context.raw} minutes` } }
                },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Minutes' } } }
            }
        });
    }

    // Monthly Incidents Chart (Bar)
    const monthlyCtx = document.getElementById('monthlyIncidentsChart')?.getContext('2d');
    if (monthlyCtx) {
        monthlyIncidentsChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Incidents',
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#f472b6', '#8b5cf6', '#38bdf8', '#f59e0b'],
                    borderRadius: 12,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => `${context.raw} incidents` } }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11, weight: 600 } }
                    },
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2, color: '#94a3b8', font: { size: 11 } },
                        grid: { color: 'rgba(148, 163, 184, 0.25)', drawBorder: false }
                    }
                }
            }
        });
    }


    // Performance Metrics Chart (Radar or Gauge)
    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    if (performanceCtx) {
        performanceChart = new Chart(performanceCtx, {
            type: 'radar',
            data: {
                labels: ['Completion Rate', 'Response Time', 'Success Rate', 'Efficiency', 'Teamwork'],
                datasets: [{
                    label: 'Performance Score (%)',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(139, 92, 246, 0.18)',
                    borderColor: '#8b5cf6',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ec4899',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 10 }, usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (context) => `${context.raw}%` } }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20, backdropColor: 'transparent', color: '#94a3b8' },
                        angleLines: { color: 'rgba(148, 163, 184, 0.35)' },
                        grid: { color: 'rgba(148, 163, 184, 0.25)' },
                        pointLabels: { color: '#64748b', font: { size: 10, weight: 600 } }
                    }
                }
            }
        });
    }
}

// Function to update all charts with incident data
function updateChartsWithData(incidents) {
    if (!incidents || incidents.length === 0) {
        // Set default empty data
        if (incidentStatusChart) incidentStatusChart.data.datasets[0].data = [0, 0, 0, 0];
        if (priorityChart) priorityChart.data.datasets[0].data = [0, 0, 0, 0];
        if (performanceChart) performanceChart.data.datasets[0].data = [0, 0, 0, 0, 0];
        
        [incidentStatusChart, priorityChart, performanceChart].forEach(chart => {
            if (chart) chart.update();
        });
        return;
    }

    // Update Incident Status Chart
    const statusCounts = {
        reported: incidents.filter(i => getIncidentStatusId(i) === 1).length,
        responding: incidents.filter(i => getIncidentStatusId(i) === 2).length,
        resolved: incidents.filter(i => getIncidentStatusId(i) === 3).length,
        closed: incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length
    };
    if (incidentStatusChart) {
        incidentStatusChart.data.datasets[0].data = [statusCounts.reported, statusCounts.responding, statusCounts.resolved, statusCounts.closed];
        incidentStatusChart.update();
    }

    // Update Priority Chart
    const priorityCounts = {
        critical: incidents.filter(i => i.priority === 'critical').length,
        high: incidents.filter(i => i.priority === 'high').length,
        medium: incidents.filter(i => i.priority === 'medium').length,
        low: incidents.filter(i => i.priority === 'low').length
    };
    if (priorityChart) {
        priorityChart.data.datasets[0].data = [priorityCounts.critical, priorityCounts.high, priorityCounts.medium, priorityCounts.low];
        priorityChart.update();
    }

    // Calculate average response times for last 7 days
    const last7Days = [];
    const responseTimes = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dayStart = new Date(date.setHours(0, 0, 0, 0));
        const dayEnd = new Date(date.setHours(23, 59, 59, 999));
        
        const dayIncidents = incidents.filter(i => {
            const created = new Date(i.created_at);
            return created >= dayStart && created <= dayEnd && getIncidentStatusId(i) >= 2;
        });
        
        let avgTime = 0;
        if (dayIncidents.length > 0) {
            const totalTime = dayIncidents.reduce((sum, inc) => {
                const created = new Date(inc.created_at);
                const responded = inc.updated_at ? new Date(inc.updated_at) : new Date();
                return sum + (responded - created) / (1000 * 60);
            }, 0);
            avgTime = Math.round(totalTime / dayIncidents.length);
        }
        responseTimes.push(avgTime);
        last7Days.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
    }
    
    if (responseTimeChart) {
        responseTimeChart.data.labels = last7Days;
        responseTimeChart.data.datasets[0].data = responseTimes;
        responseTimeChart.update();
    }

    // Update Monthly Incidents Chart (by week)
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const weeksData = [0, 0, 0, 0];
    
    incidents.forEach(incident => {
        const incidentDate = new Date(incident.created_at);
        if (incidentDate.getMonth() === currentMonth && incidentDate.getFullYear() === currentYear) {
            const weekNum = Math.min(Math.floor((incidentDate.getDate() - 1) / 7), 3);
            weeksData[weekNum]++;
        }
    });
    
    if (monthlyIncidentsChart) {
        monthlyIncidentsChart.data.datasets[0].data = weeksData;
        monthlyIncidentsChart.update();
    }

    // Update Performance Chart
    const totalIncidents = incidents.length;
    const completedIncidents = incidents.filter(i => getIncidentStatusId(i) >= 3).length;
    const completionRate = totalIncidents > 0 ? Math.round((completedIncidents / totalIncidents) * 100) : 0;
    
    const avgResponseTime = responseTimes.reduce((a, b) => a + b, 0) / responseTimes.filter(t => t > 0).length || 0;
    const responseScore = Math.max(0, Math.min(100, 100 - (avgResponseTime / 30) * 100));
    
    const successfulIncidents = incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length;
    const successRate = successfulIncidents > 0 
        ? Math.round((successfulIncidents / completedIncidents) * 100) : 0;
    
    if (performanceChart) {
        performanceChart.data.datasets[0].data = [completionRate, Math.round(responseScore), successRate, Math.round((completionRate + responseScore + successRate) / 3), 85];
        performanceChart.update();
    }
}

// Add this to your displayAssignedIncidents function to update charts
// Call updateChartsWithData(incidents) after displaying incidents

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // User Profile Dropdown Toggle
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }

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
});

window.runPendingIncidentAutoAssignment = async function() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        return;
    }

    try {
        ffAutoAssignRequest = fetchFirefighterDashboardOnce(ffAutoAssignRequest, '/api/incidents/auto-assign-recent-unassigned', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        });
        const response = await ffAutoAssignRequest;
        const result = await response.json().catch(() => ({}));
        if (result?.success && Number(result.assigned) > 0) {
            await fetchAssignedIncidents();
        }
    } catch (error) {
        console.error('Background incident auto-assignment failed:', error);
    } finally {
        ffAutoAssignRequest = null;
    }
};
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const insightsPanel = document.getElementById('firefighterMobileInsightsPanel');
    const insightsContent = document.getElementById('firefighterMobileInsightsContent');
    const insightsCloseButtons = document.querySelectorAll('[data-ff-insights-close]');
    const welcomeToast = document.getElementById('firefighterMobileWelcomeToast');
    const insightSections = Array.from(document.querySelectorAll('[data-ff-insights-source]'));
    const insightPlaceholders = new Map();

    function moveInsightsIntoPanel() {
        if (!insightsContent) {
            return;
        }

        insightSections.forEach(function (section, index) {
            if (!insightPlaceholders.has(section)) {
                const placeholder = document.createElement('div');
                placeholder.dataset.ffInsightsPlaceholder = String(index);
                section.parentNode?.insertBefore(placeholder, section);
                insightPlaceholders.set(section, placeholder);
            }

            insightsContent.appendChild(section);
        });
    }

    function restoreInsightsFromPanel() {
        insightSections.forEach(function (section) {
            const placeholder = insightPlaceholders.get(section);
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.insertBefore(section, placeholder);
                placeholder.remove();
            }
            insightPlaceholders.delete(section);
        });
    }

    function openFirefighterInsightsPanel() {
        if (!insightsPanel || window.innerWidth > 768) {
            return;
        }

        moveInsightsIntoPanel();
        insightsPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('menu-open');
    }

    function closeFirefighterInsightsPanel() {
        if (!insightsPanel) {
            return;
        }

        restoreInsightsFromPanel();
        insightsPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-open');
    }

    window.openFirefighterInsightsPanel = openFirefighterInsightsPanel;
    window.closeFirefighterInsightsPanel = closeFirefighterInsightsPanel;

    insightsCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeFirefighterInsightsPanel);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeFirefighterInsightsPanel();
        }
    });

    if (welcomeToast && window.innerWidth <= 768) {
        window.setTimeout(function () {
            welcomeToast.classList.add('is-visible');
            window.setTimeout(function () {
                welcomeToast.classList.remove('is-visible');
            }, 3200);
        }, 260);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('partials.pwa-body')
</body>
</html>

