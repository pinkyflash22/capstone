// resources/js/firefighter/dashboard.js

export class FirefighterDashboard {
    constructor() {
        this.map = null;
        this.userLocation = null;
        this.activeRoute = null;
        this.routeLayer = null;
        this.remainingRouteLayer = null; // New layer for remaining route
        this.currentIncident = null;
        this.watchId = null;
        this.updateInterval = null;
        this.incidentInterval = null;
        this.simulationInterval = null;
        this.simulationPoints = [];
        this.currentPointIndex = 0;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.userId = window.userId || null;
        this.teamId = window.teamId || null;
        this.travelMode = 'drive';
        this.geoapifyKey = '83677a1086d44c4e82f903fd2fe2ea9b' || null;
        
        // Animation properties
        this.animatedVehicle = null;
        this.animationFrame = null;
        this.animationStartTime = null;
        this.animationDuration = null;
        this.currentAnimationPoint = 0;
        this.currentDestinationType = null;
        this.routeDestination = null;
        
        // New properties for real-time updates
        this.realTimeLocationWatch = null;
        this.isSimulating = false;
        this.simulationSpeed = 1;
        this.lastLocationUpdate = null;
        
        // Store original route points
        this.originalSimulationPoints = [];
        this.traveledPoints = [];
        this.remainingPoints = [];

        // Initialize marker layers
        this.stationMarkers = null;
        this.incidentMarkers = null;
        this.assignedIncidentMarkers = null;
        this.userMarker = null;
        this.routeMarkers = [];
        this.distanceMarkers = [];
        
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
        this.addAnimationStyles();
        if (!this.checkGeolocationSupport()) {
            this.initMap();
            this.fetchStations();
            this.fetchIncidents();
            this.setupEventListeners();
            return;
        }
        
        this.initMap();
        this.getUserLocation();
        this.fetchStations();
        this.fetchIncidents();
        this.setupEventListeners();
    }

    checkGeolocationSupport() {
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
        this.fetchIncidents();
        this.incidentInterval = setInterval(() => {
            this.fetchIncidents();
        }, 10000);
    }

