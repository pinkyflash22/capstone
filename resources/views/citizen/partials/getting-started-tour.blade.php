<style>
    #citizenTour { border: 0; background: transparent; padding: 0; width: 100vw; max-width: none; height: 100dvh; max-height: none; overflow: hidden; color: #334155; }
    #citizenTour::backdrop { background: rgb(15 23 42 / 65%); backdrop-filter: blur(2px); }
    .citizen-tour-card { position: absolute; width: min(420px, calc(100% - 32px)); padding: 24px; background: #fff; border: 1px solid #fed7aa; border-radius: 22px; box-shadow: 0 24px 80px #0003; max-height: calc(100dvh - 32px); overflow-y: auto; z-index: 2; }
    .citizen-tour-kicker { color: #c2410c; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; }
    .citizen-tour-card h2 { font-size: 23px; font-weight: 800; letter-spacing: -.03em; color: #172033; margin: 12px 0; line-height: 1.2; }
    .citizen-tour-card p { font-size: 14px; line-height: 1.7; margin: 0; }
    .citizen-tour-progress { display: flex; gap: 5px; margin: 20px 0; }
    .citizen-tour-progress span { height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0; }
    .citizen-tour-progress span.is-done { background: #ea580c; }
    .citizen-tour-actions { display: flex; align-items: center; gap: 8px; }
    .citizen-tour-actions button { min-height: 44px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; background: white; }
    .citizen-tour-actions button:last-child { color: #fff; background: linear-gradient(120deg, #dc2626, #ea580c); border-color: transparent; }
    .citizen-tour-actions button:first-child { margin-right: auto; color: #64748b; border-color: transparent; }
    .citizen-tour-actions button:disabled { opacity: .4; cursor: default; }
    .citizen-tour-actions button:focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
    .citizen-tour-ring { position: fixed; pointer-events: none; border: 3px solid #fb923c; border-radius: 14px; box-shadow: 0 0 0 5px #fff5; z-index: 1; }
    #citizenTour[open] .citizen-tour-card { animation: citizen-tour-enter .22s ease-out; }
    @keyframes citizen-tour-enter { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { #citizenTour * { animation: none !important; transition: none !important; } }
</style>
<dialog id="citizenTour" aria-labelledby="citizenTourTitle" aria-describedby="citizenTourCopy">
    <div class="citizen-tour-ring" aria-hidden="true" hidden></div>
    <div class="citizen-tour-card">
        <div class="citizen-tour-kicker">Getting started · <span id="citizenTourStep"></span></div>
        <div aria-live="polite" aria-atomic="true">
            <h2 id="citizenTourTitle"></h2>
            <p id="citizenTourCopy"></p>
        </div>
        <div class="citizen-tour-progress" aria-hidden="true"></div>
        <div class="citizen-tour-actions">
            <button type="button" id="citizenTourSkip">Skip tour</button>
            <button type="button" id="citizenTourBack">Back</button>
            <button type="button" id="citizenTourNext">Start tour</button>
        </div>
    </div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('citizenTour');
    const replay = document.getElementById('citizenTourReplay');
    const userId = document.querySelector('meta[name="user-id"]')?.content;
    if (!dialog || !replay || !userId || typeof dialog.showModal !== 'function') return;

    const storageKey = `pyrosense:citizen-tour:v1:${userId}`;
    const steps = [
        { title: 'Welcome to PyroSense', copy: 'Take a quick tour of your citizen portal. Learn where to report an incident, check nearby alerts, and find emergency help. You can skip at any time and reopen this guide later.' },
        { title: 'Explore your local map', copy: 'Your overview brings the area map and nearby incident information together. Use the map search to find a place and select markers to see available details.', targets: ['#citizenMapSearch', '[data-citizen-dock-item="overview"]', '[data-section="overview"]'] },
        { title: 'Allow location when asked', copy: 'Allow location access so PyroSense can use your current position. A GPS reading within 150 meters can be used, including weak readings such as 137 meters. Check the location shown before sending a report.', targets: ['#citizenMapSearch', '[data-citizen-dock-item="overview"]'] },
        { title: 'Report an incident or hazard', copy: 'Use Report Incident on the overview, or the report button in the mobile navigation. Choose the appropriate report, review its location, and add the requested details before submitting. This tour does not send a report.', targets: ['[data-citizen-nav-trigger]', '#reportFire', '[data-citizen-risk-hazard-open]'] },
        { title: 'Stay informed nearby', copy: 'Open Alerts to review available incident notices for your area. Check Announcements for updates from your local fire station.', targets: ['#alertsFab', '[data-citizen-alerts-open]', '[data-section="alerts"]'] },
        { title: 'Find emergency help', copy: 'The SOS menu contains the available emergency calling options. Opening this guide never places a call. You are ready to explore—use Guide whenever you want to see these steps again.', targets: ['#citizenSosBtn'] }
    ];
    const card = dialog.querySelector('.citizen-tour-card');
    const ring = dialog.querySelector('.citizen-tour-ring');
    const next = document.getElementById('citizenTourNext');
    const back = document.getElementById('citizenTourBack');
    let index = 0;
    let previousFocus;
    let autoTimer;
    let started = false;

    const visibleTarget = () => {
        for (const selector of steps[index].targets || []) {
            for (const element of document.querySelectorAll(selector)) {
                const rect = element.getBoundingClientRect();
                if (rect.width && rect.height && rect.top >= 0 && rect.bottom <= innerHeight && rect.left >= 0 && rect.right <= innerWidth && getComputedStyle(element).visibility !== 'hidden') return rect;
            }
        }
        return null;
    };
    const position = () => {
        if (!dialog.open) return;
        const rect = visibleTarget();
        ring.hidden = !rect;
        if (rect) Object.assign(ring.style, { left: `${Math.max(4, rect.left - 5)}px`, top: `${Math.max(4, rect.top - 5)}px`, width: `${rect.width + 10}px`, height: `${rect.height + 10}px` });
        const height = card.offsetHeight;
        const width = card.offsetWidth;
        let top = (innerHeight - height) / 2;
        if (rect && rect.bottom + height + 32 < innerHeight) top = rect.bottom + 20;
        else if (rect && rect.top - height - 20 > 16) top = rect.top - height - 20;
        card.style.top = `${Math.max(16, Math.min(top, innerHeight - height - 16))}px`;
        card.style.left = `${Math.max(16, (innerWidth - width) / 2)}px`;
    };
    const render = () => {
        document.getElementById('citizenTourTitle').textContent = steps[index].title;
        document.getElementById('citizenTourCopy').textContent = steps[index].copy;
        document.getElementById('citizenTourStep').textContent = `${index + 1} of ${steps.length}`;
        dialog.querySelector('.citizen-tour-progress').innerHTML = steps.map((_, i) => `<span class="${i <= index ? 'is-done' : ''}"></span>`).join('');
        back.disabled = index === 0;
        next.textContent = index === 0 ? 'Start tour' : index === steps.length - 1 ? 'Finish' : 'Next';
        position();
    };
    const finish = () => {
        try { localStorage.setItem(storageKey, 'seen'); } catch (_) { /* Storage may be disabled. */ }
        dialog.close();
        if (previousFocus instanceof HTMLElement && previousFocus.isConnected) previousFocus.focus({ preventScroll: true });
    };
    const start = () => {
        clearTimeout(autoTimer);
        if (dialog.open) return;
        started = true;
        previousFocus = document.activeElement;
        index = 0;
        dialog.showModal();
        render();
        next.focus({ preventScroll: true });
    };
    replay.addEventListener('click', start);
    next.addEventListener('click', () => { if (index === steps.length - 1) finish(); else { index++; render(); } });
    back.addEventListener('click', () => { if (index > 0) { index--; render(); if (index === 0) next.focus(); } });
    document.getElementById('citizenTourSkip').addEventListener('click', finish);
    dialog.addEventListener('cancel', event => { event.preventDefault(); finish(); });
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, { passive: true, capture: true });

    // Do not interrupt a report, location prompt, or another in-page dialog.
    let interacted = false;
    const markInteraction = () => { interacted = true; };
    document.addEventListener('pointerdown', markInteraction, { once: true });
    document.addEventListener('keydown', markInteraction, { once: true });
    try {
        if (localStorage.getItem(storageKey)) return;
    } catch (_) { return; /* Manual replay remains available without storage. */ }
    autoTimer = setTimeout(() => {
        if (started || interacted || document.visibilityState !== 'visible') return;
        const otherDialogOpen = Array.from(document.querySelectorAll('dialog[open], [role="dialog"], [aria-modal="true"]')).some(element =>
            element !== dialog && element.getAttribute('aria-hidden') !== 'true'
            && element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden'
        );
        if (otherDialogOpen) return;
        start();
    }, 1800);
});
</script>
