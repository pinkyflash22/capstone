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
  --mobile-bottom-nav-h: calc(64px + var(--safe-bottom));
  --upload-bg: #f8f9fa;
  --upload-border: #e0e0e0;
  --upload-border-active: #4a6cf7;
  --upload-text: #4a4a4a;
  --upload-icon: #4a6cf7;
  --upload-btn-bg: #4a6cf7;
  --upload-btn-bg-hover: #3652cc;
  --upload-btn-text: #ffffff;
  --upload-item-bg: #ffffff;
  --upload-item-border: #e0e0e0;
  --upload-item-progress: #4a6cf7;
  --upload-item-icon: #27ae60;
  --upload-item-remove: #e74c3c;
  --upload-transition: all 0.3s ease;
}

.material-symbols-rounded {
  font-family: "Material Symbols Rounded" !important;
  font-weight: normal;
  font-style: normal;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
  -webkit-font-smoothing: antialiased;
  font-variation-settings:
    "FILL" 1,
    "wght" 500,
    "GRAD" 0,
    "opsz" 24;
}

/* Dark mode variables */
@media (prefers-color-scheme: dark) {
  :root {
    --upload-bg: #1e1e1e;
    --upload-border: #333333;
    --upload-border-active: #6c8fff;
    --upload-text: #e1e1e1;
    --upload-icon: #6c8fff;
    --upload-btn-bg: #6c8fff;
    --upload-btn-bg-hover: #5373e0;
    --upload-btn-text: #ffffff;
    --upload-item-bg: #2a2a2a;
    --upload-item-border: #333333;
    --upload-item-progress: #6c8fff;
    --upload-item-icon: #27ae60;
    --upload-item-remove: #e74c3c;
  }
}

.file-upload-container {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

.file-upload-area {
  position: relative;
  width: 100%;
  min-height: 250px;
  background-color: var(--upload-bg);
  border: 2px dashed var(--upload-border);
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 30px 20px;
  box-sizing: border-box;
  transition: var(--upload-transition);
  overflow: hidden;
}

.file-upload-area.active {
  border-color: var(--upload-border-active);
  background-color: rgba(74, 108, 247, 0.05);
}

.file-upload-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: var(--upload-text);
  width: 100%;
}

.file-upload-message i {
  font-size: 3rem;
  color: var(--upload-icon);
  margin-bottom: 1rem;
}

.file-upload-message h3 {
  margin: 0 0 0.5rem;
  font-size: 1.2rem;
}

.file-upload-message p {
  margin: 0.5rem 0;
}

.file-upload-btn {
  display: inline-block;
  padding: 10px 20px;
  margin: 10px 0;
  background-color: var(--upload-btn-bg);
  color: var(--upload-btn-text);
  border-radius: 5px;
  font-weight: 500;
  cursor: pointer;
  transition: var(--upload-transition);
}

.file-upload-btn:hover {
  background-color: var(--upload-btn-bg-hover);
}

.file-upload-info {
  font-size: 0.8rem;
  opacity: 0.7;
}

