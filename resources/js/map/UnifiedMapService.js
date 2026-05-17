// // resources/js/map/UnifiedMapService.js

// class UnifiedMapService {
//     constructor() {
//         this.map = null;
//         this.userLocation = null;
//         this.userMarker = null;
//         this.animatedVehicle = null;
//         this.vehicleInterval = null;
//         this.animationFrame = null;
//         this.simulationPoints = [];
//         this.isSimulating = false;
//         this.routeLayer = null;
//         this.originalSimulationPoints = [];
//         this.geoapifyKey = '83677a1086d44c4e82f903fd2fe2ea9b';
//         this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
//         // Get user role from meta tag
//         this.userRole = document.querySelector('meta[name="user-role"]')?.content || 'citizen';
//         this.userRoleId = document.querySelector('meta[name="user-role-id"]')?.content || 4;
//         this.userId = document.querySelector('meta[name="user-id"]')?.content || null;
        
//         // Store markers for updates
//         this.incidentMarkers = [];
//         this.stationMarkers = [];
//         this.userMarkers = [];
//         this.vehicleMarkers = [];
        
//         // Update intervals
//         this.updateInterval = null;
        
//         // Route info elements
//         this.routeCard = null;
        
//         console.log(`UnifiedMapService initialized for role: ${this.userRole} (ID: ${this.userRoleId})`);
//     }
    
//     setupOnExistingMap() {
//         // Use existing map instead of creating new one
//         this.map = window.map;
        
//         if (!this.map) {
//             console.error('No existing map found!');
//             return;
//         }
        
//         console.log('UnifiedMapService setting up on existing map');
        
//         // Add legend if not exists
//         this.addLegend();
        
//         // Initialize route card
//         this.initRouteCard();
        
//         // Get user location
//         this.getUserLocation();
        
//         // Start real-time updates
//         this.fetchIncidents();
//         this.fetchStations();
//         this.fetchActiveUsers();
//         this.fetchMovingVehicles();
//         this.startRealTimeUpdates();
        
//         console.log('UnifiedMapService setup complete');
//     }
    
//     addLegend() {
//         // Check if legend already exists
//         if (document.querySelector('.unified-legend')) return;
        
//         const legend = L.control({ position: 'bottomright' });
        
//         legend.onAdd = () => {
//             const div = L.DomUtil.create('div', 'info legend unified-legend');
//             div.style.background = 'white';
//             div.style.padding = '10px';
//             div.style.borderRadius = '8px';
//             div.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
//             div.style.fontSize = '12px';
//             div.innerHTML = `
//                 <div><strong>Legend</strong></div>
//                 <div><i class="fa-solid fa-circle" style="color: #3b82f6;"></i> Your Location</div>
//                 <div><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Active Incident</div>
//                 <div><i class="fa-solid fa-building" style="color: #22c55e;"></i> Fire Station</div>
//                 <div><i class="fa-solid fa-truck-fire" style="color: #dc2626;"></i> Moving Vehicle</div>
//                 <div><i class="fa-solid fa-user" style="color: #8b5cf6;"></i> Active Personnel</div>
//                 <div><i class="fa-solid fa-route" style="color: #3b82f6;"></i> Route</div>
//             `;
//             return div;
//         };
        
//         legend.addTo(this.map);
//     }
    
//     initRouteCard() {
//         this.routeCard = document.getElementById('routeInfoCard');
//         if (!this.routeCard) {
//             this.routeCard = document.createElement('div');
//             this.routeCard.id = 'routeInfoCard';
//             this.routeCard.innerHTML = `
//                 <h4><i class="fa-solid fa-route"></i> Route Information</h4>
//                 <div><strong>Destination:</strong> <span id="destinationTitle">---</span></div>
//                 <div><strong>Distance:</strong> <span id="routeDistance">0 km</span></div>
//                 <div><strong>ETA:</strong> <span id="routeETA">--:--</span></div>
//                 <div><strong>Duration:</strong> <span id="routeDuration">0 min</span></div>
//                 <div class="route-progress-container">
//                     <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
//                 </div>
//                 <div class="speed-control">
//                     <button class="speed-btn" data-speed="slow">🐢 Slow</button>
//                     <button class="speed-btn active" data-speed="normal">🚒 Normal</button>
//                     <button class="speed-btn" data-speed="fast">🐇 Fast</button>
//                 </div>
//                 <button class="stop-sim-btn" onclick="if(window.unifiedMap) window.unifiedMap.stopSimulation()">Stop Simulation</button>
//             `;
//             this.routeCard.style.cssText = `
//                 position: fixed;
//                 bottom: 20px;
//                 right: 20px;
//                 background: white;
//                 border-radius: 12px;
//                 padding: 15px;
//                 box-shadow: 0 4px 12px rgba(0,0,0,0.15);
//                 min-width: 250px;
//                 z-index: 1000;
//                 display: none;
//                 font-family: sans-serif;
//             `;
//             document.body.appendChild(this.routeCard);
            
