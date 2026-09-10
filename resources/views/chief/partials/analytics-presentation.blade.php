{{-- Presentation only: existing analytics requests, calculations and handlers stay in analytics.blade.php. --}}
<style>
    .chief-theme-shell .analytics-stack { gap: 18px; }
    .forecast-context { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 8px 0 4px; }
    .forecast-context__eyebrow { color: #b91c1c; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .forecast-context h2 { color: #172033; font-size: clamp(22px, 2.4vw, 30px); font-weight: 800; letter-spacing: -.045em; margin: 6px 0; }
    .forecast-context p { color: #64748b; font-size: 13px; line-height: 1.6; max-width: 420px; }
    .forecast-context__facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; max-width: 560px; }
    .forecast-context__facts > div { border-left: 2px solid #fed7aa; padding-left: 12px; min-width: 0; }
    .forecast-context dt { color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
    .forecast-context dd { color: #334155; font-size: 12px; font-weight: 700; margin-top: 5px; line-height: 1.5; overflow-wrap: anywhere; }
    .chief-theme-shell .analytics-shell-card { border: 1px solid #e5e7eb; box-shadow: 0 3px 14px #0f172a04; border-radius: 18px; background: #fff; }
    .chief-theme-shell .analytics-section { padding: 20px; }
    .chief-theme-shell .analytics-section__title { font-size: 16px; letter-spacing: -.025em; }
    .chief-theme-shell .analytics-section__copy { font-size: 12px; line-height: 1.65; max-width: 680px; }
    .chief-theme-shell .forecast-tools { padding: 0; width: 100%; max-width: none; }
    .forecast-tools__summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 15px 18px; cursor: pointer; list-style: none; color: #334155; font-size: 13px; font-weight: 700; border-radius: 18px; }
    .forecast-tools__summary::-webkit-details-marker { display: none; }
    .forecast-tools__summary > span:first-child { display: flex; align-items: center; gap: 10px; }
    .forecast-tools__summary > span:first-child i { color: #dc2626; }
    .forecast-tools__hint { color: #64748b; font-size: 11px; font-weight: 500; }
    .forecast-tools__hint i { margin-left: 12px; transition: transform .2s ease; }
    .forecast-tools[open] .forecast-tools__hint i { transform: rotate(180deg); }
    .forecast-tools[open] .forecast-tools__summary { border-bottom: 1px solid #f1f5f9; border-radius: 18px 18px 0 0; }
    .forecast-tools .analytics-control-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; padding: 16px 18px; }
    .forecast-tools .analytics-control-field { grid-column: span 2; gap: 5px; min-width: 0; }
    .forecast-tools .analytics-control-label { font-size: 10px; letter-spacing: .045em; }
    .forecast-tools .analytics-input, .forecast-tools .analytics-select { min-height: 40px; border-radius: 10px; font-size: 12px; }
    .forecast-tools .analytics-forecast-switch { grid-column: span 2; align-self: end; }
    .forecast-tools .analytics-forecast-switch__button { font-size: 11px; padding: 10px; }
    .forecast-tools .analytics-actions { grid-column: 1 / -1; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin: 0; }
    .forecast-tools .analytics-btn { min-height: 42px; padding: 10px 12px; border-radius: 10px; align-items: center; gap: 8px; box-shadow: none; }
    .forecast-tools .analytics-btn__copy strong { font-size: 12px; }
    .forecast-tools .analytics-btn__copy span { display: none; }
    .forecast-tools .analytics-btn--primary { background: linear-gradient(120deg, #b91c1c, #ea580c); }
    .forecast-tools .analytics-training-progress, .forecast-tools .analytics-training-history { grid-column: 1 / -1; }
    .forecast-history { background: #f8fafc; border-radius: 10px; }
    .forecast-history > summary { cursor: pointer; color: #64748b; font-size: 12px; font-weight: 600; padding: 4px; }
    .forecast-history[open] > summary { margin-bottom: 14px; }
    .chief-theme-shell .prediction-summary-section { width: 100%; max-width: none; padding: 16px 20px; }
    .prediction-summary-section .analytics-section__head { margin-bottom: 10px; }
    .prediction-summary-section .command-decision-strip { gap: 0; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; }
    .prediction-summary-section .command-decision-card { border: 0; border-right: 1px solid #f1f5f9; border-radius: 0; background: #fff; box-shadow: none; padding: 12px 16px; }
    .prediction-summary-section .command-decision-card:last-child { border-right: 0; }
    .chief-theme-shell .analytics-priority-grid { width: 100%; max-width: none; grid-template-columns: minmax(0, 1.8fr) minmax(260px, 1fr); gap: 18px; align-items: start; }
    .chief-theme-shell .forecast-priorities { position: relative; border-top: 3px solid #dc2626; min-height: 280px; }
    .forecast-priorities > .analytics-section__head { padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
    .forecast-priorities .analytics-section__title { font-size: 19px; }
    .forecast-priorities .recommendation-run-group { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: none; overflow: hidden; transition: border-color .2s, box-shadow .2s, transform .2s; }
    .forecast-priorities .recommendation-run-toggle { padding: 16px; background: transparent; }
    .forecast-priorities .recommendation-run-title { font-size: 14px; }
    .forecast-priorities .recommendation-run-subtitle { line-height: 1.55; }
    .forecast-priorities .recommendation-run-chip { border-radius: 6px; font-size: 10px; background: #f8fafc; }
    .forecast-priorities .recommendation-run-arrow { background: #fff1f2; color: #b91c1c; border-radius: 10px; flex-shrink: 0; }
    .forecast-priorities .recommendation-run-action { font-size: 11px; color: #b91c1c; }
    .forecast-narrative { background: #fafbfc !important; }
    .forecast-narrative .analytics-empty-state { background: #fff; }
    .chief-theme-shell .analytics-empty-state { border: 1px dashed #dbe1e9; border-radius: 12px; padding: 22px; }
    .chief-theme-shell .analytics-empty-state__title { font-size: 14px; }
    .chief-theme-shell .analytics-empty-state__copy { font-size: 12px; line-height: 1.7; }
    .chief-theme-shell .analytics-section-heading__eyebrow { color: #b91c1c; font-size: 10px; letter-spacing: .12em; }
    .chief-theme-shell .analytics-chart-wrap { border-radius: 12px; background: #fff; }
    .chief-theme-shell :is(button, a, select, input, summary):focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
    .chief-theme-shell .analytics-stack.is-scope-loading .analytics-shell-card { opacity: 1; }
    .chief-theme-shell .analytics-scope-loading { position: sticky; top: 90px; width: fit-content; margin: 0 auto; z-index: 25; border: 1px solid #fed7aa; background: #fff7ed; color: #9a3412; box-shadow: 0 4px 18px #9a341210; }
    .forecast-skeleton { display: none; gap: 14px; padding: 8px 0; }
    .forecast-skeleton__row { height: 98px; border: 1px solid #edf0f3; border-radius: 12px; padding: 18px; }
    .forecast-skeleton__line { display: block; height: 10px; margin-bottom: 13px; border-radius: 5px; background: linear-gradient(100deg, #edf0f3 25%, #fff7ed 45%, #edf0f3 65%); background-size: 240% 100%; animation: forecast-shimmer 1.8s linear infinite; }
    .forecast-skeleton__line:first-child { width: 55%; height: 14px; }
    .forecast-skeleton__line:last-child { width: 75%; }
    .is-scope-loading .forecast-skeleton { display: grid; }
    .is-scope-loading .forecast-priorities #recommendations { display: none; }
    .forecast-updated { animation: forecast-update .65s ease-out; }
    .analytics-loading-card__bar { overflow: hidden; position: relative; }
    .loading-overlay.active .analytics-loading-card__bar::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, #ffffff70, transparent); animation: forecast-shimmer 1.8s linear infinite; background-size: 240% 100%; }
    @keyframes forecast-shimmer { from { background-position: 120% 0; } to { background-position: -120% 0; } }
    @keyframes forecast-update { from { background-color: #fff7ed; } to { background-color: transparent; } }
    @keyframes forecast-enter { from { opacity: .3; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: no-preference) {
        .forecast-context, .forecast-tools, .prediction-summary-section, .analytics-priority-grid { animation: forecast-enter .4s ease-out both; }
        .prediction-summary-section { animation-delay: .06s; }
        .analytics-priority-grid { animation-delay: .12s; }
        .forecast-tools[open] .analytics-control-grid { animation: forecast-enter .2s ease-out; }
    }
    @media (max-width: 1100px) {
        .forecast-context { align-items: flex-start; flex-direction: column; gap: 16px; }
        .forecast-context__facts { max-width: none; width: 100%; }
        .forecast-tools .analytics-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .chief-theme-shell .analytics-priority-grid { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 640px) {
        .chief-theme-shell .analytics-stack { gap: 12px; }
        .forecast-context__facts { gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .forecast-context__facts > div:last-child { grid-column: 1 / -1; }
        .forecast-context h2 { font-size: 24px; }
        .forecast-tools__summary { padding: 14px; }
        .forecast-tools__hint { font-size: 0; }
        .forecast-tools__hint i { font-size: 12px; }
        .forecast-tools .analytics-control-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 14px; }
        .forecast-tools .analytics-control-field { grid-column: span 1; }
        .forecast-tools .analytics-control-field:first-child { grid-column: 1 / -1; }
        .forecast-tools .analytics-forecast-switch { grid-column: 1 / -1; }
        .chief-theme-shell .analytics-section:not(.forecast-tools) { padding: 15px; }
        .prediction-summary-section .command-decision-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .prediction-summary-section .command-decision-card { padding: 12px; }
        .forecast-priorities .analytics-section__title { font-size: 17px; }
        .forecast-priorities > .analytics-section__head .analytics-section__copy { display: block; font-size: 11px; }
    }
    /* Let nested grids and long generated content shrink to the available
       viewport instead of enlarging the page in mobile/standalone mode. */
    .chief-theme-shell .analytics-stack :where(div, section, article, header, details, summary, button, label, span, p) { min-width: 0; overflow-wrap: anywhere; }
    .chief-theme-shell :is(.analytics-dual-grid, .analytics-panel-grid, .analytics-pattern-group, .analytics-priority-grid) > * { min-width: 0; max-width: 100%; }
    .chief-theme-shell .analytics-section__head { flex-wrap: wrap; }
    .chief-theme-shell .analytics-section__actions { flex-wrap: wrap; min-width: 0; max-width: 100%; }
    .chief-theme-shell :is(.analytics-empty-pill, .recommendation-run-chip, .prediction-driver-chip, .analytics-btn, .analysis-evidence-trigger) { max-width: 100%; white-space: normal; }
    .chief-theme-shell .analytics-chart-canvas { min-width: 0; max-width: 100%; }
    .chief-theme-shell .analytics-chart-canvas canvas { max-width: 100%; }
    .chief-theme-shell .forecast-context__intro { min-width: 0; }
    .chief-theme-shell .forecast-context__facts { min-width: 0; }
    .chief-theme-shell .analytics-scope-loading { max-width: 100%; white-space: normal; }
    .chief-theme-shell .analytics-training-history__head { flex-wrap: wrap; }
    .chief-theme-shell :is(.analytics-shell-card, .prediction-card, .command-decision-card, .analytics-ops-card, .analytics-mini-stat, .analytics-metric-card, .forecast-narrative-card, .recommendation-run-group) {
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }
    .chief-theme-shell :is(.analytics-shell-card, .prediction-card, .command-decision-card, .analytics-ops-card, .analytics-mini-stat, .analytics-metric-card, .forecast-narrative-card, .recommendation-run-group):focus-within {
        border-color: #fb923c;
        box-shadow: 0 0 0 3px #fb923c24, 0 8px 24px #9a34120a;
    }
    @media (hover: hover) and (pointer: fine) {
        .chief-theme-shell :is(.prediction-card, .command-decision-card, .analytics-ops-card, .analytics-mini-stat, .analytics-metric-card, .forecast-narrative-card, .recommendation-run-group):hover {
            transform: translateY(-3px);
            border-color: #fdba74;
            box-shadow: 0 8px 22px #9a341212;
        }
        .chief-theme-shell .analytics-shell-card:hover { border-color: #fed7aa; box-shadow: 0 8px 26px #9a34120a; }
    }
    @media (max-width: 1024px) {
        .chief-theme-shell .analytics-dual-grid { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell .analytics-pattern-group { min-width: 0; max-width: 100%; }
    }
    @media (max-width: 640px) {
        .chief-theme-shell .forecast-tools .analytics-control-grid { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell .forecast-tools .analytics-control-field { grid-column: 1 / -1; }
        .chief-theme-shell .forecast-tools .analytics-actions { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell :is(.analytics-metric-grid, .analytics-model-training-grid, .analytics-run-modal__grid) { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell .recommendation-run-toggle { width: 100%; gap: 8px; }
        .chief-theme-shell .recommendation-run-meta { flex: 1; min-width: 0; }
        .chief-theme-shell .analytics-training-progress__stages { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .chief-theme-shell .analytics-training-history__item { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell .analytics-empty-state { padding: 14px; }
    }
    @media (max-width: 380px) {
        .chief-theme-shell :is(.prediction-summary-grid, .analytics-ops-grid, .command-decision-strip, .forecast-context__facts) { grid-template-columns: minmax(0, 1fr); }
        .chief-theme-shell .prediction-card { grid-column: 1 / -1; }
    }
    @media (display-mode: standalone) {
        .chief-theme-shell { padding-bottom: calc(112px + env(safe-area-inset-bottom, 0px)); }
    }
    .analytics-collapse-toggle { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 8px 12px; background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .analytics-collapse-toggle i { transition: transform .2s ease; }
    .analytics-collapse-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .analytics-collapsible.is-collapsed > .analytics-section__head { margin-bottom: 0; }
    .analytics-collapsible.is-collapsed > .analytics-section__head .analytics-section__copy { display: none; }
    .analytics-collapsible-body[hidden] { display: none !important; }
    .analytics-collapsible-body:not([hidden]) { animation: forecast-enter .2s ease-out; }
    .analytics-checklist-details > summary { cursor: pointer; padding: 4px 0; }
    .analytics-checklist-details > summary::marker { color: #ea580c; }
    .analytics-checklist-details[open] > summary { margin-bottom: 12px; }
    @media (prefers-reduced-motion: reduce) {
        .chief-theme-shell *, .analytics-loading-card * { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const stack = document.querySelector('.analytics-stack');
        const priorities = document.querySelector('.forecast-priorities');
        if (!stack || !priorities) return;

        // Move existing nodes rather than recreating them so chart canvases,
        // loaded data, control IDs and attached handlers are preserved.
        stack.querySelectorAll('.analytics-shell-card').forEach((card, index) => {
            if (card.matches('.forecast-tools, .forecast-priorities, .prediction-summary-section')) return;
            const head = card.querySelector(':scope > .analytics-section__head');
            if (!head) return;
            const title = head.querySelector('.analytics-section__title')?.textContent.trim() || 'Section';
            const body = document.createElement('div');
            body.id = `analyticsCollapsibleBody${index}`;
            body.className = 'analytics-collapsible-body';
            body.hidden = true;
            while (head.nextSibling) body.appendChild(head.nextSibling);
            card.appendChild(body);
            card.classList.add('analytics-collapsible', 'is-collapsed');
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'analytics-collapse-toggle';
            toggle.setAttribute('aria-controls', body.id);
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', `Expand ${title}`);
            toggle.innerHTML = '<span>Expand</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>';
            head.appendChild(toggle);
            toggle.addEventListener('click', () => {
                const open = body.hidden;
                body.hidden = !open;
                card.classList.toggle('is-collapsed', !open);
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', `${open ? 'Collapse' : 'Expand'} ${title}`);
                toggle.querySelector('span').textContent = open ? 'Collapse' : 'Expand';
                if (open) requestAnimationFrame(() => {
                    body.querySelectorAll('canvas').forEach(canvas => window.Chart?.getChart?.(canvas)?.resize());
                });
            });
        });

        // Reflect the page's existing request state; this adds no polling or requests.
        const skeleton = document.createElement('div');
        skeleton.className = 'forecast-skeleton';
        skeleton.setAttribute('aria-hidden', 'true');
        skeleton.innerHTML = Array.from({ length: 3 }, () => '<div class="forecast-skeleton__row"><span class="forecast-skeleton__line"></span><span class="forecast-skeleton__line"></span><span class="forecast-skeleton__line"></span></div>').join('');
        priorities.insertBefore(skeleton, document.getElementById('recommendations'));
        const syncLoading = () => {
            priorities.setAttribute('aria-busy', String(stack.classList.contains('is-scope-loading')));
        };
        new MutationObserver(syncLoading).observe(stack, { attributes: true, attributeFilter: ['class'] });
        syncLoading();

        // Keep the full explanation available on compact action buttons.
        document.querySelectorAll('.forecast-tools .analytics-btn').forEach(button => {
            const description = button.querySelector('.analytics-btn__copy span');
            if (description) button.title = description.textContent.trim();
        });

        // A short visual acknowledgement only when existing data renderers update.
        ['recommendations', 'aiPrediction', 'decisionRisk', 'decisionRange', 'decisionEvidence'].forEach(id => {
            const element = document.getElementById(id);
            if (!element) return;
            let timer;
            new MutationObserver(() => {
                element.classList.remove('forecast-updated');
                clearTimeout(timer);
                requestAnimationFrame(() => {
                    element.classList.add('forecast-updated');
                    timer = setTimeout(() => element.classList.remove('forecast-updated'), 700);
                });
            }).observe(element, { childList: true, subtree: true, characterData: true });
        });
    });
</script>
