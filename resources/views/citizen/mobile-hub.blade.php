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
    <link rel="prefetch" href="{{ Vite::asset('resources/js/user.js') }}" as="script">
    <link rel="prefetch" href="{{ Vite::asset('resources/css/citizen/dashboard.css') }}" as="style">
    <link rel="prefetch" href="{{ Vite::asset('resources/css/app.css') }}" as="style">
    @include('partials.pwa-head')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #fff7ef;
            --card: rgba(255, 255, 255, 0.94);
            --ink: #111827;
            --muted: #6b7280;
            --line: rgba(198, 61, 38, 0.12);
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --soft: #fff1e7;
            --danger-soft: #fff1f2;
        }

        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body.mobile-hub-page {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(245, 158, 11, 0.18), transparent 24%),
                radial-gradient(circle at left bottom, rgba(198, 61, 38, 0.12), transparent 28%),
                var(--bg);
            color: var(--ink);
        }

        .mobile-hub-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: calc(env(safe-area-inset-top) + 0.95rem) 1rem calc(env(safe-area-inset-bottom) + 1.2rem);
        }

        .mobile-hub-stack {
            width: min(100%, 420px);
            display: grid;
            gap: 0.95rem;
        }

        .mobile-hub-hero {
            display: grid;
            gap: 0.9rem;
        }

        .mobile-hub-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .mobile-hub-greeting small {
            display: block;
            margin-bottom: 0.14rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--muted);
        }

        .mobile-hub-greeting strong {
            display: block;
            font-size: 1.16rem;
            font-weight: 900;
            line-height: 1.12;
            letter-spacing: -0.03em;
            color: var(--ink);
        }

        .mobile-hub-profile {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            border: 1px solid rgba(198, 61, 38, 0.12);
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.14), rgba(245, 158, 11, 0.22));
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.12);
            display: grid;
            place-items: center;
            color: var(--primary);
            font-size: 1.05rem;
        }

        .mobile-hub-card {
            width: 100%;
            border-radius: 28px;
            border: 1px solid var(--line);
            background: var(--card);
            box-shadow: 0 26px 60px rgba(198, 61, 38, 0.16);
            backdrop-filter: blur(14px);
        }

        .mobile-hub-emergency-card {
            padding: 1.08rem 1rem 1rem;
            display: grid;
            gap: 0.95rem;
        }

        .mobile-hub-headline {
            display: grid;
            gap: 0.3rem;
            text-align: center;
            padding: 0.05rem 0.2rem;
        }

        .mobile-hub-headline h1 {
            margin: 0;
            font-size: 1.84rem;
            font-weight: 900;
            line-height: 1.04;
            letter-spacing: -0.05em;
            color: var(--ink);
        }

        .mobile-hub-headline p {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.45;
            color: var(--muted);
        }

        .mobile-hub-call-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.72rem;
        }

        .mobile-hub-call-btn {
            width: 100%;
            border: none;
            text-decoration: none;
            border-radius: 20px;
            padding: 0.9rem 0.78rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.58rem;
            font-size: 0.86rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
        }

        .mobile-hub-call-btn:hover,
        .mobile-hub-btn:hover,
        .mobile-hub-rfi-button:hover {
            transform: translateY(-1px);
        }

        .mobile-hub-call-btn i {
            font-size: 0.94rem;
        }

        .mobile-hub-call-btn--bfp {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(245, 158, 11, 0.18);
            color: #9a3412;
            box-shadow: 0 10px 22px rgba(245, 158, 11, 0.1);
        }

        .mobile-hub-call-btn--urgent {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 1px solid rgba(225, 29, 72, 0.16);
            color: #9f1239;
            box-shadow: 0 10px 22px rgba(225, 29, 72, 0.08);
        }

        .mobile-hub-rfi-wrap {
            display: grid;
            place-items: center;
            margin-top: 0.05rem;
        }

        .mobile-hub-rfi-rings {
            position: relative;
            width: 236px;
            height: 236px;
            display: grid;
            place-items: center;
        }

        .mobile-hub-rfi-rings::before,
        .mobile-hub-rfi-rings::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            animation: mobile-hub-rfi-pulse 2.4s ease-out infinite;
        }

        .mobile-hub-rfi-rings::before {
            width: 236px;
            height: 236px;
            background: rgba(198, 61, 38, 0.08);
            animation-delay: 0s;
        }

        .mobile-hub-rfi-rings::after {
            width: 188px;
            height: 188px;
            background: rgba(198, 61, 38, 0.12);
            animation-delay: 0.55s;
        }

        .mobile-hub-rfi-button {
            position: relative;
            z-index: 1;
            width: 138px;
            height: 138px;
            border: none;
            border-radius: 999px;
            background: radial-gradient(circle at top, #ff8a72 0%, #eb5b45 46%, #c63d26 100%);
            box-shadow:
                0 24px 42px rgba(198, 61, 38, 0.28),
                inset 0 4px 12px rgba(255, 255, 255, 0.22);
            color: #fff;
            display: grid;
            place-items: center;
            text-align: center;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
        }

        .mobile-hub-rfi-button strong {
            display: block;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.05em;
        }

        .mobile-hub-rfi-button small {
            display: block;
            margin-top: 0.34rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.92;
        }

        @keyframes mobile-hub-rfi-pulse {
            0% {
                transform: scale(0.92);
                opacity: 0.52;
            }
            45% {
                transform: scale(1.02);
                opacity: 0.18;
            }
            100% {
                transform: scale(1.12);
                opacity: 0;
            }
        }

        .mobile-hub-rfi-button.is-loading {
            opacity: 0.82;
            pointer-events: none;
        }

        .mobile-hub-address-card {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            align-items: center;
            gap: 0.8rem;
            padding: 0.95rem;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(198, 61, 38, 0.1);
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.08);
        }

        .mobile-hub-address-avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.14), rgba(245, 158, 11, 0.18));
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 0.95rem;
        }

        .mobile-hub-address-copy strong {
            display: block;
            font-size: 0.88rem;
            line-height: 1.2;
            color: var(--ink);
        }

        .mobile-hub-address-copy small {
            display: block;
            margin-top: 0.18rem;
            font-size: 0.72rem;
            line-height: 1.4;
            color: var(--muted);
        }

        .mobile-hub-status {
            display: none;
            border-radius: 18px;
            padding: 0.88rem 0.96rem;
            font-size: 0.82rem;
            line-height: 1.45;
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
        .mobile-hub-status.is-warning {
            border-color: rgba(245, 158, 11, .36);
            background: rgba(255, 251, 235, .96);
            color: #92400e;
        }

        .mobile-hub-alert-tip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.72rem 0.85rem;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
            border: 1px solid rgba(225, 29, 72, 0.08);
            color: #9f1239;
            text-align: center;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .mobile-hub-actions {
            display: grid;
            gap: 0.72rem;
        }

        .mobile-hub-dispatch-card {
            padding: 0.92rem 1rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(198, 61, 38, 0.08);
            box-shadow: 0 14px 26px rgba(198, 61, 38, 0.06);
        }

        .mobile-hub-dispatch-card strong {
            display: block;
            font-size: 0.8rem;
            color: var(--ink);
        }

        .mobile-hub-dispatch-card small {
            display: block;
            margin-top: 0.12rem;
            font-size: 0.68rem;
            color: var(--muted);
        }

        .mobile-hub-nav-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.72rem;
        }

        .mobile-hub-btn {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.2);
            text-decoration: none;
            border-radius: 20px;
            padding: 0.92rem 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: #fff;
            color: inherit;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
        }

        .mobile-hub-btn__icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
            background: var(--soft);
            color: var(--primary);
        }

        .mobile-hub-btn__copy strong {
            display: block;
            font-size: 0.88rem;
            line-height: 1.2;
        }

        .mobile-hub-btn__copy small {
            display: block;
            margin-top: 0.12rem;
            font-size: 0.68rem;
            line-height: 1.35;
            color: var(--muted);
        }

        .mobile-hub-toast {
            position: fixed;
            left: 50%;
            bottom: calc(env(safe-area-inset-bottom) + 1rem);
            z-index: 80;
            max-width: min(320px, calc(100vw - 2rem));
            transform: translate(-50%, 120%);
            border-radius: 999px;
            padding: 0.7rem 0.86rem;
            font-size: 0.82rem;
            font-weight: 800;
            line-height: 1.25;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
        }

        .mobile-hub-toast__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            min-width: min(290px, calc(100vw - 2.75rem));
        }

        .mobile-hub-toast__message {
            flex: 1 1 auto;
            min-width: 0;
            text-align: left;
        }

        .mobile-hub-toast__action {
            flex: 0 0 auto;
            border: none;
            border-radius: 999px;
            padding: 0.46rem 0.88rem;
            font-size: 0.74rem;
            font-weight: 900;
            line-height: 1;
            cursor: pointer;
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        .mobile-hub-toast__action:hover {
            transform: translateY(-1px);
        }

        .mobile-hub-toast.is-visible {
            transform: translate(-50%, 0);
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-hub-toast.is-success {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
        }

        .mobile-hub-toast.is-error {
            background: #fff1f2;
            color: #9f1239;
            border: 1px solid rgba(190, 24, 93, 0.16);
        }

        .mobile-hub-toast.is-success .mobile-hub-toast__action {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .mobile-hub-toast.is-error .mobile-hub-toast__action {
            background: rgba(159, 18, 57, 0.08);
            color: #9f1239;
            border: 1px solid rgba(159, 18, 57, 0.16);
        }

        .mobile-hub-confirm {
            position: fixed;
            left: 50%;
            bottom: calc(env(safe-area-inset-bottom) + 1.1rem);
            z-index: 120;
            width: min(360px, calc(100vw - 1.5rem));
            transform: translate(-50%, 125%);
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
        }

        .mobile-hub-confirm.is-visible {
            transform: translate(-50%, 0);
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-hub-confirm__card {
            border-radius: 22px;
            border: 1px solid rgba(225, 29, 72, 0.12);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
            padding: 1rem;
            display: grid;
            gap: 0.8rem;
        }

        .mobile-hub-confirm__eyebrow {
            margin: 0;
            color: #be123c;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .mobile-hub-confirm__title {
            margin: 0.1rem 0 0;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.15;
        }

        .mobile-hub-confirm__text {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .mobile-hub-confirm__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.62rem;
        }

        .mobile-hub-confirm__btn {
            min-height: 44px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: #fff;
            color: var(--ink);
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
        }

        .mobile-hub-confirm__btn--primary {
            border-color: transparent;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
        }

        @media (max-width: 768px) {
            .mobile-hub-shell {
                padding: calc(env(safe-area-inset-top) + 0.74rem) 0.72rem calc(env(safe-area-inset-bottom) + 0.82rem);
            }

            .mobile-hub-stack {
                gap: 0.78rem;
            }

            .mobile-hub-card {
                border-radius: 22px;
            }

            .mobile-hub-profile {
                width: 46px;
                height: 46px;
                border-radius: 15px;
                font-size: 0.96rem;
            }

            .mobile-hub-greeting strong {
                font-size: 1rem;
            }

            .mobile-hub-emergency-card {
                padding: 0.9rem 0.82rem 0.82rem;
                gap: 0.8rem;
            }

            .mobile-hub-headline h1 {
                font-size: 1.46rem;
            }

            .mobile-hub-headline p {
                font-size: 0.76rem;
            }

            .mobile-hub-call-actions {
                gap: 0.52rem;
            }

            .mobile-hub-call-btn {
                border-radius: 16px;
                padding: 0.72rem 0.5rem;
                font-size: 0.74rem;
            }

            .mobile-hub-rfi-rings {
                width: 198px;
                height: 198px;
            }

            .mobile-hub-rfi-rings::before {
                width: 198px;
                height: 198px;
            }

            .mobile-hub-rfi-rings::after {
                width: 156px;
                height: 156px;
            }

            .mobile-hub-rfi-button {
                width: 118px;
                height: 118px;
            }

            .mobile-hub-rfi-button strong {
                font-size: 1.68rem;
            }

            .mobile-hub-rfi-button small {
                font-size: 0.6rem;
            }

            .mobile-hub-address-card {
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 0.66rem;
                padding: 0.78rem;
                border-radius: 16px;
            }

            .mobile-hub-address-avatar {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                font-size: 0.82rem;
            }

            .mobile-hub-address-copy strong,
            .mobile-hub-dispatch-card strong {
                font-size: 0.78rem;
            }

            .mobile-hub-address-copy small,
            .mobile-hub-dispatch-card small {
                font-size: 0.62rem;
            }

            .mobile-hub-status {
                border-radius: 12px;
                padding: 0.65rem 0.75rem;
                font-size: 0.72rem;
            }

            .mobile-hub-alert-tip {
                border-radius: 13px;
                padding: 0.6rem 0.72rem;
                font-size: 0.66rem;
            }

            .mobile-hub-dispatch-card {
                padding: 0.76rem 0.82rem;
                border-radius: 15px;
            }

            .mobile-hub-nav-grid {
                gap: 0.55rem;
            }

            .mobile-hub-btn {
                border-radius: 15px;
                padding: 0.76rem 0.72rem;
                gap: 0.58rem;
            }

            .mobile-hub-btn__icon {
                width: 36px;
                height: 36px;
                border-radius: 11px;
                font-size: 0.9rem;
            }

            .mobile-hub-btn__copy strong {
                font-size: 0.78rem;
            }

            .mobile-hub-btn__copy small {
                font-size: 0.62rem;
            }

            .mobile-hub-confirm {
                width: min(100vw - 1rem, 360px);
                bottom: calc(env(safe-area-inset-bottom) + 0.8rem);
            }

            .mobile-hub-confirm__card {
                border-radius: 18px;
                padding: 0.82rem;
                gap: 0.7rem;
            }

            .mobile-hub-confirm__title {
                font-size: 0.92rem;
            }

            .mobile-hub-confirm__text,
            .mobile-hub-confirm__btn {
                font-size: 0.74rem;
            }
        }

        @media (max-width: 340px) {
            .mobile-hub-call-actions,
            .mobile-hub-nav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="mobile-hub-page">
    <main class="mobile-hub-shell">
        <div class="mobile-hub-stack">
            <section class="mobile-hub-hero" aria-label="Mobile hub context">
                <div class="mobile-hub-topline">
                    <div class="mobile-hub-greeting">
                        <small>Welcome back</small>
                        <strong>{{ auth()->user()?->first_name ?: 'Citizen' }}</strong>
                    </div>
                    <div class="mobile-hub-profile" aria-hidden="true">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                </div>

                <section class="mobile-hub-card mobile-hub-emergency-card">
                    <div class="mobile-hub-headline">
                        <h1>Are you in emergency?</h1>
                        <p>Press the button below and help will reach you soon.</p>
                    </div>

                    <div class="mobile-hub-call-actions">
                        <button type="button" class="mobile-hub-call-btn mobile-hub-call-btn--bfp" data-pyro-voice-call>
                            <i class="fa-solid fa-phone-volume"></i>
                            <span class="mobile-hub-btn__copy">
                                <strong>Call BFP</strong>
                            </span>
                        </button>
                        <button type="button" class="mobile-hub-call-btn mobile-hub-call-btn--urgent" id="mobileHubCall911Button">
                            <i class="fa-solid fa-phone"></i>
                            <span class="mobile-hub-btn__copy">
                                <strong>Call 911</strong>
                            </span>
                        </button>
                    </div>

                    <div class="mobile-hub-rfi-wrap">
                        <div class="mobile-hub-rfi-rings">
                            <button type="button" class="mobile-hub-rfi-button" id="mobileHubReportButton">
                                <span>
                                    <strong>RFI</strong>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="mobile-hub-address-card">
                        <div class="mobile-hub-address-avatar" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="mobile-hub-address-copy">
                            <strong id="mobileHubLocationLabel">Current location</strong>
                            <small id="mobileHubLocationHint">Allow location access so responders can reach you faster.</small>
                        </div>
                    </div>

                    <div id="mobileHubStatus" class="mobile-hub-status" role="status" aria-live="polite"></div>
                </section>

                <div class="mobile-hub-alert-tip">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>In immediate danger, call <strong>911</strong> first.</span>
                </div>
            </section>

            <div class="mobile-hub-actions">
                <div class="mobile-hub-dispatch-card">
                    <strong id="mobileHubStationLabel">Dispatch matching</strong>
                    <small id="mobileHubStationHint">Nearest available team is selected.</small>
                    <small id="mobileHubClock" style="margin-top:0.42rem;">--:--</small>
                </div>

                <div class="mobile-hub-nav-grid">
                    <a href="{{ rtrim(route('citizen.dashboard'), '/') }}/alerts?reports=history&standalone=1&source=mobile-hub" class="mobile-hub-btn mobile-hub-btn--dashboard" data-mobile-hub-nav="history">
                        <span class="mobile-hub-btn__icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <span class="mobile-hub-btn__copy">
                            <strong>History</strong>
                            <small>View past reports</small>
                        </span>
                    </a>

                    <a href="{{ route('citizen.dashboard') }}" class="mobile-hub-btn mobile-hub-btn--map" data-mobile-hub-nav="map">
                        <span class="mobile-hub-btn__icon"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span class="mobile-hub-btn__copy">
                            <strong>View Map</strong>
                            <small>Open citizen dashboard</small>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </main>
    <div class="mobile-hub-confirm" id="mobileHubConfirm911" aria-hidden="true">
        <div class="mobile-hub-confirm__card" role="dialog" aria-modal="false" aria-labelledby="mobileHubConfirm911Title">
            <div>
                <p class="mobile-hub-confirm__eyebrow">Emergency call</p>
                <h2 class="mobile-hub-confirm__title" id="mobileHubConfirm911Title">Open the 911 dialer?</h2>
            </div>
            <p class="mobile-hub-confirm__text">This will open your phone dialer with <strong>911</strong> ready to call.</p>
            <div class="mobile-hub-confirm__actions">
                <button type="button" class="mobile-hub-confirm__btn" id="mobileHubConfirm911Cancel">Cancel</button>
                <button type="button" class="mobile-hub-confirm__btn mobile-hub-confirm__btn--primary" id="mobileHubConfirm911Proceed">Call 911</button>
            </div>
        </div>
    </div>
    @include('partials.voice-call-config')
    @vite(['resources/js/mobile-hub.js'])
    @include('partials.pwa-body')
</body>
</html>
