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

// Route polling interval
let routePollingInterval = null;

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
                        <i class="fas fa-helmet-safety" style="color: white; font-size: ${isCurrentUser ? '12px' : '10px'};"></i>
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
    let data = '';
    if (window.incidentTypes) {
        window.incidentTypes.forEach(type => {
            if(type.incident_type_id === typeId || type.incindent_type_id == typeId) {
                data = type.type;
            }
        });
    }
    return data || 'Incident';
}

function createRouteInfoCard() {
    if (document.getElementById('routeInfoCard')) {
        return;
    }
    
    const mapContainer = document.getElementById('map');
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
    
    const mapParent = document.getElementById('map').parentElement;
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
                destination_lng: destination.lng
            })
        });
        
        const result = await response.json();
        console.log('Route saved to database:', result);
        return result;
    } catch (error) {
        console.error('Failed to save route to database:', error);
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
        return result;
    } catch (error) {
        console.error('Failed to update route status:', error);
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
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
    }
    
    window.map = L.map('map', {
        zoomControl: true,
        zoomAnimation: true,
        fadeAnimation: true,
        markerZoomAnimation: true
    }).setView([15.8222, 120.3297], 13);
    
    const stationFeatureGroup = L.featureGroup().addTo(window.map);
    const incidentFeatureGroup = L.featureGroup().addTo(window.map);
    const hydrantFeatureGroup = L.featureGroup().addTo(window.map);
    const otherFeatureGroup = L.featureGroup().addTo(window.map);
    
    window.stationFeatureGroup = stationFeatureGroup;
    window.incidentFeatureGroup = incidentFeatureGroup;
    window.hydrantFeatureGroup = hydrantFeatureGroup;
    window.otherFeatureGroup = otherFeatureGroup;
    
    window.setName = setName;
    window.routesGroup = L.layerGroup().addTo(window.map);
    
    if(!fetchCoordinate()) { console.log('loading'); } else { console.log('loaded'); }
    
    initAfterMapReady();
    
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
const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
window.geoapifyKey = apiKey;
window.currentDisplayedRouteId = null;

// ============================================================================
// ICON ARRAY (keeping your existing icons)
// ============================================================================

