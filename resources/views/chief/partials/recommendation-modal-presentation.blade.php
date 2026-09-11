{{-- Scoped to recommendation dialogs; no changes to forecast requests or actions. --}}
<style>
    :is(#recommendationRunModal, #exploreModal).show { display:flex; align-items:center; justify-content:center; padding:24px; }
    :is(#recommendationRunModal, #exploreModal) .modal-content.analytics-forecast-modal { display:flex; flex-direction:column; width:min(900px,100%) !important; max-width:900px !important; max-height:calc(100dvh - 48px) !important; margin:auto; padding:0; border:1px solid #e2e8f0; border-radius:22px; background:#fff; overflow:hidden; box-shadow:0 24px 80px #0f172a38; }
    :is(#recommendationRunModal, #exploreModal) .modal-content::before { display:none; }
    :is(#recommendationRunModal, #exploreModal) .modal-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px; border-bottom:1px solid #e2e8f0; background:linear-gradient(120deg,#fff7ed,#fff); }
    :is(#recommendationRunModal, #exploreModal) .modal-header h3 { margin:4px 0 0; max-width:none; font-size:21px; line-height:1.3; letter-spacing:-.03em; color:#172033; }
    .recommendation-modal-eyebrow { display:block; color:#b91c1c; font-size:10px; letter-spacing:.12em; text-transform:uppercase; font-weight:800; }
    :is(#recommendationRunModal, #exploreModal) .modal-close { flex-shrink:0; width:44px; height:44px; border-radius:12px; background:#fff; border:1px solid #e2e8f0; color:#475569; }
    :is(#recommendationRunModal, #exploreModal) .modal-body { padding:22px 24px; background:#f8fafc; min-height:0; overscroll-behavior:contain; overflow-y:auto; }
    :is(#recommendationRunModal, #exploreModal) .modal-body :where(div,p,span,strong,button,label) { min-width:0; overflow-wrap:anywhere; }
    :is(#recommendationRunModal, #exploreModal) .modal-footer { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 24px; background:#fff; border-top:1px solid #e2e8f0; }
    .recommendation-modal-note { margin:0; max-width:460px; color:#64748b; font-size:12px; line-height:1.5; }
    :is(#recommendationRunModal, #exploreModal) .modal-footer button { min-height:44px; padding:10px 22px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; }
    #exploreModal .modal-footer .btn-primary { background:#dc2626; border-color:#dc2626; color:#fff; }
    #recommendationRunModal .analytics-run-modal__hero, #exploreModal .original-recommendation { padding:18px; border:1px solid #fed7aa; border-radius:16px; background:#fffaf5; box-shadow:none; margin:0 0 16px; }
    #exploreModal .original-recommendation > div { flex-wrap:wrap; }
    #recommendationRunModal .analytics-run-modal__grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    #recommendationRunModal .analytics-run-modal__stat { padding:14px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:none; }
    #recommendationRunModal .recommendation-card { margin:10px 0; padding:16px; background:#fff; border:1px solid #e2e8f0; border-left:3px solid #f59e0b; border-radius:12px; box-shadow:none; }
    #recommendationRunModal .recommendation-card.critical, #recommendationRunModal .recommendation-card.high { border-left-color:#dc2626; }
    #recommendationRunModal .recommendation-card.low { border-left-color:#15803d; }
    #recommendationRunModal .recommendation-card > div > div:first-child { flex-shrink:0; }
    #recommendationRunModal .recommendation-card strong { font-size:15px; line-height:1.5; }
    #recommendationRunModal .explore-btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; min-height:44px; margin-top:12px; padding:10px 14px; border:1px solid #fecaca; border-radius:10px; background:#fff1ec; color:#b91c1c; font-size:13px; font-weight:700; white-space:normal; }
    #recommendationRunModal .analytics-run-modal__checklist { padding:16px; border:1px solid #e2e8f0; border-radius:12px; box-shadow:none; background:#fff; }
    #recommendationRunModal .analytics-run-modal__checkbox { min-height:48px; padding:12px; gap:10px; border-radius:10px; background:#f8fafc; }
    #recommendationRunModal .analytics-run-modal__checkbox input { width:20px; height:20px; flex-shrink:0; accent-color:#dc2626; }
    #exploreModal .section-divider { display:none; }
    #exploreModal .steps-container { display:grid; gap:10px; }
    #exploreModal .step-item { display:grid; grid-template-columns:32px minmax(0,1fr); align-items:start; gap:12px; padding:16px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:none; margin:0; }
    #exploreModal .step-number { width:32px; height:32px; border-radius:10px; background:#fff1e8; color:#b91c1c; box-shadow:none; font-weight:800; }
    #exploreModal .step-content p { margin:0; color:#334155; font-size:14px; line-height:1.7; }
    #exploreModal :is(.resources-section,.timeline-section) { margin-top:16px; padding:16px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
    #exploreModal .resource-tag { display:inline-flex; max-width:100%; white-space:normal; margin:4px; padding:8px 10px; background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; border-radius:8px; font-size:12px; }
    #exploreModal .timeline-content { padding:12px; background:#fff7ed; border-radius:10px; }
    :is(#recommendationRunModal, #exploreModal) :is(button,input,a):focus-visible { outline:3px solid #f59e0b; outline-offset:3px; }
    :is(#recommendationRunModalBody,#exploreModalBody)[data-state="loading"] { min-height:260px; }
    :is(#recommendationRunModalBody,#exploreModalBody)[data-state="loading"] > div { padding:40px 16px; }
    @media(max-width:767px), (display-mode:standalone) and (max-width:1024px) {
        :is(#recommendationRunModal, #exploreModal).show { padding:10px; align-items:center; }
        :is(#recommendationRunModal, #exploreModal) .modal-content.analytics-forecast-modal { width:100% !important; max-width:none !important; max-height:calc(100dvh - 20px) !important; border-radius:18px; }
        :is(#recommendationRunModal, #exploreModal) .modal-header { padding:16px; }
        :is(#recommendationRunModal, #exploreModal) .modal-header h3 { font-size:18px; }
        :is(#recommendationRunModal, #exploreModal) .modal-body { padding:14px; }
        :is(#recommendationRunModal, #exploreModal) .modal-footer { padding:12px 16px max(12px,env(safe-area-inset-bottom)); flex-wrap:wrap; }
        :is(#recommendationRunModal, #exploreModal) .modal-footer button { width:100%; }
        #recommendationRunModal .analytics-run-modal__grid { grid-template-columns:repeat(2,minmax(0,1fr)) !important; }
        #recommendationRunModal :is(.analytics-run-modal__hero,.analytics-run-modal__checklist,.recommendation-card) { padding:13px; }
        #exploreModal .step-item { padding:13px; gap:10px; }
        #exploreModal .step-content p { font-size:14px; }
    }
    @media(prefers-reduced-motion:no-preference) {
        :is(#recommendationRunModal,#exploreModal).show .modal-content { animation:recommendation-dialog-enter .2s ease-out; }
        :is(#recommendationRunModal,#exploreModal) :is(.recommendation-card,.step-item) { transition:border-color .2s,box-shadow .2s; }
        :is(#recommendationRunModal,#exploreModal) :is(.recommendation-card,.step-item):hover { border-color:#fdba74; box-shadow:0 4px 14px #9a34120a; }
        @keyframes recommendation-dialog-enter { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    }
    @media(prefers-reduced-motion:reduce) { :is(#recommendationRunModal,#exploreModal) * { animation:none !important; transition:none !important; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialogs = ['recommendationRunModal', 'exploreModal'].map(id => document.getElementById(id)).filter(Boolean);
    let activeDialog = null;
    let returnFocus = null;
    let previousOverflow = '';
    const sync = () => {
        const visible = dialogs.find(dialog => dialog.classList.contains('show'));
        if (visible) {
            if (!activeDialog) { returnFocus = document.activeElement; previousOverflow = document.body.style.overflow; }
            if (visible !== activeDialog) {
                visible.querySelector('.modal-body').scrollTop = 0;
                visible.querySelector('.modal-close').focus();
            }
            document.body.style.overflow = 'hidden';
        } else if (activeDialog) {
            if (!document.querySelector('.modal.show')) document.body.style.overflow = previousOverflow;
            if (returnFocus?.isConnected && returnFocus.getClientRects().length) returnFocus.focus();
        }
        activeDialog = visible || null;
    };
    const observer = new MutationObserver(sync);
    dialogs.forEach(dialog => {
        observer.observe(dialog, { attributes:true, attributeFilter:['class'] });
        const body = dialog.querySelector('.modal-body');
        const syncBusy = () => body.setAttribute('aria-busy', String(body.dataset.state === 'loading'));
        new MutationObserver(syncBusy).observe(body, { attributes:true, attributeFilter:['data-state'] });
        syncBusy();
        dialog.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); dialog.querySelector('.modal-close').click(); }
            if (event.key !== 'Tab') return;
            const controls = [...dialog.querySelectorAll('button,a[href],input,select,textarea,summary')].filter(el => !el.disabled && el.getClientRects().length);
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        });
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.querySelector('.modal-close').click(); });
    });
});
</script>
