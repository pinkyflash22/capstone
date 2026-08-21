const firefighterDashboardRoot = document.querySelector("[data-ff-page-root=\"dashboard\"]");
if (firefighterDashboardRoot && !window.__ffDashboardBooted) {
    window.__ffDashboardBooted = true;
    const config = window.firefighterDashboardConfig || {};
    const csrfToken = config.csrfToken || document.querySelector("meta[name=\"csrf-token\"]")?.content || "";
    window.mapInstanceConfig = config.mapInstanceConfig || { containerId: "firefighterDashboardMap", instanceKey: "firefighterDashboard" };
    window.deferPyroMapInit = true;
    window.__ffAllowDashboardPrefetch = false;
    window.geoapifyKey = config.geoapifyKey || null;
    window.userId = config.userId ?? null;
    window.userTeamId = config.userTeamId ?? null;
    window.userStationId = config.userStationId ?? null;
    const dashboardDataUrl = config.dashboardDataUrl;
    const dashboardPostsUrl = config.dashboardPostsUrl;
    window.allMarkers = [];
    window.mapData = { marker: window.allMarkers };
    window.stationsData = [];
    window.incidentCoords = [];
    window.currentFirefighterId = config.currentFirefighterId ?? null;
    window.currentUserId = config.currentUserId ?? null;
    const FF_LOCATION_PUSH_INTERVAL_MS = 60000;
    const FF_LOCATION_PULL_INTERVAL_MS = 90000;
    const FF_ASSIGNED_INCIDENTS_INTERVAL_MS = 30000;
    const FF_ASSIGNED_QUEUE_PAGE_SIZE = 5;
    const FF_AUTO_ASSIGN_INTERVAL_MS = 300000;
    const FF_WIDGET_CACHE_KEY = `ff_dashboard_widgets_v2_${window.currentFirefighterId}`;
    const FF_POSTS_CACHE_KEY = `ff_dashboard_posts_v2_${window.currentFirefighterId}`;
    const FF_ASSIGNED_CACHE_KEY = `ff_dashboard_assigned_v2_${window.currentFirefighterId}`;
    const FF_REVERSE_GEOCODE_CACHE_KEY = `ff_dashboard_reverse_geocode_v1_${window.currentFirefighterId || 'guest'}`;
    let ffLocationPushInterval = null;
    let ffLocationPullInterval = null;
    let ffAssignedIncidentsInterval = null;
    let ffAutoAssignInterval = null;
    let apparatusLocationWatchId = null;
    let ffDashboardWidgetsRequest = null;
    let ffDashboardPostsRequest = null;
    const FF_FETCH_TIMEOUT_MS = 10000;
    const FF_ASSIGNED_INCIDENTS_TIMEOUT_MS = 20000;
    let ffAssignedIncidentsRequest = null;
    let ffAutoAssignRequest = null;
    let ffAssignedIncidentsLastFetchedAt = 0;
    let ffAssignedQueuePage = 1;
    let responseUnitLayer = null;
    let activeResponseUnitsData = [];
    const apparatusTelemetryState = new Map();
    const APPARATUS_TELEMETRY_QUEUE_KEY = `pyrosense_apparatus_telemetry_${window.currentUserId || 'guest'}`;
    let apparatusWakeLock = null;
    let apparatusTelemetryFlushInFlight = false;
    function firefighterDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function firefighterPwaIsStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function clearFirefighterDashboardIntervals(keepApparatusWatch = false) {
        if (ffLocationPushInterval) {
            clearInterval(ffLocationPushInterval);
            ffLocationPushInterval = null;
        }
        if (ffLocationPullInterval) {
            clearInterval(ffLocationPullInterval);
            ffLocationPullInterval = null;
        }
        if (ffAssignedIncidentsInterval) {
            clearInterval(ffAssignedIncidentsInterval);
            ffAssignedIncidentsInterval = null;
        }
        if (ffAutoAssignInterval) {
            clearInterval(ffAutoAssignInterval);
            ffAutoAssignInterval = null;
        }
        if (!keepApparatusWatch && apparatusLocationWatchId !== null && navigator.geolocation) {
            navigator.geolocation.clearWatch(apparatusLocationWatchId);
            apparatusLocationWatchId = null;
        }
    }

    function startApparatusLocationWatch() {
        if (!navigator.geolocation || apparatusLocationWatchId !== null) return;
        apparatusLocationWatchId = navigator.geolocation.watchPosition(
            position => {
                if (!firefighterDashboardIsVisible() && !firefighterPwaIsStandalone()) return;
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateUserLocation(window.currentUserId, lat, lng);
                if (window.userMarker) window.userMarker.setLatLng([lat, lng]);
                publishDriverApparatusLocation(position);
            },
            error => console.warn('Live apparatus GPS watch unavailable:', error),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 3000 }
        );
    }

    function startFirefighterDashboardIntervals() {
        clearFirefighterDashboardIntervals();
        startApparatusLocationWatch();

        ffLocationPushInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateUserLocation(window.currentUserId, lat, lng);
                    sendUserLocationToServer(lat, lng);
                    publishDriverApparatusLocation(position);

                    if (window.userMarker) {
                        window.userMarker.setLatLng([lat, lng]);
                    }
                },
                function(error) {
                    console.error("Location update error:", error);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }, FF_LOCATION_PUSH_INTERVAL_MS);

        ffLocationPullInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAllFirefighterLocations();
        }, FF_LOCATION_PULL_INTERVAL_MS);

        ffAssignedIncidentsInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAssignedIncidents();
        }, FF_ASSIGNED_INCIDENTS_INTERVAL_MS);

        ffAutoAssignInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            window.runPendingIncidentAutoAssignment();
        }, FF_AUTO_ASSIGN_INTERVAL_MS);
    }

    function fetchFirefighterDashboardOnce(currentRequest, url, options = {}) {
        if (currentRequest) {
            return currentRequest;
        }

        const controller = new AbortController();
        const timeoutMs = Number(options.timeoutMs || FF_FETCH_TIMEOUT_MS);
        const { timeoutMs: _timeoutMs, ...fetchOptions } = options;
        const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);
        const requestPromise = fetch(url, {
            cache: 'no-store',
            ...fetchOptions,
            signal: controller.signal
        }).finally(() => {
            window.clearTimeout(timeoutId);
        });

        return requestPromise;
    }

    function readDashboardCache(key, fallback = null) {
        try {
            const raw = window.localStorage?.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (error) {
            console.warn('Unable to read dashboard cache:', key, error);
            return fallback;
        }
    }

    function writeDashboardCache(key, value) {
        try {
            window.localStorage?.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.warn('Unable to write dashboard cache:', key, error);
        }
    }

    function renderSectionError(targetId, message, retryHandler) {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.innerHTML = `
            <div class="ff-shell-section-error">
                <div>${escapeHtml(message)}</div>
                <button type="button" data-retry-action="${escapeHtml(retryHandler)}">Retry</button>
            </div>
        `;

        target.querySelector('[data-retry-action]')?.addEventListener('click', async function () {
            const actionName = this.getAttribute('data-retry-action');
            if (actionName && typeof window[actionName] === 'function') {
                const originalContent = this.innerHTML;
                this.disabled = true;
                this.setAttribute('aria-busy', 'true');
                this.innerHTML = '<span class="ff-shell-retry-spinner" aria-hidden="true"></span> Loading...';
                target.classList.add('is-retrying');

                try {
                    await window[actionName](true);
                } finally {
                    target.classList.remove('is-retrying');
                    if (this.isConnected) {
                        this.disabled = false;
                        this.removeAttribute('aria-busy');
                        this.innerHTML = originalContent;
                    }
                }
            }
        });
    }

    function renderDashboardAnnouncements(posts = []) {
        const container = document.getElementById('dashboardAnnouncementsList');
        if (!container) return;

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="far fa-comment-dots text-3xl mb-2"></i>
                    <p>No announcements yet.</p>
                </div>
            `;
            return;
        }

        const visiblePosts = posts.slice(0, 4);
        const fullFeedUrl = '/firefighter/announcements';

        const truncateWords = (value, limit = 5) => {
            const cleaned = String(value || '').replace(/\s+/g, ' ').trim();
            if (!cleaned) {
                return 'No update yet.';
            }

            const words = cleaned.split(' ');
            if (words.length <= limit) {
                return cleaned;
            }

            return `${words.slice(0, limit).join(' ')}...`;
        };

        const storyCards = visiblePosts.map((post) => {
            const imageUrl = post.image_url || null;
            const authorInitials = escapeHtml(post.author_initials || 'FF');
            const authorName = escapeHtml(post.author_name || 'Station Update');
            const time = escapeHtml(post.time || 'Just now');
            const caption = escapeHtml(truncateWords(post.content || 'No content', 5));

            const mediaMarkup = imageUrl
                ? `<div class="ff-dashboard-story__media"><img src="${escapeHtml(imageUrl)}" alt="${authorName}" loading="lazy" onerror="this.closest('.ff-dashboard-story__media')?.remove();"></div>`
                : `<div class="ff-dashboard-story__fallback">${authorInitials}</div>`;

            return `
                <a href="${fullFeedUrl}" class="ff-dashboard-story" aria-label="Open firefighter announcement from ${authorName}">
                    ${mediaMarkup}
                    <div class="ff-dashboard-story__overlay"></div>
                    <div class="ff-dashboard-story__content">
                        <span class="ff-dashboard-story__badge"><i class="fas fa-bullhorn"></i> Feed</span>
                        <div class="ff-dashboard-story__meta">
                            <div class="ff-dashboard-story__author">${authorName}</div>
                            <div class="ff-dashboard-story__time">${time}</div>
                            <div class="ff-dashboard-story__caption">${caption}</div>
                        </div>
                    </div>
                </a>
            `;
        });

        storyCards.push(`
            <a href="${fullFeedUrl}" class="ff-dashboard-story ff-dashboard-story--more" aria-label="See more firefighter announcements">
                <div class="ff-dashboard-story__overlay"></div>
                <div class="ff-dashboard-story__content">
                    <span class="ff-dashboard-story__more-icon"><i class="fas fa-arrow-right"></i></span>
                    <div class="ff-dashboard-story__more-title">See more...</div>
                    <div class="ff-dashboard-story__more-copy">Open the full station feed and view every announcement.</div>
                </div>
            </a>
        `);

        container.innerHTML = `<div class="ff-dashboard-stories">${storyCards.join('')}</div>`;
    }

    function renderDashboardNearbyStations(stations = []) {
        const container = document.getElementById('dashboardNearbyStationsList');
        if (!container) return;

        if (!Array.isArray(stations) || stations.length === 0) {
            container.innerHTML = `
                <div class="bg-white rounded-xl shadow p-5 text-center text-gray-500">
                    <p>No nearby stations available.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = stations.map((station) => `
            <div class="bg-white rounded-xl shadow p-5 mb-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">${escapeHtml(station.station_name || 'Station')}</p>
                        <p class="text-xs text-slate-500 font-medium">Estimated distance ${escapeHtml(station.distance || '0.0')} km</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-100">
                        <i class="fas fa-location-arrow"></i> Nearby
                    </span>
                </div>
            </div>
        `).join('');
    }

    function renderDashboardCrew(crew = []) {
        const shell = document.getElementById('dashboardCrewShell');
        const container = shell?.parentElement || document.getElementById('dashboardCrewList');
        if (!container) return;

        container.removeAttribute('data-shell-loading');

        if (!Array.isArray(crew) || crew.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>No crew records available.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = crew.map((member) => `
            <div class="crew-item">
                <div class="crew-avatar">
                    ${escapeHtml(member.initials || 'FF')}
                    <span class="crew-status ${escapeHtml(member.status || 'online')}"></span>
                </div>
                <div class="crew-info">
                    <div class="crew-name">${escapeHtml(member.name || 'Firefighter')}</div>
                    <div class="crew-role">${escapeHtml(member.role || 'Responder')}</div>
                </div>
                <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
            </div>
        `).join('');
    }

    function applyDashboardWidgetPayload(payload = {}) {
        const counts = payload.counts || {};
        const context = payload.context || {};
        if (context.station_id !== undefined && context.station_id !== null) {
            window.userStationId = context.station_id;
        }
        if (context.user_team_id !== undefined && context.user_team_id !== null) {
            window.userTeamId = context.user_team_id;
        }

        const nextMarkers = Array.isArray(payload.allMarkers) ? payload.allMarkers : [];
        const nextStations = Array.isArray(payload.stations) ? payload.stations : [];
        const nextIncidentCoords = Array.isArray(payload.incidentCoords)
            ? payload.incidentCoords
            : (Array.isArray(payload.incidents) ? payload.incidents : []);

        window.allMarkers = nextMarkers;
        window.mapData = window.mapData || {};
        window.mapData.marker = nextMarkers;
        window.stationsData = nextStations;
        window.stationDatas = nextStations;
        window.incidentCoords = nextIncidentCoords;

        document.getElementById('activeIncidentsCount').textContent = counts.active_incidents ?? '--';
        document.getElementById('equipmentCount').textContent = counts.equipment ?? '--';
        document.getElementById('crewOnlineCount').textContent = counts.crew_online ?? '--';

        renderDashboardNearbyStations(payload.stations || []);
        renderDashboardCrew(payload.crew || []);

        if (window.map && typeof window.refreshMapMarkers === 'function') {
            window.refreshMapMarkers({
                preferSeeded: true,
                revalidate: true,
                force: true,
            }).catch((error) => {
                console.warn('Unable to refresh firefighter dashboard markers:', error);
            });
        }
    }

    async function loadFirefighterDashboardWidgets(force = false) {
        try {
            if (!force && ffDashboardWidgetsRequest) {
                return ffDashboardWidgetsRequest;
            }

            ffDashboardWidgetsRequest = fetchFirefighterDashboardOnce(
                ffDashboardWidgetsRequest,
                dashboardDataUrl,
                { timeoutMs: 15000 }
            );

            const response = await ffDashboardWidgetsRequest;
            if (!response.ok) {
                throw new Error('Failed to load dashboard widgets');
            }

            const result = await response.json();
            const payload = result?.data || {};
            applyDashboardWidgetPayload(payload);
            writeDashboardCache(FF_WIDGET_CACHE_KEY, payload);
            return payload;
        } catch (error) {
            console.error('Error loading firefighter dashboard widgets:', error);
            renderSectionError('dashboardNearbyStationsList', 'Unable to refresh nearby stations right now.', 'loadFirefighterDashboardWidgets');
            renderSectionError('dashboardCrewList', 'Unable to refresh crew details right now.', 'loadFirefighterDashboardWidgets');
            return null;
        } finally {
            ffDashboardWidgetsRequest = null;
        }
    }

    window.loadFirefighterDashboardWidgets = loadFirefighterDashboardWidgets;

    async function loadFirefighterDashboardPosts(force = false) {
        try {
            if (!force && ffDashboardPostsRequest) {
                return ffDashboardPostsRequest;
            }

            ffDashboardPostsRequest = fetchFirefighterDashboardOnce(
                ffDashboardPostsRequest,
                dashboardPostsUrl,
                { timeoutMs: 15000 }
            );

            const response = await ffDashboardPostsRequest;
            if (!response.ok) {
                throw new Error('Failed to load dashboard posts');
            }

            const result = await response.json();
            const posts = Array.isArray(result?.data?.posts) ? result.data.posts : [];
            renderDashboardAnnouncements(posts);
            writeDashboardCache(FF_POSTS_CACHE_KEY, posts);
            return posts;
        } catch (error) {
            console.error('Error loading firefighter dashboard posts:', error);
            renderSectionError('dashboardAnnouncementsList', 'Unable to refresh announcements right now.', 'loadFirefighterDashboardPosts');
            return [];
        } finally {
            ffDashboardPostsRequest = null;
        }
    }

    window.loadFirefighterDashboardPosts = loadFirefighterDashboardPosts;

    // Function to fetch assigned incidents
    async function fetchAssignedIncidents(force = false) {
        try {
            if (!force && !firefighterDashboardIsVisible()) {
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            const now = Date.now();
            if (!force && ffAssignedIncidentsRequest) {
                return ffAssignedIncidentsRequest;
            }
            if (!force && ffAssignedIncidentsLastFetchedAt && (now - ffAssignedIncidentsLastFetchedAt) < FF_ASSIGNED_INCIDENTS_INTERVAL_MS) {
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            ffAssignedIncidentsRequest = fetchFirefighterDashboardOnce(
                ffAssignedIncidentsRequest,
                `/firefighter/assigned/${window.currentFirefighterId}`,
                { timeoutMs: FF_ASSIGNED_INCIDENTS_TIMEOUT_MS }
            );
            const response = await ffAssignedIncidentsRequest;
            if (!response.ok) throw new Error('Failed to fetch assigned incidents');
            
            const result = await response.json();
            console.log('API Response:', result);
            
            const incidents = result.data || [];
            console.log('Incidents data:', incidents);
            window.assignedIncidentsData = incidents;
            ffAssignedIncidentsLastFetchedAt = Date.now();
            writeDashboardCache(FF_ASSIGNED_CACHE_KEY, incidents);
            
            displayAssignedIncidents(incidents);
            refreshActiveResponseUnits(incidents);
            syncFirefighterAssignedFabCount(incidents);
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(incidents);
            }
            
            document.getElementById('assignedIncidentsCount').textContent = getAssignedIncidentQueue(incidents).length;
            
            return incidents;
        } catch (error) {
            if (error?.name === 'AbortError') {
                console.warn('Assigned incidents request timed out before completion.');
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            console.error('Error fetching assigned incidents:', error);
            renderSectionError('assignedIncidentsList', 'Failed to load assigned incidents.', 'refreshFirefighterAssignedIncidents');
            return [];
        } finally {
            ffAssignedIncidentsRequest = null;
        }
    }

    window.refreshFirefighterAssignedIncidents = fetchAssignedIncidents;

    // Compatibility handler for the inline Stop Simulation button.
    // map.js exposes stopAllSimulations / stopSimulationForRoute globally.
    async function stopSimulation() {
        try {
            if (typeof window.stopAllSimulations === 'function') {
                await window.stopAllSimulations();
            } else if (typeof window.stopSimulationForRoute === 'function' && window.activeRoute?.route_id) {
                await window.stopSimulationForRoute(window.activeRoute.route_id);
            }
        } catch (error) {
            console.error('Error stopping simulation:', error);
        } finally {
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'none';
        }
    }
    
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function formatHumanDateTime(value) {
        if (!value) return 'Unknown';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Unknown';
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatReporterLabel(incident) {
        const reporter = incident?.reporter;
        const reporterRole = String(reporter?.role?.role || '').trim().toLowerCase();
        const firstName = String(reporter?.first_name || '').trim();
        const lastName = String(reporter?.last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim() || String(reporter?.name || '').trim();

        if (reporterRole === 'citizen' || (!fullName && incident?.reported_by)) {
            return 'Concerned Citizen';
        }

        return fullName || 'Concerned Citizen';
    }

    function resolveStorageUrl(path) {
        const normalizedPath = String(path || '').trim();
        if (!normalizedPath) return null;
        if (/^(https?:)?\/\//i.test(normalizedPath) || normalizedPath.startsWith('/storage/')) {
            return normalizedPath;
        }

        return `/storage/${normalizedPath.replace(/^\/+/, '')}`;
    }

    function getIncidentImageUrl(incident) {
        const multimediaPath = incident?.marker?.multimedia?.path;
        return resolveStorageUrl(multimediaPath);
    }

    function getIncidentTypeIconClass(typeName = '') {
        const normalized = String(typeName).trim().toLowerCase();
        if (normalized.includes('medical')) return 'fa-truck-medical';
        if (normalized.includes('rescue')) return 'fa-life-ring';
        if (normalized.includes('hazard')) return 'fa-radiation';
        if (normalized.includes('false')) return 'fa-times-circle';
        if (normalized.includes('accident')) return 'fa-car-crash';
        if (normalized.includes('disaster')) return 'fa-house-damage';
        return 'fa-fire';
    }

    function getIncidentStatusId(incident = {}) {
        if (incident?.incident_status_id !== undefined && incident?.incident_status_id !== null) {
            return Number(incident.incident_status_id);
        }

        const status = String(incident?.incident_status || incident?.status || '').trim().toLowerCase();
        if (status === 'en-route' || status === 'responding') return 2;
        if (status === 'on-scene' || status === 'on scene') return 3;
        if (status === 'completed' || status === 'controlled') return 4;
        if (status === 'closed') return 5;
        return 1;
    }

    function getIncidentTypeLabel(incident = {}) {
        return incident?.incident_type_name
            || incident?.incident_type
            || incident?.incidentType?.type
            || incident?.incident_type?.type
            || (incident?.marker?.category === 'fire' ? 'Fire' : 'Incident');
    }

    function getAssignedIncidentQueue(incidents = []) {
        return (Array.isArray(incidents) ? incidents : [])
            .filter((incident) => {
                const statusId = getIncidentStatusId(incident);
                return [1, 2, 3].includes(statusId);
            })
            .sort((left, right) => {
                const leftTime = Date.parse(left?.created_at || left?.updated_at || '') || 0;
                const rightTime = Date.parse(right?.created_at || right?.updated_at || '') || 0;

                return leftTime - rightTime;
            });
    }

    function renderAssignedIncidentsPagination(totalItems = 0, totalPages = 1, currentPage = 1) {
        const paginationEl = document.getElementById('assignedIncidentsPagination');
        if (!paginationEl) {
            return;
        }

        if (totalItems <= 0 || totalPages <= 1) {
            paginationEl.innerHTML = '';
            paginationEl.hidden = true;
            return;
        }

        const startItem = ((currentPage - 1) * FF_ASSIGNED_QUEUE_PAGE_SIZE) + 1;
        const endItem = Math.min(currentPage * FF_ASSIGNED_QUEUE_PAGE_SIZE, totalItems);

        paginationEl.hidden = false;
        paginationEl.innerHTML = `
            <div class="ff-assigned-incident-pagination__summary">
                Showing ${startItem}-${endItem} of ${totalItems} queued incidents
            </div>
            <div class="ff-assigned-incident-pagination__controls">
                <button type="button" class="ff-assigned-incident-pagination__button" data-assigned-page-action="prev" ${currentPage <= 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    Previous
                </button>
                <span class="ff-assigned-incident-pagination__indicator">Page ${currentPage} of ${totalPages}</span>
                <button type="button" class="ff-assigned-incident-pagination__button" data-assigned-page-action="next" ${currentPage >= totalPages ? 'disabled' : ''}>
                    Next
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        `;

        paginationEl.querySelector('[data-assigned-page-action="prev"]')?.addEventListener('click', () => {
            ffAssignedQueuePage = Math.max(1, ffAssignedQueuePage - 1);
            displayAssignedIncidents(window.assignedIncidentsData || []);
        });

        paginationEl.querySelector('[data-assigned-page-action="next"]')?.addEventListener('click', () => {
            ffAssignedQueuePage = Math.min(totalPages, ffAssignedQueuePage + 1);
            displayAssignedIncidents(window.assignedIncidentsData || []);
        });
    }

    function syncFirefighterAssignedFabCount(incidents = window.assignedIncidentsData || []) {
        const count = getAssignedIncidentQueue(incidents).length;
        const badge = document.getElementById('firefighterMobileAssignedFabCount');
        if (badge) {
            badge.textContent = String(count);
        }
    }

    function getReverseGeocodeCache() {
        return readDashboardCache(FF_REVERSE_GEOCODE_CACHE_KEY, {}) || {};
    }

    function setReverseGeocodeCache(nextCache) {
        writeDashboardCache(FF_REVERSE_GEOCODE_CACHE_KEY, nextCache && typeof nextCache === 'object' ? nextCache : {});
    }

    function buildReverseGeocodeKey(lat, lng) {
        return `${Number(lat).toFixed(5)},${Number(lng).toFixed(5)}`;
    }

    async function reverseGeocodeIncidentLocation(lat, lng) {
        if (lat === null || lat === undefined || lng === null || lng === undefined) {
            return 'Location not specified';
        }

        const cacheKey = buildReverseGeocodeKey(lat, lng);
        const cache = getReverseGeocodeCache();
        if (cache[cacheKey]?.label) {
            return cache[cacheKey].label;
        }

        try {
            const response = await fetch('/api/reverse', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ lat, lon: lng }),
            });

            if (!response.ok) {
                throw new Error('Reverse geocode request failed');
            }

            const payload = await response.json();
            const formatted = payload?.features?.[0]?.properties?.formatted
                || payload?.features?.[0]?.properties?.address_line1
                || payload?.features?.[0]?.properties?.address_line2
                || `${lat}, ${lng}`;

            cache[cacheKey] = {
                label: formatted,
                saved_at: new Date().toISOString(),
            };
            setReverseGeocodeCache(cache);

            return formatted;
        } catch (error) {
            console.warn('Assigned incident reverse geocode failed:', error);
            return `${lat}, ${lng}`;
        }
    }

    async function hydrateAssignedIncidentLocations(incidents = []) {
        const tasks = incidents
            .filter((incident) => incident?.marker?.lat !== null && incident?.marker?.lat !== undefined && incident?.marker?.lng !== null && incident?.marker?.lng !== undefined)
            .map(async (incident) => {
                const label = await reverseGeocodeIncidentLocation(incident.marker.lat, incident.marker.lng);
                const target = document.querySelector(`[data-incident-location="${incident.incident_id}"]`);
                if (target && label) {
                    target.textContent = label;
                    target.setAttribute('title', label);
                }
            });

        await Promise.allSettled(tasks);
    }

    function setIncidentActionPendingState(incidentId, options = {}) {
        const card = document.querySelector(`.incident-card [data-incident-id="${incidentId}"]`)?.closest('.incident-card')
            || document.querySelector(`.incident-card .view-incident-details[data-incident-id="${incidentId}"]`)?.closest('.incident-card');
        if (!card) return null;

        const buttons = Array.from(card.querySelectorAll('button[data-incident-id]'));
        const statusBadge = card.querySelector('.incident-status-badge');
        const previous = {
            buttonStates: buttons.map((button) => ({
                button,
                disabled: button.disabled,
                html: button.innerHTML,
            })),
            statusText: statusBadge?.textContent || null,
            statusClass: statusBadge?.className || null,
        };

        buttons.forEach((button) => {
            button.disabled = true;
            button.dataset.pending = 'true';
            button.setAttribute('aria-busy', 'true');
            button.classList.add('is-action-pending');
            if (!button.classList.contains('view-incident-details')) {
                button.innerHTML = `<span class="ff-action-spinner" aria-hidden="true"></span><span>${options.buttonLabel || 'Updating...'}</span>`;
            }
        });

        if (statusBadge) {
            statusBadge.textContent = options.badgeLabel || 'UPDATING';
            statusBadge.classList.add('opacity-80');
        }

        card.dataset.pendingIncidentAction = 'true';
        return previous;
    }

    function restoreIncidentActionPendingState(incidentId, previousState) {
        const card = document.querySelector(`.incident-card [data-incident-id="${incidentId}"]`)?.closest('.incident-card')
            || document.querySelector(`.incident-card .view-incident-details[data-incident-id="${incidentId}"]`)?.closest('.incident-card');
        if (!card || !previousState) return;

        previousState.buttonStates.forEach(({ button, disabled, html }) => {
            button.disabled = disabled;
            button.innerHTML = html;
            delete button.dataset.pending;
            button.removeAttribute('aria-busy');
            button.classList.remove('is-action-pending');
        });

        const statusBadge = card.querySelector('.incident-status-badge');
        if (statusBadge && previousState.statusClass) {
            statusBadge.className = previousState.statusClass;
            statusBadge.textContent = previousState.statusText || statusBadge.textContent;
        }

        delete card.dataset.pendingIncidentAction;
    }

    function displayAssignedIncidents(incidents) {
    const container = document.getElementById('assignedIncidentsList');
    const queuedIncidents = getAssignedIncidentQueue(incidents);
    const totalQueued = queuedIncidents.length;
    const totalPages = Math.max(1, Math.ceil(totalQueued / FF_ASSIGNED_QUEUE_PAGE_SIZE));

    ffAssignedQueuePage = Math.min(Math.max(1, ffAssignedQueuePage), totalPages);

    if (totalQueued === 0) {
        container.innerHTML = `
            <div class="empty-incidents">
                <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                <p>No active queued incidents</p>
                <p class="text-sm">Only reported, responding, and on-scene incidents appear here.</p>
            </div>
        `;
        renderAssignedIncidentsPagination(0, 1, 1);
        return;
    }

    const startIndex = (ffAssignedQueuePage - 1) * FF_ASSIGNED_QUEUE_PAGE_SIZE;
    const pageIncidents = queuedIncidents.slice(startIndex, startIndex + FF_ASSIGNED_QUEUE_PAGE_SIZE);
    let html = '';
    pageIncidents.forEach(incident => {
        const statusId = getIncidentStatusId(incident);
        let statusClass = 'status-reported';
        let statusText = 'REPORTED';
        let actionButton = '';
        let falseAlarmButton = '';
        let backupButton = '';
        
        if (statusId == 1) {
            statusClass = 'status-reported';
            statusText = 'REPORTED';
            actionButton = `
                <button type="button" class="respond-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-truck-fast"></i><span>Respond</span>
                </button>
            `;
        } else if (statusId == 2) {
            statusClass = 'status-responding';
            statusText = 'RESPONDING';
            actionButton = `
                <button type="button" class="ff-assigned-incident-row__action ff-assigned-incident-row__action--muted" disabled>
                    <i class="fas fa-truck-fast"></i><span>En Route</span>
                </button>
            `;
        } else if (statusId == 3) {
            statusClass = 'status-resolved';
            statusText = 'ON SCENE';
            actionButton = `
                <button type="button" class="complete-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-check-circle"></i><span>Complete</span>
                </button>
            `;
        } else if (statusId == 4) {
            statusClass = 'status-resolved';
            statusText = 'CONTROLLED';
            actionButton = `
                <button type="button" class="close-case ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-folder"></i><span>Close</span>
                </button>
            `;
        } else if (statusId == 5) {
            statusClass = 'status-closed';
            statusText = 'CLOSED';
            actionButton = `
                <button type="button" class="ff-assigned-incident-row__action ff-assigned-incident-row__action--muted" disabled>
                    <i class="fas fa-folder"></i><span>Closed</span>
                </button>
            `;
        }

        if ([1, 2].includes(parseInt(statusId))) {
            falseAlarmButton = `
                <button type="button" class="false-alarm-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--warn" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-triangle-exclamation"></i><span>False Alarm</span>
                </button>
            `;
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        if ([2, 3].includes(parseInt(statusId)) && String(incidentType).toLowerCase().includes('fire')) {
            backupButton = `<button type="button" class="request-incident-backup ff-assigned-incident-row__action ff-assigned-incident-row__action--warn" data-incident-id="${incident.incident_id}"><i class="fas fa-tower-broadcast"></i><span>Request Backup</span></button>`;
        }
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const reporterLabel = formatReporterLabel(incident);
        const reportedAt = formatHumanDateTime(incident.created_at);
        const assignedAt = formatHumanDateTime(incident.updated_at || incident.created_at);
        const imageUrl = getIncidentImageUrl(incident);
        const incidentTypeIcon = getIncidentTypeIconClass(incidentType);
        const hasCoordinates = lat !== null && lat !== undefined && lng !== null && lng !== undefined;
        const locationLabel = hasCoordinates ? 'Resolving location...' : 'Location not specified';
        const descriptionSummary = String(description).replace(/\s+/g, ' ').trim();
        const imageMarkup = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(incidentTitle)}" onerror="this.remove(); this.parentElement.classList.add('no-image'); this.parentElement.innerHTML = '<div class=&quot;incident-media-fallback&quot;><i class=&quot;fas ${incidentTypeIcon}&quot;></i><span>No Image</span></div>';">`
            : `<div class="incident-media-fallback"><i class="fas ${incidentTypeIcon}"></i><span>No Image</span></div>`;
        
        html += `
            <article class="incident-card ff-assigned-incident-row" data-incident-card="${incident.incident_id}">
                <button type="button" class="ff-assigned-incident-row__main view-incident-details" data-incident-id="${incident.incident_id}" aria-label="Open incident details for ${escapeHtml(incidentTitle)}">
                    <div class="ff-assigned-incident-row__media">
                        ${imageMarkup}
                    </div>
                    <div class="ff-assigned-incident-row__copy">
                        <div class="ff-assigned-incident-row__badges">
                            <span class="ff-assigned-incident-row__type">
                                <i class="fas ${incidentTypeIcon}"></i>
                                ${escapeHtml(incidentType)}
                            </span>
                            <span class="incident-status-badge ff-assigned-incident-row__status ${statusClass}">
                                ${escapeHtml(statusText)}
                            </span>
                        </div>
                        <h3 class="incident-title ff-assigned-incident-row__title">${escapeHtml(incidentTitle)}</h3>
                        <div class="ff-assigned-incident-row__detail">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <span data-incident-location="${incident.incident_id}">${escapeHtml(locationLabel)}</span>
                        </div>
                        <div class="ff-assigned-incident-row__detail">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span>${escapeHtml(reporterLabel)}</span>
                        </div>
                        <div class="ff-assigned-incident-row__meta">
                            <span>${escapeHtml(reportedAt)}</span>
                            <span class="ff-assigned-incident-row__meta-separator">&bull;</span>
                            <span>Assigned ${escapeHtml(assignedAt)}</span>
                        </div>
                        <p class="ff-assigned-incident-row__summary">${escapeHtml(descriptionSummary)}</p>
                    </div>
                </button>
                <div class="ff-assigned-incident-row__actions">
                    ${actionButton}
                    ${backupButton}
                    ${falseAlarmButton}
                </div>
            </article>
        `;
    });
    
    container.innerHTML = html;
    
    // Event Listeners for View Details buttons
    document.querySelectorAll('.view-incident-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const incidentId = btn.dataset.incidentId;
            const incident = pageIncidents.find(i => i.incident_id == incidentId) || queuedIncidents.find(i => i.incident_id == incidentId);
            if (incident) {
                showProfessionalIncidentModal(incident);
            }
        });
    });
    
    // Event Listeners for Respond buttons (status 1)
    document.querySelectorAll('.respond-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await respondToIncident(incidentId, currentStatus, btn);
        });
    });

    // Event Listeners for Complete buttons (status 2)
    document.querySelectorAll('.complete-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await completeIncident(incidentId, currentStatus, btn);
        });
    });
    
    // Event Listeners for Close Case buttons (status 3)
    document.querySelectorAll('.close-case').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await closeCase(incidentId, currentStatus, btn);
        });
    });

    document.querySelectorAll('.false-alarm-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await markIncidentFalseAlarm(incidentId, currentStatus, btn);
        });
    });

    document.querySelectorAll('.request-incident-backup').forEach(btn => {
        btn.addEventListener('click', async () => requestIncidentBackup(btn.dataset.incidentId, btn));
    });

    hydrateAssignedIncidentLocations(pageIncidents);
    renderAssignedIncidentsPagination(totalQueued, totalPages, ffAssignedQueuePage);
}
    // Professional Incident Details Modal
    function showProfessionalIncidentModal(incident) {
        let modal = document.getElementById('professionalIncidentModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'professionalIncidentModal';
            modal.className = 'professional-modal';
            modal.innerHTML = `
                <div class="professional-modal-overlay"></div>
                <div class="professional-modal-container">
                    <div class="professional-modal-header">
                        <div class="professional-modal-title">
                            <i class="fas fa-file-alt"></i>
                            Incident Details
                        </div>
                        <button class="professional-modal-close">&times;</button>
                    </div>
                    <div class="professional-modal-body" id="professionalIncidentContent"></div>
                    <div class="professional-modal-footer">
                        <button class="btn btn-outline modal-close-btn">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const closeBtn = modal.querySelector('.professional-modal-close');
            const overlay = modal.querySelector('.professional-modal-overlay');
            const closeModalBtn = modal.querySelector('.modal-close-btn');
            
            const closeModal = () => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            };
            
            closeBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            closeModalBtn.addEventListener('click', closeModal);
        }
        
        const content = document.getElementById('professionalIncidentContent');
        
        const statusId = getIncidentStatusId(incident);
        let statusBadgeClass = 'status-badge-reported';
        let statusText = 'REPORTED';
        let statusIcon = 'far fa-bell';
        
        if (statusId == 2) { 
            statusBadgeClass = 'status-badge-responding'; 
            statusText = 'RESPONDING';
            statusIcon = 'fas fa-spinner fa-pulse';
        } else if (statusId == 3) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'ON SCENE';
            statusIcon = 'fas fa-crosshairs';
        } else if (statusId == 4) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'CONTROLLED';
            statusIcon = 'fas fa-check-circle';
        } else if (statusId == 5) { 
            statusBadgeClass = 'status-badge-closed'; 
            statusText = 'CLOSED';
            statusIcon = 'fas fa-folder';
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const teamId = incident.team?.team_id;
        const reportedBy = formatReporterLabel(incident);
        const createdAt = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
        const updatedAt = incident.updated_at ? new Date(incident.updated_at).toLocaleString() : null;
        const incidentTypeIcon = getIncidentTypeIconClass(incidentType);
        const locationHeadline = incident.marker?.address
            || incident.marker?.location_name
            || incident.marker?.landmark
            || incidentTitle;
        const roleLabel = String(incident?.reporter?.role?.role || 'Citizen').trim() || 'Citizen';
        const hasCoordinates = lat !== null && lat !== undefined && lng !== null && lng !== undefined;
        const incidentSubtitle = incidentTitle && incidentTitle !== locationHeadline ? incidentTitle : null;
        const overviewItems = [
            {
                icon: 'fa-location-dot',
                label: 'Location',
                value: escapeHtml(locationHeadline),
            },
            {
                icon: 'fa-user',
                label: 'Reporter',
                value: escapeHtml(reportedBy),
            },
            {
                icon: 'fa-user-shield',
                label: 'Source',
                value: escapeHtml(roleLabel),
            },
            {
                icon: 'fa-calendar-days',
                label: 'Reported',
                value: escapeHtml(createdAt),
            },
        ];

        if (teamId) {
            overviewItems.push({
                icon: 'fa-users',
                label: 'Assigned Team',
                value: `Team #${escapeHtml(String(teamId))}`,
            });
        }

        if (updatedAt) {
            overviewItems.push({
                icon: 'fa-clock',
                label: 'Last Updated',
                value: escapeHtml(updatedAt),
            });
        }

        const overviewMarkup = overviewItems.map((item) => `
            <div class="incident-sheet__meta-item">
                <span class="incident-sheet__meta-icon"><i class="fas ${item.icon}"></i></span>
                <div class="incident-sheet__meta-copy">
                    <span class="incident-sheet__meta-label">${item.label}</span>
                    <strong class="incident-sheet__meta-value">${item.value}</strong>
                </div>
            </div>
        `).join('');
        
        content.innerHTML = `
            <div class="incident-sheet">
                <section class="incident-sheet__hero">
                    <div class="incident-sheet__hero-top">
                        <span class="incident-sheet__status details-status-header ${statusBadgeClass}">
                            <i class="${statusIcon}"></i>
                            <span>${statusText}</span>
                        </span>
                        <span class="incident-sheet__type details-type-pill">
                            <i class="fas ${incidentTypeIcon}"></i>
                            ${escapeHtml(incidentType)}
                        </span>
                    </div>
                    <div class="incident-sheet__hero-body">
                        <p class="incident-sheet__eyebrow">Response Summary</p>
                        <h2 class="incident-sheet__title">${escapeHtml(locationHeadline)}</h2>
                        ${incidentSubtitle ? `<p class="incident-sheet__subtitle">${escapeHtml(incidentSubtitle)}</p>` : ''}
                        <p class="incident-sheet__summary">${escapeHtml(description)}</p>
                    </div>
                </section>

                <section class="incident-sheet__section">
                    <div class="incident-sheet__section-head">
                        <h3>Overview</h3>
                        <span class="incident-sheet__section-note">Operational details only</span>
                    </div>
                    <div class="incident-sheet__meta-grid">
                        ${overviewMarkup}
                    </div>
                </section>

                <section class="incident-sheet__section">
                    <div class="incident-sheet__section-head">
                        <h3>Incident Notes</h3>
                    </div>
                    <div class="incident-sheet__notes">
                        ${escapeHtml(description)}
                    </div>
                </section>

                ${hasCoordinates ? `
                <section class="incident-sheet__footnote">
                    <span class="incident-sheet__footnote-label"><i class="fas fa-location-crosshairs"></i> Coordinates</span>
                    <span class="incident-sheet__footnote-value">${escapeHtml(String(lat))}, ${escapeHtml(String(lng))}</span>
                </section>
                ` : ''}
            </div>
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
async function respondToIncident(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 1) {
        showToast('Cannot respond to this incident - invalid status', 'error');
        return;
    }
    
    if (!confirm('Confirm responding to this incident? Your status will be updated to RESPONDING.')) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Sending...',
        badgeLabel: 'RESPONDING...'
    });
    
    try {
        const newStatusId = 2;
        const response = await fetch(`/firefighter/incidents/${incidentId}/respond`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'You are now responding to this incident!', 'success');
            await Promise.all([
                fetchAssignedIncidents(true),
                refreshFirefighterLiveMap()
            ]);
            if (typeof window.calculateRouteToIncident === 'function') {
                await window.calculateRouteToIncident(incidentId);
            }
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to update incident status', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error responding to incident:', error);
        showToast('Failed to update incident status', 'error');
    }
}

async function completeIncident(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 3) {
        showToast('Cannot mark controlled until the team is on scene', 'error');
        return;
    }
    
    if (!confirm('Complete this incident and mark it as controlled?')) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Submitting...',
        badgeLabel: 'MARKING CONTROLLED...'
    });
    
    try {
        const newStatusId = 4;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Incident completed and marked as controlled!', 'success');
            await Promise.all([
                fetchAssignedIncidents(true),
                refreshFirefighterLiveMap()
            ]);
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to update incident', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error completing incident:', error);
        showToast('Failed to update incident', 'error');
    }
}

async function closeCase(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 4) {
        showToast('Cannot close this case until the incident is controlled', 'error');
        return;
    }
    
    if (!confirm('Close this case? This action will archive the incident.')) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Closing...',
        badgeLabel: 'CLOSING...'
    });
    
    try {
        const newStatusId = 5;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Case closed successfully!', 'success');
            await Promise.all([
                fetchAssignedIncidents(true),
                refreshFirefighterLiveMap()
            ]);
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to close case', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error closing case:', error);
        showToast('Failed to close case', 'error');
    }
}

async function markIncidentFalseAlarm(incidentId, currentStatusId, triggerButton = null) {
    if (![1, 2].includes(currentStatusId)) {
        showToast('False alarm can only be set while the incident is still active', 'error');
        return;
    }

    const confirmed = await showFalseAlarmConfirmationToast();
    if (!confirmed) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Tagging false alarm...',
        badgeLabel: 'FLAGGING...'
    });

    try {
        const response = await fetch(`/api/incidents/${incidentId}/false-alarm`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message || 'Incident tagged as false alarm', 'success');
            await Promise.all([
                fetchAssignedIncidents(true),
                refreshFirefighterLiveMap()
            ]);
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to tag false alarm', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error tagging false alarm:', error);
        showToast('Failed to tag false alarm', 'error');
    }
}

async function requestIncidentBackup(incidentId, triggerButton = null) {
    if (!confirm('Request another available firetruck and crew? Your station will be checked first, then the nearest available station.')) return;
    const previous = triggerButton?.innerHTML;
    if (triggerButton) { triggerButton.disabled = true; triggerButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Finding unit...</span>'; }
    try {
        const response = await fetch(`/firefighter/incidents/${incidentId}/backup`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No backup unit is available.');
        showToast(result.message, 'success');
        await fetchAssignedIncidents(true);
        await refreshActiveResponseUnits(window.assignedIncidentsData || []);
    } catch (error) {
        showToast(error.message || 'Unable to dispatch backup.', 'error');
    } finally {
        if (triggerButton?.isConnected) { triggerButton.disabled = false; triggerButton.innerHTML = previous; }
    }
}

    async function refreshActiveResponseUnits(incidents = window.assignedIncidentsData || []) {
    const list = document.getElementById('activeResponseUnitsList');
    const count = document.getElementById('activeResponseUnitsCount');
    if (!list || !count) return [];
    const activeIncidents = (Array.isArray(incidents) ? incidents : []).filter(incident => [2, 3].includes(Number(getIncidentStatusId(incident))));
    if (!activeIncidents.length) {
        activeResponseUnitsData = [];
        window.activeResponseUnitsData = [];
        renderActiveResponseUnits([]);
        updateApparatusTrackingState();
        return [];
    }

    const responses = await Promise.allSettled(activeIncidents.map(async incident => {
        const response = await fetch(`/firefighter/incidents/${incident.incident_id}/response-resources`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (!response.ok) return [];
        const payload = await response.json();
        return (payload.units || []).map(unit => ({ ...unit, incident_id: incident.incident_id, incident_title: incident.marker?.title || `Incident #${incident.incident_id}` }));
    }));
    const units = responses.flatMap(result => result.status === 'fulfilled' ? result.value : []).filter(unit => ['reserved', 'en_route', 'on_scene'].includes(unit.status));
    activeResponseUnitsData = units;
    window.activeResponseUnitsData = units;
    renderActiveResponseUnits(units);
    updateApparatusTrackingState();
    flushQueuedApparatusTelemetry();
    return units;
}

