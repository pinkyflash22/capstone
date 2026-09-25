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
        .mitigation-wrap { width: 100%; max-width: 1320px; margin: 0 auto; }
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
        .summary-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:8px; }
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
        .mitigation-field textarea { min-height:96px; resize:vertical; }
        .filter-actions { display:flex; gap:10px; align-items:flex-end; justify-content:flex-end; }
        .attachments { display:flex; flex-direction:column; gap:8px; }
        .attachments a { display:block; color:#c2410c; font-weight:700; text-decoration:none; overflow-wrap:anywhere; }
        .attachment-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(112px,1fr)); gap:9px; }
        .attachment-thumb { position:relative; display:block; min-width:0; aspect-ratio:4/3; overflow:hidden; border:1px solid #e2e8f0; border-radius:13px; background:#f1f5f9; color:#fff !important; }
        .attachment-thumb img { width:100%; height:100%; display:block; object-fit:cover; transition:transform .2s ease; }
        .attachment-thumb:hover img { transform:scale(1.04); }
        .attachment-thumb span { position:absolute; right:0; bottom:0; left:0; padding:18px 8px 7px; background:linear-gradient(transparent,rgba(15,23,42,.82)); font-size:.62rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .attachment-file { display:flex !important; align-items:center; gap:8px; min-height:42px; padding:8px 10px; border:1px solid #e2e8f0; border-radius:11px; background:#f8fafc; color:#475569 !important; font-size:.72rem; }
        .attachment-file i { color:#f97316; }
        .attachment-lightbox[hidden] { display:none; }
        .attachment-lightbox { position:fixed; inset:0; z-index:140; display:grid; grid-template-columns:52px minmax(0,1fr) 52px; align-items:center; gap:12px; padding:24px; background:rgba(2,6,23,.9); backdrop-filter:blur(10px); }
        .attachment-lightbox__figure { min-width:0; max-width:min(1050px,100%); max-height:calc(100dvh - 80px); margin:auto; display:flex; flex-direction:column; align-items:center; gap:10px; }
        .attachment-lightbox__figure img { display:block; max-width:100%; max-height:calc(100dvh - 130px); object-fit:contain; border-radius:16px; box-shadow:0 24px 70px rgba(0,0,0,.38); }
        .attachment-lightbox__figure figcaption { max-width:80vw; color:#e2e8f0; font-size:.78rem; font-weight:750; text-align:center; overflow-wrap:anywhere; }
        .attachment-lightbox__button { display:grid; place-items:center; width:46px; height:46px; border:1px solid rgba(255,255,255,.18); border-radius:14px; background:rgba(255,255,255,.1); color:#fff; cursor:pointer; }
        .attachment-lightbox__button:hover { background:rgba(255,255,255,.18); }
        .attachment-lightbox__close { position:absolute; top:18px; right:18px; }
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
        .mitigation-form-dialog form { display:flex; flex:1 1 auto; flex-direction:column; width:100%; min-height:0; overflow:hidden; }
        .mitigation-form-scroll { flex:1 1 auto; min-height:0; padding:20px 22px; overflow-y:auto; overscroll-behavior:contain; background:#f8fafc; }
        .form-actions { display:flex; justify-content:end; gap:12px; margin-top:20px; }
        .mitigation-form-dialog .form-actions { position:relative; z-index:2; margin:0; padding:15px 22px; border-top:1px solid #e2e8f0; background:#fff; box-shadow:0 -8px 22px rgba(15,23,42,.06); flex:0 0 auto; }
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
        .insight-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .insight-card { display:flex; flex-direction:column; min-width:0; padding:15px; border:1px solid #e2e8f0; border-radius:17px; background:#fff; box-shadow:none; }
        .insight-card__top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
        .insight-card__eyebrow { display:flex; align-items:center; gap:6px; margin-bottom:7px; color:#64748b; font-size:.65rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
        .insight-card__title { margin:0; color:#0f172a; font-size:.9rem; font-weight:900; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .insight-card__meta { display:flex; align-items:center; flex-wrap:wrap; gap:6px; margin-top:8px; color:#64748b; font-size:.7rem; line-height:1.4; }
        .insight-card__summary { margin:10px 0 0; color:#64748b; font-size:.73rem; line-height:1.48; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .insight-card__footer { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; padding-top:13px; }
        .insight-card__actions { display:flex; align-items:center; gap:6px; }
        .insight-card__actions form { margin:0; }
        .insight-action { width:36px !important; min-height:36px !important; padding:0 !important; justify-content:center !important; border-radius:11px !important; box-shadow:none !important; }
        .insight-action span { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
        .mitigation-register .panel-head { align-items:center; }
        .register-sort { display:inline-flex; align-items:center; gap:7px; min-height:38px; padding:0 9px 0 11px; border:1px solid #e2e8f0; border-radius:999px; background:#fff; color:#64748b; }
        .register-sort i { color:#f97316; font-size:.72rem; }
        .register-sort select { max-width:132px; border:0; outline:0; background:transparent; color:#334155; font-size:.72rem; font-weight:800; cursor:pointer; }
        .mitigation-register-table { min-width:720px; table-layout:fixed; }
        .mitigation-register-table .register-plan-col { width:44%; }
        .mitigation-register-table .register-status-col { width:21%; }
        .mitigation-register-table .register-owner-col { width:23%; }
        .mitigation-register-table .register-actions-col { width:12%; }
        .register-col-location, .register-col-source { display:none; }
        .register-plan { min-width:0; }
        .register-plan__location { display:flex; align-items:flex-start; gap:6px; margin-top:5px; color:#64748b; font-size:.72rem; line-height:1.4; }
        .register-plan__location i { margin-top:2px; color:#f97316; }
        .register-meta { display:flex; flex-direction:column; gap:6px; }
        .register-owner { color:#1e293b; font-size:.78rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .register-due { color:#64748b; font-size:.7rem; }
        .details-dialog { display:flex; flex-direction:column; width:min(900px,calc(100vw - 48px)) !important; max-width:900px !important; max-height:min(90vh,820px) !important; padding:0 !important; overflow:hidden !important; }
        .details-dialog .mitigation-dialog__head { align-items:center; margin:0; padding:18px 20px; background:#fff; flex:0 0 auto; }
        .details-dialog__body { min-height:0; padding:18px 20px 22px; overflow-y:auto; background:#f8fafc; }
        .details-dialog__actions { display:flex; justify-content:flex-end; gap:8px; padding:12px 20px; border-top:1px solid #e2e8f0; background:#fff; }
        .detail-hero { padding:18px; border:1px solid #fed7aa; border-radius:18px; background:linear-gradient(135deg,#fff7ed,#fff 72%); }
        .detail-hero__badges { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
        .detail-hero__copy { margin:0; color:#334155; font-size:.82rem; line-height:1.6; white-space:pre-line; }
        .detail-facts { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin:10px 0; }
        .detail-fact { min-width:0; padding:11px 12px; border:1px solid #e2e8f0; border-radius:14px; background:#fff; }
        .detail-fact span { display:block; color:#94a3b8; font-size:.6rem; font-weight:850; letter-spacing:.07em; text-transform:uppercase; }
        .detail-fact strong { display:block; margin-top:5px; color:#1e293b; font-size:.76rem; line-height:1.35; overflow-wrap:anywhere; }
        .detail-disclosures { display:flex; flex-direction:column; gap:8px; }
        .detail-disclosure { border:1px solid #e2e8f0; border-radius:16px; background:#fff; overflow:hidden; }
        .detail-disclosure > .detail-disclosure__heading { display:grid; grid-template-columns:auto minmax(0,1fr); align-items:center; gap:10px; min-height:54px; padding:10px 14px; }
        .detail-disclosure > summary::-webkit-details-marker { display:none; }
        .detail-disclosure__icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; color:#c2410c; background:#ffedd5; }
        .detail-disclosure__label strong { display:block; color:#1e293b; font-size:.78rem; }
        .detail-disclosure__label span { display:block; margin-top:2px; color:#94a3b8; font-size:.65rem; }
        .detail-disclosure__chevron { color:#94a3b8; transition:transform .18s ease; }
        .detail-disclosure[open] .detail-disclosure__chevron { transform:rotate(180deg); }
        .detail-disclosure__content { padding:0 14px 14px; border-top:1px solid #f1f5f9; }
        .detail-content-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; padding-top:13px; }
        .detail-content-item { min-width:0; }
        .detail-content-item.is-wide { grid-column:1 / -1; }
        .detail-content-item > span { display:block; margin-bottom:4px; color:#94a3b8; font-size:.61rem; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
        .detail-content-item > div { color:#475569; font-size:.76rem; line-height:1.55; overflow-wrap:anywhere; }
        @media (min-width:1025px) {
            .mitigation-dialog { left:var(--sidebar-w,280px); }
        }

        @media (min-width: 1025px) {
            .mitigation-main { margin-left: var(--sidebar-w, 280px); width: calc(100% - var(--sidebar-w, 280px)); }
        }

        @media (max-width: 1400px) {
            .summary-grid { grid-template-columns:repeat(4, minmax(0,1fr)); }
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
            .mitigation-shell, .mitigation-main, .dashboard-container, .mitigation-wrap { min-width:0; max-width:100%; box-sizing:border-box; }
            .mitigation-main { margin-left:0; width:100%; }
            .dashboard-container { width:100%; padding:10px 10px calc(env(safe-area-inset-bottom, 0px) + 92px); }
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
            .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; }
            .filters-grid, .details-grid, .details-grid.two-col { grid-template-columns:1fr; gap:8px; }
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
            .row-actions > :not(.primary-view) { display:none !important; }
            .row-btn.primary-view { width:auto; min-height:38px; padding:0 12px; gap:6px; }
            .row-btn.primary-view span { display:inline; }
            .attachments a, .note, .details-item span, .details-item div { font-size:.68rem; }
            .mitigation-dialog { align-items:flex-end; padding:8px; }
            .mitigation-dialog__card, .mitigation-dialog__card.wide { width:100%; max-width:100%; max-height:min(88dvh, 760px); border-radius:22px 22px 16px 16px; padding:11px; }
            .mitigation-dialog__card.mitigation-form-dialog { width:100%; max-width:100%; height:min(92dvh,900px); max-height:92dvh; padding:0; border-radius:24px 24px 12px 12px; }
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
            .hero-btn, .submit-btn, .cancel-btn, .filter-btn { width:auto; justify-content:center; min-height:36px; padding:0 11px; font-size:.7rem; }
            .mobile-icon-action { width:36px !important; min-width:36px; min-height:36px !important; padding:0 !important; border-radius:11px !important; box-shadow:none !important; }
            .mobile-icon-action span { position:absolute !important; width:1px !important; height:1px !important; padding:0 !important; margin:-1px !important; overflow:hidden !important; clip:rect(0,0,0,0) !important; white-space:nowrap !important; border:0 !important; }
            .mitigation-attention { display:grid; grid-template-columns:repeat(5,36px); justify-content:start; gap:7px; overflow-x:auto; }
            .filter-actions { width:100%; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
            .filter-actions .filter-btn { width:100%; }
            .compact-draft-card { grid-template-columns:1fr; gap:9px; padding:11px; }
            .compact-draft-card__actions { justify-content:flex-end; }
            .compact-action { width:34px !important; min-height:34px; padding:0; }
            .mitigation-ai-saved > div:last-child, .mitigation-ai-recommendations > div:last-child, .insight-list { grid-template-columns:1fr; }
            .insight-card { padding:12px; border-radius:14px; }
            .insight-card__summary { -webkit-line-clamp:2; }
            .mitigation-register .panel-head > .chip-row { width:100%; display:grid; grid-template-columns:minmax(0,1fr) auto; }
            .register-sort { min-width:0; width:100%; }
            .register-sort select { width:100%; max-width:none; }
            .details-dialog { width:100% !important; max-width:100% !important; max-height:92dvh !important; border-radius:24px 24px 12px 12px !important; }
            .details-dialog .mitigation-dialog__head { padding:14px; }
            .details-dialog__body { padding:10px; }
            .details-dialog__actions { display:grid; grid-template-columns:1fr 1fr; padding:10px 12px calc(10px + env(safe-area-inset-bottom,0px)); }
            .details-dialog__actions .filter-btn { width:100%; min-height:42px; }
            .details-dialog__actions #detailsChecklistLink[hidden] + #detailsEditButton { grid-column:1 / -1; }
            .detail-hero { padding:13px; border-radius:15px; }
            .detail-facts { grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; }
            .detail-content-grid { grid-template-columns:1fr; gap:10px; }
            .detail-content-item.is-wide { grid-column:auto; }
            .attachment-gallery { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .attachment-lightbox { grid-template-columns:40px minmax(0,1fr) 40px; gap:5px; padding:58px 8px 18px; }
            .attachment-lightbox__button { width:38px; height:38px; border-radius:12px; }
            .attachment-lightbox__close { top:10px; right:10px; }
        }
    </style>
    <style>
        .mitigation-form-errors{margin:12px 18px 0;padding:11px 13px;border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;font-size:.78rem;line-height:1.45}.mitigation-form-errors strong{display:block;margin-bottom:4px}.mitigation-form-errors ul{margin:0;padding-left:18px}
        .mitigation-page-notice{display:flex;align-items:flex-start;gap:9px;margin-bottom:10px;padding:10px 12px;border:1px solid #bbf7d0;border-radius:13px;background:#f0fdf4;color:#166534;font-size:.74rem;font-weight:750;line-height:1.45}.mitigation-page-notice.is-error{border-color:#fecaca;background:#fef2f2;color:#991b1b}.mitigation-page-notice i{margin-top:2px}.suggestion-scan-help{margin-top:6px!important;font-size:.68rem!important}.suggestion-scan-form button:disabled{opacity:.6;cursor:wait}
    </style>
    <style data-mitigation-mobile-style>
        @media (max-width: 767px), (display-mode: standalone) and (max-width: 1024px) {
            body.chief-dashboard-page { background: #f7f8fa; }
            .dashboard-container { padding: 10px 10px calc(106px + env(safe-area-inset-bottom, 0px)); }
            .mobile-page-head { display: none; }

            .workflow-nav {
                display: flex;
                width: 100%;
                margin: 0 0 10px;
                padding: 4px;
                gap: 3px;
                overflow: hidden;
                border-radius: 14px;
            }
            .workflow-nav__arrow { display: none; }
            .workflow-nav__step {
                min-width: 0;
                min-height: 34px;
                flex: 1;
                justify-content: center;
                gap: 5px;
                padding: 0 4px;
                border-radius: 10px;
                font-size: 8.5px;
            }

            .mitigation-banner {
                display: block;
                margin-bottom: 11px;
                padding: 14px;
                border-radius: 18px;
                background: linear-gradient(145deg, #fff, #fff8ef);
                box-shadow: 0 6px 18px rgba(39, 46, 59, .05);
            }
            .mitigation-banner__title { margin-top: 5px; font-size: 18px; line-height: 1.18; }
            .mitigation-banner__copy { font-size: 10.5px; line-height: 1.45; }
            .mitigation-banner__meta { margin-top: 10px; }

            .mitigation-stack { gap: 10px; }
            .mitigation-summary { order: 1; }
            .mitigation-attention { order: 2; }
            .mitigation-register { order: 3; }
            .mitigation-ai-saved { order: 4; }
            .mitigation-ai-recommendations { order: 5; }
            .summary-grid .card { min-height: 62px; padding: 10px; border-radius: 15px; }
            .card-value { margin-top: 5px; font-size: 18px; }

            .mitigation-attention { margin-bottom: 0 !important; }
            .mitigation-register {
                overflow: visible;
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }
            .mitigation-register::before { display: none; }
            .mitigation-register .panel-head {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
                padding: 13px;
                border: 1px solid rgba(226, 232, 240, .92);
                border-radius: 17px;
                background: #fff;
                box-shadow: 0 5px 16px rgba(39, 46, 59, .04);
            }
            .mitigation-register .panel-head > div:first-child { min-width: 0; }
            .mitigation-register .panel-head h3 { font-size: 15px; }
            .mitigation-register .panel-head p { display: none; }
            .mitigation-register .panel-head > .chip-row { width: auto; grid-template-columns: minmax(0, 118px) 36px; }
            .register-sort { min-height: 36px; }
            .register-sort select { font-size: 10px; }

            .filter-disclosure {
                margin-bottom: 8px;
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 4px 14px rgba(39, 46, 59, .035);
            }
            .filter-disclosure > summary { min-height: 44px; padding: 0 13px; font-size: 11px; }
            .filter-disclosure .panel { padding: 12px !important; border-radius: 0 0 14px 14px; }

            .mitigation-register .table-wrap { overflow: visible; border: 0; background: transparent; }
            .mitigation-register-table tbody { gap: 9px; }
            .mitigation-register-table tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px 10px;
                padding: 11px;
                border-radius: 18px;
                box-shadow: 0 7px 20px rgba(39, 46, 59, .055);
            }
            .mitigation-register-table tbody td {
                display: block;
                min-width: 0;
                width: auto !important;
                padding: 0;
                border: 0;
            }
            .mitigation-register-table tbody td::before { display: none; }
            .mitigation-register-table .register-col-location,
            .mitigation-register-table .register-col-source { display: none !important; }
            .mitigation-register-table td[data-label="Plan"] { grid-column: 1 / -1; }
            .mitigation-register-table td[data-label="Priority / Stage"] { grid-column: 1 / 2; align-self: center; }
            .mitigation-register-table td[data-label="Owner / Target"] { grid-column: 2 / 3; align-self: center; text-align: right; }
            .mitigation-register-table td[data-label="Actions"] { grid-column: 1 / -1; padding-top: 7px; border-top: 1px solid #f1f5f9; }
            .register-plan__location { margin-top: 4px; font-size: 10px; }
            .register-plan + .chip-row { margin-top: 6px !important; gap: 5px; }
            .register-owner { max-width: 128px; font-size: 10px; }
            .register-due { margin-top: 3px; font-size: 9px; }
            .row-actions { display: flex; justify-content: flex-end; gap: 5px; }
            .mitigation-register-table .row-actions > :not(.primary-view) { display: inline-flex !important; margin: 0; }
            .row-actions .row-btn { width: 32px; min-width: 32px; min-height: 32px; padding: 0; justify-content: center; border-radius: 10px; }
            .row-btn.primary-view { width: 32px; min-height: 32px; padding: 0; }
            .row-btn.primary-view span { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
        }

        @media (max-width: 390px) {
            .workflow-nav__step { font-size: 8px; }
            .mitigation-register .panel-head { padding: 11px; }
            .mitigation-register .panel-head > .chip-row { grid-template-columns: minmax(0, 104px) 36px; }
            .mitigation-register-table tbody tr { padding: 10px; gap: 7px; }
            .row-actions { gap: 4px; }
            .row-actions .row-btn, .row-btn.primary-view { width: 30px; min-width: 30px; min-height: 30px; }
        }
    </style>
    @include('chief.partials.workflow-presentation')
    @include('chief.partials.mitigation-presentation')
    <style>
        .mitigation-stats-strip { margin: 0 0 14px; }
        .mitigation-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .mitigation-stat-card { min-height: 76px; padding: 13px 14px; border: 1px solid #E9E5DE; border-left-width: 3px; border-radius: 14px; background: #fff; box-shadow: none; }
        .mitigation-stat-card--high { border-left-color: #B23A22; }
        .mitigation-stat-card--approval { border-left-color: #A5680B; }
        .mitigation-stat-card--progress { border-left-color: #3A6FB0; }
        .mitigation-stat-card--complete { border-left-color: #1E7A4C; }
        .mitigation-stat-card__label { color: #64748b; font-size: 11px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
        .mitigation-stat-card__value { margin-top: 7px; color: #0f172a; font-size: 24px; line-height: 1; font-weight: 900; }
        .mitigation-attention { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 14px !important; }
        .mitigation-attention .mitigation-attention-chip { display: inline-flex; align-items: center; gap: 7px; min-height: 34px; padding: 0 12px; border: 1px solid #d7dce5; border-radius: 999px; background: #fff; color: #5f6673; font-size: 12px; font-weight: 850; text-decoration: none; box-shadow: none; }
        .mitigation-attention .mitigation-attention-chip.is-hot { border-color: #F0C7BA; background: #FBEAE4; color: #B23A22; }
        .mitigation-attention .mitigation-attention-chip:hover { border-color: #cbd5e1; background: #f8fafc; }
        .mitigation-attention .mitigation-attention-chip.is-hot:hover { border-color: #E8AD9C; background: #FBEAE4; }
        .mitigation-register-table { border-collapse: separate; border-spacing: 0; }
        .mitigation-register-table tbody tr { position: relative; box-shadow: none; }
        .mitigation-register-table tbody tr td { border-bottom: 1px solid #E9E5DE; background: #fff; }
        .mitigation-register-table tbody tr:last-child td { border-bottom: 1px solid #E9E5DE; }
        .mitigation-register-table tbody tr:hover td { background: #fffaf5; }
        .mitigation-register-table td[data-label="Plan"] { position: relative; padding-left: 19px; }
        .mitigation-priority-bar { position: absolute; inset: 0 auto 0 0; width: 5px; background: #A5680B; }
        .mitigation-priority-bar.is-high { background: #B23A22; }
        .mitigation-priority-bar.is-medium { background: #A5680B; }
        .mitigation-priority-bar.is-low { background: #1E7A4C; }
        .register-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; margin-top: 11px; }
        .register-tag { display: inline-flex; align-items: center; gap: 6px; min-height: 27px; padding: 0 10px; border-radius: 999px; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; line-height: 1; }
        .register-tag--category { border: 1px solid #d7dce5; background: #fff; color: #5f6673; }
        .register-tag--ai { border: 1px solid #E4D8F6; background: #F3EEFB; color: #7A4FB8; }
        .register-tag--revision { margin-left: 4px; border: 1px solid #F7D5C7; background: #FCEEE7; color: #B8461C; }
        .register-tag--overdue { border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; }
        .priority-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 28px; padding: 0 10px; border-radius: 999px; font-size: 11px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .priority-pill::before, .state-label::before { content: ""; width: 7px; height: 7px; border-radius: 999px; background: currentColor; flex: 0 0 7px; }
        .priority-pill.is-high { background: #FBEAE4; color: #B23A22; }
        .priority-pill.is-medium { background: #FBF0DD; color: #A5680B; }
        .priority-pill.is-low { background: #E7F5EC; color: #1E7A4C; }
        .state-label { display: inline-flex; align-items: center; gap: 7px; min-height: 24px; color: #6B6E76; font-size: 11px; font-weight: 750; text-transform: uppercase; letter-spacing: .04em; }
        .state-label.is-approved, .state-label.is-completed { color: #1E7A4C; }
        .state-label.is-blocked { color: #B23A22; }
        .owner-chip { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .owner-avatar { display: inline-flex; align-items: center; justify-content: center; width: 31px; height: 31px; border-radius: 999px; background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; font-size: 10px; font-weight: 900; letter-spacing: .04em; flex: 0 0 31px; }
        .owner-avatar.is-empty { border-style: dashed; background: #fff; color: #94a3b8; }
        .register-owner.is-unassigned { color: #8a9099; font-style: italic; font-weight: 700; }
        .register-due { display: inline-flex; align-items: center; gap: 6px; }
        .row-actions { align-items: center; justify-content: flex-end; }
        .action-menu { position: relative; display: inline-flex; }
        .action-menu summary { list-style: none; }
        .action-menu summary::-webkit-details-marker { display: none; }
        .action-menu__panel { position: absolute; right: 0; top: calc(100% + 7px); z-index: 25; min-width: 168px; padding: 6px; border: 1px solid #E9E5DE; border-radius: 12px; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, .12); }
        .action-menu__item { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 36px; padding: 0 10px; border: 0; border-radius: 8px; background: transparent; color: #B23A22; font-size: 12px; font-weight: 800; text-align: left; cursor: pointer; }
        .action-menu__item:hover { background: #FCEEE7; }
        .mitigation-ai-saved .insight-list { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 12px !important; }
        .mitigation-ai-saved .ai-draft-card { display: flex !important; flex-direction: column; gap: 13px; min-width: 0; padding: 16px !important; border: 1px solid #E9E5DE; border-radius: 16px; background: #fff; box-shadow: none !important; }
        .mitigation-ai-saved .ai-draft-card__head { display: flex !important; align-items: flex-start; justify-content: space-between; gap: 10px; min-width: 0; }
        .mitigation-ai-saved .ai-draft-card__title { margin: 0; color: #0f172a; font-size: 18px; line-height: 1.18; font-weight: 800; letter-spacing: 0; }
        .mitigation-ai-saved .ai-draft-card__subtitle { margin: 5px 0 0; color: #6b7280; font-size: 13px; line-height: 1.45; font-weight: 500; }
        .mitigation-ai-saved .ai-draft-card__badges { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 7px; flex: 0 0 auto; }
        .ai-draft-pill { display: inline-flex; align-items: center; gap: 6px; min-height: 26px; padding: 0 9px; border-radius: 999px; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .ai-draft-pill--risk.is-medium, .ai-draft-pill--risk.is-moderate { background: #FBF0DD; color: #A5680B; }
        .ai-draft-pill--risk.is-low { background: #E7F5EC; color: #1E7A4C; }
        .ai-draft-pill--risk.is-high, .ai-draft-pill--risk.is-critical { background: #FBEAE4; color: #B23A22; }
        .ai-draft-pill--ai { background: #F3EEFB; color: #7A4FB8; }
        .ai-draft-tag { display: inline-flex; align-items: center; width: fit-content; min-height: 25px; padding: 0 9px; border: 1px solid #d7dce5; border-radius: 999px; background: #fff; color: #5f6673; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .mitigation-ai-saved .ai-draft-card__summary { display: block !important; margin: 0; color: #4b5563; font-size: 13px; line-height: 1.55; font-weight: 450; }
        .mitigation-ai-saved .ai-draft-card__footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: auto; padding-top: 10px; border-top: 1px solid #E9E5DE; }
        .ai-draft-ready-chip { display: inline-flex; align-items: center; min-height: 26px; padding: 0 9px; border-radius: 999px; background: #F0EEEA; color: #4A4E58; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
        .mitigation-ai-saved .ai-draft-card__actions { display: flex !important; align-items: center; gap: 8px; min-width: 0; }
        .mitigation-ai-saved .ai-draft-action { width: auto !important; min-width: 0 !important; min-height: 36px !important; padding: 0 12px !important; border-radius: 10px !important; box-shadow: none !important; font-size: 12px; }
        .mitigation-ai-saved .ai-draft-action span { position: static !important; width: auto !important; height: auto !important; margin: 0 !important; clip: auto !important; overflow: visible !important; white-space: nowrap !important; }
        .mitigation-ai-saved .ai-draft-action--primary { background: #B23A22; color: #fff; }
        .mitigation-ai-saved .ai-draft-action--secondary { border: 1px solid #E9E5DE; background: #fff; color: #4A4E58; }
        .suggestion-scan-help { display: inline-flex; align-items: center; gap: 6px; width: fit-content; margin-top: 7px !important; padding: 5px 8px; border-radius: 999px; background: #f0fdf4; color: #166534; font-size: 11px !important; font-weight: 750; line-height: 1.25; }
        .mitigation-ai-recommendations .insight-list { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 12px !important; }
        .mitigation-ai-recommendations .suggestion-card { display: flex !important; flex-direction: column; gap: 13px; min-width: 0; padding: 16px !important; border: 1px solid #E9E5DE; border-radius: 16px; background: #fff; box-shadow: none !important; }
        .suggestion-card__head { display: flex !important; align-items: flex-start; justify-content: space-between; gap: 10px; min-width: 0; }
        .suggestion-card__title { margin: 0; color: #0f172a; font-size: 18px; line-height: 1.18; font-weight: 800; letter-spacing: 0; }
        .suggestion-card__subtitle { margin: 5px 0 0; color: #6b7280; font-size: 13px; line-height: 1.45; font-weight: 500; }
        .suggestion-card__badges { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 7px; flex: 0 0 auto; }
        .suggestion-pill { display: inline-flex; align-items: center; gap: 6px; min-height: 26px; padding: 0 9px; border-radius: 999px; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .suggestion-pill--risk.is-low { background: #E7F5EC; color: #1E7A4C; }
        .suggestion-pill--risk.is-medium, .suggestion-pill--risk.is-moderate { background: #FBF0DD; color: #A5680B; }
        .suggestion-pill--risk.is-high, .suggestion-pill--risk.is-critical { background: #FBEAE4; color: #B23A22; }
        .suggestion-pill--type { background: #EAF1FB; color: #3A6FB0; }
        .suggestion-card__category { display: inline-flex; align-items: center; width: fit-content; min-height: 25px; padding: 0 9px; border: 1px solid #d7dce5; border-radius: 999px; background: #fff; color: #5f6673; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .suggestion-card__summary { display: block !important; margin: 0; color: #4b5563; font-size: 13px; line-height: 1.55; font-weight: 450; }
        .suggestion-card__footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: auto; padding-top: 10px; border-top: 1px solid #E9E5DE; }
        .suggestion-card__actions { display: flex !important; align-items: center; justify-content: flex-end; gap: 8px; min-width: 0; margin-left: auto; }
        .suggestion-action { width: auto !important; min-width: 0 !important; min-height: 36px !important; padding: 0 12px !important; border-radius: 10px !important; box-shadow: none !important; font-size: 12px; }
        .suggestion-action span { position: static !important; width: auto !important; height: auto !important; margin: 0 !important; clip: auto !important; overflow: visible !important; white-space: nowrap !important; }
        .suggestion-action--view { width: 36px !important; padding: 0 !important; justify-content: center; border: 1px solid #E9E5DE; background: #fff; color: #4A4E58; }
        .suggestion-action--view span { position: absolute !important; width: 1px !important; height: 1px !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; }
        .suggestion-action--dismiss { border: 1px solid #F1B9A9; background: #fff; color: #B23A22; }
        .suggestion-action--dismiss:hover { background: #FBEAE4; }
        .suggestion-action--add { border: 1px solid #E4602C; background: #E4602C; color: #fff; }
        @media (max-width: 767px), (display-mode: standalone) and (max-width: 1024px) {
            .mitigation-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .mitigation-stat-card { min-height: 68px; padding: 12px; border-radius: 13px; }
            .mitigation-stat-card__label { font-size: 10px; }
            .mitigation-stat-card__value { font-size: 21px; }
            .mitigation-attention { display: flex; overflow: visible; }
            .mitigation-attention .mitigation-attention-chip { min-height: 34px; width: auto !important; min-width: 0; padding: 0 11px !important; }
            .mitigation-attention .mitigation-attention-chip span { position: static !important; width: auto !important; height: auto !important; margin: 0 !important; clip: auto !important; overflow: visible !important; white-space: nowrap !important; }
            .mitigation-register .table-wrap { background: #fff; border: 0; }
            .mitigation-register-table tbody { display: block; padding: 0; }
            .mitigation-register-table tbody tr { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0; padding: 0; border: 0; border-radius: 0; background: #fff; box-shadow: none; }
            .mitigation-register-table tbody td { display: block; width: 100% !important; padding: 10px 14px; border-bottom: 0; }
            .mitigation-register-table tbody tr { border-bottom: 1px solid #E9E5DE; }
            .mitigation-register-table tbody tr:last-child { border-bottom: 1px solid #E9E5DE; }
            .mitigation-register-table td[data-label="Plan"] { padding-left: 20px; }
            .mitigation-register-table td[data-label="Priority / Stage"],
            .mitigation-register-table td[data-label="Owner / Target"],
            .mitigation-register-table td[data-label="Actions"] { grid-column: auto; align-self: stretch; text-align: left; }
            .mitigation-register-table td[data-label="Actions"] { padding-top: 0; border-top: 0; }
            .mitigation-register-table .register-col-location,
            .mitigation-register-table .register-col-source { display: none !important; }
            .row-actions { justify-content: flex-start; }
            .mitigation-register-table .row-actions > :not(.primary-view) { display: inline-flex !important; }
            .row-btn.primary-view span { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
            .action-menu__panel { right: auto; left: 0; }
            .mitigation-ai-saved .insight-list { grid-template-columns: minmax(0, 1fr) !important; }
            .mitigation-ai-saved .ai-draft-card__head,
            .mitigation-ai-saved .ai-draft-card__footer { align-items: stretch; flex-direction: column; }
            .mitigation-ai-saved .ai-draft-card__badges { justify-content: flex-start; }
            .mitigation-ai-saved .ai-draft-card__actions { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
            .mitigation-ai-saved .ai-draft-action { justify-content: center; }
            .mitigation-ai-recommendations .insight-list { grid-template-columns: minmax(0, 1fr) !important; }
            .suggestion-card__head,
            .suggestion-card__footer { align-items: stretch; flex-direction: column; }
            .suggestion-card__badges { justify-content: flex-start; }
            .suggestion-card__actions { display: grid !important; grid-template-columns: 36px minmax(0, 1fr) minmax(0, 1.25fr); width: 100%; }
            .suggestion-action { justify-content: center; }
        }
        @media (max-width: 520px) {
            .mitigation-stats-grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @include('chief.partials.sidebar', ['active' => 'mitigations'])
    @php
        $summary = array_replace(
            ['total' => 0, 'awaiting_approval' => 0, 'in_progress' => 0, 'overdue' => 0, 'high_priority' => 0, 'completed' => 0, 'blocked' => 0, 'missing_owner' => 0, 'follow_up' => 0],
            $mitigationSummaryCounts ?? []
        );
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

                    <section class="mitigation-stats-strip" aria-label="Mitigation summary">
                        <div class="mitigation-stats-grid">
                            <div class="mitigation-stat-card mitigation-stat-card--high">
                                <div class="mitigation-stat-card__label">High Priority</div>
                                <div class="mitigation-stat-card__value">{{ $summary['high_priority'] }}</div>
                            </div>
                            <div class="mitigation-stat-card mitigation-stat-card--approval">
                                <div class="mitigation-stat-card__label">Awaiting Approval</div>
                                <div class="mitigation-stat-card__value">{{ $summary['awaiting_approval'] }}</div>
                            </div>
                            <div class="mitigation-stat-card mitigation-stat-card--progress">
                                <div class="mitigation-stat-card__label">In Progress</div>
                                <div class="mitigation-stat-card__value">{{ $summary['in_progress'] }}</div>
                            </div>
                            <div class="mitigation-stat-card mitigation-stat-card--complete">
                                <div class="mitigation-stat-card__label">Completed</div>
                                <div class="mitigation-stat-card__value">{{ $summary['completed'] }}</div>
                            </div>
                        </div>
                    </section>

                    <div class="mitigation-stack">
                        @if(session('success'))
                            <div class="mitigation-page-notice" role="status"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
                        @endif
                        @error('mitigation_suggestions')
                            <div class="mitigation-page-notice is-error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                        @if(!empty($mitigationDraft))
                            <section class="panel mitigation-draft-ready" style="border:1px solid rgba(249,115,22,.18); background:linear-gradient(135deg, rgba(255,247,237,.96), rgba(255,255,255,.98));">
                                <div class="panel-head">
                                    <div>
                                        <h3>Draft Ready</h3>
                                        <p>Prefilled from <strong>{{ $mitigationDraft['source_title'] ?? ($mitigationDraft['title'] ?? 'the selected checklist') }}</strong>.</p>
                                    </div>
                                    <button type="button" class="hero-btn mobile-icon-action" data-open-create-modal title="Open mitigation draft" aria-label="Open mitigation draft"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Open Draft</span></button>
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
                                <div class="insight-list">
                                    @foreach($newAiDraftMitigations as $action)
                                        @php
                                            $linkedChecklist = $action->aiActionPlanChecklist;
                                            $linkedSuggestion = optional($linkedChecklist?->suggestions)->sortByDesc('created_at')->first();
                                            $linkedAnalysis = $linkedChecklist?->analysis;
                                            $riskLevel = strtolower((string) ($linkedAnalysis?->risk_level ?? $linkedChecklist?->priority_level ?? $action->priority_level ?? 'medium'));
                                            $sourceLabel = $mitigationSourceOptions[$action->source_type] ?? ucfirst(str_replace('_', ' ', (string) $action->source_type));
                                            $categoryLabel = $mitigationCategoryOptions[$action->category] ?? ucfirst(str_replace('_', ' ', (string) $action->category));
                                            $draftEvidence = (array) data_get($linkedChecklist?->ai_payload, 'evidence_summary', []);
                                            $draftLocationHeading = $action->barangay?->barangay_name ?? data_get($draftEvidence, 'barangay_name', 'Unknown barangay');
                                            $draftCondition = trim((string) data_get($draftEvidence, 'leading_condition', 'fire-prevention readiness'));
                                            $draftSubtitleBase = ucfirst(strtolower(str_replace(['_', '-'], ' ', $draftCondition)));
                                            $draftSubtitle = str_contains(strtolower($draftSubtitleBase), 'review') ? $draftSubtitleBase : "{$draftSubtitleBase} review";
                                            $draftRiskTone = match ($riskLevel) {
                                                'critical', 'high' => 'high',
                                                'low' => 'low',
                                                default => 'medium',
                                            };
                                            $draftProbability = data_get($draftEvidence, 'probability_percent');
                                            $draftVerifiedRiskCount = (int) data_get($draftEvidence, 'verified_risk_count', 0);
                                            $draftVerifiedHazardCount = (int) data_get($draftEvidence, 'verified_hazard_count', 0);
                                            $draftHistoricalIncidentCount = (int) data_get($draftEvidence, 'historical_incident_count', 0);
                                            $draftCurrentMonthFireCount = (int) data_get($draftEvidence, 'current_month_fire_incidents', 0);
                                            $draftSummary = (is_numeric($draftProbability) ? rtrim(rtrim(number_format((float) $draftProbability, 1), '0'), '.') . '% fire probability' : 'Fire probability pending')
                                                . " from {$draftVerifiedRiskCount} verified " . \Illuminate\Support\Str::plural('risk', $draftVerifiedRiskCount)
                                                . ", {$draftVerifiedHazardCount} verified " . \Illuminate\Support\Str::plural('hazard', $draftVerifiedHazardCount)
                                                . ", {$draftHistoricalIncidentCount} historical " . \Illuminate\Support\Str::plural('incident', $draftHistoricalIncidentCount)
                                                . ", {$draftCurrentMonthFireCount} completed " . \Illuminate\Support\Str::plural('fire', $draftCurrentMonthFireCount) . ' this month.';
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
                                        <article class="insight-card ai-draft-card">
                                            <div class="ai-draft-card__head">
                                                <div>
                                                    <h4 class="ai-draft-card__title text-lg font-extrabold">{{ $draftLocationHeading }}</h4>
                                                    <p class="ai-draft-card__subtitle">{{ $draftSubtitle }}</p>
                                                </div>
                                                <div class="ai-draft-card__badges">
                                                    <span class="ai-draft-pill ai-draft-pill--risk is-{{ $draftRiskTone }}">{{ strtoupper($riskLevel) }} risk</span>
                                                    <span class="ai-draft-pill ai-draft-pill--ai"><i class="fa-solid fa-wand-magic-sparkles"></i> AI draft</span>
                                                </div>
                                            </div>
                                            <span class="ai-draft-tag">{{ $categoryLabel }}</span>
                                            <p class="ai-draft-card__summary">{{ $draftSummary }}</p>
                                            <div class="ai-draft-card__footer">
                                                <span class="ai-draft-ready-chip">Ready to review</span>
                                                <div class="ai-draft-card__actions">
                                                    <button type="button" class="hero-btn insight-action ai-draft-action ai-draft-action--primary" title="Review draft" aria-label="Review draft" data-open-details-modal data-payload='@json($draftPayload)'><i class="fa-solid fa-eye"></i><span>Review</span></button>
                                                    <a href="{{ route('station-admin.mitigations') }}#mitigation-row-{{ $action->mitigation_action_id }}" class="filter-btn secondary insight-action ai-draft-action ai-draft-action--secondary" title="Open plan" aria-label="Open plan"><i class="fa-solid fa-table-list"></i><span>Open plan</span></a>
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
                                    <p>Rule-based risk suggestions not yet added to the register.</p>
                                    <p class="suggestion-scan-help"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Opening this page does not generate or rewrite records. A scan only adds new suggestions and preserves existing progress.</span></p>
                                </div>
                                @if(!empty($canRefreshMitigationSuggestions))
                                    <form method="POST" action="{{ request()->routeIs('admin.*') ? route('admin.mitigations.suggestions.refresh') : route('station-admin.mitigations.suggestions.refresh') }}" class="suggestion-scan-form" data-suggestion-scan-form>
                                        @csrf
                                        <button type="submit" class="filter-btn secondary" title="Scan current station risk evidence for new suggestions"><i class="fa-solid fa-rotate"></i><span>Scan Risk Suggestions</span></button>
                                    </form>
                                @endif
                            </div>
                            @if(($generatedMitigationDrafts ?? collect())->isEmpty())
                                <div class="empty">No pending system-generated mitigation drafts right now.</div>
                            @else
                                <div class="insight-list">
                                    @foreach($generatedMitigationDrafts as $draftChecklist)
                                        @php
                                            $draftPayload = is_array($draftChecklist->ai_payload) ? $draftChecklist->ai_payload : [];
                                            $draftDrivers = collect($draftPayload['drivers'] ?? [])->take(3)->values();
                                            $draftFactors = collect($draftPayload['contributing_factors'] ?? [])->take(4)->map(fn ($factor) => ucwords(str_replace('_', ' ', (string) $factor)))->values();
                                            $riskLevel = strtolower((string) ($draftPayload['risk_level'] ?? $draftChecklist->priority_level ?? 'medium'));
                                            $categoryLabel = $mitigationCategoryOptions[\Illuminate\Support\Str::snake((string) ($draftPayload['recommended_category'] ?? 'other'))] ?? ucfirst(str_replace('_', ' ', (string) ($draftPayload['recommended_category'] ?? 'other')));
                                            $suggestionLocationHeading = $draftChecklist->barangay?->barangay_name
                                                ?? data_get($draftPayload, 'predicted_area.name')
                                                ?? $draftChecklist->riskArea?->marker?->title
                                                ?? 'Unknown barangay';
                                            $suggestionSubtitle = $categoryLabel . ' review';
                                            $suggestionRiskTone = match ($riskLevel) {
                                                'critical', 'high' => 'high',
                                                'low' => 'low',
                                                default => 'medium',
                                            };
                                            $suggestionProbability = data_get($draftPayload, 'risk_score');
                                            $suggestionRiskCount = (int) data_get($draftPayload, 'predicted_area.metrics.risks', 0);
                                            $suggestionHazardCount = (int) data_get($draftPayload, 'predicted_area.metrics.hazards', 0);
                                            $suggestionHistoricalCount = (int) data_get($draftPayload, 'predicted_area.metrics.historical_incidents', 0);
                                            $suggestionCompletedFires = 0;
                                            $suggestionSummary = (is_numeric($suggestionProbability) ? rtrim(rtrim(number_format((float) $suggestionProbability, 1), '0'), '.') . '% fire probability' : 'Fire probability pending')
                                                . " from {$suggestionRiskCount} verified " . \Illuminate\Support\Str::plural('risk', $suggestionRiskCount)
                                                . ", {$suggestionHazardCount} verified " . \Illuminate\Support\Str::plural('hazard', $suggestionHazardCount)
                                                . ", {$suggestionHistoricalCount} historical " . \Illuminate\Support\Str::plural('incident', $suggestionHistoricalCount)
                                                . ", {$suggestionCompletedFires} completed " . \Illuminate\Support\Str::plural('fire', $suggestionCompletedFires) . ' this month.';
                                            $suggestionTasks = collect($draftChecklist->items ?? [])->map(fn ($item) => ['text' => $item->item_text, 'status' => $item->status])->values()->all();
                                            $suggestionReviewPayload = [
                                                'title' => $draftChecklist->plan_title,
                                                'description' => $draftChecklist->plan_description,
                                                'category_label' => $categoryLabel,
                                                'priority_level' => $draftChecklist->priority_level,
                                                'status' => 'draft',
                                                'status_label' => 'Pending review',
                                                'source_type' => 'ai_action_plan',
                                                'source_label' => 'AI Action Plan',
                                                'barangay_label' => $draftChecklist->barangay?->barangay_name,
                                                'location_label' => $draftChecklist->riskArea?->marker?->title,
                                                'risk_level' => strtoupper($riskLevel),
                                                'factors' => $draftFactors->all(),
                                                'source_references' => $draftDrivers->all(),
                                                'tasks' => $suggestionTasks,
                                                'tasks_done' => collect($suggestionTasks)->where('status', 'followed')->count(),
                                                'tasks_total' => count($suggestionTasks),
                                                'progress_percent' => 0,
                                                'effectiveness' => 'Pending evaluation',
                                                'attachments' => [],
                                            ];
                                        @endphp
                                        <article class="insight-card suggestion-card">
                                            <div class="suggestion-card__head">
                                                <div>
                                                    <h4 class="suggestion-card__title text-lg font-extrabold">{{ $suggestionLocationHeading }}</h4>
                                                    <p class="suggestion-card__subtitle">{{ $suggestionSubtitle }}</p>
                                                </div>
                                                <div class="suggestion-card__badges">
                                                    <span class="suggestion-pill suggestion-pill--risk is-{{ $suggestionRiskTone }}">{{ strtoupper($riskLevel) }} risk</span>
                                                    <span class="suggestion-pill suggestion-pill--type"><i class="fa-solid fa-list-check"></i> Suggested</span>
                                                </div>
                                            </div>
                                            <span class="suggestion-card__category">{{ $categoryLabel }}</span>
                                            <p class="suggestion-card__summary">{{ $suggestionSummary }}</p>
                                            <div class="suggestion-card__footer">
                                                <div class="suggestion-card__actions">
                                                    <button type="button" class="filter-btn secondary insight-action suggestion-action suggestion-action--view" title="View details" aria-label="View details" data-open-details-modal data-payload='@json($suggestionReviewPayload)'><i class="fa-solid fa-eye"></i><span>View details</span></button>
                                                    <form method="POST" action="{{ route('station-admin.mitigations.generated-drafts.reject', ['checklistId' => $draftChecklist->ai_action_plan_checklist_id]) }}">@csrf<button type="submit" class="filter-btn secondary insight-action suggestion-action suggestion-action--dismiss" title="Dismiss suggestion" aria-label="Dismiss suggestion"><i class="fa-solid fa-ban"></i><span>Dismiss</span></button></form>
                                                    <a href="{{ route('station-admin.mitigations', ['draft_ai_action_plan_checklist_id' => $draftChecklist->ai_action_plan_checklist_id]) }}" class="hero-btn insight-action suggestion-action suggestion-action--add" title="Add to register" aria-label="Add to register"><i class="fa-solid fa-list-check"></i><span>Add to register</span></a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <nav class="mitigation-attention" aria-label="Mitigation attention views">
                            <a class="mitigation-attention-chip {{ (int) $summary['overdue'] > 0 ? 'is-hot' : '' }}" href="{{ route('station-admin.mitigations', ['attention' => 'overdue']) }}" title="Overdue: {{ $summary['overdue'] }}" aria-label="Show overdue mitigations: {{ $summary['overdue'] }}"><i class="fa-solid fa-clock"></i><span>Overdue {{ $summary['overdue'] }}</span></a>
                            <a class="mitigation-attention-chip {{ (int) $summary['blocked'] > 0 ? 'is-hot' : '' }}" href="{{ route('station-admin.mitigations', ['attention' => 'blocked']) }}" title="Blocked: {{ $summary['blocked'] }}" aria-label="Show blocked mitigations: {{ $summary['blocked'] }}"><i class="fa-solid fa-road-barrier"></i><span>Blocked {{ $summary['blocked'] }}</span></a>
                            <a class="mitigation-attention-chip {{ (int) $summary['awaiting_approval'] > 0 ? 'is-hot' : '' }}" href="{{ route('station-admin.mitigations', ['attention' => 'awaiting_review']) }}" title="Needs review" aria-label="Show mitigations needing review"><i class="fa-solid fa-user-check"></i><span>Needs review</span></a>
                            <a class="mitigation-attention-chip {{ (int) $summary['missing_owner'] > 0 ? 'is-hot' : '' }}" href="{{ route('station-admin.mitigations', ['attention' => 'missing_owner']) }}" title="No owner: {{ $summary['missing_owner'] }}" aria-label="Show mitigations without an owner: {{ $summary['missing_owner'] }}"><i class="fa-solid fa-user-slash"></i><span>No owner {{ $summary['missing_owner'] }}</span></a>
                            <a class="mitigation-attention-chip {{ (int) $summary['follow_up'] > 0 ? 'is-hot' : '' }}" href="{{ route('station-admin.mitigations', ['attention' => 'follow_up']) }}" title="Follow-up: {{ $summary['follow_up'] }}" aria-label="Show mitigations requiring follow-up: {{ $summary['follow_up'] }}"><i class="fa-solid fa-rotate"></i><span>Follow-up {{ $summary['follow_up'] }}</span></a>
                        </nav>

                        <section class="panel mitigation-register">
                            <div class="panel-head">
                                <div>
                                    <h3>Mitigation Register</h3>
                                    <p>Track, assign, and review prevention actions.</p>
                                </div>
                                <div class="chip-row">
                                    <label class="register-sort" for="mitigationRegisterSort"><i class="fa-solid fa-arrow-down-wide-short"></i><span class="sr-only">Sort mitigation register</span><select id="mitigationRegisterSort" data-mitigation-sort aria-label="Sort mitigation register"><option value="priority" @selected(($filters['sort'] ?? 'priority') === 'priority')>Priority</option><option value="due_date_asc" @selected(($filters['sort'] ?? '') === 'due_date_asc')>Due date: earliest</option><option value="due_date_desc" @selected(($filters['sort'] ?? '') === 'due_date_desc')>Due date: latest</option><option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status</option></select></label>
                                    <button type="button" class="hero-btn mobile-icon-action" data-open-create-modal title="Add mitigation" aria-label="Add mitigation"><i class="fa-solid fa-plus"></i><span>Add Mitigation</span></button>
                                </div>
                            </div>

                            <section class="mitigation-filter-region" aria-label="Filter mitigations">
                            <form id="mitigationFilters" method="GET" action="{{ route('station-admin.mitigations') }}" class="panel" style="padding:14px; box-shadow:none;">
                                <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'priority' }}" data-mitigation-sort-value>
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
                            </section>

                            <div id="mitigationTableData" aria-live="polite">
                            @if($mitigationActions->isEmpty())
                                <div class="empty"><i class="fa-solid fa-filter-circle-xmark"></i> No matching mitigation plans.</div>
                            @else
                                <div class="table-wrap">
                                    <table class="mitigation-register-table">
                                        <thead>
                                            <tr>
                                                <th class="register-plan-col">Plan and coverage</th>
                                                <th class="register-col-location">Location</th>
                                                <th class="register-status-col">Status</th>
                                                <th class="register-owner-col">Owner and due date</th>
                                                <th class="register-col-source">Source</th>
                                                <th class="register-actions-col">Actions</th>
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
                                                        'baseline_condition' => $action->baseline_condition,
                                                        'expected_result' => $action->expected_result,
                                                        'remarks' => $action->remarks,
                                                        'implementation_notes' => $action->implementation_notes,
                                                        'blocked_reason' => $action->blocked_reason,
                                                        'completion_notes' => $action->completion_notes,
                                                        'outcome_result' => $action->outcome_result,
                                                        'effectiveness_key' => $action->effectiveness ?: 'pending_evaluation',
                                                        'effectiveness' => $mitigationEffectivenessOptions[$action->effectiveness ?: 'pending_evaluation'] ?? 'Pending Evaluation',
                                                        'verification_date' => optional($action->verification_date)->format('Y-m-d'),
                                                        'verification_date_label' => optional($action->verification_date)->format('M d, Y'),
                                                        'verified_by_label' => $action->verifier?->full_name,
                                                        'follow_up_required' => (bool) $action->follow_up_required,
                                                        'follow_up_date' => optional($action->follow_up_date)->format('Y-m-d'),
                                                        'follow_up_date_label' => optional($action->follow_up_date)->format('M d, Y'),
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
                                                        'checklist_url' => $action->ai_action_plan_checklist_id ? route('station-admin.course-checklists', ['checklist' => $action->ai_action_plan_checklist_id]) : null,
                                                    ];
                                                    $isOverdue = $action->target_date && $action->target_date->lt(today()) && ! in_array($action->status, ['completed', 'cancelled'], true);
                                                    $priorityKey = strtolower((string) ($action->priority_level ?: 'medium'));
                                                    $priorityTone = match ($priorityKey) {
                                                        'critical', 'high' => 'high',
                                                        'low' => 'low',
                                                        default => 'medium',
                                                    };
                                                    $locationHeading = $action->scope_type === 'citywide'
                                                        ? (($action->barangay?->city?->city_name ?? 'City') . ' citywide')
                                                        : ($action->scope_type === 'stationwide' ? 'Stationwide coverage' : ($action->barangay?->barangay_name ?? 'Unknown barangay'));
                                                    $planSubtitle = trim((string) $action->title);
                                                    foreach (array_filter([$locationHeading, $action->barangay?->barangay_name, $action->riskArea?->marker?->title]) as $locationText) {
                                                        $planSubtitle = trim(preg_replace('/^\s*' . preg_quote($locationText, '/') . '\s*(?:[-–—:|]\s*)?/iu', '', $planSubtitle) ?? $planSubtitle);
                                                    }
                                                    $planSubtitle = $planSubtitle !== '' ? $planSubtitle : ($action->description ?: 'Mitigation plan');
                                                    $ownerName = $action->responsibleUser?->full_name ?? $action->responsibleTeam?->team_name;
                                                    $ownerInitials = collect(preg_split('/\s+/', trim((string) $ownerName)) ?: [])
                                                        ->filter()
                                                        ->take(2)
                                                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                                        ->implode('');
                                                    $ownerInitials = $ownerInitials ?: 'UN';
                                                @endphp
                                                <tr id="mitigation-row-{{ $action->mitigation_action_id }}" class="mitigation-register-row mitigation-register-row--{{ $priorityTone }}">
                                                    <td data-label="Plan">
                                                        <span class="mitigation-priority-bar is-{{ $priorityTone }}" aria-hidden="true"></span>
                                                        <div class="register-plan">
                                                            <div class="text-base font-extrabold leading-tight text-slate-950">{{ $locationHeading }}</div>
                                                            <div class="mt-1 text-sm leading-snug text-slate-500">{{ $planSubtitle }}</div>
                                                        </div>
                                                        <div class="register-tags">
                                                            <span class="register-tag register-tag--category">{{ $categoryLabel }}</span>
                                                            <span class="register-tag {{ $sourceType === 'ai_action_plan' ? 'register-tag--ai' : 'register-tag--category' }}">{{ $sourceLabel }}</span>
                                                            @if($isOverdue)
                                                                <span class="register-tag register-tag--overdue"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</span>
                                                            @endif
                                                            @if($action->requires_revision_review)
                                                                <span class="register-tag register-tag--revision"><i class="fa-solid fa-flag"></i> Review revision {{ $action->analysis_revision }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td data-label="Location" class="register-col-location">
                                                        <div class="title" style="font-size:.86rem;">{{ $action->scope_type === 'citywide' ? (($action->barangay?->city?->city_name ?? 'City') . ' · Citywide') : ($action->scope_type === 'stationwide' ? 'Stationwide coverage' : ($action->barangay?->barangay_name ?? 'Unknown barangay')) }}</div>
                                                        <div class="note">{{ $action->riskArea?->marker?->title ?? 'No linked location marker' }}</div>
                                                    </td>
                                                    <td data-label="Priority / Stage"><div class="chip-row"><span class="priority-pill is-{{ $priorityTone }}">{{ strtoupper($action->priority_level) }}</span><span class="state-label is-{{ $action->status }}">{{ strtoupper($statusLabel) }}</span>@if($action->completion_approval_status === 'pending')<span class="register-tag register-tag--revision"><i class="fa-solid fa-user-check"></i> Completion review</span>@endif</div></td>
                                                    <td data-label="Owner / Target">
                                                        <div class="register-meta">
                                                            <div class="owner-chip">
                                                                <span class="owner-avatar {{ $ownerName ? '' : 'is-empty' }}">{{ $ownerName ? $ownerInitials : '' }}</span>
                                                                <div class="register-owner {{ $ownerName ? '' : 'is-unassigned' }}">{{ $ownerName ?? 'Unassigned' }}</div>
                                                            </div>
                                                            <div class="register-due"><i class="fa-regular fa-calendar"></i> {{ $action->target_date?->format('M d, Y') ?? 'No target date' }}</div>
                                                        </div>
                                                    </td>
                                                    <td data-label="Source" class="register-col-source">
                                                        <div class="chip-row">
                                                            <span class="tag {{ $sourceType }}">{{ $sourceLabel }}</span>
                                                            @if($action->aiActionPlanChecklist)
                                                                <span class="chip"><i class="fa-solid fa-list-check"></i> Linked checklist</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td data-label="Actions">
                                                        <div class="row-actions">
                                                            <button type="button" class="row-btn view primary-view" title="View details" aria-label="View details" data-open-details-modal data-payload='@json($payload)'><i class="fa-solid fa-eye"></i><span>View</span></button>
                                                            <button type="button" class="row-btn edit" title="Edit action" aria-label="Edit action" data-open-edit-modal data-payload='@json($payload)'><i class="fa-solid fa-pen"></i></button>
                                                            @if($action->completion_approval_status === 'pending')
                                                                <form method="POST" action="{{ route('station-admin.mitigations.completion-review', $action->getKey()) }}" style="display:inline">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="row-btn view" title="Approve completion" aria-label="Approve completion"><i class="fa-solid fa-check"></i></button></form>
                                                                <form method="POST" action="{{ route('station-admin.mitigations.completion-review', $action->getKey()) }}" style="display:inline" onsubmit="const reason=window.prompt('Why is this completion being returned?');if(!reason||!reason.trim())return false;this.elements.decision_notes.value=reason.trim();">@csrf<input type="hidden" name="decision" value="rejected"><input type="hidden" name="decision_notes" value=""><button type="submit" class="row-btn delete" title="Return for further work" aria-label="Return for further work"><i class="fa-solid fa-rotate-left"></i></button></form>
                                                            @endif
                                                            <details class="action-menu">
                                                                <summary class="row-btn" title="More actions" aria-label="More actions"><i class="fa-solid fa-ellipsis"></i></summary>
                                                                <div class="action-menu__panel">
                                                                    <button type="button" class="action-menu__item" data-open-delete-modal data-id="{{ $action->mitigation_action_id }}" data-title="{{ $action->title }}"><i class="fa-solid fa-trash"></i><span>Delete plan</span></button>
                                                                </div>
                                                            </details>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @include('chief.partials.pagination', [
                                    'paginator' => $mitigationActions,
                                    'ariaLabel' => 'Mitigation register pagination',
                                    'summaryLabel' => 'results',
                                ])
                            @endif
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="mitigation-dialog" id="detailsModal" aria-hidden="true">
        <div class="mitigation-dialog__card details-dialog" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
            <div class="mitigation-dialog__head">
                <div class="mitigation-dialog__title"><span class="mitigation-dialog__title-icon"><i class="fa-solid fa-shield-heart"></i></span><div><h3 id="detailsTitle">Mitigation details</h3><p id="detailsSubtitle">Plan context, ownership, progress, and evidence.</p></div></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal aria-label="Close mitigation details"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="details-dialog__body" id="detailsContent"></div>
            <div class="details-dialog__actions">
                <a href="#" class="filter-btn secondary" id="detailsChecklistLink" hidden><i class="fa-solid fa-list-check"></i><span>View Checklist</span></a>
                <button type="button" class="filter-btn primary" id="detailsEditButton" hidden><i class="fa-solid fa-pen"></i><span>Edit / Update</span></button>
            </div>
        </div>
    </div>

    <div class="attachment-lightbox" id="mitigationAttachmentLightbox" role="dialog" aria-modal="true" aria-label="Proof image preview" hidden>
        <button type="button" class="attachment-lightbox__button" data-lightbox-previous aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
        <figure class="attachment-lightbox__figure"><img data-lightbox-image src="" alt=""><figcaption data-lightbox-caption></figcaption></figure>
        <button type="button" class="attachment-lightbox__button" data-lightbox-next aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
        <button type="button" class="attachment-lightbox__button attachment-lightbox__close" data-lightbox-close aria-label="Close image preview"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mitigation-dialog" id="createModal" aria-hidden="true">
        <div class="mitigation-dialog__card mitigation-form-dialog" role="dialog" aria-modal="true" aria-labelledby="createMitigationTitle">
            <div class="mitigation-dialog__head">
                <div class="mitigation-dialog__title"><span class="mitigation-dialog__title-icon"><i class="fa-solid fa-shield-halved"></i></span><div><h3 id="createMitigationTitle">Create mitigation plan</h3><p id="createMitigationSubtitle">Start manually or generate an editable draft from an existing checklist.</p></div></div>
                <button type="button" class="mitigation-dialog__close" data-close-modal aria-label="Close create mitigation"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="createForm" action="{{ route('station-admin.mitigations.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="mitigation_form_context" value="create">
                @if($errors->any() && old('mitigation_form_context') === 'create')
                    <div class="mitigation-form-errors" role="alert"><strong>Please correct the mitigation form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
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
                <input type="hidden" name="mitigation_form_context" value="edit">
                <input type="hidden" name="mitigation_action_id" value="{{ old('mitigation_action_id') }}">
                @if($errors->any() && old('mitigation_form_context') === 'edit')
                    <div class="mitigation-form-errors" role="alert"><strong>Please correct the mitigation form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
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
            const createForm = document.getElementById('createForm');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const detailsModal = document.getElementById('detailsModal');
            const editForm = document.getElementById('editForm');
            const deleteForm = document.getElementById('deleteForm');
            const deleteMessage = document.getElementById('deleteMessage');
            const detailsTitle = document.getElementById('detailsTitle');
            const detailsSubtitle = document.getElementById('detailsSubtitle');
            const detailsContent = document.getElementById('detailsContent');
            const detailsChecklistLink = document.getElementById('detailsChecklistLink');
            const detailsEditButton = document.getElementById('detailsEditButton');
            const attachmentLightbox = document.getElementById('mitigationAttachmentLightbox');
            const lightboxImage = attachmentLightbox?.querySelector('[data-lightbox-image]');
            const lightboxCaption = attachmentLightbox?.querySelector('[data-lightbox-caption]');
            let lightboxItems = [];
            let lightboxIndex = 0;
            let currentDetailsPayload = null;
            const updateBaseUrl = @json(route('station-admin.mitigations.update', ['mitigationActionId' => '__ID__']));
            const deleteBaseUrl = @json(route('station-admin.mitigations.destroy', ['mitigationActionId' => '__ID__']));
            const checklistDraftUrl = @json(request()->routeIs('admin.*')
                ? route('admin.mitigations.checklist-draft')
                : route('station-admin.mitigations.checklist-draft'));
            const menuToggle = document.getElementById('mitigationUserMenuToggle');
            const menu = document.getElementById('mitigationUserMenu');
            document.querySelector('[data-suggestion-scan-form]')?.addEventListener('submit', (event) => {
                const button = event.currentTarget.querySelector('button[type="submit"]');
                if (!button || button.disabled) return;
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Scanning...</span>';
            });

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

            const priorityTone = (priority) => {
                const normalized = String(priority || 'medium').toLowerCase();
                if (normalized === 'critical' || normalized === 'high') return 'high';
                if (normalized === 'low') return 'low';
                return 'medium';
            };

            const removeLeadingLocation = (title, ...locations) => {
                let cleaned = String(title || '').trim();
                locations.filter(Boolean).forEach((location) => {
                    const escaped = String(location).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    cleaned = cleaned.replace(new RegExp(`^\\s*${escaped}\\s*(?:[-–—:|]\\s*)?`, 'iu'), '').trim();
                });
                return cleaned || 'Mitigation plan';
            };

            const initialsFor = (name) => String(name || '')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('') || 'UN';

            const renderBadge = (label, className) => `<span class="tag ${escapeHtml(className)}">${escapeHtml(label)}</span>`;

            const checklistDraftBuilder = createForm?.querySelector('[data-checklist-draft-builder]');
            const checklistDraftSource = checklistDraftBuilder?.querySelector('[data-checklist-draft-source]');
            const checklistDraftItems = checklistDraftBuilder?.querySelector('[data-checklist-draft-items]');
            const checklistDraftButton = checklistDraftBuilder?.querySelector('[data-generate-checklist-draft]');
            const checklistDraftStatus = checklistDraftBuilder?.querySelector('[data-checklist-draft-status]');
            const recommendedActionsPreview = createForm?.querySelector('[data-recommended-actions-preview]');
            const createMitigationTitle = document.getElementById('createMitigationTitle');
            const createMitigationSubtitle = document.getElementById('createMitigationSubtitle');

            const setCreateField = (name, value, fallback = '') => {
                const field = createForm?.querySelector(`[name="${name}"]`);
                if (field) field.value = value ?? fallback;
            };

            const requestChecklistDraft = async (checklistId, itemIds = null) => {
                const response = await fetch(checklistDraftUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        checklist_id: Number(checklistId),
                        ...(Array.isArray(itemIds) ? { item_ids: itemIds } : {}),
                    }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.success) {
                    const validationMessage = payload?.errors
                        ? Object.values(payload.errors).flat().find(Boolean)
                        : null;
                    throw new Error(validationMessage || payload?.message || 'Unable to generate the mitigation draft.');
                }

                return payload.data;
            };

            const renderChecklistDraftItems = (items) => {
                if (!checklistDraftItems) return;
                if (!Array.isArray(items) || !items.length) {
                    checklistDraftItems.innerHTML = '<small>No stored checklist items are available for this recommendation.</small>';
                    checklistDraftItems.hidden = false;
                    checklistDraftButton.disabled = true;
                    return;
                }

                checklistDraftItems.innerHTML = `
                    <label><i class="fa-solid fa-square-check"></i><span>Select recommended actions</span><em>${items.length} available</em></label>
                    <div class="task-list">
                        ${items.map((item) => `
                            <label class="task-item">
                                <input type="checkbox" value="${escapeHtml(item.id)}" data-checklist-draft-item checked>
                                <span><strong>${escapeHtml(item.text)}</strong><small>${escapeHtml(String(item.status || 'pending').replace('_', ' '))}</small></span>
                            </label>
                        `).join('')}
                    </div>
                    <small>Only selected items will be used to structure the draft. The original checklist will not be changed.</small>
                `;
                checklistDraftItems.hidden = false;
                checklistDraftButton.disabled = false;
            };

            const renderRecommendedActionsPreview = (actions) => {
                if (!recommendedActionsPreview) return;
                recommendedActionsPreview.innerHTML = `
                    <div class="mitigation-workflow-note" style="margin-top:10px;">
                        <span class="mitigation-workflow-note__icon"><i class="fa-solid fa-list-check"></i></span>
                        <div><strong>Recommended actions used</strong><ul style="margin:6px 0 0;padding-left:18px;">${actions.map((action) => `<li>${escapeHtml(action)}</li>`).join('')}</ul></div>
                    </div>
                `;
                recommendedActionsPreview.hidden = false;
            };

            checklistDraftSource?.addEventListener('change', async () => {
                const checklistId = checklistDraftSource.value;
                checklistDraftItems.hidden = true;
                checklistDraftItems.innerHTML = '';
                checklistDraftButton.disabled = true;
                if (!checklistId) {
                    checklistDraftStatus.textContent = 'Choose a checklist to begin. Nothing will be saved until you submit this form.';
                    return;
                }

                checklistDraftStatus.textContent = 'Loading stored checklist items...';
                try {
                    const draft = await requestChecklistDraft(checklistId);
                    renderChecklistDraftItems(draft.available_items);
                    checklistDraftStatus.textContent = 'Select the actions to include, then generate the editable draft.';
                } catch (error) {
                    checklistDraftStatus.textContent = error.message;
                }
            });

            checklistDraftButton?.addEventListener('click', async () => {
                const checklistId = checklistDraftSource?.value;
                const itemIds = Array.from(checklistDraftItems?.querySelectorAll('[data-checklist-draft-item]:checked') || [])
                    .map((input) => Number(input.value))
                    .filter(Number.isFinite);
                if (!checklistId || !itemIds.length) {
                    checklistDraftStatus.textContent = 'Select at least one recommended checklist action.';
                    return;
                }

                checklistDraftButton.disabled = true;
                checklistDraftStatus.textContent = 'Structuring the selected recommendations into a professional mitigation draft...';
                try {
                    const draft = await requestChecklistDraft(checklistId, itemIds);
                    setCreateField('scope_type', draft.scope_type, 'barangay');
                    setCreateField('barangay_id', draft.barangay_id);
                    createForm?.querySelector('[name="barangay_id"]')?.dispatchEvent(new Event('change'));
                    setCreateField('title', draft.title);
                    setCreateField('category', draft.category, 'other');
                    setCreateField('priority_level', draft.priority_level, 'medium');
                    setCreateField('status', 'draft');
                    setCreateField('target_date', draft.target_date);
                    setCreateField('description', draft.description);
                    setCreateField('baseline_condition', draft.baseline_condition);
                    setCreateField('expected_result', draft.expected_result);
                    setCreateField('remarks', draft.remarks);
                    setCreateField('source_type', 'ai_action_plan');
                    setCreateField('risk_area_id', draft.risk_area_id);
                    setCreateField('ai_action_plan_checklist_id', draft.ai_action_plan_checklist_id);
                    renderRecommendedActionsPreview(draft.recommended_actions || []);
                    createMitigationTitle.textContent = draft.label || 'AI-Assisted Mitigation Draft';
                    createMitigationSubtitle.textContent = draft.helper_text || 'Generated from the recommended checklist. Review and edit before saving.';
                    checklistDraftStatus.textContent = 'Draft generated. Review and edit every field before selecting Save mitigation.';
                    createForm?.querySelector('[name="title"]')?.focus({ preventScroll: true });
                } catch (error) {
                    checklistDraftStatus.textContent = error.message;
                } finally {
                    checklistDraftButton.disabled = false;
                }
            });

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

                const imagePattern = /\.(?:avif|gif|jpe?g|png|webp)(?:$|[?#])/i;
                return `<div class="attachment-gallery">${attachments.map((attachment) => {
                    const url = escapeHtml(attachment.url || '');
                    const name = escapeHtml(attachment.name || 'Attachment');
                    if (imagePattern.test(String(attachment.url || '')) || imagePattern.test(String(attachment.name || ''))) {
                        return `<a class="attachment-thumb" href="${url}" target="_blank" rel="noopener" title="Preview ${name}" data-attachment-image data-image-url="${url}" data-image-name="${name}"><img src="${url}" alt="${name}" loading="lazy" decoding="async"><span>${name}</span></a>`;
                    }
                    return `<a class="attachment-file" href="${url}" target="_blank" rel="noopener"><i class="fa-solid fa-file-arrow-down"></i><span>${name}</span></a>`;
                }).join('')}</div>`;
            };

            const showLightboxImage = () => {
                const item = lightboxItems[lightboxIndex];
                if (!item || !lightboxImage || !lightboxCaption) return;
                lightboxImage.src = item.url;
                lightboxImage.alt = item.name;
                lightboxCaption.textContent = `${item.name} · ${lightboxIndex + 1} of ${lightboxItems.length}`;
                attachmentLightbox.querySelector('[data-lightbox-previous]').hidden = lightboxItems.length < 2;
                attachmentLightbox.querySelector('[data-lightbox-next]').hidden = lightboxItems.length < 2;
            };

            const openAttachmentLightbox = (trigger) => {
                const nodes = Array.from(detailsContent.querySelectorAll('[data-attachment-image]'));
                lightboxItems = nodes.map((node) => ({ url: node.dataset.imageUrl, name: node.dataset.imageName || 'Proof image' }));
                lightboxIndex = Math.max(0, nodes.indexOf(trigger));
                showLightboxImage();
                attachmentLightbox.hidden = false;
                attachmentLightbox.querySelector('[data-lightbox-close]')?.focus();
            };

            const closeAttachmentLightbox = () => {
                if (!attachmentLightbox || attachmentLightbox.hidden) return;
                attachmentLightbox.hidden = true;
                if (lightboxImage) lightboxImage.src = '';
                lightboxItems = [];
            };

            detailsContent?.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-attachment-image]');
                if (!trigger) return;
                event.preventDefault();
                openAttachmentLightbox(trigger);
            });
            attachmentLightbox?.querySelector('[data-lightbox-close]')?.addEventListener('click', closeAttachmentLightbox);
            attachmentLightbox?.querySelector('[data-lightbox-previous]')?.addEventListener('click', () => {
                lightboxIndex = (lightboxIndex - 1 + lightboxItems.length) % lightboxItems.length;
                showLightboxImage();
            });
            attachmentLightbox?.querySelector('[data-lightbox-next]')?.addEventListener('click', () => {
                lightboxIndex = (lightboxIndex + 1) % lightboxItems.length;
                showLightboxImage();
            });
            attachmentLightbox?.addEventListener('click', (event) => {
                if (event.target === attachmentLightbox) closeAttachmentLightbox();
            });

            const renderCompactDetails = (payload, followUpText) => `
                <section class="detail-hero">
                    <div class="detail-hero__badges">
                        ${renderBadge(payload.risk_level || 'UNKNOWN RISK', payload.priority_level || 'medium')}
                        ${renderBadge(payload.status_label || 'Draft', payload.status || 'draft')}
                        ${renderBadge(payload.source_label || 'Manual', payload.source_type || 'manual')}
                    </div>
                    <p class="detail-hero__copy">${escapeHtml(payload.description || 'No mitigation objective has been recorded.')}</p>
                </section>
                <div class="detail-facts">
                    <div class="detail-fact"><span>Category</span><strong>${escapeHtml(payload.category_label || 'Other')}</strong></div>
                    <div class="detail-fact"><span>Owner</span><strong>${escapeHtml(payload.responsible_user_label || payload.responsible_team_label || 'Unassigned')}</strong></div>
                    <div class="detail-fact"><span>Target</span><strong>${escapeHtml(payload.target_date_label || 'Not scheduled')}</strong></div>
                    <div class="detail-fact"><span>Progress</span><strong>${escapeHtml(payload.progress_percent || 0)}%</strong></div>
                </div>
                <div class="detail-disclosures">
                    <section class="detail-disclosure">
                        <div class="detail-disclosure__heading"><span class="detail-disclosure__icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span><span class="detail-disclosure__label"><strong>Evidence and rationale</strong><span>Risk factors, recommendation, and source links</span></span></div>
                        <div class="detail-disclosure__content"><div class="detail-content-grid">
                            <div class="detail-content-item is-wide"><span>Contributing factors</span><div>${renderList(payload.factors, 'No contributing factors attached.')}</div></div>
                            <div class="detail-content-item is-wide"><span>Recommendation</span><div>${escapeHtml(payload.recommendation || 'No linked recommendation recorded.')}</div></div>
                            <div class="detail-content-item"><span>Analysis</span><div>${escapeHtml(payload.analysis_label || 'No linked analysis')}</div></div>
                            <div class="detail-content-item"><span>AI checklist</span><div>${escapeHtml(payload.checklist_label || 'No linked AI checklist')}</div></div>
                            <div class="detail-content-item is-wide"><span>Traceability</span><div>${renderList(payload.source_references, 'No source references attached.')}</div></div>
                        </div></div>
                    </section>
                    <section class="detail-disclosure">
                        <div class="detail-disclosure__heading"><span class="detail-disclosure__icon"><i class="fa-solid fa-list-check"></i></span><span class="detail-disclosure__label"><strong>Execution and checklist</strong><span>Ownership, tasks, approval, and current updates</span></span></div>
                        <div class="detail-disclosure__content"><div class="detail-content-grid">
                            <div class="detail-content-item"><span>Responsible team</span><div>${escapeHtml(payload.responsible_team_label || 'Not assigned')}</div></div>
                            <div class="detail-content-item"><span>Responsible person</span><div>${escapeHtml(payload.responsible_user_label || 'Not assigned')}</div></div>
                            <div class="detail-content-item"><span>Approval</span><div>${escapeHtml(payload.approval_label || 'Not approved yet')}</div></div>
                            <div class="detail-content-item"><span>Approved at</span><div>${escapeHtml(payload.approval_date_label || 'Pending')}</div></div>
                            <div class="detail-content-item is-wide"><span>Checklist tasks</span><div>${renderTasks(payload.tasks, payload.tasks_done || 0, payload.tasks_total || 0)}</div></div>
                            <div class="detail-content-item is-wide"><span>Implementation updates</span><div>${escapeHtml(payload.implementation_notes || 'No implementation updates recorded yet.')}</div></div>
                            <div class="detail-content-item is-wide"><span>Blocked reason</span><div>${escapeHtml(payload.blocked_reason || 'Not blocked')}</div></div>
                            <div class="detail-content-item is-wide"><span>Planning remarks</span><div>${escapeHtml(payload.remarks || 'No planning remarks recorded.')}</div></div>
                        </div></div>
                    </section>
                    <section class="detail-disclosure">
                        <div class="detail-disclosure__heading"><span class="detail-disclosure__icon"><i class="fa-solid fa-file-shield"></i></span><span class="detail-disclosure__label"><strong>Results and documentation</strong><span>Proof files, completion notes, and follow-up</span></span></div>
                        <div class="detail-disclosure__content"><div class="detail-content-grid">
                            <div class="detail-content-item is-wide"><span>Attachments</span><div>${renderAttachments(payload.attachments)}</div></div>
                            <div class="detail-content-item is-wide"><span>Condition before work</span><div>${escapeHtml(payload.baseline_condition || 'No baseline recorded.')}</div></div>
                            <div class="detail-content-item is-wide"><span>Expected result</span><div>${escapeHtml(payload.expected_result || 'No expected result recorded.')}</div></div>
                            <div class="detail-content-item is-wide"><span>Completion notes</span><div>${escapeHtml(payload.completion_notes || 'No completion notes recorded yet.')}</div></div>
                            <div class="detail-content-item is-wide"><span>Outcome</span><div>${escapeHtml(payload.outcome_result || 'No outcome recorded yet.')}</div></div>
                            <div class="detail-content-item"><span>Effectiveness</span><div>${escapeHtml(payload.effectiveness || 'Pending evaluation')}</div></div>
                            <div class="detail-content-item"><span>Verified</span><div>${escapeHtml(payload.verification_date_label ? `${payload.verification_date_label}${payload.verified_by_label ? ` by ${payload.verified_by_label}` : ''}` : 'Not verified')}</div></div>
                            <div class="detail-content-item"><span>Follow-up required</span><div>${escapeHtml(followUpText)}</div></div>
                            <div class="detail-content-item"><span>Follow-up date</span><div>${escapeHtml(payload.follow_up_date_label || 'Not scheduled')}</div></div>
                            <div class="detail-content-item is-wide"><span>Follow-up notes</span><div>${escapeHtml(payload.follow_up_notes || 'No follow-up notes recorded.')}</div></div>
                        </div></div>
                    </section>
                </div>
            `;

            const renderDetails = (payload) => {
                currentDetailsPayload = payload;
                detailsTitle.textContent = payload.title || 'Mitigation Details';
                const followUpText = payload.follow_up_required ? 'Yes' : 'No';
                detailsSubtitle.textContent = [payload.scope_label || 'Barangay', payload.barangay_label || 'Unknown barangay', payload.location_label].filter(Boolean).join(' · ');

                detailsContent.innerHTML = renderCompactDetails(payload, followUpText);
                if (detailsChecklistLink) {
                    detailsChecklistLink.hidden = !payload.checklist_url;
                    detailsChecklistLink.href = payload.checklist_url || '#';
                }
                if (detailsEditButton) detailsEditButton.hidden = !payload.id;
                return;

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
            if (checklistDraftSource?.value) {
                checklistDraftSource.dispatchEvent(new Event('change'));
            }

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
                if (attachmentLightbox && !attachmentLightbox.hidden) {
                    closeAttachmentLightbox();
                    return;
                }
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
                    const mitigationIdField = editForm.querySelector('[name="mitigation_action_id"]');
                    if (mitigationIdField) mitigationIdField.value = String(payload.id);
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
                    set('baseline_condition', payload.baseline_condition);
                    set('expected_result', payload.expected_result);
                    set('remarks', payload.remarks);
                    set('implementation_notes', payload.implementation_notes);
                    set('blocked_reason', payload.blocked_reason);
                    set('completion_notes', payload.completion_notes);
                    set('outcome_result', payload.outcome_result);
                    set('effectiveness', payload.effectiveness_key, 'pending_evaluation');
                    set('verification_date', payload.verification_date);
                    set('follow_up_required', payload.follow_up_required ? '1' : '0');
                    set('follow_up_date', payload.follow_up_date);
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

            detailsEditButton?.addEventListener('click', () => {
                if (!currentDetailsPayload?.id) return;
                const matchingEdit = Array.from(document.querySelectorAll('#mitigationTableData [data-open-edit-modal]')).find((button) => {
                    try { return String(JSON.parse(button.getAttribute('data-payload') || '{}').id) === String(currentDetailsPayload.id); }
                    catch (error) { return false; }
                });
                if (!matchingEdit) return;
                closeModal(detailsModal);
                matchingEdit.click();
            });

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
                    const renderPager = () => {
                        const currentPage = Number(meta.current_page || 1);
                        const lastPage = Number(meta.last_page || 1);
                        const total = Number(meta.total || 0);
                        const perPage = Number(meta.per_page || 10);
                        const from = total > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
                        const to = rows.length ? from + rows.length - 1 : 0;
                        const pages = Array.from({ length: lastPage }, (_, index) => index + 1)
                            .filter((page) => page === 1 || page === lastPage || Math.abs(page - currentPage) <= 1);
                        let previousPage = null;
                        const pageLinks = pages.map((page) => {
                            const ellipsis = previousPage && page - previousPage > 1
                                ? '<span class="chief-pagination__ellipsis" aria-hidden="true">…</span>'
                                : '';
                            previousPage = page;
                            const pageButton = page === currentPage
                                ? `<span class="chief-pagination__current" aria-current="page">${escapeHtml(page)}</span>`
                                : `<a class="chief-pagination__link" href="${escapeHtml(pageLink(page))}" aria-label="Go to page ${escapeHtml(page)}">${escapeHtml(page)}</a>`;
                            return `${ellipsis}${pageButton}`;
                        }).join('');
                        const previousLink = currentPage <= 1
                            ? '<span class="chief-pagination__disabled" aria-disabled="true" aria-label="Previous page">‹</span>'
                            : `<a class="chief-pagination__link" href="${escapeHtml(pageLink(currentPage - 1))}" rel="prev" aria-label="Previous page">‹</a>`;
                        const nextLink = currentPage >= lastPage
                            ? '<span class="chief-pagination__disabled" aria-disabled="true" aria-label="Next page">›</span>'
                            : `<a class="chief-pagination__link" href="${escapeHtml(pageLink(currentPage + 1))}" rel="next" aria-label="Next page">›</a>`;

                        return `<nav class="chief-pagination" aria-label="Mitigation register pagination"><div class="chief-pagination__summary">Showing <strong>${escapeHtml(from)}–${escapeHtml(to)}</strong> of <strong>${escapeHtml(total)}</strong> results</div><div class="chief-pagination__controls">${previousLink}${pageLinks}${nextLink}</div></nav>`;
                    };
                    table.innerHTML = rows.length ? `
                        <div class="table-wrap"><table class="mitigation-register-table"><thead><tr><th class="register-plan-col">Plan and coverage</th><th class="register-status-col">Status</th><th class="register-owner-col">Owner and due date</th><th class="register-actions-col">Open</th></tr></thead><tbody>
                        ${rows.map((item) => {
                            const editPayload = {...item, target_date:item.target_date_value};
                            const tone = priorityTone(item.priority);
                            const locationHeading = item.barangay || 'Unknown barangay';
                            const planSubtitle = removeLeadingLocation(item.title || 'Untitled', locationHeading, item.location);
                            const isAiActionPlan = String(item.source || '').toLowerCase().replace(/\s+/g, '_') === 'ai_action_plan';
                            const ownerName = item.responsible || '';
                            return `<tr id="mitigation-row-${escapeHtml(item.id)}" class="mitigation-register-row mitigation-register-row--${escapeHtml(tone)}"><td data-label="Plan"><span class="mitigation-priority-bar is-${escapeHtml(tone)}" aria-hidden="true"></span><div class="register-plan"><div class="text-base font-extrabold leading-tight text-slate-950">${escapeHtml(locationHeading)}</div><div class="mt-1 text-sm leading-snug text-slate-500">${escapeHtml(planSubtitle)}</div></div><div class="register-tags"><span class="register-tag register-tag--category">${escapeHtml(item.category || 'other')}</span><span class="register-tag ${isAiActionPlan ? 'register-tag--ai' : 'register-tag--category'}">${escapeHtml(item.source || 'manual')}</span>${item.requires_revision_review ? `<span class="register-tag register-tag--revision"><i class="fa-solid fa-flag"></i> Review revision ${escapeHtml(item.analysis_revision || '')}</span>` : ''}</div></td><td data-label="Status"><div class="chip-row"><span class="priority-pill is-${escapeHtml(tone)}">${escapeHtml(item.priority || 'medium')}</span><span class="state-label is-${escapeHtml(item.status || 'draft')}">${escapeHtml(item.status || 'draft')}</span></div></td><td data-label="Owner / Target"><div class="register-meta"><div class="owner-chip"><span class="owner-avatar ${ownerName ? '' : 'is-empty'}">${ownerName ? escapeHtml(initialsFor(ownerName)) : ''}</span><div class="register-owner ${ownerName ? '' : 'is-unassigned'}">${escapeHtml(ownerName || 'Unassigned')}</div></div><div class="register-due"><i class="fa-regular fa-calendar"></i> ${escapeHtml(item.target_date || 'No target date')}</div></div></td><td data-label="Actions"><div class="row-actions"><a class="row-btn view primary-view" href="{{ route('station-admin.mitigations') }}?mitigation_action_id=${encodeURIComponent(item.id)}#mitigation-row-${encodeURIComponent(item.id)}" title="Open mitigation" aria-label="Open mitigation"><i class="fa-solid fa-eye"></i><span>View</span></a><button type="button" class="row-btn edit" data-open-edit-modal data-payload="${escapeHtml(JSON.stringify(editPayload))}" title="Edit mitigation" aria-label="Edit mitigation"><i class="fa-solid fa-pen"></i></button><details class="action-menu"><summary class="row-btn" title="More actions" aria-label="More actions"><i class="fa-solid fa-ellipsis"></i></summary><div class="action-menu__panel"><button type="button" class="action-menu__item" data-open-delete-modal data-id="${escapeHtml(item.id)}" data-title="${escapeHtml(item.title || 'this mitigation')}"><i class="fa-solid fa-trash"></i><span>Delete plan</span></button></div></details></div></td></tr>`;
                        }).join('')}
                        </tbody></table></div>
                        ${renderPager()}` : '<div class="empty"><i class="fa-solid fa-filter-circle-xmark"></i> No matching mitigation plans.</div>';
                    table.style.opacity = '1';
                    table.setAttribute('aria-busy', 'false');
                    window.history.replaceState({}, '', url);
                    bindMitigationTableActions();
                } catch (error) {
                    console.error(error);
                    table.style.opacity = '1';
                    table.setAttribute('aria-busy', 'false');
                }
            };

            const mitigationFilters = document.getElementById('mitigationFilters');
            const mitigationSort = document.querySelector('[data-mitigation-sort]');
            const mitigationSortValue = mitigationFilters?.querySelector('[data-mitigation-sort-value]');
            mitigationSort?.addEventListener('change', () => {
                if (mitigationSortValue) mitigationSortValue.value = mitigationSort.value;
                const params = new URLSearchParams(new FormData(mitigationFilters));
                params.delete('page');
                loadMitigationTable(`${mitigationFilters.action}?${params.toString()}`);
            });
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
                const link = event.target.closest('#mitigationTableData .chief-pagination a');
                if (!link) return;
                event.preventDefault();
                loadMitigationTable(link.href);
            });
            bindMitigationTableActions();
            window.addEventListener('pyro:mitigations-mutated', () => {
                [createModal, editModal, deleteModal, detailsModal].forEach(closeModal);
                loadMitigationTable(window.location.href);
            });
            const requestedMitigationId = new URLSearchParams(window.location.search).get('mitigation_action_id');
            if (requestedMitigationId) {
                document.querySelector(`#mitigation-row-${CSS.escape(requestedMitigationId)} [data-open-details-modal]`)?.click();
            }

            if (menuToggle && menu) {
                menuToggle.addEventListener('click', () => menu.classList.toggle('show'));
                document.addEventListener('click', (event) => {
                    if (!menuToggle.contains(event.target) && !menu.contains(event.target)) {
                        menu.classList.remove('show');
                    }
                });
            }

            @if($errors->any())
                @if(old('mitigation_form_context') === 'edit' && old('mitigation_action_id'))
                    editForm.action = updateBaseUrl.replace('__ID__', @json((string) old('mitigation_action_id')));
                    openModal(editModal);
                @else
                    openModal(createModal);
                @endif
            @endif

            @if(!empty($autoOpenCreateModal))
                openModal(createModal);
            @endif
        });
    </script>
</body>
</html>
