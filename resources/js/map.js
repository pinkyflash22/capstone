import './bootstrap';
import './routing-panel.js';
import { station, getRoute, setName, getStations, getRouteDistance } from './functions.js';

console.log('🔥🔥🔥 MAP.JS IS LOADED! 🔥🔥🔥');
console.log('Timestamp:', new Date().toISOString());

// ============================================================================
// GLOBAL VARIABLES - Using window. for global access
// ============================================================================

let name = "";
let selectedLat = null;
let selectedLng = null;
let path, temp;
let title = null;
let description = null;
let existingMarkers = {};
let thisLat = null;
let i = 0;
let tempMarker = null;
let stationLayer = L.featureGroup();
let riskHazardFeatureGroup = null;
let incidentLayer = L.layerGroup();
let hydrantLayer = L.layerGroup();
let otherLayer = L.layerGroup();
let shapeChecker = false;
let isDrawing = false;
let drawnItems = null;
let shapeLayers = {};
let selectedShapeId = null;
let startMarker = null;
let endMarker = null;
let stations = [];
let markerPlacementSelectionActive = false;
let markerMapClickHandlerRegistered = false;
let markerSaveInProgress = false;
let optimisticMarkerSequence = 0;
const optimisticMapMarkers = new Map();

// Route and simulation variables
let routeLayer = null;
let remainingRouteLayer = null;
let activeRoute = null;
let simulationPoints = [];
let originalSimulationPoints = [];
let traveledPoints = [];
window.remainingPoints = [];
let currentPointIndex = 0;
let isSimulating = false;
let animatedVehicle = null;
let animationFrame = null;
let animationStartTime = null;
let animationDuration = null;
let currentAnimationPoint = 0;
let currentDestinationType = null;
let routeDestination = null;

// MULTI-TRUCK SUPPORT
let activeTrucks = new Map(); // Stores each truck separately by route_id

// Tracking variables
let firefighterMarkers = {};
let animationIntervals = {};
let routeLayers = {};
let routeOverlayControl = null;
let routeStationLayers = new Map();
let routeRenderingById = new Map();
let routeLayerMetadata = new Map();
let markerReferenceCache = {
    incidentTypes: null,
    hydrants: null,
    schools: null,
    stations: null,
    loadedAt: 0,
    requestPromise: null
};
let latestIncidentsCache = {
    data: null,
    loadedAt: 0,
    requestPromise: null,
    cursorUpdatedAt: null,
    cursorIncidentId: 0
};
const incidentDetailsCache = new Map();
const incidentDetailsRequestCache = new Map();
const TRUCK_REALTIME_REFRESH_MS = 2000;

// Shape management variables
let manageShapesMode = false;
let selectedShapeForEdit = null;
let activeShapeGeometryEdit = null;
let shapeFeatureGroup = null;
let fireRiskAreaLayerGroup = null;
let currentRiskAnalysisLayerGroup = null;
let nextMonthForecastLayerGroup = null;
const RISK_HEAT_BLEND_STORAGE_KEY = 'pyrosense_risk_heat_blend_meters';
let riskHeatBlendMeters = (() => {
    try {
        const saved = Number(window.localStorage?.getItem(RISK_HEAT_BLEND_STORAGE_KEY));
        return Number.isFinite(saved) ? Math.min(200, Math.max(50, saved)) : 100;
    } catch (error) {
        return 100;
    }
})();
let boundaryEditorContext = null;

// Notification system

// Details panel
let detailsPanel = null;
let currentDetailsMarker = null;
let currentDetailsIncident = null;

// Edit modal
let editModal = null;
let currentEditMarker = null;
let markerLocationPicker = null;

let userLocation = null;
let currentIncident = null;
let watchId = null;
let updateInterval = null;
let incidentInterval = null;
let simulationInterval = null;

// Real-time updates
let realTimeLocationWatch = null;
let lastLocationUpdate = null;

// Marker layers
let stationMarkers = null;
let incidentMarkers = null;
let assignedIncidentMarkers = null;
let userMarker = null;
let routeMarkers = [];
let distanceMarkers = [];
window.thisIncidents = null;
// Route polling interval
let routePollingInterval = null;
let markerStatePollingInterval = null;
let boundaryStatePollingInterval = null;
let lastMarkerStateSignature = null;
let lastBoundaryStateSignature = null;
let lastMarkersFetchAt = 0;
let lastStationsFetchAt = 0;
let lastShapesFetchAt = 0;
let lastUserLocationSentSignature = null;
let lastUserLocationSentAt = 0;
const ROUTE_POLL_INTERVAL_MS = 15000;
const MARKER_STATE_POLL_INTERVAL_MS = 30000;
const FIREFIGHTER_ROUTE_POLL_INTERVAL_MS = 25000;
const FIREFIGHTER_MARKER_STATE_POLL_INTERVAL_MS = 90000;
const BOUNDARY_STATE_POLL_INTERVAL_MS = 15000;
const STATIC_LAYER_REFETCH_INTERVAL_MS = 180000;
const LOCATION_WRITE_DEDUP_WINDOW_MS = 90000;
const LATEST_INCIDENTS_CACHE_TTL_MS = 10000;
const TRUCK_REALTIME_ROUTE_SYNC_MS = 5000;
const NAVIGATION_OFF_ROUTE_THRESHOLD_METERS = 120;
const NAVIGATION_REROUTE_COOLDOWN_MS = 30000;
const LIVE_FETCH_TIMEOUT_MS = 15000;
const STATIC_MAP_CACHE_TTL_MS = 5 * 60 * 1000;
const BOUNDARY_CHANGE_STORAGE_KEY = 'pyrosense:boundary-change';
const liveRequestRegistry = new Map();
let routePollingInFlight = false;
let markerStatePollingInFlight = false;
let boundaryStatePollingInFlight = false;
let shapeFetchController = null;
let shapeFetchSequence = 0;
let markerFeedFetchController = null;
let markerFeedFetchSequence = 0;
let riskAreaFetchController = null;
let riskAreaFetchSequence = 0;
let dynamicMapRefreshPromise = null;
let dynamicMapRefreshQueued = false;
let mapPollingVisibilityHandler = null;
let mapPollingPageHideHandler = null;
let mapPollingBeforeUnloadHandler = null;
let mapBoundaryStorageHandler = null;

function isPageVisible() {
    return typeof document === 'undefined' || document.visibilityState === 'visible';
}

function abortLiveRequestsByPrefix(prefix) {
    for (const [requestKey, requestEntry] of liveRequestRegistry.entries()) {
        if (!String(requestKey).startsWith(prefix)) {
            continue;
        }

        try {
            requestEntry?.controller?.abort();
        } catch (error) {
            console.warn('Failed to abort live request:', requestKey, error);
        }
    }
}

function destroyPyroMapRuntime(options = {}) {
    const { removeMap = true } = options;

    stopMapLivePolling();
    unbindMapPollingLifecycle();

    stopAllSimulations(false);
    abortLiveRequestsByPrefix('route-active');
    abortLiveRequestsByPrefix('markers-poll');
    abortLiveRequestsByPrefix('boundaries-poll');
    abortLiveRequestsByPrefix('incident-latest-poll');
    abortLiveRequestsByPrefix('chief-incident-map');
    shapeFetchController?.abort();
    markerFeedFetchController?.abort();
    riskAreaFetchController?.abort();

    if (removeMap && window.map) {
        try {
            window.map.off();
            window.map.remove();
        } catch (error) {
            console.warn('Failed to remove active map instance cleanly:', error);
        }
    }

    window.map = null;
    window.firefighterDashboardMap = null;
    window.routesGroup = null;
}

window.destroyPyroMapRuntime = destroyPyroMapRuntime;

function stopMapLivePolling() {
    if (routePollingInterval) {
        clearInterval(routePollingInterval);
        routePollingInterval = null;
    }

    if (markerStatePollingInterval) {
        clearInterval(markerStatePollingInterval);
        markerStatePollingInterval = null;
    }

    if (boundaryStatePollingInterval) {
        clearInterval(boundaryStatePollingInterval);
        boundaryStatePollingInterval = null;
    }
}

function bindMapPollingLifecycle() {
    if (mapPollingVisibilityHandler || typeof document === 'undefined') {
        return;
    }

    mapPollingVisibilityHandler = () => {
        if (isPageVisible()) {
            startMapLivePolling();
            return;
        }

        stopMapLivePolling();
    };
    mapPollingPageHideHandler = () => destroyPyroMapRuntime({ removeMap: false });
    mapPollingBeforeUnloadHandler = () => destroyPyroMapRuntime({ removeMap: false });
    mapBoundaryStorageHandler = (event) => {
        if (event.key !== BOUNDARY_CHANGE_STORAGE_KEY || !event.newValue) return;

        try {
            const payload = JSON.parse(event.newValue);
            if (payload?.signature) {
                lastBoundaryStateSignature = String(payload.signature);
            }
        } catch (error) {
            console.warn('Unable to parse boundary change event:', error);
        }

        refreshDynamicMapData({ reason: 'boundary-cross-tab' });
    };

    document.addEventListener('visibilitychange', mapPollingVisibilityHandler);
    window.addEventListener('pagehide', mapPollingPageHideHandler);
    window.addEventListener('beforeunload', mapPollingBeforeUnloadHandler);
    window.addEventListener('storage', mapBoundaryStorageHandler);
}

function unbindMapPollingLifecycle() {
    if (mapPollingVisibilityHandler && typeof document !== 'undefined') {
        document.removeEventListener('visibilitychange', mapPollingVisibilityHandler);
    }
    if (mapPollingPageHideHandler) {
        window.removeEventListener('pagehide', mapPollingPageHideHandler);
    }
    if (mapPollingBeforeUnloadHandler) {
        window.removeEventListener('beforeunload', mapPollingBeforeUnloadHandler);
    }
    if (mapBoundaryStorageHandler) {
        window.removeEventListener('storage', mapBoundaryStorageHandler);
    }

    mapPollingVisibilityHandler = null;
    mapPollingPageHideHandler = null;
    mapPollingBeforeUnloadHandler = null;
    mapBoundaryStorageHandler = null;
}

function isFirefighterDashboardMapInstance() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    return configuredInstanceKey === 'firefighterDashboard';
}

function isChiefCommandMapContext() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    const currentPath = String(window.location?.pathname || '');
    const roleMeta = String(document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').toLowerCase().trim();

    return configuredInstanceKey === 'chiefDashboard'
        || configuredInstanceKey === 'chiefIncidentMap'
        || currentPath.startsWith('/station-admin/')
        || currentPath.startsWith('/admin/')
        || roleMeta === 'chief'
        || roleMeta === 'station admin'
        || roleMeta === 'provincial admin'
        || roleMeta === 'admin';
}

function isDispatchCommandMapContext() {
    const roleMeta = String(document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').toLowerCase().trim();
    return ['chief', 'station admin', 'provincial admin', 'admin'].includes(roleMeta);
}

function isChiefDashboardMapInstance() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    return configuredInstanceKey === 'chiefDashboard';
}

function shouldUseResponderPopupUi() {
    return isFirefighterDashboardMapInstance() || isChiefCommandMapContext();
}

function isCitizenDashboardMapContext() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    const currentPath = String(window.location?.pathname || '');
    const roleMeta = String(document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').toLowerCase().trim();

    return configuredInstanceKey === 'citizenDashboard'
        || currentPath.startsWith('/citizen/')
        || roleMeta === 'citizen';
}

function shouldUseResponderMobileDetailsUi() {
    return shouldUseResponderPopupUi() && window.innerWidth <= 768;
}

function getRoutePollIntervalMs() {
    return isFirefighterDashboardMapInstance()
        ? FIREFIGHTER_ROUTE_POLL_INTERVAL_MS
        : ROUTE_POLL_INTERVAL_MS;
}

function getMarkerStatePollIntervalMs() {
    return isFirefighterDashboardMapInstance()
        ? FIREFIGHTER_MARKER_STATE_POLL_INTERVAL_MS
        : MARKER_STATE_POLL_INTERVAL_MS;
}

function shouldSkipStaticLayerRefetch(lastFetchedAt) {
    return (isFirefighterDashboardMapInstance() || isChiefDashboardMapInstance())
        && lastFetchedAt > 0
        && (Date.now() - lastFetchedAt) < STATIC_LAYER_REFETCH_INTERVAL_MS;
}

function buildLookupMap(items, keySelector) {
    const lookup = new Map();

    if (!Array.isArray(items) || typeof keySelector !== 'function') {
        return lookup;
    }

    for (const item of items) {
        const key = keySelector(item);
        if (key !== null && key !== undefined && key !== '') {
            lookup.set(String(key), item);
        }
    }

    return lookup;
}

function buildIncidentLookup(incidents) {
    const lookup = new Map();

    if (!Array.isArray(incidents)) {
        return lookup;
    }

    for (const incident of incidents) {
        const incidentId = incident?.incident_id ?? incident?.id ?? null;
        const lat = incident?.lat ?? incident?.latitude ?? incident?.marker?.lat ?? null;
        const lng = incident?.lng ?? incident?.longitude ?? incident?.marker?.lng ?? null;

        if (incident?.marker_id !== null && incident?.marker_id !== undefined) {
            lookup.set(`marker:${incident.marker_id}`, incident);
        }

        if (incidentId !== null && incidentId !== undefined) {
            lookup.set(`incident:${incidentId}`, incident);
        }

        if (lat !== null && lat !== undefined && lng !== null && lng !== undefined) {
            lookup.set(`coord:${Number(lat).toFixed(6)}:${Number(lng).toFixed(6)}`, incident);
        }
    }

    return lookup;
}

function findIncidentInLookup(marker, incidentLookup, fallbackIncident = null) {
    if (fallbackIncident) {
        return fallbackIncident;
    }

    if (!(incidentLookup instanceof Map)) {
        return null;
    }

    if (marker?.marker_id !== null && marker?.marker_id !== undefined) {
        const byMarker = incidentLookup.get(`marker:${marker.marker_id}`);
        if (byMarker) {
            return byMarker;
        }
    }

    if (marker?.incident_id !== null && marker?.incident_id !== undefined) {
        const byIncident = incidentLookup.get(`incident:${marker.incident_id}`);
        if (byIncident) {
            return byIncident;
        }
    }

    const markerLat = marker?.lat ?? marker?.marker?.lat ?? null;
    const markerLng = marker?.lng ?? marker?.marker?.lng ?? null;
    if (markerLat !== null && markerLat !== undefined && markerLng !== null && markerLng !== undefined) {
        const byCoordinate = incidentLookup.get(`coord:${Number(markerLat).toFixed(6)}:${Number(markerLng).toFixed(6)}`);
        if (byCoordinate) {
            return byCoordinate;
        }
    }

    return null;
}

function normalizeIncidentForMap(incident) {
    if (!incident || typeof incident !== 'object') {
        return null;
    }

    const incidentId = incident.incident_id ?? incident.id ?? null;
    const lat = incident.lat ?? incident.latitude ?? incident.marker?.lat ?? null;
    const lng = incident.lng ?? incident.longitude ?? incident.marker?.lng ?? null;

    return {
        ...incident,
        incident_id: incidentId,
        id: incident.id ?? incidentId,
        lat,
        lng,
        status: incident.status || incident.incident_status || 'reported',
        incident_status: incident.incident_status || incident.status || 'reported',
        incident_type: incident.incident_type || incident.incident_type_name || 'Fire',
        incident_type_name: incident.incident_type_name || incident.incident_type || 'Fire',
        reported_at: incident.reported_at || incident.created_at || incident.updated_at || null
    };
}

function resolveIncidentCursorTimestamp(incident) {
    return incident?.updated_at
        || incident?.marker?.updated_at
        || incident?.created_at
        || null;
}

function buildLatestIncidentCursor(incidents) {
    if (!Array.isArray(incidents) || incidents.length === 0) {
        return {
            updatedAt: null,
            incidentId: 0
        };
    }

    const sortedIncidents = [...incidents].sort((left, right) => {
        const leftTimestamp = Date.parse(resolveIncidentCursorTimestamp(left) || 0) || 0;
        const rightTimestamp = Date.parse(resolveIncidentCursorTimestamp(right) || 0) || 0;

        if (leftTimestamp === rightTimestamp) {
            return Number(right?.incident_id ?? right?.id ?? 0) - Number(left?.incident_id ?? left?.id ?? 0);
        }

        return rightTimestamp - leftTimestamp;
    });
    const latestIncident = sortedIncidents[0] || null;

    return {
        updatedAt: resolveIncidentCursorTimestamp(latestIncident),
        incidentId: Number(latestIncident?.incident_id ?? latestIncident?.id ?? 0) || 0
    };
}

function mergeIncidentCollections(existingIncidents = [], incomingIncidents = []) {
    const mergedById = new Map();

    for (const incident of existingIncidents) {
        const normalizedIncident = normalizeIncidentForMap(incident);
        const incidentId = normalizedIncident?.incident_id ?? normalizedIncident?.id ?? null;
        if (incidentId === null || incidentId === undefined) {
            continue;
        }

        mergedById.set(Number(incidentId), normalizedIncident);
    }

    for (const incident of incomingIncidents) {
        const normalizedIncident = normalizeIncidentForMap(incident);
        const incidentId = normalizedIncident?.incident_id ?? normalizedIncident?.id ?? null;
        if (incidentId === null || incidentId === undefined) {
            continue;
        }

        mergedById.set(Number(incidentId), normalizedIncident);
    }

    return Array.from(mergedById.values()).sort((left, right) => {
        const leftTimestamp = Date.parse(resolveIncidentCursorTimestamp(left) || 0) || 0;
        const rightTimestamp = Date.parse(resolveIncidentCursorTimestamp(right) || 0) || 0;

        if (leftTimestamp === rightTimestamp) {
            return Number(right?.incident_id ?? right?.id ?? 0) - Number(left?.incident_id ?? left?.id ?? 0);
        }

        return rightTimestamp - leftTimestamp;
    });
}

function commitLatestIncidentsCache(nextIncidents, options = {}) {
    const {
        replace = false,
        cursorUpdatedAt = null,
        cursorIncidentId = 0
    } = options;
    const normalizedIncomingIncidents = Array.isArray(nextIncidents)
        ? nextIncidents.map(normalizeIncidentForMap).filter(Boolean)
        : [];
    const mergedIncidents = replace
        ? normalizedIncomingIncidents
        : mergeIncidentCollections(latestIncidentsCache.data || [], normalizedIncomingIncidents);
    const derivedCursor = buildLatestIncidentCursor(mergedIncidents);

    latestIncidentsCache = {
        data: mergedIncidents,
        loadedAt: Date.now(),
        requestPromise: null,
        cursorUpdatedAt: cursorUpdatedAt || derivedCursor.updatedAt || latestIncidentsCache.cursorUpdatedAt || null,
        cursorIncidentId: Number(cursorIncidentId || derivedCursor.incidentId || latestIncidentsCache.cursorIncidentId || 0) || 0
    };

    return latestIncidentsCache.data;
}

function mergeIncidentIntoClientState(incident) {
    const normalizedIncident = normalizeIncidentForMap(incident);
    if (!normalizedIncident?.incident_id) {
        return normalizedIncident;
    }

    const currentIncidents = Array.isArray(window.incidentsData) ? window.incidentsData : [];
    const nextIncidents = currentIncidents.filter((entry) => {
        const entryId = entry?.incident_id ?? entry?.id ?? null;
        return Number(entryId) !== Number(normalizedIncident.incident_id);
    });

    nextIncidents.unshift(normalizedIncident);
    window.incidentsData = nextIncidents;
    window.incidentsLookup = buildIncidentLookup(nextIncidents);
    commitLatestIncidentsCache(nextIncidents, { replace: true });

    return normalizedIncident;
}

window.mergeIncidentIntoClientState = mergeIncidentIntoClientState;

async function refreshIncidentMapImmediately(options = {}) {
    try {
        await Promise.allSettled([
            getLatestIncidentsData({ force: true, useLiveRequest: true }),
            window.refreshMapMarkers ? window.refreshMapMarkers({ force: true, ...options }) : Promise.resolve()
        ]);
    } catch (error) {
        console.warn('Immediate incident map refresh failed:', error);
    }
}

window.refreshIncidentMapImmediately = refreshIncidentMapImmediately;

function getCachedIncidentDetails(incidentId) {
    if (incidentId === null || incidentId === undefined) {
        return null;
    }

    return incidentDetailsCache.get(Number(incidentId)) || null;
}

async function fetchIncidentDetailsById(incidentId) {
    const normalizedIncidentId = Number(incidentId);
    if (!Number.isFinite(normalizedIncidentId) || normalizedIncidentId <= 0) {
        return null;
    }

    const cachedIncident = getCachedIncidentDetails(normalizedIncidentId);
    if (cachedIncident) {
        return cachedIncident;
    }

    if (incidentDetailsRequestCache.has(normalizedIncidentId)) {
        return incidentDetailsRequestCache.get(normalizedIncidentId);
    }

    const requestPromise = (async () => {
        const response = await fetch(`/api/incidents/${normalizedIncidentId}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch incident details (${response.status})`);
        }

        const payload = await response.json();
        const incident = normalizeIncidentForMap(payload?.data || payload);

        if (incident?.incident_id) {
            incidentDetailsCache.set(normalizedIncidentId, incident);
            mergeIncidentIntoClientState(incident);
        }

        return incident;
    })().finally(() => {
        incidentDetailsRequestCache.delete(normalizedIncidentId);
    });

    incidentDetailsRequestCache.set(normalizedIncidentId, requestPromise);
    return requestPromise;
}

function getTruckRealtimeLocation(truck) {
    if (truck?.routeSource && isDemoRouteSource(truck.routeSource)) {
        return null;
    }

    if (!truck || !Array.isArray(truck.routeTeamUserIds) || truck.routeTeamUserIds.length === 0) {
        return null;
    }

    const candidateLocations = [];

    for (const userId of truck.routeTeamUserIds) {
        const location = getUserLocation(userId);
        if (!location?.ready) {
            continue;
        }

        const lat = parseFloat(location.lat);
        const lng = parseFloat(location.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            continue;
        }

        candidateLocations.push({
            userId: Number(userId),
            lat,
            lng,
            lastUpdated: location.lastUpdated || null
        });
    }

    if (candidateLocations.length === 0) {
        return null;
    }

    const primaryUserId = Number(truck.routePrimaryUserId || 0);
    if (primaryUserId) {
        const primaryMatch = candidateLocations.find((entry) => entry.userId === primaryUserId);
        if (primaryMatch) {
            return {
                ...primaryMatch,
                source: 'primary'
            };
        }
    }

    if (candidateLocations.length === 1) {
        return {
            ...candidateLocations[0],
            source: 'single'
        };
    }

    const center = candidateLocations.reduce((carry, entry) => {
        carry.lat += entry.lat;
        carry.lng += entry.lng;
        return carry;
    }, { lat: 0, lng: 0 });

    return {
        lat: center.lat / candidateLocations.length,
        lng: center.lng / candidateLocations.length,
        source: 'centroid',
        members: candidateLocations.length
    };
}

function getClosestSimulationPointIndex(points = [], lat, lng, fallbackIndex = 0) {
    if (!Array.isArray(points) || points.length === 0 || !Number.isFinite(lat) || !Number.isFinite(lng)) {
        return fallbackIndex;
    }

    let bestIndex = fallbackIndex;
    let bestDistance = Number.POSITIVE_INFINITY;

    for (let index = 0; index < points.length; index++) {
        const point = points[index];
        if (!Array.isArray(point) || point.length < 2) {
            continue;
        }

        const distance = calculateDistanceBetweenPoints(lat, lng, point[0], point[1]);
        if (distance < bestDistance) {
            bestDistance = distance;
            bestIndex = index;
        }
    }

    return bestIndex;
}

function getDistanceFromNavigationRoute(points = [], lat, lng) {
    if (!Array.isArray(points) || points.length === 0) return Number.POSITIVE_INFINITY;
    return points.reduce((closest, point) => {
        if (!Array.isArray(point) || point.length < 2) return closest;
        return Math.min(closest, calculateDistanceBetweenPoints(lat, lng, Number(point[0]), Number(point[1])));
    }, Number.POSITIVE_INFINITY);
}

async function maybeRerouteNavigation(routeId, location) {
    const truck = activeTrucks.get(routeId);
    if (!truck || truck.rerouteInFlight || isDemoRouteSource(truck.routeSource) || !window.geoapifyKey) return false;
    const destinationLat = Number(truck.routeDestination?.lat);
    const destinationLng = Number(truck.routeDestination?.lng);
    if (!Number.isFinite(destinationLat) || !Number.isFinite(destinationLng)) return false;
    const distanceFromRoute = getDistanceFromNavigationRoute(truck.simulationPoints, location.lat, location.lng);
    if (distanceFromRoute <= NAVIGATION_OFF_ROUTE_THRESHOLD_METERS) return false;
    if (truck.lastRerouteAt && Date.now() - truck.lastRerouteAt < NAVIGATION_REROUTE_COOLDOWN_MS) return false;

    truck.rerouteInFlight = true;
    truck.lastRerouteAt = Date.now();
    try {
        const waypoints = `${location.lat},${location.lng}|${destinationLat},${destinationLng}`;
        const response = await fetch(`https://api.geoapify.com/v1/routing?waypoints=${waypoints}&mode=drive&apiKey=${window.geoapifyKey}`);
        if (!response.ok) throw new Error(`Reroute request failed (${response.status}).`);
        const payload = await response.json();
        const points = extractRouteLatLngs(payload.features?.[0]);
        if (points.length < 2) throw new Error('Reroute returned no usable road geometry.');

        const latestTruck = activeTrucks.get(routeId);
        if (!latestTruck) return false;
        latestTruck.simulationPoints = points;
        latestTruck.originalSimulationPoints = [...points];
        latestTruck.currentPointIndex = 0;
        latestTruck.routeLayer?.setLatLngs(points);
        latestTruck.traveledLayer?.setLatLngs([[location.lat, location.lng]]);
        updateRouteInfoCard();
        showToast('Off-route movement detected. Navigation recalculated.', 'warning');
        return true;
    } catch (error) {
        console.warn('Unable to recalculate navigation route:', error);
        return false;
    } finally {
        const latestTruck = activeTrucks.get(routeId);
        if (latestTruck) latestTruck.rerouteInFlight = false;
    }
}

function updateTruckTraveledPath(truck, lat, lng) {
    if (!truck || !truck.traveledLayer || !Array.isArray(truck.simulationPoints)) {
        return;
    }

    const traveledPoints = [];
    for (let index = 0; index <= truck.currentPointIndex; index++) {
        if (truck.simulationPoints[index]) {
            traveledPoints.push(truck.simulationPoints[index]);
        }
    }

    traveledPoints.push([lat, lng]);

    if (traveledPoints.length > 1) {
        truck.traveledLayer.setLatLngs(traveledPoints);
    }
}

function updateTruckMarkerFromRealtimeLocation(routeId, location) {
    const truck = activeTrucks.get(routeId);
    if (!truck || !truck.animatedVehicle || !location) {
        return false;
    }

    const previousLatLng = truck.animatedVehicle.getLatLng();
    truck.animatedVehicle.setLatLng([location.lat, location.lng]);

    if (previousLatLng && Number.isFinite(previousLatLng.lat) && Number.isFinite(previousLatLng.lng)) {
        const angle = Math.atan2(location.lng - previousLatLng.lng, location.lat - previousLatLng.lat) * 180 / Math.PI;
        if (Number.isFinite(angle)) {
            applyTruckHeading(truck.animatedVehicle, angle);
        }
    }

    truck.currentPointIndex = getClosestSimulationPointIndex(
        truck.simulationPoints,
        location.lat,
        location.lng,
        truck.currentPointIndex || 0
    );
    truck.usesRealtimeTracking = true;
    updateTruckTraveledPath(truck, location.lat, location.lng);
    maybeRerouteNavigation(routeId, location).catch(error => console.warn('Navigation reroute failed:', error));

    const progressPercent = truck.simulationPoints?.length
        ? Math.min((truck.currentPointIndex / Math.max(truck.simulationPoints.length - 1, 1)) * 100, 100)
        : 0;

    const nextSignature = `${location.lat.toFixed(6)}:${location.lng.toFixed(6)}:${truck.currentPointIndex}`;
    const shouldSyncRoute = !truck.routeStatusSyncInFlight
        && (
            !truck.lastRealtimeRouteSyncAt
            || (Date.now() - truck.lastRealtimeRouteSyncAt) >= TRUCK_REALTIME_ROUTE_SYNC_MS
            || truck.lastRealtimeLocationSignature !== nextSignature
        );

    if (shouldSyncRoute) {
        truck.lastRealtimeRouteSyncAt = Date.now();
        truck.lastRealtimeLocationSignature = nextSignature;
        truck.routeStatusSyncInFlight = true;
        updateRouteStatus(routeId, 'moving', truck.currentPointIndex, location.lat, location.lng, progressPercent)
            .catch((error) => {
                console.error('Failed to sync realtime truck status:', error);
            })
            .finally(() => {
                const latestTruck = activeTrucks.get(routeId);
                if (latestTruck) {
                    latestTruck.routeStatusSyncInFlight = false;
                }
            });
    }

    syncTruckArrivalStatus(routeId, location.lat, location.lng).catch((error) => {
        console.error('Failed to sync realtime truck arrival status:', error);
    });

    updateRouteInfoCard();
    return true;
}

function fetchLiveResponseOnce(key, url, options = {}) {
    if (liveRequestRegistry.has(key)) {
        return liveRequestRegistry.get(key).promise;
    }

    const controller = new AbortController();
    const timeoutMs = options.timeoutMs ?? LIVE_FETCH_TIMEOUT_MS;
    const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);
    const { timeoutMs: _timeoutMs, headers = {}, ...restOptions } = options;
    const requestPromise = fetch(url, {
        cache: 'no-store',
        ...restOptions,
        headers,
        signal: controller.signal
    }).finally(() => {
        window.clearTimeout(timeoutId);
        liveRequestRegistry.delete(key);
    });

    liveRequestRegistry.set(key, {
        promise: requestPromise,
        controller
    });
    return requestPromise;
}

window.mapInstances = window.mapInstances || {};
window.activeMapInstanceKey = window.activeMapInstanceKey || 'default';

window.calculateRouteToStation = calculateRouteToStation;
window.activeRoute = null;
// window.userCoords = {
//     currentLatitude: null,
//     currentLongitude: null,
//     ready: false
// };
// WITH this multi-user location storage:
// window.userLocations = {}; // Store all users' locations by user ID
window.currentUserId = window.currentUserId || null; // Will be set from backend

window.userCoords = window.userCoords || {
    currentLatitude: null,
    currentLongitude: null,
    ready: false
};

const DEMO_ROUTE_FALLBACK_ORIGIN = {
    lat: 15.8222,
    lng: 120.3297,
    title: 'Demo dispatch origin'
};

function buildRouteOrigin(lat, lng, options = {}) {
    const numericLat = Number(lat);
    const numericLng = Number(lng);

    return {
        currentLatitude: numericLat,
        currentLongitude: numericLng,
        ready: Number.isFinite(numericLat) && Number.isFinite(numericLng),
        routeSource: options.routeSource || 'real',
        routeSourceLabel: options.routeSourceLabel || (options.routeSource === 'demo' ? 'Demo Route' : 'Live GPS'),
        suppressLiveTracking: Boolean(options.suppressLiveTracking),
        meta: options.meta || null
    };
}

function getRouteSourceBadgeMeta(routeSource) {
    return String(routeSource || 'real').toLowerCase() === 'demo'
        ? { label: 'DEMO', className: 'demo', icon: 'fa-flask' }
        : { label: 'LIVE', className: 'live', icon: 'fa-location-crosshairs' };
}

function updateRouteSourceBadge(routeSource = 'real') {
    const badge = document.getElementById('routeSourceBadge');
    if (!badge) {
        return;
    }

    const meta = getRouteSourceBadgeMeta(routeSource);
    badge.className = `route-source-badge ${meta.className}`;
    badge.innerHTML = `<i class="fas ${meta.icon}"></i><span>${meta.label}</span>`;
}

function getCurrentRouteSource() {
    return String(
        window.activeRoute?.route_source
        || window.activeRoute?.routeSource
        || window.routeDestination?.routeSource
        || 'real'
    ).toLowerCase();
}

function isDemoRouteSource(routeSource) {
    return String(routeSource || '').toLowerCase() === 'demo';
}

async function getFreshRealRouteOrigin() {
    if (window.userCoords?.ready) {
        return buildRouteOrigin(window.userCoords.currentLatitude, window.userCoords.currentLongitude, {
            routeSource: 'real',
            routeSourceLabel: 'Live GPS'
        });
    }

    if (!navigator.geolocation) {
        return null;
    }

    return new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = Number(position.coords.latitude);
                const lng = Number(position.coords.longitude);
                updateUserLocation(window.currentUserId, lat, lng);
                window.userCoords = {
                    currentLatitude: lat,
                    currentLongitude: lng,
                    ready: true
                };
                resolve(buildRouteOrigin(lat, lng, {
                    routeSource: 'real',
                    routeSourceLabel: 'Live GPS'
                }));
            },
            () => resolve(null),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
}

async function getDemoRouteOrigin(destination = null) {
    try {
        await fetchStations({ preferSeeded: true });
    } catch (error) {
        console.warn('Unable to refresh stations for demo route origin:', error);
    }

    const preferredStationId = Number(window.userStationId || 0);
    const preferredStation = Array.isArray(stations)
        ? stations.find((station) => Number(station.station_id) === preferredStationId)
        : null;

    const candidateStation = preferredStation
        || (Array.isArray(stations) ? stations.find((station) => Number.isFinite(Number(station.lat)) && Number.isFinite(Number(station.lng))) : null);

    if (candidateStation) {
        return buildRouteOrigin(candidateStation.lat, candidateStation.lng, {
            routeSource: 'demo',
            routeSourceLabel: 'Demo Route',
            suppressLiveTracking: true,
            meta: {
                title: candidateStation.station_name || 'Demo dispatch origin'
            }
        });
    }

    if (destination && Number.isFinite(Number(destination.lat)) && Number.isFinite(Number(destination.lng))) {
        return buildRouteOrigin(Number(destination.lat) - 0.0125, Number(destination.lng) - 0.0095, {
            routeSource: 'demo',
            routeSourceLabel: 'Demo Route',
            suppressLiveTracking: true,
            meta: {
                title: DEMO_ROUTE_FALLBACK_ORIGIN.title
            }
        });
    }

    return buildRouteOrigin(DEMO_ROUTE_FALLBACK_ORIGIN.lat, DEMO_ROUTE_FALLBACK_ORIGIN.lng, {
        routeSource: 'demo',
        routeSourceLabel: 'Demo Route',
        suppressLiveTracking: true,
        meta: {
            title: DEMO_ROUTE_FALLBACK_ORIGIN.title
        }
    });
}

async function resolveRouteOrigin(mode = 'real', destination = null) {
    if (isDemoRouteSource(mode)) {
        return getDemoRouteOrigin(destination);
    }

    return getFreshRealRouteOrigin();
}

function promptRouteSourceSelection(options = {}) {
    const title = options.title || 'Choose route source';
    const description = options.description || 'Pick whether to start navigation from your live GPS or from a demo dispatch origin.';

    return new Promise((resolve) => {
        const existing = document.getElementById('routeSourceSelectionModal');
        if (existing) {
            existing.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'routeSourceSelectionModal';
        modal.className = 'route-source-modal';
        modal.innerHTML = `
            <div class="route-source-modal__backdrop"></div>
            <div class="route-source-modal__dialog" role="dialog" aria-modal="true" aria-label="${escapeHtml(title)}">
                <div class="route-source-modal__eyebrow">Route Source</div>
                <h3>${escapeHtml(title)}</h3>
                <p>${escapeHtml(description)}</p>
                <div class="route-source-modal__options">
                    <button type="button" class="route-source-option" data-mode="real">
                        <span class="route-source-option__badge live"><i class="fas fa-location-crosshairs"></i> Live</span>
                        <strong>Use real GPS</strong>
                        <small>Starts the route from your current device location.</small>
                    </button>
                    <button type="button" class="route-source-option" data-mode="demo">
                        <span class="route-source-option__badge demo"><i class="fas fa-flask"></i> Demo</span>
                        <strong>Use demo route</strong>
                        <small>Starts from a demo dispatch origin and keeps route playback simulated.</small>
                    </button>
                </div>
                <button type="button" class="route-source-modal__cancel">Cancel</button>
            </div>
        `;

        const cleanup = (value = null) => {
            modal.remove();
            resolve(value);
        };

        modal.querySelector('.route-source-modal__backdrop')?.addEventListener('click', () => cleanup(null));
        modal.querySelector('.route-source-modal__cancel')?.addEventListener('click', () => cleanup(null));
        modal.querySelectorAll('[data-mode]').forEach((button) => {
            button.addEventListener('click', () => cleanup(button.getAttribute('data-mode')));
        });

        document.body.appendChild(modal);
    });
}

function readBoundaryEditorContext() {
    try {
        const params = new URLSearchParams(window.location.search);
        const barangayId = Number(params.get('focus_barangay_id') || '');
        const barangayName = (params.get('focus_barangay_name') || '').trim();
        const cityId = Number(params.get('focus_city_id') || '');
        const cityName = (params.get('focus_city_name') || '').trim();
        const lat = Number(params.get('focus_lat') || '');
        const lng = Number(params.get('focus_lng') || '');
        const openEditor = ['1', 'true', 'yes', 'on'].includes(String(params.get('open_boundary_editor') || '').toLowerCase());

        if (!openEditor && !barangayId && !barangayName && !cityId && !cityName) {
            return null;
        }

        return {
            openEditor,
            barangayId: Number.isFinite(barangayId) && barangayId > 0 ? barangayId : null,
            barangayName: barangayName || null,
            cityId: Number.isFinite(cityId) && cityId > 0 ? cityId : null,
            cityName: cityName || null,
            lat: Number.isFinite(lat) ? lat : null,
            lng: Number.isFinite(lng) ? lng : null,
        };
    } catch (error) {
        console.error('Failed to read boundary editor context:', error);
        return null;
    }
}

boundaryEditorContext = readBoundaryEditorContext();

let boundarySaveModalPromiseResolvers = null;
let cachedBoundaryCities = null;
const cachedBoundaryBarangaysByCity = new Map();
let cachedBoundaryEditorDefaults = null;

function ensureBoundarySaveModal() {
    let modal = document.getElementById('boundarySaveModal');
    if (modal) {
        return modal;
    }

    const style = document.createElement('style');
    style.textContent = `
        .boundary-save-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            padding: 20px;
            background: rgba(15, 23, 42, 0.56);
            backdrop-filter: blur(6px);
        }
        .boundary-save-modal.is-open {
            display: flex;
        }
        .boundary-save-modal__card {
            width: min(100%, 520px);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            overflow: hidden;
        }
        .boundary-save-modal__header {
            padding: 20px 22px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }
        .boundary-save-modal__eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #c2410c;
            margin-bottom: 6px;
        }
        .boundary-save-modal__title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }
        .boundary-save-modal__copy {
            margin: 8px 0 0;
            font-size: 14px;
            color: #475569;
            line-height: 1.55;
        }
        .boundary-save-modal__progress {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            padding: 16px 22px 0;
        }
        .boundary-save-modal__step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.18);
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }
        .boundary-save-modal__step.is-active {
            background: #fff7ed;
            border-color: rgba(249, 115, 22, 0.28);
            color: #9a3412;
        }
        .boundary-save-modal__step-index {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(148, 163, 184, 0.16);
        }
        .boundary-save-modal__step.is-active .boundary-save-modal__step-index {
            background: #ea580c;
            color: #ffffff;
        }
        .boundary-save-modal__body {
            padding: 18px 22px 22px;
        }
        .boundary-save-modal__panel {
            display: none;
        }
        .boundary-save-modal__panel.is-active {
            display: block;
        }
        .boundary-save-modal__label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .boundary-save-modal__input,
        .boundary-save-modal__select {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
        }
        .boundary-save-modal__input:focus,
        .boundary-save-modal__select:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.14);
        }
        .boundary-save-modal__hint {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .boundary-save-modal__summary {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }
        .boundary-save-modal__summary-item strong {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .boundary-save-modal__summary-item span {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .boundary-save-modal__actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 0 22px 22px;
        }
        .boundary-save-modal__actions-right {
            display: flex;
            gap: 10px;
        }
        .boundary-save-modal__button {
            border: none;
            border-radius: 14px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }
        .boundary-save-modal__button--ghost {
            background: #e2e8f0;
            color: #334155;
        }
        .boundary-save-modal__button--secondary {
            background: #fff7ed;
            color: #9a3412;
        }
        .boundary-save-modal__button--primary {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
            color: #ffffff;
        }
        .boundary-save-modal__button[disabled] {
            opacity: 0.65;
            cursor: not-allowed;
        }
        @media (max-width: 640px) {
            .boundary-save-modal__progress {
                grid-template-columns: 1fr;
            }
            .boundary-save-modal__actions {
                flex-direction: column;
            }
            .boundary-save-modal__actions-right {
                justify-content: stretch;
            }
            .boundary-save-modal__actions-right .boundary-save-modal__button,
            .boundary-save-modal__actions .boundary-save-modal__button {
                width: 100%;
            }
        }
    `;
    document.head.appendChild(style);

    modal = document.createElement('div');
    modal.id = 'boundarySaveModal';
    modal.className = 'boundary-save-modal';
    modal.innerHTML = `
        <div class="boundary-save-modal__card" role="dialog" aria-modal="true" aria-labelledby="boundarySaveModalTitle">
            <div class="boundary-save-modal__header">
                <div class="boundary-save-modal__eyebrow">Boundary Setup</div>
                <h3 id="boundarySaveModalTitle" class="boundary-save-modal__title">Save Boundary Shape</h3>
                <p class="boundary-save-modal__copy">Name the shape, select the municipality or city, then connect it to the correct barangay before saving.</p>
            </div>
            <div class="boundary-save-modal__progress">
                <div class="boundary-save-modal__step is-active" data-boundary-step="1"><span class="boundary-save-modal__step-index">1</span><span>Name</span></div>
                <div class="boundary-save-modal__step" data-boundary-step="2"><span class="boundary-save-modal__step-index">2</span><span>Municipality</span></div>
                <div class="boundary-save-modal__step" data-boundary-step="3"><span class="boundary-save-modal__step-index">3</span><span>Barangay</span></div>
            </div>
            <div class="boundary-save-modal__body">
                <section class="boundary-save-modal__panel is-active" data-boundary-panel="1">
                    <label class="boundary-save-modal__label" for="boundaryShapeNameInput">Boundary name</label>
                    <input id="boundaryShapeNameInput" class="boundary-save-modal__input" type="text" maxlength="255" placeholder="Example: Poblacion Boundary">
                    <p class="boundary-save-modal__hint">This is the label users will see when the saved boundary is loaded on the map.</p>
                </section>
                <section class="boundary-save-modal__panel" data-boundary-panel="2">
                    <label class="boundary-save-modal__label" for="boundaryCitySelect">Municipality / City</label>
                    <select id="boundaryCitySelect" class="boundary-save-modal__select">
                        <option value="">Select a municipality or city</option>
                    </select>
                    <p class="boundary-save-modal__hint">Barangays in the next step will be filtered to the selected municipality or city.</p>
                </section>
                <section class="boundary-save-modal__panel" data-boundary-panel="3">
                    <label class="boundary-save-modal__label" for="boundaryBarangaySelect">Barangay</label>
                    <select id="boundaryBarangaySelect" class="boundary-save-modal__select" disabled>
                        <option value="">Select a barangay</option>
                    </select>
                    <p class="boundary-save-modal__hint">Save only after the correct barangay is selected. This connects the drawn shape to boundary CRUD records.</p>
                    <div class="boundary-save-modal__summary" style="margin-top: 14px;">
                        <div class="boundary-save-modal__summary-item">
                            <strong>Name</strong>
                            <span id="boundarySaveSummaryName">-</span>
                        </div>
                        <div class="boundary-save-modal__summary-item">
                            <strong>Municipality / City</strong>
                            <span id="boundarySaveSummaryCity">-</span>
                        </div>
                        <div class="boundary-save-modal__summary-item">
                            <strong>Barangay</strong>
                            <span id="boundarySaveSummaryBarangay">-</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="boundary-save-modal__actions">
                <button type="button" id="boundarySaveCancelBtn" class="boundary-save-modal__button boundary-save-modal__button--ghost">Cancel</button>
                <div class="boundary-save-modal__actions-right">
                    <button type="button" id="boundarySaveBackBtn" class="boundary-save-modal__button boundary-save-modal__button--secondary" style="display:none;">Back</button>
                    <button type="button" id="boundarySaveNextBtn" class="boundary-save-modal__button boundary-save-modal__button--primary">Next</button>
                    <button type="button" id="boundarySaveSubmitBtn" class="boundary-save-modal__button boundary-save-modal__button--primary" style="display:none;">Save Boundary</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    return modal;
}

async function fetchBoundaryCities() {
    if (Array.isArray(cachedBoundaryCities)) {
        return cachedBoundaryCities;
    }

    const response = await fetch('/api/boundaries/cities', {
        headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
        throw new Error(`Failed to load cities (${response.status})`);
    }

    const payload = await response.json();
    cachedBoundaryCities = Array.isArray(payload) ? payload : [];
    return cachedBoundaryCities;
}

async function fetchBoundaryBarangays(cityId) {
    const normalizedCityId = Number(cityId || 0);

    if (!normalizedCityId) {
        return [];
    }

    if (cachedBoundaryBarangaysByCity.has(normalizedCityId)) {
        return cachedBoundaryBarangaysByCity.get(normalizedCityId);
    }

    const response = await fetch(`/api/boundaries/barangays/${normalizedCityId}`, {
        headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
        throw new Error(`Failed to load barangays (${response.status})`);
    }

    const payload = await response.json();
    const barangays = Array.isArray(payload) ? payload : [];
    cachedBoundaryBarangaysByCity.set(normalizedCityId, barangays);
    return barangays;
}

async function fetchBoundaryEditorDefaults() {
    if (cachedBoundaryEditorDefaults) {
        return cachedBoundaryEditorDefaults;
    }

    const response = await fetch('/api/boundaries/editor-context', {
        headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
        throw new Error(`Failed to load boundary editor defaults (${response.status})`);
    }

    const payload = await response.json();
    cachedBoundaryEditorDefaults = payload || {};
    return cachedBoundaryEditorDefaults;
}

async function openBoundarySaveModal({ defaultName = 'My Shape', context = null } = {}) {
    const modal = ensureBoundarySaveModal();
    const nameInput = document.getElementById('boundaryShapeNameInput');
    const citySelect = document.getElementById('boundaryCitySelect');
    const barangaySelect = document.getElementById('boundaryBarangaySelect');
    const summaryName = document.getElementById('boundarySaveSummaryName');
    const summaryCity = document.getElementById('boundarySaveSummaryCity');
    const summaryBarangay = document.getElementById('boundarySaveSummaryBarangay');
    const backBtn = document.getElementById('boundarySaveBackBtn');
    const nextBtn = document.getElementById('boundarySaveNextBtn');
    const submitBtn = document.getElementById('boundarySaveSubmitBtn');
    const cancelBtn = document.getElementById('boundarySaveCancelBtn');
    const stepIndicators = Array.from(modal.querySelectorAll('[data-boundary-step]'));
    const stepPanels = Array.from(modal.querySelectorAll('[data-boundary-panel]'));

    let currentStep = 1;
    let isClosing = false;
    const editorDefaults = await fetchBoundaryEditorDefaults().catch(() => ({}));
    let selectedCityId = context?.cityId || editorDefaults?.default_city_id || null;
    let selectedBarangayId = context?.barangayId || null;
    const shouldLockCity = Boolean(context?.cityId || editorDefaults?.locked_to_station_city);

    const setStep = (step) => {
        currentStep = step;
        stepIndicators.forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.boundaryStep) === step);
        });
        stepPanels.forEach((panel) => {
            panel.classList.toggle('is-active', Number(panel.dataset.boundaryPanel) === step);
        });
        backBtn.style.display = step > 1 ? '' : 'none';
        nextBtn.style.display = step < 3 ? '' : 'none';
        submitBtn.style.display = step === 3 ? '' : 'none';
    };

    const closeWith = (result) => {
        if (isClosing) {
            return;
        }

        isClosing = true;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';

        cancelBtn.onclick = null;
        backBtn.onclick = null;
        nextBtn.onclick = null;
        submitBtn.onclick = null;
        citySelect.onchange = null;

        if (boundarySaveModalPromiseResolvers) {
            const resolvers = boundarySaveModalPromiseResolvers;
            boundarySaveModalPromiseResolvers = null;
            resolvers.resolve(result);
        }
    };

    const syncSummary = () => {
        const selectedCityLabel = citySelect.options[citySelect.selectedIndex]?.text || '-';
        const selectedBarangayLabel = barangaySelect.options[barangaySelect.selectedIndex]?.text || '-';
        summaryName.textContent = nameInput.value.trim() || '-';
        summaryCity.textContent = selectedCityId ? selectedCityLabel : '-';
        summaryBarangay.textContent = selectedBarangayId ? selectedBarangayLabel : '-';
    };

    const fillCityOptions = async () => {
        const cities = await fetchBoundaryCities();
        citySelect.innerHTML = '<option value="">Select a municipality or city</option>';

        cities.forEach((city) => {
            const option = document.createElement('option');
            option.value = String(city.city_id);
            option.textContent = city.province_name
                ? `${city.city_name}, ${city.province_name}`
                : city.city_name;
            option.dataset.cityName = city.city_name || '';
            citySelect.appendChild(option);
        });

        if (selectedCityId) {
            citySelect.value = String(selectedCityId);
        } else if (context?.cityName) {
            const fallbackOption = Array.from(citySelect.options).find((option) => option.dataset.cityName === context.cityName);
            if (fallbackOption) {
                selectedCityId = Number(fallbackOption.value);
                citySelect.value = fallbackOption.value;
            }
        } else if (editorDefaults?.default_city_name) {
            const fallbackOption = Array.from(citySelect.options).find((option) => option.dataset.cityName === editorDefaults.default_city_name);
            if (fallbackOption) {
                selectedCityId = Number(fallbackOption.value);
                citySelect.value = fallbackOption.value;
            }
        }

        citySelect.disabled = shouldLockCity;
    };

    const fillBarangayOptions = async (cityId) => {
        barangaySelect.innerHTML = '<option value="">Select a barangay</option>';
        barangaySelect.disabled = true;
        selectedBarangayId = context?.barangayId || selectedBarangayId;

        if (!cityId) {
            syncSummary();
            return;
        }

        const barangays = await fetchBoundaryBarangays(cityId);
        barangays.forEach((barangay) => {
            const option = document.createElement('option');
            option.value = String(barangay.barangay_id);
            option.textContent = barangay.barangay_name || `Barangay ${barangay.barangay_id}`;
            option.dataset.barangayName = barangay.barangay_name || '';
            barangaySelect.appendChild(option);
        });

        if (selectedBarangayId) {
            barangaySelect.value = String(selectedBarangayId);
        } else if (context?.barangayName) {
            const fallbackOption = Array.from(barangaySelect.options).find((option) => option.dataset.barangayName === context.barangayName);
            if (fallbackOption) {
                selectedBarangayId = Number(fallbackOption.value);
                barangaySelect.value = fallbackOption.value;
            }
        }

        barangaySelect.disabled = false;
        if (barangays.length === 0) {
            barangaySelect.innerHTML = '<option value="">No available barangays without a boundary yet</option>';
            barangaySelect.disabled = true;
        }
        syncSummary();
    };

    nameInput.value = defaultName;
    citySelect.innerHTML = '<option value="">Loading municipalities...</option>';
    barangaySelect.innerHTML = '<option value="">Select a barangay</option>';
    barangaySelect.disabled = true;
    summaryName.textContent = defaultName || '-';
    summaryCity.textContent = '-';
    summaryBarangay.textContent = '-';
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setStep(1);

    const resultPromise = new Promise((resolve, reject) => {
        boundarySaveModalPromiseResolvers = { resolve, reject };
    });

    await fillCityOptions();
    await fillBarangayOptions(selectedCityId);
    syncSummary();

    citySelect.onchange = async function () {
        selectedCityId = Number(this.value || 0) || null;
        selectedBarangayId = null;
        await fillBarangayOptions(selectedCityId);
        syncSummary();
    };

    barangaySelect.onchange = function () {
        selectedBarangayId = Number(this.value || 0) || null;
        syncSummary();
    };

    cancelBtn.onclick = () => closeWith(null);
    backBtn.onclick = () => setStep(Math.max(1, currentStep - 1));
    nextBtn.onclick = async () => {
        if (currentStep === 1) {
            if (!nameInput.value.trim()) {
                showNotification('Please enter a boundary name first.', 'warning');
                nameInput.focus();
                return;
            }
            syncSummary();
            setStep(2);
            return;
        }

        if (currentStep === 2) {
            if (!citySelect.value) {
                showNotification('Select a municipality or city before continuing.', 'warning');
                citySelect.focus();
                return;
            }
            syncSummary();
            setStep(3);
        }
    };

    submitBtn.onclick = () => {
        const selectedBarangayOption = barangaySelect.options[barangaySelect.selectedIndex];

        if (!nameInput.value.trim()) {
            setStep(1);
            showNotification('Please enter a boundary name first.', 'warning');
            return;
        }

        if (!citySelect.value) {
            setStep(2);
            showNotification('Select a municipality or city before saving.', 'warning');
            return;
        }

        if (!barangaySelect.value || !selectedBarangayOption) {
            showNotification('Select the barangay that this boundary belongs to.', 'warning');
            barangaySelect.focus();
            return;
        }

        closeWith({
            name: nameInput.value.trim(),
            city_id: Number(citySelect.value),
            city_name: citySelect.options[citySelect.selectedIndex]?.dataset.cityName || citySelect.options[citySelect.selectedIndex]?.text || '',
            barangay_id: Number(barangaySelect.value),
            barangay_name: selectedBarangayOption.dataset.barangayName || selectedBarangayOption.text || '',
        });
    };

    return resultPromise;
}


// ============================================================================
// ROUTE DISPLAY ELEMENTS
// ============================================================================
//

// Add these functions to your map.js file (before they are called)

// Store all user locations
window.userLocations = window.userLocations || {};

// Function to update a user's location
function updateUserLocation(userId, latitude, longitude) {
    if (!window.userLocations) window.userLocations = {};
    
    window.userLocations[userId] = {
        lat: latitude,
        lng: longitude,
        lastUpdated: new Date(),
        ready: true
    };
    
    // For backward compatibility with current user
    if (userId == window.currentUserId) {
        window.userCoords = {
            currentLatitude: latitude,
            currentLongitude: longitude,
            ready: true
        };
    }
    
    console.log(`Updated location for user ${userId}: ${latitude}, ${longitude}`);
    return true;
}

// Function to get a user's location
function getUserLocation(userId) {
    if (window.userLocations && window.userLocations[userId]) {
        return window.userLocations[userId];
    }
    return null;
}

// Function to get all user locations
function getAllUserLocations() {
    return window.userLocations || {};
}

// Function to send location to server
async function sendUserLocationToServer(latitude, longitude) {
    if (!window.currentUserId) {
        console.warn('No current user ID, skipping location update');
        return;
    }

    const nextLocationSignature = `${Number(latitude).toFixed(6)}:${Number(longitude).toFixed(6)}`;
    if (
        nextLocationSignature === lastUserLocationSentSignature
        && (Date.now() - lastUserLocationSentAt) < LOCATION_WRITE_DEDUP_WINDOW_MS
    ) {
        return;
    }
    
    try {
        const response = await fetch('/firefighter/api/location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                latitude: latitude,
                longitude: longitude,
                timestamp: new Date().toISOString()
            })
        });
        
        const result = await response.json();
        console.log('Location API response:', result);
        
        if (result.success) {
            lastUserLocationSentSignature = nextLocationSignature;
            lastUserLocationSentAt = Date.now();
            console.log('Location saved to server successfully');
            return result;
        } else {
            console.error('Server returned error:', result.message);
            return null;
        }
    } catch (error) {
        console.error('Error sending location to server:', error);
        return null;
    }
}

// Function to fetch all firefighters' locations
async function fetchAllFirefighterLocations() {
    try {
        const response = await fetchLiveResponseOnce('firefighter-locations', '/api/firefighter/locations', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) {
            console.warn('Location API returned status:', response.status);
            return null;
        }
        
        const data = await response.json();
        
        if (data.success && data.locations) {
            data.locations.forEach(loc => {
                if (loc.user_id && loc.latitude && loc.longitude) {
                    updateUserLocation(loc.user_id, loc.latitude, loc.longitude);
                }
            });
            displayAllFirefighterLocations();
        }
        
        return data;
    } catch (error) {
        if (error?.name === 'AbortError') {
            return null;
        }
        console.error('Error fetching firefighter locations:', error);
        return null;
    }
}

// Function to display all firefighters on map
function displayAllFirefighterLocations() {
    if (!window.map) return;
    
    // Clear existing firefighter markers
    if (window.firefighterMarkers) {
        Object.keys(window.firefighterMarkers).forEach(userId => {
            if (userId != window.currentUserId && window.firefighterMarkers[userId]) {
                window.map.removeLayer(window.firefighterMarkers[userId]);
            }
        });
    }
    window.firefighterMarkers = window.firefighterMarkers || {};
    
    // Display each user's location
    Object.keys(window.userLocations).forEach(userId => {
        const location = window.userLocations[userId];
        if (location && location.ready && location.lat && location.lng) {

            // FIX: Convert lat and lng to numbers if they are strings
            const lat = parseFloat(location.lat);
            const lng = parseFloat(location.lng);
            
            // Skip if invalid coordinates
            if (isNaN(lat) || isNaN(lng)) {
                console.warn(`Invalid coordinates for user ${userId}:`, location.lat, location.lng);
                return;
            }
            
            // Skip if already has a marker
            if (window.firefighterMarkers[userId]) {
                // Update existing marker position
                window.firefighterMarkers[userId].setLatLng([lat,lng]);
                return;
            }
            
            const isCurrentUser = (userId == window.currentUserId);
            const iconColor = isCurrentUser ? '#3b82f6' : '#10b981';
            
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'firefighter-location-marker',
                    html: `<div style="
                        width: ${isCurrentUser ? '24px' : '20px'};
                        height: ${isCurrentUser ? '24px' : '20px'};
                        background-color: ${iconColor};
                        border: 3px solid white;
                        border-radius: 50%;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="fas fa-user" style="color: white; font-size: ${isCurrentUser ? '12px' : '10px'};"></i>
                    </div>`,
                    iconSize: [isCurrentUser ? 24 : 20, isCurrentUser ? 24 : 20],
                    iconAnchor: [12, 12]
                })
            }).bindPopup(`${isCurrentUser ? 'You' : `Firefighter ${userId}`}<br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`);
            
            marker.addTo(window.map);
            window.firefighterMarkers[userId] = marker;
        }
    });

    refreshRealtimeTrackedTrucks();
}

function getIncidentTypeName(typeId) {
    return 'Fire';
}

function resolveIncidentTypeLabel(marker = {}) {
    if (isIncidentMarker(marker) || String(marker?.category || '').trim().toLowerCase() === 'fire') {
        return 'Fire';
    }

    return 'Incident';
}

function getMarkerCategoryKey(markerOrCategory = null) {
    if (markerOrCategory && typeof markerOrCategory === 'object') {
        const marker = markerOrCategory;
        const directCategory = String(marker.category || '').trim().toLowerCase();
        if (directCategory) {
            if (directCategory === 'fire') return 'incident';
            return directCategory;
        }

        const relationCategory = String(marker.category?.name || marker.category || '').trim().toLowerCase();
        if (relationCategory) {
            if (relationCategory.includes('station')) return 'station';
            if (relationCategory.includes('incident') || relationCategory.includes('fire')) return 'incident';
            if (relationCategory.includes('hydrant')) return 'hydrant';
            if (relationCategory.includes('school')) return 'school';
            if (relationCategory.includes('barangay')) return 'barangay';
            if (relationCategory.includes('bridge')) return 'bridge';
            if (relationCategory.includes('hazard')) return 'hazard';
            if (relationCategory.includes('risk')) return 'risk';
        }

        if (marker.markerCategory_id !== undefined && marker.markerCategory_id !== null) {
            return getMarkerCategoryKey(marker.markerCategory_id);
        }
    }

    const categoryId = parseInt(markerOrCategory, 10);
    switch (categoryId) {
        case 1: return 'station';
        case 2:
        case 3: return 'incident';
        case 4: return 'hydrant';
        case 5: return 'school';
        case 6: return 'barangay';
        case 8: return 'risk';
        case 9: return 'hazard';
        default: return 'other';
    }
}

function getNormalizedCategoryId(markerOrCategory = null) {
    const categoryIdMap = {
        station: 1,
        incident: 2,
        hydrant: 4,
        school: 5,
        barangay: 6,
        bridge: 6,
        risk: null,
        hazard: null,
        other: null
    };

    return categoryIdMap[getMarkerCategoryKey(markerOrCategory)] ?? null;
}

function isStationMarker(marker = {}) {
    return getMarkerCategoryKey(marker) === 'station';
}

function isIncidentMarker(marker = {}) {
    return getMarkerCategoryKey(marker) === 'incident';
}

function isHydrantMarker(marker = {}) {
    return getMarkerCategoryKey(marker) === 'hydrant';
}

function isSchoolMarker(marker = {}) {
    return getMarkerCategoryKey(marker) === 'school';
}

function getMarkerStatusValue(marker = {}) {
    return String(marker?.status || '').trim().toLowerCase() || 'active';
}

function isActiveFireIncidentMarker(marker = {}) {
    if (!isIncidentMarker(marker)) {
        return false;
    }

    if (getMarkerStatusValue(marker) === 'inactive') {
        return false;
    }

    const incidentStatus = normalizeIncidentStatus(marker?.incident_status || marker?.status || 'reported');

    return ['reported', 'en-route', 'on-scene'].includes(incidentStatus);
}

function shouldDisplayMarker(marker = {}) {
    const status = getMarkerStatusValue(marker);

    if (status === 'inactive') {
        return false;
    }

    return true;
}

function normalizeIncidentStatus(input = '') {
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
    if (normalized === 'controlled' || normalized === 'completed') return 'controlled';
    if (normalized === 'closed') return 'closed';
    return 'reported';
}

function getIncidentTypePresentation(input = {}) {
    const marker = typeof input === 'object' ? input : {};
    const incidentTypeLabel = (typeof input === 'string' ? input : resolveIncidentTypeLabel(input)).toLowerCase();
    const incidentStatus = normalizeIncidentStatus(marker?.incident_status || marker?.status || '');
    const presentation = {
        label: 'Fire',
        iconClass: 'fa-fire',
        bgColor: '#ef4444',
        borderColor: '#ef4444'
    };

    if (incidentStatus === 'controlled') {
        presentation.label = 'Investigation';
        presentation.iconClass = 'fa-fire';
        presentation.bgColor = '#10b981';
        presentation.borderColor = '#10b981';
        return presentation;
    }

    if (incidentStatus === 'on-scene') {
        presentation.bgColor = '#f59e0b';
        presentation.borderColor = '#facc15';
        return presentation;
    }

    if (incidentStatus === 'en-route') {
        presentation.bgColor = '#f97316';
        presentation.borderColor = '#fb923c';
        return presentation;
    }

    if (incidentTypeLabel.includes('fire')) {
        presentation.bgColor = '#dc2626';
        presentation.borderColor = '#ef4444';
    }

    return presentation;
}

function createIncidentTypeIcon(marker = {}, extraClass = '') {
    const { iconClass, bgColor } = getIncidentTypePresentation(marker);
    const imagePath = inferMarkerImagePath(marker, { id: 'incident' });
    const markerSize = 42;
    const markerAnchor = 21;
    const isActiveIncident = isActiveFireIncidentMarker(marker);

    return L.divIcon({
        className: `custom-fa-icon pyro-map-marker-icon incident-type-icon ${isActiveIncident ? 'incident-type-icon--active' : ''} ${extraClass}`.trim(),
        html: `
            <div class="incident-marker ${isActiveIncident ? 'incident-marker--active' : ''}" style="width:${markerSize}px;height:${markerSize}px;">
                ${isActiveIncident ? `
                    <span class="incident-marker__pulse"></span>
                    <span class="incident-marker__pulse incident-marker__pulse--delay"></span>
                ` : ''}
                ${buildImageBackedMarkerHtml({
                    width: markerSize,
                    height: markerSize,
                    borderRadius: '50%',
                    imagePath,
                    fallbackIconClass: iconClass,
                    bgColor,
                    iconFontSize: 18,
                    wrapperClass: 'incident-marker__icon'
                })}
            </div>
        `,
        iconSize: [markerSize, markerSize],
        iconAnchor: [markerAnchor, markerAnchor],
        popupAnchor: [0, -24]
    });
}

function createRouteInfoCard() {
    if (document.getElementById('routeInfoCard')) {
        return;
    }
    
    const configuredContainerId = window.mapInstanceConfig?.containerId || 'map';
    const mapContainer = document.getElementById(configuredContainerId);
    if (!mapContainer) return;
    
    const routeCard = document.createElement('div');
    routeCard.id = 'routeInfoCard';
    routeCard.className = 'route-info-card';
    routeCard.style.cssText = `
        position: absolute;
        bottom: 20px;
        left: 20px;
        right: 20px;
        background: white;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        max-width: 400px;
        border-left: 4px solid #3b82f6;
    `;
    
    routeCard.innerHTML = `
        <div class="route-info-card__handle" aria-hidden="true"></div>
        <div class="route-card-header">
            <h4>
                <i class="fas fa-route"></i>
                Active routes
            </h4>
            <button id="stopAllSimulationsBtn" class="text-sm text-red-600 hover:text-red-700" style="background: none; border: none; cursor: pointer;">
                <i class="fas fa-stop"></i> Stop All
            </button>
        </div>
        <div id="activeRoutesList" style="max-height: 300px; overflow-y: auto;">
            <div style="text-align: center; padding: 20px; color: #6b7280;">No active routes</div>
        </div>
    `;
    
    const mapParent = mapContainer.parentElement;
    if (mapParent) {
        mapParent.style.position = 'relative';
        mapParent.appendChild(routeCard);
    } else {
        document.body.appendChild(routeCard);
    }
    
    setTimeout(() => {
        const stopAllBtn = document.getElementById('stopAllSimulationsBtn');
        if (stopAllBtn) {
            stopAllBtn.addEventListener('click', () => {
                stopAllSimulations();
            });
        }
    }, 100);
}

const BARANGAY_GROUP_ICON_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="9" r="2" fill="none"/><circle cx="16" cy="9" r="2" fill="none"/><circle cx="12" cy="7" r="2" fill="none"/><path d="M4 18a4 4 0 0 1 8 0M12 18a4 4 0 0 1 8 0M8 16a4 4 0 0 1 8 0"/></svg>';

function createLegendItemHtml({ iconClass = null, iconHtml = null, imagePath = null, bgColor, label, shape = 'circle' }) {
    return `
        <div class="ff-clean-map-legend__item">
            <span class="ff-clean-map-legend__icon ${shape === 'square' ? 'is-square' : 'is-circle'}" style="border-color:${bgColor};">
                ${imagePath
                    ? `<img src="${imagePath}" alt="" onerror="this.style.display='none'; const fallback=this.nextElementSibling; if(fallback){fallback.style.display='inline-flex';}"><span class="ff-clean-map-legend__fallback" style="color:${bgColor};">${iconHtml ? iconHtml : (iconClass ? `<i class="fas ${iconClass}"></i>` : '')}</span>`
                    : (iconHtml ? `<span class="ff-clean-map-legend__fallback is-visible" style="color:${bgColor};">${iconHtml}</span>` : (iconClass ? `<i class="fas ${iconClass}" style="color:${bgColor};"></i>` : ''))
                }
            </span>
            <span class="ff-clean-map-legend__label">${label}</span>
        </div>
    `;
}

function redrawRiskHeatLayers() {
    [currentRiskAnalysisLayerGroup, nextMonthForecastLayerGroup].forEach((group) => {
        group?.eachLayer?.((layer) => layer?._reset?.());
    });
}

function bindChiefRiskHeatBlendControl(container) {
    const input = container?.querySelector('[data-risk-heat-blend]');
    const output = container?.querySelector('[data-risk-heat-blend-output]');
    if (!input || !output) return;

    input.value = String(riskHeatBlendMeters);
    output.textContent = `${riskHeatBlendMeters} m`;
    input.addEventListener('input', () => {
        riskHeatBlendMeters = Math.min(200, Math.max(50, Number(input.value) || 100));
        output.textContent = `${riskHeatBlendMeters} m`;
        try {
            window.localStorage?.setItem(RISK_HEAT_BLEND_STORAGE_KEY, String(riskHeatBlendMeters));
        } catch (error) {
            console.warn('Unable to remember heat blend distance:', error);
        }
        redrawRiskHeatLayers();
    });
}

function createFirefighterMapLegend() {
    if (window.activeMapInstanceKey !== 'firefighterDashboard') {
        return;
    }

    const legendContainer = document.getElementById('firefighterMapLegend');
    if (!legendContainer) {
        return;
    }

    legendContainer.innerHTML = `
        <div class="ff-clean-map-legend__title">Map Legend</div>
        <div class="ff-clean-map-legend__items">
            ${createLegendItemHtml({ iconClass: 'fa-building', imagePath: '/station.png', bgColor: '#2563eb', label: 'Fire Station' })}
            ${createLegendItemHtml({ bgColor: '#0f766e', imagePath: '/hall.png', label: 'Barangay Hall', iconHtml: BARANGAY_GROUP_ICON_SVG })}
            ${createLegendItemHtml({ iconClass: 'fa-fire-extinguisher', imagePath: '/hydrant.png', bgColor: '#ef4444', label: 'Hydrant', shape: 'square' })}
            ${createLegendItemHtml({ ...getIncidentTypePresentation('Fire'), imagePath: '/fire.png' })}
            ${createLegendItemHtml({ iconClass: 'fa-triangle-exclamation', imagePath: '/hazard.png', bgColor: '#c2410c', label: 'Risk & Hazard Report' })}
            ${createLegendItemHtml({ iconClass: 'fa-school', bgColor: '#eab308', label: 'School' })}
            ${createLegendItemHtml({ iconClass: 'fa-location-dot', bgColor: '#64748b', label: 'Other Marker' })}
            ${createLegendItemHtml({ iconClass: 'fa-location-arrow', bgColor: '#2563eb', label: 'Navigation Arrow' })}
            ${createLegendItemHtml({ iconClass: 'fa-truck-fast', bgColor: '#b42318', label: 'Dispatched Apparatus' })}
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-low"></span>
                <span class="ff-clean-map-legend__label">Low Analysis Heat</span>
            </div>
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-medium"></span>
                <span class="ff-clean-map-legend__label">Medium Analysis Heat</span>
            </div>
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-high"></span>
                <span class="ff-clean-map-legend__label">High Analysis Heat</span>
            </div>
        </div>
    `;
}

function createChiefMapLegend() {
    const legendContainer = document.getElementById('chiefMapLegend');
    if (!legendContainer) {
        return;
    }

    legendContainer.innerHTML = `
        <div class="ff-clean-map-legend__title">Map Legend</div>
        <div class="ff-clean-map-legend__items">
            ${createLegendItemHtml({ iconClass: 'fa-building', imagePath: '/station.png', bgColor: '#2563eb', label: 'Fire Station' })}
            ${createLegendItemHtml({ bgColor: '#0f766e', imagePath: '/hall.png', label: 'Barangay Hall', iconHtml: BARANGAY_GROUP_ICON_SVG })}
            ${createLegendItemHtml({ iconClass: 'fa-fire-extinguisher', imagePath: '/hydrant.png', bgColor: '#ef4444', label: 'Hydrant', shape: 'square' })}
            ${createLegendItemHtml({ ...getIncidentTypePresentation('Fire'), imagePath: '/fire.png' })}
            ${createLegendItemHtml({ iconClass: 'fa-triangle-exclamation', imagePath: '/hazard.png', bgColor: '#c2410c', label: 'Risk & Hazard Report' })}
            ${createLegendItemHtml({ iconClass: 'fa-school', bgColor: '#eab308', label: 'School' })}
            ${createLegendItemHtml({ iconClass: 'fa-location-dot', bgColor: '#64748b', label: 'Other Marker' })}
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-low"></span>
                <span class="ff-clean-map-legend__label">Low Analysis Heat</span>
            </div>
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-medium"></span>
                <span class="ff-clean-map-legend__label">Medium Analysis Heat</span>
            </div>
            <div class="ff-clean-map-legend__item">
                <span class="ff-clean-map-legend__icon is-square is-risk-high"></span>
                <span class="ff-clean-map-legend__label">High Analysis Heat</span>
            </div>
        </div>
        <div class="ff-risk-heat-control">
            <div class="ff-risk-heat-control__head"><strong>Heat blending distance</strong><output data-risk-heat-blend-output>${riskHeatBlendMeters} m</output></div>
            <input type="range" min="50" max="200" step="25" value="${riskHeatBlendMeters}" data-risk-heat-blend aria-label="Analysis heat blending distance in meters">
            <small>Nearby evidence blends when zoomed out and separates as you zoom in.</small>
        </div>
    `;
    bindChiefRiskHeatBlendControl(legendContainer);
}

function updateRouteInfoCard() {
    const routeCard = document.getElementById('routeInfoCard');
    if (activeTrucks.size === 0) {
        if (routeCard) routeCard.style.display = 'none';
        return;
    }

    const primaryTruck = Array.from(activeTrucks.values()).at(-1);
    const destinationEl = document.getElementById('destinationTitle');
    const progressEl = document.getElementById('routeProgress');
    const trackingEl = document.getElementById('routeTrackingMode');
    if (destinationEl) {
        destinationEl.textContent = primaryTruck?.routeDestination?.title || 'Selected destination';
    }
    if (progressEl) {
        const pointCount = primaryTruck?.simulationPoints?.length || 0;
        const progress = pointCount > 1
            ? Math.min(100, Math.max(0, (Number(primaryTruck.currentPointIndex || 0) / (pointCount - 1)) * 100))
            : 0;
        progressEl.style.width = `${progress}%`;
    }
    if (trackingEl) {
        trackingEl.textContent = isDemoRouteSource(primaryTruck?.routeSource)
            ? 'Demo route playback'
            : (primaryTruck?.usesRealtimeTracking ? 'Tracking apparatus from live GPS' : 'Following the calculated GPS route');
    }
    updateRouteSourceBadge(primaryTruck?.routeSource || 'real');
    if (routeCard) routeCard.style.display = 'block';

    const routesList = document.getElementById('activeRoutesList');
    if (!routesList) return;
    
    let html = '';
    let index = 1;
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    
    for (const [routeId, truck] of activeTrucks) {
        const color = truckColors[index % truckColors.length];
        const progress = truck.currentPointIndex && truck.simulationPoints ? 
            Math.round((truck.currentPointIndex / truck.simulationPoints.length) * 100) : 0;
        
        html += `
            <div style="border-left: 3px solid ${color}; padding: 8px 12px; margin-bottom: 8px; background: #f9fafb; border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; font-size: 13px;">
                        <i class="fa-solid fa-location-arrow" style="color: ${color};"></i>
                        Navigation ${index}
                    </span>
                    <button onclick="stopSimulationForRoute(${routeId})" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 12px;">
                        <i class="fas fa-times"></i> Stop
                    </button>
                </div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                    Destination: ${truck.routeDestination?.title || 'Unknown'}
                </div>
                <div style="margin-top: 4px;">
                    <div style="background: #e5e7eb; border-radius: 10px; height: 4px; overflow: hidden;">
                        <div style="width: ${progress}%; height: 100%; background: ${color}; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>
        `;
        index++;
    }
    
    routesList.innerHTML = html;
    if (routeCard) routeCard.style.display = 'block';
}

// ============================================================================
// ROUTE DATABASE FUNCTIONS
// ============================================================================

async function saveRouteToDatabase(route, destination, origin = null) {
    try {
        const routeSource = origin?.routeSource || destination?.routeSource || 'real';
        const response = await fetch('/api/route', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                coordinates: JSON.stringify(route.geometry.coordinates),
                total_distance: route.properties.distance,
                estimated_time: route.properties.time,
                status: 'active',
                route_source: routeSource,
                destination_title: destination.title,
                destination_lat: destination.lat,
                destination_lng: destination.lng,
                origin_lat: origin?.currentLatitude ?? null,
                origin_lng: origin?.currentLongitude ?? null,
                incident_id: window.thisIncidents ?? destination?.incidentId ?? null
            })
        });
        
        const result = await response.json();
        if (!response.ok || result.success === false) {
            console.error('Route save API error:', result);
            if (response.status === 409) {
                showToast(result.message || 'Your team is already committed to another active incident.', 'warning');
            }
            return null;
        }
        console.log('Route saved to database:', result);
        return result;
    } catch (error) {
        console.error('Failed to save route to database:', error);
        return null;
    }
}

async function fetchActiveRouteFromDatabase() {
    try {
        const response = await fetchLiveResponseOnce('route-active-single', '/api/route/active');
        if (!response.ok) return null;
        
        const routes = await response.json();
        const activeRouteData = routes.find(route => route.status === 'active' || route.status === 'moving');
        
        if (activeRouteData) {
            console.log('Found active route in database:', activeRouteData);
            return activeRouteData;
        }
        return null;
    } catch (error) {
        if (error?.name === 'AbortError') {
            return null;
        }
        console.error('Failed to fetch active route:', error);
        return null;
    }
}

async function updateRouteStatus(routeId, status, positionIndex = null, lat = null, lng = null, progress = null) {
    try {
        const body = { status: status };
        if (positionIndex !== null) body.current_position_index = positionIndex;
        if (lat !== null && lng !== null) { body.current_lat = lat; body.current_lng = lng; }
        if (progress !== null) body.progress_percentage = progress;
        
        const response = await fetch(`/api/route/${routeId}/status`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(body)
        });
        
        const result = await response.json();
        console.log(`Route ${routeId} status updated to ${status}:`, result);
        if (!response.ok || result?.success === false) {
            throw new Error(result?.error || result?.message || `Failed to update route ${routeId} to ${status}`);
        }
        return result;
    } catch (error) {
        console.error('Failed to update route status:', error);
        throw error;
    }
}

async function clearActiveRoute() {
    try {
        const response = await fetch('/api/route/clear-active', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const result = await response.json();
        console.log('Active route cleared:', result);
        return result;
    } catch (error) {
        console.error('Failed to clear active route:', error);
    }
}

// ============================================================================
// MAP INITIALIZATION
// ============================================================================

function initializePyroMapRuntime() {
    const configuredContainerId = window.mapInstanceConfig?.containerId || 'map';
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || 'default';
    const mapContainer = document.getElementById(configuredContainerId);
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
    }

    const existingMapInstance = window.mapInstances?.[configuredInstanceKey];
    if (existingMapInstance) {
        const existingContainer = existingMapInstance._container;
        const isSameContainer = existingContainer === mapContainer;
        const isExistingContainerMounted = !!(existingContainer && existingContainer.isConnected);

        if (!isSameContainer || !isExistingContainerMounted) {
            try {
                existingMapInstance.remove();
            } catch (error) {
                console.warn('Unable to remove stale map instance cleanly:', error);
            }

            delete window.mapInstances[configuredInstanceKey];

            if (window.activeMapInstanceKey === configuredInstanceKey) {
                window.map = null;
                window.activeMapInstanceKey = null;
            }
        }
    }

    if (window.mapInstances?.[configuredInstanceKey]) {
        window.map = window.mapInstances[configuredInstanceKey];
        window.activeMapInstanceKey = configuredInstanceKey;

        setTimeout(() => window.map?.invalidateSize(), 0);
        setTimeout(() => window.map?.invalidateSize(), 180);
        setTimeout(() => window.map?.invalidateSize(), 420);
        return window.map;
    }
    
    const initializedMap = L.map(configuredContainerId, {
        zoomControl: true,
        zoomAnimation: true,
        fadeAnimation: true,
        markerZoomAnimation: true
    }).setView([15.8222, 120.3297], 13);

    window.map = initializedMap;
    window.mapInstances[configuredInstanceKey] = initializedMap;
    window.activeMapInstanceKey = configuredInstanceKey;

    if (configuredInstanceKey === 'firefighterDashboard') {
        window.firefighterDashboardMap = initializedMap;
    }
    
    stationFeatureGroup = L.featureGroup().addTo(window.map);
    incidentFeatureGroup = L.featureGroup().addTo(window.map);
    hydrantFeatureGroup = L.featureGroup().addTo(window.map);
    riskHazardFeatureGroup = L.featureGroup().addTo(window.map);
    otherFeatureGroup = L.featureGroup().addTo(window.map);
    
    window.stationFeatureGroup = stationFeatureGroup;
    window.incidentFeatureGroup = incidentFeatureGroup;
    window.hydrantFeatureGroup = hydrantFeatureGroup;
    window.riskHazardFeatureGroup = riskHazardFeatureGroup;
    window.otherFeatureGroup = otherFeatureGroup;
    
    window.setName = setName;
    window.routesGroup = L.layerGroup().addTo(window.map);
    
    initAfterMapReady();

    fetchStations({
        preferSeeded: true,
        revalidate: !isFirefighterDashboardMapInstance() && !isChiefDashboardMapInstance()
    });
    fetchCoordinate({
        preferSeeded: true,
        revalidate: true
    });
    
    // Ensure the map repaints correctly after layout settles.
    setTimeout(() => window.map?.invalidateSize(), 0);
    setTimeout(() => window.map?.invalidateSize(), 250);
    setTimeout(() => window.map?.invalidateSize(), 600);
    
    bindMapPollingLifecycle();
    startMapLivePolling();
    return initializedMap;
}

window.initializePyroMapRuntime = initializePyroMapRuntime;

document.addEventListener('DOMContentLoaded', function() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || 'default';

    if (window.deferPyroMapInit === true && configuredInstanceKey === 'firefighterDashboard') {
        return;
    }

    initializePyroMapRuntime();
});

// Colors for shapes
const colors = ["#ef4444", "#3b82f6", "#22c55e", "#f59e0b", "#8b5cf6", "#06b6d4"];

// Global variables that need to be accessible
let stationFeatureGroup, incidentFeatureGroup, hydrantFeatureGroup, otherFeatureGroup;
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const roleToken = document.querySelector('meta[name="user-role-id"]')?.getAttribute('content');

const roleName = String(document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').trim().toLowerCase()
    || (
        window.location.pathname.includes('/firefighter') ? 'firefighter'
            : window.location.pathname.includes('/station-admin') ? 'chief'
            : window.location.pathname.includes('/citizen') ? 'citizen'
            : ''
    );
function resolveCurrentUserMapId() {
    const metaUserId = String(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '').trim();
    if (metaUserId) {
        return metaUserId;
    }

    const runtimeUserId = window.currentUserId ?? window.currentCitizenId ?? null;
    if (runtimeUserId !== null && runtimeUserId !== undefined && String(runtimeUserId).trim() !== '') {
        return String(runtimeUserId).trim();
    }

    return 'guest';
}

const currentUserMetaId = resolveCurrentUserMapId();
const currentStationMetaId = String(document.querySelector('meta[name="station-id"]')?.getAttribute('content') || window.userStationId || '').trim();
window.thisToken = roleToken;

function getMarkerFeedEndpoint() {
    return resolveCurrentUserMapId() !== 'guest' ? '/map/markers/feed' : '/api/markers';
}

const canManageMarkers = ['admin', 'provincial admin', 'chief'].includes(roleName)
    || [2, 3, 4].includes(Number(roleToken));
const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
window.geoapifyKey = apiKey;
window.currentDisplayedRouteId = null;

const INCIDENT_ALERT_FEED_STORAGE_KEY = `pyrosense_incident_alert_feed_${roleName || 'guest'}_${currentUserMetaId}`;
const INCIDENT_ALERT_SEEN_STORAGE_KEY = `pyrosense_incident_alert_seen_${roleName || 'guest'}_${currentUserMetaId}`;
const INCIDENT_ALERT_PANEL_ID = 'incidentAlertCenterPanel';
const INCIDENT_ALERT_LIST_ID = 'incidentAlertCenterList';
const INCIDENT_ALERT_TOGGLE_ID = 'incidentAlertCenterToggle';
const INCIDENT_ALERT_BADGE_ID = 'incidentAlertCenterBadge';
const INCIDENT_ALERT_AUDIO_UNLOCK_EVENTS = ['pointerdown', 'touchstart', 'keydown'];
let incidentAlertAudioContext = null;
let incidentAlertPermissionRequested = false;
let incidentAlertScopeInitialization = new Set();

function safeReadJsonFromStorage(key, fallback) {
    try {
        const raw = window.localStorage?.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch (error) {
        console.warn('Unable to read local storage key:', key, error);
        return fallback;
    }
}

function safeWriteJsonToStorage(key, value) {
    try {
        window.localStorage?.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.warn('Unable to write local storage key:', key, error);
    }
}

function getStaticMapCacheKey(scope) {
    return `pyrosense_static_map_${scope}_${window.activeMapInstanceKey || 'default'}`;
}

function readStaticMapCache(scope, maxAgeMs = STATIC_MAP_CACHE_TTL_MS) {
    const cachedEntry = safeReadJsonFromStorage(getStaticMapCacheKey(scope), null);

    if (!cachedEntry || !Array.isArray(cachedEntry.data)) {
        return null;
    }

    if (!cachedEntry.savedAt || (Date.now() - cachedEntry.savedAt) > maxAgeMs) {
        return null;
    }

    return cachedEntry.data;
}

function writeStaticMapCache(scope, data) {
    if (!Array.isArray(data)) {
        return;
    }

    safeWriteJsonToStorage(getStaticMapCacheKey(scope), {
        savedAt: Date.now(),
        data
    });
}

function clearStaticMapCache(scope) {
    try {
        window.localStorage?.removeItem(getStaticMapCacheKey(scope));
    } catch (error) {
        console.warn('Unable to clear local storage key:', getStaticMapCacheKey(scope), error);
    }
}

function loadIncidentAlertFeed() {
    const feed = safeReadJsonFromStorage(INCIDENT_ALERT_FEED_STORAGE_KEY, []);
    return Array.isArray(feed) ? feed : [];
}

function saveIncidentAlertFeed(feed) {
    safeWriteJsonToStorage(INCIDENT_ALERT_FEED_STORAGE_KEY, Array.isArray(feed) ? feed.slice(0, 30) : []);
}

function loadIncidentAlertSeenMap() {
    const seenMap = safeReadJsonFromStorage(INCIDENT_ALERT_SEEN_STORAGE_KEY, {});
    return seenMap && typeof seenMap === 'object' ? seenMap : {};
}

function saveIncidentAlertSeenMap(seenMap) {
    safeWriteJsonToStorage(INCIDENT_ALERT_SEEN_STORAGE_KEY, seenMap && typeof seenMap === 'object' ? seenMap : {});
}

function formatIncidentAlertRelativeTime(dateValue) {
    const date = dateValue ? new Date(dateValue) : null;
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
        return 'Just now';
    }

    const diffMs = Date.now() - date.getTime();
    const diffMinutes = Math.max(0, Math.round(diffMs / 60000));

    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes} min ago`;

    const diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours} hr ago`;

    const diffDays = Math.round(diffHours / 24);
    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
}

function getIncidentAlertTypeMeta(incident = {}) {
    const normalizedType = String(
        incident?.incident_type_name
        || incident?.incident_type
        || incident?.marker?.category
        || incident?.category
        || 'incident'
    ).trim().toLowerCase();

    if (normalizedType.includes('fire')) {
        return { label: 'Fire Incident', icon: 'fa-fire', iconClass: 'text-red-600 bg-red-100' };
    }
    if (normalizedType.includes('medical')) {
        return { label: 'Medical Incident', icon: 'fa-truck-medical', iconClass: 'text-blue-600 bg-blue-100' };
    }
    if (normalizedType.includes('rescue')) {
        return { label: 'Rescue Incident', icon: 'fa-life-ring', iconClass: 'text-emerald-600 bg-emerald-100' };
    }

    return { label: 'Emergency Incident', icon: 'fa-triangle-exclamation', iconClass: 'text-amber-600 bg-amber-100' };
}

function getIncidentAlertStatusLabel(incident = {}) {
    const normalized = normalizeIncidentStatus(
        incident?.incident_status
        || incident?.status
        || 'reported'
    );

    switch (normalized) {
        case 'en-route':
            return 'Responding';
        case 'on-scene':
            return 'On Scene';
        case 'controlled':
            return 'Controlled';
        case 'closed':
            return 'Closed';
        default:
            return 'Reported';
    }
}

function getIncidentAlertDistanceMeters(incident = {}) {
    if (typeof incident?.distance_meters === 'number') {
        return incident.distance_meters;
    }

    const markerLat = Number(incident?.lat ?? incident?.marker?.lat);
    const markerLng = Number(incident?.lng ?? incident?.marker?.lng);

    if (!window.userCoords?.ready || Number.isNaN(markerLat) || Number.isNaN(markerLng)) {
        return null;
    }

    return calculateDistanceMeters(
        Number(window.userCoords.currentLatitude),
        Number(window.userCoords.currentLongitude),
        markerLat,
        markerLng
    );
}

function calculateDistanceMeters(lat1, lng1, lat2, lng2) {
    const earthRadius = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return earthRadius * c;
}

function ensureIncidentAlertStyles() {
    if (document.getElementById('incidentAlertCenterStyles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'incidentAlertCenterStyles';
    style.textContent = `
        #${INCIDENT_ALERT_PANEL_ID} {
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            width: min(24rem, calc(100vw - 2rem));
            max-height: min(70vh, 40rem);
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 1.25rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
            z-index: 5500;
            display: none;
            overflow: hidden;
        }
        #${INCIDENT_ALERT_PANEL_ID}.show {
            display: flex;
            flex-direction: column;
        }
        .incident-alert-center-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1rem 0.875rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #fff7ed, #ffffff);
        }
        .incident-alert-center-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1e293b;
        }
        .incident-alert-center-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .incident-alert-center-actions button {
            border: 0;
            background: transparent;
            color: #c2410c;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        .incident-alert-center-list {
            overflow-y: auto;
            padding: 0.5rem 0;
        }
        .incident-alert-entry {
            display: flex;
            gap: 0.85rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
        }
        .incident-alert-entry.unread {
            background: #fff7ed;
        }
        .incident-alert-entry:last-child {
            border-bottom: 0;
        }
        .incident-alert-entry-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .incident-alert-entry-title {
            font-size: 0.875rem;
            font-weight: 800;
            color: #0f172a;
        }
        .incident-alert-entry-time {
            font-size: 0.7rem;
            color: #64748b;
            white-space: nowrap;
        }
        .incident-alert-entry-message {
            font-size: 0.8rem;
            line-height: 1.45;
            color: #475569;
            margin-top: 0.2rem;
        }
        .incident-alert-entry-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.55rem;
        }
        .incident-alert-entry-actions button {
            border: 0;
            border-radius: 9999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
        }
        .incident-alert-entry-actions .primary {
            background: #ea580c;
            color: #ffffff;
        }
        .incident-alert-entry-actions .secondary {
            background: #f1f5f9;
            color: #475569;
        }
        #${INCIDENT_ALERT_TOGGLE_ID} {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 9999px;
            border: 0;
            background: linear-gradient(135deg, #dc2626, #f97316);
            color: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
            z-index: 5400;
            cursor: pointer;
            display: none;
        }
        #${INCIDENT_ALERT_TOGGLE_ID}.show {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        #${INCIDENT_ALERT_BADGE_ID},
        .incident-alert-badge-inline {
            position: absolute;
            top: -0.15rem;
            right: -0.15rem;
            min-width: 1.15rem;
            height: 1.15rem;
            padding: 0 0.25rem;
            border-radius: 9999px;
            background: #dc2626;
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            display: none;
            align-items: center;
            justify-content: center;
        }
    `;

    document.head.appendChild(style);
}

function ensureIncidentAlertPanel() {
    ensureIncidentAlertStyles();

    let panel = document.getElementById(INCIDENT_ALERT_PANEL_ID);
    if (!panel) {
        panel = document.createElement('div');
        panel.id = INCIDENT_ALERT_PANEL_ID;
        panel.innerHTML = `
            <div class="incident-alert-center-header">
                <div>
                    <div class="incident-alert-center-title">Incident Notifications</div>
                    <div class="incident-alert-center-subtitle">Sound, vibration, and live alert feed</div>
                </div>
                <div class="incident-alert-center-actions">
                    <button type="button" id="incidentAlertMarkRead">Mark all read</button>
                </div>
            </div>
            <div id="${INCIDENT_ALERT_LIST_ID}" class="incident-alert-center-list"></div>
        `;
        document.body.appendChild(panel);

        panel.querySelector('#incidentAlertMarkRead')?.addEventListener('click', () => {
            markAllIncidentAlertsRead();
        });

        document.addEventListener('click', (event) => {
            const toggleButton = document.getElementById(INCIDENT_ALERT_TOGGLE_ID);
            const linkedButton = document.getElementById('notificationBtn');
            const insidePanel = panel.contains(event.target);
            const insideToggle = toggleButton?.contains(event.target);
            const insideLinked = linkedButton?.contains(event.target);

            if (!insidePanel && !insideToggle && !insideLinked) {
                panel.classList.remove('show');
            }
        });
    }

    const notificationButton = document.getElementById('notificationBtn');
    const firefighterNotificationButton = document.querySelector('[data-ff-open-notifications]');
    const nativeDropdown = document.getElementById('alertsDropdown');
    if (notificationButton && !notificationButton.dataset.incidentAlertBound) {
        notificationButton.dataset.incidentAlertBound = 'true';
        if (!nativeDropdown) {
            notificationButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                panel.classList.toggle('show');
                if (panel.classList.contains('show')) {
                    markAllIncidentAlertsRead();
                }
            });
        }
    }

    const citizenNotificationFeed = document.getElementById('citizenNotificationsFeed');
    let floatingToggle = document.getElementById(INCIDENT_ALERT_TOGGLE_ID);

    if (firefighterNotificationButton && floatingToggle) {
        floatingToggle.remove();
        floatingToggle = null;
    }

    if (!notificationButton && !firefighterNotificationButton && !citizenNotificationFeed && !floatingToggle) {
        floatingToggle = document.createElement('button');
        floatingToggle.id = INCIDENT_ALERT_TOGGLE_ID;
        floatingToggle.className = 'show';
        floatingToggle.type = 'button';
        floatingToggle.innerHTML = `
            <i class="fa-regular fa-bell"></i>
            <span id="${INCIDENT_ALERT_BADGE_ID}">0</span>
        `;
        document.body.appendChild(floatingToggle);
        floatingToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            panel.classList.toggle('show');
            if (panel.classList.contains('show')) {
                markAllIncidentAlertsRead();
            }
        });
    }

    const citizenMarkReadButton = document.getElementById('citizenNotificationsMarkRead');
    if (citizenMarkReadButton && !citizenMarkReadButton.dataset.incidentAlertBound) {
        citizenMarkReadButton.dataset.incidentAlertBound = 'true';
        citizenMarkReadButton.addEventListener('click', () => {
            markAllIncidentAlertsRead();
        });
    }

    return panel;
}

function syncIncidentAlertBadges(feed = loadIncidentAlertFeed()) {
    const unreadCount = feed.filter((entry) => !entry.read).length;
    const floatingBadge = document.getElementById(INCIDENT_ALERT_BADGE_ID);
    const notificationButton = document.getElementById('notificationBtn') || document.querySelector('[data-ff-open-notifications]');
    const chiefAlertBadge = document.getElementById('alertBadge');

    if (floatingBadge) {
        floatingBadge.textContent = String(unreadCount);
        floatingBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
    }

    if (notificationButton) {
        let inlineBadge = notificationButton.querySelector('.incident-alert-badge-inline');
        if (!inlineBadge) {
            inlineBadge = document.createElement('span');
            inlineBadge.className = 'incident-alert-badge-inline';
            notificationButton.appendChild(inlineBadge);
        }
        inlineBadge.textContent = String(unreadCount);
        inlineBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
    }

    if (chiefAlertBadge) {
        chiefAlertBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
    }
}

function renderCitizenNotificationFeed(feed = loadIncidentAlertFeed()) {
    const container = document.getElementById('citizenNotificationsFeed');
    if (!container) {
        return;
    }

    if (!feed.length) {
        container.innerHTML = `
            <div class="p-6 text-center text-sm text-gray-500">
                No emergency notifications yet.
            </div>
        `;
        return;
    }

    container.innerHTML = feed.map((entry) => `
        <div class="p-5 ${entry.read ? 'hover:bg-gray-50' : 'bg-orange-50 hover:bg-orange-100'} transition">
            <div class="flex gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${entry.iconClass}">
                    <i class="fa-solid ${entry.icon}"></i>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start mb-1 gap-3">
                        <h3 class="font-semibold text-gray-800">${escapeHtml(entry.title)}</h3>
                        <span class="text-xs text-gray-500 whitespace-nowrap">${escapeHtml(formatIncidentAlertRelativeTime(entry.created_at))}</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">${escapeHtml(entry.message)}</p>
                    <div class="flex gap-3">
                        <button type="button" class="text-xs bg-orange-600 text-white px-3 py-1 rounded-full" onclick="window.focusIncidentAlertEntry?.('${entry.id}')">View Details</button>
                        <button type="button" class="text-xs text-gray-500 hover:text-gray-700" onclick="window.dismissIncidentAlertEntry?.('${entry.id}')">Dismiss</button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function renderChiefNotificationDropdown(feed = loadIncidentAlertFeed()) {
    const container = document.getElementById('alertsDropdownList');
    if (!container) {
        return;
    }

    if (!feed.length) {
        container.innerHTML = `
            <div class="px-4 py-6 text-sm text-slate-500 text-center">
                No live incident notifications yet.
            </div>
        `;
        return;
    }

    container.innerHTML = feed.map((entry) => `
        <article class="alert-item-dropdown ${entry.read ? '' : 'unread'} px-4 py-4 hover:bg-slate-50 transition">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${entry.iconClass}">
                    <i class="fa-solid ${entry.icon}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-bold text-slate-800">${escapeHtml(entry.title)}</p>
                        <span class="text-[11px] text-slate-400 whitespace-nowrap">${escapeHtml(formatIncidentAlertRelativeTime(entry.created_at))}</span>
                    </div>
                    <p class="mt-1 text-xs leading-5 text-slate-500">${escapeHtml(entry.message)}</p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="rounded-full bg-amber-600 px-3 py-1 text-[11px] font-semibold text-white" onclick="window.focusIncidentAlertEntry?.('${entry.id}')">Open</button>
                        <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600" onclick="window.dismissIncidentAlertEntry?.('${entry.id}')">Dismiss</button>
                    </div>
                </div>
            </div>
        </article>
    `).join('');
}

function renderIncidentAlertPanel(feed = loadIncidentAlertFeed()) {
    ensureIncidentAlertPanel();

    const list = document.getElementById(INCIDENT_ALERT_LIST_ID);
    if (!list) {
        return;
    }

    if (!feed.length) {
        list.innerHTML = `
            <div class="p-4 text-sm text-slate-500">
                No incident notifications yet.
            </div>
        `;
        syncIncidentAlertBadges(feed);
        renderCitizenNotificationFeed(feed);
        renderChiefNotificationDropdown(feed);
        return;
    }

    list.innerHTML = feed.map((entry) => `
        <article class="incident-alert-entry ${entry.read ? '' : 'unread'}">
            <div class="incident-alert-entry-icon ${entry.iconClass}">
                <i class="fa-solid ${entry.icon}"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div class="incident-alert-entry-title">${escapeHtml(entry.title)}</div>
                    <div class="incident-alert-entry-time">${escapeHtml(formatIncidentAlertRelativeTime(entry.created_at))}</div>
                </div>
                <div class="incident-alert-entry-message">${escapeHtml(entry.message)}</div>
                <div class="incident-alert-entry-actions">
                    <button type="button" class="primary" onclick="window.focusIncidentAlertEntry?.('${entry.id}')">Open</button>
                    <button type="button" class="secondary" onclick="window.dismissIncidentAlertEntry?.('${entry.id}')">Dismiss</button>
                </div>
            </div>
        </article>
    `).join('');

    syncIncidentAlertBadges(feed);
    renderCitizenNotificationFeed(feed);
    renderChiefNotificationDropdown(feed);
}

function markAllIncidentAlertsRead() {
    const feed = loadIncidentAlertFeed().map((entry) => ({ ...entry, read: true }));
    saveIncidentAlertFeed(feed);
    renderIncidentAlertPanel(feed);
}

window.markAllIncidentAlertsRead = markAllIncidentAlertsRead;

function dismissIncidentAlertEntry(entryId) {
    const nextFeed = loadIncidentAlertFeed().filter((entry) => entry.id !== entryId);
    saveIncidentAlertFeed(nextFeed);
    renderIncidentAlertPanel(nextFeed);
}

function focusIncidentAlertEntry(entryId) {
    const feed = loadIncidentAlertFeed();
    const target = feed.find((entry) => entry.id === entryId);
    if (!target) {
        return;
    }

    const nextFeed = feed.map((entry) => entry.id === entryId ? { ...entry, read: true } : entry);
    saveIncidentAlertFeed(nextFeed);
    renderIncidentAlertPanel(nextFeed);

    if (typeof target.lat === 'number' && typeof target.lng === 'number' && window.map) {
        window.map.setView([target.lat, target.lng], 16);
    }

    if (roleName === 'citizen' && typeof window.switchSection === 'function') {
        window.switchSection('alerts');
    }
}

window.dismissIncidentAlertEntry = dismissIncidentAlertEntry;
window.focusIncidentAlertEntry = focusIncidentAlertEntry;

function ensureIncidentAlertAudioUnlocked() {
    if (incidentAlertAudioContext) {
        return;
    }

    try {
        const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextCtor) {
            return;
        }

        incidentAlertAudioContext = new AudioContextCtor();
        if (incidentAlertAudioContext.state === 'suspended') {
            incidentAlertAudioContext.resume().catch(() => {});
        }
    } catch (error) {
        console.warn('Unable to initialize incident alert audio context:', error);
    }
}

function playIncidentAlertTone() {
    ensureIncidentAlertAudioUnlocked();
    if (!incidentAlertAudioContext) {
        return;
    }

    try {
        const now = incidentAlertAudioContext.currentTime;
        const frequencies = [880, 660, 880];

        frequencies.forEach((frequency, index) => {
            const oscillator = incidentAlertAudioContext.createOscillator();
            const gainNode = incidentAlertAudioContext.createGain();
            const startAt = now + (index * 0.18);

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gainNode.gain.setValueAtTime(0.0001, startAt);
            gainNode.gain.exponentialRampToValueAtTime(0.16, startAt + 0.03);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.16);

            oscillator.connect(gainNode);
            gainNode.connect(incidentAlertAudioContext.destination);
            oscillator.start(startAt);
            oscillator.stop(startAt + 0.17);
        });
    } catch (error) {
        console.warn('Unable to play incident alert tone:', error);
    }
}

function requestIncidentNotificationPermission() {
    if (!('Notification' in window) || incidentAlertPermissionRequested || Notification.permission !== 'default') {
        return;
    }

    incidentAlertPermissionRequested = true;
    Notification.requestPermission().catch(() => {});
}

function bindIncidentAlertPermissionPrompt() {
    const promptOnce = () => {
        ensureIncidentAlertAudioUnlocked();
        requestIncidentNotificationPermission();
        INCIDENT_ALERT_AUDIO_UNLOCK_EVENTS.forEach((eventName) => {
            document.removeEventListener(eventName, promptOnce, true);
        });
    };

    INCIDENT_ALERT_AUDIO_UNLOCK_EVENTS.forEach((eventName) => {
        document.addEventListener(eventName, promptOnce, true);
    });
}

function showIncidentAlertToast(entry) {
    window.PyroNotify?.notify(entry.message, {
        type: 'warning',
        title: entry.title,
        duration: 5500,
    });
}

function sendBrowserIncidentNotification(entry) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    try {
        new Notification(entry.title, {
            body: entry.message,
            tag: `incident-${entry.incident_id}`,
            vibrate: [220, 120, 220],
            renotify: true
        });
    } catch (error) {
        console.warn('Unable to send browser notification:', error);
    }
}

function buildIncidentAlertEntry(incident, options = {}) {
    const typeMeta = getIncidentAlertTypeMeta(incident);
    const statusLabel = getIncidentAlertStatusLabel(incident);
    const distanceMeters = getIncidentAlertDistanceMeters(incident);
    const distanceLabel = typeof distanceMeters === 'number'
        ? `${Math.round(distanceMeters)}m away`
        : null;
    const incidentLabel = incident?.title || incident?.marker?.title || `Incident #${incident?.incident_id ?? 'N/A'}`;
    const locationText = incident?.address || incident?.marker?.description || incident?.description || '';
    const prefix = options.titlePrefix || (roleName === 'citizen' ? 'Nearby Emergency Alert' : 'New Incident Alert');

    return {
        id: `${options.scope || 'incident'}-${incident?.incident_id}-${Date.now()}`,
        incident_id: Number(incident?.incident_id),
        title: `${prefix}: ${typeMeta.label}`,
        message: [incidentLabel, statusLabel, distanceLabel, locationText].filter(Boolean).join(' • '),
        created_at: incident?.created_at || incident?.updated_at || new Date().toISOString(),
        read: false,
        lat: Number(incident?.lat ?? incident?.marker?.lat),
        lng: Number(incident?.lng ?? incident?.marker?.lng),
        icon: typeMeta.icon,
        iconClass: typeMeta.iconClass
    };
}

function triggerIncidentAlert(entry) {
    playIncidentAlertTone();
    if (navigator.vibrate) {
        navigator.vibrate([240, 120, 240]);
    }
    showIncidentAlertToast(entry);
    sendBrowserIncidentNotification(entry);
}

function shouldNotifyForIncident(incident, options = {}) {
    const normalizedStatus = normalizeIncidentStatus(
        incident?.incident_status
        || incident?.status
        || 'reported'
    );

    if (normalizedStatus === 'closed') {
        return false;
    }

    if (typeof options.maxDistanceMeters === 'number') {
        const distanceMeters = getIncidentAlertDistanceMeters(incident);
        if (typeof distanceMeters !== 'number' || distanceMeters > options.maxDistanceMeters) {
            return false;
        }
    }

    if (options.nearestStationOnly && currentStationMetaId) {
        const nearestStationId = String(incident?.nearest_station_id || '').trim();
        if (!nearestStationId || nearestStationId !== currentStationMetaId) {
            return false;
        }
    }

    return true;
}

function syncIncidentAlertFeed(scope, incidents, options = {}) {
    ensureIncidentAlertPanel();

    const seenMap = loadIncidentAlertSeenMap();
    const scopeInitKey = `__scope_initialized__:${scope}`;
    const isInitialScopeSync = !incidentAlertScopeInitialization.has(scope) && !seenMap[scopeInitKey];
    const nextFeedEntries = [];

    for (const incident of Array.isArray(incidents) ? incidents : []) {
        if (!incident?.incident_id || !shouldNotifyForIncident(incident, options)) {
            continue;
        }

        const seenKey = `${scope}:${incident.incident_id}`;
        const fingerprint = [
            incident.incident_id,
            incident.updated_at || incident.created_at || '',
            incident.incident_status_id || incident.incident_status || incident.status || ''
        ].join(':');

        if (!seenMap[seenKey] && !isInitialScopeSync) {
            nextFeedEntries.push(buildIncidentAlertEntry(incident, { ...options, scope }));
        }

        seenMap[seenKey] = fingerprint;
    }

    seenMap[scopeInitKey] = true;
    incidentAlertScopeInitialization.add(scope);
    saveIncidentAlertSeenMap(seenMap);

    if (nextFeedEntries.length) {
        const currentFeed = loadIncidentAlertFeed();
        const nextFeed = [...nextFeedEntries, ...currentFeed].slice(0, 30);
        saveIncidentAlertFeed(nextFeed);
        renderIncidentAlertPanel(nextFeed);
        nextFeedEntries.forEach(triggerIncidentAlert);
        return nextFeedEntries;
    }

    renderIncidentAlertPanel(loadIncidentAlertFeed());
    return [];
}

window.syncIncidentAlertFeed = syncIncidentAlertFeed;

document.addEventListener('DOMContentLoaded', () => {
    ensureIncidentAlertPanel();
    renderIncidentAlertPanel(loadIncidentAlertFeed());
    bindIncidentAlertPermissionPrompt();
});

// ============================================================================
// ICON ARRAY (keeping your existing icons)
// ============================================================================

const ICON_ARRAY = [
    {
        id: 'station',
        name: 'Fire Station',
        category: 'emergency',
        icon: 'fa-building',
        color: '#1d4ed8',
        bgColor: '#2563eb',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'incident',
        name: 'Incident',
        category: 'emergency',
         icon: '/storage/defaults/alarm.png',
        color: '#dc2626',
        bgColor: '#ef4444',
        size: [36, 36],
        anchor: [18, 18]
    },
    {
        id: 'hydrant',
        name: 'Fire Hydrant',
        category: 'emergency',
        icon: 'fa-fire-extinguisher',
        color: '#b91c1c',
        bgColor: '#ef4444',
        size: [28, 28],
        anchor: [14, 14]
    },
    {
        id: 'firefighter',
        name: 'Firefighter',
        category: 'personnel',
        icon: '/storage/defaults/firefighter.png',
        color: '#1e40af',
        bgColor: '#3b82f6',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'firetruck',
        name: 'Fire Truck',
        category: 'vehicle',
        icon: 'fa-truck-fire',
        color: '#b91c1c',
        bgColor: '#ef4444',
        size: [40, 32],
        anchor: [20, 16]
    },
    {
        id: 'ambulance',
        name: 'Ambulance',
        category: 'vehicle',
        icon: 'fa-truck-medical',
        color: '#b91c1c',
        bgColor: '#dc2626',
        size: [40, 32],
        anchor: [20, 16]
    },
    {
        id: 'police',
        name: 'Police',
        category: 'emergency',
        icon: 'fa-shield',
        color: '#1e3a8a',
        bgColor: '#2563eb',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'school',
        name: 'School',
        category: 'infrastructure',
        icon: 'fa-school',
        color: '#854d0e',
        bgColor: '#eab308',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'evacuation',
        name: 'Evacuation Center',
        category: 'resource',
        icon: 'fa-house-chimney',
        color: '#854d0e',
        bgColor: '#eab308',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'bridge',
        name: 'Bridge',
        category: 'infrastructure',
        icon: 'fa-bridge',
        color: '#4b5563',
        bgColor: '#6b7280',
        size: [36, 32],
        anchor: [18, 16]
    },
    {
        id: 'hospital',
        name: 'Hospital',
        category: 'infrastructure',
        icon: 'fa-hospital',
        color: '#b91c1c',
        bgColor: '#ef4444',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'church',
        name: 'Church',
        category: 'infrastructure',
        icon: 'fa-church',
        color: '#4b5563',
        bgColor: '#9ca3af',
        size: [32, 36],
        anchor: [16, 36]
    },
    {
        id: 'government',
        name: 'Government',
        category: 'infrastructure',
        icon: 'fa-landmark',
        color: '#4b5563',
        bgColor: '#6b7280',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'water',
        name: 'Water Source',
        category: 'resource',
        icon: 'fa-water',
        color: '#1e40af',
        bgColor: '#3b82f6',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'food',
        name: 'Food Supply',
        category: 'resource',
        icon: 'fa-utensils',
        color: '#166534',
        bgColor: '#22c55e',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'fuel',
        name: 'Fuel Station',
        category: 'resource',
        icon: 'fa-gas-pump',
        color: '#92400e',
        bgColor: '#f59e0b',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'redcross',
        name: 'Red Cross',
        category: 'organization',
        icon: 'fa-hand-holding-heart',
        color: '#b91c1c',
        bgColor: '#ef4444',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'ngo',
        name: 'NGO',
        category: 'organization',
        icon: 'fa-hand-holding-heart',
        color: '#166534',
        bgColor: '#22c55e',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'river',
        name: 'River',
        category: 'natural',
        icon: 'fa-water',
        color: '#1e40af',
        bgColor: '#3b82f6',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'mountain',
        name: 'Mountain',
        category: 'natural',
        icon: 'fa-mountain',
        color: '#166534',
        bgColor: '#22c55e',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'forest',
        name: 'Forest',
        category: 'natural',
        icon: 'fa-tree',
        color: '#166534',
        bgColor: '#22c55e',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'risk',
        name: 'Risk Marker',
        category: 'emergency',
        icon: 'fa-triangle-exclamation',
        color: '#075985',
        bgColor: '#0284c7',
        size: [34, 34],
        anchor: [17, 17]
    },
    {
        id: 'hazard',
        name: 'Hazard Marker',
        category: 'emergency',
        icon: 'fa-radiation',
        color: '#92400e',
        bgColor: '#d97706',
        size: [34, 34],
        anchor: [17, 17]
    }
];

const CATEGORY_ICON_MAP = {
    1: 'station',
    2: 'incident',
    3: 'incident',
    4: 'hydrant',
    5: 'school',
    6: 'evacuation'
};

const DEFAULT_PUBLIC_MARKER_IMAGE_MAP = {
    station: '/station.png',
    hydrant: '/hydrant.png',
    barangay: '/hall.png',
    evacuation: '/hall.png',
    incident: '/fire.png',
    risk: '/hazard.png',
    hazard: '/hazard.png',
    firefighter: '/team.png',
    firetruck: '/truck.png'
};

function getPublicMarkerImageMap() {
    const configuredMap = window.pyroPublicAssetUrls;
    return {
        ...DEFAULT_PUBLIC_MARKER_IMAGE_MAP,
        ...(configuredMap && typeof configuredMap === 'object' ? configuredMap : {})
    };
}

// ============================================================================
// HELPER FUNCTIONS FOR ICONS (keeping your existing icon functions)
// ============================================================================

function inferMarkerImagePath(marker = null, iconConfig = null) {
    const publicMarkerImageMap = getPublicMarkerImageMap();
    const explicitCategoryKey = getMarkerCategoryKey(marker);
    if (explicitCategoryKey && publicMarkerImageMap[explicitCategoryKey]) {
        return publicMarkerImageMap[explicitCategoryKey];
    }

    if (iconConfig?.id && publicMarkerImageMap[iconConfig.id]) {
        return publicMarkerImageMap[iconConfig.id];
    }

    const haystack = [
        marker?.title,
        marker?.description,
        marker?.category,
        marker?.category?.name,
        marker?.hazard_type,
        marker?.risk_type,
        marker?.type
    ].filter(Boolean).join(' ').toLowerCase();

    if (haystack.includes('fire incident')) return '/fire.png';
    if (haystack.includes('fire emergency')) return '/fire.png';
    if (haystack.includes('fire alert')) return '/fire.png';
    if (haystack.includes('active fire')) return '/fire.png';
    if (haystack.includes('short circuit')) return '/electric.png';
    if (haystack.includes('faulty wiring')) return '/electric.png';
    if (haystack.includes('live wire')) return '/electric.png';
    if (haystack.includes('octopus')) return '/electric.png';
    if (haystack.includes('gas leak')) return '/gas.png';
    if (haystack.includes('gas')) return '/gas.png';
    if (haystack.includes('flammable')) return '/flammable.png';
    if (haystack.includes('electric')) return '/electric.png';
    if (haystack.includes('electrical')) return '/electric.png';
    if (haystack.includes('block road')) return '/blockroad.png';
    if (haystack.includes('blocked road')) return '/blockroad.png';
    if (haystack.includes('road block')) return '/blockroad.png';
    if (haystack.includes('damaged')) return '/damaged.png';
    if (haystack.includes('hazard')) return '/hazard.png';
    if (haystack.includes('risk')) return '/hazard.png';
    if (haystack.includes('incident')) return '/fire.png';
    if (haystack.includes('fire')) return '/fire.png';
    if (haystack.includes('barangay')) return '/hall.png';
    if (haystack.includes('hydrant')) return '/hydrant.png';
    if (haystack.includes('station')) return '/station.png';

    return null;
}

function getMarkerPopupImagePath(marker = {}, coord = {}) {
    return marker?.risk_area_public?.primary_photo_url
        || marker?.risk_area_public?.photos?.[0]?.photo_url
        || marker?.risk_area_public?.photos?.[0]?.url
        || coord?.risk_area_public?.primary_photo_url
        || coord?.risk_area_public?.photos?.[0]?.photo_url
        || coord?.risk_area_public?.photos?.[0]?.url
        || marker?.photo_url
        || marker?.photos?.[0]?.photo_url
        || marker?.photos?.[0]?.url
        || coord?.photo_url
        || coord?.photos?.[0]?.photo_url
        || coord?.photos?.[0]?.url
        || marker?.multimedia?.path
        || coord?.image?.path
        || null;
}

function normalizePopupImageUrl(imageSource) {
    if (!imageSource) {
        return null;
    }

    const normalizedSource = String(imageSource).trim();

    if (!normalizedSource) {
        return null;
    }

    if (/^https?:\/\//i.test(normalizedSource) || normalizedSource.startsWith('/')) {
        return normalizedSource;
    }

    if (/^storage\//i.test(normalizedSource)) {
        return `/${normalizedSource.replace(/^\/+/, '')}`;
    }

    return '/storage/' + normalizedSource.replace(/^\/+/, '');
}

function buildImageBackedMarkerHtml({ width, height, borderRadius, imagePath, fallbackIconClass, bgColor, iconFontSize, wrapperClass = '' }) {
    const safeImagePath = imagePath ? String(imagePath).replace(/"/g, '&quot;') : '';

    return `
        <div class="${wrapperClass}" style="
            width: ${width}px;
            height: ${height}px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            ${safeImagePath ? `
                <img
                    src="${safeImagePath}"
                    alt=""
                    draggable="false"
                    style="
                        width: ${width}px;
                        height: ${height}px;
                        object-fit: contain;
                        border-radius: 0;
                        border: 0;
                        box-shadow: none;
                        filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.24));
                        background: transparent;
                        display: block;
                    "
                    onerror="
                        this.style.display='none';
                        const fallback = this.parentElement.querySelector('.marker-image-fallback');
                        if (fallback) fallback.style.display='flex';
                    "
                />
            ` : ''}
            <div class="marker-image-fallback" style="
                width: ${width}px;
                height: ${height}px;
                background-color: ${bgColor};
                border-radius: ${borderRadius};
                display: ${safeImagePath ? 'none' : 'flex'};
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3), 0 2px 4px -1px rgba(0,0,0,0.2);
                border: 2px solid white;
                transition: transform 0.2s;
            ">
                <i class="fas ${fallbackIconClass}" style="
                    color: white;
                    font-size: ${iconFontSize}px;
                    filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
                "></i>
            </div>
        </div>
    `;
}

function createFontAwesomeIcon(iconConfig, extraClass = '', customColor = null, marker = null) {
    const bgColor = customColor || iconConfig.bgColor;
    const imagePath = inferMarkerImagePath(marker, iconConfig);
    const hasImageMarker = Boolean(imagePath);
    const [baseWidth, baseHeight] = iconConfig.size;
    const width = hasImageMarker ? Math.round(baseWidth * 1.35) : baseWidth;
    const height = hasImageMarker ? Math.round(baseHeight * 1.35) : baseHeight;
    const borderRadius = iconConfig.id === 'hydrant' ? '4px 4px 8px 8px' : '50%';
    const iconAnchor = hasImageMarker
        ? [Math.round(width / 2), Math.round(height / 2)]
        : iconConfig.anchor;

    return L.divIcon({
        className: `custom-fa-icon pyro-map-marker-icon ${extraClass}`,
        html: `
            ${buildImageBackedMarkerHtml({
                width,
                height,
                borderRadius,
                imagePath,
                fallbackIconClass: iconConfig.icon,
                bgColor,
                iconFontSize: Math.min(width, height) * 0.6
            })}
            ${iconConfig.id === 'firefighter' ? `
                <div style="
                    position: absolute;
                    bottom: -20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: rgba(0,0,0,0.7);
                    color: white;
                    font-size: 10px;
                    padding: 2px 6px;
                    border-radius: 12px;
                    white-space: nowrap;
                    font-weight: bold;
                    border: 1px solid white;
                    display: none;
                " class="speed-badge">0 km/h</div>
            ` : ''}
        `,
        iconSize: [width, height],
        iconAnchor: iconAnchor,
        popupAnchor: [0, -height / 2]
    });
}   
function getIconById(iconId, extraClass = '', marker = null) {
    const iconConfig = ICON_ARRAY.find(icon => icon.id === iconId);
    if (!iconConfig) {
        console.warn(`Icon with id '${iconId}' not found, using default`);
        return getDefaultIcon();
    }

    return createFontAwesomeIcon(iconConfig, extraClass, null, marker);
}
// function getIconById(iconId, extraClass = '') {
//     const iconConfig = ICON_ARRAY.find(icon => icon.id === iconId);
//     if (!iconConfig) {
//         console.warn(`Icon with id '${iconId}' not found, using default`);
//         return getDefaultIcon();
//     }
//     return createFontAwesomeIcon(iconConfig, extraClass);
// }

function getIconByCategoryId(categoryId, extraClass = '', marker = null) {
    const normalizedCategoryId = getNormalizedCategoryId(marker || categoryId);

    if (normalizedCategoryId === 2 && marker) {
        return createIncidentTypeIcon(marker, extraClass);
    }

    const iconId = CATEGORY_ICON_MAP[normalizedCategoryId];
    if (iconId) {
        return getIconById(iconId, extraClass, marker);
    }
    return getDefaultIcon(marker, extraClass);
}

function getDefaultIcon(marker = null, extraClass = '') {
    const imagePath = inferMarkerImagePath(marker, null);
    const markerSize = imagePath ? 36 : 24;

    return L.divIcon({
        className: `default-icon pyro-map-marker-icon ${extraClass}`.trim(),
        html: buildImageBackedMarkerHtml({
            width: markerSize,
            height: markerSize,
            borderRadius: '50%',
            imagePath,
            fallbackIconClass: 'fa-location-dot',
            bgColor: '#6b7280',
            iconFontSize: imagePath ? 18 : 12
        }),
        iconSize: [markerSize, markerSize],
        iconAnchor: [markerSize / 2, markerSize / 2],
        popupAnchor: [0, -markerSize / 2]
    });
}

function createSharedMapMarkerIcon(markerType, options = {}) {
    const marker = {
        ...(options.marker || {}),
        category: options.marker?.category || markerType
    };

    if (options.status !== undefined && options.status !== null) {
        marker.incident_status = options.status;
        marker.status = options.status;
    }

    if (options.isActive === false && isIncidentMarker(marker) && !marker.incident_status) {
        marker.incident_status = 'controlled';
    }

    return getIconByCategoryId(marker.markerCategory_id ?? null, options.extraClass || '', marker);
}

window.createSharedMapMarkerIcon = createSharedMapMarkerIcon;

function getFirefighterIconWithSpeed(speed = 0) {
    const iconConfig = ICON_ARRAY.find(icon => icon.id === 'firefighter');
    const speedValue = Math.round(speed);
    let speedColor = '#1e40af';
    
    if (speedValue > 60) {
        speedColor = '#dc2626';
    } else if (speedValue > 30) {
        speedColor = '#f59e0b';
    }
    
    return L.divIcon({
        className: 'custom-fa-icon firefighter-animated',
        html: `
            <div style="
                width: ${iconConfig.size[0]}px;
                height: ${iconConfig.size[1]}px;
                background-color: ${iconConfig.bgColor};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3);
                border: 3px solid white;
                animation: pulse 2s infinite;
            ">
                <i class="fas ${iconConfig.icon}" style="
                    color: white;
                    font-size: 20px;
                "></i>
            </div>
            <div style="
                position: absolute;
                bottom: -22px;
                left: 50%;
                transform: translateX(-50%);
                background-color: ${speedColor};
                color: white;
                font-size: 10px;
                padding: 2px 8px;
                border-radius: 12px;
                white-space: nowrap;
                font-weight: bold;
                border: 1px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            " class="speed-badge-${speedValue}">
                ${speedValue} km/h
            </div>
        `,
        iconSize: iconConfig.size,
        iconAnchor: iconConfig.anchor,
        popupAnchor: [0, -iconConfig.anchor[1]]
    });
}

// Add animation CSS
const styleElement = document.createElement('style');
styleElement.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
    
    .custom-fa-icon {
        transition: all 0.3s ease;
    }

    .leaflet-div-icon.pyro-map-marker-icon,
    .leaflet-marker-icon.pyro-map-marker-icon,
    .pyro-map-marker-icon {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .incident-marker {
        position: relative;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        background: transparent;
        border: 0;
        box-shadow: none;
        overflow: visible;
    }

    .incident-marker__icon {
        position: relative;
        z-index: 2;
        pointer-events: none;
    }

    .incident-marker__pulse {
        position: absolute;
        inset: 3px;
        z-index: 1;
        border-radius: 999px;
        border: 3px solid rgba(220, 38, 38, 0.72);
        background: rgba(239, 68, 68, 0.16);
        animation: incidentMarkerPulse 2s ease-out infinite;
        pointer-events: none;
    }

    .incident-marker__pulse--delay {
        animation-delay: 0.7s;
    }

    @keyframes incidentMarkerPulse {
        0% {
            opacity: 0.78;
            transform: scale(0.72);
        }
        72% {
            opacity: 0;
            transform: scale(2.05);
        }
        100% {
            opacity: 0;
            transform: scale(2.05);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .incident-marker__pulse {
            animation: none;
            opacity: 0.28;
            transform: scale(1.25);
        }
    }
    
    .custom-fa-icon:hover {
        transform: scale(1.1);
        z-index: 1000 !important;
    }
    
    .fire-truck-marker {
        transition: all 0.1s linear;
    }
    
    .route-progress-container {
        margin-top: 10px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        height: 6px;
    }
    
    .route-progress-bar {
        height: 100%;
        background: #3b82f6;
        transition: width 0.3s ease;
        border-radius: 10px;
    }
    
    .traveled-path {
        filter: brightness(0.7);
    }
    
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        width: 100%;
    }
    
    .notification {
        background: white;
        border-radius: 12px;
        margin-bottom: 10px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        border-left: 4px solid #3b82f6;
        overflow: hidden;
        transition: all 0.3s ease;
        animation: slideIn 0.3s ease;
    }
    
    .notification.success { border-left-color: #10b981; }
    .notification.warning { border-left-color: #f59e0b; }
    .notification.error { border-left-color: #ef4444; }
    
    .notification-header {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        background: #f9fafb;
    }
    
    .notification-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .notification-content {
        padding: 0 16px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
    }
    
    .notification.expanded .notification-content {
        padding: 16px;
        max-height: 200px;
        border-top: 1px solid #e5e7eb;
    }
    
    .notification-close {
        cursor: pointer;
        color: #9ca3af;
        transition: color 0.2s;
    }
    
    .notification-close:hover { color: #4b5563; }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .details-panel {
        position: fixed;
        top: 80px;
        right: 20px;
        width: 380px;
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(251,247,239,0.96) 100%);
        border-radius: 16px;
        box-shadow: 0 24px 40px -14px rgba(198,61,38,0.28);
        z-index: 1000;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid rgba(198, 61, 38, 0.14);
        display: none;
    }
    
    .details-panel.visible {
        display: block;
        animation: slideInRight 0.3s ease;
    }

    .details-panel.compact-responder {
        width: 296px;
        top: 136px;
        right: 14px;
    }

    .details-panel.compact-responder .details-body {
        padding: 10px;
        max-height: 60vh;
    }

    .details-panel.compact-responder .details-header {
        padding: 12px 14px;
    }

    .details-panel.compact-responder .details-header h3 {
        font-size: 14px;
        gap: 6px;
    }

    .details-panel.compact-responder .details-header h3 i {
        font-size: 13px;
    }

    .details-panel.compact-responder .details-close {
        font-size: 15px;
    }

    .details-header {
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        color: white;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .details-header h3 {
        font-weight: 600;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .details-close {
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .details-close:hover { opacity: 1; }
    
    .details-body {
        padding: 16px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .details-shimmer-shell {
        display: grid;
        gap: 12px;
        padding: 4px 0;
    }

    .details-shimmer-block {
        position: relative;
        overflow: hidden;
        background: #f4e7db;
        border-radius: 12px;
    }

    .details-shimmer-block::after {
        content: '';
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.58) 50%, rgba(255,255,255,0) 100%);
        animation: detailsShimmer 1.4s infinite;
    }

    @keyframes detailsShimmer {
        100% { transform: translateX(100%); }
    }
    
    .details-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
    }
    
    .details-info {
        background: #f9fafb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .details-info-item {
        display: flex;
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .details-info-label {
        width: 120px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .details-info-value {
        flex: 1;
        color: #1f2937;
        font-weight: 500;
    }
    
    .details-description {
        background: #f9fafb;
        border-radius: 12px;
        padding: 16px;
        font-size: 14px;
        line-height: 1.6;
        color: #374151;
    }

    .responder-custom-popup .leaflet-popup-content-wrapper {
        border-radius: 22px;
        padding: 0;
        background: #fffaf8;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .responder-custom-popup .leaflet-popup-content {
        margin: 0;
    }

    .responder-custom-popup .leaflet-popup-tip {
        background: #fff6f1;
    }

    .responder-marker-popup {
        width: 258px;
        background: linear-gradient(180deg, #fffdfb 0%, #fff7f1 100%);
        color: #1f2937;
    }

    .responder-marker-popup-topbar {
        height: 4px;
        background: linear-gradient(90deg, #c63d26 0%, #d18a16 100%);
    }

    .responder-marker-popup.station .responder-marker-popup-topbar {
        background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%);
    }

    .responder-marker-popup.hydrant .responder-marker-popup-topbar {
        background: linear-gradient(90deg, #0891b2 0%, #22d3ee 100%);
    }

    .responder-marker-popup.incident .responder-marker-popup-topbar {
        background: linear-gradient(90deg, #dc2626 0%, #f97316 100%);
    }

    .responder-marker-popup.school .responder-marker-popup-topbar {
        background: linear-gradient(90deg, #ca8a04 0%, #eab308 100%);
    }

    .responder-marker-popup.barangay .responder-marker-popup-topbar {
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
    }

    .responder-marker-popup-header {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 12px 8px;
    }

    .responder-marker-popup-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--popup-accent) 14%, white);
        color: var(--popup-accent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--popup-accent) 20%, white);
        flex-shrink: 0;
    }

    .responder-marker-popup-heading {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .responder-marker-popup-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .responder-marker-popup-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #fff1ec;
        color: #9a3412;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .responder-marker-popup-chip.status.reported {
        background: #fee2e2;
        color: #b91c1c;
    }

    .responder-marker-popup-chip.status.responding {
        background: #fef3c7;
        color: #b45309;
    }

    .responder-marker-popup-chip.status.resolved {
        background: #dcfce7;
        color: #15803d;
    }

    .responder-marker-popup-chip.status.closed,
    .responder-marker-popup-chip.status.default {
        background: #e5e7eb;
        color: #4b5563;
    }

    .responder-marker-popup-title {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: #111827;
        line-height: 1.3;
    }

    .responder-marker-popup-subtitle {
        margin: 0;
        font-size: 11px;
        line-height: 1.45;
        color: #6b7280;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .responder-marker-popup-media {
        margin: 0 12px;
        border-radius: 14px;
        overflow: hidden;
        background: #f8fafc;
        min-height: 82px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .responder-marker-popup-image {
        width: 100%;
        height: 88px;
        object-fit: cover;
        display: block;
    }

    .responder-marker-popup-media.placeholder {
        flex-direction: row;
        gap: 8px;
        color: #94a3b8;
        font-size: 11px;
        border: 1px dashed #fed7aa;
    }

    .responder-marker-popup-placeholder-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(198, 61, 38, 0.08);
        color: #c63d26;
        font-size: 14px;
    }

    .responder-marker-popup-details {
        display: grid;
        gap: 6px;
        padding: 10px 12px;
    }

    .responder-marker-popup-detail-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .responder-marker-popup-detail-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
    }

    .responder-marker-popup-detail-value {
        flex: 1;
        text-align: right;
        font-size: 11px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.35;
    }

    .responder-marker-popup-actions {
        display: grid;
        gap: 6px;
        padding: 0 12px 12px;
    }

    .responder-marker-popup-alert {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 8px 10px;
        border-radius: 12px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #9f1239;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.35;
    }

    .responder-marker-popup-btn {
        border: none;
        border-radius: 12px;
        min-height: 36px;
        padding: 8px 10px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }

    .responder-marker-popup-btn:hover {
        transform: translateY(-1px);
        filter: brightness(1.02);
    }

    .responder-marker-popup-btn.disabled,
    .responder-marker-popup-btn:disabled {
        opacity: 0.58;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
        filter: none;
    }

    .responder-marker-popup-btn.primary {
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(198, 61, 38, 0.18);
    }

    .responder-marker-popup-btn.secondary {
        background: #fff3ed;
        color: #9a3412;
        border: 1px solid rgba(198, 61, 38, 0.14);
    }

    .responder-marker-popup-btn.danger {
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(220, 38, 38, 0.18);
    }

    .responder-marker-popup-btn.demo {
        background: #fff;
        color: #9a3412;
        border: 1px dashed rgba(198, 61, 38, 0.38);
    }

    .responder-marker-popup-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        padding: 2px 6px;
        border-radius: 999px;
        background: rgba(251, 146, 60, 0.16);
        color: #9a3412;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.08em;
    }

    .route-source-modal {
        position: fixed;
        inset: 0;
        z-index: 10040;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .route-source-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.56);
        backdrop-filter: blur(4px);
    }

    .route-source-modal__dialog {
        position: relative;
        width: min(440px, calc(100vw - 24px));
        border-radius: 22px;
        background: linear-gradient(180deg, #fffaf5 0%, #ffffff 26%);
        border: 1px solid rgba(251, 146, 60, 0.18);
        box-shadow: 0 28px 64px rgba(15, 23, 42, 0.24);
        padding: 22px;
        color: #1f2937;
    }

    .route-source-modal__eyebrow {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #c2410c;
        margin-bottom: 8px;
    }

    .route-source-modal__dialog h3 {
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 800;
    }

    .route-source-modal__dialog p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.55;
    }

    .route-source-modal__options {
        display: grid;
        gap: 12px;
        margin-top: 18px;
    }

    .route-source-option {
        text-align: left;
        border: 1px solid rgba(251, 146, 60, 0.16);
        border-radius: 18px;
        background: #ffffff;
        padding: 16px;
        cursor: pointer;
        display: grid;
        gap: 6px;
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }

    .route-source-option:hover {
        transform: translateY(-1px);
        border-color: rgba(249, 115, 22, 0.34);
        box-shadow: 0 16px 30px rgba(251, 146, 60, 0.12);
    }

    .route-source-option strong {
        font-size: 14px;
        color: #111827;
    }

    .route-source-option small {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .route-source-option__badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .route-source-option__badge.live {
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }

    .route-source-option__badge.demo {
        background: rgba(249, 115, 22, 0.12);
        color: #c2410c;
    }

    .route-source-modal__cancel {
        width: 100%;
        margin-top: 14px;
        border: 1px solid rgba(148, 163, 184, 0.34);
        border-radius: 14px;
        background: #f8fafc;
        color: #475569;
        padding: 11px 14px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .responder-map-modal {
        position: fixed;
        inset: 0;
        z-index: 10020;
        display: none;
        background: rgba(15, 23, 42, 0.46);
        backdrop-filter: blur(4px);
        padding: 24px;
    }

    .responder-map-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .responder-map-modal-card {
        width: min(100%, 540px);
        max-height: calc(100vh - 48px);
        overflow: hidden;
        border-radius: 28px;
        background: linear-gradient(180deg, #fffdfb 0%, #fff6ef 100%);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        border: 1px solid rgba(198, 61, 38, 0.14);
        display: flex;
        flex-direction: column;
    }

    .responder-map-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 20px 22px 0;
    }

    .responder-map-modal-kicker {
        margin: 0 0 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9a3412;
    }

    .responder-map-modal-title {
        margin: 0;
        font-size: 24px;
        font-weight: 900;
        color: #132033;
    }

    .responder-map-modal-close {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        border: 1px solid rgba(198, 61, 38, 0.12);
        background: #fff7f2;
        color: #9a3412;
        cursor: pointer;
    }

    .responder-map-modal-body {
        padding: 18px 22px 22px;
        overflow-y: auto;
    }

    .responder-map-detail-panel {
        display: grid;
        gap: 14px;
    }

    .responder-map-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        padding: 7px 11px;
        border-radius: 999px;
        background: #fff1ec;
        color: #9a3412;
        font-size: 11px;
        font-weight: 800;
    }

    .responder-map-detail-badge.danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .responder-map-detail-image-wrap {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #fff;
    }

    .responder-map-detail-image {
        width: 100%;
        height: 132px;
        object-fit: cover;
        display: block;
    }

    .responder-map-detail-image-placeholder {
        height: 116px;
        display: grid;
        place-items: center;
        gap: 8px;
        background: linear-gradient(180deg, #fffaf5 0%, #fff2e8 100%);
        color: #9a3412;
        font-size: 11px;
        font-weight: 700;
    }

    .responder-map-detail-image-placeholder i {
        font-size: 22px;
        opacity: 0.8;
    }

    .responder-map-detail-grid {
        display: grid;
        gap: 8px;
    }

    .responder-map-detail-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 12px;
        background: rgba(255,255,255,0.84);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .responder-map-detail-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .responder-map-detail-value {
        flex: 1;
        text-align: right;
        color: #132033;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.35;
    }

    .responder-map-detail-actions {
        display: grid;
        gap: 8px;
    }

    .responder-map-detail-actions.split {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .responder-map-preview-sheet {
        position: fixed;
        inset: 0;
        z-index: 10018;
        display: none;
        pointer-events: none;
    }

    .responder-map-preview-sheet.show {
        display: block;
        pointer-events: auto;
    }

    .responder-map-preview-sheet__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.34);
        backdrop-filter: blur(4px);
    }

    .responder-map-preview-sheet__card {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg, #fffdfb 0%, #fff7f1 100%);
        border-radius: 28px 28px 0 0;
        box-shadow: 0 -18px 40px rgba(15, 23, 42, 0.18);
        padding: 14px 14px calc(16px + env(safe-area-inset-bottom, 0px));
        transform: translateY(100%);
        transition: transform 0.22s ease;
        max-height: min(74svh, 620px);
        overflow: hidden;
    }

    .responder-map-preview-sheet.show .responder-map-preview-sheet__card {
        transform: translateY(0);
    }

    .responder-map-preview-sheet__handle {
        width: 56px;
        height: 5px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.5);
        margin: 0 auto 12px;
    }

    .responder-map-preview-sheet__close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 42px;
        height: 42px;
        border: 1px solid rgba(251, 191, 36, 0.22);
        border-radius: 16px;
        background: rgba(255, 250, 245, 0.96);
        color: #c2410c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 24px rgba(198, 61, 38, 0.12);
    }

    .responder-map-preview-sheet__body {
        overflow-y: auto;
        max-height: calc(min(74svh, 620px) - 42px);
        padding-right: 2px;
    }

    .responder-map-preview-sheet__body .responder-marker-popup {
        width: 100%;
        max-width: none;
        background: transparent;
        box-shadow: none;
    }

    .responder-map-preview-sheet__body .responder-marker-popup-header {
        padding-right: 52px;
    }

    .responder-map-preview-sheet__body .responder-marker-popup-details {
        gap: 10px;
    }

    .responder-map-preview-sheet__body .responder-marker-popup-detail-row {
        grid-template-columns: 1fr;
        gap: 5px;
    }

    .responder-map-preview-sheet__body .responder-marker-popup-detail-value {
        text-align: left;
    }

    .responder-map-preview-sheet__body .responder-marker-popup-actions {
        gap: 10px;
    }

    @media (max-width: 768px) {
        .details-panel.compact-responder {
            left: 10px;
            right: 10px;
            top: auto;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 82px);
            width: auto;
            max-width: none;
            border-radius: 28px;
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.2);
            z-index: 10020;
        }

        .details-panel.compact-responder::before {
            content: "";
            display: block;
            width: 54px;
            height: 5px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.45);
            margin: 12px auto 0;
        }

        .details-panel.compact-responder .details-header {
            padding: 12px 14px;
        }

        .details-panel.compact-responder .details-body {
            max-height: min(56svh, 480px);
            padding: 12px 14px 16px;
        }

        .responder-map-preview-sheet {
            display: block;
            opacity: 0;
            visibility: hidden;
        }

        .responder-map-preview-sheet.show {
            opacity: 1;
            visibility: visible;
        }
    }
    
    .edit-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10080;
    }
    
    .edit-modal.active { display: flex; }
    
    .edit-modal-content {
        background: white;
        border-radius: 16px;
        width: 500px;
        max-width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        padding: 24px;
    }
    
    .edit-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .edit-modal-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .edit-modal-close {
        cursor: pointer;
        font-size: 20px;
        color: #9ca3af;
        transition: color 0.2s;
    }
    
    .edit-modal-close:hover { color: #4b5563; }
    
    .edit-form-group {
        margin-bottom: 16px;
    }
    
    .edit-form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }
    
    .edit-form-group input,
    .edit-form-group textarea,
    .edit-form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .edit-form-group input:focus,
    .edit-form-group textarea:focus,
    .edit-form-group select:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
    
    .edit-modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    
    .edit-btn-save {
        flex: 1;
        background: #10b981;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .edit-btn-save:hover { background: #059669; }
    
    .edit-btn-cancel {
        flex: 1;
        background: #e5e7eb;
        color: #4b5563;
        border: none;
        padding: 10px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .edit-btn-cancel:hover { background: #d1d5db; }

    @media (max-width: 768px) {
        .edit-modal {
            align-items: flex-end;
            background: rgba(15, 23, 42, 0.34);
            padding: 0;
        }

        .edit-modal-content {
            width: 100%;
            max-width: 100%;
            max-height: min(82svh, 620px);
            border-radius: 24px 24px 0 0;
            padding: 16px;
            padding-bottom: calc(16px + env(safe-area-inset-bottom));
            box-shadow: 0 -18px 48px rgba(15, 23, 42, 0.18);
        }

        .edit-modal-content::before {
            content: '';
            display: block;
            width: 52px;
            height: 5px;
            margin: -4px auto 14px;
            border-radius: 999px;
            background: #cbd5e1;
        }

        .edit-modal-header {
            margin-bottom: 12px;
            padding-bottom: 10px;
        }

        .edit-modal-header h3 {
            font-size: 18px;
        }

        .edit-modal-close {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            color: #c2410c;
            background: #fff7ed;
        }

        .edit-form-group {
            margin-bottom: 12px;
        }

        .edit-form-group label {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .edit-form-group input,
        .edit-form-group textarea,
        .edit-form-group select {
            min-height: 42px;
            padding: 9px 11px;
            border-radius: 12px;
            font-size: 14px;
        }

        .edit-modal-actions {
            position: sticky;
            bottom: 0;
            margin: 12px -16px -16px;
            padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
            background: linear-gradient(180deg, rgba(255,255,255,0.84), #fff 34%);
        }

        .edit-btn-save,
        .edit-btn-cancel {
            min-height: 44px;
            border-radius: 14px;
        }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(styleElement);

// ============================================================================
// NOTIFICATION SYSTEM (keeping your existing functions)
// ============================================================================

function showNotification(message, type = 'info', title = 'Notification') {
    return window.PyroNotify?.notify(message, { type, title }) ?? null;
}

// Allow page-specific shells to use the shared themed notification surface.
window.showPyroNotification = showNotification;

// ============================================================================
// DETAILS PANEL (keeping your existing functions)
// ============================================================================

function initDetailsPanel() {
    if (!document.getElementById('detailsPanel')) {
        detailsPanel = document.createElement('div');
        detailsPanel.id = 'detailsPanel';
        detailsPanel.className = 'details-panel';
        detailsPanel.innerHTML = `
            <div class="details-header">
                <h3><i class="fas fa-info-circle"></i> <span>Marker Details</span></h3>
                <div class="details-close" onclick="closeDetailsPanel()"><i class="fas fa-times"></i></div>
            </div>
            <div class="details-body">
                <div id="detailsContent">
                    <div style="text-align: center; padding: 40px 20px; color: #9ca3af;">
                        <i class="fas fa-map-marker-alt" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p>Select a marker to view details</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(detailsPanel);
    } else {
        detailsPanel = document.getElementById('detailsPanel');
    }
}

function initEditModal() {
    if (!document.getElementById('editModal')) {
        editModal = document.createElement('div');
        editModal.id = 'editModal';
        editModal.className = 'edit-modal';
        editModal.setAttribute('aria-hidden', 'true');
        editModal.innerHTML = `
            <div class="edit-modal-content">
                <div class="edit-modal-header">
                    <h3><i class="fas fa-edit"></i> Edit Marker</h3>
                    <div class="edit-modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></div>
                </div>
                <div id="editModalContent"></div>
            </div>
        `;
        editModal.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
        document.body.appendChild(editModal);
    } else {
        editModal = document.getElementById('editModal');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function getStationsOptions(selectedId = null) {
    try {
        const response = await fetch('/api/station');
        if (response.ok) {
            const stations = await response.json();
            const stationsList = stations.data || stations;
            return stationsList.map(station => `
                <option value="${station.station_id}" ${selectedId == station.station_id ? 'selected' : ''}>
                    ${station.station_name}
                </option>
            `).join('');
        }
        return '';
    } catch (error) {
        console.error('Error fetching stations:', error);
        return '';
    }
}

function getChiefMarkerCategoryOptions(selectedCategory = '') {
    const categories = [
        { value: 'fire', label: 'Incident', aliases: ['incident', 'fire'] },
        { value: 'station', label: 'Fire Station', aliases: ['station', 'fire station'] },
        { value: 'hydrant', label: 'Hydrant', aliases: ['hydrant', 'fire hydrant'] },
        { value: 'barangay', label: 'Barangay Hall', aliases: ['barangay', 'barangay hall'] }
    ];

    const normalizedSelected = String(selectedCategory || '').trim().toLowerCase();

    return categories.map(category => {
        const isSelected = category.value === normalizedSelected || category.aliases.includes(normalizedSelected);
        return `
            <option
                value="${category.value}"
                data-category-name="${category.label}"
                data-category-enum="${category.value}"
                ${isSelected ? 'selected' : ''}
            >
                ${category.label}
            </option>
        `;
    }).join('');
}

async function openEditModal(marker) {
    console.log('Opening edit modal for marker:', marker);
    
    initEditModal();
    currentEditMarker = marker;

    if (typeof closeResponderMapPreviewSheet === 'function') {
        closeResponderMapPreviewSheet();
    }
    
    const modalContent = document.getElementById('editModalContent');
    let extraFieldsHtml = '';
    
    if (isStationMarker(marker)) {
        try {
            const stationResponse = await fetch(`/api/station/marker/${marker.marker_id}`);
            if (stationResponse.ok) {
                const stationData = await stationResponse.json();
                extraFieldsHtml = `
                    <div class="edit-form-group">
                        <label><i class="fas fa-building"></i> Station Name</label>
                        <input type="text" id="editStationName" value="${escapeHtml(stationData.station_name || '')}">
                    </div>
                    <div class="edit-form-group">
                        <label><i class="fas fa-barcode"></i> Station Code</label>
                        <input type="text" id="editStationCode" value="${escapeHtml(stationData.station_code || '')}">
                    </div>
                    <div class="edit-form-group">
                        <label><i class="fas fa-phone"></i> Contact Number</label>
                        <input type="text" id="editStationContact" value="${escapeHtml(stationData.contact_number || '')}">
                    </div>
                    <div class="edit-form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="editStationEmail" value="${escapeHtml(stationData.email || '')}">
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching station data:', error);
        }
    } else if (isHydrantMarker(marker)) {
        try {
            const hydrantResponse = await fetch(`/api/hydrant/marker/${marker.marker_id}`);
            if (hydrantResponse.ok) {
                const hydrantData = await hydrantResponse.json();
                const stationsOptions = await getStationsOptions(hydrantData.station_id);
                extraFieldsHtml = `
                    <div class="edit-form-group">
                        <label><i class="fas fa-barcode"></i> Hydrant Code</label>
                        <input type="text" id="editHydrantCode" value="${escapeHtml(hydrantData.hydrant_code || '')}">
                    </div>
                    <div class="edit-form-group">
                        <label><i class="fas fa-building"></i> Assigned Station</label>
                        <select id="editHydrantStation">
                            <option value="">Select Station</option>
                            ${stationsOptions}
                        </select>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching hydrant data:', error);
        }
    } else if (isSchoolMarker(marker)) {
        extraFieldsHtml = '';
    }
    
    const categoriesHtml = getChiefMarkerCategoryOptions(marker.category || marker.markerCategory_id);
    
    modalContent.innerHTML = `
        <div class="edit-form-group">
            <label><i class="fas fa-tag"></i> Title *</label>
            <input type="text" id="editTitle">
        </div>
        <div class="edit-form-group">
            <label><i class="fas fa-align-left"></i> Description</label>
            <textarea id="editDescription" rows="3"></textarea>
        </div>
        <div class="edit-form-group">
            <label><i class="fas fa-layer-group"></i> Category</label>
            <select id="editCategory">
                <option value="">Select Category</option>
                ${categoriesHtml}
            </select>
        </div>
        <div class="edit-form-group">
            <label><i class="fas fa-location-crosshairs"></i> Marker Location</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="number" id="editMarkerLat" step="0.0000001" min="-90" max="90" aria-label="Marker latitude" value="${escapeHtml(String(marker.lat ?? ''))}">
                <input type="number" id="editMarkerLng" step="0.0000001" min="-180" max="180" aria-label="Marker longitude" value="${escapeHtml(String(marker.lng ?? ''))}">
            </div>
            <button type="button" id="editMarkerPickLocation" style="display:flex;width:100%;min-height:42px;margin-top:9px;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(37,99,235,.2);border-radius:12px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:850;cursor:pointer;">
                <i class="fas fa-map-location-dot"></i> Move Marker on Map
            </button>
            <small style="display:block;margin-top:7px;color:#64748b;line-height:1.45;">Click the map or drag the preview marker to its correct real-world position.</small>
        </div>
        ${extraFieldsHtml}
        <div class="edit-modal-actions">
            <button class="edit-btn-save" id="editSaveBtn"><i class="fas fa-save"></i> Update Marker</button>
            <button class="edit-btn-cancel" onclick="closeEditModal()"><i class="fas fa-times"></i> Cancel</button>
        </div>
    `;
    
    setTimeout(() => {
        const titleInput = document.getElementById('editTitle');
        const descriptionInput = document.getElementById('editDescription');
        
        if (titleInput) {
            titleInput.value = marker.title || '';
        }
        if (descriptionInput) {
            descriptionInput.value = marker.description || '';
        }
    }, 50);
    
    editModal.classList.add('active');
    editModal.style.display = 'flex';
    editModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('map-edit-modal-open');
    
    const saveBtn = document.getElementById('editSaveBtn');
    document.getElementById('editMarkerPickLocation')?.addEventListener('click', () => beginMarkerLocationPicker(marker));
    if (saveBtn) {
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
        
        newSaveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            updateMarker(marker.marker_id);
        });
    }
}

async function updateMarker(markerId) {
    const titleInput = document.getElementById('editTitle');
    const descriptionInput = document.getElementById('editDescription');
    const categorySelect = document.getElementById('editCategory');
    const latitude = Number(document.getElementById('editMarkerLat')?.value);
    const longitude = Number(document.getElementById('editMarkerLng')?.value);
    
    const title = titleInput ? titleInput.value.trim() : '';
    const description = descriptionInput ? descriptionInput.value : '';
    const categoryId = categorySelect ? categorySelect.value : '';
    if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90 || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
        showNotification('Choose a valid marker location before updating.', 'warning');
        return;
    }
    
    // if (!title) {
    //     alert('Please enter marker title');
    //     if (titleInput) titleInput.focus();
    //     return;
    // }
    // if (!categoryId) {
    //     alert('Please select a category');
    //     return;
    // }
    
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryName = selectedOption?.getAttribute('data-category-name') || selectedOption?.text || '';
    const categoryEnum = selectedOption?.getAttribute('data-category-enum') || getMarkerCategoryEnumValue(categoryId) || categoryId;
    
    try {
        showLoadingModal('Updating marker...');
        
        const markerResponse = await fetch(`/api/markers/${markerId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                description: description,
                category: categoryEnum
            })
        });
        
        if (!markerResponse.ok) {
            const errorText = await markerResponse.text();
            throw new Error(`Failed to update marker: ${errorText}`);
        }

        const locationResponse = await fetch(`/api/markers/${markerId}/location`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ lat: latitude, lng: longitude })
        });
        if (!locationResponse.ok) {
            const locationError = await locationResponse.json().catch(() => ({}));
            throw new Error(locationError.message || 'Failed to save the marker location.');
        }
        
        if (categoryName.toLowerCase().includes('station')) {
            const stationName = document.getElementById('editStationName')?.value;
            const stationCode = document.getElementById('editStationCode')?.value;
            const stationContact = document.getElementById('editStationContact')?.value;
            const stationEmail = document.getElementById('editStationEmail')?.value;
            
            await fetch(`/api/station/marker/${markerId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    station_name: stationName,
                    station_code: stationCode,
                    contact_number: stationContact,
                    email: stationEmail
                })
            });
        } else if (categoryName.toLowerCase().includes('hydrant')) {
            const hydrantCode = document.getElementById('editHydrantCode')?.value;
            const hydrantStationId = document.getElementById('editHydrantStation')?.value;
            
            await fetch(`/api/hydrant/marker/${markerId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    hydrant_code: hydrantCode,
                    station_id: hydrantStationId
                })
            });
        }
        
        closeModal();
        closeEditModal();
        showNotification('Marker updated successfully!', 'success');
        invalidateMarkerReferenceData();
        await refreshDynamicMapData({ reason: 'marker-updated' });
        
    } catch (error) {
        console.error('Error updating marker:', error);
        alert('Error updating marker: ' + error.message);
    } finally {
        closeModal();
    }
}

function ensureMarkerLocationPickerToolbar() {
    let toolbar = document.getElementById('markerLocationPickerToolbar');
    if (toolbar) return toolbar;
    toolbar = document.createElement('section');
    toolbar.id = 'markerLocationPickerToolbar';
    toolbar.hidden = true;
    toolbar.style.cssText = 'position:fixed;left:50%;bottom:28px;z-index:2450;display:flex;max-width:min(650px,calc(100vw - 32px));align-items:center;gap:13px;padding:13px 14px;border:1px solid rgba(37,99,235,.2);border-radius:18px;background:rgba(255,255,255,.98);box-shadow:0 18px 44px rgba(15,23,42,.22);transform:translateX(-50%);';
    toolbar.style.display = 'none';
    toolbar.innerHTML = `
        <div style="display:grid;width:42px;height:42px;flex:0 0 auto;place-items:center;border-radius:13px;background:#eff6ff;color:#2563eb;"><i class="fas fa-location-crosshairs"></i></div>
        <div style="min-width:0;flex:1;"><strong style="display:block;color:#0f172a;font-size:13px;">Move marker</strong><span data-marker-picker-coordinates style="display:block;margin-top:3px;color:#64748b;font-size:11px;">Click the map or drag the marker.</span></div>
        <button type="button" data-marker-picker-cancel style="min-height:38px;padding:0 13px;border:1px solid #cbd5e1;border-radius:11px;background:#fff;color:#475569;font-size:11px;font-weight:850;cursor:pointer;">Cancel</button>
        <button type="button" data-marker-picker-use style="min-height:38px;padding:0 13px;border:0;border-radius:11px;background:#2563eb;color:#fff;font-size:11px;font-weight:850;cursor:pointer;"><i class="fas fa-check"></i> Use Position</button>
    `;
    document.body.appendChild(toolbar);
    L.DomEvent.disableClickPropagation(toolbar);
    toolbar.querySelector('[data-marker-picker-cancel]')?.addEventListener('click', () => finishMarkerLocationPicker(false));
    toolbar.querySelector('[data-marker-picker-use]')?.addEventListener('click', () => finishMarkerLocationPicker(true));
    return toolbar;
}

function updateMarkerLocationPickerPosition(latlng) {
    if (!markerLocationPicker || !latlng) return;
    markerLocationPicker.latlng = L.latLng(latlng.lat, latlng.lng);
    markerLocationPicker.preview.setLatLng(markerLocationPicker.latlng);
    const label = document.querySelector('[data-marker-picker-coordinates]');
    if (label) label.textContent = `${markerLocationPicker.latlng.lat.toFixed(7)}, ${markerLocationPicker.latlng.lng.toFixed(7)}`;
}

function beginMarkerLocationPicker(marker) {
    if (!window.map || !marker?.marker_id) return;
    const lat = Number(document.getElementById('editMarkerLat')?.value ?? marker.lat);
    const lng = Number(document.getElementById('editMarkerLng')?.value ?? marker.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        showNotification('This marker has no valid starting coordinates.', 'warning');
        return;
    }

    const sourceLayer = existingMarkers[marker.marker_id];
    const sourceOpacity = Number(sourceLayer?.options?.opacity ?? 1);
    sourceLayer?.setOpacity?.(0.25);
    const previewOptions = { draggable: true, zIndexOffset: 2000, opacity: 0.9 };
    if (sourceLayer?.options?.icon) previewOptions.icon = sourceLayer.options.icon;
    const preview = L.marker([lat, lng], previewOptions).addTo(window.map);
    const mapClickHandler = (event) => updateMarkerLocationPickerPosition(event.latlng);
    markerLocationPicker = { markerId: marker.marker_id, preview, latlng: L.latLng(lat, lng), mapClickHandler, sourceLayer, sourceOpacity };
    preview.on('dragend', () => updateMarkerLocationPickerPosition(preview.getLatLng()));
    window.map.on('click', mapClickHandler);
    window.map.closePopup();
    editModal.style.display = 'none';
    editModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('map-edit-modal-open');
    const toolbar = ensureMarkerLocationPickerToolbar();
    toolbar.hidden = false;
    toolbar.style.display = 'flex';
    updateMarkerLocationPickerPosition(markerLocationPicker.latlng);
    window.map.setView([lat, lng], Math.max(window.map.getZoom(), 17), { animate: true });
}

function finishMarkerLocationPicker(usePosition) {
    if (!markerLocationPicker) return;
    const { preview, mapClickHandler, latlng, sourceLayer, sourceOpacity } = markerLocationPicker;
    window.map?.off('click', mapClickHandler);
    if (preview && window.map?.hasLayer(preview)) window.map.removeLayer(preview);
    sourceLayer?.setOpacity?.(sourceOpacity);
    markerLocationPicker = null;
    const toolbar = document.getElementById('markerLocationPickerToolbar');
    if (toolbar) {
        toolbar.hidden = true;
        toolbar.style.display = 'none';
    }
    if (usePosition) {
        const latInput = document.getElementById('editMarkerLat');
        const lngInput = document.getElementById('editMarkerLng');
        if (latInput) latInput.value = latlng.lat.toFixed(7);
        if (lngInput) lngInput.value = latlng.lng.toFixed(7);
    }
    if (editModal && currentEditMarker) {
        editModal.classList.add('active');
        editModal.style.display = 'flex';
        editModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('map-edit-modal-open');
    }
}

function closeEditModal() {
    if (markerLocationPicker) finishMarkerLocationPicker(false);
    if (editModal) {
        if (editModal.contains(document.activeElement)) {
            document.activeElement?.blur?.();
        }
        editModal.classList.remove('active');
        editModal.style.display = 'none';
        editModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('map-edit-modal-open');
        currentEditMarker = null;
    }
}

async function showDetailsPanel(markerData, imagePath = null) {
    if (!detailsPanel) {
        initDetailsPanel();
    }
    
    const detailsContent = document.getElementById('detailsContent');
    if (detailsContent) {
        detailsContent.innerHTML = `
            <div style="padding: 12px 0 4px;">
                <div style="font-weight: 700; color: #7c2d12; margin-bottom: 12px;">Fetching details</div>
                <div class="details-shimmer-shell" aria-hidden="true">
                    <div class="details-shimmer-block" style="height: 180px;"></div>
                    <div class="details-shimmer-block" style="height: 22px; width: 62%;"></div>
                    <div class="details-shimmer-block" style="height: 16px; width: 88%;"></div>
                    <div class="details-shimmer-block" style="height: 16px; width: 74%;"></div>
                    <div class="details-shimmer-block" style="height: 110px;"></div>
                </div>
            </div>
        `;
    }
    detailsPanel.classList.add('visible');

    const title = markerData.title || 'Untitled Marker';
    const description = markerData.description || 'No description available';
    const lat = markerData.lat || 'N/A';
    const lng = markerData.lng || 'N/A';
    const address = await setName(lat, lng);
    const category = getCategoryName(markerData);
    const created = markerData.created_at ? new Date(markerData.created_at).toLocaleString() : 'N/A';
    
    let extraInfoHtml = '';
    
    // Incident details
    if (isIncidentMarker(markerData)) {
        const incidentStatus = normalizeIncidentStatus(markerData.incident_status || markerData.status || 'reported');
        const incidentTypeName = 'Fire';
        
        let statusColor = '#ef4444';
        let statusIcon = 'fa-exclamation-triangle';
        
        let typeIcon = '🔥';
        let typeName = incidentTypeName;
        switch(incidentTypeName.toLowerCase()) {
            case 'fire': typeIcon = '🔥'; typeName = 'Fire'; break;
            case 'medical emergency': typeIcon = '🚑'; typeName = 'Medical Emergency'; break;
            case 'rescue': typeIcon = '🆘'; typeName = 'Rescue Operation'; break;
            case 'hazardous materials': typeIcon = '⚠️'; typeName = 'Hazardous Materials'; break;
            case 'false alarm': typeIcon = '❌'; typeName = 'False Alarm'; break;
        }
        
        typeIcon = 'Fire';
        typeName = 'Fire';

        if (incidentStatus === 'en-route') {
            statusColor = '#f59e0b';
            statusIcon = 'fa-truck';
        } else if (incidentStatus === 'on-scene') {
            statusColor = '#8b5cf6';
            statusIcon = 'fa-crosshairs';
        } else if (incidentStatus === 'controlled') {
            statusColor = '#10b981';
            statusIcon = 'fa-check-circle';
        } else if (incidentStatus === 'closed') {
            statusColor = '#6b7280';
            statusIcon = 'fa-folder';
        }
        
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%); border-left: 4px solid ${statusColor};">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: ${statusColor};">
                    <i class="fas ${statusIcon}"></i> Incident Details
                </h4>
                <div class="details-info-item">
                    <span class="details-info-label">Status:</span>
                    <span class="details-info-value" style="color: ${statusColor}; font-weight: 600;">${incidentStatus.toUpperCase()}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Type:</span>
                    <span class="details-info-value"><span style="font-size: 16px; margin-right: 6px;">${typeIcon}</span> ${typeName}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Reported:</span>
                    <span class="details-info-value">${markerData.reported_at ? new Date(markerData.reported_at).toLocaleString() : created}</span>
                </div>
            </div>
        `;
    }
    
    // Hydrant details
    if (isHydrantMarker(markerData) && markerData.hydrant) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%); border-left: 4px solid #c63d26;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #c63d26;">
                    <i class="fas fa-water"></i> Hydrant Details
                </h4>
                <div class="details-info-item">
                    <span class="details-info-label">Hydrant Code:</span>
                    <span class="details-info-value">${markerData.hydrant.hydrant_code || 'N/A'}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Assigned Station:</span>
                    <span class="details-info-value">${markerData.hydrant.station?.station_name || 'Not Assigned'}</span>
                </div>
            </div>
        `;
    }
    
    // Station details
    if (isStationMarker(markerData) && markerData.station) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%); border-left: 4px solid #c63d26;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #c63d26;">
                    <i class="fas fa-building"></i> Station Details
                </h4>
                <div class="details-info-item">
                    <span class="details-info-label">Station Name:</span>
                    <span class="details-info-value">${markerData.station.station_name || 'N/A'}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Station Code:</span>
                    <span class="details-info-value">${markerData.station.station_code || 'N/A'}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Contact:</span>
                    <span class="details-info-value">${markerData.station.contact_number || 'N/A'}</span>
                </div>
                <div class="details-info-item">
                    <span class="details-info-label">Email:</span>
                    <span class="details-info-value">${markerData.station.email || 'N/A'}</span>
                </div>
            </div>
        `;
    }
    
    // School details
    if (isSchoolMarker(markerData) && markerData.school) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%); border-left: 4px solid #c63d26;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #c63d26;">
                    <i class="fas fa-school"></i> School Details
                </h4>
                <div class="details-info-item">
                    <span class="details-info-label">School Name:</span>
                    <span class="details-info-value">${markerData.school.school_name || 'N/A'}</span>
                </div>
            </div>
        `;
    }
    
    let imageHtml = imagePath 
        ? `<img src="${imagePath}" class="details-image" alt="Marker image">`
        : `<div style="width: 100%; height: 200px; background: #f3f4f6; border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
            <i class="fas fa-image" style="font-size: 48px;"></i>
        </div>`;
    
    detailsContent.innerHTML = `
        ${imageHtml}
        <div class="details-info">
            <div class="details-info-item">
                <span class="details-info-label">Title:</span>
                <span class="details-info-value">${escapeHtml(title)}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Category:</span>
                <span class="details-info-value">${escapeHtml(category)}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Coordinates:</span>
                <span class="details-info-value">${escapeHtml(address)}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Created:</span>
                <span class="details-info-value">${escapeHtml(created)}</span>
            </div>
        </div>
        ${extraInfoHtml}
        <div class="details-description">
            <strong>Description:</strong><br>
            ${escapeHtml(description)}
        </div>
    `;
    
    currentDetailsMarker = markerData;
}

function closeDetailsPanel() {
    if (detailsPanel) {
        detailsPanel.classList.remove('visible');
        detailsPanel.classList.remove('compact-responder');
        currentDetailsMarker = null;
        currentDetailsIncident = null;
    }
}

function openCurrentMapDetailEditor() {
    if (!currentDetailsMarker) {
        return;
    }

    openEditModal(currentDetailsMarker);
}

function deleteCurrentMapDetailMarker() {
    if (!currentDetailsMarker?.marker_id) {
        return;
    }

    deleteMarker(currentDetailsMarker.marker_id);
}

function respondCurrentMapDetail() {
    if (!currentDetailsMarker) {
        return;
    }

    respondIncident(currentDetailsMarker);
}

function routeCurrentMapDetail(source = 'real') {
    if (!currentDetailsMarker) {
        return;
    }

    if (isIncidentMarker(currentDetailsMarker)) {
        window.calculateRouteToIncident(
            currentDetailsIncident?.incident_id ?? currentDetailsMarker.incident_id ?? null,
            currentDetailsMarker.marker_id,
            { source }
        );
        return;
    }

    if (isStationMarker(currentDetailsMarker)) {
        window.calculateRouteToStation(currentDetailsMarker.marker_id);
    }
}

// ============================================================================
// FUNCTION DEFINITIONS
// ============================================================================

export function getName(lat, lon) {
    const useMobileMarkerModal = window.matchMedia?.('(max-width: 640px)').matches;
    let popupmarker = null;

    if (useMobileMarkerModal) {
        selectedLat = lat;
        selectedLng = lon;
        if (window.map?.closePopup) {
            window.map.closePopup();
        }
        openModal({ fromMapClick: true });

        const descriptionInput = document.getElementById('markerDescription');
        if (descriptionInput && !descriptionInput.value) {
            descriptionInput.placeholder = 'Resolving selected address...';
        }
    } else {
        popupmarker = L.popup()
            .setLatLng([lat, lon])
            .setContent("Loading address...")
            .openOn(window.map);
    }

    fetch('/api/reverse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ lat: lat, lon: lon })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.features || data.features.length === 0) {
            const descriptionInput = document.getElementById('markerDescription');
            if (useMobileMarkerModal && descriptionInput && !descriptionInput.value) {
                descriptionInput.placeholder = 'Address unavailable. You can still save this marker.';
            }
            return;
        }
        const address = data.features[0].properties.formatted;

        if (useMobileMarkerModal) {
            const descriptionInput = document.getElementById('markerDescription');
            if (descriptionInput && !descriptionInput.value) {
                descriptionInput.value = address;
                descriptionInput.placeholder = 'Marker details';
            }
            return;
        }

        popupmarker.setLatLng([lat, lon]).setContent(`
            <div style="min-width: 200px;">
                <p style="margin: 0 0 10px 0; font-size: 13px;">${address}</p>
                <button id="popupActionBtn" style="background-color: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Add as Marker
                </button>
            </div>
        `);
        if (popupmarker) popupmarker.openOn(window.map);
        const btn = document.getElementById('popupActionBtn');
        if (btn) {
            btn.addEventListener('click', function() {
                selectedLat = lat;
                selectedLng = lon;
                openModal({ fromMapClick: true });
            });
        }
        popupmarker.on('popupclose', function() {
            window.map.removeLayer(popupmarker);
            popupmarker = null;
        });
    })
    .catch(err => {
        const descriptionInput = document.getElementById('markerDescription');
        if (useMobileMarkerModal && descriptionInput && !descriptionInput.value) {
            descriptionInput.placeholder = 'Address unavailable. You can still save this marker.';
        }
        console.error(err);
    });
}

function clearAllMarkers() {
    try {
        const groups = [stationFeatureGroup, incidentFeatureGroup, hydrantFeatureGroup, riskHazardFeatureGroup, otherFeatureGroup].filter(Boolean);

        groups.forEach((group) => {
            try {
                const layers = [];
                group.eachLayer((layer) => layers.push(layer));
                layers.forEach((layer) => {
                    try {
                        group.removeLayer(layer);
                    } catch (layerError) {
                        console.warn('Failed to remove marker layer cleanly:', layerError);
                    }
                });
            } catch (groupError) {
                console.warn('Failed to clear marker feature group cleanly:', groupError);
            }
        });

        existingMarkers = {};
    } catch (error) {
        console.error('Error clearing markers:', error);
    }
}

function getNormalizedStationId(routeData = {}) {
    const candidate = routeData.route_station_id ?? routeData.station_id ?? routeData.routeStationId ?? null;
    return normalizeNullableInt(candidate);
}

function ensureRouteOverlayRegistered(layer, label) {
    if (!routeOverlayControl || !layer || !label) {
        return;
    }

    if (!routeLayerMetadata.has(label)) {
        routeLayerMetadata.set(label, { label });
        routeOverlayControl.addOverlay(layer, label);
    }
}

function extractRouteLatLngs(routeLike) {
    let coordinates = routeLike;

    if (routeLike && typeof routeLike === 'object') {
        if (routeLike.geometry && routeLike.geometry.coordinates) {
            coordinates = routeLike.geometry.coordinates;
        } else if (routeLike.coordinates !== undefined) {
            coordinates = routeLike.coordinates;
        }
    }

    if (typeof coordinates === 'string') {
        try {
            coordinates = JSON.parse(coordinates);
        } catch (error) {
            console.error('Failed to parse route coordinates string:', error);
            return [];
        }
    }

    if (coordinates && typeof coordinates === 'object' && !Array.isArray(coordinates) && coordinates.coordinates) {
        coordinates = coordinates.coordinates;
    }

    if (!Array.isArray(coordinates) || coordinates.length === 0) {
        return [];
    }

    const coordinatePairs = [];
    const collectCoordinatePairs = (value) => {
        if (!Array.isArray(value)) {
            return;
        }

        if (
            value.length >= 2
            && !Array.isArray(value[0])
            && !Array.isArray(value[1])
            && Number.isFinite(Number(value[0]))
            && Number.isFinite(Number(value[1]))
        ) {
            coordinatePairs.push(value);
            return;
        }

        value.forEach(collectCoordinatePairs);
    };

    collectCoordinatePairs(coordinates);

    const latlngs = [];
    for (let i = 0; i < coordinatePairs.length; i++) {
        const coord = coordinatePairs[i];
        if (!Array.isArray(coord) || coord.length < 2) {
            continue;
        }

        const lng = Number(coord[0]);
        const lat = Number(coord[1]);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            continue;
        }

        latlngs.push([lat, lng]);
    }

    return latlngs;
}

function getRouteStationLayer(routeData = {}) {
    const normalizedStationId = getNormalizedStationId(routeData);
    const stationId = normalizedStationId ?? 'unassigned';
    const label = stationId === 'unassigned'
        ? 'Routes - Unassigned'
        : `Routes - Station ${stationId}`;

    if (routeStationLayers.has(stationId)) {
        const existingLayer = routeStationLayers.get(stationId);
        if (window.map && !window.map.hasLayer(existingLayer)) {
            existingLayer.addTo(window.map);
        }
        ensureRouteOverlayRegistered(existingLayer, label);
        return existingLayer;
    }

    const layer = L.layerGroup().addTo(window.map);

    routeStationLayers.set(stationId, layer);
    ensureRouteOverlayRegistered(layer, label);

    return layer;
}

function normalizeNullableInt(value) {
    if (value === null || value === undefined || value === '' || value === 'null') {
        return null;
    }

    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? null : parsed;
}

function shouldDisplayRouteForCurrentUser(routeData = {}) {
    if (isDemoRouteSource(routeData.route_source || routeData.routeSource)) {
        return true;
    }

    const currentUserTeamId = normalizeNullableInt(window.userTeamId);
    const currentUserStationId = normalizeNullableInt(window.userStationId);
    const routeTeamId = normalizeNullableInt(routeData.route_team_id);
    const routeStationId = normalizeNullableInt(routeData.route_station_id);

    const teamMatches = currentUserTeamId !== null && routeTeamId !== null
        ? routeTeamId === currentUserTeamId
        : null;

    const stationMatches = currentUserStationId !== null && routeStationId !== null
        ? routeStationId === currentUserStationId
        : null;

    if (teamMatches === true || stationMatches === true) {
        return true;
    }

    if (teamMatches === false && stationMatches === false) {
        return false;
    }

    return true;
}

function clearExistingRouteRendering(routeId) {
    const rendering = routeRenderingById.get(routeId);
    if (!rendering) {
        return;
    }

    rendering.layers.forEach((layer) => {
        try {
            rendering.group.removeLayer(layer);
        } catch (error) {
            console.warn('Failed to remove route layer cleanly:', error);
        }
    });

    routeRenderingById.delete(routeId);
}

function registerRouteLayer(routeId, group, layer) {
    if (!group) {
        group = getRouteStationLayer({});
    }

    if (group && window.map && !window.map.hasLayer(group)) {
        group.addTo(window.map);
    }

    if (!routeRenderingById.has(routeId)) {
        routeRenderingById.set(routeId, { group, layers: [] });
    }

    routeRenderingById.get(routeId).layers.push(layer);
    group.addLayer(layer);
    return layer;
}

function removeRegisteredRouteLayer(routeId, layer) {
    const rendering = routeRenderingById.get(routeId);
    if (!rendering || !layer) {
        return;
    }

    try {
        rendering.group.removeLayer(layer);
    } catch (error) {
        console.warn('Failed to remove registered route layer:', error);
    }

    rendering.layers = rendering.layers.filter((existingLayer) => existingLayer !== layer);
}

function unregisterRouteRendering(routeId) {
    clearExistingRouteRendering(routeId);
}

async function fetchCoordinate(options = {}) {
    const { preferSeeded = false, revalidate = true, force = false } = options;
    let renderedFallbackData = false;
    let fetchSequence = null;
    let fetchController = null;

    try {
        if (force) {
            window.removeMapPlacementMarker?.({ clearCoordinates: false });
        }

        const seededMarkers = Array.isArray(window.mapData?.marker) && window.mapData.marker.length > 0
            ? window.mapData.marker
            : window.mapData?.usermark;
        if (preferSeeded && Array.isArray(seededMarkers) && seededMarkers.length > 0) {
            // Paint markers immediately for fast first render.
            renderMarkersQuick(seededMarkers);
            renderedFallbackData = true;

            // Upgrade marker details asynchronously without blocking first paint.
            setTimeout(() => {
                updateCoordinate(seededMarkers, { fetchSupplemental: false }).catch((error) => {
                    console.error('Background marker enrichment failed:', error);
                });
            }, 0);

            if (!revalidate) {
                return;
            }
        }

        const cachedMarkers = readStaticMapCache('markers');
        if ((!Array.isArray(seededMarkers) || seededMarkers.length === 0) && Array.isArray(cachedMarkers) && cachedMarkers.length > 0) {
            renderMarkersQuick(cachedMarkers);
            renderedFallbackData = true;

            if (!revalidate) {
                return;
            }
        }

        if (!force && shouldSkipStaticLayerRefetch(lastMarkersFetchAt)) {
            return;
        }

        fetchSequence = ++markerFeedFetchSequence;
        markerFeedFetchController?.abort();
        fetchController = new AbortController();
        markerFeedFetchController = fetchController;
        const response = await fetch(getMarkerFeedEndpoint(), {
            cache: 'no-store',
            signal: fetchController.signal
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        if (fetchSequence !== markerFeedFetchSequence) {
            return;
        }
        let coordinates;
        if (result.data && Array.isArray(result.data)) {
            coordinates = result.data;
        } else if (Array.isArray(result)) {
            coordinates = result;
        } else {
            showNotification('Failed to load markers: Invalid response format', 'error');
            return;
        }
        if (!coordinates) return;
        await updateCoordinate(coordinates, {
            fetchSupplemental: true,
            forceIncidents: Boolean(options.forceIncidents)
        });
        lastMarkersFetchAt = Date.now();
        writeStaticMapCache('markers', coordinates);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        console.error('Fetch error in fetchCoordinate:', error);
        if (!renderedFallbackData && !isChiefDashboardMapInstance()) {
            showNotification('Failed to fetch markers: ' + error.message, 'error');
        }
    } finally {
        if (fetchSequence === markerFeedFetchSequence) {
            markerFeedFetchController = null;
        }
    }
}

window.refreshMapMarkers = function(options = {}) {
    return fetchCoordinate({
        preferSeeded: false,
        revalidate: true,
        force: true,
        forceIncidents: true,
        ...options
    });
};

function getIncidentVisibilityState(marker, incidents, incidentLookup = null) {
    if (!marker || !isIncidentMarker(marker)) {
        return { visible: true, incident: null };
    }

    if (!shouldDisplayMarker(marker)) {
        return { visible: false, incident: null };
    }

    const linkedIncident = findIncidentInLookup(marker, incidentLookup)
        || incidents.find((incident) => Number(incident.marker_id) === Number(marker.marker_id))
        || incidents.find((incident) => Number(incident.marker?.marker_id) === Number(marker.marker_id))
        || null;

    if (!linkedIncident) {
        return { visible: true, incident: null };
    }

    const rawStatus = normalizeIncidentStatus(linkedIncident.status || linkedIncident.incident_status || 'reported');

    if (rawStatus === 'closed') {
        return { visible: false, incident: linkedIncident };
    }

    return { visible: true, incident: linkedIncident };
}

function buildMarkerStateSignature(coordinates, incidents) {
    const normalizedCoordinates = normalizeCoordinatePayload(coordinates);
    const incidentLookup = buildIncidentLookup(incidents);

    return normalizedCoordinates
        .map((marker) => {
            const visibility = getIncidentVisibilityState(marker, incidents, incidentLookup);
            const linkedIncident = visibility.incident;
            return [
                marker.marker_id ?? 'unknown',
                marker.updated_at ?? marker.created_at ?? '',
                marker.lat ?? '',
                marker.lng ?? '',
                visibility.visible ? 'visible' : 'hidden',
                linkedIncident?.incident_id ?? '',
                linkedIncident?.incident_status_id ?? linkedIncident?.status ?? '',
                linkedIncident?.updated_at ?? ''
            ].join(':');
        })
        .sort()
        .join('|');
}

async function pollMarkerStateChanges() {
    if (!isPageVisible()) {
        return;
    }

    if (markerStatePollingInFlight) {
        return;
    }

    markerStatePollingInFlight = true;

    try {
        const [markersResponse, incidentsResponse] = await Promise.all([
            fetchLiveResponseOnce('markers-poll', getMarkerFeedEndpoint(), {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }),
            getLatestIncidentsData({ force: true, useLiveRequest: true })
        ]);

        if (!markersResponse.ok) {
            throw new Error(`Failed to poll markers: ${markersResponse.status}`);
        }

        const markersPayload = await markersResponse.json();
        const incidentsPayload = Array.isArray(incidentsResponse)
            ? incidentsResponse
            : (Array.isArray(window.incidentsData) ? window.incidentsData : []);
        const coordinates = normalizeCoordinatePayload(markersPayload);
        const incidents = normalizeCoordinatePayload(incidentsPayload);
        const nextSignature = buildMarkerStateSignature(coordinates, incidents);

        if (lastMarkerStateSignature === nextSignature) {
            return;
        }

        lastMarkerStateSignature = nextSignature;
        await updateCoordinate(coordinates, {
            fetchSupplemental: false,
            incidents
        });
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        console.error('Marker state polling failed:', error);
    } finally {
        markerStatePollingInFlight = false;
    }
}

async function pollBoundaryStateChanges() {
    if (!isPageVisible() || boundaryStatePollingInFlight) {
        return;
    }

    boundaryStatePollingInFlight = true;

    try {
        const response = await fetchLiveResponseOnce('boundaries-poll', '/api/shapes/latest', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        if (!response.ok) {
            throw new Error(`Failed to poll boundaries: ${response.status}`);
        }

        const payload = await response.json();
        const nextSignature = String(payload?.signature || `${payload?.count || 0}:${payload?.updated_at || ''}`);
        if (!lastBoundaryStateSignature) {
            lastBoundaryStateSignature = nextSignature;
            return;
        }
        if (lastBoundaryStateSignature === nextSignature) {
            return;
        }

        lastBoundaryStateSignature = nextSignature;
        await refreshDynamicMapData({ reason: 'boundary-change' });
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.error('Boundary state polling failed:', error);
        }
    } finally {
        boundaryStatePollingInFlight = false;
    }
}

function normalizeCoordinatePayload(data) {
    if (data?.data && Array.isArray(data.data)) return data.data;
    if (Array.isArray(data)) return data;
    if (data && typeof data === 'object') return [data];
    return [];
}

function invalidateMarkerReferenceData() {
    markerReferenceCache = {
        incidentTypes: null,
        hydrants: null,
        schools: null,
        stations: null,
        loadedAt: 0,
        requestPromise: null
    };
    lastStationsFetchAt = 0;
    clearStaticMapCache('stations');
}

function invalidateShapeData() {
    lastShapesFetchAt = 0;
    clearStaticMapCache('shapes');
}

function renderMarkersQuick(data) {
    const coordinates = normalizeCoordinatePayload(data);
    if (!coordinates.length) return;

    clearAllMarkers();

    for (const marker of coordinates) {
        if (!marker || !marker.lat || !marker.lng) continue;
        if (!shouldDisplayMarker(marker)) continue;

        const lat = parseFloat(marker.lat);
        const lng = parseFloat(marker.lng);
        if (Number.isNaN(lat) || Number.isNaN(lng)) continue;

        const tempIcon = getIconByCategoryId(marker.markerCategory_id, '', marker);
        const popupContent = `
            <div style="min-width: 180px;">
                <strong>${marker.title || `Marker ${marker.marker_id ?? ''}`}</strong>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    ${marker.description || 'Loading details...'}
                </div>
            </div>
        `;

        const markerOptions = {
            icon: tempIcon,
            riseOnHover: true,
        };

        if (['risk', 'hazard'].includes(getMarkerCategoryKey(marker))) {
            markerOptions.zIndexOffset = 850;
        }

        const newMarker = L.marker([lat, lng], markerOptions).bindPopup(popupContent, {
            minWidth: 100,
            maxWidth: 260
        });

        addMarkerToFeatureGroup(marker, newMarker);
        existingMarkers[marker.marker_id] = newMarker;
    }
}

async function getMarkerReferenceData(options = {}) {
    const { force = false } = options;
    const shouldRefreshCache = force || !markerReferenceCache.loadedAt;

    if (!shouldRefreshCache &&
        markerReferenceCache.incidentTypes &&
        markerReferenceCache.hydrants &&
        markerReferenceCache.schools &&
        markerReferenceCache.stations) {
        return markerReferenceCache;
    }

    if (markerReferenceCache.requestPromise) {
        return markerReferenceCache.requestPromise;
    }

    markerReferenceCache.requestPromise = (async () => {
        const [
            hydrantsResponse,
            stationsResponse
        ] = await Promise.all([
            fetch('/api/hydrant'),
            fetch('/api/station')
        ]);

        let incidentTypes = [];
        let hydrants = markerReferenceCache.hydrants || [];
        let schools = [];
        let stationsData = markerReferenceCache.stations || [];

        if (hydrantsResponse.ok) {
            const hydrantData = await hydrantsResponse.json();
            hydrants = hydrantData.data || hydrantData;
        }

        if (stationsResponse.ok) {
            const stationData = await stationsResponse.json();
            stationsData = stationData.data || stationData;
        }

        writeStaticMapCache('stations', Array.isArray(stationsData) ? stationsData : []);

        markerReferenceCache = {
            incidentTypes,
            hydrants,
            schools,
            stations: stationsData,
            loadedAt: Date.now(),
            requestPromise: null
        };

        return markerReferenceCache;
    })().catch((error) => {
        markerReferenceCache.requestPromise = null;
        throw error;
    });

    return markerReferenceCache.requestPromise;
}

async function getLatestIncidentsData(options = {}) {
    const { force = false, useLiveRequest = false } = options;
    const shouldUseChiefLightweightEndpoint = isChiefCommandMapContext();
    const cacheIsFresh = !force
        && Array.isArray(latestIncidentsCache.data)
        && (Date.now() - latestIncidentsCache.loadedAt) < LATEST_INCIDENTS_CACHE_TTL_MS;

    if (cacheIsFresh) {
        return latestIncidentsCache.data;
    }

    if (latestIncidentsCache.requestPromise) {
        return latestIncidentsCache.requestPromise;
    }

    latestIncidentsCache.requestPromise = (async () => {
        const liveRequestKey = shouldUseChiefLightweightEndpoint
            ? 'chief-incident-map-changes-poll'
            : 'incident-latest-poll';
        const hasChiefCursor = shouldUseChiefLightweightEndpoint
            && useLiveRequest
            && Array.isArray(latestIncidentsCache.data)
            && latestIncidentsCache.data.length > 0
            && Boolean(latestIncidentsCache.cursorUpdatedAt);
        const requestUrl = shouldUseChiefLightweightEndpoint
            ? (hasChiefCursor
                ? `/station-admin/api/incidents/map/changes?${new URLSearchParams({
                    cursor_updated_at: latestIncidentsCache.cursorUpdatedAt,
                    cursor_incident_id: String(latestIncidentsCache.cursorIncidentId || 0)
                }).toString()}`
                : '/station-admin/api/incidents/map')
            : '/api/incident/latest';
        const response = useLiveRequest
            ? await fetchLiveResponseOnce(liveRequestKey, requestUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            : await fetch(requestUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

        if (!response.ok) {
            throw new Error(`Failed to fetch latest incidents: ${response.status}`);
        }

        const incidentData = await response.json();
        const rawIncidents = incidentData.data || incidentData || [];
        const normalizedIncidents = Array.isArray(rawIncidents) ? rawIncidents : [];

        if (shouldUseChiefLightweightEndpoint) {
            const responseCursorUpdatedAt = incidentData?.meta?.cursor?.updated_at || null;
            const responseCursorIncidentId = Number(incidentData?.meta?.cursor?.incident_id || 0) || 0;
            const nextIncidents = commitLatestIncidentsCache(normalizedIncidents, {
                replace: !hasChiefCursor,
                cursorUpdatedAt: responseCursorUpdatedAt,
                cursorIncidentId: responseCursorIncidentId
            });

            return nextIncidents;
        }

        return commitLatestIncidentsCache(normalizedIncidents, { replace: true });
    })().catch((error) => {
        latestIncidentsCache.requestPromise = null;
        throw error;
    });

    return latestIncidentsCache.requestPromise;
}

async function updateCoordinate(data, options = {}) {
    const {
        fetchSupplemental = true,
        incidents: preloadedIncidents = null,
        forceIncidents = false
    } = options;

    try {
        let incidents = Array.isArray(preloadedIncidents) ? preloadedIncidents : [];
        let markerReferences = {
            incidentTypes: markerReferenceCache.incidentTypes || [],
            hydrants: markerReferenceCache.hydrants || [],
            schools: markerReferenceCache.schools || [],
            stations: markerReferenceCache.stations || []
        };

        if (fetchSupplemental) {
            const [incidentResponseResult, markerReferencesResult] = await Promise.allSettled([
                getLatestIncidentsData({ force: forceIncidents }),
                getMarkerReferenceData()
            ]);

            if (incidentResponseResult.status === 'fulfilled') {
                incidents = incidentResponseResult.value;
            }

            if (markerReferencesResult.status === 'fulfilled' && markerReferencesResult.value) {
                markerReferences = markerReferencesResult.value;
            }
        }

        if (!incidents.length && Array.isArray(window.incidentsData)) {
            incidents = window.incidentsData;
        }
        if (Array.isArray(incidents)) {
            window.incidentsData = incidents;
            if (roleName && roleName !== 'citizen' && typeof window.syncIncidentAlertFeed === 'function') {
                window.syncIncidentAlertFeed(`map-${roleName}-incidents`, incidents, {
                    titlePrefix: roleName === 'chief' ? 'Command Alert' : 'Responder Alert',
                    nearestStationOnly: ['chief', 'firefighter'].includes(roleName)
                });
            }
        }

        const incidentTypes = markerReferences.incidentTypes || [];
        window.incidentypes = incidentTypes;
        window.incidentTypes = incidentTypes;
        
        const coordinates = normalizeCoordinatePayload(data);
        if (coordinates.length === 0) {
            // A successful empty feed means prior incident layers must not remain on screen.
            clearAllMarkers();
            return;
        }
        
        const hydrants = markerReferences.hydrants || [];
        const schools = markerReferences.schools || [];
        const stations = markerReferences.stations || [];
        const incidentLookup = buildIncidentLookup(incidents);
        window.incidentsLookup = incidentLookup;
        const stationByMarkerId = buildLookupMap(stations, (station) => station?.marker_id);
        const stationByStationId = buildLookupMap(stations, (station) => station?.station_id);
        const hydrantByMarkerId = buildLookupMap(hydrants, (hydrant) => hydrant?.marker_id);
        const schoolByMarkerId = buildLookupMap(schools, (school) => school?.marker_id);
        
        const markersToProcess = [];
        
        for (const item of coordinates) {
            if (item.marker && item.marker.lat && item.marker.lng) {
                if (!shouldDisplayMarker(item.marker)) {
                    continue;
                }
                const visibility = getIncidentVisibilityState(item.marker, incidents, incidentLookup);
                if (!visibility.visible) {
                    continue;
                }
                markersToProcess.push({ ...item.marker, incident_id: item.incident_id, incident_status: item.incident_status_id });
            } else if (item.lat && item.lng) {
                if (!shouldDisplayMarker(item)) {
                    continue;
                }
                if (isStationMarker(item)) {
                    const station = stationByMarkerId.get(String(item.marker_id));
                    if (station) item.station = station;
                } else if (isIncidentMarker(item)) {
                    const visibility = getIncidentVisibilityState(item, incidents, incidentLookup);
                    const incident = visibility.incident;
                    if (!visibility.visible) {
                        continue;
                    }
                    if (incident) {
                        item.incident_status = normalizeIncidentStatus(incident.incident_status || incident.status || 'reported');
                        item.incident_id = incident.incident_id;
                        item.incident_type_name = 'Fire';
                        item.reported_at = incident.created_at;
                    }
                } else if (isHydrantMarker(item)) {
                    const hydrant = hydrantByMarkerId.get(String(item.marker_id));
                    if (hydrant) {
                        item.hydrant = hydrant;
                        if (hydrant.station_id) {
                            const station = stationByStationId.get(String(hydrant.station_id));
                            if (station) item.hydrant.station = station;
                        }
                    }
                } else if (isSchoolMarker(item)) {
                    const school = schoolByMarkerId.get(String(item.marker_id));
                    if (school) item.school = school;
                }
                markersToProcess.push(item);
            }
        }
        
        clearAllMarkers();
        
        markersToProcess.forEach(marker => {
            if (!marker.lat || !marker.lng) return;
            const lat = parseFloat(marker.lat);
            const lng = parseFloat(marker.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            
            const incident = findIncidentInLookup(marker, incidentLookup);
            const tempIcon = getIconByCategoryId(marker.markerCategory_id, '', marker);
            const markerOptions = {
                icon: tempIcon,
                riseOnHover: true,
            };

            if (['risk', 'hazard'].includes(getMarkerCategoryKey(marker))) {
                markerOptions.zIndexOffset = 850;
            }

            const newMarker = L.marker([lat, lng], markerOptions);
            const popupContent = generatePopupContent({ marker: marker, incident }, incidents);
            const popupMaxWidth = window.innerWidth <= 768 ? 264 : 340;
            const popupOptions = { minWidth: 100, maxWidth: popupMaxWidth };
            if (shouldUseResponderPopupUi()) {
                popupOptions.className = 'responder-custom-popup';
            }

            newMarker.bindPopup(popupContent, popupOptions);
            
            if (isIncidentMarker(marker)) {
                const circle = L.circle([lat, lng], { radius: 200, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2 });
                incidentFeatureGroup.addLayer(circle);
            }
            
            addMarkerToFeatureGroup(marker, newMarker);
            newMarker.on('click', async () => {
                const resolvedIncident = findIncidentForMarker(marker, incident);
                const incidentId = resolvedIncident?.incident_id ?? resolvedIncident?.id ?? marker?.incident_id ?? null;
                const canFetchFullIncident = isIncidentMarker(marker) && Number.isFinite(Number(incidentId)) && Number(incidentId) > 0;
                const hasCachedIncident = Boolean(getCachedIncidentDetails(incidentId));
                const initialPopupContext = {
                    marker,
                    incident: resolvedIncident,
                    image: marker.multimedia || null,
                    created_at: marker.created_at || null,
                    isLoadingDetails: canFetchFullIncident && !hasCachedIncident,
                    loadingMessage: 'Loading latest incident details...'
                };
                const initialPopupContent = generatePopupContent(initialPopupContext, incidents);
                newMarker.setPopupContent(initialPopupContent);

                if (shouldUseResponderMobileDetailsUi()) {
                    try {
                        window.map?.closePopup?.();
                    } catch (closePopupError) {
                        console.warn('Unable to close existing popup before opening mobile details:', closePopupError);
                    }

                    openResponderMapPreviewSheet(initialPopupContent, initialPopupContext);
                } else if (!newMarker.isPopupOpen?.()) {
                    newMarker.openPopup();
                }

                addPopupEventListeners(initialPopupContext);

                if (!canFetchFullIncident || hasCachedIncident) {
                    return;
                }

                try {
                    const fullIncident = await fetchIncidentDetailsById(incidentId);
                    const hydratedIncident = fullIncident || resolvedIncident;
                    const hydratedPopupContext = {
                        marker,
                        incident: hydratedIncident,
                        image: marker.multimedia || null,
                        created_at: marker.created_at || null
                    };
                    const hydratedPopupContent = generatePopupContent(hydratedPopupContext, incidents);
                    newMarker.setPopupContent(hydratedPopupContent);
                    addPopupEventListeners(hydratedPopupContext);

                    if (shouldUseResponderMobileDetailsUi()) {
                        openResponderMapPreviewSheet(hydratedPopupContent, hydratedPopupContext);
                    }
                } catch (popupHydrationError) {
                    console.error('Unable to hydrate incident popup details:', popupHydrationError);
                    const errorPopupContext = {
                        marker,
                        incident: resolvedIncident,
                        image: marker.multimedia || null,
                        created_at: marker.created_at || null,
                        isLoadingDetails: true,
                        loadingMessage: 'Unable to refresh full incident details right now.'
                    };
                    const errorPopupContent = generatePopupContent(errorPopupContext, incidents);
                    newMarker.setPopupContent(errorPopupContent);
                    addPopupEventListeners(errorPopupContext);

                    if (shouldUseResponderMobileDetailsUi()) {
                        openResponderMapPreviewSheet(errorPopupContent, errorPopupContext);
                    }
                }
            });

            newMarker.on('popupopen', () => addPopupEventListeners({
                marker,
                incident: findIncidentForMarker(marker, incident),
                image: marker.multimedia || null,
                created_at: marker.created_at
            }));
            existingMarkers[marker.marker_id] = newMarker;
        });

        lastMarkerStateSignature = buildMarkerStateSignature(coordinates, incidents);
        
    } catch (error) {
        console.error('Error updating coordinates:', error);
        showNotification('Error updating markers: ' + error.message, 'error');
    }
}

async function fetchStations(options = {}) {
    const { preferSeeded = false, revalidate = true, force = false } = options;
    let usedSeededOrCachedData = false;

    try {
        const normalizeStationsPayload = (payload) => {
            const stationsData = payload?.data || payload || [];

            return stationsData.map(station => ({
                id: station.station_id || station.id,
                station_id: station.station_id || station.id,
                station_name: station.station_name,
                lat: station.marker?.lat || station.lat,
                lng: station.marker?.lng || station.lng,
                marker_id: station.marker_id,
                marker: station.marker
            }));
        };

        const seededStations = Array.isArray(window.stationsData)
            ? window.stationsData
            : (Array.isArray(window.stationDatas?.stations) ? window.stationDatas.stations : []);
        if (preferSeeded && Array.isArray(seededStations) && seededStations.length > 0) {
            stations = normalizeStationsPayload(seededStations);
            usedSeededOrCachedData = true;

            if (!revalidate) {
                return stations;
            }
        }

        const cachedStations = readStaticMapCache('stations');
        if ((!Array.isArray(seededStations) || seededStations.length === 0) && Array.isArray(cachedStations) && cachedStations.length > 0) {
            stations = normalizeStationsPayload(cachedStations);
            usedSeededOrCachedData = true;

            if (!revalidate) {
                return stations;
            }
        }

        if (!force && isChiefDashboardMapInstance() && Array.isArray(stations) && stations.length > 0) {
            return stations;
        }

        if (!force && shouldSkipStaticLayerRefetch(lastStationsFetchAt)) {
            return stations;
        }

        const response = await fetch('/api/station', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch stations');
        
        const data = await response.json();
        stations = normalizeStationsPayload(data);
        lastStationsFetchAt = Date.now();
        writeStaticMapCache('stations', data.data || data || []);
        
        console.log('Stations loaded:', stations.length);
        return stations;
    } catch (error) {
        console.error('Error fetching stations:', error);
        if (!usedSeededOrCachedData && !isChiefDashboardMapInstance()) {
            showNotification?.('Unable to refresh stations right now', 'warning');
        }
        if (!Array.isArray(stations)) {
            stations = [];
        }
        return stations;
    }
}

function generatePopupContent(coord, incidents) {
    const marker = coord.marker || coord;
    const incident = coord.incident
        || incidents.find(inc => inc.marker_id === marker.marker_id);
    const loadingMarkup = coord?.isLoadingDetails && isIncidentMarker(marker)
        ? buildIncidentPopupLoadingMarkup(coord.loadingMessage || 'Loading latest incident details...')
        : '';
    const popupImageSource = getMarkerPopupImagePath(marker, coord);
    const imagePath = normalizePopupImageUrl(popupImageSource);
    const sidebarGradient = 'linear-gradient(135deg, #c63d26 0%, #d18a16 100%)';
    const sidebarSoftSurface = 'linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%)';
    const isMobilePopup = window.innerWidth <= 768;
    const popupMinWidth = isMobilePopup ? 216 : 280;
    const popupMaxWidth = isMobilePopup ? 248 : 320;
    const incidentMinWidth = isMobilePopup ? 228 : 300;
    const incidentMaxWidth = isMobilePopup ? 264 : 340;
    const headerPadding = isMobilePopup ? '10px 12px' : '12px 16px';
    const headerMargin = isMobilePopup ? '-10px -10px 0 -10px' : '-12px -12px 0 -12px';
    const bodyPadding = isMobilePopup ? '10px 0' : '12px 0';
    const sectionPadding = isMobilePopup ? '10px' : '12px';
    const buttonPadding = isMobilePopup ? '7px' : '8px';
    const primaryButtonPadding = isMobilePopup ? '8px' : '10px';
    const buttonFontSize = isMobilePopup ? '12px' : '13px';
    const primaryButtonFontSize = isMobilePopup ? '13px' : '14px';
    const titleFontSize = isMobilePopup ? '14px' : '16px';
    const bodyFontSize = isMobilePopup ? '11px' : '12px';
    const metaFontSize = isMobilePopup ? '10px' : '11px';
    const imageHeight = isMobilePopup ? '48px' : '60px';
    const actionGap = isMobilePopup ? '6px' : '8px';

    if (shouldUseResponderPopupUi()) {
        return buildResponderPopupContent(marker, incident, imagePath, {
            isLoadingDetails: coord?.isLoadingDetails,
            loadingMessage: coord?.loadingMessage
        });
    }

    // Special design for fire stations (category_id = 1)
    if (isStationMarker(marker)) {
        return `
            <div style="min-width: ${popupMinWidth}px; max-width: ${popupMaxWidth}px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: ${sidebarGradient}; color: white; padding: ${headerPadding}; border-radius: 12px 12px 0 0; margin: ${headerMargin};">
                    <div style="display: flex; align-items: center; gap: ${actionGap};">
                        <i class="fas fa-building" style="font-size: ${isMobilePopup ? '20px' : '24px'};"></i>
                        <div>
                            <strong style="font-size: ${titleFontSize};">${marker.station?.station_name || marker.title || 'Fire Station'}</strong>
                            <div style="font-size: ${metaFontSize}; opacity: 0.9;">Emergency Response Facility</div>
                        </div>
                    </div>
                </div>
                <div style="padding: ${bodyPadding};">
                    <div style="background: ${sidebarSoftSurface}; border-radius: 10px; padding: ${sectionPadding}; margin-bottom: 10px; border-left: 3px solid #c63d26;">
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-bottom: 8px;">
                            <i class="fas fa-building" style="color: #c63d26; width: 20px;"></i>
                            <span style="font-size: ${buttonFontSize};">${marker.station?.station_name || marker.title || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-bottom: 8px;">
                            <i class="fas fa-phone" style="color: #c63d26; width: 20px;"></i>
                            <span style="font-size: ${buttonFontSize};">${marker.station?.contact_number || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: ${actionGap};">
                            <i class="fas fa-map-marker-alt" style="color: #c63d26; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:${actionGap}; margin-top:8px;">
                            <i class="fas fa-location-crosshairs" style="color: #c63d26; width: 20px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">${Number(marker.lat).toFixed(6)}, ${Number(marker.lng).toFixed(6)}</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToStation(${marker.marker_id})" style="width: 100%; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer; margin-bottom: 8px;">
                    <i class="fa-solid fa-route"></i> Get Directions
                </button>
                <div style="display: flex; gap: ${actionGap}; margin-bottom: 8px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 10px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: ${imageHeight}; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Beautiful incident display (category_id = 2 or 3)
    if (isIncidentMarker(marker)) {
        const incidentStatus = normalizeIncidentStatus(marker.incident_status || marker.status || 'reported');
        const incidentTypeName = 'Fire';
        const reportedAt = marker.reported_at ? new Date(marker.reported_at).toLocaleString() : new Date(marker.created_at).toLocaleString();
        const reportedAtMobile = marker.reported_at
            ? new Date(marker.reported_at).toLocaleString([], { month: 'numeric', day: 'numeric', year: '2-digit', hour: 'numeric', minute: '2-digit' })
            : new Date(marker.created_at).toLocaleString([], { month: 'numeric', day: 'numeric', year: '2-digit', hour: 'numeric', minute: '2-digit' });
        const reportedAtDisplay = isMobilePopup ? reportedAtMobile : reportedAt;
        const incidentPresentation = getIncidentTypePresentation(incidentTypeName);
        const typeIcon = incidentPresentation.iconClass;
        const typeColor = incidentPresentation.bgColor;
        const typeBgGradient = sidebarGradient;
        
        let statusBadge = '';
        let statusColor = '';
        
        switch(incidentStatus.toLowerCase()) {
            case 'reported': statusColor = '#ef4444'; statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">REPORTED</span>'; break;
            case 'en-route': statusColor = '#f59e0b'; statusBadge = '<span style="background: #f59e0b; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">EN ROUTE</span>'; break;
            case 'on-scene': statusColor = '#8b5cf6'; statusBadge = '<span style="background: #8b5cf6; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">ON SCENE</span>'; break;
            case 'controlled': statusColor = '#10b981'; statusBadge = '<span style="background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">CONTROLLED</span>'; break;
            case 'closed': statusColor = '#6b7280'; statusBadge = '<span style="background: #6b7280; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">CLOSED</span>'; break;
            default: statusColor = '#ef4444'; statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">REPORTED</span>';
        }
        
        return `
            <div style="min-width: ${incidentMinWidth}px; max-width: ${incidentMaxWidth}px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: ${typeBgGradient}; color: white; padding: ${isMobilePopup ? '12px' : '14px 16px'}; border-radius: 12px 12px 0 0; margin: ${headerMargin};">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: ${isMobilePopup ? '10px' : '12px'}; min-width: 0;">
                            <span style="width: ${isMobilePopup ? '34px' : '38px'}; height: ${isMobilePopup ? '34px' : '38px'}; border-radius: 999px; background: rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; border: 1px solid rgba(255,255,255,0.25); flex-shrink: 0;">
                                <i class="fas ${typeIcon}" style="font-size: ${isMobilePopup ? '14px' : '16px'};"></i>
                            </span>
                            <div style="min-width: 0;">
                                <strong style="font-size: ${titleFontSize}; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${marker.title || 'Emergency Incident'}</strong>
                                <div style="font-size: ${metaFontSize}; opacity: 0.9; margin-top: 2px;">${incidentTypeName}</div>
                            </div>
                        </div>
                        ${statusBadge}
                    </div>
                </div>
                
                <div style="padding: ${bodyPadding};">
                    <div style="background: ${sidebarSoftSurface}; border-radius: 10px; padding: ${sectionPadding}; margin-bottom: 10px; border-left: 3px solid ${typeColor};">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: ${isMobilePopup ? '8px' : '10px'}; margin-bottom: 8px;">
                            <div>
                                <div style="font-size: ${metaFontSize}; color: #6b7280; margin-bottom: 2px;">STATUS</div>
                                <div style="font-size: ${buttonFontSize}; font-weight: 600; color: ${statusColor};">${incidentStatus.toUpperCase()}</div>
                            </div>
                            <div>
                                <div style="font-size: ${metaFontSize}; color: #6b7280; margin-bottom: 2px;">REPORTED</div>
                                <div style="font-size: ${bodyFontSize};">${reportedAtDisplay}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: ${actionGap}; margin-top: 8px; padding-top: 8px; border-top: 1px solid #fee2e2;">
                            <i class="fas fa-map-marker-alt" style="color: ${typeColor}; font-size: 12px; margin-top: 2px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563; line-height: 1.4;">${marker.description || 'No description available'}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-top: 8px;">
                            <i class="fas fa-location-crosshairs" style="color: ${typeColor}; font-size: 12px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">${Number(marker.lat).toFixed(6)}, ${Number(marker.lng).toFixed(6)}</span>
                        </div>
                        ${incident?.incident_id ? `
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-top: 8px;">
                            <i class="fas fa-hashtag" style="color: ${typeColor}; font-size: 12px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">Incident #${incident.incident_id}</span>
                        </div>
                        ` : ''}
                    </div>
                    ${loadingMarkup}
                </div>
                
                ${incidentStatus === 'reported' && canManageMarkers ? `
                <button id="respond${marker.marker_id}" 
                    style="width: 100%; background: #dc2626; color: white; border: none; padding: ${primaryButtonPadding}; border-radius: 10px; font-size: ${primaryButtonFontSize}; font-weight: 600; cursor: pointer; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: ${actionGap}; transition: all 0.2s;">
                    <i class="fas fa-phone-alt"></i> RESPOND NOW
                </button>
                ` : ''}
                ${canManageMarkers ? `
                <div style="display: flex; gap: ${actionGap}; margin-bottom: 8px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                ` : ''}

                <div style="display:flex; gap:${actionGap}; margin-bottom: 8px;">
                    <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToIncident(${incident?.incident_id ?? marker?.incident_id ?? 'null'}, ${marker.marker_id})" style="flex:1; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fa-solid fa-route"></i> Get Directions
                    </button>
                    <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToIncident(${incident?.incident_id ?? marker?.incident_id ?? 'null'}, ${marker.marker_id}, { source: 'demo' })" style="flex:1; background: #fff7ed; color: #9a3412; border: 1px dashed rgba(198, 61, 38, 0.35); padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 700; cursor: pointer;">
                        <i class="fa-solid fa-flask"></i> Demo
                    </button>
                </div>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 10px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: ${imageHeight}; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for fire hydrants (category_id = 4)
    if (isHydrantMarker(marker) && marker.hydrant) {
        return `
            <div style="min-width: ${popupMinWidth}px; max-width: ${popupMaxWidth}px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: ${headerPadding}; border-radius: 12px 12px 0 0; margin: ${headerMargin};">
                    <div style="display: flex; align-items: center; gap: ${actionGap};">
                        <i class="fas fa-fire-extinguisher" style="font-size: ${isMobilePopup ? '20px' : '24px'};"></i>
                        <div>
                            <strong style="font-size: ${titleFontSize};">${marker.title || 'Fire Hydrant'}</strong>
                            <div style="font-size: ${metaFontSize}; opacity: 0.9;">Water Supply Point</div>
                        </div>
                    </div>
                </div>
                <div style="padding: ${bodyPadding};">
                    <div style="background: #f8fafc; border-radius: 8px; padding: ${isMobilePopup ? '9px' : '10px'}; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-bottom: 8px;">
                            <i class="fas fa-barcode" style="color: #dc2626; width: 20px;"></i>
                            <span style="font-size: ${buttonFontSize};">${marker.hydrant.hydrant_code || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: ${actionGap}; margin-bottom: 8px;">
                            <i class="fas fa-building" style="color: #dc2626; width: 20px;"></i>
                            <span style="font-size: ${buttonFontSize};">${marker.hydrant.station?.station_name || 'Not Assigned'}</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: ${actionGap};">
                            <i class="fas fa-map-marker-alt" style="color: #dc2626; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                    </div>
                </div>
                ${canManageMarkers ? `
                ${canManageMarkers ? `
                ${canManageMarkers ? `
                <div style="display: flex; gap: ${actionGap}; margin-bottom: 8px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                ` : ''}
                ` : ''}
                ` : ''}
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 10px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: ${imageHeight}; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for schools (category_id = 5)
    if (isSchoolMarker(marker) && marker.school) {
        return `
            <div style="min-width: ${popupMinWidth}px; max-width: ${popupMaxWidth}px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); color: white; padding: ${headerPadding}; border-radius: 12px 12px 0 0; margin: ${headerMargin};">
                    <div style="display: flex; align-items: center; gap: ${actionGap};">
                        <i class="fas fa-school" style="font-size: ${isMobilePopup ? '20px' : '24px'};"></i>
                        <div>
                            <strong style="font-size: ${titleFontSize};">${marker.school.school_name || marker.title || 'School'}</strong>
                            <div style="font-size: ${metaFontSize}; opacity: 0.9;">Educational Institution</div>
                        </div>
                    </div>
                </div>
                <div style="padding: ${bodyPadding};">
                    <div style="background: #f8fafc; border-radius: 8px; padding: ${isMobilePopup ? '9px' : '10px'};">
                        <div style="display: flex; align-items: flex-start; gap: ${actionGap};">
                            <i class="fas fa-align-left" style="color: #eab308; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: ${bodyFontSize}; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                    </div>
                </div>
                ${canManageMarkers ? `
                ${canManageMarkers ? `
                <div style="display: flex; gap: ${actionGap}; margin-bottom: 8px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                ` : ''}
                ` : ''}
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 10px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: ${imageHeight}; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Default popup for other marker types
    return `
        <div class="flex flex-col gap-2 w-48">
            <strong class="text-sm font-bold">${marker.title || 'Marker ' + marker.marker_id}</strong>
            <p class="text-xs text-gray-600">${marker.description || 'No description'}</p>
            <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
                ${imagePath ? `<img src="${imagePath}" class="object-cover w-full h-full rounded" />` : `<span class="text-gray-400 text-xs">No image</span>`}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
            ${getActionButtons({ marker: marker }, incidents)}
            <button id="viewDetails${marker.marker_id}" class="bg-blue-600 text-white text-xs px-2 py-1.5 rounded hover:bg-blue-700 flex items-center justify-center gap-1 w-full mt-1">
                <i class="fas fa-info-circle"></i> View Full Details
            </button>
        </div>
    `;
}

function getResponderPopupPresentation(marker) {
    if (isIncidentMarker(marker)) {
        return {
            label: 'Fire Incident',
            icon: 'fa-fire',
            color: '#dc2626',
            accentClass: 'incident'
        };
    }

    if (isStationMarker(marker)) {
        return {
            label: 'Fire Station',
            icon: 'fa-building',
            color: '#2563eb',
            accentClass: 'station'
        };
    }

    if (isHydrantMarker(marker)) {
        return {
            label: 'Fire Hydrant',
            icon: 'fa-fire-extinguisher',
            color: '#0891b2',
            accentClass: 'hydrant'
        };
    }

    if (isSchoolMarker(marker)) {
        return {
            label: 'School',
            icon: 'fa-school',
            color: '#ca8a04',
            accentClass: 'school'
        };
    }

    if (getMarkerCategoryKey(marker) === 'barangay') {
        return {
            label: 'Barangay Resource',
            icon: 'fa-building-flag',
            color: '#0f766e',
            accentClass: 'barangay'
        };
    }

    if (getMarkerCategoryKey(marker) === 'risk') {
        return {
            label: 'Risk Report',
            icon: 'fa-shield-heart',
            color: '#c2410c',
            accentClass: 'default'
        };
    }

    if (getMarkerCategoryKey(marker) === 'hazard') {
        return {
            label: 'Hazard Report',
            icon: 'fa-triangle-exclamation',
            color: '#c2410c',
            accentClass: 'default'
        };
    }

    return {
        label: getCategoryName(marker),
        icon: 'fa-location-dot',
        color: '#c63d26',
        accentClass: 'default'
    };
}

function getResponderPopupStatusMeta(statusValue) {
    const statusKey = normalizeIncidentStatus(statusValue || 'reported');
    switch (statusKey) {
        case 'en-route':
            return { className: 'responding', label: 'En Route', icon: 'fa-truck-medical' };
        case 'on-scene':
            return { className: 'resolved', label: 'On Scene', icon: 'fa-crosshairs' };
        case 'controlled':
            return { className: 'resolved', label: 'Controlled', icon: 'fa-circle-check' };
        case 'closed':
            return { className: 'closed', label: 'Closed', icon: 'fa-folder-closed' };
        case 'reported':
        default:
            return { className: 'reported', label: 'Reported', icon: 'fa-bell' };
    }
}

function getBlockingIncidentRouteForCurrentTeam(targetIncidentId = null) {
    const currentTeamId = Number(window.userTeamId || 0);
    const resolvedIncidentId = targetIncidentId !== null && targetIncidentId !== undefined
        ? Number(targetIncidentId)
        : null;

    if (!currentTeamId || !Number.isFinite(currentTeamId)) {
        return null;
    }

    return Array.from(activeTrucks.values()).find((truck) => {
        const truckTeamId = Number(truck?.routeTeamId || 0);
        const truckIncidentId = truck?.incidentId !== null && truck?.incidentId !== undefined
            ? Number(truck.incidentId)
            : null;

        return truckTeamId === currentTeamId
            && truck?.isSimulating
            && truckIncidentId !== null
            && truckIncidentId !== resolvedIncidentId;
    }) || null;
}

function getBlockingIncidentRouteLabel(route) {
    if (!route) {
        return 'another ongoing incident';
    }

    const destinationTitle = route?.routeDestination?.title;
    if (typeof destinationTitle === 'string' && destinationTitle.trim() !== '') {
        return destinationTitle.trim();
    }

    const incidentId = route?.incidentId;
    if (incidentId !== null && incidentId !== undefined) {
        const incidentLookup = window.incidentsLookup instanceof Map ? window.incidentsLookup : null;
        const incidentRecord = incidentLookup?.get(`incident:${incidentId}`) || null;
        const incidentTitle = incidentRecord?.title || incidentRecord?.marker?.title || incidentRecord?.marker_title || null;
        if (typeof incidentTitle === 'string' && incidentTitle.trim() !== '') {
            return incidentTitle.trim();
        }

        return `Incident #${incidentId}`;
    }

    return 'another ongoing incident';
}

function buildIncidentPopupLoadingMarkup(message = 'Loading latest incident details...') {
    return `
        <div style="margin-top: 10px; display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 10px; background: rgba(255, 247, 237, 0.95); border: 1px solid rgba(217, 119, 6, 0.18); color: #9a3412; font-size: 12px; font-weight: 600;">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            <span>${escapeHtml(message)}</span>
        </div>
    `;
}

function buildIncidentCommandActionMarkup(incident, marker = null) {
    if (!isDispatchCommandMapContext()) return '';
    const incidentId = Number(incident?.incident_id ?? incident?.id ?? marker?.incident_id ?? 0);
    if (!Number.isFinite(incidentId) || incidentId <= 0) return '';
    const status = normalizeIncidentStatus(incident?.status || marker?.incident_status || 'reported');
    if (!['reported', 'responding', 'on-scene', 'on scene'].includes(status)) return '';
    const needsBackup = ['requesting_other_station', 'waiting_for_team', 'waiting_for_apparatus'].includes(String(incident?.dispatch_state || ''));
    const label = status === 'reported' ? 'Manage Dispatch' : 'Call Nearest Station';

    return `
        ${needsBackup ? `
            <div class="responder-marker-popup-alert command-needed">
                <i class="fas fa-tower-broadcast"></i>
                No capable local response is available. Command escalation is required.
            </div>
        ` : ''}
        <button type="button" class="responder-marker-popup-btn command" onclick="window.openIncidentCommandPanel(${incidentId})">
            <i class="fas fa-headset"></i>
            ${label}
        </button>
    `;
}

function ensureIncidentCommandPanel() {
    let modal = document.getElementById('incidentCommandDispatchModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'incidentCommandDispatchModal';
    modal.className = 'incident-command-modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="incident-command-modal__backdrop" data-command-close></div>
        <section class="incident-command-modal__card" role="dialog" aria-modal="true" aria-labelledby="incidentCommandTitle">
            <header class="incident-command-modal__header">
                <div><span>Command dispatch</span><h2 id="incidentCommandTitle">Incident response control</h2></div>
                <button type="button" data-command-close aria-label="Close command dispatch"><i class="fas fa-xmark"></i></button>
            </header>
            <div class="incident-command-modal__body" id="incidentCommandBody"></div>
        </section>
    `;
    document.body.appendChild(modal);
    modal.querySelectorAll('[data-command-close]').forEach((button) => button.addEventListener('click', closeIncidentCommandPanel));
    return modal;
}

function closeIncidentCommandPanel() {
    const modal = document.getElementById('incidentCommandDispatchModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('incident-command-open');
}

function commandResponseError(payload, fallback) {
    return Object.values(payload?.errors || {}).flat().find(Boolean) || payload?.message || fallback;
}

async function submitIncidentCommandAction(incidentId, endpoint, body, button) {
    const previous = button?.innerHTML;
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
    try {
        const response = await fetch(`/command/incidents/${incidentId}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(body)
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(commandResponseError(payload, 'Dispatch command failed.'));

        incidentDetailsCache.delete(Number(incidentId));
        closeIncidentCommandPanel();
        showToast(payload.message || 'Dispatch command completed.', 'success');
        await refreshIncidentMapImmediately({ forceIncidents: true });
    } catch (error) {
        showToast(error.message || 'Dispatch command failed.', 'error');
        if (button?.isConnected) {
            button.disabled = false;
            button.innerHTML = previous;
        }
    }
}

async function openIncidentCommandPanel(incidentId) {
    if (!isDispatchCommandMapContext()) return;
    const id = Number(incidentId);
    if (!Number.isFinite(id) || id <= 0) return;

    const modal = ensureIncidentCommandPanel();
    const body = document.getElementById('incidentCommandBody');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('incident-command-open');
    body.innerHTML = '<div class="incident-command-modal__loading"><i class="fas fa-spinner fa-spin"></i><span>Checking teams, personnel, apparatus, and nearby stations...</span></div>';

    try {
        const response = await fetch(`/command/incidents/${id}/dispatch-options`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            cache: 'no-store'
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(commandResponseError(payload, 'Unable to load dispatch options.'));

        const data = payload.data || {};
        const status = normalizeIncidentStatus(data.status || 'reported');
        const isReported = status === 'reported';
        const localTeams = Array.isArray(data.local_teams) ? data.local_teams : [];
        const externalStations = Array.isArray(data.external_stations) ? data.external_stations : [];
        const externalAllowed = !isReported || Boolean(data.needs_external_response);
        const externalRows = externalStations.map((station, index) => `
            <li class="${index === 0 ? 'nearest' : ''}">
                <span><strong>${escapeHtml(station.station_name || `Station #${station.station_id}`)}</strong><small>${Math.max(0, Math.round(Number(station.distance_meters || 0) / 100) / 10)} km from incident</small></span>
                <span><b>${Number(station.available_team_count || 0)}</b> team · <b>${Number(station.available_firetruck_count || 0)}</b> truck</span>
            </li>
        `).join('');

        body.innerHTML = `
            <div class="incident-command-summary ${data.needs_external_response ? 'needs-backup' : ''}">
                <span class="incident-command-summary__icon"><i class="fas ${data.needs_external_response ? 'fa-triangle-exclamation' : 'fa-shield-halved'}"></i></span>
                <div><strong>Incident #${id} · ${escapeHtml(String(data.status || 'Reported'))}</strong><p>${escapeHtml(data.dispatch_state_label || 'Dispatch status is being evaluated.')}</p></div>
            </div>
            <div class="incident-command-grid">
                <section class="incident-command-section">
                    <div class="incident-command-section__heading"><span>Local reassignment</span><strong>${escapeHtml(data.station_name || 'Incident station')}</strong></div>
                    ${isReported ? (localTeams.length ? `
                        <label>Available response team
                            <select id="incidentCommandTeam">${localTeams.map(team => `<option value="${team.team_id}" ${Number(team.team_id) === Number(data.assigned_team_id) ? 'selected' : ''}>${escapeHtml(team.label)}</option>`).join('')}</select>
                        </label>
                    ` : '<div class="incident-command-empty"><i class="fas fa-users-slash"></i><span>No local team currently has both deployable personnel and an available firetruck.</span></div>')
                    : '<div class="incident-command-empty"><i class="fas fa-circle-info"></i><span>An active incident cannot switch its primary team. Dispatch another station as backup instead.</span></div>'}
                </section>
                <section class="incident-command-section">
                    <div class="incident-command-section__heading"><span>Nearest capable stations</span><strong>Measured from incident</strong></div>
                    ${externalRows ? `<ol class="incident-command-stations">${externalRows}</ol>` : '<div class="incident-command-empty danger"><i class="fas fa-tower-broadcast"></i><span>No other capable station is available right now.</span></div>'}
                </section>
            </div>
            <label class="incident-command-reason">Command reason
                <textarea id="incidentCommandReason" maxlength="255" placeholder="Example: Local team already responding to another fire" required></textarea>
            </label>
            <footer class="incident-command-actions">
                ${isReported && localTeams.length ? '<button type="button" class="local" id="incidentCommandReassign"><i class="fas fa-people-arrows"></i> Reassign Local Team</button>' : ''}
                ${externalAllowed && externalStations.length ? `<button type="button" class="external" id="incidentCommandExternal"><i class="fas fa-tower-broadcast"></i> ${isReported ? 'Call Nearest Station' : 'Dispatch Nearest Backup'}</button>` : ''}
            </footer>
        `;

        const reason = () => String(document.getElementById('incidentCommandReason')?.value || '').trim();
        document.getElementById('incidentCommandReassign')?.addEventListener('click', (event) => {
            if (!reason()) return showToast('Enter the reason for manual reassignment.', 'warning');
            const teamId = Number(document.getElementById('incidentCommandTeam')?.value || 0);
            submitIncidentCommandAction(id, 'reassign', { team_id: teamId, reason: reason() }, event.currentTarget);
        });
        document.getElementById('incidentCommandExternal')?.addEventListener('click', (event) => {
            if (!reason()) return showToast('Enter the reason for calling another station.', 'warning');
            if (!window.confirm('Dispatch the nearest capable station measured from this incident location?')) return;
            submitIncidentCommandAction(id, 'external-response', { reason: reason() }, event.currentTarget);
        });
    } catch (error) {
        body.innerHTML = `<div class="incident-command-modal__error"><i class="fas fa-circle-exclamation"></i><strong>Dispatch controls unavailable</strong><span>${escapeHtml(error.message || 'Please try again.')}</span></div>`;
    }
}

window.openIncidentCommandPanel = openIncidentCommandPanel;
window.closeIncidentCommandPanel = closeIncidentCommandPanel;

function buildResponderPopupContent(marker, incident, imagePath = null, options = {}) {
    const markerPresentation = getResponderPopupPresentation(marker);
    const statusMeta = getResponderPopupStatusMeta(marker.incident_status || incident?.status);
    const targetIncidentId = incident?.incident_id ?? marker?.incident_id ?? null;
    const loadingMarkup = options.isLoadingDetails && isIncidentMarker(marker)
        ? buildIncidentPopupLoadingMarkup(options.loadingMessage || 'Loading latest incident details...')
        : '';
    const blockingRoute = isIncidentMarker(marker)
        ? getBlockingIncidentRouteForCurrentTeam(targetIncidentId)
        : null;
    const isIncidentRoutingBlocked = Boolean(blockingRoute);
    const formatCoordinates = () => {
        const lat = Number(marker.lat);
        const lng = Number(marker.lng);

        return Number.isFinite(lat) && Number.isFinite(lng)
            ? `${lat.toFixed(5)}, ${lng.toFixed(5)}`
            : '';
    };
    const formatDateTime = (value) => {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleString([], {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    };
    const compactValue = (...values) => values
        .map((value) => (value == null ? '' : String(value).trim()))
        .find((value) => value && !['N/A', 'Unavailable', 'Not available', 'Unknown'].includes(value)) || '';
    const riskAreaPublic = marker?.risk_area_public || {};
    const locationValue = formatCoordinates();
    const reportedAt = formatDateTime(
        incident?.reported_at
        || incident?.created_at
        || marker.reported_at
        || marker.created_at
    );
    const reporterName = compactValue(
        incident?.reporter?.name,
        incident?.reported_by?.name,
        incident?.user?.name,
        incident?.reporter_name,
        marker.reported_by,
        marker.reporter_name
    );
    const assignedTeam = compactValue(
        incident?.assigned_team?.name,
        incident?.team?.name,
        incident?.assignedTeam?.name,
        incident?.station?.name,
        marker.assigned_team,
        marker.station?.name
    );
    const riskReviewStatus = compactValue(riskAreaPublic.review_status, marker.review_status);
    const riskClassification = compactValue(
        riskAreaPublic.condition_label,
        riskAreaPublic.hazard_category,
        riskAreaPublic.risk_type,
        marker.condition_label,
        marker.hazard_category,
        marker.risk_type
    );
    const riskBarangay = compactValue(riskAreaPublic.barangay_name, marker.barangay_name);
    const riskCitizenNote = compactValue(
        riskAreaPublic.remarks,
        marker.remarks,
        marker.description
    );
    const detailRows = isIncidentMarker(marker)
        ? [
            { label: 'Reported', value: reportedAt },
            { label: 'Reported by', value: reporterName },
            { label: 'Assigned team', value: assignedTeam },
            { label: 'Location', value: locationValue }
        ]
        : ['risk', 'hazard'].includes(getMarkerCategoryKey(marker))
            ? [
                { label: 'Review status', value: riskReviewStatus },
                { label: 'Classification', value: riskClassification },
                { label: 'Barangay', value: riskBarangay },
                { label: 'Citizen note', value: riskCitizenNote },
                { label: 'Location', value: locationValue }
            ]
        : isStationMarker(marker)
            ? [
                { label: 'Hotline', value: compactValue(marker.station?.contact_number, marker.contact_number) },
                { label: 'Station', value: compactValue(marker.station?.station_code, marker.station_code, marker.station?.name) },
                { label: 'Location', value: locationValue }
            ]
            : isHydrantMarker(marker)
                ? [
                    { label: 'Hydrant', value: compactValue(marker.hydrant?.hydrant_code, marker.hydrant_code, marker.title) },
                    { label: 'Station', value: compactValue(marker.hydrant?.station?.name, marker.station?.name) },
                    { label: 'Location', value: locationValue }
                ]
                : [
                    { label: 'Contact', value: compactValue(marker.contact_number, marker.phone) },
                    { label: 'Location', value: locationValue }
                ];
    const visibleDetailRows = detailRows.filter((row) => compactValue(row.value));

    const managementMarkup = canManageMarkers
        ? `
            <div class="responder-map-detail-actions split">
                <button id="edit${marker.marker_id}" class="responder-marker-popup-btn secondary">
                    <i class="fas fa-pen-to-square"></i>
                    Edit
                </button>
                <button id="delete${marker.marker_id}" class="responder-marker-popup-btn danger">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        `
        : '';

    let actionMarkup = '';

    if (isIncidentMarker(marker)) {
        actionMarkup += buildIncidentCommandActionMarkup(incident, marker);

        if (!isDispatchCommandMapContext() && canManageMarkers && normalizeIncidentStatus(marker.incident_status || incident?.status || 'reported') === 'reported') {
            actionMarkup += `
                <button id="respond${marker.marker_id}" class="responder-marker-popup-btn danger${isIncidentRoutingBlocked ? ' disabled' : ''}" ${isIncidentRoutingBlocked ? 'type="button" disabled title="Your team is already navigating to another incident."' : ''}>
                    <i class="fas fa-phone-volume"></i>
                    ${isIncidentRoutingBlocked ? 'Team Busy' : 'Respond Now'}
                </button>
            `;
        }

        if (isIncidentRoutingBlocked) {
            actionMarkup += `
                <div class="responder-marker-popup-alert">
                    <i class="fas fa-circle-exclamation"></i>
                    Team already assigned to ${escapeHtml(getBlockingIncidentRouteLabel(blockingRoute))}
                </div>
            `;
        }

        actionMarkup += `
            <button
                type="button"
                class="responder-marker-popup-btn secondary${isIncidentRoutingBlocked ? ' disabled' : ''}"
                ${isIncidentRoutingBlocked
                    ? 'disabled title="Finish or clear the active team route before navigating to another incident."'
                    : `onclick="window.calculateRouteToIncident(${targetIncidentId ?? 'null'}, ${marker.marker_id})"`}
            >
                <i class="fas fa-route"></i>
                ${isIncidentRoutingBlocked ? 'Directions Locked' : 'Get Directions'}
            </button>
        `;

        actionMarkup += `
            <button
                type="button"
                class="responder-marker-popup-btn demo${isIncidentRoutingBlocked ? ' disabled' : ''}"
                ${isIncidentRoutingBlocked
                    ? 'disabled title="Finish or clear the active team route before navigating to another incident."'
                    : `onclick="window.calculateRouteToIncident(${targetIncidentId ?? 'null'}, ${marker.marker_id}, { source: 'demo' })"`}
            >
                <i class="fas fa-flask"></i>
                Demo Route
                <span class="responder-marker-popup-badge">DEMO</span>
            </button>
        `;

        actionMarkup += managementMarkup;
    } else if (isStationMarker(marker)) {
        actionMarkup += `
            <button type="button" class="responder-marker-popup-btn secondary" onclick="window.calculateRouteToStation(${marker.marker_id})">
                <i class="fas fa-route"></i>
                Get Directions
            </button>
        `;

        actionMarkup += managementMarkup;
    } else if (['risk', 'hazard'].includes(getMarkerCategoryKey(marker))) {
        actionMarkup += `
            <button
                type="button"
                class="responder-marker-popup-btn secondary"
                onclick="window.calculateRouteToStation(${marker.marker_id}, ${JSON.stringify(marker.title || markerPresentation.label)}, ${Number(marker.lat)}, ${Number(marker.lng)})"
            >
                <i class="fas fa-route"></i>
                Get Directions
            </button>
        `;
    } else {
        actionMarkup += managementMarkup;
    }

    const actionSection = actionMarkup.trim()
        ? `
            <div class="responder-marker-popup-actions">
                ${actionMarkup}
            </div>
        `
        : '';

    return `
        <div class="responder-marker-popup ${markerPresentation.accentClass}">
            <div class="responder-marker-popup-topbar"></div>
            <div class="responder-marker-popup-header">
                <div class="responder-marker-popup-icon" style="--popup-accent:${markerPresentation.color};">
                    <i class="fas ${markerPresentation.icon}"></i>
                </div>
                <div class="responder-marker-popup-heading">
                    <div class="responder-marker-popup-chip-row">
                        <span class="responder-marker-popup-chip">${escapeHtml(markerPresentation.label)}</span>
                        ${isIncidentMarker(marker) ? `
                            <span class="responder-marker-popup-chip status ${statusMeta.className}">
                                <i class="fas ${statusMeta.icon}"></i>
                                ${escapeHtml(statusMeta.label)}
                            </span>
                        ` : ''}
                    </div>
                    <strong class="responder-marker-popup-title">${escapeHtml(marker.title || `Marker ${marker.marker_id || ''}`)}</strong>
                    <p class="responder-marker-popup-subtitle">${escapeHtml(marker.description || 'No description provided yet.')}</p>
                </div>
            </div>
            ${imagePath ? `
                <div class="responder-marker-popup-media">
                    <img src="${imagePath}" alt="${escapeHtml(marker.title || 'Marker image')}" class="responder-marker-popup-image" />
                </div>
            ` : `
                <div class="responder-marker-popup-media placeholder">
                    <div class="responder-marker-popup-placeholder-icon">
                        <i class="fas ${markerPresentation.icon}"></i>
                    </div>
                    <span>No photo attached</span>
                </div>
            `}
            <div class="responder-marker-popup-details">
                ${visibleDetailRows.map((row) => `
                    <div class="responder-marker-popup-detail-row">
                        <span class="responder-marker-popup-detail-label">${escapeHtml(row.label)}</span>
                        <span class="responder-marker-popup-detail-value">${escapeHtml(row.value)}</span>
                    </div>
                `).join('')}
            </div>
            ${loadingMarkup}
            ${actionSection}
            <input type="hidden" value="${escapeHtml(String(marker.marker_id || ''))}" id="mid">
        </div>
    `;
}

function findIncidentForMarker(marker, fallbackIncident = null) {
    const incidentId = marker?.incident_id ?? fallbackIncident?.incident_id ?? fallbackIncident?.id ?? null;
    const cachedIncident = getCachedIncidentDetails(incidentId);
    if (cachedIncident) {
        return cachedIncident;
    }

    if (fallbackIncident) {
        return fallbackIncident;
    }

    if (!Array.isArray(window.incidentsData)) {
        return null;
    }

    return findIncidentInLookup(
        marker,
        window.incidentsLookup instanceof Map ? window.incidentsLookup : buildIncidentLookup(window.incidentsData)
    );
}

function ensureResponderMapDetailsModal() {
    let modal = document.getElementById('responderMapDetailsModal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'responderMapDetailsModal';
    modal.className = 'responder-map-modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="responder-map-modal-card" role="dialog" aria-modal="false" aria-labelledby="responderMapDetailsModalTitle">
            <div class="responder-map-modal-header">
                <div>
                    <p class="responder-map-modal-kicker">Map Details</p>
                    <h2 id="responderMapDetailsModalTitle" class="responder-map-modal-title">Details</h2>
                </div>
                <button type="button" id="responderMapDetailsModalClose" class="responder-map-modal-close" aria-label="Close details modal">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="responderMapDetailsModalBody" class="responder-map-modal-body"></div>
        </div>
    `;

    document.body.appendChild(modal);
    document.getElementById('responderMapDetailsModalClose')?.addEventListener('click', closeResponderMapDetailsModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeResponderMapDetailsModal();
        }
    });

    return modal;
}

function ensureResponderMapPreviewSheet() {
    let sheet = document.getElementById('responderMapPreviewSheet');
    if (sheet) {
        return sheet;
    }

    sheet = document.createElement('div');
    sheet.id = 'responderMapPreviewSheet';
    sheet.className = 'responder-map-preview-sheet';
    sheet.setAttribute('aria-hidden', 'true');
    sheet.innerHTML = `
        <div class="responder-map-preview-sheet__backdrop" data-responder-map-preview-close></div>
        <div class="responder-map-preview-sheet__card" role="dialog" aria-modal="false" aria-label="Map marker preview">
            <button type="button" id="responderMapPreviewSheetClose" class="responder-map-preview-sheet__close" aria-label="Close preview">
                <i class="fas fa-xmark"></i>
            </button>
            <div class="responder-map-preview-sheet__handle" aria-hidden="true"></div>
            <div id="responderMapPreviewSheetBody" class="responder-map-preview-sheet__body"></div>
        </div>
    `;

    document.body.appendChild(sheet);
    document.getElementById('responderMapPreviewSheetClose')?.addEventListener('click', closeResponderMapPreviewSheet);
    sheet.querySelector('[data-responder-map-preview-close]')?.addEventListener('click', closeResponderMapPreviewSheet);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet.classList.contains('show')) {
            closeResponderMapPreviewSheet();
        }
    });

    return sheet;
}

function openResponderMapPreviewSheet(content, markerContext = null) {
    if (!shouldUseResponderMobileDetailsUi()) {
        return;
    }

    const sheet = ensureResponderMapPreviewSheet();
    const body = document.getElementById('responderMapPreviewSheetBody');
    if (body) {
        body.innerHTML = content;
    }

    sheet.classList.add('show');
    sheet.style.display = 'block';
    sheet.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    if (markerContext?.marker) {
        const marker = markerContext.marker;
        const sheetEditButton = body?.querySelector(`[id="edit${marker.marker_id}"]`);
        if (sheetEditButton && canManageMarkers) {
            sheetEditButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                openEditModal(marker);
            });
        }

        addPopupEventListeners({
            marker,
            incident: markerContext.incident || null,
            image: markerContext.image || null,
            created_at: markerContext.created_at || marker.created_at || null
        });
    }
}

function closeResponderMapPreviewSheet() {
    const sheet = document.getElementById('responderMapPreviewSheet');
    if (!sheet) {
        return;
    }

    if (sheet.contains(document.activeElement)) {
        document.activeElement?.blur?.();
    }

    sheet.classList.remove('show');
    sheet.style.display = 'none';
    sheet.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function openResponderMapDetailsModal(title, content) {
    closeResponderMapPreviewSheet();

    if (!detailsPanel) {
        initDetailsPanel();
    }

    const headerElement = detailsPanel?.querySelector('.details-header h3');
    const detailsContent = document.getElementById('detailsContent');

    if (headerElement) {
        headerElement.innerHTML = `<i class="fas fa-circle-info"></i> <span>${escapeHtml(title || 'Marker Details')}</span>`;
    }

    if (detailsContent) {
        detailsContent.innerHTML = content;
    }

    detailsPanel?.classList.add('compact-responder');
    detailsPanel?.classList.add('visible');
}

function closeResponderMapDetailsModal() {
    closeDetailsPanel();
}

function buildResponderMapInfoRows(rows = []) {
    return rows.map((row) => `
        <div class="responder-map-detail-row">
            <span class="responder-map-detail-label">${escapeHtml(row.label)}</span>
            <span class="responder-map-detail-value">${escapeHtml(row.value)}</span>
        </div>
    `).join('');
}

function buildResponderDetailsActionMarkup(marker, incident = null) {
    if (!marker) {
        return '';
    }

    const actions = [];
    const incidentId = incident?.incident_id ?? marker?.incident_id ?? null;
    const incidentStatus = normalizeIncidentStatus(marker.incident_status || incident?.status || 'reported');
    const blockingRoute = isIncidentMarker(marker)
        ? getBlockingIncidentRouteForCurrentTeam(incidentId)
        : null;
    const isIncidentRoutingBlocked = Boolean(blockingRoute);

    if (isIncidentMarker(marker)) {
        actions.push(buildIncidentCommandActionMarkup(incident, marker));

        if (!isDispatchCommandMapContext() && canManageMarkers && incidentStatus === 'reported') {
            actions.push(`
                <button type="button" class="responder-marker-popup-btn danger${isIncidentRoutingBlocked ? ' disabled' : ''}" onclick="window.respondCurrentMapDetail()" ${isIncidentRoutingBlocked ? 'disabled title="Your team is already navigating to another incident."' : ''}>
                    <i class="fas fa-phone-volume"></i>
                    ${isIncidentRoutingBlocked ? 'Team Busy' : 'Respond Now'}
                </button>
            `);
        }

        if (isIncidentRoutingBlocked) {
            actions.push(`
                <div class="responder-marker-popup-alert">
                    <i class="fas fa-circle-exclamation"></i>
                    Team already assigned to ${escapeHtml(getBlockingIncidentRouteLabel(blockingRoute))}
                </div>
            `);
        }

        actions.push(`
            <button type="button" class="responder-marker-popup-btn secondary${isIncidentRoutingBlocked ? ' disabled' : ''}" onclick="window.routeCurrentMapDetail()" ${isIncidentRoutingBlocked ? 'disabled title="Finish or clear the active team route before navigating to another incident."' : ''}>
                <i class="fas fa-route"></i>
                ${isIncidentRoutingBlocked ? 'Directions Locked' : 'Get Directions'}
            </button>
        `);

        actions.push(`
            <button type="button" class="responder-marker-popup-btn demo${isIncidentRoutingBlocked ? ' disabled' : ''}" onclick="window.routeCurrentMapDetail('demo')" ${isIncidentRoutingBlocked ? 'disabled title="Finish or clear the active team route before navigating to another incident."' : ''}>
                <i class="fas fa-flask"></i>
                Demo Route
                <span class="responder-marker-popup-badge">DEMO</span>
            </button>
        `);
    } else if (isStationMarker(marker)) {
        actions.push(`
            <button type="button" class="responder-marker-popup-btn secondary" onclick="window.routeCurrentMapDetail()">
                <i class="fas fa-route"></i>
                Get Directions
            </button>
        `);
    }

    if (canManageMarkers) {
        actions.push(`
            <div class="responder-map-detail-actions split">
                <button type="button" class="responder-marker-popup-btn secondary" onclick="window.openCurrentMapDetailEditor()">
                    <i class="fas fa-pen-to-square"></i>
                    Edit
                </button>
                <button type="button" class="responder-marker-popup-btn danger" onclick="window.deleteCurrentMapDetailMarker()">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        `);
    }

    if (!actions.length) {
        return '';
    }

    return `
        <div class="responder-map-detail-actions">
            ${actions.join('')}
        </div>
    `;
}

function showResponderMarkerDetails(marker, coord, imagePath = null) {
    currentDetailsMarker = marker;
    currentDetailsIncident = null;
    const presentation = getResponderPopupPresentation(marker);
    const createdAt = marker.created_at ? new Date(marker.created_at).toLocaleString() : 'Unknown';
    const updatedAt = marker.updated_at ? new Date(marker.updated_at).toLocaleString() : 'Unknown';
    const infoRows = [
        { label: 'Marker ID', value: marker.marker_id ? `#${marker.marker_id}` : 'Unavailable' },
        { label: 'Type', value: presentation.label },
        { label: 'Description', value: marker.description || 'No description' },
        { label: 'Coordinates', value: `${marker.lat}, ${marker.lng}` },
        { label: 'Created', value: createdAt },
        { label: 'Updated', value: updatedAt }
    ];

    if (isStationMarker(marker)) {
        infoRows.splice(2, 0,
            { label: 'Station Name', value: marker.station?.station_name || marker.title || 'Not available' },
            { label: 'Station Code', value: marker.station?.station_code || 'Unavailable' },
            { label: 'Hotline', value: marker.station?.contact_number || marker.contact_number || 'Not available' },
            { label: 'Email', value: marker.station?.email || 'Unavailable' }
        );
    } else if (isHydrantMarker(marker)) {
        infoRows.splice(2, 0,
            { label: 'Hydrant Code', value: marker.hydrant?.hydrant_code || 'Unavailable' },
            { label: 'Assigned Station', value: marker.hydrant?.station?.station_name || 'Not assigned' }
        );
    } else if (isSchoolMarker(marker)) {
        infoRows.splice(2, 0, { label: 'School Name', value: marker.school?.school_name || marker.title || 'Unavailable' });
    } else if (getMarkerCategoryKey(marker) === 'barangay') {
        infoRows.splice(2, 0, { label: 'Resource Type', value: 'Barangay Hall / Resource Marker' });
    }

    openResponderMapDetailsModal(
        marker.title || 'Marker Details',
        `
            <div class="responder-map-detail-panel">
                <div class="responder-map-detail-badge">
                    <i class="fas fa-circle-info"></i>
                    Location Summary
                </div>
                <div class="responder-map-detail-image-wrap">
                    ${imagePath
                        ? `<img src="${imagePath}" alt="${escapeHtml(marker.title || 'Marker image')}" class="responder-map-detail-image">`
                        : `
                            <div class="responder-map-detail-image-placeholder">
                                <i class="fas ${presentation.icon}"></i>
                                <span>No marker image uploaded</span>
                            </div>
                        `}
                </div>
                <div class="responder-map-detail-grid">
                    ${buildResponderMapInfoRows(infoRows)}
                </div>
                ${buildResponderDetailsActionMarkup(marker, null)}
            </div>
        `
    );
}

function showResponderIncidentDetails(marker, incident, imagePath = null) {
    currentDetailsMarker = marker;
    currentDetailsIncident = incident || null;
    const statusMeta = getResponderPopupStatusMeta(marker.incident_status || incident?.status || 'reported');
    const reportedAt = marker.reported_at
        ? new Date(marker.reported_at).toLocaleString()
        : (incident?.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown');
    const updatedAt = incident?.updated_at ? new Date(incident.updated_at).toLocaleString() : 'Unknown';
    const reportedBy = incident?.reporter
        ? [incident.reporter.first_name, incident.reporter.last_name].filter(Boolean).join(' ') || incident.reporter.email || 'Citizen Reporter'
        : (incident?.reported_by || 'Citizen Reporter');
    const assignedTeam = incident?.team?.team_name || incident?.team?.name || (incident?.team?.team_id ? `Team #${incident.team.team_id}` : 'Not yet assigned');

    openResponderMapDetailsModal(
        'Incident Details',
        `
            <div class="responder-map-detail-panel">
                <div class="responder-map-detail-badge danger">
                    <i class="fas fa-fire"></i>
                    Active Incident
                </div>
                <div class="responder-map-detail-image-wrap">
                    ${imagePath
                        ? `<img src="${imagePath}" alt="${escapeHtml(marker.title || 'Incident image')}" class="responder-map-detail-image">`
                        : `
                            <div class="responder-map-detail-image-placeholder">
                                <i class="fas fa-fire"></i>
                                <span>No incident image uploaded</span>
                            </div>
                        `}
                </div>
                <div class="responder-map-detail-grid">
                    ${buildResponderMapInfoRows([
                        { label: 'Marker ID', value: marker.marker_id ? `#${marker.marker_id}` : 'Unavailable' },
                        { label: 'Incident ID', value: incident?.incident_id ? `#${incident.incident_id}` : 'Unavailable' },
                        { label: 'Type', value: 'Fire Incident' },
                        { label: 'Status', value: statusMeta.label },
                        { label: 'Reported', value: reportedAt },
                        { label: 'Updated', value: updatedAt },
                        { label: 'Reported By', value: reportedBy },
                        { label: 'Assigned Team', value: assignedTeam },
                        { label: 'Description', value: marker.description || incident?.marker?.description || 'No description provided.' },
                        { label: 'Coordinates', value: `${marker.lat}, ${marker.lng}` }
                    ])}
                </div>
                ${buildResponderDetailsActionMarkup(marker, incident)}
            </div>
        `
    );
}

async function calculateRouteToStation(stationId, stationName = null, lat = null, lng = null) {
    await fetchStations({ preferSeeded: true });
    console.log('trying...', stations);
    console.log('station id...', stationId);

    const station = stations.find(s =>
        s?.marker?.marker_id == stationId
        || s?.marker_id == stationId
        || s?.station_id == stationId
        || s?.id == stationId
    );
    console.log('Found station:', station);

    const destinationLat = parseFloat(station?.lat ?? station?.marker?.lat ?? lat);
    const destinationLng = parseFloat(station?.lng ?? station?.marker?.lng ?? lng);

    if (!Number.isFinite(destinationLat) || !Number.isFinite(destinationLng)) {
        showToast('Station coordinates are not available.', 'error');
        throw new Error('Station coordinates are not available.');
    }

    const destination = {
        lat: destinationLat,
        lng: destinationLng,
        title: station?.station_name || stationName || 'Fire Station'
    };
    console.log('calculating route to station:', destination);

    const origin = typeof resolveRouteOrigin === 'function'
        ? await resolveRouteOrigin('real', destination)
        : window.userCoords;

    if (!origin?.ready || !Number.isFinite(Number(origin.currentLatitude)) || !Number.isFinite(Number(origin.currentLongitude))) {
        showToast('Getting your location... Please wait.', 'warning');
        throw new Error('Current location is not ready.');
    }

    window.thisIncidents = null;
    return await calculateRouteWithGeoapify(origin, destination);
}

async function calculateRouteWithGeoapify(origin, destination) {
    if (!origin || !destination || !window.geoapifyKey) {
        console.error('Missing origin, destination, or API key');
        throw new Error('Missing route configuration.');
    }
    console.log('Generating origin:', origin);
    console.log('Generating destination:', destination);
    
    const modeMap = {
        'driving': 'drive',
        'walking': 'walk',
        'cycling': 'bike'
    };
    let travelMode = 'driving';
    const mode = modeMap[travelMode] || 'drive';
    console.log('Generating mode:', mode);
    
    const url = `https://api.geoapify.com/v1/routing?waypoints=${origin.currentLatitude},${origin.currentLongitude}|${destination.lat},${destination.lng}&mode=${mode}&apiKey=${window.geoapifyKey}`;
    console.log('Generating url:', url);

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error('Route calculation failed');
        
        const data = await response.json();
        console.log('Generating route data:', data);
        
        if (!data.features || data.features.length === 0) {
            throw new Error('No route returned by Geoapify.');
        }

        const route = data.features[0];
        const routeSource = origin?.routeSource || destination?.routeSource || 'real';
        route.route_source = routeSource;
        route.origin_lat = origin?.currentLatitude ?? null;
        route.origin_lng = origin?.currentLongitude ?? null;
        route.destination_title = destination?.title || 'Selected destination';
        route.destination_lat = destination?.lat ?? null;
        route.destination_lng = destination?.lng ?? null;
        routeDestination = {
            ...destination,
            title: route.destination_title,
            routeSource,
        };
        window.routeDestination = routeDestination;
        window.activeRoute = route;

        if (window.thisIncidents) {
            // SAVE TO DATABASE FOR ALL USERS TO SEE
            const savedRoute = await saveRouteToDatabase(route, destination, origin);
            console.log("displaying route data1: ", savedRoute);

            // Add to multi-truck system
            if (savedRoute && savedRoute.data) {
                const routeId = savedRoute.data.route_id;
                const matchedIncident = window.incidentsData?.find(inc => inc.incident_id == window.thisIncidents);
                const persistedRoute = {
                    ...savedRoute.data,
                    route_source: savedRoute.data.route_source || routeSource,
                    route_team_id: matchedIncident?.team_id ?? null,
                    route_station_id: savedRoute.data.route_station_id ?? null
                };
                const displayed = await displayAndStartRoute(routeId, persistedRoute, destination);
                console.log("displaying route data2: ", persistedRoute);

                if (displayed === false) {
                    throw new Error('Route could not be displayed for this user.');
                }
            } else {
                // Fallback: still show route to the current user if DB persistence fails.
                console.warn('Route persistence failed; rendering local route fallback.');
                const displayed = displayGeoapifyRoute(route);
                if (!displayed) {
                    throw new Error('Route could not be displayed.');
                }
                updateRouteInfoFromGeoapify(route);
            }
        } else {
            console.log('No incident_id for route persistence, displaying station route locally');
            const displayed = displayGeoapifyRoute(route);
            if (!displayed) {
                throw new Error('Route could not be displayed.');
            }
            updateRouteInfoFromGeoapify(route);
        }

        updateRouteInfoFromGeoapify(route);
        console.log("displaying route data3: ", route);

        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard) routeCard.style.display = 'block';

        return { ok: true, route, routeSource };
        
    } catch (error) {
        console.error('Geoapify route calculation error:', error);
        console.log('error route data:', error);
        showToast(error.message || 'Route failed. Please try again.', 'error');
        throw error;
    }
}

function showToast(message, type = 'info') {
    return window.PyroNotify?.notify(message, type) ?? null;
}

function getTruckPopupLoadingMarkup(truck = {}) {
    return `
        <div style="min-width: 260px; max-width: 320px; font-family: system-ui, -apple-system, sans-serif;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div class="inline-fa-icon icon-spin" style="width: 1rem; height: 1rem; color: #2563eb;">
                    <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
                </div>
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #1f2937;">Fetching truck data</div>
                    <div style="font-size: 12px; color: #6b7280;">Loading team and target location...</div>
                </div>
            </div>
            <div style="background: #f8fafc; border-radius: 10px; padding: 10px; font-size: 12px; color: #64748b;">
                Truck ${truck.routeId || 'N/A'}
            </div>
        </div>
    `;
}

function getTruckPopupErrorMarkup(truck = {}, message = 'Failed to load truck details.') {
    return `
        <div style="min-width: 260px; max-width: 320px; font-family: system-ui, -apple-system, sans-serif;">
            <div style="font-size: 14px; font-weight: 700; color: #b91c1c; margin-bottom: 8px;">Truck ${truck.routeId || 'N/A'}</div>
            <div style="background: #fef2f2; color: #991b1b; border-radius: 10px; padding: 10px; font-size: 12px;">
                ${message}
            </div>
        </div>
    `;
}

function getTruckPopupMarkup(truck = {}, payload = {}) {
    const team = payload.team || null;
    const members = Array.isArray(team?.members) ? team.members : [];
    const targetLocation = payload.target_location || {};
    const membersHtml = members.length
        ? members.map((member, index) => `
            <div style="display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; ${index < members.length - 1 ? 'border-bottom: 1px solid #fed7aa;' : ''}">
                <span style="font-size: 12px; font-weight: 600; color: #1f2937;">${member.name}</span>
                <span style="font-size: 11px; color: #9a3412; text-align: right;">${member.role_label}</span>
            </div>
        `).join('')
        : '<div style="font-size: 12px; color: #6b7280;">No team members found.</div>';

    return `
        <div style="min-width: 280px; max-width: 340px; font-family: system-ui, -apple-system, sans-serif;">
            <div style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 12px 14px; border-radius: 12px 12px 0 0; margin: -12px -12px 0 -12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <div>
                        <div style="font-size: 15px; font-weight: 700;">${team?.label || `Truck ${truck.routeId || 'N/A'}`}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${String(payload.status || truck.status || 'moving').toUpperCase()}</div>
                    </div>
                    <i class="fas fa-truck" style="font-size: 20px;"></i>
                </div>
            </div>
            <div style="padding-top: 12px;">
                <div style="background: #f8fafc; border-radius: 10px; padding: 10px; margin-bottom: 10px;">
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">TARGET LOCATION</div>
                    <div style="font-size: 13px; font-weight: 600; color: #1f2937;">${targetLocation.title || truck.routeDestination?.title || 'Destination unavailable'}</div>
                    ${(targetLocation.lat && targetLocation.lng) ? `<div style="font-size: 11px; color: #64748b; margin-top: 4px;">${Number(targetLocation.lat).toFixed(6)}, ${Number(targetLocation.lng).toFixed(6)}</div>` : ''}
                </div>
                <div style="background: #fff7ed; border-radius: 10px; padding: 10px;">
                    <div style="font-size: 11px; color: #9a3412; margin-bottom: 6px;">TEAM MEMBERS</div>
                    ${membersHtml}
                </div>
            </div>
        </div>
    `;
}

function bindTruckDetailsPopup(routeId, marker, truck = null) {
    if (!marker) {
        return;
    }

    marker.bindPopup(getTruckPopupLoadingMarkup(truck || { routeId }), {
        minWidth: 260,
        maxWidth: 360
    });

    marker.on('popupopen', async () => {
        const activeTruck = truck || activeTrucks.get(routeId) || { routeId };
        const popup = marker.getPopup();

        if (popup) {
            popup.setContent(getTruckPopupLoadingMarkup(activeTruck));
        }

        showToast('Fetching truck data...', 'info');

        try {
            const response = await fetch(`/api/route/${routeId}/truck-details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.success === false) {
                throw new Error(payload.error || 'Failed to fetch truck details.');
            }

            if (popup) {
                popup.setContent(getTruckPopupMarkup(activeTruck, payload.data || {}));
            }
        } catch (error) {
            console.error('Failed to load truck popup details:', error);
            if (popup) {
                popup.setContent(getTruckPopupErrorMarkup(activeTruck, error.message || 'Failed to load truck details.'));
            }
            showToast(error.message || 'Failed to load truck data.', 'error');
        }
    });
}

function displayGeoapifyRoute(route) {
    if (!window.map || typeof L === 'undefined') {
        console.error('Map is not ready for route display');
        showToast('Map is not ready for route display.', 'error');
        return false;
    }

    if (routeLayer) {
        window.map.removeLayer(routeLayer);
    }
    
    const latlngs = extractRouteLatLngs(route);
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found in route');
        console.log('Route structure:', JSON.stringify(route?.geometry?.coordinates || route?.coordinates || route).substring(0, 500));
        showToast('Invalid route data', 'error');
        return false;
    }
    
    console.log('Final latlngs count:', latlngs.length);
    console.log('First point:', latlngs[0]);
    console.log('Last point:', latlngs[latlngs.length - 1]);
    
    // Store for simulation
    originalSimulationPoints = [...latlngs];
    window.remainingPoints = [...latlngs];
    simulationPoints = latlngs;
    
    // Draw the route line
    routeLayer = L.polyline(latlngs, {
        color: '#3b82f6',
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round',
        lineCap: 'round'
    }).addTo(window.map);

    if (typeof routeLayer.bringToFront === 'function') {
        routeLayer.bringToFront();
    }
    
    // Fit map to route bounds
    const bounds = routeLayer.getBounds();
    if (bounds.isValid()) {
        window.map.fitBounds(bounds, { padding: [50, 50] });
    }
    
    // Clear old markers
    clearRouteMarkers();
    
    // Add start marker
    const startPoint = latlngs[0];
    const startMarker = L.marker(startPoint, {
        icon: L.divIcon({
            className: 'start-marker',
            html: '<div style="background: #22c55e; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #22c55e;"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        })
    }).bindPopup('<strong>Start Point</strong><br>Route origin').addTo(window.map);
    
    // Add end marker
    const endPoint = latlngs[latlngs.length - 1];
    const endMarker = L.marker(endPoint, {
        icon: L.divIcon({
            className: 'end-marker',
            html: '<div style="background: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #ef4444;"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        })
    }).bindPopup('<strong>Destination</strong><br>target location').addTo(window.map);
    
    routeMarkers = [startMarker, endMarker];
    currentPointIndex = 0;
    
    // Add distance markers
    if (route.properties && route.properties.distance) {
        addDistanceMarkers(latlngs, route.properties.distance);
    } else if (route.total_distance) {
        addDistanceMarkers(latlngs, route.total_distance);
    }
    
    console.log('Route displayed successfully!');
    return true;
}

function addDistanceMarkers(latlngs, totalDistance) {
    if (!latlngs.length) return;
    
    let cumulativeDistances = [0];
    for (let i = 1; i < latlngs.length; i++) {
        const dist = calculateDistanceBetweenPoints(
            latlngs[i-1][0], latlngs[i-1][1],
            latlngs[i][0], latlngs[i][1]
        );
        cumulativeDistances.push(cumulativeDistances[i-1] + dist);
    }
    
    const interval = 500;
    
    for (let d = interval; d < totalDistance; d += interval) {
        for (let i = 1; i < cumulativeDistances.length; i++) {
            if (cumulativeDistances[i] >= d && cumulativeDistances[i-1] <= d) {
                const ratio = (d - cumulativeDistances[i-1]) / (cumulativeDistances[i] - cumulativeDistances[i-1]);
                const lat = latlngs[i-1][0] + (latlngs[i][0] - latlngs[i-1][0]) * ratio;
                const lng = latlngs[i-1][1] + (latlngs[i][1] - latlngs[i-1][1]) * ratio;
                
                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'distance-marker',
                        html: `<div style="background: #6b7280; width: 6px; height: 6px; border-radius: 50%; border: 1px solid white;"></div>`,
                        iconSize: [6, 6],
                        iconAnchor: [3, 3]
                    })
                }).bindPopup(`${(d/1000).toFixed(1)} km`).addTo(window.map);
                
                distanceMarkers.push(marker);
                break;
            }
        }
    }
}

function calculateDistanceBetweenPoints(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function getRouteStatusLabelFromId(statusId) {
    switch (Number(statusId)) {
        case 2:
            return 'en-route';
        case 3:
            return 'on-scene';
        case 4:
            return 'controlled';
        case 5:
            return 'closed';
        case 1:
        default:
            return 'reported';
    }
}

function syncIncidentStatusCaches(incidentId, statusId) {
    if (!incidentId) {
        return;
    }

    const normalizedStatus = getRouteStatusLabelFromId(statusId);

    if (Array.isArray(window.incidentsData)) {
        window.incidentsData = window.incidentsData.map((incident) => {
            if (Number(incident?.incident_id) !== Number(incidentId)) {
                return incident;
            }

            return {
                ...incident,
                status: normalizedStatus,
                incident_status: normalizedStatus,
                incident_status_id: Number(statusId),
            };
        });
    }

    if (Array.isArray(window.incidentCoords)) {
        window.incidentCoords = window.incidentCoords.map((incident) => {
            if (Number(incident?.incident_id) !== Number(incidentId)) {
                return incident;
            }

            return {
                ...incident,
                status: normalizedStatus,
                incident_status: normalizedStatus,
                incident_status_id: Number(statusId),
            };
        });
    }
}

async function updateIncidentStatusFromRoute(routeId, statusId) {
    const truck = activeTrucks.get(routeId);
    const incidentId = truck?.incidentId;
    if (!incidentId) {
        throw new Error(`Missing incident id for route ${routeId}`);
    }

    if (truck?.lastSentIncidentStatus === statusId) {
        return;
    }

    const response = await fetch(`/api/incidents/${incidentId}/${statusId}`, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.success === false) {
        throw new Error(payload.message || `Failed to update incident ${incidentId} to status ${statusId}`);
    }

    truck.lastSentIncidentStatus = statusId;
    truck.incidentStatusId = statusId;
    syncIncidentStatusCaches(incidentId, statusId);
    await refreshIncidentMapImmediately();
    if (typeof window.refreshFirefighterAssignedIncidents === 'function') {
        await window.refreshFirefighterAssignedIncidents();
    }
}

async function finalizeTruckArrival(routeId, lat = null, lng = null) {
    const truck = activeTrucks.get(routeId);
    if (!truck || truck.arrivalHandled) {
        return;
    }

    const fallbackPoint = truck.simulationPoints?.[truck.simulationPoints.length - 1] || null;
    const finalLat = Number.isFinite(lat) ? lat : fallbackPoint?.[0] ?? truck.routeDestination?.lat ?? null;
    const finalLng = Number.isFinite(lng) ? lng : fallbackPoint?.[1] ?? truck.routeDestination?.lng ?? null;

    truck.arrivalHandled = true;
    truck.onSceneStatusSent = true;
    truck.routeStatusSyncInFlight = true;

    const progress = truck.simulationPoints?.length
        ? (truck.currentPointIndex / truck.simulationPoints.length) * 100
        : 100;

    try {
        await updateRouteStatus(routeId, 'inactive', truck.currentPointIndex, finalLat, finalLng, progress);
    } catch (error) {
        truck.arrivalHandled = false;
        truck.onSceneStatusSent = false;
        truck.routeStatusSyncInFlight = false;
        console.error('Failed to finalize truck arrival:', error);
        showToast(error.message || 'Failed to finish navigation', 'error');
        return;
    } finally {
        if (truck) {
            truck.routeStatusSyncInFlight = false;
        }
    }

    await stopSimulationForRoute(routeId, false, false);
    showToast('Navigation reached the destination', 'success');
}

async function syncTruckArrivalStatus(routeId, lat, lng) {
    const truck = activeTrucks.get(routeId);
    if (!truck || truck.arrivalHandled || !truck.routeDestination) {
        return;
    }

    const distanceToIncident = calculateDistanceBetweenPoints(
        lat,
        lng,
        truck.routeDestination.lat,
        truck.routeDestination.lng
    );

    // Consider the truck "arrived/near target" within 50 meters.
    if (distanceToIncident <= 50) {
        await finalizeTruckArrival(routeId, lat, lng);
    }
}

function clearRouteMarkers() {
    routeMarkers.forEach(marker => {
        window.map.removeLayer(marker);
    });
    routeMarkers = [];
    
    if (distanceMarkers) {
        distanceMarkers.forEach(marker => {
            window.map.removeLayer(marker);
        });
        distanceMarkers = [];
    }
}

function updateRouteInfoFromGeoapify(route) {
    let distance, time;
    
    // Handle database format
    if (route.total_distance) {
        distance = route.total_distance / 1000;
        time = route.estimated_time / 60;
    } else if (route.properties) {
        // Handle Geoapify API format
        distance = route.properties.distance / 1000;
        time = route.properties.time / 60;
    } else {
        console.error('Unknown route format for info display');
        return;
    }
    
    const hours = Math.floor(time / 60);
    const minutes = Math.floor(time % 60);
    let durationText = hours > 0 ? `${hours} hr ${minutes} min` : `${minutes} min`;
    
    const distanceEl = document.getElementById('routeDistance');
    const durationEl = document.getElementById('routeDuration');
    const etaEl = document.getElementById('routeETA');
    const destinationTitle = document.getElementById('destinationTitle');
    const trackingMode = document.getElementById('routeTrackingMode');
    
    if (distanceEl) distanceEl.textContent = `${distance.toFixed(1)} km`;
    if (durationEl) durationEl.textContent = durationText;
    updateRouteSourceBadge(route?.route_source || getCurrentRouteSource());
    
    if (etaEl) {
        const eta = new Date(Date.now() + (time * 60 * 1000));
        etaEl.textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    const resolvedDestinationTitle = route?.destination_title
        || route?.routeDestination?.title
        || routeDestination?.title
        || window.routeDestination?.title
        || 'Selected destination';
    if (destinationTitle) {
        destinationTitle.textContent = resolvedDestinationTitle;
    }
    if (trackingMode) {
        trackingMode.textContent = isDemoRouteSource(route?.route_source || route?.routeSource)
            ? 'Demo route playback'
            : 'Following the calculated GPS route';
    }
    
    const routeCard = document.getElementById('routeInfoCard');
    if (routeCard) {
        routeCard.style.display = 'block';
    }
    
    // Older route cards may not include the progress indicator.
    if (routeCard && !document.getElementById('routeProgress')) {
        const progressHTML = `
            <div class="route-progress-container" style="margin-top: 12px; background: #e5e7eb; border-radius: 10px; overflow: hidden; height: 6px;">
                <div id="routeProgress" class="route-progress-bar" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.3s ease;"></div>
            </div>
        `;
        routeCard.insertAdjacentHTML('beforeend', progressHTML);
    }
}

// ============================================================================
// MULTI-TRUCK SUPPORT FUNCTIONS
// ============================================================================

// async function displayAndStartRoute(routeId, route, destination) {
//     console.log('Displaying route data:', route);
    
//     // Extract coordinates - handle the triple-nested string format
//     let coordinates = null;
    
//     // Case: coordinates is a string (from database)
//     if (typeof route.coordinates === 'string') {
//         try {
//             coordinates = JSON.parse(route.coordinates);
//             console.log('Parsed string coordinates, type:', Array.isArray(coordinates));
//         } catch (e) {
//             console.error('Failed to parse coordinates string:', e);
//             showToast('Invalid route coordinates', 'error');
//             return;
//         }
//     } 
//     // Case: coordinates is already an array
//     else if (Array.isArray(route.coordinates)) {
//         coordinates = route.coordinates;
//     }
//     // Case: route has geometry
//     else if (route.geometry && route.geometry.coordinates) {
//         coordinates = route.geometry.coordinates;
//     }
    
//     if (!coordinates) {
//         console.error('No coordinates found in route');
//         showToast('Invalid route data - no coordinates', 'error');
//         return;
//     }
    
//     console.log('Coordinates structure:', JSON.stringify(coordinates).substring(0, 200));
    
//     // Handle triple-nested array from database
//     let flattenedCoords = coordinates;
    
//     // Check if it's triple nested (first element is array, and its first element is also array)
//     if (Array.isArray(coordinates) && coordinates.length > 0) {
//         if (Array.isArray(coordinates[0]) && coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
//             // Triple nested - flatten by taking the first element
//             flattenedCoords = coordinates[0];
//             console.log('Flattened triple-nested coordinates, new length:', flattenedCoords.length);
//         }
//     }
    
//     // Convert [lng, lat] to [lat, lng] for Leaflet
//     const latlngs = [];
//     for (let i = 0; i < flattenedCoords.length; i++) {
//         const coord = flattenedCoords[i];
//         if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
//             // Geoapify returns [lng, lat], Leaflet wants [lat, lng]
//             latlngs.push([coord[1], coord[0]]);
//         }
//     }
    
//     if (latlngs.length === 0) {
//         console.error('No valid coordinates found after parsing');
//         showToast('Invalid route coordinates', 'error');
//         return;
//     }
    
//     console.log(`Route ${routeId} has ${latlngs.length} points`);
//     console.log('First point (lat,lng):', latlngs[0]);
//     console.log('Last point (lat,lng):', latlngs[latlngs.length - 1]);
    
//     const truckData = {
//         routeId: routeId,
//         simulationPoints: latlngs,
//         originalSimulationPoints: [...latlngs],
//         currentPointIndex: 0,
//         isSimulating: false,
//         routeDestination: destination,
//         routeLayer: null,
//         traveledLayer: null,
//         animatedVehicle: null,
//         animationFrame: null,
//         realTimeLocationWatch: null
//     };
    
//     activeTrucks.set(routeId, truckData);
    
//     // Display route line with unique color
//     const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//     const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
//     truckData.routeLayer = L.polyline(latlngs, {
//         color: truckColors[colorIndex],
//         weight: 5,
//         opacity: 0.8,
//         lineJoin: 'round',
//         lineCap: 'round'
//     }).addTo(window.map);
    
//     // Add start marker - validate point exists
//     if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
//         const startPoint = latlngs[0];
//         L.marker(startPoint, {
//             icon: L.divIcon({
//                 className: 'start-marker',
//                 html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                 iconSize: [12, 12],
//                 iconAnchor: [6, 6]
//             })
//         }).bindPopup('Start Point').addTo(window.map);
//     }
    
//     // Add end marker - validate point exists
//     if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
//         const endPoint = latlngs[latlngs.length - 1];
//         L.marker(endPoint, {
//             icon: L.divIcon({
//                 className: 'end-marker',
//                 html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                 iconSize: [12, 12],
//                 iconAnchor: [6, 6]
//             })
//         }).bindPopup(`Destination: ${destination.title}`).addTo(window.map);
//     }
    
//     // Fit map to route bounds
//     const bounds = truckData.routeLayer.getBounds();
//     if (bounds.isValid()) {
//         window.map.fitBounds(bounds, { padding: [50, 50] });
//     }
    
//     updateRouteInfoCard();
    
//     // AUTO-START SIMULATION - NO BUTTON NEEDED!
//     console.log('Auto-starting simulation for route:', routeId);
//     await startSimulationForRoute(routeId);
// }

async function displayAndStartRoute(routeId, route, destination) {
    console.log('Displaying route data:', route);
    if (!shouldDisplayRouteForCurrentUser(route)) {
        console.log('Skipping route for current user/team:', routeId);
        return false;
    }
    
    // Extract coordinates (same as before)
    const latlngs = extractRouteLatLngs(route);
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found after parsing');
        showToast('Invalid route coordinates', 'error');
        return false;
    }
    
    console.log(`Route ${routeId} has ${latlngs.length} points`);

    clearExistingRouteRendering(routeId);
    const routeGroup = getRouteStationLayer(route);
    updateRouteSourceBadge(route.route_source || destination?.routeSource || 'real');
    
    const truckData = {
        routeId: routeId,
        simulationPoints: latlngs,
        originalSimulationPoints: [...latlngs],
        currentPointIndex: 0,
        isSimulating: false,
        routeDestination: {
            ...destination,
            routeSource: route.route_source || destination?.routeSource || 'real'
        },
        incidentId: route.incident_id || null,
        incidentStatusId: Number(route.incident_status_id ?? destination?.incidentStatusId ?? 1),
        routeSource: route.route_source || destination?.routeSource || 'real',
        routeStationId: route.route_station_id || null,
        routeTeamId: route.route_team_id || null,
        routeTeamUserIds: Array.isArray(route.route_team_user_ids) ? route.route_team_user_ids : [],
        routePrimaryUserId: route.route_primary_user_id || null,
        renderGroup: routeGroup,
        routeLayer: null,
        traveledLayer: null,
        animatedVehicle: null,
        animationFrame: null,
        realTimeLocationWatch: null,
        startMarker: null,
        endMarker: null,
        onSceneStatusSent: false,
        arrivalHandled: false,
        lastSentIncidentStatus: Number(route.incident_status_id ?? destination?.incidentStatusId ?? 1),
        statusChangedByRoute: false,
        usesRealtimeTracking: false,
        lastRealtimeRouteSyncAt: 0,
        lastRealtimeLocationSignature: null,
        rerouteInFlight: false,
        lastRerouteAt: 0
    };
    
    activeTrucks.set(routeId, truckData);
    
    // Display route line with unique color
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
    truckData.routeLayer = registerRouteLayer(routeId, routeGroup, L.polyline(latlngs, {
        color: truckColors[colorIndex],
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round',
        lineCap: 'round'
    }));
    
    // Add start and end markers
    if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
        truckData.startMarker = registerRouteLayer(routeId, routeGroup, L.marker(latlngs[0], {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup('Start Point'));
    }
    
    if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
        truckData.endMarker = registerRouteLayer(routeId, routeGroup, L.marker(latlngs[latlngs.length - 1], {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup(`Destination: ${destination.title}`));
    }
    
    // Fit map to route bounds
    const bounds = truckData.routeLayer.getBounds();
    if (bounds.isValid()) {
        window.map.fitBounds(bounds, { padding: [50, 50] });
    }
    
    updateRouteInfoCard();
    
    // Start simulation for new route
    console.log('Starting simulation for new route:', routeId);
    await startSimulationForRoute(routeId);
    return true;
}

function addStartMovementButton(routeId, destinationTitle) {
    const routesList = document.getElementById('activeRoutesList');
    if (!routesList) return;
    
    const routeDivs = routesList.children;
    for (let i = 0; i < routeDivs.length; i++) {
        const div = routeDivs[i];
        if (div.innerHTML.includes(`Truck ${i+1}`)) {
            const startBtn = document.createElement('button');
            startBtn.innerHTML = `<i class="fa-solid fa-play"></i> Start Movement to ${destinationTitle}`;
            startBtn.style.cssText = 'width: 100%; margin-top: 8px; padding: 6px; background: #22c55e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;';
            startBtn.onclick = () => startSimulationForRoute(routeId);
            div.appendChild(startBtn);
            break;
        }
    }
}


async function startSimulationForRoute(routeId) {
    const truck = activeTrucks.get(routeId);
    if (!truck || !truck.simulationPoints || truck.simulationPoints.length === 0) {
        showToast('No route points available for simulation', 'error');
        return;
    }
    
    console.log('Starting simulation for route:', routeId);
    
    // Validate start point
    const startPoint = truck.simulationPoints[0];
    if (!startPoint || !startPoint[0] || !startPoint[1]) {
        console.error('Invalid start point coordinates:', startPoint);
        return;
    }
    
    try {
        // Directions never dispatch resources or mutate the incident lifecycle.
        await updateRouteStatus(
            routeId,
            'moving',
            0,
            startPoint[0],
            startPoint[1],
            0
        );
        console.log('Route status updated to moving with initial position');
    } catch (error) {
        console.error('Error starting route simulation:', error);
        showToast(error.message || 'Failed to start route simulation', 'error');
        return;
    }
    
    truck.isSimulating = true;
    truck.currentPointIndex = 0;
    
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
    if (truck.animatedVehicle && truck.renderGroup) {
        truck.renderGroup.removeLayer(truck.animatedVehicle);
    }
    if (truck.traveledLayer && truck.renderGroup) {
        truck.renderGroup.removeLayer(truck.traveledLayer);
    }
    
    const renderGroup = truck.renderGroup || getRouteStationLayer(truck);
    truck.animatedVehicle = registerRouteLayer(routeId, renderGroup, L.marker([startPoint[0], startPoint[1]], {
        icon: createRouteMovementIcon(truck.routeSource, truckColors[colorIndex]),
        zIndexOffset: 1000
    }));
    bindNavigationDetailsPopup(routeId, truck.animatedVehicle, truck);
    if (truck.simulationPoints[1]) {
        const nextPoint = truck.simulationPoints[1];
        const initialAngle = Math.atan2(nextPoint[1] - startPoint[1], nextPoint[0] - startPoint[0]) * 180 / Math.PI;
        setTimeout(() => applyTruckHeading(truck.animatedVehicle, initialAngle), 0);
    }
    truck.traveledLayer = registerRouteLayer(routeId, renderGroup, L.polyline([], {
        color: '#9ca3af',
        weight: 4,
        opacity: 0.6
    }));

    if (!isDemoRouteSource(truck.routeSource)) {
        startRealTimeLocationUpdatesForTruck(routeId);
    }

    const realtimeLocation = getTruckRealtimeLocation(truck);
    if (realtimeLocation) {
        updateTruckMarkerFromRealtimeLocation(routeId, realtimeLocation);
        showToast('Live navigation tracking enabled', 'success');
        return;
    }

    startSmoothAnimationForTruck(routeId);
    showToast(
        isDemoRouteSource(truck.routeSource)
            ? 'Demo navigation started.'
            : 'Navigation started.',
        'success'
    );
}

function startSmoothAnimationForTruck(routeId) {
    const truck = activeTrucks.get(routeId);
    if (!truck) {
        console.error('No truck found for route:', routeId);
        return;
    }
    
    if (!truck.simulationPoints || truck.simulationPoints.length < 2) {
        console.error('Not enough points for animation. Points:', truck.simulationPoints?.length);
        return;
    }
    
    const startIndex = truck.currentPointIndex;
    console.log('Starting smooth animation for truck:', routeId);
    console.log('Points count:', truck.simulationPoints.length);
    console.log('Starting from point index:', startIndex);
    console.log(`Progress: ${(startIndex / truck.simulationPoints.length * 100).toFixed(1)}%`);
    
    // Stop any existing animation
    if (truck.animationFrame) {
        cancelAnimationFrame(truck.animationFrame);
        truck.animationFrame = null;
    }
    
    // If we're already at or near the end, mark as complete
    if (startIndex >= truck.simulationPoints.length - 1) {
        console.log('Truck already at or near destination, marking as complete');
        finalizeTruckArrival(routeId).then(() => {
            showToast('Navigation reached the destination.', 'success');
        }).catch((error) => {
            console.error('Failed to finalize truck arrival:', error);
        });
        return;
    }
    
    // Build segments from current position (startIndex)
    const segments = [];
    let totalRemainingDistance = 0;
    
    for (let i = startIndex; i < truck.simulationPoints.length - 1; i++) {
        const p1 = truck.simulationPoints[i];
        const p2 = truck.simulationPoints[i + 1];
        
        if (!p1 || !p2) {
            console.warn('Invalid points at index:', i);
            continue;
        }
        
        const distance = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
        totalRemainingDistance += distance;
        segments.push({
            start: p1,
            end: p2,
            distance: distance,
            cumulativeDistance: totalRemainingDistance,
            angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
        });
    }
    
    if (segments.length === 0) {
        console.error('No valid segments created from index', startIndex);
        return;
    }
    
    console.log(`Total remaining distance from index ${startIndex}: ${totalRemainingDistance.toFixed(2)} meters`);
    console.log(`Segments count: ${segments.length}`);
    
    const baseSpeed = 13.89; // 50 km/h in m/s
    const totalRemainingDuration = totalRemainingDistance / baseSpeed;
    
    const startTime = performance.now();
    let lastUpdateTime = startTime;
    const UPDATE_INTERVAL = 1000;
    const ARRIVAL_CHECK_INTERVAL = 1000;

    // Reuse a single polyline instance during animation to avoid layer churn/jank.
    if (!truck.traveledLayer) {
        truck.traveledLayer = registerRouteLayer(
            routeId,
            truck.renderGroup || getRouteStationLayer(truck),
            L.polyline([], {
                color: '#9ca3af',
                weight: 4,
                opacity: 0.6,
                lineJoin: 'round',
                lineCap: 'round'
            })
        );
    }
    
    console.log(`Animation will run for ${totalRemainingDuration.toFixed(0)} seconds`);
    
    const animate = (currentTime) => {
        if (!activeTrucks.has(routeId)) {
            console.log('Truck removed, stopping animation');
            return;
        }
        
        const currentTruck = activeTrucks.get(routeId);
        if (currentTruck?.isStoppingSimulation) {
            currentTruck.animationFrame = null;
            return;
        }
        if (!currentTruck.animatedVehicle) {
            console.log('Truck vehicle missing, stopping animation');
            return;
        }
        
        const elapsed = (currentTime - startTime) / 1000;
        let progress = Math.min(elapsed / totalRemainingDuration, 1);
        
        if (progress >= 1) {
            console.log(`Navigation route ${routeId} arrived at destination.`);
            currentTruck.currentPointIndex = currentTruck.simulationPoints.length - 1;
            finalizeTruckArrival(routeId).then(() => {
                showToast('Navigation reached the destination.', 'success');
            }).catch((error) => {
                console.error('Failed to finalize truck arrival:', error);
            });
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
            if (!currentTruck.lastArrivalCheckAt || currentTime - currentTruck.lastArrivalCheckAt >= ARRIVAL_CHECK_INTERVAL) {
                currentTruck.lastArrivalCheckAt = currentTime;
                syncTruckArrivalStatus(routeId, lat, lng).catch((error) => {
                    console.error('Failed to sync truck arrival status:', error);
                });
            }
            
            // Calculate current point index based on progress
            let currentIndex = startIndex;
            let accumulatedDistance = 0;
            
            for (let i = startIndex; i < currentTruck.simulationPoints.length - 1; i++) {
                const p1 = currentTruck.simulationPoints[i];
                const p2 = currentTruck.simulationPoints[i + 1];
                if (!p1 || !p2) continue;
                
                const segDist = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
                if (accumulatedDistance + segDist >= distanceTraveled) {
                    currentIndex = i;
                    break;
                }
                accumulatedDistance += segDist;
                currentIndex = i + 1;
            }
            
            // Update the truck's current point index
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
            
            if (traveledPoints.length > 1 && currentTruck.traveledLayer) {
                currentTruck.traveledLayer.setLatLngs(traveledPoints);
            }
            
            // Update database periodically without blocking rendering.
            if (currentTime - lastUpdateTime > UPDATE_INTERVAL && !currentTruck.routeStatusSyncInFlight) {
                lastUpdateTime = currentTime;
                const progressPercent = (currentTruck.currentPointIndex / currentTruck.simulationPoints.length) * 100;
                currentTruck.routeStatusSyncInFlight = true;
                updateRouteStatus(routeId, 'moving', currentTruck.currentPointIndex, lat, lng, progressPercent)
                    .catch((error) => {
                        console.error('Failed to sync route moving status:', error);
                    })
                    .finally(() => {
                        const latestTruck = activeTrucks.get(routeId);
                        if (latestTruck) {
                            latestTruck.routeStatusSyncInFlight = false;
                        }
                    });
            }
            
            updateRouteInfoCard();
        }
        
        currentTruck.animationFrame = requestAnimationFrame(animate);
    };
    
    truck.animationFrame = requestAnimationFrame(animate);
}

window.testAnimation = async function(routeId) {
    const truck = activeTrucks.get(routeId);
    if (!truck) {
        console.log('No truck found for route:', routeId);
        console.log('Active trucks:', Array.from(activeTrucks.keys()));
        return;
    }
    console.log('Truck found:', truck);
    console.log('Simulation points:', truck.simulationPoints?.length);
    console.log('Starting animation...');
    startSmoothAnimationForTruck(routeId);
};

function startRealTimeLocationUpdatesForTruck(routeId) {
    const truck = activeTrucks.get(routeId);
    if (!truck) return;
    if (isDemoRouteSource(truck.routeSource)) return;
    if (truck.realTimeLocationWatch) clearInterval(truck.realTimeLocationWatch);
    
    truck.realTimeLocationWatch = setInterval(() => {
        const currentTruck = activeTrucks.get(routeId);
        if (!currentTruck || !currentTruck.animatedVehicle) {
            return;
        }

        const realtimeLocation = getTruckRealtimeLocation(currentTruck);
        if (realtimeLocation) {
            if (currentTruck.animationFrame) {
                cancelAnimationFrame(currentTruck.animationFrame);
                currentTruck.animationFrame = null;
            }

            updateTruckMarkerFromRealtimeLocation(routeId, realtimeLocation);
        }

        if (currentTruck.isSimulating && currentTruck.animatedVehicle) {
            const position = currentTruck.animatedVehicle.getLatLng();
            if (position) {
                const locationDisplay = document.getElementById('userLocation');
                if (locationDisplay) {
                    const trackingLabel = realtimeLocation ? 'Live' : 'Moving';
                    locationDisplay.innerHTML = `<i class="fa-solid fa-location-arrow"></i> Navigation: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)} (${trackingLabel})`;
                }
            }
        }
    }, TRUCK_REALTIME_REFRESH_MS);
}

function refreshRealtimeTrackedTrucks() {
    for (const [routeId, truck] of activeTrucks.entries()) {
        if (!truck?.isSimulating || !truck?.animatedVehicle) {
            continue;
        }

        const realtimeLocation = getTruckRealtimeLocation(truck);
        if (!realtimeLocation) {
            continue;
        }

        if (truck.animationFrame) {
            cancelAnimationFrame(truck.animationFrame);
            truck.animationFrame = null;
        }

        updateTruckMarkerFromRealtimeLocation(routeId, realtimeLocation);
    }
}

async function stopSimulationForRoute(routeId, syncWithServer = true, showStoppedToast = true) {
    const truck = activeTrucks.get(routeId);
    if (!truck) return false;

    if (truck.isStoppingSimulation) {
        return truck.stopPromise || null;
    }
    
    truck.isStoppingSimulation = true;
    truck.stopPromise = (async () => {
        if (syncWithServer) {
            try {
                await updateRouteStatus(routeId, 'inactive');

            } catch (error) {
                console.error(`Failed to stop simulation for route ${routeId}:`, error);
                showToast(error.message || 'Failed to stop route simulation cleanly', 'error');
                truck.isStoppingSimulation = false;
                truck.stopPromise = null;
                return false;
            }
        }

        if (truck.animationFrame) {
            cancelAnimationFrame(truck.animationFrame);
            truck.animationFrame = null;
        }
        if (truck.realTimeLocationWatch) {
            clearInterval(truck.realTimeLocationWatch);
            truck.realTimeLocationWatch = null;
        }

        truck.isSimulating = false;
        unregisterRouteRendering(routeId);
        activeTrucks.delete(routeId);

        truck.isStoppingSimulation = false;
        truck.stopPromise = null;
        updateRouteInfoCard();

        if (showStoppedToast) {
            showToast('Navigation stopped', 'info');
        }
        return true;
    })();

    return truck.stopPromise;
}

async function stopAllSimulations(syncWithServer = true) {
    const routeIds = Array.from(activeTrucks.keys());
    const results = await Promise.all(routeIds.map((routeId) => stopSimulationForRoute(routeId, syncWithServer, false)));
    return {
        requested: routeIds.length,
        stopped: results.filter(Boolean).length,
        remaining: activeTrucks.size,
    };
}

document.addEventListener('click', async (event) => {
    const button = event.target?.closest?.('[data-stop-route-navigation]');
    if (!button || button.disabled) return;

    button.disabled = true;
    const label = button.querySelector('span');
    const originalLabel = label?.textContent || 'Stop navigation';
    if (label) label.textContent = 'Stopping…';

    try {
        const result = await stopAllSimulations(true);
        if (result.remaining === 0) {
            document.getElementById('routeInfoCard')?.style.setProperty('display', 'none');
            showToast(result.stopped > 0 ? 'Navigation stopped' : 'No active navigation to stop', 'info');
        } else {
            showToast('Some routes could not be stopped. Please try again.', 'error');
            updateRouteInfoCard();
        }
    } catch (error) {
        console.error('Failed to stop navigation:', error);
        showToast(error.message || 'Failed to stop navigation', 'error');
    } finally {
        button.disabled = false;
        if (label) label.textContent = originalLabel;
    }
});

function createNavigationArrowIcon(color = '#2563eb') {
    return L.divIcon({
        className: 'pyro-navigation-arrow-wrap',
        html: `<span class="pyro-navigation-arrow" style="display:grid;place-items:center;width:44px;height:44px;color:${color};filter:drop-shadow(0 6px 6px rgba(15,23,42,.35))"><svg width="44" height="44" viewBox="0 0 48 48" aria-hidden="true"><path d="M24 4 41 42 24 34 7 42 24 4Z" fill="currentColor" stroke="white" stroke-width="3" stroke-linejoin="round"/></svg></span>`,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
        popupAnchor: [0, -22]
    });
}

function createDemoTruckIcon() {
    return L.icon({
        iconUrl: '/truck.png',
        className: 'pyro-demo-route-truck-marker',
        iconSize: [52, 52],
        iconAnchor: [26, 26],
        popupAnchor: [0, -24],
    });
}

function createRouteMovementIcon(routeSource, color = '#2563eb') {
    return isDemoRouteSource(routeSource)
        ? createDemoTruckIcon()
        : createNavigationArrowIcon(color);
}

function bindNavigationDetailsPopup(routeId, marker, route = null) {
    if (!marker) return;
    const destination = route?.routeDestination?.title || 'Selected destination';
    const isDemo = isDemoRouteSource(route?.routeSource);
    const source = isDemo ? 'Demo truck route' : 'Live navigation';
    const note = isDemo
        ? 'Demonstration only. This truck does not dispatch resources or update the incident status.'
        : 'This arrow represents navigation only. Apparatus appears after a response is accepted.';
    marker.bindPopup(`<div style="min-width:190px;font-family:system-ui,-apple-system,sans-serif"><strong style="color:#1d4ed8">${source}</strong><div style="margin-top:5px;font-size:12px;color:#475569">Directions to ${escapeHtml(destination)}</div><small style="display:block;margin-top:7px;color:#64748b">${note}</small></div>`);
}

function applyTruckHeading(marker, angle) {
    if (!marker) {
        return;
    }

    const markerElement = marker.getElement();
    if (!markerElement) {
        return;
    }

    if (markerElement.classList.contains('pyro-demo-route-truck-marker')) {
        return;
    }

    const visual = markerElement.querySelector('.pyro-navigation-arrow') || markerElement.querySelector('img') || markerElement.querySelector('.leaflet-marker-icon') || markerElement;
    visual.style.transformOrigin = '50% 50%';
    const currentTransform = visual.style.transform || '';
    const baseTransform = currentTransform.replace(/\s*rotate\([^)]*\)/g, '').trim();
    visual.style.transform = `${baseTransform} rotate(${angle}deg)`.trim();
}

function startRoutePolling() {
    if (routePollingInterval) {
        return;
    }

    const pollRoutes = async () => {
        if (!isPageVisible() || routePollingInFlight) {
            return;
        }

        routePollingInFlight = true;

        try {
            const response = await fetchLiveResponseOnce('route-active-poll', '/api/route/active', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            if (!response.ok) return;
            
            const routes = await response.json();
            console.log('Polling - found routes:', routes.length);
            const visibleRouteIds = new Set();
            
            for (const routeData of routes) {
                if (!shouldDisplayRouteForCurrentUser(routeData)) {
                    continue;
                }

                visibleRouteIds.add(routeData.route_id);

                // Check if we already have this route
                if (activeTrucks.has(routeData.route_id)) {
                    const existingTruck = activeTrucks.get(routeData.route_id);
                    existingTruck.routeTeamId = routeData.route_team_id || existingTruck.routeTeamId || null;
                    existingTruck.routeTeamUserIds = Array.isArray(routeData.route_team_user_ids)
                        ? routeData.route_team_user_ids
                        : (existingTruck.routeTeamUserIds || []);
                    existingTruck.routePrimaryUserId = routeData.route_primary_user_id || existingTruck.routePrimaryUserId || null;
                    existingTruck.routeSource = routeData.route_source || existingTruck.routeSource || 'real';
                    existingTruck.routeDestination = {
                        title: routeData.destination_title || existingTruck.routeDestination?.title || 'Destination',
                        lat: routeData.destination_lat ?? existingTruck.routeDestination?.lat ?? null,
                        lng: routeData.destination_lng ?? existingTruck.routeDestination?.lng ?? null,
                        routeSource: routeData.route_source || existingTruck.routeDestination?.routeSource || 'real',
                    };
                    // If route is moving but our local position is behind, update it
                     if (routeData.status === 'moving' && !existingTruck.isSimulating) {
                        const dbIndex = routeData.current_position_index || 0;
                        // const localIndex = existingTruck.currentPointIndex;
                        console.log(`Restarting stopped truck from DB position: ${dbIndex}`);
                        await startSimulationFromPosition(routeData.route_id, dbIndex);
                        
                        // If database has a newer position, update the truck
                        // if (dbIndex > localIndex + 1) {
                        //     console.log(`Updating truck position from ${localIndex} to ${dbIndex} from DB`);
                        //     await startSimulationFromPosition(routeData.route_id, dbIndex);
                        // }
                    }
                    continue;
                }
                
                console.log('Loading NEW route from DB:', routeData.route_id, 'status:', routeData.status, 'position:', routeData.current_position_index);
                
                const latlngs = extractRouteLatLngs(routeData);
                
                if (latlngs.length === 0) continue;
                
                console.log(`Route ${routeData.route_id} has ${latlngs.length} points`);
                
                // Get saved position from database
                const savedIndex = routeData.current_position_index || 0;
                console.log(`Saved position index: ${savedIndex}, total points: ${latlngs.length}`);
                clearExistingRouteRendering(routeData.route_id);
                const routeGroup = getRouteStationLayer(routeData);
                
                const truckData = {
                    routeId: routeData.route_id,
                    simulationPoints: latlngs,
                    originalSimulationPoints: [...latlngs],
                    currentPointIndex: savedIndex,
                    isSimulating: routeData.status === 'moving',
                    routeDestination: {
                        title: routeData.destination_title || 'Destination',
                        lat: routeData.destination_lat ?? latlngs[latlngs.length - 1]?.[0] ?? null,
                        lng: routeData.destination_lng ?? latlngs[latlngs.length - 1]?.[1] ?? null,
                        routeSource: routeData.route_source || 'real',
                    },
                    incidentId: routeData.incident_id || null,
                    routeSource: routeData.route_source || 'real',
                    routeStationId: routeData.route_station_id || null,
                    routeTeamId: routeData.route_team_id || null,
                    routeTeamUserIds: Array.isArray(routeData.route_team_user_ids) ? routeData.route_team_user_ids : [],
                    routePrimaryUserId: routeData.route_primary_user_id || null,
                    renderGroup: routeGroup,
                    routeLayer: null,
                    traveledLayer: null,
                    animatedVehicle: null,
                    animationFrame: null,
                    realTimeLocationWatch: null,
                    startMarker: null,
                    endMarker: null,
                    onSceneStatusSent: false,
                    arrivalHandled: false,
                    lastSentIncidentStatus: null,
                    usesRealtimeTracking: false,
                    lastRealtimeRouteSyncAt: 0,
                    lastRealtimeLocationSignature: null,
                    rerouteInFlight: false,
                    lastRerouteAt: 0
                };
                
                activeTrucks.set(routeData.route_id, truckData);
                
                const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
                const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeData.route_id) % truckColors.length;
                
                // Draw the route line
                truckData.routeLayer = registerRouteLayer(routeData.route_id, routeGroup, L.polyline(latlngs, {
                    color: truckColors[colorIndex],
                    weight: 5,
                    opacity: 0.8
                }));
                
                // Add start and end markers
                if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
                    truckData.startMarker = registerRouteLayer(routeData.route_id, routeGroup, L.marker(latlngs[0], {
                        icon: L.divIcon({
                            className: 'start-marker',
                            html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6]
                        })
                    }).bindPopup('Start Point'));
                }
                
                if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
                    truckData.endMarker = registerRouteLayer(routeData.route_id, routeGroup, L.marker(latlngs[latlngs.length - 1], {
                        icon: L.divIcon({
                            className: 'end-marker',
                            html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6]
                        })
                    }).bindPopup(`Destination: ${routeData.destination_title}`));
                }
                
                // Resume from saved position
                if (routeData.status === 'moving') {
                    console.log('Resuming moving route from position index:', savedIndex);
                    await startSimulationFromPosition(routeData.route_id, savedIndex);
                } else if (routeData.status === 'active') {
                    console.log('Starting new active route from beginning:', routeData.route_id);
                    await startSimulationForRoute(routeData.route_id);
                }
            }

            for (const existingRouteId of Array.from(activeTrucks.keys())) {
                if (!visibleRouteIds.has(existingRouteId)) {
                    await stopSimulationForRoute(existingRouteId, false, false);
                }
            }
            
            updateRouteInfoCard();
            
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }
            console.error('Error polling routes:', error);
        } finally {
            routePollingInFlight = false;
        }
    };

    pollRoutes();
    
    routePollingInterval = setInterval(pollRoutes, getRoutePollIntervalMs());
}

function startMapLivePolling() {
    if (!isPageVisible()) {
        return;
    }

    startRoutePolling();

    if (!markerStatePollingInterval) {
        markerStatePollingInterval = setInterval(pollMarkerStateChanges, getMarkerStatePollIntervalMs());
    }

    if (!boundaryStatePollingInterval) {
        boundaryStatePollingInterval = setInterval(pollBoundaryStateChanges, BOUNDARY_STATE_POLL_INTERVAL_MS);
        pollBoundaryStateChanges();
    }
}

async function startSimulationFromPosition(routeId, startIndex) {
    console.log('Starting simulation from position index:', startIndex);
    const truck = activeTrucks.get(routeId);
    if (!truck) {
        console.error('No truck found for route:', routeId);
        return;
    }
    
    // Validate start index - DON'T reset to 0, just ensure it's within bounds
    if (startIndex >= truck.simulationPoints.length) {
        console.warn('Start index exceeds points length, setting to last valid index');
        startIndex = truck.simulationPoints.length - 1;
    }
    
    if (startIndex < 0) {
        console.warn('Start index is negative, setting to 0');
        startIndex = 0;
    }
    
    // Get the start point
    const startPoint = truck.simulationPoints[startIndex];
    if (!startPoint || !startPoint[0] || !startPoint[1]) {
        console.error('Invalid start point at index:', startIndex);
        console.log('Available points:', truck.simulationPoints);
        return;
    }
    
    console.log(`Starting from index ${startIndex} with point:`, startPoint);
    console.log(`Total points: ${truck.simulationPoints.length}`);
    console.log(`Progress: ${(startIndex / truck.simulationPoints.length * 100).toFixed(1)}%`);
    
    // Set the current point index
    truck.currentPointIndex = startIndex;
    truck.isSimulating = true;
    
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
    // Remove existing vehicle if any
    if (truck.animatedVehicle && truck.renderGroup) {
        truck.renderGroup.removeLayer(truck.animatedVehicle);
    }
    if (truck.traveledLayer && truck.renderGroup) {
        truck.renderGroup.removeLayer(truck.traveledLayer);
    }
    
    // Create truck at saved position
    console.log('Creating truck at position:', [startPoint[0], startPoint[1]]);
    const renderGroup = truck.renderGroup || getRouteStationLayer(truck);
    truck.animatedVehicle = registerRouteLayer(routeId, renderGroup, L.marker([startPoint[0], startPoint[1]], {
        icon: createRouteMovementIcon(truck.routeSource, truckColors[colorIndex]),
        zIndexOffset: 1000
    }));
    bindNavigationDetailsPopup(routeId, truck.animatedVehicle, truck);
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
    
    console.log(`Creating traveled path with ${traveledPoints.length} points`);
    
    if (traveledPoints.length > 1) {
        truck.traveledLayer = registerRouteLayer(routeId, renderGroup, L.polyline(traveledPoints, {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6,
            lineJoin: 'round',
            lineCap: 'round'
        }));
    } else {
        truck.traveledLayer = registerRouteLayer(routeId, renderGroup, L.polyline([], {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6
        }));
    }
    
    startRealTimeLocationUpdatesForTruck(routeId);
    const realtimeLocation = getTruckRealtimeLocation(truck);
    if (realtimeLocation) {
        updateTruckMarkerFromRealtimeLocation(routeId, realtimeLocation);
        console.log(`Realtime responder tracking resumed for truck ${routeId}`);
        return;
    }

    // Start animation from current position
    startSmoothAnimationForTruck(routeId);
    
    console.log(`Resumed truck ${routeId} from position ${startIndex} (${(startIndex/truck.simulationPoints.length*100).toFixed(1)}%)`);
}

function getActionButtons(coord, incidents) {
    const marker = coord.marker || coord;
    if (isIncidentMarker(marker)) {
        const matchingIncident = incidents.find(incident => incident.marker?.marker_id === marker.marker_id);
        if (matchingIncident && matchingIncident.status === "reported") {
            return `<button id="respond${marker.marker_id}" class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 flex items-center gap-1">
                        <i class="fas fa-phone-alt"></i> Respond
                    </button>`;
        }
        return "";
    } else {
        return canManageMarkers ? `<div style="display:flex; gap: 4px;">
                    <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1.5 rounded" id="edit${marker.marker_id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" class="bg-red-600 text-white text-xs px-2 py-1.5 rounded" style="width: 40%;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>` : '';
    }
}

function addMarkerToFeatureGroup(categoryInput, marker) {
    if (!marker) return;

    switch (getMarkerCategoryKey(categoryInput)) {
        case 'station':
            if (stationFeatureGroup) stationFeatureGroup.addLayer(marker);
            else if (window.map) marker.addTo(window.map);
            break;
        case 'incident':
            if (incidentFeatureGroup) incidentFeatureGroup.addLayer(marker);
            else if (window.map) marker.addTo(window.map);
            break;
        case 'hydrant':
            if (hydrantFeatureGroup) hydrantFeatureGroup.addLayer(marker);
            else if (window.map) marker.addTo(window.map);
            break;
        case 'risk':
        case 'hazard':
            if (riskHazardFeatureGroup) riskHazardFeatureGroup.addLayer(marker);
            else if (window.map) marker.addTo(window.map);
            break;
        default:
            if (otherFeatureGroup) otherFeatureGroup.addLayer(marker);
            else if (window.map) marker.addTo(window.map);
    }
}

function addPopupEventListeners(coord) {
    const marker = coord.marker || coord;
    setTimeout(() => {
        if (!canManageMarkers) {
            document.getElementById('edit' + marker.marker_id)?.remove();
            document.getElementById('delete' + marker.marker_id)?.remove();
        }

        if (canManageMarkers) {
            const deleteBtn = document.getElementById('delete' + marker.marker_id);
            const editBtn = document.getElementById('edit' + marker.marker_id);
            const respondBtn = document.getElementById('respond' + marker.marker_id);
            if (deleteBtn) deleteBtn.addEventListener('click', () => deleteMarker(marker.marker_id));
            if (editBtn) editBtn.addEventListener('click', () => openEditModal(marker));
            if (respondBtn) respondBtn.addEventListener('click', () => respondIncident(marker));
        }

        const viewDetailsBtn = document.getElementById('viewDetails' + marker.marker_id);
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', async () => {
                showToast('Fetching details...', 'info');
                const popupImageSource = getMarkerPopupImagePath(marker, coord);
                const imagePath = normalizePopupImageUrl(popupImageSource);
                const markerData = { ...marker, category: getCategoryName(marker), created_at: coord.created_at || marker.created_at };
                let resolvedIncident = findIncidentForMarker(marker, coord.incident);
                const incidentId = resolvedIncident?.incident_id ?? resolvedIncident?.id ?? marker?.incident_id ?? null;

                if (isIncidentMarker(marker) && Number.isFinite(Number(incidentId)) && Number(incidentId) > 0) {
                    try {
                        resolvedIncident = await fetchIncidentDetailsById(incidentId) || resolvedIncident;
                    } catch (incidentDetailsError) {
                        console.error('Unable to fetch full incident details for details view:', incidentDetailsError);
                    }
                }

                if (shouldUseResponderPopupUi()) {
                    if (isIncidentMarker(marker)) {
                        showResponderIncidentDetails(markerData, resolvedIncident, imagePath);
                        return;
                    }

                    showResponderMarkerDetails(markerData, coord, imagePath);
                    return;
                }

                await showDetailsPanel(markerData, imagePath);
            });
        }
    }, 100);
}

function getCategoryName(categoryInput) {
    switch (getMarkerCategoryKey(categoryInput)) {
        case 'station': return 'Fire Station';
        case 'incident': return 'Incident';
        case 'hydrant': return 'Fire Hydrant';
        case 'school': return 'School';
        case 'barangay': return 'Barangay';
        case 'bridge': return 'Bridge';
        case 'risk': return 'Risk Marker';
        case 'hazard': return 'Hazard Marker';
        default: return 'Unknown';
    }
}

function getMarkerCategoryEnumValue(categoryInput) {
    const categoryKey = getMarkerCategoryKey(categoryInput);

    switch (categoryKey) {
        case 'incident': return 'fire';
        case 'station': return 'station';
        case 'hydrant': return 'hydrant';
        case 'school': return 'school';
        case 'barangay': return 'barangay';
        case 'bridge': return 'bridge';
        case 'risk': return 'risk';
        case 'hazard': return 'hazard';
        default: return null;
    }
}

function showMarkerFeedback(message, type = 'info', title = 'Map update') {
    if (typeof showNotification === 'function') {
        showNotification(message, type, title);
        return;
    }

    console[type === 'error' ? 'error' : 'log'](message);
}

function removeMapPlacementMarker(options = {}) {
    const { clearCoordinates = true } = options;

    if (startMarker && window.map) {
        try {
            window.map.removeLayer(startMarker);
        } catch (error) {
            console.warn('Unable to remove placement marker:', error);
        }
    }

    startMarker = null;

    if (clearCoordinates) {
        selectedLat = null;
        selectedLng = null;
    }
}

window.focusMapOnExternalLocation = function(lat, lng, options = {}) {
    if (!window.map || typeof L === 'undefined') {
        return false;
    }

    const latitude = Number(lat);
    const longitude = Number(lng);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return false;
    }

    const zoom = Number.isFinite(Number(options.zoom)) ? Number(options.zoom) : Math.max(window.map.getZoom?.() || 15, 16);
    const popupLabel = options.label || 'Selected location';
    const popupDescription = options.description || '';

    try {
        window.map.setView([latitude, longitude], zoom, { animate: true });

        const content = `
            <div style="min-width: 170px;">
                <div style="font-weight: 800; color: #0f172a;">${escapeHtml(String(popupLabel))}</div>
                ${popupDescription ? `<div style="margin-top: 4px; font-size: 12px; color: #475569;">${escapeHtml(String(popupDescription))}</div>` : ''}
                <div style="margin-top: 6px; font-size: 11px; color: #64748b;">${latitude.toFixed(6)}, ${longitude.toFixed(6)}</div>
            </div>
        `;

        L.popup()
            .setLatLng([latitude, longitude])
            .setContent(content)
            .openOn(window.map);

        return true;
    } catch (error) {
        console.warn('Unable to focus map on external location:', error);
        return false;
    }
};

function shouldAllowDashboardMarkerPlacementClick() {
    const path = window.location?.pathname || '';

    return Boolean(document.getElementById('markerModal'))
        && (path.includes('/station-admin/dashboard') || path.includes('/admin/dashboard'));
}

function handleManagedMapClick(e) {
    if (isDrawing) return;

    if (manageShapesMode) {
        let clickedShape = null;
        let clickedLayer = null;
        Object.values(shapeLayers).forEach(layer => {
            if (layer.getBounds && layer.getBounds().contains(e.latlng)) {
                clickedShape = layer.shapeId;
                clickedLayer = layer;
            }
        });

        if (clickedShape) {
            onShapeClick(clickedShape, clickedLayer, e.latlng);
        }

        return;
    }

    if (!markerPlacementSelectionActive && !shouldAllowDashboardMarkerPlacementClick()) {
        return;
    }

    selectedLat = e.latlng.lat;
    selectedLng = e.latlng.lng;
    removeMapPlacementMarker({ clearCoordinates: false });
    startMarker = L.marker(e.latlng).addTo(window.map);
    getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
}

function ensureManagedMapClickHandler() {
    if (!window.map) return;

    window.map.off('click', handleManagedMapClick);
    window.map.on('click', handleManagedMapClick);
    markerMapClickHandlerRegistered = true;
}

window.removeMapPlacementMarker = removeMapPlacementMarker;
window.enableMarkerPlacementSelection = function enableMarkerPlacementSelection() {
    markerPlacementSelectionActive = true;
    ensureManagedMapClickHandler();

    if (window.map?.getContainer && !manageShapesMode) {
        window.map.getContainer().style.cursor = 'crosshair';
    }
};

window.disableMarkerPlacementSelection = function disableMarkerPlacementSelection(options = {}) {
    const { clearCoordinates = true } = options;
    markerPlacementSelectionActive = false;
    removeMapPlacementMarker({ clearCoordinates });

    if (window.map?.getContainer && !manageShapesMode) {
        window.map.getContainer().style.cursor = '';
    }
};

function getOptimisticMarkerLayer(categoryInput) {
    switch (getMarkerCategoryKey(categoryInput)) {
        case 'station': return stationLayer;
        case 'incident': return incidentLayer;
        case 'hydrant': return hydrantLayer;
        default: return otherLayer;
    }
}

function createOptimisticMapMarker(lat, lng, title, categoryInput) {
    if (!window.map || !lat || !lng) {
        return null;
    }

    const optimisticId = `optimistic-${Date.now()}-${++optimisticMarkerSequence}`;
    const label = getCategoryName(categoryInput);
    const displayTitle = title || label || 'New marker';
    const icon = L.divIcon({
        className: 'pyro-optimistic-marker-wrapper',
        html: `
            <div class="pyro-optimistic-marker" aria-label="Saving ${label}">
                <span class="pyro-optimistic-marker__pulse"></span>
                <i class="fa-solid fa-location-dot"></i>
            </div>
        `,
        iconSize: [34, 42],
        iconAnchor: [17, 42],
        popupAnchor: [0, -38],
    });

    const marker = L.marker([lat, lng], {
        icon,
        interactive: false,
        keyboard: false,
        opacity: 0.94,
        zIndexOffset: 900,
    });
    const layer = getOptimisticMarkerLayer(categoryInput);

    if (!window.map.hasLayer(layer)) {
        layer.addTo(window.map);
    }

    marker.bindTooltip(`Saving ${displayTitle}`, { direction: 'top', offset: [0, -34] });
    marker.addTo(layer);
    optimisticMapMarkers.set(optimisticId, { marker, layer });

    return optimisticId;
}

function removeOptimisticMapMarker(optimisticId) {
    if (!optimisticId || !optimisticMapMarkers.has(optimisticId)) {
        return;
    }

    const entry = optimisticMapMarkers.get(optimisticId);
    try {
        entry.layer.removeLayer(entry.marker);
    } catch (error) {
        console.warn('Unable to remove optimistic marker:', error);
    }
    optimisticMapMarkers.delete(optimisticId);
}

function deleteMarker(value) {
    if (!confirm('Are you sure you want to delete this marker?')) return;
    fetch(`/api/markers/${value}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || `Server error: ${res.status}`);
        return data;
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Marker deleted successfully!', 'success');
            invalidateMarkerReferenceData();
            refreshDynamicMapData({ reason: 'marker-deleted' });
            if (currentDetailsMarker && currentDetailsMarker.marker_id == value) closeDetailsPanel();
        } else {
            showNotification(data.message || 'Failed to delete marker', 'error');
        }
    })
    .catch(err => {
        console.error('Delete error:', err);
        showNotification('Error deleting marker: ' + err.message, 'error');
    });
}

async function saveMarker(lat, lng, title, description, categoryId) {
    try {
        const category = getMarkerCategoryEnumValue(categoryId) || categoryId;
        const markerRes = await fetch(getMarkerFeedEndpoint(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ lat, lng, title, description, category, status: 'active', multimedia_id: 1 })
        });
        if (!markerRes.ok) throw new Error('Failed to save marker');
        const markerData = await markerRes.json();
        const markerId = markerData.data.marker_id;
        if (typeof closeModal === 'function') closeModal();
        const titleInput = document.getElementById('markerTitle');
        const descInput = document.getElementById('markerDescription');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        invalidateMarkerReferenceData();
        await refreshDynamicMapData({ reason: 'marker-created' });
        showNotification('Marker created successfully!', 'success');
        return markerId;
    } catch (err) {
        console.error("Error saving marker:", err);
        showNotification('Error saving marker: ' + err.message, 'error');
        return null;
    }
}

async function respondIncident(location) {
    console.log("respondIncident called with location:", location);
}

// ============================================================================
// SHAPE MANAGEMENT FUNCTIONS (keep all your existing shape functions)
// ============================================================================

function toggleShapeManagement() {
    manageShapesMode = !manageShapesMode;
    if (!manageShapesMode && activeShapeGeometryEdit) {
        cancelShapeGeometryEdit();
    }
    const toggleBtn = document.getElementById('toggleShapeMode');
    if (toggleBtn) {
        toggleBtn.innerHTML = manageShapesMode ? '<i class="fas fa-shapes mr-1"></i> Manage Shapes: ON' : '<i class="fas fa-shapes mr-1"></i> Manage Shapes: OFF';
        toggleBtn.style.background = manageShapesMode
            ? 'linear-gradient(135deg, #c63d26 0%, #d18a16 100%)'
            : '#fff7f3';
        toggleBtn.style.color = manageShapesMode ? '#ffffff' : '#9a3412';
        toggleBtn.style.border = manageShapesMode
            ? '1px solid rgba(198, 61, 38, 0.14)'
            : '1px solid rgba(198, 61, 38, 0.18)';
        toggleBtn.style.boxShadow = manageShapesMode
            ? '0 12px 28px rgba(198, 61, 38, 0.18)'
            : 'none';
    }
     if (window.map) {
        window.map.getContainer().style.cursor = manageShapesMode ? 'pointer' : '';
    }
    // window.map.getContainer().style.cursor = manageShapesMode ? 'pointer' : '';
    showNotification(`Shape management ${manageShapesMode ? 'enabled' : 'disabled'}`, 'info');
}

function getShapePopupMarkup(shapeId, shapeName) {
    const safeName = escapeHtml(shapeName || 'My Shape');

    return `
        <div style="min-width: 248px; padding: 0; font-family: Inter, sans-serif;">
            <div style="background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color: #fff; border-radius: 16px 16px 0 0; padding: 12px 14px;">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.82;">Shape Management</div>
                <div style="font-size: 14px; font-weight: 800; margin-top: 4px;">${safeName}</div>
            </div>
            <div style="background: #fff; border: 1px solid rgba(198, 61, 38, 0.10); border-top: 0; border-radius: 0 0 16px 16px; padding: 14px;">
                <label style="display:block; font-size: 11px; font-weight: 800; color: #9a3412; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">
                    Rename Shape
                </label>
                <input
                    id="shapeNameInput_${shapeId}"
                    type="text"
                    value="${safeName}"
                    style="width: 100%; height: 40px; border-radius: 12px; border: 1px solid #fed7aa; background: #fff7ed; color: #7c2d12; padding: 0 12px; font-size: 13px; outline: none;"
                >
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top: 12px;">
                    <button
                        id="shapeEditBtn_${shapeId}"
                        type="button"
                        style="flex:1; height:40px; border:0; border-radius:12px; background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color:#fff; font-size:12px; font-weight:800; cursor:pointer;"
                    >
                        <i class="fas fa-font" style="margin-right:6px;"></i>Rename
                    </button>
                    <button
                        id="shapeGeometryEditBtn_${shapeId}"
                        type="button"
                        style="height:40px; border:1px solid rgba(37, 99, 235, 0.18); border-radius:12px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:800; cursor:pointer;"
                    >
                        <i class="fas fa-draw-polygon" style="margin-right:6px;"></i>Edit Boundary
                    </button>
                    <button
                        id="shapeDeleteBtn_${shapeId}"
                        type="button"
                        style="grid-column:1 / -1; height:40px; border:1px solid rgba(198, 61, 38, 0.14); border-radius:12px; background:#fff7f3; color:#9a3412; font-size:12px; font-weight:800; cursor:pointer;"
                    >
                        <i class="fas fa-trash" style="margin-right:6px;"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    `;
}

function onShapeClick(shapeId, layer, latlng) {
    if (!manageShapesMode) return;
    selectedShapeForEdit = { id: shapeId, layer: layer, name: layer.shapeName };
    const popup = L.popup({
        closeButton: true,
        className: 'shape-management-popup',
        offset: [0, -8]
    })
        .setLatLng(latlng)
        .setContent(getShapePopupMarkup(shapeId, layer.shapeName))
        .openOn(window.map);

    setTimeout(() => {
        const editBtn = document.getElementById(`shapeEditBtn_${shapeId}`);
        const geometryEditBtn = document.getElementById(`shapeGeometryEditBtn_${shapeId}`);
        const deleteBtn = document.getElementById(`shapeDeleteBtn_${shapeId}`);

        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                editShape(selectedShapeForEdit);
            });
        }

        if (geometryEditBtn) {
            geometryEditBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                beginShapeGeometryEdit(selectedShapeForEdit);
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                deleteShape(shapeId);
            });
        }
    }, 0);
}

function editableLayersForShape(rootLayer) {
    const editableLayers = [];
    const visit = (layer) => {
        if (layer?.editing && typeof layer.editing.enable === 'function') {
            editableLayers.push(layer);
            return;
        }
        if (typeof layer?.eachLayer === 'function') layer.eachLayer(visit);
    };
    visit(rootLayer);
    return editableLayers;
}

function ensureShapeGeometryEditor() {
    let editor = document.getElementById('shapeGeometryEditor');
    if (editor) return editor;

    if (!document.getElementById('shapeGeometryEditorStyles')) {
        const style = document.createElement('style');
        style.id = 'shapeGeometryEditorStyles';
        style.textContent = `
            .shape-geometry-editor{position:fixed;left:50%;bottom:28px;z-index:2400;display:flex;max-width:min(620px,calc(100vw - 32px));align-items:center;gap:14px;padding:13px 14px;border:1px solid rgba(37,99,235,.2);border-radius:18px;background:rgba(255,255,255,.98);box-shadow:0 18px 44px rgba(15,23,42,.22);transform:translateX(-50%)}
            .shape-geometry-editor[hidden]{display:none!important}.shape-geometry-editor__icon{display:grid;width:42px;height:42px;flex:0 0 auto;place-items:center;border-radius:13px;background:#eff6ff;color:#2563eb}.shape-geometry-editor__copy{min-width:0;flex:1}.shape-geometry-editor__copy strong{display:block;color:#0f172a;font-size:13px}.shape-geometry-editor__copy span{display:block;margin-top:3px;color:#64748b;font-size:11px}.shape-geometry-editor__actions{display:flex;gap:8px}.shape-geometry-editor button{min-height:38px;padding:0 13px;border-radius:11px;font-size:11px;font-weight:850;cursor:pointer}.shape-geometry-editor__cancel{border:1px solid #cbd5e1;background:#fff;color:#475569}.shape-geometry-editor__save{border:0;background:#2563eb;color:#fff}
            @media(max-width:768px){.shape-geometry-editor{left:12px;right:12px;bottom:calc(var(--mobile-bottom-nav-h,84px) + 12px + env(safe-area-inset-bottom,0px));max-width:none;flex-wrap:wrap;transform:none}.shape-geometry-editor__copy{width:calc(100% - 58px)}.shape-geometry-editor__actions{width:100%}.shape-geometry-editor button{flex:1}}
        `;
        document.head.appendChild(style);
    }

    editor = document.createElement('section');
    editor.id = 'shapeGeometryEditor';
    editor.className = 'shape-geometry-editor';
    editor.hidden = true;
    editor.innerHTML = `
        <div class="shape-geometry-editor__icon"><i class="fas fa-draw-polygon" aria-hidden="true"></i></div>
        <div class="shape-geometry-editor__copy"><strong data-shape-geometry-title>Edit boundary</strong><span>Drag the white vertex handles to remove the overlap, then save.</span></div>
        <div class="shape-geometry-editor__actions">
            <button type="button" class="shape-geometry-editor__cancel" data-shape-geometry-cancel>Cancel</button>
            <button type="button" class="shape-geometry-editor__save" data-shape-geometry-save><i class="fas fa-check" aria-hidden="true"></i> Save Changes</button>
        </div>
    `;
    document.body.appendChild(editor);
    L.DomEvent.disableClickPropagation(editor);
    editor.querySelector('[data-shape-geometry-cancel]')?.addEventListener('click', cancelShapeGeometryEdit);
    editor.querySelector('[data-shape-geometry-save]')?.addEventListener('click', saveShapeGeometryEdit);
    return editor;
}

function beginShapeGeometryEdit(shapeData) {
    if (!shapeData?.layer) return;
    if (activeShapeGeometryEdit) cancelShapeGeometryEdit();

    const editableLayers = editableLayersForShape(shapeData.layer);
    if (!editableLayers.length) {
        showNotification('This saved shape type cannot be edited with map vertices.', 'warning');
        return;
    }

    window.map?.closePopup?.();
    editableLayers.forEach((layer) => layer.editing.enable());
    activeShapeGeometryEdit = {
        ...shapeData,
        editableLayers,
        originalGeojson: JSON.parse(JSON.stringify(shapeData.layer.shapeGeojson || shapeData.layer.toGeoJSON())),
    };
    const editor = ensureShapeGeometryEditor();
    editor.querySelector('[data-shape-geometry-title]').textContent = `Editing ${shapeData.name || 'boundary'}`;
    editor.hidden = false;
    window.map?.getContainer?.().classList.add('is-editing-saved-boundary');
    showNotification('Boundary editing enabled. Drag a vertex to adjust the saved shape.', 'info');
}

function editedGeojsonForShape(editState) {
    const exported = editState.layer.toGeoJSON();
    const originalType = editState.originalGeojson?.type;
    const singleFeature = exported?.type === 'FeatureCollection' && exported.features?.length === 1
        ? exported.features[0]
        : exported;
    if (originalType === 'Feature') return singleFeature;
    if (originalType && !['Feature', 'FeatureCollection'].includes(originalType)) return singleFeature?.geometry || singleFeature;
    return exported;
}

function finishShapeGeometryEdit() {
    activeShapeGeometryEdit?.editableLayers?.forEach((layer) => layer.editing.disable());
    activeShapeGeometryEdit = null;
    const editor = document.getElementById('shapeGeometryEditor');
    if (editor) editor.hidden = true;
    window.map?.getContainer?.().classList.remove('is-editing-saved-boundary');
}

async function cancelShapeGeometryEdit() {
    if (!activeShapeGeometryEdit) return;
    finishShapeGeometryEdit();
    invalidateShapeData();
    await loadShapes({ force: true });
    showNotification('Boundary changes canceled.', 'info');
}

async function saveShapeGeometryEdit() {
    if (!activeShapeGeometryEdit) return;
    const editState = activeShapeGeometryEdit;
    const saveButton = document.querySelector('[data-shape-geometry-save]');
    if (saveButton) saveButton.disabled = true;
    try {
        const response = await fetch(`/api/shapes/${editState.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ geojson: editedGeojsonForShape(editState) }),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `Server error ${response.status}`);
        finishShapeGeometryEdit();
        invalidateShapeData();
        announceBoundaryChange(payload.boundary_version);
        await loadShapes({ force: true });
        showNotification('Boundary geometry saved successfully!', 'success');
    } catch (error) {
        console.error('Error updating boundary geometry:', error);
        showNotification('Failed to save boundary geometry: ' + error.message, 'error');
    } finally {
        if (saveButton) saveButton.disabled = false;
    }
}

function editShape(shapeData) {
    const input = document.getElementById(`shapeNameInput_${shapeData.id}`);
    const newName = input?.value?.trim() || '';
    if (!newName) {
        showNotification('Please enter a shape name.', 'warning');
        return;
    }
    if (newName === shapeData.name) {
        window.map.closePopup();
        return;
    }
    fetch(`/api/shapes/${shapeData.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ name: newName })
    })
    .then(async res => {
        const raw = await res.text();
        const data = raw ? JSON.parse(raw) : {};
        if (!res.ok) throw new Error(data.message || `Server error ${res.status}`);
        return data;
    })
    .then(data => {
        showNotification('Shape updated successfully!', 'success');
        shapeData.layer.shapeName = newName;
        shapeData.layer.unbindTooltip();
        shapeData.layer.bindTooltip(newName, { permanent: false, direction: "top", sticky: true, opacity: 0.9 });
        window.map.closePopup();
        announceBoundaryChange(data.boundary_version);
        refreshDynamicMapData({ reason: 'boundary-renamed' });
    })
    .catch(err => {
        console.error('Error updating shape:', err);
        showNotification('Failed to update shape: ' + err.message, 'error');
    });
}

function deleteShape(shapeId) {
    console.log('deleting shape with: ', shapeId);
    fetch(`/api/shapes/${shapeId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' }
    })
    .then(async res => {
        const raw = await res.text();
        const data = raw ? JSON.parse(raw) : {};
        if (!res.ok) throw new Error(data.message || `Server error ${res.status}`);
        return data;
    })
    .then(data => {
        if (shapeLayers[shapeId]) {
            if (shapeFeatureGroup) shapeFeatureGroup.removeLayer(shapeLayers[shapeId]);
            delete shapeLayers[shapeId];
        }

        window.map.closePopup();
        showNotification('Shape deleted successfully!', 'success');
        announceBoundaryChange(data.boundary_version);
        refreshDynamicMapData({ reason: 'boundary-deleted' });
    })
    .catch(err => {
        console.error('Error deleting shape:', err);
        showNotification('Failed to delete shape: ' + err.message, 'error');
    });
}

async function loadShapes(options = {}) {
    const { force = false } = options;
    let fetchSequence = null;
    let fetchController = null;

    try {
        if (!force && lastShapesFetchAt > 0) {
            return;
        }

        if (!force && shouldSkipStaticLayerRefetch(lastShapesFetchAt)) {
            return;
        }

        fetchSequence = ++shapeFetchSequence;
        shapeFetchController?.abort();
        fetchController = new AbortController();
        shapeFetchController = fetchController;

        if (shapeFeatureGroup) shapeFeatureGroup.clearLayers();
        shapeLayers = {};
        const cachedShapes = readStaticMapCache('shapes');

        if (Array.isArray(cachedShapes) && cachedShapes.length > 0) {
            cachedShapes.forEach((shape, index) => {
                let geojson = shape.geojson;
                if (typeof geojson === "string") {
                    try { geojson = JSON.parse(geojson); } catch (e) { console.error('Error parsing geojson:', e); return; }
                }
                try {
                    const layer = L.geoJSON(geojson, {
                        style: { color: colors[index % colors.length], weight: 3, fillOpacity: 0.2, fillColor: colors[index % colors.length] },
                        interactive: true, pane: 'overlayPane'
                    });
                    layer.shapeId = shape.id;
                    layer.shapeName = shape.name;
                    layer.shapeBarangayId = shape.barangay_id || null;
                    layer.shapeBoundaryType = shape.boundary_type || 'custom';
                    layer.shapeSource = shape.source || 'manual';
                    layer.shapeGeojson = geojson;
                    layer.on('click', function(e) { if (manageShapesMode) { L.DomEvent.stopPropagation(e); onShapeClick(shape.id, layer, e.latlng); } });
                    if (shapeFeatureGroup) shapeFeatureGroup.addLayer(layer);
                    shapeLayers[shape.id] = layer;
                    layer.bindTooltip(shape.name || "Shape", { permanent: false, direction: "top", sticky: true, opacity: 0.9 });
                } catch (e) { console.error('Error creating cached layer for shape:', e); }
            });

            lastShapesFetchAt = Date.now();

            if (!force) {
                return;
            }
        }

        const response = await fetch('/api/shapes', {
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            signal: fetchController.signal
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const shapesPayload = await response.json();
        if (fetchSequence !== shapeFetchSequence) {
            return;
        }
        const shapes = Array.isArray(shapesPayload?.data) ? shapesPayload.data : (Array.isArray(shapesPayload) ? shapesPayload : []);
        writeStaticMapCache('shapes', shapes);
        if (shapeFeatureGroup) {
            shapeFeatureGroup.clearLayers();
        }
        shapeLayers = {};
        shapes.forEach((shape, index) => {
            let geojson = shape.geojson;
            if (typeof geojson === "string") {
                try { geojson = JSON.parse(geojson); } catch (e) { console.error('Error parsing geojson:', e); return; }
            }
            try {
                const layer = L.geoJSON(geojson, {
                    style: { color: colors[index % colors.length], weight: 3, fillOpacity: 0.2, fillColor: colors[index % colors.length] },
                    interactive: true, pane: 'overlayPane'
                });
                layer.shapeId = shape.id;
                layer.shapeName = shape.name;
                layer.shapeBarangayId = shape.barangay_id || null;
                layer.shapeBoundaryType = shape.boundary_type || 'custom';
                layer.shapeSource = shape.source || 'manual';
                layer.shapeGeojson = geojson;
                layer.on('click', function(e) { if (manageShapesMode) { L.DomEvent.stopPropagation(e); onShapeClick(shape.id, layer, e.latlng); } });
                if (shapeFeatureGroup) shapeFeatureGroup.addLayer(layer);
                shapeLayers[shape.id] = layer;
                layer.bindTooltip(shape.name || "Shape", { permanent: false, direction: "top", sticky: true, opacity: 0.9 });
            } catch (e) { console.error('Error creating layer for shape:', e); }
        });
        lastShapesFetchAt = Date.now();
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        console.error('Error loading shapes:', error);
        if (!isChiefDashboardMapInstance()) {
            showNotification('Error loading shapes', 'error');
        }
    } finally {
        if (fetchSequence === shapeFetchSequence) {
            shapeFetchController = null;
        }
    }
}

function normalizeFireRiskAreaGeojson(rawGeojson) {
    if (!rawGeojson) {
        return null;
    }

    if (typeof rawGeojson === 'string') {
        try {
            return JSON.parse(rawGeojson);
        } catch (error) {
            console.warn('Unable to parse fire-risk area geojson:', error);
            return null;
        }
    }

    return rawGeojson;
}

function fireRiskHeatWeight(point = {}, area = {}) {
    const areaLevel = String(area.risk_level || '').toLowerCase();
    const areaBaseline = areaLevel === 'high' ? 0.82 : areaLevel === 'medium' ? 0.58 : 0.36;
    const assessment = String(point.assessment_level || '').toLowerCase();
    const assessmentWeight = assessment === 'high'
        ? 0.96
        : ['moderate', 'medium'].includes(assessment) ? 0.68 : 0;
    const pointScore = Number(point.risk_score);
    const scoreWeight = Number.isFinite(pointScore) && pointScore > 0
        ? Math.min(1, Math.max(0.25, pointScore / 100))
        : 0;
    const incidentCount = Math.max(1, Number(point.incident_count) || 1);
    const incidentWeight = point.source_type === 'historical_incident'
        ? Math.min(0.92, 0.48 + (Math.log2(incidentCount) * 0.12))
        : 0;

    // Saved analysis heat uses the analyzed barangay probability/risk score for
    // its primary intensity. Local evidence can only weight an existing saved
    // analysis area; it can never create an independent report heatmap.
    if (area.source_type === 'saved_ai_forecast') {
        const analyzedScore = Number(area.probability_percent ?? area.risk_score);
        const analysisWeight = Number.isFinite(analyzedScore)
            ? Math.min(1, Math.max(0.25, analyzedScore / 100))
            : areaBaseline;
        const pressureCount = Object.values(point.pressure_flags || {}).filter(Boolean).length;
        const pressureWeight = pressureCount > 0 ? Math.min(0.96, 0.42 + (pressureCount * 0.09)) : 0;
        const households = Math.max(0, Number(point.estimated_affected_households) || 0);
        const householdWeight = households > 0 ? Math.min(0.88, 0.34 + (Math.log2(households + 1) * 0.08)) : 0;
        const casualties = Math.max(0, Number(point.injured_count) || 0) + (Math.max(0, Number(point.death_count) || 0) * 2);
        const lossWeight = casualties > 0 || Number(point.estimated_damage) > 0
            ? Math.min(0.94, 0.48 + (casualties * 0.08) + (Math.log10(Math.max(1, Number(point.estimated_damage) || 1)) * 0.04))
            : 0;
        const localSeverity = Math.max(
            0.35,
            assessmentWeight,
            scoreWeight,
            incidentWeight,
            pressureWeight,
            householdWeight,
            lossWeight
        );
        const localMultiplier = 0.72 + (localSeverity * 0.38);

        return Math.min(1, Math.max(0.18, analysisWeight * localMultiplier));
    }

    return Math.min(1, Math.max(0.25, areaBaseline, assessmentWeight, scoreWeight, incidentWeight));
}

function fireRiskHeatPoints(area = {}) {
    const evidencePoints = Array.isArray(area.evidence_points) ? area.evidence_points : [];

    return evidencePoints
        .map((point) => ({
            lat: Number(point.lat),
            lng: Number(point.lng),
            intensity: fireRiskHeatWeight(point, area),
            riskLevel: String(area.risk_level || 'medium').toLowerCase()
        }))
        .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng));
}

function fireRiskLocalFactorLabels(point = {}) {
    const pressureLabels = {
        blocked_hydrant: 'Blocked hydrant',
        combustible_materials_present: 'Combustible materials',
        informal_settlement: 'Dense informal settlement',
        cooking_fire_prone_activity: 'Fire-prone cooking activity',
        poor_waste_disposal: 'Poor waste disposal',
        previous_fire_prone_location: 'Known previous fire-prone location',
        poor_access: 'Difficult emergency access'
    };
    const labels = [];
    const assessment = String(point.assessment_level || '').trim();
    if (assessment) labels.push(`${assessment.replaceAll('_', ' ')} field assessment`);
    if (point.source_type === 'historical_incident') {
        labels.push(`${Math.max(1, Number(point.incident_count) || 1)} recorded fire occurrence(s)`);
    }
    Object.entries(point.pressure_flags || {}).forEach(([key, active]) => {
        if (active && pressureLabels[key]) labels.push(pressureLabels[key]);
    });
    const households = Math.max(0, Number(point.estimated_affected_households) || 0);
    if (households > 0) labels.push(`${households} potentially affected household(s)`);
    const casualties = Math.max(0, Number(point.injured_count) || 0) + Math.max(0, Number(point.death_count) || 0);
    if (casualties > 0) labels.push(`${casualties} historical casualty record(s)`);
    if (Number(point.estimated_damage) > 0) labels.push('Recorded historical property damage');

    return labels.slice(0, 6);
}

function createFireRiskHeatLayer(points = []) {
    const riskRank = { low: 0, medium: 1, high: 2 };
    const validPoints = points
        .filter((point) => (
            Number.isFinite(point.lat)
            && Number.isFinite(point.lng)
            && Number.isFinite(point.intensity)
        ))
        .sort((left, right) => (
            (riskRank[left.riskLevel] ?? 1) - (riskRank[right.riskLevel] ?? 1)
            || left.intensity - right.intensity
        ));

    return new (L.Layer.extend({
        onAdd(map) {
            this._map = map;
            this._canvas = L.DomUtil.create('canvas', 'leaflet-risk-heatmap-layer');
            this._canvas.setAttribute('aria-hidden', 'true');
            this._canvas.style.pointerEvents = 'none';
            this._canvas.style.mixBlendMode = 'multiply';
            map.getPane('overlayPane').appendChild(this._canvas);
            map.on('moveend zoomend resize', this._reset, this);
            this._reset();
        },

        onRemove(map) {
            map.off('moveend zoomend resize', this._reset, this);
            this._canvas?.remove();
            this._canvas = null;
            this._map = null;
        },

        _reset() {
            if (!this._map || !this._canvas) return;

            const size = this._map.getSize();
            const ratio = Math.max(1, window.devicePixelRatio || 1);
            const origin = this._map.containerPointToLayerPoint([0, 0]);
            L.DomUtil.setPosition(this._canvas, origin);
            this._canvas.style.width = `${size.x}px`;
            this._canvas.style.height = `${size.y}px`;
            this._canvas.width = Math.round(size.x * ratio);
            this._canvas.height = Math.round(size.y * ratio);

            const context = this._canvas.getContext('2d');
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.clearRect(0, 0, size.x, size.y);

            const zoom = this._map.getZoom();
            const centerLatitude = Number(this._map.getCenter()?.lat) || 0;
            const metersPerPixel = (156543.03392 * Math.cos(centerLatitude * Math.PI / 180)) / (2 ** zoom);
            const zoomSeparationFactor = 0.72 ** Math.max(0, zoom - 14);
            const effectiveBlendMeters = riskHeatBlendMeters * zoomSeparationFactor;
            const radius = Math.max(6, Math.min(78, effectiveBlendMeters / Math.max(0.1, metersPerPixel)));
            validPoints.forEach((point) => {
                const pixel = this._map.latLngToContainerPoint([point.lat, point.lng]);
                if (pixel.x < -radius || pixel.y < -radius || pixel.x > size.x + radius || pixel.y > size.y + radius) {
                    return;
                }

                const alpha = Math.max(0.16, Math.min(0.72, point.intensity * 0.7));
                const gradient = context.createRadialGradient(pixel.x, pixel.y, 0, pixel.x, pixel.y, radius);
                if (point.riskLevel === 'high') {
                    gradient.addColorStop(0, `rgba(185, 28, 28, ${alpha})`);
                    gradient.addColorStop(0.34, `rgba(239, 68, 68, ${alpha * 0.82})`);
                } else if (point.riskLevel === 'low') {
                    gradient.addColorStop(0, `rgba(34, 197, 94, ${alpha * 0.82})`);
                    gradient.addColorStop(0.34, `rgba(132, 204, 22, ${alpha * 0.7})`);
                } else {
                    gradient.addColorStop(0, `rgba(234, 88, 12, ${alpha})`);
                    gradient.addColorStop(0.34, `rgba(245, 158, 11, ${alpha * 0.82})`);
                }
                gradient.addColorStop(0.68, `rgba(250, 204, 21, ${alpha * 0.44})`);
                gradient.addColorStop(1, 'rgba(250, 204, 21, 0)');
                context.fillStyle = gradient;
                context.fillRect(pixel.x - radius, pixel.y - radius, radius * 2, radius * 2);
            });
        }
    }))();
}

function getFireRiskAreaStyle(area = {}) {
    const level = String(area.risk_level || '').toLowerCase();
    const overlayType = String(area.overlay_type || 'verified_evidence').toLowerCase();
    const timelineStyle = overlayType === 'next_month_forecast'
        ? { dashArray: '8 7', fillOpacity: 0.14, weight: 3 }
        : overlayType === 'current_analysis'
            ? { dashArray: null, fillOpacity: 0.22, weight: 3 }
            : { dashArray: '3 4', fillOpacity: 0.26, weight: 2.5 };

    if (level === 'high') {
        return {
            color: '#b91c1c',
            fillColor: '#ef4444',
            fillOpacity: timelineStyle.fillOpacity,
            opacity: 0.94,
            weight: timelineStyle.weight,
            dashArray: timelineStyle.dashArray,
            pane: 'overlayPane'
        };
    }

    if (level === 'low') {
        return {
            color: '#15803d',
            fillColor: '#22c55e',
            fillOpacity: timelineStyle.fillOpacity,
            opacity: 0.88,
            weight: timelineStyle.weight,
            dashArray: timelineStyle.dashArray,
            pane: 'overlayPane'
        };
    }

    return {
        color: '#d97706',
        fillColor: '#f59e0b',
        fillOpacity: timelineStyle.fillOpacity,
        opacity: 0.9,
        weight: timelineStyle.weight,
        dashArray: timelineStyle.dashArray,
        pane: 'overlayPane'
    };
}

function buildFireRiskAreaPopup(area = {}) {
    const metrics = area.metrics || {};
    const isForecast = area.source_type === 'saved_ai_forecast';
    const drivers = Array.isArray(area.drivers) && area.drivers.length
        ? area.drivers.map((driver) => `<li>${escapeHtml(String(driver))}</li>`).join('')
        : '<li>No detailed driver summary was saved for this area.</li>';
    const level = String(area.risk_level || 'medium').toUpperCase();
    const score = area.risk_score !== null && area.risk_score !== undefined
        ? Number(area.risk_score).toFixed(2)
        : 'N/A';
    const radiusMeters = Number(metrics.cluster_radius_meters || area.radius_meters || 0);
    const precisionLabel = area.geometry_precision === 'barangay_hall_reference'
        ? 'Barangay hall reference only; no geographic risk footprint is implied'
        : area.geometry_precision === 'barangay_boundary'
            ? 'Synced barangay boundary'
            : area.geometry_precision === 'original_evidence_points'
                ? 'Heat intensity at original report and incident positions'
                : area.geometry_precision === 'supporting_evidence_positions'
                    ? 'Forecast heat anchored to supporting historical evidence positions'
                : 'Localized verified-evidence cluster';
    const scopeLabel = area.overlay_type === 'next_month_forecast'
        ? 'Next-month forecast'
        : area.overlay_type === 'current_analysis'
            ? 'Current-month analysis'
            : 'Verified risk evidence';
    const subtitle = area.geometry_type === 'point'
        ? escapeHtml(area.city_name || 'Barangay reference location')
        : area.geometry_type === 'heatmap'
        ? `${escapeHtml(area.city_name || 'Localized evidence')} · ${Number(metrics.support_points || 0)} source positions`
        : area.geometry_type === 'circle'
        ? `${escapeHtml(area.city_name || 'Clustered nearby evidence')} · ${radiusMeters > 0 ? `${Math.round(radiusMeters)}m display radius` : 'Risk zone'}`
        : escapeHtml(area.city_name || 'Mapped barangay boundary');
    const riskColors = area.risk_level === 'high'
        ? ['#fee2e2', '#991b1b']
        : area.risk_level === 'low' ? ['#dcfce7', '#166534'] : ['#fef3c7', '#92400e'];
    const metricGrid = isForecast ? `
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;font-size:12px;color:#334155;">
            <span><strong>${area.probability_percent ?? 'N/A'}%</strong><br>probability</span>
            <span><strong>${area.confidence_percent ?? 'N/A'}%</strong><br>confidence</span>
            <span><strong>${Number(area.predicted_fire_incidents || 0)}</strong><br>expected fires</span>
        </div>` : `
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px;font-size:12px;color:#334155;">
            <span><strong>${Number(metrics.verified_records || 0)}</strong><br>verified records</span>
            <span><strong>${Number(metrics.historical_incidents || 0)}</strong><br>historical incidents</span>
            <span><strong>${Number(metrics.support_points || 0)}</strong><br>support points</span>
            <span><strong>${Number(metrics.affected_households || 0)}</strong><br>households</span>
        </div>`;
    const evidencePoints = !isForecast && Array.isArray(area.evidence_points)
        ? area.evidence_points
        : [];
    const evidenceList = evidencePoints.length ? `
        <div style="margin-top:12px;font-size:12px;color:#334155;">
            <strong>Contributing map points</strong>
            <ul style="margin:6px 0 0 18px;padding:0;line-height:1.45;">
                ${evidencePoints.map((point) => {
                    const isIncident = point.source_type === 'historical_incident';
                    const typeLabel = isIncident
                        ? 'Historical fire'
                        : (point.entry_type === 'hazard' ? 'Verified hazard' : 'Verified risk');
                    const name = point.marker_title || point.barangay_name || 'Mapped evidence';
                    const date = isIncident && point.incident_date ? ` · ${point.incident_date}` : '';
                    return `<li><strong>${escapeHtml(typeLabel)}</strong> · ${escapeHtml(name)}${escapeHtml(date)}</li>`;
                }).join('')}
            </ul>
        </div>` : '';
    const selectedPoint = area.selected_evidence_point || null;
    const localIntensity = selectedPoint ? Math.round(fireRiskHeatWeight(selectedPoint, area) * 100) : null;
    const localFactors = selectedPoint ? fireRiskLocalFactorLabels(selectedPoint) : [];
    const localHotspotDetails = selectedPoint ? `
        <div style="margin-top:12px;padding:10px;border:1px solid #fed7aa;border-radius:10px;background:#fff7ed;font-size:12px;color:#7c2d12;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;"><strong>Local hotspot intensity</strong><strong>${localIntensity}%</strong></div>
            <div style="height:6px;margin-top:7px;overflow:hidden;border-radius:999px;background:#ffedd5;"><span style="display:block;width:${localIntensity}%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#84cc16,#f59e0b,#dc2626);"></span></div>
            <div style="margin-top:7px;color:#9a3412;">Analysis probability weighted by this location's recorded conditions.</div>
            ${localFactors.length ? `<ul style="margin:7px 0 0 18px;padding:0;line-height:1.45;">${localFactors.map((factor) => `<li>${escapeHtml(factor)}</li>`).join('')}</ul>` : ''}
        </div>` : '';

    return `
        <div class="fire-risk-area-popup">
            <div style="font-weight:800;color:#0f172a;font-size:15px;">${escapeHtml(area.name || 'Fire-risk area')}</div>
            <div style="margin-top:4px;font-size:12px;color:#64748b;">${escapeHtml(scopeLabel)}${area.target_label ? ` · ${escapeHtml(area.target_label)}` : ''}</div>
            <div style="margin-top:3px;font-size:12px;color:#64748b;">${subtitle}</div>
            <div style="margin-top:3px;font-size:11px;color:#7c2d12;">${escapeHtml(precisionLabel)}</div>
            <div style="margin-top:10px;display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:${riskColors[0]};color:${riskColors[1]};font-weight:800;font-size:12px;">
                ${level} FIRE RISK · ${score}
            </div>
            ${metricGrid}
            ${localHotspotDetails}
            ${evidenceList}
            <div style="margin-top:12px;font-size:12px;color:#334155;">
                <strong>Why highlighted</strong>
                <ul style="margin:6px 0 0 18px;padding:0;line-height:1.45;">${drivers}</ul>
            </div>
        </div>
    `;
}

function createFireRiskAreaLayer(area = {}) {
    if (String(area.geometry_type || '').toLowerCase() === 'heatmap') {
        const points = fireRiskHeatPoints(area);
        return points.length ? createFireRiskHeatLayer(points) : null;
    }

    if (String(area.geometry_type || '').toLowerCase() === 'point') {
        const centerLat = Number(area?.center?.lat);
        const centerLng = Number(area?.center?.lng);

        if (!Number.isFinite(centerLat) || !Number.isFinite(centerLng)) {
            return null;
        }

        return L.circleMarker([centerLat, centerLng], {
            ...getFireRiskAreaStyle(area),
            radius: 9,
            fillOpacity: 0.08,
            weight: 3,
            dashArray: '3 3',
            interactive: true,
            pane: 'overlayPane'
        });
    }

    if (String(area.geometry_type || '').toLowerCase() === 'circle') {
        const centerLat = Number(area?.center?.lat);
        const centerLng = Number(area?.center?.lng);
        const radiusMeters = Number(area.radius_meters || area?.metrics?.cluster_radius_meters || 0);

        if (!Number.isFinite(centerLat) || !Number.isFinite(centerLng) || !Number.isFinite(radiusMeters) || radiusMeters <= 0) {
            return null;
        }

        return L.circle([centerLat, centerLng], {
            ...getFireRiskAreaStyle(area),
            radius: radiusMeters,
            interactive: true,
            pane: 'overlayPane'
        });
    }

    const geojson = normalizeFireRiskAreaGeojson(area.geojson);
    if (!geojson) {
        return null;
    }

    return L.geoJSON(geojson, {
        style: getFireRiskAreaStyle(area),
        interactive: true,
        pane: 'overlayPane'
    });
}

function addFireRiskEvidencePoints(area = {}, targetGroup = null) {
    if (!targetGroup) {
        return;
    }

    const points = Array.isArray(area.evidence_points) ? area.evidence_points : [];
    points.forEach((point) => {
        const lat = Number(point.lat);
        const lng = Number(point.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const isIncident = point.source_type === 'historical_incident';
        const label = isIncident
            ? `Analysis evidence · Historical fire${point.incident_date ? ` · ${point.incident_date}` : ''}`
            : `Analysis evidence · ${point.entry_type === 'hazard' ? 'Approved hazard location' : 'Approved risk location'}${point.marker_title ? ` · ${point.marker_title}` : ''}`;
        L.circleMarker([lat, lng], {
            radius: 14,
            color: 'transparent',
            weight: 0,
            fillColor: 'transparent',
            fillOpacity: 0,
            interactive: true,
            pane: 'overlayPane'
        })
            .bindTooltip(escapeHtml(label), { direction: 'top', sticky: true })
            .bindPopup(buildFireRiskAreaPopup({ ...area, selected_evidence_point: point }), {
                maxWidth: 320,
                className: 'fire-risk-area-popup-shell'
            })
            .addTo(targetGroup);
    });
}

async function loadFireRiskAreas() {
    if (!window.map || !fireRiskAreaLayerGroup) {
        return;
    }

    const fetchSequence = ++riskAreaFetchSequence;
    riskAreaFetchController?.abort();
    const fetchController = new AbortController();
    riskAreaFetchController = fetchController;

    try {
        const query = new URLSearchParams({ levels: 'low,medium,high' });
        if (currentStationMetaId) {
            query.set('station_id', currentStationMetaId);
        }
        const response = await fetch(`/api/fire-risk-areas?${query.toString()}`, {
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: fetchController.signal
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const payload = await response.json();
        if (fetchSequence !== riskAreaFetchSequence) {
            return;
        }
        const areas = Array.isArray(payload?.data) ? payload.data : [];
        fireRiskAreaLayerGroup.clearLayers();
        currentRiskAnalysisLayerGroup?.clearLayers();
        nextMonthForecastLayerGroup?.clearLayers();
        const heatmapBuckets = new Map();

        areas.forEach((area) => {
            try {
                const targetGroup = area.overlay_type === 'next_month_forecast'
                    ? nextMonthForecastLayerGroup
                    : area.overlay_type === 'current_analysis'
                        ? currentRiskAnalysisLayerGroup
                        : fireRiskAreaLayerGroup;

                const heatPoints = fireRiskHeatPoints(area);
                if (heatPoints.length) {
                    const bucket = heatmapBuckets.get(targetGroup) || { points: [], areas: [] };
                    bucket.points.push(...heatPoints);
                    bucket.areas.push(area);
                    heatmapBuckets.set(targetGroup, bucket);
                    return;
                }

                const layer = createFireRiskAreaLayer(area);
                if (!layer) {
                    return;
                }

                layer.bindTooltip(`${area.name || 'Barangay'} · ${String(area.risk_level || '').toUpperCase()} fire risk`, {
                    permanent: false,
                    direction: 'top',
                    sticky: true,
                    opacity: 0.92
                });
                layer.bindPopup(buildFireRiskAreaPopup(area), {
                    maxWidth: 320,
                    className: 'fire-risk-area-popup-shell'
                });
                layer.addTo(targetGroup || fireRiskAreaLayerGroup);
                addFireRiskEvidencePoints(area, targetGroup || fireRiskAreaLayerGroup);
            } catch (error) {
                console.warn('Unable to render fire-risk area overlay:', { area, error });
            }
        });

        heatmapBuckets.forEach((bucket, targetGroup) => {
            if (!targetGroup || !bucket.points.length) return;
            createFireRiskHeatLayer(bucket.points).addTo(targetGroup);
            bucket.areas.forEach((area) => addFireRiskEvidencePoints(area, targetGroup));
        });

        console.log('Fire risk areas loaded:', areas.length);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        console.error('Error loading fire risk areas:', error);
        if (!isChiefDashboardMapInstance()) {
            showNotification?.('Unable to load fire risk areas', 'warning');
        }
    } finally {
        if (fetchSequence === riskAreaFetchSequence) {
            riskAreaFetchController = null;
        }
    }
}

function announceBoundaryChange(signature = null) {
    if (signature) {
        lastBoundaryStateSignature = String(signature);
    }

    try {
        window.localStorage?.setItem(BOUNDARY_CHANGE_STORAGE_KEY, JSON.stringify({
            signature: signature || lastBoundaryStateSignature || '',
            changedAt: Date.now()
        }));
    } catch (error) {
        console.warn('Unable to announce boundary change to other tabs:', error);
    }
}

async function refreshDynamicMapData(options = {}) {
    dynamicMapRefreshQueued = true;
    if (dynamicMapRefreshPromise) {
        return dynamicMapRefreshPromise;
    }

    dynamicMapRefreshPromise = (async () => {
        do {
            dynamicMapRefreshQueued = false;
            invalidateShapeData();

            const refreshes = [
                fetchCoordinate({ force: true, forceIncidents: true }),
                fetchStations({ force: true }),
                loadShapes({ force: true }),
                loadFireRiskAreas()
            ];
            if (typeof window.refreshChiefDashboardStats === 'function') {
                refreshes.push(window.refreshChiefDashboardStats());
            }

            const results = await Promise.allSettled(refreshes);
            results.forEach((result) => {
                if (result.status === 'rejected' && result.reason?.name !== 'AbortError') {
                    console.error('Dynamic map refresh task failed:', result.reason);
                }
            });
        } while (dynamicMapRefreshQueued);
    })().finally(() => {
        dynamicMapRefreshPromise = null;
    });

    return dynamicMapRefreshPromise;
}

window.refreshDynamicMapData = refreshDynamicMapData;

function showLoadingModal(message) {
    if (document.getElementById('loadingModal')) return;
    const modal = document.createElement('div');
    modal.id = 'loadingModal';
    modal.style.cssText = `position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 10000;`;
    modal.innerHTML = `<div style="background: white; padding: 30px; border-radius: 12px; text-align: center;"><i class="fas fa-circle-notch fa-spin" style="font-size: 40px; color: #3b82f6;"></i><p style="margin-top: 15px;">${message}</p></div>`;
    document.body.appendChild(modal);
}

function focusBoundaryEditorContext() {
    if (!boundaryEditorContext || !window.map) {
        return;
    }

    const matchedLayer = Object.values(shapeLayers).find((layer) => {
        if (!layer) {
            return false;
        }

        const matchesBarangayId = boundaryEditorContext.barangayId
            && Number(layer.shapeBarangayId) === Number(boundaryEditorContext.barangayId);
        const matchesBarangayType = (layer.shapeBoundaryType || '').toLowerCase() === 'barangay';

        return matchesBarangayId && matchesBarangayType;
    });

    if (matchedLayer?.getBounds) {
        const bounds = matchedLayer.getBounds();
        if (bounds.isValid()) {
            window.map.fitBounds(bounds.pad(0.2));
        }

        if (typeof matchedLayer.openTooltip === 'function') {
            matchedLayer.openTooltip();
        }

        showNotification(`Boundary editor ready for ${boundaryEditorContext.barangayName || 'the selected barangay'}.`, 'success');
        return;
    }

    if (boundaryEditorContext.lat !== null && boundaryEditorContext.lng !== null) {
        window.map.setView([boundaryEditorContext.lat, boundaryEditorContext.lng], Math.max(window.map.getZoom(), 15));
        showNotification(`No saved polygon yet for ${boundaryEditorContext.barangayName || 'this barangay'}. Draw one on the map to save it.`, 'info');
        return;
    }

    showNotification(`Boundary editor opened for ${boundaryEditorContext.barangayName || 'the selected barangay'}.`, 'info');
}

function closeModal() {
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) document.body.removeChild(loadingModal);
    const successModal = document.getElementById('successModal');
    if (successModal) document.body.removeChild(successModal);
}

function enhancePyroLayerControl() {
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    const isFirefighterLayerContext = configuredInstanceKey === 'firefighterDashboard';
    const isChiefLayerContext = isChiefCommandMapContext();

    if (!isCitizenDashboardMapContext() && !isFirefighterLayerContext && !isChiefLayerContext) {
        return;
    }

    const control = document.querySelector('.leaflet-control-layers');
    const list = control?.querySelector('.leaflet-control-layers-list');
    const toggle = control?.querySelector('.leaflet-control-layers-toggle');
    const base = control?.querySelector('.leaflet-control-layers-base');
    const overlays = control?.querySelector('.leaflet-control-layers-overlays');

    if (!control || !list || !toggle || !base || !overlays || control.dataset.pyroEnhanced === 'true') {
        return;
    }

    control.dataset.pyroEnhanced = 'true';
    control.classList.add('pyro-layer-control', 'pyro-citizen-layer-control');
    control.classList.toggle('pyro-firefighter-layer-control', isFirefighterLayerContext);
    control.classList.toggle('pyro-chief-layer-control', isChiefLayerContext);
    toggle.setAttribute('aria-label', 'Open map layers');

    const countBadge = document.createElement('span');
    countBadge.className = 'pyro-map-layer-count citizen-map-layer-count';
    toggle.appendChild(countBadge);

    const header = document.createElement('div');
    header.className = 'pyro-layer-panel-header citizen-layer-panel-header';
    header.innerHTML = `
        <span>Map layers</span>
        <button type="button" class="pyro-layer-panel-close citizen-layer-panel-close" aria-label="Close map layers">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    `;
    list.prepend(header);
    header.querySelector('.pyro-layer-panel-close')?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        control.classList.remove('leaflet-control-layers-expanded');
        toggle.setAttribute('aria-expanded', 'false');
    });

    const styleTitle = document.createElement('div');
    styleTitle.className = 'pyro-layer-section-title citizen-layer-section-title';
    styleTitle.textContent = 'Map style';
    base.before(styleTitle);

    const overlayTitle = document.createElement('div');
    overlayTitle.className = 'pyro-layer-section-title citizen-layer-section-title';
    overlayTitle.textContent = 'Show on map';
    overlays.before(overlayTitle);

    const normalizeLayerLabel = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const displayLabelMap = {
        'Carto Light': 'Carto',
        'Street Map': 'OSM',
        'OSM Standard': 'OSM',
        'OSM HOT': 'OSM HOT',
        Satellite: 'Satellite',
        Stations: 'Fire stations',
        'Fire Stations': 'Fire stations',
        Incidents: 'Incidents',
        Hydrants: 'Fire hydrants',
        'Fire Hydrants': 'Fire hydrants',
        'Risk & Hazard Reports': 'Risk and hazard reports',
        'Other Markers': 'Other locations',
        'Water Reference': 'Water references',
        Shapes: 'Boundaries',
        'Fire Risk Areas': 'Fire risk areas'
    };
    const iconMap = {
        'Fire stations': 'fa-building-shield',
        Incidents: 'fa-fire-flame-curved',
        'Fire hydrants': 'fa-droplet',
        'Risk and hazard reports': 'fa-triangle-exclamation',
        'Other locations': 'fa-location-dot',
        'Water references': 'fa-water',
        Boundaries: 'fa-draw-polygon',
        'Fire risk areas': 'fa-map-location-dot'
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
        label.title = displayLabel;
        label.setAttribute('aria-label', displayLabel);
        span.textContent = '';

        const icon = document.createElement('i');
        icon.className = `fa-solid ${iconMap[displayLabel] || 'fa-location-dot'} pyro-layer-row-icon citizen-layer-row-icon`;
        icon.setAttribute('aria-hidden', 'true');

        const text = document.createElement('span');
        text.textContent = displayLabel;

        span.appendChild(icon);
        span.appendChild(text);
        span.appendChild(input);
    });

    const updateBadge = () => {
        const activeCount = Array.from(overlays.querySelectorAll('input')).filter((input) => input.checked).length;
        countBadge.hidden = activeCount === 0;
        countBadge.textContent = String(activeCount);
    };

    control.addEventListener('change', updateBadge);
    updateBadge();
}

const enhanceCitizenDesktopLayerControl = enhancePyroLayerControl;

function syncPyroSharedMapLayerControls() {
    const registry = window.pyroSharedMapLayers;
    if (!registry || !window.map) {
        return;
    }

    const activeBaseLabel = Object.entries(registry.baseMaps || {})
        .find(([, layer]) => window.map.hasLayer(layer))?.[0] || registry.defaultBaseLabel || '';

    document.querySelectorAll('[data-ff-map-base-toggle]').forEach((input) => {
        input.checked = input.dataset.ffMapBaseToggle === activeBaseLabel;
    });

    document.querySelectorAll('[data-ff-map-layer-toggle]').forEach((input) => {
        const layer = registry.overlays?.[input.dataset.ffMapLayerToggle];
        input.checked = Boolean(layer && window.map.hasLayer(layer));
    });
}

function bindPyroSharedMapLayerInputs(root = document) {
    const scope = root || document;

    scope.querySelectorAll('[data-ff-map-base-toggle]').forEach((input) => {
        if (input.dataset.pyroLayerInputBound === 'true') {
            return;
        }

        input.addEventListener('change', () => {
            if (input.checked) {
                window.setPyroMapBaseLayer?.(input.dataset.ffMapBaseToggle);
            }
        });
        input.dataset.pyroLayerInputBound = 'true';
    });

    scope.querySelectorAll('[data-ff-map-layer-toggle]').forEach((input) => {
        if (input.dataset.pyroLayerInputBound === 'true') {
            return;
        }

        input.addEventListener('change', () => {
            window.setPyroMapOverlayVisibility?.(input.dataset.ffMapLayerToggle, input.checked);
        });
        input.dataset.pyroLayerInputBound = 'true';
    });
}

function exposePyroSharedMapLayers(baseMaps, overlays, defaultBaseLabel) {
    window.pyroSharedMapLayers = {
        baseMaps,
        overlays,
        defaultBaseLabel
    };

    window.setPyroMapBaseLayer = function setPyroMapBaseLayer(label) {
        const registry = window.pyroSharedMapLayers;
        const targetLayer = registry?.baseMaps?.[label];
        if (!window.map || !targetLayer) {
            return;
        }

        Object.values(registry.baseMaps || {}).forEach((layer) => {
            if (layer && window.map.hasLayer(layer)) {
                window.map.removeLayer(layer);
            }
        });

        targetLayer.addTo(window.map);
        syncPyroSharedMapLayerControls();
    };

    window.setPyroMapOverlayVisibility = function setPyroMapOverlayVisibility(label, visible) {
        const registry = window.pyroSharedMapLayers;
        const layer = registry?.overlays?.[label];
        if (!window.map || !layer) {
            return;
        }

        if (visible && !window.map.hasLayer(layer)) {
            window.map.addLayer(layer);
        } else if (!visible && window.map.hasLayer(layer)) {
            window.map.removeLayer(layer);
        }

        syncPyroSharedMapLayerControls();
    };

    window.syncPyroSharedMapLayerControls = syncPyroSharedMapLayerControls;
    bindPyroSharedMapLayerInputs();
}

function createResponderMobileMapLayersPanel() {
    if (document.querySelector('[data-ff-map-layers-panel]') || !isChiefCommandMapContext()) {
        return null;
    }

    const panel = document.createElement('div');
    panel.className = 'firefighter-mobile-map-panel responder-mobile-map-panel md:hidden';
    panel.id = 'chiefMobileMapLayersPanel';
    panel.dataset.ffMapLayersPanel = '';
    panel.setAttribute('aria-hidden', 'true');
    panel.setAttribute('inert', '');
    panel.innerHTML = `
        <div class="firefighter-mobile-map-panel__backdrop" data-ff-map-layers-close></div>
        <div class="firefighter-mobile-map-panel__sheet" role="dialog" aria-modal="false" aria-label="Map options">
            <div class="firefighter-mobile-map-panel__handle" aria-hidden="true"></div>
            <div class="firefighter-mobile-map-panel__header">
                <div>
                    <h2 class="firefighter-mobile-map-panel__title">Map options</h2>
                    <p class="firefighter-mobile-map-panel__subtitle">Show or hide details on the map</p>
                </div>
                <button type="button" class="firefighter-mobile-map-panel__close" data-ff-map-layers-close aria-label="Close map options">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="firefighter-mobile-map-panel__body">
                <div class="firefighter-mobile-map-panel__base" role="radiogroup" aria-label="Map style">
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="chief_map_base" data-ff-map-base-toggle="Carto Light" checked>
                        <span>Carto</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="chief_map_base" data-ff-map-base-toggle="OSM Standard">
                        <span>OSM</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="chief_map_base" data-ff-map-base-toggle="OSM HOT">
                        <span>OSM HOT</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="chief_map_base" data-ff-map-base-toggle="Satellite">
                        <span>Satellite</span>
                    </label>
                </div>
                <div class="firefighter-mobile-map-panel__layers">
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Stations" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-building-shield" aria-hidden="true"></i></span>
                        <span>Stations</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Incidents" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-fire-flame-curved" aria-hidden="true"></i></span>
                        <span>Incidents</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Hydrants" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-droplet" aria-hidden="true"></i></span>
                        <span>Hydrants</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Risk & Hazard Reports" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span>
                        <span>Reports</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Other Markers" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                        <span>Other</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Risk Areas" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-map-location-dot" aria-hidden="true"></i></span>
                        <span>Risk areas</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Water Reference">
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-water" aria-hidden="true"></i></span>
                        <span>Water</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Shapes" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-draw-polygon" aria-hidden="true"></i></span>
                        <span>Boundaries</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                </div>
                <details class="firefighter-mobile-map-panel__legend">
                    <summary>
                        <span>Map legend</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </summary>
                    <div class="firefighter-mobile-map-panel__legend-list">
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="/station.png" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-building" aria-hidden="true" style="display:none;"></i></span>
                            <span>Fire station</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="/hall.png" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-users" aria-hidden="true" style="display:none;"></i></span>
                            <span>Barangay hall</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="/fire.png" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-fire-flame-curved" aria-hidden="true" style="display:none;"></i></span>
                            <span>Incident</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="/hydrant.png" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-droplet" aria-hidden="true" style="display:none;"></i></span>
                            <span>Hydrant</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch firefighter-mobile-map-panel__legend-swatch-other"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span>
                            <span>Risk &amp; hazard report</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch firefighter-mobile-map-panel__legend-swatch-other"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                            <span>Other marker</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-route" aria-hidden="true"></span>
                            <span>Route</span>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    `;
    document.body.appendChild(panel);
    return panel;
}

function bindResponderMobileMapLayersPanel() {
    const panel = document.querySelector('[data-ff-map-layers-panel]') || createResponderMobileMapLayersPanel();
    if (!panel || panel.dataset.pyroPanelBound === 'true') {
        return;
    }

    const openPanel = () => {
        if (window.innerWidth > 768) {
            return;
        }

        bindPyroSharedMapLayerInputs(panel);
        window.syncPyroSharedMapLayerControls?.();
        panel.removeAttribute('inert');
        panel.setAttribute('aria-hidden', 'false');
        panel.classList.add('is-open');
        window.setTimeout(() => window.map?.invalidateSize?.(), 80);
    };

    const closePanel = () => {
        if (panel.contains(document.activeElement)) {
            document.activeElement?.blur?.();
        }

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        panel.setAttribute('inert', '');
        window.setTimeout(() => window.map?.invalidateSize?.(), 80);
    };

    panel.querySelectorAll('[data-ff-map-layers-close]').forEach((button) => {
        button.addEventListener('click', closePanel);
    });

    panel.dataset.pyroPanelBound = 'true';
    window.openResponderMapLayersPanel = openPanel;
    window.closeResponderMapLayersPanel = closePanel;

    if (!window.openFirefighterMapLayersPanel) {
        window.openFirefighterMapLayersPanel = openPanel;
    }
}

function bindFirefighterMobileLayerControlTrigger() {
    const isResponderLayerContext = isFirefighterDashboardMapInstance() || isChiefCommandMapContext();
    if (!isResponderLayerContext || !routeOverlayControl) {
        return;
    }

    const control = routeOverlayControl.getContainer?.();
    const toggle = control?.querySelector('.leaflet-control-layers-toggle');
    const trigger = toggle || control;
    if (!control || !trigger || control.dataset.ffMobileTriggerBound === 'true') {
        return;
    }

    const openMobileLayers = (event) => {
        const opener = window.openResponderMapLayersPanel || window.openFirefighterMapLayersPanel;
        if (window.innerWidth > 768 || typeof opener !== 'function') {
            return;
        }

        event.__ffMapLayerControlHandled = true;
        event.preventDefault();
        event.stopImmediatePropagation?.();
        event.stopPropagation();
        control.classList.remove('leaflet-control-layers-expanded');
        window.closeFirefighterInsightsPanel?.();
        opener();
    };

    trigger.addEventListener('click', openMobileLayers, true);
    trigger.addEventListener('pointerdown', openMobileLayers, true);
    trigger.addEventListener('touchstart', openMobileLayers, { capture: true, passive: false });
    control.dataset.ffMobileTriggerBound = 'true';
}

// ============================================================================
// INITIALIZATION AFTER MAP IS READY
// ============================================================================

function initAfterMapReady() {
    console.log('Initializing after map is ready');
    
    stationFeatureGroup = window.stationFeatureGroup;
    incidentFeatureGroup = window.incidentFeatureGroup;
    hydrantFeatureGroup = window.hydrantFeatureGroup;
    otherFeatureGroup = window.otherFeatureGroup;
    
    initDetailsPanel();
    initEditModal();
    
    // Create route info card for ALL users
    if (!document.getElementById('routeInfoCard')) {
    createRouteInfoCard();
    console.log('Route info card created');
    console.log('User Role:', roleName, 'Role ID:', roleToken);
    }

    createFirefighterMapLegend();
    createChiefMapLegend();
    
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
        document.head.appendChild(faLink);
    }
    
    window.closeDetailsPanel = closeDetailsPanel;
    window.closeEditModal = closeEditModal;
    
    drawnItems = new L.FeatureGroup();
    window.map.addLayer(drawnItems);
    shapeFeatureGroup = L.featureGroup().addTo(window.map);
    // Kept as a detached compatibility group for older callers. Reported
    // risk/hazard evidence is displayed by riskHazardFeatureGroup as markers.
    fireRiskAreaLayerGroup = L.featureGroup();
    currentRiskAnalysisLayerGroup = L.featureGroup().addTo(window.map);
    nextMonthForecastLayerGroup = L.featureGroup().addTo(window.map);
    
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || window.activeMapInstanceKey || 'default';
    const isFirefighterDashboardMap = configuredInstanceKey === 'firefighterDashboard';
    const isChiefCommandMap = isChiefCommandMapContext();

    var cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap, &copy; CartoDB',
        subdomains: 'abcd',
        maxZoom: 19
    });
    var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    });
    var hotStreet = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors, Tiles style by Humanitarian OpenStreetMap Team'
    });
    var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 13, attribution: 'Tiles © Esri' });
    var waterReference = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Hydro_Reference/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 18,
        attribution: 'Hydro reference (c) Esri'
    });
    ((isFirefighterDashboardMap || isChiefCommandMap) ? cartoLight : street).addTo(window.map);
    window.pyroPwa?.attachMapOfflineNotice?.(window.map);

    console.log(roleToken);
    console.log('User Role:', roleName, 'Role ID:', roleToken);
    const isCitizenDashboardMap = isCitizenDashboardMapContext();
    const routeBaseMaps = (isFirefighterDashboardMap || isChiefCommandMap)
        ? {
            "Carto Light": cartoLight,
            "OSM Standard": street,
            "OSM HOT": hotStreet,
            "Satellite": satellite
        }
        : isCitizenDashboardMap
            ? {
                "Carto Light": cartoLight,
                "OSM": street,
                "Satellite": satellite
            }
        : {
            "Street Map": street,
            "Satellite": satellite
        };
    const routeOverlays = isFirefighterDashboardMap
        ? {
            "Fire Stations": stationFeatureGroup,
            "Incidents": incidentFeatureGroup,
            "Fire Hydrants": hydrantFeatureGroup,
            "Risk & Hazard Reports": riskHazardFeatureGroup,
            "Other Markers": otherFeatureGroup,
            "Current Risk Analysis": currentRiskAnalysisLayerGroup,
            "Next-Month Forecast": nextMonthForecastLayerGroup,
            "Water Reference": waterReference,
            "Shapes": shapeFeatureGroup
        }
        : isCitizenDashboardMap
            ? {
                "Fire Stations": stationFeatureGroup,
                "Incidents": incidentFeatureGroup,
                "Fire Hydrants": hydrantFeatureGroup,
                "Other Markers": otherFeatureGroup,
                "Water Reference": waterReference,
                "Shapes": shapeFeatureGroup
            }
        : isChiefCommandMap
            ? {
                "Fire Stations": stationFeatureGroup,
                "Incidents": incidentFeatureGroup,
                "Fire Hydrants": hydrantFeatureGroup,
                "Risk & Hazard Reports": riskHazardFeatureGroup,
                "Other Markers": otherFeatureGroup,
                "Current Risk Analysis": currentRiskAnalysisLayerGroup,
                "Next-Month Forecast": nextMonthForecastLayerGroup,
                "Water Reference": waterReference,
                "Shapes": shapeFeatureGroup
            }
        : {
            "Stations": stationFeatureGroup,
            "Incidents": incidentFeatureGroup,
            "Hydrants": hydrantFeatureGroup,
            "Risk & Hazard Reports": riskHazardFeatureGroup,
            "Other Markers": otherFeatureGroup,
            "Current Risk Analysis": currentRiskAnalysisLayerGroup,
            "Next-Month Forecast": nextMonthForecastLayerGroup,
            "Shapes": shapeFeatureGroup
        };
    routeOverlayControl = L.control.layers(routeBaseMaps, routeOverlays, { collapsed: true, position: 'topright' });
    routeOverlayControl.addTo(window.map);
    exposePyroSharedMapLayers(
        routeBaseMaps,
        routeOverlays,
        (isFirefighterDashboardMap || isChiefCommandMap) ? 'Carto Light' : (isCitizenDashboardMap ? 'Carto Light' : 'Street Map')
    );
    enhancePyroLayerControl();
    window.setTimeout(enhancePyroLayerControl, 100);
    window.setTimeout(enhancePyroLayerControl, 350);
    bindResponderMobileMapLayersPanel();
    bindFirefighterMobileLayerControlTrigger();
    window.setTimeout(bindResponderMobileMapLayersPanel, 100);
    window.setTimeout(bindFirefighterMobileLayerControlTrigger, 100);
    window.setTimeout(syncPyroSharedMapLayerControls, 150);

    window.map.on('baselayerchange overlayadd overlayremove', () => {
        syncPyroSharedMapLayerControls();
    });

    // Defense-in-depth: unauthorized roles (e.g. firefighter role 5) cannot see edit/delete controls.
    window.map.on('popupopen', (event) => {
        if (shouldUseResponderMobileDetailsUi() && event?.popup) {
            const content = event.popup.getContent?.();
            if (content && typeof openResponderMapPreviewSheet === 'function') {
                window.setTimeout(() => {
                    try {
                        window.map?.closePopup?.();
                    } catch (error) {
                        console.warn('Unable to close mobile Leaflet popup:', error);
                    }
                }, 0);
                openResponderMapPreviewSheet(content);
            }
        }

        if (canManageMarkers) return;
        document.querySelectorAll('[id^="edit"], [id^="delete"]').forEach((el) => el.remove());
    });
    
    if (canManageMarkers) {
        const attachShapeToggleButton = () => {
            const existingBtn = document.getElementById('toggleShapeMode');
            const isMobileViewport = window.innerWidth <= 768;
            const mobileTarget = document.querySelector('.leaflet-control-layers-overlays');
            const desktopTarget = document.querySelector('.leaflet-control-layers');
            const target = isMobileViewport && mobileTarget ? mobileTarget : desktopTarget;

            if (!target) {
                return;
            }

            const btn = existingBtn || document.createElement('button');
            btn.id = 'toggleShapeMode';
            btn.type = 'button';
            btn.innerHTML = manageShapesMode
                ? '<i class="fas fa-shapes mr-1"></i> Manage Shapes: ON'
                : '<i class="fas fa-shapes mr-1"></i> Manage Shapes: OFF';
            btn.classList.toggle('chief-map-shape-toggle--mobile', isMobileViewport);

            if (!btn.dataset.bound) {
                btn.addEventListener('click', toggleShapeManagement);
                btn.dataset.bound = 'true';
            }

            if (!existingBtn) {
                btn.style.cssText = 'margin: 8px 5px; padding: 10px 14px; background: #fff7f3; color: #9a3412; border: 1px solid rgba(198, 61, 38, 0.18); border-radius: 12px; cursor: pointer; font-size: 13px; font-weight: 800;';
            }

            target.appendChild(btn);
        };

        attachShapeToggleButton();
        window.addEventListener('resize', attachShapeToggleButton);

        if (boundaryEditorContext?.openEditor && !manageShapesMode) {
            toggleShapeManagement();
        }
        
        const drawControl = new L.Control.Draw({ 
            edit: { featureGroup: drawnItems, edit: true, remove: true }, 
            draw: { polyline: true, polygon: true, rectangle: true, circle: true, marker: true, circlemarker: false } 
        });
        window.map.addControl(drawControl);
        
        window.map.on(L.Draw.Event.DRAWSTART, () => { isDrawing = true; });
        window.map.on(L.Draw.Event.DRAWSTOP, () => { isDrawing = false; });
        window.map.on(L.Draw.Event.DRAWVERTEX, () => { isDrawing = true; });
        
        window.map.on(L.Draw.Event.CREATED, async function(event) {
            const layer = event.layer;
            const geojson = layer.toGeoJSON();
            const defaultShapeName = boundaryEditorContext?.barangayName
                ? `${boundaryEditorContext.barangayName} Boundary`
                : 'My Shape';

            let boundaryMeta = null;

            try {
                boundaryMeta = await openBoundarySaveModal({
                    defaultName: defaultShapeName,
                    context: boundaryEditorContext,
                });
            } catch (modalError) {
                console.error('Boundary save modal failed:', modalError);
                showNotification('Unable to open boundary save form.', 'error');
                return;
            }

            if (!boundaryMeta) {
                showNotification('Boundary save canceled.', 'info');
                return;
            }

            fetch('/api/shapes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: boundaryMeta.name,
                    geojson: JSON.stringify(geojson),
                    station_id: null,
                    barangay_id: boundaryMeta.barangay_id,
                    barangay_name: boundaryMeta.barangay_name,
                    boundary_type: 'barangay',
                    source: 'manual'
                })
            })
            .then(async res => {
                if (!res.ok) throw new Error(`Server error: ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (data.shape) {
                    cachedBoundaryBarangaysByCity.delete(boundaryMeta.city_id);
                    showNotification('Shape saved successfully!', 'success');
                    announceBoundaryChange(data.boundary_version);
                    refreshDynamicMapData({ reason: 'boundary-created' });
                }
            })
            .catch(err => {
                console.error('Error saving shape:', err);
                showNotification('Failed to save shape.', 'error');
            });
        });
        
        window.map.on('draw:edited', function(event) {
            const layers = event.layers;
            layers.eachLayer(function(layer) {
                if (layer.shapeId) {
                    fetch(`/api/shapes/${layer.shapeId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ geojson: layer.toGeoJSON() })
                    })
                    .then(res => res.json())
                    .then(data => {
                        showNotification('Shape updated successfully!', 'success');
                        announceBoundaryChange(data.boundary_version);
                        refreshDynamicMapData({ reason: 'boundary-edited' });
                    })
                    .catch(err => console.error('Error updating shape:', err));
                }
            });
        });
        
        window.map.on('draw:deleted', function(event) {
            const layers = event.layers;
            layers.eachLayer(function(layer) {
                if (layer.shapeId) {
                    fetch(`/api/shapes/${layer.shapeId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(data => {
                        showNotification('Shape deleted successfully!', 'success');
                        announceBoundaryChange(data.boundary_version);
                        refreshDynamicMapData({ reason: 'boundary-deleted' });
                    })
                    .catch(err => console.error('Error deleting shape:', err));
                }
            });
        });
    }
    
    if (isChiefDashboardMapInstance()) {
        window.setTimeout(() => {
            loadShapes({ force: true });
            setTimeout(focusBoundaryEditorContext, 350);
        }, 1800);
    } else {
        loadShapes({ force: true });
        setTimeout(focusBoundaryEditorContext, 350);
    }
    if (!isCitizenDashboardMap) {
        if (isChiefDashboardMapInstance()) {
            window.setTimeout(() => {
                loadFireRiskAreas();
            }, 2400);
        } else {
            loadFireRiskAreas();
        }
    }
    startMapLivePolling();
    
    if (canManageMarkers) {
        ensureManagedMapClickHandler();
    }
    
}

// ============================================================================
// Save marker button handler
// ============================================================================

function getMarkerSaveFormContext() {
    const categorySelect = document.getElementById('mCategory');
    const selectedOption = categorySelect?.options?.[categorySelect.selectedIndex] || null;
    const categoryId = categorySelect?.value || '';
    const categoryName = selectedOption?.getAttribute('data-category-name') || '';
    const categoryEnum = selectedOption?.getAttribute('data-category-enum') || getMarkerCategoryEnumValue(categoryId) || categoryId;

    return {
        title: document.getElementById('markerTitle')?.value?.trim() || '',
        description: document.getElementById('markerDescription')?.value?.trim() || '',
        categoryId,
        categoryName,
        categoryEnum,
        isIncidentMarker: categoryEnum === 'fire',
        isStationMarker: categoryEnum === 'station' || categoryName.toLowerCase() === 'station',
        isHydrantMarker: categoryEnum === 'hydrant' || categoryName.toLowerCase() === 'hydrant',
        isSchoolMarker: categoryEnum === 'school' || categoryName.toLowerCase() === 'school',
        isBarangayMarker: categoryEnum === 'barangay' || categoryName.toLowerCase() === 'barangay hall',
        reportedByUserIdInput: document.getElementById('reportedByUserId'),
        reportedBySearchInput: document.getElementById('reportedBySearch'),
        reportedByWalkInInput: document.getElementById('reportedByWalkIn'),
        walkInFirstNameInput: document.getElementById('walkInFirstName'),
        walkInLastNameInput: document.getElementById('walkInLastName'),
        walkInContactNumberInput: document.getElementById('walkInContactNumber'),
        defaultIncidentTeamIdInput: document.getElementById('defaultIncidentTeamId'),
    };
}

function validateMarkerSaveContext(context) {
    if (!selectedLat || !selectedLng) {
        return 'Tap the map first to choose where this marker should be placed.';
    }

    if (!context.categoryEnum) {
        return 'Choose a marker category before saving.';
    }

    if (context.isIncidentMarker) {
        if (!context.defaultIncidentTeamIdInput?.value) {
            return 'No default station team is configured for this account yet.';
        }

        if (context.reportedByWalkInInput?.checked) {
            if (!context.walkInFirstNameInput?.value?.trim() || !context.walkInLastNameInput?.value?.trim() || !context.walkInContactNumberInput?.value?.trim()) {
                return 'Complete the walk-in reporter details first.';
            }
        } else if (!context.reportedByUserIdInput?.value) {
            return 'Select the citizen who reported this incident.';
        }
    }

    if (context.isStationMarker) {
        const requiredStationFields = [
            document.getElementById('stationName')?.value,
            document.getElementById('stationCode')?.value,
            document.getElementById('stationContact')?.value,
            document.getElementById('stationEmail')?.value,
            document.getElementById('stationBarangay')?.value,
            document.getElementById('stationStreet')?.value,
        ];

        if (requiredStationFields.some(value => !String(value || '').trim())) {
            return 'Complete the station required fields first.';
        }
    }

    if (context.isHydrantMarker) {
        if (!document.getElementById('hydrantCode')?.value?.trim()) {
            return 'Enter the hydrant code first.';
        }

        if (!document.getElementById('hydrantStation')?.value) {
            return 'Choose the station this hydrant belongs to.';
        }
    }

    if (context.isSchoolMarker && !document.getElementById('schoolName')?.value?.trim()) {
        return 'Enter the school name first.';
    }

    if (context.isBarangayMarker) {
        if (!document.getElementById('barangayHallName')?.value?.trim() || !document.getElementById('barangayHallBarangay')?.value) {
            return 'Complete the barangay hall name and barangay first.';
        }
    }

    return null;
}

async function readJsonResponse(response, fallbackMessage) {
    const raw = await response.text();
    let parsed = null;

    if (raw) {
        try {
            parsed = JSON.parse(raw);
        } catch (parseError) {
            throw new Error(response.ok ? `${fallbackMessage}: invalid server response` : `${fallbackMessage} (HTTP ${response.status})`);
        }
    }

    if (!response.ok) {
        const validationMessage = parsed?.message
            || parsed?.error
            || Object.values(parsed?.errors || {}).flat().join(' ')
            || `${fallbackMessage} (HTTP ${response.status})`;

        throw new Error(validationMessage);
    }

    return parsed || {};
}

async function saveMarkerDetailsForCategory(markerId, context, jsonHeaders) {
    if (context.isIncidentMarker) {
        let reportedByUserId = context.reportedByUserIdInput?.value || '';

        if (context.reportedByWalkInInput?.checked) {
            const walkInResponse = await fetch('/station-admin/api/walk-in-citizen', {
                method: 'POST',
                headers: jsonHeaders,
                body: JSON.stringify({
                    first_name: context.walkInFirstNameInput?.value?.trim() || '',
                    last_name: context.walkInLastNameInput?.value?.trim() || '',
                    contact_number: context.walkInContactNumberInput?.value?.trim() || ''
                })
            });

            const walkInResult = await readJsonResponse(walkInResponse, 'Failed to save walk-in citizen');
            if (!walkInResult.success || !walkInResult.user_id) {
                throw new Error(walkInResult.message || 'Failed to save walk-in citizen');
            }

            reportedByUserId = walkInResult.user_id;
            if (context.reportedBySearchInput && walkInResult.citizen) {
                const citizenName = [walkInResult.citizen.first_name, walkInResult.citizen.middle_name, walkInResult.citizen.last_name].filter(Boolean).join(' ');
                context.reportedBySearchInput.value = `${walkInResult.citizen.account_code || 'CIT'} - ${citizenName} - ${walkInResult.citizen.contact_number || ''}`.trim();
            }
            if (context.reportedByUserIdInput) {
                context.reportedByUserIdInput.value = reportedByUserId;
            }
        }

        const incidentResponse = await fetch('/api/incidents', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                marker_id: markerId,
                status: 'reported',
                team_id: Number(context.defaultIncidentTeamIdInput.value),
                reported_by: Number(reportedByUserId)
            })
        });

        const incidentResult = await readJsonResponse(incidentResponse, 'Failed to create incident');
        if (!incidentResult.success) {
            throw new Error(incidentResult.message || 'Failed to create incident');
        }
        return;
    }

    if (context.isStationMarker) {
        const addressResponse = await fetch('/api/full-addresses', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                street: document.getElementById('stationStreet')?.value,
                barangay_id: document.getElementById('stationBarangay')?.value,
                postal_id: null
            })
        });
        const addressResult = await readJsonResponse(addressResponse, 'Failed to create station address');
        if (!addressResult.success) throw new Error(addressResult.message || 'Failed to create station address');

        const stationResponse = await fetch('/api/station', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                station_name: document.getElementById('stationName')?.value,
                station_code: document.getElementById('stationCode')?.value,
                contact_number: document.getElementById('stationContact')?.value,
                email: document.getElementById('stationEmail')?.value,
                address_id: addressResult.address_id,
                marker_id: markerId
            })
        });
        await readJsonResponse(stationResponse, 'Failed to create station');
        return;
    }

    if (context.isHydrantMarker) {
        const hydrantResponse = await fetch('/api/hydrant', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                hydrant_code: document.getElementById('hydrantCode')?.value,
                station_id: document.getElementById('hydrantStation')?.value,
                marker_id: markerId,
                hydrant_type_id: document.getElementById('hydrantType')?.value || null
            })
        });
        await readJsonResponse(hydrantResponse, 'Failed to create hydrant');
        return;
    }

    if (context.isSchoolMarker) {
        const schoolResponse = await fetch('/api/schools', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                school_name: document.getElementById('schoolName')?.value,
                marker_id: markerId
            })
        });
        await readJsonResponse(schoolResponse, 'Failed to create school marker details');
        return;
    }

    if (context.isBarangayMarker) {
        const hallStreet = document.getElementById('barangayHallStreet')?.value?.trim();
        let addressId = null;

        if (hallStreet) {
            const hallAddressResponse = await fetch('/api/full-addresses', {
                method: 'POST',
                headers: jsonHeaders,
                body: JSON.stringify({
                    street: hallStreet,
                    barangay_id: document.getElementById('barangayHallBarangay')?.value,
                    postal_id: null
                })
            });
            const hallAddressResult = await readJsonResponse(hallAddressResponse, 'Failed to create barangay hall address');
            if (!hallAddressResult.success) throw new Error(hallAddressResult.message || 'Failed to create barangay hall address');
            addressId = hallAddressResult.address_id;
        }

        const hallResponse = await fetch('/api/barangay-halls', {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                hall_name: document.getElementById('barangayHallName')?.value?.trim(),
                barangay_id: Number(document.getElementById('barangayHallBarangay')?.value),
                marker_id: markerId,
                address_id: addressId,
                contact_number: document.getElementById('barangayHallContact')?.value?.trim() || null,
                email: document.getElementById('barangayHallEmail')?.value?.trim() || null,
                captain_user_id: document.getElementById('barangayHallCaptain')?.value ? Number(document.getElementById('barangayHallCaptain')?.value) : null
            })
        });
        await readJsonResponse(hallResponse, 'Failed to create barangay hall');
    }
}

async function saveMarkerOptimisticallyFromForm(saveButton) {
    if (markerSaveInProgress) return;

    const context = getMarkerSaveFormContext();
    const validationMessage = validateMarkerSaveContext(context);

    if (validationMessage) {
        showMarkerFeedback(validationMessage, 'warning', 'Marker needs details');
        return;
    }

    markerSaveInProgress = true;
    const originalText = saveButton?.innerHTML;
    if (saveButton) {
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        saveButton.disabled = true;
    }

    const saveLat = selectedLat;
    const saveLng = selectedLng;
    const optimisticMarkerId = createOptimisticMapMarker(saveLat, saveLng, context.title || context.categoryName, context.categoryEnum);
    const jsonHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    };

    window.disableMarkerPlacementSelection?.({ clearCoordinates: true });
    window.closeModal?.({ skipPlacementCleanup: true });
    showMarkerFeedback('Marker is saving in the background.', 'info', 'Saving marker');

    try {
        const markerResponse = await fetch(getMarkerFeedEndpoint(), {
            method: 'POST',
            headers: jsonHeaders,
            body: JSON.stringify({
                title: context.title || context.categoryName || 'Map marker',
                description: context.description,
                lat: saveLat,
                lng: saveLng,
                category: context.categoryEnum,
                status: 'active'
            })
        });

        const markerResult = await readJsonResponse(markerResponse, 'Failed to create marker');
        const markerId = markerResult.marker?.marker_id || markerResult.data?.marker_id || markerResult.marker_id;
        if (!markerId) throw new Error('Failed to create marker');

        await saveMarkerDetailsForCategory(markerId, context, jsonHeaders);
        removeOptimisticMapMarker(optimisticMarkerId);
        invalidateMarkerReferenceData();
        await refreshDynamicMapData({ reason: 'marker-created' });
        showMarkerFeedback('Marker saved and added to the map.', 'success', 'Marker saved');
    } catch (error) {
        console.error('Error saving marker:', error);
        removeOptimisticMapMarker(optimisticMarkerId);
        window.removeMapPlacementMarker?.({ clearCoordinates: true });
        await fetchCoordinate({ force: true }).catch(() => {});
        showMarkerFeedback(error.message || 'Marker could not be saved.', 'error', 'Save failed');
    } finally {
        markerSaveInProgress = false;
        if (saveButton) {
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        }
    }
}

setTimeout(() => {
    const saveMarkerBtn = document.getElementById('saveMarkerBtn');
    if (saveMarkerBtn) {
        const newSaveBtn = saveMarkerBtn.cloneNode(true);
        saveMarkerBtn.parentNode.replaceChild(newSaveBtn, saveMarkerBtn);
        
        newSaveBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            await saveMarkerOptimisticallyFromForm(newSaveBtn);
            return;
            const titleInput = document.getElementById('markerTitle');
            const descriptionInput = document.getElementById('markerDescription');
            const categorySelect = document.getElementById('mCategory');
            const reportedByUserIdInput = document.getElementById('reportedByUserId');
            const reportedBySearchInput = document.getElementById('reportedBySearch');
            const reportedByWalkInInput = document.getElementById('reportedByWalkIn');
            const walkInFirstNameInput = document.getElementById('walkInFirstName');
            const walkInLastNameInput = document.getElementById('walkInLastName');
            const walkInContactNumberInput = document.getElementById('walkInContactNumber');
            const defaultIncidentTeamIdInput = document.getElementById('defaultIncidentTeamId');
            const title = titleInput ? titleInput.value : '';
            const description = descriptionInput ? descriptionInput.value : '';
            const categoryId = categorySelect ? categorySelect.value : '';
            
            if (!selectedLat || !selectedLng) { alert('Please click on the map to select a location first'); return; }
            // if (!title) { alert('Please enter marker title'); return; }
            // if (!categoryId) { alert('Please select a category'); return; }
            
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption?.getAttribute('data-category-name') || '';
            const categoryEnum = selectedOption?.getAttribute('data-category-enum') || getMarkerCategoryEnumValue(categoryId) || categoryId;
            const isIncidentMarker = categoryEnum === 'fire';
            const isWalkInReporter = Boolean(reportedByWalkInInput?.checked);

            if (isIncidentMarker) {
                if (!defaultIncidentTeamIdInput?.value) {
                    alert('No default station team is configured for this account yet.');
                    return;
                }

                if (isWalkInReporter) {
                    if (!walkInFirstNameInput?.value?.trim() || !walkInLastNameInput?.value?.trim() || !walkInContactNumberInput?.value?.trim()) {
                        alert('Please complete the walk-in reporter details');
                        return;
                    }
                } else if (!reportedByUserIdInput?.value) {
                    alert('Please select a citizen from the reported by field');
                    return;
                }
            }
            
            const originalText = newSaveBtn.innerHTML;
            newSaveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            newSaveBtn.disabled = true;

            const jsonHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            };

            const readJsonResponse = async (response, fallbackMessage) => {
                const raw = await response.text();
                let parsed = null;

                if (raw) {
                    try {
                        parsed = JSON.parse(raw);
                    } catch (parseError) {
                        if (!response.ok) {
                            throw new Error(`${fallbackMessage} (HTTP ${response.status})`);
                        }

                        throw new Error(`${fallbackMessage}: invalid server response`);
                    }
                }

                if (!response.ok) {
                    const validationMessage = parsed?.message
                        || parsed?.error
                        || Object.values(parsed?.errors || {}).flat().join(' ')
                        || `${fallbackMessage} (HTTP ${response.status})`;

                    throw new Error(validationMessage);
                }

                return parsed || {};
            };
            
            try {
                const markerResponse = await fetch(getMarkerFeedEndpoint(), {
                    method: 'POST',
                    headers: jsonHeaders,
                    body: JSON.stringify({ title, description, lat: selectedLat, lng: selectedLng, category: categoryEnum, status: 'active' })
                });
                const markerResult = await readJsonResponse(markerResponse, 'Failed to create marker');
                const markerId = markerResult.data?.marker_id || markerResult.marker_id;
                if (!markerId) throw new Error('Failed to create marker');

                if (isIncidentMarker) {
                    let reportedByUserId = reportedByUserIdInput?.value || '';

                    if (isWalkInReporter) {
                        const walkInResponse = await fetch('/station-admin/api/walk-in-citizen', {
                            method: 'POST',
                            headers: jsonHeaders,
                            body: JSON.stringify({
                                first_name: walkInFirstNameInput?.value?.trim() || '',
                                last_name: walkInLastNameInput?.value?.trim() || '',
                                contact_number: walkInContactNumberInput?.value?.trim() || ''
                            })
                        });

                        const walkInResult = await readJsonResponse(walkInResponse, 'Failed to save walk-in citizen');
                        if (!walkInResult.success || !walkInResult.user_id) {
                            throw new Error(walkInResult.message || 'Failed to save walk-in citizen');
                        }

                        reportedByUserId = walkInResult.user_id;

                        if (reportedBySearchInput && walkInResult.citizen) {
                            const citizenLabel = `${walkInResult.citizen.account_code || 'CIT'} · ${[walkInResult.citizen.first_name, walkInResult.citizen.middle_name, walkInResult.citizen.last_name].filter(Boolean).join(' ')} · ${walkInResult.citizen.contact_number || ''}`.trim();
                            reportedBySearchInput.value = citizenLabel;
                        }
                        if (reportedByUserIdInput) {
                            reportedByUserIdInput.value = reportedByUserId;
                        }
                    }

                    const incidentResponse = await fetch('/api/incidents', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            marker_id: markerId,
                            status: 'reported',
                            team_id: Number(defaultIncidentTeamIdInput.value),
                            reported_by: Number(reportedByUserId)
                        })
                    });

                    const incidentResult = await readJsonResponse(incidentResponse, 'Failed to create incident');
                    if (!incidentResult.success) {
                        throw new Error(incidentResult.message || 'Failed to create incident');
                    }
                }
                
                if (categoryName.toLowerCase() === 'station') {
                    const stationName = document.getElementById('stationName')?.value;
                    const stationCode = document.getElementById('stationCode')?.value;
                    const stationContact = document.getElementById('stationContact')?.value;
                    const stationEmail = document.getElementById('stationEmail')?.value;
                    const barangayId = document.getElementById('stationBarangay')?.value;
                    const street = document.getElementById('stationStreet')?.value;
                    
                    if (!stationName || !stationCode || !stationContact || !stationEmail || !barangayId || !street) {
                        alert('Please fill in all station required fields');
                        throw new Error('Missing station fields');
                    }
                    
                    const addressResponse = await fetch('/api/full-addresses', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ street, barangay_id: barangayId, postal_id: null })
                    });
                    const addressResult = await readJsonResponse(addressResponse, 'Failed to create address');
                    if (!addressResult.success) throw new Error(addressResult.message || 'Failed to create address');
                    
                    const stationResponse = await fetch('/api/station', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ station_name: stationName, station_code: stationCode, contact_number: stationContact, email: stationEmail, address_id: addressResult.address_id, marker_id: markerId })
                    });
                    await readJsonResponse(stationResponse, 'Failed to create station');
                } else if (categoryName.toLowerCase() === 'hydrant') {
                    const hydrantCode = document.getElementById('hydrantCode')?.value;
                    const hydrantStationId = document.getElementById('hydrantStation')?.value;
                    const hydrantTypeId = document.getElementById('hydrantType')?.value;
                    
                    if (!hydrantCode) { throw new Error('Please enter hydrant code'); }
                    if (!hydrantStationId) { throw new Error('Please select the station this hydrant belongs to'); }
                    
                    const hydrantResponse = await fetch('/api/hydrant', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ hydrant_code: hydrantCode, station_id: hydrantStationId, marker_id: markerId, hydrant_type_id: hydrantTypeId || null })
                    });
                    await readJsonResponse(hydrantResponse, 'Failed to create hydrant');
                } else if (categoryName.toLowerCase() === 'school') {
                    const schoolName = document.getElementById('schoolName')?.value;
                    if (!schoolName) { throw new Error('Please enter school name'); }
                    const schoolResponse = await fetch('/api/schools', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ school_name: schoolName, marker_id: markerId })
                    });
                    await readJsonResponse(schoolResponse, 'Failed to create school marker details');
                } else if (categoryEnum === 'barangay' || categoryName.toLowerCase() === 'barangay hall') {
                    const hallName = document.getElementById('barangayHallName')?.value?.trim();
                    const hallContact = document.getElementById('barangayHallContact')?.value?.trim();
                    const hallEmail = document.getElementById('barangayHallEmail')?.value?.trim();
                    const hallBarangayId = document.getElementById('barangayHallBarangay')?.value;
                    const hallStreet = document.getElementById('barangayHallStreet')?.value?.trim();
                    const hallCaptainOfficerId = document.getElementById('barangayHallCaptain')?.value;

                    if (!hallName || !hallBarangayId) {
                        throw new Error('Missing barangay hall fields');
                    }

                    let addressId = null;
                    if (hallStreet) {
                        const hallAddressResponse = await fetch('/api/full-addresses', {
                            method: 'POST',
                            headers: jsonHeaders,
                            body: JSON.stringify({ street: hallStreet, barangay_id: hallBarangayId, postal_id: null })
                        });
                        const hallAddressResult = await readJsonResponse(hallAddressResponse, 'Failed to create barangay hall address');
                        if (!hallAddressResult.success) {
                            throw new Error(hallAddressResult.message || 'Failed to create barangay hall address');
                        }
                        addressId = hallAddressResult.address_id;
                    }

                    const hallResponse = await fetch('/api/barangay-halls', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            hall_name: hallName,
                            barangay_id: Number(hallBarangayId),
                            marker_id: markerId,
                            address_id: addressId,
                            contact_number: hallContact || null,
                            email: hallEmail || null,
                            captain_user_id: hallCaptainOfficerId ? Number(hallCaptainOfficerId) : null
                        })
                    });
                    await readJsonResponse(hallResponse, 'Failed to create barangay hall');
                }
                
                alert('Marker saved successfully!');
                const modal = document.getElementById('markerModal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('is-open', 'active');
                    modal.setAttribute('aria-hidden', 'true');
                }
                invalidateMarkerReferenceData();
                await refreshDynamicMapData({ reason: 'marker-created' });
            } catch (error) {
                console.error('Error saving:', error);
                alert('Error: ' + error.message);
            } finally {
                newSaveBtn.innerHTML = originalText;
                newSaveBtn.disabled = false;
            }
        });
    }
}, 1000);

//incident
async function calculateRouteToIncident(incidentId, markerId = null, options = {}) {
    console.log('calculateRouteToIncident called with ID:', incidentId, 'marker:', markerId);
    
    //update natin ung incident to incident_status_id pag nag stop na ung truck to number 2 -responding
    
    const incidentPool = [
        ...(Array.isArray(window.incidentsData) ? window.incidentsData : []),
        ...(Array.isArray(window.incidentCoords) ? window.incidentCoords : []),
        ...(Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : []),
    ];
    const markerPool = [
        ...(Array.isArray(window.mapData?.marker) ? window.mapData.marker : []),
        ...(Array.isArray(window.allMarkers) ? window.allMarkers : []),
    ];

    console.log(window.incidentsData);

    let incident = null;
    if (incidentId !== null && incidentId !== undefined && incidentId !== '') {
        incident = incidentPool.find(inc => inc.incident_id == incidentId) || null;
    }

    if (!incident && markerId !== null && markerId !== undefined && markerId !== '') {
        incident = incidentPool.find(inc => inc.marker_id == markerId) || null;
    }

    if (!incident && markerId !== null && markerId !== undefined && markerId !== '') {
        incident = incidentPool.find(inc => inc.marker?.marker_id == markerId) || null;
    }

    const markerRecord = (markerId !== null && markerId !== undefined && markerId !== '')
        ? markerPool.find(marker => marker.marker_id == markerId) || null
        : null;

    console.log('Looking for incident with ID:', incidentId, 'marker:', markerId, 'Found:', incident, 'Marker:', markerRecord);
    if (!incident && !markerRecord) {
        console.error('Incident not found with ID:', incidentId, 'and marker ID:', markerId);
        showToast('Incident not found', 'error');
        return;
    }
    
    console.log('Found incident:', incident);
    
    // Get coordinates from incident marker
    // The incident may have marker data or direct lat/lng
    let lat, lng;
    
    if (incident?.marker) {
        lat = incident.marker.lat;
        lng = incident.marker.lng;
    } else if (incident?.lat && incident?.lng) {
        lat = incident.lat;
        lng = incident.lng;
    } else if (incident?.marker_lat && incident?.marker_lng) {
        lat = incident.marker_lat;
        lng = incident.marker_lng;
    } else if (markerRecord) {
        lat = markerRecord.lat;
        lng = markerRecord.lng;
    }
    
    if (!lat || !lng) {
        console.error('No coordinates for incident:', incident, 'marker:', markerRecord);
        showToast('Incident coordinates not available', 'error');
        return;
    }
    
    // Get incident type name for better display
    let incidentType = 'Fire';
    const incidentStatusId = Number(
        incident?.incident_status_id
        ?? (
            normalizeIncidentStatus(incident?.incident_status || incident?.status || markerRecord?.incident_status || markerRecord?.status || 'reported') === 'en-route' ? 2
            : normalizeIncidentStatus(incident?.incident_status || incident?.status || markerRecord?.incident_status || markerRecord?.status || 'reported') === 'on-scene' ? 3
            : normalizeIncidentStatus(incident?.incident_status || incident?.status || markerRecord?.incident_status || markerRecord?.status || 'reported') === 'controlled' ? 4
            : normalizeIncidentStatus(incident?.incident_status || incident?.status || markerRecord?.incident_status || markerRecord?.status || 'reported') === 'closed' ? 5
            : 1
        )
    );
    
    const resolvedIncidentId = incident?.incident_id ?? markerRecord?.incident_id ?? null;
    const existingIncidentRoute = getBlockingIncidentRouteForCurrentTeam(resolvedIncidentId);

    if (existingIncidentRoute) {
        showToast('Your team is already responding to another ongoing incident.', 'warning');
        return;
    }

    const destination = {
        lat: parseFloat(lat),
        lng: parseFloat(lng),
        title: `${incidentType} - ${incident?.title || markerRecord?.title || 'Emergency'}`,
        incidentStatusId,
        incidentId: resolvedIncidentId,
        routeSource: options?.source || 'real'
    };
    
    console.log('Calculating route to incident:', destination);
    const preferredSource = options?.source || 'real';
    const origin = await resolveRouteOrigin(preferredSource, destination);

    if (!origin?.ready) {
        showToast(preferredSource === 'demo'
            ? 'Demo route origin is not available right now.'
            : 'Getting your location... Please wait.', 'warning');
        return;
    }

    window.thisIncidents = resolvedIncidentId;
    console.log('thisincidents: ', window.thisIncidents);
    return await calculateRouteWithGeoapify(origin, destination);
}



window.closeModal = function(options = {}) {
    const modal = document.getElementById('markerModal');
    if (!modal) {
        return;
    }

    if (!options.skipPlacementCleanup) {
        window.disableMarkerPlacementSelection?.({
            clearCoordinates: options.keepCoordinates !== true,
        });
    }

    modal.style.display = 'none';
    modal.classList.remove('is-open', 'active');
    modal.setAttribute('aria-hidden', 'true');
};

window.openModal = function(options = {}) {
    if (options.fromMapClick) {
        window.disableMarkerPlacementSelection?.({ clearCoordinates: false });
    } else {
        window.removeMapPlacementMarker?.({ clearCoordinates: true });
        window.enableMarkerPlacementSelection?.();
    }

    const titleInput = document.getElementById('markerTitle');
    const descriptionInput = document.getElementById('markerDescription');
    const categorySelect = document.getElementById('mCategory');
    const stationFieldsDiv = document.getElementById('stationFields');
    const hydrantFieldsDiv = document.getElementById('hydrantFields');
    const schoolFieldsDiv = document.getElementById('schoolFields');
    const barangayHallFieldsDiv = document.getElementById('barangayHallFields');
    const incidentFieldsDiv = document.getElementById('incidentFields');
    const reportedByWalkInInput = document.getElementById('reportedByWalkIn');
    
    if (titleInput) titleInput.value = '';
    if (descriptionInput) descriptionInput.value = '';
    if (categorySelect) categorySelect.value = '';
    if (stationFieldsDiv) stationFieldsDiv.classList.remove('show');
    if (hydrantFieldsDiv) hydrantFieldsDiv.classList.remove('show');
    if (schoolFieldsDiv) schoolFieldsDiv.classList.remove('show');
    if (barangayHallFieldsDiv) barangayHallFieldsDiv.classList.remove('show');
    if (incidentFieldsDiv) incidentFieldsDiv.classList.remove('show');
    if (reportedByWalkInInput) reportedByWalkInInput.checked = false;
    
    const modal = document.getElementById('markerModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('is-open', 'active');
        modal.setAttribute('aria-hidden', 'false');
    }
};

const markerModalCleanupTarget = document.getElementById('markerModal');
if (markerModalCleanupTarget && !markerModalCleanupTarget.dataset.markerCleanupBound) {
    markerModalCleanupTarget.dataset.markerCleanupBound = 'true';

    markerModalCleanupTarget.addEventListener('click', (event) => {
        if (event.target === markerModalCleanupTarget) {
            window.closeModal?.();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (
            markerModalCleanupTarget.classList.contains('is-open') ||
            markerModalCleanupTarget.classList.contains('active') ||
            markerModalCleanupTarget.style.display !== 'none'
        ) {
            window.closeModal?.();
        }
    });
}

window.openAddCategory = function() {
    const modal = document.getElementById('categoryModal');
    if (modal) modal.style.display = 'flex';
};

window.closeAddCategory = function() {
    const modal = document.getElementById('categoryModal');
    if (modal) modal.style.display = 'none';
};

window.emergencyCall = function() {
    if (confirm('Call 911 for emergency services?')) window.location.href = 'tel:911';
};

window.openEditModal = function(marker) {
    openEditModal(marker);
};
window.openCurrentMapDetailEditor = openCurrentMapDetailEditor;
window.deleteCurrentMapDetailMarker = deleteCurrentMapDetailMarker;
window.respondCurrentMapDetail = respondCurrentMapDetail;
window.routeCurrentMapDetail = routeCurrentMapDetail;

// ============================================================================
// EXPOSE FUNCTIONS GLOBALLY FOR BLADE FILES
// ============================================================================

// Make sure these are available globally
window.calculateRouteToIncident = calculateRouteToIncident;
window.calculateDemoRouteToIncident = (incidentId, markerId = null) => calculateRouteToIncident(incidentId, markerId, { source: 'demo' });
window.calculateRouteToStation = calculateRouteToStation;
window.calculateRouteWithGeoapify = calculateRouteWithGeoapify;
window.displayAndStartRoute = displayAndStartRoute;
window.startSimulationForRoute = startSimulationForRoute;
window.stopSimulationForRoute = stopSimulationForRoute;
window.stopAllSimulations = stopAllSimulations;
window.promptRouteSourceSelection = promptRouteSourceSelection;
window.resolveRouteOrigin = resolveRouteOrigin;
window.updateRouteSourceBadge = updateRouteSourceBadge;
window.updateUserLocation = updateUserLocation;
window.getUserLocation = getUserLocation;
window.getAllUserLocations = getAllUserLocations;
window.displayAllFirefighterLocations = displayAllFirefighterLocations;
window.fetchAllFirefighterLocations = fetchAllFirefighterLocations;
window.sendUserLocationToServer = sendUserLocationToServer;

// Also expose the location variables
window.userLocations = window.userLocations || {};
window.userCoords = window.userCoords || { currentLatitude: null, currentLongitude: null, ready: false };
window.currentUserId = window.currentUserId || null;

// Expose helper functions
window.showToast = function(message, type = 'info') {
    return window.PyroNotify?.notify(message, type) ?? null;
};

console.log('Map.js fully loaded - All functions exposed globally');

