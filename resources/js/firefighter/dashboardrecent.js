// resources/js/firefighter/dashboard.js

class FirefighterDashboard {
    constructor() {
        this.map = null;
        this.userLocation = null;
        this.activeRoute = null;
        this.routeLayer = null;
        this.currentIncident = null;
        this.watchId = null;
        this.updateInterval = null;
        this.incidentInterval = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.userId = window.userId || null;
        this.teamId = window.teamId || null;
        this.travelMode = 'driving';
        
        // Initialize marker layers
        this.stationMarkers = null;
        this.incidentMarkers = null;
        this.assignedIncidentMarkers = null;
        this.userMarker = null;
        
        // Store data
        this.stations = [];
        this.incidents = [];
        this.assignedIncident = null;
        
        this.init();
        
        // Add cleanup on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }

    init() {
        this.initMap();
        this.getUserLocation();
        this.fetchStations();
        this.fetchIncidents();
        this.setupEventListeners();
    
            if (!navigator.geolocation) {
                this.showToast('Geolocation is not supported by your browser', 'error');
                const locationDisplay = document.getElementById('userLocation');
                if (locationDisplay) {
                    locationDisplay.textContent = '📍 Location: Not supported';
                }
                return false;
            }
            return true;

    }

    initMap() {
        this.map = L.map('incidentMap').setView([16.0433, 120.3333], 13);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(this.map);
        
        // Initialize layer groups for different marker types
        this.stationMarkers = L.layerGroup().addTo(this.map);
        this.incidentMarkers = L.layerGroup().addTo(this.map);
        this.assignedIncidentMarkers = L.layerGroup().addTo(this.map);
        
        // Add user marker
        this.userMarker = L.marker([16.0433, 120.3333], {
            icon: L.divIcon({
                className: 'user-marker',
                html: '<div style="background: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #3b82f6;"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            })
        }).addTo(this.map);
        
        // Add legend
        this.addLegend();
        
        // Start fetching incidents every 10 seconds
        this.startIncidentPolling();
    }

    startIncidentPolling() {
        // Initial fetch
        this.fetchIncidents();
        
        // Fetch every 10 seconds
        this.incidentInterval = setInterval(() => {
            this.fetchIncidents();
        }, 10000);
    }

    async fetchIncidents() {
        try {
            // Fetch ALL markers first
            const response = await fetch('/api/markers', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch markers');
            
            const data = await response.json();
            const allMarkers = data.data || data;
            
            // Filter only incident markers (markerCategory_id = 3)
            const incidentMarkers = allMarkers.filter(marker => marker.markerCategory_id === 3);
            
            // Convert markers to incident format
            this.incidents = incidentMarkers.map(marker => ({
                incident_id: marker.marker_id,
                marker: marker,
                title: marker.title,
                description: marker.description,
                created_at: marker.created_at,
                priority: this.getPriorityFromCategory(marker.markerCategory_id)
            }));
            
            // Also fetch assigned incident for this user (if any)
            await this.fetchAssignedIncident();
            
            // Display all incidents
            this.displayAllIncidents();
            
            // Update stats
            this.updateIncidentStats(this.incidents);
            
        } catch (error) {
            console.error('Error fetching incidents:', error);
        }
    }
    
    getPriorityFromCategory(categoryId) {
        // You can set priority based on category or other logic
        const priorityMap = {
            1: 'low',      // Station
            2: 'medium',   // Regular marker
            3: 'high',     // Incident
            4: 'critical'  // Hydrant
        };
        return priorityMap[categoryId] || 'medium';
    }

    async fetchAssignedIncident() {
        try {
            const response = await fetch(`/api/incidents/assigned/${this.userId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            this.assignedIncident = data.data || data;
            
            if (this.assignedIncident) {
                this.currentIncident = this.assignedIncident;
            }
            
        } catch (error) {
            console.error('Error fetching assigned incident:', error);
            this.assignedIncident = null;
        }
    }

    displayAllIncidents() {
        // Clear existing incident markers
        if (this.incidentMarkers) this.incidentMarkers.clearLayers();
        if (this.assignedIncidentMarkers) this.assignedIncidentMarkers.clearLayers();
        
        if (!this.incidents || this.incidents.length === 0) {
            console.log('No incidents to display');
            return;
        }
        
        this.incidents.forEach(incident => {
            // Get the marker data
            const markerData = incident.marker || incident;
            
            if (markerData.lat && markerData.lng) {
                // Check if this is the assigned incident
                const isAssigned = this.assignedIncident && 
                    (incident.incident_id === this.assignedIncident.incident_id ||
                     markerData.marker_id === (this.assignedIncident.marker_id || this.assignedIncident.marker?.marker_id));
                
                if (isAssigned && this.assignedIncidentMarkers) {
                    const assignedMarker = this.createIncidentMarker(incident, true);
                    if (assignedMarker) {
                        this.assignedIncidentMarkers.addLayer(assignedMarker);
                    }
                } else if (this.incidentMarkers) {
                    const markerObj = this.createIncidentMarker(incident, false);
                    if (markerObj) {
                        this.incidentMarkers.addLayer(markerObj);
                    }
                }
            }
        });
    }

    createIncidentMarker(incident, isAssigned = false) {
        const markerData = incident.marker || incident;
        const lat = markerData.lat;
        const lng = markerData.lng;
        
        if (!lat || !lng) return null;
        
        const color = isAssigned ? '#f97316' : '#ef4444';
        const icon = isAssigned ? 'fa-fire-extinguisher' : 'fa-fire';
        const size = isAssigned ? 32 : 28;
        const title = markerData.title || incident.title || 'Incident';
        const description = markerData.description || incident.description || 'No description';
        
        const marker = L.marker([parseFloat(lat), parseFloat(lng)], {
            icon: L.divIcon({
                className: `incident-marker ${isAssigned ? 'assigned' : ''}`,
                html: `<div style="background: ${color}; width: ${size}px; height: ${size}px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); ${isAssigned ? 'animation: pulse 1.5s infinite;' : ''}">
                        <i class="fa-solid ${icon}" style="color: white; font-size: ${size * 0.5}px;"></i>
                       </div>`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2]
            })
        });
        
        const priority = incident.priority || 'medium';
        
        marker.bindPopup(`
            <div style="min-width: 250px;">
                <div class="flex justify-between items-start">
                    <strong><i class="fa-solid ${icon}"></i> ${this.escapeHtml(title)}</strong>
                    <span class="priority-badge priority-${priority.toLowerCase()}">${priority.toUpperCase()}</span>
                </div>
                <p class="text-sm mt-1">${this.escapeHtml(description)}</p>
                <div class="mt-2 text-xs text-gray-500">
                    <i class="fa-regular fa-clock"></i> Reported: ${new Date(incident.created_at).toLocaleString()}<br>
                    <i class="fa-solid fa-location-dot"></i> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}
                </div>
                ${isAssigned ? `
                    <div class="mt-2 p-2 bg-orange-50 rounded-lg">
                        <strong class="text-orange-600">✓ Your Assigned Incident</strong>
                        <button class="btn btn-primary btn-sm mt-2 w-full" onclick="window.dashboard.navigateToIncident(${incident.incident_id})">
                            <i class="fa-solid fa-route"></i> Navigate
                        </button>
                    </div>
                ` : `
                    <button class="btn btn-primary btn-sm mt-2 w-full" onclick="window.dashboard.acceptIncident(${incident.incident_id})">
                        <i class="fa-solid fa-check"></i> Accept Incident
                    </button>
                `}
            </div>
        `);
        
        return marker;
    }

    async navigateToIncident(incidentId) {
        const incident = this.incidents.find(i => i.incident_id == incidentId) || this.assignedIncident;
        if (!incident) {
            this.showToast('Incident not found', 'error');
            return;
        }
        
        this.currentIncident = incident;
        await this.calculateRoute(incident);
        
        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard) routeCard.style.display = 'block';
    }

    updateIncidentStats(incidents) {
        const activeIncidents = incidents ? incidents.length : 0;
        const newToday = incidents ? incidents.filter(i => {
            const incidentDate = new Date(i.created_at);
            const today = new Date();
            return incidentDate.toDateString() === today.toDateString();
        }).length : 0;
        
        const statValue = document.querySelector('.stat-card:first-child .stat-value');
        const statTrend = document.querySelector('.stat-card:first-child .stat-trend');
        
        if (statValue) statValue.textContent = activeIncidents;
        if (statTrend) statTrend.innerHTML = `<i class="fa-solid fa-arrow-up"></i> ${newToday} new today`;
    }

    addLegend() {
        const legend = L.control({ position: 'bottomright' });
        
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'info legend');
            div.style.background = 'white';
            div.style.padding = '10px';
            div.style.borderRadius = '8px';
            div.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
            div.style.fontSize = '12px';
            div.innerHTML = `
                <div><strong>Legend</strong></div>
                <div><i class="fa-solid fa-circle" style="color: #3b82f6;"></i> Your Location</div>
                <div><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Active Incident</div>
                <div><i class="fa-solid fa-fire-extinguisher" style="color: #f97316;"></i> Your Assigned Incident</div>
                <div><i class="fa-solid fa-building" style="color: #22c55e;"></i> Fire Station</div>
                <div><i class="fa-solid fa-route" style="color: #3b82f6;"></i> Route</div>
            `;
            return div;
        };
        
        legend.addTo(this.map);
    }

    async fetchStations() {
        try {
            const response = await fetch('/api/stations', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch stations');
            
            const data = await response.json();
            this.stations = data.data || data;
            this.displayStations(this.stations);
            
        } catch (error) {
            console.error('Error fetching stations:', error);
        }
    }

    displayStations(stations) {
        if (this.stationMarkers) this.stationMarkers.clearLayers();
        
        if (!stations || stations.length === 0) return;
        
        stations.forEach(station => {
            const lat = station.lat || station.marker?.lat;
            const lng = station.lng || station.marker?.lng;
            
            if (lat && lng) {
                const marker = this.createStationMarker(station);
                if (marker && this.stationMarkers) {
                    this.stationMarkers.addLayer(marker);
                }
            }
        });
    }

    createStationMarker(station) {
        const lat = station.lat || station.marker?.lat;
        const lng = station.lng || station.marker?.lng;
        
        if (!lat || !lng) return null;
        
        return L.marker([parseFloat(lat), parseFloat(lng)], {
            icon: L.divIcon({
                className: 'station-marker',
                html: `<div style="background: #22c55e; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <i class="fa-solid fa-building" style="color: white; font-size: 12px;"></i>
                       </div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            })
        }).bindPopup(`
            <div style="min-width: 200px;">
                <strong><i class="fa-solid fa-building"></i> ${this.escapeHtml(station.station_name || 'Fire Station')}</strong>
                <p class="text-sm mt-1">${this.escapeHtml(station.address || station.marker?.description || 'No address available')}</p>
                <div class="mt-2 text-xs text-gray-500">
                    <i class="fa-solid fa-phone"></i> ${station.contact_number || 'N/A'}<br>
                    <i class="fa-solid fa-users"></i> ${station.personnel_count || 'N/A'} personnel
                </div>
                <button class="btn btn-sm btn-outline mt-2" onclick="window.dashboard.calculateRouteToStation(${station.station_id})">
                    <i class="fa-solid fa-route"></i> Get Directions
                </button>
            </div>
        `);
    }

    async calculateRouteToStation(stationId) {
        const station = this.stations.find(s => s.station_id == stationId);
        if (!station || !this.userLocation) return;
        
        const destination = {
            marker: {
                lat: station.lat || station.marker?.lat,
                lng: station.lng || station.marker?.lng
            },
            title: station.station_name
        };
        
        await this.calculateRouteToDestination(destination);
        this.showToast(`Route to ${station.station_name} calculated`, 'success');
    }

    async calculateRouteToDestination(destination) {
        if (!this.userLocation || !destination.marker) return;
        
        const origin = `${this.userLocation.lat},${this.userLocation.lng}`;
        const dest = `${destination.marker.lat},${destination.marker.lng}`;
        
        try {
            const response = await fetch(`https://router.project-osrm.org/route/v1/${this.travelMode}/${origin};${dest}?overview=full&geometries=geojson`);
            
            if (!response.ok) throw new Error('Route calculation failed');
            
            const data = await response.json();
            
            if (data.routes && data.routes.length > 0) {
                this.activeRoute = data.routes[0];
                this.displayRoute(data.routes[0]);
                this.updateRouteInfo(data.routes[0]);
                
                const routeCard = document.getElementById('routeInfoCard');
                if (routeCard) routeCard.style.display = 'block';
                
                if (this.updateInterval) clearInterval(this.updateInterval);
                this.updateInterval = setInterval(() => this.updateRouteETA(destination), 10000);
            }
            
        } catch (error) {
            console.error('Route calculation error:', error);
            this.showToast('Failed to calculate route', 'error');
        }
    }

    async calculateRoute(incident) {
        if (!this.userLocation || !incident.marker) return;
        
        const destination = {
            marker: incident.marker,
            title: incident.marker?.title || 'Incident'
        };
        
        await this.calculateRouteToDestination(destination);
    }

    displayRoute(route) {
        if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
        }
        
        this.routeLayer = L.geoJSON(route.geometry, {
            style: {
                color: '#3b82f6',
                weight: 4,
                opacity: 0.8,
                lineDash: [5, 5]
            }
        }).addTo(this.map);
        
        const bounds = this.routeLayer.getBounds();
        this.map.fitBounds(bounds, { padding: [50, 50] });
    }

