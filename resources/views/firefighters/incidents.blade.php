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
            grid-template-columns: minmax(0, 1.2fr) minmax(220px, .7fr) minmax(180px, .45fr) auto;
            gap: 14px; align-items: center;
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
        .incident-actions { display: flex; flex-direction: column; gap: 10px; min-width: 158px; }
        .action-btn {
            display: inline-flex; justify-content: center; align-items: center; gap: 8px; height: 42px; padding: 0 16px;
            border-radius: 14px; border: 0; cursor: pointer; font-size: .85rem; font-weight: 700; transition: all .2s ease;
        }
        .action-btn.primary { background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color: #fff; }
        .action-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(198,61,38,.22); }
        .action-btn.ghost { background: #fff7f3; color: #9a3412; border: 1px solid rgba(198,61,38,.12); }
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
        .incident-modal {
            position: fixed; inset: 0; display: none; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, .55); z-index: 2600; padding: 20px;
        }
        .incident-modal.active { display: flex; }
        .incident-modal-panel {
            width: min(920px, 100%); max-height: calc(100vh - 40px); overflow: hidden; border-radius: 26px; background: #fff;
            border: 1px solid rgba(226,232,240,.9); box-shadow: 0 24px 56px rgba(15,23,42,.2);
        }
        .incident-modal-header {
            display: flex; justify-content: space-between; gap: 16px; align-items: flex-start;
            padding: 24px 24px 18px; border-bottom: 1px solid rgba(226,232,240,.86);
            background: linear-gradient(135deg, #fff4ec 0%, #fff7f1 100%);
        }
        .incident-modal-title { font-size: 1.2rem; font-weight: 800; color: #172033; }
        .incident-modal-subtitle { margin-top: 6px; color: #64748b; font-size: .9rem; }
        .incident-modal-close { width: 42px; height: 42px; border-radius: 999px; border: 0; cursor: pointer; background: #fff; color: #9a3412; }
        .incident-modal-body { padding: 24px; overflow-y: auto; max-height: calc(100vh - 180px); display: grid; gap: 20px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .detail-card {
            padding: 16px; border-radius: 18px; background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226,232,240,.9);
        }
        .detail-label {
            margin-bottom: 6px; color: #94a3b8; font-size: .72rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .detail-value { color: #172033; font-size: .92rem; font-weight: 600; line-height: 1.5; word-break: break-word; }
        .detail-description { padding: 18px; border-radius: 18px; border: 1px solid rgba(226,232,240,.9); background: #fff; }
        .detail-description h3 { margin: 0 0 10px; color: #172033; font-size: .98rem; font-weight: 800; }
        .detail-description p { margin: 0; color: #475569; line-height: 1.7; font-size: .94rem; }
        .detail-image { width: 100%; max-height: 260px; object-fit: cover; border-radius: 18px; border: 1px solid rgba(226,232,240,.9); }
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
            .stats-grid, .detail-grid, .incident-meta { grid-template-columns: 1fr; }
            .main-content { padding: 12px 14px calc(112px + env(safe-area-inset-bottom, 0px)); }
            .incidents-shell { gap: 16px; }
            .incidents-summary,
            .list-card {
                border-radius: 20px;
                padding-left: 16px;
                padding-right: 16px;
            }
            .incidents-summary h1 { font-size: 1.72rem; }
            .incidents-summary p { font-size: .9rem; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .stat-card { padding: 16px; border-radius: 18px; }
            .stat-card-value { font-size: 1.5rem; }
            .toolbar-card { padding: 14px 16px; gap: 10px; }
            .filter-tabs { padding: 0 16px 16px; overflow-x: auto; flex-wrap: nowrap; }
            .filter-tab { white-space: nowrap; }
            .list-card-header { padding: 18px 16px 14px; }
            .incident-list,
            .shimmer-grid { padding: 12px 16px 18px; }
            .incident-item { grid-template-columns: 1fr; padding: 16px; border-radius: 18px; }
            .incident-item { grid-template-columns: 1fr; }
            .incident-image { width: 56px; height: 56px; }
            .incident-actions, .shimmer-actions { flex-direction: column; }
            .action-btn { width: 100%; }
            .incident-modal { padding: 10px; align-items: flex-end; }
            .incident-modal-panel {
                width: 100%;
                max-height: min(84dvh, 760px);
                border-radius: 24px 24px 18px 18px;
            }
            .incident-modal-body { padding: 16px; max-height: calc(84dvh - 118px); }
        }
        @media (max-width: 430px) {
            .stats-grid { grid-template-columns: 1fr; }
            .summary-pills { width: 100%; }
            .summary-pill { width: 100%; justify-content: center; }
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
                    <input type="search" id="incidentSearch" class="search-field" placeholder="Search title, type, reporter, location, or incident ID">
                    <select id="incidentTypeFilter" class="select-field"><option value="all">All incident types</option></select>
                    <select id="incidentSort" class="select-field">
                        <option value="reported_desc">Newest reported</option>
                        <option value="reported_asc">Oldest reported</option>
                        <option value="updated_desc">Latest updated</option>
                        <option value="updated_asc">Oldest updated</option>
                        <option value="type_asc">Type A-Z</option>
                    </select>
                    <button type="button" class="action-btn ghost" id="refreshIncidentsBtn"><i class="fa-solid fa-rotate"></i> Refresh</button>
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
        const INCIDENT_REGISTRY_POLL_INTERVAL_MS = 30000;
        const incidentState = { incidents: [], filter: 'all', search: '', type: 'all', sort: 'reported_desc', poller: null, request: null, lastFetchedAt: 0 };
        const listState = document.getElementById('incidentListState');
        const resultsCount = document.getElementById('resultsCount');
        const typeFilter = document.getElementById('incidentTypeFilter');
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
            document.getElementById('statTotal').textContent = stats.total ?? 0;
            document.getElementById('statActive').textContent = stats.active ?? 0;
            document.getElementById('statControlled').textContent = stats.controlled ?? 0;
            document.getElementById('statClosed').textContent = stats.closed ?? 0;
            document.getElementById('statAvgResponse').textContent = `${stats.avg_response_minutes ?? 0}m`;
        }

        function buildTypeOptions(incidents) {
            const currentValue = incidentState.type;
            const types = [...new Set((incidents || []).map(incident => incident.incident_type_name).filter(Boolean))].sort((a, b) => a.localeCompare(b));
            typeFilter.innerHTML = `<option value="all">All incident types</option>${types.map(type => `<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`).join('')}`;
            typeFilter.value = (types.includes(currentValue) || currentValue === 'all') ? currentValue : 'all';
        }

        function getFilteredIncidents() {
            const search = incidentState.search.trim().toLowerCase();
            const filtered = incidentState.incidents.filter((incident) => {
                const bucket = getStatusBucket(incident.incident_status_id);
                if (incidentState.filter !== 'all' && bucket !== incidentState.filter) return false;
                if (incidentState.type !== 'all' && incident.incident_type_name !== incidentState.type) return false;
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
                    case 'type_asc': return String(left.incident_type_name || '').localeCompare(String(right.incident_type_name || ''));
                    case 'reported_desc':
                    default: return new Date(right.reported_at || 0) - new Date(left.reported_at || 0);
                }
            });

            return filtered;
        }

        function renderIncidentList() {
            const incidents = getFilteredIncidents();
            resultsCount.textContent = `${incidents.length} incident${incidents.length === 1 ? '' : 's'}`;

            if (!incidents.length) {
                listState.innerHTML = `<div class="list-empty"><i class="fa-solid fa-folder-open"></i><div class="text-lg font-semibold text-slate-700">No incidents match the current filters.</div><div class="mt-2 text-sm text-slate-500">Try clearing the search or switching to another incident category.</div></div>`;
                return;
            }

            listState.innerHTML = `<div class="incident-list">${incidents.map((incident) => {
                const imageUrl = resolveStorageUrl(incident.image_url);
                const statusBucket = getStatusBucket(incident.incident_status_id);
                const statusLabel = statusBucket === 'active' ? 'Available / Active' : (statusBucket === 'controlled' ? 'Controlled' : 'Closed');
                return `
                    <article class="incident-item">
                        <div class="incident-image">
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Incident Image">` : `<i class="fa-solid ${getIncidentTypeIcon(incident.incident_type_name)}"></i>`}
                        </div>
                        <div class="incident-main">
                            <div class="incident-topline">
                                <span class="incident-chip chip-type"><i class="fa-solid ${getIncidentTypeIcon(incident.incident_type_name)}"></i>${escapeHtml(incident.incident_type_name || 'Incident')}</span>
                                <span class="incident-chip ${getStatusChipClass(incident.incident_status_id)}">${escapeHtml(statusLabel)}</span>
                                <span class="incident-chip chip-type">#${escapeHtml(incident.incident_id)}</span>
                            </div>
                            <h3 class="incident-title">${escapeHtml(incident.title || `Incident #${incident.incident_id}`)}</h3>
                            <p class="incident-description">${escapeHtml(incident.description || 'No incident description was recorded for this response.')}</p>
                            <div class="incident-meta">
                                <div class="incident-meta-item"><span class="incident-meta-label">Location</span>${escapeHtml(incident.location || 'Unknown location')}</div>
                                <div class="incident-meta-item"><span class="incident-meta-label">Reported By</span>${escapeHtml(incident.reporter_name || 'Concerned Citizen')}</div>
                                <div class="incident-meta-item"><span class="incident-meta-label">Reported At</span>${escapeHtml(formatDateTime(incident.reported_at))}</div>
                                <div class="incident-meta-item"><span class="incident-meta-label">Last Updated</span>${escapeHtml(formatDateTime(incident.updated_at))}</div>
                            </div>
                        </div>
                        <div class="incident-actions">
                            <button type="button" class="action-btn primary" onclick="openIncidentDetails(${incident.incident_id})"><i class="fa-solid fa-eye"></i> View Details</button>
                            <button type="button" class="action-btn ghost" onclick="copyIncidentId(${incident.incident_id})"><i class="fa-regular fa-copy"></i> Copy ID</button>
                        </div>
                    </article>`;
            }).join('')}</div>`;
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
                buildTypeOptions(incidentState.incidents);
                renderIncidentList();
            } catch (error) {
                console.error('Incident registry fetch failed:', error);
                listState.innerHTML = `<div class="list-empty"><i class="fa-solid fa-triangle-exclamation"></i><div class="text-lg font-semibold text-slate-700">Failed to load incident records.</div><div class="mt-2 text-sm text-slate-500">${escapeHtml(error.message || 'Unknown error')}</div></div>`;
            } finally {
                incidentState.request = null;
            }
        }

        function openIncidentDetails(incidentId) {
            const incident = incidentState.incidents.find(item => Number(item.incident_id) === Number(incidentId));
            if (!incident) {
                showToast('Incident details not found.', true);
                return;
            }

            window.logUserInteraction?.('viewed incident details');

            const modal = document.getElementById('incidentDetailModal');
            const modalBody = document.getElementById('incidentModalBody');
            const modalTitle = document.getElementById('incidentModalTitle');
            const modalSubtitle = document.getElementById('incidentModalSubtitle');
            const imageUrl = resolveStorageUrl(incident.image_url);

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
            document.getElementById('incidentDetailModal').classList.remove('active');
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
                document.querySelectorAll('.filter-tab').forEach(button => button.classList.toggle('active', button === tab));
                renderIncidentList();
            });
            searchInput.addEventListener('input', (event) => { incidentState.search = event.target.value || ''; renderIncidentList(); });
            typeFilter.addEventListener('change', (event) => { incidentState.type = event.target.value || 'all'; renderIncidentList(); });
            sortSelect.addEventListener('change', (event) => { incidentState.sort = event.target.value || 'reported_desc'; renderIncidentList(); });
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
