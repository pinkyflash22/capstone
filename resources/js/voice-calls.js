const voiceConfig = window.PyroSenseVoiceCall || {};
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const role = String(voiceConfig.role || '').toLowerCase();
const isResponder = ['station admin', 'chief', 'chief station admin', 'provincial admin'].includes(role);
const isCaller = ['citizen', 'anonymous', 'barangay', 'barangay captain', 'firefighter'].includes(role);
const isMobileHubPage = document.body?.classList.contains('mobile-hub-page');

let incomingPollTimer = null;
let callerPollTimer = null;
let callbackPollTimer = null;
let durationTimer = null;
let activeCall = null;
let activeAgora = null;
let agoraClient = null;
let localAudioTrack = null;
let isMuted = false;
let joinedCallId = null;
let ringtoneState = null;
let outgoingRingtoneState = null;
let knownIncomingIds = new Set();
let knownCallsById = new Map();
let incomingPollFailureCount = 0;
let activeCallerTrigger = null;
let callerVoicePrimeAttempted = false;
let callerHasMicrophoneAccess = false;
let agoraJoinState = 'idle';
let incomingPollInFlight = null;
let voiceSessionBootstrapPromise = null;
let knownMissedIds = new Set();
let knownCallbackId = null;
let isSpeakerEnabled = true;
const remoteAudioTracks = new Map();
const displayedCallNotifications = new Map();
const responderSessions = new Map();
let activeResponderCalls = new Map();
let responderPanelOpen = false;
let responderPanelTotal = 0;
let responderCallSearch = '';
let responderCallFilter = 'all';
let responderCallSort = 'newest';
let responderHistoryLimit = 8;
let latestResponderPayload = null;
let agoraSdkPromise = null;
let agoraSdkLoadStartedAt = null;
let agoraSdkLoadMetrics = null;
let pendingNotificationCallId = null;

const routes = voiceConfig.routes || {};
const fallbackStations = Array.isArray(voiceConfig.fallbackStations) ? voiceConfig.fallbackStations : [];
const pollMs = Number(voiceConfig.pollIntervalMs || 2000);
const AGORA_JOIN_TIMEOUT_MS = 15000;
const REQUEST_TIMEOUT_MS = 12000;

function measureAgoraSdkLoad(completedAt) {
    const resource = performance.getEntriesByType?.('resource')
        ?.filter((entry) => entry.name.includes('/assets/AgoraRTC_N-') || entry.name.includes('AgoraRTC_N-production'))
        .at(-1);
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const metrics = {
        duration_ms: Math.max(0, Math.round(completedAt - (agoraSdkLoadStartedAt || completedAt))),
        transfer_bytes: Number(resource?.transferSize || 0),
        compressed_bytes: Number(resource?.encodedBodySize || 0),
        decoded_bytes: Number(resource?.decodedBodySize || 0),
        connection_type: connection?.effectiveType || null,
        measured_at: new Date().toISOString(),
    };

    agoraSdkLoadMetrics = metrics;
    window.PyroSenseVoiceMetrics = { ...(window.PyroSenseVoiceMetrics || {}), agora_sdk: metrics };
    window.dispatchEvent(new CustomEvent('pyro:agora-load-measured', { detail: metrics }));
    return metrics;
}

function updateAgoraLoadStatus(elapsedMs = 0) {
    const elapsedSeconds = Math.max(1, Math.ceil(elapsedMs / 1000));
    const message = elapsedMs >= 3000
        ? `Still loading secure calling… ${elapsedSeconds}s`
        : elapsedMs >= 1000
            ? `Preparing secure call… ${elapsedSeconds}s`
            : 'Preparing secure call…';

    document.querySelectorAll('[data-pyro-secure-call-state]').forEach((node) => {
        node.hidden = false;
        node.setAttribute('role', 'status');
        node.setAttribute('aria-live', 'polite');
        node.textContent = message;
    });
}

async function loadAgoraRtc() {
    if (!agoraSdkPromise) {
        agoraSdkLoadStartedAt = performance.now();
        agoraSdkPromise = import('agora-rtc-sdk-ng/esm')
            .then(({ createClient, createMicrophoneAudioTrack }) => {
                measureAgoraSdkLoad(performance.now());
                return { createClient, createMicrophoneAudioTrack };
            })
            .catch((error) => {
                agoraSdkPromise = null;
                agoraSdkLoadStartedAt = null;
                throw error;
            });
    }

    return agoraSdkPromise;
}

function prepareAgoraForCallInterface(reason = 'call-ui') {
    if (!voiceConfig.configured || !navigator.onLine) return Promise.resolve(null);

    document.documentElement.dataset.pyroAgoraLoad = 'loading';
    const progressStartedAt = performance.now();
    updateAgoraLoadStatus();
    const progressTimer = window.setInterval(() => {
        updateAgoraLoadStatus(performance.now() - progressStartedAt);
    }, 500);
    return loadAgoraRtc()
        .then((sdk) => {
            window.clearInterval(progressTimer);
            document.documentElement.dataset.pyroAgoraLoad = 'ready';
            document.querySelectorAll('[data-pyro-secure-call-state]').forEach((node) => {
                node.hidden = true;
                node.textContent = 'Secure calling ready';
            });
            window.dispatchEvent(new CustomEvent('pyro:agora-ready', {
                detail: { reason, metrics: agoraSdkLoadMetrics },
            }));
            return sdk;
        })
        .catch((error) => {
            window.clearInterval(progressTimer);
            document.documentElement.dataset.pyroAgoraLoad = 'failed';
            document.querySelectorAll('[data-pyro-secure-call-state]').forEach((node) => {
                node.hidden = false;
                node.textContent = 'Phone fallback available';
            });
            console.warn('Agora could not be prepared for the calling interface:', error);
            return null;
        });
}

function routeFor(name, callId = '') {
    return String(routes[name] || '').replace('__CALL__', callId);
}

function emergencyFallbackStation() {
    const callable = fallbackStations.filter((station) => String(station?.phone || '').replace(/\D+/g, ''));
    if (!callable.length) return null;

    const location = currentCallerLocationPayload();
    const latitude = Number(location.caller_latitude);
    const longitude = Number(location.caller_longitude);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return callable[0];

    const radians = (degrees) => degrees * (Math.PI / 180);
    const distance = (station) => {
        const stationLat = Number(station?.lat);
        const stationLng = Number(station?.lng);
        if (!Number.isFinite(stationLat) || !Number.isFinite(stationLng)) return Number.POSITIVE_INFINITY;
        const latDelta = radians(stationLat - latitude);
        const lngDelta = radians(stationLng - longitude);
        const value = Math.sin(latDelta / 2) ** 2
            + Math.cos(radians(latitude)) * Math.cos(radians(stationLat)) * Math.sin(lngDelta / 2) ** 2;
        return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
    };

    return callable.reduce((nearest, station) => distance(station) < distance(nearest) ? station : nearest, callable[0]);
}

function openEmergencyTelephoneFallback(reason = '') {
    const station = emergencyFallbackStation();
    const stationPhone = String(station?.phone || '').replace(/[^\d+]/g, '');
    if (stationPhone) {
        toast(`${reason ? `${reason} ` : ''}Opening ${station.name || 'the nearest station'} telephone line.`, 'warning');
        window.location.assign(`tel:${stationPhone}`);
        return true;
    }

    if (['citizen', 'anonymous'].includes(role)) {
        toast(`${reason ? `${reason} ` : ''}No station telephone is available. Opening 911.`, 'warning');
        window.location.assign('tel:911');
        return true;
    }

    toast('Internet calling is unavailable. Contact your station dispatcher.', 'error');
    return false;
}

async function ensureCitizenVoiceSession() {
    if (!isCaller || role !== 'anonymous' || !routes.anonymousSession) {
        return true;
    }

    if (voiceSessionBootstrapPromise) {
        return voiceSessionBootstrapPromise;
    }

    voiceSessionBootstrapPromise = jsonFetch(routes.anonymousSession, {
        method: 'POST',
        timeoutMs: 10000,
        timeoutMessage: 'Anonymous voice session setup timed out.',
    }).then((payload) => {
        voiceConfig.userId = payload.user_id || voiceConfig.userId;
        if (payload.csrf_token) {
            csrfToken = payload.csrf_token;
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrfToken);
        }
        return true;
    }).catch((error) => {
        voiceSessionBootstrapPromise = null;
        throw error;
    });

    return voiceSessionBootstrapPromise;
}

function toast(message, type = 'info') {
    window.PyroNotify?.notify(message, { type, duration: 4200 });
}

function setCallerTriggerState(trigger, state = 'idle') {
    if (!trigger) {
        return;
    }

    const labelNode = trigger.querySelector('.mobile-hub-btn__copy strong');
    const originalLabel = trigger.dataset.originalVoiceLabel
        || labelNode?.textContent
        || trigger.textContent?.trim()
        || 'Call BFP';

    trigger.dataset.originalVoiceLabel = originalLabel;
    trigger.classList.toggle('is-loading', state === 'preparing' || state === 'calling');
    trigger.classList.toggle('is-active-call', state === 'connected' || state === 'ringing');
    trigger.setAttribute('aria-busy', ['preparing', 'calling'].includes(state) ? 'true' : 'false');
    trigger.disabled = ['preparing', 'calling', 'connected', 'ringing'].includes(state);

    if (labelNode) {
        if (state === 'preparing') {
            labelNode.textContent = 'Preparing secure call…';
        } else if (state === 'calling') {
            labelNode.textContent = 'Calling BFP...';
        } else if (state === 'connected' || state === 'ringing') {
            labelNode.textContent = 'BFP Call Active';
        } else {
            labelNode.textContent = originalLabel;
        }
    }
}

function setAgoraJoinState(state = 'idle') {
    agoraJoinState = state;

    if (activeCall && isCaller) {
        renderCallerDock(activeCall);
    }
}

