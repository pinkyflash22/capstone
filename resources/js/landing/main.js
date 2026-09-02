// resources/js/landing/main.js

import { getReliableLocationFix } from '../location-accuracy.js';

// Global variables
let map;
let stations = [];
let hydrants = [];
let stationMarkers = [];
let hydrantMarkers = [];
let userMarker = null;
let currentStationLayer = null;
let currentHydrantLayer = null;
let anonymousReportInFlight = false;
let landingMapMarkerLayer = null;
let landingMapBoundaryLayer = null;
let landingMapRiskLayer = null;
let landingMapActiveFilter = 'all';
let mobilePreviewMap = null;
let mobilePreviewMarkerLayer = null;
let mobilePreviewBoundaryLayer = null;
let mobilePreviewRiskLayer = null;
let mobilePreviewActiveFilter = 'all';
let mobilePreviewResourcesLoaded = false;
let mobilePreviewResourcesLoading = false;
let landingResourceMarkers = [];
let landingBoundaryShapes = [];
let landingRiskAreas = [];
const LANDING_INCIDENT_VISIBILITY_RADIUS_METERS = 300;
const LANDING_RESPONDER_CACHE_KEY = 'pyrosense:nearestResponder';
const LANDING_STATION_DIRECTORY_CACHE_KEY = 'pyrosense:stationDirectory';
const LANDING_RESPONDER_CACHE_MAX_AGE_MS = 60 * 1000;
const LANDING_RESPONDER_CACHE_MAX_DISTANCE_METERS = 150;
const OFFLINE_EMERGENCY_QUEUE_TYPE = 'offline_emergency_fire_report';
const OFFLINE_BFP_DIAL_NUMBER = '09171891611';
const OFFLINE_EMERGENCY_TITLE = 'Offline Fire Emergency';
let offlineEmergencyCallInFlight = false;

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadStations();
    loadHydrants();
    initEventListeners();
    initMobileEntryPage();
    primeVisitorLocation();
    initScrollAnimations();
});

// Initialize map
function initMap() {
    // Default center (Pangasinan)
    map = L.map('map').setView([16.0433, 120.3333], 11);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    landingMapBoundaryLayer = L.layerGroup().addTo(map);
    landingMapRiskLayer = L.layerGroup().addTo(map);
    landingMapMarkerLayer = L.layerGroup().addTo(map);

    loadLandingMapResources();
}

// Load stations from database
function loadStations() {
    showLoading('stations');
    
    fetch('/api/stations')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                stations = (Array.isArray(data.data) ? data.data : []).map((station) => ({
                    personnel: station.personnel ?? 'N/A',
                    equipment: station.equipment ?? 'Station resources available',
                    captain: station.captain ?? 'Officer on duty',
                    ...station,
                }));
                cacheLandingStationDirectory(stations);
                updateNearbyStations(stations.slice(0, 5));
                renderLandingMapResources();
                renderMobilePreviewMarkers();
            }
        })
        .catch(error => {
            console.error('Error loading stations:', error);
            useSampleStations();
        })
        .finally(() => {
            hideLoading('stations');
        });
}

// Load hydrants from database
function loadHydrants() {
    fetch('/api/hydrants')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hydrants = (Array.isArray(data.data) ? data.data : []).map((hydrant) => ({
                    status: hydrant.status ?? 'operational',
                    ...hydrant,
                }));
                renderMobilePreviewMarkers();
            }
        })
        .catch(error => {
            console.error('Error loading hydrants:', error);
        });
}

