@php
    $user = auth()->user();
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Firefighter';
    $initials = strtoupper(substr($user->first_name ?? 'F', 0, 1) . substr($user->last_name ?? 'R', 0, 1));
    $stationName = $stationOfficer?->station?->station_name ?? 'Unassigned station';
    $teamName = $userTeam?->team_name ?? ($userTeam?->team_id ? 'Team #' . $userTeam->team_id : 'No active team');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Firefighter Hybrid V3</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite(['resources/css/app.css'])
    @include('partials.pwa-head')
    <style>
        :root {
            --ff3-bg: #f6f1ea;
            --ff3-panel: rgba(255, 255, 255, .9);
            --ff3-card: #ffffff;
            --ff3-line: #e6dccf;
            --ff3-ink: #172033;
            --ff3-copy: #627089;
            --ff3-muted: #94a0b3;
            --ff3-red: #cf4329;
            --ff3-orange: #e89212;
            --ff3-teal: #104e5d;
            --ff3-green: #15965c;
            --ff3-radius-lg: 26px;
            --ff3-radius-md: 18px;
            --ff3-shadow: 0 24px 48px rgba(25, 33, 45, .08);
        }

        * { box-sizing: border-box; }
        body.ff3-body {
            margin: 0;
            min-height: 100vh;
            color: var(--ff3-ink);
            font-family: "Inter", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 10% 6%, rgba(207, 67, 41, .16), transparent 28%),
                radial-gradient(circle at 88% 12%, rgba(232, 146, 18, .2), transparent 24%),
                linear-gradient(135deg, #fff9f1 0%, var(--ff3-bg) 46%, #eef7f8 100%);
        }

        .ff3-shell {
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
            min-height: 100vh;
        }

        .ff3-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 24px 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(255, 248, 238, .9));
            border-right: 1px solid rgba(230, 220, 207, .86);
        }

        .ff3-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(230, 220, 207, .9);
            box-shadow: 0 16px 34px rgba(207, 67, 41, .08);
        }

        .ff3-brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 999px;
            background: #fff7ef;
            padding: 8px;
        }

        .ff3-brand strong { display: block; font-size: 1.18rem; letter-spacing: -.03em; }
        .ff3-brand span { color: var(--ff3-copy); font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .14em; }

        .ff3-nav-group { margin-top: 24px; }
        .ff3-nav-label {
            margin: 0 0 10px;
            padding: 0 12px;
            color: var(--ff3-muted);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .ff3-nav { display: grid; gap: 7px; }
        .ff3-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            padding: 0 14px;
            border-radius: 16px;
            color: #4f5e73;
            text-decoration: none;
            font-weight: 800;
            transition: background .18s ease, color .18s ease, transform .18s ease;
        }

        .ff3-nav a i { width: 18px; text-align: center; }
        .ff3-nav a:hover,
        .ff3-nav a.is-active {
            color: var(--ff3-red);
            background: linear-gradient(135deg, #fff4ec, #fffaf2);
            box-shadow: inset 0 0 0 1px rgba(207, 67, 41, .1);
        }

        .ff3-nav a[data-mode="page"]::after {
            content: "page";
            margin-left: auto;
            color: #a3adbc;
            font-size: .65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .ff3-identity {
            margin-top: 24px;
            padding: 18px;
            border-radius: 24px;
            color: #e7f6f8;
            background: linear-gradient(135deg, var(--ff3-teal), #176b75);
            box-shadow: 0 18px 34px rgba(16, 78, 93, .2);
        }

        .ff3-identity__row { display: flex; gap: 12px; align-items: center; }
        .ff3-avatar {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, .15);
            font-weight: 900;
        }

        .ff3-identity strong { display: block; }
        .ff3-identity span { display: block; margin-top: 2px; color: rgba(231, 246, 248, .76); font-size: .82rem; }
        .ff3-main { min-width: 0; padding: 24px; }

        .ff3-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            border-radius: 26px;
            background: rgba(255, 255, 255, .84);
            border: 1px solid rgba(230, 220, 207, .84);
            box-shadow: 0 14px 34px rgba(25, 33, 45, .05);
            backdrop-filter: blur(16px);
        }

        .ff3-topbar h1 { margin: 0; font-size: clamp(1.4rem, 2vw, 2rem); line-height: 1.08; letter-spacing: -.04em; }
        .ff3-topbar p { margin: 6px 0 0; color: var(--ff3-copy); font-size: .92rem; }
        .ff3-topbar__actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .ff3-status,
        .ff3-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: #fff7ef;
            border: 1px solid rgba(207, 67, 41, .12);
            color: var(--ff3-red);
            font-size: .76rem;
            font-weight: 900;
        }

        .ff3-status { background: #edfdf4; border-color: rgba(21, 150, 92, .16); color: var(--ff3-green); text-transform: uppercase; letter-spacing: .12em; }
        .ff3-status::before { content: ""; width: 9px; height: 9px; border-radius: 99px; background: var(--ff3-green); box-shadow: 0 0 0 5px rgba(21, 150, 92, .14); }

        .ff3-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(230, 220, 207, .9);
            background: #fff;
            color: var(--ff3-ink);
            cursor: pointer;
            text-decoration: none;
            font-weight: 900;
        }

        .ff3-button--primary {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, var(--ff3-red), var(--ff3-orange));
            box-shadow: 0 14px 26px rgba(207, 67, 41, .2);
        }

        .ff3-stage { margin-top: 22px; }
        .ff3-stage.is-loading { cursor: progress; }
        .ff3-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 30px;
            background:
                radial-gradient(circle at top right, rgba(232, 146, 18, .18), transparent 28%),
                linear-gradient(135deg, #fff5ea, #ffffff);
            border: 1px solid rgba(230, 220, 207, .86);
            box-shadow: var(--ff3-shadow);
        }

        .ff3-hero h2 { margin: 14px 0 0; font-size: clamp(2rem, 4vw, 3.7rem); line-height: .95; letter-spacing: -.06em; }
        .ff3-hero p { max-width: 780px; margin: 14px 0 0; color: var(--ff3-copy); line-height: 1.7; }

        .ff3-grid { display: grid; gap: 18px; }
        .ff3-grid--stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top: 18px; }
        .ff3-grid--two { grid-template-columns: minmax(0, 1.4fr) minmax(320px, .8fr); margin-top: 18px; }

        .ff3-card {
            border-radius: var(--ff3-radius-lg);
            background: var(--ff3-card);
            border: 1px solid rgba(230, 220, 207, .86);
            box-shadow: 0 16px 36px rgba(25, 33, 45, .05);
        }

        .ff3-card__body { padding: 20px; }
        .ff3-card h3 { margin: 0; font-size: 1.05rem; font-weight: 900; }
        .ff3-card p { margin: 8px 0 0; color: var(--ff3-copy); line-height: 1.65; }
        .ff3-stat { padding: 20px; }
        .ff3-stat__label { display: flex; justify-content: space-between; gap: 12px; color: var(--ff3-copy); font-size: .76rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .ff3-stat__value { margin-top: 14px; font-size: 2.3rem; line-height: 1; font-weight: 950; letter-spacing: -.06em; }
        .ff3-stat__meta { margin-top: 8px; color: var(--ff3-muted); font-size: .86rem; line-height: 1.5; }

        .ff3-list { display: grid; gap: 12px; }
        .ff3-list-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(230, 220, 207, .82);
            background: linear-gradient(135deg, #fff, #fffaf4);
        }

        .ff3-list-item h4 { margin: 0; font-size: .98rem; font-weight: 900; }
        .ff3-list-item p { margin: 7px 0 0; color: var(--ff3-copy); font-size: .9rem; line-height: 1.55; }
        .ff3-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; color: var(--ff3-muted); font-size: .78rem; font-weight: 800; }
        .ff3-empty { padding: 32px 18px; text-align: center; color: var(--ff3-muted); }
        .ff3-toolbar { display: flex; justify-content: space-between; align-items: end; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
        .ff3-input { min-height: 44px; min-width: 240px; border-radius: 14px; border: 1px solid rgba(230, 220, 207, .94); padding: 0 14px; outline: none; }
        .ff3-input:focus { border-color: rgba(207, 67, 41, .35); box-shadow: 0 0 0 4px rgba(207, 67, 41, .08); }
        .ff3-map { height: 560px; min-height: 420px; border-radius: 24px; overflow: hidden; border: 1px solid rgba(230, 220, 207, .9); background: #eef3f5; }
        .ff3-skeleton { min-height: 180px; border-radius: 26px; background: linear-gradient(90deg, rgba(255,255,255,.62), rgba(255,246,236,.95), rgba(255,255,255,.62)); background-size: 220% 100%; animation: ff3-shimmer 1.35s ease infinite; }

        @keyframes ff3-shimmer { from { background-position: 120% 0; } to { background-position: -120% 0; } }

        @media (max-width: 1180px) {
            .ff3-grid--stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ff3-grid--two { grid-template-columns: 1fr; }
        }

        @media (max-width: 920px) {
            .ff3-shell { grid-template-columns: 1fr; }
            .ff3-sidebar { position: relative; height: auto; }
        }

        @media (max-width: 640px) {
            .ff3-main { padding: 14px; }
            .ff3-topbar { align-items: start; flex-direction: column; }
            .ff3-hero { padding: 22px; }
            .ff3-grid--stats { grid-template-columns: 1fr; }
            .ff3-list-item { flex-direction: column; }
            .ff3-input { width: 100%; min-width: 0; }
            .ff3-map { height: 460px; }
        }
    </style>
</head>
<body class="ff3-body">
    @include('partials.unified-notifications')
    <div class="ff3-shell">
        <aside class="ff3-sidebar">
            <div class="ff3-brand">
                <img src="{{ asset('logo.png') }}" alt="PyroSense logo">
                <div>
                    <strong>PyroSense</strong>
                    <span>Firefighter V3</span>
                </div>
            </div>

            <div class="ff3-nav-group">
                <p class="ff3-nav-label">Hybrid sections</p>
                <nav class="ff3-nav" aria-label="Firefighter V3 hybrid navigation">
                    <a href="{{ route('firefighter.v3.dashboard') }}" data-ff3-section="dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
                    <a href="{{ route('firefighter.v3.dashboard', ['section' => 'assigned']) }}" data-ff3-section="assigned"><i class="fa-solid fa-fire"></i><span>Assigned Incidents</span></a>
                    <a href="{{ route('firefighter.v3.dashboard', ['section' => 'map']) }}" data-ff3-section="map"><i class="fa-solid fa-map-location-dot"></i><span>Map</span></a>
                    <a href="{{ route('firefighter.v3.dashboard', ['section' => 'schedule']) }}" data-ff3-section="schedule"><i class="fa-solid fa-calendar-days"></i><span>Schedule</span></a>
                </nav>
            </div>

            <div class="ff3-nav-group">
                <p class="ff3-nav-label">Normal page loads</p>
                <nav class="ff3-nav" aria-label="Firefighter V3 normal page navigation">
                    <a href="{{ route('firefighter.v2.station') }}" data-mode="page"><i class="fa-solid fa-building-shield"></i><span>My Station</span></a>
                    <a href="{{ route('firefighter.v2.requests') }}" data-mode="page"><i class="fa-solid fa-clipboard-check"></i><span>FSIC Requests</span></a>
                    <a href="{{ route('firefighter.v2.announcements') }}" data-mode="page"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                    <a href="{{ route('firefighter.v2.profile') }}" data-mode="page"><i class="fa-solid fa-id-badge"></i><span>Profile</span></a>
                    <a href="{{ route('firefighter.dashboard') }}" data-mode="page"><i class="fa-solid fa-rotate-left"></i><span>Current Module</span></a>
                </nav>
            </div>

            <div class="ff3-identity">
                <div class="ff3-identity__row">
                    <div class="ff3-avatar">{{ $initials }}</div>
                    <div>
                        <strong>{{ $fullName }}</strong>
                        <span>{{ $stationName }}</span>
                    </div>
                </div>
                <div class="ff3-meta" style="color:rgba(231,246,248,.78);">
                    <span><i class="fa-solid fa-people-group"></i> {{ $teamName }}</span>
                    <span><i class="fa-solid fa-shield-heart"></i> Hybrid shell</span>
                </div>
            </div>
        </aside>

        <main class="ff3-main">
            <header class="ff3-topbar">
                <div>
                    <h1 id="ff3PageTitle">Firefighter Hybrid V3</h1>
                    <p id="ff3PageSubtitle">Persistent sidebar and topbar with dynamic high-use responder sections.</p>
                </div>
                <div class="ff3-topbar__actions">
                    <span class="ff3-status">On Duty</span>
                    <form method="POST" action="{{ route('logout') }}" data-firefighter-logout-form>
                        @csrf
                        <button type="submit" class="ff3-button ff3-button--primary">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <section id="ff3Stage" class="ff3-stage" aria-live="polite">
                <div class="ff3-skeleton"></div>
            </section>
        </main>
    </div>

    @include('firefighters.partials.logout-confirmation')

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const config = {
                initialSection: @json($initialSection),
                urls: {
                    dashboard: @json(route('firefighter.v2.api.dashboard')),
                    assigned: @json(route('firefighter.assigned', ['id' => $user->user_id])),
                    schedule: @json(route('firefighter.schedule.data')),
                },
                sectionUrls: {
                    dashboard: @json(route('firefighter.v3.dashboard')),
                    assigned: @json(route('firefighter.v3.dashboard', ['section' => 'assigned'])),
                    map: @json(route('firefighter.v3.dashboard', ['section' => 'map'])),
                    schedule: @json(route('firefighter.v3.dashboard', ['section' => 'schedule'])),
                },
                stationName: @json($stationName),
            };

            const stage = document.getElementById('ff3Stage');
            const pageTitle = document.getElementById('ff3PageTitle');
            const pageSubtitle = document.getElementById('ff3PageSubtitle');
            const navLinks = Array.from(document.querySelectorAll('[data-ff3-section]'));
            let abortController = null;
            let cleanupFns = [];
            let activeSection = null;

            const sectionMeta = {
                dashboard: {
                    title: 'Responder Dashboard',
                    subtitle: 'Fast overview using the existing firefighter dashboard API.',
                },
                assigned: {
                    title: 'Assigned Incidents',
                    subtitle: 'Dynamic incident registry for your current team assignment.',
                },
                map: {
                    title: 'Operations Map',
                    subtitle: 'Map loads only when needed and is destroyed when you leave this section.',
                },
                schedule: {
                    title: 'Responder Schedule',
                    subtitle: 'Station and personal events loaded dynamically from the current schedule API.',
                },
            };

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                })[char]);
            }

            function text(value, fallback = 'Not available') {
                const normalized = value === null || value === undefined || value === '' ? fallback : value;
                return escapeHtml(normalized);
            }

            function setShellState(section) {
                const meta = sectionMeta[section] || sectionMeta.dashboard;
                pageTitle.textContent = meta.title;
                pageSubtitle.textContent = meta.subtitle;
                navLinks.forEach((link) => {
                    link.classList.toggle('is-active', link.dataset.ff3Section === section);
                });
            }

            function cleanupSection() {
                if (abortController) {
                    abortController.abort();
                    abortController = null;
                }

                cleanupFns.forEach((cleanup) => {
                    try {
                        cleanup();
                    } catch (error) {
                        console.warn('Firefighter V3 cleanup skipped:', error);
                    }
                });
                cleanupFns = [];
            }

            async function fetchJson(url) {
                abortController = new AbortController();
                const response = await fetch(url, {
                    signal: abortController.signal,
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(`Request failed: ${response.status}`);
                }

                return response.json();
            }

            function loadingMarkup(label) {
                return `
                    <section class="ff3-hero">
                        <span class="ff3-pill"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Loading</span>
                        <h2>${escapeHtml(label)}</h2>
                        <p>Preparing this section without reloading the firefighter shell.</p>
                    </section>
                    <div class="ff3-grid ff3-grid--stats">
                        <div class="ff3-skeleton"></div>
                        <div class="ff3-skeleton"></div>
                        <div class="ff3-skeleton"></div>
                        <div class="ff3-skeleton"></div>
                    </div>
                `;
            }

            function statCard(label, value, meta, icon, color = 'var(--ff3-red)') {
                return `
                    <article class="ff3-card ff3-stat">
                        <div class="ff3-stat__label"><span>${escapeHtml(label)}</span><i class="${escapeHtml(icon)}" style="color:${color};"></i></div>
                        <div class="ff3-stat__value">${text(value, '0')}</div>
                        <div class="ff3-stat__meta">${escapeHtml(meta)}</div>
                    </article>
                `;
            }

            function emptyState(message) {
                return `<div class="ff3-empty">${escapeHtml(message)}</div>`;
            }

            function renderDashboard(data) {
                const stats = data.stats || {};
                const crew = Array.isArray(data.crew) ? data.crew : [];
                const posts = Array.isArray(data.posts) ? data.posts : [];
                const incidents = Array.isArray(data.incidents) ? data.incidents : [];
                const stations = Array.isArray(data.stations) ? data.stations : [];

                stage.innerHTML = `
                    <section class="ff3-hero">
                        <span class="ff3-pill"><i class="fa-solid fa-bolt"></i> Hybrid section</span>
                        <h2>Command view, no full reload.</h2>
                        <p>This V3 dashboard keeps the sidebar and topbar alive, then refreshes only the responder data inside the content stage.</p>
                    </section>
                    <section class="ff3-grid ff3-grid--stats">
                        ${statCard('Active Incidents', stats.active_incidents, 'Current incidents tied to your team.', 'fa-solid fa-fire', 'var(--ff3-red)')}
                        ${statCard('Crew on Station', stats.crew_on_station, 'Personnel from your assigned station.', 'fa-solid fa-user-group', 'var(--ff3-orange)')}
                        ${statCard('Nearby Stations', stats.nearby_stations, 'Support stations near your assignment.', 'fa-solid fa-building-shield', 'var(--ff3-teal)')}
                        ${statCard('Equipment Types', stats.available_equipment, 'Equipment records available in the system.', 'fa-solid fa-toolbox', 'var(--ff3-green)')}
                    </section>
                    <section class="ff3-grid ff3-grid--two">
                        <article class="ff3-card">
                            <div class="ff3-card__body">
                                <div class="ff3-toolbar">
                                    <div>
                                        <h3>Quick Operations Summary</h3>
                                        <p>Map data is ready, but the real Leaflet map only boots when you open the Map section.</p>
                                    </div>
                                    <button type="button" class="ff3-button ff3-button--primary" data-ff3-jump="map"><i class="fa-solid fa-map-location-dot"></i> Open map</button>
                                </div>
                                <div class="ff3-grid ff3-grid--stats" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                                    ${statCard('Incident markers', incidents.length, 'Available for the map section.', 'fa-solid fa-location-dot')}
                                    ${statCard('Station markers', stations.length, 'Nearby stations from the dashboard payload.', 'fa-solid fa-building-shield', 'var(--ff3-teal)')}
                                </div>
                            </div>
                        </article>
                        <article class="ff3-card">
                            <div class="ff3-card__body">
                                <h3>Assigned Crew</h3>
                                <p>Your current crew snapshot.</p>
                                <div class="ff3-list" style="margin-top:14px;">
                                    ${crew.length ? crew.map((member) => `
                                        <div class="ff3-list-item">
                                            <div>
                                                <h4>${text(member.name, 'Station officer')}</h4>
                                                <div class="ff3-meta">
                                                    <span>${text(member.rank, 'Responder')}</span>
                                                    <span>${text(member.role, 'Firefighter')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('') : emptyState('No crew records available yet.')}
                                </div>
                            </div>
                        </article>
                    </section>
                    <section class="ff3-card" style="margin-top:18px;">
                        <div class="ff3-card__body">
                            <h3>Recent Station Feed</h3>
                            <p>Latest posts reused from the existing firefighter dashboard feed.</p>
                            <div class="ff3-list" style="margin-top:14px;">
                                ${posts.length ? posts.map((post) => `
                                    <article class="ff3-list-item">
                                        <div>
                                            <h4>${text(post.title, 'Station update')}</h4>
                                            <p>${text(post.summary, 'No summary available.')}</p>
                                            <div class="ff3-meta">
                                                <span>${text(post.author, 'BFP Staff')}</span>
                                                <span>${text(post.created_at_human, 'Recently')}</span>
                                            </div>
                                        </div>
                                    </article>
                                `).join('') : emptyState('No station feed yet.')}
                            </div>
                        </div>
                    </section>
                `;

                stage.querySelector('[data-ff3-jump="map"]')?.addEventListener('click', () => navigateTo('map'));
            }

            function incidentItem(incident) {
                return `
                    <article class="ff3-list-item" data-ff3-incident-card>
                        <div>
                            <div class="ff3-meta" style="margin-top:0;">
                                <span class="ff3-pill"><i class="fa-solid fa-fire"></i> ${text(incident.incident_type_name || incident.type, 'Incident')}</span>
                                <span class="ff3-pill"><i class="fa-solid fa-signal"></i> ${text(incident.incident_status_name || incident.status, 'Reported')}</span>
                            </div>
                            <h4 style="margin-top:12px;">${text(incident.title, `Incident #${incident.incident_id ?? 'N/A'}`)}</h4>
                            <p>${text(incident.description, 'No incident narrative was attached yet.')}</p>
                            <div class="ff3-meta">
                                <span><i class="fa-solid fa-location-dot"></i> ${text(incident.location || incident.address, 'Location not specified')}</span>
                                <span><i class="fa-solid fa-hashtag"></i> ${text(incident.incident_id, 'N/A')}</span>
                                <span><i class="fa-solid fa-clock"></i> ${text(incident.created_at_human || incident.reported_at, 'Recently')}</span>
                            </div>
                        </div>
                        <a href="{{ route('firefighter.incidents') }}" class="ff3-button">Open current module</a>
                    </article>
                `;
            }

            function renderAssigned(payload) {
                const incidents = Array.isArray(payload.data?.incidents)
                    ? payload.data.incidents
                    : (Array.isArray(payload.data) ? payload.data : []);
                const stats = payload.data?.stats || {};
                const hasRegistryStats = Object.keys(stats).length > 0;

                stage.innerHTML = `
                    <section class="ff3-hero">
                        <span class="ff3-pill"><i class="fa-solid fa-fire"></i> Assigned response</span>
                        <h2>Your incident queue.</h2>
                        <p>This section stays dynamic so firefighters can scan assigned work without rebuilding the whole page shell.</p>
                    </section>
                    <section class="ff3-grid ff3-grid--stats">
                        ${statCard('Visible Incidents', incidents.length, 'Loaded from the current assigned/registry endpoint.', 'fa-solid fa-list-check')}
                        ${statCard('Active', hasRegistryStats ? stats.active : incidents.length, 'Still active or responding.', 'fa-solid fa-bolt', 'var(--ff3-orange)')}
                        ${statCard('Controlled', hasRegistryStats ? stats.controlled : 0, 'Controlled response records.', 'fa-solid fa-shield', 'var(--ff3-teal)')}
                        ${statCard('Closed', hasRegistryStats ? stats.closed : 0, 'Finalized response records.', 'fa-solid fa-folder-closed', 'var(--ff3-green)')}
                    </section>
                    <section class="ff3-card" style="margin-top:18px;">
                        <div class="ff3-card__body">
                            <div class="ff3-toolbar">
                                <div>
                                    <h3>Assigned Incident Feed</h3>
                                    <p>Search stays local after this section loads. No extra requests on every keystroke.</p>
                                </div>
                                <input class="ff3-input" id="ff3IncidentSearch" type="search" placeholder="Search incidents">
                            </div>
                            <div class="ff3-list" id="ff3IncidentList">
                                ${incidents.length ? incidents.map(incidentItem).join('') : emptyState('No assigned incidents are active right now.')}
                            </div>
                        </div>
                    </section>
                `;

                const search = document.getElementById('ff3IncidentSearch');
                const cards = Array.from(stage.querySelectorAll('[data-ff3-incident-card]'));
                const onSearch = () => {
                    const query = search.value.trim().toLowerCase();
                    cards.forEach((card) => {
                        card.hidden = !card.textContent.toLowerCase().includes(query);
                    });
                };
                search?.addEventListener('input', onSearch);
                cleanupFns.push(() => search?.removeEventListener('input', onSearch));
            }

            function renderSchedule(data) {
                const events = Array.isArray(data.events) ? data.events : [];

                stage.innerHTML = `
                    <section class="ff3-hero">
                        <span class="ff3-pill"><i class="fa-solid fa-calendar-days"></i> ${text(data.station_name, config.stationName)}</span>
                        <h2>Schedule board.</h2>
                        <p>Station and personal events are loaded inside the V3 shell while keeping event creation/editing safely in the current module.</p>
                    </section>
                    <section class="ff3-card" style="margin-top:18px;">
                        <div class="ff3-card__body">
                            <div class="ff3-toolbar">
                                <div>
                                    <h3>Upcoming Activities</h3>
                                    <p>Read-only dynamic view for now. Use the current schedule module for editing.</p>
                                </div>
                                <a href="{{ route('firefighter.schedule') }}" class="ff3-button ff3-button--primary"><i class="fa-solid fa-pen-to-square"></i> Manage schedule</a>
                            </div>
                            <div class="ff3-list">
                                ${events.length ? events.map((event) => `
                                    <article class="ff3-list-item">
                                        <div>
                                            <h4>${text(event.title || event.name || event.event_title, 'Scheduled activity')}</h4>
                                            <p>${text(event.description || event.notes, 'No notes attached.')}</p>
                                            <div class="ff3-meta">
                                                <span><i class="fa-solid fa-calendar"></i> ${text(event.event_date || event.date, 'No date')}</span>
                                                <span><i class="fa-solid fa-clock"></i> ${text([event.start_time, event.end_time].filter(Boolean).join(' - '), 'No time')}</span>
                                                <span><i class="fa-solid fa-tag"></i> ${text(event.scope || event.type || event.event_type, 'Station activity')}</span>
                                            </div>
                                        </div>
                                    </article>
                                `).join('') : emptyState('No schedule events are listed for this station yet.')}
                            </div>
                        </div>
                    </section>
                `;
            }

            function renderMap(data) {
                const incidents = Array.isArray(data.incidents) ? data.incidents : [];
                const stations = Array.isArray(data.stations) ? data.stations : [];

                stage.innerHTML = `
                    <section class="ff3-hero">
                        <span class="ff3-pill"><i class="fa-solid fa-map-location-dot"></i> Lazy-loaded map</span>
                        <h2>Operations map.</h2>
                        <p>The map is created only on this section and removed on exit, preventing duplicate Leaflet instances and hidden timers.</p>
                    </section>
                    <section class="ff3-card" style="margin-top:18px;">
                        <div class="ff3-card__body">
                            <div class="ff3-toolbar">
                                <div>
                                    <h3>Active Response Geography</h3>
                                    <p>${incidents.length} incident marker(s) and ${stations.length} station marker(s) available from the existing dashboard payload.</p>
                                </div>
                                <button type="button" class="ff3-button" data-ff3-jump="assigned"><i class="fa-solid fa-list"></i> View incident list</button>
                            </div>
                            <div id="ff3OperationsMap" class="ff3-map"></div>
                        </div>
                    </section>
                `;

                stage.querySelector('[data-ff3-jump="assigned"]')?.addEventListener('click', () => navigateTo('assigned'));

                if (typeof L === 'undefined') {
                    document.getElementById('ff3OperationsMap').innerHTML = emptyState('Leaflet is unavailable, so the map cannot render.');
                    return;
                }

                const map = L.map('ff3OperationsMap').setView([15.8133, 120.3410], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const bounds = [];
                incidents.forEach((incident) => {
                    const lat = Number(incident.lat);
                    const lng = Number(incident.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    bounds.push([lat, lng]);
                    L.circleMarker([lat, lng], {
                        radius: 10,
                        color: '#cf4329',
                        weight: 3,
                        fillColor: '#e89212',
                        fillOpacity: .92,
                    }).addTo(map).bindPopup(`<strong>${text(incident.title, 'Fire incident')}</strong><br>${text(incident.status, 'Active')}`);
                });

                stations.forEach((station) => {
                    const lat = Number(station.lat);
                    const lng = Number(station.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    bounds.push([lat, lng]);
                    L.marker([lat, lng]).addTo(map).bindPopup(`<strong>${text(station.station_name, 'Fire station')}</strong>`);
                });

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 15 });
                }

                cleanupFns.push(() => map.remove());
            }

            async function loadSection(section, push = true) {
                if (!sectionMeta[section] || activeSection === section) {
                    return;
                }

                cleanupSection();
                activeSection = section;
                setShellState(section);
                stage.classList.add('is-loading');
                stage.innerHTML = loadingMarkup(sectionMeta[section].title);

                if (push) {
                    window.history.pushState({ ff3Section: section }, '', config.sectionUrls[section]);
                }

                try {
                    if (section === 'dashboard') {
                        const payload = await fetchJson(config.urls.dashboard);
                        renderDashboard(payload.data || {});
                    } else if (section === 'assigned') {
                        const assignedPayload = await fetchJson(config.urls.assigned);
                        renderAssigned(assignedPayload || {});
                    } else if (section === 'schedule') {
                        const payload = await fetchJson(config.urls.schedule);
                        renderSchedule(payload.data || {});
                    } else if (section === 'map') {
                        const payload = await fetchJson(config.urls.dashboard);
                        renderMap(payload.data || {});
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(`Unable to load firefighter V3 ${section} section`, error);
                        stage.innerHTML = `
                            <section class="ff3-hero">
                                <span class="ff3-pill"><i class="fa-solid fa-triangle-exclamation"></i> Section unavailable</span>
                                <h2>Could not load ${escapeHtml(sectionMeta[section].title)}.</h2>
                                <p>The existing full page is still available, so this hybrid layer fails safely.</p>
                            </section>
                        `;
                    }
                } finally {
                    stage.classList.remove('is-loading');
                }
            }

            function navigateTo(section) {
                loadSection(section, true);
            }

            navLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    navigateTo(link.dataset.ff3Section);
                });
            });

            window.addEventListener('popstate', (event) => {
                const section = event.state?.ff3Section || 'dashboard';
                loadSection(section, false);
            });

            window.history.replaceState({ ff3Section: config.initialSection }, '', config.sectionUrls[config.initialSection]);
            loadSection(config.initialSection, false);
        })();
    </script>
</body>
</html>
