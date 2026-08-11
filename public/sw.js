const CACHE_VERSION = 'pyrosense-pwa-v20260811-1';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const TILE_CACHE = `${CACHE_VERSION}-tiles`;
const OFFLINE_URL = '/offline.html';
const LANDING_URL = '/';
const PWA_START_URL = '/pwa/start';
const PWA_QUICK_REPORT_URL = '/pwa/quick-report';

const NO_CACHE_HTML_PATTERNS = [
    /^\/auth(?:\/.*)?$/i,
    /^\/login$/i,
    /^\/logout$/i
];

const APP_SHELL_URLS = [
    '/manifest.json',
    OFFLINE_URL,
    '/logo.png',
    '/icon-72x72.png',
    '/icon-192x192.png',
    '/icon-512x512.png',
    '/pwa-screenshot-mobile.svg',
    '/pwa-screenshot-wide.svg'
];

const MAX_CACHE_ENTRIES = {
    [SHELL_CACHE]: 20,
    [STATIC_CACHE]: 120,
    [DYNAMIC_CACHE]: 40,
    [TILE_CACHE]: 80,
};

const MAX_CACHEABLE_BYTES = 1024 * 1024;
const quotaWarnings = new Set();

function isQuotaExceededError(error) {
    return error?.name === 'QuotaExceededError'
        || error?.message?.includes?.('Quota exceeded')
        || error?.message?.includes?.('quota');
}

function getMaxEntries(cacheName) {
    return MAX_CACHE_ENTRIES[cacheName] || 40;
}

function shouldSkipCacheWrite(response) {
    const contentLength = Number(response?.headers?.get?.('content-length') || 0);
    return Number.isFinite(contentLength) && contentLength > MAX_CACHEABLE_BYTES;
}

async function trimCache(cacheName, maxEntries = getMaxEntries(cacheName)) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    if (keys.length <= maxEntries) {
        return;
    }

    const overflow = keys.length - maxEntries;
    await Promise.all(keys.slice(0, overflow).map((request) => cache.delete(request)));
}

async function reclaimQuota(targetCacheName) {
    if (targetCacheName !== TILE_CACHE) {
        await trimCache(DYNAMIC_CACHE, Math.min(18, getMaxEntries(DYNAMIC_CACHE)));
    }

    await trimCache(TILE_CACHE, Math.min(30, getMaxEntries(TILE_CACHE)));

    if (targetCacheName === STATIC_CACHE) {
        await trimCache(STATIC_CACHE, Math.min(70, getMaxEntries(STATIC_CACHE)));
    }
}

async function safeCachePut(cacheName, request, response) {
    if (!response || !(response.ok || response.type === 'opaque') || shouldSkipCacheWrite(response)) {
        return;
    }

    const cache = await caches.open(cacheName);
    const requestLabel = typeof request === 'string'
        ? request
        : (request?.url || '[unknown-request]');

    try {
        await cache.put(request, response.clone());
        await trimCache(cacheName);
    } catch (error) {
        if (!isQuotaExceededError(error)) {
            console.warn('[PyroSense PWA] Cache write skipped:', cacheName, requestLabel, error);
            return;
        }

        const quotaWarningKey = `${cacheName}:${requestLabel}`;
        if (!quotaWarnings.has(quotaWarningKey)) {
            console.warn('[PyroSense PWA] Storage quota reached, trimming cache:', cacheName, requestLabel);
            quotaWarnings.add(quotaWarningKey);
        }

        try {
            await reclaimQuota(cacheName);
            await cache.put(request, response.clone());
            await trimCache(cacheName);
        } catch (retryError) {
            if (!quotaWarnings.has(`${quotaWarningKey}:retry`)) {
                console.warn('[PyroSense PWA] Cache retry skipped after quota trim:', cacheName, requestLabel, retryError);
                quotaWarnings.add(`${quotaWarningKey}:retry`);
            }
        }
    }
}

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);

        await Promise.all(APP_SHELL_URLS.map(async (url) => {
            try {
                const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                if (response && (response.ok || response.type === 'opaque')) {
                    await safeCachePut(SHELL_CACHE, url, response);
                }
            } catch (error) {
                console.warn('[PyroSense PWA] Failed to precache:', url, error);
            }
        }));

        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.map((key) => {
            if (![
                SHELL_CACHE,
                STATIC_CACHE,
                DYNAMIC_CACHE,
                TILE_CACHE
            ].includes(key)) {
                return caches.delete(key);
            }
            return Promise.resolve();
        }));

        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;
    const isNavigation = request.mode === 'navigate';
    const isProtectedHtmlRoute = isSameOrigin && NO_CACHE_HTML_PATTERNS.some((pattern) => pattern.test(url.pathname));
    const isTileRequest = url.hostname.includes('tile.openstreetmap.org')
        || url.pathname.includes('/MapServer/tile/');
    const isStaticAsset = request.destination === 'style'
        || request.destination === 'script'
        || request.destination === 'image'
        || request.destination === 'font'
        || url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/assets/')
        || url.pathname.startsWith('/storage/')
        || url.pathname.endsWith('.css')
        || url.pathname.endsWith('.js')
        || url.pathname.endsWith('.png')
        || url.pathname.endsWith('.jpg')
        || url.pathname.endsWith('.jpeg')
        || url.pathname.endsWith('.svg')
        || url.pathname.endsWith('.webp')
        || url.pathname.endsWith('.ico')
        || url.pathname.endsWith('.woff2');
    const isApiRequest = isSameOrigin && (
        url.pathname.startsWith('/api/')
        || url.pathname.startsWith('/citizen/dashboard-data')
        || url.pathname.startsWith('/firefighter/')
        || url.pathname.startsWith('/admin/')
        || url.pathname.startsWith('/chief/')
        || url.pathname.startsWith('/barangay/')
    );
    const isAuthRequest = isSameOrigin && (
        url.pathname === '/auth'
        || url.pathname === '/login'
        || url.pathname === '/logout'
        || url.pathname.startsWith('/auth/')
        || url.pathname === '/check-auth'
    );

    if (isStaticAsset) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    if (isTileRequest) {
        event.respondWith(cacheFirst(request, TILE_CACHE));
        return;
    }

    if (!isSameOrigin) {
        return;
    }

    if (isNavigation) {
        if (isProtectedHtmlRoute || isAuthRequest) {
            event.respondWith(handleNoCacheNavigationRequest(request));
            return;
        }

        event.respondWith(handleNavigationRequest(request));
        return;
    }

    if (isApiRequest && !isAuthRequest) {
        event.respondWith(networkFirst(request, DYNAMIC_CACHE));
        return;
    }

    if (isSameOrigin) {
        if (isProtectedHtmlRoute || isAuthRequest) {
            event.respondWith(fetch(request));
            return;
        }

        event.respondWith(networkFirst(request, DYNAMIC_CACHE));
    }
});