//             this.routeCard.querySelectorAll('.speed-btn').forEach(btn => {
//                 btn.addEventListener('click', (e) => {
//                     this.routeCard.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
//                     btn.classList.add('active');
//                     if (this.isSimulating) this.startAnimation();
//                 });
//             });
//         }
//     }
    
//     getSpeedMultiplier() {
//         const activeBtn = this.routeCard?.querySelector('.speed-btn.active');
//         if (activeBtn) {
//             const speed = activeBtn.dataset.speed;
//             switch(speed) {
//                 case 'slow': return 0.5;
//                 case 'fast': return 2;
//                 default: return 1;
//             }
//         }
//         return 1;
//     }
    
//     getUserLocation() {
//         if (!navigator.geolocation) return;
        
//         navigator.geolocation.getCurrentPosition(
//             (position) => {
//                 this.userLocation = {
//                     lat: position.coords.latitude,
//                     lng: position.coords.longitude
//                 };
                
//                 // Check if user marker already exists from map.js
//                 if (window.userMarker) {
//                     window.userMarker.setLatLng([this.userLocation.lat, this.userLocation.lng]);
//                 } else {
//                     // Create user marker if not exists
//                     this.userMarker = L.marker([this.userLocation.lat, this.userLocation.lng], {
//                         icon: L.divIcon({
//                             className: 'user-marker',
//                             html: '<div style="background: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #3b82f6;"></div>',
//                             iconSize: [16, 16],
//                             iconAnchor: [8, 8]
//                         })
//                     }).addTo(this.map);
//                 }
                
//                 if (!this.map.getCenter().equals([this.userLocation.lat, this.userLocation.lng])) {
//                     this.map.setView([this.userLocation.lat, this.userLocation.lng], 14);
//                 }
//             },
//             (error) => console.error('Geolocation error:', error)
//         );
//     }
    
//     startRealTimeUpdates() {
//         // Update every 10 seconds
//         this.updateInterval = setInterval(() => {
//             this.fetchIncidents();
//             this.fetchActiveUsers();
//             this.fetchMovingVehicles();
//         }, 10000);
//     }
    
//     async fetchIncidents() {
//         try {
//             const response = await fetch('/api/incidents/active', {
//                 headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
//             });
//             const data = await response.json();
//             const incidents = data.data || data;
            
//             // Update incident markers with real-time data
//             incidents.forEach(incident => {
//                 if (incident.lat && incident.lng) {
//                     this.updateIncidentMarker(incident);
//                 }
//             });
//         } catch (error) {
//             console.error('Error fetching incidents:', error);
//         }
//     }
    
//     updateIncidentMarker(incident) {
//         // Check if marker exists in your existingMarkers
//         if (window.existingMarkers && window.existingMarkers[incident.marker_id]) {
//             const existingMarker = window.existingMarkers[incident.marker_id];
//             const newPopup = this.generateIncidentPopup(incident);
//             existingMarker.bindPopup(newPopup);
//         }
//     }
    
//     generateIncidentPopup(incident) {
//         const priorityColors = {
//             critical: '#ef4444',
//             high: '#f97316',
//             medium: '#eab308',
//             low: '#22c55e'
//         };
//         const color = priorityColors[incident.priority] || '#eab308';
        
//         let icon = '⚠️';
//         if (incident.incident_type === 'fire') icon = '🔥';
//         else if (incident.incident_type === 'drown') icon = '💧';
//         else if (incident.incident_type === 'treefall') icon = '🌳';
        
