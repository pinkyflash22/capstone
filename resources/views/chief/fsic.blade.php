<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'station_admin' }}">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? 4 }}">
    
    <title>FSIC Management - PyroSense</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/app.css'])
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
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
            transition: transform 0.3s ease;
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

        .logout-container {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: none;
            border: none;
            color: #4b5563;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Status Badges */
        .fsic-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2rem;
            padding: 0 0.9rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .fsic-status-pill::before {
            content: '';
            width: 0.42rem;
            height: 0.42rem;
            border-radius: 9999px;
            background: currentColor;
            opacity: 0.75;
        }

        .fsic-status-pill.bg-yellow-100 { background: #fff1b8; color: #b45309; }
        .fsic-status-pill.bg-blue-100 { background: #dbeafe; color: #1d4ed8; }
        .fsic-status-pill.bg-purple-100 { background: #f3e8ff; color: #7e22ce; }
        .fsic-status-pill.bg-green-100 { background: #dcfce7; color: #15803d; }
        .fsic-status-pill.bg-red-100 { background: #fee2e2; color: #b91c1c; }
        .fsic-status-pill.bg-gray-100 { background: #f1f5f9; color: #475569; }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: white;
            padding: 0.7rem 1.1rem;
            border-radius: 0.9rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            box-shadow: 0 10px 24px rgba(198, 61, 38, 0.18);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.2);
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            padding: 0.7rem 1.1rem;
            border-radius: 0.9rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-outline {
            background: #fffaf0;
            color: #9a3412;
            border: 1px solid #fed7aa;
            padding: 0.7rem 1.1rem;
            border-radius: 0.9rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-outline:hover {
            background: #ffedd5;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .certificate-sheet {
            background: #fff;
            border: 2px solid #94a3b8;
            box-shadow: inset 0 0 0 1px #e5e7eb;
            padding: 28px 34px;
            color: #1f2937;
        }

        .certificate-header {
            display: grid;
            grid-template-columns: 84px 1fr 84px;
            gap: 12px;
            align-items: start;
            padding-bottom: 16px;
            border-bottom: 1px solid #94a3b8;
        }

        .certificate-seal {
            width: 74px;
            height: 74px;
            border-radius: 999px;
            border: 4px solid #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #b45309;
            margin: 0 auto;
            background: #fff7ed;
        }

        .certificate-heading {
            text-align: center;
            line-height: 1.35;
        }

        .certificate-heading .small {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .certificate-heading .main {
            font-size: 0.98rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .certificate-heading .sub {
            font-size: 0.82rem;
        }

        .certificate-title-row {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            padding: 16px 0 10px;
            border-bottom: 1px solid #94a3b8;
        }

        .certificate-no {
            font-size: 0.95rem;
            font-weight: 800;
            color: #dc2626;
        }

        .certificate-date {
            text-align: right;
            font-size: 0.82rem;
        }

        .certificate-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #475569;
            padding: 16px 0 12px;
        }

        .certificate-types {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: fit-content;
            margin: 0 auto 18px;
            font-size: 0.83rem;
        }

        .certificate-type-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .certificate-checkbox {
            width: 20px;
            height: 20px;
            border: 1px solid #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #0f172a;
        }

        .certificate-body {
            font-size: 0.9rem;
            line-height: 1.8;
            border-top: 1px solid #94a3b8;
            border-bottom: 1px solid #94a3b8;
            padding: 18px 0;
        }

        .certificate-line {
            display: inline-block;
            min-width: 180px;
            border-bottom: 1px solid #475569;
            font-weight: 700;
            text-align: center;
            line-height: 1.3;
        }

        .certificate-line.address {
            min-width: 280px;
        }

        .certificate-line.wide {
            min-width: 320px;
        }

        .certificate-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding-top: 18px;
        }

        .certificate-meta-block {
            font-size: 0.82rem;
            line-height: 1.8;
        }

        .certificate-sign {
            text-align: center;
            padding-top: 18px;
        }

        .certificate-sign-name {
            font-weight: 800;
            text-transform: uppercase;
            border-top: 1px solid #475569;
            display: inline-block;
            padding-top: 6px;
            min-width: 240px;
        }

        .certificate-note {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            font-size: 0.76rem;
            color: #475569;
            text-align: center;
            font-style: italic;
        }
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6b7280;
            z-index: 2;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: #dc2626;
            color: white;
            box-shadow: 0 0 0 4px rgba(220,38,38,0.2);
        }
        
        .step.completed .step-circle {
            background: #10b981;
            color: white;
        }
        
        .step.completed .step-circle i {
            font-size: 1rem;
        }
        
        .step-label {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #dc2626;
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: #10b981;
        }
        
        .step-line {
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .step:last-child .step-line {
            display: none;
        }
        
        .step.completed + .step .step-line {
            background: #10b981;
        }
        
        /* Modal */
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
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
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
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #fff7ed 0%, #fffdf7 100%);
            padding: 0.75rem;
            border-radius: 1.4rem;
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        
        .tab-btn {
            padding: 0.85rem 1.15rem;
            background: transparent;
            border: 1px solid transparent;
            font-size: 0.9rem;
            font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 1rem;
        }
        
        .tab-btn:hover {
            color: #9a3412;
            background: rgba(255, 255, 255, 0.72);
        }
        
        .tab-btn.active {
            color: #ffffff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            box-shadow: 0 10px 22px rgba(198, 61, 38, 0.18);
        }
        
        /* Tab Content - Fixed Layout */
        .tabs-container {
            position: relative;
            min-height: 550px;
        }
        
        .tab-content {
            display: none;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        
        .tab-content.active {
            display: block;
            opacity: 1;
        }
        
        /* Table Styles */
        .requests-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, #ffffff 100%);
            border-radius: 1.6rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.9);
            overflow: hidden;
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }
        
        .requests-card .p-5 {
            flex-shrink: 0;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #fffaf1 0%, #ffffff 100%);
        }
        
        .table-wrapper {
            overflow-x: auto;
            flex: 1;
            -webkit-overflow-scrolling: touch;
        }
        
        .fsic-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }
        
        .fsic-table th {
            padding: 0.875rem 1rem;
            font-weight: 800;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background-color: #fffaf0;
            border-bottom: 1px solid #f1dfc4;
            color: #64748b;
        }
        
        .fsic-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f3f1eb;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155;
        }
        
        .fsic-table tbody tr:hover {
            background: #fffaf4;
        }

        .fsic-request-code {
            font-weight: 800;
            color: #0f172a;
        }

        .fsic-request-primary {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .fsic-request-title {
            font-weight: 700;
            color: #0f172a;
        }

        .fsic-request-meta {
            font-size: 0.78rem;
            color: #64748b;
        }

        .fsic-table-empty {
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: #64748b;
            font-size: 0.95rem;
        }

        .fsic-table-empty strong {
            display: block;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 0.4rem;
        }

        .fsic-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .fsic-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 2.15rem;
            padding: 0 0.8rem;
            border-radius: 9999px;
            border: 1px solid transparent;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff7ed;
            color: #9a3412;
        }

        .fsic-action-btn:hover {
            transform: translateY(-1px);
        }

        .fsic-action-btn.is-view {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .fsic-action-btn.is-review,
        .fsic-action-btn.is-approve,
        .fsic-action-btn.is-issue,
        .fsic-action-btn.is-inspect,
        .fsic-action-btn.is-certificate {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #ffffff;
            border-color: rgba(198, 61, 38, 0.3);
        }

        .fsic-action-btn.is-reject {
            background: #fff1f2;
            color: #b91c1c;
            border-color: #fecdd3;
        }

        .fsic-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .fsic-summary-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .fsic-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.8rem;
            padding: 0 1rem;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .fsic-summary-pill.pending {
            background: #fff1b8;
            color: #b45309;
        }

        .fsic-summary-pill.inspect {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .fsic-page-header {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .fsic-page-title {
            font-size: 2.2rem;
            line-height: 1.08;
            font-weight: 800;
            color: #0f172a;
        }

        .fsic-page-copy {
            color: #64748b;
            font-size: 1.02rem;
        }

        .fsic-content-shell {
            max-width: 1300px;
            margin: 0 auto;
            padding: 2rem 1.5rem 2.5rem;
        }
        
        /* Fixed column widths */
        .col-code { width: 130px; }
        .col-date { width: 100px; }
        .col-applicant { width: 150px; }
        .col-business { width: 200px; }
        .col-address { width: 200px; }
        .col-status { width: 120px; }
        .col-actions { width: 140px; }
        .col-fsic { width: 140px; }
        .col-valid { width: 110px; }
        
        /* Scrollbar styling */
        .table-wrapper::-webkit-scrollbar {
            height: 6px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Form Steps */
        .form-step {
            display: none;
        }
        
        .form-step.active-step {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .mobile-logo {
                display: flex;
            }
            
            .tab-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            body.chief-legacy-layout {
                background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            }

            .legacy-chief-sidebar {
                transform: translateX(-100%);
            }
            
            .legacy-chief-sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 84px) !important;
            }
            
            .top-nav-container {
                padding: 0 16px;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }
            
            .step-line {
                display: none;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .modal-content {
                width: 95%;
                max-height: 90vh;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .lector-top-bar {
                min-height: 48px !important;
                height: 48px !important;
                margin: 8px auto 0 !important;
                padding: 0 10px !important;
                width: calc(100% - 20px) !important;
                max-width: 330px !important;
                border-radius: 14px !important;
                top: calc(env(safe-area-inset-top, 0px) + 6px) !important;
            }

            .lector-top-bar .flex.items-center.gap-4 {
                gap: 0.45rem !important;
            }

            .lector-top-bar .flex.items-center.gap-6 {
                gap: 0.45rem !important;
            }

            .lector-top-bar .w-10.h-10 {
                width: 2rem !important;
                height: 2rem !important;
            }

            .lector-top-bar .user-dropdown-toggle {
                padding: 4px 8px !important;
                gap: 0.45rem !important;
            }

            .lector-top-bar .user-dropdown-toggle .w-8.h-8 {
                width: 1.75rem !important;
                height: 1.75rem !important;
            }

            .fsic-content-shell {
                max-width: 330px;
                padding: 0.7rem 0.5rem 1.4rem;
            }

            .fsic-toolbar {
                align-items: stretch;
                gap: 0.6rem;
                margin-bottom: 0.8rem;
            }

            .fsic-page-title {
                font-size: 0.9rem;
                line-height: 1.2;
            }

            .fsic-page-copy {
                font-size: 0.68rem;
                line-height: 1.4;
            }

            .fsic-summary-pills {
                gap: 0.35rem;
            }

            .fsic-summary-pill {
                min-height: 1.75rem;
                padding: 0 0.55rem;
                font-size: 0.62rem;
                gap: 0.35rem;
            }

            .fsic-summary-pill i {
                font-size: 0.7rem;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
                min-height: 2rem;
                padding: 0.65rem 0.8rem;
                font-size: 0.7rem;
                border-radius: 0.8rem;
            }

            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x proximity;
                padding: 0.42rem;
                gap: 0.35rem;
                border-radius: 1rem;
            }

            .tabs::-webkit-scrollbar {
                display: none;
            }

            .tab-btn {
                flex: 0 0 auto;
                white-space: nowrap;
                scroll-snap-align: start;
                padding: 0.52rem 0.7rem;
                font-size: 0.6rem;
                border-radius: 0.75rem;
            }

            .tabs-container,
            .requests-card {
                min-height: auto;
            }

            .requests-card {
                border-radius: 1.15rem;
            }

            .requests-card .p-5 {
                padding: 0.85rem 0.95rem;
            }

            .requests-card .p-5 h2 {
                font-size: 0.8rem;
                line-height: 1.4;
            }

            .table-wrapper {
                overflow: visible;
                padding: 0.75rem;
            }

            .fsic-table,
            .fsic-table thead,
            .fsic-table tbody,
            .fsic-table tr,
            .fsic-table th,
            .fsic-table td {
                display: block;
                width: 100%;
            }

            .fsic-table {
                min-width: 0;
                border-collapse: separate;
            }

            .fsic-table thead {
                display: none;
            }

            .fsic-table tbody {
                display: grid;
                gap: 0.65rem;
            }

            .fsic-table tbody tr {
                background: linear-gradient(180deg, #fffdf9 0%, #ffffff 100%);
                border: 1px solid #f3e4c8;
                border-radius: 0.9rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
                padding: 0.25rem 0;
                overflow: hidden;
            }

            .fsic-table td {
                border-bottom: 1px solid #f8ede0;
                padding: 0.62rem 0.8rem;
                font-size: 0.72rem;
                line-height: 1.45;
            }

            .fsic-table td:last-child {
                border-bottom: none;
            }

            .fsic-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 0.28rem;
                font-size: 0.56rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .fsic-request-primary {
                gap: 0.2rem;
            }

            .fsic-request-title {
                font-size: 0.8rem;
            }

            .fsic-request-meta {
                font-size: 0.64rem;
            }

            .fsic-request-code {
                font-size: 0.8rem;
            }

            .fsic-status-pill {
                width: fit-content;
                min-height: 1.55rem;
                padding: 0 0.55rem;
                font-size: 0.62rem;
            }

            .fsic-action-group {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.45rem;
            }

            .fsic-action-btn {
                width: 100%;
                justify-content: center;
                min-height: 1.95rem;
                padding: 0.45rem 0.7rem;
                font-size: 0.65rem;
            }

            .fsic-table-empty {
                padding: 1.7rem 0.9rem;
                font-size: 0.72rem;
            }

            .fsic-table-empty strong {
                font-size: 0.82rem;
            }
        }
        
        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #dc2626;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Add Applicant Button */
        .add-applicant-btn {
            background: none;
            border: none;
            color: #dc2626;
            font-size: 0.75rem;
            cursor: pointer;
            margin-top: 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .add-applicant-btn:hover {
            text-decoration: underline;
        }
        
        .applicant-form {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .applicant-form h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1f2937;
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

        .user-dropdown {
            position: relative;
        }

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

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

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

        .fa-solid,
        .fas,
        .fa-regular,
        .far,
        .fa-brands,
        .fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
            font-style: normal !important;
            line-height: 1 !important;
        }

        .fa-solid,
        .fas,
        .fa-solid::before,
        .fas::before {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }

        .fa-regular,
        .far,
        .fa-regular::before,
        .far::before {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 400 !important;
        }

        .fa-brands,
        .fab,
        .fa-brands::before,
        .fab::before {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        @media (max-width: 768px) {
            .lector-top-bar {
                top: 12px;
                margin: 12px 14px 0;
                padding: 0 16px;
                border-radius: 20px;
            }

            .fsic-content-shell {
                width: min(100%, 760px);
                max-width: 760px;
                padding: 12px 14px calc(env(safe-area-inset-bottom, 0px) + 96px);
            }

            .requests-card,
            .tabs-container {
                border-radius: 0;
                margin-inline: -14px;
            }

            .requests-card .p-5 {
                padding: 16px;
            }

            .modal {
                align-items: flex-end;
                padding: 10px;
            }

            .modal-content {
                width: 100% !important;
                max-width: 100% !important;
                max-height: min(84dvh, 760px) !important;
                border-radius: 24px 24px 18px 18px !important;
            }

            .modal-content::before {
                content: "";
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 14px auto 0;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">

    <!-- SIDE NAVIGATION -->
    @include('chief.partials.sidebar', ['active' => 'fsic'])
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
                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Station Admin' }}</span>
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
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item active">
                    <i class="fa-solid fa-file-certificate"></i>
                    <span>FSIC Management</span>
                    <span class="nav-badge">{{ $pendingCount ?? 0 }}</span>
                </a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
                <a href="{{ route('station-admin.management') }}" class="nav-item">
                    <i class="fa-solid fa-users"></i>
                    <span>Personnel</span>
                </a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item">
                    <i class="fa-solid fa-toolbox"></i>
                    <span>Resources</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">REPORTS</div>
                <a href="{{ route('station-admin.reports') }}" class="nav-item">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Analytics</span>
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
    </aside>

    <!-- BACKDROP for mobile -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">FSIC Management</h1>
                    <p class="text-xs text-slate-500 font-medium">Inspection And Certification Records • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="relative">
                    <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition relative">
                        <i class="fa-regular fa-bell"></i>
                        @if(($pendingCount ?? 0) > 0)
                        <span class="absolute top-2 right-2 w-2 h-2 bg-amber-500 rounded-full"></span>
                        @endif
                    </button>
                </div>

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

        <!-- MAIN CONTENT AREA -->
        <div class="fsic-content-shell">
            <!-- Header with Add Request Button -->
            <div class="fsic-toolbar">
                <div class="fsic-page-header">
                    <h1 class="fsic-page-title">Fire Safety Inspection Certificate</h1>
                    <p class="fsic-page-copy">Manage, inspect, approve, and issue FSIC requests with a live station workflow.</p>
                </div>
                <div class="flex items-center gap-3 mt-3 sm:mt-0">
                    <div class="fsic-summary-pills">
                        <span class="fsic-summary-pill pending">
                            <i class="fa-solid fa-clock"></i>
                            Pending: {{ $pendingCount ?? 0 }}
                        </span>
                        <span class="fsic-summary-pill inspect">
                            <i class="fa-solid fa-clipboard-list"></i>
                            For Inspection: {{ $forInspectionCount ?? 0 }}
                        </span>
                    </div>
                    <button onclick="openNewRequestModal()" class="btn-primary flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> New Request
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="pending">Pending Requests</button>
                <button class="tab-btn" data-tab="for-inspection">For Inspection</button>
                <button class="tab-btn" data-tab="inspected">Inspected</button>
                <button class="tab-btn" data-tab="approved">Approved</button>
                <button class="tab-btn" data-tab="rejected">Rejected</button>
                <button class="tab-btn" data-tab="all">All Requests</button>
            </div>

            <!-- Tabs Container -->
            <div class="tabs-container">
                <!-- Pending Tab -->
                <div id="tab-pending" class="tab-content active">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-clock text-yellow-600"></i>
                                Pending Requests for Review
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-address">Address</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Date">{{ $request->date_requested->format('M d, Y') }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">{{ $request->owner_name ?: 'Applicant record' }}</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Address">{{ $request->barangay }}, {{ $request->city }}</td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                                <button class="fsic-action-btn is-review" onclick="reviewRequest({{ $request->id }})"><i class="fa-solid fa-clipboard-list"></i><span>Review</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="fsic-table-empty"><strong>No pending requests</strong>New applications waiting for command review will appear here.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- For Inspection Tab -->
                <div id="tab-for-inspection" class="tab-content">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-clipboard-list text-blue-600"></i>
                                Requests For Inspection
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-address">Address</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($forInspectionRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Date">{{ $request->date_requested->format('M d, Y') }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">{{ optional($request->inspection)->stationOfficer?->user?->first_name ? 'Assigned officer ready' : 'Awaiting field update' }}</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Address">{{ $request->barangay }}, {{ $request->city }}</td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                                <button class="fsic-action-btn is-inspect" onclick="openInspectionModal({{ $request->id }})"><i class="fa-solid fa-clipboard-check"></i><span>Inspect</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="fsic-table-empty"><strong>No requests for inspection</strong>Requests assigned for field inspection will appear here.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Inspected Tab -->
                <div id="tab-inspected" class="tab-content">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-check-circle text-purple-600"></i>
                                Inspected Requests
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inspectedRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Date">{{ $request->date_requested->format('M d, Y') }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">Inspection report submitted</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                                <button class="fsic-action-btn is-approve" onclick="openApprovalModal({{ $request->id }})"><i class="fa-solid fa-certificate"></i><span>Approve</span></button>
                                                <button class="fsic-action-btn is-reject" onclick="rejectInspectedRequest({{ $request->id }})"><i class="fa-solid fa-ban"></i><span>Reject</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="fsic-table-empty"><strong>No inspected requests</strong>Completed inspection reports ready for command decision will show here.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Approved Tab -->
                <div id="tab-approved" class="tab-content">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-certificate text-green-600"></i>
                                Approved Requests
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-fsic">FSIC Number</th>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-date">Date Issued</th>
                                        <th class="col-valid">Valid Until</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($approvedRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="FSIC Number">{{ $request->approval->fsic_number ?? 'N/A' }}</td>
                                        <td data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">{{ $request->owner_name ?: 'Applicant record' }}</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Date Issued">{{ $request->approval->date_issued ? \Carbon\Carbon::parse($request->approval->date_issued)->format('M d, Y') : 'Not yet issued' }}</td>
                                        <td data-label="Valid Until">{{ $request->approval->valid_until ?? 'N/A' }}</td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                                <button class="fsic-action-btn is-certificate" onclick="openCertificateModal({{ $request->id }})"><i class="fa-solid fa-file-lines"></i><span>Certificate</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="fsic-table-empty"><strong>No approved requests</strong>Approved and issued records will appear here for printing and tracking.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rejected Tab -->
                <div id="tab-rejected" class="tab-content">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-times-circle text-red-600"></i>
                                Rejected Requests
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rejectedRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Date">{{ $request->date_requested->format('M d, Y') }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">Request was declined</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="fsic-table-empty"><strong>No rejected requests</strong>Declined applications will be listed here for audit and review.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- All Requests Tab -->
                <div id="tab-all" class="tab-content">
                    <div class="requests-card">
                        <div class="p-5 border-b">
                            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-list-ul"></i>
                                All FSIC Requests
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="fsic-table">
                                <thead>
                                    <tr>
                                        <th class="col-code">Request Code</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-applicant">Applicant</th>
                                        <th class="col-business">Business Name</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allRequests ?? [] as $request)
                                    <tr>
                                        <td class="fsic-request-code" data-label="Request Code">{{ $request->request_code }}</td>
                                        <td data-label="Date">{{ $request->date_requested->format('M d, Y') }}</td>
                                        <td data-label="Applicant"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->user->first_name ?? '' }} {{ $request->user->last_name ?? '' }}</span><span class="fsic-request-meta">{{ $request->owner_name ?: 'Applicant record' }}</span></div></td>
                                        <td data-label="Business Name"><div class="fsic-request-primary"><span class="fsic-request-title">{{ $request->business_name }}</span><span class="fsic-request-meta">{{ $request->business_type ?: 'Business request' }}</span></div></td>
                                        <td data-label="Status"><span class="fsic-status-pill {{ $request->status_badge }}">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td data-label="Actions">
                                            <div class="fsic-action-group">
                                                <button class="fsic-action-btn is-view" onclick="viewRequest({{ $request->id }})"><i class="fa-solid fa-eye"></i><span>View</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="fsic-table-empty"><strong>No requests found</strong>The full request registry will appear here as records are created.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- REVIEW MODAL WITH 3-STEP PROGRESS -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-xl font-bold">Review FSIC Request</h2>
                <button onclick="closeReviewModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reviewRequestId">
                
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="step" id="step1">
                        <div class="step-circle">1</div>
                        <div class="step-line"></div>
                        <div class="step-label">Review Request</div>
                    </div>
                    <div class="step" id="step2">
                        <div class="step-circle">2</div>
                        <div class="step-line"></div>
                        <div class="step-label">Decision</div>
                    </div>
                    <div class="step" id="step3">
                        <div class="step-circle">3</div>
                        <div class="step-label">Finalize</div>
                    </div>
                </div>
                
                <!-- Step 1: Review Details -->
                <div id="reviewStep1" class="form-step active-step">
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">Request Details</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p><span class="text-gray-500">Request Code:</span></p>
                            <p class="font-medium" id="reviewRequestCode">-</p>
                            <p><span class="text-gray-500">Date Requested:</span></p>
                            <p class="font-medium" id="reviewDateRequested">-</p>
                            <p><span class="text-gray-500">Business Name:</span></p>
                            <p class="font-medium" id="reviewBusinessName">-</p>
                            <p><span class="text-gray-500">Owner Name:</span></p>
                            <p class="font-medium" id="reviewOwnerName">-</p>
                            <p><span class="text-gray-500">Business Type:</span></p>
                            <p class="font-medium" id="reviewBusinessType">-</p>
                            <p><span class="text-gray-500">Building Address:</span></p>
                            <p class="font-medium" id="reviewAddress">-</p>
                            <p><span class="text-gray-500">Barangay:</span></p>
                            <p class="font-medium" id="reviewBarangay">-</p>
                            <p><span class="text-gray-500">City:</span></p>
                            <p class="font-medium" id="reviewCity">-</p>
                            <p><span class="text-gray-500">Province:</span></p>
                            <p class="font-medium" id="reviewProvince">-</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">Registration Numbers</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p><span class="text-gray-500">DTI/SEC No.:</span></p>
                            <p class="font-medium" id="reviewDti">-</p>
                            <p><span class="text-gray-500">Mayor's Permit No.:</span></p>
                            <p class="font-medium" id="reviewMayorsPermit">-</p>
                            <p><span class="text-gray-500">TIN No.:</span></p>
                            <p class="font-medium" id="reviewTin">-</p>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Decision -->
                <div id="reviewStep2" class="form-step">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Select Decision</label>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="reviewDecision" value="for_inspection" class="w-4 h-4 text-blue-600">
                                <div>
                                    <span class="font-medium">Send for Inspection</span>
                                    <p class="text-xs text-gray-500">Forward this request to inspection team for on-site evaluation</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="reviewDecision" value="rejected" class="w-4 h-4 text-red-600">
                                <div>
                                    <span class="font-medium">Reject Request</span>
                                    <p class="text-xs text-gray-500">Deny this application and provide reason</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Finalize -->
                <div id="reviewStep3" class="form-step">
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4" id="finalIcon">
                            <i class="fa-solid fa-check-circle text-green-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2" id="finalTitle">Ready to Submit</h3>
                        <p class="text-gray-600 mb-4" id="finalMessage">Please review your decision before submitting.</p>
                        
                        <div class="bg-gray-50 p-4 rounded-lg text-left">
                            <p class="text-sm"><strong>Decision:</strong> <span id="finalDecision">-</span></p>
                        </div>

                        <div id="assignedPersonnelWrap" class="bg-gray-50 p-4 rounded-lg text-left mt-4" style="display: none;">
                            <label for="assignedStationOfficerId" class="block text-sm font-medium text-gray-700 mb-2">Assign Inspection Personnel</label>
                            <select id="assignedStationOfficerId" class="w-full border rounded-lg p-2">
                                <option value="">Select available personnel</option>
                                @foreach($availableInspectors ?? [] as $officer)
                                    <option value="{{ $officer->station_officer_id }}">
                                        {{ trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')) ?: ('Officer #' . $officer->station_officer_id) }}
                                        @if(!empty($officer->rank))
                                            - {{ $officer->rank }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-2">Only available personnel from your station are listed here.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="prevBtn" onclick="prevStep()" style="display: none;">Previous</button>
                <button class="btn-outline" onclick="closeReviewModal()">Cancel</button>
                <button class="btn-primary" id="nextBtn" onclick="nextStep()">Next</button>
                <button class="btn-success" id="submitBtn" onclick="submitReview()" style="display: none;">Submit Decision</button>
            </div>
        </div>
    </div>

    <!-- INSPECTION MODAL -->
    <div id="inspectionModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 class="text-xl font-bold">Conduct Inspection</h2>
                <button onclick="closeInspectionModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="inspectionRequestId">
                
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold text-gray-800 mb-2">Request Information</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><span class="text-gray-500">Request Code:</span></p>
                        <p class="font-medium" id="inspectionRequestCode">-</p>
                        <p><span class="text-gray-500">Business Name:</span></p>
                        <p class="font-medium" id="inspectionBusinessName">-</p>
                        <p><span class="text-gray-500">Owner:</span></p>
                        <p class="font-medium" id="inspectionOwnerName">-</p>
                    </div>
                </div>
                
                <h3 class="font-semibold text-gray-800 mb-3">Inspection Checklist</h3>
                
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_fire_protection" class="w-4 h-4">
                        <span class="text-sm">Fire Protection Systems (Sprinklers, Extinguishers)</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_alarm_systems" class="w-4 h-4">
                        <span class="text-sm">Alarm and Detection Systems</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_material_used" class="w-4 h-4">
                        <span class="text-sm">Standard Construction Materials Used</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_electrical" class="w-4 h-4">
                        <span class="text-sm">Electrical Wiring and Installation</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_housekeeping" class="w-4 h-4">
                        <span class="text-sm">Housekeeping and Storage Practices</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_hazards" class="w-4 h-4">
                        <span class="text-sm">Fire Hazards Identification</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                        <input type="checkbox" id="check_planning" class="w-4 h-4">
                        <span class="text-sm">Emergency Planning and Exit Routes</span>
                    </label>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Inspection Remarks</label>
                    <textarea id="inspectionRemarks" rows="3" class="w-full border rounded-lg p-2" placeholder="Enter inspection findings..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Compliance Status</label>
                    <select id="complianceStatus" class="w-full border rounded-lg p-2">
                        <option value="1">Compliant - Passed</option>
                        <option value="0">Non-Compliant - Failed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeInspectionModal()">Cancel</button>
                <button class="btn-primary" onclick="submitInspection()">Submit Inspection Report</button>
            </div>
        </div>
    </div>

    <!-- APPROVAL MODAL -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-xl font-bold">Final Approval</h2>
                <button onclick="closeApprovalModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approvalRequestId">
                
                <div class="bg-green-50 p-4 rounded-lg mb-4">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-certificate text-green-600 text-2xl"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">Ready for Final Approval</h3>
                            <p class="text-sm text-gray-600">This request has passed inspection and is ready to be approved</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold text-gray-800 mb-2">Request Summary</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><span class="text-gray-500">Request Code:</span></p>
                        <p class="font-medium" id="approvalRequestCode">-</p>
                        <p><span class="text-gray-500">Business Name:</span></p>
                        <p class="font-medium" id="approvalBusinessName">-</p>
                        <p><span class="text-gray-500">Owner:</span></p>
                        <p class="font-medium" id="approvalOwnerName">-</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">FSIC Certificate Number</label>
                    <input type="text" id="fsicNumber" class="w-full border rounded-lg p-2" placeholder="Auto-generated or custom FSIC number">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate</p>
                </div>
                
                <div class="mb-4">
                    <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                        <div class="font-semibold mb-1">Citizen Claim-Based Issuance</div>
                        <div>The certificate will be issued only when the citizen clicks claim. `Date Issued` will be the claim date, and `Valid Until` will be computed as exactly one year from that date.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        <div class="font-semibold mb-1">Walk-In Issuance</div>
                        <div>Use `Issue Now` for walk-ins or citizens without an account. It will set `Date Issued` immediately and compute `Valid Until` as one year from today.</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Approval Remarks</label>
                    <textarea id="approvalRemarks" rows="3" class="w-full border rounded-lg p-2" placeholder="Enter approval remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeApprovalModal()">Cancel</button>
                <button class="btn-success" onclick="submitApproval()">
                    <i class="fa-solid fa-certificate mr-1"></i> Approve Request
                </button>
                <button class="btn-primary" onclick="submitApproval(true)">
                    <i class="fa-solid fa-file-circle-check mr-1"></i> Issue Now
                </button>
            </div>
        </div>
    </div>

    <!-- VIEW REQUEST MODAL -->
    <div id="viewRequestModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 class="text-xl font-bold">FSIC Request Details</h2>
                <button onclick="closeViewModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body" id="viewRequestContent">
                <div class="text-center py-8">Loading...</div>
            </div>
            <div class="modal-footer">
                <button class="btn-danger" id="viewRejectBtn" style="display: none;" onclick="submitModalReject()">
                    Reject Request
                </button>
                <button class="btn-outline" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <div id="certificateModal" class="modal">
        <div class="modal-content" style="max-width: 980px;">
            <div class="modal-header">
                <h2 class="text-xl font-bold">FSIC Certificate Preview</h2>
                <button onclick="closeCertificateModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body" id="certificateModalBody">
                <div class="text-center py-8">Loading certificate...</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="printCertificateContent()">Print</button>
                <button class="btn-outline" onclick="closeCertificateModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- NEW REQUEST MODAL WITH LOCATION DROPDOWNS AND ADD APPLICANT -->
    <div id="newRequestModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 class="text-xl font-bold">New FSIC Request</h2>
                <button onclick="closeNewRequestModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="newRequestForm">
                    <!-- Applicant Selection -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Applicant *</label>
                        <select name="user_id" id="applicantSelect" class="w-full border rounded-lg p-2" required>
                            <option value="">Select Existing User</option>
                            @foreach($users ?? [] as $user)
                                <option value="{{ $user->user_id }}">{{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <button type="button" class="add-applicant-btn" onclick="toggleApplicantForm()">
                            <i class="fa-solid fa-plus-circle"></i> Add New Applicant
                        </button>
                    </div>
                    
                    <!-- Add New Applicant Form (Hidden by default) -->
                    <div id="applicantForm" class="applicant-form" style="display: none;">
                        <h4><i class="fa-solid fa-user-plus"></i> New Applicant Information</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input type="text" id="new_first_name" class="w-full border rounded-lg p-2" placeholder="First Name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" id="new_middle_name" class="w-full border rounded-lg p-2" placeholder="Middle Name">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                <input type="text" id="new_last_name" class="w-full border rounded-lg p-2" placeholder="Last Name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                                <input type="tel" id="new_contact_number" class="w-full border rounded-lg p-2" placeholder="09XX XXX XXXX">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" id="new_email" class="w-full border rounded-lg p-2" placeholder="email@example.com">
                        </div>
                        <button type="button" class="btn-primary btn-sm" onclick="createNewApplicant()">
                            <i class="fa-solid fa-save"></i> Save Applicant
                        </button>
                        <button type="button" class="btn-outline btn-sm" onclick="toggleApplicantForm()">
                            Cancel
                        </button>
                        <div id="applicantLoading" class="hidden mt-2"><span class="loading-spinner"></span> Creating...</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                        <input type="text" name="business_name" class="w-full border rounded-lg p-2" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                        <input type="text" name="owner_name" class="w-full border rounded-lg p-2" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                        <select name="business_type" class="w-full border rounded-lg p-2">
                            <option value="">Select...</option>
                            <option value="retail">Retail / Store</option>
                            <option value="restaurant">Restaurant / Food Service</option>
                            <option value="office">Office</option>
                            <option value="warehouse">Warehouse / Storage</option>
                            <option value="hotel">Hotel / Inn</option>
                            <option value="school">School / Educational</option>
                            <option value="hospital">Hospital / Clinic</option>
                            <option value="manufacturing">Manufacturing / Factory</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Building/Street Address *</label>
                        <input type="text" name="building_address" class="w-full border rounded-lg p-2" required>
                    </div>
                    
                    <!-- Location Dropdowns -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province *</label>
                            <select name="province_id" id="provinceSelect" class="w-full border rounded-lg p-2" required onchange="loadCities()">
                                <option value="">Select Province</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->province_id }}" data-province-name="{{ $province->province_name }}">{{ $province->province_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City/Municipality *</label>
                            <select name="city_id" id="citySelect" class="w-full border rounded-lg p-2" required onchange="loadBarangays()" disabled>
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barangay *</label>
                            <select name="barangay_id" id="barangaySelect" class="w-full border rounded-lg p-2" required disabled>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Hidden fields for location names -->
                    <input type="hidden" id="provinceName" name="province">
                    <input type="hidden" id="cityName" name="city">
                    <input type="hidden" id="barangayName" name="barangay">
                    
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">DTI/SEC No.</label>
                            <input type="text" name="dti_sec_registration_no" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mayor's Permit No.</label>
                            <input type="text" name="mayors_permit_no" class="w-full border rounded-lg p-2">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">TIN Number</label>
                        <input type="text" name="tin_number" class="w-full border rounded-lg p-2">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="closeNewRequestModal()">Cancel</button>
                <button class="btn-primary" onclick="submitNewRequest()">Submit Request</button>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let currentRequestId = null;
        let currentViewRequestId = null;
        let selectedDecision = null;
        let isCreatingApplicant = false;
        let selectedAssignedPersonnelId = '';
        
        // Sidebar toggle
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
                    document.body.style.overflow = 'hidden';
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                    document.body.style.overflow = 'auto';
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
            
            // Tab switching
            initTabs();
        });

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            }
        });

        function initTabs() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.dataset.tab;
                    
                    tabBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    tabContents.forEach(content => content.classList.remove('active'));
                    const activeTab = document.getElementById(`tab-${tabId}`);
                    if (activeTab) {
                        activeTab.classList.add('active');
                    }
                });
            });
        }

        function emergencyCall() {
            if (confirm('Call 911 for emergency services?')) {
                window.location.href = 'tel:911';
            }
        }

        // ============ LOCATION DROPDOWN FUNCTIONS ============
        async function loadCities() {
            const provinceSelect = document.getElementById('provinceSelect');
            const provinceId = provinceSelect.value;
            const provinceName = provinceSelect.options[provinceSelect.selectedIndex]?.getAttribute('data-province-name') || '';
            document.getElementById('provinceName').value = provinceName;
            
            const citySelect = document.getElementById('citySelect');
            const barangaySelect = document.getElementById('barangaySelect');
            
            if (!provinceId) {
                citySelect.innerHTML = '<option value="">Select City First</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                return;
            }
            
            try {
                const response = await fetch(`/api/cities/${provinceId}`);
                const cities = await response.json();
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                cities.forEach(city => {
                    citySelect.innerHTML += `<option value="${city.city_id}" data-city-name="${city.city_name}">${city.city_name}</option>`;
                });
                citySelect.disabled = false;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                document.getElementById('cityName').value = '';
                document.getElementById('barangayName').value = '';
            } catch (error) {
                console.error('Error loading cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }

        async function loadBarangays() {
            const citySelect = document.getElementById('citySelect');
            const cityId = citySelect.value;
            const cityName = citySelect.options[citySelect.selectedIndex]?.getAttribute('data-city-name') || '';
            document.getElementById('cityName').value = cityName;
            
            const barangaySelect = document.getElementById('barangaySelect');
            
            if (!cityId) {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                return;
            }
            
            try {
                const response = await fetch(`/api/barangays/${cityId}`);
                const barangays = await response.json();
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.innerHTML += '<option value="" disabled>──────────</option>';
                barangays.forEach(barangay => {
                    barangaySelect.innerHTML += `<option value="${barangay.barangay_id}" data-barangay-name="${barangay.barangay_name}">${barangay.barangay_name}</option>`;
                });
                barangaySelect.disabled = false;
            } catch (error) {
                console.error('Error loading barangays:', error);
                barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }

        // Update barangay name when selected
        document.getElementById('barangaySelect')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const barangayName = selectedOption?.getAttribute('data-barangay-name') || '';
            document.getElementById('barangayName').value = barangayName;
        });

        // ============ ADD APPLICANT FUNCTIONS ============
        function toggleApplicantForm() {
            const form = document.getElementById('applicantForm');
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
                // Reset form fields
                document.getElementById('new_first_name').value = '';
                document.getElementById('new_middle_name').value = '';
                document.getElementById('new_last_name').value = '';
                document.getElementById('new_contact_number').value = '';
                document.getElementById('new_email').value = '';
            }
        }

        async function createNewApplicant() {
            const firstName = document.getElementById('new_first_name').value.trim();
            const middleName = document.getElementById('new_middle_name').value.trim();
            const lastName = document.getElementById('new_last_name').value.trim();
            const contactNumber = document.getElementById('new_contact_number').value.trim();
            const email = document.getElementById('new_email').value.trim();
            
            if (!firstName) {
                alert('Please enter First Name');
                return;
            }
            if (!lastName) {
                alert('Please enter Last Name');
                return;
            }
            if (!contactNumber) {
                alert('Please enter Contact Number');
                return;
            }
            if (!email) {
                alert('Please enter Email');
                return;
            }
            
            const loadingDiv = document.getElementById('applicantLoading');
            loadingDiv.classList.remove('hidden');
            
            try {
                const response = await fetch('/station-admin/api/create-applicant', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        first_name: firstName,
                        middle_name: middleName,
                        last_name: lastName,
                        contact_number: contactNumber,
                        email: email
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Applicant created successfully!\nAccount Code: ${data.account_code}\nPassword: pyrosense123`);
                    
                    // Add new user to select dropdown
                    const select = document.getElementById('applicantSelect');
                    const option = document.createElement('option');
                    option.value = data.user_id;
                    option.textContent = `${firstName} ${lastName} (${email})`;
                    select.appendChild(option);
                    select.value = data.user_id;
                    
                    // Hide form and reset
                    toggleApplicantForm();
                } else {
                    alert(data.message || 'Failed to create applicant');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating applicant');
            } finally {
                loadingDiv.classList.add('hidden');
            }
        }

        // ============ REVIEW MODAL WITH 3-STEP PROGRESS ============
        async function reviewRequest(id) {
            currentRequestId = id;
            currentStep = 1;
            selectedDecision = null;
            
            try {
                const response = await fetch(`/station-admin/api/fsic/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('reviewRequestId').value = id;
                    document.getElementById('reviewRequestCode').textContent = data.request_code;
                    document.getElementById('reviewDateRequested').textContent = data.date_requested;
                    document.getElementById('reviewBusinessName').textContent = data.business_name;
                    document.getElementById('reviewOwnerName').textContent = data.owner_name;
                    document.getElementById('reviewBusinessType').textContent = data.business_type || 'Not specified';
                    document.getElementById('reviewAddress').textContent = data.building_address;
                    document.getElementById('reviewBarangay').textContent = data.barangay;
                    document.getElementById('reviewCity').textContent = data.city;
                    document.getElementById('reviewProvince').textContent = data.province;
                    document.getElementById('reviewDti').textContent = data.dti_sec_registration_no || 'Not provided';
                    document.getElementById('reviewMayorsPermit').textContent = data.mayors_permit_no || 'Not provided';
                    document.getElementById('reviewTin').textContent = data.tin_number || 'Not provided';
                    
                    // Reset form
                    document.querySelectorAll('input[name="reviewDecision"]').forEach(radio => radio.checked = false);
                    document.getElementById('assignedStationOfficerId').value = '';
                    selectedAssignedPersonnelId = '';
                    
                    updateReviewStep();
                    document.getElementById('reviewModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            } catch (error) {
                alert('Failed to load request details');
            }
        }

        function updateReviewStep() {
            for (let i = 1; i <= 3; i++) {
                const step = document.getElementById(`step${i}`);
                const circle = step?.querySelector('.step-circle');
                if (step) {
                    if (i < currentStep) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                        if (circle) circle.innerHTML = '<i class="fa-solid fa-check"></i>';
                    } else if (i === currentStep) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                        if (circle) circle.innerHTML = i;
                    } else {
                        step.classList.remove('active', 'completed');
                        if (circle) circle.innerHTML = i;
                    }
                }
            }
            
            for (let i = 1; i <= 3; i++) {
                const stepDiv = document.getElementById(`reviewStep${i}`);
                if (stepDiv) {
                    if (i === currentStep) {
                        stepDiv.classList.add('active-step');
                    } else {
                        stepDiv.classList.remove('active-step');
                    }
                }
            }
            
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            if (currentStep === 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'inline-flex';
                submitBtn.style.display = 'none';
            } else if (currentStep === 2) {
                prevBtn.style.display = 'inline-flex';
                nextBtn.style.display = 'inline-flex';
                submitBtn.style.display = 'none';
            } else if (currentStep === 3) {
                prevBtn.style.display = 'inline-flex';
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'inline-flex';
                
                const decision = document.querySelector('input[name="reviewDecision"]:checked')?.value;
                const finalDecisionSpan = document.getElementById('finalDecision');
                const finalIcon = document.getElementById('finalIcon');
                const finalTitle = document.getElementById('finalTitle');
                const finalMessage = document.getElementById('finalMessage');
                const assignedPersonnelWrap = document.getElementById('assignedPersonnelWrap');
                
                if (decision === 'for_inspection') {
                    finalDecisionSpan.innerHTML = '<span class="text-blue-600 font-semibold">Send for Inspection</span>';
                    finalIcon.innerHTML = '<i class="fa-solid fa-clipboard-list text-blue-600 text-3xl"></i>';
                    finalTitle.textContent = 'Ready to Send for Inspection';
                    finalMessage.textContent = 'This request will be forwarded to the inspection team.';
                    assignedPersonnelWrap.style.display = 'block';
                } else if (decision === 'rejected') {
                    finalDecisionSpan.innerHTML = '<span class="text-red-600 font-semibold">Reject Request</span>';
                    finalIcon.innerHTML = '<i class="fa-solid fa-times-circle text-red-600 text-3xl"></i>';
                    finalTitle.textContent = 'Ready to Reject Request';
                    finalMessage.textContent = 'This request will be rejected and the applicant will be notified.';
                    assignedPersonnelWrap.style.display = 'none';
                }
            }
        }

        function nextStep() {
            if (currentStep === 1) {
                currentStep = 2;
                updateReviewStep();
            } else if (currentStep === 2) {
                const decision = document.querySelector('input[name="reviewDecision"]:checked')?.value;
                if (!decision) {
                    alert('Please select a decision');
                    return;
                }
                selectedAssignedPersonnelId = '';
                selectedDecision = decision;
                currentStep = 3;
                updateReviewStep();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateReviewStep();
            }
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            currentStep = 1;
            selectedDecision = null;
            selectedAssignedPersonnelId = '';
            updateReviewStep();
        }

        async function submitReview() {
            const requestId = document.getElementById('reviewRequestId').value;
            const decision = selectedDecision;
            const assignedStationOfficerId = document.getElementById('assignedStationOfficerId').value || selectedAssignedPersonnelId;

            if (decision === 'for_inspection' && !assignedStationOfficerId) {
                alert('Please assign available personnel for inspection');
                return;
            }
            
            try {
                const response = await fetch(`/station-admin/api/fsic/${requestId}/review`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        decision: decision,
                        assigned_station_officer_id: decision === 'for_inspection' ? assignedStationOfficerId : null
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeReviewModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to submit review');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // ============ INSPECTION MODAL ============
        async function openInspectionModal(id) {
            try {
                const response = await fetch(`/station-admin/api/fsic/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('inspectionRequestId').value = id;
                    document.getElementById('inspectionRequestCode').textContent = data.request_code;
                    document.getElementById('inspectionBusinessName').textContent = data.business_name;
                    document.getElementById('inspectionOwnerName').textContent = data.owner_name;
                    
                    document.querySelectorAll('#inspectionModal input[type="checkbox"]').forEach(cb => cb.checked = false);
                    document.getElementById('inspectionRemarks').value = '';
                    document.getElementById('complianceStatus').value = '1';
                    
                    document.getElementById('inspectionModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            } catch (error) {
                alert('Failed to load request details');
            }
        }

        function closeInspectionModal() {
            document.getElementById('inspectionModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        async function submitInspection() {
            const requestId = document.getElementById('inspectionRequestId').value;
            const checklist = {
                fire_protection: document.getElementById('check_fire_protection').checked ? 1 : 0,
                alarm_systems: document.getElementById('check_alarm_systems').checked ? 1 : 0,
                standard_material_used: document.getElementById('check_material_used').checked ? 1 : 0,
                electrical: document.getElementById('check_electrical').checked ? 1 : 0,
                housekeeping: document.getElementById('check_housekeeping').checked ? 1 : 0,
                hazards: document.getElementById('check_hazards').checked ? 1 : 0,
                planning: document.getElementById('check_planning').checked ? 1 : 0
            };
            const remarks = document.getElementById('inspectionRemarks').value;
            const isCompliant = document.getElementById('complianceStatus').value === '1';
            
            if (!remarks) {
                alert('Please enter inspection remarks');
                return;
            }
            
            try {
                const response = await fetch(`/station-admin/api/fsic/${requestId}/inspect`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        checklist: checklist,
                        remarks: remarks,
                        is_compliant: isCompliant
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeInspectionModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to submit inspection');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // ============ APPROVAL MODAL ============
        async function openApprovalModal(id) {
            try {
                const response = await fetch(`/station-admin/api/fsic/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('approvalRequestId').value = id;
                    document.getElementById('approvalRequestCode').textContent = data.request_code;
                    document.getElementById('approvalBusinessName').textContent = data.business_name;
                    document.getElementById('approvalOwnerName').textContent = data.owner_name;
                    document.getElementById('fsicNumber').value = '';
                    document.getElementById('approvalRemarks').value = '';
                    
                    document.getElementById('approvalModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            } catch (error) {
                alert('Failed to load request details');
            }
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        async function submitApproval(issueNow = false) {
            const requestId = document.getElementById('approvalRequestId').value;
            const fsicNumber = document.getElementById('fsicNumber').value;
            const remarks = document.getElementById('approvalRemarks').value;
            
            try {
                const response = await fetch(`/station-admin/api/fsic/${requestId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        fsic_number: fsicNumber,
                        remarks: remarks,
                        issue_now: issueNow
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeApprovalModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve request');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // ============ VIEW REQUEST ============
        async function viewRequest(id) {
            const modal = document.getElementById('viewRequestModal');
            const content = document.getElementById('viewRequestContent');
            const rejectBtn = document.getElementById('viewRejectBtn');
            currentViewRequestId = id;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            rejectBtn.style.display = 'none';
            
            try {
                const response = await fetch(`/station-admin/api/fsic/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const inspectionDetails = data.inspection_details || null;
                    const failedItems = Array.isArray(inspectionDetails?.failed_checklist_items) ? inspectionDetails.failed_checklist_items : [];
                    const failedChecklistMarkup = failedItems.length
                        ? `
                            <div class="mt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-red-600 mb-2">Checklist Items That Failed</p>
                                <div class="flex flex-wrap gap-2">
                                    ${failedItems.map(item => `<span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 text-red-700 text-xs font-medium">${escapeHtml(item.label)}</span>`).join('')}
                                </div>
                            </div>
                        `
                        : `<p class="text-sm text-green-700 font-medium">All checklist items passed.</p>`;

                    const inspectionSection = inspectionDetails
                        ? `
                            <div class="border-t pt-3">
                                <h4 class="font-semibold text-gray-800 mb-2">Inspection Details</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <p><span class="text-gray-500">Inspection Date:</span></p>
                                    <p class="font-medium">${escapeHtml(formatDisplayDate(inspectionDetails.inspection_date) || 'N/A')}</p>
                                    <p><span class="text-gray-500">Inspected By:</span></p>
                                    <p class="font-medium">${escapeHtml(inspectionDetails.inspector_name || 'Not assigned')}</p>
                                    <p><span class="text-gray-500">Inspector Rank:</span></p>
                                    <p class="font-medium">${escapeHtml(inspectionDetails.inspector_rank || 'N/A')}</p>
                                    <p><span class="text-gray-500">Compliant:</span></p>
                                    <p class="font-medium ${Number(inspectionDetails.is_compliant) === 1 ? 'text-green-600' : 'text-red-600'}">${Number(inspectionDetails.is_compliant) === 1 ? 'Yes' : 'No'}</p>
                                </div>
                                <div class="mt-3 text-sm">
                                    <p class="text-gray-500 mb-1">Inspection Remarks:</p>
                                    <p class="font-medium text-gray-800">${escapeHtml(inspectionDetails.inspection_remarks || 'No remarks provided')}</p>
                                </div>
                                ${Number(inspectionDetails.is_compliant) === 1 ? '' : failedChecklistMarkup}
                                <div class="mt-3 text-sm">
                                    <p class="text-gray-500 mb-1">Checklist Remarks:</p>
                                    <p class="font-medium text-gray-800">${escapeHtml(inspectionDetails.checklist_remarks || 'No checklist remarks provided')}</p>
                                </div>
                            </div>
                        `
                        : '';

                    content.innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500">Request Code</p>
                                    <p class="font-bold text-sm">${escapeHtml(data.request_code || 'N/A')}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500">Date Requested</p>
                                    <p class="font-bold text-sm">${data.date_requested || 'N/A'}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500">Status</p>
                                    <p><span class="fsic-status-pill ${data.status_badge || 'bg-gray-100'}">${escapeHtml(String(data.status || 'unknown').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()))}</span></p>
                                </div>
                            </div>
                            
                            <div class="border-t pt-3">
                                <h4 class="font-semibold text-gray-800 mb-2">Business Information</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <p><span class="text-gray-500">Business Name:</span></p>
                                    <p class="font-medium">${escapeHtml(data.business_name || 'N/A')}</p>
                                    <p><span class="text-gray-500">Owner:</span></p>
                                    <p class="font-medium">${escapeHtml(data.owner_name || 'N/A')}</p>
                                    <p><span class="text-gray-500">Business Type:</span></p>
                                    <p class="font-medium">${escapeHtml(data.business_type) || 'Not specified'}</p>
                                </div>
                            </div>
                            
                            <div class="border-t pt-3">
                                <h4 class="font-semibold text-gray-800 mb-2">Location</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <p><span class="text-gray-500">Address:</span></p>
                                    <p class="font-medium">${escapeHtml(data.building_address || 'N/A')}</p>
                                    <p><span class="text-gray-500">Barangay:</span></p>
                                    <p class="font-medium">${escapeHtml(data.barangay || 'N/A')}</p>
                                    <p><span class="text-gray-500">City:</span></p>
                                    <p class="font-medium">${escapeHtml(data.city || 'N/A')}</p>
                                    <p><span class="text-gray-500">Province:</span></p>
                                    <p class="font-medium">${escapeHtml(data.province || 'N/A')}</p>
                                </div>
                            </div>
                            
                            <div class="border-t pt-3">
                                <h4 class="font-semibold text-gray-800 mb-2">Registration Numbers</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <p><span class="text-gray-500">DTI/SEC No.:</span></p>
                                    <p class="font-medium">${escapeHtml(data.dti_sec_registration_no) || 'Not provided'}</p>
                                    <p><span class="text-gray-500">Mayor's Permit:</span></p>
                                    <p class="font-medium">${escapeHtml(data.mayors_permit_no) || 'Not provided'}</p>
                                    <p><span class="text-gray-500">TIN No.:</span></p>
                                    <p class="font-medium">${escapeHtml(data.tin_number) || 'Not provided'}</p>
                                </div>
                            </div>

                            ${inspectionSection}
                        </div>
                    `;

                    if (data.status === 'inspected') {
                        rejectBtn.style.display = 'inline-flex';
                    }
                }
            } catch (error) {
                content.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load details</div>';
            }
        }

        function closeViewModal() {
            document.getElementById('viewRequestModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            currentViewRequestId = null;
            document.getElementById('viewRejectBtn').style.display = 'none';
        }

        async function openCertificateModal(id) {
            const modal = document.getElementById('certificateModal');
            const body = document.getElementById('certificateModalBody');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            body.innerHTML = '<div class="text-center py-8">Loading certificate...</div>';

            try {
                const response = await fetch(`/station-admin/api/fsic/${id}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load certificate data.');
                }

                const approval = data.approval_details || {};
                const issueDate = formatDisplayDateShort(approval.date_issued || approval.approval_date);
                const validUntil = formatDisplayDateShort(approval.valid_until);
                const stationName = approval.station_name || 'Bureau of Fire Protection Station';
                const approverInfo = formatApproverCertificateLabel(approval);
                const businessType = data.business_type ? String(data.business_type).replace(/\b\w/g, c => c.toUpperCase()) : 'Business Permit';

                body.innerHTML = `
                    <div id="certificatePrintArea" class="certificate-sheet">
                        <div class="certificate-header">
                            <div class="certificate-seal">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="certificate-heading">
                                <div class="small">Department of the Interior and Local Government</div>
                                <div class="main">Bureau of Fire Protection</div>
                                <div class="small">National Capital Region</div>
                                <div class="sub mt-2">${escapeHtml(stationName)}</div>
                            </div>
                            <div class="certificate-seal">
                                <i class="fa-solid fa-fire-flame-curved"></i>
                            </div>
                        </div>

                        <div class="certificate-title-row">
                            <div class="certificate-no">FSIC No. ${escapeHtml(approval.fsic_number || 'Pending')}</div>
                            <div class="certificate-date">
                                <div class="font-semibold">${escapeHtml(issueDate || 'Not yet issued')}</div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">Date</div>
                            </div>
                        </div>

                        <div class="certificate-title">Fire Safety Inspection Certificate</div>

                        <div class="certificate-types">
                            <div class="certificate-type-row">
                                <span class="certificate-checkbox">${data.business_type ? 'X' : ''}</span>
                                <span>For Business Permit / Renewal</span>
                            </div>
                            <div class="certificate-type-row">
                                <span class="certificate-checkbox">${!data.business_type ? 'X' : ''}</span>
                                <span>Others</span>
                            </div>
                        </div>

                        <div class="certificate-body">
                            <p>To whom it may concern:</p>
                            <p>
                                By virtue of the provisions of RA 9514 otherwise known as the Fire Code of the Philippines,
                                the application for Fire Safety Inspection Certificate of
                                <span class="certificate-line wide">${escapeHtml(data.business_name || 'N/A')}</span>
                                owned and managed by
                                <span class="certificate-line">${escapeHtml(data.owner_name || 'N/A')}</span>
                                with postal address at
                                <span class="certificate-line address">${escapeHtml([data.building_address, data.barangay, data.city, data.province].filter(Boolean).join(', ') || 'N/A')}</span>
                                is hereby granted after said building structure or facility has been duly inspected with the finding that it has complied with the fire safety and protection requirements of the Fire Code of the Philippines of 2008 and its Revised Implementing Rules and Regulations.
                            </p>
                            <p class="mt-4">
                                This certification is valid for
                                <span class="certificate-line">${escapeHtml(businessType)}</span>
                                and shall remain valid until
                                <span class="certificate-line">${escapeHtml(validUntil || 'Pending claim')}</span>.
                            </p>
                        </div>

                        <div class="certificate-meta">
                            <div class="certificate-meta-block">
                                <div><span class="font-semibold">Request Code:</span> ${escapeHtml(data.request_code || 'N/A')}</div>
                                <div><span class="font-semibold">Approval Date:</span> ${escapeHtml(formatDisplayDateShort(approval.approval_date) || 'N/A')}</div>
                                <div><span class="font-semibold">Date Issued:</span> ${escapeHtml(issueDate || 'Not yet issued')}</div>
                                <div><span class="font-semibold">Approval Remarks:</span> ${escapeHtml(approval.approval_remarks || 'No remarks recorded')}</div>
                            </div>
                            <div class="certificate-sign">
                                <div class="text-sm font-semibold mb-8">Approved By:</div>
                                <div class="certificate-sign-name">${escapeHtml(approverInfo.signName || 'AUTHORIZED APPROVING OFFICER')}</div>
                                <div class="text-sm">${escapeHtml(approverInfo.signTitle || stationName)}</div>
                            </div>
                        </div>

                        <div class="certificate-note">
                            This certificate is generated from the PyroSense FSIC system and reflects the current approval, issuance, and validity records stored in the database.
                        </div>
                    </div>
                `;
            } catch (error) {
                body.innerHTML = `<div class="text-center py-8 text-red-500">${escapeHtml(error.message || 'Failed to load certificate.')}</div>`;
            }
        }

        function closeCertificateModal() {
            document.getElementById('certificateModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function formatDisplayDate(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function formatDisplayDateShort(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function abbreviateFireRank(rank) {
            const value = String(rank || '').trim();
            if (!value) return '';

            const normalized = value.toLowerCase().replace(/\s+/g, ' ');
            const rankMap = {
                'fire officer 1': 'FO1',
                'fire officer 2': 'FO2',
                'fire officer 3': 'FO3',
                'senior fire officer 1': 'SFO1',
                'senior fire officer 2': 'SFO2',
                'senior fire officer 3': 'SFO3',
                'senior fire officer 4': 'SFO4',
                'fire inspector': 'FINSP',
                'fire senior inspector': 'FSINSP',
                'fire chief inspector': 'FCINSP',
                'fire superintendent': 'FSUPT',
                'fire senior superintendent': 'FSSUPT',
                'fire chief superintendent': 'FCSUPT',
                'fire director': 'FDIR',
            };

            return rankMap[normalized] || value.toUpperCase();
        }

        function formatApproverCertificateLabel(approval) {
            const approverName = String(approval?.approver_name || 'Authorized Approving Officer').trim().toUpperCase();
            const approverRole = String(approval?.approver_role || '').trim().toLowerCase();
            const rankAbbr = abbreviateFireRank(approval?.approver_rank);

            if (approverRole === 'chief' || approverRole === 'station admin') {
                const prefix = rankAbbr ? `FIRE MARSHAL / ${rankAbbr}` : 'FIRE MARSHAL';
                return {
                    signName: `${prefix} ${approverName}`.trim(),
                    signTitle: approval?.station_name || 'Bureau of Fire Protection Station',
                };
            }

            return {
                signName: [rankAbbr, approverName].filter(Boolean).join(' ').trim(),
                signTitle: approval?.station_name || 'Bureau of Fire Protection Station',
            };
        }

        async function rejectInspectedRequest(id) {
            if (!confirm('Reject this inspected request?')) {
                return;
            }

            try {
                const response = await fetch(`/station-admin/api/fsic/${id}/review`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        decision: 'rejected'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    closeViewModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to reject request');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        function submitModalReject() {
            if (!currentViewRequestId) {
                return;
            }

            rejectInspectedRequest(currentViewRequestId);
        }

        function printCertificateContent() {
            const certificate = document.getElementById('certificatePrintArea');
            if (!certificate) {
                return;
            }

            const printWindow = window.open('', '_blank', 'width=980,height=1200');
            if (!printWindow) {
                return;
            }

            printWindow.document.write(`
                <html>
                    <head>
                        <title>FSIC Certificate</title>
                        <style>
                            body { font-family: Arial, sans-serif; background: #fff; padding: 24px; }
                            .certificate-sheet { background: #fff; border: 2px solid #94a3b8; box-shadow: inset 0 0 0 1px #e5e7eb; padding: 28px 34px; color: #1f2937; }
                            .certificate-header { display: grid; grid-template-columns: 84px 1fr 84px; gap: 12px; align-items: start; padding-bottom: 16px; border-bottom: 1px solid #94a3b8; }
                            .certificate-seal { width: 74px; height: 74px; border-radius: 999px; border: 4px solid #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #b45309; margin: 0 auto; background: #fff7ed; }
                            .certificate-heading { text-align: center; line-height: 1.35; }
                            .certificate-heading .small { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
                            .certificate-heading .main { font-size: 0.98rem; font-weight: 800; text-transform: uppercase; }
                            .certificate-heading .sub { font-size: 0.82rem; }
                            .certificate-title-row { display: flex; justify-content: space-between; align-items: end; gap: 16px; padding: 16px 0 10px; border-bottom: 1px solid #94a3b8; }
                            .certificate-no { font-size: 0.95rem; font-weight: 800; color: #dc2626; }
                            .certificate-date { text-align: right; font-size: 0.82rem; }
                            .certificate-title { text-align: center; font-size: 1.8rem; font-weight: 800; letter-spacing: 0.03em; color: #475569; padding: 16px 0 12px; }
                            .certificate-types { display: flex; flex-direction: column; gap: 6px; width: fit-content; margin: 0 auto 18px; font-size: 0.83rem; }
                            .certificate-type-row { display: flex; align-items: center; gap: 10px; }
                            .certificate-checkbox { width: 20px; height: 20px; border: 1px solid #64748b; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #0f172a; }
                            .certificate-body { font-size: 0.9rem; line-height: 1.8; border-top: 1px solid #94a3b8; border-bottom: 1px solid #94a3b8; padding: 18px 0; }
                            .certificate-line { display: inline-block; min-width: 180px; border-bottom: 1px solid #475569; font-weight: 700; text-align: center; line-height: 1.3; }
                            .certificate-line.address { min-width: 280px; }
                            .certificate-line.wide { min-width: 320px; }
                            .certificate-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding-top: 18px; }
                            .certificate-meta-block { font-size: 0.82rem; line-height: 1.8; }
                            .certificate-sign { text-align: center; padding-top: 18px; }
                            .certificate-sign-name { font-weight: 800; text-transform: uppercase; border-top: 1px solid #475569; display: inline-block; padding-top: 6px; min-width: 240px; }
                            .certificate-note { margin-top: 18px; padding-top: 12px; border-top: 1px solid #cbd5e1; font-size: 0.76rem; color: #475569; text-align: center; font-style: italic; }
                        </style>
                    </head>
                    <body>${certificate.outerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        // ============ NEW REQUEST MODAL ============
        function openNewRequestModal() {
            // Reset form
            document.getElementById('newRequestForm').reset();
            document.getElementById('provinceSelect').value = '';
            document.getElementById('citySelect').innerHTML = '<option value="">Select City First</option>';
            document.getElementById('citySelect').disabled = true;
            document.getElementById('barangaySelect').innerHTML = '<option value="">Select Barangay</option>';
            document.getElementById('barangaySelect').disabled = true;
            document.getElementById('provinceName').value = '';
            document.getElementById('cityName').value = '';
            document.getElementById('barangayName').value = '';
            
            document.getElementById('newRequestModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeNewRequestModal() {
            document.getElementById('newRequestModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('newRequestForm').reset();
            // Hide applicant form if visible
            const applicantForm = document.getElementById('applicantForm');
            if (applicantForm.style.display === 'block') {
                applicantForm.style.display = 'none';
            }
        }

        async function submitNewRequest() {
            const form = document.getElementById('newRequestForm');
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            // Get location names from hidden fields
            data.province = document.getElementById('provinceName').value;
            data.city = document.getElementById('cityName').value;
            data.barangay = document.getElementById('barangayName').value;
            
            // Generate request code
            const requestCode = 'FSIC-' + new Date().getFullYear() + '-' + Math.random().toString(36).substring(2, 8).toUpperCase();
            data.request_code = requestCode;
            data.date_requested = new Date().toISOString().split('T')[0];
            data.status = 'pending';
            
            if (!data.user_id) {
                alert('Please select an applicant');
                return;
            }
            
            if (!data.business_name || !data.owner_name || !data.building_address || !data.barangay || !data.city || !data.province) {
                alert('Please fill in all required fields');
                return;
            }
            
            try {
                const response = await fetch('/station-admin/api/fsic', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('FSIC request submitted successfully!');
                    closeNewRequestModal();
                    location.reload();
                } else {
                    alert(result.message || 'Failed to submit request');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Make functions globally available
        window.reviewRequest = reviewRequest;
        window.viewRequest = viewRequest;
        window.openInspectionModal = openInspectionModal;
        window.openApprovalModal = openApprovalModal;
        window.openNewRequestModal = openNewRequestModal;
        window.closeNewRequestModal = closeNewRequestModal;
        window.closeViewModal = closeViewModal;
        window.rejectInspectedRequest = rejectInspectedRequest;
        window.submitModalReject = submitModalReject;
        window.openCertificateModal = openCertificateModal;
        window.closeCertificateModal = closeCertificateModal;
        window.printCertificateContent = printCertificateContent;
        window.closeReviewModal = closeReviewModal;
        window.closeInspectionModal = closeInspectionModal;
        window.closeApprovalModal = closeApprovalModal;
        window.nextStep = nextStep;
        window.prevStep = prevStep;
        window.submitReview = submitReview;
        window.submitInspection = submitInspection;
        window.submitApproval = submitApproval;
        window.submitNewRequest = submitNewRequest;
        window.loadCities = loadCities;
        window.loadBarangays = loadBarangays;
        window.toggleApplicantForm = toggleApplicantForm;
        window.createNewApplicant = createNewApplicant;
        window.emergencyCall = emergencyCall;
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>
