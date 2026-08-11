@php
    $nearbyAlertCollection = collect($nearbyIncidents ?? [])->values();
    $combinedReportCollection = collect($citizenCombinedReports ?? [])->values();
    $riskMapCollection = collect($riskMapReports ?? [])->values();
    $initialAlertStatuses = $nearbyAlertCollection
        ->pluck('incident_status')
        ->filter()
        ->map(fn ($status) => ucwords(str_replace(['_', '-'], ' ', strtolower((string) $status))))
        ->unique()
        ->values();
@endphp

<style>
    #section-alerts.areas-concern-page {
        --aoc-red: #c63d26;
        --aoc-red-dark: #9f2f1d;
        --aoc-orange: #e39b17;
        --aoc-cream: #fff8ed;
        --aoc-ink: #132033;
        --aoc-muted: #64748b;
        --aoc-line: rgba(198, 61, 38, 0.14);
        --aoc-shadow: 0 22px 54px rgba(88, 43, 20, 0.1);
        width: min(100%, 1240px);
        margin-inline: auto;
        padding: clamp(0.85rem, 1.45vw, 1.35rem);
        padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        color: var(--aoc-ink);
        position: relative;
        z-index: 1;
    }

    #section-alerts .aoc-shell {
        display: grid;
        gap: clamp(1rem, 2vw, 1.35rem);
    }

    #section-alerts .aoc-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(198, 61, 38, 0.16);
        border-radius: 2rem;
        background:
            radial-gradient(circle at 92% 12%, rgba(227, 155, 23, 0.24), transparent 16rem),
            linear-gradient(135deg, rgba(255, 248, 237, 0.98), rgba(255, 255, 255, 0.96));
        box-shadow: var(--aoc-shadow);
        padding: clamp(1.1rem, 3vw, 1.75rem);
    }

    #section-alerts .aoc-hero::before {
        content: '';
        position: absolute;
        inset: auto -4rem -6rem auto;
        width: 15rem;
        height: 15rem;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.08);
        pointer-events: none;
    }

    #section-alerts .aoc-hero-grid {
        position: relative;
        display: grid;
        gap: 1.1rem;
    }

    #section-alerts .aoc-eyebrow,
    #section-alerts .aoc-section-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        width: fit-content;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.1);
        color: var(--aoc-red-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 0.45rem 0.7rem;
    }

    #section-alerts .aoc-title {
        margin-top: 0.8rem;
        color: var(--aoc-ink);
        font-size: clamp(1.85rem, 6vw, 3rem);
        font-weight: 900;
        line-height: 0.98;
        letter-spacing: -0.055em;
    }

    #section-alerts .aoc-subtitle {
        max-width: 45rem;
        margin-top: 0.75rem;
        color: var(--aoc-muted);
        font-size: clamp(0.94rem, 2.4vw, 1.03rem);
        line-height: 1.72;
    }

    #section-alerts .aoc-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    #section-alerts .aoc-alert-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        min-height: 44px;
        border-radius: 999px;
        border: 1px solid rgba(239, 68, 68, 0.16);
        background: linear-gradient(135deg, #fff1f2, #fff7ed);
        color: #b91c1c;
        padding: 0.72rem 1rem;
        font-size: 0.88rem;
        font-weight: 850;
        box-shadow: 0 12px 30px rgba(198, 61, 38, 0.1);
    }

    #section-alerts .aoc-history-btn {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 0.62rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--aoc-red), var(--aoc-orange));
        color: #fff;
        padding: 0.82rem 1.1rem;
        font-size: 0.92rem;
        font-weight: 850;
        box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
    }

    #section-alerts .aoc-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    #section-alerts .aoc-stat-card,
    #section-alerts .aoc-panel,
    #section-alerts .aoc-map-card,
    #section-alerts .aoc-snapshot {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
    }

    #section-alerts .aoc-stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.6rem;
        padding: 1.1rem;
    }

    #section-alerts .aoc-stat-card::after {
        content: '';
        position: absolute;
        inset: auto -2rem -3rem auto;
        width: 8rem;
        height: 8rem;
        border-radius: 999px;
        background: var(--aoc-stat-glow, rgba(198, 61, 38, 0.08));
    }

    #section-alerts .aoc-stat-top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.8rem;
    }

    #section-alerts .aoc-stat-label {
        color: var(--aoc-muted);
        font-size: 0.82rem;
        font-weight: 750;
    }

    #section-alerts .aoc-stat-value {
        margin-top: 0.42rem;
        color: var(--aoc-ink);
        font-size: clamp(2rem, 6vw, 2.65rem);
        font-weight: 950;
        letter-spacing: -0.055em;
        line-height: 1;
    }

    #section-alerts .aoc-stat-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        flex: 0 0 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 1.05rem;
        background: var(--aoc-icon-bg, #fff1f2);
        color: var(--aoc-icon-color, #dc2626);
        font-size: 1.1rem;
    }

    #section-alerts .aoc-stat-copy {
        position: relative;
        z-index: 1;
        margin-top: 0.9rem;
        color: var(--aoc-muted);
        font-size: 0.78rem;
        line-height: 1.55;
    }

    #section-alerts .aoc-panel,
    #section-alerts .aoc-map-card,
    #section-alerts .aoc-snapshot {
        overflow: hidden;
        border-radius: 1.75rem;
        position: relative;
        z-index: 0;
    }

    #section-alerts .aoc-panel-head,
    #section-alerts .aoc-map-head,
    #section-alerts .aoc-snapshot-head {
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(135deg, rgba(255, 248, 237, 0.74), rgba(255, 255, 255, 0.96));
        padding: 1.1rem;
    }

    #section-alerts .aoc-panel-head-row,
    #section-alerts .aoc-map-head-row {
        display: grid;
        gap: 1rem;
    }

    #section-alerts .aoc-section-title {
        margin-top: 0.55rem;
        color: var(--aoc-ink);
        font-size: clamp(1.1rem, 3vw, 1.35rem);
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    #section-alerts .aoc-section-copy {
        margin-top: 0.35rem;
        color: var(--aoc-muted);
        font-size: 0.88rem;
        line-height: 1.62;
    }

    #section-alerts .aoc-filter-row {
        display: grid;
        gap: 0.65rem;
    }

    #section-alerts .aoc-search-wrap {
        position: relative;
    }

    #section-alerts .aoc-search-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 0.85rem;
    }

    #section-alerts .aoc-input,
    #section-alerts .aoc-select {
        width: 100%;
        min-height: 46px;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #fff;
        color: #334155;
        font-size: 0.9rem;
        outline: none;
    }

    #section-alerts .aoc-input {
        padding: 0.75rem 1rem 0.75rem 2.5rem;
    }

    #section-alerts .aoc-select {
        padding: 0.75rem 0.95rem;
    }

    #section-alerts .aoc-filter-control {
        position: relative;
        min-width: 0;
    }

    #section-alerts .aoc-filter-icon {
        display: none;
    }

    #section-alerts .aoc-filter-button,
    #section-alerts .aoc-filter-menu {
        display: none;
    }

    #section-alerts .aoc-map-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.1rem;
    }

    #section-alerts .aoc-map-body,
    #section-alerts .aoc-snapshot-body {
        padding: 1.1rem;
    }

    #section-alerts #areasConcernRiskMap {
        min-height: 24rem;
        border-radius: 1.4rem !important;
        border-color: rgba(198, 61, 38, 0.14) !important;
        position: relative;
        z-index: 0;
    }

    #section-alerts #areasConcernRiskMap .leaflet-pane,
    #section-alerts #areasConcernRiskMap .leaflet-top,
    #section-alerts #areasConcernRiskMap .leaflet-bottom,
    #section-alerts #areasConcernRiskMap .leaflet-control,
    #section-alerts #areasConcernRiskMap .leaflet-popup {
        z-index: 1;
    }

    #section-alerts .aoc-map-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        width: fit-content;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        padding: 0.55rem 0.8rem;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
    }

    #section-alerts .aoc-mini-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    #section-alerts .aoc-mini-card {
        border-radius: 1.25rem;
        padding: 1rem;
        background: linear-gradient(135deg, #fff7ed, #fff);
        border: 1px solid rgba(198, 61, 38, 0.1);
    }

    #section-alerts .aoc-alert-card {
        padding: 1rem;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    #section-alerts .aoc-alert-card:hover {
        background: rgba(255, 247, 237, 0.58);
    }

    #section-alerts .aoc-alert-main {
        display: grid;
        gap: 1rem;
    }

    #section-alerts .aoc-alert-identity {
        display: flex;
        align-items: flex-start;
        gap: 0.8rem;
        min-width: 0;
    }

    #section-alerts .aoc-alert-icon {
        display: inline-flex;
        width: 2.8rem;
        height: 2.8rem;
        flex: 0 0 2.8rem;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: linear-gradient(135deg, #fee2e2, #ffedd5);
        color: #dc2626;
        box-shadow: 0 12px 24px rgba(220, 38, 38, 0.12);
    }

    #section-alerts .aoc-alert-title {
        color: var(--aoc-ink);
        font-size: 1rem;
        font-weight: 850;
    }

    #section-alerts .aoc-alert-address {
        margin-top: 0.22rem;
        color: var(--aoc-muted);
        font-size: 0.88rem;
        line-height: 1.45;
    }

    #section-alerts .aoc-alert-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        color: var(--aoc-muted);
        font-size: 0.76rem;
    }

    #section-alerts .aoc-alert-meta span {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
        border-radius: 999px;
        background: #f8fafc;
        padding: 0.4rem 0.62rem;
    }

    #section-alerts .aoc-alert-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
    }

    #section-alerts .aoc-action-btn {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        gap: 0.38rem;
        border-radius: 0.9rem;
        padding: 0.6rem 0.82rem;
        font-size: 0.84rem;
        font-weight: 800;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    #section-alerts .aoc-action-btn--primary {
        background: #fff7ed;
        color: #c2410c;
    }

    #section-alerts .aoc-action-btn--muted {
        background: #f8fafc;
        color: #475569;
    }

    #section-alerts .aoc-action-btn--map {
        background: #ecfeff;
        color: #0f766e;
    }

    #section-alerts .aoc-recent-report {
        display: block;
        width: 100%;
        border-radius: 1.25rem;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(135deg, #fff, #fffaf5);
        padding: 1rem;
        text-align: left;
        transition: border-color 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
    }

    #section-alerts .aoc-recent-report:hover {
        border-color: rgba(198, 61, 38, 0.22);
        background: #fff7ed;
    }

    #section-alerts .reports-history-mobile-list {
        display: none;
    }

    #citizenReportsHistoryModal {
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }

    #citizenReportsHistoryModal .reports-history-panel {
        width: min(100%, 58rem);
        max-width: 58rem;
        max-height: min(78vh, 46rem);
        margin: 0 auto;
        border-radius: 1.75rem;
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2);
    }

    #citizenReportsHistoryModal .reports-history-body {
        max-height: calc(min(78vh, 46rem) - 10.5rem);
    }

    body.citizen-history-standalone {
        background: #fffdfa;
    }

    body.citizen-history-standalone #sidebar,
    body.citizen-history-standalone #sidebarBackdrop,
    body.citizen-history-standalone .citizen-mobile-dock,
    body.citizen-history-standalone [data-citizen-settings-panel],
    body.citizen-history-standalone [data-citizen-map-layers-panel] {
        display: none !important;
    }

    body.citizen-history-standalone #section-alerts {
        width: 100%;
        max-width: none;
        padding: 0;
        margin: 0;
    }

    body.citizen-history-standalone #section-alerts .aoc-shell > *:not(#citizenReportsHistoryModal) {
        display: none !important;
    }

    body.citizen-history-standalone #citizenReportsHistoryModal {
        padding: 0;
        align-items: stretch;
        justify-content: stretch;
        background: rgba(255, 253, 250, 0.98);
        backdrop-filter: none;
    }

    body.citizen-history-standalone #citizenReportsHistoryModal .reports-history-panel {
        width: 100%;
        max-width: none;
        min-height: 100vh;
        max-height: none;
        border: none;
        border-radius: 0;
        box-shadow: none;
    }

    body.citizen-history-standalone #citizenReportsHistoryModal .reports-history-header,
    body.citizen-history-standalone #citizenReportsHistoryModal .reports-history-toolbar,
    body.citizen-history-standalone #citizenReportsHistoryModal .reports-history-body {
        padding-left: clamp(1rem, 4vw, 1.5rem);
        padding-right: clamp(1rem, 4vw, 1.5rem);
    }

    @media (min-width: 760px) {
        #section-alerts .aoc-hero-grid,
        #section-alerts .aoc-panel-head-row,
        #section-alerts .aoc-map-head-row {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }

        #section-alerts .aoc-filter-row {
            grid-template-columns: minmax(15rem, 1fr) 11rem 11rem;
            align-items: center;
        }

        #section-alerts .aoc-alert-main {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }

        #section-alerts .aoc-alert-actions {
            justify-content: flex-end;
        }
    }

    @media (max-width: 900px) {
        #section-alerts .aoc-map-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        #section-alerts.areas-concern-page {
            width: 100%;
            padding: 1rem;
            padding-bottom: calc(7.5rem + env(safe-area-inset-bottom, 0px));
        }

        #section-alerts .aoc-shell {
            gap: 1rem;
        }

        #section-alerts .aoc-hero {
            border-radius: 1.4rem;
            margin-inline: 0;
            padding: 1.35rem 1rem 1.05rem;
            box-shadow: none;
            background:
                radial-gradient(circle at 88% 20%, rgba(227, 155, 23, 0.2), transparent 8rem),
                linear-gradient(145deg, rgba(255, 253, 247, 0.98), rgba(255, 248, 237, 0.94));
        }

        #section-alerts .aoc-title {
            max-width: 15rem;
            font-size: 2rem;
            line-height: 1.05;
            letter-spacing: -0.05em;
        }

        #section-alerts .aoc-subtitle {
            max-width: 17rem;
            font-size: 0.93rem;
            line-height: 1.58;
        }

        #section-alerts .aoc-panel,
        #section-alerts .aoc-map-card,
        #section-alerts .aoc-snapshot {
            border-radius: 1.2rem;
            border: 1px solid rgba(120, 53, 15, 0.09);
            box-shadow: none;
            margin-inline: 0;
        }

        #section-alerts .aoc-hero-actions,
        #section-alerts .aoc-history-btn,
        #section-alerts .aoc-alert-pill {
            width: 100%;
        }

        #section-alerts .aoc-alert-pill,
        #section-alerts .aoc-history-btn {
            justify-content: center;
        }

        #section-alerts .aoc-alert-pill {
            margin-top: 0.35rem;
            border-radius: 1rem;
        }

        #section-alerts .aoc-history-btn {
            border-radius: 1rem;
            min-height: 52px;
            font-size: 0.98rem;
        }

        #section-alerts .aoc-history-btn::after {
            content: '\f105';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-left: auto;
        }

        #section-alerts .aoc-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
        }

        #section-alerts .aoc-stat-card {
            min-height: 8.25rem;
            border-radius: 1rem;
            padding: 0.72rem;
        }

        #section-alerts .aoc-stat-top {
            display: grid;
            gap: 0.55rem;
        }

        #section-alerts .aoc-stat-icon {
            order: -1;
            width: 2.25rem;
            height: 2.25rem;
            flex-basis: 2.25rem;
            border-radius: 0.78rem;
            font-size: 0.95rem;
        }

        #section-alerts .aoc-stat-label {
            font-size: 0.68rem;
            line-height: 1.18;
        }

        #section-alerts .aoc-stat-value {
            margin-top: 0.28rem;
            font-size: 1.7rem;
        }

        #section-alerts .aoc-stat-copy {
            display: none;
        }

        #section-alerts .aoc-panel-head,
        #section-alerts .aoc-map-head,
        #section-alerts .aoc-snapshot-head,
        #section-alerts .aoc-map-body,
        #section-alerts .aoc-snapshot-body {
            padding: 1rem 0.95rem;
        }

        #section-alerts .aoc-panel-head,
        #section-alerts .aoc-map-head,
        #section-alerts .aoc-snapshot-head {
            background: transparent;
        }

        #section-alerts .aoc-filter-row {
            grid-template-columns: minmax(0, 1fr) 46px 46px;
            gap: 0.45rem;
            align-items: center;
        }

        #section-alerts .aoc-input {
            font-size: 0.86rem;
            padding-right: 0.75rem;
        }

        #section-alerts .aoc-search-wrap,
        #section-alerts .aoc-filter-control {
            min-height: 46px;
        }

        #section-alerts .aoc-filter-control {
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 1rem;
            background: #fff;
            color: #c2410c;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        #section-alerts .aoc-filter-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        #section-alerts .aoc-filter-control .aoc-select {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        #section-alerts .aoc-filter-button {
            display: inline-flex;
            width: 100%;
            height: 100%;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: inherit;
            background: transparent;
            color: inherit;
        }

        #section-alerts .aoc-filter-menu {
            display: block;
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            z-index: 30;
            width: max-content;
            min-width: 11.5rem;
            max-width: calc(100vw - 2rem);
            border: 1px solid rgba(198, 61, 38, 0.14);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 18px 40px rgba(88, 43, 20, 0.16);
            padding: 0.35rem;
        }

        #section-alerts .aoc-filter-menu[hidden] {
            display: none;
        }

        #section-alerts .aoc-filter-option {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            border: 0;
            border-radius: 0.75rem;
            background: transparent;
            color: #334155;
            padding: 0.68rem 0.78rem;
            text-align: left;
            font-size: 0.88rem;
            font-weight: 700;
        }

        #section-alerts .aoc-filter-option.is-active {
            background: #fff7ed;
            color: #c2410c;
        }

        #section-alerts #areasConcernRiskMap {
            min-height: 20rem;
            border-radius: 0.9rem !important;
        }

        #section-alerts .aoc-alert-actions,
        #section-alerts .aoc-action-btn {
            width: 100%;
        }

        #citizenReportsHistoryModal {
            align-items: flex-end;
            padding: 0.75rem;
            padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        }

        #citizenReportsHistoryModal .reports-history-panel {
            width: 100%;
            max-width: 27rem;
            max-height: 86vh;
            border-radius: 1.6rem;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18);
        }

        #citizenReportsHistoryModal .reports-history-header {
            align-items: flex-start;
            padding: 1rem 1rem 0.85rem;
        }

        #citizenReportsHistoryModal .reports-history-title {
            font-size: 1rem;
            line-height: 1.18;
        }

        #citizenReportsHistoryModal .reports-history-subtitle {
            margin-top: 0.18rem;
            max-width: 14rem;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        #citizenReportsHistoryModal [data-reports-history-close] {
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
        }

        #citizenReportsHistoryModal .reports-history-toolbar {
            padding: 0.75rem 1rem;
        }

        #citizenReportsHistoryModal .reports-history-toolbar-row,
        #citizenReportsHistoryModal .reports-history-filter-group {
            gap: 0.55rem;
        }

        #citizenReportsHistoryModal #reportsHistorySearch,
        #citizenReportsHistoryModal #reportsHistoryTypeFilter {
            min-height: 42px;
            width: 100%;
            border-radius: 0.95rem;
            padding: 0.68rem 0.85rem;
            font-size: 0.82rem;
        }

        #citizenReportsHistoryModal #reportsHistoryTypeFilter {
            max-width: 11.5rem;
        }

        #citizenReportsHistoryModal #reportsHistorySummary {
            font-size: 0.78rem;
        }

        #citizenReportsHistoryModal .reports-history-body {
            max-height: calc(86vh - 10.75rem);
            padding: 0.85rem 1rem 1rem;
        }

        #citizenReportsHistoryModal .reports-history-table-wrap {
            display: none;
        }

        #section-alerts .reports-history-mobile-list {
            display: grid;
            gap: 0.7rem;
        }

        body.citizen-history-standalone #citizenReportsHistoryModal {
            padding: 0;
            align-items: stretch;
        }

        body.citizen-history-standalone #citizenReportsHistoryModal .reports-history-panel {
            max-width: none;
            max-height: none;
            min-height: 100vh;
            border-radius: 0;
        }

        #section-alerts .reports-history-card {
            display: grid;
            gap: 0.65rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 1.05rem;
            background: linear-gradient(145deg, #ffffff, #fffaf5);
            padding: 0.85rem;
        }

        #section-alerts .reports-history-card__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        #section-alerts .reports-history-card__date {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1.35;
        }

        #section-alerts .reports-history-card__title {
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.2;
        }

        #section-alerts .reports-history-card__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.38rem;
        }

        #section-alerts .reports-history-chip {
            display: inline-flex;
            align-items: center;
            min-height: 1.75rem;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            padding: 0.28rem 0.55rem;
            font-size: 0.68rem;
            font-weight: 850;
        }

        #section-alerts .reports-history-chip--status {
            background: #f1f5f9;
            color: #475569;
        }

        #section-alerts .reports-history-card__location {
            color: #475569;
            font-size: 0.78rem;
            line-height: 1.38;
        }

        #section-alerts .reports-history-card__action {
            min-height: 40px;
            border-radius: 0.9rem;
            background: linear-gradient(135deg, var(--aoc-red), var(--aoc-orange));
            color: #fff;
            font-size: 0.8rem;
            font-weight: 850;
        }

        #section-alerts .reports-history-empty {
            border: 1px dashed rgba(148, 163, 184, 0.4);
            border-radius: 1.15rem;
            background: #f8fafc;
            padding: 1.4rem 1rem;
            text-align: center;
        }
    }
