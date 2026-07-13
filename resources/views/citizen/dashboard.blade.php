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
            gap: 0;
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
            min-height: 34px;
            border-radius: 10px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
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
            gap: 6px;
            padding: 0;
        }

        .citizen-map-layer-item {
            min-height: 44px;
            padding: 0 10px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }

        .citizen-map-layer-item__icon {
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            border-radius: 0;
            background: transparent;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            box-shadow: none;
        }

        .citizen-map-layer-item__copy strong {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .citizen-map-layer-item__check {
            margin-left: auto;
            color: #2563eb;
            display: none;
            font-size: 0.68rem;
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
            gap: 8px 10px;
            padding: 4px 2px 6px;
            box-shadow: none !important;
            filter: none !important;
            -webkit-filter: none !important;
        }

        .citizen-map-legend-item-sheet {
            min-height: 30px;
            padding: 0 2px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            border: 0;
            background: transparent;
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
            width: 24px;
            height: 24px;
            min-width: 24px;
            border-radius: 8px;
            box-shadow: none;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-marker,
        .citizen-map-legend-item-sheet .citizen-map-legend-swatch-truck {
            width: 18px;
            height: 18px;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-image {
            width: 18px;
            height: 18px;
            object-fit: contain;
            display: block;
        }

        .citizen-map-legend-item-sheet .marker-legend-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            display: block;
        }

        .citizen-map-legend-disclosure .incident-marker__pulse,
        .citizen-map-legend-disclosure .incident-pulse {
            display: none !important;
            animation: none !important;
        }

        .citizen-map-legend-item-sheet .incident-marker {
            width: 24px !important;
            height: 24px !important;
        }

        .citizen-map-legend-item-sheet .incident-marker__icon,
        .citizen-map-legend-item-sheet .incident-marker__icon > div,
        .citizen-map-legend-item-sheet .incident-marker__icon img {
            width: 24px !important;
            height: 24px !important;
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
                min-width: 44px;
                max-width: 44px;
                margin-left: auto !important;
                float: right !important;
            }

            .citizen-map-stage .leaflet-control-layers-toggle {
                width: 44px;
                height: 44px;
                background-size: 18px 18px;
            }

            .citizen-map-stage .leaflet-control-layers-expanded {
                width: 44px !important;
                padding: 0;
                border-radius: 14px;
                box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
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
            }

            .citizen-mobile-dock__shell {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: flex-end;
            }

            .citizen-mobile-dock__track {
                position: relative;
                width: min(100%, 344px);
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                align-items: end;
                gap: 6px;
                min-height: 60px;
                padding: 10px 16px 9px;
                border-radius: 24px;
                background: linear-gradient(180deg, rgba(18, 53, 60, 0.95) 0%, rgba(22, 73, 82, 0.96) 100%);
                border: 1px solid rgba(255, 255, 255, 0.12);
                box-shadow: 0 18px 38px rgba(15, 23, 42, 0.24);
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
                justify-content: center;
                justify-self: center;
                color: rgba(214, 227, 230, 0.5);
                text-decoration: none;
                transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
                border-radius: 999px;
                z-index: 1;
                border: none;
                background: transparent;
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
            }

            .citizen-mobile-dock__center-wrap {
                position: relative;
                display: flex;
                justify-content: center;
                align-self: start;
                overflow: visible;
            }

            .citizen-mobile-dock__item--center {
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
                transform: scale(1.05);
            }

            .citizen-mobile-dock__burst {
                position: absolute;
                left: 50%;
                bottom: 76px;
                width: min(calc(100vw - 32px), 300px);
                min-height: 56px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: clamp(7px, 2vw, 11px);
                padding: 7px;
                border-radius: 999px;
                background: rgba(255, 253, 248, 0.96);
                border: 1px solid rgba(198, 61, 38, 0.12);
                box-shadow: 0 20px 44px rgba(88, 43, 20, 0.2);
                backdrop-filter: blur(16px);
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

            .citizen-mobile-dock__burst-item {
                position: relative;
                width: 44px;
                height: 44px;
                flex: 0 0 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: #fff7ed;
                color: #c63d26;
                border: 1px solid rgba(198, 61, 38, 0.12);
                box-shadow: 0 12px 24px rgba(198, 61, 38, 0.12);
                text-decoration: none;
                opacity: 0;
                transform: translateY(12px) scale(0.9);
                transition: opacity 0.2s ease, transform 0.2s ease, background 0.2s ease, color 0.2s ease;
            }

            .citizen-mobile-dock__burst-item::before {
                content: '';
                position: absolute;
                inset: 6px;
                border-radius: inherit;
                border: 1px solid rgba(16, 185, 129, 0.12);
                pointer-events: none;
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item:nth-child(2) { transition-delay: 24ms; }
            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item:nth-child(3) { transition-delay: 48ms; }
            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item:nth-child(4) { transition-delay: 72ms; }
            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst-item:nth-child(5) { transition-delay: 96ms; }

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

            [data-citizen-insights-source] {
                display: none !important;
            }

            .citizen-mobile-insights-panel.is-open [data-citizen-insights-source] {
                display: block !important;
            }

            [data-citizen-settings-panel] {
                align-items: stretch;
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
    @vite(['resources/css/app.css', 'resources/css/citizen/dashboard.css', 'resources/js/user.js'])
    @vite(['resources/js/map.js'])

    <script>
    document.addEventListener('DOMContentLoaded', function(){
          
          var user = @json($usermarker)|| [];
          
   window.mapInstanceConfig = {
        containerId: 'userMap',
        instanceKey: 'citizenDashboard'
    };

   window.mapData = {
        usermark: @json($usermarker) || []
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
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl font-bold">
                        {{ $citizenInitials }}
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

            <!-- ANALYTICS SECTION - SEPARATE FROM NAVIGATION -->
            <div class="px-4 py-4 border-b border-gray-200">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line"></i> YOUR ANALYTICS
             
                </p>
                <div class="space-y-3">
                    <!-- Safety Score -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-3 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-medium text-gray-600">Safety Score</span>
                            {{-- lagay tayo logic dito para mag calculate minors naman na ito --}}
                            <span class="text-xs font-bold text-green-600">85%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-green-600 h-1.5 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-blue-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Alerts</p>
                            <p id="citizenSidebarAlertsValue" class="text-lg font-bold text-blue-600">{{ $totalAlerts ?? 12 }}</p>
                        </div>
                        <div class="bg-orange-50 p-2 rounded-lg text-center">
                            <p class="text-xs text-gray-500">Reports</p>
                            <p id="citizenSidebarReportsValue" class="text-lg font-bold text-orange-600">{{ $overviewReportCount ?? $countReport ?? 0 }}</p>
                            {{-- <p class="text-lg font-bold text-orange-600">{{ $incidents ?? 0 }}</p> --}}

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
                    
                    <!-- Weekly Activity -->
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

            <!-- Dynamic Navigation Menu -->
            <nav class="py-4 px-3">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">MAIN MENU</p>
                <ul class="space-y-1">
                    <li>
                        <a onclick="switchSection('overview')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="overview">
                            <i class="fa-solid fa-gauge-high w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Overview</span>
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">live</span>
                        </a>
                    </li>
                    <li>
                        <a onclick="switchSection('alerts')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Areas of Concern</span>
                            <span id="citizenNavAlertsBadge" class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 0 }}</span>
                        </a>
                    </li>
                    <li>
                        <a onclick="switchSection('fsic')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="fsic">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span id="citizenNavRequestsBadge" class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $fsicRequests->count() }}</span>
                        </a>
                    </li>
                    <li>
                        <a onclick="switchSection('contacts')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="contacts">
                            <i class="fa-solid fa-phone-volume w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Emergency Contacts</span>
                        </a>
                    </li>
                    <li>
                        <a onclick="switchSection('announcements')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="announcements">
                            <i class="fa-solid fa-bullhorn w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Announcements</span>
                        </a>
                    </li>
                    {{-- <li>
                        <a  onclick="switchSection('safety')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="safety">
                            <i class="fa-solid fa-book-open w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Safety Guide</span>
                        </a>
                    </li> --}}
                </ul>

                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">COMMUNITY</p>
                <ul class="space-y-1">
                    {{-- <li>
                        <a onclick="switchSection('neighborhood')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="neighborhood">
                            <i class="fa-solid fa-people-group w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Neighborhood Watch</span>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">8 online</span>
                        </a>
                    </li> --}}
                    {{-- <li>
                        <a onclick="switchSection('events')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="events">
                            <i class="fa-solid fa-calendar-check w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Drills & Events</span>
                        </a>
                    </li> --}}
                    {{-- <li>
                        <a onclick="switchSection('news')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="news">
                            <i class="fa-solid fa-newspaper w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">News & Updates</span>
                        </a>
                    </li> --}}
                </ul>


            </div>
        </div>
    </aside>

    <!-- mobile backdrop -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <nav class="citizen-mobile-dock md:hidden" aria-label="Citizen mobile navigation">
        <div class="citizen-mobile-dock__shell">
            <div class="citizen-mobile-dock__track">
                <button type="button" class="citizen-mobile-dock__item active" data-citizen-dock-item="overview" onclick="switchSection('overview')" aria-label="Overview">
                    <i class="fa-solid fa-house citizen-mobile-dock__icon" aria-hidden="true"></i>
                </button>

                <button type="button" class="citizen-mobile-dock__item" aria-label="Notifications" data-citizen-alerts-open>
                    <i class="fa-solid fa-bell citizen-mobile-dock__icon" aria-hidden="true"></i>
                </button>

                <div class="citizen-mobile-dock__center-wrap" data-citizen-nav-center>
                    <div class="citizen-mobile-dock__burst" data-citizen-nav-burst>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="fsic" onclick="switchSection('fsic')" aria-label="Requests">
                            <i class="fa-solid fa-folder-open citizen-mobile-dock__burst-icon" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="alerts" onclick="switchSection('alerts')" aria-label="Areas of Concern">
                            <i class="fa-solid fa-triangle-exclamation citizen-mobile-dock__burst-icon" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="contacts" onclick="switchSection('contacts')" aria-label="Emergency Contacts">
                            <i class="fa-solid fa-phone citizen-mobile-dock__burst-icon" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-risk-hazard-open aria-label="Report risk or hazard">
                            <i class="fa-solid fa-bullhorn citizen-mobile-dock__burst-icon" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-insights-open aria-label="Charts and Cards">
                            <i class="fa-solid fa-chart-column citizen-mobile-dock__burst-icon" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button type="button" class="citizen-mobile-dock__item citizen-mobile-dock__item--center" data-citizen-nav-trigger aria-label="Report fire incident">
                        <i class="fa-solid fa-fire-flame-curved citizen-mobile-dock__icon" aria-hidden="true"></i>
                    </button>
                </div>

                <button type="button" class="citizen-mobile-dock__item" data-citizen-dock-item="announcements" onclick="switchSection('announcements')" aria-label="Announcements">
                    <i class="fa-solid fa-bullhorn citizen-mobile-dock__icon" aria-hidden="true"></i>
                </button>

                <button type="button" class="citizen-mobile-dock__item" data-citizen-settings-open aria-label="Settings">
                    <i class="fa-solid fa-gear citizen-mobile-dock__icon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="citizen-mobile-panel md:hidden" data-citizen-settings-panel aria-hidden="true">
        <div class="citizen-mobile-panel__backdrop" data-citizen-panel-close></div>
        <div class="citizen-mobile-panel__sheet">
            <div class="citizen-mobile-panel__header">
                <div>
                    <p class="citizen-mobile-panel__eyebrow">Citizen Portal</p>
                    <h2 class="citizen-mobile-panel__title">Settings</h2>
                </div>
                <button type="button" class="citizen-mobile-panel__close" data-citizen-panel-close aria-label="Close settings">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="citizen-mobile-panel__menu">
                <button type="button" class="citizen-mobile-panel__action" onclick="switchSection('profile')" data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">person</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>Profile</strong>
                        <small>Manage your citizen account</small>
                    </span>
                </button>
                <button type="button" class="citizen-mobile-panel__action" data-citizen-open-logs data-citizen-panel-close>
                    <span class="material-symbols-rounded citizen-mobile-panel__action-icon" aria-hidden="true">history</span>
                    <span class="citizen-mobile-panel__action-copy">
                        <strong>My Logs</strong>
                        <small>View your recent activity</small>
                    </span>
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
    <div class="citizen-mobile-panel md:hidden" data-citizen-map-layers-panel aria-hidden="true">
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
                        <input type="radio" class="citizen-map-base-item__toggle" name="citizen_map_base" data-citizen-map-base-toggle="Street Map" checked>
                        <span>Street Map</span>
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
        <div id="citizenMobileWelcomeToast" class="citizen-mobile-welcome-toast md:hidden" role="status" aria-live="polite">
            <div class="citizen-mobile-welcome-toast__icon">
                <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
            </div>
            <div class="citizen-mobile-welcome-toast__copy">
                <strong>Welcome back, {{ $displayName }}</strong>
                <span>Citizen portal is ready with your alerts, map, and reports.</span>
            </div>
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
                        <button class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-phone-alt"></i>
                            <span class="hidden sm:inline">SOS</span>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="user-dropdown">
                            <button id="userDropdownBtn" class="user-dropdown-toggle">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-sm font-bold">
                                    {{ $citizenInitials }}
                                </div>
                                <span class="hidden md:inline text-sm font-semibold text-gray-700">{{ $displayName }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            
                            <div id="userDropdownMenu" class="user-dropdown-menu">
                                <div class="user-dropdown-header">
                                    <p class="text-sm font-bold text-gray-800">{{ $displayName }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <a onclick="switchSection('profile')" class="user-dropdown-item">
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
            
            <!-- Safety Guide Section -->
            @include('citizen.sections.safety')
            
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
        <div id="fireIncidentModal" class="modal fixed inset-0 bg-slate-950/55 backdrop-blur-sm flex items-center z-50 justify-center hidden p-4">
            <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-orange-100 bg-white shadow-[0_32px_90px_rgba(15,23,42,0.24)]">
                <div class="relative overflow-hidden bg-gradient-to-r from-[#c63d26] via-[#d45a1a] to-[#e39b17] px-6 py-6 text-white">
                    <div class="absolute inset-y-0 right-0 w-40 bg-white/10 blur-2xl"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/18 ring-1 ring-white/25 backdrop-blur">
                                <i class="fa-solid fa-fire-flame-curved text-[28px]" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/75">PyroSense Emergency</p>
                                <h2 class="mt-1 text-2xl font-bold leading-tight">Report Fire Incident</h2>
                                <p class="mt-2 max-w-md text-sm text-white/85">Send an emergency fire report right away. BFP can begin processing immediately while station assignment continues in the background.</p>
                            </div>
                        </div>
                        <button type="button" id="closeFireModal" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/12 text-white transition hover:bg-white/20" aria-label="Close fire report modal">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>

                <form id="fireIncidentForm" action="" method="POST" class="px-6 py-6">
                    @csrf
                    <div class="grid gap-4">
                        <div class="rounded-3xl border border-red-100 bg-gradient-to-br from-red-50 via-orange-50 to-white p-5 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Emergency fire report</p>
                                    <p class="mt-1">Use this only for an actual fire emergency. The incident is saved first so the citizen gets instant feedback.</p>
                                </div>
                                <span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-red-700">Priority</span>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">You can use your live location or choose a map point within 100 meters if the fire is slightly away from your exact GPS position.</p>
                        </div>

                        <div id="fireLocationPanel" class="rounded-3xl border border-orange-100 bg-[#fffaf4] p-5 text-sm text-slate-700 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Incident location</p>
                                    <p class="mt-1 text-slate-600">Pick the exact fire point within a 100-meter radius of your current location.</p>
                                </div>
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-orange-700">100m radius</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" id="fireUseCurrentLocationButton" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">my_location</span>
                                    Use My Location
                                </button>
                                <button type="button" id="firePickOnMapButton" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-700">
                                    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">place</span>
                                    Choose Location (100m)
                                </button>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Location status</p>
                                <p id="fireLocationStatus" class="mt-2 text-sm text-slate-700">Your current location will be used for this fire report.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <button type="button" class="rounded-2xl border border-slate-200 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200" data-fire-modal-close>Cancel</button>
                        <button type="button" class="js-citizen-report-action inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#c63d26] to-[#e39b17] px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(198,61,38,0.28)] transition hover:brightness-105" data-report-kind="fire">
                            <span class="material-symbols-rounded text-[18px]" aria-hidden="true">bolt</span>
                            Report Fire Incident
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

                        <div class="rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50 via-orange-50 to-white p-4 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">Non-fire safety concern</p>
                                    <p class="mt-1">Use this for unsafe fire-related conditions that are not an active fire emergency.</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700">BFP review</span>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm">
                            <label for="concernTypeSelect" class="block text-sm font-semibold text-slate-900">Risk / Hazard type</label>
                            <p class="mt-1 text-xs text-slate-500">Required by the current citizen reporting flow. If unsure, keep the default option.</p>
                            <select id="concernTypeSelect" class="mt-3 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="unsure">I am not sure yet</option>
                                <option value="electrical_issue">Electrical issue</option>
                                <option value="unsafe_cooking_lpg">Unsafe cooking or LPG use</option>
                                <option value="blocked_hydrant_access">Blocked hydrant or fire access</option>
                                <option value="combustible_clutter">Combustible clutter or open burning</option>
                                <option value="dense_fire_prone_area">Dense fire-prone area</option>
                            </select>
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
    <div id="incidentReportLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[120]">
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
    <div id="myLogsModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-center justify-center z-[4000] p-4">
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
                if (!action) {
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
                        body: JSON.stringify({ action })
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
    const CITIZEN_LOCATION_PULL_INTERVAL_MS = 45000;
    let citizenLocationPushInterval = null;
    let citizenLocationPullInterval = null;

    function citizenDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function clearCitizenDashboardLocationIntervals() {
        if (citizenLocationPushInterval) {
            clearInterval(citizenLocationPushInterval);
            citizenLocationPushInterval = null;
        }
        if (citizenLocationPullInterval) {
            clearInterval(citizenLocationPullInterval);
            citizenLocationPullInterval = null;
        }
    }

    function startCitizenDashboardLocationIntervals() {
        clearCitizenDashboardLocationIntervals();

        citizenLocationPushInterval = setInterval(() => {
            if (!citizenDashboardIsVisible()) return;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateUserLocation(window.currentUserId, lat, lng);
                    window.userCoords.currentLatitude = lat;
                    window.userCoords.currentLongitude = lng;
                    window.userCoords.ready = true;
                    if (window.refreshCitizenMapData) {
                        window.refreshCitizenMapData();
                    }
                    if (window.refreshCitizenOverviewData) {
                        window.refreshCitizenOverviewData();
                    }

                    sendUserLocationToServer(lat, lng);

                    if (typeof window.currentLoc === 'function') {
                        window.currentLoc(lat, lng);
                    }
                    updateCitizenResolvedLocation(lat, lng);
                },
                function(error) {
                    console.error("Location update error:", error);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }, CITIZEN_LOCATION_PUSH_INTERVAL_MS);

        citizenLocationPullInterval = setInterval(() => {
            if (!citizenDashboardIsVisible()) return;
            fetchAllFirefighterLocations();
        }, CITIZEN_LOCATION_PULL_INTERVAL_MS);
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
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            // const lat = 15.232331;
            // const lng = 120.232331;

            // Update for current user
            updateUserLocation(window.currentUserId, lat, lng);
            window.userCoords.currentLatitude = lat;
            window.userCoords.currentLongitude = lng;
            window.userCoords.ready = true;
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
                window.currentLoc(lat, lng);
            }
            updateCitizenResolvedLocation(lat, lng);
            
            // Send location to server for other users to see
            sendUserLocationToServer(lat, lng);
            
            document.dispatchEvent(new Event("locationReady"));
        },
        function(error) {
            console.error("Location error:", error);
            // Fallback to default location
            const defaultLat = 15.8222;
            const defaultLng = 120.3297;
            updateUserLocation(window.currentUserId, defaultLat, defaultLng);
            window.userCoords.currentLatitude = defaultLat;
            window.userCoords.currentLongitude = defaultLng;
            window.userCoords.ready = true;
            if (window.refreshCitizenMapData) {
                window.refreshCitizenMapData();
            }
            if (window.refreshCitizenOverviewData) {
                window.refreshCitizenOverviewData();
            }
            if (typeof window.currentLoc === 'function') {
                window.currentLoc(defaultLat, defaultLng);
            }
            updateCitizenResolvedLocation(defaultLat, defaultLng);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
    
    // Initial fetch
    setTimeout(() => {
        fetchAllFirefighterLocations();
    }, 2000);
    
} else {
    console.warn("Geolocation not supported");
    const defaultLat = 15.8222;
    const defaultLng = 120.3297;
    updateUserLocation(window.currentUserId, defaultLat, defaultLng);
    window.userCoords.currentLatitude = defaultLat;
    window.userCoords.currentLongitude = defaultLng;
    window.userCoords.ready = true;
    if (window.refreshCitizenMapData) {
        window.refreshCitizenMapData();
    }
    if (window.refreshCitizenOverviewData) {
        window.refreshCitizenOverviewData();
    }
    updateCitizenResolvedLocation(defaultLat, defaultLng);
    if (typeof window.currentLoc === 'function') {
        window.currentLoc(defaultLat, defaultLng);
    }
     
            
}
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
            document.querySelectorAll('.nav-item').forEach(item => {
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
        const citizenMobileWelcomeToast = document.getElementById('citizenMobileWelcomeToast');
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
                'Street Map': ['Street Map', 'OSM', 'OSM Standard'],
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

            citizenMapBaseToggles.forEach((toggle) => {
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
            const holdDelay = 360;

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

        const openCitizenSettingsPanel = () => {
            citizenSettingsPanel?.classList.add('is-open');
            citizenSettingsPanel?.setAttribute('aria-hidden', 'false');
        };

        const closeCitizenSettingsPanel = () => {
            citizenSettingsPanel?.classList.remove('is-open');
            citizenSettingsPanel?.setAttribute('aria-hidden', 'true');
        };

        const openCitizenMapLayersPanel = () => {
            citizenMapLayersPanel?.classList.add('is-open');
            citizenMapLayersPanel?.setAttribute('aria-hidden', 'false');
            citizenMapLayersPanel._lastTrigger = document.activeElement;
            if (typeof window.syncCitizenMapLayerControls === 'function') {
                window.syncCitizenMapLayerControls();
            }
        };

        const closeCitizenMapLayersPanel = () => {
            if (citizenMapLayersPanel?.contains(document.activeElement)) {
                document.activeElement.blur();
            }
            citizenMapLayersPanel?.classList.remove('is-open');
            citizenMapLayersPanel?.setAttribute('aria-hidden', 'true');
            const lastTrigger = citizenMapLayersPanel?._lastTrigger;
            if (lastTrigger && typeof lastTrigger.focus === 'function') {
                window.setTimeout(() => lastTrigger.focus({ preventScroll: true }), 0);
            }
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
            button.addEventListener('click', function () {
                closeCitizenSettingsPanel();
                openMyLogsModal();
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

        if (citizenMobileWelcomeToast && window.innerWidth <= 768) {
            citizenMobileWelcomeToast.classList.add('is-visible');
            window.setTimeout(() => {
                citizenMobileWelcomeToast.classList.remove('is-visible');
            }, 3200);
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

            citizenInsightsPanel.classList.remove('is-open');
            citizenInsightsPanel.setAttribute('aria-hidden', 'true');
            restoreCitizenInsightsSources();
        };

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
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const myLogsModal = document.getElementById('myLogsModal');
        const myLogsContainer = document.getElementById('myLogsContainer');
        const myLogsSummary = document.getElementById('myLogsSummary');
        const refreshMyLogsBtn = document.getElementById('refreshMyLogsBtn');
        const closeMyLogsModalBtn = document.getElementById('closeMyLogsModal');

        if (userDropdownBtn) {
            userDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('show');
            });
        }

        document.addEventListener('click', (e) => {
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

        function renderMyLogs(logs = []) {
            window.citizenUserLogs = logs;

            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            myLogsSummary.textContent = `${logs.length} log entr${logs.length === 1 ? 'y' : 'ies'} found`;

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
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
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
                                            <span class="break-words"><span class="font-semibold text-slate-700">User Agent:</span> ${summarizeUserAgent(log.user_agent)}</span>
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
            `;
        }

        async function loadMyLogs() {
            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            myLogsSummary.textContent = 'Loading your activity logs...';
            myLogsContainer.innerHTML = '<div class="text-sm text-slate-500">Loading logs...</div>';

            try {
                const response = await fetch('{{ route('user-logs.index') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load logs (${response.status})`);
                }

                const payload = await response.json();
                renderMyLogs(Array.isArray(payload?.data) ? payload.data : []);
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
            }
            loadMyLogs();
        }

        function closeMyLogsModal() {
            if (myLogsModal) {
                myLogsModal.classList.add('hidden');
                myLogsModal.classList.remove('flex');
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
                statusNode.textContent = 'Tap the map within 100 meters of your current location to place the safety concern point.';
                return;
            }

            if (citizenHazardDraft.mode === 'picked' && citizenHazardDraft.selectedLat !== null && citizenHazardDraft.selectedLng !== null) {
                statusNode.textContent = `Selected map point: ${citizenHazardDraft.selectedLat.toFixed(6)}, ${citizenHazardDraft.selectedLng.toFixed(6)}.`;
                return;
            }

            statusNode.textContent = 'Safety concern reports will use your current location unless you pick another point on the map.';
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
                statusNode.textContent = 'Getting your current location now...';
                return;
            }

            if (citizenFireDraft.picking) {
                statusNode.textContent = 'Tap the map within 100 meters of your current location to place the fire incident point.';
                return;
            }

            if (citizenFireDraft.mode === 'picked' && citizenFireDraft.selectedLat !== null && citizenFireDraft.selectedLng !== null) {
                statusNode.textContent = `Selected fire point: ${citizenFireDraft.selectedLat.toFixed(6)}, ${citizenFireDraft.selectedLng.toFixed(6)}.`;
                return;
            }

            statusNode.textContent = `Current location ready: ${Number(window.userCoords.currentLatitude).toFixed(6)}, ${Number(window.userCoords.currentLongitude).toFixed(6)}.`;
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

        function startCitizenHazardMapPick() {
            if (!window.userCoords.ready || !window.userCoords.currentLatitude || !window.userCoords.currentLongitude) {
                window.showCitizenToast?.('Getting your location. Please wait and try again.', true);
                return;
            }

            citizenHazardDraft.mode = 'picked';
            citizenHazardDraft.picking = true;

            document.getElementById('concernReportModal').style.display = 'none';
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
                    color: '#f59e0b',
                    fillColor: '#fbbf24',
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

            document.getElementById('fireIncidentModal').style.display = 'none';
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
                    color: '#c63d26',
                    fillColor: '#f97316',
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

        function openFireIncidentModal() {
            const fireModal = document.getElementById('fireIncidentModal');

            if (window.userCoords.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude && citizenFireDraft.selectedLat === null && citizenFireDraft.selectedLng === null) {
                citizenFireDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenFireDraft.selectedLng = Number(window.userCoords.currentLongitude);
            }

            updateCitizenFireLocationUi();
            fireModal.style.display = 'flex';
        }

        function openConcernReportModal() {
            if (window.userCoords.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude && citizenHazardDraft.selectedLat === null && citizenHazardDraft.selectedLng === null) {
                citizenHazardDraft.selectedLat = Number(window.userCoords.currentLatitude);
                citizenHazardDraft.selectedLng = Number(window.userCoords.currentLongitude);
            }
            updateCitizenHazardLocationUi();
            document.getElementById('concernReportModal').style.display = 'flex';
        }

        document.querySelectorAll('[data-fire-modal-close], #closeFireModal').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                document.getElementById('fireIncidentModal').style.display = 'none';
            });
        });

        document.querySelectorAll('[data-concern-modal-close], #closeConcernModal').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                document.getElementById('concernReportModal').style.display = 'none';
            });
        });

        document.getElementById('hazardUseCurrentLocationButton')?.addEventListener('click', function() {
            setCitizenHazardCurrentLocation();
        });

        document.getElementById('hazardPickOnMapButton')?.addEventListener('click', function() {
            startCitizenHazardMapPick();
        });

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
}

async function submitCitizenQuickReport(reportKind) {
    const description = '';
    const incidentTypeId = 1;
    const reporter = document.getElementById('reported_by').value;
    const concernType = document.getElementById('concernTypeSelect')?.value || 'unsure';
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

    if (window.pyroPwa?.isOffline?.()) {
        const firePayload = {
            lat: fireLat,
            lng: fireLng,
            title: 'Citizen Fire Emergency',
            description: description || 'Citizen-submitted fire emergency.',
            status: 'reported',
            reported_by: reporter,
            alarmdatetime: new Date().toISOString(),
        };
        const concernPayload = {
            report_kind: reportKind,
            concern_type: concernType,
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
        document.getElementById(reportKind === 'fire' ? 'fireIncidentModal' : 'concernReportModal').style.display = 'none';
        showIncidentToast?.(`${reportLabels[reportKind] || 'Report'} saved offline. Pending sync.`, false, 4200);
        return;
    }

    // Close modal early for better UX
    document.getElementById(reportKind === 'fire' ? 'fireIncidentModal' : 'concernReportModal').style.display = 'none';

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
        : 'Saving your safety concern...';
    loadingOverlay?.classList.remove('hidden');
    loadingOverlay?.classList.add('flex');

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
                reporter
            );
            successMessage = window.latestIncidentSubmitMessage || 'Fire incident reported successfully.';
        } else {
            const response = await fetch('{{ route('citizen.risk-reports.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    report_kind: reportKind,
                    concern_type: concernType,
                    lat: concernLat,
                    lng: concernLng,
                    user_lat: Number(window.userCoords.currentLatitude),
                    user_lng: Number(window.userCoords.currentLongitude)
                })
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload?.message || `Failed to submit ${reportKind} report.`);
            }

            success = Boolean(payload?.success);
            successMessage = payload?.message || `${reportKind.charAt(0).toUpperCase() + reportKind.slice(1)} report submitted successfully.`;
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
            if (typeof window.refreshCitizenOverviewData === 'function') {
                window.refreshCitizenOverviewData().catch((refreshError) => {
                    console.error('Citizen dashboard refresh failed after report submit:', refreshError);
                });
            }
        }
        
    } catch (error) {
        console.error('Error:', error);
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
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.textContent = message;
    toast.style.background = isError
        ? 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)'
        : 'linear-gradient(135deg, #f97316 0%, #dc2626 100%)';
    toast.classList.add('show');

    window.clearTimeout(window.citizenIncidentToastTimer);
    window.citizenIncidentToastTimer = window.setTimeout(() => {
        toast.classList.remove('show');
        toast.style.background = '';
    }, duration);
};

