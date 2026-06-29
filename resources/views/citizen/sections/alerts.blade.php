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

<div id="section-alerts" class="content-section">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-slate-800">Areas of Concern</h1>
            <p class="text-sm text-slate-500 mt-1">Track active fire alerts near you, review your submitted reports, and inspect mapped risk or hazard factors.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">
                <i class="fa-solid fa-fire"></i>
                <span id="areasConcernActiveAlertsValue">{{ $nearbyAlertCollection->count() }}</span> active fire alerts
            </span>
            <button
                type="button"
                id="openCitizenReportsHistoryBtn"
                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#c63d26] to-[#e39b17] px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_34px_rgba(198,61,38,0.22)] transition hover:brightness-105"
            >
                <i class="fa-solid fa-clock-rotate-left"></i>
                View My Reports History
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
        <div class="rounded-3xl border border-red-100 bg-gradient-to-br from-white via-red-50 to-orange-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Active Fire Alerts</p>
                    <p id="areasConcernAlertCountCard" class="mt-2 text-3xl font-bold text-slate-900">{{ $nearbyAlertCollection->count() }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <i class="fa-solid fa-fire text-xl"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Nearby fire incidents still in reported, responding, or on-scene status.</p>
        </div>

        <div class="rounded-3xl border border-amber-100 bg-gradient-to-br from-white via-amber-50 to-orange-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">My Submitted Reports</p>
                    <p id="areasConcernReportCountCard" class="mt-2 text-3xl font-bold text-slate-900">{{ $combinedReportCollection->count() }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                    <i class="fa-solid fa-file-lines text-xl"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Includes your fire incident submissions and your risk or hazard factors.</p>
        </div>

        <div class="rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-sky-50 to-cyan-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Risk / Hazard Markers</p>
                    <p id="areasConcernRiskMarkerCountCard" class="mt-2 text-3xl font-bold text-slate-900">{{ $riskMapCollection->count() }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Mapped risk and hazard records already approved for display or submitted by you.</p>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm mb-6 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Active Alerts</h2>
                    <p class="text-sm text-slate-500">This view only shows fire alerts for citizen awareness. Use search, status, and sorting to narrow the list.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input
                            id="areasConcernAlertSearch"
                            type="search"
                            placeholder="Search address, barangay, or status"
                            class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm text-slate-700 focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-100 sm:w-72"
                        >
                    </div>
                    <select id="areasConcernStatusFilter" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-100">
                        <option value="all">All statuses</option>
                        @foreach($initialAlertStatuses as $statusLabel)
                            <option value="{{ strtolower($statusLabel) }}">{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                    <select id="areasConcernSortFilter" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-100">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="closest">Closest first</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
        </div>
        <div id="areasConcernAlertsList" class="divide-y divide-slate-100"></div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Risk and Hazard Map</h2>
                        <p class="text-sm text-slate-500">Mapped non-fire concerns shared through PyroSense. Marker details include category, status, location context, and available image preview.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                        <i class="fa-solid fa-layer-group"></i>
                        <span id="areasConcernMapMarkerCount">{{ $riskMapCollection->count() }}</span> markers
                    </span>
                </div>
            </div>
            <div class="p-5">
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

        <aside class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-5">
                <h2 class="text-lg font-bold text-slate-900">My Reports Snapshot</h2>
                <p class="text-sm text-slate-500">Quick view of what you have submitted before opening the full history modal.</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="rounded-2xl bg-red-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-600">Fire Reports</p>
                        <p id="areasConcernFireReportCount" class="mt-2 text-2xl font-bold text-slate-900">0</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Risk / Hazard</p>
                        <p id="areasConcernRiskReportCount" class="mt-2 text-2xl font-bold text-slate-900">0</p>
                    </div>
                </div>
                <div id="areasConcernRecentReports" class="space-y-3"></div>
            </div>
        </aside>
    </div>

    <div id="citizenReportsHistoryModal" class="fixed inset-0 z-[4200] hidden items-center justify-center bg-slate-950/45 backdrop-blur-sm p-4">
        <div class="w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-5">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">My Reports History</h3>
                    <p class="text-sm text-slate-500">Fire Incident and Risk/Hazard Factor records submitted by your account.</p>
                </div>
                <button type="button" data-reports-history-close class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="border-b border-slate-100 px-6 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap gap-3">
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
            <div class="max-h-[70vh] overflow-auto px-6 py-5">
                <div class="overflow-x-auto rounded-3xl border border-slate-200">
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
            <button type="button" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-orange-200 hover:bg-orange-50/40" data-concern-report-open="${escapeHtml(String(record.incident_id || record.risk_area_id || ''))}" data-concern-report-kind="${escapeHtml(readReportKind(record))}">
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
                <article class="px-5 py-5 transition hover:bg-slate-50">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                                    <i class="fa-solid fa-fire"></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-semibold text-slate-900">Fire Alert</h3>
                                    <p class="text-sm text-slate-500">${escapeHtml(incident.address || 'No location description available.')}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span class="rounded-full ${statusMeta.badge} px-3 py-1 font-semibold">${escapeHtml(statusMeta.label)}</span>
                                <span><i class="fa-regular fa-clock mr-1"></i>${escapeHtml(formatDate(incident.created_at))}</span>
                                <span><i class="fa-solid fa-location-crosshairs mr-1"></i>${escapeHtml(distance)}</span>
                                <span><i class="fa-solid fa-map-pin mr-1"></i>${escapeHtml(barangay || 'Barangay pending')}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <button type="button" class="rounded-2xl bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-200" data-alert-details="${escapeHtml(String(incident.incident_id || ''))}">
                                <i class="fa-regular fa-eye mr-1"></i>Details
                            </button>
                            <button type="button" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200" data-alert-share="${escapeHtml(String(incident.incident_id || ''))}">
                                <i class="fa-solid fa-share-nodes mr-1"></i>Share
                            </button>
                            ${incident.lat && incident.lng ? `<button type="button" class="rounded-2xl bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-200" data-alert-map="${escapeHtml(String(incident.incident_id || ''))}"><i class="fa-regular fa-map mr-1"></i>View on Map</button>` : ''}
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

        tbody.querySelectorAll('[data-history-details]').forEach((button) => {
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

    reportHistoryModal?.addEventListener('click', (event) => {
        if (event.target === reportHistoryModal) closeReportsHistoryModal();
    });

    concernDetailsModal?.addEventListener('click', (event) => {
        if (event.target === concernDetailsModal) closeConcernDetailsModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (reportHistoryModal && !reportHistoryModal.classList.contains('hidden')) closeReportsHistoryModal();
        if (concernDetailsModal && !concernDetailsModal.classList.contains('hidden')) closeConcernDetailsModal();
    });

    updateAreasConcernSummary();
    renderAreasConcernAlerts();
    renderAreasConcernRecentReports();
    renderReportsHistoryTable();
    ensureConcernMap();
})();
</script>
