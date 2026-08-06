<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · {{ $pageTitle }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])

    <style>
        body {
            background: #f8fafc;
            overflow-x: hidden;
        }

        html,
        body.chief-dashboard-page {
            min-height: 100%;
            height: auto;
            overflow-y: auto;
        }

        .review-shell {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            min-height: 100vh;
        }

        .review-main {
            min-width: 0;
            overflow: visible;
        }

        .lector-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 24px 28px 8px;
            padding: 14px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .review-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 28px 8px;
        }

        .review-topbar__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .review-topbar h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
        }

        .review-topbar p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 8px 14px;
            border: 1px solid #fcd9bd;
            border-radius: 999px;
            background: #fff7ed;
            color: #334155;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #ffedd5;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 260px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            overflow: hidden;
            z-index: 100;
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
            width: 100%;
            padding: 12px 18px;
            background: transparent;
            border: 0;
            color: #1e293b;
            text-decoration: none;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .review-page {
            padding: 20px 28px 40px;
        }

        .review-notice-layer {
            position: fixed;
            top: 22px;
            right: 24px;
            z-index: 1700;
            pointer-events: none;
        }

        .review-notice {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: min(360px, calc(100vw - 32px));
            max-width: min(420px, calc(100vw - 32px));
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid #bbf7d0;
            background: rgba(240, 253, 244, 0.98);
            color: #166534;
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.14);
            opacity: 0;
            transform: translateY(-16px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: auto;
        }

        .review-notice.show {
            opacity: 1;
            transform: translateY(0);
        }

        .review-notice--error {
            border-color: #fecaca;
            background: rgba(254, 242, 242, 0.98);
            color: #b91c1c;
        }

        .review-notice__icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #dcfce7;
            color: #15803d;
            flex-shrink: 0;
        }

        .review-notice--error .review-notice__icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .review-notice__content {
            min-width: 0;
        }

        .review-notice__title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .review-notice__message {
            margin-top: 2px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
        }

        .map-picker-card {
            grid-column: 1 / -1;
            display: grid;
            gap: 12px;
        }

        .map-picker-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
        }

        .map-picker-status__label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #9a3412;
            font-size: 0.88rem;
            font-weight: 800;
        }

        .map-picker-status__label.is-ready {
            color: #166534;
        }

        .map-picker-status__coords {
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .map-picker-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .map-picker-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid #fdba74;
            background: #fff;
            color: #c2410c;
            font-size: 0.84rem;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .map-picker-btn:hover {
            background: #fff7ed;
            border-color: #fb923c;
        }

        .map-picker-btn.is-confirmed {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .map-picker-help {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .map-picker-canvas {
            width: 100%;
            height: 300px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
            background: linear-gradient(180deg, #eff6ff 0%, #fff 100%);
        }

        .leaflet-container {
            font-family: inherit;
        }

        .map-picker-hall-pin {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border: 3px solid rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 22px rgba(234, 88, 12, 0.28);
        }

        .map-picker-selected-pin {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: radial-gradient(circle at 35% 35%, #86efac 0%, #22c55e 45%, #15803d 100%);
            border: 4px solid rgba(255, 255, 255, 0.98);
            box-shadow: 0 16px 30px rgba(21, 128, 61, 0.32);
        }

        .review-notice__close {
            width: 32px;
            height: 32px;
            margin-left: auto;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: inherit;
            cursor: pointer;
            flex-shrink: 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .summary-card__label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary-card__value {
            margin-top: 14px;
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
        }

        .filters-card,
        .queue-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
        }

        .filters-card {
            padding: 20px;
            margin-bottom: 22px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1.4fr repeat(3, minmax(0, 0.7fr)) auto;
            gap: 14px;
            align-items: end;
        }

        .filters-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filters-group--compact {
            gap: 0;
        }

        .filter-control {
            position: relative;
        }

        .filter-control__icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #f97316;
            pointer-events: none;
        }

        .filters-group label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .filters-group input,
        .filters-group select,
        .review-notes {
            width: 100%;
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            padding: 11px 14px;
            font-size: 0.95rem;
            color: #0f172a;
            background: #fff;
        }

        .filter-control > input,
        .filter-control > select {
            padding-left: 38px;
        }

        .filters-group input:focus,
        .filters-group select:focus,
        .review-notes:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .filter-submit {
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            padding: 11px 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .queue-card {
            padding: 24px;
        }

        .queue-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .queue-head h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .queue-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .queue-count {
            padding: 10px 14px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 700;
        }

        .request-list {
            display: grid;
            gap: 14px;
        }

        .request-tile {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 18px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #fffdfb 100%);
        }

        .request-tile__title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 800;
            color: #0f172a;
        }

        .request-tile__sub {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .request-tile__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .request-tile__trailing {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .chip--pending { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .chip--approved { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .chip--rejected { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .chip--neutral { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .chip--risk { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .chip--hazard { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

        .request-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .request-field {
            padding: 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #edf2f7;
        }

        .request-field__label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .request-field__value {
            margin-top: 8px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.5;
            word-break: break-word;
        }

        .request-copy {
            margin: 0;
            color: #475569;
            line-height: 1.6;
            white-space: pre-line;
        }

        .photo-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .photo-strip img {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .review-form__row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 12px;
            align-items: end;
        }

        .review-actions {
            display: contents;
        }

        .action-btn {
            border: none;
            border-radius: 14px;
            min-height: 46px;
            padding: 0 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .action-btn--approve {
            background: #16a34a;
            color: #fff;
        }

        .action-btn--reject {
            background: #e2e8f0;
            color: #0f172a;
        }

        .review-history {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .empty-state {
            padding: 48px 18px;
            text-align: center;
            color: #64748b;
        }

        .empty-state__hint {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .empty-state__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .empty-state__link:hover {
            background: #ffedd5;
        }

        .pagination-wrap {
            margin-top: 20px;
        }

        #riskReviewResults.is-loading {
            opacity: 0.56;
            transition: opacity 0.18s ease;
            pointer-events: none;
        }

        .review-trigger {
            border: none;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 14px;
            background: #0f172a;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
        }

        .create-entry-trigger {
            min-height: 30px;
            padding: 0 10px;
            font-size: 0.86rem;
            box-shadow: none;
        }

        .review-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.56);
            z-index: 5000;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .review-modal.is-open {
            display: flex;
        }

        .review-modal__panel {
            width: min(920px, 100%);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: #fff;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
            padding: 24px;
        }

        .review-modal__panel form[data-create-form] {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .review-modal__panel form[data-create-form] .manage-form-grid {
            flex: 1 1 auto;
        }

        .review-modal__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .review-modal__title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .review-modal__copy {
            margin: 6px 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .review-modal__close {
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            cursor: pointer;
        }

        .manage-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 18px;
            margin-top: 18px;
        }

        .manage-form-stack {
            display: grid;
            gap: 16px;
        }

        .manage-form-card {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .review-form-errors {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .manage-form-card h4 {
            margin: 0 0 8px;
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .manage-form-card p {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .create-entry-actions {
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.92) 0%, #f8fafc 30%);
            padding-top: 10px;
        }

        .manage-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .manage-field-grid .full {
            grid-column: 1 / -1;
        }

        .manage-toggle {
            display: inline-flex;
            gap: 10px;
            margin-bottom: 14px;
        }

        .manage-toggle__btn {
            border: 1px solid #dbe4ee;
            background: #fff;
            color: #475569;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }

        .manage-toggle__btn.is-active {
            border-color: #fb923c;
            background: #fff7ed;
            color: #c2410c;
        }

        .manage-chip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .manage-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid #dbe4ee;
            background: #fff;
            color: #334155;
            font-size: 0.9rem;
        }

        .manage-chip input {
            width: 16px;
            height: 16px;
        }

        .manage-inline-note {
            margin-top: 10px;
            color: #64748b;
            font-size: 0.82rem;
        }

        .mobile-menu-btn {
            display: none;
            border: none;
            background: #fff;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            color: #334155;
        }

        @media (max-width: 1100px) {
            .summary-grid,
            .request-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filters-grid,
            .review-form__row {
                grid-template-columns: 1fr 1fr;
            }

            .review-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            html,
            body.chief-dashboard-page {
                height: auto !important;
                min-height: 100% !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch;
            }

            .review-shell {
                grid-template-columns: 1fr;
            }

            .review-notice-layer {
                top: auto;
                right: 10px;
                left: 10px;
                bottom: calc(84px + env(safe-area-inset-bottom, 0px));
            }

            .review-notice {
                min-width: 0;
                max-width: none;
                width: 100%;
                padding: 12px 14px;
                border-radius: 16px;
                transform: translateY(16px);
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
            }

            .review-notice.show {
                transform: translateY(0);
            }

            .review-notice__icon {
                width: 34px;
                height: 34px;
            }

            .review-notice__title {
                font-size: 11px;
            }

            .review-notice__message {
                font-size: 12px;
            }

            .review-notice__close {
                width: 28px;
                height: 28px;
            }

            .lector-top-bar,
            .review-topbar,
            .review-page {
                padding-left: 16px;
                padding-right: 16px;
            }

            .lector-top-bar {
                gap: 10px;
                margin: 16px 16px 8px;
                padding: 10px 12px;
                border-radius: 16px;
            }

            .main-content,
            body.chief-dashboard-page .main-content.review-main {
                margin-left: 0 !important;
                min-height: auto !important;
                overflow: visible !important;
                overflow-y: auto !important;
                padding: 12px 10px calc(var(--mobile-bottom-nav-h) + 18px) !important;
            }

            .user-dropdown-toggle {
                gap: 8px;
                padding: 6px 10px;
            }

            .user-dropdown-menu {
                width: min(240px, calc(100vw - 24px));
            }

            .review-topbar {
                align-items: flex-start;
            }

            .review-topbar__actions {
                width: 100%;
                justify-content: space-between;
            }

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .summary-card {
                padding: 14px 12px;
                border-radius: 18px;
            }

            .summary-card__label {
                gap: 6px;
                font-size: 0.68rem;
                letter-spacing: 0.05em;
            }

            .summary-card__value {
                margin-top: 10px;
                font-size: 1.35rem;
            }

            .filters-card,
            .queue-card {
                border-radius: 20px;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            }

            .filters-card {
                padding: 12px;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .filters-card::-webkit-scrollbar {
                display: none;
            }

            .filters-grid {
                display: grid;
                grid-template-columns: minmax(150px, 1.15fr) repeat(3, minmax(118px, 0.78fr));
                gap: 8px;
                align-items: stretch;
                min-width: 520px;
            }

            .request-grid,
            .review-form__row,
            .manage-form-grid,
            .manage-field-grid,
            .manage-chip-grid {
                grid-template-columns: 1fr;
            }

            .filters-group {
                gap: 6px;
            }

            .filters-group label {
                display: none;
            }

            .filters-group--compact {
                min-width: 0;
            }

            .filters-group input,
            .filters-group select,
            .review-notes {
                min-height: 38px;
                padding: 8px 10px;
                border-radius: 12px;
                font-size: 0.84rem;
            }

            .map-picker-canvas {
                height: 220px;
                border-radius: 18px;
            }

            .map-picker-status {
                padding: 10px 12px;
                border-radius: 14px;
            }

            .map-picker-btn {
                min-height: 36px;
                padding: 0 12px;
                font-size: 0.78rem;
            }

            .filter-control__icon {
                left: 10px;
                width: 16px;
                height: 16px;
                font-size: 0.78rem;
            }

            .filter-control > input,
            .filter-control > select {
                padding-left: 32px;
            }

            .queue-head,
            .request-tile {
                flex-direction: column;
                align-items: flex-start;
            }

            .review-modal__head {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
                gap: 10px;
            }

            .queue-card {
                padding: 14px;
            }

            .queue-head {
                gap: 8px;
                margin-bottom: 10px;
            }

            .queue-head h2 {
                font-size: 0.98rem;
            }

            .queue-head p {
                font-size: 0.76rem;
                line-height: 1.4;
            }

            .queue-count {
                padding: 6px 9px;
                font-size: 0.74rem;
            }

            .request-list {
                gap: 10px;
            }

            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .request-tile {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px 11px;
                border-radius: 16px;
                border-color: #fde7cf;
                background:
                    linear-gradient(180deg, rgba(255, 247, 237, 0.52) 0%, #ffffff 32%),
                    linear-gradient(90deg, #f97316 0 3px, transparent 3px 100%);
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
            }

            .request-tile__trailing {
                width: 100%;
                justify-content: flex-end;
            }

            .request-tile__title {
                font-size: 0.9rem;
                line-height: 1.3;
            }

            .request-tile__sub {
                margin-top: 3px;
                font-size: 0.74rem;
                line-height: 1.35;
            }

            .request-tile__meta {
                gap: 6px;
                margin-top: 8px;
            }

            .chip {
                min-height: 24px;
                padding: 0 8px;
                font-size: 0.62rem;
                gap: 5px;
                letter-spacing: 0.01em;
            }

            .review-trigger {
                width: auto;
                min-width: 92px;
                min-height: 34px;
                border-radius: 10px;
                font-size: 0.76rem;
                padding: 0 11px;
                background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
                box-shadow: 0 8px 18px rgba(249, 115, 22, 0.18);
            }

            .action-btn {
                min-height: 40px;
                padding: 0 12px;
                border-radius: 12px;
                font-size: 0.82rem;
            }

            .review-modal {
                align-items: flex-start;
                padding: max(10px, env(safe-area-inset-top, 0px)) 10px max(10px, env(safe-area-inset-bottom, 0px));
                overflow-y: auto;
            }

            .review-modal__panel {
                width: 100%;
                max-height: min(86dvh, calc(100vh - 20px - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px)));
                margin-top: 0;
                padding: 0;
                border-radius: 20px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            .review-modal__title {
                font-size: 1rem;
            }

            .review-modal__copy {
                font-size: 0.82rem;
                line-height: 1.45;
            }

            .review-modal__close {
                width: 36px;
                height: 36px;
                position: absolute;
                top: 12px;
                right: 12px;
            }

            .review-modal__head {
                position: sticky;
                top: 0;
                z-index: 2;
                margin-bottom: 0;
                padding: 14px 52px 12px 14px;
                background: linear-gradient(180deg, #fff7ed 0%, #fffdf8 100%);
                border-bottom: 1px solid #fde7cf;
            }

            .review-modal__panel form[data-create-form],
            .review-modal__panel form[data-manage-form],
            .review-modal__panel form[data-final-classification-form] {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                padding: 14px;
                -webkit-overflow-scrolling: touch;
            }

            .manage-form-card {
                padding: 14px 12px;
                border-radius: 16px;
            }

            .manage-form-card h4 {
                font-size: 0.92rem;
                margin-bottom: 6px;
            }

            .manage-form-card p,
            .manage-inline-note,
            .review-history,
            .request-copy {
                font-size: 0.8rem;
                line-height: 1.45;
            }

            .manage-toggle {
                gap: 8px;
            }

            .manage-toggle__btn {
                min-height: 34px;
                padding: 0 12px;
                font-size: 0.78rem;
            }

            .manage-chip {
                min-height: 36px;
                padding: 8px 10px;
                border-radius: 12px;
                font-size: 0.76rem;
                gap: 8px;
            }

            .manage-chip input {
                width: 14px;
                height: 14px;
            }

            .create-entry-actions {
                position: sticky;
                bottom: 0;
                margin-top: 6px;
                padding-top: 10px;
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.08) 0%, #f8fafc 24%);
            }

            .request-field {
                padding: 10px;
                border-radius: 14px;
            }

            .request-field__label {
                font-size: 0.62rem;
                letter-spacing: 0.05em;
            }

            .request-field__value {
                margin-top: 6px;
                font-size: 0.82rem;
                line-height: 1.4;
            }

            .photo-strip {
                gap: 8px;
            }

            .photo-strip img {
                width: 72px;
                height: 72px;
                border-radius: 12px;
            }

            .empty-state {
                padding: 26px 12px;
            }

            .empty-state p {
                font-size: 0.84rem;
                line-height: 1.45;
            }

            .pagination-wrap {
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .pagination-wrap nav {
                transform: scale(0.94);
                transform-origin: left top;
            }

            .pagination-wrap::-webkit-scrollbar {
                height: 4px;
            }

            .pagination-wrap::-webkit-scrollbar-thumb {
                background: rgba(249, 115, 22, 0.25);
                border-radius: 999px;
            }
        }

        @media (max-width: 480px) {
            body {
                overflow-y: auto;
            }

            .main-content,
            body.chief-dashboard-page .main-content.review-main {
                padding: 12px 10px calc(var(--mobile-bottom-nav-h) + 28px) !important;
                overflow-y: auto !important;
            }

            .summary-grid {
                gap: 8px;
            }

            .summary-card {
                padding: 12px 10px;
            }

            .summary-card__value {
                font-size: 1.2rem;
            }

            .filters-card,
            .queue-card {
                border-radius: 18px;
            }

            .queue-card {
                padding: 12px;
            }

            .request-tile {
                padding: 10px 9px;
                border-radius: 14px;
            }

            .request-tile__title {
                font-size: 0.86rem;
            }

            .request-tile__sub {
                font-size: 0.72rem;
            }

            .chip {
                min-height: 22px;
                padding: 0 7px;
                font-size: 0.58rem;
            }

            .review-trigger {
                min-width: 84px;
                min-height: 32px;
                font-size: 0.72rem;
            }

            .review-modal__panel {
                border-radius: 18px;
                max-height: min(82dvh, calc(100vh - 16px - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px)));
            }

            .review-modal__panel form[data-create-form],
            .review-modal__panel form[data-manage-form],
            .review-modal__panel form[data-final-classification-form] {
                padding: 12px;
            }

            .empty-state__hint {
                font-size: 0.8rem;
            }

            .empty-state__link {
                min-height: 28px;
                padding: 0 8px;
                font-size: 0.76rem;
            }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @php
        $reviewNoticeMessage = session('status') ?? session('error');
        $reviewNoticeType = session('error') ? 'error' : 'success';
        $reviewNoticeTitle = session('error') ? 'Action Needed' : 'Saved';
        $reviewNoticeIcon = session('error') ? 'fa-triangle-exclamation' : 'fa-check';
    @endphp
    @if ($reviewNoticeMessage)
        <div class="review-notice-layer" aria-live="polite" aria-atomic="true">
            <div id="reviewPageNotice" class="review-notice {{ $reviewNoticeType === 'error' ? 'review-notice--error' : '' }}" role="status">
                <div class="review-notice__icon">
                    <i class="fa-solid {{ $reviewNoticeIcon }}"></i>
                </div>
                <div class="review-notice__content">
                    <div class="review-notice__title">{{ $reviewNoticeTitle }}</div>
                    <div class="review-notice__message">{{ $reviewNoticeMessage }}</div>
                </div>
                <button type="button" class="review-notice__close" id="reviewPageNoticeClose" aria-label="Close notification">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    @include($sidebarPartial, ['active' => $activeNav])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <main class="main-content review-main">
        @if ($mode === 'chief')
            <nav class="lector-top-bar">
                <div class="flex items-center gap-4">
                    <button id="openSidebar" class="lg:hidden text-slate-600" type="button" aria-label="Open menu">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-slate-800">{{ $pageTitle }}</h1>
                        <p class="text-xs text-slate-500 font-medium">{{ now()->format('l, F j') }}</p>
                    </div>
                </div>

                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle" type="button" aria-expanded="false" aria-haspopup="true">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
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
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @else
            <div class="review-topbar">
                <div class="flex items-start gap-4">
                    <button id="openSidebar" class="mobile-menu-btn" type="button" aria-label="Open menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h1>{{ $pageTitle }}</h1>
                        <p>{{ $pageSubtitle }}</p>
                    </div>
                </div>
                <div class="review-topbar__actions">
                    <div class="chip chip--neutral">
                        <i class="fa-regular fa-calendar"></i>
                        <span>{{ now()->format('l, F j, Y') }}</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="review-page chief-theme-shell">
            <section class="summary-grid">
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-inbox"></i> Total Requests</div>
                    <div class="summary-card__value">{{ $summaryCounts['all'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-regular fa-clock"></i> Pending</div>
                    <div class="summary-card__value">{{ $summaryCounts['pending'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-circle-check"></i> Approved</div>
                    <div class="summary-card__value">{{ $summaryCounts['approved'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-ban"></i> Rejected</div>
                    <div class="summary-card__value">{{ $summaryCounts['rejected'] }}</div>
                </article>
            </section>

            <section class="filters-card">
                <form method="GET" action="{{ route($indexRouteName) }}" class="filters-grid" id="reviewFiltersForm">
                    <div class="filters-group filters-group--compact">
                        <label for="search">Search</label>
                        <div class="filter-control">
                            <span class="filter-control__icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input id="search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search" data-auto-submit-search>
                        </div>
                    </div>
                    <div class="filters-group filters-group--compact">
                        <label for="review_status">Review Status</label>
                        <div class="filter-control">
                            <span class="filter-control__icon"><i class="fa-regular fa-clock"></i></span>
                            <select id="review_status" name="review_status" data-auto-submit-filter>
                                <option value="all" @selected($filters['review_status'] === 'all')>All statuses</option>
                                <option value="pending" @selected($filters['review_status'] === 'pending')>Pending</option>
                                <option value="approved" @selected($filters['review_status'] === 'approved')>Approved</option>
                                <option value="rejected" @selected($filters['review_status'] === 'rejected')>Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-group filters-group--compact">
                        <label for="entry_type">Entry Type</label>
                        <div class="filter-control">
                            <span class="filter-control__icon"><i class="fa-solid fa-layer-group"></i></span>
                            <select id="entry_type" name="entry_type" data-auto-submit-filter>
                                <option value="all" @selected($filters['entry_type'] === 'all')>All entries</option>
                                <option value="hazard" @selected($filters['entry_type'] === 'hazard')>Hazard only</option>
                                <option value="risk" @selected($filters['entry_type'] === 'risk')>Risk only</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-group filters-group--compact">
                        <label for="review_type">Change</label>
                        <div class="filter-control">
                            <span class="filter-control__icon"><i class="fa-solid fa-arrow-right-arrow-left"></i></span>
                            <select id="review_type" name="review_type" data-auto-submit-filter>
                                <option value="all" @selected($filters['review_type'] === 'all')>All changes</option>
                                <option value="create" @selected($filters['review_type'] === 'create')>Create</option>
                                <option value="update" @selected($filters['review_type'] === 'update')>Update</option>
                                <option value="delete" @selected($filters['review_type'] === 'delete')>Delete</option>
                            </select>
                        </div>
                    </div>
                </form>
            </section>

            <div class="review-modal" id="create-risk-area-modal" aria-hidden="true">
                <div class="review-modal__panel">
                    <div class="review-modal__head">
                        <div>
                            <h3 class="review-modal__title"><i class="fa-solid fa-plus" style="margin-right:10px;"></i>Create New Entry</h3>
                            <p class="review-modal__copy">Add a live hazard or risk record directly from the review page.</p>
                        </div>
                        <button type="button" class="review-modal__close" data-review-close>
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    @php
                        $createBarangayHallMapPayload = $createBarangayHalls->map(fn ($hall) => [
                            'id' => (int) $hall->barangay_hall_id,
                            'name' => $hall->barangay?->barangay_name ?? $hall->hall_name ?? 'Barangay hall',
                            'city' => $hall->barangay?->city?->city_name,
                            'lat' => $hall->marker?->lat !== null ? (float) $hall->marker->lat : null,
                            'lng' => $hall->marker?->lng !== null ? (float) $hall->marker->lng : null,
                        ])->values();
                    @endphp

                    <form method="POST" action="{{ route($createRouteName) }}" data-create-form>
                        @csrf
                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                        <input type="hidden" name="entry_type_filter" value="{{ $filters['entry_type'] }}">
                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">
                        <input type="hidden" name="entry_type" value="{{ old('entry_type', 'risk') }}" data-entry-type-input>
                        <input type="hidden" name="condition_code" value="{{ old('condition_code') }}" data-condition-code-input>
                        <input id="create_lat" type="hidden" name="lat" value="{{ old('lat') }}" required>
                        <input id="create_lng" type="hidden" name="lng" value="{{ old('lng') }}" required>

                        @if ($errors->any() && old('title'))
                            <div class="review-form-errors">
                                <strong>Unable to save the entry.</strong><br>
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="manage-form-grid">
                            <div class="manage-form-stack">
                                <section class="manage-form-card">
                                    <h4>Core details</h4>
                                    <p>Set the location, type, and main details for this live record.</p>
                                    <div class="manage-toggle">
                                        <button type="button" class="manage-toggle__btn {{ old('entry_type', 'risk') === 'hazard' ? 'is-active' : '' }}" data-manage-mode="hazard">Hazard</button>
                                        <button type="button" class="manage-toggle__btn {{ old('entry_type', 'risk') === 'risk' ? 'is-active' : '' }}" data-manage-mode="risk">Risk</button>
                                    </div>
                                    <div class="manage-field-grid">
                                        <div class="filters-group full">
                                            <label for="create_title">Title</label>
                                            <input id="create_title" type="text" name="title" value="{{ old('title') }}" required>
                                        </div>
                                        <div class="filters-group full">
                                            <label for="create_description">Location description</label>
                                            <textarea id="create_description" name="location_description" class="review-notes" rows="4" placeholder="Landmark or exact spot." required>{{ old('location_description') }}</textarea>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_barangay_hall_id">Barangay</label>
                                            <select id="create_barangay_hall_id" name="barangay_hall_id" required>
                                                <option value="">Select barangay</option>
                                                @foreach ($createBarangayHalls as $hall)
                                                    <option value="{{ $hall->barangay_hall_id }}" @selected((string) old('barangay_hall_id') === (string) $hall->barangay_hall_id)>
                                                        {{ $hall->barangay?->barangay_name ?? $hall->hall_name ?? 'Barangay hall' }}
                                                        @if ($hall->barangay?->city?->city_name)
                                                            - {{ $hall->barangay->city->city_name }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if ($mode === 'admin')
                                            <div class="filters-group">
                                                <label for="create_station_id">Linked station</label>
                                                <select id="create_station_id" name="station_id">
                                                    <option value="">No linked station</option>
                                                    @foreach ($stations as $station)
                                                        <option value="{{ $station->station_id }}" @selected((string) old('station_id') === (string) $station->station_id)>{{ $station->station_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" name="station_id" value="{{ $currentStationId }}">
                                        @endif
                                        <div class="filters-group">
                                            <label for="create_street_or_sitio">Street / Purok / Sitio</label>
                                            <input id="create_street_or_sitio" type="text" name="street_or_sitio" value="{{ old('street_or_sitio') }}" placeholder="Optional area name.">
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_status">Record status</label>
                                            <select id="create_status" name="status" required>
                                                <option value="pending" @selected(old('status', 'verified') === 'pending')>Pending</option>
                                                <option value="verified" @selected(old('status', 'verified') === 'verified')>Verified</option>
                                                <option value="resolved" @selected(old('status', 'verified') === 'resolved')>Resolved</option>
                                            </select>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_visibility">Visibility</label>
                                            <select id="create_visibility" name="visibility" required>
                                                <option value="active" @selected(old('visibility', 'active') === 'active')>Visible / Active</option>
                                                <option value="inactive" @selected(old('visibility') === 'inactive')>Hidden / Inactive</option>
                                            </select>
                                        </div>
                                        <div class="map-picker-card">
                                            <label>Select area on map</label>
                                            <div class="map-picker-status">
                                                <div id="createMapSelectionState" class="map-picker-status__label">
                                                    <i class="fa-regular fa-circle"></i>
                                                    <span>Area not selected yet</span>
                                                </div>
                                                <div id="createMapSelectionCoords" class="map-picker-status__coords">Tap the map to choose a spot.</div>
                                            </div>
                                            <div class="map-picker-toolbar">
                                                <button type="button" id="confirmCreateMapSelection" class="map-picker-btn">
                                                    <i class="fa-solid fa-check"></i>
                                                    Use This Spot
                                                </button>
                                                <span class="map-picker-help">Orange pins show barangays in your allowed city. Tap anywhere on the map, then confirm.</span>
                                            </div>
                                            <div
                                                id="createEntryMapPicker"
                                                class="map-picker-canvas"
                                                data-halls='@json($createBarangayHallMapPayload)'
                                                data-initial-lat="{{ old('lat') }}"
                                                data-initial-lng="{{ old('lng') }}"
                                            ></div>
                                        </div>
                                        <div class="filters-group full">
                                            <label for="create_remarks">Notes</label>
                                            <textarea id="create_remarks" name="remarks" class="review-notes" rows="3" placeholder="Short notes if needed.">{{ old('remarks') }}</textarea>
                                        </div>
                                    </div>
                                </section>

                                <section class="manage-form-card" data-mode-section="risk" style="{{ old('entry_type', 'risk') === 'risk' ? '' : 'display:none;' }}">
                                    <h4>Risk details</h4>
                                    <p>Choose the risk classification used by the live marker.</p>
                                    <div class="manage-field-grid">
                                        <div class="filters-group">
                                            <label for="create_risk_type">Risk type</label>
                                            <select id="create_risk_type" name="risk_type" data-risk-type-select>
                                                <option value="">Select risk type</option>
                                                @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                    <optgroup label="{{ $groupLabel }}">
                                                        @foreach ($groupValues as $value)
                                                            <option value="{{ $value }}" @selected(old('risk_type') === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_risk_condition">Risk condition</label>
                                            <select id="create_risk_condition" data-risk-condition-select data-current-value="{{ old('condition_code') }}">
                                                <option value="">Select risk condition</option>
                                            </select>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_assessment_level">Assessment level</label>
                                            <select id="create_assessment_level" name="assessment_level">
                                                <option value="">Select level</option>
                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('assessment_level') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_risk_score">Risk score</label>
                                            <input id="create_risk_score" type="number" step="0.01" min="0" max="100" name="risk_score" value="{{ old('risk_score') }}">
                                        </div>
                                    </div>
                                </section>

                                <section class="manage-form-card" data-mode-section="hazard" style="{{ old('entry_type', 'risk') === 'hazard' ? '' : 'display:none;' }}">
                                    <h4>Hazard details</h4>
                                    <p>Choose the hazard classification used by the live marker.</p>
                                    <div class="manage-field-grid">
                                        <div class="filters-group">
                                            <label for="create_hazard_category">Hazard type</label>
                                            <select id="create_hazard_category" name="hazard_category" data-hazard-type-select>
                                                <option value="">Select hazard type</option>
                                                @foreach ($hazardTypeOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('hazard_category') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="filters-group">
                                            <label for="create_hazard_condition">Hazard condition</label>
                                            <select id="create_hazard_condition" data-hazard-condition-select data-current-value="{{ old('condition_code') }}">
                                                <option value="">Select hazard condition</option>
                                            </select>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <aside class="manage-form-stack">
                                <section class="manage-form-card create-entry-actions">
                                    <h4>Save behavior</h4>
                                    <p>This creates a live entry directly on this page, without leaving risk reviews.</p>
                                    <div class="review-actions">
                                        <button type="submit" class="action-btn action-btn--approve">
                                            <i class="fa-solid fa-plus mr-2"></i>Create Entry
                                        </button>
                                    </div>
                                </section>
                            </aside>
                        </div>
                    </form>
                </div>
            </div>

            <div id="riskReviewResults">
            <section class="queue-card">
                <div class="queue-head">
                    <div>
                        <h2>{{ $mode === 'chief' ? 'Requests' : 'Review Request List' }}</h2>
                        <p>{{ $mode === 'chief' ? 'Review incoming hazard and risk requests.' : 'Use this queue to inspect barangay-submitted hazard and risk entries before they are finalized in operations.' }}</p>
                    </div>
                    <div class="queue-count">{{ $reviewQueue->total() }} item{{ $reviewQueue->total() === 1 ? '' : 's' }}</div>
                </div>

                @if ($reviewQueue->isEmpty())
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open text-3xl mb-3"></i>
                        <p>No hazard or risk requests matched the current filters.</p>
                        <p class="empty-state__hint">
                            Need one?
                            <button type="button" class="empty-state__link create-entry-trigger" data-review-open="create-risk-area-modal">Create a new hazard or risk entry.</button>
                        </p>
                    </div>
                @else
                    <div class="request-list">
                        @foreach ($reviewQueue as $item)
                            @php
                                $entryType = strtolower($item->entry_type ?? 'risk');
                                $reviewStatus = strtolower($item->review_status ?? 'pending');
                                $reviewType = strtolower($item->review_type ?? 'create');
                                $barangayName = $item->barangayHall?->barangay?->barangay_name ?? 'Unassigned barangay';
                                $cityName = $item->barangayHall?->barangay?->city?->city_name ?? null;
                                $typeLabel = $entryType === 'hazard'
                                    ? ($item->hazard_category ? ucwords(str_replace('_', ' ', $item->hazard_category)) : 'Hazard')
                                    : ($item->risk_type ? ucwords(str_replace('_', ' ', $item->risk_type)) : 'Risk');
                                $typeIcon = $entryType === 'hazard'
                                    ? ($hazardTypeIcons[$item->hazard_category ?? ''] ?? 'fa-triangle-exclamation')
                                    : ($riskTypeIcons[$item->risk_type ?? ''] ?? 'fa-layer-group');
                                $finalEntryType = in_array($entryType, ['hazard', 'risk'], true) ? $entryType : 'risk';
                                $finalRiskType = array_key_exists($item->risk_type ?? '', $riskTypeOptions)
                                    ? $item->risk_type
                                    : array_key_first($riskTypeOptions);
                                $finalHazardCategory = array_key_exists($item->hazard_category ?? '', $hazardTypeOptions)
                                    ? $item->hazard_category
                                    : array_key_first($hazardTypeOptions);
                                $finalConditionCode = $item->condition_code ?? $item->mode;
                            @endphp
                            <article class="request-tile">
                                <div>
                                    <h3 class="request-tile__title">{{ $item->marker?->title ?? 'Untitled request' }}</h3>
                                    <p class="request-tile__sub">
                                        {{ $barangayName }}@if($cityName), {{ $cityName }}@endif
                                        · {{ $item->street_or_sitio ?: 'General barangay area' }}
                                        · {{ $item->station?->station_name ?: 'No station assigned' }}
                                    </p>
                                    <div class="request-tile__meta">
                                            <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}">
                                                <i class="fa-solid {{ $typeIcon }}"></i>
                                                {{ strtoupper($entryType) }}
                                            </span>
                                            <span class="chip chip--neutral">{{ strtoupper($reviewType) }} REQUEST</span>
                                            <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                            <span class="chip chip--neutral">{{ $typeLabel }}</span>
                                            @if ($item->assessment_level)
                                                <span class="chip chip--neutral">ASSESSMENT · {{ strtoupper($item->assessment_level) }}</span>
                                            @endif
                                    </div>
                                </div>
                                <div class="request-tile__trailing">
                                    <button
                                        type="button"
                                        class="review-trigger"
                                        data-review-open="review-modal-{{ $item->risk_area_id }}"
                                    >
                                        Review
                                    </button>
                                </div>
                            </article>

                            <div class="review-modal" id="review-modal-{{ $item->risk_area_id }}" aria-hidden="true">
                                <div class="review-modal__panel">
                                    <div class="review-modal__head">
                                        <div>
                                            <h3 class="review-modal__title"><i class="fa-solid {{ $typeIcon }}" style="margin-right:10px;"></i>{{ $item->marker?->title ?? 'Untitled request' }}</h3>
                                            <p class="review-modal__copy">Review the full hazard or risk submission details, then approve or reject this request.</p>
                                        </div>
                                        <button type="button" class="review-modal__close" data-review-close>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <div class="request-tile__meta">
                                        <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}">{{ strtoupper($entryType) }}</span>
                                        <span class="chip chip--neutral">{{ strtoupper($reviewType) }} REQUEST</span>
                                        <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                        <span class="chip chip--neutral">{{ $typeLabel }}</span>
                                    </div>

                                    <div class="request-grid" style="margin-top: 18px;">
                                        <div class="request-field">
                                            <div class="request-field__label">Barangay</div>
                                            <div class="request-field__value">{{ $barangayName }}@if($cityName), {{ $cityName }}@endif</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Street / Sitio</div>
                                            <div class="request-field__value">{{ $item->street_or_sitio ?: 'General barangay area' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Coordinates</div>
                                            <div class="request-field__value">
                                                {{ $item->marker?->lat !== null ? number_format((float) $item->marker?->lat, 6) : 'N/A' }},
                                                {{ $item->marker?->lng !== null ? number_format((float) $item->marker?->lng, 6) : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Reported By</div>
                                            <div class="request-field__value">{{ $item->reporter?->first_name ? trim($item->reporter->first_name . ' ' . $item->reporter->last_name) : 'Unknown user' }}</div>
                                        </div>
                                    </div>

                                    <div class="request-field" style="margin-top: 18px;">
                                        <div class="request-field__label">Description</div>
                                        <div class="request-field__value">
                                            <p class="request-copy">{{ $item->marker?->description ?: 'No description provided.' }}</p>
                                        </div>
                                    </div>

                                    <div class="request-grid" style="margin-top: 18px;">
                                        <div class="request-field">
                                            <div class="request-field__label">Condition</div>
                                            <div class="request-field__value">{{ $item->condition_label ?: 'Not specified' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Current Status</div>
                                            <div class="request-field__value">{{ strtoupper($item->status ?? 'pending') }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Risk Score</div>
                                            <div class="request-field__value">{{ $item->risk_score !== null ? number_format((float) $item->risk_score, 2) : 'Not scored' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Affected Households</div>
                                            <div class="request-field__value">{{ $item->estimated_affected_households ?? 'Not provided' }}</div>
                                        </div>
                                    </div>

                                    @if ($item->photos->isNotEmpty())
                                        <div class="request-field" style="margin-top: 18px;">
                                            <div class="request-field__label">Attached Photos</div>
                                            <div class="photo-strip">
                                                @foreach ($item->photos as $photo)
                                                    <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener">
                                                        <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($item->reviewer || $item->review_notes)
                                        <div class="review-history">
                                            <strong>Latest Review</strong><br>
                                            Status: {{ strtoupper($reviewStatus) }}
                                            @if ($item->reviewer)
                                                · Reviewed by {{ trim(($item->reviewer->first_name ?? '') . ' ' . ($item->reviewer->last_name ?? '')) }}
                                            @endif
                                            @if ($item->reviewed_at)
                                                · {{ $item->reviewed_at->format('M d, Y h:i A') }}
                                            @endif
                                            @if ($item->review_notes)
                                                <br>Notes: {{ $item->review_notes }}
                                            @endif
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route($reviewRouteName, $item->risk_area_id) }}" style="margin-top: 20px;" data-final-classification-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="entry_type" value="{{ $filters['entry_type'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">

                                        @if ($reviewType !== 'delete')
                                            <input type="hidden" name="final_entry_type" value="{{ $finalEntryType }}" data-entry-type-input>
                                            <input type="hidden" name="final_condition_code" value="{{ $finalConditionCode }}" data-condition-code-input>

                                            <section class="manage-form-card" style="margin-bottom: 16px;">
                                                <h4>Final classification</h4>
                                                <p>Before approval, confirm whether this submission should become a live hazard or risk marker.</p>
                                                <div class="manage-toggle">
                                                    <button type="button" class="manage-toggle__btn {{ $finalEntryType === 'hazard' ? 'is-active' : '' }}" data-manage-mode="hazard">Hazard</button>
                                                    <button type="button" class="manage-toggle__btn {{ $finalEntryType === 'risk' ? 'is-active' : '' }}" data-manage-mode="risk">Risk</button>
                                                </div>

                                                <div class="manage-field-grid" data-mode-section="risk" style="{{ $finalEntryType === 'risk' ? '' : 'display:none;' }}">
                                                    <div class="filters-group">
                                                        <label for="review_risk_type_{{ $item->risk_area_id }}">Risk type</label>
                                                        <select id="review_risk_type_{{ $item->risk_area_id }}" name="final_risk_type" data-risk-type-select>
                                                            <option value="">Select risk type</option>
                                                            @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                                <optgroup label="{{ $groupLabel }}">
                                                                    @foreach ($groupValues as $value)
                                                                        <option value="{{ $value }}" @selected($finalRiskType === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="filters-group">
                                                        <label for="review_risk_condition_{{ $item->risk_area_id }}">Risk condition</label>
                                                        <select id="review_risk_condition_{{ $item->risk_area_id }}" data-risk-condition-select data-current-value="{{ $finalConditionCode }}">
                                                            <option value="">Select risk condition</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="manage-field-grid" data-mode-section="hazard" style="{{ $finalEntryType === 'hazard' ? '' : 'display:none;' }}">
                                                    <div class="filters-group">
                                                        <label for="review_hazard_type_{{ $item->risk_area_id }}">Hazard type</label>
                                                        <select id="review_hazard_type_{{ $item->risk_area_id }}" name="final_hazard_category" data-hazard-type-select>
                                                            <option value="">Select hazard type</option>
                                                            @foreach ($hazardTypeOptions as $value => $label)
                                                                <option value="{{ $value }}" @selected($finalHazardCategory === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="filters-group">
                                                        <label for="review_hazard_condition_{{ $item->risk_area_id }}">Hazard condition</label>
                                                        <select id="review_hazard_condition_{{ $item->risk_area_id }}" data-hazard-condition-select data-current-value="{{ $finalConditionCode }}">
                                                            <option value="">Select hazard condition</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </section>
                                        @endif

                                        <div class="review-form__row">
                                            <div class="filters-group">
                                                <label for="review_notes_{{ $item->risk_area_id }}">Review Notes</label>
                                                <textarea
                                                    id="review_notes_{{ $item->risk_area_id }}"
                                                    name="review_notes"
                                                    class="review-notes"
                                                    rows="3"
                                                    placeholder="Short review note."
                                                ></textarea>
                                            </div>

                                            <div class="review-actions">
                                                <button type="submit" name="decision" value="approved" class="action-btn action-btn--approve">
                                                    <i class="fa-solid fa-circle-check mr-2"></i>Approve
                                                </button>
                                                <button type="submit" name="decision" value="rejected" class="action-btn action-btn--reject">
                                                    <i class="fa-solid fa-ban mr-2"></i>Reject
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pagination-wrap">
                        {{ $reviewQueue->links() }}
                    </div>
                @endif
            </section>

            <section class="queue-card" style="margin-top: 22px;">
                <div class="queue-head">
                    <div>
                        <h2>{{ $mode === 'chief' ? 'Live Entries' : 'Active Hazards & Risks' }}</h2>
                        <p>{{ $mode === 'chief' ? 'Manage active hazard and risk records for your station.' : 'These are already live records. Chief and admin can still adjust visibility, update details, or remove them from the system here.' }}</p>
                    </div>
                    <div class="queue-count">{{ $activeEntries->count() }} active</div>
                </div>

                @if ($activeEntries->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-shield-heart text-3xl mb-3"></i>
                        <p>No active hazard or risk entries matched the current filters.</p>
                        <p class="empty-state__hint">
                            Need one?
                            <button type="button" class="empty-state__link create-entry-trigger" data-review-open="create-risk-area-modal">Create a new hazard or risk entry.</button>
                        </p>
                    </div>
                @else
                    <div class="request-list">
                        @foreach ($activeEntries as $item)
                            @php
                                $entryType = strtolower($item->entry_type ?? 'risk');
                                $reviewStatus = strtolower($item->review_status ?? 'approved');
                                $barangayName = $item->barangayHall?->barangay?->barangay_name ?? 'Unassigned barangay';
                                $cityName = $item->barangayHall?->barangay?->city?->city_name ?? null;
                                $typeLabel = $entryType === 'hazard'
                                    ? ($item->hazard_category ? ucwords(str_replace('_', ' ', $item->hazard_category)) : 'Hazard')
                                    : ($item->risk_type ? ucwords(str_replace('_', ' ', $item->risk_type)) : 'Risk');
                                $visibility = strtolower($item->marker?->status ?? 'active');
                                $typeIcon = $entryType === 'hazard'
                                    ? ($hazardTypeIcons[$item->hazard_category ?? ''] ?? 'fa-triangle-exclamation')
                                    : ($riskTypeIcons[$item->risk_type ?? ''] ?? 'fa-layer-group');
                            @endphp
                            <article class="request-tile">
                                <div>
                                    <h3 class="request-tile__title">{{ $item->marker?->title ?? 'Untitled active entry' }}</h3>
                                    <p class="request-tile__sub">
                                        {{ $barangayName }}@if($cityName), {{ $cityName }}@endif
                                        · {{ $item->street_or_sitio ?: 'General barangay area' }}
                                        · {{ $item->station?->station_name ?: 'No station assigned' }}
                                    </p>
                                    <div class="request-tile__meta">
                                        <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}"><i class="fa-solid {{ $typeIcon }}"></i>{{ strtoupper($entryType) }}</span>
                                        <span class="chip chip--neutral"><i class="fa-solid {{ $typeIcon }}"></i>{{ $typeLabel }}</span>
                                        <span class="chip chip--neutral">VISIBILITY · {{ strtoupper($visibility) }}</span>
                                        <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                        @if ($item->assessment_level)
                                            <span class="chip chip--neutral">ASSESSMENT · {{ strtoupper($item->assessment_level) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="request-tile__trailing">
                                    <button
                                        type="button"
                                        class="review-trigger"
                                        data-review-open="manage-modal-{{ $item->risk_area_id }}"
                                    >
                                        Manage
                                    </button>
                                </div>
                            </article>

                            <div class="review-modal" id="manage-modal-{{ $item->risk_area_id }}" aria-hidden="true">
                                <div class="review-modal__panel">
                                    <div class="review-modal__head">
                                        <div>
                                            <h3 class="review-modal__title"><i class="fa-solid {{ $typeIcon }}" style="margin-right:10px;"></i>{{ $item->marker?->title ?? 'Untitled active entry' }}</h3>
                                            <p class="review-modal__copy">Update live visibility and record details here, or remove the active hazard/risk if it should no longer remain on the operational map.</p>
                                        </div>
                                        <button type="button" class="review-modal__close" data-review-close>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route($activeUpdateRouteName, $item->risk_area_id) }}" enctype="multipart/form-data" data-manage-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">

                                        <input type="hidden" name="entry_type" value="{{ $entryType }}" data-entry-type-input>
                                        <input type="hidden" name="condition_code" value="{{ $item->condition_code ?? $item->mode }}" data-condition-code-input>

                                        <div class="manage-form-grid">
                                            <div class="manage-form-stack">
                                                <section class="manage-form-card">
                                                    <h4>Core details</h4>
                                                    <p>Use the same structure as the barangay hazard/risk form so live records stay consistent with submitted entries.</p>
                                                    <div class="manage-toggle">
                                                        <button type="button" class="manage-toggle__btn {{ $entryType === 'hazard' ? 'is-active' : '' }}" data-manage-mode="hazard">Hazard</button>
                                                        <button type="button" class="manage-toggle__btn {{ $entryType === 'risk' ? 'is-active' : '' }}" data-manage-mode="risk">Risk</button>
                                                    </div>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group full">
                                                            <label for="active_title_{{ $item->risk_area_id }}">Title</label>
                                                            <input id="active_title_{{ $item->risk_area_id }}" type="text" name="title" value="{{ $item->marker?->title }}">
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_description_{{ $item->risk_area_id }}">Location description</label>
                                                            <textarea id="active_description_{{ $item->risk_area_id }}" name="location_description" class="review-notes" rows="4">{{ $item->marker?->description }}</textarea>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_street_{{ $item->risk_area_id }}">Street / Purok / Sitio</label>
                                                            <input id="active_street_{{ $item->risk_area_id }}" type="text" name="street_or_sitio" value="{{ $item->street_or_sitio }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_station_{{ $item->risk_area_id }}">Linked station</label>
                                                            <select id="active_station_{{ $item->risk_area_id }}" name="station_id">
                                                                <option value="">No linked station</option>
                                                                @foreach ($stations as $station)
                                                                    <option value="{{ $station->station_id }}" @selected((string) $item->station_id === (string) $station->station_id)>{{ $station->station_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_status_{{ $item->risk_area_id }}">Record status</label>
                                                            <select id="active_status_{{ $item->risk_area_id }}" name="status">
                                                                <option value="pending" @selected(($item->status ?? '') === 'pending')>Pending</option>
                                                                <option value="verified" @selected(($item->status ?? '') === 'verified')>Verified</option>
                                                                <option value="resolved" @selected(($item->status ?? '') === 'resolved')>Resolved</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_visibility_{{ $item->risk_area_id }}">Visibility</label>
                                                            <select id="active_visibility_{{ $item->risk_area_id }}" name="visibility">
                                                                <option value="active" @selected(($item->marker?->status ?? 'active') === 'active')>Visible / Active</option>
                                                                <option value="inactive" @selected(($item->marker?->status ?? '') === 'inactive')>Hidden / Inactive</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_notes_{{ $item->risk_area_id }}">Notes</label>
                                                            <textarea id="active_notes_{{ $item->risk_area_id }}" name="notes" class="review-notes" rows="3">{{ $item->remarks }}</textarea>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_photos_{{ $item->risk_area_id }}">Add photo evidence</label>
                                                            <input id="active_photos_{{ $item->risk_area_id }}" type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp">
                                                            <div class="manage-inline-note">New photos are appended to the existing evidence gallery.</div>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="manage-form-card" data-mode-section="risk" style="{{ $entryType === 'risk' ? '' : 'display:none;' }}">
                                                    <h4>Area-level risk assessment</h4>
                                                    <p>These fields match the barangay risk-entry workflow for area-level fire exposure.</p>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group">
                                                            <label for="active_risk_type_{{ $item->risk_area_id }}">Risk type</label>
                                                            <select id="active_risk_type_{{ $item->risk_area_id }}" name="risk_type" data-risk-type-select>
                                                                <option value="">Select risk type</option>
                                                                @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                                    <optgroup label="{{ $groupLabel }}">
                                                                        @foreach ($groupValues as $value)
                                                                            <option value="{{ $value }}" @selected(($item->risk_type ?? '') === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_risk_condition_{{ $item->risk_area_id }}">Condition</label>
                                                            <select id="active_risk_condition_{{ $item->risk_area_id }}" data-risk-condition-select data-current-value="{{ $item->condition_code ?? $item->mode }}">
                                                                <option value="">Select risk condition</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_population_density_{{ $item->risk_area_id }}">Population density level</label>
                                                            <select id="active_population_density_{{ $item->risk_area_id }}" name="population_density_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->population_density_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_building_density_{{ $item->risk_area_id }}">Building density level</label>
                                                            <select id="active_building_density_{{ $item->risk_area_id }}" name="building_density_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->building_density_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_road_access_{{ $item->risk_area_id }}">Road accessibility</label>
                                                            <select id="active_road_access_{{ $item->risk_area_id }}" name="road_accessibility">
                                                                <option value="">Select accessibility</option>
                                                                @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'difficult' => 'Difficult', 'very_difficult' => 'Very Difficult'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->road_accessibility ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_assessment_{{ $item->risk_area_id }}">Assessment level</label>
                                                            <select id="active_assessment_{{ $item->risk_area_id }}" name="assessment_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->assessment_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_hydrant_distance_{{ $item->risk_area_id }}">Distance to hydrant (m)</label>
                                                            <input id="active_hydrant_distance_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="50000" name="distance_to_hydrant" value="{{ $item->distance_to_hydrant }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_water_distance_{{ $item->risk_area_id }}">Distance to water source (m)</label>
                                                            <input id="active_water_distance_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="50000" name="distance_to_water_source" value="{{ $item->distance_to_water_source }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_households_{{ $item->risk_area_id }}">Estimated affected households</label>
                                                            <input id="active_households_{{ $item->risk_area_id }}" type="number" min="0" max="100000" name="estimated_affected_households" value="{{ $item->estimated_affected_households }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_risk_score_{{ $item->risk_area_id }}">Risk score</label>
                                                            <input id="active_risk_score_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="100" name="risk_score" value="{{ $item->risk_score }}">
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_nearby_{{ $item->risk_area_id }}">Nearby establishments</label>
                                                            <textarea id="active_nearby_{{ $item->risk_area_id }}" name="nearby_establishments" class="review-notes" rows="3">{{ $item->nearby_establishments }}</textarea>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_assessment_description_{{ $item->risk_area_id }}">Assessment description</label>
                                                            <textarea id="active_assessment_description_{{ $item->risk_area_id }}" name="assessment_description" class="review-notes" rows="3">{{ $item->assessment_description }}</textarea>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="manage-form-card" data-mode-section="hazard" style="{{ $entryType === 'hazard' ? '' : 'display:none;' }}">
                                                    <h4>Hazard condition details</h4>
                                                    <p>These fields mirror the barangay hazard-report flow for observed unsafe conditions.</p>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group">
                                                            <label for="active_hazard_type_{{ $item->risk_area_id }}">Hazard type</label>
                                                            <select id="active_hazard_type_{{ $item->risk_area_id }}" name="hazard_category" data-hazard-type-select>
                                                                @foreach ($hazardTypeOptions as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->hazard_category ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_hazard_condition_{{ $item->risk_area_id }}">Condition</label>
                                                            <select id="active_hazard_condition_{{ $item->risk_area_id }}" data-hazard-condition-select data-current-value="{{ $item->condition_code ?? $item->mode }}">
                                                                <option value="">Select hazard condition</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="manage-chip-grid" style="margin-top: 14px;">
                                                        <label class="manage-chip"><input type="checkbox" name="near_hydrant" value="1" @checked($item->near_hydrant)> Near hydrant</label>
                                                        <label class="manage-chip"><input type="checkbox" name="near_water_source" value="1" @checked($item->near_water_source)> Near water source</label>
                                                        <label class="manage-chip"><input type="checkbox" name="blocked_hydrant" value="1" @checked($item->blocked_hydrant)> Blocked hydrant</label>
                                                        <label class="manage-chip"><input type="checkbox" name="informal_settlement" value="1" @checked($item->informal_settlement)> Informal settlement</label>
                                                        <label class="manage-chip"><input type="checkbox" name="cooking_fire_prone_activity" value="1" @checked($item->cooking_fire_prone_activity)> Cooking / fire-prone activity</label>
                                                        <label class="manage-chip"><input type="checkbox" name="combustible_materials_present" value="1" @checked($item->combustible_materials_present)> Combustible materials present</label>
                                                        <label class="manage-chip"><input type="checkbox" name="poor_waste_disposal" value="1" @checked($item->poor_waste_disposal)> Poor waste disposal</label>
                                                        <label class="manage-chip"><input type="checkbox" name="previous_fire_prone_location" value="1" @checked($item->previous_fire_prone_location)> Previous fire-prone location</label>
                                                    </div>
                                                    <div class="filters-group" style="margin-top: 14px;">
                                                        <label for="active_remarks_{{ $item->risk_area_id }}">Remarks</label>
                                                        <textarea id="active_remarks_{{ $item->risk_area_id }}" name="remarks" class="review-notes" rows="3">{{ $item->remarks }}</textarea>
                                                    </div>
                                                </section>
                                            </div>

                                            <aside class="manage-form-stack">
                                                <section class="manage-form-card">
                                                    <h4>Location and status</h4>
                                                    <p>These fields stay visible while you manage the live entry.</p>
                                                    <div class="request-grid">
                                                        <div class="request-field">
                                                            <div class="request-field__label">Barangay</div>
                                                            <div class="request-field__value">{{ $barangayName }}@if($cityName), {{ $cityName }}@endif</div>
                                                        </div>
                                                        <div class="request-field">
                                                            <div class="request-field__label">Coordinates</div>
                                                            <div class="request-field__value">
                                                                {{ $item->marker?->lat !== null ? number_format((float) $item->marker?->lat, 6) : 'N/A' }},
                                                                {{ $item->marker?->lng !== null ? number_format((float) $item->marker?->lng, 6) : 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </section>

                                                @if ($item->photos->isNotEmpty())
                                                    <section class="manage-form-card">
                                                        <h4>Attached photos</h4>
                                                        <p>Existing image evidence attached to this live record.</p>
                                                        <div class="photo-strip">
                                                            @foreach ($item->photos as $photo)
                                                                <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener">
                                                                    <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </section>
                                                @endif

                                                <section class="manage-form-card">
                                                    <h4>Save behavior</h4>
                                                    <p>The live record remains active, but all edited fields are updated using the same data structure as the barangay creation form.</p>
                                                    <div class="review-actions">
                                                        <button type="submit" class="action-btn action-btn--approve">
                                                            <i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </section>
                                            </aside>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route($activeDeleteRouteName, $item->risk_area_id) }}" style="margin-top: 14px;" onsubmit="return confirm('Remove this active hazard or risk entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="entry_type" value="{{ $filters['entry_type'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">
                                        <button type="submit" class="action-btn action-btn--reject">
                                            <i class="fa-solid fa-trash-can mr-2"></i>Remove Entry
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropdownButton = document.getElementById('userDropdownBtn');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            const filtersForm = document.getElementById('reviewFiltersForm');
            const searchInput = filtersForm?.querySelector('[data-auto-submit-search]');
            const filterInputs = filtersForm?.querySelectorAll('[data-auto-submit-filter]');
            const pageNotice = document.getElementById('reviewPageNotice');
            const pageNoticeClose = document.getElementById('reviewPageNoticeClose');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const openSidebar = document.getElementById('openSidebar');
            const createRiskAreaModal = document.getElementById('create-risk-area-modal');
            const resultsContainer = document.getElementById('riskReviewResults');
            const riskConditionOptions = @json($riskConditionOptions);
            const hazardConditionOptions = @json($hazardConditionOptions);
            const shouldOpenCreateModal = @json((bool) old('title'));
            const createForm = document.querySelector('[data-create-form]');
            const createLatInput = document.getElementById('create_lat');
            const createLngInput = document.getElementById('create_lng');
            const createBarangaySelect = document.getElementById('create_barangay_hall_id');
            const createMapElement = document.getElementById('createEntryMapPicker');
            const createMapConfirmButton = document.getElementById('confirmCreateMapSelection');
            const createMapSelectionState = document.getElementById('createMapSelectionState');
            const createMapSelectionCoords = document.getElementById('createMapSelectionCoords');
            const createMapHalls = (() => {
                try {
                    return JSON.parse(createMapElement?.dataset.halls || '[]');
                } catch (error) {
                    return [];
                }
            })();
            let searchSubmitTimeout = null;
            let createEntryMap = null;
            let createEntrySelectedMarker = null;
            let createEntryTentativeLatLng = null;
            let createEntryConfirmed = false;

            const showNotice = (notice) => {
                if (!notice) {
                    return;
                }

                requestAnimationFrame(() => {
                    notice.classList.add('show');
                });

                const dismiss = () => {
                    notice.classList.remove('show');
                    window.setTimeout(() => {
                        notice.parentElement?.remove();
                    }, 220);
                };

                pageNoticeClose?.addEventListener('click', dismiss, { once: true });
                window.setTimeout(dismiss, window.innerWidth <= 768 ? 3200 : 3800);
            };

            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isOpen = dropdownMenu.classList.toggle('show');
                    dropdownButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                document.addEventListener('click', (event) => {
                    if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.remove('show');
                        dropdownButton.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            if (sidebar && sidebarBackdrop && openSidebar) {
                openSidebar.addEventListener('click', () => {
                    sidebar.classList.add('open', 'active');
                    sidebarBackdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });

                sidebarBackdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open', 'active');
                    sidebarBackdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }

            const closeModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (window.innerWidth > 768) {
                    document.body.style.overflow = '';
                } else {
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                }
            };

            const openModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                if (window.innerWidth > 768) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                }

                if (modal === createRiskAreaModal && createEntryMap) {
                    window.setTimeout(() => {
                        createEntryMap.invalidateSize();
                    }, 80);
                }
            };

            const hallIcon = L.divIcon({
                className: '',
                html: '<div class="map-picker-hall-pin"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });

            const selectedIcon = L.divIcon({
                className: '',
                html: '<div class="map-picker-selected-pin"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12],
            });

            const updateCreateSelectionStatus = () => {
                if (!createMapSelectionState || !createMapSelectionCoords) {
                    return;
                }

                const icon = createMapSelectionState.querySelector('i');
                const text = createMapSelectionState.querySelector('span');
                const hasSelection = !!createEntryTentativeLatLng;

                createMapSelectionState.classList.toggle('is-ready', hasSelection && createEntryConfirmed);

                if (icon) {
                    icon.className = hasSelection && createEntryConfirmed
                        ? 'fa-solid fa-circle-check'
                        : hasSelection
                            ? 'fa-solid fa-location-dot'
                            : 'fa-regular fa-circle';
                }

                if (text) {
                    text.textContent = hasSelection
                        ? (createEntryConfirmed ? 'Area selected' : 'Spot picked, confirm it')
                        : 'Area not selected yet';
                }

                if (hasSelection) {
                    createMapSelectionCoords.textContent = `${Number(createEntryTentativeLatLng.lat).toFixed(6)}, ${Number(createEntryTentativeLatLng.lng).toFixed(6)}`;
                } else {
                    createMapSelectionCoords.textContent = 'Tap the map to choose a spot.';
                }

                if (createMapConfirmButton) {
                    createMapConfirmButton.classList.toggle('is-confirmed', hasSelection && createEntryConfirmed);
                    createMapConfirmButton.innerHTML = hasSelection && createEntryConfirmed
                        ? '<i class="fa-solid fa-check"></i> Spot Confirmed'
                        : '<i class="fa-solid fa-check"></i> Use This Spot';
                }
            };

            const setCreateTentativeSelection = (lat, lng, shouldConfirm = false) => {
                const parsedLat = Number(lat);
                const parsedLng = Number(lng);

                if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
                    return;
                }

                createEntryTentativeLatLng = { lat: parsedLat, lng: parsedLng };
                createEntryConfirmed = shouldConfirm;

                if (!createEntrySelectedMarker && createEntryMap) {
                    createEntrySelectedMarker = L.marker([parsedLat, parsedLng], { icon: selectedIcon }).addTo(createEntryMap);
                } else if (createEntrySelectedMarker) {
                    createEntrySelectedMarker.setLatLng([parsedLat, parsedLng]);
                }

                if (createLatInput) {
                    createLatInput.value = shouldConfirm ? parsedLat.toFixed(7) : '';
                }

                if (createLngInput) {
                    createLngInput.value = shouldConfirm ? parsedLng.toFixed(7) : '';
                }

                updateCreateSelectionStatus();
            };

            const centerCreateMapForHall = (hallId) => {
                if (!createEntryMap) {
                    return;
                }

                const selectedHall = createMapHalls.find((hall) => String(hall.id) === String(hallId));

                if (!selectedHall || selectedHall.lat === null || selectedHall.lng === null) {
                    return;
                }

                const lat = Number(selectedHall.lat);
                const lng = Number(selectedHall.lng);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                createEntryMap.setView([lat, lng], 16, { animate: true });
            };

            const initCreateMap = () => {
                if (!createMapElement || typeof L === 'undefined') {
                    return;
                }

                if (!createEntryMap) {
                    const firstHallWithCoords = createMapHalls.find((hall) => hall.lat !== null && hall.lng !== null);
                    const initialLat = Number(createMapElement.dataset.initialLat || firstHallWithCoords?.lat || 16.0432);
                    const initialLng = Number(createMapElement.dataset.initialLng || firstHallWithCoords?.lng || 120.3331);

                    createEntryMap = L.map(createMapElement, {
                        zoomControl: true,
                        attributionControl: true,
                    }).setView([initialLat, initialLng], firstHallWithCoords ? 15 : 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(createEntryMap);

                    createEntryHallMarkers = createMapHalls
                        .filter((hall) => hall.lat !== null && hall.lng !== null)
                        .map((hall) => {
                            const marker = L.marker([Number(hall.lat), Number(hall.lng)], { icon: hallIcon }).addTo(createEntryMap);
                            marker.bindPopup(`<strong>${hall.name}</strong>${hall.city ? `<br>${hall.city}` : ''}`);
                            marker.on('click', () => {
                                if (createBarangaySelect) {
                                    createBarangaySelect.value = String(hall.id);
                                }
                                centerCreateMapForHall(hall.id);
                            });

                            return marker;
                        });

                    createEntryMap.on('click', (event) => {
                        setCreateTentativeSelection(event.latlng.lat, event.latlng.lng, false);
                    });
                }

                if (createLatInput?.value && createLngInput?.value) {
                    setCreateTentativeSelection(createLatInput.value, createLngInput.value, true);
                    createEntryMap.setView([Number(createLatInput.value), Number(createLngInput.value)], 16, { animate: false });
                } else if (createBarangaySelect?.value) {
                    centerCreateMapForHall(createBarangaySelect.value);
                }

                updateCreateSelectionStatus();
                window.setTimeout(() => createEntryMap?.invalidateSize(), 80);
            };

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.review-modal.is-open').forEach((modal) => {
                    closeModal(modal);
                });
            });

            const populateConditionSelect = (select, options, selectedValue) => {
                if (!select) {
                    return;
                }

                const currentPlaceholder = select.querySelector('option')?.textContent || 'Select condition';
                select.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = currentPlaceholder;
                select.appendChild(placeholder);

                Object.entries(options || {}).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = value === selectedValue;
                    select.appendChild(option);
                });
            };

            const initializeManagedForms = (root = document) => {
                root.querySelectorAll('[data-manage-form], [data-final-classification-form], [data-create-form]').forEach((form) => {
                    if (form.dataset.reviewFormBound === 'true') {
                        return;
                    }

                    form.dataset.reviewFormBound = 'true';

                    const entryTypeInput = form.querySelector('[data-entry-type-input]');
                    const conditionCodeInput = form.querySelector('[data-condition-code-input]');
                    const modeButtons = form.querySelectorAll('[data-manage-mode]');
                    const riskTypeSelect = form.querySelector('[data-risk-type-select]');
                    const hazardTypeSelect = form.querySelector('[data-hazard-type-select]');
                    const riskConditionSelect = form.querySelector('[data-risk-condition-select]');
                    const hazardConditionSelect = form.querySelector('[data-hazard-condition-select]');
                    const riskSection = form.querySelector('[data-mode-section="risk"]');
                    const hazardSection = form.querySelector('[data-mode-section="hazard"]');

                    const setMode = (mode) => {
                        if (entryTypeInput) {
                            entryTypeInput.value = mode;
                        }

                        modeButtons.forEach((button) => {
                            button.classList.toggle('is-active', button.dataset.manageMode === mode);
                        });

                        if (riskSection) {
                            riskSection.style.display = mode === 'risk' ? '' : 'none';
                        }

                        if (hazardSection) {
                            hazardSection.style.display = mode === 'hazard' ? '' : 'none';
                        }

                        if (mode === 'risk') {
                            const options = riskConditionOptions[riskTypeSelect?.value || ''] || {};
                            const selected = riskConditionSelect?.dataset.currentValue || conditionCodeInput?.value || '';
                            populateConditionSelect(riskConditionSelect, options, selected);
                            if (conditionCodeInput && riskConditionSelect) {
                                conditionCodeInput.value = riskConditionSelect.value || '';
                            }
                        } else {
                            const options = hazardConditionOptions[hazardTypeSelect?.value || ''] || {};
                            const selected = hazardConditionSelect?.dataset.currentValue || conditionCodeInput?.value || '';
                            populateConditionSelect(hazardConditionSelect, options, selected);
                            if (conditionCodeInput && hazardConditionSelect) {
                                conditionCodeInput.value = hazardConditionSelect.value || '';
                            }
                        }
                    };

                    modeButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            if (riskConditionSelect) {
                                riskConditionSelect.dataset.currentValue = '';
                            }
                            if (hazardConditionSelect) {
                                hazardConditionSelect.dataset.currentValue = '';
                            }
                            setMode(button.dataset.manageMode || 'risk');
                        });
                    });

                    riskTypeSelect?.addEventListener('change', () => {
                        populateConditionSelect(riskConditionSelect, riskConditionOptions[riskTypeSelect.value || ''] || {}, '');
                        if ((entryTypeInput?.value || 'risk') === 'risk' && conditionCodeInput && riskConditionSelect) {
                            conditionCodeInput.value = riskConditionSelect.value || '';
                        }
                    });

                    hazardTypeSelect?.addEventListener('change', () => {
                        populateConditionSelect(hazardConditionSelect, hazardConditionOptions[hazardTypeSelect.value || ''] || {}, '');
                        if ((entryTypeInput?.value || 'risk') === 'hazard' && conditionCodeInput && hazardConditionSelect) {
                            conditionCodeInput.value = hazardConditionSelect.value || '';
                        }
                    });

                    riskConditionSelect?.addEventListener('change', () => {
                        if ((entryTypeInput?.value || 'risk') === 'risk' && conditionCodeInput) {
                            conditionCodeInput.value = riskConditionSelect.value || '';
                        }
                    });

                    hazardConditionSelect?.addEventListener('change', () => {
                        if ((entryTypeInput?.value || 'risk') === 'hazard' && conditionCodeInput) {
                            conditionCodeInput.value = hazardConditionSelect.value || '';
                        }
                    });

                    setMode(entryTypeInput?.value || 'risk');
                });
            };

            const fetchReviewResults = async (url, options = {}) => {
                if (!resultsContainer) {
                    window.location.href = url;
                    return;
                }

                resultsContainer.classList.add('is-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }

                    const html = await response.text();
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const incomingResults = parsed.getElementById('riskReviewResults');

                    if (!incomingResults) {
                        window.location.href = url;
                        return;
                    }

                    resultsContainer.innerHTML = incomingResults.innerHTML;
                    initializeManagedForms(resultsContainer);

                    if (options.pushHistory) {
                        window.history.pushState({ riskReviewDynamic: true }, '', url);
                    } else {
                        window.history.replaceState({ riskReviewDynamic: true }, '', url);
                    }
                } catch (error) {
                    window.location.href = url;
                } finally {
                    resultsContainer.classList.remove('is-loading');
                }
            };

            if (filtersForm) {
                const submitFilters = () => {
                    if (searchSubmitTimeout) {
                        clearTimeout(searchSubmitTimeout);
                        searchSubmitTimeout = null;
                    }

                    const actionUrl = new URL(filtersForm.action || window.location.href, window.location.origin);
                    const formData = new FormData(filtersForm);
                    actionUrl.search = new URLSearchParams(formData).toString();
                    fetchReviewResults(actionUrl.toString());
                };

                filtersForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    submitFilters();
                });

                searchInput?.addEventListener('input', () => {
                    if (searchSubmitTimeout) {
                        clearTimeout(searchSubmitTimeout);
                    }

                    searchSubmitTimeout = setTimeout(() => {
                        submitFilters();
                    }, 350);
                });

                filterInputs?.forEach((input) => {
                    input.addEventListener('change', submitFilters);
                });
            }

            document.addEventListener('click', (event) => {
                const openTrigger = event.target.closest('[data-review-open]');
                if (openTrigger) {
                    const modal = document.getElementById(openTrigger.dataset.reviewOpen);
                    if (openTrigger.dataset.reviewOpen === 'create-risk-area-modal') {
                        initCreateMap();
                    }
                    openModal(modal);
                    return;
                }

                const closeTrigger = event.target.closest('[data-review-close]');
                if (closeTrigger) {
                    closeModal(closeTrigger.closest('.review-modal'));
                    return;
                }

                const modalBackdrop = event.target.classList?.contains('review-modal') ? event.target : null;
                if (modalBackdrop) {
                    closeModal(modalBackdrop);
                    return;
                }

                const paginationLink = event.target.closest('#riskReviewResults .pagination-wrap a');
                if (paginationLink?.href) {
                    event.preventDefault();
                    fetchReviewResults(paginationLink.href, { pushHistory: true });
                }
            });

            window.addEventListener('popstate', () => {
                if (!resultsContainer) {
                    return;
                }

                fetchReviewResults(window.location.href);
            });

            createBarangaySelect?.addEventListener('change', () => {
                if (!createEntryMap) {
                    return;
                }

                centerCreateMapForHall(createBarangaySelect.value);
            });

            createMapConfirmButton?.addEventListener('click', () => {
                if (!createEntryTentativeLatLng) {
                    updateCreateSelectionStatus();
                    return;
                }

                setCreateTentativeSelection(createEntryTentativeLatLng.lat, createEntryTentativeLatLng.lng, true);
            });

            createForm?.addEventListener('submit', (event) => {
                if (!createEntryTentativeLatLng || !createEntryConfirmed || !createLatInput?.value || !createLngInput?.value) {
                    event.preventDefault();
                    updateCreateSelectionStatus();
                    createMapSelectionCoords.textContent = 'Choose a map spot and confirm it first.';
                    createMapSelectionState?.classList.remove('is-ready');
                    const selectionIcon = createMapSelectionState?.querySelector('i');
                    const selectionText = createMapSelectionState?.querySelector('span');

                    if (selectionIcon) {
                        selectionIcon.className = 'fa-solid fa-circle-exclamation';
                    }

                    if (selectionText) {
                        selectionText.textContent = 'Map location required';
                    }
                    initCreateMap();
                    openModal(createRiskAreaModal);
                }
            });

            initializeManagedForms(document);
            showNotice(pageNotice);

            if (shouldOpenCreateModal && createRiskAreaModal) {
                initCreateMap();
                openModal(createRiskAreaModal);
            }
        });
    </script>
    @if ($mode === 'chief')
        @include('chief.partials.logout-confirmation')
    @endif
</body>
</html>
