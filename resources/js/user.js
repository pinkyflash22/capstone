// import './functions.js';
// import './map.js';

// import { getRoute, userLocation } from './functions.js';
import './routing-panel.js';
import './location-accuracy.js';
import { saveIncident, userLocation } from './functions.js';
import { setName } from './functions.js';
// Route and simulation variables for user map
let userRouteLayer = null;
let userRemainingRouteLayer = null;
let userActiveRoute = null;
let userSimulationPoints = [];
let userOriginalSimulationPoints = [];
let userTraveledPoints = [];
let userRemainingPoints = [];
let userCurrentPointIndex = 0;
let userIsSimulating = false;
let userAnimatedVehicle = null;
let userAnimationFrame = null;
let userAnimationStartTime = null;
let userAnimationDuration = null;
let userCurrentAnimationPoint = 0;
let userCurrentDestinationType = null;
let userRouteDestination = null;
// Multi-truck support for user map
let userActiveTrucks = new Map(); // Stores each truck separately by route_id
let userRouteRenderings = new Map();

// Route polling interval for user map
let userRoutePollingInterval = null;
// import {}
window.setThisName = setName;
window.saveIncident = saveIncident;
window.userLocation = userLocation;
window.thisOfficers = window.stationOfficer || {};
window.userCoords = window.userCoords || {
    currentLatitude: null,
    currentLongitude: null,
    ready: false
};
window.userLocations = window.userLocations || {};
window.updateUserLocation = function updateCitizenUserLocation(userId, latitude, longitude, metadata = {}) {
    const accuracy = Number(metadata.accuracy);
    const timestamp = Number(metadata.timestamp) || Date.now();
    const location = {
        lat: Number(latitude),
        lng: Number(longitude),
        accuracy: Number.isFinite(accuracy) ? accuracy : null,
        timestamp,
        lastUpdated: new Date(timestamp),
        ready: true,
    };

    window.userLocations[userId] = location;
    if (String(userId) === String(window.currentUserId)) {
        window.userCoords = {
            currentLatitude: location.lat,
            currentLongitude: location.lng,
            accuracy: location.accuracy,
            timestamp: location.timestamp,
            ready: true,
        };
    }

    return location;
};
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Flag to prevent multiple marker loads
let markersLoaded = false;

// Global layer groups for different marker types
let stationLayerGroup;
let incidentLayerGroup;
let hydrantLayerGroup;
let otherLayerGroup;
let boundaryLayerGroup;
const USER_INCIDENT_VISIBILITY_METERS = 3000;
let userOwnedRouteDiscoveryInterval = null;
let userTrackedIncidentId = null;
let citizenLocationRadius = null;
let markerChangePollInterval = null;
let latestCitizenMarkerSignature = null;
let userRoutePanelLoading = false;
let userRoutePanelMinimized = false;
const CITIZEN_ROUTE_DISCOVERY_INTERVAL_MS = 10000;
const CITIZEN_MARKER_WATCH_INTERVAL_MS = 30000;
const CITIZEN_ROUTE_POLL_INTERVAL_MS = 10000;
const CITIZEN_LIVE_FETCH_TIMEOUT_MS = 10000;
const citizenLiveRequestRegistry = new Map();
let citizenBaseLayers = {};
let citizenActiveBaseLayerKey = 'OSM Standard';
let citizenRouteDiscoveryFailureCount = 0;
let citizenRouteDiscoveryPausedUntil = 0;
let citizenMapPreviewPreviousFocus = null;
let latestCitizenBoundaryRenderedCount = 0;
let citizenBoundaryReloadTimer = null;
let citizenBoundaryLoadInFlight = false;
let citizenBoundaryNeedsLocationRetry = false;

function citizenPageIsVisible() {
    return typeof document === 'undefined' || document.visibilityState === 'visible';
}

function fetchCitizenLiveResponseOnce(key, url, options = {}) {
    if (citizenLiveRequestRegistry.has(key)) {
        return citizenLiveRequestRegistry.get(key).then((response) => response.clone());
    }

    const controller = new AbortController();
    const timeoutMs = options.timeoutMs ?? CITIZEN_LIVE_FETCH_TIMEOUT_MS;
    const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);
    const { timeoutMs: _timeoutMs, headers = {}, ...restOptions } = options;
    const requestPromise = fetch(url, {
        cache: 'no-store',
        ...restOptions,
        headers,
        signal: controller.signal
    }).finally(() => {
        window.clearTimeout(timeoutId);
        citizenLiveRequestRegistry.delete(key);
    });

    citizenLiveRequestRegistry.set(key, requestPromise);
    return requestPromise.then((response) => response.clone());
}
let citizenIncidentMarkerRefs = new Map();
let citizenLegendControl = null;
let citizenLayerControl = null;
let citizenMapSearchIndex = [];
let citizenMapSearchResults = [];
let citizenSearchDebounceTimer = null;
let citizenSearchActiveIndex = -1;
let citizenSearchLocationMarker = null;
const CITIZEN_MAP_SEARCH_GEOCODE_KEY = window.geoapifyKey || '83677a1086d44c4e82f903fd2fe2ea9b';
const BARANGAY_GROUP_ICON_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="9" r="2" fill="none"/><circle cx="16" cy="9" r="2" fill="none"/><circle cx="12" cy="7" r="2" fill="none"/><path d="M4 18a4 4 0 0 1 8 0M12 18a4 4 0 0 1 8 0M8 16a4 4 0 0 1 8 0"/></svg>';
const citizenMapLayerState = {
    stations: true,
    incidents: true,
    hydrants: true,
    others: true,
    boundaries: true
};

function ensureCitizenMarkerPanes() {
    if (!window.usermap) {
        return;
    }

    const paneConfig = [
        { name: 'citizenBoundaryPane', zIndex: 410 },
        { name: 'citizenBarangayPane', zIndex: 620 },
        { name: 'citizenStationPane', zIndex: 630 },
        { name: 'citizenHydrantPane', zIndex: 640 },
        { name: 'citizenIncidentPane', zIndex: 650 },
        { name: 'citizenUserPane', zIndex: 660 },
    ];

    paneConfig.forEach(({ name, zIndex }) => {
        const pane = window.usermap.getPane(name) || window.usermap.createPane(name);
        pane.style.zIndex = String(zIndex);
    });
}

function getCitizenMarkerPane(categoryKey, isIncidentMarker = false) {
    if (isIncidentMarker || categoryKey === 'fire') {
        return 'citizenIncidentPane';
    }

    if (categoryKey === 'hydrant') {
        return 'citizenHydrantPane';
    }

    if (categoryKey === 'station') {
        return 'citizenStationPane';
    }

    if (categoryKey === 'barangay') {
        return 'citizenBarangayPane';
    }

    return 'markerPane';
}

