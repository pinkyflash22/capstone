import './bootstrap';
import { station, getRoute, setName, getStations, getRouteDistance } from './functions.js';

console.log('🔥🔥🔥 MAP.JS IS LOADED! 🔥🔥🔥');
console.log('Timestamp:', new Date().toISOString());

// ============================================================================
// OUTSIDE DOMContentLoaded - GLOBAL VARIABLES AND FUNCTIONS
// ============================================================================

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

// Map initialization (needs to be outside because functions reference it)
window.map = L.map('map', {
    zoomControl: true,
    zoomAnimation: true,
    fadeAnimation: true,
    markerZoomAnimation: true
}).setView([15.8222, 120.3297], 13);

const stationFeatureGroup = L.featureGroup().addTo(map);
const incidentFeatureGroup = L.featureGroup().addTo(map);
const hydrantFeatureGroup = L.featureGroup().addTo(map);
const otherFeatureGroup = L.featureGroup().addTo(map);

window.setName = setName;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
window.routesGroup = L.layerGroup().addTo(map);

// Colors for shapes
const colors = ["#ef4444", "#3b82f6", "#22c55e", "#f59e0b", "#8b5cf6", "#06b6d4"];

// ============================================================================
// ICON ARRAY - Font Awesome based icons
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
    3: 'incident',     // Incident
    4: 'hydrant',      // Hydrant
    5: 'school',       // School
    6: 'evacuation'    // Evacuation Center
};

// ============================================================================
// HELPER FUNCTIONS FOR ICONS
// ============================================================================

/**
 * Create a Font Awesome icon as a Leaflet DivIcon
 */
