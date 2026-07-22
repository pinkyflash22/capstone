@php
    $showPwaInstallButton = $showPwaInstallButton ?? false;
    $pwaServiceWorkerVersion = file_exists(public_path('sw.js')) ? (string) filemtime(public_path('sw.js')) : (string) time();
    $pwaInstallScript = asset('pwa-install.js') . '?v=' . $pwaServiceWorkerVersion;
    $pwaBackgroundSyncScript = asset('background-sync.js') . '?v=' . $pwaServiceWorkerVersion;
@endphp

<script src="{{ $pwaInstallScript }}"></script>
<script src="{{ $pwaBackgroundSyncScript }}"></script>

@if ($showPwaInstallButton)
    <style>
        .pyrosense-pwa-install {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 1400;
            display: none;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 999px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 14px 32px rgba(198, 61, 38, 0.28);
            font-weight: 800;
            font-size: 14px;
            white-space: nowrap;
            cursor: pointer;
        }

        .pyrosense-pwa-install__icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .pyrosense-pwa-sheet {
            position: fixed;
            inset: 0;
            z-index: 1500;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(15, 23, 42, 0.52);
            padding: 16px;
        }

        .pyrosense-pwa-sheet.is-open {
            display: flex;
        }

        .pyrosense-pwa-sheet__card {
            width: min(100%, 420px);
            border-radius: 26px;
            background: #fffaf4;
            color: #1f2937;
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .pyrosense-pwa-sheet__header {
            padding: 18px 20px 12px;
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.12), rgba(209, 138, 22, 0.14));
        }

        .pyrosense-pwa-sheet__title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .pyrosense-pwa-sheet__body {
            padding: 16px 20px 18px;
            font-size: 14px;
            line-height: 1.6;
            color: #4b5563;
        }

        .pyrosense-pwa-sheet__body ol {
            margin: 12px 0 0 18px;
            padding: 0;
        }

        .pyrosense-pwa-sheet__body li + li {
            margin-top: 8px;
        }

        .pyrosense-pwa-sheet__actions {
            display: flex;
            justify-content: flex-end;
            padding: 0 20px 20px;
        }

        .pyrosense-pwa-sheet__close {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            background: #f3e5dc;
            color: #9a3412;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 640px) {
            .pyrosense-pwa-install {
                left: 50%;
                right: auto;
                bottom: calc(14px + env(safe-area-inset-bottom));
                transform: translateX(-50%);
                justify-content: center;
                gap: 8px;
                width: min(calc(100vw - 28px), 320px);
                min-height: 46px;
                padding: 10px 14px;
                font-size: 13px;
                box-shadow: 0 12px 24px rgba(198, 61, 38, 0.2);
            }

            .pyrosense-pwa-install__icon {
                width: 26px;
                height: 26px;
                font-size: 13px;
            }
        }
    </style>

    <button id="pyrosense-pwa-install" class="pyrosense-pwa-install" type="button" aria-haspopup="dialog" aria-controls="pyrosense-pwa-sheet">
        <span class="pyrosense-pwa-install__icon"><i class="fa-solid fa-download" aria-hidden="true"></i></span>
        <span>Install PyroSense</span>
    </button>

    <div id="pyrosense-pwa-sheet" class="pyrosense-pwa-sheet" aria-hidden="true">
        <div class="pyrosense-pwa-sheet__card" role="dialog" aria-modal="true" aria-labelledby="pyrosense-pwa-title">
            <div class="pyrosense-pwa-sheet__header">
                <h2 id="pyrosense-pwa-title" class="pyrosense-pwa-sheet__title">Install PyroSense</h2>
            </div>
            <div id="pyrosense-pwa-sheet-body" class="pyrosense-pwa-sheet__body"></div>
            <div class="pyrosense-pwa-sheet__actions">
                <button id="pyrosense-pwa-close" class="pyrosense-pwa-sheet__close" type="button">Close</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const installBtn = document.getElementById('pyrosense-pwa-install');
            const fallbackSheet = document.getElementById('pyrosense-pwa-sheet');
            const fallbackBody = document.getElementById('pyrosense-pwa-sheet-body');
            const fallbackClose = document.getElementById('pyrosense-pwa-close');

            if (!installBtn || !fallbackSheet || !fallbackBody || !fallbackClose) {
                return;
            }

            const isStandalone = function () {
                return window.laravelPwaInstall?.isStandalone?.()
                    || window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;
            };

            const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
            const isSafari = /safari/i.test(window.navigator.userAgent) && !/chrome|crios|fxios|edgios/i.test(window.navigator.userAgent);

            const openSheet = function (html) {
                fallbackBody.innerHTML = html;
                fallbackSheet.classList.add('is-open');
                fallbackSheet.setAttribute('aria-hidden', 'false');
            };

            const closeSheet = function () {
                fallbackSheet.classList.remove('is-open');
                fallbackSheet.setAttribute('aria-hidden', 'true');
            };

            const showInstallButton = function () {
                if (!isStandalone()) {
                    installBtn.style.display = 'inline-flex';
                }
            };

            const updateInstallButton = function () {
                if (isStandalone()) {
                    installBtn.style.display = 'none';
                    closeSheet();
                    return;
                }

                if (window.laravelPwaInstall?.canInstall?.()) {
                    installBtn.style.display = 'inline-flex';
                    installBtn.dataset.mode = 'prompt';
                    return;
                }

                if (isIos && isSafari) {
                    installBtn.style.display = 'inline-flex';
                    installBtn.dataset.mode = 'ios';
                    return;
                }

                installBtn.style.display = 'none';
            };

            installBtn.addEventListener('click', async function () {
                const mode = installBtn.dataset.mode;

                if (mode === 'prompt' && window.laravelPwaInstall?.showPrompt) {
                    await window.laravelPwaInstall.showPrompt();
                    window.setTimeout(updateInstallButton, 250);
                    return;
                }

                openSheet(
                    '<p>Add PyroSense to your home screen for the app-style experience.</p>' +
                    '<ol>' +
                    '<li>Tap the <strong>Share</strong> button in Safari.</li>' +
                    '<li>Select <strong>Add to Home Screen</strong>.</li>' +
                    '<li>Tap <strong>Add</strong> to install PyroSense.</li>' +
                    '</ol>'
                );
            });

            fallbackClose.addEventListener('click', closeSheet);
            fallbackSheet.addEventListener('click', function (event) {
                if (event.target === fallbackSheet) {
                    closeSheet();
                }
            });

            window.addEventListener('pwa-installable', function () {
                showInstallButton();
                updateInstallButton();
            });

            window.addEventListener('pwa-installed', function () {
                updateInstallButton();
            });

            document.addEventListener('DOMContentLoaded', function () {
                updateInstallButton();
            });
        })();
    </script>