function renderActiveResponseUnits(units) {
    const list = document.getElementById('activeResponseUnitsList');
    const count = document.getElementById('activeResponseUnitsCount');
    if (!list || !count) return;
    count.textContent = String(units.length);
    list.innerHTML = units.length ? units.map(unit => {
        const eta = unit.status === 'on_scene' ? 'On scene' : (unit.eta_minutes !== null && unit.eta_minutes !== undefined ? `~${Math.max(0, Math.ceil(Number(unit.eta_minutes)))} min` : 'ETA pending');
        const identity = unit.unit_number || unit.equipment || `Run ${unit.run_number}`;
        const gps = unit.gps_status === 'station_fallback' ? 'station location' : `${String(unit.gps_status || 'offline').replace('_', ' ')} GPS`;
        const members = Array.isArray(unit.team_members) ? unit.team_members : [];
        const apparatusMeta = [unit.plate_number, unit.water_capacity_liters ? `${Number(unit.water_capacity_liters).toLocaleString()} L` : null, `${members.length} crew`].filter(Boolean).join(' / ');
        return `<article class="ff-response-unit">
            <span class="ff-response-unit__icon"><i class="fas fa-truck-fast"></i></span>
            <span class="ff-response-unit__copy"><strong>${escapeHtml(identity)} / Run ${escapeHtml(unit.run_number)}</strong><small>${escapeHtml(apparatusMeta || unit.apparatus_type || 'Registered apparatus')}</small><small>${escapeHtml(unit.station_name || 'Station')} / ${escapeHtml(gps)}</small></span>
            <span class="ff-response-unit__eta"><strong>${escapeHtml(eta)}</strong>${escapeHtml(String(unit.status).replace('_', ' '))}</span>
            ${unit.can_recall ? `<button type="button" class="ff-response-unit__recall" onclick="recallResponseBackup(${Number(unit.incident_id)},${Number(unit.id)})"><i class="fas fa-rotate-left"></i> Recall backup</button>` : ''}
        </article>`;
    }).join('') : '<p>No units currently dispatched.</p>';
    renderResponseUnitMapMarkers(units);
}

