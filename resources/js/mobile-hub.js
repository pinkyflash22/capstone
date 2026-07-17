const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
const reportButton = document.getElementById('mobileHubReportButton');
const statusBox = document.getElementById('mobileHubStatus');
const locationLabel = document.getElementById('mobileHubLocationLabel');
const locationHint = document.getElementById('mobileHubLocationHint');
const stationLabel = document.getElementById('mobileHubStationLabel');
const stationHint = document.getElementById('mobileHubStationHint');
const clockNode = document.getElementById('mobileHubClock');
let hubToastTimer = null;

if (reportButton) {
    reportButton.addEventListener('click', submitMobileHubIncidentReport);
}

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

async function resolveNearestAvailableResponder(lat, lng) {
    const params = new URLSearchParams({
        lat: String(lat),
        lng: String(lng),
        incident_type_id: '1',
    });

    const response = await fetch(`/api/incidents/nearest-available-responder?${params.toString()}`, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
    });

    if (!response.ok) {
        throw new Error(`Responder lookup failed with status ${response.status}.`);
    }

    const payload = await response.json();
    const responder = payload?.data || null;

    if (!responder?.station_id) {
        return {
            stationId: null,
            stationName: '',
            teamId: null,
            availability: 'none',
            distanceMeters: null,
        };
    }

    const distanceLabel = getDistanceLabel(responder.distance_meters);
    updateNearestStationContext(
        distanceLabel ? `${responder.station_name} (${distanceLabel})` : responder.station_name
    );

    return {
        stationId: Number(responder.station_id) || null,
        stationName: responder.station_name || '',
        teamId: Number(responder.team_id) || null,
        availability: responder.availability || 'nearest_station_only',
        distanceMeters: Number(responder.distance_meters || 0) || null,
    };
}

async function resolveNearestAvailableResponderForReport(lat, lng) {
    try {
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

async function resolveMobileHubMarkerLabel(lat, lng) {
    const geoapifyKey = window.geoapifyKey || '83677a1086d44c4e82f903fd2fe2ea9b';

    if (!geoapifyKey) {
        return {
            title: 'Fire Emergency',
            description: 'Fire emergency reported by citizen.',
        };
    }

    try {
        const params = new URLSearchParams({
            lat: String(lat),
            lon: String(lng),
            format: 'json',
            apiKey: geoapifyKey,
        });

        const response = await fetch(`https://api.geoapify.com/v1/geocode/reverse?${params.toString()}`);
        if (!response.ok) {
            throw new Error('Reverse geocoding failed.');
        }

        const payload = await response.json();
        const result = Array.isArray(payload?.results) ? payload.results[0] : null;

        const primary = result?.address_line1 || result?.formatted || result?.street || result?.suburb || result?.hamlet || null;
        const locality = [result?.barangay, result?.city, result?.county].filter(Boolean).join(', ');

        return {
            title: primary || 'Fire Emergency',
            description: locality || result?.formatted || 'Fire emergency reported by citizen.',
        };
    } catch (error) {
        console.warn('Mobile hub reverse geocode failed:', error);
        return {
            title: 'Fire Emergency',
            description: 'Fire emergency reported by citizen.',
        };
    }
}

async function createMarker(lat, lng) {
    const markerLabel = await resolveMobileHubMarkerLabel(lat, lng);

    const response = await fetch('/api/markers', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            title: markerLabel.title,
            description: markerLabel.description,
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

async function submitMobileHubIncidentReport() {
    clearHubStatus();

    if (!csrfToken || !currentUserId) {
        showHubStatus('Your session is missing required security data. Refresh the page and try again.');
        showHubToast('Session expired. Please refresh and try again.', 'error');
        return;
    }

    try {
        setReportButtonLoading(true);
        showHubToast('Sending fire emergency report...', 'success');
        showHubStatus('Sending fire report...', 'success');

        const coords = await getFreshCoords();
        updateLocationContext(true);
        const responder = await resolveNearestAvailableResponderForReport(coords.lat, coords.lng);
        const markerId = await createMarker(coords.lat, coords.lng);
        const incidentPayload = await createIncident(markerId, responder.stationId, responder.teamId);
        await updateTeamStatus(responder.teamId);

        showHubStatus(
            responder.teamId
                ? `Fire emergency report sent to ${responder.stationName}.`
                : (incidentPayload?.message || 'Fire report sent. Dispatch matching is running.'),
            'success'
        );
        showHubToast('Fire emergency report sent.', 'success');
    } catch (error) {
        console.error('Mobile hub report failed:', error);
        showHubStatus(error.message || 'Failed to send the report. Please try again.');
        showHubToast(error.message || 'Report failed. Please try again.', 'error');
    } finally {
        setReportButtonLoading(false);
    }
}