const ICON_ARRAY = [
    {
        id: 'station',
        name: 'Fire Station',
        category: 'emergency',
        icon: 'fa-fire-extinguisher',
        color: '#2563eb',
        bgColor: '#3b82f6',
        size: [32, 32],
        anchor: [16, 16]
    },
    {
        id: 'incident',
        name: 'Incident',
        category: 'emergency',
        icon: 'fa-exclamation-triangle',
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
        size: [28, 36],
        anchor: [14, 36]
    },
    {
        id: 'firefighter',
        name: 'Firefighter',
        category: 'personnel',
        icon: 'fa-helmet-safety',
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
        icon: 'fa-shield-halved',
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
                    class="speed-badge"
                ">0 km/h</div>
            ` : ''}
        `,
        iconSize: [width, height],
        iconAnchor: iconConfig.anchor,
        popupAnchor: [0, -height/2]
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

function getIconByCategoryId(categoryId, extraClass = '') {
    const iconId = CATEGORY_ICON_MAP[categoryId];
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
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 30px -10px rgba(0,0,0,0.2);
        z-index: 1000;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        display: none;
    }
    
    .details-panel.visible {
        display: block;
        animation: slideInRight 0.3s ease;
    }
    
    .details-header {
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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

async function getHydrantTypesOptions(selectedId = null) {
    try {
        const response = await fetch('/api/hydrant-types');
        if (response.ok) {
            const types = await response.json();
            const typesList = types.data || types;
            return typesList.map(type => `
                <option value="${type.hydrant_type_id}" ${selectedId == type.hydrant_type_id ? 'selected' : ''}>
                    ${type.type}
                </option>
            `).join('');
        }
        return '';
    } catch (error) {
        console.error('Error fetching hydrant types:', error);
        return '';
    }
}

async function openEditModal(marker) {
    console.log('Opening edit modal for marker:', marker);
    
    initEditModal();
    currentEditMarker = marker;
    
    const modalContent = document.getElementById('editModalContent');
    let extraFieldsHtml = '';
    
    if (marker.markerCategory_id == 1) {
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
    } else if (marker.markerCategory_id == 4) {
        try {
            const hydrantResponse = await fetch(`/api/hydrant/marker/${marker.marker_id}`);
            if (hydrantResponse.ok) {
                const hydrantData = await hydrantResponse.json();
                const stationsOptions = await getStationsOptions(hydrantData.station_id);
                const hydrantTypesOptions = await getHydrantTypesOptions(hydrantData.hydrant_type_id);
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
                    <div class="edit-form-group">
                        <label><i class="fas fa-tag"></i> Hydrant Type</label>
                        <select id="editHydrantType">
                            <option value="">Select Hydrant Type</option>
                            ${hydrantTypesOptions}
                        </select>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching hydrant data:', error);
        }
    } else if (marker.markerCategory_id == 5) {
        try {
            const schoolResponse = await fetch(`/api/schools/marker/${marker.marker_id}`);
            if (schoolResponse.ok) {
                const schoolData = await schoolResponse.json();
                extraFieldsHtml = `
                    <div class="edit-form-group">
                        <label><i class="fas fa-school"></i> School Name</label>
                        <input type="text" id="editSchoolName" value="${escapeHtml(schoolData.school_name || '')}">
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching school data:', error);
        }
    }
    
    let categoriesHtml = '';
    try {
        const categoriesResponse = await fetch('/api/marker-categories');
        if (categoriesResponse.ok) {
            const categories = await categoriesResponse.json();
            categoriesHtml = categories.map(cat => `
                <option value="${cat.markerCategory_id}" ${cat.markerCategory_id == marker.markerCategory_id ? 'selected' : ''}>
                    ${escapeHtml(cat.marker_category)}
                </option>
            `).join('');
        }
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
    
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
    
    if (!title) {
        alert('Please enter marker title');
        if (titleInput) titleInput.focus();
        return;
    }
    if (!categoryId) {
        alert('Please select a category');
        return;
    }
    
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryName = selectedOption?.getAttribute('data-category-name') || selectedOption?.text || '';
    
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
                markerCategory_id: categoryId
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
            const hydrantTypeId = document.getElementById('editHydrantType')?.value;
            
            await fetch(`/api/hydrant/marker/${markerId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    hydrant_code: hydrantCode,
                    station_id: hydrantStationId,
                    hydrant_type_id: hydrantTypeId
                })
            });
        } else if (categoryName.toLowerCase().includes('school')) {
            const schoolName = document.getElementById('editSchoolName')?.value;
            
            await fetch(`/api/schools/marker/${markerId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    school_name: schoolName
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
    const title = markerData.title || 'Untitled Marker';
    const description = markerData.description || 'No description available';
    const lat = markerData.lat || 'N/A';
    const lng = markerData.lng || 'N/A';
    const address = await setName(lat, lng);
    const category = markerData.category || getCategoryName(markerData.markerCategory_id);
    const created = markerData.created_at ? new Date(markerData.created_at).toLocaleString() : 'N/A';
    
    let extraInfoHtml = '';
    
    // Incident details
    if (markerData.markerCategory_id == 2 || markerData.markerCategory_id == 3) {
        const incidentStatus = markerData.incident_status || 'reported';
        const incidentID = window.incidentsData?.find(incident => incident.marker_id === markerData.marker_id)?.incident_id;
        const type = window.incidentsData?.find(incident => incident.marker_id === markerData.marker_id)?.incident_type_id || 'N/A';
        const sample = getIncidentTypeName(type);
        const incidentTypeName = sample ?? 'Fire';
        
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
        
        if (incidentStatus === 'responding') {
            statusColor = '#f59e0b';
            statusIcon = 'fa-truck';
        } else if (incidentStatus === 'resolved') {
            statusColor = '#10b981';
            statusIcon = 'fa-check-circle';
        }
        
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: #fef2f2; border-left: 4px solid ${statusColor};">
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
    if (markerData.markerCategory_id == 4 && markerData.hydrant) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: #eff6ff;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #1e40af;">
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
    if (markerData.markerCategory_id == 1 && markerData.station) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: #eff6ff;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #1e40af;">
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
    if (markerData.markerCategory_id == 5 && markerData.school) {
        extraInfoHtml = `
            <div class="details-info" style="margin-top: 16px; background: #eff6ff;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #1e40af;">
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
    
    detailsPanel.classList.add('visible');
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
        if (stationFeatureGroup) stationFeatureGroup.clearLayers();
        if (incidentFeatureGroup) incidentFeatureGroup.clearLayers();
        if (hydrantFeatureGroup) hydrantFeatureGroup.clearLayers();
        if (otherFeatureGroup) otherFeatureGroup.clearLayers();
        existingMarkers = {};
    } catch (error) {
        console.error('Error clearing markers:', error);
    }
}

async function fetchCoordinate() {
    try {
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
        clearAllMarkers();
        await updateCoordinate(coordinates);
    } catch (error) {
        console.error('Fetch error in fetchCoordinate:', error);
        showNotification('Failed to fetch markers: ' + error.message, 'error');
    }
}

async function updateCoordinate(data) {
    try {
        const incidentResponse = await fetch('/api/incident/latest');
        let incidents = [];
        if (incidentResponse.ok) {
            const incidentData = await incidentResponse.json();
            incidents = incidentData.data || incidentData;
        }
        
        const incidentTypeResponse = await fetch('/api/incident-type');
        let incidentTypes = [];
        if (incidentTypeResponse.ok) {
            const typesData = await incidentTypeResponse.json();
            incidentTypes = typesData.data || typesData;
        }
        
        let coordinates = [];
        if (data.data && Array.isArray(data.data)) {
            coordinates = data.data;
        } else if (Array.isArray(data)) {
            coordinates = data;
        } else if (data && typeof data === 'object') {
            coordinates = [data];
        } else {
            return;
        }
        
        const hydrantsResponse = await fetch('/api/hydrants');
        const schoolsResponse = await fetch('/api/schools');
        const stationsResponse = await fetch('/api/station');
        
        let hydrants = [], schools = [], stations = [];
        
        if (hydrantsResponse.ok) {
            const hydrantData = await hydrantsResponse.json();
            hydrants = hydrantData.data || hydrantData;
        }
        if (schoolsResponse.ok) {
            const schoolData = await schoolsResponse.json();
            schools = schoolData.data || schoolData;
        }
        if (stationsResponse.ok) {
            const stationData = await stationsResponse.json();
            stations = stationData.data || stationData;
        }
        
        const markersToProcess = [];
        
        for (const item of coordinates) {
            if (item.marker && item.marker.lat && item.marker.lng) {
                markersToProcess.push({ ...item.marker, incident_id: item.incident_id, incident_status: item.incident_status_id });
            } else if (item.lat && item.lng) {
                if (item.markerCategory_id == 1) {
                    const station = stations.find(s => s.marker_id == item.marker_id);
                    if (station) item.station = station;
                } else if (item.markerCategory_id == 2 || item.markerCategory_id == 3) {
                    const incident = incidents.find(inc => inc.marker?.marker_id === item.marker_id);
                    if (incident) {
                        item.incident_status = incident.status;
                         item.incident_id = incident.incident_id;  // ADD THIS LINE
                        const incidentType = incidentTypes.find(type => type.incident_type_id == incident.incident_type_id);
                        item.incident_type_name = incidentType ? incidentType.type : incident.incident_type || 'Fire';
                        item.reported_at = incident.created_at;
                    }
                } else if (item.markerCategory_id == 4) {
                    const hydrant = hydrants.find(h => h.marker_id == item.marker_id);
                    if (hydrant) {
                        item.hydrant = hydrant;
                        if (hydrant.station_id) {
                            const station = stations.find(s => s.station_id == hydrant.station_id);
                            if (station) item.hydrant.station = station;
                        }
                    }
                } else if (item.markerCategory_id == 5) {
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
            const tempIcon = getIconByCategoryId(marker.markerCategory_id);
            const newMarker = L.marker([lat, lng], { icon: tempIcon }).bindPopup(popupContent, { minWidth: 100, maxWidth: 300 });
            
            if (marker.markerCategory_id == 2 || marker.markerCategory_id == 3) {
                const circle = L.circle([lat, lng], { radius: 200, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2 });
                incidentFeatureGroup.addLayer(circle);
            }
            
            addMarkerToFeatureGroup(marker.markerCategory_id, newMarker);
            newMarker.on('popupopen', () => addPopupEventListeners({ marker: marker, image: marker.multimedia || null, created_at: marker.created_at }));
            existingMarkers[marker.marker_id] = newMarker;
        });
        
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
    
    console.log('coord: ', coord, "incidents: ", incidents);
    console.log("incidents: ", incident);
    console.log("incident_id: ", incident?.incident_id);

    // Special design for fire stations (category_id = 1)
    if (marker.markerCategory_id == 1) {
        return `
            <div style="min-width: 280px; max-width: 320px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 12px 16px; border-radius: 12px 12px 0 0; margin: -12px -12px 0 -12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-fire-extinguisher" style="font-size: 24px;"></i>
                        <div>
                            <strong style="font-size: 16px;">${marker.title || 'Fire Station'}</strong>
                            <div style="font-size: 11px; opacity: 0.9;">Emergency Response Facility</div>
                        </div>
                    </div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="background: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-building" style="color: #3b82f6; width: 20px;"></i>
                            <span style="font-size: 13px;">${marker.station?.station_name || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-phone" style="color: #3b82f6; width: 20px;"></i>
                            <span style="font-size: 13px;">${marker.station?.contact_number || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fas fa-map-marker-alt" style="color: #3b82f6; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: 12px; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToStation(${marker.marker_id})" style="width: 100%; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; margin-bottom: 10px;">
                    <i class="fa-solid fa-route"></i> Get Directions
                </button>
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 12px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: 60px; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Beautiful incident display (category_id = 2 or 3)
    if (marker.markerCategory_id == 2 || marker.markerCategory_id == 3) {
        const incidentStatus = marker.incident_status || 'reported';
        const incidentTypeName = marker.incident_type_id || marker.incident_type || 'Fire';
        const reportedAt = marker.reported_at ? new Date(marker.reported_at).toLocaleString() : new Date(marker.created_at).toLocaleString();
        
        let typeIcon = '🔥';
        let typeColor = '#ef4444';
        let typeBgGradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        
        switch(incidentTypeName.toLowerCase()) {
            case 'fire': typeIcon = '🔥'; typeColor = '#ef4444'; typeBgGradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'; break;
            case 'medical emergency': typeIcon = '🚑'; typeColor = '#10b981'; typeBgGradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)'; break;
            case 'rescue': typeIcon = '🆘'; typeColor = '#f59e0b'; typeBgGradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; break;
            case 'hazardous materials': typeIcon = '⚠️'; typeColor = '#f97316'; typeBgGradient = 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)'; break;
            case 'false alarm': typeIcon = '❌'; typeColor = '#6b7280'; typeBgGradient = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)'; break;
        }
        
        let statusBadge = '';
        let statusColor = '';
        
        switch(incidentStatus.toLowerCase()) {
            case 'reported': statusColor = '#ef4444'; statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">REPORTED</span>'; break;
            case 'responding': statusColor = '#f59e0b'; statusBadge = '<span style="background: #f59e0b; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">RESPONDING</span>'; break;
            case 'resolved': statusColor = '#10b981'; statusBadge = '<span style="background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">RESOLVED</span>'; break;
            default: statusColor = '#ef4444'; statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">ACTIVE</span>';
        }
        
        return `
            <div style="min-width: 300px; max-width: 340px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: ${typeBgGradient}; color: white; padding: 14px 16px; border-radius: 12px 12px 0 0; margin: -12px -12px 0 -12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 28px;">${typeIcon}</span>
                            <div>
                                <strong style="font-size: 16px;">${marker.title || 'Emergency Incident'}</strong>
                                <div style="font-size: 11px; opacity: 0.9; margin-top: 2px;">${incidentTypeName}</div>
                            </div>
                        </div>
                        ${statusBadge}
                    </div>
                </div>
                
                <div style="padding: 12px 0;">
                    <div style="background: #fef2f2; border-radius: 10px; padding: 12px; margin-bottom: 12px; border-left: 3px solid ${typeColor};">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">STATUS</div>
                                <div style="font-size: 13px; font-weight: 600; color: ${statusColor};">${incidentStatus.toUpperCase()}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">REPORTED</div>
                                <div style="font-size: 12px;">${reportedAt}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px; margin-top: 8px; padding-top: 8px; border-top: 1px solid #fee2e2;">
                            <i class="fas fa-map-marker-alt" style="color: ${typeColor}; font-size: 12px; margin-top: 2px;"></i>
                            <span style="font-size: 12px; color: #4b5563; line-height: 1.4;">${marker.description || 'No description available'}</span>
                        </div>
                    </div>
                </div>
                
                ${incidentStatus === 'reported' && roleToken == 1 ? `
                <button id="respond${marker.marker_id}" 
                    style="width: 100%; background: #dc2626; color: white; border: none; padding: 10px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                    <i class="fas fa-phone-alt"></i> RESPOND NOW
                </button>
                ` : ''}
                ${roleToken == 1 ? `
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                ` : ''}

                <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToIncident(${incident?.incident_id})" style="width: 100%; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; margin-bottom: 10px;">
                    <i class="fa-solid fa-route"></i> Get Directions to Incident
                </button>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 12px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: 60px; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for fire hydrants (category_id = 4)
    if (marker.markerCategory_id == 4 && marker.hydrant) {
        return `
            <div style="min-width: 280px; max-width: 320px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 12px 16px; border-radius: 12px 12px 0 0; margin: -12px -12px 0 -12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-fire-extinguisher" style="font-size: 24px;"></i>
                        <div>
                            <strong style="font-size: 16px;">${marker.title || 'Fire Hydrant'}</strong>
                            <div style="font-size: 11px; opacity: 0.9;">Water Supply Point</div>
                        </div>
                    </div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="background: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-barcode" style="color: #dc2626; width: 20px;"></i>
                            <span style="font-size: 13px;">${marker.hydrant.hydrant_code || 'N/A'}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-building" style="color: #dc2626; width: 20px;"></i>
                            <span style="font-size: 13px;">${marker.hydrant.station?.station_name || 'Not Assigned'}</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fas fa-map-marker-alt" style="color: #dc2626; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: 12px; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 12px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: 60px; object-fit: cover;"></div>` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for schools (category_id = 5)
    if (marker.markerCategory_id == 5 && marker.school) {
        return `
            <div style="min-width: 280px; max-width: 320px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); color: white; padding: 12px 16px; border-radius: 12px 12px 0 0; margin: -12px -12px 0 -12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-school" style="font-size: 24px;"></i>
                        <div>
                            <strong style="font-size: 16px;">${marker.school.school_name || marker.title || 'School'}</strong>
                            <div style="font-size: 11px; opacity: 0.9;">Educational Institution</div>
                        </div>
                    </div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="background: #f8fafc; border-radius: 8px; padding: 10px;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fas fa-align-left" style="color: #eab308; width: 20px; margin-top: 2px;"></i>
                            <span style="font-size: 12px; color: #4b5563;">${marker.description || 'No description'}</span>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" style="flex: 1; background: #10b981; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" style="flex: 1; background: #ef4444; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <button id="viewDetails${marker.marker_id}" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 8px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;">
                    <i class="fas fa-info-circle"></i> View Full Details
                </button>
                ${imagePath ? `<div style="margin-top: 12px; border-radius: 8px; overflow: hidden;"><img src="${imagePath}" style="width: 100%; height: 60px; object-fit: cover;"></div>` : ''}
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
            
            // SAVE TO DATABASE FOR ALL USERS TO SEE
            const savedRoute = await saveRouteToDatabase(route, destination);
            console.log("displaying route data1: ", savedRoute);
            
            // Add to multi-truck system
            if (savedRoute && savedRoute.data) {    
                const routeId = savedRoute.data.route_id;
                await displayAndStartRoute(routeId, route, destination);
            console.log("displaying route data2: ", route);

            }
            
            console.log("displaying route data3: ", route);
            // displayGeoapifyRoute(route);
            // updateRouteInfoFromGeoapify(route);
            
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

function displayGeoapifyRoute(route) {
    if (routeLayer) {
        window.map.removeLayer(routeLayer);
    }
    
    let latlngs = [];
    let coordinates;
    
    // Handle database format (string with triple brackets)
    if (typeof route === 'object' && route.coordinates) {
        // This is from database
        if (typeof route.coordinates === 'string') {
            coordinates = JSON.parse(route.coordinates);
        } else {
            coordinates = route.coordinates;
        }
    } else if (route.geometry) {
        // This is from Geoapify API
        coordinates = route.geometry.coordinates;
    } else {
        console.error('Unknown route format:', route);
        return;
    }
    
    console.log('Raw coordinates:', coordinates);
    console.log('Coordinates type:', Array.isArray(coordinates));
    console.log('First level length:', coordinates?.length);
    
    // Fix: Handle the triple nested array format
    // Database format: [[[lng, lat], [lng, lat], ...]]
    // Leaflet needs: [[lat, lng], [lat, lng], ...]
    
    // Check if we have a triple-nested array (from database)
    if (Array.isArray(coordinates) && coordinates.length > 0) {
        // If first element is an array and its first element is also an array
        if (Array.isArray(coordinates[0]) && Array.isArray(coordinates[0][0])) {
            // Triple nested - flatten it
            coordinates = coordinates[0];
            console.log('Flattened coordinates, new length:', coordinates.length);
        }
        
        // Now convert [lng, lat] to [lat, lng] for Leaflet
        for (let i = 0; i < coordinates.length; i++) {
            const coord = coordinates[i];
            if (Array.isArray(coord) && coord.length >= 2) {
                // Geoapify returns [lng, lat], Leaflet wants [lat, lng]
                latlngs.push([coord[1], coord[0]]);
            }
        }
    }
    
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
    
    // Extract coordinates (same as before)
    let coordinates = null;
    
    if (typeof route.coordinates === 'string') {
        try {
            coordinates = JSON.parse(route.coordinates);
        } catch (e) {
            console.error('Failed to parse coordinates string:', e);
            showToast('Invalid route coordinates', 'error');
            return;
        }
    } else if (Array.isArray(route.coordinates)) {
        coordinates = route.coordinates;
    } else if (route.geometry && route.geometry.coordinates) {
        coordinates = route.geometry.coordinates;
    }
    
    if (!coordinates) {
        console.error('No coordinates found in route');
        showToast('Invalid route data - no coordinates', 'error');
        return;
    }
    
    // Handle triple-nested array
    let flattenedCoords = coordinates;
    if (Array.isArray(coordinates) && coordinates.length > 0) {
        if (Array.isArray(coordinates[0]) && coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
            flattenedCoords = coordinates[0];
            console.log('Flattened triple-nested coordinates, new length:', flattenedCoords.length);
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
        showToast('Invalid route coordinates', 'error');
        return;
    }
    
    console.log(`Route ${routeId} has ${latlngs.length} points`);
    
    const truckData = {
        routeId: routeId,
        simulationPoints: latlngs,
        originalSimulationPoints: [...latlngs],
        currentPointIndex: 0,
        isSimulating: false,
        routeDestination: destination,
        routeLayer: null,
        traveledLayer: null,
        animatedVehicle: null,
        animationFrame: null,
        realTimeLocationWatch: null
    };
    
    activeTrucks.set(routeId, truckData);
    
    // Display route line with unique color
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
    truckData.routeLayer = L.polyline(latlngs, {
        color: truckColors[colorIndex],
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round',
        lineCap: 'round'
    }).addTo(window.map);
    
    // Add start and end markers
    if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
        L.marker(latlngs[0], {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup('Start Point').addTo(window.map);
    }
    
    if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
        L.marker(latlngs[latlngs.length - 1], {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            })
        }).bindPopup(`Destination: ${destination.title}`).addTo(window.map);
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
// async function displayAndStartRoute(routeId, route, destination) {
//     console.log('Displaying route data:', route);
    
//     // Extract coordinates (same as before)
//     let coordinates = null;
    
//     if (typeof route.coordinates === 'string') {
//         try {
//             coordinates = JSON.parse(route.coordinates);
//         } catch (e) {
//             console.error('Failed to parse coordinates string:', e);
//             showToast('Invalid route coordinates', 'error');
//             return;
//         }
//     } else if (Array.isArray(route.coordinates)) {
//         coordinates = route.coordinates;
//     } else if (route.geometry && route.geometry.coordinates) {
//         coordinates = route.geometry.coordinates;
//     }
    
//     if (!coordinates) {
//         console.error('No coordinates found in route');
//         showToast('Invalid route data - no coordinates', 'error');
//         return;
//     }
    
//     // Handle triple-nested array
//     let flattenedCoords = coordinates;
//     if (Array.isArray(coordinates) && coordinates.length > 0) {
//         if (Array.isArray(coordinates[0]) && coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
//             flattenedCoords = coordinates[0];
//             console.log('Flattened triple-nested coordinates, new length:', flattenedCoords.length);
//         }
//     }
    
//     // Convert [lng, lat] to [lat, lng] for Leaflet
//     const latlngs = [];
//     for (let i = 0; i < flattenedCoords.length; i++) {
//         const coord = flattenedCoords[i];
//         if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
//             latlngs.push([coord[1], coord[0]]);
//         }
//     }
    
//     if (latlngs.length === 0) {
//         console.error('No valid coordinates found after parsing');
//         showToast('Invalid route coordinates', 'error');
//         return;
//     }
    
//     console.log(`Route ${routeId} has ${latlngs.length} points`);
    
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
    
//     // Add start and end markers
//     if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
//         L.marker(latlngs[0], {
//             icon: L.divIcon({
//                 className: 'start-marker',
//                 html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                 iconSize: [12, 12],
//                 iconAnchor: [6, 6]
//             })
//         }).bindPopup('Start Point').addTo(window.map);
//     }
    
//     if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
//         L.marker(latlngs[latlngs.length - 1], {
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
    
//     // ONLY auto-start if this is a NEW route (status active, not moving)
//     // For existing moving routes, don't reset position
//     if (route.status === 'active') {
//         console.log('New route - auto-starting simulation for route:', routeId);
//         await startSimulationForRoute(routeId);
//     }
//     // If route is already moving, it will be handled by startRoutePolling
// }


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

// async function startSimulationForRoute(routeId) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck || !truck.simulationPoints || truck.simulationPoints.length === 0) {
//         showToast('No route points available for simulation', 'error');
//         return;
//     }
    
//     await updateRouteStatus(routeId, 'moving', 0, truck.simulationPoints[0][0], truck.simulationPoints[0][1], 0);
    
//     truck.isSimulating = true;
    
//     const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//     const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
//     truck.animatedVehicle = L.marker(truck.simulationPoints[0], {
//         icon: createFireTruckIcon(truckColors[colorIndex]),
//         zIndexOffset: 1000
//     }).addTo(window.map);
//     truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
//     truck.traveledLayer = L.polyline([], {
//         color: '#9ca3af',
//         weight: 4,
//         opacity: 0.6,
//         lineJoin: 'round',
//         lineCap: 'round'
//     }).addTo(window.map);
    
//     // Remove start button from UI
//     const routesList = document.getElementById('activeRoutesList');
//     if (routesList) {
//         const routeDivs = routesList.children;
//         for (let i = 0; i < routeDivs.length; i++) {
//             const div = routeDivs[i];
//             if (div.innerHTML.includes(`Truck ${Array.from(activeTrucks.keys()).indexOf(routeId) + 1}`)) {
//                 const btn = div.querySelector('button');
//                 if (btn && btn.innerHTML.includes('Start')) btn.remove();
//                 break;
//             }
//         }
//     }
    
//     startSmoothAnimationForTruck(routeId);
//     startRealTimeLocationUpdatesForTruck(routeId);
//     showToast(`Movement started for truck ${routeId}!`, 'success');
// }

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
    
    // Update route status to moving with initial position
    try {
        await fetch(`/api/route/${routeId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ 
                status: 'moving',
                current_position_index: 0,
                current_lat: startPoint[0],
                current_lng: startPoint[1],
                progress_percentage: 0
            })
        });
        console.log('Route status updated to moving with initial position');
    } catch (error) {
        console.error('Error updating route status:', error);
    }
    
    truck.isSimulating = true;
    truck.currentPointIndex = 0;
    
    const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
    const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
    if (truck.animatedVehicle) {
        window.map.removeLayer(truck.animatedVehicle);
    }
    
    truck.animatedVehicle = L.marker([startPoint[0], startPoint[1]], {
        icon: createFireTruckIcon(truckColors[colorIndex]),
        zIndexOffset: 1000
    }).addTo(window.map);
    truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
    if (truck.traveledLayer) {
        window.map.removeLayer(truck.traveledLayer);
    }
    truck.traveledLayer = L.polyline([], {
        color: '#9ca3af',
        weight: 4,
        opacity: 0.6
    }).addTo(window.map);
    
    startSmoothAnimationForTruck(routeId);
    startRealTimeLocationUpdatesForTruck(routeId);
    showToast(`Movement started for truck ${routeId}!`, 'success');
}
// async function startSimulationForRoute(routeId) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck || !truck.simulationPoints || truck.simulationPoints.length === 0) {
//         showToast('No route points available for simulation', 'error');
//         console.error('No truck data for route:', routeId);
//         return;
//     }
    
//     console.log('Starting simulation for route:', routeId);
//     console.log('Simulation points count:', truck.simulationPoints.length);
//     console.log('First point:', truck.simulationPoints[0]);
//     console.log('Last point:', truck.simulationPoints[truck.simulationPoints.length - 1]);
    
//     // Validate start point
//     const startPoint = truck.simulationPoints[0];
//     if (!startPoint || !startPoint[0] || !startPoint[1]) {
//         console.error('Invalid start point coordinates:', startPoint);
//         showToast('Invalid start point coordinates', 'error');
//         return;
//     }
    
//     // Update route status to moving
//     try {
//         const response = await fetch(`/api/route/${routeId}/status`, {
//             method: 'PUT',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': csrfToken
//             },
//             body: JSON.stringify({ 
//                 status: 'moving',
//                 current_position_index: 0,
//                 current_lat: startPoint[0],
//                 current_lng: startPoint[1],
//                 progress_percentage: 0
//             })
//         });
        
//         if (!response.ok) {
//             console.error('Failed to update route status');
//         } else {
//             console.log('Route status updated to moving');
//         }
//     } catch (error) {
//         console.error('Error updating route status:', error);
//     }
    
//     truck.isSimulating = true;
    
//     const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//     const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
//     // Remove existing vehicle if any
//     if (truck.animatedVehicle) {
//         window.map.removeLayer(truck.animatedVehicle);
//     }
    
//     // Create the truck marker
//     console.log('Creating truck at:', [startPoint[0], startPoint[1]]);
//     truck.animatedVehicle = L.marker([startPoint[0], startPoint[1]], {
//         icon: createFireTruckIcon(truckColors[colorIndex]),
//         zIndexOffset: 1000
//     }).addTo(window.map);
//     truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
//     // Create traveled layer
//     if (truck.traveledLayer) {
//         window.map.removeLayer(truck.traveledLayer);
//     }
//     truck.traveledLayer = L.polyline([], {
//         color: '#9ca3af',
//         weight: 4,
//         opacity: 0.6,
//         lineJoin: 'round',
//         lineCap: 'round'
//     }).addTo(window.map);
    
//     // Remove start button from UI
//     const routesList = document.getElementById('activeRoutesList');
//     if (routesList) {
//         const routeDivs = routesList.children;
//         for (let i = 0; i < routeDivs.length; i++) {
//             const div = routeDivs[i];
//             if (div.innerHTML.includes(`Truck ${Array.from(activeTrucks.keys()).indexOf(routeId) + 1}`)) {
//                 const btn = div.querySelector('button');
//                 if (btn && btn.innerHTML.includes('Start')) btn.remove();
//                 break;
//             }
//         }
//     }
    
//     console.log('Starting animation for truck:', routeId);
//     startSmoothAnimationForTruck(routeId);
//     startRealTimeLocationUpdatesForTruck(routeId);
//     showToast(`Movement started for truck ${routeId}!`, 'success');
// }

// function startSmoothAnimationForTruck(routeId) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck) {
//         console.error('No truck found for route:', routeId);
//         return;
//     }
    
//     if (!truck.simulationPoints || truck.simulationPoints.length < 2) {
//         console.error('Not enough points for animation. Points:', truck.simulationPoints?.length);
//         return;
//     }
    
//     console.log('Starting smooth animation for truck:', routeId);
//     console.log('Points count:', truck.simulationPoints.length);
//     console.log('Current point index:', truck.currentPointIndex);
    
//     // Stop any existing animation
//     if (truck.animationFrame) {
//         cancelAnimationFrame(truck.animationFrame);
//         truck.animationFrame = null;
//     }
    
//     // Build segments from current position
//     const segments = [];
//     let totalDistance = 0;
    
//     for (let i = truck.currentPointIndex; i < truck.simulationPoints.length - 1; i++) {
//         const p1 = truck.simulationPoints[i];
//         const p2 = truck.simulationPoints[i + 1];
        
//         if (!p1 || !p2) {
//             console.warn('Invalid points at index:', i);
//             continue;
//         }
        
//         const distance = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//         totalDistance += distance;
//         segments.push({
//             start: p1,
//             end: p2,
//             distance: distance,
//             cumulativeDistance: totalDistance,
//             angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
//         });
//     }
    
//     if (segments.length === 0) {
//         console.error('No valid segments created');
//         return;
//     }
    
//     console.log('Total distance:', totalDistance, 'meters');
//     console.log('Segments count:', segments.length);
    
//     const baseSpeed = 13.89; // 50 km/h in m/s
//     const totalDuration = totalDistance / baseSpeed;
//     const startTime = performance.now();
//     let lastUpdateTime = startTime;
//     let lastPositionIndex = truck.currentPointIndex;
    
//     console.log(`Animation started: Duration ${totalDuration.toFixed(0)} seconds`);
    
//     const animate = async (currentTime) => {
//         // Check if truck still exists
//         if (!activeTrucks.has(routeId)) {
//             console.log('Truck removed, stopping animation');
//             return;
//         }
        
//         const currentTruck = activeTrucks.get(routeId);
//         if (!currentTruck.animatedVehicle) {
//             console.log('Truck vehicle missing, stopping animation');
//             return;
//         }
        
//         const elapsed = (currentTime - startTime) / 1000;
//         let progress = Math.min(elapsed / totalDuration, 1);
        
//         if (progress >= 1) {
//             // Arrived at destination
//             console.log(`Truck ${routeId} arrived at destination!`);
//             await updateRouteStatus(routeId, 'inactive');
//             stopSimulationForRoute(routeId);
//             showToast(`Truck ${routeId} arrived at destination!`, 'success');
//             return;
//         }
        
//         const distanceTraveled = progress * totalDistance;
        
//         // Find current segment
//         let currentSegment = null;
//         let segmentStartDistance = 0;
        
//         for (let i = 0; i < segments.length; i++) {
//             if (distanceTraveled <= segments[i].cumulativeDistance) {
//                 currentSegment = segments[i];
//                 segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
//                 break;
//             }
//         }
        
//         if (currentSegment && currentTruck.animatedVehicle) {
//             const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
            
//             const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
//             const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
            
//             if (isNaN(lat) || isNaN(lng)) {
//                 console.warn('Invalid position calculated');
//                 return;
//             }
            
//             // Update truck position
//             currentTruck.animatedVehicle.setLatLng([lat, lng]);
            
//             // Rotate truck based on direction
//             const truckElement = currentTruck.animatedVehicle.getElement();
//             if (truckElement) {
//                 const truckDiv = truckElement.querySelector('div');
//                 if (truckDiv) {
//                     truckDiv.style.transform = `rotate(${currentSegment.angle}deg)`;
//                 }
//             }
            
//             // Update traveled path (gray line)
//             const traveledPoints = [];
//             for (let i = currentTruck.currentPointIndex; i <= currentTruck.currentPointIndex + Math.floor(progress * segments.length); i++) {
//                 if (currentTruck.simulationPoints[i]) {
//                     traveledPoints.push(currentTruck.simulationPoints[i]);
//                 }
//             }
//             traveledPoints.push([lat, lng]);
            
//             if (currentTruck.traveledLayer) {
//                 window.map.removeLayer(currentTruck.traveledLayer);
//             }
//             if (traveledPoints.length > 1) {
//                 currentTruck.traveledLayer = L.polyline(traveledPoints, {
//                     color: '#9ca3af',
//                     weight: 4,
//                     opacity: 0.6,
//                     lineJoin: 'round',
//                     lineCap: 'round'
//                 }).addTo(window.map);
//             }
            
//             // Update position index
//             let currentIndex = currentTruck.currentPointIndex;
//             let accumulatedDistance = 0;
//             for (let i = currentTruck.currentPointIndex; i < currentTruck.simulationPoints.length - 1; i++) {
//                 const p1 = currentTruck.simulationPoints[i];
//                 const p2 = currentTruck.simulationPoints[i + 1];
//                 if (!p1 || !p2) continue;
//                 const segDist = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//                 if (accumulatedDistance + segDist >= distanceTraveled) {
//                     currentIndex = i;
//                     break;
//                 }
//                 accumulatedDistance += segDist;
//                 currentIndex = i + 1;
//             }
            
//             currentTruck.currentPointIndex = currentIndex;
            
//             // Update database every 2 seconds
//             if (currentTime - lastUpdateTime > 2000 && currentIndex !== lastPositionIndex) {
//                 lastUpdateTime = currentTime;
//                 lastPositionIndex = currentIndex;
//                 const progressPercent = (currentIndex / currentTruck.simulationPoints.length) * 100;
//                 await updateRouteStatus(routeId, 'moving', currentIndex, lat, lng, progressPercent);
//                 console.log(`Updated position: index ${currentIndex}, progress ${progressPercent.toFixed(1)}%`);
//             }
            
//             // Update UI
//             updateRouteInfoCard();
            
//             // Pan map to follow truck
//             const truckLatLng = currentTruck.animatedVehicle.getLatLng();
//             const distanceToCenter = calculateDistanceBetweenPoints(
//                 window.map.getCenter().lat, window.map.getCenter().lng,
//                 truckLatLng.lat, truckLatLng.lng
//             );
            
//             if (distanceToCenter > 200) {
//                 window.map.panTo(truckLatLng, { animate: true, duration: 0.5 });
//             }
//         }
        
//         currentTruck.animationFrame = requestAnimationFrame(animate);
//     };
    
//     truck.animationFrame = requestAnimationFrame(animate);
// }
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
        updateRouteStatus(routeId, 'inactive', startIndex);
        stopSimulationForRoute(routeId);
        showToast(`Truck ${routeId} arrived at destination!`, 'success');
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
    let lastPositionIndex = startIndex;
    const UPDATE_INTERVAL = 500;
    
    console.log(`Animation will run for ${totalRemainingDuration.toFixed(0)} seconds`);
    
    const animate = async (currentTime) => {
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
            await updateRouteStatus(routeId, 'inactive', currentTruck.simulationPoints.length - 1);
            stopSimulationForRoute(routeId);
            showToast(`Truck ${routeId} arrived at destination!`, 'success');
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
                console.log(`Truck ${routeId} moved to index: ${currentIndex} (${(currentIndex/currentTruck.simulationPoints.length*100).toFixed(1)}%)`);
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
                window.map.removeLayer(currentTruck.traveledLayer);
            }
            if (traveledPoints.length > 1) {
                currentTruck.traveledLayer = L.polyline(traveledPoints, {
                    color: '#9ca3af',
                    weight: 4,
                    opacity: 0.6,
                    lineJoin: 'round',
                    lineCap: 'round'
                }).addTo(window.map);
            }
            
            // Update database every 500ms
            if (currentTime - lastUpdateTime > UPDATE_INTERVAL) {
                lastUpdateTime = currentTime;
                const progressPercent = (currentTruck.currentPointIndex / currentTruck.simulationPoints.length) * 100;
                
                await updateRouteStatus(routeId, 'moving', currentTruck.currentPointIndex, lat, lng, progressPercent);
                console.log(`DB Updated: index ${currentTruck.currentPointIndex}, progress ${progressPercent.toFixed(1)}%`);
            }
            
            updateRouteInfoCard();
        }
        
        currentTruck.animationFrame = requestAnimationFrame(animate);
    };
    
    truck.animationFrame = requestAnimationFrame(animate);
}
// function startSmoothAnimationForTruck(routeId) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck) {
//         console.error('No truck found for route:', routeId);
//         return;
//     }
    
//     if (!truck.simulationPoints || truck.simulationPoints.length < 2) {
//         console.error('Not enough points for animation. Points:', truck.simulationPoints?.length);
//         return;
//     }
    
//     console.log('Starting smooth animation for truck:', routeId);
//     console.log('Points count:', truck.simulationPoints.length);
//     console.log('Current point index (starting from):', truck.currentPointIndex);
    
//     // Stop any existing animation
//     if (truck.animationFrame) {
//         cancelAnimationFrame(truck.animationFrame);
//         truck.animationFrame = null;
//     }
    
//     // Build segments from current position (not from 0)
//     const segments = [];
//     let totalRemainingDistance = 0;
    
//     for (let i = truck.currentPointIndex; i < truck.simulationPoints.length - 1; i++) {
//         const p1 = truck.simulationPoints[i];
//         const p2 = truck.simulationPoints[i + 1];
        
//         if (!p1 || !p2) {
//             console.warn('Invalid points at index:', i);
//             continue;
//         }
        
//         const distance = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//         totalRemainingDistance += distance;
//         segments.push({
//             start: p1,
//             end: p2,
//             distance: distance,
//             cumulativeDistance: totalRemainingDistance,
//             angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
//         });
//     }
    
//     if (segments.length === 0) {
//         console.error('No valid segments created');
//         return;
//     }
    
//     console.log('Total remaining distance:', totalRemainingDistance, 'meters');
//     console.log('Segments count:', segments.length);
    
//     const baseSpeed = 13.89; // 50 km/h in m/s
//     const totalRemainingDuration = totalRemainingDistance / baseSpeed;
    
//     // Start time is NOW - the truck starts from its current position
//     const startTime = performance.now();
//     let lastUpdateTime = startTime;
//     let lastPositionIndex = truck.currentPointIndex;
    
//     console.log(`Animation will run for ${totalRemainingDuration.toFixed(0)} seconds from current position`);
//     console.log(`Starting from point index: ${truck.currentPointIndex} of ${truck.simulationPoints.length}`);
    
//     const animate = async (currentTime) => {
//         // Check if truck still exists
//         if (!activeTrucks.has(routeId)) {
//             console.log('Truck removed, stopping animation');
//             return;
//         }
        
//         const currentTruck = activeTrucks.get(routeId);
//         if (!currentTruck.animatedVehicle) {
//             console.log('Truck vehicle missing, stopping animation');
//             return;
//         }
        
//         const elapsed = (currentTime - startTime) / 1000;
//         let progress = Math.min(elapsed / totalRemainingDuration, 1);
        
//         if (progress >= 1) {
//             // Arrived at destination
//             console.log(`Truck ${routeId} arrived at destination!`);
//             await updateRouteStatus(routeId, 'inactive');
//             stopSimulationForRoute(routeId);
//             //update natin ung incident to incident_status_id pag nag stop na ung truck to number 3 -on scene
//             showToast(`Truck ${routeId} arrived at destination!`, 'success');
//             return;
//         }
        
//         const distanceTraveled = progress * totalRemainingDistance;
        
//         // Find current segment
//         let currentSegment = null;
//         let segmentStartDistance = 0;
        
//         for (let i = 0; i < segments.length; i++) {
//             if (distanceTraveled <= segments[i].cumulativeDistance) {
//                 currentSegment = segments[i];
//                 segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
//                 break;
//             }
//         }
        
//         if (currentSegment && currentTruck.animatedVehicle) {
//             const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
            
//             const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
//             const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
            
//             if (isNaN(lat) || isNaN(lng)) {
//                 console.warn('Invalid position calculated');
//                 return;
//             }
            
//             // Update truck position
//             currentTruck.animatedVehicle.setLatLng([lat, lng]);
            
//             // Rotate truck based on direction
//             const truckElement = currentTruck.animatedVehicle.getElement();
//             if (truckElement) {
//                 const truckDiv = truckElement.querySelector('div');
//                 if (truckDiv) {
//                     truckDiv.style.transform = `rotate(${currentSegment.angle}deg)`;
//                 }
//             }
            
//             // Update traveled path (gray line) - includes all points from start to current
//             const traveledPoints = [];
//             // Add all points from beginning to current point index
//             for (let i = 0; i <= currentTruck.currentPointIndex; i++) {
//                 if (currentTruck.simulationPoints[i]) {
//                     traveledPoints.push(currentTruck.simulationPoints[i]);
//                 }
//             }
//             // Add the current interpolated position
//             traveledPoints.push([lat, lng]);
            
//             if (currentTruck.traveledLayer) {
//                 window.map.removeLayer(currentTruck.traveledLayer);
//             }
//             if (traveledPoints.length > 1) {
//                 currentTruck.traveledLayer = L.polyline(traveledPoints, {
//                     color: '#9ca3af',
//                     weight: 4,
//                     opacity: 0.6,
//                     lineJoin: 'round',
//                     lineCap: 'round'
//                 }).addTo(window.map);
//             }
            
//             // Update position index in database
//             let currentIndex = currentTruck.currentPointIndex;
//             let accumulatedDistance = 0;
//             for (let i = currentTruck.currentPointIndex; i < currentTruck.simulationPoints.length - 1; i++) {
//                 const p1 = currentTruck.simulationPoints[i];
//                 const p2 = currentTruck.simulationPoints[i + 1];
//                 if (!p1 || !p2) continue;
//                 const segDist = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//                 if (accumulatedDistance + segDist >= distanceTraveled) {
//                     currentIndex = i;
//                     break;
//                 }
//                 accumulatedDistance += segDist;
//                 currentIndex = i + 1;
//             }
            
//             currentTruck.currentPointIndex = currentIndex;
//             console.log(`Updating position index to: ${currentIndex}`);
//             // Update database every 2 seconds
//             if (currentTime - lastUpdateTime > 2000 && currentIndex !== lastPositionIndex) {
//                 lastUpdateTime = currentTime;
//                 lastPositionIndex = currentIndex;
//                 const progressPercent = (currentIndex / currentTruck.simulationPoints.length) * 100;
//                 await updateRouteStatus(routeId, 'moving', currentIndex, lat, lng, progressPercent);
//                 console.log(`Updated position: index ${currentIndex}, progress ${progressPercent.toFixed(1)}%`);
//             }
            
//             // Update UI
//             updateRouteInfoCard();
            
//             // Pan map to follow truck
//             const truckLatLng = currentTruck.animatedVehicle.getLatLng();
//             const distanceToCenter = calculateDistanceBetweenPoints(
//                 window.map.getCenter().lat, window.map.getCenter().lng,
//                 truckLatLng.lat, truckLatLng.lng
//             );
            
//             if (distanceToCenter > 200) {
//                 window.map.panTo(truckLatLng, { animate: true, duration: 0.5 });
//             }
//         }
        
//         currentTruck.animationFrame = requestAnimationFrame(animate);
//     };
    
//     truck.animationFrame = requestAnimationFrame(animate);
// }
// Add this to console for testing
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
// function startSmoothAnimationForTruck(routeId) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck || !truck.simulationPoints || truck.simulationPoints.length < 2) {
//         console.error('Not enough points for animation');
//         return;
//     }
    
//     if (truck.animationFrame) cancelAnimationFrame(truck.animationFrame);
    
//     const segments = [];
//     let totalDistance = 0;
    
//     for (let i = truck.currentPointIndex; i < truck.simulationPoints.length - 1; i++) {
//         const p1 = truck.simulationPoints[i];
//         const p2 = truck.simulationPoints[i + 1];
//         const distance = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//         totalDistance += distance;
//         segments.push({
//             start: p1,
//             end: p2,
//             distance: distance,
//             cumulativeDistance: totalDistance,
//             angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
//         });
//     }
    
//     const baseSpeed = 13.89;
//     const totalDuration = totalDistance / baseSpeed;
//     const startTime = performance.now();
//     let lastUpdateTime = startTime;
//     let lastPositionIndex = truck.currentPointIndex;
    
//     const animate = async (currentTime) => {
//         const elapsed = (currentTime - startTime) / 1000;
//         let progress = Math.min(elapsed / totalDuration, 1);
        
//         if (progress >= 1) {
//             await updateRouteStatus(routeId, 'inactive');
//             stopSimulationForRoute(routeId);
//             showToast(`Truck ${routeId} arrived at destination!`, 'success');
//             return;
//         }
        
//         const distanceTraveled = progress * totalDistance;
        
//         let currentSegment = null;
//         let segmentStartDistance = 0;
        
//         for (let i = 0; i < segments.length; i++) {
//             if (distanceTraveled <= segments[i].cumulativeDistance) {
//                 currentSegment = segments[i];
//                 segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
//                 break;
//             }
//         }
        
//         if (currentSegment && truck.animatedVehicle) {
//             const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
            
//             const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
//             const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
            
//             truck.animatedVehicle.setLatLng([lat, lng]);
            
//             // Update traveled path (gray line)
//             const traveledPoints = [];
//             for (let i = truck.currentPointIndex; i <= truck.currentPointIndex + Math.floor(progress * segments.length); i++) {
//                 if (truck.simulationPoints[i]) traveledPoints.push(truck.simulationPoints[i]);
//             }
//             traveledPoints.push([lat, lng]);
            
//             if (truck.traveledLayer) window.map.removeLayer(truck.traveledLayer);
//             truck.traveledLayer = L.polyline(traveledPoints, {
//                 color: '#9ca3af',
//                 weight: 4,
//                 opacity: 0.6,
//                 lineJoin: 'round',
//                 lineCap: 'round'
//             }).addTo(window.map);
            
//             // Update position index
//             let currentIndex = truck.currentPointIndex;
//             let accumulatedDistance = 0;
//             for (let i = truck.currentPointIndex; i < truck.simulationPoints.length - 1; i++) {
//                 const p1 = truck.simulationPoints[i];
//                 const p2 = truck.simulationPoints[i + 1];
//                 const segDist = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
//                 if (accumulatedDistance + segDist >= distanceTraveled) {
//                     currentIndex = i;
//                     break;
//                 }
//                 accumulatedDistance += segDist;
//                 currentIndex = i + 1;
//             }
            
//             truck.currentPointIndex = currentIndex;
            
//             if (currentTime - lastUpdateTime > 2000 && currentIndex !== lastPositionIndex) {
//                 lastUpdateTime = currentTime;
//                 lastPositionIndex = currentIndex;
//                 const progressPercent = (currentIndex / truck.simulationPoints.length) * 100;
//                 await updateRouteStatus(routeId, 'moving', currentIndex, lat, lng, progressPercent);
//             }
            
//             updateRouteInfoCard();
//         }
        
//         truck.animationFrame = requestAnimationFrame(animate);
//     };
    
//     truck.animationFrame = requestAnimationFrame(animate);
// }

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

async function stopSimulationForRoute(routeId) {
    const truck = activeTrucks.get(routeId);
    if (!truck) return;
    
    if (truck.animationFrame) cancelAnimationFrame(truck.animationFrame);
    if (truck.realTimeLocationWatch) clearInterval(truck.realTimeLocationWatch);
    if (truck.routeLayer) window.map.removeLayer(truck.routeLayer);
    if (truck.traveledLayer) window.map.removeLayer(truck.traveledLayer);
    if (truck.animatedVehicle) window.map.removeLayer(truck.animatedVehicle);
    
    truck.isSimulating = false;
    await updateRouteStatus(routeId, 'inactive');
    activeTrucks.delete(routeId);
    

    updateRouteInfoCard();
    showToast(`Truck ${routeId} simulation stopped`, 'info');
}

function stopAllSimulations() {
    for (const [routeId] of activeTrucks) {
        stopSimulationForRoute(routeId);
    }
}

function createFireTruckIcon(color = '#dc2626') {
    // Using a data URI (embedded image) - ALWAYS works, no external requests
    const svgTruck = `
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="8" width="20" height="10" rx="2" fill="#dc2626" stroke="#991b1b" stroke-width="1.5"/>
            <rect x="5" y="4" width="14" height="4" fill="#b91c1c" stroke="#991b1b" stroke-width="1"/>
            <circle cx="7" cy="18" r="2" fill="#1f2937" stroke="#374151" stroke-width="1"/>
            <circle cx="17" cy="18" r="2" fill="#1f2937" stroke="#374151" stroke-width="1"/>
            <rect x="8" y="6" width="8" height="2" fill="#f59e0b"/>
            <circle cx="12" cy="5" r="1.5" fill="#ef4444"/>
        </svg>
    `;
    
    const dataUrl = 'data:image/svg+xml,' + encodeURIComponent(svgTruck);
    
    return L.icon({
        iconUrl: dataUrl,
        iconSize: [48, 48],
        iconAnchor: [24, 24],
        popupAnchor: [0, -24],
        className: 'fire-truck-icon'
    });
}
// function createFireTruckIcon(color = '#dc2626') {
//     return L.divIcon({
//         className: 'fire-truck-marker',
//         html: `
//             <div style="position: relative; width: 40px; height: 40px;">
//                 <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: ${color}; border-radius: 8px; transform: rotate(45deg); box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>
//                 <div style="position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; background: #fff; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
//                     <i class="fa-solid fa-truck-fire" style="color: ${color}; font-size: 16px;"></i>
//                 </div>
//                 <div style="position: absolute; bottom: 2px; left: 5px; width: 8px; height: 8px; background: #1f2937; border-radius: 50%;"></div>
//                 <div style="position: absolute; bottom: 2px; right: 5px; width: 8px; height: 8px; background: #1f2937; border-radius: 50%;"></div>
//                 <div style="position: absolute; top: -5px; right: -5px; width: 12px; height: 12px; background: #f59e0b; border-radius: 50%; animation: blink 0.5s infinite;"></div>
//             </div>
//         `,
//         iconSize: [40, 40],
//         iconAnchor: [20, 20],
//         popupAnchor: [0, -20]
//     });
// }

// ============================================================================
// ROUTE POLLING
// ============================================================================
// function startRoutePolling() {
//     if (routePollingInterval) clearInterval(routePollingInterval);
    
//     routePollingInterval = setInterval(async () => {
//         try {
//             const response = await fetch('/api/route/active');
//             if (!response.ok) return;
            
//             const routes = await response.json();
            
//             for (const routeData of routes) {
//                 if (activeTrucks.has(routeData.route_id)) continue;
                
//                 console.log('Found active route:', routeData.route_id, routeData.status);
                
//                 // Parse coordinates - handle triple-nested string
//                 let coordinates = routeData.coordinates;
//                 if (typeof coordinates === 'string') {
//                     try {
//                         coordinates = JSON.parse(coordinates);
//                     } catch (e) {
//                         console.error('Failed to parse coordinates:', e);
//                         continue;
//                     }
//                 }
                
//                 // Handle triple nested array
//                 if (Array.isArray(coordinates) && coordinates.length > 0 && 
//                     Array.isArray(coordinates[0]) && coordinates[0].length > 0 && 
//                     Array.isArray(coordinates[0][0])) {
//                     coordinates = coordinates[0];
//                     console.log('Flattened triple-nested coordinates');
//                 }
                
//                 // Convert to lat/lng for Leaflet
//                 const latlngs = [];
//                 for (let i = 0; i < coordinates.length; i++) {
//                     const coord = coordinates[i];
//                     if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
//                         latlngs.push([coord[1], coord[0]]);
//                     }
//                 }
                
//                 if (latlngs.length === 0) {
//                     console.warn('No valid coordinates for route:', routeData.route_id);
//                     continue;
//                 }
                
//                 console.log(`Route ${routeData.route_id} has ${latlngs.length} points`);
                
//                 const truckData = {
//                     routeId: routeData.route_id,
//                     simulationPoints: latlngs,
//                     originalSimulationPoints: [...latlngs],
//                     currentPointIndex: routeData.current_position_index || 0,
//                     isSimulating: routeData.status === 'moving',
//                     routeDestination: { title: routeData.destination_title || 'Destination' },
//                     routeLayer: null,
//                     traveledLayer: null,
//                     animatedVehicle: null,
//                     animationFrame: null,
//                     realTimeLocationWatch: null
//                 };
                
//                 activeTrucks.set(routeData.route_id, truckData);
                
//                 const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//                 const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeData.route_id) % truckColors.length;
                
//                 truckData.routeLayer = L.polyline(latlngs, {
//                     color: truckColors[colorIndex],
//                     weight: 5,
//                     opacity: 0.8
//                 }).addTo(window.map);
                
//                 // Add start and end markers
//                 if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
//                     L.marker(latlngs[0], {
//                         icon: L.divIcon({
//                             className: 'start-marker',
//                             html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                             iconSize: [12, 12],
//                             iconAnchor: [6, 6]
//                         })
//                     }).bindPopup('Start Point').addTo(window.map);
//                 }
                
//                 if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
//                     L.marker(latlngs[latlngs.length - 1], {
//                         icon: L.divIcon({
//                             className: 'end-marker',
//                             html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                             iconSize: [12, 12],
//                             iconAnchor: [6, 6]
//                         })
//                     }).bindPopup(`Destination: ${routeData.destination_title}`).addTo(window.map);
//                 }
                
//                 // AUTO-START SIMULATION if status is active or moving
//                 if (routeData.status === 'moving' && routeData.current_position_index > 0) {
//                     truckData.currentPointIndex = routeData.current_position_index;
//                     await startSimulationFromPosition(routeData.route_id, routeData.current_position_index);
//                 } else if (routeData.status === 'active') {
//                     // Auto-start simulation for new routes
//                     console.log('Auto-starting simulation for route from polling:', routeData.route_id);
//                     await startSimulationForRoute(routeData.route_id);
//                 }
//             }
            
//             updateRouteInfoCard();
            
//         } catch (error) {
//             console.error('Error polling routes:', error);
//         }
//     }, 5000);
// }

function startRoutePolling() {
    if (routePollingInterval) clearInterval(routePollingInterval);
    
    // Poll every 1 second instead of 3 seconds for real-time tracking
    routePollingInterval = setInterval(async () => {
        try {
            const response = await fetch('/api/route/active');
            if (!response.ok) return;
            
            const routes = await response.json();
            console.log('Polling - found routes:', routes.length);
            
            for (const routeData of routes) {
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
                
                // Parse coordinates
                let coordinates = routeData.coordinates;
                if (typeof coordinates === 'string') {
                    try {
                        coordinates = JSON.parse(coordinates);
                    } catch (e) {
                        console.error('Failed to parse coordinates:', e);
                        continue;
                    }
                }
                
                // Handle triple nested array
                if (Array.isArray(coordinates) && coordinates.length > 0 && 
                    Array.isArray(coordinates[0]) && coordinates[0].length > 0 && 
                    Array.isArray(coordinates[0][0])) {
                    coordinates = coordinates[0];
                }
                
                // Convert to lat/lng for Leaflet
                const latlngs = [];
                for (let i = 0; i < coordinates.length; i++) {
                    const coord = coordinates[i];
                    if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
                        latlngs.push([coord[1], coord[0]]);
                    }
                }
                
                if (latlngs.length === 0) continue;
                
                console.log(`Route ${routeData.route_id} has ${latlngs.length} points`);
                
                // Get saved position from database
                const savedIndex = routeData.current_position_index || 0;
                console.log(`Saved position index: ${savedIndex}, total points: ${latlngs.length}`);
                
                const truckData = {
                    routeId: routeData.route_id,
                    simulationPoints: latlngs,
                    originalSimulationPoints: [...latlngs],
                    currentPointIndex: savedIndex,
                    isSimulating: routeData.status === 'moving',
                    routeDestination: { title: routeData.destination_title || 'Destination' },
                    routeLayer: null,
                    traveledLayer: null,
                    animatedVehicle: null,
                    animationFrame: null,
                    realTimeLocationWatch: null
                };
                
                activeTrucks.set(routeData.route_id, truckData);
                
                const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
                const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeData.route_id) % truckColors.length;
                
                // Draw the route line
                truckData.routeLayer = L.polyline(latlngs, {
                    color: truckColors[colorIndex],
                    weight: 5,
                    opacity: 0.8
                }).addTo(window.map);
                
                // Add start and end markers
                if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
                    L.marker(latlngs[0], {
                        icon: L.divIcon({
                            className: 'start-marker',
                            html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6]
                        })
                    }).bindPopup('Start Point').addTo(window.map);
                }
                
                if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
                    L.marker(latlngs[latlngs.length - 1], {
                        icon: L.divIcon({
                            className: 'end-marker',
                            html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6]
                        })
                    }).bindPopup(`Destination: ${routeData.destination_title}`).addTo(window.map);
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
            
            updateRouteInfoCard();
            
        } catch (error) {
            console.error('Error polling routes:', error);
        }
    }, 1000); // Poll every 1 second for real-time updates
}
// function startRoutePolling() {
//     if (routePollingInterval) clearInterval(routePollingInterval);
    
