// Add this class at the beginning of your map.js file or in a separate file
class NearestLocationFinder {
    constructor() {
        this.openRouterKey = 'YOUR_OPENROUTER_API_KEY_HERE'; // Replace with your actual key
        this.map = window.map;
        this.stations = [];
        this.hydrants = [];
        this.resources = [];
    }

    // Initialize by collecting all locations from existing markers
    collectLocations() {
        // Reset arrays
        this.stations = [];
        this.hydrants = [];
        this.resources = [];

        // Loop through existingMarkers to categorize them
        Object.keys(existingMarkers).forEach(markerId => {
            const marker = existingMarkers[markerId];
            // We need to get the category from somewhere - you might need to store this
            // For now, we'll assume you have a way to get the category
            const category = this.getMarkerCategory(markerId);
            
            const location = {
                id: markerId,
                lat: marker.getLatLng().lat,
                lng: marker.getLatLng().lng,
                marker: marker
            };

            if (category === 1) { // Station
                this.stations.push(location);
            } else if (category === 2 || category === 4) { // Hydrant or resources
                this.hydrants.push(location);
                this.resources.push(location);
            }
        });
    }

    // Helper to get marker category - you need to implement this based on your data structure
    getMarkerCategory(markerId) {
        // This depends on how you store category data
        // You might need to fetch from your database or maintain a separate mapping
        // For now, return a default
        return 1; // Placeholder - implement based on your actual data
    }

    // Haversine formula for straight-line distance
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Find nearest by straight-line distance (fast, less accurate)
    findNearestLinear(userLat, userLng, type = 'station') {
        this.collectLocations();
        
        let locations;
        switch(type) {
            case 'station':
                locations = this.stations;
                break;
            case 'hydrant':
                locations = this.hydrants;
                break;
            case 'resource':
                locations = this.resources;
                break;
            default:
                locations = [...this.stations, ...this.hydrants, ...this.resources];
        }

        if (locations.length === 0) {
            return { error: `No ${type}s found` };
        }

        // Calculate distances and sort
        const withDistance = locations.map(loc => ({
            ...loc,
            distance: this.calculateDistance(userLat, userLng, loc.lat, loc.lng)
        }));

        withDistance.sort((a, b) => a.distance - b.distance);
        
        return {
            nearest: withDistance[0],
            all: withDistance.slice(0, 5) // Top 5 nearest
        };
    }

    // Use OpenRouter to get intelligent recommendations
    async findNearestWithAI(userLat, userLng, query, type = 'station') {
        try {
            // First get linear distances as fallback
            const linearResults = this.findNearestLinear(userLat, userLng, type);
            
            if (linearResults.error) {
                return linearResults;
            }

            // Prepare context for AI
            const locations = linearResults.all.map(loc => ({
                id: loc.id,
                distance: loc.distance.toFixed(2) + ' km',
                coordinates: `${loc.lat}, ${loc.lng}`
            }));

            const prompt = `
You are a fire emergency response AI assistant. A user needs to find the nearest ${type} at coordinates (${userLat}, ${userLng}).

Here are the nearest locations with their straight-line distances:
${JSON.stringify(locations, null, 2)}

Based on this data:
1. Identify the absolute nearest ${type}
2. Provide a brief recommendation on which location would be best to respond to
3. If there are multiple options close by, suggest which might be most accessible

Respond in JSON format with these fields:
- nearest_id: the ID of the nearest location
- distance: the distance in km
- reasoning: brief explanation of why this is the best choice
- alternatives: array of other nearby locations with distances
            `;

            // Call OpenRouter API
            const response = await fetch('https://openrouter.ai/api/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.openRouterKey}`,
                    'Content-Type': 'application/json',
                    'HTTP-Referer': window.location.origin, // Optional, for rankings
                    'X-Title': 'PyroSense Emergency System'
                },
                body: JSON.stringify({
                    model: 'deepseek/deepseek-r1-distill-qwen-32b', // or any other model
                    messages: [
                        {
                            role: 'system',
                            content: 'You are an emergency response AI assistant that helps find the nearest fire stations, hydrants, and resources. Always respond in valid JSON format.'
                        },
                        {
                            role: 'user',
                            content: prompt
                        }
                    ],
                    temperature: 0.3,
                    max_tokens: 500
                })
            });

            const data = await response.json();
            const aiResponse = JSON.parse(data.choices[0].message.content);
            
            // Find the actual marker data
            const nearestLocation = linearResults.all.find(l => l.id == aiResponse.nearest_id) || linearResults.nearest;
            
