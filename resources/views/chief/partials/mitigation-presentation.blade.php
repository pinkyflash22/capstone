{{-- Checklist-style workspace. All record sections remain visible. --}}
<style>
    .mitigation-wrap { max-width: 1280px; }
    .mitigation-wrap .workflow-nav { gap: 6px; padding: 5px; border-radius: 11px; box-shadow: none; margin-bottom: 14px; }
    .mitigation-wrap .workflow-nav__step { border-radius: 8px; min-height: 40px; font-size: 11px; }
    .mitigation-wrap .workflow-nav__step.is-active { background: #fff1e8; color: #b91c1c; box-shadow: none; }
    .mitigation-wrap .workflow-nav__step.is-active i { color: inherit; }
    .mitigation-wrap .workflow-nav__arrow { display: none; }
    .mitigation-wrap .mitigation-banner { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .mitigation-banner .mitigation-banner__meta { width: 100%; min-width: 0; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .mitigation-banner .mitigation-banner__meta-card { border-top: 1px solid #e2e8f0; padding: 14px; }
    .mitigation-banner .mitigation-banner__meta-value { font-size: 26px; line-height: 1.3; }
    .mitigation-stack { gap: 12px; }
    .mitigation-stack > .mitigation-summary { order: 0; }
    .mitigation-stack > .mitigation-attention { order: 1; }
    .mitigation-stack > .mitigation-register { order: 2; }
    .mitigation-stack > .mitigation-ai-saved { order: 3; }
    .mitigation-stack > .mitigation-ai-recommendations { order: 4; }
    .mitigation-stack > .panel { padding: 0; overflow: hidden; }
    .mitigation-stack > .panel::before { display: none; }
    .mitigation-stack .panel-head { margin: 0; border-bottom: 1px solid #f1f5f9; }
    .mitigation-stack .panel-head h3 { margin: 0 0 4px; }
    .mitigation-stack .panel-head p { margin: 0; }
    .mitigation-stack .insight-list { padding: 16px; gap: 10px; }
    .mitigation-stack .insight-card { border-radius: 12px; box-shadow: none; }
    .mitigation-stack .mitigation-filter-region { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; }
    .mitigation-filter-region #mitigationFilters { padding: 0 !important; border: 0; border-radius: 0; background: transparent; }
    .mitigation-filter-region #mitigationFilters::before { display: none; }
    .mitigation-filter-region .filters-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .mitigation-filter-region .mitigation-field--full { grid-column: 1 / -1; }
    .mitigation-stack .table-wrap { margin: 0; border: 0; border-radius: 0; max-width: 100%; max-height: none; overflow-x: auto; }
    .mitigation-stack .mitigation-register-table th { background: #f8fafc; font-size: 10px; letter-spacing: .06em; padding: 11px 13px; }
    .mitigation-stack .mitigation-register-table td { padding: 12px 13px; font-size: 12px; }
    .mitigation-stack .table-pagination { margin: 0; padding: 14px 18px; border-top: 1px solid #e2e8f0; border-radius: 0; }
    .mitigation-stack .row-actions { flex-wrap: wrap; }
    .mitigation-stack .row-btn { min-width: 40px; min-height: 40px; }
    .mitigation-wrap :is(input, select, textarea) { max-width: 100%; }
    @media (max-width: 767px), (display-mode: standalone) and (max-width: 1024px) {
        .mitigation-wrap { min-width: 0; max-width: 100%; }
        .mitigation-wrap .workflow-nav { width: 100%; flex-wrap: wrap; }
        .mitigation-wrap .workflow-nav__step { flex: 1 1 85px; justify-content: center; }
        .mitigation-banner .mitigation-banner__meta { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .mitigation-banner .mitigation-banner__meta-card { padding: 11px; }
        .mitigation-banner .mitigation-banner__meta-label { font-size: 10px; }
        .mitigation-stack .panel-head { display: flex; flex-direction: column; align-items: stretch; gap: 10px; border-radius: 0; box-shadow: none; }
        .mitigation-stack .panel-head p { display: block; font-size: 12px; }
        .mitigation-stack .mitigation-register .panel-head > .chip-row { display: flex; width: 100%; }
        .mitigation-stack .register-sort { flex: 1; min-width: 0; }
        .mitigation-stack .register-sort select { width: 100%; min-height: 44px; font-size: 14px; }
        .mitigation-stack .mitigation-filter-region { padding: 12px; }
        .mitigation-filter-region .filters-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mitigation-filter-region .mitigation-field { grid-column: auto; }
        .mitigation-filter-region .mitigation-field--full { grid-column: 1 / -1; }
        .mitigation-stack .mitigation-field :is(input, select) { font-size: 16px; min-height: 44px; }
        .mitigation-stack .mitigation-register-table { display: block; min-width: 0; width: 100%; }
        .mitigation-stack .mitigation-register-table thead { display: none; }
        .mitigation-stack .mitigation-register-table tbody { display: grid; gap: 10px; padding: 12px; }
        .mitigation-stack .mitigation-register-table tbody tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 13px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: none; }
        .mitigation-stack .mitigation-register-table tbody td { min-width: 0; padding: 0; overflow-wrap: anywhere; }
        .mitigation-stack .mitigation-register-table td[colspan] { grid-column: 1 / -1; }
        .mitigation-stack .register-owner { max-width: none; }
        .mitigation-stack .row-actions { gap: 8px; }
        .mitigation-stack .mitigation-attention { display: flex; flex-wrap: wrap; overflow: visible; gap: 8px; }
        .mitigation-stack .mitigation-attention .filter-btn { width: auto; min-width: 44px; min-height: 44px; padding: 8px 10px; }
        .mitigation-stack .mitigation-attention .filter-btn span { position: static; width: auto; height: auto; margin: 0; clip: auto; overflow: visible; font-size: 11px; }
        .mitigation-stack .row-actions .row-btn { min-height: 44px; min-width: 44px; }
        .mitigation-stack .insight-list { grid-template-columns: minmax(0, 1fr); padding: 12px; }
        .mitigation-stack .insight-card__actions { flex-wrap: wrap; }
    }
    @media (max-width: 390px) {
        .mitigation-filter-region .filters-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>
