import { getReliableLocationFix } from './location-accuracy.js';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
const reportButton = document.getElementById('mobileHubReportButton');
const statusBox = document.getElementById('mobileHubStatus');
const locationLabel = document.getElementById('mobileHubLocationLabel');
const locationHint = document.getElementById('mobileHubLocationHint');
const stationLabel = document.getElementById('mobileHubStationLabel');
const stationHint = document.getElementById('mobileHubStationHint');
const clockNode = document.getElementById('mobileHubClock');
const call911Button = document.getElementById('mobileHubCall911Button');
const dashboardLink = document.querySelector('[data-mobile-hub-nav="map"]');
const confirm911Sheet = document.getElementById('mobileHubConfirm911');
const confirm911Cancel = document.getElementById('mobileHubConfirm911Cancel');
const confirm911Proceed = document.getElementById('mobileHubConfirm911Proceed');
const responderCacheKey = 'pyrosense:nearestResponder';
const responderCacheMaxAgeMs = 60 * 1000;
const responderCacheMaxDistanceMeters = 150;
const reverseGeocodeCacheKey = 'pyrosense:mobileHubReverseGeocode';
if (reportButton) {
    reportButton.addEventListener('click', submitMobileHubIncidentReport);
}

if (call911Button) {
    call911Button.addEventListener('click', open911Confirm);
}

confirm911Cancel?.addEventListener('click', close911Confirm);
confirm911Proceed?.addEventListener('click', proceed911Call);

confirm911Sheet?.addEventListener('click', (event) => {
    if (event.target === confirm911Sheet) {
        close911Confirm();
    }
});

