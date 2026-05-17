<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <title>FireWatch 24/7 · Predictive Analytics</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
         body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
        }
        .modal {
            display:none;
            position:fixed;
            top:0; left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,.5);
            z-index: 1000;
        }

        .modal-content{
            background:white;
            padding:20px;
            width:400px;
            margin:100px auto;
            border-radius: 12px;
        }

        .emergency-bg {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            position: relative;
            overflow: hidden;
        }
        .emergency-bg::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
            pointer-events: none;
        }
         .sidebar {
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

        .sidebar-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
        }

        .sidebar-header-content {
            position: relative;
            z-index: 1;
        }
         .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            font-size: 24px;
            color: white;
        }

        .logo-text h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .logo-text p {
            font-size: 12px;
            opacity: 0.8;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
        }

        .user-details h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .user-details span {
            font-size: 12px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .user-details span i {
            font-size: 8px;
            color: #4ade80;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 20px 16px;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-section-title {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 12px;
            margin-bottom: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: #f3f4f6;
        }

        .nav-item.active {
            background: #fee2e2;
            color: #dc2626;
            font-weight: 500;
        }

        .nav-item i {
            width: 20px;
            font-size: 16px;
        }

        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .nav-badge.secondary {
            background: #f59e0b;
        }

        /* Main Content - Fixed margin for sidebar */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-width: 0;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .fire-icon {
            animation: flicker 3s infinite;
        }
        @keyframes flicker {
            0%, 100% { opacity: 1; }
            33% { opacity: 0.8; transform: scale(1.02); }
            66% { opacity: 0.9; }
        }
        /* Sidebar transition */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Mobile menu open state */
        body.menu-open {
            overflow: hidden;
        }
        
        /* Analytics specific styles */
        .prediction-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: transform 0.3s;
        }
        .prediction-card:hover {
            transform: translateY(-5px);
        }
        
        .recommendation-item {
            border-left: 3px solid #dc2626;
            transition: background 0.3s;
        }
        .recommendation-item:hover {
            background: #fef2f2;
        }
        
        .risk-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .risk-low { background: #10b981; }
        .risk-medium { background: #f59e0b; }
        .risk-high { background: #ef4444; }
        .risk-critical { background: #7f1d1d; }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #dc2626;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .analysis-tab {
            transition: all 0.3s;
        }
        .analysis-tab.active {
            background: #dc2626;
            color: white;
        }
        
        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/map.js', 'resources/css/admin/dashboard.css'])

</head>
<body class="bg-gray-50 min-h-screen flex flex-col lg:flex-row">

    <!-- SIDE NAVIGATION (same as dashboard) -->
    {{-- <aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-full w-72 bg-white shadow-xl lg:shadow-md z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex flex-col h-full">
            <div class="emergency-bg text-white p-5 relative">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center fire-icon">
                        <i class="fa-solid fa-fire-extinguisher text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">FireWatch 24/7</h2>
                        <p class="text-white/80 text-xs">command center</p>
                    </div>
                </div>
                <button id="closeSidebar" class="absolute top-5 right-4 lg:hidden text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
                <div class="relative z-10 mt-4 flex items-center gap-2 text-xs">
                    <span class="flex items-center gap-1 bg-white/20 px-2 py-1 rounded-full"><i class="fa-solid fa-circle text-green-300 text-[8px]"></i> online</span>
                    <span class="bg-yellow-500/30 px-2 py-1 rounded-full">active 24/7</span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-4 no-scrollbar">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-gauge-high w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.maps')}}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-map-location-dot w-5"></i>
                            <span>Incident map</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-truck-fire w-5"></i>
                            <span>Active units</span>
                            <span class="ml-auto bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">6</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-bell w-5"></i>
                            <span>Alerts</span>
                            <span class="ml-auto bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded-full">3</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.analytics') }}" class="flex items-center gap-3 px-4 py-3 text-red-600 bg-red-50 rounded-xl font-medium">
                            <i class="fa-solid fa-chart-line w-5"></i>
                            <span>Predictive Analytics</span>
                            <span class="ml-auto bg-red-600 text-white text-xs px-2 py-1 rounded-full">AI</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-clock-rotate-left w-5"></i>
                            <span>History</span>
                        </a>
                    </li>
                    <li class="pt-4 mt-2 border-t border-gray-200">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">resources</p>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-helmet-safety w-5"></i>
                            <span>Personnel</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-truck w-5"></i>
                            <span>Fleet</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-water w-5"></i>
                            <span>Hydrants</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-red-50 rounded-xl transition group">
                        <i class="fa-solid fa-arrow-right-from-bracket w-5 text-gray-400 group-hover:text-red-600"></i>
                        <span class="flex-1 text-left">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside> --}}
 <!-- SIDE NAVIGATION (unchanged - keep all your existing sidebar code exactly as is) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
                <div class="logo-area">
                    <div class="logo-icon">
                        <i class="fa-solid fa-fire-extinguisher"></i>
                    </div>
                    <div class="logo-text">
                        <h2>PyroSense</h2>
                        <p>command center</p>
                    </div>
                </div>

                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->name ?? 'JD', 0, 2) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->name ?? 'John Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role ?? 'Chief Administrator' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item active">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="nav-item">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                    @if(App\Models\Post::whereDate('created_at', today())->count() > 0)
                        <span class="nav-badge">New</span>
                    @endif
                </a>
                <a href="{{ route('admin.maps') }}" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Incident Map</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-truck-fire"></i>
                    <span>Active Units</span>
                    <span class="nav-badge secondary">6</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <a href="{{ route('admin.analytics') }}" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Predictive Analytics</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-bell"></i>
                    <span>Alerts</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Resources</div>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-helmet-safety"></i>
                    <span>Personnel</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-truck"></i>
                    <span>Fleet</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-water"></i>
                    <span>Hydrants</span>
                </a>
            </div>
        </nav>

        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid #e5e7eb; background: white;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width: 100%; padding: 12px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: #4b5563; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- BACKDROP for mobile -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 min-w-0 bg-gray-50">

        <!-- TOP NAVIGATION -->
        <nav class="bg-white shadow-sm sticky top-0 z-30">
            <div class="px-4 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" class="lg:hidden text-gray-700 focus:outline-none">
                            <i class="fa-solid fa-bars text-2xl"></i>
                        </button>
                        <div class="flex items-center space-x-2 lg:hidden">
                            <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center fire-icon">
                                <i class="fa-solid fa-fire-extinguisher text-white text-sm"></i>
                            </div>
                            <span class="text-lg font-bold text-gray-800">Fire<span class="text-red-600">Watch</span></span>
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center space-x-8">
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-red-600 font-medium">Dashboard</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Emergency</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Services</a>
                        <a href="{{ route('admin.analytics') }}" class="text-red-600 font-medium">Analytics</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Resources</a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="hidden sm:inline text-sm text-gray-600">Station 7</span>
                        <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-phone-alt"></i>
                            <span class="hidden sm:inline">Emergency</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ANALYTICS CONTENT -->
        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            <!-- page title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">🔮 Predictive Analytics</h1>
                    <p class="text-sm text-gray-500 mt-1">AI-powered fire risk analysis and recommendations</p>
                </div>
                <div class="flex items-center gap-3 mt-3 sm:mt-0">
                    <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1.5 rounded-full flex items-center gap-1">
                        <i class="fa-solid fa-robot"></i> DeepSeek AI
                    </span>
                    <p class="text-sm text-gray-500"><i class="fa-regular fa-calendar mr-1"></i> {{ date('d M Y · H:i') }} hrs</p>
                </div>
            </div>

            <!-- AI Status Banner -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg flex items-start gap-3">
                <i class="fa-solid fa-microchip text-blue-500 mt-1"></i>
                <div class="text-sm text-blue-700 flex-1">
                    <span class="font-bold">AI Predictive Engine Active:</span> Analyzing historical patterns, weather data, and risk factors. Last updated 2 minutes ago.
                </div>
                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium" onclick="refreshAnalysis()">
                    <i class="fa-solid fa-rotate mr-1"></i> Refresh
                </button>
            </div>

            <!-- Analysis Type Tabs -->
            <div class="bg-white rounded-xl shadow-sm p-2 mb-6 inline-flex flex-wrap gap-2">
                <button class="analysis-tab active px-4 py-2 rounded-lg text-sm font-medium transition" onclick="switchAnalysisTab('fire-risk')">
                    <i class="fa-solid fa-fire mr-1"></i> Fire Risk
                </button>
                <button class="analysis-tab px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition" onclick="switchAnalysisTab('resource')">
                    <i class="fa-solid fa-truck-fire mr-1"></i> Resource Optimization
                </button>
                <button class="analysis-tab px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition" onclick="switchAnalysisTab('trend')">
                    <i class="fa-solid fa-chart-line mr-1"></i> Trend Analysis
                </button>
                <button class="analysis-tab px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition" onclick="switchAnalysisTab('prevention')">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Prevention
                </button>
            </div>

            <!-- Loading State (hidden by default) -->
            <div id="loadingState" class="hidden fixed inset-0 bg-white/80 z-50 flex items-center justify-center">
                <div class="text-center">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-gray-600">AI is analyzing data...</p>
                </div>
            </div>

            <!-- Main Analytics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Input/Prediction Cards -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Prediction Input Card -->
                    <div class="bg-white rounded-xl shadow p-5">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-red-600"></i>
                            Analysis Parameters
                        </h3>
                        
                        <form id="analysisForm" onsubmit="runAnalysis(event)">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Location / Sector</label>
            <select name="sector" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <option value="All Sectors">All Sectors (Entire City)</option>
                @if(isset($sectors) && $sectors->count() > 0)
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->title }}" 
                                data-marker-id="{{ $sector->marker_id }}"
                                data-category="{{ $sector->marker_category }}">
                            {{ $sector->title }} 
                            @if($sector->marker_category)
                                <span class="text-gray-500">({{ $sector->marker_category }})</span>
                            @endif
                        </option>
                    @endforeach
                @else
                    <option value="Sector 1">Sector 1 - Downtown</option>
                    <option value="Sector 2">Sector 2 - Industrial</option>
                    <option value="Sector 3">Sector 3 - Residential North</option>
                    <option value="Sector 4" selected>Sector 4 - Commercial District</option>
                    <option value="Sector 5">Sector 5 - Hillside</option>
                @endif
            </select>
            <p class="text-xs text-gray-500 mt-1">Select a specific area or view all sectors</p>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
            <select name="period" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <option value="Last 24 hours">Last 24 hours</option>
                <option value="Last 7 days" selected>Last 7 days</option>
                <option value="Last 30 days">Last 30 days</option>
                <option value="Last 3 months">Last 3 months</option>
                <option value="Last 1 year">Last 1 year</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Sources</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="data_sources[]" value="incidents" checked class="rounded text-red-600">
                    <span class="text-sm">Historical Incidents</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="data_sources[]" value="markers" checked class="rounded text-red-600">
                    <span class="text-sm">Marker Locations</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="data_sources[]" value="weather" class="rounded text-red-600">
                    <span class="text-sm">Weather Data (if available)</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="data_sources[]" value="building" class="rounded text-red-600">
                    <span class="text-sm">Building Information</span>
                </label>
            </div>
        </div>
        
        <!-- Hidden field to also pass marker_id if needed -->
        <input type="hidden" name="marker_id" id="selected_marker_id" value="">
        
        <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2 font-medium">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            Generate Prediction
        </button>
    </div>
