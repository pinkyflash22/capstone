<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · Reports & Analytics</title>
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])
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
<body class="chief-dashboard-page">
    <!-- Side Navigation -->
    @include('admin.partials.sidebar', ['active' => 'reports'])
    {{-- <aside class="sidebar">
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

                <button id="closeSidebar" class="absolute top-5 right-4 lg:hidden text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>

                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->name ?? 'AD', 0, 2) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->name ?? 'Admin User' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role ?? 'Administrator' }}</span>
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="nav-item">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
                <a href="{{ route('admin.maps') }}" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Incident Map</span>
                </a>
                <a href="{{ route('admin.resource-management.index') }}" class="nav-item">
                    <i class="fa-solid fa-toolbox"></i>
                    <span>Resources</span>
                </a>
                <a href="#" class="nav-item active">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
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
                <div class="nav-section-title">RESOURCES</div>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-user"></i>
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
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-kit-medical"></i>
                    <span>Equipment</span>
                </a>
            </div>
        </nav>

        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside> --}}

    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="mobile-menu-header">
            <button id="openSidebar" class="mobile-menu-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="reports-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-chart-pie"></i>
                    Reports & Analytics
                </h1>
                <div class="date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- Alert Banner -->
            <div class="alert-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div class="content">
                    <strong>Report Center:</strong> Generate custom reports with filters, view report history, and export data for analysis.
                </div>
            </div>

            <!-- Report Generator Card -->
            <div class="report-generator-card">
                <div class="card-header">
                    <h2>
                        <i class="fa-solid fa-file-waveform"></i>
                        Generate New Report
                    </h2>
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fa-regular fa-file-lines"></i> Report Type</label>
                        <select id="reportType">
                            <option value="incidents">Incident Report</option>
                            <option value="personnel">Personnel Report</option>
                            <option value="stations">Station Summary</option>
                            <option value="alerts">Alerts Report</option>
                            <option value="chiefs">Chiefs Report</option>
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
                        <label><i class="fa-solid fa-flag"></i> Status / Severity</label>
                        <select id="statusFilter">
                            <option value="">All</option>
                           @foreach ($incidentStatuses as $status)
                                <option value="{{ $status->status }}">{{ $status->status }}</option>
                               
                           @endforeach
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
                        <button class="btn-export" id="exportReportBtn" style="padding: 6px 16px; font-size: 0.8rem;">
                            <i class="fa-solid fa-download"></i>
                            Export as CSV
                        </button>
                    </div>
                </div>
                <div class="preview-content" id="previewContent">
                    <div class="empty-preview">
                        <i class="fa-regular fa-chart-scatter"></i>
                        <p>Select filters and click "Generate Report" to view data</p>
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

    <script>
        // Pass PHP variables to JavaScript
        window.reportsData = {
            incidents: @json($incidents ?? []),
            personnel: @json($stationperson ?? []),
            stations: @json($stations ?? []),
            alerts: @json($alerts ?? []),
            chiefs: @json($chiefs ?? []),
            mapmarker: @json($mapmarker ?? [])
        };
        let currentGeneratedRows = [];
        let currentGeneratedType = 'incidents';
        let currentPreviewPage = 1;
        const previewPageSize = 15;

        // Function to show toast notification
        function showToast(message, type = 'success') {
            window.PyroNotify?.notify(message, type);
        }

        // Function to generate report based on filters
        function generateReport() {
            const reportType = document.getElementById('reportType').value;
            const stationId = document.getElementById('stationFilter').value;

            console.log('Generating report for type:', reportType, 'stationId:', stationId);
            const statusFilter = document.getElementById('statusFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let data = [];
            
            // Get data based on report type
            switch(reportType) {
                case 'incidents':
                    data = [...(window.reportsData.incidents || [])];
                    break;
                case 'personnel':
                    data = [...(window.reportsData.personnel || [])];
                    break;
                case 'stations':
                    data = [...(window.reportsData.stations || [])];
                    break;
                case 'alerts':
                    data = [...(window.reportsData.alerts || [])];
                    break;
                case 'chiefs':
                    data = [...(window.reportsData.chiefs || [])];
                    break;
                default:
                    data = [];
            }
                console.log('station : ', stationId, 'data : ', data);
            
            // Apply station filter
            if (stationId) {
                data = data.filter(item => {
                    if (item.team?.station?.station_id) return item.team.station.station_id == stationId;
                    if (item.station && item.station.station_id) return item.station.station_id == stationId;
                    if (item.marker && item.marker.station_id) return item.marker.station_id == stationId;
                    return false;
                });
            }
                console.log('station2 : ', stationId, 'data : ', data);
                console.log('status : ', statusFilter, 'data : ', data);
            
            // Apply status filter
            if (statusFilter) {
                data = data.filter(item => {
                    if (item.incidentStatus?.status) return item.incidentStatus.status.toLowerCase() === statusFilter.toLowerCase();
                    if (item.severity) return item.severity === statusFilter;
                    if (item.incidentStatus?.status) return item.incidentStatus.status === statusFilter;
                    return false;
                });
            }
            
            // Apply date filters for incidents
            if (startDate && reportType === 'incidents') {
                data = data.filter(item => {
                    const itemDate = item.created_at || item.date;
                    return itemDate && itemDate.split('T')[0] >= startDate;
                });
            }
            
            if (endDate && reportType === 'incidents') {
                data = data.filter(item => {
                    const itemDate = item.created_at || item.date;
                    return itemDate && itemDate.split('T')[0] <= endDate;
                });
            }
            
            currentGeneratedRows = data;
            currentGeneratedType = reportType;
            currentPreviewPage = 1;

            // Update preview
            updateReportPreview(reportType, data);
            
            // Save to history
            saveToHistory(reportType, data.length, { stationId, statusFilter, startDate, endDate });
            
            showToast(`Report generated successfully with ${data.length} records`);
        }
        
        // Function to update report preview
        function buildAdminReportTableHtml(reportType, data) {
            let tableHtml = '<table class="report-table"><thead><tr>';
            console.log('Generating report for type:', reportType, 'with data:', data);
            if (reportType === 'incidents') {
                tableHtml += '<th>ID</th><th>Incident Type</th><th>Location</th><th>Station</th><th>Date</th><th>Severity</th><th>Status</th><th>Action</th>';
                tableHtml += '</tr></thead><tbody>';
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.incident_id || item.id || 'N/A'}</td>
                            <td><strong>${item.incidentType?.type || item.type || 'N/A'}</strong></td>
                            <td>${item.marker?.lat && item.marker?.lng ? `${item.marker.lat}, ${item.marker.lng}` : (item.marker?.title || 'N/A')}</td>
                            <td>${item.team?.station?.station_name || 'N/A'}</td>
                            <td>${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</td>
                            <td><span class="status-badge ${item.severity || 'Medium'}">${item.severity || 'Medium'}</span></td>
                            <td><span class="status-badge ${item.status || 'active'}">${item.incidentStatus?.status || 'Active'}</span></td>
                            <td><span class="status-badge "><button class='btn btn-sm btn-outline-primary' onclick="viewIncident(${item.incident_id || item.id || 'N/A'})">View</button></span></td>

                        </tr>
                    `;
                });
            } else if (reportType === 'personnel') {
                tableHtml += '<th>ID</th><th>Name</th><th>Rank</th><th>Station</th><th>Status</th><th>Assigned Date</th><th>Action</th>';
                tableHtml += '</tr></thead><tbody>';
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.station_officer_id || item.user?.user_id || 'N/A'}</td>
                            <td><strong>${item.user?.first_name || ''} ${item.user?.middle_name || ''} ${item.user?.last_name || ''}</strong></td>
                            <td>${item.rank || 'N/A'}</td>
                            <td>${item.station?.station_name || 'N/A'}</td>
                            <td><span class="status-badge ${item.status || 'active'}">${item.status || 'Active'}</span></td>
                            <td>${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</td>
                            <td><span class="status-badge "><button class='btn btn-sm btn-outline-primary' onclick="viewPersonnel(${item.station_officer_id || item.user?.user_id || 'N/A'})">View</button></span></td>

                        </tr>
                    `;
                });
            } else if (reportType === 'stations') {
                console.log('Generating station report with data:', data);
                tableHtml += '<th>ID</th><th>Station Name</th><th>Location</th><th>Status</th><th>Personnel Count</th><th>Established</th>';
                tableHtml += '</tr></thead><tbody>';
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.station_code || item.id || 'N/A'}</td>
                            <td><strong>${item.station_name || item.name || 'N/A'}</strong></td>
                            <td>${item.marker?.lat || 'N/A'} , ${item.marker?.lng || 'N/A'}</td>
                            <td><span class="status-badge ${(item.marker?.status?.status_name || 'active').toLowerCase()}">${item.marker?.status?.status_name || 'Active'}</span></td>
                           
                            <td>${item.users?.length ?? 0}</td>
                            <td>${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</td>
                        </tr>
                    `;
                    console.log('st',item.users);
                });
            } else if (reportType === 'chiefs') {
                tableHtml += '<th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined Date</th>';
                tableHtml += '</tr></thead><tbody>';
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.user_id || item.id || 'N/A'}</td>
                            <td><strong>${item.first_name || ''} ${item.middle_name || ''} ${item.last_name || ''}</strong></td>
                            <td>${item.email || 'N/A'}</td>
                            <td>${item.role?.role || item.role || 'Chief'}</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td>${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</td>
                        </tr>
                    `;
                });
            } else if (reportType === 'alerts') {
                tableHtml += '<th>ID</th><th>Alert Type</th><th>Location</th><th>Severity</th><th>Date</th><th>Status</th>';
                tableHtml += '</tr></thead><tbody>';
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.incident_id || item.id || 'N/A'}</td>
                            <td><strong>${item.incidentType?.type_name || item.type || 'Alert'}</strong></td>
                            <td>${item.location || item.marker?.title || 'N/A'}</td>
                            <td><span class="status-badge ${item.severity || 'High'}">${item.severity || 'High'}</span></td>
                            <td>${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</td>
                            <td><span class="status-badge ${item.status || 'active'}">${item.status || 'Active'}</span></td>
                        </tr>
                    `;
                });
            }
            
            tableHtml += '</tbody></table>';
            return tableHtml;
        }

        function renderAdminPreviewPage(reportType, data, page = 1) {
            const previewContent = document.getElementById('previewContent');
            const totalPages = Math.max(1, Math.ceil(data.length / previewPageSize));
            currentPreviewPage = Math.min(Math.max(1, page), totalPages);
            const startIndex = (currentPreviewPage - 1) * previewPageSize;
            const endIndex = Math.min(startIndex + previewPageSize, data.length);
            const pageRows = data.slice(startIndex, endIndex);
            const controls = data.length > previewPageSize ? `
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;">
                    <div style="font-size:0.82rem;color:#64748b;">Showing ${startIndex + 1}-${endIndex} of ${data.length} records</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" class="btn-export" onclick="renderAdminPreviewPage('${reportType}', currentGeneratedRows, ${currentPreviewPage - 1})" ${currentPreviewPage <= 1 ? 'disabled' : ''}>Prev</button>
                        <div class="stat-badge">Page ${currentPreviewPage} of ${totalPages}</div>
                        <button type="button" class="btn-export" onclick="renderAdminPreviewPage('${reportType}', currentGeneratedRows, ${currentPreviewPage + 1})" ${currentPreviewPage >= totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            ` : '';
            previewContent.innerHTML = buildAdminReportTableHtml(reportType, pageRows) + controls;
        }

        function updateReportPreview(reportType, data) {
            const previewSection = document.getElementById('reportPreview');
            const previewContent = document.getElementById('previewContent');
            const recordCount = document.getElementById('recordCount');
            const reportTitle = document.getElementById('reportTitle');
            
            const typeNames = {
                incidents: 'Incident Report',
                personnel: 'Personnel Report',
                stations: 'Station Summary',
                alerts: 'Alerts Report',
                chiefs: 'Chiefs Report'
            };
            
            reportTitle.textContent = `- ${typeNames[reportType]}`;
            recordCount.textContent = data.length;
            
            if (data.length === 0) {
                previewContent.innerHTML = `
                    <div class="empty-preview">
                        <i class="fa-regular fa-chart-scatter"></i>
                        <p>No data matches your filters. Try adjusting the criteria.</p>
                    </div>
                `;
                previewSection.style.display = 'block';
                return;
            }

            renderAdminPreviewPage(reportType, data, 1);
            previewSection.style.display = 'block';
            
            // Scroll to preview
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Function to save report to history
        function saveToHistory(reportType, recordCount, filters) {
            const history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            const typeNames = {
                incidents: 'Incident Report',
                personnel: 'Personnel Report',
                stations: 'Station Summary',
                alerts: 'Alerts Report',
                chiefs: 'Chiefs Report'
            };
            
            const newReport = {
                id: Date.now(),
                name: typeNames[reportType],
                type: reportType,
                recordCount: recordCount,
                filters: filters,
                date: new Date().toISOString(),
                timestamp: new Date().toLocaleString()
            };
            
            history.unshift(newReport);
            if (history.length > 50) history.pop();
            localStorage.setItem('reportHistory', JSON.stringify(history));
            renderHistoryList();
        }
        
        function viewIncident(incidentId) {
            const incident = window.reportsData.incidents.find(i => i.incident_id == incidentId);
            if (incident) {
                alert(`Viewing details for Incident ID: ${incidentId}\nType: ${incident.incident_type?.type}\nLocation: ${incident.marker?.lat || 'N/A'}\nStatus: ${incident.incident_status?.status || 'N/A'}`);
            } else {
                alert('Incident details not found.');
            }
        }

 function viewPersonnel(personnelId) {
    const personnel = window.reportsData.personnel.find(
        p => p.station_officer_id == personnelId || 
             p.user?.user_id == personnelId
    );

    if (personnel) {

        // 🔥 FIX: find the correct user inside array
        const user = personnel.station?.users?.find(
            u => u.user_id == personnel.user_id
        );

        console.log(personnel);
        console.log(personnel.station);
        console.log(user);

        alert(`Viewing details for Personnel ID: ${personnelId}
Name: ${user?.first_name || ''} ${user?.last_name || ''}
Rank: ${personnel.rank}
Station: ${personnel.station?.station_name}`);

    } else {
        alert('Personnel details not found.');
    }
}
        // Function to render history list
        function renderHistoryList() {
            const historyList = document.getElementById('historyList');
            const searchTerm = document.getElementById('historySearch')?.value.toLowerCase() || '';
            let history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            
            if (searchTerm) {
                history = history.filter(item => item.name.toLowerCase().includes(searchTerm));
            }
            
            if (history.length === 0) {
                historyList.innerHTML = `
                    <div class="empty-preview" style="padding: 40px;">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>No report history yet. Generate your first report!</p>
                    </div>
                `;
                return;
            }
            
            historyList.innerHTML = history.map(item => `
                <div class="history-item" data-id="${item.id}">
                    <div class="history-info">
                        <div class="history-name">
                            <i class="fa-solid fa-file-alt"></i>
                            ${item.name}
                        </div>
                        <div class="history-meta">
                            <span><i class="fa-regular fa-calendar"></i> ${item.timestamp}</span>
                            <span><i class="fa-solid fa-list"></i> ${item.recordCount} records</span>
                        </div>
                    </div>
                    <div class="history-actions">
                        <button class="view-report" onclick="viewReport(${item.id})" title="View Report">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="delete-report" onclick="deleteReport(${item.id})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        // Function to view a saved report
        function viewReport(reportId) {
            const history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            const report = history.find(r => r.id == reportId);
            if (report) {
                document.getElementById('reportType').value = report.type;
                if (report.filters.stationId) document.getElementById('stationFilter').value = report.filters.stationId;
                if (report.filters.statusFilter) document.getElementById('statusFilter').value = report.filters.statusFilter;
                if (report.filters.startDate) document.getElementById('startDate').value = report.filters.startDate;
                if (report.filters.endDate) document.getElementById('endDate').value = report.filters.endDate;
                generateReport();
                showToast(`Loaded report: ${report.name}`);
            }
        }
        
        // Function to delete a report
        function deleteReport(reportId) {
            let history = JSON.parse(localStorage.getItem('reportHistory') || '[]');
            history = history.filter(r => r.id != reportId);
            localStorage.setItem('reportHistory', JSON.stringify(history));
            renderHistoryList();
            showToast('Report deleted from history', 'success');
        }
        
        // Function to clear all history
        function clearAllHistory() {
            if (confirm('Are you sure you want to clear all report history? This action cannot be undone.')) {
                localStorage.setItem('reportHistory', '[]');
                renderHistoryList();
                showToast('All report history cleared', 'success');
            }
        }
        
        // Function to reset filters
        function resetFilters() {
            document.getElementById('reportType').value = 'incidents';
            document.getElementById('stationFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            showToast('Filters reset to default', 'success');
        }
        
        // Function to export report as CSV
        function exportReportAsCSV() {
            if (!currentGeneratedRows.length) {
                showToast('No data to export', 'error');
                return;
            }

            const temp = document.createElement('div');
            temp.innerHTML = buildAdminReportTableHtml(currentGeneratedType, currentGeneratedRows);
            
            let csvContent = [];
            const rows = temp.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowData = [];
                const cols = row.querySelectorAll('th, td');
                cols.forEach(col => {
                    let text = col.innerText.replace(/,/g, ';');
                    rowData.push(text);
                });
                csvContent.push(rowData.join(','));
            });
            
            const blob = new Blob([csvContent.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportType}_report_${new Date().toISOString().slice(0,19)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToast(`Report exported as CSV`);
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            renderHistoryList();
            
            document.getElementById('generateReportBtn').addEventListener('click', generateReport);
            document.getElementById('resetFiltersBtn').addEventListener('click', resetFilters);
            document.getElementById('exportReportBtn')?.addEventListener('click', exportReportAsCSV);
            document.getElementById('clearHistoryBtn')?.addEventListener('click', clearAllHistory);
            document.getElementById('historySearch')?.addEventListener('input', renderHistoryList);
            
            // Sidebar mobile functionality
            const openSidebar = document.getElementById('openSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            
            if (openSidebar) {
                openSidebar.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                });
            }
            
            if (closeSidebar) {
                closeSidebar.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }
            
            if (backdrop) {
                backdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
