import './bootstrap';
import { station, getRoute, setName, getStations, getRouteDistance } from './functions.js';
// import { calculateRouteToStation  } from './firefighter/dashboard.js';
console.log('🔥🔥🔥 MAP.JS IS LOADED! 🔥🔥🔥');
console.log('Timestamp:', new Date().toISOString());

// ============================================================================
// OUTSIDE DOMContentLoaded - GLOBAL VARIABLES AND FUNCTIONS
// ============================================================================
// const ff = new Firefighter();
// Global variables

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
let isDrawing = false;              // Drawing state flag
let drawnItems;                     // Will be initialized inside DOM
let shapeLayers = {};               // Store shape layers by ID
let selectedShapeId = null;         // Currently selected shape
let startMarker = null;             // For map click marker
let endMarker = null;
let routeLayer = null;
let stations= [];
// Tracking variables
let firefighterMarkers = {};        // Store markers for each firefighter
let animationIntervals = {};        // Store animation intervals
let routeLayers = {};               // Store route layers

// Shape management variables
let manageShapesMode = false;        // Toggle for shape management mode
let selectedShapeForEdit = null;     // Currently selected shape for editing
let shapeFeatureGroup;               // Will be initialized inside DOM

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
let activeRoute = null;
// let routeLayer = null;
      let remainingRouteLayer = null; // New layer for remaining route
       let currentIncident = null;
      let watchId = null;
      let updateInterval = null;
       let incidentInterval = null;
      let simulationInterval = null;
       let simulationPoints = [];
      let currentPointIndex = 0;
      
        
        // Animation properties
      let  animatedVehicle = null;
       let animationFrame = null;
      let  animationStartTime = null;
      let  animationDuration = null;
      let  currentAnimationPoint = 0;
       let currentDestinationType = null;
       let routeDestination = null;
        
        // New properties for real-time updates
      let realTimeLocationWatch = null;
      let isSimulating = false;
      let simulationSpeed = 1;
       let lastLocationUpdate = null;
        
        // Store original route points
      let  originalSimulationPoints = [];
      let traveledPoints = [];
      window.remainingPoints = [];

        // Initialize marker layers
       let stationMarkers = null;
       let incidentMarkers = null;
       let assignedIncidentMarkers = null;
       let userMarker = null;
      let  routeMarkers = [];
        let distanceMarkers = [];

 window.calculateRouteToStation = calculateRouteToStation;
window.activeRoute = null;
window.userCoords = {
    currentLatitude: null,
    currentLongitude: null,
    ready: false
};

// ============================================================================
// ROUTE DISPLAY ELEMENTS - Create dynamic route info card
// ============================================================================
function getIncidentTypeName(typeId) {
    let data ='';
    window.incidentTypes.forEach(type => {
        console.log('Checking type:', type);
        if(type.incident_type_id === typeId || type.incindent_type_id == typeId) {
            console.log('Found matching type:', type.type);
            
           data =  type.type;
           
        }
    });
    // const incidentTypes = {
    //     1: 'Fire',
    //     2: 'Medical Emergency',
    //     3: 'Natural Disaster',
    //     4: 'Accident',
    //     5: 'Other'
    // };
    return data || 'Incident';
}


