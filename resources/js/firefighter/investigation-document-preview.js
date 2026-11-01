import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

let pdfLibrary;
async function loadPdfLibrary() {
    pdfLibrary ||= import('pdfjs-dist').then((library) => {
        library.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;
        return library;
    });
    return pdfLibrary;
}

// Render the exporter's PDF pages; never modify form values or submission behavior.
export function mountInvestigationDocumentPreview(preview) {
        if (!preview || preview.dataset.previewMounted === 'true') return () => {};
        preview.dataset.previewMounted = 'true';
        const form = preview.closest('form');
        const modal = preview.closest('.report-modal');
        const storedPreview = preview.dataset.previewMode === 'stored';
        const viewport = preview.querySelector('.investigation-preview__scroll');
        const frame = preview.querySelector('.investigation-preview__zoom-frame');
        const content = preview.querySelector('[data-preview-pages]');
        const controls = preview.querySelector('.investigation-preview__zoom');
        const status = preview.querySelector('[data-preview-status]');
        let zoom = 100;
        let documentWidth = 816;
        let poll = null;
        let debounce = null;
        let request = null;
        let pending = false;
        let revision = 0;
        let lastSignature = '';
        let disposed = false;
        let loadingPdf = null;
        const ready = () => !disposed && modal?.classList.contains('active')
            && (storedPreview || modal.dataset.reportState === 'ready');
        function setStatus(message, stale = false) {
            status.textContent = message;
            preview.classList.toggle('is-stale', stale);
        }
        function layoutZoom() {
            if (!viewport.clientWidth) return;
            const styles = getComputedStyle(viewport);
            const available = viewport.clientWidth - parseFloat(styles.paddingLeft) - parseFloat(styles.paddingRight);
            const scale = Math.min(1, Math.max(1, available) / documentWidth) * zoom / 100;
            content.style.width = `${documentWidth}px`;
            content.style.transform = `scale(${scale})`;
            frame.style.width = `${documentWidth * scale}px`;
            frame.style.height = `${content.offsetHeight * scale}px`;
        }
        function previewPages() {
            return Array.from(content.querySelectorAll('[data-preview-page]'));
        }
        function rememberPosition() {
            const pages = previewPages();
            if (!pages.length) return null;
            const viewportRect = viewport.getBoundingClientRect();
            const marker = viewportRect.top + 8;
            let index = pages.findIndex((page) => page.getBoundingClientRect().bottom > marker);
            if (index < 0) index = pages.length - 1;
            const pageRect = pages[index].getBoundingClientRect();
            return {
                index,
                offset: viewportRect.top - pageRect.top,
                scrollLeft: viewport.scrollLeft,
            };
        }
        function restorePosition(position) {
            if (!position) return;
            requestAnimationFrame(() => {
                if (!ready()) return;
                layoutZoom();
                requestAnimationFrame(() => {
                    const pages = previewPages();
                    if (!pages.length) return;
                    const page = pages[Math.min(position.index, pages.length - 1)];
                    const viewportRect = viewport.getBoundingClientRect();
                    const pageRect = page.getBoundingClientRect();
                    viewport.scrollTop += pageRect.top - viewportRect.top + position.offset;
                    viewport.scrollLeft = position.scrollLeft;
                });
            });
        }
        function snapshot() {
            if (!form) return { data: null, signature: preview.dataset.previewUrl || '' };
            const data = new FormData(form);
            const entries = Array.from(data.entries()).filter(([, value]) => !(value instanceof File) || value.size);
            return { data, signature: JSON.stringify(entries.map(([name, value]) => [name,
                value instanceof File ? [value.name, value.size, value.lastModified] : value])) };
        }
        async function renderPdf(bytes, version) {
            const library = await loadPdfLibrary();
            if (!ready() || version !== revision) return;
            const task = library.getDocument({ data: bytes, isEvalSupported: false });
            loadingPdf = task;
            try {
                const pdf = await task.promise;
                const pages = document.createDocumentFragment();
                let width = 0;
                for (let number = 1; number <= pdf.numPages; number++) {
                    if (!ready() || version !== revision) return;
                    const page = await pdf.getPage(number);
                    const view = page.getViewport({ scale: 96 / 72 });
                    width = Math.max(width, view.width);
                    const canvas = document.createElement('canvas');
                    const density = 1.5;
                    canvas.width = Math.ceil(view.width * density);
                    canvas.height = Math.ceil(view.height * density);
                    canvas.style.width = `${view.width}px`;
                    canvas.style.height = `${view.height}px`;
                    canvas.className = 'investigation-preview__pdf-page';
                    canvas.setAttribute('role', 'img');
                    const text = await page.getTextContent();
                    canvas.setAttribute('aria-label', `Page ${number} of ${pdf.numPages}. ${text.items.map((item) => item.str || '').join(' ')}`);
                    await page.render({ canvas, viewport: view,
                        transform: [density, 0, 0, density, 0, 0] }).promise;
                    const sheet = document.createElement('div');
                    sheet.className = 'investigation-preview__page-sheet';
                    sheet.dataset.previewPage = number;
                    sheet.style.width = `${view.width}px`;
                    const label = document.createElement('span');
                    label.className = 'investigation-preview__page-label';
                    label.textContent = `Page ${number} of ${pdf.numPages}`;
                    sheet.append(canvas, label);
                    pages.append(sheet);
                    page.cleanup();
                }
                if (!ready() || version !== revision) return;
                const position = rememberPosition();
                documentWidth = width || 816;
                content.replaceChildren(pages);
                layoutZoom();
                restorePosition(position);
                setStatus(`Preview up to date - ${pdf.numPages} ${pdf.numPages === 1 ? 'page' : 'pages'}`);
            } finally {
                if (loadingPdf === task) loadingPdf = null;
                await task.destroy();
            }
        }
        async function update() {
            if (!ready() || document.hidden) return;
            if (request) { pending = true; return; }
            pending = false;
            const version = revision;
            const current = snapshot();
            request = new AbortController();
            setStatus('Rendering Word template...', true);
            preview.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(preview.dataset.previewUrl, {
                    method: storedPreview ? 'GET' : 'POST',
                    ...(current.data ? { body: current.data } : {}),
                    credentials: 'same-origin', signal: request.signal,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/json',
                        'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    const validationMessage = Object.values(payload.errors || {}).flat()[0];
                    throw new Error(validationMessage || payload.message || 'Preview unavailable. Select Refresh to retry.');
                }
                const contentType = response.headers.get('Content-Type') || '';
                const isPdf = contentType.includes('application/pdf');
                if (!isPdf) throw new Error('The exact Word-template preview could not be rendered. Select Refresh to retry.');
                const bytes = await response.arrayBuffer();
                detectChanges();
                if (ready() && version === revision) {
                    await renderPdf(bytes, version);
                }
            } catch (error) {
                if (ready() && version === revision && error.name !== 'AbortError') {
                    setStatus(`${error.message} ${content.childElementCount ? 'The displayed preview is out of date.' : ''}`, true);
                }
            } finally {
                request = null;
                preview.setAttribute('aria-busy', 'false');
                if (pending && ready()) {
                    clearTimeout(debounce);
                    debounce = setTimeout(update, 900);
                }
            }
        }
        function detectChanges() {
            if (!ready() || document.hidden) return;
            const { signature } = snapshot();
            if (signature === lastSignature) return;
            lastSignature = signature;
            revision++;
            pending = true;
            setStatus(storedPreview ? 'Loading saved report document...' : 'Changes pending - updating after you pause typing...', true);
            clearTimeout(debounce);
            debounce = setTimeout(update, storedPreview ? 0 : 900);
        }
        function sync() {
            clearInterval(poll);
            poll = null;
            if (ready()) {
                layoutZoom();
                detectChanges();
                if (!request && preview.classList.contains('is-stale')) {
                    clearTimeout(debounce);
                    debounce = setTimeout(update, 900);
                }
                if (!storedPreview) poll = setInterval(detectChanges, 400);
            } else {
                clearTimeout(debounce);
                if (!modal.classList.contains('active')) {
                    revision++;
                    lastSignature = '';
                    pending = false;
                    request?.abort();
                    content.replaceChildren();
                    layoutZoom();
                    setStatus('Preparing document preview...');
                }
            }
        }
        function handleControls(event) {
            const button = event.target.closest('button');
            if (!button) return;
            if (button.hasAttribute('data-preview-retry')) {
                revision++;
                clearTimeout(debounce);
                update();
                return;
            }
            const action = button.dataset.previewZoom;
            if (!action) return;
            zoom = action === 'reset' ? 100 : Math.min(200, Math.max(50, zoom + (action === 'in' ? 10 : -10)));
            preview.querySelector('[data-preview-zoom-label]').textContent = `${zoom}%`;
            controls.querySelector('[data-preview-zoom="out"]').disabled = zoom <= 50;
            controls.querySelector('[data-preview-zoom="in"]').disabled = zoom >= 200;
            layoutZoom();
        }
        const observer = new MutationObserver(sync);
        observer.observe(modal, { attributes: true, attributeFilter: ['class', 'data-report-state'] });
        const resize = new ResizeObserver(layoutZoom);
        resize.observe(viewport);
        resize.observe(content);
        form?.addEventListener('input', detectChanges);
        form?.addEventListener('change', detectChanges);
        controls.addEventListener('click', handleControls);
        sync();
        return () => {
            disposed = true;
            revision++;
            observer.disconnect();
            resize.disconnect();
            clearInterval(poll);
            clearTimeout(debounce);
            request?.abort();
            loadingPdf?.destroy();
            form?.removeEventListener('input', detectChanges);
            form?.removeEventListener('change', detectChanges);
            controls.removeEventListener('click', handleControls);
            delete preview.dataset.previewMounted;
        };
}

export function initInvestigationDocumentPreviews() {
    const cleanups = [];
    document.querySelectorAll('[data-document-preview]').forEach((preview) => {
        cleanups.push(mountInvestigationDocumentPreview(preview));
    });
    return () => cleanups.forEach((cleanup) => cleanup());
}