// Use sample stations if database fails
function useSampleStations() {
    stations = [
        {
            id: 1,
            name: 'Urbiztondo Fire Station',
            address: 'Urbiztondo, Pangasinan',
            lat: 15.8233,
            lng: 120.3294,
            phone: '(075) 123-4567',
            personnel: 25,
            equipment: '3 fire trucks',
            captain: 'Capt. Reyes'
        },
        {
            id: 2,
            name: 'Mangatarem Fire Station',
            address: 'Mangatarem, Pangasinan',
            lat: 15.7875,
            lng: 120.2922,
            phone: '(075) 234-5678',
            personnel: 20,
            equipment: '2 fire trucks',
            captain: 'Capt. Santos'
        },
        {
            id: 3,
            name: 'San Carlos Fire Station',
            address: 'San Carlos City, Pangasinan',
            lat: 15.9281,
            lng: 120.3489,
            phone: '(075) 345-6789',
            personnel: 30,
            equipment: '4 fire trucks',
            captain: 'Capt. Villanueva'
        },
        {
            id: 4,
            name: 'Lingayen Fire Station HQ',
            address: 'Lingayen, Pangasinan',
            lat: 16.0215,
            lng: 120.2319,
            phone: '(075) 456-7890',
            personnel: 35,
            equipment: '5 fire trucks',
            captain: 'Capt. Mendoza'
        },
        {
            id: 5,
            name: 'Dagupan Fire Station',
            address: 'Dagupan City, Pangasinan',
            lat: 16.0433,
            lng: 120.3333,
            phone: '(075) 567-8901',
            personnel: 28,
            equipment: '3 fire trucks',
            captain: 'Capt. Fernandez'
        }
    ];
    
    addStationMarkers();
    updateNearbyStations(stations.slice(0, 5));
}
const stationIcon = L.divIcon({
    className: 'station-marker',
    html: `
        <div style="position: relative;">
            <i class="fa-solid fa-fire-station" style="color: #dc2626; font-size: 2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>
            <span style="position: absolute; bottom: -2px; right: -2px; width: 10px; height: 10px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
        </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

const stationIconSelected = L.divIcon({
    className: 'station-marker-selected',
    html: `
        <div style="position: relative;">
            <i class="fa-solid fa-fire-station" style="color: #b91c1c; font-size: 2.5rem; filter: drop-shadow(0 4px 8px rgba(220,38,38,0.4));"></i>
            <span style="position: absolute; bottom: -2px; right: -2px; width: 12px; height: 12px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
        </div>
    `,
    iconSize: [40, 40],
    iconAnchor: [20, 40],
    popupAnchor: [0, -40]
});

const hydrantIcon = L.divIcon({
    className: 'hydrant-marker',
    html: `
        <div style="position: relative;">
            <i class="fa-solid fa-fire-hydrant" style="color: #2563eb; font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>
            <span style="position: absolute; bottom: -2px; right: -2px; width: 8px; height: 8px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
        </div>
    `,
    iconSize: [28, 28],
    iconAnchor: [14, 28],
    popupAnchor: [0, -28]
});

const hydrantIconWarning = L.divIcon({
    className: 'hydrant-marker-warning',
    html: `
        <div style="position: relative;">
            <i class="fa-solid fa-fire-hydrant" style="color: #f59e0b; font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>
            <span style="position: absolute; bottom: -2px; right: -2px; width: 8px; height: 8px; background: #f59e0b; border: 2px solid white; border-radius: 50%;"></span>
        </div>
    `,
    iconSize: [28, 28],
    iconAnchor: [14, 28],
    popupAnchor: [0, -28]
});

const userLocationIcon = L.divIcon({
    className: 'user-location-marker',
    html: `
        <div style="position: relative;">
            <i class="fa-solid fa-circle-user" style="color: #3b82f6; font-size: 2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 30px; height: 30px; background: rgba(59,130,246,0.2); border-radius: 50%; animation: pulse 2s infinite;"></div>
        </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% {
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0.8;
        }
        70% {
            transform: translate(-50%, -50%) scale(1.5);
            opacity: 0;
        }
        100% {
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0;
        }
    }
    
    .station-marker, .hydrant-marker, .user-location-marker {
        background: transparent;
        border: none;
    }
    
    .station-marker i, .hydrant-marker i {
        transition: transform 0.2s;
    }
    
    .station-marker:hover i, .hydrant-marker:hover i {
        transform: scale(1.1);
    }
`;
document.head.appendChild(style);
function addStationMarkers() {
    // Clear existing markers
    stationMarkers.forEach(marker => map.removeLayer(marker));
    stationMarkers = [];
    
    stations.forEach(station => {
        const marker = L.marker([station.lat, station.lng], { 
            icon: stationIcon,
            riseOnHover: true
        }).addTo(map);
        
        marker.bindPopup(`
            <div style="padding: 1rem; min-width: 250px;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 2px solid #fee2e2; padding-bottom: 0.75rem;">
                    <i class="fa-solid fa-fire-station" style="color: #dc2626; font-size: 2rem;"></i>
                    <div>
                        <strong style="color: #dc2626; font-size: 1.2rem;">${station.name}</strong>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                    <div style="background: #f9fafb; padding: 0.5rem; border-radius: 8px; text-align: center;">
                        <i class="fa-solid fa-phone" style="color: #dc2626;"></i>
                        <div style="font-size: 0.85rem; margin-top: 0.25rem;">${station.phone}</div>
                    </div>
                    <div style="background: #f9fafb; padding: 0.5rem; border-radius: 8px; text-align: center;">
                        <i class="fa-solid fa-users" style="color: #dc2626;"></i>
                        <div style="font-size: 0.85rem; margin-top: 0.25rem;">${station.personnel}</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #f9fafb; border-radius: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #dc2626; width: 20px;"></i>
                    <span style="font-size: 0.9rem;">${station.address}</span>
                </div>
                
                <div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #f9fafb; border-radius: 8px;">
                    <i class="fa-solid fa-truck" style="color: #dc2626; width: 20px;"></i>
                    <span style="font-size: 0.9rem;">${station.equipment}</span>
                </div>
                
                <div style="margin-bottom: 1rem; padding: 0.5rem; background: #f9fafb; border-radius: 8px;">
                    <i class="fa-solid fa-user-tie" style="color: #dc2626; width: 20px;"></i>
                    <span style="font-size: 0.9rem;">${station.captain}</span>
                </div>
                
                <button onclick="openStationModal(${station.id})" 
                        style="background: #dc2626; color: white; border: none; 
                               padding: 0.75rem; border-radius: 8px; 
                               cursor: pointer; width: 100%; font-weight: 600;
                               display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i class="fa-solid fa-eye"></i>
                    View Full Details
                </button>
            </div>
        `);
        
        // Add hover effect
        marker.on('mouseover', function() {
            this.setIcon(stationIconSelected);
        });
        
        marker.on('mouseout', function() {
            this.setIcon(stationIcon);
        });
        
        stationMarkers.push(marker);
    });
}

// Update addHydrantMarkers function
function addHydrantMarkers() {
    // Clear existing markers
    hydrantMarkers.forEach(marker => map.removeLayer(marker));
    hydrantMarkers = [];
    
    hydrants.forEach(hydrant => {
        const icon = hydrant.status === 'operational' ? hydrantIcon : hydrantIconWarning;
        
        const marker = L.marker([hydrant.lat, hydrant.lng], {
            icon: icon,
            riseOnHover: true
        }).addTo(map);
        
        marker.bindPopup(`
            <div style="padding: 1rem; min-width: 220px;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.75rem;">
                    <i class="fa-solid fa-fire-hydrant" style="color: #2563eb; font-size: 1.8rem;"></i>
                    <div>
                        <strong style="color: #2563eb; font-size: 1.1rem;">${hydrant.name}</strong>
                    </div>
                </div>
                
                <div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #f9fafb; border-radius: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #2563eb; width: 20px;"></i>
                    <span style="font-size: 0.9rem;">${hydrant.address}</span>
                </div>
                
                <div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #f9fafb; border-radius: 8px;">
                    <i class="fa-solid fa-circle-check" style="color: #2563eb; width: 20px;"></i>
                    <span style="font-size: 0.9rem; text-transform: capitalize;">Status: ${hydrant.status}</span>
                </div>
                
                <div style="background: ${hydrant.status === 'operational' ? '#10b981' : '#f59e0b'}; color: white; padding: 0.5rem; border-radius: 8px; text-align: center; font-size: 0.9rem;">
                    <i class="fa-solid fa-${hydrant.status === 'operational' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    ${hydrant.status === 'operational' ? 'Operational' : 'Under Maintenance'}
                </div>
            </div>
        `);
        
        hydrantMarkers.push(marker);
    });
}

// Optional: Add cluster group for better performance with many markers
// Uncomment if you have many markers

const stationCluster = typeof L.markerClusterGroup === 'function' ? L.markerClusterGroup() : null;
const hydrantCluster = typeof L.markerClusterGroup === 'function' ? L.markerClusterGroup() : null;

function addStationMarkersWithCluster() {
    if (!stationCluster) {
        addStationMarkers();
        return;
    }

    stations.forEach(station => {
        const marker = L.marker([station.lat, station.lng], { icon: stationIcon });
        marker.bindPopup(`...`); // Your popup content
        stationCluster.addLayer(marker);
    });
    map.addLayer(stationCluster);
}

function addHydrantMarkersWithCluster() {
    if (!hydrantCluster) {
        addHydrantMarkers();
        return;
    }

    hydrants.forEach(hydrant => {
        const marker = L.marker([hydrant.lat, hydrant.lng], { 
            icon: hydrant.status === 'operational' ? hydrantIcon : hydrantIconWarning 
        });
        marker.bindPopup(`...`); // Your popup content
        hydrantCluster.addLayer(marker);
    });
    map.addLayer(hydrantCluster);
} 
// Add station markers to map
// function addStationMarkers() {
//     // Clear existing markers
//     stationMarkers.forEach(marker => map.removeLayer(marker));
//     stationMarkers = [];
    
//     stations.forEach(station => {
//         const marker = L.marker([station.lat, station.lng]).addTo(map);
        
//         marker.bindPopup(`
//             <div style="padding: 0.5rem;">
//                 <strong style="color: #dc2626; font-size: 1.1rem;">${station.name}</strong><br>
//                 <span style="font-size: 0.9rem;">${station.address}</span><br>
//                 <span style="font-size: 0.85rem; color: #6b7280;">
//                     <i class="fa-solid fa-phone"></i> ${station.phone}<br>
//                     <i class="fa-solid fa-users"></i> ${station.personnel} personnel<br>
//                     <i class="fa-solid fa-truck"></i> ${station.equipment}<br>
//                     <i class="fa-solid fa-user-tie"></i> ${station.captain}
//                 </span><br>
//                 <button onclick="openStationModal(${station.id})" 
//                         style="background: #dc2626; color: white; border: none; 
//                                padding: 0.5rem 1rem; border-radius: 6px; 
//                                margin-top: 0.5rem; cursor: pointer; width: 100%;">
//                     View Details
//                 </button>
//             </div>
//         `);
        
//         stationMarkers.push(marker);
//     });
// }

// // Add hydrant markers to map
// function addHydrantMarkers() {
//     // Clear existing markers
//     hydrantMarkers.forEach(marker => map.removeLayer(marker));
//     hydrantMarkers = [];
    
//     hydrants.forEach(hydrant => {
//         const color = hydrant.status === 'operational' ? '#10b981' : '#f59e0b';
        
//         const marker = L.marker([hydrant.lat, hydrant.lng], {
//             icon: L.divIcon({
//                 className: 'hydrant-marker',
//                 html: `<i class="fa-solid fa-fire-hydrant" style="color: ${color}; font-size: 1.5rem;"></i>`
//             })
//         }).addTo(map);
        
//         marker.bindPopup(`
//             <div style="padding: 0.5rem;">
//                 <strong>${hydrant.name}</strong><br>
//                 <span style="font-size: 0.9rem;">${hydrant.address}</span><br>
//                 <span class="status-${hydrant.status}" style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 20px; font-size: 0.8rem; margin-top: 0.5rem;">
//                     ${hydrant.status === 'operational' ? '✅ Operational' : '⚠️ Maintenance'}
//                 </span>
//             </div>
//         `);
        
//         hydrantMarkers.push(marker);
//     });
// }

// Toggle map layers
function toggleStations(show) {
    if (show) {
        stationMarkers.forEach(marker => marker.addTo(map));
    } else {
        stationMarkers.forEach(marker => map.removeLayer(marker));
    }
}

function toggleHydrants(show) {
    if (show) {
        addHydrantMarkers();
    } else {
        hydrantMarkers.forEach(marker => map.removeLayer(marker));
        hydrantMarkers = [];
    }
}

// Initialize event listeners
function initEventListeners() {
    // Search button
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', searchLocation);
    }

    // Location button
    const locationBtn = document.getElementById('locationBtn');
    if (locationBtn) {
        locationBtn.addEventListener('click', getUserLocation);
    }

    // Search input enter key
    const searchInput = document.getElementById('locationSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchLocation();
        });
    }

    // Desktop map filter chips
    const landingMapFilterButtons = document.querySelectorAll('[data-landing-map-filter]');

    landingMapFilterButtons.forEach((button) => {
        button.addEventListener('click', function() {
            landingMapActiveFilter = this.dataset.landingMapFilter || 'all';
            landingMapFilterButtons.forEach((chip) => {
                chip.classList.toggle('active', chip === this);
            });
            renderLandingMapResources();
        });
    });

    // Modal close on outside click
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

