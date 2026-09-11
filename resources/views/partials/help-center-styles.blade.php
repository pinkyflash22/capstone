<style>
    #aboutModal .modal-content, .policy-modal-overlay .policy-modal { display:flex; flex-direction:column; width:min(760px,100%); max-width:760px; max-height:90vh; max-height:90dvh; padding:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:24px; background:#fff; box-shadow:0 24px 80px #0f172a33; }
    #aboutModal, .policy-modal-overlay { padding:20px; backdrop-filter:blur(5px); }
    #aboutModal .modal-header, .policy-modal-header { flex-shrink:0; padding:22px 24px; border-bottom:1px solid #e2e8f0; background:linear-gradient(120deg,#fff7ed,#fff); }
    #aboutModal .modal-header h2, .policy-modal-header h3 { margin:0; font-size:22px; line-height:1.3; color:#172033; letter-spacing:-.03em; }
    #aboutModal .modal-close { flex-shrink:0; width:44px; height:44px; border-radius:12px; background:#fff; color:#475569; border:1px solid #e2e8f0; cursor:pointer; }
    #aboutModal .modal-body, .policy-modal-body { min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:24px; }
    .policy-modal-footer { flex-shrink:0; padding:16px 24px; border-top:1px solid #e2e8f0; background:#fff; }
    .policy-modal-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .policy-modal-actions button { min-height:44px; }
    .help-prose { color:#475569; font-size:14px; line-height:1.7; overflow-wrap:anywhere; }
    .help-prose * { box-sizing:border-box; }
    .help-prose h3 { margin:5px 0 8px; font-size:25px; line-height:1.25; letter-spacing:-.035em; color:#172033; }
    .help-prose h4 { margin:0 0 7px; font-size:15px; color:#172033; }
    .help-prose p { margin:0 0 12px; }
    .help-prose section { padding:18px 0; border-bottom:1px solid #e2e8f0; }
    .help-prose section:last-child { border:0; }
    .help-prose a:not(.help-button) { color:#b91c1c; text-decoration:underline; text-underline-offset:3px; }
    .help-eyebrow { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.12em; color:#b91c1c; }
    .help-intro { margin-bottom:20px; }
    .help-callout { padding:16px; margin-bottom:18px; border:1px solid #fed7aa; border-radius:14px; background:#fff7ed; color:#9a3412; }
    .help-callout p { margin:5px 0 0; }
    .help-callout .help-button { margin-top:12px; }
    .help-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .help-grid section { padding:16px; border:1px solid #e2e8f0 !important; border-radius:14px; background:#f8fafc; }
    .help-prose label { display:grid; gap:6px; font-weight:700; font-size:12px; color:#334155; margin:12px 0; }
    .help-prose :is(input,select,textarea) { width:100%; min-width:0; max-width:100%; min-height:44px; border:1px solid #cbd5e1; border-radius:10px; padding:11px 12px; font:inherit; color:#172033; background:#fff; }
    .help-prose textarea { resize:vertical; }
    .help-prose details { margin:9px 0; border:1px solid #e2e8f0; border-radius:12px; background:#fff; }
    .help-prose summary { padding:14px; min-height:48px; cursor:pointer; font-weight:700; color:#334155; }
    .help-prose summary::marker { color:#dc2626; }
    .help-prose details[open] { border-color:#fdba74; }
    .help-prose details p { padding:0 16px 12px; margin:0; }
    .help-prose [hidden] { display:none !important; }
    .help-button { display:inline-flex; justify-content:center; align-items:center; gap:8px; min-height:44px; padding:11px 16px; border:1px solid #dc2626; border-radius:10px; background:#dc2626; color:#fff; text-decoration:none; font:inherit; font-size:13px; font-weight:700; cursor:pointer; }
    .help-button--secondary { background:#fff; color:#b91c1c; border-color:#fecaca; }
    .help-caption { font-size:12px; color:#64748b; margin-top:10px !important; }
    .help-prose :is(button,a,input,textarea,select,summary):focus-visible, #aboutModal .modal-close:focus-visible { outline:3px solid #f59e0b; outline-offset:3px; }
    @media(max-width:640px) {
        #aboutModal, .policy-modal-overlay { padding:10px; }
        #aboutModal .modal-content, .policy-modal-overlay .policy-modal { width:100%; max-height:calc(100dvh - 20px); margin:auto; border-radius:18px; }
        #aboutModal .modal-header, .policy-modal-header { padding:16px; }
        #aboutModal .modal-header h2, .policy-modal-header h3 { font-size:19px; }
        #aboutModal .modal-body, .policy-modal-body { padding:16px; }
        .policy-modal-footer { padding:12px 16px max(12px,env(safe-area-inset-bottom)); }
        .policy-modal-actions button { flex:1 1 140px; }
        .help-grid { grid-template-columns:minmax(0,1fr); }
        .help-prose :is(input,select,textarea) { font-size:16px; }
        .help-button { width:100%; }
    }
    @media(prefers-reduced-motion:no-preference) { #aboutModal.active .modal-content, .policy-modal { animation:help-enter .2s ease-out; } @keyframes help-enter { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } } }
</style>
