<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense · Management</title>
    <style>
         aside.legacy-chief-sidebar .nav-item i {
            width: 20px;
            font-size: 16px;
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

        .fa-brands,
        .fab,
        .fa-brands::before,
        .fab::before {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
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

        .management-station-hero {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            margin: 20px 0 24px;
            padding: 28px 30px;
            border-radius: 26px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffdf7 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
        }

        .management-station-copy {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .management-eyebrow {
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 800;
        }

        .management-station-hero h2 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.05;
            color: #0f172a;
            font-weight: 800;
        }

        .management-station-subcopy {
            margin: 0;
            color: #64748b;
            font-size: 0.98rem;
        }

        .management-station-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 4px;
        }

        .management-station-meta span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(245, 158, 11, 0.1);
            color: #9a3412;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .management-station-badge {
            min-width: 180px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid rgba(245, 158, 11, 0.16);
            text-align: right;
        }

        .management-station-badge-label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .management-station-badge strong {
            color: #16a34a;
            font-size: 1rem;
            font-weight: 800;
        }
        .management-station-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }
        .management-station-actions .btn-outline {
            min-width: 150px;
        }
        .station-media-panel {
            display: grid;
            grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }
        .station-media-profile {
            border-radius: 22px;
            overflow: hidden;
            min-height: 260px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c2410c;
        }
        .station-media-profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .station-media-profile i {
            font-size: 3rem;
        }
        .station-media-tools {
            display: grid;
            gap: 18px;
        }
        .station-media-card {
            padding: 18px;
            border-radius: 20px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
        }
        .station-media-card h3 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
        }
        .station-media-card p {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }
        .station-media-card input[type="file"],
        .station-media-card input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #fff;
            margin-bottom: 12px;
        }
        .station-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
        }
        .station-gallery-item {
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .station-gallery-item img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            display: block;
        }
        .station-gallery-copy {
            padding: 12px;
            color: #475569;
            font-size: 0.82rem;
            line-height: 1.5;
        }
        .station-gallery-actions {
            padding: 0 12px 12px;
        }
        .station-flash {
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .station-flash.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .station-flash.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .lector-top-bar {
                top: 12px;
                margin: 12px 14px 0;
                padding: 0 16px;
                border-radius: 20px;
            }

            .management-station-hero {
                flex-direction: column;
                padding: 22px 18px;
                margin-top: 16px;
            }

            .management-station-hero h2 {
                font-size: 1.55rem;
            }

            .management-station-badge {
                width: 100%;
                text-align: left;
            }
            .station-media-panel {
                grid-template-columns: 1fr;
                padding: 20px 18px;
            }
        }

        .management-container {
            padding: 6px 24px 32px;
            display: flex;
            flex-direction: column;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 16px;
            padding: 28px 30px;
            border-radius: 26px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
        }

        .management-container > .management-station-hero { order: 1; }
        .management-container > .page-header { order: 2; display: none; }
        .management-container > .stats-grid { order: 3; }
        .management-container > .tab-navigation { order: 4; }
        .management-container > .search-filter-bar { order: 5; }
        .management-container > .loading-state,
        .management-container > .table-card { order: 6; }

        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .page-header h1 i,
        .table-header h2 i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }

        .page-header .date {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(245, 158, 11, 0.12);
            color: #9a3412;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .alert-banner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding: 18px 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.92) 0%, rgba(254, 252, 232, 0.96) 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .alert-banner > i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);
            color: #fff;
            flex-shrink: 0;
        }

        .alert-banner .content {
            color: #7c2d12;
            line-height: 1.55;
            font-size: 0.96rem;
            flex: 1;
        }

        .alert-banner .date {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(245, 158, 11, 0.12);
            color: #9a3412;
            white-space: nowrap;
            font-size: 0.83rem;
            font-weight: 700;
        }

        .tab-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
            padding: 16px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: transparent;
            color: #64748b;
            font-size: 0.92rem;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .tab-btn i {
            color: #94a3b8;
        }

        .tab-btn:hover {
            background: #fff7ed;
            color: #c2410c;
            border-color: rgba(245, 158, 11, 0.14);
        }

        .tab-btn:hover i,
        .tab-btn.active i {
            color: inherit;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(209, 75, 36, 0.18);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 24px 22px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
        }

        .stat-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #cc4c1d 0%, #dd8a11 100%) !important;
            color: #fff;
            box-shadow: 0 18px 30px rgba(221, 138, 17, 0.22);
        }

        .stat-icon i {
            font-size: 1.2rem;
            color: #fff !important;
        }

        .stat-info h3 {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 0.8rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .stat-number {
            color: #0f172a;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-change {
            margin-top: 8px;
            color: #9a3412;
            font-weight: 700;
            font-size: 0.84rem;
        }

        .stat-change.positive {
            color: #16a34a;
        }

        .search-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            padding: 18px 20px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fff9f2 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            flex: 1 1 320px;
            padding: 0 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .search-box i {
            color: #d97706;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-select,
        .btn-filter,
        .btn-primary,
        .btn-outline {
            min-height: 46px;
            border-radius: 16px !important;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .filter-select {
            min-width: 148px;
            padding: 0 14px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #475569;
        }

        .btn-filter {
            padding: 0 16px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff7ed;
            color: #b45309;
        }

        .btn-filter:hover,
        .btn-outline:hover {
            background: #ffedd5;
            border-color: rgba(245, 158, 11, 0.24);
        }

        .btn-primary {
            padding: 0 18px;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%) !important;
            border: 0 !important;
            color: #fff !important;
            box-shadow: 0 16px 28px rgba(221, 138, 17, 0.24);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 34px rgba(221, 138, 17, 0.32);
        }

        .btn-outline {
            padding: 0 16px;
            border: 1px solid rgba(245, 158, 11, 0.16) !important;
            background: #fff !important;
            color: #b45309 !important;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff;
            color: #b45309;
            transition: all 0.22s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            background: #fff7ed;
            border-color: rgba(245, 158, 11, 0.24);
        }

        .action-btn.delete {
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.14);
        }

        .action-btn.delete:hover {
            background: #fef2f2;
            border-color: rgba(220, 38, 38, 0.22);
        }

        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(8px);
            z-index: 3200;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            width: min(760px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border-radius: 28px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.16);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
        }

        .modal-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            border-radius: 14px;
            background: #fff;
            color: #b45309;
        }

        .modal-body {
            padding: 22px 24px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 24px 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #9a3412;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff;
            color: #334155;
            padding: 14px 16px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(217, 119, 6, 0.4);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
        }

        .detail-container .status-badge-large,
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #b45309;
            font-size: 0.8rem;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .loading-state,
        .table-card {
            border-radius: 28px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.05);
        }

        .loading-state {
            margin-bottom: 18px;
            padding: 42px 24px;
        }

        .loading-spinner {
            border-top-color: #d97706 !important;
            border-right-color: rgba(245, 158, 11, 0.18) !important;
            border-bottom-color: rgba(245, 158, 11, 0.18) !important;
            border-left-color: rgba(245, 158, 11, 0.18) !important;
        }

        .table-header {
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
        }

        .table-header h2 {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .table-responsive {
            padding: 0 8px 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            padding: 18px 18px;
            background: #fffaf0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .data-table thead th:first-child {
            border-top-left-radius: 18px;
        }

        .data-table thead th:last-child {
            border-top-right-radius: 18px;
        }

        .data-table tbody td {
            padding: 18px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            color: #334155;
            font-size: 0.94rem;
            background: rgba(255, 255, 255, 0.88);
        }

        .data-table tbody tr:hover td {
            background: #fff7ed;
        }

        .empty-state {
            padding: 52px 24px !important;
            text-align: center;
            color: #64748b;
        }

        .empty-state i {
            font-size: 2rem;
            color: #f59e0b;
            margin-bottom: 12px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 24px 24px;
            color: #64748b;
        }

        .pagination-info {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination-btn {
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            background: #fff;
            color: #b45309;
            font-weight: 700;
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            border-color: transparent;
            color: #fff;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .management-container {
                padding: 6px 14px 24px;
            }

            .page-header,
            .alert-banner,
            .search-filter-bar,
            .pagination {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions {
                width: 100%;
            }

            .filter-select,
            .btn-filter,
            .btn-primary,
            .btn-outline {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 24px 18px;
            }

            .page-header h1 {
                font-size: 1.6rem;
            }

            .tab-navigation {
                padding: 12px;
            }

            .tab-btn {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/management.css'])
    @vite(['resources/css/admin/resource-management.css'])

</head>
@php
    $currentStation = ($stations ?? collect())->first();
    $currentStationMarker = $currentStation?->marker;
    $currentRoleName = strtolower(auth()->user()->role->role ?? '');
    $currentStationProfilePath = $currentStation?->profileMedia?->path;
    $currentStationProfileUrl = $currentStationProfilePath ? asset('storage/' . ltrim(str_replace('\\', '/', $currentStationProfilePath), '/')) : null;
    $currentStationGallery = collect($currentStation?->galleryMedia ?? [])->map(function ($media) {
        return [
            'id' => $media->station_media_id,
            'caption' => $media->caption,
            'url' => asset('storage/' . ltrim(str_replace('\\', '/', (string) $media->path), '/')),
        ];
    });
@endphp
<body class="chief-legacy-layout">
    @include('chief.partials.sidebar', ['active' => 'management'])
    <aside class="sidebar legacy-chief-sidebar" style="display:none;">
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
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item "><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 2 }}</span>
                        </a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('station-admin.management') }}" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i><span>Management</span></a>
                <a href="{{ route('station-admin.reports') }}" class="nav-item"><i class="fa-solid fa-chart-pie"></i><span>Reports</span></a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
                
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item"><i class="fa-solid fa-chart-line"></i><span>Predictive Analytics</span></a>
                {{-- <a href="#" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
                <a href="#" class="nav-item"><i class="fa-solid fa-bell"></i><span>Alerts</span></a> --}}
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
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
                    <h1 class="text-xl font-bold text-slate-800">Station Management</h1>
                    <p class="text-xs text-slate-500 font-medium">Command Resources And Registry • {{ now()->format('l, F j') }}</p>
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

        <!-- MANAGEMENT CONTENT -->
        <div class="management-container">
            @if(session('success'))
                <div class="station-flash success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="station-flash error">{{ $errors->first() }}</div>
            @endif

            <div class="management-station-hero">
                <div class="management-station-copy">
                    <p class="management-eyebrow">Current Command Station</p>
                    <h2>{{ $currentStation?->station_name ?? 'Station Not Assigned' }}</h2>
                    <p class="management-station-subcopy">
                        {{ $currentStation?->station_code ? 'Code: '.$currentStation->station_code.' • ' : '' }}
                        {{ $currentStationMarker?->title ?? 'No mapped station location available yet.' }}
                    </p>
                    <div class="management-station-meta">
                        <span><i class="fa-solid fa-phone"></i> {{ $currentStation?->contact_number ?? 'No contact number' }}</span>
                        <span><i class="fa-solid fa-envelope"></i> {{ $currentStation?->email ?? 'No email registered' }}</span>
                        <span><i class="fa-solid fa-users"></i> {{ $personnel->count() }} personnel records loaded</span>
                    </div>
                </div>
                <div class="management-station-badge">
                    <span class="management-station-badge-label">Station Status</span>
                    <strong>Operational</strong>
                    @if($currentRoleName === 'chief' && $currentStation)
                        <div class="management-station-actions">
                            <button type="button" class="btn-outline" id="editStationBtn">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Edit Station
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            @if($currentStation && in_array($currentRoleName, ['chief', 'station admin'], true))
                <section class="station-media-panel">
                    <div class="station-media-profile">
                        @if($currentStationProfileUrl)
                            <img src="{{ $currentStationProfileUrl }}" alt="{{ $currentStation->station_name }}">
                        @else
                            <i class="fa-solid fa-building-shield"></i>
                        @endif
                    </div>
                    <div class="station-media-tools">
                        <div class="station-media-card">
                            <h3>Station Profile Image</h3>
                            <p>Upload one main image for the station. This becomes the hero image shown on firefighter My Station.</p>
                            <form method="POST" action="{{ route('station-admin.stations.media.profile', $currentStation->station_id) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="profile_image" accept="image/*" required>
                                <button type="submit" class="btn-primary">
                                    <i class="fa-solid fa-upload"></i>
                                    Save Profile Image
                                </button>
                            </form>
                        </div>

                        <div class="station-media-card">
                            <h3>Station Gallery</h3>
                            <p>Add supporting photos for the station grounds, apparatus bay, office, or other station-related visuals.</p>
                            <form method="POST" action="{{ route('station-admin.stations.media.gallery', $currentStation->station_id) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="gallery_images[]" accept="image/*" multiple required>
                                <input type="text" name="caption" placeholder="Optional caption for this upload batch">
                                <button type="submit" class="btn-outline">
                                    <i class="fa-solid fa-images"></i>
                                    Add Gallery Photos
                                </button>
                            </form>
                        </div>

                        <div class="station-media-card">
                            <h3>Current Gallery</h3>
                            <p>These images are visible to firefighters when they open the station page.</p>
                            @if($currentStationGallery->isEmpty())
                                <p>No gallery images uploaded yet.</p>
                            @else
                                <div class="station-gallery-grid">
                                    @foreach($currentStationGallery as $photo)
                                        <div class="station-gallery-item">
                                            <img src="{{ $photo['url'] }}" alt="Station photo {{ $loop->iteration }}">
                                            <div class="station-gallery-copy">{{ $photo['caption'] ?: 'Station photo #' . $loop->iteration }}</div>
                                            <div class="station-gallery-actions">
                                                <form method="POST" action="{{ route('station-admin.station-media.destroy', $photo['id']) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-outline" style="width:100%;">
                                                        <i class="fa-solid fa-trash"></i>
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-table"></i>
                    Station Registry
                </h1>
                <div class="date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="incidents">
                    <i class="fa-solid fa-fire"></i>
                    Incidents
                </button>
                <button class="tab-btn" data-tab="reports">
                    <i class="fa-solid fa-file-lines"></i>
                    Reports
                </button>
                <button class="tab-btn" data-tab="personnel">
                    <i class="fa-solid fa-users"></i>
                    Personnel
                </button>
                <button class="tab-btn" data-tab="equipment">
                    <i class="fa-solid fa-toolbox"></i>
                    Equipment
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Incidents</h3>
                        <div class="stat-number" id="totalIncidents">{{$totalIncidents->count() ?? 0 }}</div>
                        <div class="stat-change positive" id="activeIncidents">0 active</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reports</h3>
                        <div class="stat-number" id="totalReports">{{ $reports->count() ?? 0 }}</div>
                        <div class="stat-change" id="pendingReports">0 filed</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Personnel</h3>
                        <div class="stat-number" id="totalPersonnel">{{ $personnel->count() ?? 0 }}</div>
                        <div class="stat-change positive">station roster</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-toolbox"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Equipment</h3>
                        <div class="stat-number" id="totalEquipment">{{ $equipment->count() ?? 0 }}</div>
                        <div class="stat-change positive">station assigned</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search...">
                </div>
                <div class="filter-actions">
                    <select class="filter-select" id="filterSelect">
                        <option value="">All Status</option>
                    </select>
                    <select class="filter-select" id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    <button class="btn-filter" id="refreshBtn">
                        <i class="fa-solid fa-rotate"></i>
                        Refresh
                    </button>
                    <button class="btn-outline" id="exportBtn">
                        <i class="fa-solid fa-download"></i>
                        Export
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading data...</p>
            </div>

            <!-- Data Table -->
            <div class="table-card" id="dataTable">
                <div class="table-header">
                    <h2>
                        <i class="fa-solid fa-list"></i>
                        <span id="tableTitle">Incidents</span>
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead id="tableHeader">
                            <tr>
                                {{-- <th>ID</th> --}}
                                {{-- <th>Title</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Actions</th> --}}
                            </tr>
                        </thead>
                       <script>
                            // Pass data to JavaScript
                               window.rolesData = @json($roles);
                            window.incidentDb = @json($incidents);
                            window.reportDb = @json($reports);
                            window.personnelDb = @json($personnel);
                            window.equipmentDb = @json($equipment);
                            window.stationDb = @json($stations);
                            window.currentStationData = @json($currentStation);
                            window.csrfToken = '{{ csrf_token() }}';
                        </script>
                            <tbody id="tableBody">
                            
                           {{-- @if(!$totalIncidents)
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h3>No data available</h3>
                                    <p>Click refresh to load data or add new entries.</p>
                                </td>
                            </tr>
                           @endif --}}
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="pagination">
                    <div class="pagination-info">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>View Details</h2>
                <button class="modal-close" id="viewModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="management.closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form Modal -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Item</h2>
                <button class="modal-close" id="formModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="dataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" class="form-input" placeholder="Enter title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                                                  <i class="fa-solid fa-align-left"></i>
                            Description
                        </label>
                        <textarea class="form-textarea" placeholder="Enter description" rows="4"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-tag"></i>
                                Status
                            </label>
                            <select class="form-select">
                                <option>Active</option>
                                <option>Pending</option>
                                <option>Completed</option>
                                <option>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-calendar"></i>
                                Date
                            </label>
                            <input type="date" class="form-input">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelFormBtn">Cancel</button>
                <button class="btn-primary" id="saveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button class="modal-close" id="deleteModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="text-align: center; margin: 20px 0;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #dc2626; margin-bottom: 16px;"></i>
                    <br>
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelDeleteBtn">Cancel</button>
                <button class="btn-primary" style="background: #dc2626;" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    @if($currentRoleName === 'chief' && $currentStation)
    <div id="editStationModal" class="modal">
        <div class="modal-content" style="max-width: 720px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-building"></i> Edit Station Information</h2>
                <button class="modal-close" id="editStationModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="editStationForm">
                <div class="modal-body">
                    <input type="hidden" id="editStationId" value="{{ $currentStation->station_id }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-building"></i> Station Name</label>
                            <input type="text" class="form-input" id="editStationName" value="{{ $currentStation->station_name }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-barcode"></i> Station Code</label>
                            <input type="text" class="form-input" id="editStationCode" value="{{ $currentStation->station_code }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number</label>
                            <input type="text" class="form-input" id="editStationContactNumber" value="{{ $currentStation->contact_number }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address</label>
                            <input type="email" class="form-input" id="editStationEmail" value="{{ $currentStation->email }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" id="cancelEditStationBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="saveStationBtn">Save Station</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    {{-- equipment modal --}}
     <div id="ecatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Equipment Category</h2>
                <button class="modal-close" id="ecatModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="ecatDataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" name="category_name" class="form-input" placeholder="Enter title">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="ecatCancelBtn">Cancel</button>
                <button class="btn-primary" id="ecatSaveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script>
        // Mobile sidebar toggle
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
    @vite(['resources/js/chief/management.js'])
</body>
</html>