function normalizeCitizenSearchText(value) {
    return String(value ?? '')
        .toLowerCase()
        .replace(/[^\w\s-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function getCitizenSearchCategoryLabel(categoryKey, incident = null) {
    if (incident) {
        return 'Incident / Hazard';
    }

    switch (categoryKey) {
        case 'hydrant':
            return 'Hydrant / Resource';
        case 'station':
            return 'Fire Station / Resource';
        case 'fire':
            return 'Hazard Marker';
        case 'barangay':
            return 'Barangay';
        case 'school':
            return 'School';
        case 'bridge':
            return 'Bridge';
        default:
            return 'Map Marker';
    }
}

function getCitizenSearchIcon(categoryKey, incident = null) {
    if (incident) {
        return 'fa-fire';
    }

    switch (categoryKey) {
        case 'hydrant':
            return 'fa-fire-extinguisher';
        case 'station':
            return 'fa-building';
        case 'barangay':
            return 'fa-people-group';
        case 'school':
            return 'fa-school';
        case 'bridge':
            return 'fa-road-bridge';
        default:
            return 'fa-location-dot';
    }
}

function buildCitizenMapSearchIndex(markers = [], incidents = []) {
    const incidentByMarkerId = new Map();
    const nextIndex = [];

    incidents.forEach((incident) => {
        if (incident?.marker_id != null) {
            incidentByMarkerId.set(String(incident.marker_id), incident);
        }
    });

    markers.forEach((marker) => {
        if (!marker?.lat || !marker?.lng) {
            return;
        }

        const categoryKey = getCitizenMarkerCategoryKey(marker);
        const incident = incidentByMarkerId.get(String(marker.marker_id)) || null;
        const incidentType = incident?.incident_type?.type || incident?.incident_type_name || incident?.incident_type || '';
        const incidentStatus = incident?.incident_status || incident?.status || '';
        const categoryLabel = getCitizenSearchCategoryLabel(categoryKey, incident);
        const locationLabel = getCitizenUsefulValue(marker?.address, marker?.location, marker?.barangay, marker?.barangay_name, incident?.address);
        const safeSearchTitle = getCitizenUsefulValue(marker?.title, marker?.name, incidentType)
            || (incident
                ? (locationLabel ? `Fire incident - ${locationLabel}` : 'Fire incident')
                : (locationLabel ? `${categoryLabel} - ${locationLabel}` : categoryLabel));
        const aliasWords = [
            categoryKey,
            categoryLabel,
            incident ? 'hazard' : '',
            incident ? 'incident' : '',
            incident ? 'fire' : '',
            categoryKey === 'hydrant' ? 'resource' : '',
            categoryKey === 'station' ? 'resource fire station responder' : '',
            marker?.title,
            marker?.description,
            incidentType,
            incidentStatus,
            incident?.address
        ].filter(Boolean);

        nextIndex.push({
            id: `${incident ? 'incident' : 'marker'}-${marker.marker_id}`,
            resultType: incident ? 'incident' : 'marker',
            marker_id: marker.marker_id,
            incident_id: incident?.incident_id ?? null,
            lat: Number(marker.lat),
            lng: Number(marker.lng),
            title: safeSearchTitle,
            description: marker.description || incident?.address || 'No description provided',
            meta: categoryLabel,
            icon: getCitizenSearchIcon(categoryKey, incident),
            searchText: normalizeCitizenSearchText(aliasWords.join(' '))
        });
    });

    citizenMapSearchIndex = nextIndex;
}

function scoreCitizenSearchResult(entry, query) {
    const normalizedQuery = normalizeCitizenSearchText(query);
    if (!normalizedQuery) {
        return 0;
    }

    let score = 0;
    if (normalizeCitizenSearchText(entry.title).includes(normalizedQuery)) score += 12;
    if (normalizeCitizenSearchText(entry.meta).includes(normalizedQuery)) score += 7;
    if (normalizeCitizenSearchText(entry.description).includes(normalizedQuery)) score += 4;
    if (entry.searchText.includes(normalizedQuery)) score += 3;

    normalizedQuery.split(' ').filter(Boolean).forEach((token) => {
        if (normalizeCitizenSearchText(entry.title).includes(token)) score += 3;
        if (entry.searchText.includes(token)) score += 1;
    });

    return score;
}

function searchCitizenMapLocal(query) {
    return citizenMapSearchIndex
        .map((entry) => ({ ...entry, score: scoreCitizenSearchResult(entry, query) }))
        .filter((entry) => entry.score > 0)
        .sort((a, b) => b.score - a.score)
        .slice(0, 6);
}

async function searchCitizenMapGeocode(query) {
    const normalizedQuery = normalizeCitizenSearchText(query);
    if (!normalizedQuery || normalizedQuery.length < 3) {
        return [];
    }

    if (window.pyroPwa?.isOffline?.()) {
        return [];
    }

    try {
        const params = new URLSearchParams({
            text: query,
            limit: '3',
            format: 'json',
            apiKey: CITIZEN_MAP_SEARCH_GEOCODE_KEY
        });

        const response = await fetch(`https://api.geoapify.com/v1/geocode/search?${params.toString()}`);
        if (!response.ok) {
            throw new Error(`Geocode search failed with ${response.status}`);
        }

        const payload = await response.json();
        return Array.isArray(payload?.results)
            ? payload.results.slice(0, 3).map((place, index) => ({
                id: `geocode-${index}-${place.place_id || place.lat}-${place.lon}`,
                resultType: 'location',
                marker_id: null,
                incident_id: null,
                lat: Number(place.lat),
                lng: Number(place.lon),
                title: place.formatted || place.address_line1 || query,
                description: [place.address_line1, place.address_line2].filter(Boolean).join(' • ') || 'Location result',
                meta: 'Location Search',
                icon: 'fa-location-dot'
            }))
            : [];
    } catch (error) {
        console.error('Citizen map geocode search failed:', error);
        return [];
    }
}

function clearCitizenSearchLocationMarker() {
    if (citizenSearchLocationMarker && window.usermap) {
        window.usermap.removeLayer(citizenSearchLocationMarker);
        citizenSearchLocationMarker = null;
    }
}

function focusCitizenMapSearchResult(result) {
    if (!result || !window.usermap) {
        return;
    }

    if (result.resultType === 'location') {
        clearCitizenSearchLocationMarker();
        citizenSearchLocationMarker = L.marker([result.lat, result.lng], {
            icon: getDefaultIcon()
        })
            .addTo(window.usermap)
            .bindPopup(`<strong>${escapeCitizenModalHtml(result.title)}</strong><br>${escapeCitizenModalHtml(result.description || 'Location result')}`);

        window.usermap.setView([result.lat, result.lng], Math.max(window.usermap.getZoom() || 15, 16), { animate: true });
        setTimeout(() => citizenSearchLocationMarker?.openPopup(), 220);
        return;
    }

    if (result.incident_id || result.marker_id) {
        window.focusCitizenIncidentOnMap?.({
            incident_id: result.incident_id,
            marker_id: result.marker_id,
            lat: result.lat,
            lng: result.lng
        });
    }
}

function renderCitizenMapSearchResults(results = []) {
    const resultsHost = document.getElementById('citizenMapSearchResults');
    const clearButton = document.getElementById('citizenMapSearchClear');

    if (!resultsHost) {
        return;
    }

    citizenMapSearchResults = results;
    citizenSearchActiveIndex = -1;
    clearButton?.classList.toggle('hidden', results.length === 0 && !document.getElementById('citizenMapSearchInput')?.value);

    if (!results.length) {
        resultsHost.innerHTML = '<div class="citizen-map-search__empty">No matching incidents, hazards, resources, or locations found.</div>';
        resultsHost.classList.remove('hidden');
        return;
    }

    resultsHost.innerHTML = `
        <div class="citizen-map-search__results-list">
            ${results.map((result, index) => `
                <button type="button" class="citizen-map-search__result" data-search-result-index="${index}">
                    <span class="citizen-map-search__result-icon"><i class="fas ${result.icon}"></i></span>
                    <span class="citizen-map-search__result-copy">
                        <span class="citizen-map-search__result-meta">${escapeCitizenModalHtml(result.meta)}</span>
                        <span class="citizen-map-search__result-title">${escapeCitizenModalHtml(result.title)}</span>
                        <span class="citizen-map-search__result-desc">${escapeCitizenModalHtml(result.description || '')}</span>
                    </span>
                </button>
            `).join('')}
        </div>
    `;
    resultsHost.classList.remove('hidden');

    resultsHost.querySelectorAll('[data-search-result-index]').forEach((button) => {
        button.addEventListener('click', () => {
            const resultIndex = Number(button.dataset.searchResultIndex);
            const targetResult = citizenMapSearchResults[resultIndex];
            if (!targetResult) {
                return;
            }

            focusCitizenMapSearchResult(targetResult);
            resultsHost.classList.add('hidden');
        });
    });
}

function hideCitizenMapSearchResults() {
    document.getElementById('citizenMapSearchResults')?.classList.add('hidden');
    citizenSearchActiveIndex = -1;
}

async function runCitizenMapSearch(query) {
    const clearButton = document.getElementById('citizenMapSearchClear');
    const trimmedQuery = String(query || '').trim();

    clearButton?.classList.toggle('hidden', trimmedQuery.length === 0);

    if (!trimmedQuery) {
        hideCitizenMapSearchResults();
        return;
    }

    const localResults = searchCitizenMapLocal(trimmedQuery);
    const locationResults = await searchCitizenMapGeocode(trimmedQuery);
    const mergedResults = [...localResults, ...locationResults].slice(0, 8);

    renderCitizenMapSearchResults(mergedResults);
}

function initializeCitizenMapSearch() {
    const searchInput = document.getElementById('citizenMapSearchInput');
    const clearButton = document.getElementById('citizenMapSearchClear');
    const resultsHost = document.getElementById('citizenMapSearchResults');

    if (!searchInput || !resultsHost || searchInput.dataset.searchBound === 'true') {
        return;
    }

    searchInput.dataset.searchBound = 'true';

    searchInput.addEventListener('input', () => {
        window.clearTimeout(citizenSearchDebounceTimer);
        citizenSearchDebounceTimer = window.setTimeout(() => {
            runCitizenMapSearch(searchInput.value);
        }, 220);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideCitizenMapSearchResults();
            return;
        }

        if (!citizenMapSearchResults.length) {
            return;
        }

        const optionButtons = Array.from(resultsHost.querySelectorAll('[data-search-result-index]'));

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            citizenSearchActiveIndex = Math.min(citizenSearchActiveIndex + 1, optionButtons.length - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            citizenSearchActiveIndex = Math.max(citizenSearchActiveIndex - 1, 0);
        } else if (event.key === 'Enter' && citizenSearchActiveIndex >= 0) {
            event.preventDefault();
            optionButtons[citizenSearchActiveIndex]?.click();
            return;
        } else {
            return;
        }

        optionButtons.forEach((button, index) => {
            button.classList.toggle('is-active', index === citizenSearchActiveIndex);
        });
    });

    clearButton?.addEventListener('click', () => {
        searchInput.value = '';
        clearButton.classList.add('hidden');
        clearCitizenSearchLocationMarker();
        hideCitizenMapSearchResults();
        searchInput.focus();
    });

    document.addEventListener('click', (event) => {
        const searchRoot = document.getElementById('citizenMapSearch');
        if (searchRoot && !searchRoot.contains(event.target)) {
            hideCitizenMapSearchResults();
        }
    });
}

window.refreshCitizenMapSearchIndex = function() {
    buildCitizenMapSearchIndex(window.mapData?.usermark || [], window.incidentsData || []);

    const activeQuery = document.getElementById('citizenMapSearchInput')?.value?.trim();
    if (activeQuery) {
        runCitizenMapSearch(activeQuery);
    }
};

function isCitizenMobileViewport() {
    return window.innerWidth <= 768;
}

function normalizeCitizenBaseLayerLabel(label = '') {
    const normalized = String(label || '').trim().toLowerCase();
    if (!normalized) {
        return 'OSM Standard';
    }

    if (['satellite'].includes(normalized)) {
        return 'Satellite';
    }

    if (['street map', 'osm', 'osm standard'].includes(normalized)) {
        return 'OSM Standard';
    }

    if (normalized === 'osm hot') {
        return 'OSM HOT';
    }

    return 'OSM Standard';
}

function getCitizenActiveBaseLayer() {
    if (window.usermap) {
        const matchedLayer = Object.entries(citizenBaseLayers).find(([, layer]) => layer && window.usermap.hasLayer(layer));
        if (matchedLayer) {
            citizenActiveBaseLayerKey = matchedLayer[0];
        }
    }

    return citizenActiveBaseLayerKey;
}

function setCitizenBaseLayer(label = '') {
    if (!window.usermap) {
        return false;
    }

    const targetKey = normalizeCitizenBaseLayerLabel(label);
    const targetLayer = citizenBaseLayers[targetKey];

    if (!targetLayer) {
        return false;
    }

    Object.entries(citizenBaseLayers).forEach(([layerKey, layer]) => {
        if (!layer) {
            return;
        }

        if (layerKey === targetKey) {
            if (!window.usermap.hasLayer(layer)) {
                layer.addTo(window.usermap);
            }
        } else if (window.usermap.hasLayer(layer)) {
            window.usermap.removeLayer(layer);
        }
    });

    citizenActiveBaseLayerKey = targetKey;
    return true;
}

window.getCitizenActiveBaseLayer = getCitizenActiveBaseLayer;
window.setCitizenBaseLayer = setCitizenBaseLayer;

function getCitizenLayerGroupByKey(layerKey) {
    switch (layerKey) {
        case 'stations':
            return stationLayerGroup;
        case 'incidents':
            return incidentLayerGroup;
        case 'hydrants':
            return hydrantLayerGroup;
        case 'others':
            return otherLayerGroup;
        default:
            return null;
    }
}

function applyCitizenMapLayerVisibility(layerKey, isVisible) {
    const layerGroup = getCitizenLayerGroupByKey(layerKey);
    if (!window.usermap || !layerGroup) {
        return;
    }

    citizenMapLayerState[layerKey] = Boolean(isVisible);

    if (citizenMapLayerState[layerKey]) {
        if (!window.usermap.hasLayer(layerGroup)) {
            layerGroup.addTo(window.usermap);
        }
    } else if (window.usermap.hasLayer(layerGroup)) {
        window.usermap.removeLayer(layerGroup);
    }
}

function syncCitizenMapLayerControls() {
    Object.entries(citizenMapLayerState).forEach(([layerKey, fallbackVisible]) => {
        const layerGroup = getCitizenLayerGroupByKey(layerKey);
        const isVisible = layerGroup && window.usermap
            ? window.usermap.hasLayer(layerGroup)
            : fallbackVisible;

        citizenMapLayerState[layerKey] = isVisible;
        document.querySelectorAll(`[data-citizen-map-layer-toggle="${layerKey}"]`).forEach((input) => {
            input.checked = isVisible;
        });
    });
}

function bindCitizenMapLayerToggleInputs() {
    document.querySelectorAll('[data-citizen-map-layer-toggle]').forEach((input) => {
        if (input.dataset.boundCitizenLayerToggle === 'true') {
            return;
        }

        input.addEventListener('change', () => {
            applyCitizenMapLayerVisibility(input.dataset.citizenMapLayerToggle, input.checked);
        });
        input.dataset.boundCitizenLayerToggle = 'true';
    });
}

function bindCitizenMobileLayerControlTrigger() {
    if (!citizenLayerControl) {
        return;
    }

    const controlContainer = citizenLayerControl.getContainer?.();
    const layersToggle = controlContainer?.querySelector('.leaflet-control-layers-toggle');
    if (!controlContainer || controlContainer.dataset.mobileTriggerBound === 'true') {
        return;
    }

    const handleToggle = (event) => {
        if (!isCitizenMobileViewport()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        window.openCitizenMapLayersPanel?.();
    };

    (layersToggle || controlContainer).addEventListener('click', handleToggle);
    (layersToggle || controlContainer).addEventListener('touchstart', handleToggle, { passive: false });
    controlContainer.dataset.mobileTriggerBound = 'true';
}

function enhanceCitizenLeafletLayerControl() {
    if (!citizenLayerControl) {
        return;
    }

    const control = citizenLayerControl.getContainer?.();
    const base = control?.querySelector('.leaflet-control-layers-base');
    const overlays = control?.querySelector('.leaflet-control-layers-overlays');

    if (!control || !base || !overlays || control.dataset.citizenLabelsEnhanced === 'true') {
        return;
    }

    const normalizeLayerLabel = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const displayLabelMap = {
        'OSM Standard': 'OSM',
        'OSM HOT': 'OSM HOT',
        Satellite: 'Satellite',
        'Fire Stations': 'Fire stations',
        Incidents: 'Incidents',
        'Fire Hydrants': 'Hydrants',
        'Other Markers': 'Other locations',
        'Water Reference': 'Water'
    };
    const iconMap = {
        'Fire stations': 'fa-building-shield',
        Incidents: 'fa-fire-flame-curved',
        Hydrants: 'fa-droplet',
        'Other locations': 'fa-location-dot',
        Water: 'fa-water'
    };

    base.querySelectorAll('label').forEach((label) => {
        const input = label.querySelector('input');
        const span = label.querySelector('span');
        if (!input || !span) return;

        const rawLabel = normalizeLayerLabel(span.textContent);
        const displayLabel = displayLabelMap[rawLabel] || rawLabel;
        label.title = rawLabel;
        label.setAttribute('aria-label', rawLabel);
        span.textContent = '';
        span.appendChild(input);

        const text = document.createElement('span');
        text.textContent = displayLabel;
        text.title = rawLabel;
        span.appendChild(text);
    });

    overlays.querySelectorAll('label').forEach((label) => {
        const input = label.querySelector('input');
        const span = label.querySelector('span');
        if (!input || !span) return;

        const rawLabel = normalizeLayerLabel(span.textContent);
        const displayLabel = displayLabelMap[rawLabel] || rawLabel;
        label.title = rawLabel;
        label.setAttribute('aria-label', rawLabel);
        span.textContent = '';

        const icon = document.createElement('i');
        icon.className = `fa-solid ${iconMap[displayLabel] || 'fa-location-dot'} citizen-layer-row-icon`;
        icon.setAttribute('aria-hidden', 'true');

        const text = document.createElement('span');
        text.textContent = displayLabel;
        text.title = rawLabel;

        span.appendChild(icon);
        span.appendChild(text);
        span.appendChild(input);
    });

    control.dataset.citizenLabelsEnhanced = 'true';
}

window.syncCitizenMapLayerControls = syncCitizenMapLayerControls;

function ensureCitizenMarkerStyles() {
    if (document.getElementById('citizen-marker-style-overrides')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'citizen-marker-style-overrides';
    style.textContent = `
        .leaflet-marker-icon.citizen-fa-icon,
        .leaflet-marker-icon.custom-fa-icon,
        .leaflet-marker-icon.shared-map-div-icon,
        .leaflet-div-icon.shared-map-div-icon {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .citizen-fa-icon,
        .custom-fa-icon {
            transition: all 0.3s ease;
        }

        .citizen-fa-icon:hover,
        .custom-fa-icon:hover {
            transform: scale(1.1);
            z-index: 1000 !important;
        }

        .citizen-fa-icon i,
        .custom-fa-icon i,
        .leaflet-div-icon i,
        .leaflet-marker-icon i {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
            font-weight: 900 !important;
            font-style: normal !important;
            line-height: 1;
            display: inline-block;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }

        .shared-map-marker,
        .shared-map-marker__icon {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            grid-template-rows: minmax(0, 1fr);
            min-width: 0;
            min-height: 0;
            place-items: center;
            background: transparent;
            border: 0;
            box-shadow: none;
            overflow: visible;
        }

        .shared-map-marker__icon img {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            min-width: 0;
            min-height: 0;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.24));
        }
    `;

    document.head.appendChild(style);
}

function normalizeUserRouteId(routeId) {
    return String(routeId);
}

function renderCitizenLocationMarker(lat, lng, radiusMeters = 180) {
    if (!window.usermap) {
        console.warn('[CitizenLocation] renderCitizenLocationMarker skipped: usermap is not ready', {
            lat,
            lng,
            radiusMeters,
        });
        return;
    }

    console.log('[CitizenLocation] renderCitizenLocationMarker called', {
        lat,
        lng,
        radiusMeters,
        hasExistingMarker: Boolean(window.userMarker),
        mapContainerId: window.usermap?._container?.id || null,
    });

    const citizenIcon = L.divIcon({
        className: 'citizen-location-marker',
        html: `
            <div style="position: relative; width: 52px; height: 52px;">
                <div style="position:absolute; inset:0; border-radius:9999px; background:rgba(249,115,22,0.18); animation:pulse 2s infinite;"></div>
                <div style="position:absolute; inset:7px; border-radius:9999px; background:linear-gradient(135deg,#f97316 0%, #dc2626 100%); border:3px solid rgba(255,255,255,0.95); box-shadow:0 12px 28px rgba(124,45,18,0.35); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-user-shield" style="color:#fff; font-size:16px;"></i>
                </div>
                <div style="position:absolute; left:50%; bottom:-4px; width:14px; height:14px; transform:translateX(-50%) rotate(45deg); background:#ea580c; border-right:2px solid rgba(255,255,255,0.95); border-bottom:2px solid rgba(255,255,255,0.95);"></div>
            </div>
        `,
        iconSize: [52, 60],
        iconAnchor: [26, 52],
        popupAnchor: [0, -44]
    });

    if (window.userMarker) {
        window.userMarker.setLatLng([lat, lng]);
        window.userMarker.setIcon(citizenIcon);
        console.log('[CitizenLocation] existing user marker moved', {
            lat,
            lng,
            isOnMap: window.usermap.hasLayer(window.userMarker),
        });
    } else {
        window.userMarker = L.marker([lat, lng], { icon: citizenIcon })
            .addTo(window.usermap)
            .bindPopup('<strong>You are here</strong><br>Your live location on the citizen map');
        console.log('[CitizenLocation] new user marker added to map', {
            lat,
            lng,
            isOnMap: window.usermap.hasLayer(window.userMarker),
        });
    }

    if (citizenLocationRadius) {
        citizenLocationRadius.setLatLng([lat, lng]);
        citizenLocationRadius.setRadius(radiusMeters);
    } else {
        citizenLocationRadius = L.circle([lat, lng], {
            color: '#f97316',
            fillColor: '#fb923c',
            fillOpacity: 0.12,
            radius: radiusMeters,
            weight: 2
        }).addTo(window.usermap);
    }

    updateCitizenMapLegend();
    console.log('[CitizenLocation] location marker render complete', {
        markerLatLng: window.userMarker?.getLatLng?.() || null,
        radiusLatLng: citizenLocationRadius?.getLatLng?.() || null,
    });
}

function normalizeCitizenBoundaryGeojson(rawGeojson) {
    if (!rawGeojson) {
        return null;
    }

    if (typeof rawGeojson === 'string') {
        try {
            return JSON.parse(rawGeojson);
        } catch (error) {
            console.warn('[CitizenBoundary] Unable to parse boundary geojson string', error);
            return null;
        }
    }

    return rawGeojson;
}

function getCitizenBoundaryStyle(shape = {}, index = 0) {
    const boundaryType = String(shape.boundary_type || '').toLowerCase();
    const palette = boundaryType === 'barangay'
        ? { color: '#2563eb', fillColor: '#60a5fa' }
        : boundaryType === 'station'
            ? { color: '#16a34a', fillColor: '#86efac' }
            : { color: '#0f766e', fillColor: '#5eead4' };

    return {
        color: palette.color,
        fillColor: palette.fillColor,
        fillOpacity: 0.12,
        opacity: 0.82,
        weight: 2,
        dashArray: boundaryType === 'barangay' ? null : '6 5',
        pane: 'citizenBoundaryPane'
    };
}

function getCitizenCurrentCoords() {
    const lat = Number(window.userCoords?.currentLatitude);
    const lng = Number(window.userCoords?.currentLongitude);
    const hasCoords = typeof window.citizenHasUsableCoords === 'function'
        ? window.citizenHasUsableCoords()
        : Boolean(window.userCoords?.ready && Number.isFinite(lat) && Number.isFinite(lng));

    return hasCoords ? { lat, lng } : null;
}

async function fetchCitizenBoundaryFromCurrentLocation() {
    const coords = getCitizenCurrentCoords();
    if (!coords) {
        citizenBoundaryNeedsLocationRetry = true;
        return [];
    }

    citizenBoundaryNeedsLocationRetry = false;
    const response = await fetchCitizenLiveResponseOnce('citizen-boundary-locate', '/api/boundaries/locate-point', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(coords),
        timeoutMs: 10000
    });

    if (!response.ok) {
        throw new Error(`Boundary location lookup failed with status ${response.status}`);
    }

    const payload = await response.json();
    return payload?.matched && payload?.boundary ? [payload.boundary] : [];
}

async function loadCitizenBoundaries() {
    if (!window.usermap) {
        console.warn('[CitizenBoundary] Map not ready, skipping boundary load');
        return;
    }

    if (citizenBoundaryLoadInFlight) {
        return;
    }

    citizenBoundaryLoadInFlight = true;

    if (!boundaryLayerGroup) {
        boundaryLayerGroup = L.layerGroup().addTo(window.usermap);
    }

    const renderShapes = (shapes = [], source = 'live') => {
        boundaryLayerGroup.clearLayers();

        let renderedCount = 0;
        shapes.forEach((shape, index) => {
            const geojson = normalizeCitizenBoundaryGeojson(shape.geojson);
            if (!geojson) {
                return;
            }

            try {
                const layer = L.geoJSON(geojson, {
                    style: getCitizenBoundaryStyle(shape, index),
                    interactive: true,
                    pane: 'citizenBoundaryPane'
                });

                layer.bindTooltip(shape.name || shape.barangay_name || 'Boundary', {
                    permanent: false,
                    direction: 'top',
                    sticky: true,
                    opacity: 0.9
                });

                layer.addTo(boundaryLayerGroup);
                renderedCount += 1;
            } catch (error) {
                console.warn('[CitizenBoundary] Unable to render boundary shape', { shape, error });
            }
        });

        latestCitizenBoundaryRenderedCount = renderedCount;

        console.log('[CitizenBoundary] boundaries rendered', {
            source,
            total: shapes.length,
            rendered: renderedCount,
        });
    };

    const cachedShapes = window.pyroPwa?.loadDataset?.('citizen-map-boundaries', []);
    if (Array.isArray(cachedShapes) && cachedShapes.length) {
        renderShapes(cachedShapes, 'cache');
    }

    try {
        console.log('[CitizenBoundary] fetching /api/shapes');
        const response = await fetchCitizenLiveResponseOnce('citizen-boundaries', '/api/shapes', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeoutMs: 10000
        });

        if (!response.ok) {
            throw new Error(`Boundary fetch failed with status ${response.status}`);
        }

        const payload = await response.json();
        let shapes = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);

        if (shapes.length === 0) {
            shapes = await fetchCitizenBoundaryFromCurrentLocation();
        }

        window.pyroPwa?.storeDataset?.('citizen-map-boundaries', shapes);
        renderShapes(shapes, shapes.length === 1 ? 'current-location' : 'live');
    } catch (error) {
        console.error('[CitizenBoundary] Failed to load boundaries', error);

        if (!Array.isArray(cachedShapes) || !cachedShapes.length) {
            try {
                const fallbackShapes = await fetchCitizenBoundaryFromCurrentLocation();
                if (fallbackShapes.length) {
                    renderShapes(fallbackShapes, 'current-location-fallback');
                    return;
                }
            } catch (fallbackError) {
                console.error('[CitizenBoundary] Current-location boundary fallback failed', fallbackError);
            }

            boundaryLayerGroup.clearLayers();
            latestCitizenBoundaryRenderedCount = 0;
        }
    } finally {
        citizenBoundaryLoadInFlight = false;
    }
}

window.loadCitizenBoundaries = loadCitizenBoundaries;

document.addEventListener('locationReady', () => {
    if ((latestCitizenBoundaryRenderedCount > 0 && !citizenBoundaryNeedsLocationRetry) || citizenBoundaryReloadTimer) {
        return;
    }

    citizenBoundaryReloadTimer = window.setTimeout(() => {
        citizenBoundaryReloadTimer = null;
        window.loadCitizenBoundaries?.();
    }, 250);
});

function ensureCitizenLegendControl() {
    if (document.querySelector('#section-overview .citizen-map-legend-grid')) {
        return null;
    }

    if (!window.usermap || citizenLegendControl) {
        return citizenLegendControl;
    }

    citizenLegendControl = L.control({ position: 'bottomright' });
    citizenLegendControl.onAdd = function () {
        const div = L.DomUtil.create('div', 'citizen-map-floating-legend');
        div.innerHTML = '';
        L.DomEvent.disableClickPropagation(div);
        return div;
    };

    citizenLegendControl.addTo(window.usermap);
    return citizenLegendControl;
}

function buildLegendSwatch({ color, icon = null, iconHtml = null, imagePath = null, shape = 'circle', pulse = false, size = 18, borderWidth = 2, shadow = '0 3px 10px rgba(15,23,42,0.18)', iconSize = 9 }) {
    const radius = shape === 'square' ? '4px 4px 8px 8px' : '9999px';
    const animation = pulse && !imagePath ? 'animation:pulse 1.5s infinite;' : '';
    const safeImagePath = imagePath ? String(imagePath).replace(/"/g, '&quot;') : '';
    const glyph = iconHtml
        ? `<span style="display:inline-flex;width:${iconSize + 6}px;height:${iconSize + 6}px;align-items:center;justify-content:center;">${iconHtml}</span>`
        : safeImagePath
            ? `<img src="${safeImagePath}" alt="" style="width:${size + 6}px;height:${size + 6}px;object-fit:contain;display:block;">`
            : (icon ? `<i class="fas ${icon}" data-inline-fa-processed="1"></i>` : '');

    return `
        <span style="
            width: ${size}px;
            height: ${size}px;
            border-radius: ${radius};
            background: transparent;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: ${safeImagePath ? 0 : borderWidth}px solid #fff;
            box-shadow: none;
            filter: none;
            font-size: ${iconSize}px;
            flex-shrink: 0;
            ${animation}
        ">
            ${glyph}
        </span>
    `;
}

function updateCitizenMapLegend(legendEntries = []) {
    if (!window.usermap) {
        return;
    }

    if (document.querySelector('#section-overview .citizen-map-legend-grid')) {
        return;
    }

    ensureCitizenLegendControl();
    const legendElement = document.querySelector('.citizen-map-floating-legend');
    if (!legendElement) {
        return;
    }

    const entries = [];
    const seenKeys = new Set();

    if (window.userMarker) {
        entries.push({
            key: 'user-location',
            label: 'Your location',
            swatch: buildLegendSwatch({ color: '#f97316', icon: 'fa-user-shield', pulse: true })
        });
    }

    legendEntries.forEach((entry) => {
        if (!entry?.key || seenKeys.has(entry.key)) {
            return;
        }

        seenKeys.add(entry.key);
        entries.push(entry);
    });

    if (!entries.length) {
        legendElement.innerHTML = '';
        legendElement.style.display = 'none';
        return;
    }

    legendElement.style.display = 'block';
    legendElement.innerHTML = `
        <div style="
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(226,232,240,0.95);
            border-radius: 16px;
            box-shadow: none;
            filter: none;
            padding: 12px 14px;
            min-width: 180px;
            backdrop-filter: blur(12px);
        ">
            <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                Map Legend
            </div>
            <div style="display: flex; flex-direction: column; gap: 7px;">
                ${entries.map((entry) => `
                    <div style="display:flex; align-items:center; gap:8px; font-size:12px; color:#334155; line-height:1.2;">
                        ${entry.swatch}
                        <span>${entry.label}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function getUserTruckColor(routeId) {
    const normalizedRouteId = normalizeUserRouteId(routeId);
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(userActiveTrucks.keys()).indexOf(normalizedRouteId) % truckColors.length;
    return truckColors[colorIndex];
}

function userRegisterRouteLayer(routeId, layer) {
    const normalizedRouteId = normalizeUserRouteId(routeId);
    if (!userRouteRenderings.has(normalizedRouteId)) {
        userRouteRenderings.set(normalizedRouteId, []);
    }

    userRouteRenderings.get(normalizedRouteId).push(layer);
    return layer;
}

function userRemoveRegisteredRouteLayer(routeId, layer) {
    const normalizedRouteId = normalizeUserRouteId(routeId);
    if (!window.usermap || !layer) {
        return;
    }

    try {
        window.usermap.removeLayer(layer);
    } catch (error) {
        console.warn('Failed to remove user route layer:', error);
    }

    if (userRouteRenderings.has(normalizedRouteId)) {
        userRouteRenderings.set(
            normalizedRouteId,
            userRouteRenderings.get(normalizedRouteId).filter((existingLayer) => existingLayer !== layer)
        );
    }
}

function userClearRouteRendering(routeId) {
    const normalizedRouteId = normalizeUserRouteId(routeId);
    const layers = userRouteRenderings.get(normalizedRouteId) || [];
    layers.forEach((layer) => {
        try {
            window.usermap.removeLayer(layer);
        } catch (error) {
            console.warn('Failed to clear user route rendering:', error);
        }
    });
    userRouteRenderings.delete(normalizedRouteId);
}

function userRemoveAllTruckMarkers(exceptRouteId = null) {
    if (!window.usermap) {
        return;
    }

    const keepRouteId = exceptRouteId !== null ? normalizeUserRouteId(exceptRouteId) : null;

    for (const [routeId, truck] of Array.from(userActiveTrucks.entries())) {
        const normalizedRouteId = normalizeUserRouteId(routeId);
        if (keepRouteId && normalizedRouteId === keepRouteId) {
            continue;
        }

        if (truck?.animationFrame) {
            cancelAnimationFrame(truck.animationFrame);
            truck.animationFrame = null;
        }

        if (truck?.animatedVehicle) {
            userRemoveRegisteredRouteLayer(normalizedRouteId, truck.animatedVehicle);
            truck.animatedVehicle = null;
        }
    }

    window.usermap.eachLayer((layer) => {
        if (!(layer instanceof L.Marker)) {
            return;
        }

        const iconUrl = layer?.options?.icon?.options?.iconUrl || '';
        const className = layer?.options?.icon?.options?.className || '';
        const isTruckMarker = String(iconUrl).includes('/truck.png') || String(className).includes('fire-truck-icon');

        if (!isTruckMarker) {
            return;
        }

        try {
            window.usermap.removeLayer(layer);
        } catch (error) {
            console.warn('Failed to remove duplicate citizen truck marker:', error);
        }
    });
}

function applyTruckHeading(marker, angle) {
    if (!marker) {
        return;
    }

    const markerElement = marker.getElement();
    if (!markerElement) {
        return;
    }

    const visual = markerElement.querySelector('img') || markerElement.querySelector('.leaflet-marker-icon') || markerElement;
    visual.style.transformOrigin = '50% 50%';
    const currentTransform = visual.style.transform || '';
    const baseTransform = currentTransform.replace(/\s*rotate\([^)]*\)/g, '').trim();
    visual.style.transform = `${baseTransform} rotate(${angle}deg)`.trim();
}

document.addEventListener('DOMContentLoaded', function(){
    ensureCitizenMarkerStyles();
    initializeCitizenMapSearch();
    bindCitizenMapLayerToggleInputs();
    console.log('officers in users: ', window.thisOfficers);
    const userMapElement = document.getElementById('userMap');
    if (!userMapElement) {
        console.log('Citizen user map container not present on this page, skipping user map initialization');
        return;
    }
    // Initialize map only once
    if (!window.usermap || !window.usermap._container) {
        window.usermap = L.map('userMap').setView([15.8222, 120.3297], 13);
        window.userMap = window.usermap;
        ensureCitizenMarkerPanes();

        var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(window.usermap);

        var hotStreet = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors, Tiles style by Humanitarian OpenStreetMap Team'
        });
        
        var satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 18,
                attribution: 'Tiles © Esri'
            }
        );
        var waterReference = L.tileLayer(
            'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Hydro_Reference/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 18,
                attribution: 'Hydro reference (c) Esri'
            }
        );
        window.pyroPwa?.attachMapOfflineNotice?.(window.usermap);

        // Initialize layer groups
        stationLayerGroup = L.layerGroup().addTo(window.usermap);
        incidentLayerGroup = L.layerGroup().addTo(window.usermap);
        hydrantLayerGroup = L.layerGroup().addTo(window.usermap);
        otherLayerGroup = L.layerGroup().addTo(window.usermap);
        boundaryLayerGroup = L.layerGroup().addTo(window.usermap);

        citizenBaseLayers = {
            "OSM Standard": street,
            "OSM HOT": hotStreet,
            "Satellite": satellite
        };
        citizenActiveBaseLayerKey = 'OSM Standard';
        
        // Create layer control with all groups
        citizenLayerControl = L.control.layers(citizenBaseLayers, {
            "Fire Stations": stationLayerGroup,
            "Incidents": incidentLayerGroup,
            "Fire Hydrants": hydrantLayerGroup,
            "Other Markers": otherLayerGroup,
            "Boundaries": boundaryLayerGroup,
            "Water Reference": waterReference
        }, {
            collapsed: false,
            position: 'topright'
        }).addTo(window.usermap);
        enhanceCitizenLeafletLayerControl();
        
        // Set all layers visible by default
        stationLayerGroup.addTo(window.usermap);
        incidentLayerGroup.addTo(window.usermap);
        hydrantLayerGroup.addTo(window.usermap);
        otherLayerGroup.addTo(window.usermap);
        boundaryLayerGroup.addTo(window.usermap);
        syncCitizenMapLayerControls();
        bindCitizenMobileLayerControlTrigger();
        window.usermap.on('baselayerchange', (event) => {
            if (event?.name) {
                citizenActiveBaseLayerKey = String(event.name);
            } else {
                getCitizenActiveBaseLayer();
            }

            window.syncCitizenMapLayerControls?.();
        });

        console.log("Map initialized successfully with layer controls");
        
        // Load markers after a short delay
        setTimeout(() => {
            if (!markersLoaded) {
                loadMarkers();
                markersLoaded = true;
            }
            loadCitizenBoundaries();
            startCitizenRouteDiscovery();
            startCitizenMarkerWatcher();
        }, 500);
    } else {
        console.log("Map already exists, skipping initialization");
        window.userMap = window.usermap;
        ensureCitizenMarkerPanes();
        if (!boundaryLayerGroup) {
            boundaryLayerGroup = L.layerGroup().addTo(window.usermap);
        }
        // Load markers if not loaded
        if (!markersLoaded) {
            loadMarkers();
            markersLoaded = true;
        }
        loadCitizenBoundaries();
        syncCitizenMapLayerControls();
        bindCitizenMobileLayerControlTrigger();
        startCitizenRouteDiscovery();
        startCitizenMarkerWatcher();
    }

    // Function to get current location
    window.currentLoc = function(lat, lng) {
        console.log('[CitizenLocation] currentLoc called', {
            lat,
            lng,
            hasUserMap: Boolean(window.usermap),
            mapContainerId: window.usermap?._container?.id || null,
        });

        if (!window.usermap) {
            console.error('[CitizenLocation] currentLoc failed: map not initialized');
            window.__pendingCitizenLocation = { lat, lng };
            return;
        }

        renderCitizenLocationMarker(lat, lng, 180);
        window.usermap.setView([lat, lng], 15);

        console.log('[CitizenLocation] currentLoc completed', {
            lat,
            lng,
            zoom: window.usermap.getZoom(),
        });
    };

    if (
        window.__pendingCitizenLocation
        && Number.isFinite(Number(window.__pendingCitizenLocation.lat))
        && Number.isFinite(Number(window.__pendingCitizenLocation.lng))
    ) {
        const pendingLocation = window.__pendingCitizenLocation;
        console.log('[CitizenLocation] consuming queued location after map init', pendingLocation);
        window.__pendingCitizenLocation = null;
        window.currentLoc(Number(pendingLocation.lat), Number(pendingLocation.lng));
    }

    window.addEventListener('resize', () => {
        if (!isCitizenMobileViewport()) {
            window.closeCitizenMapLayersPanel?.();
            closeCitizenMapPreviewSheet();
        }
        bindCitizenMobileLayerControlTrigger();
    });

});

