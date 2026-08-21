<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · Resource Management</title>
    
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])
    @vite(['resources/js/admin/resource-management.js'])
    @vite(['resources/js/admin/resources.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fb;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .management-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Tables */
        .station-grid {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-top: 24px;
        }

        .section-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .section-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .filter-select-sm {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8rem;
            background: white;
            cursor: pointer;
        }

        .station-table {
            width: 100%;
            border-collapse: collapse;
        }

        .station-table th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .station-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 0.85rem;
        }

        .station-table tbody tr:hover {
            background: #faf5ff;
        }

        /* Status Badges */
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status.available { background: #10b981; color: white; }
        .status.dispatched { background: #3b82f6; color: white; }
        .status.on-scene { background: #8b5cf6; color: white; }
        .status.busy { background: #f59e0b; color: white; }
        .status.off-duty { background: #6b7280; color: white; }
        .status.on-leave { background: #ec489a; color: white; }
        .status.unavailable { background: #ef4444; color: white; }
        .status.training { background: #14b8a6; color: white; }
        .status.resigned { background: #78716c; color: white; }
        .status.active { background: #10b981; color: white; }
        .status.inactive { background: #ef4444; color: white; }
        .status.operational { background: #10b981; color: white; }
        .status.maintenance { background: #f59e0b; color: white; }

        /* Status Select Dropdown */
        .status-select {
            padding: 4px 8px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            background: white;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.2s;
            color: #64748b;
        }

        .action-btn:hover { background: #f1f5f9; }
        .action-btn.view-resource:hover { color: #2c3e50; }
        .action-btn.edit-resource:hover { color: #f59e0b; }
        .action-btn.delete-resource:hover { color: #ef4444; }

        /* Search and Filter Bar */
        .search-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 24px;
            background: white;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .boundary-control-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 22px 24px;
            margin-bottom: 24px;
            border: 1px solid #eef2f6;
        }

        .boundary-control-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .boundary-control-header h3 {
            margin: 0 0 8px;
            font-size: 1.08rem;
            color: #1e293b;
        }

        .boundary-control-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            max-width: 70ch;
            line-height: 1.55;
        }

        .boundary-summary {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .boundary-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1px solid #fed7aa;
        }

        .boundary-table-wrap {
            overflow-x: auto;
        }

        .boundary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .boundary-table th,
        .boundary-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
            font-size: 0.84rem;
            color: #475569;
            vertical-align: middle;
        }

        .boundary-table th {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .boundary-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .boundary-status-pill.synced {
            background: #dcfce7;
            color: #166534;
        }

        .boundary-status-pill.missing {
            background: #fef3c7;
            color: #92400e;
        }

        .boundary-status-pill.invalid {
            background: #fee2e2;
            color: #991b1b;
        }

        .boundary-sync-btn {
            min-height: 38px;
            border: none;
            border-radius: 10px;
            padding: 0 14px;
            background: #2c3e50;
            color: white;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .boundary-sync-btn[disabled] {
            opacity: 0.65;
            cursor: wait;
        }

        .boundary-action-group { display:flex; align-items:center; flex-wrap:wrap; gap:7px; }
        .boundary-action-group form { margin:0; }
        .boundary-visit-btn { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:0 12px; border:1px solid #cbd5e1; border-radius:10px; color:#334155; background:#fff; text-decoration:none; font-size:.78rem; font-weight:800; }

        .search-box {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            flex: 1;
            max-width: 320px;
        }

        .search-box i { color: #94a3b8; margin-right: 10px; }
        .search-box input {
            border: none;
            background: none;
            width: 100%;
            outline: none;
            font-size: 0.85rem;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-select {
            padding: 8px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .btn-filter {
            padding: 8px 16px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-filter:hover { background: #e2e8f0; }

        /* Add Dropdown */
        .add-dropdown {
            position: relative;
            display: inline-block;
        }

        .add-dropdown-btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .add-dropdown-btn:hover { background: #1e2a36; }

        .add-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            min-width: 250px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            z-index: 100;
            overflow: hidden;
        }

        .add-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #334155;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .add-dropdown-content a:hover { background: #f1f5f9; }
        .add-dropdown-content i { width: 20px; color: #2c3e50; }
        .add-dropdown.show .add-dropdown-content { display: block; }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            background: white;
            padding: 8px 16px;
            border-radius: 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .tab-btn {
            padding: 8px 20px;
            background: none;
            border: none;
            cursor: pointer;
            border-radius: 30px;
            transition: all 0.2s;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .tab-btn:hover { background: #f1f5f9; }
        .tab-btn.active { background: #2c3e50; color: white; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.blue { background: #e0f2fe; color: #0284c7; }
        .stat-icon.orange { background: #fff3e0; color: #f97316; }
        .stat-icon.red { background: #fee2e2; color: #dc2626; }
        .stat-icon.purple { background: #f3e8ff; color: #9333ea; }

        .stat-info h3 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }

        .stat-number { font-size: 28px; font-weight: 700; color: #1e293b; }
        .stat-change { font-size: 11px; color: #10b981; margin-top: 4px; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date {
            font-size: 0.8rem;
            color: #64748b;
            background: white;
            padding: 8px 16px;
            border-radius: 40px;
        }

        .alert-banner {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 14px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2c3e50;
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h2 { font-size: 1.2rem; margin: 0; }
        .modal-close { background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer; }

        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #eef2f6;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 0.85rem;
        }
        .form-label i { margin-right: 6px; color: #2c3e50; }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .btn-primary {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-primary:hover { background: #1e2a36; }

        .btn-outline {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .btn-outline:hover { background: #f8fafc; }

        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            cursor: pointer;
        }

        .detail-row {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }

        .detail-value { color: #1e293b; font-size: 0.9rem; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .full-width {
            grid-column: span 2;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .form-input {
            padding-right: 40px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
        }

        .checkbox-group a {
            color: #2c3e50;
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: none;
            z-index: 9999;
            font-size: 0.85rem;
        }

        /* Sortable headers */
        .sortable {
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
        }

        .sortable:hover {
            background: #e2e8f0;
        }

        .sortable i {
            margin-left: 6px;
            font-size: 0.7rem;
            opacity: 0.5;
        }

        .sortable.asc i, .sortable.desc i {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .station-table { font-size: 0.75rem; }
            .station-table th, .station-table td { padding: 10px 12px; }
        }
    </style>
</head>
<body class="chief-dashboard-page resource-management-page">
    <!-- Side Navigation -->
    @include('admin.partials.sidebar', ['active' => 'resources'])
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('admin.management') }}" class="nav-item"><i class="fa-solid fa-map-location-dot"></i><span>Management</span></a>
                <a href="{{ route('admin.reports') }}" class="nav-item"><i class="fa-solid fa-chart-pie"></i><span>Reports</span></a>
                <a href="#" class="nav-item active"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="{{ route('admin.analytics') }}" class="nav-item"><i class="fa-solid fa-chart-line"></i><span>Predictive Analytics</span></a>
                {{-- <a href="#" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
                <a href="#" class="nav-item"><i class="fa-solid fa-bell"></i><span>Alerts</span></a> --}}
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside> --}}
    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>

    <main class="main-content">
        <div class="mobile-menu-header"><button id="openSidebar" class="mobile-menu-btn"><i class="fa-solid fa-bars"></i></button></div>

        <div class="management-container">
            <div class="page-header">
                <h1><i class="fa-solid fa-toolbox"></i> Resource Management</h1>
                <div class="date"><i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs</div>
            </div>

            <div class="alert-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div class="content"><strong>Resource Dashboard:</strong> Manage personnel, teams, and equipment across all stations.</div>
            </div>

            @php
                $syncedBoundaryCount = $boundaryBarangays->where('boundary_status', 'synced')->count();
                $invalidBoundaryCount = $boundaryBarangays->where('boundary_status', 'invalid')->count();
                $missingBoundaryCount = $boundaryBarangays->where('boundary_status', 'missing')->count();
            @endphp

            <section class="boundary-control-card">
                <div class="boundary-control-header">
                    <div>
                        <h3><i class="fa-solid fa-draw-polygon"></i> Barangay Boundary Control</h3>
                        <p>Boundary status is read-only here. Station administrators and chiefs manage polygons for barangays inside their assigned station coverage.</p>
                    </div>
                    <div class="boundary-summary">
                        <span class="boundary-summary-pill"><i class="fa-solid fa-layer-group"></i> {{ $boundaryBarangays->count() }} barangays</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-circle-check"></i> {{ $syncedBoundaryCount }} synced</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-triangle-exclamation"></i> {{ $invalidBoundaryCount }} invalid</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-clock-rotate-left"></i> {{ $missingBoundaryCount }} pending</span>
                    </div>
                </div>

                <div class="boundary-table-wrap">
                    <table class="boundary-table">
                        <thead>
                            <tr>
                                <th>Barangay</th>
                                <th>City</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Last Sync</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($boundaryBarangays as $boundaryBarangay)
                                <tr>
                                    <td><strong>{{ $boundaryBarangay['barangay_name'] }}</strong></td>
                                    <td>{{ $boundaryBarangay['city_name'] ?? 'No city linked' }}</td>
                                    <td>
                                        <span class="boundary-status-pill {{ $boundaryBarangay['boundary_status'] }}">
                                            <i class="fa-solid {{ $boundaryBarangay['boundary_status_icon'] }}"></i>
                                            {{ $boundaryBarangay['boundary_status_label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $boundaryBarangay['boundary_source'] ? strtoupper($boundaryBarangay['boundary_source']) : 'NONE' }}</td>
                                    <td>{{ $boundaryBarangay['boundary_updated_at_human'] ?? 'Never synced' }}</td>
                                    <td>
                                        <div class="boundary-action-group">
                                            <span class="boundary-status-pill" title="Managed by the assigned station">
                                                <i class="fa-solid fa-lock"></i> Station managed
                                            </span>
                                            <a href="{{ route('admin.barangays.show', $boundaryBarangay['barangay_id']) }}" class="boundary-visit-btn" title="Open barangay boundary details">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Details
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No barangays are available for boundary sync yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-user"></i></div><div class="stat-info"><h3>Total Personnel</h3><div class="stat-number">{{ $stationOfficers->count() }}</div><div class="stat-change">{{$stationOfficers->where('status','available')->count()}} active</div></div></div>
                <div class="stat-card"><div class="stat-icon orange"><i class="fa-solid fa-truck"></i></div><div class="stat-info"><h3>Fleet Vehicles</h3><div class="stat-number" id="totalVehicles">0</div><div class="stat-change">operational</div></div></div>
                <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-kit-medical"></i></div><div class="stat-info"><h3>Equipment Items</h3><div class="stat-number">{{ $equipments->count() }}</div><div class="stat-change">total units</div></div></div>
                <div class="stat-card"><div class="stat-icon purple"><i class="fa-solid fa-building"></i></div><div class="stat-info"><h3>Active Stations</h3><div class="stat-number">{{ $activeStations->count() }}</div><div class="stat-change">online</div></div></div>
            </div>

            <div class="search-filter-bar">
                <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search personnel, teams, or equipment..."></div>
                <div class="filter-actions">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="on-scene">On Scene</option>
                        <option value="busy">Busy</option>
                        <option value="off-duty">Off Duty</option>
                        <option value="on-leave">On Leave</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="training">Training</option>
                        <option value="resigned">Resigned</option>
                    </select>
                    <select class="filter-select" id="stationFilter">
                        <option value="">All Stations</option>
                        @foreach ($activeStations as $station)
                            <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                        @endforeach
                    </select>

                    <button class="btn-filter" id="refreshBtn"><i class="fa-solid fa-rotate"></i> Refresh</button>
                    
                    <div class="add-dropdown" id="addDropdown">
                        <button class="add-dropdown-btn" id="addDropdownBtn"><i class="fa-solid fa-plus"></i> Add New <i class="fa-solid fa-chevron-down"></i></button>
                        <div class="add-dropdown-content">
                            <a href="#" data-modal="registerUserModal"><i class="fa-solid fa-user-plus"></i> Register New User</a>
                            <a href="#" data-modal="officerModal"><i class="fa-solid fa-user-plus"></i> Add Officer to Station</a>
                            <a href="#" data-modal="teamModal"><i class="fa-solid fa-user-plus"></i> Create Team</a>
                            <a href="#" data-modal="equipmentItemModal"><i class="fa-solid fa-box"></i> Add Equipment Item</a>
                            <a href="#" data-modal="equipmentCategoryModal"><i class="fa-solid fa-tags"></i> Add Equipment Category</a>
                            <a href="#" data-modal="stationEquipmentModal"><i class="fa-solid fa-warehouse"></i> Assign Equipment to Station</a>
                        </div>
                    </div>
                    
                    <button class="btn-outline" id="exportBtn"><i class="fa-solid fa-download"></i> Export</button>
                </div>
            </div>

            <div class="tab-navigation" id="stationTabs">
                <button class="tab-btn active" data-station="all"><i class="fa-solid fa-layer-group"></i> All Stations</button>
                @foreach ($activeStations as $station)
                    <button class="tab-btn" data-station="{{ $station->station_id }}"><i class="fa-solid fa-building"></i> {{ $station->station_name }}</button>
                @endforeach
            </div>

            <!-- Personnel Table -->
            <div class="station-grid">
                <div class="section-header">
                    <h3><i class="fa-solid fa-user"></i> Personnel Management</h3>
                    <span class="stat-badge" id="personnelCount">{{ $stationOfficers->count() }} total</span>
                </div>
                <div class="table-responsive">
                    <table class="station-table">
                        <thead>
                            <tr>
                                <th>BFP ID</th>
                                <th>Officer Name</th>
                                <th>Station</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="personnelTableBody">
                            @foreach ($stationOfficers as $officer)
                            @php
                                $statusClass = strtolower($officer->status ?? 'unknown');
                                $bgColor = '#6b728020';
                                $textColor = '#374151';
                                
                                if ($statusClass == 'available') { $bgColor = '#10b98120'; $textColor = '#065f46'; }
                                elseif ($statusClass == 'dispatched') { $bgColor = '#3b82f620'; $textColor = '#1e40af'; }
                                elseif ($statusClass == 'on-scene') { $bgColor = '#8b5cf620'; $textColor = '#5b21b6'; }
                                elseif ($statusClass == 'busy') { $bgColor = '#f59e0b20'; $textColor = '#92400e'; }
                                elseif ($statusClass == 'off-duty') { $bgColor = '#6b728020'; $textColor = '#374151'; }
                                elseif ($statusClass == 'on-leave') { $bgColor = '#ec489a20'; $textColor = '#9d174d'; }
                                elseif ($statusClass == 'unavailable') { $bgColor = '#ef444420'; $textColor = '#991b1b'; }
                                elseif ($statusClass == 'training') { $bgColor = '#14b8a620'; $textColor = '#0f766e'; }
                                elseif ($statusClass == 'resigned') { $bgColor = '#78716c20'; $textColor = '#44403c'; }
                            @endphp
                            <tr class="personnel-row" data-officer-id="{{ $officer->station_officer_id }}" data-station-id="{{ $officer->station_id }}" data-status="{{ $statusClass }}">
                                <td>{{ $officer->user?->user_id ?? 'N/A' }}</td>
                                <td class="personnel-name"><strong>{{ $officer->user?->first_name ?? '' }} {{ $officer->user?->middle_name ?? '' }} {{ $officer->user?->last_name ?? '' }}</strong></td>
                                <td>{{ $officer->station?->station_name ?? 'N/A' }}</td>
                                <td>{{ $officer->rank ?? 'N/A' }}</td>
                                <td>
                                    <select class="status-select" data-officer-id="{{ $officer->station_officer_id }}" style="background: {{ $bgColor }}; color: {{ $textColor }}; border-color: {{ $textColor }}40;">
                                        <option value="available" {{ $officer->status == 'available' ? 'selected' : '' }}>Available</option>
                                        <option value="dispatched" {{ $officer->status == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                        <option value="on-scene" {{ $officer->status == 'on-scene' ? 'selected' : '' }}>On Scene</option>
                                        <option value="busy" {{ $officer->status == 'busy' ? 'selected' : '' }}>Busy</option>
                                        <option value="off-duty" {{ $officer->status == 'off-duty' ? 'selected' : '' }}>Off Duty</option>
                                        <option value="on-leave" {{ $officer->status == 'on-leave' ? 'selected' : '' }}>On Leave</option>
                                        <option value="unavailable" {{ $officer->status == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                                        <option value="training" {{ $officer->status == 'training' ? 'selected' : '' }}>Training</option>
                                        <option value="resigned" {{ $officer->status == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                    </select>
                                </td>
                                <td class="actions">
                                    <button class="action-btn view-resource" onclick="viewOfficer({{ $officer->station_officer_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn edit-resource" onclick="editOfficer({{ $officer->station_officer_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="action-btn delete-resource" onclick="deleteOfficer({{ $officer->station_officer_id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="station-grid">
                <div class="section-header">
                    <h3><i class="fa-solid fa-people-group"></i> Team Management</h3>
                    <span class="stat-badge" id="teamsCount">{{ $allTeam->count() }} teams</span>
                </div>
                <div class="table-responsive">
                    <table class="station-table">
                        <thead>
                            <tr>
                                <th>Team ID</th>
                                <th>Team Leader</th>
                                <th>Driver</th>
                                <th>Pumper</th>
                                <th>Officer 1</th>
                                <th>Officer 2</th>
                                <th>Officer 3</th>
                                <th>Rescue/Radio</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teamsTableBody">
                            @foreach ($allTeam as $team)
                            @php
                                $teamStatusClass = strtolower($team->status ?? 'unknown');
                                $teamBgColor = '#6b728020';
                                $teamTextColor = '#374151';
                                
                                if ($teamStatusClass == 'available') { $teamBgColor = '#10b98120'; $teamTextColor = '#065f46'; }
                                elseif ($teamStatusClass == 'dispatched') { $teamBgColor = '#3b82f620'; $teamTextColor = '#1e40af'; }
                                elseif ($teamStatusClass == 'responding') { $teamBgColor = '#8b5cf620'; $teamTextColor = '#5b21b6'; }
                                elseif ($teamStatusClass == 'busy') { $teamBgColor = '#f59e0b20'; $teamTextColor = '#92400e'; }
                                elseif ($teamStatusClass == 'off') { $teamBgColor = '#6b728020'; $teamTextColor = '#374151'; }
                                elseif ($teamStatusClass == 'training') { $teamBgColor = '#14b8a620'; $teamTextColor = '#0f766e'; }
                            @endphp
                            <tr class="team-row" data-team-id="{{ $team->team_id }}" data-status="{{ $teamStatusClass }}" data-station-id="{{ $team->station_id ?? '' }}">
                                <td><strong>{{ $team->team_id ?? 'N/A' }}</strong></td>
                                <td>{{ $team->teamLeader?->first_name ?? '' }} {{ $team->teamLeader?->last_name ?? '' }}<br><small>({{ $team->teamLeader?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->commander?->first_name ?? '' }} {{ $team->commander?->last_name ?? '' }}<br><small>({{ $team->commander?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->pumpers?->first_name ?? '' }} {{ $team->pumpers?->last_name ?? '' }}<br><small>({{ $team->pumpers?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->officer1?->first_name ?? '' }} {{ $team->officer1?->last_name ?? '' }}<br><small>({{ $team->officer1?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->officer2?->first_name ?? '' }} {{ $team->officer2?->last_name ?? '' }}<br><small>({{ $team->officer2?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->officer3?->first_name ?? '' }} {{ $team->officer3?->last_name ?? '' }}<br><small>({{ $team->officer3?->account_code ?? 'N/A' }})</small></td>
                                <td>{{ $team->rescueRadio?->first_name ?? '' }} {{ $team->rescueRadio?->last_name ?? '' }}<br><small>({{ $team->rescueRadio?->account_code ?? 'N/A' }})</small></td>
                                <td>
                                    <select class="status-select" data-team-id="{{ $team->team_id }}" style="background: {{ $teamBgColor }}; color: {{ $teamTextColor }}; border-color: {{ $teamTextColor }}40;">
                                        <option value="available" {{ $team->status == 'available' ? 'selected' : '' }}>Available</option>
                                        {{-- <option value="dispatched" {{ $team->status == 'dispatched' ? 'selected' : '' }}>Dispatched</option> --}}
                                        <option value="responding" {{ $team->status == 'responding' ? 'selected' : '' }}>On Scene</option>
                                        {{-- <option value="busy" {{ $team->status == 'busy' ? 'selected' : '' }}>Busy</option> --}}
                                        <option value="off" {{ $team->status == 'off' ? 'selected' : '' }}>Off Duty</option>
                                        {{-- <option value="training" {{ $team->status == 'training' ? 'selected' : '' }}>Training</option> --}}
                                    </select>
                                </td>
                                <td class="actions">
                                    <button class="action-btn view-resource" onclick="viewTeam({{ $team->team_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn edit-resource" onclick="openEditTeamModal({{ $team->team_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

           

            <!-- All System Users Table with Enhanced Filtering and Edit Functionality -->
            <div class="station-grid">
                <div class="section-header">
                    <h3><i class="fa-solid fa-users"></i> All System Users</h3>
                    <div class="filter-controls">
                        <select id="userRoleFilter" class="filter-select-sm" onchange="filterUsers()">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="firefighter">Firefighter</option>
                            <option value="chief">Chief</option>
                            <option value="provincial admin">Provincial Admin</option>
                            <option value="station admin">Station Admin</option>
                            <option value="citizen">Citizen</option>
                        </select>
                        <select id="userSortFilter" class="filter-select-sm" onchange="sortUsers()">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="role_asc">Role (A-Z)</option>
                            <option value="role_desc">Role (Z-A)</option>
                            <option value="date_desc">Date Modified (Newest)</option>
                            <option value="date_asc">Date Modified (Oldest)</option>
                        </select>
                        <span class="stat-badge" id="usersCount">{{ $allusers->count() }} total</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="station-table" id="usersTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTableByColumn(0)">User ID <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th class="sortable" onclick="sortTableByColumn(1)">Account Code <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th class="sortable" onclick="sortTableByColumn(2)">Full Name <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th class="sortable" onclick="sortTableByColumn(3)">Email <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th>Contact Number</th>
                                <th class="sortable" onclick="sortTableByColumn(5)">Role <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th>Status</th>
                                <th class="sortable" onclick="sortTableByColumn(7)">Date Modified <i class="fa-solid fa-arrow-down-wide-short"></i></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            @foreach ($allusers as $user)
                            <tr class="user-row" data-user-id="{{ $user->user_id }}" data-role="{{ strtolower($user->role->role ?? 'unknown') }}" data-updated-at="{{ $user->updated_at ?? $user->created_at }}">
                                <td>{{ $user->user_id }}</td>
                                <td><strong>{{ $user->account_code ?? 'N/A' }}</strong></td>
                                <td>{{ $user->first_name ?? '' }} {{ $user->middle_name ?? '' }} {{ $user->last_name ?? '' }}</td>
                                <td>{{ $user->email ?? 'N/A' }}</td>
                                <td>{{ $user->contact_number ?? 'N/A' }}</td>
                                <td>
                                    <span class="status" style="background: {{ 
                                        ($user->role->role ?? '') == 'admin' ? '#dc2626' : 
                                        (($user->role->role ?? '') == 'firefighter' ? '#3b82f6' : 
                                        (($user->role->role ?? '') == 'chief' ? '#8b5cf6' : 
                                        (($user->role->role ?? '') == 'dispatcher' ? '#f59e0b' : 
                                        (($user->role->role ?? '') == 'paramedic' ? '#10b981' : '#6b7280')))) 
                                    }}; color: white;">
                                        {{ ucfirst($user->role->role ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status {{ $user->email_verified_at ? 'active' : 'inactive' }}" style="background: {{ $user->email_verified_at ? '#10b981' : '#ef4444' }}; color: white;">
                                        {{ $user->email_verified_at ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                                <td><small>{{ \Carbon\Carbon::parse($user->updated_at ?? $user->created_at)->format('Y-m-d H:i') }}</small></td>
                                <td class="actions">
                                    <button class="action-btn view-resource" onclick="viewUser({{ $user->user_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn edit-resource" onclick="openEditUserModal({{ $user->user_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="action-btn delete-resource" onclick="deleteUser({{ $user->user_id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

             <!-- Equipment Inventory Table -->
            <div class="station-grid">
                <div class="section-header">
                    <h3><i class="fa-solid fa-kit-medical"></i> Equipment Items</h3>
                    <span class="stat-badge" id="equipmentCount">{{ $equipments->count() }} items</span>
                </div>
                <div class="table-responsive">
                    <table class="station-table">
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Category</th>
                                {{-- <th>Station</th> --}}
                                {{-- <th>Quantity Available</th> --}}
                                {{-- <th>Status</th> --}}
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentTableBody">
                            @php
                                $stationEquipment = [];
                                foreach ($equipments as $equip) {
                                    $stationId = $equip->station_id ?? 'unassigned';
                                    if (!isset($stationEquipment[$stationId])) {
                                        $stationEquipment[$stationId] = [];
                                    }
                                    $found = false;
                                    foreach ($stationEquipment[$stationId] as &$item) {
                                        if ($item['equipment_id'] == $equip->equipment_id) {
                                            $item['quantity']++;
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        $stationEquipment[$stationId][] = [
                                            'equipment_id' => $equip->equipment_id,
                                            'name' => $equip->equipment_name,
                                            'category' => $equip->category->category_name ?? 'Uncategorized',
                                            'quantity' => 1,
                                            'station_id' => $stationId,
                                            'station_name' => $equip->station->station_name ?? 'Unassigned'
                                        ];
                                    }
                                }
                            @endphp
                            @foreach ($stationEquipment as $items)
                                @foreach ($items as $item)
                                <tr class="equipment-row" data-equipment-id="{{ $item['equipment_id'] }}" data-station-id="{{ $item['station_id'] }}">
                                    <td><strong>{{ $item['name'] }}</strong></td>
                                    <td>{{ $item['category'] }}</td>
                                    {{-- <td class="equipment-station">{{ $item['station_name'] }}</td> --}}
                                    {{-- <td><span class="status-badge" style="background: #10b98120; color: #065f46; padding: 4px 10px; border-radius: 20px; font-weight: 600;">{{ $item['quantity'] }}</span></td> --}}
                                    {{-- <td><span class="status operational">Available</span></td> --}}
                                    <td>
                                        <button class="action-btn edit-resource" onclick="editEquipment({{ $item['equipment_id'] }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="action-btn delete-resource" onclick="deleteEquipment({{ $item['equipment_id'] }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
    </main>

    <!-- ==================== MODALS ==================== -->

    <!-- Register User Modal -->
    <div id="registerUserModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-plus"></i> Register New User</h2>
                <button class="modal-close" onclick="closeModal('registerUserModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="registerUserForm" method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> First Name <span style="color: #dc2626;">*</span></label>
                            <input type="text" class="form-input" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Middle Name</label>
                            <input type="text" class="form-input" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Last Name <span style="color: #dc2626;">*</span></label>
                            <input type="text" class="form-input" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number <span style="color: #dc2626;">*</span></label>
                            <input type="tel" class="form-input" name="contact_number" required pattern="[0-9]{11}" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address <span style="color: #dc2626;">*</span></label>
                            <input type="email" class="form-input" name="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Account Type <span style="color: #dc2626;">*</span></label>
                            <select class="form-select" name="role_id" required>
                                <option value="">Select Role</option>
                                @foreach($roles ?? [] as $role)
                                    <option value="{{ $role->role_id }}">{{ ucfirst($role->role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-lock"></i> Password</label>
                            <div class="input-wrapper">
                                <input type="password" class="form-input" name="password" id="regPassword" placeholder="Leave blank to auto-generate">
                                <button type="button" class="password-toggle" onclick="togglePasswordField('regPassword', 'regPasswordIcon')">
                                    <i class="fa-solid fa-eye" id="regPasswordIcon"></i>
                                </button>
                            </div>
                            <small style="font-size: 0.7rem; color: #64748b;">Leave blank to auto-generate a secure password</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-lock"></i> Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" class="form-input" name="password_confirmation" id="regPasswordConfirm">
                                <button type="button" class="password-toggle" onclick="togglePasswordField('regPasswordConfirm', 'regPasswordConfirmIcon')">
                                    <i class="fa-solid fa-eye" id="regPasswordConfirmIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #eef2f6;">
                        <h4 style="margin-bottom: 16px; font-size: 0.9rem;"><i class="fa-solid fa-location-dot"></i> Address Information (Optional)</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-map-marker-alt"></i> Province</label>
                                <select class="form-select" name="province_id" id="regProvince">
                                    <option value="">Select Province</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-city"></i> City/Municipality</label>
                                <select class="form-select" name="city_id" id="regCity" disabled>
                                    <option value="">Select City</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-home"></i> Barangay</label>
                                <select class="form-select" name="barangay_id" id="regBarangay" disabled>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label"><i class="fa-solid fa-road"></i> Street Address</label>
                                <input type="text" class="form-input" name="street" placeholder="House number, street name">
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group" style="margin-top: 20px;">
                        <input type="checkbox" id="regTerms" name="terms" required>
                        <label for="regTerms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="regEmergencyAgreement" name="emergency_agreement" required>
                        <label for="regEmergencyAgreement">I confirm that I am a verified emergency responder</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('registerUserModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Register User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-edit"></i> Edit User</h2>
                <button class="modal-close" onclick="closeEditUserModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="editUserId" name="user_id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-barcode"></i> Account Code</label>
                            <input type="text" class="form-input" id="editAccountCode" readonly disabled style="background: #f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> First Name <span style="color: #dc2626;">*</span></label>
                            <input type="text" class="form-input" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Middle Name</label>
                            <input type="text" class="form-input" id="editMiddleName" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Last Name <span style="color: #dc2626;">*</span></label>
                            <input type="text" class="form-input" id="editLastName" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number <span style="color: #dc2626;">*</span></label>
                            <input type="tel" class="form-input" id="editContactNumber" name="contact_number" required pattern="[0-9]{11}" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address <span style="color: #dc2626;">*</span></label>
                            <input type="email" class="form-input" id="editEmail" name="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Account Type</label>
                            <select class="form-select" id="editRoleId" name="role_id" required>
                                <option value="">Select Role</option>
                                @foreach($roles ?? [] as $role)
                                    <option value="{{ $role->role_id }}">{{ ucfirst($role->role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-flag-checkered"></i> Verification Status</label>
                            <select class="form-select" id="editVerifiedStatus" name="email_verified_at">
                                <option value="verified">Verified</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa-solid fa-key"></i> New Password</label>
                            <div class="input-wrapper">
                                <input type="password" class="form-input" id="editPassword" name="password" placeholder="Leave blank to keep current password">
                                <button type="button" class="password-toggle" onclick="togglePasswordField('editPassword', 'editPasswordIcon')">
                                    <i class="fa-solid fa-eye" id="editPasswordIcon"></i>
                                </button>
                            </div>
                            <small style="font-size: 0.7rem; color: #64748b;">Only fill this if you want to change the password (min. 8 characters)</small>
                        </div>
                    </div>

                    <!-- Address Section for Edit -->
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #eef2f6;">
                        <h4 style="margin-bottom: 16px; font-size: 0.9rem;"><i class="fa-solid fa-location-dot"></i> Address Information</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-map-marker-alt"></i> Province</label>
                                <select class="form-select" name="province_id" id="editProvince">
                                    <option value="">Select Province</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-city"></i> City/Municipality</label>
                                <select class="form-select" name="city_id" id="editCity" disabled>
                                    <option value="">Select City</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-home"></i> Barangay</label>
                                <select class="form-select" name="barangay_id" id="editBarangay" disabled>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label"><i class="fa-solid fa-road"></i> Street Address</label>
                                <input type="text" class="form-input" name="street" id="editStreet" placeholder="House number, street name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-circle"></i> User Details</h2>
                <button class="modal-close" onclick="closeViewUserModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewUserContent"></div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeViewUserModal()">Close</button>
                <button class="btn-primary" onclick="editFromUserView()">Edit User</button>
            </div>
        </div>
    </div>

    {{-- <!-- Create team Modal -->
    <div id="teamModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-users"></i> Create New Team</h2>
                <button class="modal-close" onclick="closeModal('teamModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="createTeamForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i>Team Leader</label>
                          <select class="form-select" name="team_leader_id" id="teamLeaderSelect">
                            <option value="">Select Team Leader</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i>Pumper</label>
                           <select class="form-select" name="pumper_id" id="pumperSelect">
                            <option value="">Select Pumper</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i>Driver</label>
                          <select class="form-select" name="driver_id" id="driverSelect">
                            <option value="">Select Driver</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                             <label class="form-label"><i class="fa-solid fa-user"></i>Officer 1</label>
                          <select class="form-select" name="officer1_id" id="officer1Select">
                            <option value="">Select Officer 1</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i>Officer 2</label>
                          <select class="form-select" name="officer2_id" id="officer2Select">
                            <option value="">Select Officer 2</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i>Officer 3</label>
                          <select class="form-select" name="officer3_id" id="officer3Select">
                            <option value="">Select Officer 3</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="form-group">
                             <label class="form-label"><i class="fa-solid fa-user"></i>Rescue Operator</label>
                          <select class="form-select" name="rescue_operator_id" id="rescueOperatorSelect">
                            <option value="">Select Rescue Operator</option>
                            @foreach($users->where('role.role', 'firefighter') as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endforeach
                        </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('teamModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Create Team</button>
                </div>
            </form>
        </div>
    </div> --}}

    <!-- Create Team Modal -->
<div id="teamModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-users"></i> Create New Team</h2>
            <button class="modal-close" onclick="closeModal('teamModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="createTeamForm" method="POST">
            @csrf
            <div class="modal-body">
                <!-- Station Selection - Full Width -->
                <div class="form-group full-width" style="margin-bottom: 24px;">
                    <label class="form-label"><i class="fa-solid fa-building"></i> Select Station <span style="color: #dc2626;">*</span></label>
                    <select class="form-select" id="teamStationSelect" name="station_id" required>
                        <option value="">-- Select Station First --</option>
                        @foreach ($activeStations as $station)
                            <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                        @endforeach
                    </select>
                    <small style="font-size: 0.7rem; color: #64748b;">Select a station to load available personnel</small>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #eef2f6; margin: 16px 0 20px 0;"></div>

                <!-- Personnel Selection Fields - Initially Disabled -->
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user-tie"></i> Team Leader <span style="color: #dc2626;">*</span></label>
                        <select class="form-select" name="team_leader" id="teamLeaderSelect" disabled required>
                            <option value="">Select Team Leader</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-truck-fast"></i> Driver <span style="color: #dc2626;">*</span></label>
                        <select class="form-select" name="commander_driver" id="driverSelect" disabled >
                            <option value="">Select Driver</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-water"></i> Pumper <span style="color: #dc2626;">*</span></label>
                        <select class="form-select" name="pumper" id="pumperSelect" disabled >
                            <option value="">Select Pumper</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Officer 1</label>
                        <select class="form-select" name="firefighter_officer_1" id="officer1Select" disabled>
                            <option value="">Select Officer 1</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Officer 2</label>
                        <select class="form-select" name="firefighter_officer_2" id="officer2Select" disabled>
                            <option value="">Select Officer 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Officer 3</label>
                        <select class="form-select" name="firefighter_officer_3" id="officer3Select" disabled>
                            <option value="">Select Officer 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-radio"></i> Rescue / Radio Operator</label>
                        <select class="form-select" name="rescue_radio" id="rescueOperatorSelect" disabled>
                            <option value="">Select Rescue Operator</option>
                        </select>
                    </div>
                </div>

                <!-- Required fields indicator -->
                <div style="margin-top: 16px; font-size: 0.7rem; color: #dc2626;">
                    <i class="fa-solid fa-circle-info"></i> Team Leader, Driver, and Pumper are required fields
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('teamModal')">Cancel</button>
                <button type="submit" class="btn-primary" id="createTeamSubmitBtn" disabled>Create Team</button>
            </div>
        </form>
    </div>
</div>

    <!-- View Officer Modal -->
    <div id="viewOfficerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Officer Details</h2>
                <button class="modal-close" onclick="closeViewModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewOfficerContent"></div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeViewModal()">Close</button>
                <button class="btn-primary" onclick="editFromView()">Edit</button>
            </div>
        </div>
    </div>

    <!-- View Team Modal -->
    <div id="viewTeamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Team Details</h2>
                <button class="modal-close" onclick="closeViewTeamModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewTeamContent"></div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeViewTeamModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Team Modal -->
    {{-- <div id="editTeamModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-people-group"></i> Edit Team</h2>
                <button class="modal-close" onclick="closeEditTeamModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="editTeamContent"></div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeEditTeamModal()">Cancel</button>
                <button class="btn-primary" id="updateTeamBtn" onclick="submitTeamUpdate()">Update Team</button>
            </div>
        </div>
    </div> --}}
    <!-- Edit Team Modal - WITH UNIQUE IDs -->
<div id="editTeamModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-people-group"></i> Edit Team</h2>
            <button class="modal-close" onclick="closeEditTeamModal()"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body" id="editTeamContent"></div>
        <div class="modal-footer">
            <button class="btn-outline" onclick="closeEditTeamModal()">Cancel</button>
            <button class="btn-primary" id="updateTeamBtn" onclick="submitTeamUpdate()">Update Team</button>
        </div>
    </div>
</div>

    <!-- Edit Officer Modal -->
    {{-- <div id="editOfficerModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-edit"></i> Edit Officer</h2>
                <button class="modal-close" onclick="closeEditModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="editOfficerForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="editOfficerId" name="station_officer_id">
                    <input type="hidden" id="editUserId" name="user_id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-id-card"></i> BFP ID</label>
                            <input type="text" class="form-input" id="editBfpId" readonly disabled style="background: #f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> First Name</label>
                            <input type="text" class="form-input" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Middle Name</label>
                            <input type="text" class="form-input" id="editMiddleName" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-user"></i> Last Name</label>
                            <input type="text" class="form-input" id="editLastName" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number</label>
                            <input type="text" class="form-input" id="editContactNumber" name="contact_number" required>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                            <input type="email" class="form-input" id="editEmail" name="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-medal"></i> Rank</label>
                            <input type="text" class="form-input" id="editRank" name="rank" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-building"></i> Station</label>
                            <select class="form-select" id="editStationId" name="station_id" required>
                                <option value="">Select Station</option>
                                @foreach ($activeStations as $station)
                                    <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="available">Available</option>
                                <option value="dispatched">Dispatched</option>
                                <option value="on-scene">On Scene</option>
                                <option value="busy">Busy</option>
                                <option value="off-duty">Off Duty</option>
                                <option value="on-leave">On Leave</option>
                                <option value="unavailable">Unavailable</option>
                                <option value="training">Training</option>
                                <option value="resigned">Resigned</option>
                            </select>
                        </div>
                        <div class="form-group full-width" id="passwordFieldGroup" style="display: none;">
                            <label class="form-label"><i class="fa-solid fa-key"></i> New Password</label>
                            <div class="input-wrapper">
                                <input type="password" class="form-input" id="editPassword" name="password" placeholder="Enter new password (leave blank to keep current)">
                                <button type="button" class="password-toggle" onclick="togglePasswordField('editPassword', 'editPasswordIcon')">
                                    <i class="fa-solid fa-eye" id="editPasswordIcon"></i>
                                </button>
                            </div>
                            <small style="font-size: 0.7rem; color: #64748b;">Only fill this if you want to change the password</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Officer</button>
                </div>
            </form>
        </div>
    </div> --}}
    <!-- Edit Officer Modal - WITH UNIQUE IDs -->
<div id="editOfficerModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-edit"></i> Edit Officer</h2>
            <button class="modal-close" onclick="closeEditModal()"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editOfficerForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <input type="hidden" id="editOfficerId" name="station_officer_id">
                <input type="hidden" id="editOfficerUserId" name="user_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-id-card"></i> BFP ID</label>
                        <input type="text" class="form-input" id="editOfficerBfpId" readonly disabled style="background: #f1f5f9;">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> First Name</label>
                        <input type="text" class="form-input" id="editOfficerFirstName" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Middle Name</label>
                        <input type="text" class="form-input" id="editOfficerMiddleName" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Last Name</label>
                        <input type="text" class="form-input" id="editOfficerLastName" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number</label>
                        <input type="text" class="form-input" id="editOfficerContactNumber" name="contact_number" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                        <input type="email" class="form-input" id="editOfficerEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-medal"></i> Rank</label>
                        <input type="text" class="form-input" id="editOfficerRank" name="rank" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-building"></i> Station</label>
                        <select class="form-select" id="editOfficerStationId" name="station_id" required>
                            <option value="">Select Station</option>
                            @foreach ($activeStations as $station)
                                <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
                        <select class="form-select" id="editOfficerStatus" name="status" required>
                            <option value="available">Available</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="on-scene">On Scene</option>
                            <option value="busy">Busy</option>
                            <option value="off-duty">Off Duty</option>
                            <option value="on-leave">On Leave</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="training">Training</option>
                            <option value="resigned">Resigned</option>
                        </select>
                    </div>
                    <div class="form-group full-width" id="officerPasswordFieldGroup" style="display: none;">
                        <label class="form-label"><i class="fa-solid fa-key"></i> New Password</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-input" id="editOfficerPassword" name="password" placeholder="Enter new password (leave blank to keep current)">
                            <button type="button" class="password-toggle" onclick="toggleOfficerPasswordField()">
                                <i class="fa-solid fa-eye" id="editOfficerPasswordIcon"></i>
                            </button>
                        </div>
                        <small style="font-size: 0.7rem; color: #64748b;">Only fill this if you want to change the password</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-primary">Update Officer</button>
            </div>
        </form>
    </div>
</div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteOfficerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button class="modal-close" onclick="closeDeleteModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="text-align:center">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:48px;color:#dc2626"></i>
                    <br><br>
                    Are you sure you want to remove this officer?
                </p>
                <input type="hidden" id="deleteOfficerId">
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-danger" onclick="confirmDeleteOfficer()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete User</h2>
                <button class="modal-close" onclick="closeDeleteUserModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="text-align:center">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:48px;color:#dc2626"></i>
                    <br><br>
                    Are you sure you want to delete this user? This action cannot be undone.
                </p>
                <input type="hidden" id="deleteUserId">
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeDeleteUserModal()">Cancel</button>
                <button class="btn-danger" onclick="confirmDeleteUser()">Delete User</button>
            </div>
        </div>
    </div>

    <!-- Add Officer to Station Modal -->
    {{-- <div id="officerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-plus"></i> Add Officer to Station</h2>
                <button class="modal-close" onclick="closeModal('officerModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="connectionForm" action="{{ route('admin.station-officers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Officer</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select Officer</option>
                            @foreach ($users as $user)
                                @php $isOfficer = false; @endphp
                                @foreach ($stationOfficers as $officer)
                                    @if ((int)$officer->user_id === (int)$user->user_id)
                                        @php $isOfficer = true; @endphp
                                        @break
                                    @endif
                                @endforeach
                                @if (!$isOfficer)
                                    <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-building"></i> Station</label>
                        <select name="station_id" class="form-select" required>
                            <option value="">Select Station</option>
                            @foreach ($activeStations as $station)
                                <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-medal"></i> Rank</label>
                        <input type="text" class="form-input" name="rank" placeholder="e.g., Fire Officer III" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="on-scene">On Scene</option>
                            <option value="busy">Busy</option>
                            <option value="off-duty">Off Duty</option>
                            <option value="on-leave">On Leave</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="training">Training</option>
                            <option value="resigned">Resigned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('officerModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Officer</button>
                </div>
            </form>
        </div>
    </div> --}}
<!-- Add Officer to Station Modal -->
<div id="officerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus"></i> Add Officer to Station</h2>
            <button class="modal-close" onclick="closeModal('officerModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="connectionForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-user"></i> Officer <span style="color: #dc2626;">*</span></label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select Officer</option>
                        @foreach ($users as $user)
                            @php $isOfficer = false; @endphp
                            @foreach ($stationOfficers as $officer)
                                @if ((int)$officer->user_id === (int)$user->user_id)
                                    @php $isOfficer = true; @endphp
                                    @break
                                @endif
                            @endforeach
                            @if (!$isOfficer)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->account_code }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-building"></i> Station <span style="color: #dc2626;">*</span></label>
                    <select name="station_id" class="form-select" required>
                        <option value="">Select Station</option>
                        @foreach ($activeStations as $station)
                            <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-medal"></i> Rank <span style="color: #dc2626;">*</span></label>
                    <input type="text" class="form-input" name="rank" placeholder="e.g., Fire Officer III" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-flag"></i> Status <span style="color: #dc2626;">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="available">Available</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="on-scene">On Scene</option>
                        <option value="busy">Busy</option>
                        <option value="off-duty">Off Duty</option>
                        <option value="on-leave">On Leave</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="training">Training</option>
                        <option value="resigned">Resigned</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('officerModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Officer</button>
            </div>
        </form>
    </div>
</div>
    <!-- Add Equipment Category Modal -->
    <div id="equipmentCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-tags"></i> Add Equipment Category</h2>
                <button class="modal-close" onclick="closeModal('equipmentCategoryModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="equipmentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Category Name</label>
                        <input type="text" class="form-input" name="category_name" placeholder="e.g., PPE, Breathing Apparatus" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('equipmentCategoryModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Equipment Item Modal -->
    <div id="equipmentItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-box"></i> Add Equipment Item</h2>
                <button class="modal-close" onclick="closeModal('equipmentItemModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="eform" action="{{ route('admin.equipment.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-layer-group"></i> Equipment Category</label>
                        <select class="form-select" name="equipment_category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($equipmentcategory as $ecat)
                                <option value="{{ $ecat->equipment_category_id }}">{{ $ecat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Equipment Name</label>
                        <input type="text" class="form-input" name="equipment_name" placeholder="e.g., Fire Extinguisher" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('equipmentItemModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Equipment to Station Modal -->
    <div id="stationEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-warehouse"></i> Assign Equipment to Station</h2>
                <button class="modal-close" onclick="closeModal('stationEquipmentModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="stationEquipmentForm" action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-box"></i> Equipment</label>
                        <select class="form-select" name="equipment_id" required>
                            <option value="">Select Equipment</option>
                            @foreach ($equipments as $equip)
                                <option value="{{ $equip->equipment_id }}">{{ $equip->equipment_name }} ({{ $equip->category->category_name ?? 'Uncategorized' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-building"></i> Station</label>
                        <select class="form-select" name="station_id" required>
                            <option value="">Select Station</option>
                            @foreach ($activeStations as $station)
                                <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-cubes"></i> Quantity</label>
                        <input type="number" class="form-input" name="quantity" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('stationEquipmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Assign Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        window.resourcesData = {
            stationOfficers: @json($stationOfficers),
            allTeam: @json($allTeam),
            equipments: @json($equipments),
            equipmentcategory: @json($equipmentcategory),
            activeStations: @json($activeStations),
            users: @json($users),
            allUsers: @json($allusers)
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        
        // Add Officer to Station - JavaScript ONLY (no duplicate)
const officerForm = document.getElementById('connectionForm');
if (officerForm) {
    // Remove any existing submit handlers by cloning
    const newOfficerForm = officerForm.cloneNode(true);
    officerForm.parentNode.replaceChild(newOfficerForm, officerForm);
    
    // Remove the action attribute to prevent any default submission
    newOfficerForm.removeAttribute('action');
    
    let isSubmitting = false;
    
    newOfficerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Prevent double submission
        if (isSubmitting) {
            console.log('Already submitting...');
            return;
        }
        
        // Get the submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : 'Save Officer';
        
        // Validate form data
        const formData = new FormData(this);
        const userId = formData.get('user_id');
        const stationId = formData.get('station_id');
        const rank = formData.get('rank');
        const status = formData.get('status');
        
        if (!userId || !stationId || !rank || !status) {
            showToast('Please fill in all fields', 'error');
            return;
        }
        
        // Check if officer already assigned
        const existingOfficer = window.resourcesData.stationOfficers.find(o => o.user_id == userId);
        if (existingOfficer) {
            showToast('This officer is already assigned to a station', 'error');
            return;
        }
        
        // Disable button and show loading
        isSubmitting = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        }
        
        // Submit via AJAX only
        fetch('/admin/station-officers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Officer added successfully!');
                closeModal('officerModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Error adding officer', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                isSubmitting = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error adding officer', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
            isSubmitting = false;
        });
    });
}




        let currentOfficerId = null;
        let currentTeamId = null;
        let currentUserId = null;

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = type === 'success' ? '#10b981' : '#ef4444';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        // User Filtering and Sorting Functions
        function filterUsers() {
            const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const roleFilter = document.getElementById('userRoleFilter')?.value || '';
            const rows = document.querySelectorAll('#usersTableBody .user-row');
            
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                const accountCode = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const email = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
                const userRole = row.dataset.role || '';
                let show = true;
                
                if (search && !name.includes(search) && !accountCode.includes(search) && !email.includes(search)) show = false;
                if (roleFilter && userRole !== roleFilter) show = false;
                
                row.style.display = show ? '' : 'none';
            });
            
            updateUserCount();
        }

        function sortUsers() {
            const sortValue = document.getElementById('userSortFilter')?.value || 'name_asc';
            const tbody = document.getElementById('usersTableBody');
            const rows = Array.from(tbody.querySelectorAll('.user-row'));
            
            rows.sort((a, b) => {
                let aVal, bVal;
                
                switch(sortValue) {
                    case 'name_asc':
                        aVal = a.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        return aVal.localeCompare(bVal);
                    case 'name_desc':
                        aVal = a.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        return bVal.localeCompare(aVal);
                    case 'role_asc':
                        aVal = a.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        return aVal.localeCompare(bVal);
                    case 'role_desc':
                        aVal = a.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        return bVal.localeCompare(aVal);
                    case 'date_desc':
                        aVal = new Date(a.dataset.updatedAt || a.querySelector('td:nth-child(8)')?.textContent || 0);
                        bVal = new Date(b.dataset.updatedAt || b.querySelector('td:nth-child(8)')?.textContent || 0);
                        return bVal - aVal;
                    case 'date_asc':
                        aVal = new Date(a.dataset.updatedAt || a.querySelector('td:nth-child(8)')?.textContent || 0);
                        bVal = new Date(b.dataset.updatedAt || b.querySelector('td:nth-child(8)')?.textContent || 0);
                        return aVal - bVal;
                    default:
                        return 0;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
            updateUserCount();
        }

        function sortTableByColumn(columnIndex) {
            const tbody = document.getElementById('usersTableBody');
            const rows = Array.from(tbody.querySelectorAll('.user-row'));
            const headers = document.querySelectorAll('#usersTable th.sortable');
            const currentHeader = headers[columnIndex];
            const isAsc = currentHeader.classList.contains('asc');
            
            // Reset all headers
            headers.forEach(header => {
                header.classList.remove('asc', 'desc');
                const icon = header.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-arrow-down-wide-short';
            });
            
            rows.sort((a, b) => {
                let aVal, bVal;
                
                switch(columnIndex) {
                    case 0: // User ID
                        aVal = parseInt(a.querySelector('td:nth-child(1)')?.textContent) || 0;
                        bVal = parseInt(b.querySelector('td:nth-child(1)')?.textContent) || 0;
                        break;
                    case 1: // Account Code
                        aVal = a.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                        break;
                    case 2: // Full Name
                        aVal = a.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                        break;
                    case 3: // Email
                        aVal = a.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
                        break;
                    case 5: // Role
                        aVal = a.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        bVal = b.querySelector('td:nth-child(6) span')?.textContent.toLowerCase() || '';
                        break;
                    case 7: // Date Modified
                        aVal = new Date(a.dataset.updatedAt || a.querySelector('td:nth-child(8)')?.textContent || 0);
                        bVal = new Date(b.dataset.updatedAt || b.querySelector('td:nth-child(8)')?.textContent || 0);
                        break;
                    default:
                        return 0;
                }
                
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return isAsc ? aVal - bVal : bVal - aVal;
                }
                return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            
            if (isAsc) {
                currentHeader.classList.add('desc');
                const icon = currentHeader.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-arrow-up-wide-short';
            } else {
                currentHeader.classList.add('asc');
                const icon = currentHeader.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-arrow-down-wide-short';
            }
            
            rows.forEach(row => tbody.appendChild(row));
            updateUserCount();
        }

        function updateUserCount() {
            const visibleRows = document.querySelectorAll('#usersTableBody .user-row[style="display: none;"]');
            const totalRows = document.querySelectorAll('#usersTableBody .user-row').length;
            const visibleCount = totalRows - visibleRows.length;
            const countSpan = document.getElementById('usersCount');
            if (countSpan) countSpan.textContent = `${visibleCount} / ${totalRows} total`;
        }

        // User CRUD Functions
        function openEditUserModal(userId) {
            const user = window.resourcesData.allUsers.find(u => u.user_id == userId);
            if (!user) return;
            
            currentUserId = userId;
            document.getElementById('editUserId').value = user.user_id;
            document.getElementById('editAccountCode').value = user.account_code || 'N/A';
            document.getElementById('editFirstName').value = user.first_name || '';
            document.getElementById('editMiddleName').value = user.middle_name || '';
            document.getElementById('editLastName').value = user.last_name || '';
            document.getElementById('editContactNumber').value = user.contact_number || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editRoleId').value = user.role_id || '';
            document.getElementById('editVerifiedStatus').value = user.email_verified_at ? 'verified' : 'pending';
            document.getElementById('editPassword').value = '';
            
            // Load address if exists
            if (user.address) {
                document.getElementById('editStreet').value = user.address.street || '';
                // Set province and cascade load city/barangay
                if (user.address.province_id) {
                    document.getElementById('editProvince').value = user.address.province_id;
                    loadCitiesForEdit(user.address.province_id, user.address.city_id);
                }
                if (user.address.city_id) {
                    setTimeout(() => {
                        loadBarangaysForEdit(user.address.city_id, user.address.barangay_id);
                    }, 100);
                }
            } else {
                document.getElementById('editStreet').value = '';
                document.getElementById('editProvince').value = '';
                document.getElementById('editCity').innerHTML = '<option value="">Select City</option>';
                document.getElementById('editCity').disabled = true;
                document.getElementById('editBarangay').innerHTML = '<option value="">Select Barangay</option>';
                document.getElementById('editBarangay').disabled = true;
            }
            
            document.getElementById('editUserModal').style.display = 'flex';
            document.getElementById('editUserModal').classList.add('active');
        }

        function loadCitiesForEdit(provinceId, selectedCityId = null) {
            fetch(`/api/cities/${provinceId}`)
                .then(response => response.json())
                .then(cities => {
                    const citySelect = document.getElementById('editCity');
                    citySelect.disabled = false;
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.city_id;
                        option.textContent = city.city_name;
                        if (selectedCityId && city.city_id == selectedCityId) option.selected = true;
                        citySelect.appendChild(option);
                    });
                    if (selectedCityId) {
                        loadBarangaysForEdit(selectedCityId);
                    }
                });
        }

        function loadBarangaysForEdit(cityId, selectedBarangayId = null) {
            fetch(`/api/barangays/${cityId}`)
                .then(response => response.json())
                .then(barangays => {
                    const barangaySelect = document.getElementById('editBarangay');
                    barangaySelect.disabled = false;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                                        barangays.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay.barangay_id;
                        option.textContent = barangay.barangay_name;
                        if (selectedBarangayId && barangay.barangay_id == selectedBarangayId) option.selected = true;
                        barangaySelect.appendChild(option);
                    });
                });
        }

        function viewUser(userId) {
            const user = window.resourcesData.allUsers.find(u => u.user_id == userId);
            if (user) {
                const content = `
                    <div class="detail-row"><div class="detail-label">User ID</div><div class="detail-value">${user.user_id}</div></div>
                    <div class="detail-row"><div class="detail-label">Account Code</div><div class="detail-value"><strong>${user.account_code || 'N/A'}</strong></div></div>
                    <div class="detail-row"><div class="detail-label">Full Name</div><div class="detail-value">${user.first_name || ''} ${user.middle_name || ''} ${user.last_name || ''}</div></div>
                    <div class="detail-row"><div class="detail-label">Email Address</div><div class="detail-value">${user.email || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Contact Number</div><div class="detail-value">${user.contact_number || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Role</div><div class="detail-value"><span class="status" style="background: ${(user.role?.role ?? '') == 'admin' ? '#dc2626' : ((user.role?.role ?? '') == 'firefighter' ? '#3b82f6' : ((user.role?.role ?? '') == 'chief' ? '#8b5cf6' : '#6b7280'))}; color: white;">${ucfirst(user.role?.role ?? 'Unknown')}</span></div></div>
                    <div class="detail-row"><div class="detail-label">Verification Status</div><div class="detail-value"><span class="status" style="background: ${user.email_verified_at ? '#10b981' : '#ef4444'}; color: white;">${user.email_verified_at ? 'Verified' : 'Pending'}</span></div></div>
                    <div class="detail-row"><div class="detail-label">Registered On</div><div class="detail-value">${new Date(user.created_at).toLocaleString()}</div></div>
                    <div class="detail-row"><div class="detail-label">Last Modified</div><div class="detail-value">${new Date(user.updated_at).toLocaleString()}</div></div>
                `;
                document.getElementById('viewUserContent').innerHTML = content;
                currentUserId = userId;
                document.getElementById('viewUserModal').style.display = 'flex';
                document.getElementById('viewUserModal').classList.add('active');
            }
        }

        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function closeViewUserModal() {
            closeModal('viewUserModal');
            currentUserId = null;
        }

        function closeEditUserModal() {
            closeModal('editUserModal');
        }

        function closeDeleteUserModal() {
            closeModal('deleteUserModal');
        }

        function editFromUserView() {
            closeViewUserModal();
            if (currentUserId) {
                openEditUserModal(currentUserId);
            }
        }

        function deleteUser(userId) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserModal').style.display = 'flex';
            document.getElementById('deleteUserModal').classList.add('active');
        }

        function confirmDeleteUser() {
            const userId = document.getElementById('deleteUserId').value;
            fetch(`/admin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('User deleted successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error deleting user', 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting user', 'error');
            });
            closeDeleteUserModal();
        }

        // Update Officer Status Inline
        document.querySelectorAll('.status-select[data-officer-id]').forEach(select => {
            select.addEventListener('change', function() {
                const officerId = this.dataset.officerId;
                const newStatus = this.value;
                
                fetch(`/admin/station-officers/${officerId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Status updated successfully');
                        const row = document.querySelector(`tr[data-officer-id="${officerId}"]`);
                        if (row) {
                            row.dataset.status = newStatus;
                        }
                        updateSelectStyle(this, newStatus);
                    } else {
                        showToast(data.message || 'Error updating status', 'error');
                    }
                })
                .catch(error => {
                    showToast('Error updating status', 'error');
                });
            });
        });

        // Update Team Status Inline
        document.querySelectorAll('.status-select[data-team-id]').forEach(select => {
            select.addEventListener('change', function() {
                const teamId = this.dataset.teamId;
                const newStatus = this.value;
                
                fetch(`/admin/teams/${teamId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Team status updated successfully');
                        updateSelectStyle(this, newStatus);
                    } else {
                        showToast(data.message || 'Error updating status', 'error');
                    }
                })
                .catch(error => {
                    showToast('Error updating status', 'error');
                });
            });
        });

        function updateSelectStyle(select, status) {
            const styles = {
                'available': { bg: '#10b98120', text: '#065f46' },
                'dispatched': { bg: '#3b82f620', text: '#1e40af' },
                'on-scene': { bg: '#8b5cf620', text: '#5b21b6' },
                'busy': { bg: '#f59e0b20', text: '#92400e' },
                'off-duty': { bg: '#6b728020', text: '#374151' },
                'on-leave': { bg: '#ec489a20', text: '#9d174d' },
                'unavailable': { bg: '#ef444420', text: '#991b1b' },
                'training': { bg: '#14b8a620', text: '#0f766e' },
                'resigned': { bg: '#78716c20', text: '#44403c' }
            };
            const style = styles[status] || styles['off-duty'];
            select.style.background = style.bg;
            select.style.color = style.text;
            select.style.borderColor = style.text + '40';
        }

        // Open Edit Team Modal
        // function openEditTeamModal(teamId) {
        //     const team = window.resourcesData.allTeam.find(t => t.team_id == teamId);
        //     if (!team) return;
            
        //     currentTeamId = teamId;
        //     const allUsers = window.resourcesData.allUsers || [];
            
        //     let content = `
        //         <form id="editTeamForm">
        //             <input type="hidden" name="team_id" value="${team.team_id}">
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-user-tie"></i> Team Leader</label>
        //                 <select class="form-select" name="team_leader_id" id="teamLeaderSelect">
        //                     <option value="">Select Team Leader</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.team_leader_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-truck-fast"></i> Driver / Commander</label>
        //                 <select class="form-select" name="commander_id">
        //                     <option value="">Select Driver</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.commander_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-water"></i> Pumper</label>
        //                 <select class="form-select" name="pumper_id">
        //                     <option value="">Select Pumper</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.pumper_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-user"></i> Officer 1</label>
        //                 <select class="form-select" name="officer1_id">
        //                     <option value="">Select Officer</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer1_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-user"></i> Officer 2</label>
        //                 <select class="form-select" name="officer2_id">
        //                     <option value="">Select Officer</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer2_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-user"></i> Officer 3</label>
        //                 <select class="form-select" name="officer3_id">
        //                     <option value="">Select Officer</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer3_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-radio"></i> Rescue / Radio Operator</label>
        //                 <select class="form-select" name="rescue_radio_id">
        //                     <option value="">Select Operator</option>
        //                     ${allUsers.map(user => `<option value="${user.user_id}" ${team.rescue_radio_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
        //                 </select>
        //             </div>
        //             <div class="form-group">
        //                 <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
        //                 <select class="form-select" name="status">
        //                     <option value="available" ${team.status == 'available' ? 'selected' : ''}>Available</option>
        //                     <option value="dispatched" ${team.status == 'dispatched' ? 'selected' : ''}>Dispatched</option>
        //                     <option value="on-scene" ${team.status == 'on-scene' ? 'selected' : ''}>On Scene</option>
        //                     <option value="busy" ${team.status == 'busy' ? 'selected' : ''}>Busy</option>
        //                     <option value="off-duty" ${team.status == 'off-duty' ? 'selected' : ''}>Off Duty</option>
        //                     <option value="training" ${team.status == 'training' ? 'selected' : ''}>Training</option>
        //                 </select>
        //             </div>
        //         </form>
        //     `;
            
        //     document.getElementById('editTeamContent').innerHTML = content;
        //     document.getElementById('editTeamModal').style.display = 'flex';
        //     document.getElementById('editTeamModal').classList.add('active');
        // }
        function openEditTeamModal(teamId) {
    const team = window.resourcesData.allTeam.find(t => t.team_id == teamId);
    if (!team) return;
    
    currentTeamId = teamId;
    const allUsers = window.resourcesData.allUsers || [];
    
    let content = `
        <form id="editTeamFormContent">
            <input type="hidden" name="team_id" value="${team.team_id}">
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-user-tie"></i> Team Leader</label>
                <select class="form-select" name="team_leader_id" id="editTeamLeaderSelect">
                    <option value="">Select Team Leader</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.team_leader_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-truck-fast"></i> Driver / Commander</label>
                <select class="form-select" name="commander_id" id="editTeamCommanderSelect">
                    <option value="">Select Driver</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.commander_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-water"></i> Pumper</label>
                <select class="form-select" name="pumper_id" id="editTeamPumperSelect">
                    <option value="">Select Pumper</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.pumper_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 1</label>
                <select class="form-select" name="officer1_id" id="editTeamOfficer1Select">
                    <option value="">Select Officer</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer1_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 2</label>
                <select class="form-select" name="officer2_id" id="editTeamOfficer2Select">
                    <option value="">Select Officer</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer2_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 3</label>
                <select class="form-select" name="officer3_id" id="editTeamOfficer3Select">
                    <option value="">Select Officer</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.officer3_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-radio"></i> Rescue / Radio Operator</label>
                <select class="form-select" name="rescue_radio_id" id="editTeamRescueSelect">
                    <option value="">Select Operator</option>
                    ${allUsers.map(user => `<option value="${user.user_id}" ${team.rescue_radio_id == user.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name} (${user.account_code})</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
                <select class="form-select" name="status" id="editTeamStatusSelect">
                    <option value="available" ${team.status == 'available' ? 'selected' : ''}>Available</option>
                    <option value="dispatched" ${team.status == 'dispatched' ? 'selected' : ''}>Dispatched</option>
                    <option value="on-scene" ${team.status == 'on-scene' ? 'selected' : ''}>On Scene</option>
                    <option value="busy" ${team.status == 'busy' ? 'selected' : ''}>Busy</option>
                    <option value="off-duty" ${team.status == 'off-duty' ? 'selected' : ''}>Off Duty</option>
                    <option value="training" ${team.status == 'training' ? 'selected' : ''}>Training</option>
                </select>
            </div>
        </form>
    `;
    
    document.getElementById('editTeamContent').innerHTML = content;
    document.getElementById('editTeamModal').style.display = 'flex';
    document.getElementById('editTeamModal').classList.add('active');
}
        // function submitTeamUpdate() {
        //     const form = document.getElementById('editTeamForm');
        //     const formData = new FormData(form);
            
        //     fetch(`/admin/teams/${currentTeamId}`, {
        //         method: 'PUT',
        //         headers: {
        //             'X-CSRF-TOKEN': csrfToken
        //         },
        //         body: formData
        //     })
        //     .then(response => response.json())
        //     .then(data => {
        //         if (data.success) {
        //             showToast('Team updated successfully');
        //             setTimeout(() => location.reload(), 1500);
        //         } else {
        //             showToast(data.message || 'Error updating team', 'error');
        //         }
        //     })
        //     .catch(error => {
        //         showToast('Error updating team', 'error');
        //     });
        // }
// function submitTeamUpdate() {
//     const form = document.getElementById('editTeamFormContent');
//     if (!form) {
//         showToast('Form not found', 'error');
//         return;
//     }
    
//     const formData = new FormData(form);
    
//     fetch(`/admin/teams/${currentTeamId}`, {
//         method: 'POST',  // Use POST with method override
//         headers: {
//             'X-CSRF-TOKEN': csrfToken,
//             'X-HTTP-Method-Override': 'PUT'
//         },
//         body: formData
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             showToast('Team updated successfully');
//             setTimeout(() => location.reload(), 1500);
//         } else {
//             showToast(data.message || 'Error updating team', 'error');
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         showToast('Error updating team', 'error');
//     });
    
//     closeEditTeamModal();
// }
function submitTeamUpdate() {
    const form = document.getElementById('editTeamFormContent');
    if (!form) {
        showToast('Form not found', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validate no duplicate team members
    if (!validateTeamMembers(formData)) {
        return;
    }
    
    fetch(`/admin/teams/${currentTeamId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Team updated successfully');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Error updating team', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating team', 'error');
    });
    
    closeEditTeamModal();
}
function validateTeamMembers(formData) {
    const userIds = [
        formData.get('team_leader_id'),
        formData.get('commander_id'),
        formData.get('pumper_id'),
        formData.get('officer1_id'),
        formData.get('officer2_id'),
        formData.get('officer3_id'),
        formData.get('rescue_radio_id')
    ].filter(id => id && id !== '');
    
    const uniqueIds = new Set(userIds);
    if (userIds.length !== uniqueIds.size) {
        showToast('A user cannot be assigned to multiple roles in the same team', 'error');
        return false;
    }
    return true;
}
        // Filter functions for all tables
        function filterPersonnel() {
            const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const status = document.getElementById('statusFilter')?.value || '';
            const station = document.getElementById('stationFilter')?.value || '';
            const activeTab = document.querySelector('#stationTabs .tab-btn.active')?.dataset.station || 'all';
            const rows = document.querySelectorAll('#personnelTableBody .personnel-row');
            
            rows.forEach(row => {
                const name = row.querySelector('.personnel-name')?.textContent.toLowerCase() || '';
                const rowStatus = row.dataset.status || '';
                const rowStation = row.dataset.stationId || '';
                let show = true;
                
                if (search && !name.includes(search)) show = false;
                if (status && rowStatus !== status) show = false;
                if (station && rowStation !== station) show = false;
                if (activeTab !== 'all' && rowStation !== activeTab) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function filterTeams() {
            const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const status = document.getElementById('statusFilter')?.value || '';
            const station = document.getElementById('stationFilter')?.value || '';
            const activeTab = document.querySelector('#stationTabs .tab-btn.active')?.dataset.station || 'all';
            const rows = document.querySelectorAll('#teamsTableBody .team-row');
            
            rows.forEach(row => {
                const teamId = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                const rowStatus = row.dataset.status || '';
                const rowStation = row.dataset.stationId || '';
                let show = true;
                
                if (search && !teamId.includes(search)) show = false;
                if (status && rowStatus !== status) show = false;
                if (station && rowStation !== station) show = false;
                if (activeTab !== 'all' && rowStation !== activeTab) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function filterEquipment() {
            const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const station = document.getElementById('stationFilter')?.value || '';
            const activeTab = document.querySelector('#stationTabs .tab-btn.active')?.dataset.station || 'all';
            const rows = document.querySelectorAll('#equipmentTableBody .equipment-row');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                const rowStation = row.dataset.stationId || '';
                let show = true;
                
                if (search && !name.includes(search)) show = false;
                if (station && rowStation !== station) show = false;
                if (activeTab !== 'all' && rowStation !== activeTab) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function filterAll() { 
            filterPersonnel(); 
            filterTeams(); 
            filterEquipment();
            filterUsers();
        }

        // View functions
        function viewOfficer(id) {
            const officer = window.resourcesData.stationOfficers.find(o => o.station_officer_id == id);
            if (officer) {
                const content = `
                    <div class="detail-row"><div class="detail-label">BFP ID</div><div class="detail-value">${officer.user?.user_id || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Full Name</div><div class="detail-value">${officer.user?.first_name || ''} ${officer.user?.middle_name || ''} ${officer.user?.last_name || ''}</div></div>
                    <div class="detail-row"><div class="detail-label">Station</div><div class="detail-value">${officer.station?.station_name || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Rank</div><div class="detail-value">${officer.rank || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value"><span class="status ${officer.status?.toLowerCase()}">${officer.status || 'Unknown'}</span></div></div>
                    <div class="detail-row"><div class="detail-label">Email</div><div class="detail-value">${officer.user?.email || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Contact</div><div class="detail-value">${officer.user?.contact_number || 'N/A'}</div></div>
                `;
                document.getElementById('viewOfficerContent').innerHTML = content;
                currentOfficerId = id;
                document.getElementById('viewOfficerModal').style.display = 'flex';
                document.getElementById('viewOfficerModal').classList.add('active');
            }
        }

        function viewTeam(id) {
            const team = window.resourcesData.allTeam.find(t => t.team_id == id);
            if (team) {
                const content = `
                    <div class="detail-row"><div class="detail-label">Team ID</div><div class="detail-value">${team.team_id}</div></div>
                    <div class="detail-row"><div class="detail-label">Team Leader</div><div class="detail-value">${team.teamLeader?.first_name || ''} ${team.teamLeader?.last_name || ''} (${team.teamLeader?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Driver</div><div class="detail-value">${team.commander?.first_name || ''} ${team.commander?.last_name || ''} (${team.commander?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Pumper</div><div class="detail-value">${team.pumpers?.first_name || ''} ${team.pumpers?.last_name || ''} (${team.pumpers?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Officer 1</div><div class="detail-value">${team.officer1?.first_name || ''} ${team.officer1?.last_name || ''} (${team.officer1?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Officer 2</div><div class="detail-value">${team.officer2?.first_name || ''} ${team.officer2?.last_name || ''} (${team.officer2?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Officer 3</div><div class="detail-value">${team.officer3?.first_name || ''} ${team.officer3?.last_name || ''} (${team.officer3?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Rescue/Radio</div><div class="detail-value">${team.rescueRadio?.first_name || ''} ${team.rescueRadio?.last_name || ''} (${team.rescueRadio?.account_code || 'N/A'})</div></div>
                    <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value"><span class="status ${team.status?.toLowerCase()}">${team.status || 'Unknown'}</span></div></div>
                `;
                document.getElementById('viewTeamContent').innerHTML = content;
                document.getElementById('viewTeamModal').style.display = 'flex';
                document.getElementById('viewTeamModal').classList.add('active');
            }
        }
        function editOfficer(id) { 
    const officer = window.resourcesData.stationOfficers.find(o => o.station_officer_id == id);
    console.log("officers data: ", officer); 

    if (officer) { 
        document.getElementById('editOfficerId').value = officer.station_officer_id;
        document.getElementById('editOfficerUserId').value = officer.user_id;
        document.getElementById('editOfficerBfpId').value = officer.user?.user_id || 'N/A';
        document.getElementById('editOfficerFirstName').value = officer.user?.first_name || '';
        document.getElementById('editOfficerMiddleName').value = officer.user?.middle_name || '';
        document.getElementById('editOfficerLastName').value = officer.user?.last_name || '';
        document.getElementById('editOfficerContactNumber').value = officer.user?.contact_number || '';
        document.getElementById('editOfficerEmail').value = officer.user?.email || '';
        document.getElementById('editOfficerRank').value = officer.rank || '';
        document.getElementById('editOfficerStatus').value = officer.status?.toLowerCase() || 'available';
        document.getElementById('editOfficerStationId').value = officer.station_id || '';
        
        const hasPassword = officer.user?.password !== null && officer.user?.password !== '';
        const passwordFieldGroup = document.getElementById('officerPasswordFieldGroup');
        if (passwordFieldGroup) {
            passwordFieldGroup.style.display = hasPassword ? 'block' : 'none';
        }
        
        document.getElementById('editOfficerModal').style.display = 'flex'; 
        document.getElementById('editOfficerModal').classList.add('active'); 
    } 
}
        // function editOfficer(id) { 
        //     const officer = window.resourcesData.stationOfficers.find(o => o.station_officer_id == id);
        //     console.log("officers data: ", officer); 
        //     console.log("officers data:", officer.user.first_name); 

        //     if (officer) { 
        //         document.getElementById('editOfficerId').value = officer.station_officer_id;
        //         document.getElementById('editUserId').value = officer.user_id;
        //         document.getElementById('editBfpId').value = officer.user?.user_id || 'N/A';
        //         document.getElementById('editFirstName').value = officer.user?.first_name || '';
        //         document.getElementById('editMiddleName').value = officer.user?.middle_name || '';
        //         document.getElementById('editLastName').value = officer.user?.last_name || '';
        //         document.getElementById('editContactNumber').value = officer.user?.contact_number || '';
        //         document.getElementById('editEmail').value = officer.user?.email || '';
        //         document.getElementById('editRank').value = officer.rank || '';
        //         document.getElementById('editStatus').value = officer.status?.toLowerCase() || 'available';
        //         document.getElementById('editStationId').value = officer.station_id || '';
                
        //         const hasPassword = officer.user?.password !== null && officer.user?.password !== '';
        //         const passwordFieldGroup = document.getElementById('passwordFieldGroup');
        //         if (passwordFieldGroup) {
        //             passwordFieldGroup.style.display = hasPassword ? 'block' : 'none';
        //         }
                
        //         document.getElementById('editOfficerModal').style.display = 'flex'; 
        //         document.getElementById('editOfficerModal').classList.add('active'); 
        //     } 
        // }
        
        function deleteOfficer(id) { 
            document.getElementById('deleteOfficerId').value = id; 
            document.getElementById('deleteOfficerModal').style.display = 'flex'; 
            document.getElementById('deleteOfficerModal').classList.add('active'); 
        }
        
        function editEquipment(id) { 
            showToast('Edit equipment feature - implement API endpoint', 'info'); 
        }
        
        function deleteEquipment(id) { 
            if (confirm('Delete this equipment?')) {
                showToast('Equipment deleted', 'success');
            }
        }

        function closeViewModal() { 
            closeModal('viewOfficerModal'); 
            currentOfficerId = null; 
        }
        
        function closeViewTeamModal() { 
            closeModal('viewTeamModal'); 
        }
        
        function closeEditTeamModal() { 
            closeModal('editTeamModal'); 
        }
        
        function closeEditModal() { 
            closeModal('editOfficerModal'); 
        }
        
        function closeDeleteModal() { 
            closeModal('deleteOfficerModal'); 
        }
        
        function editFromView() { 
            closeViewModal(); 
            if (currentOfficerId) {
                editOfficer(currentOfficerId);
            }
        }

        function confirmDeleteOfficer() {
            const id = document.getElementById('deleteOfficerId').value;
            fetch(`/admin/station-officer/${id}`, { 
                method: 'DELETE', 
                headers: { 
                    'Accept': 'application/json', 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                }
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success) { 
                    showToast('Officer deleted'); 
                    setTimeout(() => location.reload(), 500); 
                } else { 
                    showToast(d.message || 'Error', 'error'); 
                } 
            });
            closeDeleteModal();
        }
        function toggleOfficerPasswordField() {
    const input = document.getElementById('editOfficerPassword');
    const icon = document.getElementById('editOfficerPasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
        // function togglePasswordField(inputId, iconId) {
        //     const input = document.getElementById(inputId);
        //     const icon = document.getElementById(iconId);
        //     if (input.type === 'password') {
        //         input.type = 'text';
        //         icon.classList.remove('fa-eye');
        //         icon.classList.add('fa-eye-slash');
        //     } else {
        //         input.type = 'password';
        //         icon.classList.remove('fa-eye-slash');
        //         icon.classList.add('fa-eye');
        //     }
        // }

        // Form submissions
        // document.getElementById('editOfficerForm')?.addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     const officerId = document.getElementById('editOfficerId').value;
        //     const userId = document.getElementById('editUserId').value;
        //     const password = document.getElementById('editPassword')?.value;
            
        //     let userData = {
        //         first_name: document.getElementById('editFirstName').value,
        //         middle_name: document.getElementById('editMiddleName').value,
        //         last_name: document.getElementById('editLastName').value,
        //         contact_number: document.getElementById('editContactNumber').value,
        //         email: document.getElementById('editEmail').value,
        //     };

        //     if(password) {
        //         if (password.length < 8) {
        //             showToast('Password must be at least 8 characters', 'error');
        //             return;
        //         }
        //         userData.password = password;
        //     }
            
        //     fetch(`/admin/users/${userId}`, {
        //         method: 'PUT',
        //         headers: {
        //             'Content-Type': 'application/json',
        //             'X-CSRF-TOKEN': csrfToken,
        //             'Accept': 'application/json'
        //         },
        //         body: JSON.stringify(userData)
        //     })
        //     .then(r => {
        //         if (!r.ok) {
        //             return r.json().then(err => { throw err; });
        //         }
        //         return r.json();
        //     })
        //     .then(data => {
        //         if (!data.success && data.message !== 'No changes to update') {
        //             showToast(data.message || 'Error updating user', 'error');
        //             return;
        //         }
                
        //         const officerData = {
        //             rank: document.getElementById('editRank').value,
        //             status: document.getElementById('editStatus').value,
        //             station_id: document.getElementById('editStationId').value
        //         };
        //         return fetch(`/admin/officers/${officerId}`, {
        //             method: 'POST',
        //             headers: {
        //                 'X-CSRF-TOKEN': csrfToken,
        //                 'X-HTTP-Method-Override': 'PUT',
        //                 'Content-Type': 'application/json'
        //             },
        //             body: JSON.stringify(officerData)
        //         });
        //     })
        //     .then(response => {
        //         if (response) return response.json();
        //     })
        //     .then(data => {
        //         if (data && data.success) {
        //             showToast('Officer updated successfully');
        //             setTimeout(() => location.reload(), 1500);
        //         } else if (data) {
        //             showToast(data.message || 'Error updating officer', 'error');
        //         }
        //     })
        //     .catch(error => {
        //         showToast('Error updating officer', 'error');
        //     });
            
        //     closeEditModal();
        // });
    document.getElementById('editOfficerForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const officerId = document.getElementById('editOfficerId').value;
    const userId = document.getElementById('editOfficerUserId').value;
    const password = document.getElementById('editOfficerPassword')?.value;
    
    let userData = {
        first_name: document.getElementById('editOfficerFirstName').value,
        middle_name: document.getElementById('editOfficerMiddleName').value,
        last_name: document.getElementById('editOfficerLastName').value,
        contact_number: document.getElementById('editOfficerContactNumber').value,
        email: document.getElementById('editOfficerEmail').value,
    };

    if(password && password.length > 0) {
        if (password.length < 8) {
            showToast('Password must be at least 8 characters', 'error');
            return;
        }
        userData.password = password;
    }
    
    fetch(`/admin/users/${userId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(r => {
        if (!r.ok) {
            return r.json().then(err => { throw err; });
        }
        return r.json();
    })
    .then(data => {
        if (!data.success && data.message !== 'No changes to update') {
            showToast(data.message || 'Error updating user', 'error');
            return;
        }
        
        const officerData = {
            rank: document.getElementById('editOfficerRank').value,
            status: document.getElementById('editOfficerStatus').value,
            station_id: document.getElementById('editOfficerStationId').value
        };
        return fetch(`/admin/officers/${officerId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-HTTP-Method-Override': 'PUT',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(officerData)
        });
    })
    .then(response => {
        if (response) return response.json();
    })
    .then(data => {
        if (data && data.success) {
            showToast('Officer updated successfully');
            setTimeout(() => location.reload(), 1500);
        } else if (data) {
            showToast(data.message || 'Error updating officer', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating officer', 'error');
    });
    
    closeEditModal();
});
        document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const userId = document.getElementById('editUserId').value;
            const password = document.getElementById('editPassword')?.value;
            
            let userData = {
                first_name: document.getElementById('editFirstName').value,
                middle_name: document.getElementById('editMiddleName').value,
                last_name: document.getElementById('editLastName').value,
                contact_number: document.getElementById('editContactNumber').value,
                email: document.getElementById('editEmail').value,
                role_id: document.getElementById('editRoleId').value,
                email_verified_at: document.getElementById('editVerifiedStatus').value === 'verified' ? new Date().toISOString() : null,
                province_id: document.getElementById('editProvince').value || null,
                city_id: document.getElementById('editCity').value || null,
                barangay_id: document.getElementById('editBarangay').value || null,
                street: document.getElementById('editStreet').value || null
            };

            if(password && password.length > 0) {
                if (password.length < 8) {
                    showToast('Password must be at least 8 characters', 'error');
                    return;
                }
                userData.password = password;
            }
            
            fetch(`/admin/users/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('User updated successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error updating user', 'error');
                }
            })
            .catch(error => {
                showToast('Error updating user', 'error');
            });
            
            closeEditUserModal();
        });

        document.getElementById('eform')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/admin/equipment', { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success) { 
                    showToast('Equipment added'); 
                    setTimeout(() => location.reload(), 1500); 
                } else { 
                    showToast(d.message || 'Error', 'error'); 
                } 
            });
            closeModal('equipmentItemModal');
        });

        

        document.getElementById('equipmentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/admin/equipment-category', { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success) { 
                    showToast('Category added'); 
                    setTimeout(() => location.reload(), 1500); 
                } else { 
                    showToast(d.message || 'Error', 'error'); 
                } 
            });
            closeModal('equipmentCategoryModal');
        });

        document.getElementById('stationEquipmentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/admin/equipment/assign', { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success) { 
                    showToast('Equipment assigned'); 
                    setTimeout(() => location.reload(), 1500); 
                } else { 
                    showToast(d.message || 'Error', 'error'); 
                } 
            });
            closeModal('stationEquipmentModal');
        });

        document.getElementById('registerUserForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(this.action, { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                } 
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success || d.redirect) { 
                    showToast('User registered successfully'); 
                    setTimeout(() => location.reload(), 1500); 
                } else { 
                    showToast('Error registering user', 'error'); 
                } 
            });
            closeModal('registerUserModal');
        });



        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('searchInput');
            const status = document.getElementById('statusFilter');
            const station = document.getElementById('stationFilter');
            const refresh = document.getElementById('refreshBtn');
            const roleFilter = document.getElementById('userRoleFilter');
            const sortFilter = document.getElementById('userSortFilter');
            
            if (search) search.addEventListener('input', filterAll);
            if (status) status.addEventListener('change', filterAll);
            if (station) station.addEventListener('change', filterAll);
            if (roleFilter) roleFilter.addEventListener('change', filterUsers);
            if (sortFilter) sortFilter.addEventListener('change', sortUsers);
            if (refresh) refresh.addEventListener('click', () => location.reload());

            // Station tabs
            const tabs = document.querySelectorAll('#stationTabs .tab-btn');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    filterAll();
                });
            });

            // Add dropdown
            const dropdown = document.getElementById('addDropdown');
            const dropdownBtn = document.getElementById('addDropdownBtn');
            if (dropdownBtn) {
                dropdownBtn.addEventListener('click', (e) => { 
                    e.stopPropagation(); 
                    dropdown.classList.toggle('show'); 
                });
                document.addEventListener('click', () => dropdown.classList.remove('show'));
                document.querySelectorAll('.add-dropdown-content a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const modalId = link.dataset.modal;
                        const modal = document.getElementById(modalId);
                        if (modal) { 
                            modal.style.display = 'flex'; 
                            modal.classList.add('active'); 
                        }
                        dropdown.classList.remove('show');
                    });
                });
            }

            // Export button
            document.getElementById('exportBtn')?.addEventListener('click', () => showToast('Export feature - implement CSV export', 'info'));

            // Sidebar
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

            // Province/City/Barangay cascade for registration modal
            const regProvince = document.getElementById('regProvince');
            const regCity = document.getElementById('regCity');
            const regBarangay = document.getElementById('regBarangay');
            
            if (regProvince) {
                regProvince.addEventListener('change', function() {
                    const provinceId = this.value;
                    regCity.disabled = true;
                    regCity.innerHTML = '<option value="">Select City</option>';
                    regBarangay.disabled = true;
                    regBarangay.innerHTML = '<option value="">Select Barangay</option>';
                    
                    if (provinceId) {
                        fetch(`/api/cities/${provinceId}`)
                            .then(response => response.json())
                            .then(cities => {
                                regCity.disabled = false;
                                regCity.innerHTML = '<option value="">Select City</option>';
                                cities.forEach(city => {
                                    regCity.innerHTML += `<option value="${city.city_id}">${city.city_name}</option>`;
                                });
                            });
                    }
                });
            }
            
            if (regCity) {
                regCity.addEventListener('change', function() {
                    const cityId = this.value;
                    regBarangay.disabled = true;
                    regBarangay.innerHTML = '<option value="">Select Barangay</option>';
                    
                    if (cityId) {
                        fetch(`/api/barangays/${cityId}`)
                            .then(response => response.json())
                            .then(barangays => {
                                regBarangay.disabled = false;
                                regBarangay.innerHTML = '<option value="">Select Barangay</option>';
                                barangays.forEach(barangay => {
                                    regBarangay.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                                });
                            });
                    }
                });
            }

            // Province/City/Barangay cascade for edit modal
            const editProvince = document.getElementById('editProvince');
            const editCity = document.getElementById('editCity');
            const editBarangay = document.getElementById('editBarangay');
            
            if (editProvince) {
                editProvince.addEventListener('change', function() {
                    const provinceId = this.value;
                    editCity.disabled = true;
                    editCity.innerHTML = '<option value="">Select City</option>';
                    editBarangay.disabled = true;
                    editBarangay.innerHTML = '<option value="">Select Barangay</option>';
                    
                    if (provinceId) {
                        fetch(`/api/cities/${provinceId}`)
                            .then(response => response.json())
                            .then(cities => {
                                editCity.disabled = false;
                                editCity.innerHTML = '<option value="">Select City</option>';
                                cities.forEach(city => {
                                    editCity.innerHTML += `<option value="${city.city_id}">${city.city_name}</option>`;
                                });
                            });
                    }
                });
            }
            
            if (editCity) {
                editCity.addEventListener('change', function() {
                    const cityId = this.value;
                    editBarangay.disabled = true;
                    editBarangay.innerHTML = '<option value="">Select Barangay</option>';
                    
                    if (cityId) {
                        fetch(`/api/barangays/${cityId}`)
                            .then(response => response.json())
                            .then(barangays => {
                                editBarangay.disabled = false;
                                editBarangay.innerHTML = '<option value="">Select Barangay</option>';
                                barangays.forEach(barangay => {
                                    editBarangay.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                                });
                            });
                    }
                });
            }
        });

    // Store officers data by station
let officersByStation = {};
let currentTeamOfficers = {
    team_leader_id: null,
    driver_id: null,
    pumper_id: null,
    officer1_id: null,
    officer2_id: null,
    officer3_id: null,
    rescue_operator_id: null
};

// Load officers data on page load
function loadOfficersData() {
    const stationOfficers = window.resourcesData.stationOfficers || [];
    const existingTeams = window.resourcesData.allTeam || [];
    
    console.log('existing teams: ', existingTeams);
    // Create a Set of user_ids that are already in any team
    const officersInTeams = new Set();
    existingTeams.forEach(team => {
        if (team.team_leader) officersInTeams.add(parseInt(team.team_leader.user_id));
        if (team.commander_driver) officersInTeams.add(parseInt(team.commander_driver));
        if (team.pumper) officersInTeams.add(parseInt(team.pumper));
        if (team.firefighter_officer_1) officersInTeams.add(parseInt(team.firefighter_officer_1));
        if (team.firefighter_officer_2) officersInTeams.add(parseInt(team.firefighter_officer_2));
        if (team.firefighter_officer_3) officersInTeams.add(parseInt(team.firefighter_officer_3));
        if (team.rescue_radio) officersInTeams.add(parseInt(team.rescue_radio));
    });
    
    console.log('Officers already in teams:', Array.from(officersInTeams));
    
    // Clear existing data
    officersByStation = {};
    
    // Group officers by station_id
    stationOfficers.forEach(officer => {
        const stationId = officer.station_id;
        if (!stationId) return;
        
        if (!officersByStation[stationId]) {
            officersByStation[stationId] = [];
        }
        
        const userData = officer.user || {};
        const userId = officer.user_id || userData.user_id;
        
        // Skip officers already in a team
        if (officersInTeams.has(parseInt(userId))) {
            console.log(`Skipping officer ${userId} - already in a team`);
            return;
        }
        
        const officerData = {
            user_id: userId,
            first_name: userData.first_name || '',
            middle_name: userData.middle_name || '',
            last_name: userData.last_name || '',
            account_code: userData.account_code || '',
            rank: officer.rank || '',
            full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
            display_name: `${userData.first_name || ''} ${userData.last_name || ''} (${userData.account_code || 'N/A'}) - ${officer.rank || 'No Rank'}`
        };
        
        if (officerData.user_id) {
            officersByStation[stationId].push(officerData);
        }
    });
    
    console.log('Available officers by station:', officersByStation);
}

// Get available officers excluding selected ones
function getAvailableOfficers(stationId, excludeUserIds = []) {
    const allOfficers = officersByStation[stationId] || [];
    const excludeSet = new Set(excludeUserIds.filter(id => id && id !== ''));
    
    return allOfficers.filter(officer => !excludeSet.has(parseInt(officer.user_id)));
}

// Update all select dropdowns based on current selections
function updateAllSelects(stationId, currentSelections) {
    const selectIds = [
        { id: 'teamLeaderSelect', field: 'team_leader' },
        { id: 'driverSelect', field: 'commander_driver' },
        { id: 'pumperSelect', field: 'pumper' },
        { id: 'officer1Select', field: 'firefighter_officer_1' },
        { id: 'officer2Select', field: 'firefighter_officer_2' },
        { id: 'officer3Select', field: 'firefighter_officer_3' },
        { id: 'rescueOperatorSelect', field: 'rescue_radio' }
    ];
    
    // For each select, get available officers excluding all other selected values
    selectIds.forEach(selectInfo => {
        const select = document.getElementById(selectInfo.id);
        if (!select) return;
        
        // Get currently selected value for this field
        const currentValue = currentSelections[selectInfo.field];
        
        // Get all other selected values to exclude
        const excludeValues = Object.values(currentSelections).filter((val, index) => {
            const fieldName = Object.keys(currentSelections)[index];
            return fieldName !== selectInfo.field && val && val !== '';
        });
        
        // Get available officers
        const availableOfficers = getAvailableOfficers(stationId, excludeValues);
        
        // Save current scroll position
        const scrollTop = select.scrollTop;
        
        // Clear and repopulate
        select.innerHTML = '<option value="">Select ' + getSelectLabel(selectInfo.id) + '</option>';
        
        availableOfficers.forEach(officer => {
            const option = document.createElement('option');
            option.value = officer.user_id;
            option.textContent = officer.display_name;
            if (currentValue && parseInt(currentValue) === parseInt(officer.user_id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        // Restore scroll position
        select.scrollTop = scrollTop;
        
        // Disable if no officers available
        select.disabled = availableOfficers.length === 0 && !currentValue;
    });
}

// Populate selects based on selected station
function populateStationOfficers(stationId) {
    console.log('Populating data for station:', stationId);
    const officers = officersByStation[stationId] || [];
    
    if (officers.length === 0) {
        console.log('No available officers found for this station');
        showToast('No available officers for this station. Some officers may already be assigned to other teams.', 'warning');
        resetTeamForm();
        return;
    }
    
    // Reset current selections
    currentTeamOfficers = {
        team_leader_id: null,
        driver_id: null,
        pumper_id: null,
        officer1_id: null,
        officer2_id: null,
        officer3_id: null,
        rescue_operator_id: null
    };
    
    // Update all selects
    updateAllSelects(stationId, currentTeamOfficers);
    
    console.log('Populated successfully');
}

// Handle selection change for any dropdown
function onOfficerSelectChange(fieldName, selectElement) {
    const stationId = document.getElementById('teamStationSelect')?.value;
    if (!stationId) return;
    
    // Update current selections
    currentTeamOfficers[fieldName] = selectElement.value || null;
    
    // Update all selects with new exclusions
    updateAllSelects(stationId, currentTeamOfficers);
    
    // Check if required fields are filled
    checkRequiredFields();
}

// Initialize event listeners for all selects
function initSelectEventListeners() {
    const selectMappings = [
        { id: 'teamLeaderSelect', field: 'team_leader' },
        { id: 'driverSelect', field: 'commander_driver' },
        { id: 'pumperSelect', field: 'pumper' },
        { id: 'officer1Select', field: 'firefighter_officer_1' },
        { id: 'officer2Select', field: 'firefighter_officer_2' },
        { id: 'officer3Select', field: 'firefighter_officer_3' },
        { id: 'rescueOperatorSelect', field: 'rescue_radio' }
    ];
    
    selectMappings.forEach(mapping => {
        const select = document.getElementById(mapping.id);
        if (select) {
            // Remove existing listener to avoid duplicates
            select.removeEventListener('change', function() {});
            select.addEventListener('change', function() {
                onOfficerSelectChange(mapping.field, this);
            });
        }
    });
}

// Reset all selects and disable them
function resetTeamForm() {
    const selectIds = ['teamLeaderSelect', 'driverSelect', 'pumperSelect', 'officer1Select', 'officer2Select', 'officer3Select', 'rescueOperatorSelect'];
    
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (select) {
            select.innerHTML = '<option value="">Select ' + getSelectLabel(id) + '</option>';
            select.disabled = true;
            select.value = '';
        }
    });
    
    // Reset current selections
    currentTeamOfficers = {
        team_leader_id: null,
        driver_id: null,
        pumper_id: null,
        officer1_id: null,
        officer2_id: null,
        officer3_id: null,
        rescue_operator_id: null
    };
    
    // Disable submit button
    const submitBtn = document.getElementById('createTeamSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
    }
}

// Check if required fields are filled (Team Leader, Driver, Pumper)
function checkRequiredFields() {
    const teamLeader = document.getElementById('teamLeaderSelect')?.value;
    const driver = document.getElementById('driverSelect')?.value;
    const pumper = document.getElementById('pumperSelect')?.value;
    const submitBtn = document.getElementById('createTeamSubmitBtn');
    
    const isEnabled = teamLeader && teamLeader !== '' && 
                      driver && driver !== '' && 
                      pumper && pumper !== '';
    
    if (submitBtn) {
        submitBtn.disabled = !isEnabled;
        submitBtn.style.opacity = isEnabled ? '1' : '0.6';
        submitBtn.style.cursor = isEnabled ? 'pointer' : 'not-allowed';
    }
    
    return isEnabled;
}

// Helper function to get select labels
function getSelectLabel(selectId) {
    const labels = {
        teamLeaderSelect: 'Team Leader',
        driverSelect: 'Driver',
        pumperSelect: 'Pumper',
        officer1Select: 'Officer 1',
        officer2Select: 'Officer 2',
        officer3Select: 'Officer 3',
        rescueOperatorSelect: 'Rescue Operator'
    };
    return labels[selectId] || 'Personnel';
}

// Station change handler
function stationChangeHandler() {
    const stationId = this.value;
    console.log('Station selected:', stationId);
    
    if (stationId && stationId !== '') {
        if (officersByStation[stationId] && officersByStation[stationId].length > 0) {
            populateStationOfficers(stationId);
            initSelectEventListeners();
        } else {
            showToast('No available officers for this station', 'warning');
            resetTeamForm();
        }
    } else {
        resetTeamForm();
    }
}

// Initialize team modal
function initTeamModal() {
    const stationSelect = document.getElementById('teamStationSelect');
    const modal = document.getElementById('teamModal');
    
    if (!stationSelect) {
        console.error('Station select not found');
        return;
    }
    
    // Load officers data first
    loadOfficersData();
    
    // Handle modal open
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (modal.classList.contains('active')) {
                        console.log('Modal opened - resetting form');
                        stationSelect.value = '';
                        resetTeamForm();
                        // Reload data to get latest team assignments
                        loadOfficersData();
                    }
                }
            });
        });
        observer.observe(modal, { attributes: true });
    }
    
    // Station change event
    stationSelect.removeEventListener('change', stationChangeHandler);
    stationSelect.addEventListener('change', stationChangeHandler);
}

// Form submission with validation
document.getElementById('createTeamForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const stationId = document.getElementById('teamStationSelect')?.value;
    const teamLeader = document.getElementById('teamLeaderSelect')?.value;
    const driver = document.getElementById('driverSelect')?.value;
    const pumper = document.getElementById('pumperSelect')?.value;
    
    if (!stationId || stationId === '') {
        showToast('Please select a station first', 'error');
        return;
    }
    
    if (!teamLeader || !driver || !pumper) {
        showToast('Team Leader, Driver, and Pumper are required fields', 'error');
        return;
    }
    
    // Validate no duplicate assignments
    const userIds = [
        teamLeader, driver, pumper,
        document.getElementById('officer1Select')?.value,
        document.getElementById('officer2Select')?.value,
        document.getElementById('officer3Select')?.value,
        document.getElementById('rescueOperatorSelect')?.value
        
    ].filter(id => id && id !== '');
    
    const uniqueIds = new Set(userIds);
    if (userIds.length !== uniqueIds.size) {
        showToast('A personnel cannot be assigned to multiple roles in the same team', 'error');
        return;
    }
    
    // Submit form
    const formData = new FormData(this);
    formData.delete('station_id'); // Remove station_id - it's NOT in your teams table
    
    console.log('Sending form data (without station_id):', [...formData.entries()]);
    console.log('form:',formData);
    console.log('Before delete:', [...formData.entries()]);
    const submitBtn = document.getElementById('createTeamSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
    submitBtn.disabled = true;
    
    fetch('/admin/teams', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,

        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Team created successfully');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message || 'Error creating team', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error creating team', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initTeamModal();

    document.querySelectorAll('.boundary-sync-form').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const button = form.querySelector('.boundary-sync-btn');
            const originalHtml = button?.innerHTML || 'Sync Boundary';

            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Syncing...';
            }

            try {
                const response = await fetch(form.dataset.route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Boundary sync failed.');
                }

                showToast(payload.message || 'Boundary synced successfully.');
                window.setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                showToast(error.message || 'Boundary sync failed.', 'error');

                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            }
        });
    });
});
    </script>
</body>
</html>
