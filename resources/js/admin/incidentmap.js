// resources/js/admin/incidentmap.js

class IncidentMap {
    constructor() {
        this.map = null;
        this.stations = [];
        this.hydrants = [];
        this.stationMarkers = [];
        this.hydrantMarkers = [];
        this.userMarker = null;
        this.drawControl = null;
        this.init();
    }

    init() {
        this.initMap();
        this.initEventListeners();
        this.loadData();
    }

    initMap() {
        // Default center (Pangasinan)
        const defaultCenter = [16.0433, 120.3333];
        
        this.map = L.map('incidentMap').setView(defaultCenter, 11);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap, &copy; CartoDB'
        }).addTo(this.map);

        // Initialize draw control
        this.initDrawControl();
    }

    initDrawControl() {
        this.drawControl = new L.Control.Draw({
            draw: {
                polyline: false,
                polygon: false,
                circle: false,
                rectangle: false,
                marker: {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<i class="fa-solid fa-location-dot" style="color: #dc2626; font-size: 24px;"></i>'
                    })
                }
            },
            edit: false
        });
        
        this.map.addControl(this.drawControl);

        this.map.on(L.Draw.Event.CREATED, (e) => {
            const layer = e.layer;
            const latlng = layer.getLatLng();
            this.openAddMarkerModal(latlng);
        });
    }

    initEventListeners() {
        // Layer toggles
        document.getElementById('toggleStations')?.addEventListener('click', () => {
            this.toggleLayer('stations');
        });

        document.getElementById('toggleHydrants')?.addEventListener('click', () => {
            this.toggleLayer('hydrants');
        });

        // Location button
        document.getElementById('myLocation')?.addEventListener('click', () => {
            this.goToMyLocation();
        });

        // Search
        document.getElementById('searchLocation')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchLocation(e.target.value);
            }
        });

        document.getElementById('searchBtn')?.addEventListener('click', () => {
            const input = document.getElementById('searchLocation');
            this.searchLocation(input.value);
        });

        // Modal close on outside click
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    loadData() {
        this.showLoading(true);
        
        Promise.all([
            this.loadStations(),
            this.loadHydrants()
        ]).finally(() => {
            this.showLoading(false);
        });
    }

    async loadStations() {
        try {
            const response = await fetch('/api/stations');
            const data = await response.json();
            
            if (data.success) {
                this.stations = data.data;
                this.addStationMarkers();
                this.updateStationsList();
            }
        } catch (error) {
            console.error('Error loading stations:', error);
            this.useSampleStations();
        }
    }

    async loadHydrants() {
        try {
            const response = await fetch('/api/hydrants');
            const data = await response.json();
            
            if (data.success) {
                this.hydrants = data.data;
                this.updateHydrantsList();
            }
        } catch (error) {
            console.error('Error loading hydrants:', error);
            this.useSampleHydrants();
        }
    }

    useSampleStations() {
        this.stations = [
            {
                id: 1,
                name: 'Urbiztondo Fire Station',
                address: 'Urbiztondo, Pangasinan',
                lat: 15.8233,
                lng: 120.3294,
                phone: '(075) 123-4567'
            },
            {
                id: 2,
                name: 'Mangatarem Fire Station',
                address: 'Mangatarem, Pangasinan',
                lat: 15.7875,
                lng: 120.2922,
                phone: '(075) 234-5678'
            },
            {
                id: 3,
                name: 'San Carlos Fire Station',
                address: 'San Carlos City, Pangasinan',
                lat: 15.9281,
                lng: 120.3489,
                phone: '(075) 345-6789'
            },
            {
                id: 4,
                name: 'Lingayen Fire Station HQ',
                address: 'Lingayen, Pangasinan',
                lat: 16.0215,
                lng: 120.2319,
                phone: '(075) 456-7890'
            },
            {
                id: 5,
                name: 'Dagupan Fire Station',
                address: 'Dagupan City, Pangasinan',
                lat: 16.0433,
                lng: 120.3333,
                phone: '(075) 567-8901'
            }
        ];
        
        this.addStationMarkers();
        this.updateStationsList();
    }

    useSampleHydrants() {
        this.hydrants = [
            {
                id: 1,
                name: 'Hydrant A-123',
                address: 'Urbiztondo Proper',
                lat: 15.8245,
                lng: 120.3301,
                status: 'operational'
            },
            {
                id: 2,
                name: 'Hydrant B-456',
                address: 'Mangatarem Public Market',
                lat: 15.7881,
                lng: 120.2930,
                status: 'operational'
            },
            {
                id: 3,
                name: 'Hydrant C-789',
                address: 'San Carlos Plaza',
                lat: 15.9290,
                lng: 120.3495,
                status: 'maintenance'
            },
            {
                id: 4,
                name: 'Hydrant D-012',
                address: 'Lingayen Capitol',
                lat: 16.0220,
                lng: 120.2325,
                status: 'operational'
            },
            {
                id: 5,
                name: 'Hydrant E-345',
                address: 'Dagupan City Hall',
                lat: 16.0440,
                lng: 120.3340,
                status: 'operational'
            }
        ];
        
        this.updateHydrantsList();
    }

    addStationMarkers() {
        this.stationMarkers.forEach(marker => this.map.removeLayer(marker));
        this.stationMarkers = [];

        this.stations.forEach(station => {
            const marker = L.marker([station.lat, station.lng], {
                icon: L.divIcon({
                    className: 'station-marker',
                    html: '<i class="fa-solid fa-fire-station" style="color: #dc2626; font-size: 24px;"></i>'
                })
            }).addTo(this.map);

            marker.bindPopup(`
                <div style="padding: 8px;">
                    <strong style="color: #dc2626;">${station.name}</strong><br>
                    <span style="font-size: 12px;">${station.address}</span><br>
                    <span style="font-size: 12px;"><i class="fa-solid fa-phone"></i> ${station.phone}</span>
                </div>
            `);

            this.stationMarkers.push(marker);
        });
    }

    toggleLayer(layer) {
        const btn = document.getElementById(`toggle${layer.charAt(0).toUpperCase() + layer.slice(1)}`);
        btn.classList.toggle('active');
        
        if (layer === 'stations') {
            if (btn.classList.contains('active')) {
                this.addStationMarkers();
            } else {
                this.stationMarkers.forEach(marker => this.map.removeLayer(marker));
            }
        } else if (layer === 'hydrants') {
            if (btn.classList.contains('active')) {
                this.addHydrantMarkers();
            } else {
                this.hydrantMarkers.forEach(marker => this.map.removeLayer(marker));
            }
        }
    }

    addHydrantMarkers() {
        this.hydrantMarkers.forEach(marker => this.map.removeLayer(marker));
        this.hydrantMarkers = [];

        this.hydrants.forEach(hydrant => {
            const color = hydrant.status === 'operational' ? '#10b981' : '#f59e0b';
            
            const marker = L.marker([hydrant.lat, hydrant.lng], {
                icon: L.divIcon({
                    className: 'hydrant-marker',
                    html: `<i class="fa-solid fa-fire-hydrant" style="color: ${color}; font-size: 20px;"></i>`
                })
            }).addTo(this.map);

            marker.bindPopup(`
                <div style="padding: 8px;">
                    <strong>${hydrant.name}</strong><br>
                    <span style="font-size: 12px;">${hydrant.address}</span><br>
                    <span class="status-${hydrant.status}">${hydrant.status}</span>
                </div>
            `);

            this.hydrantMarkers.push(marker);
        });
    }

    updateStationsList() {
        const container = document.getElementById('stationsList');
        if (!container) return;

        container.innerHTML = this.stations.map(station => `
            <div class="station-list-item" onclick="incidentMap.flyToStation(${station.lat}, ${station.lng})">
                <div class="station-list-icon">
                    <i class="fa-solid fa-fire-station"></i>
                </div>
                <div class="station-list-info">
                    <div class="station-list-name">${station.name}</div>
                    <div class="station-list-address">${station.address}</div>
                    <div class="station-list-distance">
                        <i class="fa-solid fa-location-dot"></i>
                        Click to view on map
                    </div>
                </div>
            </div>
        `).join('');
    }

    updateHydrantsList() {
        const container = document.getElementById('hydrantsList');
        if (!container) return;

        container.innerHTML = this.hydrants.map(hydrant => `
            <div class="hydrant-list-item" onclick="incidentMap.flyToHydrant(${hydrant.lat}, ${hydrant.lng})">
                <div class="hydrant-list-icon">
                    <i class="fa-solid fa-fire-hydrant"></i>
                </div>
                <div class="hydrant-list-info">
                    <div class="hydrant-list-name">${hydrant.name}</div>
                    <div class="hydrant-list-address">${hydrant.address}</div>
                    <span class="hydrant-status status-${hydrant.status}">
                        ${hydrant.status === 'operational' ? '✅ Operational' : '⚠️ Maintenance'}
                    </span>
                </div>
            </div>
        `).join('');
    }

    flyToStation(lat, lng) {
        this.map.setView([lat, lng], 16);
    }

    flyToHydrant(lat, lng) {
        this.map.setView([lat, lng], 18);
    }

    goToMyLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    
                    if (this.userMarker) {
                        this.map.removeLayer(this.userMarker);
                    }
                    
                    this.userMarker = L.marker([latitude, longitude], {
                        icon: L.divIcon({
                            className: 'user-marker',
                            html: '<i class="fa-solid fa-circle-user" style="color: #3b82f6; font-size: 24px;"></i>'
                        })
                    }).addTo(this.map).bindPopup('Your Location');
                    
                    this.map.setView([latitude, longitude], 14);
                },
                (error) => {
                    alert('Unable to get your location. Please enable location services.');
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }

    async searchLocation(query) {
        if (!query.trim()) return;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Pangasinan, Philippines')}`
            );
            const results = await response.json();
            
            if (results.length > 0) {
                const location = results[0];
                this.map.setView([location.lat, location.lon], 14);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    openAddMarkerModal(latlng) {
        // Store latlng for saving
        this.currentLatLng = latlng;
        
        const modal = document.getElementById('markerModal');
        if (modal) {
            modal.classList.add('active');
        }
    }

    saveMarker() {
        const title = document.getElementById('markerTitle')?.value;
        const description = document.getElementById('markerDescription')?.value;
        const icon = document.getElementById('markerIcon')?.value;

        if (title && this.currentLatLng) {
            // Save marker logic here
            console.log('Saving marker:', { title, description, icon, latlng: this.currentLatLng });
            
            // Add marker to map
            L.marker([this.currentLatLng.lat, this.currentLatLng.lng], {
                icon: L.divIcon({
                    html: '<i class="fa-solid fa-location-dot" style="color: #dc2626; font-size: 24px;"></i>'
                })
            }).addTo(this.map).bindPopup(title);

            this.closeModal('markerModal');
        }
    }

    showLoading(show) {
        const loader = document.getElementById('loadingSpinner');
        if (loader) {
            loader.style.display = show ? 'block' : 'none';
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }

    emergencyCall() {
        if (confirm('Call 911 for emergency services?')) {
            window.location.href = 'tel:911';
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.incidentMap = new IncidentMap();
});

// Global functions
function openModal(modalId) {
    if (window.incidentMap) {
        window.incidentMap.openModal(modalId);
    }
}

function closeModal(modalId) {
    if (window.incidentMap) {
        window.incidentMap.closeModal(modalId);
    }
}

function emergencyCall() {
    if (window.incidentMap) {
        window.incidentMap.emergencyCall();
    }
}

function saveMarker() {
    if (window.incidentMap) {
        window.incidentMap.saveMarker();
    }
}