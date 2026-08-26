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

        .content-container { max-width: 1400px; margin: 0 auto; padding: 24px; width: 100%; position: relative; }
        .analytics-scope-loading {
            position: absolute;
            top: 18px;
            right: 24px;
            z-index: 60;
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(252, 165, 165, 0.55);
            background: rgba(255, 247, 237, 0.96);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.10);
            color: #9a3412;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .analytics-scope-loading.is-active { display: inline-flex; }
        .analytics-scope-loading__spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(217, 119, 6, 0.18);
            border-top-color: #dc2626;
            animation: spin 0.9s linear infinite;
            flex-shrink: 0;
        }
        .analytics-stack.is-scope-loading .analytics-shell-card,
        .analytics-stack.is-scope-loading .analytics-hero {
            opacity: 0.58;
            transition: opacity 140ms ease;
        }
        .analytics-stack.is-scope-loading canvas {
            filter: saturate(0.85);
        }
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s; }
        .risk-high { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        .risk-medium { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .risk-low { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .form-select { padding: 10px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; width: 100%; }

        .loading-overlay {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1000;
            display: none;
            width: min(460px, calc(100vw - 48px));
            pointer-events: none;
        }
        .loading-overlay.active { display: block; }
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
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
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
        .recommendation-run-toggle:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.18);
            outline-offset: -3px;
        }
        .historical-weather-preview-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .historical-weather-preview-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            background: linear-gradient(180deg, #fff, #fffaf5);
        }
        .historical-weather-preview-card__label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9a3412;
            margin-bottom: 6px;
        }
        .historical-weather-preview-card__value {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.5;
        }
        .historical-weather-preview-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: auto;
            background: #fff;
        }
        .historical-weather-preview-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1020px;
        }
        .historical-weather-preview-table th,
        .historical-weather-preview-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }
        .historical-weather-preview-table th {
            position: sticky;
            top: 0;
            background: #fff7ed;
            color: #9a3412;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            z-index: 1;
        }
        .historical-weather-preview-note {
            margin-bottom: 16px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.6;
        }
        .recommendation-run-summary {
            padding: 12px 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }
        .recommendation-run-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
        }
        .analytics-run-modal {
            width: min(92vw, 620px) !important;
            max-width: 620px !important;
            max-height: min(78vh, 600px) !important;
        }
        .analytics-run-modal__hero {
            padding: 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(220,38,38,0.08), rgba(245,158,11,0.12));
            border: 1px solid rgba(245,158,11,0.24);
        }
        .analytics-run-modal__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        .analytics-run-modal__stat {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .analytics-run-modal__checklists {
            display: grid;
            gap: 12px;
        }
        .analytics-run-modal__checklist {
            padding: 14px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .analytics-run-modal__checklist-items {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }
        .analytics-run-modal__checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .analytics-run-modal__checkbox input {
            margin-top: 2px;
            accent-color: #dc2626;
        }
        .analytics-run-modal__checkbox-copy {
            font-size: 13px;
            line-height: 1.55;
            color: #334155;
        }
        .analytics-run-modal__checkbox-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .analytics-barangay-modal {
            width: min(90vw, 520px);
            max-width: 520px;
            max-height: min(76vh, 620px);
        }
        .barangay-ranking-list {
            display: grid;
            gap: 10px;
        }
        .barangay-ranking-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            background: #fff;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .barangay-ranking-card__rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            flex-shrink: 0;
            background: #f59e0b;
        }
        .barangay-ranking-card__rank.is-top {
            background: #dc2626;
        }
        .barangay-ranking-card__stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .barangay-ranking-card__stat {
            padding: 9px 10px;
            border-radius: 12px;
            background: #f8fafc;
        }
        .barangay-ranking-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }
        .barangay-ranking-viewall {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
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
        #predictionDetailModal,
        #analysisEvidenceModal,
        #recommendationRunModal,
        #barangayRankingModal {
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
        #predictionDetailModal .modal-content.analytics-forecast-modal {
            width: min(92vw, 600px) !important;
            max-width: 600px !important;
            max-height: min(77vh, 580px) !important;
        }
        #analysisEvidenceModal .modal-content.analytics-forecast-modal {
            width: min(94vw, 760px) !important;
            max-width: 760px !important;
            max-height: min(82vh, 720px) !important;
        }
        .analytics-section__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }
        .analysis-evidence-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid #fed7aa;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .analysis-evidence-trigger:hover:not(:disabled) {
            transform: translateY(-1px);
            background: #ffedd5;
            box-shadow: 0 8px 18px rgba(154, 52, 18, .12);
        }
        .analysis-evidence-trigger:disabled {
            cursor: not-allowed;
            opacity: .5;
        }
        .analysis-evidence {
            display: grid;
            gap: 16px;
        }
        .analysis-evidence__intro {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid #fed7aa;
            background: linear-gradient(135deg, #fff7ed, #fff);
        }
        .analysis-evidence__eyebrow,
        .analysis-evidence__section-title {
            color: #9a3412;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .analysis-evidence__title {
            margin-top: 4px;
            color: #0f172a;
            font-size: 17px;
            font-weight: 850;
        }
        .analysis-evidence__copy {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12.5px;
            line-height: 1.55;
        }
        .analysis-evidence__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
        }
        .analysis-evidence__metric {
            padding: 11px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: #f8fafc;
        }
        .analysis-evidence__metric strong {
            display: block;
            color: #0f172a;
            font-size: 18px;
        }
        .analysis-evidence__metric span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 10.5px;
            font-weight: 750;
            line-height: 1.35;
        }
        .analysis-evidence__section {
            display: grid;
            gap: 9px;
        }
        .analysis-evidence__facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        .analysis-evidence__fact {
            padding: 10px 11px;
            border-left: 3px solid #f59e0b;
            border-radius: 9px;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            line-height: 1.45;
        }
        .analysis-evidence__fact b { color: #0f172a; }
        .analysis-evidence__table-wrap {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
        }
        .analysis-evidence__table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .analysis-evidence__table th,
        .analysis-evidence__table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }
        .analysis-evidence__table th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .analysis-evidence__table tr:last-child td { border-bottom: 0; }
        .analysis-evidence__note {
            padding: 11px 12px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11.5px;
            line-height: 1.5;
        }
        #recommendationRunModal .modal-content.analytics-forecast-modal {
            width: min(92vw, 620px) !important;
            max-width: 620px !important;
            max-height: min(78vh, 600px) !important;
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
            width: min(92vw, 600px) !important;
            max-width: 600px !important;
            max-height: min(77vh, 580px) !important;
        }
        .analytics-forecast-modal--details .modal-body {
            padding: 0.88rem 0.95rem;
        }
        .analytics-forecast-modal--details .modal-body [style*="padding:18px"],
        .analytics-forecast-modal--details .modal-body [style*="padding:16px"],
        .analytics-forecast-modal--details .modal-body [style*="padding:14px"] {
            padding: 10px !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:28px"] {
            font-size: 1.08rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:24px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:22px"] {
            font-size: 0.98rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:16px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:15px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:14px"] {
            font-size: 0.82rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="font-size:13px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:12px"],
        .analytics-forecast-modal--details .modal-body [style*="font-size:11px"] {
            font-size: 0.76rem !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="min-width:220px"] {
            min-width: 0 !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr))"] {
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
        }
        .analytics-forecast-modal--details .modal-body [style*="display:grid; gap:18px"] {
            gap: 8px !important;
        }
        .analytics-forecast-modal--details .modal-body button[onclick*="exploreRecommendation"] {
            padding: 9px 11px !important;
        }
        .analytics-forecast-modal--details .modal-body ul {
            margin-bottom: 0;
            padding-left: 16px !important;
        }
        .analytics-forecast-modal--details .modal-body li {
            margin-bottom: 5px;
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
            #predictionDetailModal.modal.show,
            #analysisEvidenceModal.modal.show {
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
            #analysisEvidenceModal .modal-content.analytics-forecast-modal {
                width: min(100%, 390px) !important;
                max-height: min(78svh, 610px) !important;
            }
            .analytics-section__actions,
            .analysis-evidence-trigger { width: 100%; }
            .analysis-evidence__intro { display: grid; }
            .analysis-evidence__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .analysis-evidence__facts { grid-template-columns: 1fr; }
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
            .analytics-forecast-modal--details {
                width: min(95vw, 430px) !important;
                max-width: 430px !important;
                max-height: min(74svh, 500px) !important;
            }
            .analytics-forecast-modal--details .modal-body [style*="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr))"] {
                grid-template-columns: 1fr !important;
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
        .forecast-narrative-list {
            display: grid;
            gap: 12px;
        }
        .forecast-narrative-card {
            width: 100%;
            text-align: left;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: linear-gradient(180deg, #fff, #fffaf5);
            padding: 16px;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .forecast-narrative-card:hover {
            border-color: rgba(220, 38, 38, 0.22);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .forecast-narrative-card__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }
        .forecast-narrative-card__title {
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }
        .forecast-narrative-card__meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.55;
        }
        .forecast-narrative-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .forecast-narrative-card__summary {
            margin-top: 12px;
            font-size: 13px;
            line-height: 1.7;
            color: #334155;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .forecast-narrative-card__footer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 800;
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
        .analytics-section-heading {
            display: grid;
            gap: 4px;
            margin: 10px 4px 4px;
        }
        .analytics-section-heading__eyebrow {
            color: #b45309;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .analytics-section-heading__title {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 900;
        }
        .analytics-section-heading__copy {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.65;
            max-width: 72ch;
        }
        .prediction-factors-grid {
            display: grid;
            gap: 14px;
        }
        .prediction-factors-group {
            display: grid;
            gap: 10px;
        }
        .prediction-factors-group__title {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .prediction-factors-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .prediction-factor-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(251, 146, 60, 0.22);
            color: #9a3412;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.45;
        }
        .prediction-factor-chip--muted {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }
        .analytics-pattern-group {
            border-radius: 28px;
            border: 1px solid rgba(226,232,240,0.88);
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,250,245,0.98));
            box-shadow: 0 16px 36px rgba(15,23,42,0.05);
            padding: 18px;
            display: grid;
            gap: 16px;
        }
        .analytics-pattern-group .analytics-section-heading {
            margin: 0;
        }
        .analytics-mini-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .analytics-mini-stat {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
            padding: 12px 14px;
        }
        .analytics-mini-stat__label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .analytics-mini-stat__value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
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
        .analytics-forecast-switch {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            padding: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
        }
        .analytics-forecast-switch__button {
            min-height: 44px;
            border: 0;
            border-radius: 11px;
            background: transparent;
            color: #64748b;
            font: inherit;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
            transition: background .18s ease, color .18s ease, box-shadow .18s ease;
        }
        .analytics-forecast-switch__button.is-active {
            background: #fff;
            color: #c2410c;
            box-shadow: 0 5px 16px rgba(15, 23, 42, .09);
        }
        .analytics-loading-card {
            width: 100%;
            padding: 24px 22px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,247,237,0.96));
            border: 1px solid rgba(251,146,60,0.22);
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18);
            display: grid;
            gap: 14px;
            text-align: left;
            pointer-events: auto;
        }
        .analytics-loading-card__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            width: fit-content;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .analytics-loading-card__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.3rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }
        .analytics-loading-card__copy {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.55;
        }
        .analytics-loading-card__meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 800;
            color: #475569;
        }
        .analytics-loading-card__percent {
            color: #dc2626;
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: -0.03em;
        }
        .analytics-loading-card__bar {
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
            position: relative;
        }
        .analytics-loading-card__bar > span {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: inherit;
            background: linear-gradient(90deg, #f97316 0%, #dc2626 55%, #2563eb 100%);
            transition: width 0.35s ease;
            box-shadow: 0 6px 18px rgba(220, 38, 38, 0.25);
        }
        .analytics-loading-card__steps {
            display: grid;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .analytics-loading-card__step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }
        .analytics-loading-card__step-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #cbd5e1;
            transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
            flex-shrink: 0;
        }
        .analytics-loading-card__step.is-active {
            color: #c2410c;
        }
        .analytics-loading-card__step.is-active .analytics-loading-card__step-dot {
            background: #f97316;
            transform: scale(1.15);
            box-shadow: 0 0 0 5px rgba(249, 115, 22, 0.16);
        }
        .analytics-loading-card__step.is-complete {
            color: #0f766e;
        }
        .analytics-loading-card__step.is-complete .analytics-loading-card__step-dot {
            background: #10b981;
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
            .analytics-pattern-group { padding: 12px; border-radius: 20px; gap: 12px; }
            .analytics-mini-stat-grid { grid-template-columns: 1fr; }
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
            .loading-overlay {
                right: 10px;
                bottom: calc(var(--mobile-bottom-nav-h, 76px) + env(safe-area-inset-bottom, 0px) + 10px);
                width: calc(100vw - 20px);
            }
            .analytics-loading-card { width: 100%; padding: 16px; border-radius: 18px; gap: 10px; }
            .analytics-loading-card__title { font-size: 1.12rem; }
            .analytics-loading-card__copy,
            .analytics-loading-card__step,
            .analytics-loading-card__meta { font-size: 12px; }
            .analytics-loading-card__percent { font-size: 1.2rem; }
            .analytics-loading-card__steps { display: none; }
            .analytics-run-modal {
                width: min(95vw, 440px) !important;
                max-width: 440px !important;
                max-height: min(75svh, 520px) !important;
            }
            .analytics-run-modal__hero,
            .analytics-run-modal__checklist {
                padding: 12px;
            }
            .analytics-run-modal__checkbox {
                padding: 9px 10px;
            }
            .analytics-barangay-modal {
                width: min(95vw, 400px) !important;
                max-width: 400px !important;
                max-height: min(74svh, 500px) !important;
            }
            .barangay-ranking-card {
                padding: 12px;
            }
            .forecast-narrative-card {
                padding: 13px;
            }
            .forecast-narrative-card__title {
                font-size: 14px;
            }
            .forecast-narrative-card__summary {
                font-size: 12px;
                -webkit-line-clamp: 4;
            }
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
            z-index: 10050;
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

        .analytics-metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .analytics-metric-card {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .analytics-metric-card__label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .analytics-metric-card__value {
            margin-top: 8px;
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
        }

        .analytics-metric-card__copy {
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }

        .analytics-model-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .analytics-model-training-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .analytics-model-training-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 18px;
            padding: 16px;
        }

        .analytics-model-training-card__label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .analytics-model-training-card__value {
            margin-top: 8px;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.5;
            word-break: break-word;
        }

        .analytics-model-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .analytics-model-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .analytics-model-notes {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .analytics-model-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 13px;
            line-height: 1.6;
        }

        .analytics-model-note i {
            color: #dc2626;
            margin-top: 3px;
        }

        .analytics-model-explainer {
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #fed7aa;
            background: linear-gradient(180deg, #fff7ed, #fffbeb);
            color: #9a3412;
            font-size: 13px;
            line-height: 1.7;
        }

        .analytics-model-explainer strong {
            color: #7c2d12;
        }

        .analytics-model-meta__pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: #fff;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .analytics-model-chart-note {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        .analytics-model-status {
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #fde68a;
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.92), rgba(255, 251, 235, 0.96));
            color: #92400e;
            font-size: 13px;
            line-height: 1.7;
        }

        .analytics-model-status.is-ready {
            border-color: rgba(16, 185, 129, 0.22);
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.92), rgba(240, 253, 244, 0.96));
            color: #166534;
        }

        .analytics-chart-empty-note {
            display: none;
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .analytics-chart-empty-note.is-visible {
            display: block;
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
            <div id="analyticsScopeLoading" class="analytics-scope-loading" role="status" aria-live="polite" aria-hidden="true">
                <span class="analytics-scope-loading__spinner"></span>
                <span id="analyticsScopeLoadingLabel">Loading scoped forecast…</span>
            </div>
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
                            <label class="analytics-control-label" for="historicalWindowSelect">Historical Window</label>
                            <select id="historicalWindowSelect" class="analytics-select">
                                <option value="3" selected>Last 3 years</option>
                                <option value="5">Last 5 years</option>
                                <option value="all">All available history</option>
                            </select>
                        </div>
                        <div class="analytics-control-field">
                            <label class="analytics-control-label" for="targetMonthDisplay">Target Month</label>
                            <input id="targetMonthDisplay" class="analytics-input" type="text" value="Current month forecast" readonly>
                        </div>
                        <div class="analytics-forecast-switch" role="tablist" aria-label="Saved forecast period">
                            <button type="button" class="analytics-forecast-switch__button is-active" data-forecast-view-scope="current_month" role="tab" aria-selected="true">
                                <i class="fa-solid fa-calendar-day"></i> Current Month
                            </button>
                            <button type="button" class="analytics-forecast-switch__button" data-forecast-view-scope="next_month" role="tab" aria-selected="false">
                                <i class="fa-solid fa-calendar-plus"></i> Next Month
                            </button>
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
                            <button id="previewHistoricalWeatherBtn" class="analytics-btn analytics-btn--secondary" type="button">
                                <div class="analytics-btn__copy">
                                    <strong><i class="fa-solid fa-cloud-sun-rain"></i> Preview Historical Weather</strong>
                                    <span>Temporarily queries Open-Meteo archive weather for the selected barangay before any save step.</span>
                                </div>
                                <i class="fa-solid fa-magnifying-glass-chart"></i>
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
                        <div class="analytics-section__actions">
                            <div id="predictionScopeLabel" class="analytics-empty-pill" style="display:none;">Prediction scope</div>
                            <button id="analysisEvidenceBtn" class="analysis-evidence-trigger" type="button" onclick="openAnalysisEvidenceModal()" disabled>
                                <i class="fa-solid fa-database"></i>
                                How was this calculated?
                            </button>
                        </div>
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
                                    <div class="prediction-card__label">Observed to Date</div>
                                    <div id="summaryObservedCount" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #b91c1c, #ef4444);"><i class="fa-solid fa-fire-flame-curved"></i></div>
                            </div>
                            <div class="prediction-card__copy">Completed or closed fire incidents already recorded in the current month.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Predicted Additional</div>
                                    <div id="summaryAdditionalCount" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #ea580c, #f59e0b);"><i class="fa-solid fa-forward"></i></div>
                            </div>
                            <div class="prediction-card__copy">Possible additional incidents remaining in the forecast window.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Planning Midpoint</div>
                                    <div id="summaryIncidentCount" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #c2410c, #f97316);"><i class="fa-solid fa-calendar-check"></i></div>
                            </div>
                            <div class="prediction-card__copy">Calibrated midpoint—not a guaranteed incident count.</div>
                        </article>
                        <article class="prediction-card">
                            <div class="prediction-card__head">
                                <div>
                                    <div class="prediction-card__label">Expected Incident Range</div>
                                    <div id="summaryIncidentRange" class="prediction-card__value">--</div>
                                </div>
                                <div class="prediction-card__icon" style="background: linear-gradient(135deg, #7c3aed, #a855f7);"><i class="fa-solid fa-arrows-left-right"></i></div>
                            </div>
                            <div id="summaryIncidentRangeCopy" class="prediction-card__copy">Planning interval based on model output, observed pace, and same-month history.</div>
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
                            <div id="confidenceScoreCopy" class="prediction-card__copy">Evidence coverage across incidents, history, barangay forecasts, risk findings, weather, and model availability.</div>
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
                                <p class="analytics-section__copy">Operational recommendations prioritized for station action based on the current forecast response, hotspot context, and field findings. Only the 3 latest saved analysis and prediction runs are shown here.</p>
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

                <section class="analytics-pattern-group">
                    <div class="analytics-section-heading">
                        <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                        <div class="analytics-section-heading__title">Historical and Seasonal Patterns</div>
                        <div class="analytics-section-heading__copy">Use actual historical incident records to see how the current forecast fits the longer fire trend, seasonality, recurring calendar pressure, and recent saved model behavior.</div>
                        <div id="historicalWindowBadge" class="analytics-empty-pill">Historical window: Last 3 years</div>
                    </div>

                    <section class="analytics-dual-grid">
                        <div class="analytics-shell-card analytics-section">
                            <div class="analytics-section__head">
                                <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-chart-line"></i> Historical vs Current Actual</div>
                                <p class="analytics-section__copy">Calendar-aligned same-month comparison using the current month or month-to-date window against the previous 3 available historical years and their baseline average.</p>
                                </div>
                            </div>
                            <div class="analytics-chart-wrap">
                                <div class="analytics-chart-canvas">
                                    <canvas id="historicalTrendChart"></canvas>
                                </div>
                            </div>
                            <div id="historicalTrendInsights" class="analytics-insight-list">
                                <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Historical monthly trend will appear after the first analysis response is loaded.</span></div>
                            </div>
                        </div>
                        <div class="analytics-shell-card analytics-section">
                            <div class="analytics-section__head">
                                <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-cloud-sun-rain"></i> Seasonal Fire Pattern</div>
                                <p class="analytics-section__copy">Compares the current season against the historical seasonal average from the available multi-year fire dataset.</p>
                                </div>
                            </div>
                            <div class="analytics-chart-wrap">
                                <div class="analytics-chart-canvas">
                                    <canvas id="seasonalPatternChart"></canvas>
                                </div>
                            </div>
                            <div id="seasonalPatternInsights" class="analytics-insight-list">
                                <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Seasonal comparison will appear after the first analysis response is loaded.</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-panel-grid">
                        <div class="analytics-shell-card analytics-section">
                            <div class="analytics-section__head">
                                <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-calendar-days"></i> Monthly Pattern</div>
                                <p class="analytics-section__copy">Compares the current year's monthly actual pattern against the historical average for the same months across the previous available years.</p>
                                </div>
                            </div>
                            <div class="analytics-chart-wrap">
                                <div class="analytics-chart-canvas">
                                    <canvas id="monthlyPatternChart"></canvas>
                                </div>
                            </div>
                            <div id="monthlyPatternInsights" class="analytics-insight-list">
                                <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Monthly pattern analysis will appear after the first analysis response is loaded.</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-panel-grid">
                        <div class="analytics-shell-card analytics-section">
                            <div class="analytics-section__head">
                                <div>
                                    <div class="analytics-section__title"><i class="fa-solid fa-brain"></i> ML Trend Analysis</div>
                                    <p class="analytics-section__copy">Reuses the dashboard trend-analysis pattern to compare saved analysis runs, their actual incident counts, predicted fire incidents, and confidence trend over time.</p>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span id="analysisRevisionBadge" class="analytics-empty-pill">Revision --</span>
                                    <span id="mlTrendRunCount" class="analytics-empty-pill">0 saved model run(s)</span>
                                    <button type="button" id="revisionHistoryBtn" class="analytics-empty-pill" onclick="openRevisionHistoryModal()" disabled style="cursor:pointer; border:1px solid #fed7aa; background:#fff7ed;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> History
                                    </button>
                                </div>
                            </div>
                            <div class="analytics-chart-wrap">
                                <div class="analytics-chart-canvas">
                                    <canvas id="mlTrendAnalysisChart"></canvas>
                                </div>
                            </div>
                            <div class="analytics-mini-stat-grid">
                                <div class="analytics-mini-stat">
                                    <div class="analytics-mini-stat__label">Latest Risk</div>
                                    <div id="mlTrendLatestRisk" class="analytics-mini-stat__value">No data</div>
                                </div>
                                <div class="analytics-mini-stat">
                                    <div class="analytics-mini-stat__label">Latest Confidence</div>
                                    <div id="mlTrendLatestConfidence" class="analytics-mini-stat__value">0%</div>
                                </div>
                                <div class="analytics-mini-stat">
                                    <div class="analytics-mini-stat__label">Forecast Runs</div>
                                    <div id="mlTrendForecastRuns" class="analytics-mini-stat__value">0</div>
                                </div>
                            </div>
                            <div id="mlTrendInsights" class="analytics-insight-list">
                                <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Saved-run ML trend analysis will appear after the first analysis response is loaded.</span></div>
                            </div>
                        </div>
                        <div class="analytics-shell-card analytics-section">
                            <div class="analytics-section__head">
                                <div>
                                    <div class="analytics-section__title"><i class="fa-solid fa-calendar-days"></i> Month-over-Month Comparison</div>
                                    <p class="analytics-section__copy">Compares last month’s completed incidents with this month’s incidents to date, alongside the saved forecast for each month.</p>
                                </div>
                            </div>
                            <div class="analytics-chart-wrap">
                                <div class="analytics-chart-canvas">
                                    <canvas id="mlMonthComparisonChart"></canvas>
                                </div>
                            </div>
                            <div id="mlMonthComparisonInsights" class="analytics-insight-list">
                                <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Month-over-month comparison will appear after an analysis response is loaded.</span></div>
                            </div>
                        </div>
                    </section>
                </section>

                <div class="analytics-section-heading">
                    <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                    <div class="analytics-section-heading__title">Weather and Incident Patterns</div>
                    <div class="analytics-section-heading__copy">Compare weather-linked historical fire activity with time-based incident patterns to understand how current conditions align with prior fire behavior.</div>
                </div>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-temperature-three-quarters"></i> Weather and Fire Pattern</div>
                                <p class="analytics-section__copy">Compares equivalent historical periods and the current actual period using available incident-linked temperature, humidity, and wind history.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="weatherPatternChart"></canvas>
                            </div>
                            <div class="analytics-legend-note">
                                <span><i class="analytics-legend-swatch" style="background:#dc2626;"></i> Historical actual incidents</span>
                                <span><i class="analytics-legend-swatch" style="background:#f59e0b;"></i> Average temperature</span>
                                <span><i class="analytics-legend-swatch" style="background:#2563eb;"></i> Average humidity</span>
                                <span><i class="analytics-legend-swatch" style="background:#14b8a6;"></i> Average wind speed</span>
                            </div>
                        </div>
                        <div id="weatherPatternInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Weather-linked incident patterns will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-clock"></i> Time-of-Day Pattern</div>
                                <p class="analytics-section__copy">Compares current hourly incident behavior against the 3-year historical hourly baseline for the same calendar-aligned period.</p>
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

                <section class="analytics-panel-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-calendar-day"></i> Day-of-Week Pattern</div>
                                <p class="analytics-section__copy">Compares current weekday behavior against the 3-year historical weekday baseline for the same aligned period.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="dayOfWeekPatternChart"></canvas>
                            </div>
                        </div>
                        <div id="dayOfWeekPatternInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Weekday comparison will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                </section>

                <div class="analytics-section-heading">
                    <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                    <div class="analytics-section-heading__title">Barangay Comparison</div>
                    <div class="analytics-section-heading__copy">Keep the hotspot ranking and station-readiness view together so you can compare forecast pressure against the current operational picture.</div>
                </div>

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

                <div class="analytics-section-heading">
                    <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                    <div class="analytics-section-heading__title">Historical vs Predicted</div>
                    <div class="analytics-section-heading__copy">Keep actual historical activity and forecast values visually separate so decision-makers can see what already happened versus what the model expects next.</div>
                </div>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-calendar-week"></i> Recent Trend</div>
                                <p class="analytics-section__copy">Rolling last-7-day operational trend. This is separate from the calendar-aligned historical comparison panels above.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="dailyChart"></canvas>
                            </div>
                            <div class="analytics-legend-note">
                                <span><i class="analytics-legend-swatch" style="background:#94a3b8;"></i> Historical actual incidents</span>
                                <span><i class="analytics-legend-swatch" style="background:#dc2626;"></i> Current actual incidents</span>
                                <span><i class="analytics-legend-swatch" style="background:#f59e0b;"></i> Forecast probability (%)</span>
                            </div>
                        </div>
                        <div id="dailyTrendInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Insights will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-chart-column"></i> Historical vs Forecast Window</div>
                                <p class="analytics-section__copy">Compares recent historical monthly fire incidents against the current forecast month prediction, keeping actual and forecast values clearly separated.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="forecastCompareChart"></canvas>
                            </div>
                        </div>
                        <div id="forecastCompareInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Historical versus forecast comparison will appear after the first analysis response is loaded.</span></div>
                        </div>
                    </div>
                </section>

                <div class="analytics-section-heading">
                    <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                    <div class="analytics-section-heading__title">Prediction Factors</div>
                    <div class="analytics-section-heading__copy">Expose the main model drivers, watch items, and current operational contributors behind the fire-risk forecast when the backend returns them.</div>
                </div>

                <section class="analytics-panel-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-circle-nodes"></i> Prediction Factors</div>
                                <p class="analytics-section__copy">Primary ML/model drivers, forecast watch items, and current risk signals linked to the loaded forecast.</p>
                            </div>
                        </div>
                        <div id="predictionFactorsPanel" class="prediction-factors-grid">
                            <div class="analytics-empty-state">
                                <div class="analytics-empty-state__title">No prediction factors yet.</div>
                                <p class="analytics-empty-state__copy">Run an analysis to load model drivers, watch items, and current operational contributors.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="analytics-section-heading">
                    <div class="analytics-section-heading__eyebrow">Predictive analytics</div>
                    <div class="analytics-section-heading__title">Model Learning & Performance</div>
                    <div class="analytics-section-heading__copy">Separate the model-learning evidence from the historical fire patterns so commanders can see how the current Random Forest forecast model performs on real saved evaluation data.</div>
                </div>

                <section class="analytics-panel-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-microchip"></i> Model Performance Summary</div>
                                <p class="analytics-section__copy">Uses the currently saved forecast model metadata and cached evaluation artifact. This does not retrain the model when the page loads.</p>
                            </div>
                        </div>
                        <div class="analytics-metric-grid" id="modelPerformanceMetricGrid">
                            <div class="analytics-metric-card">
                                <div class="analytics-metric-card__label">Model type</div>
                                <div class="analytics-metric-card__value" style="font-size:20px;">Waiting for model</div>
                                <div class="analytics-metric-card__copy">The active forecast model will appear after the latest snapshot loads.</div>
                            </div>
                        </div>
                        <div id="modelPerformanceStatus" class="analytics-model-status">
                            Waiting for saved model metadata and evaluation cache.
                        </div>
                        <div id="modelPerformanceMeta" class="analytics-model-meta"></div>
                        <div id="modelPerformanceExplainer" class="analytics-model-explainer" style="display:none;"></div>
                        <div id="modelTrainingEvidence" class="analytics-model-training-grid">
                            <div class="analytics-model-training-card">
                                <div class="analytics-model-training-card__label">Training dataset</div>
                                <div class="analytics-model-training-card__value">Waiting for model metadata</div>
                            </div>
                        </div>
                        <div id="modelTrainingFeatures" class="analytics-model-chip-list"></div>
                        <div id="modelTrainingNotes" class="analytics-model-notes"></div>
                    </div>
                </section>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-bars-progress"></i> Feature Importance</div>
                                <p class="analytics-section__copy">Top variables contributing the most to the current Random Forest forecast model.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="featureImportanceChart"></canvas>
                            </div>
                        </div>
                        <div id="featureImportanceEmptyNote" class="analytics-chart-empty-note"></div>
                        <div id="featureImportanceInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Feature importance will appear once model metadata is loaded.</span></div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-shield-heart"></i> Cross-validation Performance</div>
                                <p class="analytics-section__copy">Fold-by-fold ROC AUC from the saved training pipeline when cross-validation scores are available.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="crossValidationChart"></canvas>
                            </div>
                        </div>
                        <div id="crossValidationEmptyNote" class="analytics-chart-empty-note"></div>
                        <div id="crossValidationInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Cross-validation scores will appear if the saved model metadata contains them.</span></div>
                        </div>
                    </div>
                </section>

                <section class="analytics-dual-grid">
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-chart-line"></i> Actual vs Predicted</div>
                                <p class="analytics-section__copy">Compares actual historical incident counts against the saved regression output for the same held-out evaluation periods.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="actualVsPredictedChart"></canvas>
                            </div>
                        </div>
                        <div id="actualVsPredictedEmptyNote" class="analytics-chart-empty-note"></div>
                        <div id="actualVsPredictedInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Actual versus predicted evaluation points will appear when the row-level evaluation artifact is available.</span></div>
                        </div>
                    </div>
                    <div class="analytics-shell-card analytics-section">
                        <div class="analytics-section__head">
                            <div>
                                <div class="analytics-section__title"><i class="fa-solid fa-wave-square"></i> Prediction Error / Residual Pattern</div>
                                <p class="analytics-section__copy">Shows where the regression forecast is above or below actual historical incident counts on the held-out evaluation periods.</p>
                            </div>
                        </div>
                        <div class="analytics-chart-wrap">
                            <div class="analytics-chart-canvas">
                                <canvas id="residualPatternChart"></canvas>
                            </div>
                        </div>
                        <div id="residualPatternEmptyNote" class="analytics-chart-empty-note"></div>
                        <div id="residualPatternInsights" class="analytics-insight-list">
                            <div class="analytics-insight"><i class="fa-solid fa-circle-info"></i><span>Residual patterns will appear once the cached evaluation artifact is generated.</span></div>
                        </div>
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

    <div id="analysisEvidenceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="analysisEvidenceModalTitle" onclick="if (event.target === this) closeAnalysisEvidenceModal()">
        <div class="modal-content analytics-forecast-modal">
            <div class="modal-header">
                <h3 id="analysisEvidenceModalTitle"><i class="fa-solid fa-database" style="color: #dc2626;"></i> Calculation Evidence</h3>
                <button class="modal-close" type="button" onclick="closeAnalysisEvidenceModal()" aria-label="Close calculation evidence"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="analysisEvidenceModalBody">
                <div class="text-center py-8 text-gray-500">Run or open an analysis to inspect its contributing data.</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" type="button" onclick="closeAnalysisEvidenceModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>

    <div id="recommendationRunModal" class="modal">
        <div class="modal-content analytics-forecast-modal analytics-run-modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-layer-group" style="color: #dc2626;"></i> Clustered Recommendation Details</h3>
                <button class="modal-close" onclick="closeRecommendationRunModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="recommendationRunModalBody">
                <div class="text-center py-8 text-gray-500">No clustered recommendation details available.</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeRecommendationRunModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>

    <div id="barangayRankingModal" class="modal">
        <div class="modal-content analytics-forecast-modal analytics-barangay-modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-ranking-star" style="color: #dc2626;"></i> All Ranked Barangays</h3>
                <button class="modal-close" onclick="closeBarangayRankingModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="barangayRankingModalBody">
                <div class="text-center py-8 text-gray-500">No ranked barangays available.</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeBarangayRankingModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>

    <div id="revisionHistoryModal" class="modal">
        <div class="modal-content analytics-forecast-modal analytics-barangay-modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-code-compare" style="color:#dc2626;"></i> Forecast Revision History</h3>
                <button class="modal-close" onclick="closeRevisionHistoryModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="revisionHistoryModalBody">
                <div class="text-center py-8 text-gray-500">Select a saved analysis to view its revisions.</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeRevisionHistoryModal()" style="padding:8px 20px; background:#f1f5f9; border:none; border-radius:10px; cursor:pointer; font-weight:500;">Close</button>
            </div>
        </div>
    </div>

    <div id="historicalWeatherPreviewModal" class="modal">
        <div class="modal-content analytics-forecast-modal analytics-barangay-modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-cloud-sun-rain" style="color: #dc2626;"></i> Historical Weather Preview</h3>
                <button class="modal-close" onclick="closeHistoricalWeatherPreviewModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body" id="historicalWeatherPreviewBody">
                <div class="text-center py-8 text-gray-500">Choose a barangay and preview the Open-Meteo archive weather first.</div>
            </div>
            <div class="modal-footer">
                <button id="saveHistoricalWeatherBtn" class="btn-primary" style="padding: 8px 20px; background: #dc2626; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Save Previewed Weather</button>
                <button class="btn-outline" onclick="closeHistoricalWeatherPreviewModal()" style="padding: 8px 20px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="analytics-loading-card" role="status" aria-live="polite">
            <div class="analytics-loading-card__eyebrow">
                <i class="fa-solid fa-wave-square"></i>
                Live Forecast Run
            </div>
            <div>
                <h3 id="analyticsLoadingTitle" class="analytics-loading-card__title">Preparing analysis request...</h3>
                <p id="analyticsLoadingCopy" class="analytics-loading-card__copy">PyroSense is collecting the selected station, barangay, and forecast context.</p>
            </div>
            <div class="analytics-loading-card__meta">
                <span id="analyticsLoadingPhase">Initializing</span>
                <span id="analyticsLoadingPercent" class="analytics-loading-card__percent">0%</span>
            </div>
            <div class="analytics-loading-card__bar">
                <span id="analyticsLoadingBar"></span>
            </div>
            <ul class="analytics-loading-card__steps">
                <li id="analyticsLoadingStep1" class="analytics-loading-card__step is-active">
                    <span class="analytics-loading-card__step-dot"></span>
                    Collecting selected forecast inputs
                </li>
                <li id="analyticsLoadingStep2" class="analytics-loading-card__step">
                    <span class="analytics-loading-card__step-dot"></span>
                    Reading incidents, weather, resources, and risk data
                </li>
                <li id="analyticsLoadingStep3" class="analytics-loading-card__step">
                    <span class="analytics-loading-card__step-dot"></span>
                    Building prediction, recommendation, and checklist results
                </li>
                <li id="analyticsLoadingStep4" class="analytics-loading-card__step">
                    <span class="analytics-loading-card__step-dot"></span>
                    Saving the latest analysis snapshot
                </li>
            </ul>
        </div>
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
        let hourlyChart, dailyChart, historicalTrendChart, seasonalPatternChart, monthlyPatternChart, weatherPatternChart, forecastCompareChart, mlTrendAnalysisChart, mlMonthComparisonChart, dayOfWeekPatternChart;
        let featureImportanceChart, crossValidationChart, actualVsPredictedChart, residualPatternChart;
        let currentRecommendations = [];
        let currentAnalysisId = null;
        let currentAnalysisRevision = null;
        let analyticsStations = [];
        let currentBarangayPredictions = [];
        let currentPredictionDetails = null;
        let currentAiModels = null;
        let lastAiWarningToastKey = null;
        let recommendationRunGroups = [];
        let recommendationRunCache = new Map();
        let recommendationRunChecklistCache = new Map();
        let recommendationExploreCache = new Map();
        let historicalWeatherPreviewState = null;
        let analyticsLoadingState = {
            active: false,
            percent: 0,
            timer: null,
            startedAt: null,
        };
        let currentRecommendationRunGroup = null;
        let currentSortedBarangayPredictions = [];
        let forecastNarrativeRuns = [];
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let analyticsConfirmResolver = null;
        let analyticsConfirmOpener = null;
        const analyticsMobileQuery = window.matchMedia('(max-width: 768px)');
        let analyticsScopeLoadingDepth = 0;
        let activeForecastViewScope = 'current_month';

        function setActiveForecastViewScope(scope = 'current_month') {
            activeForecastViewScope = scope === 'next_month' ? 'next_month' : 'current_month';
            document.querySelectorAll('[data-forecast-view-scope]').forEach((button) => {
                const active = button.dataset.forecastViewScope === activeForecastViewScope;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

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

        function setScopedForecastLoading(isActive, label = 'Loading scoped forecast...') {
            const loadingChip = document.getElementById('analyticsScopeLoading');
            const loadingLabel = document.getElementById('analyticsScopeLoadingLabel');
            const analyticsStack = document.querySelector('.analytics-stack');

            if (isActive) {
                analyticsScopeLoadingDepth += 1;
            } else {
                analyticsScopeLoadingDepth = Math.max(0, analyticsScopeLoadingDepth - 1);
            }

            const shouldShow = analyticsScopeLoadingDepth > 0;
            if (loadingChip) {
                loadingChip.classList.toggle('is-active', shouldShow);
                loadingChip.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
            }
            if (loadingLabel && label) {
                loadingLabel.textContent = label;
            }
            if (analyticsStack) {
                analyticsStack.classList.toggle('is-scope-loading', shouldShow);
            }
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
            window.PyroNotify?.notify(message || 'Action updated.', type, 3600);
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

            analyticsConfirmOpener = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
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
                if (modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                modal.setAttribute('aria-hidden', 'true');
                modal.hidden = true;
            }

            analyticsConfirmOpener?.focus({ preventScroll: true });
            analyticsConfirmOpener = null;

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
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.getElementById('analysisEvidenceModal')?.classList.contains('show')) {
                closeAnalysisEvidenceModal();
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
                currentSortedBarangayPredictions = [];
                panel.innerHTML = '<div class="text-center py-8 text-gray-500">No recent saved barangay forecasts available yet.</div>';
                return;
            }

            const sorted = [...predictions].sort((a, b) => {
                const aScore = Number(a.probability_percent ?? a.risk_score ?? 0);
                const bScore = Number(b.probability_percent ?? b.risk_score ?? 0);
                return bScore - aScore;
            });
            currentSortedBarangayPredictions = sorted;
            const visiblePredictions = sorted.slice(0, 5);

            const buildBarangayPredictionCards = (items, startRank = 1) => `
                <div class="barangay-ranking-list">
                    ${items.map((prediction, index) => {
                        const rank = startRank + index;
                        const riskLevel = prediction.risk_level || prediction.assessment_level || 'Unknown';
                        const probability = prediction.probability_percent ?? prediction.risk_score ?? null;
                        const incidents = prediction.expected_month_total_fire_incidents ?? prediction.predicted_fire_incidents ?? prediction.predicted_incident_count ?? null;
                        const historicalBaseline = prediction.historical_baseline_incidents ?? null;
                        const targetLabel = prediction.prediction_target_label || prediction.prediction_context?.target_period_label || '';
                        const updatedAt = prediction.analysis_created_at
                            ? new Date(prediction.analysis_created_at).toLocaleString()
                            : '';

                        return `
                            <div class="barangay-ranking-card">
                                <div class="barangay-ranking-card__rank ${rank <= 3 ? 'is-top' : ''}">
                                    ${rank}
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:15px; font-weight:800; color:#0f172a;">${escapeHtml(prediction.barangay_name || 'Barangay')}</div>
                                            ${prediction.city_name ? `<div style="font-size:12px; color:#64748b; margin-top:2px;">${escapeHtml(prediction.city_name)}</div>` : ''}
                                        </div>
                                        <span style="padding:6px 12px; border-radius:999px; background:#fff7ed; color:#9a3412; font-size:11px; font-weight:700; text-transform:uppercase;">
                                            ${escapeHtml(riskLevel)}
                                        </span>
                                    </div>
                                    <div class="barangay-ranking-card__stats">
                                        <div class="barangay-ranking-card__stat">
                                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Probability</div>
                                            <div style="font-size:17px; font-weight:800; color:#0f172a; margin-top:4px;">${probability !== null ? `${escapeHtml(String(probability))}%` : 'N/A'}</div>
                                        </div>
                                        <div class="barangay-ranking-card__stat">
                                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Expected Month Total</div>
                                            <div style="font-size:17px; font-weight:800; color:#0f172a; margin-top:4px;">${incidents !== null ? escapeHtml(String(incidents)) : 'N/A'}</div>
                                        </div>
                                        <div class="barangay-ranking-card__stat">
                                            <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Historical Baseline</div>
                                            <div style="font-size:17px; font-weight:800; color:#0f172a; margin-top:4px;">${historicalBaseline !== null ? escapeHtml(String(historicalBaseline)) : 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${(targetLabel || updatedAt) ? `
                                        <div style="margin-top:10px; font-size:12px; color:#64748b; line-height:1.5;">
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

            panel.innerHTML = `
                ${buildBarangayPredictionCards(visiblePredictions)}
                ${sorted.length > 5 ? `
                    <div class="barangay-ranking-actions">
                        <button type="button" class="barangay-ranking-viewall" onclick="openBarangayRankingModal()">
                            <i class="fa-solid fa-list"></i>
                            View all ${escapeHtml(String(sorted.length))} barangays
                        </button>
                    </div>
                ` : ''}
            `;
        }

        function openBarangayRankingModal() {
            const modal = document.getElementById('barangayRankingModal');
            const body = document.getElementById('barangayRankingModalBody');
            if (!modal || !body) return;

            if (!Array.isArray(currentSortedBarangayPredictions) || !currentSortedBarangayPredictions.length) {
                body.innerHTML = '<div class="text-center py-8 text-gray-500">No ranked barangays available.</div>';
            } else {
                body.innerHTML = `
                    <div class="barangay-ranking-list">
                        ${currentSortedBarangayPredictions.map((prediction, index) => {
                            const riskLevel = prediction.risk_level || prediction.assessment_level || 'Unknown';
                            const probability = prediction.probability_percent ?? prediction.risk_score ?? null;
                            const incidents = prediction.expected_month_total_fire_incidents ?? prediction.predicted_fire_incidents ?? prediction.predicted_incident_count ?? null;
                            const historicalBaseline = prediction.historical_baseline_incidents ?? null;
                            const targetLabel = prediction.prediction_target_label || prediction.prediction_context?.target_period_label || '';
                            return `
                                <div class="barangay-ranking-card">
                                    <div class="barangay-ranking-card__rank ${index < 3 ? 'is-top' : ''}">${index + 1}</div>
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                                            <div>
                                                <div style="font-size:14px; font-weight:800; color:#0f172a;">${escapeHtml(prediction.barangay_name || 'Barangay')}</div>
                                                ${prediction.city_name ? `<div style="font-size:11px; color:#64748b; margin-top:2px;">${escapeHtml(prediction.city_name)}</div>` : ''}
                                            </div>
                                            <span style="padding:5px 10px; border-radius:999px; background:#fff7ed; color:#9a3412; font-size:11px; font-weight:700; text-transform:uppercase;">
                                                ${escapeHtml(riskLevel)}
                                            </span>
                                        </div>
                                        <div class="barangay-ranking-card__stats">
                                            <div class="barangay-ranking-card__stat">
                                                <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase;">Probability</div>
                                                <div style="font-size:15px; font-weight:800; color:#0f172a; margin-top:4px;">${probability !== null ? `${escapeHtml(String(probability))}%` : 'N/A'}</div>
                                            </div>
                                            <div class="barangay-ranking-card__stat">
                                                <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase;">Expected Total</div>
                                                <div style="font-size:15px; font-weight:800; color:#0f172a; margin-top:4px;">${incidents !== null ? escapeHtml(String(incidents)) : 'N/A'}</div>
                                            </div>
                                            <div class="barangay-ranking-card__stat">
                                                <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase;">Baseline</div>
                                                <div style="font-size:15px; font-weight:800; color:#0f172a; margin-top:4px;">${historicalBaseline !== null ? escapeHtml(String(historicalBaseline)) : 'N/A'}</div>
                                            </div>
                                        </div>
                                        ${targetLabel ? `<div style="margin-top:8px; font-size:11px; color:#64748b;">Target period: <strong>${escapeHtml(targetLabel)}</strong></div>` : ''}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            modal.classList.add('show');
        }

        function closeBarangayRankingModal() {
            document.getElementById('barangayRankingModal')?.classList.remove('show');
        }

        async function openRevisionHistoryModal() {
            const modal = document.getElementById('revisionHistoryModal');
            const body = document.getElementById('revisionHistoryModalBody');
            if (!modal || !body) return;

            modal.classList.add('show');
            if (!currentAnalysisId) {
                body.innerHTML = '<div class="text-center py-8 text-gray-500">No saved analysis is selected.</div>';
                return;
            }

            body.innerHTML = '<div class="text-center py-8 text-gray-500"><i class="fa-solid fa-spinner fa-spin"></i> Loading revisions...</div>';
            try {
                const response = await fetch(`/station-admin/api/analyses/${encodeURIComponent(currentAnalysisId)}/revisions`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Revision history could not be loaded.');
                }

                const revisions = Array.isArray(result.data) ? result.data : [];
                const current = revisions.find((revision) => revision.is_current) || revisions[0] || {};
                body.innerHTML = revisions.length ? `
                    <div style="display:grid; gap:12px;">
                        ${revisions.map((revision) => {
                            const incidentDelta = Number(revision.incident_count || 0) - Number(current.incident_count || 0);
                            const predictedDelta = Number(revision.predicted_fire_incidents || 0) - Number(current.predicted_fire_incidents || 0);
                            return `
                                <article style="padding:16px; border:1px solid ${revision.is_current ? '#fdba74' : '#e2e8f0'}; border-radius:16px; background:${revision.is_current ? '#fff7ed' : '#fff'};">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:14px; font-weight:900; color:#0f172a;">Revision ${escapeHtml(String(revision.revision_number))}${revision.is_current ? ' · Current' : ''}</div>
                                            <div style="font-size:12px; color:#64748b; margin-top:4px;">${escapeHtml(revision.target_month_label || 'Target month unavailable')} · ${escapeHtml(revision.barangay_name || 'All barangays')}</div>
                                        </div>
                                        <span style="padding:6px 10px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:11px; font-weight:800; text-transform:uppercase;">${escapeHtml(revision.risk_level || 'Unknown')}</span>
                                    </div>
                                    <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin-top:12px;">
                                        <div style="padding:10px; border-radius:12px; background:#f8fafc;"><small style="color:#64748b;">Actual</small><div style="font-weight:900;">${escapeHtml(String(revision.incident_count ?? 0))}</div></div>
                                        <div style="padding:10px; border-radius:12px; background:#f8fafc;"><small style="color:#64748b;">Predicted</small><div style="font-weight:900;">${revision.predicted_fire_incidents ?? 'N/A'}</div></div>
                                        <div style="padding:10px; border-radius:12px; background:#f8fafc;"><small style="color:#64748b;">Confidence</small><div style="font-weight:900;">${escapeHtml(String(revision.confidence ?? 0))}%</div></div>
                                    </div>
                                    ${!revision.is_current ? `<div style="font-size:11px; color:#64748b; margin-top:10px;">Compared with current: actual ${incidentDelta >= 0 ? '+' : ''}${incidentDelta}, predicted ${predictedDelta >= 0 ? '+' : ''}${predictedDelta}.</div>` : ''}
                                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; margin-top:12px;">
                                        <span style="font-size:11px; color:#64748b;">${revision.recorded_at ? escapeHtml(new Date(revision.recorded_at).toLocaleString()) : ''}</span>
                                        ${!revision.is_current ? `<button type="button" onclick="rollbackAnalysisRevision(${Number(revision.revision_number)})" style="border:none; border-radius:999px; padding:8px 12px; background:#0f172a; color:#fff; font-size:11px; font-weight:800; cursor:pointer;"><i class="fa-solid fa-rotate-left"></i> Restore</button>` : ''}
                                    </div>
                                </article>
                            `;
                        }).join('')}
                    </div>
                ` : '<div class="text-center py-8 text-gray-500">No revision history is available yet.</div>';
            } catch (error) {
                body.innerHTML = `<div class="text-center py-8 text-red-500">${escapeHtml(error.message)}</div>`;
            }
        }

        function closeRevisionHistoryModal() {
            document.getElementById('revisionHistoryModal')?.classList.remove('show');
        }

        async function rollbackAnalysisRevision(revisionNumber) {
            const confirmed = await showAnalyticsConfirm({
                eyebrow: 'Restore revision',
                title: `Restore revision ${revisionNumber}?`,
                message: 'The current result will be archived. AI drafts will be reconciled, while confirmed or edited checklist and mitigation records remain protected for review.',
                cancelText: 'Cancel',
                acceptText: 'Restore revision',
            });
            if (!confirmed || !currentAnalysisId) return;

            try {
                const response = await fetch(`/station-admin/api/analyses/${encodeURIComponent(currentAnalysisId)}/rollback`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ revision_number: revisionNumber }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Revision restore failed.');
                }
                closeRevisionHistoryModal();
                showAnalyticsToast(result.message || 'Revision restored.', 'success');
                await loadLatestAnalysisSnapshot(currentPredictionDetails?.prediction_scope || 'current_month');
            } catch (error) {
                showAnalyticsToast(error.message || 'Revision restore failed.', 'error');
            }
        }

        function closeHistoricalWeatherPreviewModal() {
            document.getElementById('historicalWeatherPreviewModal')?.classList.remove('show');
        }

        function setHistoricalWeatherSaveButtonState(isDisabled, label = 'Save Previewed Weather') {
            const button = document.getElementById('saveHistoricalWeatherBtn');
            if (!button) return;

            button.disabled = isDisabled;
            button.style.opacity = isDisabled ? '0.7' : '';
            button.textContent = label;
        }

        function formatHistoricalWeatherPreviewValue(value, digits = 1) {
            if (value === null || value === undefined || value === '') {
                return '—';
            }

            const numericValue = Number(value);
            if (Number.isNaN(numericValue)) {
                return escapeHtml(String(value));
            }

            return numericValue.toFixed(digits);
        }

        function renderHistoricalWeatherPreviewModal(result) {
            const modal = document.getElementById('historicalWeatherPreviewModal');
            const body = document.getElementById('historicalWeatherPreviewBody');
            if (!modal || !body) return;

            const rows = Array.isArray(result?.rows) ? result.rows : [];
            const barangayName = result?.barangay?.name || 'Selected barangay';
            const cityName = result?.barangay?.city_name || 'Unknown city';
            const latitude = result?.coordinates?.latitude;
            const longitude = result?.coordinates?.longitude;
            const years = Array.isArray(result?.years) ? result.years : [];
            historicalWeatherPreviewState = result;
            setHistoricalWeatherSaveButtonState(false);

            body.innerHTML = `
                <div class="historical-weather-preview-note">
                    Preview only. This does not save anything yet. Monthly incident totals come from the exact matching rows in <strong>historical_fire_incidents</strong> for the selected <strong>barangay_id</strong>. The table shows both the number of matched historical rows and the summed <strong>incident_count</strong> from those rows, while weather values are aggregated from the Open-Meteo archive query built from the barangay hall marker coordinates.
                </div>
                <div class="historical-weather-preview-meta">
                    <div class="historical-weather-preview-card">
                        <div class="historical-weather-preview-card__label">Barangay</div>
                        <div class="historical-weather-preview-card__value">${escapeHtml(barangayName)}${cityName ? `, ${escapeHtml(cityName)}` : ''}</div>
                    </div>
                    <div class="historical-weather-preview-card">
                        <div class="historical-weather-preview-card__label">Coordinates</div>
                        <div class="historical-weather-preview-card__value">${formatHistoricalWeatherPreviewValue(latitude, 6)}, ${formatHistoricalWeatherPreviewValue(longitude, 6)}</div>
                    </div>
                    <div class="historical-weather-preview-card">
                        <div class="historical-weather-preview-card__label">Historical Window</div>
                        <div class="historical-weather-preview-card__value">${escapeHtml(result?.historical_window?.label || 'Historical preview')}</div>
                    </div>
                    <div class="historical-weather-preview-card">
                        <div class="historical-weather-preview-card__label">Years Queried</div>
                        <div class="historical-weather-preview-card__value">${years.length ? escapeHtml(years.join(', ')) : 'None found'}</div>
                    </div>
                </div>
                <div class="historical-weather-preview-table-wrap">
                    <table class="historical-weather-preview-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Matched Rows</th>
                                <th>Incident Count Total</th>
                                <th>temperature_2m avg</th>
                                <th>relative_humidity_2m avg</th>
                                <th>precipitation total</th>
                                <th>rain total</th>
                                <th>vapour_pressure_deficit avg</th>
                                <th>wind_speed_10m avg</th>
                                <th>wind_gusts_10m avg</th>
                                <th>weather_code mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.length ? rows.map((row) => `
                                <tr>
                                    <td>${escapeHtml(row.month_label || '')}</td>
                                    <td>${escapeHtml(String(row.historical_row_count ?? 0))}</td>
                                    <td>${escapeHtml(String(row.incident_count ?? 0))}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.temperature_2m)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.relative_humidity_2m)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.precipitation, 2)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.rain, 2)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.vapour_pressure_deficit, 2)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.wind_speed_10m, 2)}</td>
                                    <td>${formatHistoricalWeatherPreviewValue(row.wind_gusts_10m, 2)}</td>
                                    <td>${row.weather_code ?? '—'}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="11" style="text-align:center; color:#64748b; padding: 24px;">No historical weather preview rows are available for this barangay yet.</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                </div>
            `;

            modal.classList.add('show');
        }

        async function previewHistoricalWeatherArchive() {
            const stationSelect = document.getElementById('stationSelect');
            const barangaySelect = document.getElementById('barangaySelect');
            const historicalWindowSelect = document.getElementById('historicalWindowSelect');
            const button = document.getElementById('previewHistoricalWeatherBtn');

            if (!stationSelect?.value) {
                showAnalyticsToast('Please select a station first.', 'warning');
                return;
            }

            if (!barangaySelect?.value || barangaySelect.value === 'all') {
                showAnalyticsToast('Choose one barangay first so the archive preview can use its marker coordinates.', 'warning');
                return;
            }

            const originalHtml = button?.innerHTML;
            const modal = document.getElementById('historicalWeatherPreviewModal');
            const body = document.getElementById('historicalWeatherPreviewBody');

            if (button) {
                button.disabled = true;
                button.style.opacity = '0.7';
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Previewing...';
            }

            if (body) {
                body.innerHTML = `
                    <div class="text-center py-8">
                        <div class="loading-spinner" style="width: 34px; height: 34px;"></div>
                        <p class="mt-3 text-gray-500">Querying Open-Meteo archive weather for the selected barangay...</p>
                    </div>
                `;
            }
            historicalWeatherPreviewState = null;
            setHistoricalWeatherSaveButtonState(true, 'Save Previewed Weather');
            modal?.classList.add('show');

            try {
                const params = new URLSearchParams({
                    station_id: stationSelect.value,
                    barangay_id: barangaySelect.value,
                    historical_window: historicalWindowSelect?.value || '3',
                });

                const response = await fetch(`/station-admin/api/historical-weather-preview?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.message || result.details || 'Historical weather preview failed.');
                }

                renderHistoricalWeatherPreviewModal(result);
                showAnalyticsToast(result.message || 'Historical weather preview is ready.', 'success');
            } catch (error) {
                closeHistoricalWeatherPreviewModal();
                historicalWeatherPreviewState = null;
                showAnalyticsToast(error.message || 'Unable to preview historical weather right now.', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.style.opacity = '';
                    button.innerHTML = originalHtml;
                }
            }
        }

        async function saveHistoricalWeatherArchive() {
            const state = historicalWeatherPreviewState;
            if (!state?.barangay?.id) {
                showAnalyticsToast('Preview the archive weather first before saving.', 'warning');
                return;
            }

            const confirmed = await showAnalyticsConfirm({
                eyebrow: 'Historical weather save',
                title: 'Save previewed weather to historical fire incidents?',
                message: 'This will update existing monthly rows for the selected barangay and create one zero-incident weather placeholder only for months that have no historical incident rows.',
                cancelText: 'Cancel',
                acceptText: 'Save weather',
            });

            if (!confirmed) {
                return;
            }

            setHistoricalWeatherSaveButtonState(true, 'Saving...');

            try {
                const payload = {
                    station_id: document.getElementById('stationSelect')?.value || '',
                    barangay_id: state.barangay.id,
                    historical_window: document.getElementById('historicalWindowSelect')?.value || '3',
                };

                const response = await fetch('/station-admin/api/historical-weather-save', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.message || result.details || 'Historical weather save failed.');
                }

                const updatedRows = result?.summary?.updated_rows ?? 0;
                const createdPlaceholders = result?.summary?.created_placeholders ?? 0;
                showAnalyticsToast(`${result.message || 'Historical weather saved.'} Updated ${updatedRows} row(s), created ${createdPlaceholders} placeholder month row(s).`, 'success');
                setHistoricalWeatherSaveButtonState(false, 'Saved');
                closeHistoricalWeatherPreviewModal();
            } catch (error) {
                setHistoricalWeatherSaveButtonState(false, 'Save Previewed Weather');
                showAnalyticsToast(error.message || 'Unable to save historical weather right now.', 'error');
            }
        }

        function buildAnalysisScopeQuery(predictionScope = 'current_month') {
            const scopeMeta = getSelectedScopeMeta();
            const params = new URLSearchParams();

            if (scopeMeta.stationId) {
                params.set('station_id', scopeMeta.stationId);
            }

            params.set('prediction_scope', predictionScope || 'current_month');

            if (scopeMeta.barangayId && scopeMeta.barangayId !== 'all') {
                params.set('barangay_id', scopeMeta.barangayId);
            }

            if (scopeMeta.historicalWindowValue) {
                params.set('historical_window', scopeMeta.historicalWindowValue);
            }

            return params.toString();
        }

        async function fetchScopedAnalysisSnapshot(predictionScope = 'current_month') {
            const query = buildAnalysisScopeQuery(predictionScope);
            const response = await fetch(`/station-admin/api/analyses/latest-snapshot?${query}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        }

        async function loadLatestAnalysisSnapshot(predictionScope = 'current_month', notifyMissing = false) {
            setActiveForecastViewScope(predictionScope);
            setScopedForecastLoading(true, 'Loading forecast scope...');
            try {
                const result = await fetchScopedAnalysisSnapshot(predictionScope);
                if (!result.success || !result.data) {
                    if (notifyMissing) {
                        showAnalyticsToast(`No saved ${predictionScope === 'next_month' ? 'next-month forecast' : 'current-month analysis'} exists for this selection yet.`, 'info');
                    }
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
            } finally {
                setScopedForecastLoading(false);
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

        function buildRecommendationCacheKey(recommendation) {
            return JSON.stringify({
                analysisId: currentAnalysisId || null,
                checklistId: recommendation?.action_plan_checklist_id || null,
                title: getRecommendationTitle(recommendation),
                description: getRecommendationDescription(recommendation),
                priority: recommendation?.priority || null,
            });
        }

        function getSelectedScopeMeta() {
            const stationSelect = document.getElementById('stationSelect');
            const barangaySelect = document.getElementById('barangaySelect');
            const historicalWindowSelect = document.getElementById('historicalWindowSelect');
            return {
                stationId: stationSelect?.value || '',
                stationName: stationSelect?.options?.[stationSelect.selectedIndex]?.text || 'Selected station',
                barangayId: barangaySelect?.value || 'all',
                barangayName: barangaySelect?.options?.[barangaySelect.selectedIndex]?.text || 'All barangays',
                dateRangeLabel: document.getElementById('periodSelect')?.options?.[document.getElementById('periodSelect').selectedIndex]?.text || 'Last 30 days',
                historicalWindowValue: historicalWindowSelect?.value || '3',
                historicalWindowLabel: historicalWindowSelect?.options?.[historicalWindowSelect.selectedIndex]?.text || 'Last 3 years',
            };
        }

        function updateHistoricalWindowBadge(label = null) {
            const badge = document.getElementById('historicalWindowBadge');
            if (!badge) return;

            const fallback = getSelectedScopeMeta().historicalWindowLabel;
            badge.textContent = `Historical window: ${label || fallback}`;
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

        function getPredictionScopePriority(scope) {
            return scope === 'next_month' ? 1 : 0;
        }

        function getAnalysisScopeMeta(detail = {}, summary = {}) {
            const detailData = detail?.analysis_data?.raw_data || {};
            const selectedBarangay = detail?.selected_barangay_prediction
                || detailData?.selected_barangay_prediction
                || null;
            const selectedBarangayName = selectedBarangay?.barangay_name || null;
            const isBarangaySpecific = Boolean(selectedBarangayName);
            const sectorName = detail?.sector || summary?.sector || 'the selected station';

            return {
                isBarangaySpecific,
                barangayName: selectedBarangayName,
                shortLabel: isBarangaySpecific ? 'Barangay-specific run' : 'Station-wide run',
                chipLabel: isBarangaySpecific ? selectedBarangayName : 'All barangays',
                detailLabel: isBarangaySpecific
                    ? `Saved under barangay focus: ${selectedBarangayName}`
                    : `Saved under station-wide scope for ${sectorName}`,
            };
        }

        function buildForecastNarrativeRun(analysisId, summary = {}, detail = null) {
            const detailData = detail?.analysis_data?.raw_data || {};
            const prediction = detail?.predictions || null;
            const predictionContext = detail?.prediction_context
                || detailData?.prediction_context
                || prediction?.prediction_context
                || {};
            const predictionScope = detail?.prediction_scope
                || detailData?.prediction_scope
                || prediction?.prediction_scope
                || 'current_month';
            const hotspot = detail?.selected_barangay_prediction
                || detailData?.selected_barangay_prediction
                || (Array.isArray(detail?.barangay_predictions)
                    ? [...detail.barangay_predictions].sort((a, b) => Number(b.probability_percent ?? 0) - Number(a.probability_percent ?? 0))[0]
                    : null);

            return {
                id: Number(analysisId),
                createdAt: detail?.created_at || summary?.created_at || null,
                sector: detail?.sector || summary?.sector || 'Station Analysis',
                predictionScope,
                targetLabel: predictionContext?.target_period_label || prediction?.forecast_window || null,
                summary: prediction?.summary || null,
                outlook: prediction?.outlook || detail?.risk_level || summary?.risk_level || 'Forecast',
                confidenceScore: detail?.confidence_score || summary?.confidence_score || summary?.confidence_data || null,
                predictedIncidents: prediction?.predicted_fire_incidents ?? summary?.incident_count ?? null,
                hotspotName: hotspot?.barangay_name || null,
                probability: hotspot?.probability_percent ?? prediction?.model_prediction?.probability_percent ?? null,
                scopeMeta: getAnalysisScopeMeta(detail, summary),
                detail,
            };
        }

        function renderForecastNarrativeList() {
            const predictionContainer = document.getElementById('aiPrediction');
            if (!predictionContainer) return;

            if (!forecastNarrativeRuns.length) {
                setPredictionEmptyState(
                    'No prediction has been generated for this dataset yet.',
                    'Historical, current, and predicted values stay separated on this page. Run the analysis to load a forecast narrative, command summary, and chart comparisons.'
                );
                return;
            }

            const barangayRuns = forecastNarrativeRuns.filter((run) => run.scopeMeta?.isBarangaySpecific);
            const stationRuns = forecastNarrativeRuns.filter((run) => !run.scopeMeta?.isBarangaySpecific);
            const renderForecastRunCard = (run) => `
                <button type="button" class="forecast-narrative-card" onclick="openForecastNarrativeModal(${run.id})">
                    <div class="forecast-narrative-card__head">
                        <div>
                            <div class="forecast-narrative-card__title">${escapeHtml(run.targetLabel || (run.predictionScope === 'next_month' ? 'Next Month Forecast' : 'Current Month Forecast'))}</div>
                            <div class="forecast-narrative-card__meta">
                                ${escapeHtml(run.sector || 'Station Analysis')} · ${escapeHtml(formatRecommendationRunTimestamp(run.createdAt))}
                            </div>
                            <div class="forecast-narrative-card__meta" style="margin-top:4px; font-weight:700; color:${run.scopeMeta?.isBarangaySpecific ? '#dc2626' : '#475569'};">
                                ${escapeHtml(run.scopeMeta?.detailLabel || 'Saved analysis run')}
                            </div>
                        </div>
                        <span class="recommendation-run-chip">
                            <i class="fa-solid ${run.predictionScope === 'next_month' ? 'fa-calendar-plus' : 'fa-calendar-day'}" style="color:${run.predictionScope === 'next_month' ? '#2563eb' : '#dc2626'};"></i>
                            ${escapeHtml(run.predictionScope === 'next_month' ? 'Next month' : 'Current month')}
                        </span>
                    </div>
                    <div class="forecast-narrative-card__chips">
                        <span class="recommendation-run-chip">
                            <i class="fa-solid ${run.scopeMeta?.isBarangaySpecific ? 'fa-map-pin' : 'fa-map'}" style="color:${run.scopeMeta?.isBarangaySpecific ? '#dc2626' : '#475569'};"></i>
                            ${escapeHtml(run.scopeMeta?.chipLabel || 'All barangays')}
                        </span>
                        <span class="recommendation-run-chip"><i class="fa-solid fa-shield-heart" style="color:#f59e0b;"></i>${escapeHtml(run.outlook || 'Forecast')}</span>
                        ${run.confidenceScore !== null && run.confidenceScore !== undefined ? `<span class="recommendation-run-chip"><i class="fa-solid fa-crosshairs" style="color:#059669;"></i>${escapeHtml(String(run.confidenceScore))}% confidence</span>` : ''}
                        ${run.predictedIncidents !== null && run.predictedIncidents !== undefined ? `<span class="recommendation-run-chip"><i class="fa-solid fa-fire-flame-curved" style="color:#dc2626;"></i>${escapeHtml(String(run.predictedIncidents))} possible incident(s)</span>` : ''}
                        ${run.hotspotName ? `<span class="recommendation-run-chip"><i class="fa-solid fa-location-dot" style="color:#dc2626;"></i>${escapeHtml(run.hotspotName)}</span>` : ''}
                    </div>
                    <div class="forecast-narrative-card__summary">${escapeHtml(run.summary || 'No saved forecast narrative is available for this analysis yet.')}</div>
                    <div class="forecast-narrative-card__footer">
                        <i class="fa-solid fa-up-right-from-square"></i>
                        Open forecast details
                    </div>
                </button>
            `;
            const renderForecastRunSection = (title, copy, runs) => {
                if (!runs.length) {
                    return '';
                }

                return `
                    <section class="forecast-narrative-section" style="display:grid; gap:12px;">
                        <div class="analytics-section-heading" style="display:grid; gap:4px;">
                            <div style="font-size:0.9rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#475569;">${escapeHtml(title)}</div>
                            <p style="margin:0; color:#64748b; font-size:0.92rem;">${escapeHtml(copy)}</p>
                        </div>
                        <div class="forecast-narrative-list">
                            ${runs.map((run) => renderForecastRunCard(run)).join('')}
                        </div>
                    </section>
                `;
            };

            predictionContainer.innerHTML = `
                <div style="display:grid; gap:20px;">
                    ${renderForecastRunSection(
                        'Latest Barangay-Specific Runs',
                        'Saved analyses created for one selected barangay appear here first.',
                        barangayRuns
                    )}
                    ${renderForecastRunSection(
                        'Latest Station-Wide Runs',
                        'Saved analyses covering all barangays under the station appear here.',
                        stationRuns
                    )}
                </div>
            `;
        }

        async function loadForecastNarrativeRuns(priorityAnalysisData = null) {
            const predictionContainer = document.getElementById('aiPrediction');
            if (predictionContainer) {
                predictionContainer.innerHTML = `
                    <div class="analytics-empty-state">
                        <div class="analytics-empty-state__title">Loading forecast narratives...</div>
                        <p class="analytics-empty-state__copy">Preparing the saved current-month and next-month forecast narratives for review.</p>
                    </div>
                `;
            }

            try {
                const response = await fetch(`/station-admin/api/analyses/history?${buildAnalysisScopeQuery(priorityAnalysisData?.prediction_scope || 'current_month')}`, {
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
                        sector: priorityAnalysisData.sector || priorityAnalysisData.location_name || 'Selected station',
                        risk_level: priorityAnalysisData.risk_level || null,
                        confidence_score: priorityAnalysisData.confidence_score || null,
                        incident_count: priorityAnalysisData.total_incidents || 0,
                        created_at: new Date().toISOString(),
                    });
                }

                const runs = await Promise.all(
                    Array.from(summaryMap.values())
                        .slice(0, 10)
                        .map(async (summary) => {
                            const detailGroup = await fetchRecommendationRunDetail(summary.analysis_id, summary);
                            return buildForecastNarrativeRun(summary.analysis_id, summary, detailGroup?.detail || detailGroup);
                        })
                );

                forecastNarrativeRuns = runs
                    .filter((run) => run.summary || run.targetLabel || run.detail)
                    .sort((a, b) => {
                        const scopeSort = getPredictionScopePriority(a.predictionScope) - getPredictionScopePriority(b.predictionScope);
                        if (scopeSort !== 0) {
                            return scopeSort;
                        }
                        return new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime();
                    });

                renderForecastNarrativeList();
            } catch (error) {
                console.error('Forecast narrative history load error:', error);
                if (predictionContainer) {
                    predictionContainer.innerHTML = `
                        <div class="analytics-empty-state">
                            <div class="analytics-empty-state__title">Unable to load forecast narratives.</div>
                            <p class="analytics-empty-state__copy">${escapeHtml(error.message || 'Please try again.')}</p>
                        </div>
                    `;
                }
            }
        }

        async function openForecastNarrativeModal(analysisId) {
            const normalizedId = Number(analysisId);
            const selectedRun = forecastNarrativeRuns.find((run) => Number(run.id) === normalizedId);
            if (!selectedRun) {
                showAnalyticsToast('Forecast narrative details are unavailable for this analysis run.', 'warning');
                return;
            }

            const detailGroup = await fetchRecommendationRunDetail(normalizedId, selectedRun.detail || selectedRun);
            const detail = detailGroup?.detail || detailGroup;
            if (!detail) {
                showAnalyticsToast('Forecast detail payload could not be loaded.', 'warning');
                return;
            }

            currentPredictionDetails = detail;
            syncAnalysisEvidenceButton(detail);
            currentAnalysisId = normalizedId;
            currentRecommendations = Array.isArray(detail.recommendations) ? detail.recommendations : [];
            currentBarangayPredictions = Array.isArray(detail.barangay_predictions)
                ? detail.barangay_predictions
                : (Array.isArray(detail.analysis_data?.raw_data?.barangay_predictions) ? detail.analysis_data.raw_data.barangay_predictions : []);
            openPredictionDetailModal();
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
                scopeMeta: getAnalysisScopeMeta(detail, summary),
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
            closeRecommendationRunModal();
            exploreRecommendation(group.recommendations[recommendationIndex], recommendationIndex);
        }

        async function loadRecommendationRunChecklists(analysisId) {
            const normalizedId = Number(analysisId);
            if (recommendationRunChecklistCache.has(normalizedId)) {
                return recommendationRunChecklistCache.get(normalizedId);
            }

            try {
                const response = await fetch(`/station-admin/api/action-plan-checklists?analysis_id=${encodeURIComponent(normalizedId)}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                if (!response.ok) {
                    throw new Error(`Failed to load checklist rows for analysis ${normalizedId}`);
                }

                const payload = await response.json();
                const checklists = Array.isArray(payload?.data) ? payload.data : [];
                recommendationRunChecklistCache.set(normalizedId, checklists);
                return checklists;
            } catch (error) {
                console.error('Recommendation run checklist load failed:', error);
                recommendationRunChecklistCache.set(normalizedId, []);
                return [];
            }
        }

        function renderRecommendationRunChecklistItems(checklist) {
            const items = Array.isArray(checklist?.items) && checklist.items.length
                ? checklist.items
                : (Array.isArray(checklist?.checklist_items)
                    ? checklist.checklist_items.map((label) => ({ item_text: label, status: 'pending' }))
                    : []);

            if (!items.length) {
                return '<div class="analytics-empty-state" style="padding:12px;"><div class="analytics-empty-state__title">No checklist items saved.</div></div>';
            }

            return `
                <div class="analytics-run-modal__checklist-items">
                    ${items.map((item) => {
                        const itemText = item?.item_text || item?.title || item?.label || 'Checklist item';
                        const itemStatus = String(item?.status || 'pending').toLowerCase();
                        const isChecked = itemStatus === 'followed' || itemStatus === 'confirmed';
                        const statusLabel = itemStatus.replace(/_/g, ' ');

                        return `
                            <label class="analytics-run-modal__checkbox">
                                <input type="checkbox" ${isChecked ? 'checked' : ''} disabled>
                                <span class="analytics-run-modal__checkbox-copy">
                                    ${escapeHtml(itemText)}
                                    <span class="analytics-run-modal__checkbox-meta">
                                        <i class="fa-solid ${isChecked ? 'fa-circle-check' : 'fa-clock'}" style="color:${isChecked ? '#16a34a' : '#f59e0b'};"></i>
                                        ${escapeHtml(statusLabel)}
                                    </span>
                                </span>
                            </label>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function renderRecommendationRunModalBody(group, checklists = []) {
            const prediction = group?.detail?.predictions || null;
            const summaryText = prediction?.summary || 'No saved prediction summary is available for this analysis run.';
            const recommendations = Array.isArray(group?.recommendations) ? group.recommendations : [];
            const checklistHtml = checklists.length
                ? `
                    <div class="analytics-run-modal__checklists">
                        ${checklists.map((checklist) => {
                            const progress = checklist?.checklist_progress || {};
                            const linkedMitigationCount = Number(checklist?.linked_mitigation_count || 0);
                            return `
                                <div class="analytics-run-modal__checklist">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                        <div>
                                            <div style="font-size:14px; font-weight:800; color:#0f172a;">${escapeHtml(checklist?.plan_title || checklist?.source_title || 'Action Plan Checklist')}</div>
                                            <div style="font-size:12px; color:#64748b; margin-top:4px;">${escapeHtml(checklist?.status || 'pending')}</div>
                                        </div>
                                        <span style="padding:6px 10px; border-radius:999px; background:#fff7ed; color:#9a3412; font-size:11px; font-weight:800;">
                                            ${escapeHtml(String(progress.percent ?? 0))}% progress
                                        </span>
                                    </div>
                                    ${checklist?.plan_description ? `<div style="font-size:13px; line-height:1.6; color:#475569; margin-top:8px;">${escapeHtml(checklist.plan_description)}</div>` : ''}
                                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                                        <span class="recommendation-run-chip"><i class="fa-solid fa-list-check" style="color:#2563eb;"></i>${escapeHtml(String((Array.isArray(checklist?.items) && checklist.items.length) || (Array.isArray(checklist?.checklist_items) ? checklist.checklist_items.length : 0)))} checklist item(s)</span>
                                        <span class="recommendation-run-chip"><i class="fa-solid fa-shield-halved" style="color:#dc2626;"></i>${escapeHtml(String(linkedMitigationCount))} mitigation link(s)</span>
                                    </div>
                                    ${renderRecommendationRunChecklistItems(checklist)}
                                </div>
                            `;
                        }).join('')}
                    </div>
                `
                : `
                    <div class="analytics-empty-state">
                        <div class="analytics-empty-state__title">No checklist rows saved for this analysis yet.</div>
                        <p class="analytics-empty-state__copy">Run details and recommendations are available, but no stored action-plan checklist rows were found for this saved analysis.</p>
                    </div>
                `;

            return `
                <div style="display:grid; gap:14px;">
                    <div class="analytics-run-modal__hero">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                            <div>
                                <div style="font-size:12px; font-weight:800; color:#9a3412; text-transform:uppercase; letter-spacing:0.08em;">${escapeHtml(group.scopeLabel || 'Run AI Analysis')}</div>
                                <div style="font-size:22px; font-weight:900; color:#0f172a; margin-top:4px;">${escapeHtml(group.sector || 'Station Analysis')}</div>
                                <div style="font-size:12px; color:#64748b; margin-top:6px;">${escapeHtml(formatRecommendationRunTimestamp(group.createdAt))}</div>
                            </div>
                            <span style="padding:7px 12px; border-radius:999px; background:#fff; border:1px solid rgba(220,38,38,0.12); color:#b91c1c; font-size:12px; font-weight:800;">
                                ${escapeHtml(group.targetLabel || 'Current forecast')}
                            </span>
                        </div>
                        <p style="margin-top:12px; font-size:13px; line-height:1.7; color:#334155;">${escapeHtml(summaryText)}</p>
                    </div>

                    <div class="analytics-run-modal__grid">
                        <div class="analytics-run-modal__stat">
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Risk Level</div>
                            <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:6px;">${escapeHtml(group.riskLevel || 'Unknown')}</div>
                        </div>
                        <div class="analytics-run-modal__stat">
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Confidence</div>
                            <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:6px;">${group.confidenceScore !== null && group.confidenceScore !== undefined ? `${escapeHtml(String(group.confidenceScore))}%` : 'N/A'}</div>
                        </div>
                        <div class="analytics-run-modal__stat">
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Predicted Incidents</div>
                            <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:6px;">${escapeHtml(String(prediction?.predicted_fire_incidents ?? group.incidentCount ?? 'N/A'))}</div>
                        </div>
                        <div class="analytics-run-modal__stat">
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Recommendations</div>
                            <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:6px;">${escapeHtml(String(recommendations.length))}</div>
                        </div>
                    </div>

                    <div style="padding:14px; border-radius:16px; border:1px solid #e2e8f0; background:#fff;">
                        <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">AI Recommendations</div>
                        ${recommendations.length ? recommendations.map((rec, idx) => {
                            const meta = extractRecommendationMeta(rec);
                            let priorityClass = 'medium';
                            let priorityLabel = 'MEDIUM';
                            if (rec.priority === 'Critical') { priorityClass = 'critical'; priorityLabel = 'CRITICAL'; }
                            else if (rec.priority === 'High') { priorityClass = 'high'; priorityLabel = 'HIGH'; }
                            else if (rec.priority === 'Low') { priorityClass = 'low'; priorityLabel = 'LOW'; }

                            return `
                                <div class="recommendation-card ${priorityClass}">
                                    <div style="display:flex; gap:12px;">
                                        <div style="width:28px; height:28px; border-radius:50%; background:${priorityClass === 'critical' ? '#dc2626' : (priorityClass === 'high' ? '#f59e0b' : (priorityClass === 'medium' ? '#10b981' : '#94a3b8'))}; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800;">${idx + 1}</div>
                                        <div style="flex:1;">
                                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                                <div style="width:34px; height:34px; border-radius:12px; background:${meta.accent}15; color:${meta.accent}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                    <i class="fa-solid ${meta.icon}"></i>
                                                </div>
                                                <div style="flex:1;">
                                                    <strong style="display:block; color:#0f172a;">${escapeHtml(rec.title || 'Recommendation')}</strong>
                                                    <p style="color:#4b5563; font-size:13px; margin-top:6px;">${escapeHtml(rec.description || '')}</p>
                                                </div>
                                            </div>
                                            <span class="priority-badge priority-${priorityClass}">${priorityLabel} PRIORITY</span>
                                            <button class="explore-btn" onclick="openRecommendationFromRun(${group.id}, ${idx})">
                                                <i class="fa-solid fa-chart-simple"></i> Explore & Get Detailed Suggestions
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('') : '<div class="analytics-empty-state"><div class="analytics-empty-state__title">No saved recommendations found.</div></div>'}
                    </div>

                    <div style="padding:14px; border-radius:16px; border:1px solid #e2e8f0; background:#fff;">
                        <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">Action Plan Checklist</div>
                        ${checklistHtml}
                    </div>
                </div>
            `;
        }

        async function openRecommendationRunModal(analysisId) {
            const normalizedId = Number(analysisId);
            const modal = document.getElementById('recommendationRunModal');
            const body = document.getElementById('recommendationRunModalBody');
            const existingGroup = recommendationRunGroups.find((group) => Number(group.id) === normalizedId) || recommendationRunCache.get(normalizedId);

            if (!modal || !body || !existingGroup) {
                showAnalyticsToast('Recommendation run details are unavailable right now.', 'warning');
                return;
            }

            body.innerHTML = `
                <div class="text-center py-8">
                    <div class="loading-spinner" style="width: 34px; height: 34px;"></div>
                    <p class="mt-3 text-gray-500">Loading clustered recommendation details...</p>
                </div>
            `;
            modal.classList.add('show');

            const detailedGroup = await fetchRecommendationRunDetail(normalizedId, existingGroup.detail ? existingGroup : existingGroup);
            const checklists = await loadRecommendationRunChecklists(normalizedId);
            currentRecommendationRunGroup = detailedGroup;
            recommendationRunGroups = recommendationRunGroups.map((group) =>
                Number(group.id) === normalizedId ? { ...group, ...detailedGroup } : group
            );
            body.innerHTML = renderRecommendationRunModalBody(detailedGroup, checklists);
        }

        function closeRecommendationRunModal() {
            document.getElementById('recommendationRunModal')?.classList.remove('show');
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

            const barangayGroups = recommendationRunGroups.filter((group) => group.scopeMeta?.isBarangaySpecific);
            const stationGroups = recommendationRunGroups.filter((group) => !group.scopeMeta?.isBarangaySpecific);
            const renderRecommendationRunCard = (group) => {
                const recommendations = Array.isArray(group.recommendations) ? group.recommendations : [];
                const summaryLine = `${group.sector} · ${formatRecommendationRunTimestamp(group.createdAt)}`;
                const chips = [
                    group.targetLabel ? `<span class="recommendation-run-chip"><i class="fa-solid fa-calendar-days" style="color:#dc2626;"></i>${escapeHtml(group.targetLabel)}</span>` : '',
                    `<span class="recommendation-run-chip"><i class="fa-solid ${group.scopeMeta?.isBarangaySpecific ? 'fa-map-pin' : 'fa-map'}" style="color:${group.scopeMeta?.isBarangaySpecific ? '#dc2626' : '#475569'};"></i>${escapeHtml(group.scopeMeta?.chipLabel || 'All barangays')}</span>`,
                    `<span class="recommendation-run-chip"><i class="fa-solid fa-list-check" style="color:#2563eb;"></i>${escapeHtml(String(recommendations.length))} recommendation(s)</span>`,
                    group.riskLevel ? `<span class="recommendation-run-chip"><i class="fa-solid fa-shield-heart" style="color:#f59e0b;"></i>${escapeHtml(group.riskLevel)}</span>` : '',
                    group.confidenceScore !== null && group.confidenceScore !== undefined ? `<span class="recommendation-run-chip"><i class="fa-solid fa-crosshairs" style="color:#059669;"></i>${escapeHtml(String(group.confidenceScore))}% confidence</span>` : ''
                ].filter(Boolean).join('');

                return `
                    <div class="recommendation-run-group">
                        <button type="button" class="recommendation-run-toggle" onclick="openRecommendationRunModal(${group.id})">
                            <div class="recommendation-run-meta">
                                <div class="recommendation-run-title">
                                    <i class="fa-solid fa-layer-group" style="color:#dc2626;"></i>
                                    <span>${escapeHtml(group.scopeLabel)}</span>
                                </div>
                                <div class="recommendation-run-subtitle">${escapeHtml(summaryLine)}</div>
                                <div class="recommendation-run-subtitle" style="color:${group.scopeMeta?.isBarangaySpecific ? '#dc2626' : '#64748b'}; font-weight:700;">
                                    ${escapeHtml(group.scopeMeta?.detailLabel || 'Saved analysis run')}
                                </div>
                                <div class="recommendation-run-chips">${chips}</div>
                                <div class="recommendation-run-action">
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                    Open clustered recommendations
                                </div>
                            </div>
                            <span class="recommendation-run-arrow"><i class="fa-solid fa-up-right-from-square"></i></span>
                        </button>
                    </div>
                `;
            };
            const renderRecommendationRunSection = (title, copy, groups) => {
                if (!groups.length) {
                    return '';
                }

                return `
                    <section class="recommendation-run-section" style="display:grid; gap:12px;">
                        <div class="analytics-section-heading" style="display:grid; gap:4px;">
                            <div style="font-size:0.9rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#475569;">${escapeHtml(title)}</div>
                            <p style="margin:0; color:#64748b; font-size:0.92rem;">${escapeHtml(copy)}</p>
                        </div>
                        <div class="recommendation-run-list">
                            ${groups.map((group) => renderRecommendationRunCard(group)).join('')}
                        </div>
                    </section>
                `;
            };

            recContainer.innerHTML = `
                <div style="display:grid; gap:20px;">
                    ${renderRecommendationRunSection(
                        'Latest Barangay-Specific Runs',
                        'Clustered recommendation sets tied to one selected barangay appear here.',
                        barangayGroups
                    )}
                    ${renderRecommendationRunSection(
                        'Latest Station-Wide Runs',
                        'Clustered recommendation sets for the whole station appear here.',
                        stationGroups
                    )}
                </div>
            `;
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
                const response = await fetch(`/station-admin/api/analyses/history?${buildAnalysisScopeQuery(priorityAnalysisData?.prediction_scope || 'current_month')}`, {
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
                        .slice(0, 3)
                        .map((summary) => fetchRecommendationRunDetail(summary.analysis_id, summary))
                );

                recommendationRunGroups = groups.sort((a, b) => new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime());
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

        function formatLearningMetric(value, suffix = '', decimals = 4) {
            if (value === null || value === undefined || value === '') {
                return 'Unavailable';
            }

            const numericValue = Number(value);
            if (!Number.isFinite(numericValue)) {
                return String(value);
            }

            return `${numericValue.toFixed(decimals)}${suffix}`;
        }

        function setChartEmptyNote(containerId, message = '') {
            const container = document.getElementById(containerId);
            if (!container) return;

            const normalized = String(message || '').trim();
            container.textContent = normalized;
            container.classList.toggle('is-visible', normalized !== '');
        }

        function renderModelLearningPerformance(analysisData) {
            const modelMetadata = analysisData?.model_metadata || {};
            const learning = modelMetadata.learning_performance || {};
            const featureImportance = Array.isArray(learning.feature_importance) && learning.feature_importance.length
                ? learning.feature_importance
                : (Array.isArray(modelMetadata.global_feature_importance) ? modelMetadata.global_feature_importance : []);
            const classification = learning.classification_performance || {};
            const crossValidation = learning.cross_validation || {};
            const actualVsPredicted = learning.actual_vs_predicted || null;
            const residualPattern = learning.residual_pattern || null;
            const regressionMetrics = learning.regression_performance || modelMetadata.regression_metrics || {};
            const compactChart = isAnalyticsMobile();
            const chartFontSize = compactChart ? 10 : 12;
            const chartPointRadius = compactChart ? 2 : 3;
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

            const metricGrid = document.getElementById('modelPerformanceMetricGrid');
            const modelMeta = document.getElementById('modelPerformanceMeta');
            const modelStatus = document.getElementById('modelPerformanceStatus');
            const modelPerformanceExplainer = document.getElementById('modelPerformanceExplainer');
            const modelTrainingEvidence = document.getElementById('modelTrainingEvidence');
            const modelTrainingFeatures = document.getElementById('modelTrainingFeatures');
            const modelTrainingNotes = document.getElementById('modelTrainingNotes');
            const precisionValue = Number(classification.precision);
            const recallValue = Number(classification.recall);
            const rocAucValue = Number(classification.roc_auc ?? modelMetadata.metrics?.roc_auc);
            const testPositiveSupport = Number(
                classification.classification_report?.['1']?.support
                ?? modelMetadata.metrics?.classification_report?.['1']?.support
                ?? 0
            );

            if (modelStatus) {
                const statusMessage = learning.available
                    ? 'Saved model metadata and cached row-level evaluation data are ready. The charts below are using the current trained model without retraining on page load.'
                    : (learning.partial
                        ? 'The page is currently using saved model metadata. Row-level evaluation charts will appear after the cached evaluation artifact is generated successfully for this trained model.'
                        : 'Saved model performance metadata is limited right now, so only supported sections will be shown.');
                modelStatus.textContent = statusMessage;
                modelStatus.classList.toggle('is-ready', Boolean(learning.available));
            }

            if (metricGrid) {
                const incidentRegression = regressionMetrics.predicted_incident_count || {};
                metricGrid.innerHTML = `
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">Model type</div>
                        <div class="analytics-metric-card__value" style="font-size:20px;">${escapeHtml(learning.model_type || 'Random Forest')}</div>
                        <div class="analytics-metric-card__copy">The current forecast stack uses tree-based learning, so epoch curves are intentionally not shown.</div>
                    </div>
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">Train / test rows</div>
                        <div class="analytics-metric-card__value">${escapeHtml(String(classification.train_rows ?? modelMetadata.metrics?.train_rows ?? '0'))} / ${escapeHtml(String(classification.test_rows ?? modelMetadata.metrics?.test_rows ?? '0'))}</div>
                        <div class="analytics-metric-card__copy">Saved split from the most recent model training run.</div>
                    </div>
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">Accuracy</div>
                        <div class="analytics-metric-card__value">${escapeHtml(formatLearningMetric(classification.accuracy ?? modelMetadata.metrics?.accuracy, '', 4))}</div>
                        <div class="analytics-metric-card__copy">Classification accuracy on the saved test split.</div>
                    </div>
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">ROC AUC</div>
                        <div class="analytics-metric-card__value">${escapeHtml(formatLearningMetric(classification.roc_auc ?? modelMetadata.metrics?.roc_auc, '', 4))}</div>
                        <div class="analytics-metric-card__copy">Probability discrimination score for the high-risk classification target.</div>
                    </div>
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">Incident-count MAE</div>
                        <div class="analytics-metric-card__value">${escapeHtml(formatLearningMetric(incidentRegression.mae, '', 4))}</div>
                        <div class="analytics-metric-card__copy">Average absolute error for the saved incident-count regression output.</div>
                    </div>
                    <div class="analytics-metric-card">
                        <div class="analytics-metric-card__label">Incident-count R²</div>
                        <div class="analytics-metric-card__value">${escapeHtml(formatLearningMetric(incidentRegression.r2_score, '', 4))}</div>
                        <div class="analytics-metric-card__copy">Explains how closely the regression output follows actual incident-count variation.</div>
                    </div>
                `;
            }

            if (modelMeta) {
                const pills = [
                    learning.available
                        ? `<span class="analytics-model-meta__pill"><i class="fa-solid fa-database"></i> Cached evaluation artifact ready</span>`
                        : `<span class="analytics-model-meta__pill"><i class="fa-solid fa-clock-rotate-left"></i> Using saved metadata${learning.partial ? ' until row-level cache is ready' : ''}</span>`,
                    `<span class="analytics-model-meta__pill"><i class="fa-solid fa-calendar-check"></i> Trained at ${escapeHtml(modelMetadata.trained_at || learning.trained_at || 'Unavailable')}</span>`,
                    `<span class="analytics-model-meta__pill"><i class="fa-solid fa-ruler-combined"></i> Cross-validation metric: ${escapeHtml(crossValidation.metric || 'ROC AUC')}</span>`,
                    `<span class="analytics-model-meta__pill"><i class="fa-solid fa-circle-info"></i> ${escapeHtml(learning.supports_epoch_learning_curve ? 'Epoch learning curves supported' : 'No epoch learning curves for Random Forest')}</span>`,
                ];

                modelMeta.innerHTML = pills.join('');
            }

            if (modelPerformanceExplainer) {
                let explainerHtml = '';

                if (Number.isFinite(rocAucValue) && rocAucValue >= 0.8 && precisionValue === 0 && recallValue === 0) {
                    explainerHtml = `
                        <strong>How to read this:</strong> the saved cross-validation ROC-AUC is strong, which means the model is generally ranking higher-risk records above lower-risk ones.
                        But the saved test-split precision and recall are both 0.0000, so on the final yes/no classification step it did not correctly flag the positive high-risk class.
                        ${testPositiveSupport > 0 ? `The saved test split only had <strong>${escapeHtml(String(testPositiveSupport))}</strong> positive case(s), so this usually points to class imbalance or a threshold that is too conservative.` : 'This usually points to class imbalance or a threshold that is too conservative.'}
                    `;
                } else if (Number.isFinite(rocAucValue)) {
                    explainerHtml = `
                        <strong>How to read this:</strong> ROC-AUC shows how well the model separates higher-risk from lower-risk records across thresholds, while precision and recall show how well it performs after converting scores into a final class decision.
                    `;
                }

                modelPerformanceExplainer.innerHTML = explainerHtml;
                modelPerformanceExplainer.style.display = explainerHtml ? 'block' : 'none';
            }

            if (modelTrainingEvidence) {
                const datasetKind = modelMetadata.dataset_kind || 'Unknown';
                const datasetPath = modelMetadata.dataset_path || 'Unavailable';
                const targetColumn = modelMetadata.target_column || 'Unavailable';
                const targetDescription = modelMetadata.target_description || 'No saved target description found.';
                const recordsUsed = modelMetadata.records_used ?? 'N/A';
                const featureCount = modelMetadata.feature_count ?? 0;
                const topSignals = Array.isArray(modelMetadata.top_global_signals)
                    ? modelMetadata.top_global_signals.map((signal) => signal?.feature).filter(Boolean).slice(0, 5)
                    : [];

                modelTrainingEvidence.innerHTML = `
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Training dataset</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(datasetKind))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Dataset file</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(datasetPath))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Prediction target</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(targetColumn))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Target meaning</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(targetDescription))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Records used</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(recordsUsed))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Input features</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(featureCount))} feature${Number(featureCount) === 1 ? '' : 's'}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Model family</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(String(learning.model_type || 'RandomForestClassifier + RandomForestRegressor'))}</div>
                    </div>
                    <div class="analytics-model-training-card">
                        <div class="analytics-model-training-card__label">Top contributing signals</div>
                        <div class="analytics-model-training-card__value">${escapeHtml(topSignals.join(', ') || 'Unavailable')}</div>
                    </div>
                `;
            }

            if (modelTrainingFeatures) {
                const featureColumns = Array.isArray(modelMetadata.feature_columns) ? modelMetadata.feature_columns : [];
                modelTrainingFeatures.innerHTML = featureColumns.length
                    ? featureColumns.map((feature) => `
                        <span class="analytics-model-chip">
                            <i class="fa-solid fa-diagram-project"></i>${escapeHtml(String(feature))}
                        </span>
                    `).join('')
                    : `
                        <span class="analytics-model-chip">
                            <i class="fa-solid fa-circle-info"></i>No saved feature column list is available.
                        </span>
                    `;
            }

            if (modelTrainingNotes) {
                const notes = Array.isArray(modelMetadata.notes) ? modelMetadata.notes.filter(Boolean) : [];
                modelTrainingNotes.innerHTML = notes.length
                    ? notes.map((note) => `
                        <div class="analytics-model-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>${escapeHtml(String(note))}</span>
                        </div>
                    `).join('')
                    : `
                        <div class="analytics-model-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>No saved training notes were found in the current model metadata.</span>
                        </div>
                    `;
            }

            if (featureImportanceChart) featureImportanceChart.destroy();
            if (crossValidationChart) crossValidationChart.destroy();
            if (actualVsPredictedChart) actualVsPredictedChart.destroy();
            if (residualPatternChart) residualPatternChart.destroy();

            setChartEmptyNote('featureImportanceEmptyNote', '');
            setChartEmptyNote('crossValidationEmptyNote', '');
            setChartEmptyNote('actualVsPredictedEmptyNote', '');
            setChartEmptyNote('residualPatternEmptyNote', '');

            const featureCtx = document.getElementById('featureImportanceChart')?.getContext('2d');
            if (featureCtx && featureImportance.length) {
                const reversed = [...featureImportance].slice(0, 8).reverse();
                featureImportanceChart = new Chart(featureCtx, {
                    type: 'bar',
                    data: {
                        labels: reversed.map((item) => item.feature || 'Feature'),
                        datasets: [{
                            label: 'Importance',
                            data: reversed.map((item) => Number(item.importance || 0)),
                            backgroundColor: 'rgba(220,38,38,0.78)',
                            borderRadius: compactChart ? 5 : 7,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: chartTicks,
                                title: { display: !compactChart, text: 'Relative importance' }
                            },
                            y: {
                                ticks: chartTicks,
                            }
                        }
                    }
                });
            } else {
                setChartEmptyNote(
                    'featureImportanceEmptyNote',
                    'Feature importance is unavailable because the current saved metadata does not contain aggregated importance values.'
                );
            }

            setInsightList(
                'featureImportanceInsights',
                [
                    featureImportance[0]?.feature ? `${featureImportance[0].feature} is currently the strongest global signal in the saved model.` : null,
                    featureImportance[1]?.feature ? `${featureImportance[1].feature} is the next strongest contributor in the current model metadata.` : null,
                    learning.warning || null,
                ].filter(Boolean),
                'Feature importance is unavailable for the current saved model metadata.'
            );

            const cvScores = Array.isArray(crossValidation.scores) ? crossValidation.scores : [];
            const crossValidationCtx = document.getElementById('crossValidationChart')?.getContext('2d');
            if (crossValidationCtx && cvScores.length) {
                crossValidationChart = new Chart(crossValidationCtx, {
                    type: 'line',
                    data: {
                        labels: cvScores.map((_, index) => `Fold ${index + 1}`),
                        datasets: [{
                            label: crossValidation.metric || 'ROC AUC',
                            data: cvScores,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,0.12)',
                            pointBackgroundColor: '#2563eb',
                            pointRadius: chartPointRadius,
                            tension: 0.25,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: chartLegend },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: chartTicks,
                                title: { display: !compactChart, text: crossValidation.metric || 'ROC AUC' }
                            },
                            x: {
                                ticks: chartTicks,
                            }
                        }
                    }
                });
            } else {
                setChartEmptyNote(
                    'crossValidationEmptyNote',
                    'Cross-validation cannot be displayed yet because the current saved training metadata does not include fold-by-fold validation scores.'
                );
            }

            setInsightList(
                'crossValidationInsights',
                [
                    cvScores.length ? `The saved cross-validation mean is ${formatLearningMetric(crossValidation.mean, '', 4)} across ${cvScores.length} fold(s).` : null,
                    classification.precision !== undefined && classification.recall !== undefined
                        ? `Precision is ${formatLearningMetric(classification.precision, '', 4)} while recall is ${formatLearningMetric(classification.recall, '', 4)} on the classification test split.`
                        : null,
                ].filter(Boolean),
                'Cross-validation scores are unavailable for the current saved model metadata.'
            );

            const actualVsPredictedCtx = document.getElementById('actualVsPredictedChart')?.getContext('2d');
            if (actualVsPredictedCtx && actualVsPredicted?.labels?.length) {
                actualVsPredictedChart = new Chart(actualVsPredictedCtx, {
                    type: 'line',
                    data: {
                        labels: actualVsPredicted.labels,
                        datasets: [
                            {
                                label: 'Actual',
                                data: actualVsPredicted.actual || [],
                                borderColor: '#dc2626',
                                backgroundColor: 'rgba(220,38,38,0.10)',
                                pointRadius: chartPointRadius,
                                tension: 0.3,
                                fill: false,
                            },
                            {
                                label: 'Predicted',
                                data: actualVsPredicted.predicted || [],
                                borderColor: '#0f766e',
                                backgroundColor: 'rgba(15,118,110,0.10)',
                                pointRadius: chartPointRadius,
                                tension: 0.3,
                                borderDash: [6, 5],
                                fill: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: chartLegend },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: chartTicks,
                                title: { display: !compactChart, text: actualVsPredicted.metric_label || 'Actual vs predicted' }
                            },
                            x: {
                                ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                                title: { display: !compactChart, text: actualVsPredicted.x_axis_label || 'Held-out evaluation period' }
                            }
                        }
                    }
                });
            } else {
                setChartEmptyNote(
                    'actualVsPredictedEmptyNote',
                    learning.partial
                        ? 'Actual-versus-predicted points are waiting for the cached row-level evaluation artifact. The model summary above is still based on the saved trained metadata.'
                        : 'Actual-versus-predicted evaluation points are unavailable for the current saved model output.'
                );
            }

            setInsightList(
                'actualVsPredictedInsights',
                [
                    actualVsPredicted?.labels?.length
                        ? (actualVsPredicted.source === 'saved_forecast_runs'
                            ? 'This chart is currently using saved forecast runs for the selected scope while the row-level training evaluation cache is unavailable.'
                            : `This chart uses held-out evaluation points from the saved ${actualVsPredicted.target_column || 'incident_count'} regression target.`)
                        : null,
                    actualVsPredicted?.actual?.length ? `Actual historical observations and predicted values are displayed separately for the same evaluation periods.` : null,
                    !actualVsPredicted ? (learning.warning || 'Row-level actual-versus-predicted data has not been cached yet.') : null,
                ].filter(Boolean),
                'Actual versus predicted evaluation data is unavailable for the current saved model.'
            );

            const residualPatternCtx = document.getElementById('residualPatternChart')?.getContext('2d');
            if (residualPatternCtx && residualPattern?.labels?.length) {
                residualPatternChart = new Chart(residualPatternCtx, {
                    type: 'bar',
                    data: {
                        labels: residualPattern.labels,
                        datasets: [{
                            label: residualPattern.metric_label || 'Residual',
                            data: residualPattern.residuals || [],
                            backgroundColor: (residualPattern.residuals || []).map((value) => Number(value || 0) >= 0 ? 'rgba(37,99,235,0.78)' : 'rgba(220,38,38,0.78)'),
                            borderRadius: compactChart ? 4 : 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: chartLegend },
                        scales: {
                            y: {
                                ticks: chartTicks,
                                title: { display: !compactChart, text: residualPattern.metric_label || 'Prediction error' }
                            },
                            x: {
                                ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                                title: { display: !compactChart, text: residualPattern.x_axis_label || 'Held-out evaluation period' }
                            }
                        }
                    }
                });
            } else {
                setChartEmptyNote(
                    'residualPatternEmptyNote',
                    learning.partial
                        ? 'Residual analysis will appear after the cached row-level regression evaluation is generated for this trained model.'
                        : 'Residual analysis is unavailable because the current saved model output does not include regression evaluation rows.'
                );
            }

            const residuals = Array.isArray(residualPattern?.residuals) ? residualPattern.residuals.map((value) => Number(value || 0)) : [];
            const positiveResiduals = residuals.filter((value) => value > 0).length;
            const negativeResiduals = residuals.filter((value) => value < 0).length;
            setInsightList(
                'residualPatternInsights',
                [
                    residuals.length ? `${positiveResiduals} held-out periods were over-predicted, while ${negativeResiduals} were under-predicted by the incident-count regression output.` : null,
                    residuals.length ? 'Positive bars mean the model predicted more incidents than actually occurred; negative bars mean it predicted fewer.' : null,
                    !residuals.length ? (learning.warning || 'Residual data has not been cached yet.') : null,
                ].filter(Boolean),
                'Residual data is unavailable for the current saved model.'
            );
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
            const hasPreviousWindow = previousSeries.length > 0;
            const hasCurrentWindow = currentSeries.length > 0;
            const previousTotal = previousSeries.reduce((sum, value) => sum + Number(value || 0), 0);
            const currentTotal = currentSeries.reduce((sum, value) => sum + Number(value || 0), 0);
            const trendDelta = previousTotal > 0 ? (((currentTotal - previousTotal) / previousTotal) * 100) : null;

            const hotspot = analysisData.selected_barangay_prediction
                || (Array.isArray(analysisData.barangay_predictions)
                    ? [...analysisData.barangay_predictions].sort((a, b) => Number(b.probability_percent || 0) - Number(a.probability_percent || 0))[0]
                    : null);

            const probability = prediction?.model_prediction?.probability_percent ?? hotspot?.probability_percent ?? null;
            const expectedCount = prediction?.expected_month_total_fire_incidents
                ?? prediction?.predicted_fire_incidents
                ?? hotspot?.expected_month_total_fire_incidents
                ?? hotspot?.predicted_fire_incidents
                ?? prediction?.model_prediction?.predicted_incident_count
                ?? null;
            const scope = analysisData.prediction_scope || prediction?.prediction_scope || 'current_month';
            const observedCount = prediction?.observed_fire_incidents_to_date
                ?? (scope === 'current_month' ? analysisData.prediction_context?.current_month_fire_incidents : 0)
                ?? 0;
            const additionalCount = prediction?.predicted_additional_fire_incidents
                ?? (expectedCount !== null ? Math.max(0, Number(expectedCount) - Number(observedCount || 0)) : null);
            const incidentRange = prediction?.incident_count_range
                ?? prediction?.model_prediction?.incident_count_range
                ?? hotspot?.incident_count_range
                ?? null;
            const calibration = prediction?.forecast_calibration
                ?? prediction?.model_prediction?.forecast_calibration
                ?? null;
            const confidenceScore = analysisData.confidence_score ?? prediction?.model_prediction?.confidence_band ?? null;
            const drivers = Array.isArray(prediction?.drivers) ? prediction.drivers : [];

            document.getElementById('summaryProbability').textContent = probability !== null ? `${Number(probability).toFixed(1)}%` : 'Unavailable';
            document.getElementById('summaryObservedCount').textContent = scope === 'current_month' ? String(observedCount) : 'Not started';
            document.getElementById('summaryAdditionalCount').textContent = additionalCount !== null ? String(additionalCount) : 'Unavailable';
            document.getElementById('summaryIncidentCount').textContent = expectedCount !== null ? String(expectedCount) : 'Unavailable';
            const rangeElement = document.getElementById('summaryIncidentRange');
            const rangeCopy = document.getElementById('summaryIncidentRangeCopy');
            if (rangeElement) {
                const lower = Number(incidentRange?.lower);
                const upper = Number(incidentRange?.upper);
                rangeElement.textContent = Number.isFinite(lower) && Number.isFinite(upper)
                    ? (lower === upper ? String(lower) : `${lower}–${upper}`)
                    : 'Unavailable';
            }
            if (rangeCopy && calibration) {
                rangeCopy.textContent = `Raw model sum ${Number(calibration.raw_model_total || 0)} · observed-pace projection ${Number(calibration.pace_projection || 0).toFixed(1)} · historical average ${Number(calibration.historical_same_month_average || 0).toFixed(1)}.`;
            } else if (rangeCopy) {
                rangeCopy.textContent = 'Planning interval based on model output, observed pace, and same-month history.';
            }
            document.getElementById('riskLabel').textContent = analysisData.risk_level || prediction?.outlook || 'Unavailable';
            document.getElementById('confidenceScore').textContent = confidenceScore !== null
                ? (typeof confidenceScore === 'number' ? `${confidenceScore}%` : String(confidenceScore))
                : 'Unavailable';
            const confidenceCopy = document.getElementById('confidenceScoreCopy');
            const confidenceContext = analysisData.confidence_context || {};
            if (confidenceCopy) {
                const factors = Array.isArray(confidenceContext.factors) ? confidenceContext.factors.slice(0, 3) : [];
                confidenceCopy.textContent = factors.length
                    ? `Evidence score: ${factors.join(' · ')}`
                    : 'Evidence coverage across incidents, history, barangay forecasts, risk findings, weather, and model availability.';
                confidenceCopy.title = Array.isArray(confidenceContext.factors)
                    ? confidenceContext.factors.join('\n')
                    : '';
            }

            const trendDirection = document.getElementById('summaryTrendDirection');
            const trendCopy = document.getElementById('summaryTrendCopy');
            const trendIndicator = document.getElementById('summaryTrendIndicator');
            if (trendDirection && trendCopy && trendIndicator) {
                if (!hasPreviousWindow || !hasCurrentWindow) {
                    trendDirection.textContent = 'Comparison unavailable';
                    trendCopy.textContent = 'A previous seven-day comparison will appear once the backend has both current and previous actual incident periods available.';
                    trendIndicator.innerHTML = '<i class="fa-solid fa-chart-simple"></i><span>No previous period data available</span>';
                } else if (previousTotal === 0 && currentTotal === 0) {
                    trendDirection.textContent = 'Holding steady';
                    trendCopy.textContent = 'Both the current and previous seven-day periods recorded 0 incidents.';
                    trendIndicator.innerHTML = '<i class="fa-solid fa-equals"></i><span>0 incidents in both periods</span>';
                } else if (previousTotal === 0) {
                    trendDirection.textContent = 'Rising against previous period';
                    trendCopy.textContent = `Current seven-day activity totals ${currentTotal} incidents after a previous seven-day baseline of 0 incidents.`;
                    trendIndicator.innerHTML = '<i class="fa-solid fa-arrow-trend-up"></i><span>Previous period had 0 incidents</span>';
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
            const historicalTrend = chartContext.historical_trend || {};
            const seasonalPattern = chartContext.seasonal_pattern || {};
            const monthlyPattern = chartContext.monthly_pattern || {};
            const mlTrendAnalysis = chartContext.ml_trend_analysis || {};
            const weatherPattern = chartContext.weather_pattern || {};
            const dayOfWeekPattern = chartContext.day_of_week_pattern || {};
            const historicalVsPredicted = chartContext.historical_vs_predicted || {};
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
            if (historicalTrendChart) historicalTrendChart.destroy();
            if (seasonalPatternChart) seasonalPatternChart.destroy();
            if (monthlyPatternChart) monthlyPatternChart.destroy();
            if (weatherPatternChart) weatherPatternChart.destroy();
            if (forecastCompareChart) forecastCompareChart.destroy();
            if (mlTrendAnalysisChart) mlTrendAnalysisChart.destroy();
            if (mlMonthComparisonChart) mlMonthComparisonChart.destroy();
            if (dayOfWeekPatternChart) dayOfWeekPatternChart.destroy();

            const historicalTrendCtx = document.getElementById('historicalTrendChart').getContext('2d');
            historicalTrendChart = new Chart(historicalTrendCtx, {
                type: 'line',
                data: {
                    labels: historicalTrend.labels || [],
                    datasets: [
                        {
                            label: 'Current Actual',
                            data: historicalTrend.current_actual || [],
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220,38,38,0.08)',
                            pointBackgroundColor: '#dc2626',
                            pointRadius: chartPointRadius,
                            tension: 0.3,
                            fill: false,
                        },
                        {
                            label: '3-Year Historical Average',
                            data: historicalTrend.historical_average || [],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.1)',
                            borderDash: [6, 6],
                            pointRadius: 0,
                            tension: 0.25,
                            fill: false,
                        },
                        ...((historicalTrend.historical_years || []).map((yearSeries, index) => ({
                            label: `Historical ${yearSeries.label || yearSeries.year}`,
                            data: yearSeries.values || [],
                            borderColor: ['#94a3b8', '#64748b', '#475569'][index % 3],
                            backgroundColor: 'rgba(148,163,184,0.05)',
                            pointRadius: compactChart ? 1 : 2,
                            tension: 0.2,
                            borderDash: [3, 5],
                            fill: false,
                        })))
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: chartLegend },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                            title: { display: !compactChart, text: 'Calendar-aligned date' }
                        }
                    }
                }
            });

            const seasonalPatternCtx = document.getElementById('seasonalPatternChart').getContext('2d');
            seasonalPatternChart = new Chart(seasonalPatternCtx, {
                type: 'bar',
                data: {
                    labels: seasonalPattern.labels || [],
                    datasets: [
                        {
                            label: 'Current Actual',
                            data: seasonalPattern.current_actual || [],
                            backgroundColor: 'rgba(220,38,38,0.78)',
                            borderRadius: compactChart ? 6 : 8,
                        },
                        {
                            label: 'Historical Average',
                            data: seasonalPattern.historical_average || [],
                            backgroundColor: 'rgba(37,99,235,0.78)',
                            borderRadius: compactChart ? 6 : 8,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: chartLegend },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        x: {
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Season' }
                        }
                    }
                }
            });

            const monthlyPatternCtx = document.getElementById('monthlyPatternChart').getContext('2d');
            monthlyPatternChart = new Chart(monthlyPatternCtx, {
                type: 'bar',
                data: {
                    labels: monthlyPattern.labels || [],
                    datasets: [
                        {
                            label: 'Current Actual',
                            data: monthlyPattern.current_actual || [],
                            backgroundColor: 'rgba(220,38,38,0.72)',
                            borderRadius: compactChart ? 5 : 7,
                        },
                        {
                            label: 'Historical Average',
                            data: monthlyPattern.historical_average || [],
                            backgroundColor: 'rgba(249,115,22,0.68)',
                            borderRadius: compactChart ? 5 : 7,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: chartLegend },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: 0 },
                            title: { display: !compactChart, text: 'Calendar month' }
                        }
                    }
                }
            });

            const weatherPatternCtx = document.getElementById('weatherPatternChart').getContext('2d');
            weatherPatternChart = new Chart(weatherPatternCtx, {
                type: 'line',
                data: {
                    labels: weatherPattern.labels || [],
                    datasets: [
                        {
                            label: 'Historical actual incidents',
                            data: weatherPattern.actual_incidents || [],
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220,38,38,0.12)',
                            pointRadius: chartPointRadius,
                            tension: 0.3,
                            yAxisID: 'y',
                        },
                        {
                            label: 'temperature_2m (C)',
                            data: weatherPattern.temperature_2m || [],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.12)',
                            pointRadius: chartPointRadius,
                            tension: 0.25,
                            yAxisID: 'y1',
                            spanGaps: true,
                        },
                        {
                            label: 'relative_humidity_2m (%)',
                            data: weatherPattern.relative_humidity_2m || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,0.12)',
                            pointRadius: chartPointRadius,
                            tension: 0.25,
                            yAxisID: 'y1',
                            spanGaps: true,
                        },
                        {
                            label: 'wind_speed_10m',
                            data: weatherPattern.wind_speed_10m || [],
                            borderColor: '#14b8a6',
                            backgroundColor: 'rgba(20,184,166,0.12)',
                            pointRadius: chartPointRadius,
                            tension: 0.25,
                            yAxisID: 'y1',
                            spanGaps: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: chartLegend },
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
                            title: { display: !compactChart, text: 'Weather signal' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                            title: { display: !compactChart, text: 'Historical month' }
                        }
                    }
                }
            });

            const dayOfWeekCtx = document.getElementById('dayOfWeekPatternChart').getContext('2d');
            dayOfWeekPatternChart = new Chart(dayOfWeekCtx, {
                type: 'bar',
                data: {
                    labels: dayOfWeekPattern.labels || [],
                    datasets: [
                        {
                            label: 'Current Actual',
                            data: dayOfWeekPattern.current_actual || [],
                            backgroundColor: 'rgba(220,38,38,0.78)',
                            borderRadius: compactChart ? 4 : 6,
                        },
                        {
                            label: '3-Year Historical Average',
                            data: dayOfWeekPattern.historical_average || [],
                            backgroundColor: 'rgba(37,99,235,0.72)',
                            borderRadius: compactChart ? 4 : 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: chartLegend },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        x: {
                            ticks: chartTicks,
                            title: { display: !compactChart, text: 'Weekday' }
                        }
                    }
                }
            });

            const mlTrendSeries = Array.isArray(mlTrendAnalysis.series) ? mlTrendAnalysis.series : [];
            const mlTrendCtx = document.getElementById('mlTrendAnalysisChart').getContext('2d');
            mlTrendAnalysisChart = new Chart(mlTrendCtx, {
                type: 'line',
                data: {
                    labels: mlTrendSeries.map((item) => item.label),
                    datasets: [
                        {
                            label: 'Actual Incidents',
                            data: mlTrendSeries.map((item) => item.incident_count),
                            borderColor: '#c63d26',
                            backgroundColor: 'rgba(198, 61, 38, 0.08)',
                            borderWidth: compactChart ? 2 : 3,
                            pointRadius: chartPointRadius,
                            tension: 0.35,
                            fill: false
                        },
                        {
                            label: 'Expected / Forecast Incidents',
                            data: mlTrendSeries.map((item) => item.predicted_fire_incidents),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            borderWidth: compactChart ? 2 : 3,
                            pointRadius: chartPointRadius,
                            tension: 0.35,
                            borderDash: [8, 6],
                            fill: false
                        },
                        {
                            label: 'Confidence %',
                            data: mlTrendSeries.map((item) => item.confidence),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            borderWidth: compactChart ? 1.5 : 2,
                            pointRadius: compactChart ? 2 : 3,
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
                        legend: chartLegend,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { ...chartTicks, precision: 0 },
                            title: { display: !compactChart, text: 'Incident count' }
                        },
                        confidenceAxis: {
                            beginAtZero: true,
                            position: 'right',
                            min: 0,
                            max: 100,
                            grid: { display: false },
                            ticks: {
                                ...chartTicks,
                                callback: (value) => `${value}%`
                            },
                            title: { display: !compactChart, text: 'Confidence %' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 }
                        }
                    }
                }
            });

            const mlTrendRunCount = document.getElementById('mlTrendRunCount');
            const mlTrendLatestRisk = document.getElementById('mlTrendLatestRisk');
            const mlTrendLatestConfidence = document.getElementById('mlTrendLatestConfidence');
            const mlTrendForecastRuns = document.getElementById('mlTrendForecastRuns');
            if (mlTrendRunCount) mlTrendRunCount.textContent = `${Number(mlTrendAnalysis.run_count || 0)} saved model run(s)`;
            if (mlTrendLatestRisk) mlTrendLatestRisk.textContent = mlTrendAnalysis.latest_risk || 'No data';
            if (mlTrendLatestConfidence) mlTrendLatestConfidence.textContent = `${Number(mlTrendAnalysis.latest_confidence || 0)}%`;
            if (mlTrendForecastRuns) mlTrendForecastRuns.textContent = String(Number(mlTrendAnalysis.run_count || 0));

            const mlMonthComparison = mlTrendAnalysis.month_over_month || {};
            const mlMonthComparisonSeries = Array.isArray(mlMonthComparison.series) ? mlMonthComparison.series : [];
            const mlMonthComparisonCtx = document.getElementById('mlMonthComparisonChart').getContext('2d');
            mlMonthComparisonChart = new Chart(mlMonthComparisonCtx, {
                type: 'bar',
                data: {
                    labels: mlMonthComparisonSeries.map((item) => item.label),
                    datasets: [
                        {
                            label: 'Completed Incidents',
                            data: mlMonthComparisonSeries.map((item) => item.actual_incidents),
                            backgroundColor: '#c63d26',
                            borderRadius: 7,
                        },
                        {
                            label: 'Saved Forecast',
                            data: mlMonthComparisonSeries.map((item) => item.predicted_fire_incidents),
                            backgroundColor: '#2563eb',
                            borderRadius: 7,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: chartLegend },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { ...chartTicks, precision: 0 },
                            title: { display: !compactChart, text: 'Incident count' },
                        },
                        x: { grid: { display: false }, ticks: chartTicks },
                    },
                },
            });

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
                            label: '3-Year historical baseline',
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
                            label: 'Current actual',
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

            const forecastCompareCtx = document.getElementById('forecastCompareChart').getContext('2d');
            forecastCompareChart = new Chart(forecastCompareCtx, {
                type: 'bar',
                data: {
                    labels: historicalVsPredicted.labels || [],
                    datasets: [
                        {
                            label: 'Historical',
                            data: historicalVsPredicted.historical_values || [],
                            backgroundColor: 'rgba(148,163,184,0.82)',
                            borderRadius: compactChart ? 5 : 7,
                            yAxisID: 'y',
                        },
                        {
                            label: '3-Year Historical Baseline',
                            data: historicalVsPredicted.historical_baseline || [],
                            backgroundColor: 'rgba(245,158,11,0.72)',
                            borderRadius: compactChart ? 5 : 7,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Current Actual',
                            data: historicalVsPredicted.current_actual || [],
                            backgroundColor: 'rgba(220,38,38,0.78)',
                            borderRadius: compactChart ? 5 : 7,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Forecast / Predicted',
                            data: historicalVsPredicted.forecast_incidents || [],
                            backgroundColor: 'rgba(37,99,235,0.82)',
                            borderRadius: compactChart ? 5 : 7,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: 'Forecast probability (%)',
                            data: historicalVsPredicted.forecast_probability || [],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.12)',
                            pointRadius: chartPointRadius,
                            tension: 0,
                            spanGaps: true,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: chartLegend },
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
                            title: { display: !compactChart, text: 'Forecast probability (%)' }
                        },
                        x: {
                            ticks: { ...chartTicks, maxRotation: compactChart ? 0 : 45 },
                            title: { display: !compactChart, text: 'Historical month vs forecast window' }
                        }
                    }
                }
            });

            const chartInsights = Array.isArray(chartContext.insights) ? chartContext.insights : [];
            const historicalTotals = Array.isArray(historicalTrend.current_actual) ? historicalTrend.current_actual : [];
            const topHistoricalIndex = historicalTotals.length ? historicalTotals.indexOf(Math.max(...historicalTotals)) : -1;
            const monthlyTotals = Array.isArray(monthlyPattern.historical_average) ? monthlyPattern.historical_average.filter((value) => value !== null) : [];
            const topMonthlyValue = monthlyTotals.length ? Math.max(...monthlyTotals) : null;
            const topMonthlyIndex = topMonthlyValue !== null && Array.isArray(monthlyPattern.historical_average)
                ? monthlyPattern.historical_average.indexOf(topMonthlyValue)
                : -1;

            setInsightList(
                'historicalTrendInsights',
                [
                    historicalTrend?.missing?.historical_average ? 'Calendar-aligned historical comparisons are not available for the current scope yet.' : null,
                    topHistoricalIndex >= 0 && historicalTrend.labels?.[topHistoricalIndex]
                        ? `The strongest current actual day in the aligned comparison window appears around ${historicalTrend.labels[topHistoricalIndex]}.`
                        : null,
                ].filter(Boolean),
                'Calendar-aligned historical comparison is unavailable for the current dataset.'
            );
            setInsightList(
                'seasonalPatternInsights',
                [
                    seasonalPattern?.missing?.historical_average ? 'Seasonal comparison data is unavailable for the current scope.' : null,
                    !seasonalPattern?.missing?.historical_average
                        ? `${seasonalPattern.current_season === 'dry' ? 'Dry' : 'Wet'} season is being compared against its historical average from the available multi-year dataset.`
                        : null,
                ].filter(Boolean),
                'Seasonal fire pattern is unavailable for the current dataset.'
            );
            setInsightList(
                'monthlyPatternInsights',
                [
                    monthlyPattern?.missing?.historical_average ? 'There is not enough historical monthly data to identify recurring same-month baselines.' : null,
                    topMonthlyIndex >= 0 && monthlyPattern.labels?.[topMonthlyIndex]
                        ? `${monthlyPattern.labels[topMonthlyIndex]} shows the highest historical baseline average in the stored monthly dataset.`
                        : null,
                ].filter(Boolean),
                'Monthly fire pattern is unavailable for the current dataset.'
            );
            setInsightList(
                'dayOfWeekPatternInsights',
                [
                    dayOfWeekPattern?.missing?.historical_average ? 'Historical weekday baseline data is unavailable for the current scope.' : null,
                    !dayOfWeekPattern?.missing?.historical_average ? 'Weekday activity is compared against the 3-year average for equivalent calendar-aligned periods.' : null,
                ].filter(Boolean),
                'Weekday pattern is unavailable for the current dataset.'
            );
            setInsightList(
                'mlTrendInsights',
                [
                    mlTrendAnalysis?.missing?.series ? 'No saved analysis history is available yet for the ML trend comparison chart.' : null,
                    mlTrendSeries.length > 1 ? `This panel compares ${mlTrendSeries.length} saved analysis run(s) so you can track how incident counts, predicted incidents, and confidence have been moving over time.` : null,
                ].filter(Boolean),
                'ML trend analysis is unavailable for the current dataset.'
            );
            setInsightList(
                'mlMonthComparisonInsights',
                [
                    mlMonthComparison?.missing?.forecast ? 'No saved forecast was found for either comparison month.' : null,
                    !mlMonthComparison?.missing?.forecast ? 'Completed incidents are compared with the latest saved forecast targeting each month.' : null,
                    mlMonthComparison?.missing?.actual_incidents ? 'No completed incidents were recorded for these months in the current scope.' : null,
                ].filter(Boolean),
                'Month-over-month comparison is unavailable for the current dataset.'
            );
            setInsightList(
                'weatherPatternInsights',
                [
                    weatherPattern?.missing?.actual_incidents ? 'Historical incident-weather records are unavailable for the current scope.' : null,
                    weatherPattern?.missing?.temperature_2m ? 'temperature_2m history is missing from the available incident-weather records.' : null,
                    weatherPattern?.missing?.relative_humidity_2m ? 'relative_humidity_2m history is missing from the available incident-weather records.' : null,
                    weatherPattern?.missing?.wind_speed_10m ? 'wind_speed_10m history is missing from the available incident-weather records.' : null,
                    !weatherPattern?.missing?.precipitation ? 'Open-Meteo precipitation readings are now available for newer live incident contexts.' : null,
                    !weatherPattern?.missing?.wind_gusts_10m ? 'Open-Meteo wind_gusts_10m readings are available for newer live incident contexts.' : null,
                ].filter(Boolean),
                'Weather and fire pattern data is unavailable for the current dataset.'
            );
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
                    hourly?.missing?.historical_baseline ? '3-year historical time-of-day baseline data is unavailable for the hourly comparison chart.' : null,
                    hourly?.missing?.predicted_probability ? 'Predicted peak-hour probability data is unavailable for the current selection.' : null,
                ].filter(Boolean),
                'Hourly pattern insight is unavailable for the current dataset.'
            );
            setInsightList(
                'forecastCompareInsights',
                [
                    historicalVsPredicted?.missing?.actual_incidents ? 'Calendar-aligned historical totals are unavailable for the comparison panel.' : null,
                    historicalVsPredicted?.missing?.historical_baseline ? 'The 3-year historical baseline could not be calculated for this scope.' : null,
                    historicalVsPredicted?.missing?.current_actual ? 'Current actual totals are not available for this target period.' : null,
                    historicalVsPredicted?.missing?.forecast_incidents ? 'Forecast incident count is unavailable for this selection, so only the historical series can be reviewed.' : null,
                    historicalVsPredicted?.forecast_label
                        ? `The forecast column is reserved for ${historicalVsPredicted.forecast_label} so Historical, 3-Year Baseline, Current Actual, and Forecast stay visually separate.`
                        : null,
                ].filter(Boolean),
                'Historical versus forecast comparison is unavailable for the current dataset.'
            );

            renderModelLearningPerformance(analysisData);
        }

        function renderPredictionFactors(analysisData) {
            const panel = document.getElementById('predictionFactorsPanel');
            if (!panel) return;

            const chartContext = analysisData.chart_context || {};
            const factorContext = chartContext.prediction_factors || {};
            const prediction = analysisData.predictions || {};
            const drivers = Array.isArray(factorContext.drivers) && factorContext.drivers.length
                ? factorContext.drivers
                : (Array.isArray(prediction.drivers)
                    ? prediction.drivers.map((driver) => typeof driver === 'string' ? driver : (driver?.summary || null)).filter(Boolean)
                    : []);
            const watchItems = Array.isArray(factorContext.watch_items) ? factorContext.watch_items : [];
            const operationalFactors = Array.isArray(analysisData.risk_factors) ? analysisData.risk_factors.filter(Boolean) : [];
            const targetLabel = factorContext.target_label || 'Current forecast window';
            const selectedBarangay = factorContext.selected_barangay || 'Station-wide scope';
            const confidenceBand = factorContext.confidence_band || analysisData.confidence_score || 'Unavailable';

            const renderChipList = (items, icon, emptyText) => {
                if (!items.length) {
                    return `<span class="prediction-factor-chip prediction-factor-chip--muted"><i class="fa-solid fa-circle-info"></i>${escapeHtml(emptyText)}</span>`;
                }

                return items.map((item) => `
                    <span class="prediction-factor-chip">
                        <i class="fa-solid ${icon}"></i>${escapeHtml(String(item))}
                    </span>
                `).join('');
            };

            panel.innerHTML = `
                <div class="prediction-factors-group">
                    <div class="prediction-factors-group__title">Forecast context</div>
                    <div class="prediction-factors-chips">
                        <span class="prediction-factor-chip"><i class="fa-solid fa-calendar"></i>${escapeHtml(String(targetLabel))}</span>
                        <span class="prediction-factor-chip"><i class="fa-solid fa-location-dot"></i>${escapeHtml(String(selectedBarangay))}</span>
                        <span class="prediction-factor-chip"><i class="fa-solid fa-shield-halved"></i>${escapeHtml(String(confidenceBand))}</span>
                    </div>
                </div>
                <div class="prediction-factors-group">
                    <div class="prediction-factors-group__title">Main model drivers</div>
                    <div class="prediction-factors-chips">
                        ${renderChipList(drivers, 'fa-circle-nodes', 'No model drivers were returned for this forecast yet.')}
                    </div>
                </div>
                <div class="prediction-factors-group">
                    <div class="prediction-factors-group__title">Forecast watch items</div>
                    <div class="prediction-factors-chips">
                        ${renderChipList(watchItems, 'fa-eye', 'No watch items were returned for this forecast yet.')}
                    </div>
                </div>
                <div class="prediction-factors-group">
                    <div class="prediction-factors-group__title">Current operational contributors</div>
                    <div class="prediction-factors-chips">
                        ${renderChipList(operationalFactors, 'fa-triangle-exclamation', 'No current operational contributors were identified for this scope.')}
                    </div>
                </div>
            `;
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

        function setAnalyticsLoadingStep(stepNumber, state) {
            const element = document.getElementById(`analyticsLoadingStep${stepNumber}`);
            if (!element) return;

            element.classList.remove('is-active', 'is-complete');
            if (state === 'active') {
                element.classList.add('is-active');
            } else if (state === 'complete') {
                element.classList.add('is-complete');
            }
        }

        function updateAnalyticsLoadingUI(percent, mode = 'analysis') {
            const normalizedPercent = Math.max(0, Math.min(100, Math.round(percent)));
            const title = document.getElementById('analyticsLoadingTitle');
            const copy = document.getElementById('analyticsLoadingCopy');
            const phase = document.getElementById('analyticsLoadingPhase');
            const percentLabel = document.getElementById('analyticsLoadingPercent');
            const bar = document.getElementById('analyticsLoadingBar');

            if (percentLabel) {
                percentLabel.textContent = `${normalizedPercent}%`;
            }

            if (bar) {
                bar.style.width = `${normalizedPercent}%`;
            }

            const modeLabel = mode === 'next_month' ? 'next-month forecast' : 'station analysis';

            if (normalizedPercent < 18) {
                title.textContent = mode === 'next_month'
                    ? 'Preparing next-month forecast request...'
                    : 'Preparing analysis request...';
                copy.textContent = 'PyroSense is collecting the selected station, barangay, and forecast context.';
                phase.textContent = 'Initializing';
                setAnalyticsLoadingStep(1, 'active');
                setAnalyticsLoadingStep(2, null);
                setAnalyticsLoadingStep(3, null);
                setAnalyticsLoadingStep(4, null);
            } else if (normalizedPercent < 52) {
                title.textContent = mode === 'next_month'
                    ? 'Loading live data for the forward-looking forecast...'
                    : 'Loading live operational data...';
                copy.textContent = 'Reading incidents, weather, personnel, equipment, and validated risk signals from the selected scope.';
                phase.textContent = 'Gathering data';
                setAnalyticsLoadingStep(1, 'complete');
                setAnalyticsLoadingStep(2, 'active');
                setAnalyticsLoadingStep(3, null);
                setAnalyticsLoadingStep(4, null);
            } else if (normalizedPercent < 85) {
                title.textContent = mode === 'next_month'
                    ? 'Building the next-month prediction and recommendations...'
                    : 'Building prediction and recommendation results...';
                copy.textContent = `Generating the ${modeLabel}, recommendations, and action-plan checklist suggestions from the available evidence.`;
                phase.textContent = 'Analyzing';
                setAnalyticsLoadingStep(1, 'complete');
                setAnalyticsLoadingStep(2, 'complete');
                setAnalyticsLoadingStep(3, 'active');
                setAnalyticsLoadingStep(4, null);
            } else if (normalizedPercent < 100) {
                title.textContent = 'Finalizing and saving the latest forecast snapshot...';
                copy.textContent = 'The response is taking a little longer, so PyroSense is waiting for the server to finish and save the latest artifacts.';
                phase.textContent = 'Finalizing';
                setAnalyticsLoadingStep(1, 'complete');
                setAnalyticsLoadingStep(2, 'complete');
                setAnalyticsLoadingStep(3, 'complete');
                setAnalyticsLoadingStep(4, 'active');
            } else {
                title.textContent = 'Forecast ready.';
                copy.textContent = 'The latest prediction, recommendations, and saved workflow context are now available.';
                phase.textContent = 'Complete';
                setAnalyticsLoadingStep(1, 'complete');
                setAnalyticsLoadingStep(2, 'complete');
                setAnalyticsLoadingStep(3, 'complete');
                setAnalyticsLoadingStep(4, 'complete');
            }
        }

        function startAnalyticsLoading(mode = 'analysis') {
            const overlay = document.getElementById('loadingOverlay');
            if (!overlay) return;

            if (analyticsLoadingState.timer) {
                clearInterval(analyticsLoadingState.timer);
            }

            analyticsLoadingState = {
                active: true,
                percent: 4,
                timer: null,
                startedAt: Date.now(),
            };

            overlay.classList.add('active');
            updateAnalyticsLoadingUI(analyticsLoadingState.percent, mode);

            analyticsLoadingState.timer = window.setInterval(() => {
                if (!analyticsLoadingState.active) {
                    return;
                }

                const elapsed = Date.now() - analyticsLoadingState.startedAt;
                let nextPercent = analyticsLoadingState.percent;

                if (elapsed < 4000) {
                    nextPercent += 7;
                } else if (elapsed < 9000) {
                    nextPercent += 4;
                } else if (elapsed < 16000) {
                    nextPercent += 2;
                } else if (elapsed < 26000) {
                    nextPercent += 1;
                } else {
                    nextPercent += 0.4;
                }

                analyticsLoadingState.percent = Math.min(95, nextPercent);
                updateAnalyticsLoadingUI(analyticsLoadingState.percent, mode);
            }, 450);
        }

        function setAnalyticsActionButtonsBusy(isBusy, activeMode = 'current_month') {
            const analyzeBtn = document.getElementById('analyzeBtn');
            const predictBtn = document.getElementById('predictNextMonthBtn');
            const previewBtn = document.getElementById('previewHistoricalWeatherBtn');
            const buttons = [analyzeBtn, predictBtn, previewBtn].filter(Boolean);

            buttons.forEach((button) => {
                button.disabled = isBusy;
                button.style.opacity = isBusy ? '0.7' : '';
                button.style.pointerEvents = isBusy ? 'none' : '';
            });

            if (analyzeBtn) {
                analyzeBtn.style.boxShadow = isBusy && activeMode === 'current_month'
                    ? '0 0 0 3px rgba(220, 38, 38, 0.12)'
                    : '';
            }

            if (predictBtn) {
                predictBtn.style.boxShadow = isBusy && activeMode === 'next_month'
                    ? '0 0 0 3px rgba(234, 88, 12, 0.12)'
                    : '';
            }
        }

        async function finishAnalyticsLoading(mode = 'analysis') {
            const overlay = document.getElementById('loadingOverlay');
            if (!overlay) return;

            analyticsLoadingState.active = false;

            if (analyticsLoadingState.timer) {
                clearInterval(analyticsLoadingState.timer);
                analyticsLoadingState.timer = null;
            }

            analyticsLoadingState.percent = 100;
            updateAnalyticsLoadingUI(100, mode);

            await new Promise((resolve) => window.setTimeout(resolve, 220));
            overlay.classList.remove('active');
        }

        async function runAIAnalysis(locationId, predictionScope = 'current_month') {
            const periodDays = document.getElementById('periodSelect').value;
            const scopeMeta = getSelectedScopeMeta();
            const precheckLabel = predictionScope === 'next_month'
                ? 'Checking saved next-month forecast…'
                : 'Checking saved analysis…';

            setAnalyticsActionButtonsBusy(true, predictionScope);
            setScopedForecastLoading(true, precheckLabel);

            let shouldReplaceExisting = false;

            try {
                const existingSnapshot = await fetchScopedAnalysisSnapshot(predictionScope);
                if (existingSnapshot?.success && existingSnapshot?.data?.analysis_id) {
                    shouldReplaceExisting = true;
                    const scopeLabel = scopeMeta.barangayId !== 'all'
                        ? `${scopeMeta.barangayName} under ${scopeMeta.stationName}`
                        : `${scopeMeta.stationName} station-wide`;
                    const confirmed = await showAnalyticsConfirm({
                        eyebrow: 'Replace saved forecast',
                        title: 'Overwrite existing saved analysis?',
                        message: `A saved ${predictionScope === 'next_month' ? 'next-month prediction' : 'current-month analysis'} already exists for ${scopeLabel}. Only this exact station, month, run type, and barangay scope will be revised. Untouched AI drafts will refresh; confirmed or edited checklists and mitigations will be preserved for revision review.`,
                        cancelText: 'Keep existing',
                        acceptText: 'Replace forecast',
                    });

                    if (!confirmed) {
                        return;
                    }
                }
            } catch (snapshotError) {
                console.warn('Scoped snapshot pre-check skipped:', snapshotError);
            } finally {
                setScopedForecastLoading(false);
            }

            setActiveForecastViewScope(predictionScope);
            startAnalyticsLoading(predictionScope);

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
                        prediction_scope: predictionScope,
                        analysis_kind: predictionScope === 'next_month' ? 'forecast' : 'actual_analysis',
                        replace_existing: shouldReplaceExisting,
                        historical_window: document.getElementById('historicalWindowSelect')?.value || '3'
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
                    loadForecastNarrativeRuns(result.data).catch((error) => console.error('Forecast narrative refresh error:', error));
                    loadRecommendationRunGroups(result.data).catch((error) => console.error('Recommendation run refresh error:', error));
                } else {
                    showPredictionApiError(result.message || 'Analysis failed', result.details || null);
                }
            } catch (error) {
                console.error('Analysis error:', error);
                showPredictionApiError('Failed to run AI analysis.', error.message);
            } finally {
                await finishAnalyticsLoading(predictionScope);
                setAnalyticsActionButtonsBusy(false, predictionScope);
            }
        }

        async function exploreRecommendation(recommendation, index) {
            const modal = document.getElementById('exploreModal');
            const modalBody = document.getElementById('exploreModalBody');
            const recommendationTitle = getRecommendationTitle(recommendation);
            const cacheKey = buildRecommendationCacheKey(recommendation);
            const cached = recommendationExploreCache.get(cacheKey);
            
            modal.classList.add('show');
            if (cached) {
                displayExploreResults(
                    cached.data,
                    recommendation,
                    cached.source || null,
                    cached.note || null,
                    cached.model_used || null
                );
                return;
            }

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
                    recommendationExploreCache.set(cacheKey, {
                        data: result.data,
                        source: result.source || null,
                        note: result.note || null,
                        model_used: result.model_used || null,
                    });
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

        function closeAnalysisEvidenceModal() {
            document.getElementById('analysisEvidenceModal')?.classList.remove('show');
        }

        function getAnalysisEvidencePayload(detail = currentPredictionDetails) {
            return detail?.evidence_provenance
                || detail?.analysis_data?.raw_data?.evidence_provenance
                || detail?.analysis_data?.analysis_summary?.evidence_provenance
                || null;
        }

        function syncAnalysisEvidenceButton(detail = currentPredictionDetails) {
            const button = document.getElementById('analysisEvidenceBtn');
            if (!button) return;
            button.disabled = !detail;
            button.title = getAnalysisEvidencePayload(detail)
                ? 'Inspect the records, weather, risks, hazards, readiness, and model drivers saved with this result.'
                : 'This older run has limited evidence. Open it to see what is available.';
        }

        function evidenceValue(value, suffix = '') {
            if (value === null || value === undefined || value === '') return 'Unavailable';
            return `${escapeHtml(String(value))}${suffix}`;
        }

        function renderEvidenceMetric(value, label) {
            return `
                <div class="analysis-evidence__metric">
                    <strong>${evidenceValue(value)}</strong>
                    <span>${escapeHtml(label)}</span>
                </div>
            `;
        }

        function openAnalysisEvidenceModal() {
            const modal = document.getElementById('analysisEvidenceModal');
            const body = document.getElementById('analysisEvidenceModalBody');
            const evidence = getAnalysisEvidencePayload();

            if (!modal || !body) return;

            if (!evidence) {
                body.innerHTML = `
                    <div class="analytics-empty-state">
                        <div class="analytics-empty-state__title">Detailed evidence is unavailable for this older run.</div>
                        <p class="analytics-empty-state__copy">Run the analysis again to save separate fire-history, weather-observation, risk, hazard, and mitigation contributions.</p>
                    </div>
                `;
                modal.classList.add('show');
                return;
            }

            const scope = evidence.scope || {};
            const counts = evidence.counts || {};
            const weather = evidence.weather || {};
            const mitigation = evidence.mitigation || {};
            const result = evidence.result || {};
            const model = evidence.model || {};
            const definitions = evidence.definitions || {};
            const barangays = Array.isArray(evidence.barangays) ? evidence.barangays : [];
            const sources = Object.entries(model.prediction_sources || {})
                .map(([source, count]) => `${String(source).replaceAll('_', ' ')}: ${count}`)
                .join(' · ') || 'No model source recorded';
            const generatedAt = evidence.generated_at
                ? formatRecommendationRunTimestamp(evidence.generated_at)
                : 'Saved analysis run';
            const drivers = Array.isArray(model.drivers) ? model.drivers : [];
            const riskFactors = Array.isArray(evidence.risk_factors) ? evidence.risk_factors : [];

            body.innerHTML = `
                <div class="analysis-evidence">
                    <section class="analysis-evidence__intro">
                        <div>
                            <div class="analysis-evidence__eyebrow">Saved evidence snapshot</div>
                            <div class="analysis-evidence__title">${escapeHtml(scope.location || 'Selected station')}</div>
                            <p class="analysis-evidence__copy">${escapeHtml(scope.target_period || 'Selected forecast period')} · ${escapeHtml(String(scope.days_analyzed || 0))} operational days · generated ${escapeHtml(generatedAt)}</p>
                        </div>
                        <span class="analytics-empty-pill">${escapeHtml(scope.prediction_scope === 'next_month' ? 'Next month' : 'Current month')}</span>
                    </section>

                    <section class="analysis-evidence__section">
                        <div class="analysis-evidence__section-title">Records that contributed</div>
                        <div class="analysis-evidence__grid">
                            ${renderEvidenceMetric(counts.actual_fire_incidents_in_analysis_window ?? 0, 'Actual fires in selected analysis window')}
                            ${renderEvidenceMetric(counts.historical_fire_baseline ?? 0, 'Historical fire baseline used by model')}
                            ${renderEvidenceMetric(counts.weather_observation_rows ?? 0, 'Weather-only observation rows')}
                            ${renderEvidenceMetric(counts.daily_weather_rows ?? 0, 'Dedicated daily weather rows')}
                            ${renderEvidenceMetric(counts.observed_weather_days ?? 0, 'Completed weather days')}
                            ${renderEvidenceMetric(counts.barangays_evaluated ?? barangays.length, 'Barangays evaluated')}
                            ${renderEvidenceMetric(counts.verified_risks ?? 0, 'Verified risks')}
                            ${renderEvidenceMetric(counts.verified_hazards ?? 0, 'Verified hazards')}
                            ${renderEvidenceMetric(counts.high_risk_assessments ?? 0, 'High assessments')}
                            ${renderEvidenceMetric(counts.observed_current_month_fires ?? 0, 'Observed fires this month')}
                        </div>
                    </section>

                    <section class="analysis-evidence__section">
                        <div class="analysis-evidence__section-title">Weather, readiness, and result</div>
                        <div class="analysis-evidence__facts">
                            <div class="analysis-evidence__fact"><b>Weather:</b> ${evidenceValue(weather.temperature_c, '°C')}, ${evidenceValue(weather.humidity_percent, '%')} humidity, ${evidenceValue(weather.wind_kph, ' km/h')} wind${weather.condition ? ` · ${escapeHtml(weather.condition)}` : ''}</div>
                            <div class="analysis-evidence__fact"><b>Mitigation:</b> ${evidenceValue(mitigation.checklist_steps_followed ?? 0)} of ${evidenceValue(mitigation.checklist_steps_total ?? 0)} checklist steps followed; ${evidenceValue(mitigation.completed_actions ?? 0)} of ${evidenceValue(mitigation.total_actions ?? 0)} actions complete.</div>
                            <div class="analysis-evidence__fact"><b>Risk adjustment:</b> ${evidenceValue(result.base_risk_score)} base score minus ${evidenceValue(mitigation.risk_reduction_points ?? 0)} mitigation points = ${evidenceValue(result.adjusted_risk_score)} (${escapeHtml(result.risk_level || 'unclassified')}).</div>
                        </div>
                    </section>

                    <section class="analysis-evidence__section">
                        <div class="analysis-evidence__section-title">Barangay evidence breakdown</div>
                        ${barangays.length ? `
                            <div class="analysis-evidence__table-wrap">
                                <table class="analysis-evidence__table">
                                    <thead><tr><th>Barangay</th><th>Historical fires</th><th>Weather rows</th><th>Risks / hazards</th><th>Water readiness</th><th>Forecast</th><th>Source</th></tr></thead>
                                    <tbody>
                                        ${barangays.map((row) => `
                                            <tr>
                                                <td><b>${escapeHtml(row.barangay_name || 'Unknown')}</b><br><span style="color:#64748b;">${evidenceValue(row.current_month_fires ?? 0)} current-month fire(s)</span></td>
                                                <td>${evidenceValue(row.historical_fire_baseline ?? 0)}<br><span style="color:#64748b;">${evidenceValue(row.positive_historical_records ?? 0)} positive record(s)</span></td>
                                                <td>${evidenceValue(row.weather_observation_rows ?? 0)}</td>
                                                <td>${evidenceValue(row.verified_risks ?? 0)} / ${evidenceValue(row.verified_hazards ?? 0)}<br><span style="color:#64748b;">pressure ${evidenceValue(row.risk_pressure ?? 0)}</span></td>
                                                <td>${row.near_hydrant ? 'Hydrant' : 'No hydrant flag'}<br><span style="color:#64748b;">${row.near_water_source ? 'Water source nearby' : 'No water-source flag'}</span></td>
                                                <td><b>${evidenceValue(row.probability_percent, '%')}</b><br><span style="color:#64748b;">${escapeHtml(row.risk_level || 'unclassified')}</span></td>
                                                <td>${escapeHtml(String(row.prediction_source || 'unknown').replaceAll('_', ' '))}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : '<div class="analysis-evidence__note">No barangay-level forecast inputs were stored for this run.</div>'}
                    </section>

                    <section class="analysis-evidence__section">
                        <div class="analysis-evidence__section-title">Model source and leading drivers</div>
                        <div class="analysis-evidence__note"><b>Prediction source:</b> ${escapeHtml(sources)}. The displayed probability is ${evidenceValue(result.forecast_probability_percent, '%')} with a planning midpoint of ${evidenceValue(result.forecast_incident_midpoint)} incident(s).</div>
                        ${(drivers.length || riskFactors.length) ? `
                            <div class="analysis-evidence__facts">
                                ${[...drivers, ...riskFactors].slice(0, 6).map((driver) => `<div class="analysis-evidence__fact">${escapeHtml(String(driver))}</div>`).join('')}
                            </div>
                        ` : ''}
                    </section>

                    <section class="analysis-evidence__section">
                        <div class="analysis-evidence__section-title">Counting rules</div>
                        <div class="analysis-evidence__note">
                            <b>Historical fires:</b> ${escapeHtml(definitions.historical_fire_baseline || 'Only positive incident counts contribute to fire totals.')}<br>
                            <b>Weather rows:</b> ${escapeHtml(definitions.weather_observation_rows || 'Weather-only rows are not counted as fire incidents.')}<br>
                            <b>Daily weather:</b> ${escapeHtml(definitions.daily_weather_rows || 'Completed-day observations are stored separately from incidents.')}<br>
                            <b>Risk reduction:</b> ${escapeHtml(definitions.mitigation_effect || 'Verified readiness may reduce severity or likelihood pressure.')}
                        </div>
                    </section>
                </div>
            `;

            modal.classList.add('show');
            replaceChiefAnalyticsFaIcons(modal);
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
            const peakHoursLabel = prediction?.likely_peak_hours_label || (likelyPeakHours.length ? likelyPeakHours.join(', ') : 'No clear peak yet');
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
                            <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase;">Observed to Date</div>
                            <div style="font-size:28px; font-weight:900; color:#7c2d12; margin-top:6px;">${prediction?.forecast_count_basis === 'next_month_forecast' ? 'Not started' : (prediction?.observed_fire_incidents_to_date ?? 'N/A')}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#fffbeb; border:1px solid #fde68a;">
                            <div style="font-size:11px; font-weight:800; color:#b45309; text-transform:uppercase;">Predicted Additional</div>
                            <div style="font-size:28px; font-weight:900; color:#92400e; margin-top:6px;">${prediction?.predicted_additional_fire_incidents ?? 'N/A'}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#fff7ed; border:1px solid #fed7aa;">
                            <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase;">Expected Month Total</div>
                            <div style="font-size:28px; font-weight:900; color:#7c2d12; margin-top:6px;">${prediction?.expected_month_total_fire_incidents ?? prediction?.predicted_fire_incidents ?? 'N/A'}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#eff6ff; border:1px solid #bfdbfe;">
                            <div style="font-size:11px; font-weight:800; color:#1d4ed8; text-transform:uppercase;">Estimated Damage</div>
                            <div style="font-size:22px; font-weight:900; color:#1e3a8a; margin-top:6px;">${prediction?.predicted_damage !== undefined && prediction?.predicted_damage !== null ? `PHP ${escapeHtml(Number(prediction.predicted_damage).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}` : 'N/A'}</div>
                        </div>
                        <div style="padding:14px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                            <div style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Peak Hours</div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; margin-top:6px;">${escapeHtml(peakHoursLabel)}</div>
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
                                    ${item.requires_revision_review ? '<div style="display:inline-flex; align-items:center; gap:6px; margin-top:7px; padding:5px 9px; border-radius:999px; background:#fee2e2; color:#991b1b; font-size:10px; font-weight:900; text-transform:uppercase;"><i class="fa-solid fa-triangle-exclamation"></i> Revision review required</div>' : ''}
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
                                ${item.linked_mitigation_url ? `
                                    <a href="${escapeHtml(item.linked_mitigation_url)}" style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:#0f172a; color:#fff; font-size:12px; font-weight:800; text-decoration:none;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>View Linked Mitigation</span>
                                    </a>
                                ` : ''}
                                ${item.mitigation_draft_url ? `
                                    <a href="${escapeHtml(item.mitigation_draft_url)}" style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:linear-gradient(135deg, #dc2626, #f59e0b); color:#fff; font-size:12px; font-weight:800; text-decoration:none;">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>Prepare Mitigation</span>
                                    </a>
                                ` : ''}
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
            currentAnalysisRevision = analysisData.analysis_revision || null;
            const revisionBadge = document.getElementById('analysisRevisionBadge');
            const revisionButton = document.getElementById('revisionHistoryBtn');
            if (revisionBadge) revisionBadge.textContent = currentAnalysisRevision ? `Revision ${currentAnalysisRevision}` : 'Revision --';
            if (revisionButton) revisionButton.disabled = !currentAnalysisId;
            currentRecommendations = analysisData.recommendations || [];
            currentBarangayPredictions = Array.isArray(analysisData.barangay_predictions) ? analysisData.barangay_predictions : [];
            currentPredictionDetails = analysisData;
            setActiveForecastViewScope(analysisData.prediction_scope || 'current_month');
            syncAnalysisEvidenceButton(analysisData);
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
            const historicalWindowSelect = document.getElementById('historicalWindowSelect');
            if (historicalWindowSelect && analysisData.historical_window) {
                historicalWindowSelect.value = String(analysisData.historical_window);
            }
            updateTargetMonthDisplay(analysisData.prediction_scope || 'current_month', analysisData.prediction_context || null);
            updateHistoricalWindowBadge(
                analysisData.chart_context?.historical_window_meta?.label
                    || getSelectedScopeMeta().historicalWindowLabel
            );
            updateHeroModelLabel(formatAiModelLabel(analysisData?.ai_models?.recommendation || analysisData?.ai_models?.prediction) || analysisData?.model_metadata?.dataset_kind || 'Latest saved model');
            loadForecastNarrativeRuns(analysisData).catch((error) => {
                console.error('Forecast narrative list load failed:', error);
                if (predictionContainer) {
                    setPredictionEmptyState(
                        'Forecast narrative could not be loaded.',
                        'Please try running the analysis again or refresh the saved forecast history.'
                    );
                }
            });

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
            renderPredictionFactors(analysisData);

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
                const commandPrefix = window.location.pathname.startsWith('/admin/') ? '/admin' : '/station-admin';
                const response = await fetch(`${commandPrefix}/api/retrain-forecast-model`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json().catch(() => ({}));

                if (!response.ok || !result.success) {
                    const detailParts = [
                        result.message || 'Model retraining failed.',
                        result.details || '',
                        result.code ? `Code: ${result.code}.` : '',
                        result.diagnostics?.python_binary ? `Python: ${result.diagnostics.python_binary}.` : '',
                        result.exception_message || '',
                    ].filter(Boolean);
                    throw new Error(detailParts.join(' '));
                }

                if (!result.status_url) {
                    throw new Error('The server did not return a retraining status URL.');
                }

                let trainingResult = result;
                let pollAttempts = 0;
                while (!['completed', 'failed'].includes(trainingResult.status) && pollAttempts < 180) {
                    await new Promise((resolve) => window.setTimeout(resolve, 2000));
                    const statusResponse = await fetch(trainingResult.status_url || result.status_url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    trainingResult = await statusResponse.json().catch(() => ({}));
                    if (!statusResponse.ok) {
                        throw new Error(trainingResult.message || 'Unable to read retraining progress.');
                    }

                    const progress = Number.isFinite(Number(trainingResult.progress))
                        ? Math.max(0, Math.min(100, Number(trainingResult.progress)))
                        : 0;
                    if (button) {
                        button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${escapeHtml(String(trainingResult.message || 'Retraining...'))} ${progress}%`;
                        button.setAttribute('aria-label', `${trainingResult.message || 'Retraining'} ${progress}%`);
                    }
                    pollAttempts += 1;
                }

                if (trainingResult.status === 'failed' || !trainingResult.success) {
                    const detailParts = [
                        trainingResult.message || 'Model retraining failed.',
                        trainingResult.details || '',
                        trainingResult.code ? `Code: ${trainingResult.code}.` : '',
                    ].filter(Boolean);
                    throw new Error(detailParts.join(' '));
                }

                if (trainingResult.status !== 'completed') {
                    throw new Error('Retraining is still running. You can safely return to this page and try again shortly.');
                }

                const trainedAt = trainingResult.model_metadata?.trained_at
                    ? ` Trained at ${trainingResult.model_metadata.trained_at}.`
                    : '';
                showAnalyticsToast(`${trainingResult.message || 'Forecast model retrained successfully.'}${trainedAt}`, 'success');
            } catch (error) {
                showAnalyticsToast(error.message || 'Unable to retrain the forecast model.', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.style.opacity = '';
                    button.innerHTML = originalHtml;
                    button.removeAttribute('aria-label');
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
        document.querySelectorAll('[data-forecast-view-scope]').forEach((button) => {
            button.addEventListener('click', () => {
                loadLatestAnalysisSnapshot(button.dataset.forecastViewScope || 'current_month', true)
                    .catch((error) => showAnalyticsToast(error.message || 'Unable to load the saved forecast.', 'error'));
            });
        });
        document.getElementById('previewHistoricalWeatherBtn')?.addEventListener('click', previewHistoricalWeatherArchive);
        document.getElementById('saveHistoricalWeatherBtn')?.addEventListener('click', saveHistoricalWeatherArchive);

        document.getElementById('stationSelect')?.addEventListener('change', (e) => {
            populateBarangaySelectFromStation(e.target.value);
            updateRecommendationScopeLabel();
            loadLatestAnalysisSnapshot(activeForecastViewScope).catch((error) => console.error('Scoped snapshot refresh error:', error));
            loadForecastNarrativeRuns().catch((error) => console.error('Forecast narrative refresh error:', error));
            loadRecommendationRunGroups().catch((error) => console.error('Recommendation run refresh error:', error));
        });

        document.getElementById('barangaySelect')?.addEventListener('change', () => {
            updateRecommendationScopeLabel();
            loadLatestAnalysisSnapshot(activeForecastViewScope).catch((error) => console.error('Scoped snapshot refresh error:', error));
            loadForecastNarrativeRuns().catch((error) => console.error('Forecast narrative refresh error:', error));
            loadRecommendationRunGroups().catch((error) => console.error('Recommendation run refresh error:', error));
        });

        document.getElementById('historicalWindowSelect')?.addEventListener('change', () => {
            updateHistoricalWindowBadge();
            loadLatestAnalysisSnapshot(activeForecastViewScope).catch((error) => console.error('Scoped snapshot refresh error:', error));
        });

        document.addEventListener('DOMContentLoaded', () => {
            replaceChiefAnalyticsFaIcons();
            updateHistoricalWindowBadge();
            setHistoricalWeatherSaveButtonState(true);

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