function renderResponseUnitMapMarkers(units) {
    if (!window.map || typeof L === 'undefined') return;
    if (!responseUnitLayer || responseUnitLayer._map !== window.map) {
        responseUnitLayer = L.layerGroup().addTo(window.map);
    } else {
        responseUnitLayer.clearLayers();
    }
    units.forEach(unit => {
        const lat = Number(unit.lat); const lng = Number(unit.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        const identity = escapeHtml(unit.unit_number || unit.equipment || `Run ${unit.run_number}`);
        const eta = unit.status === 'on_scene' ? 'On scene' : (unit.eta_minutes !== null ? `ETA ${Math.max(0, Math.ceil(Number(unit.eta_minutes)))} min` : 'ETA pending');
        const members = Array.isArray(unit.team_members) ? unit.team_members : [];
        const memberRows = members.length ? members.map(member => `<div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-top:1px solid #e2e8f0"><span><strong>${escapeHtml(member.name)}</strong>${member.rank ? `<small style="display:block;color:#64748b">${escapeHtml(member.rank)}</small>` : ''}</span><small style="color:#9f231c;text-align:right">${escapeHtml(member.role)}</small></div>`).join('') : '<small style="color:#64748b">No crew roster is attached.</small>';
        const capacity = unit.water_capacity_liters ? `${Number(unit.water_capacity_liters).toLocaleString()} liters` : 'Not recorded';
        const loadout = Array.isArray(unit.loadout) ? unit.loadout : [];
        const loadoutRows = loadout.length ? loadout.map(item => `<div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;border-top:1px solid #e2e8f0"><span>${escapeHtml(item.name || 'Equipment')}</span><strong>×${Number(item.quantity) || 0}</strong></div>`).join('') : '<small style="color:#64748b">No onboard loadout recorded.</small>';
        const gpsSeen = unit.gps_last_seen_at ? new Date(unit.gps_last_seen_at) : null;
        const gpsAge = gpsSeen && !Number.isNaN(gpsSeen.getTime()) ? Math.max(0, Math.round((Date.now() - gpsSeen.getTime()) / 1000)) : null;
        const gpsDetail = unit.gps_status === 'station_fallback' ? 'Shown at station until live apparatus GPS is available.' : `Apparatus GPS: ${escapeHtml(unit.gps_status)}${gpsAge !== null ? ` / ${gpsAge < 60 ? `${gpsAge}s` : `${Math.floor(gpsAge / 60)}m`} ago` : ''}${unit.gps_accuracy_meters !== null && unit.gps_accuracy_meters !== undefined ? ` / ±${Math.round(Number(unit.gps_accuracy_meters))}m` : ''}`;
        const marker = L.marker([lat, lng], { icon: L.divIcon({ className: '', html: '<span class="pyro-response-unit-marker"><i class="fas fa-truck-fast"></i></span>', iconSize: [40, 40], iconAnchor: [20, 20] }) });
        marker.bindPopup(`<div style="min-width:270px;max-width:330px;font-family:system-ui,-apple-system,sans-serif"><div style="margin:-12px -12px 10px;padding:12px 14px;border-radius:12px 12px 0 0;color:#fff;background:linear-gradient(135deg,#b42318,#681512)"><strong style="font-size:15px">${identity}</strong><small style="display:block;margin-top:3px;opacity:.85">Actual dispatched apparatus / Run ${escapeHtml(unit.run_number)}</small></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:9px;font-size:11px"><span><b>Plate</b><br>${escapeHtml(unit.plate_number || 'Not recorded')}</span><span><b>Capacity</b><br>${escapeHtml(capacity)}</span><span><b>Station</b><br>${escapeHtml(unit.station_name || 'Responding station')}</span><span><b>Status / ETA</b><br>${escapeHtml(String(unit.status).replace('_', ' '))} / ${escapeHtml(eta)}</span></div><div style="margin:8px 0 5px;color:#9f231c;font-size:10px;font-weight:800;letter-spacing:.08em">ONBOARD EQUIPMENT</div>${loadoutRows}<div style="margin:8px 0 5px;color:#9f231c;font-size:10px;font-weight:800;letter-spacing:.08em">${escapeHtml(unit.team_label || 'RESPONSE TEAM')}</div>${memberRows}<small style="display:block;margin-top:8px;color:#64748b">${gpsDetail}</small></div>`, { minWidth: 270, maxWidth: 350 });
        marker.addTo(responseUnitLayer);
    });
}

async function publishDriverApparatusLocation(position) {
    if (!position?.coords || !window.currentUserId) return;
    const driverUnits = activeResponseUnitsData.filter(unit => unit.apparatus_id && ['reserved', 'en_route', 'on_scene'].includes(unit.status)
        && Array.isArray(unit.team_members)
        && unit.team_members.some(member => Number(member.user_id) === Number(window.currentUserId) && member.role === 'Commander / Driver'));
    for (const unit of driverUnits) {
        const signature = `${Number(position.coords.latitude).toFixed(5)}:${Number(position.coords.longitude).toFixed(5)}`;
        const previous = apparatusTelemetryState.get(unit.id);
        if (previous?.pending || (previous && Date.now() - previous.sentAt < 5000) || (previous?.signature === signature && Date.now() - previous.sentAt < 15000)) continue;
        apparatusTelemetryState.set(unit.id, { signature, sentAt: Date.now(), pending: true });
        const telemetryPayload = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            speed_kph: Number.isFinite(position.coords.speed) ? position.coords.speed * 3.6 : null,
            heading: Number.isFinite(position.coords.heading) ? position.coords.heading : null,
        };
        try {
            const response = await fetch(`/firefighter/response-units/${unit.id}/apparatus-location`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(telemetryPayload),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const requestError = new Error(payload.message || 'Unable to update apparatus GPS.');
                requestError.canQueue = response.status >= 500 || response.status === 503;
                throw requestError;
            }
            const index = activeResponseUnitsData.findIndex(item => Number(item.id) === Number(unit.id));
            if (index >= 0 && payload.unit) activeResponseUnitsData[index] = { ...activeResponseUnitsData[index], ...payload.unit };
            renderActiveResponseUnits(activeResponseUnitsData);
        } catch (error) {
            console.warn('Apparatus telemetry update failed:', error);
            if (!navigator.onLine || error.canQueue) queueApparatusTelemetry(unit.id, telemetryPayload);
        } finally {
            const latest = apparatusTelemetryState.get(unit.id) || {};
            apparatusTelemetryState.set(unit.id, { ...latest, pending: false });
        }
    }
}