//         return `
//             <div style="padding: 8px; min-width: 220px;">
//                 <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
//                     <span style="font-size: 20px;">${icon}</span>
//                     <strong style="font-size: 16px; color: #1f2937;">${incident.title || `Incident #${incident.incident_id}`}</strong>
//                     <span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px;">${incident.priority || 'MEDIUM'}</span>
//                 </div>
//                 <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">${incident.description || 'No description'}</div>
//                 <div style="margin-top: 8px; font-size: 11px; color: #9ca3af;">
//                     <i class="fa-regular fa-clock"></i> ${new Date(incident.created_at).toLocaleString()}
//                 </div>
//                 <button onclick="if(window.unifiedMap) window.unifiedMap.calculateRouteToIncident(${incident.incident_id})" style="margin-top: 10px; padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%;">
//                     <i class="fa-solid fa-route"></i> Get Directions
//                 </button>
//             </div>
//         `;
//     }
    
//     async fetchStations() {
//         try {
//             const response = await fetch('/api/station', {
//                 headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
//             });
//             const data = await response.json();
//             const stations = data.data || data.stationData || data;
            
//             // Update station markers with real-time data
//             stations.forEach(station => {
//                 const lat = station.lat || station.marker?.lat;
//                 const lng = station.lng || station.marker?.lng;
//                 if (lat && lng) {
//                     this.updateStationMarker(station, lat, lng);
//                 }
//             });
//         } catch (error) {
//             console.error('Error fetching stations:', error);
//         }
//     }
    
//     updateStationMarker(station, lat, lng) {
//         // Find existing station marker by ID
//         if (window.stationMarkers && window.stationMarkers[station.station_id]) {
//             const existingMarker = window.stationMarkers[station.station_id];
//             const newPopup = this.generateStationPopup(station, lat, lng);
//             existingMarker.bindPopup(newPopup);
//         }
//     }
    
//     generateStationPopup(station, lat, lng) {
//         return `
//             <div style="padding: 10px; min-width: 220px;">
//                 <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">
//                     <span style="font-size: 20px;">🏢</span>
//                     <strong style="font-size: 14px; color: #1f2937;">${station.station_name}</strong>
//                 </div>
//                 <div style="margin-bottom: 4px;">
//                     <span style="font-size: 11px; color: #6b7280;">📋 Code:</span>
//                     <span style="font-size: 12px; margin-left: 6px;">${station.station_code || 'N/A'}</span>
//                 </div>
//                 <div style="margin-bottom: 4px;">
//                     <span style="font-size: 11px; color: #6b7280;">📞 Contact:</span>
//                     <span style="font-size: 12px; margin-left: 6px;">${station.contact_number || 'N/A'}</span>
//                 </div>
//                 <div style="margin-bottom: 4px;">
//                     <span style="font-size: 11px; color: #6b7280;">✉️ Email:</span>
//                     <span style="font-size: 11px; margin-left: 6px;">${station.email || 'N/A'}</span>
//                 </div>
//                 <button onclick="if(window.unifiedMap) window.unifiedMap.calculateRouteToStation(${station.station_id}, '${station.station_name}', ${lat}, ${lng})" style="margin-top: 8px; padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%;">
//                     <i class="fa-solid fa-route"></i> Get Directions
//                 </button>
//             </div>
//         `;
//     }
    
//     async fetchActiveUsers() {
//         try {
//             const response = await fetch('/api/active-users', {
//                 headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
//             });
//             const data = await response.json();
//             const users = data.data || data;
            
//             // Clear existing user markers
//             this.userMarkers.forEach(m => {
//                 if (this.map) this.map.removeLayer(m.marker);
//             });
//             this.userMarkers = [];
            
//             users.forEach(user => {
//                 if (user.lat && user.lng) {
//                     this.addUserMarker(user);
//                 }
//             });
//         } catch (error) {
//             console.error('Error fetching active users:', error);
//         }
//     }
    
//     addUserMarker(user) {
//         const marker = L.marker([user.lat, user.lng], {
//             icon: L.divIcon({
//                 className: 'user-marker',
//                 html: `<div style="background: #8b5cf6; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">👤</div>`,
//                 iconSize: [24, 24],
//                 popupAnchor: [0, -12]
//             })
//         }).addTo(this.map);
        
//         marker.bindPopup(`
//             <div style="padding: 8px;">
//                 <strong>${user.name || `User #${user.id}`}</strong>
//                 <div>Role: ${user.role || 'Firefighter'}</div>
//                 <div>Status: <span style="color: #22c55e;">●</span> ${user.status || 'Active'}</div>
//                 <div style="font-size: 11px; color: #6b7280;">Last seen: ${new Date(user.last_seen).toLocaleTimeString()}</div>
//             </div>
//         `);
        
