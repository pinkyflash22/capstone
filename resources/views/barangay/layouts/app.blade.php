<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <title>PyroSense · @yield('page_title', 'Barangay Portal')</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 32px;
        }

        .portal-top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 0 0 20px;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
        }

        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .portal-shell {
            max-width: 1480px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(260px, 0.95fr);
            gap: 20px;
            padding: 28px 30px;
            border-radius: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 44%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .hero-card h1 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.75rem);
            line-height: 1;
            color: #0f172a;
            font-weight: 900;
        }

        .hero-card p {
            margin: 12px 0 0;
            color: #64748b;
            line-height: 1.65;
            font-size: 0.98rem;
            max-width: 64ch;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.78);
            color: #9a3412;
            border: 1px solid rgba(245, 158, 11, 0.14);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .summary-grid,
        .metric-grid,
        .panel-grid {
            display: grid;
            gap: 18px;
        }

        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .metric-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .panel-grid.two {
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
        }

        .panel-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .summary-card,
        .metric-card,
        .panel-card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .summary-card,
        .metric-card {
            padding: 18px 18px 16px;
        }

        .panel-card {
            padding: 22px 24px;
        }

        .summary-label,
        .metric-label,
        .panel-eyebrow {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary-value,
        .metric-value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 1.6rem;
            font-weight: 900;
        }

        .panel-title {
            margin: 8px 0 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .panel-copy {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .list-stack {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .list-item {
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            padding: 16px;
        }

        .list-item h4 {
            margin: 0;
            color: #0f172a;
            font-size: 0.98rem;
            font-weight: 800;
        }

        .list-item p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .tag.critical,
        .tag.high { background: #fff1f2; color: #be123c; }
        .tag.medium { background: #fff7ed; color: #c2410c; }
        .tag.low { background: #ecfdf5; color: #047857; }
        .tag.pending { background: #fff7ed; color: #b45309; }
        .tag.in_progress { background: #eff6ff; color: #1d4ed8; }
        .tag.complied { background: #ecfdf5; color: #047857; }
        .tag.not_complied { background: #fef2f2; color: #b91c1c; }
        .tag.deferred { background: #f8fafc; color: #475569; }

        .empty-state {
            margin-top: 18px;
            border: 1px dashed rgba(226, 232, 240, 1);
            border-radius: 22px;
            padding: 26px 18px;
            text-align: center;
            color: #94a3b8;
            background: #f8fafc;
            font-size: 0.9rem;
        }

        .two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .stat-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-top: 1px solid rgba(241, 245, 249, 1);
        }

        .stat-line:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .stat-line strong {
            color: #0f172a;
            font-weight: 800;
        }

        .map-card {
            min-height: 220px;
            padding: 20px;
            border-radius: 24px;
            background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 38%), linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
        }

        .tiny-label {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .table-wrap {
            margin-top: 18px;
            overflow-x: auto;
        }

        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrap th,
        .table-wrap td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
            font-size: 0.88rem;
        }

        .table-wrap th {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 1280px) {
            .hero-card,
            .panel-grid.two,
            .panel-grid.three,
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding-bottom: calc(108px + env(safe-area-inset-bottom, 0px));
            }

            .portal-top-bar {
                display: none;
            }

            .summary-grid,
            .two-col {
                grid-template-columns: 1fr;
            }

            .hero-card,
            .panel-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    @include('barangay.partials.sidebar', ['active' => trim($__env->yieldContent('active_nav'))])

    <main class="main-content">
        <nav class="portal-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden md:block">
                    <h1 class="text-xl font-bold text-slate-800">@yield('page_title', 'Barangay Portal')</h1>
                    <p class="text-xs text-slate-500 font-medium">@yield('page_subtitle', 'Community-level situational view and risk coordination tools')</p>
                </div>
            </div>

            <div class="hidden md:block user-dropdown">
                <button id="userDropdownBtn" class="user-dropdown-toggle">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                        {{ strtoupper(substr(auth()->user()->first_name ?? 'B', 0, 1) . substr(auth()->user()->last_name ?? 'R', 0, 1)) }}
                    </div>
                    <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                </button>

                <div id="userDropdownMenu" class="user-dropdown-menu">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                        <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="p-2">
                        <a href="{{ route('barangay.profile') }}" class="user-dropdown-item">
                            <i class="fa-regular fa-user"></i>
                            <span>Barangay Profile</span>
                        </a>
                        <a href="#" class="user-dropdown-item chief-open-logs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>My Logs</span>
                        </a>
                        <div class="h-px bg-slate-100 my-2"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="portal-shell">
            <div id="barangayWelcomeToast" class="chief-mobile-welcome-toast" role="status" aria-live="polite">
                <div class="chief-mobile-welcome-toast__icon">
                    <i class="fa-solid fa-shield-heart"></i>
                </div>
                <div class="chief-mobile-welcome-toast__copy">
                    <strong>@yield('page_title', 'Barangay Portal')</strong>
                    <span>{{ $barangay?->barangay_name ?? 'Barangay Desk' }} is ready.</span>
                </div>
            </div>

            <section class="hero-card">
                <div>
                    <h1>@yield('hero_title', 'Barangay safety operations in one place.')</h1>
                    <p>@yield('hero_copy', 'Monitor community hazards, review risk zones, and keep preparedness work aligned with the latest barangay-level records.')</p>
                    <div class="hero-badges">
                        @yield('hero_badges')
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Barangay</div>
                        <div class="summary-value" style="font-size:1.2rem;">{{ $barangay?->barangay_name ?? 'Not linked' }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">City</div>
                        <div class="summary-value" style="font-size:1.2rem;">{{ $city?->city_name ?? 'No city data' }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Hall</div>
                        <div class="summary-value" style="font-size:1rem;">{{ $hall?->hall_name ?? 'Hall profile pending' }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Officer</div>
                        <div class="summary-value" style="font-size:1rem;">{{ auth()->user()->full_name }}</div>
                    </div>
                </div>
            </section>

            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const welcomeToast = document.getElementById('barangayWelcomeToast');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            let welcomeToastTimer = null;

            openBtn?.addEventListener('click', function () {
                sidebar?.classList.toggle('open');
            });

            if (welcomeToast) {
                welcomeToast.classList.add('is-visible');
                window.clearTimeout(welcomeToastTimer);
                welcomeToastTimer = window.setTimeout(() => {
                    welcomeToast.classList.remove('is-visible');
                }, 2800);
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });

                userDropdownMenu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }
        });
    </script>
    @include('chief.partials.logs-modal', ['chiefLogsAllowAll' => false])
    @include('chief.partials.logs-script', ['chiefLogsAllowAll' => false])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