function getQueuedApparatusTelemetry() {
    try { return JSON.parse(localStorage.getItem(APPARATUS_TELEMETRY_QUEUE_KEY) || '[]'); } catch (_) { return []; }
}

function queueApparatusTelemetry(unitId, payload) {
    const queue = getQueuedApparatusTelemetry().filter(item => Number(item.unitId) !== Number(unitId));
    queue.push({ unitId: Number(unitId), payload, queuedAt: new Date().toISOString() });
    localStorage.setItem(APPARATUS_TELEMETRY_QUEUE_KEY, JSON.stringify(queue.slice(-100)));
    updateApparatusTrackingState();
}

async function flushQueuedApparatusTelemetry() {
    if (!navigator.onLine || apparatusTelemetryFlushInFlight) return;
    const queue = getQueuedApparatusTelemetry();
    if (!queue.length) return;
    apparatusTelemetryFlushInFlight = true;
    const remaining = [];
    for (const item of queue) {
        try {
            const response = await fetch(`/firefighter/response-units/${item.unitId}/apparatus-location`, {
                method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(item.payload),
            });
            if (!response.ok && ![403, 404, 409, 422].includes(response.status)) remaining.push(item);
        } catch (_) { remaining.push(item); }
    }
    localStorage.setItem(APPARATUS_TELEMETRY_QUEUE_KEY, JSON.stringify(remaining.slice(-100)));
    apparatusTelemetryFlushInFlight = false;
    updateApparatusTrackingState();
}

