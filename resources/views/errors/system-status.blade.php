@php
    /* Must render without Vite, authentication, or database access. */
    $errorPages = [
        400 => ['title' => 'Check your request', 'message' => 'Some details are missing or incorrect. Please go back and check them.'],
        401 => ['title' => 'Please sign in', 'message' => 'Sign in to open this page.', 'action' => 'Sign in', 'url' => url('/auth')],
        403 => ['title' => 'Access unavailable', 'message' => 'Your account cannot open this page.', 'action' => 'Go home', 'url' => url('/')],
        404 => ['title' => 'Page not found', 'message' => 'This page may have moved or the link is incorrect.', 'action' => 'Go home', 'url' => url('/')],
        405 => ['title' => 'Action unavailable', 'message' => 'Please go back and try another action.'],
        408 => ['title' => 'Connection timed out', 'message' => 'Check your connection and try again.'],
        419 => ['title' => 'Please sign in again', 'message' => 'Your session has expired.', 'action' => 'Sign in', 'url' => url('/auth')],
        422 => ['title' => 'Check your details', 'message' => 'Please go back and correct the missing or invalid information.'],
        429 => ['title' => 'Please wait a moment', 'message' => 'Try again in a little while.'],
        500 => ['title' => 'Something went wrong', 'message' => 'We could not load this page. Please try again.'],
        502 => ['title' => 'Unable to connect', 'message' => 'A service is temporarily unavailable. Please try again shortly.'],
        503 => ['title' => 'Temporarily unavailable', 'message' => 'Please try again in a few minutes.'],
        504 => ['title' => 'Taking too long', 'message' => 'We could not load this page in time. Please try again.'],
    ];
    $status = (int) ($status ?? 500);
    $page = $errorPages[$status] ?? ['title' => 'Unable to open this page', 'message' => 'Please go back or try again.'];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#c63d26"><meta name="robots" content="noindex,nofollow">
<title>{{ $status }} | PyroSense</title>
<style>
:root{color-scheme:light;--ink:#202938;--muted:#667085;--red:#c63d26}
*{box-sizing:border-box}body{margin:0;min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:24px 16px calc(24px + env(safe-area-inset-bottom,0px));background:radial-gradient(ellipse at 50% 30%,#fff0dc,transparent 65%),#f8fafc;color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.status-card{width:min(100%,440px);padding:30px 26px;text-align:center;border:1px solid #eee4d9;border-radius:26px;background:#ffffffed;box-shadow:0 18px 60px #7c2d120c;animation:arrive .4s ease-out}
.brand{font-size:12px;font-weight:800;letter-spacing:.08em;color:#9a3412;margin:0 0 24px}.status-icon{display:grid;place-items:center;width:62px;height:62px;margin:0 auto 18px;border-radius:19px;background:#fff1e8;color:var(--red)}.status-icon svg{width:32px;height:32px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.signal{animation:signal 2.4s ease-in-out infinite}
h1{font-size:clamp(24px,6vw,30px);line-height:1.2;letter-spacing:-.035em;margin:0 0 12px}p{font-size:14px;line-height:1.65;color:var(--muted);margin:0}.status-code{font-size:11px;letter-spacing:.12em;font-weight:700;color:#98a2b3;margin:0 0 10px}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:16px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:11px 14px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;color:#475569;font:600 13px system-ui;text-decoration:none;cursor:pointer;transition:transform .18s,box-shadow .18s}.button.primary{background:linear-gradient(120deg,#c63d26,#e76e21);border-color:transparent;color:#fff}.button:hover{transform:translateY(-2px);box-shadow:0 4px 12px #9a341212}.button:focus-visible,summary:focus-visible{outline:3px solid #f59e0b;outline-offset:3px}[hidden]{display:none!important}
.saved-pages{margin-top:12px}.saved-pages summary{cursor:pointer;padding:12px;color:#9a3412;font-size:13px;font-weight:700;border-radius:10px}.saved-pages nav{display:grid;gap:8px;margin-top:8px}.saved-pages p{font-size:12px;margin:8px 0}.footer-link{display:inline-block;margin-top:18px;font-size:12px;color:#64748b}
.fire-scene{position:relative;width:160px;height:148px;margin:4px auto 2px;isolation:isolate}.fire-glow{position:absolute;bottom:0;left:24px;width:112px;height:40px;background:#fb923c2b;filter:blur(12px);border-radius:50%;animation:glow 2s ease-in-out infinite}.log{position:absolute;bottom:13px;left:45px;width:74px;height:9px;border-radius:8px;background:#795548;transform:rotate(15deg)}.log.second{transform:rotate(-15deg)}
.flame{position:absolute;bottom:23px;width:44px;height:64px;left:58px;border-radius:70% 5% 65% 45%;background:linear-gradient(140deg,#ef4444,#f97316 60%,#fbbf24);transform:rotate(-35deg);transform-origin:center bottom;animation:burn 1.2s ease-in-out infinite alternate}.flame.left{left:44px;bottom:22px;width:31px;height:45px;animation-delay:-.5s}.flame.right{left:84px;width:28px;height:39px;animation-delay:-.8s}.flame.core{left:68px;width:25px;height:37px;background:linear-gradient(#fbbf24,#fef3c7);animation-duration:.85s}
.smoke{position:absolute;left:70px;bottom:77px;width:22px;height:22px;border-radius:50%;background:#94a3b8;filter:blur(6px);opacity:0;animation:smoke 3.6s ease-out infinite}.smoke.two{animation-delay:-1.2s;left:76px}.smoke.three{animation-delay:-2.4s;left:64px}
@keyframes burn{from{transform:rotate(-39deg) scale(.95,1)}to{transform:rotate(-29deg) scale(1.04,.88)}}@keyframes smoke{0%{opacity:0;transform:translate(0,0) scale(.6)}20%{opacity:.24}100%{opacity:0;transform:translate(16px,-57px) scale(2.2)}}@keyframes glow{50%{opacity:.55;transform:scale(.9)}}@keyframes signal{50%{opacity:.4;transform:translateY(-2px)}}@keyframes arrive{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
@media(max-width:360px){.status-card{padding:24px 18px}.actions{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}.smoke{opacity:.12}}
</style>
</head>
<body data-error-code="{{ $status }}">
<main class="status-card">
    <div class="brand">PyroSense</div>
    <div class="status-icon" aria-hidden="true"><svg class="signal" viewBox="0 0 24 24"><path d="M10.3 4.2 2.5 18a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/><path d="M12 9v5m0 3h.01"/></svg></div>
    <p class="status-code">Error {{ $status }}</p>
    <h1>{{ $page['title'] }}</h1>
    <p>{{ $page['message'] }}</p>
    <div class="fire-scene" aria-hidden="true"><span class="fire-glow"></span><span class="smoke"></span><span class="smoke two"></span><span class="smoke three"></span><span class="log"></span><span class="log second"></span><span class="flame left"></span><span class="flame right"></span><span class="flame"></span><span class="flame core"></span></div>
    <div class="actions">
        <button class="button" type="button" onclick="history.length > 1 ? history.back() : location.assign('/')">Go back</button>
        @if(isset($page['url']))
            <a class="button primary" href="{{ $page['url'] }}">{{ $page['action'] }}</a>
        @elseif(in_array($status, [400, 405, 422], true))
            <a class="button primary" href="{{ url('/') }}">Go home</a>
        @else
            <button class="button primary" type="button" onclick="location.assign(location.href)">Try again</button>
        @endif
    </div>
</main>
</body>
</html>
