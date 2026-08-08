<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'station_admin' }}">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    
    <title>PyroSense · Fire Risk Forecast</title>
    
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
        .recommendation-run-list { display: grid; gap: 12px; }
        .recommendation-run-group {
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
        }
        .recommendation-run-toggle {
            width: 100%;
            border: none;
            background: linear-gradient(180deg, #fff, #fffaf5);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            text-align: left;
            cursor: pointer;
        }
        .recommendation-run-toggle:hover { background: linear-gradient(180deg, #fffaf5, #fff7ed); }
        .recommendation-run-meta { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .recommendation-run-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .recommendation-run-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }
        .recommendation-run-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .recommendation-run-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
        }
        .recommendation-run-arrow {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: #fff7ed;
            color: #c2410c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 160ms ease;
        }
        .recommendation-run-group.is-open .recommendation-run-arrow { transform: rotate(180deg); }
        .recommendation-run-content {
            display: none;
            padding: 0 16px 16px;
            border-top: 1px solid #f1f5f9;
            background: #fff;
        }
        .recommendation-run-group.is-open .recommendation-run-content { display: block; }
        .recommendation-run-summary {
            padding: 12px 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }
        
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

        #exploreModal,
        #predictionDetailModal {
            padding: 18px;
        }
        .analytics-forecast-modal {
            width: min(88vw, 680px);
            max-height: min(74vh, 640px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
        }
        #exploreModal .modal-content.analytics-forecast-modal {
            width: min(92vw, 560px) !important;
            max-width: 560px !important;
            max-height: min(78vh, 560px) !important;
        }
        #exploreModal .modal-body {
            padding: 0.9rem 1rem !important;
        }
        #exploreModal .modal-footer button {
            min-width: 100px;
        }
        #exploreModalBody[data-state="loading"],
        #exploreModalBody[data-state="error"] {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        #exploreModalBody[data-state="ready"] {
            display: block;
            min-height: 0;
        }
        .analytics-google-symbol {
            font-family: "Material Symbols Rounded" !important;
            font-weight: normal;
            font-style: normal;
            font-size: 1.1em;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            font-variation-settings:
                "FILL" 1,
                "wght" 500,
                "GRAD" 0,
                "opsz" 24;
        }
        .analytics-forecast-modal--details {
            width: min(82vw, 620px);
            max-height: min(70vh, 580px);
        }
        .analytics-forecast-modal--details .modal-body {
            padding: 0.88rem 0.92rem;
        }
        .analytics-forecast-modal--details .modal-body [style*="padding:18px"],
        .analytics-forecast-modal--details .modal-body [style*="padding:16px"],
        .analytics-forecast-modal--details .modal-body [style*="padding:14px"] {
            padding: 10px !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:28px"] {
            font-size: 1.16rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:24px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:22px"] {
            font-size: 1rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:16px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:15px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:14px"] {
            font-size: 0.8rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="min-width:220px"] {
            min-width: 0 !important;
        }
        .analytics-forecast-modal .modal-header {
            flex: 0 0 auto;
            padding: 0.9rem 1rem;
        }
        .analytics-forecast-modal .modal-header h3 {
            font-size: 0.98rem;
            line-height: 1.25;
        }
        .analytics-forecast-modal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 0.95rem 1rem;
        }
        .analytics-forecast-modal .modal-footer {
            flex: 0 0 auto;
            padding: 0.78rem 1rem;
            background: rgba(255, 255, 255, 0.96);
        }
        .analytics-forecast-modal .modal-close {
            width: 38px;
            height: 38px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }
        .analytics-forecast-modal .steps-container {
            display: grid;
            gap: 10px;
        }
        .analytics-forecast-modal .step-item {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr);
            gap: 10px;
            align-items: flex-start;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .analytics-forecast-modal .step-number {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .analytics-forecast-modal .step-content p {
            margin: 0;
            font-size: 12.5px;
            line-height: 1.55;
            color: #334155;
        }
        .analytics-forecast-modal .resource-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0 8px 8px 0;
            padding: 7px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            color: #334155;
        }
        .analytics-forecast-modal .resources-title,
        .analytics-forecast-modal .timeline-title {
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }
        .analytics-forecast-modal .timeline-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .analytics-forecast-modal .timeline-text {
            font-size: 12.5px;
            line-height: 1.55;
            color: #334155;
        }

        @media (max-width: 640px) {
            #exploreModal.modal.show,
            #predictionDetailModal.modal.show {
                align-items: flex-end;
                justify-content: center;
                padding: 0 12px max(8px, env(safe-area-inset-bottom));
            }
            .analytics-forecast-modal,
            .analytics-forecast-modal--details {
                width: min(100%, 380px);
                max-width: none;
                max-height: min(72svh, 540px);
                border-radius: 20px 20px 14px 14px;
                animation: analyticsSheetIn 0.24s ease;
            }
            .analytics-forecast-modal--details {
                width: min(100%, 360px);
                max-height: min(68svh, 500px);
            }
            .analytics-forecast-modal::before {
                content: "";
                width: 44px;
                height: 4px;
                border-radius: 999px;
                background: #cbd5e1;
                align-self: center;
                margin: 8px 0 1px;
                flex: 0 0 auto;
            }
            .analytics-forecast-modal .modal-header {
                padding: 0.65rem 0.82rem 0.72rem;
            }
            .analytics-forecast-modal .modal-header h3 {
                font-size: 0.84rem;
                max-width: calc(100% - 54px);
            }
            .analytics-forecast-modal .modal-close {
                width: 34px;
                height: 34px;
                font-size: 0.85rem;
            }
            .analytics-forecast-modal .modal-body {
                padding: 0.72rem 0.82rem;
            }
            .analytics-forecast-modal .modal-footer {
                padding: 0.62rem 0.82rem 0.8rem;
                display: grid;
                grid-template-columns: 1fr;
                gap: 7px;
            }
            .analytics-forecast-modal .modal-footer button {
                width: 100%;
                min-height: 38px;
                font-size: 12px;
            }
            .analytics-forecast-modal .modal-body > div {
                gap: 10px !important;
            }
            .analytics-forecast-modal .modal-body [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }
            .analytics-forecast-modal .modal-body [style*="padding:18px"],
            .analytics-forecast-modal .modal-body [style*="padding:16px"],
            .analytics-forecast-modal .modal-body [style*="padding:14px"] {
                padding: 10px !important;
            }
            .analytics-forecast-modal .modal-body [style*="font-size:28px"] {
                font-size: 1.12rem !important;
            }
            .analytics-forecast-modal .modal-body [style*="font-size:22px"],
            .analytics-forecast-modal .modal-body [style*="font-size:24px"] {
                font-size: 0.98rem !important;
            }
            .analytics-forecast-modal .modal-body [style*="font-size:15px"],
            .analytics-forecast-modal .modal-body [style*="font-size:14px"] {
                font-size: 0.76rem !important;
            }
            .analytics-forecast-modal .modal-body [style*="font-size:13px"] {
                font-size: 0.72rem !important;
            }
            .step-item {
                gap: 8px;
                padding: 9px;
                margin-bottom: 7px;
            }
            .step-number {
                width: 22px;
                height: 22px;
                font-size: 10px;
            }
            .resource-tag {
                width: 100%;
                margin: 0 0 7px 0;
                font-size: 11px;
                padding: 7px 9px;
                margin-right: 0;
                padding: 8px 10px;
                font-size: 12px;
            }
        }
        @keyframes analyticsSheetIn {
            from { transform: translateY(18px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
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

        .analytics-stack { display: flex; flex-direction: column; gap: 22px; }
        .analytics-hero {
            position: relative;
            overflow: hidden;
            padding: 28px 30px;
            border-radius: 24px;
            border: 1px solid rgba(251, 191, 36, 0.25);
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 38%),
                radial-gradient(circle at bottom left, rgba(220, 38, 38, 0.12), transparent 40%),
                linear-gradient(135deg, rgba(255,255,255,0.98), rgba(255,251,235,0.96));
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }
        .analytics-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.75);
            border: 1px solid rgba(251,191,36,0.28);
            color: #9a3412;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .analytics-hero__title {
            margin-top: 14px;
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 1.04;
            letter-spacing: -0.05em;
            font-weight: 900;
            color: #0f172a;
            max-width: 11ch;
        }
        .analytics-hero__subtitle {
            margin-top: 14px;
            max-width: 70ch;
            color: #475569;
            font-size: 1rem;
            line-height: 1.75;
        }
        .analytics-hero__meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        .analytics-hero__meta-card {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(226,232,240,0.8);
        }
        .analytics-hero__meta-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .analytics-hero__meta-value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .analytics-shell-card {
            border-radius: 24px;
            border: 1px solid rgba(226,232,240,0.85);
            background: rgba(255,255,255,0.96);
            box-shadow: 0 14px 32px rgba(15,23,42,0.05);
        }
        .analytics-focus-card {
            width: min(100%, 1060px);
            margin-inline: auto;
        }
        .prediction-summary-section {
            max-width: 1060px;
            margin-inline: auto;
            box-shadow: 0 10px 24px rgba(15,23,42,0.04);
        }
        .analytics-section {
            padding: 24px 26px;
        }
        .analytics-section__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .analytics-section__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 900;
            color: #0f172a;
        }
        .analytics-section__copy {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.65;
            max-width: 70ch;
            display: none;
        }

        .analytics-control-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
            align-items: end;
        }
        .analytics-control-field {
            grid-column: span 3;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .analytics-control-field--wide { grid-column: span 4; }
        .analytics-control-label {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .analytics-input,
        .analytics-select {
            min-height: 48px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid #dbe3ef;
            background: #fff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            padding: 0 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .analytics-input[readonly] {
            background: #f8fafc;
            color: #475569;
        }
        .analytics-input:focus,
        .analytics-select:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
        }
        .analytics-actions {
            display: grid;
            grid-column: span 12;
            grid-template-columns: 1.6fr 1fr 1fr;
            gap: 12px;
            margin-top: 6px;
        }
        .analytics-btn {
            min-height: 52px;
            border: none;
            border-radius: 18px;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }
        .analytics-btn:hover { transform: translateY(-1px); }
        .analytics-btn__copy strong {
            display: block;
            font-size: 14px;
            font-weight: 800;
        }
        .analytics-btn__copy span {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            line-height: 1.45;
            opacity: 0.86;
        }
        .analytics-btn--primary {
            background: linear-gradient(135deg, #dc2626, #f59e0b);
            color: #fff;
            box-shadow: 0 16px 32px rgba(220,38,38,0.22);
        }
        .analytics-btn--secondary {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid rgba(249, 115, 22, 0.16);
        }
        .analytics-btn--teal {
            background: #ecfeff;
            color: #115e59;
            border: 1px solid rgba(13, 148, 136, 0.16);
        }

        .prediction-summary-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
        }
        .prediction-card {
            grid-column: span 3;
            padding: 15px 16px;
            border-radius: 18px;
            border: 1px solid rgba(226,232,240,0.9);
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
            box-shadow: 0 8px 18px rgba(15,23,42,0.035);
        }
        .prediction-card--wide { grid-column: span 6; }
        .prediction-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .prediction-card__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            box-shadow: 0 8px 16px rgba(15,23,42,0.07);
        }
        .prediction-card__label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .prediction-card__value {
            margin-top: 10px;
            color: #0f172a;
            font-size: clamp(1.32rem, 1.75vw, 1.9rem);
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -0.04em;
        }
        .prediction-card__copy {
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }
        .prediction-card__trend {
            margin-top: 9px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 800;
            color: #334155;
        }
        .prediction-drivers {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .prediction-driver-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(251, 146, 60, 0.18);
        }

        .analytics-empty-state {
            border-radius: 22px;
            border: 1px dashed rgba(148,163,184,0.45);
            background: linear-gradient(135deg, #fff, #f8fafc);
            padding: 28px 24px;
        }
        .analytics-empty-state__title {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 900;
        }
        .analytics-empty-state__copy {
            margin-top: 8px;
            color: #64748b;
            font-size: 0.94rem;
            line-height: 1.7;
            max-width: 64ch;
        }
        .analytics-empty-state__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }
        .analytics-empty-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(251, 146, 60, 0.18);
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
        }

        .analytics-dual-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 22px;
        }
        .analytics-priority-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(320px, 0.98fr);
            gap: 20px;
            align-items: start;
        }
        .analytics-priority-grid,
        .analytics-panel-grid {
            width: min(100%, 1180px);
            margin-inline: auto;
        }
        .analytics-chart-wrap {
            position: relative;
            min-width: 0;
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid rgba(226,232,240,0.9);
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.95));
            padding: 18px;
        }
        .analytics-chart-canvas {
            position: relative;
            min-height: 320px;
        }
        .analytics-insight-list {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }
        .analytics-insight {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 13px;
            line-height: 1.6;
        }
        .analytics-insight i { color: #ea580c; margin-top: 2px; }

        .analytics-panel-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .analytics-ops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .analytics-ops-card {
            padding: 18px;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.95));
            border: 1px solid rgba(226,232,240,0.9);
        }
        .analytics-ops-card__title {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .analytics-ops-card__value {
            margin-top: 10px;
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 900;
        }
        .analytics-progress {
            margin-top: 14px;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
        }
        .analytics-progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }
        .analytics-legend-note {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }
        .analytics-legend-note span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .analytics-legend-swatch {
            width: 12px;
            height: 12px;
            border-radius: 999px;
        }

        .chief-theme-shell,
        .analytics-stack,
        .analytics-shell-card,
        .analytics-section,
        .analytics-chart-wrap,
        .prediction-card {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }

        .analytics-info-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .analytics-info-button {
            display: inline-flex;
            width: 38px;
            height: 38px;
            border: 1px solid rgba(251, 146, 60, 0.22);
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .analytics-info-button:hover,
        .analytics-info-button:focus-visible {
            background: #ffffff;
            border-color: rgba(194, 65, 12, 0.28);
            color: #9a3412;
            transform: translateY(-1px);
            outline: none;
        }

        .analytics-info-popover {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 45;
            width: min(260px, calc(100vw - 28px));
            padding: 12px 14px;
            border: 1px solid rgba(251, 146, 60, 0.2);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.98);
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            transition: opacity 160ms ease, transform 160ms ease;
        }

        .analytics-info-wrap:hover .analytics-info-popover,
        .analytics-info-wrap:focus-within .analytics-info-popover,
        .analytics-info-wrap.is-open .analytics-info-popover {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .analytics-mobile-select-trigger,
        .analytics-mobile-select-sheet {
            display: none;
        }

        .analytics-mobile-select-sheet {
            position: fixed;
            inset: 0;
            z-index: 10020;
            align-items: flex-end;
            justify-content: center;
            padding: 12px;
            background: rgba(15, 23, 42, 0.18);
        }

        .analytics-mobile-select-sheet[aria-hidden="false"] {
            display: flex;
        }

        .analytics-mobile-select-panel {
            width: min(420px, 100%);
            max-height: min(66vh, 500px);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(251, 146, 60, 0.18);
            border-radius: 24px 24px 18px 18px;
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.13), transparent 36%),
                #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .analytics-mobile-select-handle {
            width: 52px;
            height: 5px;
            margin: 8px auto 6px;
            border-radius: 999px;
            background: #cbd5e1;
            flex: 0 0 auto;
        }

        .analytics-mobile-select-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 14px 12px;
            border-bottom: 1px solid #eef2f7;
        }

        .analytics-mobile-select-title {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
        }

        .analytics-mobile-select-close {
            width: 44px;
            height: 44px;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            background: #fffaf5;
            color: #c2410c;
            cursor: pointer;
        }

        .analytics-mobile-select-search {
            margin: 12px 14px 8px;
            min-height: 42px;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            padding: 0 12px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            outline: none;
        }

        .analytics-mobile-select-options {
            display: grid;
            gap: 8px;
            padding: 0 14px 14px;
            overflow: auto;
        }

        .analytics-mobile-select-option {
            min-height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 800;
            text-align: left;
            cursor: pointer;
        }

        .analytics-mobile-select-option.is-selected {
            border-color: rgba(245, 158, 11, 0.46);
            background: #fff7ed;
            color: #c2410c;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn { display: block; }
            .mobile-logo { display: flex; }
            .charts-grid { grid-template-columns: 1fr; }
            .analytics-dual-grid { grid-template-columns: 1fr; }
            .analytics-actions { grid-template-columns: 1fr; }
            .analytics-control-field,
            .analytics-control-field--wide,
            .prediction-card,
            .prediction-card--wide { grid-column: span 6; }
        }
        @media (max-width: 768px) {
            .legacy-chief-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .legacy-chief-sidebar.open { transform: translateX(0); }
            .main-content {
                margin-left: 0;
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 112px) !important;
                overflow-x: hidden;
            }
            .dashboard-grid { grid-template-columns: 1fr; gap: 12px; margin-bottom: 12px; }
            .top-nav-container { padding: 0 16px; }
            .step-item { flex-direction: column; align-items: flex-start; }
            .step-number { margin-bottom: 8px; }

            .lector-top-bar {
                min-height: 48px !important;
                height: 48px !important;
                margin: 8px auto 0 !important;
                padding: 0 10px !important;
                width: calc(100% - 20px) !important;
                max-width: 100% !important;
                border-radius: 14px !important;
                top: calc(env(safe-area-inset-top, 0px) + 6px) !important;
            }

            .lector-top-bar .flex.items-center.gap-4,
            .lector-top-bar .flex.items-center.gap-6 {
                gap: 0.45rem !important;
            }

            .lector-top-bar .user-dropdown-toggle {
                padding: 4px 8px !important;
                gap: 0.45rem !important;
            }

            .lector-top-bar .user-dropdown-toggle .w-8.h-8 {
                width: 1.75rem !important;
                height: 1.75rem !important;
            }

            .station-badge {
                font-size: 0.62rem !important;
                padding: 4px 8px !important;
                max-width: 132px !important;
            }

            .content-container {
                width: 100%;
                max-width: 100%;
                padding: 10px 10px calc(env(safe-area-inset-bottom, 0px) + 112px);
                overflow-x: clip;
            }

            .content-container > div:first-child h1 {
                font-size: 1rem !important;
                line-height: 1.15;
            }

            .content-container > div:first-child p {
                font-size: 0.68rem !important;
                line-height: 1.4;
            }

            .stat-card {
                border-radius: 14px;
                padding: 12px !important;
            }

            .form-select {
                min-height: 34px;
                font-size: 0.68rem;
                padding: 6px 10px;
            }

            #riskScore {
                font-size: 2rem !important;
            }

            #riskLabel,
            #confidenceScore {
                font-size: 0.7rem !important;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 12px;
            }

            canvas { max-height: none !important; }

            .recommendation-card {
                padding: 12px;
                border-radius: 10px;
            }

            .explore-btn {
                font-size: 0.68rem;
                padding: 4px 6px;
            }

            .analytics-stack { gap: 12px; }
            .analytics-hero,
            .analytics-shell-card,
            .analytics-chart-wrap,
            .prediction-card,
            .analytics-ops-card { border-radius: 18px; }
            .analytics-section { padding: 13px; }
            .analytics-hero { padding: 15px 14px; }
            .analytics-hero__eyebrow { padding: 6px 10px; font-size: 9.5px; }
            .analytics-hero__title { margin-top: 10px; font-size: 1.55rem; max-width: none; }
            .analytics-hero__subtitle { font-size: 0.78rem; line-height: 1.45; }
            .analytics-hero__meta { grid-template-columns: 1fr; gap: 8px; margin-top: 12px; }
            .analytics-hero__meta-card { padding: 10px 12px; border-radius: 14px; }
            .analytics-hero__meta-value { font-size: 12.5px; margin-top: 5px; }
            .analytics-section__head { align-items: center; gap: 8px; margin-bottom: 10px; }
            .analytics-section__title { font-size: 0.94rem; gap: 8px; }
            .analytics-section__copy { display: none; }
            .analytics-focus-card,
            .prediction-summary-section,
            .analytics-priority-grid,
            .analytics-panel-grid { width: 100%; max-width: none; }
            .analytics-info-button { display: inline-flex; }
            .analytics-control-grid { display: flex; flex-direction: column; gap: 10px; }
            .analytics-control-field,
            .analytics-control-field--wide { width: 100%; grid-column: auto; gap: 5px; }
            .analytics-control-label { font-size: 10px; letter-spacing: 0.06em; }
            .analytics-input,
            .analytics-select,
            .analytics-mobile-select-trigger {
                min-height: 38px;
                border-radius: 11px;
                font-size: 12px;
                padding: 0 10px;
            }
            .analytics-mobile-select-trigger {
                width: 100%;
                border: 1px solid #dbe3ef;
                background: #fff;
                color: #0f172a;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                font-weight: 800;
            }
            #barangaySelect.analytics-mobile-enhanced {
                position: absolute;
                width: 1px;
                height: 1px;
                opacity: 0;
                pointer-events: none;
            }
            .analytics-actions { grid-template-columns: 1fr; gap: 8px; width: 100%; }
            .analytics-btn { min-height: 40px; padding: 9px 10px; border-radius: 12px; align-items: center; }
            .analytics-btn__copy strong { font-size: 11.75px; }
            .analytics-btn__copy span { display: none; }
            .prediction-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .prediction-card,
            .prediction-card--wide { grid-column: auto; padding: 11px; border-radius: 16px; }
            .prediction-card--wide { grid-column: 1 / -1; }
            .prediction-card__icon { width: 34px; height: 34px; border-radius: 12px; font-size: 13px; }
            .prediction-card__label { font-size: 9.5px; }
            .prediction-card__value { font-size: 1.25rem; margin-top: 9px; }
            .prediction-card__copy { font-size: 11.5px; line-height: 1.35; }
            .prediction-driver-chip { padding: 6px 9px; font-size: 10.5px; }
            .analytics-dual-grid,
            .analytics-priority-grid,
            .analytics-panel-grid { grid-template-columns: 1fr; gap: 10px; }
            .analytics-chart-wrap { padding: 10px; overflow: hidden; }
            .analytics-chart-canvas { width: 100%; min-width: 0 !important; min-height: 0 !important; height: 224px; }
            .analytics-chart-canvas canvas { width: 100% !important; height: 100% !important; }
            .analytics-insight { padding: 9px 10px; border-radius: 13px; font-size: 11.5px; line-height: 1.45; }
            .analytics-ops-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .analytics-ops-card { padding: 11px; }
            .analytics-ops-card__title { font-size: 10px; }
            .analytics-ops-card__value { font-size: 1.16rem; margin-top: 8px; }
            .analytics-legend-note { gap: 6px; font-size: 10.5px; }
            .recommendation-card { padding: 12px !important; border-radius: 15px !important; margin-bottom: 10px !important; }
            .recommendation-run-toggle { padding: 12px; align-items: flex-start; }
            .recommendation-run-title { font-size: 13px; }
            .recommendation-run-subtitle { font-size: 11px; }
            .recommendation-run-arrow { width: 30px; height: 30px; border-radius: 10px; }
            .recommendation-run-content { padding: 0 12px 12px; }
            .explore-btn { width: 100%; justify-content: center; padding: 10px 12px; font-size: 12px; border-radius: 12px; }
            #aiExplanation { margin-top: 12px !important; padding: 12px !important; font-size: 11.5px !important; border-radius: 14px !important; }
        }

        @media (max-width: 340px) {
            .analytics-hero__title { font-size: 1.38rem; }
            .analytics-section { padding: 12px; }
            .prediction-summary-grid,
            .analytics-ops-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; }
            .prediction-card,
            .prediction-card--wide,
            .analytics-ops-card { padding: 10px; }
            .prediction-card__value,
            .analytics-ops-card__value { font-size: 1.02rem; }
            .prediction-card__copy { font-size: 10.5px; }
            .analytics-chart-canvas { height: 210px; }
        }

        .analytics-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: min(360px, calc(100vw - 32px));
            padding: 12px 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            color: #0f172a;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .analytics-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .analytics-toast__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            flex: 0 0 auto;
        }

        .analytics-toast[data-type="success"] .analytics-toast__icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .analytics-toast[data-type="warning"] .analytics-toast__icon {
            background: #fff7ed;
            color: #ea580c;
        }

        .analytics-toast[data-type="error"] .analytics-toast__icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .analytics-toast__message {
            font-size: 0.9rem;
            line-height: 1.4;
            font-weight: 700;
        }

        .analytics-confirm[hidden],
        .analytics-toast[hidden] {
            display: none !important;
        }

        .analytics-confirm {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.46);
            backdrop-filter: blur(4px);
        }

        .analytics-confirm__panel {
            width: min(420px, 100%);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .analytics-confirm__body {
            padding: 24px;
        }

        .analytics-confirm__eyebrow {
            margin-bottom: 8px;
            color: #dc2626;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .analytics-confirm__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.22rem;
            font-weight: 900;
        }

        .analytics-confirm__message {
            margin: 10px 0 0;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .analytics-confirm__actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 14px 18px 18px;
            background: #fffaf5;
        }

        .analytics-confirm__button {
            min-height: 44px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 14px;
            padding: 0 18px;
            font-weight: 800;
            cursor: pointer;
        }

        .analytics-confirm__button--cancel {
            background: #fff;
            color: #475569;
        }

        .analytics-confirm__button--accept {
            border: none;
            background: linear-gradient(135deg, #dc2626, #f59e0b);
            color: #fff;
            box-shadow: 0 14px 30px rgba(220, 38, 38, 0.18);
        }

        @media (max-width: 640px) {
            .analytics-toast {
                top: auto;
                right: 14px;
                bottom: calc(92px + env(safe-area-inset-bottom));
                left: 14px;
                max-width: none;
            }

            .analytics-confirm {
                align-items: flex-end;
                padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
            }

            .analytics-confirm__panel {
                width: min(100%, 400px);
                border-radius: 20px;
            }

            .analytics-confirm__body {
                padding: 16px 15px;
            }

            .analytics-confirm__actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                padding: 10px 12px 12px;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">

    @include('chief.partials.sidebar', ['active' => 'analytics'])
    <aside class="sidebar legacy-chief-sidebar" style="display:none;">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
                <div class="logo-area">
                    <div class="logo-icon">@include('partials.app-logo')</div>
                    <div class="logo-text"><h2>PyroSense</h2><p>command center</p></div>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span></div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Station Admin' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;"><i class="fa-regular fa-clock"></i> {{ now()->format('H:i') }} · {{ now()->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item"><i class="fa-solid fa-file-certificate"></i><span>FSIC Management</span></a>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item active"><i class="fa-solid fa-chart-line"></i><span>Fire Risk Forecast</span></a>
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
                    <h1 class="text-xl font-bold text-slate-800">Fire Risk Forecast</h1>
                    <p class="text-xs text-slate-500 font-medium">Station intelligence and fire-risk forecasting · {{ now()->format('l, F j') }}</p>
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

        <div class="content-container chief-theme-shell">
            <div class="analytics-stack">
                <section class="analytics-hero chief-theme-hero">
                    <div>
                        <div class="analytics-hero__eyebrow chief-theme-hero__eyebrow">
                            <i class="fa-solid fa-microchip"></i>
                            <span>Command Forecasting Suite</span>
                        </div>
                        <h1 class="analytics-hero__title chief-theme-hero__title">Fire Risk Forecast</h1>
                        <p class="analytics-hero__subtitle chief-theme-hero__copy">Compare historical incidents, current station conditions, and predicted fire-risk outlooks in one command-ready dashboard built for station-level decision making.</p>
                    </div>
                    <div class="analytics-hero__meta chief-theme-summary-grid">
                        <div class="analytics-hero__meta-card">
                            <div class="analytics-hero__meta-label">Analysis Scope</div>
                            <div class="analytics-hero__meta-value" id="analyticsHeroScope">Waiting for station context</div>
                        </div>
                        <div class="analytics-hero__meta-card">
                            <div class="analytics-hero__meta-label">Forecast Target</div>
                            <div class="analytics-hero__meta-value" id="analyticsHeroTarget">Current month</div>
                        </div>
                        <div class="analytics-hero__meta-card">
                            <div class="analytics-hero__meta-label">Active Model</div>
                            <div class="analytics-hero__meta-value" id="analyticsHeroModel">Loading latest saved model</div>
                        </div>
                    </div>
                </section>

                <section class="analytics-shell-card analytics-section chief-theme-panel analytics-focus-card">
                    <div class="analytics-section__head">
                        <div>
                            <div class="analytics-section__title"><i class="fa-solid fa-sliders"></i> Filter and AI Actions</div>
                            <p class="analytics-section__copy">Choose the station context, narrow to a barangay when needed, set the comparison range, and then decide whether to run a fresh analysis, generate a next-month forecast, or retrain the model on the latest database export.</p>
                        </div>
                    </div>
                    <div class="analytics-control-grid">
                        <div class="analytics-control-field">
                            <label class="analytics-control-label" for="stationSelect">Station</label>
                            <select id="stationSelect" class="analytics-select">
                                <option value="">Loading locations...</option>
                            </select>
                        </div>
                        <div class="analytics-control-field">
                            <label class="analytics-control-label" for="barangaySelect">Barangay</label>
                            <select id="barangaySelect" class="analytics-select">
                                <option value="all">All barangays in forecast</option>
                            </select>
                        </div>
                        <div class="analytics-control-field">
                            <label class="analytics-control-label" for="periodSelect">Date Range</label>
                            <select id="periodSelect" class="analytics-select">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                        <div class="analytics-control-field">
                            <label class="analytics-control-label" for="targetMonthDisplay">Target Month</label>
                            <input id="targetMonthDisplay" class="analytics-input" type="text" value="Current month forecast" readonly>
                        </div>
                        <div class="analytics-actions">
                            <button id="analyzeBtn" class="analytics-btn analytics-btn--primary" type="button">
                                <div class="analytics-btn__copy">
                                    <strong><i class="fa-solid fa-chart-line"></i> Run AI Analysis</strong>
                                    <span>Runs the full command analysis using the selected station, barangay, and date range.</span>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <button id="predictNextMonthBtn" class="analytics-btn analytics-btn--secondary" type="button">
                                <div class="analytics-btn__copy">
                                    <strong><i class="fa-solid fa-calendar-plus"></i> Predict Next Month</strong>
                                    <span>Generates a forward-looking risk outlook for the next calendar month.</span>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                            <button id="retrainModelBtn" class="analytics-btn analytics-btn--teal" type="button">
                                <div class="analytics-btn__copy">
                                    <strong><i class="fa-solid fa-rotate"></i> Retrain Model</strong>
                                    <span>Rebuilds the station forecasting model using the latest exported training dataset.</span>
                                </div>
                                <i class="fa-solid fa-gears"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <section class="analytics-shell-card analytics-section chief-theme-panel prediction-summary-section">
                    <div class="analytics-section__head">
                        <div>
                            <div class="analytics-section__title"><i class="fa-solid fa-bolt"></i> Prediction Summary</div>
                            <p class="analytics-section__copy">A compact command summary of probability, forecasted load, confidence, and hotspot signals derived from the latest analysis response.</p>
                        </div>
                        <div id="predictionScopeLabel" class="analytics-empty-pill" style="display:none;">Prediction scope</div>
                    </div>
                    <div class="prediction-summary-grid">
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Predicted Probability</div>
                                    <div id="summaryProbability" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #dc2626, #f59e0b);"><i class="fa-solid fa-wave-square"></i></div>
                            </div>
                            <div class="prediction-card__copy">Station-wide forecast probability for the selected prediction window.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Predicted Incident Count</div>
                                    <div id="summaryIncidentCount" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #ef4444, #f97316);"><i class="fa-solid fa-fire-flame-curved"></i></div>
                            </div>
                            <div class="prediction-card__copy">Projected possible fire incidents in the active forecast period.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Risk Level</div>
                                    <div id="riskLabel" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #0f172a, #475569);"><i class="fa-solid fa-shield-heart"></i></div>
                            </div>
                            <div class="prediction-card__copy">Current command risk classification after mitigation context is applied.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Confidence Score</div>
                                    <div id="confidenceScore" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #2563eb, #38bdf8);"><i class="fa-solid fa-crosshairs"></i></div>
                            </div>
                            <div class="prediction-card__copy">Confidence reported by the current analysis response and model context.</div>
                        </article>
                        <article class="prediction-card prediction-card--wide">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Trend Direction</div>
                                    <div id="summaryTrendDirection" class="prediction-card__value">Waiting for analysis</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #7c3aed, #ec4899);"><i class="fa-solid fa-arrow-trend-up"></i></div>
                            </div>
                            <div id="summaryTrendCopy" class="prediction-card__copy">The page will compare the current seven-day pattern against the previous seven-day period once analysis data is loaded.</div>
                            <div id="summaryTrendIndicator" class="prediction-card__trend"><i class="fa-solid fa-chart-simple"></i><span>No comparison yet</span></div>
                        </article>
                        <article class="prediction-card prediction-card--wide">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Highest-Risk Barangay and Main Drivers</div>
                                    <div id="summaryTopBarangay" class="prediction-card__value">No hotspot yet</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #059669, #14b8a6);"><i class="fa-solid fa-location-dot"></i></div>
                            </div>
                            <div id="summaryTopBarangayCopy" class="prediction-card__copy">Run an analysis to identify which barangay is currently carrying the strongest forecast pressure.</div>
                            <div id="summaryDrivers" class="prediction-drivers"></div>
                        </article>
                    </div>
                    <div id="riskScore" style="display:none;">--</div>
                    <div id="riskFactors" style="display:none;"></div>
                </section>

                <section class="analytics-priority-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-wand-magic-sparkles"></i> Forecast Narrative</div>
                                <p class="analytics-section__copy">Readable command summary that explains the current forecast window, hotspot context, and key drivers behind the AI response.</p>
                            </div>
                        </div>
                        <div id="aiPrediction">
                            <div class="analytics-empty-state">
                                <div class="analytics-empty-state__title">No prediction has been generated for this selection yet.</div>
                                <p class="analytics-empty-state__copy">Select a station and optional barangay, choose a comparison range, and run AI analysis to produce a fire-risk forecast using historical incidents, current conditions, and model output.</p>
                                <div class="analytics-empty-state__meta">
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-location-dot"></i> <span id="predictionEmptyStation">Selected station will appear here</span></span>
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-map"></i> <span id="predictionEmptyBarangay">All barangays</span></span>
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-calendar-days"></i> <span id="predictionEmptyRange">Last 30 days</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-lightbulb"></i> AI Recommendations</div>
                                <p class="analytics-section__copy">Operational recommendations prioritized for station action based on the current forecast response, hotspot context, and field findings.</p>
                            </div>
                            <div id="recommendationScopeLabel" class="analytics-empty-pill" style="display:none;">Recommendation scope</div>
                        </div>
                        <div id="recommendations">
                            <div class="analytics-empty-state">
                                <div class="analytics-empty-state__title">No AI recommendations yet.</div>
                                <p class="analytics-empty-state__copy">Run the station analysis first to generate recommendation cards and workflow-ready action items.</p>
                            </div>
                        </div>
                        <div id="aiExplanation" style="margin-top: 20px; padding: 14px 16px; background: #f8fafc; border-radius: 16px; font-size: 13px; color: #475569; display: none;"></div>
                    </div>
                </section>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-calendar-week"></i> Daily Trend — 7 Days</div>
                                <p class="analytics-section__copy">Compares previous-period actuals, current seven-day incident activity, and the forecast probability signal returned by the backend.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="dailyChart"></canvas>
                            </div>
                            <div class="analytics-legend-note">
                                <span><i class="analytics-legend-swatch" style="background:#94a3b8;"></i> Previous period actual incidents</span>
                                <span><i class="analytics-legend-swatch" style="background:#dc2626;"></i> Current seven-day actual incidents</span>
                                <span><i class="analytics-legend-swatch" style="background:#f59e0b;"></i> Predicted probability (%)</span>
                            </div>
                        </div>
                        <div id="dailyTrendInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Insights will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-clock"></i> Hourly Incident Pattern</div>
                                <p class="analytics-section__copy">Shows previous-month hourly activity, the current-month hourly pattern, and predicted peak-hour emphasis when the model returns hourly watch windows.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                            <div class="analytics-legend-note">
                                <span><i class="analytics-legend-swatch" style="background:#475569;"></i> Previous month baseline</span>
                                <span><i class="analytics-legend-swatch" style="background:#2563eb;"></i> Current month hourly actuals</span>
                                <span><i class="analytics-legend-swatch" style="background:#ea580c;"></i> Predicted peak-hour probability</span>
                            </div>
                        </div>
                        <div id="hourlyPatternInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Hourly insights will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                </section>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-location-dot"></i> Barangay Forecast Ranking</div>
                                <p class="analytics-section__copy">Barangay-level hotspot ranking for the current target month using the backend prediction payload only.</p>
                            </div>
                        </div>
                        <div id="barangayPredictionPanel">
                            <div class="analytics-empty-state">
                                <div class="analytics-empty-state__title">No barangay forecast ranking yet.</div>
                                <p class="analytics-empty-state__copy">Run AI analysis to score each covered barangay and identify the current hotspot order for command review.</p>
                            </div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-fire"></i> Operational Snapshot</div>
                                <p class="analytics-section__copy">Current incident load, readiness, and response indicators supporting the forecast interpretation.</p>
                            </div>
                        </div>
                        <div class="analytics-ops-grid">
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Total Incidents</div>
                                <div class="analytics-ops-card__value" id="totalIncidents">--</div>
                                <div class="prediction-card__copy">All incidents included in the active comparison range.</div>
                            </article>
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Fire Incidents</div>
                                <div class="analytics-ops-card__value" id="fireIncidents">--</div>
                                <div class="prediction-card__copy">Fire-related incidents within the same filtered range.</div>
                            </article>
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Average Response</div>
                                <div class="analytics-ops-card__value" id="avgResponse">--</div>
                                <div class="prediction-card__copy">Average time from alarm to completed response, when available.</div>
                            </article>
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Peak Hour</div>
                                <div class="analytics-ops-card__value" id="peakHour">--</div>
                                <div class="prediction-card__copy">Observed hour with the strongest current activity concentration.</div>
                            </article>
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Personnel Readiness</div>
                                <div class="analytics-ops-card__value" id="availablePersonnel">--</div>
                                <div class="analytics-progress"><span id="personnelBar" style="width:0%; background:linear-gradient(135deg, #10b981, #14b8a6);"></span></div>
                                <div class="prediction-card__copy">Available responders versus the total personnel roster.</div>
                            </article>
                            <article class="analytics-ops-card">
                                <div class="analytics-ops-card__title">Equipment Readiness</div>
                                <div class="analytics-ops-card__value" id="availableEquipment">--</div>
                                <div class="analytics-progress"><span id="equipmentBar" style="width:0%; background:linear-gradient(135deg, #2563eb, #38bdf8);"></span></div>
                                <div class="prediction-card__copy">Available station equipment compared with total assigned inventory.</div>
                            </article>
                        </div>
                        <div id="incidentTypes" class="prediction-card__copy" style="margin-top:14px;"></div>
                    </div>
                </section>

                <section class="analytics-panel-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-list-check"></i> Action Plan Checklist</div>
                                <p class="analytics-section__copy">Saved checklist rows generated from the recommendations and prepared for review-to-mitigation handoff.</p>
                            </div>
                        </div>
                        <div id="actionPlanChecklistPanel">
                            <div class="analytics-empty-state">
                                <div class="analytics-empty-state__title">No checklist rows have been created yet.</div>
                                <p class="analytics-empty-state__copy">Once an analysis is stored, this section will show workflow-ready checklist items and mitigation handoff links.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Enhanced Explore Recommendation Modal -->
    <div id="exploreModal" class="modal">
        <div class="modal-content analytics-forecast-modal">
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

    <div id="predictionDetailModal" class="modal">
        <div class="modal-content analytics-forecast-modal analytics-forecast-modal--details">
            <div class="modal-header">
                <h3><i class="fa-solid fa-chart-line" style="color: #dc2626;"></i> Forecast Details</h3>
                <button class="modal-close" onclick="closePredictionDetailModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="predictionDetailModalBody">
                <div class="text-center py-8 text-gray-500">No forecast details available.</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closePredictionDetailModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div style="text-align: center;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 16px;">AI is analyzing real incident data...</p>
        </div>
    </div>

    <div id="analyticsToast" class="analytics-toast" data-type="info" role="status" aria-live="polite" hidden>
        <span class="analytics-toast__icon"><i class="fa-solid fa-circle-info"></i></span>
        <span id="analyticsToastMessage" class="analytics-toast__message"></span>
    </div>

    <div id="analyticsConfirmModal" class="analytics-confirm" aria-hidden="true" hidden>
        <div class="analytics-confirm__panel" role="dialog" aria-modal="true" aria-labelledby="analyticsConfirmTitle" aria-describedby="analyticsConfirmMessage">
            <div class="analytics-confirm__body">
                <div id="analyticsConfirmEyebrow" class="analytics-confirm__eyebrow">Confirm action</div>
                <h3 id="analyticsConfirmTitle" class="analytics-confirm__title">Continue?</h3>
                <p id="analyticsConfirmMessage" class="analytics-confirm__message">Please confirm this action.</p>
            </div>
            <div class="analytics-confirm__actions">
                <button type="button" id="analyticsConfirmCancel" class="analytics-confirm__button analytics-confirm__button--cancel">Cancel</button>
                <button type="button" id="analyticsConfirmAccept" class="analytics-confirm__button analytics-confirm__button--accept">Continue</button>
            </div>
        </div>
    </div>

    <script>
        let hourlyChart, dailyChart;
        let currentRecommendations = [];
        let currentAnalysisId = null;
        let analyticsStations = [];
        let currentBarangayPredictions = [];
        let currentPredictionDetails = null;
        let currentAiModels = null;
        let lastAiWarningToastKey = null;
        let recommendationRunGroups = [];
        let recommendationRunCache = new Map();
        let openRecommendationRunId = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let analyticsToastTimer = null;
        let analyticsConfirmResolver = null;
        const analyticsMobileQuery = window.matchMedia('(max-width: 768px)');

        function isAnalyticsMobile() {
            return analyticsMobileQuery.matches;
        }

        function cleanGeneratedText(text) {
            return String(text ?? '')
                .replace(/\*\*(.*?)\*\*/g, '$1')
                .replace(/`([^`]*)`/g, '$1')
                .replace(/^#{1,6}\s*/gm, '')
                .replace(/^\s*[-*]\s+/gm, '')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        }

        function syncMobileBarangaySelect() {
            const select = document.getElementById('barangaySelect');
            const trigger = document.querySelector('[data-analytics-barangay-trigger]');
            if (!select || !trigger) return;

            const selected = select.options[select.selectedIndex];
            trigger.disabled = select.disabled;
            trigger.innerHTML = `
                <span>${escapeHtml(selected?.textContent || 'Choose barangay')}</span>
                <i class="fa-solid fa-chevron-up"></i>
            `;
        }

        function closeMobileBarangaySheet() {
            const sheet = document.querySelector('[data-analytics-barangay-sheet]');
            if (!sheet) return;

            sheet.setAttribute('aria-hidden', 'true');
            document.body.style.removeProperty('overflow');
            document.querySelector('[data-analytics-barangay-trigger]')?.focus({ preventScroll: true });
        }

        function renderMobileBarangayOptions(searchTerm = '') {
            const select = document.getElementById('barangaySelect');
            const list = document.querySelector('[data-analytics-barangay-options]');
            if (!select || !list) return;

            const query = searchTerm.trim().toLowerCase();
            const options = Array.from(select.options).filter((option) => {
                return !query || option.textContent.toLowerCase().includes(query);
            });

            list.innerHTML = options.length
                ? options.map((option) => `
                    <button type="button"
                        class="analytics-mobile-select-option ${option.value === select.value ? 'is-selected' : ''}"
                        data-analytics-barangay-value="${escapeHtml(option.value)}"
                        aria-selected="${option.value === select.value ? 'true' : 'false'}">
                        <span>${escapeHtml(option.textContent)}</span>
                        ${option.value === select.value ? '<i class="fa-solid fa-check"></i>' : ''}
                    </button>
                `).join('')
                : '<div class="analytics-mobile-select-option" aria-disabled="true">No barangay found</div>';
        }

        function openMobileBarangaySheet() {
            const select = document.getElementById('barangaySelect');
            const sheet = document.querySelector('[data-analytics-barangay-sheet]');
            const search = document.querySelector('[data-analytics-barangay-search]');
            if (!select || !sheet || select.disabled) return;

            renderMobileBarangayOptions('');
            if (search) search.value = '';
            sheet.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            setTimeout(() => search?.focus({ preventScroll: true }), 80);
        }

        function initializeMobileBarangaySelect() {
            const select = document.getElementById('barangaySelect');
            if (!select || document.querySelector('[data-analytics-barangay-trigger]')) return;

            select.classList.add('analytics-mobile-enhanced');

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'analytics-mobile-select-trigger';
            trigger.setAttribute('data-analytics-barangay-trigger', 'true');
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.addEventListener('click', openMobileBarangaySheet);
            select.insertAdjacentElement('afterend', trigger);

            const sheet = document.createElement('div');
            sheet.className = 'analytics-mobile-select-sheet';
            sheet.setAttribute('data-analytics-barangay-sheet', 'true');
            sheet.setAttribute('aria-hidden', 'true');
            sheet.innerHTML = `
                <div class="analytics-mobile-select-panel" role="dialog" aria-modal="true" aria-label="Choose barangay">
                    <div class="analytics-mobile-select-handle" aria-hidden="true"></div>
                    <div class="analytics-mobile-select-head">
                        <h2 class="analytics-mobile-select-title">Choose barangay</h2>
                        <button type="button" class="analytics-mobile-select-close" data-analytics-barangay-close aria-label="Close barangay selector">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <input type="search" class="analytics-mobile-select-search" data-analytics-barangay-search placeholder="Search barangay">
                    <div class="analytics-mobile-select-options" data-analytics-barangay-options></div>
                </div>
            `;
            document.body.appendChild(sheet);

            sheet.addEventListener('click', (event) => {
                if (event.target === sheet || event.target.closest('[data-analytics-barangay-close]')) {
                    closeMobileBarangaySheet();
                    return;
                }

                const option = event.target.closest('[data-analytics-barangay-value]');
                if (!option) return;

                select.value = option.getAttribute('data-analytics-barangay-value');
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncMobileBarangaySelect();
                closeMobileBarangaySheet();
            });

            sheet.querySelector('[data-analytics-barangay-search]')?.addEventListener('input', (event) => {
                renderMobileBarangayOptions(event.target.value);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && sheet.getAttribute('aria-hidden') === 'false') {
                    closeMobileBarangaySheet();
                }
            });

            new MutationObserver(syncMobileBarangaySelect).observe(select, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled']
            });
            select.addEventListener('change', syncMobileBarangaySelect);
            syncMobileBarangaySelect();
        }

        function enhanceAnalyticsInfoButtons() {
            document.querySelectorAll('.analytics-section__head').forEach((head, index) => {
                const copy = head.querySelector('.analytics-section__copy');
                if (!copy || head.querySelector('.analytics-info-wrap')) return;

                const wrap = document.createElement('span');
                wrap.className = 'analytics-info-wrap';
                wrap.innerHTML = `
                    <button type="button" class="analytics-info-button" aria-expanded="false" aria-label="Show section details">
                        <i class="fa-solid fa-circle-info"></i>
                    </button>
                    <span class="analytics-info-popover" id="analyticsInfoPopover${index}" role="status">${escapeHtml(copy.textContent)}</span>
                `;
                head.appendChild(wrap);

                const button = wrap.querySelector('.analytics-info-button');
                button?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    document.querySelectorAll('.analytics-info-wrap.is-open').forEach((openWrap) => {
                        if (openWrap !== wrap) {
                            openWrap.classList.remove('is-open');
                            openWrap.querySelector('.analytics-info-button')?.setAttribute('aria-expanded', 'false');
                        }
                    });
                    const nextState = !wrap.classList.contains('is-open');
                    wrap.classList.toggle('is-open', nextState);
                    button.setAttribute('aria-expanded', nextState ? 'true' : 'false');
                });
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('.analytics-info-wrap.is-open').forEach((wrap) => {
                    wrap.classList.remove('is-open');
                    wrap.querySelector('.analytics-info-button')?.setAttribute('aria-expanded', 'false');
                });
            });
        }

        function showAnalyticsToast(message, type = 'info') {
            const toast = document.getElementById('analyticsToast');
            const messageEl = document.getElementById('analyticsToastMessage');
            const icon = toast?.querySelector('.analytics-toast__icon i');

            if (!toast || !messageEl) return;

            const iconMap = {
                success: 'fa-circle-check',
                error: 'fa-circle-exclamation',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info',
            };

            messageEl.textContent = message || 'Action updated.';
            toast.dataset.type = type;
            if (icon) {
                icon.className = `fa-solid ${iconMap[type] || iconMap.info}`;
            }

            toast.hidden = false;
            window.clearTimeout(analyticsToastTimer);
            requestAnimationFrame(() => toast.classList.add('is-visible'));
            analyticsToastTimer = window.setTimeout(() => {
                toast.classList.remove('is-visible');
                window.setTimeout(() => { toast.hidden = true; }, 200);
            }, 3600);
        }

        function showAnalyticsConfirm(options = {}) {
            const modal = document.getElementById('analyticsConfirmModal');
            const eyebrow = document.getElementById('analyticsConfirmEyebrow');
            const title = document.getElementById('analyticsConfirmTitle');
            const message = document.getElementById('analyticsConfirmMessage');
            const cancel = document.getElementById('analyticsConfirmCancel');
            const accept = document.getElementById('analyticsConfirmAccept');

            if (!modal || !cancel || !accept || !title || !message) {
                return Promise.resolve(false);
            }

            eyebrow.textContent = options.eyebrow || 'Confirm action';
            title.textContent = options.title || 'Continue?';
            message.textContent = options.message || 'Please confirm this action.';
            cancel.textContent = options.cancelText || 'Cancel';
            accept.textContent = options.acceptText || 'Continue';

            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            accept.focus({ preventScroll: true });

            return new Promise((resolve) => {
                analyticsConfirmResolver = resolve;
            });
        }

        function closeAnalyticsConfirm(value) {
            const modal = document.getElementById('analyticsConfirmModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'true');
                modal.hidden = true;
            }

            if (analyticsConfirmResolver) {
                analyticsConfirmResolver(value);
                analyticsConfirmResolver = null;
            }
        }

        document.getElementById('analyticsConfirmCancel')?.addEventListener('click', () => closeAnalyticsConfirm(false));
        document.getElementById('analyticsConfirmAccept')?.addEventListener('click', () => closeAnalyticsConfirm(true));
        document.getElementById('analyticsConfirmModal')?.addEventListener('click', (event) => {
            if (event.target.id === 'analyticsConfirmModal') {
                closeAnalyticsConfirm(false);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            enhanceAnalyticsInfoButtons();
            initializeMobileBarangaySelect();

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
                        syncMobileBarangaySelect();
                    }
                    return;
                }
                
                select.innerHTML = '<option value="all">All Locations</option>';
                stations.forEach(station => {
                    select.innerHTML += `<option value="${station.id}">${station.name}</option>`;
                });
                
                if (stations.length > 0) {
                    select.value = String(stations[0].id);
                    populateBarangaySelectFromStation(stations[0].id);
                }

                updateTargetMonthDisplay('current_month', null);
                updateRecommendationScopeLabel();

                await loadLatestAnalysisSnapshot();
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
                syncMobileBarangaySelect();
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
            syncMobileBarangaySelect();
        }

        function renderBarangayPredictionPanel(predictions = []) {
            const panel = document.getElementById('barangayPredictionPanel');
            if (!panel) return;

            if (!Array.isArray(predictions) || predictions.length === 0) {
                panel.innerHTML = '<div class="text-center py-8 text-gray-500">No recent saved barangay forecasts available yet.</div>';
                return;
            }

            const sorted = [...predictions].sort((a, b) => {
                const aScore = Number(a.probability_percent ?? a.risk_score ?? 0);
                const bScore = Number(b.probability_percent ?? b.risk_score ?? 0);
                return bScore - aScore;
            });

            panel.innerHTML = `
                <div style="display:grid; gap:12px;">
                    ${sorted.map((prediction, index) => {
                        const riskLevel = prediction.risk_level || prediction.assessment_level || 'Unknown';
                        const probability = prediction.probability_percent ?? prediction.risk_score ?? null;
                        const incidents = prediction.predicted_fire_incidents ?? prediction.predicted_incident_count ?? null;
                        const targetLabel = prediction.prediction_target_label || prediction.prediction_context?.target_period_label || '';
                        const updatedAt = prediction.analysis_created_at
                            ? new Date(prediction.analysis_created_at).toLocaleString()
                            : '';

                        return `
                            <div style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; background: #fff; display:flex; gap:14px; align-items:flex-start;">
                                <div style="width:32px; height:32px; border-radius:50%; background:${index < 3 ? '#dc2626' : '#f59e0b'}; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0;">
                                    ${index + 1}
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:16px; font-weight:800; color:#0f172a;">${escapeHtml(prediction.barangay_name || 'Barangay')}</div>
                                            ${prediction.city_name ? `<div style="font-size:12px; color:#64748b; margin-top:2px;">${escapeHtml(prediction.city_name)}</div>` : ''}
                                        </div>
                                        <span style="padding:6px 12px; border-radius:999px; background:#fff7ed; color:#9a3412; font-size:12px; font-weight:700; text-transform:uppercase;">
                                            ${escapeHtml(riskLevel)}
                                        </span>
                                    </div>
                                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin-top:12px;">
                                        <div style="padding:10px 12px; border-radius:12px; background:#f8fafc;">
                                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Probability</div>
                                            <div style="font-size:18px; font-weight:800; color:#0f172a; margin-top:4px;">${probability !== null ? `${escapeHtml(String(probability))}%` : 'N/A'}</div>
                                        </div>
                                        <div style="padding:10px 12px; border-radius:12px; background:#f8fafc;">
                                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Possible Incidents</div>
                                            <div style="font-size:18px; font-weight:800; color:#0f172a; margin-top:4px;">${incidents !== null ? escapeHtml(String(incidents)) : 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${(targetLabel || updatedAt) ? `
                                            <div style="margin-top:12px; font-size:12px; color:#64748b;">
                                                ${targetLabel ? `Target period: <strong>${escapeHtml(targetLabel)}</strong>` : ''}
                                            ${targetLabel && updatedAt ? ' · ' : ''}
                                            ${updatedAt ? `Saved: <strong>${escapeHtml(updatedAt)}</strong>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        async function loadLatestAnalysisSnapshot() {
            try {
                const response = await fetch('/station-admin/api/analyses/latest-snapshot', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (!result.success || !result.data) {
                    return;
                }

                updateTargetMonthDisplay(result.data?.prediction_scope || 'current_month', result.data?.prediction_context || null);
                updateHeroModelLabel(formatAiModelLabel(result.data?.ai_models?.recommendation || result.data?.ai_models?.prediction) || result.data?.model_metadata?.dataset_kind || 'Latest saved model');
                updateDashboardWithAIData(result);
                const latestModelLabel = formatAiModelLabel(result.data?.ai_models?.recommendation || result.data?.ai_models?.prediction);
                document.getElementById('selectedStationBadge').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${result.location_name || 'Latest Saved Analysis'} · Latest Saved Snapshot${latestModelLabel ? ` · <i class="fa-solid fa-microchip"></i> ${latestModelLabel}` : ''}`;
                if (currentAnalysisId) {
                    loadActionPlanChecklistPanel(currentAnalysisId);
                }
                updateRecommendationScopeLabel();
            } catch (error) {
                console.error('Latest analysis snapshot load error:', error);
            }
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

            const scopeLabel = selectedBarangayId !== 'all'
                ? `${selectedBarangayName} · ${stationName}`
                : stationName;

            const heroScope = document.getElementById('analyticsHeroScope');
            const emptyStation = document.getElementById('predictionEmptyStation');
            const emptyBarangay = document.getElementById('predictionEmptyBarangay');

            if (heroScope) heroScope.textContent = scopeLabel || 'Selected station scope';
            if (emptyStation) emptyStation.textContent = stationName || 'Selected station';
            if (emptyBarangay) emptyBarangay.textContent = selectedBarangayId !== 'all' ? selectedBarangayName : 'All barangays';
        }

        function formatAiModelLabel(model) {
            if (!model) return null;
            return model
                .split('/')
                .pop()
                .replace(/-/g, ' ');
        }

        function getSelectedScopeMeta() {
            const stationSelect = document.getElementById('stationSelect');
            const barangaySelect = document.getElementById('barangaySelect');
            return {
                stationId: stationSelect?.value || '',
                stationName: stationSelect?.options?.[stationSelect.selectedIndex]?.text || 'Selected station',
                barangayId: barangaySelect?.value || 'all',
                barangayName: barangaySelect?.options?.[barangaySelect.selectedIndex]?.text || 'All barangays',
                dateRangeLabel: document.getElementById('periodSelect')?.options?.[document.getElementById('periodSelect').selectedIndex]?.text || 'Last 30 days',
            };
        }

        function updateTargetMonthDisplay(predictionScope = 'current_month', predictionContext = null) {
            const targetMonthDisplay = document.getElementById('targetMonthDisplay');
            const heroTarget = document.getElementById('analyticsHeroTarget');
            const emptyRange = document.getElementById('predictionEmptyRange');

            const fallbackLabel = predictionScope === 'next_month' ? 'Next month forecast' : 'Current month forecast';
            const targetLabel = predictionContext?.target_month_label || fallbackLabel;

            if (targetMonthDisplay) targetMonthDisplay.value = targetLabel;
            if (heroTarget) heroTarget.textContent = targetLabel;
            if (emptyRange) emptyRange.textContent = getSelectedScopeMeta().dateRangeLabel;
        }

        function updateHeroModelLabel(label) {
            const heroModel = document.getElementById('analyticsHeroModel');
            if (heroModel) {
                heroModel.textContent = label || 'Waiting for model metadata';
            }
        }

        function formatRecommendationRunTimestamp(value) {
            if (!value) return 'Saved analysis';
            try {
                return new Date(value).toLocaleString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            } catch {
                return 'Saved analysis';
            }
        }

        function getRecommendationRunScopeLabel(group) {
            const detail = group?.detail || {};
            const predictionScope = detail.prediction_scope
                || detail?.analysis_data?.raw_data?.prediction_scope
                || detail?.predictions?.prediction_scope
                || null;
            return predictionScope === 'next_month' ? 'Predict Next Month' : 'Run AI Analysis';
        }

        function buildRecommendationRunGroup(analysisId, summary = {}, detail = null) {
            const detailData = detail?.analysis_data?.raw_data || {};
            const recommendations = Array.isArray(detail?.recommendations)
                ? detail.recommendations
                : (Array.isArray(summary?.recommendations) ? summary.recommendations : []);
            const targetLabel = detail?.prediction_context?.target_period_label
                || detailData?.prediction_context?.target_period_label
                || detail?.predictions?.prediction_context?.target_period_label
                || detail?.predictions?.forecast_window
                || null;

            return {
                id: Number(analysisId),
                createdAt: detail?.created_at || summary?.created_at || null,
                sector: detail?.sector || summary?.sector || 'Station Analysis',
                riskLevel: detail?.risk_level || summary?.risk_level || 'Unknown',
                confidenceScore: detail?.confidence_score || summary?.confidence_score || summary?.confidence_data || null,
                incidentCount: detail?.incident_count || summary?.incident_count || detail?.total_incidents || 0,
                recommendations,
                detail,
                scopeLabel: getRecommendationRunScopeLabel({ detail }),
                targetLabel,
            };
        }

        async function fetchRecommendationRunDetail(analysisId, summary = {}) {
            const cached = recommendationRunCache.get(Number(analysisId));
            if (cached?.detail) {
                return cached;
            }

            try {
                const response = await fetch(`/station-admin/api/analyses/${encodeURIComponent(analysisId)}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                if (!response.ok) {
                    throw new Error(`Failed to load analysis ${analysisId}`);
                }
                const payload = await response.json();
                if (!payload.success || !payload.data) {
                    throw new Error('Analysis detail is unavailable.');
                }

                const group = buildRecommendationRunGroup(analysisId, summary, payload.data);
                recommendationRunCache.set(Number(analysisId), group);
                return group;
            } catch (error) {
                console.error('Recommendation run detail load failed:', error);
                const fallbackGroup = buildRecommendationRunGroup(analysisId, summary, null);
                recommendationRunCache.set(Number(analysisId), fallbackGroup);
                return fallbackGroup;
            }
        }

        function openRecommendationFromRun(analysisId, recommendationIndex) {
            const group = recommendationRunCache.get(Number(analysisId));
            if (!group || !Array.isArray(group.recommendations) || !group.recommendations[recommendationIndex]) {
                showAnalyticsToast('Recommendation details are unavailable for this analysis run.', 'warning');
                return;
            }

            currentAnalysisId = Number(analysisId);
            currentRecommendations = group.recommendations;
            exploreRecommendation(group.recommendations[recommendationIndex], recommendationIndex);
        }

        function renderRecommendationRunGroups() {
            const recContainer = document.getElementById('recommendations');
            if (!recContainer) return;

            if (!recommendationRunGroups.length) {
                recContainer.innerHTML = `
                    <div class="analytics-empty-state">
                        <div class="analytics-empty-state__title">No AI recommendations yet.</div>
                        <p class="analytics-empty-state__copy">Run the station analysis first to generate grouped recommendation sets for each saved forecast run.</p>
                    </div>
                `;
                return;
            }

            recContainer.innerHTML = `
                <div class="recommendation-run-list">
                    ${recommendationRunGroups.map((group) => {
                        const isOpen = Number(openRecommendationRunId) === Number(group.id);
                        const recommendations = Array.isArray(group.recommendations) ? group.recommendations : [];
                        const summaryLine = `${group.sector} · ${formatRecommendationRunTimestamp(group.createdAt)}`;
                        const chips = [
                            group.targetLabel ? `<span class="recommendation-run-chip"><i class="fa-solid fa-calendar-days" style="color:#dc2626;"></i>${escapeHtml(group.targetLabel)}</span>` : '',
                            `<span class="recommendation-run-chip"><i class="fa-solid fa-list-check" style="color:#2563eb;"></i>${escapeHtml(String(recommendations.length))} recommendation(s)</span>`,
                            group.riskLevel ? `<span class="recommendation-run-chip"><i class="fa-solid fa-shield-heart" style="color:#f59e0b;"></i>${escapeHtml(group.riskLevel)}</span>` : '',
                            group.confidenceScore !== null && group.confidenceScore !== undefined ? `<span class="recommendation-run-chip"><i class="fa-solid fa-crosshairs" style="color:#059669;"></i>${escapeHtml(String(group.confidenceScore))}% confidence</span>` : ''
                        ].filter(Boolean).join('');

                        let contentHtml = `
                            <div class="recommendation-run-summary">
                                ${escapeHtml(group.scopeLabel)} recommendations for ${escapeHtml(group.sector)}.
                            </div>
                        `;

                        if (!recommendations.length) {
                            contentHtml += `
                                <div class="analytics-empty-state" style="padding:14px 16px;">
                                    <div class="analytics-empty-state__title">No recommendations saved for this run.</div>
                                </div>
                            `;
                        } else {
                            contentHtml += recommendations.map((rec, idx) => {
                                const meta = extractRecommendationMeta(rec);
                                const evidenceParts = [
                                    meta.focus ? meta.focus : null,
                                    meta.place ? meta.place : null,
                                    !meta.place && meta.barangay ? meta.barangay : null,
                                ].filter(Boolean);
                                const evidenceLine = evidenceParts.length
                                    ? `<div style="font-size:12px; color:#64748b; margin-top:8px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                            <i class="fa-solid fa-magnifying-glass-chart" style="color:${meta.accent};"></i>
                                            <span><strong style="color:#475569;">Based on:</strong> ${escapeHtml(evidenceParts.join(' • '))}</span>
                                       </div>`
                                    : '';
                                let priorityClass = 'medium';
                                let priorityLabel = 'MEDIUM';
                                if (rec.priority === 'Critical') { priorityClass = 'critical'; priorityLabel = 'CRITICAL'; }
                                else if (rec.priority === 'High') { priorityClass = 'high'; priorityLabel = 'HIGH'; }
                                else if (rec.priority === 'Low') { priorityClass = 'low'; priorityLabel = 'LOW'; }

                                const metaChips = [
                                    meta.barangay ? `
                                        <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; font-size:12px; font-weight:700; color:#334155;">
                                            <i class="fa-solid fa-location-dot" style="color:#dc2626;"></i>${escapeHtml(meta.barangay)}
                                        </span>
                                    ` : '',
                                    meta.place ? `
                                        <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; font-size:12px; font-weight:700; color:#334155;">
                                            <i class="fa-solid fa-road" style="color:#2563eb;"></i>${escapeHtml(meta.place)}
                                        </span>
                                    ` : '',
                                    meta.focus ? `
                                        <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; font-size:12px; font-weight:700; color:#334155;">
                                            <i class="fa-solid ${meta.icon}" style="color:${meta.accent};"></i>${escapeHtml(meta.focus)}
                                        </span>
                                    ` : '',
                                ].filter(Boolean).join('');

                                return `
                                    <div class="recommendation-card ${priorityClass}">
                                        <div style="display: flex; gap: 12px;">
                                            <div style="width: 28px; height: 28px; background: ${priorityClass === 'critical' ? '#dc2626' : (priorityClass === 'high' ? '#f59e0b' : (priorityClass === 'medium' ? '#10b981' : '#94a3b8'))}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">${idx + 1}</div>
                                            <div style="flex:1;">
                                                <div style="display:flex; align-items:flex-start; gap:10px;">
                                                    <div style="width:34px; height:34px; border-radius:12px; background:${meta.accent}15; color:${meta.accent}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                        <i class="fa-solid ${meta.icon}"></i>
                                                    </div>
                                                    <div style="flex:1;">
                                                        <strong style="display:block; color:#0f172a;">${escapeHtml(rec.title || 'Recommendation')}</strong>
                                                        ${metaChips ? `<div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;">${metaChips}</div>` : ''}
                                                        ${evidenceLine}
                                                    </div>
                                                </div>
                                                <p style="color: #4b5563; font-size: 13px; margin-top: 4px;">${escapeHtml(rec.description || '')}</p>
                                                <span class="priority-badge priority-${priorityClass}">${priorityLabel} PRIORITY</span>
                                                <button class="explore-btn" onclick="openRecommendationFromRun(${group.id}, ${idx})">
                                                    <i class="fa-solid fa-chart-simple"></i> Explore & Get Detailed Suggestions
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        }

                        return `
                            <div class="recommendation-run-group ${isOpen ? 'is-open' : ''}">
                                <button type="button" class="recommendation-run-toggle" onclick="toggleRecommendationRunGroup(${group.id})">
                                    <div class="recommendation-run-meta">
                                        <div class="recommendation-run-title">
                                            <i class="fa-solid fa-layer-group" style="color:#dc2626;"></i>
                                            <span>${escapeHtml(group.scopeLabel)}</span>
                                        </div>
                                        <div class="recommendation-run-subtitle">${escapeHtml(summaryLine)}</div>
                                        <div class="recommendation-run-chips">${chips}</div>
                                    </div>
                                    <span class="recommendation-run-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                                </button>
                                <div class="recommendation-run-content">${contentHtml}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        async function toggleRecommendationRunGroup(analysisId) {
            const normalizedId = Number(analysisId);
            openRecommendationRunId = Number(openRecommendationRunId) === normalizedId ? null : normalizedId;
            renderRecommendationRunGroups();

            if (!openRecommendationRunId) {
                return;
            }

            const existingGroup = recommendationRunGroups.find((group) => Number(group.id) === normalizedId);
            if (existingGroup?.detail && Array.isArray(existingGroup.recommendations) && existingGroup.recommendations.length) {
                return;
            }

            const updatedGroup = await fetchRecommendationRunDetail(normalizedId, existingGroup || {});
            recommendationRunGroups = recommendationRunGroups.map((group) =>
                Number(group.id) === normalizedId ? { ...group, ...updatedGroup } : group
            );
            renderRecommendationRunGroups();
        }

        async function loadRecommendationRunGroups(priorityAnalysisData = null) {
            const recContainer = document.getElementById('recommendations');
            if (recContainer) {
                recContainer.innerHTML = `
                    <div class="analytics-empty-state">
                        <div class="analytics-empty-state__title">Loading recommendation runs...</div>
                        <p class="analytics-empty-state__copy">Preparing grouped recommendation sets from recent forecast runs.</p>
                    </div>
                `;
            }

            try {
                const response = await fetch('/station-admin/api/analyses/history', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                if (!response.ok) {
                    throw new Error(`Failed to load analysis history (${response.status})`);
                }

                const payload = await response.json();
                const history = Array.isArray(payload?.data) ? payload.data : [];
                const summaryMap = new Map(history.map((item) => [Number(item.analysis_id), item]));

                if (priorityAnalysisData?.analysis_id) {
                    summaryMap.set(Number(priorityAnalysisData.analysis_id), {
                        analysis_id: priorityAnalysisData.analysis_id,
                        sector: priorityAnalysisData.sector || priorityAnalysisData.location_name || document.getElementById('stationSelect')?.options?.[document.getElementById('stationSelect')?.selectedIndex]?.text || 'Selected station',
                        risk_level: priorityAnalysisData.risk_level || null,
                        confidence_score: priorityAnalysisData.confidence_score || null,
                        incident_count: priorityAnalysisData.total_incidents || 0,
                        created_at: new Date().toISOString(),
                    });

                    const priorityGroup = buildRecommendationRunGroup(priorityAnalysisData.analysis_id, summaryMap.get(Number(priorityAnalysisData.analysis_id)), priorityAnalysisData);
                    recommendationRunCache.set(Number(priorityAnalysisData.analysis_id), priorityGroup);
                }

                const groups = await Promise.all(
                    Array.from(summaryMap.values())
                        .sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime())
                        .slice(0, 10)
                        .map((summary) => fetchRecommendationRunDetail(summary.analysis_id, summary))
                );

                recommendationRunGroups = groups.sort((a, b) => new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime());
                if (!openRecommendationRunId && recommendationRunGroups.length) {
                    openRecommendationRunId = recommendationRunGroups[0].id;
                }
                renderRecommendationRunGroups();
            } catch (error) {
                console.error('Recommendation run history load error:', error);
                if (recContainer) {
                    recContainer.innerHTML = `
                        <div class="analytics-empty-state">
                            <div class="analytics-empty-state__title">Unable to load grouped recommendation runs.</div>
                            <p class="analytics-empty-state__copy">${escapeHtml(error.message || 'Please try again.')}</p>
                        </div>
                    `;
                }
            }
        }

        function setInsightList(containerId, insights = [], fallback = 'No insight available yet.') {
            const container = document.getElementById(containerId);
            if (!container) return;

            const items = Array.isArray(insights) ? insights.filter(Boolean) : [];
            if (!items.length) {
                container.innerHTML = `<div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>${escapeHtml(fallback)}</span></div>`;
                return;
            }

            container.innerHTML = items.map((insight) => `
                <div class="analytics-insight">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>${escapeHtml(insight)}</span>
                </div>
            `).join('');
        }

        function setPredictionEmptyState(messageTitle, messageCopy) {
            const predictionContainer = document.getElementById('aiPrediction');
            if (!predictionContainer) return;

            const scope = getSelectedScopeMeta();
            predictionContainer.innerHTML = `
                <div class="analytics-empty-state">
                    <div class="analytics-empty-state__title">${escapeHtml(messageTitle)}</div>
                    <p class="analytics-empty-state__copy">${escapeHtml(messageCopy)}</p>
                    <div class="analytics-empty-state__meta">
                        <span class="analytics-empty-pill"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(scope.stationName)}</span>
                        <span class="analytics-empty-pill"><i class="fa-solid fa-map"></i> ${escapeHtml(scope.barangayId !== 'all' ? scope.barangayName : 'All barangays')}</span>
                        <span class="analytics-empty-pill"><i class="fa-solid fa-calendar-days"></i> ${escapeHtml(scope.dateRangeLabel)}</span>
                    </div>
                </div>
            `;
        }

        function renderPredictionSummary(analysisData) {
            const prediction = analysisData.predictions || null;
            const chartContext = analysisData.chart_context || {};
            const dailyContext = chartContext.daily || {};
            const previousSeries = Array.isArray(dailyContext.previous_period) ? dailyContext.previous_period : [];
            const currentSeries = Array.isArray(dailyContext.current_period) ? dailyContext.current_period : [];
            const previousTotal = previousSeries.reduce((sum, value) => sum + Number(value || 0), 0);
            const currentTotal = currentSeries.reduce((sum, value) => sum + Number(value || 0), 0);
            const trendDelta = previousTotal > 0 ? (((currentTotal - previousTotal) / previousTotal) * 100) : null;

            const hotspot = analysisData.selected_barangay_prediction
                || (Array.isArray(analysisData.barangay_predictions)
                    ? [...analysisData.barangay_predictions].sort((a, b) => Number(b.probability_percent || 0) - Number(a.probability_percent || 0))[0]
                    : null);

            const probability = hotspot?.probability_percent ?? prediction?.model_prediction?.probability_percent ?? null;
            const predictedCount = prediction?.predicted_fire_incidents ?? hotspot?.predicted_fire_incidents ?? prediction?.model_prediction?.predicted_incident_count ?? null;
            const confidenceScore = analysisData.confidence_score ?? prediction?.model_prediction?.confidence_band ?? null;
            const drivers = Array.isArray(prediction?.drivers) ? prediction.drivers : [];

            document.getElementById('summaryProbability').textContent = probability !== null ? `${Number(probability).toFixed(1)}%` : 'Unavailable';
            document.getElementById('summaryIncidentCount').textContent = predictedCount !== null ? String(predictedCount) : 'Unavailable';
            document.getElementById('riskLabel').textContent = analysisData.risk_level || prediction?.outlook || 'Unavailable';
            document.getElementById('confidenceScore').textContent = confidenceScore !== null
                ? (typeof confidenceScore === 'number' ? `${confidenceScore}%` : String(confidenceScore))
                : 'Unavailable';

            const trendDirection = document.getElementById('summaryTrendDirection');
            const trendCopy = document.getElementById('summaryTrendCopy');
            const trendIndicator = document.getElementById('summaryTrendIndicator');
            if (trendDirection && trendCopy && trendIndicator) {
                if (trendDelta === null || !Number.isFinite(trendDelta)) {
                    trendDirection.textContent = 'Comparison unavailable';
                    trendCopy.textContent = 'A previous seven-day comparison will appear once the backend has both current and previous actual incident periods available.';
                    trendIndicator.innerHTML = '<i class="fa-solid fa-chart-simple"></i><span>No previous period data available</span>';
                } else {
                    const directionLabel = trendDelta > 0 ? 'Rising against previous period' : (trendDelta < 0 ? 'Lower than previous period' : 'Holding steady');
                    trendDirection.textContent = directionLabel;
                    trendCopy.textContent = `Current seven-day activity totals ${currentTotal} incidents, compared with ${previousTotal} incidents in the previous seven-day period.`;
                    trendIndicator.innerHTML = `<i class="fa-solid ${trendDelta > 0 ? 'fa-arrow-trend-up' : (trendDelta < 0 ? 'fa-arrow-trend-down' : 'fa-equals')}"></i><span>${trendDelta > 0 ? '+' : ''}${trendDelta.toFixed(1)}% versus previous period</span>`;
                }
            }

            document.getElementById('summaryTopBarangay').textContent = hotspot?.barangay_name || 'No hotspot yet';
            document.getElementById('summaryTopBarangayCopy').textContent = hotspot
                ? `Forecast probability ${Number(hotspot.probability_percent || 0).toFixed(1)}%${hotspot.prediction_target_label ? ` for ${hotspot.prediction_target_label}` : ''}.`
                : 'Run an analysis to identify the highest-risk barangay in the current station coverage.';

            const driverContainer = document.getElementById('summaryDrivers');
            if (driverContainer) {
                const items = drivers.slice(0, 4);
                driverContainer.innerHTML = items.length
                    ? items.map((driver) => `<span class="prediction-driver-chip"><i class="fa-solid fa-circle-nodes"></i>${escapeHtml(typeof driver === 'string' ? driver : (driver?.summary || 'Driver'))}</span>`).join('')
                    : '<span class="prediction-driver-chip"><i class="fa-solid fa-circle-info"></i>No main contributing factors returned yet.</span>';
            }
        }

        function renderCharts(analysisData) {
            const chartContext = analysisData.chart_context || {};
            const daily = chartContext.daily || {};
            const hourly = chartContext.hourly || {};
            const compactChart = isAnalyticsMobile();
            const chartFontSize = compactChart ? 10 : 12;
            const chartPointRadius = compactChart ? 2 : 4;
            const chartLegend = {
                position: compactChart ? 'bottom' : 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: compactChart ? 7 : 10,
                    padding: compactChart ? 8 : 12,
                    font: { size: chartFontSize, weight: compactChart ? '600' : '700' }
                }
            };
            const chartTicks = {
                color: '#64748b',
                font: { size: chartFontSize },
                maxTicksLimit: compactChart ? 5 : 8
            };

            if (hourlyChart) hourlyChart.destroy();
            if (dailyChart) dailyChart.destroy();

            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: daily.labels || [],
                    datasets: [
                        {
                            label: 'Previous period actual',
                            data: daily.previous_period || [],
                            borderColor: '#94a3b8',
                            backgroundColor: 'rgba(148,163,184,0.12)',
                            borderDash: [6, 6],
                            tension: 0.35,
                            pointRadius: compactChart ? 1.5 : 3,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Current actual',
                            data: daily.current_period || [],
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220,38,38,0.12)',
                            tension: 0.35,
                            pointRadius: chartPointRadius,
                            pointBackgroundColor: '#dc2626',
                            yAxisID: 'y',
                        },
                        {
                            label: 'Predicted probability (%)',
                            data: daily.predicted_probability || [],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.14)',
                            tension: 0.2,
                            pointRadius: 0,
                            borderWidth: compactChart ? 1.5 : 2,
                            yAxisID: 'y1',
                            spanGaps: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: chartLegend,
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return context.dataset.yAxisID === 'y1'
                                        ? `${label}: ${Number(value || 0).toFixed(1)}%`
                                        : `${label}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            ticks: chartTicks,
                            grid: { drawOnChartArea: false },
                            title: { display: !compactChart, text: 'Predicted probability (%)' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                            title: { display: !compactChart, text: 'Date' }
                        }
                    }
                }
            });

            const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
            hourlyChart = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: hourly.labels || [],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Previous month baseline',
                            data: hourly.historical_baseline || [],
                            borderColor: '#475569',
                            backgroundColor: 'rgba(71,85,105,0.12)',
                            borderDash: [5, 5],
                            tension: 0.3,
                            pointRadius: 0,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'Current month actual',
                            data: hourly.current_period || [],
                            backgroundColor: 'rgba(37,99,235,0.78)',
                            borderRadius: compactChart ? 4 : 6,
                            barPercentage: compactChart ? 0.72 : 0.82,
                            categoryPercentage: compactChart ? 0.72 : 0.82,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: 'Predicted peak-hour probability (%)',
                            data: hourly.predicted_probability || [],
                            borderColor: '#ea580c',
                            backgroundColor: 'rgba(234,88,12,0.12)',
                            tension: 0,
                            spanGaps: true,
                            pointRadius: chartPointRadius,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: chartLegend,
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return context.dataset.yAxisID === 'y1'
                                        ? `${label}: ${Number(value || 0).toFixed(1)}%`
                                        : `${label}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident frequency' }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            ticks: chartTicks,
                            grid: { drawOnChartArea: false },
                            title: { display: !compactChart, text: 'Predicted probability (%)' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: 0 },
                            title: { display: !compactChart, text: 'Hour of day' }
                        }
                    }
                }
            });

            const chartInsights = Array.isArray(chartContext.insights) ? chartContext.insights : [];
            setInsightList(
                'dailyTrendInsights',
                [
                    chartInsights[0],
                    daily?.missing?.predicted_probability ? 'Predicted probability series is unavailable for this selection, so only actual incident patterns are shown.' : null,
                    chartInsights[2],
                ].filter(Boolean),
                'Daily comparison insight is unavailable for the current dataset.'
            );
            setInsightList(
                'hourlyPatternInsights',
                [
                    chartInsights[3],
                    hourly?.missing?.historical_baseline ? 'Historical baseline data is unavailable for the hourly comparison chart.' : null,
                    hourly?.missing?.predicted_probability ? 'Predicted peak-hour probability data is unavailable for the current selection.' : null,
                ].filter(Boolean),
                'Hourly pattern insight is unavailable for the current dataset.'
            );
        }

        function showPredictionApiError(message, details = null) {
            const predictionContainer = document.getElementById('aiPrediction');
            const fallbackMessage = message || 'Prediction could not be completed right now.';
            const detailHtml = details
                ? `<div style="margin-top:8px;font-size:13px;color:#7f1d1d;">${details}</div>`
                : '';

            if (predictionContainer) {
                predictionContainer.innerHTML = `<div style="border:1px solid rgba(220,38,38,0.18); background:#fef2f2; color:#991b1b; border-radius:16px; padding:18px 20px;"><div style="font-weight:800; font-size:15px;">Prediction service unavailable</div><div style="margin-top:6px; font-size:14px; line-height:1.5;">${fallbackMessage}</div>${detailHtml}</div>`;
            }

            showAnalyticsToast(details ? `${fallbackMessage} ${details}` : fallbackMessage, 'error');
        }

        async function runAIAnalysis(locationId, predictionScope = 'current_month') {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            const periodDays = document.getElementById('periodSelect').value;

            try {
                const response = await fetch('/api/station-admin/predictions', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        barangay_id: document.getElementById('barangaySelect')?.value || 'all',
                        days: parseInt(periodDays),
                        prediction_scope: predictionScope
                    })
                });
                
                if (!response.ok) {
                    let errorPayload = null;
                    let errorText = '';

                    try {
                        errorPayload = await response.json();
                    } catch (parseError) {
                        errorText = await response.text();
                    }

                    console.error('Error response:', errorPayload || errorText);
                    throw new Error(
                        errorPayload?.details
                        || errorPayload?.message
                        || errorText
                        || `HTTP error! status: ${response.status}`
                    );
                }
                
                const result = await response.json();
                console.log('AI Analysis Result:', result);
                
                if (result.success) {
                    updateTargetMonthDisplay(predictionScope, result.data?.prediction_context || null);
                    updateDashboardWithAIData(result);
                    const scopeLabel = predictionScope === 'next_month' ? 'Next Month Forecast' : 'Current Month Forecast';
                    const aiModelLabel = formatAiModelLabel(result.data?.ai_models?.recommendation || result.data?.ai_models?.prediction);
                    document.getElementById('selectedStationBadge').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${result.location_name || 'Selected Location'} · ${scopeLabel}${aiModelLabel ? ` · <i class="fa-solid fa-microchip"></i> ${aiModelLabel}` : ''}`;
                    updateRecommendationScopeLabel();
                } else {
                    showPredictionApiError(result.message || 'Analysis failed', result.details || null);
                }
            } catch (error) {
                console.error('Analysis error:', error);
                showPredictionApiError('Failed to run AI analysis.', error.message);
            } finally {
                overlay.classList.remove('active');
            }
        }

        async function exploreRecommendation(recommendation, index) {
            const modal = document.getElementById('exploreModal');
            const modalBody = document.getElementById('exploreModalBody');
            const recommendationTitle = getRecommendationTitle(recommendation);
            
            modal.classList.add('show');
            modalBody.setAttribute('data-state', 'loading');
            modalBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 40px; height: 40px;"></div>
                    <p class="mt-3 text-gray-500">Generating detailed implementation plan for "${escapeHtml(recommendationTitle)}"... </p>
                </div>
            `;
            replaceChiefAnalyticsFaIcons(modalBody);
            
            try {
                const response = await fetch('/station-admin/api/explore-recommendation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        recommendation_title: recommendationTitle,
                        recommendation_description: getRecommendationDescription(recommendation),
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
                    displayExploreResults(result.data, recommendation, result.source || null, result.note || null, result.model_used || null);
                    if (currentAnalysisId) {
                        loadActionPlanChecklistPanel(currentAnalysisId);
                    }
                } else {
                    modalBody.setAttribute('data-state', 'error');
                    modalBody.innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i>
                            <p>${escapeHtml(result.message || 'Unable to generate the implementation plan right now.')}</p>
                            <button type="button" class="analytics-confirm__button analytics-confirm__button--accept" style="margin-top:14px;" onclick="exploreRecommendation(currentRecommendations[${index}], ${index})">Retry</button>
                        </div>
                    `;
                    replaceChiefAnalyticsFaIcons(modalBody);
                    showAnalyticsToast(result.message || 'Implementation plan is unavailable right now.', 'warning');
                }
            } catch (error) {
                console.error('Explore error:', error);
                modalBody.setAttribute('data-state', 'error');
                modalBody.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i>
                        <p>Failed to generate implementation plan. Please try again.</p>
                        <button type="button" class="analytics-confirm__button analytics-confirm__button--accept" style="margin-top:14px;" onclick="exploreRecommendation(currentRecommendations[${index}], ${index})">Retry</button>
                    </div>
                `;
                replaceChiefAnalyticsFaIcons(modalBody);
                showAnalyticsToast('Implementation plan request failed. You can retry from the modal.', 'error');
            }
        }
        
        function displayExploreResults(data, recommendation, source = null, note = null, modelUsed = null) {
            const modelLabel = formatAiModelLabel(modelUsed);
            const modalBody = document.getElementById('exploreModalBody');
            const recommendationTitle = getRecommendationTitle(recommendation);
            const recommendationDescription = getRecommendationDescription(recommendation);
            modalBody.setAttribute('data-state', 'ready');
            
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
                    <p style="font-size: 14px; color: #475569; margin-bottom: 8px;"><strong>Title:</strong> ${escapeHtml(recommendationTitle)}</p>
                    <p style="font-size: 13px; color: #64748b;"><strong>Description:</strong> ${escapeHtml(recommendationDescription)}</p>
                </div>
                ${source === 'fallback' ? `
                    <div style="margin: 0 0 18px; padding: 12px 14px; border-radius: 14px; background: #fff7ed; border: 1px solid #fdba74; color: #9a3412;">
                        <div style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:13px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Context-based fallback plan
                        </div>
                        <div style="font-size:13px; margin-top:6px; line-height:1.6;">
                            ${escapeHtml(note || 'The AI implementation planner did not return a usable structured response, so this plan was generated from the current recommendation, forecast, and risk context.')}
                        </div>
                    </div>
                ` : ''}
                ${source === 'ai_api' && modelLabel ? `
                    <div style="margin: 0 0 18px; padding: 12px 14px; border-radius: 14px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;">
                        <div style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:13px;">
                            <i class="fa-solid fa-microchip"></i>
                            AI model used
                        </div>
                        <div style="font-size:13px; margin-top:6px; line-height:1.6;">${escapeHtml(modelLabel)}</div>
                    </div>
                ` : ''}
                ${(data.how || data.who || data.when) ? `
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin:0 0 18px;">
                        ${data.how ? `
                            <div style="padding:14px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                                <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.08em; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-gears" style="color:#0f766e;"></i> How
                                </div>
                                <div style="font-size:13px; color:#334155; margin-top:8px; line-height:1.6;">${escapeHtml(data.how)}</div>
                            </div>
                        ` : ''}
                        ${data.who ? `
                            <div style="padding:14px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                                <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.08em; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-user-group" style="color:#2563eb;"></i> Who
                                </div>
                                <div style="font-size:13px; color:#334155; margin-top:8px; line-height:1.6;">${escapeHtml(data.who)}</div>
                            </div>
                        ` : ''}
                        ${data.when ? `
                            <div style="padding:14px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                                <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.08em; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-calendar-check" style="color:#dc2626;"></i> When
                                </div>
                                <div style="font-size:13px; color:#334155; margin-top:8px; line-height:1.6;">${escapeHtml(data.when)}</div>
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                
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
            replaceChiefAnalyticsFaIcons(modalBody);
        }

        function getRecommendationTitle(recommendation = {}) {
            return recommendation?.title
                || recommendation?.recommendation_title
                || recommendation?.source_title
                || recommendation?.plan_title
                || recommendation?.focus
                || 'Recommendation';
        }

        function getRecommendationDescription(recommendation = {}) {
            return recommendation?.description
                || recommendation?.plan_description
                || recommendation?.summary
                || recommendation?.recommendation_description
                || recommendation?.details
                || 'No additional recommendation description was provided.';
        }
        
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = cleanGeneratedText(text);
            return div.innerHTML;
        }

        function extractRecommendationMeta(recommendation) {
            const title = getRecommendationTitle(recommendation);
            const description = getRecommendationDescription(recommendation);
            const combined = `${title}. ${description}`;

            const placeMatch = combined.match(/\bin\s+([^.,]+(?:,\s*[^.,]+)?)/i)
                || combined.match(/\bat\s+([^.,]+(?:,\s*[^.,]+)?)/i);
            const barangayMatch = combined.match(/\b([A-Z][A-Za-z0-9\s-]+)\s*,\s*([A-Z][A-Za-z0-9\s-]+)/);
            const typeMatch = combined.match(/\b(octopus wiring|linear wiring|exposed wires|overloaded outlets|illegal electrical connections|damaged extension cords|improper splicing|outdated electrical systems|leaking gas hose|LPG|gas|cooking setups|blocked hydrant|hydrant|water access|housing spacing|exit access|combustible building conditions|informal settlement|poor waste disposal|combustible materials)\b/i);

            const normalized = combined.toLowerCase();
            let icon = 'fa-circle-info';
            let accent = '#475569';

            if (normalized.includes('wiring') || normalized.includes('electrical')) {
                icon = 'fa-bolt';
                accent = '#d97706';
            } else if (normalized.includes('lpg') || normalized.includes('gas') || normalized.includes('cooking')) {
                icon = 'fa-fire-burner';
                accent = '#dc2626';
            } else if (normalized.includes('hydrant') || normalized.includes('water access')) {
                icon = 'fa-faucet-drip';
                accent = '#2563eb';
            } else if (normalized.includes('housing') || normalized.includes('structural') || normalized.includes('occupancy')) {
                icon = 'fa-house-crack';
                accent = '#7c3aed';
            }

            return {
                place: placeMatch ? placeMatch[1].trim() : null,
                barangay: barangayMatch ? barangayMatch[2].trim() : null,
                focus: typeMatch ? typeMatch[1].trim() : null,
                icon,
                accent,
            };
        }
        
        function closeExploreModal() {
            document.getElementById('exploreModal').classList.remove('show');
        }

        function closePredictionDetailModal() {
            document.getElementById('predictionDetailModal').classList.remove('show');
        }

        function getChiefAnalyticsMaterialSymbol(iconName) {
            const icons = {
                'fa-arrow-right': 'arrow_forward',
                'fa-arrow-right-from-bracket': 'logout',
                'fa-arrow-trend-down': 'trending_down',
                'fa-arrow-trend-up': 'trending_up',
                'fa-arrows-spin': 'autorenew',
                'fa-bars': 'menu',
                'fa-bolt': 'bolt',
                'fa-brain': 'psychology',
                'fa-bullhorn': 'campaign',
                'fa-calendar-check': 'event_available',
                'fa-calendar-days': 'calendar_month',
                'fa-calendar-plus': 'event',
                'fa-calendar-week': 'calendar_view_week',
                'fa-chart-line': 'show_chart',
                'fa-chart-simple': 'bar_chart',
                'fa-check': 'check',
                'fa-chevron-down': 'expand_more',
                'fa-circle': 'radio_button_checked',
                'fa-circle-check': 'task_alt',
                'fa-circle-exclamation': 'error',
                'fa-circle-info': 'info',
                'fa-circle-nodes': 'hub',
                'fa-clipboard-list': 'assignment',
                'fa-clock': 'schedule',
                'fa-clock-rotate-left': 'history',
                'fa-crosshairs': 'my_location',
                'fa-database': 'database',
                'fa-file-certificate': 'workspace_premium',
                'fa-fire': 'local_fire_department',
                'fa-fire-burner': 'local_fire_department',
                'fa-fire-flame-curved': 'local_fire_department',
                'fa-faucet-drip': 'water_drop',
                'fa-gears': 'settings',
                'fa-gauge-high': 'speed',
                'fa-house-crack': 'home',
                'fa-info': 'info',
                'fa-layer-group': 'layers',
                'fa-lightbulb': 'lightbulb',
                'fa-list-check': 'checklist',
                'fa-location-dot': 'location_on',
                'fa-magnifying-glass-chart': 'query_stats',
                'fa-map': 'map',
                'fa-microchip': 'memory',
                'fa-quote-left': 'format_quote',
                'fa-road': 'route',
                'fa-rotate': 'refresh',
                'fa-shield-halved': 'shield',
                'fa-shield-heart': 'health_and_safety',
                'fa-sign-out-alt': 'logout',
                'fa-sliders': 'tune',
                'fa-spinner': 'progress_activity',
                'fa-times': 'close',
                'fa-toolbox': 'inventory',
                'fa-triangle-exclamation': 'warning',
                'fa-user-group': 'groups',
                'fa-users': 'groups',
                'fa-wand-magic-sparkles': 'auto_fix_high',
                'fa-wave-square': 'monitoring',
                'fa-xmark': 'close',
            };

            return icons[iconName] || null;
        }

        function replaceChiefAnalyticsFaIcons(root = document) {
            const icons = root.querySelectorAll('i[class*="fa-"]:not([data-chief-inline-fa-processed])');
            const ignored = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-spin', 'fa-pulse', 'fa-fw'];

            icons.forEach((icon) => {
                const iconClass = Array.from(icon.classList).find((cls) => cls.startsWith('fa-') && !ignored.includes(cls));
                const materialSymbol = iconClass ? getChiefAnalyticsMaterialSymbol(iconClass) : null;

                if (!materialSymbol) {
                    icon.setAttribute('data-chief-inline-fa-processed', '1');
                    return;
                }

                const replacement = document.createElement('span');
                replacement.className = Array.from(icon.classList)
                    .filter((cls) => !['fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands', 'fa-pulse', 'fa-spin', iconClass].includes(cls))
                    .join(' ');
                replacement.className = `${replacement.className} inline-fa-icon analytics-google-symbol`.trim();

                if (['fa-spinner', 'fa-arrows-spin', 'fa-rotate'].includes(iconClass)) {
                    replacement.classList.add('icon-spin');
                }

                replacement.setAttribute('aria-hidden', icon.getAttribute('aria-hidden') || 'true');

                const style = icon.getAttribute('style');
                if (style) {
                    replacement.setAttribute('style', style);
                }

                replacement.textContent = materialSymbol;
                icon.setAttribute('data-chief-inline-fa-processed', '1');
                icon.replaceWith(replacement);
            });
        }

        function openPredictionDetailModal() {
            if (!currentPredictionDetails) {
                return;
            }

            const modal = document.getElementById('predictionDetailModal');
            const body = document.getElementById('predictionDetailModalBody');
            const prediction = currentPredictionDetails.predictions || null;
            const recommendations = Array.isArray(currentPredictionDetails.recommendations) ? currentPredictionDetails.recommendations : [];
            const weatherSummary = prediction?.weather_context?.summary || currentPredictionDetails.weather_context?.summary || null;
            const drivers = Array.isArray(prediction?.drivers) ? prediction.drivers : [];
            const likelyPeakHours = Array.isArray(prediction?.likely_peak_hours) ? prediction.likely_peak_hours : [];
            const focusLabel = currentPredictionDetails.prediction_context?.target_period_label || prediction?.prediction_context?.target_period_label || prediction?.forecast_window || 'Forecast window';
            const modelMetadata = currentPredictionDetails.model_metadata || null;
            const workflowContext = currentPredictionDetails.workflow_context || null;
            const selectedBarangay = currentPredictionDetails.selected_barangay_prediction || null;
            const topHotspot = !selectedBarangay && Array.isArray(currentBarangayPredictions) && currentBarangayPredictions.length
                ? [...currentBarangayPredictions].sort((a, b) => Number(b.probability_percent ?? 0) - Number(a.probability_percent ?? 0))[0]
                : null;
            const focusBarangayLabel = selectedBarangay?.barangay_name
                ? selectedBarangay.barangay_name
                : (topHotspot?.barangay_name || null);
            const focusBarangayContext = selectedBarangay?.barangay_name
                ? 'Selected barangay focus'
                : (topHotspot?.barangay_name ? 'Highest hotspot barangay' : null);

            body.innerHTML = `
                <div style="display:grid; gap:18px;">
                    <div style="padding:18px; border-radius:18px; background: linear-gradient(135deg, rgba(220,38,38,0.08), rgba(245,158,11,0.12)); border:1px solid rgba(245,158,11,0.24);">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                            <div>
                                <div style="font-size:12px; font-weight:800; color:#9a3412; text-transform:uppercase; letter-spacing:0.08em;">Forecast Window</div>
                                <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">${escapeHtml(focusLabel)}</div>
                            </div>
                            <span style="padding:8px 14px; border-radius:999px; background:rgba(220,38,38,0.12); color:#b91c1c; font-size:12px; font-weight:800; text-transform:uppercase;">
                                ${escapeHtml(prediction?.outlook || 'Forecast')}
                            </span>
                        </div>
                        ${focusBarangayLabel ? `
                            <div style="margin-top:12px; display:inline-flex; gap:8px; align-items:center; padding:8px 12px; border-radius:999px; background:#fff; border:1px solid rgba(220,38,38,0.12); color:#7f1d1d; font-size:12px; font-weight:700;">
                                <span>${escapeHtml(focusBarangayContext || 'Barangay')}</span>
                                <span>&bull;</span>
                                <span>${escapeHtml(focusBarangayLabel)}</span>
                            </div>
                        ` : ''}
                        <p style="margin-top:14px; font-size:14px; line-height:1.7; color:#334155;">${escapeHtml(prediction?.summary || 'No detailed summary available.')}</p>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                        <div style="padding:14px; border-radius:14px; background:#fff7ed; border:1px solid #fed7aa;">
                            <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase;">Possible Incidents</div>
                            <div style="font-size:28px; font-weight:900; color:#7c2d12; margin-top:6px;">${prediction?.predicted_fire_incidents ?? 'N/A'}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#eff6ff; border:1px solid #bfdbfe;">
                            <div style="font-size:11px; font-weight:800; color:#1d4ed8; text-transform:uppercase;">Estimated Damage</div>
                            <div style="font-size:22px; font-weight:900; color:#1e3a8a; margin-top:6px;">${prediction?.predicted_damage !== undefined && prediction?.predicted_damage !== null ? `PHP ${escapeHtml(Number(prediction.predicted_damage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}` : 'N/A'}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                            <div style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Peak Hours</div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; margin-top:6px;">${likelyPeakHours.length ? escapeHtml(likelyPeakHours.join(', ')) : 'N/A'}</div>
                        </div>
                    </div>

                    ${drivers.length ? `
                        <div style="padding:16px; border-radius:16px; background:#fff; border:1px solid #e2e8f0;">
                            <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">Primary Drivers</div>
                            <ul style="margin:0; padding-left:18px; color:#334155; line-height:1.7;">
                                ${drivers.map((driver) => `<li>${escapeHtml(driver)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}

                    ${weatherSummary ? `
                        <div style="padding:16px; border-radius:16px; background:#fff7ed; border:1px solid #fed7aa;">
                            <div style="font-size:12px; font-weight:800; color:#9a3412; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">Weather Context</div>
                            <div style="font-size:14px; line-height:1.7; color:#7c2d12;">${escapeHtml(weatherSummary)}</div>
                        </div>
                    ` : ''}

                    ${modelMetadata ? `
                        <div style="padding:16px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0;">
                            <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">Model Evidence</div>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                                <div>
                                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:800;">Dataset</div>
                                    <div style="font-size:14px; color:#0f172a; font-weight:800; margin-top:4px;">${escapeHtml(modelMetadata.dataset_kind || 'Unknown')}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:800;">Records Used</div>
                                    <div style="font-size:14px; color:#0f172a; font-weight:800; margin-top:4px;">${escapeHtml(String(modelMetadata.records_used || 'N/A'))}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:800;">Last Trained</div>
                                    <div style="font-size:14px; color:#0f172a; font-weight:800; margin-top:4px;">${escapeHtml(modelMetadata.trained_at || 'Unavailable')}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:800;">Top Signals</div>
                                    <div style="font-size:13px; color:#334155; line-height:1.6; margin-top:4px;">${escapeHtml((modelMetadata.top_global_signals || []).map((signal) => signal.feature).join(', ') || 'Unavailable')}</div>
                                </div>
                            </div>
                            ${workflowContext?.next_actions?.length ? `
                                <div style="margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0;">
                                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:800;">Workflow</div>
                                    <div style="font-size:13px; color:#334155; line-height:1.7; margin-top:6px;">${escapeHtml((workflowContext.next_actions || []).join(' | '))}</div>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}

                    <div style="padding:16px; border-radius:16px; background:#fff; border:1px solid #e2e8f0;">
                        <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.08em;">AI Recommendations</div>
                        <div style="font-size:13px; color:#64748b; margin-top:4px; margin-bottom:12px;">Open a recommendation to see deeper implementation steps, suggestions, and planning guidance.</div>
                        ${recommendations.length ? `
                            <div style="display:grid; gap:10px;">
                                ${recommendations.map((rec, idx) => `
                                    <button type="button" onclick="closePredictionDetailModal(); exploreRecommendation(${JSON.stringify(rec).replace(/"/g, '&quot;')}, ${idx})" style="width:100%; text-align:left; border:1px solid #e2e8f0; background:#f8fafc; border-radius:14px; padding:14px 16px; cursor:pointer;">
                                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                            <div>
                                                <div style="font-size:15px; font-weight:800; color:#0f172a;">${escapeHtml(rec.title || 'Recommendation')}</div>
                                                <div style="font-size:13px; color:#475569; margin-top:4px;">${escapeHtml(rec.description || '')}</div>
                                            </div>
                                            <span style="font-size:12px; color:#dc2626; font-weight:800; white-space:nowrap;">View</span>
                                        </div>
                                    </button>
                                `).join('')}
                            </div>
                        ` : '<div class="text-center py-6 text-gray-500">No saved recommendations available for this forecast.</div>'}
                    </div>
                </div>
            `;

            modal.classList.add('show');
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
                    const progress = item.checklist_progress || {};
                    const linkedMitigationCount = Number(item.linked_mitigation_count || 0);

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
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:10px; margin-top:12px;">
                                <div style="padding:12px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa;">
                                    <div style="font-size:11px; font-weight:800; color:#9a3412; text-transform:uppercase;">Checklist Progress</div>
                                    <div style="font-size:22px; font-weight:900; color:#7c2d12; margin-top:6px;">${escapeHtml(String(progress.percent ?? 0))}%</div>
                                </div>
                                <div style="padding:12px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0;">
                                    <div style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Linked Mitigation</div>
                                    <div style="font-size:22px; font-weight:900; color:#0f172a; margin-top:6px;">${escapeHtml(String(linkedMitigationCount))}</div>
                                </div>
                            </div>
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
                            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="${escapeHtml(item.mitigation_draft_url || '#')}" style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:linear-gradient(135deg, #dc2626, #f59e0b); color:#fff; font-size:12px; font-weight:800; text-decoration:none;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span>Create Mitigation Draft</span>
                                </a>
                                <span style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; color:#475569; font-size:12px; font-weight:700;">
                                    <i class="fa-solid fa-arrows-spin"></i>
                                    ${escapeHtml(item.effectiveness_hint || 'Ready for workflow handoff.')}
                                </span>
                            </div>
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
            currentBarangayPredictions = Array.isArray(analysisData.barangay_predictions) ? analysisData.barangay_predictions : [];
            currentPredictionDetails = analysisData;
            renderBarangayPredictionPanel(currentBarangayPredictions);
            const aiWarningMessage = analysisData.ai_warning || null;
            const aiWarningDetails = analysisData.ai_failure_message || analysisData.note || null;
            const aiWarningKey = aiWarningMessage
                ? `${analysisData.analysis_id || 'live'}:${aiWarningMessage}:${aiWarningDetails || ''}`
                : null;

            if (aiWarningMessage && aiWarningKey !== lastAiWarningToastKey) {
                lastAiWarningToastKey = aiWarningKey;
                showAnalyticsToast(
                    aiWarningDetails
                        ? `${aiWarningMessage} ${aiWarningDetails}`
                        : aiWarningMessage,
                    'warning'
                );
            }
            const predictionContainer = document.getElementById('aiPrediction');
            const prediction = analysisData.predictions || null;
            updateTargetMonthDisplay(analysisData.prediction_scope || 'current_month', analysisData.prediction_context || null);
            updateHeroModelLabel(formatAiModelLabel(analysisData?.ai_models?.recommendation || analysisData?.ai_models?.prediction) || analysisData?.model_metadata?.dataset_kind || 'Latest saved model');

            if (predictionContainer) {
                if (!prediction || !prediction.summary) {
                    setPredictionEmptyState(
                        'No prediction has been generated for this dataset yet.',
                        'Historical, current, and predicted values stay separated on this page. Run the analysis to load a forecast narrative, command summary, and chart comparisons.'
                    );
                } else {
                    const primaryDriver = Array.isArray(prediction.drivers) && prediction.drivers.length ? prediction.drivers[0] : null;
                    const scopeCaption = prediction.prediction_context?.target_period_label || prediction.forecast_window || 'Forecast window';
                    const selectedBarangay = analysisData.selected_barangay_prediction || null;
                    const topHotspot = !selectedBarangay && Array.isArray(currentBarangayPredictions) && currentBarangayPredictions.length
                        ? [...currentBarangayPredictions].sort((a, b) => Number(b.probability_percent ?? 0) - Number(a.probability_percent ?? 0))[0]
                        : null;
                    const focusBarangayLabel = selectedBarangay?.barangay_name ? selectedBarangay.barangay_name : (topHotspot?.barangay_name || null);
                    const recommendationCount = Array.isArray(currentRecommendations) ? currentRecommendations.length : 0;
                    const modelMetadata = analysisData.model_metadata || null;

                    predictionContainer.innerHTML = `
                        <div style="padding:22px; border-radius:22px; background:linear-gradient(135deg, rgba(220,38,38,0.08), rgba(245,158,11,0.12)); border:1px solid rgba(245,158,11,0.22);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                                <div>
                                    <div style="font-size:11px; font-weight:800; color:#9a3412; text-transform:uppercase; letter-spacing:0.08em;">Forecast window</div>
                                    <div style="font-size:24px; font-weight:900; color:#0f172a; margin-top:6px;">${escapeHtml(scopeCaption)}</div>
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-shield-heart"></i> ${escapeHtml(prediction.outlook || 'Forecast Outlook')}</span>
                                    ${focusBarangayLabel ? `<span class="analytics-empty-pill"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(focusBarangayLabel)}</span>` : ''}
                                </div>
                            </div>
                            <p style="margin-top:16px; font-size:14px; line-height:1.8; color:#334155;">${escapeHtml(prediction.summary)}</p>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:18px;">
                                <div style="padding:14px; border-radius:16px; background:rgba(255,255,255,0.65); border:1px solid rgba(220,38,38,0.12);">
                                    <div style="font-size:11px; font-weight:800; color:#9a3412; text-transform:uppercase;">Predicted incidents</div>
                                    <div style="font-size:28px; font-weight:900; color:#7f1d1d; margin-top:8px;">${prediction.predicted_fire_incidents ?? 'N/A'}</div>
                                </div>
                                <div style="padding:14px; border-radius:16px; background:rgba(255,255,255,0.65); border:1px solid rgba(234,88,12,0.12);">
                                    <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase;">Estimated damage</div>
                                    <div style="font-size:20px; font-weight:900; color:#9a3412; margin-top:8px;">${prediction.predicted_damage !== undefined && prediction.predicted_damage !== null ? `PHP ${Number(prediction.predicted_damage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'Unavailable'}</div>
                                </div>
                                <div style="padding:14px; border-radius:16px; background:rgba(255,255,255,0.65); border:1px solid rgba(148,163,184,0.16);">
                                    <div style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Recommendation count</div>
                                    <div style="font-size:28px; font-weight:900; color:#0f172a; margin-top:8px;">${recommendationCount}</div>
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-top:16px;">
                                <div style="font-size:13px; color:#475569; max-width:72%;">
                                    ${primaryDriver ? `<strong style="color:#9a3412;">Main contributing factor:</strong> ${escapeHtml(typeof primaryDriver === 'string' ? primaryDriver : (primaryDriver?.summary || 'Driver'))}` : 'Open the detailed forecast to review model drivers, weather context, and stored AI recommendations.'}
                                </div>
                                <button type="button" onclick="openPredictionDetailModal()" style="padding: 10px 16px; background: #fff; color: #b91c1c; border: 1px solid rgba(220,38,38,0.16); border-radius: 12px; cursor: pointer; font-weight: 800;">
                                    View Forecast Details
                                </button>
                            </div>
                            ${modelMetadata ? `
                                <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-microchip"></i> ${escapeHtml(modelMetadata.dataset_kind || 'model')}</span>
                                    <span class="analytics-empty-pill"><i class="fa-solid fa-database"></i> ${escapeHtml(String(modelMetadata.records_used || 'N/A'))} records used</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            }

            document.getElementById('riskScore').textContent = `${analysisData.risk_score || 0}/100`;
            document.getElementById('totalIncidents').textContent = analysisData.total_incidents || 0;
            document.getElementById('fireIncidents').textContent = analysisData.fire_incidents || 0;
            document.getElementById('avgResponse').textContent = analysisData.avg_response_time ? `${analysisData.avg_response_time} min` : 'Unavailable';
            document.getElementById('peakHour').textContent = analysisData.peak_hour !== null && analysisData.peak_hour !== undefined ? `${String(analysisData.peak_hour).padStart(2, '0')}:00` : 'Unavailable';

            const personnelPercent = analysisData.total_personnel > 0 ? (analysisData.available_personnel / analysisData.total_personnel) * 100 : 0;
            document.getElementById('availablePersonnel').textContent = `${analysisData.available_personnel || 0}/${analysisData.total_personnel || 0}`;
            document.getElementById('personnelBar').style.width = `${personnelPercent}%`;

            const equipPercent = analysisData.total_equipment > 0 ? (analysisData.available_equipment / analysisData.total_equipment) * 100 : 0;
            document.getElementById('availableEquipment').textContent = `${analysisData.available_equipment || 0}/${analysisData.total_equipment || 0}`;
            document.getElementById('equipmentBar').style.width = `${equipPercent}%`;

            const incidentTypes = analysisData.incidents_by_type || {};
            const incidentTypeEntries = Object.entries(incidentTypes);
            document.getElementById('incidentTypes').innerHTML = incidentTypeEntries.length
                ? `<strong style="color:#334155;">Incident type mix:</strong> ${incidentTypeEntries.map(([type, count]) => `${escapeHtml(String(type))}: <strong>${escapeHtml(String(count))}</strong>`).join(' · ')}`
                : 'Incident type breakdown is unavailable for this selection.';

            const hiddenRiskFactors = document.getElementById('riskFactors');
            hiddenRiskFactors.innerHTML = Array.isArray(analysisData.risk_factors)
                ? analysisData.risk_factors.map((factor) => `<div>${escapeHtml(factor)}</div>`).join('')
                : '';

            renderPredictionSummary(analysisData);
            renderCharts(analysisData);

            loadRecommendationRunGroups(analysisData).catch((error) => {
                console.error('Grouped recommendation render failed:', error);
            });
            
            if (analysisData.ai_explanation) {
                document.getElementById('aiExplanation').style.display = 'block';
                document.getElementById('aiExplanation').innerHTML = `<strong><i class="fa-solid fa-brain"></i> AI Analysis Summary:</strong><br>${escapeHtml(analysisData.ai_explanation)}`;
            }
        }

        async function emergencyCall() {
            const confirmed = await showAnalyticsConfirm({
                eyebrow: 'Emergency call',
                title: 'Call 911?',
                message: 'This will open your phone app or browser calling prompt.',
                cancelText: 'No',
                acceptText: 'Call 911',
            });

            if (confirmed) {
                window.location.href = 'tel:911';
            }
        }

        async function retrainForecastModel() {
            const button = document.getElementById('retrainModelBtn');
            const confirmed = await showAnalyticsConfirm({
                eyebrow: 'Model training',
                title: 'Retrain forecast model?',
                message: 'PyroSense will rebuild the forecast model using the latest station and incident records. This can take a few moments.',
                cancelText: 'Not now',
                acceptText: 'Retrain',
            });

            if (!confirmed) {
                return;
            }

            const originalHtml = button?.innerHTML;
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.7';
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Retraining...';
            }

            try {
                const response = await fetch('/station-admin/api/retrain-forecast-model', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json().catch(() => ({}));

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Model retraining failed.');
                }

                const trainedAt = result.model_metadata?.trained_at
                    ? ` Trained at ${result.model_metadata.trained_at}.`
                    : '';
                showAnalyticsToast(`${result.message || 'Forecast model retrained successfully.'}${trainedAt}`, 'success');
            } catch (error) {
                showAnalyticsToast(error.message || 'Unable to retrain the forecast model.', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.style.opacity = '';
                    button.innerHTML = originalHtml;
                }
            }
        }

        document.getElementById('analyzeBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'current_month');
            else showAnalyticsToast('Please select a location first.', 'warning');
        });

        document.getElementById('predictNextMonthBtn')?.addEventListener('click', () => {
            const stationSelect = document.getElementById('stationSelect');
            if (stationSelect.value) runAIAnalysis(stationSelect.value, 'next_month');
            else showAnalyticsToast('Please select a location first.', 'warning');
        });

        document.getElementById('retrainModelBtn')?.addEventListener('click', retrainForecastModel);

        document.getElementById('stationSelect')?.addEventListener('change', (e) => {
            populateBarangaySelectFromStation(e.target.value);
        });

        document.addEventListener('DOMContentLoaded', () => {
            replaceChiefAnalyticsFaIcons();

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType !== 1) {
                            return;
                        }

                        if (node.matches && node.matches('i[class*="fa-"]')) {
                            replaceChiefAnalyticsFaIcons(node.parentNode || document);
                            return;
                        }

                        if (node.querySelectorAll) {
                            replaceChiefAnalyticsFaIcons(node);
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });

        loadStations();
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>



