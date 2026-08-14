<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PyroSense · FSIC Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    <style>
        body {
            background: #fbf7ef;
            color: #1f2937;
        }

        .fsic-shell {
            display: flex;
            min-height: 100vh;
        }

        .fsic-main {
            width: calc(100% - var(--ff-sidebar-w, 300px));
        }

        .fsic-content {
            width: 100%;
        }

        .fsic-page-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .fsic-mobile-title {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 22px;
        }

        .fsic-page-title h1 {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 800;
            color: #172033;
        }

        .fsic-page-title p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 0.92rem;
        }

        .fsic-mobile-count {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: #fff7f3;
            border: 1px solid rgba(198, 61, 38, 0.16);
            color: #c63d26;
            font-weight: 800;
        }

        .fsic-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .fsic-kpi {
            background: #fff;
            border: 1px solid rgba(31, 41, 55, 0.08);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }

        .fsic-kpi-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .fsic-kpi-label {
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .fsic-kpi-label-short {
            display: none;
        }

        .fsic-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            color: #c63d26;
        }

        .fsic-kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .fsic-card {
            background: #fff;
            border: 1px solid rgba(31, 41, 55, 0.08);
            border-radius: 22px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }

        .fsic-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 20px 22px 14px;
        }

        .fsic-card-title {
            font-size: 1.08rem;
            font-weight: 800;
            color: #172033;
        }

        .fsic-card-subtitle {
            margin-top: 4px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .fsic-table-wrap {
            overflow-x: auto;
            padding: 0 22px 22px;
        }

        .fsic-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fsic-table th {
            text-align: left;
            padding: 12px 14px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        .fsic-table td {
            padding: 15px 14px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            font-size: 0.92rem;
            color: #334155;
            vertical-align: middle;
        }

        .fsic-table tbody tr {
            cursor: pointer;
            transition: background 0.18s ease;
        }

        .fsic-table tbody tr:hover {
            background: #fffaf4;
        }

        .fsic-row-title {
            font-weight: 700;
            color: #172033;
        }

        .fsic-row-meta {
            margin-top: 4px;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .status-for_inspection {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-inspected {
            background: #ede9fe;
            color: #7c3aed;
        }

        .status-approved {
            background: #dcfce7;
            color: #15803d;
        }

        .status-default {
            background: #e5e7eb;
            color: #4b5563;
        }

        .table-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 12px;
            border: 0;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .table-action.inspect {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
        }

        .table-action.view {
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .table-action:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .empty-state {
            padding: 44px 22px 30px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            color: #cbd5e1;
        }

        .fsic-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.52);
            z-index: 2000;
            padding: 20px;
        }

        .fsic-modal.active {
            display: flex;
        }

        .fsic-modal-panel {
            width: min(840px, 100%);
            max-height: calc(100vh - 40px);
            overflow: hidden;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 54px rgba(15, 23, 42, 0.28);
        }

        .fsic-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .fsic-modal-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #172033;
        }

        .fsic-modal-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .fsic-close-btn {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 999px;
            background: #fff7f3;
            color: #c63d26;
            cursor: pointer;
        }

        .fsic-modal-body {
            padding: 22px 24px;
            overflow-y: auto;
            max-height: calc(100vh - 220px);
        }

        .fsic-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .fsic-detail-card {
            padding: 14px 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff0e8 0%, #fff8ee 100%);
            border: 1px solid rgba(198, 61, 38, 0.1);
        }

        .fsic-detail-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9a3412;
            margin-bottom: 6px;
        }

        .fsic-detail-value {
            font-size: 0.92rem;
            line-height: 1.45;
            color: #334155;
            word-break: break-word;
        }

        .progress-steps {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .progress-step-circle {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .progress-step.active .progress-step-circle,
        .progress-step.completed .progress-step-circle {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
        }

        .progress-step-text {
            font-size: 0.84rem;
            font-weight: 700;
            color: #64748b;
        }

        .progress-step.active .progress-step-text,
        .progress-step.completed .progress-step-text {
            color: #172033;
        }

        .inspection-step {
            display: none;
        }

        .inspection-step.active {
            display: block;
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .checklist-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            background: #fff;
        }

        .checklist-item input[type="checkbox"] {
            margin-top: 2px;
            width: 18px;
            height: 18px;
            accent-color: #c63d26;
        }

        .checklist-item-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #172033;
        }

        .checklist-item-help {
            margin-top: 4px;
            font-size: 0.78rem;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
        }

        .form-group textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
            padding: 14px 15px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            outline: none;
        }

        .compliance-banner {
            padding: 18px 20px;
            border-radius: 18px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }

        .compliance-banner.pass {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #166534;
        }

        .compliance-banner.fail {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .compliance-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .summary-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
            margin-bottom: 18px;
        }

        .summary-item {
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .summary-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 0.92rem;
            color: #334155;
            line-height: 1.45;
        }

        .fsic-modal-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 16px 24px 22px;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }

        .btn-row {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
        }

        .btn-outline {
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .btn-muted {
            background: #f8fafc;
            color: #475569;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 2100;
            display: none;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 14px;
            color: #fff;
            font-size: 0.9rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
        }

        .toast.show {
            display: inline-flex;
        }

        .toast.success {
            background: #16a34a;
        }

        .toast.error {
            background: #dc2626;
        }

        .toast.info {
            background: #2563eb;
        }

        @media (max-width: 1024px) {
            .fsic-main {
                margin-left: 0;
                width: 100%;
                padding: 18px;
            }

            .fsic-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            html {
                scroll-padding-top: 0;
            }

            body.firefighter-portal-shell {
                background: #fbf7ef !important;
            }

            .fsic-shell {
                min-height: auto;
                display: block;
            }

            .fsic-shell > .ff-mobile-bottom-spacer {
                display: none !important;
                height: 0 !important;
            }

            .fsic-main {
                min-height: auto !important;
                padding: max(8px, var(--ff-safe-top)) 10px calc(var(--ff-mobile-dock-h, var(--ff-mobile-bottom-h, 84px)) + 20px) !important;
                overflow-x: hidden;
            }

            .fsic-content {
                max-width: 430px;
                margin: 0 auto;
                display: grid;
                gap: 10px;
            }

            .fsic-mobile-title {
                display: flex;
            }

            .fsic-page-actions {
                display: none;
            }

            .fsic-page-actions .fsic-page-title {
                display: none;
            }

            .fsic-page-actions .btn {
                width: 44px;
                height: 44px;
                padding: 0;
                border-radius: 14px;
            }

            .fsic-page-actions .btn span {
                display: none;
            }

            .fsic-kpis {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 6px;
                margin-bottom: 0;
            }

            .fsic-kpi {
                min-width: 0;
                border-radius: 14px;
                padding: 8px 5px;
                box-shadow: none;
                display: grid;
                align-content: center;
                gap: 4px;
            }

            .fsic-kpi-head {
                justify-content: center;
                margin-bottom: 0;
            }

            .fsic-kpi-label {
                display: block;
                max-width: 100%;
                overflow: hidden;
                text-align: center;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 0.52rem;
                letter-spacing: 0.02em;
            }

            .fsic-kpi-label-full {
                display: none;
            }

            .fsic-kpi-label-short {
                display: inline;
            }

            .fsic-kpi-icon {
                display: none;
            }

            .fsic-kpi-value {
                text-align: center;
                font-size: 1.08rem;
            }

            .fsic-card {
                border-radius: 22px;
                box-shadow: none;
                overflow: hidden;
                min-height: 0;
            }

            .fsic-card-header {
                padding: 14px 14px 8px;
            }

            .fsic-card-title {
                font-size: 1rem;
            }

            .fsic-card-subtitle {
                display: none;
            }

            .fsic-table-wrap {
                overflow: visible;
                padding: 0 10px 12px;
                min-height: 0;
            }

            .fsic-table,
            .fsic-table tbody,
            .fsic-table tr,
            .fsic-table td {
                display: block;
                width: 100%;
            }

            .fsic-table thead {
                display: none;
            }

            .fsic-table tbody {
                display: grid;
                gap: 10px;
            }

            .fsic-table tbody tr {
                padding: 11px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                border-radius: 18px;
                background: #fffdfa;
            }

            .fsic-table td {
                border-bottom: 0;
                padding: 4px 0;
                font-size: 0.82rem;
            }

            .fsic-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 2px;
                font-size: 0.62rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .fsic-table td:first-child::before,
            .fsic-table td:nth-child(2)::before {
                display: none;
            }

            .fsic-row-title {
                font-size: 0.88rem;
                line-height: 1.2;
            }

            .fsic-row-meta {
                font-size: 0.7rem;
            }

            .status-pill {
                padding: 5px 9px;
                font-size: 0.68rem;
            }

            .table-action {
                width: 100%;
                min-height: 42px;
                border-radius: 14px;
                margin-top: 6px;
                font-size: 0.8rem;
            }

            .empty-state {
                padding: 22px 12px;
                font-size: 0.82rem;
            }

            .empty-state i {
                font-size: 2rem;
            }

            .fsic-modal {
                align-items: flex-end;
                padding: 0 10px calc(var(--ff-mobile-dock-h, var(--ff-mobile-bottom-h, 84px)) + 8px);
                z-index: 2600;
            }

            .fsic-modal-panel {
                width: min(100%, 430px);
                max-height: min(76dvh, 620px);
                border-radius: 24px;
                box-shadow: 0 -16px 34px rgba(15, 23, 42, 0.18);
            }

            .fsic-modal-header {
                padding: 16px 16px 12px;
                align-items: center;
            }

            .fsic-modal-title {
                font-size: 1rem;
            }

            .fsic-modal-subtitle {
                font-size: 0.74rem;
            }

            .fsic-close-btn {
                width: 44px;
                height: 44px;
                flex: 0 0 auto;
            }

            .fsic-modal-body {
                padding: 14px 16px;
                max-height: calc(76dvh - 150px);
            }

            .fsic-modal-footer {
                padding: 12px 16px 16px;
                gap: 8px;
            }

            .fsic-kpis,
            .fsic-detail-grid,
            .checklist-grid,
            .summary-list {
                grid-template-columns: 1fr;
            }

            .fsic-content .fsic-kpis {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .btn-row {
                margin-left: 0;
                width: 100%;
                flex-direction: row;
            }

            .btn-row .btn {
                width: 100%;
                min-height: 44px;
            }
        }

        @media (max-width: 360px) {
            .fsic-main {
                padding-inline: 8px !important;
            }

            .fsic-content .fsic-kpis {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .fsic-page-title h1 {
                font-size: 1.28rem;
            }

            .fsic-page-title p {
                font-size: 0.76rem;
            }

            .fsic-mobile-title {
                padding: 13px 14px;
                border-radius: 20px;
            }

            .fsic-card {
                border-radius: 20px;
            }

            .fsic-table-wrap {
                padding-inline: 8px;
            }

            .fsic-kpi {
                padding: 7px 3px;
            }

            .fsic-kpi-label {
                font-size: 0.48rem;
            }

            .fsic-kpi-value {
                font-size: 1rem;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    <div class="fsic-shell">
        @include('firefighters.partials.shell-sidebar', ['active' => 'fsic'])

        <main class="main-content fsic-main">
            @include('firefighters.partials.shell-topbar', [
                'title' => 'FSIC Requests',
                'subtitle' => 'Assigned fire safety inspection requests'
            ])

            <div class="requests-shell fsic-content">
            <section class="fsic-mobile-title" aria-label="FSIC request summary">
                <div class="fsic-page-title">
                    <h1>FSIC Requests</h1>
                    <p>Assigned inspections</p>
                </div>
                <span class="fsic-mobile-count" id="mobileAssignedCount">{{ $assignedFsicRequests->count() }}</span>
            </section>

            <div class="fsic-page-actions">
                <div class="fsic-page-title">
                    <h1>FSIC Requests</h1>
                    <p>Inspect assigned fire safety clearance requests for your station.</p>
                </div>
                <button type="button" class="btn btn-outline" onclick="refreshFsicRequests()">
                    <i class="fas fa-rotate"></i> <span>Refresh</span>
                </button>
            </div>

            <div class="fsic-kpis">
                <div class="fsic-kpi">
                    <div class="fsic-kpi-head">
                        <span class="fsic-kpi-label"><span class="fsic-kpi-label-full">Assigned</span><span class="fsic-kpi-label-short">All</span></span>
                        <span class="fsic-kpi-icon"><i class="fas fa-file-invoice"></i></span>
                    </div>
                    <div class="fsic-kpi-value" id="assignedCount">{{ $assignedFsicRequests->count() }}</div>
                </div>
                <div class="fsic-kpi">
                    <div class="fsic-kpi-head">
                        <span class="fsic-kpi-label"><span class="fsic-kpi-label-full">For Inspection</span><span class="fsic-kpi-label-short">Inspect</span></span>
                        <span class="fsic-kpi-icon"><i class="fas fa-clipboard-check"></i></span>
                    </div>
                    <div class="fsic-kpi-value" id="forInspectionCount">{{ $assignedFsicRequests->where('status', 'for_inspection')->count() }}</div>
                </div>
                <div class="fsic-kpi">
                    <div class="fsic-kpi-head">
                        <span class="fsic-kpi-label"><span class="fsic-kpi-label-full">Inspected</span><span class="fsic-kpi-label-short">Done</span></span>
                        <span class="fsic-kpi-icon"><i class="fas fa-magnifying-glass"></i></span>
                    </div>
                    <div class="fsic-kpi-value" id="inspectedCount">{{ $assignedFsicRequests->where('status', 'inspected')->count() }}</div>
                </div>
                <div class="fsic-kpi">
                    <div class="fsic-kpi-head">
                        <span class="fsic-kpi-label"><span class="fsic-kpi-label-full">Approved</span><span class="fsic-kpi-label-short">OK</span></span>
                        <span class="fsic-kpi-icon"><i class="fas fa-certificate"></i></span>
                    </div>
                    <div class="fsic-kpi-value" id="approvedCount">{{ $assignedFsicRequests->where('status', 'approved')->count() }}</div>
                </div>
            </div>

            <section class="fsic-card">
                <div class="fsic-card-header">
                    <div>
                        <div class="fsic-card-title">Assigned Request Queue</div>
                        <div class="fsic-card-subtitle">Click a row to view full request details. Inspect requests that are still waiting for field evaluation.</div>
                    </div>
                </div>

                <div class="fsic-table-wrap">
                    <table class="fsic-table">
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Applicant</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                                <th>FSIC Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="fsicRequestsTableBody">
                            @forelse($assignedFsicRequests as $request)
                                <tr data-request-id="{{ $request->id }}" class="fsic-request-row">
                                    <td data-label="Request">
                                        <div class="fsic-row-title">{{ $request->business_name }}</div>
                                        <div class="fsic-row-meta">{{ $request->request_code }}</div>
                                    </td>
                                    <td data-label="Applicant">
                                        <div class="fsic-row-title">{{ $request->owner_name }}</div>
                                        <div class="fsic-row-meta">{{ $request->user->email ?? 'No email' }}</div>
                                    </td>
                                    <td data-label="Requested">{{ optional($request->date_requested)->format('M d, Y') ?? optional($request->created_at)->format('M d, Y') }}</td>
                                    <td data-label="Status">
                                        <span class="status-pill status-{{ $request->status }}">
                                            {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                        </span>
                                    </td>
                                    <td data-label="FSIC No.">{{ $request->approval->fsic_number ?? 'Pending' }}</td>
                                    <td data-label="Action">
                                        @if($request->status === 'for_inspection')
                                            <button type="button" class="table-action inspect" onclick="event.stopPropagation(); openInspectionModal({{ $request->id }})">
                                                <i class="fas fa-clipboard-check"></i> Inspect
                                            </button>
                                        @else
                                            <button type="button" class="table-action view" onclick="event.stopPropagation(); openDetailsModal({{ $request->id }})">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-file-circle-check"></i>
                                            <div>No FSIC requests are assigned to you yet.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </main>
    </div>

    <div id="detailsModal" class="fsic-modal">
        <div class="fsic-modal-panel">
            <div class="fsic-modal-header">
                <div>
                    <div class="fsic-modal-title">FSIC Request Details</div>
                    <div class="fsic-modal-subtitle">Assigned request information and latest inspection state.</div>
                </div>
                <button type="button" class="fsic-close-btn" onclick="closeDetailsModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="fsic-modal-body" id="detailsModalBody">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div>Loading request details...</div>
                </div>
            </div>
            <div class="fsic-modal-footer">
                <div class="btn-row">
                    <button type="button" class="btn btn-muted" onclick="closeDetailsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="inspectionModal" class="fsic-modal">
        <div class="fsic-modal-panel">
            <div class="fsic-modal-header">
                <div>
                    <div class="fsic-modal-title">Inspect FSIC Request</div>
                    <div class="fsic-modal-subtitle" id="inspectionModalSubtitle">Complete the checklist and submit your inspection findings.</div>
                </div>
                <button type="button" class="fsic-close-btn" onclick="closeInspectionModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="fsic-modal-body">
                <input type="hidden" id="inspectionRequestId">

                <div class="progress-steps">
                    <div class="progress-step active" id="inspectionProgressStep1">
                        <span class="progress-step-circle">1</span>
                        <span class="progress-step-text">Checklist</span>
                    </div>
                    <div class="progress-step" id="inspectionProgressStep2">
                        <span class="progress-step-circle">2</span>
                        <span class="progress-step-text">Compliance Review</span>
                    </div>
                </div>

                <div id="inspectionStep1" class="inspection-step active">
                    <div class="checklist-grid">
                        <label class="checklist-item">
                            <input type="checkbox" id="check_fire_protection">
                            <div>
                                <div class="checklist-item-title">Fire Protection</div>
                                <div class="checklist-item-help">Sprinklers, extinguishers, and related protection systems are compliant.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_alarm_systems">
                            <div>
                                <div class="checklist-item-title">Alarm Systems</div>
                                <div class="checklist-item-help">Alarm and detection systems are present and working properly.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_standard_material_used">
                            <div>
                                <div class="checklist-item-title">Standard Materials Used</div>
                                <div class="checklist-item-help">Construction materials used meet the required standards.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_electrical">
                            <div>
                                <div class="checklist-item-title">Electrical</div>
                                <div class="checklist-item-help">Electrical wiring and installation are safe and code-compliant.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_housekeeping">
                            <div>
                                <div class="checklist-item-title">Housekeeping</div>
                                <div class="checklist-item-help">Storage areas and housekeeping practices reduce fire risk.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_hazards">
                            <div>
                                <div class="checklist-item-title">Hazards</div>
                                <div class="checklist-item-help">Fire hazards have been identified and addressed.</div>
                            </div>
                        </label>
                        <label class="checklist-item">
                            <input type="checkbox" id="check_planning">
                            <div>
                                <div class="checklist-item-title">Planning</div>
                                <div class="checklist-item-help">Emergency planning, exit routes, and evacuation guidance are in place.</div>
                            </div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="inspectionRemarks">Inspection Remarks</label>
                        <textarea id="inspectionRemarks" placeholder="Enter inspection findings and remarks..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="inspectionChecklistRemarks">Checklist Notes</label>
                        <textarea id="inspectionChecklistRemarks" placeholder="Optional checklist-specific notes..."></textarea>
                    </div>
                </div>

                <div id="inspectionStep2" class="inspection-step">
                    <div id="complianceBanner" class="compliance-banner">
                        <div class="compliance-title" id="complianceTitle">Compliance Result</div>
                        <div id="complianceMessage"></div>
                    </div>

                    <div class="summary-list" id="inspectionSummaryList"></div>
                </div>
            </div>
            <div class="fsic-modal-footer">
                <button type="button" class="btn btn-muted" id="inspectionPrevBtn" style="display:none;" onclick="goToInspectionStep(1)">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <div class="btn-row">
                    <button type="button" class="btn btn-outline" onclick="closeInspectionModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="inspectionNextBtn" onclick="goToInspectionStep(2)">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="inspectionSubmitBtn" style="display:none;" onclick="submitInspection()">
                        <i class="fas fa-paper-plane"></i> Submit Inspection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast info">
        <i class="fas fa-circle-info"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        const initialFsicRequests = @json($assignedFsicRequests);
        let fsicRequests = Array.isArray(initialFsicRequests) ? initialFsicRequests : [];
        let currentInspectionStep = 1;
        let activeInspectionRequest = null;

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const text = document.getElementById('toastMessage');
            toast.className = `toast ${type} show`;
            text.textContent = message;
            window.clearTimeout(window.fsicToastTimeout);
            window.fsicToastTimeout = window.setTimeout(() => {
                toast.classList.remove('show');
            }, 3200);
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        }

        function formatDate(dateValue) {
            if (!dateValue) return 'N/A';
            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function getStatusClass(status) {
            if (status === 'for_inspection') return 'status-for_inspection';
            if (status === 'inspected') return 'status-inspected';
            if (status === 'approved') return 'status-approved';
            return 'status-default';
        }

        function formatStatus(status) {
            return String(status || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
        }

        function updateStats() {
            const assignedCount = fsicRequests.length;
            document.getElementById('assignedCount').textContent = assignedCount;
            document.getElementById('forInspectionCount').textContent = fsicRequests.filter(request => canInspectRequest(request)).length;
            document.getElementById('inspectedCount').textContent = fsicRequests.filter(request => request.status === 'inspected' && !canInspectRequest(request)).length;
            document.getElementById('approvedCount').textContent = fsicRequests.filter(request => request.status === 'approved').length;
            const mobileAssignedCount = document.getElementById('mobileAssignedCount');
            if (mobileAssignedCount) {
                mobileAssignedCount.textContent = assignedCount;
            }
        }

        function canInspectRequest(request) {
            if (!request || request.status === 'approved') {
                return false;
            }

            const inspection = request.inspection || null;
            const hasInspectionDate = Boolean(inspection?.inspection_date);
            const hasChecklist = Boolean(inspection?.checklist);

            return !hasInspectionDate && !hasChecklist;
        }

        function buildRequestRow(request) {
            const canInspect = canInspectRequest(request);
            return `
                <tr data-request-id="${request.id}" class="fsic-request-row">
                    <td data-label="Request">
                        <div class="fsic-row-title">${escapeHtml(request.business_name)}</div>
                        <div class="fsic-row-meta">${escapeHtml(request.request_code)}</div>
                    </td>
                    <td data-label="Applicant">
                        <div class="fsic-row-title">${escapeHtml(request.owner_name)}</div>
                        <div class="fsic-row-meta">${escapeHtml(request.user?.email || 'No email')}</div>
                    </td>
                    <td data-label="Requested">${escapeHtml(formatDate(request.date_requested || request.created_at))}</td>
                    <td data-label="Status">
                        <span class="status-pill ${getStatusClass(request.status)}">${escapeHtml(formatStatus(request.status))}</span>
                    </td>
                    <td data-label="FSIC No.">${escapeHtml(request.approval?.fsic_number || 'Pending')}</td>
                    <td data-label="Action">
                        ${canInspect
                            ? `<button type="button" class="table-action inspect" onclick="event.stopPropagation(); openInspectionModal(${request.id})"><i class="fas fa-clipboard-check"></i> Inspect</button>`
                            : `<button type="button" class="table-action view" onclick="event.stopPropagation(); openDetailsModal(${request.id})"><i class="fas fa-eye"></i> View</button>`
                        }
                    </td>
                </tr>
            `;
        }

        function renderRequestsTable() {
            const tbody = document.getElementById('fsicRequestsTableBody');
            if (!fsicRequests.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-file-circle-check"></i>
                                <div>No FSIC requests are assigned to you yet.</div>
                            </div>
                        </td>
                    </tr>
                `;
                updateStats();
                return;
            }

            tbody.innerHTML = fsicRequests.map(buildRequestRow).join('');
            bindRequestRowEvents();
            updateStats();
        }

        function bindRequestRowEvents() {
            document.querySelectorAll('.fsic-request-row').forEach(row => {
                row.addEventListener('click', () => {
                    const requestId = row.getAttribute('data-request-id');
                    if (requestId) {
                        openDetailsModal(requestId);
                    }
                });
            });
        }

        async function refreshFsicRequests() {
            try {
                const requests = await Promise.all(
                    fsicRequests.map(async request => {
                        const response = await fetch(`/firefighter/api/fsic-requests/${request.id}`);
                        const result = await response.json();
                        return result.data;
                    })
                );
                fsicRequests = requests.filter(Boolean);
                renderRequestsTable();
                showToast('FSIC requests refreshed.', 'info');
            } catch (error) {
                console.error('Failed to refresh FSIC requests:', error);
                showToast('Failed to refresh FSIC requests.', 'error');
            }
        }

        async function fetchFsicRequest(id) {
            const response = await fetch(`/firefighter/api/fsic-requests/${id}`);
            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to load FSIC request.');
            }
            return result.data;
        }

        function createDetailCard(label, value) {
            return `
                <div class="fsic-detail-card">
                    <div class="fsic-detail-label">${escapeHtml(label)}</div>
                    <div class="fsic-detail-value">${escapeHtml(value || 'N/A')}</div>
                </div>
            `;
        }

        async function openDetailsModal(id) {
            const modal = document.getElementById('detailsModal');
            const body = document.getElementById('detailsModalBody');
            modal.classList.add('active');
            body.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div>Loading request details...</div>
                </div>
            `;

            try {
                const request = await fetchFsicRequest(id);
                body.innerHTML = `
                    <div class="fsic-detail-grid">
                        ${createDetailCard('Request Code', request.request_code)}
                        ${createDetailCard('Status', formatStatus(request.status))}
                        ${createDetailCard('Business Name', request.business_name)}
                        ${createDetailCard('Owner Name', request.owner_name)}
                        ${createDetailCard('Business Type', request.business_type || 'Not specified')}
                        ${createDetailCard('Date Requested', formatDate(request.date_requested || request.created_at))}
                        ${createDetailCard('Building Address', request.building_address)}
                        ${createDetailCard('Location', [request.barangay, request.city, request.province].filter(Boolean).join(', '))}
                        ${createDetailCard('DTI / SEC No.', request.dti_sec_registration_no || 'Not provided')}
                        ${createDetailCard('Mayor\'s Permit', request.mayors_permit_no || 'Not provided')}
                        ${createDetailCard('TIN Number', request.tin_number || 'Not provided')}
                        ${createDetailCard('Applicant', request.user?.email || 'No email')}
                        ${createDetailCard('Inspection Date', formatDate(request.inspection?.inspection_date))}
                        ${createDetailCard('Inspection Result', request.inspection ? (Number(request.inspection.is_compliant) === 1 ? 'Compliant' : 'Not Compliant') : 'Not inspected yet')}
                        ${createDetailCard('Inspection Remarks', request.inspection?.inspection_remarks || 'No remarks yet')}
                        ${createDetailCard('Checklist Notes', request.inspection?.checklist?.remarks || 'No checklist notes yet')}
                        ${createDetailCard('FSIC Number', request.approval?.fsic_number || 'Pending')}
                        ${createDetailCard('Certificate Valid Until', formatDate(request.approval?.valid_until))}
                    </div>
                `;
            } catch (error) {
                console.error(error);
                body.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>${escapeHtml(error.message)}</div>
                    </div>
                `;
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        async function openInspectionModal(id) {
            try {
                activeInspectionRequest = await fetchFsicRequest(id);
                document.getElementById('inspectionRequestId').value = activeInspectionRequest.id;
                document.getElementById('inspectionModalSubtitle').textContent = `${activeInspectionRequest.request_code} · ${activeInspectionRequest.business_name}`;
                document.getElementById('inspectionRemarks').value = activeInspectionRequest.inspection?.inspection_remarks || '';
                document.getElementById('inspectionChecklistRemarks').value = activeInspectionRequest.inspection?.checklist?.remarks || '';

                const checklist = activeInspectionRequest.inspection?.checklist || {};
                document.getElementById('check_fire_protection').checked = Number(checklist.fire_protection || 0) === 1;
                document.getElementById('check_alarm_systems').checked = Number(checklist.alarm_systems || 0) === 1;
                document.getElementById('check_standard_material_used').checked = Number(checklist.standard_material_used || 0) === 1;
                document.getElementById('check_electrical').checked = Number(checklist.electrical || 0) === 1;
                document.getElementById('check_housekeeping').checked = Number(checklist.housekeeping || 0) === 1;
                document.getElementById('check_hazards').checked = Number(checklist.hazards || 0) === 1;
                document.getElementById('check_planning').checked = Number(checklist.planning || 0) === 1;

                goToInspectionStep(1);
                document.getElementById('inspectionModal').classList.add('active');
            } catch (error) {
                console.error(error);
                showToast(error.message || 'Failed to load inspection request.', 'error');
            }
        }

        function closeInspectionModal() {
            document.getElementById('inspectionModal').classList.remove('active');
            activeInspectionRequest = null;
            currentInspectionStep = 1;
        }

        function getChecklistPayload() {
            return {
                fire_protection: document.getElementById('check_fire_protection').checked ? 1 : 0,
                alarm_systems: document.getElementById('check_alarm_systems').checked ? 1 : 0,
                standard_material_used: document.getElementById('check_standard_material_used').checked ? 1 : 0,
                electrical: document.getElementById('check_electrical').checked ? 1 : 0,
                housekeeping: document.getElementById('check_housekeeping').checked ? 1 : 0,
                hazards: document.getElementById('check_hazards').checked ? 1 : 0,
                planning: document.getElementById('check_planning').checked ? 1 : 0,
                remarks: document.getElementById('inspectionChecklistRemarks').value.trim(),
            };
        }

        function buildInspectionSummary(checklist, remarks) {
            const mapping = [
                ['Fire Protection', checklist.fire_protection],
                ['Alarm Systems', checklist.alarm_systems],
                ['Standard Materials', checklist.standard_material_used],
                ['Electrical', checklist.electrical],
                ['Housekeeping', checklist.housekeeping],
                ['Hazards', checklist.hazards],
                ['Planning', checklist.planning],
            ];

            const summary = mapping.map(([label, value]) => `
                <div class="summary-item">
                    <div class="summary-label">${escapeHtml(label)}</div>
                    <div class="summary-value">${value === 1 ? 'Compliant' : 'Needs attention'}</div>
                </div>
            `).join('');

            document.getElementById('inspectionSummaryList').innerHTML = `
                ${summary}
                <div class="summary-item">
                    <div class="summary-label">Inspection Remarks</div>
                    <div class="summary-value">${escapeHtml(remarks)}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Checklist Notes</div>
                    <div class="summary-value">${escapeHtml(checklist.remarks || 'No checklist notes provided')}</div>
                </div>
            `;
        }

        function goToInspectionStep(step) {
            currentInspectionStep = step;
            document.getElementById('inspectionStep1').classList.toggle('active', step === 1);
            document.getElementById('inspectionStep2').classList.toggle('active', step === 2);
            document.getElementById('inspectionPrevBtn').style.display = step === 2 ? 'inline-flex' : 'none';
            document.getElementById('inspectionNextBtn').style.display = step === 1 ? 'inline-flex' : 'none';
            document.getElementById('inspectionSubmitBtn').style.display = step === 2 ? 'inline-flex' : 'none';
            document.getElementById('inspectionProgressStep1').className = `progress-step ${step === 1 ? 'active' : 'completed'}`;
            document.getElementById('inspectionProgressStep2').className = `progress-step ${step === 2 ? 'active' : ''}`;

            if (step === 2) {
                const remarks = document.getElementById('inspectionRemarks').value.trim();
                if (!remarks) {
                    showToast('Inspection remarks are required.', 'error');
                    currentInspectionStep = 1;
                    goToInspectionStep(1);
                    return;
                }

                const checklist = getChecklistPayload();
                const isCompliant = [
                    checklist.fire_protection,
                    checklist.alarm_systems,
                    checklist.standard_material_used,
                    checklist.electrical,
                    checklist.housekeeping,
                    checklist.hazards,
                    checklist.planning,
                ].every(value => Number(value) === 1);

                const banner = document.getElementById('complianceBanner');
                const title = document.getElementById('complianceTitle');
                const message = document.getElementById('complianceMessage');
                banner.className = `compliance-banner ${isCompliant ? 'pass' : 'fail'}`;
                title.textContent = isCompliant ? 'Compliant Inspection Result' : 'Not Compliant Inspection Result';
                message.textContent = isCompliant
                    ? 'All checklist items passed. The inspection will be saved as compliant.'
                    : 'One or more checklist items failed. The inspection will be saved as not compliant.';

                buildInspectionSummary(checklist, remarks);
            }
        }

        async function submitInspection() {
            const requestId = document.getElementById('inspectionRequestId').value;
            const remarks = document.getElementById('inspectionRemarks').value.trim();
            if (!requestId || !remarks) {
                showToast('Inspection remarks are required.', 'error');
                return;
            }

            const payload = {
                inspection_remarks: remarks,
                checklist: getChecklistPayload(),
            };

            try {
                const response = await fetch(`/firefighter/api/fsic-requests/${requestId}/inspect`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Failed to submit inspection.');
                }

                const updatedRequest = result.data;
                fsicRequests = fsicRequests.map(request => request.id == updatedRequest.id ? updatedRequest : request);
                renderRequestsTable();
                closeInspectionModal();
                showToast(result.message || 'FSIC inspection submitted successfully.', 'success');
            } catch (error) {
                console.error(error);
                showToast(error.message || 'Failed to submit inspection.', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderRequestsTable();
        });

        window.refreshFsicRequests = refreshFsicRequests;
        window.openInspectionModal = openInspectionModal;
        window.closeInspectionModal = closeInspectionModal;
        window.openDetailsModal = openDetailsModal;
        window.closeDetailsModal = closeDetailsModal;
        window.goToInspectionStep = goToInspectionStep;
        window.submitInspection = submitInspection;

        if (window.matchMedia('(max-width: 768px)').matches && 'scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
            window.requestAnimationFrame(() => window.scrollTo({ top: 0, left: 0 }));
        }
    </script>
    @include('partials.pwa-body')
</body>
</html>
