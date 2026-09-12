const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const notify = (message, type = 'success') => {
    if (window.PyroNotify?.notify) {
        window.PyroNotify.notify(message, type);
        return;
    }

    window.alert(message);
};

const setBusy = (form, busy) => {
    form.toggleAttribute('aria-busy', busy);
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;

    if (busy) {
        button.dataset.dynamicOriginalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
    } else {
        button.disabled = false;
        if (button.dataset.dynamicOriginalHtml) button.innerHTML = button.dataset.dynamicOriginalHtml;
    }
};

const request = async (form) => {
    const method = (form.method || 'GET').toUpperCase();
    const options = {
        method,
        headers: {
            Accept: 'application/json, text/html;q=0.9',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    };
    let url = form.action || window.location.href;

    if (method === 'GET') {
        const target = new URL(url, window.location.origin);
        target.search = new URLSearchParams(new FormData(form)).toString();
        url = target.toString();
    } else {
        options.body = new FormData(form);
    }

    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
            const validation = Object.values(payload.errors || {}).flat().find(Boolean);
            throw new Error(validation || payload.message || 'The action could not be completed.');
        }
        return { payload, url: response.url || url };
    }

    const html = await response.text();
    if (!response.ok) throw new Error('The action could not be completed.');
    return { document: new DOMParser().parseFromString(html, 'text/html'), url: response.url || url };
};

const replaceFromDocument = (nextDocument, selectors) => {
    selectors.forEach((selector) => {
        const current = document.querySelector(selector);
        const incoming = nextDocument?.querySelector(selector);
        if (current && incoming) current.replaceWith(incoming);
    });
};

const partialPageConfig = () => {
    if (document.querySelector('.reports-shell')) return { selectors: ['.reports-shell'], focus: '.report-card' };
    if (document.querySelector('.weather-shell')) return { selectors: ['.weather-shell'], focus: '#weatherRecords' };
    if (document.querySelector('.barangay-card')) return { selectors: ['.barangay-card'], focus: '.barangay-card' };
    if (document.querySelector('[data-risk-feed-root]')) {
        return {
            selectors: ['[data-risk-feed-root="risks"]', '[data-risk-feed-root="markers"]'],
            focus: '[data-risk-feed-root="risks"]',
        };
    }
    if (document.querySelector('[data-profile-activity-root]')) {
        return { selectors: ['[data-profile-activity-root]'], focus: '[data-profile-activity-root]' };
    }
    if (document.querySelector('.main-content .list') && document.querySelector('.filters')) {
        return { selectors: ['.main-content .shell'], focus: '.main-content .list' };
    }

    return null;
};

const prepareDynamicContent = () => {
    document.querySelectorAll('.barangay-card .barangay-row').forEach((row) => {
        row.dataset.dynamicBarangayRow = 'true';
    });
    document.querySelectorAll('[data-risk-feed-root] [data-risk-detail]').forEach((button) => {
        button.dataset.dynamicRiskDetail = 'true';
    });
};

