<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <title>PyroSense Mobile Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @include('partials.pwa-head')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #fff7ef;
            --card: #ffffff;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: rgba(198, 61, 38, 0.12);
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --soft: #fff1e7;
        }

        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(245, 158, 11, 0.18), transparent 26%),
                radial-gradient(circle at bottom left, rgba(198, 61, 38, 0.12), transparent 28%),
                var(--bg);
            color: var(--ink);
        }

        .mobile-hub-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: calc(env(safe-area-inset-top) + 1.25rem) 1rem calc(env(safe-area-inset-bottom) + 1.25rem);
        }

        .mobile-hub-stack {
            width: min(100%, 430px);
            display: grid;
            gap: 0.7rem;
        }

        .mobile-hub-context {
            display: grid;
            gap: 0.55rem;
        }

        .mobile-hub-context-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.8rem 0.9rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(198, 61, 38, 0.1);
            box-shadow: 0 14px 32px rgba(198, 61, 38, 0.08);
            backdrop-filter: blur(12px);
        }

        .mobile-hub-context-copy strong,
        .mobile-hub-context-time strong {
            display: block;
            font-size: 0.82rem;
            line-height: 1.2;
            color: var(--ink);
        }

        .mobile-hub-context-copy small,
        .mobile-hub-context-time small {
            display: block;
            margin-top: 0.14rem;
            font-size: 0.68rem;
            color: var(--muted);
        }

        .mobile-hub-context-time {
            text-align: right;
            white-space: nowrap;
        }

        .mobile-hub-alert-tip {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.72rem 0.85rem;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
            border: 1px solid rgba(225, 29, 72, 0.08);
            color: #9f1239;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .mobile-hub-card {
            width: min(100%, 430px);
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 26px 60px rgba(198, 61, 38, 0.16);
            padding: 1rem;
            backdrop-filter: blur(14px);
        }

        .mobile-hub-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.2rem 0.2rem 1rem;
        }

        .mobile-hub-logo {
            width: 76px;
            height: 58px;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 12px 28px rgba(198, 61, 38, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.6rem;
        }

        .mobile-hub-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .mobile-hub-title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .mobile-hub-title span {
            color: var(--primary);
        }

        .mobile-hub-copy {
            margin: 0.2rem 0 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .mobile-hub-actions {
            display: grid;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .mobile-hub-hotlines {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .mobile-hub-btn {
            width: 100%;
            border: none;
            text-decoration: none;
            border-radius: 20px;
            padding: 0.95rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.95rem;
            color: inherit;
            cursor: pointer;
        }

        .mobile-hub-btn__icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.15rem;
        }

        .mobile-hub-btn__copy strong {
            display: block;
            font-size: 0.98rem;
            line-height: 1.2;
        }

        .mobile-hub-btn__copy small {
            display: block;
            margin-top: 0.18rem;
            font-size: 0.78rem;
            line-height: 1.4;
            opacity: 0.84;
        }

        .mobile-hub-btn--report {
            background: linear-gradient(135deg, var(--primary) 0%, #e1602d 100%);
            color: #ffffff;
            box-shadow: 0 18px 36px rgba(198, 61, 38, 0.24);
            padding-block: 1.2rem;
        }

        .mobile-hub-btn--report .mobile-hub-btn__icon {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
        }

        .mobile-hub-btn--hotline {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(245, 158, 11, 0.18);
            color: #9a3412;
        }

        .mobile-hub-btn--hotline .mobile-hub-btn__icon {
            background: rgba(245, 158, 11, 0.14);
            color: var(--accent-dark);
        }

        .mobile-hub-btn--hotline-urgent {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 1px solid rgba(225, 29, 72, 0.16);
            color: #9f1239;
        }

        .mobile-hub-btn--hotline-urgent .mobile-hub-btn__icon {
            background: rgba(225, 29, 72, 0.12);
            color: #be123c;
        }

        .mobile-hub-btn--dashboard,
        .mobile-hub-btn--map {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: var(--ink);
        }

        .mobile-hub-btn--dashboard .mobile-hub-btn__icon,
        .mobile-hub-btn--map .mobile-hub-btn__icon {
            background: var(--soft);
            color: var(--primary);
        }

        .mobile-hub-btn--compact {
            justify-content: center;
            gap: 0.7rem;
            padding: 0.95rem 0.85rem;
        }

        .mobile-hub-btn--compact .mobile-hub-btn__icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            font-size: 1rem;
        }

        .mobile-hub-btn--compact .mobile-hub-btn__copy strong {
            font-size: 0.9rem;
        }

        .mobile-hub-btn--compact .mobile-hub-btn__copy small {
            margin-top: 0.1rem;
            font-size: 0.72rem;
        }

        .mobile-hub-footer {
            display: grid;
            gap: 0.55rem;
        }

        .mobile-hub-utility {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.8rem 0.9rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.68);
            border: 1px solid rgba(198, 61, 38, 0.08);
            box-shadow: 0 14px 30px rgba(198, 61, 38, 0.06);
            backdrop-filter: blur(12px);
        }

        .mobile-hub-utility strong {
            display: block;
            font-size: 0.78rem;
            color: var(--ink);
        }

        .mobile-hub-utility small {
            display: block;
            margin-top: 0.12rem;
            font-size: 0.66rem;
            color: var(--muted);
        }

        .mobile-hub-install {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            font-size: 0.78rem;
        }

        .mobile-hub-status {
            display: none;
            margin-top: 0.85rem;
            border-radius: 18px;
            padding: 0.9rem 1rem;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .mobile-hub-status.is-visible {
            display: block;
        }

        .mobile-hub-status.is-error {
            background: #fff1f2;
            color: #9f1239;
            border: 1px solid rgba(190, 24, 93, 0.14);
        }

        .mobile-hub-status.is-success {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.16);
        }

        .mobile-hub-btn.is-loading {
            opacity: 0.78;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .mobile-hub-shell {
                padding: calc(env(safe-area-inset-top) + 0.65rem) 0.7rem calc(env(safe-area-inset-bottom) + 0.75rem);
            }

            .mobile-hub-card {
                border-radius: 18px;
                padding: 0.75rem;
            }

            .mobile-hub-context-bar,
            .mobile-hub-utility {
                border-radius: 14px;
                padding: 0.65rem 0.75rem;
            }

            .mobile-hub-context-copy strong,
            .mobile-hub-context-time strong,
            .mobile-hub-utility strong {
                font-size: 0.74rem;
            }

            .mobile-hub-context-copy small,
            .mobile-hub-context-time small,
            .mobile-hub-utility small,
            .mobile-hub-install {
                font-size: 0.62rem;
            }

            .mobile-hub-alert-tip {
                border-radius: 13px;
                padding: 0.6rem 0.72rem;
                font-size: 0.66rem;
            }

            .mobile-hub-brand {
                gap: 0.6rem;
                padding: 0.05rem 0.05rem 0.6rem;
            }

            .mobile-hub-logo {
                width: 54px;
                height: 42px;
                border-radius: 12px;
                padding: 0.3rem 0.4rem;
            }

            .mobile-hub-title {
                font-size: 1.05rem;
            }

            .mobile-hub-copy {
                font-size: 0.74rem;
            }

            .mobile-hub-actions {
                gap: 0.55rem;
                margin-top: 0.7rem;
            }

            .mobile-hub-hotlines {
                gap: 0.5rem;
            }

            .mobile-hub-btn {
                border-radius: 14px;
                padding: 0.68rem 0.72rem;
                gap: 0.6rem;
            }

            .mobile-hub-btn__icon {
                width: 36px;
                height: 36px;
                border-radius: 11px;
                font-size: 0.9rem;
            }

            .mobile-hub-btn__copy strong {
                font-size: 0.8rem;
            }

            .mobile-hub-btn__copy small {
                font-size: 0.64rem;
                line-height: 1.3;
            }

            .mobile-hub-btn--report {
                padding-block: 0.8rem;
            }

            .mobile-hub-btn--compact {
                padding: 0.62rem 0.5rem;
                gap: 0.4rem;
            }

            .mobile-hub-btn--compact .mobile-hub-btn__icon {
                width: 30px;
                height: 30px;
                border-radius: 9px;
                font-size: 0.8rem;
            }

            .mobile-hub-btn--compact .mobile-hub-btn__copy strong {
                font-size: 0.74rem;
            }

            .mobile-hub-btn--compact .mobile-hub-btn__copy small {
                font-size: 0.6rem;
            }

            .mobile-hub-status {
                margin-top: 0.65rem;
                border-radius: 12px;
                padding: 0.65rem 0.75rem;
                font-size: 0.72rem;
            }

            .mobile-hub-footer {
                gap: 0.45rem;
            }
        }

        @media (max-width: 340px) {
            .mobile-hub-hotlines {
                grid-template-columns: 1fr;
            }

            .mobile-hub-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <main class="mobile-hub-shell">
        <div class="mobile-hub-stack">
        <section class="mobile-hub-context" aria-label="Mobile hub context">
            <div class="mobile-hub-context-bar">
                <div class="mobile-hub-context-copy">
                    <strong id="mobileHubLocationLabel">Location ready</strong>
                    <small id="mobileHubLocationHint">Preparing your current-location report access.</small>
                </div>
                <div class="mobile-hub-context-time">
                    <strong id="mobileHubClock">--:--</strong>
                    <small>Local time</small>
                </div>
            </div>
            <div class="mobile-hub-alert-tip">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>If there is immediate danger to life, call <strong>911</strong> first before sending a report.</span>
            </div>
        </section>

        <section class="mobile-hub-card">
            <div class="mobile-hub-brand">
                <div class="mobile-hub-logo">
                    <img src="{{ asset('storage/defaults/pyrosense.png') }}" alt="PyroSense logo">
                </div>
                <div>
                    <div class="mobile-hub-title">Pyro<span>Sense</span></div>
                    <p class="mobile-hub-copy">Quick access for mobile users.</p>
                </div>
            </div>

            <div class="mobile-hub-actions">
                <button type="button" class="mobile-hub-btn mobile-hub-btn--report" id="mobileHubReportButton">
                    <span class="mobile-hub-btn__icon"><i class="fa-solid fa-fire-flame-curved"></i></span>
                    <span class="mobile-hub-btn__copy">
                        <strong>REPORT FIRE EMERGENCY</strong>
                        <small>Send your current-location report immediately.</small>
                    </span>
                </button>

                <div class="mobile-hub-hotlines">
                    <a href="tel:911" class="mobile-hub-btn mobile-hub-btn--hotline-urgent mobile-hub-btn--compact">
                        <span class="mobile-hub-btn__icon"><i class="fa-solid fa-phone"></i></span>
                        <span class="mobile-hub-btn__copy">
                            <strong>Call 911</strong>
                            <small>Emergency line</small>
                        </span>
                    </a>

                    <a href="tel:09171891611" class="mobile-hub-btn mobile-hub-btn--hotline mobile-hub-btn--compact">
                        <span class="mobile-hub-btn__icon"><i class="fa-solid fa-phone-volume"></i></span>
                        <span class="mobile-hub-btn__copy">
                            <strong>Call BFP</strong>
                            <small>Station hotline</small>
                        </span>
                    </a>
                </div>

                <a href="{{ route('citizen.dashboard') }}" class="mobile-hub-btn mobile-hub-btn--dashboard">
                    <span class="mobile-hub-btn__icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <span class="mobile-hub-btn__copy">
                        <strong>Dashboard / History</strong>
                        <small>Go to your citizen dashboard and report history.</small>
                    </span>
                </a>

                <a href="{{ route('citizen.dashboard') }}" class="mobile-hub-btn mobile-hub-btn--map">
                    <span class="mobile-hub-btn__icon"><i class="fa-solid fa-map-location-dot"></i></span>
                    <span class="mobile-hub-btn__copy">
                        <strong>View Map</strong>
                        <small>Open the citizen dashboard map and live alerts.</small>
                    </span>
                </a>
            </div>

            <div id="mobileHubStatus" class="mobile-hub-status" role="status" aria-live="polite"></div>
        </section>

        <div class="mobile-hub-footer">
            <div class="mobile-hub-utility">
                <div>
                    <strong id="mobileHubStationLabel">Nearest station</strong>
                    <small id="mobileHubStationHint">Checking closest fire station for your area.</small>
                </div>
                <span class="mobile-hub-install">Install App</span>
            </div>
        </div>
        </div>
    </main>
    @vite(['resources/js/mobile-hub.js'])
    @include('partials.pwa-body')
</body>
</html>
