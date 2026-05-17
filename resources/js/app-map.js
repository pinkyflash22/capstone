// resources/js/app-map.js

// Initialize map service when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if there's a map element on the page
    const mapElement = document.getElementById('incidentMap');
    
    if (mapElement && !window.mapService) {
        // Determine page type from body class or URL
        const bodyClass = document.body.className;
        const isAdmin = bodyClass.includes('admin') || window.location.pathname.includes('/admin');
        const isFirefighter = bodyClass.includes('firefighter') || window.location.pathname.includes('/firefighter');
        const isCitizen = bodyClass.includes('citizen') || window.location.pathname.includes('/citizen');
        
        // Initialize map service with appropriate options
        window.mapService = new MapService();
        window.mapService.init('incidentMap', {
            showUserLocation: true,
            showIncidents: !isAdmin, // Show incidents on all except maybe admin
            showStations: true,
            showActiveUsers: isAdmin // Show active users only on admin dashboard
        });
        
        // Make it globally accessible
        window.calculateRouteToStation = (id, name, lat, lng) => {
            window.mapService.calculateRouteToStation(id, name, lat, lng);
        };
        
        window.calculateRouteToIncident = (id) => {
            window.mapService.calculateRouteToIncident(id);
        };
        
        window.stopSimulation = () => {
            window.mapService.stopSimulation();
        };
        
        console.log('Map service initialized for:', { isAdmin, isFirefighter, isCitizen });
    }
});