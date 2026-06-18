{{-- ═══════════════════════════════════════════════════════════
     OVERVIEW SECTION  —  Lector-inspired Layout
     PyroSense · Citizen Dashboard
═══════════════════════════════════════════════════════════ --}}
<div id="section-overview" class="content-section active">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-2 citizen-overview-welcome">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                {{ Auth::user()->userInfo->address ?? '2230 Pine Rd · Zone 7' }}
            </p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-gray-100 px-3 py-2 rounded-xl shadow-sm text-sm text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
            {{ now()->format('l, M d Y') }}
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" data-citizen-insights-source>
        <div class="lector-sparkcard" style="background:linear-gradient(135deg,#e91e8c 0%,#f48fb1 100%);">
            <div class="lector-sparkcard-inner">
                <p class="lector-sparkcard-title">Total Reports</p>
                <svg class="lector-sparkcard-wave" viewBox="0 0 120 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,35 18,25 35,30 52,18 70,23 88,12 105,16 120,8" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <polygon points="0,35 18,25 35,30 52,18 70,23 88,12 105,16 120,8 120,40 0,40" fill="rgba(255,255,255,0.12)"/>
                </svg>
                <p id="citizenOverviewTotalReportsValue" class="lector-sparkcard-amount">{{ $countReport ?? 0 }}</p>
                <p class="lector-sparkcard-date">{{ now()->subDays(30)->format('M d') }} â€“ {{ now()->format('M d') }}</p>
            </div>
        </div>

        <div class="lector-sparkcard" style="background:linear-gradient(135deg,#7c3aed 0%,#a78bfa 100%);">
            <div class="lector-sparkcard-inner">
                <p class="lector-sparkcard-title">Active Alerts</p>
                <svg class="lector-sparkcard-wave" viewBox="0 0 120 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,30 18,20 35,26 52,13 70,19 88,9 105,13 120,6" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <polygon points="0,30 18,20 35,26 52,13 70,19 88,9 105,13 120,6 120,40 0,40" fill="rgba(255,255,255,0.12)"/>
                </svg>
                <p id="citizenOverviewActiveAlertsValue" class="lector-sparkcard-amount">{{ $activeAlerts ?? 0 }}</p>
                <p class="lector-sparkcard-date">Active right now</p>
            </div>
        </div>

        <div class="lector-sparkcard" style="background:linear-gradient(135deg,#0ea5e9 0%,#38bdf8 100%);">
            <div class="lector-sparkcard-inner">
                <p class="lector-sparkcard-title">Risk Index</p>
                <svg class="lector-sparkcard-wave" viewBox="0 0 120 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,22 18,28 35,16 52,30 70,14 88,20 105,10 120,17" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <polygon points="0,22 18,28 35,16 52,30 70,14 88,20 105,10 120,17 120,40 0,40" fill="rgba(255,255,255,0.12)"/>
                </svg>
                <p id="citizenOverviewRiskPercentageValue" class="lector-sparkcard-amount">{{ $riskPercentage ?? 65 }}<span class="text-lg font-medium">%</span></p>
                <p id="citizenOverviewRiskLevelLabel" class="lector-sparkcard-date">{{ ucfirst($riskLevel ?? 'Moderate') }} risk</p>
            </div>
        </div>

        <div class="lector-sparkcard" style="background:linear-gradient(135deg,#f59e0b 0%,#fbbf24 100%);">
            <div class="lector-sparkcard-inner">
                <p class="lector-sparkcard-title">Safety Score</p>
                <svg class="lector-sparkcard-wave" viewBox="0 0 120 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="0,38 12,28 25,32 38,20 52,25 66,16 80,20 95,10 108,6 120,4" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <polygon points="0,38 12,28 25,32 38,20 52,25 66,16 80,20 95,10 108,6 120,4 120,40 0,40" fill="rgba(255,255,255,0.12)"/>
                </svg>
                <p class="lector-sparkcard-amount">85<span class="text-lg font-medium">%</span></p>
                <p class="lector-sparkcard-date">Excellent Â· â†‘ 12%</p>
            </div>
        </div>
    </div>

    <div class="citizen-map-stage mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between px-5 pt-5 pb-3 gap-3">
            <div><h2 class="font-bold text-gray-800 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" style="color:var(--c-primary);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>Incidents &amp; Safety Near You</h2><p class="text-xs text-gray-400 mt-0.5">Real-time map · 2 mile radius</p></div>
            <div class="flex items-center gap-2 flex-wrap"><span class="lector-live-pill"><span class="lector-live-dot"></span> live</span><button class="lector-btn-outline"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>Mark myself safe</button><button id="reportFire" type="button" class="lector-btn-primary report-incident-trigger"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 9.526a2 2 0 0 0 1.973 1.679l4.154-.05.374 2.237A2 2 0 0 0 13.133 18H19.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 3H21m0 0v9.75M21 3 12 12"/></svg>Report Incident</button></div>
        </div>
        <div id="citizenMapLegendPanel" class="citizen-map-legend"><div class="citizen-map-legend-header"><div class="citizen-map-legend-title">Map Legend</div><div class="citizen-map-legend-note">Live route, marker, and responder symbols</div></div><div class="citizen-map-legend-grid"><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-user"><span class="citizen-map-legend-marker citizen-map-legend-marker-user"><i class="fas fa-user-shield"></i></span></span><span>Your location</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-station"><span class="citizen-map-legend-marker citizen-map-legend-marker-station"><i class="fas fa-building"></i></span></span><span>Fire station</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-barangay"><span class="citizen-map-legend-marker citizen-map-legend-marker-barangay"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="9" r="2" fill="none"></circle><circle cx="16" cy="9" r="2" fill="none"></circle><circle cx="12" cy="7" r="2" fill="none"></circle><path d="M4 18a4 4 0 0 1 8 0M12 18a4 4 0 0 1 8 0M8 16a4 4 0 0 1 8 0"></path></svg></span></span><span>Barangay hall</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-incident"><span class="citizen-map-legend-marker citizen-map-legend-marker-incident"><i class="fas fa-fire"></i></span></span><span>Incident marker</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-hydrant"><span class="citizen-map-legend-marker citizen-map-legend-marker-hydrant"><i class="fas fa-fire-extinguisher"></i></span></span><span>Hydrant</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-other"><span class="citizen-map-legend-marker citizen-map-legend-marker-other"><i class="fas fa-map-marker-alt"></i></span></span><span>Other marker</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-route"><span class="citizen-map-legend-route-line"></span></span><span>Emergency route</span></div><div class="citizen-map-legend-item"><span class="citizen-map-legend-swatch citizen-map-legend-swatch-truck"><img src="/storage/defaults/firetruck.png" alt="Emergency truck"></span><span>Truck animation</span></div></div></div>
        <div class="citizen-map-shell">
            <div class="citizen-map-search citizen-map-search--overlay" id="citizenMapSearch">
                <div class="citizen-map-search__input-wrap">
                    <i class="fa-solid fa-magnifying-glass citizen-map-search__icon"></i>
                    <input id="citizenMapSearchInput" type="search" class="citizen-map-search__input" placeholder="Search incidents, hazards, hydrants, stations, or a location" autocomplete="off" spellcheck="false">
                    <button id="citizenMapSearchClear" type="button" class="citizen-map-search__clear hidden" aria-label="Clear map search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="citizen-map-search__hint">Try: Fire in Barangay X, hydrant, octopus wiring area, or a street/location</div>
                <div id="citizenMapSearchResults" class="citizen-map-search__results hidden" aria-live="polite"></div>
            </div>
            <button type="button" class="citizen-map-layer-trigger md:hidden" data-citizen-map-layers-open aria-label="Open map filters">
                <span class="material-symbols-rounded" aria-hidden="true">layers</span>
            </button>
            <div id="userMap" style="height:380px; width:100%; border-radius:0;"></div><aside id="citizenMapDetailsHost" class="citizen-map-details-host" aria-live="polite"></aside>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6" data-citizen-insights-source><div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5"><div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4"><div><h3 class="font-bold text-gray-800 text-base">Incident Trends</h3><p class="text-xs text-gray-400 mt-0.5">Overview of Latest Week</p></div><div class="flex items-center gap-3 flex-wrap"><div class="flex gap-0.5 bg-gray-100 p-1 rounded-lg"><button class="chart-period-btn lector-period-btn active" data-period="week">Weekly</button><button class="chart-period-btn lector-period-btn" data-period="month">Monthly</button><button class="chart-period-btn lector-period-btn" data-period="year">Yearly</button></div><div class="flex gap-3"><span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#e91e8c;"></span>Reported</span><span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#a78bfa;"></span>Resolved</span></div></div></div><div class="h-56 relative"><canvas id="incidentTrendChart"></canvas></div></div><div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col"><div class="mb-3"><h3 class="font-bold text-gray-800 text-base">Incident Types</h3><p class="text-xs text-gray-400 mt-0.5">Latest Month</p></div><div class="flex-1 relative" style="min-height:160px;"><canvas id="incidentTypeChart"></canvas></div><div id="typeBreakdown" class="grid grid-cols-3 gap-x-2 gap-y-2 mt-4 text-center"></div></div></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6" data-citizen-insights-source><div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"><h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>Incident Status</h3><div class="h-48 relative"><canvas id="incidentStatusChart"></canvas></div></div><div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"><h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:var(--c-secondary);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>Peak Hours</h3><div class="h-48 relative"><canvas id="hourlyDistributionChart"></canvas></div></div><div class="rounded-2xl shadow-sm border border-gray-100 p-5" style="background:linear-gradient(135deg,#fce4ec 0%,#ede9fe 100%);"><h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:var(--c-primary);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>Current Risk Level</h3><div class="text-center"><div class="relative inline-block"><svg class="w-32 h-32" viewBox="0 0 128 128"><circle stroke-width="10" stroke="#e5e7eb" fill="transparent" r="54" cx="64" cy="64"/><circle stroke-width="10" stroke="url(#riskGrad)" fill="transparent" r="54" cx="64" cy="64" stroke-dasharray="339" stroke-dashoffset="{{ 339 - (339 * ($riskPercentage ?? 65) / 100) }}" stroke-linecap="round" transform="rotate(-90 64 64)"/><defs><linearGradient id="riskGrad" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#e91e8c"/><stop offset="100%" stop-color="#7c3aed"/></linearGradient></defs></svg><div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-3xl font-bold text-gray-800">{{ $riskPercentage ?? 65 }}%</span><span class="text-xs text-gray-500">risk index</span></div></div><div class="mt-3"><span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium @if(($riskLevel ?? 'moderate') == 'low') bg-green-100 text-green-700 @elseif(($riskLevel ?? 'moderate') == 'moderate') bg-yellow-100 text-yellow-700 @else bg-pink-100 text-pink-700 @endif">{{ ucfirst($riskLevel ?? 'moderate') }} Risk</span></div></div></div></div>