const navigatePartialPage = async (url, { historyMode = 'push', focus = true } = {}) => {
    const config = partialPageConfig();
    if (!config || document.documentElement.dataset.dynamicNavigating === 'true') return false;

    document.documentElement.dataset.dynamicNavigating = 'true';
    document.querySelector(config.selectors[0])?.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('The page could not be updated.');

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        replaceFromDocument(nextDocument, config.selectors);
        prepareDynamicContent();

        if (historyMode === 'replace') window.history.replaceState({}, '', response.url || url);
        if (historyMode === 'push') window.history.pushState({}, '', response.url || url);

        if (focus) {
            const target = document.querySelector(config.focus);
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        return true;
    } catch (error) {
        notify(error.message || 'The page could not be updated.', 'error');
        return false;
    } finally {
        document.documentElement.dataset.dynamicNavigating = 'false';
        document.querySelector(config.selectors[0])?.removeAttribute('aria-busy');
    }
};

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const isAar = form.matches('.review, .filters') && document.querySelector('.main-content .list');
    const isWeather = form.matches('.weather-card-backfill, .weather-backfill, .weather-filter');
    const isReportsFilter = form.matches('.report-filter') && document.querySelector('.reports-shell');
    const isLandingMedia = form.matches('[data-media-form]') || Boolean(form.closest('.lm-list'));
    const isMitigation = form.matches('#createForm, #editForm, #deleteForm, [data-suggestion-scan-form]')
        || Boolean(form.closest('#mitigationTableData'))
        || form.action.includes('/mitigations/generated-drafts/');
    const isRiskReview = form.method.toUpperCase() !== 'GET'
        && form.action.includes('/risk-reviews');
    const isStationMedia = Boolean(form.closest('.station-media-admin-card'))
        && form.action.includes('/stations/')
        && form.action.includes('/media/');
    const isCommunityRequest = form.action.includes('/community-activity-requests')
        && !form.closest('#requestReviewModal');
    const isBarangayCrud = form.action.includes('/barangays')
        && (form.id === 'barangayDeleteForm' || Boolean(form.querySelector('[name="barangay_name"]')));
    if (!isAar && !isWeather && !isReportsFilter && !isLandingMedia && !isMitigation && !isRiskReview && !isStationMedia && !isCommunityRequest && !isBarangayCrud) return;
    if (form.hasAttribute('data-offline-submit') && !window.navigator.onLine) return;

    event.preventDefault();
    if (form.dataset.dynamicSubmitting === 'true') return;
    form.dataset.dynamicSubmitting = 'true';
    setBusy(form, true);

    try {
        const result = await request(form);

        if (isReportsFilter) {
            replaceFromDocument(result.document, ['.reports-shell']);
            window.history.replaceState({}, '', result.url);
        } else if (isAar) {
            let nextDocument = result.document;
            if (!nextDocument) {
                const refreshed = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                nextDocument = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
            }
            replaceFromDocument(nextDocument, ['.main-content .list', '.main-content .pagination']);
            if (form.matches('.filters')) window.history.replaceState({}, '', result.url);
            else notify(result.payload?.message || 'After activity report updated.');
        } else if (isWeather) {
            replaceFromDocument(result.document, ['.weather-shell']);
            if ((form.method || 'GET').toUpperCase() === 'GET') window.history.replaceState({}, '', result.url);
            notify('Weather records updated.');
        } else if (isLandingMedia) {
            replaceFromDocument(result.document, ['.lm-shell']);
            notify('Landing content updated.');
        } else if (isMitigation) {
            window.dispatchEvent(new CustomEvent('pyro:mitigations-mutated'));
            notify(result.payload?.message || 'Mitigation record updated.');
        } else if (isRiskReview) {
            let nextDocument = result.document;
            if (!nextDocument) {
                const refreshed = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                nextDocument = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
            }
            replaceFromDocument(nextDocument, ['#riskReviewResults']);
            replaceFromDocument(nextDocument, ['[data-review-dynamic-root]']);
            window.dispatchEvent(new CustomEvent('pyro:risk-reviews-mutated'));
            notify(result.payload?.message || 'Risk review updated.');
        } else if (isStationMedia) {
            const card = form.closest('.station-media-admin-card');
            if (result.payload?.data?.url) {
                const preview = card?.querySelector('.station-media-preview');
                if (preview) preview.innerHTML = `<img src="${result.payload.data.url}" alt="Station profile">`;
            } else if (Array.isArray(result.payload?.data)) {
                let grid = card?.querySelector('.station-media-mini-grid');
                if (!grid && card) {
                    grid = document.createElement('div');
                    grid.className = 'station-media-mini-grid';
                    card.appendChild(grid);
                }
                result.payload.data.slice(0, Math.max(0, 4 - (grid?.children.length || 0))).forEach((item) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'station-media-mini';
                    wrapper.innerHTML = `<img src="${item.url || ''}" alt="Station gallery photo">`;
                    grid?.appendChild(wrapper);
                });
            }
            form.reset();
            notify(result.payload?.message || 'Station media updated.');
        } else if (isCommunityRequest) {
            if (form.classList.contains('community-request-edit')) {
                form.querySelectorAll('input, select, textarea').forEach((field) => field.dispatchEvent(new Event('change', { bubbles: true })));
            } else {
                form.reset();
            }
            const modal = form.closest('.profile-modal, .citizen-request-modal, dialog');
            modal?.removeAttribute('open');
            if (modal) {
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('citizen-request-modal-open');
            window.dispatchEvent(new CustomEvent('pyro:community-request-saved'));
            notify(result.payload?.message || 'Community activity request saved.');
        } else if (isBarangayCrud) {
            const name = String(form.querySelector('[name="barangay_name"]')?.value || '').trim();
            if (form.id === 'barangayEditForm') {
                const id = form.querySelector('[name="editing_barangay_id"]')?.value;
                const trigger = document.querySelector(`[data-barangay-edit-trigger][data-barangay-id="${CSS.escape(String(id || ''))}"]`);
                const row = trigger?.closest('.barangay-row');
                const label = row?.querySelector('.barangay-row__identity .barangay-name');
                if (label) label.textContent = name;
                if (trigger) trigger.dataset.barangayName = name;
                const deleteTrigger = row?.querySelector('[data-barangay-delete-trigger]');
                if (deleteTrigger) deleteTrigger.dataset.barangayName = name;
            } else if (form.id === 'barangayDeleteForm') {
                const deletedUrl = form.action;
                document.querySelectorAll('[data-barangay-delete-trigger]').forEach((trigger) => {
                    if (trigger.dataset.deleteUrl === deletedUrl) trigger.closest('.barangay-row')?.remove();
                });
            } else {
                const incomingRows = Array.from(result.document?.querySelectorAll('.barangay-row') || []);
                const incoming = incomingRows.find((row) => row.querySelector('.barangay-row__identity .barangay-name')?.textContent.trim() === name);
                const list = document.querySelector('.barangay-card');
                if (incoming && list && !Array.from(list.querySelectorAll('.barangay-row')).some((row) => row.querySelector('.barangay-name')?.textContent.trim() === name)) {
                    incoming.dataset.dynamicBarangayRow = 'true';
                    list.querySelector('.barangay-empty')?.remove();
                    const pagination = list.querySelector('.barangay-pagination');
                    if (pagination) pagination.before(incoming);
                    else list.appendChild(incoming);
                }
                form.reset();
            }

            form.dataset.submitting = 'false';
            form.closest('.barangay-modal-backdrop')?.classList.remove('active');
            document.body.classList.remove('menu-open');
            notify('Barangay information updated.');
        }
    } catch (error) {
        notify(error.message || 'The action could not be completed.', 'error');
    } finally {
        form.dataset.dynamicSubmitting = 'false';
        if (form.isConnected) setBusy(form, false);
    }
});