document.querySelectorAll('[data-mobile-hub-nav]').forEach((link) => {
    link.addEventListener('click', () => {
        const destination = link.dataset.mobileHubNav === 'history' ? 'history' : 'map';
        showHubToast(destination === 'history' ? 'Opening report history...' : 'Opening map...', 'success');
        showHubStatus(destination === 'history' ? 'Opening report history...' : 'Opening map...', 'success');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    startHubClock();
    primeHubContext();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        close911Confirm();
    }
});

window.addEventListener('pyro:mobile-hub-call-started', () => {
    showHubToast('Call started. Do you want to view the dashboard?', 'success', {
        actionLabel: 'View',
        onAction: openDashboard,
    });
});

function showHubStatus(message, type = 'error') {
    if (!statusBox) {
        return;
    }

    statusBox.textContent = message;
    const toneClass = type === 'success' ? 'is-success' : (type === 'warning' ? 'is-warning' : 'is-error');
    statusBox.className = `mobile-hub-status is-visible ${toneClass}`;
}

function openDashboard() {
    if (dashboardLink?.href) {
        window.location.href = dashboardLink.href;
        return;
    }

    window.location.href = '/citizen/dashboard';
}

function showHubToast(message, type = 'success', options = {}) {
    const actionLabel = typeof options.actionLabel === 'string' ? options.actionLabel.trim() : '';
    const onAction = typeof options.onAction === 'function' ? options.onAction : null;
    window.PyroNotify?.notify(message, {
        type: type === 'error' ? 'error' : 'success',
        duration: 3200,
        actionLabel,
        onAction,
    });
}

function clearHubStatus() {
    if (!statusBox) {
        return;
    }

    statusBox.textContent = '';
    statusBox.className = 'mobile-hub-status';
}

function open911Confirm() {
    if (!confirm911Sheet) {
        window.location.href = 'tel:911';
        return;
    }

    confirm911Sheet.classList.add('is-visible');
    confirm911Sheet.setAttribute('aria-hidden', 'false');
    showHubToast('Confirm to open the 911 dialer.', 'error');
}

function close911Confirm() {
    if (!confirm911Sheet) {
        return;
    }

    confirm911Sheet.classList.remove('is-visible');
    confirm911Sheet.setAttribute('aria-hidden', 'true');
}

function proceed911Call() {
    close911Confirm();
    showHubToast('Opening 911 dialer. Do you want to view the dashboard?', 'success', {
        actionLabel: 'View',
        onAction: openDashboard,
    });
    window.setTimeout(() => {
        window.location.href = 'tel:911';
    }, 180);
}

function setReportButtonLoading(isLoading) {
    if (!reportButton) {
        return;
    }

    reportButton.classList.toggle('is-loading', isLoading);
    reportButton.disabled = isLoading;
}

function isAbortError(error) {
    return error?.name === 'AbortError';
}

function readCachedCoords() {
    try {
        const raw = window.localStorage.getItem('pyrosense:lastKnownCoords');
        const parsed = raw ? JSON.parse(raw) : null;

        if (!parsed?.lat || !parsed?.lng) {
            return null;
        }

        return {
            lat: Number(parsed.lat),
            lng: Number(parsed.lng),
            timestamp: Number(parsed.timestamp || 0),
        };
    } catch (error) {
        console.warn('Unable to read cached coordinates:', error);
        return null;
    }
}

function writeCachedCoords(lat, lng) {
    try {
        const timestamp = Date.now();
        window.localStorage.setItem('pyrosense:lastKnownCoords', JSON.stringify({
            lat,
            lng,
            timestamp,
        }));
        window.localStorage.setItem('pyrosense:current-location', JSON.stringify({
            lat,
            lng,
            timestamp,
        }));
        window.currentLocation = {
            lat,
            lng,
            timestamp,
        };
    } catch (error) {
        console.warn('Unable to cache coordinates:', error);
    }
}

function getCoordsFallbackLabel(lat, lng) {
    const normalizedLat = Number(lat);
    const normalizedLng = Number(lng);

    if (!Number.isFinite(normalizedLat) || !Number.isFinite(normalizedLng)) {
        return 'Allow location access.';
    }

    return `${normalizedLat.toFixed(6)}, ${normalizedLng.toFixed(6)}`;
}

function readCachedReverseGeocode(lat, lng, maxAgeMs = 24 * 60 * 60 * 1000) {
    try {
        const raw = window.localStorage.getItem(reverseGeocodeCacheKey);
        const cached = raw ? JSON.parse(raw) : null;

        if (!cached?.label || !cached?.lat || !cached?.lng || !cached?.timestamp) {
            return null;
        }

        const isFresh = Date.now() - Number(cached.timestamp) <= maxAgeMs;
        const isNearby = getDistanceMeters(lat, lng, Number(cached.lat), Number(cached.lng)) <= 35;

        return isFresh && isNearby ? String(cached.label) : null;
    } catch (error) {
        console.warn('Unable to read cached reverse geocode:', error);
        return null;
    }
}

function writeCachedReverseGeocode(lat, lng, label) {
    try {
        window.localStorage.setItem(reverseGeocodeCacheKey, JSON.stringify({
            lat,
            lng,
            label,
            timestamp: Date.now(),
        }));
    } catch (error) {
        console.warn('Unable to cache reverse geocode:', error);
    }
}

function readCachedResponder(lat, lng) {
    try {
        const raw = window.localStorage.getItem(responderCacheKey);
        const cached = raw ? JSON.parse(raw) : null;

        if (!cached?.responder || !cached?.lat || !cached?.lng || !cached?.timestamp) {
            return null;
        }

        const isFresh = Date.now() - Number(cached.timestamp) <= responderCacheMaxAgeMs;
        const isNearby = getDistanceMeters(lat, lng, Number(cached.lat), Number(cached.lng)) <= responderCacheMaxDistanceMeters;

        return isFresh && isNearby ? normalizeResponder(cached.responder) : null;
    } catch (error) {
        console.warn('Unable to read cached responder:', error);
        return null;
    }
}

function writeCachedResponder(lat, lng, responder) {
    try {
        window.localStorage.setItem(responderCacheKey, JSON.stringify({
            lat,
            lng,
            responder,
            timestamp: Date.now(),
        }));
    } catch (error) {
        console.warn('Unable to cache responder:', error);
    }
}

function clearCachedResponder() {
    try {
        window.localStorage.removeItem(responderCacheKey);
    } catch (error) {
        console.warn('Unable to clear cached responder:', error);
    }
}

function startHubClock() {
    if (!clockNode) {
        return;
    }

    const render = () => {
        clockNode.textContent = new Date().toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit',
        });
    };

    render();
    window.setInterval(render, 30000);
}

async function primeHubContext() {
    const cached = readCachedCoords();

    if (cached?.lat && cached?.lng) {
        refreshLocationContext(cached.lat, cached.lng);
        updateNearestStationContext('Dispatch matching');
        scheduleResponderPrefetch(cached.lat, cached.lng);
        return;
    }

    updateLocationContext(false);
    updateNearestStationContext('Dispatch matching');
}

function updateLocationContext(isReady, hintText = '') {
    if (locationLabel) {
        locationLabel.textContent = isReady ? 'Location ready' : 'Location needed';
    }

    if (locationHint) {
        locationHint.textContent = isReady
            ? (hintText || 'Ready for one-tap report.')
            : (hintText || 'Allow location access.');
    }
}

