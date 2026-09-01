// resources/js/map/CentralMapService.js

class CentralMapService {
    constructor() {
        this.map = null;
        this.userLocation = null;
        this.userMarker = null;
        this.animatedVehicle = null;
        this.animationFrame = null;
        this.simulationPoints = [];
        this.isSimulating = false;
        this.routeLayer = null;
        this.originalSimulationPoints = [];
        this.geoapifyKey = '83677a1086d44c4e82f903fd2fe2ea9b';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        // Store markers
        this.incidentMarkers = [];
        this.stationMarkers = [];
        this.userMarkers = [];
        
        // Route info elements
        this.routeCard = null;
        
        // Callbacks for page-specific functionality
        this.onIncidentClick = null;
        this.onStationClick = null;
    }
    
    init(mapElementId, options = {}) {
        this.mapElementId = mapElementId;
        this.initMap();
        
        if (options.showUserLocation !== false) {
            this.getUserLocation();
        }
        
        if (options.showIncidents !== false) {
            this.fetchIncidents();
        }
        
        if (options.showStations !== false) {
            this.fetchStations();
        }
        
        if (options.showActiveUsers !== false) {
            this.fetchActiveUsers();
        }
        
        this.initRouteCard();
        this.startLocationTracking();
        
        console.log(`CentralMapService initialized for ${mapElementId}`);
        return this.map;
    }
    