//         this.userMarkers.push({ id: user.id, marker, user });
//     }
    
//     async fetchMovingVehicles() {
//         try {
//             const response = await fetch('/api/active-vehicles', {
//                 headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
//             });
//             const data = await response.json();
//             const vehicles = data.data || data;
            
//             // Clear existing vehicle markers
//             this.vehicleMarkers.forEach(m => {
//                 if (this.map) this.map.removeLayer(m.marker);
//             });
//             this.vehicleMarkers = [];
            
//             vehicles.forEach(vehicle => {
//                 if (vehicle.lat && vehicle.lng) {
//                     this.addVehicleMarker(vehicle);
//                 }
//             });
//         } catch (error) {
//             console.error('Error fetching vehicles:', error);
//         }
//     }
    
//     addVehicleMarker(vehicle) {
//         const marker = L.marker([vehicle.lat, vehicle.lng], {
//             icon: L.divIcon({
//                 className: 'vehicle-marker',
//                 html: `<div style="background: #dc2626; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; animation: pulse 1.5s infinite;">
//                             <i class="fa-solid fa-truck-fire"></i>
//                         </div>`,
//                 iconSize: [28, 28],
//                 popupAnchor: [0, -14]
//             })
//         }).addTo(this.map);
        
//         marker.bindPopup(`
//             <div style="padding: 8px;">
//                 <strong><i class="fa-solid fa-truck-fire"></i> ${vehicle.vehicle_name || 'Fire Truck'}</strong>
//                 <div>Speed: ${vehicle.speed || '0'} km/h</div>
//                 <div>Status: ${vehicle.status || 'Responding'}</div>
//                 <div>Destination: ${vehicle.destination || 'Incident'}</div>
//             </div>
//         `);
        
//         this.vehicleMarkers.push({ id: vehicle.vehicle_id, marker, vehicle });
//     }
    
//     createFireTruckIcon() {
//         return L.divIcon({
//             className: 'fire-truck-marker',
//             html: `
//                 <div style="position: relative; width: 40px; height: 40px;">
//                     <div style="background: #dc2626; width: 40px; height: 40px; border-radius: 8px; transform: rotate(45deg); box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>
//                     <i class="fa-solid fa-truck-fire" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 20px;"></i>
//                     <div style="position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; animation: blink 0.5s infinite;"></div>
//                 </div>
//             `,
//             iconSize: [40, 40],
//             iconAnchor: [20, 20],
//             popupAnchor: [0, -20]
//         });
//     }
    
//     async calculateRouteToStation(stationId, stationName, lat, lng) {
//         if (!this.userLocation) {
//             this.showToast('Getting your location...', 'info');
//             this.getUserLocation();
//             setTimeout(() => this.calculateRouteToStation(stationId, stationName, lat, lng), 2000);
//             return;
//         }
        
//         const destination = { lat, lng, title: stationName };
//         await this.calculateRoute(this.userLocation, destination);
//         this.startSimulation(destination);
//     }
    
//     async calculateRouteToIncident(incidentId) {
//         // Find incident from existing markers
//         let incident = null;
//         if (window.existingMarkers) {
//             for (const [id, marker] of Object.entries(window.existingMarkers)) {
//                 if (marker.incidentId == incidentId || id == incidentId) {
//                     incident = { lat: marker.getLatLng().lat, lng: marker.getLatLng().lng };
//                     break;
//                 }
//             }
//         }
        
//         if (!incident) {
//             this.showToast('Incident not found', 'error');
//             return;
//         }
        
//         if (!this.userLocation) {
//             this.showToast('Getting your location...', 'info');
//             this.getUserLocation();
//             setTimeout(() => this.calculateRouteToIncident(incidentId), 2000);
//             return;
//         }
        
//         const destination = {
//             lat: incident.lat,
//             lng: incident.lng,
//             title: `Incident #${incidentId}`
//         };
//         await this.calculateRoute(this.userLocation, destination);
//         this.startSimulation(destination);
//     }
    
//     async calculateRoute(origin, destination) {
//         const url = `https://api.geoapify.com/v1/routing?waypoints=${origin.lat},${origin.lng}|${destination.lat},${destination.lng}&mode=drive&apiKey=${this.geoapifyKey}`;
        