//     routePollingInterval = setInterval(async () => {
//         try {
//             const response = await fetch('/api/route/active');
//             if (!response.ok) return;
            
//             const routes = await response.json();
//             console.log('Polling - found routes:', routes.length);
            
//             for (const routeData of routes) {
//                 // Skip if we already have this route
//                 //coomment muna
//                 if (activeTrucks.has(routeData.route_id)) {
//                     console.log('Route already in activeTrucks, skipping');
//                     continue;
//                 }
                
//                 console.log('Loading route from DB:', routeData.route_id, 'status:', routeData.status, 'position:', routeData.current_position_index);
                
//                 // Parse coordinates
//                 let coordinates = routeData.coordinates;
//                 if (typeof coordinates === 'string') {
//                     try {
//                         coordinates = JSON.parse(coordinates);
//                     } catch (e) {
//                         console.error('Failed to parse coordinates:', e);
//                         continue;
//                     }
//                 }
                
//                 // Handle triple nested array
//                 if (Array.isArray(coordinates) && coordinates.length > 0 && 
//                     Array.isArray(coordinates[0]) && coordinates[0].length > 0 && 
//                     Array.isArray(coordinates[0][0])) {
//                     coordinates = coordinates[0];
//                 }
                
//                 // Convert to lat/lng for Leaflet
//                 const latlngs = [];
//                 for (let i = 0; i < coordinates.length; i++) {
//                     const coord = coordinates[i];
//                     if (coord && coord.length >= 2 && coord[0] !== null && coord[1] !== null) {
//                         latlngs.push([coord[1], coord[0]]);
//                     }
//                 }
                
