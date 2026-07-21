function bootFirefighterMyStationPage() {
    const root = document.querySelector('[data-ff-page-root="station"]');
    if (!root || root.dataset.ffStationBooted === 'true') {
        return;
    }

    root.dataset.ffStationBooted = 'true';

    const config = window.firefighterMyStationConfig || {};
    const routes = config.routes || {};
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const currentOfficerName = config.currentOfficerName || 'Current Station Officer';
    const finalSubstantiatingDocumentOptions = Array.isArray(config.finalSubstantiatingDocumentOptions)
        ? config.finalSubstantiatingDocumentOptions
        : [];
    const reportStore = { spot: {}, progress: {}, final: {} };
    const referenceCache = { spot: null, progress: null, final: null };
    const requestControllers = new Map();

    const overviewSection = document.getElementById('stationOverviewSection');
    const personnelSection = document.getElementById('stationPersonnelSection');
    const equipmentSection = document.getElementById('stationEquipmentSection');
    const reportsContainer = document.getElementById('stationReportsPanelContainer');
    const reportTabs = document.getElementById('reportTabs');
    const reportToastStack = document.getElementById('reportToastStack');
    const manageReportModal = document.getElementById('manageReportModal');
    const manageReportModalTitle = document.getElementById('manageReportModalTitle');
    const manageReportModalBody = document.getElementById('manageReportModalBody');
    const manageReportModalFooter = document.getElementById('manageReportModalFooter');
    const spotForm = document.getElementById('spotReportForm');
    const progressForm = document.getElementById('progressReportForm');
    const finalForm = document.getElementById('finalReportForm');
    const initialReportTab = config.activeReportTab || 'spot';
    let currentReportTab = initialReportTab;

    function abortRequest(key) {
        const existing = requestControllers.get(key);
        if (existing) {
            existing.abort();
            requestControllers.delete(key);
        }
    }

    function createAbortController(key) {
        abortRequest(key);
        const controller = new AbortController();
        requestControllers.set(key, controller);
        return controller;
    }

    function cleanupRequests() {
        requestControllers.forEach((controller) => controller.abort());
        requestControllers.clear();
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function formatDateTime(value) {
        if (!value) return 'N/A';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'N/A';
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function formatMoney(value) {
        return `PHP ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function buildSirRoute(reportId) {
        return String(routes.sirTemplate || '').replace('__SPOT_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildPirRoute(reportId) {
        return String(routes.pirTemplate || '').replace('__PROGRESS_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildFirRoute(reportId) {
        return String(routes.firTemplate || '').replace('__FINAL_REPORT_ID__', encodeURIComponent(reportId));
    }

    function showToast(message, type = 'success', timeout = 3200) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }

        if (!reportToastStack) {
            console[type === 'error' ? 'error' : 'log'](message);
            return;
        }

        const toast = document.createElement('div');
        toast.className = `report-toast ${type}`;
        toast.textContent = message;
        reportToastStack.appendChild(toast);

        window.setTimeout(() => toast.remove(), timeout);
    }

    function buildLoadingCard(message) {
        return `
            <div class="section-shell-card station-loading-card">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;
    }

    function setModalLoading(type, message = 'Loading report form...') {
        const modal = document.getElementById(`${type}ReportModal`);
        const body = modal?.querySelector('.report-modal-body');
        if (!body) return null;

        const loading = document.createElement('div');
        loading.className = 'report-modal-loading';
        loading.dataset.reportModalLoading = type;
        loading.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i><span>${escapeHtml(message)}</span>`;
        body.prepend(loading);
        return loading;
    }

    function clearModalLoading(type) {
        document.querySelectorAll('[data-report-modal-loading]').forEach((element) => {
            if (element.dataset.reportModalLoading === type) {
                element.remove();
            }
        });
    }

    function setFormControlsDisabled(form, disabled) {
        form?.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = disabled;
        });
    }

    function renderSectionError(target, message, retryHandler) {
        if (!target) return;
        target.innerHTML = `
            <div class="station-section-error">
                <div>${escapeHtml(message)}</div>
                <button type="button" class="btn btn-primary" data-station-retry="${escapeHtml(retryHandler)}">Retry</button>
            </div>
        `;
    }

    async function fetchStationSection(key, url) {
        const controller = createAbortController(key);
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });

        if (requestControllers.get(key) === controller) {
            requestControllers.delete(key);
        }

        if (!response.ok) {
            throw new Error(`Unable to load ${key} right now.`);
        }

        return response.json();
    }

    function updateReportCounts() {
        const counters = {
            spotReportsCount: Object.keys(reportStore.spot).length,
            progressReportsCount: Object.keys(reportStore.progress).length,
            finalReportsCount: Object.keys(reportStore.final).length,
        };

        Object.entries(counters).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element && Number(value) >= 0) {
                element.textContent = String(value);
            }
        });
    }

    async function loadOverview(force = false) {
        try {
            const query = force ? `${routes.overview}?refresh=1` : routes.overview;
            const result = await fetchStationSection('overview', query);
            overviewSection.innerHTML = result?.data?.html || '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station overview failed:', error);
            renderSectionError(overviewSection, 'Unable to load the station overview.', 'loadFirefighterStationOverview');
        }
    }

    async function loadPersonnel(force = false) {
        try {
            const query = force ? `${routes.personnel}?refresh=1` : routes.personnel;
            const result = await fetchStationSection('personnel', query);
            personnelSection.innerHTML = result?.data?.html || '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station personnel failed:', error);
            renderSectionError(personnelSection, 'Unable to load station personnel.', 'loadFirefighterStationPersonnel');
        }
    }

    async function loadEquipment(force = false) {
        try {
            const query = force ? `${routes.equipment}?refresh=1` : routes.equipment;
            const result = await fetchStationSection('equipment', query);
            equipmentSection.innerHTML = result?.data?.html || '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station equipment failed:', error);
            renderSectionError(equipmentSection, 'Unable to load station equipment.', 'loadFirefighterStationEquipment');
        }
    }

    async function loadReports(tab = 'spot', urlOverride = null) {
        try {
            currentReportTab = tab;
            document.querySelectorAll('.report-tab').forEach((tabButton) => {
                tabButton.classList.toggle('active', tabButton.dataset.reportTab === tab);
            });

            if (reportsContainer) {
                reportsContainer.innerHTML = buildLoadingCard(`Loading ${tab} investigation reports...`);
            }

            const url = urlOverride || `${routes.reports}?tab=${encodeURIComponent(tab)}`;
            const result = await fetchStationSection('reports', url);
            reportsContainer.innerHTML = result?.data?.html || '';
            reportStore[tab] = result?.data?.store || {};

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('report_tab', tab);
            window.history.replaceState(window.history.state || {}, '', currentUrl);
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station reports failed:', error);
            renderSectionError(reportsContainer, 'Unable to load station reports.', 'loadFirefighterStationReports');
        }
    }

    async function ensureModalData(scope) {
        if (referenceCache[scope]) {
            return referenceCache[scope];
        }

        const result = await fetchStationSection(`modal-${scope}`, `${routes.modalData}?scope=${encodeURIComponent(scope)}`);
        referenceCache[scope] = result?.data || {};
        return referenceCache[scope];
    }

    function createOptions(items, selectedValue = '', extra = null) {
        return (items || []).map((item) => {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            const extraAttrs = typeof extra === 'function' ? extra(item) : '';
            return `<option value="${escapeHtml(item.id)}" ${selected} ${extraAttrs}>${escapeHtml(item.label)}</option>`;
        }).join('');
    }

    function populateOfficerSelect(select, selectedValue = '') {
        const officerOptions = referenceCache.spot?.officers
            || referenceCache.progress?.officers
            || referenceCache.final?.officers
            || [];

        select.innerHTML = `<option value="">Select officer</option>${createOptions(officerOptions, selectedValue)}`;
    }

    function renderSubstantiatingDocumentCheckboxes(selectedValues = []) {
        const selectedSet = new Set((selectedValues || []).map(String));
        return finalSubstantiatingDocumentOptions.map((label) => `
            <label class="detail-item" style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
                <input
                    type="checkbox"
                    name="substantiating_documents[]"
                    value="${escapeHtml(label)}"
                    ${selectedSet.has(label) ? 'checked' : ''}
                    style="margin-top:4px;"
                >
                <span>${escapeHtml(label)}</span>
            </label>
        `).join('');
    }

    function hydrateFinalDocuments(defaultValues = []) {
        const target = document.getElementById('finalSubstantiatingDocuments');
        if (!target) return;
        target.innerHTML = renderSubstantiatingDocumentCheckboxes(defaultValues.length ? defaultValues : ['Spot Investigation Report', 'Progress Investigation Report']);
    }

    function populateSpotModal(data) {
        const incidentSelect = spotForm?.querySelector('select[name="incident_id"]');
        const officerSelects = spotForm?.querySelectorAll('select[data-station-ref="officers"]');
        if (!incidentSelect || !officerSelects) return;

        incidentSelect.innerHTML = `<option value="">Select incident</option>${createOptions(data.incidents || [], '', (item) => `data-updated-at="${escapeHtml(item.updated_at || '')}" data-location="${escapeHtml(item.label || '')}"` )}`;
        officerSelects.forEach((select) => populateOfficerSelect(select));
    }

    function populateProgressModal(data) {
        const reportSelect = progressForm?.querySelector('select[name="spot_investigation_id"]');
        const fireOriginSelect = progressForm?.querySelector('select[name="fire_origin_id"]');
        const officerSelects = progressForm?.querySelectorAll('select[data-station-ref="officers"]');
        if (!reportSelect || !fireOriginSelect || !officerSelects) return;

        reportSelect.innerHTML = `<option value="">Select spot report</option>${createOptions(data.spot_reports || [])}`;
        fireOriginSelect.innerHTML = `<option value="">Select fire origin</option>${createOptions(data.fire_origins || [])}<option value="other">Other</option>`;
        officerSelects.forEach((select) => populateOfficerSelect(select));
    }

    function populateFinalModal(data) {
        const reportSelect = finalForm?.querySelector('select[name="progress_investigation_id"]');
        const officerSelects = finalForm?.querySelectorAll('select[data-station-ref="officers"]');
        if (!reportSelect || !officerSelects) return;

        reportSelect.innerHTML = `<option value="">Select progress report</option>${createOptions(data.progress_reports || [])}`;
        officerSelects.forEach((select) => populateOfficerSelect(select));
        hydrateFinalDocuments();
    }

    async function openReportModal(type) {
        const modal = document.getElementById(`${type}ReportModal`);
        const form = type === 'spot' ? spotForm : type === 'progress' ? progressForm : finalForm;

        modal?.classList.add('active');
        clearModalLoading(type);
        modal?.querySelectorAll('.station-section-error').forEach((errorBox) => errorBox.remove());
        const loading = setModalLoading(type);
        setFormControlsDisabled(form, true);

        try {
            if (type === 'spot') populateSpotModal(await ensureModalData('spot'));
            if (type === 'progress') populateProgressModal(await ensureModalData('progress'));
            if (type === 'final') populateFinalModal(await ensureModalData('final'));
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error(`Unable to open ${type} modal:`, error);
            loading?.remove();
            const body = modal?.querySelector('.report-modal-body');
            body?.prepend(Object.assign(document.createElement('div'), {
                className: 'station-section-error',
                innerHTML: `<div>Unable to load ${escapeHtml(type)} report choices right now. You can close this and try again.</div>`,
            }));
            showToast(`Unable to load ${type} report choices right now.`, 'error');
        } finally {
            clearModalLoading(type);
            setFormControlsDisabled(form, false);
        }
    }

    function closeReportModal(type) {
        const modal = document.getElementById(`${type}ReportModal`);
        modal?.classList.remove('active');
        clearModalLoading(type);
        modal?.querySelector('.station-section-error')?.remove();
    }

    function openManageModal(title) {
        manageReportModalTitle.textContent = title;
        manageReportModal.classList.add('active');
    }

    function closeManageModal() {
        manageReportModal.classList.remove('active');
        manageReportModalBody.innerHTML = '';
        manageReportModalFooter.innerHTML = '';
    }

    function switchReportTab(type) {
        document.querySelectorAll('.report-tab').forEach((tabButton) => {
            tabButton.classList.toggle('active', tabButton.dataset.reportTab === type);
        });
        loadReports(type);
    }

    async function handleReportDeepLink() {
        const params = new URLSearchParams(window.location.search);
        const reportType = params.get('openReport');

        if (!reportType) return;

        if (!['spot', 'progress', 'final'].includes(reportType)) {
            params.delete('openReport');
            replaceCurrentQuery(params);
            return;
        }

        await openReportModal(reportType);

        if (reportType === 'spot') {
            const incidentId = params.get('incident_id');
            const incidentSelect = spotForm?.querySelector('select[name="incident_id"]');

            if (incidentId && incidentSelect) {
                incidentSelect.value = incidentId;
                incidentSelect.dispatchEvent(new Event('change', { bubbles: true }));

                if (incidentSelect.value !== String(incidentId)) {
                    showToast('This incident is not available for a new spot investigation report.', 'error');
                }
            }
        }

        params.delete('openReport');
        params.delete('incident_id');
        replaceCurrentQuery(params);
    }

    function replaceCurrentQuery(params) {
        const query = params.toString();
        const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
        window.history.replaceState(window.history.state || {}, '', cleanUrl);
    }

    function getSelectedOptionText(select) {
        const option = select?.options?.[select.selectedIndex];
        return option?.dataset?.location || option?.textContent?.trim() || 'Unknown incident location';
    }

    function getCheckedValues(form, inputName) {
        return Array.from(form?.querySelectorAll(`input[name="${inputName}"]:checked`) || []).map((input) => input.value);
    }

    function removeOption(select, value) {
        if (!select) return;
        const option = Array.from(select.options).find((item) => item.value === String(value));
        option?.remove();
    }

    function incrementCounter(id) {
        const element = document.getElementById(id);
        if (!element) return;
        const count = Number.parseInt(element.textContent || '0', 10);
        element.textContent = Number.isNaN(count) ? '1' : String(count + 1);
    }

    function decrementCounter(id) {
        const element = document.getElementById(id);
        if (!element) return;
        const count = Number.parseInt(element.textContent || '0', 10);
        element.textContent = String(Math.max(0, Number.isNaN(count) ? 0 : count - 1));
    }

    function createActionButtons(type, report) {
        const actions = [
            `<button type="button" class="table-action view" data-report-action="view" data-report-type="${type}" data-report-id="${report.id}">View</button>`,
        ];

        if (type === 'spot') {
            actions.push(`<a class="table-action edit" href="${buildSirRoute(report.id)}">Word</a>`);
            actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
            if (!report.has_progress) {
                actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
            }
        }

        if (type === 'progress') {
            actions.push(`<a class="table-action edit" href="${buildPirRoute(report.id)}">Word</a>`);
            if (!report.has_final) {
                actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
                actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
            }
        }

        if (type === 'final') {
            actions.push(`<a class="table-action edit" href="${buildFirRoute(report.id)}">Word</a>`);
            actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
            actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
        }

        return `<div class="table-actions">${actions.join('')}</div>`;
    }

    function appendSpotReportRow(report) {
        const tbody = document.getElementById('spotReportsTableBody');
        if (!tbody || !report?.id) return;
        const emptyState = tbody.querySelector('td[colspan="6"]')?.closest('tr');
        if (emptyState) emptyState.remove();

        tbody.insertAdjacentHTML('afterbegin', `
            <tr id="spot-report-${report.id}">
                <td><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">Spot report</div></td>
                <td>${escapeHtml(report.investigator_name || currentOfficerName)}</td>
                <td>${escapeHtml(formatDateTime(report.fire_started))}<br>${escapeHtml(formatDateTime(report.fire_out))}</td>
                <td>${escapeHtml(formatMoney(report.estimated_damage))}</td>
                <td>${escapeHtml(String(report.occupancy || 'n/a').replace(/^./, (match) => match.toUpperCase()))}</td>
                <td>${createActionButtons('spot', report)}</td>
            </tr>
        `);

        incrementCounter('spotReportsCount');
    }

    function appendProgressReportRow(report) {
        const tbody = document.getElementById('progressReportsTableBody');
        if (!tbody || !report?.id) return;
        const emptyState = tbody.querySelector('td[colspan="6"]')?.closest('tr');
        if (emptyState) emptyState.remove();

        tbody.insertAdjacentHTML('afterbegin', `
            <tr id="progress-report-${report.id}">
                <td><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">Progress report</div></td>
                <td>${escapeHtml(report.fire_origin_name || 'N/A')}</td>
                <td>${escapeHtml(report.fire_cause || 'N/A')}</td>
                <td>${escapeHtml(report.responsible_arson || 'Not specified')}</td>
                <td>${escapeHtml(report.investigator_name || currentOfficerName)}</td>
                <td>${createActionButtons('progress', report)}</td>
            </tr>
        `);

        incrementCounter('progressReportsCount');
    }

    function appendFinalReportRow(report) {
        const tbody = document.getElementById('finalReportsTableBody');
        if (!tbody || !report?.id) return;
        const emptyState = tbody.querySelector('td[colspan="5"]')?.closest('tr');
        if (emptyState) emptyState.remove();

        tbody.insertAdjacentHTML('afterbegin', `
            <tr id="final-report-${report.id}">
                <td><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">Final report</div></td>
                <td>${escapeHtml(report.exact_address || 'N/A')}</td>
                <td>${escapeHtml(formatMoney(report.total_damage))}</td>
                <td>${escapeHtml(formatDateTime(report.created_at))}</td>
                <td>${createActionButtons('final', report)}</td>
            </tr>
        `);

        incrementCounter('finalReportsCount');
    }

    function replaceRow(type, report) {
        const row = document.getElementById(`${type}-report-${report.id}`);
        row?.remove();
        if (type === 'spot') appendSpotReportRow(report);
        if (type === 'progress') appendProgressReportRow(report);
        if (type === 'final') appendFinalReportRow(report);
    }

    function renderOfficerSelectOptions(selectedValue = '') {
        const officers = referenceCache.spot?.officers || referenceCache.progress?.officers || referenceCache.final?.officers || [];
        return `<option value="">Select officer</option>${createOptions(officers, selectedValue)}`;
    }

    function renderFireOriginSelectOptions(selectedValue = '', selectedLabel = '') {
        const fireOrigins = referenceCache.progress?.fire_origins || [];
        const matched = fireOrigins.some((origin) => String(origin.id) === String(selectedValue));
        const selectedOther = !matched && selectedLabel ? 'selected' : '';
        return `<option value="">Select fire origin</option>${createOptions(fireOrigins, selectedValue)}<option value="other" ${selectedOther}>Other</option>`;
    }

    function renderViewDetails(type, report) {
        if (type === 'spot') {
            return `
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Report Type</div><div class="detail-value">Spot investigation</div></div>
                    <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                    <div class="detail-item"><div class="detail-label">Investigator</div><div class="detail-value">${escapeHtml(report.investigator_name || currentOfficerName)}</div></div>
                    <div class="detail-item"><div class="detail-label">Occupancy</div><div class="detail-value">${escapeHtml(report.occupancy || 'N/A')}</div></div>
                    <div class="detail-item"><div class="detail-label">Fire Started</div><div class="detail-value">${escapeHtml(formatDateTime(report.fire_started))}</div></div>
                    <div class="detail-item"><div class="detail-label">Fire Out</div><div class="detail-value">${escapeHtml(formatDateTime(report.fire_out))}</div></div>
                    <div class="detail-item"><div class="detail-label">Owner</div><div class="detail-value">${escapeHtml(report.owner || 'Not provided')}</div></div>
                    <div class="detail-item"><div class="detail-label">Report Completed</div><div class="detail-value">${escapeHtml(formatDateTime(report.report_completed_at))}</div></div>
                    <div class="detail-item full"><div class="detail-label">Narrative</div><div class="detail-value">${escapeHtml(report.details_of_investigation || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Disposition</div><div class="detail-value">${escapeHtml(report.disposition || 'Not provided')}</div></div>
                </div>
            `;
        }

        if (type === 'progress') {
            return `
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Report Type</div><div class="detail-value">Progress investigation</div></div>
                    <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                    <div class="detail-item"><div class="detail-label">Fire Origin</div><div class="detail-value">${escapeHtml(report.fire_origin_name || 'N/A')}</div></div>
                    <div class="detail-item"><div class="detail-label">Responsible Arson</div><div class="detail-value">${escapeHtml(report.responsible_arson || 'Not specified')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Fire Cause</div><div class="detail-value">${escapeHtml(report.fire_cause || 'N/A')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Facts of the Case</div><div class="detail-value">${escapeHtml(report.facts_of_case || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Disposition</div><div class="detail-value">${escapeHtml(report.disposition || 'Not provided')}</div></div>
                </div>
            `;
        }

        return `
            <div class="detail-grid">
                <div class="detail-item"><div class="detail-label">Report Type</div><div class="detail-value">Final investigation</div></div>
                <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                <div class="detail-item"><div class="detail-label">Total Damage</div><div class="detail-value">${escapeHtml(formatMoney(report.total_damage))}</div></div>
                <div class="detail-item"><div class="detail-label">Alarm Time</div><div class="detail-value">${escapeHtml(formatDateTime(report.alarm_at))}</div></div>
                <div class="detail-item full"><div class="detail-label">Exact Address</div><div class="detail-value">${escapeHtml(report.exact_address || 'N/A')}</div></div>
                <div class="detail-item full"><div class="detail-label">Cause of Fire</div><div class="detail-value">${escapeHtml(report.cause_of_fire || report.fire_cause || 'N/A')}</div></div>
                <div class="detail-item full"><div class="detail-label">Discussion</div><div class="detail-value">${escapeHtml(report.discussion || 'Not provided')}</div></div>
                <div class="detail-item full"><div class="detail-label">Findings</div><div class="detail-value">${escapeHtml(report.findings || 'Not provided')}</div></div>
                <div class="detail-item full"><div class="detail-label">Recommendation</div><div class="detail-value">${escapeHtml(report.recommendation || 'Not provided')}</div></div>
            </div>
        `;
    }

    function renderEditForm(type, report) {
        if (type === 'spot') {
            return `
                <form id="manageReportEditForm" class="form-grid">
                    <div class="form-group"><label class="form-label">Dead</label><input class="form-input" type="number" name="dead" min="0" step="1" value="${escapeHtml(report.dead || 0)}"></div>
                    <div class="form-group"><label class="form-label">Injured</label><input class="form-input" type="number" name="injured" min="0" step="1" value="${escapeHtml(report.injured || 0)}"></div>
                    <div class="form-group full"><label class="form-label">Owner</label><input class="form-input" type="text" name="owner" value="${escapeHtml(report.owner || '')}"></div>
                    <div class="form-group"><label class="form-label">Report Completed</label><input class="form-input" type="datetime-local" name="report_completed_at" value="${escapeHtml((report.report_completed_at || '').slice(0, 16))}"></div>
                    <div class="form-group"><label class="form-label">Memorandum For</label><input class="form-input" type="text" name="memorandum_for" value="${escapeHtml(report.memorandum_for || '')}"></div>
                    <div class="form-group"><label class="form-label">Fire Started</label><input class="form-input" type="datetime-local" name="fire_started" value="${escapeHtml((report.fire_started || '').slice(0, 16))}"></div>
                    <div class="form-group"><label class="form-label">Fire Out</label><input class="form-input" type="datetime-local" name="fire_out" value="${escapeHtml((report.fire_out || '').slice(0, 16))}"></div>
                    <div class="form-group full"><label class="form-label">Place of Occurrence</label><input class="form-input" type="text" name="place_of_occurrence" value="${escapeHtml(report.place_of_occurrence || '')}"></div>
                    <div class="form-group"><label class="form-label">Estimated Damage</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_damage" value="${escapeHtml(report.estimated_damage || 0)}"></div>
                    <div class="form-group"><label class="form-label">Estimated Area</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_area" value="${escapeHtml(report.estimated_area || 0)}"></div>
                    <div class="form-group"><label class="form-label">Affected Establishments</label><input class="form-input" type="number" min="0" step="1" name="affected_establishments_count" value="${escapeHtml(report.affected_establishments_count || 0)}"></div>
                    <div class="form-group"><label class="form-label">Highest Alarm</label><input class="form-input" type="text" name="highest_alarm_level" value="${escapeHtml(report.highest_alarm_level || '')}"></div>
                    <div class="form-group full"><label class="form-label">Narrative</label><textarea class="form-textarea" name="details_of_investigation">${escapeHtml(report.details_of_investigation || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Disposition</label><textarea class="form-textarea" name="disposition">${escapeHtml(report.disposition || '')}</textarea></div>
                    <div class="form-group"><label class="form-label">Noted By</label><select class="form-select" name="noted_by_station_officer_id">${renderOfficerSelectOptions(report.noted_by_station_officer_id || '')}</select></div>
                    <div class="form-group"><label class="form-label">Approved By</label><select class="form-select" name="approved_by_station_officer_id">${renderOfficerSelectOptions(report.approved_by_station_officer_id || '')}</select></div>
                </form>
            `;
        }

        if (type === 'progress') {
            return `
                <form id="manageReportEditForm" class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fire Origin</label>
                        <select class="form-select" name="fire_origin_id">${renderFireOriginSelectOptions(report.fire_origin_id || '', report.fire_origin_name || '')}</select>
                    </div>
                    <div class="form-group" data-fire-origin-other-group style="${report.fire_origin_id || report.fire_origin_name ? '' : 'display:none;'}">
                        <label class="form-label">Specify Fire Origin</label>
                        <input class="form-input" type="text" name="fire_origin_other" value="${escapeHtml(report.fire_origin_name || '')}">
                    </div>
                    <div class="form-group"><label class="form-label">Responsible Arson</label><input class="form-input" type="text" name="responsible_arson" value="${escapeHtml(report.responsible_arson || '')}"></div>
                    <div class="form-group"><label class="form-label">Report Completed</label><input class="form-input" type="datetime-local" name="report_completed_at" value="${escapeHtml((report.report_completed_at || '').slice(0, 16))}"></div>
                    <div class="form-group full"><label class="form-label">Fire Cause</label><textarea class="form-textarea" name="fire_cause">${escapeHtml(report.fire_cause || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Facts of the Case</label><textarea class="form-textarea" name="facts_of_case">${escapeHtml(report.facts_of_case || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Disposition</label><textarea class="form-textarea" name="disposition">${escapeHtml(report.disposition || '')}</textarea></div>
                    <div class="form-group"><label class="form-label">Noted By</label><select class="form-select" name="noted_by_station_officer_id">${renderOfficerSelectOptions(report.noted_by_station_officer_id || '')}</select></div>
                    <div class="form-group"><label class="form-label">Approved By</label><select class="form-select" name="approved_by_station_officer_id">${renderOfficerSelectOptions(report.approved_by_station_officer_id || '')}</select></div>
                </form>
            `;
        }

        return `
            <form id="manageReportEditForm" class="form-grid">
                <div class="form-group"><label class="form-label">Total Damage</label><input class="form-input" type="number" min="0" step="0.01" name="total_damage" value="${escapeHtml(report.total_damage || 0)}"></div>
                <div class="form-group"><label class="form-label">Report Completed</label><input class="form-input" type="datetime-local" name="report_completed_at" value="${escapeHtml((report.report_completed_at || '').slice(0, 16))}"></div>
                <div class="form-group full"><label class="form-label">Office Address</label><textarea class="form-textarea" name="office_address">${escapeHtml(report.office_address || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Exact Address</label><textarea class="form-textarea" name="exact_address">${escapeHtml(report.exact_address || '')}</textarea></div>
                <div class="form-group"><label class="form-label">Memorandum For</label><input class="form-input" type="text" name="memorandum_for" value="${escapeHtml(report.memorandum_for || '')}"></div>
                <div class="form-group"><label class="form-label">Alarm Time</label><input class="form-input" type="datetime-local" name="alarm_at" value="${escapeHtml((report.alarm_at || '').slice(0, 16))}"></div>
                <div class="form-group full"><label class="form-label">Establishment Burned</label><input class="form-input" type="text" name="establishment_burned" value="${escapeHtml(report.establishment_burned || '')}"></div>
                <div class="form-group full"><label class="form-label">Fire Victim/s</label><textarea class="form-textarea" name="fire_victims">${escapeHtml(report.fire_victims || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Origin of Fire</label><textarea class="form-textarea" name="origin_of_fire">${escapeHtml(report.origin_of_fire || report.fire_origin_name || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Cause of Fire</label><textarea class="form-textarea" name="cause_of_fire">${escapeHtml(report.cause_of_fire || report.fire_cause || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Facts of the Case</label><textarea class="form-textarea" name="facts_of_case">${escapeHtml(report.facts_of_case || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Discussion</label><textarea class="form-textarea" name="discussion">${escapeHtml(report.discussion || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Findings</label><textarea class="form-textarea" name="findings">${escapeHtml(report.findings || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Recommendation</label><textarea class="form-textarea" name="recommendation">${escapeHtml(report.recommendation || '')}</textarea></div>
                <div class="form-group full"><label class="form-label">Substantiating Documents</label><div class="checkbox-grid">${renderSubstantiatingDocumentCheckboxes(report.substantiating_documents || [])}</div></div>
                <div class="form-group"><label class="form-label">Noted By</label><select class="form-select" name="noted_by_station_officer_id">${renderOfficerSelectOptions(report.noted_by_station_officer_id || '')}</select></div>
                <div class="form-group"><label class="form-label">Approved By</label><select class="form-select" name="approved_by_station_officer_id">${renderOfficerSelectOptions(report.approved_by_station_officer_id || '')}</select></div>
            </form>
        `;
    }

    async function openViewModal(type, report) {
        openManageModal(`${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = renderViewDetails(type, report);
        const downloadButton = type === 'spot'
            ? `<a class="btn btn-outline" href="${buildSirRoute(report.id)}">Download Word</a>`
            : type === 'progress'
                ? `<a class="btn btn-outline" href="${buildPirRoute(report.id)}">Download Word</a>`
                : `<a class="btn btn-outline" href="${buildFirRoute(report.id)}">Download Word</a>`;
        manageReportModalFooter.innerHTML = `${downloadButton}<button type="button" class="btn btn-outline" data-close-manage-modal>Close</button>`;
    }

    async function openEditModal(type, report) {
        if (type === 'progress') {
            await ensureModalData('progress');
        } else if (type === 'final') {
            await ensureModalData('final');
        } else {
            await ensureModalData('spot');
        }

        openManageModal(`Edit ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = renderEditForm(type, report);
        manageReportModalFooter.innerHTML = `
            <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
            <button type="button" class="btn btn-primary" id="saveManagedReportButton"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        `;

        document.getElementById('saveManagedReportButton')?.addEventListener('click', () => saveManagedReport(type, report.id));
    }

    async function saveManagedReport(type, reportId) {
        const form = document.getElementById('manageReportEditForm');
        if (!form) return;
        const submitButton = document.getElementById('saveManagedReportButton');
        const originalHtml = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const formData = new FormData(form);
            const response = await fetch(`${routes.reportManageBase}/${type}-investigation/${reportId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
                body: formData,
            });

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to update report.');
            }

            const updated = { ...(reportStore[type][reportId] || {}) };
            Object.fromEntries(formData.entries());
            if (type === 'spot') {
                updated.dead = formData.get('dead');
                updated.injured = formData.get('injured');
                updated.owner = formData.get('owner');
                updated.report_completed_at = formData.get('report_completed_at');
                updated.memorandum_for = formData.get('memorandum_for');
                updated.fire_started = formData.get('fire_started');
                updated.fire_out = formData.get('fire_out');
                updated.place_of_occurrence = formData.get('place_of_occurrence');
                updated.estimated_damage = formData.get('estimated_damage');
                updated.estimated_area = formData.get('estimated_area');
                updated.affected_establishments_count = formData.get('affected_establishments_count');
                updated.highest_alarm_level = formData.get('highest_alarm_level');
                updated.details_of_investigation = formData.get('details_of_investigation');
                updated.disposition = formData.get('disposition');
                updated.noted_by_station_officer_id = formData.get('noted_by_station_officer_id');
                updated.approved_by_station_officer_id = formData.get('approved_by_station_officer_id');
            } else if (type === 'progress') {
                updated.fire_origin_id = result.data?.fire_origin_id ?? formData.get('fire_origin_id');
                updated.fire_origin_name = result.data?.fire_origin?.origin || formData.get('fire_origin_other') || manageReportModalBody.querySelector('select[name="fire_origin_id"]')?.selectedOptions?.[0]?.textContent?.trim() || 'N/A';
                updated.responsible_arson = formData.get('responsible_arson');
                updated.report_completed_at = formData.get('report_completed_at');
                updated.fire_cause = formData.get('fire_cause');
                updated.facts_of_case = formData.get('facts_of_case');
                updated.disposition = formData.get('disposition');
                updated.noted_by_station_officer_id = formData.get('noted_by_station_officer_id');
                updated.approved_by_station_officer_id = formData.get('approved_by_station_officer_id');
            } else {
                updated.total_damage = formData.get('total_damage');
                updated.report_completed_at = formData.get('report_completed_at');
                updated.office_address = formData.get('office_address');
                updated.exact_address = formData.get('exact_address');
                updated.memorandum_for = formData.get('memorandum_for');
                updated.alarm_at = formData.get('alarm_at');
                updated.establishment_burned = formData.get('establishment_burned');
                updated.fire_victims = formData.get('fire_victims');
                updated.origin_of_fire = formData.get('origin_of_fire');
                updated.cause_of_fire = formData.get('cause_of_fire');
                updated.facts_of_case = formData.get('facts_of_case');
                updated.discussion = formData.get('discussion');
                updated.findings = formData.get('findings');
                updated.recommendation = formData.get('recommendation');
                updated.substantiating_documents = getCheckedValues(form, 'substantiating_documents[]');
                updated.noted_by_station_officer_id = formData.get('noted_by_station_officer_id');
                updated.approved_by_station_officer_id = formData.get('approved_by_station_officer_id');
            }

            reportStore[type][reportId] = updated;
            replaceRow(type, updated);
            closeManageModal();
            showToast('Report updated successfully.', 'success');
        } catch (error) {
            console.error('Update failed:', error);
            showToast(error.message || 'Failed to update report.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalHtml;
        }
    }

    async function deleteManagedReport(type, reportId) {
        const report = reportStore[type][reportId];
        if (!report) return;

        openManageModal(`Delete ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = `
            <div class="detail-item">
                <div class="detail-label">Delete Report</div>
                <div class="detail-value">This will permanently remove the ${type} investigation report for ${escapeHtml(report.incident_location || `record #${reportId}`)}.</div>
            </div>
        `;
        manageReportModalFooter.innerHTML = `
            <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteReportButton"><i class="fa-solid fa-trash"></i> Delete Report</button>
        `;

        document.getElementById('confirmDeleteReportButton')?.addEventListener('click', async () => {
            try {
                const response = await fetch(`${routes.reportManageBase}/${type}-investigation/${reportId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'DELETE',
                    },
                });
                const result = await response.json();
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Failed to delete report.');
                }
                document.getElementById(`${type}-report-${reportId}`)?.remove();
                delete reportStore[type][reportId];
                decrementCounter(`${type}ReportsCount`);
                closeManageModal();
                showToast('Report deleted successfully.', 'success');
            } catch (error) {
                console.error('Delete failed:', error);
                showToast(error.message || 'Failed to delete report.', 'error');
            }
        });
    }

    async function submitReportForm(form, endpoint, mapSuccessData, handleSuccess) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalHtml = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

        try {
            const formData = new FormData(form);
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to submit report.');
            }

            const mapped = mapSuccessData(formData, result.data || {});
            handleSuccess(mapped);
            form.reset();
            showToast('Investigation report submitted successfully.', 'success');
            return mapped;
        } catch (error) {
            console.error('Investigation report submission failed:', error);
            showToast(error.message || 'Failed to submit report.', 'error');
            return null;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalHtml;
        }
    }

    function syncFireOriginOtherVisibility(form, selectedValue = null, selectedLabel = '') {
        const select = form?.querySelector('select[name="fire_origin_id"]');
        const otherGroup = form?.querySelector('[data-fire-origin-other-group]');
        const otherInput = form?.querySelector('input[name="fire_origin_other"]');
        if (!select || !otherGroup || !otherInput) return;

        const resolvedValue = selectedValue ?? select.value;
        const isOther = resolvedValue === 'other';
        otherGroup.style.display = isOther ? '' : 'none';
        otherInput.required = isOther;

        if (isOther && selectedLabel && !otherInput.value) {
            otherInput.value = selectedLabel;
        }
        if (!isOther) {
            otherInput.value = '';
        }
    }

    function bindFireOriginOtherField(scope = document) {
        scope.querySelectorAll('form').forEach((form) => {
            const select = form.querySelector('select[name="fire_origin_id"]');
            const otherGroup = form.querySelector('[data-fire-origin-other-group]');
            if (!select || !otherGroup || select.dataset.fireOriginOtherBound === 'true') return;
            select.dataset.fireOriginOtherBound = 'true';
            select.addEventListener('change', () => syncFireOriginOtherVisibility(form));
            syncFireOriginOtherVisibility(form);
        });
    }

    async function generateNarrative(endpoint, payload, fields, triggerButton) {
        const originalHtml = triggerButton.innerHTML;
        triggerButton.disabled = true;
        triggerButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Drafting...';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to generate a narrative draft.');
            }

            Object.entries(fields).forEach(([name, target]) => {
                const input = target.form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.value = result.data?.[target.key] || '';
                }
            });
            showToast('Narrative draft generated. You can still edit it before saving.', 'success');
        } catch (error) {
            console.error('Narrative draft failed:', error);
            showToast(error.message || 'Failed to generate a narrative draft.', 'error');
        } finally {
            triggerButton.disabled = false;
            triggerButton.innerHTML = originalHtml;
        }
    }

    function bindNarrativeButtons() {
        document.querySelectorAll('[data-generate-spot-narrative]').forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const formData = new FormData(form);
                generateNarrative(routes.spotNarrative, Object.fromEntries(formData.entries()), {
                    details_of_investigation: { form, key: 'narrative' },
                }, button);
            });
        });

        document.querySelectorAll('[data-generate-progress-narrative]').forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const formData = new FormData(form);
                generateNarrative(routes.progressNarrative, Object.fromEntries(formData.entries()), {
                    facts_of_case: { form, key: 'narrative' },
                }, button);
            });
        });

        document.querySelectorAll('[data-generate-final-narrative]').forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const formData = new FormData(form);
                const payload = Object.fromEntries(formData.entries());
                payload.substantiating_documents = formData.getAll('substantiating_documents[]');
                generateNarrative(routes.finalNarrative, payload, {
                    facts_of_case: { form, key: 'facts_of_case' },
                    discussion: { form, key: 'discussion' },
                    findings: { form, key: 'findings' },
                    recommendation: { form, key: 'recommendation' },
                }, button);
            });
        });
    }

    function bindForms() {
        bindFireOriginOtherField(document);
        bindNarrativeButtons();

        spotForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const incidentSelect = spotForm.querySelector('select[name="incident_id"]');
            const selectedIncidentId = incidentSelect?.value;
            const selectedLocation = getSelectedOptionText(incidentSelect);
            const mapped = await submitReportForm(spotForm, routes.spotCreate, (formData, data) => ({
                ...data,
                id: data.spot_investigation_report_id,
                type: 'spot',
                incident_id: formData.get('incident_id'),
                incident_location: selectedLocation,
                investigator_name: currentOfficerName,
                fire_started: formData.get('fire_started'),
                fire_out: formData.get('fire_out'),
                estimated_damage: formData.get('estimated_damage'),
                occupancy: formData.get('occupancy'),
                estimated_area: formData.get('estimated_area'),
                dead: formData.get('dead'),
                injured: formData.get('injured'),
                owner: formData.get('owner'),
                report_completed_at: formData.get('report_completed_at'),
                memorandum_for: formData.get('memorandum_for'),
                place_of_occurrence: formData.get('place_of_occurrence'),
                involved_structure: formData.get('involved_structure'),
                establishment_name: formData.get('establishment_name'),
                occupant: formData.get('occupant'),
                highest_alarm_level: formData.get('highest_alarm_level'),
                affected_establishments_count: formData.get('affected_establishments_count'),
                description: formData.get('description'),
                fatality_details: formData.get('fatality_details'),
                details_of_investigation: formData.get('details_of_investigation'),
                disposition: formData.get('disposition'),
                noted_by_station_officer_id: formData.get('noted_by_station_officer_id'),
                approved_by_station_officer_id: formData.get('approved_by_station_officer_id'),
                has_progress: false,
            }), appendSpotReportRow);

            if (mapped) {
                reportStore.spot[mapped.id] = mapped;
                removeOption(incidentSelect, selectedIncidentId);
                closeReportModal('spot');
                switchReportTab('spot');
            }
        });

        progressForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const progressSelect = progressForm.querySelector('select[name="spot_investigation_id"]');
            const selectedSpotId = progressSelect?.value;
            const selectedLocation = getSelectedOptionText(progressSelect);
            const originLabel = progressForm.querySelector('input[name="fire_origin_other"]')?.value?.trim()
                || progressForm.querySelector('select[name="fire_origin_id"]')?.selectedOptions?.[0]?.textContent?.trim()
                || 'N/A';
            const mapped = await submitReportForm(progressForm, routes.progressCreate, (formData, data) => ({
                ...data,
                id: data.progress_investigation_report_id,
                type: 'progress',
                spot_investigation_id: formData.get('spot_investigation_id'),
                incident_location: selectedLocation,
                investigator_name: currentOfficerName,
                fire_origin_id: data.fire_origin_id ?? formData.get('fire_origin_id'),
                fire_origin_name: data.fire_origin?.origin || originLabel,
                fire_cause: formData.get('fire_cause'),
                responsible_arson: formData.get('responsible_arson'),
                report_completed_at: formData.get('report_completed_at'),
                memorandum_for: formData.get('memorandum_for'),
                mission_order_number: formData.get('mission_order_number'),
                mission_order_date: formData.get('mission_order_date'),
                facts_of_case: formData.get('facts_of_case'),
                disposition: formData.get('disposition'),
                noted_by_station_officer_id: formData.get('noted_by_station_officer_id'),
                approved_by_station_officer_id: formData.get('approved_by_station_officer_id'),
                has_final: false,
            }), appendProgressReportRow);

            if (mapped) {
                reportStore.progress[mapped.id] = mapped;
                if (reportStore.spot[selectedSpotId]) {
                    reportStore.spot[selectedSpotId].has_progress = true;
                    replaceRow('spot', reportStore.spot[selectedSpotId]);
                }
                removeOption(progressSelect, selectedSpotId);
                closeReportModal('progress');
                switchReportTab('progress');
            }
        });

        finalForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const progressSelect = finalForm.querySelector('select[name="progress_investigation_id"]');
            const selectedProgressId = progressSelect?.value;
            const selectedLocation = getSelectedOptionText(progressSelect);
            const mapped = await submitReportForm(finalForm, routes.finalCreate, (formData, data) => ({
                ...data,
                id: data.final_investigation_report_id,
                type: 'final',
                progress_investigation_id: formData.get('progress_investigation_id'),
                incident_location: selectedLocation,
                investigator_name: currentOfficerName,
                report_completed_at: data.report_completed_at || formData.get('report_completed_at') || new Date().toISOString(),
                memorandum_for: formData.get('memorandum_for'),
                office_address: data.office_address || formData.get('office_address'),
                exact_address: formData.get('exact_address'),
                alarm_at: data.alarm_at || formData.get('alarm_at'),
                establishment_burned: data.establishment_burned || formData.get('establishment_burned'),
                fire_victims: data.fire_victims || formData.get('fire_victims'),
                origin_of_fire: data.origin_of_fire || formData.get('origin_of_fire') || reportStore.progress[selectedProgressId]?.fire_origin_name || 'N/A',
                cause_of_fire: data.cause_of_fire || formData.get('cause_of_fire') || reportStore.progress[selectedProgressId]?.fire_cause || 'N/A',
                total_damage: formData.get('total_damage'),
                substantiating_documents: data.substantiating_documents || formData.getAll('substantiating_documents[]'),
                facts_of_case: formData.get('facts_of_case'),
                discussion: formData.get('discussion'),
                findings: formData.get('findings'),
                recommendation: formData.get('recommendation'),
                created_at: new Date().toISOString(),
                fire_origin_name: reportStore.progress[selectedProgressId]?.fire_origin_name || 'N/A',
                fire_cause: reportStore.progress[selectedProgressId]?.fire_cause || 'N/A',
                noted_by_station_officer_id: formData.get('noted_by_station_officer_id'),
                approved_by_station_officer_id: formData.get('approved_by_station_officer_id'),
            }), appendFinalReportRow);

            if (mapped) {
                reportStore.final[mapped.id] = mapped;
                if (reportStore.progress[selectedProgressId]) {
                    reportStore.progress[selectedProgressId].has_final = true;
                    replaceRow('progress', reportStore.progress[selectedProgressId]);
                }
                removeOption(progressSelect, selectedProgressId);
                closeReportModal('final');
                switchReportTab('final');
            }
        });
    }

    const documentClickHandler = async (event) => {
        const retryButton = event.target.closest('[data-station-retry]');
        if (retryButton) {
            const handler = retryButton.getAttribute('data-station-retry');
            if (handler && typeof window[handler] === 'function') {
                window[handler](true);
            }
            return;
        }

        const reportOpenButton = event.target.closest('[data-open-report-modal]');
        if (reportOpenButton) {
            await openReportModal(reportOpenButton.dataset.openReportModal);
            return;
        }

        const reportCloseButton = event.target.closest('[data-close-report-modal]');
        if (reportCloseButton) {
            closeReportModal(reportCloseButton.dataset.closeReportModal);
            return;
        }

        if (event.target.matches('[data-close-manage-modal]') || event.target.closest('[data-close-manage-modal]')) {
            closeManageModal();
            return;
        }

        const actionButton = event.target.closest('[data-report-action]');
        if (actionButton) {
            const { reportAction, reportType, reportId } = actionButton.dataset;
            const report = reportStore[reportType]?.[reportId];
            if (!report) return;
            if (reportAction === 'view') await openViewModal(reportType, report);
            if (reportAction === 'edit') await openEditModal(reportType, report);
            if (reportAction === 'delete') await deleteManagedReport(reportType, reportId);
            return;
        }

        const paginationLink = event.target.closest('#stationReportsPanelContainer a');
        if (paginationLink && paginationLink.href && paginationLink.href.includes('/firefighter/api/my-station/reports')) {
            event.preventDefault();
            const parsed = new URL(paginationLink.href, window.location.origin);
            await loadReports(parsed.searchParams.get('tab') || initialReportTab, parsed.toString());
            return;
        }
    };

    document.addEventListener('click', documentClickHandler);

    reportTabs?.addEventListener('click', (event) => {
        const tab = event.target.closest('.report-tab');
        if (!tab) return;
        switchReportTab(tab.dataset.reportTab);
    });

    document.querySelectorAll('.report-modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    window.loadFirefighterStationOverview = loadOverview;
    window.loadFirefighterStationPersonnel = loadPersonnel;
    window.loadFirefighterStationEquipment = loadEquipment;
    window.loadFirefighterStationReports = () => loadReports(currentReportTab);

    bindForms();
    loadOverview();
    loadPersonnel();
    loadReports(initialReportTab);
    loadEquipment();
    handleReportDeepLink();

    window.__ffCurrentPageCleanup = function () {
        cleanupRequests();
        closeManageModal();
        document.querySelectorAll('.report-modal.active').forEach((modal) => modal.classList.remove('active'));
        document.removeEventListener('click', documentClickHandler);
    };
}

window.initFirefighterMyStationPage = bootFirefighterMyStationPage;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFirefighterMyStationPage, { once: true });
} else {
    bootFirefighterMyStationPage();
}
