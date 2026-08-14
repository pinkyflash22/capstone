<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · Firefighter Incidents</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    <style data-ff-page-style="incidents">
        .incidents-shell { display: grid; gap: 24px; }
        .incidents-summary {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
            padding: 28px; border-radius: 24px;
            background: linear-gradient(135deg, #fff4ec 0%, #fff8f0 44%, #ffffff 100%);
            border: 1px solid rgba(198, 61, 38, 0.12); box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
        }
        .incidents-summary h1 { margin: 0; font-size: 2rem; font-weight: 800; color: #172033; }
        .incidents-summary p { margin: 8px 0 0; max-width: 720px; color: #64748b; font-size: .95rem; line-height: 1.55; }
        .summary-pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .summary-pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px;
            background: rgba(255,255,255,.86); color: #9a3412; border: 1px solid rgba(198,61,38,.12);
            font-size: .8rem; font-weight: 700; white-space: nowrap;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 18px; }
        .stat-card {
            position: relative; overflow: hidden; padding: 22px; border-radius: 22px; background: #fff;
            border: 1px solid rgba(226,232,240,.9); box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }
        .stat-card::after {
            content: ''; position: absolute; top: -24px; right: -24px; width: 92px; height: 92px; border-radius: 999px;
            background: linear-gradient(135deg, rgba(198,61,38,.09), rgba(209,138,22,.1));
        }
        .stat-card-label {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            margin-bottom: 16px; color: #64748b; font-size: .75rem; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase;
        }
        .stat-card-label i { color: #c63d26; font-size: 1.1rem; }
        .stat-card-value { font-size: 2rem; font-weight: 800; line-height: 1; color: #172033; }
        .stat-card-meta { margin-top: 10px; font-size: .85rem; color: #94a3b8; }
        .toolbar-card, .list-card {
            background: #fff; border: 1px solid rgba(226,232,240,.92); border-radius: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }
        .toolbar-card {
            padding: 18px 20px; display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(180px, .36fr) auto;
            gap: 14px; align-items: center;
        }
        .sort-select-wrap {
            position: relative;
            min-width: 0;
        }
        .sort-select-wrap::before {
            content: '\f0b0';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #c63d26;
            pointer-events: none;
            z-index: 1;
        }
        .sort-select-wrap .select-field {
            padding-left: 42px;
        }
        .search-field, .select-field {
            width: 100%; height: 48px; border-radius: 16px; border: 1px solid #e2e8f0; background: #fffaf6;
            color: #172033; padding: 0 16px; outline: none; font-size: .92rem;
        }
        .search-field:focus, .select-field:focus {
            border-color: rgba(198, 61, 38, .34); box-shadow: 0 0 0 4px rgba(198,61,38,.08);
        }
        .filter-tabs { display: flex; flex-wrap: wrap; gap: 10px; padding: 0 20px 20px; }
        .filter-tab {
            border: 0; cursor: pointer; padding: 10px 16px; border-radius: 999px; background: #fff7f3; color: #9a3412;
            font-size: .82rem; font-weight: 700; transition: all .2s ease;
        }
        .filter-tab.active {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color: #fff;
            box-shadow: 0 10px 20px rgba(198,61,38,.2);
        }
        .list-card-header {
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            padding: 22px 24px 18px; border-bottom: 1px solid rgba(226,232,240,.86);
        }
        .list-card-title { font-size: 1.15rem; font-weight: 800; color: #172033; }
        .list-card-subtitle { margin-top: 6px; color: #64748b; font-size: .9rem; }
        .results-pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px;
            background: #fff7f3; color: #c63d26; border: 1px solid rgba(198,61,38,.12); font-size: .82rem; font-weight: 800;
        }
        .incident-list { padding: 14px 24px 24px; display: grid; gap: 14px; }
        .incident-item {
            display: grid; grid-template-columns: 68px minmax(0, 1fr) auto; gap: 16px; align-items: start;
            padding: 18px; border-radius: 20px; background: linear-gradient(135deg, #ffffff 0%, #fff9f4 100%);
            border: 1px solid rgba(226,232,240,.92); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        }
        .incident-image {
            width: 68px; height: 68px; border-radius: 18px; overflow: hidden;
            background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%); border: 1px solid rgba(198,61,38,.12);
            display: flex; align-items: center; justify-content: center; color: #c63d26; font-size: 1.45rem;
        }
        .incident-image img { width: 100%; height: 100%; object-fit: cover; }
        .incident-main { min-width: 0; }
        .incident-topline { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }
        .incident-chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px;
            font-size: .74rem; font-weight: 800; letter-spacing: .04em;
        }
        .chip-type { background: #fff7f3; color: #c63d26; border: 1px solid rgba(198,61,38,.12); }
        .chip-active { background: #dbeafe; color: #1d4ed8; }
        .chip-controlled { background: #dcfce7; color: #15803d; }
        .chip-closed { background: #ede9fe; color: #7c3aed; }
        .incident-title { margin: 0; font-size: 1.02rem; font-weight: 800; color: #172033; }
        .incident-description {
            margin: 8px 0 0; color: #64748b; font-size: .9rem; line-height: 1.55; display: -webkit-box;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .incident-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; margin-top: 14px; }
        .incident-meta-item { font-size: .83rem; color: #475569; }
        .incident-meta-label {
            display: block; margin-bottom: 4px; color: #94a3b8; font-size: .72rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .incident-meta-item--secondary { display: block; }
        .incident-actions { display: flex; flex-direction: column; gap: 10px; min-width: 158px; }
        .incident-item.is-clickable { cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .incident-item.is-clickable:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
            border-color: rgba(198,61,38,.18);
        }
        .action-btn {
            display: inline-flex; justify-content: center; align-items: center; gap: 8px; height: 42px; padding: 0 16px;
            border-radius: 14px; border: 0; cursor: pointer; font-size: .85rem; font-weight: 700; transition: all .2s ease;
            text-decoration: none;
        }
        .action-btn.primary { background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color: #fff; }
        .action-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(198,61,38,.22); }
        .action-btn.ghost { background: #fff7f3; color: #9a3412; border: 1px solid rgba(198,61,38,.12); }
        .action-btn.investigation { background: #ecfeff; color: #0f766e; border: 1px solid rgba(20,184,166,.22); }
        .action-note {
            display: inline-flex; justify-content: center; align-items: center; gap: 8px; min-height: 38px; padding: 0 12px;
            border-radius: 14px; background: #f8fafc; color: #64748b; border: 1px solid rgba(226,232,240,.92);
            font-size: .8rem; font-weight: 800; text-align: center;
        }
        .list-empty { padding: 56px 20px; text-align: center; color: #94a3b8; }
        .list-empty i { font-size: 3rem; color: #cbd5e1; margin-bottom: 14px; }
        .shimmer-grid { padding: 14px 24px 24px; display: grid; gap: 14px; }
        .shimmer-card {
            display: grid; grid-template-columns: 68px minmax(0, 1fr) 160px; gap: 16px; padding: 18px;
            border-radius: 20px; background: #fff; border: 1px solid rgba(226,232,240,.92);
        }
        .shimmer-box { position: relative; overflow: hidden; background: #f1f5f9; border-radius: 14px; }
        .shimmer-box::after {
            content: ''; position: absolute; inset: 0; transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent); animation: shimmer 1.35s infinite;
        }
        .shimmer-avatar { width: 68px; height: 68px; border-radius: 18px; }
        .shimmer-lines { display: grid; gap: 10px; }
        .shimmer-line-lg { height: 18px; width: 45%; }
        .shimmer-line-md { height: 14px; width: 88%; }
        .shimmer-line-sm { height: 14px; width: 62%; }
        .shimmer-actions { display: grid; gap: 10px; }
        .shimmer-btn { height: 42px; width: 100%; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }
        .incident-list-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0 24px 24px;
            flex-wrap: wrap;
        }
        .incident-list-footer[hidden] { display: none !important; }
        .incident-pagination-summary {
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
        }
        .incident-pagination-controls {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .incident-pagination-indicator {
            color: #475569;
            font-size: .82rem;
            font-weight: 800;
        }
        .incident-pagination-button {
            min-height: 40px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid rgba(226,232,240,.92);
            background: #fff;
            color: #172033;
            font-size: .82rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all .18s ease;
        }
        .incident-pagination-button:hover:not(:disabled) {
            border-color: rgba(198,61,38,.22);
            color: #c63d26;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }
        .incident-pagination-button:disabled {
            cursor: not-allowed;
            opacity: .5;
            box-shadow: none;
        }
        .incident-modal {
            position: fixed; inset: 0; display: none; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, .58); z-index: 10030; padding: 20px; backdrop-filter: blur(10px);
        }
        .incident-modal.active { display: flex; }
        .incident-modal-panel {
            width: min(560px, 100%); max-height: min(78dvh, 720px); overflow: hidden;
            border-radius: 24px; background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(226,232,240,.95); box-shadow: 0 30px 70px rgba(15,23,42,.28);
        }
        .incident-modal-header {
            display: flex; justify-content: space-between; gap: 16px; align-items: center;
            padding: 16px 20px; border-bottom: 1px solid rgba(226,232,240,.92);
        }
        .incident-modal-title { font-size: 1rem; font-weight: 900; color: #0f172a; display: inline-flex; align-items: center; gap: 10px; }
        .incident-modal-subtitle { display: none; }
        .incident-modal-close {
            width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(226,232,240,.92);
            cursor: pointer; background: rgba(255,255,255,.94); color: #64748b; box-shadow: 0 10px 24px rgba(15,23,42,.08);
        }
        .incident-modal-body { padding: 18px 20px 20px; overflow-y: auto; max-height: calc(100vh - 140px); display: grid; gap: 16px; }
        .incident-sheet {
            display: grid;
            gap: 16px;
        }
        .incident-sheet__hero {
            display: grid;
            gap: 14px;
            padding: 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.98));
            border: 1px solid rgba(251, 191, 36, 0.2);
        }
        .incident-sheet__hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .incident-sheet__status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .4px;
        }
        .incident-sheet__type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: .55rem .8rem;
            border-radius: 999px;
            border: 1px solid rgba(226,232,240,.92);
            background: rgba(255,255,255,.92);
            color: #c63d26;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .incident-sheet__hero-body { display: grid; gap: 10px; }
        .incident-sheet__eyebrow {
            margin: 0;
            color: #d18a16;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .incident-sheet__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.2rem;
            line-height: 1.2;
            font-weight: 900;
        }
        .incident-sheet__subtitle {
            margin: -4px 0 0;
            color: #475569;
            font-size: .9rem;
            line-height: 1.45;
            font-weight: 700;
        }
        .incident-sheet__summary {
            margin: 0;
            color: #334155;
            font-size: .94rem;
            line-height: 1.6;
        }
        .incident-sheet .detail-image {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 16px;
            border: 1px solid rgba(226,232,240,.92);
        }
        .incident-sheet__section { display: grid; gap: 12px; }
        .incident-sheet__section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .incident-sheet__section-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: .96rem;
            font-weight: 900;
        }
        .incident-sheet__section-note {
            color: #64748b;
            font-size: .74rem;
            font-weight: 700;
        }
        .incident-sheet__meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .incident-sheet__meta-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 13px 14px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }
        .incident-sheet__meta-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(198,61,38,.08);
            color: #c63d26;
            font-size: 1rem;
        }
        .incident-sheet__meta-copy { display: grid; gap: 4px; min-width: 0; }
        .incident-sheet__meta-label {
            color: #64748b; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        }
        .incident-sheet__meta-value {
            color: #0f172a; font-size: .92rem; font-weight: 800; line-height: 1.4; word-break: break-word;
        }
        .incident-sheet__notes {
            padding: 14px 16px; border-radius: 16px; border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            color: #334155; font-size: .92rem; line-height: 1.65; white-space: pre-wrap; word-break: break-word;
        }
        .incident-sheet__footnote {
            display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
            padding: 12px 14px; border-radius: 14px; border: 1px dashed #cbd5e1; background: rgba(248,250,252,.88);
        }
        .incident-sheet__footnote-label {
            display: inline-flex; align-items: center; gap: 8px; color: #64748b; font-size: .78rem; font-weight: 800;
        }
        .incident-sheet__footnote-value {
            color: #0f172a; font-size: .84rem; font-weight: 800; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }
        .status-badge-reported { background: #fef3c7; color: #92400e; }
        .status-badge-responding { background: #dbeafe; color: #1d4ed8; }
        .status-badge-resolved { background: #dcfce7; color: #166534; }
        .status-badge-closed { background: #e5e7eb; color: #4b5563; }
        .incidents-mobile-title {
            display: none;
        }
        @media (max-width: 1280px) {
            .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .toolbar-card { grid-template-columns: 1fr 220px 180px; }
        }
        @media (max-width: 1024px) {
            .stats-grid, .detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .toolbar-card { grid-template-columns: 1fr; }
            .incidents-summary { flex-direction: column; }
            .incident-item, .shimmer-card { grid-template-columns: 56px minmax(0, 1fr); }
            .incident-actions, .shimmer-actions { grid-column: 1 / -1; min-width: 0; flex-direction: row; display: flex; }
        }
        @media (max-width: 720px) {
            .main-content { padding: max(10px, env(safe-area-inset-top, 0px)) 10px calc(104px + env(safe-area-inset-bottom, 0px)) !important; }
            .incidents-shell { gap: 10px; }
            .incidents-mobile-title {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 12px 14px;
                border-radius: 18px;
                background: rgba(255,255,255,.94);
                border: 1px solid rgba(226,232,240,.9);
            }
            .incidents-mobile-title__copy {
                min-width: 0;
            }
            .incidents-mobile-title__eyebrow {
                margin: 0 0 2px;
                color: #c63d26;
                font-size: .62rem;
                font-weight: 900;
                letter-spacing: .12em;
                text-transform: uppercase;
            }
            .incidents-mobile-title__heading {
                margin: 0;
                color: #172033;
                font-size: 1.05rem;
                font-weight: 900;
                line-height: 1.1;
            }
            .incidents-mobile-title__count {
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 40px;
                height: 34px;
                padding: 0 10px;
                border-radius: 999px;
                background: #fff7f3;
                color: #c63d26;
                border: 1px solid rgba(198,61,38,.12);
                font-size: .72rem;
                font-weight: 900;
            }
            .incidents-summary,
            .stats-grid {
                display: none !important;
            }
            .list-card {
                border-radius: 18px;
                box-shadow: none;
                border-color: rgba(226,232,240,.88);
                overflow: hidden;
            }
            .list-card-header {
                padding: 10px 14px 8px;
                align-items: flex-start;
            }
            .list-card-title { font-size: .98rem; }
            .list-card-subtitle { display: none; }
            .results-pill {
                padding: 7px 9px;
                gap: 5px;
                font-size: .68rem;
                white-space: nowrap;
            }
            .results-pill i { font-size: .72rem; }
            .toolbar-card {
                margin: 0 10px 10px;
                padding: 10px;
                gap: 8px;
                border-radius: 15px;
                box-shadow: none;
                border-color: rgba(226,232,240,.86);
                grid-template-columns: minmax(0, 1fr) 42px 42px;
            }
            .search-field,
            .select-field {
                height: 40px;
                border-radius: 12px;
                padding: 0 11px;
                font-size: .78rem;
            }
            .sort-select-wrap {
                width: 42px;
                height: 40px;
            }
            .sort-select-wrap::before {
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: .82rem;
            }
            .sort-select-wrap .select-field {
                width: 42px;
                min-width: 42px;
                padding: 0;
                color: transparent;
                text-shadow: none;
                cursor: pointer;
                appearance: none;
                -webkit-appearance: none;
                background: #fff7f3;
                border-color: rgba(198,61,38,.16);
            }
            .sort-select-wrap .select-field option {
                color: #172033;
            }
            #refreshIncidentsBtn {
                width: 42px;
                min-width: 42px;
                padding: 0;
                border-radius: 12px;
            }
            #refreshIncidentsBtn .action-btn__text {
                display: none;
            }
            .filter-tabs {
                padding: 0 10px 10px;
                gap: 7px;
                overflow-x: auto;
                flex-wrap: nowrap;
                scrollbar-width: none;
            }
            .filter-tabs::-webkit-scrollbar { display: none; }
            .filter-tab {
                padding: 7px 10px;
                font-size: .68rem;
                white-space: nowrap;
                box-shadow: none !important;
            }
            .incident-list,
            .shimmer-grid { padding: 8px 10px 12px; gap: 9px; }
            .incident-list-footer {
                padding: 0 10px 12px;
                gap: 8px;
            }
            .incident-pagination-summary,
            .incident-pagination-indicator {
                font-size: .7rem;
            }
            .incident-pagination-button {
                min-height: 36px;
                padding: 0 11px;
                border-radius: 11px;
                font-size: .72rem;
            }
            .incident-item {
                grid-template-columns: 40px minmax(0, 1fr);
                gap: 10px;
                padding: 11px;
                border-radius: 15px;
                box-shadow: none;
            }
            .incident-image {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                font-size: .95rem;
            }
            .incident-topline {
                gap: 5px;
                margin-bottom: 5px;
            }
            .incident-chip {
                gap: 4px;
                padding: 4px 7px;
                font-size: .58rem;
                letter-spacing: .025em;
            }
            .incident-chip i { font-size: .62rem; }
            .incident-title {
                font-size: .86rem;
                line-height: 1.25;
            }
            .incident-description {
                display: none;
            }
            .incident-meta {
                grid-template-columns: 1fr;
                gap: 5px;
                margin-top: 8px;
            }
            .incident-meta-item {
                font-size: .7rem;
                line-height: 1.3;
            }
            .incident-meta-label {
                margin-bottom: 1px;
                font-size: .56rem;
                letter-spacing: .04em;
            }
            .incident-meta-item--secondary {
                display: none;
            }
            .incident-actions,
            .shimmer-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 7px;
                min-width: 0;
            }
            .incident-actions .action-btn:first-child:last-child,
            .incident-actions .action-note:first-child:last-child {
                grid-column: 1 / -1;
            }
            .action-btn,
            .action-note {
                width: 100%;
                min-height: 36px;
                height: 36px;
                padding: 0 9px;
                border-radius: 11px;
                gap: 5px;
                font-size: .7rem;
            }
            .action-btn i,
            .action-note i { font-size: .72rem; }
            .list-empty {
                padding: 30px 14px;
            }
            .list-empty i {
                font-size: 1.9rem;
                margin-bottom: 8px;
            }
            .shimmer-card {
                grid-template-columns: 40px minmax(0, 1fr);
                gap: 10px;
                padding: 11px;
                border-radius: 15px;
            }
            .shimmer-avatar {
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }
            .shimmer-line-lg { height: 13px; width: 54%; }
            .shimmer-line-md { height: 10px; }
            .shimmer-line-sm { height: 10px; }
            .shimmer-btn { height: 34px; }
            .incident-modal { padding: 0 10px calc(90px + env(safe-area-inset-bottom, 0px)); align-items: flex-end; }
            .incident-modal-panel {
                width: 100%;
                max-height: min(58dvh, 520px);
                border-radius: 24px 24px 18px 18px;
                box-shadow: 0 -10px 28px rgba(15,23,42,.18);
            }
            .incident-modal-header {
                padding: 15px 16px 12px;
                align-items: center;
            }
            .incident-modal-title { font-size: .98rem; }
            .incident-modal-close {
                width: 40px;
                height: 40px;
                flex: 0 0 40px;
            }
            .incident-modal-body {
                padding: 14px 12px 16px;
                gap: 12px;
                max-height: calc(58dvh - 82px);
            }
            .incident-sheet__hero {
                padding: 15px;
                border-radius: 18px;
            }
            .incident-sheet__hero-top {
                align-items: flex-start;
                flex-direction: column;
            }
            .incident-sheet__meta-grid {
                grid-template-columns: 1fr;
            }
            .incident-sheet__meta-item {
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 10px;
                padding: 12px;
                border-radius: 14px;
            }
            .incident-sheet__meta-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                font-size: .92rem;
            }
            .incident-sheet__title {
                font-size: 1.06rem;
            }
            .incident-sheet__summary,
            .incident-sheet__notes,
            .incident-sheet__meta-value {
                font-size: .86rem;
            }
            .incident-sheet__footnote {
                align-items: flex-start;
                flex-direction: column;
            }
        }
        @media (max-width: 430px) {
            .main-content { padding-left: 8px; padding-right: 8px; }
            .toolbar-card { margin-left: 8px; margin-right: 8px; }
            .filter-tabs { padding-left: 8px; padding-right: 8px; }
            .incident-list,
            .shimmer-grid { padding-left: 8px; padding-right: 8px; }
            .incident-chip:nth-child(3) { display: none; }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'incidents'])

    <div data-ff-page-root="incidents">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'Incident Registry',
            'subtitle' => 'Live incident records responded to by your assigned team'
        ])

        <div class="incidents-shell">
            <section class="incidents-summary">
                <div>
                    <h1>Incident Registry</h1>
                    <p>This page only shows incidents responded to by your assigned firefighter team. Use the filters and sorting controls to review active, controlled, and closed operations without leaving the page.</p>
                </div>
                <div class="summary-pills">
                    <span class="summary-pill"><i class="fa-solid fa-user-shield"></i> {{ $stationOfficer['rank'] ?? 'Responder' }}</span>
                    <span class="summary-pill"><i class="fa-solid fa-building"></i> {{ $stationOfficer['station_name'] ?? 'No station assigned' }}</span>
                    <span class="summary-pill"><i class="fa-solid fa-people-group"></i> {{ $userTeam['team_name'] ?? 'No team assigned' }}</span>
                </div>
            </section>

            <section class="stats-grid" id="incidentStatsGrid">
                <article class="stat-card"><div class="stat-card-label"><span>Total Responded</span><i class="fa-solid fa-list-check"></i></div><div class="stat-card-value" id="statTotal">--</div><div class="stat-card-meta">All team incidents tied to your active responder assignment</div></article>
                <article class="stat-card"><div class="stat-card-label"><span>Active</span><i class="fa-solid fa-bolt"></i></div><div class="stat-card-value" id="statActive">--</div><div class="stat-card-meta">Currently responding or on-scene incidents</div></article>
                <article class="stat-card"><div class="stat-card-label"><span>Controlled</span><i class="fa-solid fa-shield"></i></div><div class="stat-card-value" id="statControlled">--</div><div class="stat-card-meta">Incidents completed under controlled status</div></article>
                <article class="stat-card"><div class="stat-card-label"><span>Closed</span><i class="fa-solid fa-folder-check"></i></div><div class="stat-card-value" id="statClosed">--</div><div class="stat-card-meta">Incidents finalized and closed</div></article>
                <article class="stat-card"><div class="stat-card-label"><span>Avg Response</span><i class="fa-solid fa-stopwatch"></i></div><div class="stat-card-value" id="statAvgResponse">--</div><div class="stat-card-meta">Based on completed incidents from report time to end time</div></article>
            </section>

            <section class="incidents-mobile-title" aria-label="Incident registry summary">
                <div class="incidents-mobile-title__copy">
                    <p class="incidents-mobile-title__eyebrow">Responder incidents</p>
                    <h1 class="incidents-mobile-title__heading">Incident Registry</h1>
                </div>
                <span class="incidents-mobile-title__count" id="mobileResultsCount">0</span>
            </section>

            <section class="list-card">
                <div class="list-card-header">
                    <div>
                        <div class="list-card-title">Team Incident List</div>
                        <div class="list-card-subtitle">Sortable, filterable, and updated live without reloading the firefighter portal.</div>
                    </div>
                    <div class="results-pill"><i class="fa-solid fa-fire-flame-curved"></i> <span id="resultsCount">0 incidents</span></div>
                </div>

                <div class="filter-tabs" id="statusTabs">
                    <button type="button" class="filter-tab active" data-filter="all">All</button>
                    <button type="button" class="filter-tab" data-filter="active">Available / Active</button>
                    <button type="button" class="filter-tab" data-filter="controlled">Controlled</button>
                    <button type="button" class="filter-tab" data-filter="closed">Closed</button>
                </div>

                <div class="toolbar-card">
                    <input type="search" id="incidentSearch" class="search-field" placeholder="Search incident, reporter, or location">
                    <span class="sort-select-wrap">
                        <select id="incidentSort" class="select-field" aria-label="Sort incidents" title="Sort incidents">
                            <option value="reported_desc">Newest reported</option>
                            <option value="reported_asc">Oldest reported</option>
                            <option value="updated_desc">Latest updated</option>
                            <option value="updated_asc">Oldest updated</option>
                        </select>
                    </span>
                    <button type="button" class="action-btn ghost" id="refreshIncidentsBtn" aria-label="Refresh incidents" title="Refresh incidents"><i class="fa-solid fa-rotate"></i> <span class="action-btn__text">Refresh</span></button>
                </div>

                <div id="incidentListState"></div>
            </section>
        </div>
    </main>

    <div class="incident-modal" id="incidentDetailModal">
        <div class="incident-modal-panel">
            <div class="incident-modal-header">
                <div>
                    <div class="incident-modal-title" id="incidentModalTitle">Incident Details</div>
                    <div class="incident-modal-subtitle" id="incidentModalSubtitle">Resolved live from firefighter registry data</div>
                </div>
                <button type="button" class="incident-modal-close" id="closeIncidentModal"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="incident-modal-body" id="incidentModalBody"></div>
        </div>
    </div>

    <script>
        const registryEndpoint = `{{ route('firefighter.incidents.registry') }}`;
        const INCIDENT_REGISTRY_POLL_INTERVAL_MS = 15000;
        const INCIDENT_PAGE_SIZE = 6;
        const incidentState = { incidents: [], filter: 'all', search: '', sort: 'reported_desc', currentPage: 1, poller: null, request: null, lastFetchedAt: 0 };
        const listState = document.getElementById('incidentListState');
        const resultsCount = document.getElementById('resultsCount');
        const mobileResultsCount = document.getElementById('mobileResultsCount');
        const sortSelect = document.getElementById('incidentSort');
        const searchInput = document.getElementById('incidentSearch');
        const statusTabs = document.getElementById('statusTabs');
        const refreshBtn = document.getElementById('refreshIncidentsBtn');

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        }

        function formatDateTime(value) {
            if (!value) return 'N/A';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        function formatMinutes(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
            const minutes = Number(value);
            if (minutes < 60) return `${minutes} min`;
            const hours = Math.floor(minutes / 60);
            const remainder = minutes % 60;
            return `${hours} hr ${remainder} min`;
        }

        function getIncidentTypeIcon(typeName = '') {
            const normalized = String(typeName).toLowerCase();
            if (normalized.includes('medical')) return 'fa-truck-medical';
            if (normalized.includes('rescue')) return 'fa-life-ring';
            if (normalized.includes('hazard')) return 'fa-radiation';
            if (normalized.includes('false')) return 'fa-circle-xmark';
            if (normalized.includes('accident')) return 'fa-car-burst';
            if (normalized.includes('disaster')) return 'fa-house-crack';
            return 'fa-fire';
        }

        function getStatusBucket(statusId) {
            const value = Number(statusId);
            if (value === 4) return 'controlled';
            if (value === 5) return 'closed';
            return 'active';
        }

        function getStatusChipClass(statusId) {
            const bucket = getStatusBucket(statusId);
            if (bucket === 'controlled') return 'chip-controlled';
            if (bucket === 'closed') return 'chip-closed';
            return 'chip-active';
        }

        function resolveStorageUrl(path) {
            if (!path) return null;
            const normalized = String(path).trim();
            if (!normalized) return null;
            if (normalized.startsWith('http://') || normalized.startsWith('https://') || normalized.startsWith('/storage/')) return normalized;
            return `/storage/${normalized.replace(/^\/+/, '')}`;
        }

        function renderShimmerState() {
            if (!listState) return;
            listState.innerHTML = `<div class="shimmer-grid">${Array.from({ length: 4 }).map(() => `
                <div class="shimmer-card">
                    <div class="shimmer-box shimmer-avatar"></div>
                    <div class="shimmer-lines">
                        <div class="shimmer-box shimmer-line-lg"></div>
                        <div class="shimmer-box shimmer-line-md"></div>
                        <div class="shimmer-box shimmer-line-sm"></div>
                        <div class="shimmer-box shimmer-line-md"></div>
                    </div>
                    <div class="shimmer-actions">
                        <div class="shimmer-box shimmer-btn"></div>
                        <div class="shimmer-box shimmer-btn"></div>
                    </div>
                </div>`).join('')}</div>`;
        }

        function updateStats(stats = {}) {
            const setStatText = (id, value) => {
                const target = document.getElementById(id);
                if (target) target.textContent = value;
            };

            setStatText('statTotal', stats.total ?? 0);
            setStatText('statActive', stats.active ?? 0);
            setStatText('statControlled', stats.controlled ?? 0);
            setStatText('statClosed', stats.closed ?? 0);
            setStatText('statAvgResponse', `${stats.avg_response_minutes ?? 0}m`);
        }

        function getFilteredIncidents() {
            const search = incidentState.search.trim().toLowerCase();
            const filtered = incidentState.incidents.filter((incident) => {
                const bucket = getStatusBucket(incident.incident_status_id);
                if (incidentState.filter !== 'all' && bucket !== incidentState.filter) return false;
                if (!search) return true;
                const haystack = [incident.incident_id, incident.title, incident.description, incident.location, incident.reporter_name, incident.incident_type_name, incident.incident_status_name]
                    .map(value => String(value || '').toLowerCase()).join(' ');
                return haystack.includes(search);
            });

            filtered.sort((left, right) => {
                switch (incidentState.sort) {
                    case 'reported_asc': return new Date(left.reported_at || 0) - new Date(right.reported_at || 0);
                    case 'updated_desc': return new Date(right.updated_at || 0) - new Date(left.updated_at || 0);
                    case 'updated_asc': return new Date(left.updated_at || 0) - new Date(right.updated_at || 0);
                    case 'reported_desc':
                    default: return new Date(right.reported_at || 0) - new Date(left.reported_at || 0);
                }
            });

            return filtered;
        }

        function renderIncidentPagination(totalItems, totalPages, currentPage) {
            const hidden = totalItems <= 0 || totalPages <= 1;
            return `
                <div class="incident-list-footer" ${hidden ? 'hidden' : ''}>
                    <div class="incident-pagination-summary">
                        Showing ${((currentPage - 1) * INCIDENT_PAGE_SIZE) + 1}-${Math.min(currentPage * INCIDENT_PAGE_SIZE, totalItems)} of ${totalItems} incidents
                    </div>
                    <div class="incident-pagination-controls">
                        <button type="button" class="incident-pagination-button" data-incident-page-action="prev" ${currentPage <= 1 ? 'disabled' : ''}>
                            <i class="fa-solid fa-chevron-left"></i>
                            Previous
                        </button>
                        <span class="incident-pagination-indicator">Page ${currentPage} of ${totalPages}</span>
                        <button type="button" class="incident-pagination-button" data-incident-page-action="next" ${currentPage >= totalPages ? 'disabled' : ''}>
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        function renderIncidentList() {
            const incidents = getFilteredIncidents();
            if (!listState) return;
            if (resultsCount) {
                resultsCount.textContent = `${incidents.length} incident${incidents.length === 1 ? '' : 's'}`;
            }
            if (mobileResultsCount) {
                mobileResultsCount.textContent = String(incidents.length);
            }

            if (!incidents.length) {
                listState.innerHTML = `<div class="list-empty"><i class="fa-solid fa-folder-open"></i><div class="text-lg font-semibold text-slate-700">No incidents match the current filters.</div><div class="mt-2 text-sm text-slate-500">Try clearing the search or switching to another incident category.</div></div>`;
                return;
            }

            const totalPages = Math.max(1, Math.ceil(incidents.length / INCIDENT_PAGE_SIZE));
            incidentState.currentPage = Math.min(Math.max(1, incidentState.currentPage), totalPages);
            const startIndex = (incidentState.currentPage - 1) * INCIDENT_PAGE_SIZE;
            const pagedIncidents = incidents.slice(startIndex, startIndex + INCIDENT_PAGE_SIZE);

            listState.innerHTML = `<div class="incident-list">${pagedIncidents.map((incident) => {
                const imageUrl = resolveStorageUrl(incident.image_url);
                const statusBucket = getStatusBucket(incident.incident_status_id);
                const statusLabel = statusBucket === 'active' ? 'Available / Active' : (statusBucket === 'controlled' ? 'Controlled' : 'Closed');
                const investigationAction = incident.investigation_ready && incident.investigation_url
                    ? `<a class="action-btn investigation" href="${escapeHtml(incident.investigation_url)}"><i class="fa-solid fa-magnifying-glass"></i> Start Spot Report</a>`
                    : (incident.has_spot_report ? `<span class="action-note"><i class="fa-solid fa-file-circle-check"></i> Spot report filed</span>` : '');
                return `
                    <article class="incident-item is-clickable" data-incident-row data-incident-id="${incident.incident_id}" role="button" tabindex="0" aria-label="Open incident details for ${escapeHtml(incident.title || `Incident ${incident.incident_id}`)}">
                        <div class="incident-image">
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Incident Image">` : `<i class="fa-solid ${getIncidentTypeIcon(incident.incident_type_name)}"></i>`}
                        </div>
                        <div class="incident-main">
                            <div class="incident-topline">
                                <span class="incident-chip chip-type"><i class="fa-solid ${getIncidentTypeIcon(incident.incident_type_name)}"></i>${escapeHtml(incident.incident_type_name || 'Incident')}</span>
                                <span class="incident-chip ${getStatusChipClass(incident.incident_status_id)}">${escapeHtml(statusLabel)}</span>
                            </div>
                            <h3 class="incident-title">${escapeHtml(incident.title || `Incident #${incident.incident_id}`)}</h3>
                            <p class="incident-description">${escapeHtml(incident.description || 'No incident description was recorded for this response.')}</p>
                            <div class="incident-meta">
                                <div class="incident-meta-item"><span class="incident-meta-label">Location</span>${escapeHtml(incident.location || 'Unknown location')}</div>
                                <div class="incident-meta-item incident-meta-item--secondary"><span class="incident-meta-label">Reported By</span>${escapeHtml(incident.reporter_name || 'Concerned Citizen')}</div>
                                <div class="incident-meta-item"><span class="incident-meta-label">Reported At</span>${escapeHtml(formatDateTime(incident.reported_at))}</div>
                                <div class="incident-meta-item incident-meta-item--secondary"><span class="incident-meta-label">Last Updated</span>${escapeHtml(formatDateTime(incident.updated_at))}</div>
                            </div>
                        </div>
                        <div class="incident-actions" data-incident-actions>
                            ${investigationAction}
                            <button type="button" class="action-btn ghost" onclick="copyIncidentId(${incident.incident_id})"><i class="fa-regular fa-copy"></i> Copy ID</button>
                        </div>
                    </article>`;
            }).join('')}</div>${renderIncidentPagination(incidents.length, totalPages, incidentState.currentPage)}`;

            listState.querySelectorAll('[data-incident-row]').forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('[data-incident-actions]')) {
                        return;
                    }

                    openIncidentDetails(row.dataset.incidentId);
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    if (event.target.closest('[data-incident-actions]')) {
                        return;
                    }

                    event.preventDefault();
                    openIncidentDetails(row.dataset.incidentId);
                });
            });

            listState.querySelector('[data-incident-page-action="prev"]')?.addEventListener('click', () => {
                incidentState.currentPage = Math.max(1, incidentState.currentPage - 1);
                renderIncidentList();
            });

            listState.querySelector('[data-incident-page-action="next"]')?.addEventListener('click', () => {
                incidentState.currentPage = Math.min(totalPages, incidentState.currentPage + 1);
                renderIncidentList();
            });
        }

        function showToast(message, isError = false) {
            if (window.showCitizenToast) {
                window.showCitizenToast(message, isError, 3200);
                return;
            }
            console[isError ? 'error' : 'log'](message);
        }

        async function fetchIncidentRegistry(showLoading = true) {
            if (document.visibilityState !== 'visible') {
                return;
            }

            const now = Date.now();
            if (!showLoading && incidentState.request) {
                return incidentState.request;
            }
            if (!showLoading && incidentState.lastFetchedAt && (now - incidentState.lastFetchedAt) < INCIDENT_REGISTRY_POLL_INTERVAL_MS) {
                return;
            }

            if (showLoading) renderShimmerState();

            try {
                incidentState.request = fetch(registryEndpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
                const response = await incidentState.request;
                const result = await response.json();
                if (!response.ok || result.success === false) throw new Error(result.message || 'Failed to fetch incident registry.');

                incidentState.incidents = Array.isArray(result.data?.incidents) ? result.data.incidents : [];
                incidentState.lastFetchedAt = Date.now();
                updateStats(result.data?.stats || {});
                renderIncidentList();
                syncOpenIncidentDetails();
            } catch (error) {
                console.error('Incident registry fetch failed:', error);
                if (listState) {
                    listState.innerHTML = `<div class="list-empty"><i class="fa-solid fa-triangle-exclamation"></i><div class="text-lg font-semibold text-slate-700">Failed to load incident records.</div><div class="mt-2 text-sm text-slate-500">${escapeHtml(error.message || 'Unknown error')}</div></div>`;
                }
            } finally {
                incidentState.request = null;
            }
        }

        function syncOpenIncidentDetails() {
            const modal = document.getElementById('incidentDetailModal');
            const incidentId = modal?.dataset.incidentId;

            if (!modal?.classList.contains('active') || !incidentId) {
                return;
            }

            if (!incidentState.incidents.some((incident) => Number(incident.incident_id) === Number(incidentId))) {
                closeIncidentDetails();
                return;
            }

            openIncidentDetails(incidentId, { silent: true });
        }

        function openIncidentDetails(incidentId, options = {}) {
            const incident = incidentState.incidents.find(item => Number(item.incident_id) === Number(incidentId));
            if (!incident) {
                showToast('Incident details not found.', true);
                return;
            }

            const modal = document.getElementById('incidentDetailModal');
            const modalBody = document.getElementById('incidentModalBody');
            const modalTitle = document.getElementById('incidentModalTitle');
            const modalSubtitle = document.getElementById('incidentModalSubtitle');
            const imageUrl = resolveStorageUrl(incident.image_url);

            const statusId = Number(incident.incident_status_id);
            const incidentTypeName = incident.incident_type_name || 'Incident';
            const incidentTypeIcon = getIncidentTypeIcon(incidentTypeName);
            const statusText = statusId === 5 ? 'CLOSED' : (statusId === 4 ? 'CONTROLLED' : 'ACTIVE');
            const statusClass = statusId === 5 ? 'status-badge-closed' : (statusId === 4 ? 'status-badge-resolved' : 'status-badge-responding');
            const statusIcon = statusId === 5 ? 'fa-folder' : (statusId === 4 ? 'fa-check-circle' : 'fa-bell');
            const locationHeadline = incident.location || incident.title || `Incident #${incident.incident_id}`;
            const incidentSubtitle = incident.title && incident.title !== locationHeadline ? incident.title : null;
            const description = incident.description || 'No incident description was recorded for this incident.';
            const reportedBy = incident.reporter_name || 'Concerned Citizen';
            const roleLabel = incident.reporter_role || 'Citizen';
            const createdAt = formatDateTime(incident.reported_at);
            const updatedAt = formatDateTime(incident.updated_at);
            const hasCoordinates = incident.coordinates?.lat !== null && incident.coordinates?.lat !== undefined && incident.coordinates?.lng !== null && incident.coordinates?.lng !== undefined;
            const overviewItems = [
                { icon: 'fa-location-dot', label: 'Location', value: escapeHtml(locationHeadline) },
                { icon: 'fa-user', label: 'Reporter', value: escapeHtml(reportedBy) },
                { icon: 'fa-user-shield', label: 'Source', value: escapeHtml(roleLabel) },
                { icon: 'fa-calendar-days', label: 'Reported', value: escapeHtml(createdAt) },
            ];

            if (incident.team_name) {
                overviewItems.push({ icon: 'fa-users', label: 'Assigned Team', value: escapeHtml(incident.team_name) });
            }

            if (incident.response_minutes !== null && incident.response_minutes !== undefined) {
                overviewItems.push({ icon: 'fa-stopwatch', label: 'Response Duration', value: escapeHtml(formatMinutes(incident.response_minutes)) });
            }

            if (incident.updated_at) {
                overviewItems.push({ icon: 'fa-clock', label: 'Last Updated', value: escapeHtml(updatedAt) });
            }

            const overviewMarkup = overviewItems.map((item) => `
                <div class="incident-sheet__meta-item">
                    <span class="incident-sheet__meta-icon"><i class="fa-solid ${item.icon}"></i></span>
                    <div class="incident-sheet__meta-copy">
                        <span class="incident-sheet__meta-label">${item.label}</span>
                        <strong class="incident-sheet__meta-value">${item.value}</strong>
                    </div>
                </div>
            `).join('');

            modalTitle.innerHTML = `<i class="fa-solid fa-file-alt"></i> Incident Details`;
            modalSubtitle.textContent = '';
            modalBody.innerHTML = `
                <div class="incident-sheet">
                    <section class="incident-sheet__hero">
                        <div class="incident-sheet__hero-top">
                            <span class="incident-sheet__status ${statusClass}">
                                <i class="fa-solid ${statusIcon}"></i>
                                <span>${statusText}</span>
                            </span>
                            <span class="incident-sheet__type">
                                <i class="fa-solid ${incidentTypeIcon}"></i>
                                ${escapeHtml(incidentTypeName)}
                            </span>
                        </div>
                        <div class="incident-sheet__hero-body">
                            <p class="incident-sheet__eyebrow">Response Summary</p>
                            <h2 class="incident-sheet__title">${escapeHtml(locationHeadline)}</h2>
                            ${incidentSubtitle ? `<p class="incident-sheet__subtitle">${escapeHtml(incidentSubtitle)}</p>` : ''}
                            <p class="incident-sheet__summary">${escapeHtml(description)}</p>
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Incident Image" class="detail-image">` : ''}
                        </div>
                    </section>

                    <section class="incident-sheet__section">
                        <div class="incident-sheet__section-head">
                            <h3>Overview</h3>
                            <span class="incident-sheet__section-note">Operational details only</span>
                        </div>
                        <div class="incident-sheet__meta-grid">${overviewMarkup}</div>
                    </section>

                    <section class="incident-sheet__section">
                        <div class="incident-sheet__section-head">
                            <h3>Incident Notes</h3>
                        </div>
                        <div class="incident-sheet__notes">${escapeHtml(description)}</div>
                    </section>

                    ${hasCoordinates ? `
                    <section class="incident-sheet__footnote">
                        <span class="incident-sheet__footnote-label"><i class="fa-solid fa-location-crosshairs"></i> Coordinates</span>
                        <span class="incident-sheet__footnote-value">${escapeHtml(`${incident.coordinates?.lat}, ${incident.coordinates?.lng}`)}</span>
                    </section>
                    ` : ''}
                </div>
            `;
            modal.dataset.incidentId = String(incident.incident_id);
            modal.classList.add('active');
            return;

            modalTitle.textContent = incident.title || `Incident #${incident.incident_id}`;
            modalSubtitle.textContent = `${incident.incident_type_name || 'Incident'} · ${incident.incident_status_name || 'Unknown status'} · #${incident.incident_id}`;
            modalBody.innerHTML = `
                ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Incident Image" class="detail-image">` : ''}
                <div class="detail-grid">
                    <div class="detail-card"><div class="detail-label">Incident Type</div><div class="detail-value">${escapeHtml(incident.incident_type_name || 'N/A')}</div></div>
                    <div class="detail-card"><div class="detail-label">Status</div><div class="detail-value">${escapeHtml(incident.incident_status_name || 'N/A')}</div></div>
                    <div class="detail-card"><div class="detail-label">Reported By</div><div class="detail-value">${escapeHtml(incident.reporter_name || 'Concerned Citizen')}</div></div>
                    <div class="detail-card"><div class="detail-label">Team</div><div class="detail-value">${escapeHtml(incident.team_name || 'N/A')}</div></div>
                    <div class="detail-card"><div class="detail-label">Reported At</div><div class="detail-value">${escapeHtml(formatDateTime(incident.reported_at))}</div></div>
                    <div class="detail-card"><div class="detail-label">Ended / Updated At</div><div class="detail-value">${escapeHtml(formatDateTime(incident.updated_at))}</div></div>
                    <div class="detail-card"><div class="detail-label">Response Duration</div><div class="detail-value">${escapeHtml(formatMinutes(incident.response_minutes))}</div></div>
                    <div class="detail-card"><div class="detail-label">Coordinates</div><div class="detail-value">${escapeHtml(`${incident.coordinates?.lat ?? 'N/A'}, ${incident.coordinates?.lng ?? 'N/A'}`)}</div></div>
                    <div class="detail-card"><div class="detail-label">Marker ID</div><div class="detail-value">${escapeHtml(incident.marker_id || 'N/A')}</div></div>
                    <div class="detail-card"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(incident.location || 'N/A')}</div></div>
                </div>
                <div class="detail-description">
                    <h3>Incident Narrative</h3>
                    <p>${escapeHtml(incident.description || 'No narrative was recorded for this incident.')}</p>
                </div>
            `;
            modal.classList.add('active');
        }

        function closeIncidentDetails() {
            const modal = document.getElementById('incidentDetailModal');
            modal.classList.remove('active');
            delete modal.dataset.incidentId;
        }

        async function copyIncidentId(incidentId) {
            try {
                await navigator.clipboard.writeText(String(incidentId));
                showToast(`Incident #${incidentId} copied.`);
            } catch (error) {
                showToast('Failed to copy incident ID.', true);
            }
        }

        function bindEvents() {
            statusTabs.addEventListener('click', (event) => {
                const tab = event.target.closest('.filter-tab');
                if (!tab) return;
                incidentState.filter = tab.dataset.filter || 'all';
                incidentState.currentPage = 1;
                document.querySelectorAll('.filter-tab').forEach(button => button.classList.toggle('active', button === tab));
                renderIncidentList();
            });
            searchInput.addEventListener('input', (event) => { incidentState.search = event.target.value || ''; incidentState.currentPage = 1; renderIncidentList(); });
            sortSelect.addEventListener('change', (event) => { incidentState.sort = event.target.value || 'reported_desc'; incidentState.currentPage = 1; renderIncidentList(); });
            refreshBtn.addEventListener('click', () => fetchIncidentRegistry(true));
            document.getElementById('closeIncidentModal').addEventListener('click', closeIncidentDetails);
            document.getElementById('incidentDetailModal').addEventListener('click', (event) => {
                if (event.target.id === 'incidentDetailModal') closeIncidentDetails();
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            bindEvents();
            fetchIncidentRegistry(true);
            incidentState.poller = window.setInterval(() => fetchIncidentRegistry(false), INCIDENT_REGISTRY_POLL_INTERVAL_MS);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    fetchIncidentRegistry(false);
                }
            });
            window.__ffCurrentPageCleanup = function () {
                if (incidentState.poller) {
                    window.clearInterval(incidentState.poller);
                    incidentState.poller = null;
                }
            };
        });

        window.openIncidentDetails = openIncidentDetails;
        window.copyIncidentId = copyIncidentId;
    </script>
    </div>
    @include('partials.pwa-body')
</body>
</html>