@endif

<style>
    .pyrosense-connection-pill {
        position: fixed;
        left: 16px;
        bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        z-index: 1550;
        display: none;
        align-items: center;
        gap: 10px;
        max-width: min(420px, calc(100vw - 32px));
        padding: 12px 15px;
        border-radius: 18px;
        background: rgba(15, 23, 42, 0.92);
        color: #fff7ed;
        border: 1px solid rgba(251, 146, 60, 0.22);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.24);
        backdrop-filter: blur(14px);
        font-size: 13px;
        line-height: 1.45;
    }

    .pyrosense-connection-pill.is-visible {
        display: inline-flex;
    }

    .pyrosense-connection-pill__icon {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.12);
        font-weight: 800;
    }

    .pyrosense-connection-pill__copy strong,
    .pyrosense-connection-pill__copy span {
        display: block;
    }

    .pyrosense-connection-pill__copy strong {
        font-size: 13px;
        font-weight: 800;
    }

    .pyrosense-connection-pill__copy span {
        margin-top: 2px;
        color: rgba(255, 247, 237, 0.84);
    }

    .pyrosense-map-offline-note {
        position: absolute;
        top: 88px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 700;
        display: none;
        width: min(92%, 420px);
        padding: 10px 14px;
        border-radius: 14px;
        background: rgba(255, 248, 235, 0.96);
        color: #9a3412;
        border: 1px solid rgba(249, 115, 22, 0.22);
        box-shadow: 0 14px 30px rgba(249, 115, 22, 0.12);
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .pyrosense-map-offline-note.is-visible {
        display: block;
    }

    @media (max-width: 640px) {
        .pyrosense-connection-pill {
            left: 12px;
            right: 12px;
            bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            max-width: none;
        }
    }
</style>

<div id="pyrosenseConnectionPill" class="pyrosense-connection-pill" role="status" aria-live="polite">
    <span id="pyrosenseConnectionPillIcon" class="pyrosense-connection-pill__icon">!</span>
    <span class="pyrosense-connection-pill__copy">
        <strong id="pyrosenseConnectionPillTitle">Offline mode</strong>
        <span id="pyrosenseConnectionPillText">Saved pages and queued reports will stay available.</span>
    </span>
</div>

<script>
    (function () {
        const STORAGE_PREFIX = 'pyrosense-pwa';
        const DATASET_KEY = `${STORAGE_PREFIX}:datasets`;
        const REPORT_QUEUE_KEY = `${STORAGE_PREFIX}:report-queue`;
        const STATUS_KEY = `${STORAGE_PREFIX}:status`;
        const SW_URL = `/sw.js?v={{ $pwaServiceWorkerVersion }}`;
        const AUTH_URL = @json(url('/auth'));
        const pill = document.getElementById('pyrosenseConnectionPill');
        const pillIcon = document.getElementById('pyrosenseConnectionPillIcon');
        const pillTitle = document.getElementById('pyrosenseConnectionPillTitle');
        const pillText = document.getElementById('pyrosenseConnectionPillText');
        if (!window.__pyroNativeFetch) {
            window.__pyroNativeFetch = window.fetch.bind(window);
        }

        const nativeFetch = window.__pyroNativeFetch;
        let sessionExpiredPromptOpen = false;
        let sessionExpiredDetected = false;

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function isWriteMethod(method) {
            const normalized = String(method || 'GET').toUpperCase();
            return ['POST', 'PUT', 'PATCH', 'DELETE'].includes(normalized);
        }

        function isSameOriginRequest(input) {
            try {
                const requestUrl = typeof input === 'string'
                    ? input
                    : (input instanceof Request ? input.url : String(input?.url || ''));

                const resolved = new URL(requestUrl, window.location.origin);
                return resolved.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        }

        function handleSessionExpired() {
            if (sessionExpiredPromptOpen) {
                return;
            }

            sessionExpiredPromptOpen = true;
            sessionExpiredDetected = true;

            const confirmed = window.confirm('Your session expired. Please sign in again.');
            sessionExpiredPromptOpen = false;

            if (confirmed) {
                window.location.assign(AUTH_URL);
            }
        }

        async function pyroFetch(input, init = {}) {
            const method = String(
                init.method
                || (input instanceof Request ? input.method : 'GET')
            ).toUpperCase();
            const sameOrigin = isSameOriginRequest(input);
            const requestInit = { ...init };

            if (sameOrigin) {
                requestInit.credentials = requestInit.credentials || 'same-origin';
            }

            if (sameOrigin && isWriteMethod(method)) {
                const headers = new Headers(requestInit.headers || (input instanceof Request ? input.headers : undefined) || {});
                headers.set('Accept', 'application/json');
                headers.set('X-Requested-With', 'XMLHttpRequest');

                const csrfToken = getCsrfToken();
                if (csrfToken) {
                    headers.set('X-CSRF-TOKEN', csrfToken);
                }

                requestInit.headers = headers;
            }

            const response = await nativeFetch(input, requestInit);

            if (sameOrigin && isWriteMethod(method) && response.status === 419) {
                handleSessionExpired();
            }

            return response;
        }

        window.pyroFetch = pyroFetch;

        if (!window.__pyroFetchWrapped) {
            window.fetch = function (input, init) {
                return pyroFetch(input, init);
            };
            window.fetch.__pyroWrapped = true;
            window.__pyroFetchWrapped = true;
        }

        function safeReadJson(key, fallback) {
            try {
                const raw = window.localStorage?.getItem(key);
                return raw ? JSON.parse(raw) : fallback;
            } catch (error) {
                console.warn('[PyroSense PWA] Failed to read local storage key:', key, error);
                return fallback;
            }
        }

        function safeWriteJson(key, value) {
            try {
                window.localStorage?.setItem(key, JSON.stringify(value));
                return true;
            } catch (error) {
                console.warn('[PyroSense PWA] Failed to write local storage key:', key, error);
                return false;
            }
        }

        function getQueue() {
            const queue = safeReadJson(REPORT_QUEUE_KEY, []);
            return Array.isArray(queue) ? queue : [];
        }

        function setQueue(queue) {
            safeWriteJson(REPORT_QUEUE_KEY, Array.isArray(queue) ? queue : []);
        }

        function getDatasets() {
            const datasets = safeReadJson(DATASET_KEY, {});
            return datasets && typeof datasets === 'object' ? datasets : {};
        }

        function setDatasets(nextDatasets) {
            safeWriteJson(DATASET_KEY, nextDatasets && typeof nextDatasets === 'object' ? nextDatasets : {});
        }

        function showConnectionState(title, message, icon) {
            if (!pill || !pillIcon || !pillTitle || !pillText) {
                return;
            }

            pillTitle.textContent = title;
            pillText.textContent = message;
            pillIcon.textContent = icon;
            pill.classList.add('is-visible');
        }

        function hideConnectionState() {
            if (!pill) {
                return;
            }

            pill.classList.remove('is-visible');
        }

        function updateConnectionState(forceVisible = false) {
            const pendingCount = getQueue().length;
            const offline = !window.navigator.onLine;

            if (offline) {
                showConnectionState(
                    'Offline mode',
                    pendingCount > 0
                        ? `${pendingCount} report${pendingCount === 1 ? '' : 's'} pending sync. Cached pages and saved data are still available.`
                        : 'Cached pages and your last saved dashboard data are still available.',
                    '!'
                );
                return;
            }

            if (pendingCount > 0 || forceVisible) {
                showConnectionState(
                    pendingCount > 0 ? 'Reconnecting' : 'Back online',
                    pendingCount > 0
                        ? `Syncing ${pendingCount} queued report${pendingCount === 1 ? '' : 's'} now.`
                        : 'Live updates and online reporting are available again.',
                    pendingCount > 0 ? '...' : 'OK'
                );
                if (!pendingCount) {
                    window.setTimeout(hideConnectionState, 2200);
                }
                return;
            }

            hideConnectionState();
        }

        async function syncQueuedReports() {
            if (!window.navigator.onLine) {
                updateConnectionState(true);
                return { synced: 0, remaining: getQueue().length };
            }

            if (sessionExpiredDetected) {
                return { synced: 0, remaining: getQueue().length };
            }

            const queue = getQueue();
            if (!queue.length) {
                updateConnectionState(true);
                return { synced: 0, remaining: 0 };
            }

            const remaining = [];
            let synced = 0;

            for (const item of queue) {
                try {
                    const response = await pyroFetch(item.url, {
                        method: item.method || 'POST',
                        headers: item.headers || {},
                        body: item.body || null
                    });

                    if (response.status === 419) {
                        remaining.push(item);
                        break;
                    }

                    if (!response.ok) {
                        remaining.push(item);
                        continue;
                    }

                    const payload = await response.json().catch(() => ({}));
                    synced += 1;

                    window.dispatchEvent(new CustomEvent('pyrosense:report-synced', {
                        detail: {
                            queueItem: item,
                            response: payload
                        }
                    }));
                } catch (error) {
                    remaining.push(item);
                }
            }

            setQueue(remaining);
            safeWriteJson(STATUS_KEY, {
                synced_at: new Date().toISOString(),
                synced,
                remaining: remaining.length
            });

            updateConnectionState(true);

            if (!remaining.length) {
                window.setTimeout(hideConnectionState, 2200);
            }

            return { synced, remaining: remaining.length };
        }

        function storeDataset(name, data) {
            if (!name) {
                return;
            }

            const datasets = getDatasets();
            datasets[name] = {
                stored_at: new Date().toISOString(),
                data
            };
            setDatasets(datasets);
        }

        function loadDataset(name, fallback = null) {
            const datasets = getDatasets();
            return datasets[name]?.data ?? fallback;
        }

        function enqueueOfflineReport(report) {
            const queue = getQueue();
            const item = {
                id: report.id || `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                url: report.url,
                method: report.method || 'POST',
                headers: report.headers || {},
                body: report.body || null,
                type: report.type || 'report',
                created_at: report.created_at || new Date().toISOString(),
                meta: report.meta || {}
            };

            queue.push(item);
            setQueue(queue);
            updateConnectionState(true);

            window.dispatchEvent(new CustomEvent('pyrosense:report-queued', {
                detail: {
                    queueItem: item,
                    pendingCount: queue.length
                }
            }));

            return item;
        }

        function attachMapOfflineNotice(mapInstance, options = {}) {
            if (!mapInstance || mapInstance.__pyrosenseOfflineNoticeBound) {
                return;
            }

            const container = mapInstance.getContainer?.();
            if (!container) {
                return;
            }

            const message = options.message || 'Map tiles are unavailable offline. Showing last saved data.';
            const note = document.createElement('div');
            note.className = 'pyrosense-map-offline-note';
            note.textContent = message;
            container.appendChild(note);

            const showNote = function () {
                note.classList.add('is-visible');
            };

            const hideNote = function () {
                note.classList.remove('is-visible');
            };

            mapInstance.eachLayer((layer) => {
                if (layer instanceof L.TileLayer) {
                    layer.on('tileerror', showNote);
                    layer.on('load', hideNote);
                }
            });

            mapInstance.__pyrosenseOfflineNoticeBound = true;
            mapInstance.__pyrosenseOfflineNote = note;
        }

        async function ensureServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register(SW_URL, { scope: '/' });
                if (window.navigator.onLine) {
                    registration.update?.();
                }
            } catch (error) {
                console.error('[PyroSense PWA] Service worker registration failed:', error);
            }
        }

        window.pyroPwa = Object.assign(window.pyroPwa || {}, {
            storeDataset,
            loadDataset,
            enqueueOfflineReport,
            syncQueuedReports,
            updateConnectionState,
            attachMapOfflineNotice,
            getPendingReportCount: () => getQueue().length,
            getQueuedReports: () => getQueue().slice(),
            isOffline: () => !window.navigator.onLine
        });

        window.addEventListener('online', function () {
            updateConnectionState(true);
            syncQueuedReports();
        });

        window.addEventListener('offline', function () {
            updateConnectionState(true);
        });

        document.addEventListener('DOMContentLoaded', function () {
            ensureServiceWorker();
            updateConnectionState(false);
            if (window.navigator.onLine && getQueue().length) {
                syncQueuedReports();
            }
        });
    })();
</script>
