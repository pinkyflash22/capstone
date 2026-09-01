// resources/js/admin/dashboard.js

class Dashboard {
    constructor() {
        this.map = null;
        this.markers = [];
        // this.init();
    }

    // init() {
    //     this.initMap();
    //     this.initEventListeners();
    //     this.loadDashboardData();
    // }

    // initMap() {
    //     // Default center (Pangasinan coordinates)
    //     const defaultCenter = [16.0433, 120.3333];
        
    //     this.map = L.map('dashboardMap').setView(defaultCenter, 11);
        
    //     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    //         attribution: '&copy; OpenStreetMap contributors'
    //     }).addTo(this.map);
    // }

    initEventListeners() {
        // Refresh button
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // Modal close on outside click
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });

        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    loadDashboardData() {
        // Load stations
        this.loadStations();
        
        // Load recent incidents
        this.loadRecentIncidents();
    }

    loadStations() {
        fetch('/api/stations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.addStationMarkers(data.data);
                }
            })
            .catch(error => console.error('Error loading stations:', error));
    }

    addStationMarkers(stations) {
        stations.slice(0, 5).forEach(station => {
            const marker = L.marker([station.lat, station.lng]).addTo(this.map);
            
            marker.bindPopup(`
                <div style="padding: 8px;">
                    <strong style="color: #dc2626;">${station.name}</strong><br>
                    <span style="font-size: 12px;">${station.address}</span>
                </div>
            `);
            
            this.markers.push(marker);
        });
    }

    loadRecentIncidents() {
        // This would fetch from your API
        console.log('Loading recent incidents...');
    }

    refreshData() {
        this.showNotification('Refreshing dashboard data...', 'info');
        // Reload data
        this.loadDashboardData();
    }

    // Modal functions
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

    showNotification(message, type = 'info') {
        window.PyroNotify?.notify(message, type);
    }

    // Emergency call
    emergencyCall() {
        if (confirm('Call 911 for emergency services?')) {
            window.location.href = 'tel:911';
        }
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});

// Global functions for onclick handlers
function openModal(modalId) {
    if (window.dashboard) {
        window.dashboard.openModal(modalId);
    }
}

function closeModal(modalId) {
    if (window.dashboard) {
        window.dashboard.closeModal(modalId);
    }
}

function emergencyCall() {
    if (window.dashboard) {
        window.dashboard.emergencyCall();
    }
}
