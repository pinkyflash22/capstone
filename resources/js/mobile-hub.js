const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
const reportButton = document.getElementById('mobileHubReportButton');
const statusBox = document.getElementById('mobileHubStatus');
const locationLabel = document.getElementById('mobileHubLocationLabel');
const locationHint = document.getElementById('mobileHubLocationHint');
const stationLabel = document.getElementById('mobileHubStationLabel');
const stationHint = document.getElementById('mobileHubStationHint');
const clockNode = document.getElementById('mobileHubClock');
const responderCacheKey = 'pyrosense:nearestResponder';
const responderCacheMaxAgeMs = 60 * 1000;
const responderCacheMaxDistanceMeters = 150;
let hubToastTimer = null;

if (reportButton) {
    reportButton.addEventListener('click', submitMobileHubIncidentReport);
}

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

function showHubStatus(message, type = 'error') {
    if (!statusBox) {
        return;
    }

    statusBox.textContent = message;
    statusBox.className = `mobile-hub-status is-visible ${type === 'success' ? 'is-success' : 'is-error'}`;
}

function showHubToast(message, type = 'success') {
    let toast = document.getElementById('mobileHubToast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'mobileHubToast';
        toast.className = 'mobile-hub-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);
    }

    window.clearTimeout(hubToastTimer);
    toast.textContent = message;
    toast.className = `mobile-hub-toast is-visible ${type === 'error' ? 'is-error' : 'is-success'}`;

    hubToastTimer = window.setTimeout(() => {
        toast.classList.remove('is-visible');
    }, 3200);
}

function clearHubStatus() {
    if (!statusBox) {
        return;
    }

    statusBox.textContent = '';
    statusBox.className = 'mobile-hub-status';
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
        window.localStorage.setItem('pyrosense:lastKnownCoords', JSON.stringify({
            lat,
            lng,
            timestamp: Date.now(),
        }));
    } catch (error) {
        console.warn('Unable to cache coordinates:', error);
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
        updateLocationContext(true);
        updateNearestStationContext('Dispatch matching');
        scheduleResponderPrefetch(cached.lat, cached.lng);
        return;
    }

    updateLocationContext(false);
    updateNearestStationContext('Dispatch matching');
}

function updateLocationContext(isReady) {
    if (locationLabel) {
        locationLabel.textContent = isReady ? 'Location ready' : 'Location needed';
    }

    if (locationHint) {
        locationHint.textContent = isReady
            ? 'Ready for one-tap report.'
            : 'Allow location access.';
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

function getFreshCoords(maxAgeMs = 10 * 60 * 1000) {
    const cached = readCachedCoords();
    if (cached && Date.now() - cached.timestamp <= maxAgeMs) {
        return Promise.resolve(cached);
    }

    if (!navigator.geolocation) {
        return Promise.reject(new Error('Location services are not available on this device.'));
    }

    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const coords = {
                    lat: Number(position.coords.latitude),
                    lng: Number(position.coords.longitude),
                    timestamp: Date.now(),
                };
                writeCachedCoords(coords.lat, coords.lng);
                resolve(coords);
            },
            () => {
                if (cached) {
                    resolve(cached);
                    return;
                }

                reject(new Error('Unable to get your current location. Please allow location access and try again.'));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    });
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

async function createMarker(lat, lng) {
    const response = await fetch('/api/markers', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            title: 'Fire Emergency',
            description: 'One-tap fire emergency report from mobile hub.',
            lat,
            lng,
            status: 'active',
            category: 'fire',
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to save the incident marker.');
    }

    const payload = await response.json();
    const markerId = payload?.data?.marker_id || payload?.marker_id || null;

    if (!markerId) {
        throw new Error('The system did not return a marker ID.');
    }

    return Number(markerId);
}

async function createIncident(markerId, stationId, teamId) {
    const body = {
        marker_id: markerId,
        incident_status_id: 1,
        incident_type_id: 1,
        reported_by: currentUserId,
        alarmdatetime: new Date().toISOString(),
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
        throw new Error('Failed to submit the incident report.');
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
    const [responder, markerId] = await Promise.all([
        resolveNearestAvailableResponderForReport(coords.lat, coords.lng),
        createMarker(coords.lat, coords.lng),
    ]);
    const incidentPayload = await createIncident(markerId, responder.stationId, responder.teamId);
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
        showHubStatus('Sending fire report...', 'success');

        const coords = await getFreshCoords();
        updateLocationContext(true);

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
        showHubToast(optimisticFeedbackShown ? 'Fire report confirmed.' : 'Fire report sent.', 'success');
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
