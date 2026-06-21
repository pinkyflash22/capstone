<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    {{-- <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'citizen' }}">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? 4 }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <title>PyroSense · Dashboard</title>
    
    @include('partials.pwa-head')
    <style>
        /* All your existing styles remain the same */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

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
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }
        /* Add to your existing styles in dashboard.blade.php */
@keyframes blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

.fire-truck-marker {
    transition: all 0.1s linear;
    z-index: 1000;
}

.route-progress-container {
    margin-top: 10px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    height: 6px;
}

.route-progress-bar {
    height: 100%;
    background: #3b82f6;
    transition: width 0.3s ease;
}

.speed-control {
    margin-top: 8px;
    display: flex;
    gap: 8px;
}

.speed-btn {
    padding: 4px 8px;
    font-size: 11px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    cursor: pointer;
}

.speed-btn.active {
    background: #3b82f6;
    color: white;
}

.stop-sim-btn {
    width: 100%;
    padding: 6px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 8px;
}
        /* Sidebar Styles */
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-width: 0;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: calc(24px + var(--safe-bottom));
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

        .dashboard-welcome-toast__copy strong {
            display: block;
            color: #9a3412;
            font-size: 0.92rem;
            line-height: 1.2;
            font-weight: 800;
        }

        .dashboard-welcome-toast__copy span {
            display: block;
            color: #b45309;
            font-size: 0.76rem;
            line-height: 1.35;
            margin-top: 3px;
        }

        .lector-top-bar {
            border-radius: 28px;
        }

        .user-dropdown-toggle {
            border-radius: 999px;
        }

        .chief-dashboard-welcome,
        .dashboard-hidden-panel {
            display: none !important;
        }

        .main-content .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .main-content .kpi-grid > * {
            min-width: 0;
        }

        .main-content .kpi-card {
            min-height: 92px;
            min-width: 0;
            padding: 14px 16px;
            gap: 12px;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 18px;
        }

        .main-content .kpi-info h3 {
            font-size: 11px;
            letter-spacing: 0.08em;
        }

        .main-content .kpi-info p {
            font-size: 20px;
            line-height: 1.05;
        }

        @media (max-width: 1180px) {
            .main-content .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .main-content .kpi-grid {
                grid-template-columns: 1fr;
            }
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
            .dashboard-welcome-toast {
                top: 82px;
                left: 12px;
                right: 12px;
                min-width: 0;
                max-width: none;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
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
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/app.css', 'resources/js/map.js'])
    @vite(['resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])

</head>
<body class="chief-dashboard-page">
    <!-- SIDE NAVIGATION -->
    @include('admin.partials.sidebar', ['active' => 'dashboard'])
    {{-- <aside class="sidebar" id="sidebar">
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item active">
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
                <a href="{{ route('admin.resource-management.index') }}" class="nav-item ">
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
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>History</span>
                </a>
                <a href="{{ route('admin.alerts') }}" class="nav-item">
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
    </aside> --}}

    <!-- BACKDROP for mobile -->
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
                    <p class="text-xs text-slate-500 font-medium">Monitoring Command Center • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Notifications -->
                <div class="hidden md:block relative">
                    <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition relative">
                        <i class="fa-regular fa-bell"></i>
                        @if($alerts->count() > 0)
                        <span class="absolute top-2 right-2 w-2 h-2 bg-amber-500 rounded-full"></span>
                        @endif
                    </button>
                </div>

                <!-- User Profile Dropdown -->
                <div class="hidden md:block user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}
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
                            <a href="#" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#" class="user-dropdown-item">
                                <i class="fa-regular fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                            <div class="h-px bg-slate-100 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- DASHBOARD CONTENT -->
        <div class="dashboard-container">
            <div id="adminDashboardWelcomeToast" class="dashboard-welcome-toast" role="status" aria-live="polite">
                <div class="dashboard-welcome-toast__icon">
                    <i class="fa-solid fa-signal"></i>
                </div>
                <div class="dashboard-welcome-toast__copy">
                    <strong>Welcome back, {{ auth()->user()->first_name }}!</strong>
                    <span>Command overview is ready. Recent analytics are loaded below.</span>
                </div>
            </div>

            <div class="chief-dashboard-welcome bg-amber-50 border-l-4 border-amber-500 rounded-2xl p-8 mb-8 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-amber-900 mb-2">Welcome back, {{ auth()->user()->first_name }}!</h1>
                    <p class="text-amber-700 font-medium">System Command Overview. {{ now()->format('l, F j, Y') }}</p>
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

            <div id="announcements" class="lector-card mb-8 scroll-mt-24">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-100">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Latest Announcements</h2>
                            <p class="text-xs text-slate-500 font-medium">Recent system-wide posts from command</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.announcements') }}" class="text-xs font-semibold text-amber-700 hover:text-amber-800">Open full announcements</a>
                </div>
                <div class="grid gap-4">
                    @forelse ($posts as $post)
                        <article class="rounded-2xl border border-slate-100 bg-slate-50/70 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800">{{ $post->title }}</h3>
                                    <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? $post->body ?? ''), 140) }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-500 border border-slate-200">
                                    {{ optional($post->created_at)->diffForHumans() }}
                                </span>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No announcements published yet.
                        </div>
                    @endforelse
                </div>
            </div>
            <!-- page title + date -->
            <div id="dashboard" class="page-header scroll-mt-24">
                <h1>Operations Dashboard</h1>
                <div class="page-date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- emergency banner -->
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-2xl p-6 mb-8 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0 border border-amber-200">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-amber-900 font-bold text-lg mb-1">Operational Alert</h4>
                    <p class="text-amber-700 leading-relaxed font-medium">Red flag warning active in Sector 4. All primary response units in Sector 4 are on heightened readiness. Crews are advised to monitor weather patterns in real-time.</p>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="kpi-grid">
                <div class="lector-card kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Active Incidents</h3>
                        <p>{{ $incidents->count() }}</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Units Deployed</h3>
                        <p>12</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);">
                        <i class="fa-solid fa-water"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Weather Status</h3>
                        <p id="weather-result" class="capitalize">Rainy</p>
                    </div>
                </div>

                <div class="lector-card kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Avg. Response</h3>
                        <p>3.2min</p>
                    </div>
                </div>
            </div>

            @php
                $analysisTrendSeries = collect($recentAnalyses ?? [])
                    ->sortBy('created_at')
                    ->values()
                    ->map(function ($analysis) {
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

            <!-- Full Width Map -->
            <div id="management" class="lector-card mb-8 scroll-mt-24">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Operational Map</h2>
                            <p class="text-xs text-slate-500 font-medium">Real-time incident & resources tracking</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-blue-100 text-blue-600 text-[10px] font-bold rounded-full uppercase tracking-wider border border-blue-200">System Ready</span>
                </div>
   
                <div id="map"></div>

                {{-- <div id="unifiedMap" style="height: 500px; width: 100%;"></div> --}}
                <div class="map-actions">
                    <button class="btn-outline" id="addCategory" onclick="openAddCategory()">
                        <i class="fa-regular fa-layer-group"></i> Add Category
                    </button>
                </div>
            </div>

            <!-- Charts Grid -->
            <div id="analytics" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 scroll-mt-24">
                <!-- Incident History Graph -->
                <div class="lector-card">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 uppercase tracking-widest text-[11px]">System Analytics</h3>
                                <p class="text-lg font-bold text-slate-800">Incident Trends</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
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
                <div id="reports" class="chart-card scroll-mt-24">
                    <div class="chart-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-brain" style="color: #dc2626;"></i>
                            <h3>ML Trend Analysis</h3>
                        </div>
                        <span class="text-sm text-gray-500">{{ $analysisTrendSeries->count() }} saved model run(s)</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="analysisTrendChart"></canvas>
                    </div>
                    <div class="grid grid-cols-3 gap-3 mt-4">
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
            <div class="charts-grid dashboard-hidden-panel">
                <!-- Incident Status Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #dc2626;"></i>
                            <h3>Incident Status</h3>
                        </div>
                    </div>
                    
                    @php
                        $reportedCount = $incidents->where('status', 'reported')->count();
                        $respondingCount = $incidents->where('status', 'responding')->count();
                        $resolvedCount = $incidents->where('status', 'resolved')->count();
                        $completedCount = $incidents->where('status', 'completed')->count();
                        
                        $totalActive = $reportedCount + $respondingCount;
                    @endphp
                    
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-xs text-gray-500">Active</span>
                            <p class="text-2xl font-bold text-orange-600">{{ $totalActive }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-xs text-gray-500">Resolved</span>
                            <p class="text-2xl font-bold text-green-600">{{ $resolvedCount + $completedCount }}</p>
                        </div>
                    </div>
                </div>

                <!-- Hourly Incident Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-clock" style="color: #dc2626;"></i>
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
                    
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                    
                    <div class="flex justify-between mt-3 text-xs text-gray-500">
                        <span>00:00</span>
                        <span>06:00</span>
                        <span>12:00</span>
                        <span>18:00</span>
                        <span>23:00</span>
                    </div>
                </div>
            </div>

            <!-- bottom grid: station status -->
            <div class="bottom-grid dashboard-hidden-panel">
                <!-- station readiness -->
                <div class="station-card">
                    <div class="station-header">
                        <i class="fa-solid fa-user"></i>
                        <h3>Stations Readiness</h3>
                    </div>
                    <div class="station-grid">
                      
                        @foreach ($stations as $station )
                         @php
                            $count = 0;
                            $available = 0;
                        @endphp
                            @foreach ($stationperson as $person )
                                @php
                                    if($station->station_id == $person->station_id){
                                        $count++;
                                        if($person->status == 'active' || $person -> status === 'active'){
                                            $available++;
                                        }
                                    }
                                    
                                @endphp
                            @endforeach
                       
                        <div class="station-item">
                            <div class="name">{{ $station->station_name }}</div>
                            <div class="stats">{{ $available}} / {{ $count}} <small>units</small></div>
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
                            <span class="text-2xl font-bold text-blue-700">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Equipment Card (replacing Today's Operational Summary) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-brain"></i>
                                Previous Analyses
                            </h2>
                            <p class="text-amber-50 text-sm mt-1">Latest saved AI forecasts across the command system.</p>
                        </div>
                        <a href="{{ route('admin.analytics') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/25 transition">
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
                        @endphp
                        <div class="mb-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-orange-50/40 p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                            {{ $analysis->sector ?? 'System Analysis' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold
                                            @if(strtolower($analysis->risk_level ?? '') === 'high' || strtolower($analysis->risk_level ?? '') === 'critical') bg-red-100 text-red-700
                                            @elseif(strtolower($analysis->risk_level ?? '') === 'medium') bg-amber-100 text-amber-700
                                            @else bg-emerald-100 text-emerald-700 @endif">
                                            {{ $analysis->risk_level ?? 'Unknown Risk' }}
                                        </span>
                                    </div>
                                    <h3 class="mt-3 text-lg font-bold text-slate-800">{{ $prediction['prediction_context']['target_period_label'] ?? ($prediction['forecast_window'] ?? ($analysis->period ?? 'Analysis Window')) }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">Saved {{ \Carbon\Carbon::parse($analysis->created_at)->format('M d, Y h:i A') }}</p>
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
                                <p class="mt-4 text-sm leading-6 text-slate-600">{{ $predictionSummary ?: 'No prediction summary available for this analysis.' }}</p>
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

            <div id="resources" class="dashboard-hidden-panel bg-white rounded-xl shadow-lg overflow-hidden mb-6 scroll-mt-24">
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
            <div class="dashboard-footer">
                <i class="fa-solid fa-shield"></i> PyroSense · secured emergency dashboard · live data
            </div>
        </div>

        <!-- Modals (same as before) -->
        <!-- Modals -->
<div id="markerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Add Map Marker</h3>
        
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
                @foreach ($categories as $category)
                    <option value="{{ $category->markerCategory_id }}" data-category-name="{{ $category->marker_category }}">
                        {{ $category->marker_category }}
                    </option>
                @endforeach
            </select>
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
            <option value="">Select Station</option>
            @foreach($stations as $station)
                <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="form-row">
        <label><i class="fa-solid fa-tag"></i> Hydrant Type</label>
        <select id="hydrantType" class="w-full">
            <option value="">Select Hydrant Type</option>
            @foreach($hydrantTypes ?? [] as $type)
                <option value="{{ $type->hydrant_type_id }}">{{ $type->type }}</option>
            @endforeach
        </select>
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
        
        <div class="modal-actions">
            <button class="btn-save" id="saveMarkerBtn">Save Marker</button>
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>
        {{-- <div id="markerModal" class="modal" style="display: none;">
            <div class="modal-content">
                <h3>Add Map Marker</h3>
                
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
                        @foreach ($categories as $category)
                            <option value="{{ $category->markerCategory_id }}" data-category-name="{{ $category->marker_category }}">
                                {{ $category->marker_category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Station Fields (Initially Hidden) -->
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
                        <label><i class="fa-solid fa-user-tie"></i> Station Chief</label>
                        <select id="stationChief" class="w-full">
                            <option value="">Select Chief</option>
                            @foreach($chiefs as $chief)
                                <option value="{{ $chief->user_id }}">{{ $chief->full_name }} ({{ $chief->account_code }})</option>
                            @endforeach
                        </select>
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
                
                <div class="modal-actions">
                    <button class="btn-save" id="saveMarkerBtn">Save Marker</button>
                    <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div> --}}

        <div id="updateModal" style="display:none;">
            <form id="editForm" action="{{ route('markers.update', 0) }}" method="post">
                @csrf
                @method('PUT')
                <input type="hidden" id="markerid" name="marker_id">
                <input type="text" id="editTitle" name="title">
                <textarea id="editDesc" name="description"></textarea>
                <select id="editCat" name="markerCategory_id">
                    @foreach ($categories as $category)
                        <option value="{{ $category->markerCategory_id }}">
                            {{ $category->marker_category }}
                        </option>
                    @endforeach
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
        </div>

        <div id="categoryModal" class="modal">
            <div class="modal-content">
                <h3>Add Category</h3>
                <input type="text" class="border p-2 w-full mb-4" name="category" id="marker_category" placeholder="e.g., hydrant, station">
                <div class="modal-actions">
                    <button class="btn-save" type="submit" id="saveCategory">Save</button>
                    <button class="btn-cancel" onclick="closeAddCategory()">Cancel</button>
                </div>
            </div>
        </div>
        @php
            $fireCount = $incidents->filter(fn($incident) => strtolower((string) ($incident->marker->category ?? $incident->incident_type ?? '')) === 'fire')->count();
            $medicalCount = $incidents->filter(fn($incident) => str_contains(strtolower((string) ($incident->incident_type ?? '')), 'medical'))->count();
            $rescueCount = $incidents->filter(fn($incident) => str_contains(strtolower((string) ($incident->incident_type ?? '')), 'rescue'))->count();
            $otherCount = $incidents->count() - ($fireCount + $medicalCount + $rescueCount);
            $reportedCount = $incidents->where('status', 'reported')->count();
            $respondingCount = $incidents->where('status', 'responding')->count();
            $resolvedCount = $incidents->where('status', 'resolved')->count();
            $completedCount = $incidents->where('status', 'completed')->count();
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
        resolvedCount: {{ $resolvedCount }},
        completedCount: {{ $completedCount }},
        hourlyData: {{ json_encode(array_values($hourlyData)) }}
    };
    const mlTrendData = @json($analysisTrendSeries);

    document.getElementById('stationProvince').addEventListener('change', function() {
        const provinceId = this.value;
        const citySelect = document.getElementById('stationCity');
        const barangaySelect = document.getElementById('stationBarangay');
        
        if (!provinceId) {
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
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
            })
            .catch(error => {
                console.error('Error:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            });
    });

    document.getElementById('stationCity').addEventListener('change', function() {
        const cityId = this.value;
        const barangaySelect = document.getElementById('stationBarangay');
        
        if (!cityId) {
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            return;
        }
        
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`/api/barangays/${cityId}`)
            .then(response => response.json())
            .then(barangays => {
                barangaySelect.disabled = false;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.innerHTML += '<option value="" disabled>──────────</option>';
                barangays.forEach(barangay => {
                    barangaySelect.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
            });
    });
    
    var marker = @json($mapmarker) || [];
    window.mapData = {
        marker: @json($mapmarker) || []
    };
    var stations = @json($stations) || [];

    window.stationDatas = {
        stations: @json($stations) || []
    };

    let multimediaData = [];
    let icons = [];

    document.addEventListener('DOMContentLoaded', function() {
        const welcomeToast = document.getElementById('adminDashboardWelcomeToast');
        // Alert bell toggle
        const alertBell = document.getElementById('alertBell');
        const alertsDropdown = document.getElementById('alertsDropdown');
        
        if (alertBell) {
            alertBell.addEventListener('click', function(e) {
                e.stopPropagation();
                alertsDropdown.classList.toggle('show');
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!alertBell?.contains(e.target) && !alertsDropdown?.contains(e.target)) {
                alertsDropdown?.classList.remove('show');
            }
        });

        // Initialize all charts
        initializeCharts();

        if (welcomeToast) {
            welcomeToast.classList.add('is-visible');
            window.setTimeout(() => {
                welcomeToast.classList.remove('is-visible');
            }, 3200);
        }
        
        // Debug: Log available categories (remove this in production)
        console.log('Available categories:');
        const catSelect = document.getElementById('mCategory');
        if (catSelect) {
            Array.from(catSelect.options).forEach(opt => {
                if (opt.value) {
                    console.log(`ID: ${opt.value}, Name: ${opt.getAttribute('data-category-name')}, Text: ${opt.text}`);
                }
            });
        }
    });

    function initializeCharts() {
        // Incident History Chart
        const periodBtns = document.querySelectorAll('.chart-period-btn');
        const ctx = document.getElementById('incidentChart')?.getContext('2d');
        let currentChart = null;

        if (ctx) {
            function loadChartData(period) {
                fetch(`/admin/incidents?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (currentChart) {
                            currentChart.destroy();
                        }
                        
                        const labels = data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        const values = data.values || [5, 8, 12, 7, 9, 15, 10];
                        
                        currentChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Number of Incidents',
                                    data: values,
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
                    })
                    .catch(error => {
                        console.error('Error loading chart data:', error);
                        // Fallback data if API fails
                        if (currentChart) currentChart.destroy();
                        currentChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                                datasets: [{
                                    label: 'Number of Incidents',
                                    data: [5, 8, 12, 7, 9, 15, 10],
                                    borderColor: '#dc2626',
                                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.3,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        });
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

        // Status Chart
        const statusCtx = document.getElementById('statusChart')?.getContext('2d');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: ['Reported', 'Responding', 'Resolved', 'Completed'],
                    datasets: [{
                        data: [chartData.reportedCount, chartData.respondingCount, chartData.resolvedCount, chartData.completedCount],
                        backgroundColor: ['#ef4444', '#f97316', '#22c55e', '#3b82f6'],
                        borderRadius: 6,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
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

        // Hourly Chart
        const hourlyCtx = document.getElementById('hourlyChart')?.getContext('2d');
        if (hourlyCtx) {
            const hours = Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0') + ':00');
            new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: hours.filter((_, i) => i % 3 === 0),
                    datasets: [{
                        label: 'Incidents by Hour',
                        data: chartData.hourlyData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { precision: 0 }
                        }
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
            const response = await fetch('/api/equipment/with-stations');
            if (!response.ok) throw new Error('Failed to fetch equipment');
            
            const equipment = await response.json();
            displayEquipment(equipment);
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
        
        container.innerHTML = equipment.map(item => `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-hard-hat text-blue-600"></i>
                            <h3 class="font-semibold text-gray-800">${escapeHtml(item.equipment_name)}</h3>
                            <span class="text-xs px-2 py-1 bg-gray-100 rounded-full">${escapeHtml(item.category || 'Uncategorized')}</span>
                        </div>
                        
                        <!-- Station-wise breakdown -->
                        <div class="space-y-2 mt-3">
                            ${item.stations && item.stations.length > 0 ? item.stations.map(station => `
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">
                                            <i class="fas fa-building mr-1"></i>${escapeHtml(station.station_name)}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="bg-green-50 rounded-lg p-2">
                                            <div class="text-green-600 font-bold text-lg">${station.available_quantity || 0}</div>
                                            <div class="text-xs text-gray-600">Available</div>
                                        </div>
                                        <div class="bg-yellow-50 rounded-lg p-2">
                                            <div class="text-yellow-600 font-bold text-lg">${station.maintenance_quantity || 0}</div>
                                            <div class="text-xs text-gray-600">Maintenance</div>
                                        </div>
                                        <div class="bg-red-50 rounded-lg p-2">
                                            <div class="text-red-600 font-bold text-lg">${station.damaged_quantity || 0}</div>
                                            <div class="text-xs text-gray-600">Damaged</div>
                                        </div>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="text-sm text-gray-500 italic">No stations have this equipment</div>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function refreshEquipment() {
        loadEquipment();
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
    
    // Function to check selected category and show appropriate fields
    function checkCategoryFields() {
        if (categorySelect) {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption?.getAttribute('data-category-name') || '';
            
            console.log('Selected category:', categoryName);
            
            // Hide all field groups first
            if (stationFields) stationFields.classList.remove('show');
            if (hydrantFields) hydrantFields.classList.remove('show');
            if (schoolFields) schoolFields.classList.remove('show');
            
            // Remove required attributes from all
            if (document.getElementById('stationName')) document.getElementById('stationName').required = false;
            if (document.getElementById('stationCode')) document.getElementById('stationCode').required = false;
            if (document.getElementById('stationContact')) document.getElementById('stationContact').required = false;
            if (document.getElementById('stationEmail')) document.getElementById('stationEmail').required = false;
            if (document.getElementById('hydrantCode')) document.getElementById('hydrantCode').required = false;
            if (document.getElementById('hydrantStation')) document.getElementById('hydrantStation').required = false;
            if (document.getElementById('schoolName')) document.getElementById('schoolName').required = false;
            
            // Check for Fire Station
            if (categoryName.toLowerCase() === 'fire station') {
                console.log('Showing station fields');
                if (stationFields) stationFields.classList.add('show');
                if (document.getElementById('stationName')) document.getElementById('stationName').required = true;
                if (document.getElementById('stationCode')) document.getElementById('stationCode').required = true;
                if (document.getElementById('stationContact')) document.getElementById('stationContact').required = true;
                if (document.getElementById('stationEmail')) document.getElementById('stationEmail').required = true;
            }
            // Check for Fire Hydrant
            else if (categoryName.toLowerCase() === 'hydrant') {
                console.log('Showing hydrant fields');
                if (hydrantFields) hydrantFields.classList.add('show');
                if (document.getElementById('hydrantCode')) document.getElementById('hydrantCode').required = true;
                if (document.getElementById('hydrantStation')) document.getElementById('hydrantStation').required = true;
            }
            // Check for School
            else if (categoryName.toLowerCase() === 'school') {
                console.log('Showing school fields');
                if (schoolFields) schoolFields.classList.add('show');
                if (document.getElementById('schoolName')) document.getElementById('schoolName').required = true;
            }
        }
    }
    
    // Add event listener to category select
    if (categorySelect) {
        categorySelect.addEventListener('change', checkCategoryFields);
    }
    
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
            document.getElementById('alertsDropdown')?.classList.remove('show');
        }
    }

    function markAllAsRead() {
        const badge = document.getElementById('alertBadge');
        if (badge) badge.textContent = '0';
        document.querySelectorAll('.alert-item-dropdown').forEach(item => {
            item.classList.remove('unread');
        });
        document.getElementById('alertsDropdown')?.classList.remove('show');
    }

    function openModal() {
        const modal = document.getElementById('markerModal');
        if (modal) modal.style.display = 'flex';
        
        // Reset all fields when opening modal
        if (stationFields) stationFields.classList.remove('show');
        if (hydrantFields) hydrantFields.classList.remove('show');
        if (schoolFields) schoolFields.classList.remove('show');
        
        // Clear all inputs
        const inputs = modal.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.id !== 'mCategory') {
                input.value = '';
            }
        });
        
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

    function openAddCategory() {
        const modal = document.getElementById('categoryModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeAddCategory() {
        const modal = document.getElementById('categoryModal');
        if (modal) modal.style.display = 'none';
    }

    function closeModal() {
        const modal = document.getElementById('markerModal');
        if (modal) modal.style.display = 'none';
    }

    function emergencyCall() {
        if (confirm('Call 911 for emergency services?')) {
            window.location.href = 'tel:911';
        }
    }

    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const openBtn = document.getElementById('openSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (openBtn) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('open');
                backdrop.classList.remove('hidden');
                document.body.classList.add('menu-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
            });
        }
        // User dropdown toggle
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function() {
                userDropdownMenu.classList.remove('show');
            });

            userDropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
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
</script>
<!-- Add this at the end of your dashboard.blade.php, before closing body tag -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

{{-- @vite(['resources/js/map/UnifiedMapService.js']) --}}

<script>
    // Store existing map reference for UnifiedMapService
    window.unifiedMap = null;
    
    // Initialize UnifiedMapService AFTER your existing map is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for your existing map to be initialized
        const checkMap = setInterval(function() {
            if (window.map && window.map._container) {
                clearInterval(checkMap);
                console.log('Existing map found, initializing UnifiedMapService...');
                
                // Create UnifiedMapService instance
                window.unifiedMap = new UnifiedMapService();
                
                // Setup on existing map
                window.unifiedMap.setupOnExistingMap();
                
                console.log('UnifiedMapService integrated with existing map');
            }
        }, 1000);
    });
    
    // Make route calculation functions globally available
    window.calculateRouteToStation = function(stationId, stationName, lat, lng) {
        if (window.unifiedMap) {
            window.unifiedMap.calculateRouteToStation(stationId, stationName, lat, lng);
        }
    };
    
    window.calculateRouteToIncident = function(incidentId) {
        if (window.unifiedMap) {
            window.unifiedMap.calculateRouteToIncident(incidentId);
        }
    };
    
    window.stopSimulation = function() {
        if (window.unifiedMap) {
            window.unifiedMap.stopSimulation();
        }
    };
</script>
@include('partials.pwa-body', ['showPwaInstallButton' => true])
</body>
</html>