function initMobileEntryPage() {
    const anonymousReportButtons = document.querySelectorAll('[data-anonymous-report-trigger]');
    const openMapButton = document.querySelector('[data-mobile-open-map]');
    const emergencyCallButtons = document.querySelectorAll('[data-emergency-call]');
    const emergencyCallModal = document.getElementById('emergencyCallConfirm');
    const emergencyCallConfirmButton = document.querySelector('[data-emergency-call-confirm]');
    const emergencyCallCancelButtons = document.querySelectorAll('[data-emergency-call-cancel]');
    const emergencyCallTitle = document.getElementById('emergencyCallConfirmTitle');
    const emergencyCallMessage = document.getElementById('emergencyCallConfirmMessage');
    const emergencyCallLocationPanel = document.getElementById('emergencyCallLocationPanel');
    const emergencyCallCoords = document.getElementById('emergencyCallCoords');
    const emergencyCallCachedLocationWrap = document.getElementById('emergencyCallCachedLocationWrap');
    const emergencyCallCachedLocation = document.getElementById('emergencyCallCachedLocation');
    const emergencyCallCopyCoordsButton = document.getElementById('emergencyCallCopyCoords');
    const emergencyCallBfpButton = document.getElementById('emergencyCallBfpButton');
    const mobileMapModal = document.getElementById('mobileMapModal');
    const mobileMapCloseButtons = document.querySelectorAll('[data-mobile-map-close]');
    const mobileMapFilterButtons = document.querySelectorAll('[data-mobile-map-filter]');

    if (window.innerWidth <= 768 && window.location.hash === '#map') {
        document.body.classList.add('mobile-show-landing');
    }

    anonymousReportButtons.forEach((button) => {
        button.addEventListener('click', submitAnonymousIncidentReport);
    });

    function resetEmergencyCallModal() {
        if (emergencyCallTitle) {
            emergencyCallTitle.textContent = 'Call emergency?';
        }

        if (emergencyCallMessage) {
            emergencyCallMessage.textContent = 'This will open your phone app and dial 911.';
        }

        if (emergencyCallLocationPanel) {
            emergencyCallLocationPanel.hidden = true;
        }

        if (emergencyCallCoords) {
            emergencyCallCoords.textContent = 'Unavailable';
        }

        if (emergencyCallCachedLocationWrap) {
            emergencyCallCachedLocationWrap.hidden = true;
        }

        if (emergencyCallCachedLocation) {
            emergencyCallCachedLocation.textContent = 'Unavailable';
        }

        if (emergencyCallCopyCoordsButton) {
            emergencyCallCopyCoordsButton.hidden = true;
            emergencyCallCopyCoordsButton.disabled = true;
            delete emergencyCallCopyCoordsButton.dataset.coords;
        }

        if (emergencyCallBfpButton) {
            emergencyCallBfpButton.hidden = true;
            emergencyCallBfpButton.textContent = 'Call BFP station';
            delete emergencyCallBfpButton.dataset.phone;
        }

        if (emergencyCallConfirmButton) {
            emergencyCallConfirmButton.textContent = 'Call 911';
        }
    }

    function closeEmergencyCallConfirm() {
        if (!emergencyCallModal) return;
        resetEmergencyCallModal();
        emergencyCallModal.classList.remove('is-open');
        emergencyCallModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function openEmergencyCallConfirm(event) {
        if (!emergencyCallModal) {
            return;
        }

        event?.preventDefault();
        emergencyCallModal.classList.add('is-open');
        emergencyCallModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        emergencyCallConfirmButton?.focus?.({ preventScroll: true });
    }

    function openEmergencyDialer(number) {
        if (!number) {
            return;
        }

        closeEmergencyCallConfirm();
        window.location.href = `tel:${number}`;
    }

    async function copyEmergencyCoordinates() {
        const coords = emergencyCallCopyCoordsButton?.dataset.coords || '';
        if (!coords) {
            return;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(coords);
            } else {
                const helper = document.createElement('textarea');
                helper.value = coords;
                helper.setAttribute('readonly', 'readonly');
                helper.style.position = 'absolute';
                helper.style.left = '-9999px';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                helper.remove();
            }

            showLandingToast('Coordinates copied. Tell the dispatcher this location.', false, 2800);
        } catch (error) {
            console.warn('Unable to copy emergency coordinates:', error);
            showLandingToast('Unable to copy coordinates on this device.', true, 3200);
        }
    }

    function renderOfflineEmergencyModal(context = {}) {
        if (emergencyCallTitle) {
            emergencyCallTitle.textContent = 'Offline emergency call';
        }

        if (emergencyCallLocationPanel) {
            emergencyCallLocationPanel.hidden = false;
        }

        if (emergencyCallCoords) {
            emergencyCallCoords.textContent = context.coordsText || 'Location could not be determined.';
        }

        if (emergencyCallCachedLocationWrap) {
            emergencyCallCachedLocationWrap.hidden = !context.cachedLocation;
        }

        if (emergencyCallCachedLocation) {
            emergencyCallCachedLocation.textContent = context.cachedLocation || 'Unavailable';
        }

        if (emergencyCallCopyCoordsButton) {
            const hasCoords = Boolean(context.coordsText);
            emergencyCallCopyCoordsButton.hidden = !hasCoords;
            emergencyCallCopyCoordsButton.disabled = !hasCoords;
            emergencyCallCopyCoordsButton.dataset.coords = context.coordsText || '';
        }

        if (emergencyCallBfpButton) {
            emergencyCallBfpButton.hidden = false;
            const station = context.station || resolveLandingEmergencyStation();
            emergencyCallBfpButton.dataset.phone = normalizeStationPhone(station.phone);
            emergencyCallBfpButton.textContent = `Call ${station.name || 'BFP station'}`;
        }

        if (emergencyCallMessage) {
            emergencyCallMessage.textContent = context.message;
        }
    }

    async function openOfflineEmergencyCallFlow(event, preferredStation = null) {
        event?.preventDefault();

        if (offlineEmergencyCallInFlight) {
            showLandingToast('Emergency fallback is already preparing...', false, 2200);
            return;
        }

        offlineEmergencyCallInFlight = true;
        setEmergencyCallButtonsLoading(true);
        showLandingToast('No internet. Getting your location...', false, 2400);

        let coords = null;

        try {
            coords = await getEmergencyGpsLocation();
        } catch (error) {
            console.warn('Offline emergency GPS capture failed:', error);
        }

        const cachedLocation = getCachedEmergencyLocationLabel(coords);
        const station = preferredStation || resolveLandingEmergencyStation(coords);

        if (coords) {
            const queued = queueOfflineEmergencyIncident(coords, cachedLocation);
            renderOfflineEmergencyModal({
                coordsText: formatEmergencyCoordinates(coords),
                cachedLocation,
                station,
                message: queued.queueItem
                    ? (
                        queued.wasAlreadyQueued
                            ? 'This offline fire emergency report is already waiting to sync. Your phone app can still call BFP or 911 now.'
                            : 'A pending fire emergency report was saved on this device and will sync automatically when internet returns.'
                    )
                    : 'Your location is ready for the call, but PyroSense could not save an offline report on this screen.',
            });
            showLandingToast(
                queued.queueItem
                    ? (
                        queued.wasAlreadyQueued
                            ? 'Offline report already pending sync.'
                            : 'Offline fire report saved. You can call BFP or 911 now.'
                    )
                    : 'Coordinates ready. You can call BFP or 911 now.',
                false,
                3400
            );
        } else {
            renderOfflineEmergencyModal({
                coordsText: '',
                cachedLocation,
                station,
                message: 'Location could not be determined, so no offline fire report was saved. You can still call BFP or 911 immediately.',
            });
            showLandingToast('Location could not be determined. Call BFP or 911 now.', true, 3600);
        }

        openEmergencyCallConfirm();
        offlineEmergencyCallInFlight = false;
        setEmergencyCallButtonsLoading(false);
    }

    emergencyCallButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const station = resolveLandingEmergencyStation();
            if (station?.station_id) button.dataset.voiceStationId = String(station.station_id);
            if (station?.phone) button.dataset.fallbackPhone = normalizeStationPhone(station.phone);
            if (station?.name) button.dataset.fallbackStationName = station.name;

            if (!window.navigator.onLine) {
                openOfflineEmergencyCallFlow(event, station);
                return;
            }

            if (window.PyroSenseVoiceCall?.configured && typeof window.pyroStartVoiceCall === 'function') {
                event.preventDefault();
                window.pyroStartVoiceCall(button, event);
                return;
            }

            resetEmergencyCallModal();
            openEmergencyCallConfirm(event);
        });
    });

    emergencyCallCancelButtons.forEach((button) => {
        button.addEventListener('click', closeEmergencyCallConfirm);
    });

    emergencyCallConfirmButton?.addEventListener('click', () => openEmergencyDialer('911'));
    emergencyCallCopyCoordsButton?.addEventListener('click', copyEmergencyCoordinates);
    emergencyCallBfpButton?.addEventListener('click', () => openEmergencyDialer(emergencyCallBfpButton.dataset.phone || OFFLINE_BFP_DIAL_NUMBER));

    window.addEventListener('pyro:voice-call-failed', (event) => {
        const trigger = event.detail?.trigger;
        const station = resolveLandingEmergencyStation(null, trigger?.dataset?.voiceStationId);
        renderOfflineEmergencyModal({
            coordsText: formatEmergencyCoordinates(readCachedVisitorLocation()),
            cachedLocation: getCachedEmergencyLocationLabel(),
            station,
            message: `The internet call could not connect. Use your phone network to call ${station.name || 'the BFP station'} instead.`,
        });
        openEmergencyCallConfirm();
        showLandingToast('Internet call unavailable. Station telephone fallback is ready.', true, 3600);
    });

    function closeMobileMapModal() {
        if (!mobileMapModal) return;
        mobileMapModal.classList.remove('is-open');
        mobileMapModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function openMobileMapModal() {
        if (!mobileMapModal) {
            document.body.classList.add('mobile-show-landing');
            window.requestAnimationFrame(() => {
                document.getElementById('map')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                map?.invalidateSize?.();
            });
            return;
        }

        mobileMapModal.classList.add('is-open');
        mobileMapModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        window.requestAnimationFrame(() => {
            initMobilePreviewMap();
            loadMobilePreviewResources();
            mobilePreviewMap?.invalidateSize?.();
        });
    }

    openMapButton?.addEventListener('click', openMobileMapModal);

    mobileMapCloseButtons.forEach((button) => {
        button.addEventListener('click', closeMobileMapModal);
    });

    mobileMapFilterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            mobilePreviewActiveFilter = button.dataset.mobileMapFilter || 'all';
            mobileMapFilterButtons.forEach((chip) => {
                chip.classList.toggle('is-active', chip === button);
            });
            renderLandingMapResources();
            renderMobilePreviewMarkers();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (emergencyCallModal?.classList.contains('is-open')) {
            closeEmergencyCallConfirm();
        } else if (mobileMapModal?.classList.contains('is-open')) {
             closeMobileMapModal();
        }
    });
}

function initMobilePreviewMap() {
    const mapEl = document.getElementById('mobileMapPreview');
    if (!mapEl || mobilePreviewMap || typeof L === 'undefined') {
        return;
    }

    mobilePreviewMap = L.map(mapEl, {
        zoomControl: true,
        attributionControl: true,
    }).setView([16.0433, 120.3333], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mobilePreviewMap);

    mobilePreviewBoundaryLayer = L.layerGroup().addTo(mobilePreviewMap);
    mobilePreviewRiskLayer = L.layerGroup().addTo(mobilePreviewMap);
    mobilePreviewMarkerLayer = L.layerGroup().addTo(mobilePreviewMap);
}

