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
    const FF_AUTO_ASSIGN_INTERVAL_MS = 300000;
    const FF_WIDGET_CACHE_KEY = `ff_dashboard_widgets_${window.currentFirefighterId}`;
    const FF_POSTS_CACHE_KEY = `ff_dashboard_posts_${window.currentFirefighterId}`;
    const FF_ASSIGNED_CACHE_KEY = `ff_dashboard_assigned_${window.currentFirefighterId}`;
    let ffLocationPushInterval = null;
    let ffLocationPullInterval = null;
    let ffAssignedIncidentsInterval = null;
    let ffAutoAssignInterval = null;
    let ffDashboardWidgetsRequest = null;
    let ffDashboardPostsRequest = null;
    const FF_FETCH_TIMEOUT_MS = 10000;
    const FF_ASSIGNED_INCIDENTS_TIMEOUT_MS = 20000;
    let ffAssignedIncidentsRequest = null;
    let ffAutoAssignRequest = null;
    let ffAssignedIncidentsLastFetchedAt = 0;
    function firefighterDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function clearFirefighterDashboardIntervals() {
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
    }

    function startFirefighterDashboardIntervals() {
        clearFirefighterDashboardIntervals();

        ffLocationPushInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateUserLocation(window.currentUserId, lat, lng);
                    sendUserLocationToServer(lat, lng);

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

        target.querySelector('[data-retry-action]')?.addEventListener('click', function () {
            const actionName = this.getAttribute('data-retry-action');
            if (actionName && typeof window[actionName] === 'function') {
                window[actionName](true);
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

        container.innerHTML = posts.map((post) => `
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 mb-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">${escapeHtml(post.author_name || 'Station Update')}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">${escapeHtml(post.time || 'Just now')}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-[11px] font-bold text-orange-700 border border-orange-100">
                        <i class="fas fa-bullhorn"></i> Feed
                    </span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">${escapeHtml(post.content || 'No content')}</p>
            </article>
        `).join('');
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
        document.getElementById('activeIncidentsCount').textContent = counts.active_incidents ?? '--';
        document.getElementById('equipmentCount').textContent = counts.equipment ?? '--';
        document.getElementById('crewOnlineCount').textContent = counts.crew_online ?? '--';

        renderDashboardNearbyStations(payload.stations || []);
        renderDashboardCrew(payload.crew || []);
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
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(incidents);
            }
            
            document.getElementById('assignedIncidentsCount').textContent = incidents.length;
            
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
                window.stopAllSimulations();
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
            if (!button.classList.contains('view-incident-details')) {
                button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${options.buttonLabel || 'Updating...'}`;
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
    
    if (!incidents || incidents.length === 0) {
        container.innerHTML = `
            <div class="empty-incidents">
                <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                <p>No assigned incidents</p>
                <p class="text-sm">You haven't been assigned to any incidents yet.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    incidents.forEach(incident => {
        const statusId = getIncidentStatusId(incident);
        let statusClass = 'status-reported';
        let statusText = 'REPORTED';
        let actionButton = '';
        let falseAlarmButton = '';
        
        if (statusId == 1) {
            statusClass = 'status-reported';
            statusText = 'REPORTED';
            actionButton = `
                <button class="btn btn-success respond-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-truck"></i> Respond Now
                </button>
            `;
        } else if (statusId == 2) {
            statusClass = 'status-responding';
            statusText = 'RESPONDING';
            actionButton = `
                <button class="btn btn-outline" disabled style="opacity: 0.7; cursor: default;">
                    <i class="fas fa-truck"></i> En Route
                </button>
            `;
        } else if (statusId == 3) {
            statusClass = 'status-resolved';
            statusText = 'ON SCENE';
            actionButton = `
                <button class="btn btn-warning complete-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}" style="background: #f59e0b; color: white;">
                    <i class="fas fa-check-circle"></i> Complete Incident
                </button>
            `;
        } else if (statusId == 4) {
            statusClass = 'status-resolved';
            statusText = 'CONTROLLED';
            actionButton = `
                <button class="btn btn-info close-case" data-incident-id="${incident.incident_id}" data-status="${statusId}" style="background: #8b5cf6; color: white;">
                    <i class="fas fa-folder"></i> Close Case
                </button>
            `;
        } else if (statusId == 5) {
            statusClass = 'status-closed';
            statusText = 'CLOSED';
            actionButton = `
                <button class="btn btn-secondary" disabled style="background: #6b7280; color: white; opacity: 0.6; cursor: not-allowed;">
                    <i class="fas fa-folder"></i> Case Closed
                </button>
            `;
        }

        if ([1, 2].includes(parseInt(statusId))) {
            falseAlarmButton = `
                <button class="btn btn-danger false-alarm-incident" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-exclamation-triangle"></i> False Alarm
                </button>
            `;
        }
        
        const incidentType = getIncidentTypeLabel(incident);
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
        const locationLabel = hasCoordinates ? `${lat}, ${lng}` : 'Location not specified';
        const imageMarkup = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(incidentTitle)}" onerror="this.remove(); this.parentElement.classList.add('no-image'); this.parentElement.innerHTML = '<div class=&quot;incident-media-fallback&quot;><i class=&quot;fas ${incidentTypeIcon}&quot;></i><span>No Image</span></div>';">`
            : `<div class="incident-media-fallback"><i class="fas ${incidentTypeIcon}"></i><span>No Image</span></div>`;
        
        html += `
            <div class="incident-card medium">
                <div class="incident-card-layout">
                    <div class="incident-media">
                        ${imageMarkup}
                    </div>
                    <div class="incident-main">
                        <div class="incident-topline">
                            <span class="incident-type">
                                <i class="fas ${incidentTypeIcon}"></i> ${escapeHtml(incidentType)}
                            </span>
                            <span class="incident-priority priority-medium">Assigned</span>
                        </div>
                        <div class="incident-title-wrap">
                            <h3 class="incident-title">${escapeHtml(incidentTitle)}</h3>
                            <div class="incident-subtitle">${escapeHtml(description)}</div>
                        </div>
                        <div class="incident-detail-grid incident-detail-grid-compact">
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-map-marker-alt"></i> Location</span>
                                <span class="incident-detail-value-inline">${escapeHtml(locationLabel)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-user-shield"></i> Reported By</span>
                                <span class="incident-detail-value-inline">${escapeHtml(reporterLabel)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="far fa-calendar-alt"></i> Reported At</span>
                                <span class="incident-detail-value-inline">${escapeHtml(reportedAt)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="far fa-clock"></i> Assigned At</span>
                                <span class="incident-detail-value-inline">${escapeHtml(assignedAt)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-hashtag"></i> Incident ID</span>
                                <span class="incident-detail-value-inline">#${escapeHtml(incident.incident_id)}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-crosshairs"></i> Coordinates</span>
                                <span class="incident-detail-value-inline">${hasCoordinates ? `${escapeHtml(lat)}, ${escapeHtml(lng)}` : 'Coordinates unavailable'}</span>
                            </span>
                            <span class="incident-detail-item">
                                <span class="incident-detail-label-inline"><i class="fas fa-signal"></i> Status</span>
                                <span class="incident-detail-value-inline">${escapeHtml(statusText)}</span>
                            </span>
                        </div>
                    </div>
                    <div class="incident-actions-column">
                        <div class="incident-status-badge ${statusClass} is-block">
                            ${statusText}
                        </div>
                        <button class="btn btn-outline view-incident-details" data-incident-id="${incident.incident_id}">
                            <i class="far fa-eye"></i> Details
                        </button>
                        ${falseAlarmButton}
                        ${actionButton}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Event Listeners for View Details buttons
    document.querySelectorAll('.view-incident-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const incidentId = btn.dataset.incidentId;
            const incident = incidents.find(i => i.incident_id == incidentId);
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
}
    // Professional Incident Details Modal
    function showProfessionalIncidentModal(incident) {
        window.logUserInteraction?.('viewed incident details');
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
                            Incident Report
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
        const reportedBy = incident.reported_by || 'Dispatch';
        const createdAt = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
        
        content.innerHTML = `
            <div class="professional-incident-details">
                <!-- Header Status Bar -->
                <div class="details-status-header ${statusBadgeClass}">
                    <i class="${statusIcon}"></i>
                    <span>${statusText}</span>
                </div>
                
                <!-- Incident ID & Type Row -->
                <div class="details-info-row">
                    <div class="details-info-item">
                        <label>INCIDENT ID</label>
                        <div class="details-info-value">#${incident.incident_id}</div>
                    </div>
                    <div class="details-info-item">
                        <label>INCIDENT TYPE</label>
                        <div class="details-info-value">
                            <i class="fas fa-fire" style="color: #ef4444;"></i> ${escapeHtml(incidentType)}
                        </div>
                    </div>
                </div>
                
                <!-- Title Section -->
                <div class="details-title-section">
                    <label>TITLE / LOCATION</label>
                    <div class="details-title">${escapeHtml(incidentTitle)}</div>
                </div>
                
                <!-- Coordinates Section -->
                ${lat && lng ? `
                <div class="details-coordinates">
                    <div class="coord-box">
                        <label>LATITUDE</label>
                        <div class="coord-value">${lat}</div>
                    </div>
                    <div class="coord-box">
                        <label>LONGITUDE</label>
                        <div class="coord-value">${lng}</div>
                    </div>
                </div>
                ` : '<div class="details-info-item"><label>COORDINATES</label><div class="details-info-value">Not available</div></div>'}
                
                <!-- Description Section -->
                <div class="details-description">
                    <label><i class="fas fa-align-left"></i> INCIDENT DESCRIPTION</label>
                    <div class="description-text">${escapeHtml(description)}</div>
                </div>
                
                <!-- Report Information Grid -->
                <div class="details-grid-2">
                    <div class="details-info-item">
                        <label><i class="far fa-user"></i> REPORTED BY</label>
                        <div class="details-info-value">${escapeHtml(reportedBy)}</div>
                    </div>
                    <div class="details-info-item">
                        <label><i class="far fa-calendar"></i> REPORTED AT</label>
                        <div class="details-info-value">${createdAt}</div>
                    </div>
                </div>
                
                <!-- Team Information -->
                ${teamId ? `
                <div class="details-team-info">
                    <label><i class="fas fa-users"></i> ASSIGNED TEAM</label>
                    <div class="team-badge">Team #${teamId}</div>
                </div>
                ` : ''}
                
                <!-- Last Updated -->
                ${incident.updated_at ? `
                <div class="details-last-updated">
                    <i class="far fa-clock"></i> Last updated: ${new Date(incident.updated_at).toLocaleString()}
                </div>
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
            showToast(result.message || 'You are now responding to this incident!', 'success');
            await fetchAssignedIncidents(true);
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
            fetchAssignedIncidents(true); // Refresh the list
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
            fetchAssignedIncidents(true); // Refresh the list
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
        buttonLabel: 'Tagging...',
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
            fetchAssignedIncidents(true);
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

function showFalseAlarmConfirmationToast() {
    return new Promise((resolve) => {
        const existing = document.getElementById('falseAlarmConfirmToast');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.id = 'falseAlarmConfirmToast';
        toast.style.cssText = `
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: min(360px, calc(100vw - 32px));
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(251, 146, 60, 0.28);
            border-left: 4px solid #ea580c;
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            padding: 16px;
            z-index: 5000;
            color: #7c2d12;
        `;

        toast.innerHTML = `
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <div style="width:38px; height:38px; border-radius:999px; background:#fb923c; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; margin-bottom:4px;">Flag as False Alarm?</div>
                    <div style="font-size:13px; line-height:1.45; color:#9a3412;">
                        This will tag the incident as a false alarm and remove it from active response handling.
                    </div>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
                <button type="button" id="falseAlarmCancelBtn" style="padding:8px 12px; border-radius:10px; border:1px solid #fdba74; background:#fff; color:#9a3412; font-size:12px; font-weight:700; cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="falseAlarmConfirmBtn" style="padding:8px 12px; border-radius:10px; border:none; background:#ea580c; color:#fff; font-size:12px; font-weight:700; cursor:pointer;">
                    Confirm
                </button>
            </div>
        `;

        const cleanup = (value) => {
            if (toast.parentNode) {
                toast.remove();
            }
            resolve(value);
        };

        document.body.appendChild(toast);

        toast.querySelector('#falseAlarmCancelBtn')?.addEventListener('click', () => cleanup(false));
        toast.querySelector('#falseAlarmConfirmBtn')?.addEventListener('click', () => cleanup(true));

        setTimeout(() => {
            if (document.body.contains(toast)) {
                cleanup(false);
            }
        }, 10000);
    });
}
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
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
            document.getElementById('assignedIncidentsCount').textContent = cachedAssigned.length;
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

        if (typeof window.initializePyroMapRuntime === 'function') {
            window.initializePyroMapRuntime();
        }

        window.setTimeout(async function () {
            if (loadState) {
                loadState.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Refreshing map layers...</span>';
            }

            try {
                initializeFirefighterGeolocation();
                await Promise.allSettled([
                    typeof fetchAllFirefighterLocations === 'function' ? fetchAllFirefighterLocations() : Promise.resolve(),
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

        await fetchAssignedIncidents(true);
        loadFirefighterDashboardWidgets();
        startDeferredMapHydration();
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
            if (firefighterDashboardIsVisible()) {
                fetchAssignedIncidents(true);
                if (typeof fetchAllFirefighterLocations === 'function') {
                    fetchAllFirefighterLocations();
                }
                startFirefighterDashboardIntervals();
                return;
            }

            clearFirefighterDashboardIntervals();
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

// Function to initialize all charts
function initializeCharts() {
    // Incident Status Chart (Pie/Doughnut)
    const statusCtx = document.getElementById('incidentStatusChart')?.getContext('2d');
    if (statusCtx) {
        incidentStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reported', 'Responding', 'Resolved', 'Closed'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#e91e8c', '#7c3aed', '#1da1f2', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} incidents` } }
                }
            }
        });
    }

    // Response Time Trend Chart (Line)
    const responseCtx = document.getElementById('responseTimeChart')?.getContext('2d');
    if (responseCtx) {
        responseTimeChart = new Chart(responseCtx, {
            type: 'line',
            data: {
                labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Avg Response Time (minutes)',
                    data: [0, 2, 7, 5, 1, 0, 4],
                    borderColor: '#e91e8c',
                    backgroundColor: 'rgba(233, 30, 140, 0.16)',
                    tension: 0.45,
                    fill: true,
                    pointBackgroundColor: '#e91e8c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } },
                    tooltip: { callbacks: { label: (context) => `${context.raw} minutes` } }
                },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Minutes' } } }
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
                maintainAspectRatio: true,
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
                        max: 10,
                        ticks: { stepSize: 2, color: '#94a3b8', font: { size: 11 } },
                        grid: { color: 'rgba(148, 163, 184, 0.25)', drawBorder: false }
                    }
                }
            }
        });
    }


    // Performance Metrics Chart (Radar or Gauge)
    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    if (performanceCtx) {
        performanceChart = new Chart(performanceCtx, {
            type: 'radar',
            data: {
                labels: ['Completion Rate', 'Response Time', 'Success Rate', 'Efficiency', 'Teamwork'],
                datasets: [{
                    label: 'Performance Score (%)',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(139, 92, 246, 0.18)',
                    borderColor: '#8b5cf6',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ec4899',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 10 }, usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (context) => `${context.raw}%` } }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20, backdropColor: 'transparent', color: '#94a3b8' },
                        angleLines: { color: 'rgba(148, 163, 184, 0.35)' },
                        grid: { color: 'rgba(148, 163, 184, 0.25)' },
                        pointLabels: { color: '#64748b', font: { size: 10, weight: 600 } }
                    }
                }
            }
        });
    }
}