function createFontAwesomeIcon(iconConfig, extraClass = '', customColor = null) {
    const bgColor = customColor || iconConfig.bgColor;
    const iconColor = iconConfig.color;
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
                transform: ${iconConfig.id === 'hydrant' ? 'none' : 'scale(1)'};
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

/**
 * Get icon by ID
 */
function getIconById(iconId, extraClass = '') {
    const iconConfig = ICON_ARRAY.find(icon => icon.id === iconId);
    if (!iconConfig) {
        console.warn(`Icon with id '${iconId}' not found, using default`);
        return getDefaultIcon();
    }
    return createFontAwesomeIcon(iconConfig, extraClass);
}

/**
 * Get icon by category ID (for existing markers)
 */
function getIconByCategoryId(categoryId, extraClass = '') {
    const iconId = CATEGORY_ICON_MAP[categoryId];
    if (iconId) {
        return getIconById(iconId, extraClass);
    }
    return getDefaultIcon();
}

/**
 * Get default icon
 */
function getDefaultIcon() {
    return L.divIcon({
        className: 'default-icon',
        html: '<div style="width: 24px; height: 24px; background-color: #6b7280; border-radius: 50%; border: 2px solid white;"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
}

/**
 * Get animated firefighter icon with speed
 */
function getFirefighterIconWithSpeed(speed = 0) {
    const iconConfig = ICON_ARRAY.find(icon => icon.id === 'firefighter');
    const speedValue = Math.round(speed);
    let speedColor = '#1e40af'; // Default blue
    
    if (speedValue > 60) {
        speedColor = '#dc2626'; // Red for high speed
    } else if (speedValue > 30) {
        speedColor = '#f59e0b'; // Orange for medium speed
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
const style = document.createElement('style');
style.textContent = `
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
    
    /* Notification styles */
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
    
    .notification.success {
        border-left-color: #10b981;
    }
    
    .notification.warning {
        border-left-color: #f59e0b;
    }
    
    .notification.error {
        border-left-color: #ef4444;
    }
    
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
    
    .notification-close:hover {
        color: #4b5563;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Details panel styles */
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
    
    .details-close:hover {
        opacity: 1;
    }
    
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
        width: 100px;
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
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// ============================================================================
// NOTIFICATION SYSTEM
// ============================================================================

/**
 * Initialize notification container
 */
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

/**
 * Show a notification
 */
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
    
    // Auto expand after a short delay
    setTimeout(() => {
        notification.classList.add('expanded');
    }, 100);
    
    // Auto remove after 5 seconds
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
    
    // Toggle expand/collapse on header click
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
// DETAILS PANEL
// ============================================================================

/**
 * Initialize details panel
 */
function initDetailsPanel() {
    if (!document.getElementById('detailsPanel')) {
        detailsPanel = document.createElement('div');
        detailsPanel.id = 'detailsPanel';
        detailsPanel.className = 'details-panel';
        detailsPanel.innerHTML = `
            <div class="details-header">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    <span>Marker Details</span>
                </h3>
                <div class="details-close" onclick="closeDetailsPanel()">
                    <i class="fas fa-times"></i>
                </div>
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

/**
 * Show details panel with marker information
 */
async function showDetailsPanel(markerData, imagePath = null) {
    if (!detailsPanel) {
        initDetailsPanel();
    }
    
    const detailsContent = document.getElementById('detailsContent');
    const title = markerData.title || 'Untitled Marker';
    const description = markerData.description || 'No description available';
    const lat = markerData.lat || 'N/A';
    const lng = markerData.lng || 'N/A';

    const address = await setName(lat,lng);
    
    const category = markerData.category || 'Uncategorized';
    const created = markerData.created_at ? new Date(markerData.created_at).toLocaleString() : 'N/A';
    
    let imageHtml = '';
    if (imagePath) {
        imageHtml = `<img src="${imagePath}" class="details-image" alt="Marker image">`;
    } else {
        imageHtml = `
            <div style="width: 100%; height: 200px; background: #f3f4f6; border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                <i class="fas fa-image" style="font-size: 48px;"></i>
            </div>
        `;
    }
    
    detailsContent.innerHTML = `
        ${imageHtml}
        <div class="details-info">
            <div class="details-info-item">
                <span class="details-info-label">Title:</span>
                <span class="details-info-value">${title}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Category:</span>
                <span class="details-info-value">${category}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Coordinates:</span>
                <span class="details-info-value">${address}</span>
            </div>
            <div class="details-info-item">
                <span class="details-info-label">Created:</span>
                <span class="details-info-value">${created}</span>
            </div>
        </div>
        <div class="details-description">
            <strong style="display: block; margin-bottom: 8px; color: #374151;">Description:</strong>
            ${description}
        </div>
    `;
    
    detailsPanel.classList.add('visible');
    currentDetailsMarker = markerData;
}

/**
 * Close details panel
 */
function closeDetailsPanel() {
    if (detailsPanel) {
        detailsPanel.classList.remove('visible');
        currentDetailsMarker = null;
    }
}

// ============================================================================
// FUNCTION DEFINITIONS
// ============================================================================

/**
 * Get address from coordinates and show popup
 */
export function getName(lat, lon) {
    const url = "https://api.geoapify.com/v1/geocode/reverse?" +
        `lat=${lat}&lon=${lon}&` +
        `apiKey=${apiKey}`;
    
    let popupmarker = L.popup()
        .setLatLng([lat, lon])
        .setContent("Loading address...")
        .openOn(map);

    fetch('/api/reverse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            lat: lat,
            lon: lon
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.features || data.features.length === 0) return;

        const address = data.features[0].properties.formatted;

        popupmarker.setLatLng([lat, lon])
            .setContent(`
                <div style="min-width: 200px;">
                    <p style="margin: 0 0 10px 0; font-size: 13px;">${address}</p>
                    <button id="popupActionBtn" style="background-color: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fas fa-plus"></i>
                        Add as Marker
                    </button>
                </div>
            `);

        if (popupmarker) {
            popupmarker.openOn(map);
            console.log('ReverasadI:', address);
        }

        const btn = document.getElementById('popupActionBtn');
        if (btn) {
            btn.addEventListener('click', function() {
                selectedLat = lat;
                selectedLng = lon;
                openModal();
            });
        }

        console.log('Reverse API:', data);

        popupmarker.on('popupclose', function() {
            map.removeLayer(popupmarker);
            popupmarker = null;
        });
    })
    .catch(err => console.error(err));
}

/**
 * Clear all markers from the map
 */
function clearAllMarkers() {
    try {
        if (stationFeatureGroup) stationFeatureGroup.clearLayers();
        if (incidentFeatureGroup) incidentFeatureGroup.clearLayers();
        if (hydrantFeatureGroup) hydrantFeatureGroup.clearLayers();
        if (otherFeatureGroup) otherFeatureGroup.clearLayers();
        existingMarkers = {};
        console.log('All markers cleared');
    } catch (error) {
        console.error('Error clearing markers:', error);
    }
}

/**
 * Fetch latest coordinates and update map - FIXED for paginated response
 */
async function fetchCoordinate() {
    try {
        console.log('Fetching coordinates...');
        const response = await fetch('/api/markers');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Received response:', result);

        // Handle paginated response from Laravel
        let coordinates;
        if (result.data && Array.isArray(result.data)) {
            coordinates = result.data;
            console.log('Processing paginated data with', coordinates.length, 'markers');
        } else if (Array.isArray(result)) {
            coordinates = result;
            console.log('Processing direct array with', coordinates.length, 'markers');
        } else {
            console.error('Unexpected response format:', result);
            showNotification('Failed to load markers: Invalid response format', 'error');
            return;
        }

        if (!coordinates || coordinates.length === 0) {
            console.log('No markers found');
            return;
        }

        clearAllMarkers();
        await updateCoordinate(coordinates);
        console.log('Markers refreshed successfully');
    } catch (error) {
        console.error('Fetch error in fetchCoordinate:', error);
        showNotification('Failed to fetch markers: ' + error.message, 'error');
    }
}

/**
 * Update map with new coordinates
 */
async function updateCoordinate(data) {
    try {
        // Fetch incidents for response buttons
        const incidentResponse = await fetch('/api/incident/latest');
        let incidents = [];
        
        if (incidentResponse.ok) {
            const incidentData = await incidentResponse.json();
            incidents = incidentData.data || incidentData;
        }
        
        console.log('Processing data:', data);
        
        let coordinates = [];
        
        if (data.data && Array.isArray(data.data)) {
            coordinates = data.data;
            console.log('Processing paginated data with', coordinates.length, 'items');
        } 
        else if (Array.isArray(data)) {
            coordinates = data;
            console.log('Processing direct array with', coordinates.length, 'items');
        }
        else if (data && typeof data === 'object') {
            coordinates = [data];
            console.log('Processing single object');
        }
        else {
            console.error('Unexpected data format:', data);
            return;
        }
        
        // Fetch additional data for hydrants and schools
        const hydrantsResponse = await fetch('/api/hydrants');
        const schoolsResponse = await fetch('/api/schools');
        
        let hydrants = [];
        let schools = [];
        
        if (hydrantsResponse.ok) {
            const hydrantData = await hydrantsResponse.json();
            hydrants = hydrantData.data || hydrantData;
        }
        
        if (schoolsResponse.ok) {
            const schoolData = await schoolsResponse.json();
            schools = schoolData.data || schoolData;
        }
        
        const markersToProcess = [];
        
        for (const item of coordinates) {
            if (item.marker && item.marker.lat && item.marker.lng) {
                markersToProcess.push({
                    ...item.marker,
                    incident_id: item.incident_id,
                    incident_status: item.incident_status_id
                });
            }
            else if (item.lat && item.lng) {
                // Attach hydrant data if it's a hydrant
                if (item.markerCategory_id == 4) {
                    const hydrant = hydrants.find(h => h.marker_id == item.marker_id);
                    if (hydrant) {
                        item.hydrant = hydrant;
                        // Fetch station data for hydrant
                        if (hydrant.station_id) {
                            const stationResponse = await fetch(`/api/stations/${hydrant.station_id}`);
                            if (stationResponse.ok) {
                                const stationData = await stationResponse.json();
                                item.hydrant.station = stationData;
                            }
                        }
                    }
                }
                // Attach school data if it's a school
                else if (item.markerCategory_id == 5) {
                    const school = schools.find(s => s.marker_id == item.marker_id);
                    if (school) {
                        item.school = school;
                    }
                }
                markersToProcess.push(item);
            }
            else {
                console.warn('Invalid item - missing lat/lng:', item);
            }
        }
        
        console.log(`Processed ${coordinates.length} items into ${markersToProcess.length} markers`);
        
        if (markersToProcess.length === 0) {
            console.log('No valid markers to process');
            return;
        }
        
        clearAllMarkers();
        
        markersToProcess.forEach(marker => {
            if (!marker.lat || !marker.lng) {
                console.warn('Invalid marker data - missing lat/lng:', marker);
                return;
            }
            
            const lat = parseFloat(marker.lat);
            const lng = parseFloat(marker.lng);
            
            if (isNaN(lat) || isNaN(lng)) {
                console.warn('Invalid marker data - lat/lng not numbers:', marker);
                return;
            }
            
            const popupData = {
                marker: marker,
                image: marker.multimedia || null,
                created_at: marker.created_at
            };
            
            const popupContent = generatePopupContent(popupData, incidents);
            const tempIcon = getIconByCategoryId(marker.markerCategory_id);
            
            const newMarker = L.marker([lat, lng], {icon: tempIcon})
                .bindPopup(popupContent, { minWidth: 100, maxWidth: 300 });

            if (marker.markerCategory_id == 3) {
                const circle = L.circle([lat, lng], { 
                    radius: 200,
                    color: '#ef4444',
                    fillColor: '#ef4444',
                    fillOpacity: 0.2
                });
                incidentFeatureGroup.addLayer(circle);
            }

            addMarkerToFeatureGroup(marker.markerCategory_id, newMarker);

            newMarker.on('popupopen', () => addPopupEventListeners({
                marker: marker,
                image: marker.multimedia || null,
                created_at: marker.created_at
            }));

            existingMarkers[marker.marker_id] = newMarker;
        });
        
        console.log('Markers updated successfully, total:', Object.keys(existingMarkers).length);
        
    } catch (error) {
        console.error('Error updating coordinates:', error);
        showNotification('Error updating markers: ' + error.message, 'error');
    }
}

/**
 * Generate popup content for markers
 */
function generatePopupContent(coord, incidents) {
    const marker = coord.marker || coord;
    const imagePath = marker.multimedia?.path ? '/storage/' + marker.multimedia.path : (coord.image?.path ? '/storage/' + coord.image.path : null);
    console.log("all data:", marker);
    
    // Special design for fire stations (category_id = 1)
    if (marker.markerCategory_id == 1) {
        return `
            <div style="min-width: 240px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="font-weight: bold; font-size: 16px; margin-bottom: 8px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                    🚒 ${marker.title || 'Fire Station'}
                </div>
                <div style="background-color: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 14px; color: #2563eb;">📞</span>
                        <span style="font-size: 14px; font-weight: 500;">${marker.station?.contact_number || 'N/A'}</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 14px; color: #2563eb;">🗺️</span>
                        <span style="font-size: 13px;">${marker.description || 'No description'}</span>
                    </div>
                </div>
                
                <!-- Admin Controls -->
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" 
                        style="flex: 1; background-color: #10b981; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-edit" style="font-size: 12px;"></i>
                        Edit
                    </button>
                    <button id="delete${marker.marker_id}" 
                        style="flex: 1; background-color: #ef4444; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-trash" style="font-size: 12px;"></i>
                        Delete
                    </button>
                </div>
                
                <button id="viewDetails${marker.marker_id}" 
                    style="width: 100%; background-color: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 8px;">
                    <i class="fas fa-info-circle"></i>
                    View Full Details
                </button>
                
                ${imagePath ? `
                <div style="width: 100%; height: 60px; border-radius: 6px; overflow: hidden; margin-top: 8px; border: 1px solid #e2e8f0;">
                    <img src="${imagePath}" style="width: 100%; height: 100%; object-fit: cover;" alt="Station image">
                </div>
                ` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for fire hydrants (category_id = 4)
    if (marker.markerCategory_id == 4 && marker.hydrant) {
        return `
            <div style="min-width: 260px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="font-weight: bold; font-size: 16px; margin-bottom: 8px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                    💧 ${marker.title || 'Fire Hydrant'}
                </div>
                <div style="background-color: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 14px; color: #2563eb;">🔢</span>
                        <span style="font-size: 14px; font-weight: 500;">Hydrant Code: ${marker.hydrant.hydrant_code || 'N/A'}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 14px; color: #2563eb;">🏢</span>
                        <span style="font-size: 14px; font-weight: 500;">Station: ${marker.hydrant.station?.station_name || 'Not Assigned'}</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 14px; color: #2563eb;">📝</span>
                        <span style="font-size: 13px;">${marker.description || 'No description'}</span>
                    </div>
                </div>
                
                <!-- Admin Controls -->
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" 
                        style="flex: 1; background-color: #10b981; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-edit" style="font-size: 12px;"></i>
                        Edit
                    </button>
                    <button id="delete${marker.marker_id}" 
                        style="flex: 1; background-color: #ef4444; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-trash" style="font-size: 12px;"></i>
                        Delete
                    </button>
                </div>
                
                <button id="viewDetails${marker.marker_id}" 
                    style="width: 100%; background-color: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 8px;">
                    <i class="fas fa-info-circle"></i>
                    View Full Details
                </button>
                
                ${imagePath ? `
                <div style="width: 100%; height: 60px; border-radius: 6px; overflow: hidden; margin-top: 8px; border: 1px solid #e2e8f0;">
                    <img src="${imagePath}" style="width: 100%; height: 100%; object-fit: cover;" alt="Hydrant image">
                </div>
                ` : ''}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
        `;
    }
    
    // Special design for schools (category_id = 5)
    if (marker.markerCategory_id == 5 && marker.school) {
        return `
            <div style="min-width: 260px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="font-weight: bold; font-size: 16px; margin-bottom: 8px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                    🏫 ${marker.school.school_name || marker.title || 'School'}
                </div>
                <div style="background-color: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 14px; color: #2563eb;">🔢</span>
                        <span style="font-size: 14px; font-weight: 500;">School ID: ${marker.school.school_id || 'N/A'}</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 14px; color: #2563eb;">📝</span>
                        <span style="font-size: 13px;">${marker.description || 'No description'}</span>
                    </div>
                </div>
                
                <!-- Admin Controls -->
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <button id="edit${marker.marker_id}" 
                        style="flex: 1; background-color: #10b981; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-edit" style="font-size: 12px;"></i>
                        Edit
                    </button>
                    <button id="delete${marker.marker_id}" 
                        style="flex: 1; background-color: #ef4444; color: white; border: none; padding: 8px 0; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="fas fa-trash" style="font-size: 12px;"></i>
                        Delete
                    </button>
                </div>
                
                <button id="viewDetails${marker.marker_id}" 
                    style="width: 100%; background-color: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 8px;">
                    <i class="fas fa-info-circle"></i>
                    View Full Details
                </button>
                
                ${imagePath ? `
                <div style="width: 100%; height: 60px; border-radius: 6px; overflow: hidden; margin-top: 8px; border: 1px solid #e2e8f0;">
                    <img src="${imagePath}" style="width: 100%; height: 100%; object-fit: cover;" alt="School image">
                </div>
                ` : ''}
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
                ${imagePath
                    ? `<img src="${imagePath}" class="object-cover w-full h-full rounded" />`
                    : `<span class="text-gray-400 text-xs">No image</span>`}
                <input type='hidden' value='${marker.marker_id}' id='mid'>
            </div>
            ${getActionButtons({ marker: marker }, incidents)}
            <button id="viewDetails${marker.marker_id}" 
                class="bg-blue-600 text-white text-xs px-2 py-1.5 rounded hover:bg-blue-700 flex items-center justify-center gap-1 w-full mt-1">
                <i class="fas fa-info-circle" style="font-size: 12px;"></i>
                View Full Details
            </button>
        </div>
    `;
}

/**
 * Get action buttons based on marker category and incident status
 */
function getActionButtons(coord, incidents) {
    const marker = coord.marker || coord;
    
    if (marker.markerCategory_id == 3) {
        const matchingIncident = incidents.find(incident => 
            incident.marker?.marker_id === marker.marker_id
        );
        
        if (matchingIncident && matchingIncident.status === "reported") {
            return `<button id="respond${marker.marker_id}" 
                        class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 flex items-center gap-1">
                        <i class="fas fa-phone-alt" style="font-size: 12px;"></i>
                        Respond
                    </button>`;
        }
        return "";
    } else {
        return `<div class="bg-white-600" style="display:flex; gap: 4px; justify-content:space-evenly;">
                    <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1.5 rounded hover:bg-green-700 flex items-center justify-center gap-1" 
                        id="edit${marker.marker_id}">
                        <i class="fas fa-edit" style="font-size: 12px;"></i>
                        Edit
                    </button>
                    <button id="delete${marker.marker_id}" 
                        class="bg-red-600 text-white text-xs px-2 py-1.5 rounded hover:bg-red-700 flex items-center justify-center gap-1" 
                        style="width: 40%;">
                        <i class="fas fa-trash" style="font-size: 12px;"></i>
                        Delete
                    </button>
                </div>`;
    }
}

/**
 * Get marker icon based on category
 */
function getMarkerIcon(categoryId) {
    return getIconByCategoryId(categoryId);
}

/**
 * Add marker to appropriate feature group
 */
function addMarkerToFeatureGroup(categoryId, marker) {
    switch (parseInt(categoryId)) {
        case 1:
            stationFeatureGroup.addLayer(marker);
            break;
        case 3:
            incidentFeatureGroup.addLayer(marker);
            break;
        case 4:
            hydrantFeatureGroup.addLayer(marker);
            break;
        default:
            otherFeatureGroup.addLayer(marker);
    }
}

/**
 * Add event listeners to popup buttons
 */
function addPopupEventListeners(coord) {
    const marker = coord.marker || coord;
    
    setTimeout(() => {
        const deleteBtn = document.getElementById('delete' + marker.marker_id);
        const editBtn = document.getElementById('edit' + marker.marker_id);
        const respondBtn = document.getElementById('respond' + marker.marker_id);
        const viewDetailsBtn = document.getElementById('viewDetails' + marker.marker_id);

        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => deleteMarker(marker.marker_id));
        }
        
        if (editBtn) {
            editBtn.addEventListener('click', () => openEditModal(marker));
        }
        
        if (respondBtn) {
            respondBtn.addEventListener('click', () => respondIncident(marker));
        }

        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', () => {
                const imagePath = coord.image?.path ? '/storage/' + coord.image.path : (marker.multimedia?.path ? '/storage/' + marker.multimedia.path : null);
                const markerData = {
                    ...marker,
                    category: getCategoryName(marker.markerCategory_id),
                    created_at: coord.created_at || marker.created_at
                };
                showDetailsPanel(markerData, imagePath);
            });
        }
    }, 100);
}

/**
 * Get category name by ID
 */
function getCategoryName(categoryId) {
    const categoryMap = {
        1: 'Fire Station',
        2: 'Regular Marker',
        3: 'Incident',
        4: 'Fire Hydrant',
        5: 'School',
        6: 'Evacuation Center'
    };
    return categoryMap[categoryId] || 'Unknown';
}

/**
 * Delete a marker
 */
function deleteMarker(value) {
    console.log('items:', value);

    if (!confirm('Are you sure you want to delete this marker?')) {
        return;
    }
    
    fetch(`/api/markers/${value}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(async res => {
        const data = await res.json();
        
        if (!res.ok) {
            throw new Error(data.message || `Server error: ${res.status}`);
        }
        
        return data;
    })
    .then(data => {
        console.log("deleted:", data);
        if (data.success) {
            showNotification(data.message || 'Marker deleted successfully!', 'success');
            fetchCoordinate();
            
            if (currentDetailsMarker && currentDetailsMarker.marker_id == value) {
                closeDetailsPanel();
            }
        } else {
            showNotification(data.message || 'Failed to delete marker', 'error');
        }
    })
    .catch(err => {
        console.error('Delete error:', err);
        showNotification('Error deleting marker: ' + err.message, 'error');
    });
}

/**
 * Save a new marker
 */
async function saveMarker(lat, lng, title, description, categoryId) {
    try {
        console.log('Starting saveMarker...');
        
        const markerRes = await fetch('/api/markers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                lat: lat,
                lng: lng,
                title: title,
                description: description,
                markerCategory_id: categoryId,
                status_id: 1,
                multimedia_id: 1,
            })
        });
        
        if (!markerRes.ok) {
            const errorText = await markerRes.text();
            console.error('Marker save error:', errorText);
            throw new Error('Failed to save marker');
        }
        
        const markerData = await markerRes.json();
        const markerId = markerData.data.marker_id;
        console.log("Marker created:", markerData, "ID:", markerId);

        if (typeof closeModal === 'function') {
            closeModal();
        }
        
        const titleInput = document.getElementById('markerTitle');
        const descInput = document.getElementById('markerDescription');
        
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        
        console.log('Calling fetchCoordinate...');
        await fetchCoordinate();
        console.log('Markers refreshed successfully');
        
        showNotification('Marker created successfully!', 'success');
        return markerId;
        
    } catch (err) {
        console.error("Error saving marker or coordinate:", err);
        showNotification('Error saving marker: ' + err.message, 'error');
        return null;
    }
}

/**
 * Respond to an incident - assign available personnel
 */
async function respondIncident(location) {
    console.log("location data:", location);

    try {
        const response = await fetch('/api/incident/latest');
        const incidents = await response.json();

        let incidentId = null;
        let station_id = null;

        incidents.forEach(incident => {
            if (location.marker_id == incident.marker.marker_id) {
                incidentId = incident.incident_id;
                station_id = incident.station_id;
            }
        });

        if (!incidentId) {
            console.log("No matching incident found");
            showNotification('No matching incident found', 'warning');
            return;
        }

        showLoadingModal("Fetching available personnel...");

        const personnelRes = await fetch(`/api/stations/${station_id}/available-personnel`);
        if (!personnelRes.ok) {
            throw new Error("Failed to fetch available personnel");
        }
        
        const availablePersonnel = await personnelRes.json();
        console.log("Available personnel:", availablePersonnel);

        closeModal();

        if (!availablePersonnel || availablePersonnel.length === 0) {
            showNotification("No available personnel at this station!", 'warning');
            return;
        }

        const selectedPersonnel = await showPersonnelSelectionModal(availablePersonnel);
        
        if (!selectedPersonnel || selectedPersonnel.length === 0) {
            showNotification("No personnel selected!", 'warning');
            return;
        }

        showLoadingModal("Creating incident report and assigning personnel...");

        const incidentRes = await fetch('/api/incidentReport', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                incident_id: incidentId,
                victims: 0,
                severity: 'low',
                dateEnded: null
            })
        });

        if (!incidentRes.ok) {
            const errorText = await incidentRes.text();
            console.error("Insert error:", errorText);
            throw new Error("Failed to insert incident report");
        }

        const incidentReportData = await incidentRes.json();
        console.log("Incident report response:", incidentReportData);

        let incidentReportId = null;

        if (incidentReportData.reported && incidentReportData.reported.id) {
            incidentReportId = incidentReportData.reported.id;
        } else if (incidentReportData.data?.incident_report_id) {
            incidentReportId = incidentReportData.data.incident_report_id;
        } else if (incidentReportData.incident_report_id) {
            incidentReportId = incidentReportData.incident_report_id;
        } else if (incidentReportData.id) {
            incidentReportId = incidentReportData.id;
        }

        if (!incidentReportId) {
            console.error("No incident report ID returned:", incidentReportData);
            throw new Error("Failed to get incident report ID");
        }

        console.log("Extracted incident report ID:", incidentReportId);

        const assignedPersonnel = [];

        for (const personnel of selectedPersonnel) {
            console.log("Attempting to assign personnel:", personnel);
            
            let roleValue = 'firefighter';
            if (personnel.role) {
                const roleLower = personnel.role.toLowerCase();
                if (['team_leader', 'firefighter', 'driver', 'medic', 'commander'].includes(roleLower)) {
                    roleValue = roleLower;
                }
            }
            
            const requestBody = {
                incident_id: incidentReportId,
                personnel_id: personnel.personnel_id,
                role: roleValue,
                status: 'assigned'
            };
            
            console.log("Request body:", requestBody);
            
            const teamRes = await fetch('/api/responded-team', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            const responseText = await teamRes.text();
            console.log("Response status:", teamRes.status);
            console.log("Response text:", responseText);
            
            if (teamRes.ok) {
                try {
                    const data = JSON.parse(responseText);
                    console.log("Personnel assigned successfully:", data);
                    assignedPersonnel.push(data.data || data);
                } catch (e) {
                    console.error("Error parsing success response:", e);
                }
            } else {
                console.error("Error assigning personnel. Status:", teamRes.status);
                console.error("Error response:", responseText);
            }
        }

        const updateRes = await fetch(`/api/incident/${incidentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                station_id: station_id,
                status: "responding"
            })
        });

        if (!updateRes.ok) {
            const errorText = await updateRes.text();
            console.error("Laravel response:", errorText);
            throw new Error("Failed to update incident");
        }

        closeModal();

        console.log("Incident updated successfully with", assignedPersonnel.length, "personnel assigned");
        
        if (assignedPersonnel.length > 0) {
            showNotification(`Successfully assigned ${assignedPersonnel.length} personnel to respond!`, 'success');
            
            const personnelIds = assignedPersonnel.map(p => p.personnel_id);
            initializeIncidentTracking(incidentId, personnelIds);
        } else {
            showNotification(`Incident updated but no personnel were assigned.`, 'info');
        }
        
        await fetchCoordinate();

    } catch (err) {
        console.error("Error:", err);
        closeModal();
        showNotification("Error: " + err.message, 'error');
    }
}

// ============================================================================
// FIREFIGHTER TRACKING AND ANIMATION FUNCTIONS
// ============================================================================

function startTrackingFirefighter(personnelId, incidentId) {
    console.log(`Starting tracking for firefighter ${personnelId} to incident ${incidentId}`);
    
    fetchRoute(personnelId, incidentId);
    
    if (animationIntervals[personnelId]) {
        clearInterval(animationIntervals[personnelId]);
    }
    
    animationIntervals[personnelId] = setInterval(() => {
        updateFirefighterLocation(personnelId, incidentId);
    }, 5000);
}

async function fetchRoute(personnelId, incidentId) {
    try {
        const response = await fetch(`/api/firefighter/${personnelId}/track/${incidentId}`);
        const data = await response.json();
        
        if (data.success) {
            displayRoute(data.route, data.current_location, data.incident_location);
        }
    } catch (error) {
        console.error('Error fetching route:', error);
    }
}

async function updateFirefighterLocation(personnelId, incidentId) {
    try {
        const response = await fetch(`/api/firefighter/${personnelId}/live-location`);
        const data = await response.json();
        
        if (data.success) {
            const location = data.data;
            updateMarkerPosition(personnelId, location);
            
            if (location.status === 'en_route') {
                fetchRoute(personnelId, incidentId);
            }
        }
    } catch (error) {
        console.error('Error updating location:', error);
    }
}

function displayRoute(routeData, currentLocation, incidentLocation) {
    if (!routeData.route || !routeData.route.features) return;
    
    const routeKey = `${currentLocation.personnel_id}_${currentLocation.incident_id}`;
    if (routeLayers[routeKey]) {
        map.removeLayer(routeLayers[routeKey]);
    }
    
    const routeLayer = L.geoJSON(routeData.route, {
        style: {
            color: '#3b82f6',
            weight: 4,
            opacity: 0.7,
            lineDash: [10, 10]
        }
    }).addTo(map);
    
    routeLayers[routeKey] = routeLayer;
    
    addFirefighterMarker(currentLocation);
    addIncidentMarker(incidentLocation, currentLocation.incident_id);
}

function addFirefighterMarker(location) {
    const key = `ff_${location.personnel_id}`;
    
    if (firefighterMarkers[key]) {
        map.removeLayer(firefighterMarkers[key]);
    }
    
    const icon = getFirefighterIconWithSpeed(location.speed);
    
    const marker = L.marker([location.latitude, location.longitude], {
        icon: icon,
        title: `Firefighter ${location.personnel_id}`,
        zIndexOffset: 1000
    }).addTo(map);
    
    marker.bindPopup(`
        <div class="p-2">
            <strong class="block text-sm"><i class="fas fa-helmet-safety mr-1"></i> Firefighter</strong>
            <p class="text-xs mt-1">Status: <span class="font-semibold">${location.status}</span></p>
            <p class="text-xs"><i class="fas fa-tachometer-alt mr-1"></i> Speed: ${Math.round(location.speed || 0)} km/h</p>
            <p class="text-xs"><i class="fas fa-clock mr-1"></i> ${new Date(location.timestamp).toLocaleTimeString()}</p>
        </div>
    `);
    
    firefighterMarkers[key] = marker;
}

function addIncidentMarker(location, incidentId) {
    const key = `inc_${incidentId}`;
    
    if (firefighterMarkers[key]) return;
    
    const marker = L.marker([location.lat, location.lng], {
        icon: getIconById('incident'),
        title: 'Incident Location',
        zIndexOffset: 500
    }).addTo(map);
    
    firefighterMarkers[key] = marker;
}

function updateMarkerPosition(personnelId, newLocation) {
    const key = `ff_${personnelId}`;
    const marker = firefighterMarkers[key];
    
    if (!marker) {
        addFirefighterMarker(newLocation);
        return;
    }
    
    const oldLatLng = marker.getLatLng();
    const newLatLng = L.latLng(newLocation.latitude, newLocation.longitude);
    
    marker.setIcon(getFirefighterIconWithSpeed(newLocation.speed));
    
    animateMarkerSmoothly(marker, oldLatLng, newLatLng, 2000);
    
    marker.setPopupContent(`
        <div class="p-2">
            <strong class="block text-sm"><i class="fas fa-helmet-safety mr-1"></i> Firefighter</strong>
            <p class="text-xs mt-1">Status: <span class="font-semibold">${newLocation.status}</span></p>
            <p class="text-xs"><i class="fas fa-tachometer-alt mr-1"></i> Speed: ${Math.round(newLocation.speed || 0)} km/h</p>
            <p class="text-xs"><i class="fas fa-clock mr-1"></i> ${new Date(newLocation.timestamp).toLocaleTimeString()}</p>
        </div>
    `);
}

function animateMarkerSmoothly(marker, start, end, duration) {
    const startTime = performance.now();
    
    function animate(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const easeProgress = 1 - Math.pow(1 - progress, 3);
        
        const lat = start.lat + (end.lat - start.lat) * easeProgress;
        const lng = start.lng + (end.lng - start.lng) * easeProgress;
        
        marker.setLatLng([lat, lng]);
        
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

function stopTrackingFirefighter(personnelId) {
    if (animationIntervals[personnelId]) {
        clearInterval(animationIntervals[personnelId]);
        delete animationIntervals[personnelId];
    }
    
    const key = `ff_${personnelId}`;
    if (firefighterMarkers[key]) {
        map.removeLayer(firefighterMarkers[key]);
        delete firefighterMarkers[key];
    }
}

function initializeIncidentTracking(incidentId, personnelIds) {
    personnelIds.forEach(personnelId => {
        startTrackingFirefighter(personnelId, incidentId);
    });
}

function showPersonnelSelectionModal(personnel) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.id = 'personnelSelectionModal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;

        modal.innerHTML = `
            <div style="background-color: white; padding: 24px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
                <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 16px; color: #333;">Select Response Team</h2>
                <p style="font-size: 14px; color: #666; margin-bottom: 16px;">Choose personnel to respond to this incident:</p>
                
                <div id="personnelList" style="margin-bottom: 20px;">
                    ${personnel.map(p => `
                        <div style="padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; background-color: #f9fafb;">
                            <input type="checkbox" id="p_${p.personnel_id}" value="${p.personnel_id}" data-name="${p.first_name} ${p.last_name}" data-role="${p.rank || 'firefighter'}" style="margin-right: 12px; width: 18px; height: 18px;">
                            <i class="fas fa-user-helmet-safety" style="font-size: 20px; color: #3b82f6; margin-right: 12px;"></i>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px;">${p.first_name} ${p.last_name}</div>
                                <div style="font-size: 13px; color: #666;">
                                    <span style="background-color: ${getRoleColor(p.rank)}; color: white; padding: 2px 8px; border-radius: 12px; margin-right: 8px;">${p.rank || 'Firefighter'}</span>
                                    ${p.availability || 'Available'}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div style="display: flex; gap: 12px; justify-content: space-between; align-items: center; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                    <div>
                        <span id="selectedCount" style="font-size: 14px; color: #666;">0 selected</span>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button id="cancelPersonnelBtn" style="padding: 10px 20px; border: 1px solid #d1d5db; background-color: white; color: #374151; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Cancel
                        </button>
                        <button id="confirmPersonnelBtn" style="padding: 10px 20px; background-color: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Assign Selected
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const checkboxes = modal.querySelectorAll('input[type="checkbox"]');
        const selectedCountSpan = modal.querySelector('#selectedCount');
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const checked = modal.querySelectorAll('input[type="checkbox"]:checked').length;
                selectedCountSpan.textContent = `${checked} selected`;
            });
        });

        document.getElementById('confirmPersonnelBtn').addEventListener('click', () => {
            const checkboxes = document.querySelectorAll('#personnelList input[type="checkbox"]:checked');
            const selected = Array.from(checkboxes).map(cb => {
                return {
                    personnel_id: cb.value,
                    name: cb.dataset.name,
                    role: cb.dataset.role
                };
            });
            
            document.body.removeChild(modal);
            resolve(selected);
        });

        document.getElementById('cancelPersonnelBtn').addEventListener('click', () => {
            document.body.removeChild(modal);
            resolve([]);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                resolve([]);
            }
        });
    });
}

function getRoleColor(rank) {
    const colors = {
        'commander': '#8b5cf6',
        'team_leader': '#3b82f6',
        'medic': '#10b981',
        'driver': '#f59e0b',
        'firefighter': '#6b7280'
    };
    return colors[rank?.toLowerCase()] || '#6b7280';
}

function showLoadingModal(message) {
    if (document.getElementById('loadingModal')) {
        return;
    }
    
    const modal = document.createElement('div');
    modal.id = 'loadingModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    modal.innerHTML = `
        <div style="background-color: white; padding: 30px; border-radius: 12px; text-align: center; min-width: 250px;">
            <i class="fas fa-circle-notch fa-spin" style="font-size: 40px; color: #3b82f6; margin-bottom: 15px;"></i>
            <p style="color: #333; font-size: 14px; margin: 0;">${message}</p>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeModal() {
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) {
        document.body.removeChild(loadingModal);
    }
    
    const successModal = document.getElementById('successModal');
    if (successModal) {
        document.body.removeChild(successModal);
    }
}

function saveCategory(name) {
    fetch('/api/marker-categories', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            marker_category: name,
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Category saved:', data);
        showNotification('Category saved successfully!', 'success');
        if (typeof closeAddCategory === 'function') {
            closeAddCategory();
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Error saving category', 'error');
    });
}

// ============================================================================
// SHAPE MANAGEMENT FUNCTIONS
// ============================================================================

function toggleShapeManagement() {
    manageShapesMode = !manageShapesMode;
    
    const toggleBtn = document.getElementById('toggleShapeMode');
    if (toggleBtn) {
        toggleBtn.innerHTML = manageShapesMode 
            ? '<i class="fas fa-shapes mr-1"></i> Manage Shapes: ON'
            : '<i class="fas fa-shapes mr-1"></i> Manage Shapes: OFF';
        
        toggleBtn.classList.toggle('bg-purple-600', !manageShapesMode);
        toggleBtn.classList.toggle('bg-green-600', manageShapesMode);
    }
    
    map.getContainer().style.cursor = manageShapesMode ? 'pointer' : '';
    console.log('Shape management mode:', manageShapesMode ? 'ON' : 'OFF');
    showNotification(`Shape management ${manageShapesMode ? 'enabled' : 'disabled'}`, 'info');
}

function onShapeClick(shapeId, layer, latlng) {
    if (!manageShapesMode) return;
    
    selectedShapeForEdit = {
        id: shapeId,
        layer: layer,
        name: layer.shapeName
    };
    
    const popupContent = `
        <div class="shape-management-popup" style="min-width: 200px; padding: 8px;">
            <strong class="block text-center text-sm mb-2" style="font-size: 14px; color: #333;">${layer.shapeName || 'Shape'}</strong>
            <div style="display: flex; gap: 10px; margin-top: 8px; justify-content: center;">
                <button id="shapeEditBtn" 
                    class="flex items-center justify-center gap-1.5 bg-blue-600 text-white text-xs px-4 py-2 rounded hover:bg-blue-700 transition-colors"
                    style="border: none; cursor: pointer; width: 80px; font-size: 13px;">
                    <i class="fas fa-edit"></i>
                    Edit
                </button>
                <button id="shapeDeleteBtn_${shapeId}" 
                    class="shape-delete-btn flex items-center justify-center gap-1.5 bg-red-600 text-white text-xs px-4 py-2 rounded hover:bg-red-700 transition-colors"
                    style="border: none; cursor: pointer; width: 80px; font-size: 13px;"
                    data-shape-id="${shapeId}">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-center" style="font-size: 11px;">Click outside to cancel</p>
        </div>
    `;
    
    L.popup({
        closeOnClick: true,
        autoClose: true,
        className: 'shape-management-popup-container'
    })
    .setLatLng(latlng)
    .setContent(popupContent)
    .openOn(map)
    .on('popupopen', function() {
        setTimeout(() => {
            const editBtn = document.getElementById('shapeEditBtn');
            const deleteBtn = document.getElementById(`shapeDeleteBtn_${shapeId}`);
            if (editBtn) {  
                editBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    editShape(selectedShapeForEdit);
                });
            }
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteShape(shapeId);
                });
            }
        }, 100);
    });
}