//         try {
//             this.showToast('Calculating route...', 'info');
//             const response = await fetch(url);
//             const data = await response.json();
            
//             if (data.features?.length) {
//                 this.displayRoute(data.features[0]);
//                 this.updateRouteInfo(data.features[0]);
//                 this.routeDestination = destination;
//                 this.showToast(`Route to ${destination.title} calculated`, 'success');
//             }
//         } catch (error) {
//             console.error('Route error:', error);
//             this.showToast('Failed to calculate route', 'error');
//         }
//     }
    
//     displayRoute(route) {
//         if (this.routeLayer) this.map.removeLayer(this.routeLayer);
        
//         const geometry = route.geometry;
//         let latlngs = [];
        
//         if (geometry.type === 'LineString') {
//             latlngs = geometry.coordinates.map(coord => [coord[1], coord[0]]);
//         } else if (geometry.type === 'MultiLineString') {
//             geometry.coordinates.forEach(segment => {
//                 latlngs.push(...segment.map(coord => [coord[1], coord[0]]));
//             });
//         }
        
//         if (latlngs.length) {
//             this.routeLayer = L.polyline(latlngs, { color: '#3b82f6', weight: 5 }).addTo(this.map);
//             this.map.fitBounds(this.routeLayer.getBounds(), { padding: [50, 50] });
//             this.simulationPoints = latlngs;
//             this.originalSimulationPoints = [...latlngs];
//         }
//     }
    
//     updateRouteInfo(route) {
//         const distance = (route.properties.distance / 1000).toFixed(1);
//         const time = Math.floor(route.properties.time / 60);
//         const hours = Math.floor(time / 60);
//         const minutes = time % 60;
        
//         const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
//         const eta = new Date(Date.now() + (route.properties.time * 1000));
        
//         const distanceEl = document.getElementById('routeDistance');
//         const durationEl = document.getElementById('routeDuration');
//         const etaEl = document.getElementById('routeETA');
//         const destTitle = document.getElementById('destinationTitle');
        
//         if (distanceEl) distanceEl.textContent = `${distance} km`;
//         if (durationEl) durationEl.textContent = durationText;
//         if (etaEl) etaEl.textContent = eta.toLocaleTimeString();
//         if (destTitle && this.routeDestination) destTitle.textContent = this.routeDestination.title;
        
//         if (this.routeCard) this.routeCard.style.display = 'block';
//     }
    
//     startSimulation(destination) {
//         if (!this.simulationPoints?.length) {
//             this.showToast('No route to simulate', 'error');
//             return;
//         }
        
//         if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
//         this.isSimulating = true;
        
//         if (!this.animatedVehicle || !this.animatedVehicle.marker) {
//             this.animatedVehicle = {
//                 marker: L.marker(this.simulationPoints[0], {
//                     icon: this.createFireTruckIcon(),
//                     zIndexOffset: 1000
//                 }).addTo(this.map)
//             };
//         } else {
//             this.animatedVehicle.marker.setLatLng(this.simulationPoints[0]);
//         }
        
//         this.animatedVehicle.marker.bindPopup(`🚒 En Route to ${destination.title}`);
//         this.startAnimation();
//         this.showToast('Simulation started', 'success');
//     }
    
//     startAnimation() {
//         const segments = [];
//         let totalDistance = 0;
        
//         for (let i = 0; i < this.simulationPoints.length - 1; i++) {
//             const p1 = this.simulationPoints[i];
//             const p2 = this.simulationPoints[i + 1];
//             const distance = this.calculateDistance(p1[0], p1[1], p2[0], p2[1]);
//             totalDistance += distance;
//             segments.push({
//                 start: p1, end: p2, distance: distance,
//                 cumulativeDistance: totalDistance,
//                 angle: Math.atan2(p2[1] - p1[1], p2[0] - p1[0]) * 180 / Math.PI
//             });
//         }
        
//         const speed = 13.89 * this.getSpeedMultiplier();
//         const totalDuration = totalDistance / speed;
//         const startTime = performance.now();
        
//         const animate = (currentTime) => {
//             const elapsed = (currentTime - startTime) / 1000;
//             let progress = Math.min(elapsed / totalDuration, 1);
            
//             if (progress >= 1) {
//                 this.stopSimulation();
//                 this.showToast('Arrived at destination!', 'success');
//                 return;
//             }
            
//             const distanceTraveled = progress * totalDistance;
//             let currentSegment = null;
//             let segmentStartDistance = 0;
            