async function jsonFetch(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), options.timeoutMs || REQUEST_TIMEOUT_MS);
    let response;

    try {
        response = await fetch(url, {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {}),
            },
            body: options.body ? JSON.stringify(options.body) : undefined,
            signal: controller.signal,
        });
    } catch (error) {
        clearTimeout(timer);
        if (error?.name === 'AbortError') {
            throw new Error(options.timeoutMessage || 'Request timed out. Please try again.');
        }
        throw error;
    }

    clearTimeout(timer);

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(payload.message || `Request failed with status ${response.status}.`);
    }
    return payload;
}

function withTimeout(promise, timeoutMs, message) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error(message)), timeoutMs);
        promise
            .then((value) => {
                clearTimeout(timer);
                resolve(value);
            })
            .catch((error) => {
                clearTimeout(timer);
                reject(error);
            });
    });
}

async function primeCallerVoiceAccess() {
    if (callerVoicePrimeAttempted || !isCaller || !navigator.mediaDevices?.getUserMedia) {
        return;
    }

    callerVoicePrimeAttempted = true;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        callerHasMicrophoneAccess = true;
        stream.getTracks().forEach((track) => track.stop());
    } catch (error) {
        callerHasMicrophoneAccess = false;
        console.warn('Caller microphone priming failed:', error);
    }
}

function initials(name) {
    return String(name || 'Citizen')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('') || 'C';
}

function formatSeconds(seconds) {
    const safe = Math.max(0, Number(seconds || 0));
    const minutes = Math.floor(safe / 60).toString().padStart(2, '0');
    const remainder = Math.floor(safe % 60).toString().padStart(2, '0');
    return `${minutes}:${remainder}`;
}

function callAge(call) {
    const started = call?.answered_at || call?.created_at;
    if (!started) return call?.ringing_seconds || 0;
    return Math.max(0, Math.floor((Date.now() - new Date(started).getTime()) / 1000));
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function finiteNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function normalizeLocation(source) {
    if (!source || typeof source !== 'object') return null;
    const lat = finiteNumber(source.lat ?? source.latitude ?? source.currentLatitude);
    const lng = finiteNumber(source.lng ?? source.lon ?? source.longitude ?? source.currentLongitude);
    const accuracy = finiteNumber(source.accuracy ?? source.radiusMeters ?? source.caller_accuracy);
    const timestampValue = source.timestamp ?? source.capturedAt ?? source.lastUpdated;
    const parsedTimestamp = timestampValue instanceof Date
        ? timestampValue.getTime()
        : (typeof timestampValue === 'string' ? new Date(timestampValue).getTime() : Number(timestampValue));
    if (lat === null || lng === null || lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return {
        caller_latitude: lat,
        caller_longitude: lng,
        caller_accuracy: accuracy !== null && accuracy > 0 ? Math.round(accuracy) : null,
        caller_location_label: source.label || source.address || source.display || null,
        _captured_at: Number.isFinite(parsedTimestamp) ? parsedTimestamp : null,
    };
}

function locationPayloadFields(location) {
    if (!location) return {};
    return {
        caller_latitude: location.caller_latitude,
        caller_longitude: location.caller_longitude,
        caller_accuracy: location.caller_accuracy,
        caller_location_label: location.caller_location_label,
    };
}

function isReliableCallerLocation(location, maxAgeMs = 30000, maxAccuracyMeters = 150) {
    return Boolean(location
        && Number.isFinite(location._captured_at)
        && Date.now() - location._captured_at >= 0
        && Date.now() - location._captured_at <= maxAgeMs
        && Number.isFinite(location.caller_accuracy)
        && location.caller_accuracy > 0
        && location.caller_accuracy <= maxAccuracyMeters);
}

function currentCallerLocationPayload(maxAgeMs = 30000, maxAccuracyMeters = 150) {
    const globals = [
        window.userCoords,
        window.PyroSenseCurrentLocation,
        window.currentUserLocation,
        window.citizenLocation,
        window.currentLocation,
    ];

    for (const source of globals) {
        const normalized = normalizeLocation(source);
        if (isReliableCallerLocation(normalized, maxAgeMs, maxAccuracyMeters)) return locationPayloadFields(normalized);
    }

    for (const key of ['pyrosense:user-location', 'pyrosense:current-location', 'citizen:user-location', 'citizen:current-location']) {
        try {
            const normalized = normalizeLocation(JSON.parse(localStorage.getItem(key) || 'null'));
            if (isReliableCallerLocation(normalized, maxAgeMs, maxAccuracyMeters)) return locationPayloadFields(normalized);
        } catch {}
    }

    try {
        const rawLastKnownCoords = JSON.parse(localStorage.getItem('pyrosense:lastKnownCoords') || 'null');
        const normalizedLastKnownCoords = normalizeLocation(rawLastKnownCoords);
        if (isReliableCallerLocation(normalizedLastKnownCoords, maxAgeMs, maxAccuracyMeters)) {
            return locationPayloadFields(normalizedLastKnownCoords);
        }
    } catch {}

    return {};
}

function getFreshCallerLocationPayload() {
    const recentLocation = currentCallerLocationPayload(15000, 150);
    if (recentLocation.caller_latitude !== undefined || !navigator.geolocation) {
        return Promise.resolve(recentLocation);
    }

    return new Promise((resolve) => {
        let settled = false;
        let bestFix = null;
        let watchId = null;
        const finish = () => {
            if (settled) return;
            settled = true;
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
            clearTimeout(timeoutId);
            resolve(bestFix ? locationPayloadFields(bestFix) : {});
        };
        const timeoutId = window.setTimeout(finish, 6000);

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                const normalized = normalizeLocation({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp || Date.now(),
                });
                if (!isReliableCallerLocation(normalized, 30000, 150)) return;
                if (!bestFix || normalized.caller_accuracy < bestFix.caller_accuracy) bestFix = normalized;
                if (normalized.caller_accuracy <= 50) finish();
            },
            () => finish(),
            { enableHighAccuracy: true, timeout: 5500, maximumAge: 0 }
        );
    }).then((location) => {
        if (location.caller_latitude === undefined) return {};
        const stored = {
            lat: location.caller_latitude,
            lng: location.caller_longitude,
            accuracy: location.caller_accuracy,
            timestamp: Date.now(),
        };
        try {
            localStorage.setItem('pyrosense:lastKnownCoords', JSON.stringify(stored));
        } catch {}
        window.userCoords = {
            ...(window.userCoords || {}),
            currentLatitude: stored.lat,
            currentLongitude: stored.lng,
            accuracy: stored.accuracy,
            timestamp: stored.timestamp,
            ready: true,
        };
        return location;
    });
}

function callLocation(call) {
    return call?.caller_location || {};
}

function callerLocationDisplay(location) {
    if (!location?.has_location) return 'Location unavailable';
    const accuracy = finiteNumber(location.accuracy);
    return accuracy && accuracy > 0
        ? `${location.display} (within ±${Math.round(accuracy)} m)`
        : location.display;
}

function candidateMaps() {
    return [
        window.map,
        window.map,
        window.userMap,
        window.adminMap,
        window.chiefMap,
        window.stationAdminMap,
        window.pyroMap,
        window.dashboardMap,
    ].filter((map) => map && typeof map.setView === 'function');
}

function viewCallerLocation(call) {
    const location = callLocation(call);
    if (!location.has_location) {
        toast('Caller location is unavailable.', 'error');
        return;
    }

    const latLng = [Number(location.lat), Number(location.lng)];
    if (typeof window.focusMapOnExternalLocation === 'function') {
        const focused = window.focusMapOnExternalLocation(location.lat, location.lng, {
            label: location.label || 'Caller location',
            description: call?.caller?.name ? `${call.caller.name} voice call` : 'Voice call origin',
            zoom: 16,
            accuracy: location.accuracy,
        });

        if (focused) {
            window.dispatchEvent(new CustomEvent('pyro:voice-caller-location', { detail: { call, latLng } }));
            toast(`Caller location centered${location.accuracy ? ` within ±${Math.round(Number(location.accuracy))} m` : ''}.`);
            return;
        }
    }

    const targetMap = candidateMaps()[0];
    if (targetMap) {
        targetMap.setView(latLng, Math.max(targetMap.getZoom?.() || 15, 16), { animate: true });
    }

    window.dispatchEvent(new CustomEvent('pyro:voice-caller-location', { detail: { call, latLng } }));
    toast(`Caller location centered${location.accuracy ? ` within ±${Math.round(Number(location.accuracy))} m` : ''}.`);
}

function refreshIncidentMarkersFromVoice(payload) {
    window.dispatchEvent(new CustomEvent('pyro:voice-call-incident-created', { detail: payload }));

    if (payload?.incident && typeof window.mergeIncidentIntoClientState === 'function') {
        try {
            window.mergeIncidentIntoClientState(payload.incident);
        } catch (error) {
            console.warn('Unable to merge voice-call incident into client state:', error);
        }
    }

    if (typeof window.refreshChiefIncidentNotifications === 'function') {
        window.refreshChiefIncidentNotifications().catch?.((error) => {
            console.warn('Voice-call incident refresh failed:', error);
        });
    }

    const refresh = window.refreshMapMarkers || window.refreshMarkers || window.loadMarkers || window.reloadMapMarkers;
    if (typeof refresh === 'function') {
        Promise.resolve(refresh({ force: true })).catch((error) => {
            console.warn('Voice-call map refresh failed:', error);
        });
    }
}