async function loadLandingMapResources() {
    await loadMobilePreviewResources();
}

async function loadMobilePreviewResources() {
    if (mobilePreviewResourcesLoaded || mobilePreviewResourcesLoading) {
        renderLandingMapResources();
        renderMobilePreviewMarkers();
        return;
    }

    mobilePreviewResourcesLoading = true;

    try {
        const [markersResult, shapesResult, risksResult] = await Promise.allSettled([
            fetch('/api/markers', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then((response) => response.ok ? response.json() : Promise.reject(response)),
            fetch('/api/shapes', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then((response) => response.ok ? response.json() : Promise.reject(response)),
            fetch('/api/fire-risk-areas?levels=medium,high', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then((response) => response.ok ? response.json() : Promise.reject(response)),
        ]);

        if (markersResult.status === 'fulfilled') {
            landingResourceMarkers = normalizeLandingResourcePayload(markersResult.value);
        }

        if (shapesResult.status === 'fulfilled') {
            landingBoundaryShapes = Array.isArray(shapesResult.value) ? shapesResult.value : [];
        }

        if (risksResult.status === 'fulfilled') {
            landingRiskAreas = Array.isArray(risksResult.value?.data) ? risksResult.value.data : [];
        }

        mobilePreviewResourcesLoaded = true;
    } catch (error) {
        console.warn('Unable to load mobile map preview resources:', error);
    } finally {
        mobilePreviewResourcesLoading = false;
        renderLandingMapResources();
        renderMobilePreviewMarkers();
    }
}

function normalizeLandingResourcePayload(payload) {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload?.markers)) return payload.markers;
    return [];
}

function normalizeLandingCategory(marker = {}) {
    const direct = String(marker.category?.name || marker.category || marker.marker_category || '').trim().toLowerCase();

    if (direct.includes('station')) return 'station';
    if (direct.includes('hydrant')) return 'hydrant';
    if (direct.includes('barangay') || direct.includes('hall')) return 'barangay';
    if (direct.includes('fire') || direct.includes('incident')) return 'fire';
    if (direct.includes('risk')) return 'risk';
    if (direct.includes('hazard')) return 'hazard';
    if (direct.includes('school')) return 'school';
    if (direct.includes('bridge')) return 'bridge';

    const categoryId = Number(marker.markerCategory_id || marker.marker_category_id || marker.category_id);
    return {
        1: 'station',
        2: 'hydrant',
        3: 'fire',
        4: 'hydrant',
        5: 'school',
        6: 'barangay',
        7: 'bridge',
        8: 'risk',
        9: 'hazard',
    }[categoryId] || 'other';
}

function shouldShowLandingResource(marker = {}) {
    return shouldShowLandingResourceForFilter(marker, mobilePreviewActiveFilter);
}

function shouldShowLandingResourceForFilter(marker = {}, activeFilter = 'all') {
    const category = normalizeLandingCategory(marker);

    if (category === 'fire' && !isLandingIncidentNearVisitor(marker)) {
        return false;
    }

    if (activeFilter === 'all') return true;
    if (activeFilter === 'stations') return category === 'station';
    if (activeFilter === 'hydrants') return category === 'hydrant';
    if (activeFilter === 'incidents') return category === 'fire';
    if (activeFilter === 'barangay') return category === 'barangay';
    if (activeFilter === 'risks') return ['risk', 'hazard'].includes(category);
    if (activeFilter === 'other') {
        return !['station', 'hydrant', 'fire', 'barangay', 'risk', 'hazard'].includes(category);
    }

    return true;
}

function isLandingIncidentNearVisitor(marker = {}) {
    const visitorCoords = getLandingVisitorCoords();
    if (!visitorCoords) {
        return false;
    }

    const lat = Number(marker.lat);
    const lng = Number(marker.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return false;
    }

    const distanceKm = calculateDistance(visitorCoords.lat, visitorCoords.lng, lat, lng);
    return distanceKm * 1000 <= LANDING_INCIDENT_VISIBILITY_RADIUS_METERS;
}

function getLandingVisitorCoords(maxAgeMs = 10 * 60 * 1000) {
    const cached = readCachedVisitorLocation();
    const lat = Number(cached?.lat);
    const lng = Number(cached?.lng);
    const timestamp = Number(cached?.timestamp || 0);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    if (Date.now() - timestamp > maxAgeMs) {
        return null;
    }

    return { lat, lng };
}

function landingPreviewIcon(marker = {}) {
    const category = normalizeLandingCategory(marker);
    const asset = landingPreviewAsset(category);
    const size = category === 'fire' ? 40 : 34;

    return L.icon({
        iconUrl: asset,
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
        popupAnchor: [0, -size + 4],
        className: 'landing-preview-marker-icon',
    });
}

function landingPreviewAsset(category) {
    return {
        station: '/station.png',
        hydrant: '/hydrant.png',
        barangay: '/hall.png',
        fire: '/fire.png',
        risk: '/hazard.png',
        hazard: '/hazard.png',
        bridge: '/blockroad.png',
        school: '/logo.png',
        other: '/logo.png',
    }[category] || '/logo.png';
}

function escapeLandingHtml(value = '') {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function landingResourceTitle(marker = {}) {
    return marker.station_public?.name
        || marker.barangay_public?.name
        || marker.hydrant_public?.code
        || marker.title
        || {
            station: 'Fire station',
            hydrant: 'Fire hydrant',
            barangay: 'Barangay hall',
            fire: 'Fire incident',
            risk: 'Risk marker',
            hazard: 'Hazard marker',
        }[normalizeLandingCategory(marker)]
        || 'Map marker';
}

function landingResourceDescription(marker = {}) {
    return marker.station_public?.address
        || marker.barangay_public?.address
        || marker.description
        || marker.hydrant_public?.station_name
        || 'Resource location';
}

function landingResourcePopup(marker = {}) {
    const category = normalizeLandingCategory(marker);
    const meta = landingCategoryMeta(category);
    const title = escapeLandingHtml(landingResourceTitle(marker));
    const description = escapeLandingHtml(landingResourceDescription(marker));
    const status = marker.status
        ? `<span class="landing-preview-popup__status">${escapeLandingHtml(marker.status)}</span>`
        : '';
    const rows = landingResourceRows(marker, category)
        .map((row) => `
            <div class="landing-preview-popup__row">
                <span>${escapeLandingHtml(row.label)}</span>
                <strong>${escapeLandingHtml(row.value)}</strong>
            </div>
        `)
        .join('');

    return `
        <div class="landing-preview-popup">
            <div class="landing-preview-popup__head">
                <span class="landing-preview-popup__icon" style="--preview-accent:${meta.color}">
                    <img src="${landingPreviewAsset(category)}" alt="" loading="lazy">
                </span>
                <div class="landing-preview-popup__title">
                    <strong>${title}</strong>
                    <span>${description}</span>
                </div>
            </div>
            <div class="landing-preview-popup__meta">
                <span class="landing-preview-popup__chip" style="--preview-accent:${meta.color}">
                    ${escapeLandingHtml(meta.label)}
                </span>
                ${status}
            </div>
            ${rows ? `<div class="landing-preview-popup__rows">${rows}</div>` : ''}
        </div>
    `;
}

function landingCategoryMeta(category) {
    return {
        station: { label: 'Fire station', color: '#2563eb' },
        hydrant: { label: 'Hydrant', color: '#0ea5e9' },
        barangay: { label: 'Barangay hall', color: '#0f766e' },
        fire: { label: 'Fire incident', color: '#dc2626' },
        risk: { label: 'Risk marker', color: '#d97706' },
        hazard: { label: 'Hazard marker', color: '#ea580c' },
        bridge: { label: 'Bridge / road', color: '#64748b' },
        school: { label: 'School', color: '#7c3aed' },
        other: { label: 'Other marker', color: '#475569' },
    }[category] || { label: 'Map marker', color: '#475569' };
}

function landingResourceRows(marker = {}, category = 'other') {
    const rows = [];
    const add = (label, value) => {
        if (value !== undefined && value !== null && String(value).trim() !== '') {
            rows.push({ label, value: String(value) });
        }
    };

    if (category === 'station') {
        add('Contact', marker.station_public?.contact_number || marker.station?.contact_number);
        add('Hours', marker.station_public?.operating_hours);
        add('Chief', marker.station_public?.chief_name);
    } else if (category === 'hydrant') {
        add('Code', marker.hydrant_public?.code || marker.hydrant?.hydrant_code);
        add('Type', marker.hydrant_public?.type || marker.hydrant?.type);
        add('Station', marker.hydrant_public?.station_name);
    } else if (category === 'barangay') {
        add('Contact', marker.barangay_public?.contact_number);
        add('Hours', marker.barangay_public?.office_hours);
        add('Barangay', marker.barangay_public?.barangay_name);
    } else if (['fire', 'risk', 'hazard'].includes(category)) {
        add('Status', marker.status);
        add('Details', marker.description);
    }

    return rows.slice(0, 3);
}

function parseLandingGeojson(rawGeojson) {
    if (!rawGeojson) return null;

    if (typeof rawGeojson === 'string') {
        try {
            return JSON.parse(rawGeojson);
        } catch (error) {
            console.warn('Unable to parse preview GeoJSON:', error);
            return null;
        }
    }

    return rawGeojson;
}

function landingRiskAreaStyle(area = {}) {
    const isHigh = String(area.risk_level || '').toLowerCase() === 'high';

    return {
        color: isHigh ? '#b91c1c' : '#d97706',
        fillColor: isHigh ? '#ef4444' : '#f59e0b',
        fillOpacity: isHigh ? 0.28 : 0.22,
        opacity: 0.9,
        weight: isHigh ? 2.5 : 2,
    };
}

function renderLandingMapResources() {
    if (!map || !landingMapMarkerLayer || !landingMapBoundaryLayer || !landingMapRiskLayer) {
        return;
    }

    landingMapMarkerLayer.clearLayers();
    landingMapBoundaryLayer.clearLayers();
    landingMapRiskLayer.clearLayers();

    const bounds = [];

    landingBoundaryShapes.forEach((shape) => {
        const geojson = parseLandingGeojson(shape.geojson);
        if (!geojson) return;

        try {
            const layer = L.geoJSON(geojson, {
                style: {
                    color: '#2563eb',
                    fillColor: '#93c5fd',
                    fillOpacity: 0.08,
                    opacity: 0.72,
                    weight: 1.5,
                },
                interactive: true,
            }).bindPopup(`<strong>${escapeLandingHtml(shape.name || 'Boundary')}</strong>`);

            layer.addTo(landingMapBoundaryLayer);
            layer.eachLayer((child) => {
                const childBounds = child.getBounds?.();
                if (childBounds?.isValid?.()) bounds.push(childBounds);
            });
        } catch (error) {
            console.warn('Unable to render landing boundary:', { shape, error });
        }
    });

    if (['all', 'risks'].includes(landingMapActiveFilter)) {
        landingRiskAreas.forEach((area) => {
            const geojson = parseLandingGeojson(area.geojson);
            if (!geojson) return;

            try {
                const layer = L.geoJSON(geojson, {
                    style: landingRiskAreaStyle(area),
                    interactive: true,
                }).bindPopup(buildLandingRiskAreaPopup(area));

                layer.addTo(landingMapRiskLayer);
                layer.eachLayer((child) => {
                    const childBounds = child.getBounds?.();
                    if (childBounds?.isValid?.()) bounds.push(childBounds);
                });
            } catch (error) {
                console.warn('Unable to render landing risk area:', { area, error });
            }
        });
    }

    landingResourceMarkers
        .filter((marker) => shouldShowLandingResourceForFilter(marker, landingMapActiveFilter))
        .forEach((marker) => {
            const lat = Number(marker.lat);
            const lng = Number(marker.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            bounds.push([lat, lng]);
            L.marker([lat, lng], {
                icon: landingPreviewIcon(marker),
                riseOnHover: true,
            })
                .bindPopup(landingResourcePopup(marker), { maxWidth: 260 })
                .addTo(landingMapMarkerLayer);
        });

    const nextBounds = L.latLngBounds(bounds);
    if (nextBounds.isValid()) {
        map.fitBounds(nextBounds, { padding: [36, 36], maxZoom: 13 });
    }
}

function buildLandingRiskAreaPopup(area = {}) {
    return `
        <div class="landing-preview-popup landing-preview-popup--risk-area">
            <div class="landing-preview-popup__head">
                <span class="landing-preview-popup__icon" style="--preview-accent:${area.risk_level === 'high' ? '#dc2626' : '#d97706'}">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <div class="landing-preview-popup__title">
                    <strong>${escapeLandingHtml(area.name || 'Fire-risk area')}</strong>
                    <span>${escapeLandingHtml(area.city_name || 'Mapped boundary')}</span>
                </div>
            </div>
            <div class="landing-preview-popup__meta">
                <span class="landing-preview-popup__chip" style="--preview-accent:${area.risk_level === 'high' ? '#dc2626' : '#d97706'}">
                    ${escapeLandingHtml(String(area.risk_label || `${area.risk_level || 'Medium'} fire risk`))}
                </span>
            </div>
        </div>
    `;
}

function renderMobilePreviewMarkers() {
    if (!mobilePreviewMap) {
        return;
    }

    mobilePreviewMarkerLayer?.clearLayers();
    mobilePreviewBoundaryLayer?.clearLayers();
    mobilePreviewRiskLayer?.clearLayers();

    const bounds = [];

    landingBoundaryShapes.forEach((shape) => {
        const geojson = parseLandingGeojson(shape.geojson);
        if (!geojson) return;

        try {
            const layer = L.geoJSON(geojson, {
                style: {
                    color: '#2563eb',
                    fillColor: '#93c5fd',
                    fillOpacity: 0.08,
                    opacity: 0.72,
                    weight: 1.5,
                },
                interactive: true,
            }).bindPopup(`<strong>${escapeLandingHtml(shape.name || 'Boundary')}</strong>`);

            layer.addTo(mobilePreviewBoundaryLayer);
            layer.eachLayer((child) => {
                const childBounds = child.getBounds?.();
                if (childBounds?.isValid?.()) bounds.push(childBounds);
            });
        } catch (error) {
            console.warn('Unable to render preview boundary:', { shape, error });
        }
    });

    const shouldShowRiskAreas = ['all', 'risks'].includes(mobilePreviewActiveFilter);
    if (shouldShowRiskAreas) {
        landingRiskAreas.forEach((area) => {
            const geojson = parseLandingGeojson(area.geojson);
            if (!geojson) return;

            try {
                const layer = L.geoJSON(geojson, {
                    style: landingRiskAreaStyle(area),
                    interactive: true,
                }).bindPopup(buildLandingRiskAreaPopup(area));

                layer.addTo(mobilePreviewRiskLayer);
                layer.eachLayer((child) => {
                    const childBounds = child.getBounds?.();
                    if (childBounds?.isValid?.()) bounds.push(childBounds);
                });
            } catch (error) {
                console.warn('Unable to render preview risk area:', { area, error });
            }
        });
    }

    landingResourceMarkers
        .filter(shouldShowLandingResource)
        .forEach((marker) => {
            const lat = Number(marker.lat);
            const lng = Number(marker.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            bounds.push([lat, lng]);
            L.marker([lat, lng], {
                icon: landingPreviewIcon(marker),
                riseOnHover: true,
            })
                .bindPopup(landingResourcePopup(marker), { maxWidth: 240 })
                .addTo(mobilePreviewMarkerLayer);
        });

    if (bounds.length) {
        const nextBounds = L.latLngBounds(bounds);
        if (nextBounds.isValid()) {
            mobilePreviewMap.fitBounds(nextBounds, { padding: [24, 24], maxZoom: 14 });
        }
    }
}

async function primeVisitorLocation() {
    if (!navigator.geolocation) {
        showLandingToast('Location is not supported on this device.', true, 3200);
        return;
    }

    const cached = readCachedVisitorLocation();
    const isFresh = cached && (Date.now() - Number(cached.timestamp || 0) < 5 * 60 * 1000);

    if (isFresh) {
        showLandingToast('Location ready.', false, 2600);
        scheduleLandingResponderPrefetch(Number(cached.lat), Number(cached.lng));
        return;
    }

    if (navigator.permissions?.query) {
        try {
            const permission = await navigator.permissions.query({ name: 'geolocation' });
            if (permission.state !== 'granted') {
                showLandingToast('Tap a location or report action when you are ready to enable GPS.', false, 3600);
                return;
            }
        } catch (error) {
            return;
        }
    } else {
        return;
    }

    showLandingToast('Checking your location...', false, 2200);

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = Number(position.coords.latitude);
            const lng = Number(position.coords.longitude);
            persistVisitorLocation(lat, lng);
            scheduleLandingResponderPrefetch(lat, lng);
            showLandingToast('Location ready.', false, 3000);
        },
        (error) => {
            console.warn('Visitor location prefetch failed:', error);
            showLandingToast('Allow location access for one-tap reporting.', true, 4200);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function persistVisitorLocation(lat, lng) {
    try {
        window.localStorage.setItem('pyrosense:lastKnownCoords', JSON.stringify({
            lat,
            lng,
            timestamp: Date.now()
        }));
        renderLandingMapResources();
        renderMobilePreviewMarkers();
    } catch (error) {
        console.warn('Unable to cache visitor location:', error);
    }
}

function getLandingDistanceMeters(lat1, lng1, lat2, lng2) {
    const earthRadius = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return earthRadius * c;
}

function cacheLandingStationDirectory(directory) {
    try {
        const cacheable = (Array.isArray(directory) ? directory : [])
            .filter(station => station?.phone)
            .map(station => ({
                id: Number(station.id) || null,
                station_id: Number(station.station_id) || null,
                name: station.name || 'BFP station',
                phone: station.phone,
                lat: Number(station.lat),
                lng: Number(station.lng),
            }));
        window.localStorage.setItem(LANDING_STATION_DIRECTORY_CACHE_KEY, JSON.stringify(cacheable));
    } catch (error) {
        console.warn('Unable to cache the station contact directory:', error);
    }
}

function readCachedLandingStationDirectory() {
    try {
        const cached = JSON.parse(window.localStorage.getItem(LANDING_STATION_DIRECTORY_CACHE_KEY) || '[]');
        return Array.isArray(cached) ? cached : [];
    } catch {
        return [];
    }
}

function normalizeStationPhone(phone) {
    const normalized = String(phone || '').replace(/[^+\d]/g, '');
    return normalized || OFFLINE_BFP_DIAL_NUMBER;
}

function resolveLandingEmergencyStation(coords = null, preferredStationId = null) {
    const directory = (stations.length ? stations : readCachedLandingStationDirectory()).filter(station => station?.phone);
    if (!directory.length) {
        return { station_id: null, name: 'BFP station', phone: OFFLINE_BFP_DIAL_NUMBER };
    }

    if (preferredStationId) {
        const preferred = directory.find(station => Number(station.station_id) === Number(preferredStationId));
        if (preferred) return preferred;
    }

    const sourceCoords = coords || readCachedVisitorLocation();
    if (sourceCoords && Number.isFinite(Number(sourceCoords.lat)) && Number.isFinite(Number(sourceCoords.lng))) {
        const responder = readCachedLandingResponder(Number(sourceCoords.lat), Number(sourceCoords.lng));
        if (responder?.stationId) {
            const responderStation = directory.find(station => Number(station.station_id) === Number(responder.stationId));
            if (responderStation) return responderStation;
        }

        return [...directory]
            .filter(station => Number.isFinite(Number(station.lat)) && Number.isFinite(Number(station.lng)))
            .sort((a, b) => getLandingDistanceMeters(sourceCoords.lat, sourceCoords.lng, a.lat, a.lng)
                - getLandingDistanceMeters(sourceCoords.lat, sourceCoords.lng, b.lat, b.lng))[0] || directory[0];
    }

    return directory[0];
}

function readCachedLandingResponder(lat, lng) {
    try {
        const raw = window.localStorage.getItem(LANDING_RESPONDER_CACHE_KEY);
        const cached = raw ? JSON.parse(raw) : null;

        if (!cached?.responder || !cached?.lat || !cached?.lng || !cached?.timestamp) {
            return null;
        }

        const isFresh = Date.now() - Number(cached.timestamp) <= LANDING_RESPONDER_CACHE_MAX_AGE_MS;
        const isNearby = getLandingDistanceMeters(lat, lng, Number(cached.lat), Number(cached.lng))
            <= LANDING_RESPONDER_CACHE_MAX_DISTANCE_METERS;

        return isFresh && isNearby ? cached.responder : null;
    } catch (error) {
        console.warn('Unable to read cached landing responder:', error);
        return null;
    }
}

function writeCachedLandingResponder(lat, lng, responder) {
    try {
        window.localStorage.setItem(LANDING_RESPONDER_CACHE_KEY, JSON.stringify({
            lat,
            lng,
            responder: {
                stationId: Number(responder.station_id ?? responder.stationId) || null,
                stationName: responder.station_name ?? responder.stationName ?? '',
                teamId: Number(responder.team_id ?? responder.teamId) || null,
                availability: responder.availability || 'nearest_station_only',
                distanceMeters: Number(responder.distance_meters ?? responder.distanceMeters ?? 0) || null,
            },
            timestamp: Date.now(),
        }));
    } catch (error) {
        console.warn('Unable to cache landing responder:', error);
    }
}

async function prefetchLandingResponder(lat, lng) {
    if (readCachedLandingResponder(lat, lng)) {
        return;
    }

    const params = new URLSearchParams({
        lat: String(lat),
        lng: String(lng),
        incident_type_id: '1',
    });

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 2500);

    try {
        const response = await fetch(`/api/incidents/nearest-available-responder?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`Responder prefetch failed with status ${response.status}.`);
        }

        const payload = await response.json();
        if (payload?.data?.station_id) {
            writeCachedLandingResponder(lat, lng, payload.data);
        }
    } finally {
        window.clearTimeout(timeoutId);
    }
}

function isLandingAbortError(error) {
    return error?.name === 'AbortError';
}

function scheduleLandingResponderPrefetch(lat, lng) {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    const run = () => {
        prefetchLandingResponder(lat, lng).catch((error) => {
            if (isLandingAbortError(error)) {
                return;
            }

            console.warn('Landing responder prefetch failed:', error);
        });
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(run, { timeout: 1800 });
        return;
    }

    window.setTimeout(run, 250);
}

function readCachedVisitorLocation() {
    try {
        const raw = window.localStorage.getItem('pyrosense:lastKnownCoords');
        return raw ? JSON.parse(raw) : null;
    } catch (error) {
        console.warn('Unable to read cached visitor location:', error);
        return null;
    }
}

function getAnonymousUserId() {
    const rawValue = document.querySelector('meta[name="anonymous-user-id"]')?.getAttribute('content') || '';
    const parsed = Number(rawValue);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function setEmergencyCallButtonsLoading(isLoading) {
    const buttons = document.querySelectorAll('[data-emergency-call]');

    buttons.forEach((button) => {
        if (!button.dataset.defaultLabel) {
            button.dataset.defaultLabel = button.innerHTML;
        }

        button.classList.toggle('is-loading', isLoading);
        if (button.tagName === 'BUTTON') {
            button.disabled = isLoading;
        } else {
            button.setAttribute('aria-disabled', isLoading ? 'true' : 'false');
        }

        button.innerHTML = isLoading
            ? '<i class="fa-solid fa-spinner fa-spin"></i> Preparing call...'
            : button.dataset.defaultLabel;
    });
}

function setAnonymousReportButtonsLoading(isLoading) {
    const buttons = document.querySelectorAll('[data-anonymous-report-trigger]');

    buttons.forEach((button) => {
        if (!button.dataset.defaultLabel) {
            button.dataset.defaultLabel = button.innerHTML;
        }

        button.disabled = isLoading;
        button.classList.toggle('is-loading', isLoading);
        button.innerHTML = isLoading
            ? '<i class="fa-solid fa-spinner fa-spin"></i> Reporting...'
            : button.dataset.defaultLabel;
    });
}

function showLandingToast(message, isError = false, timeoutMs = 3600) {
    window.PyroNotify?.notify(message, isError ? 'error' : 'success', { duration: timeoutMs });
}

function formatEmergencyCoordinates(coords) {
    if (!coords || !Number.isFinite(Number(coords.lat)) || !Number.isFinite(Number(coords.lng))) {
        return '';
    }

    return `${Number(coords.lat).toFixed(6)}, ${Number(coords.lng).toFixed(6)}`;
}

function getEmergencyGpsLocation() {
    return getReliableLocationFix({ timeoutMs: 12000, acceptBestAvailable: true }).then((fix) => {
        persistVisitorLocation(fix.lat, fix.lng);
        return fix;
    });
}

function getNearestLandingMarkerByCategory(coords, category) {
    if (!coords || !Array.isArray(landingResourceMarkers) || !landingResourceMarkers.length) {
        return null;
    }

    let nearest = null;
    let nearestDistance = Number.POSITIVE_INFINITY;

    landingResourceMarkers.forEach((marker) => {
        if (normalizeLandingCategory(marker) !== category) {
            return;
        }

        const lat = Number(marker.lat);
        const lng = Number(marker.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const distance = getLandingDistanceMeters(coords.lat, coords.lng, lat, lng);
        if (distance < nearestDistance) {
            nearest = marker;
            nearestDistance = distance;
        }
    });

    return nearest;
}

function getCachedEmergencyLocationLabel(coords = null) {
    const sourceCoords = coords || readCachedVisitorLocation();
    if (!sourceCoords || !Number.isFinite(Number(sourceCoords.lat)) || !Number.isFinite(Number(sourceCoords.lng))) {
        return '';
    }

    const lookupCoords = {
        lat: Number(sourceCoords.lat),
        lng: Number(sourceCoords.lng),
    };
    const barangayMarker = getNearestLandingMarkerByCategory(lookupCoords, 'barangay');
    if (barangayMarker) {
        const title = landingResourceTitle(barangayMarker);
        const description = landingResourceDescription(barangayMarker);
        return [title, description].filter(Boolean).join(' - ');
    }

    const responder = readCachedLandingResponder(lookupCoords.lat, lookupCoords.lng);
    if (responder?.stationName) {
        return responder.stationName;
    }

    return '';
}

function buildOfflineEmergencyDedupeKey(coords, userId) {
    const normalizedLat = Number(coords.lat).toFixed(4);
    const normalizedLng = Number(coords.lng).toFixed(4);
    return `${OFFLINE_EMERGENCY_QUEUE_TYPE}:${userId || 'anonymous'}:${normalizedLat}:${normalizedLng}`;
}

function queueOfflineEmergencyIncident(coords, cachedLocation) {
    const userId = getAnonymousUserId();
    if (!userId || !window.pyroPwa?.enqueueOfflineReport || !window.pyroPwa?.getQueuedReports) {
        return {
            queueItem: null,
            wasAlreadyQueued: false,
        };
    }

    const dedupeKey = buildOfflineEmergencyDedupeKey(coords, userId);
    const existingQueueItem = window.pyroPwa.getQueuedReports().find((item) => (
        item.type === OFFLINE_EMERGENCY_QUEUE_TYPE
        && item.meta?.dedupe_key === dedupeKey
        && item.meta?.status === 'pending_sync'
    ));

    if (existingQueueItem) {
        return {
            queueItem: existingQueueItem,
            wasAlreadyQueued: true,
        };
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const createdAt = new Date().toISOString();
    const queueItem = window.pyroPwa.enqueueOfflineReport({
        type: OFFLINE_EMERGENCY_QUEUE_TYPE,
        url: '/api/incidents',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            lat: coords.lat,
            lng: coords.lng,
            title: OFFLINE_EMERGENCY_TITLE,
            description: cachedLocation
                ? `Offline emergency call fallback. Cached area: ${cachedLocation}.`
                : 'Offline emergency call fallback.',
            status: 'reported',
            reported_by: userId,
        }),
        created_at: createdAt,
        meta: {
            status: 'pending_sync',
            source: 'offline_emergency_call',
            user_id: userId,
            queued_at: createdAt,
            timestamp: createdAt,
            dedupe_key: dedupeKey,
            coords: {
                lat: coords.lat,
                lng: coords.lng,
                accuracy: coords.accuracy,
            },
            cached_location: cachedLocation || null,
        },
    });

    return {
        queueItem,
        wasAlreadyQueued: false,
    };
}

function getFreshVisitorLocation() {
    return getReliableLocationFix({ acceptBestAvailable: true }).then((fix) => {
        persistVisitorLocation(fix.lat, fix.lng);
        return fix;
    });
}

async function createAnonymousIncidentMarker(lat, lng) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch('/api/markers', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            title: 'Anonymous Fire Emergency',
            description: 'Fire emergency reported anonymously.',
            lat,
            lng,
            status: 'active',
            category: 'fire',
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to save the anonymous report marker.');
    }

    const payload = await response.json();
    const markerId = payload?.data?.marker_id || payload?.marker_id || null;

    if (!markerId) {
        throw new Error('The system did not return a marker ID for the anonymous report.');
    }

    return Number(markerId);
}

async function createAnonymousIncident(markerId, anonymousUserId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch('/api/incidents', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            marker_id: markerId,
            reported_by: anonymousUserId,
            status: 'reported',
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to submit the anonymous fire report.');
    }

    return response.json();
}

async function submitAnonymousIncidentReport() {
    if (anonymousReportInFlight) {
        showLandingToast('Anonymous report is already sending...', false, 2200);
        return;
    }

    const anonymousUserId = getAnonymousUserId();
    if (!anonymousUserId) {
        showLandingToast('Anonymous reporting is not available yet.', true);
        return;
    }

    try {
        anonymousReportInFlight = true;
        setAnonymousReportButtonsLoading(true);
        showLandingToast('Getting your location...', false, 2200);

        const coords = await getFreshVisitorLocation();
        showLandingToast('Sending anonymous fire report...', false, 2600);
        const markerId = await createAnonymousIncidentMarker(coords.lat, coords.lng);
        await createAnonymousIncident(markerId, anonymousUserId);

        showLandingToast('Anonymous fire report sent.', false, 4200);
    } catch (error) {
        console.error('Anonymous report failed:', error);
        showLandingToast(error.message || 'Failed to send the anonymous fire report.', true, 4800);
    } finally {
        anonymousReportInFlight = false;
        setAnonymousReportButtonsLoading(false);
    }
}

window.addEventListener('pyrosense:report-synced', (event) => {
    const queueItem = event.detail?.queueItem;
    if (queueItem?.type !== OFFLINE_EMERGENCY_QUEUE_TYPE) {
        return;
    }

    showLandingToast('Your offline emergency report has synced successfully.', false, 3800);
});

// Search location
function searchLocation() {
    const input = document.getElementById('locationSearch');
    const query = input.value.trim();
    
    if (!query) return;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Pangasinan, Philippines')}`)
        .then(response => response.json())
        .then(results => {
            if (results.length > 0) {
                const location = results[0];
                const lat = parseFloat(location.lat);
                const lng = parseFloat(location.lon);
                
                map.setView([lat, lng], 13);
                findNearbyStations(lat, lng);
            }
        })
        .catch(error => console.error('Search error:', error));
}

// Get user location
async function getUserLocation() {
    try {
        const fix = await getReliableLocationFix();
        const latitude = fix.lat;
        const longitude = fix.lng;
                
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                
                userMarker = L.marker([latitude, longitude], {
                    icon: L.divIcon({
                        className: 'user-marker',
                        html: '<i class="fa-solid fa-circle-user" style="color: #3b82f6; font-size: 2rem;"></i>'
                    })
                }).addTo(map).bindPopup('Your Location');
                
                map.setView([latitude, longitude], 13);
                findNearbyStations(latitude, longitude);
    } catch (error) {
        console.error('Geolocation error:', error);
        showLandingToast(error.message || 'Unable to get your location.', true, 4200);
    }
}

// Find nearby stations
function findNearbyStations(lat, lng) {
    const stationsWithDistance = stations.map(station => {
        const distance = calculateDistance(
            lat, lng, station.lat, station.lng
        );
        return { ...station, distance };
    });
    
    stationsWithDistance.sort((a, b) => a.distance - b.distance);
    updateNearbyStations(stationsWithDistance.slice(0, 5));
}

// Update nearby stations list
function updateNearbyStations(nearbyStations) {
    const container = document.getElementById('nearbyStations');
    if (!container) return;

    if (!Array.isArray(nearbyStations)) {
        nearbyStations = [];
    }
    
    if (nearbyStations.length === 0) {
        container.innerHTML = '<div class="loading-spinner">No stations found</div>';
        return;
    }
    
    container.innerHTML = nearbyStations.map(station => `
        <div class="station-item" onclick="flyToStation(${station.lat}, ${station.lng})">
            <div class="station-icon">
                <i class="fa-solid fa-fire-extinguisher"></i>
            </div>
            <div class="station-info">
                <div class="station-name">${station.name}</div>
                <div class="station-distance">
                    <i class="fa-solid fa-location-dot"></i>
                    ${typeof station.distance === 'number' ? `${station.distance.toFixed(1)} km away` : 'Location available'}
                </div>
            </div>
            <i class="fa-solid fa-chevron-right" style="color: #9ca3af;"></i>
        </div>
    `).join('');
}

// Calculate distance between two points
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function toRad(degrees) {
    return degrees * (Math.PI/180);
}

// Fly to station on map
function flyToStation(lat, lng) {
    map.setView([lat, lng], 15);
}

// Modal functions
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = 'auto';
}

// Open station modal with data
window.openStationModal = function(stationId) {
    const station = stations.find(s => s.id == stationId);
    if (!station) return;
    
    // Create or update station modal
    let modal = document.getElementById('stationDetailModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'stationDetailModal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>${station.name}</h2>
                <button class="modal-close" onclick="closeModal('stationDetailModal')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 1rem;">
                    <i class="fa-solid fa-location-dot" style="color: #dc2626; width: 20px;"></i>
                    ${station.address}
                </div>
                <div style="margin-bottom: 1rem;">
                    <i class="fa-solid fa-phone" style="color: #dc2626; width: 20px;"></i>
                    ${station.phone}
                </div>
                <div style="margin-bottom: 1rem;">
                    <i class="fa-solid fa-users" style="color: #dc2626; width: 20px;"></i>
                    ${station.personnel} Personnel
                </div>
                <div style="margin-bottom: 1rem;">
                    <i class="fa-solid fa-truck" style="color: #dc2626; width: 20px;"></i>
                    ${station.equipment}
                </div>
                <div style="margin-bottom: 1rem;">
                    <i class="fa-solid fa-user-tie" style="color: #dc2626; width: 20px;"></i>
                    ${station.captain}
                </div>
                <button type="button" onclick="startLandingStationCall(this, event, ${Number(station.id) || 0})"
                        style="background: #dc2626; color: white; border: none; padding: 0.75rem; border-radius: 8px; width: 100%; margin-top: 1rem; cursor: pointer;">
                    <i class="fa-solid fa-phone-alt"></i> Call Station
                </button>
            </div>
        </div>
    `;
    
    openModal('stationDetailModal');
}

// Emergency call
window.emergencyCall = function() {
    document.querySelector('[data-emergency-call]')?.click?.();
}

window.startLandingStationCall = function(button, event, stationMarkerId) {
    event?.preventDefault?.();
    const station = stations.find(item => Number(item.id) === Number(stationMarkerId))
        || readCachedLandingStationDirectory().find(item => Number(item.id) === Number(stationMarkerId));
    const fallback = station || resolveLandingEmergencyStation();

    button.dataset.voiceStationId = fallback.station_id ? String(fallback.station_id) : '';
    button.dataset.fallbackPhone = normalizeStationPhone(fallback.phone);
    button.dataset.fallbackStationName = fallback.name || 'BFP station';

    if (window.navigator.onLine
        && window.PyroSenseVoiceCall?.configured
        && typeof window.pyroStartVoiceCall === 'function') {
        window.pyroStartVoiceCall(button, event);
        return;
    }

    window.location.href = `tel:${button.dataset.fallbackPhone}`;
}

// Show/hide loading
function showLoading(element) {
    const el = document.getElementById(element + 'Loading');
    if (el) el.style.display = 'block';
}

function hideLoading(element) {
    const el = document.getElementById(element + 'Loading');
    if (el) el.style.display = 'none';
}

// About Us modals data
const aboutContent = {
    aboutBfpUrbiztondo: {
        title: 'About BFP Urbiztondo',
        content: `
            <div class="about-doc">
                <div class="about-doc-hero">
                    <span class="about-doc-badge"><i class="fa-solid fa-fire-extinguisher"></i> Bureau of Fire Protection</span>
                    <h3>Urbiztondo Fire Station, Pangasinan</h3>
                    <p>
                        Based on the official BFP Region 1 station listings, Urbiztondo Fire Station forms part of the
                        Pangasinan provincial fire service network under the Bureau of Fire Protection, supporting
                        municipal fire prevention, suppression, and emergency response operations in Urbiztondo and nearby areas.
                    </p>
                </div>

                <div class="about-doc-grid">
                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-bullseye"></i> Mandate</h4>
                        <p>
                            The Bureau of Fire Protection was created by Republic Act No. 6975 and is tasked to prevent and suppress
                            destructive fires, enforce fire-related laws, investigate fire causes, and maintain fire stations with
                            adequate personnel and equipment in cities and municipalities subject to DILG standards.
                        </p>
                    </div>

                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-shield-heart"></i> Service Role</h4>
                        <p>
                            For a municipality like Urbiztondo, this means maintaining readiness for structural fires, rescue support,
                            community fire safety education, inspections, and coordination during emergencies that affect homes,
                            businesses, transport assets, and public spaces.
                        </p>
                    </div>

                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-eye"></i> Vision</h4>
                        <p>
                            The official BFP Citizens Charter presents the agency vision as
                            <strong>“A modern fire service fully capable of ensuring a fire safe nation by 2034.”</strong>
                        </p>
                    </div>

                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-handshake-angle"></i> Mission</h4>
                        <p>
                            BFP’s published mission centers on preventing and suppressing destructive fires, investigating their causes,
                            providing emergency medical and rescue services, and enforcing fire-related laws with active community involvement.
                        </p>
                    </div>
                </div>

                <div class="about-doc-section">
                    <h4><i class="fa-solid fa-phone-volume"></i> Public Contact Reference</h4>
                    <p>
                        The BFP Region 1 Contact Us page lists <strong>Urbiztondo FS</strong> with mobile contact
                        <strong>0917-189-1611</strong>. This gives residents and nearby users a direct station reference point
                        for inquiries and station coordination.
                    </p>
                </div>

                <div class="about-doc-section">
                    <h4><i class="fa-solid fa-people-group"></i> Why It Matters in PyroSense</h4>
                    <p>
                        In this system, the Urbiztondo station profile supports the same real-world responsibilities reflected in the official
                        BFP mandate: improving visibility of response assets, guiding citizens to the right emergency resources, and helping
                        local responders act faster through mapped stations, incidents, hydrants, and operational records.
                    </p>
                </div>

                <div class="about-doc-sources">
                    <h4><i class="fa-solid fa-link"></i> Sources</h4>
                    <ul>
                        <li><a href="https://region1.bfp.gov.ph/contact-us/" target="_blank" rel="noopener noreferrer">BFP Region 1 Contact Us</a></li>
                        <li><a href="https://region1.bfp.gov.ph/powers-and-functions-of-the-bfp/" target="_blank" rel="noopener noreferrer">BFP Region 1 Legal Mandates, Powers and Functions</a></li>
                        <li><a href="https://www.bfp.gov.ph/wp-content/uploads/2020/12/3-BFP-Citizens-Charter-Handbook-for-Printing.pdf" target="_blank" rel="noopener noreferrer">BFP Citizens Charter</a></li>
                        <li><a href="https://bfp.gov.ph/overview-2014/" target="_blank" rel="noopener noreferrer">BFP Overview</a></li>
                    </ul>
                    <p class="about-doc-note">
                        The station-specific summary above combines official BFP mandate and contact references with a clear operational description for Urbiztondo.
                    </p>
                </div>
            </div>
        `
    },
    history: {
        title: 'Our History',
        content: `
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>2018 - Founded</h4>
                    <p>PyroSense was established to modernize fire emergency response in Pangasinan.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>2020 - First 5 Stations</h4>
                    <p>Launched with 5 fire stations, reducing average response time by 30%.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>2022 - Expansion</h4>
                    <p>Expanded to 15 stations across the province.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>2024 - Innovation Award</h4>
                    <p>Received National Excellence in Emergency Response Award.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>2026 - Today</h4>
                    <p>Serving 25+ municipalities with 150+ personnel.</p>
                </div>
            </div>
        `
    },
    mission: {
        title: 'Mission & Vision',
        content: `
            <h3 style="color: #dc2626; margin-bottom: 0.5rem;">Our Mission</h3>
            <p style="margin-bottom: 1.5rem;">To protect lives and property through rapid response, innovative technology, and community education.</p>
            
            <h3 style="color: #dc2626; margin-bottom: 0.5rem;">Our Vision</h3>
            <p>To be the leading emergency response system in the Philippines, achieving zero preventable fire-related casualties through prevention and quick response.</p>
            
            <h3 style="color: #dc2626; margin: 1.5rem 0 0.5rem;">Core Values</h3>
            <ul style="list-style: none;">
                <li style="margin-bottom: 0.5rem;">✓ Speed - Every second counts</li>
                <li style="margin-bottom: 0.5rem;">✓ Safety - Protecting lives first</li>
                <li style="margin-bottom: 0.5rem;">✓ Service - Community first approach</li>
                <li style="margin-bottom: 0.5rem;">✓ Innovation - Always improving</li>
            </ul>
        `
    },
    leadership: {
        title: 'Leadership Team',
        content: `
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 1.5rem;">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <strong>Chief Juan Dela Cruz</strong><br>
                    <small style="color: #6b7280;">Fire Director, 25 years experience</small>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 1.5rem;">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <strong>Sr. Insp. Maria Santos</strong><br>
                    <small style="color: #6b7280;">Operations Chief, 18 years experience</small>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 1.5rem;">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div>
                    <strong>Engr. Robert Lim</strong><br>
                    <small style="color: #6b7280;">Technology Director</small>
                </div>
            </div>
        `
    },
    careers: {
        title: 'Join Our Team',
        content: `
            <p style="margin-bottom: 1.5rem;">Current openings at PyroSense:</p>
            
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <strong>Firefighter</strong><br>
                <small>Multiple positions · Pangasinan</small>
                <p style="margin-top: 0.5rem;">Requirements: Passed firefighter exam, physically fit</p>
            </div>
            
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <strong>Emergency Dispatcher</strong><br>
                <small>Full-time · Lingayen HQ</small>
                <p style="margin-top: 0.5rem;">Requirements: Good communication skills, computer literate</p>
            </div>
            
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <strong>Equipment Technician</strong><br>
                <small>Full-time · Various locations</small>
                <p style="margin-top: 0.5rem;">Requirements: Mechanical/technical background</p>
            </div>
            
            <button onclick="window.location.href='mailto:careers@pyrosense.gov'" 
                    style="background: #dc2626; color: white; border: none; padding: 0.75rem; border-radius: 8px; width: 100%; margin-top: 1rem; cursor: pointer;">
                <i class="fa-solid fa-envelope"></i> Send Application
            </button>
        `
    },
    faq: {
        title: 'Frequently Asked Questions',
        content: `
            <div style="margin-bottom: 1rem;">
                <strong style="color: #dc2626;">How do I report a fire?</strong>
                <p>Call 911 immediately. Our dispatchers will alert the nearest station while you're on the phone.</p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <strong style="color: #dc2626;">How can I find the nearest fire station?</strong>
                <p>Use our interactive map above or enable location services to find stations near you.</p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <strong style="color: #dc2626;">Is PyroSense free to use?</strong>
                <p>Yes, all emergency services and station location features are completely free for the public.</p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <strong style="color: #dc2626;">How can I request fire safety training?</strong>
                <p>Contact your local fire station or email training@pyrosense.gov to schedule a session.</p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <strong style="color: #dc2626;">What should I do while waiting for firefighters?</strong>
                <p>Evacuate the building immediately, stay low to avoid smoke, and wait at the designated meeting point.</p>
            </div>
        `
    },
    contact: {
        title: 'Contact BFP Urbiztondo',
        content: `
            <div class="about-doc">
                <div class="about-doc-hero">
                    <span class="about-doc-badge"><i class="fa-solid fa-phone-volume"></i> Public Contact</span>
                    <h3>Reach the Fire Station or Emergency Services</h3>
                    <p>
                        For emergencies, use the national emergency hotline first. For station-level coordination and local
                        fire safety concerns, the public may also refer to the listed BFP Region 1 contact details for Urbiztondo.
                    </p>
                </div>

                <div class="about-doc-grid">
                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-phone"></i> Emergency Hotline</h4>
                        <p><strong>911</strong></p>
                        <p>Use this for immediate emergency response and dispatch coordination.</p>
                    </div>

                    <div class="about-doc-card">
                        <h4><i class="fa-solid fa-mobile-screen-button"></i> Urbiztondo FS</h4>
                        <p><strong>0917-189-1611</strong></p>
                        <p>Listed on the BFP Region 1 contact directory for Urbiztondo Fire Station.</p>
                    </div>
                </div>

                <div class="about-doc-section">
                    <h4><i class="fa-solid fa-circle-info"></i> Best Use of This Contact</h4>
                    <p>
                        Use station contact channels for follow-up coordination, local inquiries, community fire safety concerns,
                        and non-emergency station communication. For active emergencies, always prioritize 911 or the fastest
                        official emergency dispatch channel available in your area.
                    </p>
                </div>
            </div>
        `
    }
};

// Open about us modal
window.openAboutModal = function(type) {
    const content = aboutContent[type];
    if (!content) return;
    
    let modal = document.getElementById('aboutModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'aboutModal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>${content.title}</h2>
                <button class="modal-close" onclick="closeModal('aboutModal')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${content.content}
            </div>
        </div>
    `;
    
    openModal('aboutModal');
}

function initScrollAnimations() {
    const revealItems = document.querySelectorAll('.reveal-on-scroll');

    revealItems.forEach((item) => {
        const delay = item.dataset.revealDelay;
        if (delay) {
            item.style.setProperty('--reveal-delay', `${delay}ms`);
        }
        item.classList.add('is-visible');
    });

    const heroParallaxTarget = document.querySelector('.hero-parallax-target');
    if (!heroParallaxTarget || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let ticking = false;
    const applyParallax = () => {
        const scrollY = window.scrollY || window.pageYOffset || 0;
        const offset = Math.min(scrollY * 0.08, 26);
        heroParallaxTarget.style.transform = `translate3d(0, ${offset}px, 0)`;
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(applyParallax);
            ticking = true;
        }
    }, { passive: true });
}
