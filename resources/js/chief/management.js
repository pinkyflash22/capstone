// resources/js/admin/management.js

class ManagementPage {
    constructor() {
        this.currentTab = 'incidents';
        this.currentPage = 1;
        this.perPage = 10;
        this.searchTerm = '';
        this.filters = {};
        this.sortBy = 'created_at';
        this.sortOrder = 'desc';
        this.currentEditId = null;
        this.currentDeleteId = null;
        this.currentItem = null;
        this.data = {
            incidents: window.incidentDb || [],
            reports: window.reportDb || [],
            personnel: window.personnelDb || [],
            equipment: window.equipmentDb || []
        };
        this.rolesData ={
            allRoles: window.rolesData || [],
        };
        this.managedBarangays = window.managedBarangays || [];
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken;
        this.nextId = {
            reports: 100,
            events: 100,
            trainings: 100
        };
        this.init();
    }

    init() {
        this.removeUnsupportedTabs();
        this.initEventListeners();
        this.loadData();
        this.initTabs();
        this.initSearch();
        this.initModals();
        this.initStationEditor();
        this.setupActionListeners();
        this.initSorting();
        
        document.dispatchEvent(new Event('management:ready'));
    }

    removeUnsupportedTabs() {
        const supportedTabs = ['incidents', 'reports', 'personnel', 'equipment'];
        document.querySelectorAll('.tab-btn').forEach((btn) => {
            if (!supportedTabs.includes(btn.dataset.tab)) {
                btn.remove();
            }
        });
    }

    initEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e));
        });

        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.searchTerm = e.target.value;
                this.currentPage = 1;
                this.renderTable();
            }, 300));
        }

        // Filter select
        const filterSelect = document.getElementById('filterSelect');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.currentPage = 1;
                this.renderTable();
            });
        }

        // Date filter
        const dateFilter = document.getElementById('dateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.filters.date = e.target.value;
                this.currentPage = 1;
                this.renderTable();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportData());
        }

        // Add new button
        const addNewBtn = document.getElementById('addNewBtn');
        if (addNewBtn) {
            addNewBtn.addEventListener('click', () => this.openAddModal());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });
    }
    
    initSorting() {
        // Sorting is handled in renderTableHeader, but we need to re-attach after each render
    }

    getSortFieldFromHeader(headerText) {
        const fieldMap = {
            'ID': 'id',
            'Report ID': 'id',
            'Title': 'title',
            'Report': 'title',
            'Name': 'name',
            'Equipment': 'equipment_name',
            'Type': 'type',
            'Stage': 'type',
            'Incident': 'incident_title',
            'Investigator': 'author',
            'Status': 'status',
            'Date': 'date',
            'Filed': 'date',
            'Reported By': 'reported_by',
            'Author': 'author',
            'Location': 'location',
            'Available': 'available_quantity',
            'Maintenance': 'maintenance_quantity',
            'Damaged': 'damaged_quantity'
        };
        return fieldMap[headerText] || null;
    }

    setupActionListeners() {
        const tableBody = document.getElementById('tableBody');
        if (tableBody) {
            tableBody.addEventListener('click', async (e) => {
                const actionBtn = e.target.closest('.action-btn');
                if (!actionBtn) return;

                e.preventDefault();
                
                const row = actionBtn.closest('tr');
                const id = row?.dataset?.id;
                
                if (!id) return;

                if (actionBtn.classList.contains('view')) {
                    await this.viewItem(id);
                } else if (actionBtn.classList.contains('edit')) {
                    this.editItem(id);
                } else if (actionBtn.classList.contains('delete')) {
                    this.deleteItem(id);
                }
            });
        }
    }

    initTabs() {
        const firstTab = document.querySelector('.tab-btn');
        if (firstTab) {
            firstTab.classList.add('active');
        }
        this.updateTableTitle();
    }

    initSearch() {
        this.updateSearchPlaceholder();
    }

    initModals() {
        // View modal
        document.getElementById('viewModalClose')?.addEventListener('click', () => {
            this.closeModal('viewModal');
        });

        // Add/Edit modal
        document.getElementById('formModalClose')?.addEventListener('click', () => {
            this.closeModal('formModal');
        });

        document.getElementById('cancelFormBtn')?.addEventListener('click', () => {
            this.closeModal('formModal');
        });

        document.getElementById('saveFormBtn')?.addEventListener('click', () => {
            this.saveFormData();
        });

        // Delete modal
        document.getElementById('deleteModalClose')?.addEventListener('click', () => {
            this.closeModal('deleteModal');
        });

        document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => {
            this.closeModal('deleteModal');
        });

        document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
            this.confirmDelete();
        });
    }

    initStationEditor() {
        const editStationBtn = document.getElementById('editStationBtn');
        const editStationModal = document.getElementById('editStationModal');
        const closeEditStationBtn = document.getElementById('editStationModalClose');
        const cancelEditStationBtn = document.getElementById('cancelEditStationBtn');
        const editStationForm = document.getElementById('editStationForm');

        if (!editStationBtn || !editStationModal || !editStationForm) {
            return;
        }

        editStationBtn.addEventListener('click', () => {
            editStationModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeEditStationBtn?.addEventListener('click', () => this.closeModal('editStationModal'));
        cancelEditStationBtn?.addEventListener('click', () => this.closeModal('editStationModal'));

        editStationForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const stationId = document.getElementById('editStationId')?.value;
            const payload = {
                station_name: document.getElementById('editStationName')?.value?.trim() || '',
                station_code: document.getElementById('editStationCode')?.value?.trim() || '',
                contact_number: document.getElementById('editStationContactNumber')?.value?.trim() || '',
                email: document.getElementById('editStationEmail')?.value?.trim() || '',
            };

            try {
                const response = await fetch(`/station-admin/stations/${stationId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    const message = data?.message || 'Failed to update station information.';
                    throw new Error(message);
                }

                window.currentStationData = data.data || window.currentStationData;
                this.showToast(data.message || 'Station information updated successfully.', 'success');
                this.closeModal('editStationModal');
                window.location.reload();
            } catch (error) {
                console.error('Error updating station:', error);
                this.showToast(error.message || 'Failed to update station information.', 'error');
            }
        });
    }

    async loadData() {
        this.showLoading(true);
        
        try {
            const [incidents, reports, personnel, equipment] = await Promise.all([
                this.fetchData(`/api/managements/incidents`),
                this.fetchData(`/api/managements/reports`),
                this.fetchData(`/api/managements/personnel`),
                this.fetchData(`/api/managements/equipment`)
            ]);

            this.data = {
                incidents: incidents.length ? incidents : (window.incidentDb || []),
                reports: reports.length ? reports : (window.reportDb || []),
                personnel: personnel.length ? personnel : (window.personnelDb || []),
                equipment: equipment.length ? equipment : (window.equipmentDb || [])
            };

            this.updateStats();
            this.renderTable();
            
        } catch (error) {
            console.error('Error loading data:', error);
            this.showToast('Failed to load data', 'error');
            this.renderTable();
        } finally {
            this.showLoading(false);
        }
    }

    async fetchData(url) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            return data.data || data;
        } catch (error) {
            console.error(`Error fetching ${url}:`, error);
            return [];
        }
    }

    updateStats() {
        const stats = {
            totalIncidents: this.data.incidents.length,
            activeIncidents: this.data.incidents.filter((incident) => {
                const status = this.getIncidentStatusMeta(incident).value;
                return ['reported', 'responding', 'active'].includes(status);
            }).length,
            totalReports: this.data.reports.length,
            pendingReports: this.data.reports.filter(r => ['filed', 'conducting'].includes(r.status)).length,
            totalPersonnel: this.data.personnel.length,
            totalEquipment: this.data.equipment.length
        };

        document.getElementById('totalIncidents') && (document.getElementById('totalIncidents').textContent = stats.totalIncidents);
        document.getElementById('activeIncidents') && (document.getElementById('activeIncidents').textContent = `${stats.activeIncidents} active`);
        document.getElementById('totalReports') && (document.getElementById('totalReports').textContent = stats.totalReports);
        document.getElementById('pendingReports') && (document.getElementById('pendingReports').textContent = `${stats.pendingReports} open`);
        document.getElementById('totalPersonnel') && (document.getElementById('totalPersonnel').textContent = stats.totalPersonnel);
        document.getElementById('totalEquipment') && (document.getElementById('totalEquipment').textContent = stats.totalEquipment);
    }

    switchTab(event) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.currentTarget.classList.add('active');

        this.currentTab = event.currentTarget.dataset.tab;
        this.currentPage = 1;
        this.searchTerm = '';
        this.sortBy = this.getDefaultSortField();
        this.sortOrder = 'desc';
        
        this.updateTableTitle();
        this.updateSearchPlaceholder();
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
        }

        this.updateFilterOptions();
        this.renderTable();
    }

    getDefaultSortField() {
        switch(this.currentTab) {
            case 'incidents': return 'created_at';
            case 'reports': return 'created_at';
            case 'personnel': return 'first_name';
            case 'equipment': return 'equipment_name';
            default: return 'created_at';
        }
    }

    updateTableTitle() {
        const tableTitle = document.getElementById('tableTitle');
        if (tableTitle) {
            const titles = {
                incidents: 'Incidents',
                reports: 'Reports',
                personnel: 'Personnel',
                equipment: 'Equipment'
            };
            tableTitle.textContent = titles[this.currentTab] || 'Data';
        }
    }

    updateSearchPlaceholder() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        const placeholders = {
            incidents: 'Search by title, incident ID, location, or source...',
            reports: 'Search by report stage, investigator, or incident...',
            personnel: 'Search by name, email, or account code...',
            equipment: 'Search by equipment name, type, or serial number...'
        };

        searchInput.placeholder = placeholders[this.currentTab] || 'Search...';
    }

    updateFilterOptions() {
        const filterSelect = document.getElementById('filterSelect');
        const dateFilter = document.getElementById('dateFilter');
        const addNewBtn = document.getElementById('addNewBtn');
        if (!filterSelect) return;

        const options = {
            incidents: [
                { value: '', label: 'All Status' },
                { value: '1', label: 'Reported' },
                { value: '2', label: 'Responding' },
                { value: '3', label: 'Active' },
                { value: '4', label: 'Resolved' }
            ],
            reports: [
                { value: '', label: 'All Status' },
                { value: 'spot', label: 'Spot Investigation' },
                { value: 'progress', label: 'Progress Investigation' },
                { value: 'final', label: 'Final Investigation' }
            ],
            personnel: [
                { value: '', label: 'All Personnel' },
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' }
            ],
            equipment: [
                { value: '', label: 'All Equipment' },
                { value: 'available', label: 'Available' },
                { value: 'in-use', label: 'In Use' },
                { value: 'maintenance', label: 'Under Maintenance' },
                { value: 'damaged', label: 'Damaged' }
            ]
        };

        filterSelect.innerHTML = '<option value="">All Status</option>';
        (options[this.currentTab] || []).forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            filterSelect.appendChild(option);
        });

        if (dateFilter) {
            dateFilter.style.display = this.currentTab === 'incidents' ? '' : 'none';
        }

        if (addNewBtn) {
            if (this.currentTab === 'incidents') {
                addNewBtn.style.display = '';
                addNewBtn.title = 'Add historical fire record';
                addNewBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span class="btn-label">Add Historical Fire</span>';
            } else {
                addNewBtn.style.display = this.currentTab === 'reports' ? 'none' : '';
                addNewBtn.title = `Add ${this.currentTab.slice(0, -1)}`;
                addNewBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span class="btn-label">Add New</span>';
            }
        }
    }

    renderTable() {
        const data = this.getFilteredData();
        const sortedData = this.sortData(data);
        const paginatedData = this.paginateData(sortedData);
        
        this.renderTableHeader();
        this.renderTableBody(paginatedData);
        this.renderPagination(sortedData.length);
    }

    sortData(data) {
        return [...data].sort((a, b) => {
            let aVal, bVal;
            
            switch(this.sortBy) {
                case 'incident_id':
                    aVal = a.incident_id || 0;
                    bVal = b.incident_id || 0;
                    break;
                case 'report_id':
                    aVal = a.report_id || 0;
                    bVal = b.report_id || 0;
                    break;
                case 'title':
                    aVal = a.marker?.title || a.title || '';
                    bVal = b.marker?.title || b.title || '';
                    break;
                case 'incident_title':
                    aVal = a.incident_title || '';
                    bVal = b.incident_title || '';
                    break;
                case 'first_name':
                    aVal = a.first_name || '';
                    bVal = b.first_name || '';
                    break;
                case 'equipment_name':
                    aVal = a.equipment_name || a.name || '';
                    bVal = b.equipment_name || b.name || '';
                    break;
                case 'type':
                    aVal = a.type || '';
                    bVal = b.type || '';
                    break;
                case 'available_quantity':
                    aVal = Number(a.available_quantity || 0);
                    bVal = Number(b.available_quantity || 0);
                    break;
                case 'maintenance_quantity':
                    aVal = Number(a.maintenance_quantity || 0);
                    bVal = Number(b.maintenance_quantity || 0);
                    break;
                case 'damaged_quantity':
                    aVal = Number(a.damaged_quantity || 0);
                    bVal = Number(b.damaged_quantity || 0);
                    break;
                case 'status':
                    aVal = a.incident_status_id || a.status || '';
                    bVal = b.incident_status_id || b.status || '';
                    break;
                case 'date':
                    aVal = a.date || a.created_at || '';
                    bVal = b.date || b.created_at || '';
                    break;
                case 'author':
                    aVal = a.author || '';
                    bVal = b.author || '';
                    break;
                case 'organizer':
                    aVal = a.organizer || '';
                    bVal = b.organizer || '';
                    break;
                case 'instructor':
                    aVal = a.instructor || '';
                    bVal = b.instructor || '';
                    break;
                case 'location':
                    aVal = a.location || '';
                    bVal = b.location || '';
                    break;
                default:
                    aVal = a.created_at || '';
                    bVal = b.created_at || '';
            }
            
            if (this.sortOrder === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
    }

    getFilteredData() {
        let data = [...(this.data[this.currentTab] || [])];

        if (this.searchTerm) {
            data = data.filter(item => {
                const searchable = this.getSearchableFields(item);
                return searchable.toLowerCase().includes(this.searchTerm.toLowerCase());
            });
        }

        if (this.filters.status) {
            data = data.filter(item => {
                const statusValue = this.currentTab === 'incidents'
                    ? this.getIncidentStatusMeta(item).id?.toString() || this.getIncidentStatusMeta(item).value
                    : (item.report_kind || (item.status_id?.toString()) || item.status || '');
                return statusValue === this.filters.status;
            });
        }

        if (this.filters.date && this.currentTab === 'incidents') {
            const today = new Date().toDateString();
            data = data.filter(item => {
                const itemDate = new Date(item.created_at).toDateString();
                return itemDate === today;
            });
        }

        return data;
    }

    getSearchableFields(item) {
        switch(this.currentTab) {
            case 'incidents':
                return [
                    item.incident_id?.toString(),
                    item.marker?.title,
                    item.marker?.description,
                    item.reported_by,
                    item.historical_records?.[0]?.source_name,
                    item.historical_records?.[0]?.incident_location,
                    item.historical_records?.[0]?.fire_cause
                ].filter(f => f).join(' ');
            case 'reports':
                return [
                    item.title,
                    item.author,
                    item.type,
                    item.description,
                    item.incident_title,
                    item.investigator_name
                ].filter(f => f).join(' ');
            case 'personnel':
                return [
                    item.first_name,
                    item.last_name,
                    item.email,
                    item.account_code,
                    item.contact_number
                ].filter(f => f).join(' ');
            case 'equipment':
                return [
                    item.equipment_name,
                    item.type,
                    item.serial_number,
                    item.model,
                    item.category_name
                ].filter(f => f).join(' ');
            default:
                return '';
        }
    }

    paginateData(data) {
        const start = (this.currentPage - 1) * this.perPage;
        const end = start + this.perPage;
        return data.slice(start, end);
    }

    renderTableHeader() {
        const thead = document.getElementById('tableHeader');
        if (!thead) return;

        const headers = {
            incidents: ['Title', 'Location', 'Priority', 'Status', 'Reported By', 'Date', 'Actions'],
            reports: ['Report', 'Stage', 'Incident', 'Investigator', 'Filed', 'Actions'],
            personnel: ['Name', 'Role', 'Account Code', 'Contact', 'Status', 'Actions'],
            equipment: ['Equipment', 'Type', 'Available', 'Maintenance', 'Damaged', 'Actions']
        };

        const currentHeaders = headers[this.currentTab] || ['Data', 'Actions'];
        
        thead.innerHTML = `
            <tr>
                ${currentHeaders.map(header => `<th data-sortable="${header !== 'Actions'}">${header}</th>`).join('')}
            </tr>
        `;
        
        // Re-attach sorting listeners
        const ths = thead.querySelectorAll('th[data-sortable="true"]');
        ths.forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const field = this.getSortFieldFromHeader(th.textContent.trim());
                if (field) {
                    if (this.sortBy === field) {
                        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortOrder = 'asc';
                    }
                    this.renderTable();
                }
            });
            // Add sort icon
            th.innerHTML += `<i class="fa-solid fa-sort" style="margin-left: 5px; opacity: 0.5;"></i>`;
        });
    }

    renderTableBody(data) {
        const tbody = document.getElementById('tableBody');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr class="table-empty-row">
                    <td colspan="8" class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <h3>No ${this.currentTab} found</h3>
                        <p>Try adjusting your search or filter to find what you're looking for.</p>
                    </td>
                </tr>
            `;
            return;
        }

        const renderers = {
            incidents: (item) => this.renderIncidentRow(item),
            reports: (item) => this.renderReportRow(item),
            personnel: (item) => this.renderPersonnelRow(item),
            equipment: (item) => this.renderEquipmentRow(item)
        };

        const renderer = renderers[this.currentTab];
        tbody.innerHTML = data.map((item) => renderer(item)).join('');
    }

    renderIncidentRow(incident) {
        const priority = this.getIncidentPriorityValue(incident);
        
        const priorityClass = {
            'critical': 'priority-critical',
            'high': 'priority-high',
            'medium': 'priority-medium',
            'low': 'priority-low'
        }[priority] || 'priority-medium';

        const historical = this.getPrimaryHistoricalRecord(incident);
        const status = this.getIncidentStatusMeta(incident).value;
        
        const statusClass = {
            'active': 'status-badge active',
            'responding': 'status-badge responding',
            'resolved': 'status-badge resolved',
            'reported': 'status-badge reported'
        }[status] || 'status-badge pending';

        const title = incident.marker?.title || incident.title || 'Untitled';
        const location = historical?.incident_location || (incident.marker ? `${incident.marker.lat}, ${incident.marker.lng}` : 'N/A');
        const reporterName = [incident.reporter?.first_name, incident.reporter?.last_name].filter(Boolean).join(' ').trim();
        const reportedBy = historical?.source_name || reporterName || incident.reported_by || 'Unknown';
        const dateSource = historical?.incident_date || incident.created_at;
        const date = dateSource ? new Date(dateSource).toLocaleDateString() : 'N/A';
        const historicalBadge = historical ? '<span class="badge" style="margin-top:6px;display:inline-flex;">Historical Fire Record</span>' : '';

        return `
            <tr data-id="${incident.incident_id}">
                <td data-label="Title">
                    <div class="cell-content">
                        <strong>${this.escapeHtml(title)}</strong>
                        ${historicalBadge}
                        ${incident.marker?.description ? `<br><small>${this.escapeHtml(incident.marker.description.substring(0, 50))}${incident.marker.description.length > 50 ? '...' : ''}</small>` : ''}
                    </div>
                </td>
                <td data-label="Location">
                    <div class="location-cell">
                        <i class="fa-solid fa-location-dot"></i>
                        <span class="coordinates">${this.escapeHtml(location)}</span>
                    </div>
                </td>
                <td data-label="Priority"><span class="priority-badge ${priorityClass}">${priority}</span></td>
                <td data-label="Status"><span class="${statusClass}">${status}</span></td>
                <td data-label="Reported By">${this.escapeHtml(reportedBy)}</td>
                <td data-label="Date">${date}</td>
                <td data-label="Actions">
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <button class="action-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }

    renderReportRow(report) {
        const statusClass = {
            spot: 'status-badge pending',
            progress: 'status-badge active',
            final: 'status-badge approved'
        }[report.report_kind] || 'status-badge pending';

        return `
            <tr data-id="${report.id}">
                <td data-label="Report"><strong>${this.escapeHtml(report.title)}</strong></td>
                <td data-label="Stage"><span class="badge">${this.escapeHtml(report.type || 'Investigation')}</span></td>
                <td data-label="Incident">${this.escapeHtml(report.incident_title || 'N/A')}</td>
                <td data-label="Investigator">${this.escapeHtml(report.investigator_name || report.author || 'Unknown')}</td>
                <td data-label="Filed">${report.created_at ? new Date(report.created_at).toLocaleDateString() : 'N/A'}</td>
                <td data-label="Actions">
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <span class="${statusClass}">${this.escapeHtml(report.report_kind || 'report')}</span>
                    </div>
                </td>
            </tr>
        `;
    }

    renderEventRow(event) {
        const statusClass = {
            'upcoming': 'status-badge upcoming',
            'ongoing': 'status-badge ongoing',
            'completed': 'status-badge completed',
            'cancelled': 'status-badge cancelled'
        }[event.status] || 'status-badge pending';

        return `
            <tr data-id="${event.id}">
                <td>#${event.id}</td>
                <td><strong>${this.escapeHtml(event.name)}</strong></td>
                <td><i class="fa-solid fa-location-dot"></i> ${this.escapeHtml(event.location || 'N/A')}</td>
                <td>${event.date ? new Date(event.date).toLocaleDateString() : 'N/A'}</td>
                <td><span class="${statusClass}">${event.status || 'upcoming'}</span></td>
                <td>${this.escapeHtml(event.organizer || 'N/A')}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <button class="action-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }

    getPersonnelStatusValue(person) {
        return (person?.status || (person?.status_id == 1 ? 'active' : 'inactive') || 'inactive').toString().toLowerCase();
    }

    getPersonnelStatusClass(status) {
        const statusClassMap = {
            active: 'status-badge active',
            inactive: 'status-badge cancelled',
            available: 'status-badge approved',
            on_duty: 'status-badge active',
            'on-duty': 'status-badge active',
            off_duty: 'status-badge pending',
            'off-duty': 'status-badge pending'
        };

        return statusClassMap[status] || 'status-badge pending';
    }

    renderPersonnelRow(person) {
        const name = `${person.first_name || ''} ${person.last_name || ''}`.trim() || 'Unknown';
        const role = person.role?.role || 'Staff';
        const status = this.getPersonnelStatusValue(person);
        const statusClass = this.getPersonnelStatusClass(status);
        const statusLabel = status.replace(/[_-]/g, ' ');

        return `
            <tr data-id="${person.user_id}">
                <td data-label="Name">
                    <div class="avatar-group">
                        <span class="avatar-sm">${name.substring(0, 2).toUpperCase()}</span>
                        <strong>${this.escapeHtml(name)}</strong>
                    </div>
                </td>
                <td data-label="Role">${this.escapeHtml(role)}</td>
                <td data-label="Account Code"><code>${this.escapeHtml(person.account_code || 'N/A')}</code></td>
                <td data-label="Contact">${this.escapeHtml(person.contact_number || 'N/A')}</td>
                <td data-label="Status"><span class="${statusClass}">${this.escapeHtml(statusLabel)}</span></td>
                <td data-label="Actions">
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <button class="action-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }

    renderEquipmentRow(equipment) {
        const statusClass = {
            'available': 'status-badge approved',
            'in-use': 'status-badge active',
            'maintenance': 'status-badge pending',
            'damaged': 'status-badge rejected'
        }[equipment.status] || 'status-badge pending';

        return `
            <tr data-id="${equipment.equipment_id}">
                <td data-label="Equipment">
                    <strong>${this.escapeHtml(equipment.equipment_name)}</strong>
                    ${equipment.category_name ? `<br><small>${this.escapeHtml(equipment.category_name)}</small>` : ''}
                </td>
                <td data-label="Type">${this.escapeHtml(equipment.type || 'General')}</td>
                <td data-label="Available">${equipment.available_quantity ?? 0}</td>
                <td data-label="Maintenance">${equipment.maintenance_quantity ?? 0}</td>
                <td data-label="Damaged">${equipment.damaged_quantity ?? 0}</td>
                <td data-label="Actions">
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <button class="action-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }

    getItemIdentifier(item) {
        return item.id || item.incident_id || item.user_id || item.equipment_id || item.report_id || item.equipment_station_id;
    }

    renderTrainingRow(training) {
        const statusClass = {
            'upcoming': 'status-badge upcoming',
            'ongoing': 'status-badge ongoing',
            'completed': 'status-badge completed',
            'cancelled': 'status-badge cancelled'
        }[training.status] || 'status-badge pending';

        return `
            <tr data-id="${training.id}">
                <td>#${training.id}</td>
                <td><strong>${this.escapeHtml(training.title)}</strong></td>
                <td>${this.escapeHtml(training.instructor || 'TBD')}</td>
                <td>${training.date ? new Date(training.date).toLocaleDateString() : 'N/A'}</td>
                <td><span class="${statusClass}">${training.status || 'upcoming'}</span></td>
                <td><i class="fa-solid fa-location-dot"></i> ${this.escapeHtml(training.location || 'N/A')}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        <button class="action-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }

    renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.perPage);
        const pagination = document.getElementById('pagination');
        if (!pagination) return;

        const start = totalItems > 0 ? ((this.currentPage - 1) * this.perPage) + 1 : 0;
        const end = Math.min(this.currentPage * this.perPage, totalItems);

        let paginationHtml = `
            <div class="pagination-info">
                Showing ${start} to ${end} of ${totalItems} entries
            </div>
            <div class="pagination-controls">
        `;

        paginationHtml += `
            <button class="pagination-btn" data-page="${this.currentPage - 1}" ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                paginationHtml += `
                    <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                paginationHtml += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        paginationHtml += `
            <button class="pagination-btn" data-page="${this.currentPage + 1}" ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>`;

        pagination.innerHTML = paginationHtml;

        pagination.querySelectorAll('.pagination-btn[data-page]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!btn.hasAttribute('disabled')) {
                    const page = parseInt(btn.dataset.page);
                    this.changePage(page);
                }
            });
        });
    }

    changePage(page) {
        this.currentPage = page;
        this.renderTable();
    }

    async refreshData() {
        this.showToast('Refreshing data...', 'info');
        await this.loadData();
        this.showToast('Data refreshed successfully', 'success');
    }

    exportData() {
        const data = this.getFilteredData();
        const csv = this.convertToCSV(data);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${this.currentTab}_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        this.showToast('Export completed', 'success');
    }

    convertToCSV(data) {
        if (data.length === 0) return '';
        
        let headers = [];
        if (this.currentTab === 'incidents') {
            headers = ['Title', 'Location', 'Priority', 'Status', 'Reported By', 'Date'];
        } else if (this.currentTab === 'reports') {
            headers = ['Report', 'Stage', 'Incident', 'Investigator', 'Filed'];
        } else if (this.currentTab === 'personnel') {
            headers = ['Name', 'Role', 'Account Code', 'Contact', 'Status'];
        } else if (this.currentTab === 'equipment') {
            headers = ['Equipment', 'Type', 'Available', 'Maintenance', 'Damaged'];
        }
        
        const csvRows = [headers.join(',')];
        
        for (const row of data) {
            const values = headers.map(header => {
                let value = '';
                switch(header) {
                    case 'Report ID': value = row.report_id || row.id || ''; break;
                    case 'Title': value = row.marker?.title || row.title; break;
                    case 'Report': value = row.title || ''; break;
                    case 'Location': value = row.location || (row.marker ? `${row.marker.lat}, ${row.marker.lng}` : ''); break;
                    case 'Priority': 
                        const priorityMap = {1: 'low', 2: 'medium', 3: 'high', 4: 'critical'};
                        value = priorityMap[row.incident_type_id] || ''; 
                        break;
                    case 'Status': 
                        if (row.incident_status_id) {
                            const statusMap = {1: 'reported', 2: 'responding', 3: 'active', 4: 'resolved'};
                            value = statusMap[row.incident_status_id] || '';
                        } else if (row.status) {
                            value = row.status;
                        } else if (row.status_id !== undefined && row.status_id !== null) {
                            value = row.status_id == 1 ? 'active' : 'inactive';
                        } else {
                            value = row.status || '';
                        }
                        break;
                    case 'Reported By': value = row.reported_by || ''; break;
                    case 'Date': value = row.date ? new Date(row.date).toLocaleDateString() : (row.created_at ? new Date(row.created_at).toLocaleDateString() : ''); break;
                    case 'Filed': value = row.created_at ? new Date(row.created_at).toLocaleDateString() : ''; break;
                    case 'Name': value = row.name || (row.first_name ? `${row.first_name} ${row.last_name}`.trim() : ''); break;
                    case 'Role': value = row.role?.role || ''; break;
                    case 'Account Code': value = row.account_code || ''; break;
                    case 'Contact': value = row.contact_number || ''; break;
                    case 'Equipment': value = row.equipment_name || ''; break;
                    case 'Type': value = row.type || ''; break;
                    case 'Stage': value = row.type || ''; break;
                    case 'Incident': value = row.incident_title || ''; break;
                    case 'Investigator': value = row.investigator_name || row.author || ''; break;
                    case 'Available': value = row.available_quantity ?? ''; break;
                    case 'Maintenance': value = row.maintenance_quantity ?? ''; break;
                    case 'Damaged': value = row.damaged_quantity ?? ''; break;
                    default: value = '';
                }
                return `"${String(value).replace(/"/g, '""')}"`;
            });
            csvRows.push(values.join(','));
        }
        
        return csvRows.join('\n');
    }

    openAddModal() {
        const titles = {
            incidents: 'Add Historical Fire Record',
            personnel: 'Add Personnel',
            equipment: 'Add Equipment'
        };

        if (this.currentTab === 'reports') {
            this.showToast('Investigation reports are view-only in this registry.', 'info');
            return;
        }

        if (this.currentTab === 'incidents' && !this.managedBarangays.length) {
            this.showToast('No covered barangays are assigned to this station yet.', 'warning');
            return;
        }

        document.getElementById('modalTitle').textContent = titles[this.currentTab] || 'Add New Item';
        
        const modalBody = document.querySelector('#formModal .modal-body');
        modalBody.innerHTML = this.renderAddForm();
        
        document.getElementById('formModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    renderAddForm() {
        switch(this.currentTab) {
            case 'incidents':
                return this.renderIncidentAddForm();
            case 'personnel':
                return this.renderPersonnelAddForm();
            case 'equipment':
                return this.renderEquipmentAddForm();
            default:
                return `<p>Form not available for ${this.currentTab}</p>`;
        }
    }

    renderIncidentAddForm() {
        const barangayOptions = this.managedBarangays.length
            ? this.managedBarangays.map((barangay) => `<option value="${barangay.barangay_id}">${this.escapeHtml(barangay.barangay_name)}</option>`).join('')
            : '<option value="">No covered barangays available</option>';

        return `
            <form id="dataForm" class="edit-form">
                <input type="hidden" name="entry_mode" value="historical_fire_record">
                <div class="info-card" style="margin-bottom:16px;">
                    <div class="card-content">
                        <p style="margin:0;font-size:0.92rem;color:#475569;">Use this form for fires that were already handled outside the live reporting flow. It will be saved as a historical BFP fire record on this incident registry page.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Incident Title *</label>
                    <input type="text" class="form-input" name="title" placeholder="Enter fire incident title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Short summary of the fire incident" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Incident Date *</label>
                        <input type="date" class="form-input" name="incident_date" max="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Incident Time</label>
                        <input type="time" class="form-input" name="incident_time">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Covered Barangay *</label>
                        <select class="form-select" name="barangay_id" ${this.managedBarangays.length ? 'required' : 'disabled'}>
                            <option value="">Select barangay</option>
                            ${barangayOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Classification *</label>
                        <select class="form-select" name="fire_classification" required>
                            <option value="accidental">Accidental</option>
                            <option value="intentional">Intentional</option>
                            <option value="undetermined" selected>Undetermined</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Incident Location *</label>
                    <input type="text" class="form-input" name="incident_location" placeholder="Street, purok, sitio, or landmark" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fire Cause</label>
                    <input type="text" class="form-input" name="fire_cause" placeholder="Known or suspected fire cause">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude *</label>
                        <input type="text" class="form-input" name="lat" placeholder="Latitude" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude *</label>
                        <input type="text" class="form-input" name="lng" placeholder="Longitude" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Incident Count</label>
                        <input type="number" class="form-input" name="incident_count" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimated Damage</label>
                        <input type="number" class="form-input" name="estimated_damage" min="0" step="0.01" placeholder="PHP amount">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Injured Count</label>
                        <input type="number" class="form-input" name="injured_count" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Death Count</label>
                        <input type="number" class="form-input" name="death_count" min="0" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Source Name</label>
                        <input type="text" class="form-input" name="source_name" placeholder="BFP station logbook, after-action report, etc.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source Reference</label>
                        <input type="text" class="form-input" name="source_reference" placeholder="Case number or reference">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Weather Condition</label>
                        <input type="text" class="form-input" name="weather_condition" placeholder="Sunny, cloudy, rainy">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wind Direction</label>
                        <input type="text" class="form-input" name="wind_direction" placeholder="NE, East, etc.">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Temperature (°C)</label>
                        <input type="number" class="form-input" name="temperature" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Humidity (%)</label>
                        <input type="number" class="form-input" name="humidity" min="0" max="100" step="0.1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Wind Speed</label>
                    <input type="number" class="form-input" name="wind_speed" min="0" step="0.1" placeholder="kph or recorded unit">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" name="notes" placeholder="Additional context for the historical record" rows="4"></textarea>
                </div>
            </form>
        `;
    }

    renderReportAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" class="form-input" name="title" placeholder="Report title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <input type="text" class="form-input" name="type" placeholder="e.g., Incident, Maintenance">
                </div>
                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" class="form-input" name="author" placeholder="Author name">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Enter description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </form>
        `;
    }

    renderEventAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Event Name *</label>
                    <input type="text" class="form-input" name="name" placeholder="Event name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="location" placeholder="Location">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="date" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label class="form-label">Organizer</label>
                    <input type="text" class="form-input" name="organizer" placeholder="Organizer name">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Event description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </form>
        `;
    }

    renderPersonnelAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-input" name="first_name" placeholder="First name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name </label>
                        <input type="text" class="form-input" name="middle_name" placeholder="Middle name" >
                    </div>
                </div>

                <div class="form-row">
                 <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-input" name="last_name" placeholder="Last name" required>
                    </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-input" name="email" placeholder="Email address" required>
                </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-input" name="contact_number" placeholder="Contact number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role_id" required>
                           
                            <option value="1">Admin</option>
                            <option value="2">Provincial Admin</option>
                            <option value="3">Chief</option>
                            <option value="4">Citizen</option>
                            <option value="5">Firefighter</option>

                            
                        </select>
                    </div>
                </div>
            </form>
        `;
    }

    // <div class="form-group">
    //                     <label class="form-label">Account Code</label>
    //                     <input type="text" class="form-input" name="account_code" placeholder="Account code">
    //                 </div>
    // <div class="form-row">
    //                 <div class="form-group">
    //                     <label class="form-label">Password</label>
    //                     <input type="password" class="form-input" name="password" placeholder="Password (required for new user)">
    //                 </div>
                    
    //             </div>
    renderEquipmentAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Equipment Name *</label>
                    <input type="text" class="form-input" name="equipment_name" placeholder="Equipment name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-input" name="type" placeholder="Equipment type">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-input" name="model" placeholder="Model">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-input" name="serial_number" placeholder="Serial number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="available">Available</option>
                            <option value="in-use">In Use</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                </div>
            </form>
        `;
    }

    renderTrainingAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Training Title *</label>
                    <input type="text" class="form-input" name="title" placeholder="Training title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <input type="text" class="form-input" name="instructor" placeholder="Instructor name">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="location" placeholder="Training location">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="date" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Training description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </form>
        `;
    }

    // Edit item
    editItem(id) {
        const item = this.data[this.currentTab].find(i => this.getItemIdentifier(i) == id);
        if (!item) {
            this.showToast('Item not found', 'error');
            return;
        }

        if (this.currentTab === 'reports') {
            this.showToast('Investigation reports are view-only in this registry.', 'info');
            return;
        }

        this.currentEditId = id;
        this.currentItem = item;
        document.getElementById('modalTitle').textContent = `Edit ${this.currentTab.slice(0, -1)}`;
        
        const modalBody = document.querySelector('#formModal .modal-body');
        
        switch(this.currentTab) {
            case 'incidents':
                modalBody.innerHTML = this.renderIncidentEditForm(item);
                break;
            case 'personnel':
                modalBody.innerHTML = this.renderPersonnelEditForm(item);
                break;
            case 'equipment':
                modalBody.innerHTML = this.renderEquipmentEditForm(item);
                break;
        }
        
        document.getElementById('formModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    renderIncidentEditForm(incident) {
        const historical = this.getPrimaryHistoricalRecord(incident);

        if (historical) {
            const barangayOptions = this.managedBarangays.length
                ? this.managedBarangays.map((barangay) => `<option value="${barangay.barangay_id}" ${String(historical.barangay_id || '') === String(barangay.barangay_id) ? 'selected' : ''}>${this.escapeHtml(barangay.barangay_name)}</option>`).join('')
                : '<option value="">No covered barangays available</option>';

            return `
                <form id="dataForm" class="edit-form">
                    <input type="hidden" name="entry_mode" value="historical_fire_record">
                    <div class="info-card" style="margin-bottom:16px;">
                        <div class="card-content">
                            <p style="margin:0;font-size:0.92rem;color:#475569;">This incident is stored as a historical BFP fire record. Updating this form will update both the linked incident marker and the historical fire details.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Incident Title *</label>
                        <input type="text" class="form-input" name="title" value="${this.escapeHtml(incident.marker?.title || incident.title || '')}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" rows="3">${this.escapeHtml(incident.marker?.description || incident.description || '')}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Incident Date *</label>
                            <input type="date" class="form-input" name="incident_date" value="${historical.incident_date ? String(historical.incident_date).slice(0, 10) : ''}" max="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Incident Time</label>
                            <input type="time" class="form-input" name="incident_time" value="${historical.incident_time ? String(historical.incident_time).slice(0, 5) : ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Covered Barangay *</label>
                            <select class="form-select" name="barangay_id" ${this.managedBarangays.length ? 'required' : 'disabled'}>
                                <option value="">Select barangay</option>
                                ${barangayOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Classification *</label>
                            <select class="form-select" name="fire_classification" required>
                                <option value="accidental" ${historical.fire_classification === 'accidental' ? 'selected' : ''}>Accidental</option>
                                <option value="intentional" ${historical.fire_classification === 'intentional' ? 'selected' : ''}>Intentional</option>
                                <option value="undetermined" ${historical.fire_classification === 'undetermined' ? 'selected' : ''}>Undetermined</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Incident Location *</label>
                        <input type="text" class="form-input" name="incident_location" value="${this.escapeHtml(historical.incident_location || '')}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fire Cause</label>
                        <input type="text" class="form-input" name="fire_cause" value="${this.escapeHtml(historical.fire_cause || '')}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Latitude *</label>
                            <input type="text" class="form-input" name="lat" value="${incident.marker?.lat || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude *</label>
                            <input type="text" class="form-input" name="lng" value="${incident.marker?.lng || ''}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Incident Count</label>
                            <input type="number" class="form-input" name="incident_count" min="1" value="${historical.incident_count ?? 1}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Damage</label>
                            <input type="number" class="form-input" name="estimated_damage" min="0" step="0.01" value="${historical.estimated_damage ?? ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Injured Count</label>
                            <input type="number" class="form-input" name="injured_count" min="0" value="${historical.injured_count ?? 0}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Death Count</label>
                            <input type="number" class="form-input" name="death_count" min="0" value="${historical.death_count ?? 0}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Source Name</label>
                            <input type="text" class="form-input" name="source_name" value="${this.escapeHtml(historical.source_name || '')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Source Reference</label>
                            <input type="text" class="form-input" name="source_reference" value="${this.escapeHtml(historical.source_reference || '')}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Weather Condition</label>
                            <input type="text" class="form-input" name="weather_condition" value="${this.escapeHtml(historical.weather_condition || '')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Wind Direction</label>
                            <input type="text" class="form-input" name="wind_direction" value="${this.escapeHtml(historical.wind_direction || '')}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Temperature (°C)</label>
                            <input type="number" class="form-input" name="temperature" step="0.1" value="${historical.temperature ?? ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Humidity (%)</label>
                            <input type="number" class="form-input" name="humidity" min="0" max="100" step="0.1" value="${historical.humidity ?? ''}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wind Speed</label>
                        <input type="number" class="form-input" name="wind_speed" min="0" step="0.1" value="${historical.wind_speed ?? ''}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-textarea" name="notes" rows="4">${this.escapeHtml(historical.notes || '')}</textarea>
                    </div>
                </form>
            `;
        }

        const currentPriority = this.getIncidentPriorityValue(incident);
        const currentStatus = this.getIncidentStatusMeta(incident).value;
        
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-input" name="title" value="${this.escapeHtml(incident.marker?.title || incident.title || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(incident.marker?.description || incident.description || '')}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="low" ${currentPriority === 'low' ? 'selected' : ''}>Low</option>
                            <option value="medium" ${currentPriority === 'medium' ? 'selected' : ''}>Medium</option>
                            <option value="high" ${currentPriority === 'high' ? 'selected' : ''}>High</option>
                            <option value="critical" ${currentPriority === 'critical' ? 'selected' : ''}>Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="reported" ${currentStatus === 'reported' ? 'selected' : ''}>Reported</option>
                            <option value="responding" ${currentStatus === 'responding' ? 'selected' : ''}>Responding</option>
                            <option value="active" ${currentStatus === 'active' ? 'selected' : ''}>Active</option>
                            <option value="resolved" ${currentStatus === 'resolved' ? 'selected' : ''}>Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="text" class="form-input" name="lat" value="${incident.marker?.lat || ''}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="text" class="form-input" name="lng" value="${incident.marker?.lng || ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reported By</label>
                    <input type="text" class="form-input" name="reported_by" value="${this.escapeHtml(incident.reported_by || '')}">
                </div>
            </form>
        `;
    }

    renderReportEditForm(report) {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-input" name="title" value="${this.escapeHtml(report.title)}">
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <input type="text" class="form-input" name="type" value="${this.escapeHtml(report.type || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" class="form-input" name="author" value="${this.escapeHtml(report.author || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(report.description || '')}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="pending" ${report.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="approved" ${report.status === 'approved' ? 'selected' : ''}>Approved</option>
                        <option value="rejected" ${report.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                    </select>
                </div>
            </form>
        `;
    }

    renderEventEditForm(event) {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Event Name</label>
                    <input type="text" class="form-input" name="name" value="${this.escapeHtml(event.name)}">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="location" value="${this.escapeHtml(event.location || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="date" value="${event.date ? new Date(event.date).toISOString().split('T')[0] : ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Organizer</label>
                    <input type="text" class="form-input" name="organizer" value="${this.escapeHtml(event.organizer || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(event.description || '')}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming" ${event.status === 'upcoming' ? 'selected' : ''}>Upcoming</option>
                        <option value="ongoing" ${event.status === 'ongoing' ? 'selected' : ''}>Ongoing</option>
                        <option value="completed" ${event.status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${event.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
            </form>
        `;
    }

    renderPersonnelEditForm(person) {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-input" name="first_name" value="${this.escapeHtml(person.first_name || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-input" name="last_name" value="${this.escapeHtml(person.last_name || '')}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" value="${this.escapeHtml(person.email || '')}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Account Code</label>
                        <input type="text" class="form-input" name="account_code" value="${this.escapeHtml(person.account_code || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-input" name="contact_number" value="${this.escapeHtml(person.contact_number || '')}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" ${person.status_id == 1 ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${person.status_id == 0 ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Role ID</label>
                    <select class="form-select" name="role_id">
                        <option value="2" ${person.role_id == 2 ? 'selected' : ''}>Firefighter</option>
                        <option value="3" ${person.role_id == 3 ? 'selected' : ''}>Staff</option>
                        <option value="4" ${person.role_id == 4 ? 'selected' : ''}>Personnel</option>
                        <option value="5" ${person.role_id == 5 ? 'selected' : ''}>Citizen</option>
                    </select>
                </div>
            </form>
        `;
    }

    renderEquipmentEditForm(equipment) {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Equipment Name</label>
                    <input type="text" class="form-input" name="equipment_name" value="${this.escapeHtml(equipment.equipment_name)}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-input" name="type" value="${this.escapeHtml(equipment.type || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-input" name="model" value="${this.escapeHtml(equipment.model || '')}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-input" name="serial_number" value="${this.escapeHtml(equipment.serial_number || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="available" ${equipment.status === 'available' ? 'selected' : ''}>Available</option>
                            <option value="in-use" ${equipment.status === 'in-use' ? 'selected' : ''}>In Use</option>
                            <option value="maintenance" ${equipment.status === 'maintenance' ? 'selected' : ''}>Under Maintenance</option>
                            <option value="damaged" ${equipment.status === 'damaged' ? 'selected' : ''}>Damaged</option>
                        </select>
                    </div>
                </div>
            </form>
        `;
    }

    renderTrainingEditForm(training) {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Training Title</label>
                    <input type="text" class="form-input" name="title" value="${this.escapeHtml(training.title)}">
                </div>
                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <input type="text" class="form-input" name="instructor" value="${this.escapeHtml(training.instructor || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="location" value="${this.escapeHtml(training.location || '')}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="date" value="${training.date ? new Date(training.date).toISOString().split('T')[0] : ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(training.description || '')}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming" ${training.status === 'upcoming' ? 'selected' : ''}>Upcoming</option>
                        <option value="ongoing" ${training.status === 'ongoing' ? 'selected' : ''}>Ongoing</option>
                        <option value="completed" ${training.status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${training.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
            </form>
        `;
    }

    // View item details
    async viewItem(id) {
        const item = this.data[this.currentTab].find(i => this.getItemIdentifier(i) == id);
        if (!item) {
            this.showToast('Item not found', 'error');
            return;
        }

        this.currentItem = item;
        const viewContent = document.getElementById('viewContent');
        
        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        };

        switch(this.currentTab) {
            case 'incidents':
                await this.renderIncidentDetail(item, viewContent, formatDate);
                break;
            case 'reports':
                this.renderReportDetail(item, viewContent, formatDate);
                break;
            case 'personnel':
                this.renderPersonnelDetail(item, viewContent, formatDate);
                break;
            case 'equipment':
                this.renderEquipmentDetail(item, viewContent, formatDate);
                break;
        }
        
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    renderIncidentDetail(item, viewContent, formatDate) {
        const priority = this.getIncidentPriorityValue(item);
        const status = this.getIncidentStatusMeta(item).value;
        const historical = this.getPrimaryHistoricalRecord(item);
        const reporterName = [item.reporter?.first_name, item.reporter?.last_name].filter(Boolean).join(' ').trim();
        const sourceName = historical?.source_name || reporterName || item.reported_by || 'Unknown';
        const recordedDate = historical?.incident_date || item.created_at;
        const recordedTime = historical?.incident_time || null;
        const location = historical?.incident_location || (item.marker ? `${item.marker.lat}, ${item.marker.lng}` : 'N/A');
        const historyBlock = historical ? `
            <div class="info-card">
                <div class="card-title">
                    <i class="fa-solid fa-book-open"></i>
                    <h3>Historical Fire Record</h3>
                </div>
                <div class="card-content">
                    <div class="info-row"><span class="info-label">Source:</span><span class="info-value">${this.escapeHtml(historical.source_name || 'Station Historical Fire Entry')}</span></div>
                    <div class="info-row"><span class="info-label">Reference:</span><span class="info-value">${this.escapeHtml(historical.source_reference || 'N/A')}</span></div>
                    <div class="info-row"><span class="info-label">Barangay:</span><span class="info-value">${this.escapeHtml(historical.barangay?.barangay_name || 'N/A')}</span></div>
                    <div class="info-row"><span class="info-label">Classification:</span><span class="info-value">${this.escapeHtml(historical.fire_classification || 'undetermined')}</span></div>
                    <div class="info-row"><span class="info-label">Cause:</span><span class="info-value">${this.escapeHtml(historical.fire_cause || 'N/A')}</span></div>
                    <div class="info-row"><span class="info-label">Impact:</span><span class="info-value">${Number(historical.incident_count || 1)} incident(s), ${Number(historical.injured_count || 0)} injured, ${Number(historical.death_count || 0)} death(s)</span></div>
                    <div class="info-row"><span class="info-label">Estimated Damage:</span><span class="info-value">${historical.estimated_damage ? `PHP ${Number(historical.estimated_damage).toLocaleString()}` : 'N/A'}</span></div>
                    <div class="info-row"><span class="info-label">Weather:</span><span class="info-value">${this.escapeHtml(historical.weather_condition || 'N/A')}</span></div>
                    <div class="info-row"><span class="info-label">Notes:</span><span class="info-value">${this.escapeHtml(historical.notes || 'N/A')}</span></div>
                </div>
            </div>
        ` : '';

        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <div class="incident-badge priority-${priority}">
                            <i class="fa-solid fa-fire"></i>
                            <span>${historical ? 'HISTORICAL FIRE RECORD' : `${priority.toUpperCase()} INCIDENT`}</span>
                        </div>
                        <h2 class="incident-title">${this.escapeHtml(item.marker?.title || item.title || 'Untitled Incident')}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-label">Current Status</span>
                        <span class="status-badge-large status-${status}">
                            <i class="fa-solid fa-circle"></i>
                            ${status.toUpperCase()}
                        </span>
                    </div>
                </div>

                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">${historical ? 'Incident Date' : 'Reported'}</span>
                            <span class="stat-value">${formatDate(recordedDate)}${recordedTime ? ` ${recordedTime}` : ''}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Location</span>
                            <span class="stat-value">${this.escapeHtml(location)}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">${historical ? 'Source' : 'Reported By'}</span>
                            <span class="stat-value">${this.escapeHtml(sourceName)}</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-title">
                        <i class="fa-solid fa-align-left"></i>
                        <h3>Description</h3>
                    </div>
                    <div class="card-content">
                        <p>${this.escapeHtml(item.marker?.description || item.description || 'No description provided.')}</p>
                    </div>
                </div>
                ${historyBlock}
            </div>
        `;
    }

    renderReportDetail(report, viewContent, formatDate) {
        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="incident-title">${this.escapeHtml(report.title)}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large status-${report.report_kind}">${report.type || 'Investigation Report'}</span>
                    </div>
                </div>
                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Date</span>
                            <span class="stat-value">${formatDate(report.created_at)}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-tag"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Type</span>
                            <span class="stat-value">${this.escapeHtml(report.type || 'Investigation')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Investigator</span>
                            <span class="stat-value">${this.escapeHtml(report.investigator_name || report.author || 'Unknown')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Incident</span>
                            <span class="stat-value">${this.escapeHtml(report.incident_title || 'N/A')}</span>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-align-left"></i><h3>Description</h3></div>
                    <div class="card-content"><p>${this.escapeHtml(report.description || 'No description provided.')}</p></div>
                </div>
            </div>
        `;
    }

    renderEventDetail(event, viewContent, formatDate) {
        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="incident-title">${this.escapeHtml(event.name)}</h2>
                        <div class="incident-id">ID: #${event.id}</div>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large status-${event.status}">${event.status || 'upcoming'}</span>
                    </div>
                </div>
                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Date</span>
                            <span class="stat-value">${event.date ? new Date(event.date).toLocaleDateString() : 'N/A'}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Location</span>
                            <span class="stat-value">${this.escapeHtml(event.location || 'N/A')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Organizer</span>
                            <span class="stat-value">${this.escapeHtml(event.organizer || 'N/A')}</span>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-align-left"></i><h3>Description</h3></div>
                    <div class="card-content"><p>${this.escapeHtml(event.description || 'No description provided.')}</p></div>
                </div>
            </div>
        `;
    }

    renderPersonnelDetail(person, viewContent, formatDate) {
        const name = `${person.first_name || ''} ${person.last_name || ''}`.trim() || 'Unknown';
        const status = this.getPersonnelStatusValue(person);
        const statusLabel = status.replace(/[_-]/g, ' ').toUpperCase();
        const statusClass = `status-${status.replace(/_/g, '-')}`;

        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="personnel-title">${this.escapeHtml(name)}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large ${statusClass}">
                            <i class="fa-solid fa-circle"></i>
                            ${this.escapeHtml(statusLabel)}
                        </span>
                    </div>
                </div>

                <div class="personnel-detail-grid">
                    <div class="info-card">
                        <div class="card-title">
                            <i class="fa-solid fa-address-card"></i>
                            <h3>Personal Information</h3>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value">${this.escapeHtml(name)}</span>
                            </div>
                            <div class="info-row">
                            <span class="info-label">Role:</span>
                            <span class="info-value">${this.escapeHtml(person.rank || person.role?.role || 'Staff')}</span>
                        </div>
                            <div class="info-row">
                                <span class="info-label">Account Code:</span>
                                <span class="info-value">${this.escapeHtml(person.account_code || 'N/A')}</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="card-title">
                            <i class="fa-solid fa-phone"></i>
                            <h3>Contact Information</h3>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value">${this.escapeHtml(person.email || 'N/A')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Contact:</span>
                                <span class="info-value">${this.escapeHtml(person.contact_number || 'N/A')}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderEquipmentDetail(equipment, viewContent, formatDate) {
        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="incident-title">${this.escapeHtml(equipment.equipment_name)}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large status-${equipment.status || 'available'}">
                            ${equipment.status || 'available'}
                        </span>
                    </div>
                </div>

                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-tag"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Type</span>
                            <span class="stat-value">${this.escapeHtml(equipment.type || 'General')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Available</span>
                            <span class="stat-value">${equipment.available_quantity ?? 0}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Maintenance</span>
                            <span class="stat-value">${equipment.maintenance_quantity ?? 0}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Damaged</span>
                            <span class="stat-value">${equipment.damaged_quantity ?? 0}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderTrainingDetail(training, viewContent, formatDate) {
        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="incident-title">${this.escapeHtml(training.title)}</h2>
                        <div class="incident-id">ID: #${training.id}</div>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large status-${training.status}">${training.status || 'upcoming'}</span>
                    </div>
                </div>
                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Date</span>
                            <span class="stat-value">${training.date ? new Date(training.date).toLocaleDateString() : 'N/A'}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Location</span>
                            <span class="stat-value">${this.escapeHtml(training.location || 'N/A')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Instructor</span>
                            <span class="stat-value">${this.escapeHtml(training.instructor || 'TBD')}</span>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-align-left"></i><h3>Description</h3></div>
                    <div class="card-content"><p>${this.escapeHtml(training.description || 'No description provided.')}</p></div>
                </div>
            </div>
        `;
    }

    // Delete item
    deleteItem(id) {
        if (this.currentTab === 'reports') {
            this.showToast('Investigation reports are view-only in this registry.', 'info');
            return;
        }
        this.currentDeleteId = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    async confirmDelete() {
        if (!this.currentDeleteId) return;

        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const originalDeleteLabel = confirmDeleteBtn ? confirmDeleteBtn.innerHTML : '';

        try {
            this.showLoading(true);
            if (confirmDeleteBtn) {
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
            }
            
            // For incidents, personnel, equipment we call backend; others are frontend-only
            if (['incidents', 'personnel', 'equipment'].includes(this.currentTab)) {
                const response = await fetch(`/api/managements/${this.currentTab}/${this.currentDeleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Delete failed');
            }
            
            // Remove from frontend data
            this.data[this.currentTab] = this.data[this.currentTab].filter(item => {
                const itemId = this.getItemIdentifier(item);
                return itemId != this.currentDeleteId;
            });
            
            this.showToast('Item deleted successfully', 'success');
            this.renderTable();
            this.updateStats();
        } catch (error) {
            console.error('Delete error:', error);
            this.showToast(error.message || 'Failed to delete item', 'error');
        } finally {
            if (confirmDeleteBtn) {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = originalDeleteLabel;
            }
            this.showLoading(false);
            this.closeModal('deleteModal');
            this.currentDeleteId = null;
        }
    }

    async saveFormData() {
        const form = document.getElementById('dataForm');
        if (!form) return;

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            this.showToast('Saving...', 'info');
            
            // For incidents, personnel, equipment: call backend; others: frontend-only
            if (['incidents', 'personnel', 'equipment'].includes(this.currentTab)) {
                let url = '';
                let method = '';
                
                if (this.currentEditId) {
                    // Update existing
                    url = `/api/managements/${this.currentTab}/${this.currentEditId}`;
                    method = 'PUT';
                } else {
                    // Create new
                    url = `/api/managements/${this.currentTab}`;
                    method = 'POST';
                }
                
                // Prepare data based on tab type
                let payload = {};
                
                if (this.currentTab === 'personnel') {
                    // Map frontend fields to backend expected fields
                    payload = {
                        first_name: data.first_name,
                        last_name: data.last_name,
                        middle_name: data.middle_name,

                        email: data.email,
                        // account_code: data.account_code || null,
                        contact_number: data.contact_number || null,
                        role_id: data.role_id,
                    };
                    
                    // Add password only for new users
                    if (!this.currentEditId && data.password) {
                        // payload.password = data.password;
                    }
                } else if (this.currentTab === 'incidents') {
                    if (data.entry_mode === 'historical_fire_record') {
                        payload = {
                            entry_mode: data.entry_mode,
                            title: data.title,
                            description: data.description,
                            incident_date: data.incident_date,
                            incident_time: data.incident_time || null,
                            barangay_id: data.barangay_id,
                            incident_location: data.incident_location,
                            fire_classification: data.fire_classification,
                            fire_cause: data.fire_cause || null,
                            incident_count: data.incident_count || 1,
                            injured_count: data.injured_count || 0,
                            death_count: data.death_count || 0,
                            estimated_damage: data.estimated_damage || null,
                            lat: data.lat,
                            lng: data.lng,
                            source_name: data.source_name || null,
                            source_reference: data.source_reference || null,
                            weather_condition: data.weather_condition || null,
                            temperature: data.temperature || null,
                            humidity: data.humidity || null,
                            wind_speed: data.wind_speed || null,
                            wind_direction: data.wind_direction || null,
                            notes: data.notes || null
                        };
                    } else {
                        payload = {
                            title: data.title,
                            description: data.description,
                            priority: data.priority,
                            status: data.status,
                            lat: data.lat,
                            lng: data.lng,
                            reported_by: data.reported_by
                        };
                    }
                } else if (this.currentTab === 'equipment') {
                    payload = {
                        equipment_name: data.equipment_name,
                        type: data.type,
                        serial_number: data.serial_number,
                        model: data.model,
                        status: data.status
                    };
                }
                
                console.log('Saving payload:', payload); // Debug log
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Save failed');
                }
                
                const result = await response.json();
                console.log('Save result:', result); // Debug log
                
                if (this.currentEditId) {
                    // Update local data
                    const index = this.data[this.currentTab].findIndex(i => {
                        const itemId = this.getItemIdentifier(i);
                        return itemId == this.currentEditId;
                    });
                    if (index !== -1) {
                        this.data[this.currentTab][index] = { ...this.data[this.currentTab][index], ...result.data };
                    }
                } else {
                    // Add new (using returned data from backend)
                    const newItem = result.data || { ...data, id: Date.now() };
                    this.data[this.currentTab].unshift(newItem);
                }
            } else {
                // Frontend-only storage for reports, events, trainings
                if (this.currentEditId) {
                    const index = this.data[this.currentTab].findIndex(i => i.id == this.currentEditId);
                    if (index !== -1) {
                        this.data[this.currentTab][index] = { ...this.data[this.currentTab][index], ...data };
                    }
                } else {
                    const newId = this.nextId[this.currentTab]++;
                    const newItem = {
                        id: newId,
                        ...data,
                        created_at: new Date().toISOString()
                    };
                    this.data[this.currentTab].unshift(newItem);
                }
            }
            
            this.showToast('Saved successfully', 'success');
            this.closeModal('formModal');
            this.renderTable();
            this.updateStats();
            this.currentEditId = null;
        } catch (error) {
            console.error('Save error:', error);
            this.showToast(error.message || 'Failed to save', 'error');
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
        document.body.style.overflow = 'auto';
        this.currentEditId = null;
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
        this.currentEditId = null;
    }

    showLoading(show) {
        const loadingEl = document.getElementById('loadingState');
        const tableEl = document.getElementById('dataTable');
        
        if (loadingEl && tableEl) {
            loadingEl.style.display = show ? 'flex' : 'none';
            tableEl.style.display = show ? 'none' : 'block';
        }
    }

    showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };

        toast.innerHTML = `
            <i class="fa-regular ${icons[type]}"></i>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getIncidentStatusMeta(incident) {
        const rawId = Number(incident?.incident_status_id ?? 0);
        const rawStatus = String(incident?.status || '').trim().toLowerCase();

        if (rawId === 1 || rawStatus === 'reported') return { id: 1, value: 'reported' };
        if (rawId === 2 || rawStatus === 'responding') return { id: 2, value: 'responding' };
        if (rawId === 3 || rawStatus === 'active' || rawStatus === 'controlled') return { id: 3, value: 'active' };
        if (rawId === 4 || rawId === 5 || ['resolved', 'closed'].includes(rawStatus)) return { id: 4, value: 'resolved' };

        return { id: rawId || null, value: rawStatus || 'reported' };
    }

    getIncidentPriorityValue(incident) {
        const priorityMap = { 1: 'low', 2: 'medium', 3: 'high', 4: 'critical' };
        return priorityMap[incident?.incident_type_id] || 'medium';
    }

    getPrimaryHistoricalRecord(incident) {
        if (!Array.isArray(incident?.historical_records) || !incident.historical_records.length) {
            return null;
        }

        return incident.historical_records[0];
    }
    
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch('/api/reverse', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ lat, lng })
            });
            const data = await response.json();
            if (data.features && data.features.length) {
                return data.features[0].properties.formatted;
            }
            return `${lat}, ${lng}`;
        } catch (err) {
            console.error('Reverse geocoding error:', err);
            return `${lat}, ${lng}`;
        }
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    window.management = new ManagementPage();
});