function getCurrentLocation(){
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // Set view to user location
            map.setView([lat, lng], 15);

            // Add marker
            L.marker([lat, lng])
                .addTo(map)
                .bindPopup('📍 You are here')
                .openPopup();

            // Optional: accuracy circle
            L.circle([lat, lng], {
                radius: position.coords.accuracy,
            }).addTo(map);

            console.log('User location:', lat, lng);
        },
        (error) => {
            console.error('Error getting location:', error);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 5000
        }
    );
}

function getIncidentTypePresentation(incidentTypeId, incidentTypeName = '') {
    const normalizedName = String(incidentTypeName || '').trim().toLowerCase();

    if (incidentTypeId == 1 || normalizedName.includes('fire')) {
        return { label: 'Fire', icon: 'fa-fire', color: '#dc2626' };
    }
    if (incidentTypeId == 2 || normalizedName.includes('medical')) {
        return { label: 'Medical Emergency', icon: 'fa-truck-medical', color: '#10b981' };
    }
    if (normalizedName.includes('rescue')) {
        return { label: incidentTypeName || 'Rescue', icon: 'fa-life-ring', color: '#f59e0b' };
    }
    if (normalizedName.includes('hazard')) {
        return { label: incidentTypeName || 'Hazardous Materials', icon: 'fa-radiation', color: '#16a34a' };
    }
    if (normalizedName.includes('accident') || incidentTypeId == 4) {
        return { label: incidentTypeName || 'Accident', icon: 'fa-car-crash', color: '#f97316' };
    }
    if (normalizedName.includes('disaster') || normalizedName.includes('flood') || normalizedName.includes('storm') || incidentTypeId == 3) {
        return { label: incidentTypeName || 'Natural Disaster', icon: 'fa-house-damage', color: '#0ea5e9' };
    }
    if (normalizedName.includes('false')) {
        return { label: incidentTypeName || 'False Alarm', icon: 'fa-times-circle', color: '#6b7280' };
    }

    return {
        label: incidentTypeName || 'Incident',
        icon: 'fa-exclamation-triangle',
        color: '#6b7280'
    };
}

// Get incident type name by ID
function getIncidentTypeName(typeId) {
    return getIncidentTypePresentation(typeId).label;
}

function getCitizenMarkerCategoryKey(markerOrCategory) {
    if (markerOrCategory && typeof markerOrCategory === 'object') {
        if (markerOrCategory.category !== undefined && markerOrCategory.category !== null) {
            return getCitizenMarkerCategoryKey(markerOrCategory.category);
        }

        if (markerOrCategory.markerCategory_id !== undefined && markerOrCategory.markerCategory_id !== null) {
            return getCitizenMarkerCategoryKey(markerOrCategory.markerCategory_id);
        }
    }

    if (typeof markerOrCategory === 'string') {
        const normalized = markerOrCategory.trim().toLowerCase();

        if (['fire', 'station', 'hydrant', 'school', 'barangay', 'bridge', 'risk', 'hazard'].includes(normalized)) {
            return normalized;
        }

        if (normalized === 'incident') {
            return 'fire';
        }
    }

    const categoryId = Number(markerOrCategory);
    if (Number.isNaN(categoryId)) {
        return null;
    }

    return {
        1: 'station',
        2: 'fire',
        3: 'fire',
        4: 'hydrant',
        5: 'school',
        6: 'barangay',
        7: 'bridge'
    }[categoryId] ?? null;
}

function isCitizenIncidentCategory(categoryInput) {
    return getCitizenMarkerCategoryKey(categoryInput) === 'fire';
}

function isCitizenHydrantCategory(categoryInput) {
    return getCitizenMarkerCategoryKey(categoryInput) === 'hydrant';
}

function isCitizenStationCategory(categoryInput) {
    return getCitizenMarkerCategoryKey(categoryInput) === 'station';
}

function getCitizenMarkerPresentation(categoryInput) {
    const categoryKey = getCitizenMarkerCategoryKey(categoryInput);

    switch (categoryKey) {
        case 'station':
            return { key: categoryKey, label: 'Fire Station', icon: 'fa-building', color: '#2563eb', accentClass: 'station' };
        case 'hydrant':
            return { key: categoryKey, label: 'Fire Hydrant', icon: 'fa-fire-extinguisher', color: '#ef4444', accentClass: 'hydrant' };
        case 'fire':
            return { key: categoryKey, label: 'Fire Incident', icon: 'fa-fire', color: '#f97316', accentClass: 'incident' };
        case 'school':
            return { key: categoryKey, label: 'School', icon: 'fa-school', color: '#7c3aed', accentClass: 'default' };
        case 'barangay':
            return { key: categoryKey, label: 'Barangay', icon: 'fa-people-group', color: '#0f766e', accentClass: 'default' };
        case 'bridge':
            return { key: categoryKey, label: 'Bridge', icon: 'fa-road-bridge', color: '#64748b', accentClass: 'default' };
        case 'risk':
            return { key: categoryKey, label: 'Risk Marker', icon: 'fa-triangle-exclamation', color: '#0284c7', accentClass: 'default' };
        case 'hazard':
            return { key: categoryKey, label: 'Hazard Marker', icon: 'fa-radiation', color: '#d97706', accentClass: 'default' };
        default:
            return { key: 'default', label: 'Marker', icon: 'fa-map-marker-alt', color: '#6b7280', accentClass: 'default' };
    }
}

// Get icon color based on incident type
function getIncidentColor(incidentTypeId, incidentTypeName = '') {
    return getIncidentTypePresentation(incidentTypeId, incidentTypeName).color;
}

function normalizeCitizenIncidentStatus(input = '') {
    const numericStatus = Number(input);
    if (Number.isFinite(numericStatus)) {
        if (numericStatus === 2) return 'en-route';
        if (numericStatus === 3) return 'on-scene';
        if (numericStatus === 4) return 'controlled';
        if (numericStatus === 5) return 'closed';
        if (numericStatus === 1) return 'reported';
    }

    const normalized = String(input || '').trim().toLowerCase();
    if (normalized === 'en-route' || normalized === 'responding') return 'en-route';
    if (normalized === 'on scene' || normalized === 'on-scene') return 'on-scene';
    if (normalized === 'controlled' || normalized === 'resolved' || normalized === 'completed') return 'controlled';
    if (normalized === 'closed') return 'closed';
    return 'reported';
}

function isCitizenActiveFireIncident(incident = null, marker = {}) {
    if (!incident && !marker) {
        return false;
    }

    const status = normalizeCitizenIncidentStatus(
        incident?.incident_status_id
        ?? incident?.status_id
        ?? incident?.incident_status
        ?? incident?.status
        ?? marker?.incident_status
        ?? marker?.status
        ?? 'reported'
    );

    return ['reported', 'en-route', 'on-scene'].includes(status);
}

function inferCitizenSharedMarkerAsset(categoryKey, marker = {}) {
    const haystack = [
        marker?.title,
        marker?.description,
        marker?.category,
        marker?.hazard_type,
        marker?.risk_type,
        marker?.type
    ].filter(Boolean).join(' ').toLowerCase();

    if (categoryKey === 'station') return '/station.png';
    if (categoryKey === 'hydrant') return '/hydrant.png';
    if (categoryKey === 'barangay') return '/hall.png';
    if (categoryKey === 'fire') return '/fire.png';
    if (categoryKey === 'risk' || categoryKey === 'hazard') return '/hazard.png';
    if (haystack.includes('gas')) return '/gas.png';
    if (haystack.includes('electric') || haystack.includes('wiring') || haystack.includes('octopus')) return '/electric.png';
    if (haystack.includes('flammable')) return '/flammable.png';
    if (haystack.includes('block')) return '/blockroad.png';
    if (haystack.includes('damaged')) return '/damaged.png';
    if (haystack.includes('hazard') || haystack.includes('risk')) return '/hazard.png';

    return null;
}