//             for (let i = 0; i < segments.length; i++) {
//                 if (distanceTraveled <= segments[i].cumulativeDistance) {
//                     currentSegment = segments[i];
//                     segmentStartDistance = i === 0 ? 0 : segments[i-1].cumulativeDistance;
//                     break;
//                 }
//             }
            
//             if (currentSegment) {
//                 const segmentProgress = (distanceTraveled - segmentStartDistance) / currentSegment.distance;
//                 const lat = currentSegment.start[0] + (currentSegment.end[0] - currentSegment.start[0]) * segmentProgress;
//                 const lng = currentSegment.start[1] + (currentSegment.end[1] - currentSegment.start[1]) * segmentProgress;
                
//                 this.animatedVehicle.marker.setLatLng([lat, lng]);
//                 this.updateRemainingRoute(lat, lng);
                
//                 const remainingDistance = totalDistance - distanceTraveled;
//                 const remainingTime = remainingDistance / speed;
                
//                 const progressBar = document.getElementById('routeProgress');
//                 if (progressBar) progressBar.style.width = `${progress * 100}%`;
                
//                 const durationEl = document.getElementById('routeDuration');
//                 if (durationEl) {
//                     const minutes = Math.floor(remainingTime / 60);
//                     const seconds = Math.floor(remainingTime % 60);
//                     durationEl.textContent = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
//                 }
//             }
            
//             this.animationFrame = requestAnimationFrame(animate);
//         };
        
//         if (this.animationFrame) {
//             cancelAnimationFrame(this.animationFrame);
//         }
//         this.animationFrame = requestAnimationFrame(animate);
//     }
    
//     updateRemainingRoute(currentLat, currentLng) {
//         if (!this.originalSimulationPoints.length) return;
        
//         let closestIndex = 0;
//         let minDistance = Infinity;
        
//         for (let i = 0; i < this.originalSimulationPoints.length; i++) {
//             const point = this.originalSimulationPoints[i];
//             const distance = this.calculateDistance(currentLat, currentLng, point[0], point[1]);
//             if (distance < minDistance) {
//                 minDistance = distance;
//                 closestIndex = i;
//             }
//         }
        
//         const remainingPoints = this.originalSimulationPoints.slice(closestIndex);
        
//         if (this.routeLayer) this.map.removeLayer(this.routeLayer);
//         if (remainingPoints.length >= 2) {
//             this.routeLayer = L.polyline(remainingPoints, { color: '#3b82f6', weight: 5 }).addTo(this.map);
//         }
//     }
    
//     calculateDistance(lat1, lng1, lat2, lng2) {
//         const R = 6371000;
//         const dLat = (lat2 - lat1) * Math.PI / 180;
//         const dLng = (lng2 - lng1) * Math.PI / 180;
//         const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
//                   Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
//                   Math.sin(dLng/2) * Math.sin(dLng/2);
//         const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
//         return R * c;
//     }
    
//     stopSimulation() {
//         if (this.animationFrame) {
//             cancelAnimationFrame(this.animationFrame);
//             this.animationFrame = null;
//         }
//         this.isSimulating = false;
        
//         if (this.routeLayer && this.originalSimulationPoints.length) {
//             this.map.removeLayer(this.routeLayer);
//             this.routeLayer = L.polyline(this.originalSimulationPoints, { color: '#3b82f6', weight: 5 }).addTo(this.map);
//         }
        
//         if (this.routeCard) this.routeCard.style.display = 'none';
//         this.showToast('Simulation stopped', 'info');
//     }
    
//     showToast(message, type) {
//         const toast = document.createElement('div');
//         toast.textContent = message;
//         toast.style.cssText = `
//             position: fixed;
//             bottom: 20px;
//             left: 20px;
//             padding: 12px 24px;
//             background: ${type === 'error' ? '#ef4444' : '#22c55e'};
//             color: white;
//             border-radius: 8px;
//             z-index: 9999;
//             font-size: 14px;
//             font-weight: 500;
//         `;
//         document.body.appendChild(toast);
//         setTimeout(() => toast.remove(), 3000);
//     }
    
//     cleanup() {
//         if (this.updateInterval) clearInterval(this.updateInterval);
//         if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
//         if (this.vehicleInterval) clearInterval(this.vehicleInterval);
//     }
// }

// // Make it globally available
// window.UnifiedMapService = UnifiedMapService;