</div>{{-- end #section-overview --}}

<div id="topRoutePanelHost" style="display:none; position:fixed; right:24px; bottom:180px; z-index:520; width:min(380px, calc(100vw - 32px));"></div>

{{-- ═══════════════════════════════════════════════════════════
     FLOATING ALERTS BUTTON + SLIDE PANEL
═══════════════════════════════════════════════════════════ --}}

{{-- FAB Bell --}}
<button id="alertsFab" aria-label="View alerts for your area" class="alerts-fab">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
    </svg>
    @if(($activeAlerts ?? 0) > 0)
        <span id="citizenAlertsFabBadge" class="alerts-fab-badge">{{ $activeAlerts ?? 0 }}</span>
    @endif
</button>

<button type="button" class="report-fab report-incident-trigger" aria-label="Report incident">
    <i class="fas fa-bullhorn report-fab-icon"></i>
</button>

{{-- Backdrop --}}
<div id="alertsBackdrop" class="alerts-backdrop"></div>

{{-- Slide Panel --}}
<div id="alertsPanel" class="alerts-panel">
    <div class="alerts-panel-header">
        <div>
            <h3 class="font-bold text-gray-800 text-base">Alerts for Your Area</h3>
            <p id="citizenNearbyAlertsCount" class="text-xs text-gray-400 mt-0.5">{{ ($nearbyIncidents ?? collect())->count() }} incident(s) nearby</p>
        </div>
        <button id="closeAlertsPanel" class="alerts-panel-close" aria-label="Close alerts">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Incident list --}}
    <div id="citizenAlertsPanelList" class="alerts-panel-list">
        <div class="text-center py-12 text-gray-400">
            <span class="inline-fa-icon icon-spin mx-auto mb-3" style="width:2rem;height:2rem;color:var(--c-primary);">
                <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
            </span>
            <p class="text-sm font-medium">Finding incident alerts near you</p>
            <p class="text-xs mt-1">Checking incidents within 2 km of your current location...</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     CHARTS SCRIPT  (data from Laravel, keep logic intact)
