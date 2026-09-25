@php
    $isProvincialResourceAdmin = strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) === 'provincial admin';
    $resourceRoutePrefix = $isProvincialResourceAdmin ? 'admin' : 'station-admin';
    $resourceBaseUrl = url('/'.$resourceRoutePrefix);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense · Resource Management</title>
    
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])
    @vite(['resources/js/admin/resource-management.js'])
    @vite(['resources/js/admin/resources.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .fa-solid,
        .fas,
        .fa-regular,
        .far,
        .fa-brands,
        .fab,
        .fa-solid::before,
        .fas::before {
            font-family: "Font Awesome 6 Free" !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .fa-solid,
        .fas,
        .fa-solid::before,
        .fas::before {
            font-weight: 900 !important;
        }

        .fa-regular,
        .far,
        .fa-regular::before,
        .far::before {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 400 !important;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
        }

        body.modal-open {
            overflow: hidden;
            touch-action: none;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px 24px 28px;
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
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 0 0 20px;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
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
            padding: 12px 18px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        /* Tables */
        .station-grid {
            display: block !important;
            width: 100%;
            flex: 0 0 100%;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border-radius: 26px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .section-header {
            width: 100%;
            flex-wrap: wrap;
            padding: 22px 24px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .section-header h3 {
            font-size: 1.14rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.14);
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #b45309;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .filter-select-sm {
            padding: 8px 12px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            border-radius: 12px;
            font-size: 0.8rem;
            background: white;
            cursor: pointer;
        }

        .station-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .station-table th {
            text-align: left;
            padding: 14px 16px;
            background: #fffaf0;
            font-weight: 800;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
        }

        .station-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            color: #475569;
            font-size: 0.85rem;
        }

        .plain-sight-hidden {
            display: none !important;
        }

        .station-table tbody tr:hover {
            background: #fff7ed;
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
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            cursor: pointer;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            transition: all 0.2s;
            color: #b45309;
        }

        .action-btn:hover { background: #fff7ed; transform: translateY(-1px); }
        .action-btn.view-resource:hover { color: #c2410c; }
        .action-btn.edit-resource:hover { color: #f59e0b; }
        .action-btn.delete-resource:hover { color: #ef4444; background: #fef2f2; border-color: rgba(239, 68, 68, 0.18); }

        /* Search and Filter Bar */
        .search-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            padding: 16px 24px;
            border-radius: 24px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .boundary-control-card {
            background: linear-gradient(135deg, #fff9f2 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
            border-radius: 24px;
            padding: 22px 24px;
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
            font-size: 1.05rem;
            color: #0f172a;
            font-weight: 800;
        }

        .boundary-control-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.55;
            max-width: 70ch;
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
            background: rgba(255, 255, 255, 0.88);
            color: #9a3412;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1px solid rgba(245, 158, 11, 0.16);
        }

        .coverage-diagnostic { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin:0 0 18px; }
        .coverage-diagnostic__item { display:flex; gap:11px; align-items:flex-start; min-width:0; padding:13px 14px; border:1px solid #e2e8f0; border-radius:16px; background:rgba(255,255,255,.82); }
        .coverage-diagnostic__item > i { display:grid; place-items:center; flex:0 0 32px; height:32px; border-radius:10px; color:#c2410c; background:#ffedd5; }
        .coverage-diagnostic__item strong,.coverage-diagnostic__item small { display:block; }
        .coverage-diagnostic__item strong { color:#0f172a; font-size:.82rem; line-height:1.3; }
        .coverage-diagnostic__item small { margin-top:3px; color:#64748b; font-size:.72rem; line-height:1.4; }
        .coverage-diagnostic__item.is-warning { border-color:#fcd34d; background:#fffbeb; }
        .coverage-diagnostic__item.is-warning > i { color:#92400e; background:#fef3c7; }

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
            border: 0;
            border-radius: 12px;
            padding: 0 14px;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 22px rgba(221, 138, 17, 0.18);
        }

        .boundary-sync-btn[disabled] {
            opacity: 0.68;
            cursor: wait;
        }

        .boundary-action-group { display:flex; align-items:center; flex-wrap:wrap; gap:7px; }
        .boundary-action-group form { margin:0; }
        .boundary-visit-btn { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:0 12px; border:1px solid #fed7aa; border-radius:12px; color:#9a3412; background:#fff7ed; text-decoration:none; font-size:.78rem; font-weight:800; }

        .search-box {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            border-radius: 16px;
            padding: 8px 16px;
            flex: 1;
            max-width: 320px;
        }

        .search-box i { color: #d97706; margin-right: 10px; }
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
            border: 1px solid rgba(245, 158, 11, 0.14);
            border-radius: 14px;
            background: white;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .btn-filter {
            padding: 8px 16px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            border-radius: 14px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            color: #b45309;
        }

        .btn-filter:hover { background: #ffedd5; }

        /* Add Dropdown */
        .add-dropdown {
            position: relative;
            display: inline-block;
        }

        .add-dropdown-btn {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .add-dropdown-btn:hover { box-shadow: 0 14px 26px rgba(221, 138, 17, 0.24); }

        .add-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            min-width: 250px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            z-index: 100;
            overflow: hidden;
            border: 1px solid rgba(245, 158, 11, 0.12);
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

        .add-dropdown-content a:hover { background: #fff7ed; }
        .add-dropdown-content i { width: 20px; color: #c2410c; }
        .add-dropdown.show .add-dropdown-content { display: block; }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
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

        .tab-btn:hover { background: #fff7ed; color: #c2410c; }
        .tab-btn.active { background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%); color: white; box-shadow: 0 12px 24px rgba(221, 138, 17, 0.2); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border-radius: 22px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
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

        .stat-icon.blue,
        .stat-icon.orange,
        .stat-icon.red,
        .stat-icon.purple {
            background: linear-gradient(135deg, #cc4c1d 0%, #dd8a11 100%);
            color: #fff;
            box-shadow: 0 16px 26px rgba(221, 138, 17, 0.2);
        }

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
            font-weight: 800;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date {
            font-size: 0.8rem;
            color: #9a3412;
            background: #fff7ed;
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid rgba(245, 158, 11, 0.12);
        }

        .alert-banner {
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.92) 0%, rgba(254, 252, 232, 0.96) 100%);
            border-left: 4px solid #d97706;
            padding: 14px 20px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            color: #7c2d12;
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
            padding: 20px;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border-radius: 24px;
            width: min(92vw, 560px);
            max-width: 560px;
            max-height: min(84vh, 820px);
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 28px 54px rgba(15, 23, 42, 0.16);
        }

        #registerUserModal .modal-content,
        #editUserModal .modal-content,
        #teamModal .modal-content,
        #editOfficerModal .modal-content,
        #equipmentCategoryModal .modal-content,
        #equipmentItemModal .modal-content,
        #stationEquipmentModal .modal-content {
            width: min(92vw, 620px);
            max-width: 620px !important;
        }

        #editEquipmentModal .modal-content {
            width: min(90vw, 760px);
            max-width: 760px !important;
        }

        #viewUserModal .modal-content,
        #viewOfficerModal .modal-content,
        #viewTeamModal .modal-content {
            width: min(92vw, 640px);
            max-width: 640px !important;
        }

        #editTeamModal .modal-content {
            width: min(94vw, 720px);
            max-width: 720px !important;
        }

        #deleteOfficerModal .modal-content,
        #deleteUserModal .modal-content {
            width: min(90vw, 440px);
            max-width: 440px !important;
        }

        .modal-header {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: white;
            border-radius: 24px 24px 0 0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .modal-header h2 { font-size: 1rem; margin: 0; line-height: 1.3; }
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.05rem;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            cursor: pointer;
        }

        .modal-body { padding: 16px 18px; }
        .modal-footer {
            padding: 12px 18px 16px;
            border-top: 1px solid #eef2f6;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255,248,239,0.9) 0%, #fff 28%);
            backdrop-filter: blur(8px);
            z-index: 2;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #9a3412;
            font-size: 0.85rem;
        }
        .form-label i { margin-right: 6px; color: #d97706; }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            border-radius: 14px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
        }

        .btn-primary {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-primary:hover { box-shadow: 0 14px 26px rgba(221, 138, 17, 0.24); }

        .btn-outline {
            background: white;
            border: 1px solid rgba(245, 158, 11, 0.14);
            padding: 10px 24px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #b45309;
        }

        .btn-outline:hover { background: #fff7ed; }

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
        .resource-profile-card {
            display: grid;
            gap: 0.8rem;
        }
        .resource-profile-hero {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 0.95rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border: 1px solid rgba(226, 232, 240, 0.95);
        }
        .resource-profile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            flex-shrink: 0;
        }
        .resource-profile-heading {
            min-width: 0;
        }
        .resource-profile-name {
            font-size: 0.95rem;
            font-weight: 800;
            color: #172033;
            word-break: break-word;
        }
        .resource-profile-meta {
            margin-top: 0.2rem;
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .resource-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
        }
        .resource-detail-card {
            padding: 0.8rem 0.9rem;
            border-radius: 1rem;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .resource-detail-card .detail-label {
            margin-bottom: 0.35rem;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .resource-detail-card .detail-value {
            font-size: 0.86rem;
            font-weight: 600;
            color: #172033;
            line-height: 1.4;
            word-break: break-word;
        }
        .resource-detail-section {
            padding-top: 0.2rem;
        }
        .resource-detail-section-title {
            font-size: 0.76rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.55rem;
        }
        .resource-edit-card {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .resource-edit-card .form-group {
            margin-bottom: 0;
        }
        .resource-edit-card .form-label {
            margin-bottom: 0.55rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .resource-edit-card .form-select,
        .resource-edit-card .form-input {
            min-height: 46px;
            border-radius: 0.9rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fffdfa;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        #editUserModal .form-grid {
            gap: 12px 18px;
        }

        #editUserModal .form-group {
            margin-bottom: 12px;
        }

        #editUserModal .form-label {
            margin-bottom: 5px;
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
            top: 22px;
            right: 22px;
            background: #1e293b;
            color: white;
            padding: 12px 18px;
            border-radius: 14px;
            display: none;
            z-index: 9999;
            font-size: 0.85rem;
            font-weight: 600;
            max-width: min(360px, calc(100vw - 32px));
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.18);
            transform: translateY(-10px);
            opacity: 0;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        #toast.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        #toast.toast-success {
            background: #059669;
        }

        #toast.toast-error {
            background: #dc2626;
        }

        #toast.toast-info {
            background: #2563eb;
        }

        #toast.toast-warning {
            background: #d97706;
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
            body.chief-legacy-layout {
                background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            }

            .main-content {
                padding: 10px 10px calc(env(safe-area-inset-bottom, 0px) + 84px) !important;
            }

            .lector-top-bar {
                min-height: 48px !important;
                height: 48px !important;
                margin: 8px auto 12px !important;
                padding: 0 10px !important;
                width: calc(100% - 4px) !important;
                max-width: 330px !important;
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

            .management-container {
                max-width: 330px;
                gap: 12px;
            }

            .chief-theme-shell {
                gap: 12px;
            }

            .page-header {
                margin-bottom: 12px;
                gap: 8px;
                align-items: flex-start;
                flex-direction: column;
            }

            .page-header h1 {
                font-size: 0.95rem;
                gap: 8px;
            }

            .date {
                font-size: 0.62rem;
                padding: 6px 10px;
            }

            .alert-banner {
                margin-bottom: 12px;
                padding: 10px 12px;
                gap: 8px;
                border-radius: 14px;
                font-size: 0.7rem;
                line-height: 1.45;
            }

            .boundary-control-card,
            .station-grid,
            .stat-card {
                border-radius: 16px;
            }

            .boundary-control-card {
                padding: 12px;
            }

            .boundary-control-header {
                gap: 10px;
                margin-bottom: 12px;
            }

            .boundary-control-header h3 {
                font-size: 0.86rem;
                margin-bottom: 4px;
            }

            .boundary-control-header p {
                font-size: 0.68rem;
                line-height: 1.45;
            }

            .boundary-summary {
                gap: 6px;
            }

            .boundary-summary-pill {
                min-height: 28px;
                padding: 0 10px;
                font-size: 0.62rem;
                gap: 6px;
            }

            .coverage-diagnostic { grid-template-columns:1fr; gap:7px; margin-bottom:12px; }
            .coverage-diagnostic__item { padding:10px 11px; border-radius:13px; }
            .coverage-diagnostic__item > i { flex-basis:28px; height:28px; }
            .coverage-diagnostic__item strong { font-size:.72rem; }
            .coverage-diagnostic__item small { font-size:.64rem; }

            .boundary-table th,
            .boundary-table td {
                padding: 8px 8px;
                font-size: 0.68rem;
            }

            .boundary-table th {
                font-size: 0.58rem;
            }

            .boundary-sync-btn {
                min-height: 30px;
                padding: 0 10px;
                font-size: 0.64rem;
                border-radius: 10px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 12px;
            }

            .stat-card {
                padding: 12px;
                gap: 10px;
            }

            .stat-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                font-size: 15px;
            }

            .stat-info h3 {
                font-size: 0.58rem;
                margin-bottom: 4px;
            }

            .stat-number {
                font-size: 1rem;
            }

            .stat-change {
                font-size: 0.55rem;
                margin-top: 2px;
            }

            .search-filter-bar {
                margin-bottom: 12px;
                padding: 10px 12px;
                border-radius: 16px;
                gap: 10px;
            }

            .search-box {
                max-width: none;
                width: 100%;
                min-height: 38px;
                padding: 6px 12px;
            }

            .search-box input,
            .filter-select,
            .btn-filter,
            .add-dropdown-btn {
                font-size: 0.68rem;
            }

            .filter-actions {
                width: 100%;
                gap: 8px;
            }

            .filter-select,
            .btn-filter,
            .add-dropdown-btn {
                min-height: 34px;
                padding: 6px 10px;
                border-radius: 10px;
            }

            .add-dropdown {
                width: 100%;
            }

            .add-dropdown-btn {
                width: 100%;
                justify-content: center;
            }

            .add-dropdown-content {
                min-width: 0;
                width: 100%;
            }

            .section-header {
                padding: 12px;
                gap: 10px;
            }

            .section-header h3 {
                font-size: 0.82rem;
                gap: 8px;
            }

            .section-header h3 i {
                width: 30px;
                height: 30px;
                border-radius: 10px;
                font-size: 0.8rem;
            }

            .stat-badge {
                min-height: 28px;
                padding: 0 10px;
                font-size: 0.62rem;
            }

            .filter-controls {
                width: 100%;
                gap: 8px;
                flex-wrap: wrap;
            }

            .filter-select-sm {
                min-height: 34px;
                padding: 6px 10px;
                font-size: 0.68rem;
                border-radius: 10px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }

            .table-responsive.mobile-card-table {
                overflow: visible;
            }

            .station-table {
                font-size: 0.66rem;
                min-width: 620px;
            }

            .mobile-card-table .station-table {
                min-width: 0;
                width: 100%;
            }

            .mobile-card-table .station-table thead {
                display: none;
            }

            .mobile-card-table .station-table,
            .mobile-card-table .station-table tbody,
            .mobile-card-table .station-table tr,
            .mobile-card-table .station-table td {
                display: block;
                width: 100%;
            }

            .mobile-card-table .station-table tbody {
                padding: 8px;
            }

            .mobile-card-table .station-table tr {
                margin-bottom: 10px;
                padding: 10px 12px;
                border: 1px solid rgba(245, 158, 11, 0.12);
                border-radius: 14px;
                background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }

            .mobile-card-table .station-table tr:last-child {
                margin-bottom: 0;
            }

            .mobile-card-table .station-table td {
                min-height: 0;
                padding: 7px 0;
                border-bottom: 1px solid rgba(226, 232, 240, 0.8);
                font-size: 0.72rem;
                white-space: normal;
            }

            .mobile-card-table .station-table td:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .mobile-card-table .station-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.6rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #64748b;
            }

            .mobile-card-table .station-table td.actions {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 8px;
                flex-wrap: wrap;
                padding-top: 10px;
            }

            .mobile-card-table .station-table td.actions::before {
                width: 100%;
            }

            .mobile-card-table .station-table td .status-select,
            .mobile-card-table .station-table td .status {
                max-width: 100%;
            }

            .mobile-card-table .station-table td small {
                display: inline-block;
                margin-top: 2px;
                line-height: 1.35;
            }

            .station-table th,
            .station-table td {
                padding: 7px 8px;
                font-size: 0.63rem;
            }

            .station-table th {
                font-size: 0.54rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 14px;
            }

            .stat-card {
                padding: 12px;
                gap: 10px;
                border-radius: 16px;
            }

            .stat-info h3 {
                font-size: 0.62rem;
                line-height: 1.25;
            }

            .stat-number {
                font-size: 1.18rem;
                line-height: 1;
            }

            .stat-change {
                font-size: 0.66rem;
            }

            .action-btn {
                width: 32px;
                height: 32px;
                border-radius: 10px;
            }

            .status-select {
                min-height: 30px;
                padding: 3px 7px;
                font-size: 0.66rem;
            }

            .modal {
                align-items: flex-start;
                padding: max(8px, env(safe-area-inset-top, 0px)) 10px calc(env(safe-area-inset-bottom, 0px) + 104px);
            }

            .modal-content,
            #registerUserModal .modal-content,
            #editUserModal .modal-content,
            #teamModal .modal-content,
            #editOfficerModal .modal-content,
            #equipmentCategoryModal .modal-content,
            #equipmentItemModal .modal-content,
            #stationEquipmentModal .modal-content,
            #viewUserModal .modal-content,
            #viewOfficerModal .modal-content,
            #viewTeamModal .modal-content,
            #editTeamModal .modal-content,
            #deleteOfficerModal .modal-content,
            #deleteUserModal .modal-content {
                width: 100%;
                max-width: 100% !important;
                max-height: calc(100dvh - 118px);
                margin-top: 0;
                border-radius: 18px;
            }

            .modal-header {
                padding: 11px 12px;
                border-radius: 18px 18px 0 0;
                gap: 10px;
            }

            .modal-header h2 {
                font-size: 0.86rem;
                line-height: 1.25;
                min-width: 0;
                flex: 1;
            }

            .modal-close {
                width: 30px;
                height: 30px;
                font-size: 0.92rem;
                flex-shrink: 0;
            }

            .modal-body {
                padding: 12px;
                overscroll-behavior: contain;
            }

            .modal-footer {
                position: sticky;
                bottom: 0;
                padding: 10px 12px 14px;
                gap: 8px;
                background: linear-gradient(180deg, rgba(255,248,239,0.9) 0%, #fff 28%);
                backdrop-filter: blur(8px);
            }

            #editTeamModal .modal-content,
            #editEquipmentModal .modal-content {
                width: 100%;
                max-width: 100% !important;
            }

            .modal-footer .btn-outline,
            .modal-footer .btn-primary,
            .modal-footer .btn-danger {
                flex: 1 1 0;
                min-height: 38px;
                padding: 8px 12px;
                font-size: 0.74rem;
                border-radius: 12px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .form-label {
                margin-bottom: 6px;
                font-size: 0.75rem;
            }

            .form-input,
            .form-select {
                padding: 9px 11px;
                border-radius: 12px;
                font-size: 0.78rem;
            }

            .resource-profile-card {
                gap: 0.55rem;
            }

            .resource-profile-hero {
                gap: 0.7rem;
                padding: 0.7rem 0.78rem;
                border-radius: 14px;
            }

            .resource-profile-avatar {
                width: 40px;
                height: 40px;
                font-size: 0.72rem;
            }

            .resource-profile-name {
                font-size: 0.82rem;
            }

            .resource-profile-meta {
                font-size: 0.7rem;
            }

            .resource-detail-grid {
                grid-template-columns: 1fr;
                gap: 0.35rem;
            }

            .resource-detail-card {
                padding: 0.18rem 0;
                border: 0;
                border-bottom: 1px solid #eef2f6;
                border-radius: 0;
                box-shadow: none;
                background: transparent;
            }

            .resource-detail-card:last-child {
                border-bottom: 0;
            }

            .resource-detail-card .detail-label {
                font-size: 0.64rem;
                margin-bottom: 0.12rem;
            }

            .resource-detail-card .detail-value {
                font-size: 0.78rem;
                line-height: 1.35;
            }

            .resource-detail-section-title {
                font-size: 0.68rem;
                margin-bottom: 0.35rem;
            }

            #toast {
                top: auto;
                right: 50%;
                bottom: calc(env(safe-area-inset-bottom, 0px) + 92px);
                transform: translateX(50%) translateY(10px);
                width: min(92vw, 320px);
                max-width: min(92vw, 320px);
                border-radius: 14px;
                padding: 11px 14px;
                font-size: 0.76rem;
            }

            #toast.show {
                transform: translateX(50%) translateY(0);
            }
        }
    </style>
    @include('chief.partials.resources-presentation')
</head>
<body class="chief-legacy-layout resource-management-page">
    <!-- Side Navigation -->
    @include($isProvincialResourceAdmin ? 'admin.partials.sidebar' : 'chief.partials.sidebar', ['active' => 'resources'])
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
                <a href="{{ route($resourceRoutePrefix.'.dashboard') }}" class="nav-item active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route($resourceRoutePrefix.'.fsic') }}" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 2 }}</span>
                        </a>
                <a href="{{ route($resourceRoutePrefix.'.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route($resourceRoutePrefix.'.management') }}" class="nav-item"><i class="fa-solid fa-map-location-dot"></i><span>Management</span></a>
                <a href="{{ route($resourceRoutePrefix.'.reports') }}" class="nav-item"><i class="fa-solid fa-chart-pie"></i><span>Reports</span></a>
                <a href="{{ route($resourceRoutePrefix.'.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
                
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="{{ route($resourceRoutePrefix.'.analytics') }}" class="nav-item"><i class="fa-solid fa-chart-line"></i><span>Fire Risk Forecast</span></a>
                {{-- <a href="#" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
                <a href="#" class="nav-item"><i class="fa-solid fa-bell"></i><span>Alerts</span></a> --}}
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside>
    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>

    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Resource Management</h1>
                    <p class="text-xs text-slate-500 font-medium">Station Assets And Personnel Registry • {{ now()->format('l, F j') }}</p>
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
                            <a href="{{ $isProvincialResourceAdmin ? route('admin.dashboard') : route('station-admin.profile') }}" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>{{ $isProvincialResourceAdmin ? 'Admin Home' : 'Commander Profile' }}</span>
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

        <div class="management-container chief-theme-shell">
            <section class="chief-theme-hero resource-order-hero">
                <div>
                    <span class="chief-theme-hero__eyebrow">
                        <i class="fa-solid fa-toolbox"></i>
                        Station Resource Desk
                    </span>
                    <h1 class="chief-theme-hero__title">Resource Management</h1>
                    <p class="chief-theme-hero__copy">Manage your personnel, schedules, equipment, and community coverage.</p>
                    <div class="chief-theme-badges">
                        <span class="chief-theme-badge"><i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs</span>
                    </div>
                </div>
                <div class="chief-theme-summary-grid">
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Personnel</div>
                        <div class="chief-theme-summary-value">{{ $stationOfficers->count() }}</div>
                        <div class="chief-theme-summary-copy">{{ $stationOfficers->where('status','available')->count() }} currently active.</div>
                    </article>
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Equipment</div>
                        <div class="chief-theme-summary-value">{{ $equipments->count() }}</div>
                        <div class="chief-theme-summary-copy">Tracked equipment units in the command inventory.</div>
                    </article>
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Active Stations</div>
                        <div class="chief-theme-summary-value">{{ $activeStations->count() }}</div>
                        <div class="chief-theme-summary-copy">Station records available for assignment and support.</div>
                    </article>
                </div>
            </section>

            <nav class="resource-priority-map" aria-label="Resource management priorities">
                <a href="#personnelResourceSection"><i class="fa-solid fa-bolt" aria-hidden="true"></i><strong>Personnel</strong></a>
                <a href="#teamsResourceSection"><i class="fa-solid fa-people-group" aria-hidden="true"></i><strong>Teams</strong></a>
                <a href="#apparatusResourceSection"><i class="fa-solid fa-truck" aria-hidden="true"></i><strong>Apparatus</strong></a>
                <a href="#equipmentResourceSection"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><strong>Equipment</strong></a>
                <a href="#boundaryResourceSection"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><strong>Coverage</strong></a>
                <a href="#barangayOfficerResourceSection"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><strong>Barangay officers</strong></a>
                <a href="#usersResourceSection"><i class="fa-solid fa-address-book" aria-hidden="true"></i><strong>Accounts</strong></a>
            </nav>

            @php
                $syncedBoundaryCount = $boundaryBarangays->where('boundary_status', 'synced')->count();
                $invalidBoundaryCount = $boundaryBarangays->where('boundary_status', 'invalid')->count();
                $missingBoundaryCount = $boundaryBarangays->where('boundary_status', 'missing')->count();
                $stationAddressBarangay = $currentStation?->address?->barangay;
                $stationMunicipality = $stationAddressBarangay?->city?->city_name;
                $stationProvince = $stationAddressBarangay?->city?->province?->province_name;
                $coverageRelationshipIssue = !$currentStation
                    ? 'Select a station to inspect its relationships.'
                    : (!$currentStation->address_id
                        ? 'Station address is not assigned.'
                        : (!$stationAddressBarangay
                            ? 'Station address has no barangay relationship.'
                            : (!$stationAddressBarangay->city_id ? 'Station barangay has no municipality relationship.' : null)));
                $municipalityCoverageCount = $stationAddressBarangay?->city_id
                    ? $boundaryBarangays->filter(fn ($item) => (int) ($item['city_id'] ?? 0) === (int) $stationAddressBarangay->city_id)->count()
                    : 0;
            @endphp

            <section class="boundary-control-card resource-tier resource-tier--community" id="boundaryResourceSection">
                <div class="boundary-control-header">
                    <div>
                        <h3><i class="fa-solid fa-draw-polygon"></i> Barangay Boundary Control</h3>
                        <p>Geoapify sync stays manual. Refresh one covered barangay at a time when you want the latest boundary, then refine the polygon manually on the map if the imported line needs cleanup.</p>
                    </div>
                    <div class="boundary-summary">
                        <span class="boundary-summary-pill"><i class="fa-solid fa-layer-group"></i> {{ $boundaryBarangays->count() }} covered barangays</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-circle-check"></i> {{ $syncedBoundaryCount }} synced</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-triangle-exclamation"></i> {{ $invalidBoundaryCount }} invalid</span>
                        <span class="boundary-summary-pill"><i class="fa-solid fa-clock-rotate-left"></i> {{ $missingBoundaryCount }} pending</span>
                    </div>
                </div>

                <div class="coverage-diagnostic" aria-label="Station coverage relationship diagnostic">
                    <div class="coverage-diagnostic__item {{ $coverageRelationshipIssue ? 'is-warning' : '' }}">
                        <i class="fa-solid {{ $coverageRelationshipIssue ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
                        <div><strong>{{ $coverageRelationshipIssue ? 'Relationship needs attention' : 'Address relationships connected' }}</strong><small>{{ $coverageRelationshipIssue ?? 'Station → barangay → municipality is complete.' }}</small></div>
                    </div>
                    <div class="coverage-diagnostic__item {{ $stationMunicipality ? '' : 'is-warning' }}">
                        <i class="fa-solid fa-city" aria-hidden="true"></i>
                        <div><strong>{{ $stationMunicipality ?? 'Municipality unavailable' }}</strong><small>{{ $stationProvince ? $stationProvince.' · ' : '' }}Barangays with this city ID are treated as municipality coverage.</small></div>
                    </div>
                    <div class="coverage-diagnostic__item">
                        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                        <div><strong>{{ $municipalityCoverageCount }} municipality barangays</strong><small>{{ $boundaryBarangays->count() }} total active or inferred coverage records shown.</small></div>
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
                            @forelse ($boundaryPage as $boundaryBarangay)
                                <tr>
                                    <td data-label="Barangay"><strong>{{ $boundaryBarangay['barangay_name'] }}</strong></td>
                                    <td data-label="City">{{ $boundaryBarangay['city_name'] ?? 'No city linked' }}</td>
                                    <td data-label="Status">
                                        <span class="boundary-status-pill {{ $boundaryBarangay['boundary_status'] }}">
                                            <i class="fa-solid {{ $boundaryBarangay['boundary_status_icon'] }}"></i>
                                            {{ $boundaryBarangay['boundary_status_label'] }}
                                        </span>
                                    </td>
                                    <td data-label="Source">{{ $boundaryBarangay['boundary_source'] ? strtoupper($boundaryBarangay['boundary_source']) : 'NONE' }}</td>
                                    <td data-label="Last Sync">{{ $boundaryBarangay['boundary_updated_at_human'] ?? 'Never synced' }}</td>
                                    <td data-label="Action" class="actions">
                                        <div class="boundary-action-group">
                                            <form class="boundary-sync-form" method="POST" action="{{ route($resourceRoutePrefix.'.barangay-boundaries.sync', $boundaryBarangay['barangay_id']) }}" data-route="{{ route($resourceRoutePrefix.'.barangay-boundaries.sync', $boundaryBarangay['barangay_id']) }}">
                                                @csrf
                                                <button type="submit" class="boundary-sync-btn" title="Fetch the latest barangay polygon from Geoapify">
                                                    <i class="fa-solid fa-arrows-rotate"></i>
                                                    {{ $boundaryBarangay['boundary_status'] === 'synced' ? 'Refresh' : 'Sync' }}
                                                </button>
                                            </form>
                                            <a href="{{ route($resourceRoutePrefix.'.barangays.show', $boundaryBarangay['barangay_id']) }}" class="boundary-visit-btn" title="Open barangay boundary details">
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
                @include('chief.partials.pagination', [
                    'paginator' => $boundaryPage,
                    'ariaLabel' => 'Boundary pagination',
                    'summaryLabel' => 'boundaries',
                    'class' => 'resource-pagination',
                ])
            </section>

            <div class="search-filter-bar resource-order-tools">
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
                        <option value="unavailable">--</option>
                        <option value="training">Training</option>
                        <option value="resigned">Resigned</option>
                    </select>
                  

                    <button class="btn-filter" id="refreshBtn"><i class="fa-solid fa-rotate"></i> Refresh</button>
                    
                    <div class="add-dropdown" id="addDropdown">
                        <button class="add-dropdown-btn" id="addDropdownBtn"><i class="fa-solid fa-plus"></i> Add New <i class="fa-solid fa-chevron-down"></i></button>
                        <div class="add-dropdown-content">
                            <a href="#" data-modal="registerUserModal"><i class="fa-solid fa-user-plus"></i> Register New User</a>
                            @if(strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) !== 'provincial admin')
                                <a href="#" onclick="openBarangayOfficerModal(); return false;"><i class="fa-solid fa-people-roof"></i> Add Barangay Officer</a>
                            @endif
                            {{-- <a href="#" data-modal="officerModal"><i class="fa-solid fa-user-plus"></i> Add Officer to Station</a> --}}
                            <a href="#" data-modal="teamModal"><i class="fa-solid fa-user-plus"></i> Create Team</a>
                            <a href="#" data-modal="equipmentItemModal"><i class="fa-solid fa-box"></i> Add Equipment Item</a>
                            {{-- <a href="#" data-modal="equipmentCategoryModal"><i class="fa-solid fa-tags"></i> Add Equipment Category</a> --}}
                            <a href="#" data-modal="stationEquipmentModal"><i class="fa-solid fa-warehouse"></i> Add Equipment Station</a>
                            <a href="#" data-modal="apparatusModal" onclick="openApparatusModal()"><i class="fa-solid fa-truck-field"></i> Register Apparatus</a>
                        </div>
                    </div>
                    
                    <button class="btn-outline" id="exportBtn"><i class="fa-solid fa-download"></i> Export</button>
                </div>
            </div>

            <div class="tab-navigation resource-order-station" id="stationTabs">
                     @foreach ($activeStations as $station)

                <button class="tab-btn active" data-station="all"><i class="fa-solid fa-building"></i>
                    {{ $station->station_name }}
                </button>
                @endforeach
          
               
            </div>

            <!-- Personnel Table -->
            <div class="station-grid resource-tier resource-tier--operations resource-table-compact" id="personnelResourceSection">
                <div class="section-header">
                    <h3><i class="fa-solid fa-user"></i> Personnel Management</h3>
                    <span class="stat-badge" id="personnelCount">{{ $stationOfficers->count() }} total</span>
                </div>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table">
                        <thead>
                            <tr>
                                <th class="plain-sight-hidden" aria-hidden="true">BFP ID</th>
                                <th>Officer Name</th>
                                <th>Station</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="personnelTableBody">
                            @foreach ($personnelPage as $officer)
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
                                <td class="plain-sight-hidden" aria-hidden="true">{{ $officer->user?->user_id ?? '--' }}</td>
                                <td class="personnel-name" data-label="Officer Name"><strong>{{ $officer->user?->first_name ?? '' }} {{ $officer->user?->middle_name ?? '' }} {{ $officer->user?->last_name ?? '' }}</strong></td>
                                <td data-label="Station">{{ $officer->station?->station_name ?? '--' }}</td>
                                <td data-label="Rank">{{ $officer->rank ?? '--' }}</td>
                                <td data-label="Status">
                                    <select class="status-select" data-officer-id="{{ $officer->station_officer_id }}" style="background: {{ $bgColor }}; color: {{ $textColor }}; border-color: {{ $textColor }}40;">
                                        <option value="available" {{ $officer->status == 'available' ? 'selected' : '' }}>Available</option>
                                        <option value="dispatched" {{ $officer->status == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                        <option value="on-scene" {{ $officer->status == 'on-scene' ? 'selected' : '' }}>On Scene</option>
                                        <option value="busy" {{ $officer->status == 'busy' ? 'selected' : '' }}>Busy</option>
                                        <option value="off-duty" {{ $officer->status == 'off-duty' ? 'selected' : '' }}>Off Duty</option>
                                        <option value="on-leave" {{ $officer->status == 'on-leave' ? 'selected' : '' }}>On Leave</option>
                                        <option value="unavailable" {{ $officer->status == 'unavailable' ? 'selected' : '' }}>--</option>
                                        <option value="training" {{ $officer->status == 'training' ? 'selected' : '' }}>Training</option>
                                        <option value="resigned" {{ $officer->status == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                    </select>
                                </td>
                                <td class="actions" data-label="Actions">
                                    <button class="action-btn view-resource" onclick="viewOfficer({{ $officer->station_officer_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn edit-resource" onclick="editOfficer({{ $officer->station_officer_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    @if((int) ($officer->user_id ?? 0) !== (int) (auth()->user()->user_id ?? 0))
                                    <button class="action-btn delete-resource" onclick="deleteOfficer({{ $officer->station_officer_id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $personnelPage,
                    'ariaLabel' => 'Personnel pagination',
                    'summaryLabel' => 'personnel',
                    'class' => 'resource-pagination',
                ])
            </div>

            {{-- Shift and rest-day scheduling is disabled. Team availability is managed directly. --}}
            @if(false)
            @php
                $dutyRosterRole = strtolower(trim((string) (auth()->user()?->role?->role ?? '')));
                $isProvincialRosterAdmin = $dutyRosterRole === 'provincial admin';
                $dutyRosterStationId = $isProvincialRosterAdmin
                    ? (int) request('roster_station_id', $activeStations->first()?->station_id)
                    : (int) ($currentStation?->station_id ?? 0);
            @endphp
            @if(in_array($dutyRosterRole, ['chief', 'stationadmin', 'provincial admin'], true))
            <section class="duty-roster resource-tier resource-tier--roster" id="dutyRosterApp" aria-labelledby="dutyRosterTitle">
                <div class="duty-roster__header">
                    <div>
                        <span class="duty-roster__eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> Live station staffing</span>
                        <h3 id="dutyRosterTitle">Dynamic Duty Roster</h3>
                        <p>1. Record availability and rest days. 2. Assign available firefighters to a run. 3. Set duty hours and recovery. A 24-hour shift includes 24 hours of recovery; 12-hour shifts include 12 hours.</p>
                    </div>
                    <div class="duty-roster__actions">
                        @if($isProvincialRosterAdmin)
                            <label class="duty-roster__station-picker">
                                <span>Station</span>
                                <select id="dutyRosterStationSelect" aria-label="Select station duty roster">
                                    @foreach($activeStations as $station)
                                        <option value="{{ $station->station_id }}" @selected((int) $station->station_id === $dutyRosterStationId)>{{ $station->station_name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <span class="duty-roster__clock" id="dutyRosterClock">Loading station time…</span>
                        <button type="button" class="duty-roster__button duty-roster__button--secondary" data-roster-action="exception">
                            <i class="fa-solid fa-calendar-xmark"></i> Rest / Leave
                        </button>
                        <button type="button" class="duty-roster__button" data-roster-action="shift">
                            <i class="fa-solid fa-calendar-plus"></i> Schedule Shift
                        </button>
                    </div>
                </div>

                <div class="duty-roster__metrics" aria-live="polite">
                    <article><span>Deployable now</span><strong id="dutyMetricDeployable">—</strong><small>firefighters</small></article>
                    <article><span>Station command</span><strong id="dutyMetricCommand">—</strong><small>chief/admin on station duty</small></article>
                    <article><span>Rest / leave</span><strong id="dutyMetricExceptions">—</strong><small>active right now</small></article>
                    <article><span>Next handoff</span><strong id="dutyMetricHandoff">—</strong><small id="dutyMetricHandoffLabel">No upcoming shift</small></article>
                </div>

                <div class="duty-roster__body">
                    <div>
                        <div class="duty-roster__subhead"><h4>Shift timeline</h4><span>Updates every 30 seconds</span></div>
                        <div id="dutyShiftTimeline" class="duty-roster__timeline"><div class="duty-roster__loading">Loading duty shifts…</div></div>
                    </div>
                    <aside>
                        <div class="duty-roster__subhead"><h4>Rest &amp; leave</h4><span>Not deployable</span></div>
                        <div id="dutyExceptionList" class="duty-roster__exceptions"><div class="duty-roster__loading">Loading availability…</div></div>
                    </aside>
                </div>

                <dialog class="duty-roster__dialog" id="dutyShiftDialog">
                    <form id="dutyShiftForm" method="dialog">
                        <div class="duty-roster__dialog-head"><div><span>Duty planning</span><h4 id="dutyShiftDialogTitle">Schedule a shift</h4></div><button type="button" data-dialog-close aria-label="Close">&times;</button></div>
                        <div class="duty-roster__form-grid duty-roster__plan-picker">
                            <label>Quick plan<select name="schedule_template">
                                <option value="single">One team</option>
                                <option value="two_team_12">Two teams, 12-hour turns</option>
                                <option value="two_team_24">1st / 2nd run, 24 hours on / 24 hours rest</option>
                                <option value="three_team_24">Three teams, 24-hour turns</option>
                            </select><small>Pick a plan or set one shift.</small></label>
                        </div>
                        <fieldset class="duty-roster__rotation-picker" data-rotation-picker hidden>
                            <legend>Teams in order</legend>
                            <p data-rotation-help>Choose teams.</p>
                            <div data-rotation-teams></div>
                        </fieldset>
                        <section class="duty-roster__turn-preview" data-turn-preview hidden aria-live="polite">
                            <h5><i class="fa-regular fa-calendar-check"></i> Turn preview</h5>
                            <div data-turn-preview-list>Choose teams to see their turns.</div>
                        </section>
                        <div class="duty-roster__form-grid">
                            <label>Shift name<input name="label" required maxlength="100" placeholder="e.g. Team 1 Night Shift"></label>
                            <label>Starts<input name="starts_at" type="datetime-local" required></label>
                            <label>Ends<input name="ends_at" type="datetime-local" required><small>For overnight duty, select the following date.</small></label>
                            <label>Duty duration<select name="shift_duration"><option value="24">24 hours + 24 hours recovery</option><option value="12">12 hours + 12 hours recovery</option><option value="custom">Custom start / end times</option></select><small>Recovery equals duty duration, up to 24 hours. Add Rest / Leave for longer time off.</small></label>
                            <label data-single-team>Assign to response run / team<select name="team_id" required></select></label>
                        </div>
                        <div class="duty-roster__form-grid duty-roster__recurrence" data-recurrence-fields>
                            <label data-repeat-choice>Repeats<select name="recurrence"><option value="none">Does not repeat</option><option value="daily">Every day</option><option value="weekly">Every week</option><option value="weekdays">Selected weekdays</option><option value="rotation">Team turns</option></select></label>
                            <label data-repeat-until hidden>End date<input name="recurrence_until" type="date"><small>Up to one year. You can change one day later.</small></label>
                            <label data-rotation-interval hidden>Repeat every (days)<input name="rotation_interval_days" type="number" min="2" max="31" value="2"><small>Use 2 for 24-hour duty followed by 24-hour rest.</small></label>
                        </div>
                        <fieldset class="duty-roster__weekday-picker" data-weekday-picker hidden>
                            <legend>Repeat on</legend>
                            @foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $weekdayValue => $weekdayLabel)
                                <label><input type="checkbox" name="weekdays[]" value="{{ $weekdayValue }}"><span>{{ $weekdayLabel }}</span></label>
                            @endforeach
                        </fieldset>
                        <div class="duty-roster__edit-scope" data-edit-scope hidden>
                            <strong>Apply this change to</strong>
                            <label><input type="radio" name="edit_scope" value="this" checked> Only this day</label>
                            <label><input type="radio" name="edit_scope" value="future"> This and next days</label>
                            <small>Completed shifts remain unchanged.</small>
                        </div>
                        <h5 data-deployable-title>Team members</h5>
                        <section data-deployable-fields>
                            <p data-crew-summary aria-live="polite"></p>
                            <details data-crew-editor>
                                <summary>Change members (optional)</summary>
                                <p>The selected team's members are included automatically. Changes here apply to this shift.</p>
                                <div class="duty-roster__assignment-grid" id="dutyAssignments"></div>
                            </details>
                        </section>
                        <h5>Must remain at the station</h5>
                        <div class="duty-roster__form-grid">
                            <label>Chief / station admin<select name="station_duty" required></select></label>
                            <label>Backup command (recommended)<select name="station_duty_backup"><option value="">None</option></select></label>
                        </div>
                        <label class="duty-roster__notes">Notes<textarea name="notes" maxlength="1000" rows="2" placeholder="Optional briefing or handoff note"></textarea></label>
                        <div class="duty-roster__form-error" data-form-error></div>
                        <div class="duty-roster__dialog-actions"><button type="button" class="duty-roster__button duty-roster__button--secondary" data-dialog-close>Cancel</button><button class="duty-roster__button" type="submit">Save shift</button></div>
                    </form>
                </dialog>

                <dialog class="duty-roster__dialog" id="dutyExceptionDialog">
                    <form id="dutyExceptionForm" method="dialog">
                        <div class="duty-roster__dialog-head"><div><span>Availability</span><h4>Set rest day or leave</h4></div><button type="button" data-dialog-close aria-label="Close">&times;</button></div>
                        <div class="duty-roster__form-grid">
                            <label>Personnel<select name="station_officer_id" required></select></label>
                            <label>Type<select name="type" required><option value="rest_day">Rest day</option><option value="leave">Leave</option></select></label>
                            <label>Starts<input name="starts_at" type="datetime-local" required></label>
                            <label>Ends<input name="ends_at" type="datetime-local" required></label>
                        </div>
                        <label class="duty-roster__notes">Reason<input name="reason" maxlength="255" placeholder="Optional reason"></label>
                        <div class="duty-roster__form-error" data-form-error></div>
                        <div class="duty-roster__dialog-actions"><button type="button" class="duty-roster__button duty-roster__button--secondary" data-dialog-close>Cancel</button><button class="duty-roster__button" type="submit">Save availability</button></div>
                    </form>
                </dialog>
                <dialog class="duty-roster__dialog duty-roster__delete-dialog" id="dutyDeleteDialog">
                    <div class="duty-roster__delete-panel">
                        <div class="duty-roster__dialog-head"><div><span>Remove shift</span><h4>What should be removed?</h4></div><button type="button" data-dialog-close aria-label="Close">&times;</button></div>
                        <p data-delete-help>Choose one.</p>
                        <div class="duty-roster__delete-choices">
                            <button type="button" class="duty-roster__delete-choice" data-delete-scope="this">
                                <i class="fa-regular fa-calendar"></i>
                                <span><strong>Only this day</strong><small>Keep the next shifts.</small></span>
                            </button>
                            <button type="button" class="duty-roster__delete-choice duty-roster__delete-choice--danger" data-delete-scope="future">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <span><strong>This and next days</strong><small>Keep past shifts.</small></span>
                            </button>
                        </div>
                        <div class="duty-roster__form-error" data-form-error></div>
                        <div class="duty-roster__dialog-actions"><button type="button" class="duty-roster__button duty-roster__button--secondary" data-dialog-close>Cancel</button></div>
                    </div>
                </dialog>
                <script type="application/json" id="dutyRosterConfig">{!! json_encode(['baseUrl' => url($isProvincialRosterAdmin ? '/admin/duty-roster' : '/station-admin/duty-roster'), 'timezone' => config('app.timezone'), 'stationId' => $dutyRosterStationId, 'isProvincialAdmin' => $isProvincialRosterAdmin], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
            </section>
            @endif
            @endif

            <!-- Teams Table -->
            <div class="station-grid resource-tier resource-tier--teams resource-table-compact" id="teamsResourceSection">
                <div class="section-header">
                    <h3><i class="fa-solid fa-people-group"></i> Team Management</h3>
                    <span class="stat-badge" id="teamsCount">{{ $allTeam->count() }} teams</span>
                </div>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table">
                        <thead>
                            <tr>
                                <th class="plain-sight-hidden" aria-hidden="true">Team ID</th>
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
                            @foreach ($teamsPage as $team)
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
                            <tr class="team-row" data-team-id="{{ $team->team_id }}" data-status="{{ $teamStatusClass }}" data-station-id="{{ $team->station_id ?? $team->teamLeader?->station_id ?? '' }}">
                                <td class="plain-sight-hidden" aria-hidden="true"><strong>{{ $team->team_id ?? '--' }}</strong></td>
                                <td data-label="Team Leader"><strong>{{ trim(($team->teamLeader?->user?->first_name ?? '').' '.($team->teamLeader?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->teamLeader?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Driver"><strong>{{ trim(($team->commander?->user?->first_name ?? '').' '.($team->commander?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->commander?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Pumper"><strong>{{ trim(($team->pumpers?->user?->first_name ?? '').' '.($team->pumpers?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->pumpers?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Officer 1"><strong>{{ trim(($team->officer1?->user?->first_name ?? '').' '.($team->officer1?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->officer1?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Officer 2"><strong>{{ trim(($team->officer2?->user?->first_name ?? '').' '.($team->officer2?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->officer2?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Officer 3"><strong>{{ trim(($team->officer3?->user?->first_name ?? '').' '.($team->officer3?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->officer3?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Rescue/Radio"><strong>{{ trim(($team->rescueRadio?->user?->first_name ?? '').' '.($team->rescueRadio?->user?->last_name ?? '')) ?: '--' }}</strong><br><small>{{ $team->rescueRadio?->user?->account_code ?? '--' }}</small></td>
                                <td data-label="Status">
                                    <select class="status-select" data-team-id="{{ $team->team_id }}" style="background: {{ $teamBgColor }}; color: {{ $teamTextColor }}; border-color: {{ $teamTextColor }}40;">
                                        <option value="available" {{ $team->status == 'available' ? 'selected' : '' }}>Available</option>
                                        {{-- <option value="dispatched" {{ $team->status == 'dispatched' ? 'selected' : '' }}>Dispatched</option> --}}
                                        <option value="responding" {{ $team->status == 'responding' ? 'selected' : '' }}>On Scene</option>
                                        {{-- <option value="busy" {{ $team->status == 'busy' ? 'selected' : '' }}>Busy</option> --}}
                                        <option value="off" {{ $team->status == 'off' ? 'selected' : '' }}>Off Duty</option>
                                        {{-- <option value="training" {{ $team->status == 'training' ? 'selected' : '' }}>Training</option> --}}
                                    </select>
                                </td>
                                <td class="actions" data-label="Actions">
                                    <button class="action-btn view-resource" onclick="viewTeam({{ $team->team_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn edit-resource" onclick="openEditTeamModal({{ $team->team_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $teamsPage,
                    'ariaLabel' => 'Team pagination',
                    'summaryLabel' => 'teams',
                    'class' => 'resource-pagination',
                ])
            </div>

           

            @if(strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) !== 'provincial admin')
            <div class="station-grid resource-tier resource-tier--barangay resource-table-compact" id="barangayOfficerResourceSection">
                <div class="section-header">
                    <div><h3><i class="fa-solid fa-people-roof"></i> Barangay Officers</h3><small style="color:#64748b">Officers assigned within your station's active barangay coverage.</small></div>
                    <div style="display:flex;align-items:center;gap:10px"><span class="stat-badge">{{ $barangayOfficers->count() }} assigned</span><button type="button" class="btn-primary" onclick="openBarangayOfficerModal()"><i class="fa-solid fa-user-plus"></i> Add Officer</button></div>
                </div>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table">
                        <thead><tr><th>Officer</th><th>Barangay</th><th>Position</th><th>Portal Account</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($barangayOfficersPage as $officer)
                                <tr>
                                    <td data-label="Officer"><strong>{{ trim(($officer->user?->first_name ?? '').' '.($officer->user?->middle_name ?? '').' '.($officer->user?->last_name ?? '')) ?: 'Unlinked officer' }}</strong><small style="display:block;color:#64748b">{{ $officer->user?->contact_number ?? 'No contact number' }}</small></td>
                                    <td data-label="Barangay">{{ $officer->barangay?->barangay_name ?? 'Unknown' }}</td>
                                    <td data-label="Position"><span class="status" style="background:#fff7ed;color:#9a3412">{{ $officer->position }}</span></td>
                                    <td data-label="Portal Account">{{ $officer->user?->account_code ?? '--' }}<small style="display:block;color:#64748b">{{ $officer->user?->email }}</small></td>
                                    <td data-label="Status"><span class="status" style="background:{{ $officer->status === 'active' ? '#dcfce7' : '#f1f5f9' }};color:{{ $officer->status === 'active' ? '#166534' : '#475569' }}">{{ ucfirst($officer->status) }}</span></td>
                                    <td data-label="Actions"><button type="button" class="action-btn" onclick="openBarangayOfficerModal({{ $officer->barangay_officer_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button><button type="button" class="action-btn delete-resource" onclick="deleteBarangayOfficer({{ $officer->barangay_officer_id }})" title="Remove assignment"><i class="fa-solid fa-user-minus"></i></button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center;padding:28px;color:#64748b">No barangay officers assigned yet. Add an existing eligible user or register a new officer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $barangayOfficersPage,
                    'ariaLabel' => 'Barangay officer pagination',
                    'summaryLabel' => 'officers',
                    'class' => 'resource-pagination',
                ])
            </div>
            @endif

            <!-- All System Users Table with Enhanced Filtering and Edit Functionality -->
            <div class="station-grid resource-tier resource-tier--records resource-table-compact" id="usersResourceSection">
                <div class="section-header">
                    <h3><i class="fa-solid fa-users"></i> All System Users</h3>
                    <div class="filter-controls">
                        <select id="userRoleFilter" class="filter-select-sm" onchange="filterUsers()">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="firefighter">Firefighter</option>
                            <option value="chief">Chief</option>
                            <option value="provincial admin">Provincial Admin</option>
                            <option value="stationadmin">Station Admin</option>
                            <option value="citizen">Citizen</option>
                            @foreach($roles->filter(fn ($role) => str_starts_with(strtolower($role->role), 'barangay')) as $barangayRole)
                                <option value="{{ strtolower($barangayRole->role) }}">{{ ucfirst($barangayRole->role) }}</option>
                            @endforeach
                        </select>
                        <select id="userSortFilter" class="filter-select-sm" onchange="sortUsers()">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="role_asc">Role (A-Z)</option>
                            <option value="role_desc">Role (Z-A)</option>
                            <option value="date_desc">Date Modified (Newest)</option>
                            <option value="date_asc">Date Modified (Oldest)</option>
                        </select>
                        <span class="stat-badge" id="usersCount">{{ $systemUsers->count() }} total</span>
                    </div>
                </div>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table" id="usersTable">
                        <thead>
                            <tr>
                                <th class="sortable plain-sight-hidden" onclick="sortTableByColumn(0)" aria-hidden="true">User ID <i class="fa-solid fa-arrow-down-wide-short"></i></th>
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
                            @foreach ($usersPage as $user)
                            <tr class="user-row" data-user-id="{{ $user->user_id }}" data-role="{{ strtolower($user->role->role ?? 'unknown') }}" data-updated-at="{{ $user->updated_at ?? $user->created_at }}">
                                <td class="plain-sight-hidden" aria-hidden="true">{{ $user->user_id }}</td>
                                <td data-label="Account Code"><strong>{{ $user->account_code ?? '--' }}</strong></td>
                                <td data-label="Full Name">{{ $user->first_name ?? '' }} {{ $user->middle_name ?? '' }} {{ $user->last_name ?? '' }}</td>
                                <td data-label="Email">{{ $user->email ?? '--' }}</td>
                                <td data-label="Contact Number">{{ $user->contact_number ?? '--' }}</td>
                                <td data-label="Role">
                                    <span class="status" style="background: {{ 
                                        ($user->role->role ?? '') == 'admin' ? '#dc2626' : 
                                        (($user->role->role ?? '') == 'firefighter' ? '#3b82f6' : 
                                        (($user->role->role ?? '') == 'chief' ? '#8b5cf6' : 
                                        (($user->role->role ?? '') == 'stationadmin' ? '#8b5cf6' :
                                        (($user->role->role ?? '') == 'dispatcher' ? '#f59e0b' : 
                                        (($user->role->role ?? '') == 'paramedic' ? '#10b981' : '#6b7280'))))) 
                                    }}; color: white;">
                                        {{ ucfirst($user->role->role ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <span class="status {{ $user->email_verified_at ? 'active' : 'inactive' }}" style="background: {{ $user->email_verified_at ? '#10b981' : '#ef4444' }}; color: white;">
                                        {{ $user->email_verified_at ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                                <td data-label="Date Modified"><small>{{ \Carbon\Carbon::parse($user->updated_at ?? $user->created_at)->format('Y-m-d H:i') }}</small></td>
                                <td class="actions" data-label="Actions">
                                    <button class="action-btn view-resource" onclick="viewUser({{ $user->user_id }})" title="View"><i class="fa-solid fa-eye"></i></button>
                                    @if($user->can_manage_resources)
                                    <button class="action-btn edit-resource" onclick="openEditUserModal({{ $user->user_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    @if((int) ($user->user_id ?? 0) !== (int) (auth()->user()->user_id ?? 0))
                                    <button class="action-btn delete-resource" onclick="deleteUser({{ $user->user_id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    @endif
                                    @else
                                    <small title="This account is outside your station coverage or has no linked barangay.">View only</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $usersPage,
                    'ariaLabel' => 'User pagination',
                    'summaryLabel' => 'users',
                    'class' => 'resource-pagination',
                ])
            </div>

             <!-- Equipment Inventory Table -->
            <div class="station-grid resource-tier resource-tier--inventory resource-table-compact" id="equipmentResourceSection">
                <div class="section-header">
                    <h3><i class="fa-solid fa-warehouse" aria-hidden="true"></i> Station Equipment</h3>
                    <span class="stat-badge">{{ $stationEquipmentAssignments->count() }} assigned items</span>
                </div>
                <p style="margin:0 0 16px;color:#64748b;font-size:.875rem;line-height:1.6;">Equipment assigned to {{ $isProvincialResourceAdmin ? 'each station' : 'your station' }}, with its recorded availability and condition.</p>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table" aria-label="Equipment assigned to stations">
                        <thead>
                            <tr>
                                <th scope="col">Equipment</th>
                                @if($isProvincialResourceAdmin)<th scope="col">Station</th>@endif
                                <th scope="col">Category</th>
                                <th scope="col">Available</th>
                                <th scope="col">Damaged</th>
                                <th scope="col">Under Maintenance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stationEquipmentAssignments as $assignment)
                                <tr class="station-equipment-row" data-station-id="{{ $assignment->station_id }}">
                                    <td data-label="Equipment"><strong>{{ $assignment->equipment?->equipment_name ?? '--' }}</strong></td>
                                    @if($isProvincialResourceAdmin)<td data-label="Station">{{ $assignment->station?->station_name ?? '--' }}</td>@endif
                                    <td data-label="Category">{{ $assignment->equipment?->category ? \Illuminate\Support\Str::headline($assignment->equipment->category) : '--' }}</td>
                                    <td data-label="Available"><strong>{{ $assignment->available_quantity === null ? '--' : number_format((int) $assignment->available_quantity) }}</strong></td>
                                    <td data-label="Damaged">{{ $assignment->damaged_quantity === null ? '--' : number_format((int) $assignment->damaged_quantity) }}</td>
                                    <td data-label="Under Maintenance">{{ $assignment->maintenance_quantity === null ? '--' : number_format((int) $assignment->maintenance_quantity) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isProvincialResourceAdmin ? 6 : 5 }}" style="padding:24px;text-align:center;color:#64748b;">No equipment has been assigned yet. Use <strong>Add Equipment Station</strong> in the actions menu to assign inventory.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="height:1px;background:#e2e8f0;margin:24px 0;" aria-hidden="true"></div>
                <div class="section-header">
                    <h3><i class="fa-solid fa-kit-medical"></i> Equipment Catalog</h3>
                    <span class="stat-badge" id="equipmentCount">{{ $equipments->count() }} items</span>
                </div>
                <div class="table-responsive mobile-card-table">
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
                            @foreach ($equipmentPage as $item)
                                <tr class="equipment-row" data-equipment-id="{{ $item->equipment_id }}" data-category="{{ $item->category ?? 'Uncategorized' }}" data-type="{{ $item->type ?? 'Unspecified' }}">
                                    <td data-label="Equipment Name"><strong>{{ $item->equipment_name }}</strong></td>
                                    <td data-label="Category">{{ $item->category ?? 'Uncategorized' }}</td>
                                    {{-- <td class="equipment-station">{{ $item['station_name'] }}</td> --}}
                                    {{-- <td><span class="status-badge" style="background: #10b98120; color: #065f46; padding: 4px 10px; border-radius: 20px; font-weight: 600;">{{ $item['quantity'] }}</span></td> --}}
                                    {{-- <td><span class="status operational">Available</span></td> --}}
                                    <td class="actions" data-label="Actions">
                                        <button class="action-btn edit-resource" onclick="editEquipment({{ $item->equipment_id }})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="action-btn delete-resource" onclick="deleteEquipment({{ $item->equipment_id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $equipmentPage,
                    'ariaLabel' => 'Equipment pagination',
                    'summaryLabel' => 'equipment',
                    'class' => 'resource-pagination',
                ])
            </div>

            <div class="station-grid resource-tier resource-tier--apparatus resource-table-compact" id="apparatusResourceSection">
                <div class="section-header">
                    <h3><i class="fa-solid fa-truck-field"></i> Fire Apparatus Registry</h3>
                    <span class="stat-badge">{{ $apparatusRecords->count() }} units</span>
                </div>
                <div class="table-responsive mobile-card-table">
                    <table class="station-table">
                        <thead><tr><th>Unit</th><th>Station</th><th>Plate / Type</th><th>Capacity</th><th>Readiness</th><th>GPS</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($apparatusPage as $apparatus)
                                @php
                                    $ready = $apparatus->status === 'available' && $apparatus->maintenance_status === 'operational';
                                    $gpsColor = $apparatus->gps_status === 'online' ? '#15803d' : ($apparatus->gps_status === 'stale' ? '#b45309' : '#64748b');
                                @endphp
                                <tr>
                                    <td data-label="Unit"><strong>{{ $apparatus->unit_number }}</strong><br><small>{{ $apparatus->equipment?->equipment_name ?? 'Fire apparatus' }}</small></td>
                                    <td data-label="Station">{{ $apparatus->station?->station_name ?? '--' }}</td>
                                    <td data-label="Plate / Type"><strong>{{ $apparatus->plate_number ?: 'No plate' }}</strong><br><small>{{ ucwords(str_replace('_', ' ', $apparatus->apparatus_type)) }}</small></td>
                                    <td data-label="Capacity">{{ $apparatus->water_capacity_liters ? number_format($apparatus->water_capacity_liters).' L' : 'Not set' }}</td>
                                    <td data-label="Readiness"><span class="status-badge" style="background:{{ $ready ? '#dcfce7' : '#fee2e2' }};color:{{ $ready ? '#166534' : '#991b1b' }};padding:5px 10px;border-radius:999px;">{{ $ready ? 'Ready' : ucwords(str_replace('_', ' ', $apparatus->maintenance_status === 'operational' ? $apparatus->status : $apparatus->maintenance_status)) }}</span></td>
                                    <td data-label="GPS"><span style="color:{{ $gpsColor }};font-weight:700;"><i class="fa-solid fa-satellite-dish"></i> {{ ucwords(str_replace('_', ' ', $apparatus->gps_status)) }}</span></td>
                                    <td class="actions" data-label="Actions">
                                        <button class="action-btn edit-resource" type="button" onclick="openApparatusModal({{ $apparatus->apparatus_id }})" title="Edit apparatus"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="action-btn delete-resource" type="button" onclick="deleteApparatus({{ $apparatus->apparatus_id }})" title="Remove apparatus"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="text-align:center;padding:28px;color:#64748b;">No individual apparatus registered yet. Existing aggregate firetruck inventory remains available as a temporary fallback.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('chief.partials.pagination', [
                    'paginator' => $apparatusPage,
                    'ariaLabel' => 'Apparatus pagination',
                    'summaryLabel' => 'apparatus',
                    'class' => 'resource-pagination',
                ])
            </div>


        </div>
    </main>

    <!-- ==================== MODALS ==================== -->

    <div id="barangayOfficerModal" class="modal">
        <div class="modal-content" style="max-width:760px">
            <div class="modal-header"><h2><i class="fa-solid fa-people-roof"></i> <span id="barangayOfficerModalTitle">Add Barangay Officer</span></h2><button class="modal-close" type="button" onclick="closeModal('barangayOfficerModal')"><i class="fa-solid fa-times"></i></button></div>
            <form id="barangayOfficerForm" method="POST" action="{{ $resourceBaseUrl.'/barangay-officers' }}">
                @csrf<input type="hidden" name="_method" id="barangayOfficerMethod" value="POST">
                <div class="modal-body">
                    <div id="barangayOfficerModeGroup" class="form-group full-width"><label class="form-label">Officer source *</label><div style="display:flex;gap:10px;flex-wrap:wrap"><label style="padding:10px 14px;border:1px solid #dbe4ee;border-radius:10px"><input type="radio" name="mode" value="existing" checked> Select existing user</label><label style="padding:10px 14px;border:1px solid #dbe4ee;border-radius:10px"><input type="radio" name="mode" value="new"> Register new account</label></div></div>
                    <div id="barangayOfficerExistingFields" class="form-group full-width"><label class="form-label">Eligible user *</label><select class="form-select" name="user_id" id="barangayOfficerUser"><option value="">Select a user</option>@foreach($eligibleBarangayOfficerUsers as $eligibleUser)<option value="{{ $eligibleUser->user_id }}">{{ trim(($eligibleUser->first_name ?? '').' '.($eligibleUser->last_name ?? '')) }} — {{ ucfirst($eligibleUser->role?->role ?? 'user') }}{{ $eligibleUser->address?->barangay ? ' / '.$eligibleUser->address->barangay->barangay_name : '' }}</option>@endforeach</select><small>Firefighters, administrators, station administrators, and chiefs are excluded.</small></div>
                    <div id="barangayOfficerNewFields" class="form-grid" style="display:none">
                        <div class="form-group"><label class="form-label">First name *</label><input class="form-input" name="first_name" maxlength="100"></div>
                        <div class="form-group"><label class="form-label">Middle name</label><input class="form-input" name="middle_name" maxlength="100"></div>
                        <div class="form-group"><label class="form-label">Last name *</label><input class="form-input" name="last_name" maxlength="100"></div>
                        <div class="form-group"><label class="form-label">Mobile number *</label><input class="form-input" name="contact_number" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX"></div>
                        <div class="form-group full-width"><label class="form-label">Email *</label><input type="email" class="form-input" name="email" maxlength="255"></div>
                        <div class="form-group full-width"><label class="form-label">Street / office address</label><input class="form-input" name="street" maxlength="255" placeholder="Defaults to Barangay Hall"></div>
                        <div class="form-group"><label class="form-label">Password</label><input type="password" class="form-input" name="password" minlength="8" placeholder="Leave blank to generate"></div>
                        <div class="form-group"><label class="form-label">Confirm password</label><input type="password" class="form-input" name="password_confirmation"></div>
                    </div>
                    <div class="form-grid" style="margin-top:14px">
                        <div class="form-group"><label class="form-label">Covered barangay *</label><select class="form-select" name="barangay_id" id="barangayOfficerBarangay" required><option value="">Select barangay</option>@foreach($boundaryBarangays as $coveredBarangay)<option value="{{ $coveredBarangay['barangay_id'] }}">{{ $coveredBarangay['barangay_name'] }}{{ !empty($coveredBarangay['city_name']) ? ' — '.$coveredBarangay['city_name'] : '' }}</option>@endforeach</select></div>
                        <div class="form-group"><label class="form-label">Position *</label><select class="form-select" name="position" id="barangayOfficerPosition" required><option value="captain">Captain</option><option value="kagawad">Kagawad</option><option value="secretary">Secretary</option><option value="treasurer">Treasurer</option><option value="tanod">Tanod</option><option value="officer" selected>Officer</option></select></div>
                        <div class="form-group"><label class="form-label">Status *</label><select class="form-select" name="status" id="barangayOfficerStatus" required><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-outline" onclick="closeModal('barangayOfficerModal')">Cancel</button><button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Officer</button></div>
            </form>
        </div>
    </div>

    <!-- Register User Modal -->
    <div id="registerUserModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-plus"></i> Register New User</h2>
                <button class="modal-close" onclick="closeModal('registerUserModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="registerUserForm" method="POST" action="{{ url('/'.(strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) === 'provincial admin' ? 'admin' : 'station-admin').'/users/register') }}">
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
                            <select class="form-select" name="role_id" id="registerUserRole" required>
                                <option value="">Select Role</option>
                                @foreach($roles ?? [] as $role)
                                    @if($role->role_id == 1 || $role->role_id == 2)
                                        @php
                                            continue;
                                        @endphp
                                    @endif
                                    <option value="{{ $role->role_id }}" data-role-name="{{ strtolower($role->role) }}">{{ ucfirst($role->role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="registerUserRankGroup" style="display: none;">
                            <label class="form-label"><i class="fa-solid fa-medal"></i> Rank <span style="color: #dc2626;">*</span></label>
                            <select class="form-select" name="rank" id="registerUserRank">
                                <option value="">Select Rank</option>
                                <option value="Fire Officer 1">Fire Officer 1</option>
                                <option value="Fire Officer 2">Fire Officer 2</option>
                                <option value="Fire Officer 3">Fire Officer 3</option>
                                <option value="Senior Officer 1">Senior Officer 1</option>
                                <option value="Senior Officer 2">Senior Officer 2</option>
                                <option value="Senior Officer 3">Senior Officer 3</option>
                                <option value="Senior Fire Officer 4">Senior Fire Officer 4</option>
                                <option value="Fire Inspector">Fire Inspector</option>
                                <option value="Fire Senior Inspector">Fire Senior Inspector</option>
                                <option value="Fire Chief Inspector">Fire Chief Inspector</option>
                                <option value="Fire Superintendent">Fire Superintendent</option>
                            </select>
                            <small style="font-size: 0.7rem; color: #64748b;">Required for firefighter, chief, and station admin accounts.</small>
                        </div>
                        <div class="form-group" id="registerBarangayOfficerPositionGroup" style="display: none;">
                            <label class="form-label"><i class="fa-solid fa-id-badge"></i> Barangay Position <span style="color: #dc2626;">*</span></label>
                            <select class="form-select" name="barangay_officer_position" id="registerBarangayOfficerPosition">
                                <option value="">Select Position</option>
                                <option value="captain">Captain</option>
                                <option value="kagawad">Kagawad</option>
                                <option value="secretary">Secretary</option>
                                <option value="treasurer">Treasurer</option>
                                <option value="tanod">Tanod</option>
                                <option value="officer">Officer</option>
                            </select>
                            <small style="font-size: 0.7rem; color: #64748b;">Required for barangay officer accounts.</small>
                        </div>
                        <div class="form-group" id="registerBarangayOfficerStatusGroup" style="display: none;">
                            <label class="form-label"><i class="fa-solid fa-signal"></i> Barangay Officer Status</label>
                            <select class="form-select" name="barangay_officer_status" id="registerBarangayOfficerStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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
                                <small id="registerBarangayOfficerBarangayHint" style="display: none; font-size: 0.7rem; color: #64748b;">This barangay will be used for the barangay officer assignment.</small>
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
                    <select class="form-select" id="teamStationSelect" name="station_id" style='' required>
                        @foreach ($activeStations as $station)
                            <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                        @endforeach
                    </select>
                    {{-- <small style="font-size: 0.7rem; color: #64748b;">Select a station to load available personnel</small> --}}
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
                        <select class="form-select" name="commander_driver" id="driverSelect" disabled required>
                            <option value="">Select Driver</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-water"></i> Pumper <span style="color: #dc2626;">*</span></label>
                        <select class="form-select" name="pumper" id="pumperSelect" disabled required>
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
    <div class="modal-content" style="max-width: 980px;">
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
                                <option value="unavailable">--</option>
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
                        <select class="form-select" id="editOfficerRank" name="rank" required>
                            <option value="">Select Rank</option>
                            <option value="Fire Officer 1">Fire Officer 1</option>
                            <option value="Fire Officer 2">Fire Officer 2</option>
                            <option value="Fire Officer 3">Fire Officer 3</option>
                            <option value="Senior Officer 1">Senior Officer 1</option>
                            <option value="Senior Officer 2">Senior Officer 2</option>
                            <option value="Senior Officer 3">Senior Officer 3</option>
                            <option value="Senior Fire Officer 4">Senior Fire Officer 4</option>
                            <option value="Fire Inspector">Fire Inspector</option>
                            <option value="Fire Senior Inspector">Fire Senior Inspector</option>
                            <option value="Fire Chief Inspector">Fire Chief Inspector</option>
                            <option value="Fire Superintendent">Fire Superintendent</option>
                        </select>
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
                            <option value="unavailable">--</option>
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
            <form id="connectionForm" action="{{ route($resourceRoutePrefix.'.station-officers.store') }}" method="POST">
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
                            <option value="unavailable">--</option>
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
                        <option value="unavailable">--</option>
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
    @php
        $equipmentCategoryOptions = [
            'Personal Protective Equipment',
            'firefighting equipment',
            'rescue equipment',
            'communication equipment',
            'vehicles',
        ];
        $equipmentTypeOptions = ['disposable', 'repairable'];
        $apparatusRoutePrefix = strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) === 'provincial admin' ? 'admin' : 'station-admin';
    @endphp

    <div id="apparatusModal" class="modal">
        <div class="modal-content" style="max-width: 820px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-truck-field"></i> <span id="apparatusModalTitle">Register Fire Apparatus</span></h2>
                <button class="modal-close" type="button" onclick="closeModal('apparatusModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="apparatusForm" method="POST" action="{{ url('/'.$apparatusRoutePrefix.'/apparatus') }}">
                @csrf
                <input type="hidden" id="apparatusMethod" name="_method" value="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        @if($apparatusRoutePrefix === 'admin')
                            <div class="form-group"><label class="form-label">Assigned Station *</label><select class="form-select" name="station_id" id="apparatusStationId" required>@foreach($activeStations as $station)<option value="{{ $station->station_id }}">{{ $station->station_name }}</option>@endforeach</select></div>
                        @else
                            <input type="hidden" name="station_id" id="apparatusStationId" value="{{ $currentStation?->station_id }}">
                        @endif
                        <div class="form-group"><label class="form-label">Vehicle Equipment Type *</label><select class="form-select" name="equipment_id" id="apparatusEquipmentId" required><option value="">Select vehicle type</option>@foreach($equipments->where('category', 'vehicles') as $equipment)<option value="{{ $equipment->equipment_id }}">{{ $equipment->equipment_name }}</option>@endforeach</select><small>Defines the catalog type; this unit remains directly owned by its station.</small></div>
                        <div class="form-group"><label class="form-label">Unit Number *</label><input class="form-input" name="unit_number" id="apparatusUnitNumber" maxlength="50" placeholder="e.g. ENGINE 01" required></div>
                        <div class="form-group"><label class="form-label">Plate Number</label><input class="form-input" name="plate_number" id="apparatusPlateNumber" maxlength="30" placeholder="e.g. BFP-1234"></div>
                        <div class="form-group"><label class="form-label">Apparatus Type *</label><select class="form-select" name="apparatus_type" id="apparatusType" required><option value="fire_engine">Fire Engine</option><option value="pumper">Pumper</option><option value="tanker">Tanker</option><option value="ladder">Ladder Truck</option><option value="rescue">Rescue</option><option value="ambulance">Ambulance</option><option value="utility">Utility</option></select></div>
                        <div class="form-group"><label class="form-label">Water Capacity (liters)</label><input type="number" class="form-input" name="water_capacity_liters" id="apparatusCapacity" min="0" max="100000"></div>
                        <div class="form-group"><label class="form-label">Operational Status *</label><select class="form-select" name="status" id="apparatusStatus" required><option value="available">Available</option><option value="dispatched">Dispatched</option><option value="out_of_service">Out of Service</option></select></div>
                        <div class="form-group"><label class="form-label">Maintenance Status *</label><select class="form-select" name="maintenance_status" id="apparatusMaintenanceStatus" required><option value="operational">Operational</option><option value="due">Maintenance Due</option><option value="in_maintenance">In Maintenance</option></select></div>
                        <div class="form-group"><label class="form-label">Last Maintenance</label><input type="date" class="form-input" name="last_maintenance_at" id="apparatusLastMaintenance"></div>
                        <div class="form-group"><label class="form-label">Next Maintenance Due</label><input type="date" class="form-input" name="next_maintenance_due_at" id="apparatusNextMaintenance"></div>
                        <div class="form-group"><label class="form-label">GPS Status *</label><select class="form-select" name="gps_status" id="apparatusGpsStatus" required><option value="online">Online</option><option value="stale">Stale</option><option value="offline">Offline</option><option value="not_installed">Not Installed</option></select></div>
                        <div class="form-group"><label class="form-label">Last GPS Latitude</label><input type="number" step="0.0000001" class="form-input" name="last_lat" id="apparatusLat" min="-90" max="90"></div>
                        <div class="form-group"><label class="form-label">Last GPS Longitude</label><input type="number" step="0.0000001" class="form-input" name="last_lng" id="apparatusLng" min="-180" max="180"></div>
                        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Notes</label><textarea class="form-input" name="notes" id="apparatusNotes" rows="3" maxlength="1000"></textarea></div>
                    </div>
                    <section style="margin-top:18px;padding:16px;border:1px solid #dbe4ee;border-radius:14px;background:#f8fafc">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px">
                            <div><strong><i class="fa-solid fa-boxes-stacked"></i> Onboard equipment loadout</strong><small style="display:block;color:#64748b;margin-top:3px">Reserve equipment already assigned to this station. Quantities cannot exceed the station's serviceable stock.</small></div>
                        </div>
                        <div id="apparatusLoadoutRows" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px">
                            @foreach($stationEquipmentAssignments->filter(fn ($assignment) => strtolower((string) $assignment->equipment?->category) !== 'vehicles') as $assignment)
                                <label class="apparatus-loadout-row" data-station-id="{{ $assignment->station_id }}" data-assignment-id="{{ $assignment->equipment_station_id }}" style="display:flex;align-items:center;gap:10px;padding:10px;background:#fff;border:1px solid #e2e8f0;border-radius:10px">
                                    <input type="hidden" name="loadout[{{ $loop->index }}][equipment_station_id]" value="{{ $assignment->equipment_station_id }}">
                                    <span style="min-width:0;flex:1"><strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $assignment->equipment?->equipment_name }}</strong><small style="color:#64748b">{{ $assignment->available_quantity }} serviceable</small></span>
                                    <input type="number" name="loadout[{{ $loop->index }}][quantity]" class="form-input apparatus-loadout-quantity" min="0" max="{{ $assignment->available_quantity }}" value="0" aria-label="Onboard quantity for {{ $assignment->equipment?->equipment_name }}" style="width:76px;padding:7px">
                                </label>
                            @endforeach
                        </div>
                        <p id="apparatusLoadoutEmpty" style="display:none;color:#64748b;margin:8px 0 0">No non-vehicle equipment is assigned to this station yet.</p>
                    </section>
                </div>
                <div class="modal-footer"><button type="button" class="btn-outline" onclick="closeModal('apparatusModal')">Cancel</button><button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Apparatus</button></div>
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
            <form id="equipmentForm" method="POST" action="{{ route($resourceRoutePrefix.'.equipment-category.store') }}">
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
            <form id="eform" action="{{ route($resourceRoutePrefix.'.equipment.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-layer-group"></i> Equipment Category</label>
                        <select class="form-select" name="category">
                            <option value="">Select Category</option>
                            @foreach ($equipmentCategoryOptions as $categoryOption)
                                <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Equipment Name</label>
                        <input type="text" class="form-input" name="equipment_name" placeholder="e.g., Fire Extinguisher" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-screwdriver-wrench"></i> Equipment Type</label>
                        <select class="form-select" name="type">
                            <option value="">Select Type</option>
                            @foreach ($equipmentTypeOptions as $typeOption)
                                <option value="{{ $typeOption }}">{{ ucfirst($typeOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('equipmentItemModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Equipment Modal -->
    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Edit Equipment</h2>
                <button class="modal-close" onclick="closeModal('editEquipmentModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="editEquipmentForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="editEquipmentId" name="equipment_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Equipment Name</label>
                        <input type="text" class="form-input" id="editEquipmentName" name="equipment_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-layer-group"></i> Equipment Category</label>
                        <select class="form-select" id="editEquipmentCategory" name="category">
                            <option value="">Select Category</option>
                            @foreach ($equipmentCategoryOptions as $categoryOption)
                                <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-screwdriver-wrench"></i> Equipment Type</label>
                        <select class="form-select" id="editEquipmentType" name="type">
                            <option value="">Select Type</option>
                            @foreach ($equipmentTypeOptions as $typeOption)
                                <option value="{{ $typeOption }}">{{ ucfirst($typeOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('editEquipmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Equipment Modal -->
    <div id="deleteEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-trash"></i> Delete Equipment</h2>
                <button class="modal-close" onclick="closeModal('deleteEquipmentModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="text-align:center">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:42px;color:#dc2626"></i>
                    <br><br>
                    Do you want to delete this equipment?
                    <br>
                    <strong id="deleteEquipmentName" style="word-break:break-word;"></strong>
                </p>
                <input type="hidden" id="deleteEquipmentId">
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeModal('deleteEquipmentModal')">Cancel</button>
                <button class="btn-danger" onclick="confirmDeleteEquipment()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Assign Equipment to Station Modal -->
    <div id="stationEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-warehouse"></i> Assign Equipment to Station</h2>
                <button class="modal-close" onclick="closeModal('stationEquipmentModal')"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="stationEquipmentForm" action="{{ route($resourceRoutePrefix.'.resource-management.assign-equipment') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-box"></i> Equipment</label>
                        <select class="form-select" name="equipment_id" required>
                            <option value="">Select Equipment</option>
                            @foreach ($equipments as $equip)
                                <option value="{{ $equip->equipment_id }}">{{ $equip->equipment_name }} ({{ $equip->category ?? 'Uncategorized' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-building"></i> Station</label>
                        @if($isProvincialResourceAdmin)
                            <select class="form-select" name="station_id" required>
                                <option value="">Choose station</option>
                                @foreach($activeStations as $station)
                                    <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-input" value="{{ $currentStation->station_name ?? 'No assigned station' }}" readonly>
                            <input type="hidden" name="station_id" value="{{ $currentStation->station_id ?? '' }}">
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-cubes"></i> Quantity</label>
                        <input type="number" class="form-input" name="available_quantity" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('stationEquipmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Assign Equipment</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $resourceRegistryPayload = ['stationOfficers' => $stationOfficers, 'allTeam' => $teamData, 'equipments' => $equipments, 'activeStations' => $activeStations, 'users' => $users, 'allUsers' => $systemUsers, 'barangayOfficers' => $barangayOfficers, 'apparatus' => $apparatusRecords];
    @endphp
    <script id="resourceRegistryPayload" type="application/json">@json($resourceRegistryPayload)</script>
    <script>
        window.resourcesData = {
            stationOfficers: @json($stationOfficers),
            allTeam: @json($teamData),
            equipments: @json($equipments),
            activeStations: @json($activeStations),
            users: @json($users),
            allUsers: @json($systemUsers),
            teamBaseUrl: @json($resourceBaseUrl.'/teams')
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const resourceAdminBaseUrl = @json($resourceBaseUrl);

        
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
        fetch(`${resourceAdminBaseUrl}/station-officers`, {
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
                window.refreshResourceWorkspace();
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
            window.PyroNotify?.notify(message, type);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }

            syncModalBodyState();
        }

        function openModalElement(modal) {
            if (!modal) return;

            modal.style.display = 'flex';
            modal.classList.add('active');
            syncModalBodyState();
        }

        function syncModalBodyState() {
            const hasActiveModal = Array.from(document.querySelectorAll('.modal')).some((modal) =>
                modal.classList.contains('active') && modal.style.display !== 'none'
            );

            document.body.classList.toggle('modal-open', hasActiveModal);
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

        const chiefResourceContext = {
            cityId: @json($chiefCityId ?? null),
            provinceId: @json($chiefProvinceId ?? null),
        };

        function getAllowedChiefCityId() {
            const cityId = Number(chiefResourceContext.cityId || 0);
            return Number.isFinite(cityId) && cityId > 0 ? cityId : null;
        }

        function getAllowedChiefProvinceId() {
            const provinceId = Number(chiefResourceContext.provinceId || 0);
            return Number.isFinite(provinceId) && provinceId > 0 ? provinceId : null;
        }

        function filterCitiesToChiefScope(cities) {
            const allowedCityId = getAllowedChiefCityId();

            if (!allowedCityId) {
                return Array.isArray(cities) ? cities : [];
            }

            return (Array.isArray(cities) ? cities : []).filter(city => Number(city.city_id) === allowedCityId);
        }

        function populateCityOptions(select, cities, selectedCityId = null) {
            if (!select) return;

            const filteredCities = filterCitiesToChiefScope(cities);
            select.innerHTML = '<option value="">Select City</option>';

            filteredCities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.city_id;
                option.textContent = city.city_name;

                if (selectedCityId && Number(city.city_id) === Number(selectedCityId)) {
                    option.selected = true;
                }

                select.appendChild(option);
            });

            select.disabled = filteredCities.length === 0;
        }

        function lockAddressScopeToChiefArea() {
            const allowedProvinceId = getAllowedChiefProvinceId();
            const allowedCityId = getAllowedChiefCityId();
            const regProvince = document.getElementById('regProvince');
            const regCity = document.getElementById('regCity');
            const regBarangay = document.getElementById('regBarangay');
            const editProvince = document.getElementById('editProvince');
            const editCity = document.getElementById('editCity');
            const editBarangay = document.getElementById('editBarangay');

            const lockProvince = (select) => {
                if (!select || !allowedProvinceId) return;

                const optionExists = Array.from(select.options).some(option => Number(option.value) === Number(allowedProvinceId));
                if (!optionExists) return;

                select.value = String(allowedProvinceId);
                select.disabled = true;
            };

            const lockCity = (select) => {
                if (!select || !allowedCityId) return;
                select.value = String(allowedCityId);
                select.disabled = true;
            };

            lockProvince(regProvince);
            lockProvince(editProvince);
            lockCity(regCity);
            lockCity(editCity);

            if (regBarangay && allowedCityId && regCity && Number(regCity.value || 0) !== Number(allowedCityId)) {
                regBarangay.disabled = true;
                regBarangay.innerHTML = '<option value="">Select Barangay</option>';
            }

            if (editBarangay && allowedCityId && editCity && Number(editCity.value || 0) !== Number(allowedCityId)) {
                editBarangay.disabled = true;
                editBarangay.innerHTML = '<option value="">Select Barangay</option>';
            }
        }

        function loadScopedBarangays(cityId, barangaySelect, selectedBarangayId = null) {
            const allowedCityId = getAllowedChiefCityId();

            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

            if (!cityId || (allowedCityId && Number(cityId) !== Number(allowedCityId))) {
                return Promise.resolve();
            }

            return fetch(`/api/barangays/${cityId}`)
                .then(response => response.json())
                .then(barangays => {
                    barangaySelect.disabled = false;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                    barangays.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay.barangay_id;
                        option.textContent = barangay.barangay_name;

                        if (selectedBarangayId && Number(barangay.barangay_id) === Number(selectedBarangayId)) {
                            option.selected = true;
                        }

                        barangaySelect.appendChild(option);
                    });
                });
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
            if (!user.can_manage_resources) { showToast('This account is outside your station editing scope.', 'error'); return; }
            
            currentUserId = userId;
            document.getElementById('editUserId').value = user.user_id;
            document.getElementById('editAccountCode').value = user.account_code || '--';
            document.getElementById('editFirstName').value = user.first_name || '';
            document.getElementById('editMiddleName').value = user.middle_name || '';
            document.getElementById('editLastName').value = user.last_name || '';
            document.getElementById('editContactNumber').value = user.contact_number || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editRoleId').value = user.role_id || '';
            document.getElementById('editRoleId').disabled = Boolean(user.barangay_officer);
            document.getElementById('editVerifiedStatus').value = user.email_verified_at ? 'verified' : 'pending';
            document.getElementById('editPassword').value = '';
            
            // Load address if exists
            if (user.address) {
                document.getElementById('editStreet').value = user.address.street || '';
                // Set province and cascade load city/barangay
                const cityId = user.address.barangay?.city_id;
                const provinceId = user.address.barangay?.city?.province_id;
                document.getElementById('editProvince').value = provinceId || '';
                loadCitiesForEdit(provinceId, cityId, user.address.barangay_id);
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

        function loadCitiesForEdit(provinceId, selectedCityId = null, selectedBarangayId = null) {
            const allowedProvinceId = getAllowedChiefProvinceId();
            const allowedCityId = getAllowedChiefCityId();
            const citySelect = document.getElementById('editCity');
            const barangaySelect = document.getElementById('editBarangay');

            if (!citySelect || !barangaySelect) {
                return;
            }

            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Select City</option>';
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

            if (allowedProvinceId && Number(provinceId) !== Number(allowedProvinceId)) {
                return;
            }

            fetch(`/api/cities/${provinceId}`)
                .then(response => response.json())
                .then(cities => {
                    populateCityOptions(citySelect, cities, selectedCityId);
                    lockAddressScopeToChiefArea();

                    if (allowedCityId && selectedCityId && Number(selectedCityId) !== Number(allowedCityId)) {
                        citySelect.value = '';
                        return;
                    }

                    if (selectedCityId) {
                        loadBarangaysForEdit(selectedCityId, selectedBarangayId);
                    }
                });
        }

        function loadBarangaysForEdit(cityId, selectedBarangayId = null) {
            const barangaySelect = document.getElementById('editBarangay');
            if (!barangaySelect) return;

            loadScopedBarangays(cityId, barangaySelect, selectedBarangayId);
        }

        function viewUser(userId) {
            const user = window.resourcesData.allUsers.find(u => u.user_id == userId);
            if (user) {
                const userName = `${user.first_name || ''} ${user.middle_name || ''} ${user.last_name || ''}`.replace(/\s+/g, ' ').trim() || 'Unknown User';
                const initials = userName.split(' ').filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || 'US';
                const content = `
                    <div class="resource-profile-card">
                        <div class="resource-profile-hero">
                            <div class="resource-profile-avatar">${initials}</div>
                            <div class="resource-profile-heading">
                                <div class="resource-profile-name">${userName}</div>
                                <div class="resource-profile-meta">${user.email || 'No email address'} | ${ucfirst(user.role?.role ?? 'Unknown')}</div>
                            </div>
                        </div>
                        <div class="resource-detail-section">
                            <div class="resource-detail-section-title">Account Information</div>
                            <div class="resource-detail-grid">
                                <div class="resource-detail-card"><div class="detail-label">Account Code</div><div class="detail-value">${user.account_code || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Email Address</div><div class="detail-value">${user.email || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Contact Number</div><div class="detail-value">${user.contact_number || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Role</div><div class="detail-value"><span class="status" style="background: ${(user.role?.role ?? '') == 'admin' ? '#dc2626' : ((user.role?.role ?? '') == 'firefighter' ? '#3b82f6' : ((user.role?.role ?? '') == 'chief' ? '#8b5cf6' : '#6b7280'))}; color: white;">${ucfirst(user.role?.role ?? 'Unknown')}</span></div></div>
                                <div class="resource-detail-card"><div class="detail-label">Verification</div><div class="detail-value"><span class="status" style="background: ${user.email_verified_at ? '#10b981' : '#ef4444'}; color: white;">${user.email_verified_at ? 'Verified' : 'Pending'}</span></div></div>
                                <div class="resource-detail-card"><div class="detail-label">Registered On</div><div class="detail-value">${new Date(user.created_at).toLocaleString()}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Last Modified</div><div class="detail-value">${new Date(user.updated_at).toLocaleString()}</div></div>
                            </div>
                        </div>
                    </div>
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
            const activeUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
            if (Number(userId) === activeUserId) {
                showToast('You cannot delete the currently logged-in chief account.', 'error');
                return;
            }
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserModal').style.display = 'flex';
            document.getElementById('deleteUserModal').classList.add('active');
        }

        function confirmDeleteUser() {
            const userId = document.getElementById('deleteUserId').value;
            const confirmButton = document.querySelector('#deleteUserModal .btn-danger');
            const originalLabel = confirmButton ? confirmButton.innerHTML : '';

            if (confirmButton) {
                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
            }

            fetch(`${resourceAdminBaseUrl}/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Error deleting user');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    showToast('User deleted successfully');
                    window.refreshResourceWorkspace();
                } else {
                    throw new Error(data.message || 'Error deleting user');
                }
            })
            .catch(error => {
                showToast(error.message || 'Error deleting user', 'error');
            })
            .finally(() => {
                if (confirmButton) {
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = originalLabel;
                }
                closeDeleteUserModal();
            });
        }

        // Update Officer Status Inline
        document.querySelectorAll('.status-select[data-officer-id]').forEach(select => {
            select.addEventListener('change', function() {
                const officerId = this.dataset.officerId;
                const newStatus = this.value;
                
                fetch(`${resourceAdminBaseUrl}/station-officers/${officerId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
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
                
                fetch(`${window.resourcesData.teamBaseUrl}/${teamId}/status`, {
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
function normalizeTeamMember(officer) {
    if (!officer || typeof officer !== 'object') return null;
    const user = officer.user && typeof officer.user === 'object' ? officer.user : officer;
    return {
        ...user,
        station_officer_id: officer.station_officer_id || user.station_officer_id || null,
        rank: officer.rank || user.rank || '',
        status: officer.status || user.status || ''
    };
}

function resolveTeamMemberUser(team, relationKey, fieldKey) {
    const relationValue = team?.[relationKey];
    if (relationValue && typeof relationValue === 'object' && !Array.isArray(relationValue)) {
        return normalizeTeamMember(relationValue);
    }

    const serializedRelationKey = relationKey.replace(/([A-Z])/g, '_$1').toLowerCase();
    const serializedRelationValue = team?.[serializedRelationKey];
    if (serializedRelationValue && typeof serializedRelationValue === 'object' && !Array.isArray(serializedRelationValue)) {
        return normalizeTeamMember(serializedRelationValue);
    }

    const fallbackUserId = team?.[fieldKey];
    if (!fallbackUserId) {
        return null;
    }

    const stationOfficers = window.resourcesData.stationOfficers || [];
    const officer = stationOfficers.find(item => String(item.station_officer_id) === String(fallbackUserId));
    return normalizeTeamMember(officer);
}

function escapeResourceHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatTeamMemberLabel(user, emptyLabel = '--') {
    if (!user) {
        return escapeResourceHtml(emptyLabel);
    }

    const fullName = `${user.first_name || ''} ${user.last_name || ''}`.replace(/\s+/g, ' ').trim();
    return `${escapeResourceHtml(fullName || emptyLabel)} (${escapeResourceHtml(user.account_code || '--')})`;
}

function buildEditTeamOptionLabel(user) {
    return `${user.first_name || ''} ${user.last_name || ''}`.replace(/\s+/g, ' ').trim() + ` (${user.account_code || '--'})`;
}

function getTeamMemberSelectionValue(team, relationKey, fieldKey) {
    const resolvedUser = resolveTeamMemberUser(team, relationKey, fieldKey);
    return resolvedUser?.station_officer_id || team?.[fieldKey] || '';
}

function getEligibleEditTeamUsers(defaultSelections = {}, fallbackSelectedUsers = []) {
    const stationOfficers = window.resourcesData.stationOfficers || [];
    const selectedIds = new Set(
        Object.values(defaultSelections)
            .filter(value => value !== null && value !== undefined && value !== '')
            .map(value => String(value))
    );

    const eligibleUsers = stationOfficers
        .filter(officer => {
            const user = officer.user || {};
            const roleName = String(user.role?.role || '').toLowerCase();
            const officerId = String(officer.station_officer_id || '');
            const isCurrentSelection = selectedIds.has(officerId);
            const isEligibleRole = !['admin', 'chief', 'stationadmin', 'provincial admin'].includes(roleName);
            const isAvailable = String(officer.status || '').toLowerCase() === 'available';

            return Boolean(officerId) && isEligibleRole && (isAvailable || isCurrentSelection);
        })
        .map(officer => ({
            user_id: officer.station_officer_id,
            station_officer_id: officer.station_officer_id,
            first_name: officer.user?.first_name || '',
            middle_name: officer.user?.middle_name || '',
            last_name: officer.user?.last_name || '',
            account_code: officer.user?.account_code || '',
            rank: officer.rank || '',
            status: officer.status || '',
            role: officer.user?.role || null
        }));

    fallbackSelectedUsers.forEach(user => {
        if (!user?.station_officer_id) return;

        const userId = String(user.station_officer_id);
        const alreadyIncluded = eligibleUsers.some(existingUser => String(existingUser.user_id) === userId);
        if (selectedIds.has(userId) && !alreadyIncluded) {
            eligibleUsers.push({
                user_id: user.station_officer_id,
                station_officer_id: user.station_officer_id,
                first_name: user.first_name || '',
                middle_name: user.middle_name || '',
                last_name: user.last_name || '',
                account_code: user.account_code || '',
                rank: user.rank || '',
                status: user.status || '',
                role: user.role || null
            });
        }
    });

    return eligibleUsers;
}

function renderEditTeamSelectOptions(select, users, currentSelections, currentField, placeholder) {
    if (!select) return;

    const currentValue = currentSelections[currentField] ? String(currentSelections[currentField]) : '';
    const selectedElsewhere = new Set(
        Object.entries(currentSelections)
            .filter(([field, value]) => field !== currentField && value)
            .map(([, value]) => String(value))
    );

    select.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.appendChild(placeholderOption);

    users
        .filter(user => !selectedElsewhere.has(String(user.user_id)) || String(user.user_id) === currentValue)
        .forEach(user => {
            const option = document.createElement('option');
            option.value = user.user_id;
            option.textContent = buildEditTeamOptionLabel(user);
            option.selected = String(user.user_id) === currentValue;
            select.appendChild(option);
        });
}

function syncEditTeamMemberDropdowns(defaultSelections = {}, fallbackSelectedUsers = []) {
    const currentSelections = {
        team_leader: document.getElementById('editTeamLeaderSelect')?.value || defaultSelections.team_leader || '',
        commander_driver: document.getElementById('editTeamCommanderSelect')?.value || defaultSelections.commander_driver || '',
        pumper: document.getElementById('editTeamPumperSelect')?.value || defaultSelections.pumper || '',
        firefighter_officer_1: document.getElementById('editTeamOfficer1Select')?.value || defaultSelections.firefighter_officer_1 || '',
        firefighter_officer_2: document.getElementById('editTeamOfficer2Select')?.value || defaultSelections.firefighter_officer_2 || '',
        firefighter_officer_3: document.getElementById('editTeamOfficer3Select')?.value || defaultSelections.firefighter_officer_3 || '',
        rescue_radio: document.getElementById('editTeamRescueSelect')?.value || defaultSelections.rescue_radio || ''
    };
    const eligibleUsers = getEligibleEditTeamUsers(currentSelections, fallbackSelectedUsers);

    [
        ['editTeamLeaderSelect', 'team_leader', 'Select Team Leader'],
        ['editTeamCommanderSelect', 'commander_driver', 'Select Driver'],
        ['editTeamPumperSelect', 'pumper', 'Select Pumper'],
        ['editTeamOfficer1Select', 'firefighter_officer_1', 'Select Officer 1'],
        ['editTeamOfficer2Select', 'firefighter_officer_2', 'Select Officer 2'],
        ['editTeamOfficer3Select', 'firefighter_officer_3', 'Select Officer 3'],
        ['editTeamRescueSelect', 'rescue_radio', 'Select Rescue / Radio Operator']
    ].forEach(([selectId, fieldKey, placeholder]) => {
        renderEditTeamSelectOptions(document.getElementById(selectId), eligibleUsers, currentSelections, fieldKey, placeholder);
    });
}

function attachEditTeamSelectSync() {
    [
        'editTeamLeaderSelect',
        'editTeamCommanderSelect',
        'editTeamPumperSelect',
        'editTeamOfficer1Select',
        'editTeamOfficer2Select',
        'editTeamOfficer3Select',
        'editTeamRescueSelect'
    ].forEach(selectId => {
        const select = document.getElementById(selectId);
        if (!select || select.dataset.syncBound === 'true') return;

        select.addEventListener('change', syncEditTeamMemberDropdowns);
        select.dataset.syncBound = 'true';
    });
}

        function openEditTeamModal(teamId) {
    const team = window.resourcesData.allTeam.find(t => t.team_id == teamId);
    if (!team) return;
    
    currentTeamId = teamId;
    const teamLeader = resolveTeamMemberUser(team, 'teamLeader', 'team_leader');
    const commander = resolveTeamMemberUser(team, 'commander', 'commander_driver');
    const pumper = resolveTeamMemberUser(team, 'pumpers', 'pumper');
    const rescueRadio = resolveTeamMemberUser(team, 'rescueRadio', 'rescue_radio');
    const officer1 = resolveTeamMemberUser(team, 'officer1', 'firefighter_officer_1');
    const officer2 = resolveTeamMemberUser(team, 'officer2', 'firefighter_officer_2');
    const officer3 = resolveTeamMemberUser(team, 'officer3', 'firefighter_officer_3');
    const teamLeaderName = `${teamLeader?.first_name || ''} ${teamLeader?.last_name || ''}`.replace(/\s+/g, ' ').trim() || '--';
    const teamInitials = teamLeaderName.split(' ').filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || 'TM';
    
    let content = `
        <form id="editTeamFormContent">
            <input type="hidden" name="team_id" value="${team.team_id}">
            <div class="resource-profile-card">
                <div class="resource-profile-hero">
                    <div class="resource-profile-avatar">${teamInitials}</div>
                    <div class="resource-profile-heading">
                        <div class="resource-profile-name">Edit Team #${team.team_id}</div>
                        <div class="resource-profile-meta">${escapeResourceHtml(teamLeaderName)} | ${escapeResourceHtml(team.status || 'Unknown')} status</div>
                    </div>
                </div>
                <div class="resource-detail-section">
                    <div class="resource-detail-section-title">Core Team Roles</div>
                    <div class="resource-detail-grid">
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user-tie"></i> Team Leader</label>
                                <select class="form-select" name="team_leader" id="editTeamLeaderSelect"></select>
                            </div>
                        </div>
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-truck-fast"></i> Driver / Commander</label>
                                <select class="form-select" name="commander_driver" id="editTeamCommanderSelect"></select>
                            </div>
                        </div>
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-water"></i> Pumper</label>
                                <select class="form-select" name="pumper" id="editTeamPumperSelect"></select>
                            </div>
                        </div>
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-radio"></i> Rescue / Radio Operator</label>
                                <select class="form-select" name="rescue_radio" id="editTeamRescueSelect"></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="resource-detail-section">
                    <div class="resource-detail-section-title">Assigned Officers</div>
                    <div class="resource-detail-grid">
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 1</label>
                                <select class="form-select" name="firefighter_officer_1" id="editTeamOfficer1Select"></select>
                            </div>
                        </div>
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 2</label>
                                <select class="form-select" name="firefighter_officer_2" id="editTeamOfficer2Select"></select>
                            </div>
                        </div>
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user"></i> Officer 3</label>
                                <select class="form-select" name="firefighter_officer_3" id="editTeamOfficer3Select"></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="resource-detail-section">
                    <div class="resource-detail-section-title">Team Status</div>
                    <div class="resource-detail-grid">
                        <div class="resource-edit-card">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-flag"></i> Status</label>
                                <select class="form-select" name="status" id="editTeamStatusSelect">
                                    <option value="available" ${team.status == 'available' ? 'selected' : ''}>Available</option>
                                    <option value="responding" ${team.status == 'responding' ? 'selected' : ''}>On Scene</option>
                                    <option value="off" ${team.status == 'off' ? 'selected' : ''}>Off Duty</option>
                                </select>
                            </div>
                        </div>
                        <div class="resource-detail-card">
                            <div class="detail-label">Current Team Leader</div>
                            <div class="detail-value">${formatTeamMemberLabel(teamLeader, '--')}</div>
                        </div>
                        <div class="resource-detail-card">
                            <div class="detail-label">Current Driver</div>
                            <div class="detail-value">${formatTeamMemberLabel(commander, '--')}</div>
                        </div>
                        <div class="resource-detail-card">
                            <div class="detail-label">Current Pumper</div>
                            <div class="detail-value">${formatTeamMemberLabel(pumper, '--')}</div>
                        </div>
                        <div class="resource-detail-card">
                            <div class="detail-label">Current Rescue / Radio</div>
                            <div class="detail-value">${formatTeamMemberLabel(rescueRadio, '--')}</div>
                        </div>
                        <div class="resource-detail-card">
                            <div class="detail-label">Current Officers</div>
                            <div class="detail-value">${[
                                formatTeamMemberLabel(officer1, '--'),
                                formatTeamMemberLabel(officer2, '--'),
                                formatTeamMemberLabel(officer3, '--')
                            ].join('<br>')}</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('editTeamContent').innerHTML = content;
    const defaultSelections = {
        team_leader: getTeamMemberSelectionValue(team, 'teamLeader', 'team_leader'),
        commander_driver: getTeamMemberSelectionValue(team, 'commander', 'commander_driver'),
        pumper: getTeamMemberSelectionValue(team, 'pumpers', 'pumper'),
        firefighter_officer_1: getTeamMemberSelectionValue(team, 'officer1', 'firefighter_officer_1'),
        firefighter_officer_2: getTeamMemberSelectionValue(team, 'officer2', 'firefighter_officer_2'),
        firefighter_officer_3: getTeamMemberSelectionValue(team, 'officer3', 'firefighter_officer_3'),
        rescue_radio: getTeamMemberSelectionValue(team, 'rescueRadio', 'rescue_radio')
    };
    const fallbackSelectedUsers = [
        resolveTeamMemberUser(team, 'teamLeader', 'team_leader'),
        resolveTeamMemberUser(team, 'commander', 'commander_driver'),
        resolveTeamMemberUser(team, 'pumpers', 'pumper'),
        resolveTeamMemberUser(team, 'officer1', 'firefighter_officer_1'),
        resolveTeamMemberUser(team, 'officer2', 'firefighter_officer_2'),
        resolveTeamMemberUser(team, 'officer3', 'firefighter_officer_3'),
        resolveTeamMemberUser(team, 'rescueRadio', 'rescue_radio')
    ].filter(Boolean);

    syncEditTeamMemberDropdowns(defaultSelections, fallbackSelectedUsers);
    attachEditTeamSelectSync();
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
    
    fetch(`${window.resourcesData.teamBaseUrl}/${currentTeamId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-HTTP-Method-Override': 'PUT',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Team updated successfully');
            window.refreshResourceWorkspace();
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
        formData.get('team_leader'),
        formData.get('commander_driver'),
        formData.get('pumper'),
        formData.get('firefighter_officer_1'),
        formData.get('firefighter_officer_2'),
        formData.get('firefighter_officer_3'),
        formData.get('rescue_radio')
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
                const officerName = `${officer.user?.first_name || ''} ${officer.user?.middle_name || ''} ${officer.user?.last_name || ''}`.replace(/\s+/g, ' ').trim() || 'Unknown Officer';
                const initials = officerName.split(' ').filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || 'OF';
                const content = `
                    <div class="resource-profile-card">
                        <div class="resource-profile-hero">
                            <div class="resource-profile-avatar">${initials}</div>
                            <div class="resource-profile-heading">
                                <div class="resource-profile-name">${officerName}</div>
                                <div class="resource-profile-meta">${officer.rank || 'No rank assigned'} | ${officer.station?.station_name || 'No station assigned'}</div>
                            </div>
                        </div>
                        <div class="resource-detail-section">
                            <div class="resource-detail-section-title">Personnel Information</div>
                            <div class="resource-detail-grid">
                                <div class="resource-detail-card"><div class="detail-label">Rank</div><div class="detail-value">${officer.rank || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Station</div><div class="detail-value">${officer.station?.station_name || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Status</div><div class="detail-value"><span class="status ${officer.status?.toLowerCase()}">${officer.status || 'Unknown'}</span></div></div>
                                <div class="resource-detail-card"><div class="detail-label">Email</div><div class="detail-value">${officer.user?.email || '--'}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Contact</div><div class="detail-value">${officer.user?.contact_number || '--'}</div></div>
                            </div>
                        </div>
                    </div>
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
                const teamLeader = resolveTeamMemberUser(team, 'teamLeader', 'team_leader');
                const commander = resolveTeamMemberUser(team, 'commander', 'commander_driver');
                const pumper = resolveTeamMemberUser(team, 'pumpers', 'pumper');
                const rescueRadio = resolveTeamMemberUser(team, 'rescueRadio', 'rescue_radio');
                const officer1 = resolveTeamMemberUser(team, 'officer1', 'firefighter_officer_1');
                const officer2 = resolveTeamMemberUser(team, 'officer2', 'firefighter_officer_2');
                const officer3 = resolveTeamMemberUser(team, 'officer3', 'firefighter_officer_3');
                const teamLeaderName = `${teamLeader?.first_name || ''} ${teamLeader?.last_name || ''}`.replace(/\s+/g, ' ').trim() || '--';
                const teamInitials = teamLeaderName.split(' ').filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || 'TM';
                const content = `
                    <div class="resource-profile-card">
                        <div class="resource-profile-hero">
                            <div class="resource-profile-avatar">${teamInitials}</div>
                            <div class="resource-profile-heading">
                                <div class="resource-profile-name">Team #${team.team_id}</div>
                                <div class="resource-profile-meta">${escapeResourceHtml(teamLeaderName)} | ${escapeResourceHtml(team.status || 'Unknown')} status</div>
                            </div>
                        </div>
                        <div class="resource-detail-section">
                            <div class="resource-detail-section-title">Team Overview</div>
                            <div class="resource-detail-grid">
                                <div class="resource-detail-card"><div class="detail-label">Status</div><div class="detail-value"><span class="status ${escapeResourceHtml(team.status?.toLowerCase())}">${escapeResourceHtml(team.status || 'Unknown')}</span></div></div>
                                <div class="resource-detail-card"><div class="detail-label">Team Leader</div><div class="detail-value">${formatTeamMemberLabel(teamLeader, '--')}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Driver</div><div class="detail-value">${formatTeamMemberLabel(commander, '--')}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Pumper</div><div class="detail-value">${formatTeamMemberLabel(pumper, '--')}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Rescue / Radio</div><div class="detail-value">${formatTeamMemberLabel(rescueRadio, '--')}</div></div>
                            </div>
                        </div>
                        <div class="resource-detail-section">
                            <div class="resource-detail-section-title">Assigned Officers</div>
                            <div class="resource-detail-grid">
                                <div class="resource-detail-card"><div class="detail-label">Officer 1</div><div class="detail-value">${formatTeamMemberLabel(officer1, '--')}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Officer 2</div><div class="detail-value">${formatTeamMemberLabel(officer2, '--')}</div></div>
                                <div class="resource-detail-card"><div class="detail-label">Officer 3</div><div class="detail-value">${formatTeamMemberLabel(officer3, '--')}</div></div>
                            </div>
                        </div>
                    </div>
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
        //         document.getElementById('editBfpId').value = officer.user?.user_id || '--';
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
            const officer = window.resourcesData.stationOfficers.find(o => o.station_officer_id == id);
            const activeUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
            if (Number(officer?.user_id || officer?.user?.user_id || 0) === activeUserId) {
                showToast('You cannot delete the currently logged-in chief personnel record.', 'error');
                return;
            }
            document.getElementById('deleteOfficerId').value = id; 
            document.getElementById('deleteOfficerModal').style.display = 'flex'; 
            document.getElementById('deleteOfficerModal').classList.add('active'); 
        }
        
        function editEquipment(id) {
            const equipment = (window.resourcesData.equipments || []).find(item => Number(item.equipment_id) === Number(id));
            if (!equipment) {
                showToast('Equipment record not found.', 'error');
                return;
            }

            document.getElementById('editEquipmentId').value = equipment.equipment_id;
            document.getElementById('editEquipmentName').value = equipment.equipment_name || '';
            document.getElementById('editEquipmentCategory').value = equipment.category || '';
            document.getElementById('editEquipmentType').value = equipment.type || '';
            document.getElementById('editEquipmentForm').action = `${resourceAdminBaseUrl}/equipment/${equipment.equipment_id}`;
            document.getElementById('editEquipmentModal').style.display = 'flex';
            document.getElementById('editEquipmentModal').classList.add('active');
        }
        
        function deleteEquipment(id) {
            const equipment = (window.resourcesData.equipments || []).find(item => Number(item.equipment_id) === Number(id));
            if (!equipment) {
                showToast('Equipment record not found.', 'error');
                return;
            }

            document.getElementById('deleteEquipmentId').value = equipment.equipment_id;
            document.getElementById('deleteEquipmentName').textContent = equipment.equipment_name || 'Selected equipment';
            document.getElementById('deleteEquipmentModal').style.display = 'flex';
            document.getElementById('deleteEquipmentModal').classList.add('active');
        }

        function confirmDeleteEquipment() {
            const id = document.getElementById('deleteEquipmentId').value;
            const deleteButton = document.querySelector('#deleteEquipmentModal .btn-danger');
            const originalLabel = deleteButton ? deleteButton.innerHTML : '';

            if (deleteButton) {
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
            }

            fetch(`${resourceAdminBaseUrl}/equipment/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to delete equipment.');
                }
                return data;
            })
            .then(data => {
                showToast(data.message || 'Equipment deleted successfully.', 'success');
                closeModal('deleteEquipmentModal');
                window.refreshResourceWorkspace();
            })
            .catch(error => {
                showToast(error.message || 'Unable to delete equipment.', 'error');
            })
            .finally(() => {
                if (deleteButton) {
                    deleteButton.disabled = false;
                    deleteButton.innerHTML = originalLabel;
                }
            });
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
            fetch(`${resourceAdminBaseUrl}/station-officer/${id}`, {
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
                    window.refreshResourceWorkspace();
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
    
    fetch(`${resourceAdminBaseUrl}/users/${userId}`, {
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
        return fetch(`${resourceAdminBaseUrl}/officers/${officerId}`, {
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
            window.refreshResourceWorkspace();
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
            
            fetch(`${resourceAdminBaseUrl}/users/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(userData)
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to update user.');
                return data;
            })
            .then(data => {
                if (data.success) {
                    closeEditUserModal();
                    showToast('User updated successfully');
                    window.refreshResourceWorkspace();
                } else {
                    showToast(data.message || 'Error updating user', 'error');
                }
            })
            .catch(error => {
                showToast(error.message || 'Error updating user', 'error');
            });
        });

        document.getElementById('eform')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            fetch(this.action, { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(async r => {
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Unable to add equipment.');
                return d;
            })
            .then(d => { 
                showToast(d.message || 'Equipment added successfully.', 'success');
                closeModal('equipmentItemModal');
                window.refreshResourceWorkspace();
            })
            .catch(error => {
                showToast(error.message || 'Unable to add equipment.', 'error');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            });
        });

        

        document.getElementById('equipmentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            fetch(this.action, { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(async r => {
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Unable to add category.');
                return d;
            })
            .then(d => { 
                showToast(d.message || 'Category added successfully.', 'success');
                closeModal('equipmentCategoryModal');
                window.refreshResourceWorkspace();
            })
            .catch(error => {
                showToast(error.message || 'Unable to add category.', 'error');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            });
        });

        document.getElementById('stationEquipmentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Assigning...';
            }

            fetch(this.action, { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                } 
            })
            .then(async r => {
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Unable to assign equipment.');
                return d;
            })
            .then(d => { 
                showToast(d.message || 'Equipment assigned successfully.', 'success');
                closeModal('stationEquipmentModal');
                window.refreshResourceWorkspace();
            })
            .catch(error => {
                showToast(error.message || 'Unable to assign equipment.', 'error');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            });
        });

        document.getElementById('editEquipmentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
            }

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PUT'
                }
            })
            .then(async r => {
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Unable to update equipment.');
                return d;
            })
            .then(d => {
                showToast(d.message || 'Equipment updated successfully.', 'success');
                closeModal('editEquipmentModal');
                window.refreshResourceWorkspace();
            })
            .catch(error => {
                showToast(error.message || 'Unable to update equipment.', 'error');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            });
        });

        document.getElementById('registerUserForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering...';
            }

            fetch(this.action, { 
                method: 'POST', 
                body: new FormData(this), 
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                } 
            })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) {
                    throw new Error(data.message || 'Error registering user');
                }
                return data;
            })
            .then(d => { 
                if (d.success) { 
                    const successMessage = d.generated_password
                        ? `User registered. Temporary password: ${d.generated_password}`
                        : 'User registered successfully';
                    showToast(successMessage);
                    form.reset();
                    toggleRegisterUserRankField();
                    closeModal('registerUserModal');
                    window.refreshResourceWorkspace();
                } else {
                    throw new Error(d.message || 'Error registering user');
                } 
            })
            .catch(error => {
                showToast(error.message || 'Error registering user', 'error');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            });
        });

        function toggleRegisterUserRankField() {
            const roleSelect = document.getElementById('registerUserRole');
            const rankGroup = document.getElementById('registerUserRankGroup');
            const rankInput = document.getElementById('registerUserRank');
            const barangayPositionGroup = document.getElementById('registerBarangayOfficerPositionGroup');
            const barangayPositionInput = document.getElementById('registerBarangayOfficerPosition');
            const barangayStatusGroup = document.getElementById('registerBarangayOfficerStatusGroup');
            const barangayStatusInput = document.getElementById('registerBarangayOfficerStatus');
            const barangayHint = document.getElementById('registerBarangayOfficerBarangayHint');

            if (!roleSelect || !rankGroup || !rankInput || !barangayPositionGroup || !barangayPositionInput || !barangayStatusGroup || !barangayStatusInput || !barangayHint) return;

            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const roleName = (selectedOption?.dataset?.roleName || '').toLowerCase();
            const needsRank = ['firefighter', 'chief', 'stationadmin'].includes(roleName);
            const isBarangayOfficer = ['barangay officer', 'barangay_officer'].includes(roleName);

            rankGroup.style.display = needsRank ? '' : 'none';
            rankInput.required = needsRank;

            if (!needsRank) {
                rankInput.value = '';
            }

            barangayPositionGroup.style.display = isBarangayOfficer ? '' : 'none';
            barangayStatusGroup.style.display = isBarangayOfficer ? '' : 'none';
            barangayHint.style.display = isBarangayOfficer ? 'block' : 'none';
            barangayPositionInput.required = isBarangayOfficer;

            if (!isBarangayOfficer) {
                barangayPositionInput.value = '';
                barangayStatusInput.value = 'active';
            }
        }



        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('searchInput');
            const status = document.getElementById('statusFilter');
            const station = document.getElementById('stationFilter');
            const refresh = document.getElementById('refreshBtn');
            const roleFilter = document.getElementById('userRoleFilter');
            const sortFilter = document.getElementById('userSortFilter');
            const registerUserRole = document.getElementById('registerUserRole');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            let resourceFilterTimer = null;
            const resourceSectionIds = [
                'boundaryResourceSection',
                'personnelResourceSection',
                'teamsResourceSection',
                'usersResourceSection',
                'equipmentResourceSection',
                'barangayOfficerResourceSection',
                'apparatusResourceSection'
            ];

            const applyResourceTextLimits = (scope = document) => {
                scope.querySelectorAll('.resource-table-compact .station-table td:not(.actions)').forEach((cell) => {
                    const text = cell.textContent.replace(/\s+/g, ' ').trim();
                    if (text) cell.title = text;
                });
            };

            const bindDynamicStatusControls = () => {
                document.querySelectorAll('.status-select[data-officer-id]').forEach((select) => {
                    if (select.dataset.dynamicBound === 'true') return;
                    select.dataset.dynamicBound = 'true';
                    select.addEventListener('change', function () {
                        const officerId = this.dataset.officerId;
                        fetch(`${resourceAdminBaseUrl}/station-officers/${officerId}/status`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ status: this.value })
                        }).then((response) => response.json()).then((data) => {
                            if (!data.success) throw new Error(data.message || 'Unable to update status.');
                            updateSelectStyle(this, this.value);
                            showToast('Status updated successfully');
                        }).catch((error) => showToast(error.message || 'Unable to update status.', 'error'));
                    });
                });

                document.querySelectorAll('.status-select[data-team-id]').forEach((select) => {
                    if (select.dataset.dynamicBound === 'true') return;
                    select.dataset.dynamicBound = 'true';
                    select.addEventListener('change', function () {
                        fetch(`${window.resourcesData.teamBaseUrl}/${this.dataset.teamId}/status`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ status: this.value })
                        }).then((response) => response.json()).then((data) => {
                            if (!data.success) throw new Error(data.message || 'Unable to update team status.');
                            updateSelectStyle(this, this.value);
                            showToast('Team status updated successfully');
                        }).catch((error) => showToast(error.message || 'Unable to update team status.', 'error'));
                    });
                });
            };

            let resourceLoadSequence = 0;
            const loadResourceTables = async (targetUrl = window.location.href, resetPages = false) => {
                const sequence = ++resourceLoadSequence;
                const url = new URL(targetUrl, window.location.origin);
                url.searchParams.set('resource_search', search?.value.trim() || '');
                url.searchParams.set('resource_status', status?.value || '');
                url.searchParams.set('user_role', roleFilter?.value || '');
                url.searchParams.set('user_sort', sortFilter?.value || 'name_asc');
                if (resetPages) {
                    ['personnel_page', 'teams_page', 'users_page', 'equipment_page', 'boundaries_page', 'barangay_officers_page', 'apparatus_page'].forEach((key) => url.searchParams.delete(key));
                }

                resourceSectionIds.forEach((id) => document.getElementById(id)?.setAttribute('aria-busy', 'true'));
                try {
                    const fetchUrl = new URL(url);
                    fetchUrl.searchParams.set('_resource_refresh', '1');
                    const response = await fetch(fetchUrl.toString(), { cache: 'no-store', headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!response.ok) throw new Error('Unable to load resource records.');
                    const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
                    if (sequence !== resourceLoadSequence) return;
                    const registry = documentCopy.getElementById('resourceRegistryPayload');
                    if (!registry) throw new Error('Your session may have expired. Please sign in again.');
                    const freshData = JSON.parse(registry.textContent);
                    Object.assign(window.resourcesData, freshData);
                    barangayOfficerRegistry.splice(0, barangayOfficerRegistry.length, ...freshData.barangayOfficers);
                    apparatusRegistry.splice(0, apparatusRegistry.length, ...freshData.apparatus);
                    resourceSectionIds.forEach((id) => {
                        const current = document.getElementById(id);
                        const replacement = documentCopy.getElementById(id);
                        if (current && replacement) {
                            ['userRoleFilter', 'userSortFilter'].forEach(controlId => {
                                const control = current.querySelector('#' + controlId);
                                const placeholder = replacement.querySelector('#' + controlId);
                                if (control && placeholder) placeholder.replaceWith(control);
                            });
                            current.replaceChildren(...replacement.childNodes);
                        }
                    });
                    window.history.replaceState({}, '', url.toString());
                    ['.chief-theme-summary-grid', '.stats-grid'].forEach(selector => {
                        const current = document.querySelector('.management-container ' + selector);
                        const replacement = documentCopy.querySelector('.management-container ' + selector);
                        if (current && replacement) current.innerHTML = replacement.innerHTML;
                    });
                    document.querySelectorAll('.modal select[id]').forEach(select => {
                        const replacement = documentCopy.getElementById(select.id);
                        if (!replacement || select.closest('.modal.active')) return;
                        const value = select.value;
                        select.innerHTML = replacement.innerHTML;
                        select.value = value;
                    });
                    bindDynamicStatusControls();
                    applyResourceTextLimits();
                } catch (error) {
                    showToast(error.message || 'Unable to load resource records.', 'error');
                } finally {
                    if (sequence === resourceLoadSequence) resourceSectionIds.forEach((id) => document.getElementById(id)?.removeAttribute('aria-busy'));
                }
            };
            window.refreshResourceWorkspace = () => {
                document.querySelectorAll('.modal.active').forEach(modal => closeModal(modal.id));
                return loadResourceTables(window.location.href);
            };
            const liveTeamTimer = window.setInterval(() => {
                if (document.hidden || document.querySelector('.modal.active, [aria-busy="true"]')) return;
                if (resourceSectionIds.some(id => document.getElementById(id)?.contains(document.activeElement))) return;
                loadResourceTables(window.location.href);
            }, 30000);
            window.addEventListener('pagehide', () => window.clearInterval(liveTeamTimer), { once: true });

            if (search) search.addEventListener('input', () => {
                clearTimeout(resourceFilterTimer);
                resourceFilterTimer = setTimeout(() => loadResourceTables(window.location.href, true), 300);
            });
            if (status) status.addEventListener('change', () => loadResourceTables(window.location.href, true));
            if (station) station.addEventListener('change', filterAll);
            if (roleFilter) roleFilter.addEventListener('change', () => loadResourceTables(window.location.href, true));
            if (sortFilter) sortFilter.addEventListener('change', () => loadResourceTables(window.location.href, true));
            if (registerUserRole) registerUserRole.addEventListener('change', toggleRegisterUserRankField);
            if (refresh) refresh.addEventListener('click', () => loadResourceTables(window.location.href));

            document.addEventListener('click', (event) => {
                const paginationLink = event.target.closest('.resource-pagination a');
                if (!paginationLink) return;
                event.preventDefault();
                loadResourceTables(paginationLink.href);
            });

            toggleRegisterUserRankField();
            applyResourceTextLimits();

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
                        openModalElement(modal);
                        dropdown.classList.remove('show');
                    });
                });
            }

            document.querySelectorAll('.modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal.id);
                    }
                });

                const observer = new MutationObserver(() => syncModalBodyState());
                observer.observe(modal, {
                    attributes: true,
                    attributeFilter: ['class', 'style'],
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;

                const activeModal = Array.from(document.querySelectorAll('.modal.active'))
                    .find((modal) => modal.style.display !== 'none');

                if (activeModal) {
                    closeModal(activeModal.id);
                }
            });

            syncModalBodyState();

            // Export button
            document.getElementById('exportBtn')?.addEventListener('click', () => showToast('Export feature - implement CSV export', 'info'));

            // Sidebar
            const openSidebar = document.getElementById('openSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.getElementById('sidebar');
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

            lockAddressScopeToChiefArea();
            
            if (regProvince) {
                regProvince.addEventListener('change', function() {
                    const provinceId = this.value;
                    const allowedProvinceId = getAllowedChiefProvinceId();
                    regCity.disabled = true;
                    regCity.innerHTML = '<option value="">Select City</option>';
                    regBarangay.disabled = true;
                    regBarangay.innerHTML = '<option value="">Select Barangay</option>';
                    
                    if (provinceId && (!allowedProvinceId || Number(provinceId) === Number(allowedProvinceId))) {
                        fetch(`/api/cities/${provinceId}`)
                            .then(response => response.json())
                            .then(cities => {
                                populateCityOptions(regCity, cities);
                                lockAddressScopeToChiefArea();

                                const allowedCityId = getAllowedChiefCityId();
                                if (allowedCityId) {
                                    loadScopedBarangays(allowedCityId, regBarangay);
                                }
                            });
                    }
                });
            }
            
            if (regCity) {
                regCity.addEventListener('change', function() {
                    const cityId = this.value;
                    loadScopedBarangays(cityId, regBarangay);
                });
            }

            // Province/City/Barangay cascade for edit modal
            const editProvince = document.getElementById('editProvince');
            const editCity = document.getElementById('editCity');
            const editBarangay = document.getElementById('editBarangay');
            
            if (editProvince) {
                editProvince.addEventListener('change', function() {
                    const provinceId = this.value;
                    loadCitiesForEdit(provinceId);
                });
            }
            
            if (editCity) {
                editCity.addEventListener('change', function() {
                    const cityId = this.value;
                    loadScopedBarangays(cityId, editBarangay);
                });
            }

            if (regProvince && getAllowedChiefProvinceId()) {
                regProvince.dispatchEvent(new Event('change'));
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
// function loadOfficersData() {
//     const stationOfficers = window.resourcesData.stationOfficers || [];
//     const existingTeams = window.resourcesData.allTeam || [];
    
//     console.log('existing teams: ', existingTeams);
//     console.log('existing teams: ', stationOfficers);

//     // Create a Set of user_ids that are already in any team
//     const officersInTeams = new Set();
//     existingTeams.forEach(team => {
//         if (team.team_leader) officersInTeams.add(parseInt(team.team_leader.user_id));
//         if (team.commander_driver) officersInTeams.add(parseInt(team.commander_driver));
//         if (team.pumper) officersInTeams.add(parseInt(team.pumper));
//         if (team.firefighter_officer_1) officersInTeams.add(parseInt(team.firefighter_officer_1));
//         if (team.firefighter_officer_2) officersInTeams.add(parseInt(team.firefighter_officer_2));
//         if (team.firefighter_officer_3) officersInTeams.add(parseInt(team.firefighter_officer_3));
//         if (team.rescue_radio) officersInTeams.add(parseInt(team.rescue_radio));
//     });
    
//     console.log('Officers already in teams:', Array.from(officersInTeams));
    
//     // Clear existing data
//     officersByStation = {};
    
//     // Group officers by station_id
//     stationOfficers.forEach(officer => {
//         const stationId = officer.station_id;
//         if (!stationId) return;
        
//         if (!officersByStation[stationId]) {
//             officersByStation[stationId] = [];
//         }
        
//         const userData = officer.user || {};
//         const userId = officer.user_id || userData.user_id;
        
//         // Skip officers already in a team
//         if (officersInTeams.has(parseInt(userId))) {
//             console.log(`Skipping officer ${userId} - already in a team`);
//             return;
//         }
        
//         const officerData = {
//             user_id: userId,
//             first_name: userData.first_name || '',
//             middle_name: userData.middle_name || '',
//             last_name: userData.last_name || '',
//             account_code: userData.account_code || '',
//             rank: officer.rank || '',
//             full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
//             display_name: `${userData.first_name || ''} ${userData.last_name || ''} (${userData.account_code || '--'}) - ${officer.rank || 'No Rank'}`
//         };
        
//         if (officerData.user_id) {
//             officersByStation[stationId].push(officerData);
//         }
//     });
    
// }
// Fixed loadOfficersData function
function loadOfficersData() {
    const stationOfficers = window.resourcesData.stationOfficers || [];
    const existingTeams = window.resourcesData.allTeam || [];
    
    // Create a Set of user_ids that are already in any team
    const officersInTeams = new Set();
    existingTeams.forEach(team => {
        // Use the correct property names from your team object
        if (team.team_leader) officersInTeams.add(parseInt(team.team_leader));
        if (team.commander_driver) officersInTeams.add(parseInt(team.commander_driver));
        if (team.pumper) officersInTeams.add(parseInt(team.pumper));
        if (team.firefighter_officer_1) officersInTeams.add(parseInt(team.firefighter_officer_1));
        if (team.firefighter_officer_2) officersInTeams.add(parseInt(team.firefighter_officer_2));
        if (team.firefighter_officer_3) officersInTeams.add(parseInt(team.firefighter_officer_3));
        if (team.rescue_radio) officersInTeams.add(parseInt(team.rescue_radio));
    });
    
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
        const officerId = officer.station_officer_id;
        const roleName = String(userData.role?.role || '').toLowerCase();
        const excludedRoles = new Set(['admin', 'chief', 'stationadmin']);

        if (excludedRoles.has(roleName)) {
            return;
        }

        if (String(officer.status || '').toLowerCase() !== 'available') {
            return;
        }
        
        // Skip officers already in a team
        if (officersInTeams.has(parseInt(officerId))) {
            return;
        }
        
        const officerData = {
            user_id: officerId,
            station_officer_id: officerId,
            first_name: userData.first_name || '',
            middle_name: userData.middle_name || '',
            last_name: userData.last_name || '',
            account_code: userData.account_code || '',
            rank: officer.rank || '',
            full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
            display_name: `${userData.first_name || ''} ${userData.last_name || ''} (${userData.account_code || '--'})`
        };
        
        if (officerData.user_id) {
            officersByStation[stationId].push(officerData);
        }
    });
    
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
// function populateStationOfficers(stationId) {
//     console.log('Populating data for station:', stationId);
//     const officers = officersByStation[stationId] || [];
    
//     if (officers.length === 0) {
//         console.log('No available officers found for this station');
//         showToast('No available officers for this station. Some officers may already be assigned to other teams.', 'warning');
//         resetTeamForm();
//         return;
//     }
    
//     // Reset current selections
//     currentTeamOfficers = {
//         team_leader_id: null,
//         driver_id: null,
//         pumper_id: null,
//         officer1_id: null,
//         officer2_id: null,
//         officer3_id: null,
//         rescue_operator_id: null
//     };
    
//     // Update all selects
//     updateAllSelects(stationId, currentTeamOfficers);
    
//     console.log('Populated successfully');
// }
// Fixed populateStationOfficers function
function populateStationOfficers(stationId) {
    console.log('Populating data for station:', stationId);
    const officers = officersByStation[stationId] || [];
    
    console.log('Officers found:', officers.length, officers);
    
    if (officers.length === 0) {
        console.log('No available officers found for this station');
        resetTeamForm();
        return;
    }
    
    // Update each select individually
    const selectMappings = [
        { id: 'teamLeaderSelect', field: 'team_leader', label: 'Team Leader' },
        { id: 'driverSelect', field: 'commander_driver', label: 'Driver' },
        { id: 'pumperSelect', field: 'pumper', label: 'Pumper' },
        { id: 'officer1Select', field: 'firefighter_officer_1', label: 'Officer 1' },
        { id: 'officer2Select', field: 'firefighter_officer_2', label: 'Officer 2' },
        { id: 'officer3Select', field: 'firefighter_officer_3', label: 'Officer 3' },
        { id: 'rescueOperatorSelect', field: 'rescue_radio', label: 'Rescue Operator' }
    ];
    
    selectMappings.forEach(mapping => {
        const select = document.getElementById(mapping.id);
        if (select) {
            // Clear and populate
            select.innerHTML = `<option value="">Select ${mapping.label}</option>`;
            
            officers.forEach(officer => {
                const option = document.createElement('option');
                option.value = officer.user_id;
                option.textContent = officer.display_name;
                select.appendChild(option);
            });
            
            // Enable the select
            select.disabled = false;
        }
    });
    
    // Reset current selections
    currentTeamOfficers = {
        team_leader: null,
        commander_driver: null,
        pumper: null,
        firefighter_officer_1: null,
        firefighter_officer_2: null,
        firefighter_officer_3: null,
        rescue_radio: null
    };
    
    // Enable submit button check
    checkRequiredFields();
    
    console.log('Populated successfully');
}
// Handle selection change for any dropdown
// function onOfficerSelectChange(fieldName, selectElement) {
//     const stationId = document.getElementById('teamStationSelect')?.value;
//     if (!stationId) return;
    
//     // Update current selections
//     currentTeamOfficers[fieldName] = selectElement.value || null;
    
//     // Update all selects with new exclusions
//     updateAllSelects(stationId, currentTeamOfficers);
    
//     // Check if required fields are filled
//     checkRequiredFields();
// }
// Fixed onOfficerSelectChange function
function onOfficerSelectChange(fieldName, selectElement) {
    const stationId = document.getElementById('teamStationSelect')?.value;
    if (!stationId) return;
    
    // Update current selections
    currentTeamOfficers[fieldName] = selectElement.value || null;
    
    // Get all selected values to exclude
    const selectedValues = [];
    for (const [key, value] of Object.entries(currentTeamOfficers)) {
        if (value && value !== '') {
            selectedValues.push(parseInt(value));
        }
    }
    
    // Update each select to exclude already selected officers
    const selectMappings = [
        { id: 'teamLeaderSelect', field: 'team_leader', label: 'Team Leader' },
        { id: 'driverSelect', field: 'commander_driver', label: 'Driver' },
        { id: 'pumperSelect', field: 'pumper', label: 'Pumper' },
        { id: 'officer1Select', field: 'firefighter_officer_1', label: 'Officer 1' },
        { id: 'officer2Select', field: 'firefighter_officer_2', label: 'Officer 2' },
        { id: 'officer3Select', field: 'firefighter_officer_3', label: 'Officer 3' },
        { id: 'rescueOperatorSelect', field: 'rescue_radio', label: 'Rescue Operator' }
    ];
    
    const officers = officersByStation[stationId] || [];
    
    selectMappings.forEach(mapping => {
        const select = document.getElementById(mapping.id);
        if (!select) return;
        
        const currentValue = currentTeamOfficers[mapping.field];
        
        // Clear and repopulate
        select.innerHTML = `<option value="">Select ${mapping.label}</option>`;
        
        officers.forEach(officer => {
            const officerId = parseInt(officer.user_id);
            // Skip if this officer is selected in another field and it's not the current field's value
            if (selectedValues.includes(officerId) && officerId !== parseInt(currentValue)) {
                return;
            }
            
            const option = document.createElement('option');
            option.value = officer.user_id;
            option.textContent = officer.display_name;
            if (currentValue && parseInt(currentValue) === officerId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        // Disable if no options available
        select.disabled = select.options.length <= 1;
    });
    
    // Check if required fields are filled
    checkRequiredFields();
}
// Initialize event listeners for all selects
// function initSelectEventListeners() {
//     const selectMappings = [
//         { id: 'teamLeaderSelect', field: 'team_leader' },
//         { id: 'driverSelect', field: 'commander_driver' },
//         { id: 'pumperSelect', field: 'pumper' },
//         { id: 'officer1Select', field: 'firefighter_officer_1' },
//         { id: 'officer2Select', field: 'firefighter_officer_2' },
//         { id: 'officer3Select', field: 'firefighter_officer_3' },
//         { id: 'rescueOperatorSelect', field: 'rescue_radio' }
//     ];
    
//     selectMappings.forEach(mapping => {
//         const select = document.getElementById(mapping.id);
//         if (select) {
//             // Remove existing listener to avoid duplicates
//             select.removeEventListener('change', function() {});
//             // select.addEventListener('change', function() {
//                 onOfficerSelectChange(mapping.field, this);
//             // });
//         }
//     });
// }
// Initialize event listeners for all selects - FIXED
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
            const newSelect = select.cloneNode(true);
            select.parentNode.replaceChild(newSelect, select);
            
            // Add the event listener to the new select
            newSelect.addEventListener('change', function() {
                onOfficerSelectChange(mapping.field, this);
            });
        }
    });
}
// Reset all selects and disable them
// function resetTeamForm() {
//     const selectIds = ['teamLeaderSelect', 'driverSelect', 'pumperSelect', 'officer1Select', 'officer2Select', 'officer3Select', 'rescueOperatorSelect'];
    
//     selectIds.forEach(id => {
//         const select = document.getElementById(id);
//         if (select) {
//             select.innerHTML = '<option value="">Select ' + getSelectLabel(id) + '</option>';
//             select.disabled = true;
//             select.value = '';
//         }
//     });
    
//     // Reset current selections
//     currentTeamOfficers = {
//         team_leader_id: null,
//         driver_id: null,
//         pumper_id: null,
//         officer1_id: null,
//         officer2_id: null,
//         officer3_id: null,
//         rescue_operator_id: null
//     };
    
//     // Disable submit button
//     const submitBtn = document.getElementById('createTeamSubmitBtn');
//     if (submitBtn) {
//         submitBtn.disabled = true;
//         submitBtn.style.opacity = '0.6';
//     }
// }
function resetTeamForm() {
    const selectIds = ['teamLeaderSelect', 'driverSelect', 'pumperSelect', 'officer1Select', 'officer2Select', 'officer3Select', 'rescueOperatorSelect'];
    
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (select) {
            select.innerHTML = '<option value="">Select ' + getSelectLabel(id) + '</option>';
            select.disabled = true;
        }
    });
    
    // Reset current selections
    currentTeamOfficers = {
        team_leader: null,
        commander_driver: null,
        pumper: null,
        firefighter_officer_1: null,
        firefighter_officer_2: null,
        firefighter_officer_3: null,
        rescue_radio: null
    };
    
    // Disable submit button
    const submitBtn = document.getElementById('createTeamSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
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
// function stationChangeHandler() {
//     const stationId = this.value;
    
//     if (stationId && stationId !== '') {
//         if (officersByStation[stationId] && officersByStation[stationId].length > 0) {
//             populateStationOfficers(stationId);
//             initSelectEventListeners();
//         } else {
//             showToast('No available officers for this station', 'warning');
//             resetTeamForm();
//         }
//     } else {
//         resetTeamForm();
//     }
// }
function stationChangeHandler() {
    const stationId = this.value;
    if (stationId && stationId !== '') {
        // Check if we have officers for this station
        if (officersByStation[stationId] && officersByStation[stationId].length > 0) {
            // Reset current selections first
            currentTeamOfficers = {
                team_leader: null,
                commander_driver: null,
                pumper: null,
                firefighter_officer_1: null,
                firefighter_officer_2: null,
                firefighter_officer_3: null,
                rescue_radio: null
            };
            
            // Populate the selects
            populateStationOfficers(stationId);
            
            // Re-initialize event listeners after populating
            setTimeout(() => {
                initSelectEventListeners();
            }, 100);
        } else {
            showToast('No available officers for this station', 'warning');
            resetTeamForm();
        }
    } else {
        resetTeamForm();
    }
}
// Initialize team modal
// function initTeamModal() {
//     const stationSelect = document.getElementById('teamStationSelect');
//     const s = document.getElementById('teamStationSelect').value;
//     const modal = document.getElementById('teamModal');
    
//     if (!stationSelect) {
//         console.error('Station select not found');
//         return;
//     }
    
//     // Load officers data first
//     loadOfficersData();
    
//     // Handle modal open
//     if (modal) {
//         const observer = new MutationObserver(function(mutations) {
//             mutations.forEach(function(mutation) {
//                 if (mutation.attributeName === 'class') {
//                     if (modal.classList.contains('active')) {
//                         console.log('Modal opened - resetting form');
//                         stationSelect.value = s;
//                         resetTeamForm();
//                         // Reload data to get latest team assignments
//                         loadOfficersData();
//                     }
//                 }
//             });
//         });
//         observer.observe(modal, { attributes: true });
//     }
    
//     // Station change event
//     stationSelect.removeEventListener('change', stationChangeHandler);
//     stationSelect.addEventListener('change', stationChangeHandler);
// }
// Fixed initTeamModal function
function initTeamModal() {
    const stationSelect = document.getElementById('teamStationSelect');
    const modal = document.getElementById('teamModal');
    
    if (!stationSelect) {
        console.error('Station select not found');
        return;
    }
    
    // Load officers data first
    loadOfficersData();
    
    // Handle modal open - populate when modal opens
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (modal.classList.contains('active')) {
                        console.log('Modal opened - loading data');
                        // Reload data to get latest team assignments
                        loadOfficersData();
                        
                        // Get selected station value
                        const selectedStation = stationSelect.value;
                        if (selectedStation) {
                            // Trigger station change to populate
                            stationChangeHandler.call(stationSelect);
                        } else {
                            resetTeamForm();
                        }
                    }
                }
            });
        });
        observer.observe(modal, { attributes: true });
    }
    
    // Remove existing event listener and add new one
    const newStationSelect = stationSelect.cloneNode(true);
    stationSelect.parentNode.replaceChild(newStationSelect, stationSelect);
    
    // Add the event listener to the new select
    newStationSelect.addEventListener('change', stationChangeHandler);
    
    // If there's a pre-selected value, trigger the change
    if (newStationSelect.value) {
        setTimeout(() => {
            stationChangeHandler.call(newStationSelect);
        }, 500);
    }
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
    const submitBtn = document.getElementById('createTeamSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
    submitBtn.disabled = true;
    
    fetch(window.resourcesData.teamBaseUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Team created successfully');
            window.refreshResourceWorkspace();
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

const apparatusRegistry = @json($apparatusRecords);
const apparatusBaseUrl = @json(url('/'.$apparatusRoutePrefix.'/apparatus'));
const barangayOfficerRegistry = @json($barangayOfficers);
const barangayOfficerBaseUrl = @json($resourceBaseUrl.'/barangay-officers');

function syncBarangayOfficerMode() {
    const form = document.getElementById('barangayOfficerForm');
    if (!form) return;
    const mode = form.querySelector('input[name="mode"]:checked')?.value || 'existing';
    const editing = document.getElementById('barangayOfficerMethod')?.value === 'PUT';
    const existing = document.getElementById('barangayOfficerExistingFields');
    const newFields = document.getElementById('barangayOfficerNewFields');
    if (existing) existing.style.display = !editing && mode === 'existing' ? '' : 'none';
    if (newFields) newFields.style.display = editing || mode === 'new' ? 'grid' : 'none';
    newFields?.querySelectorAll('input').forEach(input => { input.disabled = !editing && mode !== 'new'; });
    const userSelect = document.getElementById('barangayOfficerUser');
    if (userSelect) userSelect.required = !editing && mode === 'existing';
    ['first_name', 'last_name', 'email', 'contact_number'].forEach(name => {
        const input = form.elements[name];
        if (input) input.required = editing || mode === 'new';
    });
}

function openBarangayOfficerModal(officerId = null) {
    const form = document.getElementById('barangayOfficerForm');
    if (!form) return;
    form.reset();
    const officer = barangayOfficerRegistry.find(item => Number(item.barangay_officer_id) === Number(officerId));
    const editing = Boolean(officer);
    form.action = editing ? `${barangayOfficerBaseUrl}/${officerId}` : barangayOfficerBaseUrl;
    document.getElementById('barangayOfficerMethod').value = editing ? 'PUT' : 'POST';
    document.getElementById('barangayOfficerModalTitle').textContent = editing ? 'Edit Barangay Officer' : 'Add Barangay Officer';
    document.getElementById('barangayOfficerModeGroup').style.display = editing ? 'none' : '';
    const barangaySelect = document.getElementById('barangayOfficerBarangay');
    barangaySelect.disabled = editing;
    if (editing) {
        ['first_name', 'middle_name', 'last_name', 'email', 'contact_number'].forEach(name => { form.elements[name].value = officer.user?.[name] || ''; });
        form.elements.street.value = officer.user?.address?.street || '';
        barangaySelect.value = officer.barangay_id;
        document.getElementById('barangayOfficerPosition').value = String(officer.position || 'officer').toLowerCase();
        document.getElementById('barangayOfficerStatus').value = officer.status || 'active';
    } else {
        form.querySelector('input[name="mode"][value="existing"]').checked = true;
        document.getElementById('barangayOfficerPosition').value = 'officer';
        document.getElementById('barangayOfficerStatus').value = 'active';
    }
    syncBarangayOfficerMode();
    form.elements.password.placeholder = editing ? 'Leave blank to keep password' : 'Leave blank to generate';
    document.getElementById('barangayOfficerModal').style.display = 'flex';
    document.getElementById('barangayOfficerModal').classList.add('active');
}

document.querySelectorAll('#barangayOfficerForm input[name="mode"]').forEach(input => input.addEventListener('change', syncBarangayOfficerMode));
document.getElementById('barangayOfficerForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = this.querySelector('button[type="submit"]');
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    try {
        const response = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const payload = await response.json().catch(() => ({}));
        const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
        if (!response.ok) throw new Error(validationMessage || payload.message || 'Unable to save barangay officer.');
        if (payload.temporary_password) window.alert(`Officer account created. Temporary password: ${payload.temporary_password}\n\nCopy this now and ask the officer to change it after signing in.`);
        showToast(payload.message || 'Barangay officer saved.', 'success');
        window.refreshResourceWorkspace();
    } catch (error) {
        showToast(error.message || 'Unable to save barangay officer.', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

async function deleteBarangayOfficer(officerId) {
    if (!confirm('Remove this barangay officer assignment? An existing user will be restored to their former account role.')) return;
    try {
        const response = await fetch(`${barangayOfficerBaseUrl}/${officerId}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Unable to remove barangay officer.');
        showToast(payload.message, 'success');
        window.refreshResourceWorkspace();
    } catch (error) {
        showToast(error.message || 'Unable to remove barangay officer.', 'error');
    }
}

function syncApparatusLoadoutRows(loadout = []) {
    const stationId = String(document.getElementById('apparatusStationId')?.value || '');
    const quantities = new Map((Array.isArray(loadout) ? loadout : []).map(item => [String(item.equipment_station_id), Number(item.quantity) || 0]));
    let visible = 0;
    document.querySelectorAll('.apparatus-loadout-row').forEach(row => {
        const enabled = row.dataset.stationId === stationId;
        row.style.display = enabled ? 'flex' : 'none';
        row.querySelectorAll('input').forEach(input => { input.disabled = !enabled; });
        const quantity = row.querySelector('.apparatus-loadout-quantity');
        if (quantity) quantity.value = enabled ? (quantities.get(row.dataset.assignmentId) || 0) : 0;
        if (enabled) visible += 1;
    });
    const empty = document.getElementById('apparatusLoadoutEmpty');
    if (empty) empty.style.display = visible ? 'none' : 'block';
}

function openApparatusModal(apparatusId = null) {
    const form = document.getElementById('apparatusForm');
    if (!form) return;
    form.reset();
    form.action = apparatusId ? `${apparatusBaseUrl}/${apparatusId}` : apparatusBaseUrl;
    document.getElementById('apparatusMethod').value = apparatusId ? 'PUT' : 'POST';
    document.getElementById('apparatusModalTitle').textContent = apparatusId ? 'Edit Fire Apparatus' : 'Register Fire Apparatus';
    const item = apparatusRegistry.find(record => Number(record.apparatus_id) === Number(apparatusId));
    if (item) {
        const set = (id, value) => { const element = document.getElementById(id); if (element) element.value = value ?? ''; };
        set('apparatusStationId', item.station_id);
        set('apparatusEquipmentId', item.equipment_id);
        set('apparatusUnitNumber', item.unit_number);
        set('apparatusPlateNumber', item.plate_number);
        set('apparatusType', item.apparatus_type);
        set('apparatusCapacity', item.water_capacity_liters);
        set('apparatusStatus', item.status);
        set('apparatusMaintenanceStatus', item.maintenance_status);
        set('apparatusLastMaintenance', item.last_maintenance_at ? String(item.last_maintenance_at).slice(0, 10) : '');
        set('apparatusNextMaintenance', item.next_maintenance_due_at ? String(item.next_maintenance_due_at).slice(0, 10) : '');
        set('apparatusGpsStatus', item.gps_status);
        set('apparatusLat', item.last_lat);
        set('apparatusLng', item.last_lng);
        set('apparatusNotes', item.notes);
    } else {
        document.getElementById('apparatusStatus').value = 'available';
        document.getElementById('apparatusMaintenanceStatus').value = 'operational';
        document.getElementById('apparatusGpsStatus').value = 'offline';
    }
    syncApparatusLoadoutRows(item?.loadout || []);
    document.getElementById('apparatusModal').style.display = 'flex';
    document.getElementById('apparatusModal').classList.add('active');
}

document.getElementById('apparatusStationId')?.addEventListener('change', () => syncApparatusLoadoutRows([]));

async function deleteApparatus(apparatusId) {
    const item = apparatusRegistry.find(record => Number(record.apparatus_id) === Number(apparatusId));
    if (!confirm(`Remove ${item?.unit_number || 'this apparatus'} from the registry?`)) return;
    try {
        const response = await fetch(`${apparatusBaseUrl}/${apparatusId}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Unable to remove apparatus.');
        showToast(payload.message || 'Apparatus removed.', 'success');
        window.refreshResourceWorkspace();
    } catch (error) {
        showToast(error.message || 'Unable to remove apparatus.', 'error');
    }
}

document.getElementById('apparatusForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = this.querySelector('button[type="submit"]');
    const original = button?.innerHTML;
    if (button) { button.disabled = true; button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...'; }
    try {
        const response = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const payload = await response.json().catch(() => ({}));
        const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
        if (!response.ok) throw new Error(validationMessage || payload.message || 'Unable to save apparatus.');
        showToast(payload.message || 'Apparatus saved.', 'success');
        closeModal('apparatusModal');
        window.refreshResourceWorkspace();
    } catch (error) {
        showToast(error.message || 'Unable to save apparatus.', 'error');
    } finally {
        if (button) { button.disabled = false; button.innerHTML = original; }
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initTeamModal();

    document.addEventListener('submit', async function (event) {
            const form = event.target.closest('.boundary-sync-form');
            if (!form) return;
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
                window.refreshResourceWorkspace();
            } catch (error) {
                showToast(error.message || 'Boundary sync failed.', 'error');

                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            }
    });
});
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>