//                 if (latlngs.length === 0) {
//                     console.warn('No valid coordinates for route:', routeData.route_id);
//                     continue;
//                 }
                
//                 console.log(`Route ${routeData.route_id} has ${latlngs.length} points`);
                
//                 const truckData = {
//                     routeId: routeData.route_id,
//                     simulationPoints: latlngs,
//                     originalSimulationPoints: [...latlngs],
//                     currentPointIndex: routeData.current_position_index || 0,
//                     isSimulating: routeData.status === 'moving' ||  routeData.status === 'active',
//                     routeDestination: { title: routeData.destination_title || 'Destination' },
//                     routeLayer: null,
//                     traveledLayer: null,
//                     animatedVehicle: null,
//                     animationFrame: null,
//                     realTimeLocationWatch: null
//                 };
//                 console.log(`Loading route ${routeData.route_id} with position index: ${routeData.current_position_index}`);
//                 activeTrucks.set(routeData.route_id, truckData);
                
//                 const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//                 const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeData.route_id) % truckColors.length;
                
//                 // Draw the route line
//                 truckData.routeLayer = L.polyline(latlngs, {
//                     color: truckColors[colorIndex],
//                     weight: 5,
//                     opacity: 0.8
//                 }).addTo(window.map);
                
//                 // Add start and end markers
//                 if (latlngs[0] && latlngs[0][0] && latlngs[0][1]) {
//                     L.marker(latlngs[0], {
//                         icon: L.divIcon({
//                             className: 'start-marker',
//                             html: '<div style="background: #22c55e; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                             iconSize: [12, 12],
//                             iconAnchor: [6, 6]
//                         })
//                     }).bindPopup('Start Point').addTo(window.map);
//                 }
                