function currentUserIsActiveApparatusDriver() {
    return activeResponseUnitsData.some(unit => unit.apparatus_id && ['reserved', 'en_route', 'on_scene'].includes(unit.status)
        && Array.isArray(unit.team_members) && unit.team_members.some(member => Number(member.user_id) === Number(window.currentUserId) && member.role === 'Commander / Driver'));
}

async function updateApparatusTrackingState() {
    const state = document.getElementById('apparatusTrackingState');
    const wrapper = state?.closest('.ff-apparatus-tracking');
    const install = document.getElementById('apparatusTrackingInstall');
    if (!state || !wrapper) return;
    const isDriver = currentUserIsActiveApparatusDriver();
    const queued = getQueuedApparatusTelemetry().length;
    wrapper.classList.toggle('is-live', isDriver && navigator.onLine);
    wrapper.classList.toggle('is-queued', queued > 0);
    state.innerHTML = queued > 0
        ? `<i class="fas fa-cloud-arrow-up"></i> ${queued} GPS update${queued === 1 ? '' : 's'} queued offline`
        : (isDriver ? `<i class="fas fa-satellite-dish"></i> Live apparatus GPS active${apparatusWakeLock ? ' / screen awake' : ''}` : '<i class="fas fa-location-crosshairs"></i> Waiting for assigned driver run');
    const standalone = firefighterPwaIsStandalone();
    if (install) install.hidden = standalone || !window.laravelPwaInstall?.canInstall?.();
    if (isDriver && document.visibilityState === 'visible' && 'wakeLock' in navigator && !apparatusWakeLock) {
        try {
            apparatusWakeLock = await navigator.wakeLock.request('screen');
            apparatusWakeLock.addEventListener('release', () => { apparatusWakeLock = null; updateApparatusTrackingState(); });
        } catch (error) { console.warn('Screen wake lock unavailable:', error); }
    } else if (!isDriver && apparatusWakeLock) {
        await apparatusWakeLock.release().catch(() => {});
        apparatusWakeLock = null;
    }
}

