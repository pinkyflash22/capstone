<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="station-id" content="{{ \App\Models\StationOfficer::where('user_id', auth()->id())->value('station_id') ?? '' }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <title>PyroSense · Alerts</title>
    
    <style>
        /* All your existing styles remain the same */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { 
            font-family: 'Inter', sans-serif; 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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

        .legacy-chief-sidebar .sidebar-header-content {
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

        .legacy-chief-sidebar .sidebar-nav {
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

        .legacy-chief-sidebar .nav-item {
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

        .legacy-chief-sidebar .nav-item:hover {
            background: #f3f4f6;
        }

        .legacy-chief-sidebar .nav-item.active {
            background: #fee2e2;
            color: #dc2626;
            font-weight: 500;
        }

        .legacy-chief-sidebar .nav-item i {
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-width: 0;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .top-nav {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 30;
            padding: 0 24px;
        }

        .top-nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #4b5563;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .mobile-logo {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .desktop-menu {
            display: flex;
            gap: 32px;
        }

        .desktop-menu a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s;
        }

        .desktop-menu a:hover,
        .desktop-menu a.active {
            color: #dc2626;
        }

        .top-nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .station-badge {
            color: #6b7280;
            font-size: 14px;
        }

        .emergency-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .emergency-btn:hover {
            background: #b91c1c;
        }

        /* Alert Bell */
        .alert-bell {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            color: #4b5563;
            transition: color 0.2s;
        }

        .alert-bell:hover {
            color: #dc2626;
        }

        .alert-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
        }

        /* Alerts Dropdown */
        .alerts-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            margin-top: 10px;
            z-index: 1000;
            display: none;
            border: 1px solid #e5e7eb;
        }

        .alerts-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .alerts-header {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alerts-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .alerts-header span {
            font-size: 12px;
            color: #6b7280;
        }

        .alerts-list-dropdown {
            max-height: 300px;
            overflow-y: auto;
        }

        .alert-item-dropdown {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
            cursor: pointer;
        }

        .alert-item-dropdown:hover {
            background: #f9fafb;
        }

        .alert-item-dropdown.unread {
            background: #eff6ff;
        }

        .alert-title-dropdown {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-time-dropdown {
            font-size: 12px;
            color: #6b7280;
        }

        .alerts-footer {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .alerts-footer button {
            background: none;
            border: none;
            color: #dc2626;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 24px;
            width: 100%;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .page-date {
            color: #6b7280;
            font-size: 14px;
        }

        .page-date i {
            margin-right: 6px;
            color: #dc2626;
        }

        /* Alert Banner */
        .alert-banner {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-banner i {
            color: #f59e0b;
            font-size: 18px;
            margin-top: 2px;
        }

        .alert-banner .content {
            flex: 1;
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-banner .content strong {
            font-weight: 600;
            color: #b45309;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(220,38,38,0.1);
            border-color: #dc2626;
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .kpi-title {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .kpi-icon.red { background: #fee2e2; color: #dc2626; }
        .kpi-icon.orange { background: #fff7ed; color: #f97316; }
        .kpi-icon.blue { background: #eff6ff; color: #3b82f6; }
        .kpi-icon.green { background: #ecfdf5; color: #10b981; }
        .kpi-icon.purple { background: #f3e8ff; color: #a855f7; }
        .kpi-icon.yellow { background: #fef9c3; color: #eab308; }

        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .kpi-trend {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .kpi-trend i {
            color: #10b981;
        }

        /* Map Card - Full Width */
        .map-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            width: 100%;
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .map-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .map-header h2 i {
            color: #dc2626;
        }

        .map-badge {
            background: #fee2e2;
            color: #dc2626;
            font-size: 12px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 20px;
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
            background: #f1f5f9;
            margin-bottom: 16px;
            border: 1px solid #e5e7eb;
            z-index: 0;
        }

        .map-actions {
            display: flex;
            gap: 12px;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-outline {
            background: white;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .chart-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-header h3 i {
            color: #dc2626;
        }

        .chart-container {
            height: 250px;
            position: relative;
        }

        .chart-controls {
            display: flex;
            gap: 8px;
        }

        .chart-period-btn {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chart-period-btn:hover {
            background: #e5e7eb;
        }

        .chart-period-btn.active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        /* Bottom Grid - Station Cards */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Station Card */
        .station-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .station-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .station-header i {
            color: #dc2626;
        }

        .station-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
        }

        .station-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .station-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #f3f4f6;
        }

        .station-item .name {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .station-item .stats {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .station-item .stats small {
            font-size: 12px;
            font-weight: 400;
            color: #9ca3af;
        }

        .station-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-ready {
            background: #dcfce7;
            color: #166534;
        }

        .badge-deployed {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-available {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Incident Type Distribution */
        .incident-distribution {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
        }

        .incident-type-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .type-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .type-color.fire { background: #ef4444; }
        .type-color.medical { background: #3b82f6; }
        .type-color.rescue { background: #f97316; }
        .type-color.other { background: #a855f7; }

        .type-name {
            flex: 1;
            font-size: 14px;
            color: #4b5563;
        }

        .type-count {
            font-weight: 600;
            color: #1f2937;
        }

        .type-percent {
            width: 60px;
            text-align: right;
            color: #6b7280;
            font-size: 13px;
        }

        .progress-bar-container {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
        }

        .progress-bar-fill.fire { background: #ef4444; }
        .progress-bar-fill.medical { background: #3b82f6; }
        .progress-bar-fill.rescue { background: #f97316; }
        .progress-bar-fill.other { background: #a855f7; }

        /* Emergency Stats */
        .emergency-stats {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            color: white;
            margin-bottom: 24px;
        }

        .emergency-stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
        }

        .emergency-stats-content {
            position: relative;
            z-index: 1;
        }

        .emergency-stats h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            opacity: 0.9;
        }

        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            padding: 12px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 11px;
            opacity: 0.8;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .system-status i {
            color: #4ade80;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 12px;
        }

        .dashboard-footer i {
            color: #dc2626;
            margin-right: 4px;
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
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 500px;
            max-width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .modal input,
        .modal textarea,
        .modal select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .btn-save {
            flex: 1;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-cancel {
            flex: 1;
            background: #e5e7eb;
            color: #4b5563;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Station Fields Styles */
        .station-fields {
            display: none;
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e5e7eb;
            animation: slideDown 0.3s ease;
        }

        .station-fields.show {
            display: block;
        }

        .station-fields h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .station-fields h4 i {
            color: #dc2626;
        }

        .station-fields .form-row {
            margin-bottom: 16px;
        }

        .station-fields label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .station-fields label i {
            color: #dc2626;
            margin-right: 6px;
        }

        .station-fields input,
        .station-fields select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .station-fields input:focus,
        .station-fields select:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .station-fields input[readonly] {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .station-code-preview {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
            text-align: center;
        }

        .station-code-preview span {
            font-size: 12px;
            opacity: 0.9;
        }

        .station-code-preview strong {
            font-size: 18px;
            font-family: monospace;
            letter-spacing: 1px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .bottom-grid {
                grid-template-columns: 1fr;
            }
            
            .desktop-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .mobile-logo {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .legacy-chief-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .legacy-chief-sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .top-nav-container {
                padding: 0 16px;
            }
            
            .station-badge {
                display: none;
            }
            
            #map {
                height: 350px;
            }
        }

        .fire-icon {
            animation: flicker 3s infinite;
        }
        
        @keyframes flicker {
            0%, 100% { opacity: 1; }
            33% { opacity: 0.8; transform: scale(1.02); }
            66% { opacity: 0.9; }
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/js/map.js'])
</head>
<body>
    <!-- SIDE NAVIGATION -->
    @include('chief.partials.sidebar', ['active' => 'dashboard'])
    <aside class="sidebar legacy-chief-sidebar" id="legacySidebar" style="display:none;">
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item ">
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
                <a href="{{ route('admin.management') }}" class="nav-item">
                    <i class="fa-solid fa-table"></i>
                    <span>Management</span>
                </a>
                <a href="#" class="nav-item ">
                    <i class="fa-solid fa-toolbox"></i>
                    <span>Resources</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <a href="" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Predictive Analytics</span>
                </a>
                <a href="#" class="nav-item ">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item active">
                    <i class="fa-solid fa-bell"></i>
                    <span>Alerts</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Resources</div>
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
    <main class="main-content">

        <!-- DASHBOARD CONTENT -->
        <div class="dashboard-container">
            <!-- page title + date -->
            <div class="page-header">
                <h1></h1>
                <div class="page-date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

<div id="section-alerts" class="content-section">
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Alerts</h1>
            <p class="text-sm text-gray-500 mt-1">Stay informed about incidents and emergencies in your area</p>
        </div>
        <div class="flex items-center gap-3 mt-3 sm:mt-0">
            <span class="bg-orange-100 text-orange-700 text-sm px-4 py-2 rounded-full flex items-center gap-2">
                <i class="fa-solid fa-bell"></i> 
                {{ $incidents->where('incident_status_id', 1)->count() }} Active Incidents
            </span>
        </div>
    </div>

    <!-- Alert Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Fire Alerts</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $incidents->where('incident_status_id', 1)->where('incident_type_id', 1)->count() }}
                    </p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-fire"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Require immediate attention</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Medical Alerts</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $incidents->where('incident_status_id', 1)->where('incident_type_id', 2)->count() }}
                    </p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-ambulance"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Medical emergencies</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Rescue Alerts</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $incidents->where('incident_status_id', 1)->where('incident_type_id', 3)->count() }}
                    </p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Rescue operations</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Resolved Today</p>
                    <p class="text-2xl font-bold text-gray-800">
                        @php
                            $today = \Carbon\Carbon::today();
                            $resolvedToday = $incidents->filter(function($incident) use ($today) {
                                return $incident->incident_status_id == 3 && 
                                       \Carbon\Carbon::parse($incident->created_at)->isToday();
                            })->count();
                        @endphp
                        {{ $resolvedToday }}
                    </p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Incidents cleared</p>
        </div>
    </div>

    <!-- Alert Filters and Search -->
    <div class="bg-white rounded-xl shadow mb-8">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="font-bold text-gray-800">Active Alerts ({{ $incidents->where('incident_status_id', '!=', 3)->count() }})</h2>
                    <div class="flex gap-2">
                        <button class="filter-btn active px-4 py-2 text-sm rounded-lg bg-orange-600 text-white" data-filter="all">All</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="1">Fire</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="2">Medical</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="3">Rescue</button>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-initial">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" placeholder="Search alerts..." class="pl-10 pr-4 py-2 border rounded-lg text-sm w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-orange-500" id="alertSearch">
                    </div>
                    <select class="text-sm border rounded-lg px-3 py-2 bg-white" id="alertSort">
                        <option value="newest">Sort by: Newest</option>
                        <option value="oldest">Sort by: Oldest</option>
                        <option value="type">Sort by: Type</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Alerts List -->
        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto" id="alertsList">
            @php
                $activeIncidents = $incidents->where('incident_status_id', '!=', 3)->sortByDesc('created_at');
            @endphp
            
            @forelse($activeIncidents as $incident)
            @php
                // Get incident type name based on incident_type_id
                $incidentTypeName = '';
                $incidentIcon = '⚠️';
                $incidentColor = 'bg-yellow-500';
                
                if($incident->incident_type_id == 1) {
                    $incidentTypeName = 'Fire';
                    $incidentIcon = '🔥';
                    $incidentColor = 'bg-red-500';
                } elseif($incident->incident_type_id == 2) {
                    $incidentTypeName = 'Medical Emergency';
                    $incidentIcon = '🚑';
                    $incidentColor = 'bg-blue-500';
                } elseif($incident->incident_type_id == 3) {
                    $incidentTypeName = 'Rescue';
                    $incidentIcon = '🆘';
                    $incidentColor = 'bg-green-500';
                } else {
                    $incidentTypeName = 'Other';
                    $incidentIcon = '⚠️';
                    $incidentColor = 'bg-yellow-500';
                }
                
                // Status name based on incident_status_id
                $statusName = '';
                $statusColor = '';
                if($incident->incident_status_id == 1) {
                    $statusName = 'Reported';
                    $statusColor = 'bg-red-100 text-red-700';
                } elseif($incident->incident_status_id == 2) {
                    $statusName = 'Responding';
                    $statusColor = 'bg-yellow-100 text-yellow-700';
                } elseif($incident->incident_status_id == 3) {
                    $statusName = 'Resolved';
                    $statusColor = 'bg-green-100 text-green-700';
                } else {
                    $statusName = 'Unknown';
                    $statusColor = 'bg-gray-100 text-gray-700';
                }
            @endphp
            <div class="p-5 hover:bg-gray-50 transition alert-item" data-type="{{ $incident->incident_type_id }}" data-date="{{ $incident->created_at }}" data-id="{{ $incident->incident_id }}" data-lat="{{ $incident->marker->lat ?? '' }}" data-lng="{{ $incident->marker->lng ?? '' }}">
                <div class="flex items-start gap-4">
                    <span class="w-2 h-2 {{ $incidentColor }} rounded-full mt-2"></span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-semibold text-gray-800">
                                {{ $incidentIcon }} {{ $incidentTypeName }} Incident
                            </h3>
                            <span class="text-xs {{ $statusColor }} px-2 py-1 rounded-full">
                                {{ $statusName }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">
                            @if($incident->marker && $incident->marker->description)
                                {{ $incident->marker->description }}
                            @else
                                No additional details provided.
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                            <span><i class="fa-regular fa-clock mr-1"></i> {{ $incident->created_at->diffForHumans() }}</span>
                            @if($incident->reported_by)
                            <span><i class="fa-regular fa-user mr-1"></i> Reported by: {{ $incident->reported_by }}</span>
                            @endif
                            <span id="location-{{ $incident->incident_id }}" class="location-span">
                                @if($incident->marker && $incident->marker->lat && $incident->marker->lng)
                                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading address...
                                @else
                                    <i class="fa-solid fa-map-pin mr-1"></i> Location pending
                                @endif
                            </span>
                            <span><i class="fa-regular fa-flag mr-1"></i> 
                                @if($incident->incident_status_id == 1) Awaiting response
                                @elseif($incident->incident_status_id == 2) Crews dispatched
                                @elseif($incident->incident_status_id == 3) Resolved
                                @else {{ $statusName }}
                                @endif
                            </span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-2 mt-3">
                            <button class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full hover:bg-orange-200 transition" onclick="showIncidentDetailsModal({{ $incident->incident_id }})">
                                <i class="fa-regular fa-eye mr-1"></i> View Details
                            </button>
                            <button class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition" onclick="shareAlert({{ $incident->incident_id }})">
                                <i class="fa-regular fa-share-from-square mr-1"></i> Share
                            </button>
                            @if($incident->marker && $incident->marker->lat && $incident->marker->lng)
                            <button class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 transition" onclick="viewOnMap({{ $incident->marker->lat }}, {{ $incident->marker->lng }})">
                                <i class="fa-regular fa-map mr-1"></i> View on Map
                            </button>
                            @endif
                            
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-regular fa-bell-slash text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Active Alerts</h3>
                <p class="text-sm text-gray-500 mb-4">There are no active incidents in your area at the moment.</p>
                <button class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-orange-700 transition" onclick="openIncidentModal()">
                    <i class="fa-regular fa-flag mr-2"></i>Report an Incident
                </button>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($activeIncidents->count() > 0)
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Showing <span class="font-medium" id="showingStart">1</span> to <span class="font-medium" id="showingEnd">{{ min(10, $activeIncidents->count()) }}</span> of <span class="font-medium">{{ $activeIncidents->count() }}</span> alerts
            </p>
            <div class="flex gap-2">
                <button class="px-3 py-1 border rounded-lg text-sm bg-white hover:bg-gray-50 pagination-prev" disabled>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="px-3 py-1 border rounded-lg text-sm bg-white hover:bg-gray-50 pagination-next">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- MY REPORTS HISTORY SECTION -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-orange-600"></i>
                    My Reports History
                </h2>
                <span class="text-sm text-gray-500">Total Reports: {{ $userReports ?? 0 }}</span>
            </div>
        </div>

        @php
            $currentUserAccountId = Auth::user()->user_id ?? null;
            $userReportedIncidents = $incidents->where('reported_by', $currentUserAccountId)->sortByDesc('created_at');
        @endphp

        @if($userReportedIncidents->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Incident Type</th>
                            <th class="px-6 py-3">Location</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($userReportedIncidents as $report)
                        @php
                            $reportTypeName = '';
                            $reportIcon = '⚠️';
                            if($report->incident_type_id == 1) {
                                $reportTypeName = 'Fire';
                                $reportIcon = '🔥';
                            } elseif($report->incident_type_id == 2) {
                                $reportTypeName = 'Medical Emergency';
                                $reportIcon = '🚑';
                            } elseif($report->incident_type_id == 3) {
                                $reportTypeName = 'Rescue';
                                $reportIcon = '🆘';
                            } else {
                                $reportTypeName = 'Other';
                                $reportIcon = '⚠️';
                            }
                            
                            $reportStatusName = '';
                            $reportStatusClass = '';
                            if($report->incident_status_id == 1) {
                                $reportStatusName = 'Reported';
                                $reportStatusClass = 'bg-red-100 text-red-700';
                            } elseif($report->incident_status_id == 2) {
                                $reportStatusName = 'Responding';
                                $reportStatusClass = 'bg-yellow-100 text-yellow-700';
                            } elseif($report->incident_status_id == 3) {
                                $reportStatusName = 'Resolved';
                                $reportStatusClass = 'bg-green-100 text-green-700';
                            } else {
                                $reportStatusName = 'Unknown';
                                $reportStatusClass = 'bg-gray-100 text-gray-700';
                            }
                        @endphp
                        <tr class="bg-white border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $report->created_at->format('M d, Y') }}
                                <span class="text-xs text-gray-400 block">{{ $report->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="flex items-center gap-1">
                                    {{ $reportIcon }} {{ $reportTypeName }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($report->marker && $report->marker->lat && $report->marker->lng)
                                    <span id="location-report-{{ $report->incident_id }}" class="text-xs location-span">
                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading...
                                    </span>
                                @else
                                    <span class="text-gray-400">Location pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs {{ $reportStatusClass }}">
                                    {{ $reportStatusName }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-orange-600 hover:text-orange-700 font-medium text-xs mr-2" onclick="showIncidentDetailsModal({{ $report->incident_id }})">
                                    <i class="fa-regular fa-eye mr-1"></i> View
                                </button>
                                @if($report->incident_status_id == 1)
                                <button class="text-red-600 hover:text-red-700 font-medium text-xs" onclick="cancelReport({{ $report->incident_id }}, {{ $report->marker_id }})">
                                    <i class="fa-regular fa-trash-can mr-1"></i> Cancel
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Reports Summary -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-orange-600">{{ $userReportedIncidents->where('incident_status_id', 1)->count() }}</p>
                        <p class="text-xs text-gray-500">Pending Reports</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-yellow-600">{{ $userReportedIncidents->where('incident_status_id', 2)->count() }}</p>
                        <p class="text-xs text-gray-500">In Progress</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $userReportedIncidents->where('incident_status_id', 3)->count() }}</p>
                        <p class="text-xs text-gray-500">Resolved</p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-regular fa-file-lines text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Reports Yet</h3>
                <p class="text-sm text-gray-500 mb-4">You haven't reported any incidents yet.</p>
                <button class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-orange-700 transition" onclick="openIncidentModal()">
                    <i class="fa-regular fa-flag mr-2"></i>Report Your First Incident
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Incident Details Modal -->
<div id="incidentDetailsModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h2 class="text-xl font-bold">Incident Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="incidentDetailsContent">
            <!-- Content will be populated dynamically -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <button class="btn-outline" onclick="closeDetailsModal()">Close</button>
            <button class="btn-primary" onclick="shareCurrentIncident()">Share</button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f3f4f6;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 1.5rem;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #4b5563;
}

.modal-body {
    margin-bottom: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 16px;
    border-top: 2px solid #f3f4f6;
}

.filter-btn {
    transition: all 0.2s ease;
}

.filter-btn:hover:not(.active) {
    background-color: #e5e7eb !important;
}

.btn-outline {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-primary {
    padding: 8px 16px;
    background: #f97316;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #ea580c;
}

.btn-danger {
    padding: 8px 16px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger:hover {
    background: #b91c1c;
}

.location-span {
    max-width: 250px;
    display: inline-block;
    white-space: normal;
    word-break: break-word;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #f97316;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Store incidents data
const incidentsData = @json($incidents);
let currentIncidentId = null;

// Incident type mapping
const incidentTypeMap = {
    1: { name: 'Fire', icon: '🔥', color: 'red' },
    2: { name: 'Medical Emergency', icon: '🚑', color: 'blue' },
    3: { name: 'Rescue', icon: '🆘', color: 'green' }
};

// Status mapping
const statusMap = {
    1: { name: 'Reported', class: 'bg-red-100 text-red-700', canCancel: true },
    2: { name: 'Responding', class: 'bg-yellow-100 text-yellow-700', canCancel: false },
    3: { name: 'Resolved', class: 'bg-green-100 text-green-700', canCancel: false }
};

// Format address - show FULL address
function formatAddress(fullAddress) {
    if (!fullAddress) return 'Location unknown';
    return fullAddress;
}

// Load address for a single incident
// async function loadAddressForIncident(incidentId, lat, lng, isReport = false) {
//     const spanId = isReport ? `location-report-${incidentId}` : `location-${incidentId}`;
//     const locationSpan = document.getElementById(spanId);
    
//     if (!locationSpan) return;
    
//     try {
//         if (typeof window.setThisName === 'function') {
//             const address = await window.setThisName(lat, lng);
//             if (address && address !== '' && address !== 'undefined') {
//                 locationSpan.innerHTML = `<i class="fa-solid fa-location-dot mr-1"></i> ${address}`;
//                 locationSpan.title = address;
//                 return;
//             }
//         }
        
//         locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
        
//     } catch (error) {
//         console.error(`Error loading address:`, error);
//         locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
//     }
// }
// Load address for a single incident
async function loadAddressForIncident(incidentId, lat, lng, isReport = false) {
    const spanId = isReport ? `location-report-${incidentId}` : `location-${incidentId}`;
    const locationSpan = document.getElementById(spanId);
    
    if (!locationSpan) return;
    
    // Ensure lat and lng are numbers
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    
    if (isNaN(numLat) || isNaN(numLng)) {
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> Invalid coordinates`;
        return;
    }
    
    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(numLat, numLng);
            if (address && address !== '' && address !== 'undefined') {
                locationSpan.innerHTML = `<i class="fa-solid fa-location-dot mr-1"></i> ${address}`;
                locationSpan.title = address;
                return;
            }
        }
        
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
        
    } catch (error) {
        console.error(`Error loading address:`, error);
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    }
}
// Load all addresses
async function loadAllAddresses() {
    for (const incident of incidentsData) {
        if (incident.marker && incident.marker.lat && incident.marker.lng && incident.incident_status_id !== 3) {
            await loadAddressForIncident(incident.incident_id, incident.marker.lat, incident.marker.lng, false);
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    const currentUserId = {{ Auth::user()->user_id ?? 'null' }};
    const userReports = incidentsData.filter(i => i.reported_by === currentUserId);
    
    for (const report of userReports) {
        if (report.marker && report.marker.lat && report.marker.lng) {
            await loadAddressForIncident(report.incident_id, report.marker.lat, report.marker.lng, true);
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
}

// View on map - Google Maps
// function viewOnMap(lat, lng) {
//     window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
// }
// View on map - Google Maps
function viewOnMap(lat, lng) {
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    
    if (isNaN(numLat) || isNaN(numLng)) {
        alert('Invalid coordinates');
        return;
    }
    
    window.open(`https://www.google.com/maps?q=${numLat},${numLng}`, '_blank');
}
// Cancel Report Function
async function cancelReport(incidentId, markerId) {
    if (!confirm('Are you sure you want to cancel this report? This action cannot be undone.')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    try {
        // Show loading state on the button that was clicked
        const buttons = document.querySelectorAll(`[onclick*="cancelReport(${incidentId}"]`);
        buttons.forEach(btn => {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Cancelling...';
            btn.disabled = true;
        });
        
        // First, update incident status to cancelled/resolved (or delete)
        const incidentResponse = await fetch(`/api/incidents/${incidentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                incident_status_id: 3, // Resolved/Cancelled
                status: 'cancelled'
            })
        });
        
        if (!incidentResponse.ok) {
            throw new Error('Failed to cancel incident');
        }
        
        // Also delete or update the marker if needed
        if (markerId) {
            await fetch(`/api/markers/${markerId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
        }
        
        alert('Report cancelled successfully!');
        
        // Reload the page to refresh the list
        location.reload();
        
    } catch (error) {
        console.error('Error cancelling report:', error);
        alert('Failed to cancel report. Please try again.');
        
        // Reset buttons
        const buttons = document.querySelectorAll(`[onclick*="cancelReport(${incidentId}"]`);
        buttons.forEach(btn => {
            btn.innerHTML = '<i class="fa-regular fa-trash-can mr-1"></i> Cancel';
            btn.disabled = false;
        });
    }
}

// Show incident details modal
// function showIncidentDetailsModal(incidentId) {
//     currentIncidentId = incidentId;
//     const incident = incidentsData.find(i => i.incident_id === incidentId);
    
//     if (!incident) return;
    
//     const typeInfo = incidentTypeMap[incident.incident_type_id] || { name: 'Other', icon: '⚠️', color: 'gray' };
//     const statusInfo = statusMap[incident.incident_status_id] || { name: 'Unknown', class: 'bg-gray-100 text-gray-700', canCancel: false };
    
//     // Build modal content
//     let content = `
//         <div class="space-y-4">
//             <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
//                 <div class="w-12 h-12 bg-${typeInfo.color}-100 rounded-full flex items-center justify-center">
//                     <span class="text-2xl">${typeInfo.icon}</span>
//                 </div>
//                 <div>
//                     <h3 class="font-semibold text-lg">${typeInfo.name} Incident</h3>
//                     <p class="text-sm text-gray-500">ID: #${incident.incident_id}</p>
//                 </div>
//             </div>
            
//             <div class="grid grid-cols-2 gap-4">
//                 <div>
//                     <p class="text-xs text-gray-500 mb-1">Status</p>
//                     <p class="font-medium">
//                         <span class="px-3 py-1 rounded-full text-xs ${statusInfo.class}">
//                             ${statusInfo.name}
//                         </span>
//                     </p>
//                 </div>
//                 <div>
//                     <p class="text-xs text-gray-500 mb-1">Reported</p>
//                     <p class="font-medium text-sm">${new Date(incident.created_at).toLocaleString()}</p>
//                 </div>
//                 <div>
//                     <p class="text-xs text-gray-500 mb-1">Location</p>
//                     <p class="font-medium text-sm" id="modal-location-${incident.incident_id}">
//                         ${incident.marker ? `${incident.marker.lat.toFixed(6)}, ${incident.marker.lng.toFixed(6)}` : 'Pending'}
//                     </p>
//                 </div>
//                 <div>
//                     <p class="text-xs text-gray-500 mb-1">Reported By</p>
//                     <p class="font-medium text-sm">${incident.reported_by || 'Anonymous'}</p>
//                 </div>
//             </div>
            
//             ${incident.marker && incident.marker.description ? `
//             <div class="bg-gray-50 p-4 rounded-lg">
//                 <p class="text-xs text-gray-500 mb-1">Description</p>
//                 <p class="text-sm text-gray-700">${incident.marker.description}</p>
//             </div>
//             ` : ''}
            
//             ${incident.marker && incident.marker.image ? `
//             <div class="bg-gray-50 p-4 rounded-lg">
//                 <p class="text-xs text-gray-500 mb-1">Attached Image</p>
//                 <img src="${incident.marker.image}" class="w-full rounded-lg mt-2" alt="Incident image">
//             </div>
//             ` : ''}
            
//             ${incident.incident_status_id !== 3 ? `
//             <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
//                 <div class="flex items-start gap-3">
//                     <i class="fa-solid fa-circle-exclamation text-yellow-600 mt-1"></i>
//                     <div>
//                         <p class="font-medium text-yellow-700">Safety Advisory</p>
//                         <p class="text-sm text-yellow-600">Please avoid the area if possible. Emergency crews are responding.</p>
//                     </div>
//                 </div>
//             </div>
//             ` : ''}
            
//             ${incident.marker && incident.marker.lat && incident.marker.lng ? `
//             <div class="flex gap-2">
//                 <button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition" onclick="viewOnMap(${incident.marker.lat}, ${incident.marker.lng})">
//                     <i class="fa-regular fa-map mr-1"></i> Open in Google Maps
//                 </button>
//             </div>
//             ` : ''}
//         </div>
//     `;
    
//     document.getElementById('incidentDetailsContent').innerHTML = content;
    
//     // Update modal footer based on if user can cancel
//     const modalFooter = document.getElementById('modalFooter');
//     const currentUserId = {{ Auth::user()->user_id ?? 'null' }};
    
//     if (incident.reported_by == currentUserId && incident.incident_status_id == 1) {
//         modalFooter.innerHTML = `
//             <button class="btn-outline" onclick="closeDetailsModal()">Close</button>
//             <button class="btn-danger" onclick="cancelReportFromModal(${incident.incident_id}, ${incident.marker_id})">
//                 <i class="fa-regular fa-trash-can mr-1"></i> Cancel Report
//             </button>
//             <button class="btn-primary" onclick="shareCurrentIncident()">Share</button>
//         `;
//     } else {
//         modalFooter.innerHTML = `
//             <button class="btn-outline" onclick="closeDetailsModal()">Close</button>
//             <button class="btn-primary" onclick="shareCurrentIncident()">Share</button>
//         `;
//     }
    
//     openModal('incidentDetailsModal');
    
//     // Load full address for modal
//     if (incident.marker && incident.marker.lat && incident.marker.lng) {
//         loadAddressForModal(incident.incident_id, incident.marker.lat, incident.marker.lng);
//     }
// }
// Show incident details modal
function showIncidentDetailsModal(incidentId) {
    currentIncidentId = incidentId;
    const incident = incidentsData.find(i => i.incident_id === incidentId);
    
    if (!incident) return;
    
    const typeInfo = incidentTypeMap[incident.incident_type_id] || { name: 'Other', icon: '⚠️', color: 'gray' };
    const statusInfo = statusMap[incident.incident_status_id] || { name: 'Unknown', class: 'bg-gray-100 text-gray-700', canCancel: false };
    
    // Helper function to format coordinates safely
    function formatCoord(coord) {
        if (coord === null || coord === undefined) return 'N/A';
        const num = parseFloat(coord);
        return isNaN(num) ? coord : num.toFixed(6);
    }
    
    // Get coordinates safely
    const lat = incident.marker?.lat ? formatCoord(incident.marker.lat) : 'N/A';
    const lng = incident.marker?.lng ? formatCoord(incident.marker.lng) : 'N/A';
    const locationText = (lat !== 'N/A' && lng !== 'N/A') ? `${lat}, ${lng}` : 'Pending';
    
    // Build modal content
    let content = `
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 bg-${typeInfo.color}-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">${typeInfo.icon}</span>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">${typeInfo.name} Incident</h3>
                    <p class="text-sm text-gray-500">ID: #${incident.incident_id}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Status</p>
                    <p class="font-medium">
                        <span class="px-3 py-1 rounded-full text-xs ${statusInfo.class}">
                            ${statusInfo.name}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Reported</p>
                    <p class="font-medium text-sm">${new Date(incident.created_at).toLocaleString()}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Location</p>
                    <p class="font-medium text-sm" id="modal-location-${incident.incident_id}">
                        ${locationText}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Reported By</p>
                    <p class="font-medium text-sm">${incident.reported_by || 'Anonymous'}</p>
                </div>
            </div>
            
            ${incident.marker && incident.marker.description ? `
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">${escapeHtml(incident.marker.description)}</p>
            </div>
            ` : ''}
            
            ${incident.marker && incident.marker.image ? `
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">Attached Image</p>
                <img src="${incident.marker.image}" class="w-full rounded-lg mt-2" alt="Incident image" onerror="this.style.display='none'">
            </div>
            ` : ''}
            
            ${incident.incident_status_id !== 3 ? `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-yellow-600 mt-1"></i>
                    <div>
                        <p class="font-medium text-yellow-700">Safety Advisory</p>
                        <p class="text-sm text-yellow-600">Please avoid the area if possible. Emergency crews are responding.</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${incident.marker && incident.marker.lat && incident.marker.lng ? `
            <div class="flex gap-2">
                <button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition" onclick="viewOnMap(${parseFloat(incident.marker.lat)}, ${parseFloat(incident.marker.lng)})">
                    <i class="fa-regular fa-map mr-1"></i> Open in Google Maps
                </button>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('incidentDetailsContent').innerHTML = content;
    
    // Update modal footer based on if user can cancel
    const modalFooter = document.getElementById('modalFooter');
    const currentUserId = {{ Auth::user()->user_id ?? 'null' }};
    
    if (incident.reported_by == currentUserId && incident.incident_status_id == 1) {
        modalFooter.innerHTML = `
            <button class="btn-outline" onclick="closeDetailsModal()">Close</button>
            <button class="btn-danger" onclick="cancelReportFromModal(${incident.incident_id}, ${incident.marker_id || 0})">
                <i class="fa-regular fa-trash-can mr-1"></i> Cancel Report
            </button>
            <button class="btn-primary" onclick="shareCurrentIncident()">Share</button>
        `;
    } else {
        modalFooter.innerHTML = `
            <button class="btn-outline" onclick="closeDetailsModal()">Close</button>
            <button class="btn-primary" onclick="shareCurrentIncident()">Share</button>
        `;
    }
    
    openModal('incidentDetailsModal');
    
    // Load full address for modal
    if (incident.marker && incident.marker.lat && incident.marker.lng) {
        loadAddressForModal(incident.incident_id, 
            parseFloat(incident.marker.lat), 
            parseFloat(incident.marker.lng));
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
// Load address for modal
async function loadAddressForModal(incidentId, lat, lng) {
    const locationSpan = document.getElementById(`modal-location-${incidentId}`);
    if (!locationSpan) return;
    
    // Ensure lat and lng are numbers
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    
    if (isNaN(numLat) || isNaN(numLng)) {
        locationSpan.innerHTML = 'Invalid coordinates';
        return;
    }
    
    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(numLat, numLng);
            if (address && address !== '') {
                locationSpan.innerHTML = address;
                locationSpan.title = address;
            } else {
                locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
            }
        } else {
            locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
        }
    } catch (error) {
        console.error('Error loading address for modal:', error);
        locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    }
}
// Cancel report from modal
async function cancelReportFromModal(incidentId, markerId) {
    closeDetailsModal();
    await cancelReport(incidentId, markerId);
}

// Load address for modal
async function loadAddressForModal(incidentId, lat, lng) {
    const locationSpan = document.getElementById(`modal-location-${incidentId}`);
    if (!locationSpan) return;
    
    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(lat, lng);
            if (address && address !== '') {
                locationSpan.innerHTML = address;
                locationSpan.title = address;
            }
        }
    } catch (error) {
        console.error('Error loading address for modal:', error);
    }
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
    document.getElementById('incidentDetailsModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    currentIncidentId = null;
}

function shareCurrentIncident() {
    const incident = incidentsData.find(i => i.incident_id === currentIncidentId);
    if (incident) {
        shareAlert(currentIncidentId);
    }
}

function shareAlert(incidentId) {
    const incident = incidentsData.find(i => i.incident_id === incidentId);
    const typeInfo = incidentTypeMap[incident.incident_type_id] || { name: 'Incident', icon: '⚠️' };
    const statusInfo = statusMap[incident.incident_status_id] || { name: 'Active' };
    
    if (incident) {
        const shareData = {
            title: `Emergency Alert: ${typeInfo.name} Incident`,
            text: `There is an active ${typeInfo.name} incident. Status: ${statusInfo.name}. Location: ${incident.marker ? `${incident.marker.lat}, ${incident.marker.lng}` : 'Unknown'}`,
            url: window.location.href
        };
        
        if (navigator.share) {
            navigator.share(shareData).catch(() => {});
        } else {
            // Copy to clipboard
            navigator.clipboard.writeText(`${shareData.title}\n${shareData.text}\n\nLink: ${shareData.url}`);
            alert('Alert details copied to clipboard!');
        }
    }
}

// Filter and search functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('alertSearch');
    const sortSelect = document.getElementById('alertSort');
    let currentPage = 1;
    const itemsPerPage = 10;
    
    setTimeout(() => {
        loadAllAddresses();
    }, 1000);
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'bg-orange-600', 'text-white');
                b.classList.add('bg-gray-100', 'text-gray-600');
            });
            this.classList.add('active', 'bg-orange-600', 'text-white');
            this.classList.remove('bg-gray-100', 'text-gray-600');
            
            const filter = this.dataset.filter;
            filterAlerts(filter, searchInput?.value || '');
        });
    });
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
            filterAlerts(activeFilter, this.value);
            currentPage = 1;
            updatePagination();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const alertList = document.getElementById('alertsList');
            const items = Array.from(document.querySelectorAll('.alert-item'));
            
            items.sort((a, b) => {
                if (this.value === 'newest') {
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                } else if (this.value === 'oldest') {
                    return new Date(a.dataset.date) - new Date(b.dataset.date);
                } else if (this.value === 'type') {
                    return parseInt(a.dataset.type) - parseInt(b.dataset.type);
                }
                return 0;
            });
            
            items.forEach(item => alertList.appendChild(item));
            currentPage = 1;
            updatePagination();
        });
    }
    
    function updatePagination() {
        const visibleItems = Array.from(document.querySelectorAll('.alert-item')).filter(item => item.style.display !== 'none');
        const totalPages = Math.ceil(visibleItems.length / itemsPerPage);
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        document.querySelectorAll('.alert-item').forEach(item => {
            if (item.style.display !== 'none') {
                item.style.display = 'none';
            }
        });
        
        visibleItems.slice(start, end).forEach(item => {
            item.style.display = 'block';
        });
        
        const showingStart = visibleItems.length > 0 ? start + 1 : 0;
        const showingEnd = Math.min(end, visibleItems.length);
        document.getElementById('showingStart').textContent = showingStart;
        document.getElementById('showingEnd').textContent = showingEnd;
        
        const prevBtn = document.querySelector('.pagination-prev');
        const nextBtn = document.querySelector('.pagination-next');
        
        if (prevBtn) {
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                }
            };
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.onclick = () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                }
            };
        }
    }
    
    function filterAlerts(type, searchTerm = '') {
        const alertItems = document.querySelectorAll('.alert-item');
        let visibleCount = 0;
        
        alertItems.forEach(item => {
            const itemType = item.dataset.type;
            const itemText = item.textContent.toLowerCase();
            const matchesType = type === 'all' || itemType === type;
            const matchesSearch = searchTerm === '' || itemText.includes(searchTerm.toLowerCase());
            
            if (matchesType && matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        currentPage = 1;
        updatePagination();
        
        const alertsList = document.getElementById('alertsList');
        const existingNoResult = alertsList.querySelector('.no-result-message');
        
        if (visibleCount === 0 && !existingNoResult) {
            const noResultDiv = document.createElement('div');
            noResultDiv.className = 'p-8 text-center no-result-message';
            noResultDiv.innerHTML = `
                <i class="fa-solid fa-search text-gray-400 text-4xl mb-3"></i>
                <p class="text-gray-500">No alerts match your search criteria</p>
            `;
            alertsList.appendChild(noResultDiv);
        } else if (visibleCount > 0 && existingNoResult) {
            existingNoResult.remove();
        }
    }
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }
});

// Expose functions globally
window.showIncidentDetailsModal = showIncidentDetailsModal;
window.shareAlert = shareAlert;
window.viewOnMap = viewOnMap;
window.cancelReport = cancelReport;
window.cancelReportFromModal = cancelReportFromModal;
window.closeDetailsModal = closeDetailsModal;
window.shareCurrentIncident = shareCurrentIncident;
</script>



  
       

      

    
            <!-- footer -->
            <div class="dashboard-footer">
                <i class="fa-solid fa-shield"></i> PyroSense · secured emergency dashboard · live data
            </div>
        </div>
    </main>
@include('chief.partials.logout-confirmation')
</body>
</html>