//                 if (latlngs[latlngs.length - 1] && latlngs[latlngs.length - 1][0] && latlngs[latlngs.length - 1][1]) {
//                     L.marker(latlngs[latlngs.length - 1], {
//                         icon: L.divIcon({
//                             className: 'end-marker',
//                             html: '<div style="background: #ef4444; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>',
//                             iconSize: [12, 12],
//                             iconAnchor: [6, 6]
//                         })
//                     }).bindPopup(`Destination: ${routeData.destination_title}`).addTo(window.map);
//                 }
                
//                 // IMPORTANT: If status is 'moving', resume from saved position WITHOUT resetting
//                 if (routeData.status === 'moving') {
//                     console.log('Resuming moving route from position:', routeData.current_position_index);
//                     await startSimulationFromPosition(routeData.route_id, routeData.current_position_index);
//                 }
//                 // If status is 'active', start fresh
//                 else if (routeData.status === 'active') {
//                     console.log('Starting new active route:', routeData.route_id);
//                     await startSimulationForRoute(routeData.route_id);
//                 }
//             }
            
//             updateRouteInfoCard();
            
//         } catch (error) {
//             console.error('Error polling routes:', error);
//         }
//     }, 3000);
// }
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
    if (truck.animatedVehicle) {
        window.map.removeLayer(truck.animatedVehicle);
    }
    
    // Create truck at saved position
    console.log('Creating truck at position:', [startPoint[0], startPoint[1]]);
    truck.animatedVehicle = L.marker([startPoint[0], startPoint[1]], {
        icon: createFireTruckIcon(truckColors[colorIndex]),
        zIndexOffset: 1000
    }).addTo(window.map);
    truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
    // Create traveled path from beginning to current position
    const traveledPoints = [];
    for (let i = 0; i <= startIndex; i++) {
        if (truck.simulationPoints[i]) {
            traveledPoints.push(truck.simulationPoints[i]);
        }
    }
    
    console.log(`Creating traveled path with ${traveledPoints.length} points`);
    
    if (truck.traveledLayer) {
        window.map.removeLayer(truck.traveledLayer);
    }
    if (traveledPoints.length > 1) {
        truck.traveledLayer = L.polyline(traveledPoints, {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(window.map);
    } else {
        truck.traveledLayer = L.polyline([], {
            color: '#9ca3af',
            weight: 4,
            opacity: 0.6
        }).addTo(window.map);
    }
    
    // Start animation from current position
    startSmoothAnimationForTruck(routeId);
    startRealTimeLocationUpdatesForTruck(routeId);
    
    console.log(`Resumed truck ${routeId} from position ${startIndex} (${(startIndex/truck.simulationPoints.length*100).toFixed(1)}%)`);
}
// async function startSimulationFromPosition(routeId, startIndex) {
//     console.log('Starting simulation from position:', startIndex);
//     const truck = activeTrucks.get(routeId);
//     if (!truck) return;
    
//     console.log('First 5 points:', truck.simulationPoints.slice(0, 5));
//     console.log('Point at index 73:', truck.simulationPoints[73]);
//     console.log('Last 5 points:', truck.simulationPoints.slice(-5));

//     truck.currentPointIndex = startIndex;
//     truck.isSimulating = true;
    
//         const startPoints = truck.simulationPoints[startIndex];
//     const lastPoint = truck.simulationPoints[truck.simulationPoints.length - 1];
//     console.log('Start point lat/lng:', startPoints);
//     console.log('Last point lat/lng:', lastPoint);
//         if (startPoints[0] === lastPoint[0] && startPoints[1] === lastPoint[1]) {
//         console.warn('WARNING: Start point equals last point! Index may be wrong.');
//     }
//     const startPoint = truck.simulationPoints[startIndex];
//     if (!startPoint || !startPoint[0] || !startPoint[1]) {
//         console.error('Invalid start point for route:', routeId);
//         return;
//     }
    
//     const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//     const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
//     // Remove existing vehicle if any
//     if (truck.animatedVehicle) {
//         window.map.removeLayer(truck.animatedVehicle);
//     }
    
//     // Create truck at saved position
//     console.log('Creating truck at saved position:', [startPoint[0], startPoint[1]]);
//     truck.animatedVehicle = L.marker([startPoint[0], startPoint[1]], {
//         icon: createFireTruckIcon(truckColors[colorIndex]),
//         zIndexOffset: 1000
//     }).addTo(window.map);
//     truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
//     // Create traveled path from beginning to current position
//     const traveledPoints = [];
//     for (let i = 0; i <= startIndex; i++) {
//         if (truck.simulationPoints[i]) {
//             traveledPoints.push(truck.simulationPoints[i]);
//         }
//     }
    
//     console.log(`Creating traveled path with ${traveledPoints.length} points`);
    
//     if (traveledPoints.length > 1) {
//         truck.traveledLayer = L.polyline(traveledPoints, {
//             color: '#9ca3af',
//             weight: 4,
//             opacity: 0.6,
//             lineJoin: 'round',
//             lineCap: 'round'
//         }).addTo(window.map);
//     } else {
//         truck.traveledLayer = L.polyline([], {
//             color: '#9ca3af',
//             weight: 4,
//             opacity: 0.6
//         }).addTo(window.map);
//     }
    
//     // Start animation from current position
//     startSmoothAnimationForTruck(routeId);
//     startRealTimeLocationUpdatesForTruck(routeId);
    
//     console.log(`Resumed truck ${routeId} from position ${startIndex} (${(startIndex/truck.simulationPoints.length*100).toFixed(1)}%)`);
// }
////////above is the current//////
// async function startSimulationFromPosition(routeId, startIndex) {
//     const truck = activeTrucks.get(routeId);
//     if (!truck) return;
    
//     truck.currentPointIndex = startIndex;
//     truck.isSimulating = true;
    
//     const startPoint = truck.simulationPoints[startIndex];
//     if (!startPoint || !startPoint[0] || !startPoint[1]) {
//         console.error('Invalid start point for route:', routeId);
//         return;
//     }
    
//     const truckColors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4'];
//     const colorIndex = Array.from(activeTrucks.keys()).indexOf(routeId) % truckColors.length;
    
//     truck.animatedVehicle = L.marker([startPoint[0], startPoint[1]], {
//         icon: createFireTruckIcon(truckColors[colorIndex]),
//         zIndexOffset: 1000
//     }).addTo(window.map);
//     truck.animatedVehicle.bindPopup(`🚒 Truck ${routeId} - En Route to ${truck.routeDestination?.title}`);
    
//     truck.traveledLayer = L.polyline([], {
//         color: '#9ca3af',
//         weight: 4,
//         opacity: 0.6
//     }).addTo(window.map);
    
//     startSmoothAnimationForTruck(routeId);
//     startRealTimeLocationUpdatesForTruck(routeId);
// }

// Keep all your existing functions below (startSimulation, updateRemainingRoute, etc.) unchanged
// ... (rest of your existing functions remain exactly as they were)

// ============================================================================
// HELPER FUNCTIONS (keep all your existing helper functions)
// ============================================================================

function getActionButtons(coord, incidents) {
    const marker = coord.marker || coord;
    if (marker.markerCategory_id == 2 || marker.markerCategory_id == 3) {
        const matchingIncident = incidents.find(incident => incident.marker?.marker_id === marker.marker_id);
        if (matchingIncident && matchingIncident.status === "reported") {
            return `<button id="respond${marker.marker_id}" class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 flex items-center gap-1">
                        <i class="fas fa-phone-alt"></i> Respond
                    </button>`;
        }
        return "";
    } else {
        return `<div style="display:flex; gap: 4px;">
                    <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1.5 rounded" id="edit${marker.marker_id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button id="delete${marker.marker_id}" class="bg-red-600 text-white text-xs px-2 py-1.5 rounded" style="width: 40%;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>`;
    }
}

function addMarkerToFeatureGroup(categoryId, marker) {
    switch (parseInt(categoryId)) {
        case 1: stationFeatureGroup.addLayer(marker); break;
        case 2:
        case 3: incidentFeatureGroup.addLayer(marker); break;
        case 4: hydrantFeatureGroup.addLayer(marker); break;
        default: otherFeatureGroup.addLayer(marker);
    }
}

function addPopupEventListeners(coord) {
    const marker = coord.marker || coord;
    setTimeout(() => {
        if(roleToken == 1){
            const deleteBtn = document.getElementById('delete' + marker.marker_id);
            const editBtn = document.getElementById('edit' + marker.marker_id);
            const respondBtn = document.getElementById('respond' + marker.marker_id);
            if (deleteBtn) deleteBtn.addEventListener('click', () => deleteMarker(marker.marker_id));
            if (editBtn) editBtn.addEventListener('click', () => openEditModal(marker));
            if (respondBtn) respondBtn.addEventListener('click', () => respondIncident(marker));
        }

        const viewDetailsBtn = document.getElementById('viewDetails' + marker.marker_id);
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', () => {
                const imagePath = coord.image?.path ? '/storage/' + coord.image.path : (marker.multimedia?.path ? '/storage/' + marker.multimedia.path : null);
                const markerData = { ...marker, category: getCategoryName(marker.markerCategory_id), created_at: coord.created_at || marker.created_at };
                showDetailsPanel(markerData, imagePath);
            });
        }
    }, 100);
}

