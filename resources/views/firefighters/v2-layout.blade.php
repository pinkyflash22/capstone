<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PyroSense Firefighter V2')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css'])
    @include('partials.pwa-head')
    <style>
        :root {
            --ff2-bg: linear-gradient(180deg, #fff7ef 0%, #fffaf3 46%, #f8fbff 100%);
            --ff2-card: rgba(255, 255, 255, 0.94);
            --ff2-line: rgba(226, 232, 240, 0.95);
            --ff2-ink: #172033;
            --ff2-copy: #5f718b;
            --ff2-muted: #8ea0b9;
            --ff2-warm: #c94b2a;
            --ff2-amber: #db9218;
            --ff2-teal: #124e5d;
            --ff2-success: #1d9b60;
            --ff2-radius-xl: 30px;
            --ff2-radius-lg: 24px;
            --ff2-radius-md: 18px;
            --ff2-shadow: 0 24px 52px rgba(15, 23, 42, 0.08);
        }
        body.ff2-body { margin: 0; min-height: 100vh; background: var(--ff2-bg); color: var(--ff2-ink); font-family: "Inter","Segoe UI",sans-serif; }
        .ff2-shell { display: grid; grid-template-columns: 320px minmax(0, 1fr); min-height: 100vh; }
        .ff2-sidebar { position: sticky; top: 0; height: 100vh; overflow-y: auto; border-right: 1px solid rgba(226,232,240,.72); background: radial-gradient(circle at top right, rgba(219,146,24,.24) 0, rgba(219,146,24,.24) 90px, transparent 90px), radial-gradient(circle at left 88px, rgba(201,75,42,.18) 0, rgba(201,75,42,.18) 110px, transparent 110px), linear-gradient(180deg, #ffffff 0%, #fff8f2 28%, #ffffff 100%); padding: 28px 22px 24px; }
        .ff2-brand { display: flex; align-items: center; gap: 16px; padding: 18px; border-radius: 28px; background: linear-gradient(135deg, #ffffff 0%, #fff5ed 100%); border: 1px solid rgba(201,75,42,.12); box-shadow: 0 14px 32px rgba(201,75,42,.08); }
        .ff2-brand-mark { width: 58px; height: 58px; border-radius: 999px; display: grid; place-items: center; background: linear-gradient(135deg, #c94b2a 0%, #db9218 100%); color: #fff; font-size: 1.45rem; box-shadow: 0 18px 28px rgba(201,75,42,.24); }
        .ff2-brand-copy strong { display: block; font-size: 1.28rem; line-height: 1.1; letter-spacing: -.02em; }
        .ff2-brand-copy span { display: block; margin-top: 4px; color: var(--ff2-copy); font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; }
        .ff2-nav-group { margin-top: 28px; }
        .ff2-nav-label { margin: 0 0 12px; padding: 0 12px; color: #9aa9bf; font-size: .72rem; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .ff2-nav { display: grid; gap: 8px; }
        .ff2-nav a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 18px; color: #4e607b; font-weight: 700; text-decoration: none; transition: all .2s ease; }
        .ff2-nav a:hover, .ff2-nav a.is-active { color: var(--ff2-warm); background: linear-gradient(135deg, #fff5ec 0%, #fff9ef 100%); box-shadow: inset 0 0 0 1px rgba(201,75,42,.08); }
        .ff2-nav a i { width: 18px; text-align: center; }
        .ff2-sidebar-footer { margin-top: 28px; padding: 18px; border-radius: 24px; background: linear-gradient(135deg, rgba(18,78,93,.96) 0%, rgba(20,94,110,.94) 100%); color: #e5f3f7; box-shadow: 0 18px 34px rgba(18,78,93,.2); }
        .ff2-sidebar-footer strong { display: block; font-size: 1rem; }
        .ff2-sidebar-footer span { display: block; margin-top: 4px; color: rgba(229,243,247,.78); font-size: .86rem; }
        .ff2-main { min-width: 0; padding: 28px; }
        .ff2-topbar { display: flex; justify-content: space-between; gap: 18px; align-items: center; padding: 20px 22px; border-radius: 28px; background: rgba(255,255,255,.84); border: 1px solid rgba(226,232,240,.94); box-shadow: 0 12px 30px rgba(15,23,42,.05); backdrop-filter: blur(14px); }
        .ff2-topbar h1 { margin: 0; font-size: clamp(1.5rem, 2vw, 2rem); line-height: 1.05; letter-spacing: -.03em; }
        .ff2-topbar p { margin: 6px 0 0; color: var(--ff2-copy); font-size: .92rem; }
        .ff2-status { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; background: #ecfdf3; color: #1e8a57; border: 1px solid rgba(34,197,94,.16); font-size: .76rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .ff2-status::before { content: ""; width: 10px; height: 10px; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 5px rgba(34,197,94,.12); }
        .ff2-content { display: grid; gap: 22px; margin-top: 22px; }
        .ff2-page-header { position: relative; overflow: hidden; padding: 30px; border-radius: var(--ff2-radius-xl); background: radial-gradient(circle at top right, rgba(219,146,24,.14) 0, rgba(219,146,24,.14) 140px, transparent 140px), linear-gradient(135deg, #fff5ea 0%, #fffaf4 50%, #ffffff 100%); border: 1px solid rgba(201,75,42,.12); box-shadow: var(--ff2-shadow); }
        .ff2-page-header__eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; background: rgba(255,255,255,.82); color: var(--ff2-warm); border: 1px solid rgba(201,75,42,.1); font-size: .78rem; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .ff2-page-header h2 { margin: 18px 0 0; font-size: clamp(2rem, 4vw, 3.8rem); line-height: .95; letter-spacing: -.05em; }
        .ff2-page-header p { max-width: 780px; margin: 14px 0 0; color: var(--ff2-copy); font-size: 1rem; line-height: 1.75; }
        .ff2-card { background: var(--ff2-card); border: 1px solid var(--ff2-line); border-radius: var(--ff2-radius-lg); box-shadow: 0 18px 44px rgba(15,23,42,.06); }
        .ff2-card__body { padding: 22px; }
        .ff2-card__title { margin: 0; font-size: 1.08rem; font-weight: 900; color: var(--ff2-ink); }
        .ff2-card__subtitle { margin: 8px 0 0; color: var(--ff2-copy); font-size: .92rem; line-height: 1.65; }
        .ff2-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .ff2-stat { position: relative; overflow: hidden; padding: 22px; border-radius: var(--ff2-radius-lg); background: linear-gradient(145deg, #ffffff 0%, #fffaf3 100%); border: 1px solid rgba(226,232,240,.95); box-shadow: 0 14px 30px rgba(15,23,42,.05); }
        .ff2-stat::after { content: ""; position: absolute; right: -30px; bottom: -30px; width: 110px; height: 110px; border-radius: 999px; background: linear-gradient(135deg, rgba(201,75,42,.12), rgba(219,146,24,.08)); }
        .ff2-stat__label { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: var(--ff2-copy); font-size: .76rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .ff2-stat__value { margin-top: 18px; font-size: 2.35rem; line-height: 1; font-weight: 900; letter-spacing: -.05em; }
        .ff2-stat__meta { position: relative; z-index: 1; margin-top: 10px; color: var(--ff2-muted); font-size: .86rem; line-height: 1.55; }
        .ff2-grid-2 { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .8fr); gap: 22px; }
        .ff2-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 999px; background: #fff7ef; border: 1px solid rgba(201,75,42,.12); color: var(--ff2-warm); font-size: .76rem; font-weight: 900; }
        .ff2-button { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 46px; padding: 0 18px; border-radius: 15px; border: 0; text-decoration: none; cursor: pointer; font-size: .9rem; font-weight: 800; transition: transform .2s ease, box-shadow .2s ease; }
        .ff2-button--primary { background: linear-gradient(135deg, var(--ff2-warm) 0%, var(--ff2-amber) 100%); color: #fff; box-shadow: 0 14px 28px rgba(201,75,42,.2); }
        .ff2-button--ghost { background: #fff; color: var(--ff2-ink); border: 1px solid rgba(226,232,240,.95); }
        .ff2-button:hover { transform: translateY(-1px); }
        .ff2-list { display: grid; gap: 14px; }
        .ff2-list-item { display: flex; justify-content: space-between; gap: 16px; align-items: start; padding: 18px; border-radius: 18px; border: 1px solid rgba(226,232,240,.92); background: linear-gradient(135deg, #ffffff 0%, #fffaf4 100%); }
        .ff2-list-item h3 { margin: 0; font-size: 1rem; font-weight: 800; }
        .ff2-list-item p { margin: 8px 0 0; color: var(--ff2-copy); font-size: .9rem; line-height: 1.65; }
        .ff2-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; color: var(--ff2-muted); font-size: .8rem; font-weight: 700; }
        .ff2-empty { padding: 36px 20px; text-align: center; color: var(--ff2-muted); }
        .ff2-table-wrap { overflow-x: auto; }
        .ff2-table { width: 100%; border-collapse: collapse; min-width: 640px; }
        .ff2-table th, .ff2-table td { padding: 14px 16px; border-bottom: 1px solid rgba(226,232,240,.9); text-align: left; font-size: .9rem; }
        .ff2-table th { color: var(--ff2-copy); font-size: .76rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        @media (max-width: 1200px) { .ff2-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ff2-grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 1024px) { .ff2-shell { grid-template-columns: 1fr; } .ff2-sidebar { position: relative; height: auto; } }
        @media (max-width: 720px) { .ff2-main { padding: 16px; } .ff2-sidebar { padding: 18px 16px; } .ff2-topbar { padding: 18px; flex-direction: column; align-items: start; } .ff2-page-header { padding: 24px 20px; } .ff2-page-header h2 { font-size: 2.25rem; } .ff2-stats { grid-template-columns: 1fr; } .ff2-list-item { flex-direction: column; } }
    </style>
    @yield('head')
</head>
<body class="ff2-body">
    <div class="ff2-shell">
        @include('firefighters.partials.v2-sidebar', ['active' => $active ?? null])
        <main class="ff2-main">
            @include('firefighters.partials.v2-topbar', ['title' => $pageTitle ?? 'Responder Command V2', 'subtitle' => $pageSubtitle ?? 'Firefighter pages rebuilt in a safer parallel module'])
            <div class="ff2-content">
                @yield('content')
            </div>
        </main>
    </div>
    @yield('scripts')
</body>
</html>