═══════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const incidents = @json($incidents);
    const chartPalette = ['#dc2626', '#f97316', '#f59e0b', '#fb7185', '#38bdf8', '#8b5cf6', '#10b981'];
    const chartState = {};

    function readType(incident) {
        return incident?.incident_type?.type
            || incident?.incidentType?.type
            || incident?.incident_type
            || 'Unknown';
    }

    function readStatus(incident) {
        return incident?.incident_status?.status
            || incident?.incidentStatus?.status
            || incident?.incident_status
            || 'Unknown';
    }

    function normalizeIncident(incident) {
        return {
            type: readType(incident),
            status: readStatus(incident),
            created_at: incident?.created_at || null
        };
    }

    function processIncidentData(source) {
        const normalized = (Array.isArray(source) ? source : []).map(normalizeIncident);
        const typeCounts = {};
        const statusCounts = {};
        const hourlyCounts = Array(24).fill(0);
        const dailyCounts = Array(7).fill(0);

        normalized.forEach((incident) => {
            typeCounts[incident.type] = (typeCounts[incident.type] || 0) + 1;
            statusCounts[incident.status] = (statusCounts[incident.status] || 0) + 1;

            if (incident.created_at) {
                const created = new Date(incident.created_at);
                if (!Number.isNaN(created.getTime())) {
                    hourlyCounts[created.getHours()] += 1;
                    dailyCounts[created.getDay()] += 1;
                }
            }
        });

        return { typeCounts, statusCounts, hourlyCounts, dailyCounts };
    }

    function renderTypeBreakdown(typeCounts) {
        const breakdown = document.getElementById('typeBreakdown');
        if (!breakdown) {
            return;
        }

        const entries = Object.entries(typeCounts);
        const total = entries.reduce((sum, [, count]) => sum + count, 0);

        if (!entries.length || total === 0) {
            breakdown.innerHTML = '<div class="col-span-3 text-xs text-gray-400 py-3">No incident data yet</div>';
            return;
        }

        breakdown.innerHTML = entries.map(([type, count], index) => {
            const pct = Math.round((count / total) * 100);
            return `
                <div class="text-center">
                    <p class="text-base font-bold text-gray-800">${pct}<span class="text-xs font-normal text-gray-400">%</span></p>
                    <p class="text-[10px] text-gray-400 truncate" style="color:${chartPalette[index % chartPalette.length]};">${type}</p>
                </div>
            `;
        }).join('');
    }

    function createGradient(context, colors) {
        const gradient = context.createLinearGradient(0, 0, 0, 220);
        colors.forEach(([stop, color]) => gradient.addColorStop(stop, color));
        return gradient;
    }

    function buildCharts(source) {
        const { typeCounts, statusCounts, hourlyCounts, dailyCounts } = processIncidentData(source);
        const trendCtx = document.getElementById('incidentTrendChart')?.getContext('2d');
        const typeCtx = document.getElementById('incidentTypeChart')?.getContext('2d');
        const statusCtx = document.getElementById('incidentStatusChart')?.getContext('2d');
        const hourlyCtx = document.getElementById('hourlyDistributionChart')?.getContext('2d');

        if (trendCtx && !chartState.trend) {
            chartState.trend = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    datasets: [{
                        label: 'Incidents',
                        data: dailyCounts,
                        borderColor: '#dc2626',
                        backgroundColor: createGradient(trendCtx, [
                            [0, 'rgba(220, 38, 38, 0.28)'],
                            [0.65, 'rgba(249, 115, 22, 0.12)'],
                            [1, 'rgba(251, 191, 36, 0.03)']
                        ]),
                        borderWidth: 3,
                        tension: 0.45,
                        fill: true,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 } } },
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } }
                    }
                }
            });
        } else if (chartState.trend) {
            chartState.trend.data.datasets[0].data = dailyCounts;
            chartState.trend.update();
        }

        if (typeCtx && !chartState.type) {
            chartState.type = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(typeCounts),
                    datasets: [{
                        data: Object.values(typeCounts),
                        backgroundColor: Object.keys(typeCounts).map((_, index) => chartPalette[index % chartPalette.length]),
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '65%'
                }
            });
        } else if (chartState.type) {
            chartState.type.data.labels = Object.keys(typeCounts);
            chartState.type.data.datasets[0].data = Object.values(typeCounts);
            chartState.type.data.datasets[0].backgroundColor = Object.keys(typeCounts).map((_, index) => chartPalette[index % chartPalette.length]);
            chartState.type.update();
        }

        if (statusCtx && !chartState.status) {
            chartState.status = new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(statusCounts).map((status) => status.charAt(0).toUpperCase() + status.slice(1)),
                    datasets: [{
                        data: Object.values(statusCounts),
                        backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#dc2626'],
                        borderRadius: 8,
                        barPercentage: 0.65
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 } } },
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } }
                    }
                }
            });
        } else if (chartState.status) {
            chartState.status.data.labels = Object.keys(statusCounts).map((status) => status.charAt(0).toUpperCase() + status.slice(1));
            chartState.status.data.datasets[0].data = Object.values(statusCounts);
            chartState.status.update();
        }

        if (hourlyCtx && !chartState.hourly) {
            chartState.hourly = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: Array.from({ length: 24 }, (_, index) => `${String(index).padStart(2, '0')}:00`).filter((_, index) => index % 3 === 0),
                    datasets: [{
                        label: 'Incidents',
                        data: hourlyCounts.filter((_, index) => index % 3 === 0),
                        borderColor: '#f97316',
                        backgroundColor: createGradient(hourlyCtx, [
                            [0, 'rgba(249, 115, 22, 0.24)'],
                            [1, 'rgba(249, 115, 22, 0.02)']
                        ]),
                        borderWidth: 2,
                        tension: 0.45,
                        fill: true,
                        pointBackgroundColor: '#f97316',
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } }
                    }
                }
            });
        } else if (chartState.hourly) {
            chartState.hourly.data.datasets[0].data = hourlyCounts.filter((_, index) => index % 3 === 0);
            chartState.hourly.update();
        }

        renderTypeBreakdown(typeCounts);
    }

    window.updateCitizenOverviewCharts = function(payload) {
        buildCharts(Array.isArray(payload) ? payload : []);
    };

    buildCharts(incidents);

    document.querySelectorAll('.chart-period-btn').forEach((button) => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.chart-period-btn').forEach((candidate) => candidate.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const fab        = document.getElementById('alertsFab');
    const panel      = document.getElementById('alertsPanel');
    const backdrop   = document.getElementById('alertsBackdrop');
    const closeBtn   = document.getElementById('closeAlertsPanel');

    function openPanel() {
        panel.classList.add('open');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closePanel() {
        panel.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (fab)      fab.addEventListener('click', openPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (backdrop) backdrop.addEventListener('click', closePanel);
    window.openCitizenAlertsPanel = openPanel;
    window.closeCitizenAlertsPanel = closePanel;

    function attachCitizenLegendToLayerControl() {
        const legendPanel = document.getElementById('citizenMapLegendPanel');
        const legendHeader = legendPanel ? legendPanel.querySelector('.citizen-map-legend-header') : null;

        if (!legendPanel) {
            return;
        }

        if (legendHeader && !legendHeader.dataset.toggleBound) {
            legendHeader.addEventListener('click', () => {
                legendPanel.classList.toggle('is-collapsed');
            });
            legendHeader.dataset.toggleBound = 'true';
        }
    }

    window.setTimeout(attachCitizenLegendToLayerControl, 250);
    window.addEventListener('resize', attachCitizenLegendToLayerControl);

    // Escape key closes panel
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });
});
</script>

<style>
/* ── Period toggle buttons ─────────────────────────────── */
.lector-period-btn {
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 500;
    border-radius: 7px;
    transition: all 0.2s ease;
    cursor: pointer;
    color: #6b7280;
    background: transparent;
    border: none;
}
.lector-period-btn.active {
    background: linear-gradient(135deg,#dc2626,#f59e0b) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(220,38,38,0.28);
}
.lector-period-btn:not(.active):hover { background: rgba(220,38,38,0.08); color: #dc2626; }

@media (max-width: 768px) {
    #alertsFab,
    .report-fab {
        display: none !important;
    }
}
</style>