function getCategoryName(categoryId) {
    const categoryMap = { 1: 'Fire Station', 2: 'Incident', 3: 'Incident Location', 4: 'Fire Hydrant', 5: 'School', 6: 'Evacuation Center' };
    return categoryMap[categoryId] || 'Unknown';
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
        const markerRes = await fetch('/api/markers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ lat, lng, title, description, markerCategory_id: categoryId, status_id: 1, multimedia_id: 1 })
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
        toggleBtn.classList.toggle('bg-purple-600', !manageShapesMode);
        toggleBtn.classList.toggle('bg-green-600', manageShapesMode);
    }
    window.map.getContainer().style.cursor = manageShapesMode ? 'pointer' : '';
    showNotification(`Shape management ${manageShapesMode ? 'enabled' : 'disabled'}`, 'info');
}

function onShapeClick(shapeId, layer, latlng) {
    if (!manageShapesMode) return;
    selectedShapeForEdit = { id: shapeId, layer: layer, name: layer.shapeName };
    const popupContent = `
        <div style="min-width: 200px; padding: 8px;">
            <strong class="block text-center text-sm mb-2">${layer.shapeName || 'Shape'}</strong>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="shapeEditBtn" class="bg-blue-600 text-white text-xs px-4 py-2 rounded">Edit</button>
                <button id="shapeDeleteBtn_${shapeId}" class="bg-red-600 text-white text-xs px-4 py-2 rounded">Delete</button>
            </div>
        </div>
    `;
    L.popup().setLatLng(latlng).setContent(popupContent).openOn(window.map).on('popupopen', function() {
        setTimeout(() => {
            const editBtn = document.getElementById('shapeEditBtn');
            const deleteBtn = document.getElementById(`shapeDeleteBtn_${shapeId}`);
            if (editBtn) editBtn.addEventListener('click', (e) => { e.stopPropagation(); editShape(selectedShapeForEdit); });
            if (deleteBtn) deleteBtn.addEventListener('click', (e) => { e.stopPropagation(); deleteShape(shapeId); });
        }, 100);
    });
}