document.addEventListener('click', async (event) => {
    const navigationLink = event.target.closest?.(
        '.report-pagination a[href], .weather-pagination a[href], .barangay-pagination a[href], '
        + '.main-content .pagination a[href], .report-filter a[href], .weather-filter a[href], '
        + '.weather-forecast-card[href], #weatherRecords .weather-card__badge[href], '
        + '.barangay-risk-pagination a[href], .profile-pagination a[href]'
    );
    if (navigationLink && partialPageConfig()) {
        event.preventDefault();
        await navigatePartialPage(navigationLink.href);
        return;
    }

    const riskDetail = event.target.closest?.('[data-dynamic-risk-detail]');
    if (riskDetail) {
        try {
            const payload = JSON.parse(riskDetail.dataset.riskDetail || '{}');
            const modal = document.getElementById('barangayRiskDetailModal');
            const kind = document.getElementById('barangayRiskDetailKind');
            const title = document.getElementById('barangayRiskDetailTitle');
            const body = document.getElementById('barangayRiskDetailBody');
            if (kind) kind.textContent = payload.kind || 'Record';
            if (title) title.textContent = payload.title || 'Details';
            if (body) {
                body.replaceChildren();
                const description = document.createElement('p');
                description.textContent = payload.description || 'No description provided.';
                body.appendChild(description);
                (payload.fields || []).forEach((field) => {
                    const item = document.createElement('div');
                    item.className = 'list-item';
                    const label = document.createElement('strong');
                    label.textContent = field.label || 'Detail';
                    const value = document.createElement('p');
                    value.textContent = field.value || 'Not available';
                    item.append(label, value);
                    body.appendChild(item);
                });
            }
            if (modal) modal.hidden = false;
            modal?.querySelector('[data-risk-detail-close]')?.focus();
        } catch {
            notify('Unable to open this record.', 'error');
        }
        return;
    }

    const row = event.target.closest?.('[data-dynamic-barangay-row]');
    if (!row) return;

    const edit = event.target.closest('[data-barangay-edit-trigger]');
    if (edit) {
        const form = document.getElementById('barangayEditForm');
        if (!form) return;
        const id = edit.dataset.barangayId || '';
        form.action = String(form.dataset.actionTemplate || '').replace('__BARANGAY__', id);
        const idField = form.querySelector('[name="editing_barangay_id"]');
        const nameField = form.querySelector('[name="barangay_name"]');
        if (idField) idField.value = id;
        if (nameField) nameField.value = edit.dataset.barangayName || '';
        document.getElementById('barangayEditModal')?.classList.add('active');
        document.body.classList.add('menu-open');
        return;
    }

    const details = event.target.closest('[data-barangay-details-trigger]');
    if (details) {
        const modal = document.getElementById('barangayDetailsModal');
        const body = document.getElementById('barangayDetailsModalBody');
        modal?.classList.add('active');
        document.body.classList.add('menu-open');
        if (body) body.innerHTML = '<p class="barangay-details-loading">Loading...</p>';
        fetch(details.dataset.detailsUrl || '', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((payload) => {
                const data = payload.data || {};
                const title = document.getElementById('barangayDetailsModalTitle');
                const copy = document.getElementById('barangayDetailsModalCopy');
                if (title) title.textContent = data.barangay_name || 'Barangay details';
                if (copy) copy.textContent = [data.city_name, data.province_name].filter(Boolean).join(', ');
                if (body) body.innerHTML = `<div class="barangay-details-grid">
                    <div class="barangay-details-stat"><div class="barangay-details-stat__label">Residents</div><div class="barangay-details-stat__value">${data.summary?.resident_count || 0}</div></div>
                    <div class="barangay-details-stat"><div class="barangay-details-stat__label">Incidents</div><div class="barangay-details-stat__value">${data.summary?.live_incident_count || 0}</div></div>
                    <div class="barangay-details-stat"><div class="barangay-details-stat__label">Risk areas</div><div class="barangay-details-stat__value">${data.summary?.risk_area_count || 0}</div></div>
                </div>`;
            })
            .catch(() => { if (body) body.textContent = 'Unable to load barangay details.'; });
        return;
    }

    const remove = event.target.closest('[data-barangay-delete-trigger]');
    if (remove) {
        const form = document.getElementById('barangayDeleteForm');
        if (form) form.action = remove.dataset.deleteUrl || '';
        const force = document.getElementById('force_delete_related');
        if (force) force.value = '0';
        const title = document.getElementById('barangayDeleteModalTitle');
        if (title) title.textContent = `Delete ${remove.dataset.barangayName || 'barangay'}`;
        document.getElementById('barangayDeleteModal')?.classList.add('active');
        document.body.classList.add('menu-open');
    }
});

window.addEventListener('popstate', () => {
    if (partialPageConfig()) navigatePartialPage(window.location.href, { historyMode: 'none', focus: false });
});

document.addEventListener('change', (event) => {
    const type = event.target.closest?.('[data-media-type]');
    if (!type) return;
    const form = type.closest('[data-media-form]');
    form?.querySelectorAll('[data-image-field]').forEach((field) => field.hidden = type.value !== 'image');
    form?.querySelectorAll('[data-video-field]').forEach((field) => field.hidden = type.value !== 'video');
});
