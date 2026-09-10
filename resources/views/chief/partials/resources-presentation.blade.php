{{-- Scoped presentation enhancements; resource data and action handlers remain unchanged. --}}
<style>
    .management-container.chief-theme-shell { gap: 16px; }
    .management-container .resource-order-hero { background: transparent; border: 0; box-shadow: none; padding: 8px 0; border-radius: 0; gap: 24px; }
    .management-container .resource-order-hero::before, .management-container .resource-order-hero::after { display: none; }
    .management-container .chief-theme-hero__eyebrow { background: transparent; padding: 0; border: 0; color: #b91c1c; font-size: 10px; letter-spacing: .14em; }
    .management-container .chief-theme-hero__title { color: #172033; font-size: clamp(24px, 2.5vw, 32px); letter-spacing: -.04em; margin-top: 8px; }
    .management-container .chief-theme-hero__copy { font-size: 13px; line-height: 1.65; color: #64748b; max-width: 520px; }
    .management-container .chief-theme-badges { gap: 8px; margin-top: 12px; }
    .management-container .chief-theme-badge { font-size: 10px; padding: 5px 8px; color: #64748b; background: #fff; border-color: #e2e8f0; }
    .management-container .chief-theme-summary-grid { gap: 10px; }
    .management-container .chief-theme-summary-card { background: #fff; border: 1px solid #e2e8f0; border-top: 2px solid #ea580c; border-radius: 12px; padding: 14px; box-shadow: none; }
    .management-container .chief-theme-summary-label { color: #64748b; font-size: 10px; letter-spacing: .07em; }
    .management-container .chief-theme-summary-value { color: #172033; font-size: 27px; font-weight: 800; }
    .management-container .chief-theme-summary-copy { color: #64748b; font-size: 11px; line-height: 1.5; }
    .management-container .resource-priority-map { gap: 8px; }
    .management-container .resource-priority-map a { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 12px; min-width: 0; box-shadow: none; }
    .management-container .resource-priority-map strong { font-size: 12px; color: #334155; }
    .management-container .resource-priority-map small { font-size: 10px; line-height: 1.5; }
    .management-container .resource-tier-heading { margin: 8px 0 0; padding: 8px 0; background: transparent; border: 0; }
    .management-container .resource-tier-heading h2 { color: #172033; font-size: 18px; letter-spacing: -.025em; }
    .management-container .resource-tier-heading p { color: #64748b; font-size: 12px; }
    .management-container :is(.station-grid, .boundary-control-card, .duty-roster) { min-width: 0; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; box-shadow: 0 3px 14px #0f172a04; scroll-margin-top: 100px; }
    .management-container :is(.section-header, .boundary-control-header) { padding: 16px 18px; background: #fff; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px; }
    .management-container :is(.section-header, .boundary-control-header) h3 { font-size: 15px; color: #172033; letter-spacing: -.02em; }
    .management-container .boundary-control-header p { color: #64748b; font-size: 12px; line-height: 1.6; }
    .management-container .stat-badge { padding: 5px 9px; font-size: 11px; color: #9a3412; background: #fff7ed; border-radius: 7px; }
    .management-container .search-filter-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: none; gap: 10px; flex-wrap: wrap; }
    .management-container .search-box { min-width: 0; flex: 1 1 240px; }
    .management-container :is(.search-box input, .filter-select, .filter-select-sm) { min-width: 0; max-width: 100%; border-radius: 9px; font-size: 12px; min-height: 40px; }
    .management-container :is(.filter-actions, .filter-controls) { flex-wrap: wrap; gap: 8px; min-width: 0; }
    .management-container :is(.btn-filter, .btn-add) { border-radius: 9px; min-height: 40px; font-size: 12px; }
    .management-container :is(.table-responsive, .boundary-table-wrap) { min-width: 0; max-width: 100%; overflow-x: auto; overscroll-behavior-x: contain; }
    .management-container :is(.station-table, .boundary-table) th { font-size: 10px; text-transform: uppercase; letter-spacing: .065em; color: #64748b; background: #f8fafc; }
    .management-container :is(.station-table, .boundary-table) td { font-size: 12px; }
    .management-container .resource-pagination { min-width: 0; max-width: 100%; overflow-x: auto; }
    .management-container :is(.resource-tier, .chief-theme-summary-card, .resource-priority-map a) { transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .management-container :is(.resource-tier, .chief-theme-summary-card):focus-within { border-color: #fb923c; box-shadow: 0 0 0 3px #fb923c20; }
    .management-container :is(button, a, input, select):focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
    .resource-collapse-toggle { display: inline-flex; align-items: center; gap: 8px; margin-left: auto; min-height: 40px; padding: 8px 12px; color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .resource-collapse-toggle i { transition: transform .2s ease; }
    .resource-collapse-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .resource-collapse-body[hidden] { display: none !important; }
    .management-container .resource-is-collapsed > :is(.section-header, .boundary-control-header) { border-bottom: 0; margin-bottom: 0; }
    .management-container .resource-is-collapsed > .boundary-control-header p { display: none; }
    .resource-collapse-body:not([hidden]) { animation: resource-reveal .22s ease-out; }
    .management-container .resource-tier[aria-busy="true"] > :is(.section-header, .boundary-control-header)::after { content: 'Updating records…'; color: #9a3412; font-size: 11px; padding: 6px 10px; border-radius: 7px; background: linear-gradient(100deg, #fff7ed 25%, #fed7aa 50%, #fff7ed 75%); background-size: 200% 100%; animation: resource-loading 1.6s linear infinite; }
    .management-container .resource-tier[aria-busy="true"] .resource-collapse-body { opacity: .6; }
    @keyframes resource-loading { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    @keyframes resource-reveal { from { opacity: .35; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    @media (hover: hover) and (pointer: fine) {
        .management-container .resource-tier:hover { border-color: #fed7aa; box-shadow: 0 8px 24px #9a34120a; }
        .management-container :is(.chief-theme-summary-card, .resource-priority-map a):hover { transform: translateY(-2px); border-color: #fdba74; box-shadow: 0 6px 18px #9a341210; }
        .management-container .station-table tbody tr:hover { background: #fffaf5; }
    }
    @media (max-width: 1100px) {
        .management-container .resource-order-hero { display: grid; grid-template-columns: minmax(0, 1fr); }
        .management-container .chief-theme-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .management-container .resource-priority-map { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .management-container.chief-theme-shell { gap: 12px; padding-inline: 10px; padding-bottom: calc(110px + env(safe-area-inset-bottom, 0px)); }
        .management-container :is(.resource-order-hero, .resource-tier, .resource-priority-map, .search-filter-bar) :where(div, span, p, a, button) { min-width: 0; overflow-wrap: anywhere; }
        .management-container .chief-theme-summary-card { padding: 10px; }
        .management-container .chief-theme-summary-value { font-size: 23px; }
        .management-container .chief-theme-summary-copy { font-size: 10px; }
        .management-container :is(.section-header, .boundary-control-header) { padding: 12px; }
        .management-container .search-box { flex-basis: 100%; width: 100%; }
        .management-container .filter-actions { display: flex; width: 100%; }
        .management-container .filter-actions > * { flex: 1 1 140px; max-width: 100%; white-space: normal; }
        .management-container .boundary-summary { flex-wrap: wrap; }
        .management-container .boundary-summary-pill { white-space: normal; font-size: 10px; }
        .management-container .tab-navigation { max-width: 100%; overflow-x: auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .management-container *, .resource-collapse-body { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.management-container');
    if (!shell) return;
    const expanders = new Map();
    const openStates = new Map();
    const enhanceCard = (card, index) => {
        if (card.querySelector(':scope > .resource-collapse-body')) return;
        const head = card.querySelector(':scope > .section-header, :scope > .boundary-control-header');
        if (!head) return;
        const title = head.querySelector('h3')?.textContent.trim() || 'Resources';
        const body = document.createElement('div');
        body.className = 'resource-collapse-body';
        body.id = `resourceCollapseBody${index}`;
        while (head.nextSibling) body.appendChild(head.nextSibling);
        card.appendChild(body);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'resource-collapse-toggle';
        toggle.setAttribute('aria-controls', body.id);
        toggle.innerHTML = '<span></span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>';
        const setOpen = open => {
            openStates.set(card.id, open);
            body.hidden = !open;
            card.classList.toggle('resource-is-collapsed', !open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', `${open ? 'Collapse' : 'Expand'} ${title}`);
            toggle.querySelector('span').textContent = open ? 'Collapse' : 'Expand';
        };
        // Keep operational readiness visible, and preserve visibility on paginated visits.
        const paginated = Array.from(new URLSearchParams(location.search).keys()).some(key => key.includes('page'));
        setOpen(openStates.get(card.id) ?? (paginated || card.matches('.resource-tier--operations, .resource-tier--teams, .resource-tier--apparatus')));
        head.appendChild(toggle);
        toggle.addEventListener('click', () => setOpen(body.hidden));
        expanders.set(card.id, () => setOpen(true));
    };
    shell.querySelectorAll('.station-grid.resource-tier, .boundary-control-card.resource-tier').forEach((card, index) => {
        enhanceCard(card, index);
        // Existing AJAX handlers replace each card's innerHTML. Reattach only
        // presentation controls, preserving its open state and the fresh rows.
        new MutationObserver(() => enhanceCard(card, index)).observe(card, { childList: true });
    });
    const revealHash = () => {
        const expand = expanders.get(location.hash.slice(1));
        if (expand) { expand(); document.getElementById(location.hash.slice(1))?.scrollIntoView({ block: 'start' }); }
    };
    shell.querySelectorAll('.resource-priority-map a[href^="#"]').forEach(link => {
        link.addEventListener('click', () => expanders.get(link.hash.slice(1))?.());
    });
    window.addEventListener('hashchange', revealHash);
    if (location.hash) requestAnimationFrame(revealHash);
    // Searches still use the existing filtering handlers; expose their results.
    shell.querySelector('#searchInput')?.addEventListener('input', event => {
        if (event.target.value.trim()) expanders.forEach(expand => expand());
    });
    shell.querySelector('#statusFilter')?.addEventListener('change', () => expanders.forEach(expand => expand()));
});
</script>
