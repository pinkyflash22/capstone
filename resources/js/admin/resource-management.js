// resources/js/admin/resource-management.js

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== STATE MANAGEMENT =====
    const state = {
        stations: [],
        resources: {},
        currentStation: 'all',
        currentCategory: '',
        currentStatus: '',
        searchTerm: ''
    };

    // ===== DOM ELEMENTS =====
    const elements = {
        stationOverview: document.getElementById('stationOverview'),
        stationResources: document.getElementById('stationResources'),
        stationTabs: document.getElementById('stationTabs'),
        totalPersonnel: document.getElementById('totalPersonnel'),
        totalVehicles: document.getElementById('totalVehicles'),
        totalEquipment: document.getElementById('totalEquipment'),
        totalStations: document.getElementById('totalStations'),
        lowStockCount: document.getElementById('lowStockCount'),
        searchInput: document.getElementById('searchInput'),
        categoryFilter: document.getElementById('categoryFilter'),
        statusFilter: document.getElementById('statusFilter'),
        refreshBtn: document.getElementById('refreshBtn'),
        addResourceBtn: document.getElementById('addResourceBtn'),

        addEquipmentBtn: document.getElementById('addEquipmentBtn'),

        exportBtn: document.getElementById('exportBtn'),
        loadingState: document.getElementById('loadingState'),
        resourceModal: document.getElementById('resourceModal'),
        deleteModal: document.getElementById('deleteModal'),
        viewModal: document.getElementById('viewResourceModal'),
        resourceForm: document.getElementById('resourceForm'),
        resourceType: document.getElementById('resourceType'),
        stationId: document.getElementById('stationId')
    };

    // ===== INITIALIZATION =====
    function init() {
        loadSampleData();
        setupSidebar();
        bindEvents();
        render();
    }

    // ===== DATA LOADING =====
    function loadSampleData() {
        const stationary = Array.isArray(window.resourcesData?.activeStations)
            ? window.resourcesData.activeStations
            : (Array.isArray(window.stationary) ? window.stationary : []);
        state.stations = [];

        if (!stationary.length) {
            populateStationDropdown();
            return;
        }

        stationary.forEach(station=>{
            var count = 0;

            // window.personnelm.forEach(person =>{
            //      if(station.station_id === person.stationBelong){
            //     count++;
            // }
            // });
         
           state.stations.push({
            
                id: station.station_id,
                name: station.station_name,
                // code: station.station_code,
                // chief: station.station_code,
                address: station.marker?.lat !== undefined && station.marker?.lng !== undefined
                    ? `${station.marker.lat}, ${station.marker.lng}`
                    : (station.address?.street || 'Location not recorded'),
                personnel: count, vehicles: 8, equipment: 156
            
        });
        //    console.log(station.station_name);
        });
        state.resources = state.resources || {};

        // Populate station dropdown
        populateStationDropdown();
    }

    function populateStationDropdown() {
        if (!elements.stationId) return;
        
        let options = '<option value="">Select Station</option>';
        state.stations.forEach(station => {
            options += `<option value="${station.id}">${station.name}</option>`;
        });
        elements.stationId.innerHTML = options;
    }

    // ===== RENDERING =====
    function render() {
        updateStats();
        renderStationTabs();
        renderStationCards();
        renderStationResources();
    }

    function updateStats() {
        let totalPersonnel = 0;
        let totalVehicles = 0;
        let totalEquipment = 0;

        Object.values(state.resources).forEach(station => {
            totalPersonnel += station.person?.length || 0;
            totalVehicles += station.vehicles?.length || 0;
            totalEquipment += station.equipment?.length || 0;
        });

        if (elements.totalPersonnel) elements.totalPersonnel.textContent = totalPersonnel;
        if (elements.totalVehicles) elements.totalVehicles.textContent = totalVehicles;
        if (elements.totalEquipment) elements.totalEquipment.textContent = totalEquipment;
        if (elements.totalStations) elements.totalStations.textContent = state.stations.length;
    }

    function renderStationTabs() {
        if (!elements.stationTabs) return;
        
        let html = '<button class="tab-btn active" data-station="all"><i class="fa-solid fa-layer-group"></i>All Stations</button>';
        
        state.stations.forEach(station => {
            html += `<button class="tab-btn" data-station="${station.id}"><i class="fa-solid fa-building"></i>${station.name}</button>`;
        });
        
        elements.stationTabs.innerHTML = html;
        
        elements.stationTabs.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                elements.stationTabs.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                state.currentStation = this.dataset.station;
                filterAndRender();
            });
        });
    }

    function renderStationCards() {
        if (!elements.stationOverview) return;
        
        let html = '';
        state.stations.forEach(station => {
            html += `
                <div class="station-card" data-station-id="${station.id}">
                    <div class="station-card-header">
                        <div class="station-card-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="station-card-info">
                            <h3>${station.name}</h3>
                            <p>${station.address}</p>
                        </div>
                    </div>
                    <div class="station-card-stats">
                        <div class="station-stat">
                            <div class="stat-value">${station.personnel}</div>
                            <div class="stat-label">Personnel</div>
                        </div>
                        <div class="station-stat">
                            <div class="stat-value">${station.vehicles}</div>
                            <div class="stat-label">Vehicles</div>
                        </div>
                        <div class="station-stat">
                            <div class="stat-value">${station.equipment}</div>
                            <div class="stat-label">Equipment</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        elements.stationOverview.innerHTML = html;
        
        elements.stationOverview.querySelectorAll('.station-card').forEach(card => {
            card.addEventListener('click', function() {
                elements.stationOverview.querySelectorAll('.station-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                const stationId = this.dataset.stationId;
                document.getElementById(`station-${stationId}`)?.scrollIntoView({ behavior: 'smooth' });
            });
        });
    }

    function renderStationResources() {
        if (!elements.stationResources) return;
        
        let html = '';
        
        state.stations.forEach(station => {
            const resources = state.resources[station.id] || { personnel: [], vehicles: [], equipment: [] };
            
            html += `
                <div id="station-${station.id}" class="station-resource-card">
                    <div class="station-resource-header">
                        <div class="station-info-compact">
                            <div class="station-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <h3 class="station-name">${station.name}</h3>
                                <span class="station-address">${station.address}</span>
                            </div>
                        </div>
                        <div class="station-stats-compact">
                            <span class="stat-badge personnel-badge">
                                <i class="fa-solid fa-users"></i> ${station.personnel || 0}
                            </span>
                            <span class="stat-badge vehicle-badge">
                                <i class="fa-solid fa-truck"></i> ${resources.vehicles?.length || 0}
                            </span>
                            <span class="stat-badge equipment-badge">
                                <i class="fa-solid fa-kit-medical"></i> ${resources.equipment?.length || 0}
                            </span>
                        </div>
                    </div>
                    
                    <div class="resource-tabs">
                        <button class="resource-tab active" data-tab="personnel" data-station="${station.id}">Personnel</button>
                        <button class="resource-tab" data-tab="vehicles" data-station="${station.id}">Vehicles</button>
                        <button class="resource-tab" data-tab="equipment" data-station="${station.id}">Equipment</button>
                    </div>
                    
                    <div class="resource-list-container">
                        <div class="resource-list personnel-list active">
                            ${renderResourceItems('personnel', resources.personnel || [])}
                        </div>
                        <div class="resource-list vehicles-list">
                            ${renderResourceItems('vehicle', resources.vehicles || [])}
                        </div>
                        <div class="resource-list equipment-list">
                            ${renderResourceItems('equipment', resources.equipment || [])}
                        </div>
                    </div>
                </div>
            `;
        });
        
        elements.stationResources.innerHTML = html;
        attachResourceTabEvents();
        attachResourceItemEvents();
    }

    function renderResourceItems(type, items) {
        if (!items || items.length === 0) {
            return `
                <div class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>No ${type} found</p>
                </div>
            `;
        }

        return items.map(item => createResourceItemHtml(type, item)).join('');
    }

    function createResourceItemHtml(type, item) {
        const icons = {
            personnel: 'fa-solid fa-user',
            vehicle: 'fa-solid fa-truck',
            equipment: 'fa-solid fa-kit-medical'
        };

        const titles = {
            personnel: item.name,
            vehicle: item.type,
            equipment: item.name
        };

        const subtitles = {
            personnel: item.rank,
            vehicle: item.plate,
            equipment: item.category
        };

        let detailsHtml = '';
        
        if (type === 'personnel') {
            detailsHtml = `
                <p><i class="fa-solid fa-phone"></i> ${item.contact || 'N/A'}</p>
                <p><i class="fa-solid fa-certificate"></i> ${item.specialization || 'General'}</p>
            `;
        } else if (type === 'vehicle') {
            detailsHtml = `
                <p><i class="fa-solid fa-gas-pump"></i> Fuel: ${item.fuel || 'N/A'}</p>
            `;
        } else if (type === 'equipment') {
            detailsHtml = `
                <p><i class="fa-solid fa-hashtag"></i> SN: ${item.serial || 'N/A'}</p>
                <p><i class="fa-solid fa-calendar"></i> Last Maint: ${item.lastMaintenance || 'N/A'}</p>
            `;
        }

        return `
            <div class="resource-item" data-resource-id="${item.id}" data-resource-type="${type}">
                <div class="resource-item-header">
                    <div class="resource-item-title">
                        <div class="resource-icon">
                            <i class="${icons[type]}"></i>
                        </div>
                        <div>
                            <h4 class="resource-name">${titles[type]}</h4>
                            <span class="resource-subtitle">${subtitles[type]}</span>
                        </div>
                    </div>
                    <span class="resource-status-badge status-${item.status.replace(/\s+/g, '-')}">${item.status}</span>
                </div>
                <div class="resource-item-details">
                    ${detailsHtml}
                </div>
                <div class="resource-item-actions">
                    <button class="action-btn view-resource" title="View" data-id="${item.id}" data-type="${type}">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="action-btn edit-resource" title="Edit" data-id="${item.id}" data-type="${type}">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="action-btn delete-resource" title="Delete" data-id="${item.id}" data-type="${type}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    // ===== EVENT HANDLERS =====
    function bindEvents() {
        // Search and filters
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', handleSearch);
        }
        
        if (elements.categoryFilter) {
            elements.categoryFilter.addEventListener('change', handleFilterChange);
        }
        
        if (elements.statusFilter) {
            elements.statusFilter.addEventListener('change', handleFilterChange);
        }
        
        if (elements.refreshBtn) {
            elements.refreshBtn.addEventListener('click', handleRefresh);
        }
        
        if (elements.addResourceBtn) {
            elements.addResourceBtn.addEventListener('click', () => openResourceModal('add'));
        }
          if (elements.addEquipmentBtn) {
            elements.addEquipmentBtn.addEventListener('click', () => openEquipmentModal('add'));
        }
        
        if (elements.exportBtn) {
            elements.exportBtn.addEventListener('click', handleExport);
        }
        
        if (elements.resourceType) {
            elements.resourceType.addEventListener('change', handleResourceTypeChange);
        }
        
        // Form buttons
        const saveBtn = document.getElementById('saveResourceBtn');
        if (saveBtn) saveBtn.addEventListener('click', handleSaveResource);
        
        const cancelBtn = document.getElementById('cancelResourceBtn');
        if (cancelBtn) cancelBtn.addEventListener('click', () => closeModal('resourceModal'));
        
        const resourceClose = document.getElementById('resourceModalClose');
        if (resourceClose) resourceClose.addEventListener('click', () => closeModal('resourceModal'));
        
         const cancelEBtn = document.getElementById('cancelEquipmentBtn');
        if (cancelEBtn) cancelEBtn.addEventListener('click', () => closeModal('equipmentModal'));
      
        const equipmentClose = document.getElementById('equipmentModalClose');
        if (equipmentClose) equipmentClose.addEventListener('click', () => closeModal('equipmentModal'));
        // Delete modal
        const cancelDelete = document.getElementById('cancelDeleteBtn');
        if (cancelDelete) cancelDelete.addEventListener('click', () => closeModal('deleteModal'));
        
        const deleteClose = document.getElementById('deleteModalClose');
        if (deleteClose) deleteClose.addEventListener('click', () => closeModal('deleteModal'));
        
        const confirmDelete = document.getElementById('confirmDeleteBtn');
        if (confirmDelete) confirmDelete.addEventListener('click', handleDeleteResource);
        
        // View modal
        const closeView = document.getElementById('closeViewBtn');
        if (closeView) closeView.addEventListener('click', () => closeModal('viewResourceModal'));
        
        const viewClose = document.getElementById('viewModalClose');
        if (viewClose) viewClose.addEventListener('click', () => closeModal('viewResourceModal'));
        
        const editFromView = document.getElementById('editFromViewBtn');
        if (editFromView) {
            editFromView.addEventListener('click', function() {
                const resourceId = document.getElementById('deleteResourceId')?.value;
                if (resourceId) {
                    closeModal('viewResourceModal');
                    editResource(resourceId);
                }
            });
        }
    }

    function attachResourceTabEvents() {
        document.querySelectorAll('.resource-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const stationCard = this.closest('.station-resource-card');
                stationCard.querySelectorAll('.resource-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const tabType = this.dataset.tab;
                stationCard.querySelectorAll('.resource-list').forEach(list => {
                    list.classList.remove('active');
                });
                stationCard.querySelector(`.${tabType}-list`).classList.add('active');
            });
        });
    }

    function attachResourceItemEvents() {
        document.querySelectorAll('.view-resource').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const type = btn.dataset.type;
                viewResource(id, type);
            });
        });

        document.querySelectorAll('.edit-resource').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                editResource(id);
            });
        });

        document.querySelectorAll('.delete-resource').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                showDeleteModal(id);
            });
        });
    }

    function handleSearch(e) {
        state.searchTerm = e.target.value.toLowerCase();
        filterAndRender();
    }

    function handleFilterChange() {
        state.currentCategory = elements.categoryFilter?.value || '';
        state.currentStatus = elements.statusFilter?.value || '';
        filterAndRender();
    }

    function handleRefresh() {
        showLoading();
        setTimeout(() => {
            loadSampleData();
            render();
            hideLoading();
            showToast('Data refreshed successfully', 'success');
        }, 500);
    }

    function handleExport() {
        const data = JSON.stringify(state, null, 2);
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `resource-management-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
        showToast('Data exported successfully', 'success');
    }

    function handleResourceTypeChange(e) {
        const selectedType = e.target.value;
        document.querySelectorAll('.resource-fields').forEach(field => {
            field.style.display = 'none';
        });
        if (selectedType) {
            const fieldset = document.getElementById(selectedType + 'Fields');
            if (fieldset) fieldset.style.display = 'block';
        }
    }

    function handleSaveResource() {
        showToast('Resource saved successfully', 'success');
        closeModal('resourceModal');
    }

    function handleDeleteResource() {
        const id = document.getElementById('deleteResourceId')?.value;
        if (id) {
            showToast('Resource deleted successfully', 'success');
            closeModal('deleteModal');
        }
    }

    // ===== MODAL FUNCTIONS =====
    function openResourceModal(mode, resourceId = null) {
        const title = document.getElementById('resourceModalTitle');
        if (title) {
            title.textContent = mode === 'add' ? 'Add New Resource' : 'Edit Resource';
        }
        
        if (mode === 'add') {
            document.getElementById('resourceForm')?.reset();
            document.getElementById('resourceId').value = '';
        }
        
        openModal('resourceModal');
    }

    function openEquipmentModal(mode, resourceId = null) {
        const title = document.getElementById('equipmentModalTitle');
        if (title) {
            title.textContent = mode === 'add' ? 'Add New Category' : 'Edit Category';
        }
        
        if (mode === 'add') {
            document.getElementById('equipmentForm')?.reset();
            // document.getElementById('resourceId').value = '';
        }
        
        openModal('equipmentModal');
    }

    function viewResource(id, type) {
        let resourceData = null;
        for (const stationId in state.resources) {
            const station = state.resources[stationId];
            const list = station[type + 's'] || station[type] || [];
            resourceData = list.find(item => item.id == id);
            if (resourceData) break;
        }

        if (!resourceData) {
            showToast('Resource not found', 'error');
            return;
        }

        const content = document.getElementById('viewResourceContent');
        if (content) {
            let html = '<div class="detail-container">';
            
            if (type === 'personnel') {
                html += `
                    <h2>${resourceData.name}</h2>
                    <p><strong>Rank:</strong> ${resourceData.rank}</p>
                    <p><strong>Contact:</strong> ${resourceData.contact}</p>
                    <p><strong>Specialization:</strong> ${resourceData.specialization}</p>
                    <p><strong>Status:</strong> ${resourceData.status}</p>
                `;
            } else if (type === 'vehicle') {
                html += `
                    <h2>${resourceData.type}</h2>
                    <p><strong>License Plate:</strong> ${resourceData.plate}</p>
                    <p><strong>Fuel Level:</strong> ${resourceData.fuel}</p>
                    <p><strong>Status:</strong> ${resourceData.status}</p>
                `;
            } else if (type === 'equipment') {
                html += `
                    <h2>${resourceData.name}</h2>
                    <p><strong>Category:</strong> ${resourceData.category}</p>
                    <p><strong>Serial Number:</strong> ${resourceData.serial}</p>
                    <p><strong>Last Maintenance:</strong> ${resourceData.lastMaintenance}</p>
                    <p><strong>Status:</strong> ${resourceData.status}</p>
                `;
            }
            
            html += '</div>';
            content.innerHTML = html;
        }

        const deleteId = document.getElementById('deleteResourceId');
        if (deleteId) deleteId.value = id;
        
        openModal('viewResourceModal');
    }

    function editResource(id) {
        openResourceModal('edit', id);
    }

    function showDeleteModal(id) {
        const deleteId = document.getElementById('deleteResourceId');
        if (deleteId) deleteId.value = id;
        openModal('deleteModal');
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // ===== UTILITY FUNCTIONS =====
    function filterAndRender() {
        // Implement filtering logic if needed
        render();
    }

    function showLoading() {
        if (elements.loadingState) {
            elements.loadingState.style.display = 'block';
        }
    }

    function hideLoading() {
        if (elements.loadingState) {
            elements.loadingState.style.display = 'none';
        }
    }

    function showToast(message, type = 'info') {
        window.PyroNotify?.notify(message, type);
    }

    function setupSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (openBtn) {
            openBtn.addEventListener('click', () => {
                sidebar?.classList.add('open');
                backdrop?.classList.remove('hidden');
                document.body.classList.add('menu-open');
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                sidebar?.classList.remove('open');
                backdrop?.classList.add('hidden');
                document.body.classList.remove('menu-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                sidebar?.classList.remove('open');
                backdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
            });
        }
    }

   document.getElementById('eform').addEventListener('submit', function(e){
    e.preventDefault(); // stop reload

    let formData = new FormData(this);

    fetch('/admin/equipment', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            showToast('Equipment added!', 'success');

            document.getElementById('emodal').style.display = 'none';
            this.reset();
        } else {
            showToast('Failed!', 'error');
        }
    })
    .catch(() => showToast('Error occurred', 'error'));
});
//    document.getElementById('connectionForm').addEventListener('submit', function(e){
//     e.preventDefault(); // stop reload

//     let formData = new FormData(this);

//     fetch('/api/connection', {
//         method: 'POST',
//         body: formData,
//         headers: {
//             'Accept': 'application/json',
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
//         }
//     })
//     .then(res => res.json())
//     .then(data => {
//         if(data.success){
//             showToast('Equipment added!', 'success');

//             document.getElementById('emodal').style.display = 'none';
//             this.reset();
//         } else {
//             showToast('Failed!', 'error');
//         }
//     })
//     .catch(() => showToast('Error occurred', 'error'));
// });
// connectionForm
    // Start the application
    init();
});
