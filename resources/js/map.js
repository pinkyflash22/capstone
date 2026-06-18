import './bootstrap';
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
    loadedAt: 0
};

// Shape management variables
let manageShapesMode = false;
let selectedShapeForEdit = null;
let shapeFeatureGroup = null;

// Notification system
let notifications = [];
let notificationPanel = null;

// Details panel
let detailsPanel = null;
let currentDetailsMarker = null;

// Edit modal
let editModal = null;
let currentEditMarker = null;

let userLocation = null;
let currentIncident = null;
let watchId = null;
let updateInterval = null;
let incidentInterval = null;
let simulationInterval = null;

// Real-time updates
let realTimeLocationWatch = null;
let simulationSpeed = 1;
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
let lastMarkerStateSignature = null;

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
window.currentUserId = null; // Will be set from backend

window.userCoords = {
    currentLatitude: null,
    currentLongitude: null,
    ready: false
};


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
    
    try {
        const response = await fetch('/api/firefighter/location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                user_id: window.currentUserId,
                latitude: latitude,
                longitude: longitude,
                timestamp: new Date().toISOString()
            })
        });
        
        const result = await response.json();
        console.log('Location API response:', result);
        
        if (result.success) {
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
        const response = await fetch('/api/firefighter/locations', {
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

function shouldDisplayMarker(marker = {}) {
    const status = getMarkerStatusValue(marker);

    if (status === 'inactive') {
        return false;
    }

    return true;
}

function normalizeIncidentStatus(input = '') {
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
        borderColor: '#ffffff'
    };

    if (incidentStatus === 'controlled') {
        presentation.label = 'Investigation';
        presentation.iconClass = 'fa-magnifying-glass';
        presentation.bgColor = '#0f766e';
        return presentation;
    }

    if (incidentTypeLabel.includes('fire')) {
        presentation.bgColor = '#dc2626';
    }

    return presentation;
}

function createIncidentTypeIcon(marker = {}, extraClass = '') {
    const { iconClass, bgColor, borderColor } = getIncidentTypePresentation(marker);

    return L.divIcon({
        className: `custom-fa-icon incident-type-icon ${extraClass}`.trim(),
        html: `
            <div style="
                width: 34px;
                height: 34px;
                background: ${bgColor};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.24);
                border: 3px solid ${borderColor};
            ">
                <i class="fas ${iconClass}" style="
                    color: white;
                    font-size: 15px;
                    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.22));
                "></i>
            </div>
        `,
        iconSize: [34, 34],
        iconAnchor: [17, 17],
        popupAnchor: [0, -18]
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
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-route text-blue-600"></i>
                Active Routes
            </h3>
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

function createLegendItemHtml({ iconClass = null, iconHtml = null, bgColor, label, shape = 'circle' }) {
    return `
        <div class="firefighter-map-legend-item">
            <span class="firefighter-map-legend-icon ${shape === 'square' ? 'is-square' : 'is-circle'}" style="background:${bgColor};">
                ${iconHtml ? `<span style="display:inline-flex;width:16px;height:16px;align-items:center;justify-content:center;">${iconHtml}</span>` : (iconClass ? `<i class="fas ${iconClass}" style="font-size:12px;"></i>` : '')}
            </span>
            <span class="firefighter-map-legend-label">${label}</span>
        </div>
    `;
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
        <div class="firefighter-map-legend-title">Map Legend</div>
        <div class="firefighter-map-legend-items">
            ${createLegendItemHtml({ iconClass: 'fa-building', bgColor: '#2563eb', label: 'Fire Station' })}
            ${createLegendItemHtml({ bgColor: '#0f766e', label: 'Barangay Hall', iconHtml: BARANGAY_GROUP_ICON_SVG })}
            ${createLegendItemHtml({ iconClass: 'fa-fire-extinguisher', bgColor: '#ef4444', label: 'Hydrant', shape: 'square' })}
            ${createLegendItemHtml(getIncidentTypePresentation('Fire'))}
            ${createLegendItemHtml({ iconClass: 'fa-school', bgColor: '#eab308', label: 'School' })}
            ${createLegendItemHtml({ iconClass: 'fa-location-dot', bgColor: '#64748b', label: 'Other Marker' })}
        </div>
    `;
}

function createChiefMapLegend() {
    const legendContainer = document.getElementById('chiefMapLegend');
    if (!legendContainer) {
        return;
    }

    legendContainer.innerHTML = `
        <div class="chief-map-legend-title">Map Legend</div>
        <div class="chief-map-legend-items">
            <div class="chief-map-legend-item">
                <span class="chief-map-legend-icon" style="background:#2563eb;"><i class="fas fa-building" style="font-size:12px;"></i></span>
                <span class="chief-map-legend-label">Fire Station</span>
            </div>
            <div class="chief-map-legend-item">
                <span class="chief-map-legend-icon" style="background:#0f766e;"><span style="display:inline-flex;width:16px;height:16px;align-items:center;justify-content:center;">${BARANGAY_GROUP_ICON_SVG}</span></span>
                <span class="chief-map-legend-label">Barangay Hall</span>
            </div>
            <div class="chief-map-legend-item">
                <span class="chief-map-legend-icon is-square" style="background:#ef4444;"><i class="fas fa-fire-extinguisher" style="font-size:12px;"></i></span>
                <span class="chief-map-legend-label">Hydrant</span>
            </div>
            ${[
                getIncidentTypePresentation('Fire'),
              
            ].map(item => `
                <div class="chief-map-legend-item">
                    <span class="chief-map-legend-icon" style="background:${item.bgColor};"><i class="fas ${item.iconClass}" style="font-size:12px;"></i></span>
                    <span class="chief-map-legend-label">${item.label}</span>
                </div>
            `).join('')}
            <div class="chief-map-legend-item">
                <span class="chief-map-legend-icon" style="background:#64748b;"><i class="fas fa-location-dot" style="font-size:12px;"></i></span>
                <span class="chief-map-legend-label">Other Marker</span>
            </div>
        </div>
    `;
}

function updateRouteInfoCard() {
    const routesList = document.getElementById('activeRoutesList');
    if (!routesList) return;
    
    if (activeTrucks.size === 0) {
        routesList.innerHTML = '<div style="text-align: center; padding: 20px; color: #6b7280;">No active routes</div>';
        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard) routeCard.style.display = 'none';
        return;
    }
    
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
                        <i class="fa-solid fa-truck" style="color: ${color};"></i>
                        Truck ${index}
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
    const routeCard = document.getElementById('routeInfoCard');
    if (routeCard) routeCard.style.display = 'block';
}

function setAnimationSpeedGlobal(speed) {
    if (animationFrame && simulationPoints && simulationPoints.length > 0 && isSimulating) {
        startSmoothAnimation();
    }
}

// ============================================================================
// ROUTE DATABASE FUNCTIONS
// ============================================================================

async function saveRouteToDatabase(route, destination) {
    try {


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
                destination_title: destination.title,
                destination_lat: destination.lat,
                destination_lng: destination.lng,
                incident_id: window.thisIncidents ?? destination?.incidentId ?? null
            })
        });
        
        const result = await response.json();
        if (!response.ok || result.success === false) {
            console.error('Route save API error:', result);
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
        const response = await fetch('/api/route/active');
        if (!response.ok) return null;
        
        const routes = await response.json();
        const activeRouteData = routes.find(route => route.status === 'active' || route.status === 'moving');
        
        if (activeRouteData) {
            console.log('Found active route in database:', activeRouteData);
            return activeRouteData;
        }
        return null;
    } catch (error) {
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

document.addEventListener('DOMContentLoaded', function() {
    const configuredContainerId = window.mapInstanceConfig?.containerId || 'map';
    const configuredInstanceKey = window.mapInstanceConfig?.instanceKey || 'default';
    const mapContainer = document.getElementById(configuredContainerId);
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
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
    otherFeatureGroup = L.featureGroup().addTo(window.map);
    
    window.stationFeatureGroup = stationFeatureGroup;
    window.incidentFeatureGroup = incidentFeatureGroup;
    window.hydrantFeatureGroup = hydrantFeatureGroup;
    window.otherFeatureGroup = otherFeatureGroup;
    
    window.setName = setName;
    window.routesGroup = L.layerGroup().addTo(window.map);
    
    initAfterMapReady();
    fetchCoordinate({ preferSeeded: true, revalidate: true });
    
    // Ensure the map repaints correctly after layout settles.
    setTimeout(() => window.map?.invalidateSize(), 0);
    setTimeout(() => window.map?.invalidateSize(), 250);
    
    fetchStations();
    
    // Start polling for routes from database
    startRoutePolling();
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
const currentUserMetaId = String(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 'guest').trim() || 'guest';
const currentStationMetaId = String(document.querySelector('meta[name="station-id"]')?.getAttribute('content') || window.userStationId || '').trim();
window.thisToken = roleToken;

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
        .incident-alert-toast-stack {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 5600;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .incident-alert-toast {
            width: min(22rem, calc(100vw - 2rem));
            background: linear-gradient(135deg, #7f1d1d, #ea580c);
            color: #ffffff;
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
        }
        .incident-alert-toast-title {
            font-size: 0.85rem;
            font-weight: 800;
        }
        .incident-alert-toast-message {
            font-size: 0.75rem;
            line-height: 1.45;
            margin-top: 0.3rem;
            color: rgba(255, 255, 255, 0.9);
        }
    `;

    document.head.appendChild(style);
}

function ensureIncidentAlertToastStack() {
    let toastStack = document.getElementById('incidentAlertToastStack');
    if (toastStack) {
        return toastStack;
    }

    toastStack = document.createElement('div');
    toastStack.id = 'incidentAlertToastStack';
    toastStack.className = 'incident-alert-toast-stack';
    document.body.appendChild(toastStack);
    return toastStack;
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

    if (!notificationButton && !citizenNotificationFeed && !floatingToggle) {
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
    const notificationButton = document.getElementById('notificationBtn');
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
    const toastStack = ensureIncidentAlertToastStack();
    const toast = document.createElement('div');
    toast.className = 'incident-alert-toast';
    toast.innerHTML = `
        <div class="incident-alert-toast-title">${escapeHtml(entry.title)}</div>
        <div class="incident-alert-toast-message">${escapeHtml(entry.message)}</div>
    `;
    toastStack.prepend(toast);

    window.setTimeout(() => {
        toast.remove();
    }, 5500);
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

// ============================================================================
// HELPER FUNCTIONS FOR ICONS (keeping your existing icon functions)
// ============================================================================

function createFontAwesomeIcon(iconConfig, extraClass = '', customColor = null) {
    const bgColor = customColor || iconConfig.bgColor;
    const [width, height] = iconConfig.size;

    return L.divIcon({
        className: `custom-fa-icon ${extraClass}`,
        html: `
            <div style="
                width: ${width}px;
                height: ${height}px;
                background-color: ${bgColor};
                border-radius: ${iconConfig.id === 'hydrant' ? '4px 4px 8px 8px' : '50%'};
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3), 0 2px 4px -1px rgba(0,0,0,0.2);
                border: 2px solid white;
                transition: transform 0.2s;
            ">
                <i class="fas ${iconConfig.icon}" style="
                    color: white;
                    font-size: ${Math.min(width, height) * 0.6}px;
                    filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
                "></i>
            </div>
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
        iconAnchor: iconConfig.anchor,
        popupAnchor: [0, -height / 2]
    });
}   
function getIconById(iconId, extraClass = '') {
    const iconConfig = ICON_ARRAY.find(icon => icon.id === iconId);
    if (!iconConfig) {
        console.warn(`Icon with id '${iconId}' not found, using default`);
        return getDefaultIcon();
    }

    return createFontAwesomeIcon(iconConfig, extraClass);
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
        return getIconById(iconId, extraClass);
    }
    return getDefaultIcon();
}

function getDefaultIcon() {
    return L.divIcon({
        className: 'default-icon',
        html: '<div style="width: 24px; height: 24px; background-color: #6b7280; border-radius: 50%; border: 2px solid white;"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
}

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
    
    .speed-control {
        margin-top: 8px;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .speed-btn {
        padding: 4px 8px;
        font-size: 11px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .speed-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
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
        z-index: 10001;
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
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(styleElement);

// ============================================================================
// NOTIFICATION SYSTEM (keeping your existing functions)
// ============================================================================

function initNotificationSystem() {
    if (!document.getElementById('notificationContainer')) {
        notificationPanel = document.createElement('div');
        notificationPanel.id = 'notificationContainer';
        notificationPanel.className = 'notification-container';
        document.body.appendChild(notificationPanel);
    } else {
        notificationPanel = document.getElementById('notificationContainer');
    }
}

function showNotification(message, type = 'info', title = 'Notification') {
    if (!notificationPanel) {
        initNotificationSystem();
    }
    
    const id = 'notif_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const icons = {
        success: '<i class="fas fa-check-circle" style="color: #10b981;"></i>',
        warning: '<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>',
        error: '<i class="fas fa-times-circle" style="color: #ef4444;"></i>',
        info: '<i class="fas fa-info-circle" style="color: #3b82f6;"></i>'
    };
    
    const titles = {
        success: 'Success',
        warning: 'Warning',
        error: 'Error',
        info: 'Information'
    };
    
    const notification = document.createElement('div');
    notification.id = id;
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-header">
            <div class="notification-title">
                ${icons[type] || icons.info}
                <span>${title || titles[type]}</span>
            </div>
            <div class="notification-close" onclick="document.getElementById('${id}').remove()">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="notification-content">
            ${message}
        </div>
    `;
    
    notificationPanel.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('expanded');
    }, 100);
    
    setTimeout(() => {
        if (document.getElementById(id)) {
            notification.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => {
                if (document.getElementById(id)) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
    
    const header = notification.querySelector('.notification-header');
    header.addEventListener('click', (e) => {
        if (!e.target.classList.contains('notification-close') && !e.target.closest('.notification-close')) {
            notification.classList.toggle('expanded');
        }
    });
    
    notifications.push({
        id: id,
        element: notification,
        message: message,
        type: type,
        timestamp: Date.now()
    });
    
    return id;
}

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
        editModal.innerHTML = `
            <div class="edit-modal-content">
                <div class="edit-modal-header">
                    <h3><i class="fas fa-edit"></i> Edit Marker</h3>
                    <div class="edit-modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></div>
                </div>
                <div id="editModalContent"></div>
            </div>
        `;
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
    
    const saveBtn = document.getElementById('editSaveBtn');
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
    
    const title = titleInput ? titleInput.value.trim() : '';
    const description = descriptionInput ? descriptionInput.value : '';
    const categoryId = categorySelect ? categorySelect.value : '';
    
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

        await fetchCoordinate();
        
    } catch (error) {
        console.error('Error updating marker:', error);
        alert('Error updating marker: ' + error.message);
    } finally {
        closeModal();
    }
}

function closeEditModal() {
    if (editModal) {
        editModal.classList.remove('active');
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
        currentDetailsMarker = null;
    }
}

// ============================================================================
// FUNCTION DEFINITIONS
// ============================================================================

export function getName(lat, lon) {
    let popupmarker = L.popup()
        .setLatLng([lat, lon])
        .setContent("Loading address...")
        .openOn(window.map);

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
        if (!data.features || data.features.length === 0) return;
        const address = data.features[0].properties.formatted;
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
                openModal();
            });
        }
        popupmarker.on('popupclose', function() {
            window.map.removeLayer(popupmarker);
            popupmarker = null;
        });
    })
    .catch(err => console.error(err));
}

function clearAllMarkers() {
    try {
        const groups = [stationFeatureGroup, incidentFeatureGroup, hydrantFeatureGroup, otherFeatureGroup].filter(Boolean);

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

    if (Array.isArray(coordinates[0]) && coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
        coordinates = coordinates[0];
    }

    const latlngs = [];
    for (let i = 0; i < coordinates.length; i++) {
        const coord = coordinates[i];
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
    const { preferSeeded = false, revalidate = true } = options;

    try {
        const seededMarkers = window.mapData?.marker;
        if (preferSeeded && Array.isArray(seededMarkers) && seededMarkers.length > 0) {
            // Paint markers immediately for fast first render.
            renderMarkersQuick(seededMarkers);

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

        const response = await fetch('/api/markers');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        let coordinates;
        if (result.data && Array.isArray(result.data)) {
            coordinates = result.data;
        } else if (Array.isArray(result)) {
            coordinates = result;
        } else {
            showNotification('Failed to load markers: Invalid response format', 'error');
            return;
        }
        if (!coordinates || coordinates.length === 0) return;
        await updateCoordinate(coordinates, { fetchSupplemental: true });
    } catch (error) {
        console.error('Fetch error in fetchCoordinate:', error);
        showNotification('Failed to fetch markers: ' + error.message, 'error');
    }
}

function getIncidentVisibilityState(marker, incidents) {
    if (!marker || !isIncidentMarker(marker)) {
        return { visible: true, incident: null };
    }

    if (!shouldDisplayMarker(marker)) {
        return { visible: false, incident: null };
    }

    const linkedIncident = incidents.find((incident) => Number(incident.marker_id) === Number(marker.marker_id))
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

    return normalizedCoordinates
        .map((marker) => {
            const visibility = getIncidentVisibilityState(marker, incidents);
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
    try {
        const [markersResponse, incidentsResponse] = await Promise.all([
            fetch('/api/markers', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }),
            fetch('/api/incident/latest', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
        ]);

        if (!markersResponse.ok) {
            throw new Error(`Failed to poll markers: ${markersResponse.status}`);
        }

        const markersPayload = await markersResponse.json();
        const incidentsPayload = incidentsResponse.ok
            ? await incidentsResponse.json()
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
        console.error('Marker state polling failed:', error);
    }
}

function normalizeCoordinatePayload(data) {
    if (data?.data && Array.isArray(data.data)) return data.data;
    if (Array.isArray(data)) return data;
    if (data && typeof data === 'object') return [data];
    return [];
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

        const newMarker = L.marker([lat, lng], { icon: tempIcon }).bindPopup(popupContent, {
            minWidth: 100,
            maxWidth: 260
        });

        addMarkerToFeatureGroup(marker, newMarker);
        existingMarkers[marker.marker_id] = newMarker;
    }
}

async function getMarkerReferenceData() {
    const shouldRefreshCache = !markerReferenceCache.loadedAt || (Date.now() - markerReferenceCache.loadedAt) > 60000;

    if (!shouldRefreshCache &&
        markerReferenceCache.incidentTypes &&
        markerReferenceCache.hydrants &&
        markerReferenceCache.schools &&
        markerReferenceCache.stations) {
        return markerReferenceCache;
    }

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

    markerReferenceCache = {
        incidentTypes,
        hydrants,
        schools,
        stations: stationsData,
        loadedAt: Date.now()
    };

    return markerReferenceCache;
}

async function updateCoordinate(data, options = {}) {
    const { fetchSupplemental = true, incidents: preloadedIncidents = null } = options;

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
                fetch('/api/incident/latest'),
                getMarkerReferenceData()
            ]);

            if (incidentResponseResult.status === 'fulfilled') {
                const incidentResponse = incidentResponseResult.value;
                if (incidentResponse.ok) {
                    const incidentData = await incidentResponse.json();
                    incidents = incidentData.data || incidentData;
                }
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
            return;
        }
        
        const hydrants = markerReferences.hydrants || [];
        const schools = markerReferences.schools || [];
        const stations = markerReferences.stations || [];
        
        const markersToProcess = [];
        
        for (const item of coordinates) {
            if (item.marker && item.marker.lat && item.marker.lng) {
                if (!shouldDisplayMarker(item.marker)) {
                    continue;
                }
                const visibility = getIncidentVisibilityState(item.marker, incidents);
                if (!visibility.visible) {
                    continue;
                }
                markersToProcess.push({ ...item.marker, incident_id: item.incident_id, incident_status: item.incident_status_id });
            } else if (item.lat && item.lng) {
                if (!shouldDisplayMarker(item)) {
                    continue;
                }
                if (isStationMarker(item)) {
                    const station = stations.find(s => s.marker_id == item.marker_id);
                    if (station) item.station = station;
                } else if (isIncidentMarker(item)) {
                    const visibility = getIncidentVisibilityState(item, incidents);
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
                    const hydrant = hydrants.find(h => h.marker_id == item.marker_id);
                    if (hydrant) {
                        item.hydrant = hydrant;
                        if (hydrant.station_id) {
                            const station = stations.find(s => s.station_id == hydrant.station_id);
                            if (station) item.hydrant.station = station;
                        }
                    }
                } else if (isSchoolMarker(item)) {
                    const school = schools.find(s => s.marker_id == item.marker_id);
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
            
            const popupContent = generatePopupContent({ marker: marker }, incidents);
            const tempIcon = getIconByCategoryId(marker.markerCategory_id, '', marker);
            const popupMaxWidth = window.innerWidth <= 768 ? 264 : 300;
            const newMarker = L.marker([lat, lng], { icon: tempIcon }).bindPopup(popupContent, { minWidth: 100, maxWidth: popupMaxWidth });
            
            if (isIncidentMarker(marker)) {
                const circle = L.circle([lat, lng], { radius: 200, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2 });
                incidentFeatureGroup.addLayer(circle);
            }
            
            addMarkerToFeatureGroup(marker, newMarker);
            newMarker.on('popupopen', () => addPopupEventListeners({ marker: marker, image: marker.multimedia || null, created_at: marker.created_at }));
            existingMarkers[marker.marker_id] = newMarker;
        });

        lastMarkerStateSignature = buildMarkerStateSignature(coordinates, incidents);
        
    } catch (error) {
        console.error('Error updating coordinates:', error);
        showNotification('Error updating markers: ' + error.message, 'error');
    }
}

async function fetchStations() {
    try {
        const response = await fetch('/api/station', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch stations');
        
        const data = await response.json();
        const stationsData = data.data || data;
        
        // Normalize station data
        stations = stationsData.map(station => ({
            id: station.station_id || station.id,
            station_id: station.station_id || station.id,
            station_name: station.station_name,
            lat: station.marker?.lat || station.lat,
            lng: station.marker?.lng || station.lng,
            marker_id: station.marker_id,
            marker: station.marker
        }));
        
        console.log('Stations loaded:', stations.length);
    } catch (error) {
        console.error('Error fetching stations:', error);
        stations = [];
    }
}

function generatePopupContent(coord, incidents) {
    const marker = coord.marker || coord;
    const incident = incidents.find(inc => inc.marker_id === marker.marker_id);
    const imagePath = marker.multimedia?.path ? '/storage/' + marker.multimedia.path : (coord.image?.path ? '/storage/' + coord.image.path : null);
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

                <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToIncident(${incident?.incident_id ?? marker?.incident_id ?? 'null'}, ${marker.marker_id})" style="width: 100%; background: #10b981; color: white; border: none; padding: ${buttonPadding}; border-radius: 8px; font-size: ${buttonFontSize}; font-weight: 500; cursor: pointer; margin-bottom: 8px;">
                    <i class="fa-solid fa-route"></i> Get Directions to Incident
                </button>
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

async function calculateRouteToStation(stationId) {
    await fetchStations();
    console.log('trying...', stations);
    console.log('station id...', stationId);
    
    // Find station by marker_id
    const station = stations.find(s => s.marker.marker_id == stationId);
    console.log('Found station:', station);
    
    if (!station || !window.userCoords.ready) return;
    
    const destination = {
        lat: parseFloat(station.lat),
        lng: parseFloat(station.lng),
        title: station.station_name || 'Fire Station'
    };
    console.log('calculating route to station:', destination);
    const origin = window.userCoords;
    window.thisIncidents = null;
    await calculateRouteWithGeoapify(origin, destination);
}

async function calculateRouteWithGeoapify(origin, destination) {
    if (!origin || !destination || !window.geoapifyKey) {
        console.error('Missing origin, destination, or API key');
        return;
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
        
        if (data.features && data.features.length > 0) {
            const route = data.features[0];
            window.activeRoute = route;

            if (window.thisIncidents) {
                // SAVE TO DATABASE FOR ALL USERS TO SEE
                const savedRoute = await saveRouteToDatabase(route, destination);
                console.log("displaying route data1: ", savedRoute);
                
                // Add to multi-truck system
                if (savedRoute && savedRoute.data) {    
                    const routeId = savedRoute.data.route_id;
                    const matchedIncident = window.incidentsData?.find(inc => inc.incident_id == window.thisIncidents);
                    const persistedRoute = {
                        ...savedRoute.data,
                        route_team_id: matchedIncident?.team_id ?? null,
                        route_station_id: savedRoute.data.route_station_id ?? null
                    };
                    await displayAndStartRoute(routeId, persistedRoute, destination);
                    console.log("displaying route data2: ", persistedRoute);
                } else {
                    // Fallback: still show route to the current user if DB persistence fails.
                    console.warn('Route persistence failed; rendering local route fallback.');
                    displayGeoapifyRoute(route);
                    updateRouteInfoFromGeoapify(route);
                }
            } else {
                console.log('No incident_id for route persistence, displaying station route locally');
                displayGeoapifyRoute(route);
                updateRouteInfoFromGeoapify(route);
            }
            
            console.log("displaying route data3: ", route);
            
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'block';
            
            window.routeDestination = destination;
        }
        
    } catch (error) {
        console.error('Geoapify route calculation error:', error);
        console.log('error route data:', error);
    }
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
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
    if (routeLayer) {
        window.map.removeLayer(routeLayer);
    }
    
    const latlngs = extractRouteLatLngs(route);
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found in route');
        console.log('Coordinates structure:', JSON.stringify(coordinates).substring(0, 500));
        showToast('Invalid route data', 'error');
        return;
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
        if (truck.incidentId) {
            await updateIncidentStatusFromRoute(routeId, 3);
        }
        await updateRouteStatus(routeId, 'inactive', truck.currentPointIndex, finalLat, finalLng, progress);
    } catch (error) {
        truck.arrivalHandled = false;
        truck.onSceneStatusSent = false;
        truck.routeStatusSyncInFlight = false;
        console.error('Failed to finalize truck arrival:', error);
        showToast(error.message || 'Failed to update incident to On Scene', 'error');
        return;
    } finally {
        if (truck) {
            truck.routeStatusSyncInFlight = false;
        }
    }

    await stopSimulationForRoute(routeId, false, false);
    showToast(`Truck ${routeId} reached destination`, 'success');
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
    
    if (distanceEl) distanceEl.textContent = `${distance.toFixed(1)} km`;
    if (durationEl) durationEl.textContent = durationText;
    
    if (etaEl) {
        const eta = new Date(Date.now() + (time * 60 * 1000));
        etaEl.textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    if (destinationTitle && route.destination_title) {
        destinationTitle.textContent = route.destination_title;
    } else if (destinationTitle && routeDestination) {
        destinationTitle.textContent = routeDestination.title || 'Destination';
    }
    
    const routeCard = document.getElementById('routeInfoCard');
    if (routeCard) {
        routeCard.style.display = 'block';
    }
    
    // Add progress bar if not exists
    if (routeCard && !document.getElementById('routeProgress')) {
        const progressHTML = `
            <div class="route-progress-container" style="margin-top: 12px; background: #e5e7eb; border-radius: 10px; overflow: hidden; height: 6px;">
                <div id="routeProgress" class="route-progress-bar" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.3s ease;"></div>
            </div>
            <div class="speed-control" style="margin-top: 12px; display: flex; gap: 8px;">
                <button class="speed-btn" data-speed="slow" style="padding: 6px 12px; font-size: 12px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">🐢 Slow</button>
                <button class="speed-btn active" data-speed="normal" style="padding: 6px 12px; font-size: 12px; background: #3b82f6; color: white; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer;">🚒 Normal</button>
                <button class="speed-btn" data-speed="fast" style="padding: 6px 12px; font-size: 12px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">🐇 Fast</button>
            </div>
        `;
        routeCard.insertAdjacentHTML('beforeend', progressHTML);
        
        document.querySelectorAll('.speed-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.speed-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = '#f3f4f6';
                    b.style.color = '#374151';
                });
                btn.classList.add('active');
                btn.style.background = '#3b82f6';
                btn.style.color = 'white';
                const speed = btn.dataset.speed;
                setAnimationSpeedGlobal(speed);
            });
        });
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
        return;
    }
    
    // Extract coordinates (same as before)
    const latlngs = extractRouteLatLngs(route);
    
    if (latlngs.length === 0) {
        console.error('No valid coordinates found after parsing');
        showToast('Invalid route coordinates', 'error');
        return;
    }
    
    console.log(`Route ${routeId} has ${latlngs.length} points`);

    clearExistingRouteRendering(routeId);
    const routeGroup = getRouteStationLayer(route);
    
    const truckData = {
        routeId: routeId,
        simulationPoints: latlngs,
        originalSimulationPoints: [...latlngs],
        currentPointIndex: 0,
        isSimulating: false,
        routeDestination: destination,
        incidentId: route.incident_id || null,
        incidentStatusId: Number(route.incident_status_id ?? destination?.incidentStatusId ?? 1),
        routeStationId: route.route_station_id || null,
        routeTeamId: route.route_team_id || null,
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
        statusChangedByRoute: false
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
        // Only transition to responding when this route starts from a reported incident.
        if (truck.incidentId && Number(truck.incidentStatusId) === 1) {
            await updateIncidentStatusFromRoute(routeId, 2);
            truck.incidentStatusId = 2;
            truck.statusChangedByRoute = true;
        }

        // Keep the route record in sync with the truck while it is moving.
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
        icon: createFireTruckIcon(truckColors[colorIndex]),
        zIndexOffset: 1000
    }));
    bindTruckDetailsPopup(routeId, truck.animatedVehicle, truck);
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
    
    startSmoothAnimationForTruck(routeId);
    startRealTimeLocationUpdatesForTruck(routeId);
    showToast(`Movement started for truck ${routeId}!`, 'success');
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
            showToast(`Truck ${routeId} arrived at destination!`, 'success');
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
        if (!currentTruck.animatedVehicle) {
            console.log('Truck vehicle missing, stopping animation');
            return;
        }
        
        const elapsed = (currentTime - startTime) / 1000;
        let progress = Math.min(elapsed / totalRemainingDuration, 1);
        
        if (progress >= 1) {
            console.log(`Truck ${routeId} arrived at destination!`);
            currentTruck.currentPointIndex = currentTruck.simulationPoints.length - 1;
            finalizeTruckArrival(routeId).then(() => {
                showToast(`Truck ${routeId} arrived at destination!`, 'success');
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
    if (truck.realTimeLocationWatch) clearInterval(truck.realTimeLocationWatch);
    
    truck.realTimeLocationWatch = setInterval(() => {
        if (truck.isSimulating && truck.animatedVehicle) {
            const position = truck.animatedVehicle.getLatLng();
            if (position) {
                const locationDisplay = document.getElementById('userLocation');
                if (locationDisplay) {
                    locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Truck ${routeId}: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)} (Moving)`;
                }
            }
        }
    }, 500);
}

async function stopSimulationForRoute(routeId, syncWithServer = true, showStoppedToast = true) {
    const truck = activeTrucks.get(routeId);
    if (!truck) return;
    
    if (truck.animationFrame) cancelAnimationFrame(truck.animationFrame);
    if (truck.realTimeLocationWatch) clearInterval(truck.realTimeLocationWatch);
    unregisterRouteRendering(routeId);
    
    truck.isSimulating = false;
    if (syncWithServer) {
        try {
            await updateRouteStatus(routeId, 'inactive');

            const shouldReturnToReported = truck.incidentId
                && !truck.arrivalHandled
                && truck.statusChangedByRoute
                && Number(truck.lastSentIncidentStatus) === 2;

            if (shouldReturnToReported) {
                await updateIncidentStatusFromRoute(routeId, 1);
                truck.incidentStatusId = 1;
                truck.statusChangedByRoute = false;
            }
        } catch (error) {
            console.error(`Failed to stop simulation for route ${routeId}:`, error);
            showToast(error.message || 'Failed to stop route simulation cleanly', 'error');
            return;
        }
    }
    activeTrucks.delete(routeId);
    

    updateRouteInfoCard();
    if (showStoppedToast) {
        showToast(`Truck ${routeId} simulation stopped`, 'info');
    }
}

function stopAllSimulations() {
    for (const [routeId] of activeTrucks) {
        stopSimulationForRoute(routeId);
    }
}

function createFireTruckIcon(color = '#dc2626') {
    const dataUrl = '/storage/defaults/firetruck.png';
    return L.icon({
        iconUrl: dataUrl,
        iconSize: [48, 48],
        iconAnchor: [24, 24],
        popupAnchor: [0, -24],
        className: 'fire-truck-icon'
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

function startRoutePolling() {
    if (routePollingInterval) clearInterval(routePollingInterval);

    const pollRoutes = async () => {
        try {
            const response = await fetch('/api/route/active');
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
                    },
                    incidentId: routeData.incident_id || null,
                    routeStationId: routeData.route_station_id || null,
                    routeTeamId: routeData.route_team_id || null,
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
                    lastSentIncidentStatus: null
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
            console.error('Error polling routes:', error);
        }
    };

    pollRoutes();
    
    // Poll every 1 second instead of 3 seconds for real-time tracking
    routePollingInterval = setInterval(pollRoutes, 1000);
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
        icon: createFireTruckIcon(truckColors[colorIndex]),
        zIndexOffset: 1000
    }));
    bindTruckDetailsPopup(routeId, truck.animatedVehicle, truck);
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
    
    // Start animation from current position
    startSmoothAnimationForTruck(routeId);
    startRealTimeLocationUpdatesForTruck(routeId);
    
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
                const imagePath = coord.image?.path ? '/storage/' + coord.image.path : (marker.multimedia?.path ? '/storage/' + marker.multimedia.path : null);
                const markerData = { ...marker, category: getCategoryName(marker), created_at: coord.created_at || marker.created_at };
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
        default: return null;
    }
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
            fetchCoordinate();
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
        const markerRes = await fetch('/api/markers', {
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
        await fetchCoordinate();
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
                <div style="display:flex; gap:10px; margin-top: 12px;">
                    <button
                        id="shapeEditBtn_${shapeId}"
                        type="button"
                        style="flex:1; height:40px; border:0; border-radius:12px; background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color:#fff; font-size:12px; font-weight:800; cursor:pointer;"
                    >
                        <i class="fas fa-pen-to-square" style="margin-right:6px;"></i>Save
                    </button>
                    <button
                        id="shapeDeleteBtn_${shapeId}"
                        type="button"
                        style="flex:1; height:40px; border:1px solid rgba(198, 61, 38, 0.14); border-radius:12px; background:#fff7f3; color:#9a3412; font-size:12px; font-weight:800; cursor:pointer;"
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
        const deleteBtn = document.getElementById(`shapeDeleteBtn_${shapeId}`);

        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                editShape(selectedShapeForEdit);
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
    })
    .catch(err => {
        console.error('Error deleting shape:', err);
        showNotification('Failed to delete shape: ' + err.message, 'error');
    });
}

async function loadShapes() {
    try {
        if (shapeFeatureGroup) shapeFeatureGroup.clearLayers();
        shapeLayers = {};
        const response = await fetch('/api/shapes', { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const shapes = await response.json();
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
                layer.on('click', function(e) { if (manageShapesMode) { L.DomEvent.stopPropagation(e); onShapeClick(shape.id, layer, e.latlng); } });
                if (shapeFeatureGroup) shapeFeatureGroup.addLayer(layer);
                shapeLayers[shape.id] = layer;
                layer.bindTooltip(shape.name || "Shape", { permanent: false, direction: "top", sticky: true, opacity: 0.9 });
            } catch (e) { console.error('Error creating layer for shape:', e); }
        });
    } catch (error) {
        console.error('Error loading shapes:', error);
        showNotification('Error loading shapes', 'error');
    }
}

function showLoadingModal(message) {
    if (document.getElementById('loadingModal')) return;
    const modal = document.createElement('div');
    modal.id = 'loadingModal';
    modal.style.cssText = `position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 10000;`;
    modal.innerHTML = `<div style="background: white; padding: 30px; border-radius: 12px; text-align: center;"><i class="fas fa-circle-notch fa-spin" style="font-size: 40px; color: #3b82f6;"></i><p style="margin-top: 15px;">${message}</p></div>`;
    document.body.appendChild(modal);
}

function closeModal() {
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) document.body.removeChild(loadingModal);
    const successModal = document.getElementById('successModal');
    if (successModal) document.body.removeChild(successModal);
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
    
    initNotificationSystem();
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
    
    var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(window.map);
    var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 13, attribution: 'Tiles © Esri' });
    
    console.log(roleToken);
    console.log('User Role:', roleName, 'Role ID:', roleToken);
    routeOverlayControl = L.control.layers({ "Street Map": street, "Satellite": satellite }, {
        "🚉 Stations": stationFeatureGroup,
        "🔥 Incidents": incidentFeatureGroup,
        "💧 Hydrants": hydrantFeatureGroup,
        "📍 Other Markers": otherFeatureGroup,
        "🔷 Shapes": shapeFeatureGroup
    }, { collapsed: true, position: 'topright' });
    routeOverlayControl.addTo(window.map);

    // Defense-in-depth: unauthorized roles (e.g. firefighter role 5) cannot see edit/delete controls.
    window.map.on('popupopen', () => {
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
        
        const drawControl = new L.Control.Draw({ 
            edit: { featureGroup: drawnItems, edit: true, remove: true }, 
            draw: { polyline: true, polygon: true, rectangle: true, circle: true, marker: true, circlemarker: false } 
        });
        window.map.addControl(drawControl);
        
        window.map.on(L.Draw.Event.DRAWSTART, () => { isDrawing = true; });
        window.map.on(L.Draw.Event.DRAWSTOP, () => { isDrawing = false; });
        window.map.on(L.Draw.Event.DRAWVERTEX, () => { isDrawing = true; });
        
        window.map.on(L.Draw.Event.CREATED, function(event) {
            const layer = event.layer;
            const geojson = layer.toGeoJSON();
            const shapeName = prompt('Enter shape name:', 'My Shape') || 'My Shape';
            fetch('/api/shapes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: shapeName, geojson: JSON.stringify(geojson), station_id: null })
            })
            .then(async res => {
                if (!res.ok) throw new Error(`Server error: ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (data.shape) {
                    if (drawnItems.hasLayer(layer)) drawnItems.removeLayer(layer);
                    const colorIndex = Object.keys(shapeLayers).length % colors.length;
                    const newLayer = L.geoJSON(geojson, { style: { color: colors[colorIndex], weight: 3, fillOpacity: 0.2, fillColor: colors[colorIndex] }, interactive: true });
                    newLayer.shapeId = data.shape.id;
                    newLayer.shapeName = shapeName;
                    newLayer.on('click', function(e) { if (manageShapesMode) { L.DomEvent.stopPropagation(e); onShapeClick(data.shape.id, newLayer, e.latlng); } });
                    shapeFeatureGroup.addLayer(newLayer);
                    shapeLayers[data.shape.id] = newLayer;
                    newLayer.bindTooltip(shapeName, { permanent: false, direction: "top", sticky: true, opacity: 0.9 });
                    showNotification('Shape saved successfully!', 'success');
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
                    .then(data => showNotification('Shape updated successfully!', 'success'))
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
                        if (shapeLayers[layer.shapeId]) {
                            if (shapeFeatureGroup) shapeFeatureGroup.removeLayer(shapeLayers[layer.shapeId]);
                            delete shapeLayers[layer.shapeId];
                        }
                        showNotification('Shape deleted successfully!', 'success');
                    })
                    .catch(err => console.error('Error deleting shape:', err));
                }
            });
        });
    }
    
    loadShapes();
    if (markerStatePollingInterval) {
        clearInterval(markerStatePollingInterval);
    }
    markerStatePollingInterval = setInterval(pollMarkerStateChanges, 5000);
    
    if (canManageMarkers) {
        window.map.on('click', function(e) {
            selectedLat = e.latlng.lat;
            selectedLng = e.latlng.lng;
            if (isDrawing) return;
            if (manageShapesMode) {
                let clickedShape = null, clickedLayer = null;
                Object.values(shapeLayers).forEach(layer => {
                    if (layer.getBounds && layer.getBounds().contains(e.latlng)) { clickedShape = layer.shapeId; clickedLayer = layer; }
                });
                if (clickedShape) { onShapeClick(clickedShape, clickedLayer, e.latlng); return; }
            }
            if (!startMarker) {
                startMarker = L.marker(e.latlng).addTo(window.map);
                getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
                return;
            }
            window.map.removeLayer(startMarker);
            startMarker = L.marker(e.latlng).addTo(window.map);
            getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
        });
    }
    
    setTimeout(() => showNotification('Welcome to PyroSense Dashboard', 'info', 'System Ready'), 1000);
}

