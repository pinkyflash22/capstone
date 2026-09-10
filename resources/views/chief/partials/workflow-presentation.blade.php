{{-- Shared presentation for Mitigation Control and Action Plan Checklists. --}}
<style>
    .mitigation-wrap .mitigation-banner, .checklist-container .forecast-hero { background: transparent; border: 0; box-shadow: none; border-radius: 0; padding: 12px 0; gap: 20px; }
    .mitigation-wrap .mitigation-banner::before, .mitigation-wrap .mitigation-banner::after, .checklist-container .forecast-hero::before, .checklist-container .forecast-hero::after { display: none; }
    .mitigation-banner .chief-theme-hero__eyebrow, .checklist-container .forecast-hero__eyebrow { padding: 0; background: transparent; border: 0; color: #b91c1c; font-size: 10px; font-weight: 800; letter-spacing: .12em; }
    .mitigation-wrap .mitigation-banner__title, .checklist-container .forecast-hero h2 { margin: 8px 0; color: #172033; font-size: clamp(23px, 2.5vw, 30px); line-height: 1.25; letter-spacing: -.04em; }
    .mitigation-wrap .mitigation-banner__copy, .checklist-container .forecast-hero p { color: #64748b; font-size: 13px; line-height: 1.65; max-width: 580px; }
    .mitigation-banner .mitigation-banner__meta { gap: 10px; }
    .mitigation-banner .mitigation-banner__meta-card { background: #fff; padding: 13px; border: 1px solid #e2e8f0; border-top: 2px solid #ea580c; border-radius: 12px; box-shadow: none; }
    .mitigation-banner .mitigation-banner__meta-label { color: #64748b; font-size: 10px; letter-spacing: .06em; }
    .mitigation-banner .mitigation-banner__meta-value { color: #172033; font-size: 23px; font-weight: 800; }
    .mitigation-stack { gap: 16px; }
    .mitigation-stack > .panel, .checklist-workspace .checklist-register { border: 1px solid #e2e8f0; border-radius: 16px; background: #fff !important; box-shadow: 0 3px 14px #0f172a04; min-width: 0; max-width: 100%; }
    .mitigation-stack > .mitigation-register, .checklist-workspace .checklist-register { border-top: 3px solid #dc2626; }
    .mitigation-stack .panel-head, .checklist-workspace .checklist-register__head { padding: 16px 18px; gap: 12px; flex-wrap: wrap; align-items: center; }
    .mitigation-stack .panel-head h3, .checklist-workspace .checklist-register__head h2 { color: #172033; font-size: 17px; font-weight: 800; letter-spacing: -.025em; }
    .mitigation-stack .panel-head p, .checklist-workspace .checklist-register__head p { color: #64748b; font-size: 12px; line-height: 1.6; }
    .checklist-workspace .checklist-metrics { gap: 10px; }
    .checklist-workspace .checklist-metric { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: none; min-width: 0; }
    .checklist-workspace .checklist-metric strong { font-size: 26px; color: #172033; }
    .checklist-workspace .checklist-metric small { color: #64748b; font-size: 11px; }
    .checklist-container .forecast-hero .workflow-nav { gap: 6px; background: #fff; border: 1px solid #e2e8f0; border-radius: 11px; padding: 5px; max-width: 100%; }
    .checklist-container .forecast-hero .workflow-nav a { border-radius: 8px; min-height: 40px; font-size: 11px; }
    .checklist-container .forecast-hero .workflow-nav a.active { background: #fff1e8; color: #b91c1c; }
    .checklist-workspace .mobile-checklist-filter-toggle { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: calc(100% - 32px); margin: 0 16px 12px; padding: 12px; color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; cursor: pointer; font-size: 12px; font-weight: 700; }
    .checklist-workspace .checklist-filter-region:not(.is-open) { display: none; }
    .checklist-workspace .checklist-filter-region.is-open { display: block; animation: workflow-reveal .2s ease-out; }
    .checklist-workspace .checklist-filters { gap: 8px; }
    .checklist-workspace :is(.checklist-filters input, .checklist-filters select, .bulk-toolbar select) { min-width: 0; max-width: 100%; min-height: 40px; font-size: 12px; border-radius: 9px; }
    .checklist-workspace .checklist-quick-filters { display: flex; flex-wrap: wrap; gap: 6px; }
    .checklist-workspace .checklist-quick-filters a { border-radius: 8px; padding: 8px 10px; font-size: 11px; }
    .mitigation-stack .filter-disclosure { margin: 0 16px 14px; border: 1px solid #e2e8f0; border-radius: 10px; }
    .mitigation-stack .filter-disclosure > summary { min-height: 42px; padding: 12px; color: #9a3412; background: #fff7ed; border-radius: 10px; font-size: 12px; }
    .mitigation-stack .filters-grid { gap: 10px; }
    .mitigation-stack .mitigation-field :is(input, select) { min-height: 40px; min-width: 0; max-width: 100%; border-radius: 9px; font-size: 12px; }
    .mitigation-stack :is(.hero-btn, .filter-btn), .checklist-workspace :is(.action-button, .icon-button) { min-height: 40px; border-radius: 9px; }
    .mitigation-stack :is(.chip-row, .boundary-summary), .checklist-workspace :is(.bulk-toolbar, .checklist-register__tools) { flex-wrap: wrap; min-width: 0; gap: 8px; }
    .checklist-workspace .checklist-table-wrap { max-width: 100%; overflow-x: auto; overscroll-behavior-x: contain; }
    .checklist-workspace .checklist-table th { color: #64748b; background: #f8fafc; font-size: 10px; letter-spacing: .06em; }
    .checklist-workspace .checklist-table td { font-size: 12px; }
    .checklist-workspace .plan-cell strong { white-space: normal; overflow-wrap: anywhere; }
    .mitigation-stack :is(.panel, article), .mitigation-banner .mitigation-banner__meta-card, .checklist-workspace :is(.checklist-metric, .checklist-register) { transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .mitigation-stack :is(.panel, article):focus-within, .checklist-workspace .checklist-register:focus-within { border-color: #fb923c; box-shadow: 0 0 0 3px #fb923c20; }
    .mitigation-stack :is(button, a, input, select, summary):focus-visible, .checklist-workspace :is(button, a, input, select):focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
    .workflow-collapse-toggle { display: inline-flex; align-items: center; gap: 8px; margin-left: auto; min-height: 40px; padding: 8px 12px; color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .workflow-collapse-toggle i { transition: transform .2s ease; }
    .workflow-collapse-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .workflow-collapse-body[hidden] { display: none !important; }
    .workflow-collapse-body:not([hidden]) { animation: workflow-reveal .22s ease-out; }
    .workflow-is-collapsed > .panel-head { margin-bottom: 0; border-bottom: 0; }
    .workflow-is-collapsed > .panel-head .suggestion-scan-help { display: none; }
    .checklist-workspace[aria-busy="true"] .checklist-register__head::after { content: 'Updating checklists…'; color: #9a3412; font-size: 11px; padding: 6px 10px; border-radius: 7px; background: linear-gradient(100deg, #fff7ed, #fed7aa, #fff7ed); background-size: 200% 100%; animation: workflow-loading 1.6s linear infinite; }
    .checklist-workspace[aria-busy="true"] .checklist-table-wrap { opacity: .6; transition: opacity .2s ease; }
    @keyframes workflow-reveal { from { opacity: .4; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes workflow-loading { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    @media (hover: hover) and (pointer: fine) {
        .checklist-workspace .checklist-metric:hover, .mitigation-banner .mitigation-banner__meta-card:hover { transform: translateY(-2px); border-color: #fdba74; box-shadow: 0 6px 18px #9a341210; }
        .mitigation-stack > .panel:hover { border-color: #fed7aa; box-shadow: 0 8px 22px #9a34120a; }
        .checklist-workspace .checklist-table tbody tr:hover { background: #fffaf5; }
    }
    @media (max-width: 1024px) {
        .mitigation-wrap .mitigation-banner, .checklist-container .forecast-hero { display: grid; grid-template-columns: minmax(0, 1fr); }
        .mitigation-banner .mitigation-banner__meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .mitigation-stack, .checklist-workspace, .mitigation-banner, .checklist-container .forecast-hero { min-width: 0; max-width: 100%; }
        .mitigation-stack :where(div, article, section, p, span, button), .checklist-workspace :where(div, article, section, p, span, button) { min-width: 0; overflow-wrap: anywhere; }
        .mitigation-stack .panel-head, .checklist-workspace .checklist-register__head { padding: 13px; }
        .mitigation-stack .panel-head h3, .checklist-workspace .checklist-register__head h2 { font-size: 16px; }
        .mitigation-stack .mitigation-register .panel-head > .chip-row { width: 100%; grid-template-columns: minmax(0, 1fr) auto; }
        .mitigation-stack .mitigation-register .panel-head .mobile-icon-action { min-width: 44px; }
        .mitigation-stack .filters-grid, .checklist-workspace .checklist-filters { grid-template-columns: minmax(0, 1fr); }
        .mitigation-stack .mitigation-field, .checklist-workspace .checklist-search { grid-column: 1 / -1; }
        .checklist-workspace .checklist-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .checklist-workspace .checklist-metric { padding: 11px; }
        .mitigation-banner .mitigation-banner__meta-value { font-size: 21px; }
        .checklist-workspace .bulk-toolbar > select { flex: 1 1 140px; }
        .checklist-container .forecast-hero .workflow-nav { width: 100%; flex-wrap: wrap; }
        .checklist-container .forecast-hero .workflow-nav a { flex: 1 1 80px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .mitigation-stack *, .mitigation-banner *, .checklist-workspace *, .checklist-container .forecast-hero * { animation: none !important; transition: none !important; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.mitigation-ai-saved, .mitigation-ai-recommendations').forEach((card, index) => {
        const head = card.querySelector(':scope > .panel-head');
        if (!head) return;
        const title = head.querySelector('h3')?.textContent.trim() || 'Supporting details';
        const body = document.createElement('div');
        body.className = 'workflow-collapse-body';
        body.id = `workflowSupportingBody${index}`;
        body.hidden = true;
        while (head.nextSibling) body.appendChild(head.nextSibling);
        card.appendChild(body);
        card.classList.add('workflow-is-collapsed');
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'workflow-collapse-toggle';
        toggle.setAttribute('aria-controls', body.id);
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', `Expand ${title}`);
        toggle.innerHTML = '<span>Expand</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>';
        head.appendChild(toggle);
        toggle.addEventListener('click', () => {
            const open = body.hidden;
            body.hidden = !open;
            card.classList.toggle('workflow-is-collapsed', !open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', `${open ? 'Collapse' : 'Expand'} ${title}`);
            toggle.querySelector('span').textContent = open ? 'Collapse' : 'Expand';
        });
    });
});
</script>