function createCitizenStaticImageIcon(categoryInput, marker = {}) {
    const categoryKey = getCitizenMarkerCategoryKey(categoryInput) || 'default';
    const isInactiveIncident = categoryKey === 'fire' && !isCitizenActiveFireIncident(null, marker);
    const imagePath = isInactiveIncident ? null : inferCitizenSharedMarkerAsset(categoryKey, marker);
    const size = imagePath ? (categoryKey === 'fire' ? 42 : 36) : 24;
    const fallback = isInactiveIncident
        ? { icon: 'fa-magnifying-glass', color: '#10b981' }
        : getCitizenMarkerPresentation(categoryKey);
    const safeImagePath = imagePath ? String(imagePath).replace(/"/g, '&quot;') : '';

    return L.divIcon({
        className: 'shared-map-div-icon citizen-shared-map-icon',
        html: `
            <span class="shared-map-marker" style="width:${size}px;height:${size}px;">
                <span class="shared-map-marker__icon" style="width:${size}px;height:${size}px;">
                    ${safeImagePath
                        ? `<img src="${safeImagePath}" alt="" draggable="false" width="${size}" height="${size}" style="width:${size}px;height:${size}px;object-fit:contain;">`
                        : `<i class="fas ${fallback.icon}" style="color:${fallback.color};font-size:${Math.round(size * 0.58)}px;"></i>`}
                </span>
            </span>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2]
    });
}

function getCitizenSharedMarkerIcon(categoryInput, marker = {}, incident = null) {
    ensureCitizenMarkerStyles();

    const categoryKey = getCitizenMarkerCategoryKey(categoryInput) || 'default';
    const sharedType = categoryKey === 'fire' ? 'fire' : categoryKey;
    const status = incident?.incident_status_id
        ?? incident?.status_id
        ?? incident?.incident_status
        ?? incident?.status
        ?? marker?.incident_status
        ?? marker?.status
        ?? null;

    if (typeof window.createSharedMapMarkerIcon === 'function') {
        return window.createSharedMapMarkerIcon(sharedType, {
            marker: {
                ...marker,
                category: sharedType,
                markerCategory_id: marker?.markerCategory_id
            },
            status,
            isActive: categoryKey === 'fire' ? isCitizenActiveFireIncident(incident, marker) : false
        });
    }

    return createCitizenStaticImageIcon(categoryKey, marker);
}

// Get icon based on incident type
function getIncidentIcon(incidentTypeId, incidentTypeName = '', marker = {}, incident = null) {
    return getCitizenSharedMarkerIcon('fire', marker, incident);
}

// Get station icon
function getStationIcon(marker = {}) {
    return getCitizenSharedMarkerIcon('station', marker);
}

// Get hydrant icon
function getHydrantIcon(marker = {}) {
    return getCitizenSharedMarkerIcon('hydrant', marker);
}

function getCitizenCategoryIcon(categoryInput, marker = {}, incident = null) {
    return getCitizenSharedMarkerIcon(categoryInput, marker, incident);
}

// Get default marker icon
function getDefaultIcon(marker = {}) {
    return getCitizenSharedMarkerIcon('default', marker);
}

function getCitizenReferenceLocation() {
    if (window.userCoords?.ready && window.userCoords.currentLatitude && window.userCoords.currentLongitude) {
        return {
            lat: parseFloat(window.userCoords.currentLatitude),
            lng: parseFloat(window.userCoords.currentLongitude)
        };
    }

    return null;
}

function getDistanceMetersForUserMap(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);

    return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function isOwnReportedIncident(incident) {
    if (!incident) {
        return false;
    }

    const currentUserId = parseInt(window.currentCitizenId || window.currentUserId);
    if (!currentUserId) {
        return false;
    }

    return parseInt(incident.reported_by) === currentUserId;
}

function shouldDisplayIncidentMarker(usermark, incident) {
    if (!incident) {
        return false;
    }

    const incidentStatusId = Number(incident.incident_status_id ?? incident.status_id ?? null);
    const incidentTypeId = Number(incident.incident_type_id ?? incident.incident_type?.incident_type_id ?? null);
    const incidentStatus = String(incident.incident_status || incident.status || '').trim().toLowerCase();
    const incidentType = String(incident.incident_type?.type || incident.incident_type_name || incident.incident_type || '').trim().toLowerCase();

    if (
        incidentStatusId === 4
        || incidentStatusId === 5
        || incidentStatus === 'controlled'
        || incidentStatus === 'closed'
        || incidentTypeId === 5
        || incidentType === 'false alarm'
    ) {
        return false;
    }

    if (isOwnReportedIncident(incident)) {
        return true;
    }

    const userLocation = getCitizenReferenceLocation();
    if (!userLocation) {
        return false;
    }

    const markerLat = parseFloat(usermark.lat);
    const markerLng = parseFloat(usermark.lng);

    if (Number.isNaN(markerLat) || Number.isNaN(markerLng)) {
        return false;
    }

    return getDistanceMetersForUserMap(
        userLocation.lat,
        userLocation.lng,
        markerLat,
        markerLng
    ) <= USER_INCIDENT_VISIBILITY_METERS;
}

function getOwnReportedIncidentIds() {
    const currentUserId = parseInt(window.currentCitizenId || window.currentUserId);
    const ownIncidents = Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [];
    const ids = new Set();

    ownIncidents.forEach((incident) => {
        if (incident?.incident_id) {
            ids.add(parseInt(incident.incident_id));
        }
    });

    if (currentUserId && Array.isArray(window.incidentsData)) {
        window.incidentsData.forEach((incident) => {
            if (parseInt(incident?.reported_by) === currentUserId && incident?.incident_id) {
                ids.add(parseInt(incident.incident_id));
            }
        });
    }

    return ids;
}

async function syncCitizenRouteDisplay() {
    const now = Date.now();
    if (citizenRouteDiscoveryPausedUntil > now) {
        return;
    }

    const ownIncidentIds = getOwnReportedIncidentIds();

    if (ownIncidentIds.size === 0) {
        if (userTrackedIncidentId !== null) {
            userTrackedIncidentId = null;
            userClearAllRoutes();
        }
        return;
    }

    try {
        const response = await fetchCitizenLiveResponseOnce('citizen-route-active', '/api/route/active', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!response.ok) {
            citizenRouteDiscoveryFailureCount += 1;
            citizenRouteDiscoveryPausedUntil = Date.now() + Math.min(60000, 5000 * citizenRouteDiscoveryFailureCount);
            return;
        }

        citizenRouteDiscoveryFailureCount = 0;
        citizenRouteDiscoveryPausedUntil = 0;

        const activeRoutes = await response.json();
        const matchingRoute = activeRoutes.find((route) => ownIncidentIds.has(parseInt(route.incident_id)));

        if (!matchingRoute) {
            if (userTrackedIncidentId !== null) {
                userTrackedIncidentId = null;
                userClearAllRoutes();
            }
            return;
        }

        const incidentId = parseInt(matchingRoute.incident_id);
        if (incidentId === userTrackedIncidentId) {
            return;
        }

        userTrackedIncidentId = incidentId;
        await userFetchAndDisplayRoutesForIncident(incidentId);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        citizenRouteDiscoveryFailureCount += 1;
        citizenRouteDiscoveryPausedUntil = Date.now() + Math.min(60000, 5000 * citizenRouteDiscoveryFailureCount);
        console.error('Error syncing citizen route display:', error);
    }
}

function startCitizenRouteDiscovery() {
    if (userOwnedRouteDiscoveryInterval) {
        clearInterval(userOwnedRouteDiscoveryInterval);
    }

    syncCitizenRouteDisplay();
    userOwnedRouteDiscoveryInterval = setInterval(() => {
        if (!citizenPageIsVisible()) return;
        syncCitizenRouteDisplay();
    }, CITIZEN_ROUTE_DISCOVERY_INTERVAL_MS);
}

function refreshCitizenMapData() {
    syncCitizenRouteDisplay();
    return refreshCitizenMarkersIfChanged(true);
}

async function refreshCitizenMarkersIfChanged(forceReload = false) {
    if (!forceReload && !citizenPageIsVisible()) {
        return false;
    }

    try {
        const latestResponse = await fetchCitizenLiveResponseOnce('citizen-markers-latest', '/map/markers/latest', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!latestResponse.ok) {
            throw new Error(`Failed latest marker check: ${latestResponse.status}`);
        }

        const latestData = await latestResponse.json();
        const nextSignature = latestData?.marker_id
            ? `${latestData.marker_id}:${latestData.updated_at || ''}:${latestData.count || 0}`
            : `none:${latestData?.count || 0}`;

        if (!forceReload && latestCitizenMarkerSignature === nextSignature) {
            return false;
        }

        const markerFeedParams = new URLSearchParams();
        const citizenLocation = getCitizenReferenceLocation();
        if (citizenLocation) {
            markerFeedParams.set('lat', String(citizenLocation.lat));
            markerFeedParams.set('lng', String(citizenLocation.lng));
        }
        const markerFeedUrl = `/map/markers/feed${markerFeedParams.size ? `?${markerFeedParams.toString()}` : ''}`;
        const markersResponse = await fetchCitizenLiveResponseOnce('citizen-markers', markerFeedUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!markersResponse.ok) {
            throw new Error(`Failed marker reload: ${markersResponse.status}`);
        }

        const markers = await markersResponse.json();
        window.mapData = window.mapData || {};
        window.mapData.usermark = Array.isArray(markers)
            ? markers
            : (Array.isArray(markers?.data) ? markers.data : []);
        window.pyroPwa?.storeDataset?.('citizen-map-markers', window.mapData.usermark);
        latestCitizenMarkerSignature = nextSignature;
        loadMarkers();
        return true;
    } catch (error) {
        if (error?.name === 'AbortError') {
            return false;
        }
        const cachedMarkers = window.pyroPwa?.loadDataset?.('citizen-map-markers', []);
        if (Array.isArray(cachedMarkers) && cachedMarkers.length) {
            window.mapData = window.mapData || {};
            window.mapData.usermark = cachedMarkers;
            loadMarkers();
        }
        console.error('Error refreshing citizen markers:', error);
        return false;
    }
}

function startCitizenMarkerWatcher() {
    if (markerChangePollInterval) {
        clearInterval(markerChangePollInterval);
    }

    markerChangePollInterval = setInterval(() => {
        if (!citizenPageIsVisible()) return;
        refreshCitizenMarkersIfChanged(false);
    }, CITIZEN_MARKER_WATCH_INTERVAL_MS);
}

function getCitizenUsefulValue(...values) {
    for (const value of values) {
        if (value === null || value === undefined) continue;
        const normalized = String(value).replace(/\s+/g, ' ').trim();
        if (!normalized || /^n\/?a$/i.test(normalized) || /^null$/i.test(normalized) || /^undefined$/i.test(normalized)) {
            continue;
        }
        return normalized;
    }

    return '';
}

function formatCitizenPopupDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString([], {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
}

function formatCitizenPopupCoords(marker) {
    const lat = Number(marker?.lat);
    const lng = Number(marker?.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return '';
    }

    return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
}

function sanitizeCitizenPhoneHref(value) {
    const phone = getCitizenUsefulValue(value);
    if (!phone) return '';
    return phone.replace(/[^\d+]/g, '');
}

function getCitizenStatusMeta(statusLabel = '', incident = null) {
    const normalized = String(statusLabel || '').toLowerCase();

    if (incident || ['restricted', 'unavailable', 'emergency'].some((token) => normalized.includes(token))) {
        return { className: 'danger', icon: 'fa-circle' };
    }

    if (['warning', 'limited', 'pending', 'reported', 'responding'].some((token) => normalized.includes(token))) {
        return { className: 'warning', icon: 'fa-circle' };
    }

    if (['active', 'available', 'operational', 'verified', 'resolved'].some((token) => normalized.includes(token))) {
        return { className: 'ok', icon: 'fa-circle' };
    }

    return { className: 'neutral', icon: 'fa-circle' };
}

function getCitizenPopupStatusLabel(incident, marker = null) {
    const rawStatus = getCitizenUsefulValue(
        incident?.status,
        incident?.incident_status,
        marker?.status,
        marker?.verification_status,
        marker?.is_verified ? 'Verified' : ''
    );

    if (!rawStatus) return '';
    return rawStatus
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function getCitizenPopupTitle(marker, incident, markerPresentation, markerCategory) {
    const explicitTitle = getCitizenUsefulValue(
        marker?.station_public?.name,
        marker?.barangay_public?.name,
        marker?.hydrant_public?.code,
        marker?.title,
        marker?.name,
        marker?.station_name,
        marker?.barangay_name,
        marker?.hydrant_name,
        marker?.place_name,
        incident?.title,
        incident?.incident_type,
        incident?.type
    );

    if (explicitTitle && !/^marker\s*#?\d+$/i.test(explicitTitle)) {
        return explicitTitle;
    }

    const location = getCitizenUsefulValue(
        marker?.station_public?.address,
        marker?.barangay_public?.address,
        marker?.location_name,
        marker?.barangay,
        marker?.barangay_name,
        marker?.street
    );

    if (incident) {
        return location ? `Active Fire Incident - ${location}` : 'Active Fire Incident';
    }

    if (isCitizenStationCategory(markerCategory)) {
        return location ? `${location} Fire Station` : 'Fire Station';
    }

    if (isCitizenHydrantCategory(markerCategory)) {
        return location ? `Fire Hydrant - ${location}` : 'Fire Hydrant';
    }

    if (markerCategory === 'barangay') {
        return location ? `${location} Barangay Hall` : 'Barangay Hall';
    }

    return location ? `${markerPresentation.label} - ${location}` : markerPresentation.label;
}

function appendCitizenPopupRow(rows, label, ...values) {
    const value = getCitizenUsefulValue(...values);
    if (value) {
        rows.push({ label, value });
    }
}

function appendCitizenPopupLinkRow(rows, label, value, href) {
    const normalizedValue = getCitizenUsefulValue(value);
    const normalizedHref = getCitizenUsefulValue(href);
    if (normalizedValue && normalizedHref) {
        rows.push({ label, value: normalizedValue, href: normalizedHref });
    } else if (normalizedValue) {
        rows.push({ label, value: normalizedValue });
    }
}

function buildCitizenPopupRows(marker, incident, markerPresentation, markerCategory) {
    const rows = [];

    if (incident) {
        appendCitizenPopupRow(rows, 'Incident type', incident?.incident_type?.type, incident?.incident_type_name, incident?.incident_type, markerPresentation.label);
        appendCitizenPopupRow(rows, 'Reported', formatCitizenPopupDate(incident.alarmdatetime || incident.created_at || marker?.created_at));
        appendCitizenPopupRow(rows, 'Location', incident?.address, marker?.location, marker?.barangay, marker?.barangay_name);
        appendCitizenPopupRow(rows, 'Advisory', marker?.public_advisory, incident?.public_advisory, 'Keep a safe distance and follow official instructions.');
    } else if (isCitizenStationCategory(markerCategory)) {
        appendCitizenPopupRow(rows, 'Address', marker?.station_public?.address);
        appendCitizenPopupRow(rows, 'Station chief', marker?.station_public?.chief_name);
        appendCitizenPopupLinkRow(
            rows,
            'Contact',
            marker?.station_public?.contact_number,
            sanitizeCitizenPhoneHref(marker?.station_public?.contact_number) ? `tel:${sanitizeCitizenPhoneHref(marker?.station_public?.contact_number)}` : ''
        );
        appendCitizenPopupRow(rows, 'Hours', marker?.station_public?.operating_hours);
        appendCitizenPopupRow(rows, 'Last updated', formatCitizenPopupDate(marker?.updated_at));
    } else if (isCitizenHydrantCategory(markerCategory)) {
        appendCitizenPopupRow(rows, 'Hydrant code', marker?.hydrant_public?.code);
        appendCitizenPopupRow(rows, 'Type', marker?.hydrant_public?.type);
        appendCitizenPopupRow(rows, 'Location', marker?.location, marker?.address);
        appendCitizenPopupRow(rows, 'Condition', marker?.operational_status, marker?.condition);
        appendCitizenPopupRow(rows, 'Access', marker?.accessibility, marker?.access);
        appendCitizenPopupRow(rows, 'Last checked', formatCitizenPopupDate(marker?.last_inspection_at || marker?.inspected_at || marker?.updated_at));
    } else if (markerCategory === 'barangay') {
        appendCitizenPopupRow(rows, 'Address', marker?.barangay_public?.address);
        appendCitizenPopupLinkRow(
            rows,
            'Contact',
            marker?.barangay_public?.contact_number,
            sanitizeCitizenPhoneHref(marker?.barangay_public?.contact_number) ? `tel:${sanitizeCitizenPhoneHref(marker?.barangay_public?.contact_number)}` : ''
        );
        appendCitizenPopupRow(rows, 'Office hours', marker?.barangay_public?.office_hours);
        appendCitizenPopupRow(rows, 'Barangay', marker?.barangay_public?.barangay_name);
    } else {
        appendCitizenPopupRow(rows, 'Address', marker?.address, marker?.location);
        appendCitizenPopupRow(rows, 'Barangay', marker?.barangay, marker?.barangay_name);
        appendCitizenPopupRow(rows, 'City', marker?.city, marker?.municipality);
        appendCitizenPopupLinkRow(
            rows,
            'Contact',
            getCitizenUsefulValue(marker?.contact_number, marker?.phone, marker?.mobile),
            sanitizeCitizenPhoneHref(getCitizenUsefulValue(marker?.contact_number, marker?.phone, marker?.mobile))
                ? `tel:${sanitizeCitizenPhoneHref(getCitizenUsefulValue(marker?.contact_number, marker?.phone, marker?.mobile))}`
                : ''
        );
        appendCitizenPopupRow(rows, 'Hours', marker?.office_hours, marker?.operating_hours, marker?.hours);
    }

    appendCitizenPopupRow(rows, 'Coordinates', formatCitizenPopupCoords(marker));

    return rows;
}

function buildCitizenPopupAction(marker) {
    const lat = Number(marker?.lat);
    const lng = Number(marker?.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return '';
    }

    return `
        <button type="button" class="citizen-marker-popup-btn primary" onclick="window.centerCitizenMarkerPopup(${lat}, ${lng})" aria-label="Center map on this marker">
            <i class="fas fa-crosshairs"></i>
            Center on map
        </button>
    `;
}

window.centerCitizenMarkerPopup = function centerCitizenMarkerPopup(lat, lng) {
    const targetLat = Number(lat);
    const targetLng = Number(lng);

    if (!window.usermap || !Number.isFinite(targetLat) || !Number.isFinite(targetLng)) {
        return;
    }

    const targetZoom = Math.max(window.usermap.getZoom() || 15, 17);
    if (typeof window.usermap.flyTo === 'function') {
        window.usermap.flyTo([targetLat, targetLng], targetZoom, { duration: 0.55 });
    } else {
        window.usermap.setView([targetLat, targetLng], targetZoom);
    }
};

function syncCitizenRiskReportsToMainMapMarkers() {
    const riskReports = Array.isArray(window.citizenRiskMapReports)
        ? window.citizenRiskMapReports
        : [];

    window.mapData = window.mapData || {};
    const existingMarkers = Array.isArray(window.mapData.usermark)
        ? window.mapData.usermark
        : [];
    const riskMarkerIds = new Set(
        riskReports
            .map((report) => report?.marker_id ?? report?.risk_area_id)
            .filter((id) => id !== null && id !== undefined)
            .map((id) => String(id))
    );
    const nextMarkers = existingMarkers.filter((marker) => {
        if (!marker?._citizenRiskReportMarker) {
            return true;
        }

        return riskMarkerIds.has(String(marker.marker_id));
    });
    const markerIndex = new Map(
        nextMarkers
            .filter((marker) => marker?.marker_id !== null && marker?.marker_id !== undefined)
            .map((marker) => [String(marker.marker_id), marker])
    );

    riskReports.forEach((report) => {
        const lat = Number(report?.lat);
        const lng = Number(report?.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const markerId = report?.marker_id ?? `risk-report-${report?.risk_area_id ?? `${lat}:${lng}`}`;
        const category = report?.marker_category
            || report?.entry_type
            || report?.report_kind
            || 'risk';
        const marker = {
            marker_id: markerId,
            category,
            title: report?.title || (category === 'hazard' ? 'Hazard Marker' : 'Risk Marker'),
            description: report?.description || report?.remarks || report?.condition_label || 'Citizen risk/hazard report.',
            status: report?.status || report?.review_status || 'pending',
            lat,
            lng,
            hazard_type: report?.hazard_type || report?.hazard_category || null,
            risk_type: report?.risk_type || null,
            _citizenRiskReportMarker: true
        };
        const existing = markerIndex.get(String(markerId));

        if (existing) {
            Object.assign(existing, marker);
            return;
        }

        markerIndex.set(String(markerId), marker);
        nextMarkers.unshift(marker);
    });

    window.mapData.usermark = nextMarkers;
}

function shouldDisplayCitizenRiskOrHazardMarker(marker = {}, markerCategory = '') {
    if (!['risk', 'hazard'].includes(markerCategory)) {
        return true;
    }

    // The server-side marker feed already applies review and role visibility.
    // Do not gate a valid map marker on the separately refreshed report list,
    // because those two requests can finish in either order.
    return String(marker?.status || '').trim().toLowerCase() !== 'inactive';
}

export async function loadMarkers() {
    // Check if map exists
    if (!window.usermap) {
        console.error('Map not initialized, cannot load markers');
        return;
    }

    if ((!Array.isArray(window.mapData?.usermark) || !window.mapData.usermark.length) && window.pyroPwa?.loadDataset) {
        const cachedMarkers = window.pyroPwa.loadDataset('citizen-map-markers', []);
        if (Array.isArray(cachedMarkers) && cachedMarkers.length) {
            window.mapData = window.mapData || {};
            window.mapData.usermark = cachedMarkers;
        }
    }

    syncCitizenRiskReportsToMainMapMarkers();
    
    const usermarkermap = window.mapData.usermark || [];
    const incidentsData = window.incidentsData || [];
    buildCitizenMapSearchIndex(usermarkermap, incidentsData);
    
    // Clear existing markers from all layers
    if (stationLayerGroup) stationLayerGroup.clearLayers();
    if (incidentLayerGroup) incidentLayerGroup.clearLayers();
    if (hydrantLayerGroup) hydrantLayerGroup.clearLayers();
    if (otherLayerGroup) otherLayerGroup.clearLayers();
    citizenIncidentMarkerRefs.clear();

    // Create a map to quickly find incident by marker_id
    const incidentByMarkerId = {};
    incidentsData.forEach(incident => {
        if (incident.marker_id) {
            incidentByMarkerId[incident.marker_id] = incident;
        }
    });

    let bounds = L.latLngBounds(); // To auto-fit map to markers
    let markerCount = 0;
    const legendEntries = [];
    const legendKeys = new Set();

    const registerLegendEntry = (entry) => {
        if (!entry?.key || legendKeys.has(entry.key)) {
            return;
        }

        legendKeys.add(entry.key);
        legendEntries.push(entry);
    };
    
    usermarkermap.forEach(function(usermark) {
        if (usermark.lat && usermark.lng) {
            const markerCategory = getCitizenMarkerCategoryKey(usermark);
            // Check if this marker is an incident
            const incident = incidentByMarkerId[usermark.marker_id];
            const isIncidentMarker = Boolean(isCitizenIncidentCategory(markerCategory) || incident);

            if (!shouldDisplayCitizenRiskOrHazardMarker(usermark, markerCategory)) {
                return;
            }

            if (isIncidentMarker && !shouldDisplayIncidentMarker(usermark, incident)) {
                return;
            }

            markerCount++;
            // Extend bounds to include this marker
            bounds.extend([usermark.lat, usermark.lng]);
            
            // Determine icon and layer based on marker type
            let icon;
            let targetLayer;
            
            if (isCitizenStationCategory(markerCategory)) {
                // Fire Station
                icon = getStationIcon(usermark);
                targetLayer = stationLayerGroup;
                registerLegendEntry({
                    key: 'fire-station',
                    label: 'Fire Station',
                    swatch: buildLegendSwatch({ imagePath: '/station.png', color: '#2563eb', size: 20 })
                });
            } 
            else if (isCitizenHydrantCategory(markerCategory)) {
                // Fire Hydrant
                icon = getHydrantIcon(usermark);
                targetLayer = hydrantLayerGroup;
                registerLegendEntry({
                    key: 'hydrant',
                    label: 'Fire Hydrant',
                    swatch: buildLegendSwatch({ imagePath: '/hydrant.png', color: '#ef4444', shape: 'square', size: 20 })
                });
            }
            else if (isCitizenIncidentCategory(markerCategory) || incident) {
                // Fire incident marker based on markers.category
                const incidentMarkerPresentation = getCitizenMarkerPresentation('fire');
                icon = getCitizenCategoryIcon('fire', usermark, incident);
                targetLayer = incidentLayerGroup;
                registerLegendEntry({
                    key: 'incident-fire',
                    label: incidentMarkerPresentation.label,
                    swatch: buildLegendSwatch({ imagePath: '/fire.png', color: incidentMarkerPresentation.color, size: 22 })
                });
            }
            else {
                // Other markers
                const markerLegend = getCitizenMarkerPresentation(markerCategory);
                icon = getCitizenCategoryIcon(markerCategory, usermark) || getDefaultIcon(usermark);
                targetLayer = otherLayerGroup;
                registerLegendEntry({
                    key: `marker-${markerLegend.key}`,
                    label: markerLegend.label,
                    swatch: buildLegendSwatch({
                        color: markerLegend.color,
                        icon: markerLegend.icon,
                        imagePath: inferCitizenSharedMarkerAsset(markerLegend.key, usermark),
                        iconHtml: markerLegend.key === 'barangay' ? BARANGAY_GROUP_ICON_SVG : null
                    })
                });
            }
            
            // Create popup content
            const markerPresentation = getCitizenMarkerPresentation(markerCategory);
            const incidentStatus = String(incident?.status || 'reported').toLowerCase();
            const statusLabel = getCitizenPopupStatusLabel(incident, usermark) || (incident ? 'Reported' : '');
            const statusMeta = getCitizenStatusMeta(statusLabel || incidentStatus, incident);
            const popupTitle = getCitizenPopupTitle(usermark, incident, markerPresentation, markerCategory);
            const popupDescription = getCitizenUsefulValue(
                incident?.public_description,
                incident?.description,
                usermark.public_description,
                usermark.description
            );
            const detailRows = buildCitizenPopupRows(usermark, incident, markerPresentation, markerCategory);
            const popupAction = buildCitizenPopupAction(usermark);

            let popupContent = `
                <div class="citizen-marker-popup ${markerPresentation.accentClass}">
                    <div class="citizen-marker-popup-header">
                        <div class="citizen-marker-popup-icon" style="--popup-accent:${markerPresentation.color};">
                            <i class="fas ${markerPresentation.icon}"></i>
                        </div>
                        <div class="citizen-marker-popup-heading">
                            <strong class="citizen-marker-popup-title">${escapeCitizenModalHtml(popupTitle)}</strong>
                            <div class="citizen-marker-popup-chip-row">
                                <span class="citizen-marker-popup-chip">${escapeCitizenModalHtml(incident ? 'Fire incident' : markerPresentation.label)}</span>
                                ${statusLabel ? `
                                    <span class="citizen-marker-popup-chip status ${statusMeta.className}">
                                        <i class="fas ${statusMeta.icon}"></i>
                                        ${escapeCitizenModalHtml(statusLabel)}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
            `;

            if (usermark.image) {
                popupContent += `
                    <div class="citizen-marker-popup-media">
                        <img src="${usermark.image}" alt="${escapeCitizenModalHtml(usermark.title || 'Marker image')}" class="citizen-marker-popup-image" />
                    </div>
                `;
            }

            if (popupDescription) {
                popupContent += `
                    <p class="citizen-marker-popup-subtitle">${escapeCitizenModalHtml(popupDescription)}</p>
                `;
            }

            popupContent += `
                    <div class="citizen-marker-popup-details">
                        ${detailRows.map((row) => `
                            <div class="citizen-marker-popup-detail-row">
                                <span class="citizen-marker-popup-detail-label">${escapeCitizenModalHtml(row.label)}</span>
                                <span class="citizen-marker-popup-detail-value">
                                    ${row.href
                                        ? `<a href="${escapeCitizenModalHtml(row.href)}">${escapeCitizenModalHtml(row.value)}</a>`
                                        : escapeCitizenModalHtml(row.value)}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                    ${popupAction ? `
                        <div class="citizen-marker-popup-actions">
                            ${popupAction}
                        </div>
                    ` : ''}
            `;

            popupContent += `
                </div>
            `;
            
            // Create marker and add to appropriate layer group
            const marker = L.marker([usermark.lat, usermark.lng], {
                icon: icon,
                pane: getCitizenMarkerPane(markerCategory, isIncidentMarker),
                zIndexOffset: isIncidentMarker
                    ? 400
                    : isCitizenHydrantCategory(markerCategory)
                        ? 300
                        : isCitizenStationCategory(markerCategory)
                            ? 200
                            : markerCategory === 'barangay'
                                ? 100
                                : 0
            })
                .bindPopup(popupContent, {
                    minWidth: 260,
                    maxWidth: 320,
                    className: 'custom-popup citizen-marker-leaflet-popup'
                });

            marker.citizenPreviewData = {
                marker: usermark,
                incident: incident || null,
                popupContent
            };
            
            if (!targetLayer) {
                targetLayer = otherLayerGroup;
            }
            if (!targetLayer || typeof targetLayer.addLayer !== 'function') {
                console.warn('Citizen marker layer is not ready; marker refresh deferred.', usermark?.marker_id);
                return;
            }
            targetLayer.addLayer(marker);

            if (incident) {
                const incidentKey = String(incident.incident_id ?? '');
                const markerKey = String(usermark.marker_id ?? '');
                if (incidentKey) {
                    citizenIncidentMarkerRefs.set(`incident:${incidentKey}`, marker);
                }
                if (markerKey) {
                    citizenIncidentMarkerRefs.set(`marker:${markerKey}`, marker);
                }
            }
            
            marker.on('click', function () {
                if (isCitizenMobileViewport()) {
                    suppressCitizenMobilePopup(marker);
                    marker.closePopup?.();
                    openCitizenMapPreviewSheet(popupContent, {
                        marker: usermark,
                        incident: incident || null
                    });
                } else {
                    closeCitizenMapPreviewSheet();
                }
            });

            marker.on('popupopen', function () {
                if (isCitizenMobileViewport()) {
                    suppressCitizenMobilePopup(marker);
                    return;
                }

            });

            marker.on('preclick', function () {
                if (isCitizenMobileViewport()) {
                    suppressCitizenMobilePopup(marker);
                }
            });
        }
    });
    
    // Fit map to show all markers if there are markers
    if (markerCount > 0 && bounds.isValid()) {
        window.usermap.fitBounds(bounds, { padding: [50, 50] });
        console.log(`Map fitted to ${markerCount} markers`);
    } else {
        const userLocation = getCitizenReferenceLocation();
        if (userLocation) {
            window.usermap.setView([userLocation.lat, userLocation.lng], 15);
        } else {
            window.usermap.setView([15.8222, 120.3297], 13);
        }
    }
    
    updateCitizenMapLegend(legendEntries);
    const searchInput = document.getElementById('citizenMapSearchInput');
    if (searchInput?.value?.trim()) {
        runCitizenMapSearch(searchInput.value);
    }
    console.log(`Total markers loaded: ${markerCount}`);
}

window.focusCitizenIncidentOnMap = function(incident = {}) {
    if (!incident) return;

    const markerId = incident.marker_id != null ? String(incident.marker_id) : '';
    const incidentId = incident.incident_id != null ? String(incident.incident_id) : '';
    const lat = incident.lat != null ? parseFloat(incident.lat) : null;
    const lng = incident.lng != null ? parseFloat(incident.lng) : null;

    const focusMarker = () => {
        const markerRef =
            (incidentId && citizenIncidentMarkerRefs.get(`incident:${incidentId}`)) ||
            (markerId && citizenIncidentMarkerRefs.get(`marker:${markerId}`));

        if (markerRef && window.usermap) {
            const markerLatLng = markerRef.getLatLng?.();
            if (markerLatLng) {
                window.usermap.setView(markerLatLng, Math.max(window.usermap.getZoom() || 15, 16), {
                    animate: true
                });
            }
            setTimeout(() => {
                if (isCitizenMobileViewport() && markerRef.citizenPreviewData?.popupContent) {
                    openCitizenMapPreviewSheet(markerRef.citizenPreviewData.popupContent, markerRef.citizenPreviewData);
                    return;
                }
                markerRef.openPopup?.();
            }, 220);
            return true;
        }

        if (window.usermap && Number.isFinite(lat) && Number.isFinite(lng)) {
            window.usermap.setView([lat, lng], Math.max(window.usermap.getZoom() || 15, 16), {
                animate: true
            });
            return true;
        }

        return false;
    };

    if (focusMarker()) {
        return;
    }

    setTimeout(() => {
        focusMarker();
    }, 500);
};

// Function to show marker details
function legacyShowMarkerDetailsAlert(marker, incident) {
    const markerPresentation = getCitizenMarkerPresentation(marker);
    const fallbackMessage = [
        `Marker: ${marker.title || 'Marker'}`,
        '--------------------',
        `Type: ${markerPresentation.label}`,
        `Description: ${marker.description || 'No description'}`,
        ...(incident ? [`Status: ${incident.status || 'Reported'}`] : []),
        `Location: ${marker.lat}, ${marker.lng}`
    ].join('\n');

    alert(fallbackMessage);
    return;

    const details = {
        title: marker.title || 'Marker',
        description: marker.description || 'No description',
        type: marker.markerCategory_id == 1 ? '🚒 Fire Station' : 
               marker.markerCategory_id == 2 ? '💧 Fire Hydrant' :
               marker.markerCategory_id == 3 ? '🔥 Incident' : '📍 Marker',
        coordinates: `${marker.lat}, ${marker.lng}`,
        incident_type: incident ? getIncidentTypeName(incident.incident_type_id) : null,
        status: incident ? incident.status : null
    };
    
    let message = `📍 ${details.title}\n`;
    message += `━━━━━━━━━━━━━━━━━━━━\n`;
    message += `Type: ${details.type}\n`;
    message += `Description: ${details.description}\n`;
    if (details.incident_type) {
        message += `Incident Type: ${details.incident_type}\n`;
        message += `Status: ${details.status}\n`;
    }
    message += `Location: ${details.coordinates}`;
    
    alert(message);
}

// Function to show incident details
function legacyShowIncidentDetailsAlert(incident) {
    const fallbackIncidentTypeName = getCitizenMarkerPresentation(incident?.marker || incident?.category || 'fire').label;
    const fallbackDate = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
    const fallbackDescription = incident.marker && incident.marker.description
        ? incident.marker.description
        : 'If you see this incident, stay safe and follow instructions from authorities.';

    alert([
        'INCIDENT DETAILS',
        '--------------------',
        `Type: ${fallbackIncidentTypeName}`,
        `Status: ${incident.status || 'Reported'}`,
        `Reported: ${fallbackDate}`,
        `Description: ${fallbackDescription}`
    ].join('\n'));
    return;

    const incidentTypeName = getIncidentTypeName(incident.incident_type_id);
    const date = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
    
    let message = `🔥 INCIDENT DETAILS\n`;
    message += `━━━━━━━━━━━━━━━━━━━━\n`;
    message += `Type: ${incidentTypeName}\n`;
    message += `Status: ${incident.status || 'Reported'}\n`;
    message += `Reported: ${date}\n`;
    if (incident.marker && incident.marker.description) {
        message += `Description: ${incident.marker.description}\n`;
    }
    message += `━━━━━━━━━━━━━━━━━━━━\n`;
    message += `If you see this incident, stay safe and follow instructions from authorities.`;
    
    alert(message);
}

function escapeCitizenModalHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function ensureCitizenMapDetailsModal() {
    let modal = document.getElementById('citizenMapDetailsModal');
    if (modal) {
        return modal;
    }

    const modalHost = document.getElementById('citizenMapDetailsHost') || document.body;

    modal = document.createElement('div');
    modal.id = 'citizenMapDetailsModal';
    modal.className = 'citizen-map-modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="citizen-map-modal-card" role="dialog" aria-modal="false" aria-labelledby="citizenMapDetailsModalTitle">
            <div class="citizen-map-modal-header">
                <div>
                    <p class="citizen-map-modal-kicker">Map Details</p>
                    <h2 id="citizenMapDetailsModalTitle" class="citizen-map-modal-title">Details</h2>
                </div>
                <button type="button" id="citizenMapDetailsModalClose" class="citizen-map-modal-close" aria-label="Close details modal">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="citizenMapDetailsModalBody" class="citizen-map-modal-body"></div>
        </div>
    `;

    modalHost.appendChild(modal);

    document.getElementById('citizenMapDetailsModalClose')?.addEventListener('click', closeCitizenMapDetailsModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeCitizenMapDetailsModal();
        }
    });

    return modal;
}

function openCitizenMapDetailsModal(title, content) {
    const modal = ensureCitizenMapDetailsModal();
    const modalHost = document.getElementById('citizenMapDetailsHost');
    const titleElement = document.getElementById('citizenMapDetailsModalTitle');
    const bodyElement = document.getElementById('citizenMapDetailsModalBody');

    closeCitizenMapPreviewSheet();

    if (titleElement) {
        titleElement.textContent = title;
    }

    if (bodyElement) {
        bodyElement.innerHTML = content;
    }

    modal.classList.add('show');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    modalHost?.classList.add('show');
}

function closeCitizenMapDetailsModal() {
    const modal = document.getElementById('citizenMapDetailsModal');
    const modalHost = document.getElementById('citizenMapDetailsHost');
    if (!modal) {
        return;
    }

    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modalHost?.classList.remove('show');
}

function bindCitizenMapPreviewSheetInteractions(sheet) {
    if (!sheet || sheet.dataset.dragBound === 'true') {
        return;
    }

    const card = sheet.querySelector('.citizen-map-preview-sheet__card');
    const handle = sheet.querySelector('.citizen-map-preview-sheet__handle');
    if (!card || !handle) {
        return;
    }

    let startY = 0;
    let currentOffset = 0;
    let dragging = false;

    const setOffset = (offset) => {
        currentOffset = Math.max(0, offset);
        card.style.transform = `translateY(${currentOffset}px)`;
    };

    const resetOffset = () => {
        card.style.transition = 'transform 0.22s ease';
        setOffset(0);
        window.setTimeout(() => {
            card.style.transition = '';
        }, 220);
    };

    const closeFromDrag = () => {
        card.style.transition = 'transform 0.18s ease';
        setOffset(Math.max(window.innerHeight * 0.18, 160));
        window.setTimeout(() => {
            closeCitizenMapPreviewSheet();
            card.style.transition = '';
            card.style.transform = '';
        }, 170);
    };

    const beginDrag = (clientY) => {
        if (!isCitizenMobileViewport()) {
            return;
        }

        dragging = true;
        startY = clientY;
        currentOffset = 0;
        card.style.transition = 'none';
    };

    const updateDrag = (clientY) => {
        if (!dragging) {
            return;
        }

        setOffset(clientY - startY);
    };

    const endDrag = () => {
        if (!dragging) {
            return;
        }

        dragging = false;
        if (currentOffset > 90) {
            closeFromDrag();
            return;
        }

        resetOffset();
    };

    handle.addEventListener('pointerdown', (event) => {
        beginDrag(event.clientY);
        handle.setPointerCapture?.(event.pointerId);
    });

    handle.addEventListener('pointermove', (event) => {
        updateDrag(event.clientY);
    });

    handle.addEventListener('pointerup', endDrag);
    handle.addEventListener('pointercancel', endDrag);

    handle.addEventListener('touchstart', (event) => {
        beginDrag(event.touches[0].clientY);
    }, { passive: true });

    handle.addEventListener('touchmove', (event) => {
        updateDrag(event.touches[0].clientY);
    }, { passive: true });

    handle.addEventListener('touchend', endDrag, { passive: true });

    sheet.dataset.dragBound = 'true';
}

function ensureCitizenMapPreviewSheet() {
    let sheet = document.getElementById('citizenMapPreviewSheet');
    if (sheet) {
        return sheet;
    }

    sheet = document.createElement('div');
    sheet.id = 'citizenMapPreviewSheet';
    sheet.className = 'citizen-map-preview-sheet';
    sheet.setAttribute('aria-hidden', 'true');
    sheet.innerHTML = `
        <div class="citizen-map-preview-sheet__backdrop" data-citizen-map-preview-close></div>
        <div class="citizen-map-preview-sheet__card" role="dialog" aria-modal="false" aria-label="Map marker preview">
            <button type="button" id="citizenMapPreviewSheetClose" class="citizen-map-preview-sheet__close" aria-label="Close preview">
                <i class="fas fa-xmark"></i>
            </button>
            <div class="citizen-map-preview-sheet__handle" aria-hidden="true"></div>
            <div id="citizenMapPreviewSheetBody" class="citizen-map-preview-sheet__body"></div>
        </div>
    `;

    document.body.appendChild(sheet);
    document.getElementById('citizenMapPreviewSheetClose')?.addEventListener('click', closeCitizenMapPreviewSheet);
    sheet.querySelector('[data-citizen-map-preview-close]')?.addEventListener('click', closeCitizenMapPreviewSheet);
    bindCitizenMapPreviewSheetInteractions(sheet);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet.classList.contains('show')) {
            closeCitizenMapPreviewSheet();
        }
    });

    return sheet;
}

