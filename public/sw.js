const CACHE_VERSION = 'pyrosense-pwa-v20260708-1';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const TILE_CACHE = `${CACHE_VERSION}-tiles`;
const OFFLINE_URL = '/offline.html';
const LANDING_URL = '/';

const NO_CACHE_HTML_PATTERNS = [
    /^\/auth(?:\/.*)?$/i,
    /^\/login$/i,
    /^\/logout$/i,
    /^\/firefighter(?:\/.*)?$/i,
    /^\/admin(?:\/.*)?$/i,
    /^\/chief(?:\/.*)?$/i
];

const APP_SHELL_URLS = [
    LANDING_URL,
    '/pwa/start',
    '/pwa/quick-report',
    '/manifest.json',
    OFFLINE_URL,
    '/logo.png',
    '/icon-72x72.png',
    '/icon-192x192.png',
    '/icon-512x512.png',
    '/pwa-screenshot-mobile.svg',
    '/pwa-screenshot-wide.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);

        await Promise.all(APP_SHELL_URLS.map(async (url) => {
            try {
                const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                if (response && (response.ok || response.type === 'opaque')) {
                    await cache.put(url, response.clone());
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

    if (isTileRequest) {
        event.respondWith(cacheFirst(request, TILE_CACHE));
        return;
    }

    if (!isSameOrigin) {
        return;
    }

    if (isStaticAsset) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    if (isNavigation) {
        if (isProtectedHtmlRoute || isAuthRequest) {
            event.respondWith(handleProtectedNavigationRequest(request));
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

async function handleProtectedNavigationRequest(request) {
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
    try {
        const response = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put(request, response.clone());
        return response;
    } catch (error) {
        const cachedPage = await caches.match(request, { ignoreSearch: true });
        if (cachedPage) {
            return cachedPage;
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
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
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
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
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