            return {
                ...aiResponse,
                nearest: nearestLocation,
                all: linearResults.all
            };

        } catch (error) {
            console.error('AI recommendation error:', error);
            // Fallback to linear calculation if AI fails
            return {
                ...this.findNearestLinear(userLat, userLng, type),
                ai_failed: true,
                error: error.message
            };
        }
    }

    // Find nearest using actual road routes (most accurate)
    async findNearestWithRoute(userLat, userLng, type = 'station') {
        this.collectLocations();
        
        let locations;
        switch(type) {
            case 'station':
                locations = this.stations;
                break;
            case 'hydrant':
                locations = this.hydrants;
                break;
            case 'resource':
                locations = this.resources;
                break;
            default:
                locations = [...this.stations, ...this.hydrants, ...this.resources];
        }

        if (locations.length === 0) {
            return { error: `No ${type}s found` };
        }

        // Calculate actual road distances using OpenRouteService
        const withRouteInfo = await Promise.all(
            locations.slice(0, 5).map(async (loc) => {
                try {
                    const route = await this.getRouteDistance(
                        { lat: userLat, lng: userLng },
                        { lat: loc.lat, lng: loc.lng }
                    );
                    return {
                        ...loc,
                        distance: route.distance / 1000, // Convert to km
                        duration: Math.round(route.duration / 60), // Minutes
                        route: route.geometry
                    };
                } catch (error) {
                    // Fallback to linear distance if routing fails
                    return {
                        ...loc,
                        distance: this.calculateDistance(userLat, userLng, loc.lat, loc.lng),
                        duration: null,
                        route: null
                    };
                }
            })
        );

        withRouteInfo.sort((a, b) => a.distance - b.distance);
        
        return {
            nearest: withRouteInfo[0],
            all: withRouteInfo
        };
    }

    // Get actual route distance using OpenRouteService
    async getRouteDistance(start, end) {
        const orsApiKey = 'YOUR_OPENROUTESERVICE_API_KEY'; // You'll need to sign up for this
        
        const response = await fetch(
            `https://api.openrouteservice.org/v2/directions/driving-car?` +
            `api_key=${orsApiKey}&` +
            `start=${start.lng},${start.lat}&` +
            `end=${end.lng},${end.lat}`
        );
        
        const data = await response.json();
        return {
            distance: data.features[0].properties.summary.distance,
            duration: data.features[0].properties.summary.duration,
            geometry: data.features[0].geometry
        };
    }

    // Draw route on map to the selected location
    drawRouteToLocation(targetLat, targetLng, color = '#3b82f6') {
        if (window.routeLayer) {
            window.map.removeLayer(window.routeLayer);
        }

        // Get user's current location (you might want to use a stored location)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;

                    try {
                        const route = await this.getRouteDistance(
                            { lat: userLat, lng: userLng },
                            { lat: targetLat, lng: targetLng }
                        );

                        window.routeLayer = L.geoJSON(route.geometry, { 
                            color: color, 
                            weight: 5,
                            opacity: 0.7
                        }).addTo(window.map);

                        // Fit map to show the entire route
                        window.map.fitBounds(window.routeLayer.getBounds());
                    } catch (error) {
                        console.error('Route drawing error:', error);
                    }
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    alert('Unable to get your location');
                }
            );
        }
    }
}

// Initialize the finder
window.locationFinder = new NearestLocationFinder();

// Usage functions you can call from your HTML
window.findNearestStation = async function(userLat, userLng) {
    const result = await window.locationFinder.findNearestWithAI(userLat, userLng, 'station');
    displayNearestResult(result, 'Station');
    return result;
};

window.findNearestHydrant = async function(userLat, userLng) {
    const result = await window.locationFinder.findNearestWithAI(userLat, userLng, 'hydrant');
    displayNearestResult(result, 'Hydrant');
    return result;
};

window.findNearestResource = async function(userLat, userLng) {
    const result = await window.locationFinder.findNearestWithAI(userLat, userLng, 'resource');
    displayNearestResult(result, 'Resource');
    return result;
};

// Helper to display results
function displayNearestResult(result, type) {
    if (result.error) {
        alert(`Error: ${result.error}`);
        return;
    }

    const nearest = result.nearest;
    const message = `
${type} Found!
Location ID: ${nearest.id}
Distance: ${nearest.distance.toFixed(2)} km
${result.reasoning ? `\nAI Reasoning: ${result.reasoning}` : ''}
    `;

    // You can customize this to show a modal or toast
    alert(message);

    // Optional: Draw route to the location
    if (confirm('Show route on map?')) {
        window.locationFinder.drawRouteToLocation(nearest.lat, nearest.lng);
    }
}

// Add a button to use current location
window.findNearestFromMyLocation = function(type = 'station') {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                // Show loading
                const loadingMsg = L.popup()
                    .setLatLng([userLat, userLng])
                    .setContent('Finding nearest location...')
                    .openOn(window.map);

                const result = await window.locationFinder.findNearestWithAI(userLat, userLng, type);
                
                // Remove loading popup
                window.map.closePopup();
                
                displayNearestResult(result, type);
            },
            (error) => {
                console.error('Geolocation error:', error);
                alert('Unable to get your location. Please enable location services.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
    }
};