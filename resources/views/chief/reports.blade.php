<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense · Investigation Reports</title>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])
    @vite(['resources/js/admin/reports.js'])

    <style>
        /* Reports Page Specific Styles */
        .reports-container {
            padding: 24px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Report Generation Card */
        .report-generator-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 32px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #2c3e50;
        }

        .filter-grid {
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            background: #f8fafc;
            border-bottom: 1px solid #eef2f6;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-group label i {
            font-size: 0.75rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            background: white;
            transition: all 0.2s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .date-range {
            display: flex;
            gap: 12px;
        }

        .date-range input {
            flex: 1;
        }

        .generate-actions {
            padding: 20px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: white;
            border-top: 1px solid #eef2f6;
        }

        .btn-generate {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-generate:hover {
            background: #1e2a36;
            transform: translateY(-1px);
        }

        .btn-export {
            background: white;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-export:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        /* Report Preview Section */
        .report-preview {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 32px;
            overflow: hidden;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .preview-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .preview-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-stats {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .stat-badge {
            font-size: 0.8rem;
            color: #64748b;
            background: white;
            padding: 4px 12px;
            border-radius: 40px;
            border: 1px solid #e2e8f0;
        }

        .stat-badge strong {
            color: #2c3e50;
            margin-right: 4px;
        }

        .preview-content {
            padding: 24px;
            max-height: 500px;
            overflow-y: auto;
        }

        /* Report Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .report-table th {
            text-align: left;
            padding: 12px 12px;
            background: #f1f5f9;
            font-weight: 600;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
        }

        .report-table td {
            padding: 12px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        .report-table tr:hover td {
            background: #faf5ff;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.active, .status-badge.operational {
            background: #10b981;
            color: white;
        }

        .status-badge.inactive, .status-badge.maintenance {
            background: #ef4444;
            color: white;
        }

        .status-badge.on_leave {
            background: #f59e0b;
            color: white;
        }

        .status-badge.High {
            background: #dc2626;
            color: white;
        }

        .status-badge.Medium {
            background: #f59e0b;
            color: white;
        }

        .status-badge.Low {
            background: #10b981;
            color: white;
        }

        .empty-preview {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-preview i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Report History Section */
        .history-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .history-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .history-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-search {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .history-search input {
            padding: 8px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            font-size: 0.85rem;
            width: 250px;
        }

        .history-list {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
        }

        .history-item:hover {
            background: #faf5ff;
        }

        .history-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .history-name {
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-name i {
            color: #2c3e50;
            font-size: 1rem;
        }

        .history-meta {
            display: flex;
            gap: 20px;
            font-size: 0.75rem;
            color: #64748b;
        }

        .history-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .history-actions {
            display: flex;
            gap: 12px;
        }

        .history-actions button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
            color: #64748b;
        }

        .history-actions button:hover {
            background: #f1f5f9;
            color: #2c3e50;
        }

        .history-actions .delete-report:hover {
            color: #dc2626;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top-color: #2c3e50;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: #10b981;
        }

        .toast.error {
            background: #ef4444;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #2c3e50;
        }

        .page-header .date {
            font-size: 0.85rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 40px;
        }

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
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
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
            transition: all 0.2s ease;
        }

        .user-dropdown-item:hover {
            background: #fff0e8;
            color: #c63d26;
        }

        /* Alert Banner */
        .alert-banner {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 14px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .alert-banner i {
            color: #10b981;
            font-size: 1.25rem;
        }

        .alert-banner .content {
            color: #166534;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reports-container {
                padding: 16px;
            }

            .lector-top-bar {
                margin: 12px 12px 0;
                padding: 0 18px;
                height: 64px;
                border-radius: 20px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .date-range {
                flex-direction: column;
            }
            
            .history-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .history-actions {
                align-self: flex-end;
            }

            .preview-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .preview-stats {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
   @include('chief.partials.sidebar', ['active' => 'reports'])
   <aside class="sidebar legacy-chief-sidebar" style="display:none;">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
               <div class="logo-area">
                    <div class="logo-icon">
                        @include('partials.app-logo')
                    </div>
                    <div class="logo-text">
                        <h2>PyroSense</h2>
                        <p>command center</p>
                    </div>
                </div>
                <button id="closeSidebar"><i class="fa-solid fa-xmark"></i></button>
                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Chief Administrator' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 2 }}</span>
                        </a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('station-admin.management') }}" class="nav-item"><i class="fa-solid fa-map-location-dot"></i><span>Management</span></a>
                <a href="{{ route('station-admin.reports') }}" class="nav-item"><i class="fa-solid fa-chart-pie"></i><span>Reports</span></a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
                
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item"><i class="fa-solid fa-chart-line"></i><span>Predictive Analytics</span></a>
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside>

    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Executive Reports</h1>
                    <p class="text-xs text-slate-500 font-medium">Station investigation reporting and exports • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
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

        <div class="reports-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-clipboard-list"></i>
                    Investigation Reports
                </h1>
                <div class="date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- Alert Banner -->
            <div class="alert-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div class="content">
                    <strong>Investigation Suite:</strong> Generate Spot Investigation Reports, Progress Reports, and Final Investigation Reports.
                </div>
            </div>

            <!-- Report Generator Card -->
            <div class="report-generator-card">
                <div class="card-header">
                    <h2>
                        <i class="fa-solid fa-file-waveform"></i>
                        Generate Investigation Report
                    </h2>
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fa-regular fa-file-lines"></i> Report Type</label>
                        <select id="reportType">
                            <option value="spot">Spot Investigation Report</option>
                            <option value="progress">Progress Investigation Report</option>
                            <option value="final">Final Investigation Report</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fa-solid fa-building"></i> Station</label>
                        <select id="stationFilter">
                            <option value="">All Stations</option>
                            @foreach ($stations ?? [] as $station)
                                <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fa-solid fa-flag"></i> Occupancy / Status</label>
                        <select id="statusFilter">
                            <option value="">All</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                            <option value="filed">Filed</option>
                            <option value="progressed">Progressed</option>
                            <option value="conducting">Conducting</option>
                            <option value="finalized">Finalized</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fa-solid fa-arrow-down-wide-short"></i> Sort By</label>
                        <select id="sortFilter">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="incident">Incident Title</option>
                            <option value="investigator">Investigator</option>
                            <option value="damage-high">Highest Damage</option>
                            <option value="damage-low">Lowest Damage</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fa-regular fa-calendar"></i> Date Range</label>
                        <div class="date-range">
                            <input type="date" id="startDate" placeholder="Start Date">
                            <input type="date" id="endDate" placeholder="End Date">
                        </div>
                    </div>
                </div>
                
                <div class="generate-actions">
                    <button class="btn-export" id="resetFiltersBtn">
                        <i class="fa-solid fa-undo"></i>
                        Reset Filters
                    </button>
                    <button class="btn-generate" id="generateReportBtn">
                        <i class="fa-solid fa-chart-simple"></i>
                        Generate Report
                    </button>
                </div>
            </div>

            <!-- Report Preview Section -->
            <div class="report-preview" id="reportPreview">
                <div class="preview-header">
                    <h3>
                        <i class="fa-solid fa-eye"></i>
                        Report Preview
                        <span id="reportTitle" style="font-weight: normal; font-size: 0.85rem; color: #64748b;"></span>
                    </h3>
                    <div class="preview-stats">
                        <div class="stat-badge">
                            <strong id="recordCount">0</strong> records
                        </div>
                        <button class="btn-export" id="exportPdfBtn" style="padding: 6px 16px; font-size: 0.8rem;">
                            <i class="fa-regular fa-file-pdf"></i>
                            Export as PDF
                        </button>
                        <button class="btn-export" id="exportReportBtn" style="padding: 6px 16px; font-size: 0.8rem;">
                            <i class="fa-solid fa-download"></i>
                            Export as CSV
                        </button>
                    </div>
                </div>
                <div class="preview-content" id="previewContent">
                    <div class="empty-preview">
                        <i class="fa-regular fa-chart-scatter"></i>
                        <p>Select filters and click "Generate Report" to view investigation data</p>
                    </div>
                </div>
            </div>

            <!-- Report History Section -->
            <div class="history-section">
                <div class="history-header">
                    <h3>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Report History
                    </h3>
                    <div class="history-search">
                        <input type="text" id="historySearch" placeholder="Search reports...">
                        <button class="btn-export" id="clearHistoryBtn" style="padding: 6px 12px;">
                            <i class="fa-solid fa-trash-alt"></i>
                            Clear All
                        </button>
                    </div>
                </div>
                <div class="history-list" id="historyList">
                    <div class="empty-preview" style="padding: 40px;">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>No report history yet. Generate your first report!</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        // Mock investigation data matching the provided schema
        window.investigationData = {
            spot: [
                { id: 101, incident_id: 5001, officer: "Insp. Ramirez", dead: 2, injured: 5, owner: "Maria Santos", fire_started: "2025-03-10 14:20:00", fire_out: "2025-03-10 18:45:00", estimated_damage: 1250000, occupancy: "residential", estimated_area: 180, station_name: "Central Fire Station" },
                { id: 102, incident_id: 5002, officer: "Sr. Insp. Gomez", dead: 0, injured: 1, owner: "Jose Mercado", fire_started: "2025-03-12 22:10:00", fire_out: "2025-03-13 01:30:00", estimated_damage: 450000, occupancy: "commercial", estimated_area: 95, station_name: "North District Station" },
                { id: 103, incident_id: 5003, officer: "FO3 Castillo", dead: 0, injured: 0, owner: "ABC Manufacturing", fire_started: "2025-03-15 08:05:00", fire_out: "2025-03-15 11:20:00", estimated_damage: 2800000, occupancy: "industrial", estimated_area: 450, station_name: "Industrial Zone Station" }
            ],
            progress: [
                { id: 201, spot_id: 101, officer: "Insp. Ramirez", responsible_arson: "Under investigation", fire_cause: "Electrical short circuit", last_updated: "2025-03-20", findings: "Wiring violation noted", station_name: "Central Fire Station" },
                { id: 202, spot_id: 102, officer: "Sr. Insp. Gomez", responsible_arson: "None", fire_cause: "Kitchen accident", last_updated: "2025-03-22", findings: "LPG leak suspected", station_name: "North District Station" }
            ],
            final: [
                { id: 301, progress_id: 201, officer: "Insp. Ramirez", total_damage: 1320000, exact_address: "123 Mabini St, Brgy. Central", conclusion: "Accidental due to faulty wiring", recommendation: "Update electrical systems", station_name: "Central Fire Station" },
                { id: 302, progress_id: 202, officer: "Sr. Insp. Gomez", total_damage: 480000, exact_address: "45 Rizal Ave, Brgy. North", conclusion: "Negligence in LPG handling", recommendation: "Mandatory safety training", station_name: "North District Station" }
            ]
        };

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function generateReport() {
            const reportType = document.getElementById('reportType').value;
            const station = document.getElementById('stationFilter').value;
            const occupancy = document.getElementById('statusFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let data = [];
            if (reportType === 'spot') data = [...window.investigationData.spot];
            else if (reportType === 'progress') data = [...window.investigationData.progress];
            else if (reportType === 'final') data = [...window.investigationData.final];
            
            if (station) data = data.filter(item => item.station_name === station);
            if (occupancy && reportType === 'spot') data = data.filter(item => item.occupancy === occupancy);
            if (startDate && reportType === 'spot') data = data.filter(item => item.fire_started && item.fire_started.split(' ')[0] >= startDate);
            if (endDate && reportType === 'spot') data = data.filter(item => item.fire_started && item.fire_started.split(' ')[0] <= endDate);
            
            updateReportPreview(reportType, data);
            saveToHistory(reportType, data.length, { station, occupancy, startDate, endDate });
            showToast(`Report generated with ${data.length} record(s)`);
        }
        
        function updateReportPreview(reportType, data) {
            const preview = document.getElementById('reportPreview');
            const container = document.getElementById('previewContent');
            const countSpan = document.getElementById('recordCount');
            const titleSpan = document.getElementById('reportTitle');
            const typeNames = { spot: 'Spot Investigation', progress: 'Progress Investigation', final: 'Final Investigation' };
            titleSpan.textContent = `- ${typeNames[reportType]}`;
            countSpan.textContent = data.length;
            
            if (!data.length) {
                container.innerHTML = `<div class="empty-preview"><i class="fa-regular fa-folder-open"></i><p>No investigation data matches filters.</p></div>`;
                preview.style.display = 'block';
                return;
            }
            
            let html = '<table class="report-table"><thead><tr>';
            if (reportType === 'spot') {
                html += '<th>Report ID</th><th>Incident #</th><th>Investigator</th><th>Owner</th><th>Fire Started</th><th>Fire Out</th><th>Deaths</th><th>Injured</th><th>Est. Damage (PHP)</th><th>Occupancy</th><th>Area (sqm)</th><th>Station</th>';
                html += '</tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td>${r.incident_id}</td>
                        <td><strong>${r.officer}</strong></td>
                        <td>${r.owner}</td>
                        <td>${r.fire_started.split(' ')[0]}</td>
                        <td>${r.fire_out.split(' ')[0]}</td>
                        <td class="status-badge High">${r.dead}</td>
                        <td>${r.injured}</td>
                        <td>₱${r.estimated_damage.toLocaleString()}</td>
                        <td><span class="status-badge">${r.occupancy}</span></td>
                        <td>${r.estimated_area}</td>
                        <td>${r.station_name}</td>
                    </tr>`;
                });
            } else if (reportType === 'progress') {
                html += '<th>Progress ID</th><th>Spot ID</th><th>Officer</th><th>Responsible Arson</th><th>Fire Cause</th><th>Last Updated</th><th>Key Findings</th><th>Station</th>';
                html += '</tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td>${r.spot_id}</td>
                        <td><strong>${r.officer}</strong></td>
                        <td>${r.responsible_arson}</td>
                        <td>${r.fire_cause}</td>
                        <td>${r.last_updated}</td>
                        <td>${r.findings}</td>
                        <td>${r.station_name}</td>
                    </tr>`;
                });
            } else if (reportType === 'final') {
                html += '<th>Final ID</th><th>Progress ID</th><th>Officer</th><th>Total Damage (PHP)</th><th>Exact Address</th><th>Conclusion</th><th>Recommendation</th><th>Station</th>';
                html += '</tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td>${r.progress_id}</td>
                        <td><strong>${r.officer}</strong></td>
                        <td>₱${r.total_damage.toLocaleString()}</td>
                        <td>${r.exact_address}</td>
                        <td>${r.conclusion}</td>
                        <td>${r.recommendation}</td>
                        <td>${r.station_name}</td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            container.innerHTML = html;
            preview.style.display = 'block';
            preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function saveToHistory(reportType, recordCount, filters) {
            const history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            const typeNames = { spot: 'Spot Investigation', progress: 'Progress Report', final: 'Final Investigation' };
            const newReport = { id: Date.now(), name: typeNames[reportType], type: reportType, recordCount, filters, timestamp: new Date().toLocaleString() };
            history.unshift(newReport);
            if (history.length > 50) history.pop();
            localStorage.setItem('reportHistory', JSON.stringify(history));
            renderHistoryList();
        }
        
        function renderHistoryList() {
            const historyList = document.getElementById('historyList');
            const searchTerm = document.getElementById('historySearch')?.value.toLowerCase() || '';
            let history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            if (searchTerm) history = history.filter(item => item.name.toLowerCase().includes(searchTerm));
            if (!history.length) {
                historyList.innerHTML = `<div class="empty-preview" style="padding: 40px;"><i class="fa-regular fa-folder-open"></i><p>No report history yet.</p></div>`;
                return;
            }
            historyList.innerHTML = history.map(item => `
                <div class="history-item" data-id="${item.id}">
                    <div class="history-info">
                        <div class="history-name"><i class="fa-solid fa-file-alt"></i>${item.name}</div>
                        <div class="history-meta"><span><i class="fa-regular fa-calendar"></i> ${item.timestamp}</span><span><i class="fa-solid fa-list"></i> ${item.recordCount} records</span></div>
                    </div>
                    <div class="history-actions">
                        <button class="view-report" onclick="viewReport(${item.id})" title="View"><i class="fa-solid fa-eye"></i></button>
                        <button class="delete-report" onclick="deleteReport(${item.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }
        
        function viewReport(reportId) {
            const history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            const report = history.find(r => r.id == reportId);
            if (report) {
                document.getElementById('reportType').value = report.type;
                if (report.filters.station) document.getElementById('stationFilter').value = report.filters.station;
                if (report.filters.occupancy) document.getElementById('statusFilter').value = report.filters.occupancy;
                if (report.filters.startDate) document.getElementById('startDate').value = report.filters.startDate;
                if (report.filters.endDate) document.getElementById('endDate').value = report.filters.endDate;
                generateReport();
                showToast(`Loaded: ${report.name}`);
            }
        }
        
        function deleteReport(reportId) {
            let history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            history = history.filter(r => r.id != reportId);
            localStorage.setItem('reportHistory', JSON.stringify(history));
            renderHistoryList();
            showToast('Report deleted');
        }
        
        function clearAllHistory() {
            if (confirm('Permanently delete all report history?')) {
                localStorage.setItem('reportHistory', '[]');
                renderHistoryList();
                showToast('All history cleared');
            }
        }
        
        function resetFilters() {
            document.getElementById('reportType').value = 'spot';
            document.getElementById('stationFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            showToast('Filters reset');
        }
        
        function exportReportAsCSV() {
            const reportType = document.getElementById('reportType').value;
            const table = document.querySelector('#previewContent table');
            if (!table) { showToast('No data to export', 'error'); return; }
            let csv = [];
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('th, td').forEach(cell => rowData.push(cell.innerText.replace(/,/g, ';')));
                csv.push(rowData.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportType}_report_${new Date().toISOString().slice(0,19)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Exported as CSV');
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            renderHistoryList();
            document.getElementById('generateReportBtn').addEventListener('click', generateReport);
            document.getElementById('resetFiltersBtn').addEventListener('click', resetFilters);
            document.getElementById('exportReportBtn').addEventListener('click', exportReportAsCSV);
            document.getElementById('clearHistoryBtn').addEventListener('click', clearAllHistory);
            document.getElementById('historySearch').addEventListener('input', renderHistoryList);
            
            const openSidebar = document.getElementById('openSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (openSidebar) openSidebar.addEventListener('click', () => { sidebar.classList.add('open'); backdrop.classList.remove('hidden'); });
            if (closeSidebar) closeSidebar.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.classList.add('hidden'); });
            if (backdrop) backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.classList.add('hidden'); });
        });
    </script>
    <script>
        window.executiveReportData = @json($reportPayload ?? ['spot' => [], 'progress' => [], 'final' => []]);
        window.executiveStationProfile = @json($stationProfile ?? []);
        let currentGeneratedRows = [];
        let currentGeneratedType = 'spot';
        let currentPreviewPage = 1;
        const previewPageSize = 15;

        function formatDateTime(value) {
            if (!value) return 'N/A';
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? value : date.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        function formatDateOnly(value) {
            if (!value) return 'N/A';
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(Number(value || 0));
        }

        function getFilteredData() {
            const reportType = document.getElementById('reportType').value;
            const station = document.getElementById('stationFilter').value;
            const status = document.getElementById('statusFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const sortBy = document.getElementById('sortFilter').value;

            let data = [...(window.executiveReportData[reportType] || [])];

            if (station) data = data.filter(item => String(item.station_id) === String(station));
            if (status) {
                data = data.filter(item => {
                    if (reportType === 'spot' && ['residential', 'commercial', 'industrial'].includes(status)) {
                        return item.occupancy === status;
                    }
                    return (item.status || '').toLowerCase() === status.toLowerCase();
                });
            }
            if (startDate) {
                data = data.filter(item => {
                    const sourceDate = item.fire_started || item.created_at;
                    return sourceDate && sourceDate.slice(0, 10) >= startDate;
                });
            }
            if (endDate) {
                data = data.filter(item => {
                    const sourceDate = item.fire_started || item.created_at;
                    return sourceDate && sourceDate.slice(0, 10) <= endDate;
                });
            }

            data.sort((a, b) => {
                switch (sortBy) {
                    case 'oldest':
                        return new Date(a.created_at_iso || a.created_at) - new Date(b.created_at_iso || b.created_at);
                    case 'incident':
                        return (a.incident_title || '').localeCompare(b.incident_title || '');
                    case 'investigator':
                        return (a.investigator_name || '').localeCompare(b.investigator_name || '');
                    case 'damage-high':
                        return Number((b.total_damage ?? b.estimated_damage) || 0) - Number((a.total_damage ?? a.estimated_damage) || 0);
                    case 'damage-low':
                        return Number((a.total_damage ?? a.estimated_damage) || 0) - Number((b.total_damage ?? b.estimated_damage) || 0);
                    case 'newest':
                    default:
                        return new Date(b.created_at_iso || b.created_at) - new Date(a.created_at_iso || a.created_at);
                }
            });

            return { reportType, data };
        }

        function buildTableHtml(reportType, data) {
            let html = '<table class="report-table"><thead><tr>';
            if (reportType === 'spot') {
                html += '<th>Spot ID</th><th>Incident</th><th>Type</th><th>Investigator</th><th>Owner</th><th>Fire Started</th><th>Fire Out</th><th>Damage</th><th>Occupancy</th><th>Area</th><th>Status</th></tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td><strong>${r.incident_title || 'N/A'}</strong><div style="font-size:12px;color:#64748b;">Incident #${r.incident_id ?? 'N/A'}</div></td>
                        <td>${r.incident_type || 'N/A'}</td>
                        <td>${r.investigator_name || 'N/A'}</td>
                        <td>${r.owner || 'N/A'}</td>
                        <td>${formatDateTime(r.fire_started)}</td>
                        <td>${formatDateTime(r.fire_out)}</td>
                        <td>${formatCurrency(r.estimated_damage)}</td>
                        <td><span class="status-badge">${r.occupancy || 'N/A'}</span></td>
                        <td>${r.estimated_area ?? 'N/A'} sqm</td>
                        <td><span class="status-badge ${r.status || ''}">${r.status || 'filed'}</span></td>
                    </tr>`;
                });
            } else if (reportType === 'progress') {
                html += '<th>Progress ID</th><th>Incident</th><th>Type</th><th>Investigator</th><th>Fire Origin</th><th>Responsible Arson</th><th>Fire Cause</th><th>Filed</th><th>Status</th></tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td><strong>${r.incident_title || 'N/A'}</strong><div style="font-size:12px;color:#64748b;">Spot #${r.spot_investigation_id ?? 'N/A'}</div></td>
                        <td>${r.incident_type || 'N/A'}</td>
                        <td>${r.investigator_name || 'N/A'}</td>
                        <td>${r.fire_origin || 'N/A'}</td>
                        <td>${r.responsible_arson || 'N/A'}</td>
                        <td>${r.fire_cause || 'N/A'}</td>
                        <td>${formatDateOnly(r.created_at)}</td>
                        <td><span class="status-badge ${r.status || ''}">${r.status || 'conducting'}</span></td>
                    </tr>`;
                });
            } else {
                html += '<th>Final ID</th><th>Incident</th><th>Type</th><th>Investigator</th><th>Total Damage</th><th>Exact Address</th><th>Filed</th><th>Status</th></tr></thead><tbody>';
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.id}</td>
                        <td><strong>${r.incident_title || 'N/A'}</strong><div style="font-size:12px;color:#64748b;">Progress #${r.progress_investigation_id ?? 'N/A'}</div></td>
                        <td>${r.incident_type || 'N/A'}</td>
                        <td>${r.investigator_name || 'N/A'}</td>
                        <td>${formatCurrency(r.total_damage)}</td>
                        <td>${r.exact_address || 'N/A'}</td>
                        <td>${formatDateOnly(r.created_at)}</td>
                        <td><span class="status-badge completed">completed</span></td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            return html;
        }

        function renderExecutivePreviewPage(reportType, data, page = 1) {
            const container = document.getElementById('previewContent');
            const totalPages = Math.max(1, Math.ceil(data.length / previewPageSize));
            currentPreviewPage = Math.min(Math.max(1, page), totalPages);
            const startIndex = (currentPreviewPage - 1) * previewPageSize;
            const endIndex = Math.min(startIndex + previewPageSize, data.length);
            const pageRows = data.slice(startIndex, endIndex);
            const controls = data.length > previewPageSize ? `
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;">
                    <div style="font-size:0.82rem;color:#64748b;">Showing ${startIndex + 1}-${endIndex} of ${data.length} records</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" class="btn-export" onclick="renderExecutivePreviewPage('${reportType}', currentGeneratedRows, ${currentPreviewPage - 1})" ${currentPreviewPage <= 1 ? 'disabled' : ''}>Prev</button>
                        <div class="stat-badge">Page ${currentPreviewPage} of ${totalPages}</div>
                        <button type="button" class="btn-export" onclick="renderExecutivePreviewPage('${reportType}', currentGeneratedRows, ${currentPreviewPage + 1})" ${currentPreviewPage >= totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            ` : '';
            container.innerHTML = buildTableHtml(reportType, pageRows) + controls;
        }

        function saveToHistory(reportType, recordCount) {
            const history = JSON.parse(localStorage.getItem('chiefExecutiveReportHistory') || '[]');
            const typeNames = { spot: 'Spot Investigation', progress: 'Progress Investigation', final: 'Final Investigation' };
            history.unshift({ id: Date.now(), type: reportType, name: typeNames[reportType], recordCount, timestamp: new Date().toLocaleString() });
            localStorage.setItem('chiefExecutiveReportHistory', JSON.stringify(history.slice(0, 20)));
            renderHistoryList();
        }

        function renderHistoryList() {
            const historyList = document.getElementById('historyList');
            const searchTerm = (document.getElementById('historySearch')?.value || '').toLowerCase();
            let history = JSON.parse(localStorage.getItem('chiefExecutiveReportHistory') || '[]');
            if (searchTerm) history = history.filter(item => item.name.toLowerCase().includes(searchTerm));
            if (!history.length) {
                historyList.innerHTML = `<div class="empty-preview" style="padding: 40px;"><i class="fa-regular fa-folder-open"></i><p>No report history yet.</p></div>`;
                return;
            }
            historyList.innerHTML = history.map(item => `<div class="history-item"><div class="history-info"><div class="history-name"><i class="fa-solid fa-file-alt"></i>${item.name}</div><div class="history-meta"><span><i class="fa-regular fa-calendar"></i> ${item.timestamp}</span><span><i class="fa-solid fa-list"></i> ${item.recordCount} records</span></div></div><div class="history-actions"><button type="button" onclick="document.getElementById('reportType').value='${item.type}'; generateReport();" title="View"><i class="fa-solid fa-eye"></i></button></div></div>`).join('');
        }

        generateReport = function () {
            const { reportType, data } = getFilteredData();
            const preview = document.getElementById('reportPreview');
            const container = document.getElementById('previewContent');
            const countSpan = document.getElementById('recordCount');
            const titleSpan = document.getElementById('reportTitle');
            const typeNames = { spot: 'Spot Investigation', progress: 'Progress Investigation', final: 'Final Investigation' };

            currentGeneratedRows = data;
            currentGeneratedType = reportType;
            currentPreviewPage = 1;
            titleSpan.textContent = `- ${typeNames[reportType]}`;
            countSpan.textContent = data.length;

            if (!data.length) {
                container.innerHTML = `<div class="empty-preview"><i class="fa-regular fa-folder-open"></i><p>No investigation data matches the selected filters.</p></div>`;
                preview.style.display = 'block';
                showToast('No records found for the selected filters', 'error');
                return;
            }

            renderExecutivePreviewPage(reportType, data, 1);
            preview.style.display = 'block';
            saveToHistory(reportType, data.length);
            preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
            showToast(`Generated ${typeNames[reportType]} with ${data.length} record(s)`);
        };

        resetFilters = function () {
            document.getElementById('reportType').value = 'spot';
            document.getElementById('stationFilter').value = '{{ $stations->first()->station_id ?? "" }}';
            document.getElementById('statusFilter').value = '';
            document.getElementById('sortFilter').value = 'newest';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            showToast('Filters reset');
        };

        exportReportAsCSV = function () {
            if (!currentGeneratedRows.length) return showToast('No generated report to export', 'error');
            const temp = document.createElement('div');
            temp.innerHTML = buildTableHtml(currentGeneratedType, currentGeneratedRows);
            const table = temp.querySelector('table');
            if (!table) return showToast('No table available for export', 'error');
            const rows = Array.from(table.querySelectorAll('tr')).map(row => Array.from(row.querySelectorAll('th, td')).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(','));
            const blob = new Blob([rows.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${document.getElementById('reportType').value}_executive_report_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Exported as CSV');
        };

        function exportReportAsPDF() {
            if (!currentGeneratedRows.length) return showToast('No generated report to export', 'error');
            const reportType = currentGeneratedType;
            const typeNames = { spot: 'Spot Investigation', progress: 'Progress Investigation', final: 'Final Investigation' };
            const station = window.executiveStationProfile || {};
            const tableHtml = buildTableHtml(reportType, currentGeneratedRows);
            const printWindow = window.open('', '_blank', 'width=1200,height=900');
            printWindow.document.write(`<html><head><title>${typeNames[reportType]} - Executive Report</title><style>body{font-family:Arial,sans-serif;padding:32px;color:#0f172a}.header{text-align:center;margin-bottom:24px}.header h1,.header h2{margin:0}.header h1{font-size:18px}.header h2{font-size:24px;margin-top:6px}.meta{margin-top:18px;margin-bottom:24px;font-size:13px;line-height:1.7}.meta strong{display:inline-block;min-width:140px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #cbd5e1;padding:8px;text-align:left;vertical-align:top}th{background:#fff7ed}</style></head><body><div class="header"><h1>Republic of the Philippines</h1><h1>Department of the Interior and Local Government</h1><h1>Bureau of Fire Protection</h1><h2>${typeNames[reportType]} Executive Report</h2></div><div class="meta"><div><strong>Station:</strong> ${station.station_name || 'N/A'} (${station.station_code || 'N/A'})</div><div><strong>Address:</strong> ${station.address || 'N/A'}</div><div><strong>Contact:</strong> ${station.contact_number || 'N/A'} | ${station.email || 'N/A'}</div><div><strong>Chief:</strong> ${station.chief_name || 'N/A'}</div><div><strong>Personnel Count:</strong> ${station.personnel_count || 0}</div><div><strong>Generated At:</strong> ${new Date().toLocaleString()}</div><div><strong>Total Records:</strong> ${currentGeneratedRows.length}</div></div>${tableHtml}</body></html>`);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => printWindow.print(), 300);
            showToast('PDF print view opened');
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('stationFilter').value = '{{ $stations->first()->station_id ?? "" }}';
            renderHistoryList();
            document.getElementById('exportPdfBtn')?.addEventListener('click', exportReportAsPDF);

            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
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
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>
