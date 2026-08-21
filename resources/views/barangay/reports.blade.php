@extends('barangay.layouts.app')

@section('active_nav', 'reports')
@section('page_title', 'Fire Reports')
@section('page_subtitle', 'Barangay fire records')
@section('hero_title', 'Fire Reports')
@section('hero_copy', 'Create and review fire records for your barangay.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-fire-flame-curved"></i> Fire records only</span>
    <span class="hero-badge"><i class="fa-solid fa-location-crosshairs"></i> Current location capture</span>
@endsection

@section('content')
    @php
        $reportsOnPage = $fireRecords->count();
        $reportsTotal = (int) ($fireRecordTotalCount ?? $fireRecords->total());
        $pendingRecords = collect($fireRecords->items())->filter(fn ($record) => strcasecmp((string) ($record['status'] ?? ''), 'Reported') === 0)->count();
        $completedRecords = collect($fireRecords->items())->filter(fn ($record) => in_array(strtolower((string) ($record['status'] ?? '')), ['closed', 'completed'], true))->count();
    @endphp

    @if (session('success'))
        <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Unable to save the fire record.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="fire-report-shell">
    <section class="panel-card fire-report-shell__main">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="panel-eyebrow">Barangay Fire Record Register</div>
                <h3 class="panel-title">Recorded fire reports</h3>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">   
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong class="block text-lg text-slate-900" id="fireReportVisibleCount">{{ $fireRecords->count() }}</strong>
                    Records on page
                    @if(($fireRecordTotalCount ?? $fireRecords->count()) !== $fireRecords->count())
                        <span class="block text-xs text-amber-700">out of {{ $fireRecordTotalCount }} total</span>
                    @endif
                </div>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-600 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-200 transition hover:bg-orange-700" data-open-modal="createFireReportModal">
                    <i class="fa-solid fa-plus"></i>
                    Report Fire
                </button>
            </div>
        </div>

        <form id="fireReportFilters" class="mt-6 fire-report-filterbar" data-records-url="{{ route('barangay.reports.data') }}">
            <div class="fire-report-filterbar__grid">
                <div class="fire-report-form__field">
                    <label for="fire_record_search">Search</label>
                    <input id="fire_record_search" name="search" type="text" value="{{ $fireRecordFilters['search'] ?? '' }}" placeholder="Search title, cause, location, or notes">
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_classification">Classification</label>
                    <select id="fire_record_classification" name="classification">
                        <option value="">All classifications</option>
                        <option value="accidental" @selected(($fireRecordFilters['classification'] ?? '') === 'accidental')>Accidental</option>
                        <option value="intentional" @selected(($fireRecordFilters['classification'] ?? '') === 'intentional')>Intentional</option>
                        <option value="undetermined" @selected(($fireRecordFilters['classification'] ?? '') === 'undetermined')>Undetermined</option>
                    </select>
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_date_from">Date From</label>
                    <input id="fire_record_date_from" name="date_from" type="date" value="{{ $fireRecordFilters['date_from'] ?? '' }}">
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_date_to">Date To</label>
                    <input id="fire_record_date_to" name="date_to" type="date" value="{{ $fireRecordFilters['date_to'] ?? '' }}">
                </div>
            </div>
            <div class="fire-report-filterbar__actions">
                <button type="button" class="fire-report-secondary-btn" id="resetFireReportFilters"><i class="fa-solid fa-rotate-left"></i> Reset</button>
            </div>
        </form>

        <div class="empty-state mt-6" id="fireReportsEmpty" @if(!$fireRecords->isEmpty()) hidden @endif>No fire records found.</div>
        <div class="table-wrap mt-6" id="fireReportsTableWrap" @if($fireRecords->isEmpty()) hidden @endif>
                <table class="fire-report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Classification</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fireRecords as $record)
                            <tr>
                                <td>{{ $record['date'] }}</td>
                                <td><strong>{{ $record['title'] }}</strong><small>{{ $record['cause'] }}</small></td>
                                <td>{{ $record['classification'] }}</td>
                                <td>{{ $record['location'] }}</td>
                                <td><span class="fire-report-status">{{ $record['status'] }}</span></td>
                                <td>
                                    <div class="fire-report-actions">
                                        <button type="button" data-view-fire-report="{{ $record['id'] }}" aria-label="View report"><i class="fa-solid fa-eye"></i></button>
                                        <button type="button" data-open-edit-fire-report="{{ $record['id'] }}" aria-label="Edit report"><i class="fa-solid fa-pen"></i></button>
                                        <button type="button" class="delete" data-delete-fire-report="{{ $record['id'] }}" aria-label="Delete report"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
        <div class="fire-report-pagination" id="fireReportsPagination" data-current-page="{{ $fireRecords->currentPage() }}" data-last-page="{{ max(1, $fireRecords->lastPage()) }}" data-total="{{ $fireRecords->total() }}" data-from="{{ $fireRecords->firstItem() }}" data-to="{{ $fireRecords->lastItem() }}">
            <span id="fireReportsSummary">@if($fireRecords->total()) Showing {{ $fireRecords->firstItem() }}–{{ $fireRecords->lastItem() }} of {{ $fireRecords->total() }} @else No records @endif</span>
            <div id="fireReportsPaginationButtons"></div>
        </div>
    </section>

    <aside class="fire-report-shell__side">
        <article class="panel-card fire-report-side-card">
            <div class="panel-eyebrow">Scope</div>
            <h3 class="panel-title">Barangay filter</h3>
            <p class="panel-copy !mt-1">Only records tied to your logged-in barangay are loaded here.</p>

            <div class="fire-report-summary-grid fire-report-summary-grid--side">
                <article class="fire-report-summary-card">
                    <div class="tiny-label">Visible</div>
                    <strong>{{ $reportsOnPage }}</strong>
                    <span>{{ $reportsTotal }} total record{{ $reportsTotal === 1 ? '' : 's' }}</span>
                </article>
                <article class="fire-report-summary-card">
                    <div class="tiny-label">Reported</div>
                    <strong>{{ $pendingRecords }}</strong>
                    <span>Still marked as reported</span>
                </article>
                <article class="fire-report-summary-card">
                    <div class="tiny-label">Closed</div>
                    <strong>{{ $completedRecords }}</strong>
                    <span>Closed or completed on this page</span>
                </article>
            </div>

            <div class="list-stack mt-4">
                <div class="stat-line">
                    <strong>Barangay</strong>
                    <span class="tag low">{{ $barangay?->barangay_name ?? 'Not linked' }}</span>
                </div>
                <div class="stat-line">
                    <strong>Hall</strong>
                    <span class="tag complied">{{ $hall?->hall_name ?? 'No hall linked' }}</span>
                </div>
            </div>
        </article>
    </aside>
    </section>

    <script type="application/json" id="fireReportsInitialData">@json($fireRecords->items())</script>

    <div id="viewFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog fire-report-modal__dialog--details" role="dialog" aria-modal="true" aria-labelledby="viewFireReportTitle">
            <div class="fire-report-modal__header"><h3 class="panel-title" id="viewFireReportTitle">Fire Report</h3><button type="button" class="fire-report-modal__close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
            <div class="fire-report-detail-grid" id="viewFireReportContent"></div>
            <div class="fire-report-modal__actions"><button type="button" class="fire-report-secondary-btn" data-close-modal>Close</button></div>
        </div>
    </div>

    <div id="deleteFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog fire-report-modal__dialog--confirm" role="alertdialog" aria-modal="true" aria-labelledby="deleteFireReportTitle">
            <div class="fire-report-modal__header"><h3 class="panel-title" id="deleteFireReportTitle">Delete fire report?</h3><button type="button" class="fire-report-modal__close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
            <div class="fire-report-delete-copy"><i class="fa-solid fa-triangle-exclamation"></i><span>The report and its linked incident will be removed.</span></div>
            <form method="POST" id="deleteFireReportForm">
                @csrf
                @method('DELETE')
                <div class="fire-report-modal__actions"><button type="button" class="fire-report-secondary-btn" data-close-modal>Cancel</button><button type="submit" class="fire-report-danger-btn">Delete</button></div>
            </form>
        </div>
    </div>

    <div id="createFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog">
            <div class="fire-report-modal__header">
                <div>
                    <div class="panel-eyebrow">New Fire Record</div>
                    <h3 class="panel-title">Submit a barangay fire report</h3>
                </div>
                <button type="button" class="fire-report-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('barangay.reports.fire.store') }}" class="fire-report-form">
                @csrf
                @include('barangay.partials.fire-report-form-fields', ['prefix' => 'create', 'record' => null])
                <div class="fire-report-modal__actions">
                    <button type="button" class="fire-report-secondary-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="fire-report-primary-btn">Save Fire Report</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog">
            <div class="fire-report-modal__header">
                <div>
                    <div class="panel-eyebrow">Update Fire Record</div>
                    <h3 class="panel-title">Edit barangay fire report</h3>
                </div>
                <button type="button" class="fire-report-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="" id="editFireReportForm" class="fire-report-form">
                @csrf
                @method('PUT')
                @include('barangay.partials.fire-report-form-fields', ['prefix' => 'edit', 'record' => null])
                <div class="fire-report-modal__actions">
                    <button type="button" class="fire-report-secondary-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="fire-report-primary-btn">Update Fire Report</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .fire-report-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.78fr);
        gap: 1rem;
        align-items: start;
    }

    .fire-report-shell__main,
    .fire-report-side-card {
        padding: 1.15rem 1.2rem;
    }

    .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between {
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between > div:last-child {
        width: 100%;
        justify-content: space-between;
    }

    .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between .rounded-2xl.border.border-amber-100.bg-amber-50 {
        min-width: 178px;
        border-radius: 18px;
        background: #f8fafc;
        border-color: rgba(226, 232, 240, 0.92);
        color: #64748b;
    }

    .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between .rounded-2xl.border.border-amber-100.bg-amber-50 strong {
        color: #0f172a !important;
        font-size: 1.2rem;
        font-weight: 900;
    }

    .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between > div:last-child > button {
        min-height: 44px;
        border-radius: 14px;
        padding-inline: 1rem;
        font-size: 0.82rem;
        font-weight: 800;
        box-shadow: 0 12px 24px rgba(234, 88, 12, 0.18);
    }

    .fire-report-summary-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .fire-report-summary-card {
        border-radius: 18px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: #f8fafc;
        padding: 0.85rem 0.95rem;
    }

    .fire-report-summary-card strong {
        display: block;
        margin-top: 0.35rem;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 900;
    }

    .fire-report-summary-card span {
        display: block;
        margin-top: 0.25rem;
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.45;
    }

    .fire-report-side-card .stat-line {
        padding: 0.8rem 0.85rem;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.88);
        background: #fff;
    }

    .fire-report-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }

    .fire-report-modal.hidden {
        display: none;
    }

    .fire-report-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(4px);
    }

    .fire-report-modal__dialog {
        position: relative;
        width: min(960px, 100%);
        max-height: calc(100vh - 2rem);
        overflow: auto;
        border-radius: 28px;
        background: #fffdf9;
        border: 1px solid rgba(251, 191, 36, 0.18);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18);
        padding: 1.5rem;
    }

    .fire-report-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .fire-report-modal__close {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: white;
        color: #475569;
    }

    .fire-report-form {
        display: grid;
        gap: 1.25rem;
    }

    .fire-report-form__section {
        border-radius: 24px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background: linear-gradient(180deg, #fffef9 0%, #ffffff 100%);
        padding: 1rem;
    }

    .fire-report-form__section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .fire-report-form__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 34px;
        padding: 0 0.85rem;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .fire-report-form__hint {
        margin: 0.5rem 0 0;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .fire-report-required {
        margin-left: 0.35rem;
        color: #c2410c;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .fire-report-form__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .fire-report-form__field--full {
        grid-column: 1 / -1;
    }

    .fire-report-form__field label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.92rem;
        font-weight: 600;
        color: #334155;
    }

    .fire-report-form__field input,
    .fire-report-form__field select,
    .fire-report-form__field textarea {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(203, 213, 225, 0.95);
        background: white;
        padding: 0.85rem 1rem;
        color: #0f172a;
    }

    .fire-report-form__field textarea {
        min-height: 110px;
        resize: vertical;
    }

    .fire-report-location-panel {
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.22);
        background: linear-gradient(180deg, #fffaf0 0%, #ffffff 100%);
        padding: 1rem;
    }

    .fire-report-location-panel__header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .fire-report-location-btn,
    .fire-report-primary-btn,
    .fire-report-secondary-btn {
        border-radius: 16px;
        padding: 0.85rem 1.1rem;
        font-weight: 600;
    }

    .fire-report-location-btn,
    .fire-report-secondary-btn {
        border: 1px solid rgba(251, 191, 36, 0.25);
        background: #fff7ed;
        color: #c2410c;
    }

    .fire-report-primary-btn {
        border: none;
        background: #ea580c;
        color: white;
        box-shadow: 0 16px 34px rgba(234, 88, 12, 0.24);
    }

    .fire-report-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .fire-report-filterbar {
        margin-top: 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
        padding: 1rem;
    }

    .fire-report-filterbar__grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 1rem;
    }

    .fire-report-filterbar__actions {
        margin-top: 1rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .fire-report-shell {
            grid-template-columns: 1fr;
        }

        .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between > div:last-child {
            align-items: stretch;
            flex-direction: column;
        }

        .fire-report-shell__main > .flex.flex-col.gap-4.lg\:flex-row.lg\:items-center.lg\:justify-between > div:last-child > button {
            width: 100%;
        }

        .fire-report-form__section {
            border-radius: 22px;
            padding: 0.9rem;
        }

        .fire-report-form__grid {
            grid-template-columns: 1fr;
        }

        .fire-report-filterbar__grid {
            grid-template-columns: 1fr;
        }

        .fire-report-modal {
            align-items: flex-end;
            padding: 0;
        }

        .fire-report-modal__dialog {
            width: 100%;
            max-height: min(92dvh, 100dvh - 0.5rem);
            border-radius: 28px 28px 0 0;
            padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom, 0px));
        }

        .fire-report-modal__actions {
            flex-direction: column-reverse;
        }

        .fire-report-modal__actions .fire-report-primary-btn,
        .fire-report-modal__actions .fire-report-secondary-btn,
        .fire-report-location-btn,
        .fire-report-filterbar__actions .fire-report-primary-btn,
        .fire-report-filterbar__actions .fire-report-secondary-btn {
            width: 100%;
            justify-content: center;
        }

        .fire-report-filterbar__actions {
            flex-direction: column-reverse;
        }
    }

    /* Compact report register */
    .fire-report-filterbar { margin-top: 14px; padding: 12px; border-radius: 16px; }
    .fire-report-filterbar.is-loading { opacity: .65; pointer-events: none; }
    .fire-report-filterbar__grid { grid-template-columns: minmax(180px, 2fr) repeat(3, minmax(130px, 1fr)); gap: 9px; }
    .fire-report-filterbar__actions { margin-top: 9px; }
    .fire-report-form__field label { margin-bottom: 3px; font-size: .72rem; }
    .fire-report-form__field input, .fire-report-form__field select, .fire-report-form__field textarea { padding: 9px 11px; border-radius: 11px; font-size: .78rem; }
    .fire-report-table { min-width: 760px; }
    .fire-report-table th, .fire-report-table td { padding: 9px 10px !important; font-size: .76rem; vertical-align: middle; }
    .fire-report-table td strong, .fire-report-table td small { display: block; }
    .fire-report-table td small { max-width: 220px; margin-top: 2px; overflow: hidden; color: #94a3b8; font-size: .67rem; text-overflow: ellipsis; white-space: nowrap; }
    .fire-report-status { display: inline-flex; min-height: 25px; align-items: center; padding: 0 8px; border-radius: 999px; background: #fff7ed; color: #b45309; font-size: .64rem; font-weight: 800; }
    .fire-report-actions { display: flex; gap: 5px; }
    .fire-report-actions button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #b45309; }
    .fire-report-actions button.delete { color: #dc2626; background: #fef2f2; border-color: #fecaca; }
    .fire-report-pagination { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; color: #64748b; font-size: .72rem; }
    #fireReportsPaginationButtons { display: flex; gap: 5px; }
    .fire-report-page-btn { min-width: 32px; height: 30px; padding: 0 9px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #475569; font-size: .7rem; font-weight: 800; }
    .fire-report-page-btn:disabled { opacity: .45; cursor: not-allowed; }

    .fire-report-modal { padding: 14px; }
    .fire-report-modal__dialog { width: min(620px, 100%); max-height: calc(100dvh - 28px); padding: 14px; border-radius: 20px; }
    .fire-report-modal__dialog--details { width: min(440px, 100%); }
    .fire-report-modal__dialog--confirm { width: min(360px, 100%); }
    .fire-report-modal__header { align-items: center; margin-bottom: 10px; }
    .fire-report-modal__header .panel-eyebrow { font-size: .6rem; }
    .fire-report-modal__header .panel-title { font-size: .95rem; }
    .fire-report-modal__close { width: 32px; height: 32px; }
    .fire-report-form { gap: 9px; }
    .fire-report-form__section { padding: 10px; border-radius: 14px; }
    .fire-report-form__section-head { margin-bottom: 8px; }
    .fire-report-form__eyebrow { min-height: 26px; padding: 0 9px; font-size: .6rem; }
    .fire-report-form__hint { margin-top: 3px; font-size: .68rem; line-height: 1.35; }
    .fire-report-form__grid { gap: 8px; }
    .fire-report-form__field textarea { min-height: 70px; }
    .fire-report-location-panel { padding: 9px; border-radius: 13px; }
    .fire-report-location-panel__header { margin-bottom: 7px; }
    .fire-report-location-btn, .fire-report-primary-btn, .fire-report-secondary-btn, .fire-report-danger-btn { min-height: 34px; padding: 0 12px; border-radius: 10px; font-size: .73rem; font-weight: 700; }
    .fire-report-danger-btn { border: none; background: #dc2626; color: #fff; }
    .fire-report-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px; margin-bottom: 12px; }
    .fire-report-detail-grid > div { padding: 9px 10px; border: 1px solid #f1f5f9; border-radius: 11px; background: #f8fafc; }
    .fire-report-detail-grid > div.wide { grid-column: 1 / -1; }
    .fire-report-detail-grid span, .fire-report-detail-grid strong { display: block; }
    .fire-report-detail-grid span { margin-bottom: 2px; color: #94a3b8; font-size: .6rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .fire-report-detail-grid strong { color: #334155; font-size: .76rem; line-height: 1.4; overflow-wrap: anywhere; }
    .fire-report-delete-copy { display: grid; grid-template-columns: 38px 1fr; align-items: center; gap: 10px; margin: 4px 0 14px; color: #475569; font-size: .78rem; }
    .fire-report-delete-copy i { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 11px; background: #fef2f2; color: #dc2626; }

    @media (max-width: 900px) { .fire-report-filterbar__grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 640px) {
        .fire-report-filterbar__grid { grid-template-columns: 1fr; }
        .fire-report-modal { align-items: center; padding: 8px; }
        .fire-report-modal__dialog { width: 100%; max-height: calc(100dvh - 16px); margin: 0; padding: 10px; border-radius: 15px; }
        .fire-report-form__grid { grid-template-columns: 1fr; }
        .fire-report-modal__actions { flex-direction: row; }
        .fire-report-modal__actions .fire-report-primary-btn, .fire-report-modal__actions .fire-report-secondary-btn, .fire-report-modal__actions .fire-report-danger-btn { width: auto; flex: 1 1 0; }
        .fire-report-detail-grid { grid-template-columns: 1fr; }
        .fire-report-detail-grid > div.wide { grid-column: auto; }
        .fire-report-pagination { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const createModal = document.getElementById('createFireReportModal');
        const editModal = document.getElementById('editFireReportModal');
        const editForm = document.getElementById('editFireReportForm');
        const viewModal = document.getElementById('viewFireReportModal');
        const viewContent = document.getElementById('viewFireReportContent');
        const deleteModal = document.getElementById('deleteFireReportModal');
        const deleteForm = document.getElementById('deleteFireReportForm');
        const filterForm = document.getElementById('fireReportFilters');
        const tableWrap = document.getElementById('fireReportsTableWrap');
        const tableBody = tableWrap?.querySelector('tbody');
        const emptyState = document.getElementById('fireReportsEmpty');
        const pagination = document.getElementById('fireReportsPagination');
        const paginationButtons = document.getElementById('fireReportsPaginationButtons');
        const summary = document.getElementById('fireReportsSummary');
        const visibleCount = document.getElementById('fireReportVisibleCount');
        const records = new Map();
        let filterTimer = null;

        try {
            JSON.parse(document.getElementById('fireReportsInitialData')?.textContent || '[]').forEach((record) => records.set(Number(record.id), record));
        } catch (error) {
            console.error('Unable to read initial fire reports:', error);
        }

        function openModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        }

        function closeModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-open-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(document.getElementById(button.dataset.openModal));
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.closest('.fire-report-modal'));
            });
        });

        function escapeHtml(value) {
            return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        }

        function renderRows(items) {
            records.clear();
            items.forEach((record) => records.set(Number(record.id), record));
            if (visibleCount) visibleCount.textContent = String(items.length);
            if (emptyState) emptyState.hidden = items.length > 0;
            if (tableWrap) tableWrap.hidden = items.length === 0;
            if (!tableBody) return;

            tableBody.innerHTML = items.map((record) => `
                <tr>
                    <td>${escapeHtml(record.date)}</td>
                    <td><strong>${escapeHtml(record.title)}</strong><small>${escapeHtml(record.cause)}</small></td>
                    <td>${escapeHtml(record.classification)}</td>
                    <td>${escapeHtml(record.location)}</td>
                    <td><span class="fire-report-status">${escapeHtml(record.status)}</span></td>
                    <td><div class="fire-report-actions">
                        <button type="button" data-view-fire-report="${Number(record.id)}" aria-label="View report"><i class="fa-solid fa-eye"></i></button>
                        <button type="button" data-open-edit-fire-report="${Number(record.id)}" aria-label="Edit report"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="delete" data-delete-fire-report="${Number(record.id)}" aria-label="Delete report"><i class="fa-solid fa-trash"></i></button>
                    </div></td>
                </tr>
            `).join('');
        }

        function renderPagination(page) {
            const current = Number(page.current_page || 1);
            const last = Math.max(1, Number(page.last_page || 1));
            if (summary) summary.textContent = Number(page.total || 0) > 0 ? `Showing ${page.from}–${page.to} of ${page.total}` : 'No records';
            if (!paginationButtons) return;
            paginationButtons.innerHTML = `
                <button type="button" class="fire-report-page-btn" data-report-page="${Math.max(1, current - 1)}" ${current <= 1 ? 'disabled' : ''} aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="fire-report-page-btn" disabled>${current} / ${last}</button>
                <button type="button" class="fire-report-page-btn" data-report-page="${Math.min(last, current + 1)}" ${current >= last ? 'disabled' : ''} aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
            `;
        }

        function fillEditForm(record) {
            if (!editModal || !editForm || !record) return;
            editForm.action = "{{ url('/barangay/reports/fire') }}/" + record.id;
            const fields = {
                title: record.title,
                incident_date: record.incident_date,
                incident_count: record.incident_count,
                fire_classification: record.classification_key,
                fire_cause: record.cause === 'Pending assessment' ? '' : record.cause,
                location_description: record.location === 'No location description' ? '' : record.location,
                lat: record.lat,
                lng: record.lng,
                notes: record.notes,
            };
            Object.entries(fields).forEach(([key, value]) => {
                const input = document.getElementById('edit_' + key);
                if (input) input.value = value ?? '';
            });
            openModal(editModal);
        }

        function showRecord(record) {
            if (!record || !viewModal || !viewContent) return;
            viewContent.innerHTML = `
                <div class="wide"><span>Title</span><strong>${escapeHtml(record.title)}</strong></div>
                <div><span>Date</span><strong>${escapeHtml(record.date)}</strong></div><div><span>Status</span><strong>${escapeHtml(record.status)}</strong></div>
                <div><span>Classification</span><strong>${escapeHtml(record.classification)}</strong></div><div><span>Count</span><strong>${Number(record.incident_count || 1)}</strong></div>
                <div class="wide"><span>Cause</span><strong>${escapeHtml(record.cause)}</strong></div><div class="wide"><span>Location</span><strong>${escapeHtml(record.location)}</strong></div>
                <div><span>Latitude</span><strong>${escapeHtml(record.lat ?? 'Not recorded')}</strong></div><div><span>Longitude</span><strong>${escapeHtml(record.lng ?? 'Not recorded')}</strong></div>
                <div class="wide"><span>Notes</span><strong>${escapeHtml(record.notes || 'No notes.')}</strong></div>
            `;
            openModal(viewModal);
        }

        async function loadRecords(page = 1) {
            if (!filterForm) return;
            const endpoint = filterForm.dataset.recordsUrl;
            const params = new URLSearchParams(new FormData(filterForm));
            params.set('reports_page', String(page));
            params.set('per_page', '8');
            filterForm.classList.add('is-loading');

            try {
                const response = await fetch(`${endpoint}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`Report request failed with status ${response.status}`);
                const payload = await response.json();
                renderRows(payload.records?.data || []);
                renderPagination(payload.records || {});
            } catch (error) {
                console.error('Unable to filter fire reports:', error);
            } finally {
                filterForm.classList.remove('is-loading');
            }
        }

        document.addEventListener('click', (event) => {
            const viewButton = event.target.closest('[data-view-fire-report]');
            if (viewButton) return showRecord(records.get(Number(viewButton.dataset.viewFireReport)));
            const editButton = event.target.closest('[data-open-edit-fire-report]');
            if (editButton) return fillEditForm(records.get(Number(editButton.dataset.openEditFireReport)));
            const deleteButton = event.target.closest('[data-delete-fire-report]');
            if (deleteButton && deleteForm && deleteModal) {
                deleteForm.action = "{{ url('/barangay/reports/fire') }}/" + deleteButton.dataset.deleteFireReport;
                openModal(deleteModal);
                return;
            }
            const pageButton = event.target.closest('[data-report-page]');
            if (pageButton && !pageButton.disabled) loadRecords(Number(pageButton.dataset.reportPage || 1));
        });

        filterForm?.addEventListener('submit', (event) => event.preventDefault());
        filterForm?.querySelectorAll('select, input[type="date"]').forEach((field) => field.addEventListener('change', () => loadRecords(1)));
        document.getElementById('fire_record_search')?.addEventListener('input', () => {
            window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(() => loadRecords(1), 350);
        });
        document.getElementById('resetFireReportFilters')?.addEventListener('click', () => {
            filterForm.reset();
            loadRecords(1);
        });

        renderPagination({
            current_page: Number(pagination?.dataset.currentPage || 1),
            last_page: Number(pagination?.dataset.lastPage || 1),
            total: Number(pagination?.dataset.total || 0),
            from: pagination?.dataset.from || null,
            to: pagination?.dataset.to || null,
        });

        function attachCurrentLocation(buttonId, latId, lngId) {
            const button = document.getElementById(buttonId);
            const latInput = document.getElementById(latId);
            const lngInput = document.getElementById(lngId);

            if (!button || !latInput || !lngInput) {
                return;
            }

            button.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    return;
                }

                button.disabled = true;
                const previousLabel = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Getting location...';

                navigator.geolocation.getCurrentPosition(function (position) {
                    latInput.value = Number(position.coords.latitude).toFixed(7);
                    lngInput.value = Number(position.coords.longitude).toFixed(7);
                    button.disabled = false;
                    button.innerHTML = previousLabel;
                }, function () {
                    button.disabled = false;
                    button.innerHTML = previousLabel;
                });
            });
        }

        attachCurrentLocation('create_useCurrentLocationBtn', 'create_lat', 'create_lng');
        attachCurrentLocation('edit_useCurrentLocationBtn', 'edit_lat', 'edit_lng');

        @if ($errors->any())
            openModal(createModal);
        @endif
    });
</script>
@endpush
