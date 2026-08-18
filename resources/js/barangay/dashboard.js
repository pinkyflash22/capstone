const DEFAULT_CENTER = [15.8222, 120.3297];
const DEFAULT_ZOOM = 13;
const USER_FOCUS_ZOOM = 16;
const BARANGAY_GROUP_ICON_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true" style="width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;"><circle cx="8" cy="9" r="2"></circle><circle cx="16" cy="9" r="2"></circle><circle cx="12" cy="7" r="2"></circle><path d="M4 18a4 4 0 0 1 8 0"></path><path d="M12 18a4 4 0 0 1 8 0"></path><path d="M8 16a4 4 0 0 1 8 0"></path></svg>';

function getCategoryVisual(category) {
    const normalized = String(category || '').trim().toLowerCase();

    switch (normalized) {
        case 'fire':
            return { color: '#dc2626', icon: 'fa-fire', image: '/fire.png', label: 'Fire' };
        case 'barangay':
            return { color: '#0f766e', iconHtml: BARANGAY_GROUP_ICON_SVG, image: '/hall.png', label: 'Barangay' };
        case 'station':
            return { color: '#2563eb', icon: 'fa-building', image: '/station.png', label: 'Station' };
        case 'risk':
            return { color: '#0284c7', icon: 'fa-triangle-exclamation', image: '/hazard.png', label: 'Risk Area' };
        case 'hazard':
            return { color: '#d97706', icon: 'fa-radiation', image: '/hazard.png', label: 'Hazard' };
        case 'hydrant':
            return { color: '#ef4444', icon: 'fa-fire-extinguisher', image: '/hydrant.png', label: 'Hydrant' };
        case 'school':
            return { color: '#eab308', icon: 'fa-school', label: 'School' };
        case 'bridge':
            return { color: '#475569', icon: 'fa-road-bridge', label: 'Bridge' };
        default:
            return { color: '#6b7280', icon: 'fa-map-marker-alt', label: 'Marker' };
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function normalizeIncidentStatus(marker = {}) {
    const status = String(marker.incident_status || marker.status || '').trim().toLowerCase();
    return ['controlled', 'completed', 'closed', 'inactive'].includes(status) ? status : 'active';
}

function buildIcon(category, emphasized = false, marker = {}) {
    const visual = getCategoryVisual(category);
    const normalizedCategory = String(category || '').trim().toLowerCase();
    const sizes = { fire: 42, station: 43, hydrant: 38, school: 43, barangay: 43, risk: 36, hazard: 36 };
    const size = (sizes[normalizedCategory] || 32) + (emphasized ? 4 : 0);
    const activeFire = normalizedCategory === 'fire' && normalizeIncidentStatus(marker) === 'active';
    const fallback = visual.iconHtml || `<i class="fa-solid ${visual.icon}"></i>`;
    const glyph = visual.image
        ? `<img src="${visual.image}" alt="" draggable="false" style="width:${size}px;height:${size}px;object-fit:contain;filter:drop-shadow(0 6px 10px rgba(15,23,42,.24));position:relative;z-index:2;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span style="display:none;align-items:center;justify-content:center;width:${size}px;height:${size}px;border-radius:50%;background:${visual.color};color:#fff;border:2px solid #fff;">${fallback}</span>`
        : `<span style="display:flex;align-items:center;justify-content:center;width:${size}px;height:${size}px;border-radius:50%;background:${visual.color};color:#fff;border:2px solid #fff;">${fallback}</span>`;

    return L.divIcon({
        className: 'barangay-dashboard-marker pyro-map-marker-icon',
        html: `
            <div style="width:${size}px;height:${size}px;display:flex;align-items:center;justify-content:center;position:relative;">
                ${activeFire ? '<span class="barangay-dashboard-marker__pulse"></span>' : ''}
                ${glyph}
            </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2],
    });
}

function normalizeMarkers(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

function getFilterKey(category) {
    const normalized = String(category || '').trim().toLowerCase();

    if (normalized === 'fire') {
        return 'incidents';
    }

    if (normalized === 'station') {
        return 'stations';
    }

    return 'hazards';
}

function createPopupContent(marker, highlighted = false) {
    const visual = getCategoryVisual(marker.category);

    return `
        <div style="min-width:220px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <span style="width:10px;height:10px;border-radius:999px;background:${visual.color};display:inline-block;"></span>
                <strong style="color:#0f172a;font-size:0.92rem;">${escapeHtml(marker.title || 'Untitled marker')}</strong>
            </div>
            <div style="color:#64748b;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:800;">
                ${visual.label}${highlighted ? ' · Latest Local Pin' : ''}
            </div>
            <p style="margin:8px 0 0;color:#475569;font-size:0.84rem;line-height:1.5;">
                ${escapeHtml(marker.description || marker.meta || 'No description provided.')}
            </p>
            <div style="margin-top:10px;color:#94a3b8;font-size:0.74rem;">
                ${escapeHtml(marker.lat)}, ${escapeHtml(marker.lng)}
            </div>
        </div>
    `;
}

async function loadDashboardMap() {
    const mapElement = document.getElementById('barangayDashboardMap');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const latestPinsElement = document.getElementById('barangayDashboardMapLatestPins');
    const incidentCountElement = document.getElementById('barangayMapIncidentCount');
    const lastUpdatedElement = document.getElementById('barangayMapLastUpdated');
    const filterButtons = Array.from(document.querySelectorAll('[data-map-filter]'));
    const latestPins = (() => {
        try {
            return JSON.parse(mapElement.dataset.miniMapPins || '[]');
        } catch (error) {
            console.error('Failed to parse latest barangay pins:', error);
            return [];
        }
    })();

    const map = L.map(mapElement, {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd',
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    }).addTo(map);

    const allMarkerLayer = L.layerGroup().addTo(map);
    const userLayer = L.layerGroup().addTo(map);
    const categoryLayers = {
        incidents: L.layerGroup(),
        stations: L.layerGroup(),
        hazards: L.layerGroup(),
    };
    const bounds = L.latLngBounds();
    let userMarker = null;
    let userAccuracyCircle = null;
    let hasCenteredOnUser = false;

    let markers = [];

    try {
        const markerFeedUrl = mapElement.dataset.markerFeedUrl || '/map/markers/feed';
        const response = await fetch(markerFeedUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Marker fetch failed with status ${response.status}`);
        }

        const payload = await response.json();
        markers = normalizeMarkers(payload);
    } catch (error) {
        console.error('Failed to load barangay dashboard markers:', error);
    }

    const validMarkers = markers.filter((marker) => {
        return marker && marker.lat !== null && marker.lng !== null && marker.lat !== '' && marker.lng !== '';
    });

    validMarkers.forEach((marker) => {
        const filterKey = getFilterKey(marker.category);
        const leafletMarker = L.marker([Number(marker.lat), Number(marker.lng)], {
            icon: buildIcon(marker.category, false, marker),
        });

        leafletMarker.bindPopup(createPopupContent(marker, false));
        leafletMarker.addTo(allMarkerLayer);
        if (categoryLayers[filterKey]) {
            leafletMarker.addTo(categoryLayers[filterKey]);
        }
        bounds.extend([Number(marker.lat), Number(marker.lng)]);
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds.pad(0.18));
    }

    if (latestPinsElement) {
        latestPinsElement.innerHTML = '';

        if (!latestPins.length) {
            latestPinsElement.innerHTML = '<span class="tag deferred">No latest pins yet</span>';
        } else {
            latestPins.forEach((pin) => {
                const visual = getCategoryVisual(pin.category);
                const chip = document.createElement('span');
                chip.className = 'tag low';
                chip.style.borderColor = `${visual.color}33`;
                chip.style.color = visual.color;
                chip.textContent = `${pin.title}`;
                latestPinsElement.appendChild(chip);
            });
        }
    }

    if (incidentCountElement) {
        incidentCountElement.textContent = String(validMarkers.filter((marker) => getFilterKey(marker.category) === 'incidents').length);
    }

    if (lastUpdatedElement) {
        lastUpdatedElement.textContent = new Date().toLocaleString([], {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    const overlays = {
        'All Markers': allMarkerLayer,
        'Your Location': userLayer,
    };

    L.control.layers(null, overlays, {
        collapsed: false,
        position: 'topright',
    }).addTo(map);

    function applyMapFilter(filter) {
        allMarkerLayer.remove();
        Object.values(categoryLayers).forEach((layer) => {
            layer.remove();
        });

        if (filter === 'all') {
            allMarkerLayer.addTo(map);
        } else if (categoryLayers[filter]) {
            categoryLayers[filter].addTo(map);
        } else {
            allMarkerLayer.addTo(map);
        }

        userLayer.addTo(map);
    }

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.mapFilter || 'all';

            filterButtons.forEach((candidate) => candidate.classList.remove('is-active'));
            button.classList.add('is-active');
            applyMapFilter(filter);
        });
    });

    applyMapFilter('all');

    function updateUserLocationOnMap(latitude, longitude, accuracy = 0) {
        const latLng = [Number(latitude), Number(longitude)];

        if (!userMarker) {
            userMarker = L.circleMarker(latLng, {
                radius: 9,
                color: '#1d4ed8',
                weight: 3,
                fillColor: '#38bdf8',
                fillOpacity: 0.95,
            }).addTo(userLayer);

            userMarker.bindPopup(`
                <div style="min-width:180px;">
                    <strong style="color:#0f172a;">Your Location</strong>
                    <p style="margin:8px 0 0;color:#475569;font-size:0.84rem;">Live barangay dashboard location marker.</p>
                </div>
            `);
        } else {
            userMarker.setLatLng(latLng);
        }

        if (!userAccuracyCircle) {
            userAccuracyCircle = L.circle(latLng, {
                radius: Math.max(accuracy || 0, 20),
                color: '#60a5fa',
                weight: 1.5,
                fillColor: '#93c5fd',
                fillOpacity: 0.18,
            }).addTo(userLayer);
        } else {
            userAccuracyCircle.setLatLng(latLng);
            userAccuracyCircle.setRadius(Math.max(accuracy || 0, 20));
        }

        if (!hasCenteredOnUser) {
            map.setView(latLng, USER_FOCUS_ZOOM, { animate: true });
            hasCenteredOnUser = true;
        }
    }

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            (position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy || 0;

                updateUserLocationOnMap(latitude, longitude, accuracy);
            },
            (error) => {
                console.error('Barangay dashboard geolocation error:', error);
            },
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 20000,
            }
        );
    }

    window.setTimeout(() => {
        map.invalidateSize();
    }, 120);
}