function openCitizenMapPreviewSheet(content, markerContext = null) {
    if (!isCitizenMobileViewport()) {
        return;
    }

    closeCitizenMapDetailsModal();

    const sheet = ensureCitizenMapPreviewSheet();
    const body = document.getElementById('citizenMapPreviewSheetBody');
    citizenMapPreviewPreviousFocus = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;

    if (body) {
        body.innerHTML = content;
    }

    sheet.classList.add('show');
    sheet.style.display = 'block';
    sheet.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

}

function closeCitizenMapPreviewSheet() {
    const sheet = document.getElementById('citizenMapPreviewSheet');
    if (!sheet) {
        return;
    }

    if (sheet.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    sheet.classList.remove('show');
    sheet.style.display = 'none';
    sheet.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    if (citizenMapPreviewPreviousFocus && document.contains(citizenMapPreviewPreviousFocus)) {
        citizenMapPreviewPreviousFocus.focus({ preventScroll: true });
    }
    citizenMapPreviewPreviousFocus = null;
}

function suppressCitizenMobilePopup(marker) {
    if (!marker) {
        return;
    }

    if (isCitizenMobileViewport()) {
        marker.closePopup?.();
    }
}

function buildCitizenMapInfoRows(rows = []) {
    return rows.map((row) => `
        <div class="citizen-map-detail-row">
            <span class="citizen-map-detail-label">${escapeCitizenModalHtml(row.label)}</span>
            <span class="citizen-map-detail-value">${escapeCitizenModalHtml(row.value)}</span>
        </div>
    `).join('');
}

function showMarkerDetails(marker, incident) {
    const typeLabel = getCitizenMarkerPresentation(marker).label;

    const infoRows = [
        { label: 'Type', value: typeLabel },
        { label: 'Description', value: marker.description || 'No description' },
        { label: 'Location', value: `${marker.lat}, ${marker.lng}` }
    ];

    if (incident) {
        infoRows.splice(2, 0,
            { label: 'Status', value: incident.status || 'Reported' }
        );
    }

    openCitizenMapDetailsModal(
        marker.title || 'Marker Details',
        `
            <div class="citizen-map-detail-panel">
                <div class="citizen-map-detail-badge">
                    <i class="fas fa-circle-info"></i>
                    Location Summary
                </div>
                <div class="citizen-map-detail-grid">
                    ${buildCitizenMapInfoRows(infoRows)}
                </div>
            </div>
        `
    );
}

function showIncidentDetails(incident) {
    const incidentTypeName = getCitizenMarkerPresentation(incident?.marker || incident?.category || 'fire').label;
    const date = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
    const description = incident.marker && incident.marker.description
        ? incident.marker.description
        : 'If you see this incident, stay safe and follow instructions from authorities.';

    openCitizenMapDetailsModal(
        'Incident Details',
        `
            <div class="citizen-map-detail-panel">
                <div class="citizen-map-detail-badge danger">
                    <i class="fas fa-fire"></i>
                    Active Incident
                </div>
                <div class="citizen-map-detail-grid">
                    ${buildCitizenMapInfoRows([
                        { label: 'Type', value: incidentTypeName },
                        { label: 'Status', value: incident.status || 'Reported' },
                        { label: 'Reported', value: date },
                        { label: 'Description', value: description }
                    ])}
                </div>
            </div>
        `
    );
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ping {
        75%, 100% {
            transform: scale(2);
            opacity: 0;
        }
    }

    @keyframes citizenPanelShimmer {
        0% {
            transform: translateX(-140%);
        }
        100% {
            transform: translateX(140%);
        }
    }
    
    .citizen-marker-leaflet-popup .leaflet-popup-content-wrapper {
        border-radius: 16px;
        padding: 0;
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.24);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
        overflow: hidden;
    }
    
    .citizen-marker-leaflet-popup .leaflet-popup-content {
        margin: 0;
        width: min(330px, calc(100vw - 32px)) !important;
        max-height: 55vh;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .citizen-marker-leaflet-popup .leaflet-popup-tip {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: none;
    }

    .citizen-marker-leaflet-popup .leaflet-popup-close-button {
        width: 32px;
        height: 32px;
        line-height: 31px;
        color: #64748b;
        font-size: 18px;
        font-weight: 700;
    }

    .citizen-marker-popup {
        width: min(330px, calc(100vw - 32px));
        background: #ffffff;
        color: #1f2937;
        border-top: 4px solid var(--popup-accent, #c63d26);
    }

    .citizen-marker-popup-header {
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
        padding: 16px 16px 12px;
    }

    .citizen-marker-popup-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--popup-accent) 14%, white);
        color: var(--popup-accent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--popup-accent) 20%, white);
        font-size: 16px;
    }

    .citizen-marker-popup-heading {
        min-width: 0;
        display: grid;
        gap: 7px;
        padding-right: 20px;
    }

    .citizen-marker-popup-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .citizen-marker-popup-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 7px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.12);
        color: #64748b;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .citizen-marker-popup-chip.status i {
        font-size: 7px;
    }

    .citizen-marker-popup-chip.status.ok {
        background: #dcfce7;
        color: #15803d;
    }

    .citizen-marker-popup-chip.status.warning {
        background: #fef3c7;
        color: #a16207;
    }

    .citizen-marker-popup-chip.status.danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .citizen-marker-popup-chip.status.neutral {
        background: #e5e7eb;
        color: #4b5563;
    }

    .citizen-marker-popup-title {
        display: block;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.25;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .citizen-marker-popup-subtitle {
        margin: 0 16px 14px;
        font-size: 12px;
        line-height: 1.45;
        color: #475569;
        overflow-wrap: anywhere;
    }

    .citizen-marker-popup-media {
        margin: 0 16px 14px;
        height: 120px;
        border-radius: 12px;
        overflow: hidden;
        background: #f1f5f9;
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .citizen-marker-popup-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .citizen-marker-popup-details {
        display: grid;
        grid-template-columns: 92px minmax(0, 1fr);
        gap: 8px 12px;
        padding: 14px 16px 16px;
        border-top: 1px solid rgba(148, 163, 184, 0.16);
    }

    .citizen-marker-popup-detail-row {
        display: contents;
    }

    .citizen-marker-popup-detail-label {
        min-width: 0;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
    }

    .citizen-marker-popup-detail-value {
        font-size: 13px;
        line-height: 1.35;
        font-weight: 700;
        color: #334155;
        overflow-wrap: anywhere;
    }

    .citizen-marker-popup-detail-value a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 800;
    }

    .citizen-marker-popup-actions {
        display: grid;
        gap: 8px;
        padding: 0 16px 16px;
    }

    .citizen-marker-popup-btn {
        width: 100%;
        border: 1px solid rgba(37, 99, 235, 0.18);
        border-radius: 12px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.16s ease, color 0.16s ease;
    }

    .citizen-marker-popup-btn:hover {
        text-decoration: none;
    }

    .citizen-marker-popup-btn.primary {
        color: #fff;
        background: #2563eb;
        box-shadow: none;
    }

    .citizen-marker-popup-btn.secondary {
        color: #334155;
        background: #e2e8f0;
    }

    .citizen-marker-popup-btn.secondary:hover {
        background: #cbd5e1;
    }

    .citizen-map-shell {
        position: relative;
        padding: 0 20px 20px;
    }

    .citizen-map-details-host {
        position: absolute;
        top: 14px;
        right: 14px;
        width: min(290px, calc(100% - 28px));
        z-index: 500;
        pointer-events: none;
    }

    .citizen-map-details-host.show {
        pointer-events: auto;
    }

    .citizen-map-modal {
        position: relative;
        display: none;
        width: 100%;
        pointer-events: none;
    }

    .citizen-map-modal.show {
        display: block;
    }

    .citizen-map-preview-sheet {
        display: none;
        width: 100%;
        pointer-events: none;
        --citizen-preview-nav-offset: var(--mobile-nav-height, var(--mobile-bottom-nav-h, 0px));
    }

    .citizen-map-preview-sheet.show {
        display: block;
    }

    .citizen-map-preview-sheet__backdrop {
        position: absolute;
        inset: 0;
        background: transparent;
        backdrop-filter: none;
        pointer-events: none;
    }

    .citizen-map-preview-sheet__card {
        position: absolute;
        inset: auto 0 0;
        width: min(100%, 420px);
        margin: 0 auto;
        background: linear-gradient(180deg, rgba(255,255,255,0.985) 0%, rgba(255,250,244,0.98) 100%);
        backdrop-filter: blur(16px);
        border-radius: 24px 24px 0 0;
        border: 1px solid rgba(198, 61, 38, 0.12);
        box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        pointer-events: auto;
        transform: translateY(18px);
        transition: transform 0.22s ease;
    }

    .citizen-map-preview-sheet.show .citizen-map-preview-sheet__card {
        transform: translateY(0);
    }

    .citizen-map-preview-sheet__handle {
        width: 42px;
        height: 4px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.5);
        margin: 10px auto 10px;
        touch-action: none;
        cursor: grab;
    }

    .citizen-map-preview-sheet__close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 14px;
        background: rgba(248, 250, 252, 0.96);
        color: #334155;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.22);
        z-index: 2;
    }

    .citizen-map-preview-sheet__body {
        padding: 0;
        min-height: 0;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup {
        border-radius: 0;
        box-shadow: none;
        border: none;
        background: transparent;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-topbar {
        display: none;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-header {
        padding: 4px 64px 12px 16px;
        align-items: flex-start;
        gap: 12px;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-chip-row {
        gap: 6px;
        margin-bottom: 6px;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-chip {
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 10px;
        letter-spacing: 0.08em;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-title {
        font-size: 17px;
        line-height: 1.25;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-subtitle {
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-media {
        margin: 0 16px;
        height: 142px;
        border-radius: 18px;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-details {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
        padding: 14px 16px 0;
        border-top: 1px solid rgba(226, 232, 240, 0.9);
        margin-top: 14px;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-detail-row {
        padding: 12px 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        border: 0;
        border-bottom: 1px solid rgba(226, 232, 240, 0.82);
        display: grid;
        grid-template-columns: minmax(82px, 92px) minmax(0, 1fr);
        gap: 10px;
        align-items: start;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-detail-label {
        font-size: 9px;
        letter-spacing: 0.14em;
        color: #94a3b8;
        padding-top: 2px;
        white-space: nowrap;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-detail-value {
        font-size: 12px;
        color: #334155;
        min-width: 0;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-actions {
        padding: 12px 16px 14px;
    }

    .citizen-map-preview-sheet__body .citizen-marker-popup-btn {
        min-height: 46px;
        border-radius: 16px;
        font-size: 13px;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .citizen-map-modal-card {
        width: 100%;
        position: relative;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 250, 244, 0.95) 100%);
        backdrop-filter: blur(14px);
        border-radius: 22px;
        border: 1px solid rgba(198, 61, 38, 0.14);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        max-height: calc(380px - 28px);
        overflow-y: auto;
        pointer-events: auto;
    }

    .citizen-map-modal-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 -30%;
        width: 42%;
        background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.08) 45%, rgba(255, 255, 255, 0.45) 50%, rgba(255, 255, 255, 0.08) 55%, transparent 100%);
        transform: translateX(-140%);
        animation: citizenPanelShimmer 4.8s ease-in-out infinite;
        pointer-events: none;
    }

    .citizen-map-modal-header {
        position: relative;
        padding: 14px 16px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        color: #fff;
    }

    .citizen-map-modal-kicker {
        margin: 0 0 4px;
        font-size: 9px;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        opacity: 0.76;
    }

    .citizen-map-modal-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.25;
    }

    .citizen-map-modal-close {
        width: 34px;
        height: 34px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        cursor: pointer;
        transition: transform 0.2s ease, background 0.2s ease;
    }

    .citizen-map-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    .citizen-map-modal-body {
        padding: 14px;
    }

    .citizen-map-detail-panel {
        display: grid;
        gap: 10px;
    }

    .citizen-map-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        padding: 7px 10px;
        border-radius: 9999px;
        background: rgba(17, 24, 39, 0.06);
        color: #a9311d;
        font-size: 11px;
        font-weight: 700;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
    }

    .citizen-map-detail-badge.danger {
        background: rgba(198, 61, 38, 0.12);
        color: #b91c1c;
    }

    .citizen-map-detail-grid {
        display: grid;
        gap: 8px;
    }

    .citizen-map-detail-row {
        display: grid;
        grid-template-columns: minmax(0, 92px) minmax(0, 1fr);
        align-items: start;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .citizen-map-detail-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #94a3b8;
        padding-top: 2px;
    }

    .citizen-map-detail-value {
        font-size: 12.5px;
        line-height: 1.45;
        color: #1f2937;
        word-break: break-word;
        font-weight: 600;
    }

    @media (max-width: 1023px) {
        .citizen-map-shell {
            display: grid;
            gap: 16px;
            padding: 0 16px 16px;
        }

        .citizen-map-details-host {
            position: static;
            width: 100%;
            pointer-events: auto;
        }

        .citizen-map-modal-card {
            max-height: none;
        }
    }

    @media (max-width: 768px) {
        .leaflet-popup,
        .leaflet-popup-pane {
            display: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .citizen-map-modal {
            display: none !important;
        }

        .citizen-map-preview-sheet {
            position: fixed;
            inset: 0;
            z-index: 320;
            pointer-events: none;
        }

        .citizen-map-preview-sheet.show {
            pointer-events: none;
        }

        .citizen-map-preview-sheet__card {
            inset: auto 0 calc(var(--citizen-preview-nav-offset) + env(safe-area-inset-bottom, 0px)) 0;
            width: 100vw;
            max-width: 100vw;
            max-height: min(56vh, calc(60vh - var(--citizen-preview-nav-offset)), 430px);
            display: flex;
            flex-direction: column;
            height: auto;
            overflow: hidden;
            padding-bottom: 0;
            border-radius: 24px 24px 0 0;
        }

        .citizen-map-preview-sheet__body {
            flex: 0 1 auto;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-details {
            display: grid;
            grid-template-columns: 1fr;
            padding: 10px 16px 0;
            margin-top: 10px;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-header {
            padding-top: 2px;
            padding-bottom: 10px;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup {
            padding-bottom: max(10px, env(safe-area-inset-bottom));
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-subtitle {
            margin: 0 16px 10px;
            -webkit-line-clamp: 2;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-media {
            height: 104px;
            margin-left: 16px;
            margin-right: 16px;
            margin-top: 2px;
            margin-bottom: 10px;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.92) 0%, rgba(241, 245, 249, 0.96) 100%);
            border: 1px solid rgba(226, 232, 240, 0.82);
            box-shadow: none;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-detail-row {
            grid-template-columns: minmax(82px, 92px) minmax(0, 1fr);
            gap: 10px;
            padding: 10px 0;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-detail-row:last-child {
            border-bottom: 0;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-detail-label {
            font-size: 9px;
            white-space: nowrap;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-detail-value {
            font-size: 12px;
            min-width: 0;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-chip {
            background: #f8fafc;
            color: #64748b;
            box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.9);
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-chip.status {
            background: #fff7ed;
            color: #c2410c;
            box-shadow: inset 0 0 0 1px rgba(251, 146, 60, 0.14);
        }

        .citizen-map-preview-sheet__body .citizen-marker-popup-actions {
            position: sticky;
            bottom: 0;
            margin-top: 0;
            padding: 10px 16px 12px;
            background: linear-gradient(180deg, rgba(255,250,244,0) 0%, rgba(255,250,244,0.96) 24%, rgba(255,250,244,1) 100%);
        }
    }
`;
document.head.appendChild(style);

// Add these global variables at the top of user.js (after existing variables)

// Route and simulation variables for user map
// let userRouteLayer = null;
// let userRemainingRouteLayer = null;
// let userActiveRoute = null;
// let userSimulationPoints = [];
// let userOriginalSimulationPoints = [];
// let userTraveledPoints = [];
// let userRemainingPoints = [];
// let userCurrentPointIndex = 0;
// let userIsSimulating = false;
// let userAnimatedVehicle = null;
// let userAnimationFrame = null;
// let userAnimationStartTime = null;
// let userAnimationDuration = null;
// let userCurrentAnimationPoint = 0;
// let userCurrentDestinationType = null;
// let userRouteDestination = null;

// // Multi-truck support for user map
// let userActiveTrucks = new Map(); // Stores each truck separately by route_id

// // Route polling interval for user map
// let userRoutePollingInterval = null;

// ============================================================================
// ROUTE DATABASE FUNCTIONS FOR USER
// ============================================================================

async function userFetchActiveRoutesForIncident(incidentId) {
    try {
        const response = await fetch(`/api/route/active/${incidentId}`);
        if (!response.ok) return [];
        
        const routes = await response.json();
        console.log(`Found active routes for incident ${incidentId}:`, routes.length);
        return routes;
    } catch (error) {
        console.error('Failed to fetch active routes for incident:', error);
        return [];
    }
}

// ============================================================================
// ROUTE DISPLAY FUNCTIONS FOR USER MAP
// ============================================================================

function userDisplayGeoapifyRoute(route, destination) {
    if (userRouteLayer) {
        window.usermap.removeLayer(userRouteLayer);
    }
    
    let latlngs = [];
    let coordinates;
    
    // Handle database format (string with triple brackets)
    if (typeof route === 'object' && route.coordinates) {
        if (typeof route.coordinates === 'string') {
            try {
                coordinates = JSON.parse(route.coordinates);
            } catch (e) {
                console.error('Failed to parse coordinates:', e);
                return;
            }
        } else {
            coordinates = route.coordinates;
        }
    } else if (route.geometry) {
        coordinates = route.geometry.coordinates;
    } else {
        console.error('Unknown route format:', route);
        return;
    }
    
    // Handle triple-nested array from database
    if (Array.isArray(coordinates) && coordinates.length > 0) {
        if (Array.isArray(coordinates[0]) && Array.isArray(coordinates[0][0])) {
            coordinates = coordinates[0];
            console.log('Flattened triple-nested coordinates, new length:', coordinates.length);
        }
    }
    
    // Convert [lng, lat] to [lat, lng] for Leaflet
    for (let i = 0; i < coordinates.length; i++) {
        const coord = coordinates[i];
        if (Array.isArray(coord) && coord.length >= 2) {
            latlngs.push([coord[1], coord[0]]);
        }
    }
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found in route');
        return;
    }
    
    // Store for simulation
    userOriginalSimulationPoints = [...latlngs];
    userRemainingPoints = [...latlngs];
    userSimulationPoints = latlngs;
    
    // Draw the route line
    userRouteLayer = L.polyline(latlngs, {
        color: '#3b82f6',
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round',
        lineCap: 'round'
    }).addTo(window.usermap);
    
    // Fit map to route bounds
    const bounds = userRouteLayer.getBounds();
    if (bounds.isValid()) {
        window.usermap.fitBounds(bounds, { padding: [50, 50] });
    }
    
    // Clear old markers
    userClearRouteMarkers();
    
    // Add start marker
    if (latlngs[0]) {
        const startMarker = L.marker(latlngs[0], {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #22c55e;"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).bindPopup('<strong>Start Point</strong><br>Route origin').addTo(window.usermap);
        userRouteMarkers.push(startMarker);
    }
    
    // Add end marker
    if (latlngs[latlngs.length - 1]) {
        const endMarker = L.marker(latlngs[latlngs.length - 1], {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #ef4444;"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).bindPopup(`<strong>Destination</strong><br>${destination?.title || 'Target Location'}`).addTo(window.usermap);
        userRouteMarkers.push(endMarker);
    }
    
    userCurrentPointIndex = 0;
    
    console.log('User route displayed successfully!');
}

let userRouteMarkers = [];

function userClearRouteMarkers() {
    userRouteMarkers.forEach(marker => {
        if (window.usermap) window.usermap.removeLayer(marker);
    });
    userRouteMarkers = [];
}

function calculateDistanceBetweenPointsUser(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// ============================================================================
// MULTI-TRUCK SUPPORT FOR USER MAP
// ============================================================================

async function userDisplayAndStartRoute(routeId, route, destination, incidentId) {
    routeId = normalizeUserRouteId(routeId);
    console.log('Displaying user route data:', route);
    userClearRouteRendering(routeId);
    userSetRoutePanelLoading(true, 'Rendering active emergency route...');
    
    // Extract coordinates
    let coordinates = null;
    
    if (typeof route.coordinates === 'string') {
        try {
            coordinates = JSON.parse(route.coordinates);
        } catch (e) {
            console.error('Failed to parse coordinates string:', e);
            return;
        }
    } else if (Array.isArray(route.coordinates)) {
        coordinates = route.coordinates;
    } else if (route.geometry && route.geometry.coordinates) {
        coordinates = route.geometry.coordinates;
    }
    
    if (!coordinates) {
        console.error('No coordinates found in route');
        return;
    }
    
    // Handle triple-nested array
    let flattenedCoords = coordinates;
    if (Array.isArray(coordinates) && coordinates.length > 0) {
        if (Array.isArray(coordinates[0]) && coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
            flattenedCoords = coordinates[0];
        }
    }
    
    // Convert [lng, lat] to [lat, lng] for Leaflet
    const latlngs = [];
    for (let i = 0; i < flattenedCoords.length; i++) {
        const coord = flattenedCoords[i];
        if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
            latlngs.push([coord[1], coord[0]]);
        }
    }
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found after parsing');
        return;
    }
    
    console.log(`User route ${routeId} has ${latlngs.length} points`);
    
    const truckData = {
        routeId: routeId,
        simulationPoints: latlngs,
        originalSimulationPoints: [...latlngs],
        currentPointIndex: route.current_position_index || 0,
        isSimulating: route.status === 'moving',
        routeDestination: destination,
        incidentId: incidentId,
        routeSource: route.route_source || 'real',
        routeLayer: null,
        traveledLayer: null,
        animatedVehicle: null,
        animationFrame: null,
        awaitingServerCompletion: false
    };
    
    userActiveTrucks.set(routeId, truckData);
    const truckColor = getUserTruckColor(routeId);
    
    // Draw the route line
    truckData.routeLayer = userRegisterRouteLayer(routeId, L.polyline(latlngs, {
        color: truckColor,
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round',
        lineCap: 'round'
    }).addTo(window.usermap));
    
    // Add start and end markers
    if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
        userRegisterRouteLayer(routeId, L.marker(latlngs[0], {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup('Start Point').addTo(window.usermap));
    }
    
    if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
        userRegisterRouteLayer(routeId, L.marker(latlngs[latlngs.length - 1], {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup(`Destination: ${destination?.title || 'Incident Location'}`).addTo(window.usermap));
    }
    
    // Demo routes animate for presentation. Live routes only move when the
    // responder publishes a new GPS coordinate to the server.
    if (String(route.route_source || 'real').toLowerCase() !== 'demo') {
        userUpdateLiveTruckPosition(routeId, route);
    } else if (route.status === 'moving' && route.current_position_index > 0) {
        console.log('Resuming moving route from position:', route.current_position_index);
        await userStartSimulationFromPosition(routeId, route.current_position_index);
    } else if (route.status === 'active' || route.status === 'moving') {
        console.log('Starting simulation for user route:', routeId);
        await userStartSimulationForRoute(routeId);
    }
    
    userRoutePanelLoading = false;
    userUpdateRouteInfoCard();
}

async function userStartSimulationForRoute(routeId) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck || !truck.simulationPoints || truck.simulationPoints.length === 0) {
        console.error('No route points available for simulation');
        return;
    }
    
    console.log('Starting simulation for user route:', routeId);
    
    const startPoint = truck.simulationPoints[0];
    if (!startPoint || !startPoint[0] || !startPoint[1]) {
        console.error('Invalid start point coordinates:', startPoint);
        return;
    }
    
    truck.isSimulating = true;
    truck.currentPointIndex = 0;

    userRemoveAllTruckMarkers(routeId);
    
    if (truck.animatedVehicle) {
        userRemoveRegisteredRouteLayer(routeId, truck.animatedVehicle);
    }
    
    truck.animatedVehicle = userRegisterRouteLayer(routeId, L.marker([startPoint[0], startPoint[1]], {
        icon: userCreateFireTruckIcon(),
        zIndexOffset: 1000
    }).addTo(window.usermap));
    userBindTruckIncidentPopup(routeId, truck.animatedVehicle, truck);
    if (truck.simulationPoints[1]) {
        const nextPoint = truck.simulationPoints[1];
        const initialAngle = Math.atan2(nextPoint[1] - startPoint[1], nextPoint[0] - startPoint[0]) * 180 / Math.PI;
        setTimeout(() => applyTruckHeading(truck.animatedVehicle, initialAngle), 0);
    }
    
    if (truck.traveledLayer) {
        userRemoveRegisteredRouteLayer(routeId, truck.traveledLayer);
    }
    truck.traveledLayer = userRegisterRouteLayer(routeId, L.polyline([], {
        color: '#9ca3af',
        weight: 4,
        opacity: 0.6
    }).addTo(window.usermap));
    
    userStartSmoothAnimationForTruck(routeId);
    
    // Show notification to user
    userShowRouteNotification(`Emergency vehicle dispatched to incident!`, 'info');
}

async function userStartSimulationFromPosition(routeId, startIndex) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck) return;
    
    if (startIndex >= truck.simulationPoints.length) {
        startIndex = truck.simulationPoints.length - 1;
    }
    if (startIndex < 0) startIndex = 0;
    
    const startPoint = truck.simulationPoints[startIndex];
    if (!startPoint || !startPoint[0] || !startPoint[1]) {
        console.error('Invalid start point for route:', routeId);
        return;
    }
    
    truck.currentPointIndex = startIndex;
    truck.isSimulating = true;

    userRemoveAllTruckMarkers(routeId);
    
    if (truck.animatedVehicle) {
        userRemoveRegisteredRouteLayer(routeId, truck.animatedVehicle);
    }
    
    truck.animatedVehicle = userRegisterRouteLayer(routeId, L.marker([startPoint[0], startPoint[1]], {
        icon: userCreateFireTruckIcon(),
        zIndexOffset: 1000
    }).addTo(window.usermap));
    userBindTruckIncidentPopup(routeId, truck.animatedVehicle, truck);
    if (truck.simulationPoints[startIndex + 1]) {
        const nextPoint = truck.simulationPoints[startIndex + 1];
        const initialAngle = Math.atan2(nextPoint[1] - startPoint[1], nextPoint[0] - startPoint[0]) * 180 / Math.PI;
        setTimeout(() => applyTruckHeading(truck.animatedVehicle, initialAngle), 0);
    }
    
    // Create traveled path from beginning to current position
    const traveledPoints = [];
    for (let i = 0; i <= startIndex; i++) {
        if (truck.simulationPoints[i]) {
            traveledPoints.push(truck.simulationPoints[i]);
        }
    }
    
    if (truck.traveledLayer) {
        userRemoveRegisteredRouteLayer(routeId, truck.traveledLayer);
    }
    if (traveledPoints.length > 1) {
        truck.traveledLayer = userRegisterRouteLayer(routeId, L.polyline(traveledPoints, {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6
        }).addTo(window.usermap));
    } else {
        truck.traveledLayer = userRegisterRouteLayer(routeId, L.polyline([], {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6
        }).addTo(window.usermap));
    }
    
    userStartSmoothAnimationForTruck(routeId);
    console.log(`Resumed user truck ${routeId} from position ${startIndex}`);
}

function userStartSmoothAnimationForTruck(routeId) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck) {
        console.error('No truck found for route:', routeId);
        return;
    }
    
    if (!truck.simulationPoints || truck.simulationPoints.length < 2) {
        console.error('Not enough points for animation. Points:', truck.simulationPoints?.length);
        return;
    }
    
    const startIndex = truck.currentPointIndex;
    console.log('Starting smooth animation for user truck:', routeId);
    console.log('Starting from point index:', startIndex);
    
    // Stop any existing animation
    if (truck.animationFrame) {
        cancelAnimationFrame(truck.animationFrame);
        truck.animationFrame = null;
    }
    
    // If already at destination
    if (startIndex >= truck.simulationPoints.length - 1) {
        console.log('Truck already at destination');
        userHoldTruckAtDestination(routeId);
        userShowRouteNotification(`Emergency vehicle has arrived at the incident location!`, 'success');
        return;
    }
    
    // Build segments from current position
    const segments = [];
    let totalRemainingDistance = 0;
    
    for (let i = startIndex; i < truck.simulationPoints.length - 1; i++) {
        const p1 = truck.simulationPoints[i];
        const p2 = truck.simulationPoints[i + 1];
        
        if (!p1 || !p2) continue;
        
        const distance = calculateDistanceBetweenPointsUser(p1[0], p1[1], p2[0], p2[1]);
        totalRemainingDistance += distance;
        segments.push({
            start: p1,
            end: p2,
            distance: distance,
            cumulativeDistance: totalRemainingDistance,
            angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
        });
    }
    
    if (segments.length === 0) return;
    
    const baseSpeed = 13.89; // 50 km/h in m/s
    const totalRemainingDuration = totalRemainingDistance / baseSpeed;
    const startTime = performance.now();
    let lastPositionIndex = startIndex;
    
    console.log(`Animation will run for ${totalRemainingDuration.toFixed(0)} seconds`);
    
    const animate = async (currentTime) => {
        if (!userActiveTrucks.has(routeId)) {
            console.log('Truck removed, stopping animation');
            return;
        }
        
        const currentTruck = userActiveTrucks.get(routeId);
        if (!currentTruck.animatedVehicle) return;
        
        const elapsed = (currentTime - startTime) / 1000;
        let progress = Math.min(elapsed / totalRemainingDuration, 1);
        
        if (progress >= 1) {
            console.log(`User truck ${routeId} arrived at destination!`);
            userHoldTruckAtDestination(routeId);
            userShowRouteNotification(`Emergency vehicle has arrived at the incident location!`, 'success');
            return;
        }
        
        const distanceTraveled = progress * totalRemainingDistance;
        
        let currentSegment = null;
        let segmentStartDistance = 0;
        
        for (let i = 0; i < segments.length; i++) {
            if (distanceTraveled <= segments[i].cumulativeDistance) {
                currentSegment = segments[i];
                segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
                break;
            }
        }
        
        if (currentSegment && currentTruck.animatedVehicle) {
            const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
            
            const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
            const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
            
            if (isNaN(lat) || isNaN(lng)) return;
            
            // Update truck position
            currentTruck.animatedVehicle.setLatLng([lat, lng]);
            applyTruckHeading(currentTruck.animatedVehicle, currentSegment.angle);
            
            // Calculate current point index
            let currentIndex = startIndex;
            let accumulatedDistance = 0;
            
            for (let i = startIndex; i < currentTruck.simulationPoints.length - 1; i++) {
                const p1 = currentTruck.simulationPoints[i];
                const p2 = currentTruck.simulationPoints[i + 1];
                if (!p1 || !p2) continue;
                
                const segDist = calculateDistanceBetweenPointsUser(p1[0], p1[1], p2[0], p2[1]);
                if (accumulatedDistance + segDist >= distanceTraveled) {
                    currentIndex = i;
                    break;
                }
                accumulatedDistance += segDist;
                currentIndex = i + 1;
            }
            
            if (currentIndex !== currentTruck.currentPointIndex) {
                currentTruck.currentPointIndex = currentIndex;
            }
            
            // Update traveled path
            const traveledPoints = [];
            for (let i = 0; i <= currentTruck.currentPointIndex; i++) {
                if (currentTruck.simulationPoints[i]) {
                    traveledPoints.push(currentTruck.simulationPoints[i]);
                }
            }
            traveledPoints.push([lat, lng]);
            
            if (currentTruck.traveledLayer) {
                userRemoveRegisteredRouteLayer(routeId, currentTruck.traveledLayer);
            }
            if (traveledPoints.length > 1) {
                currentTruck.traveledLayer = userRegisterRouteLayer(routeId, L.polyline(traveledPoints, {
                    color: '#9ca3af',
                    weight: 4,
                    opacity: 0.6
                }).addTo(window.usermap));
            }
            
            // Update progress bar if exists
            const progressPercent = (currentTruck.currentPointIndex / currentTruck.simulationPoints.length) * 100;
            const progressBar = document.getElementById('userRouteProgress');
            if (progressBar) {
                progressBar.style.width = `${progressPercent}%`;
            }
            
            userUpdateRouteInfoCard();
        }
        
        currentTruck.animationFrame = requestAnimationFrame(animate);
    };
    
    truck.animationFrame = requestAnimationFrame(animate);
}

function userHoldTruckAtDestination(routeId) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck) return;

    if (truck.animationFrame) {
        cancelAnimationFrame(truck.animationFrame);
        truck.animationFrame = null;
    }

    const finalPoint = truck.simulationPoints?.[truck.simulationPoints.length - 1];
    if (finalPoint && truck.animatedVehicle) {
        truck.animatedVehicle.setLatLng(finalPoint);
    }

    truck.currentPointIndex = Math.max((truck.simulationPoints?.length || 1) - 1, 0);
    truck.isSimulating = false;
    truck.awaitingServerCompletion = true;
    userUpdateRouteInfoCard();
}

function userStopSimulationForRoute(routeId) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck) return;
    
    if (truck.animationFrame) cancelAnimationFrame(truck.animationFrame);
    userClearRouteRendering(routeId);
    
    truck.isSimulating = false;
    userActiveTrucks.delete(routeId);
    
    userUpdateRouteInfoCard();
}

function userCreateFireTruckIcon() {
    return L.icon({
        iconUrl: '/truck.png',
        iconSize: [48, 48],
        iconAnchor: [24, 24],
        popupAnchor: [0, -24],
        className: 'fire-truck-icon'
    });
}

function userTruckPopupLoadingMarkup(truck = {}) {
    return `
        <div style="min-width:260px;max-width:320px;font-family:system-ui,-apple-system,sans-serif;">
            <strong style="color:#1f2937;">Fetching incident information</strong>
            <div style="font-size:12px;color:#64748b;margin-top:5px;">Loading responder team and incident location...</div>
            <div style="background:#f8fafc;border-radius:10px;padding:9px;margin-top:10px;font-size:12px;color:#64748b;">Truck ${escapeCitizenModalHtml(truck.routeId || 'N/A')}</div>
        </div>
    `;
}

function userTruckPopupErrorMarkup(truck = {}, message = 'Unable to load incident information.') {
    return `
        <div style="min-width:260px;max-width:320px;font-family:system-ui,-apple-system,sans-serif;">
            <strong style="color:#b91c1c;">Truck ${escapeCitizenModalHtml(truck.routeId || 'N/A')}</strong>
            <div style="background:#fef2f2;color:#991b1b;border-radius:10px;padding:9px;margin-top:8px;font-size:12px;">${escapeCitizenModalHtml(message)}</div>
        </div>
    `;
}

function userTruckPopupMarkup(truck = {}, payload = {}) {
    const team = payload.team || {};
    const members = Array.isArray(team.members) ? team.members : [];
    const incident = payload.incident || {};
    const target = payload.target_location || {};
    const coordinates = incident.coordinates || target || {};
    const incidentTitle = incident.title || target.title || truck.routeDestination?.title || 'Fire incident';
    const readableLocation = incident.location || target.title || 'Incident location unavailable';
    const statusLabel = String(incident.status || payload.status || truck.status || 'responding').replaceAll('_', ' ').toUpperCase();
    const membersHtml = members.length
        ? members.map((member, index) => `
            <div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;${index < members.length - 1 ? 'border-bottom:1px solid #fed7aa;' : ''}">
                <span style="font-size:12px;font-weight:600;color:#1f2937;">${escapeCitizenModalHtml(member.name || 'Responder')}</span>
                <span style="font-size:11px;color:#9a3412;text-align:right;">${escapeCitizenModalHtml(member.role_label || member.rank || 'Responder')}</span>
            </div>
        `).join('')
        : '<div style="font-size:12px;color:#6b7280;">No responder team members found.</div>';

    return `
        <div style="min-width:280px;max-width:340px;font-family:system-ui,-apple-system,sans-serif;">
            <div style="background:linear-gradient(135deg,#dc2626 0%,#991b1b 100%);color:white;padding:12px 14px;border-radius:12px 12px 0 0;margin:-12px -12px 0 -12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <div>
                        <div style="font-size:15px;font-weight:700;">${escapeCitizenModalHtml(team.label || `Truck ${truck.routeId || 'N/A'}`)}</div>
                        <div style="font-size:11px;opacity:.9;">${escapeCitizenModalHtml(statusLabel)}</div>
                    </div>
                    <i class="fas fa-truck" style="font-size:20px;"></i>
                </div>
            </div>
            <div style="padding-top:12px;">
                <div style="background:#f8fafc;border-radius:10px;padding:10px;margin-bottom:10px;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px;">INCIDENT INFORMATION</div>
                    <div style="font-size:13px;font-weight:700;color:#1f2937;">${escapeCitizenModalHtml(incidentTitle)}</div>
                    <div style="font-size:12px;color:#475569;margin-top:5px;line-height:1.4;">${escapeCitizenModalHtml(readableLocation)}</div>
                    ${(coordinates.lat && coordinates.lng) ? `<div style="font-size:11px;color:#64748b;margin-top:4px;">${Number(coordinates.lat).toFixed(6)}, ${Number(coordinates.lng).toFixed(6)}</div>` : ''}
                </div>
                <div style="background:#fff7ed;border-radius:10px;padding:10px;">
                    <div style="font-size:11px;color:#9a3412;margin-bottom:6px;">RESPONDERS</div>
                    ${membersHtml}
                </div>
            </div>
        </div>
    `;
}

function userBindTruckIncidentPopup(routeId, marker, truck = null) {
    if (!marker) return;
    const activeTruck = truck || userActiveTrucks.get(normalizeUserRouteId(routeId)) || { routeId };
    marker.bindPopup(userTruckPopupLoadingMarkup(activeTruck), { minWidth: 260, maxWidth: 360 });
    marker.on('popupopen', async () => {
        const popup = marker.getPopup();
        const currentTruck = truck || userActiveTrucks.get(normalizeUserRouteId(routeId)) || activeTruck;
        if (popup) popup.setContent(userTruckPopupLoadingMarkup(currentTruck));

        try {
            const response = await fetch(`/api/route/${encodeURIComponent(routeId)}/truck-details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.success === false) {
                throw new Error(payload.error || 'Unable to load incident information.');
            }
            if (popup) popup.setContent(userTruckPopupMarkup(currentTruck, payload.data || {}));
        } catch (error) {
            console.error('Failed to load citizen truck popup details:', error);
            if (popup) popup.setContent(userTruckPopupErrorMarkup(currentTruck, error.message || 'Unable to load incident information.'));
        }
    });
}

function userUpdateLiveTruckPosition(routeId, route = {}) {
    routeId = normalizeUserRouteId(routeId);
    const truck = userActiveTrucks.get(routeId);
    if (!truck || !window.usermap) return false;

    const lat = Number(route.current_lat ?? route.origin_lat);
    const lng = Number(route.current_lng ?? route.origin_lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;

    if (truck.animationFrame) {
        cancelAnimationFrame(truck.animationFrame);
        truck.animationFrame = null;
    }

    if (!truck.animatedVehicle) {
        truck.animatedVehicle = userRegisterRouteLayer(routeId, L.marker([lat, lng], {
            icon: userCreateFireTruckIcon(),
            zIndexOffset: 1000,
        }).addTo(window.usermap));
        userBindTruckIncidentPopup(routeId, truck.animatedVehicle, truck);
    } else {
        const previous = truck.animatedVehicle.getLatLng();
        truck.animatedVehicle.setLatLng([lat, lng]);
        if (previous && (previous.lat !== lat || previous.lng !== lng)) {
            const angle = Math.atan2(lng - previous.lng, lat - previous.lat) * 180 / Math.PI;
            if (Number.isFinite(angle)) applyTruckHeading(truck.animatedVehicle, angle);
        }
    }

    truck.currentPointIndex = Number(route.current_position_index || truck.currentPointIndex || 0);
    truck.isSimulating = false;
    return true;
}

function userCreateRouteInfoCard() {
    if (document.getElementById('userRouteInfoCard')) {
        return;
    }

    const host = document.getElementById('topRoutePanelHost');
    if (!host) return;
    
    const routeCard = document.createElement('div');
    routeCard.id = 'userRouteInfoCard';
    routeCard.className = 'user-route-info-card';
    routeCard.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        display: none;
        max-width: 420px;
        margin-left: auto;
        border: 1px solid rgba(251, 146, 60, 0.25);
        border-left: 4px solid #ea580c;
    `;
    
    routeCard.innerHTML = `
        <div id="userRouteInfoHeader" class="flex justify-between items-center mb-3">
            <h3 id="userRouteInfoTitle" class="font-semibold text-gray-800">
                <i class="fas fa-route" style="color:#c2410c;"></i>
                Active Emergency Routes
            </h3>
            <button id="userRoutePanelToggle" type="button" aria-label="Minimize active emergency routes" style="width:32px; height:32px; border:none; border-radius:999px; background:#fff7ed; color:#c2410c; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;">
                <i class="fas fa-minus"></i>
            </button>
        </div>
        <div id="userRouteInfoBody">
            <div id="userActiveRoutesList" style="max-height: 300px; overflow-y: auto;">
            <div style="text-align: center; padding: 20px; color: #6b7280;">No active routes</div>
            </div>
            <div class="route-progress-container" style="margin-top: 12px; background: #e5e7eb; border-radius: 10px; overflow: hidden; height: 6px;">
                <div id="userRouteProgress" class="route-progress-bar" style="width: 0%; height: 100%; background: #ea580c; transition: width 0.3s ease;"></div>
            </div>
        </div>
    `;
    
    host.appendChild(routeCard);

    const toggleButton = document.getElementById('userRoutePanelToggle');
    if (toggleButton) {
        toggleButton.addEventListener('click', function(event) {
            event.stopPropagation();
            userRoutePanelMinimized = !userRoutePanelMinimized;
            userApplyRoutePanelState();
        });
    }

    routeCard.addEventListener('click', function(event) {
        if (!userRoutePanelMinimized) {
            return;
        }
        if (event.target.closest('#userRoutePanelToggle')) {
            return;
        }
        userRoutePanelMinimized = false;
        userApplyRoutePanelState();
    });

    userApplyRoutePanelState();
}

function userApplyRoutePanelState() {
    const routeCard = document.getElementById('userRouteInfoCard');
    const routeBody = document.getElementById('userRouteInfoBody');
    const routeTitle = document.getElementById('userRouteInfoTitle');
    const routeHeader = document.getElementById('userRouteInfoHeader');
    const toggleButton = document.getElementById('userRoutePanelToggle');

    if (!routeCard || !routeBody || !routeTitle || !routeHeader || !toggleButton) {
        return;
    }

    if (userRoutePanelMinimized) {
        routeCard.style.width = '60px';
        routeCard.style.height = '60px';
        routeCard.style.padding = '0';
        routeCard.style.borderRadius = '999px';
        routeCard.style.display = 'flex';
        routeCard.style.alignItems = 'center';
        routeCard.style.justifyContent = 'center';
        routeCard.style.cursor = 'pointer';
        routeCard.style.borderLeft = 'none';

        routeHeader.style.marginBottom = '0';
        routeHeader.style.width = '100%';
        routeHeader.style.justifyContent = 'center';
        routeTitle.innerHTML = '<i class="fas fa-route" style="color:#c2410c; font-size:1.1rem;"></i>';
        routeBody.style.display = 'none';

        toggleButton.innerHTML = '<i class="fas fa-expand-alt"></i>';
        toggleButton.setAttribute('aria-label', 'Expand active emergency routes');
        toggleButton.style.position = 'absolute';
        toggleButton.style.inset = '0';
        toggleButton.style.width = '100%';
        toggleButton.style.height = '100%';
        toggleButton.style.background = 'transparent';
        toggleButton.style.color = 'transparent';
        toggleButton.style.borderRadius = '999px';
    } else {
        routeCard.style.width = '';
        routeCard.style.height = '';
        routeCard.style.padding = '16px';
        routeCard.style.borderRadius = '12px';
        routeCard.style.display = 'block';
        routeCard.style.alignItems = '';
        routeCard.style.justifyContent = '';
        routeCard.style.cursor = '';
        routeCard.style.borderLeft = '4px solid #ea580c';

        routeHeader.style.marginBottom = '12px';
        routeHeader.style.width = '';
        routeHeader.style.justifyContent = 'space-between';
        routeTitle.innerHTML = '<i class="fas fa-route" style="color:#c2410c;"></i> Active Emergency Routes';
        routeBody.style.display = 'block';

        toggleButton.innerHTML = '<i class="fas fa-minus"></i>';
        toggleButton.setAttribute('aria-label', 'Minimize active emergency routes');
        toggleButton.style.position = '';
        toggleButton.style.inset = '';
        toggleButton.style.width = '32px';
        toggleButton.style.height = '32px';
        toggleButton.style.background = '#fff7ed';
        toggleButton.style.color = '#c2410c';
        toggleButton.style.borderRadius = '999px';
    }
}

function userSetRoutePanelLoading(isLoading, message = 'Finding active emergency routes...') {
    userRoutePanelLoading = isLoading;
    userCreateRouteInfoCard();

    const routeCard = document.getElementById('userRouteInfoCard');
    const routeHost = document.getElementById('topRoutePanelHost');
    const routesList = document.getElementById('userActiveRoutesList');

    if (!routeCard || !routeHost || !routesList) {
        return;
    }

    if (isLoading) {
        routesList.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px; padding:18px 14px; background:#fff7ed; border-radius:12px; color:#c2410c;">
                <span class="inline-fa-icon icon-spin" style="width:1.1rem; height:1.1rem;">
                    <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
                </span>
                <div>
                    <div style="font-size:13px; font-weight:700;">${message}</div>
                    <div style="font-size:11px; color:#9a3412; margin-top:2px;">Waiting for the active route to finish rendering...</div>
                </div>
            </div>`;
        routeCard.style.display = 'block';
        routeHost.style.display = 'block';
        if (userRoutePanelMinimized) {
            userRoutePanelMinimized = false;
        }
        userApplyRoutePanelState();
    }
}

function userUpdateRouteInfoCard() {
    const routesList = document.getElementById('userActiveRoutesList');
    if (!routesList) return;

    if (userRoutePanelLoading) {
        const routeCard = document.getElementById('userRouteInfoCard');
        const routeHost = document.getElementById('topRoutePanelHost');
        if (routeCard) routeCard.style.display = 'block';
        if (routeHost) routeHost.style.display = 'block';
        userApplyRoutePanelState();
        return;
    }
    
    if (userActiveTrucks.size === 0) {
        routesList.innerHTML = '<div style="text-align: center; padding: 20px; color: #6b7280;">No active routes</div>';
        const routeCard = document.getElementById('userRouteInfoCard');
        if (routeCard) routeCard.style.display = 'none';
        const routeHost = document.getElementById('topRoutePanelHost');
        if (routeHost) routeHost.style.display = 'none';
        return;
    }
    
    let html = '';
    let index = 1;
    
    for (const [routeId, truck] of userActiveTrucks) {
        const progress = truck.currentPointIndex && truck.simulationPoints ? 
            Math.round((truck.currentPointIndex / truck.simulationPoints.length) * 100) : 0;
        
        html += `
            <div style="padding: 8px 12px; margin-bottom: 8px; background: #f9fafb; border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; font-size: 13px;">
                        <i class="fa-solid fa-truck-fire" style="color: #dc2626;"></i>
                        Emergency Vehicle ${index}
                    </span>
                </div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                    Incident: ${truck.routeDestination?.title || 'Incident location'}
                </div>
                <div style="margin-top: 4px;">
                    <div style="background: #e5e7eb; border-radius: 10px; height: 4px; overflow: hidden;">
                        <div style="width: ${progress}%; height: 100%; background: #dc2626; transition: width 0.3s;"></div>
                    </div>
                </div>
                <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                    Progress: ${progress}%
                </div>
            </div>
        `;
        index++;
    }
    
    routesList.innerHTML = html;
    const routeCard = document.getElementById('userRouteInfoCard');
    if (routeCard) routeCard.style.display = 'block';
    const routeHost = document.getElementById('topRoutePanelHost');
    if (routeHost) routeHost.style.display = 'block';
    userApplyRoutePanelState();
}

function userShowRouteNotification(message, type = 'info') {
    // Create a temporary notification for the user
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 2000;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// ============================================================================
// ROUTE POLLING FOR USER MAP (Based on Incident ID)
// ============================================================================

function userStartRoutePollingForIncident(incidentId) {
    if (userRoutePollingInterval) clearInterval(userRoutePollingInterval);
    
    console.log(`Starting route polling for incident: ${incidentId}`);
    userSetRoutePanelLoading(true);

    const pickPrimaryCitizenRoute = (routes) => {
        if (!Array.isArray(routes) || routes.length === 0) {
            return null;
        }

        return [...routes].sort((left, right) => {
            const leftMoving = String(left?.status || '').toLowerCase() === 'moving' ? 1 : 0;
            const rightMoving = String(right?.status || '').toLowerCase() === 'moving' ? 1 : 0;

            if (leftMoving !== rightMoving) {
                return rightMoving - leftMoving;
            }

            const leftUpdated = new Date(left?.last_update_at || left?.updated_at || left?.created_at || 0).getTime();
            const rightUpdated = new Date(right?.last_update_at || right?.updated_at || right?.created_at || 0).getTime();

            if (leftUpdated !== rightUpdated) {
                return rightUpdated - leftUpdated;
            }

            return Number(right?.route_id || 0) - Number(left?.route_id || 0);
        })[0];
    };

    const pollRoutes = async () => {
        if (!incidentId) return;
        
        try {
            const response = await fetchCitizenLiveResponseOnce(`citizen-route-active-${incidentId}`, `/api/route/active/${incidentId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            if (!response.ok) return;
            
            const routes = await response.json();
            console.log(`Polling - found ${routes.length} routes for incident ${incidentId}`);
            const primaryRoute = pickPrimaryCitizenRoute(routes);
            const visibleRouteIds = new Set();

            if (primaryRoute) {
                const normalizedRouteId = normalizeUserRouteId(primaryRoute.route_id);
                visibleRouteIds.add(normalizedRouteId);

                if (userActiveTrucks.has(normalizedRouteId)) {
                    const existingTruck = userActiveTrucks.get(normalizedRouteId);
                    existingTruck.routeSource = primaryRoute.route_source || existingTruck.routeSource || 'real';
                    if (String(existingTruck.routeSource).toLowerCase() !== 'demo') {
                        userUpdateLiveTruckPosition(normalizedRouteId, primaryRoute);
                    } else if (primaryRoute.status === 'moving'
                        && !existingTruck.isSimulating
                        && !existingTruck.awaitingServerCompletion) {
                        const dbIndex = primaryRoute.current_position_index || 0;
                        console.log(`Restarting stopped truck from DB position: ${dbIndex}`);
                        await userStartSimulationFromPosition(normalizedRouteId, dbIndex);
                    }
                } else {
                    console.log('Loading PRIMARY route from DB for user:', normalizedRouteId, 'status:', primaryRoute.status);
                    await userDisplayAndStartRoute(
                        normalizedRouteId,
                        primaryRoute,
                        { title: 'Incident location' },
                        incidentId
                    );
                }
            }

            for (const existingRouteId of Array.from(userActiveTrucks.keys())) {
                if (!visibleRouteIds.has(existingRouteId)) {
                    userStopSimulationForRoute(existingRouteId);
                }
            }
            
            userUpdateRouteInfoCard();
            
        } catch (error) {
            console.error('Error polling routes for user:', error);
        }
    };

    pollRoutes();
    userRoutePollingInterval = setInterval(() => {
        if (!citizenPageIsVisible()) return;
        pollRoutes();
    }, CITIZEN_ROUTE_POLL_INTERVAL_MS);
}

function userStopRoutePolling() {
    if (userRoutePollingInterval) {
        clearInterval(userRoutePollingInterval);
        userRoutePollingInterval = null;
    }
}

// ============================================================================
// FUNCTION TO FETCH AND DISPLAY ROUTES FOR A SPECIFIC INCIDENT
// ============================================================================

async function userFetchAndDisplayRoutesForIncident(incidentId) {
    console.log(`Fetching routes for incident: ${incidentId}`);
    
    // Clear existing routes first
    userClearAllRoutes();
    userSetRoutePanelLoading(true, 'Finding active emergency routes...');
    
    userStartRoutePollingForIncident(incidentId);
}

function userClearAllRoutes() {
    // Stop polling
    userStopRoutePolling();
    
    // Clear all active trucks
    for (const [routeId] of userActiveTrucks) {
        userStopSimulationForRoute(routeId);
    }
    userActiveTrucks.clear();
    
    // Clear route layer if exists
    if (userRouteLayer && window.usermap) {
        window.usermap.removeLayer(userRouteLayer);
        userRouteLayer = null;
    }
    
    // Hide route info card
    const routeCard = document.getElementById('userRouteInfoCard');
    if (routeCard) routeCard.style.display = 'none';
    userRoutePanelMinimized = false;
}

// ============================================================================
// EXPOSE FUNCTIONS GLOBALLY
// ============================================================================

window.userFetchAndDisplayRoutesForIncident = userFetchAndDisplayRoutesForIncident;
window.userClearAllRoutes = userClearAllRoutes;
window.userStopRoutePolling = userStopRoutePolling;
window.refreshCitizenMapData = refreshCitizenMapData;
window.refreshCitizenMarkersIfChanged = refreshCitizenMarkersIfChanged;
window.startCitizenRouteDiscovery = startCitizenRouteDiscovery;
window.loadMarkers = loadMarkers;
window.syncCitizenRiskReportsToMainMapMarkers = syncCitizenRiskReportsToMainMapMarkers;

// Add CSS animations
const userStyleElement = document.createElement('style');
userStyleElement.textContent = `
    @keyframes blink {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
    
    .fire-truck-marker-user {
        transition: all 0.1s linear;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(userStyleElement);

console.log('User route tracking system loaded - Will display routes for specific incidents');
