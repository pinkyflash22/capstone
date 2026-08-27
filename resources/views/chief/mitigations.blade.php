<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Mitigation Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <style>
        html, body, body.chief-dashboard-page { min-height: 100%; height: auto; overflow-x: hidden; overflow-y: auto; }
        body { margin: 0; background: #f3f4f6; color: #0f172a; font-family: Inter, system-ui, sans-serif; }
        .mitigation-shell { min-height: 100vh; background: radial-gradient(circle at 92% 2%, rgba(251,191,36,.12), transparent 28%), linear-gradient(180deg, #fffaf5 0, #f3f4f6 310px); }
        .mitigation-main { min-width: 0; width: 100%; overflow: visible; }
        .mitigation-wrap { width: 100%; max-width: 1480px; margin: 0 auto; }
        .dashboard-container { padding: 20px; overflow: visible; }
        .top-bar { background: rgba(255,255,255,.9); backdrop-filter: blur(14px); border: 1px solid rgba(226,232,240,.92); min-height: 72px; padding: 0 24px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:16px; z-index:40; margin-bottom:16px; border-radius:24px; box-shadow:0 16px 36px rgba(15,23,42,.06); }
        .workflow-nav { display:flex; align-items:center; gap:8px; margin:0 0 20px; padding:8px; width:max-content; max-width:100%; border:1px solid rgba(226,232,240,.94); border-radius:999px; background:rgba(255,255,255,.9); box-shadow:0 10px 28px rgba(15,23,42,.04); overflow-x:auto; }
        .workflow-nav__step { display:inline-flex; align-items:center; gap:8px; min-height:38px; padding:0 14px; border-radius:999px; color:#64748b; text-decoration:none; font-size:.76rem; font-weight:800; white-space:nowrap; }
        .workflow-nav__step i { color:#f97316; }
        .workflow-nav__step:hover { color:#9a3412; background:#fff7ed; }
        .workflow-nav__step.is-active { color:#fff; background:linear-gradient(135deg, #f97316, #dc2626); box-shadow:0 10px 22px rgba(234,88,12,.2); }
        .workflow-nav__step.is-active i { color:#fff; }
        .workflow-nav__arrow { color:#cbd5e1; font-size:.68rem; }
        .card, .panel { position:relative; overflow:hidden; background: rgba(255,255,255,.97); border:1px solid rgba(226,232,240,.92); border-radius:18px; box-shadow:0 10px 26px rgba(15,23,42,.04); }
        .panel::before { content:""; position:absolute; inset:0 0 auto; height:3px; background:linear-gradient(90deg, #dc2626, #f59e0b); }
        .card { padding:13px; }
        .panel { padding:16px; }
        .mitigation-banner { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 20px; margin-bottom:14px; border-radius:20px; border:1px solid rgba(251,146,60,.24); background: linear-gradient(135deg, rgba(255,247,237,.99), rgba(255,255,255,.98) 62%, rgba(254,242,242,.96)); box-shadow: 0 10px 26px rgba(15,23,42,.04); }
        .mitigation-banner__title { margin:0; color:#1e293b; font-size:1.3rem; font-weight:900; letter-spacing:-0.03em; }
        .mitigation-banner__copy { margin:5px 0 0; color:#64748b; font-size:.8rem; line-height:1.45; max-width:68ch; }
        .mitigation-banner__meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; min-width:300px; }
        .mitigation-banner__meta-card { padding:9px 11px; border-radius:12px; background:rgba(255,255,255,.7); border:1px solid rgba(251,191,36,.25); }
        .mitigation-banner__meta-label, .card-label, .section-label, th { color:#64748b; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .mitigation-banner__meta-value { margin-top:3px; color:#1f2937; font-size:.82rem; font-weight:800; }
        .mitigation-stack { display:flex; flex-direction:column; gap:14px; }
        .summary-grid { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:8px; }
        .summary-grid .card { min-height:72px; }
        .card-value { margin-top:7px; color:#0f172a; font-size:1.35rem; font-weight:900; line-height:1; }
        .card-copy { display:none; }
        .panel-head { display:flex; align-items:end; justify-content:space-between; gap:12px; margin-bottom:12px; }
        .panel-head h3 { margin:0; color:#0f172a; font-size:1rem; font-weight:900; }
        .panel-head p { margin:4px 0 0; color:#64748b; font-size:.78rem; line-height:1.45; max-width:72ch; }
        .chip-row { display:flex; flex-wrap:wrap; gap:8px; }
        .chip { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 10px; border-radius:999px; background:#f8fafc; border:1px solid rgba(226,232,240,.96); color:#475569; font-size:.74rem; font-weight:700; }
        .chip.overdue { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
        .tag { display:inline-flex; align-items:center; min-height:30px; padding:0 12px; border-radius:999px; font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
        .tag.critical, .tag.high { background:#fff7ed; color:#c2410c; }
        .tag.medium { background:#eff6ff; color:#1d4ed8; }
        .tag.low { background:#ecfdf5; color:#047857; }
        .tag.draft { background:#f1f5f9; color:#475569; }
        .tag.planned { background:#fff7ed; color:#c2410c; }
        .tag.approved { background:#ede9fe; color:#6d28d9; }
        .tag.in_progress { background:#eff6ff; color:#1d4ed8; }
        .tag.blocked { background:#fef2f2; color:#b91c1c; }
        .tag.completed { background:#ecfdf5; color:#047857; }
        .tag.cancelled { background:#f1f5f9; color:#64748b; }
        .tag.ai_action_plan { background:#fdf4ff; color:#a21caf; }
        .tag.ai_recommendation { background:#eef2ff; color:#4338ca; }
        .tag.prediction { background:#eff6ff; color:#1d4ed8; }
        .tag.hazard_report { background:#fff7ed; color:#b45309; }
        .tag.incident_review { background:#ecfeff; color:#0f766e; }
        .tag.manual { background:#f8fafc; color:#475569; }
        .hero-btn, .row-btn, .submit-btn, .cancel-btn, .filter-btn { border:none; border-radius:999px; font-weight:800; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .hero-btn, .submit-btn, .filter-btn.primary { min-height:38px; padding:0 14px; background:linear-gradient(135deg, #f97316, #ef4444); color:#fff; box-shadow:0 10px 22px rgba(239,68,68,.14); font-size:.78rem; }
        .filter-btn.secondary, .cancel-btn { min-height:38px; padding:0 14px; border:1px solid rgba(203,213,225,.96); background:#fff; color:#475569; font-size:.78rem; }
        .row-btn { width:34px; justify-content:center; min-height:34px; padding:0; font-size:.76rem; white-space:nowrap; border:1px solid transparent; }
        .row-btn.view { background:#eef2ff; color:#4338ca; }
        .row-btn.edit { background:#eff6ff; color:#1d4ed8; }
        .row-btn.delete { background:#fef2f2; color:#b91c1c; }
        .table-wrap { overflow:auto; border:1px solid rgba(226,232,240,.92); border-radius:18px; background:#fff; max-height:68vh; }
        table { width:100%; min-width:860px; border-collapse:separate; border-spacing:0; table-layout:fixed; }
        th, td { padding:12px 10px; text-align:left; vertical-align:middle; border-bottom:1px solid rgba(241,245,249,1); }
        th { position:sticky; top:0; z-index:2; background:rgba(248,250,252,.96); backdrop-filter:blur(8px); }
        td { color:#334155; font-size:.88rem; background:rgba(255,255,255,.72); }
        tbody tr:hover td { background:rgba(255,247,237,.68); }
        tbody tr:last-child td { border-bottom:none; }
        .title { color:#0f172a; font-weight:800; font-size:.95rem; line-height:1.4; }
        .note { margin-top:6px; color:#64748b; font-size:.82rem; line-height:1.5; }
        .note--clamp { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .empty { padding:26px 24px; border-radius:22px; border:1px dashed rgba(203,213,225,.96); color:#64748b; text-align:center; background:#f8fafc; }
        .filters-grid, .details-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; }
        .details-grid.two-col { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .mitigation-field, .details-card { display:flex; flex-direction:column; gap:8px; }
        .mitigation-field--full, .details-card--full { grid-column:1 / -1; }
        .mitigation-field label { display:flex; align-items:center; gap:8px; color:#334155; font-size:.78rem; font-weight:850; line-height:1.35; }
        .mitigation-field label i { display:inline-flex; align-items:center; justify-content:center; width:19px; color:#ea580c; font-size:.8rem; flex:0 0 19px; }
        .mitigation-field label em { margin-left:auto; color:#94a3b8; font-size:.65rem; font-style:normal; font-weight:700; letter-spacing:.03em; text-transform:uppercase; }
        .mitigation-field > small { color:#64748b; font-size:.7rem; line-height:1.4; }
        .mitigation-field input, .mitigation-field select, .mitigation-field textarea { width:100%; min-height:48px; border:1px solid rgba(203,213,225,.96); border-radius:13px; padding:11px 13px; font-size:.84rem; color:#0f172a; background:#fff; transition:border-color .16s ease, box-shadow .16s ease, background .16s ease; }
        .mitigation-field input:focus, .mitigation-field select:focus, .mitigation-field textarea:focus { outline:none; border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.12); }
        .mitigation-field input:hover, .mitigation-field select:hover, .mitigation-field textarea:hover { border-color:#94a3b8; }
        .mitigation-form-layout { display:flex; flex-direction:column; gap:16px; }
        .mitigation-form-block { border:1px solid rgba(226,232,240,.98); border-radius:20px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.035); overflow:hidden; }
        .mitigation-form-block__head { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:12px; min-height:74px; padding:15px 18px; border-bottom:1px solid #f1f5f9; background:linear-gradient(135deg, #fffaf5, #fff 68%); }
        .mitigation-form-block__head h4 { margin:0; color:#0f172a; font-size:.9rem; font-weight:900; letter-spacing:-.01em; }
        .mitigation-form-block__head p { margin:4px 0 0; color:#64748b; font-size:.72rem; line-height:1.45; }
        .mitigation-form-block__icon { display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:13px; color:#c2410c; background:#ffedd5; border:1px solid #fed7aa; font-size:1rem; }
        .mitigation-form-block__icon.is-blue { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
        .mitigation-form-block__icon.is-green { color:#047857; background:#ecfdf5; border-color:#a7f3d0; }
        .mitigation-form-block__step { color:#cbd5e1; font-size:.68rem; font-weight:900; letter-spacing:.12em; }
        .mitigation-form-block__grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:17px 18px; padding:20px; }
        .mitigation-form-block--details > summary { cursor:pointer; list-style:none; border-bottom:0; }
        .mitigation-form-block--details > .mitigation-form-block__head { grid-template-columns:auto minmax(0,1fr) auto auto; }
        .mitigation-form-block--details > summary::-webkit-details-marker { display:none; }
        .mitigation-form-block--details[open] > summary { border-bottom:1px solid #f1f5f9; }
        .mitigation-form-block__chevron { color:#94a3b8; transition:transform .18s ease; }
        .mitigation-form-block--details[open] .mitigation-form-block__chevron { transform:rotate(180deg); }
        .mitigation-workflow-note { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; border:1px solid rgba(249,115,22,.22); background:#fff7ed; color:#9a3412; }
        .mitigation-workflow-note__icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:11px; background:#ffedd5; flex:0 0 34px; }
        .mitigation-workflow-note strong { font-size:.78rem; }
        .mitigation-workflow-note p { margin:3px 0 0; color:#b45309; font-size:.72rem; line-height:1.5; }
        .mitigation-upload-field input[type="file"] { padding:9px; background:#f8fafc; }
        .mitigation-upload-field input[type="file"]::file-selector-button { min-height:30px; margin-right:10px; padding:0 12px; border:0; border-radius:9px; color:#9a3412; background:#ffedd5; font-weight:800; cursor:pointer; }
        .table-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 4px 0; color:#64748b; font-size:.82rem; }
        .table-pagination nav { display:flex; gap:6px; flex-wrap:wrap; }
        .table-pagination a, .table-pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 10px; border:1px solid #e2e8f0; border-radius:10px; color:#475569; text-decoration:none; font-weight:800; background:#fff; }
        .table-pagination .active span { color:#fff; background:#f97316; border-color:#f97316; }
        .mitigation-field textarea { min-height:96px; resize:vertical; }
        .filter-actions { display:flex; gap:10px; align-items:flex-end; justify-content:flex-end; }
        .attachments { display:flex; flex-direction:column; gap:8px; }
        .attachments a { display:block; color:#c2410c; font-weight:700; text-decoration:none; overflow-wrap:anywhere; }
        .progress-wrap { display:flex; flex-direction:column; gap:8px; }
        .progress-meta { display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:.76rem; color:#64748b; font-weight:700; }
        .progress-bar { height:10px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
        .progress-bar > span { display:block; height:100%; border-radius:999px; background:linear-gradient(135deg, #f97316, #ef4444); }
        .row-actions { display:flex; align-items:center; justify-content:flex-start; gap:8px; min-width:0; }
        .details-stack { display:flex; flex-direction:column; gap:16px; }
        .details-section { border:1px solid rgba(226,232,240,.92); border-radius:22px; padding:18px; background:linear-gradient(180deg, rgba(248,250,252,.58), rgba(255,255,255,.96)); }
        .details-section h4 { margin:0 0 12px; color:#0f172a; font-size:1rem; font-weight:900; }
        .details-item { display:flex; flex-direction:column; gap:4px; }
        .details-item strong { color:#0f172a; font-size:.9rem; }
        .details-item span, .details-item div { color:#475569; font-size:.84rem; line-height:1.55; }
        .task-list, .plain-list { display:flex; flex-direction:column; gap:10px; }
        .task-item { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:16px; border:1px solid rgba(226,232,240,.92); background:#fff; }
        .task-item .fa-circle-check { color:#16a34a; }
        .task-item .fa-clock { color:#f59e0b; }
        .task-item .fa-ban { color:#dc2626; }
        .mitigation-dialog { position:fixed; inset:0; z-index:90; display:none; align-items:center; justify-content:center; padding:24px; background:rgba(15,23,42,.58); backdrop-filter:blur(8px); overflow:hidden; }
        .mitigation-dialog.is-open { display:flex; }
        .mitigation-dialog__card { width:min(680px, calc(100vw - 40px)); max-width:680px; max-height:min(86vh, 760px); overflow-y:auto; padding:18px; border-radius:24px; border:1px solid rgba(226,232,240,.94); background:rgba(255,255,255,.98); box-shadow:0 28px 60px rgba(15,23,42,.18); flex:0 0 auto; margin:0 auto; }
        .mitigation-dialog__card.wide { width:min(860px, calc(100vw - 40px)); max-width:860px; }
        .mitigation-dialog__card.mitigation-form-dialog { display:flex; flex-direction:column; width:min(980px, calc(100vw - 48px)); max-width:980px; max-height:min(92vh, 900px); padding:0; overflow:hidden; }
        .mitigation-dialog__head { display:flex; align-items:start; justify-content:space-between; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid rgba(226,232,240,.92); }
        .mitigation-form-dialog .mitigation-dialog__head { align-items:center; margin:0; padding:19px 22px; background:#fff; flex:0 0 auto; }
        .mitigation-dialog__title { display:flex; align-items:center; gap:13px; min-width:0; }
        .mitigation-dialog__title-icon { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:14px; color:#fff; background:linear-gradient(135deg,#f97316,#dc2626); box-shadow:0 8px 18px rgba(234,88,12,.2); flex:0 0 44px; }
        .mitigation-dialog__head h3 { margin:0; color:#0f172a; font-size:1rem; font-weight:900; }
        .mitigation-dialog__head p { margin:4px 0 0; color:#64748b; font-size:.82rem; line-height:1.5; max-width:42ch; }
        .mitigation-dialog__close { width:42px; height:42px; border:1px solid rgba(226,232,240,.94); border-radius:14px; background:#fff; color:#334155; }
        .mitigation-form-dialog form { display:flex; flex-direction:column; min-height:0; }
        .mitigation-form-scroll { min-height:0; padding:20px 22px; overflow-y:auto; overscroll-behavior:contain; background:#f8fafc; }
        .form-actions { display:flex; justify-content:end; gap:12px; margin-top:20px; }
        .mitigation-form-dialog .form-actions { margin:0; padding:15px 22px; border-top:1px solid #e2e8f0; background:#fff; box-shadow:0 -8px 22px rgba(15,23,42,.035); flex:0 0 auto; }
        .user-dropdown { position:relative; }
        .user-dropdown-toggle { display:flex; align-items:center; gap:12px; padding:8px 16px; border-radius:999px; background:#fffbeb; border:1px solid #fef3c7; cursor:pointer; }
        .user-dropdown-menu { position:absolute; top:calc(100% + 12px); right:0; width:260px; background:white; border-radius:18px; box-shadow:0 24px 56px rgba(15,23,42,.14); border:1px solid rgba(226,232,240,.95); opacity:0; visibility:hidden; transform:translateY(10px); transition:all .2s ease; overflow:hidden; }
        .user-dropdown-menu.show { opacity:1; visibility:visible; transform:translateY(0); }
        .user-dropdown-item { display:flex; align-items:center; gap:12px; padding:12px 18px; color:#1e293b; text-decoration:none; font-size:14px; width:100%; border:none; background:none; text-align:left; }
        .user-dropdown-item:hover { background:#fff0e8; color:#c63d26; }
        .mobile-page-head { display:none; }
        .mitigation-register { order:1; }
        .mitigation-summary { order:2; }
        .mitigation-draft-ready { order:0; }
        .mitigation-ai-saved { order:3; }
        .mitigation-ai-recommendations { order:4; }
        .compact-draft-list { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px; }
        .compact-draft-card { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:14px; padding:14px 16px; border:1px solid rgba(226,232,240,.92); border-radius:18px; background:#fff; }
        .compact-draft-card__body { min-width:0; }
        .compact-draft-card__title { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .compact-draft-card__meta { display:flex; align-items:center; flex-wrap:wrap; gap:6px; margin-top:7px; color:#64748b; font-size:.72rem; }
        .compact-draft-card__actions { display:flex; align-items:center; gap:6px; }
        .compact-action { width:34px; min-height:34px; padding:0; justify-content:center; border-radius:11px; box-shadow:none; }
        .compact-action span { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
        .filter-disclosure { margin-bottom:14px; border:1px solid rgba(226,232,240,.92); border-radius:16px; background:#f8fafc; }
        .filter-disclosure > summary { display:flex; align-items:center; gap:8px; padding:11px 14px; cursor:pointer; color:#475569; font-size:.78rem; font-weight:800; list-style:none; }
        .filter-disclosure > summary::-webkit-details-marker { display:none; }
        .filter-disclosure[open] > summary { border-bottom:1px solid #e2e8f0; }
        .filter-disclosure .panel { border:0; border-radius:0 0 16px 16px; margin:0 !important; background:#fff; }
        .mitigation-ai-saved > div:last-child, .mitigation-ai-recommendations > div:last-child { display:grid !important; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px !important; }
        .mitigation-ai-saved article.card, .mitigation-ai-recommendations article.card { padding:13px !important; border-radius:16px; box-shadow:none; }
        .mitigation-ai-saved article.card > div, .mitigation-ai-recommendations article.card > div { display:grid !important; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:10px !important; }
        .mitigation-ai-saved article.card .note, .mitigation-ai-recommendations article.card .note { display:none; }
        .mitigation-ai-saved article.card .chip-row, .mitigation-ai-recommendations article.card .chip-row { margin:0 0 7px !important; }
        .mitigation-ai-saved article.card > div > div:last-child, .mitigation-ai-recommendations article.card > div > div:last-child { min-width:0 !important; display:flex !important; flex-direction:row !important; gap:6px !important; }
        .mitigation-ai-saved article.card > div > div:last-child .hero-btn,
        .mitigation-ai-saved article.card > div > div:last-child .filter-btn,
        .mitigation-ai-recommendations article.card > div > div:last-child .hero-btn,
        .mitigation-ai-recommendations article.card > div > div:last-child .filter-btn { width:34px !important; min-height:34px; padding:0; justify-content:center; border-radius:11px; box-shadow:none; }
        .mitigation-ai-saved article.card > div > div:last-child span,
        .mitigation-ai-recommendations article.card > div > div:last-child span { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }

        @media (min-width: 1025px) {
            .mitigation-main { margin-left: var(--sidebar-w, 280px); width: calc(100% - var(--sidebar-w, 280px)); }
        }

        @media (max-width: 1400px) {
            .summary-grid { grid-template-columns:repeat(3, minmax(0,1fr)); }
            .filters-grid { grid-template-columns:repeat(3, minmax(0,1fr)); }
        }

        @media (max-width: 992px) {
            .mitigation-banner, .panel-head, .top-bar { flex-direction:column; align-items:flex-start; }
            .mitigation-banner__meta { width:100%; min-width:0; }
            .summary-grid, .filters-grid, .details-grid, .details-grid.two-col { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .filter-actions { grid-column:1 / -1; justify-content:flex-start; }
            .compact-draft-list { grid-template-columns:1fr; }
        }

        @media (max-width: 768px) {
            body, body.chief-dashboard-page, .mitigation-shell, .mitigation-main, .dashboard-container, .mitigation-wrap { overflow: visible !important; }
            .mitigation-main { margin-left: 0; width: 100%; }
            .dashboard-container { width: min(100%, 760px); padding: 10px 10px calc(env(safe-area-inset-bottom, 0px) + 92px); }
            .top-bar { display:none; }
            .workflow-nav { width:100%; margin-bottom:8px; border-radius:16px; padding:5px; }
            .workflow-nav__step { min-height:32px; padding:0 10px; font-size:.62rem; }
            .mobile-page-head { display:block; margin-bottom:6px; padding:1px 2px 0; }
            .mobile-page-head__eyebrow { color:#c2410c; font-size:.56rem; font-weight:900; letter-spacing:.07em; text-transform:uppercase; }
            .mobile-page-head h1 { margin:2px 0 0; color:#0f172a; font-size:.94rem; font-weight:900; line-height:1.2; }
            .mitigation-banner, .card, .panel { border-radius:20px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
            .mitigation-banner { padding:12px; gap:8px; margin-bottom:8px; border-left-width:3px; }
            .mitigation-banner__title { font-size:.94rem; line-height:1.16; }
            .mitigation-banner__copy { font-size:.68rem; line-height:1.32; margin-top:4px; }
            .mitigation-banner__meta { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:6px; min-width:0; }
            .mitigation-banner__meta-card { padding:7px 8px; border-radius:11px; }
            .mitigation-banner__meta-label { font-size:.5rem; }
            .mitigation-banner__meta-value { margin-top:2px; font-size:.64rem; line-height:1.28; }
            .summary-grid, .filters-grid, .details-grid, .details-grid.two-col { grid-template-columns:1fr; gap:8px; }
            .mitigation-stack { gap:8px; }
            .panel, .card { padding:10px; border-radius:15px; }
            .card-label { font-size:.58rem; }
            .card-value { margin-top:6px; font-size:1rem; }
            .card-copy { margin-top:3px; font-size:.62rem; line-height:1.28; }
            .panel-head { gap:7px; margin-bottom:8px; }
            .panel-head h3 { font-size:.82rem; }
            .panel-head p { font-size:.66rem; line-height:1.28; }
            .chip, .tag { min-height:22px; padding:0 8px; font-size:.58rem; }
            .table-wrap { overflow:visible; max-height:none; border:0; background:transparent; border-radius:0; padding:0; }
            table { min-width:0; width:100%; table-layout:auto; border-spacing:0; }
            thead { display:none; }
            tbody { display:grid; gap:7px; }
            tbody tr { display:block; border:1px solid rgba(226,232,240,.92); border-radius:14px; background:rgba(255,255,255,.98); box-shadow:0 8px 18px rgba(15,23,42,.035); overflow:hidden; }
            tbody tr:hover td { background:transparent; }
            td { display:grid; grid-template-columns:78px minmax(0, 1fr); gap:6px; width:100% !important; padding:7px 9px; font-size:.66rem; background:transparent; border-bottom:1px solid rgba(241,245,249,1); }
            td::before { content: attr(data-label); color:#94a3b8; font-size:.52rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; line-height:1.35; padding-top:2px; }
            td:last-child { border-bottom:0; }
            td[data-label="Actions"] { display:block; padding-top:6px; }
            td[data-label="Actions"]::before { display:none; }
            .row-actions { justify-content:flex-end; gap:5px; }
            .row-btn { width:30px; min-height:30px; font-size:.62rem; }
            .attachments a, .note, .details-item span, .details-item div { font-size:.68rem; }
            .mitigation-dialog { align-items:flex-end; padding:8px; }
            .mitigation-dialog__card, .mitigation-dialog__card.wide { width:100%; max-width:100%; max-height:min(88dvh, 760px); border-radius:22px 22px 16px 16px; padding:11px; }
            .mitigation-dialog__card.mitigation-form-dialog { width:100%; max-width:100%; max-height:92dvh; padding:0; border-radius:24px 24px 12px 12px; }
            .mitigation-dialog__card.mitigation-form-dialog::before { display:none; }
            .mitigation-form-dialog .mitigation-dialog__head { padding:14px 14px 12px; }
            .mitigation-dialog__title-icon { width:38px; height:38px; border-radius:12px; flex-basis:38px; }
            .mitigation-dialog__head h3 { font-size:.9rem; }
            .mitigation-dialog__head p { font-size:.68rem; line-height:1.35; }
            .mitigation-dialog__close { width:38px; height:38px; border-radius:12px; flex:0 0 38px; }
            .mitigation-form-scroll { padding:10px; }
            .mitigation-form-layout { gap:10px; }
            .mitigation-form-block { border-radius:16px; }
            .mitigation-form-block__head { grid-template-columns:auto minmax(0,1fr) auto; min-height:62px; gap:9px; padding:11px 12px; }
            .mitigation-form-block__icon { width:36px; height:36px; border-radius:11px; font-size:.86rem; }
            .mitigation-form-block__head h4 { font-size:.8rem; }
            .mitigation-form-block__head p { margin-top:2px; font-size:.63rem; line-height:1.35; }
            .mitigation-form-block__step { display:none; }
            .mitigation-form-block__grid { grid-template-columns:1fr; gap:13px; padding:13px 12px; }
            .mitigation-field input, .mitigation-field select, .mitigation-field textarea { min-height:46px; font-size:.78rem; border-radius:12px; }
            .mitigation-field label { font-size:.72rem; }
            .mitigation-field label em { font-size:.56rem; }
            .mitigation-form-dialog .form-actions { display:grid; grid-template-columns:1fr 1.45fr; gap:8px; padding:11px 12px calc(11px + env(safe-area-inset-bottom, 0px)); }
            .mitigation-form-dialog .form-actions .cancel-btn, .mitigation-form-dialog .form-actions .submit-btn { width:100%; min-height:44px; }
            .mitigation-dialog__card::before { content:""; display:block; width:52px; height:5px; border-radius:999px; background:#cbd5e1; margin:4px auto 10px; }
            .form-actions { flex-direction:column; align-items:stretch; }
            .hero-btn, .submit-btn, .cancel-btn, .filter-btn { width:100%; justify-content:center; min-height:38px; padding:0 12px; font-size:.72rem; }
            .compact-draft-card { grid-template-columns:1fr; gap:9px; padding:11px; }
            .compact-draft-card__actions { justify-content:flex-end; }
            .compact-action { width:34px !important; min-height:34px; padding:0; }
            .mitigation-ai-saved > div:last-child, .mitigation-ai-recommendations > div:last-child { grid-template-columns:1fr; }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @include('chief.partials.sidebar', ['active' => 'mitigations'])
    @php
        $summary = $mitigationSummaryCounts ?? ['total' => 0, 'awaiting_approval' => 0, 'in_progress' => 0, 'overdue' => 0, 'high_priority' => 0, 'completed' => 0];
        $filters = $filters ?? [];
        $hasActiveFilters = collect($filters)->filter(fn ($value) => filled($value) && $value !== false)->isNotEmpty();
    @endphp
    <div class="mitigation-shell">
        <main class="mitigation-main">
            <div class="dashboard-container chief-dashboard-container">
                <div class="mitigation-wrap chief-theme-shell">
                    <div class="top-bar">
                        <div>
                            <h1 style="margin:0; color:#1e293b; font-size:1.2rem; font-weight:900;">Mitigation Control</h1>
                            <p style="margin:4px 0 0; color:#64748b; font-size:.78rem; font-weight:700;">Lifecycle tracking from planning to evaluation. {{ now()->format('l, F j') }}</p>
                        </div>
                        <div class="user-dropdown">
                            <button type="button" class="user-dropdown-toggle" id="mitigationUserMenuToggle">
                                <span class="w-10 h-10 rounded-full bg-orange-500 text-white inline-flex items-center justify-center font-bold">
                                    {{ substr(auth()->user()->first_name ?? 'S', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                                </span>
                                <div style="text-align:left;">
                                    <div style="font-weight:800; color:#334155;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                                    <div style="font-size:.76rem; color:#64748b;">{{ auth()->user()->role->role ?? 'Station Admin' }}</div>
                                </div>
                                <i class="fa-solid fa-chevron-down" style="font-size:.75rem; color:#64748b;"></i>
                            </button>
                            <div class="user-dropdown-menu" id="mitigationUserMenu">
                                <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item"><i class="fa-solid fa-user"></i> Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <section class="mobile-page-head" aria-hidden="true">
                        <div class="mobile-page-head__eyebrow">Mitigation</div>
                        <h1>Mitigation Control</h1>
                    </section>

                    <nav class="workflow-nav" aria-label="Fire risk workflow">
                        <a class="workflow-nav__step" href="{{ route('station-admin.analytics') }}">
                            <i class="fa-solid fa-chart-line"></i><span>Fire Risk Forecast</span>
                        </a>
                        <i class="fa-solid fa-chevron-right workflow-nav__arrow" aria-hidden="true"></i>
                        <a class="workflow-nav__step" href="{{ route('station-admin.course-checklists') }}">
                            <i class="fa-solid fa-list-check"></i><span>AI Checklists</span>
                        </a>
                        <i class="fa-solid fa-chevron-right workflow-nav__arrow" aria-hidden="true"></i>
                        <span class="workflow-nav__step is-active" aria-current="page">
                            <i class="fa-solid fa-shield-halved"></i><span>Mitigation Control</span>
                        </span>
                    </nav>

                    <section class="mitigation-banner chief-theme-hero">
                        <div>
                            <span class="chief-theme-hero__eyebrow">
                                <i class="fa-solid fa-shield-halved"></i>
                                Mitigation Control
                            </span>
                            <h2 class="mitigation-banner__title chief-theme-hero__title">Turn forecasts into preventive action.</h2>
                            <p class="mitigation-banner__copy chief-theme-hero__copy">Review AI drafts, assign responsibility, track implementation, and evaluate results.</p>
                        </div>
                        <div class="mitigation-banner__meta chief-theme-summary-grid">
                            <div class="mitigation-banner__meta-card">
                                <div class="mitigation-banner__meta-label">Coverage</div>
                                <div class="mitigation-banner__meta-value">{{ $managedBarangays->count() }} barangays</div>
                            </div>
                            <div class="mitigation-banner__meta-card">
                                <div class="mitigation-banner__meta-label">Awaiting Approval</div>
                                <div class="mitigation-banner__meta-value">{{ $summary['awaiting_approval'] }}</div>
                            </div>
                            <div class="mitigation-banner__meta-card">
                                <div class="mitigation-banner__meta-label">In Progress</div>
                                <div class="mitigation-banner__meta-value">{{ $summary['in_progress'] }}</div>
                            </div>
                            <div class="mitigation-banner__meta-card">
                                <div class="mitigation-banner__meta-label">Overdue</div>
                                <div class="mitigation-banner__meta-value">{{ $summary['overdue'] }}</div>
                            </div>
                        </div>
                    </section>

                    <div class="mitigation-stack">
                        @if(!empty($mitigationDraft))
                            <section class="panel mitigation-draft-ready" style="border:1px solid rgba(249,115,22,.18); background:linear-gradient(135deg, rgba(255,247,237,.96), rgba(255,255,255,.98));">
                                <div class="panel-head">
                                    <div>
                                        <h3>Draft Ready</h3>
                                        <p>Prefilled from <strong>{{ $mitigationDraft['source_title'] ?? ($mitigationDraft['title'] ?? 'the selected checklist') }}</strong>.</p>
                                    </div>
                                    <button type="button" class="hero-btn" data-open-create-modal><i class="fa-solid fa-wand-magic-sparkles"></i><span>Open Draft</span></button>
                                </div>
                            </section>
                        @endif

                        <section class="panel mitigation-ai-saved" style="border:1px solid rgba(249,115,22,.18); background:linear-gradient(135deg, rgba(255,247,237,.96), rgba(255,255,255,.98));">
                            <div class="panel-head">
                                <div>
                                    <h3>AI Mitigation Drafts</h3>
                                    <p>Saved plans ready for assignment and review.</p>
                                </div>
                            </div>
                            @if(($newAiDraftMitigations ?? collect())->isEmpty())
                                <div class="empty">No new AI-linked draft mitigation records are waiting for review right now.</div>
                            @else
                                <div style="display:grid; gap:14px;">
                                    @foreach($newAiDraftMitigations as $action)
                                        @php
                                            $linkedChecklist = $action->aiActionPlanChecklist;
                                            $linkedSuggestion = optional($linkedChecklist?->suggestions)->sortByDesc('created_at')->first();
                                            $linkedAnalysis = $linkedChecklist?->analysis;
                                            $riskLevel = strtolower((string) ($linkedAnalysis?->risk_level ?? $linkedChecklist?->priority_level ?? $action->priority_level ?? 'medium'));
                                            $sourceLabel = $mitigationSourceOptions[$action->source_type] ?? ucfirst(str_replace('_', ' ', (string) $action->source_type));
                                            $categoryLabel = $mitigationCategoryOptions[$action->category] ?? ucfirst(str_replace('_', ' ', (string) $action->category));
                                            $draftTasks = collect($linkedChecklist?->items ?? [])->map(function ($item) {
                                                return [
                                                    'text' => $item->item_text,
                                                    'status' => $item->status,
                                                ];
                                            })->values()->all();
                                            $draftFactors = collect($linkedAnalysis?->contributing_factors ?? data_get($linkedChecklist, 'ai_payload.contributing_factors', []))
                                                ->flatten()
                                                ->filter()
                                                ->map(fn ($factor) => ucwords(str_replace('_', ' ', (string) $factor)))
                                                ->values()
                                                ->all();
                                            $draftSourceRefs = array_values(array_filter([
                                                $linkedChecklist?->plan_title ? 'AI Action Plan: ' . $linkedChecklist->plan_title : null,
                                                $linkedSuggestion?->recommendation_title ? 'AI Recommendation: ' . $linkedSuggestion->recommendation_title : null,
                                                $linkedAnalysis?->analysis_id ? 'Analysis #' . $linkedAnalysis->analysis_id : null,
                                                $action->riskArea?->marker?->title ? 'Hazard / Risk: ' . $action->riskArea->marker->title : null,
                                            ]));
                                            $draftPayload = [
                                                'id' => $action->mitigation_action_id,
                                                'barangay_id' => $action->barangay_id,
                                                'scope_type' => $action->scope_type,
                                                'title' => $action->title,
                                                'category' => $action->category,
                                                'category_label' => $categoryLabel,
                                                'priority_level' => $action->priority_level,
                                                'status' => $action->status,
                                                'status_label' => $mitigationStatusLabels[$action->status] ?? ucfirst(str_replace('_', ' ', (string) $action->status)),
                                                'source_type' => $action->source_type,
                                                'source_label' => $sourceLabel,
                                                'target_date' => optional($action->target_date)->format('Y-m-d'),
                                                'target_date_label' => $action->target_date?->format('M d, Y'),
                                                'completed_at_label' => optional($action->completed_at)->format('M d, Y h:i A'),
                                                'description' => $action->description,
                                                'remarks' => $action->remarks,
                                                'implementation_notes' => $action->implementation_notes,
                                                'completion_notes' => $action->completion_notes,
                                                'outcome_result' => $action->outcome_result,
                                                'effectiveness_key' => $action->effectiveness ?: 'pending_evaluation',
                                                'effectiveness' => $mitigationEffectivenessOptions[$action->effectiveness ?: 'pending_evaluation'] ?? 'Pending Evaluation',
                                                'follow_up_required' => (bool) $action->follow_up_required,
                                                'follow_up_notes' => $action->follow_up_notes,
                                                'risk_area_id' => $action->risk_area_id,
                                                'barangay_prediction_checklist_id' => $action->barangay_prediction_checklist_id,
                                                'ai_action_plan_checklist_id' => $action->ai_action_plan_checklist_id,
                                                'responsible_team_id' => $action->responsible_team_id,
                                                'responsible_user_id' => $action->responsible_user_id,
                                                'responsible_team_label' => $action->responsibleTeam?->team_name,
                                                'responsible_user_label' => $action->responsibleUser?->full_name,
                                                'barangay_label' => $action->barangay?->barangay_name,
                                                'location_label' => $action->riskArea?->marker?->title,
                                                'risk_level' => strtoupper($riskLevel),
                                                'recommendation' => $linkedChecklist?->source_description,
                                                'analysis_id' => $linkedAnalysis?->analysis_id,
                                                'analysis_label' => $linkedAnalysis ? ('Analysis #' . $linkedAnalysis->analysis_id . ' · ' . ($linkedAnalysis->barangay_scope ?: 'Station scope')) : null,
                                                'checklist_label' => $linkedChecklist?->plan_title,
                                                'prediction_checklist_label' => null,
                                                'source_references' => $draftSourceRefs,
                                                'factors' => $draftFactors,
                                                'tasks' => $draftTasks,
                                                'tasks_done' => $action->status === 'completed' ? count($draftTasks) : collect($draftTasks)->where('status', 'followed')->count(),
                                                'tasks_total' => count($draftTasks),
                                                'progress_percent' => 0,
                                                'attachments' => [],
                                                'approval_label' => $action->approver?->full_name,
                                                'approval_date_label' => optional($action->approved_at)->format('M d, Y h:i A'),
                                            ];
                                        @endphp
                                        <article class="card" style="padding:18px; border:1px solid rgba(226,232,240,.9);">
                                            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px;">
                                                <div style="min-width:0; flex:1 1 360px;">
                                                    <div class="chip-row" style="margin-bottom:10px;">
                                                        <span class="tag {{ $riskLevel === 'high' ? 'critical' : ($riskLevel === 'low' ? 'low' : 'medium') }}">{{ strtoupper($riskLevel) }} RISK</span>
                                                        <span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span>
                                                        <span class="tag ai_action_plan">{{ $sourceLabel }}</span>
                                                        <span class="tag draft">DRAFT</span>
                                                    </div>
                                                    <div class="title">{{ $action->title }}</div>
                                                    <div class="note">
                                                        {{ $action->barangay?->barangay_name ?? 'Unknown barangay' }}
                                                        @if($action->riskArea?->marker?->title)
                                                            · Linked marker: {{ $action->riskArea->marker->title }}
                                                        @endif
                                                        · Category: {{ $categoryLabel }}
                                                    </div>
                                                    <div class="note" style="margin-top:10px; white-space:pre-line;">{{ \Illuminate\Support\Str::limit($action->description, 360) }}</div>
                                                    @if($linkedSuggestion?->estimated_timeline)
                                                        <div class="note" style="margin-top:10px;"><strong>Suggested timeline:</strong> {{ $linkedSuggestion->estimated_timeline }}</div>
                                                    @endif
                                                    @if($action->target_date)
                                                        <div class="note" style="margin-top:6px;"><strong>Target date:</strong> {{ optional($action->target_date)->format('M d, Y') }}</div>
                                                    @endif
                                                </div>
                                                <div style="display:flex; flex-direction:column; gap:10px; min-width:220px;">
                                                    <button
                                                        type="button"
                                                        class="hero-btn"
                                                        style="width:100%; justify-content:center;"
                                                        data-open-details-modal
                                                        data-payload='@json($draftPayload)'>
                                                        <i class="fa-solid fa-eye"></i><span>Review Draft</span>
                                                    </button>
                                                    <a href="{{ route('station-admin.mitigations') }}#mitigation-row-{{ $action->mitigation_action_id }}" class="filter-btn secondary" style="width:100%; justify-content:center;">
                                                        <i class="fa-solid fa-table-list"></i><span>Locate in Register</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="panel mitigation-ai-recommendations">
                            <div class="panel-head">
                                <div>
                                    <h3>Checklist Suggestions</h3>
                                    <p>AI suggestions not yet added to the register.</p>
                                </div>
                            </div>
                            @if(($generatedMitigationDrafts ?? collect())->isEmpty())
                                <div class="empty">No pending system-generated mitigation drafts right now.</div>
                            @else
                                <div style="display:grid; gap:14px;">
                                    @foreach($generatedMitigationDrafts as $draftChecklist)
                                        @php
                                            $draftPayload = is_array($draftChecklist->ai_payload) ? $draftChecklist->ai_payload : [];
                                            $draftDrivers = collect($draftPayload['drivers'] ?? [])->take(3)->values();
                                            $draftFactors = collect($draftPayload['contributing_factors'] ?? [])->take(4)->map(fn ($factor) => ucwords(str_replace('_', ' ', (string) $factor)))->values();
                                            $riskLevel = strtolower((string) ($draftPayload['risk_level'] ?? $draftChecklist->priority_level ?? 'medium'));
                                            $categoryLabel = $mitigationCategoryOptions[\Illuminate\Support\Str::snake((string) ($draftPayload['recommended_category'] ?? 'other'))] ?? ucfirst(str_replace('_', ' ', (string) ($draftPayload['recommended_category'] ?? 'other')));
                                        @endphp
                                        <article class="card" style="padding:18px; border:1px solid rgba(226,232,240,.9);">
                                            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px;">
                                                <div style="min-width:0; flex:1 1 360px;">
                                                    <div class="chip-row" style="margin-bottom:10px;">
                                                        <span class="tag {{ $riskLevel === 'high' ? 'critical' : ($riskLevel === 'low' ? 'low' : 'medium') }}">{{ strtoupper($riskLevel) }} RISK</span>
                                                        <span class="tag {{ $draftChecklist->priority_level }}">{{ strtoupper($draftChecklist->priority_level) }}</span>
                                                        <span class="tag ai_action_plan">AI Action Plan</span>
                                                    </div>
                                                    <div class="title">{{ $draftChecklist->plan_title }}</div>
                                                    <div class="note">
                                                        {{ $draftChecklist->barangay?->barangay_name ?? 'Unknown barangay' }}
                                                        @if($draftChecklist->riskArea?->marker?->title)
                                                            · Linked marker: {{ $draftChecklist->riskArea->marker->title }}
                                                        @endif
                                                    </div>
                                                    <div class="note" style="margin-top:10px; white-space:pre-line;">{{ \Illuminate\Support\Str::limit($draftChecklist->plan_description, 380) }}</div>
                                                    @if($draftDrivers->isNotEmpty())
                                                        <div class="note" style="margin-top:10px;"><strong>Why generated:</strong> {{ $draftDrivers->implode(' | ') }}</div>
                                                    @endif
                                                    @if($draftFactors->isNotEmpty())
                                                        <div class="chip-row" style="margin-top:10px;">
                                                            @foreach($draftFactors as $factor)
                                                                <span class="chip"><i class="fa-solid fa-circle-exclamation"></i> {{ $factor }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                <div style="display:flex; flex-direction:column; gap:10px; min-width:220px;">
                                                    <form method="POST" action="{{ route('station-admin.mitigations.generated-drafts.approve', ['checklistId' => $draftChecklist->ai_action_plan_checklist_id]) }}">
                                                        @csrf
                                                        <button type="submit" class="hero-btn" style="width:100%; justify-content:center;"><i class="fa-solid fa-circle-check"></i><span>Approve</span></button>
                                                    </form>
                                                    <a href="{{ route('station-admin.mitigations', ['draft_ai_action_plan_checklist_id' => $draftChecklist->ai_action_plan_checklist_id]) }}" class="filter-btn secondary" style="width:100%; justify-content:center;">
                                                        <i class="fa-solid fa-pen"></i><span>Edit Draft</span>
                                                    </a>
                                                    <form method="POST" action="{{ route('station-admin.mitigations.generated-drafts.reject', ['checklistId' => $draftChecklist->ai_action_plan_checklist_id]) }}">
                                                        @csrf
                                                        <button type="submit" class="filter-btn secondary" style="width:100%; justify-content:center; color:#b91c1c; border-color:#fecaca; background:#fff5f5;"><i class="fa-solid fa-ban"></i><span>Reject</span></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="summary-grid mitigation-summary">
                            <article class="card"><div class="card-label">Total Mitigations</div><div class="card-value">{{ $summary['total'] }}</div><div class="card-copy">All official mitigation plans.</div></article>
                            <article class="card"><div class="card-label">Awaiting Approval</div><div class="card-value">{{ $summary['awaiting_approval'] }}</div><div class="card-copy">Draft and planned items.</div></article>
                            <article class="card"><div class="card-label">In Progress</div><div class="card-value">{{ $summary['in_progress'] }}</div><div class="card-copy">Approved and active work.</div></article>
                            <article class="card"><div class="card-label">Overdue</div><div class="card-value">{{ $summary['overdue'] }}</div><div class="card-copy">Past target date, not closed.</div></article>
                            <article class="card"><div class="card-label">High Priority</div><div class="card-value">{{ $summary['high_priority'] }}</div><div class="card-copy">High or critical severity.</div></article>
                            <article class="card"><div class="card-label">Completed</div><div class="card-value">{{ $summary['completed'] }}</div><div class="card-copy">Finished and logged.</div></article>
                        </section>

                        <section class="panel mitigation-register">
                            <div class="panel-head">
                                <div>
                                    <h3>Mitigation Register</h3>
                                    <p>Track, assign, and review prevention actions.</p>
                                </div>
                                <div class="chip-row">
                                    <span class="chip"><i class="fa-solid fa-file-lines"></i> 10 per page</span>
                                    <button type="button" class="hero-btn" data-open-create-modal><i class="fa-solid fa-plus"></i><span>Add Mitigation</span></button>
                                </div>
                            </div>

                            <details class="filter-disclosure" @if($hasActiveFilters) open @endif>
                                <summary><i class="fa-solid fa-sliders"></i> Filters @if($hasActiveFilters)<span class="chip">Active</span>@endif</summary>
                            <form id="mitigationFilters" method="GET" action="{{ route('station-admin.mitigations') }}" class="panel" style="padding:14px; box-shadow:none;">
                                <div class="filters-grid">
                                    <div class="mitigation-field mitigation-field--full">
                                        <label for="filterSearch">Search</label>
                                        <input id="filterSearch" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search title, plan, or risk location">
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterBarangay">Barangay</label>
                                        <select id="filterBarangay" name="barangay_id">
                                            <option value="">All barangays</option>
                                            @foreach($managedBarangays as $barangay)
                                                <option value="{{ $barangay->barangay_id }}" @selected((string) ($filters['barangay_id'] ?? '') === (string) $barangay->barangay_id)>{{ $barangay->barangay_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterCategory">Category</label>
                                        <select id="filterCategory" name="category">
                                            <option value="">All categories</option>
                                            @foreach($mitigationCategoryOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterPriority">Priority</label>
                                        <select id="filterPriority" name="priority_level">
                                            <option value="">All priorities</option>
                                            @foreach($mitigationPriorityOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(($filters['priority_level'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterStatus">Stage</label>
                                        <select id="filterStatus" name="status">
                                            <option value="">All stages</option>
                                            @foreach($mitigationStatusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterTeam">Responsible Team</label>
                                        <select id="filterTeam" name="responsible_team_id">
                                            <option value="">All teams</option>
                                            @foreach($stationTeams as $team)
                                                <option value="{{ $team->team_id }}" @selected((string) ($filters['responsible_team_id'] ?? '') === (string) $team->team_id)>{{ $team->team_name ?? ('Team #' . $team->team_id) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterSource">Source</label>
                                        <select id="filterSource" name="source_type">
                                            <option value="">All sources</option>
                                            @foreach($mitigationSourceOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(($filters['source_type'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mitigation-field">
                                        <label for="filterRevisionReview">Revision Review</label>
                                        <select id="filterRevisionReview" name="revision_review">
                                            <option value="0">All records</option>
                                            <option value="1" @selected(!empty($filters['revision_review']))>Needs review</option>
                                        </select>
                                    </div>
                                    <div class="filter-actions mitigation-field mitigation-field--full">
                                        <button type="submit" class="filter-btn primary"><i class="fa-solid fa-filter"></i><span>Apply Filters</span></button>
                                        <a href="{{ route('station-admin.mitigations') }}" class="filter-btn secondary"><i class="fa-solid fa-rotate-left"></i><span>Reset</span></a>
                                    </div>
                                </div>
                            </form>
                            </details>

                            <div id="mitigationTableData" aria-live="polite">
                            @if($mitigationActions->isEmpty())
                                <div class="empty"><i class="fa-solid fa-filter-circle-xmark"></i> No matching mitigation plans.</div>
                            @else
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Plan</th>
                                                <th>Location</th>
                                                <th>Priority / Stage</th>
                                                <th>Owner / Target</th>
                                                <th>Source</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($mitigationActions as $action)
                                                @php
                                                    $sourceType = $action->source_type ?: 'manual';
                                                    $sourceLabel = $mitigationSourceOptions[$sourceType] ?? ucfirst(str_replace('_', ' ', $sourceType));
                                                    $statusLabel = $mitigationStatusLabels[$action->status] ?? ucfirst(str_replace('_', ' ', $action->status));
                                                    $categoryLabel = $mitigationCategoryOptions[$action->category] ?? ucfirst(str_replace('_', ' ', $action->category));
                                                    $analysis = $action->aiActionPlanChecklist?->analysis;
                                                    $suggestion = $action->aiActionPlanChecklist?->suggestions?->sortByDesc('created_at')->first();
                                                    $taskItems = collect($action->aiActionPlanChecklist?->items ?? []);
                                                    $totalTasks = $taskItems->count();
                                                    $completedTasks = $action->status === 'completed' ? $totalTasks : $taskItems->where('status', 'followed')->count();
                                                    $progressPercent = match ($action->status) {
                                                        'draft' => 0,
                                                        'planned' => 15,
                                                        'approved' => 30,
                                                        'in_progress' => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 55,
                                                        'completed' => 100,
                                                        'cancelled' => 0,
                                                        'blocked' => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 40,
                                                        default => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 0,
                                                    };
                                                    $riskLevel = strtolower((string) ($analysis->risk_level ?? data_get($action->aiActionPlanChecklist, 'ai_payload.risk_level') ?? $action->priority_level));
                                                    $factors = collect($analysis->contributing_factors ?? data_get($action->aiActionPlanChecklist, 'ai_payload.contributing_factors', []))
                                                        ->flatten()
                                                        ->filter()
                                                        ->map(fn ($factor) => ucwords(str_replace('_', ' ', (string) $factor)))
                                                        ->values()
                                                        ->all();
                                                    $recommendationText = $suggestion?->recommendation_description
                                                        ?: (is_array($analysis?->recommendations) ? collect($analysis->recommendations)->flatten()->filter()->first() : $analysis?->recommendations);
                                                    $sourceRefs = array_values(array_filter([
                                                        $action->aiActionPlanChecklist?->plan_title ? 'AI Action Plan: ' . $action->aiActionPlanChecklist->plan_title : null,
                                                        $suggestion?->recommendation_title ? 'AI Recommendation: ' . $suggestion->recommendation_title : null,
                                                        $analysis?->analysis_id ? 'Analysis #' . $analysis->analysis_id : null,
                                                        $action->checklist?->title ? 'Prediction Checklist: ' . $action->checklist->title : null,
                                                        $action->riskArea?->marker?->title ? 'Hazard / Risk: ' . $action->riskArea->marker->title : null,
                                                    ]));
                                                    $tasks = $taskItems->map(function ($item) {
                                                        return [
                                                            'text' => $item->item_text,
                                                            'status' => $item->status,
                                                        ];
                                                    })->values()->all();
                                                    $attachments = $action->attachments->map(function ($attachment) {
                                                        return [
                                                            'name' => $attachment->file_name ?: 'Attachment',
                                                            'url' => \App\Support\MediaPath::url($attachment->file_path),
                                                            'type' => $attachment->attachment_type ?: 'proof',
                                                        ];
                                                    })->values()->all();
                                                    $payload = [
                                                        'id' => $action->mitigation_action_id,
                                                        'barangay_id' => $action->barangay_id,
                                                        'scope_type' => $action->scope_type,
                                                        'scope_label' => $action->scope_type === 'citywide' ? 'Citywide' : ($action->scope_type === 'stationwide' ? 'Stationwide' : 'Barangay'),
                                                        'title' => $action->title,
                                                        'category' => $action->category,
                                                        'category_label' => $categoryLabel,
                                                        'priority_level' => $action->priority_level,
                                                        'status' => $action->status,
                                                        'status_label' => $statusLabel,
                                                        'source_type' => $sourceType,
                                                        'source_label' => $sourceLabel,
                                                        'target_date' => optional($action->target_date)->format('Y-m-d'),
                                                        'target_date_label' => $action->target_date?->format('M d, Y'),
                                                        'completed_at_label' => optional($action->completed_at)->format('M d, Y h:i A'),
                                                        'description' => $action->description,
                                                        'remarks' => $action->remarks,
                                                        'implementation_notes' => $action->implementation_notes,
                                                        'completion_notes' => $action->completion_notes,
                                                        'outcome_result' => $action->outcome_result,
                                                        'effectiveness_key' => $action->effectiveness ?: 'pending_evaluation',
                                                        'effectiveness' => $mitigationEffectivenessOptions[$action->effectiveness ?: 'pending_evaluation'] ?? 'Pending Evaluation',
                                                        'follow_up_required' => (bool) $action->follow_up_required,
                                                        'follow_up_notes' => $action->follow_up_notes,
                                                        'risk_area_id' => $action->risk_area_id,
                                                        'barangay_prediction_checklist_id' => $action->barangay_prediction_checklist_id,
                                                        'ai_action_plan_checklist_id' => $action->ai_action_plan_checklist_id,
                                                        'responsible_team_id' => $action->responsible_team_id,
                                                        'responsible_user_id' => $action->responsible_user_id,
                                                        'responsible_team_label' => $action->responsibleTeam?->team_name,
                                                        'responsible_user_label' => $action->responsibleUser?->full_name,
                                                        'barangay_label' => $action->barangay?->barangay_name,
                                                        'location_label' => $action->riskArea?->marker?->title,
                                                        'risk_level' => strtoupper($riskLevel),
                                                        'recommendation' => $recommendationText,
                                                        'analysis_id' => $analysis?->analysis_id,
                                                        'analysis_label' => $analysis ? ('Analysis #' . $analysis->analysis_id . ' · ' . ($analysis->barangay_scope ?: 'Station scope')) : null,
                                                        'checklist_label' => $action->aiActionPlanChecklist?->plan_title,
                                                        'prediction_checklist_label' => $action->checklist?->title,
                                                        'source_references' => $sourceRefs,
                                                        'factors' => $factors,
                                                        'tasks' => $tasks,
                                                        'tasks_done' => $completedTasks,
                                                        'tasks_total' => $totalTasks,
                                                        'progress_percent' => $progressPercent,
                                                        'attachments' => $attachments,
                                                        'approval_label' => $action->approver?->full_name,
                                                        'approval_date_label' => optional($action->approved_at)->format('M d, Y h:i A'),
                                                        'requires_revision_review' => (bool) $action->requires_revision_review,
                                                        'analysis_revision' => $action->analysis_revision,
                                                    ];
                                                    $isOverdue = $action->target_date && $action->target_date->lt(today()) && ! in_array($action->status, ['completed', 'cancelled'], true);
                                                @endphp
                                                <tr id="mitigation-row-{{ $action->mitigation_action_id }}">
                                                    <td data-label="Plan">
                                                        <div class="title">{{ $action->title }}</div>
                                                        <div class="chip-row" style="margin-top:7px;">
                                                            <span class="chip">{{ $categoryLabel }}</span>
                                                            @if($isOverdue)
                                                                <span class="chip overdue"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</span>
                                                            @endif
                                                            @if($action->requires_revision_review)
                                                                <span class="chip overdue"><i class="fa-solid fa-code-compare"></i> Review revision {{ $action->analysis_revision }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td data-label="Location">
                                                        <div class="title" style="font-size:.86rem;">{{ $action->scope_type === 'citywide' ? (($action->barangay?->city?->city_name ?? 'City') . ' · Citywide') : ($action->scope_type === 'stationwide' ? 'Stationwide coverage' : ($action->barangay?->barangay_name ?? 'Unknown barangay')) }}</div>
                                                        <div class="note">{{ $action->riskArea?->marker?->title ?? 'No linked location marker' }}</div>
                                                    </td>
                                                    <td data-label="Priority / Stage"><div class="chip-row"><span class="tag {{ $action->priority_level }}">{{ strtoupper($action->priority_level) }}</span><span class="tag {{ $action->status }}">{{ strtoupper($statusLabel) }}</span>@if($action->completion_approval_status === 'pending')<span class="chip overdue"><i class="fa-solid fa-user-check"></i> Completion review</span>@endif</div></td>
                                                    <td data-label="Owner / Target">
                                                        <div class="title" style="font-size:.82rem;">{{ $action->responsibleUser?->full_name ?? 'Unassigned person' }}</div>
                                                        <div class="note"><i class="fa-regular fa-calendar"></i> {{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</div>
                                                    </td>
                                                    <td data-label="Source">
                                                        <div class="chip-row">
                                                            <span class="tag {{ $sourceType }}">{{ $sourceLabel }}</span>
                                                            @if($action->aiActionPlanChecklist)
                                                                <span class="chip"><i class="fa-solid fa-list-check"></i> Linked checklist</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td data-label="Actions">
                                                        <div class="row-actions">
                                                            <button type="button" class="row-btn view" title="View details" aria-label="View details" data-open-details-modal data-payload='@json($payload)'><i class="fa-solid fa-eye"></i></button>
                                                            <button type="button" class="row-btn edit" title="Edit action" aria-label="Edit action" data-open-edit-modal data-payload='@json($payload)'><i class="fa-solid fa-pen"></i></button>
                                                            @if($action->completion_approval_status === 'pending')
                                                                <form method="POST" action="{{ route('station-admin.mitigations.completion-review', $action->getKey()) }}" style="display:inline">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="row-btn view" title="Approve completion" aria-label="Approve completion"><i class="fa-solid fa-check"></i></button></form>
                                                                <form method="POST" action="{{ route('station-admin.mitigations.completion-review', $action->getKey()) }}" style="display:inline">@csrf<input type="hidden" name="decision" value="rejected"><button type="submit" class="row-btn delete" title="Return for further work" aria-label="Return for further work"><i class="fa-solid fa-rotate-left"></i></button></form>
                                                            @endif
                                                            <button type="button" class="row-btn delete" title="Delete action" aria-label="Delete action" data-open-delete-modal data-id="{{ $action->mitigation_action_id }}" data-title="{{ $action->title }}"><i class="fa-solid fa-trash"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="table-pagination">
                                    <span>Showing {{ $mitigationActions->firstItem() }}–{{ $mitigationActions->lastItem() }} of {{ $mitigationActions->total() }}</span>
                                    {{ $mitigationActions->onEachSide(1)->links() }}
                                </div>
                            @endif
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="mitigation-dialog" id="detailsModal" aria-hidden="true">
        <div class="mitigation-dialog__card wide">
            <div class="mitigation-dialog__head">
                <div>
                    <h3 id="detailsTitle">Mitigation Details</h3>
                    <p id="detailsSubtitle">Lifecycle, traceability, implementation, and evaluation context.</p>
                </div>
                <button type="button" class="mitigation-dialog__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="details-stack" id="detailsContent"></div>
        </div>
    </div>

    <div class="mitigation-dialog" id="createModal" aria-hidden="true">
        <div class="mitigation-dialog__card mitigation-form-dialog" role="dialog" aria-modal="true" aria-labelledby="createMitigationTitle">
            <div class="mitigation-dialog__head">
                <div class="mitigation-dialog__title"><span class="mitigation-dialog__title-icon"><i class="fa-solid fa-shield-halved"></i></span><div><h3 id="createMitigationTitle">Create mitigation plan</h3><p>Start with the plan and owner. Delivery details can be completed as work progresses.</p></div></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal aria-label="Close create mitigation"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('station-admin.mitigations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mitigation-form-scroll"><div class="mitigation-form-layout">@include('chief.partials.mitigation-form-fields', ['fieldPrefix' => 'createMitigation', 'formMode' => 'create'])</div></div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" data-close-modal><i class="fa-solid fa-xmark"></i><span>Cancel</span></button>
                    <button type="submit" class="submit-btn"><i class="fa-solid fa-floppy-disk"></i><span>Save mitigation</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mitigation-dialog" id="editModal" aria-hidden="true">
        <div class="mitigation-dialog__card mitigation-form-dialog" role="dialog" aria-modal="true" aria-labelledby="editMitigationTitle">
            <div class="mitigation-dialog__head">
                <div class="mitigation-dialog__title"><span class="mitigation-dialog__title-icon"><i class="fa-solid fa-pen-to-square"></i></span><div><h3 id="editMitigationTitle">Edit mitigation plan</h3><p>Review ownership, update delivery progress, and record the outcome in one workspace.</p></div></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal aria-label="Close edit mitigation"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="editForm" action="{{ route('station-admin.mitigations.update', ['mitigationActionId' => 0]) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mitigation-form-scroll"><div class="mitigation-form-layout">@include('chief.partials.mitigation-form-fields', ['fieldPrefix' => 'editMitigation', 'formMode' => 'edit'])</div></div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" data-close-modal><i class="fa-solid fa-xmark"></i><span>Cancel</span></button>
                    <button type="submit" class="submit-btn"><i class="fa-solid fa-floppy-disk"></i><span>Update mitigation</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mitigation-dialog" id="deleteModal" aria-hidden="true">
        <div class="mitigation-dialog__card">
            <div class="mitigation-dialog__head">
                <div><h3>Delete Mitigation</h3><p id="deleteMessage">This mitigation and its proof files will be removed.</p></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="deleteForm" action="{{ route('station-admin.mitigations.destroy', ['mitigationActionId' => 0]) }}">
                @csrf
                @method('DELETE')
                <div class="form-actions" style="margin-top:0;">
                    <button type="button" class="cancel-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="hero-btn" style="background:#dc2626; box-shadow:none;"><i class="fa-solid fa-trash"></i><span>Delete</span></button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.user-activity-logger')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const detailsModal = document.getElementById('detailsModal');
            const editForm = document.getElementById('editForm');
            const deleteForm = document.getElementById('deleteForm');
            const deleteMessage = document.getElementById('deleteMessage');
            const detailsTitle = document.getElementById('detailsTitle');
            const detailsSubtitle = document.getElementById('detailsSubtitle');
            const detailsContent = document.getElementById('detailsContent');
            const updateBaseUrl = @json(route('station-admin.mitigations.update', ['mitigationActionId' => '__ID__']));
            const deleteBaseUrl = @json(route('station-admin.mitigations.destroy', ['mitigationActionId' => '__ID__']));
            const menuToggle = document.getElementById('mitigationUserMenuToggle');
            const menu = document.getElementById('mitigationUserMenu');

            const captureOptions = (select) => Array.from(select?.options || []).map((option) => ({
                value: option.value,
                text: option.text,
                barangayId: option.dataset.barangayId || '',
            }));

            const rebuildSelectOptions = (select, originalOptions, barangayId, selectedValue = '') => {
                if (!select) return;
                select.innerHTML = '';
                originalOptions
                    .filter((option) => option.value === '' || option.barangayId === '' || !barangayId || option.barangayId === barangayId)
                    .forEach((option) => {
                        const element = document.createElement('option');
                        element.value = option.value;
                        element.textContent = option.text;
                        if (option.barangayId) {
                            element.dataset.barangayId = option.barangayId;
                        }
                        if (String(option.value) === String(selectedValue)) {
                            element.selected = true;
                        }
                        select.appendChild(element);
                    });
            };

            const wireBarangayScopedSelectors = (form) => {
                if (!form) return;
                const barangaySelect = form.querySelector('[name="barangay_id"]');
                const riskSelect = form.querySelector('[name="risk_area_id"]');
                const checklistSelect = form.querySelector('[name="barangay_prediction_checklist_id"]');
                const aiSelect = form.querySelector('[name="ai_action_plan_checklist_id"]');
                const riskOptions = captureOptions(riskSelect);
                const checklistOptions = captureOptions(checklistSelect);
                const aiOptions = captureOptions(aiSelect);

                const sync = () => {
                    const barangayId = barangaySelect?.value || '';
                    rebuildSelectOptions(riskSelect, riskOptions, barangayId, riskSelect?.value || '');
                    rebuildSelectOptions(checklistSelect, checklistOptions, barangayId, checklistSelect?.value || '');
                    rebuildSelectOptions(aiSelect, aiOptions, barangayId, aiSelect?.value || '');
                };

                barangaySelect?.addEventListener('change', sync);
                sync();
            };

            const closeModal = (modal) => {
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            const openModal = (modal) => {
                if (!modal) return;
                const scrollRegion = modal.querySelector('.mitigation-form-scroll') || modal.querySelector('.mitigation-dialog__card');
                if (scrollRegion) scrollRegion.scrollTop = 0;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                window.requestAnimationFrame(() => {
                    const firstField = modal.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button[data-close-modal]');
                    firstField?.focus({ preventScroll: true });
                });
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const renderBadge = (label, className) => `<span class="tag ${escapeHtml(className)}">${escapeHtml(label)}</span>`;

            const renderList = (items, emptyText) => {
                if (!Array.isArray(items) || !items.length) {
                    return `<div class="note">${escapeHtml(emptyText)}</div>`;
                }

                return `<div class="plain-list">${items.map((item) => `<div class="chip">${escapeHtml(item)}</div>`).join('')}</div>`;
            };

            const renderTasks = (tasks, done, total) => {
                if (!Array.isArray(tasks) || !tasks.length) {
                    return `<div class="note">No linked AI checklist tasks for this mitigation yet.</div>`;
                }

                return `
                    <div class="progress-wrap" style="margin-bottom:12px;">
                        <div class="progress-meta">
                            <span>${escapeHtml(done)} of ${escapeHtml(total)} tasks complete</span>
                            <span>${total > 0 ? Math.round((done / total) * 100) : 0}%</span>
                        </div>
                        <div class="progress-bar"><span style="width:${total > 0 ? Math.round((done / total) * 100) : 0}%"></span></div>
                    </div>
                    <div class="task-list">
                        ${tasks.map((task) => {
                            const icon = task.status === 'followed' ? 'fa-circle-check' : (task.status === 'not_followed' ? 'fa-ban' : 'fa-clock');
                            const label = task.status === 'followed' ? 'Completed' : (task.status === 'not_followed' ? 'Not Followed' : 'Pending');
                            return `
                                <div class="task-item">
                                    <i class="fa-solid ${icon}"></i>
                                    <div>
                                        <div class="title" style="font-size:.84rem;">${escapeHtml(task.text)}</div>
                                        <div class="note">${escapeHtml(label)}</div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            };

            const renderAttachments = (attachments) => {
                if (!Array.isArray(attachments) || !attachments.length) {
                    return `<div class="note">No photos or files uploaded yet.</div>`;
                }

                return `<div class="attachments">${attachments.map((attachment) => `<a href="${escapeHtml(attachment.url)}" target="_blank" rel="noopener">${escapeHtml(attachment.name)}</a>`).join('')}</div>`;
            };

            const renderDetails = (payload) => {
                detailsTitle.textContent = payload.title || 'Mitigation Details';
                detailsSubtitle.textContent = `${payload.scope_label || 'Barangay'} · ${payload.barangay_label || 'Unknown barangay'}${payload.location_label ? ' · ' + payload.location_label : ''}`;

                const followUpText = payload.follow_up_required ? 'Yes' : 'No';

                detailsContent.innerHTML = `
                    <section class="details-section">
                        <h4>1. Why this mitigation exists</h4>
                        <div class="details-grid two-col">
                            <div class="details-item">
                                <div class="section-label">Risk Level</div>
                                <div>${renderBadge(payload.risk_level || 'UNKNOWN', (payload.priority_level || 'medium'))}</div>
                            </div>
                            <div class="details-item">
                                <div class="section-label">Source</div>
                                <div>${renderBadge(payload.source_label || 'Manual', payload.source_type || 'manual')}</div>
                            </div>
                            <div class="details-item details-card--full">
                                <div class="section-label">Contributing / Risk Factors</div>
                                ${renderList(payload.factors, 'No contributing factors were attached to this mitigation.')}
                            </div>
                            <div class="details-item details-card--full">
                                <div class="section-label">Recommendation</div>
                                <div>${escapeHtml(payload.recommendation || 'No linked recommendation text recorded.')}</div>
                            </div>
                            <div class="details-item">
                                <div class="section-label">Linked Prediction / Analysis</div>
                                <div>${escapeHtml(payload.analysis_label || 'No linked analysis')}</div>
                            </div>
                            <div class="details-item">
                                <div class="section-label">Linked AI Action Plan Checklist</div>
                                <div>${escapeHtml(payload.checklist_label || 'No linked AI action plan')}</div>
                            </div>
                            <div class="details-item details-card--full">
                                <div class="section-label">Traceability</div>
                                ${renderList(payload.source_references, 'No traceability references attached.')}
                            </div>
                        </div>
                    </section>
                    <section class="details-section">
                        <h4>2. Planning</h4>
                        <div class="details-grid two-col">
                            <div class="details-item"><div class="section-label">Category</div><div>${escapeHtml(payload.category_label || 'Other')}</div></div>
                            <div class="details-item"><div class="section-label">Priority</div><div>${renderBadge((payload.priority_level || 'medium').toUpperCase(), payload.priority_level || 'medium')}</div></div>
                            <div class="details-item details-card--full"><div class="section-label">Objective</div><div>${escapeHtml(payload.description || 'No mitigation objective recorded.')}</div></div>
                            <div class="details-item"><div class="section-label">Responsible Team</div><div>${escapeHtml(payload.responsible_team_label || 'Not assigned')}</div></div>
                            <div class="details-item"><div class="section-label">Responsible Person</div><div>${escapeHtml(payload.responsible_user_label || 'Not assigned')}</div></div>
                            <div class="details-item"><div class="section-label">Target Date</div><div>${escapeHtml(payload.target_date_label || 'No target date')}</div></div>
                            <div class="details-item"><div class="section-label">Approval</div><div>${escapeHtml(payload.approval_label || 'Not approved yet')}</div></div>
                            <div class="details-item"><div class="section-label">Approved At</div><div>${escapeHtml(payload.approval_date_label || 'Pending')}</div></div>
                            <div class="details-item details-card--full"><div class="section-label">Planning Remarks</div><div>${escapeHtml(payload.remarks || 'No planning remarks recorded.')}</div></div>
                        </div>
                    </section>
                    <section class="details-section">
                        <h4>3. Implementation</h4>
                        <div class="details-grid two-col">
                            <div class="details-item"><div class="section-label">Stage</div><div>${renderBadge(payload.status_label || 'Draft', payload.status || 'draft')}</div></div>
                            <div class="details-item"><div class="section-label">Progress</div><div>${escapeHtml(payload.progress_percent || 0)}%</div></div>
                            <div class="details-item details-card--full">
                                <div class="section-label">Checklist / Tasks</div>
                                ${renderTasks(payload.tasks, payload.tasks_done || 0, payload.tasks_total || 0)}
                            </div>
                            <div class="details-item details-card--full"><div class="section-label">Implementation Remarks / Updates</div><div>${escapeHtml(payload.implementation_notes || 'No implementation updates recorded yet.')}</div></div>
                        </div>
                    </section>
                    <section class="details-section">
                        <h4>4. Evidence / Documentation</h4>
                        <div class="details-grid two-col">
                            <div class="details-item details-card--full">
                                <div class="section-label">Photos / Files</div>
                                ${renderAttachments(payload.attachments)}
                            </div>
                            <div class="details-item details-card--full"><div class="section-label">Completion Notes</div><div>${escapeHtml(payload.completion_notes || 'No completion notes recorded yet.')}</div></div>
                        </div>
                    </section>
                    <section class="details-section">
                        <h4>5. Outcome / Evaluation</h4>
                        <div class="details-grid two-col">
                            <div class="details-item details-card--full"><div class="section-label">Outcome / Result</div><div>${escapeHtml(payload.outcome_result || 'No outcome or result recorded yet.')}</div></div>
                            <div class="details-item"><div class="section-label">Effectiveness</div><div>${escapeHtml(payload.effectiveness || 'Pending Evaluation')}</div></div>
                            <div class="details-item"><div class="section-label">Follow-up Required</div><div>${escapeHtml(followUpText)}</div></div>
                            <div class="details-item details-card--full"><div class="section-label">Follow-up Notes</div><div>${escapeHtml(payload.follow_up_notes || 'No follow-up notes recorded yet.')}</div></div>
                        </div>
                    </section>
                `;
            };

            wireBarangayScopedSelectors(createModal?.querySelector('form'));
            wireBarangayScopedSelectors(editForm);

            document.querySelectorAll('[data-open-create-modal]').forEach((button) => {
                button.addEventListener('click', () => openModal(createModal));
            });

            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(createModal);
                    closeModal(editModal);
                    closeModal(deleteModal);
                    closeModal(detailsModal);
                });
            });

            [createModal, editModal, deleteModal, detailsModal].forEach((modal) => modal?.addEventListener('click', (event) => {
                if (event.target === modal) closeModal(modal);
            }));

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                [createModal, editModal, deleteModal, detailsModal].forEach((modal) => {
                    if (modal?.classList.contains('is-open')) closeModal(modal);
                });
            });

            const bindMitigationTableActions = () => {
            document.querySelectorAll('#mitigationTableData [data-open-edit-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const payload = JSON.parse(button.getAttribute('data-payload') || '{}');
                    if (!editForm || !payload.id) return;
                    editForm.action = updateBaseUrl.replace('__ID__', String(payload.id));
                    const set = (name, value, fallback = '') => {
                        const field = editForm.querySelector(`[name="${name}"]`);
                        if (field) field.value = value ?? fallback;
                    };
                    set('barangay_id', payload.barangay_id);
                    set('scope_type', payload.scope_type, 'barangay');
                    set('title', payload.title);
                    set('category', payload.category, 'other');
                    set('priority_level', payload.priority_level, 'medium');
                    set('status', payload.status, 'draft');
                    set('source_type', payload.source_type, 'manual');
                    set('target_date', payload.target_date);
                    set('description', payload.description);
                    set('remarks', payload.remarks);
                    set('implementation_notes', payload.implementation_notes);
                    set('completion_notes', payload.completion_notes);
                    set('outcome_result', payload.outcome_result);
                    set('effectiveness', payload.effectiveness_key, 'pending_evaluation');
                    set('follow_up_required', payload.follow_up_required ? '1' : '0');
                    set('follow_up_notes', payload.follow_up_notes);
                    set('responsible_team_id', payload.responsible_team_id);
                    set('responsible_user_id', payload.responsible_user_id);
                    set('risk_area_id', payload.risk_area_id);
                    set('barangay_prediction_checklist_id', payload.barangay_prediction_checklist_id);
                    set('ai_action_plan_checklist_id', payload.ai_action_plan_checklist_id);
                    editForm.querySelector('[name="barangay_id"]')?.dispatchEvent(new Event('change'));
                    set('risk_area_id', payload.risk_area_id);
                    set('barangay_prediction_checklist_id', payload.barangay_prediction_checklist_id);
                    set('ai_action_plan_checklist_id', payload.ai_action_plan_checklist_id);
                    openModal(editModal);
                });
            });

            document.querySelectorAll('#mitigationTableData [data-open-delete-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-id');
                    const title = button.getAttribute('data-title') || 'this mitigation plan';
                    if (!deleteForm || !id) return;
                    deleteForm.action = deleteBaseUrl.replace('__ID__', String(id));
                    deleteMessage.textContent = `Delete "${title}" and all uploaded proof files? This cannot be undone.`;
                    openModal(deleteModal);
                });
            });

            document.querySelectorAll('[data-open-details-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    try {
                        const payload = JSON.parse(button.getAttribute('data-payload') || '{}');
                        renderDetails(payload);
                        openModal(detailsModal);
                    } catch (error) {
                        console.error('Unable to open mitigation draft details.', error);
                        detailsTitle.textContent = 'Draft unavailable';
                        detailsSubtitle.textContent = 'The saved draft data could not be read.';
                        detailsContent.innerHTML = '<div class="empty"><i class="fa-solid fa-triangle-exclamation"></i> Refresh the page and try reviewing this draft again.</div>';
                        openModal(detailsModal);
                    }
                });
            });
            };

            const loadMitigationTable = async (url) => {
                const table = document.getElementById('mitigationTableData');
                if (!table) return;
                table.setAttribute('aria-busy', 'true');
                table.style.opacity = '.55';
                try {
                    const requestUrl = new URL(url, window.location.origin);
                    requestUrl.pathname = '{{ route('station-admin.mitigations.table', [], false) }}';
                    const response = await fetch(requestUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error('Unable to load mitigation records.');
                    const result = await response.json();
                    const rows = Array.isArray(result.data) ? result.data : [];
                    const meta = result.meta || {};
                    const pageUrl = new URL(url, window.location.origin);
                    const pageLink = (page) => { const link = new URL(pageUrl); link.searchParams.set('page', page); return link.href; };
                    table.innerHTML = rows.length ? `
                        <div class="table-wrap"><table><thead><tr><th>Plan</th><th>Location</th><th>Priority / Stage</th><th>Owner / Target</th><th>Source</th><th>Open</th></tr></thead><tbody>
                        ${rows.map((item) => `<tr id="mitigation-row-${escapeHtml(item.id)}"><td data-label="Plan"><div class="title">${escapeHtml(item.title || 'Untitled')}</div><div class="chip-row" style="margin-top:7px;"><span class="chip">${escapeHtml(item.category || 'other')}</span>${item.requires_revision_review ? `<span class="chip overdue"><i class="fa-solid fa-code-compare"></i> Revision ${escapeHtml(item.analysis_revision || '')}</span>` : ''}</div></td><td data-label="Location"><div class="title" style="font-size:.86rem;">${escapeHtml(item.barangay || 'Unknown barangay')}</div><div class="note">${escapeHtml(item.location || 'No linked location')}</div></td><td data-label="Priority / Stage"><div class="chip-row"><span class="tag ${escapeHtml(item.priority || 'medium')}">${escapeHtml(item.priority || 'medium')}</span><span class="tag ${escapeHtml(item.status || 'draft')}">${escapeHtml(item.status || 'draft')}</span></div></td><td data-label="Owner / Target"><div class="title" style="font-size:.82rem;">${escapeHtml(item.responsible || 'Unassigned')}</div><div class="note"><i class="fa-regular fa-calendar"></i> ${escapeHtml(item.target_date || 'No target date')}</div></td><td data-label="Source"><span class="tag ${escapeHtml(item.source || 'manual')}">${escapeHtml(item.source || 'manual')}</span></td><td data-label="Open"><a class="row-btn view" href="{{ route('station-admin.mitigations') }}?mitigation_action_id=${encodeURIComponent(item.id)}#mitigation-row-${encodeURIComponent(item.id)}" title="Open mitigation" aria-label="Open mitigation"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td></tr>`).join('')}
                        </tbody></table></div>
                        <div class="table-pagination"><span>${escapeHtml(String(meta.total || 0))} record(s)</span><nav>${meta.current_page > 1 ? `<a href="${escapeHtml(pageLink(meta.current_page - 1))}"><i class="fa-solid fa-chevron-left"></i></a>` : ''}<span>${escapeHtml(String(meta.current_page || 1))} / ${escapeHtml(String(meta.last_page || 1))}</span>${meta.current_page < meta.last_page ? `<a href="${escapeHtml(pageLink(meta.current_page + 1))}"><i class="fa-solid fa-chevron-right"></i></a>` : ''}</nav></div>` : '<div class="empty"><i class="fa-solid fa-filter-circle-xmark"></i> No matching mitigation plans.</div>';
                    table.style.opacity = '1';
                    table.setAttribute('aria-busy', 'false');
                    window.history.replaceState({}, '', url);
                } catch (error) {
                    console.error(error);
                    table.style.opacity = '1';
                    table.setAttribute('aria-busy', 'false');
                }
            };

            const mitigationFilters = document.getElementById('mitigationFilters');
            mitigationFilters?.addEventListener('change', () => {
                const params = new URLSearchParams(new FormData(mitigationFilters));
                loadMitigationTable(`${mitigationFilters.action}?${params.toString()}`);
            });
            mitigationFilters?.addEventListener('submit', (event) => {
                event.preventDefault();
                const params = new URLSearchParams(new FormData(mitigationFilters));
                loadMitigationTable(`${mitigationFilters.action}?${params.toString()}`);
            });
            let mitigationSearchTimer;
            mitigationFilters?.querySelector('[name="search"]')?.addEventListener('input', () => {
                window.clearTimeout(mitigationSearchTimer);
                mitigationSearchTimer = window.setTimeout(() => {
                    const params = new URLSearchParams(new FormData(mitigationFilters));
                    loadMitigationTable(`${mitigationFilters.action}?${params.toString()}`);
                }, 300);
            });
            document.addEventListener('click', (event) => {
                const link = event.target.closest('#mitigationTableData .table-pagination a');
                if (!link) return;
                event.preventDefault();
                loadMitigationTable(link.href);
            });
            bindMitigationTableActions();

            if (menuToggle && menu) {
                menuToggle.addEventListener('click', () => menu.classList.toggle('show'));
                document.addEventListener('click', (event) => {
                    if (!menuToggle.contains(event.target) && !menu.contains(event.target)) {
                        menu.classList.remove('show');
                    }
                });
            }

            @if($errors->any())
                openModal(createModal);
            @endif

            @if(!empty($autoOpenCreateModal))
                openModal(createModal);
            @endif
        });
    </script>
</body>
</html>