document.getElementById('apparatusTrackingInstall')?.addEventListener('click', () => window.laravelPwaInstall?.showPrompt?.());
window.addEventListener('online', flushQueuedApparatusTelemetry);
window.addEventListener('pwa-installable', updateApparatusTrackingState);

async function recallResponseBackup(incidentId, unitId) {
    const reason = window.prompt('Reason for recalling this backup?', 'Fire is controlled / backup is no longer required.');
    if (reason === null) return;
    try {
        const response = await fetch(`/firefighter/incidents/${incidentId}/response-resources/${unitId}/recall`, {
            method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ reason })
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Unable to recall backup.');
        showToast(payload.message || 'Backup recalled.', 'success');
        await Promise.all([fetchAssignedIncidents(true), refreshActiveResponseUnits(window.assignedIncidentsData || [])]);
    } catch (error) {
        showToast(error.message || 'Unable to recall backup.', 'error');
    }
}

window.refreshActiveResponseUnits = refreshActiveResponseUnits;
window.recallResponseBackup = recallResponseBackup;

async function refreshFirefighterLiveMap() {
    if (typeof window.refreshMapMarkers !== 'function') {
        return;
    }

    try {
        await window.refreshMapMarkers({
            force: true,
            forceIncidents: true
        });
    } catch (error) {
        // The status change succeeded; background map synchronization can retry on its normal poll.
        console.warn('Unable to refresh the live map after an incident update:', error);
    }
}