</style>

<div id="section-alerts" class="content-section areas-concern-page">
    <div class="aoc-shell">
        <section class="aoc-hero">
            <div class="aoc-hero-grid">
                <div>
                    <span class="aoc-eyebrow"><i class="fa-solid fa-shield-heart"></i> Citizen awareness</span>
                    <h1 class="aoc-title">Areas of Concern</h1>
                    <p class="aoc-subtitle">Track active fire alerts near you, review your submitted reports, and inspect mapped risk or hazard factors in one focused dashboard.</p>
                </div>
                <div class="aoc-hero-actions">
                    <span class="aoc-alert-pill">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                        <span><span id="areasConcernActiveAlertsValue">{{ $nearbyAlertCollection->count() }}</span> active fire alerts</span>
                    </span>
                    <button type="button" id="openCitizenReportsHistoryBtn" class="aoc-history-btn">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>View My Reports History</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="aoc-stats-grid" aria-label="Areas of Concern summary">
            <div class="aoc-stat-card" style="--aoc-stat-glow:rgba(220,38,38,0.1);--aoc-icon-bg:#fff1f2;--aoc-icon-color:#dc2626;">
                <div class="aoc-stat-top">
                    <div>
                        <p class="aoc-stat-label">Active Fire Alerts</p>
                        <p id="areasConcernAlertCountCard" class="aoc-stat-value">{{ $nearbyAlertCollection->count() }}</p>
                    </div>
                    <div class="aoc-stat-icon"><i class="fa-solid fa-fire"></i></div>
                </div>
                <p class="aoc-stat-copy">Nearby fire incidents still in reported, responding, or on-scene status.</p>
            </div>

            <div class="aoc-stat-card" style="--aoc-stat-glow:rgba(227,155,23,0.14);--aoc-icon-bg:#fffbeb;--aoc-icon-color:#d97706;">
                <div class="aoc-stat-top">
                    <div>
                        <p class="aoc-stat-label">My Submitted Reports</p>
                        <p id="areasConcernReportCountCard" class="aoc-stat-value">{{ $combinedReportCollection->count() }}</p>
                    </div>
                    <div class="aoc-stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                </div>
                <p class="aoc-stat-copy">Includes your fire incident submissions and your risk or hazard factors.</p>
            </div>

            <div class="aoc-stat-card" style="--aoc-stat-glow:rgba(13,148,136,0.12);--aoc-icon-bg:#ecfeff;--aoc-icon-color:#0f766e;">
                <div class="aoc-stat-top">
                    <div>
                        <p class="aoc-stat-label">Risk / Hazard Markers</p>
                        <p id="areasConcernRiskMarkerCountCard" class="aoc-stat-value">{{ $riskMapCollection->count() }}</p>
                    </div>
                    <div class="aoc-stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
                <p class="aoc-stat-copy">Mapped risk and hazard records already approved for display or submitted by you.</p>
            </div>
        </section>

        <section class="aoc-panel">
            <div class="aoc-panel-head">
                <div class="aoc-panel-head-row">
                    <div>
                        <span class="aoc-section-eyebrow"><i class="fa-solid fa-bell"></i> Fire alerts</span>
                        <h2 class="aoc-section-title">Active Alerts</h2>
                        <p class="aoc-section-copy">This view only shows fire alerts for citizen awareness. Use search, status, and sorting to narrow the list.</p>
                    </div>
                    <div class="aoc-filter-row">
                        <div class="aoc-search-wrap">
                            <i class="fa-solid fa-magnifying-glass aoc-search-icon"></i>
                            <input id="areasConcernAlertSearch" type="search" placeholder="Search address, barangay, or status" class="aoc-input">
                        </div>
                        <div class="aoc-filter-control" aria-label="Filter alerts by status">
                            <button type="button" class="aoc-filter-button" data-aoc-filter-toggle="status" aria-label="Filter by status" aria-expanded="false">
                                <i class="fa-solid fa-sliders aoc-filter-icon" aria-hidden="true"></i>
                            </button>
                            <select id="areasConcernStatusFilter" class="aoc-select">
                                <option value="all">All statuses</option>
                                @foreach($initialAlertStatuses as $statusLabel)
                                    <option value="{{ strtolower($statusLabel) }}">{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            <div class="aoc-filter-menu" data-aoc-filter-menu="status" hidden>
                                <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernStatusFilter" data-aoc-filter-value="all">All statuses</button>
                                @foreach($initialAlertStatuses as $statusLabel)
                                    <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernStatusFilter" data-aoc-filter-value="{{ strtolower($statusLabel) }}">{{ $statusLabel }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="aoc-filter-control" aria-label="Sort alerts">
                            <button type="button" class="aoc-filter-button" data-aoc-filter-toggle="sort" aria-label="Sort alerts" aria-expanded="false">
                                <i class="fa-solid fa-arrow-down-wide-short aoc-filter-icon" aria-hidden="true"></i>
                            </button>
                            <select id="areasConcernSortFilter" class="aoc-select">
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                                <option value="closest">Closest first</option>
                                <option value="status">Status</option>
                            </select>
                            <div class="aoc-filter-menu" data-aoc-filter-menu="sort" hidden>
                                <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernSortFilter" data-aoc-filter-value="newest">Newest first</button>
                                <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernSortFilter" data-aoc-filter-value="oldest">Oldest first</button>
                                <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernSortFilter" data-aoc-filter-value="closest">Closest first</button>
                                <button type="button" class="aoc-filter-option" data-aoc-filter-target="areasConcernSortFilter" data-aoc-filter-value="status">Status</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="areasConcernAlertsList" class="divide-y divide-slate-100"></div>
        </section>

        <div class="aoc-map-layout">
            <section class="aoc-map-card">
                <div class="aoc-map-head">
                    <div class="aoc-map-head-row">
                        <div>
                            <span class="aoc-section-eyebrow"><i class="fa-solid fa-map-location-dot"></i> Risk intelligence</span>
                            <h2 class="aoc-section-title">Risk and Hazard Map</h2>
                            <p class="aoc-section-copy">Mapped non-fire concerns shared through PyroSense. Marker details include category, status, location context, and available image preview.</p>
                        </div>
                        <span class="aoc-map-pill">
                            <i class="fa-solid fa-layer-group"></i>
                            <span id="areasConcernMapMarkerCount">{{ $riskMapCollection->count() }}</span> markers
                        </span>
                    </div>
                </div>
                <div class="aoc-map-body">
                <div id="areasConcernMapError" class="hidden rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>
                <div id="areasConcernMapEmpty" class="{{ $riskMapCollection->isEmpty() ? '' : 'hidden' }} rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                        <i class="fa-solid fa-map-location-dot text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No risk/hazard markers available.</p>
                    <p class="mt-1 text-xs text-slate-500">Once approved records or your own submissions are available, they will appear here.</p>
                </div>
                <div id="areasConcernMapLoading" class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-orange-700 mb-4">
                    Loading risk and hazard map...
                </div>
                <div id="areasConcernRiskMap" class="{{ $riskMapCollection->isEmpty() ? 'hidden' : '' }} h-[22rem] w-full overflow-hidden rounded-[1.75rem] border border-slate-200 sm:h-[26rem]"></div>
            </div>
        </section>

        <aside class="aoc-snapshot">
            <div class="aoc-snapshot-head">
                <span class="aoc-section-eyebrow"><i class="fa-solid fa-folder-open"></i> Report history</span>
                <h2 class="aoc-section-title">My Reports Snapshot</h2>
                <p class="aoc-section-copy">Quick view of what you have submitted before opening the full history modal.</p>
            </div>
            <div class="aoc-snapshot-body">
                <div class="aoc-mini-grid">
                    <div class="aoc-mini-card">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-600">Fire Reports</p>
                        <p id="areasConcernFireReportCount" class="mt-2 text-2xl font-bold text-slate-900">0</p>
                    </div>
                    <div class="aoc-mini-card">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Risk / Hazard</p>
                        <p id="areasConcernRiskReportCount" class="mt-2 text-2xl font-bold text-slate-900">0</p>
                    </div>
                </div>
                <div id="areasConcernRecentReports" class="space-y-3"></div>
            </div>
        </aside>
    </div>
    </div>

    <div id="citizenReportsHistoryModal" class="fixed inset-0 z-[4200] hidden items-center justify-center bg-slate-950/45 backdrop-blur-sm p-4">
        <div class="reports-history-panel w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="reports-history-header flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-5">
                <div>
                    <h3 class="reports-history-title text-lg font-bold text-slate-900">My Reports History</h3>
                    <p class="reports-history-subtitle text-sm text-slate-500">Fire Incident and Risk/Hazard Factor records submitted by your account.</p>
                </div>
                <button type="button" data-reports-history-close class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="reports-history-toolbar border-b border-slate-100 px-6 py-4">
                <div class="reports-history-toolbar-row flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="reports-history-filter-group flex flex-wrap gap-3">
                        <input id="reportsHistorySearch" type="search" placeholder="Search type, location, or status" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-100 md:w-72">
                        <select id="reportsHistoryTypeFilter" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-100">
                            <option value="all">All report types</option>
                            <option value="fire">Fire Incident</option>
                            <option value="risk_hazard">Risk/Hazard Factor</option>
                        </select>
                    </div>
                    <p id="reportsHistorySummary" class="text-sm text-slate-500">No reports yet.</p>
                </div>
            </div>
            <div class="reports-history-body max-h-[70vh] overflow-auto px-6 py-5">
                <div class="reports-history-table-wrap overflow-x-auto rounded-3xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Location / Barangay</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reportsHistoryTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
                <div id="reportsHistoryMobileList" class="reports-history-mobile-list"></div>
            </div>
        </div>
    </div>

    <div id="citizenConcernDetailsModal" class="fixed inset-0 z-[4300] hidden items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-3xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-5">
                <div>
                    <p id="citizenConcernDetailsEyebrow" class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Report details</p>
                    <h3 id="citizenConcernDetailsTitle" class="mt-1 text-xl font-bold text-slate-900">Report details</h3>
                </div>
                <button type="button" data-concern-details-close class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="max-h-[75vh] overflow-auto px-6 py-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Summary</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Type</dt>
                                <dd id="citizenConcernDetailsType" class="text-right font-semibold text-slate-900"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Category</dt>
                                <dd id="citizenConcernDetailsCategory" class="text-right text-slate-700"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd id="citizenConcernDetailsStatus" class="text-right text-slate-700"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Submitted</dt>
                                <dd id="citizenConcernDetailsDate" class="text-right text-slate-700"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Barangay</dt>
                                <dd id="citizenConcernDetailsBarangay" class="text-right text-slate-700"></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Location</p>
                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <p id="citizenConcernDetailsLocation"></p>
                            <p id="citizenConcernDetailsCoordinates" class="text-xs text-slate-500"></p>
                            <button type="button" id="citizenConcernDetailsViewMapBtn" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="fa-solid fa-map-location-dot"></i>
                                View on Map
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-4 rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Notes</p>
                    <p id="citizenConcernDetailsNotes" class="mt-3 text-sm leading-6 text-slate-700"></p>
                </div>
                <div id="citizenConcernDetailsImageWrap" class="mt-4 hidden rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Image Preview</p>
                    <img id="citizenConcernDetailsImage" src="" alt="Risk or hazard report image preview" class="mt-4 max-h-80 w-full rounded-2xl object-cover">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const pageParams = new URLSearchParams(window.location.search);
    const reportsHistoryStandalone = pageParams.get('reports') === 'history' && pageParams.get('standalone') === '1';
    const initialAlerts = @json($nearbyAlertCollection->values());
    const initialReports = @json($combinedReportCollection->values());
    const initialRiskMapReports = @json($riskMapCollection->values());
    const reportHistoryModal = document.getElementById('citizenReportsHistoryModal');
    const concernDetailsModal = document.getElementById('citizenConcernDetailsModal');
    const reportsHistorySearch = document.getElementById('reportsHistorySearch');
    const reportsHistoryTypeFilter = document.getElementById('reportsHistoryTypeFilter');
    const areasConcernAlertSearch = document.getElementById('areasConcernAlertSearch');
    const areasConcernStatusFilter = document.getElementById('areasConcernStatusFilter');
    const areasConcernSortFilter = document.getElementById('areasConcernSortFilter');

    let concernAlertsData = Array.isArray(window.nearbyAlertsData) && window.nearbyAlertsData.length ? [...window.nearbyAlertsData] : [...initialAlerts];
    let concernReportsData = Array.isArray(window.citizenCombinedReports) && window.citizenCombinedReports.length ? [...window.citizenCombinedReports] : [...initialReports];
    let concernRiskMapData = Array.isArray(window.citizenRiskMapReports) && window.citizenRiskMapReports.length ? [...window.citizenRiskMapReports] : [...initialRiskMapReports];
    let concernMap = null;
    let concernMapMarkersLayer = null;
    let activeConcernDetailRecord = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        if (!value) return 'Unknown date';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Unknown date';
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function readAlertStatusLabel(value) {
        const normalized = String(value || 'reported').trim().toLowerCase();
        if (normalized === 'reported') return 'Reported';
        if (normalized === 'responding' || normalized === 'en-route' || normalized === 'en route') return 'Responding';
        if (normalized === 'on scene' || normalized === 'on-scene') return 'On Scene';
        if (normalized === 'controlled' || normalized === 'completed') return 'Controlled';
        if (normalized === 'closed') return 'Closed';
        return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : 'Reported';
    }

    function readAlertStatusMeta(value) {
        const normalized = String(value || 'reported').trim().toLowerCase();
        if (normalized === 'reported') return { label: 'Reported', badge: 'bg-red-100 text-red-700', summary: 'Awaiting response' };
        if (normalized === 'responding' || normalized === 'en-route' || normalized === 'en route') return { label: 'Responding', badge: 'bg-amber-100 text-amber-700', summary: 'Crews dispatched' };
        if (normalized === 'on scene' || normalized === 'on-scene') return { label: 'On Scene', badge: 'bg-purple-100 text-purple-700', summary: 'Crews on scene' };
        if (normalized === 'controlled' || normalized === 'completed') return { label: 'Controlled', badge: 'bg-blue-100 text-blue-700', summary: 'Incident under control' };
        if (normalized === 'closed') return { label: 'Closed', badge: 'bg-slate-100 text-slate-700', summary: 'Incident closed' };
        return { label: 'Reported', badge: 'bg-red-100 text-red-700', summary: 'Awaiting response' };
    }

    function readReportKind(record) {
        const kind = String(record?.report_type || record?.entry_type || record?.report_kind || '').toLowerCase();
        if (kind === 'fire') return 'fire';
        if (kind === 'hazard' || kind === 'risk' || kind === 'concern') return 'risk_hazard';
        if (String(record?.incident_type_name || record?.incident_type || '').toLowerCase().includes('fire')) return 'fire';
        return 'risk_hazard';
    }

    function readReportTypeLabel(record) {
        return readReportKind(record) === 'fire' ? 'Fire Incident' : 'Risk/Hazard Factor';
    }

    function readReportCategory(record) {
        return record?.category_label || record?.condition_label || record?.incident_type_name || record?.incident_type || 'General';
    }

    function readReportStatus(record) {
        return record?.review_status_label || record?.status_label || readAlertStatusLabel(record?.status || record?.incident_status);
    }

    function readReportLocation(record) {
        return record?.location_label || record?.address || record?.street_or_sitio || 'Location pending';
    }

    function readReportBarangay(record) {
        const barangay = [record?.barangay_name, record?.barangay_city_name].filter(Boolean).join(', ');
        return barangay || 'Barangay pending';
    }

    function updateAreasConcernSummary() {
        const fireReports = concernReportsData.filter((record) => readReportKind(record) === 'fire').length;
        const riskReports = concernReportsData.filter((record) => readReportKind(record) === 'risk_hazard').length;
        const setText = (id, value) => {
            const node = document.getElementById(id);
            if (node) node.textContent = String(value);
        };

        setText('areasConcernActiveAlertsValue', concernAlertsData.length);
        setText('areasConcernAlertCountCard', concernAlertsData.length);
        setText('areasConcernReportCountCard', concernReportsData.length);
        setText('areasConcernRiskMarkerCountCard', concernRiskMapData.length);
        setText('areasConcernMapMarkerCount', concernRiskMapData.length);
        setText('areasConcernFireReportCount', fireReports);
        setText('areasConcernRiskReportCount', riskReports);
    }

    function renderAreasConcernRecentReports() {
        const host = document.getElementById('areasConcernRecentReports');
        if (!host) return;

        const records = [...concernReportsData]
            .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
            .slice(0, 4);

        if (!records.length) {
            host.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
                    <p class="text-sm font-semibold text-slate-700">No reports yet.</p>
                    <p class="mt-1 text-xs text-slate-500">Once you submit a fire, risk, or hazard report, it will appear here.</p>
                </div>
            `;
            return;
        }

        host.innerHTML = records.map((record) => `
            <button type="button" class="aoc-recent-report" data-concern-report-open="${escapeHtml(String(record.incident_id || record.risk_area_id || ''))}" data-concern-report-kind="${escapeHtml(readReportKind(record))}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">${escapeHtml(readReportTypeLabel(record))}</p>
                        <p class="mt-1 text-xs text-slate-500">${escapeHtml(readReportCategory(record))}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">${escapeHtml(readReportStatus(record))}</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">${escapeHtml(readReportLocation(record))}</p>
                <p class="mt-1 text-xs text-slate-400">${escapeHtml(formatDate(record.created_at))}</p>
            </button>
        `).join('');

        host.querySelectorAll('[data-concern-report-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const record = findReportRecord(button.dataset.concernReportOpen, button.dataset.concernReportKind);
                if (record) openConcernDetailsModal(record);
            });
        });
    }

    function renderAreasConcernAlerts() {
        const list = document.getElementById('areasConcernAlertsList');
        if (!list) return;

        const searchTerm = String(areasConcernAlertSearch?.value || '').trim().toLowerCase();
        const statusFilter = String(areasConcernStatusFilter?.value || 'all').toLowerCase();
        const sortValue = String(areasConcernSortFilter?.value || 'newest');

        let items = [...concernAlertsData];
        items = items.filter((item) => {
            const haystack = [
                item?.address,
                item?.barangay_name,
                item?.barangay_city_name,
                item?.incident_status,
                item?.status
            ].filter(Boolean).join(' ').toLowerCase();
            const statusLabel = readAlertStatusLabel(item?.incident_status || item?.status).toLowerCase();
            return (!searchTerm || haystack.includes(searchTerm)) && (statusFilter === 'all' || statusLabel === statusFilter);
        });

        items.sort((left, right) => {
            if (sortValue === 'oldest') return new Date(left.created_at || 0) - new Date(right.created_at || 0);
            if (sortValue === 'closest') return Number(left.distance_meters || Number.MAX_SAFE_INTEGER) - Number(right.distance_meters || Number.MAX_SAFE_INTEGER);
            if (sortValue === 'status') return readAlertStatusLabel(left.incident_status).localeCompare(readAlertStatusLabel(right.incident_status));
            return new Date(right.created_at || 0) - new Date(left.created_at || 0);
        });

        if (!items.length) {
            list.innerHTML = `
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa-regular fa-bell-slash text-2xl"></i>
                    </div>
                    <p class="text-base font-semibold text-slate-800">No active alerts.</p>
                    <p class="mt-1 text-sm text-slate-500">Try adjusting your search or status filter, or check back when a nearby fire alert is reported.</p>
                </div>
            `;
            return;
        }

        list.innerHTML = items.map((incident) => {
            const statusMeta = readAlertStatusMeta(incident.incident_status || incident.status);
            const distance = typeof incident.distance_meters === 'number' ? `${(incident.distance_meters / 1000).toFixed(1)} km away` : 'Distance unavailable';
            const barangay = [incident.barangay_name, incident.barangay_city_name].filter(Boolean).join(', ');

            return `
                <article class="aoc-alert-card">
                    <div class="aoc-alert-main">
                        <div class="min-w-0 flex-1">
                            <div class="aoc-alert-identity">
                                <span class="aoc-alert-icon">
                                    <i class="fa-solid fa-fire"></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="aoc-alert-title">Fire Alert</h3>
                                    <p class="aoc-alert-address">${escapeHtml(incident.address || 'No location description available.')}</p>
                                </div>
                            </div>
                            <div class="aoc-alert-meta mt-4">
                                <span class="rounded-full ${statusMeta.badge} px-3 py-1 font-semibold">${escapeHtml(statusMeta.label)}</span>
                                <span><i class="fa-regular fa-clock mr-1"></i>${escapeHtml(formatDate(incident.created_at))}</span>
                                <span><i class="fa-solid fa-location-crosshairs mr-1"></i>${escapeHtml(distance)}</span>
                                <span><i class="fa-solid fa-map-pin mr-1"></i>${escapeHtml(barangay || 'Barangay pending')}</span>
                            </div>
                        </div>
                        <div class="aoc-alert-actions">
                            <button type="button" class="aoc-action-btn aoc-action-btn--primary" data-alert-details="${escapeHtml(String(incident.incident_id || ''))}">
                                <i class="fa-regular fa-eye mr-1"></i>Details
                            </button>
                            <button type="button" class="aoc-action-btn aoc-action-btn--muted" data-alert-share="${escapeHtml(String(incident.incident_id || ''))}">
                                <i class="fa-solid fa-share-nodes mr-1"></i>Share
                            </button>
                            ${incident.lat && incident.lng ? `<button type="button" class="aoc-action-btn aoc-action-btn--map" data-alert-map="${escapeHtml(String(incident.incident_id || ''))}"><i class="fa-regular fa-map mr-1"></i>View on Map</button>` : ''}
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        list.querySelectorAll('[data-alert-details]').forEach((button) => {
            button.addEventListener('click', () => {
                const record = concernAlertsData.find((item) => String(item.incident_id) === String(button.dataset.alertDetails));
                if (record) openConcernDetailsModal(record);
            });
        });
        list.querySelectorAll('[data-alert-share]').forEach((button) => button.addEventListener('click', () => shareAlert(button.dataset.alertShare)));
        list.querySelectorAll('[data-alert-map]').forEach((button) => {
            button.addEventListener('click', () => {
                const record = concernAlertsData.find((item) => String(item.incident_id) === String(button.dataset.alertMap));
                if (record?.lat && record?.lng) viewOnMap(record.lat, record.lng);
            });
        });
    }

    function renderReportsHistoryTable() {
        const tbody = document.getElementById('reportsHistoryTableBody');
        const mobileList = document.getElementById('reportsHistoryMobileList');
        const summary = document.getElementById('reportsHistorySummary');
        if (!tbody || !summary) return;

        const searchTerm = String(reportsHistorySearch?.value || '').trim().toLowerCase();
        const typeFilter = String(reportsHistoryTypeFilter?.value || 'all');
        const items = [...concernReportsData]
            .filter((record) => {
                const kind = readReportKind(record);
                const haystack = [
                    readReportTypeLabel(record),
                    readReportCategory(record),
                    readReportLocation(record),
                    readReportBarangay(record),
                    readReportStatus(record),
                ].join(' ').toLowerCase();
                return (typeFilter === 'all' || kind === typeFilter) && (!searchTerm || haystack.includes(searchTerm));
            })
            .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

        summary.textContent = items.length ? `${items.length} report(s) found` : 'No reports yet.';

        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fa-regular fa-folder-open text-xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No reports yet.</p>
                        <p class="mt-1 text-xs text-slate-500">Your fire and risk/hazard submissions will appear here.</p>
                    </td>
                </tr>
            `;
            if (mobileList) {
                mobileList.innerHTML = `
                    <div class="reports-history-empty">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400">
                            <i class="fa-regular fa-folder-open text-lg"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">No reports yet.</p>
                        <p class="mt-1 text-xs text-slate-500">Your submissions will appear here.</p>
                    </div>
                `;
            }
            return;
        }

        tbody.innerHTML = items.map((record) => {
            const key = String(record.incident_id || record.risk_area_id || '');
            const kind = readReportKind(record);
            return `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-4 text-slate-600">${escapeHtml(formatDate(record.created_at))}</td>
                    <td class="px-4 py-4 font-semibold text-slate-900">${escapeHtml(readReportTypeLabel(record))}</td>
                    <td class="px-4 py-4 text-slate-600">${escapeHtml(readReportCategory(record))}</td>
                    <td class="px-4 py-4 text-slate-600">
                        <div>${escapeHtml(readReportLocation(record))}</div>
                        <div class="text-xs text-slate-400 mt-1">${escapeHtml(readReportBarangay(record))}</div>
                    </td>
                    <td class="px-4 py-4 text-slate-600">${escapeHtml(readReportStatus(record))}</td>
                    <td class="px-4 py-4 text-right">
                        <button type="button" class="rounded-2xl bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-200" data-history-details="${escapeHtml(key)}" data-history-kind="${escapeHtml(kind)}">
                            <i class="fa-regular fa-eye mr-1"></i>Details
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        if (mobileList) {
            mobileList.innerHTML = items.map((record) => {
                const key = String(record.incident_id || record.risk_area_id || '');
                const kind = readReportKind(record);
                const location = [readReportLocation(record), readReportBarangay(record)]
                    .filter(Boolean)
                    .join(' · ');

                return `
                    <article class="reports-history-card">
                        <div class="reports-history-card__top">
                            <div>
                                <p class="reports-history-card__date">${escapeHtml(formatDate(record.created_at))}</p>
                                <h4 class="reports-history-card__title">${escapeHtml(readReportTypeLabel(record))}</h4>
                            </div>
                            <span class="reports-history-chip reports-history-chip--status">${escapeHtml(readReportStatus(record))}</span>
                        </div>
                        <div class="reports-history-card__meta">
                            <span class="reports-history-chip">${escapeHtml(readReportCategory(record))}</span>
                        </div>
                        <p class="reports-history-card__location">
                            <i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>${escapeHtml(location || 'Location unavailable')}
                        </p>
                        <button type="button" class="reports-history-card__action" data-history-details="${escapeHtml(key)}" data-history-kind="${escapeHtml(kind)}">
                            <i class="fa-regular fa-eye mr-1"></i>View details
                        </button>
                    </article>
                `;
            }).join('');
        }

        tbody.querySelectorAll('[data-history-details]').forEach((button) => {
            button.addEventListener('click', () => {
                const record = findReportRecord(button.dataset.historyDetails, button.dataset.historyKind);
                if (record) openConcernDetailsModal(record);
            });
        });

        mobileList?.querySelectorAll('[data-history-details]').forEach((button) => {
            button.addEventListener('click', () => {
                const record = findReportRecord(button.dataset.historyDetails, button.dataset.historyKind);
                if (record) openConcernDetailsModal(record);
            });
        });
    }

    function ensureConcernMap() {
        const mapNode = document.getElementById('areasConcernRiskMap');
        const loadingNode = document.getElementById('areasConcernMapLoading');
        const emptyNode = document.getElementById('areasConcernMapEmpty');
        const errorNode = document.getElementById('areasConcernMapError');

        if (!mapNode || typeof L === 'undefined') {
            if (loadingNode) loadingNode.classList.add('hidden');
            if (errorNode) {
                errorNode.textContent = 'Unable to load the map right now.';
                errorNode.classList.remove('hidden');
            }
            return;
        }

        if (!concernRiskMapData.length) {
            mapNode.classList.add('hidden');
            if (loadingNode) loadingNode.classList.add('hidden');
            if (emptyNode) emptyNode.classList.remove('hidden');
            return;
        }

        if (emptyNode) emptyNode.classList.add('hidden');
        mapNode.classList.remove('hidden');
        if (errorNode) errorNode.classList.add('hidden');

        if (!concernMap) {
            concernMap = L.map(mapNode, { zoomControl: true, attributionControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(concernMap);
            concernMapMarkersLayer = L.layerGroup().addTo(concernMap);
        }

        concernMapMarkersLayer.clearLayers();

        const bounds = [];
        concernRiskMapData.forEach((record) => {
            const lat = Number(record.lat);
            const lng = Number(record.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const kind = String(record.entry_type || record.report_kind || 'risk').toLowerCase();
            const markerColor = kind === 'hazard' ? '#d97706' : '#0284c7';
            const markerIcon = kind === 'hazard' ? 'report_problem' : 'warning';
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'citizen-concern-map-icon',
                    html: `<div style="width:40px;height:40px;border-radius:999px;background:${markerColor};display:flex;align-items:center;justify-content:center;box-shadow:0 14px 26px rgba(15,23,42,.18);border:3px solid rgba(255,255,255,.92);color:#fff;"><span class="material-symbols-rounded" style="font-size:18px;line-height:1;">${markerIcon}</span></div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    popupAnchor: [0, -18],
                })
            });

            const photoHtml = record.photo_url ? `<img src="${escapeHtml(record.photo_url)}" alt="Risk or hazard preview" style="margin-top:12px;width:100%;max-height:160px;object-fit:cover;border-radius:14px;">` : '';
            marker.bindPopup(`
                <div style="min-width:220px;max-width:280px;">
                    <p style="font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#f59e0b;margin:0 0 6px;">${escapeHtml(readReportTypeLabel(record))}</p>
                    <h4 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">${escapeHtml(record.title || readReportCategory(record))}</h4>
                    <p style="margin:8px 0 0;font-size:13px;color:#475569;">${escapeHtml(readReportCategory(record))}</p>
                    <p style="margin:10px 0 0;font-size:13px;color:#334155;">${escapeHtml(record.description || record.remarks || record.assessment_description || 'No description provided.')}</p>
                    <div style="margin-top:12px;font-size:12px;color:#64748b;display:grid;gap:4px;">
                        <span><strong>Status:</strong> ${escapeHtml(readReportStatus(record))}</span>
                        <span><strong>Location:</strong> ${escapeHtml(readReportLocation(record))}</span>
                        <span><strong>Barangay:</strong> ${escapeHtml(readReportBarangay(record))}</span>
                        <span><strong>Submitted:</strong> ${escapeHtml(formatDate(record.created_at))}</span>
                    </div>
                    ${photoHtml}
                </div>
            `);

            marker.on('click', () => {
                activeConcernDetailRecord = record;
            });

            concernMapMarkersLayer.addLayer(marker);
            bounds.push([lat, lng]);
        });

        if (bounds.length === 1) {
            concernMap.setView(bounds[0], 16);
        } else if (bounds.length > 1) {
            concernMap.fitBounds(bounds, { padding: [30, 30] });
        } else if (window.userCoords?.currentLatitude && window.userCoords?.currentLongitude) {
            concernMap.setView([window.userCoords.currentLatitude, window.userCoords.currentLongitude], 14);
        } else {
            concernMap.setView([15.8222, 120.3297], 13);
        }

        window.setTimeout(() => {
            concernMap?.invalidateSize();
            if (loadingNode) loadingNode.classList.add('hidden');
        }, 120);
    }

    function openReportsHistoryModal() {
        if (!reportHistoryModal) return;
        if (reportsHistoryStandalone) {
            document.body.classList.add('citizen-history-standalone');
        }
        reportHistoryModal.classList.remove('hidden');
        reportHistoryModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        renderReportsHistoryTable();
    }

    function closeReportsHistoryModal() {
        if (!reportHistoryModal) return;
        reportHistoryModal.classList.add('hidden');
        reportHistoryModal.classList.remove('flex');
        document.body.style.overflow = '';
        if (reportsHistoryStandalone) {
            document.body.classList.remove('citizen-history-standalone');
        }
    }

    function openConcernDetailsModal(record) {
        if (!record || !concernDetailsModal) return;
        activeConcernDetailRecord = record;

        const setText = (id, value) => {
            const node = document.getElementById(id);
            if (node) node.textContent = value;
        };

        setText('citizenConcernDetailsEyebrow', readReportTypeLabel(record));
        setText('citizenConcernDetailsTitle', record.title || readReportCategory(record));
        setText('citizenConcernDetailsType', readReportTypeLabel(record));
        setText('citizenConcernDetailsCategory', readReportCategory(record));
        setText('citizenConcernDetailsStatus', readReportStatus(record));
        setText('citizenConcernDetailsDate', formatDate(record.created_at));
        setText('citizenConcernDetailsBarangay', readReportBarangay(record));
        setText('citizenConcernDetailsLocation', readReportLocation(record));
        setText('citizenConcernDetailsCoordinates', record.lat && record.lng ? `${Number(record.lat).toFixed(6)}, ${Number(record.lng).toFixed(6)}` : 'Coordinates unavailable');
        setText('citizenConcernDetailsNotes', record.description || record.remarks || record.assessment_description || record.condition_label || 'No additional notes available.');

        const imageWrap = document.getElementById('citizenConcernDetailsImageWrap');
        const imageNode = document.getElementById('citizenConcernDetailsImage');
        const imageUrl = record.photo_url || (Array.isArray(record.photos) && record.photos[0] ? record.photos[0].url : null);
        if (imageWrap && imageNode && imageUrl) {
            imageNode.src = imageUrl;
            imageWrap.classList.remove('hidden');
        } else if (imageWrap && imageNode) {
            imageNode.src = '';
            imageWrap.classList.add('hidden');
        }

        concernDetailsModal.classList.remove('hidden');
        concernDetailsModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeConcernDetailsModal() {
        if (!concernDetailsModal) return;
        concernDetailsModal.classList.add('hidden');
        concernDetailsModal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function findReportRecord(id, kind) {
        return concernReportsData.find((record) => {
            if (String(readReportKind(record)) !== String(kind)) return false;
            const key = kind === 'fire' ? record.incident_id : record.risk_area_id;
            return String(key) === String(id);
        }) || null;
    }

    function shareAlert(incidentId) {
        const incident = concernAlertsData.find((item) => String(item.incident_id) === String(incidentId));
        if (!incident) return;

        const shareData = {
            title: 'PyroSense Fire Alert',
            text: `Fire alert nearby. Status: ${readAlertStatusLabel(incident.incident_status || incident.status)}. ${incident.address || ''}`.trim(),
            url: window.location.href,
        };

        if (navigator.share) {
            navigator.share(shareData).catch(() => {});
            return;
        }

        if (navigator.clipboard) {
            navigator.clipboard.writeText(`${shareData.title}\n${shareData.text}\n${shareData.url}`).catch(() => {});
        }
    }

    function viewOnMap(lat, lng) {
        const parsedLat = Number(lat);
        const parsedLng = Number(lng);
        if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) return;

        window.switchSection?.('alerts');
        ensureConcernMap();
        if (concernMap) {
            concernMap.setView([parsedLat, parsedLng], Math.max(concernMap.getZoom() || 15, 16), { animate: true });
        }
    }

    window.refreshCitizenAlertsSection = function(payload) {
        concernAlertsData = Array.isArray(payload) ? payload : [];
        updateAreasConcernSummary();
        renderAreasConcernAlerts();
    };

    window.refreshCitizenAreasOfConcernReports = function(payload) {
        concernReportsData = Array.isArray(payload) ? payload : [];
        updateAreasConcernSummary();
        renderAreasConcernRecentReports();
        renderReportsHistoryTable();
    };

    window.refreshCitizenRiskConcernMap = function(payload) {
        concernRiskMapData = Array.isArray(payload) ? payload : [];
        updateAreasConcernSummary();
        ensureConcernMap();
    };

    window.showCitizenAlertDetailsModal = function(incidentId) {
        const record = concernAlertsData.find((item) => String(item.incident_id) === String(incidentId));
        if (record) openConcernDetailsModal(record);
    };

    window.closeCitizenAlertDetailsModal = closeConcernDetailsModal;
    window.shareAlert = shareAlert;
    window.viewOnMap = viewOnMap;

    document.getElementById('openCitizenReportsHistoryBtn')?.addEventListener('click', openReportsHistoryModal);
    document.querySelectorAll('[data-reports-history-close]').forEach((button) => button.addEventListener('click', closeReportsHistoryModal));
    document.querySelectorAll('[data-concern-details-close]').forEach((button) => button.addEventListener('click', closeConcernDetailsModal));
    document.getElementById('citizenConcernDetailsViewMapBtn')?.addEventListener('click', () => {
        if (activeConcernDetailRecord?.lat && activeConcernDetailRecord?.lng) {
            closeConcernDetailsModal();
            viewOnMap(activeConcernDetailRecord.lat, activeConcernDetailRecord.lng);
        }
    });

    reportsHistorySearch?.addEventListener('input', renderReportsHistoryTable);
    reportsHistoryTypeFilter?.addEventListener('change', renderReportsHistoryTable);
    areasConcernAlertSearch?.addEventListener('input', renderAreasConcernAlerts);
    areasConcernStatusFilter?.addEventListener('change', renderAreasConcernAlerts);
    areasConcernSortFilter?.addEventListener('change', renderAreasConcernAlerts);

    function closeAreasConcernFilterMenus(exceptKey = '') {
        document.querySelectorAll('[data-aoc-filter-menu]').forEach((menu) => {
            if (exceptKey && menu.dataset.aocFilterMenu === exceptKey) return;
            menu.hidden = true;
        });
        document.querySelectorAll('[data-aoc-filter-toggle]').forEach((button) => {
            if (exceptKey && button.dataset.aocFilterToggle === exceptKey) return;
            button.setAttribute('aria-expanded', 'false');
        });
    }

    function syncAreasConcernFilterOptions() {
        document.querySelectorAll('[data-aoc-filter-target]').forEach((option) => {
            const select = document.getElementById(option.dataset.aocFilterTarget || '');
            option.classList.toggle('is-active', Boolean(select && String(select.value) === String(option.dataset.aocFilterValue)));
        });
    }

    document.querySelectorAll('[data-aoc-filter-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const key = button.dataset.aocFilterToggle || '';
            const menu = document.querySelector(`[data-aoc-filter-menu="${key}"]`);
            if (!menu) return;
            const willOpen = menu.hidden;
            closeAreasConcernFilterMenus(key);
            menu.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            syncAreasConcernFilterOptions();
        });
    });

    document.querySelectorAll('[data-aoc-filter-target]').forEach((option) => {
        option.addEventListener('click', (event) => {
            event.stopPropagation();
            const select = document.getElementById(option.dataset.aocFilterTarget || '');
            if (!select) return;
            select.value = option.dataset.aocFilterValue || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            closeAreasConcernFilterMenus();
            syncAreasConcernFilterOptions();
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#section-alerts .aoc-filter-control')) {
            closeAreasConcernFilterMenus();
        }
    });

    reportHistoryModal?.addEventListener('click', (event) => {
        if (event.target === reportHistoryModal) closeReportsHistoryModal();
    });

    concernDetailsModal?.addEventListener('click', (event) => {
        if (event.target === concernDetailsModal) closeConcernDetailsModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeAreasConcernFilterMenus();
        if (reportHistoryModal && !reportHistoryModal.classList.contains('hidden')) closeReportsHistoryModal();
        if (concernDetailsModal && !concernDetailsModal.classList.contains('hidden')) closeConcernDetailsModal();
    });

    updateAreasConcernSummary();
    syncAreasConcernFilterOptions();
    renderAreasConcernAlerts();
    renderAreasConcernRecentReports();
    renderReportsHistoryTable();
    ensureConcernMap();

    if (pageParams.get('reports') === 'history') {
        window.setTimeout(() => {
            window.switchSection?.('alerts', { updateHistory: false });
            openReportsHistoryModal();

            const nextUrl = new URL(window.location.href);
            nextUrl.searchParams.delete('reports');
            nextUrl.searchParams.delete('standalone');
            nextUrl.searchParams.delete('source');
            window.history.replaceState(window.history.state, '', nextUrl.toString());
        }, 250);
    }
})();
</script>
