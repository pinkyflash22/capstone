@php
    /* Self-contained by design: this must render when Vite, auth, or the database is unavailable. */
    $errorPages = [
        400 => ['label' => 'Bad request', 'title' => 'We could not understand that request.', 'message' => 'Some information was missing or formatted incorrectly. Review what you entered, then try once more.', 'icon' => 'document', 'tone' => 'amber', 'action' => 'Try again'],
        401 => ['label' => 'Authentication required', 'title' => 'Please sign in to continue.', 'message' => 'This area is protected. Sign in with an authorized PyroSense account to access it.', 'icon' => 'key', 'tone' => 'blue', 'action' => 'Go to sign in', 'actionUrl' => url('/auth')],
        403 => ['label' => 'Access denied', 'title' => 'You do not have access to this area.', 'message' => 'Your account is signed in, but it does not have permission to perform this action or view this resource.', 'icon' => 'shield', 'tone' => 'red', 'action' => 'Return home', 'actionUrl' => url('/')],
        404 => ['label' => 'Page not found', 'title' => 'This route is off the map.', 'message' => 'The page may have moved, the address may be incomplete, or the link may no longer be active.', 'icon' => 'search', 'tone' => 'amber', 'action' => 'Return home', 'actionUrl' => url('/')],
        405 => ['label' => 'Method not allowed', 'title' => 'That action is not available here.', 'message' => 'The page exists, but it cannot accept the type of request that was sent. Return and use an available action.', 'icon' => 'blocked', 'tone' => 'red', 'action' => 'Return home', 'actionUrl' => url('/')],
        408 => ['label' => 'Request timeout', 'title' => 'The request took too long.', 'message' => 'The connection timed out before the operation could finish. Check your network and try again.', 'icon' => 'clock', 'tone' => 'blue', 'action' => 'Try again'],
        419 => ['label' => 'Session expired', 'title' => 'Your secure session has ended.', 'message' => 'You were signed out after a period of inactivity to keep your account safe. Sign in again to continue.', 'icon' => 'hourglass', 'tone' => 'amber', 'action' => 'Sign in again', 'actionUrl' => url('/auth')],
        422 => ['label' => 'Validation error', 'title' => 'A few details need your attention.', 'message' => 'The submitted information could not be accepted. Review the highlighted fields and correct any missing or invalid values.', 'icon' => 'checklist', 'tone' => 'amber', 'action' => 'Review details'],
        429 => ['label' => 'Too many requests', 'title' => 'Let us slow things down for a moment.', 'message' => 'Too many requests arrived in a short time. Please wait briefly before trying again.', 'icon' => 'gauge', 'tone' => 'blue', 'action' => 'Try again'],
        500 => ['label' => 'Internal server error', 'title' => 'Something went wrong on our side.', 'message' => 'PyroSense could not complete this operation. The error was contained, and no technical details are exposed here.', 'icon' => 'server', 'tone' => 'red', 'action' => 'Try again'],
        502 => ['label' => 'Bad gateway', 'title' => 'A connected service did not respond correctly.', 'message' => 'PyroSense received an invalid response from an upstream service. This is usually temporary.', 'icon' => 'connection', 'tone' => 'red', 'action' => 'Try again'],
        503 => ['label' => 'Service unavailable', 'title' => 'The command service is taking a short break.', 'message' => 'PyroSense is under maintenance or temporarily busy. Live operations will return as soon as service is restored.', 'icon' => 'maintenance', 'tone' => 'amber', 'action' => 'Check again'],
        504 => ['label' => 'Gateway timeout', 'title' => 'A connected service took too long.', 'message' => 'PyroSense is available, but another service did not answer in time. Please try the request again shortly.', 'icon' => 'network-clock', 'tone' => 'blue', 'action' => 'Try again'],
    ];
    $status = (int) ($status ?? 500);
    $page = $errorPages[$status] ?? ['label' => 'Unexpected response', 'title' => 'We could not complete that request.', 'message' => 'An unexpected response interrupted this operation. Return to a safe page or try again.', 'icon' => $status >= 500 ? 'server' : 'document', 'tone' => $status >= 500 ? 'red' : 'amber', 'action' => 'Try again'];
    $primaryUrl = $page['actionUrl'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#8f1d17">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} — {{ $page['label'] }} | PyroSense</title>
    <style>
        :root { color-scheme: light; --red:#b3261e; --red-dark:#761713; --amber:#f4a71d; --ink:#19242b; --muted:#63717a; --line:rgba(91,41,31,.12); --paper:rgba(255,255,255,.88); --accent:#b3261e; }
        * { box-sizing:border-box; }
        html { min-width:320px; background:#f8f5ef; }
        body { min-height:100vh; margin:0; overflow-x:hidden; color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:radial-gradient(circle at 8% 12%,rgba(244,167,29,.18),transparent 24rem),radial-gradient(circle at 94% 86%,rgba(179,38,30,.14),transparent 30rem),#f8f5ef; }
        body[data-tone="amber"] { --accent:#d78208; }
        body[data-tone="blue"] { --accent:#26718c; }
        .backdrop { position:fixed; inset:0; overflow:hidden; pointer-events:none; }
        .backdrop::before { content:""; position:absolute; inset:0; opacity:.42; background-image:linear-gradient(rgba(118,23,19,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(118,23,19,.05) 1px,transparent 1px); background-size:48px 48px; mask-image:linear-gradient(to bottom,#000,transparent 78%); }
        .ember { position:absolute; width:8px; height:8px; border-radius:70% 30% 70% 30%; background:var(--amber); opacity:.35; animation:ember-rise 7s linear infinite; }
        .ember:nth-child(1){left:6%;bottom:-2%;animation-delay:-2s}.ember:nth-child(2){left:27%;bottom:-6%;width:5px;height:5px;animation-delay:-5s}.ember:nth-child(3){right:18%;bottom:-4%;animation-delay:-1s}.ember:nth-child(4){right:5%;bottom:-8%;width:5px;height:5px;animation-delay:-4s}
        .shell { position:relative; width:min(1120px,calc(100% - 40px)); min-height:100vh; margin:auto; padding:38px 0; display:grid; align-content:center; }
        .brand { display:inline-flex; width:max-content; align-items:center; gap:11px; margin:0 0 18px 4px; color:#76211b; font-size:12px; font-weight:850; letter-spacing:.13em; text-transform:uppercase; animation:fade-in .5s ease both; }
        .brand-mark { width:42px; height:42px; display:grid; place-items:center; color:#fff; background:linear-gradient(145deg,#d43c2d,var(--red-dark)); border-radius:13px; box-shadow:0 12px 24px rgba(118,23,19,.2); }
        .brand-mark svg { width:24px;height:24px; }
        .panel { display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,.82fr); min-height:590px; overflow:hidden; background:var(--paper); border:1px solid var(--line); border-radius:34px; box-shadow:0 30px 90px rgba(78,34,23,.13); backdrop-filter:blur(18px); animation:panel-in .65s cubic-bezier(.2,.8,.2,1) both; }
        .copy { padding:clamp(38px,6vw,76px); display:flex; flex-direction:column; justify-content:center; }
        .status-line { display:flex; align-items:center; gap:13px; color:var(--accent); font-size:12px; font-weight:850; letter-spacing:.14em; text-transform:uppercase; }
        .status-line::before { content:""; width:34px; height:3px; border-radius:4px; background:currentColor; }
        .code { margin:16px 0 8px; color:#1c2a31; font-size:clamp(82px,12vw,148px); font-weight:900; line-height:.82; letter-spacing:-.085em; }
        h1 { max-width:600px; margin:24px 0 14px; font:800 clamp(31px,4.1vw,52px)/1.05 Georgia,"Times New Roman",serif; letter-spacing:-.035em; }
        .message { max-width:610px; margin:0; color:var(--muted); font-size:16px; line-height:1.75; }
        .actions { display:flex; flex-wrap:wrap; gap:11px; margin-top:30px; }
        .button { min-height:49px; border-radius:14px; padding:0 19px; display:inline-flex; align-items:center; justify-content:center; gap:9px; border:0; font:800 14px/1 inherit; text-decoration:none; cursor:pointer; transition:transform .2s ease,box-shadow .2s ease,background .2s ease; }
        .button:hover { transform:translateY(-2px); }.button:focus-visible{outline:3px solid rgba(38,113,140,.35);outline-offset:3px}
        .primary { color:#fff; background:linear-gradient(135deg,#c93c2e,var(--red-dark)); box-shadow:0 14px 26px rgba(118,23,19,.22); }
        .secondary { color:#71231d; background:#fff8f4; border:1px solid rgba(118,23,19,.14); }
        .button svg { width:17px;height:17px; }
        .safety-note { display:flex; align-items:flex-start; gap:9px; max-width:560px; margin-top:25px; color:#7d716a; font-size:12px; line-height:1.55; }
        .safety-note svg { flex:0 0 auto; width:16px; height:16px; margin-top:1px; color:#3f7b59; }
        .visual { position:relative; isolation:isolate; min-height:500px; overflow:hidden; display:grid; place-items:center; color:#fff; background:linear-gradient(150deg,#b82d23 0%,#771712 76%); }
        body[data-tone="amber"] .visual{background:linear-gradient(150deg,#efa91f 0%,#b85b0a 76%)}body[data-tone="blue"] .visual{background:linear-gradient(150deg,#3588a2 0%,#174b62 76%)}
        .visual::before { content:""; position:absolute; inset:0; opacity:.18; background-image:radial-gradient(circle,#fff 1.2px,transparent 1.4px); background-size:25px 25px; }
        .orbital { position:relative; width:min(78%,350px); aspect-ratio:1; display:grid; place-items:center; }
        .orbit,.orbit::before,.orbit::after { position:absolute; inset:0; border:1px solid rgba(255,255,255,.28); border-radius:50%; }
        .orbit{animation:spin 15s linear infinite}.orbit::before{content:"";inset:10%;border-style:dashed;animation:spin-reverse 11s linear infinite}.orbit::after{content:"";inset:22%;border-color:rgba(255,255,255,.12);box-shadow:0 0 0 28px rgba(255,255,255,.035)}
        .satellite { position:absolute; z-index:2; width:14px; height:14px; border:4px solid currentColor; border-radius:50%; background:var(--amber); box-shadow:0 0 0 7px rgba(255,255,255,.1); }
        .satellite.one{top:8%;right:18%;animation:pulse 2s ease-in-out infinite}.satellite.two{bottom:13%;left:13%;width:10px;height:10px;animation:pulse 2s .7s ease-in-out infinite}
        .icon-disc { position:relative; z-index:3; width:176px; height:176px; display:grid; place-items:center; border:1px solid rgba(255,255,255,.34); border-radius:46px; background:rgba(255,255,255,.14); box-shadow:inset 0 1px rgba(255,255,255,.25),0 28px 50px rgba(53,13,10,.22); backdrop-filter:blur(8px); animation:hover 3.4s ease-in-out infinite; }
        .icon-disc>svg { width:100px;height:100px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round; }
        .gear{transform-box:fill-box;transform-origin:center;animation:spin 6s linear infinite}.sweep{transform-box:fill-box;transform-origin:bottom center;animation:sweep 2.5s ease-in-out infinite}.blink{animation:blink 1.6s ease-in-out infinite}.dash{stroke-dasharray:8 8;animation:dash 3s linear infinite}
        .visual-label { position:absolute; z-index:4; left:28px; right:28px; bottom:27px; display:flex; justify-content:space-between; gap:14px; color:rgba(255,255,255,.74); font-size:10px; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
        .visual-label span:last-child{display:flex;align-items:center;gap:7px}.visual-label i{width:7px;height:7px;border-radius:50%;background:#ffe279;box-shadow:0 0 0 0 rgba(255,226,121,.7);animation:beacon 1.8s infinite}
        .footer { margin:18px 4px 0; color:#786b64; font-size:12px; animation:fade-in .5s .2s ease both; }
        @keyframes panel-in{from{opacity:0;transform:translateY(18px) scale(.99)}to{opacity:1;transform:none}}@keyframes fade-in{from{opacity:0}to{opacity:1}}@keyframes hover{50%{transform:translateY(-9px) rotate(1.2deg)}}@keyframes spin{to{transform:rotate(360deg)}}@keyframes spin-reverse{to{transform:rotate(-360deg)}}@keyframes pulse{50%{transform:scale(1.35);opacity:.72}}@keyframes sweep{50%{transform:rotate(75deg)}}@keyframes blink{50%{opacity:.28}}@keyframes dash{to{stroke-dashoffset:-32}}@keyframes beacon{70%{box-shadow:0 0 0 10px rgba(255,226,121,0)}}@keyframes ember-rise{from{transform:translateY(0) rotate(0);opacity:0}15%{opacity:.4}to{transform:translateY(-105vh) rotate(240deg);opacity:0}}
        @media(max-width:820px){.shell{width:min(620px,calc(100% - 28px));padding:24px 0}.panel{grid-template-columns:1fr;border-radius:26px}.copy{padding:34px 26px 30px}.visual{min-height:310px;order:-1}.orbital{width:250px}.icon-disc{width:132px;height:132px;border-radius:36px}.icon-disc>svg{width:76px;height:76px}.code{font-size:84px}h1{margin-top:18px}}
        @media(max-width:430px){.shell{width:min(100% - 20px,410px)}.brand{margin-left:2px}.visual{min-height:270px}.orbital{width:215px}.actions{flex-direction:column}.button{width:100%}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;scroll-behavior:auto!important;transition-duration:.01ms!important}}
    </style>
</head>
<body data-tone="{{ $page['tone'] }}" data-error-code="{{ $status }}">
    <div class="backdrop" aria-hidden="true"><i class="ember"></i><i class="ember"></i><i class="ember"></i><i class="ember"></i></div>
    <main class="shell">
        <div class="brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.8c3.4 3.2 5.6 6.1 5.6 9.6a5.6 5.6 0 1 1-11.2 0c0-2.9 1.8-6 5.6-9.6Z"/><path d="M9.8 15.7c0-1.7.9-3.1 2.5-4.6.9 1.2 2 2.4 2 3.9a2.3 2.3 0 0 1-4.5.7Z"/></svg></span>PyroSense Command System</div>
        <section class="panel" aria-labelledby="error-title">
            <div class="copy">
                <div class="status-line">{{ $page['label'] }}</div>
                <p class="code" aria-label="HTTP status {{ $status }}">{{ $status }}</p>
                <h1 id="error-title">{{ $page['title'] }}</h1>
                <p class="message">{{ $page['message'] }}</p>
                <div class="actions">
                    @if($primaryUrl)
                        <a class="button primary" href="{{ $primaryUrl }}">{{ $page['action'] }}<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></a>
                    @else
                        <button class="button primary" type="button" onclick="window.location.reload()">{{ $page['action'] }}<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg></button>
                    @endif
                    @unless($status === 404)<a class="button secondary" href="{{ url('/') }}">System home</a>@endunless
                    <button class="button secondary" type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.assign('/')">Go back</button>
                </div>
                <div class="safety-note"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 8 3v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg><span>Internal paths, diagnostics, and sensitive system details remain protected.</span></div>
            </div>
            <aside class="visual" aria-label="Animated illustration for {{ $page['label'] }}">
                <div class="orbital" aria-hidden="true"><span class="orbit"></span><span class="satellite one"></span><span class="satellite two"></span><div class="icon-disc">
                    @switch($page['icon'])
                        @case('key')<svg viewBox="0 0 100 100"><circle cx="37" cy="39" r="17"/><path d="m49 51 30 30m-10-10 8-8m-17-1 8-8"/><circle class="blink" cx="37" cy="39" r="5"/></svg>@break
                        @case('shield')<svg viewBox="0 0 100 100"><path d="M50 10 82 22v22c0 21-13 36-32 45-19-9-32-24-32-45V22L50 10Z"/><rect x="36" y="44" width="28" height="24" rx="5"/><path d="M42 44v-7a8 8 0 0 1 16 0v7"/><path class="blink" d="M50 53v7"/></svg>@break
                        @case('search')<svg viewBox="0 0 100 100"><circle cx="42" cy="42" r="25"/><path d="m60 60 22 22"/><path class="dash" d="M28 42c0-8 6-14 14-14"/><path d="M72 19h10v10M18 72H8V62"/></svg>@break
                        @case('blocked')<svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="36"/><path d="m25 25 50 50"/><path d="M37 60V39h18m0 0-7-7m7 7-7 7"/></svg>@break
                        @case('clock')<svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="37"/><path d="M50 28v23l16 9"/><path class="sweep" d="M50 50 35 35"/><path d="M50 8v6M92 50h-6M50 92v-6M8 50h6"/></svg>@break
                        @case('hourglass')<svg viewBox="0 0 100 100"><path d="M26 14h48M26 86h48M31 14c0 20 5 27 19 36-14 9-19 16-19 36m38-72c0 20-5 27-19 36 14 9 19 16 19 36"/><path class="blink" d="m39 70 11-8 11 8"/></svg>@break
                        @case('checklist')<svg viewBox="0 0 100 100"><rect x="22" y="14" width="56" height="72" rx="8"/><path d="m34 35 5 5 9-10M34 55h4m8 0h19M34 69h4m8 0h19"/><path class="blink" d="M65 25v15M65 47h.1"/></svg>@break
                        @case('gauge')<svg viewBox="0 0 100 100"><path d="M17 70a37 37 0 1 1 66 0"/><path d="M27 70h46M27 53l-8-3M39 39l-5-7M58 37l3-8M73 49l8-4"/><path class="sweep" d="m50 68 23-26"/><circle cx="50" cy="68" r="5"/></svg>@break
                        @case('connection')<svg viewBox="0 0 100 100"><path d="M38 62 27 73a13 13 0 0 1-18-18l17-17a13 13 0 0 1 18 0M62 38l11-11a13 13 0 0 1 18 18L74 62a13 13 0 0 1-18 0"/><path class="blink" d="m37 37 26 26M55 28l5-11M72 45l11-5M45 72l-5 11M28 55l-11 5"/></svg>@break
                        @case('maintenance')<svg viewBox="0 0 100 100"><path d="M59 22a20 20 0 0 0-24 25L12 70a7 7 0 0 0 10 10l23-23a20 20 0 0 0 25-24L57 46l-10-3-3-10 15-11Z"/><path class="gear" d="M69 64v8m0 12v7m-14-13h8m12 0h9"/></svg>@break
                        @case('network-clock')<svg viewBox="0 0 100 100"><circle cx="61" cy="61" r="27"/><path d="M61 44v18l12 7M16 30h39M16 45h25M16 60h15"/><path class="dash" d="M21 19h48"/></svg>@break
                        @case('server')<svg viewBox="0 0 100 100"><rect x="18" y="17" width="64" height="25" rx="5"/><rect x="18" y="58" width="64" height="25" rx="5"/><circle class="blink" cx="30" cy="29.5" r="3"/><circle class="blink" cx="30" cy="70.5" r="3"/><path d="M42 30h27M42 71h27M50 42v16"/><path class="gear" d="m79 47 6 3-2 7-7-1-3 6-7-3 2-7-6-3 2-7 7 1 3-6 7 3-2 7Z"/></svg>@break
                        @default<svg viewBox="0 0 100 100"><path d="M26 12h34l16 16v60H26V12Z"/><path d="M60 12v17h16M39 44h24M39 57h17"/><path class="blink" d="M50 69v8M50 83h.1"/></svg>
                    @endswitch
                </div></div>
                <div class="visual-label"><span>Response code {{ $status }}</span><span><i></i>Safe fallback active</span></div>
            </aside>
        </section>
        <p class="footer">PyroSense keeps a secure fallback available while normal service resumes.</p>
    </main>
</body>
</html>
