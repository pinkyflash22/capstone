const CACHE_NAME = "laravel-pwa-1777032801";
const OFFLINE_URL = "/offline.html";

const FILES_TO_CACHE = [
    "/pwa/quick-report",
    OFFLINE_URL
];

self.addEventListener("install", (event) => {
    console.log("[Laravel PWA] Service Worker installing...");
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(FILES_TO_CACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener("activate", (event) => {
    console.log("[Laravel PWA] Service Worker activated.");
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

self.addEventListener("fetch", (event) => {
    const request = event.request;
    const requestUrl = new URL(request.url);
    const isSameOrigin = requestUrl.origin === self.location.origin;
    const isAuthRequest = isSameOrigin && (
        requestUrl.pathname === "/login" ||
        requestUrl.pathname === "/logout" ||
        requestUrl.pathname === "/auth" ||
        requestUrl.pathname.startsWith("/auth/") ||
        requestUrl.pathname === "/check-auth"
    );
    const isLiveApiRequest = isSameOrigin && (
        requestUrl.pathname.startsWith("/api/") ||
        requestUrl.pathname.startsWith("/firefighter/") ||
        requestUrl.pathname === "/markers" ||
        requestUrl.pathname === "/latest" ||
        requestUrl.pathname === "/active" ||
        requestUrl.pathname === "/locations" ||
        requestUrl.pathname === "/auto-assign-recent-unassigned" ||
        requestUrl.pathname.endsWith("/markers") ||
        requestUrl.pathname.endsWith("/latest") ||
        requestUrl.pathname.endsWith("/active") ||
        requestUrl.pathname.endsWith("/locations") ||
        requestUrl.pathname.endsWith("/auto-assign-recent-unassigned")
    );
    const isStaticAssetRequest = (
        request.destination === "style" ||
        request.destination === "script" ||
        request.destination === "image" ||
        request.destination === "font"
    );

    if (request.method !== "GET") {
        return;
    }

    if (isAuthRequest || isLiveApiRequest) {
        return;
    }

    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (isStaticAssetRequest) {
        event.respondWith(
            caches.match(request).then((cached) => {
                return cached || fetch(request).then((response) => {
                    if (!response || response.status !== 200) {
                        return response;
                    }

                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, response.clone());
                        return response;
                    });
                });
            })
        );
        return;
    }
});

self.addEventListener("sync", (event) => {
    if (event.tag === "laravel-pwa-sync") {
        event.waitUntil(syncRequests());
    }
});

async function syncRequests() {
    const db = await openDB();
    const tx = db.transaction("offline-requests", "readonly");
    const store = tx.objectStore("offline-requests");
    const requests = await getAllRequests(store);

    for (const req of requests) {
        try {
            const response = await fetch(req.url, {
                method: req.method,
                headers: req.headers,
                body: req.body
            });

            if (response.ok) {
                const deleteTx = db.transaction("offline-requests", "readwrite");
                deleteTx.objectStore("offline-requests").delete(req.id);
            }
        } catch (err) {
            console.error("[Laravel PWA] Sync failed for:", req.url, err);
        }
    }
}

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open("laravel-pwa-sync", 1);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function getAllRequests(store) {
    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}
