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
        this.pagination = { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 };
        this.currentEditId = null;
        this.currentDeleteId = null;
        this.currentItem = null;
        this.pendingReview = null;
        this.coordinatePickerSerial = 0;
        this.coordinatePickers = [];
        this.data = {
            incidents: window.incidentDb || [],
            historical_records: window.historicalRecordDb || [],
            reports: window.reportDb || [],
            personnel: window.personnelDb || [],
            equipment: window.equipmentDb || []
        };
        this.rolesData ={
            allRoles: window.rolesData || [],
        };
        this.managedBarangays = window.managedBarangays || [];
        this.managementTeams = window.managementTeams || [];
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
        const supportedTabs = ['incidents', 'historical_records', 'reports', 'personnel', 'equipment'];
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
                this.loadCurrentTab();
            }, 300));
        }

        // Filter select
        const filterSelect = document.getElementById('filterSelect');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.currentPage = 1;
                this.loadCurrentTab();
            });
        }

        // Date filter
        const dateFilter = document.getElementById('dateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.filters.date = e.target.value;
                this.currentPage = 1;
                this.loadCurrentTab();
            });
        }

        const barangayFilter = document.getElementById('barangayFilter');
        if (barangayFilter) {
            barangayFilter.addEventListener('change', (e) => {
                this.filters.barangay = e.target.value;
                this.currentPage = 1;
                this.loadCurrentTab();
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
            'Record': 'source_name',
            'Report': 'title',
            'Name': 'name',
            'Equipment': 'equipment_name',
            'Type': 'type',
            'Stage': 'type',
            'Incident': 'incident_title',
            'Investigator': 'author',
            'Status': 'status',
            'Priority & status': 'status',
            'Stage & status': 'status',
            'Role & status': 'status',
            'Type & status': 'status',
            'Date': 'date',
            'Incident Date': 'incident_date',
            'Filed': 'date',
            'Reported By': 'reported_by',
            'Author': 'author',
            'Location': 'location',
            'Barangay & location': 'location',
            'Fire details': 'fire_classification',
            'Source & verification': 'source_type',
            'Impact': 'incident_count',
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
                } else if (actionBtn.classList.contains('approve')) {
                    await this.reviewReport(id, 'approved');
                } else if (actionBtn.classList.contains('reject')) {
                    this.openRejectReportModal(id);
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
        this.ensureRejectReportModal();

        // View modal
        document.getElementById('viewModalClose')?.addEventListener('click', () => {
            this.closeModal('viewModal');
        });

        document.getElementById('viewEditIncidentBtn')?.addEventListener('click', () => {
            this.openCurrentIncidentEditor();
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

        document.addEventListener('click', (event) => {
            const tabButton = event.target.closest('[data-incident-edit-tab]');
            if (!tabButton) return;

            event.preventDefault();
            this.activateIncidentEditTab(tabButton.dataset.incidentEditTab);
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

        document.getElementById('rejectReportModalClose')?.addEventListener('click', () => {
            this.closeModal('rejectReportModal');
        });
        document.getElementById('cancelRejectReportBtn')?.addEventListener('click', () => {
            this.closeModal('rejectReportModal');
        });
        document.getElementById('confirmRejectReportBtn')?.addEventListener('click', () => {
            this.confirmRejectReport();
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-edit-current-incident]')) {
                this.openCurrentIncidentEditor();
            }
        });
    }

    initStationEditor() {
        const editStationBtn = document.getElementById('editStationBtn');
        const editStationModal = document.getElementById('editStationModal');
        const closeEditStationBtn = document.getElementById('editStationModalClose');
        const cancelEditStationBtn = document.getElementById('cancelEditStationBtn');
        const editStationForm = document.getElementById('editStationForm');
        const provinceSelect = document.getElementById('editStationProvince');
        const citySelect = document.getElementById('editStationCity');
        const barangaySelect = document.getElementById('editStationBarangay');

        if (!editStationBtn || !editStationModal || !editStationForm) {
            return;
        }

        editStationBtn.addEventListener('click', async () => {
            editStationModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (citySelect?.value && barangaySelect?.disabled) {
                await loadOptions(barangaySelect, `/api/barangays/${encodeURIComponent(citySelect.value)}`, 'barangay_id', 'barangay_name', 'Select barangay');
            }
        });

        closeEditStationBtn?.addEventListener('click', () => this.closeModal('editStationModal'));
        cancelEditStationBtn?.addEventListener('click', () => this.closeModal('editStationModal'));

        const loadOptions = async (select, url, valueKey, labelKey, placeholder) => {
            select.disabled = true;
            select.innerHTML = `<option value="">Loading…</option>`;
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Address options could not be loaded.');
                const rows = await response.json();
                select.innerHTML = `<option value="">${placeholder}</option>${rows.map((row) => `<option value="${this.escapeHtml(row[valueKey])}">${this.escapeHtml(row[labelKey])}</option>`).join('')}`;
                select.disabled = false;
            } catch (error) {
                select.innerHTML = `<option value="">Unavailable</option>`;
                this.showToast(error.message, 'error');
            }
        };

        provinceSelect?.addEventListener('change', async () => {
            barangaySelect.innerHTML = '<option value="">Select barangay</option>';
            barangaySelect.disabled = true;
            if (!provinceSelect.value) {
                citySelect.innerHTML = '<option value="">Select city</option>';
                citySelect.disabled = true;
                return;
            }
            await loadOptions(citySelect, `/api/cities/${encodeURIComponent(provinceSelect.value)}`, 'city_id', 'city_name', 'Select city');
        });

        citySelect?.addEventListener('change', async () => {
            if (!citySelect.value) {
                barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                barangaySelect.disabled = true;
                return;
            }
            await loadOptions(barangaySelect, `/api/barangays/${encodeURIComponent(citySelect.value)}`, 'barangay_id', 'barangay_name', 'Select barangay');
        });

        editStationForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const stationId = document.getElementById('editStationId')?.value;
            const payload = {
                station_name: document.getElementById('editStationName')?.value?.trim() || '',
                station_code: document.getElementById('editStationCode')?.value?.trim() || '',
                contact_number: document.getElementById('editStationContactNumber')?.value?.trim() || '',
                email: document.getElementById('editStationEmail')?.value?.trim() || '',
                province_id: provinceSelect?.value || '',
                city_id: citySelect?.value || '',
                barangay_id: barangaySelect?.value || '',
                street: document.getElementById('editStationStreet')?.value?.trim() || '',
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
                await this.loadCurrentTab();
                window.dispatchEvent(new CustomEvent('pyro:station-updated', { detail: data.data || {} }));
            } catch (error) {
                console.error('Error updating station:', error);
                this.showToast(error.message || 'Failed to update station information.', 'error');
            }
        });
    }

    async loadData() {
        return this.loadCurrentTab();
    }

    async loadCurrentTab() {
        this.showLoading(true);

        try {
            const params = new URLSearchParams({
                page: String(this.currentPage),
                per_page: String(this.perPage),
                sort_by: this.sortBy,
                sort_order: this.sortOrder
            });
            if (this.searchTerm.trim()) params.set('search', this.searchTerm.trim());
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.date && ['incidents', 'historical_records'].includes(this.currentTab)) params.set('date_filter', this.filters.date);
            if (this.filters.barangay && this.currentTab === 'historical_records') params.set('barangay_id', this.filters.barangay);

            const endpoint = this.currentTab === 'historical_records' ? 'historical-records' : this.currentTab;
            const payload = await this.fetchData(`/api/managements/${endpoint}?${params.toString()}`);
            this.data[this.currentTab] = Array.isArray(payload.data) ? payload.data : [];
            this.pagination = payload.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: this.perPage,
                total: this.data[this.currentTab].length,
                from: this.data[this.currentTab].length ? 1 : 0,
                to: this.data[this.currentTab].length
            };
            this.currentPage = this.pagination.current_page || 1;
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
            return data;
        } catch (error) {
            console.error(`Error fetching ${url}:`, error);
            throw error;
        }
    }

    updateStats() {
        const stats = {
            totalIncidents: this.currentTab === 'incidents' ? this.pagination.total : this.data.incidents.length,
            activeIncidents: this.data.incidents.filter((incident) => {
                const status = this.getIncidentStatusMeta(incident).value;
                return ['reported', 'responding', 'active'].includes(status);
            }).length,
            totalReports: this.currentTab === 'reports' ? this.pagination.total : this.data.reports.length,
            pendingReports: this.data.reports.filter(r => (r.review_status || 'submitted') === 'submitted').length,
            totalHistoricalRecords: this.currentTab === 'historical_records' ? this.pagination.total : this.data.historical_records.length,
            verifiedHistoricalRecords: this.data.historical_records.filter(record => record.is_verified || record.verification_status === 'verified').length,
            totalPersonnel: this.currentTab === 'personnel' ? this.pagination.total : this.data.personnel.length,
            totalEquipment: this.currentTab === 'equipment' ? this.pagination.total : this.data.equipment.length
        };

        document.getElementById('totalIncidents') && (document.getElementById('totalIncidents').textContent = stats.totalIncidents);
        document.getElementById('activeIncidents') && (document.getElementById('activeIncidents').textContent = `${stats.activeIncidents} active`);
        document.getElementById('totalReports') && (document.getElementById('totalReports').textContent = stats.totalReports);
        document.getElementById('pendingReports') && (document.getElementById('pendingReports').textContent = `${stats.pendingReports} submitted`);
        document.getElementById('totalHistoricalRecords') && (document.getElementById('totalHistoricalRecords').textContent = stats.totalHistoricalRecords);
        document.getElementById('verifiedHistoricalRecords') && (document.getElementById('verifiedHistoricalRecords').textContent = `${stats.verifiedHistoricalRecords} verified`);
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
        this.filters = {};
        this.sortBy = this.getDefaultSortField();
        this.sortOrder = 'desc';
        
        this.updateTableTitle();
        this.updateSearchPlaceholder();
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
        }

        const barangayFilter = document.getElementById('barangayFilter');
        if (barangayFilter) {
            barangayFilter.value = '';
        }

        this.updateFilterOptions();
        this.loadCurrentTab();
    }

    getDefaultSortField() {
        switch(this.currentTab) {
            case 'incidents': return 'created_at';
            case 'historical_records': return 'incident_date';
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
                historical_records: 'Historical Fire Incident Records',
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
            historical_records: 'Search by barangay, location, source, cause, or linked incident...',
            reports: 'Search by report stage, investigator, or incident...',
            personnel: 'Search by name, email, or account code...',
            equipment: 'Search by equipment name, type, or serial number...'
        };

        searchInput.placeholder = placeholders[this.currentTab] || 'Search...';
    }

    updateFilterOptions() {
        const filterSelect = document.getElementById('filterSelect');
        const dateFilter = document.getElementById('dateFilter');
        const barangayFilter = document.getElementById('barangayFilter');
        const barangayFilterSlot = document.getElementById('barangayFilterSlot');
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
            historical_records: [
                { value: '', label: 'All Records' },
                { value: 'verified', label: 'Verified' },
                { value: 'unverified', label: 'Needs Review' },
                { value: 'system_incident', label: 'System Incident' },
                { value: 'bfp_record', label: 'BFP Record' },
                { value: 'barangay_hall', label: 'Barangay Hall' },
                { value: 'lgu_record', label: 'LGU Record' },
                { value: 'research', label: 'Research' },
                { value: 'other', label: 'Other Source' }
            ],
            reports: [
                { value: '', label: 'All Review Status' },
                { value: 'submitted', label: 'Submitted' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' }
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
            dateFilter.style.display = ['incidents', 'historical_records'].includes(this.currentTab) ? '' : 'none';
        }

        if (barangayFilter && barangayFilterSlot) {
            barangayFilterSlot.style.display = this.currentTab === 'historical_records' ? '' : 'none';
            barangayFilter.innerHTML = '<option value="">All Barangays</option>';
            this.managedBarangays.forEach((barangay) => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name || `Barangay #${barangay.barangay_id}`;
                barangayFilter.appendChild(option);
            });
            barangayFilter.value = this.filters.barangay || '';
        }

        if (addNewBtn) {
            if (this.currentTab === 'incidents') {
                addNewBtn.style.display = '';
                addNewBtn.title = 'Add historical fire record';
                addNewBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span class="btn-label">Add Historical Fire</span>';
            } else if (this.currentTab === 'historical_records') {
                addNewBtn.style.display = '';
                addNewBtn.title = 'Add historical fire record';
                addNewBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span class="btn-label">Add Historical Record</span>';
            } else {
                addNewBtn.style.display = this.currentTab === 'reports' ? 'none' : '';
                addNewBtn.title = `Add ${this.currentTab.slice(0, -1)}`;
                addNewBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span class="btn-label">Add New</span>';
            }
        }
    }

    renderTable() {
        const data = this.data[this.currentTab] || [];
        this.renderTableHeader();
        this.renderTableBody(data);
        this.renderPagination(this.pagination.total || 0);
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
                case 'source_name':
                    aVal = a.source_name || '';
                    bVal = b.source_name || '';
                    break;
                case 'source_type':
                    aVal = a.source_type || '';
                    bVal = b.source_type || '';
                    break;
                case 'fire_classification':
                    aVal = a.fire_classification || '';
                    bVal = b.fire_classification || '';
                    break;
                case 'incident_count':
                    aVal = Number(a.incident_count || 0);
                    bVal = Number(b.incident_count || 0);
                    break;
                case 'estimated_damage':
                    aVal = Number(a.estimated_damage || 0);
                    bVal = Number(b.estimated_damage || 0);
                    break;
                case 'is_verified':
                    aVal = a.is_verified ? 1 : 0;
                    bVal = b.is_verified ? 1 : 0;
                    break;
                case 'incident_date':
                    aVal = a.incident_date || '';
                    bVal = b.incident_date || '';
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
                    : this.currentTab === 'historical_records'
                        ? (['verified', 'unverified'].includes(this.filters.status) ? item.verification_status : item.source_type)
                    : this.currentTab === 'reports'
                        ? (item.review_status || 'submitted')
                        : (item.status_id?.toString() || item.status || '');
                return statusValue === this.filters.status;
            });
        }

        if (this.filters.date && ['incidents', 'historical_records'].includes(this.currentTab)) {
            const today = new Date().toDateString();
            data = data.filter(item => {
                const itemDate = new Date(item.incident_date || item.created_at).toDateString();
                return itemDate === today;
            });
        }

        if (this.filters.barangay && this.currentTab === 'historical_records') {
            data = data.filter(item => String(item.barangay_id || '') === String(this.filters.barangay));
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
                    item.barangay_name,
                    item.reporter_name,
                    item.reported_by,
                    item.historical_records?.[0]?.source_name,
                    item.historical_records?.[0]?.incident_location,
                    item.historical_records?.[0]?.fire_cause
                ].filter(f => f).join(' ');
            case 'historical_records':
                return [
                    item.historical_fire_incident_id?.toString(),
                    item.incident_id?.toString(),
                    item.incident_title,
                    item.barangay_name,
                    item.city_name,
                    item.incident_location,
                    item.fire_classification,
                    item.fire_cause,
                    item.source_name,
                    item.source_type,
                    item.source_reference,
                    item.weather_condition,
                    item.notes
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
            incidents: ['Title', 'Location', 'Priority & status', 'Date', 'Actions'],
            historical_records: ['Record', 'Barangay & location', 'Fire details', 'Impact', 'Incident Date', 'Source & verification', 'Actions'],
            reports: ['Report', 'Stage & status', 'Incident', 'Investigator', 'Filed', 'Actions'],
            personnel: ['Name', 'Role & status', 'Account Code', 'Contact', 'Actions'],
            equipment: ['Equipment', 'Type & status', 'Available', 'Maintenance', 'Damaged', 'Actions']
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
                    this.currentPage = 1;
                    this.loadCurrentTab();
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
            const emptyColspan = {
                incidents: 5,
                historical_records: 7,
                reports: 6,
                personnel: 5,
                equipment: 6
            }[this.currentTab] || 8;
            tbody.innerHTML = `
                <tr class="table-empty-row">
                    <td colspan="${emptyColspan}" class="empty-state">
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
            historical_records: (item) => this.renderHistoricalRecordRow(item),
            reports: (item) => this.renderReportRow(item),
            personnel: (item) => this.renderPersonnelRow(item),
            equipment: (item) => this.renderEquipmentRow(item)
        };

        const renderer = renderers[this.currentTab];
        tbody.innerHTML = data.map((item) => renderer(item)).join('');
    }

    formatLabel(value, fallback = 'N/A') {
        const normalized = String(value || '').trim();
        if (!normalized) return fallback;
        return normalized.replace(/[_-]/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    getReporterName(incident) {
        return incident?.reporter_name
            || [incident?.reporter?.first_name, incident?.reporter?.middle_name, incident?.reporter?.last_name].filter(Boolean).join(' ').trim();
    }

    getIncidentDisplayTitle(incident) {
        const baseTitle = incident?.marker?.title || incident?.title || 'Untitled Incident';
        const reporterName = this.getReporterName(incident);
        const isAnonymous = String(baseTitle).toLowerCase().includes('anonymous') || !reporterName;

        return isAnonymous ? baseTitle : `${baseTitle} - reported by ${reporterName}`;
    }

    getIncidentLocationText(incident, historical = null) {
        const barangayName = incident?.barangay_name
            || incident?.barangay?.barangay_name
            || historical?.barangay?.barangay_name
            || '';
        const description = incident?.location_description
            || historical?.incident_location
            || incident?.marker?.description
            || '';

        if (barangayName && description) {
            return `${barangayName} - ${description}`;
        }

        if (barangayName) {
            return barangayName;
        }

        if (description) {
            return description;
        }

        return incident?.marker ? `${incident.marker.lat}, ${incident.marker.lng}` : 'Location not recorded';
    }

    renderPriorityStatus(priority, status, options = {}) {
        const normalizedPriority = ['critical', 'high'].includes(priority) ? 'high' : (priority || 'medium');
        const priorityLabel = this.formatLabel(normalizedPriority, 'Medium');
        const statusLabel = this.formatLabel(status, 'Reported');
        return `
            <div class="management-priority-stack">
                <span class="management-priority-pill management-priority-pill--${normalizedPriority}">
                    <span class="management-priority-dot"></span>${this.escapeHtml(priorityLabel)}
                </span>
                <span class="management-state-label management-state-label--${this.escapeHtml(String(status || 'reported').replace(/[^a-z0-9_-]/gi, '-').toLowerCase())}">
                    <span class="management-state-dot"></span>${this.escapeHtml(statusLabel)}
                </span>
                ${options.extra ? `<span class="management-row-tag">${this.escapeHtml(options.extra)}</span>` : ''}
            </div>
        `;
    }

    renderRowActions({ includeEdit = true, includeDelete = true, extra = '' } = {}) {
        return `
            <div class="management-row-actions">
                <button class="action-btn view management-icon-action" title="View" aria-label="View"><i class="fa-regular fa-eye"></i></button>
                ${includeEdit ? '<button class="action-btn edit management-icon-action" title="Edit" aria-label="Edit"><i class="fa-regular fa-pen-to-square"></i></button>' : ''}
                ${extra}
                ${includeDelete ? `<div class="management-more-menu">
                    <button type="button" class="management-icon-action management-more-trigger" title="More actions" aria-label="More actions"><i class="fa-solid fa-ellipsis"></i></button>
                    <div class="management-more-dropdown">
                        <button class="action-btn delete management-more-item" title="Delete"><i class="fa-regular fa-trash-can"></i><span>Delete</span></button>
                    </div>
                </div>` : ''}
            </div>
        `;
    }

    renderIncidentRow(incident) {
        const priority = this.getIncidentPriorityValue(incident);
        const historical = this.getPrimaryHistoricalRecord(incident);
        const status = this.getIncidentStatusMeta(incident).value;
        const priorityAccent = ['critical', 'high'].includes(priority) ? 'high' : priority;
        const title = this.getIncidentDisplayTitle(incident);
        const location = this.getIncidentLocationText(incident, historical);
        const dateSource = historical?.incident_date || incident.created_at;
        const date = dateSource ? new Date(dateSource).toLocaleDateString() : 'N/A';
        const sourceLabel = historical ? (historical.source_name || 'Historical fire record') : (incident.source_label || 'System report');
        const description = incident.marker?.description || incident.description || '';
        const historicalBadge = historical ? '<span class="management-row-tag management-row-tag--history">Historical fire record</span>' : '';

        return `
            <tr class="management-data-row management-data-row--${priorityAccent}" data-id="${incident.incident_id}">
                <td data-label="Title">
                    <div class="management-primary-cell">
                        <strong>${this.escapeHtml(title)}</strong>
                        <div class="management-row-meta">
                            <span class="management-row-tag">${this.escapeHtml(sourceLabel)}</span>
                            ${historicalBadge}
                        </div>
                        ${description ? `<small>${this.escapeHtml(description)}</small>` : ''}
                    </div>
                </td>
                <td data-label="Location">
                    <div class="management-location-cell">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>${this.escapeHtml(location)}</span>
                    </div>
                </td>
                <td data-label="Priority & status">${this.renderPriorityStatus(priority, status)}</td>
                <td data-label="Date"><span class="management-date-text">${date}</span></td>
                <td data-label="Actions">
                    ${this.renderRowActions()}
                </td>
            </tr>
        `;
    }

    formatCurrency(value) {
        if (value === null || value === undefined || value === '') return 'N/A';
        return `PHP ${Number(value || 0).toLocaleString()}`;
    }

    renderHistoricalRecordRow(record) {
        const verified = record.is_verified || record.verification_status === 'verified';
        const status = verified ? 'verified' : 'needs review';
        const priority = verified ? 'low' : 'medium';
        const accent = verified ? 'low' : 'medium';
        const date = record.incident_date ? new Date(record.incident_date).toLocaleDateString() : 'N/A';
        const sourceLabel = this.formatLabel(record.source_type || 'other', 'Other Source');
        const impact = `${Number(record.incident_count || 0)} fire(s), ${Number(record.injured_count || 0)} injured, ${Number(record.death_count || 0)} death(s)`;
        const locationParts = [record.barangay_name, record.incident_location].filter(Boolean);
        const location = locationParts.length ? locationParts.join(' - ') : 'Location not recorded';
        const title = record.incident_title || `${this.formatLabel(record.fire_classification || 'undetermined')} fire record`;

        return `
            <tr class="management-data-row management-data-row--${accent}" data-id="${record.historical_fire_incident_id}">
                <td data-label="Record">
                    <div class="management-primary-cell">
                        <strong>${this.escapeHtml(title)}</strong>
                        <div class="management-row-meta">
                            <span class="management-row-tag management-row-tag--history">Historical fire record</span>
                            ${record.source_reference ? `<span class="management-row-tag">${this.escapeHtml(record.source_reference)}</span>` : ''}
                        </div>
                        ${record.fire_cause ? `<small>${this.escapeHtml(record.fire_cause)}</small>` : ''}
                    </div>
                </td>
                <td data-label="Barangay & location">
                    <div class="management-location-cell">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>${this.escapeHtml(location)}</span>
                    </div>
                </td>
                <td data-label="Fire details">${this.renderPriorityStatus(priority, record.fire_classification || 'undetermined', { extra: record.weather_condition || '' })}</td>
                <td data-label="Impact">
                    <div class="management-primary-cell">
                        <strong>${this.escapeHtml(impact)}</strong>
                        <small>${this.escapeHtml(this.formatCurrency(record.estimated_damage))}</small>
                    </div>
                </td>
                <td data-label="Incident Date"><span class="management-date-text">${date}</span></td>
                <td data-label="Source & verification">${this.renderPriorityStatus(priority, status, { extra: record.source_name || sourceLabel })}</td>
                <td data-label="Actions">
                    ${this.renderRowActions()}
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
            <tr class="management-data-row management-data-row--medium" data-id="${report.id}">
                <td data-label="Report"><div class="management-primary-cell"><strong>${this.escapeHtml(report.title)}</strong><div class="management-row-meta"><span class="management-row-tag">${this.escapeHtml(report.report_kind || 'investigation')}</span></div></div></td>
                <td data-label="Stage & status">${this.renderPriorityStatus('medium', reviewStatus, { extra: report.type || 'Investigation' })}</td>
                <td data-label="Incident"><span class="management-wrap-text">${this.escapeHtml(report.incident_title || 'N/A')}</span></td>
                <td data-label="Investigator">${this.escapeHtml(report.investigator_name || report.author || 'Unknown')}</td>
                <td data-label="Filed">${report.created_at ? new Date(report.created_at).toLocaleDateString() : 'N/A'}</td>
                <td data-label="Actions">
                    ${this.renderRowActions({
                        extra: `${canApprove ? '<button class="action-btn approve management-icon-action" title="Approve" aria-label="Approve"><i class="fa-solid fa-check"></i></button>' : ''}${canReject ? '<button class="action-btn reject management-icon-action" title="Reject" aria-label="Reject"><i class="fa-solid fa-xmark"></i></button>' : ''}`
                    })}
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
            <tr class="management-data-row management-data-row--low" data-id="${person.user_id}">
                <td data-label="Name">
                    <div class="avatar-group">
                        <span class="avatar-sm">${this.escapeHtml(name.substring(0, 2).toUpperCase())}</span>
                        <strong>${this.escapeHtml(name)}</strong>
                    </div>
                </td>
                <td data-label="Role & status">${this.renderPriorityStatus('low', status, { extra: role })}</td>
                <td data-label="Account Code"><code>${this.escapeHtml(person.account_code || 'N/A')}</code></td>
                <td data-label="Contact">${this.escapeHtml(person.contact_number || 'N/A')}</td>
                <td data-label="Actions">
                    ${this.renderRowActions()}
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
            <tr class="management-data-row management-data-row--medium" data-id="${equipment.equipment_id}">
                <td data-label="Equipment">
                    <div class="management-primary-cell">
                        <strong>${this.escapeHtml(equipment.equipment_name)}</strong>
                        ${equipment.category_name || equipment.category ? `<div class="management-row-meta"><span class="management-row-tag">${this.escapeHtml(equipment.category_name || equipment.category)}</span></div>` : ''}
                    </div>
                </td>
                <td data-label="Type & status">${this.renderPriorityStatus(equipment.status === 'damaged' ? 'high' : equipment.status === 'available' ? 'low' : 'medium', equipment.status || 'available', { extra: equipment.type || 'General' })}</td>
                <td data-label="Available">${Number(equipment.available_quantity || 0)}</td>
                <td data-label="Maintenance">${Number(equipment.maintenance_quantity || 0)}</td>
                <td data-label="Damaged">${Number(equipment.damaged_quantity || 0)}</td>
                <td data-label="Actions">
                    ${this.renderRowActions()}
                </td>
            </tr>
        `;
    }

    getItemIdentifier(item) {
        return item.id || item.historical_fire_incident_id || item.incident_id || item.user_id || item.equipment_id || item.report_id || item.equipment_station_id;
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
        const totalPages = Math.max(1, this.pagination.last_page || Math.ceil(totalItems / this.perPage));
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
        if (page < 1 || page > (this.pagination.last_page || 1)) return;
        this.currentPage = page;
        this.loadCurrentTab();
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
            headers = ['Title', 'Location', 'Priority', 'Status', 'Date'];
        } else if (this.currentTab === 'historical_records') {
            headers = ['Record', 'Barangay', 'Location', 'Classification', 'Cause', 'Incident Count', 'Injured', 'Deaths', 'Estimated Damage', 'Source', 'Verification', 'Incident Date'];
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
                    case 'Title': value = this.getIncidentDisplayTitle(row); break;
                    case 'Record': value = row.incident_title || `${this.formatLabel(row.fire_classification || 'undetermined')} fire record`; break;
                    case 'Report': value = row.title || ''; break;
                    case 'Location':
                        value = this.currentTab === 'historical_records'
                            ? (row.incident_location || '')
                            : this.getIncidentLocationText(row, this.getPrimaryHistoricalRecord(row));
                        break;
                    case 'Barangay': value = row.barangay_name || ''; break;
                    case 'Classification': value = row.fire_classification || ''; break;
                    case 'Cause': value = row.fire_cause || ''; break;
                    case 'Incident Count': value = row.incident_count ?? ''; break;
                    case 'Injured': value = row.injured_count ?? ''; break;
                    case 'Deaths': value = row.death_count ?? ''; break;
                    case 'Estimated Damage': value = row.estimated_damage ?? ''; break;
                    case 'Source': value = row.source_name || row.source_type || ''; break;
                    case 'Verification': value = row.verification_status || (row.is_verified ? 'verified' : 'unverified'); break;
                    case 'Incident Date': value = row.incident_date || ''; break;
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
        this.currentEditId = null;
        this.currentItem = null;

        const titles = {
            incidents: 'Add Historical Fire Record',
            historical_records: 'Add Historical Fire Record',
            personnel: 'Add Personnel',
            equipment: 'Add Equipment'
        };

        if (this.currentTab === 'reports') {
            this.showToast('Create investigation reports from the firefighter reporting workflow.', 'info');
            return;
        }
        this.hideIncidentEditStepIndicator();

        if (['incidents', 'historical_records'].includes(this.currentTab) && !this.managedBarangays.length) {
            this.showToast('No covered barangays are assigned to this station yet.', 'warning');
            return;
        }

        document.getElementById('modalTitle').textContent = titles[this.currentTab] || 'Add New Item';
        
        const modalBody = document.querySelector('#formModal .modal-body');
        modalBody.innerHTML = this.renderAddForm();
        
        document.getElementById('formModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        this.initializeCoordinatePickers();
    }

    renderAddForm() {
        switch(this.currentTab) {
            case 'incidents':
            case 'historical_records':
                return this.renderIncidentAddForm();
            case 'personnel':
                return this.renderPersonnelAddForm();
            case 'equipment':
                return this.renderEquipmentAddForm();
            default:
                return `<p>Form not available for ${this.currentTab}</p>`;
        }
    }

    getDefaultCoordinateCenter() {
        const candidates = [
            this.currentItem?.marker,
            window.currentStationData?.marker,
            Array.isArray(window.stationDb) ? window.stationDb[0]?.marker : null,
            Array.isArray(window.incidentDb) ? window.incidentDb.find((incident) => incident?.marker?.lat && incident?.marker?.lng)?.marker : null
        ].filter(Boolean);

        for (const candidate of candidates) {
            const lat = Number(candidate.lat);
            const lng = Number(candidate.lng);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                return { lat, lng };
            }
        }

        return { lat: 15.9753, lng: 120.5713 };
    }

    renderCoordinatePicker({ lat = '', lng = '', required = true } = {}) {
        this.coordinatePickerSerial += 1;
        const id = `coordinatePickerMap${this.coordinatePickerSerial}`;
        const fallback = this.getDefaultCoordinateCenter();
        const initialLat = Number.isFinite(Number(lat)) ? Number(lat) : fallback.lat;
        const initialLng = Number.isFinite(Number(lng)) ? Number(lng) : fallback.lng;
        const hasExisting = Number.isFinite(Number(lat)) && Number.isFinite(Number(lng));

        return `
            <div class="form-group coordinate-picker" data-coordinate-picker data-initial-lat="${initialLat}" data-initial-lng="${initialLng}">
                <label class="form-label">Map Coordinate ${required ? '*' : ''}</label>
                <input type="hidden" name="lat" value="${hasExisting ? initialLat : ''}" ${required ? 'required' : ''}>
                <input type="hidden" name="lng" value="${hasExisting ? initialLng : ''}" ${required ? 'required' : ''}>
                <div class="coordinate-picker__toolbar">
                    <span class="coordinate-picker__readout" data-coordinate-readout>
                        <i class="fa-solid fa-location-crosshairs"></i>
                        ${hasExisting ? `${initialLat.toFixed(6)}, ${initialLng.toFixed(6)}` : 'Click the map to choose a point'}
                    </span>
                    <button type="button" class="coordinate-picker__button" data-use-current-location>
                        <i class="fa-solid fa-location-arrow"></i>
                        Use my location
                    </button>
                </div>
                <div id="${id}" class="coordinate-picker__map" data-coordinate-map></div>
                <p class="coordinate-picker__hint">Click the map or drag the marker to set the incident coordinate.</p>
            </div>
        `;
    }

    destroyCoordinatePickers() {
        this.coordinatePickers.forEach((map) => {
            try { map.remove(); } catch (error) { console.warn('Unable to remove coordinate picker map', error); }
        });
        this.coordinatePickers = [];
    }

    initializeCoordinatePickers() {
        this.destroyCoordinatePickers();

        window.requestAnimationFrame(() => {
            setTimeout(() => {
                if (!window.L) {
                    this.showToast('Map picker could not load. Please refresh the page.', 'error');
                    return;
                }

                document.querySelectorAll('[data-coordinate-picker]').forEach((picker) => {
                    const mapEl = picker.querySelector('[data-coordinate-map]');
                    const latInput = picker.querySelector('input[name="lat"]');
                    const lngInput = picker.querySelector('input[name="lng"]');
                    const readout = picker.querySelector('[data-coordinate-readout]');
                    const useLocationBtn = picker.querySelector('[data-use-current-location]');
                    const initialLat = Number(picker.dataset.initialLat);
                    const initialLng = Number(picker.dataset.initialLng);
                    const center = Number.isFinite(initialLat) && Number.isFinite(initialLng)
                        ? [initialLat, initialLng]
                        : [15.9753, 120.5713];
                    const hasSelectedCoordinate = Number.isFinite(Number(latInput?.value)) && Number.isFinite(Number(lngInput?.value));

                    const map = L.map(mapEl, {
                        zoomControl: true,
                        attributionControl: false
                    }).setView(center, hasSelectedCoordinate ? 16 : 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19
                    }).addTo(map);

                    let marker = null;
                    const updateCoordinate = (latlng, zoom = true) => {
                        const lat = Number(latlng.lat);
                        const lng = Number(latlng.lng);
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                        latInput.value = lat.toFixed(7);
                        lngInput.value = lng.toFixed(7);
                        readout.innerHTML = `<i class="fa-solid fa-location-crosshairs"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                        if (!marker) {
                            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                            marker.on('dragend', () => updateCoordinate(marker.getLatLng(), false));
                        } else {
                            marker.setLatLng([lat, lng]);
                        }

                        if (zoom) map.setView([lat, lng], Math.max(map.getZoom(), 16));
                    };

                    if (hasSelectedCoordinate) {
                        updateCoordinate({ lat: Number(latInput.value), lng: Number(lngInput.value) }, false);
                    }

                    map.on('click', (event) => updateCoordinate(event.latlng));

                    useLocationBtn?.addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            this.showToast('Your browser does not support location detection.', 'warning');
                            return;
                        }

                        useLocationBtn.disabled = true;
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                updateCoordinate({
                                    lat: position.coords.latitude,
                                    lng: position.coords.longitude
                                });
                                useLocationBtn.disabled = false;
                            },
                            () => {
                                this.showToast('Unable to get your current location.', 'warning');
                                useLocationBtn.disabled = false;
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
                        );
                    });

                    setTimeout(() => map.invalidateSize(), 120);
                    this.coordinatePickers.push(map);
                });
            }, 80);
        });
    }

    validateCoordinatePickers() {
        const missingPicker = Array.from(document.querySelectorAll('[data-coordinate-picker]')).find((picker) => {
            const latInput = picker.querySelector('input[name="lat"]');
            const lngInput = picker.querySelector('input[name="lng"]');
            const requiresCoordinate = latInput?.hasAttribute('required') || lngInput?.hasAttribute('required');

            return requiresCoordinate && (!latInput?.value || !lngInput?.value);
        });

        if (missingPicker) {
            this.activateIncidentEditTab('location');
            missingPicker.scrollIntoView({ behavior: 'smooth', block: 'center' });
            this.showToast('Choose an incident coordinate on the map before saving.', 'warning');
            return false;
        }

        return true;
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
                ${this.renderCoordinatePicker({ required: true })}
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

    renderReportEditForm(report) {
        const allowed = report.report_kind === 'spot'
            ? ['memorandum_for','place_of_occurrence','involved_structure','establishment_name','occupant','owner','occupancy','estimated_damage','estimated_area','dead','injured','highest_alarm_level','affected_establishments_count','description','fatality_details','details_of_investigation','disposition']
            : report.report_kind === 'progress'
                ? ['memorandum_for','mission_order_number','mission_order_date','responsible_arson','fire_cause','facts_of_case','disposition','report_completed_at']
                : ['memorandum_for','office_address','alarm_at','establishment_burned','fire_victims','origin_of_fire','cause_of_fire','exact_address','total_damage','facts_of_case','discussion','findings','recommendation','report_completed_at'];
        const longFields = new Set(['description','fatality_details','details_of_investigation','disposition','facts_of_case','discussion','findings','recommendation','fire_victims']);
        const dateFields = new Set(['mission_order_date','alarm_at','report_completed_at']);
        const numericFields = new Set(['estimated_damage','estimated_area','dead','injured','affected_establishments_count','total_damage']);
        const required = new Set(report.report_kind === 'spot' ? ['occupancy','estimated_damage','estimated_area'] : report.report_kind === 'progress' ? ['fire_cause'] : ['exact_address','total_damage']);
        const controls = allowed.map((field) => {
            const label = field.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
            const raw = report.fields?.[field] ?? '';
            const value = dateFields.has(field) && raw ? String(raw).slice(0, 16).replace(' ', 'T') : raw;
            if (field === 'occupancy') return `<div class="form-group"><label class="form-label">${label}</label><select class="form-select" name="${field}" required>${['residential','commercial','industrial'].map(v => `<option value="${v}" ${String(value) === v ? 'selected' : ''}>${v[0].toUpperCase()+v.slice(1)}</option>`).join('')}</select></div>`;
            if (longFields.has(field)) return `<div class="form-group"><label class="form-label">${label}${required.has(field) ? ' *' : ''}</label><textarea class="form-textarea" name="${field}" rows="3" ${required.has(field) ? 'required' : ''}>${this.escapeHtml(value)}</textarea></div>`;
            const type = dateFields.has(field) ? (field === 'mission_order_date' ? 'date' : 'datetime-local') : numericFields.has(field) ? 'number' : 'text';
            return `<div class="form-group"><label class="form-label">${label}${required.has(field) ? ' *' : ''}</label><input class="form-input" type="${type}" name="${field}" value="${this.escapeHtml(value)}" ${numericFields.has(field) ? 'min="0" step="0.01"' : ''} ${required.has(field) ? 'required' : ''}></div>`;
        }).join('');
        return `<form id="dataForm" class="edit-form"><div class="form-grid">${controls}</div></form>`;
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
            this.currentEditId = id;
            this.currentItem = item;
            this.hideIncidentEditStepIndicator();
            document.getElementById('modalTitle').textContent = `Edit ${item.type || 'Investigation Report'}`;
            document.querySelector('#formModal .modal-body').innerHTML = this.renderReportEditForm(item);
            document.getElementById('formModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            return;
        }

        this.currentEditId = id;
        this.currentItem = item;
        document.getElementById('modalTitle').textContent = this.currentTab === 'historical_records'
            ? 'Edit Historical Fire Record'
            : `Edit ${this.currentTab.slice(0, -1)}`;
        
        const modalBody = document.querySelector('#formModal .modal-body');
        
        switch(this.currentTab) {
            case 'incidents':
                modalBody.innerHTML = this.renderIncidentEditForm(item);
                break;
            case 'historical_records':
                modalBody.innerHTML = this.renderHistoricalRecordEditForm(item);
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
        this.initializeCoordinatePickers();
        if (this.currentTab === 'incidents') {
            this.activateIncidentEditTab('details');
        } else {
            this.hideIncidentEditStepIndicator();
        }
    }

    renderHistoricalRecordEditForm(record) {
        const barangayOptions = this.managedBarangays.length
            ? this.managedBarangays.map((barangay) => `<option value="${barangay.barangay_id}" ${String(record.barangay_id || '') === String(barangay.barangay_id) ? 'selected' : ''}>${this.escapeHtml(barangay.barangay_name)}</option>`).join('')
            : '<option value="">No covered barangays available</option>';
        const sourceTypes = ['research', 'barangay_hall', 'bfp_record', 'lgu_record', 'system_incident', 'other'];

        return `
            <form id="dataForm" class="edit-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Record Title</label>
                        <input type="text" class="form-input" name="title" value="${this.escapeHtml(record.incident_title || '')}" placeholder="Linked incident title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Verification</label>
                        <select class="form-select" name="is_verified">
                            <option value="1" ${record.is_verified ? 'selected' : ''}>Verified</option>
                            <option value="0" ${!record.is_verified ? 'selected' : ''}>Needs Review</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3">${this.escapeHtml(record.description || '')}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Incident Date *</label>
                        <input type="date" class="form-input" name="incident_date" value="${record.incident_date ? String(record.incident_date).slice(0, 10) : ''}" max="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Incident Time</label>
                        <input type="time" class="form-input" name="incident_time" value="${record.incident_time ? String(record.incident_time).slice(0, 5) : ''}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Covered Barangay *</label>
                        <select class="form-select" name="barangay_id" required>
                            <option value="">Select barangay</option>
                            ${barangayOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Classification *</label>
                        <select class="form-select" name="fire_classification" required>
                            <option value="accidental" ${record.fire_classification === 'accidental' ? 'selected' : ''}>Accidental</option>
                            <option value="intentional" ${record.fire_classification === 'intentional' ? 'selected' : ''}>Intentional</option>
                            <option value="undetermined" ${record.fire_classification === 'undetermined' ? 'selected' : ''}>Undetermined</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Incident Location *</label>
                    <input type="text" class="form-input" name="incident_location" value="${this.escapeHtml(record.incident_location || '')}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fire Cause</label>
                    <input type="text" class="form-input" name="fire_cause" value="${this.escapeHtml(record.fire_cause || '')}">
                </div>
                ${this.renderCoordinatePicker({ lat: record.marker_lat || record.lat || '', lng: record.marker_lng || record.lng || '', required: false })}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Incident Count</label>
                        <input type="number" class="form-input" name="incident_count" min="1" value="${record.incident_count ?? 1}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimated Damage</label>
                        <input type="number" class="form-input" name="estimated_damage" min="0" step="0.01" value="${record.estimated_damage ?? ''}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Injured Count</label>
                        <input type="number" class="form-input" name="injured_count" min="0" value="${record.injured_count ?? 0}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Death Count</label>
                        <input type="number" class="form-input" name="death_count" min="0" value="${record.death_count ?? 0}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Source Name</label>
                        <input type="text" class="form-input" name="source_name" value="${this.escapeHtml(record.source_name || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source Type</label>
                        <select class="form-select" name="source_type">
                            ${sourceTypes.map((type) => `<option value="${type}" ${record.source_type === type ? 'selected' : ''}>${this.formatLabel(type)}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Source Reference</label>
                    <input type="text" class="form-input" name="source_reference" value="${this.escapeHtml(record.source_reference || '')}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Weather Condition</label>
                        <input type="text" class="form-input" name="weather_condition" value="${this.escapeHtml(record.weather_condition || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wind Direction</label>
                        <input type="text" class="form-input" name="wind_direction" value="${this.escapeHtml(record.wind_direction || '')}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Temperature (C)</label>
                        <input type="number" class="form-input" name="temperature" step="0.1" value="${record.temperature ?? ''}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Humidity (%)</label>
                        <input type="number" class="form-input" name="humidity" min="0" max="100" step="0.1" value="${record.humidity ?? ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Wind Speed</label>
                    <input type="number" class="form-input" name="wind_speed" min="0" step="0.1" value="${record.wind_speed ?? ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" name="notes" rows="4">${this.escapeHtml(record.notes || '')}</textarea>
                </div>
            </form>
        `;
    }

    renderOptionalLabel() {
        return '<span class="optional-label">optional</span>';
    }

    renderIncidentEditTabButton(key, label, active = false) {
        return `<button type="button" class="incident-edit-tab ${active ? 'is-active' : ''}" data-incident-edit-tab="${key}">${this.escapeHtml(label)}</button>`;
    }

    renderTeamAssignmentSelect(selectedTeamId = '') {
        const teamOptions = this.managementTeams.length
            ? this.managementTeams.map((team) => {
                const labelParts = [
                    team.team_name || `Team #${team.team_id}`,
                    team.status ? `${team.status}` : '',
                    team.station_name ? team.station_name : ''
                ].filter(Boolean);
                return `<option value="${team.team_id}" ${String(selectedTeamId || '') === String(team.team_id) ? 'selected' : ''}>${this.escapeHtml(labelParts.join(' - '))}</option>`;
            }).join('')
            : '';

        return `
            <div class="form-group">
                <label class="form-label">Assigned Team ${this.renderOptionalLabel()}</label>
                <select class="form-select" name="team_id" ${this.managementTeams.length ? '' : 'disabled'}>
                    <option value="">Unassigned</option>
                    ${teamOptions || '<option value="">No teams available</option>'}
                </select>
            </div>
        `;
    }

    renderSourceReferenceField(reference) {
        const formatted = this.formatHistoricalReference(reference);

        return `
            <div class="form-group">
                <label class="form-label">Source Reference</label>
                <input type="hidden" name="source_reference" value="${this.escapeHtml(formatted.raw)}">
                <div class="readonly-reference" title="${this.escapeHtml(formatted.raw || formatted.label)}">
                    <i class="fa-solid fa-lock"></i>
                    <input type="text" value="${this.escapeHtml(formatted.label)}" readonly disabled aria-label="Source reference">
                </div>
            </div>
        `;
    }

    renderGpsPreview(incident, descriptionText = '') {
        const lat = incident.marker?.lat ?? '';
        const lng = incident.marker?.lng ?? '';
        const parsed = this.parseIncidentDescription(descriptionText);
        const technical = parsed.technical ? ` - ${parsed.technical}` : '';
        const coordinate = lat && lng ? `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}` : 'No coordinate captured yet';

        return `<div class="incident-gps-preview">${this.escapeHtml(`${coordinate} - captured via mobile GPS${technical}`)}</div>`;
    }

    activateIncidentEditTab(tabKey) {
        const form = document.getElementById('dataForm');
        if (!form || !form.querySelector('[data-incident-edit-tab]')) return;

        const tabs = Array.from(form.querySelectorAll('[data-incident-edit-tab]'));
        const panes = Array.from(form.querySelectorAll('[data-incident-edit-pane]'));
        const activeButton = tabs.find((button) => button.dataset.incidentEditTab === tabKey) || tabs[0];
        const activeKey = activeButton?.dataset.incidentEditTab || 'details';

        tabs.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.incidentEditTab === activeKey);
        });
        panes.forEach((pane) => {
            pane.hidden = pane.dataset.incidentEditPane !== activeKey;
        });

        const activeInput = form.querySelector('input[name="_active_incident_edit_tab"]');
        if (activeInput) {
            activeInput.value = activeKey;
        }

        this.updateIncidentEditStepIndicator(activeKey);
        setTimeout(() => {
            this.coordinatePickers.forEach((map) => {
                try { map.invalidateSize(); } catch (error) { console.warn('Unable to resize map', error); }
            });
        }, 80);
    }

    updateIncidentEditStepIndicator(activeKey = 'details') {
        const indicator = document.getElementById('incidentEditStepIndicator');
        if (!indicator) return;

        const steps = [
            ['details', 'Details'],
            ['location', 'Location & impact'],
            ['source', 'Source & weather'],
            ['notes', 'Notes']
        ];
        const index = Math.max(0, steps.findIndex(([key]) => key === activeKey));
        const [, label] = steps[index] || steps[0];

        indicator.innerHTML = `Step ${index + 1} of 4 &middot; ${label}`;
        indicator.style.display = '';
    }

    hideIncidentEditStepIndicator() {
        const indicator = document.getElementById('incidentEditStepIndicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    getIncidentEditTabForField(fieldName) {
        const map = {
            title: 'details',
            description: 'details',
            incident_date: 'details',
            incident_time: 'details',
            barangay_id: 'details',
            fire_classification: 'details',
            team_id: 'details',
            lat: 'location',
            lng: 'location',
            incident_location: 'location',
            fire_cause: 'location',
            incident_count: 'location',
            estimated_damage: 'location',
            injured_count: 'location',
            death_count: 'location',
            source_name: 'source',
            source_reference: 'source',
            weather_condition: 'source',
            wind_direction: 'source',
            temperature: 'source',
            humidity: 'source',
            wind_speed: 'source',
            notes: 'notes'
        };

        return map[fieldName] || 'details';
    }

    focusFirstInvalidField(form) {
        const invalidField = form.querySelector(':invalid');
        if (!invalidField) return true;

        this.activateIncidentEditTab(this.getIncidentEditTabForField(invalidField.name));
        setTimeout(() => {
            invalidField.reportValidity();
            invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            invalidField.focus({ preventScroll: true });
        }, 80);

        return false;
    }

    renderIncidentEditFormLegacy(incident) {
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
                    ${this.renderCoordinatePicker({ lat: incident.marker?.lat || '', lng: incident.marker?.lng || '', required: true })}
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
                ${this.renderCoordinatePicker({ lat: incident.marker?.lat || '', lng: incident.marker?.lng || '', required: false })}
                <div class="form-group">
                    <label class="form-label">Reported By</label>
                    <input type="text" class="form-input" name="reported_by" value="${this.escapeHtml(incident.reported_by || '')}">
                </div>
            </form>
        `;
    }

    renderIncidentEditForm(incident) {
        const historical = this.getPrimaryHistoricalRecord(incident);

        if (historical) {
            const description = incident.marker?.description || incident.description || '';
            const barangayOptions = this.managedBarangays.length
                ? this.managedBarangays.map((barangay) => `<option value="${barangay.barangay_id}" ${String(historical.barangay_id || '') === String(barangay.barangay_id) ? 'selected' : ''}>${this.escapeHtml(barangay.barangay_name)}</option>`).join('')
                : '<option value="">No covered barangays available</option>';

            return `
                <form id="dataForm" class="edit-form incident-edit-form">
                    <input type="hidden" name="entry_mode" value="historical_fire_record">
                    <input type="hidden" name="_active_incident_edit_tab" value="details">
                    <div class="info-card" style="margin-bottom:16px;">
                        <div class="card-content">
                            <p style="margin:0;font-size:0.92rem;color:#475569;">This incident is stored as a historical BFP fire record. Updating this form will update both the linked incident marker and the historical fire details.</p>
                        </div>
                    </div>
                    <div class="incident-edit-tabs" role="tablist" aria-label="Edit incident sections">
                        ${this.renderIncidentEditTabButton('details', 'Details', true)}
                        ${this.renderIncidentEditTabButton('location', 'Location & impact')}
                        ${this.renderIncidentEditTabButton('source', 'Source & weather')}
                        ${this.renderIncidentEditTabButton('notes', 'Notes')}
                    </div>
                    <section class="incident-edit-pane" data-incident-edit-pane="details">
                        <div class="form-group">
                            <label class="form-label">Incident Title *</label>
                            <input type="text" class="form-input" name="title" value="${this.escapeHtml(incident.marker?.title || incident.title || '')}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(description)}</textarea>
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
                        ${this.renderTeamAssignmentSelect(incident.team_id || '')}
                    </section>
                    <section class="incident-edit-pane" data-incident-edit-pane="location" hidden>
                        ${this.renderCoordinatePicker({ lat: incident.marker?.lat || '', lng: incident.marker?.lng || '', required: true })}
                        ${this.renderGpsPreview(incident, description)}
                        <div class="form-group">
                            <label class="form-label">Incident Location / Landmark *</label>
                            <input type="text" class="form-input" name="incident_location" value="${this.escapeHtml(historical.incident_location || '')}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Cause ${this.renderOptionalLabel()}</label>
                            <input type="text" class="form-input" name="fire_cause" placeholder="Not recorded" value="${this.escapeHtml(historical.fire_cause || '')}">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Incident Count</label>
                                <input type="number" class="form-input" name="incident_count" min="1" value="${historical.incident_count ?? 1}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estimated Damage ${this.renderOptionalLabel()}</label>
                                <input type="number" class="form-input" name="estimated_damage" min="0" step="0.01" placeholder="Not recorded" value="${historical.estimated_damage ?? ''}">
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
                    </section>
                    <section class="incident-edit-pane" data-incident-edit-pane="source" hidden>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Source Name</label>
                                <input type="text" class="form-input" name="source_name" value="${this.escapeHtml(historical.source_name || '')}">
                            </div>
                            ${this.renderSourceReferenceField(historical.source_reference)}
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Weather Condition ${this.renderOptionalLabel()}</label>
                                <input type="text" class="form-input" name="weather_condition" placeholder="Not recorded" value="${this.escapeHtml(historical.weather_condition || '')}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Wind Direction ${this.renderOptionalLabel()}</label>
                                <input type="text" class="form-input" name="wind_direction" placeholder="Not recorded" value="${this.escapeHtml(historical.wind_direction || '')}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Temperature (C) ${this.renderOptionalLabel()}</label>
                                <input type="number" class="form-input" name="temperature" step="0.1" placeholder="Not recorded" value="${historical.temperature ?? ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Humidity (%) ${this.renderOptionalLabel()}</label>
                                <input type="number" class="form-input" name="humidity" min="0" max="100" step="0.1" placeholder="Not recorded" value="${historical.humidity ?? ''}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Wind Speed ${this.renderOptionalLabel()}</label>
                            <input type="number" class="form-input" name="wind_speed" min="0" step="0.1" placeholder="Not recorded" value="${historical.wind_speed ?? ''}">
                        </div>
                    </section>
                    <section class="incident-edit-pane" data-incident-edit-pane="notes" hidden>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea class="form-textarea" name="notes" rows="8">${this.escapeHtml(historical.notes || '')}</textarea>
                            <p class="notes-helper">Keep this for reviewer or station notes. Do not paste the auto GPS accuracy or timestamp text here; that is already captured in the location metadata.</p>
                        </div>
                    </section>
                </form>
            `;
        }

        const currentPriority = this.getIncidentPriorityValue(incident);
        const currentStatus = this.getIncidentStatusMeta(incident).value;
        const description = incident.marker?.description || incident.description || '';

        return `
            <form id="dataForm" class="edit-form incident-edit-form">
                <input type="hidden" name="_active_incident_edit_tab" value="details">
                <div class="incident-edit-tabs" role="tablist" aria-label="Edit incident sections">
                    ${this.renderIncidentEditTabButton('details', 'Details', true)}
                    ${this.renderIncidentEditTabButton('location', 'Location & impact')}
                    ${this.renderIncidentEditTabButton('source', 'Source & weather')}
                    ${this.renderIncidentEditTabButton('notes', 'Notes')}
                </div>
                <section class="incident-edit-pane" data-incident-edit-pane="details">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-input" name="title" value="${this.escapeHtml(incident.marker?.title || incident.title || '')}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" rows="4">${this.escapeHtml(description)}</textarea>
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
                    ${this.renderTeamAssignmentSelect(incident.team_id || '')}
                </section>
                <section class="incident-edit-pane" data-incident-edit-pane="location" hidden>
                    ${this.renderCoordinatePicker({ lat: incident.marker?.lat || '', lng: incident.marker?.lng || '', required: false })}
                    ${this.renderGpsPreview(incident, description)}
                </section>
                <section class="incident-edit-pane" data-incident-edit-pane="source" hidden>
                    <div class="form-group">
                        <label class="form-label">Reported By</label>
                        <input type="text" class="form-input" name="reported_by" value="${this.escapeHtml(incident.reported_by || '')}">
                    </div>
                </section>
                <section class="incident-edit-pane" data-incident-edit-pane="notes" hidden>
                    <p class="notes-helper">No separate notes field exists for this incident type. Use Description for the human-readable report narrative.</p>
                </section>
            </form>
        `;
    }

    renderLegacyReportEditForm(report) {
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
            case 'historical_records':
                this.renderHistoricalRecordDetail(item, viewContent, formatDate);
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

        const editIncidentBtn = document.getElementById('viewEditIncidentBtn');
        if (editIncidentBtn) {
            const isIncident = this.currentTab === 'incidents';
            editIncidentBtn.style.display = isIncident ? '' : 'none';
            editIncidentBtn.dataset.id = isIncident ? this.getItemIdentifier(item) : '';
        }
        
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    openCurrentIncidentEditor() {
        if (!this.currentItem || this.currentTab !== 'incidents') return;

        const incidentId = this.getItemIdentifier(this.currentItem);
        this.closeModal('viewModal');
        this.editItem(incidentId);
    }

    parseIncidentDescription(rawDescription) {
        const raw = String(rawDescription || '').trim();
        if (!raw) {
            return {
                summary: 'No description provided.',
                technical: ''
            };
        }

        const lower = raw.toLowerCase();
        const markers = [
            'gps accuracy',
            'location accuracy',
            'accuracy:',
            'capture timestamp',
            'captured at',
            'captured:',
            'timestamp:'
        ];
        const markerIndex = markers
            .map((marker) => lower.indexOf(marker))
            .filter((index) => index >= 0)
            .sort((a, b) => a - b)[0];

        if (markerIndex === undefined) {
            return {
                summary: raw,
                technical: ''
            };
        }

        const summary = raw.slice(0, markerIndex).replace(/[\s.;,-]+$/, '').trim();
        const technical = raw.slice(markerIndex).replace(/^[\s.;,-]+/, '').trim();

        return {
            summary: summary || 'No description provided.',
            technical
        };
    }

    formatHistoricalReference(reference) {
        const raw = String(reference || '').trim();
        if (!raw) return { label: 'Not recorded', raw: '' };

        const parts = raw.split(':');
        const suffix = parts.length > 1 ? parts.pop().trim() : raw;

        return {
            label: suffix ? `#${suffix}` : raw,
            raw
        };
    }

    renderEmptyAwareValue(value, formatter = null) {
        const hasValue = value !== null && value !== undefined && String(value).trim() !== '';
        if (!hasValue) {
            return '<span class="empty-field">Not recorded</span>';
        }

        return this.escapeHtml(formatter ? formatter(value) : value);
    }

    renderHistoricalInfoRow(label, value, { wide = false, formatter = null, rawTitle = '' } = {}) {
        const title = rawTitle ? ` title="${this.escapeHtml(rawTitle)}"` : '';
        return `
            <div class="info-row ${wide ? 'info-row--wide' : ''}">
                <span class="info-label">${this.escapeHtml(label)}:</span>
                <span class="info-value"${title}>${this.renderEmptyAwareValue(value, formatter)}</span>
            </div>
        `;
    }

    renderTeamAssignmentAuditBlock(item, formatDate) {
        const audits = Array.isArray(item.team_assignment_audits) ? item.team_assignment_audits : [];
        if (!audits.length) {
            return '';
        }

        const rows = audits.map((audit) => {
            const previousTeam = audit.previous_team_name || (audit.previous_team_id ? `Team #${audit.previous_team_id}` : 'Unassigned');
            const assignedTeam = audit.assigned_team_name || (audit.assigned_team_id ? `Team #${audit.assigned_team_id}` : 'Unassigned');
            const actionLabel = audit.assigned_team_id ? 'Assigned team' : 'Unassigned team';
            const actor = audit.actor_name || 'System user';
            const timestamp = audit.created_at ? formatDate(audit.created_at) : 'Time not recorded';

            return `
                <div class="info-row">
                    <span class="info-label"><i class="fa-solid fa-clipboard-check"></i> ${this.escapeHtml(actionLabel)}</span>
                    <span class="info-value">
                        <strong>${this.escapeHtml(previousTeam)} &rarr; ${this.escapeHtml(assignedTeam)}</strong><br>
                        <small>${this.escapeHtml(actor)} &middot; ${this.escapeHtml(timestamp)}</small>
                    </span>
                </div>
            `;
        }).join('');

        return `
            <div class="info-card">
                <div class="card-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h3>Team Assignment Log</h3>
                </div>
                <div class="card-content">
                    ${rows}
                </div>
            </div>
        `;
    }

    renderIncidentDetail(item, viewContent, formatDate) {
        const priority = this.getIncidentPriorityValue(item);
        const status = this.getIncidentStatusMeta(item).value;
        const historical = this.getPrimaryHistoricalRecord(item);
        const reporterName = this.getReporterName(item);
        const sourceName = historical?.source_name || reporterName || item.reported_by || 'Unknown';
        const recordedDate = historical?.incident_date || item.created_at;
        const recordedTime = historical?.incident_time || null;
        const barangayName = historical?.barangay?.barangay_name || item.barangay_name || item.barangay?.barangay_name || 'Barangay not recorded';
        const descriptionParts = this.parseIncidentDescription(item.marker?.description || item.description || '');
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
            : item.team_id
                ? `<p>No saved responder snapshot is available for this incident.</p>`
                : `<div class="empty-responders"><span>No response team has been assigned.</span><button type="button" class="btn-primary" data-edit-current-incident style="background:#E4602C;color:#fff;">Assign team</button></div>`;
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
        const assignmentAuditBlock = this.renderTeamAssignmentAuditBlock(item, formatDate);
        const reference = this.formatHistoricalReference(historical?.source_reference);
        const historyBlock = historical ? `
            <div class="info-card">
                <div class="card-title">
                    <i class="fa-solid fa-book-open"></i>
                    <h3>Historical Fire Record</h3>
                </div>
                <div class="card-content historical-detail-grid">
                    ${this.renderHistoricalInfoRow('Source', historical.source_name || 'Station Historical Fire Entry')}
                    ${this.renderHistoricalInfoRow('Reference', reference.label === 'Not recorded' ? '' : reference.label, { rawTitle: reference.raw })}
                    ${this.renderHistoricalInfoRow('Barangay', historical.barangay?.barangay_name)}
                    ${this.renderHistoricalInfoRow('Classification', historical.fire_classification || 'undetermined', { formatter: (value) => this.formatLabel(value) })}
                    ${this.renderHistoricalInfoRow('Cause', historical.fire_cause)}
                    ${this.renderHistoricalInfoRow('Impact', `${Number(historical.incident_count || 1)} incident(s), ${Number(historical.injured_count || 0)} injured, ${Number(historical.death_count || 0)} death(s)`)}
                    ${this.renderHistoricalInfoRow('Estimated Damage', historical.estimated_damage, { formatter: (value) => `PHP ${Number(value).toLocaleString()}` })}
                    ${this.renderHistoricalInfoRow('Weather', historical.weather_condition)}
                    ${this.renderHistoricalInfoRow('Notes', historical.notes, { wide: true })}
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
                        <h2 class="incident-title">${this.escapeHtml(this.getIncidentDisplayTitle(item))}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-label">Current Status</span>
                        <span class="status-badge-large status-${status}">
                            <i class="fa-solid fa-circle"></i>
                            ${status.toUpperCase()}
                        </span>
                    </div>
                </div>

                <div class="quick-stats-grid incident-detail-top-grid">
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
                            <span class="stat-label">Barangay</span>
                            <span class="stat-value">${this.escapeHtml(barangayName)}</span>
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
                    <div class="card-content incident-detail-description">
                        <p>${this.escapeHtml(descriptionParts.summary)}</p>
                        ${descriptionParts.technical ? `<div class="incident-detail-tech">${this.escapeHtml(descriptionParts.technical)}</div>` : ''}
                    </div>
                </div>
                ${respondersBlock}
                ${assignmentAuditBlock}
                ${historyBlock}
            </div>
        `;
    }

    renderHistoricalRecordDetail(record, viewContent, formatDate) {
        const verified = record.is_verified || record.verification_status === 'verified';
        const status = verified ? 'VERIFIED' : 'NEEDS REVIEW';
        const title = record.incident_title || `${this.formatLabel(record.fire_classification || 'undetermined')} fire record`;
        const location = [record.barangay_name, record.incident_location].filter(Boolean).join(' - ') || 'Location not recorded';
        const weatherParts = [
            record.weather_condition,
            record.temperature !== null && record.temperature !== undefined ? `${record.temperature} C` : null,
            record.humidity !== null && record.humidity !== undefined ? `${record.humidity}% humidity` : null,
            record.wind_speed !== null && record.wind_speed !== undefined ? `${record.wind_speed} wind speed` : null,
            record.wind_direction ? `${record.wind_direction} wind` : null
        ].filter(Boolean);

        viewContent.innerHTML = `
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <div class="incident-badge priority-${verified ? 'low' : 'medium'}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>HISTORICAL FIRE RECORD</span>
                        </div>
                        <h2 class="incident-title">${this.escapeHtml(title)}</h2>
                    </div>
                    <div class="status-display">
                        <span class="status-label">Verification</span>
                        <span class="status-badge-large status-${verified ? 'resolved' : 'reported'}">
                            <i class="fa-solid fa-circle"></i>
                            ${status}
                        </span>
                    </div>
                </div>

                <div class="quick-stats-grid">
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-regular fa-calendar"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Incident Date</span>
                            <span class="stat-value">${record.incident_date ? formatDate(record.incident_date) : 'N/A'}${record.incident_time ? ` ${record.incident_time}` : ''}</span>
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
                        <div class="stat-icon"><i class="fa-solid fa-file-shield"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Source</span>
                            <span class="stat-value">${this.escapeHtml(record.source_name || this.formatLabel(record.source_type || 'other'))}</span>
                        </div>
                    </div>
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Impact</span>
                            <span class="stat-value">${Number(record.incident_count || 0)} fire(s), ${Number(record.injured_count || 0)} injured, ${Number(record.death_count || 0)} death(s)</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-clipboard-list"></i><h3>Fire Details</h3></div>
                    <div class="card-content">
                        <div class="info-row"><span class="info-label">Classification:</span><span class="info-value">${this.escapeHtml(this.formatLabel(record.fire_classification || 'undetermined'))}</span></div>
                        <div class="info-row"><span class="info-label">Cause:</span><span class="info-value">${this.escapeHtml(record.fire_cause || 'N/A')}</span></div>
                        <div class="info-row"><span class="info-label">Estimated Damage:</span><span class="info-value">${this.escapeHtml(this.formatCurrency(record.estimated_damage))}</span></div>
                        <div class="info-row"><span class="info-label">Linked Incident:</span><span class="info-value">${record.incident_id ? `#${record.incident_id}` : 'Not linked'}</span></div>
                        <div class="info-row"><span class="info-label">Reporter:</span><span class="info-value">${this.escapeHtml(record.reported_by_name || 'N/A')}</span></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-cloud-sun"></i><h3>Weather and Notes</h3></div>
                    <div class="card-content">
                        <div class="info-row"><span class="info-label">Weather:</span><span class="info-value">${this.escapeHtml(weatherParts.join(', ') || 'N/A')}</span></div>
                        <div class="info-row"><span class="info-label">Source Reference:</span><span class="info-value">${this.escapeHtml(record.source_reference || 'N/A')}</span></div>
                        <div class="info-row"><span class="info-label">Notes:</span><span class="info-value">${this.escapeHtml(record.notes || 'No notes recorded.')}</span></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i><h3>Audit Details</h3></div>
                    <div class="card-content">
                        <div class="info-row"><span class="info-label">Created:</span><span class="info-value">${record.created_at ? formatDate(record.created_at) : 'N/A'}</span></div>
                        <div class="info-row"><span class="info-label">Recorded by:</span><span class="info-value">Not tracked on this record</span></div>
                        <div class="info-row"><span class="info-label">Last modified:</span><span class="info-value">${record.updated_at ? formatDate(record.updated_at) : 'N/A'}</span></div>
                        <div class="info-row"><span class="info-label">Last modified by:</span><span class="info-value">Not tracked on this record</span></div>
                    </div>
                </div>
            </div>
        `;
    }

    renderReportDetail(report, viewContent, formatDate) {
        const prefix = window.location.pathname.startsWith('/admin/') ? '/admin' : '/station-admin';
        const wordPath = report.report_kind === 'progress'
            ? `${prefix}/progress-reports/${encodeURIComponent(report.report_id)}/pir.docx`
            : report.report_kind === 'final'
                ? `${prefix}/final-reports/${encodeURIComponent(report.report_id)}/fir.docx`
                : `${prefix}/spot-reports/${encodeURIComponent(report.report_id)}/sir.docx`;
        const reviewStatus = report.review_status || 'submitted';
        const reviewedAt = report.reviewed_at ? formatDate(report.reviewed_at) : 'Not reviewed yet';
        const hiddenFields = new Set(['spot_investigation_report_id', 'progress_investigation_report_id', 'final_investigation_report_id', 'station_officer_id', 'review_status', 'review_comments', 'reviewed_by_user_id', 'reviewed_at']);
        const fieldRows = Object.entries(report.fields || {})
            .filter(([key, value]) => !hiddenFields.has(key) && value !== null && value !== '')
            .map(([key, value]) => {
                const label = key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
                const display = Array.isArray(value) || (value && typeof value === 'object') ? JSON.stringify(value) : String(value);
                return `<div class="info-row"><span class="info-label">${this.escapeHtml(label)}</span><span class="info-value">${this.escapeHtml(display)}</span></div>`;
            }).join('');
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
                    <div class="quick-stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="stat-content">
                            <span class="stat-label">Review</span>
                            <span class="stat-value">${this.escapeHtml(report.review_status_label || reviewStatus)}</span>
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
                <div class="info-card">
                    <div class="card-title"><i class="fa-solid fa-clipboard-list"></i><h3>Complete report information</h3></div>
                    <div class="card-content">${fieldRows || '<p>No additional fields were recorded.</p>'}</div>
                </div>
                <div class="modal-actions" style="margin-top:14px;display:flex;justify-content:flex-end;">
                    <a class="btn-primary" href="${wordPath}"><i class="fa-solid fa-file-word"></i> Download Word report</a>
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
            <div class="detail-container report-detail-container">
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

    getDeleteItemLabel(item = null) {
        const target = item || this.data[this.currentTab]?.find(i => this.getItemIdentifier(i) == this.currentDeleteId);

        if (!target) return 'this item';

        if (this.currentTab === 'historical_records') {
            return target.incident_title || `${this.formatLabel(target.fire_classification || 'undetermined')} fire record`;
        }

        if (this.currentTab === 'incidents') return this.getIncidentDisplayTitle(target);
        if (this.currentTab === 'reports') return target.title || 'this report';
        if (this.currentTab === 'personnel') return `${target.first_name || ''} ${target.last_name || ''}`.trim() || 'this personnel record';
        if (this.currentTab === 'equipment') return target.equipment_name || 'this equipment record';

        return 'this item';
    }

    // Delete item
    deleteItem(id) {
        this.currentDeleteId = id;
        const deleteTitle = document.getElementById('deleteModalTitle');
        const deleteDescription = document.getElementById('deleteModalDescription');
        const itemLabel = this.getDeleteItemLabel();

        if (deleteTitle) {
            deleteTitle.textContent = `Delete ${itemLabel}?`;
        }

        if (deleteDescription) {
            deleteDescription.textContent = this.currentTab === 'historical_records'
                ? 'This deletes only the saved historical fire record. Linked live incident data will remain unless it is managed separately.'
                : 'This action cannot be undone. The selected record will be permanently removed from the system.';
        }

        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    ensureRejectReportModal() {
        if (document.getElementById('rejectReportModal')) return;

        document.body.insertAdjacentHTML('beforeend', `
            <div id="rejectReportModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Reject Report</h2>
                        <button class="modal-close" id="rejectReportModalClose" type="button">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label" for="rejectReportComments">Review comments</label>
                            <textarea id="rejectReportComments" class="form-textarea" rows="5" placeholder="Describe what the firefighter needs to revise."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary" id="cancelRejectReportBtn" type="button">Cancel</button>
                        <button class="btn-primary" id="confirmRejectReportBtn" type="button" style="background:#dc2626;">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    openRejectReportModal(id) {
        const report = this.data.reports.find(item => this.getItemIdentifier(item) == id);
        if (!report?.report_kind || !report?.report_id) return;

        this.pendingReview = { id, decision: 'rejected' };
        const textarea = document.getElementById('rejectReportComments');
        if (textarea) textarea.value = report.review_comments || '';
        document.getElementById('rejectReportModal')?.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => textarea?.focus(), 50);
    }

    async confirmRejectReport() {
        if (!this.pendingReview?.id) return;

        const comments = document.getElementById('rejectReportComments')?.value || '';
        const updated = await this.reviewReport(this.pendingReview.id, 'rejected', comments);
        if (updated) {
            this.pendingReview = null;
            this.closeModal('rejectReportModal');
        }
    }

    async reviewReport(id, decision, reviewComments = '') {
        const report = this.data.reports.find(item => this.getItemIdentifier(item) == id);
        if (!report?.report_kind || !report?.report_id) return;

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
            return true;
        } catch (error) {
            console.error('Review error:', error);
            this.showToast(error.message || 'Failed to update report review', 'error');
            return false;
        } finally {
            this.showLoading(false);
        }
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
            if (['incidents', 'historical_records', 'personnel', 'equipment', 'reports'].includes(this.currentTab)) {
                const report = this.currentTab === 'reports' ? this.data.reports.find(item => this.getItemIdentifier(item) == this.currentDeleteId) : null;
                const deleteUrl = report
                    ? `/api/managements/reports/${encodeURIComponent(report.report_kind)}/${encodeURIComponent(report.report_id)}`
                    : this.currentTab === 'historical_records'
                        ? `/api/managements/historical-records/${this.currentDeleteId}`
                    : `/api/managements/${this.currentTab}/${this.currentDeleteId}`;
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

        if (!this.validateCoordinatePickers()) return;
        if (!form.checkValidity()) {
            this.focusFirstInvalidField(form);
            return;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            this.showToast('Saving...', 'info');
            
            // For incidents, personnel, equipment: call backend; others: frontend-only
            if (['incidents', 'historical_records', 'personnel', 'equipment', 'reports'].includes(this.currentTab)) {
                let url = '';
                let method = '';
                
                if (this.currentTab === 'reports') {
                    url = `/api/managements/reports/${encodeURIComponent(this.currentItem.report_kind)}/${encodeURIComponent(this.currentItem.report_id)}`;
                    method = 'PUT';
                } else if (this.currentTab === 'historical_records') {
                    url = this.currentEditId
                        ? `/api/managements/historical-records/${this.currentEditId}`
                        : '/api/managements/incidents';
                    method = this.currentEditId ? 'PUT' : 'POST';
                } else if (this.currentEditId) {
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
                
                if (this.currentTab === 'reports') {
                    payload = data;
                } else if (this.currentTab === 'historical_records') {
                    if (this.currentEditId) {
                        payload = {
                            title: data.title || null,
                            description: data.description || null,
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
                            lat: data.lat || null,
                            lng: data.lng || null,
                            source_name: data.source_name || null,
                            source_type: data.source_type || 'other',
                            source_reference: data.source_reference || null,
                            team_id: data.team_id || null,
                            weather_condition: data.weather_condition || null,
                            temperature: data.temperature || null,
                            humidity: data.humidity || null,
                            wind_speed: data.wind_speed || null,
                            wind_direction: data.wind_direction || null,
                            is_verified: data.is_verified === '1',
                            notes: data.notes || null
                        };
                    } else {
                        payload = {
                            entry_mode: 'historical_fire_record',
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
                            team_id: data.team_id || null,
                            weather_condition: data.weather_condition || null,
                            temperature: data.temperature || null,
                            humidity: data.humidity || null,
                            wind_speed: data.wind_speed || null,
                            wind_direction: data.wind_direction || null,
                            notes: data.notes || null
                        };
                    }
                } else if (this.currentTab === 'personnel') {
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
                            team_id: data.team_id || null,
                            notes: data.notes || null
                        };
                    } else {
                        payload = {
                            title: data.title,
                            description: data.description,
                            priority: data.priority,
                            status: data.status,
                            lat: data.lat || null,
                            lng: data.lng || null,
                            reported_by: data.reported_by,
                            team_id: data.team_id || null
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
                
                if (this.currentTab === 'reports' || this.currentTab === 'historical_records') {
                    await this.loadCurrentTab();
                } else if (this.currentEditId) {
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
        if (modalId === 'formModal') {
            this.destroyCoordinatePickers();
            this.hideIncidentEditStepIndicator();
        }
        document.body.style.overflow = 'auto';
        this.currentEditId = null;
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
        this.destroyCoordinatePickers();
        this.hideIncidentEditStepIndicator();
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
