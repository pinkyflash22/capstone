<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PyroSense Quick Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #f5ede4;
            --surface: rgba(255, 255, 255, 0.88);
            --surface-strong: #ffffff;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --accent: #d18a16;
            --border: rgba(198, 61, 38, 0.12);
            --shadow: 0 24px 60px rgba(97, 41, 24, 0.16);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(209, 138, 22, 0.22), transparent 24%),
                radial-gradient(circle at bottom left, rgba(198, 61, 38, 0.14), transparent 30%),
                linear-gradient(180deg, #fff7ef 0%, var(--bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .shell {
            width: min(100%, 460px);
        }

        .card {
            background: var(--surface);
            backdrop-filter: blur(18px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 1.4rem;
            box-shadow: var(--shadow);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(198, 61, 38, 0.08);
            color: var(--primary);
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: clamp(2rem, 5vw, 2.7rem);
            line-height: 1.02;
            margin-bottom: 0.8rem;
        }

        .lead {
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 1.3rem;
        }

        .panel {
            background: var(--surface-strong);
            border: 1px solid rgba(198, 61, 38, 0.08);
            border-radius: 22px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .panel strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 0.35rem;
        }

        .panel p {
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.94rem;
        }

        .cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            width: 100%;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 18px;
            padding: 1rem 1.1rem;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 18px 34px rgba(198, 61, 38, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 38px rgba(198, 61, 38, 0.28);
        }

        .subcopy {
            margin-top: 0.9rem;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .blocked {
            display: none;
            margin-top: 1rem;
            background: #fff4f1;
            color: #9a3412;
            border: 1px solid rgba(154, 52, 18, 0.14);
            border-radius: 18px;
            padding: 1rem;
            line-height: 1.55;
        }

        .blocked a {
            color: var(--primary-dark);
            font-weight: 700;
        }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    <main class="shell">
        <section class="card">
            <div class="eyebrow">
                <i class="fa-solid fa-mobile-screen-button"></i>
                PWA Quick Access
            </div>

            <h1>One-click report.</h1>
            <p class="lead">This screen is reserved for the installed PyroSense app so citizens can move straight into reporting from the home screen.</p>

            <div class="panel">
                <strong>What happens next</strong>
                <p>Tap the button below, sign in, and the citizen report form opens automatically inside the app.</p>
            </div>

            <a href="{{ route('auth.login', ['post_login_action' => 'quick-report']) }}" class="cta" id="quickReportCta">
                <i class="fa-solid fa-bolt"></i>
                One-Click Report
            </a>

            <p class="subcopy">This handoff uses the current app login flow. Google OAuth can be attached here once it is configured server-side.</p>

            <div class="blocked" id="browserOnlyMessage">
                This page only works from the installed PWA. Open PyroSense from your home screen instead, or go back to the <a href="{{ route('home') }}">landing page</a>.
            </div>
        </section>
    </main>

    <script>
        (function () {
            const isStandalone = window.laravelPwaInstall?.isStandalone?.()
                || window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            if (isStandalone) {
                return;
            }

            document.getElementById('quickReportCta').style.display = 'none';
            document.getElementById('browserOnlyMessage').style.display = 'block';
        })();
    </script>
    @include('partials.pwa-body')
</body>
</html>