function initializeDashboardRecords() {
    const tableBody = document.getElementById('dashboardRecordsBody');
    const pagination = document.getElementById('dashboardRecordsPagination');
    const summary = document.getElementById('dashboardRecordsSummary');
    const modal = document.getElementById('dashboardRecordModal');
    const modalBody = document.getElementById('dashboardRecordModalBody');
    const initialData = document.getElementById('dashboardRecordsInitialData');

    if (!tableBody || !pagination || !modal || !modalBody) {
        return;
    }

    const records = new Map();
    let lastFocusedElement = null;

    try {
        JSON.parse(initialData?.textContent || '[]').forEach((record) => {
            records.set(Number(record.id), record);
        });
    } catch (error) {
        console.error('Unable to read initial barangay records:', error);
    }

    function statusClass(record) {
        return record.verified ? 'complied' : 'pending';
    }

    function renderRows(items) {
        records.clear();
        items.forEach((record) => records.set(Number(record.id), record));

        tableBody.innerHTML = items.map((record) => `
            <tr class="dashboard-record-row" data-record-id="${Number(record.id)}">
                <td><strong>${escapeHtml(record.date)}</strong><br><span class="text-slate-400">${escapeHtml(record.time)}</span></td>
                <td><strong>${escapeHtml(record.classification)}</strong><br><span class="text-slate-400">${escapeHtml(record.cause)}</span></td>
                <td>${escapeHtml(record.location)}</td>
                <td><span class="tag ${statusClass(record)}">${escapeHtml(record.status)}</span></td>
                <td>
                    <button type="button" class="dashboard-record-view" data-record-view="${Number(record.id)}" aria-label="View fire record">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(page) {
        const currentPage = Number(page.current_page || 1);
        const lastPage = Math.max(1, Number(page.last_page || 1));

        if (summary) {
            summary.textContent = page.total > 0
                ? `Showing ${page.from}–${page.to} of ${page.total}`
                : 'No fire records found';
        }

        pagination.querySelector('.dashboard-record-pagination__buttons').innerHTML = `
            <button type="button" class="dashboard-page-button" data-record-page="${Math.max(1, currentPage - 1)}" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous records">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="dashboard-page-button" disabled>${currentPage} / ${lastPage}</button>
            <button type="button" class="dashboard-page-button" data-record-page="${Math.min(lastPage, currentPage + 1)}" ${currentPage >= lastPage ? 'disabled' : ''} aria-label="Next records">
                <i class="bi bi-chevron-right"></i>
            </button>
        `;
    }

    function openRecord(recordId) {
        const record = records.get(Number(recordId));
        if (!record) return;

        lastFocusedElement = document.activeElement;
        modalBody.innerHTML = `
            <div class="dashboard-record-detail-grid">
                <div class="dashboard-record-detail"><span>Date</span><strong>${escapeHtml(record.date)} · ${escapeHtml(record.time)}</strong></div>
                <div class="dashboard-record-detail"><span>Status</span><strong>${escapeHtml(record.status)}</strong></div>
                <div class="dashboard-record-detail dashboard-record-detail--wide"><span>Location</span><strong>${escapeHtml(record.location)}</strong></div>
                <div class="dashboard-record-detail"><span>Classification</span><strong>${escapeHtml(record.classification)}</strong></div>
                <div class="dashboard-record-detail"><span>Cause</span><strong>${escapeHtml(record.cause)}</strong></div>
                <div class="dashboard-record-detail"><span>Occurrences</span><strong>${Number(record.incident_count || 1)}</strong></div>
                <div class="dashboard-record-detail"><span>Estimated damage</span><strong>${escapeHtml(record.estimated_damage)}</strong></div>
                <div class="dashboard-record-detail"><span>Injured</span><strong>${Number(record.injured_count || 0)}</strong></div>
                <div class="dashboard-record-detail"><span>Deaths</span><strong>${Number(record.death_count || 0)}</strong></div>
                <div class="dashboard-record-detail dashboard-record-detail--wide"><span>Source</span><strong>${escapeHtml(record.source)}</strong></div>
                <div class="dashboard-record-detail dashboard-record-detail--wide"><span>Notes</span><strong>${escapeHtml(record.notes)}</strong></div>
            </div>
        `;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-record-modal-close]')?.focus();
    }

    function closeRecord() {
        modal.hidden = true;
        document.body.style.overflow = '';
        lastFocusedElement?.focus?.();
    }

    async function loadPage(pageNumber) {
        const endpoint = pagination.dataset.endpoint;
        if (!endpoint) return;

        pagination.querySelectorAll('button').forEach((button) => { button.disabled = true; });

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('records_page', String(pageNumber));
            url.searchParams.set('per_page', '6');

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(`Record request failed with status ${response.status}`);

            const payload = await response.json();
            const page = payload.records;
            renderRows(Array.isArray(page?.data) ? page.data : []);
            renderPagination(page || {});
        } catch (error) {
            console.error('Unable to load barangay fire records:', error);
            renderPagination({ current_page: 1, last_page: 1, total: records.size, from: records.size ? 1 : null, to: records.size });
        }
    }

    tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-record-view], [data-record-id]');
        if (trigger) openRecord(trigger.dataset.recordView || trigger.dataset.recordId);
    });

    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-record-page]');
        if (button && !button.disabled) loadPage(Number(button.dataset.recordPage || 1));
    });

    modal.querySelectorAll('[data-record-modal-close]').forEach((button) => button.addEventListener('click', closeRecord));
    modal.addEventListener('click', (event) => { if (event.target === modal) closeRecord(); });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeRecord();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardMap();
    initializeDashboardRecords();
});
