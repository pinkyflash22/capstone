@extends('firefighters.v2-layout', [
    'active' => 'dashboard',
    'pageTitle' => 'Firefighter Dashboard V2',
    'pageSubtitle' => 'A cleaner responder command surface that reuses your live firefighter data'
])

@section('title', 'PyroSense · Firefighter Dashboard V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-fire-extinguisher"></i> Parallel Dashboard</span>
        <h2>Responder Command.</h2>
        <p>This is the new firefighter workspace built beside your current module. It keeps the same assigned incidents, nearby station context, map data, and recent station feed while presenting them in a cleaner command-center layout.</p>
    </section>

    <section class="ff2-stats">
        <article class="ff2-stat">
            <div class="ff2-stat__label"><span>Active Incidents</span><i class="fa-solid fa-fire text-[#c94b2a]"></i></div>
            <div class="ff2-stat__value" id="ff2DashboardStatActive">--</div>
            <div class="ff2-stat__meta">Current incidents still assigned to your team.</div>
        </article>
        <article class="ff2-stat">
            <div class="ff2-stat__label"><span>Crew on Station</span><i class="fa-solid fa-user-group text-[#db9218]"></i></div>
            <div class="ff2-stat__value" id="ff2DashboardStatCrew">--</div>
            <div class="ff2-stat__meta">Station personnel loaded from your responder assignment.</div>
        </article>
        <article class="ff2-stat">
            <div class="ff2-stat__label"><span>Nearby Stations</span><i class="fa-solid fa-building-shield text-[#124e5d]"></i></div>
            <div class="ff2-stat__value" id="ff2DashboardStatStations">--</div>
            <div class="ff2-stat__meta">Nearest support stations ranked from your assigned station.</div>
        </article>
        <article class="ff2-stat">
            <div class="ff2-stat__label"><span>Equipment Types</span><i class="fa-solid fa-toolbox text-[#1d9b60]"></i></div>
            <div class="ff2-stat__value" id="ff2DashboardStatEquipment">--</div>
            <div class="ff2-stat__meta">Current equipment records available in the system.</div>
        </article>
    </section>

    <section class="ff2-grid-2">
        <article class="ff2-card">
            <div class="ff2-card__body">
                <div style="display:flex;justify-content:space-between;gap:16px;align-items:start;flex-wrap:wrap;">
                    <div>
                        <h3 class="ff2-card__title">Operations Map</h3>
                        <p class="ff2-card__subtitle">Markers below reuse your current firefighter datasets: incidents, nearby stations, and team activity context.</p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <span class="ff2-pill"><i class="fa-solid fa-location-crosshairs"></i> <span id="ff2MapMarkerCount">Loading markers...</span></span>
                        <a href="{{ route('firefighter.dashboard') }}" class="ff2-button ff2-button--ghost"><i class="fa-solid fa-map"></i> Open current map</a>
                    </div>
                </div>
                <div id="ff2DashboardMap" style="height:520px;border-radius:22px;margin-top:18px;border:1px solid rgba(226,232,240,.92);overflow:hidden;background:#eef2f7;"></div>
            </div>
        </article>

        <div style="display:grid;gap:22px;">
            <article class="ff2-card">
                <div class="ff2-card__body">
                    <h3 class="ff2-card__title">Assigned Crew</h3>
                    <p class="ff2-card__subtitle">Your current station personnel loaded from the same responder source.</p>
                    <div class="ff2-list" id="ff2CrewList" style="margin-top:16px;">
                        <div class="ff2-empty">Loading crew...</div>
                    </div>
                </div>
            </article>

            <article class="ff2-card">
                <div class="ff2-card__body">
                    <h3 class="ff2-card__title">Recent Station Feed</h3>
                    <p class="ff2-card__subtitle">Latest firefighter announcements and updates.</p>
                    <div class="ff2-list" id="ff2PostList" style="margin-top:16px;">
                        <div class="ff2-empty">Loading feed...</div>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapEl = document.getElementById('ff2DashboardMap');
            const crewList = document.getElementById('ff2CrewList');
            const postList = document.getElementById('ff2PostList');
            const markerCount = document.getElementById('ff2MapMarkerCount');
            const endpoint = @json(route('firefighter.v2.api.dashboard'));

            function setStat(id, value) {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = value ?? '--';
                }
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
                });
            }

            function renderCrew(items) {
                if (!items.length) {
                    crewList.innerHTML = '<div class="ff2-empty">No crew records available for your assigned station yet.</div>';
                    return;
                }

                crewList.innerHTML = items.map((crew) => `
                    <div class="ff2-list-item">
                        <div>
                            <h3>${escapeHtml(crew.name || 'Station Officer')}</h3>
                            <div class="ff2-meta">
                                <span>${escapeHtml(crew.rank || 'Responder')}</span>
                                <span>${escapeHtml(crew.role || 'Firefighter')}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function renderPosts(items) {
                if (!items.length) {
                    postList.innerHTML = '<div class="ff2-empty">No announcement feed available yet.</div>';
                    return;
                }

                postList.innerHTML = items.map((post) => `
                    <div class="ff2-list-item">
                        <div>
                            <h3>${escapeHtml(post.title || 'Station Update')}</h3>
                            <p>${escapeHtml(post.summary || 'No summary available.')}</p>
                            <div class="ff2-meta">
                                <span>${escapeHtml(post.author || 'BFP Staff')}</span>
                                <span>${escapeHtml(post.created_at_human || 'Just now')}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function renderMap(incidents, stations) {
                if (!mapEl || typeof L === 'undefined') {
                    return;
                }

                const map = L.map(mapEl).setView([15.8133, 120.3410], 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const bounds = [];

                incidents.forEach((incident) => {
                    const lat = Number(incident?.lat);
                    const lng = Number(incident?.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    bounds.push([lat, lng]);
                    L.circleMarker([lat, lng], {
                        radius: 10,
                        color: '#c94b2a',
                        weight: 3,
                        fillColor: '#fb923c',
                        fillOpacity: 0.9
                    }).addTo(map).bindPopup(`<strong>${escapeHtml(incident?.title ?? 'Incident marker')}</strong><br>${escapeHtml(incident?.status ?? 'Reported')}`);
                });

                stations.forEach((station) => {
                    const lat = Number(station?.lat);
                    const lng = Number(station?.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    bounds.push([lat, lng]);
                    L.marker([lat, lng]).addTo(map).bindPopup(`<strong>${escapeHtml(station?.station_name ?? 'Station')}</strong>`);
                });

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [28, 28] });
                }
            }

            async function loadDashboard() {
                try {
                    const response = await fetch(endpoint, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const payload = await response.json();
                    const data = payload?.data || {};
                    const stats = data.stats || {};
                    const incidents = Array.isArray(data.incidents) ? data.incidents : [];
                    const stations = Array.isArray(data.stations) ? data.stations : [];

                    setStat('ff2DashboardStatActive', stats.active_incidents ?? 0);
                    setStat('ff2DashboardStatCrew', stats.crew_on_station ?? 0);
                    setStat('ff2DashboardStatStations', stats.nearby_stations ?? 0);
                    setStat('ff2DashboardStatEquipment', stats.available_equipment ?? 0);
                    markerCount.textContent = `${incidents.length} active markers`;
                    renderCrew(Array.isArray(data.crew) ? data.crew : []);
                    renderPosts(Array.isArray(data.posts) ? data.posts : []);
                    renderMap(incidents, stations);
                } catch (error) {
                    console.error('Unable to load firefighter v2 dashboard payload', error);
                    markerCount.textContent = 'Unable to load markers';
                    crewList.innerHTML = '<div class="ff2-empty">Unable to load crew right now.</div>';
                    postList.innerHTML = '<div class="ff2-empty">Unable to load station feed right now.</div>';
                }
            }

            loadDashboard();
        });
    </script>
@endsection