function createRouteInfoCard() {
    // Check if route info card already exists
    if (document.getElementById('routeInfoCard')) {
        return;
    }
    
    // Find the map container or a suitable parent element
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;
    
    // Create the route info card
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
                Active Route
            </h3>
            <button id="stopSimulationBtn" class="text-sm text-red-600 hover:text-red-700" style="background: none; border: none; cursor: pointer;">
                <i class="fas fa-stop"></i> Stop
            </button>
        </div>
        <div class="route-stats" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="route-stat" style="text-align: center;">
                <div class="route-stat-label" style="font-size: 11px; color: #6b7280;">Distance</div>
                <div class="route-stat-value" id="routeDistance" style="font-size: 18px; font-weight: bold; color: #1f2937;">-- km</div>
            </div>
            <div class="route-stat" style="text-align: center;">
                <div class="route-stat-label" style="font-size: 11px; color: #6b7280;">Duration</div>
                <div class="route-stat-value" id="routeDuration" style="font-size: 18px; font-weight: bold; color: #1f2937;">-- min</div>
            </div>
            <div class="route-stat" style="text-align: center;">
                <div class="route-stat-label" style="font-size: 11px; color: #6b7280;">ETA</div>
                <div class="route-stat-value" id="routeETA" style="font-size: 18px; font-weight: bold; color: #1f2937;">--:--</div>
            </div>
        </div>
        <div id="destinationInfo" style="font-size: 12px; color: #6b7280; margin-bottom: 12px; text-align: center;">
            Destination: <span id="destinationTitle">Loading...</span>
        </div>
        <div class="route-progress-container" style="margin-top: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden; height: 6px;">
            <div id="routeProgress" class="route-progress-bar" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.3s ease;"></div>
        </div>
        <div class="speed-control" style="margin-top: 12px; display: flex; gap: 8px; justify-content: center;">
            <button class="speed-btn" data-speed="slow" style="padding: 6px 12px; font-size: 12px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">🐢 Slow</button>
            <button class="speed-btn active" data-speed="normal" style="padding: 6px 12px; font-size: 12px; background: #3b82f6; color: white; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer;">🚒 Normal</button>
            <button class="speed-btn" data-speed="fast" style="padding: 6px 12px; font-size: 12px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">🐇 Fast</button>
        </div>
    `;
    
    // Add to map container's parent or body
    const mapParent = document.getElementById('map').parentElement;
    if (mapParent) {
        mapParent.style.position = 'relative';
        mapParent.appendChild(routeCard);
    } else {
        document.body.appendChild(routeCard);
    }
    
    // Add event listeners for speed buttons
    setTimeout(() => {
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
        
        const stopBtn = document.getElementById('stopSimulationBtn');
        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                stopSimulation();
                const routeCard = document.getElementById('routeInfoCard');
                if (routeCard) routeCard.style.display = 'none';
            });
        }
    }, 100);
}

// Global animation speed function (only declare once)
function setAnimationSpeedGlobal(speed) {
    if (animationFrame && simulationPoints && simulationPoints.length > 0 && isSimulating) {
        startSmoothAnimation();
    }
}

// Wait for DOM to be fully loaded before initializing map
document.addEventListener('DOMContentLoaded', function() {
    // Check if map container exists
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
    }
    
    // Initialize map only after DOM is ready
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
    
    // Assign to global variables
    window.stationFeatureGroup = stationFeatureGroup;
    window.incidentFeatureGroup = incidentFeatureGroup;
    window.hydrantFeatureGroup = hydrantFeatureGroup;
    window.otherFeatureGroup = otherFeatureGroup;
    
    window.setName = setName;
    window.routesGroup = L.layerGroup().addTo(window.map);
    if( !fetchCoordinate()) {console.log('loading') }else{console.log('loaded');}
    // Continue with rest of initialization
    initAfterMapReady();
    fetchStations();
});

// Colors for shapes
const colors = ["#ef4444", "#3b82f6", "#22c55e", "#f59e0b", "#8b5cf6", "#06b6d4"];

// Global variables that need to be accessible
let stationFeatureGroup, incidentFeatureGroup, hydrantFeatureGroup, otherFeatureGroup;
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const roleToken = document.querySelector('meta[name="user-role-id"]')?.getAttribute('content');

const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
window.geoapifyKey = apiKey;

// ============================================================================
// ICON ARRAY - Font Awesome based icons (keeping your existing icons)
// ============================================================================

const ICON_ARRAY = [
    // Emergency Response Icons (0-9)
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
    
    // Infrastructure Icons (10-19)
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
    
    // Resource Icons (20-29)
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
    
    // Organization Icons (30-39)
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
    
    // Natural Features (40-49)
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

// Category mapping for existing marker types
const CATEGORY_ICON_MAP = {
    1: 'station',      // Station
    2: 'incident',     // Incident
    3: 'incident',     // Incident Location
    4: 'hydrant',      // Hydrant
    5: 'school',       // School
    6: 'evacuation'    // Evacuation Center
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
    
    .custom-fa-icon {
        transition: all 0.3s ease;
    }
    
    .custom-fa-icon:hover {
        transform: scale(1.1);
        z-index: 1000 !important;
    }
    
    .speed-badge {
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-50%) translateY(5px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
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
        console.log(markerData);
        console.log(window.incidentsData);

        const incidentID = window.incidentsData.find(incident => incident.marker_id === markerData.marker_id)?.incident_id;
        console.log('Incident ID:', incidentID);
        const type = window.incidentsData.find(incident => incident.marker_id === markerData.marker_id)?.incident_type_id || 'N/A';
        console.log('Incident Type:', type);
        //fetch incidnt
        // const incidentTypeName = markerData.incident_type_name || markerData.incident_type || 'Fire';
        const sample = getIncidentTypeName(type);
        const incidentTypeName = sample ?? 'Fire';
        console.log('Incident Type Name:', incidentTypeName);
        let statusColor = '#ef4444';
        let statusIcon = 'fa-exclamation-triangle';
        
        let typeIcon = '🔥';
        let typeName = incidentTypeName;
        switch(incidentTypeName.toLowerCase()) {
            case 'fire':
                typeIcon = '🔥';
                typeName = 'Fire';
                break;
            case 'medical emergency':
                typeIcon = '🚑';
                typeName = 'Medical Emergency';
                break;
            case 'rescue':
                typeIcon = '🆘';
                typeName = 'Rescue Operation';
                break;
            case 'hazardous materials':
                typeIcon = '⚠️';
                typeName = 'Hazardous Materials';
                break;
            case 'false alarm':
                typeIcon = '❌';
                typeName = 'False Alarm';
                break;
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
        
        // Fetch incident types from incident_type table
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
                    // Find incident data for this marker
                    const incident = incidents.find(inc => inc.marker?.marker_id === item.marker_id);
                    if (incident) {
                        item.incident_status = incident.status;
                        // Find incident type name from incident_type table
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
            const response = await fetch('/api/stations', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch stations');
            
            const data = await response.json();
            stations = data.data || data;
            console.log('stations: ' , stations);
        } catch (error) {
            console.error('Error fetching stations:', error);
        }
    }

function generatePopupContent(coord, incidents) {
    const marker = coord.marker || coord;
    const imagePath = marker.multimedia?.path ? '/storage/' + marker.multimedia.path : (coord.image?.path ? '/storage/' + coord.image.path : null);
    
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
                ${roleToken != 1 ? `
            <button class="btn btn-sm btn-outline mt-2" onclick="window.calculateRouteToStation(${marker.marker_id})">
                    <i class="fa-solid fa-route"></i> Get Directions ${marker.marker_id}
                </button>
                `:' '}

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
        
        // Get icon and color based on incident type
        let typeIcon = '🔥';
        let typeColor = '#ef4444';
        let typeBgGradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        
        switch(incidentTypeName.toLowerCase()) {
            case 'fire':
                typeIcon = '🔥';
                typeColor = '#ef4444';
                typeBgGradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                break;
            case 'medical emergency':
                typeIcon = '🚑';
                typeColor = '#10b981';
                typeBgGradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                break;
            case 'rescue':
                typeIcon = '🆘';
                typeColor = '#f59e0b';
                typeBgGradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                break;
            case 'hazardous materials':
                typeIcon = '⚠️';
                typeColor = '#f97316';
                typeBgGradient = 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)';
                break;
            case 'false alarm':
                typeIcon = '❌';
                typeColor = '#6b7280';
                typeBgGradient = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                break;
        }
        
        // Status badge styling
        let statusBadge = '';
        let statusColor = '';
        
        switch(incidentStatus.toLowerCase()) {
            case 'reported':
                statusColor = '#ef4444';
                statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">REPORTED</span>';
                break;
            case 'responding':
                statusColor = '#f59e0b';
                statusBadge = '<span style="background: #f59e0b; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">RESPONDING</span>';
                break;
            case 'resolved':
                statusColor = '#10b981';
                statusBadge = '<span style="background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">RESOLVED</span>';
                break;
            default:
                statusColor = '#ef4444';
                statusBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">ACTIVE</span>';
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
    
    // Default popup for other marker types (including evacuation centers)
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
        const station = stations.find(s => s.id == stationId);
        if (!station || !window.userCoords.ready) return;
        
        const destination = {
            lat: station.lat || station.marker?.lat,
            lng: station.lng || station.marker?.lng,
            title: station.station_name || station.name || 'Fire Station'
        };
        console.log('calculating route to station:', destination);
        const origin = window.userCoords;
        await calculateRouteWithGeoapify(origin, destination);
       startSimulationForStation();
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
            //send sa database
            const coords = data.features[0].geometry.coordinates;
            fetch('/api/route',{
                method: 'POST',
                headers:{
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                   coordinates:  JSON.stringify(coords),
                    total_distance: data.features[0].properties.distance,
                    estimated_time: data.features[0].properties.time,
                    status: 'active',
                })

            }).then(res => res.json())
            .then(result => {  
                if(!result.ok){
                console.error('Failed to store route data:', result);
             }
            console.log('Saved Route:', result);

             });

             


            if (data.features && data.features.length > 0) {
                const route = data.features[0];
                window.activeRoute = route;
                console.log("displaying route data: ", route);
                displayGeoapifyRoute(route);
                updateRouteInfoFromGeoapify(route);
                
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
        
        const geometry = route.geometry;
        let latlngs = [];
        
        if (geometry.type === 'LineString') {
            latlngs = geometry.coordinates.map(coord => [coord[1], coord[0]]);
        } 
        else if (geometry.type === 'MultiLineString') {
            geometry.coordinates.forEach(segment => {
                const segmentLatLngs = segment.map(coord => [coord[1], coord[0]]);
                latlngs.push(...segmentLatLngs);
            });
        }
        else {
            console.error('Unknown geometry type:', geometry.type);
            return;
        }
        
        if (latlngs.length === 0) {
            console.error('No coordinates found in route');
            return;
        }
        
        console.log('Route points count:', latlngs.length);
        
        originalSimulationPoints = [...latlngs];
        window.remainingPoints = [...latlngs];
        
        routeLayer = L.polyline(latlngs, {
            color: '#3b82f6',
            weight: 5,
            opacity: 0.8,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(window.map);
        
        const bounds = routeLayer.getBounds();
        if (bounds.isValid()) {
            window.map.fitBounds(bounds, { padding: [50, 50] });
        }
        
        clearRouteMarkers();
        
        const startPoint = latlngs[0];
        const startMarker = L.marker(startPoint, {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #22c55e;"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).bindPopup('<strong>Start Point</strong><br>Your location').addTo(window.map);
        
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
        simulationPoints = latlngs;
        currentPointIndex = 0;
        
        console.log('Route displayed successfully with', latlngs.length, 'points');
        addDistanceMarkers(latlngs, route.properties.distance);
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
        const distance = route.properties.distance / 1000;
        const time = route.properties.time / 60;
        
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
            const eta = new Date(Date.now() + (route.properties.time * 1000));
            etaEl.textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        if (destinationTitle && routeDestination) {
            destinationTitle.textContent = routeDestination.title || 'Destination';
        }
        
        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard) {
            routeCard.style.display = 'block';
        }
        
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

// ==================== SIMULATION FUNCTIONS ====================
    
function startSimulation(incidentId) {
        if (!simulationPoints || simulationPoints.length === 0) {
            showToast('No route available for simulation', 'error');
            return;
        }
        
        if (simulationInterval) {
            clearInterval(simulationInterval);
            simulationInterval = null;
        }
        
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        
        currentPointIndex = 0;
        isSimulating = true;
        
        originalSimulationPoints = [...simulationPoints];
        traveledPoints = [];
        window.remainingPoints = [...simulationPoints];
        
        const incident = incidents.find(i => i.incident_id === incidentId);
        const destinationType = incident ? 'incident' : 'station';
        
        showToast(`Starting route simulation to ${destinationType}...`, 'info');
        
        if (!animatedVehicle) {
            const startPoint = simulationPoints[0];
            animatedVehicle = L.marker(startPoint, {
                icon: createFireTruckIcon(),
                zIndexOffset: 1000
            }).addTo(window.map);
        } else {
            animatedVehicle.setLatLng(simulationPoints[0]);
        }
        
        const popupText = incident ? '🚒 Fire Truck En Route to Incident' : '🚒 Fire Truck En Route to Station';
        animatedVehicle.bindPopup(popupText);
        
        removeStartMarker();
        
        userLocation = {
            lat: simulationPoints[0][0],
            lng: simulationPoints[0][1]
        };
        updateUserMarker();
        
        currentDestinationType = destinationType;
        
        startSmoothAnimation();
        startRealTimeLocationUpdates();
    }

function updateRemainingRoute(currentLat, currentLng) {
        if (!originalSimulationPoints || originalSimulationPoints.length === 0) return;
        
        let closestIndex = 0;
        let minDistance = Infinity;
        
        for (let i = 0; i < originalSimulationPoints.length; i++) {
            const point = originalSimulationPoints[i];
            const distance = calculateDistanceBetweenPoints(
                currentLat, currentLng,
                point[0], point[1]
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
            }
        }
        
        const remainingPoints = originalSimulationPoints.slice(closestIndex);
        
        if (routeLayer) {
            window.map.removeLayer(routeLayer);
        }
        
        if (window.remainingPoints.length >= 2) {
            routeLayer = L.polyline(remainingPoints, {
                color: '#3b82f6',
                weight: 5,
                opacity: 0.8,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(window.map);
            
            if (closestIndex > 0) {
                const traveledPoints = originalSimulationPoints.slice(0, closestIndex + 1);
                if (traveledPoints.length >= 2) {
                    const traveledLayer = L.polyline(traveledPoints, {
                        color: '#9ca3af',
                        weight: 4,
                        opacity: 0.5,
                        lineJoin: 'round',
                        lineCap: 'round',
                        className: 'traveled-path'
                    }).addTo(window.map);
                    
                    traveledLayer = traveledLayer;
                }
            }
        }
        
        window.remainingPoints = remainingPoints;
    }

function startRealTimeLocationUpdates() {
        if (realTimeLocationWatch) {
            clearInterval(realTimeLocationWatch);
        }
        
        realTimeLocationWatch = setInterval(() => {
            if (isSimulating && animatedVehicle) {
                const truckPosition = animatedVehicle.getLatLng();
                if (truckPosition) {
                    userLocation = {
                        lat: truckPosition.lat,
                        lng: truckPosition.lng
                    };
                    
                    updateUserMarker();
                    updateRemainingRoute(truckPosition.lat, truckPosition.lng);
                    
                    const locationDisplay = document.getElementById('userLocation');
                    if (locationDisplay) {
                        locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)} (Moving)`;
                    }
                    
                    if (simulationPoints && simulationPoints.length > 0) {
                        const remainingDistance = calculateRemainingDistance(truckPosition.lat, truckPosition.lng);
                        const distanceEl = document.getElementById('routeDistance');
                        if (distanceEl) {
                            distanceEl.textContent = `${(remainingDistance / 1000).toFixed(1)} km`;
                        }
                    }
                }
            }
        }, 500);
    }