    updateRouteInfo(route) {
        const distance = route.distance / 1000;
        const duration = route.duration / 60;
        
        const hours = Math.floor(duration / 60);
        const minutes = Math.floor(duration % 60);
        
        let durationText = hours > 0 ? `${hours} hr ${minutes} min` : `${minutes} min`;
        
        const distanceEl = document.getElementById('routeDistance');
        const durationEl = document.getElementById('routeDuration');
        const etaEl = document.getElementById('routeETA');
        
        if (distanceEl) distanceEl.textContent = `${distance.toFixed(1)} km`;
        if (durationEl) durationEl.textContent = durationText;
        
        if (etaEl) {
            const eta = new Date(Date.now() + (route.duration * 1000));
            etaEl.textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }

    async updateRouteETA(destination) {
        if (!this.userLocation || !destination.marker) return;
        
        const origin = `${this.userLocation.lat},${this.userLocation.lng}`;
        const dest = `${destination.marker.lat},${destination.marker.lng}`;
        
        try {
            const response = await fetch(`https://router.project-osrm.org/route/v1/${this.travelMode}/${origin};${dest}?overview=false`);
            const data = await response.json();
            
            if (data.routes && data.routes.length > 0) {
                this.updateRouteInfo(data.routes[0]);
                
                if (data.routes[0].distance < 50) {
                    this.arriveAtDestination();
                }
            }
        } catch (error) {
            console.error('ETA update error:', error);
        }
    }

    async arriveAtDestination() {
        if (this.updateInterval) clearInterval(this.updateInterval);
        
        if (this.currentIncident) {
            await this.arriveAtIncident();
        } else {
            this.showToast('You have arrived at your destination!', 'success');
        }
        
        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard) routeCard.style.display = 'none';
        
        if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
            this.routeLayer = null;
        }
    }

    async acceptIncident(incidentId) {
        try {
            const response = await fetch(`/api/incidents/${incidentId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: this.userId })
            });
            
            if (!response.ok) throw new Error('Failed to accept incident');
            
            const result = await response.json();
            this.currentIncident = result.data || result;
            this.assignedIncident = this.currentIncident;
            
            await this.calculateRoute(this.currentIncident);
            
            await this.fetchIncidents();
            
            this.showToast('Incident accepted! Route calculated.', 'success');
            
        } catch (error) {
            console.error('Error accepting incident:', error);
            this.showToast('Failed to accept incident', 'error');
        }
    }

    async arriveAtIncident() {
        try {
            await fetch(`/api/incidents/${this.currentIncident.incident_id}/arrive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: this.userId })
            });
            
            this.showToast('You have arrived at the incident location!', 'success');
            
        } catch (error) {
            console.error('Error marking arrival:', error);
        }
    }

    async completeIncident() {
        try {
            await fetch(`/api/incidents/${this.currentIncident.incident_id}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: this.userId })
            });
            
            this.showToast('Incident marked as complete!', 'success');
            this.activeRoute = null;
            this.currentIncident = null;
            this.assignedIncident = null;
            
            if (this.routeLayer) this.map.removeLayer(this.routeLayer);
            
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'none';
            
            await this.fetchIncidents();
            
        } catch (error) {
            console.error('Error completing incident:', error);
        }
    }

 getUserLocation() {
    if (!navigator.geolocation) {
        console.error('Geolocation not supported');
        this.showToast('Location tracking not supported by your browser', 'error');
        return;
    }
    
    // Show loading indicator
    const locationDisplay = document.getElementById('userLocation');
    if (locationDisplay) {
        locationDisplay.textContent = '📍 Getting your location...';
    }
    
    // Get current position with real GPS
    navigator.geolocation.getCurrentPosition(
        (position) => {
            // Use REAL coordinates from GPS
            this.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            console.log('User location obtained:', this.userLocation);
            
            this.updateUserMarker();
            this.startLocationTracking();
            
            // Update display with real coordinates
            if (locationDisplay) {
                locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}`;
            }
            
            // Center map on user location
            if (this.map) {
                this.map.setView([this.userLocation.lat, this.userLocation.lng], 15);
            }
            
            this.showToast('Location detected successfully', 'success');
        },
        (error) => {
            console.error('Geolocation error:', error);
            
            let errorMessage = 'Unable to get your location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Please enable location permissions.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Location request timed out.';
                    break;
                default:
                    errorMessage += 'Please enable location services.';
            }
            
            this.showToast(errorMessage, 'error');
            
            if (locationDisplay) {
                locationDisplay.textContent = '📍 Location: Unable to get location';
            }
        },
        { 
            enableHighAccuracy: true, 
            timeout: 10000, 
            maximumAge: 0 
        }
    );
}

   startLocationTracking() {
    // Clear existing watch if any
    if (this.watchId) {
        navigator.geolocation.clearWatch(this.watchId);
    }
    
    // Watch position for continuous updates
    this.watchId = navigator.geolocation.watchPosition(
        (position) => {
            // Update with REAL coordinates
            this.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            console.log('Location updated:', this.userLocation);
            
            this.updateUserMarker();
            
            // Update display
            const locationDisplay = document.getElementById('userLocation');
            if (locationDisplay) {
                locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}`;
            }
            
            // If there's an active route, update ETA and distance
            if (this.activeRoute && this.currentIncident) {
                this.updateRouteETA(this.currentIncident);
            }
        },
        (error) => {
            console.error('Watch position error:', error);
        },
        { 
            enableHighAccuracy: true, 
            timeout: 5000, 
            maximumAge: 0 
        }
    );
}
    updateUserMarker() {
    if (this.userMarker && this.userLocation) {
        // Update marker position
        this.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
        
        // Optional: Add a pulsing circle around user location
        if (this.userAccuracyCircle) {
            this.map.removeLayer(this.userAccuracyCircle);
        }
        
        // Add accuracy circle (optional)
        // this.userAccuracyCircle = L.circle([this.userLocation.lat, this.userLocation.lng], {
        //     radius: 10,
        //     color: '#3b82f6',
        //     fillColor: '#3b82f6',
        //     fillOpacity: 0.2,
        //     weight: 1
        // }).addTo(this.map);
        
        // Don't auto-center on every update, only when map is not following route
        // Only center if map view is not currently following a route
        if (!this.activeRoute) {
            // Smooth pan to new location
            this.map.panTo([this.userLocation.lat, this.userLocation.lng], {
                animate: true,
                duration: 0.5
            });
        }
        
        // Update the marker popup
        this.userMarker.bindPopup(`
            <div style="text-align: center;">
                <strong>Your Location</strong><br>
                <span class="text-xs">${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}</span>
            </div>
        `);
    }
}

    changeTravelMode(mode) {
        this.travelMode = mode;
        
        if (this.currentIncident) {
            this.calculateRoute(this.currentIncident);
        }
        
        document.querySelectorAll('.travel-mode-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mode="${mode}"]`)?.classList.add('active');
        
        this.showToast(`Travel mode changed to ${mode}`, 'info');
    }

    setupEventListeners() {
        document.querySelectorAll('.travel-mode-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.mode;
                if (mode) this.changeTravelMode(mode);
            });
        });
        
        const refreshBtn = document.getElementById('refreshIncidents');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.fetchIncidents());
        }
        
        const stopTrackingBtn = document.getElementById('stopTracking');
        if (stopTrackingBtn) {
            stopTrackingBtn.addEventListener('click', () => this.stopTracking());
        }
    }

    stopTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        this.showToast('Location tracking stopped', 'info');
    }

    cleanup() {
        if (this.incidentInterval) {
            clearInterval(this.incidentInterval);
        }
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }

    showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .priority-critical { background: #fee2e2; color: #991b1b; }
    .priority-high { background: #ffedd5; color: #9a3412; }
    .priority-medium { background: #fef9c3; color: #854d0e; }
    .priority-low { background: #dcfce7; color: #166534; }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .w-full {
        width: 100%;
    }
    
    .legend i {
        margin-right: 5px;
        width: 16px;
        display: inline-block;
    }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new FirefighterDashboard();
});