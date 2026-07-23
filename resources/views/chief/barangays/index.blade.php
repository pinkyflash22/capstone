<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Barangays</title>

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

        .barangay-shell {
            max-width: 1220px;
            margin: 0 auto;
        }

        .barangay-topbar,
        .barangay-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .barangay-topbar {
            border-radius: 24px;
            padding: 22px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .barangay-eyebrow {
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 4px;
        }

        .barangay-title {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 900;
            color: #0f1d44;
            line-height: 1.05;
        }

        .barangay-subtitle {
            margin-top: 8px;
            color: #64748b;
            font-size: 15px;
            max-width: 680px;
        }

        .barangay-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 16px;
            background: linear-gradient(135deg, #d44222 0%, #df8a0b 100%);
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 16px 30px rgba(216, 92, 20, 0.18);
            white-space: nowrap;
        }

        .barangay-card {
            border-radius: 24px;
            overflow: hidden;
        }

        .barangay-list-header,
        .barangay-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(160px, 1fr) 120px 120px 130px;
            gap: 14px;
            align-items: center;
            padding: 16px 20px;
        }

        .barangay-list-header {
            background: #fff7ed;
            color: #9a3412;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-row {
            border-top: 1px solid #edf2f7;
        }

        .barangay-name {
            font-weight: 900;
            color: #0f1d44;
        }

        .barangay-muted {
            color: #64748b;
            font-size: 13px;
        }

        .barangay-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .barangay-link {
            color: #c2410c;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
        }

        .barangay-alert {
            border-radius: 16px;
            padding: 13px 16px;
            margin-bottom: 16px;
            font-weight: 700;
        }

        .barangay-alert--success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
        }

        .barangay-empty {
            padding: 42px 20px;
            text-align: center;
            color: #64748b;
        }

        .barangay-pagination {
            padding: 18px 20px;
            border-top: 1px solid #edf2f7;
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

            .barangay-topbar {
                align-items: stretch;
                flex-direction: column;
                border-radius: 22px;
                padding: 18px;
            }

            .barangay-title {
                font-size: 26px;
            }

            .barangay-action {
                width: 100%;
                min-height: 46px;
            }

            .barangay-card {
                border-radius: 20px;
            }

            .barangay-list-header {
                display: none;
            }

            .barangay-row {
                display: block;
                padding: 14px;
            }

            .barangay-row > * + * {
                margin-top: 8px;
            }

            .barangay-row__meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
    @include('chief.partials.sidebar', ['active' => 'barangays'])

    <main class="main-content">
        <div class="barangay-shell">
            <header class="barangay-topbar">
                <div>
                    <p class="barangay-eyebrow">Coverage Setup</p>
                    <h1 class="barangay-title">Barangays</h1>
                    <p class="barangay-subtitle">Add and review barangays used by boundaries, station coverage, reports, and map filters.</p>
                </div>
                <a href="{{ route('station-admin.barangays.create') }}" class="barangay-action">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Add Barangay
                </a>
            </header>

            @if (session('success'))
                <div class="barangay-alert barangay-alert--success">{{ session('success') }}</div>
            @endif

            <section class="barangay-card" aria-label="Barangay list">
                <div class="barangay-list-header">
                    <span>Barangay</span>
                    <span>City / Province</span>
                    <span>Boundaries</span>
                    <span>Stations</span>
                    <span>Action</span>
                </div>

                @forelse ($barangays as $barangay)
                    <article class="barangay-row">
                        <div>
                            <p class="barangay-name">{{ $barangay->barangay_name }}</p>
                            <p class="barangay-muted">Barangay record</p>
                        </div>
                        <div>
                            <p class="barangay-name">{{ $barangay->city?->city_name ?? 'No city assigned' }}</p>
                            <p class="barangay-muted">{{ $barangay->city?->province?->province_name ?? 'No province assigned' }}</p>
                        </div>
                        <div class="barangay-row__meta">
                            <span class="barangay-pill"><i class="fa-solid fa-draw-polygon" aria-hidden="true"></i>{{ $barangay->boundary_locations_count ?? 0 }}</span>
                        </div>
                        <div class="barangay-row__meta">
                            <span class="barangay-pill"><i class="fa-solid fa-fire-extinguisher" aria-hidden="true"></i>{{ $barangay->stations_count ?? 0 }}</span>
                        </div>
                        <div>
                            <a class="barangay-link" href="{{ route('station-admin.barangays.show', $barangay) }}">View details</a>
                        </div>
                    </article>
                @empty
                    <div class="barangay-empty">
                        <p class="font-bold text-slate-800">No barangays yet.</p>
                        <p>Add the first barangay to start building coverage data.</p>
                    </div>
                @endforelse

                @if ($barangays->hasPages())
                    <div class="barangay-pagination">
                        {{ $barangays->links() }}
                    </div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