    async fetchIncidents() {
        try {
            const response = await fetch('/api/markers', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch markers');
            
            const data = await response.json();
            const allMarkers = data.data || data;
            
            const incidentMarkers = allMarkers.filter(marker => marker.markerCategory_id === 3);
            
            this.incidents = incidentMarkers.map(marker => ({
                incident_id: marker.marker_id,
                marker: marker,
                title: marker.title,
                description: marker.description,
                created_at: marker.created_at,
                priority: this.getPriorityFromCategory(marker.markerCategory_id)
            }));
            
            await this.fetchAssignedIncident();
            this.displayAllIncidents();
            this.updateIncidentStats(this.incidents);
            
        } catch (error) {
            console.error('Error fetching incidents:', error);
        }
    }
    
    getPriorityFromCategory(categoryId) {
        const priorityMap = {
            1: 'low',
            2: 'medium',
            3: 'high',
            4: 'critical'
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
        if (this.incidentMarkers) this.incidentMarkers.clearLayers();
        if (this.assignedIncidentMarkers) this.assignedIncidentMarkers.clearLayers();
        
        if (!this.incidents || this.incidents.length === 0) {
            console.log('No incidents to display');
            return;
        }
        
        this.incidents.forEach(incident => {
            const markerData = incident.marker || incident;
            
            if (markerData.lat && markerData.lng) {
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
                        <button class="btn btn-primary btn-sm mt-2 w-full" onclick="window.dashboard.startSimulation(${incident.incident_id})">
                            <i class="fa-solid fa-play"></i> Simulate Route
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
                <div><i class="fa-solid fa-truck-fire" style="color: #dc2626;"></i> Fire Truck</div>
                <div><i class="fa-solid fa-road" style="color: #9ca3af;"></i> Traveled Path</div>
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
                    <i class="fa-solid fa-phone"></i> ${station.phone || 'N/A'}<br>
                    <i class="fa-solid fa-users"></i> ${station.personnel_count || 'N/A'} personnel
                </div>
                <button class="btn btn-sm btn-outline mt-2" onclick="window.dashboard.calculateRouteToStation(${station.id})">
                    <i class="fa-solid fa-route"></i> Get Directions
                </button>
            </div>
        `);
    }

    // ==================== GEOAPIFY ROUTING FUNCTIONS ====================
    
   async calculateRouteToStation(stationId) {
        const station = this.stations.find(s => s.id == stationId);
        if (!station || !this.userLocation) return;
        
        const destination = {
            lat: station.lat || station.marker?.lat,
            lng: station.lng || station.marker?.lng,
            title: station.station_name || station.name || 'Fire Station'
        };
        
        await this.calculateRouteWithGeoapify(this.userLocation, destination);
        this.showToast(`Route to ${destination.title} calculated`, 'success');
        
        // Automatically start simulation for station route
        this.startSimulationForStation();
    }

    startSimulationForStation() {
        if (!this.simulationPoints || this.simulationPoints.length === 0) {
            this.showToast('No route available for simulation', 'error');
            return;
        }
        
        if (this.simulationInterval) {
            clearInterval(this.simulationInterval);
            this.simulationInterval = null;
        }
        
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        
        this.currentPointIndex = 0;
        this.currentDestinationType = 'station';
        this.isSimulating = true;
        
        // Store original points and reset traveled points
        this.originalSimulationPoints = [...this.simulationPoints];
        this.traveledPoints = [];
        this.remainingPoints = [...this.simulationPoints];
        
        this.showToast('Starting route simulation to station...', 'info');
        
        // Create animated fire truck marker if not exists
        if (!this.animatedVehicle) {
            const startPoint = this.simulationPoints[0];
            this.animatedVehicle = L.marker(startPoint, {
                icon: this.createFireTruckIcon(),
                zIndexOffset: 1000
            }).addTo(this.map);
            this.animatedVehicle.bindPopup('🚒 Fire Truck En Route to Station');
        } else {
            this.animatedVehicle.setLatLng(this.simulationPoints[0]);
            this.animatedVehicle.bindPopup('🚒 Fire Truck En Route to Station');
        }
        
        // Remove the start marker if it exists
        this.removeStartMarker();
        
        // Set user location to start point
        this.userLocation = {
            lat: this.simulationPoints[0][0],
            lng: this.simulationPoints[0][1]
        };
        this.updateUserMarker();
        
        // Start smooth animation
        this.startSmoothAnimation();
        
        // Start real-time location updates
        this.startRealTimeLocationUpdates();
    }

    removeStartMarker() {
        // Remove the start marker from routeMarkers
        if (this.routeMarkers && this.routeMarkers.length > 0) {
            const startMarker = this.routeMarkers[0];
            if (startMarker) {
                this.map.removeLayer(startMarker);
                this.routeMarkers.shift(); // Remove from array
            }
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
            
            const destination = {
                lat: this.currentIncident.marker.lat,
                lng: this.currentIncident.marker.lng,
                title: this.currentIncident.marker.title
            };
            
            await this.calculateRouteWithGeoapify(this.userLocation, destination);
            await this.fetchIncidents();
            this.showToast('Incident accepted! Route calculated.', 'success');
            
        } catch (error) {
            console.error('Error accepting incident:', error);
            this.showToast('Failed to accept incident', 'error');
        }
    }

    async calculateRouteWithGeoapify(origin, destination) {
        if (!origin || !destination || !this.geoapifyKey) {
            console.error('Missing origin, destination, or API key');
            this.showToast('Missing routing information', 'error');
            return;
        }
        
        const modeMap = {
            'driving': 'drive',
            'walking': 'walk',
            'cycling': 'bike'
        };
        const mode = modeMap[this.travelMode] || 'drive';
        
        const url = `https://api.geoapify.com/v1/routing?waypoints=${origin.lat},${origin.lng}|${destination.lat},${destination.lng}&mode=${mode}&apiKey=${this.geoapifyKey}`;
        
        try {
            this.showToast('Calculating route...', 'info');
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('Route calculation failed');
            
            const data = await response.json();
            
            if (data.features && data.features.length > 0) {
                const route = data.features[0];
                this.activeRoute = route;
                console.log("displaying route data: ", route);
                this.displayGeoapifyRoute(route);
                this.updateRouteInfoFromGeoapify(route);
                
                const routeCard = document.getElementById('routeInfoCard');
                if (routeCard) routeCard.style.display = 'block';
                
                this.routeDestination = destination;
            }
            
        } catch (error) {
            console.error('Geoapify route calculation error:', error);
            this.showToast('Failed to calculate route', 'error');
        }
    }

    displayGeoapifyRoute(route) {
        if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
        }
        
        // Extract geometry coordinates from Geoapify response
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
        
        // Store original route points
        this.originalSimulationPoints = [...latlngs];
        this.remainingPoints = [...latlngs];
        
        // Create the initial full route (blue)
        this.routeLayer = L.polyline(latlngs, {
            color: '#3b82f6',
            weight: 5,
            opacity: 0.8,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(this.map);
        
        // Fit map to route bounds
        const bounds = this.routeLayer.getBounds();
        if (bounds.isValid()) {
            this.map.fitBounds(bounds, { padding: [50, 50] });
        }
        
        // Add markers for start and end
        this.clearRouteMarkers();
        
        // Start marker (first point) - will be removed when simulation starts
        const startPoint = latlngs[0];
        const startMarker = L.marker(startPoint, {
            icon: L.divIcon({
                className: 'start-marker',
                html: '<div style="background: #22c55e; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #22c55e;"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).bindPopup('<strong>Start Point</strong><br>Your location').addTo(this.map);
        
        // End marker (last point)
        const endPoint = latlngs[latlngs.length - 1];
        const endMarker = L.marker(endPoint, {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #ef4444;"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).bindPopup('<strong>Destination</strong><br>target location').addTo(this.map);
        
        this.routeMarkers = [startMarker, endMarker];
        
        // Store route points for simulation
        this.simulationPoints = latlngs;
        this.currentPointIndex = 0;
        
        console.log('Route displayed successfully with', latlngs.length, 'points');
        
        // Add distance markers
        this.addDistanceMarkers(latlngs, route.properties.distance);
    }

    addDistanceMarkers(latlngs, totalDistance) {
        if (!latlngs.length) return;
        
        let cumulativeDistances = [0];
        for (let i = 1; i < latlngs.length; i++) {
            const dist = this.calculateDistanceBetweenPoints(
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
                    }).bindPopup(`${(d/1000).toFixed(1)} km`).addTo(this.map);
                    
                    this.distanceMarkers.push(marker);
                    break;
                }
            }
        }
    }

    calculateDistanceBetweenPoints(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    clearRouteMarkers() {
        this.routeMarkers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.routeMarkers = [];
        
        if (this.distanceMarkers) {
            this.distanceMarkers.forEach(marker => {
                this.map.removeLayer(marker);
            });
            this.distanceMarkers = [];
        }
    }

    updateRouteInfoFromGeoapify(route) {
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
        
        if (destinationTitle && this.routeDestination) {
            destinationTitle.textContent = this.routeDestination.title || 'Destination';
        }
        
        const routeCard = document.getElementById('routeInfoCard');
        if (routeCard && !document.getElementById('routeProgress')) {
            const progressHTML = `
                <div class="route-progress-container mt-3">
                    <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
                </div>
                <div class="speed-control mt-2">
                    <button class="speed-btn" data-speed="slow">🐢 Slow</button>
                    <button class="speed-btn active" data-speed="normal">🚒 Normal</button>
                    <button class="speed-btn" data-speed="fast">🐇 Fast</button>
                </div>
            `;
            routeCard.insertAdjacentHTML('beforeend', progressHTML);
            
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const speed = btn.dataset.speed;
                    this.setAnimationSpeed(speed);
                });
            });
        }
    }

    setAnimationSpeed(speed) {
        if (this.animationFrame && this.simulationPoints && this.simulationPoints.length > 0 && this.isSimulating) {
            this.startSmoothAnimation();
        }
    }

    // ==================== SIMULATION FUNCTIONS ====================
    
    startSimulation(incidentId) {
        if (!this.simulationPoints || this.simulationPoints.length === 0) {
            this.showToast('No route available for simulation', 'error');
            return;
        }
        
        if (this.simulationInterval) {
            clearInterval(this.simulationInterval);
            this.simulationInterval = null;
        }
        
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        
        this.currentPointIndex = 0;
        this.isSimulating = true;
        
        // Store original points and reset traveled points
        this.originalSimulationPoints = [...this.simulationPoints];
        this.traveledPoints = [];
        this.remainingPoints = [...this.simulationPoints];
        
        const incident = this.incidents.find(i => i.incident_id === incidentId);
        const destinationType = incident ? 'incident' : 'station';
        
        this.showToast(`Starting route simulation to ${destinationType}...`, 'info');
        
        if (!this.animatedVehicle) {
            const startPoint = this.simulationPoints[0];
            this.animatedVehicle = L.marker(startPoint, {
                icon: this.createFireTruckIcon(),
                zIndexOffset: 1000
            }).addTo(this.map);
        } else {
            this.animatedVehicle.setLatLng(this.simulationPoints[0]);
        }
        
        const popupText = incident ? '🚒 Fire Truck En Route to Incident' : '🚒 Fire Truck En Route to Station';
        this.animatedVehicle.bindPopup(popupText);
        
        // Remove the start marker
        this.removeStartMarker();
        
        this.userLocation = {
            lat: this.simulationPoints[0][0],
            lng: this.simulationPoints[0][1]
        };
        this.updateUserMarker();
        
        this.currentDestinationType = destinationType;
        
        this.startSmoothAnimation();
        this.startRealTimeLocationUpdates();
    }

    updateRemainingRoute(currentLat, currentLng) {
        if (!this.originalSimulationPoints || this.originalSimulationPoints.length === 0) return;
        
        // Find the closest point on the route to current position
        let closestIndex = 0;
        let minDistance = Infinity;
        
        for (let i = 0; i < this.originalSimulationPoints.length; i++) {
            const point = this.originalSimulationPoints[i];
            const distance = this.calculateDistanceBetweenPoints(
                currentLat, currentLng,
                point[0], point[1]
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
            }
        }
        
        // Get the remaining points from closest index to end
        const remainingPoints = this.originalSimulationPoints.slice(closestIndex);
        
        // Update the route layer to show only remaining path
        if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
        }
        
        if (remainingPoints.length >= 2) {
            // Create new route layer with remaining path (blue)
            this.routeLayer = L.polyline(remainingPoints, {
                color: '#3b82f6',
                weight: 5,
                opacity: 0.8,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(this.map);
            
            // Add traveled path in gray (optional)
            if (closestIndex > 0) {
                const traveledPoints = this.originalSimulationPoints.slice(0, closestIndex + 1);
                if (traveledPoints.length >= 2) {
                    const traveledLayer = L.polyline(traveledPoints, {
                        color: '#9ca3af',
                        weight: 4,
                        opacity: 0.5,
                        lineJoin: 'round',
                        lineCap: 'round',
                        className: 'traveled-path'
                    }).addTo(this.map);
                    
                    // Store traveled layer for cleanup
                    this.traveledLayer = traveledLayer;
                }
            }
        }
        
        this.remainingPoints = remainingPoints;
    }

    startRealTimeLocationUpdates() {
        if (this.realTimeLocationWatch) {
            clearInterval(this.realTimeLocationWatch);
        }
        
        this.realTimeLocationWatch = setInterval(() => {
            if (this.isSimulating && this.animatedVehicle) {
                const truckPosition = this.animatedVehicle.getLatLng();
                if (truckPosition) {
                    this.userLocation = {
                        lat: truckPosition.lat,
                        lng: truckPosition.lng
                    };
                    
                    this.updateUserMarker();
                    
                    // Update the route to show only remaining path
                    this.updateRemainingRoute(truckPosition.lat, truckPosition.lng);
                    
                    const locationDisplay = document.getElementById('userLocation');
                    if (locationDisplay) {
                        locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)} (Moving)`;
                    }
                    
                    if (this.simulationPoints && this.simulationPoints.length > 0) {
                        const remainingDistance = this.calculateRemainingDistance(truckPosition.lat, truckPosition.lng);
                        const distanceEl = document.getElementById('routeDistance');
                        if (distanceEl) {
                            distanceEl.textContent = `${(remainingDistance / 1000).toFixed(1)} km`;
                        }
                    }
                }
            }
        }, 500); // Update every 500ms for smoother route fading
    }

    calculateRemainingDistance(currentLat, currentLng) {
        if (!this.originalSimulationPoints || this.originalSimulationPoints.length === 0) return 0;
        
        let closestIndex = 0;
        let minDistance = Infinity;
        
        for (let i = 0; i < this.originalSimulationPoints.length; i++) {
            const point = this.originalSimulationPoints[i];
            const distance = this.calculateDistanceBetweenPoints(
                currentLat, currentLng,
                point[0], point[1]
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
            }
        }
        
        let remainingDistance = 0;
        for (let i = closestIndex; i < this.originalSimulationPoints.length - 1; i++) {
            const p1 = this.originalSimulationPoints[i];
            const p2 = this.originalSimulationPoints[i + 1];
            remainingDistance += this.calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
        }
        
        return remainingDistance;
    }

    getSpeedMultiplier() {
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

    startSmoothAnimation() {
        if (!this.simulationPoints || this.simulationPoints.length < 2) {
            console.error('Not enough points for animation');
            return;
        }
        
        const segments = [];
        let totalDistance = 0;
        
        for (let i = 0; i < this.simulationPoints.length - 1; i++) {
            const p1 = this.simulationPoints[i];
            const p2 = this.simulationPoints[i + 1];
            const distance = this.calculateDistanceBetweenPoints(p1[0], p1[1], p2[0], p2[1]);
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
        const speed = baseSpeed * this.getSpeedMultiplier();
        const totalDuration = totalDistance / speed;
        const startTime = performance.now();
        
        console.log(`Animation started: Total distance ${(totalDistance/1000).toFixed(2)}km, Duration ${totalDuration.toFixed(0)} seconds`);
        
        const animate = (currentTime) => {
            const elapsed = (currentTime - startTime) / 1000;
            let progress = Math.min(elapsed / totalDuration, 1);
            
            if (progress >= 1) {
                this.stopSimulation();
                
                if (this.currentDestinationType === 'incident' && this.currentIncident) {
                    this.showToast('You have arrived at the incident location!', 'success');
                    if (confirm('Have you completed the incident?')) {
                        this.completeIncident();
                    }
                } else if (this.currentDestinationType === 'station') {
                    this.showToast('You have arrived at the fire station!', 'success');
                    if (this.routeDestination) {
                        this.showToast(`Arrived at ${this.routeDestination.title}`, 'success');
                    }
                }
                
                if (this.realTimeLocationWatch) {
                    clearInterval(this.realTimeLocationWatch);
                    this.realTimeLocationWatch = null;
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
                
                if (this.animatedVehicle) {
                    this.animatedVehicle.setLatLng([lat, lng]);
                    
                    const truckElement = this.animatedVehicle.getElement();
                    if (truckElement) {
                        const truckDiv = truckElement.querySelector('div');
                        if (truckDiv) {
                            truckDiv.style.transform = `rotate(${currentSegment.angle}deg)`;
                            truckDiv.style.transition = 'transform 0.1s linear';
                        }
                    }
                }
                
                this.userLocation = { lat, lng };
                this.updateUserMarker();
                
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
                if (locationDisplay && this.isSimulating) {
                    locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${lat.toFixed(6)}, ${lng.toFixed(6)} (Moving)`;
                }
                
                const mapCenter = this.map.getCenter();
                const truckLatLng = this.animatedVehicle.getLatLng();
                const distanceToCenter = this.calculateDistanceBetweenPoints(
                    mapCenter.lat, mapCenter.lng,
                    truckLatLng.lat, truckLatLng.lng
                );
                
                if (distanceToCenter > 200) {
                    this.map.panTo(truckLatLng, { animate: true, duration: 0.5 });
                }
            }
            
            this.animationFrame = requestAnimationFrame(animate);
        };
        
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
        }
        this.animationFrame = requestAnimationFrame(animate);
    }

    stopSimulation() {
        if (this.simulationInterval) {
            clearInterval(this.simulationInterval);
            this.simulationInterval = null;
        }
        
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        
        if (this.realTimeLocationWatch) {
            clearInterval(this.realTimeLocationWatch);
            this.realTimeLocationWatch = null;
        }
        
        this.isSimulating = false;
        
        // Restore full route if needed
        if (this.originalSimulationPoints && this.originalSimulationPoints.length > 0 && !this.currentIncident) {
            if (this.routeLayer) {
                this.map.removeLayer(this.routeLayer);
            }
            if (this.traveledLayer) {
                this.map.removeLayer(this.traveledLayer);
                this.traveledLayer = null;
            }
            
            this.routeLayer = L.polyline(this.originalSimulationPoints, {
                color: '#3b82f6',
                weight: 5,
                opacity: 0.8,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(this.map);
        }
        
        if (this.animatedVehicle && this.simulationPoints && this.simulationPoints.length > 0) {
            const finalPoint = this.simulationPoints[this.simulationPoints.length - 1];
            this.animatedVehicle.setLatLng(finalPoint);
        }
        
        const locationDisplay = document.getElementById('userLocation');
        if (locationDisplay && this.userLocation) {
            locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}`;
        }
        
        this.showToast('Simulation stopped', 'info');
    }

    createFireTruckIcon() {
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

    addAnimationStyles() {
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

    // ==================== LOCATION FUNCTIONS ====================
    
    getUserLocation() {
        if (!navigator.geolocation) {
            console.error('Geolocation not supported');
            this.showToast('Location tracking not supported by your browser', 'error');
            return;
        }
        
        const locationDisplay = document.getElementById('userLocation');
        if (locationDisplay) {
            locationDisplay.textContent = '📍 Getting your location...';
        }
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                console.log('User location obtained:', this.userLocation);
                this.updateUserMarker();
                this.startLocationTracking();
                
                if (locationDisplay) {
                    locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}`;
                }
                
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
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }
        
        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                if (!this.isSimulating) {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    this.updateUserMarker();
                    
                    const locationDisplay = document.getElementById('userLocation');
                    if (locationDisplay) {
                        locationDisplay.innerHTML = `<i class="fa-solid fa-location-dot"></i> Location: ${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}`;
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

    updateUserMarker() {
        if (this.userMarker && this.userLocation) {
            this.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
            
            if (!this.activeRoute && !this.isSimulating) {
                this.map.panTo([this.userLocation.lat, this.userLocation.lng], {
                    animate: true,
                    duration: 0.5
                });
            }
            
            const statusText = this.isSimulating ? '(Moving Along Route)' : '';
            this.userMarker.bindPopup(`
                <div style="text-align: center;">
                    <strong>Your Location ${statusText}</strong><br>
                    <span class="text-xs">${this.userLocation.lat.toFixed(6)}, ${this.userLocation.lng.toFixed(6)}</span>
                    ${this.isSimulating ? '<br><span class="text-xs text-blue-500">🚒 Moving along route</span>' : ''}
                </div>
            `);
        }
    }

    // ==================== TRAVEL MODE FUNCTIONS ====================
    
    changeTravelMode(mode) {
        this.travelMode = mode;
        
        if (this.currentIncident && this.userLocation) {
            const destination = {
                lat: this.currentIncident.marker.lat,
                lng: this.currentIncident.marker.lng,
                title: this.currentIncident.marker.title
            };
            this.calculateRouteWithGeoapify(this.userLocation, destination);
        }
        
        document.querySelectorAll('.travel-mode-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mode="${mode}"]`)?.classList.add('active');
        
        this.showToast(`Travel mode changed to ${mode}`, 'info');
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
            
            if (confirm('Have you completed the incident?')) {
                this.completeIncident();
            }
            
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
            this.isSimulating = false;
            
            if (this.animatedVehicle) {
                this.map.removeLayer(this.animatedVehicle);
                this.animatedVehicle = null;
            }
            
            if (this.traveledLayer) {
                this.map.removeLayer(this.traveledLayer);
                this.traveledLayer = null;
            }
            
            if (this.realTimeLocationWatch) {
                clearInterval(this.realTimeLocationWatch);
                this.realTimeLocationWatch = null;
            }
            
            if (this.routeLayer) this.map.removeLayer(this.routeLayer);
            this.clearRouteMarkers();
            
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'none';
            
            this.stopSimulation();
            await this.fetchIncidents();
            
        } catch (error) {
            console.error('Error completing incident:', error);
        }
    }

    // ==================== UTILITY FUNCTIONS ====================
    
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
        
        this.stopSimulation();
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
        if (this.simulationInterval) {
            clearInterval(this.simulationInterval);
        }
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
        }
        if (this.realTimeLocationWatch) {
            clearInterval(this.realTimeLocationWatch);
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