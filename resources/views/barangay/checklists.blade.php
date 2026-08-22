@extends('barangay.layouts.app')

@section('active_nav', 'checklists')
@section('page_title', 'Checklist Center')
@section('page_subtitle', 'AI predictions, checklists, and mitigation')
@section('hero_title', 'Checklist Center')
@section('hero_copy', 'Review the latest records assigned to your barangay.')

@section('hero_badges')
    <span class="hero-badge"><i class="bi bi-stars"></i> AI predictions</span>
    <span class="hero-badge"><i class="bi bi-check2-square"></i> Checklists</span>
    <span class="hero-badge"><i class="bi bi-shield-check"></i> Mitigations</span>
@endsection

@section('content')
    <style>
        .checklist-center-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .checklist-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .checklist-summary-card,
        .checklist-panel-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .checklist-summary-card {
            padding: 22px;
        }

        .checklist-panel-card {
            padding: 24px;
        }

        .checklist-summary-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .checklist-summary-icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff7ed;
            color: #ea580c;
            font-size: 1.15rem;
        }

        .checklist-summary-label {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .checklist-summary-value {
            margin-top: 16px;
            color: #0f172a;
            font-size: clamp(1.85rem, 2.2vw, 2.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .checklist-summary-copy {
            margin-top: 10px;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .checklist-panel-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .checklist-panel-heading h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .checklist-panel-heading p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.55;
            max-width: 68ch;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
        }

        .checklist-table th,
        .checklist-table td {
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid rgba(241, 245, 249, 1);
        }

        .checklist-table th {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .checklist-table td {
            color: #334155;
            font-size: 0.88rem;
        }

        .checklist-table strong {
            color: #0f172a;
            font-weight: 800;
        }

        .checklist-inline-note {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .checklist-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .checklist-lane {
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #f8fafc;
            padding: 18px;
        }

        .checklist-lane h4 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .checklist-lane p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .checklist-lane-list {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .checklist-lane-item {
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 14px;
        }

        .checklist-lane-item strong {
            display: block;
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .checklist-lane-item span {
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        @media (max-width: 1200px) {
            .checklist-summary-grid,
            .checklist-columns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .checklist-summary-grid,
            .checklist-columns {
                grid-template-columns: 1fr;
            }

            .checklist-panel-card,
            .checklist-summary-card {
                padding: 20px;
                border-radius: 24px;
            }

            .checklist-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .mitigation-form-grid {
                grid-template-columns: 1fr;
            }
        }

        .checklist-refresh-note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Compact checklist center */
        .checklist-center-stack { gap: 14px; }
        .checklist-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .checklist-summary-card {
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 78px;
            padding: 13px 14px;
            border-radius: 18px;
        }
        .checklist-summary-icon { width: 36px; height: 36px; border-radius: 11px; font-size: .9rem; flex: 0 0 36px; }
        .checklist-summary-label { display: block; font-size: .64rem; }
        .checklist-summary-value { display: block; margin: 3px 0 0; font-size: 1.35rem; }
        .checklist-panel-card { padding: 16px; border-radius: 20px; }
        .checklist-panel-heading { align-items: center; margin-bottom: 10px; }
        .checklist-panel-heading h3 { font-size: .98rem; }
        .checklist-panel-kicker { display: block; margin-bottom: 2px; color: #c2410c; font-size: .62rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .checklist-table-wrap { width: 100%; overflow-x: auto; }
        .checklist-table { min-width: 620px; }
        .checklist-table th, .checklist-table td { padding: 9px 10px; vertical-align: middle; }
        .checklist-table th { font-size: .64rem; }
        .checklist-table td { font-size: .78rem; }
        .checklist-table td > strong, .checklist-table td > small { display: block; }
        .checklist-table td > small { max-width: 280px; margin-top: 2px; overflow: hidden; color: #94a3b8; font-size: .68rem; text-overflow: ellipsis; white-space: nowrap; }
        .checklist-view-button {
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; border: 1px solid rgba(217,119,6,.2);
            border-radius: 9px; background: #fff7ed; color: #b45309;
        }
        .checklist-compact-pagination { display: flex; align-items: center; justify-content: flex-end; gap: 6px; margin-top: 10px; }
        .checklist-page-button { min-width: 32px; height: 30px; padding: 0 9px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #475569; font-size: .7rem; font-weight: 800; }
        .checklist-page-button:disabled { opacity: .45; cursor: not-allowed; }

        .checklist-detail-modal[hidden] { display: none; }
        .checklist-detail-modal { position: fixed; inset: 0; z-index: 5000; display: flex; align-items: center; justify-content: center; padding: 14px; background: rgba(15,23,42,.5); backdrop-filter: blur(6px); }
        .checklist-detail-modal__card { display: flex; flex-direction: column; width: min(480px, 100%); max-height: min(78dvh, 590px); overflow: hidden; border: 1px solid rgba(245,158,11,.18); border-radius: 20px; background: #fff; box-shadow: 0 28px 70px rgba(15,23,42,.22); }
        .checklist-detail-modal__header, .checklist-detail-modal__footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex: 0 0 auto; padding: 12px 14px; }
        .checklist-detail-modal__header { border-bottom: 1px solid #f1f5f9; }
        .checklist-detail-modal__header h3 { margin: 0; color: #0f172a; font-size: .95rem; font-weight: 900; }
        .checklist-detail-modal__header button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #64748b; }
        .checklist-detail-modal__body { min-height: 0; overflow-y: auto; padding: 12px 14px; }
        .checklist-detail-modal__footer { justify-content: flex-end; border-top: 1px solid #f1f5f9; }
        .checklist-detail-modal__footer button { min-height: 32px; padding: 0 13px; border: 1px solid rgba(217,119,6,.2); border-radius: 9px; background: #fff7ed; color: #b45309; font-size: .72rem; font-weight: 800; }
        .checklist-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px; }
        .checklist-detail-grid > div { padding: 9px 10px; border: 1px solid #f1f5f9; border-radius: 11px; background: #f8fafc; }
        .checklist-detail-grid > .wide { grid-column: 1 / -1; }
        .checklist-detail-grid span, .checklist-detail-grid strong { display: block; }
        .checklist-detail-grid span { margin-bottom: 3px; color: #94a3b8; font-size: .61rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
        .checklist-detail-grid strong { color: #334155; font-size: .76rem; line-height: 1.45; overflow-wrap: anywhere; }
        .checklist-detail-steps { margin: 5px 0 0; padding-left: 18px; color: #334155; font-size: .74rem; }
        .checklist-detail-steps li { margin: 8px 0; }
        .checklist-detail-steps em { color: #b45309; font-size: .6rem; font-style: normal; font-weight: 900; }
        .checklist-step-form { display: grid; gap: 7px; padding: 9px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
        .checklist-step-text { font-size: .73rem !important; }
        .checklist-step-controls { display: grid; grid-template-columns: minmax(105px,.7fr) minmax(130px,1.3fr) auto; gap: 6px; }
        .checklist-step-controls select, .checklist-step-controls input { min-width: 0; height: 34px; padding: 0 9px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; font-size: .7rem; }
        .checklist-step-controls button { height: 34px; padding: 0 11px; border: 0; border-radius: 8px; background: #b45309; color: #fff; font-size: .68rem; font-weight: 800; }
        .barangay-visit-list { display: grid; gap: 7px; margin-top: 8px; }
        .barangay-visit-card { padding: 9px; border: 1px solid rgba(14,116,144,.14); border-radius: 10px; background: #f0f9ff; }
        .barangay-visit-card__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .barangay-visit-card__head strong { color: #0f4c5c; font-size: .66rem; }
        .barangay-visit-card__head time { color: #64748b; font-size: .55rem; white-space: nowrap; }
        .barangay-visit-card p { margin: 5px 0 0; color: #475569; font-size: .62rem; line-height: 1.45; }
        .barangay-visit-photos { display: grid; grid-template-columns: repeat(auto-fill,minmax(64px,1fr)); gap: 5px; margin-top: 7px; }
        .barangay-visit-photos a { overflow: hidden; border: 1px solid #dbeafe; border-radius: 7px; background: #fff; }
        .barangay-visit-photos img { display: block; width: 100%; height: 54px; object-fit: cover; }
        .barangay-signoff-form { display: grid; grid-template-columns: minmax(115px,.7fr) minmax(150px,1.3fr) auto; gap: 6px; margin-top: 8px; }
        .barangay-signoff-form select, .barangay-signoff-form input { min-width: 0; height: 33px; padding: 0 8px; border: 1px solid #bae6fd; border-radius: 7px; background: #fff; color: #334155; font-size: .62rem; }
        .barangay-signoff-form button { min-width: 76px; height: 33px; border: 0; border-radius: 7px; background: #0369a1; color: #fff; font-size: .61rem; font-weight: 850; }
        .barangay-signoff-state { margin-top: 7px; padding: 6px 8px; border-radius: 7px; background: #ecfdf5; color: #047857; font-size: .6rem; font-weight: 800; line-height: 1.4; }
        .barangay-signoff-state.needs_follow_up { background: #fef2f2; color: #b91c1c; }

        @media (max-width: 900px) { .checklist-summary-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (max-width: 520px) {
            .checklist-summary-grid { grid-template-columns: 1fr 1fr; gap: 7px; }
            .checklist-summary-card { min-height: 68px; padding: 10px; }
            .checklist-summary-icon { width: 32px; height: 32px; flex-basis: 32px; }
            .checklist-summary-label { font-size: .56rem; }
            .checklist-summary-value { font-size: 1.15rem; }
            .checklist-panel-card { padding: 12px; border-radius: 16px; }
            .checklist-detail-modal { padding: 9px; }
            .checklist-detail-modal__card { max-height: calc(100dvh - 18px); border-radius: 16px; }
            .checklist-detail-grid { grid-template-columns: 1fr; }
            .checklist-detail-grid > .wide { grid-column: auto; }
            .checklist-step-controls { grid-template-columns: 1fr; }
            .barangay-visit-card__head { align-items: flex-start; flex-direction: column; }
            .barangay-signoff-form { grid-template-columns: 1fr; }
        }

    </style>

    <div id="barangayChecklistContent" data-checklist-content-url="{{ route('barangay.checklists.content') }}">
        @include('barangay.partials.checklists-content')
    </div>

    <div class="checklist-detail-modal" id="checklistDetailModal" role="dialog" aria-modal="true" aria-labelledby="checklistDetailTitle" hidden>
        <div class="checklist-detail-modal__card">
            <div class="checklist-detail-modal__header">
                <h3 id="checklistDetailTitle">Record Details</h3>
                <button type="button" data-checklist-modal-close aria-label="Close details"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="checklist-detail-modal__body" id="checklistDetailBody"></div>
            <div class="checklist-detail-modal__footer"><button type="button" data-checklist-modal-close>Close</button></div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('barangayChecklistContent');
            const contentUrl = container?.getAttribute('data-checklist-content-url');
            const modal = document.getElementById('checklistDetailModal');
            const modalBody = document.getElementById('checklistDetailBody');

            if (!container || !contentUrl || !modal || !modalBody) {
                return;
            }

            let isRefreshing = false;
            let lastFocusedElement = null;
            const pageState = {};

            const initializePagination = () => {
                container.querySelectorAll('[data-compact-list]').forEach((table) => {
                    const key = table.dataset.compactList;
                    const pageSize = Math.max(1, Number(table.dataset.pageSize || 6));
                    const rows = Array.from(table.querySelectorAll('[data-compact-row]'));
                    const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
                    const currentPage = Math.min(totalPages, Math.max(1, Number(pageState[key] || 1)));
                    const pagination = container.querySelector(`[data-pagination-for="${key}"]`);
                    pageState[key] = currentPage;

                    rows.forEach((row, index) => {
                        row.hidden = index < (currentPage - 1) * pageSize || index >= currentPage * pageSize;
                    });

                    if (!pagination) return;
                    if (totalPages <= 1) {
                        pagination.innerHTML = '';
                        return;
                    }

                    pagination.innerHTML = `
                        <button type="button" class="checklist-page-button" data-page-key="${key}" data-page-value="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="checklist-page-button" disabled>${currentPage} / ${totalPages}</button>
                        <button type="button" class="checklist-page-button" data-page-key="${key}" data-page-value="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''} aria-label="Next page"><i class="bi bi-chevron-right"></i></button>
                    `;
                });
            };

            const closeModal = () => {
                modal.hidden = true;
                modalBody.innerHTML = '';
                document.body.style.overflow = '';
                lastFocusedElement?.focus?.();
            };

            container.addEventListener('click', (event) => {
                const pageButton = event.target.closest('[data-page-key]');
                if (pageButton && !pageButton.disabled) {
                    pageState[pageButton.dataset.pageKey] = Number(pageButton.dataset.pageValue || 1);
                    initializePagination();
                    return;
                }

                const detailButton = event.target.closest('[data-checklist-detail]');
                if (!detailButton) return;

                const template = container.querySelector(`#${CSS.escape(detailButton.dataset.checklistDetail)}`);
                if (!template) return;

                lastFocusedElement = detailButton;
                modalBody.replaceChildren(template.content.cloneNode(true));
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
                modal.querySelector('[data-checklist-modal-close]')?.focus();
            });

            modal.querySelectorAll('[data-checklist-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

            const refreshChecklistContent = async () => {
                if (isRefreshing || document.hidden || !modal.hidden) {
                    return;
                }

                isRefreshing = true;

                try {
                    const response = await fetch(contentUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Checklist refresh failed with status ${response.status}`);
                    }

                    const html = await response.text();
                    container.innerHTML = html;
                    initializePagination();
                } catch (error) {
                    console.error('Unable to refresh barangay checklist content:', error);
                } finally {
                    isRefreshing = false;
                }
            };

            initializePagination();
            const requestedChecklist = new URLSearchParams(window.location.search).get('checklist');
            if (requestedChecklist) {
                container.querySelector(`[data-checklist-detail="${CSS.escape(requestedChecklist)}"]`)?.click();
            }
            window.setInterval(refreshChecklistContent, 30000);
        });
    </script>
@endpush
