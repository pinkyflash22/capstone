<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Add Barangay</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            color: #0f172a;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 36px;
        }

        .barangay-form-shell {
            max-width: 880px;
            margin: 0 auto;
        }

        .lector-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border: 1px solid #fcd9bd;
            border-radius: 999px;
            background: #fff7ed;
            color: #334155;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #ffedd5;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 260px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            overflow: hidden;
            z-index: 30;
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
            width: 100%;
            padding: 12px 18px;
            background: transparent;
            border: 0;
            color: #1e293b;
            text-decoration: none;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .barangay-form-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 28px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .barangay-form-head {
            padding: 26px;
            border-bottom: 1px solid #edf2f7;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.95), rgba(255, 255, 255, 0.98));
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }

        .barangay-eyebrow {
            color: #c2410c;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .barangay-title {
            color: #0f1d44;
            font-size: clamp(26px, 4vw, 38px);
            font-weight: 900;
            line-height: 1.05;
        }

        .barangay-subtitle {
            margin-top: 8px;
            color: #64748b;
            font-size: 15px;
            max-width: 600px;
        }

        .barangay-form-body {
            padding: 26px;
        }

        .barangay-field {
            margin-bottom: 18px;
        }

        .barangay-label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-input {
            width: 100%;
            min-height: 52px;
            border: 1px solid #dbe4ef;
            border-radius: 16px;
            padding: 0 16px;
            color: #0f1d44;
            font-size: 15px;
            font-weight: 700;
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .barangay-input:focus {
            border-color: #d65a20;
            box-shadow: 0 0 0 4px rgba(214, 90, 32, 0.12);
        }

        .barangay-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
            margin-top: 6px;
        }

        .barangay-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .barangay-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 16px;
            border: 1px solid transparent;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .barangay-button--ghost {
            border-color: #e2e8f0;
            background: #fff;
            color: #475569;
        }

        .barangay-button--primary {
            background: linear-gradient(135deg, #d44222 0%, #df8a0b 100%);
            color: #fff;
            box-shadow: 0 16px 30px rgba(216, 92, 20, 0.18);
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: calc(12px + env(safe-area-inset-top, 0px)) 12px calc(112px + env(safe-area-inset-bottom, 0px));
            }

            .lector-top-bar {
                padding: 12px 14px;
            }

            .barangay-form-card {
                border-radius: 24px;
            }

            .barangay-form-head,
            .barangay-form-body {
                padding: 18px;
            }

            .barangay-title {
                font-size: 26px;
            }

            .barangay-actions {
                flex-direction: column-reverse;
            }

            .barangay-button {
                width: 100%;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
    @include('chief.partials.sidebar', ['active' => 'barangays'])

    <main class="main-content">
        <div class="barangay-form-shell">
            <nav class="lector-top-bar">
                <div class="flex items-center gap-4">
                    <button id="openSidebar" class="lg:hidden text-slate-600" type="button">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-slate-800">Add Barangay</h1>
                        <p class="text-xs text-slate-500 font-medium">Station coverage setup · {{ now()->format('l, F j') }}</p>
                    </div>
                </div>

                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle" type="button">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>Commander Profile</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <section class="barangay-form-card">
                <header class="barangay-form-head">
                    <div>
                        <p class="barangay-eyebrow">Coverage Setup</p>
                        <h1 class="barangay-title">Add Barangay</h1>
                        <p class="barangay-subtitle">Create a barangay record so it can be used in addresses, boundaries, stations, reports, and map layers.</p>
                    </div>
                </header>

                <form method="POST" action="{{ route('station-admin.barangays.store') }}" class="barangay-form-body">
                    @csrf

                    <div class="barangay-field">
                        <label for="barangay_name" class="barangay-label">Barangay name</label>
                        <input
                            id="barangay_name"
                            name="barangay_name"
                            type="text"
                            class="barangay-input"
                            value="{{ old('barangay_name') }}"
                            placeholder="Example: Poblacion"
                            required
                        >
                        @error('barangay_name')
                            <p class="barangay-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="barangay-field">
                        <label for="city_id" class="barangay-label">City / Municipality</label>
                        <select id="city_id" name="city_id" class="barangay-input" required>
                            <option value="">Select city or municipality</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->city_id }}" @selected((string) old('city_id') === (string) $city->city_id)>
                                    {{ $city->city_name }}{{ $city->province?->province_name ? ' - ' . $city->province->province_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('city_id')
                            <p class="barangay-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="barangay-actions">
                        <a href="{{ route('station-admin.barangays.index') }}" class="barangay-button barangay-button--ghost">Cancel</a>
                        <button type="submit" class="barangay-button barangay-button--primary">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Save Barangay
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const dropdownButton = document.getElementById('userDropdownBtn');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            const sidebar = document.getElementById('sidebar');
            const openSidebar = document.getElementById('openSidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', (event) => {
                    if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }

            if (openSidebar && sidebar && sidebarBackdrop) {
                openSidebar.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarBackdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });

                sidebarBackdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    sidebarBackdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }
        })();
    </script>
    @include('chief.partials.logout-confirmation')
</body>
</html>
