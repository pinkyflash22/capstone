<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · Action Plan Checklists</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <style>
        :root{--ink:#152238;--muted:#65748b;--line:#e7dfd5;--paper:#fffdfa;--soft:#fff7ed;--orange:#d86616;--red:#c63820;--gold:#e99a14;--green:#15966f;--blue:#3173d9}
        *{box-sizing:border-box}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        body{margin:0;background:linear-gradient(180deg,#fff7ed 0,#f7f8fa 340px);color:var(--ink);font-family:Inter,system-ui,sans-serif}.checklist-shell{display:flex;min-height:100vh}.checklist-main{flex:1;min-width:0;padding:20px}.checklist-container{width:100%;max-width:1280px;margin:auto}
        .checklist-topbar{position:sticky;top:14px;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:14px 20px;margin-bottom:16px;background:rgba(255,255,255,.93);border:1px solid rgba(231,223,213,.9);border-radius:24px;box-shadow:0 15px 38px rgba(39,46,59,.08);backdrop-filter:blur(14px)}.checklist-topbar__title{display:flex;align-items:center;gap:13px}.checklist-topbar__icon{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#fff1e7;color:var(--red)}.checklist-topbar h1{font-size:19px;line-height:1.15;margin:0}.checklist-topbar p{font-size:12px;color:var(--muted);margin:4px 0 0}.mobile-menu,.profile-pill{border:0;cursor:pointer}.mobile-menu{display:none;background:#f6f7f9;width:40px;height:40px;border-radius:13px;color:var(--ink)}.profile-pill{display:flex;align-items:center;gap:9px;padding:7px 13px 7px 7px;border:1px solid #f3d8b8;border-radius:999px;background:#fffaf0;color:var(--ink);font-weight:750}.profile-pill span:first-child{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#e54a10);color:#fff;font-size:11px}
        .forecast-hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:18px 20px;margin-bottom:14px;border:1px solid #f0d9c1;border-radius:20px;background:radial-gradient(circle at 90% 12%,rgba(245,158,11,.14),transparent 30%),linear-gradient(135deg,#fff,#fff8f0);box-shadow:0 10px 28px rgba(107,66,25,.05)}.forecast-hero__eyebrow,.checklist-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#a43b1a;font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:.1em}.forecast-hero h2{font-size:clamp(20px,2vw,26px);letter-spacing:-.03em;margin:5px 0}.forecast-hero p{max-width:730px;margin:0;color:var(--muted);font-size:12px;line-height:1.5}.workflow-nav{display:flex;gap:6px;padding:5px;background:#fff;border:1px solid var(--line);border-radius:14px}.workflow-nav a{display:flex;align-items:center;gap:7px;padding:8px 10px;border-radius:10px;text-decoration:none;color:#6d7481;font-size:11px;font-weight:800}.workflow-nav a.active{background:linear-gradient(135deg,#cf3f24,#e88313);color:#fff;box-shadow:0 6px 14px rgba(207,63,36,.16)}
        .checklist-workspace{display:grid;gap:12px}.checklist-workspace[aria-busy=true]{opacity:.58;pointer-events:none}.checklist-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.checklist-metric{display:flex;align-items:center;gap:9px;min-height:58px;padding:9px 12px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.94);box-shadow:0 6px 18px rgba(39,46,59,.035)}.checklist-metric__icon{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#fff3e8;color:var(--orange)}.checklist-metric small,.checklist-metric strong{display:block}.checklist-metric small{font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:#8590a2;font-weight:800}.checklist-metric strong{font-size:18px;margin-top:1px}.checklist-metric--amber .checklist-metric__icon{background:#fff7d9;color:#c57b00}.checklist-metric--blue .checklist-metric__icon{background:#eaf2ff;color:var(--blue)}.checklist-metric--green .checklist-metric__icon{background:#e6f8f1;color:var(--green)}
        .checklist-register{border:1px solid var(--line);border-radius:26px;background:var(--paper);box-shadow:0 18px 44px rgba(39,46,59,.07);overflow:hidden}.checklist-register__head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:22px 24px 15px}.checklist-register__head h2{font-size:20px;margin:7px 0 3px}.checklist-register__head p{margin:0;color:var(--muted);font-size:13px}.checklist-result-count{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;background:var(--soft);border:1px solid #f6d4b1;border-radius:999px;color:#a43b1a;font-size:11px;font-weight:800;white-space:nowrap}
        .checklist-filters{display:grid;grid-template-columns:minmax(230px,1fr) repeat(3,minmax(135px,180px)) 40px 40px;gap:9px;padding:0 24px 17px}.checklist-filters input,.checklist-filters select{width:100%;height:40px;border:1px solid #ddd8d2;border-radius:12px;background:#fff;padding:0 12px;color:#344055;font:inherit;font-size:12px;outline:0}.checklist-filters input:focus,.checklist-filters select:focus,.modal-review textarea:focus{border-color:#e67d35;box-shadow:0 0 0 3px rgba(230,125,53,.12)}.checklist-search{position:relative}.checklist-search i{position:absolute;left:13px;top:13px;color:#9aa3b1;font-size:12px}.checklist-search input{padding-left:35px}.icon-button{display:inline-grid;place-items:center;width:36px;height:36px;border:1px solid #ddd8d2;border-radius:11px;background:#fff;color:#667085;text-decoration:none;cursor:pointer;transition:.18s}.checklist-filters>.icon-button{width:40px;height:40px}.icon-button:hover{transform:translateY(-1px);border-color:#e99a5a;color:#bd481f}.icon-button--primary{border-color:transparent;background:linear-gradient(135deg,#cf3f24,#e88313);color:#fff;box-shadow:0 7px 16px rgba(207,63,36,.18)}
        .checklist-table-wrap{overflow-x:auto;border-top:1px solid #eee8e1}.checklist-table{width:100%;border-collapse:collapse;table-layout:fixed;min-width:760px}.checklist-table th{padding:11px 13px;background:#fbf8f4;color:#8992a0;font-size:10px;text-align:left;text-transform:uppercase;letter-spacing:.08em}.checklist-table td{min-width:0;padding:12px 13px;border-top:1px solid #f0ebe5;font-size:12px;vertical-align:middle}.checklist-table tbody tr{transition:.15s}.checklist-table tbody tr:hover{background:#fffaf4}.checklist-col-select{width:42px}.checklist-col-plan{width:38%}.checklist-col-state{width:18%}.checklist-col-progress{width:16%}.checklist-col-target{width:13%}.checklist-col-actions{width:92px}.checklist-col-area,.checklist-col-status{display:none}.plan-cell{display:flex;align-items:flex-start;gap:10px;min-width:0;max-width:none}.plan-cell>span:last-child{display:block;min-width:0;flex:1}.plan-cell__icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:11px;background:#fff0e6;color:#c44824}.plan-cell strong,.plan-cell small{display:block}.plan-cell strong{font-size:12.5px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;overflow-wrap:anywhere}.plan-cell small{display:flex;align-items:flex-start;gap:5px;margin-top:5px;color:#7b8797;font-size:10px;line-height:1.35}.plan-cell small i{margin-top:2px;color:#dc5a2a}.table-location,.target-date{display:inline-flex;align-items:center;gap:6px;color:#637086;white-space:nowrap}.table-location i{color:#dc5a2a}.table-state{display:flex;align-items:flex-start;flex-wrap:wrap;gap:5px}.priority-pill,.status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border-radius:999px;font-size:10px;font-weight:850;white-space:nowrap}.priority-pill--critical{background:#fee8e5;color:#b92e21}.priority-pill--high{background:#fff0e0;color:#b8580a}.priority-pill--medium{background:#fff7d7;color:#956800}.priority-pill--low{background:#e8f7f1;color:#137759}.status-pill{background:#f0f2f5;color:#5f6876}.status-pill i{font-size:6px}.status-pill--in_progress{background:#eaf2ff;color:#2864bd}.status-pill--submitted{background:#edeafe;color:#6754bb}.status-pill--confirmed{background:#e5f8ef;color:#147653}.status-pill--rejected{background:#fee9e7;color:#b6372a}.table-progress{width:100%;max-width:130px}.table-progress>span{display:block;height:6px;border-radius:999px;background:#eee9e3;overflow:hidden}.table-progress i{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#dc4a27,#ef9d18)}.table-progress small{display:block;margin-top:5px;color:#737f91;font-size:10px}.table-actions{display:flex;justify-content:flex-end;gap:6px}.checklist-empty{display:flex;flex-direction:column;align-items:center;gap:7px;padding:46px 20px;color:#788497}.checklist-empty i{font-size:25px;color:#dc7c43}.checklist-empty strong{color:var(--ink)}.checklist-pagination{padding:14px 20px;border-top:1px solid #eee8e1}.checklist-pagination nav>div:first-child{display:none}.checklist-pagination nav>div:last-child{display:flex;align-items:center;justify-content:space-between;gap:12px}.checklist-pagination p{font-size:11px;color:#778295}.checklist-pagination a,.checklist-pagination span[aria-current]>span{border-radius:9px!important;font-size:11px!important}
        .checklist-modal[hidden]{display:none}.checklist-modal{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:20px;background:rgba(18,27,41,.58);backdrop-filter:blur(5px)}.checklist-modal__card{width:min(850px,100%);max-height:min(90vh,900px);display:flex;flex-direction:column;border:1px solid #eadfd3;border-radius:26px;background:#fffdfb;box-shadow:0 30px 80px rgba(17,24,39,.28);overflow:hidden}.checklist-modal__head{display:flex;align-items:center;justify-content:space-between;padding:16px 19px;border-bottom:1px solid #eee7df;background:#fff}.checklist-modal__head strong{display:block;font-size:15px}.checklist-modal__head small{display:block;color:var(--muted);font-size:11px;margin-top:2px}.checklist-modal__body{padding:20px;overflow:auto}.checklist-modal__foot{display:flex;justify-content:flex-end;padding:12px 18px;border-top:1px solid #eee7df;background:#fff}.close-button{border:0;background:#f5f2ee;color:#657085;border-radius:11px;width:36px;height:36px;cursor:pointer}.modal-plan-summary{display:grid;grid-template-columns:48px 1fr;gap:14px}.modal-plan-summary__icon{display:grid;place-items:center;width:48px;height:48px;border-radius:15px;background:linear-gradient(135deg,#fff0e5,#ffe8cf);color:#c34321;font-size:18px}.modal-plan-summary h3{margin:9px 0 5px;font-size:20px}.modal-plan-summary p{margin:0;color:#657186;line-height:1.55;font-size:12px}.modal-success{display:flex;align-items:flex-start;gap:8px;margin-top:9px;padding:8px 10px;border-radius:11px;background:#f3f8f5;color:#4c685c;font-size:10px;line-height:1.45}.modal-success i{color:#15966f;margin-top:2px}.modal-badges{display:flex;gap:6px;flex-wrap:wrap}.modal-progress-card{margin:17px 0;padding:13px 15px;border:1px solid #f2d2b7;border-radius:16px;background:#fff8f1}.modal-progress-card>div:first-child{display:flex;justify-content:space-between;gap:10px;font-size:11px;color:#9b4a20;font-weight:800}.modal-progress-card__bar{height:7px;margin-top:10px;border-radius:999px;background:#f1e4d8;overflow:hidden}.modal-progress-card__bar span{display:block;height:100%;background:linear-gradient(90deg,#cf3e24,#ed9a15);border-radius:inherit}.modal-section{margin-top:14px;padding:15px;border:1px solid #e9e4de;border-radius:18px;background:#fff}.modal-section__head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}.modal-section__head h4{margin:0;font-size:13px}.modal-section__head h4 i{color:#cf4b25;margin-right:7px}.modal-section__head>span{font-size:10px;color:#8791a0}.modal-steps{display:grid;gap:8px}.modal-step{display:grid;grid-template-columns:30px 1fr auto;align-items:center;gap:10px;padding:10px;border:1px solid #ece8e2;border-radius:13px;background:#fcfbf9}.modal-step--followed{border-color:#bee8d8;background:#f3fcf8}.modal-step--not_followed{border-color:#f4c8c3;background:#fff7f6}.modal-step__number{display:grid;place-items:center;width:28px;height:28px;border-radius:9px;background:#fff0e5;color:#b94422;font-size:11px;font-weight:900}.modal-step__copy strong,.modal-step__copy small{display:block}.modal-step__copy strong{font-size:12px;line-height:1.4}.modal-step__copy small{margin-top:4px;color:#7d8796;font-size:9.5px}.modal-step__actions{display:flex;gap:5px}.step-icon{display:grid;place-items:center;width:30px;height:30px;border:1px solid #dedbd6;border-radius:9px;background:#fff;color:#737c8b;cursor:pointer}.step-icon--done{color:#148160;border-color:#bae4d5}.step-icon--missed{color:#bd3c2e;border-color:#f0c4bf}.modal-review label{display:block;margin-bottom:6px;color:#6b7688;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.modal-review textarea{width:100%;resize:vertical;border:1px solid #ddd8d2;border-radius:12px;padding:10px 12px;font:inherit;font-size:12px;outline:0}.modal-review__actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.action-button{display:inline-flex;align-items:center;gap:7px;min-height:35px;padding:0 11px;border:1px solid transparent;border-radius:10px;font-size:10px;font-weight:850;cursor:pointer}.action-button--progress{background:#eaf2ff;color:#2864bd}.action-button--submitted{background:#eeebff;color:#6754bb}.action-button--confirmed{background:#e5f8ef;color:#147653}.action-button--rejected{background:#fee9e7;color:#b6372a}.modal-links{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}.modal-links a{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:8px;padding:11px 13px;border:1px solid #e8e2db;border-radius:13px;color:#3e4a5e;text-decoration:none;font-size:11px;font-weight:800}.modal-links a:hover{border-color:#efa76c;background:#fff8f1;color:#aa431f}.modal-empty{display:flex;align-items:center;gap:9px;color:#778295;font-size:11px;padding:12px}.toast-stack{position:fixed;right:20px;bottom:20px;z-index:120;display:grid;gap:8px}.toast{display:flex;align-items:center;gap:9px;min-width:250px;max-width:360px;padding:12px 14px;border-radius:13px;background:#172235;color:#fff;box-shadow:0 14px 35px rgba(15,23,42,.2);font-size:11px;font-weight:700}.toast--error{background:#a83227}.is-loading i{animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
        .modal-evidence-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:0}.modal-evidence-grid>div{min-width:0;padding:10px 11px;border-radius:12px;background:#f8fafc}.modal-evidence-grid dt{color:#8a94a3;font-size:9px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.modal-evidence-grid dd{margin:4px 0 0;color:#344055;font-size:11px;font-weight:800;overflow-wrap:anywhere}.modal-evidence-focus{margin:9px 0 0;padding:9px 11px;border-radius:12px;background:#fff7ed;color:#795137;font-size:10.5px;line-height:1.45}
        .checklist-modal__head>div{min-width:0}.checklist-modal__head strong{overflow-wrap:anywhere}.modal-plan-summary>div:last-child{min-width:0}.modal-plan-summary h3{overflow-wrap:anywhere;line-height:1.3}.modal-step__copy{min-width:0}.modal-step__copy strong{overflow-wrap:anywhere}
        @media(min-width:1025px){.checklist-main{margin-left:var(--sidebar-w,280px);width:calc(100% - var(--sidebar-w,280px));padding:20px 24px}.checklist-modal{left:var(--sidebar-w,280px)}}
        @media(max-width:1050px){.checklist-metrics{grid-template-columns:repeat(2,1fr)}.checklist-filters{grid-template-columns:1fr 1fr 1fr}.checklist-search{grid-column:span 2}.forecast-hero{grid-template-columns:1fr}.workflow-nav{width:max-content}}
        @media(max-width:767px){.checklist-main{padding:10px}.checklist-topbar{top:8px;border-radius:18px;padding:10px 12px}.mobile-menu{display:grid;place-items:center}.checklist-topbar__icon{display:none}.profile-pill b{display:none}.forecast-hero{padding:20px;border-radius:21px}.workflow-nav{width:100%;overflow:auto}.workflow-nav a{flex:1;justify-content:center}.checklist-metrics{grid-template-columns:1fr 1fr;gap:8px}.checklist-metric{min-height:68px;padding:11px}.checklist-metric__icon{width:35px;height:35px}.checklist-metric strong{font-size:19px}.checklist-register{border-radius:21px}.checklist-register__head{padding:17px;align-items:center}.checklist-register__head p{display:none}.checklist-filters{grid-template-columns:1fr 1fr;padding:0 15px 14px}.checklist-search{grid-column:1/-1}.checklist-filters select[name=barangay_id]{grid-column:1/-1}.checklist-table{min-width:0}.checklist-table thead{display:none}.checklist-table,.checklist-table tbody,.checklist-table tr,.checklist-table td{display:block;width:100%}.checklist-table tr{position:relative;padding:13px 56px 13px 14px;border-top:1px solid #eee8e1}.checklist-table td{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:5px 0;border:0}.checklist-table td:before{content:attr(data-label);font-size:9px;color:#929aa7;text-transform:uppercase;letter-spacing:.07em;font-weight:800}.checklist-table td:first-child:before,.checklist-table td:last-child:before{display:none}.checklist-table td:first-child{display:block}.checklist-table td:last-child{position:absolute;right:12px;top:13px}.plan-cell strong{white-space:normal}.table-actions{flex-direction:column}.checklist-modal{padding:8px;align-items:end}.checklist-modal__card{max-height:92dvh;border-radius:23px 23px 15px 15px}.checklist-modal__body{padding:15px}.modal-step{grid-template-columns:28px 1fr}.modal-step__actions{grid-column:2;justify-content:flex-end}.modal-progress-card>div:first-child{display:block}.modal-progress-card strong{display:block;margin-top:4px}.modal-links{grid-template-columns:1fr}}
    </style>
    <style>
        .bulk-toolbar{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:0 24px 14px}.bulk-toolbar__count{margin-right:auto;display:inline-flex;align-items:center;gap:7px;color:#6d7889;font-size:10px;font-weight:800}.bulk-toolbar select{height:35px;min-width:145px;border:1px solid #ddd8d2;border-radius:10px;background:#fff;padding:0 10px;color:#475467;font-size:10px}.bulk-toolbar button:disabled{opacity:.45;cursor:not-allowed}.checklist-table input[type=checkbox]{width:15px;height:15px;accent-color:#d55327;cursor:pointer}.history-list{display:grid;gap:8px}.history-event{display:grid;grid-template-columns:30px 1fr auto;gap:10px;align-items:start;padding:9px;border:1px solid #ece7e1;border-radius:12px;background:#fdfcfb}.history-event__icon{display:grid;place-items:center;width:28px;height:28px;border-radius:9px;background:#fff0e5;color:#c24824}.history-event strong,.history-event small{display:block}.history-event strong{font-size:11px}.history-event small{margin-top:3px;color:#7d8796;font-size:9.5px;line-height:1.4}.history-event time{color:#9098a5;font-size:9px;white-space:nowrap}.history-pagination{display:flex;align-items:center;justify-content:flex-end;gap:6px;margin-top:8px}.history-pagination button{width:29px;height:29px;border:1px solid #ddd8d2;border-radius:9px;background:#fff;color:#647084;cursor:pointer}.history-pagination button:disabled{opacity:.4;cursor:not-allowed}
        @media(max-width:767px){.bulk-toolbar{display:grid;grid-template-columns:1fr 1fr;padding:0 15px 12px}.bulk-toolbar__count{grid-column:1/-1}.bulk-toolbar select{min-width:0;width:100%}.bulk-toolbar button{grid-column:1/-1;justify-content:center}.checklist-table td[data-label=Select]{position:absolute;left:14px;top:19px;width:auto}.checklist-table tr{padding-left:42px}.history-event{grid-template-columns:28px 1fr}.history-event time{grid-column:2}.modal-evidence-grid{grid-template-columns:1fr}}
    </style>
    <style data-checklist-mobile-style>
        @media (max-width: 767px), (display-mode: standalone) and (max-width: 1024px) {
            body.chief-legacy-layout { background: #f7f8fa !important; }
            .checklist-main { width: 100%; padding: 12px 10px calc(106px + env(safe-area-inset-bottom)); }
            .checklist-container { width: 100%; max-width: none; }
            .checklist-topbar { display: none !important; }

            .forecast-hero {
                display: block;
                margin-bottom: 12px;
                padding: 17px 16px;
                border-radius: 18px;
                background: linear-gradient(145deg, #fff, #fff8ef);
                box-shadow: 0 6px 18px rgba(39, 46, 59, .05);
            }
            .forecast-hero h2 { margin: 6px 0; font-size: 21px; line-height: 1.16; }
            .forecast-hero p { font-size: 11px; line-height: 1.55; }
            .workflow-nav { width: 100%; margin-top: 14px; overflow: visible; }
            .workflow-nav a { min-width: 0; flex: 1; padding: 9px 5px; font-size: 9px; }

            .checklist-workspace { gap: 11px; }
            .checklist-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .checklist-metric { min-width: 0; min-height: 66px; padding: 10px; border-radius: 15px; }
            .checklist-metric small { font-size: 8px; }
            .checklist-metric strong { font-size: 18px; }

            .checklist-register {
                overflow: visible;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }
            .checklist-register__head {
                align-items: center;
                margin-bottom: 10px;
                padding: 16px;
                border: 1px solid var(--line);
                border-radius: 17px;
                background: #fff;
                box-shadow: 0 5px 16px rgba(39, 46, 59, .04);
            }
            .checklist-register__head h2 { margin-top: 5px; font-size: 17px; }
            .checklist-result-count { padding: 7px 9px; font-size: 9px; }

            .checklist-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 10px;
                padding: 12px;
                border: 1px solid var(--line);
                border-radius: 17px;
                background: #fff;
            }
            .checklist-search,
            .checklist-filters select[name="barangay_id"] { grid-column: 1 / -1; }
            .checklist-filters > .icon-button { width: 100%; height: 40px; }

            .bulk-toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 10px;
                padding: 12px;
                border: 1px solid var(--line);
                border-radius: 17px;
                background: #fff;
            }
            .bulk-toolbar__count { grid-column: 1 / -1; }
            .bulk-toolbar button { grid-column: 1 / -1; width: 100%; }

            .checklist-table-wrap { overflow: visible; border: 0; }
            .checklist-table,
            .checklist-table tbody { display: grid; width: 100%; gap: 11px; }
            .checklist-table { min-width: 0; border: 0; }
            .checklist-table thead { display: none; }
            .checklist-table tbody tr {
                position: relative;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px 10px;
                width: 100%;
                padding: 16px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 7px 20px rgba(39, 46, 59, .055);
            }
            .checklist-table tbody tr:hover { background: #fff; }
            .checklist-table tbody td {
                display: grid;
                min-width: 0;
                width: auto;
                gap: 5px;
                padding: 0;
                border: 0;
                align-content: start;
                justify-content: stretch;
            }
            .checklist-table tbody td::before {
                display: block;
                color: #8b95a5;
                font-size: 8px;
                font-weight: 850;
                letter-spacing: .08em;
                text-transform: uppercase;
            }
            .checklist-table .checklist-col-area,
            .checklist-table .checklist-col-status { display: none !important; }
            .checklist-table td[data-label="Select"] {
                position: absolute;
                top: 17px;
                right: 16px;
                left: auto;
                z-index: 1;
                width: auto;
            }
            .checklist-table td[data-label="Select"]::before,
            .checklist-table td[data-label="Action plan"]::before,
            .checklist-table td[data-label="Actions"]::before { display: none; }
            .checklist-table td[data-label="Action plan"] { grid-column: 1 / -1; padding-right: 30px; }
            .checklist-table td[data-label="Actions"] { position: static; grid-column: 1 / -1; }
            .plan-cell { max-width: none; align-items: flex-start; }
            .plan-cell__icon { width: 38px; height: 38px; flex-basis: 38px; }
            .plan-cell strong { white-space: normal; font-size: 13px; line-height: 1.4; }
            .table-location,
            .target-date { min-width: 0; white-space: normal; font-size: 10px; line-height: 1.4; }
            .table-progress { width: 100%; }
            .priority-pill,
            .status-pill { width: max-content; max-width: 100%; }
            .table-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .table-actions .icon-button { width: 100%; height: 42px; border-radius: 12px; }

            .checklist-pagination {
                margin-top: 10px;
                padding: 12px;
                border: 1px solid var(--line);
                border-radius: 15px;
                background: #fff;
            }
            .checklist-pagination nav > div:last-child { align-items: center; }
            .checklist-pagination nav > div:last-child > div:first-child { display: none; }

            .checklist-modal { z-index: 12000; align-items: end; padding: 0; }
            .checklist-modal__card { width: 100%; max-height: 92dvh; border-radius: 22px 22px 0 0; }
            .checklist-modal__head { padding: 15px 16px; }
            .checklist-modal__body { padding: 14px; }
            .checklist-modal__foot { padding: 11px 14px calc(11px + env(safe-area-inset-bottom)); }
            .modal-plan-summary { grid-template-columns: 40px minmax(0, 1fr); gap: 10px; }
            .modal-plan-summary__icon { width: 40px; height: 40px; border-radius: 12px; }
            .modal-plan-summary h3 { font-size: 17px; overflow-wrap: anywhere; }
            .modal-section { padding: 13px; border-radius: 15px; }
            .modal-step { grid-template-columns: 28px minmax(0, 1fr); }
            .modal-step__actions { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(3, 1fr); }
            .step-icon { width: 100%; height: 36px; }
            .modal-review__actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .modal-review__actions .action-button { justify-content: center; }
            .toast-stack { right: 10px; bottom: calc(90px + env(safe-area-inset-bottom)); left: 10px; }
            .toast { width: 100%; min-width: 0; max-width: none; }
        }
    </style>
</head>
<body class="chief-legacy-layout">
<div class="checklist-shell">
    @include('chief.partials.sidebar', ['active' => 'course-checklists'])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
    <main class="checklist-main">
        <div class="checklist-container">
            <header class="checklist-topbar">
                <div class="checklist-topbar__title">
                    <button id="openSidebar" class="mobile-menu" aria-label="Open navigation"><i class="fa-solid fa-bars"></i></button>
                    <span class="checklist-topbar__icon"><i class="fa-solid fa-clipboard-check"></i></span>
                    <div><h1>Action Plan Checklists</h1><p>Forecast response and compliance tracking · {{ now()->format('l, F j') }}</p></div>
                </div>
                <a href="{{ route('station-admin.profile') }}" class="profile-pill" style="text-decoration:none"><span>{{ substr(auth()->user()->first_name ?? 'C',0,1) }}{{ substr(auth()->user()->last_name ?? 'A',0,1) }}</span><b>{{ auth()->user()->first_name ?? 'Chief' }}</b><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></a>
            </header>

            <section class="forecast-hero">
                <div><span class="forecast-hero__eyebrow"><i class="fa-solid fa-fire-flame-curved"></i> Forecast response workflow</span><h2>From forecast insight to field action.</h2><p>Scan the register, open only the plan you need, then verify its steps and send approved work into Mitigation Control.</p></div>
                <nav class="workflow-nav" aria-label="Forecast workflow">
                    <a href="{{ route('station-admin.analytics') }}"><i class="fa-solid fa-chart-line"></i><span>Forecast</span></a>
                    <a href="{{ route('station-admin.course-checklists') }}" class="active"><i class="fa-solid fa-list-check"></i><span>Checklists</span></a>
                    <a href="{{ route('station-admin.mitigations') }}"><i class="fa-solid fa-shield-halved"></i><span>Mitigation</span></a>
                </nav>
            </section>

            @include('chief.partials.course-checklist-workspace')
        </div>
    </main>
</div>

<div id="checklistModal" class="checklist-modal" role="dialog" aria-modal="true" aria-labelledby="checklistModalTitle" hidden>
    <div class="checklist-modal__card">
        <header class="checklist-modal__head"><div><strong id="checklistModalTitle">Checklist review</strong><small>Verify steps and record operational progress.</small></div><button id="closeChecklistModal" type="button" class="close-button" aria-label="Close checklist"><i class="fa-solid fa-xmark"></i></button></header>
        <div id="checklistModalBody" class="checklist-modal__body"></div>
        <footer class="checklist-modal__foot"><button type="button" class="action-button" data-close-checklist><i class="fa-solid fa-arrow-left"></i><span>Back to register</span></button></footer>
    </div>
</div>
<script>
    let checklistCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const checklistPageUrl = @json(route('station-admin.course-checklists'));
    const checklistEndpoints = {
        history: @json(route('station-admin.action-plan-checklists.history', ['id' => '__CHECKLIST_ID__'])),
        items: @json(route('station-admin.action-plan-checklists.items', ['id' => '__CHECKLIST_ID__'])),
        confirm: @json(route('station-admin.action-plan-checklists.confirm', ['id' => '__CHECKLIST_ID__'])),
        bulk: @json(route('station-admin.action-plan-checklists.bulk')),
        refreshSession: @json(route('auth.session-refresh')),
    };
    let checklistTrigger = null;
    let checklistSearchTimer = null;

    function checklistEndpoint(type, checklistId = null) {
        const endpoint = checklistEndpoints[type] || '';
        return checklistId == null ? endpoint : endpoint.replace('__CHECKLIST_ID__', encodeURIComponent(String(checklistId)));
    }

    async function refreshChecklistSession() {
        const response = await fetch(checklistEndpoints.refreshSession, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.csrf_token) return false;
        checklistCsrf = result.csrf_token;
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', checklistCsrf);
        return true;
    }

    async function checklistFetch(url, options = {}, canRetrySession = true) {
        const controller = new AbortController();
        // Leave enough room for a checklist write to queue behind the global
        // call inbox when the app is served by Laravel's single-worker dev server.
        const timeout = window.setTimeout(() => controller.abort(), 60000);
        const headers = new Headers(options.headers || {});
        headers.set('X-Requested-With', 'XMLHttpRequest');
        if ((options.method || 'GET').toUpperCase() !== 'GET' && checklistCsrf) {
            headers.set('X-CSRF-TOKEN', checklistCsrf);
        }

        try {
            const response = await fetch(new URL(url, window.location.href), {
                ...options,
                headers,
                credentials: 'same-origin',
                cache: 'no-store',
                signal: controller.signal,
            });

            if (response.status === 419 && canRetrySession && await refreshChecklistSession()) {
                return checklistFetch(url, options, false);
            }

            if (response.redirected && new URL(response.url).pathname.includes('/login')) {
                throw new Error('Your session expired. Sign in again, then retry the checklist update.');
            }

            return response;
        } catch (error) {
            if (error?.name === 'AbortError') {
                throw new Error('The checklist request timed out. Check your connection and try again.');
            }
            if (!navigator.onLine) {
                throw new Error('You are offline. Reconnect before updating the checklist.');
            }
            if (error instanceof TypeError) {
                throw new Error('The checklist server could not be reached. Check your connection and try again.');
            }
            throw error;
        } finally {
            window.clearTimeout(timeout);
        }
    }

    function showChecklistToast(message, type = 'success') {
        window.PyroNotify?.notify(message, type);
    }

    function escapeChecklistHtml(value) {
        const node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function updateBulkToolbar() {
        const selected = [...document.querySelectorAll('[data-select-checklist]:checked')];
        const count = document.querySelector('[data-selected-count]');
        const apply = document.querySelector('[data-apply-bulk]');
        const all = document.querySelector('[data-select-all]');
        if (count) count.textContent = String(selected.length);
        if (apply) apply.disabled = selected.length === 0;
        if (all) {
            const total = document.querySelectorAll('[data-select-checklist]').length;
            all.checked = total > 0 && selected.length === total;
            all.indeterminate = selected.length > 0 && selected.length < total;
        }
    }

    async function loadChecklistHistory(checklistId, page = 1) {
        const container = document.querySelector(`[data-checklist-history="${checklistId}"]`);
        if (!container) return;
        container.innerHTML = '<div class="modal-empty"><i class="fa-solid fa-spinner fa-spin"></i><span>Loading history...</span></div>';
        try {
            const historyUrl = new URL(checklistEndpoint('history', checklistId), window.location.href);
            historyUrl.searchParams.set('page', page);
            historyUrl.searchParams.set('per_page', '6');
            const response = await checklistFetch(historyUrl, {headers:{'Accept':'application/json'}});
            const result = await response.json().catch(() => ({}));
            if (!response.ok || result.success === false) throw new Error(result.message || 'History could not be loaded.');
            const events = Array.isArray(result.data) ? result.data : [];
            container.innerHTML = events.length ? events.map((item) => {
                const date = item.created_at ? new Date(item.created_at).toLocaleString([], {month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : 'Unknown date';
                const change = item.old_value || item.new_value ? `${item.old_value ? escapeChecklistHtml(item.old_value.replaceAll('_',' ')) + ' → ' : ''}${escapeChecklistHtml((item.new_value || '').replaceAll('_',' '))}` : '';
                return `<article class="history-event"><span class="history-event__icon"><i class="fa-solid ${item.event_type === 'step_reviewed' ? 'fa-check' : item.event_type === 'assignment_changed' ? 'fa-user-shield' : 'fa-pen'}"></i></span><span><strong>${escapeChecklistHtml(item.label)}</strong><small>${escapeChecklistHtml(item.actor)}${change ? ' · ' + change : ''}${item.notes ? '<br>' + escapeChecklistHtml(item.notes) : ''}</small></span><time>${escapeChecklistHtml(date)}</time></article>`;
            }).join('') : '<div class="modal-empty"><i class="fa-regular fa-clock"></i><span>No activity has been recorded yet.</span></div>';
            const pagination = result.pagination || {};
            if ((pagination.last_page || 1) > 1) {
                container.insertAdjacentHTML('beforeend', `<div class="history-pagination"><button type="button" data-history-page="${Math.max(1, page - 1)}" data-history-id="${checklistId}" ${page <= 1 ? 'disabled' : ''} aria-label="Previous history page"><i class="fa-solid fa-chevron-left"></i></button><small>Page ${page} of ${pagination.last_page}</small><button type="button" data-history-page="${Math.min(pagination.last_page, page + 1)}" data-history-id="${checklistId}" ${page >= pagination.last_page ? 'disabled' : ''} aria-label="Next history page"><i class="fa-solid fa-chevron-right"></i></button></div>`);
            }
        } catch (error) {
            container.innerHTML = `<div class="modal-empty"><i class="fa-solid fa-triangle-exclamation"></i><span>${escapeChecklistHtml(error.message)}</span></div>`;
        }
    }

    function openChecklistModal(id, trigger = null) {
        const template = document.getElementById(`checklistDetail-${id}`);
        const modal = document.getElementById('checklistModal');
        if (!template || !modal) return;
        checklistTrigger = trigger || document.querySelector(`[data-open-checklist="${id}"]`) || null;
        const planTitle = checklistTrigger?.closest('[data-checklist-row]')?.querySelector('.plan-cell strong')?.textContent?.trim();
        document.getElementById('checklistModalTitle').textContent = planTitle || 'Checklist review';
        document.getElementById('checklistModalBody').replaceChildren(template.content.cloneNode(true));
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('closeChecklistModal').focus();
        loadChecklistHistory(id);
    }

    function closeChecklistModal() {
        const modal = document.getElementById('checklistModal');
        if (modal.hidden) return;
        modal.hidden = true;
        document.getElementById('checklistModalBody').replaceChildren();
        document.body.style.overflow = '';
        checklistTrigger?.focus?.();
    }

    async function loadChecklistWorkspace(targetUrl, { push = true, reopen = null } = {}) {
        const visibleUrl = new URL(targetUrl || window.location.href, window.location.origin);
        visibleUrl.searchParams.delete('partial');
        const requestUrl = new URL(visibleUrl);
        requestUrl.searchParams.set('partial', '1');
        const workspace = document.getElementById('checklistWorkspace');
        workspace?.setAttribute('aria-busy', 'true');
        try {
            const response = await checklistFetch(requestUrl, { headers: { 'Accept': 'text/html' } });
            if (!response.ok) throw new Error('Could not load checklist records.');
            const incoming = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('checklistWorkspace');
            if (!incoming) throw new Error('Checklist response was incomplete.');
            workspace.replaceWith(incoming);
            if (push) window.history.pushState({}, '', visibleUrl);
            if (reopen && document.getElementById(`checklistDetail-${reopen}`)) openChecklistModal(reopen);
        } catch (error) {
            workspace?.removeAttribute('aria-busy');
            showChecklistToast(error.message || 'Could not load checklist records.', 'error');
        }
    }

    async function patchChecklist(url, payload, button, checklistId) {
        const original = button.innerHTML;
        button.disabled = true;
        button.classList.add('is-loading');
        button.innerHTML = '<i class="fa-solid fa-spinner"></i>';
        try {
            const response = await checklistFetch(url, { method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(payload) });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || result.success === false) throw new Error(result.message || 'The checklist update failed.');
            closeChecklistModal();
            await loadChecklistWorkspace(window.location.href, { push:false, reopen:checklistId });
            showChecklistToast('Checklist progress updated.');
        } catch (error) {
            button.disabled = false;
            button.classList.remove('is-loading');
            button.innerHTML = original;
            showChecklistToast(error.message || 'The checklist update failed.', 'error');
        }
    }

    async function applyBulkChecklistUpdate(button) {
        const ids = [...document.querySelectorAll('[data-select-checklist]:checked')].map((item) => Number(item.value));
        const assigned = document.querySelector('[data-bulk-assignee]')?.value || '';
        const status = document.querySelector('[data-bulk-status]')?.value || '';
        if (!ids.length) return;
        if (!assigned && !status) { showChecklistToast('Choose an assignee or status first.', 'error'); return; }
        const payload = {checklist_ids: ids};
        if (assigned) payload.assigned_to = assigned === 'unassigned' ? null : Number(assigned);
        if (status) payload.status = status;
        const original = button.innerHTML;
        button.disabled = true;
        button.classList.add('is-loading');
        button.innerHTML = '<i class="fa-solid fa-spinner"></i><span>Applying</span>';
        try {
            const response = await checklistFetch(checklistEndpoints.bulk, {method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)});
            const result = await response.json().catch(() => ({}));
            if (!response.ok || result.success === false) throw new Error(result.message || 'Bulk update failed.');
            await loadChecklistWorkspace(window.location.href, {push:false});
            showChecklistToast(`${result.updated || ids.length} checklist(s) updated.`);
        } catch (error) {
            button.disabled = false;
            button.classList.remove('is-loading');
            button.innerHTML = original;
            showChecklistToast(error.message || 'Bulk update failed.', 'error');
        }
    }

    document.addEventListener('click', (event) => {
        const open = event.target.closest('[data-open-checklist]');
        if (open) { openChecklistModal(open.dataset.openChecklist, open); return; }
        if (event.target.closest('#closeChecklistModal,[data-close-checklist]') || event.target.id === 'checklistModal') { closeChecklistModal(); return; }
        const reset = event.target.closest('[data-checklist-reset]');
        if (reset) { loadChecklistWorkspace(checklistPageUrl); return; }
        const pageLink = event.target.closest('.checklist-pagination a');
        if (pageLink) { event.preventDefault(); loadChecklistWorkspace(pageLink.href); return; }
        const historyPage = event.target.closest('[data-history-page]');
        if (historyPage && !historyPage.disabled) { loadChecklistHistory(historyPage.dataset.historyId, Number(historyPage.dataset.historyPage)); return; }
        const bulk = event.target.closest('[data-apply-bulk]');
        if (bulk) { applyBulkChecklistUpdate(bulk); return; }
        const step = event.target.closest('[data-step-status]');
        if (step) { patchChecklist(checklistEndpoint('items', step.dataset.checklistId), {item_id:Number(step.dataset.itemId),status:step.dataset.status}, step, step.dataset.checklistId); return; }
        const status = event.target.closest('[data-checklist-status]');
        if (status) {
            const notes = document.getElementById(`notes-${status.dataset.checklistId}`)?.value || '';
            patchChecklist(checklistEndpoint('confirm', status.dataset.checklistId), {status:status.dataset.status,confirmation_notes:notes}, status, status.dataset.checklistId);
        }
    });

    document.addEventListener('submit', (event) => {
        if (event.target.id !== 'checklistFilters') return;
        event.preventDefault();
        const url = new URL(event.target.action);
        new FormData(event.target).forEach((value,key) => { if (String(value).trim()) url.searchParams.set(key,String(value).trim()); });
        loadChecklistWorkspace(url);
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-select-all]')) {
            document.querySelectorAll('[data-select-checklist]').forEach((item) => item.checked = event.target.checked);
            updateBulkToolbar();
            return;
        }
        if (event.target.matches('[data-select-checklist]')) { updateBulkToolbar(); return; }
        if (!event.target.closest('#checklistFilters select')) return;
        event.target.form?.requestSubmit();
    });
    document.addEventListener('input', (event) => {
        if (!event.target.matches('#checklistFilters input[name=q]')) return;
        clearTimeout(checklistSearchTimer);
        checklistSearchTimer = setTimeout(() => event.target.form?.requestSubmit(), 350);
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeChecklistModal(); });
    window.addEventListener('popstate', () => loadChecklistWorkspace(window.location.href, {push:false}));
    document.getElementById('openSidebar')?.addEventListener('click', () => { document.getElementById('sidebar')?.classList.add('open'); document.getElementById('sidebarBackdrop')?.classList.remove('hidden'); });
    document.getElementById('sidebarBackdrop')?.addEventListener('click', () => { document.getElementById('sidebar')?.classList.remove('open'); document.getElementById('sidebarBackdrop')?.classList.add('hidden'); });
</script>
@include('chief.partials.logs-modal')
@include('chief.partials.logs-script')
@include('chief.partials.logout-confirmation')
</body>
</html>