async function handleNoCacheNavigationRequest(request) {
    try {
        return await fetch(request, { cache: 'no-store', credentials: 'same-origin' });
    } catch (error) {
        const offlinePage = await caches.match(OFFLINE_URL, { ignoreSearch: true });
        if (offlinePage) {
            return offlinePage;
        }

        return new Response('Offline', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

async function handleNavigationRequest(request) {
    const requestUrl = new URL(request.url);

    try {
        const response = await fetch(request, { credentials: 'same-origin' });
        if (response && response.ok) {
            await safeCachePut(DYNAMIC_CACHE, request, response);
        }
        return response;
    } catch (error) {
        const cachedPage = await caches.match(request, { ignoreSearch: true });
        if (cachedPage) {
            return cachedPage;
        }

        if (
            requestUrl.pathname === PWA_START_URL
            || requestUrl.pathname === PWA_QUICK_REPORT_URL
            || (requestUrl.pathname === LANDING_URL && requestUrl.searchParams.get('source') === 'installed')
        ) {
            const cachedStartPage = await caches.match(PWA_START_URL, { ignoreSearch: true })
                || await caches.match(PWA_QUICK_REPORT_URL, { ignoreSearch: true })
                || await caches.match(LANDING_URL, { ignoreSearch: true });

            if (cachedStartPage) {
                return cachedStartPage;
            }
        }

        const cachedLanding = await caches.match(LANDING_URL, { ignoreSearch: true });
        if (cachedLanding) {
            return cachedLanding;
        }

        const offlinePage = await caches.match(OFFLINE_URL, { ignoreSearch: true });
        if (offlinePage) {
            return offlinePage;
        }

        return new Response('Offline', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response && (response.ok || response.type === 'opaque')) {
            await safeCachePut(cacheName, request, response);
        }
        return response;
    } catch (error) {
        if (request.destination === 'image') {
            const fallbackImage = await caches.match('/logo.png') || await caches.match('/icon-192x192.png');
            if (fallbackImage) {
                return fallbackImage;
            }
        }

        throw error;
    }
}

async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response && response.ok) {
            await safeCachePut(cacheName, request, response);
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request, { ignoreSearch: true });
        if (cached) {
            return cached;
        }

        if (request.mode === 'navigate') {
            const cachedLanding = await caches.match(LANDING_URL, { ignoreSearch: true });
            if (cachedLanding) {
                return cachedLanding;
            }

            const offlinePage = await caches.match(OFFLINE_URL, { ignoreSearch: true });
            if (offlinePage) {
                return offlinePage;
            }
        }

        return new Response(JSON.stringify({
            success: false,
            offline: true,
            message: 'Offline fallback unavailable'
        }), {
            status: 503,
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
}