// Function to update all charts with incident data
function updateChartsWithData(incidents) {
    if (!incidents || incidents.length === 0) {
        // Set default empty data
        if (incidentStatusChart) incidentStatusChart.data.datasets[0].data = [0, 0, 0, 0];
        if (priorityChart) priorityChart.data.datasets[0].data = [0, 0, 0, 0];
        if (performanceChart) performanceChart.data.datasets[0].data = [0, 0, 0, 0, 0];
        
        [incidentStatusChart, priorityChart, performanceChart].forEach(chart => {
            if (chart) chart.update();
        });
        return;
    }

    // Update Incident Status Chart
    const statusCounts = {
        reported: incidents.filter(i => getIncidentStatusId(i) === 1).length,
        responding: incidents.filter(i => getIncidentStatusId(i) === 2).length,
        resolved: incidents.filter(i => getIncidentStatusId(i) === 3).length,
        closed: incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length
    };
    if (incidentStatusChart) {
        incidentStatusChart.data.datasets[0].data = [statusCounts.reported, statusCounts.responding, statusCounts.resolved, statusCounts.closed];
        incidentStatusChart.update();
    }

    // Update Priority Chart
    const priorityCounts = {
        critical: incidents.filter(i => i.priority === 'critical').length,
        high: incidents.filter(i => i.priority === 'high').length,
        medium: incidents.filter(i => i.priority === 'medium').length,
        low: incidents.filter(i => i.priority === 'low').length
    };
    if (priorityChart) {
        priorityChart.data.datasets[0].data = [priorityCounts.critical, priorityCounts.high, priorityCounts.medium, priorityCounts.low];
        priorityChart.update();
    }

    // Calculate average response times for last 7 days
    const last7Days = [];
    const responseTimes = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dayStart = new Date(date.setHours(0, 0, 0, 0));
        const dayEnd = new Date(date.setHours(23, 59, 59, 999));
        
        const dayIncidents = incidents.filter(i => {
            const created = new Date(i.created_at);
            return created >= dayStart && created <= dayEnd && getIncidentStatusId(i) >= 2;
        });
        
        let avgTime = 0;
        if (dayIncidents.length > 0) {
            const totalTime = dayIncidents.reduce((sum, inc) => {
                const created = new Date(inc.created_at);
                const responded = inc.updated_at ? new Date(inc.updated_at) : new Date();
                return sum + (responded - created) / (1000 * 60);
            }, 0);
            avgTime = Math.round(totalTime / dayIncidents.length);
        }
        responseTimes.push(avgTime);
        last7Days.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
    }
    
    if (responseTimeChart) {
        responseTimeChart.data.labels = last7Days;
        responseTimeChart.data.datasets[0].data = responseTimes;
        responseTimeChart.update();
    }

    // Update Monthly Incidents Chart (by week)
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const weeksData = [0, 0, 0, 0];
    
    incidents.forEach(incident => {
        const incidentDate = new Date(incident.created_at);
        if (incidentDate.getMonth() === currentMonth && incidentDate.getFullYear() === currentYear) {
            const weekNum = Math.min(Math.floor((incidentDate.getDate() - 1) / 7), 3);
            weeksData[weekNum]++;
        }
    });
    
    if (monthlyIncidentsChart) {
        monthlyIncidentsChart.data.datasets[0].data = weeksData;
        monthlyIncidentsChart.update();
    }

    // Update Performance Chart
    const totalIncidents = incidents.length;
    const completedIncidents = incidents.filter(i => getIncidentStatusId(i) >= 3).length;
    const completionRate = totalIncidents > 0 ? Math.round((completedIncidents / totalIncidents) * 100) : 0;
    
    const avgResponseTime = responseTimes.reduce((a, b) => a + b, 0) / responseTimes.filter(t => t > 0).length || 0;
    const responseScore = Math.max(0, Math.min(100, 100 - (avgResponseTime / 30) * 100));
    
    const successfulIncidents = incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length;
    const successRate = successfulIncidents > 0 
        ? Math.round((successfulIncidents / completedIncidents) * 100) : 0;
    
    if (performanceChart) {
        performanceChart.data.datasets[0].data = [completionRate, Math.round(responseScore), successRate, Math.round((completionRate + responseScore + successRate) / 3), 85];
        performanceChart.update();
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
    const insightsPanel = document.getElementById('firefighterMobileInsightsPanel');
    const insightsContent = document.getElementById('firefighterMobileInsightsContent');
    const insightsCloseButtons = document.querySelectorAll('[data-ff-insights-close]');
    const welcomeToast = document.getElementById('firefighterMobileWelcomeToast');
    const insightSections = Array.from(document.querySelectorAll('[data-ff-insights-source]'));
    const insightPlaceholders = new Map();

    function moveInsightsIntoPanel() {
        if (!insightsContent) {
            return;
        }

        insightSections.forEach(function (section, index) {
            if (!insightPlaceholders.has(section)) {
                const placeholder = document.createElement('div');
                placeholder.dataset.ffInsightsPlaceholder = String(index);
                section.parentNode?.insertBefore(placeholder, section);
                insightPlaceholders.set(section, placeholder);
            }

            insightsContent.appendChild(section);
        });
    }

    function restoreInsightsFromPanel() {
        insightSections.forEach(function (section) {
            const placeholder = insightPlaceholders.get(section);
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.insertBefore(section, placeholder);
                placeholder.remove();
            }
            insightPlaceholders.delete(section);
        });
    }

    function openFirefighterInsightsPanel() {
        if (!insightsPanel || window.innerWidth > 768) {
            return;
        }

        moveInsightsIntoPanel();
        insightsPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('menu-open');
    }

    function closeFirefighterInsightsPanel() {
        if (!insightsPanel) {
            return;
        }

        restoreInsightsFromPanel();
        insightsPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-open');
    }

    window.openFirefighterInsightsPanel = openFirefighterInsightsPanel;
    window.closeFirefighterInsightsPanel = closeFirefighterInsightsPanel;

    insightsCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeFirefighterInsightsPanel);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeFirefighterInsightsPanel();
        }
    });

    if (welcomeToast && window.innerWidth <= 768) {
        welcomeToast.addEventListener('click', function () {
            openFirefighterInsightsPanel();
        });

        window.setTimeout(function () {
            welcomeToast.classList.add('is-visible');
            window.setTimeout(function () {
                welcomeToast.classList.remove('is-visible');
            }, 3200);
        }, 260);
    }
});
}
