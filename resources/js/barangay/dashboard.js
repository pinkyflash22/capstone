const DEFAULT_CENTER = [15.8222, 120.3297];
const DEFAULT_ZOOM = 13;
const USER_FOCUS_ZOOM = 16;

function getCategoryVisual(category) {
    const normalized = String(category || '').trim().toLowerCase();

    switch (normalized) {
        case 'fire':
            return { color: '#dc2626', icon: 'fa-fire', label: 'Fire' };
        case 'barangay':
            return { color: '#d97706', icon: 'fa-building-shield', label: 'Barangay' };
        case 'station':
            return { color: '#2563eb', icon: 'fa-house-fire', label: 'Station' };
        case 'hydrant':
            return { color: '#0f766e', icon: 'fa-faucet-drip', label: 'Hydrant' };
        case 'school':
            return { color: '#7c3aed', icon: 'fa-school', label: 'School' };
        case 'bridge':
            return { color: '#475569', icon: 'fa-road-bridge', label: 'Bridge' };
        default:
            return { color: '#64748b', icon: 'fa-location-dot', label: 'Marker' };
    }
}

function buildIcon(category, emphasized = false) {
    const visual = getCategoryVisual(category);
    const size = emphasized ? 22 : 18;
    const ring = emphasized ? '0 0 0 4px rgba(255,255,255,0.92), 0 12px 24px rgba(15,23,42,0.20)' : '0 0 0 3px rgba(255,255,255,0.92), 0 10px 20px rgba(15,23,42,0.14)';

    return L.divIcon({
        className: 'barangay-dashboard-marker',
        html: `
            <div style="
                width:${size}px;
                height:${size}px;
                border-radius:999px;
                background:${visual.color};
                display:flex;
                align-items:center;
                justify-content:center;
                color:#fff;
                box-shadow:${ring};
                border:2px solid rgba(255,255,255,0.9);
                position:relative;
            ">
                <i class="fa-solid ${visual.icon}" style="font-size:${emphasized ? '10px' : '8px'};"></i>
            </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2],
    });
}

function normalizeMarkers(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

function createPopupContent(marker, highlighted = false) {
    const visual = getCategoryVisual(marker.category);

    return `
        <div style="min-width:220px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <span style="width:10px;height:10px;border-radius:999px;background:${visual.color};display:inline-block;"></span>
                <strong style="color:#0f172a;font-size:0.92rem;">${marker.title || 'Untitled marker'}</strong>
            </div>
            <div style="color:#64748b;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:800;">
                ${visual.label}${highlighted ? ' · Latest Local Pin' : ''}
            </div>
            <p style="margin:8px 0 0;color:#475569;font-size:0.84rem;line-height:1.5;">
                ${marker.description || marker.meta || 'No marker description provided.'}
            </p>
            <div style="margin-top:10px;color:#94a3b8;font-size:0.74rem;">
                ${marker.lat}, ${marker.lng}
            </div>
        </div>
    `;
}

async function loadDashboardMap() {
    const mapElement = document.getElementById('barangayDashboardMap');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const latestPinsElement = document.getElementById('barangayDashboardMapLatestPins');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '';
    const latestPins = (() => {
        try {
            return JSON.parse(mapElement.dataset.miniMapPins || '[]');
        } catch (error) {
            console.error('Failed to parse latest barangay pins:', error);
            return [];
        }
    })();

    const map = L.map(mapElement, {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const allMarkerLayer = L.layerGroup().addTo(map);
    const latestPinLayer = L.layerGroup().addTo(map);
    const userLayer = L.layerGroup().addTo(map);
    const bounds = L.latLngBounds();
    let userMarker = null;
    let userAccuracyCircle = null;
    let hasCenteredOnUser = false;

    let markers = [];

    try {
        const response = await fetch('/api/markers', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Marker fetch failed with status ${response.status}`);
        }

        const payload = await response.json();
        markers = normalizeMarkers(payload);
    } catch (error) {
        console.error('Failed to load barangay dashboard markers:', error);
    }

    const validMarkers = markers.filter((marker) => {
        return marker && marker.lat !== null && marker.lng !== null && marker.lat !== '' && marker.lng !== '';
    });

    validMarkers.forEach((marker) => {
        const leafletMarker = L.marker([Number(marker.lat), Number(marker.lng)], {
            icon: buildIcon(marker.category, false),
        });

        leafletMarker.bindPopup(createPopupContent(marker, false));
        leafletMarker.addTo(allMarkerLayer);
        bounds.extend([Number(marker.lat), Number(marker.lng)]);
    });

    latestPins.forEach((pin) => {
        if (pin.lat === null || pin.lng === null || pin.lat === undefined || pin.lng === undefined) {
            return;
        }

        const highlightedMarker = L.marker([Number(pin.lat), Number(pin.lng)], {
            icon: buildIcon(pin.category, true),
            zIndexOffset: 500,
        });

        highlightedMarker.bindPopup(createPopupContent(pin, true));
        highlightedMarker.addTo(latestPinLayer);
        bounds.extend([Number(pin.lat), Number(pin.lng)]);
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds.pad(0.18));
    }

    if (latestPinsElement) {
        latestPinsElement.innerHTML = '';

        if (!latestPins.length) {
            latestPinsElement.innerHTML = '<span class="tag deferred">No latest pins yet</span>';
        } else {
            latestPins.forEach((pin) => {
                const visual = getCategoryVisual(pin.category);
                const chip = document.createElement('span');
                chip.className = 'tag low';
                chip.style.borderColor = `${visual.color}33`;
                chip.style.color = visual.color;
                chip.textContent = `${pin.title}`;
                latestPinsElement.appendChild(chip);
            });
        }
    }

    const overlays = {
        'All Markers': allMarkerLayer,
        'Latest Hazard Pins': latestPinLayer,
        'Your Location': userLayer,
    };

    L.control.layers(null, overlays, {
        collapsed: false,
        position: 'topright',
    }).addTo(map);

    async function persistUserLocation(latitude, longitude) {
        if (!currentUserId || !csrfToken) {
            return;
        }

        try {
            await fetch('/api/firefighter/location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    user_id: currentUserId,
                    latitude,
                    longitude,
                }),
            });
        } catch (error) {
            console.error('Failed to persist barangay user location:', error);
        }
    }

    function updateUserLocationOnMap(latitude, longitude, accuracy = 0) {
        const latLng = [Number(latitude), Number(longitude)];

        if (!userMarker) {
            userMarker = L.circleMarker(latLng, {
                radius: 9,
                color: '#1d4ed8',
                weight: 3,
                fillColor: '#38bdf8',
                fillOpacity: 0.95,
            }).addTo(userLayer);

            userMarker.bindPopup(`
                <div style="min-width:180px;">
                    <strong style="color:#0f172a;">Your Location</strong>
                    <p style="margin:8px 0 0;color:#475569;font-size:0.84rem;">Live barangay dashboard location marker.</p>
                </div>
            `);
        } else {
            userMarker.setLatLng(latLng);
        }

        if (!userAccuracyCircle) {
            userAccuracyCircle = L.circle(latLng, {
                radius: Math.max(accuracy || 0, 20),
                color: '#60a5fa',
                weight: 1.5,
                fillColor: '#93c5fd',
                fillOpacity: 0.18,
            }).addTo(userLayer);
        } else {
            userAccuracyCircle.setLatLng(latLng);
            userAccuracyCircle.setRadius(Math.max(accuracy || 0, 20));
        }

        if (!hasCenteredOnUser) {
            map.setView(latLng, USER_FOCUS_ZOOM, { animate: true });
            hasCenteredOnUser = true;
        }
    }

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            async (position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy || 0;

                updateUserLocationOnMap(latitude, longitude, accuracy);
                await persistUserLocation(latitude, longitude);
            },
            (error) => {
                console.error('Barangay dashboard geolocation error:', error);
            },
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 20000,
            }
        );
    }

    window.setTimeout(() => {
        map.invalidateSize();
    }, 120);
}

document.addEventListener('DOMContentLoaded', loadDashboardMap);