    initMap() {
        const defaultCenter = [16.0433, 120.3333];
        this.map = L.map(this.mapElementId).setView(defaultCenter, 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        this.addAnimationStyles();
    }
    
    addAnimationStyles() {
        if (!document.getElementById('map-animation-styles')) {
            const style = document.createElement('style');
            style.id = 'map-animation-styles';
            style.textContent = `
                @keyframes blink {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(1.2); }
                }
                @keyframes pulse {
                    0% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.1); opacity: 0.8; }
                    100% { transform: scale(1); opacity: 1; }
                }
                .fire-truck-marker {
                    transition: all 0.1s linear;
                    z-index: 1000;
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
                }
                .speed-control {
                    margin-top: 8px;
                    display: flex;
                    gap: 8px;
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
                }
                .stop-sim-btn {
                    width: 100%;
                    padding: 6px;
                    background: #ef4444;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    margin-top: 8px;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    initRouteCard() {
        this.routeCard = document.getElementById('routeInfoCard');
        if (!this.routeCard) {
            this.routeCard = document.createElement('div');
            this.routeCard.id = 'routeInfoCard';
            this.routeCard.innerHTML = `
                <h4><i class="fa-solid fa-route"></i> Route Information</h4>
                <div><strong>Destination:</strong> <span id="destinationTitle">---</span></div>
                <div><strong>Distance:</strong> <span id="routeDistance">0 km</span></div>
                <div><strong>ETA:</strong> <span id="routeETA">--:--</span></div>
                <div><strong>Duration:</strong> <span id="routeDuration">0 min</span></div>
                <div class="route-progress-container">
                    <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
                </div>
                <div class="speed-control">
                    <button class="speed-btn" data-speed="slow">🐢 Slow</button>
                    <button class="speed-btn active" data-speed="normal">🚒 Normal</button>
                    <button class="speed-btn" data-speed="fast">🐇 Fast</button>
                </div>
                <button class="stop-sim-btn" onclick="window.centralMapService.stopSimulation()">Stop Simulation</button>
            `;
            this.routeCard.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                min-width: 250px;
                z-index: 1000;
                display: none;
                font-family: sans-serif;
            `;
            document.body.appendChild(this.routeCard);
            
            // Add speed control listeners
            this.routeCard.querySelectorAll('.speed-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.routeCard.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    if (this.isSimulating) this.startAnimation();
                });
            });
        }
    }
    
    getSpeedMultiplier() {
        const activeBtn = this.routeCard?.querySelector('.speed-btn.active');
        if (activeBtn) {
            const speed = activeBtn.dataset.speed;
            switch(speed) {
                case 'slow': return 0.5;
                case 'fast': return 2;
                default: return 1;
            }
        }
        return 1;
    }
    
    getUserLocation() {
        if (!navigator.geolocation) {
            console.error('Geolocation not supported');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                if (!this.userMarker) {
                    this.userMarker = L.marker([this.userLocation.lat, this.userLocation.lng], {
                        icon: L.divIcon({
                            className: 'user-marker',
                            html: '<div style="background: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #3b82f6;"></div>',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        })
                    }).addTo(this.map);
                } else {
                    this.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
                }
                
                this.map.setView([this.userLocation.lat, this.userLocation.lng], 14);
            },
            (error) => console.error('Geolocation error:', error)
        );
    }
    
    startLocationTracking() {
        if (!navigator.geolocation) return;
        
        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                if (!this.isSimulating) {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    if (this.userMarker) {
                        this.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
                    }
                }
            },
            (error) => console.error('Watch error:', error),
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    }
    
    async fetchIncidents() {
        try {
            const response = await fetch('/api/markers', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
            });
            const data = await response.json();
            const incidents = data.data || data;
            
            incidents.forEach(incident => {
                if (incident.lat && incident.lng) {
                    this.addIncidentMarker(incident);
                }
            });
        } catch (error) {
            console.error('Error fetching incidents:', error);
        }
    }
    
    addIncidentMarker(incident) {
        const colors = { critical: '#ef4444', high: '#f97316', medium: '#eab308', low: '#22c55e' };
        const color = colors[incident.priority] || '#eab308';
        
        let icon = '⚠️';
        if (incident.incident_type === 'fire') icon = '🔥';
        else if (incident.incident_type === 'drown') icon = '💧';
        else if (incident.incident_type === 'treefall') icon = '🌳';
        
        const marker = L.marker([incident.lat, incident.lng], {
            icon: L.divIcon({
                className: 'incident-marker',
                html: `<div style="background: ${color}; width: 28px; height: 28px; border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 14px;">${icon}</div>`,
                iconSize: [28, 28],
                popupAnchor: [0, -14]
            })
        }).addTo(this.map);
        
        marker.bindPopup(`
            <div style="padding: 8px; min-width: 150px;">
                <strong>Incident #${incident.marker_id}</strong><br>
                Type: ${incident.incident_type || 'Fire'}<br>
                ${incident.title ? `<strong>${incident.title}</strong><br>` : ''}
                ${incident.description ? `<p style="font-size: 12px; margin-top: 4px;">${incident.description.substring(0, 100)}</p>` : ''}
                <button onclick="window.centralMapService.calculateRouteToIncident(${incident.marker_id})" style="margin-top: 8px; padding: 4px 8px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fa-solid fa-route"></i> Navigate
                </button>
                <button onclick="window.showIncidentDetails(${incident.marker_id})" style="margin-top: 8px; padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fa-regular fa-eye"></i> Details
                </button>
            </div>
        `);
        
        this.incidentMarkers.push({ id: incident.marker_id, marker, incident });
    }
    
    async fetchStations() {
        try {
            const response = await fetch('/api/station', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
            });
            const data = await response.json();
            const stations = data.data || data.stationData || data;
            
            stations.forEach(station => {
                const lat = station.lat || station.marker?.lat;
                const lng = station.lng || station.marker?.lng;
                if (lat && lng) {
                    this.addStationMarker(station, lat, lng);
                }
            });
        } catch (error) {
            console.error('Error fetching stations:', error);
        }
    }
    
    addStationMarker(station, lat, lng) {
        const marker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'station-marker',
                html: `<div style="background: #22c55e; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">🏢</div>`,
                iconSize: [28, 28],
                popupAnchor: [0, -14]
            })
        }).addTo(this.map);
        
        marker.bindPopup(`
            <div style="padding: 8px; min-width: 180px;">
                <strong>${station.station_name}</strong><br>
                📞 ${station.contact_number || 'N/A'}<br>
                ✉️ ${station.email || 'N/A'}<br>
                <button onclick="window.centralMapService.calculateRouteToStation(${station.station_id}, '${station.station_name}', ${lat}, ${lng})" style="margin-top: 8px; padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fa-solid fa-route"></i> Get Directions
                </button>
            </div>
        `);
        
        this.stationMarkers.push({ id: station.station_id, marker, station, lat, lng });
    }
    
    async fetchActiveUsers() {
        try {
            const response = await fetch('/api/active-users', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
            });
            const data = await response.json();
            const users = data.data || data;
            
            users.forEach(user => {
                if (user.lat && user.lng) {
                    this.addUserMarker(user);
                }
            });
        } catch (error) {
            console.error('Error fetching active users:', error);
        }
    }
    
    addUserMarker(user) {
        const marker = L.marker([user.lat, user.lng], {
            icon: L.divIcon({
                className: 'user-marker',
                html: `<div style="background: #8b5cf6; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">👤</div>`,
                iconSize: [24, 24],
                popupAnchor: [0, -12]
            })
        }).addTo(this.map);
        
        marker.bindPopup(`
            <div style="padding: 6px;">
                <strong>${user.name || `User #${user.id}`}</strong><br>
                Status: ${user.status || 'Active'}<br>
                Last seen: ${new Date(user.last_seen).toLocaleTimeString()}
            </div>
        `);
        
        this.userMarkers.push({ id: user.id, marker, user });
    }
    
    createFireTruckIcon() {
        return L.divIcon({
            className: 'fire-truck-marker',
            html: `
                <div style="position: relative; width: 40px; height: 40px;">
                    <div style="background: #dc2626; width: 40px; height: 40px; border-radius: 8px; transform: rotate(45deg); box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>
                    <i class="fa-solid fa-truck-fire" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 20px;"></i>
                    <div style="position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; animation: blink 0.5s infinite;"></div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
    }
    
    async calculateRouteToStation(stationId, stationName, lat, lng) {
        if (!this.userLocation) {
            this.showToast('Getting your location...', 'info');
            this.getUserLocation();
            setTimeout(() => this.calculateRouteToStation(stationId, stationName, lat, lng), 2000);
            return;
        }
        
        const destination = { lat, lng, title: stationName };
        await this.calculateRoute(this.userLocation, destination);
        this.startSimulation(destination);
    }
    
    async calculateRouteToIncident(incidentId) {
        const incident = this.incidentMarkers.find(m => m.id === incidentId)?.incident;
        if (!incident) return;
        
        if (!this.userLocation) {
            this.showToast('Getting your location...', 'info');
            this.getUserLocation();
            setTimeout(() => this.calculateRouteToIncident(incidentId), 2000);
            return;
        }
        
        const destination = {
            lat: incident.lat,
            lng: incident.lng,
            title: `Incident #${incidentId}`
        };
        await this.calculateRoute(this.userLocation, destination);
        this.startSimulation(destination);
    }
    
    async calculateRoute(origin, destination) {
        const url = `https://api.geoapify.com/v1/routing?waypoints=${origin.lat},${origin.lng}|${destination.lat},${destination.lng}&mode=drive&apiKey=${this.geoapifyKey}`;
        
        try {
            this.showToast('Calculating route...', 'info');
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.features?.length) {
                this.displayRoute(data.features[0]);
                this.updateRouteInfo(data.features[0]);
                this.routeDestination = destination;
                this.showToast(`Route to ${destination.title} calculated`, 'success');
            }
        } catch (error) {
            console.error('Route error:', error);
            this.showToast('Failed to calculate route', 'error');
        }
    }
    
    displayRoute(route) {
        if (this.routeLayer) this.map.removeLayer(this.routeLayer);
        
        const geometry = route.geometry;
        let latlngs = [];
        
        if (geometry.type === 'LineString') {
            latlngs = geometry.coordinates.map(coord => [coord[1], coord[0]]);
        } else if (geometry.type === 'MultiLineString') {
            geometry.coordinates.forEach(segment => {
                latlngs.push(...segment.map(coord => [coord[1], coord[0]]));
            });
        }
        
        if (latlngs.length) {
            this.routeLayer = L.polyline(latlngs, { color: '#3b82f6', weight: 5 }).addTo(this.map);
            this.map.fitBounds(this.routeLayer.getBounds(), { padding: [50, 50] });
            this.simulationPoints = latlngs;
            this.originalSimulationPoints = [...latlngs];
        }
    }
    
    updateRouteInfo(route) {
        const distance = (route.properties.distance / 1000).toFixed(1);
        const time = Math.floor(route.properties.time / 60);
        const hours = Math.floor(time / 60);
        const minutes = time % 60;
        
        const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        const eta = new Date(Date.now() + (route.properties.time * 1000));
        
        const distanceEl = document.getElementById('routeDistance');
        const durationEl = document.getElementById('routeDuration');
        const etaEl = document.getElementById('routeETA');
        const destTitle = document.getElementById('destinationTitle');
        
        if (distanceEl) distanceEl.textContent = `${distance} km`;
        if (durationEl) durationEl.textContent = durationText;
        if (etaEl) etaEl.textContent = eta.toLocaleTimeString();
        if (destTitle && this.routeDestination) destTitle.textContent = this.routeDestination.title;
        
        if (this.routeCard) this.routeCard.style.display = 'block';
    }
    
    startSimulation(destination) {
        if (!this.simulationPoints?.length) {
            this.showToast('No route to simulate', 'error');
            return;
        }
        
        if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
        this.isSimulating = true;
        
        if (!this.animatedVehicle) {
            this.animatedVehicle = L.marker(this.simulationPoints[0], {
                icon: this.createFireTruckIcon(),
                zIndexOffset: 1000
            }).addTo(this.map);
        }
        
        this.animatedVehicle.bindPopup(`🚒 En Route to ${destination.title}`);
        this.startAnimation();
        this.showToast('Simulation started', 'success');
    }
    
    startAnimation() {
        const segments = [];
        let totalDistance = 0;
        
        for (let i = 0; i < this.simulationPoints.length - 1; i++) {
            const p1 = this.simulationPoints[i];
            const p2 = this.simulationPoints[i + 1];
            const distance = this.calculateDistance(p1[0], p1[1], p2[0], p2[1]);
            totalDistance += distance;
            segments.push({
                start: p1, end: p2, distance: distance,
                cumulativeDistance: totalDistance,
                angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
            });
        }
        
        const speed = 13.89 * this.getSpeedMultiplier();
        const totalDuration = totalDistance / speed;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = (currentTime - startTime) / 1000;
            let progress = Math.min(elapsed / totalDuration, 1);
            
            if (progress >= 1) {
                this.stopSimulation();
                this.showToast('Arrived at destination!', 'success');
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
                
                this.animatedVehicle.setLatLng([lat, lng]);
                this.updateRemainingRoute(lat, lng);
                
                if (this.userMarker) {
                    this.userMarker.setLatLng([lat, lng]);
                }
                
                const remainingDistance = totalDistance - distanceTraveled;
                const remainingTime = remainingDistance / speed;
                
                const progressBar = document.getElementById('routeProgress');
                if (progressBar) progressBar.style.width = `${progress * 100}%`;
                
                const durationEl = document.getElementById('routeDuration');
                if (durationEl) {
                    const minutes = Math.floor(remainingTime / 60);
                    const seconds = Math.floor(remainingTime % 60);
                    durationEl.textContent = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
                }
            }
            
            this.animationFrame = requestAnimationFrame(animate);
        };
        
        this.animationFrame = requestAnimationFrame(animate);
    }
    
    updateRemainingRoute(currentLat, currentLng) {
        if (!this.originalSimulationPoints.length) return;
        
        let closestIndex = 0;
        let minDistance = Infinity;
        
        for (let i = 0; i < this.originalSimulationPoints.length; i++) {
            const point = this.originalSimulationPoints[i];
            const distance = this.calculateDistance(currentLat, currentLng, point[0], point[1]);
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
            }
        }
        
        const remainingPoints = this.originalSimulationPoints.slice(closestIndex);
        
        if (this.routeLayer) this.map.removeLayer(this.routeLayer);
        if (remainingPoints.length >= 2) {
            this.routeLayer = L.polyline(remainingPoints, { color: '#3b82f6', weight: 5 }).addTo(this.map);
        }
    }
    
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    stopSimulation() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        this.isSimulating = false;
        
        if (this.routeLayer && this.originalSimulationPoints.length) {
            this.map.removeLayer(this.routeLayer);
            this.routeLayer = L.polyline(this.originalSimulationPoints, { color: '#3b82f6', weight: 5 }).addTo(this.map);
        }
        
        if (this.routeCard) this.routeCard.style.display = 'none';
        this.showToast('Simulation stopped', 'info');
    }
    
    showToast(message, type) {
        window.PyroNotify?.notify(message, type);
    }
    
    cleanup() {
        if (this.watchId) navigator.geolocation.clearWatch(this.watchId);
        if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    }
}

// Make it globally available
window.CentralMapService = CentralMapService;
