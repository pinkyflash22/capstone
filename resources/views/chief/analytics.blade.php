<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'station_admin' }}">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    
    <title>PyroSense · AI Predictive Analytics</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .legacy-chief-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 50;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .legacy-chief-sidebar .sidebar-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
        }

        .legacy-chief-sidebar .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
        }

        .legacy-chief-sidebar .sidebar-header-content { position: relative; z-index: 1; }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .logo-icon { width: 48px; height: 48px; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .logo-icon i { font-size: 24px; color: white; }
        .logo-text h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .logo-text p { font-size: 12px; opacity: 0.8; }

        .user-info { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.2); }
        .user-avatar { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; }
        .user-details h4 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .user-details span { font-size: 12px; opacity: 0.8; display: flex; align-items: center; gap: 4px; }
        .user-details span i { font-size: 8px; color: #4ade80; }

        .legacy-chief-sidebar .sidebar-nav { flex: 1; overflow-y: auto; padding: 20px 16px; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { font-size: 12px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; padding: 0 12px; margin-bottom: 8px; }
        .legacy-chief-sidebar .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #4b5563; text-decoration: none; border-radius: 12px; transition: all 0.2s; margin-bottom: 2px; }
        .legacy-chief-sidebar .nav-item:hover { background: #f3f4f6; }
        .legacy-chief-sidebar .nav-item.active { background: #fee2e2; color: #dc2626; font-weight: 500; }
        .legacy-chief-sidebar .nav-item i { width: 20px; font-size: 16px; }

        .logout-container { padding: 20px; border-top: 1px solid #e5e7eb; }
        .logout-btn { width: 100%; padding: 12px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: #4b5563; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
        .logout-btn:hover { background: #fee2e2; color: #dc2626; }

        .main-content { margin-left: 280px; flex: 1; min-width: 0; background-color: #f3f4f6; min-height: 100vh; display: flex; flex-direction: column; }
        .top-nav { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 30; padding: 0 24px; }
        .top-nav-container { display: flex; justify-content: space-between; align-items: center; height: 64px; max-width: 1600px; margin: 0 auto; }
        .mobile-menu-btn { display: none; background: none; border: none; color: #4b5563; font-size: 1.5rem; cursor: pointer; }
        .mobile-logo { display: none; align-items: center; gap: 8px; }
        .top-nav-actions { display: flex; align-items: center; gap: 16px; }
        .lector-top-bar {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            height: 70px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 16px 24px 0;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .user-dropdown { position: relative; }
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 99px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .user-dropdown-toggle:hover { background: #fef3c7; }
        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            padding: 12px 20px;
            color: #1e293b;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .user-dropdown-item:hover {
            background: #fff0e8;
            color: #c63d26;
        }
        .station-badge { color: #6b7280; font-size: 14px; background: #f3f4f6; padding: 6px 12px; border-radius: 20px; }
        .emergency-btn { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s; }
        .emergency-btn:hover { background: #b91c1c; }

        .content-container { max-width: 1400px; margin: 0 auto; padding: 24px; width: 100%; }
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s; }
        .risk-high { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        .risk-medium { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .risk-low { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .form-select { padding: 10px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; width: 100%; }

        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; display: none; }
        .loading-overlay.active { display: flex; }
        .loading-spinner { border: 3px solid #f3f3f3; border-top: 3px solid #dc2626; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .recommendation-card { padding: 16px; border-radius: 12px; margin-bottom: 12px; transition: all 0.2s; }
        .recommendation-card.critical { background: #fef2f2; border-left: 4px solid #dc2626; }
        .recommendation-card.high { background: #fffbeb; border-left: 4px solid #f59e0b; }
        .recommendation-card.medium { background: #f0fdf4; border-left: 4px solid #10b981; }
        .recommendation-card.low { background: #f8fafc; border-left: 4px solid #94a3b8; }
        .priority-badge { display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin-top: 8px; }
        .priority-critical { background: #dc2626; color: white; }
        .priority-high { background: #f59e0b; color: white; }
        .priority-medium { background: #10b981; color: white; }
        .priority-low { background: #94a3b8; color: white; }
        
        /* Explore Button */
        .explore-btn {
            background: none;
            border: none;
            color: #3b82f6;
            cursor: pointer;
            font-size: 12px;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .explore-btn:hover {
            background: #eff6ff;
            color: #2563eb;
        }
        
        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover { background: #f3f4f6; color: #dc2626; }
        
        /* Enhanced Step Items */
        .step-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .step-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
            border-color: #cbd5e1;
        }
        .step-number {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(16,185,129,0.3);
        }
        .step-content { flex: 1; }
        .step-content p { font-size: 14px; color: #1e293b; line-height: 1.5; }
        
        /* Resources Section */
        .resources-section {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }
        .resources-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .resource-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 8px 14px;
            border-radius: 10px;
            margin: 0 8px 8px 0;
            font-size: 13px;
            color: #1e293b;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .resource-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .resource-tag i { color: #3b82f6; }
        
        /* Timeline Section */
        .timeline-section {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }
        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .timeline-content {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 12px 16px;
            border-radius: 10px;
        }
        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .timeline-text { font-size: 14px; color: #78350f; font-weight: 500; }
        
        /* Original Recommendation Card */
        .original-recommendation {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid #f59e0b;
        }
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 16px 0;
        }
        .section-divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, #e2e8f0, transparent);
        }
        .section-divider-icon {
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }

        @media (max-width: 1024px) {
            .mobile-menu-btn { display: block; }
            .mobile-logo { display: flex; }
            .charts-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .legacy-chief-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .legacy-chief-sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .top-nav-container { padding: 0 16px; }
            .step-item { flex-direction: column; align-items: flex-start; }
            .step-number { margin-bottom: 8px; }
        }
    </style>
</head>
<body>

    @include('chief.partials.sidebar', ['active' => 'analytics'])
    <aside class="sidebar legacy-chief-sidebar" style="display:none;">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
                <div class="logo-area">
                    <div class="logo-icon"><i class="fa-solid fa-fire-extinguisher"></i></div>
                    <div class="logo-text"><h2>PyroSense</h2><p>command center</p></div>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span></div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Station Admin' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;"><i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item"><i class="fa-solid fa-file-certificate"></i><span>FSIC Management</span></a>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item active"><i class="fa-solid fa-chart-line"></i><span>Predictive Analytics</span></a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('station-admin.management') }}" class="nav-item"><i class="fa-solid fa-users"></i><span>Management</span></a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside>

    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">AI Predictive Analytics</h1>
                    <p class="text-xs text-slate-500 font-medium">Station intelligence and AI forecasting • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <span class="station-badge" id="selectedStationBadge">Select Location</span>

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

        <div class="content-container">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 28px; font-weight: 700;">🤖 AI-Powered Predictive Analytics</h1>
                <p style="color: #6b7280; margin-top: 4px;">Real-time AI analysis using your actual incident data</p>
            </div>

            <div class="stat-card" style="margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-user"></i> Select Location for Analysis</h3>
                <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto auto; gap: 16px;">
                    <select id="stationSelect" class="form-select">
                        <option value="">Loading locations...</option>
                    </select>
                    <select id="barangaySelect" class="form-select">
                        <option value="all">All barangays in forecast</option>
                    </select>
                    <button id="analyzeBtn" style="background: #dc2626; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-chart-line"></i> Run AI Analysis
                    </button>
                    <button id="predictNextMonthBtn" style="background: #f59e0b; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-calendar-plus"></i> Predict Next Month
                    </button>
                </div>
                <div style="margin-top: 16px;">
                    <select id="periodSelect" class="form-select" style="width: 200px;">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 3 months</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
            </div>
            
            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: #dc2626;"></i>
                    AI Prediction
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Incidents, officers, and weather-based outlook</span>
                </h3>
                <div id="predictionScopeLabel" style="margin-top: 8px; display: none; font-size: 12px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 999px; padding: 6px 10px; width: fit-content;">
                    Prediction scope
                </div>
                <div id="aiPrediction" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Click "Run AI Analysis" for this month or "Predict Next Month" for the next calendar month forecast</div>
                </div>
            </div>

            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #ea580c;"></i>
                    Top Predicted Barangays
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Barangay-level hotspot ranking for the target month</span>
                </h3>
                <div id="barangayPredictionPanel" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Run AI analysis to load barangay hotspot forecasts.</div>
                </div>
            </div>

            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> 
                    AI-Powered Recommendations
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Prioritized by urgency</span>
                </h3>
                <div id="recommendationScopeLabel" style="margin-top: 8px; display: none; font-size: 12px; color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 999px; padding: 6px 10px; width: fit-content;">
                    Recommendation scope
                </div>
                <div id="recommendations" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Click "Run AI Analysis" to get recommendations</div>
                </div>
                <div id="aiExplanation" style="margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #475569; display: none;"></div>
            </div>
            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: #2563eb;"></i>
                    BFP Action Plan Checklist
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Confirm each recommendation one by one</span>
                </h3>
                <div id="actionPlanChecklistPanel" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Run AI analysis to load saved checklist rows.</div>
                </div>
            </div>
            <br>
            <div class="dashboard-grid">
                <div class="stat-card" id="riskCard">
                    <h3><i class="fa-solid fa-gauge-high"></i> AI Risk Assessment</h3>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 48px; font-weight: 800;" id="riskScore">--</div>
                        <div id="riskLabel" style="font-size: 14px; margin-top: 8px;">Loading...</div>
                        <div id="confidenceScore" style="font-size: 12px; margin-top: 4px;">AI Confidence: --%</div>
                    </div>
                    <div id="riskFactors" style="margin-top: 16px;"></div>
                </div>

                <div class="stat-card">
                    <h3><i class="fa-solid fa-fire"></i> Real Incident Statistics</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                        <div><small>Total Incidents</small><div style="font-size: 28px; font-weight: 700;" id="totalIncidents">--</div></div>
                        <div><small>Fire Incidents</small><div style="font-size: 28px; font-weight: 700;" id="fireIncidents">--</div></div>
                        <div><small>Avg Response Time</small><div style="font-size: 20px; font-weight: 700;" id="avgResponse">--</div></div>
                        <div><small>Peak Hour</small><div style="font-size: 20px; font-weight: 700;" id="peakHour">--</div></div>
                    </div>
                    <div id="incidentTypes" style="margin-top: 16px; font-size: 13px;"></div>
                </div>

                <div class="stat-card">
                    <h3><i class="fa-solid fa-truck"></i> Resources & Readiness</h3>
                    <div style="margin-top: 16px;">
                        <div><span>Available Personnel</span> <span id="availablePersonnel" style="float: right; font-weight: 700;">--</span></div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin: 8px 0;"><div id="personnelBar" style="background: #10b981; height: 8px; border-radius: 4px; width: 0%;"></div></div>
                        <div><span>Available Equipment</span> <span id="availableEquipment" style="float: right; font-weight: 700;">--</span></div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin: 8px 0;"><div id="equipmentBar" style="background: #3b82f6; height: 8px; border-radius: 4px; width: 0%;"></div></div>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="stat-card">
                    <h3><i class="fa-solid fa-chart-line"></i> Hourly Incident Pattern</h3>
                    <canvas id="hourlyChart" height="200"></canvas>
                </div>
                <div class="stat-card">
                    <h3><i class="fa-solid fa-calendar-week"></i> Daily Trend (Last 7 Days)</h3>
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Explore Recommendation Modal -->
    <div id="exploreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> AI Implementation Plan</h3>
                <button class="modal-close" onclick="closeExploreModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="exploreModalBody">
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 40px; height: 40px;"></div>
                    <p class="mt-3 text-gray-500">Generating detailed implementation plan...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeExploreModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
                <button class="btn-primary" onclick="closeExploreModal()" style="padding: 8px 20px; background: #dc2626; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Got it</button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div style="text-align: center;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 16px;">🤖 AI is analyzing real incident data...</p>
        </div>
    </div>

    <script>
        let hourlyChart, dailyChart;
        let currentRecommendations = [];
        let currentAnalysisId = null;
        let analyticsStations = [];
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                });
            }
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });
            }
        });

        async function loadStations() {
            try {
                const response = await fetch('/station-admin/api/stations-for-analytics', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                const result = await response.json();
                
                let stations = result.data || [];
                analyticsStations = Array.isArray(stations) ? stations : [];
                const select = document.getElementById('stationSelect');
                const barangaySelect = document.getElementById('barangaySelect');
                
                if (stations.length === 0) {
                    select.innerHTML = '<option value="">No locations found</option>';
                    if (barangaySelect) {
                        barangaySelect.innerHTML = '<option value="all">All barangays in forecast</option>';
                        barangaySelect.disabled = true;
                    }
                    return;
                }
                
                select.innerHTML = '<option value="all">📍 All Locations</option>';
                stations.forEach(station => {
                    select.innerHTML += `<option value="${station.id}">${station.name}</option>`;
                });
                
                if (stations.length > 0) {
                    populateBarangaySelectFromStation(stations[0].id);
                    runAIAnalysis(stations[0].id, 'current_month');
                }
            } catch (error) {
                console.error('Error loading locations:', error);
                document.getElementById('stationSelect').innerHTML = '<option value="">Error loading locations</option>';
            }
        }

        function populateBarangaySelectFromStation(stationId) {
            const barangaySelect = document.getElementById('barangaySelect');
            if (!barangaySelect) return;

            const station = analyticsStations.find((item) => String(item.id) === String(stationId));
            const stationBarangays = Array.isArray(station?.barangays) ? station.barangays : [];

            barangaySelect.innerHTML = '<option value="all">All barangays</option>';

            if (!stationBarangays.length) {
                barangaySelect.disabled = true;
                return;
            }

            stationBarangays.forEach((barangay) => {
                const option = document.createElement('option');
                option.value = String(barangay.barangay_id ?? '');
                option.textContent = barangay.city_name
                    ? `${barangay.barangay_name} · ${barangay.city_name}`
                    : (barangay.barangay_name || `Barangay ${barangay.barangay_id}`);
                barangaySelect.appendChild(option);
            });

            barangaySelect.disabled = false;
        }

        function updateRecommendationScopeLabel() {
            const recommendationLabel = document.getElementById('recommendationScopeLabel');
            const predictionLabel = document.getElementById('predictionScopeLabel');
            const stationSelect = document.getElementById('stationSelect');
            const barangaySelect = document.getElementById('barangaySelect');

            if ((!recommendationLabel && !predictionLabel) || !stationSelect) return;

            const stationName = stationSelect.options[stationSelect.selectedIndex]?.text || 'Selected station';
            const selectedBarangayId = barangaySelect?.value || 'all';
            const selectedBarangayName = barangaySelect?.options?.[barangaySelect.selectedIndex]?.text || 'All barangays';

            if (recommendationLabel) {
                recommendationLabel.textContent = selectedBarangayId !== 'all'
                    ? `Recommendation scope: ${selectedBarangayName} · ${stationName}`
                    : `Recommendation scope: All barangays under ${stationName}`;
                recommendationLabel.style.display = 'inline-flex';
            }

            if (predictionLabel) {
                predictionLabel.textContent = selectedBarangayId !== 'all'
                    ? `Prediction focus: ${selectedBarangayName} · ${stationName}`
                    : `Prediction focus: All barangays under ${stationName}`;
                predictionLabel.style.display = 'inline-flex';
            }
        }

        async function runAIAnalysis(locationId, predictionScope = 'current_month') {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            const periodDays = document.getElementById('periodSelect').value;

            try {
                const response = await fetch('/station-admin/api/ai-predictive-analysis', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        days: parseInt(periodDays),
                        prediction_scope: predictionScope
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('AI Analysis Result:', result);
                
                if (result.success) {
                    updateDashboardWithAIData(result);
                    const scopeLabel = predictionScope === 'next_month' ? 'Next Month Forecast' : 'Current Month Forecast';
                    document.getElementById('selectedStationBadge').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${result.location_name || 'Selected Location'} · ${scopeLabel}`;
                    updateRecommendationScopeLabel();
                } else {
                    alert('Error: ' + (result.message || 'Analysis failed'));
                }
            } catch (error) {
                console.error('Analysis error:', error);
                alert('Failed to run AI analysis: ' + error.message);
            } finally {
                overlay.classList.remove('active');
            }
        }

        async function exploreRecommendation(recommendation, index) {
            const modal = document.getElementById('exploreModal');
            const modalBody = document.getElementById('exploreModalBody');
            
            modal.classList.add('show');
            modalBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 40px; height: 40px;"></div>
                    <p class="mt-3 text-gray-500">Generating detailed implementation plan for "${recommendation.title}"...</p>
                </div>
            `;
            
            try {
                const response = await fetch('/station-admin/api/explore-recommendation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        recommendation_title: recommendation.title,
                        recommendation_description: recommendation.description,
                        priority: recommendation.priority,
                        analysis_id: currentAnalysisId,
                        action_plan_checklist_id: recommendation.action_plan_checklist_id || null
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    displayExploreResults(result.data, recommendation);
                    if (currentAnalysisId) {
                        loadActionPlanChecklistPanel(currentAnalysisId);
                    }
                } else {
                    modalBody.innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i>
                            <p>Failed to generate implementation plan: ${result.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Explore error:', error);
                modalBody.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i>
                        <p>Failed to generate implementation plan. Please try again.</p>
                        <p class="text-sm mt-2 text-gray-500">${error.message}</p>
                    </div>
                `;
            }
        }
        
        function displayExploreResults(data, recommendation) {
            const modalBody = document.getElementById('exploreModalBody');
            
            // Priority color mapping
            const priorityColor = {
                'Critical': '#dc2626',
                'High': '#f59e0b',
                'Medium': '#10b981',
                'Low': '#94a3b8'
            };
            const priorityBg = priorityColor[recommendation.priority] || '#10b981';
            
            let suggestionsHtml = `
                <!-- Original Recommendation Card -->
                <div class="original-recommendation">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <i class="fa-solid fa-quote-left" style="color: #f59e0b;"></i>
                        <strong style="font-size: 15px; color: #1e293b;">Original Recommendation</strong>
                        <span class="priority-badge priority-${recommendation.priority?.toLowerCase() || 'medium'}" style="background: ${priorityBg}; color: white;">${recommendation.priority || 'MEDIUM'} PRIORITY</span>
                    </div>
                    <p style="font-size: 14px; color: #475569; margin-bottom: 8px;"><strong>Title:</strong> ${escapeHtml(recommendation.title)}</p>
                    <p style="font-size: 13px; color: #64748b;"><strong>Description:</strong> ${escapeHtml(recommendation.description)}</p>
                </div>
                
                <!-- Implementation Steps Section -->
                <div class="section-divider">
                    <div class="section-divider-line"></div>
                    <div class="section-divider-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="section-divider-line"></div>
                </div>
                <h4 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clipboard-list" style="color: #10b981;"></i> Implementation Steps
                </h4>
            `;
            
            // Steps with enhanced styling
            if (data.steps && data.steps.length > 0) {
                suggestionsHtml += `<div class="steps-container">`;
                data.steps.forEach((step, idx) => {
                    suggestionsHtml += `
                        <div class="step-item">
                            <div class="step-number">${idx + 1}</div>
                            <div class="step-content">
                                <p>${escapeHtml(step)}</p>
                            </div>
                        </div>
                    `;
                });
                suggestionsHtml += `</div>`;
            } else if (data.suggestions && data.suggestions.length > 0) {
                suggestionsHtml += `<div class="steps-container">`;
                data.suggestions.forEach((suggestion, idx) => {
                    suggestionsHtml += `
                        <div class="step-item">
                            <div class="step-number">${idx + 1}</div>
                            <div class="step-content">
                                <p>${escapeHtml(suggestion)}</p>
                            </div>
                        </div>
                    `;
                });
                suggestionsHtml += `</div>`;
            } else if (data.explanation) {
                suggestionsHtml += `
                    <div class="step-item">
                        <div class="step-number"><i class="fa-solid fa-info"></i></div>
                        <div class="step-content">
                            <p>${escapeHtml(data.explanation)}</p>
                        </div>
                    </div>
                `;
            } else {
                suggestionsHtml += `<p class="text-gray-500 text-center py-4">No detailed steps available for this recommendation.</p>`;
            }
            
            // Resources Needed Section
            if (data.resources_needed && data.resources_needed.length > 0) {
                suggestionsHtml += `
                    <div class="resources-section">
                        <div class="resources-title">
                            <i class="fa-solid fa-toolbox"></i> Resources Needed
                        </div>
                        <div>
                            ${data.resources_needed.map(r => `<span class="resource-tag"><i class="fa-solid fa-circle-check"></i> ${escapeHtml(r)}</span>`).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Estimated Timeline Section
            if (data.estimated_timeline) {
                suggestionsHtml += `
                    <div class="timeline-section">
                        <div class="timeline-title">
                            <i class="fa-solid fa-calendar-week"></i> Estimated Timeline
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="timeline-text">${escapeHtml(data.estimated_timeline)}</div>
                        </div>
                    </div>
                `;
            }
            
            modalBody.innerHTML = suggestionsHtml;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeExploreModal() {
            document.getElementById('exploreModal').classList.remove('show');
        }

        async function loadActionPlanChecklistPanel(analysisId = null) {
            const panel = document.getElementById('actionPlanChecklistPanel');
            if (!panel) return;

            try {
                const url = analysisId
                    ? `/station-admin/api/action-plan-checklists?analysis_id=${encodeURIComponent(analysisId)}`
                    : '/station-admin/api/action-plan-checklists';
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                const checklists = Array.isArray(result.data) ? result.data : [];

                if (!checklists.length) {
                    panel.innerHTML = '<div class="text-center py-8 text-gray-500">No saved checklist rows yet for this analysis.</div>';
                    return;
                }

                panel.innerHTML = checklists.map((item) => {
                    const status = item.status || 'pending';
                    const checklistItems = Array.isArray(item.checklist_items) ? item.checklist_items : [];
                    const suggestions = Array.isArray(item.suggestions) ? item.suggestions : [];

                    return `
                        <div style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; margin-bottom: 14px; background: #fff;">
                            <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                <div>
                                    <div style="font-size: 15px; font-weight: 800; color:#0f172a;">${escapeHtml(item.plan_title || item.source_title || 'Action Plan')}</div>
                                    <div style="font-size: 12px; color:#64748b; margin-top:4px;">${escapeHtml(item.source_title || 'Recommendation')}</div>
                                </div>
                                <span style="padding: 6px 12px; border-radius: 999px; background: ${status === 'confirmed' ? '#dcfce7' : '#fef3c7'}; color: ${status === 'confirmed' ? '#166534' : '#92400e'}; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                                    ${escapeHtml(status)}
                                </span>
                            </div>
                            ${item.plan_description ? `<p style="margin-top:10px; font-size:13px; color:#475569;">${escapeHtml(item.plan_description)}</p>` : ''}
                            ${checklistItems.length ? `
                                <ul style="margin-top:12px; padding-left:18px; color:#334155; font-size:13px;">
                                    ${checklistItems.map((check) => `<li style="margin-bottom:6px;">${escapeHtml(check)}</li>`).join('')}
                                </ul>
                            ` : ''}
                            ${suggestions.length ? `
                                <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                                    <div style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Saved Suggestion History</div>
                                    ${suggestions.map((suggestion) => `
                                        <div style="padding:10px 12px; border-radius:12px; background:#f8fafc; margin-bottom:8px;">
                                            <div style="font-size:12px; color:#64748b;">${escapeHtml(suggestion.generated_at || '')}</div>
                                            <div style="font-size:13px; color:#0f172a; margin-top:4px;">${escapeHtml(suggestion.summary || suggestion.recommendation_title || 'Saved suggestion')}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('');
            } catch (error) {
                console.error('Checklist panel load error:', error);
                panel.innerHTML = `
                    <div class="text-center py-6 text-red-500">
                        Failed to load checklist rows.
                    </div>
                `;
            }
        }

        function updateDashboardWithAIData(data) {
            const analysisData = data.data;
            
            // Store recommendations for explore functionality
            currentAnalysisId = analysisData.analysis_id || null;
            currentRecommendations = analysisData.recommendations || [];
            const predictionContainer = document.getElementById('aiPrediction');
            const prediction = analysisData.predictions || null;

            if (predictionContainer) {
                if (!prediction || !prediction.summary) {
                    predictionContainer.innerHTML = '<div class="text-center py-8 text-gray-500">No prediction available for this dataset</div>';
                } else {
                    const predictionDrivers = (prediction.drivers || [])
                        .map(driver => `<li style="font-size: 13px; color: #475569; margin-bottom: 6px;">${escapeHtml(driver)}</li>`)
                        .join('');
                    const weatherSummary = prediction.weather_context?.summary
                        ? `<div style="margin-top: 12px; padding: 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; font-size: 13px; color: #9a3412;">
                                <strong><i class="fa-solid fa-cloud-sun" style="margin-right: 6px;"></i>Weather Context:</strong> ${escapeHtml(prediction.weather_context.summary)}
                           </div>`
                        : '';
                    const predictedFireCount = prediction.predicted_fire_incidents !== undefined && prediction.predicted_fire_incidents !== null
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.12);">
                                <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.08em;">Possible Fire Incidents</div>
                                <div style="font-size: 24px; font-weight: 800; color: #7f1d1d; margin-top: 4px;">${escapeHtml(String(prediction.predicted_fire_incidents))}</div>
                           </div>`
                        : '';
                    const predictedDamage = prediction.predicted_damage !== undefined && prediction.predicted_damage !== null
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(249,115,22,0.10); border: 1px solid rgba(249,115,22,0.16);">
                                <div style="font-size: 11px; font-weight: 700; color: #c2410c; text-transform: uppercase; letter-spacing: 0.08em;">Estimated Damage Exposure</div>
                                <div style="font-size: 20px; font-weight: 800; color: #9a3412; margin-top: 4px;">PHP ${escapeHtml(Number(prediction.predicted_damage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</div>
                           </div>`
                        : '';
                    const likelyPeakHours = (prediction.likely_peak_hours || []).length
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.18);">
                                <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em;">Likely Peak Hours</div>
                                <div style="font-size: 15px; font-weight: 700; color: #7c2d12; margin-top: 4px;">${escapeHtml(prediction.likely_peak_hours.join(', '))}</div>
                           </div>`
                        : '';
                    const scopeCaption = prediction.prediction_context?.target_period_label
                        ? `<div style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                                Prediction window: <strong>${escapeHtml(prediction.prediction_context.target_period_label)}</strong>
                           </div>`
                        : '';

                    predictionContainer.innerHTML = `
                        <div style="padding: 18px; border-radius: 16px; background: linear-gradient(135deg, rgba(220,38,38,0.08), rgba(245,158,11,0.12)); border: 1px solid rgba(245,158,11,0.25);">
                            <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em;">Forecast Window</div>
                                    <div style="font-size: 16px; font-weight: 700; color: #1f2937; margin-top: 4px;">${escapeHtml(prediction.forecast_window || 'Next 30 days')}</div>
                                </div>
                                <div style="padding: 8px 14px; border-radius: 999px; background: rgba(220,38,38,0.12); color: #b91c1c; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                                    ${escapeHtml(prediction.outlook || 'Medium')} Outlook
                                </div>
                            </div>
                            ${scopeCaption}
                            <p style="margin-top: 14px; font-size: 14px; line-height: 1.6; color: #334155;">${escapeHtml(prediction.summary)}</p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 14px;">
                                ${predictedFireCount}
                                ${predictedDamage}
                                ${likelyPeakHours}
                            </div>
                            ${predictionDrivers ? `<div style="margin-top: 14px;">
                                <div style="font-size: 12px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Primary Drivers</div>
                                <ul style="margin: 0; padding-left: 18px;">${predictionDrivers}</ul>
                            </div>` : ''}
                            ${weatherSummary}
                        </div>
                    `;
                }
            }
            
            // Risk assessment
            const riskScore = analysisData.risk_score || 0;
            const riskCard = document.getElementById('riskCard');
            riskCard.className = 'stat-card ' + (riskScore >= 70 ? 'risk-high' : (riskScore >= 40 ? 'risk-medium' : 'risk-low'));
            document.getElementById('riskScore').innerHTML = riskScore + '/100';
            document.getElementById('riskLabel').innerHTML = analysisData.risk_level || 'Not Available';
            document.getElementById('confidenceScore').innerHTML = `AI Confidence: ${analysisData.confidence_score || 85}%`;
            
            let riskFactorsHtml = '<strong>AI-Identified Risk Factors:</strong><ul style="margin-top:8px;margin-left:16px;">';
            (analysisData.risk_factors || ['No risk factors identified']).forEach(factor => {
                riskFactorsHtml += `<li style="font-size:13px;margin-bottom:4px;">• ${factor}</li>`;
            });
            riskFactorsHtml += '</ul>';
            document.getElementById('riskFactors').innerHTML = riskFactorsHtml;

            // Incident stats
            document.getElementById('totalIncidents').innerHTML = analysisData.total_incidents || 0;
            document.getElementById('fireIncidents').innerHTML = analysisData.fire_incidents || 0;
            document.getElementById('avgResponse').innerHTML = (analysisData.avg_response_time || 'N/A') + (analysisData.avg_response_time ? ' min' : '');
            document.getElementById('peakHour').innerHTML = analysisData.peak_hour ? analysisData.peak_hour + ':00' : 'N/A';
            
            // Resources
            const personnelPercent = analysisData.total_personnel > 0 ? (analysisData.available_personnel / analysisData.total_personnel) * 100 : 0;
            document.getElementById('availablePersonnel').innerHTML = `${analysisData.available_personnel || 0}/${analysisData.total_personnel || 0}`;
            document.getElementById('personnelBar').style.width = personnelPercent + '%';
            
            const equipPercent = analysisData.total_equipment > 0 ? (analysisData.available_equipment / analysisData.total_equipment) * 100 : 0;
            document.getElementById('availableEquipment').innerHTML = `${analysisData.available_equipment || 0}/${analysisData.total_equipment || 0}`;
            document.getElementById('equipmentBar').style.width = equipPercent + '%';

            // Charts
            if (hourlyChart) hourlyChart.destroy();
            if (dailyChart) dailyChart.destroy();

            const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
            hourlyChart = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: analysisData.hourly_labels || Array.from({length: 24}, (_, i) => i + ':00'),
                    datasets: [{ label: 'Incidents by Hour', data: analysisData.hourly_data || new Array(24).fill(0), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.1)', fill: true, tension: 0.4 }]
                },
                options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
            });

            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{ label: 'Incidents', data: analysisData.daily_data || [0,0,0,0,0,0,0], backgroundColor: '#ef4444', borderRadius: 6 }]
                },
                options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
            });

            // Recommendations with Explore button
            const recommendations = analysisData.recommendations || [];
            const recContainer = document.getElementById('recommendations');
            
            if (recommendations.length === 0) {
                recContainer.innerHTML = '<div class="text-center py-8 text-gray-500">No recommendations available</div>';
            } else {
                let recHtml = '<div>';
                recommendations.forEach((rec, idx) => {
                    let priorityClass = 'medium';
                    let priorityLabel = 'MEDIUM';
                    if (rec.priority === 'Critical') { priorityClass = 'critical'; priorityLabel = 'CRITICAL'; }
                    else if (rec.priority === 'High') { priorityClass = 'high'; priorityLabel = 'HIGH'; }
                    else if (rec.priority === 'Low') { priorityClass = 'low'; priorityLabel = 'LOW'; }
                    
                    recHtml += `
                        <div class="recommendation-card ${priorityClass}">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 28px; height: 28px; background: ${priorityClass === 'critical' ? '#dc2626' : (priorityClass === 'high' ? '#f59e0b' : (priorityClass === 'medium' ? '#10b981' : '#94a3b8'))}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">${idx+1}</div>
                                <div style="flex:1;">
                                    <strong>${escapeHtml(rec.title || 'Recommendation')}</strong>
                                    <p style="color: #4b5563; font-size: 13px; margin-top: 4px;">${escapeHtml(rec.description)}</p>
                                    <span class="priority-badge priority-${priorityClass}">${priorityLabel} PRIORITY</span>
                                    <button class="explore-btn" onclick="exploreRecommendation(${JSON.stringify(rec).replace(/"/g, '&quot;')}, ${idx})">
                                        <i class="fa-solid fa-chart-simple"></i> Explore & Get Detailed Suggestions
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                recHtml += '</div>';
                recContainer.innerHTML = recHtml;
            }
            
            if (analysisData.ai_explanation) {
                document.getElementById('aiExplanation').style.display = 'block';
                document.getElementById('aiExplanation').innerHTML = `<strong><i class="fa-solid fa-brain"></i> AI Analysis Summary:</strong><br>${escapeHtml(analysisData.ai_explanation)}`;
            }
        }

        function emergencyCall() {
            if (confirm('Call emergency services (911)?')) window.location.href = 'tel:911';
        }

        document.getElementById('analyzeBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'current_month');
            else alert('Please select a location');
        });

        document.getElementById('predictNextMonthBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'next_month');
            else alert('Please select a location');
        });

        document.getElementById('stationSelect')?.addEventListener('change', (e) => {
            populateBarangaySelectFromStation(e.target.value);
            if (e.target.value) runAIAnalysis(e.target.value, 'current_month');
        });

        document.getElementById('barangaySelect')?.addEventListener('change', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect?.value) runAIAnalysis(stationSelect.value, 'current_month');
        });

        document.getElementById('periodSelect')?.addEventListener('change', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'current_month');
        });

        loadStations();
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>


{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'station_admin' }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <title>PyroSense · AI Predictive Analytics</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .legacy-chief-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 50;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .legacy-chief-sidebar .sidebar-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
        }

        .legacy-chief-sidebar .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
        }

        .legacy-chief-sidebar .sidebar-header-content { position: relative; z-index: 1; }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .logo-icon { width: 48px; height: 48px; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .logo-icon i { font-size: 24px; color: white; }
        .logo-text h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .logo-text p { font-size: 12px; opacity: 0.8; }

        .user-info { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.2); }
        .user-avatar { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; }
        .user-details h4 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .user-details span { font-size: 12px; opacity: 0.8; display: flex; align-items: center; gap: 4px; }
        .user-details span i { font-size: 8px; color: #4ade80; }

        .legacy-chief-sidebar .sidebar-nav { flex: 1; overflow-y: auto; padding: 20px 16px; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { font-size: 12px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; padding: 0 12px; margin-bottom: 8px; }
        .legacy-chief-sidebar .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #4b5563; text-decoration: none; border-radius: 12px; transition: all 0.2s; margin-bottom: 2px; }
        .legacy-chief-sidebar .nav-item:hover { background: #f3f4f6; }
        .legacy-chief-sidebar .nav-item.active { background: #fee2e2; color: #dc2626; font-weight: 500; }
        .legacy-chief-sidebar .nav-item i { width: 20px; font-size: 16px; }

        .logout-container { padding: 20px; border-top: 1px solid #e5e7eb; }
        .logout-btn { width: 100%; padding: 12px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: #4b5563; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
        .logout-btn:hover { background: #fee2e2; color: #dc2626; }

        .main-content { margin-left: 280px; flex: 1; min-width: 0; background-color: #f3f4f6; min-height: 100vh; display: flex; flex-direction: column; }
        .top-nav { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 30; padding: 0 24px; }
        .top-nav-container { display: flex; justify-content: space-between; align-items: center; height: 64px; max-width: 1600px; margin: 0 auto; }
        .mobile-menu-btn { display: none; background: none; border: none; color: #4b5563; font-size: 1.5rem; cursor: pointer; }
        .mobile-logo { display: none; align-items: center; gap: 8px; }
        .top-nav-actions { display: flex; align-items: center; gap: 16px; }
        .station-badge { color: #6b7280; font-size: 14px; background: #f3f4f6; padding: 6px 12px; border-radius: 20px; }
        .emergency-btn { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s; }
        .emergency-btn:hover { background: #b91c1c; }

        .content-container { max-width: 1400px; margin: 0 auto; padding: 24px; width: 100%; }
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s; }
        .risk-high { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        .risk-medium { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .risk-low { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .form-select { padding: 10px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; width: 100%; }

        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; display: none; }
        .loading-overlay.active { display: flex; }
        .loading-spinner { border: 3px solid #f3f3f3; border-top: 3px solid #dc2626; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .recommendation-card { padding: 16px; border-radius: 12px; margin-bottom: 12px; transition: all 0.2s; }
        .recommendation-card.critical { background: #fef2f2; border-left: 4px solid #dc2626; }
        .recommendation-card.high { background: #fffbeb; border-left: 4px solid #f59e0b; }
        .recommendation-card.medium { background: #f0fdf4; border-left: 4px solid #10b981; }
        .recommendation-card.low { background: #f8fafc; border-left: 4px solid #94a3b8; }
        .priority-badge { display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin-top: 8px; }
        .priority-critical { background: #dc2626; color: white; }
        .priority-high { background: #f59e0b; color: white; }
        .priority-medium { background: #10b981; color: white; }
        .priority-low { background: #94a3b8; color: white; }
        
        /* Explore Button */
        .explore-btn {
            background: none;
            border: none;
            color: #3b82f6;
            cursor: pointer;
            font-size: 12px;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .explore-btn:hover {
            background: #eff6ff;
            color: #2563eb;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6b7280;
        }
        .modal-close:hover { color: #dc2626; }
        
        /* Suggestion styling */
        .suggestion-item {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 8px;
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-icon { color: #10b981; margin-right: 8px; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }

        @media (max-width: 1024px) {
            .mobile-menu-btn { display: block; }
            .mobile-logo { display: flex; }
            .charts-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .legacy-chief-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .legacy-chief-sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .top-nav-container { padding: 0 16px; }
        }
    </style>
</head>
<body>

    @include('chief.partials.sidebar', ['active' => 'analytics'])
    <aside class="sidebar legacy-chief-sidebar" style="display:none;">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
                <div class="logo-area">
                    <div class="logo-icon"><i class="fa-solid fa-fire-extinguisher"></i></div>
                    <div class="logo-text"><h2>PyroSense</h2><p>command center</p></div>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span></div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Station Admin' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;"><i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item"><i class="fa-solid fa-file-certificate"></i><span>FSIC Management</span></a>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item active"><i class="fa-solid fa-chart-line"></i><span>Predictive Analytics</span></a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('station-admin.management') }}" class="nav-item"><i class="fa-solid fa-users"></i><span>Management</span></a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside>

    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <main class="main-content">
        <nav class="top-nav">
            <div class="top-nav-container">
                <div class="flex items-center gap-3">
                    <button id="openSidebar" class="mobile-menu-btn"><i class="fa-solid fa-bars"></i></button>
                    <div class="mobile-logo"><div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center"><i class="fa-solid fa-fire-extinguisher text-white text-sm"></i></div><span class="text-lg font-bold text-gray-800">Pyro<span class="text-red-600">Sense</span></span></div>
                </div>
                <div class="top-nav-actions">
                    <span class="station-badge" id="selectedStationBadge">Select Location</span>
                    <button class="emergency-btn" onclick="emergencyCall()"><i class="fa-solid fa-phone-alt"></i><span class="hidden sm:inline">Emergency</span></button>
                </div>
            </div>
        </nav>

        <div class="content-container">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 28px; font-weight: 700;">🤖 AI-Powered Predictive Analytics</h1>
                <p style="color: #6b7280; margin-top: 4px;">Real-time AI analysis using your actual incident data</p>
            </div>

            <div class="stat-card" style="margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-user"></i> Select Location for Analysis</h3>
                <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto auto; gap: 16px;">
                    <select id="stationSelect" class="form-select">
                        <option value="">Loading locations...</option>
                    </select>
                    <select id="barangaySelect" class="form-select">
                        <option value="all">All barangays in forecast</option>
                    </select>
                    <button id="analyzeBtn" style="background: #dc2626; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-chart-line"></i> Run AI Analysis
                    </button>
                    <button id="predictNextMonthBtn" style="background: #f59e0b; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-calendar-plus"></i> Predict Next Month
                    </button>
                </div>
                <div style="margin-top: 16px;">
                    <select id="periodSelect" class="form-select" style="width: 200px;">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 3 months</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
            </div>
            
            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: #dc2626;"></i>
                    AI Prediction
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Incidents, officers, and weather-based outlook</span>
                </h3>
                <div id="aiPrediction" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Click "Run AI Analysis" for this month or "Predict Next Month" for the next calendar month forecast</div>
                </div>
            </div>

            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #ea580c;"></i>
                    Top Predicted Barangays
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Barangay-level hotspot ranking for the target month</span>
                </h3>
                <div id="barangayPredictionPanel" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Run AI analysis to load barangay hotspot forecasts.</div>
                </div>
            </div>

            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> 
                    AI-Powered Recommendations
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Prioritized by urgency</span>
                </h3>
                <div id="recommendations" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Click "Run AI Analysis" to get recommendations</div>
                </div>
                <div id="aiExplanation" style="margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #475569; display: none;"></div>
            </div>
            <div class="stat-card mb-2">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: #2563eb;"></i>
                    BFP Action Plan Checklist
                    <span style="font-size: 11px; font-weight: normal; color: #6b7280; margin-left: auto;">Confirm each recommendation one by one</span>
                </h3>
                <div id="actionPlanChecklistPanel" style="margin-top: 16px;">
                    <div class="text-center py-8 text-gray-500">Run AI analysis to load saved checklist rows.</div>
                </div>
            </div>
            <br>
            <div class="dashboard-grid">
                <div class="stat-card" id="riskCard">
                    <h3><i class="fa-solid fa-gauge-high"></i> AI Risk Assessment</h3>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 48px; font-weight: 800;" id="riskScore">--</div>
                        <div id="riskLabel" style="font-size: 14px; margin-top: 8px;">Loading...</div>
                        <div id="confidenceScore" style="font-size: 12px; margin-top: 4px;">AI Confidence: --%</div>
                    </div>
                    <div id="riskFactors" style="margin-top: 16px;"></div>
                </div>

                <div class="stat-card">
                    <h3><i class="fa-solid fa-fire"></i> Real Incident Statistics</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                        <div><small>Total Incidents</small><div style="font-size: 28px; font-weight: 700;" id="totalIncidents">--</div></div>
                        <div><small>Fire Incidents</small><div style="font-size: 28px; font-weight: 700;" id="fireIncidents">--</div></div>
                        <div><small>Avg Response Time</small><div style="font-size: 20px; font-weight: 700;" id="avgResponse">--</div></div>
                        <div><small>Peak Hour</small><div style="font-size: 20px; font-weight: 700;" id="peakHour">--</div></div>
                    </div>
                    <div id="incidentTypes" style="margin-top: 16px; font-size: 13px;"></div>
                </div>

                <div class="stat-card">
                    <h3><i class="fa-solid fa-truck"></i> Resources & Readiness</h3>
                    <div style="margin-top: 16px;">
                        <div><span>Available Personnel</span> <span id="availablePersonnel" style="float: right; font-weight: 700;">--</span></div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin: 8px 0;"><div id="personnelBar" style="background: #10b981; height: 8px; border-radius: 4px; width: 0%;"></div></div>
                        <div><span>Available Equipment</span> <span id="availableEquipment" style="float: right; font-weight: 700;">--</span></div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin: 8px 0;"><div id="equipmentBar" style="background: #3b82f6; height: 8px; border-radius: 4px; width: 0%;"></div></div>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="stat-card">
                    <h3><i class="fa-solid fa-chart-line"></i> Hourly Incident Pattern</h3>
                    <canvas id="hourlyChart" height="200"></canvas>
                </div>
                <div class="stat-card">
                    <h3><i class="fa-solid fa-calendar-week"></i> Daily Trend (Last 7 Days)</h3>
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Explore Recommendation Modal -->
    <div id="exploreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> Explore Recommendation</h3>
                <button class="modal-close" onclick="closeExploreModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="exploreModalBody">
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 30px; height: 30px;"></div>
                    <p class="mt-3 text-gray-500">Generating detailed suggestions...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeExploreModal()" style="padding: 8px 16px; background: #e5e7eb; border: none; border-radius: 8px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div style="text-align: center;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 16px;">🤖 AI is analyzing real incident data...</p>
        </div>
    </div>

    <script>
        let hourlyChart, dailyChart;
        let currentRecommendations = [];
        let currentAnalysisId = null;
        let currentActionPlanChecklists = [];
        let currentBarangayPredictions = [];
        let currentBarangaySelection = 'all';
        let currentPredictionScope = 'current_month';
        let analyticsStations = [];
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                });
            }
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }
        });

        async function loadStations() {
            try {
                const response = await fetch('/station-admin/api/stations-for-analytics', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                const result = await response.json();
                
                let stations = result.data || [];
                analyticsStations = Array.isArray(stations) ? stations : [];
                const select = document.getElementById('stationSelect');
                
                if (stations.length === 0) {
                    select.innerHTML = '<option value="">No locations found</option>';
                    resetBarangaySelect();
                    return;
                }
                
                select.innerHTML = '';
                stations.forEach(station => {
                    select.innerHTML += `<option value="${station.id}">${station.name}</option>`;
                });
                
                if (stations.length > 0) {
                    populateBarangaySelectFromStation(stations[0].id);
                    updateRecommendationScopeLabel();
                    runAIAnalysis(stations[0].id, 'current_month');
                }
            } catch (error) {
                console.error('Error loading locations:', error);
                document.getElementById('stationSelect').innerHTML = '<option value="">Error loading locations</option>';
                resetBarangaySelect();
            }
        }

        function resetBarangaySelect() {
            const select = document.getElementById('barangaySelect');
            if (!select) return;

            currentBarangaySelection = 'all';
            select.innerHTML = '<option value="all">All barangays in forecast</option>';
            select.disabled = true;
        }

        function populateBarangaySelectFromStation(stationId) {
            const station = analyticsStations.find((item) => String(item.id) === String(stationId));
            const stationBarangays = Array.isArray(station?.barangays) ? station.barangays : [];
            const select = document.getElementById('barangaySelect');
            if (!select) return;

            currentBarangaySelection = 'all';
            select.innerHTML = '<option value="all">All barangays</option>';

            if (!stationBarangays.length) {
                select.disabled = true;
                return;
            }

            stationBarangays.forEach((barangay) => {
                const option = document.createElement('option');
                option.value = String(barangay.barangay_id ?? '');
                option.textContent = barangay.city_name
                    ? `${barangay.barangay_name} · ${barangay.city_name}`
                    : (barangay.barangay_name || `Barangay ${barangay.barangay_id}`);
                select.appendChild(option);
            });

            select.disabled = false;
        }

        function populateBarangaySelect(predictions = []) {
            const select = document.getElementById('barangaySelect');
            if (!select) return;

            const previousSelection = currentBarangaySelection;
            const stationSelect = document.getElementById('stationSelect');
            const station = analyticsStations.find((item) => String(item.id) === String(stationSelect?.value || ''));
            const stationBarangays = Array.isArray(station?.barangays) ? station.barangays : [];
            const mergedBarangays = new Map();

            stationBarangays.forEach((barangay) => {
                if (barangay?.barangay_id == null) return;
                mergedBarangays.set(String(barangay.barangay_id), {
                    barangay_id: barangay.barangay_id,
                    barangay_name: barangay.barangay_name,
                    city_name: barangay.city_name ?? null,
                });
            });

            predictions.forEach((item) => {
                if (item?.barangay_id == null) return;
                mergedBarangays.set(String(item.barangay_id), {
                    barangay_id: item.barangay_id,
                    barangay_name: item.barangay_name,
                    city_name: item.city_name ?? null,
                });
            });

            select.innerHTML = '<option value="all">All barangays</option>';

            if (!mergedBarangays.size) {
                select.disabled = true;
                currentBarangaySelection = 'all';
                return;
            }

            Array.from(mergedBarangays.values()).forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.barangay_id ?? '');
                option.textContent = item.city_name
                    ? `${item.barangay_name} · ${item.city_name}`
                    : (item.barangay_name || `Barangay ${item.barangay_id}`);
                select.appendChild(option);
            });

            select.disabled = false;

            const hasPreviousSelection = mergedBarangays.has(String(previousSelection));
            currentBarangaySelection = hasPreviousSelection ? previousSelection : 'all';
            select.value = currentBarangaySelection;
        }

        function updateRecommendationScopeLabel() {
            const label = document.getElementById('recommendationScopeLabel');
            const predictionLabel = document.getElementById('predictionScopeLabel');
            const stationSelect = document.getElementById('stationSelect');
            const barangaySelect = document.getElementById('barangaySelect');

            if ((!label && !predictionLabel) || !stationSelect || !barangaySelect) return;

            const stationName = stationSelect.options[stationSelect.selectedIndex]?.text || 'Selected station';
            const selectedBarangayId = barangaySelect.value || 'all';
            const selectedBarangayName = barangaySelect.options[barangaySelect.selectedIndex]?.text || 'All barangays';

            label.textContent = selectedBarangayId !== 'all'
                ? `Recommendation scope: ${selectedBarangayName} · ${stationName}`
                : `Recommendation scope: All barangays under ${stationName}`;
            label.style.display = 'inline-flex';

            if (predictionLabel) {
                predictionLabel.textContent = selectedBarangayId !== 'all'
                    ? `Prediction focus: ${selectedBarangayName} · ${stationName}`
                    : `Prediction focus: All barangays under ${stationName}`;
                predictionLabel.style.display = 'inline-flex';
            }
        }

        async function runAIAnalysis(locationId, predictionScope = 'current_month') {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');
            currentPredictionScope = predictionScope;

            const periodDays = document.getElementById('periodSelect').value;
            const barangaySelect = document.getElementById('barangaySelect');
            const selectedBarangayId = barangaySelect && barangaySelect.value && barangaySelect.value !== 'all'
                ? parseInt(barangaySelect.value, 10)
                : null;

            try {
                const response = await fetch('/station-admin/api/ai-predictive-analysis', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        barangay_id: selectedBarangayId,
                        days: parseInt(periodDays),
                        prediction_scope: predictionScope
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('AI Analysis Result:', result);
                
                if (result.success) {
                    updateDashboardWithAIData(result);
                    const scopeLabel = predictionScope === 'next_month' ? 'Next Month Forecast' : 'Current Month Forecast';
                    document.getElementById('selectedStationBadge').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${result.location_name || 'Selected Location'} · ${scopeLabel}`;
                } else {
                    alert('Error: ' + (result.message || 'Analysis failed'));
                }
            } catch (error) {
                console.error('Analysis error:', error);
                alert('Failed to run AI analysis: ' + error.message);
            } finally {
                overlay.classList.remove('active');
            }
        }

        async function exploreRecommendation(recommendation, index) {
            const modal = document.getElementById('exploreModal');
            const modalBody = document.getElementById('exploreModalBody');
            
            modal.classList.add('show');
            modalBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 30px; height: 30px;"></div>
                    <p class="mt-3 text-gray-500">Generating detailed suggestions for "${recommendation.title}"...</p>
                </div>
            `;
            
            try {
                const response = await fetch('/station-admin/api/explore-recommendation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        recommendation_title: recommendation.title,
                        recommendation_description: recommendation.description,
                        priority: recommendation.priority,
                        analysis_id: currentAnalysisId,
                        action_plan_checklist_id: recommendation.action_plan_checklist_id || null
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    displayExploreResults(result.data, recommendation);
                } else {
                    modalBody.innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fa-solid fa-circle-exclamation text-3xl mb-3"></i>
                            <p>Failed to generate suggestions: ${result.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Explore error:', error);
                modalBody.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fa-solid fa-circle-exclamation text-3xl mb-3"></i>
                        <p>Failed to generate suggestions. Please try again.</p>
                        <p class="text-sm mt-2">${error.message}</p>
                    </div>
                `;
            }
        }
        
        function displayExploreResults(data, recommendation) {
            const modalBody = document.getElementById('exploreModalBody');
            
            let suggestionsHtml = `
                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">Original Recommendation</h4>
                    <p class="text-sm text-gray-700"><strong>Title:</strong> ${escapeHtml(recommendation.title)}</p>
                    <p class="text-sm text-gray-700 mt-1"><strong>Description:</strong> ${escapeHtml(recommendation.description)}</p>
                    <span class="priority-badge priority-${recommendation.priority?.toLowerCase() || 'medium'}">${recommendation.priority || 'MEDIUM'} PRIORITY</span>
                </div>
                <h4 class="font-semibold text-gray-800 mb-3">📋 Detailed Implementation Plan</h4>
            `;
            
            if (data.steps && data.steps.length > 0) {
                suggestionsHtml += `<div class="space-y-3">`;
                data.steps.forEach((step, idx) => {
                    suggestionsHtml += `
                        <div class="suggestion-item">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 24px; height: 24px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">${idx + 1}</div>
                                <div style="flex: 1;">
                                    <p style="font-size: 14px; color: #1f2937;">${escapeHtml(step)}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                suggestionsHtml += `</div>`;
            } else if (data.suggestions && data.suggestions.length > 0) {
                suggestionsHtml += `<div class="space-y-3">`;
                data.suggestions.forEach((suggestion, idx) => {
                    suggestionsHtml += `
                        <div class="suggestion-item">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 24px; height: 24px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">${idx + 1}</div>
                                <div style="flex: 1;">
                                    <p style="font-size: 14px; color: #1f2937;">${escapeHtml(suggestion)}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                suggestionsHtml += `</div>`;
            } else if (data.explanation) {
                suggestionsHtml += `
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <p style="font-size: 14px; line-height: 1.6; color: #1f2937;">${escapeHtml(data.explanation)}</p>
                    </div>
                `;
            } else {
                suggestionsHtml += `<p class="text-gray-500 text-center py-4">No detailed suggestions available for this recommendation.</p>`;
            }
            
            if (data.resources_needed && data.resources_needed.length > 0) {
                suggestionsHtml += `
                    <h4 class="font-semibold text-gray-800 mb-3 mt-4">🛠️ Resources Needed</h4>
                    <ul class="list-disc pl-5 space-y-1">
                        ${data.resources_needed.map(r => `<li class="text-sm text-gray-600">${escapeHtml(r)}</li>`).join('')}
                    </ul>
                `;
            }
            
            if (data.estimated_timeline) {
                suggestionsHtml += `
                    <h4 class="font-semibold text-gray-800 mb-3 mt-4">⏱️ Estimated Timeline</h4>
                    <p class="text-sm text-gray-600 p-3 bg-gray-50 rounded-lg">${escapeHtml(data.estimated_timeline)}</p>
                `;
            }
            
            modalBody.innerHTML = suggestionsHtml;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeExploreModal() {
            document.getElementById('exploreModal').classList.remove('show');
        }

        async function loadActionPlanChecklistPanel(analysisId = null) {
            try {
                const url = analysisId
                    ? `/station-admin/api/action-plan-checklists?analysis_id=${encodeURIComponent(analysisId)}`
                    : '/station-admin/api/action-plan-checklists';
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                currentActionPlanChecklists = result.data || [];
                renderActionPlanChecklistPanel(currentActionPlanChecklists);
            } catch (error) {
                console.error('Checklist panel load error:', error);
                document.getElementById('actionPlanChecklistPanel').innerHTML = `
                    <div class="text-center py-6 text-red-500">
                        Failed to load checklist rows.
                    </div>
                `;
            }
        }

        function renderActionPlanChecklistPanel(checklists = []) {
            const panel = document.getElementById('actionPlanChecklistPanel');
            if (!panel) return;

            if (!checklists.length) {
                panel.innerHTML = '<div class="text-center py-8 text-gray-500">No saved checklist rows yet for this analysis.</div>';
                return;
            }

            panel.innerHTML = checklists.map((item) => {
                const checklistItems = Array.isArray(item.checklist_items) ? item.checklist_items : [];
                const suggestions = Array.isArray(item.suggestions) ? item.suggestions : [];
                const status = item.status || 'pending';
                const confirmButton = status === 'confirmed'
                    ? `<span style="padding: 6px 12px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700;">CONFIRMED</span>`
                    : `<button onclick="confirmChecklistRow(${item.ai_action_plan_checklist_id}, 'confirmed')" style="padding: 8px 14px; border-radius: 10px; border: none; background: #16a34a; color: white; font-size: 12px; font-weight: 700; cursor: pointer;">Confirm</button>`;

                return `
                    <div style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; margin-bottom: 14px; background: #fff;">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                            <div>
                                <div style="font-size: 15px; font-weight: 800; color:#0f172a;">${escapeHtml(item.plan_title || item.source_title || 'Action Plan')}</div>
                                <div style="font-size: 12px; color:#64748b; margin-top:4px;">${escapeHtml(item.source_title || 'Recommendation')}</div>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <span class="priority-badge priority-${escapeHtml((item.priority_level || 'medium').toLowerCase())}">${escapeHtml((item.priority_level || 'medium').toUpperCase())}</span>
                                <span style="padding: 6px 12px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700;">${escapeHtml((status || 'pending').replaceAll('_', ' ').toUpperCase())}</span>
                                ${confirmButton}
                            </div>
                        </div>
                        <p style="margin-top:10px; font-size:13px; line-height:1.6; color:#475569;">${escapeHtml(item.plan_description || item.source_description || '')}</p>
                        ${item.success_indicator ? `<div style="margin-top:10px; padding:10px 12px; background:#f8fafc; border-radius:12px; font-size:12px; color:#334155;"><strong>Success indicator:</strong> ${escapeHtml(item.success_indicator)}</div>` : ''}
                        ${checklistItems.length ? `<div style="margin-top:12px;">
                            <div style="font-size:12px; font-weight:700; color:#0f172a; margin-bottom:8px;">Checklist Steps</div>
                            <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.6;">
                                ${checklistItems.map((check) => `<li>${escapeHtml(check)}</li>`).join('')}
                            </ul>
                        </div>` : ''}
                        ${suggestions.length ? `<div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                            <div style="font-size:12px; font-weight:700; color:#0f172a; margin-bottom:8px;">Saved Suggestion History</div>
                            ${suggestions.map((suggestion) => `
                                <div style="padding:10px 12px; background:#fffbeb; border:1px solid #fde68a; border-radius:12px; margin-bottom:8px;">
                                    <div style="font-size:12px; font-weight:700; color:#92400e;">${escapeHtml(suggestion.recommendation_title || 'Suggestion')}</div>
                                    <div style="font-size:12px; color:#6b7280; margin-top:4px;">${escapeHtml(suggestion.estimated_timeline || 'Timeline not specified')}</div>
                                    ${Array.isArray(suggestion.steps) && suggestion.steps.length ? `<ul style="margin:8px 0 0; padding-left:18px; font-size:12px; color:#4b5563;">${suggestion.steps.slice(0, 3).map((step) => `<li>${escapeHtml(step)}</li>`).join('')}</ul>` : ''}
                                </div>
                            `).join('')}
                        </div>` : '<div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b;">No saved explore-suggestion history yet for this row.</div>'}
                    </div>
                `;
            }).join('');
        }

        function renderBarangayPredictionPanel(predictions = []) {
            const panel = document.getElementById('barangayPredictionPanel');
            if (!panel) return;

            const filteredPredictions = currentBarangaySelection !== 'all'
                ? predictions.filter((item) => String(item.barangay_id) === String(currentBarangaySelection))
                : predictions;

            if (!filteredPredictions.length) {
                panel.innerHTML = '<div class="text-center py-8 text-gray-500">No barangay hotspot forecasts available for this analysis yet.</div>';
                return;
            }

            const selectedLabel = currentBarangaySelection !== 'all'
                ? `<div style="margin-bottom:12px; padding:10px 12px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; font-size:12px; color:#9a3412;">
                        Focused view: <strong>${escapeHtml(filteredPredictions[0]?.barangay_name || 'Selected barangay')}</strong>
                   </div>`
                : '';

            panel.innerHTML = selectedLabel + filteredPredictions.map((item, index) => {
                const probability = Number(item.probability_percent || 0);
                const riskLevel = String(item.risk_level || 'low').toLowerCase();
                const badgeLevel = riskLevel === 'moderate' ? 'medium' : riskLevel;
                const cityName = item.city_name ? `<span style="font-size:12px; color:#94a3b8;">${escapeHtml(item.city_name)}</span>` : '';
                const watchItems = Array.isArray(item.watch_items) && item.watch_items.length
                    ? `<div style="margin-top:8px; font-size:12px; color:#64748b;"><strong>Watch:</strong> ${escapeHtml(item.watch_items.join(', '))}</div>`
                    : '';
                const topDrivers = Array.isArray(item.drivers)
                    ? item.drivers.slice(0, 2).map((driver) => `
                        <li style="font-size:12px; color:#475569; margin-bottom:4px;">${escapeHtml(driver.summary || '')}</li>
                    `).join('')
                    : '';
                const priority = probability >= 80 ? 'Critical' : (probability >= 65 ? 'High' : (probability >= 40 ? 'Medium' : 'Low'));
                const rec = {
                    title: `Mitigate fire risk in ${item.barangay_name || 'this barangay'}`,
                    description: `Forecasted ${riskLevel} barangay hotspot risk for ${item.barangay_name || 'this barangay'} with ${probability.toFixed(2)}% probability. Focus on local drivers and prevention readiness.`,
                    priority
                };

                return `
                    <div style="border:1px solid #e2e8f0; border-radius:16px; padding:16px; margin-bottom:14px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="width:32px; height:32px; border-radius:50%; background:${index === 0 ? '#dc2626' : '#f59e0b'}; color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800;">${index + 1}</div>
                                <div>
                                    <div style="font-size:15px; font-weight:800; color:#0f172a;">${escapeHtml(item.barangay_name || 'Unknown barangay')}</div>
                                    ${cityName}
                                </div>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <span class="priority-badge priority-${escapeHtml(badgeLevel)}">${escapeHtml(riskLevel.toUpperCase())}</span>
                                <span style="padding:6px 12px; border-radius:999px; background:#fff7ed; color:#c2410c; font-size:12px; font-weight:700;">${escapeHtml(probability.toFixed(2))}%</span>
                            </div>
                        </div>
                        <div style="margin-top:12px; display:grid; grid-template-columns:minmax(0, 1fr) 104px; gap:12px; align-items:center;">
                            <div style="background:#f1f5f9; height:10px; border-radius:999px; overflow:hidden;">
                                <div style="width:${Math.max(4, Math.min(100, probability))}%; height:100%; background:${probability >= 80 ? '#dc2626' : (probability >= 65 ? '#ea580c' : (probability >= 40 ? '#f59e0b' : '#10b981'))}; border-radius:999px;"></div>
                            </div>
                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.08em; text-align:right;">${escapeHtml(item.target || 'barangay risk')}</div>
                        </div>
                        ${watchItems}
                        ${topDrivers ? `<div style="margin-top:10px;">
                            <div style="font-size:12px; font-weight:700; color:#0f172a; margin-bottom:6px;">Top Drivers</div>
                            <ul style="margin:0; padding-left:18px;">${topDrivers}</ul>
                        </div>` : ''}
                        <div style="margin-top:12px;">
                            <button class="explore-btn" onclick="exploreRecommendation(${JSON.stringify(rec).replace(/"/g, '&quot;')}, ${index})">
                                <i class="fa-solid fa-chart-simple"></i> Explore Suggestions
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function confirmChecklistRow(id, status) {
            try {
                const response = await fetch(`/station-admin/api/action-plan-checklists/${id}/confirm`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status,
                        confirmation_notes: status === 'confirmed'
                            ? 'Confirmed by BFP through Chief analytics panel.'
                            : null
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                await loadActionPlanChecklistPanel(currentAnalysisId);
            } catch (error) {
                console.error('Checklist confirmation error:', error);
                alert('Failed to confirm checklist row: ' + error.message);
            }
        }

        function updateDashboardWithAIData(data) {
            const analysisData = data.data;
            
            // Store recommendations for explore functionality
            currentAnalysisId = analysisData.analysis_id || null;
            currentRecommendations = analysisData.recommendations || [];
            currentActionPlanChecklists = analysisData.action_plan_checklists || [];
            currentBarangayPredictions = analysisData.barangay_predictions || [];
            populateBarangaySelect(currentBarangayPredictions);
            renderActionPlanChecklistPanel(currentActionPlanChecklists);
            renderBarangayPredictionPanel(currentBarangayPredictions);
            if (currentAnalysisId && !currentActionPlanChecklists.length) {
                loadActionPlanChecklistPanel(currentAnalysisId);
            }
            const predictionContainer = document.getElementById('aiPrediction');
            const prediction = analysisData.predictions || null;

            if (predictionContainer) {
                if (!prediction || !prediction.summary) {
                    predictionContainer.innerHTML = '<div class="text-center py-8 text-gray-500">No prediction available for this dataset</div>';
                } else {
                    const predictionDrivers = (prediction.drivers || [])
                        .map(driver => `<li style="font-size: 13px; color: #475569; margin-bottom: 6px;">${escapeHtml(driver)}</li>`)
                        .join('');
                    const weatherSummary = prediction.weather_context?.summary
                        ? `<div style="margin-top: 12px; padding: 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; font-size: 13px; color: #9a3412;">
                                <strong><i class="fa-solid fa-cloud-sun" style="margin-right: 6px;"></i>Weather Context:</strong> ${escapeHtml(prediction.weather_context.summary)}
                           </div>`
                        : '';
                    const predictedFireCount = prediction.predicted_fire_incidents !== undefined && prediction.predicted_fire_incidents !== null
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.12);">
                                <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.08em;">Possible Fire Incidents</div>
                                <div style="font-size: 24px; font-weight: 800; color: #7f1d1d; margin-top: 4px;">${escapeHtml(String(prediction.predicted_fire_incidents))}</div>
                           </div>`
                        : '';
                    const predictedDamage = prediction.predicted_damage !== undefined && prediction.predicted_damage !== null
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(249,115,22,0.10); border: 1px solid rgba(249,115,22,0.16);">
                                <div style="font-size: 11px; font-weight: 700; color: #c2410c; text-transform: uppercase; letter-spacing: 0.08em;">Estimated Damage Exposure</div>
                                <div style="font-size: 20px; font-weight: 800; color: #9a3412; margin-top: 4px;">PHP ${escapeHtml(Number(prediction.predicted_damage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</div>
                           </div>`
                        : '';
                    const likelyPeakHours = (prediction.likely_peak_hours || []).length
                        ? `<div style="padding: 12px 14px; border-radius: 12px; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.18);">
                                <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em;">Likely Peak Hours</div>
                                <div style="font-size: 15px; font-weight: 700; color: #7c2d12; margin-top: 4px;">${escapeHtml(prediction.likely_peak_hours.join(', '))}</div>
                           </div>`
                        : '';
                    const scopeCaption = prediction.prediction_context?.target_period_label
                        ? `<div style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                                Prediction window: <strong>${escapeHtml(prediction.prediction_context.target_period_label)}</strong>
                           </div>`
                        : '';

                    predictionContainer.innerHTML = `
                        <div style="padding: 18px; border-radius: 16px; background: linear-gradient(135deg, rgba(220,38,38,0.08), rgba(245,158,11,0.12)); border: 1px solid rgba(245,158,11,0.25);">
                            <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em;">Forecast Window</div>
                                    <div style="font-size: 16px; font-weight: 700; color: #1f2937; margin-top: 4px;">${escapeHtml(prediction.forecast_window || 'Next 30 days')}</div>
                                </div>
                                <div style="padding: 8px 14px; border-radius: 999px; background: rgba(220,38,38,0.12); color: #b91c1c; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                                    ${escapeHtml(prediction.outlook || 'Medium')} Outlook
                                </div>
                            </div>
                            ${scopeCaption}
                            <p style="margin-top: 14px; font-size: 14px; line-height: 1.6; color: #334155;">${escapeHtml(prediction.summary)}</p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 14px;">
                                ${predictedFireCount}
                                ${predictedDamage}
                                ${likelyPeakHours}
                            </div>
                            ${predictionDrivers ? `<div style="margin-top: 14px;">
                                <div style="font-size: 12px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Primary Drivers</div>
                                <ul style="margin: 0; padding-left: 18px;">${predictionDrivers}</ul>
                            </div>` : ''}
                            ${weatherSummary}
                        </div>
                    `;
                }
            }
            
            // Risk assessment
            const riskScore = analysisData.risk_score || 0;
            const riskCard = document.getElementById('riskCard');
            riskCard.className = 'stat-card ' + (riskScore >= 70 ? 'risk-high' : (riskScore >= 40 ? 'risk-medium' : 'risk-low'));
            document.getElementById('riskScore').innerHTML = riskScore + '/100';
            document.getElementById('riskLabel').innerHTML = analysisData.risk_level || 'Not Available';
            document.getElementById('confidenceScore').innerHTML = `AI Confidence: ${analysisData.confidence_score || 85}%`;
            
            let riskFactorsHtml = '<strong>AI-Identified Risk Factors:</strong><ul style="margin-top:8px;margin-left:16px;">';
            (analysisData.risk_factors || ['No risk factors identified']).forEach(factor => {
                riskFactorsHtml += `<li style="font-size:13px;margin-bottom:4px;">• ${factor}</li>`;
            });
            riskFactorsHtml += '</ul>';
            document.getElementById('riskFactors').innerHTML = riskFactorsHtml;

            // Incident stats
            document.getElementById('totalIncidents').innerHTML = analysisData.total_incidents || 0;
            document.getElementById('fireIncidents').innerHTML = analysisData.fire_incidents || 0;
            document.getElementById('avgResponse').innerHTML = (analysisData.avg_response_time || 'N/A') + (analysisData.avg_response_time ? ' min' : '');
            document.getElementById('peakHour').innerHTML = analysisData.peak_hour ? analysisData.peak_hour + ':00' : 'N/A';
            
            // Resources
            const personnelPercent = analysisData.total_personnel > 0 ? (analysisData.available_personnel / analysisData.total_personnel) * 100 : 0;
            document.getElementById('availablePersonnel').innerHTML = `${analysisData.available_personnel || 0}/${analysisData.total_personnel || 0}`;
            document.getElementById('personnelBar').style.width = personnelPercent + '%';
            
            const equipPercent = analysisData.total_equipment > 0 ? (analysisData.available_equipment / analysisData.total_equipment) * 100 : 0;
            document.getElementById('availableEquipment').innerHTML = `${analysisData.available_equipment || 0}/${analysisData.total_equipment || 0}`;
            document.getElementById('equipmentBar').style.width = equipPercent + '%';

            // Charts
            if (hourlyChart) hourlyChart.destroy();
            if (dailyChart) dailyChart.destroy();

            const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
            hourlyChart = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: analysisData.hourly_labels || Array.from({length: 24}, (_, i) => i + ':00'),
                    datasets: [{ label: 'Incidents by Hour', data: analysisData.hourly_data || new Array(24).fill(0), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.1)', fill: true, tension: 0.4 }]
                },
                options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
            });

            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{ label: 'Incidents', data: analysisData.daily_data || [0,0,0,0,0,0,0], backgroundColor: '#ef4444', borderRadius: 6 }]
                },
                options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
            });

            // Recommendations with Explore button
            const recommendations = analysisData.recommendations || [];
            const recContainer = document.getElementById('recommendations');
            
            if (recommendations.length === 0) {
                recContainer.innerHTML = '<div class="text-center py-8 text-gray-500">No recommendations available</div>';
            } else {
                let recHtml = '<div>';
                recommendations.forEach((rec, idx) => {
                    let priorityClass = 'medium';
                    let priorityLabel = 'MEDIUM';
                    if (rec.priority === 'Critical') { priorityClass = 'critical'; priorityLabel = 'CRITICAL'; }
                    else if (rec.priority === 'High') { priorityClass = 'high'; priorityLabel = 'HIGH'; }
                    else if (rec.priority === 'Low') { priorityClass = 'low'; priorityLabel = 'LOW'; }
                    
                    recHtml += `
                        <div class="recommendation-card ${priorityClass}">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 28px; height: 28px; background: ${priorityClass === 'critical' ? '#dc2626' : (priorityClass === 'high' ? '#f59e0b' : (priorityClass === 'medium' ? '#10b981' : '#94a3b8'))}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">${idx+1}</div>
                                <div style="flex:1;">
                                    <strong>${escapeHtml(rec.title || 'Recommendation')}</strong>
                                    <p style="color: #4b5563; font-size: 13px; margin-top: 4px;">${escapeHtml(rec.description)}</p>
                                    <span class="priority-badge priority-${priorityClass}">${priorityLabel} PRIORITY</span>
                                    <button class="explore-btn" onclick="exploreRecommendation(${JSON.stringify(rec).replace(/"/g, '&quot;')}, ${idx})">
                                        <i class="fa-solid fa-chart-simple"></i> Explore & Get Detailed Suggestions
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                recHtml += '</div>';
                recContainer.innerHTML = recHtml;
            }
            
            if (analysisData.ai_explanation) {
                document.getElementById('aiExplanation').style.display = 'block';
                document.getElementById('aiExplanation').innerHTML = `<strong><i class="fa-solid fa-brain"></i> AI Analysis Summary:</strong><br>${escapeHtml(analysisData.ai_explanation)}`;
            }
        }

        function emergencyCall() {
            if (confirm('Call emergency services (911)?')) window.location.href = 'tel:911';
        }

        document.getElementById('analyzeBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'current_month');
            else alert('Please select a location');
        });

        document.getElementById('predictNextMonthBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'next_month');
            else alert('Please select a location');
        });

        document.getElementById('stationSelect')?.addEventListener('change', (e) => {
            currentBarangaySelection = 'all';
            populateBarangaySelectFromStation(e.target.value);
            updateRecommendationScopeLabel();
            if (e.target.value) runAIAnalysis(e.target.value, 'current_month');
        });

        document.getElementById('periodSelect')?.addEventListener('change', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'current_month');
        });

        document.getElementById('barangaySelect')?.addEventListener('change', (e) => {
            currentBarangaySelection = e.target.value || 'all';
            updateRecommendationScopeLabel();
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect?.value) {
                runAIAnalysis(stationSelect.value, currentPredictionScope);
            }
        });

        loadStations();
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html> --}}