</form>

                    </div>

                    <!-- Quick Stats Card -->
                    <div class="emergency-bg text-white rounded-xl shadow p-5 relative">
                        <div class="relative z-10">
                            <h4 class="font-semibold mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-gauge-high"></i>
                                Current Risk Overview
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Overall Risk Index</span>
                                        <span class="font-bold">74/100</span>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 74%"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-white/70">Hotspots</span>
                                        <p class="text-xl font-bold">6</p>
                                    </div>
                                    <div>
                                        <span class="text-white/70">Vulnerable</span>
                                        <p class="text-xl font-bold">12</p>
                                    </div>
                                </div>
                                <div class="bg-white/10 rounded-lg p-3">
                                    <p class="text-xs text-white/70">Primary risk factor</p>
                                    <p class="font-medium">Electrical infrastructure aging</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Predictions -->
                    <div class="bg-white rounded-xl shadow p-5">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fa-regular fa-clock"></i>
                            Recent AI Predictions
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium">Sector 3</p>
                                    <p class="text-xs text-gray-500">2 hours ago</p>
                                </div>
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Medium risk</span>
                            </div>
                            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium">Industrial Zone</p>
                                    <p class="text-xs text-gray-500">5 hours ago</p>
                                </div>
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">High risk</span>
                            </div>
                            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium">Downtown</p>
                                    <p class="text-xs text-gray-500">1 day ago</p>
                                </div>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Low risk</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Analysis Results (spans 2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Main Prediction Card -->
                    <div class="bg-white rounded-xl shadow p-6" id="predictionResult">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                                <i class="fa-solid fa-chart-simple text-red-600"></i>
                                AI Predictive Analysis
                            </h3>
                            <span class="bg-green-100 text-green-700 text-xs px-3 py-1.5 rounded-full flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                Live Analysis
                            </span>
                        </div>

                        <!-- Risk Indicators -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-xs text-gray-500">Risk Level</span>
                                <p class="text-xl font-bold text-orange-600">MEDIUM</p>
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full mt-1 inline-block">52% probability</span>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-xs text-gray-500">Confidence</span>
                                <p class="text-xl font-bold text-blue-600">87%</p>
                                <span class="text-xs text-gray-500">AI certainty</span>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-xs text-gray-500">Timeframe</span>
                                <p class="text-xl font-bold">24h</p>
                                <span class="text-xs text-gray-500">prediction window</span>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="mb-6">
                            <canvas id="riskChart" height="200"></canvas>
                        </div>

                        <!-- Key Factors -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                            <div class="p-3 bg-red-50 rounded-lg">
                                <p class="text-xs text-red-600 font-medium">Temperature</p>
                                <p class="text-lg font-bold">34°C</p>
                                <span class="text-xs text-red-600">↑ high risk</span>
                            </div>
                            <div class="p-3 bg-yellow-50 rounded-lg">
                                <p class="text-xs text-yellow-600 font-medium">Humidity</p>
                                <p class="text-lg font-bold">45%</p>
                                <span class="text-xs text-yellow-600">low</span>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <p class="text-xs text-blue-600 font-medium">Wind Speed</p>
                                <p class="text-lg font-bold">22 km/h</p>
                                <span class="text-xs text-blue-600">moderate</span>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-lg">
                                <p class="text-xs text-purple-600 font-medium">Drought Index</p>
                                <p class="text-lg font-bold">6.2</p>
                                <span class="text-xs text-purple-600">elevated</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations Card -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-lightbulb text-yellow-500"></i>
                            AI Recommendations & Actions
                        </h3>

                        <!-- Priority Actions -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-600 rounded-full"></span>
                                Immediate Priority Actions (Next 24h)
                            </h4>
                            <div class="space-y-3">
                                <div class="recommendation-item bg-red-50 p-4 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm flex-shrink-0">1</span>
                                        <div>
                                            <p class="font-medium text-gray-800">Deploy additional unit to Sector 4</p>
                                            <p class="text-sm text-gray-600 mt-1">Increased risk detected in commercial area. Dispatch Engine 42 for standby.</p>
                                            <div class="flex items-center gap-3 mt-2">
                                                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">High urgency</span>
                                                <span class="text-xs text-gray-500">Estimated response: 3min</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="recommendation-item p-4 rounded-lg border-l-3 border-yellow-500">
                                    <div class="flex items-start gap-3">
                                        <span class="w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm flex-shrink-0">2</span>
                                        <div>
                                            <p class="font-medium text-gray-800">Inspect hydrants in Industrial Zone</p>
                                            <p class="text-sm text-gray-600 mt-1">3 hydrants reported low pressure. Schedule maintenance check.</p>
                                            <div class="flex items-center gap-3 mt-2">
                                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Medium urgency</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resource Optimization -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-truck-fire text-blue-500"></i>
                                Resource Optimization
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium">Suggested redeployment</p>
                                    <p class="text-2xl font-bold text-blue-600">2 units</p>
                                    <p class="text-xs text-gray-600 mt-1">Move from Station 3 to Station 7 for better coverage</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium">Personnel efficiency</p>
                                    <p class="text-2xl font-bold text-green-600">+23%</p>
                                    <p class="text-xs text-gray-600 mt-1">Potential improvement with suggested shifts</p>
                                </div>
                            </div>
                        </div>

                        <!-- Long-term Recommendations -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-600 mb-3">Strategic Recommendations</h4>
                            <ul class="space-y-2">
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                                    <span class="text-sm">Install 3 new hydrants along Commercial Avenue by Q3</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                                    <span class="text-sm">Schedule fire safety training for 15 high-risk buildings</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                                    <span class="text-sm">Upgrade communication systems in Station 4 and 5</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Export Actions -->
                        <div class="flex gap-3 mt-6 pt-4 border-t">
                            <button class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg transition flex items-center justify-center gap-2">
                                <i class="fa-regular fa-file-pdf"></i>
                                Export Report
                            </button>
                            <button class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg transition flex items-center justify-center gap-2">
                                <i class="fa-regular fa-share-from-square"></i>
                                Share Analysis
                            </button>
                        </div>
                    </div>

                    <!-- Historical Trends -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-chart-line text-blue-600"></i>
                            Historical Trend Analysis
                        </h3>
                        <div class="h-48">
                            <canvas id="trendChart"></canvas>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mt-4 text-center">
                            <div>
                                <p class="text-xs text-gray-500">YoY Change</p>
                                <p class="text-lg font-bold text-red-600">+12%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Peak Season</p>
                                <p class="text-lg font-bold">March-May</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Avg Response</p>
                                <p class="text-lg font-bold">4.2min</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- footer -->
            <div class="text-center text-xs text-gray-400 mt-8 border-t pt-4">
                <i class="fa-solid fa-microchip mr-1"></i> AI-powered predictive analytics · DeepSeek model · Updated in real-time
            </div>
        </div>
    </main>

    <!-- Analysis Modals -->
    <div id="analysisModal" class="modal">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Custom Analysis Parameters</h3>
            <div class="space-y-3">
                <input type="text" class="border p-2 w-full rounded" placeholder="Enter custom data (JSON)">
                <select class="border p-2 w-full rounded">
                    <option>DeepSeek V3 (Free)</option>
                    <option>Mistral 7B</option>
                    <option>Gemma 2</option>
                </select>
                <button class="bg-blue-500 text-white px-4 py-2 rounded w-full">Run Custom Analysis</button>
            </div>
            <button class="bg-gray-300 px-4 py-2 rounded mt-3 w-full" onclick="closeModal()">Cancel</button>
        </div>
    </div>

   <script>
    // Add this at the beginning of your script section
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Function to run analysis with real data
   async function runAnalysis(event) {
    event.preventDefault();
    
    // Show loading state
    document.getElementById('loadingState').classList.remove('hidden');
    
    // Get form data
    const sector = document.querySelector('select[name="sector"]')?.value || 'All Sectors';
    const period = document.querySelector('select[name="period"]')?.value || 'Last 7 days';
    const markerId = document.getElementById('selected_marker_id')?.value || '';
    
    // Get selected data sources from checkboxes
    const dataSources = [];
    document.querySelectorAll('input[name="data_sources[]"]:checked').forEach(cb => {
        dataSources.push(cb.value);
    });
    
    // If none selected, use defaults
    if (dataSources.length === 0) {
        dataSources.push('incidents', 'markers');
    }
    
    console.log('Sending request with:', { sector, period, markerId, dataSources });
    
    try {
        const response = await fetch('/api/predictive-analysis', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                sector: sector,
                period: period,
                marker_id: markerId,
                data_sources: dataSources
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            updateAnalysisUI(result.analysis, result.data);
            showSuccess('Analysis completed successfully!');
        } else {
            showError('Analysis failed: ' + result.message);
        }
        
    } catch (error) {
        console.error('Analysis error:', error);
        showError('Failed to connect to AI service');
        useSampleData();
    } finally {
        document.getElementById('loadingState').classList.add('hidden');
    }
}
    
    // Use sample data as fallback
   // Use sample data as fallback
function useSampleData() {
    console.log('Using sample data as fallback');
    
    const sampleAnalysis = {
        risk_level: 'Medium',
        confidence: 85,
        recommendations: [
            'Deploy additional fire trucks to Sector 4',
            'Inspect electrical systems in commercial buildings',
            'Schedule fire safety training for high-risk areas',
            'Monitor weather conditions closely'
        ]
    };
      // Update hidden marker_id when sector changes
    document.querySelector('select[name="sector"]').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const markerId = selectedOption.getAttribute('data-marker-id');
        document.getElementById('selected_marker_id').value = markerId || '';
    });


    // Create sample incidents with timestamps
    const now = new Date();
    const sampleIncidents = [];
    
    // Generate sample incidents across different hours
    for (let i = 0; i < 24; i++) {
        const date = new Date(now);
        date.setHours(i, 0, 0, 0);
        sampleIncidents.push({ created_at: date.toISOString() });
    }
    
    const sampleData = {
        incidents: sampleIncidents,
        incident_stats: {
            total: 24,
            avg_response: 4.2
        }
    };
    
    updateAnalysisUI(sampleAnalysis, sampleData);
}
    
    // Update UI with real AI analysis
    function updateAnalysisUI(analysis, data) {
        console.log('Updating UI with:', analysis, data);
        
        // Update risk level
        const riskLevel = analysis.risk_level || 'Medium';
        const riskElement = document.querySelector('.text-xl.font-bold.text-orange-600');
        if (riskElement) {
            riskElement.textContent = riskLevel;
            
            // Update color based on risk
            riskElement.className = 'text-xl font-bold';
            if (riskLevel.toLowerCase().includes('low')) {
                riskElement.classList.add('text-green-600');
            } else if (riskLevel.toLowerCase().includes('medium')) {
                riskElement.classList.add('text-orange-600');
            } else if (riskLevel.toLowerCase().includes('high')) {
                riskElement.classList.add('text-red-600');
            } else if (riskLevel.toLowerCase().includes('critical')) {
                riskElement.classList.add('text-red-800');
            }
        }
        
        // Update confidence
        const confidence = analysis.confidence || 87;
        const confidenceElement = document.querySelector('.text-xl.font-bold.text-blue-600');
        if (confidenceElement) {
            confidenceElement.textContent = confidence + '%';
        }
        
        // Update incident stats
        if (data && data.incident_stats) {
            const statsElements = document.querySelectorAll('.grid-cols-2 .text-xl.font-bold');
            if (statsElements.length >= 2) {
                statsElements[0].textContent = data.incident_stats.total || 0;
                statsElements[1].textContent = data.incident_stats.avg_response ? 
                    data.incident_stats.avg_response.toFixed(1) + 'min' : 'N/A';
            }
        }
        
        // Update recommendations
        if (analysis.recommendations && analysis.recommendations.length > 0) {
            const recommendationsContainer = document.querySelector('.space-y-3:has(.recommendation-item)');
            if (recommendationsContainer) {
                recommendationsContainer.innerHTML = '';
                
                analysis.recommendations.slice(0, 3).forEach((rec, index) => {
                    const urgency = index === 0 ? 'High' : (index === 1 ? 'Medium' : 'Low');
                    const bgColor = index === 0 ? 'bg-red-50' : (index === 1 ? 'bg-yellow-50' : 'bg-blue-50');
                    const badgeColor = index === 0 ? 'bg-red-100 text-red-700' : 
                                      (index === 1 ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700');
                    
                    const recHtml = `
                        <div class="recommendation-item ${bgColor} p-4 rounded-lg">
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 ${index === 0 ? 'bg-red-600' : (index === 1 ? 'bg-yellow-500' : 'bg-blue-500')} text-white rounded-full flex items-center justify-center text-sm flex-shrink-0">${index + 1}</span>
                                <div>
                                    <p class="font-medium text-gray-800">${rec}</p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="text-xs ${badgeColor} px-2 py-1 rounded-full">${urgency} urgency</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    recommendationsContainer.innerHTML += recHtml;
                });
            }
        }
        
        // Update charts with real data
        updateChartsWithRealData(data);
    }
    
    // Update charts with real data
  // Update charts with real data
  // Update charts with real data
function updateChartsWithRealData(data) {
    console.log('Updating charts with data:', data);
    
    if (!data) {
        console.log('No data to update charts');
        return;
    }
    
    // Update risk chart (hourly distribution)
    if (window.riskChart) {
        try {
            let hourData = new Array(24).fill(0);
            
            // Check if we have incidents array
            if (data.incidents && data.incidents.length > 0) {
                console.log(`Processing ${data.incidents.length} incidents for hourly chart`);
                
                // Count incidents by hour
                data.incidents.forEach(incident => {
                    if (incident.created_at) {
                        const date = new Date(incident.created_at);
                        const hour = date.getHours();
                        hourData[hour] = (hourData[hour] || 0) + 1;
                    }
                });
            } 
            // Fallback to incident_stats.hourly_distribution if available
            else if (data.incident_stats && data.incident_stats.hourly_distribution) {
                console.log('Using hourly_distribution from stats');
                hourData = data.incident_stats.hourly_distribution;
            }
            // Generate sample data for demonstration
            else {
                console.log('No incident data, generating sample for chart');
                for (let i = 0; i < 24; i++) {
                    // Create a pattern with peaks at 14, 18, 20
                    if (i === 14 || i === 18 || i === 20) {
                        hourData[i] = Math.floor(Math.random() * 3) + 3;
                    } else {
                        hourData[i] = Math.floor(Math.random() * 2) + 1;
                    }
                }
            }
            
            console.log('Hourly incident counts:', hourData);
            
            // Update chart
            if (window.riskChart.data && window.riskChart.data.datasets) {
                window.riskChart.data.datasets[0].data = hourData;
                window.riskChart.data.datasets[0].label = 'Incidents by Hour';
                
                // Force chart to redraw with animation
                window.riskChart.update({
                    duration: 800,
                    easing: 'easeInOutQuad'
                });
                
                console.log('Risk chart updated successfully');
            }
        } catch (e) {
            console.error('Error updating risk chart:', e);
        }
    } else {
        console.log('Risk chart not initialized');
    }
    
    // Update trend chart (daily distribution)
    if (window.trendChart) {
        try {
            let dayData = new Array(7).fill(0);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            
            // Check if we have incidents array
            if (data.incidents && data.incidents.length > 0) {
                console.log(`Processing ${data.incidents.length} incidents for daily chart`);
                
                // Count incidents by day of week
                data.incidents.forEach(incident => {
                    if (incident.created_at) {
                        const date = new Date(incident.created_at);
                        const day = date.getDay(); // 0 = Sunday, 6 = Saturday
                        dayData[day] = (dayData[day] || 0) + 1;
                    }
                });
            }
            // Fallback to incident_stats.daily_distribution if available
            else if (data.incident_stats && data.incident_stats.daily_distribution) {
                console.log('Using daily_distribution from stats');
                dayData = data.incident_stats.daily_distribution;
            }
            // Generate sample data
            else {
                console.log('No incident data, generating sample for trend chart');
                // Create pattern with weekends higher
                dayData = [5, 4, 3, 4, 6, 8, 7]; // Sun-Sat
            }
            
            console.log('Daily incident counts:', dayData);
            
            // Update chart
            if (window.trendChart.data && window.trendChart.data.datasets) {
                window.trendChart.data.datasets[0].data = dayData;
                window.trendChart.data.datasets[0].label = 'Current Period';
                
                // Generate previous period data (slightly lower)
                const prevData = dayData.map(val => Math.max(1, Math.floor(val * 0.7)));
                window.trendChart.data.datasets[1].data = prevData;
                
                window.trendChart.update({
                    duration: 800,
                    easing: 'easeInOutQuad'
                });
                
                console.log('Trend chart updated successfully');
            }
        } catch (e) {
            console.error('Error updating trend chart:', e);
        }
    } else {
        console.log('Trend chart not initialized');
    }
}
// function updateChartsWithRealData(data) {
//     console.log('Updating charts with data:', data);
    
//     if (!data) {
//         console.log('No data to update charts');
//         return;
//     }
    
//     // Update risk chart (hourly distribution)
//     if (window.riskChart && data.incidents && data.incidents.length > 0) {
//         try {
//             // Create 24-hour array
//             const hourData = new Array(24).fill(0);
            
//             // Count incidents by hour
//             data.incidents.forEach(incident => {
//                 if (incident.created_at) {
//                     const date = new Date(incident.created_at);
//                     const hour = date.getHours();
//                     hourData[hour] = (hourData[hour] || 0) + 1;
//                 }
//             });
            
//             // Update chart - check if properties exist
//             if (window.riskChart.data && window.riskChart.data.datasets) {
//                 window.riskChart.data.datasets[0].data = hourData;
//                 window.riskChart.data.datasets[0].label = 'Incidents by Hour';
//                 window.riskChart.update();
//                 console.log('Risk chart updated with hourly data');
//             }
//         } catch (e) {
//             console.error('Error updating risk chart:', e);
//         }
//     } else {
//         console.log('Risk chart not ready or no incident data');
//     }
    
//     // Update trend chart (daily distribution)
//     if (window.trendChart && data.incidents && data.incidents.length > 0) {
//         try {
//             const dayData = new Array(7).fill(0);
            
//             // Count incidents by day of week
//             data.incidents.forEach(incident => {
//                 if (incident.created_at) {
//                     const date = new Date(incident.created_at);
//                     const day = date.getDay(); // 0 = Sunday, 6 = Saturday
//                     dayData[day] = (dayData[day] || 0) + 1;
//                 }
//             });
            
//             // Update chart - check if properties exist
//             if (window.trendChart.data && window.trendChart.data.datasets) {
//                 window.trendChart.data.datasets[0].data = dayData;
//                 window.trendChart.data.datasets[0].label = 'Current Period';
                
//                 // For previous period, use shifted data or zeros
//                 const prevData = dayData.map(val => Math.max(0, Math.floor(val * 0.8)));
//                 window.trendChart.data.datasets[1].data = prevData;
//                 window.trendChart.data.datasets[1].label = 'Previous Period';
                
//                 window.trendChart.update();
//                 console.log('Trend chart updated with daily data');
//             }
//         } catch (e) {
//             console.error('Error updating trend chart:', e);
//         }
//     } else {
//         console.log('Trend chart not ready or no incident data');
//     }
// }
    
    // Initialize charts
    // Initialize charts
function initCharts() {
    console.log('Initializing charts...');
    
    // Risk Chart (Line chart)
    const ctx1 = document.getElementById('riskChart')?.getContext('2d');
    if (ctx1 && typeof Chart !== 'undefined') {
        // Check if chart exists and destroy properly
        if (window.riskChart && typeof window.riskChart.destroy === 'function') {
            window.riskChart.destroy();
        }
        
        try {
            window.riskChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0') + ':00'),
                    datasets: [{
                        label: 'Incidents by Hour',
                        data: new Array(24).fill(0),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f3f4f6',
                            bodyColor: '#d1d5db'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Number of Incidents'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
            console.log('Risk chart initialized successfully');
        } catch (e) {
            console.error('Error creating risk chart:', e);
        }
    } else {
        console.error('Risk chart canvas not found or Chart not loaded');
    }

    // Trend Chart (Bar chart)
    const ctx2 = document.getElementById('trendChart')?.getContext('2d');
    if (ctx2 && typeof Chart !== 'undefined') {
        // Check if chart exists and destroy properly
        if (window.trendChart && typeof window.trendChart.destroy === 'function') {
            window.trendChart.destroy();
        }
        
        try {
            window.trendChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    datasets: [
                        {
                            label: 'Current Period',
                            data: [0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: '#dc2626',
                            borderRadius: 6,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Previous Period',
                            data: [0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: '#94a3b8',
                            borderRadius: 6,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f3f4f6',
                            bodyColor: '#d1d5db'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Number of Incidents'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            console.log('Trend chart initialized successfully');
        } catch (e) {
            console.error('Error creating trend chart:', e);
        }
    } else {
        console.error('Trend chart canvas not found or Chart not loaded');
    }
}
    
    // Show error message
    function showError(message) {
        const banner = document.createElement('div');
        banner.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded flex items-center justify-between';
        banner.innerHTML = `
            <div class="flex items-center">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-700">
                <i class="fa-solid fa-times"></i>
            </button>
        `;
        
        document.querySelector('.max-w-7xl').prepend(banner);
        setTimeout(() => banner.remove(), 5000);
    }
    
    // Show success message
    function showSuccess(message) {
        const banner = document.createElement('div');
        banner.className = 'bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded flex items-center justify-between';
        banner.innerHTML = `
            <div class="flex items-center">
                <i class="fa-solid fa-check-circle mr-2"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-700">
                <i class="fa-solid fa-times"></i>
            </button>
        `;
        
        document.querySelector('.max-w-7xl').prepend(banner);
        setTimeout(() => banner.remove(), 3000);
    }
    
    // Refresh analysis
    async function refreshAnalysis() {
        document.getElementById('loadingState').classList.remove('hidden');
        
        try {
            const fakeEvent = { preventDefault: () => {} };
            await runAnalysis(fakeEvent);
            showSuccess('Analysis refreshed!');
        } catch (error) {
            console.error('Refresh error:', error);
            showError('Failed to refresh analysis');
        } finally {
            document.getElementById('loadingState').classList.add('hidden');
        }
    }
    
    // Load historical analyses
    async function loadHistoricalAnalyses() {
        try {
            const response = await fetch('/api/historical-analyses', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Historical analyses:', result);
            
            if (result.success && result.data.length > 0) {
                const historyContainer = document.querySelector('.space-y-3:has(.flex.items-center.justify-between)');
                if (historyContainer) {
                    historyContainer.innerHTML = '';
                    
                    result.data.slice(0, 5).forEach(analysis => {
                        const riskClass = analysis.risk_level.toLowerCase().includes('low') ? 'green' :
                                         analysis.risk_level.toLowerCase().includes('medium') ? 'yellow' :
                                         analysis.risk_level.toLowerCase().includes('high') ? 'red' : 'red';
                        
                        const date = new Date(analysis.created_at).toLocaleString();
                        
                        const historyHtml = `
                            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg cursor-pointer" onclick="loadAnalysis('${analysis.id}')">
                                <div>
                                    <p class="text-sm font-medium">${analysis.sector}</p>
                                    <p class="text-xs text-gray-500">${date}</p>
                                </div>
                                <span class="text-xs bg-${riskClass}-100 text-${riskClass}-700 px-2 py-1 rounded-full">${analysis.risk_level}</span>
                            </div>
                        `;
                        
                        historyContainer.innerHTML += historyHtml;
                    });
                }
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }
    
    // Load specific analysis
    function loadAnalysis(id) {
        console.log('Loading analysis:', id);
        showSuccess('Loading historical analysis...');
    }
    
    // Tab switching
    function switchAnalysisTab(tab) {
        document.querySelectorAll('.analysis-tab').forEach(btn => {
            btn.classList.remove('active', 'bg-red-600', 'text-white');
            btn.classList.add('text-gray-600');
        });
        
        event.target.classList.add('active', 'bg-red-600', 'text-white');
        
        // Run analysis for the selected tab
        const fakeEvent = { preventDefault: () => {} };
        runAnalysis(fakeEvent);
    }
    
    // Modal functions
    function openModal() {
        const modal = document.getElementById('analysisModal');
        if (modal) modal.style.display = 'block';
    }

    function closeModal() {
        const modal = document.getElementById('analysisModal');
        if (modal) modal.style.display = 'none';
    }
    
    // Load historical analyses and initialize charts on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Analytics page loaded');
        
        // Initialize charts
        setTimeout(() => {
            initCharts();
        }, 500);
        
        // Load historical analyses
        loadHistoricalAnalyses();
        
        // Run initial analysis
        setTimeout(() => {
            const fakeEvent = { preventDefault: () => {} };
            runAnalysis(fakeEvent);
        }, 1000);
    });
</script>
</body>
</html>