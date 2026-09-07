@extends('barangay.layouts.app')

@section('active_nav', 'risk-map')
@section('page_title', 'Map')
@section('page_subtitle', 'Incidents, risks, hydrants, and response points')
@section('hero_title', 'Barangay Risk Map')
@section('hero_copy', 'View local fire-safety markers and submit a risk area.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-layer-group"></i> Operational layers</span>
    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> Barangay vicinity</span>
@endsection

@php
    $riskAreaMapPayload = $riskAreas->map(function ($riskArea) {
        return [
            'id' => $riskArea->risk_area_id,
            'marker_id' => $riskArea->marker_id,
            'entry_type' => $riskArea->entry_type,
            'title' => $riskArea->marker?->title,
            'description' => $riskArea->marker?->description,
            'lat' => $riskArea->marker?->lat,
            'lng' => $riskArea->marker?->lng,
            'status' => $riskArea->status,
            'risk_type' => $riskArea->risk_type,
            'hazard_type' => $riskArea->hazard_type,
            'assessment_level' => $riskArea->assessment_level,
            'risk_score' => $riskArea->risk_score,
            'street_or_sitio' => $riskArea->street_or_sitio,
            'station_name' => $riskArea->station?->station_name,
        ];
    })->values();

    $fallbackMarkerMapPayload = collect($markers)->map(function ($marker) {
        return [
            'marker_id' => $marker->marker_id,
            'title' => $marker->title,
            'description' => $marker->description,
            'lat' => $marker->lat,
            'lng' => $marker->lng,
            'category' => $marker->category,
            'status' => $marker->status,
        ];
    })->values();

    $hallMapPayload = $hall && $hall->marker ? [
        'title' => $hall->hall_name ?: 'Barangay Hall',
        'description' => $hall->address?->street ?: ($barangay?->barangay_name ?: 'Barangay hall'),
        'lat' => $hall->marker->lat,
        'lng' => $hall->marker->lng,
        'category' => 'barangay',
    ] : null;

    $formatRiskLabel = static function ($value) {
        if (blank($value)) {
            return 'Not set';
        }

        return strtoupper(str_replace('_', ' ', (string) $value));
    };
@endphp

@push('styles')
<style>
    .barangay-risk-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .barangay-risk-grid-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(300px, 0.9fr);
        gap: 1.1rem;
        align-items: start;
    }

    .barangay-risk-section-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .barangay-risk-section-heading h3 {
        margin: 0;
    }

    .barangay-risk-section-heading p {
        margin: 0.35rem 0 0;
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.6;
        max-width: 58ch;
    }

    .barangay-risk-metric-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .barangay-summary-card {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: rgba(248, 250, 252, 0.96);
        padding: 0.9rem 1rem;
        min-height: 92px;
    }

    .barangay-summary-card .tiny-label {
        margin-bottom: 0.25rem;
    }

    .barangay-risk-map-shell {
        margin-top: 1rem;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: #fff;
        padding: 0.8rem;
        box-shadow: inset 0 0 0 1px rgba(248, 250, 252, 0.8);
    }

    .barangay-risk-side-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .barangay-risk-form {
        display: grid;
        gap: 1rem;
    }

    .barangay-risk-step {
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 250, 252, 0.92) 100%);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .barangay-risk-step[open] {
        border-color: rgba(251, 191, 36, 0.22);
    }

    .barangay-risk-step > summary {
        list-style: none;
        padding: 1rem;
    }

    .barangay-risk-step > summary::-webkit-details-marker {
        display: none;
    }

    .barangay-risk-step__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
    }

    .barangay-risk-step__body {
        padding: 0 1rem 1rem;
    }

    .barangay-risk-step__status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 0 0.8rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .barangay-risk-step__status--required {
        background: #fff7ed;
        color: #c2410c;
    }

    .barangay-risk-step__status--review {
        background: #f8fafc;
        color: #475569;
    }

    .barangay-risk-step__status--optional {
        background: #ecfdf5;
        color: #047857;
    }

    .barangay-risk-step__status--map {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .barangay-risk-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.95rem;
    }

    .barangay-risk-field--full {
        grid-column: 1 / -1;
    }

    .barangay-risk-field label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
        color: #334155;
        font-size: 0.88rem;
        font-weight: 700;
    }

    .barangay-risk-field__hint {
        margin-top: 0.45rem;
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.5;
    }

    .barangay-risk-field__required {
        color: #c2410c;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .barangay-risk-input,
    .barangay-risk-select,
    .barangay-risk-textarea,
    .barangay-risk-file {
        width: 100%;
        min-height: 46px;
        border-radius: 16px;
        border: 1px solid rgba(203, 213, 225, 0.95);
        background: #fff;
        padding: 0.85rem 0.95rem;
        color: #0f172a;
        font-size: 0.92rem;
    }

    .barangay-risk-textarea {
        min-height: 112px;
        resize: vertical;
    }

    .barangay-risk-chip-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .barangay-risk-chip {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-height: 48px;
        padding: 0.8rem 0.95rem;
        border-radius: 18px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: #fff;
        color: #334155;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .barangay-risk-chip input {
        width: 16px;
        height: 16px;
        accent-color: #ea580c;
        flex-shrink: 0;
    }

    .barangay-risk-map-panel {
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: rgba(255, 255, 255, 0.88);
        padding: 1rem;
    }

    .barangay-risk-map-panel__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .barangay-risk-location-btn,
    .barangay-risk-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 46px;
        border-radius: 16px;
        font-size: 0.9rem;
        font-weight: 800;
        transition: all 0.2s ease;
    }

    .barangay-risk-location-btn {
        padding: 0 1rem;
        border: 1px solid rgba(251, 191, 36, 0.22);
        background: #f59e0b;
        color: #fff;
        box-shadow: 0 12px 24px rgba(245, 158, 11, 0.18);
    }

    .barangay-risk-submit {
        padding: 0 1.2rem;
        border: 0;
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(234, 88, 12, 0.18);
    }

    .barangay-risk-review {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.84) 0%, rgba(255, 255, 255, 0.96) 100%);
        padding: 1rem;
    }

    .barangay-risk-review__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .barangay-risk-review__item {
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(251, 191, 36, 0.12);
        padding: 0.85rem;
    }

    .barangay-risk-review__item span {
        display: block;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .barangay-risk-review__item strong {
        display: block;
        margin-top: 0.45rem;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .barangay-risk-launch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-radius: 24px;
    }

    .barangay-risk-launch__button,
    .barangay-risk-wizard__button {
        border: 0;
        border-radius: 14px;
        min-height: 42px;
        padding: 0 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .barangay-risk-launch__button,
    .barangay-risk-wizard__button--next {
        color: #fff;
        background: linear-gradient(135deg, #ea580c, #dc2626);
        box-shadow: 0 12px 24px rgba(234, 88, 12, 0.18);
    }

    .barangay-risk-modal {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .barangay-risk-modal.hidden { display: none; }
    body.risk-modal-open { overflow: hidden; }
    .barangay-risk-feed-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .barangay-risk-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(5px);
    }

    .barangay-risk-modal__dialog {
        position: relative;
        width: min(820px, 100%);
        max-height: calc(100dvh - 2rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 24px;
        background: #fffdf9;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
    }

    .barangay-risk-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .barangay-risk-modal__close {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        color: #64748b;
    }

    .barangay-risk-modal__body {
        min-height: 0;
        overflow-y: auto;
        padding: 1rem 1.15rem 1.15rem;
    }

    .barangay-risk-wizard__progress {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.45rem;
        margin-bottom: 0.9rem;
    }

    .barangay-risk-wizard__progress-item {
        min-width: 0;
        border: 0;
        border-radius: 12px;
        background: #f1f5f9;
        color: #64748b;
        padding: 0.6rem 0.45rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-align: center;
    }

    .barangay-risk-wizard__progress-item.is-active {
        background: #fff7ed;
        color: #c2410c;
        box-shadow: inset 0 0 0 1px #fed7aa;
    }

    .barangay-risk-form .barangay-risk-step { display: none; }
    .barangay-risk-form .barangay-risk-step.is-active { display: block; }
    .barangay-risk-step > summary { pointer-events: none; }

    .barangay-risk-wizard__actions {
        position: sticky;
        bottom: -1.15rem;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin: 0.2rem -1.15rem -1.15rem;
        padding: 0.85rem 1.15rem;
        border-top: 1px solid #f1f5f9;
        background: rgba(255, 253, 249, 0.96);
        backdrop-filter: blur(10px);
    }

    .barangay-risk-wizard__button--back {
        border: 1px solid #e2e8f0;
        color: #475569;
        background: #fff;
    }

    .barangay-risk-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 0.9rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .barangay-risk-pagination__actions { display: flex; gap: 0.4rem; }
    .barangay-risk-pagination a,
    .barangay-risk-pagination span.is-disabled {
        min-width: 34px;
        height: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        background: #fff;
        text-decoration: none;
    }

    .barangay-risk-pagination span.is-disabled { opacity: 0.42; }

    .barangay-risk-feed-card {
        padding: 1.1rem 1.15rem;
    }

    .barangay-risk-feed-card__header {
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 0.9rem;
    }

    .barangay-risk-feed-card__header h3 {
        margin: 0;
    }

    .barangay-risk-feed-card__header p {
        margin: 0.3rem 0 0;
        color: #64748b;
        font-size: 0.85rem;
        line-height: 1.55;
    }

    .barangay-risk-feed-count {
        min-width: 62px;
        padding: 0.55rem 0.75rem;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #f8fafc;
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 900;
        text-align: center;
    }

    .barangay-risk-feed-count small {
        display: block;
        margin-top: 0.2rem;
        color: #64748b;
        font-size: 0.63rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .barangay-risk-record-head {
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 0.8rem;
    }

    .barangay-risk-record-copy {
        min-width: 0;
    }

    .barangay-risk-record-action {
        flex-shrink: 0;
        min-height: 34px;
        padding: 0 0.85rem;
        border: 1px solid rgba(217, 119, 6, 0.18);
        border-radius: 11px;
        background: #fff7ed;
        color: #b45309;
        font-size: 0.74rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .barangay-risk-detail-modal[hidden] { display: none; }
    .barangay-risk-detail-modal {
        position: fixed;
        inset: 0;
        z-index: 10080;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(6px);
    }

    .barangay-risk-detail-modal__card {
        display: flex;
        flex-direction: column;
        width: min(500px, 100%);
        max-height: min(78dvh, 620px);
        overflow: hidden;
        border: 1px solid rgba(245, 158, 11, 0.18);
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
    }

    .barangay-risk-detail-modal__header,
    .barangay-risk-detail-modal__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex: 0 0 auto;
        padding: 12px 14px;
    }

    .barangay-risk-detail-modal__header {
        border-bottom: 1px solid #f1f5f9;
    }

    .barangay-risk-detail-modal__header h3 {
        margin: 0;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 900;
    }

    .barangay-risk-detail-modal__header button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #fff;
        color: #64748b;
    }

    .barangay-risk-detail-modal__body {
        min-height: 0;
        overflow-y: auto;
        padding: 12px 14px;
        display: grid;
        gap: 0.8rem;
    }

    .barangay-risk-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .barangay-risk-detail-item {
        border-radius: 14px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #f8fafc;
        padding: 0.75rem 0.85rem;
    }

    .barangay-risk-detail-item span {
        display: block;
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .barangay-risk-detail-item strong {
        display: block;
        margin-top: 0.35rem;
        color: #0f172a;
        font-size: 0.86rem;
        line-height: 1.45;
        word-break: break-word;
    }

    .barangay-risk-detail-modal__footer {
        justify-content: flex-end;
        border-top: 1px solid #f1f5f9;
    }

    .barangay-risk-detail-modal__footer button {
        min-height: 32px;
        padding: 0 13px;
        border: 1px solid rgba(217, 119, 6, 0.2);
        border-radius: 9px;
        background: #fff7ed;
        color: #b45309;
        font-size: 0.72rem;
        font-weight: 800;
    }

    #barangayOperationsMap .leaflet-control-layers {
        border: 1px solid rgba(203, 213, 225, 0.95);
        border-radius: 14px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.13);
        overflow: hidden;
    }

    #barangayOperationsMap .leaflet-control-layers-expanded {
        max-width: min(290px, calc(100vw - 4rem));
        max-height: min(62vh, 430px);
        padding: 0.8rem;
        overflow: auto;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
    }

    @media (max-width: 1100px) {
        .barangay-risk-grid-shell,
        .barangay-risk-feed-grid {
            grid-template-columns: 1fr;
        }

        .barangay-risk-metric-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .barangay-risk-launch { align-items: flex-start; flex-direction: column; }
        .barangay-risk-launch__button { width: 100%; }
        .barangay-risk-modal { padding: 0.45rem; }
        .barangay-risk-modal__dialog { max-height: calc(100dvh - 0.9rem); border-radius: 18px; }
        .barangay-risk-modal__header,
        .barangay-risk-modal__body { padding-left: 0.8rem; padding-right: 0.8rem; }
        .barangay-risk-wizard__progress-item { font-size: 0; }
        .barangay-risk-wizard__progress-item::before { content: attr(data-step); font-size: 0.7rem; }
        .barangay-risk-wizard__actions { margin-left: -0.8rem; margin-right: -0.8rem; padding-left: 0.8rem; padding-right: 0.8rem; }
        .barangay-risk-step {
            border-radius: 22px;
            margin-inline: -2px;
        }

        .barangay-risk-step > summary,
        .barangay-risk-step__body,
        .barangay-risk-map-panel,
        .barangay-risk-review {
            padding-left: 0.9rem;
            padding-right: 0.9rem;
        }

        .barangay-risk-step__head {
            flex-direction: column;
            align-items: flex-start;
        }

        .barangay-risk-grid,
        .barangay-risk-chip-grid,
        .barangay-risk-review__list,
        .barangay-risk-metric-grid {
            grid-template-columns: 1fr;
        }

        .barangay-risk-map-panel__head {
            align-items: stretch;
        }

        .barangay-risk-section-heading,
        .barangay-risk-feed-card__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .barangay-risk-location-btn,
        .barangay-risk-submit {
            width: 100%;
        }

        .barangay-risk-record-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .barangay-risk-detail-modal {
            padding: 9px;
        }

        .barangay-risk-detail-modal__card {
            max-height: calc(100dvh - 18px);
            border-radius: 16px;
        }

        .barangay-risk-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <div class="barangay-risk-stack">
        @if (session('success'))
            <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold mb-1">Unable to submit the risk marker.</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="barangay-risk-grid-shell">
            <article class="panel-card">
                <div class="barangay-risk-section-heading">
                    <div>
                        <div class="panel-eyebrow">Map</div>
                        <h3 class="panel-title">Local safety markers</h3>
                        <p>Track incidents, hydrants, stations, and reported risk areas in your barangay.</p>
                    </div>
                </div>

                <div class="barangay-risk-metric-grid">
                    @include('barangay.partials.summary-metric-card', ['label' => 'Active Fires', 'value' => '<span id="barangayMapLiveCount">0</span>'])
                    @include('barangay.partials.summary-metric-card', ['label' => 'Incidents', 'value' => '<span id="barangayMapIncidentCount">0</span>'])
                    @include('barangay.partials.summary-metric-card', ['label' => 'Risk Markers', 'value' => '<span id="barangayMapRiskCount">' . e($riskAreas->count()) . '</span>'])
                    @include('barangay.partials.summary-metric-card', ['label' => 'Last Updated', 'value' => '<span id="barangayMapUpdatedAt">Loading...</span>', 'valueClass' => 'text-sm font-bold text-slate-700 mt-2'])
                </div>

                <div class="barangay-risk-map-shell">
                    <div id="barangayOperationsMap" style="height: 560px; border-radius: 20px; overflow: hidden;"></div>
                </div>
            </article>

            <aside class="barangay-risk-side-stack">
                <section class="panel-card barangay-risk-launch">
                    <div>
                        <div class="panel-eyebrow">Submit</div>
                        <h3 class="panel-title">Add risk marker</h3>
                        <p class="panel-copy !mt-1">Send a new risk area for review without leaving the map.</p>
                    </div>
                    <button type="button" class="barangay-risk-launch__button" data-open-risk-modal>
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        Add Risk Marker
                    </button>
                </section>

                <article class="panel-card barangay-risk-feed-card">
                    <div class="barangay-risk-feed-card__header">
                        <div>
                            <div class="panel-eyebrow">Totals</div>
                            <h3 class="panel-title">Map records</h3>
                            <p>Quick count of markers and submitted barangay risk points.</p>
                        </div>
                        <div class="barangay-risk-feed-count">
                            {{ $riskAreaFeed->total() + $markerInventory->total() }}
                            <small>Visible</small>
                        </div>
                    </div>

                    <div class="list-stack">
                        <div class="list-item">
                            <h4>Risk submissions</h4>
                            <p>{{ $riskAreaFeed->total() }} recorded barangay risk markers linked to this area.</p>
                        </div>
                        <div class="list-item">
                            <h4>Mapped points</h4>
                            <p>{{ $markerInventory->total() }} related marker records currently available on the map feed.</p>
                        </div>
                    </div>
                </article>
            </aside>
        </section>

        <section class="barangay-risk-feed-grid">
        @include('barangay.partials.risk-map-submission-modal')

        <article class="panel-card barangay-risk-feed-card">
            <div class="barangay-risk-feed-card__header">
                <div>
                    <div class="panel-eyebrow">Risk Markers</div>
                    <h3 class="panel-title">Recent submissions</h3>
                    <p>Latest reported risk areas from this barangay.</p>
                </div>
                <div class="barangay-risk-feed-count">
                    {{ $riskAreaFeed->total() }}
                    <small>Records</small>
                </div>
            </div>
            @if($riskAreaFeed->isEmpty())
                <div class="empty-state">No barangay risk markers are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($riskAreaFeed as $riskArea)
                        @php
                            $riskAreaDetail = [
                                'title' => $riskArea->marker?->title ?? 'Untitled risk marker',
                                'kind' => 'Risk Marker',
                                'description' => $riskArea->marker?->description ?? 'No marker description provided.',
                                'fields' => [
                                    ['label' => 'Risk Type', 'value' => $formatRiskLabel($riskArea->risk_type ?: $riskArea->risk_factor)],
                                    ['label' => 'Status', 'value' => $formatRiskLabel($riskArea->status)],
                                    ['label' => 'Assessment', 'value' => $formatRiskLabel($riskArea->assessment_level ?: $riskArea->hazard_type)],
                                    ['label' => 'Station', 'value' => $riskArea->station?->station_name ?: 'No linked station'],
                                    ['label' => 'Street / Sitio', 'value' => $riskArea->street_or_sitio ?: 'General area'],
                                    ['label' => 'Remarks', 'value' => $riskArea->remarks ?: 'No remarks'],
                                    ['label' => 'Photos', 'value' => $riskArea->photos->count() ? $riskArea->photos->count() . ' uploaded' : 'No photos'],
                                ],
                            ];
                        @endphp
                        <div class="list-item">
                            <div class="barangay-risk-record-head">
                                <div class="barangay-risk-record-copy">
                                    <h4>{{ $riskArea->marker?->title ?? 'Untitled risk marker' }}</h4>
                                    <p>{{ $riskArea->marker?->description ?? 'No marker description provided.' }}</p>
                                </div>
                                <button type="button" class="barangay-risk-record-action" data-risk-detail='@json($riskAreaDetail)'>
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    View
                                </button>
                            </div>
                            <div class="tag-row">
                                <span class="tag medium">{{ strtoupper(str_replace('_', ' ', $riskArea->risk_type ?: $riskArea->risk_factor)) }}</span>
                                <span class="tag {{ $riskArea->status }}">{{ strtoupper($riskArea->status) }}</span>
                                <span class="tag low">{{ strtoupper($riskArea->assessment_level ?: $riskArea->hazard_type) }}</span>
                                @if($riskArea->station)
                                    <span class="tag in_progress">{{ $riskArea->station->station_name }}</span>
                                @endif
                                @if($riskArea->photos->isNotEmpty())
                                    <span class="tag complied">{{ $riskArea->photos->count() }} photo{{ $riskArea->photos->count() > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            @if($riskArea->remarks || $riskArea->street_or_sitio)
                                <p class="mt-2 text-sm text-slate-500">
                                    {{ $riskArea->street_or_sitio ?: 'General area' }}
                                    @if($riskArea->remarks)
                                        · {{ $riskArea->remarks }}
                                    @endif
                                </p>
                            @endif
                            @if($riskArea->photos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    @foreach($riskArea->photos->take(3) as $photo)
                                        <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200 bg-white aspect-square">
                                            <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}" class="w-full h-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <nav class="barangay-risk-pagination" aria-label="Risk marker pages">
                    <span>Page {{ $riskAreaFeed->currentPage() }} of {{ max(1, $riskAreaFeed->lastPage()) }} · {{ $riskAreaFeed->total() }} records</span>
                    <div class="barangay-risk-pagination__actions">
                        @if($riskAreaFeed->onFirstPage())
                            <span class="is-disabled"><i class="fa-solid fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $riskAreaFeed->previousPageUrl() }}" aria-label="Previous risk marker page"><i class="fa-solid fa-chevron-left"></i></a>
                        @endif
                        @if($riskAreaFeed->hasMorePages())
                            <a href="{{ $riskAreaFeed->nextPageUrl() }}" aria-label="Next risk marker page"><i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="is-disabled"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </div>
                </nav>
            @endif
        </article>

        <article class="panel-card barangay-risk-feed-card">
            <div class="barangay-risk-feed-card__header">
                <div>
                    <div class="panel-eyebrow">Map Records</div>
                    <h3 class="panel-title">Other mapped points</h3>
                    <p>Supporting markers and locations visible in the barangay layer.</p>
                </div>
                <div class="barangay-risk-feed-count">
                    {{ $markerInventory->total() }}
                    <small>Markers</small>
                </div>
            </div>
            @if($markerInventory->isEmpty())
                <div class="empty-state">No marker records are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($markerInventory as $marker)
                        @php
                            $markerDetail = [
                                'title' => $marker->title ?? 'Untitled marker',
                                'kind' => 'Mapped Point',
                                'description' => $marker->description ?? 'No marker description provided.',
                                'fields' => [
                                    ['label' => 'Category', 'value' => $formatRiskLabel($marker->category ?? 'unknown')],
                                    ['label' => 'Status', 'value' => $formatRiskLabel($marker->status ?? 'unknown')],
                                    ['label' => 'Latitude', 'value' => filled($marker->lat) ? (string) $marker->lat : 'Not available'],
                                    ['label' => 'Longitude', 'value' => filled($marker->lng) ? (string) $marker->lng : 'Not available'],
                                ],
                            ];
                        @endphp
                        <div class="list-item">
                            <div class="barangay-risk-record-head">
                                <div class="barangay-risk-record-copy">
                                    <h4>{{ $marker->title ?? 'Untitled marker' }}</h4>
                                    <p>{{ $marker->description ?? 'No marker description provided.' }}</p>
                                </div>
                                <button type="button" class="barangay-risk-record-action" data-risk-detail='@json($markerDetail)'>
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    View
                                </button>
                            </div>
                            <div class="tag-row">
                                <span class="tag low">{{ strtoupper($marker->category ?? 'UNKNOWN') }}</span>
                                <span class="tag {{ $marker->status === 'active' ? 'complied' : 'deferred' }}">{{ strtoupper($marker->status ?? 'UNKNOWN') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <nav class="barangay-risk-pagination" aria-label="Mapped point pages">
                    <span>Page {{ $markerInventory->currentPage() }} of {{ max(1, $markerInventory->lastPage()) }} · {{ $markerInventory->total() }} records</span>
                    <div class="barangay-risk-pagination__actions">
                        @if($markerInventory->onFirstPage())
                            <span class="is-disabled"><i class="fa-solid fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $markerInventory->previousPageUrl() }}" aria-label="Previous mapped point page"><i class="fa-solid fa-chevron-left"></i></a>
                        @endif
                        @if($markerInventory->hasMorePages())
                            <a href="{{ $markerInventory->nextPageUrl() }}" aria-label="Next mapped point page"><i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="is-disabled"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </div>
                </nav>
            @endif
        </article>

        <article class="panel-card barangay-risk-feed-card">
            <div class="barangay-risk-feed-card__header">
                <div>
                    <div class="panel-eyebrow">Totals</div>
                    <h3 class="panel-title">Risk status</h3>
                    <p>Current review breakdown for barangay risk submissions.</p>
                </div>
            </div>
            <div class="list-stack">
                <div class="stat-line">
                    <strong>Total risk markers</strong>
                    <span class="tag high">{{ $portalStats['risk_markers'] ?? $riskAreas->count() }}</span>
                </div>
                <div class="stat-line">
                    <strong>Pending review</strong>
                    <span class="tag pending">{{ (int) ($riskAreaStatusCounts['pending'] ?? 0) }}</span>
                </div>
                <div class="stat-line">
                    <strong>Verified</strong>
                    <span class="tag complied">{{ (int) ($riskAreaStatusCounts['verified'] ?? 0) }}</span>
                </div>
                <div class="stat-line">
                    <strong>Resolved</strong>
                    <span class="tag low">{{ (int) ($riskAreaStatusCounts['resolved'] ?? 0) }}</span>
                </div>
            </div>
        </article>

        <div class="barangay-risk-detail-modal" id="barangayRiskDetailModal" role="dialog" aria-modal="true" aria-labelledby="barangayRiskDetailTitle" hidden>
            <div class="barangay-risk-detail-modal__card">
                <div class="barangay-risk-detail-modal__header">
                    <div>
                        <div class="panel-eyebrow" id="barangayRiskDetailKind">Record</div>
                        <h3 id="barangayRiskDetailTitle">Details</h3>
                    </div>
                    <button type="button" data-risk-detail-close aria-label="Close details"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="barangay-risk-detail-modal__body" id="barangayRiskDetailBody"></div>
                <div class="barangay-risk-detail-modal__footer">
                    <button type="button" data-risk-detail-close>Close</button>
                </div>
            </div>
        </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const riskModal = document.getElementById('barangayRiskSubmissionModal');
        const riskForm = riskModal?.querySelector('.barangay-risk-form');
        const riskSteps = Array.from(riskForm?.querySelectorAll('.barangay-risk-step') || []);
        const progressItems = Array.from(riskModal?.querySelectorAll('[data-risk-step-target]') || []);
        const backButton = riskModal?.querySelector('[data-risk-step-back]');
        const nextButton = riskModal?.querySelector('[data-risk-step-next]');
        const stepLabel = riskModal?.querySelector('[data-risk-step-label]');
        const riskTypeSelect = document.getElementById('risk_area_type');
        const conditionSelect = document.getElementById('risk_area_condition');
        const detailModal = document.getElementById('barangayRiskDetailModal');
        const detailModalKind = document.getElementById('barangayRiskDetailKind');
        const detailModalTitle = document.getElementById('barangayRiskDetailTitle');
        const detailModalBody = document.getElementById('barangayRiskDetailBody');
        let activeRiskStep = 0;
        let furthestRiskStep = 0;

        function escapeRiskDetailHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function syncRiskConditions() {
            if (!riskTypeSelect || !conditionSelect) return;

            const selectedType = riskTypeSelect.value;
            let selectedOptionVisible = false;

            Array.from(conditionSelect.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    option.disabled = false;
                    option.textContent = selectedType ? 'Select risk condition' : 'Select a risk type first';
                    return;
                }

                const isVisible = option.dataset.riskType === selectedType;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
                if (isVisible && option.selected) selectedOptionVisible = true;
            });

            if (!selectedOptionVisible) conditionSelect.value = '';
        }

        function validateRiskStep(index) {
            const step = riskSteps[index];
            if (!step) return true;

            const requiredControls = Array.from(step.querySelectorAll('[required]'));
            for (const control of requiredControls) {
                if (!control.checkValidity()) {
                    control.reportValidity();
                    control.focus({ preventScroll: false });
                    return false;
                }
            }
            return true;
        }

        function showRiskStep(index, options = {}) {
            const boundedIndex = Math.max(0, Math.min(riskSteps.length - 1, Number(index) || 0));
            if (options.validateForward && boundedIndex > activeRiskStep && !validateRiskStep(activeRiskStep)) return;

            activeRiskStep = boundedIndex;
            furthestRiskStep = Math.max(furthestRiskStep, activeRiskStep);
            riskSteps.forEach((step, stepIndex) => {
                const isActive = stepIndex === activeRiskStep;
                step.classList.toggle('is-active', isActive);
                step.open = isActive;
            });
            progressItems.forEach((item, stepIndex) => item.classList.toggle('is-active', stepIndex === activeRiskStep));
            if (backButton) backButton.disabled = activeRiskStep === 0;
            if (nextButton) nextButton.hidden = activeRiskStep === riskSteps.length - 1;
            if (stepLabel) stepLabel.textContent = `Step ${activeRiskStep + 1} of ${riskSteps.length}`;

            if (activeRiskStep === riskSteps.length - 1) {
                window.setTimeout(() => window.barangayRiskPickerMap?.invalidateSize(), 120);
            }
        }

        function openRiskModal() {
            if (!riskModal) return;
            riskModal.classList.remove('hidden');
            riskModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('risk-modal-open');
            showRiskStep(activeRiskStep);
            window.setTimeout(() => window.barangayRiskPickerMap?.invalidateSize(), 120);
        }

        function closeRiskModal() {
            if (!riskModal) return;
            riskModal.classList.add('hidden');
            riskModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('risk-modal-open');
        }

        document.querySelectorAll('[data-open-risk-modal]').forEach((button) => button.addEventListener('click', openRiskModal));
        riskModal?.querySelectorAll('[data-close-risk-modal]').forEach((button) => button.addEventListener('click', closeRiskModal));
        backButton?.addEventListener('click', () => showRiskStep(activeRiskStep - 1));
        nextButton?.addEventListener('click', () => showRiskStep(activeRiskStep + 1, { validateForward: true }));
        progressItems.forEach((item) => item.addEventListener('click', () => {
            const target = Number(item.dataset.riskStepTarget || 0);
            if (target <= furthestRiskStep) showRiskStep(target);
            else if (target === activeRiskStep + 1) showRiskStep(target, { validateForward: true });
        }));
        riskTypeSelect?.addEventListener('change', syncRiskConditions);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !riskModal?.classList.contains('hidden')) closeRiskModal();
        });
        syncRiskConditions();
        showRiskStep(0);

        function closeRiskDetailModal() {
            if (!detailModal || !detailModalBody) return;
            detailModal.hidden = true;
            detailModalBody.innerHTML = '';
        }

        function openRiskDetailModal(payload) {
            if (!detailModal || !detailModalBody || !detailModalTitle || !detailModalKind || !payload) return;

            detailModalKind.textContent = payload.kind || 'Record';
            detailModalTitle.textContent = payload.title || 'Details';

            const fieldMarkup = Array.isArray(payload.fields)
                ? payload.fields.map((field) => `
                    <div class="barangay-risk-detail-item">
                        <span>${escapeRiskDetailHtml(field.label || 'Field')}</span>
                        <strong>${escapeRiskDetailHtml(field.value || 'Not available')}</strong>
                    </div>
                `).join('')
                : '';

            detailModalBody.innerHTML = `
                <div class="list-item">
                    <h4>${escapeRiskDetailHtml(payload.title || 'Details')}</h4>
                    <p>${escapeRiskDetailHtml(payload.description || 'No description provided.')}</p>
                </div>
                <div class="barangay-risk-detail-grid">${fieldMarkup}</div>
            `;

            detailModal.hidden = false;
            detailModal.querySelector('[data-risk-detail-close]')?.focus();
        }

        document.querySelectorAll('[data-risk-detail]').forEach((button) => {
            button.addEventListener('click', () => {
                try {
                    openRiskDetailModal(JSON.parse(button.dataset.riskDetail || '{}'));
                } catch (error) {
                    console.error('Failed to read risk detail payload.', error);
                }
            });
        });

        detailModal?.querySelectorAll('[data-risk-detail-close]').forEach((button) => button.addEventListener('click', closeRiskDetailModal));
        detailModal?.addEventListener('click', (event) => {
            if (event.target === detailModal) {
                closeRiskDetailModal();
            }
        });

        if (@json($errors->any())) {
            openRiskModal();
        }

        if (typeof L === 'undefined') {
            return;
        }

        function formatBarangayRiskFileSize(bytes = 0) {
            const normalizedBytes = Number(bytes) || 0;

            if (normalizedBytes >= 1024 * 1024) {
                return `${(normalizedBytes / (1024 * 1024)).toFixed(2)} MB`;
            }

            if (normalizedBytes >= 1024) {
                return `${Math.round(normalizedBytes / 1024)} KB`;
            }

            return `${normalizedBytes} B`;
        }

        function setBarangayRiskPhotoUploadStatus(message, tone = 'neutral') {
            const statusNode = document.getElementById('barangayRiskPhotoUploadStatus');
            if (!statusNode) {
                return;
            }

            statusNode.textContent = message;
            statusNode.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-amber-200', 'bg-amber-50', 'text-amber-700', 'border-red-200', 'bg-red-50', 'text-red-700');

            if (tone === 'success') {
                statusNode.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                return;
            }

            if (tone === 'loading') {
                statusNode.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
                return;
            }

            if (tone === 'error') {
                statusNode.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                return;
            }

            statusNode.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
        }

        function setBarangayRiskSubmitAvailability(isEnabled) {
            const submitButton = document.getElementById('barangayRiskSubmitButton');
            if (!submitButton) {
                return;
            }

            submitButton.disabled = !isEnabled;
            submitButton.style.opacity = isEnabled ? '' : '0.6';
            submitButton.style.pointerEvents = isEnabled ? '' : 'none';
            submitButton.setAttribute('aria-disabled', isEnabled ? 'false' : 'true');
        }

        function evaluateBarangayRiskPhotoSelection() {
            const maxPhotoCount = 3;
            const maxPhotoTotalBytes = 5 * 1024 * 1024;
            const photoInput = document.getElementById('risk_area_photos');
            const files = Array.from(photoInput?.files || []);

            if (files.length === 0) {
                return {
                    isValid: true,
                    message: 'No photos selected yet. You can save without photos, or add up to 3 images.',
                    tone: 'neutral',
                };
            }

            if (files.length > maxPhotoCount) {
                return {
                    isValid: false,
                    message: 'Too many photos selected. Upload up to 3 images only.',
                    tone: 'error',
                };
            }

            const totalBytes = files.reduce((sum, file) => sum + (Number(file?.size) || 0), 0);

            if (totalBytes > maxPhotoTotalBytes) {
                return {
                    isValid: false,
                    message: `Selected photos total ${formatBarangayRiskFileSize(totalBytes)}. Keep the combined upload within 5.00 MB.`,
                    tone: 'error',
                };
            }

            return {
                isValid: true,
                message: `${files.length} photo(s) ready to upload - ${formatBarangayRiskFileSize(totalBytes)} total`,
                tone: 'success',
            };
        }

        function refreshBarangayRiskPhotoUploadState() {
            setBarangayRiskPhotoUploadStatus('Checking selected photos...', 'loading');

            window.setTimeout(() => {
                const result = evaluateBarangayRiskPhotoSelection();
                setBarangayRiskPhotoUploadStatus(result.message, result.tone);
                setBarangayRiskSubmitAvailability(result.isValid);
            }, 60);
        }

        document.getElementById('risk_area_photos')?.addEventListener('change', refreshBarangayRiskPhotoUploadState);
        refreshBarangayRiskPhotoUploadState();

        const riskAreaRecords = @json($riskAreaMapPayload);
        const fallbackMarkerRecords = @json($fallbackMarkerMapPayload);
        const hallRecord = @json($hallMapPayload);
        const barangayBoundaryGeojson = @json($barangayBoundaryGeojson);
        const defaultLat = hallRecord && hallRecord.lat !== null && hallRecord.lat !== undefined
            ? Number(hallRecord.lat)
            : 15.8222;
        const defaultLng = hallRecord && hallRecord.lng !== null && hallRecord.lng !== undefined
            ? Number(hallRecord.lng)
            : 120.3297;
        const operationsMapElement = document.getElementById('barangayOperationsMap');
        const liveCountElement = document.getElementById('barangayMapLiveCount');
        const incidentCountElement = document.getElementById('barangayMapIncidentCount');
        const riskCountElement = document.getElementById('barangayMapRiskCount');
        const updatedAtElement = document.getElementById('barangayMapUpdatedAt');

        function buildOperationalIcon(options) {
            const size = options.size || 32;
            const iconHtml = options.iconHtml
                ? `<span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">${options.iconHtml}</span>`
                : `<i class="fa-solid ${options.icon || 'fa-location-dot'}" style="font-size:${options.fontSize || 15}px;"></i>`;
            const imageHtml = options.image
                ? `<img src="${options.image}" alt="" draggable="false" style="width:${size}px;height:${size}px;object-fit:contain;filter:drop-shadow(0 6px 10px rgba(15,23,42,.24));" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">`
                : '';

            return L.divIcon({
                className: 'barangay-operational-marker pyro-map-marker-icon',
                html: `
                    ${imageHtml}
                    <div style="
                        width:${size}px;
                        height:${size}px;
                        border-radius:999px;
                        background:${options.color};
                        display:${options.image ? 'none' : 'flex'};
                        align-items:center;
                        justify-content:center;
                        color:#fff;
                        border:2px solid #fff;
                        box-shadow:0 4px 6px rgba(0,0,0,.28);
                    ">
                        ${iconHtml}
                    </div>
                `,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
                popupAnchor: [0, -(size / 2)],
            });
        }

        function getMarkerVisual(category, options = {}) {
            const normalized = String(category || '').trim().toLowerCase();
            const isLive = options.isLive === true;

            if (isLive) {
                return { color: '#dc2626', icon: 'fa-fire', image: '/fire.png', label: 'Active Fire', size: 42, fontSize: 18 };
            }

            switch (normalized) {
                case 'incident':
                case 'fire':
                    return { color: '#ef4444', icon: 'fa-fire', image: '/fire.png', label: 'Incident', size: 42, fontSize: 18 };
                case 'risk':
                    return { color: '#0284c7', icon: 'fa-triangle-exclamation', image: '/hazard.png', label: 'Risk', size: 34, fontSize: 17 };
                case 'hazard':
                    return { color: '#d97706', icon: 'fa-radiation', image: '/hazard.png', label: 'Hazard', size: 34, fontSize: 17 };
                case 'hydrant':
                    return { color: '#ef4444', icon: 'fa-fire-extinguisher', image: '/hydrant.png', label: 'Hydrant', size: 28, fontSize: 15 };
                case 'school':
                    return { color: '#eab308', icon: 'fa-school', label: 'School', size: 32, fontSize: 16 };
                case 'bridge':
                    return { color: '#6b7280', icon: 'fa-bridge', label: 'Bridge', size: 36, fontSize: 16 };
                case 'barangay':
                    return { color: '#eab308', icon: 'fa-landmark', image: '/hall.png', label: 'Barangay Hall', size: 32, fontSize: 16 };
                case 'station':
                    return { color: '#2563eb', icon: 'fa-building', image: '/station.png', label: 'Fire Station', size: 32, fontSize: 16 };
                default:
                    return { color: '#6b7280', icon: 'fa-location-dot', label: 'Other Marker', size: 24, fontSize: 12 };
            }
        }

        function escapeMapText(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function buildPopup(title, subtitle, description, extras = []) {
            const safeExtras = extras.filter(Boolean).map(escapeMapText);
            return `
                <div style="min-width:220px;">
                    <strong style="display:block;color:#0f172a;font-size:0.94rem;">${escapeMapText(title || 'Untitled marker')}</strong>
                    ${subtitle ? `<div style="margin-top:6px;color:#64748b;font-size:0.74rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;">${escapeMapText(subtitle)}</div>` : ''}
                    ${description ? `<p style="margin:8px 0 0;color:#475569;font-size:0.84rem;line-height:1.55;">${escapeMapText(description)}</p>` : ''}
                    ${safeExtras.length ? `<div style="margin-top:10px;color:#64748b;font-size:0.78rem;line-height:1.5;">${safeExtras.join('<br>')}</div>` : ''}
                </div>
            `;
        }

        function ensurePane(map, name, zIndex) {
            const pane = map.getPane(name) || map.createPane(name);
            pane.style.zIndex = String(zIndex);
            return name;
        }

        async function initOperationsMap() {
            if (!operationsMapElement) {
                return;
            }

            const map = L.map(operationsMapElement, {
                zoomControl: true,
                scrollWheelZoom: true,
            }).setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const panes = {
                risk: ensurePane(map, 'barangayRiskPane', 610),
                hazards: ensurePane(map, 'barangayHazardPane', 620),
                barangay: ensurePane(map, 'barangayHallPane', 630),
                stations: ensurePane(map, 'barangayStationPane', 640),
                resources: ensurePane(map, 'barangayResourcePane', 650),
                incidents: ensurePane(map, 'barangayIncidentPane', 660),
                live: ensurePane(map, 'barangayLivePane', 670),
            };

            const liveLayer = L.layerGroup().addTo(map);
            const incidentsLayer = L.layerGroup().addTo(map);
            const hazardsLayer = L.layerGroup().addTo(map);
            const riskLayer = L.layerGroup().addTo(map);
            const resourcesLayer = L.layerGroup().addTo(map);
            const barangayLayer = L.layerGroup().addTo(map);
            const stationsLayer = L.layerGroup().addTo(map);

            const overlays = {
                'Live': liveLayer,
                'Incidents': incidentsLayer,
                'Hazards': hazardsLayer,
                'Risk': riskLayer,
                'Resources': resourcesLayer,
                'Barangay': barangayLayer,
                'Stations': stationsLayer,
            };

            L.control.layers(null, overlays, {
                collapsed: false,
                position: 'topright',
            }).addTo(map);

            const [markersResponse, incidentsResponse] = await Promise.allSettled([
                fetch('/map/markers/feed', { headers: { Accept: 'application/json' } }),
                fetch('/api/recent-incidents', { headers: { Accept: 'application/json' } }),
            ]);

            let apiMarkers = fallbackMarkerRecords;
            let incidents = [];

            if (markersResponse.status === 'fulfilled' && markersResponse.value.ok) {
                const payload = await markersResponse.value.json();
                apiMarkers = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : fallbackMarkerRecords);
            }

            if (incidentsResponse.status === 'fulfilled' && incidentsResponse.value.ok) {
                const payload = await incidentsResponse.value.json();
                incidents = Array.isArray(payload?.data) ? payload.data : [];
            }

            const incidentMarkerIds = new Set(
                incidents
                    .map((incident) => incident?.marker_id)
                    .filter((value) => value !== null && value !== undefined)
                    .map((value) => String(value))
            );

            const bounds = L.latLngBounds();
            const activeStatusIds = new Set([1, 2, 3]);

            incidents.forEach((incident) => {
                const marker = incident?.marker;
                if (!marker || marker.lat === null || marker.lng === null) {
                    return;
                }

                const lat = Number(marker.lat);
                const lng = Number(marker.lng);
                const isLiveIncident = activeStatusIds.has(Number(incident.incident_status_id || 0));
                const incidentMarker = L.marker([lat, lng], {
                    pane: panes.incidents,
                    icon: buildOperationalIcon({
                        ...getMarkerVisual('incident'),
                    }),
                    zIndexOffset: 300,
                }).bindPopup(buildPopup(
                    marker.title || `Incident #${incident.incident_id}`,
                    `Incident · ${incident.incident_status || incident.status || 'Reported'}`,
                    marker.description || 'No incident description provided.',
                    [
                        incident.created_at ? `Reported: ${new Date(incident.created_at).toLocaleString()}` : null,
                        incident.incident_type_name ? `Type: ${incident.incident_type_name}` : null,
                    ]
                ));

                if (!isLiveIncident) {
                    incidentMarker.addTo(incidentsLayer);
                }
                bounds.extend([lat, lng]);

                if (isLiveIncident) {
                    L.marker([lat, lng], {
                        pane: panes.live,
                        icon: buildOperationalIcon({
                            ...getMarkerVisual('incident', { isLive: true }),
                        }),
                        zIndexOffset: 500,
                    }).bindPopup(buildPopup(
                        marker.title || `Incident #${incident.incident_id}`,
                        `Live · ${incident.incident_status || 'Reported'}`,
                        marker.description || 'Live incident marker.',
                        [
                            incident.created_at ? `Reported: ${new Date(incident.created_at).toLocaleString()}` : null,
                            'Uncheck the Live layer to hide active incident markers.',
                        ]
                    )).addTo(liveLayer);
                }
            });

            riskAreaRecords.forEach((riskArea) => {
                if (riskArea.lat === null || riskArea.lng === null) {
                    return;
                }

                const lat = Number(riskArea.lat);
                const lng = Number(riskArea.lng);
                const riskMarker = L.marker([lat, lng], {
                    pane: panes.risk,
                    icon: buildOperationalIcon(getMarkerVisual('risk')),
                    zIndexOffset: 80,
                }).bindPopup(buildPopup(
                    riskArea.title || 'Risk marker',
                    `Risk · ${(riskArea.assessment_level || riskArea.status || 'pending').toString().replace('_', ' ')}`,
                    riskArea.description || 'No risk description provided.',
                    [
                        riskArea.risk_type ? `Type: ${String(riskArea.risk_type).replaceAll('_', ' ')}` : null,
                        riskArea.risk_score !== null && riskArea.risk_score !== undefined ? `Risk score: ${riskArea.risk_score}` : null,
                        riskArea.station_name ? `Linked station: ${riskArea.station_name}` : null,
                    ]
                ));

                riskMarker.addTo(riskLayer);

                const hazardMarker = L.marker([lat, lng], {
                    pane: panes.hazards,
                    icon: buildOperationalIcon(getMarkerVisual('hazard')),
                    zIndexOffset: 120,
                }).bindPopup(buildPopup(
                    riskArea.title || 'Hazard marker',
                    `Hazard · ${(riskArea.hazard_type || 'general').toString().replace('_', ' ')}`,
                    riskArea.description || 'No hazard description provided.',
                    [
                        riskArea.street_or_sitio ? `Area: ${riskArea.street_or_sitio}` : null,
                        riskArea.status ? `Verification: ${riskArea.status}` : null,
                    ]
                ));

                hazardMarker.addTo(hazardsLayer);
                bounds.extend([lat, lng]);
            });

            const seenFallbackMarkerIds = new Set();

            apiMarkers.forEach((marker) => {
                if (!marker || marker.lat === null || marker.lng === null) {
                    return;
                }

                const markerIdKey = marker.marker_id !== null && marker.marker_id !== undefined ? String(marker.marker_id) : null;
                if (markerIdKey && seenFallbackMarkerIds.has(markerIdKey)) {
                    return;
                }

                if (markerIdKey) {
                    seenFallbackMarkerIds.add(markerIdKey);
                }

                const category = String(marker.category || '').trim().toLowerCase();
                const lat = Number(marker.lat);
                const lng = Number(marker.lng);

                if (category === 'risk') {
                    bounds.extend([lat, lng]);
                    return;
                }

                if (incidentMarkerIds.has(String(marker.marker_id)) || category === 'fire') {
                    if (!incidentMarkerIds.has(String(marker.marker_id))) {
                        L.marker([lat, lng], {
                            pane: panes.hazards,
                            icon: buildOperationalIcon(getMarkerVisual('hazard')),
                            zIndexOffset: 120,
                        }).bindPopup(buildPopup(
                            marker.title || 'Hazard point',
                            'Hazard',
                            marker.description || 'Fire-related hazard marker.'
                        )).addTo(hazardsLayer);
                        bounds.extend([lat, lng]);
                    }
                    return;
                }

                if (category === 'station') {
                    L.marker([lat, lng], {
                        pane: panes.stations,
                        icon: buildOperationalIcon(getMarkerVisual('station')),
                        zIndexOffset: 180,
                    }).bindPopup(buildPopup(
                        marker.title || 'Station',
                        'Station',
                        marker.description || 'Fire station marker.'
                    )).addTo(stationsLayer);
                    bounds.extend([lat, lng]);
                    return;
                }

                if (category === 'barangay') {
                    L.marker([lat, lng], {
                        pane: panes.barangay,
                        icon: buildOperationalIcon(getMarkerVisual('barangay')),
                        zIndexOffset: 150,
                    }).bindPopup(buildPopup(
                        marker.title || 'Barangay Hall',
                        'Barangay',
                        marker.description || 'Barangay hall marker.'
                    )).addTo(barangayLayer);
                    bounds.extend([lat, lng]);
                    return;
                }

                if (['hydrant', 'school', 'bridge'].includes(category)) {
                    const visualCategory = category === 'hydrant' ? 'hydrant' : category;
                    L.marker([lat, lng], {
                        pane: panes.resources,
                        icon: buildOperationalIcon(getMarkerVisual(visualCategory)),
                        zIndexOffset: 210,
                    }).bindPopup(buildPopup(
                        marker.title || 'Resource marker',
                        'Resource',
                        marker.description || 'Mapped response resource.'
                    )).addTo(resourcesLayer);
                    bounds.extend([lat, lng]);
                }
            });

            if (hallRecord && hallRecord.lat !== null && hallRecord.lng !== null) {
                L.marker([Number(hallRecord.lat), Number(hallRecord.lng)], {
                    pane: panes.barangay,
                    icon: buildOperationalIcon(getMarkerVisual('barangay')),
                    zIndexOffset: 160,
                }).bindPopup(buildPopup(
                    hallRecord.title || 'Barangay Hall',
                    'Barangay',
                    hallRecord.description || 'Barangay hall reference point.'
                )).addTo(barangayLayer);
                bounds.extend([Number(hallRecord.lat), Number(hallRecord.lng)]);
            }

            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.14));
            }

            liveCountElement.textContent = String(liveLayer.getLayers().length);
            incidentCountElement.textContent = String(incidentsLayer.getLayers().length);
            riskCountElement.textContent = String(riskLayer.getLayers().length);
            updatedAtElement.textContent = new Date().toLocaleString([], {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });

            window.setTimeout(() => map.invalidateSize(), 180);
        }

        async function initFirefighterStyleOperationsMap() {
            if (!operationsMapElement) return;

            const map = L.map(operationsMapElement, { zoomControl: true, scrollWheelZoom: true })
                .setView([defaultLat, defaultLng], 14);
            window.barangayOperationsMap = map;

            const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);
            const hotStreet = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap, HOT',
            });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 18, attribution: 'Tiles &copy; Esri',
            });
            const waterReference = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Hydro_Reference/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 18, attribution: 'Hydro reference &copy; Esri',
            });

            const stationLayer = L.layerGroup().addTo(map);
            const incidentLayer = L.layerGroup().addTo(map);
            const hydrantLayer = L.layerGroup().addTo(map);
            const riskHazardLayer = L.layerGroup().addTo(map);
            const otherLayer = L.layerGroup().addTo(map);
            const boundaryLayer = L.layerGroup().addTo(map);

            const baseMaps = {
                'OSM Standard': street,
                'OSM HOT': hotStreet,
                'Satellite': satellite,
            };
            const overlays = {
                'Fire Stations': stationLayer,
                'Incidents': incidentLayer,
                'Fire Hydrants': hydrantLayer,
                'Risk & Hazard Reports': riskHazardLayer,
                'Other Markers': otherLayer,
                'Barangay Boundary': boundaryLayer,
                'Water Reference': waterReference,
            };

            L.control.layers(baseMaps, overlays, { collapsed: true, position: 'topright' }).addTo(map);

            function ringContains(ring, lat, lng) {
                let inside = false;
                for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                    const current = ring[i] || [];
                    const previous = ring[j] || [];
                    const xi = Number(current[0]);
                    const yi = Number(current[1]);
                    const xj = Number(previous[0]);
                    const yj = Number(previous[1]);
                    const intersects = ((yi > lat) !== (yj > lat))
                        && (lng < ((xj - xi) * (lat - yi) / ((yj - yi) || 1e-12)) + xi);
                    if (intersects) inside = !inside;
                }
                return inside;
            }

            function polygonContains(rings, lat, lng) {
                if (!Array.isArray(rings) || !rings.length || !ringContains(rings[0], lat, lng)) return false;
                return !rings.slice(1).some((hole) => ringContains(hole, lat, lng));
            }

            function geometryContains(geojson, lat, lng) {
                if (!geojson || typeof geojson !== 'object') return false;
                if (geojson.type === 'Feature') return geometryContains(geojson.geometry, lat, lng);
                if (geojson.type === 'FeatureCollection') return (geojson.features || []).some((feature) => geometryContains(feature, lat, lng));
                if (geojson.type === 'Polygon') return polygonContains(geojson.coordinates || [], lat, lng);
                if (geojson.type === 'MultiPolygon') return (geojson.coordinates || []).some((polygon) => polygonContains(polygon, lat, lng));
                return false;
            }

            function distanceKm(lat1, lng1, lat2, lng2) {
                const radians = (value) => value * Math.PI / 180;
                const earthRadius = 6371;
                const dLat = radians(lat2 - lat1);
                const dLng = radians(lng2 - lng1);
                const a = Math.sin(dLat / 2) ** 2
                    + Math.cos(radians(lat1)) * Math.cos(radians(lat2)) * Math.sin(dLng / 2) ** 2;
                return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            function belongsToBarangay(marker) {
                const lat = Number(marker?.lat);
                const lng = Number(marker?.lng);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
                if (!barangayBoundaryGeojson) return distanceKm(defaultLat, defaultLng, lat, lng) <= 8;
                if (geometryContains(barangayBoundaryGeojson, lat, lng)) return true;
                return String(marker?.category || '').toLowerCase() === 'station'
                    && distanceKm(defaultLat, defaultLng, lat, lng) <= 20;
            }

            if (barangayBoundaryGeojson) {
                const boundary = L.geoJSON(barangayBoundaryGeojson, {
                    style: { color: '#0f766e', weight: 2, opacity: 0.85, fillColor: '#14b8a6', fillOpacity: 0.06, dashArray: '7 6' },
                }).addTo(boundaryLayer);
                const boundaryBounds = boundary.getBounds();
                if (boundaryBounds.isValid()) map.fitBounds(boundaryBounds.pad(0.08));
            }

            function incidentStatus(marker) {
                return String(marker?.incident_public?.status || marker?.incident_status || marker?.status || 'reported').trim().toLowerCase();
            }

            function isActiveIncident(marker) {
                return ['active', 'reported', 'responding', 'en-route', 'en route', 'on-scene', 'on scene']
                    .includes(incidentStatus(marker));
            }

            function addMarker(marker, seenIds, bounds) {
                if (!marker || !belongsToBarangay(marker)) return;
                const markerKey = marker.marker_id === null || marker.marker_id === undefined ? null : String(marker.marker_id);
                if (markerKey && seenIds.has(markerKey)) return;
                if (markerKey) seenIds.add(markerKey);

                const category = String(marker.category || 'other').trim().toLowerCase();
                const lat = Number(marker.lat);
                const lng = Number(marker.lng);
                const visual = getMarkerVisual(category, { isLive: category === 'fire' && isActiveIncident(marker) });
                const popupExtras = [];
                if (marker.station_public?.contact_number) popupExtras.push(`Contact: ${marker.station_public.contact_number}`);
                if (marker.hydrant_public?.code) popupExtras.push(`Hydrant: ${marker.hydrant_public.code}`);
                if (marker.risk_area_public?.condition_label) popupExtras.push(`Condition: ${marker.risk_area_public.condition_label}`);
                if (marker.risk_area_public?.review_status) popupExtras.push(`Review: ${marker.risk_area_public.review_status}`);

                const leafletMarker = L.marker([lat, lng], {
                    icon: buildOperationalIcon(visual),
                    zIndexOffset: category === 'fire' ? 400 : 100,
                }).bindPopup(buildPopup(
                    marker.title || visual.label,
                    category === 'fire' ? incidentStatus(marker) : visual.label,
                    marker.description || '',
                    popupExtras
                ));

                if (category === 'station') leafletMarker.addTo(stationLayer);
                else if (category === 'fire' || category === 'incident') leafletMarker.addTo(incidentLayer);
                else if (category === 'hydrant') leafletMarker.addTo(hydrantLayer);
                else if (category === 'risk' || category === 'hazard') leafletMarker.addTo(riskHazardLayer);
                else leafletMarker.addTo(otherLayer);

                if (!barangayBoundaryGeojson || geometryContains(barangayBoundaryGeojson, lat, lng)) bounds.extend([lat, lng]);
            }

            async function refreshMarkers({ fit = false } = {}) {
                const response = await fetch('/map/markers/feed', {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                });
                const payload = response.ok ? await response.json() : [];
                const feedMarkers = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                const mergedMarkers = feedMarkers.length ? feedMarkers : fallbackMarkerRecords;

                stationLayer.clearLayers();
                incidentLayer.clearLayers();
                hydrantLayer.clearLayers();
                riskHazardLayer.clearLayers();
                otherLayer.clearLayers();

                const seenIds = new Set();
                const bounds = L.latLngBounds();
                mergedMarkers.forEach((marker) => addMarker(marker, seenIds, bounds));
                riskAreaRecords.forEach((riskArea) => addMarker({
                    ...riskArea,
                    category: riskArea.entry_type === 'hazard' ? 'hazard' : 'risk',
                    risk_area_public: {
                        condition_label: riskArea.risk_type || riskArea.hazard_type,
                        review_status: riskArea.status,
                    },
                }, seenIds, bounds));

                const activeIncidentIds = new Set(
                    mergedMarkers
                        .filter((marker) => belongsToBarangay(marker))
                        .filter((marker) => ['fire', 'incident'].includes(String(marker.category || '').trim().toLowerCase()))
                        .filter(isActiveIncident)
                        .map((marker) => marker.marker_id ?? `${marker.lat}|${marker.lng}`)
                );
                const activeIncidents = activeIncidentIds.size;
                if (liveCountElement) liveCountElement.textContent = String(activeIncidents);
                if (incidentCountElement) incidentCountElement.textContent = String(incidentLayer.getLayers().length);
                if (riskCountElement) riskCountElement.textContent = String(riskHazardLayer.getLayers().length);
                if (updatedAtElement) updatedAtElement.textContent = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

                if (fit && !barangayBoundaryGeojson && bounds.isValid()) map.fitBounds(bounds.pad(0.14));
            }

            await refreshMarkers({ fit: true });
            const refreshTimer = window.setInterval(() => refreshMarkers().catch((error) => console.warn('Barangay marker refresh failed:', error)), 30000);
            window.addEventListener('pagehide', () => window.clearInterval(refreshTimer), { once: true });
            window.setTimeout(() => map.invalidateSize(), 180);
        }

        function initRiskPickerMap() {
            const mapElement = document.getElementById('barangayRiskAreaMap');
            const latInput = document.getElementById('risk_area_lat');
            const lngInput = document.getElementById('risk_area_lng');
            const useCurrentLocationBtn = document.getElementById('useRiskCurrentLocationBtn');

            if (!mapElement || !latInput || !lngInput) {
                return;
            }

            const pickerLat = parseFloat(latInput.value || String(defaultLat));
            const pickerLng = parseFloat(lngInput.value || String(defaultLng));
            const map = L.map(mapElement).setView([pickerLat, pickerLng], 14);
            window.barangayRiskPickerMap = map;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            let selectedMarker = null;

            function updateCoordinates(lat, lng) {
                const fixedLat = Number(lat).toFixed(7);
                const fixedLng = Number(lng).toFixed(7);

                latInput.value = fixedLat;
                lngInput.value = fixedLng;

                if (!selectedMarker) {
                    selectedMarker = L.marker([lat, lng]).addTo(map);
                } else {
                    selectedMarker.setLatLng([lat, lng]);
                }
            }

            updateCoordinates(pickerLat, pickerLng);

            map.on('click', function (event) {
                updateCoordinates(event.latlng.lat, event.latlng.lng);
            });

            useCurrentLocationBtn?.addEventListener('click', async function () {
                if (!navigator.geolocation) {
                    window.alert('Location is not supported by this browser. Select the location on the map instead.');
                    return;
                }

                const previousLabel = useCurrentLocationBtn.innerHTML;
                useCurrentLocationBtn.disabled = true;
                useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';

                try {
                    if (typeof window.PyroLocation?.getReliableLocationFix !== 'function') {
                        throw new Error('Location service is still loading. Please try again.');
                    }
                    const fix = await window.PyroLocation.getReliableLocationFix({ acceptBestAvailable: true });
                    const lat = fix.lat;
                    const lng = fix.lng;
                    updateCoordinates(lat, lng);
                    map.setView([lat, lng], 16, { animate: true });
                } catch (error) {
                    if (error?.locationReason !== 'cancelled') {
                        window.alert(error?.message || 'Unable to get your location. Select it on the map or try again.');
                    }
                } finally {
                    useCurrentLocationBtn.disabled = false;
                    useCurrentLocationBtn.innerHTML = previousLabel;
                }
            });

            window.setTimeout(function () {
                map.invalidateSize();
            }, 120);
        }

        initFirefighterStyleOperationsMap().catch((error) => {
            console.error('Failed to initialize barangay operations map:', error);
        });
        initRiskPickerMap();
    });
</script>
@endpush