// ============================================================================
// Save marker button handler
// ============================================================================

setTimeout(() => {
    const saveMarkerBtn = document.getElementById('saveMarkerBtn');
    if (saveMarkerBtn) {
        const newSaveBtn = saveMarkerBtn.cloneNode(true);
        saveMarkerBtn.parentNode.replaceChild(newSaveBtn, saveMarkerBtn);
        
        newSaveBtn.addEventListener('click', async function(e) {
            e.preventDefault();
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
                const markerResponse = await fetch('/api/markers', {
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
                    
                    if (!hydrantCode) { alert('Please enter hydrant code'); return; }
                    if (!hydrantStationId) { alert('Please select the station this hydrant belongs to'); return; }
                    
                    const hydrantResponse = await fetch('/api/hydrant', {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ hydrant_code: hydrantCode, station_id: hydrantStationId, marker_id: markerId, hydrant_type_id: hydrantTypeId || null })
                    });
                    await readJsonResponse(hydrantResponse, 'Failed to create hydrant');
                } else if (categoryName.toLowerCase() === 'school') {
                    const schoolName = document.getElementById('schoolName')?.value;
                    if (!schoolName) { alert('Please enter school name'); return; }
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
                        alert('Please fill in the required barangay hall fields');
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
                if (modal) modal.style.display = 'none';
                await fetchCoordinate();
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
async function calculateRouteToIncident(incidentId, markerId = null) {
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
    
    console.log(window.userCoords);
    if (!window.userCoords || !window.userCoords.ready) {
        showToast('Getting your location... Please wait.', 'warning');
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

    const destination = {
        lat: parseFloat(lat),
        lng: parseFloat(lng),
        title: `${incidentType} - ${incident?.title || markerRecord?.title || 'Emergency'}`,
        incidentStatusId,
        incidentId: resolvedIncidentId
    };
    
    console.log('Calculating route to incident:', destination);
    const origin = window.userCoords;

    window.thisIncidents = resolvedIncidentId;
    console.log('thisincidents: ', window.thisIncidents);
    await calculateRouteWithGeoapify(origin, destination);
}



window.closeModal = function() {
    const modal = document.getElementById('markerModal');
    if (!modal) {
        return;
    }

    modal.style.display = 'none';
    modal.classList.remove('active');
};

window.openModal = function() {
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
        modal.classList.add('active');
    }
};

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

// ============================================================================
// EXPOSE FUNCTIONS GLOBALLY FOR BLADE FILES
// ============================================================================

// Make sure these are available globally
window.calculateRouteToIncident = calculateRouteToIncident;
window.calculateRouteToStation = calculateRouteToStation;
window.calculateRouteWithGeoapify = calculateRouteWithGeoapify;
window.displayAndStartRoute = displayAndStartRoute;
window.startSimulationForRoute = startSimulationForRoute;
window.stopSimulationForRoute = stopSimulationForRoute;
window.stopAllSimulations = stopAllSimulations;
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
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
};

console.log('Map.js fully loaded - All functions exposed globally');