function editShape(shapeData) {
    const newName = prompt('Enter new name for shape:', shapeData.name);
    if (!newName || newName === shapeData.name) return;
    
    fetch(`/api/shapes/${shapeData.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            name: newName
        })
    })
    .then(async res => {
        if (!res.ok) {
            const text = await res.text();
            throw new Error(text);
        }
        return res.json();
    })
    .then(data => {
        console.log('Shape updated:', data);
        showNotification('Shape updated successfully!', 'success');
        
        shapeData.layer.shapeName = newName;
        
        shapeData.layer.unbindTooltip();
        shapeData.layer.bindTooltip(newName, {
            permanent: false,
            direction: "top",
            sticky: true,
            opacity: 0.9
        });
        
        map.closePopup();
    })
    .catch(err => {
        console.error('Error updating shape:', err);
        showNotification('Failed to update shape: ' + err.message, 'error');
    });
}

function deleteShape(shapeId) {
    if (!confirm('Are you sure you want to delete this shape?')) return;
    
    console.log('Deleting shape with ID:', shapeId);
    
    fetch(`/api/shapes/${shapeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(async res => {
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`Server error ${res.status}: ${text}`);
        }
        return res.json();
    })
    .then(data => {
        console.log('Shape deleted:', data);
        
        if (shapeLayers[shapeId]) {
            if (shapeFeatureGroup) {
                shapeFeatureGroup.removeLayer(shapeLayers[shapeId]);
            }
            delete shapeLayers[shapeId];
        }
        
        map.closePopup();
        showNotification('Shape deleted successfully!', 'success');
    })
    .catch(err => {
        console.error('Error deleting shape:', err);
        showNotification('Failed to delete shape: ' + err.message, 'error');
    });
}

