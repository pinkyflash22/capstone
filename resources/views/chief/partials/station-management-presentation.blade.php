{{-- Checklist-style station workspace; registry and media handlers remain intact. --}}
<style>
    .station-management-workspace.management-container { display:flex; flex-direction:column; gap:14px; width:100%; max-width:1280px; min-width:0; margin-inline:auto; }
    .station-management-workspace > .chief-theme-hero { order:1; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:20px; margin:0; padding:12px 0; border:0; border-radius:0; background:transparent; box-shadow:none; }
    .station-management-workspace > .chief-theme-hero::before, .station-management-workspace > .chief-theme-hero::after { display:none; }
    .station-management-workspace .chief-theme-hero__eyebrow { padding:0; border:0; background:transparent; color:#b91c1c; font-size:10px; letter-spacing:.12em; }
    .station-management-workspace .chief-theme-hero__title { color:#172033; font-size:clamp(23px,2.5vw,30px); line-height:1.25; letter-spacing:-.04em; }
    .station-management-workspace .chief-theme-hero__copy { font-size:13px; color:#64748b; }
    .station-management-workspace .chief-theme-badges { display:flex; flex-wrap:wrap; gap:8px; }
    .station-management-workspace .chief-theme-badge { white-space:normal; overflow-wrap:anywhere; background:#fff; border-color:#e2e8f0; color:#475569; font-size:11px; }
    .station-management-workspace .chief-theme-summary-grid { display:flex; align-items:stretch; flex-wrap:wrap; gap:10px; }
    .station-management-workspace .chief-theme-summary-card { padding:14px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:none; }
    .station-management-workspace .chief-theme-summary-label, .station-management-workspace .chief-theme-summary-copy { color:#64748b; font-size:11px; }
    .station-management-workspace .chief-theme-summary-value { color:#172033; font-size:26px; }
    .station-management-workspace > .station-media-panel { order:2; display:block; padding:0; margin:0 !important; border:0; background:transparent; box-shadow:none; }
    .station-management-workspace .station-media-tools { display:grid; grid-template-columns:minmax(260px,340px) minmax(0,1fr); gap:14px; align-items:start; }
    .station-management-workspace .station-profile-card { grid-column:1; grid-row:1 / 3; }
    .station-management-workspace :is(.station-gallery-upload-card,.station-gallery-library) { grid-column:2; }
    .station-management-workspace .station-media-card { min-width:0; padding:18px; background:#fff; border:1px solid #e2e8f0; border-top:3px solid #dc2626; border-radius:16px; box-shadow:0 3px 14px #0f172a04; }
    .station-management-workspace .station-media-card h3 { font-size:17px; letter-spacing:-.025em; }
    .station-management-workspace .station-media-card p { color:#64748b; font-size:12px; line-height:1.6; }
    .station-management-workspace .station-media-profile { display:flex; aspect-ratio:4 / 3; min-height:0; height:auto; padding:0; margin:14px 0; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; box-shadow:none; }
    .station-management-workspace .station-media-profile img { width:100%; height:100%; object-fit:contain; border-radius:0; background:transparent; }
    .station-photo-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; min-height:150px; padding:24px; text-align:center; color:#64748b; font-size:12px; }
    .station-photo-empty i { color:#d86616; font-size:28px; }
    .station-management-workspace .station-upload-box { padding:12px; border:1px dashed #cbd5e1; border-radius:10px; background:#f8fafc; box-shadow:none; }
    .station-management-workspace .station-upload-help { font-size:11px; line-height:1.5; color:#64748b; }
    .station-management-workspace .station-upload-label { color:#334155; font-size:12px; }
    .station-management-workspace .station-media-card input { min-width:0; max-width:100%; min-height:44px; border-radius:9px; font-size:13px; }
    .station-management-workspace input[type=file] { width:100%; padding:4px; background:transparent; border:0; }
    .station-management-workspace input[type=file]::file-selector-button { min-height:40px; margin-right:10px; padding:8px 12px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed; color:#9a3412; cursor:pointer; }
    .station-management-workspace .station-gallery-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; justify-content:stretch; }
    .station-management-workspace .station-gallery-item { width:100%; max-width:none; min-width:0; aspect-ratio:auto; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:none; }
    .station-management-workspace .station-gallery-trigger { display:block; height:100%; text-align:left; }
    .station-management-workspace .station-gallery-item img { aspect-ratio:4 / 3; width:100%; height:auto; object-fit:cover; border-radius:12px 12px 0 0; }
    .station-management-workspace .station-gallery-meta { position:static; padding:10px; background:#fff; color:#334155; }
    .station-management-workspace .station-gallery-meta-title { display:block; font-size:12px; color:#334155; white-space:normal; overflow-wrap:anywhere; }
    .station-management-workspace .station-gallery-meta small { display:block; margin-top:4px; color:#64748b; font-size:10px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
    .station-management-workspace .station-gallery-menu-button { width:44px; height:44px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; color:#334155; text-shadow:none; box-shadow:0 2px 8px #0f172a15; }
    .station-management-workspace .station-gallery-menu-item { min-height:44px; font-size:13px; }
    .station-gallery-count { display:inline-flex; padding:4px 8px; margin-left:6px; background:#fff1e8; border-radius:8px; color:#b91c1c; font-size:12px; }
    .station-upload-preview { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin:10px 0; }
    .station-upload-preview[hidden] { display:none; }
    .station-upload-preview figure { margin:0; min-width:0; }
    .station-upload-preview img { width:100%; aspect-ratio:4 / 3; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; }
    .station-upload-preview figcaption { font-size:10px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; color:#64748b; }
    .station-management-workspace > .management-summary-grid { order:3; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin:0 !important; }
    .station-management-workspace .management-summary-card { padding:14px; min-width:0; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:none; }
    .station-management-workspace .management-summary-value { color:#172033; font-size:26px; }
    .station-management-workspace > .tab-navigation { order:4; display:flex; gap:6px; padding:5px; margin:0 !important; border:1px solid #e2e8f0; border-radius:11px; background:#fff; box-shadow:none; }
    .station-management-workspace .tab-btn { flex:1; min-width:0; min-height:44px; border:0; border-radius:8px; font-size:12px; }
    .station-management-workspace .tab-btn.active { background:#fff1e8; color:#b91c1c; box-shadow:none; }
    .station-management-workspace > .search-filter-bar { order:5; padding:14px; margin:0 !important; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:none; }
    .station-management-workspace :is(.filter-actions,.search-filter-bar) { flex-wrap:wrap; gap:8px; min-width:0; }
    .station-management-workspace :is(.search-box,.filter-select) { min-width:0; max-width:100%; }
    .station-management-workspace :is(.btn-primary,.btn-outline,.btn-filter) { min-height:44px; border-radius:9px; font-size:12px; }
    .station-management-workspace > .table-card { order:6; border:1px solid #E9E5DE; border-radius:16px; margin:0 !important; box-shadow:none; min-width:0; max-width:100%; }
    .station-management-workspace .table-header { padding:16px 18px; background:#fff; }
    .station-management-workspace .table-header h2 { font-size:17px; }
    .station-management-workspace .table-responsive { max-width:100%; overflow-x:auto; }
    .station-management-workspace .data-table { border-collapse:separate; border-spacing:0; table-layout:auto; }
    .station-management-workspace .data-table th { background:#f8fafc; color:#64748b; font-size:10px; letter-spacing:.06em; }
    .station-management-workspace .data-table th:first-child, .station-management-workspace .data-table td:first-child { width:30%; }
    .station-management-workspace .data-table th:nth-child(2), .station-management-workspace .data-table td:nth-child(2) { width:28%; }
    .station-management-workspace .data-table td { white-space:normal; overflow:visible; text-overflow:clip; vertical-align:top; }
    .station-management-workspace .management-data-row { position:relative; background:#fff; }
    .station-management-workspace .management-data-row::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:#A5680B; }
    .station-management-workspace .management-data-row--high::before, .station-management-workspace .management-data-row--critical::before { background:#B23A22; }
    .station-management-workspace .management-data-row--medium::before { background:#A5680B; }
    .station-management-workspace .management-data-row--low::before { background:#1E7A4C; }
    .station-management-workspace .management-primary-cell { display:grid; gap:6px; min-width:0; }
    .station-management-workspace .management-primary-cell strong { color:#172033; font-size:14px; font-weight:850; line-height:1.35; white-space:normal; overflow-wrap:anywhere; }
    .station-management-workspace .management-primary-cell small, .station-management-workspace .management-wrap-text { color:#64748b; font-size:12px; line-height:1.45; white-space:normal; overflow-wrap:anywhere; }
    .station-management-workspace .management-row-meta { display:flex; flex-wrap:wrap; gap:6px; }
    .station-management-workspace .management-row-tag { display:inline-flex; align-items:center; width:max-content; max-width:100%; min-height:22px; padding:3px 8px; border-radius:999px; background:#F0EEEA; color:#6B6E76; font-size:10px; font-weight:800; line-height:1.2; text-transform:capitalize; }
    .station-management-workspace .management-row-tag--history { background:#F3EEFB; color:#7A4FB8; }
    .station-management-workspace .management-location-cell { display:flex; align-items:flex-start; gap:8px; min-width:0; color:#475569; font-size:12px; line-height:1.5; white-space:normal; overflow-wrap:anywhere; }
    .station-management-workspace .management-location-cell i { margin-top:3px; color:#A5680B; flex:0 0 auto; }
    .station-management-workspace .management-priority-stack { display:grid; gap:7px; justify-items:start; }
    .station-management-workspace .management-priority-pill { display:inline-flex; align-items:center; gap:7px; min-height:26px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:850; }
    .station-management-workspace .management-priority-pill--high, .station-management-workspace .management-priority-pill--critical { background:#FBEAE4; color:#B23A22; }
    .station-management-workspace .management-priority-pill--medium { background:#FBF0DD; color:#A5680B; }
    .station-management-workspace .management-priority-pill--low { background:#E7F5EC; color:#1E7A4C; }
    .station-management-workspace .management-priority-dot, .station-management-workspace .management-state-dot { width:7px; height:7px; border-radius:999px; background:currentColor; flex:0 0 auto; }
    .station-management-workspace .management-state-label { display:inline-flex; align-items:center; gap:7px; color:#3A6FB0; font-size:11px; font-weight:750; text-transform:capitalize; }
    .station-management-workspace .management-state-label--resolved, .station-management-workspace .management-state-label--approved, .station-management-workspace .management-state-label--active, .station-management-workspace .management-state-label--available { color:#1E7A4C; }
    .station-management-workspace .management-state-label--rejected, .station-management-workspace .management-state-label--damaged, .station-management-workspace .management-state-label--inactive { color:#B23A22; }
    .station-management-workspace .management-date-text { color:#4A4E58; font-size:12px; font-weight:700; white-space:nowrap; }
    .station-management-workspace .management-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:6px; min-width:max-content; }
    .station-management-workspace .management-icon-action { width:34px; height:34px; margin:0; border:1px solid #E9E5DE; border-radius:999px; background:#fff; color:#4A4E58; }
    .station-management-workspace .management-icon-action:hover { background:#FAF8F5; color:#E4602C; }
    .station-management-workspace .management-more-menu { position:relative; display:inline-flex; }
    .station-management-workspace .management-more-dropdown { position:absolute; right:0; top:calc(100% + 6px); z-index:30; display:none; min-width:132px; padding:6px; border:1px solid #E9E5DE; border-radius:12px; background:#fff; box-shadow:0 12px 28px #0f172a18; }
    .station-management-workspace .management-more-menu:hover .management-more-dropdown, .station-management-workspace .management-more-menu:focus-within .management-more-dropdown { display:block; }
    .station-management-workspace .management-more-item { width:100%; height:auto; min-height:36px; justify-content:flex-start; gap:8px; padding:8px 10px; border:0; border-radius:9px; color:#B23A22; font-size:12px; font-weight:800; }
    .station-management-workspace .management-more-item:hover { background:#FBEAE4; color:#B23A22; }
    .station-management-workspace .management-more-item span { display:inline; }
    .station-management-workspace :is(button,input,select,a):focus-visible { outline:3px solid #f59e0b; outline-offset:3px; }
    /* Gallery dialog sizing and scrolling use the shared modal stylesheet. */
    #galleryPreviewModal .station-gallery-preview { display:grid; grid-template-columns:minmax(0,2fr) minmax(200px,1fr); gap:18px; }
    #galleryPreviewModal .station-gallery-preview-frame { min-width:0; background:#f1f5f9; border-radius:12px; }
    #galleryPreviewModal .station-gallery-preview-frame img { width:100%; height:auto; max-height:65dvh; object-fit:contain; }
    #galleryPreviewModal .station-gallery-preview-info { align-content:start; min-width:0; }
    #galleryPreviewModal .station-gallery-preview-row { grid-template-columns:minmax(0,1fr); gap:6px; }
    @media(max-width:1024px) {
        .station-management-workspace > .chief-theme-hero { grid-template-columns:minmax(0,1fr); }
        .station-management-workspace .station-media-tools { grid-template-columns:minmax(0,1fr); }
        .station-management-workspace .station-media-card { grid-column:1; grid-row:auto; }
        .station-management-workspace .station-media-profile { max-height:300px; }
        .station-management-workspace > .management-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media(max-width:640px) {
        .station-management-workspace.management-container { padding-inline:10px; padding-bottom:calc(106px + env(safe-area-inset-bottom,0px)); gap:12px; }
        .station-management-workspace .station-media-card { padding:14px; }
        .station-management-workspace .station-gallery-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .station-management-workspace > .management-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .station-management-workspace > .tab-navigation { flex-wrap:wrap; }
        .station-management-workspace .tab-btn { flex:1 1 40%; }
        .station-management-workspace .filter-actions { display:flex; width:100%; flex-wrap:wrap; }
        .station-management-workspace :is(input,select) { font-size:16px; }
        .station-management-workspace .table-responsive { overflow-x:visible; }
        .station-management-workspace .data-table, .station-management-workspace .data-table thead, .station-management-workspace .data-table tbody, .station-management-workspace .data-table tr, .station-management-workspace .data-table td { display:block; width:100%; }
        .station-management-workspace .data-table thead { display:none; }
        .station-management-workspace .management-data-row { margin-bottom:10px; padding:12px 12px 12px 16px; border:1px solid #E9E5DE; border-radius:16px; }
        .station-management-workspace .management-data-row::before { border-radius:16px 0 0 16px; }
        .station-management-workspace .data-table td { display:grid; grid-template-columns:108px minmax(0,1fr); gap:10px; padding:8px 0; border:0; }
        .station-management-workspace .data-table td::before { content:attr(data-label); color:#64748b; font-size:10px; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
        .station-management-workspace .data-table td:first-child { grid-template-columns:minmax(0,1fr); width:100%; }
        .station-management-workspace .data-table td:first-child::before { display:none; }
        .station-management-workspace .management-row-actions { justify-content:flex-start; }
        #galleryPreviewModal .station-gallery-preview { grid-template-columns:minmax(0,1fr); }
        #galleryPreviewModal .station-gallery-preview-frame img { max-height:45dvh; }
    }
    @media(prefers-reduced-motion:no-preference) { .station-management-workspace :is(.station-gallery-item,.management-summary-card) { transition:border-color .2s,box-shadow .2s; } .station-management-workspace :is(.station-gallery-item,.management-summary-card):hover { border-color:#fdba74; box-shadow:0 5px 18px #9a341210; } }
    @media(prefers-reduced-motion:reduce) { .station-management-workspace * { animation:none !important; transition:none !important; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.station-management-workspace .station-upload-form input[type=file]').forEach(input => {
        const preview = document.createElement('div');
        preview.className = 'station-upload-preview';
        preview.hidden = true;
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        input.closest('.station-upload-field').append(status, preview);
        let urls = [];
        const release = () => { urls.forEach(url => URL.revokeObjectURL(url)); urls = []; };
        input.addEventListener('change', () => {
            release();
            preview.replaceChildren();
            const files = [...input.files];
            preview.hidden = !files.length;
            const limit = input.multiple ? 6 : 1;
            const invalid = files.length > limit || files.some(file => file.size > 5 * 1024 * 1024 || !['image/jpeg','image/png','image/webp'].includes(file.type));
            const message = invalid ? `Choose up to ${limit} JPG, PNG, or WEBP image${limit > 1 ? 's' : ''}, no larger than 5 MB each.` : '';
            input.setCustomValidity(message);
            status.textContent = message || (files.length ? `${files.length} selected — not uploaded yet.` : '');
            if (invalid) return;
            files.forEach(file => {
                const figure = document.createElement('figure');
                const img = document.createElement('img');
                const caption = document.createElement('figcaption');
                const url = URL.createObjectURL(file); urls.push(url);
                img.src = url; img.alt = `Selected photo: ${file.name}`;
                caption.textContent = file.name;
                figure.append(img, caption); preview.append(figure);
            });
        });
        window.addEventListener('pagehide', release);
    });
});
</script>