function calculateRemainingDistance(currentLat, currentLng) {
        if (!originalSimulationPoints || originalSimulationPoints.length === 0) return 0;
        
        let closestIndex = 0;
        let minDistance = Infinity;
        
        for (let i = 0; i < originalSimulationPoints.length; i++) {
            const point = originalSimulationPoints[i];
            const distance = calculateDistanceBetweenPoints(
                currentLat, currentLng,
                point[0], point[1]
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
            }
        }
        
        let remainingDistance = 0;
        for (let i = closestIndex; i < originalSimulationPoints.length - 1; i++) {
            const p1 = originalSimulationPoints[i];
            const p2 = originalSimulationPoints[i + 1];
            remainingDistance += calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
        }
        
        return remainingDistance;
    }

function getSpeedMultiplier() {
        const activeSpeedBtn = document.querySelector('.speed-btn.active');
        if (activeSpeedBtn) {
            const speed = activeSpeedBtn.dataset.speed;
            switch(speed) {
                case 'slow': return 0.5;
                case 'fast': return 2;
                default: return 1;
            }
        }
        return 1;
    }

function startSmoothAnimation() {
        if (!simulationPoints || simulationPoints.length < 2) {
            console.error('Not enough points for animation');
            return;
        }
        
        const segments = [];
        let totalDistance = 0;
        
        for (let i = 0; i < simulationPoints.length - 1; i++) {
            const p1 = simulationPoints[i];
            const p2 = simulationPoints[i + 1];
            const distance = calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
            totalDistance += distance;
            segments.push({
                start: p1,
                end: p2,
                distance: distance,
                cumulativeDistance: totalDistance,
                angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
            });
        }
        
        const baseSpeed = 13.89;
        const speed = baseSpeed * getSpeedMultiplier();
        const totalDuration = totalDistance / speed;
        const startTime = performance.now();
        
        console.log(`Animation started: Total distance ${(totalDistance/1000).toFixed(2)}km, Duration ${totalDuration.toFixed(0)} seconds`);
        
        const animate = (currentTime) => {
            const elapsed = (currentTime - startTime) / 1000;
            let progress = Math.min(elapsed / totalDuration, 1);
            
            if (progress >= 1) {
                stopSimulation();
                
                if (currentDestinationType === 'incident' && currentIncident) {
                    showToast('You have arrived at the incident location!', 'success');
                    if (confirm('Have you completed the incident?')) {
                        completeIncident();
                    }
                } else if (currentDestinationType === 'station') {
                    showToast('You have arrived at the fire station!', 'success');
                    if (routeDestination) {
                        showToast(`Arrived at ${routeDestination.title}`, 'success');
                    }
                }
                
                if (realTimeLocationWatch) {
                    clearInterval(realTimeLocationWatch);
                    realTimeLocationWatch = null;
                }
                
                return;
            }
            
            const distanceTraveled = progress * totalDistance;
            
            let currentSegment = null;
            let segmentStartDistance = 0;
            
            for (let i = 0; i < segments.length; i++) {
                if (distanceTraveled <= segments[i].cumulativeDistance) {
                    currentSegment = segments[i];
                    segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
                    break;
                }
            }
            
            if (currentSegment) {
                const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
                
                const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
                const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
                
                if (animatedVehicle) {
                    animatedVehicle.setLatLng([lat, lng]);
                    
                    const truckElement = animatedVehicle.getElement();
                    if (truckElement) {
                        const truckDiv = truckElement.querySelector('div');
                        if (truckDiv) {
                            truckDiv.style.transform = `rotate(${currentSegment.angle}deg)`;
                            truckDiv.style.transition = 'transform 0.1s linear';
                        }
                    }
                }
                
                userLocation = { lat, lng };
                updateUserMarker();
                
                const remainingDistance = totalDistance - distanceTraveled;
                const remainingTime = remainingDistance / speed;
                
                const distanceEl = document.getElementById('routeDistance');
                const durationEl = document.getElementById('routeDuration');
                const etaEl = document.getElementById('routeETA');
                
                if (distanceEl) distanceEl.textContent = `${(remainingDistance / 1000).toFixed(1)} km`;
                if (durationEl) {
                    const minutes = Math.floor(remainingTime / 60);
                    const seconds = Math.floor(remainingTime % 60);
                    durationEl.textContent = minutes > 0 ? `${minutes} min ${seconds} sec` : `${seconds} sec`;
                }
                if (etaEl) {
                    const eta = new Date(Date.now() + (remainingTime * 1000));
                    etaEl.textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
                
                const progressBar = document.getElementById('routeProgress');
                if (progressBar) {
                    progressBar.style.width = `${progress * 100}%`;
                }
                
                const locationDisplay = document.getElementById('userLocation');
                if (locationDisplay && isSimulating) {
                    locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${lat.toFixed(6)}, ${lng.toFixed(6)} (Moving)`;
                }
                
                const mapCenter = window.map.getCenter();
                const truckLatLng = animatedVehicle.getLatLng();
                const distanceToCenter = calculateDistanceBetweenPoints(
                    mapCenter.lat, mapCenter.lng,
                    truckLatLng.lat, truckLatLng.lng
                );
                
                if (distanceToCenter > 200) {
                    window.map.panTo(truckLatLng, { animate: true, duration: 0.5 });
                }
            }
            
            animationFrame = requestAnimationFrame(animate);
        };
        
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
        }
        animationFrame = requestAnimationFrame(animate);
    }

function stopSimulation() {
        if (simulationInterval) {
            clearInterval(simulationInterval);
            simulationInterval = null;
        }
        
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        
        if (realTimeLocationWatch) {
            clearInterval(realTimeLocationWatch);
            realTimeLocationWatch = null;
        }
        
        isSimulating = false;
        
        if (originalSimulationPoints && originalSimulationPoints.length > 0 && !currentIncident) {
            if (routeLayer) {
                window.map.removeLayer(routeLayer);
            }
            if (traveledLayer) {
                window.map.removeLayer(traveledLayer);
                traveledLayer = null;
            }
            
            routeLayer = L.polyline(originalSimulationPoints, {
                color: '#3b82f6',
                weight: 5,
                opacity: 0.8,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(window.map);
        }
        
        if (animatedVehicle && simulationPoints && simulationPoints.length > 0) {
            const finalPoint = simulationPoints[simulationPoints.length - 1];
            animatedVehicle.setLatLng(finalPoint);
        }
        
        const locationDisplay = document.getElementById('userLocation');
        if (locationDisplay && userLocation) {
            locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}`;
        }
        
        showToast('Simulation stopped', 'info');
    }

function createFireTruckIcon() {
        return L.divIcon({
            className: 'fire-truck-marker',
            html: `
                <div style="position: relative; width: 40px; height: 40px;">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #dc2626; border-radius: 8px; transform: rotate(45deg); box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>
                    <div style="position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; background: #fff; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-truck-fire" style="color: #dc2626; font-size: 16px;"></i>
                    </div>
                    <div style="position: absolute; bottom: 2px; left: 5px; width: 8px; height: 8px; background: #1f2937; border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: 2px; right: 5px; width: 8px; height: 8px; background: #1f2937; border-radius: 50%;"></div>
                    <div style="position: absolute; top: -5px; right: -5px; width: 12px; height: 12px; background: #f59e0b; border-radius: 50%; animation: blink 0.5s infinite;"></div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
    }

function addAnimationStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes blink {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(1.2); }
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
                filter: url(#gray-filter);
            }
        `;
        document.head.appendChild(style);
    }

function startSimulationForStation() {
        if (!simulationPoints || simulationPoints.length === 0) {
            showToast('No route available for simulation', 'error');
            return;
        }
        
        if (simulationInterval) {
            clearInterval(simulationInterval);
            simulationInterval = null;
        }
        
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        
        currentPointIndex = 0;
        currentDestinationType = 'station';
        isSimulating = true;
        
        originalSimulationPoints = [...simulationPoints];
        traveledPoints = [];
        window.remainingPoints = [...simulationPoints];
        
        showToast('Starting route simulation to station...', 'info');
        
        if (!animatedVehicle) {
            const startPoint = simulationPoints[0];
            animatedVehicle = L.marker(startPoint, {
                icon: createFireTruckIcon(),
                zIndexOffset: 1000
            }).addTo(window.map);
            animatedVehicle.bindPopup('🚒 Fire Truck En Route to Station');
        } else {
            animatedVehicle.setLatLng(simulationPoints[0]);
            animatedVehicle.bindPopup('🚒 Fire Truck En Route to Station');
        }
        
        removeStartMarker();
        
        userLocation = {
            lat: simulationPoints[0][0],
            lng: simulationPoints[0][1]
        };
        updateUserMarker();
        
        startSmoothAnimation();
        startRealTimeLocationUpdates();
    }

function removeStartMarker() {
        if (routeMarkers && routeMarkers.length > 0) {
            const startMarker = routeMarkers[0];
            if (startMarker) {
                window.map.removeLayer(startMarker);
                routeMarkers.shift();
            }
        }
    }

function startLocationTracking() {
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
        }
        
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                if (!isSimulating) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    updateUserMarker();
                    
                    const locationDisplay = document.getElementById('userLocation');
                    if (locationDisplay) {
                        locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}`;
                    }
                }
            },
            (error) => console.error('Watch position error:', error),
            { 
                enableHighAccuracy: true, 
                timeout: 5000, 
                maximumAge: 0 
            }
        );
    }

function updateUserMarker() {
        if (userMarker && userLocation) {
            userMarker.setLatLng([userLocation.lat, userLocation.lng]);
            
            if (!activeRoute && !isSimulating) {
                window.map.panTo([userLocation.lat, userLocation.lng], {
                    animate: true,
                    duration: 0.5
                });
            }
            
            const statusText = isSimulating ? '(Moving Along Route)' : '';
            userMarker.bindPopup(`
                <div style="text-align: center;">
                    <strong>Your Location ${statusText}</strong><br>
                    <span class="text-xs">${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}</span>
                    ${isSimulating ? '<br><span class="text-xs text-blue-500">🚒 Moving along route</span>' : ''}
                </div>
            `);
        }
    }

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
// SHAPE MANAGEMENT FUNCTIONS
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
    
    // Create route info card if it doesn't exist
      if (!document.getElementById('routeInfoCard')) {
        createRouteInfoCard();  // This creates the route card
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
                } else if (categoryName.toLowerCase() === 'fire hydrant') {
                    const hydrantCode = document.getElementById('hydrantCode')?.value;
                    const hydrantStationId = document.getElementById('hydrantStation')?.value;
                    const hydrantTypeId = document.getElementById('hydrantType')?.value;
                    
                    if (!hydrantCode) { alert('Please enter hydrant code'); return; }
                    if (!hydrantStationId) { alert('Please select the station this hydrant belongs to'); return; }
                    
                    await fetch('/api/hydrants', {
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

console.log('Map.js fully loaded - all fixes applied');