const CITIZEN_NEARBY_ALERT_SEEN_KEY = 'pyrosense:citizen-nearby-alerts:v1';
const CITIZEN_NEARBY_ALERT_SEEN_TTL_MS = 24 * 60 * 60 * 1000;

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
    const nearbyIncidents = Array.isArray(data.nearby_incidents) ? data.nearby_incidents : [];
    const userIncidents = Array.isArray(data.user_incidents) ? data.user_incidents : [];
    const userReports = Array.isArray(data.user_reports) ? data.user_reports : userIncidents;
    const riskMapReports = Array.isArray(data.risk_map_reports) ? data.risk_map_reports : [];
    const activeAlerts = Number(summary.active_alerts || 0);
    window.incidentsData = Array.isArray(data.incidents) ? data.incidents : [];

    let mapAlertEntries = [];
    if (typeof window.syncIncidentAlertFeed === 'function') {
        mapAlertEntries = window.syncIncidentAlertFeed('citizen-nearby-incidents', nearbyIncidents, {
            titlePrefix: options.fromCache ? 'Cached Emergency Alert' : 'Nearby Emergency Alert',
            maxDistanceMeters: 500
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
    window.pyroPwa?.storeDataset?.('citizen-incidents', Array.isArray(data.incidents) ? data.incidents : []);
    window.pyroPwa?.storeDataset?.('citizen-user-reports', Array.isArray(data.user_reports) ? data.user_reports : []);
    window.pyroPwa?.storeDataset?.('citizen-risk-map-reports', Array.isArray(data.risk_map_reports) ? data.risk_map_reports : []);
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
                maxDistanceMeters: 500
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
document.querySelectorAll('.report-incident-trigger').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                document.getElementById('latitude').value = window.userCoords.currentLatitude;
                document.getElementById('longitude').value =  window.userCoords.currentLongitude;
                openFireIncidentModal();
            });
        });

        document.querySelectorAll('[data-citizen-risk-hazard-open]').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                openConcernReportModal();
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
</body>
</html>
