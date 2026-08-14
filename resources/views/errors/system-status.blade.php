@php
    $errorPages = [
        400 => [
            'label' => 'Request Check',
            'title' => 'That request could not be processed.',
            'message' => 'The command sent to PyroSense was incomplete or invalid. No operational data was changed.',
            'action' => 'Return to safety',
            'icon' => 'request',
        ],
        404 => [
            'label' => 'Route Not Found',
            'title' => 'This command route is unavailable.',
            'message' => 'The page may have moved, the link may be outdated, or access to this route is no longer available.',
            'action' => 'Return to safety',
            'icon' => 'route',
        ],
        500 => [
            'label' => 'System Incident',
            'title' => 'PyroSense hit an internal system error.',
            'message' => 'Our command service could not complete this operation. The technical details were protected and recorded for review.',
            'action' => 'Try again',
            'icon' => 'alert',
        ],
        503 => [
            'label' => 'Service Recovery',
            'title' => 'The command service is temporarily offline.',
            'message' => 'PyroSense is recovering or undergoing scheduled maintenance. Live operations will return as soon as service is restored.',
            'action' => 'Check service again',
            'icon' => 'pulse',
        ],
    ];
    $page = $errorPages[$status] ?? $errorPages[500];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#a5231a">
    <title>{{ $status }} | PyroSense Command</title>
    <style>
        :root { --ember: #c63d26; --command: #8e1c16; --amber: #d18a16; --ink: #1f2937; --muted: #64748b; --paper: #fffdf9; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; overflow-x: hidden; color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: radial-gradient(circle at 85% 12%, rgba(209, 138, 22, .18), transparent 27rem), radial-gradient(circle at 7% 82%, rgba(198, 61, 38, .14), transparent 31rem), #f7f4ee; }
        .grid { position: fixed; inset: 0; pointer-events: none; opacity: .42; background-image: linear-gradient(rgba(142, 28, 22, .055) 1px, transparent 1px), linear-gradient(90deg, rgba(142, 28, 22, .055) 1px, transparent 1px); background-size: 42px 42px; mask-image: linear-gradient(to bottom, black, transparent); }
        .page { width: min(100% - 40px, 1000px); min-height: 100vh; margin: auto; display: grid; align-content: center; padding: 52px 0; position: relative; }
        .bar { display: flex; align-items: center; gap: 12px; color: #7f1d1d; font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; animation: reveal .55s ease-out both; }
        .brand { width: 40px; height: 40px; display: grid; place-items: center; color: white; background: linear-gradient(135deg, var(--ember), var(--command)); border-radius: 12px; box-shadow: 0 10px 24px rgba(142, 28, 22, .2); }
        .card { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(260px, .8fr); gap: 42px; margin-top: 24px; padding: clamp(30px, 5vw, 62px); background: rgba(255, 253, 249, .87); border: 1px solid rgba(142, 28, 22, .13); border-radius: 30px; box-shadow: 0 30px 80px rgba(94, 32, 20, .13); backdrop-filter: blur(14px); animation: rise .65s .08s ease-out both; }
        .code { margin: 0; font: 800 clamp(74px, 13vw, 142px)/.82 Georgia, serif; letter-spacing: -.08em; color: #f0d6c1; text-shadow: 2px 2px 0 rgba(198, 61, 38, .16); }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; margin-top: 26px; color: var(--ember); font-size: 12px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .eyebrow::before { content: ''; width: 22px; height: 2px; background: currentColor; }
        h1 { max-width: 620px; margin: 13px 0; font: 700 clamp(32px, 4.3vw, 54px)/1.03 Georgia, serif; letter-spacing: -.035em; }
        p { max-width: 590px; margin: 0; color: var(--muted); font-size: 16px; line-height: 1.75; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 0 19px; border-radius: 13px; font: 800 14px/1 Inter, ui-sans-serif, system-ui, sans-serif; text-decoration: none; cursor: pointer; transition: transform .2s ease, box-shadow .2s ease; }
        .button:hover { transform: translateY(-2px); }
        .button-primary { border: 0; color: #fff; background: linear-gradient(135deg, var(--ember), var(--command)); box-shadow: 0 13px 24px rgba(142, 28, 22, .22); }
        .button-secondary { border: 1px solid rgba(142, 28, 22, .16); color: #8e1c16; background: #fff8f2; }
        .status { position: relative; display: grid; align-content: space-between; min-height: 290px; padding: 26px; overflow: hidden; color: #fff; background: linear-gradient(155deg, #a5231a 0%, #701a16 100%); border-radius: 22px; box-shadow: inset 0 1px rgba(255,255,255,.18); }
        .status::after { content: ''; position: absolute; width: 240px; height: 240px; border: 1px solid rgba(255,255,255,.17); border-radius: 50%; right: -104px; bottom: -104px; box-shadow: 0 0 0 25px rgba(255,255,255,.04), 0 0 0 50px rgba(255,255,255,.03); animation: sonar 3s ease-out infinite; }
        .status-top { display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.75); }
        .live { display: flex; gap: 7px; align-items: center; } .live i { width: 8px; height: 8px; display: block; border-radius: 50%; background: #facc15; box-shadow: 0 0 0 0 rgba(250, 204, 21, .7); animation: beacon 1.8s infinite; }
        .status svg { width: 78px; height: 78px; margin: 34px 0 18px; stroke: currentColor; fill: none; stroke-width: 1.3; stroke-linecap: round; stroke-linejoin: round; animation: float 3.2s ease-in-out infinite; }
        .status strong { display: block; max-width: 220px; font: 700 23px/1.1 Georgia, serif; position: relative; z-index: 1; }
        .status small { display: block; margin-top: 9px; color: rgba(255,255,255,.7); line-height: 1.55; position: relative; z-index: 1; }
        .footer { margin: 22px 4px 0; color: #7c665a; font-size: 12px; animation: reveal .55s .2s ease-out both; }
        @keyframes reveal { from { opacity: 0; } to { opacity: 1; } } @keyframes rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } } @keyframes float { 50% { transform: translateY(-7px) rotate(2deg); } } @keyframes sonar { 70%,100% { transform: scale(1.1); opacity: 0; } } @keyframes beacon { 70% { box-shadow: 0 0 0 9px rgba(250, 204, 21, 0); } 100% { box-shadow: 0 0 0 0 rgba(250, 204, 21, 0); } }
        @media (max-width: 720px) { .page { width: min(100% - 28px, 560px); padding: 28px 0; } .card { grid-template-columns: 1fr; gap: 28px; padding: 30px 24px; } .status { min-height: 220px; } .status svg { width: 58px; height: 58px; margin: 16px 0; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <div class="grid"></div>
    <main class="page">
        <div class="bar"><span class="brand" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3c3 3 5 5.7 5 9a5 5 0 1 1-10 0c0-2.6 1.6-5.5 5-9Z"/><path d="M10 16c0-1.5.8-2.8 2.2-4.1.8 1.1 1.8 2.1 1.8 3.5a2 2 0 0 1-4 0Z"/></svg></span> PyroSense Command System</div>
        <section class="card" aria-labelledby="error-title">
            <div>
                <p class="code" aria-label="Error {{ $status }}">{{ $status }}</p>
                <div class="eyebrow">{{ $page['label'] }}</div>
                <h1 id="error-title">{{ $page['title'] }}</h1>
                <p>{{ $page['message'] }}</p>
                <div class="actions">
                    <button class="button button-primary" type="button" onclick="window.location.reload()">{{ $page['action'] }}</button>
                    <a class="button button-secondary" href="{{ route('home') }}">System home</a>
                    <button class="button button-secondary" type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.assign('{{ route('auth.login') }}')">Go back</button>
                </div>
            </div>
            <aside class="status" aria-label="System status">
                <div class="status-top"><span>Command status</span><span class="live"><i></i> Protected</span></div>
                @if ($page['icon'] === 'route')
                    <svg viewBox="0 0 96 96" aria-hidden="true"><path d="M17 25h36c10 0 17 7 17 17s-7 17-17 17H39c-8 0-13 5-13 13s5 13 13 13h40"/><circle cx="17" cy="25" r="5"/><circle cx="79" cy="72" r="5"/></svg>
                @elseif ($page['icon'] === 'request')
                    <svg viewBox="0 0 96 96" aria-hidden="true"><path d="M48 15 80 73H16L48 15Z"/><path d="M48 37v17M48 64h.1"/></svg>
                @elseif ($page['icon'] === 'pulse')
                    <svg viewBox="0 0 96 96" aria-hidden="true"><path d="M12 50h18l8-19 13 39 10-20h23"/><circle cx="48" cy="50" r="35"/></svg>
                @else
                    <svg viewBox="0 0 96 96" aria-hidden="true"><path d="M48 14v43M48 71h.1"/><path d="M18 75 43 20a6 6 0 0 1 10 0l25 55a6 6 0 0 1-5 9H23a6 6 0 0 1-5-9Z"/></svg>
                @endif
                <div><strong>Operational details are secure.</strong><small>No server paths, logs, or internal diagnostics are displayed on this page.</small></div>
            </aside>
        </section>
        <p class="footer">PyroSense maintains a safe fallback while the command system recovers.</p>
    </main>
</body>
</html>
