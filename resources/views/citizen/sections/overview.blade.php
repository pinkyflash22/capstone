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
                <p id="citizenOverviewTotalReportsValue" class="lector-sparkcard-amount">{{ $overviewReportCount ?? $countReport ?? 0 }}</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6" data-citizen-insights-source>
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">Incident Trends</h3>
                    <p id="incidentTrendRangeLabel" class="text-xs text-gray-400 mt-0.5">Showing reports for the selected period</p>
                </div>
                <div class="flex items-end gap-3 flex-wrap">
                    <label class="flex flex-col gap-1 text-xs text-gray-500 font-medium">
                        <span>Start date</span>
                        <input id="incidentTrendStartDate" type="date" class="border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white">
                    </label>
                    <label class="flex flex-col gap-1 text-xs text-gray-500 font-medium">
                        <span>End date</span>
                        <input id="incidentTrendEndDate" type="date" class="border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white">
                    </label>
                    <button id="incidentTrendResetBtn" type="button" class="lector-period-btn active">Reset</button>
                </div>
            </div>
            <div class="h-56 relative"><canvas id="incidentTrendChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col">
            <div class="mb-3">
                <h3 class="font-bold text-gray-800 text-base">Your Report Types</h3>
                <p class="text-xs text-gray-400 mt-0.5">Risk, hazard, and fire reports you submitted</p>
            </div>
            <div class="flex-1 relative" style="min-height:220px;"><canvas id="citizenReportTypeChart"></canvas></div>
            <div id="citizenReportTypeBreakdown" class="grid grid-cols-3 gap-x-2 gap-y-2 mt-4 text-center"></div>
        </div>
    </div>
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
    const userReportRecords = @json($citizenCombinedReports ?? []);
    const chartPalette = ['#dc2626', '#f97316', '#f59e0b', '#fb7185', '#38bdf8', '#8b5cf6', '#10b981'];
    const chartState = {};
    const trendRangeLabel = document.getElementById('incidentTrendRangeLabel');
    const trendStartDateInput = document.getElementById('incidentTrendStartDate');
    const trendEndDateInput = document.getElementById('incidentTrendEndDate');
    const trendResetBtn = document.getElementById('incidentTrendResetBtn');
    const trendFilterState = { start: '', end: '' };
    let currentIncidents = Array.isArray(incidents) ? incidents : [];
    let currentUserReports = Array.isArray(userReportRecords) ? userReportRecords : [];

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

    function classifyCitizenReport(record) {
        const descriptor = [
            record?.incident_type_name,
            record?.incident_type?.type,
            record?.incident_type,
            record?.type,
            record?.entry_type,
            record?.report_kind,
            record?.category
        ].filter(Boolean).join(' ').toLowerCase();

        if (descriptor.includes('risk')) {
            return 'Risk';
        }

        if (descriptor.includes('hazard')) {
            return 'Hazard';
        }

        return 'Fire';
    }

    function toDateKey(value) {
        const date = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(date.getTime())) {
            return null;
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDateLabel(dateKey) {
        const date = new Date(`${dateKey}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return dateKey;
        }

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    }

    function filterIncidentsByDateRange(source) {
        return source.filter((incident) => {
            const createdDateKey = toDateKey(incident.created_at);

            if (!createdDateKey) {
                return false;
            }

            if (trendFilterState.start && createdDateKey < trendFilterState.start) {
                return false;
            }

            if (trendFilterState.end && createdDateKey > trendFilterState.end) {
                return false;
            }

            return true;
        });
    }

    function processIncidentData(source, userSource) {
        const normalized = (Array.isArray(source) ? source : []).map(normalizeIncident);
        const filtered = filterIncidentsByDateRange(normalized);
        const reportTypeCounts = { Risk: 0, Hazard: 0, Fire: 0 };
        const trendCounts = new Map();

        filtered.forEach((incident) => {
            const createdDateKey = toDateKey(incident.created_at);
            if (!createdDateKey) {
                return;
            }

            trendCounts.set(createdDateKey, (trendCounts.get(createdDateKey) || 0) + 1);
        });

        (Array.isArray(userSource) ? userSource : []).forEach((record) => {
            const category = classifyCitizenReport(record);
            reportTypeCounts[category] = (reportTypeCounts[category] || 0) + 1;
        });

        const sortedTrendEntries = Array.from(trendCounts.entries()).sort((a, b) => a[0].localeCompare(b[0]));

        return {
            trendLabels: sortedTrendEntries.map(([dateKey]) => formatDateLabel(dateKey)),
            trendValues: sortedTrendEntries.map(([, count]) => count),
            reportTypeCounts,
        };
    }

    function getIncidentBounds(source) {
        const normalized = (Array.isArray(source) ? source : [])
            .map(normalizeIncident)
            .filter((incident) => toDateKey(incident.created_at))
            .sort((a, b) => String(a.created_at).localeCompare(String(b.created_at)));

        return {
            start: normalized[0] ? toDateKey(normalized[0].created_at) : '',
            end: normalized[normalized.length - 1] ? toDateKey(normalized[normalized.length - 1].created_at) : '',
        };
    }

    function renderTypeBreakdown(typeCounts) {
        const breakdown = document.getElementById('citizenReportTypeBreakdown');
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
            const breakdownColors = {
                Risk: '#7c3aed',
                Hazard: '#f59e0b',
                Fire: '#dc2626',
            };
            return `
                <div class="text-center">
                    <p class="text-base font-bold text-gray-800">${pct}<span class="text-xs font-normal text-gray-400">%</span></p>
                    <p class="text-[10px] text-gray-400 truncate" style="color:${breakdownColors[type] || chartPalette[index % chartPalette.length]};">${type}</p>
                </div>
            `;
        }).join('');
    }

    function createGradient(context, colors) {
        const gradient = context.createLinearGradient(0, 0, 0, 220);
        colors.forEach(([stop, color]) => gradient.addColorStop(stop, color));
        return gradient;
    }

    function syncTrendRangeLabel(labels) {
        if (!trendRangeLabel) {
            return;
        }

        if (trendFilterState.start || trendFilterState.end) {
            const startLabel = trendFilterState.start ? formatDateLabel(trendFilterState.start) : 'Beginning';
            const endLabel = trendFilterState.end ? formatDateLabel(trendFilterState.end) : 'Today';
            trendRangeLabel.textContent = `Showing reports from ${startLabel} to ${endLabel}`;
            return;
        }

        if (labels.length >= 2) {
            trendRangeLabel.textContent = `Showing reports from ${labels[0]} to ${labels[labels.length - 1]}`;
            return;
        }

        trendRangeLabel.textContent = labels.length ? `Showing reports for ${labels[0]}` : 'No reports found in the selected period';
    }

    function buildCharts(source, userSource) {
        const { trendLabels, trendValues, reportTypeCounts } = processIncidentData(source, userSource);
        const trendCtx = document.getElementById('incidentTrendChart')?.getContext('2d');
        const typeCtx = document.getElementById('citizenReportTypeChart')?.getContext('2d');

        if (trendCtx && !chartState.trend) {
            chartState.trend = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Reports',
                        data: trendValues,
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
            chartState.trend.data.labels = trendLabels;
            chartState.trend.data.datasets[0].data = trendValues;
            chartState.trend.update();
        }

        if (typeCtx && !chartState.type) {
            chartState.type = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(reportTypeCounts),
                    datasets: [{
                        data: Object.values(reportTypeCounts),
                        backgroundColor: ['#7c3aed', '#f59e0b', '#dc2626'],
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
            chartState.type.data.labels = Object.keys(reportTypeCounts);
            chartState.type.data.datasets[0].data = Object.values(reportTypeCounts);
            chartState.type.update();
        }

        renderTypeBreakdown(reportTypeCounts);
        syncTrendRangeLabel(trendLabels);
    }

    window.updateCitizenOverviewCharts = function(payload) {
        const source = Array.isArray(payload)
            ? { incidents: payload, user_reports: window.citizenCombinedReports || [] }
            : (payload || {});

        currentIncidents = Array.isArray(source.incidents) ? source.incidents : [];
        currentUserReports = Array.isArray(source.user_reports) ? source.user_reports : [];

        const bounds = getIncidentBounds(currentIncidents);
        if (trendStartDateInput) {
            trendStartDateInput.min = bounds.start || '';
            trendStartDateInput.max = bounds.end || '';
        }
        if (trendEndDateInput) {
            trendEndDateInput.min = bounds.start || '';
            trendEndDateInput.max = bounds.end || '';
        }

        buildCharts(
            currentIncidents,
            currentUserReports
        );
    };

    const initialBounds = getIncidentBounds(currentIncidents);
    const initialStart = initialBounds.start;
    const initialEnd = initialBounds.end;

    trendFilterState.start = initialStart;
    trendFilterState.end = initialEnd;

    if (trendStartDateInput) {
        trendStartDateInput.value = initialStart;
        trendStartDateInput.max = initialEnd || '';
        trendStartDateInput.addEventListener('change', function() {
            trendFilterState.start = this.value;
            if (trendEndDateInput && trendFilterState.end && trendFilterState.start && trendFilterState.start > trendFilterState.end) {
                trendFilterState.end = trendFilterState.start;
                trendEndDateInput.value = trendFilterState.end;
            }
            buildCharts(currentIncidents, currentUserReports);
        });
    }

    if (trendEndDateInput) {
        trendEndDateInput.value = initialEnd;
        trendEndDateInput.min = initialStart || '';
        trendEndDateInput.addEventListener('change', function() {
            trendFilterState.end = this.value;
            if (trendStartDateInput && trendFilterState.start && trendFilterState.end && trendFilterState.end < trendFilterState.start) {
                trendFilterState.start = trendFilterState.end;
                trendStartDateInput.value = trendFilterState.start;
            }
            buildCharts(currentIncidents, currentUserReports);
        });
    }

    trendResetBtn?.addEventListener('click', function() {
        const currentBounds = getIncidentBounds(currentIncidents);
        trendFilterState.start = currentBounds.start;
        trendFilterState.end = currentBounds.end;

        if (trendStartDateInput) {
            trendStartDateInput.value = currentBounds.start;
        }

        if (trendEndDateInput) {
            trendEndDateInput.value = currentBounds.end;
        }

        buildCharts(currentIncidents, currentUserReports);
    });

    window.citizenCombinedReports = Array.isArray(window.citizenCombinedReports) ? window.citizenCombinedReports : currentUserReports;
    buildCharts(currentIncidents, window.citizenCombinedReports);

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


