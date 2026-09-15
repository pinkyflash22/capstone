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
        this.fetchTimeoutMs = 10000;
        this.data = {
            incidents: window.incidentDb || [],
            reports: window.reportDb || [],
            events: window.eventDb || [],
            personnel: window.personnelDb || [],
            equipment: window.equipmentDb || [],
            trainings: window.trainingDb || []
        };
        this.rolesData ={
            allRoles: window.rolesData || [],
        };
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken;
        this.nextId = {
            reports: 100,
            events: 100,
            trainings: 100
        };
        this.init();
    }

    init() {
        this.initEventListeners();
        this.loadData();
        this.initTabs();
        this.initSearch();
        this.initModals();
        this.setupActionListeners();
        this.initSorting();
        
        document.dispatchEvent(new Event('management:ready'));
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
            'Title': 'title',
            'Name': 'name',
            'Equipment': 'equipment_name',
            'Type': 'type',
            'Status': 'status',
            'Date': 'date',
            'Reported By': 'reported_by',
            'Author': 'author',
            'Organizer': 'organizer',
            'Instructor': 'instructor',
            'Location': 'location'
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
                } else if (actionBtn.classList.contains('approve')) {
                    await this.reviewReport(id, 'approved');
                } else if (actionBtn.classList.contains('reject')) {
                    await this.reviewReport(id, 'rejected');
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

    async loadData() {
        this.showLoading(true);
        
        try {
            const requests = await Promise.allSettled([
                this.fetchData(`/api/management/incidents`),
                this.fetchData(`/api/managements/reports`),
                this.fetchData(`/api/management/events`),
                this.fetchData(`/api/management/personnel`),
                this.fetchData(`/api/management/equipment`),
                this.fetchData(`/api/management/trainings`)
            ]);
            const [incidents, reports, events, personnel, equipment, trainings] = requests.map((result) =>
                result.status === 'fulfilled' ? result.value : []
            );

            this.data = {
                incidents: incidents.length ? incidents : (window.incidentDb || []),
                reports: reports.length ? reports : (window.reportDb || []),
                events: events.length ? events : (window.eventDb || []),
                personnel: personnel.length ? personnel : (window.personnelDb || []),
                equipment: equipment.length ? equipment : (window.equipmentDb || []),
                trainings: trainings.length ? trainings : (window.trainingDb || [])
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
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), this.fetchTimeoutMs);

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                cache: 'no-store',
                signal: controller.signal
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            return data.data || data;
        } catch (error) {
            console.error(`Error fetching ${url}:`, error);
            return [];
        } finally {
            window.clearTimeout(timeoutId);
        }
    }

    updateStats() {
        const stats = {
            totalIncidents: this.data.incidents.length,
            activeIncidents: this.data.incidents.filter(i => i.incident_status_id == 1 || i.incident_status_id == 2).length,
            totalReports: this.data.reports.length,
            pendingReports: this.data.reports.filter(r => (r.review_status || 'submitted') === 'submitted').length,
            upcomingEvents: this.data.events.filter(e => new Date(e.date) > new Date()).length,
            totalPersonnel: this.data.personnel.length
        };

        document.getElementById('totalIncidents') && (document.getElementById('totalIncidents').textContent = stats.totalIncidents);
        document.getElementById('activeIncidents') && (document.getElementById('activeIncidents').textContent = `${stats.activeIncidents} active`);
        document.getElementById('totalReports') && (document.getElementById('totalReports').textContent = stats.totalReports);
        document.getElementById('pendingReports') && (document.getElementById('pendingReports').textContent = `${stats.pendingReports} submitted`);
        document.getElementById('upcomingEvents') && (document.getElementById('upcomingEvents').textContent = stats.upcomingEvents);
        document.getElementById('totalPersonnel') && (document.getElementById('totalPersonnel').textContent = stats.totalPersonnel);
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
            case 'events': return 'date';
            case 'personnel': return 'first_name';
            case 'equipment': return 'equipment_name';
            case 'trainings': return 'date';
            default: return 'created_at';
        }
    }

    updateTableTitle() {
        const tableTitle = document.getElementById('tableTitle');
        if (tableTitle) {
            const titles = {
                incidents: 'Incidents',
                reports: 'Reports',
                events: 'Events',
                personnel: 'Personnel',
                equipment: 'Equipment',
                trainings: 'Trainings'
            };
            tableTitle.textContent = titles[this.currentTab] || 'Data';
        }
    }

    updateSearchPlaceholder() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        const placeholders = {
            incidents: 'Search by title, incident ID, or reporter...',
            reports: 'Search by title, author, or type...',
            events: 'Search by event name, location, or organizer...',
            personnel: 'Search by name, email, or account code...',
            equipment: 'Search by equipment name, type, or serial number...',
            trainings: 'Search by training title, instructor, or location...'
        };

        searchInput.placeholder = placeholders[this.currentTab] || 'Search...';
    }

    updateFilterOptions() {
        const filterSelect = document.getElementById('filterSelect');
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
                { value: 'pending', label: 'Pending' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' }
            ],
            events: [
                { value: '', label: 'All Events' },
                { value: 'upcoming', label: 'Upcoming' },
                { value: 'ongoing', label: 'Ongoing' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' }
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
            ],
            trainings: [
                { value: '', label: 'All Trainings' },
                { value: 'upcoming', label: 'Upcoming' },
                { value: 'ongoing', label: 'Ongoing' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' }
            ]
        };

        filterSelect.innerHTML = '<option value="">All Status</option>';
        (options[this.currentTab] || []).forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            filterSelect.appendChild(option);
        });
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
                case 'title':
                    aVal = a.marker?.title || a.title || '';
                    bVal = b.marker?.title || b.title || '';
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
                const statusValue = item.incident_status_id?.toString() || 
                                   (item.status_id?.toString()) || 
                                   item.status || '';
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
                    item.reported_by
                ].filter(f => f).join(' ');
            case 'reports':
                return [
                    item.title,
                    item.author,
                    item.type,
                    item.description
                ].filter(f => f).join(' ');
            case 'events':
                return [
                    item.name,
                    item.location,
                    item.organizer,
                    item.description
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
                    item.model
                ].filter(f => f).join(' ');
            case 'trainings':
                return [
                    item.title,
                    item.instructor,
                    item.location,
                    item.description
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
            incidents: ['ID', 'Title', 'Location', 'Priority', 'Status', 'Reported By', 'Date', 'Actions'],
            reports: ['Report', 'Stage', 'Incident', 'Investigator', 'Review', 'Filed', 'Actions'],
            events: ['ID', 'Event Name', 'Location', 'Date', 'Status', 'Organizer', 'Actions'],
            personnel: ['ID', 'Name', 'Role', 'Account Code', 'Contact', 'Status', 'Actions'],
            equipment: ['ID', 'Equipment', 'Type', 'Serial #', 'Model', 'Status', 'Actions'],
            trainings: ['ID', 'Training', 'Instructor', 'Date', 'Status', 'Location', 'Actions']
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
                <tr>
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
            events: (item) => this.renderEventRow(item),
            personnel: (item) => this.renderPersonnelRow(item),
            equipment: (item) => this.renderEquipmentRow(item),
            trainings: (item) => this.renderTrainingRow(item)
        };

        const renderer = renderers[this.currentTab];
        tbody.innerHTML = data.map((item) => renderer(item)).join('');
    }

    renderIncidentRow(incident) {
        const priorityMap = {1: 'low', 2: 'medium', 3: 'high', 4: 'critical'};
        const priority = priorityMap[incident.incident_type_id] || 'medium';
        
        const priorityClass = {
            'critical': 'priority-critical',
            'high': 'priority-high',
            'medium': 'priority-medium',
            'low': 'priority-low'
        }[priority] || 'priority-medium';

        const statusMap = {1: 'reported', 2: 'responding', 3: 'active', 4: 'resolved'};
        const status = statusMap[incident.incident_status_id] || 'reported';
        
        const statusClass = {
            'active': 'status-badge active',
            'responding': 'status-badge responding',
            'resolved': 'status-badge resolved',
            'reported': 'status-badge reported'
        }[status] || 'status-badge pending';

        const title = incident.marker?.title || incident.title || 'Untitled';
        const location = incident.marker ? `${incident.marker.lat}, ${incident.marker.lng}`: 'N/A';
        const reportedBy = incident.reported_by || 'Unknown';
        const date = incident.created_at ? new Date(incident.created_at).toLocaleDateString() : 'N/A';

        return `
            <tr data-id="${incident.incident_id}">
                <td>#${incident.incident_id}</td>
                <td>
                    <div class="cell-content">
                        <strong>${this.escapeHtml(title)}</strong>
                        ${incident.marker?.description ? `<br><small>${this.escapeHtml(incident.marker.description.substring(0, 50))}${incident.marker.description.length > 50 ? '...' : ''}</small>` : ''}
                    </div>
                </td>
                <td>
                    <div class="location-cell">
                        <i class="fa-solid fa-location-dot"></i>
                        <span class="coordinates">${this.escapeHtml(location)}</span>
                    </div>
                </td>
                <td><span class="priority-badge ${priorityClass}">${priority}</span></td>
                <td><span class="${statusClass}">${status}</span></td>
                <td>${this.escapeHtml(reportedBy)}</td>
                <td>${date}</td>
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

    renderReportRow(report) {
        const reviewStatus = report.review_status || 'submitted';
        const statusClass = {
            submitted: 'status-badge pending',
            approved: 'status-badge approved',
            rejected: 'status-badge rejected'
        }[reviewStatus] || 'status-badge pending';
        const canApprove = reviewStatus !== 'approved';
        const canReject = reviewStatus !== 'rejected';

        return `
            <tr data-id="${report.id}">
                <td><strong>${this.escapeHtml(report.title)}</strong></td>
                <td><span class="badge">${this.escapeHtml(report.type || 'General')}</span></td>
                <td>${this.escapeHtml(report.incident_title || 'N/A')}</td>
                <td>${this.escapeHtml(report.investigator_name || report.author || 'Unknown')}</td>
                <td><span class="${statusClass}">${this.escapeHtml(report.review_status_label || reviewStatus)}</span></td>
                <td>${report.created_at ? new Date(report.created_at).toLocaleDateString() : 'N/A'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                        ${canApprove ? '<button class="action-btn approve" title="Approve"><i class="fa-solid fa-check"></i></button>' : ''}
                        ${canReject ? '<button class="action-btn reject" title="Reject"><i class="fa-solid fa-xmark"></i></button>' : ''}
                        <button class="action-btn delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
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

    renderPersonnelRow(person) {
        const name = `${person.first_name || ''} ${person.last_name || ''}`.trim() || 'Unknown';
        const role = person.role?.role || 'Staff';
        const status = person.status_id == 1 ? 'active' : 'inactive';
        const statusClass = status === 'active' ? 'status-badge active' : 'status-badge cancelled';

        return `
            <tr data-id="${person.user_id}">
                <td>#${person.user_id}</td>
                <td>
                    <div class="avatar-group">
                        <span class="avatar-sm">${name.substring(0, 2).toUpperCase()}</span>
                        <strong>${this.escapeHtml(name)}</strong>
                    </div>
                </td>
                <td>${this.escapeHtml(role)}</td>
                <td><code>${this.escapeHtml(person.account_code || 'N/A')}</code></td>
                <td>${this.escapeHtml(person.contact_number || 'N/A')}</td>
                <td><span class="${statusClass}">${status}</span></td>
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

    renderEquipmentRow(equipment) {
        const statusClass = {
            'available': 'status-badge approved',
            'in-use': 'status-badge active',
            'maintenance': 'status-badge pending',
            'damaged': 'status-badge rejected'
        }[equipment.status] || 'status-badge pending';

        return `
            <tr data-id="${equipment.equipment_id}">
                <td>#${equipment.equipment_id}</td>
                <td><strong>${this.escapeHtml(equipment.equipment_name)}</strong></td>
                <td>${this.escapeHtml(equipment.type || 'General')}</td>
                <td><code class="serial-number">${this.escapeHtml(equipment.serial_number || 'N/A')}</code></td>
                <td>${this.escapeHtml(equipment.model || 'N/A')}</td>
                <td><span class="${statusClass}">${equipment.status || 'available'}</span></td>
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
            headers = ['ID', 'Title', 'Location', 'Priority', 'Status', 'Reported By', 'Date'];
        } else if (this.currentTab === 'reports') {
            headers = ['Report', 'Stage', 'Incident', 'Investigator', 'Review', 'Filed'];
        } else if (this.currentTab === 'events') {
            headers = ['ID', 'Name', 'Location', 'Date', 'Status', 'Organizer'];
        } else if (this.currentTab === 'personnel') {
            headers = ['ID', 'Name', 'Role', 'Account Code', 'Contact', 'Status'];
        } else if (this.currentTab === 'equipment') {
            headers = ['ID', 'Equipment', 'Type', 'Serial #', 'Model', 'Status'];
        } else if (this.currentTab === 'trainings') {
            headers = ['ID', 'Title', 'Instructor', 'Date', 'Status', 'Location'];
        }
        
        const csvRows = [headers.join(',')];
        
        for (const row of data) {
            const values = headers.map(header => {
                let value = '';
                switch(header) {
                    case 'ID': value = row.id || row.incident_id || row.user_id || row.equipment_id; break;
                    case 'Title': value = row.marker?.title || row.title; break;
                    case 'Location': value = row.location || (row.marker ? `${row.marker.lat}, ${row.marker.lng}` : ''); break;
                    case 'Priority': 
                        const priorityMap = {1: 'low', 2: 'medium', 3: 'high', 4: 'critical'};
                        value = priorityMap[row.incident_type_id] || ''; 
                        break;
                    case 'Status': 
                        if (row.incident_status_id) {
                            const statusMap = {1: 'reported', 2: 'responding', 3: 'active', 4: 'resolved'};
                            value = statusMap[row.incident_status_id] || '';
                        } else if (row.status_id) {
                            value = row.status_id == 1 ? 'active' : 'inactive';
                        } else {
                            value = row.status || '';
                        }
                        break;
                    case 'Reported By': value = row.reported_by || ''; break;
                    case 'Date': value = row.date ? new Date(row.date).toLocaleDateString() : (row.created_at ? new Date(row.created_at).toLocaleDateString() : ''); break;
                    case 'Name': value = row.name || (row.first_name ? `${row.first_name} ${row.last_name}`.trim() : ''); break;
                    case 'Role': value = row.role?.role || ''; break;
                    case 'Account Code': value = row.account_code || ''; break;
                    case 'Contact': value = row.contact_number || ''; break;
                    case 'Equipment': value = row.equipment_name || ''; break;
                    case 'Type': value = row.type || ''; break;
                    case 'Serial #': value = row.serial_number || ''; break;
                    case 'Model': value = row.model || ''; break;
                    case 'Author': value = row.author || ''; break;
                    case 'Report': value = row.title || ''; break;
                    case 'Stage': value = row.type || ''; break;
                    case 'Incident': value = row.incident_title || ''; break;
                    case 'Investigator': value = row.investigator_name || row.author || ''; break;
                    case 'Review': value = row.review_status_label || row.review_status || ''; break;
                    case 'Filed': value = row.created_at ? new Date(row.created_at).toLocaleDateString() : ''; break;
                    case 'Organizer': value = row.organizer || ''; break;
                    case 'Instructor': value = row.instructor || ''; break;
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
            incidents: 'Add New Incident',
            reports: 'Submit New Report',
            events: 'Create New Event',
            personnel: 'Add Personnel',
            equipment: 'Add Equipment',
            trainings: 'Schedule Training'
        };

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
            case 'reports':
                return this.renderReportAddForm();
            case 'events':
                return this.renderEventAddForm();
            case 'personnel':
                return this.renderPersonnelAddForm();
            case 'equipment':
                return this.renderEquipmentAddForm();
            case 'trainings':
                return this.renderTrainingAddForm();
            default:
                return `<p>Form not available for ${this.currentTab}</p>`;
        }
    }

    renderIncidentAddForm() {
        return `
            <form id="dataForm" class="edit-form">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" class="form-input" name="title" placeholder="Enter incident title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Enter description" rows="4"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="reported">Reported</option>
                            <option value="responding">Responding</option>
                            <option value="active">Active</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="text" class="form-input" name="lat" placeholder="Latitude">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="text" class="form-input" name="lng" placeholder="Longitude">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reported By</label>
                    <input type="text" class="form-input" name="reported_by" placeholder="Reporter name">
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
        const item = this.data[this.currentTab].find(i => i.id == id || i.incident_id == id || i.user_id == id || i.equipment_id == id);
        if (!item) {
            this.showToast('Item not found', 'error');
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
            case 'reports':
                modalBody.innerHTML = this.renderReportEditForm(item);
                break;
            case 'events':
                modalBody.innerHTML = this.renderEventEditForm(item);
                break;
            case 'personnel':
                modalBody.innerHTML = this.renderPersonnelEditForm(item);
                break;
            case 'equipment':
                modalBody.innerHTML = this.renderEquipmentEditForm(item);
                break;
            case 'trainings':
                modalBody.innerHTML = this.renderTrainingEditForm(item);
                break;
        }
        
        document.getElementById('formModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    renderIncidentEditForm(incident) {
        const priorityMap = {1: 'low', 2: 'medium', 3: 'high', 4: 'critical'};
        const currentPriority = priorityMap[incident.incident_type_id] || 'medium';
        const statusMap = {1: 'reported', 2: 'responding', 3: 'active', 4: 'resolved'};
        const currentStatus = statusMap[incident.incident_status_id] || 'reported';
        
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
        const item = this.data[this.currentTab].find(i => i.id == id || i.incident_id == id || i.user_id == id || i.equipment_id == id);
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
            case 'events':
                this.renderEventDetail(item, viewContent, formatDate);
                break;
            case 'personnel':
                this.renderPersonnelDetail(item, viewContent, formatDate);
                break;
            case 'equipment':
                this.renderEquipmentDetail(item, viewContent, formatDate);
                break;
            case 'trainings':
                this.renderTrainingDetail(item, viewContent, formatDate);
                break;
        }
        
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    renderIncidentDetail(item, viewContent, formatDate) {
        const priorityMap = {1: 'low', 2: 'medium', 3: 'high', 4: 'critical'};
        const priority = priorityMap[item.incident_type_id] || 'medium';
        
        const statusMap = {1: 'reported', 2: 'responding', 3: 'active', 4: 'resolved'};
        const status = statusMap[item.incident_status_id] || 'reported';
        const responders = Array.isArray(item.responder_details) ? item.responder_details : [];
        const responderRows = responders.length
            ? responders.map((responder, index) => `
                <div class="info-row">
                    <span class="info-label"><i class="fa-solid fa-user-shield"></i> Responder ${index + 1}</span>
                    <span class="info-value">
                        <strong>${this.escapeHtml(responder.name || `Officer #${responder.station_officer_id}`)}</strong><br>
                        <small>${this.escapeHtml(responder.rank || 'Station Officer')} &middot; ${this.escapeHtml(responder.station_name || `Station #${responder.station_id}`)}</small>
                    </span>
                </div>
            `).join('')
            : `<p>${item.team_id ? 'No saved responder snapshot is available for this incident.' : 'No response team has been assigned.'}</p>`;
        const respondersBlock = `
            <div class="info-card">
                <div class="card-title">
                    <i class="fa-solid fa-helmet-safety"></i>
                    <h3>Saved Responders</h3>
                </div>
                <div class="card-content">
                    ${responderRows}
                    ${item.responder_snapshot?.created_at ? `<div class="info-row"><span class="info-label">Snapshot saved:</span><span class="info-value">${formatDate(item.responder_snapshot.created_at)}</span></div>` : ''}
                </div>
            </div>
        `;

        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <div class="incident-badge priority-${priority}">
                            <i class="fa-solid fa-fire"></i>
                            <span>${priority.toUpperCase()} INCIDENT</span>
                        </div>
                        <h2 class="incident-title">${this.escapeHtml(item.marker?.title || item.title || 'Untitled Incident')}</h2>
                        <div class="incident-id">ID: #${item.incident_id}</div>
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
                            <span class="stat-label">Reported</span>
                            <span class="stat-value">${formatDate(item.created_at)}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Location</span>
                            <span class="stat-value">${item.marker ? `${item.marker.lat}, ${item.marker.lng}` : 'N/A'}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Reported By</span>
                            <span class="stat-value">${this.escapeHtml(item.reported_by || 'Unknown')}</span>
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
                ${respondersBlock}
            </div>
        `;
    }

    renderReportDetail(report, viewContent, formatDate) {
        const reviewStatus = report.review_status || 'submitted';
        const reviewedAt = report.reviewed_at ? formatDate(report.reviewed_at) : 'Not reviewed yet';
        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="incident-title">${this.escapeHtml(report.title)}</h2>
                        <div class="incident-id">ID: #${report.report_id || report.id}</div>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large status-${reviewStatus}">${this.escapeHtml(report.review_status_label || reviewStatus)}</span>
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
                            <span class="stat-value">${this.escapeHtml(report.type || 'General')}</span>
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
                    <div class="card-title"><i class="fa-solid fa-user-check"></i><h3>Review decision</h3></div>
                    <div class="card-content">
                        <div class="info-row"><span class="info-label">Reviewed by</span><span class="info-value">${this.escapeHtml(report.reviewed_by_name || 'Pending')}</span></div>
                        <div class="info-row"><span class="info-label">Reviewed at</span><span class="info-value">${this.escapeHtml(reviewedAt)}</span></div>
                        <div class="info-row"><span class="info-label">Comments</span><span class="info-value">${this.escapeHtml(report.review_comments || 'No comments recorded.')}</span></div>
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

        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h2 class="personnel-title">${this.escapeHtml(name)}</h2>
                        <div class="personnel-id">ID: #${person.user_id}</div>
                    </div>
                    <div class="status-display">
                        <span class="status-badge-large ${person.status_id == 1 ? 'status-active' : 'status-cancelled'}">
                            <i class="fa-solid fa-circle"></i>
                            ${person.status_id == 1 ? 'ACTIVE' : 'INACTIVE'}
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
                                <span class="info-value">${this.escapeHtml(person.role?.role || 'Staff')}</span>
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
                        <div class="incident-id">ID: #${equipment.equipment_id}</div>
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
                        <div class="stat-icon"><i class="fa-solid fa-barcode"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Serial #</span>
                            <span class="stat-value">${this.escapeHtml(equipment.serial_number || 'N/A')}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-microchip"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Model</span>
                            <span class="stat-value">${this.escapeHtml(equipment.model || 'N/A')}</span>
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
        this.currentDeleteId = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    async reviewReport(id, decision) {
        const report = this.data.reports.find(item => item.id == id);
        if (!report?.report_kind || !report?.report_id) return;

        let reviewComments = '';
        if (decision === 'rejected') {
            reviewComments = window.prompt('Reason for rejecting this report:', report.review_comments || '') || '';
        }

        try {
            this.showLoading(true);
            const response = await fetch(`/api/managements/reports/${encodeURIComponent(report.report_kind)}/${encodeURIComponent(report.report_id)}/review`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    decision,
                    review_comments: reviewComments
                })
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || 'Review update failed');
            }

            Object.assign(report, payload.data || {}, {
                review_status_label: decision.charAt(0).toUpperCase() + decision.slice(1)
            });
            this.showToast(`Report ${decision}`, 'success');
            this.renderTable();
            this.updateStats();
        } catch (error) {
            console.error('Review error:', error);
            this.showToast(error.message || 'Failed to update report review', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async confirmDelete() {
        if (!this.currentDeleteId) return;

        try {
            this.showLoading(true);
            
            // For incidents, personnel, equipment we call backend; others are frontend-only
            if (['incidents', 'personnel', 'equipment', 'reports'].includes(this.currentTab)) {
                const report = this.currentTab === 'reports' ? this.data.reports.find(item => item.id == this.currentDeleteId) : null;
                const deleteUrl = report
                    ? `/api/managements/reports/${encodeURIComponent(report.report_kind)}/${encodeURIComponent(report.report_id)}`
                    : `/api/management/${this.currentTab}/${this.currentDeleteId}`;
                const response = await fetch(deleteUrl, {
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
                const itemId = item.id || item.incident_id || item.user_id || item.equipment_id;
                return itemId != this.currentDeleteId;
            });
            
            this.showToast('Item deleted successfully', 'success');
            this.renderTable();
            this.updateStats();
        } catch (error) {
            console.error('Delete error:', error);
            this.showToast(error.message || 'Failed to delete item', 'error');
        } finally {
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
                    url = `/api/management/${this.currentTab}/${this.currentEditId}`;
                    method = 'PUT';
                } else {
                    // Create new
                    url = `/api/management/${this.currentTab}`;
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
                    payload = {
                        title: data.title,
                        description: data.description,
                        priority: data.priority,
                        status: data.status,
                        lat: data.lat,
                        lng: data.lng,
                        reported_by: data.reported_by
                    };
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
                        const itemId = i.id || i.incident_id || i.user_id || i.equipment_id;
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
        window.PyroNotify?.notify(message, type);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