async function reverseGeocodeLocationLabel(lat, lng) {
    const cachedLabel = readCachedReverseGeocode(lat, lng);
    if (cachedLabel) {
        return cachedLabel;
    }

    const response = await fetch('/api/reverse', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            lat,
            lon: lng,
        }),
    });

    if (!response.ok) {
        throw new Error(`Reverse geocode failed with status ${response.status}.`);
    }

    const payload = await response.json();
    const feature = Array.isArray(payload?.features) ? payload.features[0] : null;
    const properties = feature?.properties || {};
    const label = String(
        properties.formatted
        || properties.address_line1
        || properties.address_line2
        || properties.name
        || ''
    ).trim();

    if (!label) {
        throw new Error('Reverse geocode returned no readable address.');
    }

    writeCachedReverseGeocode(lat, lng, label);
    return label;
}

async function refreshLocationContext(lat, lng) {
    const fallbackLabel = getCoordsFallbackLabel(lat, lng);
    updateLocationContext(true, fallbackLabel);

    try {
        const readableLabel = await reverseGeocodeLocationLabel(lat, lng);
        updateLocationContext(true, readableLabel);
    } catch (error) {
        console.warn('Mobile hub reverse geocode failed:', error);
        updateLocationContext(true, fallbackLabel);
    }
}

function updateNearestStationContext(name = '') {
    if (stationLabel) {
        stationLabel.textContent = name || 'Dispatch matching';
    }

    if (stationHint) {
        stationHint.textContent = name
            ? 'Nearest available team is selected.'
            : 'Nearest available team is selected.';
    }
}

function getDistanceLabel(meters) {
    const distance = Number(meters || 0);

    if (!distance) {
        return '';
    }

    if (distance < 1000) {
        return `${Math.round(distance)}m away`;
    }

    return `${(distance / 1000).toFixed(1)}km away`;
}

function getDistanceMeters(lat1, lng1, lat2, lng2) {
    const earthRadius = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return earthRadius * c;
}

async function getFreshCoords() {
    const coords = await getReliableLocationFix({ acceptBestAvailable: true });
    writeCachedCoords(coords.lat, coords.lng);
    refreshLocationContext(coords.lat, coords.lng);
    return coords;
}

function normalizeResponder(responder) {
    if (!responder?.stationId && !responder?.station_id) {
        return {
            stationId: null,
            stationName: '',
            teamId: null,
            availability: 'none',
            distanceMeters: null,
        };
    }

    return {
        stationId: Number(responder.stationId ?? responder.station_id) || null,
        stationName: responder.stationName ?? responder.station_name ?? '',
        teamId: Number(responder.teamId ?? responder.team_id) || null,
        availability: responder.availability || 'nearest_station_only',
        distanceMeters: Number(responder.distanceMeters ?? responder.distance_meters ?? 0) || null,
    };
}

function renderResponderContext(responder) {
    if (!responder?.stationId) {
        updateNearestStationContext('Dispatch matching');
        return;
    }

    const distanceLabel = getDistanceLabel(responder.distanceMeters);
    updateNearestStationContext(
        distanceLabel ? `${responder.stationName} (${distanceLabel})` : responder.stationName
    );
}

