<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    

        <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role->role ?? 'citizen' }}">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? 4 }}">

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>PyroSense · Citizen Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:FILL@1&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .fa,
        .fas,
        .far,
        .fal,
        .fab,
        .fa-solid,
        .fa-regular,
        .fa-brands {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        }
        .fa,
        .fas,
        .fal,
        .fa-solid {
            font-weight: 900 !important;
        }
        .far,
        .fa-regular {
            font-weight: 400 !important;
        }
            :root {
  --safe-top: env(safe-area-inset-top, 0px);
  --safe-bottom: env(safe-area-inset-bottom, 0px);
  --mobile-bottom-nav-h: calc(88px + var(--safe-bottom));
}

.material-symbols-rounded {
  font-family: "Material Symbols Rounded" !important;
  font-weight: normal;
  font-style: normal;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
  overflow: hidden;
  -webkit-font-smoothing: antialiased;
  font-variation-settings:
    "FILL" 1,
    "wght" 500,
    "GRAD" 0,
    "opsz" 24;
}

.citizen-mobile-panel .material-symbols-rounded,
.citizen-mobile-insights-panel .material-symbols-rounded {
  width: 1.25em;
  height: 1.25em;
  flex: 0 0 1.25em;
}

/* Dark mode variables */
@media (prefers-color-scheme: dark) {
  :root {
  }
}
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            width: 90%;
            max-width: 350px;
            margin: 100px auto;
            border-radius: 1rem;
        }
        #sidebars {

  }

        #sidebarScrollArea {
            height: 100vh;
            overflow-y: auto;
            overscroll-behavior: contain;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #sidebarScrollArea::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }

        .citizen-mobile-dock {
            display: none;
        }

        .citizen-mobile-panel {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            z-index: 260;
            transition: opacity 0.22s ease;
        }

        .citizen-mobile-panel.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .citizen-mobile-panel__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.44);
        }

        .citizen-mobile-panel__sheet {
            position: relative;
            margin-top: auto;
            width: min(100%, 420px);
            min-height: 280px;
            max-height: min(86vh, 720px);
            background: #fffdfa;
            border-radius: 28px 28px 0 0;
            box-shadow: 0 -18px 40px rgba(15, 23, 42, 0.18);
            padding: 1rem 1rem calc(1rem + var(--safe-bottom));
            overflow: auto;
            transform: translateY(16px);
            transition: transform 0.22s ease;
        }

        .citizen-mobile-panel.is-open .citizen-mobile-panel__sheet {
            transform: translateY(0);
        }

        .citizen-mobile-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .citizen-mobile-panel__eyebrow {
            color: #c63d26;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .citizen-mobile-panel__title {
            color: #0f172a;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .citizen-mobile-panel__close {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 999px;
            background: #fff1ea;
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .citizen-mobile-panel__menu,
        .citizen-mobile-panel__body {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .citizen-mobile-panel__action {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 18px;
            background: #ffffff;
            color: #0f172a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.95rem 1rem;
        }

        .citizen-mobile-panel__action--danger {
            background: #fff1f2;
            color: #be123c;
        }

        .citizen-mobile-panel__action-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #fff7ed;
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .citizen-mobile-panel__action--danger .citizen-mobile-panel__action-icon {
            background: #ffe4e6;
            color: #be123c;
        }

        .citizen-mobile-panel__action-copy strong {
            display: block;
            font-size: 0.96rem;
            font-weight: 700;
        }

        .citizen-mobile-panel__action-copy {
            flex: 1;
            min-width: 0;
            text-align: left;
        }

        .citizen-mobile-panel__action-trailing {
            margin-left: auto;
            color: #94a3b8;
            flex: 0 0 auto;
            font-size: 21px;
        }

        .citizen-mobile-panel__menu-label {
            margin: 0.2rem 0.3rem -0.25rem;
            color: #94a3b8;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .citizen-mobile-panel__menu-divider {
            height: 1px;
            margin: 0.2rem 0;
            background: rgba(226, 232, 240, 0.9);
        }

        .citizen-mobile-panel__action-copy small {
            display: block;
            margin-top: 0.12rem;
            color: #64748b;
            font-size: 0.76rem;
        }

        .citizen-mobile-panel__action--danger .citizen-mobile-panel__action-copy small {
            color: #be123c;
        }

        .citizen-mobile-welcome-toast {
            display: none;
        }

        .citizen-mobile-insights-panel {
            position: fixed;
            inset: 0;
            opacity: 0;
            pointer-events: none;
            z-index: 240;
            transition: opacity 0.22s ease;
        }

        .citizen-mobile-insights-panel.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .citizen-mobile-insights-panel__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
        }

        .citizen-mobile-insights-panel__sheet {
            position: absolute;
            inset: auto 0 0;
            background: linear-gradient(180deg, #fffdf8 0%, #fff9ef 100%);
            border-radius: 28px 28px 0 0;
            box-shadow: 0 -18px 36px rgba(15, 23, 42, 0.16);
            padding: 1rem 1rem calc(1rem + var(--safe-bottom));
            max-height: min(86vh, 760px);
            overflow-y: auto;
            transform: translateY(14px);
            transition: transform 0.22s ease;
        }

        .citizen-mobile-insights-panel.is-open .citizen-mobile-insights-panel__sheet {
            transform: translateY(0);
        }

        .citizen-mobile-insights-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .citizen-mobile-insights-panel__eyebrow {
            color: #c63d26;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .citizen-mobile-insights-panel__title {
            color: #0f172a;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .citizen-mobile-insights-panel__close {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 999px;
            background: #fff1ea;
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .citizen-mobile-insights-panel__content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            #myLogsModal {
                align-items: flex-end !important;
                justify-content: center !important;
                padding: 0 !important;
                background: rgba(15, 23, 42, 0.28) !important;
                backdrop-filter: blur(4px) !important;
                z-index: 680 !important;
            }

            #myLogsModal > div {
                width: 100% !important;
                max-width: 430px !important;
                max-height: min(78svh, 640px) !important;
                margin: 0 !important;
                border-radius: 24px 24px 0 0 !important;
                border: 1px solid rgba(226, 232, 240, 0.9) !important;
                border-bottom: 0 !important;
                box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.18) !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }

            #myLogsModal > div::before {
                content: "";
                width: 42px;
                height: 4px;
                margin: 8px auto 0;
                border-radius: 999px;
                background: #cbd5e1;
                flex: 0 0 auto;
            }

            #myLogsModal > div > div:first-child {
                padding: 12px 16px 10px !important;
                background: #fffdfa !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }

            #myLogsModal h2 {
                font-size: 1.05rem !important;
                line-height: 1.2 !important;
            }

            #myLogsModal h2 + p {
                font-size: 0.78rem !important;
                line-height: 1.25 !important;
                margin-top: 2px !important;
            }

            #closeMyLogsModal {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                border-radius: 14px !important;
                color: #475569 !important;
                background: #f8fafc !important;
            }

            #myLogsModal > div > div:nth-child(2) {
                padding: 10px 16px !important;
                gap: 10px !important;
                background: #ffffff !important;
                flex: 0 0 auto !important;
            }

            #myLogsSummary {
                font-size: 0.78rem !important;
                line-height: 1.25 !important;
            }

            #refreshMyLogsBtn {
                min-height: 38px !important;
                padding: 0 12px !important;
                border-radius: 12px !important;
                font-size: 0.78rem !important;
                white-space: nowrap !important;
            }

            #myLogsContainer {
                max-height: none !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                padding: 12px 14px calc(14px + var(--safe-bottom)) !important;
                overflow-y: auto !important;
            }

            #myLogsContainer .my-log-card {
                border-radius: 16px !important;
                padding: 12px !important;
                box-shadow: none !important;
            }

            #myLogsContainer .my-log-card > .flex {
                gap: 10px !important;
            }

            #myLogsContainer .my-log-card__icon {
                width: 36px !important;
                height: 36px !important;
                border-radius: 12px !important;
            }

            #myLogsContainer .my-log-card p {
                font-size: 0.8rem !important;
                line-height: 1.35 !important;
            }

            #myLogsContainer .my-log-card .text-sm {
                font-size: 0.76rem !important;
                line-height: 1.35 !important;
            }
        }

        [data-citizen-map-layers-panel] {
            align-items: flex-end;
            justify-content: stretch;
            z-index: 210;
        }

        [data-citizen-map-layers-panel].is-open {
            pointer-events: none;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__backdrop {
            background: transparent;
            pointer-events: none;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__sheet {
            width: 100vw;
            max-width: 100vw;
            min-height: 0;
            max-height: calc(45vh + var(--mobile-bottom-nav-h));
            margin: 0;
            padding: 6px 16px calc(var(--mobile-bottom-nav-h) + 12px);
            border-radius: 24px 24px 0 0;
            background: #ffffff;
            box-shadow: none;
            filter: none;
            -webkit-filter: none;
            overflow: hidden;
            pointer-events: auto;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__sheet:has(.citizen-map-legend-disclosure[open]) {
            max-height: min(60vh, 430px);
            overflow-y: auto;
        }

        .citizen-map-sheet-handle {
            width: 42px;
            height: 4px;
            margin: 0 auto 4px;
            border-radius: 999px;
            background: #cbd5e1;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__header {
            align-items: center;
            gap: 12px;
            min-height: 44px;
            margin: 0 0 4px;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__title {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .citizen-map-sheet-subtitle {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.2;
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__close {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f8fafc;
            color: #475569;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__close .material-symbols-rounded {
            font-size: 20px;
        }

        .citizen-map-sheet-section {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .citizen-map-base-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 2px;
            padding: 2px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .citizen-map-base-item,
        .citizen-map-layer-item {
            position: relative;
            min-width: 0;
            cursor: pointer;
        }

        .citizen-map-base-item {
            min-height: 32px;
            border-radius: 10px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
        }

        .citizen-map-base-item__toggle,
        .citizen-map-layer-item__toggle {
            appearance: none;
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .citizen-map-base-item:has(.citizen-map-base-item__toggle:checked) {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .citizen-map-layer-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 5px;
            padding: 0;
        }

        .citizen-map-layer-item {
            min-height: 38px;
            padding: 0 8px;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-align: left;
        }

        .citizen-map-layer-item__icon {
            width: 15px;
            height: 15px;
            flex: 0 0 15px;
            border-radius: 0;
            background: transparent;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.74rem;
            box-shadow: none;
        }

        .citizen-map-layer-item__copy strong {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1.1;
            white-space: nowrap;
        }

        .citizen-map-layer-item__check {
            margin-left: auto;
            color: #2563eb;
            display: none;
            font-size: 0.56rem;
        }

        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) {
            background: #eff6ff;
            border-color: rgba(37, 99, 235, 0.32);
            color: #2563eb;
        }

        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) .citizen-map-layer-item__icon,
        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) .citizen-map-layer-item__copy strong {
            color: #2563eb;
        }

        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) .citizen-map-layer-item__check {
            display: inline-flex;
        }

        .citizen-map-legend-disclosure {
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-disclosure summary {
            min-height: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 800;
            list-style: none;
            cursor: pointer;
        }

        .citizen-map-legend-disclosure summary::-webkit-details-marker {
            display: none;
        }

        .citizen-map-legend-disclosure summary i {
            color: #64748b;
            transition: transform 0.18s ease;
        }

        .citizen-map-legend-disclosure[open] summary i {
            transform: rotate(180deg);
        }

        .citizen-map-legend-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 8px;
            padding: 4px 2px 6px;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-item-sheet {
            min-height: 26px;
            padding: 0 2px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            border: 0;
            background: transparent !important;
            border-radius: 0 !important;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: left;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-disclosure,
        .citizen-map-legend-disclosure *,
        .citizen-map-legend-disclosure::before,
        .citizen-map-legend-disclosure::after {
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-swatch {
            width: 20px;
            height: 20px;
            min-width: 20px;
            border-radius: 6px;
            background-color: transparent !important;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-marker,
        .citizen-map-legend-item-sheet .citizen-map-legend-swatch-truck {
            width: 16px;
            height: 16px;
            box-shadow: none !important;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-image {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: block;
            filter: none !important;
        }

        .citizen-map-legend-item-sheet .marker-legend-icon {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: block;
            filter: none !important;
        }

        .citizen-map-legend-disclosure .incident-marker__pulse,
        .citizen-map-legend-disclosure .incident-pulse {
            display: none !important;
            animation: none !important;
        }

        .citizen-map-legend-item-sheet .incident-marker {
            width: 20px !important;
            height: 20px !important;
        }

        .citizen-map-legend-item-sheet .incident-marker__icon,
        .citizen-map-legend-item-sheet .incident-marker__icon > div,
        .citizen-map-legend-item-sheet .incident-marker__icon img {
            width: 20px !important;
            height: 20px !important;
        }

        .citizen-map-legend-item-sheet .incident-marker__pulse {
            inset: 1px;
            border-width: 2px;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-marker i {
            font-size: 9px;
        }

        .citizen-map-legend-swatch-barangay {
            background: rgba(15, 118, 110, 0.14);
        }

        .citizen-map-legend-marker-barangay {
            background: #0f766e;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .citizen-map-legend-marker-barangay svg {
            width: 12px;
            height: 12px;
            stroke: #fff;
            stroke-width: 1.8;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-swatch-truck img {
            width: 12px;
            height: 12px;
        }

        .citizen-map-legend-item-sheet span:last-child {
            font-size: 0.74rem;
            font-weight: 700;
            color: #475569;
        }

        .citizen-map-stage > .flex.flex-col.sm\:flex-row.sm\:items-center.justify-between {
            display: none !important;
        }

        .citizen-overview-welcome {
            display: none !important;
        }

        .citizen-welcome-alert {
            position: fixed;
            top: 84px;
            left: 50%;
            transform: translate(-50%, -16px);
            width: min(420px, calc(100vw - 32px));
            padding: 14px 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(194, 65, 12, 0.98), rgba(249, 115, 22, 0.96));
            color: #fff;
            box-shadow: 0 18px 36px rgba(194, 65, 12, 0.28);
            z-index: 70;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .citizen-welcome-alert.is-visible {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        .citizen-welcome-alert__row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .citizen-welcome-alert__icon {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .citizen-welcome-alert__copy strong {
            display: block;
            font-size: 0.98rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .citizen-welcome-alert__copy span {
            display: block;
            margin-top: 2px;
            font-size: 0.82rem;
            color: rgba(255, 247, 237, 0.94);
        }

        .citizen-welcome-alert__close {
            margin-left: auto;
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, 0.86);
            font-size: 1rem;
            cursor: pointer;
            pointer-events: auto;
        }

        #userMap {
            position: relative;
            z-index: 0;
            height: 400px;
            width: 100%;
            border-radius: 0.5rem;
        }

        .citizen-map-search {
            position: relative;
        }

        .citizen-map-shell {
            position: relative;
        }

        .citizen-map-search--overlay {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: min(520px, calc(100% - 180px));
            z-index: 500;
        }

        .citizen-map-shell .leaflet-top {
            top: 86px;
        }

        .citizen-map-search__input-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 14px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid transparent;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .citizen-map-search__icon {
            color: #f97316;
            flex-shrink: 0;
        }

        .citizen-map-search__input {
            flex: 1;
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            font-size: 14px;
            color: #111827;
            appearance: none;
            -webkit-appearance: none;
        }

        .citizen-map-search__input:focus,
        .citizen-map-search__input:focus-visible {
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
        }

        .citizen-map-search__input::-webkit-search-cancel-button,
        .citizen-map-search__input::-webkit-search-decoration,
        .citizen-map-search__input::-webkit-search-results-button,
        .citizen-map-search__input::-webkit-search-results-decoration {
            display: none;
            -webkit-appearance: none;
        }

        .citizen-map-search__input::placeholder {
            color: #9ca3af;
        }

        .citizen-map-search__clear {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 0;
            background: #fff7ed;
            color: #c2410c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .citizen-map-search__hint {
            margin-top: 8px;
            font-size: 11px;
            color: #9ca3af;
            padding-left: 2px;
            padding-right: 6px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.78);
        }

        .citizen-map-search__results {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            z-index: 30;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .citizen-map-layer-trigger {
            position: absolute;
            top: 88px;
            right: 12px;
            width: 46px;
            height: 46px;
            border: 0;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            color: #475569;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
            z-index: 490;
            backdrop-filter: blur(12px);
        }

        .citizen-map-search__results-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .citizen-map-search__result {
            width: 100%;
            border: 0;
            background: #fff;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-align: left;
            padding: 14px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }

        .citizen-map-search__result:last-child {
            border-bottom: 0;
        }

        .citizen-map-search__result:hover,
        .citizen-map-search__result.is-active {
            background: #fff7ed;
        }

        .citizen-map-search__result-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
            background: linear-gradient(135deg, #f97316 0%, #dc2626 100%);
        }

        .citizen-map-search__result-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .citizen-map-search__result-title {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
        }

        .citizen-map-search__result-meta {
            font-size: 11px;
            font-weight: 700;
            color: #c2410c;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .citizen-map-search__result-desc {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }

        .citizen-map-search__empty {
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        @media (max-width: 768px) {
            .citizen-map-search--overlay {
                top: 18px;
                left: 14px;
                right: 14px;
                width: auto;
                transform: none;
            }

            .citizen-map-search__input-wrap {
                min-height: 48px;
                padding: 0 12px;
                border-radius: 16px;
            }

            .citizen-map-search__hint {
                display: none;
            }

            .citizen-map-shell .leaflet-top {
                top: 84px;
            }

            .citizen-map-shell .leaflet-left {
                left: 10px;
            }

            .citizen-map-shell .leaflet-right {
                right: 10px;
            }

            .citizen-map-shell .leaflet-control-zoom {
                display: none !important;
            }

            .citizen-map-layer-trigger {
                display: inline-flex;
                top: 78px;
                right: 14px;
            }

            .citizen-map-stage .leaflet-control-layers-list,
            .citizen-map-stage .leaflet-control-layers-overlays,
            .citizen-map-stage .leaflet-control-layers-base {
                display: none !important;
            }

            .citizen-map-stage .leaflet-control-layers {
                display: none !important;
            }
        }
        .user-emergency-bg {
            background: linear-gradient(135deg, #b91c1c, #c2410c);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            transition: all 0.2s ease;
        }
        .nav-item.active {
            background: linear-gradient(135deg, #fee2e2, #ffedd5);
            color: #b91c1c;
            border-left: 4px solid #b91c1c;
            font-weight: 600;
        }
        .nav-item.active i {
            color: #b91c1c;
        }
        .content-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .content-section.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                overflow-x: hidden;
            }

            body {
                width: 100vw;
            }

            main.flex-1.min-w-0.bg-gray-50 {
                margin: 0 !important;
                padding: 0 !important;
            }

            #section-overview {
                margin: 0 !important;
                padding: 0 !important;
            }

            .citizen-map-stage {
                position: relative;
                left: auto;
                width: 100vw;
                max-width: 100vw;
                margin: calc(-1 * var(--safe-top)) 0 0 calc(50% - 50vw) !important;
            }

            .citizen-map-stage > .flex.flex-col.sm\:flex-row.sm\:items-center.justify-between {
                display: none !important;
            }

            .citizen-map-stage .citizen-map-legend {
                display: none !important;
            }

            .citizen-map-stage .citizen-map-shell {
                width: 100vw;
                max-width: 100vw;
                margin: 0;
                border-radius: 0 !important;
                overflow: hidden;
            }

            .citizen-map-stage #userMap {
                width: 100vw !important;
                min-height: calc(100svh + var(--safe-top)) !important;
                height: calc(100svh + var(--safe-top)) !important;
                border-radius: 0 !important;
            }

            .citizen-map-stage .leaflet-top {
                top: 84px;
            }

            .citizen-map-stage .leaflet-right {
                right: 14px;
                left: auto !important;
            }

            .citizen-map-stage .leaflet-control-layers {
                display: none !important;
                pointer-events: none !important;
            }

            .citizen-top-nav {
                display: none !important;
            }

            .citizen-overview-welcome {
                display: none !important;
            }

            #openSidebar {
                display: none !important;
            }

            #sidebar,
            #sidebarBackdrop {
                display: none !important;
            }

            .citizen-mobile-main-pad {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 0 calc(var(--mobile-bottom-nav-h) + 10px) !important;
                margin: 0 !important;
            }

            .content-section:not(#section-overview) {
                width: min(100% - 28px, 680px);
                margin: 0 auto !important;
                padding: 16px 0 28px !important;
            }

            .content-section:not(#section-overview) > .bg-white,
            .content-section:not(#section-overview) > .rounded-2xl,
            .content-section:not(#section-overview) > .grid,
            .content-section:not(#section-overview) > .space-y-6 {
                margin-inline: auto;
            }

            .citizen-mobile-dock {
                position: fixed;
                left: 0;
                right: 0;
                bottom: calc(12px + var(--safe-bottom));
                display: block;
                min-height: 0;
                padding: 0 14px;
                background: linear-gradient(180deg, rgba(251, 247, 239, 0) 0%, rgba(251, 247, 239, 0.58) 46%, rgba(251, 247, 239, 0.94) 100%);
                backdrop-filter: blur(8px);
                z-index: 220;
                pointer-events: none;
            }

            .citizen-mobile-dock__shell {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: flex-end;
                pointer-events: none;
            }

            .citizen-mobile-dock__track {
                position: relative;
                width: min(100%, 344px);
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 0;
                min-height: 60px;
                padding: 10px 18px 9px;
                border-radius: 24px;
                background: linear-gradient(180deg, rgba(18, 53, 60, 0.95) 0%, rgba(22, 73, 82, 0.96) 100%);
                border: 1px solid rgba(255, 255, 255, 0.12);
                box-shadow: 0 18px 38px rgba(15, 23, 42, 0.24);
                pointer-events: auto;
                --citizen-dock-center-slot: 96px;
                --citizen-dock-side-width: calc((100% - var(--citizen-dock-center-slot)) / 2);
            }

            .citizen-mobile-dock__track::before {
                content: '';
                position: absolute;
                inset: 1px;
                border-radius: inherit;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
                pointer-events: none;
            }

            .citizen-mobile-dock__item {
                position: relative;
                min-width: 44px;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                flex-direction: column;
                justify-content: center;
                justify-self: center;
                color: rgba(214, 227, 230, 0.5);
                text-decoration: none;
                transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
                border-radius: 999px;
                z-index: 1;
                border: none;
                background: transparent;
                pointer-events: auto;
            }

            .citizen-mobile-dock__group {
                position: relative;
                z-index: 2;
                width: var(--citizen-dock-side-width);
                min-width: 0;
                display: grid;
                grid-template-columns: repeat(2, minmax(44px, 1fr));
                align-items: center;
                justify-items: center;
                column-gap: clamp(8px, 2.4vw, 14px);
            }

            .citizen-mobile-dock__item.nav-item,
            .citizen-mobile-dock__item.nav-item.active {
                border-left: 0 !important;
                background: transparent !important;
                font-weight: inherit !important;
            }

            .citizen-mobile-dock__item.active {
                color: #ffffff;
            }

            .citizen-mobile-dock__item.active:not(.citizen-mobile-dock__item--center)::before {
                content: '';
                position: absolute;
                inset: 5px;
                border-radius: 999px;
                background: rgba(34, 197, 94, 0.16);
                box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.14);
                z-index: -1;
            }

            .citizen-mobile-dock__icon {
                width: 20px;
                height: 20px;
                font-size: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
                flex-shrink: 0;
            }

            .citizen-mobile-dock__label {
                display: block;
                max-width: 52px;
                margin-top: 2px;
                overflow: hidden;
                color: currentColor;
                font-size: 8px;
                font-weight: 750;
                line-height: 1;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .citizen-mobile-dock__item--call {
                color: #fecaca;
            }

            .citizen-mobile-dock__item--call::before {
                content: '';
                position: absolute;
                inset: 4px;
                z-index: -1;
                border-radius: 999px;
                background: rgba(220, 38, 38, 0.18);
                box-shadow: inset 0 0 0 1px rgba(254, 202, 202, 0.12);
            }

            .citizen-mobile-dock__center-wrap {
                position: absolute;
                left: 50%;
                top: 10px;
                width: var(--citizen-dock-center-slot);
                height: 66px;
                overflow: visible;
                pointer-events: auto;
                transform: translateX(-50%);
                z-index: 5;
            }

            .citizen-mobile-dock__item--center {
                position: absolute;
                left: 50%;
                top: 0;
                width: 66px;
                height: 66px;
                min-width: 66px;
                min-height: 66px;
                margin-top: -30px;
                padding: 0;
                color: #ffffff;
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                border: 4px solid #f5f7ec;
                border-radius: 999px;
                appearance: none;
                -webkit-appearance: none;
                flex-shrink: 0;
                box-shadow: 0 18px 34px rgba(34, 197, 94, 0.34), 0 0 0 8px rgba(255, 255, 255, 0.22);
                z-index: 3;
                pointer-events: auto;
                transform: translateX(-50%);
                flex-direction: row;
            }

            .citizen-mobile-dock__item--center::before,
            .citizen-mobile-dock__item--center::after {
                content: '';
                position: absolute;
                border-radius: 999px;
                pointer-events: none;
            }

            .citizen-mobile-dock__item--center::before {
                inset: 8px;
                border: 1px solid rgba(255, 255, 255, 0.34);
            }

            .citizen-mobile-dock__item--center::after {
                inset: 4px 9px auto;
                height: 16px;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.28), rgba(255, 255, 255, 0));
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__item--center {
                transform: translateX(-50%) scale(1.05);
            }

            .citizen-mobile-dock__burst {
                position: absolute;
                left: 50%;
                bottom: 80px;
                width: 154px;
                min-height: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                backdrop-filter: none;
                opacity: 0;
                transform: translate(-50%, 14px) scale(0.92);
                pointer-events: none;
                transition: opacity 0.2s ease, transform 0.2s ease;
                z-index: 6;
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst {
                opacity: 1;
                transform: translate(-50%, 0) scale(1);
                pointer-events: auto;
            }

            .alerts-fab {
                display: none !important;
                pointer-events: none !important;
            }

            .citizen-mobile-dock__burst-item {
                position: relative;
                width: 154px;
                min-width: 154px;
                height: 52px;
                display: inline-flex;
                align-items: center;
                flex-direction: row;
                justify-content: center;
                gap: 8px;
                padding: 0 14px;
                border-radius: 999px;
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #ffffff;
                border: 1px solid rgba(255, 255, 255, 0.78);
                box-shadow: 0 14px 28px rgba(126, 45, 18, 0.28);
                text-decoration: none;
                opacity: 0;
                transform: translateY(12px) scale(0.9);
                transition: opacity 0.2s ease, transform 0.2s ease, background 0.2s ease, color 0.2s ease;
                pointer-events: none;
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item {
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }

            .citizen-mobile-dock__burst-item.active {
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #ffffff;
                border-color: transparent;
            }

            .citizen-mobile-dock__burst-icon {
                width: 18px;
                height: 18px;
                font-size: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
                flex-shrink: 0;
            }

            .citizen-mobile-dock__burst-label {
                display: block;
                width: auto;
                overflow: hidden;
                font-size: 10px;
                font-weight: 800;
                line-height: 1.1;
                text-align: center;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            #citizenSosBtn {
                display: none;
            }

            #citizenSosMenu:not(.hidden) {
                position: fixed;
                top: auto;
                right: 14px;
                bottom: calc(var(--mobile-bottom-nav-h) + var(--safe-bottom) + 12px);
                left: 14px;
                z-index: 245;
                width: auto;
                max-width: 420px;
                margin: 0 auto;
            }

            .citizen-mobile-welcome-toast {
                position: fixed;
                top: calc(var(--safe-top) + 12px);
                left: 12px;
                right: 12px;
                z-index: 320;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 16px;
                border-radius: 22px;
                background: rgba(255, 251, 235, 0.96);
                border: 1px solid rgba(245, 158, 11, 0.18);
                box-shadow: 0 18px 36px rgba(15, 23, 42, 0.10);
                opacity: 0;
                transform: translateY(-8px);
                pointer-events: none;
                transition: opacity 0.24s ease, transform 0.24s ease;
            }

            .citizen-mobile-welcome-toast.is-visible {
                opacity: 1;
                transform: translateY(0);
            }

            .citizen-mobile-welcome-toast__icon {
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

            .citizen-mobile-welcome-toast__copy {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 0;
            }

            .citizen-mobile-welcome-toast__copy strong {
                color: #9a3412;
                font-size: 0.92rem;
                line-height: 1.2;
                font-weight: 800;
            }

            .citizen-mobile-welcome-toast__copy span {
                color: #b45309;
                font-size: 0.76rem;
                line-height: 1.35;
            }

            .citizen-mobile-nav-snackbar {
                position: fixed;
                left: 50%;
                bottom: calc(var(--mobile-bottom-nav-h) + 30px);
                z-index: 340;
                min-width: min(220px, calc(100vw - 56px));
                max-width: min(320px, calc(100vw - 40px));
                min-height: 40px;
                padding: 8px 12px;
                border-radius: 12px;
                background: rgba(15, 23, 42, 0.94);
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 0.8rem;
                font-weight: 800;
                line-height: 1.25;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
                opacity: 0;
                transform: translate(-50%, 12px);
                pointer-events: none;
                transition: opacity 0.18s ease, transform 0.18s ease;
                text-align: center;
            }

            .citizen-mobile-nav-snackbar.is-visible {
                opacity: 1;
                transform: translate(-50%, 0);
            }

            .citizen-mobile-nav-snackbar__spinner {
                width: 14px;
                height: 14px;
                border-radius: 999px;
                border: 2px solid rgba(255, 255, 255, 0.32);
                border-top-color: #ffffff;
                animation: citizenNavSnackbarSpin 0.72s linear infinite;
                flex: 0 0 14px;
            }

            @keyframes citizenNavSnackbarSpin {
                to {
                    transform: rotate(360deg);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .citizen-mobile-nav-snackbar,
                .citizen-mobile-nav-snackbar__spinner {
                    transition: none;
                    animation: none;
                }
            }

            [data-citizen-insights-source] {
                display: none !important;
            }

            .citizen-mobile-insights-panel.is-open [data-citizen-insights-source] {
                display: block !important;
            }

            #fireIncidentModal,
            #concernReportModal {
                align-items: center !important;
                justify-content: center !important;
                padding: calc(12px + var(--safe-top)) 12px calc(12px + var(--safe-bottom)) !important;
            }

            #fireIncidentModal > div,
            #concernReportModal > div {
                width: min(100%, 360px) !important;
                max-height: min(74svh, 520px) !important;
                margin: 0 auto !important;
                border-radius: 22px !important;
                box-shadow: 0 18px 42px rgba(15, 23, 42, 0.24) !important;
            }

            #fireIncidentModal > div > div:first-child,
            #concernReportModal > div > div:first-child {
                padding: 12px 14px !important;
            }

            #fireIncidentModal > div > div:first-child .h-11,
            #concernReportModal > div > div:first-child .h-12 {
                display: none !important;
            }

            #fireIncidentModal h2,
            #concernReportModal h2 {
                font-size: 1.04rem !important;
                line-height: 1.18 !important;
                margin-top: 0 !important;
            }

            #fireIncidentModal h2 + p,
            #concernReportModal h2 + p,
            #concernReportModal .citizen-concern-intro-card {
                display: none !important;
            }

            #concernReportModal .tracking-\[0\.22em\] {
                display: none !important;
            }

            #closeFireModal,
            #closeConcernModal {
                width: 38px !important;
                height: 38px !important;
                min-width: 38px !important;
                min-height: 38px !important;
                flex: 0 0 38px !important;
                padding: 0 !important;
            }

            #fireIncidentModal form,
            #concernReportModal form {
                padding: 12px 14px !important;
            }

            #fireLocationPanel,
            #hazardLocationPanel,
            #concernReportModal form .rounded-3xl,
            #concernReportModal form .rounded-2xl {
                border-radius: 16px !important;
                padding: 11px !important;
                box-shadow: none !important;
            }

            #fireLocationPanel .mt-3,
            #hazardLocationPanel .mt-4,
            #concernReportModal .mt-4,
            #concernReportModal .mt-3 {
                margin-top: 8px !important;
            }

            #fireLocationPanel > .flex,
            #hazardLocationPanel > .flex {
                align-items: center !important;
            }

            #fireLocationPanel p,
            #hazardLocationPanel p {
                line-height: 1.25 !important;
            }

            #fireLocationPanel .grid,
            #hazardLocationPanel .flex.flex-wrap {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
            }

            #fireUseCurrentLocationButton,
            #firePickOnMapButton,
            #hazardUseCurrentLocationButton,
            #hazardPickOnMapButton {
                min-height: 42px !important;
                width: 100% !important;
                justify-content: center !important;
                border-radius: 12px !important;
                padding: 8px 9px !important;
                font-size: 0.76rem !important;
                line-height: 1.15 !important;
            }

            #fireUseCurrentLocationButton .material-symbols-rounded,
            #firePickOnMapButton .material-symbols-rounded,
            #hazardUseCurrentLocationButton .material-symbols-rounded,
            #hazardPickOnMapButton .material-symbols-rounded {
                font-size: 16px !important;
            }

            #fireLocationStatus,
            #hazardLocationStatus {
                font-size: 0.74rem !important;
                line-height: 1.22 !important;
            }

            #fireIncidentModal form > .mt-4,
            #concernReportModal form > .mt-4 {
                margin-top: 10px !important;
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
            }

            #fireIncidentModal form > .mt-4 button,
            #concernReportModal form > .mt-4 button {
                width: 100% !important;
                min-height: 42px !important;
                justify-content: center !important;
                border-radius: 12px !important;
                padding: 8px 10px !important;
                font-size: 0.82rem !important;
            }

            .citizen-concern-chip {
                min-height: 38px !important;
                border-radius: 13px !important;
                padding: 7px 8px !important;
                font-size: 0.7rem !important;
                line-height: 1.12 !important;
            }

            .citizen-concern-chip .material-symbols-rounded {
                font-size: 15px !important;
                margin-right: 2px !important;
            }

            .citizen-concern-detail-chip {
                min-height: 30px !important;
                padding: 6px 9px !important;
                font-size: 0.68rem !important;
            }

            #concernNotesInput {
                min-height: 40px !important;
                height: 40px !important;
                border-radius: 14px !important;
                font-size: 0.78rem !important;
                padding: 8px 10px !important;
            }

            [data-citizen-settings-panel] {
                align-items: stretch;
                z-index: 620;
            }

            [data-citizen-settings-panel] .citizen-mobile-panel__sheet {
                width: 100%;
                max-width: 100%;
                min-height: 100dvh;
                max-height: 100dvh;
                height: 100dvh;
                margin-top: 0;
                border-radius: 0;
                padding-top: calc(1rem + var(--safe-top));
                padding-bottom: calc(1rem + var(--safe-bottom));
            }
        }
    </style>
    @include('partials.voice-call-config', [
        'pyroVoiceFallbackStations' => $stations
            ->filter(fn ($station) => filled($station->contact_number))
            ->sortByDesc(fn ($station) => (int) ($station->address?->barangay?->barangay_id ?? 0) === (int) ($citizenUser->address?->barangay?->barangay_id ?? -1))
            ->map(fn ($station) => [
            'id' => (int) $station->station_id,
            'name' => $station->station_name,
            'phone' => $station->contact_number,
            'lat' => $station->marker?->lat,
            'lng' => $station->marker?->lng,
            ])->values(),
    ])
    @vite(['resources/css/app.css', 'resources/css/citizen/dashboard.css', 'resources/js/user.js'])

    <script>
    document.addEventListener('DOMContentLoaded', function(){
          
          var user = @json($usermarker)|| [];
          
   window.mapData = {
        usermark: @json($usermarker) || [],
        marker: @json($usermarker) || []
    };
    
    // Also pass incidents data to JavaScript
    window.incidentsData = @json($incidents) || [];
    window.myReportedIncidents = @json($myReports) || [];
    window.nearbyAlertsData = @json($nearbyIncidents ?? []) || [];
    window.citizenCombinedReports = @json($citizenCombinedReports ?? []) || [];
    window.citizenRiskMapReports = @json($riskMapReports ?? []) || [];
    window.currentCitizenId = {{ $accountId ?? 'null' }};
    window.citizenSidebarDefaults = {
        drills: Number(@json($drillsAttended ?? 5)),
        badges: Number(@json($badges ?? 2))
    };
    
    console.log('Markers loaded:', window.mapData.usermark.length);
    console.log('Incidents loaded:', window.incidentsData.length);

    const serverOverviewSnapshot = {
        summary: {
            active_near_you: Number(@json($activeAlerts ?? 0)),
            your_reports: Number(@json($userReports ?? 0)),
            total_reports: Number(@json($overviewReportCount ?? 0)),
            active_alerts: Number(@json($activeAlerts ?? 0)),
            risk_percentage: Number(@json($riskPercentage ?? 0)),
            risk_level: @json($riskLevel ?? 'moderate')
        },
        incidents: Array.isArray(window.incidentsData) ? window.incidentsData : [],
        user_incidents: Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [],
        user_reports: Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : [],
        risk_map_reports: Array.isArray(window.citizenRiskMapReports) ? window.citizenRiskMapReports : [],
        nearby_incidents: Array.isArray(window.nearbyAlertsData) ? window.nearbyAlertsData : [],
        requests_count: Number(@json($fsicRequests?->count() ?? 0)),
    };

    const hydrateCitizenDashboardFromOfflineCache = () => {
        const cachedOverview = window.pyroPwa?.loadDataset?.('citizen-overview', null);
        const cachedMarkers = window.pyroPwa?.loadDataset?.('citizen-map-markers', null);

        if (cachedMarkers && Array.isArray(cachedMarkers) && cachedMarkers.length) {
            window.mapData.usermark = cachedMarkers;
            window.mapData.marker = cachedMarkers;
        } else {
            window.pyroPwa?.storeDataset?.('citizen-map-markers', window.mapData.usermark || []);
        }

        if (!cachedOverview) {
            window.persistCitizenOverviewOfflineData?.(serverOverviewSnapshot);
            return;
        }

        window.applyCitizenOverviewData?.(cachedOverview, { fromCache: true });

        if (window.pyroPwa?.isOffline?.()) {
            window.showCitizenToast?.('Showing your last saved dashboard snapshot while offline.', false, 4200);
        }
    };

    if (window.pyroPwa?.isOffline?.()) {
        hydrateCitizenDashboardFromOfflineCache();
    } else {
        window.persistCitizenOverviewOfflineData?.(serverOverviewSnapshot);
        window.pyroPwa?.storeDataset?.('citizen-map-markers', window.mapData.usermark || []);
    }

});

  
</script>
    @include('partials.pwa-head')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col lg:flex-row">
    @php
        $citizenUser = Auth::user();
        $fullName = trim($citizenUser->name ?? '');
        $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        $firstName = trim($citizenUser->first_name ?? ($nameParts[0] ?? 'Citizen'));
        $lastName = trim($citizenUser->last_name ?? (count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : ''));
        $displayName = trim($firstName . ' ' . $lastName);
        $displayName = $displayName !== '' ? $displayName : ($fullName !== '' ? $fullName : 'Citizen');
        $initialSeedLast = $lastName !== '' ? $lastName : (count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : $firstName);
        $citizenInitials = strtoupper(substr($firstName ?: 'C', 0, 1) . substr($initialSeedLast ?: 'P', 0, 1));
        $citizenProfileImageUrl = \App\Support\MediaPath::url($citizenUser->image);
    @endphp

    <!-- SIDE NAVIGATION – Dynamic Citizen Dashboard -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-full w-80 bg-white shadow-xl lg:shadow-md z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-hidden">
        <div class="flex flex-col h-full">
            <!-- Brand Header -->
            <div class="user-emergency-bg text-white p-6 relative">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-20 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm px-2 py-1">
                        @include('partials.app-logo', ['class' => 'w-full h-full object-contain'])
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">PyroSense </h2>
                        <p class="text-white/80 text-xs">citizen portal</p>
                    </div>
                </div>
                <button id="closeSidebar" class="absolute top-5 right-4 lg:hidden text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
                
                <!-- User Info Badge -->
                <div class="relative z-10 mt-4 flex items-center gap-2 text-xs bg-white/10 p-2 rounded-lg">
                    <i class="fa-solid fa-location-dot text-white/80"></i>
                    <span id="citizenSidebarResolvedLocation" class="text-white/90">{{ Auth::user()->userInfo->city ?? 'District 4' }}, {{ Auth::user()->userInfo->barangay ?? 'Zone 7' }}</span>
                    <span class="ml-auto bg-green-400/20 text-green-200 px-2 py-0.5 rounded-full text-[10px]">verified</span>
                </div>
            </div>

            <div id="sidebarScrollArea" class="flex-1 min-h-0 overflow-y-auto">

            <!-- User Profile Summary -->
            <div class="px-4 py-4 border-b border-gray-200 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full overflow-hidden bg-orange-100 flex items-center justify-center text-orange-600 text-xl font-bold" data-citizen-avatar-frame>
                        @if ($citizenProfileImageUrl)
                            <img
                                src="{{ $citizenProfileImageUrl }}"
                                alt="{{ $displayName }}"
                                class="w-full h-full object-cover"
                                data-citizen-avatar-image
                            >
                        @else
                            <span data-citizen-avatar-fallback>{{ $citizenInitials }}</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">{{ $displayName }}</p>
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <i class="fa-regular fa-envelope"></i> {{ Auth::user()->email }}
                        </p>
                        <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                            <i class="fa-regular fa-circle-check text-green-500"></i> 
                            {{ Auth::user()->userInfo->role->role ?? 'Citizen' }} · Member since {{ Auth::user()->created_at->format('M Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Dynamic Navigation Menu -->
            <nav class="py-4 px-3">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">MAIN MENU</p>
                <ul class="space-y-1">
                    <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="overview" data-citizen-switch-section="overview">
                            <i class="fa-solid fa-gauge-high w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Overview</span>
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">live</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts" data-citizen-switch-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Areas of Concern</span>
                            <span id="citizenNavAlertsBadge" class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 0 }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="fsic" data-citizen-switch-section="fsic">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span id="citizenNavRequestsBadge" class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $fsicRequests->count() }}</span>
                        </a>
                    </li>
                </ul>

                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">COMMUNITY</p>
                <ul class="space-y-1">
                    <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="contacts" data-citizen-switch-section="contacts">
                            <i class="fa-solid fa-phone-volume w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Emergency Contacts</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="announcements" data-citizen-switch-section="announcements">
                            <i class="fa-solid fa-bullhorn w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Announcements</span>
                        </a>
                    </li>
                    {{-- <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="neighborhood" data-citizen-switch-section="neighborhood">
                            <i class="fa-solid fa-people-group w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Neighborhood Watch</span>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">8 online</span>
                        </a>
                    </li> --}}
                    {{-- <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="events" data-citizen-switch-section="events">
                            <i class="fa-solid fa-calendar-check w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Drills & Events</span>
                        </a>
                    </li> --}}
                    {{-- <li>
                        <a class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="news" data-citizen-switch-section="news">
                            <i class="fa-solid fa-newspaper w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">News & Updates</span>
                        </a>
                    </li> --}}
                </ul>

            </nav>

            <!-- ANALYTICS SECTION - SEPARATE FROM NAVIGATION -->
            <div class="px-4 py-4 border-t border-gray-200">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line"></i> YOUR ANALYTICS
                </p>
                <div class="space-y-3">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-3 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-medium text-gray-600">Safety Score</span>
                            <span class="text-xs font-bold text-green-600">85%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-green-600 h-1.5 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-blue-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Alerts</p>
                            <p id="citizenSidebarAlertsValue" class="text-lg font-bold text-blue-600">{{ $totalAlerts ?? 12 }}</p>
                        </div>
                        <div class="bg-orange-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Reports</p>
                            <p id="citizenSidebarReportsValue" class="text-lg font-bold text-orange-600">{{ $overviewReportCount ?? $countReport ?? 0 }}</p>
                        </div>
                        <div class="bg-purple-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Drills</p>
                            <p id="citizenSidebarDrillsValue" class="text-lg font-bold text-orange-600">{{ $drillsAttended ?? 5 }}</p>
                        </div>
                        <div class="bg-amber-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Badges</p>
                            <p id="citizenSidebarBadgesValue" class="text-lg font-bold text-amber-600">{{ $badges ?? 2 }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-medium text-gray-600">Weekly Activity</span>
                            <span id="citizenWeeklyActivityChange" class="text-xs text-gray-500">0 this week</span>
                        </div>
                        <div id="citizenWeeklyActivityBars" class="flex gap-1 h-12 items-end" aria-label="Weekly activity chart">
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                            <div class="flex-1 bg-orange-100 rounded-t" style="height: 8%"></div>
                        </div>
                        <div id="citizenWeeklyActivityLabels" class="flex justify-between mt-1 text-[8px] text-gray-400">
                            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                        </div>
                        <p id="citizenWeeklyActivityPeriod" class="mt-2 text-[10px] text-gray-400">Current week</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- mobile backdrop -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <nav class="citizen-mobile-dock md:hidden" aria-label="Citizen mobile navigation">
        <div class="citizen-mobile-dock__shell">
            <div class="citizen-mobile-dock__track">
                <div class="citizen-mobile-dock__group citizen-mobile-dock__group--left">
                    <button type="button" class="citizen-mobile-dock__item active" data-citizen-dock-item="overview" data-citizen-switch-section="overview" aria-label="Map overview">
                        <svg class="citizen-mobile-dock__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"></path>
                            <path d="M9 3v15"></path>
                            <path d="M15 6v15"></path>
                        </svg>
                        <span class="citizen-mobile-dock__label">Map</span>
                    </button>

                    <button type="button" class="citizen-mobile-dock__item citizen-mobile-dock__item--call" aria-label="Emergency calling options" aria-haspopup="menu" aria-controls="citizenSosMenu" aria-expanded="false" data-citizen-sos-open>
                        <svg class="citizen-mobile-dock__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.5-3A19.2 19.2 0 0 1 5.4 13 19.6 19.6 0 0 1 2.4 4.5 2 2 0 0 1 4.4 2.3h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.8L8.1 10a15.5 15.5 0 0 0 5.9 5.9l1.2-1.2a2 2 0 0 1 1.8-.6l3 .5a2 2 0 0 1 2 2.3z"></path>
                        </svg>
                        <span class="citizen-mobile-dock__label">Call</span>
                    </button>
                </div>

                <div class="citizen-mobile-dock__center-wrap" data-citizen-nav-center>
                    <div class="citizen-mobile-dock__burst" data-citizen-nav-burst>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-risk-hazard-open aria-label="Report risk or hazard">
                            <svg class="citizen-mobile-dock__burst-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 13h3l10 5V6L7 11H4z"></path>
                                <path d="M7 13v5"></path>
                                <path d="M18 9.5a3 3 0 0 1 0 5"></path>
                            </svg>
                            <span class="citizen-mobile-dock__burst-label">Report Risk / Hazard</span>
                        </button>
                    </div>

                    <button type="button" class="citizen-mobile-dock__item citizen-mobile-dock__item--center" data-citizen-nav-trigger aria-label="Report fire incident">
                        <svg class="citizen-mobile-dock__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.4 21c4.1 0 6.8-2.7 6.8-6.5 0-2.4-1.2-4.5-3.1-5.8.1 2.1-.8 3.5-2.2 4.1.4-3.3-1.1-6.8-4.4-9.8.2 3.3-1.6 5.3-3 7.2-1 1.3-1.7 2.6-1.7 4.3C4.8 18.3 7.9 21 12.4 21z"></path>
                            <path d="M12 21c1.7 0 3-1.2 3-2.9 0-1.2-.7-2.2-1.8-3 .1 1.2-.4 2-1.2 2.4.2-1.8-.6-3.4-2.1-4.8.1 2-.9 3-1.5 4-.4.6-.6 1.1-.6 1.8 0 1.5 1.4 2.5 4.2 2.5z"></path>
                        </svg>
                    </button>
                </div>

                <div class="citizen-mobile-dock__group citizen-mobile-dock__group--right">
                    <button type="button" class="citizen-mobile-dock__item" data-citizen-dock-item="announcements" data-citizen-switch-section="announcements" aria-label="Announcements">
                        <svg class="citizen-mobile-dock__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 13h3l10 5V6L7 11H4z"></path>
                            <path d="M7 13v5"></path>
                            <path d="M18 9.5a3 3 0 0 1 0 5"></path>
                        </svg>
                        <span class="citizen-mobile-dock__label">Updates</span>
                    </button>

                    <button type="button" class="citizen-mobile-dock__item" data-citizen-settings-open aria-label="Open citizen menu">
                        <svg class="citizen-mobile-dock__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path>
                            <path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2.1 2.1 0 0 1-3 3l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.7V21a2.1 2.1 0 0 1-4.2 0v-.1a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .4l-.1.1a2.1 2.1 0 0 1-3-3l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.7-1.1H2a2.1 2.1 0 0 1 0-4.2h.1a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1a2.1 2.1 0 0 1 3-3l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1.1-1.7V2a2.1 2.1 0 0 1 4.2 0v.1a1.8 1.8 0 0 0 1.1 1.7 1.8 1.8 0 0 0 2-.4l.1-.1a2.1 2.1 0 0 1 3 3l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.7 1.1h.1a2.1 2.1 0 0 1 0 4.2h-.1a1.8 1.8 0 0 0-1.7 1.3z"></path>
                        </svg>
                        <span class="citizen-mobile-dock__label">Menu</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="citizen-mobile-panel md:hidden" data-citizen-settings-panel aria-hidden="true">
        <div class="citizen-mobile-panel__backdrop" data-citizen-panel-close></div>
        <div class="citizen-mobile-panel__sheet">
            <div class="citizen-mobile-panel__header">
                <div>
                    <p class="citizen-mobile-panel__eyebrow">Citizen Portal</p>
                    <h2 class="citizen-mobile-panel__title">Menu</h2>
                </div>
                <button type="button" class="citizen-mobile-panel__close" data-citizen-panel-close aria-label="Close menu">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="citizen-mobile-panel__menu">
                <p class="citizen-mobile-panel__menu-label">Explore</p>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-switch-section="fsic" data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">folder_open</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Requests</strong>
                        <small>Applications and community requests</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-switch-section="alerts" data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">warning</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Areas of Concern</strong>
                        <small>Nearby incidents, risks, and hazards</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-switch-section="contacts" data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">contact_phone</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Emergency Contacts</strong>
                        <small>View station and emergency numbers</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-insights-open>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">monitoring</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Insights</strong>
                        <small>Open charts and safety summaries</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-alerts-open>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">notifications</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Notifications</strong>
                        <small>Review your latest alerts</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <div class="citizen-mobile-panel__menu-divider" aria-hidden="true"></div>
                <p class="citizen-mobile-panel__menu-label">Account</p>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-switch-section="profile" data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">person</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Profile</strong>
                        <small>Manage your citizen account</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-open-logs>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">history</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>My Logs</strong>
                        <small>View your recent activity</small>
                    </span>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-trailing" aria-hidden="true">chevron_right</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="citizen-mobile-panel__action citizen-mobile-panel__action--danger">
                        <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">logout</span>
                        <span class="citizen-mobile-panel__action-copy">
                            <strong>Logout</strong>
                            <small>Sign out of the citizen portal</small>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="citizen-mobile-panel md:hidden" data-citizen-map-layers-panel aria-hidden="true" inert>
        <div class="citizen-mobile-panel__backdrop" data-citizen-map-layers-close></div>
        <div class="citizen-mobile-panel__sheet">
            <div class="citizen-map-sheet-handle" aria-hidden="true"></div>
            <div class="citizen-mobile-panel__header">
                <div>
                    <h2 class="citizen-mobile-panel__title">Map options</h2>
                    <p class="citizen-map-sheet-subtitle">Show or hide details on the map</p>
                </div>
                <button type="button" class="citizen-mobile-panel__close" data-citizen-map-layers-close aria-label="Close map filters and legend">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="citizen-mobile-panel__body citizen-map-sheet-section">
                <div class="citizen-map-base-list" role="radiogroup" aria-label="Map layer">
                    <label class="citizen-map-base-item">
                        <input type="radio" class="citizen-map-base-item__toggle" name="citizen_map_base" data-citizen-map-base-toggle="OSM Standard" checked>
                        <span>OSM</span>
                    </label>
                    <label class="citizen-map-base-item">
                        <input type="radio" class="citizen-map-base-item__toggle" name="citizen_map_base" data-citizen-map-base-toggle="OSM HOT">
                        <span>OSM HOT</span>
                    </label>
                    <label class="citizen-map-base-item">
                        <input type="radio" class="citizen-map-base-item__toggle" name="citizen_map_base" data-citizen-map-base-toggle="Satellite">
                        <span>Satellite</span>
                    </label>
                </div>

                <div class="citizen-map-layer-list">
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="stations" checked>
                        <span class="citizen-map-layer-item__icon" aria-hidden="true"><i class="fa-solid fa-building-shield"></i></span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Stations</strong>
                        </span>
                        <span class="citizen-map-layer-item__check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="incidents" checked>
                        <span class="citizen-map-layer-item__icon" aria-hidden="true"><i class="fa-solid fa-fire-flame-curved"></i></span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Incidents</strong>
                        </span>
                        <span class="citizen-map-layer-item__check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="hydrants" checked>
                        <span class="citizen-map-layer-item__icon" aria-hidden="true"><i class="fa-solid fa-droplet"></i></span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Hydrants</strong>
                        </span>
                        <span class="citizen-map-layer-item__check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="others" checked>
                        <span class="citizen-map-layer-item__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Places</strong>
                        </span>
                        <span class="citizen-map-layer-item__check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </label>
                </div>
                <details class="citizen-map-legend-disclosure">
                    <summary>
                        <span>Map legend</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </summary>
                    <div class="citizen-map-legend-list">
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-user"><span class="citizen-map-legend-marker citizen-map-legend-marker-user"><i class="fas fa-user-shield"></i></span></span><span>Your location</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-station"><img class="citizen-map-legend-image" src="{{ asset('station.png') }}" alt="Fire station" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><span class="citizen-map-legend-marker citizen-map-legend-marker-station" style="display:none;"><i class="fas fa-building"></i></span></span><span>Fire station</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-barangay"><img class="citizen-map-legend-image" src="{{ asset('hall.png') }}" alt="Barangay hall" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><span class="citizen-map-legend-marker citizen-map-legend-marker-barangay" style="display:none;"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="9" r="2" fill="none"></circle><circle cx="16" cy="9" r="2" fill="none"></circle><circle cx="12" cy="7" r="2" fill="none"></circle><path d="M4 18a4 4 0 0 1 8 0M12 18a4 4 0 0 1 8 0M8 16a4 4 0 0 1 8 0"></path></svg></span></span><span>Barangay hall</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-incident"><img class="marker-legend-icon marker-legend-icon--incident" src="{{ asset('fire.png') }}" alt="Incident" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><span class="citizen-map-legend-marker citizen-map-legend-marker-incident" style="display:none;"><i class="fas fa-fire"></i></span></span><span>Incident</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-hydrant"><img class="citizen-map-legend-image" src="{{ asset('hydrant.png') }}" alt="Hydrant" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><span class="citizen-map-legend-marker citizen-map-legend-marker-hydrant" style="display:none;"><i class="fas fa-fire-extinguisher"></i></span></span><span>Hydrant</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-other"><span class="citizen-map-legend-marker citizen-map-legend-marker-other"><i class="fas fa-map-marker-alt"></i></span></span><span>Other location</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-route"><span class="citizen-map-legend-route-line"></span></span><span>Route</span></div>
                        <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-truck"><img src="{{ asset('truck.png') }}" alt="Responding unit" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><span class="citizen-map-legend-marker" style="display:none;color:#f97316;"><i class="fas fa-truck-fast"></i></span></span><span>Responding unit</span></div>
                    </div>
                </details>
            </div>
        </div>
    </div>
    <div class="citizen-mobile-insights-panel md:hidden" data-citizen-insights-panel aria-hidden="true">
        <div class="citizen-mobile-insights-panel__backdrop" data-citizen-insights-close></div>
        <div class="citizen-mobile-insights-panel__sheet">
            <div class="citizen-mobile-insights-panel__header">
                <div>
                    <p class="citizen-mobile-insights-panel__eyebrow">Citizen Portal</p>
                    <h2 class="citizen-mobile-insights-panel__title">Charts and Cards</h2>
                </div>
                <button type="button" class="citizen-mobile-insights-panel__close" data-citizen-insights-close aria-label="Close charts and cards">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="citizen-mobile-insights-panel__content" id="citizenMobileInsightsContent"></div>
        </div>
    </div>

    <script>
        (() => {
            if (window.__citizenMobileNavGuardBound) {
                return;
            }

            window.__citizenMobileNavGuardBound = true;

            const buildSectionUrl = (sectionId) => {
                const baseUrl = (window.citizenDashboardBaseUrl || '/citizen/dashboard').replace(/\/$/, '');
                return sectionId === 'overview' ? baseUrl : `${baseUrl}/${sectionId}`;
            };

            const closeMobileBurst = () => {
                const center = document.querySelector('[data-citizen-nav-center]');
                const trigger = document.querySelector('[data-citizen-nav-trigger]');
                center?.classList.remove('is-open');
                trigger?.setAttribute('aria-expanded', 'false');
            };

            const closeSettingsPanel = () => {
                const panel = document.querySelector('[data-citizen-settings-panel]');
                panel?.classList.remove('is-open');
                panel?.setAttribute('aria-hidden', 'true');
            };

            const openSettingsPanel = () => {
                const panel = document.querySelector('[data-citizen-settings-panel]');
                panel?.classList.add('is-open');
                panel?.setAttribute('aria-hidden', 'false');
            };

            const showMobileNavSnackbar = (message = 'Loading page...', duration = 900) => {
                if (window.innerWidth > 768) {
                    return;
                }

                const snackbar = document.getElementById('citizenMobileNavSnackbar');
                const snackbarText = document.getElementById('citizenMobileNavSnackbarText');
                if (!snackbar) {
                    return;
                }

                if (snackbarText) {
                    snackbarText.textContent = message;
                }

                if (snackbar._hideTimer) {
                    window.clearTimeout(snackbar._hideTimer);
                }

                snackbar.classList.add('is-visible');
                snackbar._hideTimer = window.setTimeout(() => {
                    snackbar.classList.remove('is-visible');
                }, duration);
            };

            window.showCitizenMobileNavSnackbar = showMobileNavSnackbar;

            const openAlertsPanelWhenReady = (attempt = 0) => {
                if (typeof window.openCitizenAlertsPanel === 'function') {
                    window.openCitizenAlertsPanel();
                    return;
                }

                if (attempt < 20) {
                    window.setTimeout(() => openAlertsPanelWhenReady(attempt + 1), 50);
                }
            };

            const openConcernReportWhenReady = (attempt = 0) => {
                if (typeof window.openConcernReportModal === 'function') {
                    window.openConcernReportModal();
                    return;
                }

                if (attempt < 20) {
                    window.setTimeout(() => openConcernReportWhenReady(attempt + 1), 50);
                }
            };

            const fallbackSwitchSection = (sectionId, options = {}) => {
                if (!sectionId) {
                    return;
                }

                document.querySelectorAll('.content-section').forEach((section) => {
                    section.classList.remove('active');
                });

                document.getElementById(`section-${sectionId}`)?.classList.add('active');

                document.querySelectorAll('.nav-item, .citizen-mobile-dock__item, .citizen-mobile-dock__burst-item').forEach((item) => {
                    item.classList.remove('active');
                });

                document.querySelectorAll(`[data-section="${sectionId}"], [data-citizen-dock-item="${sectionId}"]`).forEach((item) => {
                    item.classList.add('active');
                });

                if (options.updateHistory !== false) {
                    const targetUrl = buildSectionUrl(sectionId);
                    if (window.location.pathname !== new URL(targetUrl, window.location.origin).pathname) {
                        window.history.pushState({ section: sectionId }, '', targetUrl);
                    }
                }
            };

            window.switchSection = window.switchSection || fallbackSwitchSection;

            document.addEventListener('click', (event) => {
                const eventTarget = event.target instanceof Element ? event.target : event.target?.parentElement;
                const trigger = eventTarget?.closest(
                    '[data-citizen-switch-section], [data-citizen-alerts-open], [data-citizen-settings-open], [data-citizen-panel-close], [data-citizen-insights-open], [data-citizen-insights-close], [data-citizen-risk-hazard-open]'
                );

                if (!trigger) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const sectionId = trigger.dataset.citizenSwitchSection;
                if (sectionId) {
                    showMobileNavSnackbar('Loading page...');
                    closeMobileBurst();
                    closeSettingsPanel();
                    (window.switchSection || fallbackSwitchSection)(sectionId);
                    return;
                }

                if (trigger.matches('[data-citizen-settings-open]')) {
                    showMobileNavSnackbar('Opening settings...');
                    closeMobileBurst();
                    openSettingsPanel();
                    return;
                }

                if (trigger.matches('[data-citizen-panel-close]')) {
                    closeSettingsPanel();
                    return;
                }

                if (trigger.matches('[data-citizen-alerts-open]')) {
                    showMobileNavSnackbar('Loading alerts...');
                    closeMobileBurst();
                    closeSettingsPanel();
                    (window.switchSection || fallbackSwitchSection)('overview');
                    window.setTimeout(openAlertsPanelWhenReady, 20);
                    return;
                }

                if (trigger.matches('[data-citizen-insights-open]')) {
                    showMobileNavSnackbar('Loading charts...');
                    closeMobileBurst();
                    closeSettingsPanel();
                    if (typeof window.openCitizenInsightsPanel === 'function') {
                        window.openCitizenInsightsPanel();
                    } else {
                        (window.switchSection || fallbackSwitchSection)('overview');
                    }
                    return;
                }

                if (trigger.matches('[data-citizen-insights-close]')) {
                    document.querySelector('[data-citizen-insights-panel]')?.classList.remove('is-open');
                    document.querySelector('[data-citizen-insights-panel]')?.setAttribute('aria-hidden', 'true');
                    return;
                }

                if (trigger.matches('[data-citizen-risk-hazard-open]')) {
                    showMobileNavSnackbar('Opening report...');
                    closeMobileBurst();
                    openConcernReportWhenReady();
                    return;
                }

            }, true);
        })();
    </script>

    <!-- MAIN CONTENT -->
    <main class="flex-1 min-w-0 bg-gray-50">
        {{-- sessioning --}}
        @if(session('success') && !\Illuminate\Support\Str::contains((string) session('success'), 'Welcome back'))
            <div style="color:green;">
                {{ session('success') }}
            </div>
        @endif
        <div id="citizenWelcomeAlert" class="citizen-welcome-alert" role="status" aria-live="polite">
            <div class="citizen-welcome-alert__row">
                <div class="citizen-welcome-alert__icon">
                    <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
                </div>
                <div class="citizen-welcome-alert__copy">
                    <strong>Welcome back, {{ $displayName }}</strong>
                    <span>Citizen portal is ready with your alerts, map, and reports.</span>
                </div>
                <button type="button" id="citizenWelcomeAlertClose" class="citizen-welcome-alert__close" aria-label="Dismiss welcome alert">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div id="citizenMobileNavSnackbar" class="citizen-mobile-nav-snackbar md:hidden" role="status" aria-live="polite" aria-atomic="true">
            <span class="citizen-mobile-nav-snackbar__spinner" aria-hidden="true"></span>
            <span id="citizenMobileNavSnackbarText">Loading page...</span>
        </div>
        <!-- top bar -->
        
        <nav class="citizen-top-nav bg-white shadow-sm sticky top-0 z-30">
            <div class="px-4 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" class="lg:hidden text-gray-700 focus:outline-none">
                            <i class="fa-solid fa-bars text-2xl"></i>
                        </button>
                        <!-- mobile logo -->
                        <div class="flex items-center space-x-2 lg:hidden">
                            <div class="w-14 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm ring-1 ring-orange-200 px-1.5 py-1">
                                @include('partials.app-logo', ['class' => 'w-full h-full object-contain'])
                            </div>
                            <span class="text-lg font-bold text-gray-800">Pyro<span class="text-orange-600">Sense</span></span>
                        </div>
                    </div>

                    <!-- desktop quick actions -->
                    <div class="hidden md:flex items-center space-x-4">
                        <span class="text-sm text-gray-600"><i class="fa-regular fa-bell mr-1"></i> <span id="citizenTopUnreadValue">{{ $activeAlerts ?? 0 }}</span> unread</span>
                        <span id="citizenTopDateValue" class="text-sm text-gray-600"><i class="fa-regular fa-clock mr-1"></i> {{ now()->format('l, M d') }}</span>
                    </div>

                    <!-- right side -->
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <button id="citizenSosBtn" type="button" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition flex items-center gap-2" aria-expanded="false" aria-haspopup="true" aria-controls="citizenSosMenu">
                                <i class="fa-solid fa-phone-alt"></i>
                                <span class="hidden sm:inline">SOS</span>
                            </button>
                            <div id="citizenSosMenu" class="hidden absolute right-0 top-full mt-3 w-64 rounded-2xl border border-orange-100 bg-white p-3 shadow-2xl z-[120]" role="menu" aria-label="Emergency actions">
                                <div class="mb-2 px-1">
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Emergency Actions</p>
                                </div>
                                <div class="grid gap-2">
                                    <button type="button" class="w-full rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-left text-amber-900 hover:bg-amber-100 transition" data-pyro-voice-call role="menuitem">
                                        <span class="flex items-center gap-3">
                                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shrink-0">
                                                <i class="fa-solid fa-phone-volume"></i>
                                            </span>
                                            <span class="mobile-hub-btn__copy">
                                                <strong class="block text-sm font-bold">Call BFP</strong>
                                                <small class="block mt-0.5 text-xs font-medium text-amber-700">Secure internet call, with phone fallback</small>
                                            </span>
                                        </span>
                                    </button>
                                    <a href="tel:911" class="block w-full rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-left text-rose-900 hover:bg-rose-100 transition" data-citizen-sos-close role="menuitem">
                                        <span class="flex items-center gap-3">
                                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 shrink-0">
                                                <i class="fa-solid fa-phone"></i>
                                            </span>
                                            <span>
                                                <strong class="block text-sm font-bold">Call 911</strong>
                                                <small class="block mt-0.5 text-xs font-medium text-rose-700">Open the emergency telephone line</small>
                                            </span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="user-dropdown">
                            <button id="userDropdownBtn" class="user-dropdown-toggle">
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-orange-100 flex items-center justify-center text-orange-600 text-sm font-bold" data-citizen-avatar-frame>
                                    @if ($citizenProfileImageUrl)
                                        <img
                                            src="{{ $citizenProfileImageUrl }}"
                                            alt="{{ $displayName }}"
                                            class="w-full h-full object-cover"
                                            data-citizen-avatar-image
                                        >
                                    @else
                                        <span data-citizen-avatar-fallback>{{ $citizenInitials }}</span>
                                    @endif
                                </div>
                                <span class="hidden md:inline text-sm font-semibold text-gray-700">{{ $displayName }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            
                            <div id="userDropdownMenu" class="user-dropdown-menu">
                                <div class="user-dropdown-header">
                                    <p class="text-sm font-bold text-gray-800">{{ $displayName }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <a class="user-dropdown-item" data-citizen-switch-section="profile">
                                    <i class="fa-solid fa-user"></i>
                                    <span>My Profile</span>
                                </a>
                                <a onclick="openMyLogsModal()" class="user-dropdown-item">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span>My Logs</span>
                                </a>
                                <div class="user-dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item logout-item w-full text-left">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- DYNAMIC CONTENT SECTIONS -->
        <div class="p-4 lg:p-8 max-w-7xl mx-auto citizen-mobile-main-pad">
            <!-- Overview Section -->
            @include('citizen.sections.overview')
            {{-- <div id="gallery"></div> sample na display imagee good --}}
            <!-- Alerts Section -->
{{-- <ul>
@foreach ($incidents as $incident)
    <li>
        <strong>ID:</strong> {{ $incident->id ?? $incident->incident_id }} <br>
        <strong>Type:</strong> {{ $incident->incident_type }} <br>
        <strong>Status:</strong> {{ $incident->status }} <br>
        <strong>Reported by:</strong> {{ $incident->reported_by }} <br>
        <strong>Date:</strong> {{ $incident->alarmdatetime }} <br>
    </li>
@endforeach
</ul> --}}
            @include('citizen.sections.alerts')
            
            <!-- Incidents Section -->
            @include('citizen.sections.incidents')
            @include('citizen.sections.fsic')
            <!-- Emergency Contacts Section -->
            @include('citizen.sections.contacts')
            @include('citizen.sections.announcements')
            
            <!-- Neighborhood Watch Section -->
            @include('citizen.sections.neighborhood')
            
            <!-- Drills & Events Section -->
            @include('citizen.sections.events')
            
            <!-- News Section -->
            @include('citizen.sections.news')
            
            <!-- Profile Section -->
            @include('citizen.sections.profile')
            
            <!-- Notifications Section -->
            @include('citizen.sections.notifications')
        </div>

        <!-- Fire Report Modal -->
        <div id="fireIncidentModal" class="modal fixed inset-0 bg-slate-950/55 backdrop-blur-sm flex items-center z-50 justify-center hidden p-3 sm:p-4">
            <div class="w-full max-w-md max-h-[88vh] overflow-y-auto rounded-[1.5rem] border border-orange-100 bg-white shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
                <div class="relative overflow-hidden bg-gradient-to-r from-[#c63d26] via-[#d45a1a] to-[#e39b17] px-4 py-4 text-white sm:px-5">
                    <div class="absolute inset-y-0 right-0 w-32 bg-white/10 blur-2xl"></div>
                    <div class="relative flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/18 ring-1 ring-white/25 backdrop-blur">
                                <i class="fa-solid fa-fire-flame-curved text-[22px]" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold leading-tight">Report fire incident</h2>
                                <p class="mt-1 text-sm text-white/85">Choose how to set the fire location.</p>
                            </div>
                        </div>
                        <button type="button" id="closeFireModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/12 text-white transition hover:bg-white/20" aria-label="Close fire report modal">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>

                <form id="fireIncidentForm" action="" method="POST" class="px-4 py-4 sm:px-5">
                    @csrf
                    <div class="grid gap-3">
                        <div id="fireLocationPanel" class="rounded-2xl border border-orange-100 bg-[#fffaf4] p-4 text-sm text-slate-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Incident location</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Use GPS or pick within 100m.</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-orange-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-orange-700">100m</span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 min-[360px]:grid-cols-2">
                                <button type="button" id="fireUseCurrentLocationButton" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#c63d26] to-[#e39b17] px-3 py-2.5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(198,61,38,0.22)] transition hover:brightness-105">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">my_location</span>
                                    Use my location
                                </button>
                                <button type="button" id="firePickOnMapButton" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-orange-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-700">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">place</span>
                                    Pick within 100m
                                </button>
                            </div>

                            <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <p id="fireLocationStatus" class="text-xs font-medium text-slate-600">Current location will be used.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200" data-fire-modal-close>Cancel</button>
                        <button type="button" class="js-citizen-report-action inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#c63d26] to-[#e39b17] px-4 py-3 text-sm font-semibold text-white shadow-[0_14px_26px_rgba(198,61,38,0.24)] transition hover:brightness-105" data-report-kind="fire">
                            <span class="material-symbols-rounded text-[18px]" aria-hidden="true">bolt</span>
                            Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Risk / Hazard Modal -->
        <div id="concernReportModal" class="modal fixed inset-0 bg-slate-950/55 backdrop-blur-sm flex items-center z-50 justify-center hidden p-4">
            <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-[2rem] border border-amber-100 bg-white shadow-[0_32px_90px_rgba(15,23,42,0.24)]">
                <div class="relative overflow-hidden bg-gradient-to-r from-[#b45309] via-[#d97706] to-[#f59e0b] px-5 py-4 text-white">
                    <div class="absolute inset-y-0 right-0 w-32 bg-white/10 blur-2xl"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/18 ring-1 ring-white/25 backdrop-blur">
                                <span class="material-symbols-rounded text-[24px]" aria-hidden="true">report_problem</span>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/75">PyroSense Community Safety</p>
                                <h2 class="mt-1 text-xl font-bold leading-tight">Report Risk / Hazard</h2>
                                <p class="mt-2 max-w-sm text-sm text-white/85">Choose the closest concern type and point. BFP will verify the final classification.</p>
                            </div>
                        </div>
                        <button type="button" id="closeConcernModal" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/12 text-white transition hover:bg-white/20" aria-label="Close risk and hazard modal">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>

                <form id="concernIncidentForm" action="" method="POST" enctype="multipart/form-data" class="px-5 py-4">
                    @csrf
                    <div class="grid gap-3.5">
                        @if($errors->any())
                            <div class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <ul class="space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="citizen-concern-intro-card rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50 via-orange-50 to-white p-4 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Non-fire safety concern</p>
                                    <p class="mt-1">Use this for unsafe fire-related conditions that are not an active fire emergency.</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700">BFP review</span>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm">
                            <label class="block text-sm font-semibold text-slate-900">What are you seeing?</label>
                            <p class="mt-1 text-xs text-slate-500">Pick the closest match. BFP can reclassify it after review.</p>
                            <select id="concernTypeSelect" class="sr-only" aria-label="Risk or hazard type">
                                <option value="unsure">I am not sure yet</option>
                                <option value="electrical_issue">Electrical issue</option>
                                <option value="unsafe_cooking_lpg">Unsafe cooking or LPG use</option>
                                <option value="blocked_hydrant_access">Blocked hydrant or fire access</option>
                                <option value="combustible_clutter">Combustible clutter or open burning</option>
                                <option value="dense_fire_prone_area">Dense fire-prone area</option>
                            </select>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-left text-xs font-semibold text-amber-800 transition" data-concern-type="unsure">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">help</span>
                                    Not sure
                                </button>
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-700 transition" data-concern-type="electrical_issue">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">bolt</span>
                                    Electrical
                                </button>
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-700 transition" data-concern-type="unsafe_cooking_lpg">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">local_fire_department</span>
                                    LPG / cooking
                                </button>
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-700 transition" data-concern-type="blocked_hydrant_access">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">water_drop</span>
                                    Blocked access
                                </button>
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-700 transition" data-concern-type="combustible_clutter">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">delete</span>
                                    Clutter / burning
                                </button>
                                <button type="button" class="citizen-concern-chip min-h-11 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-700 transition" data-concern-type="dense_fire_prone_area">
                                    <span class="material-symbols-rounded mr-1 align-middle text-[17px]" aria-hidden="true">holiday_village</span>
                                    Dense area
                                </button>
                            </div>

                            <input type="hidden" id="concernConditionDetail" value="">
                            <div class="mt-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Add quick detail</p>
                                <div class="mt-2 flex flex-wrap gap-2" data-concern-detail-options>
                                    <button type="button" class="citizen-concern-detail-chip rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="unsure" data-condition-detail="needs_verification">Needs verification</button>
                                    <button type="button" class="citizen-concern-detail-chip rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="unsure" data-condition-detail="possible_fire_risk">Possible fire risk</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="electrical_issue" data-condition-detail="exposed_wire">Exposed wire</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="electrical_issue" data-condition-detail="sparking_outlet">Sparking outlet</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="electrical_issue" data-condition-detail="overloaded_extension">Overloaded extension</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="unsafe_cooking_lpg" data-condition-detail="leaking_lpg">Possible LPG leak</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="unsafe_cooking_lpg" data-condition-detail="open_flame_near_fuel">Open flame near fuel</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="blocked_hydrant_access" data-condition-detail="blocked_hydrant">Blocked hydrant</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="blocked_hydrant_access" data-condition-detail="narrow_access">Narrow access</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="combustible_clutter" data-condition-detail="waste_buildup">Waste buildup</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="combustible_clutter" data-condition-detail="open_burning">Open burning</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="dense_fire_prone_area" data-condition-detail="houses_too_close">Houses too close</button>
                                    <button type="button" class="citizen-concern-detail-chip hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600" data-condition-for="dense_fire_prone_area" data-condition-detail="crowded_escape_path">Crowded escape path</button>
                                </div>
                            </div>

                            <label for="concernNotesInput" class="mt-4 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Optional note</label>
                            <textarea id="concernNotesInput" rows="2" maxlength="500" class="mt-2 w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-amber-300 focus:bg-white focus:ring-2 focus:ring-amber-100" placeholder="Example: near sari-sari store, wires are exposed"></textarea>
                        </div>

                        <div id="hazardLocationPanel" class="rounded-3xl border border-amber-100 bg-[#fffaf4] p-4 text-sm text-slate-700 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Safety concern location</p>
                                    <p class="mt-1 text-slate-600">Use your current position or choose a point on the map within 100 meters.</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700">100m radius</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" id="hazardUseCurrentLocationButton" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">my_location</span>
                                    Use My Location
                                </button>
                                <button type="button" id="hazardPickOnMapButton" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-amber-700">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">place</span>
                                    Choose Location (100m)
                                </button>
                            </div>
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Location status</p>
                                <p id="hazardLocationStatus" class="mt-2 text-sm text-slate-700">Safety concern reports will use your current location unless you pick another point on the map.</p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm">
                            <label for="concernPhotosInput" class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Optional photos</label>
                            <input id="concernPhotosInput" name="photos[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            <p class="mt-2 text-xs text-slate-500">Last step before submitting: upload 1 to 3 photos, with a combined total of up to 5 MB.</p>
                            <div id="concernPhotoUploadStatus" class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600" aria-live="polite">
                                No photos selected yet. You can submit without photos, or add up to 3 images.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-end gap-3">
                        <button type="button" class="rounded-2xl border border-slate-200 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200" data-concern-modal-close>Cancel</button>
                        <button type="button" class="js-citizen-report-action inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#b45309] to-[#f59e0b] px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(217,119,6,0.24)] transition hover:brightness-105" data-report-kind="concern">
                            <span class="material-symbols-rounded text-[18px]" aria-hidden="true">send</span>
                            Submit Risk / Hazard
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <input type="hidden" id="reported_by" value="{{ $accountId }}">
        <input type="hidden" id="latitude" value="">
        <input type="hidden" id="longitude" value="">
    <div id="incidentReportLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[5000]">
        <div class="bg-white rounded-2xl shadow-xl px-6 py-5 flex items-center gap-3 text-slate-700">
            <span class="inline-fa-icon icon-spin text-orange-600" style="width: 1.25rem; height: 1.25rem;">
                <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
            </span>
            <div>
                <p id="incidentReportLoadingTitle" class="font-semibold">Submitting report</p>
                <p id="incidentReportLoadingMessage" class="text-sm text-slate-500">Saving incident details...</p>
            </div>
        </div>
    </div>
    <div id="myLogsModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-center justify-center z-[4000] p-4" aria-hidden="true">
        <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">My Logs</h2>
                    <p class="text-sm text-slate-500">Your recorded activity in the system.</p>
                </div>
                <button type="button" id="closeMyLogsModal" class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <p id="myLogsSummary" class="text-sm text-slate-500">Loading your activity logs...</p>
                <button type="button" id="refreshMyLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-50 text-orange-700 border border-orange-100 hover:bg-orange-100 text-sm font-semibold">
                    <i class="fa-solid fa-rotate"></i>
                    Refresh
                </button>
            </div>
            <div id="myLogsContainer" class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-4 bg-white">
                <div class="text-sm text-slate-500">Loading logs...</div>
            </div>
        </div>
    </div>
    <div id="toast" class="toast"></div>

    </main>

    <script>
            window.stationOfficer = @json($allStationOfficer);
            window.citizenUserLogs = [];
            window.citizenDashboardBaseUrl = @json(route('citizen.dashboard'));
            window.initialCitizenSection = @json($initialSection ?? 'overview');

            function buildCitizenSectionUrl(sectionId) {
                const baseUrl = (window.citizenDashboardBaseUrl || '').replace(/\/$/, '');

                if (!baseUrl) {
                    return sectionId === 'overview' ? '/citizen/dashboard' : `/citizen/dashboard/${sectionId}`;
                }

                return sectionId === 'overview' ? baseUrl : `${baseUrl}/${sectionId}`;
            }

            function getCitizenSectionFromPath() {
                const currentPath = window.location.pathname.replace(/\/$/, '');
                const basePath = new URL(window.citizenDashboardBaseUrl || '/citizen/dashboard', window.location.origin).pathname.replace(/\/$/, '');

                if (currentPath === basePath) {
                    return 'overview';
                }

                if (currentPath.startsWith(`${basePath}/`)) {
                    return currentPath.slice(basePath.length + 1) || 'overview';
                }

                return window.initialCitizenSection || 'overview';
            }

            window.logCitizenUserAction = async function(action) {
                const normalizedAction = String(action || '').trim();

                if (!normalizedAction || /^(?:view(?:ed|ing)?|open(?:ed|ing)?|load(?:ed|ing)?|access(?:ed|ing)?|read(?:ing)?|visit(?:ed|ing)?|navigate(?:d|ing)?)(?:\s|$)/i.test(normalizedAction)) {
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    await fetch('{{ route('user-logs.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ action: normalizedAction })
                    });
                } catch (error) {
                    console.warn('Citizen action log failed:', error);
                }
            };

        document.addEventListener('DOMContentLoaded', () => {
        //       const response = await fetch('/api/teams');
        // const data = await response.json();
        // const teams = data.stationData;

 

            if (!navigator.geolocation) {
                console.log("Geolocation not supported");
                return;
            }

            // navigator.geolocation.getCurrentPosition(
            //     function(position) {
            //         const accuracy = position.coords.accuracy;

            //         if (accuracy > 500) {
            //             alert("Location is not accurate. Please enable GPS or try again.");
            //             console.log(accuracy);
                        
            //             return;
            //         }
            //         window.userCoords.currentLatitude = position.coords.latitude;
            //         window.userCoords.currentLongitude = position.coords.longitude;
            //         window.userCoords.ready = true;
            //          window.map.setView([ window.userCoords.currentLatitude,  window.userCoords.currentLongitude], 15);
                     

            //          console.log("Location loaded:",
            //             window.userCoords.currentLatitude,
            //             window.userCoords.currentLongitude
            //         );
            //          console.log("Updated:", position.coords.latitude, position.coords.longitude);
            //          console.log(position.coords.accuracy);
            //                         L.marker([window.userCoords.currentLatitude,  window.userCoords.currentLongitude])
            //                     .addTo(window.map)
            //                     .bindPopup("You are here")
            //                     .openPopup();
            //                    L.circle([window.userCoords.currentLatitude,  window.userCoords.currentLongitude], {
            //                         color: 'red',       // border color
            //                         fillColor: '#f03',  // fill color
            //                         fillOpacity: 0.3,   // transparency
            //                         radius: 500         // in meters
            //                     }).addTo(window.map);
                                
                       

            //         // OPTIONAL: trigger anything waiting for location
            //         document.dispatchEvent(new Event("locationReady"));
            //     },
            //     function(error){
            //         console.error("Location error:", error);
            //     },
            //     {
            //         enableHighAccuracy: true,
            //         // enableHighAccuracy: false,

            //             timeout: 10000,
            //             maximumAge: 0
            //     }
            // );
    window.currentUserId = {{ auth()->user()->user_id ?? 'null' }};
    const CITIZEN_LOCATION_PUSH_INTERVAL_MS = 45000;
    let citizenLocationPushInterval = null;

    function citizenDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function citizenPositionIsReliable(position, maxAccuracyMeters = 150) {
        const accuracy = Number(position?.coords?.accuracy);
        const timestamp = Number(position?.timestamp) || Date.now();

        return Number.isFinite(accuracy)
            && accuracy > 0
            && accuracy <= maxAccuracyMeters
            && Date.now() - timestamp <= 30000;
    }

    function clearCitizenDashboardLocationIntervals() {
        if (citizenLocationPushInterval) {
            clearInterval(citizenLocationPushInterval);
            citizenLocationPushInterval = null;
        }
    }

    function startCitizenDashboardLocationIntervals() {
        clearCitizenDashboardLocationIntervals();

        citizenLocationPushInterval = setInterval(async () => {
            if (!citizenDashboardIsVisible()) return;

            if (navigator.permissions?.query) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    if (permission.state !== 'granted') return;
                } catch (error) {
                    return;
                }
            } else if (!window.userCoords?.ready) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    if (!citizenPositionIsReliable(position)) {
                        console.warn('[CitizenLocation] ignored inaccurate or stale interval fix', {
                            accuracy: position?.coords?.accuracy,
                            timestamp: position?.timestamp,
                        });
                        return;
                    }

                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    console.log('[CitizenLocation] interval GPS success', {
                        userId: window.currentUserId,
                        lat,
                        lng,
                        accuracy: position.coords.accuracy,
                        timestamp: new Date().toISOString(),
                    });
                    window.updateUserLocation?.(window.currentUserId, lat, lng, {
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    });
                    window.userCoords.currentLatitude = lat;
                    window.userCoords.currentLongitude = lng;
                    window.userCoords.ready = true;
                    console.log('[CitizenLocation] interval userCoords updated', window.userCoords);
                    if (window.refreshCitizenMapData) {
                        window.refreshCitizenMapData();
                    }
                    if (window.refreshCitizenOverviewData) {
                        window.refreshCitizenOverviewData();
                    }

                    if (typeof window.currentLoc === 'function') {
                        console.log('[CitizenLocation] interval rendering marker via currentLoc');
                        window.currentLoc(lat, lng);
                    } else {
                        console.warn('[CitizenLocation] interval currentLoc not ready, queueing marker render');
                        window.__pendingCitizenLocation = { lat, lng };
                    }
                    updateCitizenResolvedLocation(lat, lng);
                },
                function(error) {
                    console.error('[CitizenLocation] interval GPS error', error);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }, CITIZEN_LOCATION_PUSH_INTERVAL_MS);

    }

    window.citizenDashboardIsVisible = citizenDashboardIsVisible;
    window.clearCitizenDashboardLocationIntervals = clearCitizenDashboardLocationIntervals;
    window.startCitizenDashboardLocationIntervals = startCitizenDashboardLocationIntervals;
            console.log('officer dashboard: ', window.stationOfficer);
            const citizenSidebarResolvedLocation = document.getElementById('citizenSidebarResolvedLocation');
            const citizenWelcomeAlert = document.getElementById('citizenWelcomeAlert');
            const citizenWelcomeAlertClose = document.getElementById('citizenWelcomeAlertClose');
            let citizenWelcomeAlertTimer = null;

            const showCitizenWelcomeAlert = () => {
                if (!citizenWelcomeAlert) {
                    return;
                }

                citizenWelcomeAlert.classList.add('is-visible');

                if (citizenWelcomeAlertTimer) {
                    clearTimeout(citizenWelcomeAlertTimer);
                }

                citizenWelcomeAlertTimer = window.setTimeout(() => {
                    citizenWelcomeAlert.classList.remove('is-visible');
                }, 4200);
            };

            citizenWelcomeAlertClose?.addEventListener('click', () => {
                citizenWelcomeAlert?.classList.remove('is-visible');
                if (citizenWelcomeAlertTimer) {
                    clearTimeout(citizenWelcomeAlertTimer);
                }
            });

            showCitizenWelcomeAlert();

            const updateCitizenResolvedLocation = async (lat, lng) => {
                if (!citizenSidebarResolvedLocation || typeof window.setThisName !== 'function') {
                    return;
                }

                try {
                    const resolvedName = await window.setThisName(lat, lng);
                    if (resolvedName && resolvedName.trim() !== '') {
                        citizenSidebarResolvedLocation.textContent = resolvedName.trim();
                        citizenSidebarResolvedLocation.title = resolvedName.trim();
                    }
                } catch (error) {
                    console.error('Failed to resolve citizen sidebar location:', error);
                }
            };

            window.requestCitizenLocationNow = function(options = {}) {
                const { silent = false } = options;

                if (window.userCoords?.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude) {
                    console.log('[CitizenLocation] requestCitizenLocationNow using cached userCoords', window.userCoords);
                    return Promise.resolve(window.userCoords);
                }

                if (!navigator.geolocation) {
                    if (!silent) {
                        window.showCitizenToast?.('Location is not supported on this device.', true);
                    }
                    return Promise.reject(new Error('Geolocation is not supported.'));
                }

                if (window.__citizenLocationRequest) {
                    console.log('[CitizenLocation] requestCitizenLocationNow reusing in-flight request');
                    return window.__citizenLocationRequest;
                }

                if (typeof window.PyroLocation?.getReliableLocationFix === 'function') {
                    window.__citizenLocationRequest = window.PyroLocation.getReliableLocationFix()
                        .then((fix) => {
                            window.updateUserLocation?.(window.currentUserId, fix.lat, fix.lng, {
                                accuracy: fix.accuracy,
                                timestamp: fix.timestamp,
                            });
                            window.userCoords.currentLatitude = fix.lat;
                            window.userCoords.currentLongitude = fix.lng;
                            window.userCoords.accuracy = fix.accuracy;
                            window.userCoords.timestamp = fix.timestamp;
                            window.userCoords.ready = true;
                            window.refreshCitizenMapData?.();
                            window.refreshCitizenOverviewData?.();
                            if (typeof window.currentLoc === 'function') window.currentLoc(fix.lat, fix.lng);
                            else window.__pendingCitizenLocation = { lat: fix.lat, lng: fix.lng };
                            updateCitizenResolvedLocation(fix.lat, fix.lng);
                            document.dispatchEvent(new Event('locationReady'));
                            return window.userCoords;
                        })
                        .catch((error) => {
                            if (!silent) window.showCitizenToast?.(error.message || 'Unable to enable location.', true, 4200);
                            throw error;
                        })
                        .finally(() => {
                            window.__citizenLocationRequest = null;
                        });

                    return window.__citizenLocationRequest;
                }

                console.log('[CitizenLocation] requestCitizenLocationNow requesting browser GPS', {
                    silent,
                    timestamp: new Date().toISOString(),
                });
                window.__citizenLocationRequest = new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            if (!citizenPositionIsReliable(position)) {
                                reject(new Error(`Location accuracy is only ±${Math.round(Number(position?.coords?.accuracy) || 0)} m.`));
                                return;
                            }

                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            console.log('[CitizenLocation] on-demand GPS success', {
                                userId: window.currentUserId,
                                lat,
                                lng,
                                accuracy: position.coords.accuracy,
                                timestamp: new Date().toISOString(),
                            });

                            window.updateUserLocation?.(window.currentUserId, lat, lng, {
                                accuracy: position.coords.accuracy,
                                timestamp: position.timestamp
                            });
                            window.userCoords.currentLatitude = lat;
                            window.userCoords.currentLongitude = lng;
                            window.userCoords.ready = true;
                            console.log('[CitizenLocation] on-demand userCoords updated', window.userCoords);

                            window.refreshCitizenMapData?.();
                            window.refreshCitizenOverviewData?.();
                            if (typeof window.currentLoc === 'function') {
                                console.log('[CitizenLocation] on-demand rendering marker via currentLoc');
                                window.currentLoc(lat, lng);
                            } else {
                                console.warn('[CitizenLocation] on-demand currentLoc not ready, queueing marker render');
                                window.__pendingCitizenLocation = { lat, lng };
                            }
                            updateCitizenResolvedLocation(lat, lng);
                            document.dispatchEvent(new Event("locationReady"));
                            resolve(window.userCoords);
                        },
                        function(error) {
                            console.error('[CitizenLocation] on-demand GPS error', error);
                            if (!silent) {
                                window.showCitizenToast?.('Location is still loading. Please allow location access and try again.', true, 4200);
                            }
                            reject(error);
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                }).finally(() => {
                    window.__citizenLocationRequest = null;
                });

                return window.__citizenLocationRequest;
            };
