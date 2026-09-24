@include('partials.unified-notifications')

@php
    $showPwaInstallButton = $showPwaInstallButton ?? false;
    $pwaVersionedFiles = [public_path('sw.js'), public_path('pwa-install.js'), public_path('background-sync.js')];
    $pwaServiceWorkerVersion = (string) (collect($pwaVersionedFiles)
        ->filter(fn ($path) => file_exists($path))
        ->map(fn ($path) => filemtime($path))
        ->max() ?: time());
    $pwaInstallScript = asset('pwa-install.js') . '?v=' . $pwaServiceWorkerVersion;
    $pwaBackgroundSyncScript = asset('background-sync.js') . '?v=' . $pwaServiceWorkerVersion;
@endphp

<script src="{{ $pwaInstallScript }}"></script>
<script>
    window.pyroOfflineConfig = {
        userId: @json(auth()->id()),
    };
</script>
<script src="{{ $pwaBackgroundSyncScript }}"></script>

<style>
    @keyframes pyrosense-image-shimmer {
        0% { background-position: 180% 0; }
        100% { background-position: -180% 0; }
    }

    img[data-pyro-image-state="loading"] {
        background-color: #e8edf3;
        background-image: linear-gradient(100deg, transparent 25%, rgba(255, 255, 255, 0.78) 46%, transparent 67%);
        background-repeat: no-repeat;
        background-size: 220% 100%;
        animation: pyrosense-image-shimmer 1.15s ease-in-out infinite;
    }

    img[data-pyro-image-state="error"] {
        background-color: #eef2f6;
        background-image: linear-gradient(135deg, #eef2f6, #e2e8f0);
    }

    @media (prefers-reduced-motion: reduce) {
        img[data-pyro-image-state="loading"] {
            animation: none;
        }
    }
</style>

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
            bottom: calc(var(--mobile-bottom-nav-h, var(--ff-mobile-dock-h, 0px)) + 10px + env(safe-area-inset-bottom, 0px));
            max-width: none;
            padding: 10px 12px;
            gap: 8px;
            border-radius: 15px;
            font-size: 12px;
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

<style>
    .pyrosense-page-snapshot-badge {
        position: fixed;
        right: 16px;
        bottom: calc(76px + env(safe-area-inset-bottom, 0px));
        z-index: 1545;
        display: none;
        align-items: center;
        gap: 10px;
        max-width: min(420px, calc(100vw - 32px));
        padding: 12px 15px;
        border-radius: 18px;
        background: rgba(255, 250, 244, 0.97);
        color: #7c2d12;
        border: 1px solid rgba(245, 158, 11, 0.2);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
        backdrop-filter: blur(14px);
        font-size: 13px;
        line-height: 1.45;
    }

    .pyrosense-page-snapshot-badge.is-visible {
        display: inline-flex;
    }

    .pyrosense-page-snapshot-badge__icon {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(135deg, rgba(198, 61, 38, 0.12), rgba(209, 138, 22, 0.2));
        color: #c2410c;
        font-weight: 800;
    }

    .pyrosense-page-snapshot-badge__copy strong,
    .pyrosense-page-snapshot-badge__copy span {
        display: block;
    }

    .pyrosense-page-snapshot-badge__copy strong {
        font-size: 13px;
        font-weight: 800;
    }

    .pyrosense-page-snapshot-badge__copy span {
        margin-top: 2px;
        color: #9a3412;
    }

    @media (max-width: 640px) {
        .pyrosense-page-snapshot-badge {
            left: 12px;
            right: 12px;
            bottom: calc(var(--mobile-bottom-nav-h, var(--ff-mobile-dock-h, 0px)) + 58px + env(safe-area-inset-bottom, 0px));
            max-width: none;
            padding: 10px 12px;
            border-radius: 15px;
        }
    }
</style>

<div id="pyrosensePageSnapshotBadge" class="pyrosense-page-snapshot-badge" role="status" aria-live="polite">
    <span id="pyrosensePageSnapshotBadgeIcon" class="pyrosense-page-snapshot-badge__icon">
        <i class="fa-solid fa-database"></i>
    </span>
    <span class="pyrosense-page-snapshot-badge__copy">
        <strong id="pyrosensePageSnapshotBadgeTitle">Offline snapshot ready</strong>
        <span id="pyrosensePageSnapshotBadgeText">Last loaded data is available for this page.</span>
    </span>
</div>

<script>
    (function () {
        const STORAGE_PREFIX = 'pyrosense-pwa';
        const ACCOUNT_SCOPE = String(@json(auth()->id()) || 'guest');
        const DATASET_KEY = `${STORAGE_PREFIX}:datasets:${ACCOUNT_SCOPE}`;
        const REPORT_QUEUE_KEY = `${STORAGE_PREFIX}:report-queue:${ACCOUNT_SCOPE}`;
        const STATUS_KEY = `${STORAGE_PREFIX}:status:${ACCOUNT_SCOPE}`;
        const PAGE_SNAPSHOT_KEY = `${STORAGE_PREFIX}:page-snapshots:${ACCOUNT_SCOPE}`;
        const PAGE_HTML_CACHE = `${STORAGE_PREFIX}:page-html:${ACCOUNT_SCOPE || 'guest'}`;
        const SHARED_PAGE_HTML_CACHE = `${STORAGE_PREFIX}:page-html:last`;
        const OFFLINE_RETURN_KEY = `${STORAGE_PREFIX}:offline-return:${ACCOUNT_SCOPE}`;
        const SW_URL = `/sw.js?v={{ $pwaServiceWorkerVersion }}`;
        const AUTH_URL = @json(url('/auth'));
        const OFFLINE_PILL_AUTO_HIDE_MS = 2600;
        const SNAPSHOT_BADGE_AUTO_HIDE_MS = 2600;
        const pill = document.getElementById('pyrosenseConnectionPill');
        const pillIcon = document.getElementById('pyrosenseConnectionPillIcon');
        const pillTitle = document.getElementById('pyrosenseConnectionPillTitle');
        const pillText = document.getElementById('pyrosenseConnectionPillText');
        const pageSnapshotBadge = document.getElementById('pyrosensePageSnapshotBadge');
        const pageSnapshotBadgeTitle = document.getElementById('pyrosensePageSnapshotBadgeTitle');
        const pageSnapshotBadgeText = document.getElementById('pyrosensePageSnapshotBadgeText');
        let imageStateObserver = null;
        if (!window.__pyroNativeFetch) {
            window.__pyroNativeFetch = window.fetch.bind(window);
        }

        const nativeFetch = window.__pyroNativeFetch;
        let sessionExpiredPromptOpen = false;
        let sessionExpiredDetected = false;

        function isManagedContentImage(image) {
            if (!(image instanceof HTMLImageElement) || image.dataset.pyroImageLoader === 'off') {
                return false;
            }

            if (image.classList.contains('leaflet-tile') || image.closest('.leaflet-container')) {
                return false;
            }

            const source = image.currentSrc || image.getAttribute('src') || '';
            if (!source || source.startsWith('data:') || source.startsWith('blob:')) {
                return false;
            }

            try {
                const imageUrl = new URL(source, window.location.href);
                return imageUrl.origin !== window.location.origin
                    || imageUrl.pathname.startsWith('/storage/');
            } catch (error) {
                return false;
            }
        }

        function setImageState(image, state) {
            image.dataset.pyroImageState = state;
            if (state === 'loading') {
                image.setAttribute('aria-busy', 'true');
            } else {
                image.removeAttribute('aria-busy');
            }
        }

        function prepareContentImage(image) {
            if (!isManagedContentImage(image)) {
                return;
            }

            const source = image.currentSrc || image.src;
            if (!image.dataset.pyroImageRetrying) {
                image.dataset.pyroImageOriginalSrc = image.getAttribute('src') || source;
                if (image.getAttribute('srcset')) {
                    image.dataset.pyroImageOriginalSrcset = image.getAttribute('srcset');
                }
            }

            if (!image.dataset.pyroImageBound) {
                image.dataset.pyroImageBound = 'true';
                image.addEventListener('load', () => {
                    image.dataset.pyroImageFailed = 'false';
                    delete image.dataset.pyroImageRetrying;
                    setImageState(image, 'loaded');
                });
                image.addEventListener('error', () => {
                    image.dataset.pyroImageFailed = 'true';
                    delete image.dataset.pyroImageRetrying;
                    setImageState(image, 'error');
                });
            }

            if (image.complete) {
                setImageState(image, image.naturalWidth > 0 ? 'loaded' : 'error');
                image.dataset.pyroImageFailed = image.naturalWidth > 0 ? 'false' : 'true';
                return;
            }

            setImageState(image, 'loading');
        }

        function appendImageRetryToken(source) {
            try {
                const retryUrl = new URL(source, window.location.href);
                retryUrl.searchParams.set('_pyro_image_retry', String(Date.now()));
                return retryUrl.href;
            } catch (error) {
                return source;
            }
        }

        function retryFailedContentImages() {
            document.querySelectorAll('img[data-pyro-image-failed="true"]').forEach((image) => {
                const originalSource = image.dataset.pyroImageOriginalSrc || image.getAttribute('src');
                if (!originalSource) {
                    return;
                }

                image.dataset.pyroImageRetrying = 'true';
                image.dataset.pyroImageFailed = 'false';
                setImageState(image, 'loading');

                const originalSrcset = image.dataset.pyroImageOriginalSrcset;
                if (originalSrcset) {
                    image.setAttribute('srcset', originalSrcset
                        .split(',')
                        .map((candidate) => {
                            const parts = candidate.trim().split(/\s+/);
                            parts[0] = appendImageRetryToken(parts[0]);
                            return parts.join(' ');
                        })
                        .join(', '));
                }

                image.src = appendImageRetryToken(originalSource);
            });
        }

        function initializeContentImageStates() {
            document.querySelectorAll('img').forEach(prepareContentImage);

            if (imageStateObserver) {
                return;
            }

            imageStateObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.target instanceof HTMLImageElement) {
                        prepareContentImage(mutation.target);
                        return;
                    }

                    mutation.addedNodes.forEach((node) => {
                        if (!(node instanceof Element)) {
                            return;
                        }

                        if (node instanceof HTMLImageElement) {
                            prepareContentImage(node);
                        }
                        node.querySelectorAll?.('img').forEach(prepareContentImage);
                    });
                });
            });
            imageStateObserver.observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['src', 'srcset']
            });
        }

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

        function getPageSnapshots() {
            const snapshots = safeReadJson(PAGE_SNAPSHOT_KEY, {});
            return snapshots && typeof snapshots === 'object' ? snapshots : {};
        }

        function setPageSnapshots(nextSnapshots) {
            safeWriteJson(PAGE_SNAPSHOT_KEY, nextSnapshots && typeof nextSnapshots === 'object' ? nextSnapshots : {});
        }

        function rememberOfflineReturnRoute() {
            try {
                const url = new URL(window.location.href);
                const path = url.pathname || '/';

                if (/^\/(?:auth|login|logout)(?:\/.*)?$/i.test(path)) {
                    return;
                }

                const fullRoute = `${path}${url.search}`;
                const roleMeta = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '';
                const inferredRole = roleMeta
                    || (path.startsWith('/citizen/') ? 'citizen' : (path.startsWith('/firefighter/') ? 'firefighter' : (path.startsWith('/chief/') ? 'chief' : (path.startsWith('/barangay/') ? 'barangay' : 'guest'))));
                const mobileHubRoute = inferredRole === 'citizen' ? '/citizen/mobile-hub' : null;

                safeWriteJson(OFFLINE_RETURN_KEY, {
                    role: inferredRole,
                    last_route: fullRoute,
                    preferred_route: mobileHubRoute || fullRoute,
                    mobile_hub_route: mobileHubRoute,
                    label: document.title || 'Cached page',
                    saved_at: new Date().toISOString(),
                });
            } catch (error) {
                console.warn('[PyroSense PWA] Failed to remember offline return route:', error);
            }
        }

        function formatSnapshotTimestamp(savedAt) {
            if (!savedAt) {
                return null;
            }

            const date = new Date(savedAt);
            if (Number.isNaN(date.getTime())) {
                return null;
            }

            return date.toLocaleString([], {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function renderPageSnapshotBadge(snapshot = null, options = {}) {
            if (!pageSnapshotBadge || !pageSnapshotBadgeTitle || !pageSnapshotBadgeText) {
                return;
            }

            if (!snapshot || (!options.forceVisible && window.navigator.onLine)) {
                pageSnapshotBadge.classList.remove('is-visible');
                window.clearTimeout(renderPageSnapshotBadge.timer);
                return;
            }

            const savedLabel = formatSnapshotTimestamp(snapshot.saved_at);
            pageSnapshotBadgeTitle.textContent = options.title || `${snapshot.label || 'Page'} offline snapshot`;
            pageSnapshotBadgeText.textContent = savedLabel
                ? `Showing the last loaded data saved on ${savedLabel}.`
                : 'Showing the last loaded data saved on this device.';
            pageSnapshotBadge.classList.add('is-visible');
            window.clearTimeout(renderPageSnapshotBadge.timer);

            if (options.autoHideMs !== 0) {
                renderPageSnapshotBadge.timer = window.setTimeout(() => {
                    pageSnapshotBadge.classList.remove('is-visible');
                }, options.autoHideMs || SNAPSHOT_BADGE_AUTO_HIDE_MS);
            }
        }

        function updatePageSnapshot(config = {}) {
            const key = config.key || window.location.pathname;
            if (!key) {
                return null;
            }

            const snapshots = getPageSnapshots();
            const snapshot = {
                key,
                label: config.label || snapshots[key]?.label || document.title || 'Page',
                path: window.location.pathname,
                saved_at: config.savedAt || new Date().toISOString(),
            };

            snapshots[key] = snapshot;
            setPageSnapshots(snapshots);
            cacheCurrentPageForOffline(snapshot);
            return snapshot;
        }

        async function cacheCurrentPageForOffline(snapshot = {}) {
            if (!('caches' in window) || !snapshot?.path) {
                return;
            }

            try {
                const path = snapshot.path || window.location.pathname;
                if (/^\/(?:auth|login|logout|register|forgot-password|reset-password)(?:\/.*)?$/i.test(path)) {
                    return;
                }

                const cache = await caches.open(PAGE_HTML_CACHE);
                const sharedCache = await caches.open(SHARED_PAGE_HTML_CACHE);
                const html = `<!doctype html>\n${document.documentElement.outerHTML}`;
                const response = new Response(html, {
                    headers: {
                        'Content-Type': 'text/html; charset=UTF-8',
                        'X-PyroSense-Offline-Snapshot': '1',
                    },
                });

                const request = new Request(path, { credentials: 'same-origin' });
                await cache.put(request, response.clone());
                await sharedCache.put(request, response.clone());

                if (!/^\/(?:auth|login|logout|register|forgot-password|reset-password)(?:\/.*)?$/i.test(path)) {
                    await sharedCache.put(new Request('/', { credentials: 'same-origin' }), response.clone());
                    await sharedCache.put(new Request('/pwa/start', { credentials: 'same-origin' }), response.clone());
                    await sharedCache.put(new Request('/pwa/quick-report', { credentials: 'same-origin' }), response.clone());
                }
            } catch (error) {
                console.warn('[PyroSense PWA] Failed to cache offline page snapshot:', error);
            }
        }

        function readPageSnapshot(key = null) {
            const snapshots = getPageSnapshots();
            return snapshots[key || window.location.pathname] || null;
        }

        function registerPageSnapshot(config = {}) {
            const key = config.key || window.location.pathname;
            const snapshot = readPageSnapshot(key);
            rememberOfflineReturnRoute();

            if (window.navigator.onLine) {
                renderPageSnapshotBadge(snapshot, { forceVisible: false });
                const updated = updatePageSnapshot({
                    key,
                    label: config.label || document.title || 'Page'
                });
                return updated;
            }

            renderPageSnapshotBadge(snapshot, {
                forceVisible: true,
                title: config.offlineTitle || `${config.label || document.title || 'Page'} offline snapshot`,
                autoHideMs: SNAPSHOT_BADGE_AUTO_HIDE_MS
            });

            return snapshot;
        }

        function showConnectionState(title, message, icon) {
            if (!pill || !pillIcon || !pillTitle || !pillText) {
                return;
            }

            pillTitle.textContent = title;
            pillText.textContent = message;
            pillIcon.textContent = icon;
            pill.classList.add('is-visible');
            window.clearTimeout(showConnectionState.timer);
        }

        function hideConnectionState() {
            if (!pill) {
                return;
            }

            pill.classList.remove('is-visible');
            window.clearTimeout(showConnectionState.timer);
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
                showConnectionState.timer = window.setTimeout(hideConnectionState, OFFLINE_PILL_AUTO_HIDE_MS);
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
                    showConnectionState.timer = window.setTimeout(hideConnectionState, 2200);
                }
                return;
            }

            hideConnectionState();
        }

        const STALE_LOCATION_CONFIRMATION_MS = 5 * 60 * 1000;

        function queuedReportLocation(item) {
            if (!item?.body || ![
                'fire-report',
                'barangay-one-tap-fire-report',
                'citizen-mobile-fire-report'
            ].includes(String(item.type || ''))) {
                return null;
            }

            try {
                const body = JSON.parse(item.body);
                const capturedAt = new Date(body.location_captured_at || body.occurred_at || item.created_at);
                const lat = Number(body.lat);
                const lng = Number(body.lng);
                const accuracy = Number(body.location_accuracy ?? body.accuracy);

                if (!Number.isFinite(lat) || !Number.isFinite(lng) || Number.isNaN(capturedAt.getTime())) {
                    return null;
                }

                return { lat, lng, accuracy, capturedAt };
            } catch (error) {
                return null;
            }
        }

        function queuedLocationDistanceMeters(first, second) {
            const earthRadius = 6371000;
            const radians = (degrees) => degrees * Math.PI / 180;
            const latitudeDelta = radians(second.lat - first.lat);
            const longitudeDelta = radians(second.lng - first.lng);
            const value = Math.sin(latitudeDelta / 2) ** 2
                + Math.cos(radians(first.lat)) * Math.cos(radians(second.lat))
                * Math.sin(longitudeDelta / 2) ** 2;

            return earthRadius * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
        }

        async function confirmStaleQueuedLocation(item) {
            const location = queuedReportLocation(item);
            if (!location) {
                return true;
            }

            const ageMs = Date.now() - location.capturedAt.getTime();
            const reconfirmedAt = item.meta?.location_reconfirmed_at
                ? new Date(item.meta.location_reconfirmed_at).getTime()
                : Number.NaN;
            const recentlyReconfirmed = Number.isFinite(reconfirmedAt)
                && Date.now() - reconfirmedAt < STALE_LOCATION_CONFIRMATION_MS;
            if (ageMs < STALE_LOCATION_CONFIRMATION_MS || recentlyReconfirmed) {
                return true;
            }

            const ageMinutes = Math.max(1, Math.round(ageMs / 60000));
            let comparison = '';

            try {
                if (typeof window.PyroLocation?.getReliableLocationFix === 'function') {
                    const current = await window.PyroLocation.getReliableLocationFix({ acceptBestAvailable: true });
                    const distance = queuedLocationDistanceMeters(location, current);
                    comparison = ` Your current GPS is about ${Math.round(distance)} m from the saved incident point.`;
                }
            } catch (error) {
                comparison = ' A fresh comparison location could not be obtained.';
            }

            const accuracyLabel = Number.isFinite(location.accuracy)
                ? ` with reported accuracy of ±${Math.round(location.accuracy)} m`
                : '';
            const confirmed = window.confirm(
                `This emergency report was captured about ${ageMinutes} minute${ageMinutes === 1 ? '' : 's'} ago${accuracyLabel}.${comparison}\n\n`
                + 'Select OK to send the original saved incident location. Select Cancel to keep it pending for review.'
            );

            if (confirmed) {
                item.meta = {
                    ...(item.meta || {}),
                    location_reconfirmed_at: new Date().toISOString(),
                };
            }

            return confirmed;
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
            let failed = 0;
            let lastFailureMessage = '';

            for (let index = 0; index < queue.length; index += 1) {
                const item = queue[index];
                try {
                    if (!await confirmStaleQueuedLocation(item)) {
                        remaining.push(item);
                        continue;
                    }

                    const syncHeaders = new Headers(item.headers || {});
                    syncHeaders.set('X-Pyro-Offline-Sync', '1');
                    syncHeaders.set('Accept', 'application/json');
                    syncHeaders.set('X-Requested-With', 'XMLHttpRequest');
                    const csrfToken = getCsrfToken();
                    if (csrfToken) {
                        syncHeaders.set('X-CSRF-TOKEN', csrfToken);
                    }

                    const response = await nativeFetch(item.url, {
                        method: item.method || 'POST',
                        headers: syncHeaders,
                        body: item.body || null,
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });

                    if (response.status === 419) {
                        failed += 1;
                        lastFailureMessage = 'Your secure session expired before the queued report could sync. Sign in again, then reconnect to retry.';
                        item.meta = {
                            ...(item.meta || {}),
                            last_sync_error: lastFailureMessage,
                            last_sync_status: response.status,
                            last_sync_attempted_at: new Date().toISOString(),
                        };
                        // Preserve the current item and every unprocessed item.
                        // Previously only the current report survived this break.
                        remaining.push(...queue.slice(index));
                        break;
                    }

                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        const validationMessage = payload?.errors
                            ? Object.values(payload.errors).flat().find(Boolean)
                            : null;
                        failed += 1;
                        lastFailureMessage = validationMessage || payload?.message || `Queued report sync failed with status ${response.status}.`;
                        item.meta = {
                            ...(item.meta || {}),
                            last_sync_error: lastFailureMessage,
                            last_sync_status: response.status,
                            last_sync_attempted_at: new Date().toISOString(),
                        };
                        remaining.push(item);
                        continue;
                    }

                    const payload = await response.json().catch(() => ({}));
                    synced += 1;

                    const syncEvent = new CustomEvent('pyrosense:report-synced', {
                        cancelable: true,
                        detail: {
                            queueItem: item,
                            response: payload
                        }
                    });
                    const showDefaultToast = window.dispatchEvent(syncEvent);
                    item.__pyroDefaultSyncToast = showDefaultToast;
                } catch (error) {
                    failed += 1;
                    lastFailureMessage = error?.message || 'Queued report sync failed. It will retry when the connection is stable.';
                    item.meta = {
                        ...(item.meta || {}),
                        last_sync_error: lastFailureMessage,
                        last_sync_attempted_at: new Date().toISOString(),
                    };
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

            if (synced > 0 && queue.some((item) => item.__pyroDefaultSyncToast)) {
                showPwaStatusToast(
                    synced === 1
                        ? 'Queued report synced successfully.'
                        : `${synced} queued reports synced successfully.`,
                    false,
                    3200
                );
            }

            if (failed > 0 && remaining.length > 0) {
                const failureEvent = new CustomEvent('pyrosense:report-sync-failed', {
                    cancelable: true,
                    detail: {
                        failed,
                        remaining: remaining.length,
                        message: lastFailureMessage,
                        queue: remaining.slice(),
                    },
                });
                const showDefaultFailureToast = window.dispatchEvent(failureEvent);

                if (showDefaultFailureToast) {
                    showPwaStatusToast(
                        lastFailureMessage || `${remaining.length} queued report${remaining.length === 1 ? '' : 's'} still pending sync.`,
                        true,
                        5200
                    );
                }
            }

            if (!remaining.length) {
                window.setTimeout(hideConnectionState, 2200);
            }

            return { synced, remaining: remaining.length };
        }

        function showPwaStatusToast(message, isError = false, duration = 3000) {
            if (!message) {
                return;
            }

            if (typeof window.showCitizenToast === 'function') {
                window.showCitizenToast(message, isError, duration);
                return;
            }

            if (typeof window.PyroNotify?.notify === 'function') {
                window.PyroNotify.notify(message, isError ? 'error' : 'success', duration);
                return;
            }

            showConnectionState(isError ? 'Sync failed' : 'Sync complete', message, isError ? '!' : '✓');
            window.setTimeout(hideConnectionState, duration);
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
            updatePageSnapshot,
            readPageSnapshot,
            registerPageSnapshot,
            retryFailedImages: retryFailedContentImages,
            getPendingReportCount: () => getQueue().length,
            getQueuedReports: () => getQueue().slice(),
            isOffline: () => !window.navigator.onLine
        });

        window.addEventListener('online', function () {
            updateConnectionState(true);
            syncQueuedReports();
            retryFailedContentImages();
        });

        window.addEventListener('offline', function () {
            updateConnectionState(true);
            renderPageSnapshotBadge(readPageSnapshot(), { forceVisible: true });
        });

        document.addEventListener('DOMContentLoaded', function () {
            rememberOfflineReturnRoute();
            ensureServiceWorker();
            initializeContentImageStates();
            updateConnectionState(false);
            if (window.navigator.onLine && getQueue().length) {
                syncQueuedReports();
            }
        });
    })();
</script>
