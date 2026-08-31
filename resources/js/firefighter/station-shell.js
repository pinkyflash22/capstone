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
    const currentOfficer = config.currentOfficer || { name: currentOfficerName };
    const finalSubstantiatingDocumentOptions = Array.isArray(config.finalSubstantiatingDocumentOptions)
        ? config.finalSubstantiatingDocumentOptions
        : [];
    const reportStore = { spot: {}, progress: {}, final: {}, aar: {} };
    const referenceCache = { spot: null, progress: null, final: null, aar: null };
    const requestControllers = new Map();
    let activeCrewMemberTrigger = null;
    let rosterSearchQuery = '';
    let maintenanceFilter = 'all';
    let readinessRefreshTimer = null;
    let readinessRefreshInFlight = false;
    let lastReadinessRefreshAt = 0;
    const READINESS_REFRESH_INTERVAL_MS = Math.max(10000, Number(config.readinessRefreshIntervalMs) || 30000);
    const MAX_PROOF_UPLOAD_BYTES = 25 * 1024 * 1024;
    const MAX_PROOF_UPLOAD_FILES = 20;
    const ALLOWED_PROOF_IMAGE_TYPES = ['image/jpeg', 'image/png'];

    const overviewSection = document.getElementById('stationOverviewSection');
    const personnelSection = document.getElementById('stationPersonnelSection');
    const equipmentSection = document.getElementById('stationEquipmentSection');
    const reportsContainer = document.getElementById('stationReportsPanelContainer');
    const reportTabs = document.getElementById('reportTabs');
    const manageReportModal = document.getElementById('manageReportModal');
    const manageReportModalTitle = document.getElementById('manageReportModalTitle');
    const manageReportModalBody = document.getElementById('manageReportModalBody');
    const manageReportModalFooter = document.getElementById('manageReportModalFooter');
    const spotForm = document.getElementById('spotReportForm');
    const progressForm = document.getElementById('progressReportForm');
    const finalForm = document.getElementById('finalReportForm');
    const aarForm = document.getElementById('aarReportForm');
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

    function createCurrentOfficerTrigger(contextLabel = 'Report investigator') {
        const name = currentOfficer.name || currentOfficerName;
        const rank = currentOfficer.rank || 'Rank not recorded';
        const initials = currentOfficer.initials || 'FF';
        const photo = currentOfficer.image
            ? `<img src="${escapeHtml(currentOfficer.image)}" alt="" loading="lazy">`
            : `<span>${escapeHtml(initials)}</span>`;

        return `<button type="button" class="report-person-card" data-crew-member-card
            data-member-group="${escapeHtml(contextLabel)}"
            data-member-name="${escapeHtml(name)}"
            data-member-position="${escapeHtml(rank)}"
            data-member-role="${escapeHtml(currentOfficer.role || 'Firefighter')}"
            data-member-rank="${escapeHtml(rank)}"
            data-member-status="${escapeHtml(currentOfficer.status || 'Available')}"
            data-member-email="${escapeHtml(currentOfficer.email || '')}"
            data-member-contact="${escapeHtml(currentOfficer.contact || '')}"
            data-member-image="${escapeHtml(currentOfficer.image || '')}"
            data-member-initials="${escapeHtml(initials)}"
            aria-haspopup="dialog" aria-controls="crewMemberDetailModal">
            <span class="report-person-photo" aria-hidden="true">${photo}</span>
            <span class="report-person-copy"><strong>${escapeHtml(name)}</strong><small>${escapeHtml(rank)}</small></span>
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        </button>`;
    }

    function closeCrewMemberModal({ restoreFocus = true } = {}) {
        const modal = document.getElementById('crewMemberDetailModal');
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('crew-member-modal-open');

        if (restoreFocus) {
            activeCrewMemberTrigger?.focus?.({ preventScroll: true });
        }
        activeCrewMemberTrigger = null;
    }

    function openCrewMemberModal(card) {
        const modal = document.getElementById('crewMemberDetailModal');
        if (!modal || !card) return;

        const data = card.dataset;
        const setText = (selector, value, fallback) => {
            const node = modal.querySelector(selector);
            if (node) node.textContent = String(value || fallback);
        };

        setText('[data-crew-modal-name]', data.memberName, 'Firefighter');
        setText('[data-crew-modal-group]', data.memberGroup, 'Station personnel');
        setText('[data-crew-modal-position]', data.memberPosition, 'Response Crew');
        setText('[data-crew-modal-rank]', data.memberRank, 'Not recorded');
        setText('[data-crew-modal-role]', data.memberRole, 'Firefighter');
        setText('[data-crew-modal-status]', data.memberStatus, 'Available');
        setText('[data-crew-modal-contact]', data.memberContact, 'Not provided');

        const photo = modal.querySelector('[data-crew-modal-photo]');
        if (photo) {
            photo.replaceChildren();
            if (data.memberImage) {
                const image = document.createElement('img');
                image.src = data.memberImage;
                image.alt = `${data.memberName || 'Firefighter'} profile`;
                image.addEventListener('error', () => {
                    const fallback = document.createElement('span');
                    fallback.textContent = data.memberInitials || 'FF';
                    photo.replaceChildren(fallback);
                }, { once: true });
                photo.appendChild(image);
            } else {
                const fallback = document.createElement('span');
                fallback.textContent = data.memberInitials || 'FF';
                photo.appendChild(fallback);
            }
        }

        const emailLink = modal.querySelector('[data-crew-modal-email]');
        if (emailLink) {
            const email = String(data.memberEmail || '').trim();
            emailLink.hidden = !email;
            emailLink.removeAttribute('href');
            if (email) {
                emailLink.href = `mailto:${email}`;
                const label = emailLink.querySelector('span');
                if (label) label.textContent = email;
            }
        }

        activeCrewMemberTrigger = card;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('crew-member-modal-open');
        modal.querySelector('[data-crew-member-close]')?.focus?.({ preventScroll: true });
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

    function buildAarRoute(reportId) {
        return String(routes.aarTemplate || '').replace('__AAR_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildAarPdfRoute(reportId) {
        return String(routes.aarPdfTemplate || '').replace('__AAR_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildAarUpdateRoute(reportId) {
        return String(routes.aarUpdateTemplate || '').replace('__AAR_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildAarDeleteRoute(reportId) {
        return String(routes.aarDeleteTemplate || '').replace('__AAR_REPORT_ID__', encodeURIComponent(reportId));
    }

    function buildProofImageDeleteRoute(reportType, reportId, imageId) {
        return String(routes.proofImageDeleteTemplate || '')
            .replace('__REPORT_TYPE__', encodeURIComponent(reportType))
            .replace('__REPORT_ID__', encodeURIComponent(reportId))
            .replace('__IMAGE_ID__', encodeURIComponent(imageId));
    }

    function buildProofImageOrderRoute(reportType, reportId) {
        return String(routes.proofImageOrderTemplate || '')
            .replace('__REPORT_TYPE__', encodeURIComponent(reportType))
            .replace('__REPORT_ID__', encodeURIComponent(reportId));
    }

    function replaceFileInputFiles(input, files) {
        const transfer = new DataTransfer();
        files.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    }

    async function compressProofImage(file) {
        if (!ALLOWED_PROOF_IMAGE_TYPES.includes(file.type)) return file;

        let bitmap;
        try {
            bitmap = await createImageBitmap(file);
            const maximumDimension = 2400;
            const scale = Math.min(1, maximumDimension / Math.max(bitmap.width, bitmap.height));
            if (scale === 1 && file.size < 1.5 * 1024 * 1024) return file;

            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            const context = canvas.getContext('2d');
            if (!context) return file;
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

            const compressedBlob = await new Promise((resolve) => canvas.toBlob(resolve, file.type, file.type === 'image/jpeg' ? 0.82 : undefined));
            if (!compressedBlob || compressedBlob.size >= file.size) return file;
            return new File([compressedBlob], file.name, { type: file.type, lastModified: file.lastModified });
        } catch (error) {
            return file;
        } finally {
            bitmap?.close?.();
        }
    }

    function bindSelectedProofImageOrdering(input, preview) {
        preview.querySelectorAll('[data-proof-file-index]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
                card.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.proofFileIndex);
            });
            card.addEventListener('dragend', () => card.classList.remove('is-dragging'));
            card.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            });
            card.addEventListener('drop', (event) => {
                event.preventDefault();
                const fromIndex = Number(event.dataTransfer.getData('text/plain'));
                const toIndex = Number(card.dataset.proofFileIndex);
                if (!Number.isInteger(fromIndex) || !Number.isInteger(toIndex) || fromIndex === toIndex) return;
                const reordered = Array.from(input.files || []);
                const [moved] = reordered.splice(fromIndex, 1);
                reordered.splice(toIndex, 0, moved);
                replaceFileInputFiles(input, reordered);
                renderProofImagePreview(input);
            });
        });
    }

    function clearProofImagePreview(scope) {
        scope?.querySelectorAll('input[name="proof_images[]"]').forEach((input) => {
            (input._proofPreviewUrls || []).forEach((url) => URL.revokeObjectURL(url));
            input._proofPreviewUrls = [];
            const preview = input.parentElement?.querySelector('[data-proof-upload-preview]');
            if (preview) preview.innerHTML = '';
            input.setCustomValidity('');
            updateProofUploadStatus(input, { reset: true });
        });
    }

    function formatUploadSize(bytes) {
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(bytes < 1024 ? 0 : 1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function ensureProofUploadStatus(input) {
        let status = input.parentElement?.querySelector('[data-proof-upload-status]');
        if (status) return status;
        status = document.createElement('div');
        status.className = 'proof-upload-status';
        status.dataset.proofUploadStatus = '';
        status.innerHTML = `
            <div class="proof-upload-status__summary" data-proof-upload-summary>No images selected · 25 MB available</div>
            <div class="proof-upload-progress" data-proof-upload-progress><span></span></div>
        `;
        input.parentElement?.appendChild(status);
        return status;
    }

    function updateProofUploadStatus(input, options = {}) {
        const status = ensureProofUploadStatus(input);
        const summary = status.querySelector('[data-proof-upload-summary]');
        const progress = status.querySelector('[data-proof-upload-progress]');
        const progressBar = progress?.querySelector('span');
        const files = Array.from(input.files || []);
        const totalBytes = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
        const exceedsLimit = totalBytes > MAX_PROOF_UPLOAD_BYTES;
        const exceedsCount = files.length > MAX_PROOF_UPLOAD_FILES;

        if (options.reset) {
            summary.textContent = 'No images selected · 25 MB available';
            summary.classList.remove('is-error');
            progress?.classList.remove('is-active');
            if (progressBar) progressBar.style.width = '0%';
            return true;
        }

        if (options.progress !== undefined) {
            const percent = Math.max(0, Math.min(100, Number(options.progress) || 0));
            progress?.classList.add('is-active');
            if (progressBar) progressBar.style.width = `${percent}%`;
            summary.textContent = options.label || `Uploading proof images · ${Math.round(percent)}%`;
            summary.classList.remove('is-error');
            return true;
        }

        const message = exceedsCount
            ? `A maximum of ${MAX_PROOF_UPLOAD_FILES} images can be uploaded at once. Remove images to submit the form.`
            : exceedsLimit
            ? `Selected images use ${formatUploadSize(totalBytes)} and exceed the 25 MB threshold. Remove or replace images to submit the form.`
            : files.length
                ? `${files.length} image${files.length === 1 ? '' : 's'} selected · ${formatUploadSize(totalBytes)} of 25 MB`
                : 'No images selected · 25 MB available';
        summary.textContent = message;
        summary.classList.toggle('is-error', exceedsLimit || exceedsCount);
        progress?.classList.remove('is-active');
        if (progressBar) progressBar.style.width = '0%';
        input.setCustomValidity(exceedsLimit || exceedsCount ? message : '');
        return !exceedsLimit && !exceedsCount;
    }

    function renderProofImagePreview(input) {
        let preview = input.parentElement?.querySelector('[data-proof-upload-preview]');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'proof-image-grid proof-image-grid--selected';
            preview.dataset.proofUploadPreview = '';
            input.insertAdjacentElement('afterend', preview);
        }

        (input._proofPreviewUrls || []).forEach((url) => URL.revokeObjectURL(url));
        input._proofPreviewUrls = [];
        preview.innerHTML = '';

        Array.from(input.files || []).forEach((file, index) => {
            const objectUrl = URL.createObjectURL(file);
            input._proofPreviewUrls.push(objectUrl);
            const card = document.createElement('figure');
            card.className = 'proof-image-card';
            card.draggable = true;
            card.dataset.proofFileIndex = String(index);
            card.innerHTML = `
                <span class="proof-image-drag" title="Drag to reorder" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>
                <img src="${objectUrl}" alt="${escapeHtml(file.name)} preview">
                <figcaption title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</figcaption>
                <button type="button" class="proof-image-remove" aria-label="Remove ${escapeHtml(file.name)}"><i class="fa-solid fa-xmark"></i></button>
            `;
            card.querySelector('button')?.addEventListener('click', () => {
                const remaining = Array.from(input.files || []).filter((_, fileIndex) => fileIndex !== index);
                replaceFileInputFiles(input, remaining);
                renderProofImagePreview(input);
            });
            preview.appendChild(card);
        });
        bindSelectedProofImageOrdering(input, preview);
        updateProofUploadStatus(input);
    }

    function bindProofImagePreviews(scope = document) {
        scope.querySelectorAll('input[name="proof_images[]"]').forEach((input) => {
            if (input.dataset.proofPreviewBound === 'true') return;
            input.dataset.proofPreviewBound = 'true';
            ensureProofUploadStatus(input);
            input.addEventListener('change', async () => {
                const validFiles = [];
                let rejectedType = false;

                Array.from(input.files || []).forEach((file) => {
                    if (!ALLOWED_PROOF_IMAGE_TYPES.includes(file.type)) {
                        rejectedType = true;
                        return;
                    }
                    validFiles.push(file);
                });

                if (validFiles.length !== (input.files?.length || 0)) {
                    replaceFileInputFiles(input, validFiles);
                }
                if (rejectedType) showToast('Proof images must be JPG or PNG files.', 'error');
                input.disabled = true;
                try {
                    const optimizedFiles = [];
                    for (let index = 0; index < validFiles.length; index += 1) {
                        updateProofUploadStatus(input, {
                            progress: validFiles.length ? ((index + 1) / validFiles.length) * 100 : 0,
                            label: `Optimizing image ${index + 1} of ${validFiles.length}…`,
                        });
                        optimizedFiles.push(await compressProofImage(validFiles[index]));
                    }
                    replaceFileInputFiles(input, optimizedFiles);
                } finally {
                    input.disabled = false;
                }
                renderProofImagePreview(input);
                if (!updateProofUploadStatus(input)) {
                    showToast('The selected proof images exceed 25 MB. Remove or replace images before submitting.', 'error');
                }
            });
        });
    }

    function renderExistingProofImages(report, type) {
        const images = Array.isArray(report.proof_images) ? report.proof_images : [];
        if (images.length === 0) {
            return '<div class="proof-image-existing" data-existing-proof-images><p class="table-muted" data-proof-images-empty>No proof images uploaded yet.</p></div>';
        }

        return `
            <div class="proof-image-existing" data-existing-proof-images>
                <div class="proof-image-grid">
                    ${images.map((image) => `
                        <figure class="proof-image-card" draggable="true" data-existing-proof-image="${escapeHtml(image.id)}">
                            <span class="proof-image-drag" title="Drag to change document order" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>
                            <a href="${escapeHtml(image.url || '#')}" target="_blank" rel="noopener">
                                <img src="${escapeHtml(image.url || '')}" alt="${escapeHtml(image.name || 'Proof image')}">
                            </a>
                            <figcaption title="${escapeHtml(image.name || 'Proof image')}">${escapeHtml(image.name || 'Proof image')}</figcaption>
                            <button type="button" class="proof-image-remove proof-image-remove--stored" data-delete-proof-image data-report-type="${escapeHtml(type)}" data-report-id="${escapeHtml(report.id)}" data-image-id="${escapeHtml(image.id)}" aria-label="Delete ${escapeHtml(image.name || 'proof image')}"><i class="fa-solid fa-trash"></i></button>
                        </figure>
                    `).join('')}
                </div>
            </div>
        `;
    }

    async function deleteExistingProofImage(button) {
        const reportType = button.dataset.reportType;
        const reportId = button.dataset.reportId;
        const imageId = button.dataset.imageId;
        if (!window.confirm('Delete this proof image permanently?')) return;

        button.disabled = true;
        try {
            const response = await fetch(buildProofImageDeleteRoute(reportType, reportId, imageId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Unable to delete the proof image.');
            }

            const report = reportStore[reportType]?.[reportId];
            if (report) {
                report.proof_images = (report.proof_images || []).filter((image) => String(image.id) !== String(imageId));
            }
            const container = button.closest('[data-existing-proof-images]');
            button.closest('[data-existing-proof-image]')?.remove();
            if (container && !container.querySelector('[data-existing-proof-image]')) {
                container.innerHTML = '<p class="table-muted" data-proof-images-empty>No proof images uploaded yet.</p>';
            }
            showToast(result.message || 'Proof image deleted successfully.', 'success');
        } catch (error) {
            button.disabled = false;
            showToast(error.message || 'Unable to delete the proof image.', 'error');
        }
    }

    function bindProofImageDeletion(scope) {
        scope?.querySelectorAll('[data-delete-proof-image]').forEach((button) => {
            if (button.dataset.deleteBound === 'true') return;
            button.dataset.deleteBound = 'true';
            button.addEventListener('click', () => deleteExistingProofImage(button));
        });
    }

    async function persistExistingProofImageOrder(container, reportType, reportId) {
        const imageIds = Array.from(container.querySelectorAll('[data-existing-proof-image]'))
            .map((card) => Number(card.dataset.existingProofImage));
        const response = await fetch(buildProofImageOrderRoute(reportType, reportId), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ image_ids: imageIds }),
        });
        const result = await response.json();
        if (!response.ok || result.success === false) {
            throw new Error(result.message || 'Unable to save the proof-image order.');
        }

        const report = reportStore[reportType]?.[reportId];
        if (report) {
            const imagesById = new Map((report.proof_images || []).map((image) => [Number(image.id), image]));
            report.proof_images = imageIds.map((imageId) => imagesById.get(imageId)).filter(Boolean);
        }
        showToast(result.message || 'Proof-image order saved.', 'success');
    }

    function bindExistingProofImageOrdering(scope, reportType, reportId) {
        const container = scope?.querySelector('[data-existing-proof-images] .proof-image-grid');
        if (!container || container.dataset.orderBound === 'true') return;
        container.dataset.orderBound = 'true';
        let draggedCard = null;
        let originalIds = [];

        container.querySelectorAll('[data-existing-proof-image]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
                draggedCard = card;
                originalIds = Array.from(container.querySelectorAll('[data-existing-proof-image]'))
                    .map((item) => item.dataset.existingProofImage);
                card.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.existingProofImage);
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('is-dragging');
                draggedCard = null;
            });
            card.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            });
            card.addEventListener('drop', async (event) => {
                event.preventDefault();
                if (!draggedCard || draggedCard === card) return;
                const bounds = card.getBoundingClientRect();
                const placeAfter = event.clientX > bounds.left + (bounds.width / 2);
                container.insertBefore(draggedCard, placeAfter ? card.nextSibling : card);
                try {
                    await persistExistingProofImageOrder(container, reportType, reportId);
                } catch (error) {
                    const cards = new Map(Array.from(container.querySelectorAll('[data-existing-proof-image]'))
                        .map((item) => [item.dataset.existingProofImage, item]));
                    originalIds.forEach((imageId) => container.appendChild(cards.get(imageId)));
                    showToast(error.message || 'Unable to save the proof-image order.', 'error');
                }
            });
        });
    }

    function validateProofUploads(form) {
        let valid = true;
        form?.querySelectorAll('input[name="proof_images[]"]').forEach((input) => {
            if (!updateProofUploadStatus(input)) valid = false;
        });
        if (!valid) {
            form?.querySelector('input[name="proof_images[]"]:invalid')?.reportValidity();
        }
        return valid;
    }

    function setProofUploadProgress(form, progress, label) {
        form?.querySelectorAll('input[name="proof_images[]"]').forEach((input) => {
            if ((input.files?.length || 0) > 0) updateProofUploadStatus(input, { progress, label });
        });
    }

    function sendMultipartAttempt(endpoint, formData, options = {}) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-Upload-Request-ID', options.requestId);
            if (options.methodOverride) xhr.setRequestHeader('X-HTTP-Method-Override', options.methodOverride);
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable && typeof options.onProgress === 'function') {
                    options.onProgress((event.loaded / event.total) * 100);
                }
            });
            xhr.addEventListener('load', () => {
                let result = {};
                try {
                    result = xhr.responseText ? JSON.parse(xhr.responseText) : {};
                } catch (error) {
                    const uploadError = new Error(xhr.status === 413
                        ? 'The upload is larger than the server request limit.'
                        : 'The server returned an invalid upload response.');
                    uploadError.retryable = xhr.status >= 500 || xhr.status === 0;
                    reject(uploadError);
                    return;
                }
                if (xhr.status < 200 || xhr.status >= 300 || result.success === false) {
                    const firstValidationError = Object.values(result.errors || {}).flat()[0];
                    const uploadError = new Error(firstValidationError || result.message || 'Failed to upload the report.');
                    uploadError.retryable = xhr.status === 408 || xhr.status === 429 || xhr.status >= 500;
                    reject(uploadError);
                    return;
                }
                resolve(result);
            });
            xhr.addEventListener('error', () => {
                const uploadError = new Error('The upload could not be completed. Check your connection and try again.');
                uploadError.retryable = true;
                reject(uploadError);
            });
            xhr.addEventListener('abort', () => {
                const uploadError = new Error('The upload was interrupted and will be retried.');
                uploadError.retryable = true;
                reject(uploadError);
            });
            xhr.send(formData);
        });
    }

    async function sendMultipartRequest(endpoint, formData, options = {}) {
        options.requestId = options.requestId || (window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(16).slice(2)}`);
        const maxRetries = Number.isInteger(options.maxRetries) ? options.maxRetries : 2;
        let attempt = 0;
        while (true) {
            try {
                return await sendMultipartAttempt(endpoint, formData, options);
            } catch (error) {
                if (!error.retryable || attempt >= maxRetries) throw error;
                attempt += 1;
                options.onRetry?.(attempt, maxRetries);
                await new Promise((resolve) => window.setTimeout(resolve, 600 * attempt));
            }
        }
    }

    function showToast(message, type = 'success', timeout = 3200) {
        window.PyroNotify?.notify(message, { type, duration: timeout });
    }

    const mobilePickerMedia = window.matchMedia('(max-width: 767px)');
    let activeReportPicker = null;
    const mobilePickerListeners = [];
    let lastPickerControl = null;
    let lastPickerOpenedAt = 0;

    function isMobileReportPicker() {
        return mobilePickerMedia.matches;
    }

    function getControlLabel(control) {
        return control?.closest('.form-group')?.querySelector('.form-label')?.textContent?.trim()
            || control?.getAttribute('aria-label')
            || 'Choose value';
    }

    function ensurePickerId(control) {
        if (!control.id) {
            control.id = `report-control-${Math.random().toString(36).slice(2, 9)}`;
        }
        return control.id;
    }

    function emitControlChange(control) {
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function closeReportPicker() {
        activeReportPicker?.remove();
        activeReportPicker = null;
        document.body.classList.remove('report-picker-open');
    }

    function buildPickerShell(title, subtitle = '') {
        closeReportPicker();

        const sheet = document.createElement('div');
        sheet.className = 'report-control-picker';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.innerHTML = `
            <div class="report-control-picker__scrim" data-close-report-picker></div>
            <div class="report-control-picker__sheet">
                <div class="report-control-picker__handle"></div>
                <div class="report-control-picker__header">
                    <div>
                        <div class="report-control-picker__title">${escapeHtml(title)}</div>
                        ${subtitle ? `<div class="report-control-picker__subtitle">${escapeHtml(subtitle)}</div>` : ''}
                    </div>
                    <button type="button" class="report-control-picker__close" data-close-report-picker aria-label="Close picker">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="report-control-picker__body"></div>
            </div>
        `;

        sheet.addEventListener('click', (event) => {
            if (event.target.closest('[data-close-report-picker]')) {
                closeReportPicker();
            }
        });

        document.body.appendChild(sheet);
        document.body.classList.add('report-picker-open');
        activeReportPicker = sheet;
        return sheet.querySelector('.report-control-picker__body');
    }

    function openMobileSelectPicker(select) {
        if (!select || select.disabled) return;

        const targetId = ensurePickerId(select);
        const body = buildPickerShell(getControlLabel(select), 'Tap one option to apply.');
        const options = Array.from(select.options || []);
        activeReportPicker.dataset.targetId = targetId;

        const list = document.createElement('div');
        list.className = 'report-select-sheet-list';
        list.innerHTML = options.map((option, index) => {
            const isSelected = option.selected || String(option.value) === String(select.value);
            const isPlaceholder = !option.value;
            return `
                <button
                    type="button"
                    class="report-select-option ${isSelected ? 'is-selected' : ''} ${isPlaceholder ? 'is-placeholder' : ''}"
                    data-report-select-index="${index}"
                    ${option.disabled ? 'disabled' : ''}
                >
                    <span>${escapeHtml(option.textContent.trim() || 'Choose option')}</span>
                    ${isSelected ? '<i class="fa-solid fa-check"></i>' : ''}
                </button>
            `;
        }).join('');

        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-report-select-index]');
            if (!button || button.disabled) return;
            const option = options[Number(button.dataset.reportSelectIndex)];
            if (!option) return;
            select.value = option.value;
            emitControlChange(select);
            closeReportPicker();
        });

        body.appendChild(list);
    }

    function padNumber(value) {
        return String(value).padStart(2, '0');
    }

    function parseReportDateValue(value, includeTime) {
        if (value) {
            const parsed = includeTime ? new Date(value) : new Date(`${value}T12:00:00`);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed;
            }
        }
        const now = new Date();
        now.setSeconds(0, 0);
        return now;
    }

    function formatReportDateValue(date, includeTime) {
        const datePart = `${date.getFullYear()}-${padNumber(date.getMonth() + 1)}-${padNumber(date.getDate())}`;
        if (!includeTime) return datePart;
        return `${datePart}T${padNumber(date.getHours())}:${padNumber(date.getMinutes())}`;
    }

    function formatPickerPreview(date, includeTime) {
        const options = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            ...(includeTime ? { hour: 'numeric', minute: '2-digit' } : {}),
        };
        return date.toLocaleString(undefined, options);
    }

    function openMobileDatePicker(input) {
        if (!input || input.disabled || input.readOnly) return;

        const includeTime = input.type === 'datetime-local';
        const targetId = ensurePickerId(input);
        const state = {
            selected: parseReportDateValue(input.value, includeTime),
            viewYear: parseReportDateValue(input.value, includeTime).getFullYear(),
            viewMonth: parseReportDateValue(input.value, includeTime).getMonth(),
        };

        const body = buildPickerShell(getControlLabel(input), includeTime ? 'Choose date and time.' : 'Choose date.');
        activeReportPicker.dataset.targetId = targetId;

        function setSelected(date) {
            state.selected = new Date(date);
            state.selected.setSeconds(0, 0);
            state.viewYear = state.selected.getFullYear();
            state.viewMonth = state.selected.getMonth();
            render();
        }

        function shiftTime(minutes) {
            const next = new Date(state.selected);
            next.setMinutes(next.getMinutes() + minutes);
            setSelected(next);
        }

        function render() {
            const firstOfMonth = new Date(state.viewYear, state.viewMonth, 1);
            const firstGridDate = new Date(state.viewYear, state.viewMonth, 1 - firstOfMonth.getDay());
            const today = new Date();
            const monthLabel = firstOfMonth.toLocaleString(undefined, { month: 'long', year: 'numeric' });
            const days = Array.from({ length: 42 }, (_, index) => {
                const day = new Date(firstGridDate);
                day.setDate(firstGridDate.getDate() + index);
                const sameMonth = day.getMonth() === state.viewMonth;
                const selected = day.toDateString() === state.selected.toDateString();
                const isToday = day.toDateString() === today.toDateString();
                return `
                    <button
                        type="button"
                        class="report-date-day ${sameMonth ? '' : 'is-muted'} ${selected ? 'is-selected' : ''} ${isToday ? 'is-today' : ''}"
                        data-report-date="${formatReportDateValue(day, false)}"
                    >
                        ${day.getDate()}
                    </button>
                `;
            }).join('');

            body.innerHTML = `
                <div class="report-date-preview">${escapeHtml(formatPickerPreview(state.selected, includeTime))}</div>
                <div class="report-date-header">
                    <button type="button" data-report-month="-1" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button>
                    <strong>${escapeHtml(monthLabel)}</strong>
                    <button type="button" data-report-month="1" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="report-date-weekdays">
                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>
                <div class="report-date-grid">${days}</div>
                ${includeTime ? `
                    <div class="report-time-row">
                        <div class="report-time-display">${padNumber(state.selected.getHours())}:${padNumber(state.selected.getMinutes())}</div>
                        <button type="button" data-report-time="-60"><i class="fa-solid fa-minus"></i> Hour</button>
                        <button type="button" data-report-time="60">Hour <i class="fa-solid fa-plus"></i></button>
                        <button type="button" data-report-time="-5"><i class="fa-solid fa-minus"></i> 5m</button>
                        <button type="button" data-report-time="5">5m <i class="fa-solid fa-plus"></i></button>
                    </div>
                ` : ''}
                <div class="report-picker-actions">
                    <button type="button" class="btn btn-outline" data-report-date-clear>Clear</button>
                    <button type="button" class="btn btn-outline" data-report-date-today>Today</button>
                    <button type="button" class="btn btn-primary" data-report-date-apply>Apply</button>
                </div>
            `;
        }

        body.addEventListener('click', (event) => {
            const monthButton = event.target.closest('[data-report-month]');
            if (monthButton) {
                state.viewMonth += Number(monthButton.dataset.reportMonth);
                if (state.viewMonth < 0) {
                    state.viewMonth = 11;
                    state.viewYear -= 1;
                }
                if (state.viewMonth > 11) {
                    state.viewMonth = 0;
                    state.viewYear += 1;
                }
                render();
                return;
            }

            const dayButton = event.target.closest('[data-report-date]');
            if (dayButton) {
                const [year, month, day] = dayButton.dataset.reportDate.split('-').map(Number);
                const next = new Date(state.selected);
                next.setFullYear(year, month - 1, day);
                setSelected(next);
                return;
            }

            const timeButton = event.target.closest('[data-report-time]');
            if (timeButton) {
                shiftTime(Number(timeButton.dataset.reportTime));
                return;
            }

            if (event.target.closest('[data-report-date-clear]')) {
                input.value = '';
                emitControlChange(input);
                closeReportPicker();
                return;
            }

            if (event.target.closest('[data-report-date-today]')) {
                setSelected(new Date());
                return;
            }

            if (event.target.closest('[data-report-date-apply]')) {
                input.value = formatReportDateValue(state.selected, includeTime);
                emitControlChange(input);
                closeReportPicker();
            }
        });

        render();
    }

    function bindMobileReportPickers() {
        if (mobilePickerListeners.length) return;

        const getMobilePickerControl = (target) => {
            if (!isMobileReportPicker() || target?.closest?.('.report-control-picker')) {
                return null;
            }

            return target?.closest?.('.report-modal.active select.form-select, .report-modal.active input[type="date"].form-input, .report-modal.active input[type="datetime-local"].form-input') || null;
        };

        const openPickerForControl = (control, event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
            control.blur();
            lastPickerControl = control;
            lastPickerOpenedAt = Date.now();

            if (control.matches('select')) {
                openMobileSelectPicker(control);
                return;
            }

            openMobileDatePicker(control);
        };

        const interceptPickerPointerDown = (event) => {
            if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) {
                return;
            }

            const control = getMobilePickerControl(event.target);
            if (!control) return;
            openPickerForControl(control, event);
        };

        const suppressNativeControlClick = (event) => {
            const control = getMobilePickerControl(event.target);
            if (!control) return;

            // The click following a handled touch must not reopen the native picker.
            if (control === lastPickerControl && (Date.now() - lastPickerOpenedAt) < 900) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        };

        const interceptPickerKeyboard = (event) => {
            if (!['Enter', ' '].includes(event.key)) return;
            const control = getMobilePickerControl(event.target);
            if (!control) return;
            openPickerForControl(control, event);
        };

        document.addEventListener('pointerdown', interceptPickerPointerDown, true);
        document.addEventListener('click', suppressNativeControlClick, true);
        document.addEventListener('keydown', interceptPickerKeyboard, true);

        mobilePickerListeners.push(
            ['pointerdown', interceptPickerPointerDown],
            ['click', suppressNativeControlClick],
            ['keydown', interceptPickerKeyboard]
        );

        // One touch interception prevents the native select and app sheet from competing.
    }

    function unbindMobileReportPickers() {
        mobilePickerListeners.splice(0).forEach(([eventName, handler]) => {
            document.removeEventListener(eventName, handler, true);
        });
    }

    function buildLoadingCard(message) {
        return `
            <div class="section-shell-card station-loading-card">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;
    }

    function updateModalOpenState() {
        const hasOpenModal = Boolean(document.querySelector('.report-modal.active'));
        document.body.classList.toggle('modal-open', hasOpenModal);
        document.body.classList.toggle('report-modal-open', hasOpenModal);
    }

    function setReportModalState(modal, state) {
        if (!modal) return;
        modal.dataset.reportState = state;
    }

    function resetReportModalScroll(modal) {
        if (!modal) return;

        window.requestAnimationFrame(() => {
            const panel = modal.querySelector('.report-modal-panel');
            const body = modal.querySelector('.report-modal-body');

            if (panel) {
                panel.scrollTop = 0;
            }

            if (body) {
                body.scrollTop = 0;
            }
        });
    }

    function setModalLoading(type, message = 'Preparing report form...') {
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
            if (control.matches('[data-close-report-modal]')) return;
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

    function setSummaryMetric(key, value, context = '') {
        const valueElement = root.querySelector(`[data-station-summary-value="${key}"]`);
        const contextElement = root.querySelector(`[data-station-summary-context="${key}"]`);

        if (valueElement) {
            valueElement.textContent = String(value ?? '--');
        }

        if (contextElement && context) {
            contextElement.textContent = context;
        }
    }

    function syncOverviewSummary() {
        const photoCard = overviewSection?.querySelector('[data-station-photo-count]');
        if (!photoCard) return;

        const photoCount = Number(photoCard.dataset.stationPhotoCount || 0);
        setSummaryMetric('photos', photoCount, photoCount === 1 ? '1 photo available' : `${photoCount} photos available`);
    }

    function syncPersonnelSummary() {
        const roster = personnelSection?.querySelector('[data-station-officers-roster]');
        if (!roster) return;

        const available = Number(roster.dataset.officersAvailable || 0);
        const total = Number(roster.dataset.officersTotal || 0);
        setSummaryMetric('officers', available, `${total} rostered officers`);
    }

    function applyRosterSearch(query = rosterSearchQuery) {
        const search = personnelSection?.querySelector('[data-station-roster-search]');
        const cards = Array.from(personnelSection?.querySelectorAll('.station-person-card') || []);
        const empty = personnelSection?.querySelector('[data-station-roster-empty]');
        const count = personnelSection?.querySelector('[data-station-roster-search-count]');

        rosterSearchQuery = String(query || '').trim().toLocaleLowerCase();
        if (search && search.value !== query) search.value = query;

        let visible = 0;
        cards.forEach((card) => {
            const matches = !rosterSearchQuery || String(card.dataset.rosterSearchText || '').includes(rosterSearchQuery);
            card.hidden = !matches;
            if (matches) visible += 1;
        });

        if (count) count.textContent = rosterSearchQuery ? `${visible} of ${cards.length} shown` : `${cards.length} shown`;
        if (empty) empty.hidden = visible > 0;
    }

    function syncEquipmentSummary() {
        const equipmentSummary = equipmentSection?.querySelector('[data-equipment-summary]');
        if (!equipmentSummary) return;

        const ready = Number(equipmentSummary.dataset.equipmentReady || 0);
        const lines = Number(equipmentSummary.dataset.equipmentLines || 0);
        setSummaryMetric('equipment', ready, `${lines} equipment records`);
    }

    function applyMaintenanceFilter(filter = maintenanceFilter) {
        const allowedFilters = ['all', 'attention', 'operational'];
        maintenanceFilter = allowedFilters.includes(filter) ? filter : 'all';

        const cards = Array.from(equipmentSection?.querySelectorAll('[data-maintenance-unit]') || []);
        const buttons = Array.from(equipmentSection?.querySelectorAll('[data-maintenance-filter]') || []);
        const empty = equipmentSection?.querySelector('[data-maintenance-filter-empty]');
        let visible = 0;

        buttons.forEach((button) => {
            const isActive = button.dataset.maintenanceFilter === maintenanceFilter;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        cards.forEach((card) => {
            const matches = maintenanceFilter === 'all' || card.dataset.maintenanceState === maintenanceFilter;
            card.hidden = !matches;
            if (matches) visible += 1;
        });

        if (empty) empty.hidden = visible > 0;
    }

    function syncReportsSummary(tab = currentReportTab) {
        const loadedCount = Object.keys(reportStore[tab] || {}).length;
        setSummaryMetric('reports', loadedCount, `${tab} reports loaded`);
    }

    async function loadOverview(force = false) {
        try {
            const query = force ? `${routes.overview}?refresh=1` : routes.overview;
            const result = await fetchStationSection('overview', query);
            overviewSection.innerHTML = result?.data?.html || '';
            syncOverviewSummary();
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station overview failed:', error);
            renderSectionError(overviewSection, 'Unable to load the station overview.', 'loadFirefighterStationOverview');
        }
    }

    async function loadPersonnel(force = false, preserveOnError = false) {
        try {
            const query = force ? `${routes.personnel}?refresh=1` : routes.personnel;
            const result = await fetchStationSection('personnel', query);
            personnelSection.innerHTML = result?.data?.html || '';
            syncPersonnelSummary();
            applyRosterSearch();
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station personnel failed:', error);
            if (!preserveOnError) {
                renderSectionError(personnelSection, 'Unable to load station personnel.', 'loadFirefighterStationPersonnel');
            }
        }
    }

    async function loadEquipment(force = false, preserveOnError = false) {
        try {
            const query = force ? `${routes.equipment}?refresh=1` : routes.equipment;
            const result = await fetchStationSection('equipment', query);
            equipmentSection.innerHTML = result?.data?.html || '';
            syncEquipmentSummary();
            applyMaintenanceFilter();
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Station equipment failed:', error);
            if (!preserveOnError) {
                renderSectionError(equipmentSection, 'Unable to load station equipment.', 'loadFirefighterStationEquipment');
            }
        }
    }

    async function refreshReadiness() {
        if (readinessRefreshInFlight || document.hidden || !document.body.contains(root)) return;

        readinessRefreshInFlight = true;
        try {
            await Promise.allSettled([loadPersonnel(true, true), loadEquipment(true, true)]);
            lastReadinessRefreshAt = Date.now();
        } finally {
            readinessRefreshInFlight = false;
        }
    }

    function handleReadinessVisibilityChange() {
        if (!document.hidden && Date.now() - lastReadinessRefreshAt >= READINESS_REFRESH_INTERVAL_MS) {
            refreshReadiness();
        }
    }

    function handlePersonnelSearch(event) {
        if (event.target.matches('[data-station-roster-search]')) {
            applyRosterSearch(event.target.value);
        }
    }

    async function loadReports(tab = 'spot', urlOverride = null) {
        try {
            currentReportTab = tab;
            document.querySelectorAll('.report-tab').forEach((tabButton) => {
                tabButton.classList.toggle('active', tabButton.dataset.reportTab === tab);
            });

            if (reportsContainer) {
                reportsContainer.innerHTML = buildLoadingCard(tab === 'aar' ? 'Loading After Activity Reports...' : `Loading ${tab} investigation reports...`);
            }

            const url = urlOverride || `${routes.reports}?tab=${encodeURIComponent(tab)}`;
            const result = await fetchStationSection('reports', url);
            reportsContainer.innerHTML = result?.data?.html || '';
            reportStore[tab] = result?.data?.store || {};

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('report_tab', tab);
            window.history.replaceState(window.history.state || {}, '', currentUrl);
            syncReportsSummary(tab);
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
            || referenceCache.aar?.officers
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

        incidentSelect.innerHTML = `<option value="">Select incident</option>${createOptions(data.incidents || [], '', (item) => `data-updated-at="${escapeHtml(item.updated_at || '')}" data-occurred-at="${escapeHtml(item.occurred_at || '')}" data-occurred-at-verified="${item.occurred_at_verified ? '1' : '0'}" data-location="${escapeHtml(item.label || '')}"` )}`;
        officerSelects.forEach((select) => populateOfficerSelect(select));

        if (incidentSelect.dataset.occurrenceBound !== 'true') {
            incidentSelect.dataset.occurrenceBound = 'true';
            incidentSelect.addEventListener('change', () => {
                const selected = incidentSelect.options[incidentSelect.selectedIndex];
                const occurredAtInput = spotForm?.querySelector('input[name="fire_started"]');
                if (occurredAtInput && selected?.dataset?.occurredAt) {
                    occurredAtInput.value = selected.dataset.occurredAt.slice(0, 16);
                }
            });
        }
    }

    function populateProgressModal(data) {
        const reportSelect = progressForm?.querySelector('select[name="spot_investigation_id"]');
        const stageSelect = progressForm?.querySelector('select[name="report_stage"]');
        const fireOriginSelect = progressForm?.querySelector('select[name="fire_origin_id"]');
        const officerSelects = progressForm?.querySelectorAll('select[data-station-ref="officers"]');
        if (!reportSelect || !stageSelect || !fireOriginSelect || !officerSelects) return;

        const refreshSourceReports = () => {
            const sourceData = referenceCache.progress || data;
            const isSecond = stageSelect.value === 'second';
            const reports = isSecond ? (sourceData.first_progress_reports || []) : (sourceData.spot_reports || []);
            const emptyText = isSecond ? 'No 1st Progress Report is ready' : 'Select spot report';
            const sourceHelp = progressForm.querySelector('[data-progress-source-help]');
            const parentInput = progressForm.querySelector('input[name="parent_progress_investigation_id"]');

            reportSelect.innerHTML = `<option value="">${emptyText}</option>${createOptions(reports, '', (item) => `data-parent-id="${escapeHtml(item.parent_id || '')}"`)}`;
            if (sourceHelp) {
                sourceHelp.textContent = isSecond
                    ? 'Only 1st Progress Reports without a 2nd report are shown.'
                    : 'Select the spot report this progress report continues.';
            }
            if (parentInput) parentInput.value = '';
        };

        if (stageSelect.dataset.sequenceBound !== 'true') {
            stageSelect.dataset.sequenceBound = 'true';
            stageSelect.addEventListener('change', refreshSourceReports);
            reportSelect.addEventListener('change', () => {
                const parentInput = progressForm.querySelector('input[name="parent_progress_investigation_id"]');
                const selected = reportSelect.options[reportSelect.selectedIndex];
                if (parentInput) parentInput.value = selected?.dataset?.parentId || '';
            });
        }

        refreshSourceReports();
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

    function populateAarModal(data) {
        const activitySelect = aarForm?.querySelector('select[name="event_activity_id"]');
        const officerSelects = aarForm?.querySelectorAll('select[data-station-ref="officers"]');
        if (!activitySelect || !officerSelects) return;

        activitySelect.innerHTML = `<option value="">Unscheduled operation or activity</option>${createOptions(data.activities || [], '', (item) => `data-title="${escapeHtml(item.title || '')}" data-type="${escapeHtml(item.type || '')}" data-date="${escapeHtml(item.date || '')}" data-location="${escapeHtml(item.location || '')}"`)}`;
        officerSelects.forEach((select) => populateOfficerSelect(select));

        if (activitySelect.dataset.aarBound !== 'true') {
            activitySelect.dataset.aarBound = 'true';
            activitySelect.addEventListener('change', () => {
                const option = activitySelect.options[activitySelect.selectedIndex];
                if (!option?.value) return;
                ['title', 'type', 'date', 'location'].forEach((field) => {
                    const inputName = field === 'title' ? 'activity_title' : field === 'type' ? 'activity_type' : field === 'date' ? 'activity_date' : 'activity_location';
                    const input = aarForm.querySelector(`[name="${inputName}"]`);
                    if (input) input.value = option.dataset[field] || '';
                });
            });
        }
    }

    async function openReportModal(type) {
        const modal = document.getElementById(`${type}ReportModal`);
        const form = type === 'spot' ? spotForm : type === 'progress' ? progressForm : type === 'final' ? finalForm : aarForm;

        modal?.classList.add('active');
        setReportModalState(modal, 'loading');
        updateModalOpenState();
        resetReportModalScroll(modal);
        clearModalLoading(type);
        modal?.querySelectorAll('.station-section-error').forEach((errorBox) => errorBox.remove());
        const loading = setModalLoading(type);
        resetReportModalScroll(modal);
        setFormControlsDisabled(form, true);

        try {
            if (type === 'spot') populateSpotModal(await ensureModalData('spot'));
            if (type === 'progress') populateProgressModal(await ensureModalData('progress'));
            if (type === 'final') populateFinalModal(await ensureModalData('final'));
            if (type === 'aar') populateAarModal(await ensureModalData('aar'));
            setReportModalState(modal, 'ready');
            resetReportModalScroll(modal);
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error(`Unable to open ${type} modal:`, error);
            setReportModalState(modal, 'error');
            loading?.remove();
            const body = modal?.querySelector('.report-modal-body');
            body?.prepend(Object.assign(document.createElement('div'), {
                className: 'station-section-error',
                innerHTML: `<div>We couldn't load the report form.</div><button type="button" class="btn btn-primary" data-open-report-modal="${escapeHtml(type)}">Try again</button>`,
            }));
            showToast(`Unable to load ${type} report choices right now.`, 'error');
            resetReportModalScroll(modal);
        } finally {
            clearModalLoading(type);
            if (modal?.dataset.reportState === 'ready') {
                setFormControlsDisabled(form, false);
            }
        }
    }

    function closeReportModal(type) {
        const modal = document.getElementById(`${type}ReportModal`);
        modal?.classList.remove('active');
        setReportModalState(modal, 'idle');
        clearModalLoading(type);
        modal?.querySelector('.station-section-error')?.remove();
        updateModalOpenState();
    }

    function openManageModal(title) {
        manageReportModalTitle.textContent = title;
        manageReportModal.classList.add('active');
        updateModalOpenState();
        resetReportModalScroll(manageReportModal);
    }

    function closeManageModal() {
        manageReportModal.classList.remove('active');
        clearProofImagePreview(manageReportModalBody);
        manageReportModalBody.innerHTML = '';
        manageReportModalFooter.innerHTML = '';
        updateModalOpenState();
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

        if (!['spot', 'progress', 'final', 'aar'].includes(reportType)) {
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
            }
            if (!report.has_final && !report.has_second) {
                actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
            }
        }

        if (type === 'final') {
            actions.push(`<a class="table-action edit" href="${buildFirRoute(report.id)}">Word</a>`);
            actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
            actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
        }

        if (type === 'aar') {
            actions.push(`<a class="table-action edit" href="${buildAarRoute(report.id)}">Word</a>`);
            actions.push(`<a class="table-action edit" href="${buildAarPdfRoute(report.id)}">PDF</a>`);
            if (report.review_status !== 'approved') {
                actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="aar" data-report-id="${report.id}">Edit</button>`);
                actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="aar" data-report-id="${report.id}">Delete</button>`);
            }
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
                <td data-label="Incident"><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">Spot report</div></td>
                <td data-label="Investigator">${createCurrentOfficerTrigger('Spot report investigator')}</td>
                <td data-label="Fire timeline">${escapeHtml(formatDateTime(report.fire_started))}<br>${escapeHtml(formatDateTime(report.fire_out))}</td>
                <td data-label="Damage">${escapeHtml(formatMoney(report.estimated_damage))}</td>
                <td data-label="Occupancy">${escapeHtml(String(report.occupancy || 'n/a').replace(/^./, (match) => match.toUpperCase()))}</td>
                <td data-label="Actions">${createActionButtons('spot', report)}</td>
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
                <td data-label="Incident"><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">${escapeHtml(report.report_stage_label || 'Progress Report')}</div></td>
                <td data-label="Fire origin">${escapeHtml(report.fire_origin_name || 'N/A')}</td>
                <td data-label="Cause">${escapeHtml(report.fire_cause || 'N/A')}</td>
                <td data-label="Responsible party">${escapeHtml(report.responsible_arson || 'Not specified')}</td>
                <td data-label="Investigator">${createCurrentOfficerTrigger('Progress report investigator')}</td>
                <td data-label="Actions">${createActionButtons('progress', report)}</td>
            </tr>
        `);

        incrementCounter('progressReportsCount');
    }

    function appendFinalReportRow(report) {
        const tbody = document.getElementById('finalReportsTableBody');
        if (!tbody || !report?.id) return;
        const emptyState = tbody.querySelector('td[colspan="6"]')?.closest('tr');
        if (emptyState) emptyState.remove();

        tbody.insertAdjacentHTML('afterbegin', `
            <tr id="final-report-${report.id}">
                <td data-label="Incident"><div class="table-title">${escapeHtml(report.incident_location || 'Fire incident')}</div><div class="table-muted">Final report</div></td>
                <td data-label="Address">${escapeHtml(report.exact_address || 'N/A')}</td>
                <td data-label="Total damage">${escapeHtml(formatMoney(report.total_damage))}</td>
                <td data-label="Investigator">${createCurrentOfficerTrigger('Final report investigator')}</td>
                <td data-label="Created">${escapeHtml(formatDateTime(report.created_at))}</td>
                <td data-label="Actions">${createActionButtons('final', report)}</td>
            </tr>
        `);

        incrementCounter('finalReportsCount');
    }

    function appendAarReportRow(report) {
        const tbody = document.getElementById('aarReportsTableBody');
        if (!tbody || !report?.id) return;
        tbody.querySelector('td[colspan="6"]')?.closest('tr')?.remove();
        tbody.insertAdjacentHTML('afterbegin', `
            <tr id="aar-report-${report.id}">
                <td data-label="Activity"><div class="table-title">${escapeHtml(report.activity_title || 'BFP activity')}</div><div class="table-muted">${escapeHtml(report.activity_location || 'Location not recorded')}</div></td>
                <td data-label="Date">${escapeHtml(report.activity_date || 'N/A')}</td>
                <td data-label="Type">${escapeHtml(report.activity_type || 'Activity')}</td>
                <td data-label="Prepared by">${createCurrentOfficerTrigger('AAR prepared by')}</td>
                <td data-label="Status"><span class="table-muted">${escapeHtml(String(report.review_status || 'submitted').replaceAll('_', ' '))}</span></td>
                <td data-label="Actions">${createActionButtons('aar', report)}</td>
            </tr>
        `);
        incrementCounter('aarReportsCount');
    }

    function replaceRow(type, report) {
        const row = document.getElementById(`${type}-report-${report.id}`);
        row?.remove();
        if (type === 'spot') appendSpotReportRow(report);
        if (type === 'progress') appendProgressReportRow(report);
        if (type === 'final') appendFinalReportRow(report);
        if (type === 'aar') appendAarReportRow(report);
    }

    function renderOfficerSelectOptions(selectedValue = '') {
        const officers = referenceCache.spot?.officers || referenceCache.progress?.officers || referenceCache.final?.officers || referenceCache.aar?.officers || [];
        return `<option value="">Select officer</option>${createOptions(officers, selectedValue)}`;
    }

    function renderFireOriginSelectOptions(selectedValue = '', selectedLabel = '') {
        const fireOrigins = referenceCache.progress?.fire_origins || [];
        const matched = fireOrigins.some((origin) => String(origin.id) === String(selectedValue));
        const selectedOther = !matched && selectedLabel ? 'selected' : '';
        return `<option value="">Select fire origin</option>${createOptions(fireOrigins, selectedValue)}<option value="other" ${selectedOther}>Other</option>`;
    }

    function renderViewDetails(type, report) {
        if (type === 'aar') {
            return `
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Report Type</div><div class="detail-value">After Activity Report</div></div>
                    <div class="detail-item"><div class="detail-label">Source</div><div class="detail-value">${escapeHtml(report.source_label || 'Unscheduled operation')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Activity</div><div class="detail-value">${escapeHtml(report.activity_title || 'BFP activity')}</div></div>
                    <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">${escapeHtml(report.activity_date || 'N/A')}</div></div>
                    <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.activity_location || 'Not recorded')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Participants</div><div class="detail-value">${escapeHtml((report.participants || []).join('\n') || 'None recorded')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Deployed Resources</div><div class="detail-value">${escapeHtml((report.deployed_resources || []).join('\n') || 'None recorded')}</div></div>
                    <div class="detail-item"><div class="detail-label">Review Status</div><div class="detail-value">${escapeHtml(String(report.review_status || 'submitted').replaceAll('_', ' '))}</div></div>
                    <div class="detail-item full"><div class="detail-label">Background</div><div class="detail-value">${escapeHtml(report.background || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Significance</div><div class="detail-value">${escapeHtml(report.significance || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Activity Details</div><div class="detail-value">${escapeHtml(report.activity_details || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Issues and Challenges</div><div class="detail-value">${escapeHtml(report.issues_challenges || 'None')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Discussion and Assessment</div><div class="detail-value">${escapeHtml(report.discussion_assessment || 'Not provided')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Recommendations</div><div class="detail-value">${escapeHtml(report.recommendations || 'None')}</div></div>
                </div>`;
        }

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
                    <div class="detail-item"><div class="detail-label">Report Type</div><div class="detail-value">${escapeHtml(report.report_stage_label || 'Progress Report')}</div></div>
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
        if (type === 'aar') {
            return `
                <form id="manageReportEditForm" class="form-grid">
                    <input type="hidden" name="event_activity_id" value="${escapeHtml(report.event_activity_id || '')}">
                    <div class="form-group full"><label class="form-label">Activity Source</label><div class="form-auto-value">${escapeHtml(report.source_label || 'Unscheduled operation')}</div></div>
                    <div class="form-group full"><label class="form-label">Activity Title</label><input class="form-input" name="activity_title" value="${escapeHtml(report.activity_title || '')}" required></div>
                    <div class="form-group"><label class="form-label">Activity Type</label><input class="form-input" name="activity_type" value="${escapeHtml(report.activity_type || '')}"></div>
                    <div class="form-group"><label class="form-label">Activity Date</label><input class="form-input" type="date" name="activity_date" value="${escapeHtml(report.activity_date || '')}" required></div>
                    <div class="form-group full"><label class="form-label">Location</label><input class="form-input" name="activity_location" value="${escapeHtml(report.activity_location || '')}"></div>
                    <div class="form-group full"><label class="form-label">Participants</label><textarea class="form-textarea" name="participants_text">${escapeHtml(report.participants_text || (report.participants || []).join('\n'))}</textarea></div>
                    <div class="form-group full"><label class="form-label">Deployed Resources</label><textarea class="form-textarea" name="deployed_resources_text">${escapeHtml(report.deployed_resources_text || (report.deployed_resources || []).join('\n'))}</textarea></div>
                    <div class="form-group"><label class="form-label">Memorandum For</label><input class="form-input" name="memorandum_for" value="${escapeHtml(report.memorandum_for || '')}"></div>
                    <div class="form-group"><label class="form-label">Report Completed</label><input class="form-input" type="datetime-local" name="report_completed_at" value="${escapeHtml((report.report_completed_at || '').slice(0, 16))}"></div>
                    <div class="form-group full"><label class="form-label">References</label><textarea class="form-textarea" name="references">${escapeHtml(report.references || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Background</label><textarea class="form-textarea" name="background">${escapeHtml(report.background || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Significance</label><textarea class="form-textarea" name="significance">${escapeHtml(report.significance || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Activity Details</label><textarea class="form-textarea" name="activity_details" required>${escapeHtml(report.activity_details || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Issues and Challenges</label><textarea class="form-textarea" name="issues_challenges">${escapeHtml(report.issues_challenges || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Discussion and Assessment</label><textarea class="form-textarea" name="discussion_assessment">${escapeHtml(report.discussion_assessment || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Recommendations</label><textarea class="form-textarea" name="recommendations">${escapeHtml(report.recommendations || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Existing Pictures</label>${renderExistingProofImages(report, type)}</div>
                    <div class="form-group full"><label class="form-label">Add Pictures</label><input class="form-input" type="file" name="proof_images[]" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png"></div>
                    <div class="form-group full"><label class="form-label">Approved By</label><select class="form-select" name="approved_by_station_officer_id">${renderOfficerSelectOptions(report.approved_by_station_officer_id || '')}</select></div>
                    <div class="form-group full"><label class="form-label">Electronic Signature</label><input class="form-input" name="signature_name" value="${escapeHtml(currentOfficerName)}" required><label><input type="checkbox" name="signature_consent" value="1" required> I certify and electronically sign this revision.</label></div>
                </form>`;
        }

        if (type === 'spot') {
            return `
                <form id="manageReportEditForm" class="form-grid">
                    <div class="form-group"><label class="form-label">Dead</label><input class="form-input" type="number" name="dead" min="0" step="1" value="${escapeHtml(report.dead || 0)}"></div>
                    <div class="form-group"><label class="form-label">Injured</label><input class="form-input" type="number" name="injured" min="0" step="1" value="${escapeHtml(report.injured || 0)}"></div>
                    <div class="form-group full"><label class="form-label">Owner</label><input class="form-input" type="text" name="owner" value="${escapeHtml(report.owner || '')}"></div>
                    <div class="form-group"><label class="form-label">Report Completed</label><div class="form-auto-value"><i class="fa-solid fa-clock"></i><span>${escapeHtml(formatDateTime(report.report_completed_at))} · recorded when submitted</span></div></div>
                    <div class="form-group"><label class="form-label">Memorandum For</label><input class="form-input" type="text" name="memorandum_for" value="${escapeHtml(report.memorandum_for || '')}"></div>
                    <div class="form-group"><label class="form-label">Fire Occurred At</label><input class="form-input" type="datetime-local" name="fire_started" value="${escapeHtml((report.fire_started || '').slice(0, 16))}"><small class="table-muted">Correcting this updates the verified incident occurrence time and its audit history.</small></div>
                    <div class="form-group"><label class="form-label">Fire Out</label><input class="form-input" type="datetime-local" name="fire_out" value="${escapeHtml((report.fire_out || '').slice(0, 16))}"></div>
                    <div class="form-group full"><label class="form-label">Place of Occurrence</label><input class="form-input" type="text" name="place_of_occurrence" value="${escapeHtml(report.place_of_occurrence || '')}"></div>
                    <div class="form-group"><label class="form-label">Estimated Damage</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_damage" value="${escapeHtml(report.estimated_damage || 0)}"></div>
                    <div class="form-group"><label class="form-label">Estimated Area</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_area" value="${escapeHtml(report.estimated_area || 0)}"></div>
                    <div class="form-group"><label class="form-label">Affected Establishments</label><input class="form-input" type="number" min="0" step="1" name="affected_establishments_count" value="${escapeHtml(report.affected_establishments_count || 0)}"></div>
                    <div class="form-group"><label class="form-label">Highest Alarm</label><select class="form-select" name="highest_alarm_level">${['', '1st Alarm', '2nd Alarm', '3rd Alarm', '4th Alarm', '5th Alarm'].map((level) => `<option value="${level}" ${String(report.highest_alarm_level || '') === level ? 'selected' : ''}>${level || 'Select alarm level'}</option>`).join('')}</select></div>
                    <div class="form-group full"><label class="form-label">Narrative</label><textarea class="form-textarea" name="details_of_investigation">${escapeHtml(report.details_of_investigation || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Disposition</label><textarea class="form-textarea" name="disposition">${escapeHtml(report.disposition || '')}</textarea></div>
                    <div class="form-group full"><label class="form-label">Existing Proof Images</label>${renderExistingProofImages(report, type)}</div>
                    <div class="form-group full"><label class="form-label">Add Proof Images</label><input class="form-input" type="file" name="proof_images[]" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png"><small class="table-muted">New images are appended. Combined selection must not exceed 25 MB.</small></div>
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
                    <div class="form-group full"><label class="form-label">Existing Proof Images</label>${renderExistingProofImages(report, type)}</div>
                    <div class="form-group full"><label class="form-label">Add Proof Images</label><input class="form-input" type="file" name="proof_images[]" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png"><small class="table-muted">New images are appended. Combined selection must not exceed 25 MB.</small></div>
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
                <div class="form-group full"><label class="form-label">Existing Proof Images</label>${renderExistingProofImages(report, type)}</div>
                <div class="form-group full"><label class="form-label">Add Proof Images</label><input class="form-input" type="file" name="proof_images[]" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png"><small class="table-muted">New images are appended. Combined selection must not exceed 25 MB.</small></div>
                <div class="form-group"><label class="form-label">Noted By</label><select class="form-select" name="noted_by_station_officer_id">${renderOfficerSelectOptions(report.noted_by_station_officer_id || '')}</select></div>
                <div class="form-group"><label class="form-label">Approved By</label><select class="form-select" name="approved_by_station_officer_id">${renderOfficerSelectOptions(report.approved_by_station_officer_id || '')}</select></div>
            </form>
        `;
    }

    async function openViewModal(type, report) {
        openManageModal(type === 'aar' ? 'After Activity Report' : `${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = renderViewDetails(type, report);
        const downloadButton = type === 'aar'
            ? `<a class="btn btn-outline" href="${buildAarRoute(report.id)}">Download Word</a>`
            : type === 'spot'
            ? `<a class="btn btn-outline" href="${buildSirRoute(report.id)}">Download Word</a>`
            : type === 'progress'
                ? `<a class="btn btn-outline" href="${buildPirRoute(report.id)}">Download Word</a>`
                : `<a class="btn btn-outline" href="${buildFirRoute(report.id)}">Download Word</a>`;
        manageReportModalFooter.innerHTML = `${downloadButton}<button type="button" class="btn btn-outline" data-close-manage-modal>Close</button>`;
        resetReportModalScroll(manageReportModal);
    }

    async function openEditModal(type, report) {
        if (type === 'progress') {
            await ensureModalData('progress');
        } else if (type === 'final') {
            await ensureModalData('final');
        } else if (type === 'aar') {
            await ensureModalData('aar');
        } else {
            await ensureModalData('spot');
        }

        openManageModal(type === 'aar' ? 'Edit After Activity Report' : `Edit ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = renderEditForm(type, report);
        bindProofImagePreviews(manageReportModalBody);
        bindProofImageDeletion(manageReportModalBody);
        bindExistingProofImageOrdering(manageReportModalBody, type, report.id);
        manageReportModalFooter.innerHTML = `
            <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
            <button type="button" class="btn btn-primary" id="saveManagedReportButton"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        `;
        resetReportModalScroll(manageReportModal);

        document.getElementById('saveManagedReportButton')?.addEventListener('click', () => saveManagedReport(type, report.id));
    }

    async function saveManagedReport(type, reportId) {
        const form = document.getElementById('manageReportEditForm');
        if (!form) return;
        if (!validateProofUploads(form)) {
            showToast('Proof images exceed the 25 MB threshold. Remove or replace images before saving.', 'error');
            return;
        }
        const submitButton = document.getElementById('saveManagedReportButton');
        const originalHtml = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const formData = new FormData(form);
            const updateEndpoint = type === 'aar' ? buildAarUpdateRoute(reportId) : `${routes.reportManageBase}/${type}-investigation/${reportId}`;
            const result = await sendMultipartRequest(updateEndpoint, formData, {
                methodOverride: 'PUT',
                onProgress: (progress) => setProofUploadProgress(form, progress, `Uploading proof images · ${Math.round(progress)}%`),
                onRetry: (attempt, maximum) => setProofUploadProgress(form, 0, `Connection interrupted · retrying ${attempt} of ${maximum}`),
            });
            setProofUploadProgress(form, 100, 'Proof images uploaded · 100%');

            const updated = { ...(reportStore[type][reportId] || {}) };
            updated.proof_images = result.data?.proof_images || updated.proof_images || [];
            Object.fromEntries(formData.entries());
            if (type === 'aar') {
                ['activity_title', 'activity_type', 'activity_date', 'activity_location', 'memorandum_for', 'report_completed_at', 'references', 'background', 'significance', 'activity_details', 'issues_challenges', 'discussion_assessment', 'recommendations', 'approved_by_station_officer_id'].forEach((field) => {
                    updated[field] = result.data?.[field] ?? formData.get(field);
                });
            } else if (type === 'spot') {
                updated.dead = formData.get('dead');
                updated.injured = formData.get('injured');
                updated.owner = formData.get('owner');
                updated.report_completed_at = result.data?.report_completed_at || updated.report_completed_at;
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
            form.querySelectorAll('input[name="proof_images[]"]').forEach((input) => updateProofUploadStatus(input));
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalHtml;
        }
    }

    async function deleteManagedReport(type, reportId) {
        const report = reportStore[type][reportId];
        if (!report) return;

        openManageModal(type === 'aar' ? 'Delete After Activity Report' : `Delete ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
        manageReportModalBody.innerHTML = `
            <div class="detail-item">
                <div class="detail-label">Delete Report</div>
                <div class="detail-value">This will permanently remove the ${type === 'aar' ? 'After Activity Report' : `${type} investigation report`} for ${escapeHtml(report.activity_title || report.incident_location || `record #${reportId}`)}.</div>
            </div>
        `;
        manageReportModalFooter.innerHTML = `
            <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteReportButton"><i class="fa-solid fa-trash"></i> Delete Report</button>
        `;
        resetReportModalScroll(manageReportModal);

        document.getElementById('confirmDeleteReportButton')?.addEventListener('click', async () => {
            try {
                const deleteEndpoint = type === 'aar' ? buildAarDeleteRoute(reportId) : `${routes.reportManageBase}/${type}-investigation/${reportId}`;
                const response = await fetch(deleteEndpoint, {
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
                if (type === 'progress') {
                    referenceCache.progress = null;
                    referenceCache.final = null;
                }
                if (type === 'final') {
                    referenceCache.final = null;
                }
                if (type === 'aar') {
                    referenceCache.aar = null;
                }
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
        if (!validateProofUploads(form)) {
            showToast('Proof images exceed the 25 MB threshold. Remove or replace images before submitting.', 'error');
            return null;
        }
        const submitButton = form.querySelector('button[type="submit"]');
        const modal = form.closest('.report-modal');
        const originalHtml = submitButton.innerHTML;
        setReportModalState(modal, 'submitting');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating report...';

        try {
            const formData = new FormData(form);
            const result = await sendMultipartRequest(endpoint, formData, {
                onProgress: (progress) => setProofUploadProgress(form, progress, `Uploading proof images · ${Math.round(progress)}%`),
                onRetry: (attempt, maximum) => setProofUploadProgress(form, 0, `Connection interrupted · retrying ${attempt} of ${maximum}`),
            });
            setProofUploadProgress(form, 100, 'Proof images uploaded · 100%');

            const mapped = mapSuccessData(formData, result.data || {});
            handleSuccess(mapped);
            form.reset();
            clearProofImagePreview(form);
            showToast('Investigation report submitted successfully.', 'success');
            return mapped;
        } catch (error) {
            console.error('Investigation report submission failed:', error);
            showToast(error.message || 'Failed to submit report.', 'error');
            form.querySelectorAll('input[name="proof_images[]"]').forEach((input) => updateProofUploadStatus(input));
            return null;
        } finally {
            setReportModalState(modal, 'ready');
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
        bindProofImagePreviews(document);

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
                report_completed_at: data.report_completed_at || new Date().toISOString(),
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
                report_stage: data.report_stage || formData.get('report_stage') || 'single',
                report_stage_label: data.report_stage_label || ({ first: '1st Progress Report', second: '2nd Progress Report' }[formData.get('report_stage')] || 'Progress Report'),
                parent_progress_investigation_id: data.parent_progress_investigation_id || formData.get('parent_progress_investigation_id'),
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
                has_second: false,
            }), appendProgressReportRow);

            if (mapped) {
                reportStore.progress[mapped.id] = mapped;
                if (reportStore.spot[selectedSpotId]) {
                    reportStore.spot[selectedSpotId].has_progress = true;
                    replaceRow('spot', reportStore.spot[selectedSpotId]);
                }
                const parentId = mapped.parent_progress_investigation_id;
                if (parentId && reportStore.progress[parentId]) {
                    reportStore.progress[parentId].has_second = true;
                }
                removeOption(progressSelect, selectedSpotId);
                referenceCache.progress = null;
                referenceCache.final = null;
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
                referenceCache.final = null;
                closeReportModal('final');
                switchReportTab('final');
            }
        });

        aarForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const activitySelect = aarForm.querySelector('select[name="event_activity_id"]');
            const mapped = await submitReportForm(aarForm, routes.aarCreate, (formData, data) => ({
                ...data,
                id: data.after_activity_report_id,
                type: 'aar',
                event_activity_id: data.event_activity_id || formData.get('event_activity_id'),
                source_label: (data.event_activity_id || formData.get('event_activity_id')) ? 'Scheduled activity' : 'Unscheduled operation',
                activity_title: data.activity_title || formData.get('activity_title'),
                activity_type: data.activity_type || formData.get('activity_type'),
                activity_date: String(data.activity_date || formData.get('activity_date') || '').slice(0, 10),
                activity_location: data.activity_location || formData.get('activity_location'),
                participants: data.participants || String(formData.get('participants_text') || '').split(/\r?\n/).map(value => value.trim()).filter(Boolean),
                deployed_resources: data.deployed_resources || String(formData.get('deployed_resources_text') || '').split(/\r?\n/).map(value => value.trim()).filter(Boolean),
                memorandum_for: data.memorandum_for || formData.get('memorandum_for'),
                report_completed_at: data.report_completed_at || formData.get('report_completed_at'),
                references: data.references || formData.get('references'),
                background: data.background || formData.get('background'),
                significance: data.significance || formData.get('significance'),
                activity_details: data.activity_details || formData.get('activity_details'),
                issues_challenges: data.issues_challenges || formData.get('issues_challenges'),
                discussion_assessment: data.discussion_assessment || formData.get('discussion_assessment'),
                recommendations: data.recommendations || formData.get('recommendations'),
                approved_by_station_officer_id: data.approved_by_station_officer_id || formData.get('approved_by_station_officer_id'),
                investigator_name: currentOfficerName,
                created_at: data.created_at || new Date().toISOString(),
                review_status: data.review_status || 'submitted',
            }), appendAarReportRow);

            if (mapped) {
                reportStore.aar[mapped.id] = mapped;
                removeOption(activitySelect, mapped.event_activity_id);
                referenceCache.aar = null;
                closeReportModal('aar');
                switchReportTab('aar');
            }
        });
    }

    const documentClickHandler = async (event) => {
        const maintenanceFilterButton = event.target.closest('[data-maintenance-filter]');
        if (maintenanceFilterButton && equipmentSection?.contains(maintenanceFilterButton)) {
            applyMaintenanceFilter(maintenanceFilterButton.dataset.maintenanceFilter);
            return;
        }

        const crewMemberCard = event.target.closest('[data-crew-member-card]');
        if (crewMemberCard) {
            openCrewMemberModal(crewMemberCard);
            return;
        }

        if (event.target.closest('[data-crew-member-close]')) {
            closeCrewMemberModal();
            return;
        }

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
    const documentKeydownHandler = (event) => {
        if (event.key === 'Escape' && document.getElementById('crewMemberDetailModal')?.classList.contains('is-open')) {
            closeCrewMemberModal();
        }
    };
    document.addEventListener('keydown', documentKeydownHandler);
    personnelSection?.addEventListener('input', handlePersonnelSearch);
    document.addEventListener('visibilitychange', handleReadinessVisibilityChange);

    reportTabs?.addEventListener('click', (event) => {
        const tab = event.target.closest('.report-tab');
        if (!tab) return;
        switchReportTab(tab.dataset.reportTab);
    });

    document.querySelectorAll('.report-modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                const modalType = modal.id.replace('ReportModal', '');
                if (modal.id === 'manageReportModal') {
                    closeManageModal();
                    return;
                }
                closeReportModal(modalType);
            }
        });
    });

    window.loadFirefighterStationOverview = loadOverview;
    window.loadFirefighterStationPersonnel = loadPersonnel;
    window.loadFirefighterStationEquipment = loadEquipment;
    window.loadFirefighterStationReports = () => loadReports(currentReportTab);

    bindMobileReportPickers();
    bindForms();
    loadOverview();
    loadPersonnel();
    loadReports(initialReportTab);
    loadEquipment();
    lastReadinessRefreshAt = Date.now();
    readinessRefreshTimer = window.setInterval(refreshReadiness, READINESS_REFRESH_INTERVAL_MS);
    handleReportDeepLink();

    window.__ffCurrentPageCleanup = function () {
        if (readinessRefreshTimer) window.clearInterval(readinessRefreshTimer);
        readinessRefreshTimer = null;
        cleanupRequests();
        closeCrewMemberModal({ restoreFocus: false });
        closeReportPicker();
        unbindMobileReportPickers();
        closeManageModal();
        document.querySelectorAll('.report-modal.active').forEach((modal) => {
            modal.classList.remove('active');
            modal.dataset.reportState = 'idle';
        });
        updateModalOpenState();
        document.removeEventListener('click', documentClickHandler);
        document.removeEventListener('keydown', documentKeydownHandler);
        personnelSection?.removeEventListener('input', handlePersonnelSearch);
        document.removeEventListener('visibilitychange', handleReadinessVisibilityChange);
    };
}

window.initFirefighterMyStationPage = bootFirefighterMyStationPage;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFirefighterMyStationPage, { once: true });
} else {
    bootFirefighterMyStationPage();
}