.file-upload-preview {
  width: 100%;
  margin-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.upload-item {
  display: flex;
  align-items: center;
  background-color: var(--upload-item-bg);
  border: 1px solid var(--upload-item-border);
  border-radius: 5px;
  padding: 10px;
  position: relative;
}

.upload-item-icon {
  font-size: 1.5rem;
  margin-right: 15px;
}

.upload-item-details {
  flex: 1;
}

.upload-item-name {
  font-weight: 500;
  margin-bottom: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

.upload-item-size {
  font-size: 0.8rem;
  opacity: 0.7;
}

.upload-item-progress-container {
  width: 100%;
  height: 4px;
  background-color: var(--upload-border);
  border-radius: 2px;
  overflow: hidden;
  margin-top: 5px;
}

.upload-item-progress {
  height: 100%;
  background-color: var(--upload-item-progress);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.upload-item-actions {
  display: flex;
  align-items: center;
}

.upload-item-status {
  margin-right: 10px;
  font-size: 1rem;
}

.upload-item-remove {
  background: none;
  border: none;
  color: var(--upload-item-remove);
  cursor: pointer;
  font-size: 1rem;
  padding: 5px;
  transition: var(--upload-transition);
}

.upload-item-remove:hover {
  opacity: 0.8;
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

        [data-citizen-map-layers-panel] .citizen-mobile-panel__sheet {
            padding-top: 0.65rem;
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

        [data-citizen-map-layers-panel] .citizen-mobile-panel__header {
            margin-bottom: 0.55rem;
            min-height: 24px;
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

        [data-citizen-map-layers-panel] .citizen-mobile-panel__close {
            width: 30px;
            height: 30px;
            background: #fff7ed;
            color: #c2410c;
            box-shadow: inset 0 0 0 1px rgba(251, 146, 60, 0.18);
        }

        [data-citizen-map-layers-panel] .citizen-mobile-panel__close .material-symbols-rounded {
            font-size: 18px;
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

        .citizen-map-sheet-section {
            display: grid;
            gap: 0.5rem;
        }

        .citizen-map-layer-list,
        .citizen-map-legend-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.45rem;
            padding: 0;
        }

        .citizen-map-sheet-label {
            color: #64748b;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0 0 0.1rem;
        }

        .citizen-map-layer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.24rem;
            min-height: 58px;
            padding: 0.3rem 0.1rem 0.2rem;
            border-radius: 14px;
            background: transparent;
            border: none;
            position: relative;
            text-align: center;
            cursor: pointer;
        }

        .citizen-map-layer-item__toggle {
            appearance: none;
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .citizen-map-layer-item__icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            background: #e5e7eb;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.16);
            transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        }

        .citizen-map-layer-item__copy {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .citizen-map-layer-item__copy strong {
            color: #64748b;
            font-size: 0.56rem;
            font-weight: 600;
            line-height: 1.1;
            transition: color 0.18s ease;
        }

        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) .citizen-map-layer-item__icon {
            background: #dbeafe;
            color: #2563eb;
            box-shadow: 0 0 0 2px #fde68a, inset 0 0 0 1px rgba(37, 99, 235, 0.14);
            transform: scale(1.06);
        }

        .citizen-map-layer-item:has(.citizen-map-layer-item__toggle:checked) .citizen-map-layer-item__copy strong {
            color: #2563eb;
        }

        .citizen-map-legend-item-sheet {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.24rem;
            min-height: 58px;
            padding: 0.3rem 0.1rem 0.2rem;
            border-radius: 14px;
            background: transparent;
            border: none;
            color: #334155;
            font-size: 0.54rem;
            line-height: 1.1;
            text-align: center;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-swatch {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 9px;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.16);
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-marker,
        .citizen-map-legend-item-sheet .citizen-map-legend-swatch-truck {
            width: 18px;
            height: 18px;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-marker i {
            font-size: 9px;
        }

        .citizen-map-legend-item-sheet .citizen-map-legend-swatch-truck img {
            width: 12px;
            height: 12px;
        }

        .citizen-map-legend-item-sheet span:last-child {
            font-weight: 600;
            color: #64748b;
        }

        @media (max-width: 380px) {
            .citizen-map-layer-list,
            .citizen-map-legend-list {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.35rem;
            }
        }

        .citizen-map-stage > .flex.flex-col.sm\:flex-row.sm\:items-center.justify-between {
            display: none !important;
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
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .citizen-map-search__icon {
            color: #f97316;
            flex-shrink: 0;
        }

        .citizen-map-search__input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            font-size: 14px;
            color: #111827;
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
                top: 10px;
                left: 10px;
                right: 10px;
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
                top: 72px;
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
                top: 66px;
                right: 10px;
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
                min-height: calc(100svh - var(--mobile-bottom-nav-h) + var(--safe-top)) !important;
                height: calc(100svh - var(--mobile-bottom-nav-h) + var(--safe-top)) !important;
                border-radius: 0 !important;
            }

            .citizen-map-stage .leaflet-top {
                top: 68px;
            }

            .citizen-map-stage .leaflet-right {
                right: 10px;
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
                max-width: none !important;
                padding: 0 0 calc(var(--mobile-bottom-nav-h) + 10px) !important;
                margin: 0 !important;
            }

            .citizen-mobile-dock {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                display: block;
                min-height: 0;
                padding: 0 14px calc(8px + var(--safe-bottom));
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
                bottom: 78px;
                width: 0;
                height: 0;
                opacity: 0;
                transform: translateX(-50%) scale(0.8);
                pointer-events: none;
                transition: opacity 0.22s ease, transform 0.22s ease;
                z-index: 6;
            }

            .citizen-mobile-dock__center-wrap.is-open .citizen-mobile-dock__burst {
                opacity: 1;
                transform: translateX(-50%) scale(1);
                pointer-events: auto;
            }

            .citizen-mobile-dock__burst-item {
                position: absolute;
                width: 46px;
                height: 46px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.96);
                color: #19515a;
                border: 1px solid rgba(255, 255, 255, 0.85);
                box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
                text-decoration: none;
                backdrop-filter: blur(10px);
            }

            .citizen-mobile-dock__burst-item::before {
                content: '';
                position: absolute;
                inset: 6px;
                border-radius: inherit;
                border: 1px solid rgba(16, 185, 129, 0.12);
                pointer-events: none;
            }

            .citizen-mobile-dock__burst-item:nth-child(1) { transform: translate(-78px, -46px); }
            .citizen-mobile-dock__burst-item:nth-child(2) { transform: translate(0, -112px); }
            .citizen-mobile-dock__burst-item:nth-child(3) { transform: translate(78px, -46px); }
            .citizen-mobile-dock__burst-item:nth-child(4) { transform: translate(0, -162px); }

            .citizen-mobile-dock__burst-item.active {
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #ffffff;
                border-color: transparent;
            }

            .citizen-mobile-dock__burst-icon {
                width: 18px;
                height: 18px;
                font-size: 18px;
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
          
   window.mapData = {
        usermark: @json($usermarker) || []
    };
    
    // Also pass incidents data to JavaScript
    window.incidentsData = @json($incidents) || [];
    window.myReportedIncidents = @json($myReports) || [];
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
                        <img src="{{ asset('storage/defaults/pyrosense.png') }}" alt="PyroSense logo" class="w-full h-full object-contain">
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
                    <span class="text-white/90">{{ Auth::user()->userInfo->city ?? 'District 4' }}, {{ Auth::user()->userInfo->barangay ?? 'Zone 7' }}</span>
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
                            <p id="citizenSidebarReportsValue" class="text-lg font-bold text-orange-600">{{ $countReport ?? 0 }}</p>
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
                            <span class="flex-1">Incidents & Alerts</span>
                            <span id="citizenNavAlertsBadge" class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 0 }}</span>
                        </a>
                    </li>
                    <li>
                        <a onclick="switchSection('fsic')" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
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
                    <span class="material-symbols-rounded citizen-mobile-dock__icon" aria-hidden="true">home</span>
                </button>

                <button type="button" class="citizen-mobile-dock__item" aria-label="Notifications" data-citizen-alerts-open>
                    <span class="material-symbols-rounded citizen-mobile-dock__icon" aria-hidden="true">notifications</span>
                </button>

                <div class="citizen-mobile-dock__center-wrap" data-citizen-nav-center>
                    <div class="citizen-mobile-dock__burst" data-citizen-nav-burst>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="fsic" onclick="switchSection('fsic')" aria-label="Requests">
                            <span class="material-symbols-rounded citizen-mobile-dock__burst-icon" aria-hidden="true">folder_open</span>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="alerts" onclick="switchSection('alerts')" aria-label="Incidents and Alerts">
                            <span class="material-symbols-rounded citizen-mobile-dock__burst-icon" aria-hidden="true">warning</span>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-dock-item="contacts" onclick="switchSection('contacts')" aria-label="Emergency Contacts">
                            <span class="material-symbols-rounded citizen-mobile-dock__burst-icon" aria-hidden="true">call</span>
                        </button>
                        <button type="button" class="citizen-mobile-dock__burst-item" data-citizen-insights-open aria-label="Charts and Cards">
                            <span class="material-symbols-rounded citizen-mobile-dock__burst-icon" aria-hidden="true">stacked_bar_chart</span>
                        </button>
                    </div>

                    <button type="button" class="citizen-mobile-dock__item citizen-mobile-dock__item--center" data-citizen-nav-trigger onclick="openIncidentModal()" aria-label="Report incident">
                        <span class="material-symbols-rounded citizen-mobile-dock__icon" aria-hidden="true">report</span>
                    </button>
                </div>

                <button type="button" class="nav-item citizen-mobile-dock__item" data-citizen-dock-item="announcements" onclick="switchSection('announcements')" aria-label="Announcements">
                    <span class="material-symbols-rounded citizen-mobile-dock__icon" aria-hidden="true">campaign</span>
                </button>

                <button type="button" class="citizen-mobile-dock__item" data-citizen-settings-open aria-label="Settings">
                    <span class="material-symbols-rounded citizen-mobile-dock__icon" aria-hidden="true">settings</span>
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
            <div class="citizen-mobile-panel__header">
                <div></div>
                <button type="button" class="citizen-mobile-panel__close" data-citizen-map-layers-close aria-label="Close map filters and legend">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="citizen-mobile-panel__body citizen-map-sheet-section">
                <p class="citizen-map-sheet-label">Map Filter</p>
                <div class="citizen-map-layer-list">
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="stations" checked>
                        <span class="citizen-map-layer-item__icon material-symbols-rounded" aria-hidden="true">local_fire_department</span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Stations</strong>
                        </span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="incidents" checked>
                        <span class="citizen-map-layer-item__icon material-symbols-rounded" aria-hidden="true">crisis_alert</span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Incidents</strong>
                        </span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="hydrants" checked>
                        <span class="citizen-map-layer-item__icon material-symbols-rounded" aria-hidden="true">water_drop</span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Hydrants</strong>
                        </span>
                    </label>
                    <label class="citizen-map-layer-item">
                        <input type="checkbox" class="citizen-map-layer-item__toggle" data-citizen-map-layer-toggle="others" checked>
                        <span class="citizen-map-layer-item__icon material-symbols-rounded" aria-hidden="true">place</span>
                        <span class="citizen-map-layer-item__copy">
                            <strong>Places</strong>
                        </span>
                    </label>
                </div>
                <p class="citizen-map-sheet-label">Map Legend</p>
                <div class="citizen-map-legend-list">
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-user"><span class="citizen-map-legend-marker citizen-map-legend-marker-user"><i class="fas fa-user-shield"></i></span></span><span>Your location</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-station"><span class="citizen-map-legend-marker citizen-map-legend-marker-station"><i class="fas fa-building"></i></span></span><span>Fire station</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-incident"><span class="citizen-map-legend-marker citizen-map-legend-marker-incident"><i class="fas fa-fire"></i></span></span><span>Incident marker</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-hydrant"><span class="citizen-map-legend-marker citizen-map-legend-marker-hydrant"><i class="fas fa-fire-extinguisher"></i></span></span><span>Hydrant</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-other"><span class="citizen-map-legend-marker citizen-map-legend-marker-other"><i class="fas fa-map-marker-alt"></i></span></span><span>Other marker</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-route"><span class="citizen-map-legend-route-line"></span></span><span>Route</span></div>
                    <div class="citizen-map-legend-item-sheet"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-truck"><img src="{{ asset('storage/defaults/firetruck.png') }}" alt="Emergency truck"></span><span>Truck</span></div>
                </div>
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
        <div id="citizenMobileWelcomeToast" class="citizen-mobile-welcome-toast md:hidden" role="status" aria-live="polite">
            <div class="citizen-mobile-welcome-toast__icon">
                <span class="material-symbols-rounded" aria-hidden="true">local_fire_department</span>
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
                                <img src="{{ asset('storage/defaults/pyrosense.png') }}" alt="PyroSense logo" class="w-full h-full object-contain">
                            </div>
                            <span class="text-lg font-bold text-gray-800">Pyro<span class="text-orange-600">Sense</span></span>
                        </div>
                    </div>

                    <!-- desktop quick actions -->
                    <div class="hidden lg:flex items-center space-x-4">
                        <span class="text-sm text-gray-600"><i class="fa-regular fa-bell mr-1"></i> <span id="citizenTopUnreadValue">{{ $activeAlerts ?? 0 }}</span> unread</span>
                        <span class="text-sm text-gray-600"><i class="fa-regular fa-clock mr-1"></i> {{ now()->format('l, M d') }}</span>
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

        <!-- Report Incident Modal -->
        <div id="incidentModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center z-50 justify-center hidden">
            <div class="bg-white rounded-lg shadow-lg w-96 p-6">
                <h2 class="text-lg font-bold mb-4">Report Incident</h2>
                {{-- <form id="incidentForm" action="{{ route('citizen.incident.store') }}" method="POST"> --}}
                <form id="incidentForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                        @if($errors->any())
                            <div style="color:red;">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <input type="hidden" name=""  id="latitude" required>
                        <input type="hidden" name=""  id="longitude" required>
                            
                        {{-- <input type="hidden" name=""  id="marker_id_input" value=""> --}}
                        {{-- <input type="hidden" name="reported_by" value="{{ $accountId }}"> --}}
                        <label class="block mb-2 font-semibold">Attach Image</label>
                        <div class="file-upload-container">
                            <div class="file-upload-area" id="drop-area">
                                <div class="file-upload-message">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h3>Drag & Drop Files Here</h3>
                                <p>or</p>
                                {{-- <label for="myfile" class="file-upload-btn">Browse Files</label> --}}
                                {{-- <input type="file" id="fileUpload" multiple hidden> --}}
                                <input type="file" id="myfile" name="file" accept="image/*,video/*"  >

                                <p class="file-upload-info">Supports: JPG, PNG, PDF, DOC, MP4 (Max: 10MB)</p>
                                </div>
                                <div class="file-upload-preview" id="previewArea"></div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="" id="reported_by" value="{{ $accountId }}">
                    <input type="hidden" id="latitude" value="">
                    <input type="hidden" id="longitude" value="">
                    <div class="flex justify-end gap-2">
                        <button type="button" id="closeModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                        <button  id="saveReport" class="px-4 py-2 bg-blue-600 text-white rounded">Submit</button>
                    </div>
                </form>
            </div>
        </div>
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
            console.log('officer dashboard: ', window.stationOfficer);
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
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
    
    // Start periodic location updates
    setInterval(() => {
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
                
                console.log("Location updated for user:", window.currentUserId);
            },
            function(error) {
                console.error("Location update error:", error);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }, 30000); // Update every 30 seconds
    
    // Fetch all firefighters' locations every 15 seconds
    setInterval(() => {
        fetchAllFirefighterLocations();
    }, 15000);
    
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
            
            // Update URL hash without page reload
            window.location.hash = sectionId;
        }

        window.switchSection = switchSection;

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
            };

            const closeBurst = () => {
                citizenMobileDock.classList.remove('is-open');
            };

            const startHold = () => {
                clearHoldTimer();
                holdActivated = false;
                holdTimer = window.setTimeout(() => {
                    holdActivated = true;
                    openBurst();
                }, holdDelay);
            };

            citizenDockTrigger.addEventListener('pointerdown', startHold);
            citizenDockTrigger.addEventListener('pointerup', clearHoldTimer);
            citizenDockTrigger.addEventListener('pointerleave', clearHoldTimer);
            citizenDockTrigger.addEventListener('pointercancel', clearHoldTimer);

            citizenDockTrigger.addEventListener('click', function () {
                if (holdActivated) {
                    holdActivated = false;
                    return;
                }
                closeBurst();
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
            if (typeof window.syncCitizenMapLayerControls === 'function') {
                window.syncCitizenMapLayerControls();
            }
        };

        const closeCitizenMapLayersPanel = () => {
            citizenMapLayersPanel?.classList.remove('is-open');
            citizenMapLayersPanel?.setAttribute('aria-hidden', 'true');
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
            const hash = forcedSection || window.location.hash.substring(1) || 'overview';
            switchSection(hash, { skipLog: true });

            const shouldOpenIncidentModal = @json((bool) session('open_incident_modal'));
            if (shouldOpenIncidentModal) {
                window.setTimeout(function() {
                    document.getElementById('latitude').value = window.userCoords?.currentLatitude ?? '';
                    document.getElementById('longitude').value = window.userCoords?.currentLongitude ?? '';
                    openIncidentModal();
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
        function openIncidentModal() {
            document.getElementById('incidentModal').style.display = 'flex';
         
        }

        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('incidentModal').style.display = 'none';
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
document.getElementById('saveReport').addEventListener('click', async function(e) {
    e.preventDefault();

    const imageInput = document.getElementById('myfile');
    const description = '';
    const incidentTypeId = 1;
    const reporter = document.getElementById('reported_by').value;
    const loadingOverlay = document.getElementById('incidentReportLoadingOverlay');
    const loadingTitle = document.getElementById('incidentReportLoadingTitle');
    const loadingMessage = document.getElementById('incidentReportLoadingMessage');
    const showIncidentToast = window.showCitizenToast;

    // Check if location is ready
    if (!window.userCoords.ready || !window.userCoords.currentLatitude) {
        showIncidentToast?.('Getting your location. Please wait and try again.', true);
        return;
    }

    // Close modal early for better UX
    document.getElementById('incidentModal').style.display = 'none';

    // Show loading state
    const saveButton = document.getElementById('saveReport');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;
    if (loadingTitle) loadingTitle.textContent = 'Preparing report';
    if (loadingMessage) loadingMessage.textContent = 'Validating incident details...';
    loadingOverlay?.classList.remove('hidden');
    loadingOverlay?.classList.add('flex');

    try {
        // Save incident - pass the file input element, not the file object
        const success = await window.saveIncident(
            description,
            window.userCoords.currentLatitude,
            window.userCoords.currentLongitude,
            incidentTypeId,
            imageInput,
            reporter
        );

        if (success) {
            document.getElementById('incidentForm').reset();
            const previewArea = document.getElementById('previewArea');
            if (previewArea) {
                previewArea.innerHTML = '';
            }
            if (typeof window.refreshCitizenOverviewData === 'function') {
                window.refreshCitizenOverviewData();
            }
            showIncidentToast('Incident reported successfully.');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showIncidentToast('Failed to report incident: ' + error.message, true);
    } finally {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
        loadingOverlay?.classList.add('hidden');
        loadingOverlay?.classList.remove('flex');
    }
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

window.refreshCitizenOverviewData = async function() {
    if (!window.userCoords?.ready || !window.userCoords?.currentLatitude || !window.userCoords?.currentLongitude) {
        return;
    }

    try {
        const params = new URLSearchParams({
            lat: String(window.userCoords.currentLatitude),
            lng: String(window.userCoords.currentLongitude)
        });

        const response = await fetch(`/citizen/dashboard-data?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Dashboard refresh failed with status ${response.status}`);
        }

        const payload = await response.json();
        const data = payload?.data || {};
        const summary = data.summary || {};
        const nearbyIncidents = Array.isArray(data.nearby_incidents) ? data.nearby_incidents : [];
        const userIncidents = Array.isArray(data.user_incidents) ? data.user_incidents : [];
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
        window.renderCitizenWeeklyActivity(data.weekly_activity || null);

        const riskPercentageNode = document.getElementById('citizenOverviewRiskPercentageValue');
        if (riskPercentageNode) {
            riskPercentageNode.innerHTML = `${summary.risk_percentage ?? 0}<span class="text-lg font-medium">%</span>`;
        }

        if (typeof window.updateCitizenOverviewCharts === 'function') {
            window.updateCitizenOverviewCharts(Array.isArray(data.incidents) ? data.incidents : []);
        }

        if (typeof window.refreshCitizenMapSearchIndex === 'function') {
            window.refreshCitizenMapSearchIndex();
        }

        if (typeof window.refreshCitizenAlertsSection === 'function') {
            window.refreshCitizenAlertsSection(Array.isArray(nearbyIncidents) ? nearbyIncidents : []);
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
        console.error('Error refreshing citizen overview data:', error);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.renderCitizenWeeklyActivity();
});

window.runPendingIncidentAutoAssignment = async function() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        return;
    }

    try {
        const response = await fetch('/api/incidents/auto-assign-recent-unassigned', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        });
        const result = await response.json().catch(() => ({}));
        if (result?.success && Number(result.assigned) > 0) {
            if (window.refreshCitizenOverviewData) {
                window.refreshCitizenOverviewData();
            }
        }
    } catch (error) {
        console.error('Background incident auto-assignment failed:', error);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.runPendingIncidentAutoAssignment();
    window.setInterval(window.runPendingIncidentAutoAssignment, 10000);
});
        // Report incident triggers
        document.querySelectorAll('.report-incident-trigger').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                document.getElementById('latitude').value = window.userCoords.currentLatitude;
                document.getElementById('longitude').value =  window.userCoords.currentLongitude;
                openIncidentModal();
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

        document.addEventListener('DOMContentLoaded', function() {
  const dropArea = document.getElementById('drop-area');
  const fileInput = document.getElementById('myfile');
  const previewArea = document.getElementById('previewArea');
  
  // Max file size in bytes (10MB)
  const MAX_FILE_SIZE = 10 * 1024 * 1024;
  
  // Allowed file types
  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'video/mp4'];
  
  // Prevent default drag behaviors
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
  });
  
  // Highlight drop area when item is dragged over it
  ['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
  });
  
  ['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
  });
  
  // Handle dropped files
  dropArea.addEventListener('drop', handleDrop, false);
  
  // Handle files from input field
  fileInput.addEventListener('change', function() {
    fileInput._selectedFile = this.files && this.files.length > 0 ? this.files[0] : null;
    handleFiles(this.files);
  });
  
  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  function highlight() {
    dropArea.classList.add('active');
  }
  
  function unhighlight() {
    dropArea.classList.remove('active');
  }
  
  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;

    syncSelectedFiles(files);
    handleFiles(files);
  }
  
  function handleFiles(files) {
    const filesArray = Array.from(files);
    
    if (filesArray.length === 0) return;
    
    previewArea.innerHTML = '';

    // Process each file
    filesArray.forEach(file => {
      // Validate file type and size
      if (!validateFile(file)) return;
      
      // Create preview item
      const uploadItem = createUploadItem(file);
      previewArea.appendChild(uploadItem);
      
      // Simulate upload
      simulateUpload(uploadItem, file);
    });
    
    // Clear the input field to allow selecting the same file again
    // fileInput.value = '';
  }

  function syncSelectedFiles(files) {
    if (!files || files.length === 0) {
      return;
    }

    try {
      const dataTransfer = new DataTransfer();

      Array.from(files).forEach(file => {
        dataTransfer.items.add(file);
      });

      fileInput.files = dataTransfer.files;
      fileInput._selectedFile = dataTransfer.files[0] || null;
    } catch (error) {
      console.warn('Unable to sync dropped files to input:', error);
      fileInput._selectedFile = files[0] || null;
    }
  }
  
  function validateFile(file) {
    // Check file type
    if (!ALLOWED_TYPES.includes(file.type)) {
      window.showCitizenToast?.(`File type not allowed: ${file.type}`, true);
      return false;
    }
    
    // Check file size
    if (file.size > MAX_FILE_SIZE) {
      window.showCitizenToast?.(`File too large: ${file.name} (${formatFileSize(file.size)})`, true);
      return false;
    }
    
    return true;
  }
  
  function createUploadItem(file) {
    const item = document.createElement('div');
    item.className = 'upload-item';
    
    const iconClass = getFileIconClass(file.type);
    
    item.innerHTML = `
<div class="upload-item-icon">
  <i class="${iconClass}"></i>
</div>

<div class="upload-item-details">
  <div class="upload-item-name">${file.name}</div>
  <div class="upload-item-size">${formatFileSize(file.size)}</div>
  <div class="upload-item-progress-container">
    <div class="upload-item-progress" style="width: 0%"></div>
  </div>
</div>

<div class="upload-item-actions">
  <div class="upload-item-status">
    <i class="fas fa-spinner fa-spin"></i>
  </div>

  <button class="upload-item-remove">
    <i class="fas fa-times"></i>
  </button>
</div>
    `;
    
    // Add event listener to remove button
    item.querySelector('.upload-item-remove').addEventListener('click', function() {
      item.remove();
    });
    
    return item;
  }
  
  function simulateUpload(item, file) {
    const progressBar = item.querySelector('.upload-item-progress');
    const statusIcon = item.querySelector('.upload-item-status i');
    let progress = 0;
    
    // Simulate upload progress
    const interval = setInterval(() => {
      progress += Math.random() * 10;
      if (progress >= 100) {
        progress = 100;
        clearInterval(interval);
        
        // Update status icon
        statusIcon.className = 'fas fa-check';
        statusIcon.style.color = 'var(--upload-item-icon)';
      }
      
      progressBar.style.width = `${progress}%`;
    }, 200);
  }
  
  function getFileIconClass(fileType) {
    if (fileType.startsWith('image/')) {
      return 'fas fa-image';
    } else if (fileType === 'application/pdf') {
      return 'fas fa-file-pdf';
    } else if (fileType.includes('word')) {
      return 'fas fa-file-word';
    } else if (fileType.startsWith('video/')) {
      return 'fas fa-file-video';
    } else {
      return 'fas fa-file';
    }
  }
  
  function formatFileSize(bytes) {
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes === 0) return '0 Byte';
    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
  }
});
    </script>
    
@include('partials.user-activity-logger')
@include('citizen.partials.logout-confirmation')
@include('firefighters.partials.icon-fallback')
@include('partials.pwa-body')
</body>
</html>