function showFalseAlarmConfirmationToast() {
    return new Promise((resolve) => {
        const existing = document.getElementById('falseAlarmConfirmToast');
        if (existing) {
            existing.remove();
        }

        const previouslyFocused = document.activeElement;

        const toast = document.createElement('div');
        toast.id = 'falseAlarmConfirmToast';
        toast.className = 'ff-false-alarm-confirm';

        toast.innerHTML = `
            <div class="ff-false-alarm-confirm__card" role="dialog" aria-modal="true" aria-labelledby="falseAlarmConfirmTitle">
                <div class="ff-false-alarm-confirm__body">
                    <div class="ff-false-alarm-confirm__icon" aria-hidden="true">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div id="falseAlarmConfirmTitle" class="ff-false-alarm-confirm__title">Flag as false alarm?</div>
                        <div class="ff-false-alarm-confirm__copy">
                            This will remove the incident from active response handling.
                        </div>
                    </div>
                </div>
                <div class="ff-false-alarm-confirm__actions">
                    <button type="button" id="falseAlarmCancelBtn" class="ff-false-alarm-confirm__button ff-false-alarm-confirm__button--cancel">
                        Cancel
                    </button>
                    <button type="button" id="falseAlarmConfirmBtn" class="ff-false-alarm-confirm__button ff-false-alarm-confirm__button--confirm">
                        Confirm
                    </button>
                </div>
            </div>
        `;

        const cleanup = (value) => {
            if (toast.parentNode) {
                toast.remove();
            }
            previouslyFocused?.focus?.();
            resolve(value);
        };

        document.body.appendChild(toast);

        toast.querySelector('#falseAlarmCancelBtn')?.addEventListener('click', () => cleanup(false));
        toast.querySelector('#falseAlarmConfirmBtn')?.addEventListener('click', () => cleanup(true));
        toast.querySelector('#falseAlarmConfirmBtn')?.focus();
        toast.addEventListener('click', (event) => {
            if (event.target === toast) {
                cleanup(false);
            }
        });

        setTimeout(() => {
            if (document.body.contains(toast)) {
                cleanup(false);
            }
        }, 10000);
    });
}
    
    // Firefighter actions use one concise themed notification on every screen size.
    function showToast(message, type = 'info') {
        const existing = document.getElementById('firefighterAppNotice');
        if (existing) {
            existing.remove();
        }

        const normalized = String(message || '').toLowerCase();
        const conciseMessage = type === 'success'
            ? (normalized.includes('false alarm') ? 'False alarm recorded.'
                : normalized.includes('respond') ? 'Response started.'
                : normalized.includes('controlled') ? 'Incident marked controlled.'
                : normalized.includes('closed') ? 'Case closed.'
                : 'Update saved.')
            : (type === 'error' ? 'Could not update this incident. Please try again.' : String(message || 'Update in progress.'));

        const icons = {
            success: 'fa-check',
            error: 'fa-triangle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-bolt'
        };
        const notice = document.createElement('div');
        notice.id = 'firefighterAppNotice';
        notice.className = `ff-app-notice ff-app-notice--${type}`;
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        notice.innerHTML = `<span class="ff-app-notice__icon" aria-hidden="true"><i class="fas ${icons[type] || icons.info}"></i></span><span class="ff-app-notice__message"></span><button type="button" class="ff-app-notice__close" aria-label="Dismiss notification"><i class="fas fa-xmark" aria-hidden="true"></i></button>`;
        notice.querySelector('.ff-app-notice__message').textContent = conciseMessage;
        document.body.appendChild(notice);

        requestAnimationFrame(() => notice.classList.add('is-visible'));
        const dismiss = () => {
            notice.classList.remove('is-visible');
            window.setTimeout(() => notice.remove(), 180);
        };

        notice.querySelector('.ff-app-notice__close')?.addEventListener('click', dismiss);
        window.setTimeout(dismiss, type === 'error' ? 5200 : 3600);
    }
    
    function paintCurrentUserLocation(lat, lng) {
        if (!window.map || typeof L === 'undefined') {
            return;
        }

        if (window.userMarker) {
            window.map.removeLayer(window.userMarker);
        }

        window.userMarker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'user-location-marker',
                html: `<div style="
                    width: 24px;
                    height: 24px;
                    background-color: #3b82f6;
                    border: 3px solid white;
                    border-radius: 50%;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    animation: pulse 2s infinite;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            })
        }).bindPopup("You are here").addTo(window.map);

        if (window.userAccuracyCircle) {
            window.map.removeLayer(window.userAccuracyCircle);
        }

        window.userAccuracyCircle = L.circle([lat, lng], {
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.1,
            radius: 50
        }).addTo(window.map);
    }

    function initializeFirefighterGeolocation() {
        if (!navigator.geolocation) {
            console.warn("Geolocation not supported");
            updateUserLocation(window.currentUserId, 15.8222, 120.3297);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateUserLocation(window.currentUserId, lat, lng);
                paintCurrentUserLocation(lat, lng);
                sendUserLocationToServer(lat, lng);
                publishDriverApparatusLocation(position);
                document.dispatchEvent(new Event("locationReady"));
            },
            function(error) {
                console.error("Location error:", error);
                updateUserLocation(window.currentUserId, 15.8222, 120.3297);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function hydrateDashboardFromCache() {
        const cachedWidgets = readDashboardCache(FF_WIDGET_CACHE_KEY, null);
        if (cachedWidgets) {
            applyDashboardWidgetPayload(cachedWidgets);
        }

        const cachedAssigned = readDashboardCache(FF_ASSIGNED_CACHE_KEY, null);
        if (Array.isArray(cachedAssigned) && cachedAssigned.length > 0) {
            window.assignedIncidentsData = cachedAssigned;
            displayAssignedIncidents(cachedAssigned);
            syncFirefighterAssignedFabCount(cachedAssigned);
            document.getElementById('assignedIncidentsCount').textContent = getAssignedIncidentQueue(cachedAssigned).length;
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(cachedAssigned);
            }
        }

        const cachedPosts = readDashboardCache(FF_POSTS_CACHE_KEY, null);
        if (Array.isArray(cachedPosts)) {
            renderDashboardAnnouncements(cachedPosts);
        }
    }

    function startDeferredMapHydration() {
        const loadState = document.getElementById('firefighterMapLoadState');
        const mapElement = document.getElementById('firefighterDashboardMap');

        const ensureMapVisible = function () {
            if (!mapElement || !window.map || typeof window.map.invalidateSize !== 'function') {
                return;
            }

            mapElement.classList.add('is-firefighter-map-ready');
            window.map.invalidateSize();
            window.setTimeout(() => window.map?.invalidateSize?.(), 180);
            window.setTimeout(() => window.map?.invalidateSize?.(), 420);
            window.setTimeout(() => window.map?.invalidateSize?.(), 900);
        };

        window.refreshFirefighterDashboardMap = ensureMapVisible;

        if (typeof window.initializePyroMapRuntime === 'function') {
            window.initializePyroMapRuntime();
        }

        window.requestAnimationFrame(ensureMapVisible);
        window.setTimeout(ensureMapVisible, 120);
        window.setTimeout(ensureMapVisible, 360);
        window.addEventListener('resize', ensureMapVisible);
        window.addEventListener('orientationchange', ensureMapVisible);
        window.addEventListener('pageshow', ensureMapVisible);

        window.setTimeout(async function () {
            if (loadState) {
                loadState.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Refreshing map layers...</span>';
            }

            try {
                initializeFirefighterGeolocation();
                await Promise.allSettled([
                    typeof fetchAllFirefighterLocations === 'function' ? fetchAllFirefighterLocations() : Promise.resolve(),
                    typeof window.refreshMapMarkers === 'function'
                        ? window.refreshMapMarkers({ preferSeeded: true, revalidate: true, force: true })
                        : Promise.resolve(),
                    typeof loadShapes === 'function' ? loadShapes() : Promise.resolve(),
                ]);
            } finally {
                if (loadState) {
                    loadState.innerHTML = '<i class="fas fa-check-circle"></i><span>Map layers synced.</span>';
                    window.setTimeout(() => loadState.style.display = 'none', 2200);
                }
            }
        }, 180);
    }

    async function bootstrapFirefighterDashboard() {
        hydrateDashboardFromCache();
        startDeferredMapHydration();

        await fetchAssignedIncidents(true);
        await loadFirefighterDashboardWidgets(true);
        window.setTimeout(() => {
            loadFirefighterDashboardPosts();
        }, 320);

        window.setTimeout(() => {
            window.__ffAllowDashboardPrefetch = true;
            if (typeof scheduleDashboardRoutePrefetch === 'function') {
                scheduleDashboardRoutePrefetch();
            }
        }, 1500);

        window.setTimeout(() => {
            window.runPendingIncidentAutoAssignment();
            startFirefighterDashboardIntervals();
        }, 2200);
    }

    // Sidebar toggle and location initialization
    document.addEventListener('DOMContentLoaded', function() {
        window.userCoords = {
            currentLatitude: null,
            currentLongitude: null,
            ready: false
        };
        window.incidentsData = [];
        
        const sidebar = document.querySelector('.sidebar');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('open');
                backdrop.classList.remove('hidden');
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        const refreshBtn = document.getElementById('refreshAssignedIncidents');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                fetchAssignedIncidents(true);
            });
        }

        const firefighterDashboardVisibilityHandler = function () {
            updateApparatusTrackingState();
            if (firefighterDashboardIsVisible()) {
                fetchAssignedIncidents(true);
                if (typeof fetchAllFirefighterLocations === 'function') {
                    fetchAllFirefighterLocations();
                }
                startFirefighterDashboardIntervals();
                return;
            }

            clearFirefighterDashboardIntervals(firefighterPwaIsStandalone() && currentUserIsActiveApparatusDriver());
        };

        bootstrapFirefighterDashboard();
        window.__ffCurrentPageCleanup = function () {
            clearFirefighterDashboardIntervals();
            document.removeEventListener('visibilitychange', firefighterDashboardVisibilityHandler);
            window.__ffAllowDashboardPrefetch = false;
            if (typeof window.destroyPyroMapRuntime === 'function') {
                window.destroyPyroMapRuntime();
            }
            if (typeof window.closeFirefighterInsightsPanel === 'function') {
                window.closeFirefighterInsightsPanel();
            }
        };

        document.addEventListener('visibilitychange', firefighterDashboardVisibilityHandler);
        
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.dataset.status;
                const statusDot = document.querySelector('.status-dot');
                const statusButton = document.querySelector('.bg-blue-600');
                
                if (status === 'on-duty') {
                    statusDot.className = 'status-dot online';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-circle"></i><span class="hidden sm:inline">On Duty</span>';
                } else if (status === 'on-break') {
                    statusDot.className = 'status-dot away';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-mug-hot"></i><span class="hidden sm:inline">On Break</span>';
                } else {
                    statusDot.className = 'status-dot offline';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-clock"></i><span class="hidden sm:inline">Off Duty</span>';
                }
                
                document.getElementById('statusModal').classList.remove('active');
                
                fetch('/api/firefighter/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: status })
                });
            });
        });
    });
    // Chart instances
let incidentStatusChart = null;
let responseTimeChart = null;
let monthlyIncidentsChart = null;
let priorityChart = null;
let performanceChart = null;

function getLastSevenDayLabels() {
    const labels = [];
    for (let index = 6; index >= 0; index--) {
        const date = new Date();
        date.setDate(date.getDate() - index);
        labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
    }
    return labels;
}

function getOperationalChartData(incidents = []) {
    const safeIncidents = Array.isArray(incidents) ? incidents : [];
    const statusCounts = {
        reported: 0,
        responding: 0,
        controlled: 0,
        closed: 0
    };
    const last7Labels = getLastSevenDayLabels();
    const last7Counts = new Array(7).fill(0);
    const monthWeeks = [0, 0, 0, 0];
    const today = new Date();
    const month = today.getMonth();
    const year = today.getFullYear();

    safeIncidents.forEach(function (incident) {
        const statusId = getIncidentStatusId(incident);
        if (statusId === 1) {
            statusCounts.reported += 1;
        } else if (statusId === 2) {
            statusCounts.responding += 1;
        } else if ([3, 4].includes(statusId)) {
            statusCounts.controlled += 1;
        } else if (statusId >= 5) {
            statusCounts.closed += 1;
        }

        const createdAt = new Date(incident.created_at || incident.reported_at || incident.updated_at);
        if (Number.isNaN(createdAt.getTime())) {
            return;
        }

        const dayDiff = Math.floor((new Date(today.getFullYear(), today.getMonth(), today.getDate()) - new Date(createdAt.getFullYear(), createdAt.getMonth(), createdAt.getDate())) / 86400000);
        if (dayDiff >= 0 && dayDiff < 7) {
            last7Counts[6 - dayDiff] += 1;
        }

        if (createdAt.getMonth() === month && createdAt.getFullYear() === year) {
            const weekIndex = Math.min(Math.floor((createdAt.getDate() - 1) / 7), 3);
            monthWeeks[weekIndex] += 1;
        }
    });

    const activeResponse = statusCounts.responding;
    const completed = statusCounts.controlled + statusCounts.closed;
    const needsReport = safeIncidents.filter(function (incident) {
        const statusId = getIncidentStatusId(incident);
        return Boolean(incident.investigation_ready || incident.ready_for_investigation || (statusId === 4 && !incident.has_spot_report));
    }).length;

    return {
        statusCounts,
        last7Labels,
        last7Counts,
        monthWeeks,
        actionSummary: [safeIncidents.length, activeResponse, completed, needsReport]
    };
}

// Function to initialize all charts
function initializeCharts() {
    const statusCtx = document.getElementById('incidentStatusChart')?.getContext('2d');
    if (statusCtx) {
        incidentStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reported', 'Responding', 'Controlled', 'Closed'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#ef4444', '#2563eb', '#16a34a', '#64748b'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11, weight: 700 }, usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} incidents` } }
                }
            }
        });
    }

    const responseCtx = document.getElementById('responseTimeChart')?.getContext('2d');
    if (responseCtx) {
        responseTimeChart = new Chart(responseCtx, {
            type: 'line',
            data: {
                labels: getLastSevenDayLabels(),
                datasets: [{
                    label: 'Assigned calls',
                    data: [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#c63d26',
                    backgroundColor: 'rgba(198, 61, 38, 0.14)',
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#c63d26',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => `${context.raw} assigned calls` } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11, weight: 700 } } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.2)' } }
                }
            }
        });
    }

    // Monthly Incidents Chart (Bar)
    const monthlyCtx = document.getElementById('monthlyIncidentsChart')?.getContext('2d');
    if (monthlyCtx) {
        monthlyIncidentsChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Incidents',
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#f472b6', '#8b5cf6', '#38bdf8', '#f59e0b'],
                    borderRadius: 12,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => `${context.raw} incidents` } }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11, weight: 600 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } },
                        grid: { color: 'rgba(148, 163, 184, 0.25)', drawBorder: false }
                    }
                }
            }
        });
    }


    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    if (performanceCtx) {
        performanceChart = new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: ['Assigned', 'Active', 'Completed', 'For report'],
                datasets: [{
                    label: 'Action count',
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#c63d26', '#2563eb', '#16a34a', '#d18a16'],
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw}` } }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.22)' }
                    },
                    y: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11, weight: 700 } } }
                }
            }
        });
    }
}

// Function to update all charts with incident data
function updateChartsWithData(incidents) {
    const chartData = getOperationalChartData(incidents);
    const statusCounts = chartData.statusCounts;

    if (incidentStatusChart) {
        incidentStatusChart.data.datasets[0].data = [statusCounts.reported, statusCounts.responding, statusCounts.controlled, statusCounts.closed];
        incidentStatusChart.update('none');
    }
    
    if (responseTimeChart) {
        responseTimeChart.data.labels = chartData.last7Labels;
        responseTimeChart.data.datasets[0].data = chartData.last7Counts;
        responseTimeChart.update('none');
    }

    if (monthlyIncidentsChart) {
        monthlyIncidentsChart.data.datasets[0].data = chartData.monthWeeks;
        monthlyIncidentsChart.update('none');
    }
    
    if (performanceChart) {
        performanceChart.data.datasets[0].data = chartData.actionSummary;
        performanceChart.update('none');
    }
}

// Add this to your displayAssignedIncidents function to update charts
// Call updateChartsWithData(incidents) after displaying incidents

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // User Profile Dropdown Toggle
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }

    // Sidebar Mobile Toggle
    const openSidebarBtn = document.getElementById('openSidebar');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    
    if (openSidebarBtn && sidebar && sidebarBackdrop) {
        openSidebarBtn.addEventListener('click', function() {
            sidebar.classList.add('active');
            sidebarBackdrop.classList.remove('hidden');
        });
        
        sidebarBackdrop.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarBackdrop.classList.add('hidden');
        });
    }
});

    window.runPendingIncidentAutoAssignment = async function() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            return;
        }

        try {
            ffAutoAssignRequest = fetchFirefighterDashboardOnce(ffAutoAssignRequest, '/api/incidents/auto-assign-recent-unassigned', {
                method: 'POST',
                timeoutMs: 20000,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
        const response = await ffAutoAssignRequest;
        const result = await response.json().catch(() => ({}));
        if (result?.success && Number(result.assigned) > 0) {
            await fetchAssignedIncidents(true);
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            console.warn('Background incident auto-assignment request timed out before completion.');
            return;
        }
        console.error('Background incident auto-assignment failed:', error);
    } finally {
        ffAutoAssignRequest = null;
    }
};
document.addEventListener('DOMContentLoaded', function () {
    const mapLayersPanel = document.querySelector('[data-ff-map-layers-panel]');
    const closeButtons = document.querySelectorAll('[data-ff-map-layers-close]');
    const baseInputs = document.querySelectorAll('[data-ff-map-base-toggle]');
    const layerInputs = document.querySelectorAll('[data-ff-map-layer-toggle]');
    const mobileLayerButtons = document.querySelectorAll('[data-ff-mobile-map-layers-button]');
    const mobileLayerCountBadges = document.querySelectorAll('[data-ff-mobile-map-layers-count]');
    let lastMobileLayerControlTap = 0;

    function syncFirefighterMapLayersPanel() {
        if (typeof window.syncPyroSharedMapLayerControls === 'function') {
            window.syncPyroSharedMapLayerControls();
        }
        syncFirefighterMobileLayerButtonBadge();
    }

    function syncFirefighterMobileLayerButtonBadge() {
        const activeCount = Array.from(layerInputs).filter(function (input) {
            return input.checked;
        }).length;

        mobileLayerCountBadges.forEach(function (badge) {
            badge.textContent = String(activeCount);
            badge.hidden = activeCount === 0;
        });
    }

    function closeFirefighterMobileDockBurst() {
        document.querySelector('[data-ff-mobile-dock]')?.classList.remove('is-burst-open');
    }

    function closeFirefighterMobileUtilityPanels() {
        document.querySelectorAll('[data-ff-notifications-panel], [data-ff-settings-panel]').forEach(function (panel) {
            if (panel.contains(document.activeElement)) {
                document.activeElement?.blur?.();
            }

            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        });
    }

    function openFirefighterMapLayersPanel() {
        if (!mapLayersPanel || window.innerWidth > 768) {
            return;
        }

        closeFirefighterMobileDockBurst();
        closeFirefighterMobileUtilityPanels();

        if (typeof window.closeFirefighterInsightsPanel === 'function') {
            window.closeFirefighterInsightsPanel();
        }

        syncFirefighterMapLayersPanel();
        mapLayersPanel.removeAttribute('inert');
        mapLayersPanel.setAttribute('aria-hidden', 'false');
        mapLayersPanel.classList.add('is-open');
    }

    function closeFirefighterMapLayersPanel() {
        if (!mapLayersPanel) {
            return;
        }

        if (mapLayersPanel.contains(document.activeElement)) {
            document.activeElement?.blur?.();
        }

        mapLayersPanel.classList.remove('is-open');
        mapLayersPanel.setAttribute('aria-hidden', 'true');
        mapLayersPanel.setAttribute('inert', '');
    }

    function bindLeafletLayerTrigger() {
        document.querySelectorAll('.firefighter-map-stage .leaflet-control-layers').forEach(function (control) {
            const trigger = control.querySelector('.leaflet-control-layers-toggle') || control;
            if (!trigger || control.dataset.ffSheetTriggerBound === 'true') {
                return;
            }

            const openFromLeafletControl = function (event) {
                if (window.innerWidth > 768) {
                    return;
                }

                event.__ffMapLayerControlHandled = true;
                event.preventDefault();
                event.stopImmediatePropagation();
                event.stopPropagation();
                control.classList.remove('leaflet-control-layers-expanded');
                openFirefighterMapLayersPanel();
            };

            trigger.addEventListener('click', openFromLeafletControl, true);
            trigger.addEventListener('pointerdown', openFromLeafletControl, true);
            trigger.addEventListener('touchstart', openFromLeafletControl, { capture: true, passive: false });
            control.dataset.ffSheetTriggerBound = 'true';
        });
    }

    function interceptLeafletLayerControl(event) {
        if (window.innerWidth > 768 || !mapLayersPanel) {
            return;
        }

        const target = event.target;
        const control = target?.closest?.('.firefighter-map-stage .leaflet-control-layers');

        if (!control) {
            return;
        }

        event.__ffMapLayerControlHandled = true;
        event.preventDefault();
        event.stopImmediatePropagation?.();
        event.stopPropagation();

        if (event.type === 'click' && Date.now() - lastMobileLayerControlTap < 450) {
            return;
        }

        lastMobileLayerControlTap = Date.now();
        control.classList.remove('leaflet-control-layers-expanded');
        openFirefighterMapLayersPanel();
    }

    window.openFirefighterMapLayersPanel = openFirefighterMapLayersPanel;
    window.closeFirefighterMapLayersPanel = closeFirefighterMapLayersPanel;

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeFirefighterMapLayersPanel);
    });

    mobileLayerButtons.forEach(function (button) {
        const openFromButton = function (event) {
            if (window.innerWidth > 768) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation?.();
            event.stopPropagation();
            openFirefighterMapLayersPanel();
        };

        button.addEventListener('pointerdown', openFromButton, true);
        button.addEventListener('touchstart', openFromButton, { capture: true, passive: false });
        button.addEventListener('click', openFromButton, true);
    });

    baseInputs.forEach(function (input) {
        if (input.dataset.ffLayerBound === 'true') {
            return;
        }

        input.addEventListener('change', function () {
            if (!input.checked || typeof window.setPyroMapBaseLayer !== 'function') {
                return;
            }

            window.setPyroMapBaseLayer(input.dataset.ffMapBaseToggle);
        });
        input.dataset.ffLayerBound = 'true';
    });

    layerInputs.forEach(function (input) {
        if (input.dataset.ffLayerBound === 'true') {
            return;
        }

        input.addEventListener('change', function () {
            if (typeof window.setPyroMapOverlayVisibility !== 'function') {
                return;
            }

            window.setPyroMapOverlayVisibility(input.dataset.ffMapLayerToggle, input.checked);
            syncFirefighterMobileLayerButtonBadge();
        });
        input.dataset.ffLayerBound = 'true';
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && mapLayersPanel?.classList.contains('is-open')) {
            closeFirefighterMapLayersPanel();
        }
    });

    document.addEventListener('pointerdown', interceptLeafletLayerControl, true);
    document.addEventListener('touchstart', interceptLeafletLayerControl, { capture: true, passive: false });
    document.addEventListener('click', interceptLeafletLayerControl, true);

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeFirefighterMapLayersPanel();
        }
    });

    bindLeafletLayerTrigger();
    window.setTimeout(bindLeafletLayerTrigger, 150);
    window.setTimeout(bindLeafletLayerTrigger, 500);
    window.addEventListener('load', bindLeafletLayerTrigger);
    syncFirefighterMobileLayerButtonBadge();
});
document.addEventListener('DOMContentLoaded', function () {
    const assignedFab = document.getElementById('firefighterMobileAssignedFab');
    const welcomeToast = document.getElementById('firefighterMobileWelcomeToast');
    const assignedQueueModal = document.getElementById('firefighterAssignedQueueModal');
    const assignedQueueModalContent = document.getElementById('firefighterAssignedQueueModalContent');
    const assignedQueueModalRefresh = document.getElementById('refreshAssignedIncidentsModal');
    const assignedQueueCloseButtons = document.querySelectorAll('[data-ff-assigned-queue-close]');
    const assignedQueueSection = document.querySelector('.assigned-incidents-container[data-ff-insights-section="assigned"]');
    let assignedQueuePlaceholder = null;

    function moveAssignedQueueIntoModal() {
        if (!assignedQueueSection || !assignedQueueModalContent) {
            return;
        }

        if (!assignedQueuePlaceholder) {
            assignedQueuePlaceholder = document.createElement('div');
            assignedQueuePlaceholder.dataset.ffAssignedQueuePlaceholder = 'true';
            assignedQueueSection.parentNode?.insertBefore(assignedQueuePlaceholder, assignedQueueSection);
        }

        assignedQueueSection.classList.add('is-in-modal');
        assignedQueueModalContent.appendChild(assignedQueueSection);
    }

    function restoreAssignedQueueFromModal() {
        if (!assignedQueueSection || !assignedQueuePlaceholder?.parentNode) {
            return;
        }

        assignedQueuePlaceholder.parentNode.insertBefore(assignedQueueSection, assignedQueuePlaceholder);
        assignedQueueSection.classList.remove('is-in-modal');
        assignedQueuePlaceholder.remove();
        assignedQueuePlaceholder = null;
    }

    function openAssignedQueueModal() {
        if (!assignedQueueModal) {
            return;
        }

        if (typeof window.closeFirefighterMapLayersPanel === 'function') {
            window.closeFirefighterMapLayersPanel();
        }
        if (typeof window.closeFirefighterInsightsPanel === 'function') {
            window.closeFirefighterInsightsPanel();
        }

        moveAssignedQueueIntoModal();
        assignedQueueModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('menu-open');
    }

    function closeAssignedQueueModal() {
        if (!assignedQueueModal) {
            return;
        }

        restoreAssignedQueueFromModal();
        assignedQueueModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-open');
    }

    window.openFirefighterAssignedQueueModal = openAssignedQueueModal;
    window.closeFirefighterAssignedQueueModal = closeAssignedQueueModal;

    assignedQueueCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeAssignedQueueModal);
    });

    assignedFab?.addEventListener('click', function () {
        openAssignedQueueModal();
    });

    assignedQueueModalRefresh?.addEventListener('click', function () {
        fetchAssignedIncidents(true);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && assignedQueueModal?.getAttribute('aria-hidden') === 'false') {
            closeAssignedQueueModal();
        }
    });

    syncFirefighterAssignedFabCount(window.assignedIncidentsData || []);

    if (welcomeToast) {
        window.setTimeout(function () {
            welcomeToast.classList.add('is-visible');
            window.setTimeout(function () {
                welcomeToast.classList.remove('is-visible');
            }, window.innerWidth <= 768 ? 3200 : 2800);
        }, 260);
    }
});
}