function editShape(shapeData) {
    const newName = prompt('Enter new name for shape:', shapeData.name);
    if (!newName || newName === shapeData.name) return;
    fetch(`/api/shapes/${shapeData.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ name: newName })
    })
    .then(async res => {
        if (!res.ok) throw new Error(await res.text());
        return res.json();
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
    if (!confirm('Are you sure you want to delete this shape?')) return;
    fetch(`/api/shapes/${shapeId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' }
    })
    .then(async res => {
        if (!res.ok) throw new Error(`Server error ${res.status}`);
        return res.json();
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

function saveCategory(name) {
    fetch('/api/marker-categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ marker_category: name })
    })
    .then(res => res.json())
    .then(data => {
        showNotification('Category saved successfully!', 'success');
        if (typeof closeAddCategory === 'function') closeAddCategory();
    })
    .catch(err => {
        console.error(err);
        showNotification('Error saving category', 'error');
    });
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
    }
    
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
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
    
    L.control.layers({ "Street Map": street, "Satellite": satellite }, {
        "🚉 Stations": stationFeatureGroup,
        "🔥 Incidents": incidentFeatureGroup,
        "💧 Hydrants": hydrantFeatureGroup,
        "📍 Other Markers": otherFeatureGroup,
        "🔷 Shapes": shapeFeatureGroup
    }, { collapsed: true, position: 'topright' }).addTo(window.map);
    
    if(roleToken == 1){    
        const shapeToggleBtn = document.getElementById('toggleShapeMode');
        if (shapeToggleBtn) {
            shapeToggleBtn.addEventListener('click', toggleShapeManagement);
        } else {
            const controlContainer = document.querySelector('.leaflet-control-layers');
            if (controlContainer) {
                const btn = document.createElement('button');
                btn.id = 'toggleShapeMode';
                btn.innerHTML = '<i class="fas fa-shapes"></i> Manage Shapes: OFF';
                btn.style.cssText = 'margin: 8px 5px; padding: 8px 12px; background: #8b5cf6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px;';
                btn.addEventListener('click', toggleShapeManagement);
                controlContainer.appendChild(btn);
            }
        }
        
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
    setInterval(fetchCoordinate, 10000);
    
    if(roleToken == 1){
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
    
    const saveCategoryBtn = document.getElementById('saveCategory');
    if (saveCategoryBtn) {
        saveCategoryBtn.addEventListener('click', function() {
            const markercategory = document.getElementById('marker_category');
            if (markercategory) saveCategory(markercategory.value);
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
            const title = titleInput ? titleInput.value : '';
            const description = descriptionInput ? descriptionInput.value : '';
            const categoryId = categorySelect ? categorySelect.value : '';
            
            if (!selectedLat || !selectedLng) { alert('Please click on the map to select a location first'); return; }
            if (!title) { alert('Please enter marker title'); return; }
            if (!categoryId) { alert('Please select a category'); return; }
            
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption?.getAttribute('data-category-name') || '';
            
            const originalText = newSaveBtn.innerHTML;
            newSaveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            newSaveBtn.disabled = true;
            
            try {
                const markerResponse = await fetch('/api/markers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ title, description, lat: selectedLat, lng: selectedLng, markerCategory_id: categoryId, status_id: 1, multimedia_id: 1 })
                });
                const markerResult = await markerResponse.json();
                const markerId = markerResult.data?.marker_id || markerResult.marker_id;
                if (!markerId) throw new Error('Failed to create marker');
                
                if (categoryName.toLowerCase() === 'fire station') {
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
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ street, barangay_id: barangayId, postal_id: null })
                    });
                    const addressResult = await addressResponse.json();
                    if (!addressResult.success) throw new Error(addressResult.message || 'Failed to create address');
                    
                    await fetch('/api/station', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ station_name: stationName, station_code: stationCode, contact_number: stationContact, email: stationEmail, address_id: addressResult.address_id, marker_id: markerId })
                    });
                } else if (categoryName.toLowerCase() === 'hydrant') {
                    const hydrantCode = document.getElementById('hydrantCode')?.value;
                    const hydrantStationId = document.getElementById('hydrantStation')?.value;
                    const hydrantTypeId = document.getElementById('hydrantType')?.value;
                    
                    if (!hydrantCode) { alert('Please enter hydrant code'); return; }
                    if (!hydrantStationId) { alert('Please select the station this hydrant belongs to'); return; }
                    
                    await fetch('/api/hydrant', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ hydrant_code: hydrantCode, station_id: hydrantStationId, marker_id: markerId, hydrant_type_id: hydrantTypeId || null })
                    });
                } else if (categoryName.toLowerCase() === 'school') {
                    const schoolName = document.getElementById('schoolName')?.value;
                    if (!schoolName) { alert('Please enter school name'); return; }
                    await fetch('/api/schools', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ school_name: schoolName, marker_id: markerId })
                    });
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
async function calculateRouteToIncident(incidentId) {
    console.log('calculateRouteToIncident called with ID:', incidentId);
    
    //update natin ung incident to incident_status_id pag nag stop na ung truck to number 2 -responding
    
    // Get incident data from window.incidentsData
    console.log(window.incidentsData);
    const incident = window.incidentsData?.find(inc => inc.incident_id == incidentId);
    console.log('Looking for incident with ID:', incidentId, 'Found:', incident);
    if (!incident) {
        console.error('Incident not found with ID:', incidentId);
        showToast('Incident not found', 'error');
        return;
    }
    
    console.log('Found incident:', incident);
    
    // Get coordinates from incident marker
    // The incident may have marker data or direct lat/lng
    let lat, lng;
    
    if (incident.marker) {
        lat = incident.marker.lat;
        lng = incident.marker.lng;
    } else if (incident.lat && incident.lng) {
        lat = incident.lat;
        lng = incident.lng;
    } else if (incident.marker_lat && incident.marker_lng) {
        lat = incident.marker_lat;
        lng = incident.marker_lng;
    }
    
    if (!lat || !lng) {
        console.error('No coordinates for incident:', incident);
        showToast('Incident coordinates not available', 'error');
        return;
    }
    
    console.log(window.userCoords);
    if (!window.userCoords || !window.userCoords.ready) {
        showToast('Getting your location... Please wait.', 'warning');
        return;
    }
    
    // Get incident type name for better display
    let incidentType = incident.incident_type || 'Incident';
    if (incident.incident_type_id && window.incidentTypes) {
        const typeObj = window.incidentTypes.find(t => t.incident_type_id == incident.incident_type_id);
        if (typeObj) incidentType = typeObj.type;
    }
    
    const destination = {
        lat: parseFloat(lat),
        lng: parseFloat(lng),
        title: `${incidentType} - ${incident.title || 'Emergency'}`
    };
    
    console.log('Calculating route to incident:', destination);
    const origin = window.userCoords;
    await calculateRouteWithGeoapify(origin, destination);
}