function ensureIncomingPanel() {
    let panel = document.querySelector('[data-pyro-voice-panel]');
    if (panel) {
        ensureResponderLauncher();
        return panel;
    }

    panel = document.createElement('section');
    panel.className = 'pyro-voice-panel';
    panel.dataset.pyroVoicePanel = 'true';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-label', 'Dispatch call center');
    panel.innerHTML = `
        <div class="pyro-voice-panel__header">
            <div class="pyro-voice-panel__heading">
                <span class="pyro-voice-panel__live-dot" aria-hidden="true"></span>
                <div>
                    <p class="pyro-voice-panel__eyebrow">BFP communications</p>
                    <h2 class="pyro-voice-panel__title">Dispatch call center</h2>
                </div>
            </div>
            <div class="pyro-voice-panel__summary">
                <span class="pyro-voice-badge" data-pyro-secure-call-state hidden>Preparing secure call…</span>
                <span class="pyro-voice-badge" data-pyro-voice-active-count>0 active</span>
                <span class="pyro-voice-badge" data-pyro-voice-count>0 total</span>
                <button type="button" class="pyro-voice-panel__close" data-pyro-voice-panel-close aria-label="Close dispatch call center"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
        <div class="pyro-voice-panel__tools" aria-label="Call list controls">
            <label class="pyro-voice-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" data-pyro-voice-search placeholder="Search caller, number, or location" autocomplete="off" aria-label="Search calls">
            </label>
            <div class="pyro-voice-filter-tabs" role="group" aria-label="Filter calls by status">
                <button type="button" class="is-active" data-pyro-voice-filter="all" aria-pressed="true">All <span data-pyro-voice-filter-count="all">0</span></button>
                <button type="button" data-pyro-voice-filter="active" aria-pressed="false">Active <span data-pyro-voice-filter-count="active">0</span></button>
                <button type="button" data-pyro-voice-filter="missed" aria-pressed="false">Missed <span data-pyro-voice-filter-count="missed">0</span></button>
                <button type="button" data-pyro-voice-filter="completed" aria-pressed="false">Completed <span data-pyro-voice-filter-count="completed">0</span></button>
            </div>
            <div class="pyro-voice-panel__selects pyro-voice-panel__selects--sort">
                <label>
                    <span class="sr-only">Sort calls</span>
                    <select data-pyro-voice-sort aria-label="Sort calls">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="caller">Caller A–Z</option>
                    </select>
                </label>
            </div>
            <span class="pyro-voice-panel__results" data-pyro-voice-results aria-live="polite"></span>
        </div>
        <div class="pyro-voice-panel__body" data-pyro-voice-list></div>
    `;
    document.body.appendChild(panel);
    panel.addEventListener('click', handleIncomingClick);
    enableResponderPanelSwipe(panel);
    panel.querySelector('[data-pyro-voice-search]')?.addEventListener('input', (event) => {
        responderCallSearch = event.target.value.trim().toLowerCase();
        if (latestResponderPayload) renderIncoming(latestResponderPayload);
    });
    panel.querySelector('.pyro-voice-filter-tabs')?.addEventListener('click', (event) => {
        const filter = event.target.closest('[data-pyro-voice-filter]');
        if (!filter) return;
        responderCallFilter = filter.dataset.pyroVoiceFilter || 'all';
        panel.querySelectorAll('[data-pyro-voice-filter]').forEach((button) => {
            const active = button === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (latestResponderPayload) renderIncoming(latestResponderPayload);
    });
    panel.querySelector('[data-pyro-voice-sort]')?.addEventListener('change', (event) => {
        responderCallSort = event.target.value;
        if (latestResponderPayload) renderIncoming(latestResponderPayload);
    });
    ensureResponderLauncher();
    return panel;
}

function ensureResponderLauncher() {
    let launcher = document.querySelector('[data-pyro-voice-launcher]');
    if (launcher) return launcher;
    launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'pyro-voice-launcher';
    launcher.dataset.pyroVoiceLauncher = 'true';
    launcher.setAttribute('aria-label', 'Open dispatch call center');
    launcher.setAttribute('aria-expanded', 'false');
    launcher.innerHTML = `<span class="pyro-voice-launcher__icon"><i class="fa-solid fa-headset"></i></span><span class="pyro-voice-launcher__copy"><strong>Calls</strong><small>Dispatch</small></span><span class="pyro-voice-launcher__badge" data-pyro-voice-launcher-count hidden>0</span>`;

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'pyro-voice-panel-backdrop';
    backdrop.dataset.pyroVoicePanelBackdrop = 'true';
    backdrop.hidden = true;
    backdrop.setAttribute('aria-label', 'Close dispatch call center');
    document.body.append(backdrop, launcher);
    launcher.addEventListener('click', () => setResponderPanelOpen(!responderPanelOpen));
    backdrop.addEventListener('click', () => setResponderPanelOpen(false));
    return launcher;
}

function enableResponderPanelSwipe(panel) {
    const header = panel.querySelector('.pyro-voice-panel__header');
    if (!header || header.dataset.pyroSwipeReady) return;
    header.dataset.pyroSwipeReady = 'true';

    let startY = null;
    let currentY = null;

    header.addEventListener('touchstart', (event) => {
        if (event.target.closest('button') || event.touches.length !== 1) return;
        startY = event.touches[0].clientY;
        currentY = startY;
        panel.classList.add('is-dragging');
    }, { passive: true });

    header.addEventListener('touchmove', (event) => {
        if (startY === null || event.touches.length !== 1) return;
        currentY = event.touches[0].clientY;
        const distance = Math.max(0, currentY - startY);
        panel.style.transform = `translateY(${Math.min(distance, 180)}px)`;
        if (distance > 0) event.preventDefault();
    }, { passive: false });

    const finishSwipe = () => {
        if (startY === null) return;
        const distance = Math.max(0, (currentY ?? startY) - startY);
        panel.classList.remove('is-dragging');
        panel.style.transform = '';
        startY = null;
        currentY = null;
        if (distance >= 80) setResponderPanelOpen(false);
    };

    header.addEventListener('touchend', finishSwipe, { passive: true });
    header.addEventListener('touchcancel', finishSwipe, { passive: true });
}

async function closeMissedCallNotification(callId) {
    const displayedNotification = displayedCallNotifications.get(Number(callId));
    if (displayedNotification) {
        displayedNotification.close();
        displayedCallNotifications.delete(Number(callId));
    }
    if (!('serviceWorker' in navigator)) return;
    try {
        const registration = await navigator.serviceWorker.ready;
        const notifications = await registration.getNotifications({ tag: `voice-call-${callId}` });
        notifications.forEach((notification) => notification.close());
    } catch {}
}

async function markMissedCallsSeen(calls = []) {
    const unseenCalls = calls.filter((call) => call?.id && !call.is_seen);
    if (!unseenCalls.length) return;

    unseenCalls.forEach((call) => {
        call.is_seen = true;
        void closeMissedCallNotification(call.id);
    });

    await Promise.allSettled(unseenCalls.map((call) => jsonFetch(routeFor('seen', call.id), {
        method: 'POST',
    })));
}

function setResponderPanelOpen(open) {
    if (!isResponder) return;
    const panel = ensureIncomingPanel();
    const launcher = ensureResponderLauncher();
    const backdrop = document.querySelector('[data-pyro-voice-panel-backdrop]');
    responderPanelOpen = Boolean(open);
    panel.hidden = !responderPanelOpen;
    panel.classList.toggle('is-open', responderPanelOpen);
    panel.setAttribute('aria-modal', window.matchMedia('(max-width: 768px)').matches && responderPanelOpen ? 'true' : 'false');
    launcher.setAttribute('aria-expanded', responderPanelOpen ? 'true' : 'false');
    launcher.setAttribute('aria-label', responderPanelOpen ? 'Close dispatch call center' : 'Open dispatch call center');
    if (backdrop) backdrop.hidden = !responderPanelOpen;
    document.body.classList.toggle('pyro-voice-panel-open', responderPanelOpen);
    if (responderPanelOpen) {
        if (latestResponderPayload?.missed_calls?.length) {
            void markMissedCallsSeen(latestResponderPayload.missed_calls).then(() => {
                if (latestResponderPayload) renderIncoming(latestResponderPayload);
            });
        }
        void prepareAgoraForCallInterface('responder-panel');
        window.setTimeout(() => panel.querySelector('[data-pyro-voice-panel-close]')?.focus(), 0);
    }
}

function updateResponderLauncher(actionableCount, incomingCount) {
    const launcher = ensureResponderLauncher();
    const badge = launcher.querySelector('[data-pyro-voice-launcher-count]');
    responderPanelTotal = Math.max(0, Number(actionableCount || 0));
    badge.textContent = responderPanelTotal > 99 ? '99+' : String(responderPanelTotal);
    badge.hidden = responderPanelTotal === 0;
    launcher.classList.toggle('has-calls', responderPanelTotal > 0);
    launcher.classList.toggle('has-incoming', Number(incomingCount || 0) > 0);
}

function ensureCallerDock() {
    let dock = document.querySelector('[data-pyro-voice-caller-dock]');
    if (dock) return dock;

    dock = document.createElement('section');
    dock.className = 'pyro-voice-caller-dock';
    dock.dataset.pyroVoiceCallerDock = 'true';
    dock.hidden = true;
    dock.innerHTML = `
        <div class="pyro-voice-caller-dock__header">
            <div>
                <p class="pyro-voice-caller-dock__eyebrow">Emergency call</p>
                <h2 class="pyro-voice-caller-dock__title">Fire Bureau Dispatch</h2>
            </div>
            <span class="pyro-voice-badge" data-pyro-voice-caller-status>Calling</span>
        </div>
        <div class="pyro-voice-caller-dock__body">
            <div class="pyro-voice-caller-dock__hero">
                <div class="pyro-voice-caller-dock__avatar" aria-hidden="true">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                </div>
                <p class="pyro-voice-caller-dock__label">Connecting to</p>
                <h3 class="pyro-voice-caller-dock__station" data-pyro-voice-caller-title>Calling station</h3>
                <p class="pyro-voice-status-line" data-pyro-voice-caller-line>Connecting to the station...</p>
            </div>
            <div class="pyro-voice-controls">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--icon" data-pyro-voice-mute disabled>
                    <i class="fa-solid fa-microphone"></i>
                    <span>Mute</span>
                </button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--icon" data-pyro-voice-speaker>
                    <i class="fa-solid fa-volume-high"></i>
                    <span>Speaker</span>
                </button>
            </div>
            <div class="pyro-voice-callback-actions" data-pyro-voice-callback-actions hidden>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--success" data-pyro-voice-accept-callback>
                    <i class="fa-solid fa-phone"></i><span>Answer</span>
                </button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-reject-callback>
                    <i class="fa-solid fa-phone-slash"></i><span>Decline</span>
                </button>
            </div>
            <div class="pyro-voice-caller-dock__footer">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--danger pyro-voice-btn--endcall" data-pyro-voice-end>
                    <i class="fa-solid fa-phone-slash"></i>
                    <span>End Call</span>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(dock);
    dock.addEventListener('click', handleCallerDockClick);
    return dock;
}

function renderCallCard(call, active = false) {
    const callerName = call?.caller?.name || 'Anonymous caller';
    const location = callLocation(call);
    const locationText = callerLocationDisplay(location);
    const session = responderSessions.get(Number(call.id)) || responderSessions.get(call.id);
    const muted = Boolean(session?.muted);
    const speakerEnabled = session?.speakerEnabled !== false;
    const callbackWaiting = call.is_callback && call.status === 'ringing';
    const onHold = Boolean(call.is_on_hold || session?.held);
    const quality = responderQualityLabel(session);
    const state = callbackWaiting ? 'Calling citizen' : (active ? 'Connected' : call.status);
    const hasIncident = Boolean(call.created_incident_id || call.incident_id);
    const flaggedClass = call.is_flagged_prank ? 'pyro-voice-call-card--flagged' : '';
    return `
        <article id="pyro-voice-call-${call.id}" class="pyro-voice-call-card ${active ? 'pyro-voice-call-card--active' : ''} ${flaggedClass}" data-call-id="${call.id}" tabindex="-1">
            <div class="pyro-voice-call-card__top">
                <div class="pyro-voice-avatar">${escapeHtml(initials(callerName))}</div>
                <div>
                    <p class="pyro-voice-call-card__name">${escapeHtml(callerName)}</p>
                    <p class="pyro-voice-call-card__meta">${escapeHtml(call.caller?.contact_number || call.caller?.email || 'Citizen')} • ${formatSeconds(callAge(call))}</p>
                </div>
                <span class="pyro-voice-badge">${escapeHtml(state)}</span>
            </div>
            <div class="pyro-voice-call-card__location">
                <span class="pyro-voice-call-card__location-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                <span>${escapeHtml(locationText)}</span>
            </div>
            ${(active || callbackWaiting) ? `
                <div class="pyro-voice-call-health" data-pyro-voice-quality="${call.id}">
                    <span class="pyro-voice-call-health__dot pyro-voice-call-health__dot--${quality.tone}"></span>
                    <span>${quality.label}</span>
                    <span>${onHold ? 'On hold' : (session?.localAudioTrack ? 'Mic ready' : 'Connecting audio')}</span>
                </div>
            ` : ''}
            <div class="pyro-voice-actions">
                ${active || callbackWaiting ? `
                    ${session ? `
                        <button type="button" class="pyro-voice-btn ${muted ? 'is-active' : ''}" data-pyro-voice-mute ${session.localAudioTrack ? '' : 'disabled'}>
                            <i class="fa-solid ${muted ? 'fa-microphone-slash' : 'fa-microphone'}"></i> ${muted ? 'Unmute' : 'Mute'}
                        </button>
                        <button type="button" class="pyro-voice-btn ${speakerEnabled ? '' : 'is-active'}" data-pyro-voice-card-speaker>
                            <i class="fa-solid ${speakerEnabled ? 'fa-volume-high' : 'fa-volume-xmark'}"></i> ${speakerEnabled ? 'Speaker' : 'Silent'}
                        </button>
                    ` : `
                        <button type="button" class="pyro-voice-btn pyro-voice-btn--primary" data-pyro-voice-reconnect>
                            <i class="fa-solid fa-headset"></i> Connect audio
                        </button>
                    `}
                    <button type="button" class="pyro-voice-btn ${onHold ? 'is-active' : ''}" data-pyro-voice-hold>
                        <i class="fa-solid ${onHold ? 'fa-play' : 'fa-pause'}"></i> ${onHold ? 'Resume' : 'Hold'}
                    </button>
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-end><i class="fa-solid fa-phone-slash" aria-hidden="true"></i> End</button>
                ` : `
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--primary" data-pyro-voice-accept ${call.can_accept ? '' : 'disabled'}><i class="fa-solid fa-phone" aria-hidden="true"></i> Accept</button>
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-reject><i class="fa-solid fa-phone-slash" aria-hidden="true"></i> Reject</button>
                `}
            </div>
            <div class="pyro-voice-actions pyro-voice-actions--triage">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--success" data-pyro-voice-create-incident ${hasIncident || call.is_flagged_prank || !location.has_location ? 'disabled' : ''}>
                    <i class="fa-solid fa-fire-extinguisher" aria-hidden="true"></i> ${hasIncident ? 'Incident created' : 'Create Incident from Call'}
                </button>
                <button type="button" class="pyro-voice-btn" data-pyro-voice-view-location ${location.has_location ? '' : 'disabled'}><i class="fa-solid fa-location-dot" aria-hidden="true"></i> View caller location</button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--warning" data-pyro-voice-flag ${call.is_flagged_prank || hasIncident ? 'disabled' : ''}><i class="fa-solid fa-flag" aria-hidden="true"></i> Flag as prank call</button>
            </div>
        </article>
    `;
}

function responderQualityLabel(session) {
    if (!session || session.joinState !== 'connected') return { label: 'Connecting', tone: 'neutral' };
    const score = Math.max(Number(session.uplinkQuality || 0), Number(session.downlinkQuality || 0));
    if (score === 0) return { label: 'Checking quality', tone: 'neutral' };
    if (score <= 2) return { label: 'Strong connection', tone: 'good' };
    if (score === 3) return { label: 'Fair connection', tone: 'fair' };
    return { label: 'Poor connection', tone: 'poor' };
}

function formatCallDateTime(value) {
    const date = value ? new Date(value) : null;
    if (!date || Number.isNaN(date.getTime())) return 'Time unavailable';

    const now = new Date();
    const sameDay = date.toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' })
        === now.toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
    const time = date.toLocaleTimeString('en-PH', {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        minute: '2-digit',
    });

    if (sameDay) return `Today, ${time}`;
    return date.toLocaleString('en-PH', {
        timeZone: 'Asia/Manila',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function renderMissedCallCard(call) {
    const callerName = call?.caller?.name || 'Anonymous caller';
    const location = callLocation(call);
    const locationText = callerLocationDisplay(location);
    const hasIncident = Boolean(call.created_incident_id || call.incident_id);
    return `
        <article id="pyro-voice-call-${call.id}" class="pyro-voice-call-card pyro-voice-call-card--missed ${call.is_seen ? 'is-seen' : 'is-unseen'}" data-call-id="${call.id}" tabindex="-1">
            <div class="pyro-voice-call-card__top">
                <div class="pyro-voice-avatar">${escapeHtml(initials(callerName))}</div>
                <div>
                    <p class="pyro-voice-call-card__name">${escapeHtml(callerName)}</p>
                    <p class="pyro-voice-call-card__meta">${escapeHtml(call.caller?.contact_number || call.caller?.email || 'Citizen')} • ${escapeHtml(formatCallDateTime(call.ended_at || call.updated_at))}</p>
                </div>
                <span class="pyro-voice-badge">${call.is_seen ? 'Seen' : 'New missed'}</span>
            </div>
            <div class="pyro-voice-call-card__location">
                <span class="pyro-voice-call-card__location-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                <span>${escapeHtml(locationText)}</span>
            </div>
            <div class="pyro-voice-actions pyro-voice-actions--triage">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--success" data-pyro-voice-create-incident ${hasIncident || call.is_flagged_prank || !location.has_location ? 'disabled' : ''}>
                    <i class="fa-solid fa-fire-extinguisher" aria-hidden="true"></i> ${hasIncident ? 'Incident created' : 'Create Incident from Missed Call'}
                </button>
                <button type="button" class="pyro-voice-btn" data-pyro-voice-view-location ${location.has_location ? '' : 'disabled'}><i class="fa-solid fa-location-dot" aria-hidden="true"></i> View caller location</button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--primary" data-pyro-voice-callback ${call.can_callback ? '' : 'disabled'}>
                    <i class="fa-solid fa-phone"></i> Call citizen back
                </button>
            </div>
        </article>
    `;
}

function renderRecentContactCard(call) {
    const callerName = call?.caller?.name || 'Citizen';
    const statusLabel = call.status === 'rejected' ? 'Declined' : 'Completed';
    return `
        <details id="pyro-voice-call-${call.id}" class="pyro-voice-call-card pyro-voice-call-card--collapsible" data-call-id="${call.id}">
            <summary class="pyro-voice-call-card__top">
                <div class="pyro-voice-avatar">${escapeHtml(initials(callerName))}</div>
                <div>
                    <p class="pyro-voice-call-card__name">${escapeHtml(callerName)}</p>
                    <p class="pyro-voice-call-card__meta">${escapeHtml(formatCallDateTime(call.ended_at || call.updated_at))} • ${escapeHtml(call.caller?.contact_number || call.caller?.email || 'Registered citizen')}</p>
                </div>
                <span class="pyro-voice-card-summary-state"><span class="pyro-voice-badge">${statusLabel}</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span>
            </summary>
            <div class="pyro-voice-actions pyro-voice-call-card__expanded-actions">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--primary pyro-voice-btn--wide" data-pyro-voice-callback ${call.can_callback ? '' : 'disabled'}>
                    <i class="fa-solid fa-phone"></i> Call citizen back
                </button>
            </div>
        </details>
    `;
}

function responderCallTimestamp(call = {}) {
    const value = call.answered_at || call.ended_at || call.updated_at || call.created_at;
    const timestamp = value ? new Date(value).getTime() : 0;
    return Number.isFinite(timestamp) ? timestamp : 0;
}

function filterAndSortResponderCalls(calls = [], category = 'completed') {
    const filtered = calls.filter((call) => {
        const activeCategory = responderCallFilter === 'active' && category === 'incoming';
        if (responderCallFilter !== 'all' && responderCallFilter !== category && !activeCategory) return false;
        if (!responderCallSearch) return true;

        const location = callLocation(call);
        const searchable = [
            call.id,
            call?.caller?.id,
            call?.caller?.name,
            call?.caller?.contact_number,
            call?.caller?.email,
            location.display,
            call.status,
        ].filter(Boolean).join(' ').toLowerCase();

        return searchable.includes(responderCallSearch);
    });

    return filtered.sort((left, right) => {
        if (responderCallSort === 'caller') {
            return String(left?.caller?.name || 'Anonymous caller').localeCompare(
                String(right?.caller?.name || 'Anonymous caller'),
                undefined,
                { sensitivity: 'base' },
            );
        }

        const difference = responderCallTimestamp(left) - responderCallTimestamp(right);
        return responderCallSort === 'oldest' ? difference : -difference;
    });
}

function renderIncoming(payload) {
    latestResponderPayload = payload;
    const panel = ensureIncomingPanel();
    const list = panel.querySelector('[data-pyro-voice-list]');
    const count = panel.querySelector('[data-pyro-voice-count]');
    const activeCount = panel.querySelector('[data-pyro-voice-active-count]');
    const calls = payload.incoming_calls || [];
    const missedCalls = payload.missed_calls || [];
    const recentContacts = payload.recent_contacts || [];
    if (responderPanelOpen) void markMissedCallsSeen(missedCalls);
    const unseenMissedCalls = missedCalls.filter((call) => {
        if (call.is_seen || call.is_notification_recent === false) return false;
        const occurredAt = new Date(call.ended_at || call.created_at || 0).getTime();
        return Number.isFinite(occurredAt) && occurredAt > Date.now() - (30 * 60 * 1000);
    });
    const activeCalls = payload.active_calls || (payload.active_call ? [payload.active_call] : []);
    const outgoingCalls = payload.outgoing_calls || [];
    const liveCalls = [...activeCalls, ...outgoingCalls];
    const total = calls.length + liveCalls.length + missedCalls.length + recentContacts.length;
    const visibleLiveCalls = filterAndSortResponderCalls(liveCalls, 'active');
    const visibleIncomingCalls = filterAndSortResponderCalls(calls, 'incoming');
    const visibleMissedCalls = filterAndSortResponderCalls(missedCalls, 'missed');
    const visibleRecentContacts = filterAndSortResponderCalls(recentContacts, 'completed');
    const visibleTotal = visibleLiveCalls.length + visibleIncomingCalls.length + visibleMissedCalls.length + visibleRecentContacts.length;

    panel.hidden = !responderPanelOpen;
    count.textContent = `${total} total`;
    activeCount.textContent = `${liveCalls.length + calls.length} active`;
    panel.querySelector('[data-pyro-voice-results]').textContent = `${visibleTotal} shown`;
    const filterCounts = {
        all: total,
        active: liveCalls.length + calls.length,
        missed: missedCalls.length,
        completed: recentContacts.length,
    };
    Object.entries(filterCounts).forEach(([category, value]) => {
        const badge = panel.querySelector(`[data-pyro-voice-filter-count="${category}"]`);
        if (badge) badge.textContent = String(value);
    });
    updateResponderLauncher(calls.length + liveCalls.length + unseenMissedCalls.length, calls.length);

    if (!total) {
        list.innerHTML = '<div class="pyro-voice-panel__empty"><i class="fa-solid fa-phone-volume"></i><strong>No calls waiting</strong><span>Incoming and active calls will appear here.</span></div>';
        stopRinging();
        activeResponderCalls = new Map();
        responderSessions.forEach((session, callId) => leaveResponderAgora(callId).catch(() => {}));
        knownIncomingIds = new Set();
        knownCallsById = new Map();
        knownMissedIds = new Set();
        return;
    }

    knownCallsById = new Map([
        ...liveCalls.map((call) => [call.id, call]),
        ...calls.map((call) => [call.id, call]),
        ...missedCalls.map((call) => [call.id, call]),
        ...recentContacts.map((call) => [call.id, call]),
    ]);
    activeResponderCalls = new Map(liveCalls.map((call) => [Number(call.id), call]));
    responderSessions.forEach((session, callId) => {
        if (!activeResponderCalls.has(Number(callId))) {
            leaveResponderAgora(callId).catch(() => {});
        }
    });

    const newIds = calls.map((call) => call.id).filter((id) => !knownIncomingIds.has(id));
    const newMissedIds = unseenMissedCalls.map((call) => call.id).filter((id) => !knownMissedIds.has(id));
    knownIncomingIds = new Set(calls.map((call) => call.id));
    knownMissedIds = new Set(missedCalls.map((call) => call.id));
    if (newIds.length) {
        startRinging();
        notifyIncoming(calls.find((call) => call.id === newIds[0]));
    }
    if (newMissedIds.length) {
        toast(`${newMissedIds.length} missed call${newMissedIds.length === 1 ? '' : 's'} waiting for review.`, 'info');
        const missedCall = missedCalls.find((call) => call.id === newMissedIds[0]);
        notifyIncoming(missedCall, 'Missed PyroSense voice call');
    }

    const sections = [];

    if (visibleLiveCalls.length || visibleIncomingCalls.length) {
        sections.push(`
            <section class="pyro-voice-section">
                <div class="pyro-voice-section__label">Live calls</div>
                <div class="pyro-voice-section__cards">
                    ${[
                        ...visibleLiveCalls.map((call) => renderCallCard(call, true)),
                        ...visibleIncomingCalls.map((call) => renderCallCard(call)),
                    ].join('')}
                </div>
            </section>
        `);
    }

    if (visibleMissedCalls.length) {
        sections.push(`
            <section class="pyro-voice-section">
                <div class="pyro-voice-section__label">Missed calls</div>
                <div class="pyro-voice-section__cards">
                    ${visibleMissedCalls.map((call) => renderMissedCallCard(call)).join('')}
                </div>
            </section>
        `);
    }

    if (visibleRecentContacts.length) {
        sections.push(`
            <section class="pyro-voice-section">
                <div class="pyro-voice-section__label">Recent citizen contacts</div>
                <div class="pyro-voice-section__cards">
                    ${visibleRecentContacts.map((call) => renderRecentContactCard(call)).join('')}
                </div>
            </section>
        `);
    }

    const history = payload.history || {};
    const canLoadMore = Boolean(history.has_more) && responderHistoryLimit < Number(history.maximum_limit || 40);
    const historyAction = canLoadMore
        ? `<button type="button" class="pyro-voice-history-more" data-pyro-voice-load-more><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Load older calls</span></button>`
        : '';

    list.innerHTML = sections.length
        ? sections.join('') + historyAction
        : '<div class="pyro-voice-panel__empty"><i class="fa-solid fa-filter-circle-xmark"></i><strong>No matching calls</strong><span>Try another search, filter, or sorting option.</span></div>';

    if (pendingNotificationCallId) {
        focusResponderCall(pendingNotificationCallId);
    }
}

async function pollIncoming() {
    if (!routes.incoming) return;

    if (incomingPollInFlight) {
        return incomingPollInFlight;
    }

    incomingPollInFlight = (async () => {
        try {
            const incomingUrl = new URL(routes.incoming, window.location.origin);
            incomingUrl.searchParams.set('history_limit', String(responderHistoryLimit));
            const payload = await jsonFetch(incomingUrl.toString());
            incomingPollFailureCount = 0;
            renderIncoming(payload);
        } catch (error) {
            incomingPollFailureCount += 1;
            if (incomingPollFailureCount === 1 || incomingPollFailureCount % 5 === 0) {
                console.warn('Voice call incoming poll failed:', error);
            }
        } finally {
            incomingPollInFlight = null;
        }
    })();

    try {
        await incomingPollInFlight;
    } finally {
        return incomingPollInFlight;
    }
}

function clearIncomingPollTimer() {
    if (incomingPollTimer) {
        clearTimeout(incomingPollTimer);
        incomingPollTimer = null;
    }
}

function getIncomingPollDelay() {
    if (isResponder && document.visibilityState === 'visible') {
        return pollMs;
    }

    const visibilityDelay = Math.max(8000, pollMs * 3);
    const backoffDelay = pollMs * Math.max(1, Math.min(6, 2 ** Math.min(incomingPollFailureCount, 3)));
    return Math.max(backoffDelay, visibilityDelay);
}

function scheduleIncomingPoll(delay = getIncomingPollDelay()) {
    clearIncomingPollTimer();
    incomingPollTimer = setTimeout(async () => {
        await pollIncoming();
        scheduleIncomingPoll();
    }, delay);
}

function getResponderVoiceBootDelay() {
    const path = String(window.location?.pathname || '');
    if (path.startsWith('/station-admin/')) {
        return 250;
    }
    return 0;
}

async function handleIncomingClick(event) {
    if (event.target.closest('[data-pyro-voice-panel-close]')) {
        setResponderPanelOpen(false);
        return;
    }
    if (event.target.closest('[data-pyro-voice-load-more]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        responderHistoryLimit = Math.min(40, responderHistoryLimit + 8);
        await pollIncoming();
        return;
    }
    const card = event.target.closest('[data-call-id]');
    if (!card) return;
    const callId = card.dataset.callId;
    const call = knownCallsById.get(Number(callId)) || knownCallsById.get(callId);

    if (event.target.closest('[data-pyro-voice-view-location]')) {
        viewCallerLocation(call);
        return;
    }

    if (event.target.closest('[data-pyro-voice-create-incident]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const originalText = button.textContent;
        button.textContent = 'Creating...';
        try {
            const payload = await jsonFetch(routeFor('createIncident', callId), { method: 'POST' });
            refreshIncidentMarkersFromVoice(payload);
            toast(payload.message || 'Incident marker created.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to create incident from call.', 'error');
            button.disabled = false;
            button.textContent = originalText;
        } finally {
            button.removeAttribute('aria-busy');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-flag]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const originalText = button.textContent;
        button.textContent = 'Flagging...';
        try {
            const payload = await jsonFetch(routeFor('flagPrank', callId), {
                method: 'POST',
                body: { reason: 'Flagged from incoming call panel' },
            });
            toast(payload.message || 'Call flagged as prank.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to flag call.', 'error');
            button.disabled = false;
            button.textContent = originalText;
        } finally {
            button.removeAttribute('aria-busy');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-callback]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        try {
            const payload = await jsonFetch(routes.callback, {
                method: 'POST',
                body: { citizen_user_id: call?.caller?.id, callback_of: callId },
            });
            await joinResponderAgora(payload.call, payload.agora);
            toast(payload.message || 'Calling citizen back.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to call this citizen back.', 'error');
            button.disabled = false;
        } finally {
            button.removeAttribute('aria-busy');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-accept]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.textContent = 'Accepting...';
        try {
            const payload = await jsonFetch(routeFor('accept', callId), {
                method: 'POST',
                timeoutMs: 10000,
                timeoutMessage: 'Accept request took too long. Please try again.',
            });
            stopRinging();
            try {
                await withTimeout(
                    joinResponderAgora(payload.call, payload.agora),
                    AGORA_JOIN_TIMEOUT_MS,
                    'Voice connection took too long. Please try accepting again.'
                );
                toast('Voice call connected.');
            } catch (joinError) {
                setAgoraJoinState('failed');
                console.warn('Voice call accepted but Agora join failed:', joinError);
                toast(joinError.message || 'Call accepted, but voice connection is still trying to connect.', 'error');
            }
        } catch (error) {
            toast(error.message || 'Unable to accept call.', 'error');
        } finally {
            await pollIncoming();
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-reject]')) {
        try {
            await jsonFetch(routeFor('reject', callId), { method: 'POST' });
            toast('Call rejected.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to reject call.', 'error');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-end]')) {
        await endResponderCall(callId);
        await pollIncoming();
        return;
    }

    if (event.target.closest('[data-pyro-voice-reconnect]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        try {
            const payload = await jsonFetch(routeFor('token', callId), { method: 'POST' });
            await joinResponderAgora(call, payload.agora);
            toast('Audio connected.');
        } catch (error) {
            toast(error.message || 'Unable to connect audio.', 'error');
            button.disabled = false;
        }
        await pollIncoming();
        return;
    }

    if (event.target.closest('[data-pyro-voice-hold]')) {
        const session = responderSessions.get(Number(callId));
        const shouldResume = Boolean(call?.is_on_hold || session?.held);
        try {
            const payload = await jsonFetch(routeFor(shouldResume ? 'resume' : 'hold', callId), { method: 'POST' });
            if (session) {
                session.held = !shouldResume;
                if (session.localAudioTrack) {
                    await session.localAudioTrack.setEnabled(shouldResume ? !session.muted : false);
                }
                session.remoteAudioTracks.forEach((track) => {
                    try { track.setVolume(shouldResume && session.speakerEnabled ? 100 : 0); } catch {}
                });
            }
            toast(payload.message || (shouldResume ? 'Call resumed.' : 'Call placed on hold.'));
        } catch (error) {
            toast(error.message || 'Unable to change hold state.', 'error');
        }
        await pollIncoming();
        return;
    }

    if (event.target.closest('[data-pyro-voice-mute]')) {
        await toggleResponderMute(callId);
        await pollIncoming();
        return;
    }

    if (event.target.closest('[data-pyro-voice-card-speaker]')) {
        toggleResponderSpeaker(callId);
        await pollIncoming();
    }
}

function replaceVoiceControlIcon(button, iconClass) {
    if (!button) return;
    const currentIcon = button.querySelector('i, .inline-fa-icon');
    const icon = document.createElement('i');
    icon.className = `fa-solid ${iconClass}`;
    icon.setAttribute('aria-hidden', 'true');
    currentIcon?.replaceWith(icon);
    window.replaceFirefighterFaIcons?.(button);
}

function renderCallerDock(call) {
    const dock = ensureCallerDock();
    const status = call.status === 'ringing'
        ? 'Calling'
        : (call.status === 'answered' && joinedCallId !== call.id
            ? 'Connecting'
            : call.status);
    dock.hidden = false;
    dock.querySelector('[data-pyro-voice-caller-status]').textContent = status;
    const incomingCallback = call.is_callback && call.status === 'ringing';
    dock.querySelector('.pyro-voice-caller-dock__eyebrow').textContent = incomingCallback ? 'Incoming station callback' : 'Emergency call';
    dock.querySelector('[data-pyro-voice-caller-title]').textContent = call.station?.name || (incomingCallback ? 'BFP station' : 'Calling station');
    dock.querySelector('[data-pyro-voice-caller-line]').textContent = callerStatusText(call);
    const muteButton = dock.querySelector('[data-pyro-voice-mute]');
    const speakerButton = dock.querySelector('[data-pyro-voice-speaker]');
    const controls = dock.querySelector('.pyro-voice-controls');
    const callbackActions = dock.querySelector('[data-pyro-voice-callback-actions]');
    const footer = dock.querySelector('.pyro-voice-caller-dock__footer');
    controls.hidden = incomingCallback;
    callbackActions.hidden = !incomingCallback;
    footer.hidden = incomingCallback;
    muteButton.disabled = call.status !== 'answered' || !localAudioTrack;
    muteButton.classList.toggle('is-active', isMuted);
    muteButton.querySelector('span').textContent = isMuted ? 'Muted' : 'Mute';
    replaceVoiceControlIcon(muteButton, isMuted ? 'fa-microphone-slash' : 'fa-microphone');
    speakerButton.classList.toggle('is-active', isSpeakerEnabled);
    speakerButton.querySelector('span').textContent = isSpeakerEnabled ? 'Speaker' : 'Silent';
    replaceVoiceControlIcon(speakerButton, isSpeakerEnabled ? 'fa-volume-high' : 'fa-volume-xmark');
}

function callerStatusText(call) {
    if (call.is_callback && call.status === 'ringing') return 'Your BFP station is calling you back.';
    if (call.status === 'answered' && call.is_on_hold) return 'The responder placed this call on hold. Please stay on the line.';
    if (call.status === 'ringing') return `Ringing for ${formatSeconds(callAge(call))}`;
    if (call.status === 'queued') return 'Queued. A station admin will answer after the active call.';
    if (call.status === 'answered' && joinedCallId !== call.id) return 'Answered. Connecting voice channel...';
    if (call.status === 'answered') return `Connected • ${formatSeconds(callAge(call))}`;
    if (call.status === 'rejected') return 'The station rejected this call.';
    if (call.status === 'missed') return 'No station admin answered in time.';
    if (call.status === 'ended') return 'Call ended.';
    return 'Connecting...';
}

async function triggerCitizenCall(trigger, event = null) {
    event?.preventDefault?.();

    if (!voiceConfig.configured || !routes.store || !navigator.onLine) {
        openEmergencyTelephoneFallback(!navigator.onLine ? 'No internet connection.' : 'Internet calling is unavailable.');
        return;
    }

    activeCallerTrigger = trigger;
    setCallerTriggerState(trigger, 'preparing');
    toast('Preparing secure call…');
    const [secureCalling, , callerLocation] = await Promise.all([
        prepareAgoraForCallInterface('caller-panel'),
        primeCallerVoiceAccess(),
        getFreshCallerLocationPayload(),
    ]);

    if (!secureCalling) {
        setCallerTriggerState(trigger, 'idle');
        openEmergencyTelephoneFallback('Secure internet calling is unavailable.');
        return;
    }

    setCallerTriggerState(trigger, 'calling');
    toast('Calling station...');

    try {
        await ensureCitizenVoiceSession();
        const payload = await jsonFetch(routes.store, {
            method: 'POST',
            body: {
                station_id: trigger.dataset.voiceStationId || null,
                incident_id: trigger.dataset.voiceIncidentId || null,
                ...callerLocation,
            },
        });
        activeCall = payload.call;
        renderCallerDock(activeCall);
        syncOutgoingRinging(activeCall.status);
        startDurationTicker();
        startCallerPolling(activeCall.id);
        if (isMobileHubPage) {
            window.dispatchEvent(new CustomEvent('pyro:mobile-hub-call-started', {
                detail: {
                    callId: activeCall?.id || null,
                    status: activeCall?.status || null,
                },
            }));
        }
    } catch (error) {
        setCallerTriggerState(trigger, 'idle');
        stopOutgoingRinging();
        toast(error.message || 'Unable to start voice call.', 'error');
        window.dispatchEvent(new CustomEvent('pyro:voice-call-failed', {
            detail: { error, trigger },
        }));
        openEmergencyTelephoneFallback('Secure internet calling could not connect.');
    }
}

async function startCitizenCall(event) {
    return triggerCitizenCall(event.currentTarget, event);
}

function startCallerPolling(callId) {
    clearInterval(callerPollTimer);
    pollCaller(callId);
    callerPollTimer = setInterval(() => pollCaller(callId), pollMs);
}

async function pollCaller(callId) {
    try {
        const payload = await jsonFetch(routeFor('show', callId));
        activeCall = payload.call;
        renderCallerDock(activeCall);
        syncOutgoingRinging(activeCall.status);

        if (activeCallerTrigger) {
            if (activeCall.status === 'answered') {
                setCallerTriggerState(activeCallerTrigger, 'connected');
            } else if (['ringing', 'queued'].includes(activeCall.status)) {
                setCallerTriggerState(activeCallerTrigger, 'ringing');
            }
        }

        if (activeCall.status === 'answered' && joinedCallId !== activeCall.id) {
            setAgoraJoinState('connecting');
            const tokenPayload = await jsonFetch(routeFor('token', activeCall.id), { method: 'POST' });
            await withTimeout(
                joinAgora(activeCall, tokenPayload.agora),
                AGORA_JOIN_TIMEOUT_MS,
                'Voice connection took too long.'
            );
            toast('Station answered. Voice connected.');
        }

        if (['rejected', 'missed', 'ended'].includes(activeCall.status)) {
            clearInterval(callerPollTimer);
            await leaveAgora();
            if (activeCallerTrigger) {
                setCallerTriggerState(activeCallerTrigger, 'idle');
                activeCallerTrigger = null;
            }
            setTimeout(() => {
                const dock = ensureCallerDock();
                dock.hidden = true;
            }, 3500);
        }
    } catch (error) {
        console.warn('Voice call status poll failed:', error);
    }
}

async function handleCallerDockClick(event) {
    if (event.target.closest('[data-pyro-voice-accept-callback]') && activeCall?.id) {
        const button = event.target.closest('button');
        button.disabled = true;
        try {
            const payload = await jsonFetch(routeFor('acceptCallback', activeCall.id), { method: 'POST' });
            activeCall = payload.call;
            stopRinging();
            await joinAgora(activeCall, payload.agora);
            renderCallerDock(activeCall);
            toast('Station callback connected.');
        } catch (error) {
            toast(error.message || 'Unable to answer callback.', 'error');
            button.disabled = false;
        }
        return;
    }
    if (event.target.closest('[data-pyro-voice-reject-callback]') && activeCall?.id) {
        await jsonFetch(routeFor('rejectCallback', activeCall.id), { method: 'POST' }).catch((error) => {
            toast(error.message || 'Unable to decline callback.', 'error');
        });
        stopRinging();
        ensureCallerDock().hidden = true;
        activeCall = null;
        return;
    }
    if (event.target.closest('[data-pyro-voice-end]') && activeCall?.id) {
        await endCurrentCall(activeCall.id);
    }
    if (event.target.closest('[data-pyro-voice-mute]')) {
        await toggleMute();
        if (activeCall) renderCallerDock(activeCall);
    }
    if (event.target.closest('[data-pyro-voice-speaker]')) {
        toggleSpeaker();
        if (activeCall) renderCallerDock(activeCall);
    }
}

async function pollCitizenCallback() {
    if (role !== 'citizen' || !routes.incomingCallback) return;
    try {
        const payload = await jsonFetch(routes.incomingCallback);
        const callback = payload.callback;
        if (!callback) {
            if (activeCall?.is_callback) {
                await leaveAgora();
                ensureCallerDock().hidden = true;
                activeCall = null;
            }
            knownCallbackId = null;
            return;
        }

        activeCall = callback;
        void prepareAgoraForCallInterface('incoming-callback');
        renderCallerDock(callback);
        if (callback.status === 'ringing' && Number(knownCallbackId) !== Number(callback.id)) {
            knownCallbackId = callback.id;
            startRinging();
            toast(`${callback.station?.name || 'Your BFP station'} is calling you back.`);
        }
        if (callback.status === 'answered' && joinedCallId !== callback.id) {
            const tokenPayload = await jsonFetch(routeFor('token', callback.id), { method: 'POST' });
            await joinAgora(callback, tokenPayload.agora);
        }
    } catch (error) {
        console.warn('Citizen callback poll failed:', error);
    }
}

async function joinResponderAgora(call, agora) {
    const callId = Number(call?.id);
    if (!callId || !agora?.app_id || !agora?.token) {
        throw new Error('Voice token is unavailable.');
    }
    if (responderSessions.has(callId)) {
        return responderSessions.get(callId);
    }

    const AgoraRTC = await loadAgoraRtc();
    const session = {
        call,
        agora,
        client: AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' }),
        localAudioTrack: null,
        remoteAudioTracks: new Map(),
        muted: false,
        speakerEnabled: true,
        joinState: 'connecting',
        held: Boolean(call.is_on_hold),
        uplinkQuality: 0,
        downlinkQuality: 0,
        qualityTimer: null,
    };
    responderSessions.set(callId, session);

    session.client.on('user-published', async (remoteUser, mediaType) => {
        await session.client.subscribe(remoteUser, mediaType);
        if (mediaType === 'audio' && remoteUser.audioTrack) {
            session.remoteAudioTracks.set(remoteUser.uid, remoteUser.audioTrack);
            remoteUser.audioTrack.setVolume(session.speakerEnabled && !session.held ? 100 : 0);
            remoteUser.audioTrack.play();
        }
    });
    session.client.on('user-unpublished', (remoteUser, mediaType) => {
        if (mediaType === 'audio') session.remoteAudioTracks.delete(remoteUser.uid);
    });
    session.client.on('user-left', (remoteUser) => session.remoteAudioTracks.delete(remoteUser.uid));
    session.client.on('network-quality', (stats) => {
        session.uplinkQuality = Number(stats?.uplinkNetworkQuality || 0);
        session.downlinkQuality = Number(stats?.downlinkNetworkQuality || 0);
        updateResponderQualityBadge(callId);
    });
    session.client.on('token-privilege-will-expire', () => renewResponderToken(callId));
    session.client.on('token-privilege-did-expire', () => renewResponderToken(callId));

    try {
        await session.client.join(agora.app_id, agora.channel_name, agora.token, agora.uid);
        session.localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        await session.client.publish([session.localAudioTrack]);
        if (session.held) {
            await session.localAudioTrack.setEnabled(false);
        }
        session.joinState = 'connected';
        session.qualityTimer = setInterval(() => updateResponderQualityBadge(callId), 1000);
        updateResponderQualityBadge(callId);
        return session;
    } catch (error) {
        await leaveResponderAgora(callId);
        throw error;
    }
}

async function renewResponderToken(callId) {
    const session = responderSessions.get(Number(callId));
    if (!session) return;
    try {
        const payload = await jsonFetch(routeFor('token', callId), { method: 'POST' });
        session.agora = payload.agora;
        await session.client.renewToken(payload.agora.token);
    } catch (error) {
        toast(`Call ${callId} token renewal failed.`, 'error');
    }
}

async function toggleResponderMute(callId) {
    const session = responderSessions.get(Number(callId));
    if (!session?.localAudioTrack) {
        toast('Microphone is not available for this call.', 'error');
        return;
    }
    session.muted = !session.muted;
    await session.localAudioTrack.setEnabled(!session.muted && !session.held);
    toast(session.muted ? 'This caller is muted.' : 'Microphone restored for this caller.');
}

function updateResponderQualityBadge(callId) {
    const session = responderSessions.get(Number(callId));
    const badge = document.querySelector(`[data-pyro-voice-quality="${Number(callId)}"]`);
    if (!session || !badge) return;
    const quality = responderQualityLabel(session);
    const micLevel = session.localAudioTrack && !session.muted && !session.held
        ? Number(session.localAudioTrack.getVolumeLevel?.() || 0)
        : 0;
    badge.innerHTML = `
        <span class="pyro-voice-call-health__dot pyro-voice-call-health__dot--${quality.tone}"></span>
        <span>${quality.label}</span>
        <span>${session.held ? 'On hold' : (micLevel > 0.03 ? 'Mic active' : 'Mic ready')}</span>
    `;
}

function toggleResponderSpeaker(callId) {
    const session = responderSessions.get(Number(callId));
    if (!session) return;
    session.speakerEnabled = !session.speakerEnabled;
    session.remoteAudioTracks.forEach((track) => {
        try { track.setVolume(session.speakerEnabled ? 100 : 0); } catch {}
    });
}

async function endResponderCall(callId) {
    try {
        await jsonFetch(routeFor('end', callId), { method: 'POST' });
        toast('Call ended.');
    } catch (error) {
        toast(error.message || 'Unable to end call.', 'error');
    } finally {
        await leaveResponderAgora(callId);
    }
}

async function leaveResponderAgora(callId) {
    const numericId = Number(callId);
    const session = responderSessions.get(numericId);
    if (!session) return;
    responderSessions.delete(numericId);
    clearInterval(session.qualityTimer);
    if (session.localAudioTrack) {
        session.localAudioTrack.stop();
        session.localAudioTrack.close();
    }
    try { await session.client.leave(); } catch (error) {
        console.warn(`Agora leave failed for call ${numericId}:`, error);
    }
    session.remoteAudioTracks.clear();
}

async function joinAgora(call, agora) {
    if (!agora?.app_id || !agora?.token) {
        setAgoraJoinState('failed');
        throw new Error('Voice token is unavailable.');
    }
    if (joinedCallId === call.id) return;

    setAgoraJoinState('connecting');
    const AgoraRTC = await loadAgoraRtc();
    stopOutgoingRinging();
    await leaveAgora();
    agoraClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
    activeAgora = agora;

    agoraClient.on('user-published', async (remoteUser, mediaType) => {
        await agoraClient.subscribe(remoteUser, mediaType);
        if (mediaType === 'audio' && remoteUser.audioTrack) {
            remoteAudioTracks.set(remoteUser.uid, remoteUser.audioTrack);
            try {
                remoteUser.audioTrack.setVolume(isSpeakerEnabled ? 100 : 0);
            } catch {}
            remoteUser.audioTrack.play();
        }
    });
    agoraClient.on('user-unpublished', (remoteUser, mediaType) => {
        if (mediaType === 'audio') {
            remoteAudioTracks.delete(remoteUser.uid);
        }
    });
    agoraClient.on('user-left', (remoteUser) => {
        remoteAudioTracks.delete(remoteUser.uid);
    });
    agoraClient.on('connection-state-change', (currentState) => {
        if (currentState === 'RECONNECTING') toast('Voice reconnecting...');
        if (currentState === 'CONNECTED' && joinedCallId) toast('Voice connected.');
    });
    agoraClient.on('token-privilege-will-expire', renewAgoraToken);
    agoraClient.on('token-privilege-did-expire', renewAgoraToken);

    await agoraClient.join(agora.app_id, agora.channel_name, agora.token, agora.uid);
    try {
        localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        await agoraClient.publish([localAudioTrack]);
        callerHasMicrophoneAccess = true;
    } catch (error) {
        localAudioTrack = null;
        callerHasMicrophoneAccess = false;
        console.warn('Voice call joined without microphone publishing:', error);
        toast('Connected without microphone access. Enable mic permissions to speak.', 'error');
    }

    joinedCallId = call.id;
    isMuted = false;
    isSpeakerEnabled = true;
    syncSpeakerState();
    setAgoraJoinState('connected');
    startDurationTicker();
}

async function renewAgoraToken() {
    if (!activeCall?.id || !agoraClient) return;
    try {
        const payload = await jsonFetch(routeFor('token', activeCall.id), { method: 'POST' });
        activeAgora = payload.agora;
        await agoraClient.renewToken(payload.agora.token);
    } catch (error) {
        toast('Voice token renewal failed. Reconnecting may be interrupted.', 'error');
    }
}

async function toggleMute() {
    if (!localAudioTrack) {
        toast('Microphone is not available for this call.', 'error');
        return;
    }
    isMuted = !isMuted;
    await localAudioTrack.setEnabled(!isMuted);
    toast(isMuted ? 'Microphone muted.' : 'Microphone active.');
}

function buildToneLoop(frequency = 880, cadenceMs = 1500, volume = 0.08) {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return null;

        const context = new AudioContext();
        const gain = context.createGain();
        gain.gain.value = 0.0001;
        gain.connect(context.destination);

        const playTone = () => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            oscillator.connect(gain);
            gain.gain.setValueAtTime(0.0001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(volume, context.currentTime + 0.04);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.42);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.46);
        };

        context.resume().then(playTone).catch(() => {});

        return {
            context,
            interval: setInterval(playTone, cadenceMs),
        };
    } catch {
        return null;
    }
}

function startOutgoingRinging() {
    if (outgoingRingtoneState?.interval) return;
    outgoingRingtoneState = buildToneLoop(740, 1650, 0.055);
}

function stopOutgoingRinging() {
    if (!outgoingRingtoneState) return;
    clearInterval(outgoingRingtoneState.interval);
    outgoingRingtoneState.context?.close?.().catch(() => {});
    outgoingRingtoneState = null;
}

function syncOutgoingRinging(status) {
    if (!isCaller) return;
    if (['ringing', 'queued'].includes(String(status || '').toLowerCase())) {
        startOutgoingRinging();
        return;
    }
    stopOutgoingRinging();
}

function syncSpeakerState() {
    remoteAudioTracks.forEach((track) => {
        try {
            track.setVolume(isSpeakerEnabled ? 100 : 0);
        } catch {}
    });
}

function toggleSpeaker() {
    isSpeakerEnabled = !isSpeakerEnabled;
    syncSpeakerState();
    toast(isSpeakerEnabled ? 'Speaker enabled.' : 'Speaker muted.');
}

async function endCurrentCall(callId) {
    const effectiveCallId = callId || activeCall?.id || joinedCallId;
    if (!effectiveCallId) {
        toast('No active call to end.', 'error');
        return;
    }

    try {
        await jsonFetch(routeFor('end', effectiveCallId), { method: 'POST' });
        toast('Call ended.');
    } catch (error) {
        toast(error.message || 'Unable to end call.', 'error');
    } finally {
        stopOutgoingRinging();
        await leaveAgora();
        clearInterval(callerPollTimer);
        activeCall = null;
        if (activeCallerTrigger) {
            setCallerTriggerState(activeCallerTrigger, 'idle');
            activeCallerTrigger = null;
        }
        const dock = document.querySelector('[data-pyro-voice-caller-dock]');
        if (dock) dock.hidden = true;
    }
}

async function leaveAgora() {
    clearInterval(durationTimer);
    durationTimer = null;
    if (localAudioTrack) {
        localAudioTrack.stop();
        localAudioTrack.close();
        localAudioTrack = null;
    }
    if (agoraClient) {
        try {
            await agoraClient.leave();
        } catch (error) {
            console.warn('Agora leave failed:', error);
        }
    }
    agoraClient = null;
    joinedCallId = null;
    isMuted = false;
    isSpeakerEnabled = true;
    remoteAudioTracks.clear();
    setAgoraJoinState('idle');
}

function startDurationTicker() {
    clearInterval(durationTimer);
    durationTimer = setInterval(() => {
        if (isCaller && activeCall) renderCallerDock(activeCall);
    }, 1000);
}

function startRinging() {
    if (ringtoneState?.interval) return;
    ringtoneState = buildToneLoop(880, 1500, 0.08);
}

function stopRinging() {
    if (!ringtoneState) return;
    clearInterval(ringtoneState.interval);
    ringtoneState.context?.close?.().catch(() => {});
    ringtoneState = null;
}

function notificationTarget(callId) {
    const url = new URL(window.location.href);
    url.hash = `voice-call-${callId}`;
    return url.href;
}

function focusResponderCall(callId) {
    if (!isResponder || !callId) return;
    pendingNotificationCallId = Number(callId);
    setResponderPanelOpen(true);

    const call = knownCallsById.get(Number(callId)) || knownCallsById.get(String(callId));
    if (call) {
        const category = call.status === 'missed'
            ? 'missed'
            : (['ended', 'rejected'].includes(call.status) ? 'completed' : 'active');
        responderCallFilter = category;
        const panel = ensureIncomingPanel();
        panel.querySelectorAll('[data-pyro-voice-filter]').forEach((button) => {
            const active = button.dataset.pyroVoiceFilter === category;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        pendingNotificationCallId = null;
        renderIncoming(latestResponderPayload || {});
    }

    window.setTimeout(() => {
        const card = document.getElementById(`pyro-voice-call-${callId}`);
        if (!card) return;
        pendingNotificationCallId = null;
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.focus({ preventScroll: true });
        card.classList.add('is-notification-target');
        window.setTimeout(() => card.classList.remove('is-notification-target'), 2400);
    }, 80);
}

async function notifyIncoming(call, title = 'Incoming PyroSense voice call') {
    if (!call || !('Notification' in window) || Notification.permission !== 'granted') return;
    const options = {
        body: call.caller?.name || 'Anonymous caller',
        tag: `voice-call-${call.id}`,
        renotify: true,
        data: { type: 'PYRO_VOICE_CALL', callId: call.id, url: notificationTarget(call.id) },
    };
    try {
        const registration = await navigator.serviceWorker?.ready;
        if (registration?.showNotification) {
            await registration.showNotification(title, options);
            return;
        }
    } catch {}
    try {
        const notification = new Notification(title, options);
        displayedCallNotifications.set(Number(call.id), notification);
        notification.onclose = () => displayedCallNotifications.delete(Number(call.id));
        notification.onclick = () => {
            window.focus();
            focusResponderCall(call.id);
            notification.close();
        };
    } catch {}
}

function requestNotificationPermissionOnGesture() {
    if (!isResponder || !('Notification' in window) || Notification.permission !== 'default') return;
    Notification.requestPermission().catch(() => {});
}

function initVoiceCalls() {
    if (!voiceConfig.configured) {
        console.warn('PyroSense voice calls are disabled because AGORA_APP_ID is missing.');
    }

    if (isCaller) {
        document.querySelectorAll('[data-pyro-voice-call]').forEach((button) => {
            button.addEventListener('click', startCitizenCall);
        });
    }

    if (!voiceConfig.configured) return;

    if (isResponder) {
        ensureIncomingPanel();
        window.setTimeout(() => {
            pollIncoming().finally(() => {
                scheduleIncomingPoll();
            });
        }, getResponderVoiceBootDelay());
        document.addEventListener('click', requestNotificationPermissionOnGesture, { once: true });
        navigator.serviceWorker?.addEventListener('message', (event) => {
            if (event.data?.type === 'PYRO_OPEN_VOICE_CALL') {
                focusResponderCall(event.data.callId);
            }
        });

        const hashMatch = window.location.hash.match(/^#voice-call-(\d+)$/);
        if (hashMatch) pendingNotificationCallId = Number(hashMatch[1]);
    }

    if (role === 'citizen' && routes.incomingCallback) {
        pollCitizenCallback();
        callbackPollTimer = setInterval(pollCitizenCallback, pollMs);
    }

    window.addEventListener('visibilitychange', () => {
        if (!isResponder) {
            return;
        }

        if (document.visibilityState === 'visible') {
            scheduleIncomingPoll(pollMs);
            return;
        }

        scheduleIncomingPoll(getIncomingPollDelay());
    });

    window.addEventListener('pagehide', () => {
        stopRinging();
        stopOutgoingRinging();
        clearIncomingPollTimer();
        clearInterval(callbackPollTimer);
        responderSessions.forEach((session, callId) => leaveResponderAgora(callId).catch(() => {}));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && responderPanelOpen) setResponderPanelOpen(false);
    });
}

window.pyroStartVoiceCall = triggerCitizenCall;
document.addEventListener('DOMContentLoaded', initVoiceCalls);