async function loadShapes() {
    try {
        if (shapeFeatureGroup) {
            shapeFeatureGroup.clearLayers();
        }
        shapeLayers = {};
        
        const response = await fetch('/api/shapes', {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            const text = await response.text();
            console.error('Load shapes error:', text.substring(0, 200));
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const shapes = await response.json();
        console.log('Loaded shapes:', shapes);

        shapes.forEach((shape, index) => {
            let geojson = shape.geojson;
            
            if (typeof geojson === "string") {
                try {
                    geojson = JSON.parse(geojson);
                } catch (e) {
                    console.error('Error parsing geojson:', e);
                    return;
                }
            }

            try {
                const layer = L.geoJSON(geojson, {
                    style: {
                        color: colors[index % colors.length],
                        weight: 3,
                        fillOpacity: 0.2,
                        fillColor: colors[index % colors.length]
                    },
                    interactive: true,
                    pane: 'overlayPane'
                });
                
                layer.shapeId = shape.id;
                layer.shapeName = shape.name;
                layer.shapeGeoJSON = geojson;
                
                layer.on('click', function(e) {
                    if (manageShapesMode) {
                        L.DomEvent.stopPropagation(e);
                        onShapeClick(shape.id, layer, e.latlng);
                    }
                });
                
                if (shapeFeatureGroup) {
                    shapeFeatureGroup.addLayer(layer);
                }
                
                shapeLayers[shape.id] = layer;
                
                layer.bindTooltip(shape.name || "Shape", {
                    permanent: false,
                    direction: "top",
                    sticky: true,
                    opacity: 0.9
                });
                
                shapeChecker = true;
            } catch (e) {
                console.error('Error creating layer for shape:', e);
            }
        });
    } catch (error) {
        console.error('Error loading shapes:', error);
        showNotification('Error loading shapes', 'error');
    }
}

// ============================================================================
// INSIDE DOMContentLoaded - INITIALIZATION CODE
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
    
    initNotificationSystem();
    initDetailsPanel();
    
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(faLink);
    }
    
    window.closeDetailsPanel = closeDetailsPanel;
    
    drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);
    
    shapeFeatureGroup = L.featureGroup().addTo(map);
    
    var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    var satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 13,
            attribution: 'Tiles © Esri'
        }
    );
    
    stationLayer.addTo(map);

    L.control.layers({
        "Street Map": street,
        "Satellite": satellite,
    },
    {
        "🚉 Stations": stationFeatureGroup,
        "🔥 Incidents": incidentFeatureGroup,
        "💧 Hydrants": hydrantFeatureGroup,
        "📍 Other Markers": otherLayer,
        "🔷 Shapes": shapeFeatureGroup
    },
    {
        collapsed: true,
        position: 'topright'
    }).addTo(map);

    let array = [];
    window.getPath = function(paths, temps) {
        temp = temps;
        path = paths;
        array.push(path);
    };

    const shapeToggleBtn = document.getElementById('toggleShapeMode');
    if (shapeToggleBtn) {
        shapeToggleBtn.addEventListener('click', toggleShapeManagement);
    } else {
        const controlContainer = document.querySelector('.leaflet-control-layers');
        if (controlContainer) {
            const btn = document.createElement('button');
            btn.id = 'toggleShapeMode';
            btn.className = 'bg-purple-600 text-white px-3 py-2 rounded text-sm mt-2 w-75 flex items-center justify-center gap-2 hover:bg-green-700 transition-colors';
            btn.innerHTML = '<i class="fas fa-shapes"></i> Manage Shapes: OFF';
            btn.style.margin = '8px 5px';
            btn.style.padding = '8px 12px';
            btn.style.border = 'none';
            btn.style.cursor = 'pointer';
            btn.style.fontSize = '13px';
            btn.style.fontWeight = '500';
            btn.addEventListener('click', toggleShapeManagement);
            controlContainer.appendChild(btn);
        }
    }

    const drawControl = new L.Control.Draw({
        edit: { 
            featureGroup: drawnItems,
            edit: true,
            remove: true
        },
        draw: {
            polyline: true,
            polygon: true,
            rectangle: true,
            circle: true,
            marker: true,
            circlemarker: false
        }
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.DRAWSTART, function() {
        isDrawing = true;
        console.log('Drawing started - map clicks disabled');
    });

    map.on(L.Draw.Event.DRAWSTOP, function() {
        isDrawing = false;
        console.log('Drawing stopped - map clicks enabled');
    });

    map.on(L.Draw.Event.DRAWVERTEX, function() {
        isDrawing = true;
    });

    map.on(L.Draw.Event.CREATED, function(event) {
        const layer = event.layer;
        const geojson = layer.toGeoJSON();
        console.log('Drawn shape:', geojson);

        const shapeName = prompt('Enter shape name:', 'My Shape') || 'My Shape';

        fetch('/api/shapes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: shapeName,
                geojson: JSON.stringify(geojson),
                station_id: null
            })
        })
        .then(async res => {
            if (!res.ok) {
                const text = await res.text();
                console.error('Server response:', text.substring(0, 200));
                throw new Error(`Server error: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('Shape saved successfully:', data);
            
            if (data.shape) {
                if (drawnItems.hasLayer(layer)) {
                    drawnItems.removeLayer(layer);
                }
                
                const colorIndex = Object.keys(shapeLayers).length % colors.length;
                const newLayer = L.geoJSON(geojson, {
                    style: {
                        color: colors[colorIndex],
                        weight: 3,
                        fillOpacity: 0.2,
                        fillColor: colors[colorIndex]
                    },
                    interactive: true
                });
                
                newLayer.shapeId = data.shape.id;
                newLayer.shapeName = shapeName;
                newLayer.shapeGeoJSON = geojson;
                
                newLayer.on('click', function(e) {
                    if (manageShapesMode) {
                        L.DomEvent.stopPropagation(e);
                        onShapeClick(data.shape.id, newLayer, e.latlng);
                    }
                });
                
                shapeFeatureGroup.addLayer(newLayer);
                shapeLayers[data.shape.id] = newLayer;
                
                newLayer.bindTooltip(shapeName, {
                    permanent: false,
                    direction: "top",
                    sticky: true,
                    opacity: 0.9
                });
                
                showNotification('Shape saved successfully!', 'success');
            }
        })
        .catch(err => {
            console.error('Error saving shape:', err);
            showNotification('Failed to save shape. Check console for details.', 'error');
        });
    });

    map.on('draw:edited', function(event) {
        const layers = event.layers;
        layers.eachLayer(function(layer) {
            if (layer.shapeId) {
                const updatedGeojson = layer.toGeoJSON();
                fetch(`/api/shapes/${layer.shapeId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        geojson: updatedGeojson
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Shape updated:', data);
                    showNotification('Shape updated successfully!', 'success');
                })
                .catch(err => console.error('Error updating shape:', err));
            }
        });
    });

    map.on('draw:deleted', function(event) {
        const layers = event.layers;
        layers.eachLayer(function(layer) {
            if (layer.shapeId) {
                const shapeId = layer.shapeId;
                
                fetch(`/api/shapes/${shapeId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Shape deleted:', data);
                    
                    if (shapeLayers[shapeId]) {
                        if (shapeFeatureGroup) {
                            shapeFeatureGroup.removeLayer(shapeLayers[shapeId]);
                        }
                        delete shapeLayers[shapeId];
                    }
                    showNotification('Shape deleted successfully!', 'success');
                })
                .catch(err => console.error('Error deleting shape:', err));
            }
        });
    });

    loadShapes();
    fetchCoordinate();
    setInterval(fetchCoordinate, 10000);
    
    map.on('click', function(e) {
        selectedLat = e.latlng.lat;
        selectedLng = e.latlng.lng;
        
        console.log('Map clicked at coordinates:', selectedLat, selectedLng);
        
        if (isDrawing) {
            console.log('Click ignored - currently drawing');
            return;
        }
        
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
                console.log('Shape clicked in management mode:', clickedShape);
                onShapeClick(clickedShape, clickedLayer, e.latlng);
                return;
            }
        }
        
        if (!startMarker) {
            startMarker = L.marker(e.latlng).addTo(map);
            getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
            return;
        }
        map.removeLayer(startMarker);
        startMarker = L.marker(e.latlng).addTo(map);
        getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
    });

    (function() {
        const sidebar = document.getElementById('sidebar');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (sidebar && openBtn && closeBtn && backdrop) {
            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('menu-open');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebarFunc() {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
            }

            openBtn.addEventListener('click', openSidebar);
            closeBtn.addEventListener('click', closeSidebarFunc);
            backdrop.addEventListener('click', closeSidebarFunc);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebarFunc();
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                    document.body.style.overflow = '';
                }
            });

            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
            }
        } else {
            console.log('Sidebar elements not found - skipping sidebar initialization');
        }
    })();

    const saveCategoryBtn = document.getElementById('saveCategory');
    if (saveCategoryBtn) {
        saveCategoryBtn.addEventListener('click', function() {
            const markercategory = document.getElementById('marker_category');
            if (markercategory) {
                console.log(markercategory.value);
                saveCategory(markercategory.value);
            }
        });
    }

    setTimeout(() => {
        showNotification('Welcome to PyroSense Dashboard', 'info', 'System Ready');
    }, 1000);
});

// ============================================================================
// FIX: Save marker button handler
// ============================================================================

setTimeout(() => {
    const saveMarkerBtn = document.getElementById('saveMarkerBtn');
    if (saveMarkerBtn) {
        console.log('Found save marker button, attaching handler...');
        
        const newSaveBtn = saveMarkerBtn.cloneNode(true);
        saveMarkerBtn.parentNode.replaceChild(newSaveBtn, saveMarkerBtn);
        
        newSaveBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            console.log('Save button clicked!');
            console.log('Current selectedLat:', selectedLat);
            console.log('Current selectedLng:', selectedLng);
            
            const titleInput = document.getElementById('markerTitle');
            const descriptionInput = document.getElementById('markerDescription');
            const categorySelect = document.getElementById('mCategory');
            
            const title = titleInput ? titleInput.value : '';
            const description = descriptionInput ? descriptionInput.value : '';
            const categoryId = categorySelect ? categorySelect.value : '';
            
            console.log('Form data:', { title, description, categoryId });
            
            if (!selectedLat || !selectedLng) {
                alert('Please click on the map to select a location first');
                return;
            }
            
            if (!title) {
                alert('Please enter marker title');
                return;
            }
            if (!categoryId) {
                alert('Please select a category');
                return;
            }
            
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption?.getAttribute('data-category-name') || '';
            
            console.log('Category name:', categoryName);
            
            const originalText = newSaveBtn.innerHTML;
            newSaveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            newSaveBtn.disabled = true;
            
            try {
                const markerResponse = await fetch('/api/markers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        title: title,
                        description: description,
                        lat: selectedLat,
                        lng: selectedLng,
                        markerCategory_id: categoryId,
                        status_id: 1,
                        multimedia_id: 1
                    })
                });
                
                const markerResult = await markerResponse.json();
                console.log('Marker creation result:', markerResult);
                
                const markerId = markerResult.data?.marker_id || markerResult.marker_id;
                
                if (!markerId) {
                    throw new Error('Failed to create marker');
                }
                
                // Fire Station
                if (categoryName.toLowerCase() === 'fire station') {
                    const stationName = document.getElementById('stationName')?.value;
                    const stationCode = document.getElementById('stationCode')?.value;
                    const stationContact = document.getElementById('stationContact')?.value;
                    const stationEmail = document.getElementById('stationEmail')?.value;
                    const barangayId = document.getElementById('stationBarangay')?.value;
                    const street = document.getElementById('stationStreet')?.value;
                    
                    console.log('Station data:', { stationName, stationCode, stationContact, stationEmail, barangayId, street });
                    
                    if (!stationName) {
                        alert('Please enter station name');
                        return;
                    }
                    if (!stationCode) {
                        alert('Please enter station code');
                        return;
                    }
                    if (!stationContact) {
                        alert('Please enter station contact number');
                        return;
                    }
                    if (!stationEmail) {
                        alert('Please enter station email');
                        return;
                    }
                    if (!barangayId) {
                        alert('Please select a barangay for the station address');
                        return;
                    }
                    if (!street) {
                        alert('Please enter street address');
                        return;
                    }
                    
                    const addressResponse = await fetch('/api/full-addresses', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            street: street,
                            barangay_id: barangayId,
                            postal_id: null
                        })
                    });
                    
                    const addressResult = await addressResponse.json();
                    console.log('Address creation result:', addressResult);
                    
                    if (!addressResult.success) {
                        throw new Error(addressResult.message || 'Failed to create address');
                    }
                    
                    const stationResponse = await fetch('/api/station', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            station_name: stationName,
                            station_code: stationCode,
                            contact_number: stationContact,
                            email: stationEmail,
                            address_id: addressResult.address_id,
                            marker_id: markerId
                        })
                    });
                    
                    const stationResult = await stationResponse.json();
                    console.log('Station creation result:', stationResult);
                    
                    if (!stationResult.success && !stationResult.data) {
                        throw new Error(stationResult.message || 'Failed to create station');
                    }
                }
                
                // Fire Hydrant
                else if (categoryName.toLowerCase() === 'hydrant') {
                    const hydrantCode = document.getElementById('hydrantCode')?.value;
                    const hydrantStationId = document.getElementById('hydrantStation')?.value;
                    const hydrantTypeId = document.getElementById('hydrantType')?.value;
                    
                    if (!hydrantCode) {
                        alert('Please enter hydrant code (serial number)');
                        return;
                    }
                    if (!hydrantStationId) {
                        alert('Please select the station this hydrant belongs to');
                        return;
                    }
                    
                    const hydrantResponse = await fetch('/api/hydrant', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            hydrant_code: hydrantCode,
                            station_id: hydrantStationId,
                            marker_id: markerId,
                            hydrant_type_id: hydrantTypeId || null
                        })
                    });
                    
                    const hydrantResult = await hydrantResponse.json();
                    
                    if (!hydrantResult.success && !hydrantResult.data) {
                        throw new Error(hydrantResult.message || 'Failed to create hydrant');
                    }
                }
                
                // School
                else if (categoryName.toLowerCase() === 'school') {
                    const schoolName = document.getElementById('schoolName')?.value;
                    
                    if (!schoolName) {
                        alert('Please enter school name');
                        return;
                    }
                    
                    const schoolResponse = await fetch('/api/schools', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            school_name: schoolName,
                            marker_id: markerId
                        })
                    });
                    
                    const schoolResult = await schoolResponse.json();
                    
                    if (!schoolResult.success && !schoolResult.data) {
                        throw new Error(schoolResult.message || 'Failed to create school');
                    }
                }
                
                // Evacuation Center - no extra fields needed
                
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
        
        console.log('Save button handler attached successfully');
    } else {
        console.error('Save marker button not found!');
    }
}, 1000);

window.closeModal = function() {
    console.log('closeModal called');
    const modal = document.getElementById('markerModal');
    if (modal) modal.style.display = 'none';
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) document.body.removeChild(loadingModal);
};

window.openModal = function() {
    console.log('openModal called with coordinates:', selectedLat, selectedLng);
    
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
    
    const provinceSelect = document.getElementById('stationProvince');
    const citySelect = document.getElementById('stationCity');
    const barangaySelect = document.getElementById('stationBarangay');
    const streetInput = document.getElementById('stationStreet');
    
    if (provinceSelect) provinceSelect.value = '';
    if (citySelect) {
        citySelect.disabled = true;
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    }
    if (barangaySelect) {
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    }
    if (streetInput) streetInput.value = '';
    
    const stationName = document.getElementById('stationName');
    const stationCode = document.getElementById('stationCode');
    const stationContact = document.getElementById('stationContact');
    const stationEmail = document.getElementById('stationEmail');
    const hydrantCode = document.getElementById('hydrantCode');
    const hydrantStation = document.getElementById('hydrantStation');
    const schoolName = document.getElementById('schoolName');
    
    if (stationName) stationName.value = '';
    if (stationCode) stationCode.value = '';
    if (stationContact) stationContact.value = '';
    if (stationEmail) stationEmail.value = '';
    if (hydrantCode) hydrantCode.value = '';
    if (hydrantStation) hydrantStation.value = '';
    if (schoolName) schoolName.value = '';
    
    const previewDiv = document.getElementById('stationCodePreview');
    if (previewDiv) previewDiv.style.display = 'none';
    
    const hydrantPreviewDiv = document.getElementById('hydrantCodePreview');
    if (hydrantPreviewDiv) hydrantPreviewDiv.style.display = 'none';
    
    const modal = document.getElementById('markerModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal opened');
    } else {
        console.error('Modal element not found!');
    }
};

window.openAddCategory = function() {
    console.log('openAddCategory called');
    const modal = document.getElementById('categoryModal');
    if (modal) modal.style.display = 'flex';
};

window.closeAddCategory = function() {
    console.log('closeAddCategory called');
    const modal = document.getElementById('categoryModal');
    if (modal) modal.style.display = 'none';
};

window.emergencyCall = function() {
    if (confirm('Call 911 for emergency services?')) {
        window.location.href = 'tel:911';
    }
};

window.openEditModal = function(marker) {
    console.log('Edit marker:', marker);
    // Implement edit functionality as needed
};

console.log('Map.js fully loaded - all fixes applied');