window.closeModal = function() {
    const modal = document.getElementById('markerModal');
    if (modal) modal.style.display = 'none';
    closeModal();
};

window.openModal = function() {
    const titleInput = document.getElementById('markerTitle');
    const descriptionInput = document.getElementById('markerDescription');
    const categorySelect = document.getElementById('mCategory');
    const stationFieldsDiv = document.getElementById('stationFields');
    const hydrantFieldsDiv = document.getElementById('hydrantFields');
    const schoolFieldsDiv = document.getElementById('schoolFields');
    
    if (titleInput) titleInput.value = '';
    if (descriptionInput) descriptionInput.value = '';
    if (categorySelect) categorySelect.value = '';
    if (stationFieldsDiv) stationFieldsDiv.classList.remove('show');
    if (hydrantFieldsDiv) hydrantFieldsDiv.classList.remove('show');
    if (schoolFieldsDiv) schoolFieldsDiv.classList.remove('show');
    
    const modal = document.getElementById('markerModal');
    if (modal) modal.style.display = 'flex';
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

  // Incident card click
        //     document.querySelectorAll('.incident-card').forEach(card => {
        //         card.addEventListener('click', function(e) {
        //             if (!e.target.closest('button')) {
        //                 const id = this.dataset.id;
        //                 focusOnIncident(parseInt(id));
        //             }
        //         });
        //     });
        //      function focusOnIncident(id) {
        //     const marker = markers.find(m => m.id === id);
        //     if (marker) {
        //         incidentMap.setView([marker.lat, marker.lng], 16);
        //         marker.marker.openPopup();
        //     }
        // }
// Make new functions globally available
// window.stopSimulationForRoute = stopSimulationForRoute;
// window.stopAllSimulations = stopAllSimulations;
// window.calculateRouteToIncident = calculateRouteToIncident;
// console.log('Map.js fully loaded - Multi-truck support enabled');
// console.log('calculateRouteToStation available:', typeof window.calculateRouteToStation);

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