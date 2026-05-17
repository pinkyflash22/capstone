// resources/js/landing/main.js

// Global variables
let map;
let stations = [];
let hydrants = [];
let stationMarkers = [];
let hydrantMarkers = [];
let userMarker = null;
let currentStationLayer = null;
let currentHydrantLayer = null;

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadStations();
    loadHydrants();
    initEventListeners();
    initScrollAnimations();
});

// Initialize map
function initMap() {
    // Default center (Pangasinan)
    map = L.map('map').setView([16.0433, 120.3333], 11);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap, &copy; CartoDB'
    }).addTo(map);
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
                addStationMarkers();
                updateNearbyStations(stations.slice(0, 5));
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

    // Map toggle buttons
    const showStationsBtn = document.getElementById('showStations');
    const showHydrantsBtn = document.getElementById('showHydrants');
    
    if (showStationsBtn) {
        showStationsBtn.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            this.classList.toggle('active');
            toggleStations(!isActive);
        });
    }
    
    if (showHydrantsBtn) {
        showHydrantsBtn.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            this.classList.toggle('active');
            toggleHydrants(!isActive);
        });
    }

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
function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                
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
            },
            (error) => {
                console.error('Geolocation error:', error);
                alert('Unable to get your location. Please enable location services.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
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
                <button onclick="window.location.href='tel:${station.phone}'" 
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
    if (confirm('Call 911 for emergency services?')) {
        window.location.href = 'tel:911';
    }
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
    });

    if (!('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            } else {
                entry.target.classList.remove('is-visible');
            }
        });
    }, {
        threshold: 0.16,
        rootMargin: '0px 0px -40px 0px'
    });

    revealItems.forEach((item) => observer.observe(item));

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
