const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 0);
const reportButton = document.getElementById('mobileHubReportButton');
const statusBox = document.getElementById('mobileHubStatus');
const locationLabel = document.getElementById('mobileHubLocationLabel');
const locationHint = document.getElementById('mobileHubLocationHint');
const stationLabel = document.getElementById('mobileHubStationLabel');
const stationHint = document.getElementById('mobileHubStationHint');
const clockNode = document.getElementById('mobileHubClock');

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

        try {
            const nearest = await resolveNearestStation(cached.lat, cached.lng);
            updateNearestStationContext(nearest.stationName);
            return;
        } catch (error) {
            console.warn('Unable to resolve nearest station from cached coordinates:', error);
        }
    }

    updateLocationContext(false);
}

function updateLocationContext(isReady) {
    if (locationLabel) {
        locationLabel.textContent = isReady ? 'Location ready' : 'Location needed';
    }

    if (locationHint) {
        locationHint.textContent = isReady
            ? 'Your current coordinates are ready for one-tap reporting.'
            : 'Allow location access so reports can be sent immediately.';
    }
}

function updateNearestStationContext(name = '') {
    if (stationLabel) {
        stationLabel.textContent = name || 'Nearest station';
    }

    if (stationHint) {
        stationHint.textContent = name
            ? 'This station is currently the closest response point.'
            : 'Checking closest fire station for your area.';
    }
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

function getDistanceMeters(lat1, lon1, lat2, lon2) {
    const earthRadius = 6371000;
    const dLat = toRadians(lat2 - lat1);
    const dLon = toRadians(lon2 - lon1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2))
        * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return earthRadius * c;
}

function toRadians(degrees) {
    return degrees * Math.PI / 180;
}

async function fetchStations() {
    const response = await fetch('/api/station', {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
    });

    if (!response.ok) {
        throw new Error(`Station lookup failed with status ${response.status}.`);
    }

    const payload = await response.json();

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload?.stationData)) {
        return payload.stationData;
    }

    if (Array.isArray(payload)) {
        return payload;
    }

    return [];
}

function resolveStationCoordinates(station) {
    if (station?.marker?.lat != null && station?.marker?.lng != null) {
        return {
            lat: Number(station.marker.lat),
            lng: Number(station.marker.lng),
        };
    }

    if (station?.lat != null && station?.lng != null) {
        return {
            lat: Number(station.lat),
            lng: Number(station.lng),
        };
    }

    return null;
}

async function resolveNearestStation(lat, lng) {
    const stations = await fetchStations();

    const candidates = stations
        .map((station) => {
            const coords = resolveStationCoordinates(station);
            if (!coords) {
                return null;
            }

            return {
                station,
                distance: getDistanceMeters(lat, lng, coords.lat, coords.lng),
            };
        })
        .filter(Boolean)
        .sort((a, b) => a.distance - b.distance)
        .slice(0, 5);

    if (!candidates.length) {
        throw new Error('No fire station with valid map coordinates is available.');
    }

    return {
        stationId: Number(candidates[0].station.station_id || 0) || null,
        stationName: candidates[0].station.station_name || candidates[0].station.name || 'Nearest station',
        teamId: null,
    };
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
        return;
    }

    try {
        setReportButtonLoading(true);
        showHubStatus('Submitting your fire emergency report using your current location...', 'success');

        const coords = await getFreshCoords();
        updateLocationContext(true);
        const { stationId, stationName, teamId } = await resolveNearestStation(coords.lat, coords.lng);
        updateNearestStationContext(stationName);
        const markerId = await createMarker(coords.lat, coords.lng);
        const incidentPayload = await createIncident(markerId, stationId, teamId);
        await updateTeamStatus(teamId);

        showHubStatus(
            incidentPayload?.message || 'Fire emergency report sent successfully. Barangay matching is running in the background.',
            'success'
        );
    } catch (error) {
        console.error('Mobile hub report failed:', error);
        showHubStatus(error.message || 'Failed to send the report. Please try again.');
    } finally {
        setReportButtonLoading(false);
    }
}
