@extends('firefighters.v2-layout', [
    'active' => 'incidents',
    'pageTitle' => 'Firefighter Incidents V2',
    'pageSubtitle' => 'Same team registry data, presented in a lighter incident workspace'
])

@section('title', 'PyroSense · Firefighter Incidents V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-fire"></i> Incident Registry</span>
        <h2>Assigned Incidents.</h2>
        <p>This incident registry still uses your assigned firefighter team records. The goal here is to keep the logic familiar while making the scan, filter, and detail flow easier to handle.</p>
    </section>

    <section class="ff2-stats">
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Total Responded</span><i class="fa-solid fa-list-check text-[#c94b2a]"></i></div><div class="ff2-stat__value" id="ff2IncidentTotal">--</div><div class="ff2-stat__meta">All incidents tied to your current team.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Active</span><i class="fa-solid fa-bolt text-[#db9218]"></i></div><div class="ff2-stat__value" id="ff2IncidentActive">--</div><div class="ff2-stat__meta">Responding and on-scene operations.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Controlled</span><i class="fa-solid fa-shield text-[#124e5d]"></i></div><div class="ff2-stat__value" id="ff2IncidentControlled">--</div><div class="ff2-stat__meta">Resolved in controlled state.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Closed</span><i class="fa-solid fa-folder-closed text-[#1d9b60]"></i></div><div class="ff2-stat__value" id="ff2IncidentClosed">--</div><div class="ff2-stat__meta">Finalized incidents.</div></article>
    </section>

    <section class="ff2-card">
        <div class="ff2-card__body">
            <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <h3 class="ff2-card__title">Team Incident Feed</h3>
                    <p class="ff2-card__subtitle">Search incident title, location, reporter, or ID. Refresh uses the same registry API behind the current firefighter incidents page.</p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input id="ff2IncidentSearch" type="search" placeholder="Search incidents..." style="min-width:260px;height:46px;border-radius:15px;border:1px solid rgba(226,232,240,.95);padding:0 14px;">
                    <button id="ff2IncidentRefresh" type="button" class="ff2-button ff2-button--primary"><i class="fa-solid fa-rotate"></i> Refresh</button>
                </div>
            </div>

            <div id="ff2IncidentList" class="ff2-list" style="margin-top:18px;"></div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const endpoint = @json(route('firefighter.v2.api.incidents'));
            const searchInput = document.getElementById('ff2IncidentSearch');
            const refreshBtn = document.getElementById('ff2IncidentRefresh');
            const list = document.getElementById('ff2IncidentList');
            let incidents = [];

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
                });
            }

            function render() {
                const query = (searchInput.value || '').toLowerCase().trim();
                const filtered = incidents.filter((incident) => {
                    const haystack = [
                        incident.incident_id,
                        incident.title,
                        incident.location,
                        incident.reporter_name,
                        incident.incident_type_name,
                        incident.incident_status_name
                    ].join(' ').toLowerCase();
                    return haystack.includes(query);
                });

                if (!filtered.length) {
                    list.innerHTML = '<div class="ff2-empty">No incidents matched this view.</div>';
                    return;
                }

                list.innerHTML = filtered.map((incident) => `
                    <article class="ff2-list-item">
                        <div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                                <span class="ff2-pill"><i class="fa-solid fa-fire"></i> ${escapeHtml(incident.incident_type_name || 'Incident')}</span>
                                <span class="ff2-pill"><i class="fa-solid fa-wave-square"></i> ${escapeHtml(incident.incident_status_name || 'Reported')}</span>
                            </div>
                            <h3 style="margin-top:12px;">${escapeHtml(incident.title || ('Incident #' + incident.incident_id))}</h3>
                            <p>${escapeHtml(incident.description || 'No narrative was attached to this incident record.')}</p>
                            <div class="ff2-meta">
                                <span><i class="fa-solid fa-user"></i> ${escapeHtml(incident.reporter_name || 'Concerned citizen')}</span>
                                <span><i class="fa-solid fa-location-dot"></i> ${escapeHtml(incident.location || 'Unspecified location')}</span>
                                <span><i class="fa-solid fa-hashtag"></i> ${escapeHtml(incident.incident_id || 'N/A')}</span>
                            </div>
                        </div>
                        <div style="display:grid;gap:10px;min-width:160px;">
                            <a href="{{ route('firefighter.incidents') }}" class="ff2-button ff2-button--ghost">Current Module</a>
                        </div>
                    </article>
                `).join('');
            }

            function setLoadingState() {
                list.innerHTML = `
                    <div class="ff2-list-item">
                        <div>
                            <h3>Loading incidents...</h3>
                            <p>Fetching your assigned team registry from the cached responder endpoint.</p>
                        </div>
                    </div>
                `;
            }

            async function refresh() {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading';
                try {
                    const response = await fetch(endpoint, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const payload = await response.json();
                    incidents = payload?.data?.incidents || [];
                    document.getElementById('ff2IncidentTotal').textContent = payload?.data?.stats?.total ?? 0;
                    document.getElementById('ff2IncidentActive').textContent = payload?.data?.stats?.active ?? 0;
                    document.getElementById('ff2IncidentControlled').textContent = payload?.data?.stats?.controlled ?? 0;
                    document.getElementById('ff2IncidentClosed').textContent = payload?.data?.stats?.closed ?? 0;
                    render();
                } catch (error) {
                    console.error('Unable to refresh incident registry', error);
                    list.innerHTML = '<div class="ff2-empty">Unable to load firefighter incidents right now.</div>';
                } finally {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Refresh';
                }
            }

            searchInput.addEventListener('input', render);
            refreshBtn.addEventListener('click', refresh);
            setLoadingState();
            refresh();
        });
    </script>
@endsection