async function initializeCitizenLocationIfGranted() {
if (navigator.geolocation) {
    let permissionState = 'prompt';
    if (navigator.permissions?.query) {
        try {
            permissionState = (await navigator.permissions.query({ name: 'geolocation' })).state;
        } catch (error) {
            permissionState = 'prompt';
        }
    }

    if (permissionState !== 'granted') {
        window.userCoords.currentLatitude = null;
        window.userCoords.currentLongitude = null;
        window.userCoords.ready = false;
        window.usermap?.setView?.([15.8222, 120.3297], 13);
        window.loadMarkers?.();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            if (!citizenPositionIsReliable(position)) {
                console.warn('[CitizenLocation] ignored inaccurate or stale initial fix', {
                    accuracy: position?.coords?.accuracy,
                    timestamp: position?.timestamp,
                });
                window.userCoords.ready = false;
                window.showCitizenToast?.('GPS accuracy is weak. Move outdoors or enable precise location.', true, 5000);
                return;
            }

            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            console.log('[CitizenLocation] initial GPS success', {
                userId: window.currentUserId,
                lat,
                lng,
                accuracy: position.coords.accuracy,
                timestamp: new Date().toISOString(),
            });
            // const lat = 15.232331;
            // const lng = 120.232331;

            // Update for current user
            window.updateUserLocation?.(window.currentUserId, lat, lng, {
                accuracy: position.coords.accuracy,
                timestamp: position.timestamp
            });
            window.userCoords.currentLatitude = lat;
            window.userCoords.currentLongitude = lng;
            window.userCoords.ready = true;
            console.log('[CitizenLocation] initial userCoords updated', window.userCoords);
            if (window.refreshCitizenMapData) {
                window.refreshCitizenMapData();
            }
            if (window.refreshCitizenOverviewData) {
                window.refreshCitizenOverviewData();
            }
            //  if (window.updateMapUserLocation) {
            //     window.updateMapUserLocation(lat, lng);
            // }
            
            console.log("Location loaded for user:", window.currentUserId);
            console.log("Location:", lat, lng);
            
            // Display user location on the citizen-only map
            if (typeof window.currentLoc === 'function') {
                console.log('[CitizenLocation] initial rendering marker via currentLoc');
                window.currentLoc(lat, lng);
            } else {
                console.warn('[CitizenLocation] initial currentLoc not ready, queueing marker render');
                window.__pendingCitizenLocation = { lat, lng };
            }
            updateCitizenResolvedLocation(lat, lng);
            
            // Send location to server for other users to see
            
            document.dispatchEvent(new Event("locationReady"));
        },
        function(error) {
            console.error('[CitizenLocation] initial GPS error; nearby alerts remain disabled', error);
            // Keep a neutral map center without treating it as the citizen's location.
            const defaultLat = 15.8222;
            const defaultLng = 120.3297;
            window.userCoords.currentLatitude = null;
            window.userCoords.currentLongitude = null;
            window.userCoords.ready = false;
            window.usermap?.setView?.([defaultLat, defaultLng], 13);
            window.loadMarkers?.();
            window.showCitizenToast?.('Allow location access to receive nearby fire alerts.', true, 5000);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
    
    // Initial fetch
    setTimeout(() => {
    }, 2000);
    
} else {
    console.warn("Geolocation not supported; nearby alerts remain disabled");
    const defaultLat = 15.8222;
    const defaultLng = 120.3297;
    window.userCoords.currentLatitude = null;
    window.userCoords.currentLongitude = null;
    window.userCoords.ready = false;
    window.usermap?.setView?.([defaultLat, defaultLng], 13);
    window.loadMarkers?.();
     
            
}
}

initializeCitizenLocationIfGranted();
        });

        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');

        function openSidebar() { 
            sidebar.classList.remove('-translate-x-full'); 
            backdrop.classList.remove('hidden'); 
        }
        function closeSidebarFunc() { 
            sidebar.classList.add('-translate-x-full'); 
            backdrop.classList.add('hidden'); 
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebarFunc);
        if (backdrop) backdrop.addEventListener('click', closeSidebarFunc);

        // Section Switching
        function switchSection(sectionId, options = {}) {
            const { updateHistory = true } = options;

            if (window.innerWidth <= 768) {
                window.showCitizenMobileNavSnackbar?.('Loading page...');
            }

            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            const selectedSection = document.getElementById('section-' + sectionId);
            if (selectedSection) {
                selectedSection.classList.add('active');
            }
            
            // Update active nav item
            document.querySelectorAll('.nav-item, .citizen-mobile-dock__item, .citizen-mobile-dock__burst-item').forEach(item => {
                item.classList.remove('active');
            });

            document.querySelectorAll(`[data-section="${sectionId}"], [data-citizen-dock-item="${sectionId}"]`).forEach(item => {
                item.classList.add('active');
            });

            if (sectionId === 'alerts' && typeof window.refreshCitizenRiskConcernMap === 'function') {
                window.setTimeout(() => {
                    window.refreshCitizenRiskConcernMap(window.citizenRiskMapReports || []);
                }, 120);
            }

            if (updateHistory) {
                const targetUrl = buildCitizenSectionUrl(sectionId);

                if (window.location.pathname !== new URL(targetUrl, window.location.origin).pathname) {
                    window.history.pushState({ section: sectionId }, '', targetUrl);
                }
            }
        }

        window.switchSection = switchSection;

        document.querySelectorAll('[data-citizen-switch-section]').forEach((trigger) => {
            trigger.addEventListener('click', function (event) {
                const sectionId = this.dataset.citizenSwitchSection;
                if (!sectionId) {
                    return;
                }

                event.preventDefault();
                switchSection(sectionId);
            });
        });

        window.addEventListener('popstate', function (event) {
            const sectionId = event.state?.section || getCitizenSectionFromPath();
            switchSection(sectionId, { updateHistory: false });
        });

        const citizenMobileDock = document.querySelector('[data-citizen-nav-center]');
        const citizenDockTrigger = document.querySelector('[data-citizen-nav-trigger]');
        const citizenSettingsPanel = document.querySelector('[data-citizen-settings-panel]');
        const citizenSettingsOpen = document.querySelector('[data-citizen-settings-open]');
        const citizenPanelCloseButtons = document.querySelectorAll('[data-citizen-panel-close]');
        const citizenMapLayersPanel = document.querySelector('[data-citizen-map-layers-panel]');
        const citizenMapLayersOpenButtons = document.querySelectorAll('[data-citizen-map-layers-open]');
        const citizenMapLayersCloseButtons = document.querySelectorAll('[data-citizen-map-layers-close]');
        const citizenOpenLogsButtons = document.querySelectorAll('[data-citizen-open-logs]');
        const citizenAlertsOpen = document.querySelector('[data-citizen-alerts-open]');
        const citizenInsightsPanel = document.querySelector('[data-citizen-insights-panel]');
        const citizenInsightsOpenButtons = document.querySelectorAll('[data-citizen-insights-open]');
        const citizenInsightsCloseButtons = document.querySelectorAll('[data-citizen-insights-close]');
        const citizenInsightsContent = document.getElementById('citizenMobileInsightsContent');
        const citizenInsightsSources = Array.from(document.querySelectorAll('[data-citizen-insights-source]')).map((node) => ({
            node,
            parent: node.parentElement,
            next: node.nextElementSibling
        }));
        const citizenMapLayerToggles = document.querySelectorAll('[data-citizen-map-layer-toggle]');
        const citizenMapBaseToggles = document.querySelectorAll('[data-citizen-map-base-toggle]');
        const citizenMapLayerBadges = document.querySelectorAll('[data-citizen-map-layer-badge]');
        const citizenOverlayLayerLabels = {
            stations: ['Stations', 'Fire Stations'],
            incidents: ['Incidents'],
            hydrants: ['Hydrants', 'Fire Hydrants'],
            others: ['Other Markers']
        };

        const findLeafletLayerInput = (labels, groupSelector) => {
            const labelAliases = {
                'Street Map': ['Street Map', 'OSM Standard', 'OSM'],
                'OSM Standard': ['OSM Standard', 'OSM'],
                'OSM HOT': ['OSM HOT'],
                Satellite: ['Satellite'],
                Stations: ['Stations', 'Fire Stations'],
                Hydrants: ['Hydrants', 'Fire Hydrants'],
                others: ['Other Markers', 'Other Locations']
            };
            const wantedLabels = (Array.isArray(labels) ? labels : [labels]).flatMap((label) => labelAliases[label] || [label]);
            const layerLabels = document.querySelectorAll(`${groupSelector} label`);

            for (const label of layerLabels) {
                const input = label.querySelector('input');
                const text = label.textContent.replace(/\s+/g, ' ').trim();

                if (input && wantedLabels.some((wanted) => text === wanted || text.endsWith(wanted))) {
                    return input;
                }
            }

            return null;
        };

        const updateCitizenMapLayerBadge = () => {
            const activeCount = Array.from(citizenMapLayerToggles).filter((toggle) => toggle.checked).length;

            citizenMapLayerBadges.forEach((badge) => {
                badge.hidden = activeCount === 0;
                badge.textContent = String(activeCount);
            });
        };

        const syncCitizenMapLayerControls = () => {
            citizenMapLayerToggles.forEach((toggle) => {
                const layerInput = findLeafletLayerInput(
                    citizenOverlayLayerLabels[toggle.dataset.citizenMapLayerToggle] || [],
                    '.leaflet-control-layers-overlays'
                );

                if (layerInput) {
                    toggle.checked = layerInput.checked;
                }
            });

            const activeBaseLabel = typeof window.getCitizenActiveBaseLayer === 'function'
                ? window.getCitizenActiveBaseLayer()
                : '';

            citizenMapBaseToggles.forEach((toggle) => {
                if (activeBaseLabel) {
                    toggle.checked = toggle.dataset.citizenMapBaseToggle === activeBaseLabel;
                    return;
                }

                const baseInput = findLeafletLayerInput(toggle.dataset.citizenMapBaseToggle, '.leaflet-control-layers-base');
                if (baseInput) {
                    toggle.checked = baseInput.checked;
                }
            });

            updateCitizenMapLayerBadge();
        };

        window.syncCitizenMapLayerControls = syncCitizenMapLayerControls;

        citizenMapLayerToggles.forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const layerInput = findLeafletLayerInput(
                    citizenOverlayLayerLabels[toggle.dataset.citizenMapLayerToggle] || [],
                    '.leaflet-control-layers-overlays'
                );

                if (layerInput && layerInput.checked !== toggle.checked) {
                    layerInput.click();
                }

                updateCitizenMapLayerBadge();
            });
        });

        citizenMapBaseToggles.forEach((toggle) => {
            toggle.addEventListener('change', () => {
                if (!toggle.checked) {
                    return;
                }

                if (typeof window.setCitizenBaseLayer === 'function') {
                    window.setCitizenBaseLayer(toggle.dataset.citizenMapBaseToggle);
                    syncCitizenMapLayerControls();
                    return;
                }

                const baseInput = findLeafletLayerInput(toggle.dataset.citizenMapBaseToggle, '.leaflet-control-layers-base');

                if (baseInput && !baseInput.checked) {
                    baseInput.click();
                }
            });
        });

        window.setTimeout(syncCitizenMapLayerControls, 0);

        if (citizenMobileDock && citizenDockTrigger) {
            let holdTimer = null;
            let holdActivated = false;
            const holdDelay = 620;

            const clearHoldTimer = () => {
                if (holdTimer) {
                    window.clearTimeout(holdTimer);
                    holdTimer = null;
                }
            };

            const openBurst = () => {
                citizenMobileDock.classList.add('is-open');
                citizenDockTrigger.setAttribute('aria-expanded', 'true');
            };

            const closeBurst = () => {
                citizenMobileDock.classList.remove('is-open');
                citizenDockTrigger.setAttribute('aria-expanded', 'false');
            };

            const startHold = () => {
                clearHoldTimer();
                holdActivated = false;
                holdTimer = window.setTimeout(() => {
                    holdActivated = true;
                    openBurst();
                }, holdDelay);
            };

            citizenDockTrigger.setAttribute('aria-haspopup', 'true');
            citizenDockTrigger.setAttribute('aria-expanded', 'false');

            citizenDockTrigger.addEventListener('pointerdown', startHold);
            citizenDockTrigger.addEventListener('pointerup', clearHoldTimer);
            citizenDockTrigger.addEventListener('pointerleave', clearHoldTimer);
            citizenDockTrigger.addEventListener('pointercancel', clearHoldTimer);

            citizenDockTrigger.addEventListener('click', function (event) {
                if (holdActivated) {
                    holdActivated = false;
                    event.preventDefault();
                    return;
                }
                closeBurst();
                window.showCitizenMobileNavSnackbar?.('Opening report...');
                if (typeof window.openFireIncidentModal === 'function') {
                    window.openFireIncidentModal();
                }
            });

            document.addEventListener('click', function (event) {
                if (!citizenMobileDock.contains(event.target)) {
                    closeBurst();
                }
            });

            document.querySelectorAll('.citizen-mobile-dock__burst-item').forEach((item) => {
                item.addEventListener('click', () => {
                    closeBurst();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeBurst();
                }
            });
        }

        const blurFocusedChild = (container) => {
            if (container?.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
        };

        const openCitizenSettingsPanel = () => {
            citizenSettingsPanel?.classList.add('is-open');
            citizenSettingsPanel?.setAttribute('aria-hidden', 'false');
        };

        const closeCitizenSettingsPanel = () => {
            blurFocusedChild(citizenSettingsPanel);
            citizenSettingsPanel?.classList.remove('is-open');
            citizenSettingsPanel?.setAttribute('aria-hidden', 'true');
        };

        const openCitizenMapLayersPanel = () => {
            if (citizenMapLayersPanel) {
                citizenMapLayersPanel._lastTrigger = document.activeElement;
            }
            citizenMapLayersPanel?.removeAttribute('inert');
            citizenMapLayersPanel?.classList.add('is-open');
            citizenMapLayersPanel?.setAttribute('aria-hidden', 'false');
            if (typeof window.syncCitizenMapLayerControls === 'function') {
                window.syncCitizenMapLayerControls();
            }
        };

        const closeCitizenMapLayersPanel = () => {
            const lastTrigger = citizenMapLayersPanel?._lastTrigger;
            if (citizenMapLayersPanel?.contains(document.activeElement)) {
                if (lastTrigger && typeof lastTrigger.focus === 'function') {
                    lastTrigger.focus({ preventScroll: true });
                } else if (document.activeElement instanceof HTMLElement) {
                    document.activeElement.blur();
                }
            }
            citizenMapLayersPanel?.classList.remove('is-open');
            citizenMapLayersPanel?.setAttribute('aria-hidden', 'true');
            citizenMapLayersPanel?.setAttribute('inert', '');
        };

        window.openCitizenMapLayersPanel = openCitizenMapLayersPanel;
        window.closeCitizenMapLayersPanel = closeCitizenMapLayersPanel;

        citizenSettingsOpen?.addEventListener('click', openCitizenSettingsPanel);
        citizenPanelCloseButtons.forEach((button) => {
            button.addEventListener('click', closeCitizenSettingsPanel);
        });
        citizenMapLayersCloseButtons.forEach((button) => {
            button.addEventListener('click', closeCitizenMapLayersPanel);
        });
        citizenMapLayersOpenButtons.forEach((button) => {
            button.addEventListener('click', function () {
                switchSection('overview');
                openCitizenMapLayersPanel();
            });
        });

        citizenOpenLogsButtons.forEach((button) => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                closeCitizenSettingsPanel();
                window.requestAnimationFrame(() => {
                    openMyLogsModal();
                });
            });
        });

        citizenAlertsOpen?.addEventListener('click', function () {
            switchSection('overview');
            window.setTimeout(() => {
                if (typeof window.openCitizenAlertsPanel === 'function') {
                    window.openCitizenAlertsPanel();
                }
            }, 20);
        });

        if (window.innerWidth <= 768) {
            window.PyroNotify?.info('Citizen portal is ready with your alerts, map, and reports.', {
                title: @json('Welcome back, '.$displayName),
                duration: 3200,
            });
        }

        const restoreCitizenInsightsSources = () => {
            citizenInsightsSources.forEach(({ node, parent, next }) => {
                if (!parent || parent.contains(node)) {
                    return;
                }
                if (next && next.parentElement === parent) {
                    parent.insertBefore(node, next);
                } else {
                    parent.appendChild(node);
                }
            });
        };

        const openCitizenInsightsPanel = () => {
            if (!citizenInsightsPanel || !citizenInsightsContent) {
                return;
            }

            switchSection('overview');
            citizenInsightsContent.innerHTML = '';
            citizenInsightsSources.forEach(({ node }) => {
                citizenInsightsContent.appendChild(node);
            });
            citizenInsightsPanel.classList.add('is-open');
            citizenInsightsPanel.setAttribute('aria-hidden', 'false');
        };

        const closeCitizenInsightsPanel = () => {
            if (!citizenInsightsPanel) {
                return;
            }

            blurFocusedChild(citizenInsightsPanel);
            citizenInsightsPanel.classList.remove('is-open');
            citizenInsightsPanel.setAttribute('aria-hidden', 'true');
            restoreCitizenInsightsSources();
        };

        window.openCitizenInsightsPanel = openCitizenInsightsPanel;
        window.closeCitizenInsightsPanel = closeCitizenInsightsPanel;

        citizenInsightsOpenButtons.forEach((button) => {
            button.addEventListener('click', function () {
                openCitizenInsightsPanel();
            });
        });

        citizenInsightsCloseButtons.forEach((button) => {
            button.addEventListener('click', closeCitizenInsightsPanel);
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                closeCitizenInsightsPanel();
                closeCitizenMapLayersPanel();
            }
        });

        // User Dropdown Toggle
        const citizenSosBtn = document.getElementById('citizenSosBtn');
        const citizenSosMenu = document.getElementById('citizenSosMenu');
        const citizenSosMenuHome = citizenSosMenu?.parentElement || null;
        const citizenSosMobileQuery = window.matchMedia('(max-width: 768px)');
        const citizenSosOpenButtons = document.querySelectorAll('[data-citizen-sos-open]');
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const myLogsModal = document.getElementById('myLogsModal');
        const myLogsContainer = document.getElementById('myLogsContainer');
        const myLogsSummary = document.getElementById('myLogsSummary');
        const refreshMyLogsBtn = document.getElementById('refreshMyLogsBtn');
        const closeMyLogsModalBtn = document.getElementById('closeMyLogsModal');

        function closeCitizenSosMenu() {
            if (!citizenSosMenu || !citizenSosBtn) {
                return;
            }

            citizenSosMenu.classList.add('hidden');
            citizenSosBtn.setAttribute('aria-expanded', 'false');
            citizenSosOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
        }

        function mountCitizenSosMenuForViewport() {
            if (!citizenSosMenu || !citizenSosMenuHome) {
                return;
            }

            if (citizenSosMobileQuery.matches) {
                if (citizenSosMenu.parentElement !== document.body) {
                    document.body.appendChild(citizenSosMenu);
                }

                return;
            }

            if (citizenSosMenu.parentElement !== citizenSosMenuHome) {
                citizenSosMenuHome.appendChild(citizenSosMenu);
            }
        }

        function openCitizenSosMenu() {
            if (!citizenSosMenu || !citizenSosBtn) {
                return;
            }

            mountCitizenSosMenuForViewport();
            citizenSosMenu.classList.remove('hidden');
            citizenSosBtn.setAttribute('aria-expanded', 'true');
            citizenSosOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
            userDropdownMenu?.classList.remove('show');
        }

        citizenSosBtn?.addEventListener('click', (e) => {
            e.stopPropagation();

            const isOpen = citizenSosMenu && !citizenSosMenu.classList.contains('hidden');
            if (isOpen) {
                closeCitizenSosMenu();
                return;
            }

            openCitizenSosMenu();
        });

        citizenSosOpenButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                citizenMobileDock?.classList.remove('is-open');

                const isOpen = citizenSosMenu && !citizenSosMenu.classList.contains('hidden');
                if (isOpen) {
                    closeCitizenSosMenu();
                } else {
                    openCitizenSosMenu();
                }
            });
        });

        if (userDropdownBtn) {
            userDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                closeCitizenSosMenu();
                userDropdownMenu.classList.toggle('show');
            });
        }

        citizenSosMenu?.querySelectorAll('[data-pyro-voice-call], [data-citizen-sos-close]').forEach((node) => {
            node.addEventListener('click', () => {
                closeCitizenSosMenu();
            });
        });

        citizenSosMobileQuery.addEventListener?.('change', () => {
            closeCitizenSosMenu();
            mountCitizenSosMenuForViewport();
        });

        document.addEventListener('click', (e) => {
            if (citizenSosMenu && citizenSosBtn && !citizenSosMenu.classList.contains('hidden')) {
                if (!citizenSosBtn.contains(e.target) && !citizenSosMenu.contains(e.target)) {
                    closeCitizenSosMenu();
                }
            }

            if (userDropdownMenu && userDropdownMenu.classList.contains('show')) {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.remove('show');
                }
            }
        });

        // Check URL hash on page load
        window.addEventListener('load', function() {
            const forcedSection = @json(session('open_section', $errors->any() ? 'profile' : null));
            const initialSection = forcedSection || getCitizenSectionFromPath() || window.initialCitizenSection || 'overview';
            switchSection(initialSection, { updateHistory: false });

            const shouldOpenIncidentModal = @json((bool) session('open_incident_modal'));
            const shouldOpenIncidentFromQuery = new URLSearchParams(window.location.search).get('report') === '1';
            if (shouldOpenIncidentModal || shouldOpenIncidentFromQuery) {
                window.setTimeout(function() {
                    document.getElementById('latitude').value = window.userCoords?.currentLatitude ?? '';
                    document.getElementById('longitude').value = window.userCoords?.currentLongitude ?? '';
                    openFireIncidentModal();

                    if (shouldOpenIncidentFromQuery) {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.delete('report');
                        window.history.replaceState(window.history.state, '', currentUrl.toString());
                    }
                }, 250);
            }
        });

        function formatLogDate(value) {
            const date = value ? new Date(value) : null;
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return 'Unknown date';
            }

            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function summarizeUserAgent(userAgent) {
            if (!userAgent) {
                return 'Unavailable';
            }

            const trimmed = String(userAgent).trim();
            return trimmed.length > 140 ? `${trimmed.slice(0, 137)}...` : trimmed;
        }

        let citizenLogsCurrentPage = 1;
        const citizenLogsPerPage = 10;

        function renderLogsPagination(meta = {}, onPageChange = 'loadMyLogs') {
            const currentPage = Number(meta?.current_page || 1);
            const lastPage = Number(meta?.last_page || 1);

            if (lastPage <= 1) {
                return '';
            }

            return `
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage <= 1 ? 'disabled' : `onclick="${onPageChange}(${currentPage - 1})"`}>
                        Previous
                    </button>
                    <span class="text-xs font-medium text-slate-500">Page ${currentPage} of ${lastPage}</span>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage >= lastPage ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage >= lastPage ? 'disabled' : `onclick="${onPageChange}(${currentPage + 1})"`}>
                        Next
                    </button>
                </div>
            `;
        }

        function renderMyLogs(logs = [], meta = {}) {
            window.citizenUserLogs = logs;

            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            const totalLogs = Number(meta?.total || logs.length || 0);
            const from = Number(meta?.from || 0);
            const to = Number(meta?.to || 0);
            myLogsSummary.textContent = totalLogs
                ? `Showing ${from}-${to} of ${totalLogs} log entries`
                : '0 log entries found';

            if (!logs.length) {
                myLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <div class="text-slate-400 text-3xl mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <p class="text-sm font-semibold text-slate-700">No logs available yet</p>
                        <p class="text-xs text-slate-500 mt-1">Your future activity will appear here.</p>
                    </div>
                `;
                return;
            }

            myLogsContainer.innerHTML = `
                <div class="space-y-3">
                    ${logs.map((log) => `
                        <div class="my-log-card rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="my-log-card__icon w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-bold text-slate-800 leading-6">${log.action || 'Unknown action'}</p>
                                        <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">#${log.user_log_id}</span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-calendar"></i></span>
                                            <span><span class="font-semibold text-slate-700">Date & Time:</span> ${formatLogDate(log.created_at)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-desktop"></i></span>
                                            <span class="break-words"><span class="font-semibold text-slate-700">Device information:</span> ${log.device_info || summarizeUserAgent(log.user_agent)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-globe"></i></span>
                                            <span><span class="font-semibold text-slate-700">IP Address:</span> ${log.ip_address || 'Unavailable'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ${renderLogsPagination(meta, 'loadMyLogs')}
            `;
        }

        async function loadMyLogs(page = 1) {
            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            citizenLogsCurrentPage = Math.max(1, Number(page || 1));
            myLogsSummary.textContent = 'Loading your activity logs...';
            myLogsContainer.innerHTML = '<div class="text-sm text-slate-500">Loading logs...</div>';

            try {
                const response = await fetch(`{{ route('user-logs.index') }}?page=${citizenLogsCurrentPage}&limit=${citizenLogsPerPage}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load logs (${response.status})`);
                }

                const payload = await response.json();
                renderMyLogs(Array.isArray(payload?.data) ? payload.data : [], payload?.meta || {});
            } catch (error) {
                console.error('Error loading user logs:', error);
                myLogsSummary.textContent = 'Unable to load your activity logs';
                myLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-red-100 bg-red-50 px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-red-700">Failed to load logs</p>
                        <p class="text-xs text-red-500 mt-1">Please try again in a moment.</p>
                    </div>
                `;
            }
        }

        function openMyLogsModal() {
            userDropdownMenu?.classList.remove('show');
            if (myLogsModal) {
                myLogsModal.classList.remove('hidden');
                myLogsModal.classList.add('flex');
                myLogsModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            loadMyLogs(1);
        }

        window.loadMyLogs = loadMyLogs;

        function closeMyLogsModal() {
            if (myLogsModal) {
                if (myLogsModal.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
                    document.activeElement.blur();
                }
                myLogsModal.classList.add('hidden');
                myLogsModal.classList.remove('flex');
                myLogsModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        }

        window.openMyLogsModal = openMyLogsModal;
        window.closeMyLogsModal = closeMyLogsModal;

        refreshMyLogsBtn?.addEventListener('click', loadMyLogs);
        closeMyLogsModalBtn?.addEventListener('click', closeMyLogsModal);
        myLogsModal?.addEventListener('click', function(e) {
            if (e.target === myLogsModal) {
                closeMyLogsModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && myLogsModal && !myLogsModal.classList.contains('hidden')) {
                closeMyLogsModal();
            }
        });

        // Modal Functions
        const citizenHazardDraft = {
            mode: 'current',
            selectedLat: null,
            selectedLng: null,
            picking: false,
            circle: null,
            marker: null,
            clickHandler: null,
        };
        const citizenFireDraft = {
            mode: 'current',
            selectedLat: null,
            selectedLng: null,
            picking: false,
            circle: null,
            marker: null,
            clickHandler: null,
        };

        function getCitizenDistanceMeters(lat1, lng1, lat2, lng2) {
            const earthRadius = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return earthRadius * c;
        }

        function clearCitizenHazardPickVisuals() {
            if (citizenHazardDraft.circle && window.usermap) {
                window.usermap.removeLayer(citizenHazardDraft.circle);
            }
            if (citizenHazardDraft.marker && window.usermap) {
                window.usermap.removeLayer(citizenHazardDraft.marker);
            }
            citizenHazardDraft.circle = null;
            citizenHazardDraft.marker = null;
        }

        function clearCitizenFirePickVisuals() {
            if (citizenFireDraft.circle && window.usermap) {
                window.usermap.removeLayer(citizenFireDraft.circle);
            }
            if (citizenFireDraft.marker && window.usermap) {
                window.usermap.removeLayer(citizenFireDraft.marker);
            }
            citizenFireDraft.circle = null;
            citizenFireDraft.marker = null;
        }

        function stopCitizenHazardMapPick() {
            if (citizenHazardDraft.clickHandler && window.usermap) {
                window.usermap.off('click', citizenHazardDraft.clickHandler);
            }
            citizenHazardDraft.clickHandler = null;
            citizenHazardDraft.picking = false;
            clearCitizenHazardPickVisuals();
        }

        function stopCitizenFireMapPick() {
            if (citizenFireDraft.clickHandler && window.usermap) {
                window.usermap.off('click', citizenFireDraft.clickHandler);
            }
            citizenFireDraft.clickHandler = null;
            citizenFireDraft.picking = false;
            clearCitizenFirePickVisuals();
        }

        function updateCitizenHazardLocationUi() {
            const statusNode = document.getElementById('hazardLocationStatus');
            const useCurrentButton = document.getElementById('hazardUseCurrentLocationButton');
            const pickOnMapButton = document.getElementById('hazardPickOnMapButton');

            if (useCurrentButton) {
                useCurrentButton.classList.toggle('bg-slate-900', citizenHazardDraft.mode === 'current');
                useCurrentButton.classList.toggle('text-white', citizenHazardDraft.mode === 'current');
                useCurrentButton.classList.toggle('border', citizenHazardDraft.mode !== 'current');
                useCurrentButton.classList.toggle('border-slate-300', citizenHazardDraft.mode !== 'current');
                useCurrentButton.classList.toggle('bg-white', citizenHazardDraft.mode !== 'current');
                useCurrentButton.classList.toggle('text-slate-700', citizenHazardDraft.mode !== 'current');
            }

            if (pickOnMapButton) {
                pickOnMapButton.classList.toggle('bg-slate-900', citizenHazardDraft.mode === 'picked');
                pickOnMapButton.classList.toggle('text-white', citizenHazardDraft.mode === 'picked');
                pickOnMapButton.classList.toggle('border', citizenHazardDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('border-slate-300', citizenHazardDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('bg-white', citizenHazardDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('text-slate-700', citizenHazardDraft.mode !== 'picked');
            }

            if (!statusNode) {
                return;
            }

            if (citizenHazardDraft.picking) {
                statusNode.textContent = 'Tap within 100m on the map.';
                return;
            }

            if (citizenHazardDraft.mode === 'picked' && citizenHazardDraft.selectedLat !== null && citizenHazardDraft.selectedLng !== null) {
                statusNode.textContent = 'Map point selected.';
                return;
            }

            statusNode.textContent = 'Current location will be used.';
        }

        function updateCitizenFireLocationUi() {
            const statusNode = document.getElementById('fireLocationStatus');
            const useCurrentButton = document.getElementById('fireUseCurrentLocationButton');
            const pickOnMapButton = document.getElementById('firePickOnMapButton');

            if (useCurrentButton) {
                useCurrentButton.classList.toggle('bg-slate-900', citizenFireDraft.mode === 'current');
                useCurrentButton.classList.toggle('text-white', citizenFireDraft.mode === 'current');
                useCurrentButton.classList.toggle('border', citizenFireDraft.mode !== 'current');
                useCurrentButton.classList.toggle('border-slate-300', citizenFireDraft.mode !== 'current');
                useCurrentButton.classList.toggle('bg-white', citizenFireDraft.mode !== 'current');
                useCurrentButton.classList.toggle('text-slate-700', citizenFireDraft.mode !== 'current');
            }

            if (pickOnMapButton) {
                pickOnMapButton.classList.toggle('bg-slate-900', citizenFireDraft.mode === 'picked');
                pickOnMapButton.classList.toggle('text-white', citizenFireDraft.mode === 'picked');
                pickOnMapButton.classList.toggle('border', citizenFireDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('border-slate-300', citizenFireDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('bg-white', citizenFireDraft.mode !== 'picked');
                pickOnMapButton.classList.toggle('text-slate-700', citizenFireDraft.mode !== 'picked');
            }

            if (!statusNode) {
                return;
            }

            if (!window.userCoords.ready || !window.userCoords.currentLatitude || !window.userCoords.currentLongitude) {
                statusNode.textContent = 'Getting current location...';
                return;
            }

            if (citizenFireDraft.picking) {
                statusNode.textContent = 'Tap within 100m on the map.';
                return;
            }

            if (citizenFireDraft.mode === 'picked' && citizenFireDraft.selectedLat !== null && citizenFireDraft.selectedLng !== null) {
                statusNode.textContent = 'Fire point selected.';
                return;
            }

            statusNode.textContent = 'Current location ready.';
        }

        function setCitizenHazardCurrentLocation() {
            if (!window.userCoords.ready || !window.userCoords.currentLatitude) {
                window.showCitizenToast?.('Getting your location. Please wait and try again.', true);
                return;
            }

            stopCitizenHazardMapPick();
            citizenHazardDraft.mode = 'current';
            citizenHazardDraft.selectedLat = Number(window.userCoords.currentLatitude);
            citizenHazardDraft.selectedLng = Number(window.userCoords.currentLongitude);
            updateCitizenHazardLocationUi();
        }

        function setCitizenFireCurrentLocation() {
            if (!window.userCoords.ready || !window.userCoords.currentLatitude || !window.userCoords.currentLongitude) {
                window.showCitizenToast?.('Getting your location. Please wait and try again.', true);
                return;
            }

            stopCitizenFireMapPick();
            citizenFireDraft.mode = 'current';
            citizenFireDraft.selectedLat = Number(window.userCoords.currentLatitude);
            citizenFireDraft.selectedLng = Number(window.userCoords.currentLongitude);
            updateCitizenFireLocationUi();
        }

        function cancelCitizenHazardReportFlow() {
            stopCitizenHazardMapPick();
            citizenHazardDraft.mode = 'current';
            citizenHazardDraft.selectedLat = window.userCoords?.ready
                ? Number(window.userCoords.currentLatitude)
                : null;
            citizenHazardDraft.selectedLng = window.userCoords?.ready
                ? Number(window.userCoords.currentLongitude)
                : null;
            updateCitizenHazardLocationUi();
            hideCitizenModal(document.getElementById('concernReportModal'));
        }

        function cancelCitizenFireReportFlow() {
            stopCitizenFireMapPick();
            citizenFireDraft.mode = 'current';
            citizenFireDraft.selectedLat = window.userCoords?.ready
                ? Number(window.userCoords.currentLatitude)
                : null;
            citizenFireDraft.selectedLng = window.userCoords?.ready
                ? Number(window.userCoords.currentLongitude)
                : null;
            updateCitizenFireLocationUi();
            hideCitizenModal(document.getElementById('fireIncidentModal'));
        }

        function startCitizenHazardMapPick() {
            if (!window.userCoords.ready || !window.userCoords.currentLatitude || !window.userCoords.currentLongitude) {
                window.showCitizenToast?.('Getting your location. Please wait and try again.', true);
                return;
            }

            citizenHazardDraft.mode = 'picked';
            citizenHazardDraft.picking = true;

            hideCitizenModal(document.getElementById('concernReportModal'));
            window.switchSection?.('overview');

            window.setTimeout(() => {
                if (!window.usermap) {
                    citizenHazardDraft.picking = false;
                    updateCitizenHazardLocationUi();
                    window.showCitizenToast?.('Map is not ready yet. Please try again.', true);
                    return;
                }

                stopCitizenHazardMapPick();
                citizenHazardDraft.mode = 'picked';
                citizenHazardDraft.picking = true;

                const originLat = Number(window.userCoords.currentLatitude);
                const originLng = Number(window.userCoords.currentLongitude);

                window.usermap.invalidateSize();
                window.usermap.setView([originLat, originLng], Math.max(window.usermap.getZoom() || 15, 17), { animate: true });

                citizenHazardDraft.circle = L.circle([originLat, originLng], {
                    radius: 100,
                    color: '#16a34a',
                    fillColor: '#22c55e',
                    fillOpacity: 0.16,
                    weight: 2,
                }).addTo(window.usermap);

                if (citizenHazardDraft.selectedLat !== null && citizenHazardDraft.selectedLng !== null) {
                    citizenHazardDraft.marker = L.marker([citizenHazardDraft.selectedLat, citizenHazardDraft.selectedLng]).addTo(window.usermap);
                }

                citizenHazardDraft.clickHandler = function(event) {
                    const targetLat = Number(event.latlng.lat);
                    const targetLng = Number(event.latlng.lng);
                    const distance = getCitizenDistanceMeters(originLat, originLng, targetLat, targetLng);

                    if (distance > 100) {
                        window.showCitizenToast?.('Please pick a point within 100 meters of your current location.', true);
                        return;
                    }

                    citizenHazardDraft.selectedLat = targetLat;
                    citizenHazardDraft.selectedLng = targetLng;
                    citizenHazardDraft.picking = false;

                    if (citizenHazardDraft.marker && window.usermap) {
                        window.usermap.removeLayer(citizenHazardDraft.marker);
                    }

                    citizenHazardDraft.marker = L.marker([targetLat, targetLng]).addTo(window.usermap);

                    if (citizenHazardDraft.clickHandler && window.usermap) {
                        window.usermap.off('click', citizenHazardDraft.clickHandler);
                    }
                    citizenHazardDraft.clickHandler = null;

                    updateCitizenHazardLocationUi();
                    openConcernReportModal();
                    window.showCitizenToast?.('Safety concern point selected. Tap Submit Risk / Hazard to continue.', false, 3800);
                };

                window.usermap.on('click', citizenHazardDraft.clickHandler);
                updateCitizenHazardLocationUi();
                window.showCitizenToast?.('Tap the map within 100 meters of your current location to place the safety concern point.', false, 4200);
            }, 200);
        }

        function startCitizenFireMapPick() {
            if (!window.userCoords.ready || !window.userCoords.currentLatitude || !window.userCoords.currentLongitude) {
                window.showCitizenToast?.('Getting your location. Please wait and try again.', true);
                return;
            }

            citizenFireDraft.mode = 'picked';
            citizenFireDraft.picking = true;

            hideCitizenModal(document.getElementById('fireIncidentModal'));
            window.switchSection?.('overview');

            window.setTimeout(() => {
                if (!window.usermap) {
                    citizenFireDraft.picking = false;
                    updateCitizenFireLocationUi();
                    window.showCitizenToast?.('Map is not ready yet. Please try again.', true);
                    return;
                }

                stopCitizenFireMapPick();
                citizenFireDraft.mode = 'picked';
                citizenFireDraft.picking = true;

                const originLat = Number(window.userCoords.currentLatitude);
                const originLng = Number(window.userCoords.currentLongitude);

                window.usermap.invalidateSize();
                window.usermap.setView([originLat, originLng], Math.max(window.usermap.getZoom() || 15, 17), { animate: true });

                citizenFireDraft.circle = L.circle([originLat, originLng], {
                    radius: 100,
                    color: '#16a34a',
                    fillColor: '#22c55e',
                    fillOpacity: 0.14,
                    weight: 2,
                }).addTo(window.usermap);

                if (citizenFireDraft.selectedLat !== null && citizenFireDraft.selectedLng !== null) {
                    citizenFireDraft.marker = L.marker([citizenFireDraft.selectedLat, citizenFireDraft.selectedLng]).addTo(window.usermap);
                }

                citizenFireDraft.clickHandler = function(event) {
                    const targetLat = Number(event.latlng.lat);
                    const targetLng = Number(event.latlng.lng);
                    const distance = getCitizenDistanceMeters(originLat, originLng, targetLat, targetLng);

                    if (distance > 100) {
                        window.showCitizenToast?.('Please pick the fire point within 100 meters of your current location.', true);
                        return;
                    }

                    citizenFireDraft.selectedLat = targetLat;
                    citizenFireDraft.selectedLng = targetLng;
                    citizenFireDraft.picking = false;

                    if (citizenFireDraft.marker && window.usermap) {
                        window.usermap.removeLayer(citizenFireDraft.marker);
                    }

                    citizenFireDraft.marker = L.marker([targetLat, targetLng]).addTo(window.usermap);

                    if (citizenFireDraft.clickHandler && window.usermap) {
                        window.usermap.off('click', citizenFireDraft.clickHandler);
                    }
                    citizenFireDraft.clickHandler = null;

                    updateCitizenFireLocationUi();
                    openFireIncidentModal();
                    window.showCitizenToast?.('Fire incident point selected. Tap Report Fire Incident to continue.', false, 3800);
                };

                window.usermap.on('click', citizenFireDraft.clickHandler);
                updateCitizenFireLocationUi();
                window.showCitizenToast?.('Tap the map within 100 meters of your current location to place the fire incident point.', false, 4200);
            }, 200);
        }

        function showCitizenModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        }

        function hideCitizenModal(modal) {
            if (!modal) {
                return;
            }

            if (modal.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }

        function openFireIncidentModal() {
            const fireModal = document.getElementById('fireIncidentModal');

            if (window.userCoords?.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude && citizenFireDraft.selectedLat === null && citizenFireDraft.selectedLng === null) {
                citizenFireDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenFireDraft.selectedLng = Number(window.userCoords.currentLongitude);
            } else {
                window.requestCitizenLocationNow?.({ silent: true })
                    .then(() => {
                        if (citizenFireDraft.selectedLat === null && citizenFireDraft.selectedLng === null && window.userCoords?.ready) {
                            citizenFireDraft.selectedLat = Number(window.userCoords.currentLatitude);
                            citizenFireDraft.selectedLng = Number(window.userCoords.currentLongitude);
                        }
                        updateCitizenFireLocationUi();
                    })
                    .catch(() => updateCitizenFireLocationUi());
            }

            updateCitizenFireLocationUi();
            showCitizenModal(fireModal);
        }

        function openConcernReportModal() {
            if (window.userCoords?.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude && citizenHazardDraft.selectedLat === null && citizenHazardDraft.selectedLng === null) {
                citizenHazardDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenHazardDraft.selectedLng = Number(window.userCoords.currentLongitude);
            } else {
                window.requestCitizenLocationNow?.({ silent: true })
                    .then(() => {
                        if (citizenHazardDraft.selectedLat === null && citizenHazardDraft.selectedLng === null && window.userCoords?.ready) {
                            citizenHazardDraft.selectedLat = Number(window.userCoords.currentLatitude);
                            citizenHazardDraft.selectedLng = Number(window.userCoords.currentLongitude);
                        }
                        updateCitizenHazardLocationUi();
                    })
                    .catch(() => updateCitizenHazardLocationUi());
            }
            updateCitizenHazardLocationUi();
            showCitizenModal(document.getElementById('concernReportModal'));
        }

        function setCitizenConcernType(type) {
            const select = document.getElementById('concernTypeSelect');
            const detailInput = document.getElementById('concernConditionDetail');
            const selectedType = type || 'unsure';

            if (select) {
                select.value = selectedType;
            }

            if (detailInput) {
                detailInput.value = '';
            }

            document.querySelectorAll('.citizen-concern-chip').forEach((chip) => {
                const active = chip.dataset.concernType === selectedType;
                chip.classList.toggle('border-amber-200', active);
                chip.classList.toggle('bg-amber-50', active);
                chip.classList.toggle('text-amber-800', active);
                chip.classList.toggle('border-slate-200', !active);
                chip.classList.toggle('bg-white', !active);
                chip.classList.toggle('text-slate-700', !active);
                chip.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            document.querySelectorAll('.citizen-concern-detail-chip').forEach((chip) => {
                const visible = chip.dataset.conditionFor === selectedType;
                chip.classList.toggle('hidden', !visible);
                chip.classList.remove('border-amber-300', 'bg-amber-50', 'text-amber-800');
                chip.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
                chip.setAttribute('aria-pressed', 'false');
            });
        }

        function setCitizenConcernDetail(detail) {
            const detailInput = document.getElementById('concernConditionDetail');

            if (detailInput) {
                detailInput.value = detail || '';
            }

            document.querySelectorAll('.citizen-concern-detail-chip:not(.hidden)').forEach((chip) => {
                const active = chip.dataset.conditionDetail === detail;
                chip.classList.toggle('border-amber-300', active);
                chip.classList.toggle('bg-amber-50', active);
                chip.classList.toggle('text-amber-800', active);
                chip.classList.toggle('border-slate-200', !active);
                chip.classList.toggle('bg-slate-50', !active);
                chip.classList.toggle('text-slate-600', !active);
                chip.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        window.openFireIncidentModal = openFireIncidentModal;
        window.openConcernReportModal = openConcernReportModal;
        window.showCitizenModal = showCitizenModal;
        window.hideCitizenModal = hideCitizenModal;

        document.querySelectorAll('[data-fire-modal-close], #closeFireModal').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                cancelCitizenFireReportFlow();
            });
        });

        document.querySelectorAll('[data-concern-modal-close], #closeConcernModal').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                cancelCitizenHazardReportFlow();
            });
        });

        document.getElementById('hazardUseCurrentLocationButton')?.addEventListener('click', function() {
            setCitizenHazardCurrentLocation();
        });

        document.getElementById('hazardPickOnMapButton')?.addEventListener('click', function() {
            startCitizenHazardMapPick();
        });

        document.getElementById('concernPhotosInput')?.addEventListener('change', function() {
            refreshConcernPhotoUploadState();
        });

        document.querySelectorAll('.citizen-concern-chip').forEach((chip) => {
            chip.addEventListener('click', function() {
                setCitizenConcernType(this.dataset.concernType || 'unsure');
            });
        });

        document.querySelectorAll('.citizen-concern-detail-chip').forEach((chip) => {
            chip.addEventListener('click', function() {
                const nextDetail = this.getAttribute('aria-pressed') === 'true'
                    ? ''
                    : (this.dataset.conditionDetail || '');
                setCitizenConcernDetail(nextDetail);
            });
        });

        setCitizenConcernType(document.getElementById('concernTypeSelect')?.value || 'unsure');
        refreshConcernPhotoUploadState();

        document.getElementById('fireUseCurrentLocationButton')?.addEventListener('click', function() {
            setCitizenFireCurrentLocation();
        });

        document.getElementById('firePickOnMapButton')?.addEventListener('click', function() {
            startCitizenFireMapPick();
        });


// document.getElementById('saveReport').addEventListener('click', function(e) {
//     e.preventDefault();

//     const image = document.getElementById('myfile');
//     const proof = document.getElementById('proof');
//     const description = document.getElementById('description').value;
//     // const type = document.getElementById('incidentType').value;
//     // const d = document.getElementById('accid').value;
//         const select = document.getElementById('incidentType');
//         const value = select.value;
        
//     // First check if user is still authenticated
//     fetch('/check-auth', {
//         method: 'GET',
//         headers: {
//             'X-Requested-With': 'XMLHttpRequest',
//             'Accept': 'application/json'
//         }
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (!data.authenticated) {
//             // User is not authenticated, redirect to login
//             alert('Your session has expired. Please login again.');
//             window.location.replace('{{ route("auth.login") }}');
//             return;
//         }
        
//         // If authenticated, proceed with report submission
//         // const latitude = document.getElementById('latitude').value;
//         // const longitude = document.getElementById('longitude').value;
//         //save muna marker
//                if(file){
//         console.log("image2: ", file);
            
//         }else{
//             console.log("Error");
//         }
//         // saving in marker table, tapos sa incident, tapos image if not null
//         if(image.files.length === 0){
//             image = 0;
//         }
//                 console.log(value);
//         console.log("image: ", image);
//         const file = image.files[0];
//         const reporter = document.getElementById('reported_by').value;
//         console.log("try to save"
//         );  
//         window.saveIncident(description,window.userCoords.currentLatitude,window.userCoords.currentLongitude,value,image, reporter);
       


 
//         // window.userLocation(window.userCoords.currentLatitude, window.userCoords.currentLongitude,d,value, file);

//         // window.userLocation(latitude, longitude,d,value, file);
//         // window.saveIncident(d);        // console.log(latitude, longitude,d);
       

   
//         // alert('Incident reported successfully!');
//         // document.getElementById('incidentModal').style.display = 'none'; 
//     })
//     .catch(() => {
//         window.location.replace('{{ route("auth.login") }}');
//     });
// });
function addPendingCitizenOfflineReport(reportKind, payload) {
    const queuedAt = new Date().toISOString();
    const pendingStatus = 'Pending sync';

    if (reportKind === 'fire') {
        const pendingIncident = {
            incident_id: `offline-fire-${Date.now()}`,
            incident_type: 'Fire Incident',
            incident_status: pendingStatus,
            created_at: queuedAt,
            created_at_human: pendingStatus,
            marker_id: null,
            lat: payload.lat,
            lng: payload.lng,
            distance_meters: 0
        };

        window.myReportedIncidents = Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [];
        window.myReportedIncidents.unshift(pendingIncident);
        window.citizenCombinedReports = Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : [];
        window.citizenCombinedReports.unshift({
            ...pendingIncident,
            report_type: 'Fire Incident'
        });
        window.renderCitizenWeeklyActivity();
        window.refreshCitizenAreasOfConcernReports?.(window.citizenCombinedReports);
        return;
    }

    const pendingConcern = {
        id: `offline-concern-${Date.now()}`,
        report_type: 'Risk/Hazard Factor',
        category: payload.concern_type || 'unsure',
        status: pendingStatus,
        verification_status: pendingStatus,
        created_at: queuedAt,
        created_at_human: pendingStatus,
        lat: payload.lat,
        lng: payload.lng
    };

    window.citizenCombinedReports = Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : [];
    window.citizenCombinedReports.unshift(pendingConcern);
    window.citizenRiskMapReports = Array.isArray(window.citizenRiskMapReports) ? window.citizenRiskMapReports : [];
    window.citizenRiskMapReports.unshift(pendingConcern);
    window.refreshCitizenAreasOfConcernReports?.(window.citizenCombinedReports);
    window.refreshCitizenRiskConcernMap?.(window.citizenRiskMapReports);
    window.loadMarkers?.();
}

function addOptimisticCitizenFireMarker(lat, lng, reporter) {
    const numericLat = Number(lat);
    const numericLng = Number(lng);

    if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) {
        return null;
    }

    const optimisticId = `optimistic-fire-${Date.now()}`;
    const createdAt = new Date().toISOString();
    const markerRecord = {
        marker_id: optimisticId,
        markerCategory_id: 2,
        category: 'fire',
        title: 'Citizen Fire Emergency',
        description: 'Fire report is being submitted.',
        status: 'reported',
        lat: numericLat,
        lng: numericLng,
        created_at: createdAt,
        is_optimistic: true
    };
    const incidentRecord = {
        incident_id: optimisticId,
        incident_type_id: 1,
        incident_type: 'Fire Incident',
        incident_type_name: 'Fire Incident',
        incident_status_id: 1,
        incident_status: 'Reported',
        status: 'reported',
        reported_by: reporter,
        marker_id: optimisticId,
        marker: markerRecord,
        lat: numericLat,
        lng: numericLng,
        created_at: createdAt,
        created_at_human: 'Submitting now',
        is_optimistic: true
    };

    window.mapData = window.mapData || {};
    window.mapData.usermark = Array.isArray(window.mapData.usermark) ? window.mapData.usermark : [];
    window.incidentsData = Array.isArray(window.incidentsData) ? window.incidentsData : [];
    window.myReportedIncidents = Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [];

    window.mapData.usermark = window.mapData.usermark.filter((marker) => marker?.marker_id !== optimisticId);
    window.incidentsData = window.incidentsData.filter((incident) => incident?.incident_id !== optimisticId);
    window.myReportedIncidents = window.myReportedIncidents.filter((incident) => incident?.incident_id !== optimisticId);

    window.mapData.usermark.unshift(markerRecord);
    window.incidentsData.unshift(incidentRecord);
    window.myReportedIncidents.unshift(incidentRecord);
    window.refreshCitizenMapSearchIndex?.();

    if (window.usermap && typeof window.loadMarkers === 'function') {
        window.loadMarkers();
    }

    return optimisticId;
}

function removeOptimisticCitizenFireMarker(optimisticId) {
    if (!optimisticId) {
        return;
    }

    if (Array.isArray(window.mapData?.usermark)) {
        window.mapData.usermark = window.mapData.usermark.filter((marker) => marker?.marker_id !== optimisticId);
    }

    if (Array.isArray(window.incidentsData)) {
        window.incidentsData = window.incidentsData.filter((incident) => incident?.incident_id !== optimisticId && incident?.marker_id !== optimisticId);
    }

    if (Array.isArray(window.myReportedIncidents)) {
        window.myReportedIncidents = window.myReportedIncidents.filter((incident) => incident?.incident_id !== optimisticId && incident?.marker_id !== optimisticId);
    }

    window.refreshCitizenMapSearchIndex?.();

    if (window.usermap && typeof window.loadMarkers === 'function') {
        window.loadMarkers();
    }
}

function getCitizenConcernOptimisticMeta(concernType = 'unsure') {
    const normalized = String(concernType || 'unsure');
    const hazardTypes = ['electrical_issue', 'unsafe_cooking_lpg'];
    const labels = {
        unsure: 'General safety concern',
        electrical_issue: 'Possible electrical hazard',
        unsafe_cooking_lpg: 'Possible LPG or cooking hazard',
        blocked_hydrant_access: 'Possible hydrant or access risk',
        combustible_clutter: 'Possible combustible clutter',
        dense_fire_prone_area: 'Possible fire-prone area'
    };

    return {
        category: hazardTypes.includes(normalized) ? 'hazard' : 'risk',
        label: labels[normalized] || labels.unsure
    };
}

function addOptimisticCitizenConcernMarker(lat, lng, concernType, concernDetail = '', notes = '') {
    const numericLat = Number(lat);
    const numericLng = Number(lng);

    if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) {
        return null;
    }

    const optimisticId = `optimistic-concern-${Date.now()}`;
    const createdAt = new Date().toISOString();
    const meta = getCitizenConcernOptimisticMeta(concernType);
    const readableDetail = concernDetail
        ? String(concernDetail).replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
        : '';
    const description = notes || readableDetail || 'Submitted for BFP verification.';
    const pendingConcern = {
        id: optimisticId,
        risk_area_id: optimisticId,
        marker_id: optimisticId,
        report_kind: meta.category,
        entry_type: meta.category,
        category: meta.category,
        type: meta.category === 'hazard' ? 'Hazard' : 'Risk',
        report_type: meta.category,
        report_type_label: 'Risk/Hazard Factor',
        type_label: meta.category === 'hazard' ? 'Hazard Factor' : 'Risk Factor',
        category_label: meta.label,
        title: 'Citizen Safety Concern',
        description,
        condition_label: readableDetail || meta.label,
        status: 'pending',
        status_label: 'Pending',
        review_status: 'pending',
        review_status_label: 'Pending Verification',
        lat: numericLat,
        lng: numericLng,
        marker_category: meta.category,
        hazard_type: meta.category === 'hazard' ? concernType : 'none',
        risk_type: meta.category === 'risk' ? concernType : null,
        created_at: createdAt,
        created_at_human: 'Submitting now',
        is_optimistic: true
    };

    window.citizenCombinedReports = Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : [];
    window.citizenRiskMapReports = Array.isArray(window.citizenRiskMapReports) ? window.citizenRiskMapReports : [];
    window.citizenCombinedReports = window.citizenCombinedReports.filter((report) => report?.id !== optimisticId && report?.risk_area_id !== optimisticId);
    window.citizenRiskMapReports = window.citizenRiskMapReports.filter((report) => report?.id !== optimisticId && report?.risk_area_id !== optimisticId);
    window.citizenCombinedReports.unshift(pendingConcern);
    window.citizenRiskMapReports.unshift(pendingConcern);
    window.refreshCitizenAreasOfConcernReports?.(window.citizenCombinedReports);
    window.refreshCitizenRiskConcernMap?.(window.citizenRiskMapReports);

    if (window.usermap && typeof window.loadMarkers === 'function') {
        window.loadMarkers();
    }

    return optimisticId;
}

function removeOptimisticCitizenConcernMarker(optimisticId) {
    if (!optimisticId) {
        return;
    }

    const isNotOptimisticConcern = (report) => {
        if (!report?.is_optimistic) {
            return true;
        }

        return ![report.id, report.risk_area_id, report.marker_id].some((value) => String(value) === String(optimisticId));
    };

    if (Array.isArray(window.citizenCombinedReports)) {
        window.citizenCombinedReports = window.citizenCombinedReports.filter(isNotOptimisticConcern);
    }

    if (Array.isArray(window.citizenRiskMapReports)) {
        window.citizenRiskMapReports = window.citizenRiskMapReports.filter(isNotOptimisticConcern);
    }

    window.refreshCitizenAreasOfConcernReports?.(window.citizenCombinedReports || []);
    window.refreshCitizenRiskConcernMap?.(window.citizenRiskMapReports || []);

    if (window.usermap && typeof window.loadMarkers === 'function') {
        window.loadMarkers();
    }
}

function formatCitizenFileSize(bytes = 0) {
    const normalizedBytes = Number(bytes) || 0;

    if (normalizedBytes >= 1024 * 1024) {
        return `${(normalizedBytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    if (normalizedBytes >= 1024) {
        return `${Math.round(normalizedBytes / 1024)} KB`;
    }

    return `${normalizedBytes} B`;
}

function setConcernPhotoUploadStatus(message, tone = 'neutral') {
    const statusNode = document.getElementById('concernPhotoUploadStatus');
    if (!statusNode) {
        return;
    }

    statusNode.textContent = message;
    statusNode.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-amber-200', 'bg-amber-50', 'text-amber-700', 'border-red-200', 'bg-red-50', 'text-red-700');

    if (tone === 'success') {
        statusNode.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
        return;
    }

    if (tone === 'loading') {
        statusNode.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
        return;
    }

    if (tone === 'error') {
        statusNode.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
        return;
    }

    statusNode.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
}

function setCitizenConcernSubmitAvailability(isEnabled) {
    const submitButton = document.querySelector('.js-citizen-report-action[data-report-kind="concern"]');
    if (!submitButton) {
        return;
    }

    submitButton.disabled = !isEnabled;
    submitButton.style.opacity = isEnabled ? '' : '0.6';
    submitButton.style.pointerEvents = isEnabled ? '' : 'none';
    submitButton.setAttribute('aria-disabled', isEnabled ? 'false' : 'true');
}

function evaluateConcernPhotoSelection() {
    const maxConcernPhotoCount = 3;
    const maxConcernPhotoTotalBytes = 5 * 1024 * 1024;
    const concernPhotosInput = document.getElementById('concernPhotosInput');
    const concernFiles = Array.from(concernPhotosInput?.files || []);

    if (concernFiles.length === 0) {
        return {
            isValid: true,
            files: concernFiles,
            totalBytes: 0,
            message: 'No photos selected yet. You can submit without photos, or add up to 3 images.',
            tone: 'neutral',
        };
    }

    if (concernFiles.length > maxConcernPhotoCount) {
        return {
            isValid: false,
            files: concernFiles,
            totalBytes: 0,
            message: 'Too many photos selected. Upload up to 3 images only.',
            tone: 'error',
        };
    }

    const totalBytes = concernFiles.reduce((sum, file) => sum + (Number(file?.size) || 0), 0);

    if (totalBytes > maxConcernPhotoTotalBytes) {
        return {
            isValid: false,
            files: concernFiles,
            totalBytes,
            message: `Selected photos total ${formatCitizenFileSize(totalBytes)}. Keep the combined upload within 5.00 MB.`,
            tone: 'error',
        };
    }

    return {
        isValid: true,
        files: concernFiles,
        totalBytes,
        message: `${concernFiles.length} photo(s) ready to upload • ${formatCitizenFileSize(totalBytes)} total`,
        tone: 'success',
    };
}

function refreshConcernPhotoUploadState() {
    setConcernPhotoUploadStatus('Checking selected photos...', 'loading');

    window.setTimeout(() => {
        const result = evaluateConcernPhotoSelection();
        setConcernPhotoUploadStatus(result.message, result.tone);
        setCitizenConcernSubmitAvailability(result.isValid);
    }, 60);
}

async function submitCitizenQuickReport(reportKind) {
    const description = '';
    const incidentTypeId = 1;
    const reporter = document.getElementById('reported_by').value;
    const concernType = document.getElementById('concernTypeSelect')?.value || 'unsure';
    const concernDetail = document.getElementById('concernConditionDetail')?.value || '';
    const concernNotes = document.getElementById('concernNotesInput')?.value?.trim() || '';
    const concernPhotosInput = document.getElementById('concernPhotosInput');
    const loadingOverlay = document.getElementById('incidentReportLoadingOverlay');
    const loadingTitle = document.getElementById('incidentReportLoadingTitle');
    const loadingMessage = document.getElementById('incidentReportLoadingMessage');
    const showIncidentToast = window.showCitizenToast;
    const actionButtons = Array.from(document.querySelectorAll('.js-citizen-report-action'));
    const actionButton = actionButtons.find((button) => button.dataset.reportKind === reportKind) || null;
    const originalButtonLabels = actionButtons.map((button) => ({
        button,
        text: button.textContent
    }));
    const reportLabels = {
        fire: 'fire report',
        concern: 'safety concern report',
    };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (typeof window.PyroLocation?.getReliableLocationFix !== 'function') {
        showIncidentToast?.('Accurate location service is still loading. Please try again.', true);
        return;
    }

    showIncidentToast?.('Getting a fresh, accurate GPS fix...', false, 3200);
    let freshLocationFix;
    try {
        freshLocationFix = await window.PyroLocation.getReliableLocationFix({ acceptBestAvailable: true });
        window.updateUserLocation?.(window.currentUserId, freshLocationFix.lat, freshLocationFix.lng, {
            accuracy: freshLocationFix.accuracy,
            timestamp: freshLocationFix.timestamp,
        });
        window.userCoords.currentLatitude = freshLocationFix.lat;
        window.userCoords.currentLongitude = freshLocationFix.lng;
        window.userCoords.accuracy = freshLocationFix.accuracy;
        window.userCoords.timestamp = freshLocationFix.timestamp;
        window.userCoords.ready = true;
        showIncidentToast?.(
            freshLocationFix.accuracyWarning
                ? `GPS is weak (±${Math.round(freshLocationFix.accuracy)} m). Your report will still be sent with this location.`
                : `GPS ready within ±${Math.round(freshLocationFix.accuracy)} m.`,
            false,
            freshLocationFix.accuracyWarning ? 4800 : 2600
        );
    } catch (error) {
        showIncidentToast?.(error.message || 'Unable to get an accurate current location.', true, 5200);
        return;
    }

    // Check if location is ready
    if (!window.userCoords.ready || !window.userCoords.currentLatitude) {
        showIncidentToast?.('Getting your location. Please wait and try again.', true);
        return;
    }

    if (reportKind === 'fire' && citizenFireDraft.mode === 'picked' && (citizenFireDraft.selectedLat === null || citizenFireDraft.selectedLng === null)) {
        startCitizenFireMapPick();
        return;
    }

    if (reportKind === 'concern' && citizenHazardDraft.mode === 'picked' && (citizenHazardDraft.selectedLat === null || citizenHazardDraft.selectedLng === null)) {
        startCitizenHazardMapPick();
        return;
    }

    const concernPhotoState = reportKind === 'concern'
        ? evaluateConcernPhotoSelection()
        : { isValid: true, files: [] };

    if (reportKind === 'concern' && !concernPhotoState.isValid) {
        setConcernPhotoUploadStatus(concernPhotoState.message, concernPhotoState.tone);
        setCitizenConcernSubmitAvailability(false);
        showIncidentToast?.(concernPhotoState.message, true);
        return;
    }

    const fireLat = citizenFireDraft.mode === 'picked' && citizenFireDraft.selectedLat !== null
        ? Number(citizenFireDraft.selectedLat)
        : Number(window.userCoords.currentLatitude);
    const fireLng = citizenFireDraft.mode === 'picked' && citizenFireDraft.selectedLng !== null
        ? Number(citizenFireDraft.selectedLng)
        : Number(window.userCoords.currentLongitude);
    const concernLat = reportKind === 'concern' && citizenHazardDraft.mode === 'picked'
        ? Number(citizenHazardDraft.selectedLat)
        : Number(window.userCoords.currentLatitude);
    const concernLng = reportKind === 'concern' && citizenHazardDraft.mode === 'picked'
        ? Number(citizenHazardDraft.selectedLng)
        : Number(window.userCoords.currentLongitude);
    let optimisticFireMarkerId = null;
    let optimisticConcernMarkerId = null;

    if (window.pyroPwa?.isOffline?.()) {
        if (reportKind === 'concern' && concernPhotoState.files.length > 0) {
            showIncidentToast?.('Photo uploads for risk or hazard reports require an internet connection.', true, 4200);
            return;
        }

        const firePayload = {
            lat: fireLat,
            lng: fireLng,
            title: 'Citizen Fire Emergency',
            description: description || 'Citizen-submitted fire emergency.',
            status: 'reported',
            reported_by: reporter,
            alarmdatetime: new Date().toISOString(),
            ...(citizenFireDraft.mode === 'picked' ? {} : {
                location_accuracy: freshLocationFix.accuracy,
                location_captured_at: new Date(freshLocationFix.timestamp).toISOString(),
            }),
        };
        const concernPayload = {
            report_kind: reportKind,
            concern_type: concernType,
            condition_detail: concernDetail,
            notes: concernNotes,
            lat: concernLat,
            lng: concernLng,
            user_lat: Number(window.userCoords.currentLatitude),
            user_lng: Number(window.userCoords.currentLongitude)
        };

        const queuePayload = reportKind === 'fire'
            ? {
                url: '/citizen/incident',
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(firePayload),
                type: 'fire-report',
                meta: { reportKind: 'fire' }
            }
            : {
                url: '{{ route('citizen.risk-reports.store') }}',
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(concernPayload),
                type: 'concern-report',
                meta: { reportKind: 'concern' }
            };

        window.pyroPwa?.enqueueOfflineReport?.(queuePayload);
        addPendingCitizenOfflineReport(reportKind, reportKind === 'fire' ? firePayload : concernPayload);
        if (reportKind === 'fire') {
            optimisticFireMarkerId = addOptimisticCitizenFireMarker(fireLat, fireLng, reporter);
        }
        hideCitizenModal(document.getElementById(reportKind === 'fire' ? 'fireIncidentModal' : 'concernReportModal'));
        showIncidentToast?.(`${reportLabels[reportKind] || 'Report'} saved offline. Pending sync.`, false, 4200);
        return;
    }

    // Close modal early for better UX
    hideCitizenModal(document.getElementById(reportKind === 'fire' ? 'fireIncidentModal' : 'concernReportModal'));

    // Show loading state
    actionButtons.forEach((button) => {
        button.disabled = true;
    });
    if (actionButton) {
        actionButton.textContent = reportKind === 'fire' ? 'Sending...' : 'Submitting...';
    }
    if (loadingTitle) loadingTitle.textContent = reportKind === 'fire' ? 'Sending fire report' : `Submitting ${reportLabels[reportKind] || 'report'}`;
    if (loadingMessage) loadingMessage.textContent = reportKind === 'fire'
        ? 'Saving your fire report now...'
        : (concernPhotoState.files.length > 0
            ? `Uploading ${concernPhotoState.files.length} photo(s) and saving your safety concern...`
            : 'Saving your safety concern...');
    loadingOverlay?.classList.remove('hidden');
    loadingOverlay?.classList.add('flex');

    if (reportKind === 'fire') {
        optimisticFireMarkerId = addOptimisticCitizenFireMarker(fireLat, fireLng, reporter);
        showIncidentToast?.('Fire report received. Marker placed while we submit it.', false, 2600);
    } else if (reportKind === 'concern') {
        optimisticConcernMarkerId = addOptimisticCitizenConcernMarker(concernLat, concernLng, concernType, concernDetail, concernNotes);
        showIncidentToast?.('Safety concern pinned. Sending for verification...', false, 2600);
    }

    try {
        let success = false;
        let successMessage = '';

        if (reportKind === 'fire') {
            success = await window.saveIncident(
                description,
                fireLat,
                fireLng,
                incidentTypeId,
                null,
                reporter,
                citizenFireDraft.mode === 'picked' ? {} : freshLocationFix
            );
            successMessage = window.latestIncidentSubmitMessage || 'Fire incident reported successfully.';
        } else {
            const formData = new FormData();
            formData.append('report_kind', reportKind);
            formData.append('concern_type', concernType);
            formData.append('condition_detail', concernDetail);
            formData.append('notes', concernNotes);
            formData.append('lat', String(concernLat));
            formData.append('lng', String(concernLng));
            formData.append('user_lat', String(Number(window.userCoords.currentLatitude)));
            formData.append('user_lng', String(Number(window.userCoords.currentLongitude)));
            concernPhotoState.files.forEach((file) => {
                formData.append('photos[]', file);
            });

            const response = await fetch('{{ route('citizen.risk-reports.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload?.message || `Failed to submit ${reportKind} report.`);
            }

            success = Boolean(payload?.success);
            successMessage = payload?.message || `${reportKind.charAt(0).toUpperCase() + reportKind.slice(1)} report submitted successfully.`;
            removeOptimisticCitizenConcernMarker(optimisticConcernMarkerId);
            window.citizenCombinedReports = Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : [];
            if (payload?.data?.report) {
                window.citizenCombinedReports.unshift(payload.data.report);
            }
            window.citizenRiskMapReports = Array.isArray(window.citizenRiskMapReports) ? window.citizenRiskMapReports : [];
            if (payload?.data?.report) {
                window.citizenRiskMapReports.unshift(payload.data.report);
            }
            if (typeof window.refreshCitizenAreasOfConcernReports === 'function') {
                window.refreshCitizenAreasOfConcernReports(window.citizenCombinedReports);
            }
            if (typeof window.refreshCitizenRiskConcernMap === 'function') {
                window.refreshCitizenRiskConcernMap(window.citizenRiskMapReports);
            }
            if (typeof window.refreshCitizenMapData === 'function') {
                Promise.resolve(window.refreshCitizenMapData()).catch((refreshError) => {
                    console.error('Citizen marker refresh failed after risk/hazard report submit:', refreshError);
                });
            } else if (window.usermap && typeof window.loadMarkers === 'function') {
                window.loadMarkers();
            }
        }

        if (success) {
            if (reportKind === 'fire') {
                document.getElementById('fireIncidentForm')?.reset();
                citizenFireDraft.mode = 'current';
                citizenFireDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenFireDraft.selectedLng = Number(window.userCoords.currentLongitude);
                stopCitizenFireMapPick();
                updateCitizenFireLocationUi();
            } else {
                document.getElementById('concernIncidentForm')?.reset();
                setCitizenConcernType('unsure');
                setCitizenConcernDetail('');
                refreshConcernPhotoUploadState();
            }
            if (reportKind === 'concern') {
                citizenHazardDraft.mode = 'current';
                citizenHazardDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenHazardDraft.selectedLng = Number(window.userCoords.currentLongitude);
                stopCitizenHazardMapPick();
                updateCitizenHazardLocationUi();
            }
            showIncidentToast(successMessage);
            window.latestIncidentSubmitMessage = null;
            if (reportKind === 'fire' && typeof window.refreshCitizenMarkersIfChanged === 'function') {
                window.refreshCitizenMarkersIfChanged(true).catch((refreshError) => {
                    console.error('Citizen marker refresh failed after fire report submit:', refreshError);
                });
            }
            if (typeof window.refreshCitizenOverviewData === 'function') {
                window.refreshCitizenOverviewData().catch((refreshError) => {
                    console.error('Citizen dashboard refresh failed after report submit:', refreshError);
                });
            }
        }
        
    } catch (error) {
        console.error('Error:', error);
        if (reportKind === 'fire') {
            const stillProcessing = String(error?.message || '').toLowerCase().includes('still processing');
            if (!stillProcessing) {
                removeOptimisticCitizenFireMarker(optimisticFireMarkerId);
            } else if (typeof window.refreshCitizenMarkersIfChanged === 'function') {
                window.setTimeout(() => {
                    window.refreshCitizenMarkersIfChanged(true).catch((refreshError) => {
                        console.error('Delayed citizen marker refresh failed after fire report timeout:', refreshError);
                    });
                }, 3500);
            }
        } else if (reportKind === 'concern') {
            removeOptimisticCitizenConcernMarker(optimisticConcernMarkerId);
        }
        showIncidentToast(`Failed to submit ${reportLabels[reportKind] || 'report'}: ${error.message}`, true);
    } finally {
        originalButtonLabels.forEach(({ button, text }) => {
            button.textContent = text;
            button.disabled = false;
        });
        loadingOverlay?.classList.add('hidden');
        loadingOverlay?.classList.remove('flex');
    }
}

document.querySelectorAll('.js-citizen-report-action').forEach((button) => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        submitCitizenQuickReport(this.dataset.reportKind || 'fire');
    });
});

window.updateIncidentReportLoading = function(stage, detail = '') {
    const loadingTitle = document.getElementById('incidentReportLoadingTitle');
    const loadingMessage = document.getElementById('incidentReportLoadingMessage');

    if (loadingTitle) {
        loadingTitle.textContent = stage || 'Submitting report';
    }

    if (loadingMessage) {
        loadingMessage.textContent = detail || 'Processing your incident report...';
    }
};

window.showCitizenToast = function(message, isError = false, duration = 2800) {
    window.PyroNotify?.notify(message, isError ? 'error' : 'success', duration);
};

const CITIZEN_NEARBY_INCIDENT_RADIUS_METERS = 3000;
const CITIZEN_NEARBY_ALERT_SEEN_KEY = 'pyrosense:citizen-nearby-alerts:v2';
const CITIZEN_NEARBY_ALERT_SEEN_TTL_MS = 24 * 60 * 60 * 1000;

function filterCitizenNearbyIncidents(incidents = []) {
    const latitude = Number(window.userCoords?.currentLatitude);
    const longitude = Number(window.userCoords?.currentLongitude);
    if (!window.userCoords?.ready || !Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return [];
    }

    const distanceMeters = (targetLat, targetLng) => {
        const earthRadius = 6371000;
        const dLat = (targetLat - latitude) * Math.PI / 180;
        const dLng = (targetLng - longitude) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(latitude * Math.PI / 180) * Math.cos(targetLat * Math.PI / 180)
            * Math.sin(dLng / 2) ** 2;

        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    return (Array.isArray(incidents) ? incidents : []).filter((incident) => {
        const status = String(incident?.incident_status || incident?.status || '').trim().toLowerCase();
        if (!['reported', 'responding', 'en-route', 'en route', 'on scene', 'on-scene'].includes(status)) {
            return false;
        }

        const incidentLat = Number(incident?.lat ?? incident?.marker?.lat);
        const incidentLng = Number(incident?.lng ?? incident?.marker?.lng);
        if (!Number.isFinite(incidentLat) || !Number.isFinite(incidentLng)) {
            return false;
        }

        const distance = distanceMeters(incidentLat, incidentLng);
        incident.distance_meters = Math.round(distance);

        return distance <= CITIZEN_NEARBY_INCIDENT_RADIUS_METERS;
    });
}

function readCitizenSeenNearbyAlerts() {
    try {
        const decoded = JSON.parse(localStorage.getItem(CITIZEN_NEARBY_ALERT_SEEN_KEY) || '{}');
        const now = Date.now();

        Object.entries(decoded).forEach(([key, timestamp]) => {
            if (!timestamp || now - Number(timestamp) > CITIZEN_NEARBY_ALERT_SEEN_TTL_MS) {
                delete decoded[key];
            }
        });

        localStorage.setItem(CITIZEN_NEARBY_ALERT_SEEN_KEY, JSON.stringify(decoded));
        return decoded;
    } catch (error) {
        return {};
    }
}

function markCitizenNearbyAlertSeen(key) {
    if (!key) return;

    const seen = readCitizenSeenNearbyAlerts();
    seen[key] = Date.now();
    localStorage.setItem(CITIZEN_NEARBY_ALERT_SEEN_KEY, JSON.stringify(seen));
}

function getCitizenNearbyAlertKey(incident = {}) {
    return String(
        incident.incident_id
        || incident.id
        || incident.marker_id
        || `${incident.lat || incident.latitude || 'na'}:${incident.lng || incident.longitude || 'na'}:${incident.created_at || ''}`
    );
}

window.requestCitizenEmergencyNotificationPermission = function() {
    if (!('Notification' in window) || Notification.permission !== 'default') {
        return;
    }

    Notification.requestPermission().catch(() => {});
};

function sendCitizenNearbyBrowserNotification(incident = {}) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    const distanceLabel = typeof incident.distance_meters === 'number'
        ? `${Math.round(incident.distance_meters)} meters away`
        : 'near your area';
    const statusLabel = incident.incident_status || incident.status || 'Reported';

    try {
        new Notification('PyroSense nearby fire alert', {
            body: `Fire incident ${distanceLabel}. Status: ${statusLabel}.`,
            tag: `pyrosense-citizen-alert-${getCitizenNearbyAlertKey(incident)}`,
            renotify: true,
            vibrate: [220, 120, 220],
            icon: '/logo.png',
            badge: '/logo.png'
        });
    } catch (error) {
        console.warn('Unable to show citizen nearby browser notification:', error);
    }
}

function notifyNewCitizenNearbyAlerts(nearbyIncidents = [], options = {}) {
    if (options.fromCache || !Array.isArray(nearbyIncidents) || !nearbyIncidents.length) {
        return;
    }

    const seen = readCitizenSeenNearbyAlerts();
    nearbyIncidents.forEach((incident) => {
        const key = getCitizenNearbyAlertKey(incident);
        if (!key || seen[key]) {
            return;
        }

        const status = String(incident.incident_status || incident.status || '').toLowerCase();
        if (['closed', 'controlled', 'resolved'].includes(status)) {
            markCitizenNearbyAlertSeen(key);
            return;
        }

        const distanceLabel = typeof incident.distance_meters === 'number'
            ? ` about ${Math.round(incident.distance_meters)}m away`
            : '';
        window.showCitizenToast?.(`Nearby fire alert${distanceLabel}. Tap the bell to view details.`, false, 6500);
        sendCitizenNearbyBrowserNotification(incident);
        markCitizenNearbyAlertSeen(key);
    });
}

window.renderCitizenWeeklyActivity = function(activityData = null) {
    const barsContainer = document.getElementById('citizenWeeklyActivityBars');
    const labelsContainer = document.getElementById('citizenWeeklyActivityLabels');
    const changeNode = document.getElementById('citizenWeeklyActivityChange');
    const periodNode = document.getElementById('citizenWeeklyActivityPeriod');

    if (!barsContainer || !labelsContainer) {
        return;
    }

    const fallbackReports = Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [];
    const fallbackLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    let labels = Array.isArray(activityData?.labels) && activityData.labels.length === 7
        ? activityData.labels
        : fallbackLabels;
    let counts = Array.isArray(activityData?.counts) && activityData.counts.length === 7
        ? activityData.counts.map((value) => Number(value || 0))
        : null;
    let total = Number(activityData?.total || 0);
    let max = Number(activityData?.max || 0);
    let change = Number(activityData?.change || 0);
    let periodLabel = activityData?.period_label || 'Current week';

    if (!counts) {
        counts = [0, 0, 0, 0, 0, 0, 0];
        const now = new Date();
        const day = now.getDay();
        const mondayOffset = day === 0 ? -6 : 1 - day;
        const startOfWeek = new Date(now);
        startOfWeek.setHours(0, 0, 0, 0);
        startOfWeek.setDate(now.getDate() + mondayOffset);
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        endOfWeek.setHours(23, 59, 59, 999);

        fallbackReports.forEach((incident) => {
            const createdAt = incident?.created_at ? new Date(incident.created_at) : null;
            if (!(createdAt instanceof Date) || Number.isNaN(createdAt.getTime())) {
                return;
            }

            if (createdAt < startOfWeek || createdAt > endOfWeek) {
                return;
            }

            const jsDay = createdAt.getDay();
            const dayIndex = jsDay === 0 ? 6 : jsDay - 1;
            counts[dayIndex] += 1;
        });

        total = counts.reduce((sum, value) => sum + value, 0);
        max = Math.max(...counts, 1);
        change = 0;
        periodLabel = `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: '2-digit' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: '2-digit' })}`;
    }

    max = Math.max(max, 1);

    barsContainer.innerHTML = counts.map((count, index) => {
        const height = count > 0 ? Math.max(18, Math.round((count / max) * 100)) : 8;
        const palette = ['bg-orange-200', 'bg-orange-300', 'bg-orange-400', 'bg-orange-500', 'bg-orange-600', 'bg-orange-400', 'bg-orange-300'];
        const barClass = count > 0 ? (palette[index] || 'bg-orange-400') : 'bg-orange-100';

        return `<div class="flex-1 ${barClass} rounded-t transition-all duration-300" style="height: ${height}%;" title="${labels[index]}: ${count} report(s)"></div>`;
    }).join('');

    labelsContainer.innerHTML = labels.map((label) => `<span>${String(label).charAt(0)}</span>`).join('');

    if (changeNode) {
        if (change > 0) {
            changeNode.textContent = `+${change} vs last week`;
            changeNode.className = 'text-xs text-emerald-600 font-medium';
        } else if (change < 0) {
            changeNode.textContent = `${change} vs last week`;
            changeNode.className = 'text-xs text-amber-600 font-medium';
        } else {
            changeNode.textContent = `${total} this week`;
            changeNode.className = 'text-xs text-gray-500';
        }
    }

    if (periodNode) {
        periodNode.textContent = periodLabel;
    }
};

window.applyCitizenOverviewData = function(data = {}, options = {}) {
    const summary = data.summary || {};
    const nearbyIncidents = filterCitizenNearbyIncidents(data.nearby_incidents);
    const userIncidents = Array.isArray(data.user_incidents) ? data.user_incidents : [];
    const userReports = Array.isArray(data.user_reports) ? data.user_reports : userIncidents;
    const riskMapReports = Array.isArray(data.risk_map_reports) ? data.risk_map_reports : [];
    const activeAlerts = nearbyIncidents.length;
    window.incidentsData = [...userIncidents, ...nearbyIncidents]
        .filter((incident, index, records) => records.findIndex((candidate) => String(candidate.incident_id) === String(incident.incident_id)) === index);

    let mapAlertEntries = [];
    if (typeof window.syncIncidentAlertFeed === 'function') {
        mapAlertEntries = window.syncIncidentAlertFeed('citizen-nearby-incidents', nearbyIncidents, {
            titlePrefix: options.fromCache ? 'Cached Emergency Alert' : 'Nearby Emergency Alert',
            maxDistanceMeters: CITIZEN_NEARBY_INCIDENT_RADIUS_METERS
        }) || [];
    }

    if (!mapAlertEntries.length) {
        notifyNewCitizenNearbyAlerts(nearbyIncidents, options);
    }

    const setText = (id, value) => {
        const node = document.getElementById(id);
        if (node) {
            node.textContent = value;
        }
    };

    const getStatusMeta = (statusValue = '') => {
        const status = String(statusValue).trim().toLowerCase();

        if (status === 'reported' || status === 'pending sync') {
            return { dotClass: 'dot-yellow', badgeClass: 'sbadge-yellow', label: statusValue || 'Pending sync' };
        }
        if (status === 'responding') {
            return { dotClass: 'dot-blue', badgeClass: 'sbadge-blue', label: 'Responding' };
        }
        if (status === 'on scene') {
            return { dotClass: 'dot-purple', badgeClass: 'sbadge-purple', label: 'On scene' };
        }

        return { dotClass: 'dot-red', badgeClass: 'sbadge-red', label: statusValue || 'Unknown' };
    };

    setText('citizenActiveNearYouValue', activeAlerts);
    setText('citizenOverviewYourReportsValue', summary.your_reports ?? userReports.length ?? 0);
    setText('citizenOverviewTotalReportsValue', summary.total_reports ?? userReports.length ?? 0);
    setText('citizenOverviewZoneRiskValue', summary.risk_level ? String(summary.risk_level).charAt(0).toUpperCase() + String(summary.risk_level).slice(1) : 'Moderate');
    setText('citizenOverviewRiskLevelLabel', `${summary.risk_level ? String(summary.risk_level).charAt(0).toUpperCase() + String(summary.risk_level).slice(1) : 'Moderate'} risk`);
    setText('citizenOverviewActiveAlertsValue', activeAlerts);
    setText('citizenSidebarAlertsValue', activeAlerts);
    setText('citizenSidebarReportsValue', summary.total_reports ?? userReports.length ?? 0);
    setText('citizenSidebarDrillsValue', data.sidebar?.drills ?? window.citizenSidebarDefaults?.drills ?? 0);
    setText('citizenSidebarBadgesValue', data.sidebar?.badges ?? window.citizenSidebarDefaults?.badges ?? 0);
    setText('citizenNavAlertsBadge', activeAlerts);
    setText('citizenNavRequestsBadge', data.requests_count ?? document.getElementById('citizenNavRequestsBadge')?.textContent ?? 0);
    setText('citizenTopUnreadValue', activeAlerts);
    setText('citizenNearbyAlertsCount', `${nearbyIncidents.length} incident(s) nearby${options.fromCache ? ' · cached' : ''}`);

    window.myReportedIncidents = userIncidents;
    window.nearbyAlertsData = nearbyIncidents;
    window.citizenCombinedReports = userReports;
    window.citizenRiskMapReports = riskMapReports;
    window.renderCitizenWeeklyActivity(data.weekly_activity || null);

    const riskPercentageNode = document.getElementById('citizenOverviewRiskPercentageValue');
    if (riskPercentageNode) {
        riskPercentageNode.innerHTML = `${summary.risk_percentage ?? 0}<span class="text-lg font-medium">%</span>`;
    }

    if (typeof window.updateCitizenOverviewCharts === 'function') {
        window.updateCitizenOverviewCharts({
            incidents: window.incidentsData,
            user_reports: userReports,
        });
    }

    if (typeof window.refreshCitizenMapSearchIndex === 'function') {
        window.refreshCitizenMapSearchIndex();
    }

    if (typeof window.refreshCitizenAlertsSection === 'function') {
        window.refreshCitizenAlertsSection(Array.isArray(nearbyIncidents) ? nearbyIncidents : []);
    }

    if (typeof window.refreshCitizenAreasOfConcernReports === 'function') {
        window.refreshCitizenAreasOfConcernReports(userReports);
    }

    if (typeof window.refreshCitizenRiskConcernMap === 'function') {
        window.refreshCitizenRiskConcernMap(riskMapReports);
    }

    if (window.usermap && typeof window.loadMarkers === 'function') {
        window.loadMarkers();
    }

    const fab = document.getElementById('alertsFab');
    let fabBadge = document.getElementById('citizenAlertsFabBadge');
    if (activeAlerts > 0) {
        if (!fabBadge && fab) {
            fabBadge = document.createElement('span');
            fabBadge.id = 'citizenAlertsFabBadge';
            fabBadge.className = 'alerts-fab-badge';
            fab.appendChild(fabBadge);
        }
        if (fabBadge) {
            fabBadge.textContent = activeAlerts;
        }
    } else if (fabBadge) {
        fabBadge.remove();
    }

    const alertsList = document.getElementById('citizenAlertsPanelList');
    if (!alertsList) {
        return;
    }

    if (!nearbyIncidents.length) {
        alertsList.innerHTML = `
            <div class="text-center py-12 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
                <p class="text-sm font-medium">No active alerts</p>
                <p class="text-xs mt-1">${options.fromCache ? 'Showing your last saved dashboard snapshot' : 'Your area is currently clear'}</p>
            </div>
        `;
        return;
    }

    alertsList.innerHTML = nearbyIncidents.map((incident) => {
        const statusMeta = getStatusMeta(incident.incident_status);
        const distanceKm = typeof incident.distance_meters === 'number'
            ? ` · ${(incident.distance_meters / 1000).toFixed(1)} km away`
            : '';

        return `
            <button type="button" class="alerts-panel-item w-full text-left js-citizen-alert-focus" data-incident='${JSON.stringify(incident).replace(/'/g, '&#39;')}'>
                <span class="alerts-dot ${statusMeta.dotClass}"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">Fire ${incident.incident_type || 'Incident'}</p>
                    <p class="text-xs text-gray-400 mt-0.5">${incident.created_at_human || 'Just now'}${distanceKm}</p>
                </div>
                <span class="alerts-status-badge ${statusMeta.badgeClass}">${statusMeta.label}</span>
            </button>
        `;
    }).join('');

    alertsList.querySelectorAll('.js-citizen-alert-focus').forEach((button) => {
        button.addEventListener('click', function() {
            try {
                const incident = JSON.parse(this.dataset.incident || '{}');
                if (typeof window.switchSection === 'function') {
                    window.switchSection('overview');
                }
                if (typeof window.focusCitizenIncidentOnMap === 'function') {
                    window.focusCitizenIncidentOnMap(incident);
                }
            } catch (parseError) {
                console.error('Unable to focus incident from alerts panel:', parseError);
            }
        });
    });
};

window.persistCitizenOverviewOfflineData = function(data = {}) {
    window.pyroPwa?.storeDataset?.('citizen-overview', data);
    window.pyroPwa?.storeDataset?.('citizen-alerts', Array.isArray(data.nearby_incidents) ? data.nearby_incidents : []);
    window.pyroPwa?.storeDataset?.('citizen-incidents', Array.isArray(window.incidentsData) ? window.incidentsData : []);
    window.pyroPwa?.storeDataset?.('citizen-user-reports', Array.isArray(data.user_reports) ? data.user_reports : []);
    window.pyroPwa?.storeDataset?.('citizen-risk-map-reports', Array.isArray(data.risk_map_reports) ? data.risk_map_reports : []);
    window.pyroPwa?.updatePageSnapshot?.({
        key: 'citizen-dashboard',
        label: 'Citizen Dashboard'
    });
};

window.refreshCitizenOverviewData = async function() {
    if (!window.userCoords?.ready || !window.userCoords?.currentLatitude || !window.userCoords?.currentLongitude) {
        return;
    }

    let timeoutId = null;
    try {
        if (window.citizenOverviewRequest) {
            await window.citizenOverviewRequest;
            return;
        }

        const params = new URLSearchParams({
            lat: String(window.userCoords.currentLatitude),
            lng: String(window.userCoords.currentLongitude)
        });

        const controller = new AbortController();
        timeoutId = window.setTimeout(() => controller.abort(), 10000);
        window.citizenOverviewRequest = fetch(`/citizen/dashboard-data?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store',
            signal: controller.signal
        });
        const response = await window.citizenOverviewRequest;

        if (!response.ok) {
            throw new Error(`Dashboard refresh failed with status ${response.status}`);
        }

        const payload = await response.json();
        const data = payload?.data || {};
        window.persistCitizenOverviewOfflineData(data);
        window.applyCitizenOverviewData(data, { fromCache: false });
        return;
        const summary = data.summary || {};
        const nearbyIncidents = Array.isArray(data.nearby_incidents) ? data.nearby_incidents : [];
        const userIncidents = Array.isArray(data.user_incidents) ? data.user_incidents : [];
        const userReports = Array.isArray(data.user_reports) ? data.user_reports : userIncidents;
        const riskMapReports = Array.isArray(data.risk_map_reports) ? data.risk_map_reports : [];
        const activeAlerts = Number(summary.active_alerts || 0);
        window.incidentsData = Array.isArray(data.incidents) ? data.incidents : [];

        if (typeof window.syncIncidentAlertFeed === 'function') {
            window.syncIncidentAlertFeed('citizen-nearby-incidents', nearbyIncidents, {
                titlePrefix: 'Nearby Emergency Alert',
                maxDistanceMeters: CITIZEN_NEARBY_INCIDENT_RADIUS_METERS
            });
        }

        const setText = (id, value) => {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = value;
            }
        };

        const getStatusMeta = (statusValue = '') => {
            const status = String(statusValue).trim().toLowerCase();

            if (status === 'reported') {
                return { dotClass: 'dot-yellow', badgeClass: 'sbadge-yellow', label: 'Reported' };
            }
            if (status === 'responding') {
                return { dotClass: 'dot-blue', badgeClass: 'sbadge-blue', label: 'Responding' };
            }
            if (status === 'on scene') {
                return { dotClass: 'dot-purple', badgeClass: 'sbadge-purple', label: 'On scene' };
            }

            return { dotClass: 'dot-red', badgeClass: 'sbadge-red', label: statusValue || 'Unknown' };
        };

        setText('citizenActiveNearYouValue', activeAlerts);
        setText('citizenOverviewYourReportsValue', summary.your_reports ?? 0);
        setText('citizenOverviewTotalReportsValue', summary.total_reports ?? 0);
        setText('citizenOverviewZoneRiskValue', summary.risk_level ? String(summary.risk_level).charAt(0).toUpperCase() + String(summary.risk_level).slice(1) : 'Moderate');
        setText('citizenOverviewRiskLevelLabel', `${summary.risk_level ? String(summary.risk_level).charAt(0).toUpperCase() + String(summary.risk_level).slice(1) : 'Moderate'} risk`);
        setText('citizenOverviewActiveAlertsValue', activeAlerts);
        setText('citizenSidebarAlertsValue', activeAlerts);
        setText('citizenSidebarReportsValue', summary.total_reports ?? 0);
        setText('citizenSidebarDrillsValue', data.sidebar?.drills ?? window.citizenSidebarDefaults?.drills ?? 0);
        setText('citizenSidebarBadgesValue', data.sidebar?.badges ?? window.citizenSidebarDefaults?.badges ?? 0);
        setText('citizenNavAlertsBadge', activeAlerts);
        setText('citizenNavRequestsBadge', data.requests_count ?? document.getElementById('citizenNavRequestsBadge')?.textContent ?? 0);
        setText('citizenTopUnreadValue', activeAlerts);
        setText('citizenNearbyAlertsCount', `${nearbyIncidents.length} incident(s) nearby`);

        window.myReportedIncidents = userIncidents;
        window.nearbyAlertsData = nearbyIncidents;
        window.citizenCombinedReports = userReports;
        window.citizenRiskMapReports = riskMapReports;
        window.renderCitizenWeeklyActivity(data.weekly_activity || null);

        const riskPercentageNode = document.getElementById('citizenOverviewRiskPercentageValue');
        if (riskPercentageNode) {
            riskPercentageNode.innerHTML = `${summary.risk_percentage ?? 0}<span class="text-lg font-medium">%</span>`;
        }

        if (typeof window.updateCitizenOverviewCharts === 'function') {
            window.updateCitizenOverviewCharts({
                incidents: Array.isArray(data.incidents) ? data.incidents : [],
                user_reports: userReports,
            });
        }

        if (typeof window.refreshCitizenMapSearchIndex === 'function') {
            window.refreshCitizenMapSearchIndex();
        }

        if (typeof window.refreshCitizenAlertsSection === 'function') {
            window.refreshCitizenAlertsSection(Array.isArray(nearbyIncidents) ? nearbyIncidents : []);
        }

        if (typeof window.refreshCitizenAreasOfConcernReports === 'function') {
            window.refreshCitizenAreasOfConcernReports(userReports);
        }

        if (typeof window.refreshCitizenRiskConcernMap === 'function') {
            window.refreshCitizenRiskConcernMap(riskMapReports);
        }

        if (window.usermap && typeof window.loadMarkers === 'function') {
            window.loadMarkers();
        }

        const fab = document.getElementById('alertsFab');
        let fabBadge = document.getElementById('citizenAlertsFabBadge');
        if (activeAlerts > 0) {
            if (!fabBadge && fab) {
                fabBadge = document.createElement('span');
                fabBadge.id = 'citizenAlertsFabBadge';
                fabBadge.className = 'alerts-fab-badge';
                fab.appendChild(fabBadge);
            }
            if (fabBadge) {
                fabBadge.textContent = activeAlerts;
            }
        } else if (fabBadge) {
            fabBadge.remove();
        }

        const alertsList = document.getElementById('citizenAlertsPanelList');
        if (alertsList) {
            if (!nearbyIncidents.length) {
                alertsList.innerHTML = `
                    <div class="text-center py-12 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <p class="text-sm font-medium">No active alerts</p>
                        <p class="text-xs mt-1">Your area is currently clear</p>
                    </div>
                `;
            } else {
                alertsList.innerHTML = nearbyIncidents.map((incident) => {
                    const statusMeta = getStatusMeta(incident.incident_status);
                    const distanceKm = typeof incident.distance_meters === 'number'
                        ? ` · ${(incident.distance_meters / 1000).toFixed(1)} km away`
                        : '';

                    return `
                        <button type="button" class="alerts-panel-item w-full text-left js-citizen-alert-focus" data-incident='${JSON.stringify(incident).replace(/'/g, '&#39;')}'>
                            <span class="alerts-dot ${statusMeta.dotClass}"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">🔥 ${incident.incident_type || 'Incident'}</p>
                                <p class="text-xs text-gray-400 mt-0.5">${incident.created_at_human || 'Just now'}${distanceKm}</p>
                            </div>
                            <span class="alerts-status-badge ${statusMeta.badgeClass}">${statusMeta.label}</span>
                        </button>
                    `;
                }).join('');

                alertsList.querySelectorAll('.js-citizen-alert-focus').forEach((button) => {
                    button.addEventListener('click', function() {
                        try {
                            const incident = JSON.parse(this.dataset.incident || '{}');
                            if (typeof window.switchSection === 'function') {
                                window.switchSection('overview');
                            }
                            if (typeof window.focusCitizenIncidentOnMap === 'function') {
                                window.focusCitizenIncidentOnMap(incident);
                            }
                        } catch (parseError) {
                            console.error('Unable to focus incident from alerts panel:', parseError);
                        }
                    });
                });
            }
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        const cachedOverview = window.pyroPwa?.loadDataset?.('citizen-overview', null);
        if (cachedOverview) {
            window.applyCitizenOverviewData(cachedOverview, { fromCache: true });
            return;
        }
        console.error('Error refreshing citizen overview data:', error);
    } finally {
        if (timeoutId) {
            window.clearTimeout(timeoutId);
        }
        window.citizenOverviewRequest = null;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.renderCitizenWeeklyActivity();

    document.querySelectorAll('#alertsFab, .report-incident-trigger, [data-citizen-risk-hazard-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            window.requestCitizenEmergencyNotificationPermission?.();
        }, { once: true });
    });
});

window.addEventListener('pyrosense:report-synced', function() {
    window.showCitizenToast?.('Queued report synced successfully.', false, 2800);
    if (typeof window.refreshCitizenOverviewData === 'function' && window.userCoords?.ready) {
        window.refreshCitizenOverviewData();
    }
});

window.runPendingIncidentAutoAssignment = async function() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const incidentId = window.latestReportedIncidentId || null;

    if (!token) {
        return;
    }

    if (!incidentId) {
        return;
    }

    let timeoutId = null;
    try {
        if (window.citizenAutoAssignRequest) {
            await window.citizenAutoAssignRequest;
            return;
        }

        const controller = new AbortController();
        timeoutId = window.setTimeout(() => controller.abort(), 10000);
        window.citizenAutoAssignRequest = fetch(`/api/incidents/${incidentId}/auto-assign`, {
            method: 'POST',
            cache: 'no-store',
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        });
        const response = await window.citizenAutoAssignRequest;
        window.clearTimeout(timeoutId);
        const result = await response.json().catch(() => ({}));
        if (result?.success && result.assigned) {
            if (window.refreshCitizenOverviewData) {
                window.refreshCitizenOverviewData();
            }
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        console.error(`Background incident auto-assignment failed for incident ${incidentId}:`, error);
    } finally {
        if (timeoutId) {
            window.clearTimeout(timeoutId);
        }
        window.citizenAutoAssignRequest = null;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.runPendingIncidentAutoAssignment();
    window.startCitizenDashboardLocationIntervals?.();

    const CITIZEN_AUTO_ASSIGN_INTERVAL_MS = 60000;
    const CITIZEN_OVERVIEW_INTERVAL_MS = 30000;
    let citizenAutoAssignInterval = null;
    let citizenOverviewInterval = null;

    function clearCitizenDashboardDataIntervals() {
        if (citizenAutoAssignInterval) {
            clearInterval(citizenAutoAssignInterval);
            citizenAutoAssignInterval = null;
        }
        if (citizenOverviewInterval) {
            clearInterval(citizenOverviewInterval);
            citizenOverviewInterval = null;
        }
    }

    function startCitizenDashboardDataIntervals() {
        clearCitizenDashboardDataIntervals();

        citizenAutoAssignInterval = window.setInterval(() => {
            if (!citizenDashboardIsVisible()) return;
            window.runPendingIncidentAutoAssignment();
        }, CITIZEN_AUTO_ASSIGN_INTERVAL_MS);

        if (window.refreshCitizenOverviewData) {
            citizenOverviewInterval = window.setInterval(() => {
                if (!citizenDashboardIsVisible()) return;
                window.refreshCitizenOverviewData();
            }, CITIZEN_OVERVIEW_INTERVAL_MS);
        }
    }

    startCitizenDashboardDataIntervals();

    document.addEventListener('visibilitychange', () => {
        if (citizenDashboardIsVisible()) {
            window.startCitizenDashboardLocationIntervals?.();
            startCitizenDashboardDataIntervals();
            if (window.refreshCitizenOverviewData) {
                window.refreshCitizenOverviewData();
            }
            return;
        }

        window.clearCitizenDashboardLocationIntervals?.();
        clearCitizenDashboardDataIntervals();
    });
});
        // Report incident triggers
document.addEventListener('click', function(event) {
            const trigger = event.target instanceof Element
                ? event.target.closest('.report-incident-trigger')
                : null;

            if (trigger) {
                event.preventDefault();
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');

                if (latitudeInput) {
                    latitudeInput.value = window.userCoords?.currentLatitude ?? '';
                }

                if (longitudeInput) {
                    longitudeInput.value = window.userCoords?.currentLongitude ?? '';
                }

                window.openFireIncidentModal?.();
            }
        });

        document.querySelectorAll('[data-citizen-risk-hazard-open]').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                window.openConcernReportModal?.();
            });
        });

        // fetch('/multimedia')
        // .then(res => res.json())
        // .then(files => {

        //     const gallery = document.getElementById('gallery');

        //     files.forEach(file => {

        //         if(file.type === 'photo' || file.type === 'proof') {

        //             const img = document.createElement('img');
        //             img.src = file.url;
        //             img.width = 200;

        //             gallery.appendChild(img);
        //         }
        //     });
        // });

    </script>
    
@include('partials.user-activity-logger')
@include('citizen.partials.logout-confirmation')
@include('firefighters.partials.icon-fallback')
@include('partials.pwa-body')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.pyroPwa?.registerPageSnapshot?.({
        key: 'citizen-dashboard',
        label: 'Citizen Dashboard'
    });
});
</script>
</body>
</html>
