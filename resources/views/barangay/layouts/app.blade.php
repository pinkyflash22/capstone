<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <title>PyroSense · @yield('page_title', 'Barangay Portal')</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/js/location-accuracy.js', 'resources/js/dynamic-actions.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --barangay-safe-top: env(safe-area-inset-top, 0px);
            --barangay-safe-bottom: env(safe-area-inset-bottom, 0px);
            --barangay-mobile-bottom-h: calc(84px + var(--barangay-safe-bottom));
        }

        html,
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            overflow-x: hidden;
            min-height: 100dvh;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100dvh;
            padding: 20px 24px 32px;
        }

        body.barangay-portal-shell.menu-open {
            overflow: hidden;
            touch-action: none;
        }

        .portal-top-bar {
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

        .portal-top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .portal-notification-shell {
            position: relative;
        }

        .portal-notification-btn {
            position: relative;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            transition: all 0.2s ease;
        }

        .portal-notification-btn:hover {
            border-color: rgba(245, 158, 11, 0.26);
            color: #c2410c;
            transform: translateY(-1px);
        }

        .portal-notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(239, 68, 68, 0.24);
        }

        .portal-notification-badge.is-hidden {
            display: none;
        }

        .portal-notification-panel {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: min(380px, calc(100vw - 48px));
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.16);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.22s ease;
            overflow: hidden;
            z-index: 55;
        }

        .portal-notification-panel.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .portal-notification-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px 14px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
        }

        .portal-notification-panel-head strong {
            display: block;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .portal-notification-panel-head span {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            margin-top: 2px;
        }

        .portal-notification-link-btn {
            border: none;
            background: transparent;
            color: #ea580c;
            font-size: 0.76rem;
            font-weight: 800;
            padding: 0;
        }

        .portal-notification-list {
            max-height: 420px;
            overflow-y: auto;
        }

        .portal-notification-item {
            width: 100%;
            border: none;
            background: transparent;
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 12px;
            text-align: left;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.9);
            transition: background 0.2s ease;
        }

        .portal-notification-item:hover {
            background: #fff7ed;
        }

        .portal-notification-item.is-unread {
            background: rgba(255, 247, 237, 0.62);
        }

        .portal-notification-item:last-child {
            border-bottom: none;
        }

        .portal-notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.08);
        }

        .portal-notification-copy {
            min-width: 0;
        }

        .portal-notification-copy strong,
        .portal-notification-empty strong {
            display: block;
            color: #0f172a;
            font-size: 0.86rem;
            line-height: 1.35;
        }

        .portal-notification-copy p,
        .portal-notification-empty p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.5;
        }

        .portal-notification-meta {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: #94a3b8;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .portal-notification-empty {
            padding: 26px 22px;
            text-align: center;
        }

        .portal-report-fire-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 999px;
            text-decoration: none;
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 800;
            white-space: nowrap;
            flex-shrink: 0;
            box-shadow: 0 12px 24px rgba(234, 88, 12, 0.18);
            transition: all 0.2s ease;
        }

        .portal-report-fire-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(234, 88, 12, 0.24);
        }

        .portal-report-fire-btn.is-loading {
            opacity: 0.72;
            pointer-events: none;
        }

        .portal-call-bfp-btn {
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid rgba(20, 93, 104, 0.14);
            border-radius: 999px;
            background: linear-gradient(135deg, #145d68 0%, #0f766e 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 12px 24px rgba(20, 93, 104, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .portal-call-bfp-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(20, 93, 104, 0.24);
        }

        .portal-call-bfp-btn.is-loading,
        .portal-call-bfp-btn.is-active-call {
            opacity: 0.75;
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

        .portal-shell {
            max-width: 1480px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .barangay-mobile-dock {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            padding: 10px 12px calc(10px + var(--barangay-safe-bottom));
            pointer-events: none;
        }

        .barangay-mobile-dock__shell {
            display: flex;
            justify-content: center;
        }

        .barangay-mobile-dock__track {
            position: relative;
            width: min(100%, 344px);
            min-height: 72px;
            padding: 10px 16px;
            border-radius: 30px;
            background: linear-gradient(180deg, rgba(24, 78, 86, 0.98) 0%, rgba(20, 93, 104, 0.98) 100%);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            align-items: center;
            gap: 8px;
            pointer-events: auto;
        }

        .barangay-mobile-dock__track::before {
            content: '';
            position: absolute;
            inset: 1px;
            border-radius: 29px;
            background: linear-gradient(180deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
            pointer-events: none;
        }

        .barangay-mobile-dock__item {
            position: relative;
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, 0.82);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .barangay-mobile-dock__item {
            width: 100%;
            min-height: 50px;
            flex-direction: column;
            border-radius: 18px;
        }

        .barangay-mobile-dock__label {
            display: block;
            max-width: 54px;
            margin-top: 2px;
            overflow: hidden;
            color: currentColor;
            font-size: 8px;
            font-weight: 750;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .barangay-mobile-dock__item.active {
            color: #ffffff;
        }

        .barangay-mobile-dock__item.active:not(.barangay-mobile-dock__item--center)::before {
            content: '';
            position: absolute;
            inset: 6px 12px;
            border-radius: 999px;
            background: rgba(52, 211, 153, 0.16);
            box-shadow: inset 0 0 0 1px rgba(110, 231, 183, 0.12);
            z-index: -1;
        }

        .barangay-mobile-dock__icon {
            font-size: 1.45rem;
            font-variation-settings: "FILL" 1, "wght" 500, "GRAD" 0, "opsz" 24;
        }

        .barangay-mobile-dock__center-wrap {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .barangay-mobile-dock__item--center {
            width: 74px;
            height: 74px;
            margin-top: -28px;
            border-radius: 999px;
            color: #ffffff;
            background: linear-gradient(180deg, #f97316 0%, #dc2626 100%);
            box-shadow:
                0 16px 34px rgba(220, 38, 38, 0.38),
                0 0 0 6px rgba(255, 255, 255, 0.16),
                0 0 0 12px rgba(255, 255, 255, 0.08);
        }

        .barangay-mobile-dock__item--center.is-loading {
            opacity: 0.72;
            pointer-events: none;
        }

        .barangay-mobile-dock__item--center::before,
        .barangay-mobile-dock__item--center::after {
            content: '';
            position: absolute;
            inset: 7px;
            border-radius: 999px;
        }

        .barangay-mobile-dock__item--center::before {
            border: 2px solid rgba(255, 255, 255, 0.92);
        }

        .barangay-mobile-dock__item--center::after {
            border: 1px solid rgba(255, 255, 255, 0.28);
        }

        .barangay-mobile-bottom-spacer {
            display: none;
            height: calc(var(--barangay-mobile-bottom-h) + 22px);
            flex-shrink: 0;
        }

        .hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(260px, 0.95fr);
            gap: 20px;
            padding: 28px 30px;
            border-radius: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 44%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .hero-card h1 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.75rem);
            line-height: 1;
            color: #0f172a;
            font-weight: 900;
        }

        .hero-card p {
            margin: 12px 0 0;
            color: #64748b;
            line-height: 1.65;
            font-size: 0.98rem;
            max-width: 64ch;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .hero-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 800;
            border: 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .hero-action-btn--primary {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(234, 88, 12, 0.18);
        }

        .hero-action-btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(234, 88, 12, 0.24);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.78);
            color: #9a3412;
            border: 1px solid rgba(245, 158, 11, 0.14);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .barangay-quick-report-status {
            position: fixed;
            top: 104px;
            right: 28px;
            z-index: 80;
            min-width: 280px;
            max-width: min(420px, calc(100vw - 32px));
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            color: #0f172a;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.22s ease;
        }

        .barangay-quick-report-status.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .barangay-quick-report-status.is-success {
            border-color: rgba(34, 197, 94, 0.22);
            background: #f0fdf4;
            color: #166534;
        }

        .barangay-quick-report-status.is-error {
            border-color: rgba(239, 68, 68, 0.22);
            background: #fef2f2;
            color: #991b1b;
        }
        .barangay-quick-report-status.is-warning {
            border-color: rgba(245, 158, 11, .38);
            background: #fffbeb;
            color: #92400e;
        }

        .barangay-live-alert-toast {
            position: fixed;
            top: 104px;
            left: 50%;
            transform: translate(-50%, -14px);
            z-index: 90;
            width: min(460px, calc(100vw - 28px));
            padding: 16px 18px;
            border-radius: 20px;
            border: 1px solid rgba(239, 68, 68, 0.16);
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.16);
            opacity: 0;
            visibility: hidden;
            transition: all 0.22s ease;
        }

        .barangay-live-alert-toast.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, 0);
        }

        .barangay-live-alert-toast__row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .barangay-live-alert-toast__icon {
            width: 44px;
            height: 44px;
            border-radius: 15px;
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.24);
        }

        .barangay-live-alert-toast__copy strong {
            display: block;
            color: #7f1d1d;
            font-size: 0.92rem;
            line-height: 1.25;
        }

        .barangay-live-alert-toast__copy p {
            margin: 4px 0 0;
            color: #9f1239;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .metric-grid,
        .panel-grid {
            display: grid;
            gap: 18px;
        }

        .metric-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .panel-grid.two {
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
        }

        .panel-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .metric-card,
        .panel-card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .metric-card {
            padding: 18px 18px 16px;
        }

        .panel-card {
            padding: 22px 24px;
        }

        .metric-label,
        .panel-eyebrow {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .metric-value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 1.6rem;
            font-weight: 900;
        }

        .panel-title {
            margin: 8px 0 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .panel-copy {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .list-stack {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .list-item {
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            padding: 16px;
        }

        .list-item h4 {
            margin: 0;
            color: #0f172a;
            font-size: 0.98rem;
            font-weight: 800;
        }

        .list-item p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .tag.critical,
        .tag.high { background: #fff1f2; color: #be123c; }
        .tag.medium { background: #fff7ed; color: #c2410c; }
        .tag.low { background: #ecfdf5; color: #047857; }
        .tag.pending { background: #fff7ed; color: #b45309; }
        .tag.in_progress { background: #eff6ff; color: #1d4ed8; }
        .tag.complied { background: #ecfdf5; color: #047857; }
        .tag.not_complied { background: #fef2f2; color: #b91c1c; }
        .tag.deferred { background: #f8fafc; color: #475569; }

        .empty-state {
            margin-top: 18px;
            border: 1px dashed rgba(226, 232, 240, 1);
            border-radius: 22px;
            padding: 26px 18px;
            text-align: center;
            color: #94a3b8;
            background: #f8fafc;
            font-size: 0.9rem;
        }

        .two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .stat-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-top: 1px solid rgba(241, 245, 249, 1);
        }

        .stat-line:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .stat-line strong {
            color: #0f172a;
            font-weight: 800;
        }

        .map-card {
            min-height: 220px;
            padding: 20px;
            border-radius: 24px;
            background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 38%), linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
        }

        .tiny-label {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .table-wrap {
            margin-top: 18px;
            overflow-x: auto;
        }

        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrap th,
        .table-wrap td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
            font-size: 0.88rem;
        }

        .table-wrap th {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 1280px) {
            .hero-card,
            .panel-grid.two,
            .panel-grid.three,
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }

            .barangay-quick-report-status {
                top: 92px;
                right: 16px;
                left: 16px;
                min-width: 0;
                max-width: none;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                min-height: 100dvh;
                padding: max(6px, var(--barangay-safe-top)) 12px calc(var(--barangay-mobile-bottom-h) + 18px);
            }

            .portal-top-bar {
                display: none;
            }

            .portal-shell {
                width: min(100%, 760px);
                gap: 14px;
            }

            .portal-shell > * {
                margin-top: 0 !important;
            }

            .two-col {
                grid-template-columns: 1fr;
            }

            .hero-card,
            .panel-card,
            .metric-card,
            .list-item,
            .map-card {
                border-radius: 24px;
                padding: 18px;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
            }

            .hero-card {
                padding: 20px 18px;
            }

            .hero-card h1 {
                font-size: clamp(2rem, 9vw, 2.75rem);
            }

            .hero-card p,
            .panel-copy,
            .list-item p {
                font-size: 0.9rem;
                line-height: 1.55;
            }

            .table-wrap {
                margin-inline: -4px;
                padding-inline: 4px;
            }

            .table-wrap table {
                min-width: 640px;
            }

            .barangay-mobile-bottom-spacer {
                display: block;
            }

            #barangayOperationsMap {
                height: min(54svh, 420px) !important;
            }

            #barangayRiskAreaMap,
            #hazardsRiskPickerMap {
                height: 260px !important;
            }
        }
    </style>
    @stack('styles')
    <style data-barangay-mobile-guardrails>
        /* Final shared guardrails for the compact Barangay/PWA shell. The scoped
           selectors also take precedence over inline page-specific styles. */
        .barangay-hero-toggle {
            display: none;
        }

        @media (max-width: 1024px) {
            :root {
                --barangay-mobile-bottom-h: calc(72px + var(--barangay-safe-bottom));
            }

            html,
            body.barangay-portal-shell {
                width: 100%;
                max-width: 100%;
                overflow-x: clip;
            }

            body.barangay-portal-shell #sidebar {
                display: none !important;
            }

            body.barangay-portal-shell .barangay-mobile-dock {
                display: block !important;
                padding: 6px 8px calc(6px + var(--barangay-safe-bottom));
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            body.barangay-portal-shell .barangay-mobile-bottom-spacer {
                display: none !important;
            }

            body.barangay-portal-shell .chief-mobile-panel.is-open {
                display: block !important;
            }

            body.barangay-portal-shell .portal-top-bar {
                display: none !important;
            }

            body.barangay-portal-shell .main-content {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                min-height: 100dvh;
                margin-left: 0 !important;
                padding: max(8px, var(--barangay-safe-top)) 10px calc(var(--barangay-mobile-bottom-h) + 18px) !important;
                overflow-x: clip;
            }

            body.barangay-portal-shell .portal-shell {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                margin-inline: 0 !important;
                gap: 12px;
            }

            body.barangay-portal-shell .portal-shell > * {
                min-width: 0 !important;
                max-width: 100%;
            }

            body.barangay-portal-shell .barangay-mobile-dock__track {
                width: min(100%, 330px);
                min-height: 60px;
                padding: 6px 10px;
                gap: 4px;
                border-radius: 23px;
            }

            body.barangay-portal-shell .barangay-mobile-dock__track::before {
                border-radius: 22px;
            }

            body.barangay-portal-shell .barangay-mobile-dock__item {
                min-width: 0;
                min-height: 44px;
                border-radius: 14px;
            }

            body.barangay-portal-shell .barangay-mobile-dock__icon {
                font-size: 1.25rem;
            }

            body.barangay-portal-shell .barangay-mobile-dock__label {
                max-width: 48px;
                font-size: 7px;
            }

            body.barangay-portal-shell .barangay-mobile-dock__item--center {
                width: 58px;
                height: 58px;
                min-height: 58px;
                margin-top: -18px;
                box-shadow: 0 12px 26px rgba(220, 38, 38, 0.34), 0 0 0 5px rgba(255, 255, 255, 0.14);
            }

            body.barangay-portal-shell .table-wrap,
            body.barangay-portal-shell .checklist-table-wrap,
            body.barangay-portal-shell #fireReportsTableWrap {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overscroll-behavior-inline: contain;
                -webkit-overflow-scrolling: touch;
            }

            body.barangay-portal-shell input,
            body.barangay-portal-shell select,
            body.barangay-portal-shell textarea,
            body.barangay-portal-shell button {
                max-width: 100%;
                box-sizing: border-box;
            }

            body.barangay-portal-shell .fire-report-modal,
            body.barangay-portal-shell .risk-modal-root,
            body.barangay-portal-shell .barangay-risk-modal,
            body.barangay-portal-shell .checklist-detail-modal,
            body.barangay-portal-shell .dashboard-record-modal {
                z-index: 12050 !important;
            }

            body.barangay-portal-shell:has(
                .fire-report-modal:not(.hidden),
                .risk-modal-root.is-open,
                .barangay-risk-modal:not(.hidden),
                .checklist-detail-modal:not([hidden]),
                .dashboard-record-modal:not([hidden])
            ) .barangay-mobile-dock {
                opacity: 0;
                pointer-events: none;
                transform: translateY(110%);
            }
        }

        @media (max-width: 1024px) {
            body.barangay-portal-shell {
                font-size: 14px;
            }

            body.barangay-portal-shell .hero-card,
            body.barangay-portal-shell .panel-card,
            body.barangay-portal-shell .metric-card,
            body.barangay-portal-shell .list-item,
            body.barangay-portal-shell .map-card {
                padding: 12px !important;
                border-radius: 16px !important;
            }

            body.barangay-portal-shell .hero-card {
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 12px !important;
            }

            body.barangay-portal-shell .hero-card h1 {
                font-size: clamp(1.35rem, 7vw, 1.8rem) !important;
                line-height: 1.08;
            }

            body.barangay-portal-shell .hero-card p,
            body.barangay-portal-shell .panel-copy,
            body.barangay-portal-shell .list-item p {
                font-size: 0.82rem !important;
                line-height: 1.45 !important;
            }

            body.barangay-portal-shell .panel-title {
                font-size: 1rem !important;
                line-height: 1.2;
            }

            body.barangay-portal-shell .panel-grid,
            body.barangay-portal-shell .two-col {
                gap: 10px !important;
            }

            body.barangay-portal-shell .empty-state {
                min-height: 0 !important;
                padding: 18px 12px !important;
                font-size: 0.76rem !important;
                line-height: 1.45;
            }

            body.barangay-portal-shell .two-col,
            body.barangay-portal-shell .panel-grid.two,
            body.barangay-portal-shell .panel-grid.three,
            body.barangay-portal-shell .fire-report-shell,
            body.barangay-portal-shell .fire-report-form__grid,
            body.barangay-portal-shell .fire-report-filterbar__grid,
            body.barangay-portal-shell .risk-form-grid,
            body.barangay-portal-shell form.list-stack > div[style*="grid-template-columns"] {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.barangay-portal-shell .metric-grid,
            body.barangay-portal-shell .risk-status-metrics,
            body.barangay-portal-shell .attention-grid,
            body.barangay-portal-shell .checklist-summary-grid,
            body.barangay-portal-shell .fire-report-summary-grid,
            body.barangay-portal-shell .barangay-risk-metric-grid,
            body.barangay-portal-shell .barangay-profile-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
            }

            body.barangay-portal-shell .barangay-dashboard-stack,
            body.barangay-portal-shell .barangay-risk-stack,
            body.barangay-portal-shell .checklist-center-stack,
            body.barangay-portal-shell .list-stack {
                gap: 10px !important;
            }

            body.barangay-portal-shell .hero-badges,
            body.barangay-portal-shell .tag-row,
            body.barangay-portal-shell .barangay-profile-cover__chips {
                display: flex;
                flex-wrap: nowrap;
                gap: 6px !important;
                max-width: 100%;
                padding-bottom: 2px;
                overflow-x: auto;
                scrollbar-width: none;
            }

            body.barangay-portal-shell .hero-badges::-webkit-scrollbar,
            body.barangay-portal-shell .tag-row::-webkit-scrollbar,
            body.barangay-portal-shell .barangay-profile-cover__chips::-webkit-scrollbar {
                display: none;
            }

            body.barangay-portal-shell .hero-badge,
            body.barangay-portal-shell .tag,
            body.barangay-portal-shell .barangay-profile-cover__chip,
            body.barangay-portal-shell .hazards-pill {
                flex: 0 0 auto;
                min-height: 24px !important;
                padding: 3px 8px !important;
                font-size: 0.62rem !important;
                line-height: 1.2;
            }

            body.barangay-portal-shell .panel-eyebrow,
            body.barangay-portal-shell .tiny-label,
            body.barangay-portal-shell .metric-label {
                font-size: 0.62rem !important;
                line-height: 1.25;
                letter-spacing: 0.06em !important;
            }

            body.barangay-portal-shell .metric-card,
            body.barangay-portal-shell .checklist-summary-card,
            body.barangay-portal-shell .fire-report-summary-card,
            body.barangay-portal-shell .barangay-profile-stat,
            body.barangay-portal-shell .risk-status-metric,
            body.barangay-portal-shell .attention-item {
                min-width: 0 !important;
                min-height: 66px !important;
                padding: 10px !important;
                border-radius: 13px !important;
            }

            body.barangay-portal-shell .metric-card-icon,
            body.barangay-portal-shell .checklist-summary-icon {
                width: 32px !important;
                height: 32px !important;
                flex-basis: 32px !important;
                border-radius: 10px !important;
                font-size: 0.82rem !important;
            }

            body.barangay-portal-shell .metric-card-number,
            body.barangay-portal-shell .metric-value,
            body.barangay-portal-shell .checklist-summary-value,
            body.barangay-portal-shell .barangay-profile-stat__value,
            body.barangay-portal-shell .risk-status-metric strong,
            body.barangay-portal-shell .attention-value {
                font-size: 1.12rem !important;
                line-height: 1.05 !important;
            }

            body.barangay-portal-shell .metric-card-description,
            body.barangay-portal-shell .checklist-summary-label,
            body.barangay-portal-shell .barangay-profile-stat__label,
            body.barangay-portal-shell .risk-status-label {
                font-size: 0.62rem !important;
                line-height: 1.3 !important;
            }

            body.barangay-portal-shell .hero-btn,
            body.barangay-portal-shell .hero-action-btn,
            body.barangay-portal-shell .portal-report-fire-btn,
            body.barangay-portal-shell .barangay-risk-launch__button,
            body.barangay-portal-shell .fire-report-primary-btn,
            body.barangay-portal-shell .fire-report-secondary-btn,
            body.barangay-portal-shell .fire-report-danger-btn,
            body.barangay-portal-shell .fire-report-location-btn,
            body.barangay-portal-shell .risk-form-submit {
                min-height: 38px !important;
                padding: 0 11px !important;
                border-radius: 10px !important;
                font-size: 0.72rem !important;
                line-height: 1.2;
            }

            body.barangay-portal-shell .list-item {
                gap: 8px !important;
            }

            body.barangay-portal-shell .table-wrap {
                margin-inline: 0 !important;
                padding-inline: 0 !important;
                border-radius: 12px;
            }

            body.barangay-portal-shell .table-wrap table,
            body.barangay-portal-shell .hazard-table,
            body.barangay-portal-shell .hazards-table,
            body.barangay-portal-shell .fire-report-table,
            body.barangay-portal-shell .checklist-table {
                min-width: 600px !important;
            }

            body.barangay-portal-shell table th,
            body.barangay-portal-shell table td {
                padding: 8px 9px !important;
                font-size: 0.7rem !important;
                line-height: 1.35;
            }

            body.barangay-portal-shell .table-wrap:has(.mobile-card-table),
            body.barangay-portal-shell .checklist-table-wrap:has(.mobile-card-table) {
                overflow: visible !important;
            }

            body.barangay-portal-shell .table-wrap table.mobile-card-table,
            body.barangay-portal-shell .checklist-table-wrap table.mobile-card-table {
                display: block !important;
                width: 100% !important;
                min-width: 0 !important;
                border: 0 !important;
                background: transparent !important;
            }

            body.barangay-portal-shell .mobile-card-table thead {
                display: none !important;
            }

            body.barangay-portal-shell .mobile-card-table tbody {
                display: grid !important;
                width: 100%;
                gap: 8px;
            }

            body.barangay-portal-shell .mobile-card-table tbody tr {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                min-width: 0;
                padding: 8px;
                overflow: hidden;
                border: 1px solid rgba(226, 232, 240, 0.92);
                border-radius: 13px;
                background: #fff;
                box-shadow: 0 5px 14px rgba(15, 23, 42, 0.04);
            }

            body.barangay-portal-shell .mobile-card-table tbody tr[hidden],
            body.barangay-portal-shell .mobile-card-table tbody tr[style*="display: none"] {
                display: none !important;
            }

            body.barangay-portal-shell .mobile-card-table tbody td {
                display: block !important;
                min-width: 0;
                padding: 6px 7px !important;
                overflow-wrap: anywhere;
                white-space: normal !important;
                border: 0 !important;
                background: transparent !important;
            }

            body.barangay-portal-shell .mobile-card-table tbody td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 3px;
                color: #94a3b8;
                font-size: 0.56rem;
                font-weight: 900;
                line-height: 1.2;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            body.barangay-portal-shell .mobile-card-table tbody td[data-mobile-wide],
            body.barangay-portal-shell .mobile-card-table tbody td[data-mobile-actions],
            body.barangay-portal-shell .mobile-card-table .risk-table-empty-row td {
                grid-column: 1 / -1;
            }

            body.barangay-portal-shell .mobile-card-table tbody td[data-mobile-actions] {
                margin-top: 2px;
                padding-top: 8px !important;
                border-top: 1px solid #f1f5f9 !important;
            }

            body.barangay-portal-shell .mobile-card-table tbody td[data-mobile-actions]::before {
                display: none;
            }

            body.barangay-portal-shell .mobile-card-table .risk-table-empty-row td::before {
                display: none;
            }

            body.barangay-portal-shell .mobile-card-table .fire-report-actions,
            body.barangay-portal-shell .mobile-card-table .hazards-actions {
                justify-content: flex-end;
            }

            body.barangay-portal-shell .mobile-card-table .checklist-view-button {
                width: 100% !important;
                min-height: 34px;
            }

            body.barangay-portal-shell .mobile-card-table td > small {
                max-width: 100% !important;
                white-space: normal !important;
            }

            body.barangay-portal-shell form label,
            body.barangay-portal-shell .fire-report-form__field,
            body.barangay-portal-shell .risk-form-field {
                min-width: 0;
                font-size: 0.72rem !important;
            }

            body.barangay-portal-shell input:not([type="checkbox"]):not([type="radio"]),
            body.barangay-portal-shell select,
            body.barangay-portal-shell textarea {
                width: 100%;
                min-width: 0;
                min-height: 40px;
                padding: 9px 10px !important;
                border-radius: 10px !important;
                font-size: 16px !important;
            }

            body.barangay-portal-shell textarea {
                min-height: 76px;
            }

            body.barangay-portal-shell form button[type="submit"] {
                scroll-margin-bottom: calc(var(--barangay-mobile-bottom-h) + 18px);
            }

            body.barangay-portal-shell .hero-card {
                position: relative;
            }

            body.barangay-portal-shell .barangay-hero-toggle {
                position: absolute;
                top: 9px;
                right: 9px;
                z-index: 2;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                padding: 0;
                border: 1px solid rgba(245, 158, 11, 0.2);
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.86);
                color: #b45309;
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
            }

            body.barangay-portal-shell .hero-card > div {
                min-width: 0;
                padding-right: 34px;
            }

            body.barangay-portal-shell .hero-card.is-collapsed {
                padding-block: 10px !important;
            }

            body.barangay-portal-shell .hero-card.is-collapsed h1 {
                max-width: calc(100% - 6px);
                margin: 0 !important;
                font-size: 1.05rem !important;
                line-height: 1.2 !important;
            }

            body.barangay-portal-shell .hero-card.is-collapsed p,
            body.barangay-portal-shell .hero-card.is-collapsed .hero-badges {
                display: none !important;
            }

            body.barangay-portal-shell .hero-card.is-collapsed .hero-actions {
                margin-top: 8px !important;
            }

            /* Dashboard */
            body.barangay-portal-shell .risk-status-card,
            body.barangay-portal-shell .decision-card,
            body.barangay-portal-shell .compact-profile-card,
            body.barangay-portal-shell .preparedness-card,
            body.barangay-portal-shell .attention-card,
            body.barangay-portal-shell .hazard-table-card,
            body.barangay-portal-shell .map-monitoring-card {
                padding: 12px !important;
                border-radius: 16px !important;
            }

            body.barangay-portal-shell .risk-status-card h2,
            body.barangay-portal-shell .preparedness-card h3,
            body.barangay-portal-shell .barangay-section-heading h2 {
                font-size: 1.05rem !important;
                line-height: 1.2 !important;
            }

            body.barangay-portal-shell .preparedness-score {
                font-size: 2rem !important;
            }

            body.barangay-portal-shell .compact-profile-grid {
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 7px !important;
            }

            body.barangay-portal-shell .compact-profile-item,
            body.barangay-portal-shell .next-action-item,
            body.barangay-portal-shell .factor-item {
                padding: 9px 10px !important;
                border-radius: 11px !important;
            }

            body.barangay-portal-shell .map-monitoring-toolbar,
            body.barangay-portal-shell .map-card-header,
            body.barangay-portal-shell .barangay-section-heading {
                gap: 8px !important;
            }

            body.barangay-portal-shell .map-filter-group {
                display: flex;
                width: 100%;
                max-width: 100%;
                padding-bottom: 2px;
                overflow-x: auto;
            }

            body.barangay-portal-shell .map-filter-btn {
                flex: 0 0 auto;
                min-height: 32px !important;
                padding: 0 9px !important;
                font-size: 0.67rem !important;
            }

            /* Risk map and risk records */
            body.barangay-portal-shell .barangay-risk-map-shell,
            body.barangay-portal-shell .barangay-risk-feed-card,
            body.barangay-portal-shell .barangay-risk-launch,
            body.barangay-portal-shell .barangay-risk-review,
            body.barangay-portal-shell .barangay-risk-map-panel {
                padding: 11px !important;
                border-radius: 15px !important;
            }

            body.barangay-portal-shell .barangay-risk-record-head,
            body.barangay-portal-shell .barangay-risk-feed-card__header,
            body.barangay-portal-shell .barangay-risk-section-heading {
                gap: 7px !important;
            }

            body.barangay-portal-shell .barangay-risk-record-action {
                min-width: 34px !important;
                min-height: 32px !important;
                padding: 0 9px !important;
                font-size: 0.68rem !important;
            }

            body.barangay-portal-shell #barangayOperationsMap {
                height: clamp(240px, 46svh, 350px) !important;
                border-radius: 13px !important;
            }

            body.barangay-portal-shell #barangayRiskAreaMap,
            body.barangay-portal-shell #hazardsRiskPickerMap {
                height: clamp(220px, 40svh, 300px) !important;
                border-radius: 12px !important;
            }

            body.barangay-portal-shell .leaflet-control-layers-expanded {
                max-width: calc(100vw - 56px) !important;
                max-height: 45svh !important;
                padding: 8px !important;
                font-size: 0.7rem !important;
            }

            /* Fire reports */
            body.barangay-portal-shell .fire-report-shell__main,
            body.barangay-portal-shell .fire-report-side-card,
            body.barangay-portal-shell .fire-report-filterbar,
            body.barangay-portal-shell .fire-report-form__section,
            body.barangay-portal-shell .fire-report-location-panel {
                padding: 10px !important;
                border-radius: 14px !important;
            }

            body.barangay-portal-shell .fire-report-filterbar__actions,
            body.barangay-portal-shell .fire-report-pagination {
                gap: 7px !important;
                margin-top: 9px !important;
            }

            /* Hazard and risk reporting */
            body.barangay-portal-shell .hazards-command,
            body.barangay-portal-shell .hazards-command__aside,
            body.barangay-portal-shell .risk-form-card,
            body.barangay-portal-shell .risk-review-card,
            body.barangay-portal-shell .risk-mode-section {
                padding: 11px !important;
                border-radius: 15px !important;
            }

            body.barangay-portal-shell .hazards-command__copy {
                gap: 8px !important;
            }

            body.barangay-portal-shell .hazards-command__copy h3 {
                font-size: 1.2rem !important;
                line-height: 1.15 !important;
            }

            body.barangay-portal-shell .hazards-command__copy p,
            body.barangay-portal-shell .hazards-command__aside p,
            body.barangay-portal-shell .risk-modal-copy {
                font-size: 0.76rem !important;
                line-height: 1.45 !important;
            }

            body.barangay-portal-shell .hazards-inline-tags {
                flex-wrap: nowrap;
                gap: 6px !important;
                max-width: 100%;
                overflow-x: auto;
            }

            body.barangay-portal-shell .hazards-inline-tag {
                flex: 0 0 auto;
                min-height: 28px !important;
                padding: 0 9px !important;
                font-size: 0.66rem !important;
            }

            body.barangay-portal-shell .risk-table-toolbar,
            body.barangay-portal-shell .risk-table-filter-group,
            body.barangay-portal-shell .hazards-actions {
                gap: 7px !important;
            }

            body.barangay-portal-shell .risk-table-filter-group {
                display: flex;
                flex-wrap: nowrap;
                max-width: 100%;
                overflow-x: auto;
            }

            body.barangay-portal-shell .risk-table-filter {
                flex: 0 0 auto;
                min-height: 32px !important;
                padding: 0 9px !important;
                font-size: 0.67rem !important;
            }

            body.barangay-portal-shell .risk-photo-thumb,
            body.barangay-portal-shell .hazards-photo-strip .risk-photo-thumb {
                width: 48px !important;
                min-width: 48px !important;
                border-radius: 10px !important;
            }

            body.barangay-portal-shell .risk-modal-panel {
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100dvh - max(8px, var(--barangay-safe-top))) !important;
                margin: max(8px, var(--barangay-safe-top)) 0 0 !important;
                border-radius: 20px 20px 0 0 !important;
            }

            body.barangay-portal-shell .risk-modal-header,
            body.barangay-portal-shell .risk-modal-body {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            body.barangay-portal-shell .risk-modal-title {
                font-size: 1.08rem !important;
                line-height: 1.2 !important;
            }

            body.barangay-portal-shell .risk-modal-close {
                width: 34px !important;
                height: 34px !important;
                flex: 0 0 34px;
            }

            /* Checklist center */
            body.barangay-portal-shell .checklist-panel-card {
                padding: 11px !important;
                border-radius: 15px !important;
            }

            body.barangay-portal-shell .checklist-panel-heading {
                gap: 7px !important;
                margin-bottom: 8px !important;
            }

            body.barangay-portal-shell .checklist-step-controls,
            body.barangay-portal-shell .barangay-signoff-form,
            body.barangay-portal-shell .barangay-record-update-form {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            /* Profile */
            body.barangay-portal-shell .barangay-profile-cover,
            body.barangay-portal-shell .barangay-profile-pane {
                border-radius: 16px !important;
            }

            body.barangay-portal-shell .barangay-profile-cover__banner {
                min-height: 92px !important;
            }

            body.barangay-portal-shell .barangay-profile-cover__body,
            body.barangay-portal-shell .barangay-profile-pane,
            body.barangay-portal-shell .barangay-user-row {
                padding: 11px !important;
            }

            body.barangay-portal-shell .barangay-profile-cover__content {
                padding: 12px 0 3px !important;
                gap: 10px !important;
            }

            body.barangay-portal-shell .barangay-profile-cover__title {
                font-size: clamp(1.35rem, 6vw, 1.8rem) !important;
                line-height: 1.08 !important;
            }

            body.barangay-portal-shell .barangay-profile-cover__copy,
            body.barangay-portal-shell .barangay-user-row__meta {
                font-size: 0.76rem !important;
                line-height: 1.45 !important;
            }

            body.barangay-portal-shell .barangay-profile-pane__grid,
            body.barangay-portal-shell .barangay-user-list {
                gap: 8px !important;
            }

            body.barangay-portal-shell .barangay-profile-pane__item {
                padding: 9px 10px !important;
            }

            /* Shared mobile/PWA menu */
            body.barangay-portal-shell .chief-mobile-panel__sheet {
                padding: calc(12px + var(--barangay-safe-top)) 12px calc(16px + var(--barangay-safe-bottom)) !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__header {
                gap: 8px !important;
                margin-bottom: 10px !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__title {
                font-size: 1.15rem !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__eyebrow {
                font-size: 0.6rem !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__action {
                min-height: 50px !important;
                padding: 9px 10px !important;
                border-radius: 12px !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__action-icon {
                width: 34px !important;
                height: 34px !important;
                flex: 0 0 34px !important;
                border-radius: 10px !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__action-copy strong {
                font-size: 0.78rem !important;
            }

            body.barangay-portal-shell .chief-mobile-panel__action-copy small {
                font-size: 0.64rem !important;
                line-height: 1.3 !important;
            }

            body.barangay-portal-shell .fire-report-modal {
                align-items: flex-end !important;
                padding: 0 !important;
            }

            body.barangay-portal-shell .fire-report-modal__dialog {
                display: flex !important;
                flex-direction: column;
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100dvh - max(8px, var(--barangay-safe-top))) !important;
                margin: 0 !important;
                padding: 12px 12px 0 !important;
                overflow-y: auto !important;
                overscroll-behavior: contain;
                border-radius: 20px 20px 0 0 !important;
            }

            body.barangay-portal-shell .fire-report-modal__header {
                flex: 0 0 auto;
                margin-bottom: 10px !important;
            }

            body.barangay-portal-shell .fire-report-modal__actions {
                position: sticky !important;
                bottom: 0;
                z-index: 3;
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
                margin: 10px -12px 0 !important;
                padding: 10px 12px calc(10px + var(--barangay-safe-bottom)) !important;
                background: rgba(255, 253, 249, 0.98);
                border-top: 1px solid rgba(226, 232, 240, 0.9);
                box-shadow: 0 -10px 24px rgba(15, 23, 42, 0.06);
            }

            body.barangay-portal-shell .fire-report-modal__actions > button,
            body.barangay-portal-shell .fire-report-modal__actions > a {
                width: 100% !important;
                min-height: 42px;
                justify-content: center;
            }

            body.barangay-portal-shell .risk-form-actions,
            body.barangay-portal-shell .barangay-risk-wizard__actions {
                position: sticky !important;
                bottom: 0 !important;
                z-index: 4;
                margin-bottom: 0 !important;
                padding-bottom: calc(10px + var(--barangay-safe-bottom)) !important;
                background: rgba(255, 255, 255, 0.98);
            }

            body.barangay-portal-shell .checklist-detail-modal,
            body.barangay-portal-shell .dashboard-record-modal {
                align-items: flex-end !important;
                padding: 0 !important;
            }

            body.barangay-portal-shell .checklist-detail-modal__card,
            body.barangay-portal-shell .dashboard-record-modal__card {
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100dvh - max(8px, var(--barangay-safe-top))) !important;
                border-radius: 20px 20px 0 0 !important;
            }

            body.barangay-portal-shell .checklist-detail-modal__footer,
            body.barangay-portal-shell .dashboard-record-modal__footer {
                padding-bottom: calc(12px + var(--barangay-safe-bottom)) !important;
            }
        }

        @media (max-width: 520px) {
            body.barangay-portal-shell .main-content {
                padding-inline: 8px !important;
            }

            body.barangay-portal-shell .hero-card,
            body.barangay-portal-shell .panel-card,
            body.barangay-portal-shell .map-card,
            body.barangay-portal-shell .risk-status-card,
            body.barangay-portal-shell .decision-card,
            body.barangay-portal-shell .compact-profile-card,
            body.barangay-portal-shell .preparedness-card,
            body.barangay-portal-shell .attention-card,
            body.barangay-portal-shell .hazard-table-card,
            body.barangay-portal-shell .map-monitoring-card {
                padding: 10px !important;
                border-radius: 14px !important;
            }

            body.barangay-portal-shell .hero-card h1 {
                font-size: 1.3rem !important;
            }

            body.barangay-portal-shell .hero-card p {
                display: -webkit-box;
                overflow: hidden;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 3;
            }

            body.barangay-portal-shell .metric-grid,
            body.barangay-portal-shell .risk-status-metrics,
            body.barangay-portal-shell .attention-grid,
            body.barangay-portal-shell .checklist-summary-grid,
            body.barangay-portal-shell .fire-report-summary-grid,
            body.barangay-portal-shell .barangay-risk-metric-grid,
            body.barangay-portal-shell .barangay-profile-stats {
                gap: 6px !important;
            }

            body.barangay-portal-shell .metric-card,
            body.barangay-portal-shell .checklist-summary-card,
            body.barangay-portal-shell .fire-report-summary-card,
            body.barangay-portal-shell .barangay-profile-stat,
            body.barangay-portal-shell .risk-status-metric,
            body.barangay-portal-shell .attention-item {
                min-height: 60px !important;
                padding: 8px !important;
            }

            body.barangay-portal-shell .table-wrap table,
            body.barangay-portal-shell .hazard-table,
            body.barangay-portal-shell .hazards-table,
            body.barangay-portal-shell .fire-report-table,
            body.barangay-portal-shell .checklist-table {
                min-width: 560px !important;
            }

            body.barangay-portal-shell .dashboard-record-detail-grid,
            body.barangay-portal-shell .fire-report-detail-grid,
            body.barangay-portal-shell .checklist-detail-grid,
            body.barangay-portal-shell .barangay-risk-detail-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.barangay-portal-shell .fire-report-pagination,
            body.barangay-portal-shell .dashboard-record-pagination {
                align-items: flex-start !important;
                flex-direction: column !important;
            }
        }

        @media (max-width: 380px) {
            body.barangay-portal-shell .main-content {
                padding-inline: 7px !important;
            }

            body.barangay-portal-shell .barangay-mobile-dock__track {
                padding-inline: 6px;
            }

            body.barangay-portal-shell .barangay-mobile-dock__item--center {
                width: 54px;
                height: 54px;
                min-height: 54px;
            }
        }
    </style>
</head>
<body class="barangay-portal-shell">
    @include('partials.unified-notifications')
    @php
        $initialBarangayNotifications = auth()->check()
            ? auth()->user()->notifications()->latest()->limit(8)->get()
            : collect();
        $initialBarangayUnreadCount = auth()->check()
            ? auth()->user()->unreadNotifications()->count()
            : 0;
    @endphp

    @include('barangay.partials.sidebar', ['active' => trim($__env->yieldContent('active_nav'))])

    <main class="main-content">
        <nav class="portal-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden md:block">
                    <h1 class="text-xl font-bold text-slate-800">@yield('page_title', 'Barangay Portal')</h1>
                    <p class="text-xs text-slate-500 font-medium">@yield('page_subtitle', 'Community-level situational view and risk coordination tools')</p>
                </div>
            </div>

            <div class="hidden md:flex portal-top-actions">
                <button type="button" class="portal-call-bfp-btn" data-pyro-voice-call aria-label="Call the assigned BFP station">
                    <i class="fa-solid fa-phone-volume" aria-hidden="true"></i>
                    <span>Call BFP</span>
                </button>

                @hasSection('top_bar_actions')
                    @yield('top_bar_actions')
                @else
                    <a href="{{ route('barangay.reports') }}" class="portal-report-fire-btn" data-barangay-quick-report data-report-url="{{ route('barangay.reports.quick') }}">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                        <span>1-Tap Fire Report</span>
                    </a>
                @endif

                <div
                    class="portal-notification-shell"
                    data-barangay-notifications
                    data-fetch-url="{{ route('barangay.notifications.index') }}"
                    data-read-url-template="{{ route('barangay.notifications.read', ['notificationId' => '__ID__']) }}"
                    data-read-all-url="{{ route('barangay.notifications.read-all') }}"
                >
                    <button type="button" class="portal-notification-btn" data-barangay-notification-toggle aria-label="Open barangay notifications">
                        <i class="bi bi-bell-fill"></i>
                        <span class="portal-notification-badge {{ $initialBarangayUnreadCount > 0 ? '' : 'is-hidden' }}" data-barangay-notification-badge>
                            {{ $initialBarangayUnreadCount > 99 ? '99+' : $initialBarangayUnreadCount }}
                        </span>
                    </button>

                    <div class="portal-notification-panel" data-barangay-notification-panel>
                        <div class="portal-notification-panel-head">
                            <div>
                                <strong>Barangay Notifications</strong>
                                <span>Fires nearby, checklist updates, and hazard review activity</span>
                            </div>
                            <button type="button" class="portal-notification-link-btn" data-barangay-notification-read-all>
                                Mark all read
                            </button>
                        </div>
                        <div class="portal-notification-list" data-barangay-notification-list>
                            @forelse ($initialBarangayNotifications as $notification)
                                @php
                                    $notificationData = is_array($notification->data) ? $notification->data : [];
                                @endphp
                                <button
                                    type="button"
                                    class="portal-notification-item {{ $notification->read_at ? '' : 'is-unread' }}"
                                    data-barangay-notification-item
                                    data-notification-id="{{ $notification->id }}"
                                    data-notification-url="{{ $notificationData['url'] ?? '' }}"
                                >
                                    <span class="portal-notification-icon">
                                        <i class="{{ $notificationData['icon'] ?? 'bi bi-bell-fill' }}"></i>
                                    </span>
                                    <span class="portal-notification-copy">
                                        <strong>{{ $notificationData['title'] ?? 'Barangay notification' }}</strong>
                                        <p>{{ $notificationData['message'] ?? 'A new barangay event is available.' }}</p>
                                        <span class="portal-notification-meta">
                                            <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                            <span>{{ strtoupper(str_replace('-', ' ', $notificationData['category'] ?? 'general')) }}</span>
                                        </span>
                                    </span>
                                </button>
                            @empty
                                <div class="portal-notification-empty" data-barangay-notification-empty>
                                    <strong>No notifications yet</strong>
                                    <p>Barangay alerts will appear here as soon as the system records them.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ strtoupper(substr(auth()->user()->first_name ?? 'B', 0, 1) . substr(auth()->user()->last_name ?? 'R', 0, 1)) }}
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
                            <a href="{{ route('barangay.profile') }}" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>Barangay Profile</span>
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

        <div class="portal-shell">
            <div id="barangayQuickReportStatus" class="barangay-quick-report-status" role="status" aria-live="polite"></div>
            <div id="barangayLiveAlertToast" class="barangay-live-alert-toast" role="alert" aria-live="assertive"></div>

            @if (! trim($__env->yieldContent('hide_hero')))
                <section class="hero-card" data-barangay-hero>
                    <button
                        type="button"
                        class="barangay-hero-toggle"
                        data-barangay-hero-toggle
                        aria-expanded="true"
                        aria-label="Collapse page introduction"
                        title="Collapse page introduction"
                    >
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h1>@yield('hero_title', 'Barangay safety operations in one place.')</h1>
                        <p>@yield('hero_copy', 'Monitor community hazards, review risk zones, and keep preparedness work aligned with the latest barangay-level records.')</p>
                        <div class="hero-badges">
                            @yield('hero_badges')
                        </div>
                        @hasSection('hero_actions')
                            <div class="hero-actions">
                                @yield('hero_actions')
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const quickReportStatus = document.getElementById('barangayQuickReportStatus');
            const quickReportButtons = document.querySelectorAll('[data-barangay-quick-report]');
            const mobileHero = document.querySelector('[data-barangay-hero]');
            const mobileHeroToggle = mobileHero?.querySelector('[data-barangay-hero-toggle]');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            const barangayLiveAlertToast = document.getElementById('barangayLiveAlertToast');
            const notificationShell = document.querySelector('[data-barangay-notifications]');
            const notificationToggle = notificationShell?.querySelector('[data-barangay-notification-toggle]');
            const notificationPanel = notificationShell?.querySelector('[data-barangay-notification-panel]');
            const notificationList = notificationShell?.querySelector('[data-barangay-notification-list]');
            const notificationBadge = notificationShell?.querySelector('[data-barangay-notification-badge]');
            const readAllNotificationsBtn = notificationShell?.querySelector('[data-barangay-notification-read-all]');
            let quickReportTimer = null;
            let notificationRefreshTimer = null;
            let barangayLiveAlertTimer = null;
            let barangayIncidentAudioContext = null;

            if (mobileHero && mobileHeroToggle) {
                const storageKey = 'pyrosense:barangay:hero-collapsed';
                let savedPreference = null;
                try {
                    savedPreference = window.localStorage.getItem(storageKey);
                } catch (error) {
                    savedPreference = null;
                }
                const setHeroCollapsed = (collapsed) => {
                    mobileHero.classList.toggle('is-collapsed', collapsed);
                    mobileHeroToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    mobileHeroToggle.setAttribute('aria-label', collapsed ? 'Expand page introduction' : 'Collapse page introduction');
                    mobileHeroToggle.setAttribute('title', collapsed ? 'Expand page introduction' : 'Collapse page introduction');
                    const icon = mobileHeroToggle.querySelector('i');
                    icon?.classList.toggle('fa-chevron-down', collapsed);
                    icon?.classList.toggle('fa-chevron-up', ! collapsed);
                };

                setHeroCollapsed(savedPreference === null
                    ? window.matchMedia('(max-width: 520px)').matches
                    : savedPreference === '1');

                mobileHeroToggle.addEventListener('click', () => {
                    const collapsed = ! mobileHero.classList.contains('is-collapsed');
                    setHeroCollapsed(collapsed);
                    try {
                        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
                    } catch (error) {
                        // The preference is optional when storage is unavailable.
                    }
                });
            }

            openBtn?.addEventListener('click', function () {
                sidebar?.classList.toggle('open');
            });

            window.PyroNotify?.info(@json(($barangay?->barangay_name ?? 'Barangay Desk').' is ready.'), {
                title: @json(trim($__env->yieldContent('page_title')) ?: 'Barangay Portal'),
                duration: 2800,
            });

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    notificationPanel?.classList.remove('show');
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });

                userDropdownMenu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }

            if (notificationToggle && notificationPanel && notificationShell) {
                notificationToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    userDropdownMenu?.classList.remove('show');
                    notificationPanel.classList.toggle('show');
                });

                notificationPanel.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

                document.addEventListener('click', function (event) {
                    if (!notificationShell.contains(event.target)) {
                        notificationPanel.classList.remove('show');
                    }
                });
            }

            readAllNotificationsBtn?.addEventListener('click', async function () {
                const url = notificationShell?.getAttribute('data-read-all-url');
                if (!url) {
                    return;
                }

                try {
                    await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                    });

                    await refreshBarangayNotifications();
                } catch (error) {
                    console.warn('Unable to mark notifications as read:', error);
                }
            });

            quickReportButtons.forEach((button) => {
                button.addEventListener('click', async function (event) {
                    event.preventDefault();
                    await submitQuickFireReport(button);
                });
            });

            notificationList?.addEventListener('click', async function (event) {
                const item = event.target.closest('[data-barangay-notification-item]');
                if (!item || !notificationShell) {
                    return;
                }

                const template = notificationShell.getAttribute('data-read-url-template') || '';
                const notificationId = item.getAttribute('data-notification-id') || '';
                const redirectUrl = item.getAttribute('data-notification-url') || '';
                const readUrl = template.replace('__ID__', notificationId);

                try {
                    if (notificationId && readUrl) {
                        await fetch(readUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        });
                    }
                } catch (error) {
                    console.warn('Unable to mark notification as read:', error);
                }

                await refreshBarangayNotifications();

                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            });

            async function refreshBarangayNotifications() {
                const fetchUrl = notificationShell?.getAttribute('data-fetch-url');
                if (!fetchUrl || !notificationList) {
                    return;
                }

                try {
                    const response = await fetch(fetchUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Notification refresh failed.');
                    }

                    const payload = await response.json();
                    const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
                    triggerBarangayLiveAlerts(notifications);
                    renderBarangayNotifications(notifications, Number(payload.unread_count || 0));
                } catch (error) {
                    console.warn('Unable to refresh barangay notifications:', error);
                }
            }

            function ensureBarangayIncidentAudio() {
                if (barangayIncidentAudioContext) {
                    return;
                }

                try {
                    const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextCtor) {
                        return;
                    }

                    barangayIncidentAudioContext = new AudioContextCtor();
                    if (barangayIncidentAudioContext.state === 'suspended') {
                        barangayIncidentAudioContext.resume().catch(() => {});
                    }
                } catch (error) {
                    console.warn('Unable to initialize barangay alert audio:', error);
                }
            }

            function playBarangayIncidentTone() {
                ensureBarangayIncidentAudio();
                if (!barangayIncidentAudioContext) {
                    return;
                }

                try {
                    const now = barangayIncidentAudioContext.currentTime;
                    [880, 660, 880].forEach((frequency, index) => {
                        const oscillator = barangayIncidentAudioContext.createOscillator();
                        const gainNode = barangayIncidentAudioContext.createGain();
                        const startAt = now + (index * 0.18);

                        oscillator.type = 'sine';
                        oscillator.frequency.value = frequency;
                        gainNode.gain.setValueAtTime(0.0001, startAt);
                        gainNode.gain.exponentialRampToValueAtTime(0.16, startAt + 0.03);
                        gainNode.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.16);

                        oscillator.connect(gainNode);
                        gainNode.connect(barangayIncidentAudioContext.destination);
                        oscillator.start(startAt);
                        oscillator.stop(startAt + 0.17);
                    });
                } catch (error) {
                    console.warn('Unable to play barangay alert tone:', error);
                }
            }

            function showBarangayLiveAlertToast(title, message) {
                if (!barangayLiveAlertToast) {
                    return;
                }

                barangayLiveAlertToast.innerHTML = `
                    <div class="barangay-live-alert-toast__row">
                        <div class="barangay-live-alert-toast__icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                        <div class="barangay-live-alert-toast__copy">
                            <strong>${escapeHtml(title)}</strong>
                            <p>${escapeHtml(message)}</p>
                        </div>
                    </div>
                `;
                barangayLiveAlertToast.classList.add('is-visible');
                window.clearTimeout(barangayLiveAlertTimer);
                barangayLiveAlertTimer = window.setTimeout(() => {
                    barangayLiveAlertToast.classList.remove('is-visible');
                }, 6000);
            }

            function triggerBarangayLiveAlerts(notifications) {
                const storageKey = 'pyrosense_barangay_fire_alert_seen';
                let seenIds = [];

                try {
                    seenIds = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
                    if (!Array.isArray(seenIds)) {
                        seenIds = [];
                    }
                } catch (error) {
                    seenIds = [];
                }

                let changed = false;

                notifications.forEach((notification) => {
                    if (notification?.category !== 'fire-nearby' || !notification?.id) {
                        return;
                    }

                    if (seenIds.includes(notification.id)) {
                        return;
                    }

                    seenIds.push(notification.id);
                    changed = true;
                    playBarangayIncidentTone();
                    if (navigator.vibrate) {
                        navigator.vibrate([240, 120, 240]);
                    }
                    showBarangayLiveAlertToast(
                        notification.title || 'Nearby fire alert',
                        notification.message || 'A fire incident was reported near the barangay area.'
                    );
                });

                if (changed) {
                    try {
                        window.localStorage.setItem(storageKey, JSON.stringify(seenIds.slice(-40)));
                    } catch (error) {
                        console.warn('Unable to persist barangay fire alert state:', error);
                    }
                }
            }

            function renderBarangayNotifications(notifications, unreadCount) {
                if (!notificationList || !notificationBadge) {
                    return;
                }

                notificationBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                notificationBadge.classList.toggle('is-hidden', unreadCount < 1);

                if (!notifications.length) {
                    notificationList.innerHTML = `
                        <div class="portal-notification-empty" data-barangay-notification-empty>
                            <strong>No notifications yet</strong>
                            <p>Barangay alerts will appear here as soon as the system records them.</p>
                        </div>
                    `;
                    return;
                }

                notificationList.innerHTML = notifications.map((notification) => {
                    const title = escapeHtml(notification.title || 'Barangay notification');
                    const message = escapeHtml(notification.message || '');
                    const icon = escapeHtml(notification.icon || 'bi bi-bell-fill');
                    const createdAt = escapeHtml(notification.created_at_human || '');
                    const category = escapeHtml(String(notification.category || 'general').replace(/-/g, ' ').toUpperCase());
                    const url = escapeHtml(notification.url || '');
                    const id = escapeHtml(notification.id || '');

                    return `
                        <button
                            type="button"
                            class="portal-notification-item ${notification.is_read ? '' : 'is-unread'}"
                            data-barangay-notification-item
                            data-notification-id="${id}"
                            data-notification-url="${url}"
                        >
                            <span class="portal-notification-icon">
                                <i class="${icon}"></i>
                            </span>
                            <span class="portal-notification-copy">
                                <strong>${title}</strong>
                                <p>${message}</p>
                                <span class="portal-notification-meta">
                                    <span>${createdAt}</span>
                                    <span>${category}</span>
                                </span>
                            </span>
                        </button>
                    `;
                }).join('');
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            refreshBarangayNotifications();
            notificationRefreshTimer = window.setInterval(refreshBarangayNotifications, 30000);

            function showQuickReportStatus(message, type = 'error') {
                if (!quickReportStatus) {
                    return;
                }

                quickReportStatus.textContent = message;
                const toneClass = type === 'success' ? 'is-success' : (type === 'warning' ? 'is-warning' : 'is-error');
                quickReportStatus.className = `barangay-quick-report-status is-visible ${toneClass}`;
                window.clearTimeout(quickReportTimer);
                quickReportTimer = window.setTimeout(() => {
                    quickReportStatus.className = 'barangay-quick-report-status';
                    quickReportStatus.textContent = '';
                }, 3800);
            }

            function readCachedCoords() {
                try {
                    const raw = window.localStorage.getItem('pyrosense:lastKnownCoords');
                    const parsed = raw ? JSON.parse(raw) : null;

                    if (!parsed?.lat || !parsed?.lng) {
                        return null;
                    }

                    return {
                        lat: Number(parsed.lat),
                        lng: Number(parsed.lng),
                        timestamp: Number(parsed.timestamp || 0),
                    };
                } catch (error) {
                    console.warn('Unable to read cached coordinates:', error);
                    return null;
                }
            }

            function writeCachedCoords(lat, lng) {
                try {
                    window.localStorage.setItem('pyrosense:lastKnownCoords', JSON.stringify({
                        lat,
                        lng,
                        timestamp: Date.now(),
                    }));
                } catch (error) {
                    console.warn('Unable to cache coordinates:', error);
                }
            }

            async function getCurrentCoords() {
                if (typeof window.PyroLocation?.getReliableLocationFix !== 'function') {
                    throw new Error('Accurate location service is still loading. Please try again.');
                }

                const coords = await window.PyroLocation.getReliableLocationFix({ acceptBestAvailable: true });
                writeCachedCoords(coords.lat, coords.lng);
                return coords;
            }

            async function submitQuickFireReport(button) {
                if (button.classList.contains('is-loading')) {
                    return;
                }

                const reportUrl = button.getAttribute('data-report-url');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                if (!reportUrl) {
                    window.location.href = button.getAttribute('href') || '{{ route('barangay.reports') }}';
                    return;
                }

                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                button.setAttribute('aria-disabled', 'true');
                if (button instanceof HTMLButtonElement) {
                    button.disabled = true;
                }
                showQuickReportStatus('Getting a fresh, accurate GPS fix...', 'success');
                let pendingReportBody = null;
                const clientReportId = window.crypto?.randomUUID?.() || null;

                try {
                    const coords = await getCurrentCoords();
                    showQuickReportStatus(
                        coords.accuracyWarning
                            ? `Weak GPS (±${Math.round(coords.accuracy)} m). Sending with the best available location...`
                            : `GPS ready within ±${Math.round(coords.accuracy)} m. Sending the fire report...`,
                        coords.accuracyWarning ? 'warning' : 'success'
                    );

                    const reportBody = {
                        lat: coords.lat,
                        lng: coords.lng,
                        accuracy: coords.accuracy,
                        location_captured_at: new Date(coords.timestamp).toISOString(),
                        client_report_id: clientReportId,
                    };
                    pendingReportBody = reportBody;

                    if (!window.navigator.onLine) {
                        if (typeof window.pyroPwa?.enqueueOfflineReport !== 'function') {
                            throw new Error('The offline report queue is not ready. Please keep this page open and try again.');
                        }

                        window.pyroPwa.enqueueOfflineReport({
                            url: reportUrl,
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(reportBody),
                            type: 'barangay-one-tap-fire-report',
                            meta: { queued_at: new Date().toISOString() },
                        });
                        showQuickReportStatus('Fire report saved offline. It will sync after reconnecting.', 'success');
                        return;
                    }

                    const response = await fetch(reportUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(reportBody),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(payload?.message || 'Unable to submit the fire report right now.');
                    }

                    showQuickReportStatus(payload?.message || 'Fire report submitted successfully.', 'success');
                } catch (error) {
                    if ((!window.navigator.onLine || error instanceof TypeError)
                        && pendingReportBody
                        && typeof window.pyroPwa?.enqueueOfflineReport === 'function') {
                        window.pyroPwa.enqueueOfflineReport({
                            url: reportUrl,
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(pendingReportBody),
                            type: 'barangay-one-tap-fire-report',
                            meta: { queued_at: new Date().toISOString() },
                        });
                        showQuickReportStatus('The connection failed, so the fire report was saved offline and will sync automatically.', 'success');
                        return;
                    }
                    showQuickReportStatus(error.message || 'Unable to submit the fire report right now.');
                } finally {
                    button.classList.remove('is-loading');
                    button.removeAttribute('aria-busy');
                    button.removeAttribute('aria-disabled');
                    if (button instanceof HTMLButtonElement) {
                        button.disabled = false;
                    }
                }
            }
        });
    </script>
    @include('chief.partials.logs-modal', ['chiefLogsAllowAll' => false])
    @include('chief.partials.logs-script', ['chiefLogsAllowAll' => false])
    @include('barangay.partials.logout-confirmation')
    @include('partials.voice-call-config')
    @include('partials.pwa-body')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