async function resolveNearestAvailableResponder(lat, lng) {
    const params = new URLSearchParams({
        lat: String(lat),
        lng: String(lng),
        incident_type_id: '1',
    });

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 2500);

    let response;
    try {
        response = await fetch(`/api/incidents/nearest-available-responder?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            signal: controller.signal,
        });
    } finally {
        window.clearTimeout(timeoutId);
    }

    if (!response.ok) {
        throw new Error(`Responder lookup failed with status ${response.status}.`);
    }

    const payload = await response.json();
    const responder = payload?.data || null;

    const normalized = normalizeResponder(responder);
    writeCachedResponder(lat, lng, normalized);
    renderResponderContext(normalized);

    return normalized;
}

async function resolveNearestAvailableResponderForReport(lat, lng) {
    try {
        const cached = readCachedResponder(lat, lng);
        if (cached) {
            renderResponderContext(cached);
            return cached;
        }

        return await resolveNearestAvailableResponder(lat, lng);
    } catch (error) {
        console.warn('Mobile hub responder lookup failed; submitting without pre-assignment:', error);
        updateNearestStationContext('Dispatch matching');

        return {
            stationId: null,
            stationName: '',
            teamId: null,
            availability: 'lookup_failed',
            distanceMeters: null,
        };
    }
}

function scheduleResponderPrefetch(lat, lng) {
    const run = () => {
        const cached = readCachedResponder(lat, lng);
        if (cached) {
            renderResponderContext(cached);
            return;
        }

        resolveNearestAvailableResponder(lat, lng).catch((error) => {
            if (isAbortError(error)) {
                return;
            }

            console.warn('Mobile hub responder prefetch failed:', error);
        });
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(run, { timeout: 1800 });
        return;
    }

    window.setTimeout(run, 250);
}

async function createIncident(coords, stationId, teamId) {
    const capturedAt = new Date(coords.timestamp).toISOString();
    const body = {
        lat: coords.lat,
        lng: coords.lng,
        title: 'Citizen Fire Emergency',
        description: 'One-tap fire emergency report from the mobile hub.',
        status: 'reported',
        reported_by: currentUserId,
        occurred_at: capturedAt,
        location_accuracy: coords.accuracy,
        location_captured_at: capturedAt,
    };

    if (stationId) {
        body.station_id = stationId;
    }

    if (teamId) {
        body.team_id = teamId;
    }

    const response = await fetch('/citizen/incident', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);
        const validationMessage = payload?.errors
            ? Object.values(payload.errors).flat().find(Boolean)
            : null;

        throw new Error(validationMessage || payload?.message || 'Failed to submit the incident report.');
    }

    return response.json();
}

async function updateTeamStatus(teamId) {
    if (!teamId) {
        return;
    }

    await fetch(`/api/teams/${teamId}`, {
        method: 'PUT',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            status: 'responding',
        }),
    });
}

function updateTeamStatusInBackground(teamId) {
    updateTeamStatus(teamId).catch((error) => {
        console.warn('Mobile hub team status update failed:', error);
    });
}

async function sendMobileHubIncidentReport(coords) {
    const responder = await resolveNearestAvailableResponderForReport(coords.lat, coords.lng);
    const incidentPayload = await createIncident(coords, responder.stationId, responder.teamId);
    updateTeamStatusInBackground(responder.teamId);
    clearCachedResponder();

    return { responder, incidentPayload };
}

async function submitMobileHubIncidentReport() {
    clearHubStatus();

    if (!csrfToken || !currentUserId) {
        showHubStatus('Your session is missing required security data. Refresh the page and try again.');
        showHubToast('Session expired. Please refresh and try again.', 'error');
        return;
    }

    let optimisticTimer = null;

    try {
        setReportButtonLoading(true);
        showHubToast('Sending fire emergency report...', 'success');
        showHubStatus('Getting a fresh, accurate GPS fix...', 'success');

        const coords = await getFreshCoords();
        showHubStatus(
            coords.accuracyWarning
                ? `Weak GPS (±${Math.round(coords.accuracy)} m). Sending the report with this best available location...`
                : `GPS ready within ±${Math.round(coords.accuracy)} m. Sending fire report...`,
            coords.accuracyWarning ? 'warning' : 'success'
        );
        refreshLocationContext(coords.lat, coords.lng);

        if (!window.navigator.onLine) {
            if (typeof window.pyroPwa?.enqueueOfflineReport !== 'function') {
                throw new Error('The offline report queue is not ready. Keep this page open and try again.');
            }

            const capturedAt = new Date(coords.timestamp).toISOString();
            window.pyroPwa.enqueueOfflineReport({
                url: '/citizen/incident',
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    lat: coords.lat,
                    lng: coords.lng,
                    title: 'Citizen Fire Emergency',
                    description: 'One-tap fire emergency report queued from the mobile hub.',
                    status: 'reported',
                    reported_by: currentUserId,
                    alarmdatetime: capturedAt,
                    location_accuracy: coords.accuracy,
                    location_captured_at: capturedAt,
                }),
                type: 'citizen-mobile-fire-report',
                meta: { queued_at: capturedAt },
            });

            showHubStatus('Fire report saved offline. It will sync after reconnecting.', 'success');
            showHubToast('Fire report queued for automatic sync.', 'success');
            return;
        }

        let optimisticFeedbackShown = false;
        optimisticTimer = window.setTimeout(() => {
            optimisticFeedbackShown = true;
            showHubStatus('Report queued. Sending in background...', 'success');
            showHubToast('Fire report queued.', 'success');
        }, 1200);

        const { responder, incidentPayload } = await sendMobileHubIncidentReport(coords);
        window.clearTimeout(optimisticTimer);

        const successMessage = responder.teamId
            ? `Fire emergency report sent to ${responder.stationName}.`
            : (incidentPayload?.message || 'Fire report sent. Dispatch matching is running.');

        showHubStatus(successMessage, 'success');
        showHubToast(
            `${optimisticFeedbackShown ? 'Fire report confirmed.' : 'Fire report sent.'} Do you want to view the dashboard?`,
            'success',
            {
                actionLabel: 'View',
                onAction: openDashboard,
            }
        );
    } catch (error) {
        console.error('Mobile hub report failed:', error);
        showHubStatus(error.message || 'Failed to send the report. Please try again.');
        showHubToast(error.message || 'Report failed. Please try again.', 'error');
    } finally {
        if (optimisticTimer) {
            window.clearTimeout(optimisticTimer);
        }
        setReportButtonLoading(false);
    }
}
